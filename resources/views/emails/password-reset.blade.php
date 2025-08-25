<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>NEEMA GOSPEL - Reset Password</title>
    <style>
        /* General reset */
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        a {
            text-decoration: none;
        }
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* Header */
        .header {
            background-color: #f9ad7efd;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        /* Content */
        .content {
            padding: 40px;
            color: #333333;
        }
        .content h2 {
            margin-top: 0;
            color: #333333;
        }
        .reset-button {
            display: inline-block;
            background-color: #f9ad7e;
            color: #ffffff !important;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        /* Mobile */
        @media screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .header {
                padding: 20px;
            }
            .reset-button {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="email-container" cellpadding="0" cellspacing="0">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h1>NEEMA GOSPEL</h1>
                            <p>Password Reset Request</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="content">
                            <h2>Hello!</h2>
                            <p>You requested to reset your password. Click the button below to set a new password:</p>

                            <p style="text-align:center;">
                                <a href="{{ $url }}" class="reset-button">Reset Password</a>
                            </p>

                            <p>If you didn’t request this, you can safely ignore this email.</p>

                            <p>Best regards,<br>The NEEMA GOSPEL Team</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p>This is an automated message. Please do not reply to this email.</p>

                            <p>&copy; {{ date('Y') }} Neema Gospel. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
