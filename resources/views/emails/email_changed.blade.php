<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Changed Notification</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Hello,</h2>
    <p>
        This is to confirm that your registered email address has been successfully updated.
    </p>

    <p>
        <strong>Previous Email:</strong> {{ $oldEmail }}<br>
        <strong>New Email:</strong> {{ $newEmail }}
    </p>

    <p>If you did not request this change, please contact our support team immediately.</p>

    <p style="margin-top: 30px;">Thank you,<br>
    The {{ config('app.name') }} Security Team</p>
</body>
</html>