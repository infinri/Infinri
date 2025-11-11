<?php
declare(strict_types=1);
/**
 * About Template
 *
 * Pure HTML template for about page
 * Meta and assets loaded in index.php
 */
?>

<!-- About Hero Section -->
<section class="page-hero about-hero">
    <div class="container">
        <div class="about-hero-content">
            <div class="about-profile">
                <div class="profile-image-wrapper">
                    <!-- Replace with your actual image path -->
                    <img src="/assets/profile.jpg" alt="Profile" class="profile-image" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="profile-placeholder" style="display: none;">
                        <span style="font-size: 4rem;">👨‍💻</span>
                    </div>
                </div>
            </div>
            
            <div class="about-intro">
                <h1 class="about-title">About Me</h1>
                <h2 class="about-subtitle">Professional PHP Developer</h2>
                <p class="about-description">
                    Specializing in modern, tested, and secure web applications with a focus 
                    on clean architecture and best practices. Passionate about writing code 
                    that is maintainable, scalable, and delightful to work with.
                </p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-value">98%</div>
                        <div class="stat-label">Test Coverage</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">Level 6</div>
                        <div class="stat-label">PHPStan</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">PSR-12</div>
                        <div class="stat-label">Compliant</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Skills Section -->
<section class="page-section about-skills">
    <div class="container">
        <h2 class="section-title">Skills & Expertise</h2>
        
        <div class="skills-grid">
            <div class="skill-card">
                <div class="skill-icon">💻</div>
                <h3 class="skill-title">Backend Development</h3>
                <ul class="skill-list">
                    <li>PHP 8.1+ with strict types</li>
                    <li>PSR standards compliance</li>
                    <li>Modern architecture patterns</li>
                    <li>Database design & optimization</li>
                    <li>RESTful API development</li>
                </ul>
            </div>
            
            <div class="skill-card">
                <div class="skill-icon">🧪</div>
                <h3 class="skill-title">Quality & Testing</h3>
                <ul class="skill-list">
                    <li>98%+ test coverage with Pest</li>
                    <li>PHPStan Level 6 analysis</li>
                    <li>Security-first approach</li>
                    <li>Continuous integration</li>
                    <li>Code review & refactoring</li>
                </ul>
            </div>
            
            <div class="skill-card">
                <div class="skill-icon">🏗️</div>
                <h3 class="skill-title">Architecture</h3>
                <ul class="skill-list">
                    <li>SOLID principles</li>
                    <li>Design patterns</li>
                    <li>Scalable systems</li>
                    <li>Performance optimization</li>
                    <li>Security best practices</li>
                </ul>
            </div>
            
            <div class="skill-card">
                <div class="skill-icon">🚀</div>
                <h3 class="skill-title">Tools & DevOps</h3>
                <ul class="skill-list">
                    <li>Git version control</li>
                    <li>Composer dependency management</li>
                    <li>CI/CD pipelines</li>
                    <li>Docker containerization</li>
                    <li>Linux server administration</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Approach Section -->
<section class="about-approach">
    <div class="container">
        <h2 class="section-title">My Approach</h2>
        <p class="approach-intro">
            I believe in writing code that stands the test of time. Every project I work on 
            follows these core principles:
        </p>
        
        <div class="approach-grid">
            <div class="approach-item">
                <div class="approach-number">01</div>
                <h3 class="approach-title">Tested</h3>
                <p class="approach-text">
                    Comprehensive test coverage ensures reliability and confidence in every deployment.
                </p>
            </div>
            
            <div class="approach-item">
                <div class="approach-number">02</div>
                <h3 class="approach-title">Secure</h3>
                <p class="approach-text">
                    Security is built in from the ground up, not bolted on as an afterthought.
                </p>
            </div>
            
            <div class="approach-item">
                <div class="approach-number">03</div>
                <h3 class="approach-title">Maintainable</h3>
                <p class="approach-text">
                    Clean, documented code that your team will understand and enjoy working with.
                </p>
            </div>
            
            <div class="approach-item">
                <div class="approach-number">04</div>
                <h3 class="approach-title">Performant</h3>
                <p class="approach-text">
                    Optimized for speed and efficiency without sacrificing code quality.
                </p>
            </div>
        </div>
    </div>
</section>
