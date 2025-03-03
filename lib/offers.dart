import 'package:flutter/material.dart';

class OffersPage extends StatefulWidget {
  const OffersPage({Key? key}) : super(key: key);

  @override
  _OffersPageState createState() => _OffersPageState();
}

class _OffersPageState extends State<OffersPage> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<Offset> _slideAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    // Initialize the animation controller
    _controller = AnimationController(
      duration: const Duration(milliseconds: 800), // Duration of the animation
      vsync: this,
    );

    // Slide animation for sliding up
    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 1), // Start position below the screen
      end: Offset.zero, // End position at the normal position
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOut));

    // Fade animation for fading in
    _fadeAnimation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOut),
    );

    // Start the animation when the page is loaded
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white, // Set AppBar background color to white
        iconTheme: IconThemeData(color: Colors.orange.shade900), // Icon color
        title: Text(
          "Offers",
          style: TextStyle(color: Colors.orange.shade900),
          // Title text color
        ),
        centerTitle: true,
      ),
      body: Padding(
        padding: const EdgeInsets.all(8.0),
        child: FadeTransition(
          opacity: _fadeAnimation, // Apply fade animation
          child: SlideTransition(
            position: _slideAnimation, // Apply slide animation
            child: GridView.builder(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 1, // One column in each row
                childAspectRatio: 2.82, // Match the aspect ratio to 1638:582 (about 2.82)
                crossAxisSpacing: 10, // Spacing between cards horizontally
                mainAxisSpacing: 10, // Spacing between cards vertically
              ),
              itemCount: 5, // Number of offers (5 images)
              itemBuilder: (context, index) {
                // List of images
                final images = [
                  'assets/offer2.jpg',
                  'assets/offer3.jpg',
                  'assets/offer4.jpg',
                  'assets/offer5.png',
                  'assets/offer6.jpg'
                ];

                return Card(
                  elevation: 0, // No shadow
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.asset(
                      images[index], // Use the corresponding image
                      fit: BoxFit.cover, // Make sure image covers the card
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
