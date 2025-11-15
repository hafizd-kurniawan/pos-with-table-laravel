<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
            <div class="text-6xl mb-4">⏰</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Subscription Expired</h1>
            <p class="text-gray-600 mb-6">
                Your subscription has expired. Please contact support to renew your subscription and continue using the POS system.
            </p>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <div class="text-sm text-gray-600 mb-2">Contact Support:</div>
                <div class="font-semibold text-blue-600">admin@possaas.com</div>
                <div class="text-sm text-gray-600 mt-2">WhatsApp: +62 812-3456-7890</div>
            </div>
            
            <form method="POST" action="{{ route('tenantadmin.logout') }}">
                @csrf
                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition">
                    Logout
                </button>
            </form>
        </div>
    </div>
</body>
</html>
