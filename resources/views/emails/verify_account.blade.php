<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Account - {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">Verify Your Account</h2>

        <p>Hi {{ $user->name ?? 'there' }},</p>

        <p>We noticed that your account is currently inactive. To reactivate your trading privileges, please verify your account by clicking the button below:</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:12px 24px;background-color:#007bff;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;">
                Verify My Account
            </a>
        </p>

        <p>If you did not request this, you can safely ignore this email.</p>

        <hr style="margin: 30px 0;">
        <p style="color: #888;">Sent by the <strong>{{ config('app.name') }}</strong> automated system.</p>
    </div>
</body>
</html>
