<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body>
    <h1>Welcome to {{ config('app.name') }}!</h1>
    <p>Hi there,</p>
    <p>Thank you for joining {{ config('app.name') }}. We're thrilled to have you with us.</p>
    <p>You can log in to your account and start exploring right away:</p>
    <p>
        <a href="https://app.moonexe.com/login" style="display:inline-block;padding:10px 20px;background-color:#007bff;color:#ffffff;text-decoration:none;border-radius:5px;">Log In to Your Account</a>
    </p>
    <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
    <p>Warm regards,<br>The {{ config('app.name') }} Team</p>
</body>
</html>
