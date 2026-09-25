<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }
        .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: #059669; padding: 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .body { padding: 32px; color: #374151; line-height: 1.6; }
        .btn { display: inline-block; background: #059669; color: #ffffff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 16px; }
        .footer { padding: 24px 32px; text-align: center; color: #9ca3af; font-size: 13px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Real3D.io</h1>
        </div>
        <div class="body">
            <p>{{ __('emails.welcome_greeting', ['name' => $user->name]) }}</p>
            <p>{{ __('emails.welcome_body') }}</p>
            <p style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="btn">{{ __('emails.welcome_cta') }}</a>
            </p>
            <p>{{ __('emails.welcome_closing') }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Real3D.io. {{ __('emails.all_rights_reserved') }}
        </div>
    </div>
</body>
</html>
