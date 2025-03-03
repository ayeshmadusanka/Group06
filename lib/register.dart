import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'login.dart';
import 'verificaition.dart';
import 'config.dart';

class SignUpPage extends StatefulWidget {
  @override
  _SignUpPageState createState() => _SignUpPageState();
}

class _SignUpPageState extends State<SignUpPage> with TickerProviderStateMixin {
  bool _isPasswordVisible = false;
  late AnimationController _controller;
  late Animation<Offset> _offsetAnimation;
  late Animation<double> _fadeAnimation;
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();


  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    );

    _offsetAnimation = Tween<Offset>(
      begin: const Offset(0, 1),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut));

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );

    // Start the animation after a small delay
    Future.delayed(Duration(milliseconds: 100), () {
      _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _usernameController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    // Direct validation of the fields before proceeding
    if (_usernameController.text.isEmpty ||
        _phoneController.text.isEmpty ||
        _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('All fields are required.')),
      );
      return;
    }

    // Prepare the request body as JSON
    final body = jsonEncode({
      'username': _usernameController.text,
      'phone_number': _phoneController.text,
      'password': _passwordController.text,
    });

    try {
      // Send POST request to the API endpoint
      final response = await http.post(
        Uri.parse('$baseUrl/register.php'),  // Use the base URL for the API
        headers: {'Content-Type': 'application/json'},
        body: body,
      );

      // Decode the response
      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        print('Response body: ${response.body}');

        if (responseData['success'] == true) {  // Check success key instead of status
          // Registration successful
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(responseData['message'])),
          );

          // Pass user_id to VerificationPage
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (context) => VerificationPage(
                userId: responseData['user_id'],  // Pass user_id
              ),
            ),
          );
        } else {
          // Registration failed
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(responseData['message'])),
          );
        }
      } else {
        // Handle server errors or unsuccessful status code
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to register. Please try again later.')),
        );
      }
    } catch (e) {
      // Catch errors related to network or API issues
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('An error occurred. Please check your connection.')),
      );
    }
  }

  // Show error dialog
  void _showError(String message) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text('Error'),
          content: Text(message),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(context);
              },
              child: Text('OK'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: true, // Adjusts for keyboard
      body: SingleChildScrollView( // Wrap the body with SingleChildScrollView
        child: Container(
          height: MediaQuery.of(context).size.height, // Ensure the height takes full screen size
          decoration: const BoxDecoration(
            image: DecorationImage(
              image: AssetImage("assets/login.png"), // Replace with your image path
              fit: BoxFit.cover,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: 280), // Move header down by 290 pixels
              SlideTransition(
                position: _offsetAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: Text(
                    "Register",
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Colors.orange.shade900,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              SlideTransition(
                position: _offsetAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: Text(
                    "Create New Account",
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.orange.shade900,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 40), // Same spacing as before
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 40.0),
                child: Column(
                  children: [
                    SlideTransition(
                      position: _offsetAnimation,
                      child: FadeTransition(
                        opacity: _fadeAnimation,
                        child: TextField(
                          controller: _usernameController,
                          decoration: InputDecoration(
                            hintText: "Username",
                            prefixIcon: Icon(Icons.person, color: Colors.orange.shade900),
                            filled: true,
                            fillColor: Colors.orange[100],
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: BorderSide.none,
                            ),
                            contentPadding: const EdgeInsets.symmetric(vertical: 15.0, horizontal: 20.0),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    SlideTransition(
                      position: _offsetAnimation,
                      child: FadeTransition(
                        opacity: _fadeAnimation,
                        child: TextField(
                          controller: _phoneController,
                          decoration: InputDecoration(
                            hintText: "Phone Number",
                            prefixIcon: Icon(Icons.phone, color: Colors.orange.shade900),
                            filled: true,
                            fillColor: Colors.orange[100],
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: BorderSide.none,
                            ),
                            contentPadding: const EdgeInsets.symmetric(vertical: 15.0, horizontal: 20.0),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    SlideTransition(
                      position: _offsetAnimation,
                      child: FadeTransition(
                        opacity: _fadeAnimation,
                        child: TextField(
                          obscureText: !_isPasswordVisible,
                          controller: _passwordController,
                          decoration: InputDecoration(
                            hintText: "Password",
                            prefixIcon: Icon(Icons.lock, color: Colors.orange.shade900),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _isPasswordVisible
                                    ? Icons.visibility
                                    : Icons.visibility_off,
                                color: Colors.orange.shade900,
                              ),
                              onPressed: () {
                                setState(() {
                                  _isPasswordVisible = !_isPasswordVisible;
                                });
                              },
                            ),
                            filled: true,
                            fillColor: Colors.orange[100],
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: BorderSide.none,
                            ),
                            contentPadding: const EdgeInsets.symmetric(vertical: 15.0, horizontal: 20.0),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 30),
                    SlideTransition(
                      position: _offsetAnimation,
                      child: FadeTransition(
                        opacity: _fadeAnimation,
                        child: ElevatedButton(
                          onPressed: _register, // Trigger register function on press
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.orange.shade900,
                            minimumSize: const Size.fromHeight(50),
                            padding: const EdgeInsets.symmetric(horizontal: 20),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(30),
                            ),
                            textStyle: const TextStyle(color: Colors.white),
                          ),
                          child: const Text(
                            "Sign Up",
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
              SlideTransition(
                position: _offsetAnimation,
                child: FadeTransition(
                  opacity: _fadeAnimation,
                  child: TextButton(
                    onPressed: () {
                      // Navigate to the Login screen
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => LoginPage()),
                      );
                    },
                    child: Text(
                      "Already have an account? Log In",
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.orange.shade900,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ),
              const Spacer(),
              Padding(
                padding: const EdgeInsets.only(bottom: 20.0),
                child: SlideTransition(
                  position: _offsetAnimation,
                  child: FadeTransition(
                    opacity: _fadeAnimation,
                    child: Text(
                      "www.breadandbutterhospitality.com",
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.orange.shade900,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
