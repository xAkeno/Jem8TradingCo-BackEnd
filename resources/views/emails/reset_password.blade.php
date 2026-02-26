<!-- resources/views/emails/reset_password.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Hello!</h2>
    <p>You can reset your password using the link below:</p>
    <a href="{{ $resetLink }}">{{ $resetLink }}</a> <!-- must be plain $resetLink -->
    <p>This link will expire in 15 minutes.</p>
</body>
</html>