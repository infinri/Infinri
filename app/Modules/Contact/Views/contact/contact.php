<?php $this->layout('layouts/base', $data) ?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-4"><?= $this->e($title) ?></h1>
        <p class="text-lg text-gray-600 mb-8"><?= $this->e($description) ?></p>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <p class="font-bold">Please fix the following errors:</p>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $this->e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/contact" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="name" name="name" 
                       value="<?= $this->e($formData['name'] ?? '') ?>"
                       class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['name']) ? 'border-red-500' : 'border-gray-300' ?>">
                <?php if (!empty($errors['name'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $this->e($errors['name']) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= $this->e($formData['email'] ?? '') ?>"
                       class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>">
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $this->e($errors['email']) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="message" name="message" rows="4" 
                          class="w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['message']) ? 'border-red-500' : 'border-gray-300' ?>"><?= $this->e($formData['message'] ?? '') ?></textarea>
                <?php if (!empty($errors['message'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $this->e($errors['message']) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Send Message
                </button>
            </div>
        </form>
    </div>
</main>
