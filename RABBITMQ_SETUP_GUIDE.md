# RabbitMQ Setup Guide - Windows Fix

## Problem
The error "Can not enable keepalive: function socket_import_stream does not exist" occurs because the PHP `sockets` extension is not enabled.

## Solution Steps

### 1. Enable PHP Sockets Extension

#### For XAMPP:
1. Navigate to `C:\xampp\php\`
2. Open `php.ini` in a text editor (as Administrator)
3. Find the line `;extension=sockets`
4. Remove the semicolon to make it: `extension=sockets`
5. Save the file
6. Restart Apache from XAMPP Control Panel

#### For WAMP:
1. Click the WAMP icon in system tray
2. Go to PHP → php.ini
3. Find `;extension=sockets`
4. Remove the semicolon: `extension=sockets`
5. Save and restart all services

#### For Laravel Valet (Windows):
1. Find your PHP installation path
2. Edit the active php.ini file
3. Enable the sockets extension

### 2. Verify Installation
After enabling, run:
```bash
php -m | findstr sockets
```
Expected output: `sockets`

### 3. Test RabbitMQ Setup
```bash
php artisan config:clear
php artisan rabbitmq:setup
```

### 4. If Still Having Issues
The configuration has been updated to be Windows-compatible:
- Keepalive disabled by default
- Better error handling
- Windows-specific socket constants

### 5. Manual PHP Extension Installation
If the extension is missing:

1. Download the sockets extension for your PHP version from:
   https://pecl.php.net/package/sockets

2. Place `php_sockets.dll` in your PHP `ext/` directory

3. Add to php.ini:
   ```
   extension=php_sockets.dll
   ```

### 6. Environment Variables
Add to your `.env` file:
```env
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_KEEPALIVE=false
```

## Troubleshooting

### Check PHP Configuration
```bash
php --ini
php -r "phpinfo();" | findstr sockets
```

### Test Socket Functions
```bash
php -r "var_dump(function_exists('socket_import_stream'));"
```

### RabbitMQ Service Status
Ensure RabbitMQ server is running:
```bash
# Check if RabbitMQ is running
netstat -an | findstr 5672
```

## Expected Output After Fix
```
Setting up RabbitMQ infrastructure...
✅ RabbitMQ setup completed successfully!
Exchanges: user.registration
Queues: email.notifications, sms.notifications
Bindings: email.notifications -> user.registered.email
Bindings: sms.notifications -> user.registered.sms
