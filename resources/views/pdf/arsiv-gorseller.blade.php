<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>{{ $baslik }}</title>
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 18px; }
    body { margin: 0; }
    .sayfa { page-break-after: always; text-align: center; }
    .sayfa:last-child { page-break-after: auto; }
    .sayfa img { max-width: 100%; max-height: 1000px; }
</style>
</head>
<body>
@foreach ($gorseller as $g)
    @if ($g && (str_starts_with((string) $g, 'data:image/') || is_file((string) $g)))
        <div class="sayfa"><img src="{{ $g }}"></div>
    @endif
@endforeach
</body>
</html>
