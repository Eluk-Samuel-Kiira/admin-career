<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.7;
            color: #111;
        }
        p { margin: 0 0 1em; }
        strong { font-weight: 700; }
    </style>
</head>
<body>
    {!! $content !!}
</body>
</html>