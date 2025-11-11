<?php
declare(strict_types=1);
/**
 * Header Template
 *
 * Pure HTML template for site header/navigation
 * Assets loaded in index.php
 */
?>
<header class="main-header">
    <div class="container">
        <nav class="main-nav" role="navigation">
            <div class="nav-brand">
                <a href="/" class="logo">
                    <span class="logo-icon">⚡</span>
                    <span class="logo-text">Portfolio</span>
                </a>
            </div>
            <button class="menu-toggle" aria-expanded="false">
                <span class="hamburger"></span>
            </button>
            <ul class="nav-list">
                <button class="menu-close" aria-label="Close menu">✕</button>
                <li><a href="/" class="nav-link">Home</a></li>
                <li><a href="/about" class="nav-link">About</a></li>
                <li><a href="/services" class="nav-link">Services</a></li>
                <li><a href="/contact" class="nav-link">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>
