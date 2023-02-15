<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $reply->content }}</title>
</head>
<body>

<h3><a href="{{ $reply->url }}">{{ $reply->content }}</a></h3>
<p>from: <a href="{{ $reply->from_url }}">{{ $reply->from }}</a></p>
@foreach($reply->images as $image)
    <img src="{{ $message->embed(Storage::path(basename($image))) }}">
@endforeach
</body>
</html>
