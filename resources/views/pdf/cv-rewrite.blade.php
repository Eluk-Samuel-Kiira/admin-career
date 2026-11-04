<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 40px 45px; }
        body { margin: 0; }
        {!! file_get_contents(public_path('css/cv-document.css')) !!}
    </style>
</head>
<body>
    <div class="cv-doc">
        {!! $content !!}
    </div>
</body>
</html>