<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Neema Gospel</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts: Poppins (matches the site style) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS Styles -->
    <style>
        /* Apply a font that matches the visual style of the website */
        body {
            font-family: 'Poppins', sans-serif;
            /* A linear gradient inspired by the footer in the screenshot */
            background: linear-gradient(125deg, #3c2f4f 0%, #c86e3b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }

        /* A content card with a subtle, dark, semi-transparent background */
        .content-card {
            background: rgba(20, 20, 30, 0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1.5rem; /* 24px */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Styling for the main call-to-action button */
        .btn-primary {
            background-color: #ffffff;
            color: #3c2f4f; /* Dark purple text for contrast */
            font-weight: 600;
            border-radius: 0.5rem; /* 8px */
            transition: all 0.3s ease-in-out;
            display: inline-block;
            border: 2px solid transparent;
        }

        .btn-primary:hover {
            background-color: transparent;
            color: #ffffff;
            border-color: #ffffff;
            transform: translateY(-3px);
        }
    </style>
</head>
<body class="p-4">

    <!-- Main Content Container -->
    <main class="text-center w-full max-w-md mx-auto">
        <!-- The Content Card -->
        <div class="content-card p-8 sm:p-12">

            <!-- Logo Section -->
            <div class="mb-8">
                <!-- Neema Gospel Logo from the official site -->
                <a href="/" aria-label="Homepage">
                    <img src="https://images.squarespace-cdn.com/content/v1/5e3b0b6c327c956e11516b9b/1581512809519-J459EV15V32A99ACKA5C/NEEMA+GOSPEL+CHOIR+-+LOGO+-+WHITE.png?format=1500w"
                         alt="Neema Gospel Choir Logo"
                         class="mx-auto w-40 sm:w-48"
                         onerror="this.style.display='none';">
                </a>
            </div>

            <!-- Error Message Section -->
            <div class="mb-10">
                <h1 class="text-7xl sm:text-8xl font-extrabold text-white tracking-tighter mb-4">404</h1>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-200 mb-2">Page Not Found</h2>
                <p class="text-base text-gray-300">
                    The page you requested could not be found. It might have been moved or deleted.
                </p>
            </div>

            <!-- Action Button Section -->
            <div class="mb-4">
                <a href="/" class="btn-primary py-3 px-8 shadow-lg">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Go Back Home
                </a>
            </div>

        </div>

        <!-- Footer Section -->
        <footer class="mt-8">
             <p class="text-sm text-white/60">
                &copy; <span id="current-year"></span> Neema Gospel Choir. All rights reserved.
            </p>
        </footer>
    </main>

    <!-- Simple script to display the current year in the footer -->
    <script>
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>

</body>
</html>
