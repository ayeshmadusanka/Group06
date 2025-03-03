import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'config.dart'; // Ensure baseUrl and imageUrl are defined here
import 'profile.dart';
import 'menu.dart';
import 'offers.dart';
import 'cart.dart';
import 'package:animate_do/animate_do.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<Offset> _textSlideAnimation;
  late Animation<Offset> _imageSlideAnimation;
  late Animation<double> _fadeAnimation;

  final PageController _pageController = PageController(); // For special offers PageView
  int _currentPageIndex = 0; // Track the current page index for offers
  int _currentIndex = 0; // For bottom navigation

  // Variables for recommendations
  List<dynamic> _recommendedItems = [];
  bool _isLoadingRecommendations = true;

  // Variables for weekly highlight
  Map<String, dynamic>? weeklyHighlight;
  bool _isLoadingHighlight = true;

  // Username retrieved from shared preferences
  String _username = "";

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1000),
      vsync: this,
    )..forward();

    // Text slides in from right
    _textSlideAnimation = Tween<Offset>(
      begin: const Offset(1, 0),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOut));

    // Images slide in from left
    _imageSlideAnimation = Tween<Offset>(
      begin: const Offset(-1, 0),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOut));

    _fadeAnimation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOut),
    );

    // Fetch recommendations, highlight and username when screen initializes
    _fetchRecommendations();
    _fetchHighlight();
    _getUsername();
  }

  Future<void> _getUsername() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    String? retrievedUsername = prefs.getString('username');
    setState(() {
      _username = retrievedUsername ?? "";
    });
    // Print the retrieved username to the console
    print("Retrieved username: $_username");
  }

  Future<void> _fetchRecommendations() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId');
    if (userId == null) {
      print('User ID is not available');
      setState(() {
        _isLoadingRecommendations = false;
      });
      return;
    }

    try {
      // Call the recommendations API
      final recUrl = 'https://model.ayeshmadusanka.site/recommendations/$userId';
      final recResponse = await http.get(Uri.parse(recUrl));
      print('Recommendations Response: ${recResponse.body}');

      if (recResponse.statusCode == 200) {
        final recData = json.decode(recResponse.body);
        List<dynamic> recommendations = recData['recommendations'];
        if (recommendations.isNotEmpty) {
          // Build comma-separated string of IDs
          final ids = recommendations.join(',');
          // Call the item details API with the recommended IDs
          final detailsUrl = '$baseUrl/get_item_details.php?ids=$ids';
          final detailsResponse = await http.get(Uri.parse(detailsUrl));
          print('Item Details Response: ${detailsResponse.body}');

          if (detailsResponse.statusCode == 200) {
            List<dynamic> items = json.decode(detailsResponse.body);
            // Update the image path by removing "../" and prepending imageUrl
            items = items.map((item) {
              String imagePath = item['image_path'];
              imagePath = imagePath.replaceAll('../', '');
              item['image_path'] = '$imageUrl/$imagePath';
              return item;
            }).toList();
            setState(() {
              _recommendedItems = items;
              _isLoadingRecommendations = false;
            });
          } else {
            print('Failed to load item details');
            setState(() {
              _isLoadingRecommendations = false;
            });
          }
        } else {
          print('No recommendations available');
          setState(() {
            _isLoadingRecommendations = false;
          });
        }
      } else {
        print('Failed to load recommendations');
        setState(() {
          _isLoadingRecommendations = false;
        });
      }
    } catch (e) {
      print('Error fetching recommendations: $e');
      setState(() {
        _isLoadingRecommendations = false;
      });
    }
  }

  Future<void> _fetchHighlight() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId');
    if (userId == null) {
      print('User ID is not available');
      setState(() {
        _isLoadingHighlight = false;
      });
      return;
    }

    try {
      // Call the weekly highlight API
      final highlightUrl = '$baseUrl/get_weekly_highlight.php?user_id=$userId';
      final highlightResponse = await http.get(Uri.parse(highlightUrl));
      print('Weekly Highlight Response: ${highlightResponse.body}');

      if (highlightResponse.statusCode == 200) {
        final data = json.decode(highlightResponse.body);
        if (data['success'] == true && data['highlight'] != null) {
          Map<String, dynamic> highlight = data['highlight'];
          // Update image path by removing "../" and appending base image URL
          String imagePath = highlight['image_path'] ?? '';
          imagePath = imagePath.replaceAll('../', '');
          highlight['image_path'] = '$imageUrl/$imagePath';
          setState(() {
            weeklyHighlight = highlight;
            _isLoadingHighlight = false;
          });
        } else {
          print('No highlight available');
          setState(() {
            _isLoadingHighlight = false;
          });
        }
      } else {
        print('Failed to load weekly highlight');
        setState(() {
          _isLoadingHighlight = false;
        });
      }
    } catch (e) {
      print('Error fetching highlight: $e');
      setState(() {
        _isLoadingHighlight = false;
      });
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    _pageController.dispose(); // Dispose PageController
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 40),
              // Greeting Row with profile icon on the right wrapped in FadeInUp animation
              FadeInDown(
                duration: const Duration(milliseconds: 800),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Greeting texts in a Column to display in two lines
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            "Hi! ${_username}",
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.deepOrange,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            "Welcome Back",
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.normal,
                              color: Colors.grey,
                            ),
                          ),
                        ],
                      ),
                      // Profile icon button
                      Container(
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: Colors.orange.shade900,
                            width: 2.0,
                          ),
                        ),
                        child: IconButton(
                          icon: Icon(
                            Icons.person_rounded,
                            size: 28,
                            color: Colors.orange.shade900,
                          ),
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (context) => ProfileScreen()),
                            );
                          },
                        ),
                      )
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 4),
              _buildAnimatedImage(_buildSpecialOffers()),
              const SizedBox(height: 12),
              _buildAnimatedText(_buildCarouselIndicators()),
              const SizedBox(height: 16),
              _buildAnimatedText(_buildSectionTitle('    Recommendations')),
              const SizedBox(height: 12),
              _buildAnimatedImage(_buildRecommendationsWidget()),
              const SizedBox(height: 16),
              _buildAnimatedText(_buildSectionTitle("   Popular This Week")),
              _buildAnimatedImage(_buildHighlightContainer()),
            ],
          ),
        ),
      ),
      // Wrap the bottom navigation bar with FadeInUp animation
      bottomNavigationBar: FadeInUp(
        duration: const Duration(milliseconds: 800),
        child: _buildBottomNavigationBar(),
      ),
    );
  }



  // Text animation: slide in from right
  Widget _buildAnimatedText(Widget child) {
    return SlideTransition(
      position: _textSlideAnimation,
      child: FadeTransition(
        opacity: _fadeAnimation,
        child: child,
      ),
    );
  }

  // Image animation: slide in from left
  Widget _buildAnimatedImage(Widget child) {
    return SlideTransition(
      position: _imageSlideAnimation,
      child: FadeTransition(
        opacity: _fadeAnimation,
        child: child,
      ),
    );
  }



  Widget _buildSpecialOffers() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              "  Today's Special Offers",
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            IconButton(
              icon: const Icon(Icons.more_horiz, color: Colors.white),
              onPressed: () {},
            ),
          ],
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 120,
          child: PageView(
            controller: _pageController,
            onPageChanged: (index) {
              setState(() {
                _currentPageIndex = index;
              });
            },
            children: [
              _buildSpecialOfferImage('assets/offer2.jpg'),
              _buildSpecialOfferImage('assets/offer1.jpg'),
              _buildSpecialOfferImage('assets/offer3.jpg'),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSpecialOfferImage(String imagePath) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        image: DecorationImage(
          image: AssetImage(imagePath),
          fit: BoxFit.cover,
        ),
      ),
    );
  }

  Widget _buildCarouselIndicators() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(3, (index) {
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Icon(
            Icons.circle,
            size: 8,
            color: index == _currentPageIndex
                ? Colors.orange[800]
                : Colors.orange[300],
          ),
        );
      }),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
      ],
    );
  }

  // Recommendations widget with horizontal scroll.
  Widget _buildRecommendationsWidget() {
    if (_isLoadingRecommendations) {
      return const Center(child: CircularProgressIndicator());
    } else if (_recommendedItems.isEmpty) {
      return const Center(child: Text('No recommendations available'));
    } else {
      return SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: _recommendedItems.map((item) {
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Display the image using the updated image path
                  Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      image: DecorationImage(
                        image: NetworkImage(item['image_path']),
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  // Display the item name
                  Container(
                    width: 100,
                    child: Text(
                      item['name'],
                      textAlign: TextAlign.center,
                      softWrap: true,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
      );
    }
  }

  // Highlight widget to display the weekly highlight.
  Widget _buildHighlightContainer() {
    if (_isLoadingHighlight) {
      return const Center(child: CircularProgressIndicator());
    } else if (weeklyHighlight == null) {
      return const Center(child: Text('No highlight available'));
    } else {
      return Padding(
        padding: const EdgeInsets.all(12.0),
        child: Container(
          height: 180,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            image: DecorationImage(
              image: NetworkImage(weeklyHighlight!['image_path']),
              fit: BoxFit.cover,
            ),
          ),
          alignment: Alignment.bottomLeft,
          padding: const EdgeInsets.all(8),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.deepOrange.withOpacity(0.8), // See-through deep orange
              borderRadius: BorderRadius.circular(10), // Circular border
            ),
            child: Text(
              weeklyHighlight!['item_name'] ?? '',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
          )


        ),
      );
    }
  }

  Widget _buildBottomNavigationBar() {
    return BottomNavigationBar(
      type: BottomNavigationBarType.fixed,
      backgroundColor: Colors.orange[900],
      selectedItemColor: Colors.white,
      unselectedItemColor: Colors.white60,
      currentIndex: _currentIndex,
      onTap: (index) {
        setState(() {
          _currentIndex = index;
        });
        // Updated navigation: profile is now accessible via the top-right icon
        if (index == 2) {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => MenuPage()),
          );
        } else if (index == 1) {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => OffersPage()),
          );
        } else if (index == 3) {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => CartPage()),
          );
        }
      },
      // Removed profile item from bottom navigation.
      items: const [
        BottomNavigationBarItem(
          icon: Icon(Icons.home),
          label: 'Home',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.local_offer),
          label: 'Offers',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.menu),
          label: 'Menu',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.shopping_cart),
          label: 'Cart',
        ),
      ],
    );
  }
}
