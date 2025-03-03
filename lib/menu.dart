import 'package:flutter/material.dart';
import 'cart.dart';
import 'home.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';

class MenuPage extends StatefulWidget {
  @override
  _MenuPageState createState() => _MenuPageState();
}

class _MenuPageState extends State<MenuPage> {
  List<Map<String, dynamic>> menuItems = [];
  List<Map<String, dynamic>> allMenuItems = [];
  String selectedFilter = "All"; // Default filter
  TextEditingController searchController = TextEditingController();

  // Function to fetch menu items from API
  Future<void> fetchMenuItems() async {
    try {
      // Build URL based on filter: if "All", do not include parameter
      String url = '$baseUrl/fetch_items.php';
      if (selectedFilter != "All") {
        url += '?item_type=$selectedFilter';
      }
      final response = await http.get(Uri.parse(url));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true) {
          // Save the fetched items locally
          allMenuItems = List<Map<String, dynamic>>.from(data['items']);
          // Apply any current search filter
          filterMenuItems(searchController.text);
        } else {
          print('Failed to fetch items: ${data['message']}');
        }
      } else {
        print('Failed to load data. HTTP status: ${response.statusCode}');
      }
    } catch (error) {
      print('Error fetching menu items: $error');
    }
  }

  // Filter the locally loaded items based on the search query
  void filterMenuItems(String query) {
    List<Map<String, dynamic>> filteredItems;
    if (query.isEmpty) {
      filteredItems = List<Map<String, dynamic>>.from(allMenuItems);
    } else {
      filteredItems = allMenuItems.where((item) {
        final itemName = item['name'].toString().toLowerCase();
        return itemName.contains(query.toLowerCase());
      }).toList();
    }
    setState(() {
      menuItems = filteredItems;
    });
  }

  // Save item to cart using SharedPreferences
  Future<void> addToCart(Map<String, dynamic> item) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> cartItems = prefs.getStringList('cart') ?? [];

    bool itemExists = false;

    for (int i = 0; i < cartItems.length; i++) {
      Map<String, dynamic> cartItem = json.decode(cartItems[i]);

      // Check if the item already exists in the cart
      if (cartItem['item_id'] == item['item_id']) {
        // Item exists, update its quantity
        cartItem['quantity']++;
        cartItems[i] = json.encode(cartItem);
        itemExists = true;
        break;
      }
    }

    // If item doesn't exist, add it as a new item
    if (!itemExists) {
      item['quantity'] = 1;
      item['imagePath'] = item['image_path'];
      item['title'] = item['name'];
      cartItems.add(json.encode(item));
    }

    await prefs.setStringList('cart', cartItems);
    print('Cart saved to SharedPreferences: $cartItems');
  }

  @override
  void initState() {
    super.initState();
    fetchMenuItems(); // Fetch items when the page loads
    // Listen for changes in the search field to filter items locally
    searchController.addListener(() {
      filterMenuItems(searchController.text);
    });
  }

  // Update filter and refresh items, also clear search field
  void updateFilter(String filter) {
    setState(() {
      selectedFilter = filter;
      searchController.clear();
      menuItems = [];
      allMenuItems = [];
    });
    fetchMenuItems();
  }

  @override
  void dispose() {
    searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: Colors.orange.shade900),
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => HomeScreen()),
            );
          },
        ),
        title: Text(
          'MENU',
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.normal,
            color: Colors.orange.shade900,
          ),
        ),
        centerTitle: true,
      ),
      body: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Column(
          children: [
            // Search field filtering local items
            SizedBox(
              height: 55,
              child: TextField(
                controller: searchController,
                decoration: InputDecoration(
                  hintText: 'Search',
                  prefixIcon:
                  Icon(Icons.search, color: Colors.orange.shade900),
                  filled: true,
                  fillColor: Colors.orange.shade50,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(30),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            SizedBox(height: 12),
            // Filter buttons for item type
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  // All button
                  SizedBox(
                    width: 120,
                    child: ElevatedButton(
                      onPressed: () => updateFilter("All"),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange.shade900,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8.0),
                        ),
                      ),
                      child: Text(
                        'All',
                        style: TextStyle(color: Colors.white),
                      ),
                    ),
                  ),
                  SizedBox(width: 18),
                  // Food button
                  SizedBox(
                    width: 120,
                    child: ElevatedButton(
                      onPressed: () => updateFilter("Food"),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange.shade900,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8.0),
                        ),
                      ),
                      child: Text(
                        'Food',
                        style: TextStyle(color: Colors.white),
                      ),
                    ),
                  ),
                  SizedBox(width: 18),
                  // Beverages button
                  SizedBox(
                    width: 120,
                    child: ElevatedButton(
                      onPressed: () => updateFilter("Beverage"),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange.shade900,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8.0),
                        ),
                      ),
                      child: Text(
                        'Beverages',
                        style: TextStyle(color: Colors.white),
                      ),
                    ),
                  ),
                  SizedBox(width: 8),
                ],
              ),
            ),
            SizedBox(height: 16),
            // List of items
            Expanded(
              child: menuItems.isEmpty
                  ? Center(child: CircularProgressIndicator())
                  : ListView.builder(
                itemCount: menuItems.length,
                itemBuilder: (context, index) {
                  final item = menuItems[index];
                  return Container(
                    margin: EdgeInsets.symmetric(vertical: 8.0),
                    padding: EdgeInsets.all(12.0),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16.0),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.2),
                          blurRadius: 6,
                          offset: Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Stack(
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 100,
                              height: 100,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(12.0),
                                image: DecorationImage(
                                  image: NetworkImage(
                                      '$imageUrl${item['image_path']}'),
                                  fit: BoxFit.cover,
                                ),
                              ),
                            ),
                            SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment:
                                CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item['name'],
                                    style: TextStyle(
                                      fontSize: 16,
                                      color: Colors.orange.shade900,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  SizedBox(height: 6),
                                  Text(
                                    item['description'],
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.orange.shade900,
                                    ),
                                  ),
                                  SizedBox(height: 20),
                                  Text(
                                    '\$${item['price']}',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.orange.shade900,
                                    ),
                                  ),
                                  SizedBox(height: 10),
                                ],
                              ),
                            ),
                          ],
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: ElevatedButton(
                            onPressed: () {
                              addToCart(item).then((_) {
                                ScaffoldMessenger.of(context)
                                    .showSnackBar(
                                  SnackBar(
                                      content: Text(
                                          '${item['name']} added to cart')),
                                );
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                      builder: (context) => CartPage()),
                                );
                              });
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.orange.shade900,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8.0),
                              ),
                            ),
                            child: Text(
                              'Add to Cart',
                              style: TextStyle(color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}