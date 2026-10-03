<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>{{ $baslik }}</title>
<style>
    @page { margin: 18px; }
    body { margin: 0; }
    .sayfa { page-break-after: always; text-align: center; }
    .sayfa:last-child { page-break-after: auto; }
    .sayfa img { max-width: 100%; max-height: 1080px; }
</style>
</head>
<body>
@foreach ($gorseller as $g)
    <div class="sayfa"><img src="{{ $g }}"></div>
@endforeach
</body>
</html>
