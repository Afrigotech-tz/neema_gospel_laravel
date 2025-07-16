# Neema Gospel Backend API Documentation

This document provides comprehensive information about the available API endpoints for the Neema Gospel Backend application.

## Base URL
```
http://localhost:8000/api
```

## Authentication
The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header for protected routes.

```
Authorization: Bearer {your-token-here}
```

## Response Format
All API responses follow this standard format:

```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data
    "errors": {} // Validation errors (if any)
}
```

---

## Authentication Endpoints

### Register User
**POST** `/register`

Register a new user account.

**Request Body:**
```json
{
    "first_name": "John",
    "surname": "Doe",
    "gender": "male", // optional: "male" or "female"
    "phone_number": "+255712345678",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "country_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "surname": "Doe",
            "full_name": "John Doe",
            "gender": "male",
            "phone_number": "+255712345678",
            "email": "john.doe@example.com",
            "country": {
                "id": 1,
                "name": "Tanzania",
                "code": "TZ",
                "dial_code": "+255"
            }
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Login User
**POST** `/login`

Authenticate user and get access token.

**Request Body:**
```json
{
    "email": "john.doe@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "surname": "Doe",
            "email": "john.doe@example.com",
            "country": {
                "id": 1,
                "name": "Tanzania",
                "code": "TZ",
                "dial_code": "+255"
            }
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Logout User
**POST** `/logout` 🔒

Logout the authenticated user and revoke the current token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Get User Profile
**GET** `/profile` 🔒

Get the authenticated user's profile information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "first_name": "John",
        "surname": "Doe",
        "full_name": "John Doe",
        "gender": "male",
        "phone_number": "+255712345678",
        "email": "john.doe@example.com",
        "country": {
            "id": 1,
            "name": "Tanzania",
            "code": "TZ",
            "dial_code": "+255"
        }
    }
}
```

---

## Countries Endpoints

### Get All Countries
**GET** `/countries`

Get paginated list of all countries with user counts.

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "Tanzania",
                "code": "TZ",
                "dial_code": "+255",
                "users_count": 5,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "current_page": 1,
        "total": 20
    }
}
```

### Get Countries List
**GET** `/countries/list`

Get simple list of all countries (for dropdowns).

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Tanzania",
            "code": "TZ",
            "dial_code": "+255"
        }
    ]
}
```

### Get Single Country
**GET** `/countries/{id}`

Get details of a specific country.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Tanzania",
        "code": "TZ",
        "dial_code": "+255",
        "users_count": 5,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### Search Countries
**GET** `/countries/search`

Search countries by name, code, or dial code.

**Query Parameters:**
- `query` (required): Search term
- `per_page` (optional): Number of items per page (default: 50)

**Example:** `/countries/search?query=tan&per_page=10`

### Get Users by Country
**GET** `/countries/{id}/users`

Get paginated list of users from a specific country.

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)

### Create Country
**POST** `/countries` 🔒

Create a new country.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "Kenya",
    "code": "KE",
    "dial_code": "+254"
}
```

### Update Country
**PUT** `/countries/{id}` 🔒

Update an existing country.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "Kenya Updated",
    "code": "KE",
    "dial_code": "+254"
}
```

### Delete Country
**DELETE** `/countries/{id}` 🔒

Delete a country (only if no users are associated).

**Headers:**
```
Authorization: Bearer {token}
```

---

## Users Endpoints

### Get All Users
**GET** `/users` 🔒

Get paginated list of all users.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)

### Get Single User
**GET** `/users/{id}` 🔒

Get details of a specific user.

**Headers:**
```
Authorization: Bearer {token}
```

### Create User
**POST** `/users` 🔒

Create a new user.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "first_name": "Jane",
    "surname": "Smith",
    "gender": "female",
    "phone_number": "+254712345678",
    "email": "jane.smith@example.com",
    "password": "password123",
    "country_id": 2
}
```

### Update User
**PUT** `/users/{id}` 🔒

Update an existing user.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "first_name": "Jane Updated",
    "surname": "Smith Updated",
    "gender": "female",
    "phone_number": "+254712345679",
    "email": "jane.updated@example.com",
    "country_id": 2
}
```

### Delete User
**DELETE** `/users/{id}` 🔒

Delete a user.

**Headers:**
```
Authorization: Bearer {token}
```

### Search Users
**GET** `/users/search` 🔒

Search users by name, email, or phone number.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `query` (required): Search term
- `per_page` (optional): Number of items per page (default: 15)

**Example:** `/users/search?query=john&per_page=10`

---

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "User not found"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Internal server error"
}
```

---

## Testing the API

### Using cURL

**Register a new user:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "surname": "Doe",
    "gender": "male",
    "phone_number": "+255712345678",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "country_id": 1
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**Get countries:**
```bash
curl -X GET http://localhost:8000/api/countries/list
```

**Get user profile (with token):**
```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman

1. Import the endpoints into Postman
2. Set the base URL to `http://localhost:8000/api`
3. For protected routes, add Authorization header with Bearer token
4. Set Content-Type to `application/json` for POST/PUT requests

---

## Database Setup

Before using the API, make sure to run the migrations and seeders:

```bash
# Run migrations
php artisan migrate

# Run seeders (optional - adds sample data)
php artisan db:seed
```

This will create the database tables and populate them with sample countries and users.

---

🔒 = Protected route (requires authentication)
