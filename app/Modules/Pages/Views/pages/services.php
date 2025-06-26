<?php $this->layout('layouts/base', $data) ?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2"><?= $this->e($title) ?></h1>
        <p class="text-lg text-gray-600 mb-8"><?= $this->e($description) ?></p>
        
        <div class="grid md:grid-cols-2 gap-8">
            <?php foreach ($services as $service): ?>
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4 text-blue-600">
                        <span class="text-2xl"><?= $this->e($service['icon']) ?></span>
                    </div>
                    <h3 class="text-xl font-semibold mb-2"><?= $this->e($service['title']) ?></h3>
                    <p class="text-gray-600"><?= $this->e($service['description']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-16 bg-blue-50 p-8 rounded-lg">
            <h2 class="text-2xl font-semibold mb-4">Ready to get started?</h2>
            <p class="text-gray-700 mb-6">
                We'd love to hear about your project and how we can help bring your ideas to life.
                Get in touch with us today for a free consultation.
            </p>
            <a href="/contact" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                Contact Us
            </a>
        </div>
    </div>
</main>
