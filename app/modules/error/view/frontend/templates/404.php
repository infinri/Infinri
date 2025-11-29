<?php
declare(strict_types=1);
/**
 * Error Template
 *
 * Pure HTML template for 404 error page
 * Meta and assets loaded in index.php
 */
?>
<main id="page-error" class="page page-404">

<!-- Error Hero -->
<section class="hero error-hero">
    <div class="container">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <h2 class="hero-title">Page Not Found</h2>
            <p class="hero-subtitle">The page you're looking for doesn't exist or has been moved.</p>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Go Home</a>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links -->
<section class="page-section error-links">
    <div class="container">
        <h2 class="section-title">Where would you like to go?</h2>
        
        <div class="grid grid-cols-3">
            <div class="card error-card">
                <div class="error-card-icon">🏠</div>
                <h3 class="card-title">Home</h3>
                <p class="card-body">Return to the homepage</p>
                <a href="/" class="btn btn-outline">Home</a>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">👤</div>
                <h3 class="card-title">About</h3>
                <p class="card-body">Learn more about me</p>
                <a href="/about" class="btn btn-outline">About</a>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">📧</div>
                <h3 class="card-title">Contact</h3>
                <p class="card-body">Get in touch</p>
                <a href="/contact" class="btn btn-outline">Contact</a>
            </div>
        </div>
    </div>
</section>

</main>
