<?php
declare(strict_types=1);
/**
 * 400 Bad Request Error Template
 */
?>

<!-- Error Hero -->
<section class="hero error-hero">
    <div class="container">
        <div class="error-content">
            <h1 class="error-code">400</h1>
            <h2 class="hero-title">Bad Request</h2>
            <p class="hero-subtitle">Your browser sent a request that this server could not understand.</p>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Go Home</a>
                <button type="button" class="btn btn-outline" id="go-back">Go Back</button>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links -->
<section class="page-section error-links">
    <div class="container">
        <h2 class="section-title">Common Issues</h2>
        
        <div class="grid grid-cols-3">
            <div class="card error-card">
                <div class="error-card-icon">🔗</div>
                <h3 class="card-title">Invalid URL</h3>
                <p class="card-body">Check the URL for typos or formatting errors</p>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">📋</div>
                <h3 class="card-title">Form Data</h3>
                <p class="card-body">Make sure all required fields are filled correctly</p>
            </div>
            
            <div class="card error-card">
                <div class="error-card-icon">🔄</div>
                <h3 class="card-title">Try Again</h3>
                <p class="card-body">Refresh the page or try your request again</p>
            </div>
        </div>
    </div>
</section>
