<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-red-600 text-white py-4">
            <div class="container mx-auto px-4 flex justify-between items-center">
                <a href="{{ url('/') }}" class="text-lg font-bold">Bunker App</a>
                <div>
                    <a href="/menu" class="px-4 py-2 hover:underline font-bold">Menu</a>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-200 text-center py-4 mt-6 text-gray-600">
            &copy; {{ date('Y') }} Bunker App. All Rights Reserved.
        </footer>
    </div>

</body>
</html>
