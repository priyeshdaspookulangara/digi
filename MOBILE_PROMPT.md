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
   - Login screen supporting Email, Mobile, or Customer ID as the identifier.
   - Session persistence.
   - Role-based UI (Admin vs Shop Owner).

2. **Admin Features:**
   - **Shop Registration:** Integrated form to create a new User (Shop Owner) and their Shop in one go. Fields: Owner Username, Email, Password, Mobile, Customer ID, and Shop Name, Description, Locality, Address, City, District, Pincode, Type (`free_listing`, `paid`, `privilege`).
   - **Taxonomy Management:** CRUD screens for Categories (hierarchical) and Tags.
   - **User Management:** List and view all users, filtered by role.

3. **Product Management (Shop Owner/Admin):**
   - List products for a selected shop.
   - "Add/Edit Product" form: Name, Price, Description, Featured Toggle.
   - Image Upload: Primary image and Gallery (multiple images).

4. **Search & Filter:**
   - Search shops/products by keywords.
   - Filter by Locality, Category, and Tags using a BottomSheet or Sidebar.

## Business Rules to Enforce
- **Free Listing Restrictions:**
  - Max 2 categories per shop.
  - No Tags allowed.
  - No SEO fields (Keywords, Social Links).
  - No Gallery/Wallpaper uploads (Disable these UI components if shop type is `free_listing`).
- **Prioritization:** Search results should reflect the priority order returned by the API (Paid shops first).

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

## New Feature: Mobile Bulk Update Offline/Local Shops (Agent & Admin Only)
We have added a RESTful JSON endpoint in the web backend: `POST /api/bulk_shops.php` (requires role `agent` or `admin`). This endpoint allows agents/admins to upsert shops in bulk. If a shop with the same `shop_name` and `owner_username` already exists, it updates the existing shop; otherwise, it registers a new merchant user account (including MLM hierarchy nodes) and inserts the new shop cleanly.

### Task for Mobile AI Agent:
Please implement a "Bulk Update" side menu/feature in the Android Kotlin App:
1. **Local Offline Shop Storage:**
   - Create a local SQLite database table using Room called `local_draft_shops`.
   - Fields should capture: `shop_name`, `owner_username`, `description`, `locality`, `address`, `city`, `district`, `pincode`, `type` (`free_listing`, `classic`, `privilege`), `owner_email` (optional), `owner_password` (optional), `owner_mobile` (optional), and `category`.
   - Provide an offline screen to enter/add multiple shops locally as draft rows.

2. **Bulk Upload Service (Sync):**
   - Implement a side menu option called **"Bulk Update"** in the navigation drawer.
   - When clicked, this screen lists all local draft shops that haven't been synchronized yet.
   - Provide a **"Sync with Server"** button.
   - When clicked, serializes the draft shops into a JSON array matching the payload format expected by `api/bulk_shops.php`:
     ```json
     [
       {
         "shop_name": "API Bulk Tech Shop",
         "owner_username": "apimerchant1",
         "description": "Tech description",
         "locality": "Chalakudy",
         "address": "123 Main St",
         "city": "Chalakudy",
         "district": "Thrissur",
         "pincode": "680307",
         "type": "privilege",
         "owner_email": "apimerchant1@example.com",
         "owner_password": "secretPass123!",
         "owner_mobile": "9999999991",
         "category": "Electronics"
       }
     ]
     ```
   - Sends a **POST** request to `/api/bulk_shops.php` using Retrofit.
   - On success:
     - Read the response indicating `created_count` and `updated_count`.
     - Display a Toast or Success Dialog: `"Bulk Sync successful! Created: X, Updated: Y"`.
     - Clear the successfully synced shops from the local draft database.
   - On failure:
     - Show appropriate network/error feedback without clearing the local drafts, so the user can retry later.

## Instruction
Please generate the initial project structure, data models, API interface, and the main ViewModels/Screens for Login and Shop Management.
