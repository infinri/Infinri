<?php $this->layout('layouts/base', $data) ?>

<main class="container mx-auto px-4 py-8">
    <section class="text-center py-12">
        <h1 class="text-4xl font-bold mb-4"><?= $this->e($title) ?></h1>
        <p class="text-xl text-gray-600 mb-8"><?= $this->e($description) ?></p>
        
        <div class="flex justify-center gap-4">
            <a href="/services" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                Our Services
            </a>
            <a href="/contact" class="bg-white text-blue-600 border border-blue-600 px-6 py-2 rounded hover:bg-gray-50 transition">
                Contact Us
            </a>
        </div>
    </section>

    <section class="py-8">
        <h2 class="text-2xl font-semibold mb-6">Why Choose Us</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-blue-600 text-3xl mb-4">🚀</div>
                <h3 class="text-xl font-semibold mb-2">Fast & Reliable</h3>
                <p class="text-gray-600">Lightning-fast websites with 99.9% uptime guarantee.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-blue-600 text-3xl mb-4">🎨</div>
                <h3 class="text-xl font-semibold mb-2">Beautiful Design</h3>
                <p class="text-gray-600">Custom designs that reflect your brand's identity.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-blue-600 text-3xl mb-4">🔒</div>
                <h3 class="text-xl font-semibold mb-2">Secure</h3>
                <p class="text-gray-600">Enterprise-grade security to protect your data.</p>
            </div>
        </div>
    </section>
</main>
