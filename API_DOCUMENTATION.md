# Neema Gospel API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require Bearer token authentication after login, except for registration and login endpoints.

## Endpoints Overview

### Authentication Endpoints

#### 1. Register User
**POST** `/register`

Register a new user with email or phone number verification.

**Request Body:**
```json
{
  "first_name": "string",
  "surname": "string",
  "gender": "male|female",
  "phone_number": "string",
  "email": "string",
  "password": "string",
  "password_confirmation": "string",
  "country_id": "integer",
  "verification_method": "email|mobile"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully. Please verify your account using the OTP sent to your email.",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "surname": "Doe",
      "email": "john@example.com",
      "phone_number": "+1234567890",
      "verification_method": "email",
      "country": {
        "id": 1,
        "name": "United States"
      }
    }
  }
  
}
```

**Status Codes:**
- 201: Created successfully
- 422: Validation errors
- 500: Server error

#### 2. Login User
**POST** `/login`

Login with email or phone number.

**Request Body:**
```json
{
  "login": "john@example.com",
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
      "email": "john@example.com"
    },
    "token": "1|laravel_sanctum_token",
    "token_type": "Bearer"
  }
}
```

**Status Codes:**
- 200: Success
- 401: Invalid credentials
- 403: Account not verified

#### 3. Verify OTP
**POST** `/verify-otp`

Verify account using OTP.

**Request Body:**
```json
{
  "login": "john@example.com",
  "otp_code": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Account verified successfully",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "surname": "Doe"
    },
    "token": "1|laravel_sanctum_token"
  }
}
```

#### 4. Resend OTP
**POST** `/resend-otp`

Resend OTP to user's email or phone.

**Request Body:**
```json
{
  "login": "john@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "New OTP sent successfully to your email."
}
```

#### 5. Logout
**POST** `/logout`
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### 6. Get Profile
**GET** `/profile`
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "surname": "Doe",
    "email": "john@example.com",
    "phone_number": "+1234567890",
    "country": {
      "id": 1,
      "name": "United States"
    }
  }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## Status Codes

- **200** - Success
- **201** - Created
- **401** - Unauthorized
- **403** - Forbidden
- **404** - Not Found
- **422** - Validation Error
- **500** - Server Error

## Rate Limiting

- Registration: 5 attempts per minute
- Login: 10 attempts per minute
- OTP resend: 3 attempts per 5 minutes

## Security Notes

- All passwords are hashed using bcrypt
- OTP codes expire after 15 minutes
- Tokens expire based on Laravel Sanctum configuration
- HTTPS is required for production environments

