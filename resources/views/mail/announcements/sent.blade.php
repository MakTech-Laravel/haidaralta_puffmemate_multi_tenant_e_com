<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F4F0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F4F0;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">
                    <tr>
                        <td style="background-color:{{ $themeColor }};padding:20px 28px;border-radius:12px 12px 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    @if (filled($logoUrl))
                                        <td style="vertical-align:middle;padding-right:12px;">
                                            <a href="{{ $homeUrl }}" style="text-decoration:none;">
                                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="36" style="display:block;border:0;height:36px;width:auto;">
                                            </a>
                                        </td>
                                    @endif
                                    <td style="vertical-align:middle;">
                                        <a href="{{ $homeUrl }}" style="color:#ffffff;font-family:Inter,Helvetica,Arial,sans-serif;font-size:18px;font-weight:700;text-decoration:none;">
                                            {{ $appName }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;border:1px solid #E5E0D8;border-top:0;padding:32px 28px;border-radius:0 0 12px 12px;font-family:Inter,Helvetica,Arial,sans-serif;">
                            <p style="margin:0 0 10px;color:#8A8478;font-size:11px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;">
                                Announcement
                            </p>
                            <h1 style="margin:0 0 16px;color:#292929;font-size:22px;line-height:1.3;font-weight:700;">
                                {{ $title }}
                            </h1>
                            <p style="margin:0 0 16px;color:#444444;font-size:16px;line-height:1.5;">
                                Hello {{ $recipientName }},
                            </p>
                            <p style="margin:0 0 24px;color:#444444;font-size:16px;line-height:1.65;">
                                {!! nl2br(e($body)) !!}
                            </p>
                            @if (filled($linkUrl))
                                <a href="{{ $linkUrl }}" style="display:inline-block;background-color:{{ $themeColor }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-size:15px;font-weight:600;">
                                    {{ filled($linkLabel) ? $linkLabel : 'Open' }}
                                </a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 8px;text-align:center;font-family:Inter,Helvetica,Arial,sans-serif;color:#8A8478;font-size:12px;line-height:1.6;">
                            &copy; {{ date('Y') }} {{ $appName }}
                            @if (filled($contactEmail) || filled($contactPhone))
                                <br>
                                @if (filled($contactEmail))
                                    <a href="mailto:{{ $contactEmail }}" style="color:#8A8478;text-decoration:none;">{{ $contactEmail }}</a>
                                @endif
                                @if (filled($contactEmail) && filled($contactPhone))
                                    &nbsp;&middot;&nbsp;
                                @endif
                                @if (filled($contactPhone))
                                    {{ $contactPhone }}
                                @endif
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
