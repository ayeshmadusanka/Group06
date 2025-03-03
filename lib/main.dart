import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'login.dart';
import 'home.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Bread and Butter',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.orange,
        scaffoldBackgroundColor: Colors.white,
        textSelectionTheme: TextSelectionThemeData(
          cursorColor: Colors.orange.shade900,
          selectionColor: Colors.orange.shade200,
          selectionHandleColor: Colors.orange.shade900,
        ),
        progressIndicatorTheme: ProgressIndicatorThemeData(
          color: Colors.deepOrange, // Set CircularProgressIndicator color
        ),
      ),

      home: const SplashScreen(), // Start with splash screen
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({Key? key}) : super(key: key);

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  // Check if the user is already logged in
  Future<bool> _isLoggedIn() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    // Assume that if 'userId' exists, the user is logged in.
    return prefs.getString('userId') != null;
  }

  void _navigate() async {
    await Future.delayed(const Duration(seconds: 2)); // Delay for splash
    bool loggedIn = await _isLoggedIn();

    if (!mounted) return;

    // Navigate to HomeScreen if logged in; otherwise go to LoginPage.
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (context) => loggedIn ? HomeScreen() : LoginPage(),
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    _navigate();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('assets/splash.png'),
            fit: BoxFit.cover,
          ),
        ),
      ),
    );
  }
}
