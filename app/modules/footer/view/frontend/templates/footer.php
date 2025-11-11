<?php
declare(strict_types=1);
/**
 * Footer Template
 *
 * Pure HTML template for site footer
 * Assets loaded in index.php
 */
?>
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About</h3>
                <p>Professional portfolio showcasing modern PHP development.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about">About</a></li>
                    <li><a href="/services">Services</a></li>
                    <li><a href="/contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect</h3>
                <ul>
                    <li><a href="#" rel="noopener">GitHub</a></li>
                    <li><a href="#" rel="noopener">LinkedIn</a></li>
                    <li><a href="#" rel="noopener">Twitter</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Portfolio. All rights reserved.</p>
        </div>
    </div>
</footer>
