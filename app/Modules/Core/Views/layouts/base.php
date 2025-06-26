<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Infinri') ?> - <?= $this->e($app['name']) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    
    <!-- Preload critical assets -->
    <link rel="preload" href="/assets/css/app.css" as="style">
    <link rel="preload" href="/assets/js/app.js" as="script">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- Scripts (defer non-critical JS) -->
    <script src="/assets/js/app.js" defer></script>
    
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.13.0/dist/cdn.min.js"></script>
</head>
<body class="min-h-full bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-gray-900">
                            <?= $this->e($app['name']) ?>
                        </a>
                    </div>
                    <nav class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?= $currentPath === '/' ? 'border-blue-500 text-gray-900' : '' ?>">
                            Home
                        </a>
                        <a href="/about" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?= $currentPath === '/about' ? 'border-blue-500 text-gray-900' : '' ?>">
                            About
                        </a>
                        <a href="/services" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?= $currentPath === '/services' ? 'border-blue-500 text-gray-900' : '' ?>">
                            Services
                        </a>
                        <a href="/contact" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?= $currentPath === '/contact' ? 'border-blue-500 text-gray-900' : '' ?>">
                            Contact
                        </a>
                    </nav>
                </div>
                <div class="-mr-2 flex items-center sm:hidden">
                    <!-- Mobile menu button -->
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false" x-data="{ open: false }" @click="open = !open">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" x-bind:class="{'hidden': open, 'block': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" x-bind:class="{'block': open, 'hidden': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="sm:hidden" id="mobile-menu" x-show="open" @click.away="open = false">
            <div class="pt-2 pb-3 space-y-1">
                <a href="/" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?= $currentPath === '/' ? 'bg-blue-50 border-blue-500 text-blue-700' : '' ?>">
                    Home
                </a>
                <a href="/about" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?= $currentPath === '/about' ? 'bg-blue-50 border-blue-500 text-blue-700' : '' ?>">
                    About
                </a>
                <a href="/services" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?= $currentPath === '/services' ? 'bg-blue-50 border-blue-500 text-blue-700' : '' ?>">
                    Services
                </a>
                <a href="/contact" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?= $currentPath === '/contact' ? 'bg-blue-50 border-blue-500 text-blue-700' : '' ?>">
                    Contact
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <?= $this->section('content') ?: $this->fetch($this->content(), $data) ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 overflow-hidden sm:px-6 lg:px-8">
            <nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
                <div class="px-5 py-2">
                    <a href="/about" class="text-base text-gray-500 hover:text-gray-900">
                        About
                    </a>
                </div>
                <div class="px-5 py-2">
                    <a href="/services" class="text-base text-gray-500 hover:text-gray-900">
                        Services
                    </a>
                </div>
                <div class="px-5 py-2">
                    <a href="/contact" class="text-base text-gray-500 hover:text-gray-900">
                        Contact
                    </a>
                </div>
                <div class="px-5 py-2">
                    <a href="/privacy" class="text-base text-gray-500 hover:text-gray-900">
                        Privacy
                    </a>
                </div>
                <div class="px-5 py-2">
                    <a href="/terms" class="text-base text-gray-500 hover:text-gray-900">
                        Terms
                    </a>
                </div>
            </nav>
            <p class="mt-8 text-center text-base text-gray-400">
                &copy; <?= date('Y') ?> <?= $this->e($app['name']) ?>. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>
