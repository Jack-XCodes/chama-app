<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $subject ?? 'Notification' }} - {{ config('app.name') }}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Main styles */
        body {
            background-color: #f4f4f4;
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            padding: 30px 40px;
            text-align: center;
        }

        .logo {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
            margin-bottom: 10px;
            display: block;
        }

        .tagline {
            color: #ffffff;
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .email-body {
            padding: 40px;
            color: #333333;
            line-height: 1.6;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #8B4513;
        }

        .content {
            font-size: 16px;
            margin-bottom: 30px;
        }

        .content p {
            margin-bottom: 16px;
        }

        .highlight-box {
            background-color: #f8f9fa;
            border-left: 4px solid #8B4513;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .urgent-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .error-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #8B4513;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #A0522D;
        }

        .button-secondary {
            background-color: #6c757d;
        }

        .button-secondary:hover {
            background-color: #5a6268;
        }

        .transaction-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .transaction-details table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-details td {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .transaction-details td:first-child {
            font-weight: 600;
            color: #495057;
            width: 35%;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #8B4513;
        }

        .amount.positive {
            color: #28a745;
        }

        .amount.negative {
            color: #dc3545;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #8B4513;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
        }

        .footer-text {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #8B4513;
            font-size: 18px;
            text-decoration: none;
        }

        /* Mobile responsive */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }

            .email-header,
            .email-body,
            .email-footer {
                padding: 20px !important;
            }

            .logo {
                font-size: 24px;
            }

            .content {
                font-size: 14px;
            }

            .button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }

            .transaction-details td:first-child {
                width: 40%;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #1a1a1a;
            }
            
            .email-body {
                color: #e0e0e0;
            }
            
            .email-footer {
                background-color: #2a2a2a;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <a href="{{ config('app.url') }}" class="logo">
                {{ config('app.name', 'Chama Management') }}
            </a>
            <p class="tagline">Building Financial Success Together</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-links">
                <a href="{{ config('app.url') }}">Dashboard</a>
                <a href="{{ config('app.url') }}/profile">Profile</a>
                <a href="{{ config('app.url') }}/reports">Reports</a>
                <a href="{{ config('app.url') }}/help">Help</a>
            </div>

            <div class="social-links">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="WhatsApp">📱</a>
                <a href="#" title="Email">📧</a>
            </div>

            <div class="footer-text">
                <p>
                    <strong>{{ config('app.name') }}</strong><br>
                    P.O. Box 12345, Nairobi, Kenya<br>
                    Phone: +254 700 000 000 | Email: info@chama.co.ke
                </p>
                
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    You're receiving this email because you're a member of our group.
                    <a href="{{ route('notification-preferences') }}" style="color: #8B4513;">Manage your notification preferences</a>
                    or <a href="#" style="color: #8B4513;">unsubscribe</a>.
                </p>
                
                <p style="font-size: 12px; color: #999; margin-top: 15px;">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>