{{ $appName }}

{{ $title }}

Hello {{ $recipientName }},

{{ $body }}
@if (filled($linkUrl))

{{ filled($linkLabel) ? $linkLabel : 'Open' }}: {{ $linkUrl }}
@endif

© {{ date('Y') }} {{ $appName }}
@if (filled($contactEmail))
{{ $contactEmail }}
@endif
@if (filled($contactPhone))
{{ $contactPhone }}
@endif
