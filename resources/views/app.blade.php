<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ config('app.name') ?? "Stock Opname Inertia" }}</title>
	@vite(['resources/js/app.js', 'resources/css/app.css'])
	@fonts
	@inertiaHead
</head>
<body>
    @inertia
</body>
</html>
