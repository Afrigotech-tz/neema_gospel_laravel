<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEEMA GOSPEL - Verification Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #FF5600;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 40px;
        }
        .otp-code {
            background-color: #f8f9fa;
            border: 2px dashed #FF5600;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #FF5600;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NEEMA GOSPEL</h1>
            <p>Your Verification Code</p>
        </div>

        <div class="content">
            <h2>Hello!</h2>
            <p>Thank you for registering with NEEMA GOSPEL. Please use the verification code below to complete your registration:</p>

            <div class="otp-code">
                {{ $otp }}
            </div>

            <div class="warning">
                <strong>Important:</strong> This code will expire in 10 minutes. Do not share this code with anyone.
            </div>

            <p>If you didn't request this code, please ignore this email.</p>

            <p>Best regards,<br>
            The NEEMA GOSPEL Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Neema Gospel. All rights reserved.</p>
        </div>
    </div>
</body>

</html>

