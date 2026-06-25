# NexGen Marketplace API Documentation

This documentation covers the RESTful API endpoints for the NexGen Marketplace, designed for mobile app integration (Android/Kotlin).

## Authentication

All protected endpoints require a session. Use the `auth.php` endpoint to log in.

### Login
**POST** `/api/auth.php`
- **Request:** `{"email": "...", "password": "..."}`
- **Response:** `{"message": "Login successful", "user": {"id": 1, "username": "admin", "role": "admin", "shop_id": 1}, "session_id": "..."}`

### Check Status
**GET** `/api/auth.php`
- **Response:** `{"logged_in": true, "user": {...}}`

---

## Shop Management (Admin & Owners)

### List/Get Shops
**GET** `/api/shops.php`
- **Params:** `id` (optional, for specific shop)
- **Response:** Array of shops or a single shop object including `categories` and `tags` (IDs).

### Create Shop (Admin Only)
**POST** `/api/shops.php`
- **Request:** `{"name": "...", "owner_id": 1, "description": "...", "locality": "...", "categories": [1,2], "tags": [3,4]}`

### Update Shop Settings
**POST** `/api/shop_settings.php`
- **Params:** `shop_id` (Required for Admin to act on a shop)
- **Request:** `{"name": "...", "description": "...", "locality": "...", "categories": [...], "tags": [...]}`

---

## Product Management

### List Products
**GET** `/api/products.php`
- **Params:** `shop_id` (Required to filter by shop)
- **Response:** Array of products.

### Get Product Details
**GET** `/api/products.php?id=123&shop_id=1`
- **Response:** Product object including `gallery` (array of image URLs).

### Add/Update Product
**POST** `/api/products.php`
- **Params:** `shop_id` (Required)
- **Request:** `{"id": 1, "name": "...", "price": 999, "description": "...", "is_featured": 1}` (Include `id` for update)

---

## Media Uploads

### Upload Image
**POST** `/api/upload.php`
- **Body:** `multipart/form-data`
- **Fields:**
  - `image`: File
  - `type`: `product`, `product_gallery`, `logo`, or `wallpaper`
  - `id`: `product_id` (for product/gallery) or `shop_id` (for logo/wallpaper)
- **Response:** `{"message": "Upload successful", "url": "uploads/..."}`

---

## Taxonomy & Search

### Get Categories/Tags
**GET** `/api/taxonomy.php`
- **Params:** `type` (`categories`, `tags`, or `all`)

### Search
**GET** `/api/search.php`
- **Params:**
  - `q`: Search query
  - `locality`: Area name
  - `cat_id`: Category ID
  - `tag_id`: Tag ID
  - `type`: `shops` or `products`
- **Response:** Array of matches.

### Get Localities
**GET** `/api/localities.php`
- **Response:** Array of locality objects.
