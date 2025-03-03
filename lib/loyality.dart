import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;

class LoyaltyPage extends StatefulWidget {
  @override
  _LoyaltyPageState createState() => _LoyaltyPageState();
}

class _LoyaltyPageState extends State<LoyaltyPage> {
  String? userId;
  String? username;
  bool isLoading = false;
  int availableBalance = 0;
  int totalEarned = 0;

  @override
  void initState() {
    super.initState();
    _loadUserAndOrders();
  }

  // Load the user ID and username, then get the loyalty points.
  Future<void> _loadUserAndOrders() async {
    final prefs = await SharedPreferences.getInstance();
    userId = prefs.getString('userId');
    username = prefs.getString('username');

    // If userId or username is not available, set default values.
    if (userId == null) {
      userId = '1';
      await prefs.setString('userId', userId!);
    }
    if (username == null) {
      username = 'ayeshmadusanka';
      await prefs.setString('username', username!);
    }

    await _getLoyaltyPoints();
  }

  // Fetch loyalty points from the API.
  Future<void> _getLoyaltyPoints() async {
    if (userId == null) return;
    setState(() {
      isLoading = true;
    });
    try {
      // Replace with your actual endpoint URL.
      final url = Uri.parse(
          'https://bread.ayeshmadusanka.site/api/get_loyalty_points.php?user_id=$userId');
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            availableBalance = data['loyalty_points'];
            totalEarned = data['loyalty_points'];
          });
        } else {
          _showMessage(data['message'] ?? 'Failed to load loyalty points.');
        }
      } else {
        _showMessage('Failed to load loyalty points. HTTP: ${response.statusCode}');
      }
    } catch (e) {
      _showMessage('Error: $e');
    } finally {
      setState(() {
        isLoading = false;
      });
    }
  }

  // Display a snack bar message.
  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            // Close Button at the Top Right
            Align(
              alignment: Alignment.topRight,
              child: IconButton(
                icon: Icon(
                  Icons.close,
                  color: Colors.black,
                  size: 30,
                ),
                onPressed: () {
                  Navigator.pop(context); // Close action
                },
              ),
            ),
            Expanded(
              child: Center(
                child: Transform.translate(
                  offset: Offset(0, -50), // Move the elements up by 50 pixels
                  child: isLoading
                      ? CircularProgressIndicator()
                      : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Username',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w400,
                          color: Colors.grey,
                        ),
                      ),
                      SizedBox(height: 10),
                      Text(
                        username ?? '',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      SizedBox(height: 30),
                      Text(
                        'Available Balance',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w400,
                          color: Colors.grey,
                        ),
                      ),
                      SizedBox(height: 10),
                      Text(
                        '$availableBalance',
                        style: TextStyle(
                          fontSize: 48,
                          fontWeight: FontWeight.bold,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      SizedBox(height: 30),
                      Text(
                        'Total Earned',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w400,
                          color: Colors.grey,
                        ),
                      ),
                      SizedBox(height: 10),
                      Text(
                        '$totalEarned',
                        style: TextStyle(
                          fontSize: 48,
                          fontWeight: FontWeight.bold,
                          color: Colors.orange.shade900,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
