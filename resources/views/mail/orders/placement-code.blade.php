<!DOCTYPE html>
<html lang="en">
<body>
    <p>Hello {{ $recipientName }},</p>
    <p>Your delivery code for order {{ $orderNumber }} is <strong>{{ $code }}</strong>.</p>
    <p>Give this code to the driver only after you have paid. It expires in 24 hours.</p>
    <p>{{ $appName }}</p>
</body>
</html>
