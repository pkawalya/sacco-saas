<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #1a202c; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
        .header p { color: #bfdbfe; font-size: 13px; margin-top: 4px; }
        .body { padding: 36px 40px; line-height: 1.75; font-size: 15px; color: #374151; }
        .body p { margin-bottom: 16px; }
        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
        .footer a { color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>SACCO HSMS</h1>
            <p>{{ $subject }}</p>
        </div>
        <div class="body">
            {!! nl2br(e($body)) !!}
        </div>
        <div class="footer">
            <p>This is an automated message from SACCO HSMS. Do not reply directly.</p>
            <p style="margin-top:8px;">© {{ date('Y') }} SACCO HSMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
