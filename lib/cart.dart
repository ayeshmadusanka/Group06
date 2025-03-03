import 'package:ai_project/orders.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'menu.dart';
import 'config.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart'; // Import for formatting date

void main() {
  runApp(MaterialApp(
    home: CartPage(),
  ));
}

class CartPage extends StatefulWidget {
  @override
  _CartPageState createState() => _CartPageState();
}

class _CartPageState extends State<CartPage> {
  List<CartItemData> cartItems = [];

  @override
  void initState() {
    super.initState();
    _loadCart();
  }

  Future<void> placeOrder() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId'); // Retrieve user ID

    if (userId == null) {
      print('User ID is not available');
      return;
    }

    // Prepare order data with item_id instead of title
    List<Map<String, dynamic>> orderItems = cartItems.map((item) {
      return {
        'item_id': item.itemId, // now sending item_id
        'price': item.price,
        'quantity': item.quantity,
      };
    }).toList();

    // Get current date and time in 'YYYY-MM-DD HH:mm:ss' format
    String orderDate = DateFormat('yyyy-MM-dd HH:mm:ss').format(DateTime.now());

    Map<String, dynamic> orderData = {
      'user_id': userId,
      'order_items': orderItems,
      'total_price': calculateTotal(),
      'order_date': orderDate,
    };

    // Convert order data to JSON
    String jsonOrderData = json.encode(orderData);

    // Log the request being sent
    print('Sending order data: $jsonOrderData');

    try {
      final response = await http.post(
        Uri.parse('https://bread.ayeshmadusanka.site/api/add_order.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonOrderData,
      );

      // Log the raw response body
      print('Server response (${response.statusCode}): ${response.body}');

      if (response.statusCode == 200) {
        var responseData = json.decode(response.body);
        if (responseData['success'] == true) {
          // Clear the cart after a successful order
          setState(() {
            cartItems.clear();
          });

          // Remove the saved cart from SharedPreferences
          await prefs.remove('cart');
          print('Order placed successfully');

          // Navigate to MyOrdersScreen
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => MyOrdersScreen()),
          );
        }
        else {
          print('Order failed: ${responseData['message']}');
        }
      } else {
        print('Failed to place order. HTTP Status: ${response.statusCode}');
      }
    } catch (e) {
      print('Error placing order: $e');
    }
  }

  // Load cart from SharedPreferences
  Future<void> _loadCart() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> savedCartItems = prefs.getStringList('cart') ?? [];

    // Debug log to check if the cart is loaded correctly
    print('Cart loaded from SharedPreferences: $savedCartItems');

    setState(() {
      // Deserialize cart items from the list of JSON strings
      cartItems = savedCartItems.map((item) => CartItemData.fromJson(json.decode(item))).toList();
    });

    // Debug log to check if the cartItems list is updated correctly
    print('Cart items after loading: ${cartItems.map((item) => item.itemId).toList()}');
  }

  // Save cart to SharedPreferences
  Future<void> _saveCart() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> savedCartItems = cartItems.map((item) => json.encode(item.toJson())).toList();

    // Debug log to check if the cart is being saved correctly
    print('Saving cart: $savedCartItems');

    await prefs.setStringList('cart', savedCartItems);

    // Log the saved cart after saving
    print('Cart saved to SharedPreferences: ${prefs.getStringList('cart')}');
  }

  // Function to calculate the total price
  double calculateTotal() {
    double total = 0;
    for (var item in cartItems) {
      total += item.price * item.quantity;
    }
    return total;
  }

  // Function to update quantity for a cart item
  void updateQuantity(int index, int change) {
    setState(() {
      cartItems[index].quantity += change;
      if (cartItems[index].quantity < 1) {
        cartItems[index].quantity = 1; // Prevent quantity from going below 1
      }
    });
    _saveCart();
  }

  // Function to remove an item from the cart
  void removeItem(int index) {
    setState(() {
      cartItems.removeAt(index);
    });
    _saveCart();
  }

  @override
  Widget build(BuildContext context) {
    double totalPrice = calculateTotal(); // Calculate the total price

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.close, color: Colors.orange.shade900),
          onPressed: () {
            Navigator.pop(context);
          },
        ),
        title: Text(
          'My Cart',
          style: TextStyle(color: Colors.orange.shade900),
        ),
        centerTitle: true,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            _buildFreeShippingBanner(),
            SizedBox(height: 16),
            Expanded(
              child: ListView.builder(
                itemCount: cartItems.length,
                itemBuilder: (context, index) {
                  return CartItem(
                    imagePath: cartItems[index].imagePath,
                    title: cartItems[index].title,
                    price: cartItems[index].price,
                    quantity: cartItems[index].quantity,
                    onRemove: () => removeItem(index),
                    onIncrease: () => updateQuantity(index, 1),
                    onDecrease: () => updateQuantity(index, -1),
                  );
                },
              ),
            ),
            Divider(thickness: 1),
            _buildTotalSection(totalPrice), // Pass the calculated total price
            SizedBox(height: 8),
            _buildCheckoutButton(),
            SizedBox(height: 8),
            _buildContinueShoppingButton(context),
          ],
        ),
      ),
    );
  }

  Widget _buildFreeShippingBanner() {
    return Container(
      padding: EdgeInsets.symmetric(vertical: 8.0),
      color: Colors.orange[50],
      width: double.infinity,
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center, // Centers the row contents
          children: [
            Icon(
              Icons.card_giftcard_rounded, // Promo code icon
              color: Colors.orange.shade900,
              size: 24, // Adjust the size as needed
            ),
            SizedBox(width: 8), // Spacing between the icon and text
            Text(
              'APPLY YOUR PROMO CODE HERE',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: Colors.orange.shade900,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalSection(double totalPrice) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            'Total',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.orange.shade900,
            ),
          ),
          Text(
            '\$${totalPrice.toStringAsFixed(2)}', // Display the total with 2 decimal places
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.orange.shade900,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCheckoutButton() {
    return ElevatedButton(
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.orange.shade900,
        padding: EdgeInsets.symmetric(vertical: 15),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(30),
        ),
      ),
      onPressed: () {
        // Call the placeOrder method when the button is pressed
        placeOrder();
      },
      child: Center(
        child: Text(
          'CHECK OUT',
          style: TextStyle(
            fontSize: 16,
            color: Colors.white,
          ),
        ),
      ),
    );
  }

  Widget _buildContinueShoppingButton(BuildContext context) {
    return TextButton(
      onPressed: () {
        Navigator.push(
          context,
          MaterialPageRoute(builder: (context) => MenuPage()),
        );
      },
      child: Text(
        'CONTINUE SHOPPING',
        style: TextStyle(
          fontSize: 16,
          color: Colors.orange.shade900,
        ),
      ),
    );
  }
}

class CartItemData {
  final int itemId; // New property for the item identifier
  final String imagePath;
  final String title;
  final double price;
  int quantity;

  CartItemData({
    required this.itemId,
    required this.imagePath,
    required this.title,
    required this.price,
    required this.quantity,
  });

  // Convert CartItemData to a JSON map
  Map<String, dynamic> toJson() {
    return {
      'item_id': itemId,
      'imagePath': imagePath,
      'title': title,
      'price': price,
      'quantity': quantity,
    };
  }

  // Create CartItemData from a JSON map with null checks
  factory CartItemData.fromJson(Map<String, dynamic> json) {
    return CartItemData(
      itemId: json['item_id'] ?? 0,
      imagePath: json['imagePath'] ?? '',
      title: json['title'] ?? '',
      price: json['price'] is String ? double.tryParse(json['price']) ?? 0.0 : json['price'] ?? 0.0,
      quantity: json['quantity'] ?? 1,
    );
  }
}

class CartItem extends StatelessWidget {
  final String imagePath;
  final String title;
  final double price;
  final int quantity;
  final VoidCallback onRemove;
  final VoidCallback onIncrease;
  final VoidCallback onDecrease;

  const CartItem({
    required this.imagePath,
    required this.title,
    required this.price,
    required this.quantity,
    required this.onRemove,
    required this.onIncrease,
    required this.onDecrease,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Stack(
        children: [
          Container(
            padding: EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12.0),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  blurRadius: 5.0,
                  spreadRadius: 1.0,
                ),
              ],
            ),
            child: Row(
              children: [
                Image.network(
                  imageUrl + imagePath,
                  width: 80,
                  height: 80,
                  fit: BoxFit.contain,
                ),
                SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      SizedBox(height: 8),
                      Text(
                        '\$${price.toStringAsFixed(2)}',
                        style: TextStyle(
                          color: Colors.orange.shade900.withOpacity(0.7),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Positioned(
            top: 8,
            right: 8,
            child: IconButton(
              icon: Icon(Icons.cancel, color: Colors.orange.shade900),
              onPressed: onRemove,
            ),
          ),
          Positioned(
            bottom: 8,
            right: 8,
            child: Row(
              children: [
                IconButton(
                  icon: Icon(Icons.remove_circle, color: Colors.orange.shade900),
                  onPressed: onDecrease,
                ),
                Text(
                  '$quantity',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: Colors.orange.shade900,
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.add_circle, color: Colors.orange.shade900),
                  onPressed: onIncrease,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
