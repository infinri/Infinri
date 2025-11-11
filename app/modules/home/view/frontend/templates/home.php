<?php
declare(strict_types=1);
/**
 * Home Template
 *
 * Pure HTML template for home page
 * Meta and assets loaded in index.php
 */
?>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">Welcome to My Portfolio</h1>
        <p class="hero-subtitle">Professional PHP Developer specializing in modern, tested, secure applications</p>
        <div class="hero-buttons">
            <a href="/about" class="btn btn-primary">Learn More</a>
            <a href="/contact" class="btn btn-outline">Get in Touch</a>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <h2 class="features-title">Features</h2>
        
        <div class="grid grid-cols-3">
            <div class="card">
                <h3 class="card-title">Modern PHP</h3>
                <p class="card-body">Built with PHP 8.1+, strict types, and PSR-12 standards</p>
            </div>
            
            <div class="card">
                <h3 class="card-title">High Quality</h3>
                <p class="card-body">98.4% test coverage, PHPStan Level 6, zero errors</p>
            </div>
            
            <div class="card">
                <h3 class="card-title">Secure</h3>
                <p class="card-body">CSRF protection, XSS prevention, strict CSP headers</p>
            </div>
        </div>
    </div>
</section>
