import 'package:ai_project/orders.dart';
import 'package:flutter/material.dart';
import 'loyality.dart'; // Import loyalty.dart
import 'login.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'orders.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> with TickerProviderStateMixin {
  String username = "Loading..."; // Default value to show while loading
  final String phoneNumber = " ";

  late AnimationController _animationController;
  late Animation<Offset> _slideAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();

    _loadUsername();

    _animationController = AnimationController(
      duration: Duration(milliseconds: 700), // 700ms duration
      vsync: this,
    );

    _slideAnimation = Tween<Offset>(
      begin: Offset(-1.0, 0.0), // Start from the left
      end: Offset.zero, // End at the normal position
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));

    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));

    _animationController.forward(); // Start the animation
  }

  // Load the username from SharedPreferences and print it
  _loadUsername() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      username = prefs.getString('username') ?? "No username found";
    });
    print("Retrieved username: $username");
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  // Function to delete SharedPreferences keys and navigate to LoginPage
  Future<void> _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('userId');
    await prefs.remove('username');
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => LoginPage()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          "Profile",
          style: TextStyle(color: Colors.orange.shade900),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        iconTheme: IconThemeData(color: Colors.orange.shade900),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Profile Information
              SlideTransition(
                position: _slideAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: Container(
                    padding: const EdgeInsets.all(16.0),
                    decoration: BoxDecoration(
                      color: Colors.orange.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 30,
                          backgroundColor: Colors.orange.shade900,
                          child: const Icon(
                            Icons.person,
                            color: Colors.white,
                            size: 30,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '$username',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Colors.orange.shade900,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              phoneNumber,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.orange.shade900,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              // Orders Section
              SlideTransition(
                position: _slideAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => MyOrdersScreen()),
                      );
                    },
                    child: ListTile(
                      leading: Icon(Icons.shopping_bag, color: Colors.orange.shade900),
                      title: Text(
                        "Orders",
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      trailing: Icon(Icons.arrow_forward_ios, color: Colors.orange.shade900),
                    ),
                  ),
                ),
              ),
              const Divider(),
              // Loyalty Points Section
              SlideTransition(
                position: _slideAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => LoyaltyPage()),
                      );
                    },
                    child: ListTile(
                      leading: Icon(Icons.loyalty, color: Colors.orange.shade900),
                      title: Text(
                        "Loyalty Points",
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      trailing: Icon(Icons.arrow_forward_ios, color: Colors.orange.shade900),
                    ),
                  ),
                ),
              ),
              const Divider(),
              // Logout Section
              SlideTransition(
                position: _slideAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: GestureDetector(
                    onTap: () {
                      _showLogoutDialog(context);
                    },
                    child: ListTile(
                      leading: Icon(Icons.logout, color: Colors.orange.shade900),
                      title: Text(
                        "Logout",
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      trailing: Icon(Icons.arrow_forward_ios, color: Colors.orange.shade900),
                    ),
                  ),
                ),
              ),
              const Divider(),
            ],
          ),
        ),
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.orange.shade50,
          title: Text(
            "Logout",
            style: TextStyle(
              color: Colors.orange.shade900,
              fontWeight: FontWeight.bold,
            ),
          ),
          content: Text(
            "Are you sure you want to logout?",
            style: TextStyle(
              color: Colors.orange.shade900,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text(
                "Cancel",
                style: TextStyle(
                  color: Colors.grey.shade800,
                ),
              ),
            ),
            TextButton(
              onPressed: () async {
                Navigator.of(context).pop();
                await _logout();
              },
              child: Text(
                "Logout",
                style: TextStyle(
                  color: Colors.orange.shade900,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

void main() => runApp(MaterialApp(
  debugShowCheckedModeBanner: false,
  home: ProfileScreen(),
));
