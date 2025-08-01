# User Profile API Documentation

## Overview
This document provides comprehensive documentation for all user profile-related API endpoints in the Neema Gospel application.

## Base URL
```
https://api.neemagospel.com/api
```

## Authentication
All endpoints require authentication via Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

## Endpoints

---

### 1. Get User Profile
Retrieve the authenticated user's profile information.

**Endpoint:** `GET /profile`

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "profile": {
      "id": 1,
      "user_id": 1,
      "profile_picture": "profile-pictures/profile_1_1234567890.jpg",
      "profile_picture_url": "https://api.neemagospel.com/storage/profile-pictures/profile_1_1234567890.jpg",
      "address": "123 Main Street",
      "city": "Nairobi",
      "state_province": "Nairobi County",
      "postal_code": "00100",
      "bio": "Passionate about spreading the gospel through music and ministry",
      "date_of_birth": "1990-05-15",
      "occupation": "Music Minister",
      "location_public": true,
      "profile_public": true,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Profile not found"
}
```

---

### 2. Update User Profile
Update the authenticated user's profile information.

**Endpoint:** `PUT /profile`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "profile_picture": "binary", // Optional - image file
  "address": "456 Church Avenue",
  "city": "Kisumu",
  "state_province": "Kisumu County",
  "postal_code": "40100",
  "bio": "Updated bio information",
  "date_of_birth": "1985-08-20",
  "occupation": "Pastor",
  "location_public": true,
  "profile_public": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      // User object with country relation
    },
    "profile": {
      // Updated profile object
    }
  }
}
```

**Validation Errors:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "date_of_birth": ["The date of birth must be a date before today."],
    "bio": ["The bio must not be greater than 1000 characters."]
  }
}
```

---

### 3. Update Profile Picture
Update only the profile picture with optimized image processing.

**Endpoint:** `POST /profile/picture`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request Body:**
- `profile_picture`: Image file (jpeg, png, jpg, gif, webp) max 5MB

**Response:**
```json
{
  "success": true,
  "message": "Profile picture updated successfully",
  "data": {
    "profile_picture_url": "https://api.neemagospel.com/storage/profile-pictures/profile_1_1234567890.jpg"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "profile_picture": ["The profile picture must be an image."]
  }
}
}
```

---

### 4. Delete Profile Picture
Remove the user's profile picture.

**Endpoint:** `DELETE /profile/picture`

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Profile picture deleted successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "No profile picture to delete"
}
```

---

### 5. Update Location
Update the user's location information.

**Endpoint:** `PUT /profile/location`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "address": "789 Ministry Road",
  "city": "Mombasa",
  "state_province": "Mombasa County",
  "postal_code": "80100",
  "location_public": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Location updated successfully",
  "data": {
    "location": {
      "address": "789 Ministry Road, Mombasa, Mombasa County, 80100",
      "location_public": true
    }
  }
}
```

---

## Data Models

### UserProfile Object
| Field | Type | Description | Required |
|-------|------|-------------|----------|
| `id` | integer | Unique identifier | Auto-generated |
| `user_id` | integer | Foreign key to users table | Auto-generated |
| `profile_picture` | string | Path to profile picture file | Optional |
| `address` | string | Street address | Optional |
| `city` | string | City name | Optional |
| `state_province` | string | State or province | Optional |
| `postal_code` | string | Postal/ZIP code | Optional |
| `bio` | string | User biography (max 1000 chars) | Optional |
| `date_of_birth` | date | Date of birth | Optional |
| `occupation` | string | User's occupation | Optional |
| `location_public` | boolean | Whether location is public | Optional, default: true |
| `profile_public` | boolean | Whether profile is public | Optional, default: true |
| `created_at` | datetime | Creation timestamp | Auto-generated |
| `updated_at` | datetime | Last update timestamp | Auto-generated |

---

## Image Upload Guidelines

### Profile Picture Specifications
- **Supported formats:** JPEG, PNG, JPG, GIF, WebP
- **Maximum size:** 5MB
- **Recommended dimensions:** 400x400 pixels (square)
- **Processing:** Images are automatically optimized and converted to WebP format for better compression

### Image URLs
- **Storage path:** `storage/profile-pictures/`
- **Base URL:** `https://api.neemagospel.com/storage/`
- **Format:** `profile_{user_id}_{timestamp}.{extension}`

---

## Error Handling

### Common HTTP Status Codes
- `200 OK` - Successful request
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request format
- `401 Unauthorized` - Missing or invalid authentication
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `500 Internal Server Error` - Server error

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## Rate Limiting
- **Profile updates:** 10 requests per minute
- **Image uploads:** 5 requests per minute
- **General requests:** 60 requests per minute

---

## Examples

### cURL Examples

#### Get Profile
```bash
curl -X GET https://api.neemagospel.com/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Update Profile
```bash
curl -X PUT https://api.neemagospel.com/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "bio": "Passionate about worship and ministry",
    "city": "Nairobi",
    "occupation": "Worship Leader"
  }'
```

#### Upload Profile Picture
```bash
curl -X POST https://api.neemagospel.com/api/profile/picture \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "profile_picture=@/path/to/image.jpg"
```

---

## Testing Checklist
- [ ] Authentication token is valid and not expired
- [ ] Required fields are provided when updating
- [ ] Image uploads are within size limits
- [ ] Date of birth is in the past
- [ ] Bio text does not exceed 1000 characters
- [ ] Location privacy settings are respected
- [ ] Profile picture URLs are accessible after upload
- [ ] Error responses are properly formatted

---

## Support
For API support or questions, please contact:
- **Email:** api-support@neemagospel.com
- **Documentation:** https://docs.neemagospel.com/api
- **Status Page:** https://status.neemagospel.com
