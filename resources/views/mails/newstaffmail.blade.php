<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Hope Pathway</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 40px; margin: 0;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden;">

        <!-- Header with Logo -->
        <div style="background: #151546; padding: 30px; text-align: center;">
            <img src="{{ asset('images/logo.png') }}" alt="Hope Pathway Logo" style="max-height: 60px;">
        </div>

        <!-- Body -->
        <div style="padding: 30px; text-align: center;">
            <h2 style="color: #151546; margin-bottom: 20px;">Welcome to Hope Pathway!</h2>
            <p style="font-size: 16px; color: #333;">Dear <strong>{{ $data['name'] }}</strong>,</p>
            <p style="font-size: 16px; color: #555; margin: 15px 0;">
                Your staff account has been successfully created. Below are your login credentials:
            </p>

            <div style="background: #f4f6f8; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: left;">
                <p><strong>Email:</strong> {{ $data['email'] }}</p>
                <p><strong>Password:</strong> {{ $data['password'] }}</p>
            </div>

            <p style="margin-top: 20px; color: #555;">
                You can log in to your account by clicking the button below:
            </p>

            <a href="{{ $data['login_url'] }}"
               style="display: inline-block; background: #4cc0e8; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-size: 16px; margin-top: 15px;">
                Get Started
            </a>
        </div>

        <!-- Footer -->
        <div style="background: #f4f6f8; padding: 20px; text-align: center; font-size: 13px; color: #888;">
            <p>&copy; {{ date('Y') }} Hope Pathway. All rights reserved.</p>
        </div>

    </div>
</body>
</html>
