<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Request - {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">Support Request from {{ $data['username'] }}</h2>

        <p><strong>Email:</strong> {{ $data['email'] }}</p>
        <p><strong>Subject:</strong> {{ $data['subject'] }}</p>

        <hr style="margin: 20px 0;">

        <p><strong>Question:</strong></p>
        <p style="white-space: pre-line;">{{ $data['question'] }}</p>

        <hr style="margin: 30px 0;">
        <p style="color: #888;">Sent from the <strong>{{ config('app.name') }}</strong> user dashboard.</p>
    </div>
</body>
</html>
