<?php $this->layout('layouts/base', $data) ?>

<main class="container mx-auto px-4 py-16 text-center">
    <div class="max-w-2xl mx-auto">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold mb-4"><?= $this->e($title) ?></h1>
        <p class="text-lg text-gray-600 mb-8"><?= $this->e($message) ?></p>
        
        <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
            Back to Home
        </a>
    </div>
</main>
