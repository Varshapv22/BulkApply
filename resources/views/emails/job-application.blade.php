<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #1f2937; line-height: 1.6;">
    {!! nl2br(e($renderedBody)) !!}
    @if (!empty($trackingId))
        <img src="{{ url('/track/pixel/' . $trackingId) }}" width="1" height="1" alt="" style="display:none;">
    @endif
</body>
</html>
