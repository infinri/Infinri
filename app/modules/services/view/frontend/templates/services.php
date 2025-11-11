<?php
declare(strict_types=1);
/**
 * Services Template
 *
 * Pure HTML template for services page
 * Meta and assets loaded in index.php
 */
?>

<!-- Services Hero -->
<section class="page-hero services-hero">
    <div class="container">
        <h1 class="page-title services-title">Services</h1>
        <p class="page-subtitle services-subtitle">
            Professional development services with a focus on quality, security, and maintainability
        </p>
    </div>
</section>

<!-- Services Grid -->
<section class="page-section services-section">
    <div class="container">
        <div class="services-grid">
            <!-- Service 1 -->
            <div class="service-card">
                <div class="service-icon">🚀</div>
                <h3 class="service-title">Web Application Development</h3>
                <p class="service-description">
                    Custom web applications built with modern PHP, following PSR standards 
                    and best practices for scalable, maintainable code.
                </p>
                <ul class="service-features">
                    <li>Modern PHP 8.1+ architecture</li>
                    <li>Secure by design approach</li>
                    <li>Comprehensive test coverage</li>
                    <li>Performance optimized</li>
                    <li>PSR standards compliance</li>
                </ul>
                <div class="service-badge">Most Popular</div>
            </div>
            
            <!-- Service 2 -->
            <div class="service-card">
                <div class="service-icon">⚡</div>
                <h3 class="service-title">API Development</h3>
                <p class="service-description">
                    RESTful APIs designed for scalability, security, and exceptional 
                    developer experience with comprehensive documentation.
                </p>
                <ul class="service-features">
                    <li>RESTful design principles</li>
                    <li>OAuth & JWT authentication</li>
                    <li>OpenAPI documentation</li>
                    <li>Rate limiting & throttling</li>
                    <li>Versioning strategy</li>
                </ul>
            </div>
            
            <!-- Service 3 -->
            <div class="service-card">
                <div class="service-icon">🔍</div>
                <h3 class="service-title">Code Review & Consulting</h3>
                <p class="service-description">
                    Expert code review and architectural consulting to improve code quality, 
                    security, and performance of existing applications.
                </p>
                <ul class="service-features">
                    <li>Security vulnerability audit</li>
                    <li>Performance profiling</li>
                    <li>Code quality assessment</li>
                    <li>Architecture recommendations</li>
                    <li>Best practices guidance</li>
                </ul>
            </div>
            
            <!-- Service 4 -->
            <div class="service-card">
                <div class="service-icon">🧪</div>
                <h3 class="service-title">Testing & Quality Assurance</h3>
                <p class="service-description">
                    Comprehensive testing strategy implementation to ensure reliability, 
                    maintainability, and confidence in your codebase.
                </p>
                <ul class="service-features">
                    <li>Unit & integration tests (Pest/PHPUnit)</li>
                    <li>PHPStan static analysis setup</li>
                    <li>CI/CD pipeline configuration</li>
                    <li>Test coverage improvement</li>
                    <li>Mutation testing</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="services-cta">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Start Your Project?</h2>
            <p class="cta-text">
                Let's discuss how I can help bring your ideas to life with clean, 
                tested, and maintainable code.
            </p>
            <div class="cta-buttons">
                <a href="/contact" class="btn btn-primary btn-lg">Get Started</a>
                <a href="/about" class="btn btn-outline btn-lg">Learn More</a>
            </div>
        </div>
    </div>
</section>
