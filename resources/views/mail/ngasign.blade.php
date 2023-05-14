<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $author }} 签名</title>
</head>
<body>

<h3>{{ $sign }}</h3>
{{--@foreach($reply->images as $image)--}}
{{--    <img src="{{ $message->embed(Storage::path(basename($image))) }}">--}}
{{--@endforeach--}}
</body>
</html>
