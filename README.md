# 📚 UniHub API - Complete Documentation

> Laravel REST API for University Management System

**Last Updated:** 2026-08-18 07:06:32

---

## 📖 Table of Contents

1. [Quick Start](#quick-start)
2. [API Documentation](#api-documentation)
3. [Controllers Reference](#controllers-reference)
4. [Models & Database](#models--database)
5. [Authentication](#authentication)
6. [Common Errors](#common-errors)

---

## 🚀 Quick Start

### Installation

```bash
# Clone repository
git clone [repository-url]
cd unihub-API

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=unihub20
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate --seed

# Start development server
php artisan serve
```

### Base URL

```
http://192.168.8.105/unihub-api/api/v1
```

---

## 📡 API Documentation

**Complete API Reference:** [docs/API_ROUTES_DETAILED.md](docs/API_ROUTES_DETAILED.md)

This file contains:
- ✅ All API endpoints
- ✅ HTTP methods (GET, POST, PUT, DELETE)
- ✅ Request body examples (JSON)
- ✅ Success response examples
- ✅ Error response examples
- ✅ Required middleware
- ✅ Authentication requirements

### Quick Example: Login

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

---

## 🎮 Controllers Reference

**Complete Controllers Guide:** [docs/CONTROLLERS_DETAILED.md](docs/CONTROLLERS_DETAILED.md)

This file contains:
- ✅ All controller classes
- ✅ Every public method with description
- ✅ Method parameters and return types
- ✅ Which routes use each method
- ✅ Purpose of each function

---

## 📦 Models & Database

**Complete Models Reference:** [docs/MODELS_DETAILED.md](docs/MODELS_DETAILED.md)

This file contains:
- ✅ All Eloquent models
- ✅ Database table mappings
- ✅ Fillable and hidden fields
- ✅ Type casts
- ✅ Relationships (hasMany, belongsTo, etc.)
- ✅ Query scopes
- ✅ Usage examples

---

## 🔐 Authentication

This API uses **Laravel Sanctum** for authentication.

### Getting Access Token

```bash
POST /api/v1/auth/login
```

### Using Token

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## ⚠️ Common Errors

| Status Code | Meaning |
|-------------|----------|
| 200 | Success |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (no permission) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 📊 Project Statistics

- **Total Routes:** 316
- **API Routes:** 297
- **Controllers:** 59
- **Models:** 62

---

*📝 Documentation auto-generated with full details*
