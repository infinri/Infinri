<?php $this->layout('layouts/base', $data) ?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6"><?= $this->e($title) ?></h1>
        
        <div class="prose max-w-none">
            <p class="text-lg text-gray-600 mb-6">
                Welcome to our company! We're dedicated to providing the best service to our customers.
                Founded in 2023, we've been helping businesses grow through innovative web solutions.
            </p>
            
            <h2 class="text-2xl font-semibold mt-8 mb-4">Our Mission</h2>
            <p class="text-gray-700 mb-6">
                Our mission is to deliver high-quality, efficient, and scalable web solutions that help
                our clients achieve their business goals. We believe in clean code, beautiful design,
                and exceptional user experiences.
            </p>
            
            <h2 class="text-2xl font-semibold mt-8 mb-4">Our Team</h2>
            <div class="grid md:grid-cols-2 gap-6 mt-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                    <h3 class="text-xl font-semibold text-center">John Doe</h3>
                    <p class="text-gray-600 text-center">Founder & CEO</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                    <h3 class="text-xl font-semibold text-center">Jane Smith</h3>
                    <p class="text-gray-600 text-center">Lead Developer</p>
                </div>
            </div>
        </div>
    </div>
</main>
