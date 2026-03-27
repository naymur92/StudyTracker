<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .maintenance-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .status-text {
            color: #666;
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .contact-info {
            background: #f5f5f5;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            color: #555;
        }

        .contact-info p {
            margin: 10px 0;
            font-size: 0.95em;
        }

        .footer-text {
            color: #999;
            font-size: 0.85em;
            margin-top: 30px;
        }

        @media (max-width: 600px) {
            .maintenance-container {
                padding: 40px 20px;
            }

            h1 {
                font-size: 2em;
            }

            .maintenance-icon {
                font-size: 60px;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Under Maintenance</h1>
        <div class="status-badge">Maintenance in Progress</div>
        <div class="status-text">
            <p>We're currently working hard to improve your experience.</p>
            <p>Our site will be back online shortly. Thank you for your patience!</p>
        </div>
        <div class="contact-info">
            <p><strong>Estimated completion:</strong> Coming soon</p>
            <p>For urgent inquiries, please contact us at support@example.com</p>
        </div>
        <div class="footer-text">
            <p>&copy; 2026 Security World. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
