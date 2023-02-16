<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $reply->content }}</title>
</head>
<body>

<h3>{{ $reply->content }}</h3>
<p>from: {{ $reply->from }}</p>
{{--@foreach($reply->images as $image)--}}
{{--    <img src="{{ $message->embed(Storage::path(basename($image))) }}">--}}
{{--@endforeach--}}
</body>
</html>
