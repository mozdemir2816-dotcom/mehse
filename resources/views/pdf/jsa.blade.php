<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
@include('pdf.partials.jsa-stiller')
</head>
<body>
@include('pdf.partials.jsa-govde', [
    'sablon' => $sablon,
    'firma' => $firma,
    'uzman' => $uzman ?? $firma?->igu,
])
</body>
</html>
