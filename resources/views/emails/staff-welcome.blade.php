<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $saccoName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #1a202c; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; }
        .header p { color: #bfdbfe; font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 17px; font-weight: 600; color: #1e40af; margin-bottom: 16px; }
        .text { color: #374151; font-size: 15px; line-height: 1.7; margin-bottom: 16px; }
        .credentials { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 24px 28px; margin: 24px 0; }
        .credentials h3 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #1d4ed8; margin-bottom: 16px; }
        .credential-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dbeafe; font-size: 14px; }
        .credential-row:last-child { border-bottom: none; }
        .credential-label { color: #6b7280; font-weight: 500; }
        .credential-value { color: #1e293b; font-weight: 700; font-family: 'Courier New', monospace; }
        .cta { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
        .warning { background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-top: 16px; }
        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $saccoName }}</h1>
            <p>Staff Account Created</p>
        </div>
        <div class="body">
            <p class="greeting">Hello, {{ $staffUser->name }}!</p>
            <p class="text">
                Your staff account has been created on the <strong>{{ $saccoName }}</strong> management system.
                You can now log in to manage members, loans, savings accounts, and more.
            </p>

            <div class="credentials">
                <h3>🔐 Your Login Credentials</h3>
                <div class="credential-row">
                    <span class="credential-label">Portal URL</span>
                    <span class="credential-value">{{ $panelUrl }}</span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Email</span>
                    <span class="credential-value">{{ $staffUser->email }}</span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Password</span>
                    <span class="credential-value">{{ $plainPassword }}</span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Your Role</span>
                    <span class="credential-value">{{ \App\Models\Tenant\User::ROLES[$staffUser->role] ?? ucfirst($staffUser->role) }}</span>
                </div>
            </div>

            <div class="cta">
                <a href="{{ $panelUrl }}" class="btn">Log In to Your Account →</a>
            </div>

            <div class="warning">
                ⚠️ <strong>Important:</strong> Please change your password immediately after your first login.
                Never share your credentials with anyone.
            </div>
        </div>
        <div class="footer">
            <p>This email was sent by {{ $saccoName }} HSMS. If you were not expecting this, please contact your administrator.</p>
            <p style="margin-top:8px;">© {{ date('Y') }} SACCO HSMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
