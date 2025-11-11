<?php
declare(strict_types=1);
/**
 * Footer Template
 *
 * Modern footer with professional information, contact details,
 * and technical stack showcase. No redundant navigation links.
 * 
 * Assets loaded in index.php
 */
?>
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section footer-brand">
                <h3>Professional Developer</h3>
                <p>Specializing in modern PHP development with clean architecture, secure coding practices, and scalable solutions.</p>
                <div class="footer-skills">
                    <span class="skill-tag">PHP 8.4+</span>
                    <span class="skill-tag">Modern Architecture</span>
                    <span class="skill-tag">Security First</span>
                </div>
                <div class="footer-tech">
                    <small>Built with Vanilla PHP • Modern CSS • Zero Dependencies</small>
                </div>
            </div>
            
            <div class="footer-section footer-contact">
                <h3>Get In Touch</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon icon-email"></span>
                        <a href="mailto:contact@portfolio.dev">contact@portfolio.dev</a>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon icon-briefcase"></span>
                        <span>Available for Projects</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon icon-location"></span>
                        <span>Remote & On-site</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-section footer-social">
                <h3>Connect & Code</h3>
                <div class="social-links">
                    <a href="https://github.com/infinri" target="_blank" rel="noopener" class="social-link">
                        <span class="social-icon icon-github"></span>
                        <span>GitHub</span>
                    </a>
                    <a href="https://linkedin.com/in/your-profile" target="_blank" rel="noopener" class="social-link">
                        <span class="social-icon icon-linkedin"></span>
                        <span>LinkedIn</span>
                    </a>
                    <a href="https://twitter.com/your-handle" target="_blank" rel="noopener" class="social-link">
                        <span class="social-icon icon-twitter"></span>
                        <span>Twitter</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> Infinri. Crafted with precision and modern PHP.</p>
            </div>
        </div>
    </div>
</footer>
