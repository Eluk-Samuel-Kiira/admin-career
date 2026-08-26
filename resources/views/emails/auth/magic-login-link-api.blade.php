<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Magic Link</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
    <table cellpadding="0" cellspacing="0" width="100%" style="background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 40px;">
        <tr>
            <td style="text-align: center;">
                <div style="font-size: 48px; margin-bottom: 10px;">
                    {{ $countryFlag ?? '🌍' }}
                </div>
                <h1 style="color: #0B1C2E; margin-bottom: 8px; font-size: 28px; font-weight: 800;">
                    Hello {{ $user->first_name ?? 'there' }}!
                </h1>
                <p style="color: #64748B; font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                    @if($isNew)
                        Welcome to <strong>{{ $countryName ?? 'Stardena Careers' }}</strong>! 
                        We're excited to have you on board.
                    @else
                        Click the button below to sign in to your <strong>{{ $countryName ?? 'Stardena Careers' }}</strong> account.
                    @endif
                </p>
                <p style="color: #64748B; font-size: 15px; line-height: 1.6; margin-bottom: 28px;">
                    This magic link will expire in <strong>24 hours</strong>.
                </p>
                
                <a href="{{ $url }}" 
                   style="display: inline-block; background: linear-gradient(135deg, #20AA3E 0%, #03A588 100%); 
                          color: white; padding: 16px 40px; border-radius: 10px; 
                          text-decoration: none; font-weight: 700; font-size: 17px;
                          margin: 8px 0 28px;">
                    @if($isNew)
                        Sign In & Get Started
                    @else
                        Sign In to {{ $countryName ?? 'Stardena Careers' }}
                    @endif
                </a>
                
                <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin-bottom: 8px;">
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <p style="color: #03A588; font-size: 13px; word-break: break-all; background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    {{ $url }}
                </p>
                
                <hr style="border: none; border-top: 1px solid #E7EFEF; margin: 24px 0;">
                <p style="color: #94A3B8; font-size: 12px; text-align: center; line-height: 1.6;">
                    If you didn't request this, you can safely ignore this email.<br>
                    For security, this link will expire after 24 hours.
                </p>
                <p style="color: #94A3B8; font-size: 12px; text-align: center; margin-top: 8px;">
                    <strong>{{ $countryName ?? 'Stardena Careers' }}</strong> — Powered by Stardena Careers
                </p>
            </td>
        </tr>
    </table>
</body>
</html>