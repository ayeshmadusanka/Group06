# ai_project

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

## Setting up in Android Studio

Follow these steps to set up and run the project in Android Studio:

1.  **Clone the Repository:**
    -   Open your terminal or Git Bash.
    -   Navigate to the directory where you want to clone the project.
    -   Run the following command, replacing `<repository_url>` with the actual URL of your repository:
        ```bash
        git clone <repository_url>
        ```

2.  **Open the Project in Android Studio:**
    -   Open Android Studio.
    -   Click on "Open" or "Open an existing Android Studio project".
    -   Navigate to the cloned project directory and select the `ai_project` folder.
    -   Click "OK".

3.  **Flutter Clean:**
    -   Open the terminal in Android Studio (View -> Tool Windows -> Terminal).
    -   Run the following command to clean the project:
        ```bash
        flutter clean
        ```

4.  **Flutter Pub Get:**
    -   In the same terminal, run the following command to fetch the project dependencies:
        ```bash
        flutter pub get
        ```

5.  **Build the App:**
    -   Ensure you have an Android emulator running or an Android device connected.
    -   Click on "Run" (the green play button) in Android Studio, or execute the following command in the terminal:
        ```bash
        flutter run
        ```

6.  **Run the App:**
    -   The app should now build and launch on your emulator or device.

## Setting up the Backend (WAMP or XAMPP) with "Backend" Folder

If this project utilizes a backend, you'll need to set up a local server environment. WAMP (Windows Apache, MySQL, PHP) or XAMPP (Cross-Platform Apache, MySQL, PHP, Perl) are recommended.

1.  **Install WAMP or XAMPP:**
    -   Download and install either WAMP or XAMPP from their respective official websites.
    -   Follow the installation instructions.

2.  **Start the Servers:**
    -   After installation, start the Apache and MySQL services from the WAMP or XAMPP control panel.

3.  **Place Backend Folder:**
    -   Place the "Backend" folder (containing your PHP scripts and other backend files) in the `www` (for WAMP) or `htdocs` (for XAMPP) directory. This directory is the root directory for your local web server.

4.  **Database Setup:**
    -   If your backend uses a database, create the necessary database and tables using phpMyAdmin, which is included with WAMP and XAMPP.
    -   Configure the database connection details in your backend files, which are located inside the "Backend" folder.

5.  **Access the Backend:**
    -   You can access your backend through your web browser by navigating to `http://localhost/Backend/<your_backend_file.php>`.
    -   Ensure your Flutter app's backend URLs are correctly configured to point to your local server (e.g., `http://10.0.2.2/Backend` for Android emulators, or `http://<your_local_ip>/Backend` for physical devices on the same network).
