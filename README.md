# 🛒 E-Commerce REST API

A production-ready RESTful API for an e-commerce platform, built with **Laravel** and **PHP**. Demonstrates clean architecture, SOLID principles, proper authentication, database design, and test coverage — the kind of backend you'd build for a real-world product.

---

## ✨ Features

- **JWT-based authentication** via Laravel Sanctum (register, login, logout)
- **Product catalogue** with search, category filtering, price range filters, and sorting
- **Category management** with product count aggregation
- **Shopping cart** — add, update, remove items with real-time stock validation
- **Order management** — checkout from cart, stock decrement, order cancellation with stock restore
- **Role-based access control** (admin vs customer routes)
- **Soft deletes** on products to preserve order history integrity
- **Database transactions** for atomic cart-to-order conversion
- **Pagination** on all list endpoints
- **PHPUnit feature tests** covering core business flows

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 10 |
| Auth | Laravel Sanctum (API tokens) |
| Database | MySQL 8 |
| Testing | PHPUnit |
| Code Style | PSR-12 |

---

## 🗄️ Database Schema

```
users
  └── carts ──── cart_items ──── products ──── categories
  └── orders ─── order_items ───────────────────┘
```

Key design decisions:
- `order_items` stores `unit_price` at time of purchase (price history preserved)
- `orders.shipping_address` is stored as JSON (flexible, no join required)
- Products use `soft_deletes` so order history is never broken
- `cart_items` has a composite unique key on `(cart_id, product_id)`

---

## 📡 API Endpoints

### Auth
```
POST   /api/v1/register
POST   /api/v1/login
POST   /api/v1/logout        [auth]
GET    /api/v1/me             [auth]
```

### Products
```
GET    /api/v1/products                     Public — supports ?search, ?category, ?min_price, ?max_price, ?sort, ?dir
GET    /api/v1/products/{id}                Public
POST   /api/v1/products                     [auth, admin]
PUT    /api/v1/products/{id}                [auth, admin]
DELETE /api/v1/products/{id}                [auth, admin]
```

### Categories
```
GET    /api/v1/categories
GET    /api/v1/categories/{id}
POST   /api/v1/categories                   [auth, admin]
PUT    /api/v1/categories/{id}              [auth, admin]
DELETE /api/v1/categories/{id}              [auth, admin]
```

### Cart
```
GET    /api/v1/cart                         [auth]
POST   /api/v1/cart/items                   [auth]
PUT    /api/v1/cart/items/{id}              [auth]
DELETE /api/v1/cart/items/{id}              [auth]
DELETE /api/v1/cart                         [auth]
```

### Orders
```
GET    /api/v1/orders                       [auth]
POST   /api/v1/orders                       [auth]
GET    /api/v1/orders/{id}                  [auth]
POST   /api/v1/orders/{id}/cancel           [auth]
```

---

## 🚀 Getting Started

```bash
# Clone the repo
git clone https://github.com/ahmedzarrar42/ecommerce-api.git
cd ecommerce-api

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then:
php artisan migrate --seed

# Run the server
php artisan serve
```

### Default seed credentials
| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| User | test@example.com | password |

---

## 🧪 Running Tests

```bash
php artisan test
```

Tests cover:
- Product listing, filtering, and creation
- Cart add / update / remove / clear
- Full order checkout flow
- Stock decrement on order, stock restore on cancel
- Auth protection on all secured endpoints
- Validation error responses

---

## 📐 Architecture Notes

- **Thin controllers** — business logic lives in models and services, not in HTTP layer
- **Eloquent scopes** (`active()`, `inStock()`) keep queries readable and reusable
- **DB::transaction()** used for checkout to guarantee atomicity
- **Form Request validation** pattern for clean input handling
- **Resource responses** return consistent JSON structures across all endpoints

---

## 🔜 Roadmap

- [ ] Payment gateway integration (Stripe)
- [ ] Product image upload (S3)
- [ ] Redis caching for product catalogue
- [ ] Order status webhook events
- [ ] API rate limiting per user

---

## 📄 License

MIT — feel free to use this as a reference or starting point.
