<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Auth Test</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .url-box {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-wrap: break-word;
            word-break: break-all;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .param-box {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            background-color: #000;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .highlight {
            background-color: #fffbcc;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>TikTok Authentication Test</h1>
    
    <h2>Authorization URL</h2>
    <div class="url-box">
        {{ $tiktok_auth_url }}
    </div>
    
    <h2>Parameters</h2>
    <div class="param-box">
        <strong>Client Key:</strong> {{ $client_key }}
    </div>
    <div class="param-box">
        <strong>Redirect URI:</strong> {{ $redirect_uri }}
    </div>
    <div class="param-box">
        <strong>URL-encoded Redirect URI:</strong> {{ $encoded_redirect_uri }}
    </div>
    <div class="param-box">
        <strong>Code Challenge:</strong> {{ $code_challenge }}
    </div>
    
    <h2>Troubleshooting</h2>
    <p>
        The most common issues with TikTok authentication:
    </p>
    <ol>
        <li>Client key not matching what's in TikTok Developer Portal</li>
        <li>Redirect URI not registered exactly the same in TikTok Developer Portal</li>
        <li>App not properly configured for Login Kit</li>
        <li>Using localhost which might not be allowed</li>
    </ol>
    
    <a href="{{ $tiktok_auth_url }}" class="btn">Try TikTok Authentication</a>
    
    <h2>Hard-coded Test</h2>
    <p>
        This test uses hard-coded values, not from your .env file:
    </p>
    <pre>
client_key = 'sbawslovnjuabyqhci'
redirect_uri = 'http://localhost/auth/tiktok/callback'
    </pre>
</body>
</html> 