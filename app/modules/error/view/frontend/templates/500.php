<?php
declare(strict_types=1);
/**
 * 500 Internal Server Error Template
 */
?>

<!-- Error Hero -->
<section class="hero error-hero">
    <div class="container">
        <div class="error-content">
            <h1 class="error-code">500</h1>
            <h2 class="hero-title">Internal Server Error</h2>
            <p class="hero-subtitle">Something went wrong on our end. We're working to fix it.</p>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Go Home</a>
                <button type="button" class="btn btn-outline" id="reload-page">Reload Page</button>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links -->
<section class="page-section error-links">
    <div class="container">
        <h2 class="section-title">What can you do?</h2>
        
        <div class="grid grid-cols-3">
            <div class="card error-card">
                <div class="error-card-icon">🔄</div>
                <h3 class="card-title">Refresh</h3>
                <p class="card-body">Try refreshing the page after a few moments</p>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">🏠</div>
                <h3 class="card-title">Go Home</h3>
                <p class="card-body">Return to the homepage and try again</p>
                <a href="/" class="btn btn-outline">Home</a>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">📧</div>
                <h3 class="card-title">Contact Us</h3>
                <p class="card-body">Let us know if the problem persists</p>
                <a href="/contact" class="btn btn-outline">Contact</a>
            </div>
        </div>
    </div>
</section>
