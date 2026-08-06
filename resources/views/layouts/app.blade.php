<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    @if (View::hasSection('loader'))
        @yield('loader')
    @else
        <div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
            <div class="w-16 h-16 border-4 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-red-600 font-semibold">Loading...</p>
        </div>
    @endif

    <aside class="hidden md:flex fixed top-0 left-0 w-64 h-screen bg-gray-200 flex-col py-6 overflow-y-auto z-40">
        <!-- Logo -->
        <div class="px-6 mb-10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 w-full overflow-hidden">
                <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="w-10 h-10 flex-shrink-0">
                <span class="text-xl font-bold text-red-600 truncate">Bunker App</span>
            </a>
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-6 space-y-4">
            <p class="text-sm text-gray-500 uppercase">Menu</p>

            <a href="{{ route('dashboard') }}"
               class="text-gray-500 block py-2 hover:underline {{ request()->routeIs('dashboard') ? 'font-bold underline' : '' }}">
               <i class="fas fa-tachometer-alt"></i> Home / Dashboard
            </a>
            <a href="{{ route('pages.consumption') }}"
               class="text-gray-500 block py-2 hover:underline {{ request()->routeIs('pages.consumption') ? 'font-bold underline' : '' }}">
               <i class="fas fa-chart-line"></i> Consumption Analysis
            </a>
            <a href="{{ route('po.planning') }}"
               class="text-gray-500 block py-2 hover:underline {{ request()->routeIs('po.planning') ? 'font-bold underline' : '' }}">
               <i class="fas fa-gas-pump"></i> Refueling Planning
            </a>
            <a href="{{ route('fuel-baseline.index') }}"
               class="text-gray-500 block py-2 hover:underline {{ request()->routeIs('fuel-baseline.index') ? 'font-bold underline' : '' }}">
               <i class="fas fa-gauge"></i> Baseline Management
            </a>
            <a href="{{ route('po.baseline') }}"
               class="text-gray-500 block py-2 hover:underline {{ request()->routeIs('po.baseline') ? 'font-bold underline' : '' }}">
               <i class="fas fa-balance-scale"></i> Baseline Analysis
            </a>
        </nav>
    </aside>

    <!-- Main Layout -->
    <div class="min-h-screen flex flex-col ml-64">
        <!-- Content -->
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-200 text-center py-4 text-gray-600 mt-auto">
            &copy; {{ date('Y') }} Bunker App. All Rights Reserved.
        </footer>
    </div>

    <!-- Script Loader Global -->
    <script>
        function showLoader() {
            document.getElementById("loader").classList.remove("hidden");
        }
        function hideLoader() {
            document.getElementById("loader").classList.add("hidden");
        }

        window.addEventListener("load", hideLoader);
        window.addEventListener("beforeunload", function() { showLoader(); });
        window.addEventListener("pageshow", function() { hideLoader(); });

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("a").forEach(function(link) {
                link.addEventListener("click", function(e) {
                    if (link.target === "_blank" || link.getAttribute("href").startsWith("#") || link.hostname !== window.location.hostname) {
                        return;
                    }
                    e.preventDefault();
                    showLoader();
                    window.location = link.href;
                });
            });

            document.querySelectorAll("form").forEach(function(form) {
                form.addEventListener("submit", function() {
                    showLoader();
                });
            });
        });
    </script>
</body>
</html>
