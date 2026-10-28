<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .maintenance-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 32px;
        }

        p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 18px;
        }

        .eta {
            background: #f0f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .admin-link {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .admin-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .admin-link a:hover {
            opacity: 1;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <div class="icon">🔧</div>
        <h1>We're Currently Under Maintenance</h1>
        <p>
            We're making some improvements to serve you better.
            Our system is temporarily unavailable.
        </p>

        <div class="eta">
            <strong>⏱️ Estimated Time:</strong> We'll be back shortly
        </div>

        <div class="spinner"></div>

        <p style="font-size: 14px; color: #999;">
            Thank you for your patience!
        </p>

        <div class="admin-link">
            <a href="{{ url('/access') }}">Administrator Access</a>
        </div>
    </div>
</body>

</html>
