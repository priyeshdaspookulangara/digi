# Prompt for Android Kotlin App Generation

## Context
I have a web application for an integrated MLM-based marketplace. The backend is PHP/SQLite with a RESTful API. I need a mobile app for Admin and Shop Owners to manage the marketplace.

## App Architecture
- **Language:** Kotlin
- **Platform:** Android
- **UI Framework:** Jetpack Compose (Modern Material 3)
- **Networking:** Retrofit with Moshi
- **Image Loading:** Coil
- **Local Storage:** Room (optional, for caching)

## Core Features to Implement
1. **Authentication:**
   - Login screen with Email/Password.
   - Session persistence (cookie-based or token-based as per API).
   - Role-based UI (Admin vs Shop Owner).

2. **Shop Management (Admin):**
   - Dashboard showing overview of shops.
   - "Add Shop" form: Name, Owner selection, Description, Locality (dropdown), Categories (multi-select chip), Tags (multi-select chip).
   - Upload Logo and Wallpaper art.

3. **Product Management (Shop Owner/Admin):**
   - List products for a selected shop.
   - "Add/Edit Product" form: Name, Price, Description, Featured Toggle.
   - Image Upload: Primary image and Gallery (multiple images).

4. **Search & Filter:**
   - Search shops/products by keywords.
   - Filter by Locality, Category, and Tags using a BottomSheet or Sidebar.

## Design Guidelines
- Modern, clean aesthetic (consistent with the web app's professional light theme).
- Use `Material 3` components (TopAppBar, FloatingActionButton, NavigationRail/NavigationBar).
- High-quality image placeholders.
- Error handling for network requests (Toasts/Snackbars).

## API Integration Reference
The app should interact with the following endpoints (Base URL: `https://dealmybiz.in/api/`):
- `auth.php`: Login/Status.
- `shops.php` & `shop_settings.php`: CRUD for shops.
- `products.php`: CRUD for products.
- `upload.php`: Multipart upload for images.
- `taxonomy.php` & `localities.php`: Fetching metadata.
- `search.php`: Querying data.

## Instruction
Please generate the initial project structure, data models, API interface, and the main ViewModels/Screens for Login and Shop Management.
