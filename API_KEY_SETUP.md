# API Key Configuration Guide

This guide explains how to set up and use API keys for Flutter and React applications.

## 🔧 Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed API Keys
```bash
php artisan db:seed --class=ApiKeySeeder
```

### 3. Apply API Key Middleware

Add the middleware to your API routes in `routes/api.php`:

```php
// Apply API key middleware to all API routes
Route::middleware(['api.key'])->group(function () {
    // All your existing API routes here
});
```

## 🔑 API Keys Provided

### Production Keys
- **Flutter App**: `flutter_app_key_2024_secure_key_12345`
- **React App**: `react_app_key_2024_secure_key_67890`

### Development Keys
- **Flutter Dev**: `dev_flutter_key_2024_dev_mode_11111`
- **React Dev**: `dev_react_key_2024_dev_mode_22222`

## 📱 How to Use in Flutter

### Add API Key to Headers
```dart
import 'package:http/http.dart' as http;

final response = await http.post(
  Uri.parse('http://127.0.0.1:8000/api/login'),
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'flutter_app_key_2024_secure_key_12345',
  },
  body: jsonEncode({
    'login': 'user@example.com',
    'password': 'password123',
  }),
);
```

## ⚛️ How to Use in React

### Add API Key to Headers
```javascript
const login = async () => {
  const response = await fetch('http://127.0.0.1:8000/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': 'react_app_key_2024_secure_key_67890',
    },
    body: JSON.stringify({
      login: 'user@example.com',
      password: 'password123',
    }),
  });
  
  const data = await response.json();
  return data;
};
```

## 🔍 Monitoring API Usage

### Check API Key Usage
```bash
php artisan tinker
>>> \App\Models\ApiKey::all()
```

### View Request Logs
```bash
tail -f storage/logs/laravel.log
```

## 🛠️ Creating New API Keys

### Via Artisan Command
```bash
php artisan tinker
>>> \App\Models\ApiKey::create([
    'name' => 'New Flutter App',
    'key' => \Illuminate\Support\Str::random(32),
    'client_type' => 'flutter',
    'rate_limit' => 1000,
    'expires_at' => now()->addYear()
]);
```

### Via API Endpoint (Admin Only)
```http
POST /api/admin/api-keys
Content-Type: application/json
Authorization: Bearer ADMIN_TOKEN

{
    "name": "New Client App",
    "client_type": "flutter",
    "rate_limit": 1000,
    "expires_at": "2025-12-31"
}
```

## 📊 API Key Features

- **Rate Limiting**: Configurable per client
- **Expiration**: Set expiry dates for keys
- **Client Tracking**: Track Flutter vs React usage
- **Request Monitoring**: Count requests per key
- **Security**: Unique keys for each client
- **Deactivation**: Disable keys instantly

## 🚨 Error Handling

### Missing API Key
```json
{
    "success": false,
    "message": "API key is required",
    "error": "Missing X-API-Key header or api_key query parameter"
}
```

### Invalid API Key
```json
{
    "success": false,
    "message": "Invalid API key",
    "error": "The provided API key is not valid"
}
```

### Rate Limit Exceeded
```json
{
    "success": false,
    "message": "Rate limit exceeded",
    "error": "You have exceeded the request limit for this API key"
}
```

## 🔐 Security Best Practices

1. **Never commit API keys to version control**
2. **Use environment variables for production keys**
3. **Rotate keys regularly**
4. **Monitor usage patterns**
5. **Set appropriate rate limits**
6. **Use HTTPS in production**

## 🔄 Environment Variables

Add to your `.env` file:
```env
API_KEY_FLUTTER=flutter_app_key_2024_secure_key_12345
API_KEY_REACT=react_app_key_2024_secure_key_67890
```

## 📈 Usage Monitoring

### Check API Key Statistics
```bash
php artisan tinker
>>> \App\Models\ApiKey::where('client_type', 'flutter')->get()
>>> \App\Models\ApiKey::where('client_type', 'react')->get()
```

### Reset Request Counts
```bash
php artisan tinker
>>> \App\Models\ApiKey::query()->update(['requests_count' => 0])
