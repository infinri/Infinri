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
                <h2>Professional Developer</h2>
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
                <h2>Get In Touch</h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon icon-email"></span>
                        <a href="mailto:infinri@gmail.com">infinri@gmail.com</a>
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
                <h2>Connect & Code</h2>
                <div class="social-links">
                    <a href="https://github.com/infinri" target="_blank" rel="noopener" class="social-link">
                        <span class="social-icon icon-github"></span>
                        <span>GitHub</span>
                    </a>
                    <a href="https://www.linkedin.com/in/lucio-saldivar/" target="_blank" rel="noopener" class="social-link">
                        <span class="social-icon icon-linkedin"></span>
                        <span>LinkedIn</span>
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
