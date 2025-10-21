<!DOCTYPE html>
<html>
<head>
    <!-- ... -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>