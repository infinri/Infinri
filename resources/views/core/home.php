<?php $this->layout('layouts/default', ['title' => $title ?? 'Home']); ?>

<div class="container mx-auto px-4 py-8">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4"><?= $this->e($title ?? 'Welcome to ' . $app['name']) ?></h1>
        <p class="text-xl text-gray-600 mb-8"><?= $this->e($description ?? 'A modern PHP application built with Slim Framework and Plates') ?></p>
        
        <div class="mt-8">
            <a href="/about" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                Learn More
            </a>
            <a href="/contact" class="ml-4 inline-block bg-white hover:bg-gray-100 text-blue-600 font-bold py-3 px-6 border border-blue-600 rounded-lg transition duration-200">
                Contact Us
            </a>
        </div>
    </div>

    <div class="mt-16 grid md:grid-cols-3 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="text-blue-600 text-4xl mb-4">
                <i class="fas fa-bolt"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Fast Performance</h3>
            <p class="text-gray-600">Optimized for speed and efficiency to deliver the best user experience.</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="text-blue-600 text-4xl mb-4">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Secure by Default</h3>
            <p class="text-gray-600">Built with security best practices in mind to protect your data.</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="text-blue-600 text-4xl mb-4">
                <i class="fas fa-arrows-alt"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Scalable</h3>
            <p class="text-gray-600">Designed to grow with your business needs and handle increased load.</p>
        </div>
    </div>
</div>
