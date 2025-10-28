# Nintendo Music App Viewer

# I no longer mantain this.

A web-based application for browsing and listening to music from Nintendo's game catalog. This project interfaces with Nintendo's public APIs to fetch data on games, news, playlists, and tracks, presenting it in a clean, user-friendly interface.

## 🌟 Features

- **Browse Recent Games:** The homepage displays a list of the latest games from Nintendo's catalog.
- **View News & Notices:** Stay updated with the latest news and service notices directly from Nintendo.
- **Explore Playlists:** Discover music playlists associated with specific games.
- **Listen to Tracks:** An integrated audio player allows for in-browser playback of game music.
- **Responsive Design:** The interface is designed to be usable on both desktop and mobile devices.

## 🚀 How It Works

This application uses a simple yet effective architecture:

-   **PHP Backend:** A PHP backend acts as a proxy between the client and Nintendo's APIs. This is crucial for two reasons:
    1.  It securely handles API requests without exposing endpoints to the client.
    2.  It injects the required `User-Agent` header to mimic an official Nintendo application, which is necessary to access the API.
-   **Vanilla JS Frontend:** The frontend is built with plain JavaScript, using the Fetch API to dynamically load content without page reloads. This keeps the application lightweight and fast.
-   **Custom Router:** A `router.php` file handles all URL routing, parsing the URL to determine what content to serve, from game lists to the audio player itself.

## 🔧 Setup

To run this project locally, you will need a PHP server environment (like XAMPP, MAMP, or a built-in PHP server).

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Gh0styTongue/Nintendo-Music-App-Viewer.git
    ```

2.  **Navigate to the project directory:**
    ```bash
    cd Nintendo-Music-App-Viewer
    ```

3.  **Start the PHP built-in web server:**
    ```bash
    php -S localhost:8000
    ```
    This will start a server. You can now access the application by navigating to `http://localhost:8000/` in your web browser.

## 📁 File Structure

-   `index.php`: The main entry point of the application. It fetches and displays the initial list of games and serves as the primary HTML structure.
-   `router.php`: The core of the application's logic. It handles all routing, processes API requests to Nintendo, and renders the appropriate pages.
-   `404.php`: Custom "Page Not Found" error page.
-   `500.php`: Custom "Internal Server Error" page.

## ⚠️ Known Challenges: DRM-Protected Content

Some audio tracks provided by the API are protected with **Widevine DRM**. The current implementation can detect these tracks but cannot play them. When a DRM-protected track is encountered, the player will display a message indicating this limitation.

Contributions or suggestions on how to handle DRM-protected audio playback are welcome.

## Disclaimer

This is an unofficial, third-party application and is not affiliated with, authorized, endorsed by, or in any way officially connected with Nintendo. All product and company names are trademarks™ or registered® trademarks of their respective holders.
