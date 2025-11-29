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
    @yield('loader')
    @if (View::hasSection('loader'))
        @yield('loader')
    @else
        <div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50 hidden">
            <div class="w-16 h-16 border-4 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
        </div>
    @endif

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

    <script>
        // Fungsi global untuk loader
        function showLoader() {
            document.getElementById("loader").classList.remove("hidden");
        }
        function hideLoader() {
            document.getElementById("loader").classList.add("hidden");
        }

        // Loader otomatis hilang setelah page load
        window.addEventListener("load", hideLoader);

        // Contoh penggunaan dengan jQuery AJAX
        $(document).on("submit", "#myForm", function(e) {
            e.preventDefault();
            showLoader();
            $.ajax({
                url: "/submit",
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    console.log("Success:", response);
                },
                error: function(err) {
                    console.error("Error:", err);
                },
                complete: function() {
                    hideLoader();
                }
            });
        });

        // Contoh penggunaan dengan Axios
        document.addEventListener("DOMContentLoaded", function() {
            const btn = document.getElementById("fetchBtn");
            if (btn) {
                btn.addEventListener("click", function() {
                    showLoader();
                    axios.get("/api/data")
                        .then(function(response) {
                            console.log("Data:", response.data);
                        })
                        .catch(function(error) {
                            console.error("Error:", error);
                        })
                        .finally(function() {
                            hideLoader();
                        });
                });
            }
        });

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
        });
    </script>
</body>
</html>
