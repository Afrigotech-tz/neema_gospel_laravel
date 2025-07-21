# Neema Gospel API Documentation

## Overview
This is the official API documentation for the Neema Gospel backend system. This Laravel-based API provides endpoints for user management, event management, authentication, and country management.

## Base URL
```
https://api.neemagospel.com/api
```

## Authentication
The API uses Laravel Sanctum for token-based authentication. Include the Bearer token in the Authorization header for protected endpoints.

```
Authorization: Bearer {your_token_here}
```

## Response Format
All API responses follow a consistent JSON format:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {...}
}
```

## Error Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {...}
}
```

---

## Authentication Endpoints

### Register User
**POST** `/register`

Register a new user with email or phone number verification.

**Request Body:**
```json
{
  "first_name": "John",
  "surname": "Doe",
  "gender": "male",
  "phone_number": "+255712345678",
  "email": "john@example.com",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "country_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully. Please verify your account using the OTP sent to your phone.",
  "data": {
    "user": {...},
    "verification_method": "mobile"
  }
}
```

### Login
**POST** `/login`

Authenticate user with email or phone number.

**Request Body:**
```json
{
  "login": "john@example.com",
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "sanctum_token_here",
    "token_type": "Bearer"
  }
}
```

### Verify OTP
**POST** `/auth/verify-otp`

Verify OTP code for account activation.

**Request Body:**
```json
{
  "login": "john@example.com",
  "otp_code": "123456"
}
```

### Resend OTP
**POST** `/auth/resend-otp`

Resend OTP to user's email or phone.

**Request Body:**
```json
{
  "login": "john@example.com"
}
```

### Logout
**POST** `/logout`

Logout authenticated user (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

---

## User Management Endpoints

### Get All Users
**GET** `/users`

Retrieve paginated list of all users.

**Query Parameters:**
- `per_page` (integer): Number of items per page (default: 15)
- `page` (integer): Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Get User by ID
**GET** `/users/{id}`

Retrieve specific user details.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "surname": "Doe",
    "email": "john@example.com",
    "phone_number": "+255712345678",
    "country": {...}
  }
}
```

### Create User
**POST** `/users`

Create a new user (requires authentication).

**Request Body:**
```json
{
  "first_name": "Jane",
  "surname": "Smith",
  "phone_number": "+255712345679",
  "email": "jane@example.com",
  "password": "securepassword123",
  "country_id": 1
}
```

### Update User
**PUT** `/users/{id}`

Update user information (requires authentication).

**Request Body:**
```json
{
  "first_name": "Jane Updated",
  "email": "jane.updated@example.com"
}
```

### Delete User
**DELETE** `/users/{id}`

Delete a user (requires authentication).

### Search Users
**GET** `/users/search`

Search users by name, email, or phone.

**Query Parameters:**
- `query` (string): Search term
- `per_page` (integer): Items per page (default: 15)

---

## Event Management Endpoints

### Get All Events
**GET** `/events`

Retrieve paginated list of events.

**Query Parameters:**
- `per_page` (integer): Items per page (default: 15)
- `type` (string): Filter by event type
- `date_from` (date): Filter events from this date
- `date_to` (date): Filter events to this date
- `sort_by` (string): Sort field (default: date)
- `sort_order` (string): Sort order (asc/desc)

### Get Event by ID
**GET** `/events/{id}`

Retrieve specific event details.

### Create Event
**POST** `/events`

Create a new event (requires authentication).

**Request Body:**
```json
{
  "title": "Sunday Service",
  "type": "service",
  "start_date": "2024-12-15 09:00:00",
  "end_date": "2024-12-15 12:00:00",
  "venue": "Main Auditorium",
  "location": "123 Church Street",
  "city": "Dar es Salaam",
  "description": "Weekly Sunday service"
}
```

### Update Event
**PUT** `/events/{id}`

Update event information (requires authentication).

### Delete Event
**DELETE** `/events/{id}`

Delete an event (requires authentication).

### Get Upcoming Events
**GET** `/events/upcoming`

Get list of upcoming events.

### Search Events
**GET** `/events/search`

Search events by title, location, or description.

**Query Parameters:**
- `query` (string): Search term

---

## Profile Management Endpoints

### Get User Profile
**GET** `/profile`

Get authenticated user's profile information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {...},
    "profile": {...}
  }
}
```

### Update Profile
**PUT** `/profile`

Update authenticated user's profile.

**Request Body:**
```json
{
  "bio": "Passionate about gospel music",
  "city": "Dar es Salaam",
  "occupation": "Musician"
}
```

### Update Profile Picture
**POST** `/profile/picture`

Upload/update profile picture.

**Request Body:**
- `profile_picture` (file): Image file (max 5MB)

### Update Location
**POST** `/profile/location`

Update user's location coordinates.

**Request Body:**
```json
{
  "latitude": -6.7924,
  "longitude": 39.2083,
  "city": "Dar es Salaam"
}
```

### Delete Profile Picture
**DELETE** `/profile/picture`

Remove profile picture.

---

## Country Management Endpoints

### Get All Countries
**GET** `/countries`

Retrieve paginated list of countries.

**Query Parameters:**
- `per_page` (integer): Items per page (default: 50)

### Get Country by ID
**GET** `/countries/{id}`

Retrieve specific country details.

### Get Country List (Simple)
**GET** `/countries/list`

Get simplified country list for dropdowns.

### Search Countries
**GET** `/countries/search`

Search countries by name or code.

**Query Parameters:**
- `query` (string): Search term

### Get Users by Country
**GET** `/countries/{id}/users`

Get users from a specific country.

---

## Language Endpoints

### Get Available Languages
**GET** `/languages`

Get list of available languages for the application.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "English",
      "code": "en",
      "is_default": true
    },
    {
      "id": 2,
      "name": "Swahili",
      "code": "sw",
      "is_default": false
    }
  ]
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

## Rate Limiting
- Authentication endpoints: 10 requests per minute
- General API endpoints: 60 requests per minute

## Pagination
All list endpoints support pagination with the following parameters:
- `per_page`: Number of items per page (default varies by endpoint)
- `page`: Page number to retrieve

## File Uploads
- Maximum file size: 5MB
- Supported image formats: JPEG, PNG, JPG, GIF, WebP
- Files are stored in public storage with optimized naming

## Webhooks
Currently, no webhooks are implemented. Future versions may include:
- Event registration notifications
- User activity alerts
- System maintenance notifications

## SDK and Libraries
- **Backend**: Laravel 11.x with Sanctum for API authentication
- **Database**: MySQL with Eloquent ORM
- **File Storage**: Local public storage with optimization
- **SMS Service**: Configurable SMS provider integration
- **Email Service**: Laravel Mail with queue support

## Support
For API support or questions, please contact:
- Email: support@neemagospel.com
- Documentation Updates: This documentation is updated with each API release
