import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Screen that loads and displays all orders for the current user.
class MyOrdersScreen extends StatefulWidget {
  @override
  _MyOrdersScreenState createState() => _MyOrdersScreenState();
}

class _MyOrdersScreenState extends State<MyOrdersScreen> {
  List<dynamic> orders = [];
  bool isLoading = false;
  String? userId;

  @override
  void initState() {
    super.initState();
    _loadUserAndOrders();
  }

  Future<void> _loadUserAndOrders() async {
    final prefs = await SharedPreferences.getInstance();
    // Retrieve the userId from SharedPreferences.
    userId = prefs.getString('userId');
    if (userId == null) {
      // For demo purposes if userId is missing, we set a default value.
      userId = '1';
      await prefs.setString('userId', userId!);
    }
    await _getOrders();
  }

  Future<void> _getOrders() async {
    if (userId == null) return;
    setState(() {
      isLoading = true;
    });
    try {
      // Replace with your actual endpoint URL.
      final url = Uri.parse(
          'https://bread.ayeshmadusanka.site/api/get_orders.php?user_id=$userId');
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            orders = data['orders'];
          });
        } else {
          _showMessage(data['message'] ?? 'Failed to load orders.');
        }
      } else {
        _showMessage('Failed to load orders. HTTP: ${response.statusCode}');
      }
    } catch (e) {
      _showMessage('Error: $e');
    } finally {
      setState(() {
        isLoading = false;
      });
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  // When an order is tapped, navigate to the order details page.
  void _openOrderDetails(dynamic order) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) =>
            OrderDetailsScreen(orderId: order['order_id']),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        centerTitle: true,
        title: Text(
          "My Orders",
          style: TextStyle(
            color: Colors.orange.shade900,
          ),
        ),
          leading: IconButton(
            icon: Icon(
              Icons.arrow_back,
              color: Colors.orange.shade900,
            ),
            onPressed: () {
              Navigator.pop(context);
              // Implement navigation if needed.
            },
          ),

      ),

      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : orders.isEmpty
          ? Center(child: Text("No orders found."))
          : RefreshIndicator(
        onRefresh: _getOrders,
        child: ListView.builder(
          padding: const EdgeInsets.all(10),
          itemCount: orders.length,
          itemBuilder: (context, index) {
            final order = orders[index];
            return GestureDetector(
              onTap: () => _openOrderDetails(order),
              child: OrderCard(order: order),
            );
          },
        ),
      ),
    );
  }
}

/// Widget that displays a summary card for each order.
class OrderCard extends StatelessWidget {
  final Map<String, dynamic> order;

  OrderCard({required this.order});

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Colors.white, // Set card background to white.
      elevation: 2,
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 5),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header row with order type and status.
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  "Dine-in",
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                Container(
                  padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.orange[100],
                    borderRadius: BorderRadius.circular(5),
                  ),
                  child: Text(
                    order['status'],
                    style: TextStyle(color: Colors.orange[900], fontSize: 12),
                  ),
                ),
              ],
            ),
            SizedBox(height: 8),
            Text("Order # ${order['order_id']}",
                style: TextStyle(fontSize: 14)),
            Text(order['order_date'] ?? "",
                style: TextStyle(color: Colors.grey, fontSize: 12)),
            SizedBox(height: 8),
            // Displaying a summary for the first order item (if provided by your API).
            Row(
              children: [

                Expanded(child: Text(order['item'] ?? "Quantity")),
                Text(" ${order['total_quantity'] ?? 1}",
                    style: TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
            SizedBox(height: 8),
            Text("USD. ${order['total_price']}",
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}

class OrderDetailsScreen extends StatefulWidget {
  final int orderId;
  OrderDetailsScreen({required this.orderId});

  @override
  _OrderDetailsScreenState createState() => _OrderDetailsScreenState();
}

class _OrderDetailsScreenState extends State<OrderDetailsScreen> {
  Map<String, dynamic>? order;
  List<dynamic> orderItems = [];
  bool isLoading = false;
  int totalQuantity = 0;

  @override
  void initState() {
    super.initState();
    _getOrderDetails();
  }

  Future<void> _getOrderDetails() async {
    setState(() {
      isLoading = true;
    });
    try {
      final url = Uri.parse(
          'https://bread.ayeshmadusanka.site/api/get_orders.php?order_id=${widget
              .orderId}');
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            order = data['order'];
            orderItems = data['order_items'];
            totalQuantity = data['total_quantity'] ?? 0;
          });
        } else {
          _showMessage(data['message'] ?? 'Failed to load order details.');
        }
      } else {
        _showMessage('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      _showMessage('Error: $e');
    } finally {
      setState(() {
        isLoading = false;
      });
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  Widget _buildOrderItemsTile() {
    return Container(
      padding: EdgeInsets.all(10),
      margin: EdgeInsets.only(top: 10),
      decoration: BoxDecoration(
        color: Colors.white, // White background
        borderRadius: BorderRadius.circular(8),
        boxShadow: [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 4,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Table Headers
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                flex: 4,
                child: Text(
                  "Item",
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
              Expanded(
                flex: 2,
                child: Text(
                  "Quantity",
                  textAlign: TextAlign.center,
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
              Expanded(
                flex: 3,
                child: Text(
                  "Price",
                  textAlign: TextAlign.right,
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          Divider(),

          // Table Data
          ...orderItems.map((item) =>
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 5),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      flex: 4,
                      child: Text(
                        item['item_name'] ?? "Item Name",
                        style: TextStyle(fontSize: 14),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Expanded(
                      flex: 2,
                      child: Text(
                        "${item['quantity']}",
                        textAlign: TextAlign.center,
                      ),
                    ),
                    Expanded(
                      flex: 3,
                      child: Text(
                        "USD. ${item['price']}",
                        textAlign: TextAlign.right,
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }


  Widget _buildTotals() {
    return Container(
      padding: EdgeInsets.all(3),
      margin: EdgeInsets.only(top: 20),
      decoration: BoxDecoration(

        // Changed background color to orange.shade100
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "Total Quantity:",
                style: TextStyle(
                  fontSize: 16,

                  color: Colors.orange.shade900,
                ),
              ),
              Text(
                "$totalQuantity",
                style: TextStyle(
                  fontSize: 16,

                  color: Colors.orange.shade900,
                ),
              ),
            ],
          ),
          SizedBox(height: 5), // Spacing between the two lines
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "Total Price:",
                style: TextStyle(
                  fontSize: 16,

                  color: Colors.orange.shade900,
                ),
              ),
              Text(
                "USD. ${order!['total_price']}",
                style: TextStyle(
                  fontSize: 16,

                  color: Colors.orange.shade900,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white, // Set background to white
        centerTitle: true, // Ensure title is centered
        title: Text(
          "Order Details",
          style: TextStyle(
            color: Colors.orange.shade900, // Set text color to orange.shade900
          ),
        ),
        leading: IconButton(
          icon: Icon(
            Icons.arrow_back,
            color: Colors.orange.shade900, // Set icon color to orange.shade900
          ),
          onPressed: () {
            Navigator.pop(context); // Navigate back
          },
        ),
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : order == null
          ? Center(child: Text("No details found."))
          : Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12.0),
        // Add left and right padding
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Order header details
              Text("Order # ${order!['order_id']}",
                  style: TextStyle(
                      fontSize: 16, fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              Text("Date: ${order!['order_date']}"),
              SizedBox(height: 8),
              Text("Status: ${order!['status']}"),
              SizedBox(height: 10),
              // Order Items list

              _buildOrderItemsTile(),

              // Totals section below items
              _buildTotals(),
            ],
          ),
        ),
      ),
    );
  }
}
