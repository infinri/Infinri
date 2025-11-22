<?php
declare(strict_types=1);
/**
 * Cookie Policy
 */
?>
<div class="legal-document">
    <div class="legal-header">
        <div class="legal-logo">
            <img src="/assets/base/images/logo.svg" alt="Infinri" width="80" height="80">
        </div>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="last-updated">Last Updated: <?= htmlspecialchars($lastUpdated) ?></p>
        <button class="download-pdf-btn" id="download-pdf">
            <span class="download-icon">📄</span>
            Download as PDF
        </button>
    </div>

    <div class="legal-content">
        <section>
            <h2>1. What Are Cookies</h2>
            <p>Cookies are small text files that are placed on your device when you visit our website. They help us provide essential security features and core functionality.</p>
        </section>

        <section>
            <h2>2. Cookies We Use</h2>
            
            <h3>Essential Session Cookies</h3>
            <p>We use a single session cookie (PHPSESSID) to provide CSRF (Cross-Site Request Forgery) protection for the contact form. This cookie:</p>
            <ul>
                <li>Is essential for security</li>
                <li>Contains no personal information</li>
                <li>Is deleted when you close your browser</li>
                <li>Uses secure flags (HttpOnly, Secure, SameSite=Strict)</li>
            </ul>
            <p><strong>This cookie is required for the contact form to work.</strong></p>
        </section>

        <section>
            <h2>3. Third-Party Services</h2>
            
            <h3>Google reCAPTCHA v3</h3>
            <p>We use Google reCAPTCHA v3 to protect our contact form from spam and abuse. Google may set cookies to analyze whether you are a legitimate visitor.</p>
            <p>Google's use of cookies is subject to their own privacy policy: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google Privacy Policy</a></p>
            
            <h3>Email Services</h3>
            <p>We use Brevo for email delivery. Brevo does not set cookies on your device — all processing happens server-side via API.</p>
        </section>

        <section>
            <h2>4. What We Don't Use</h2>
            <p>We do <strong>not</strong> use:</p>
            <ul>
                <li>Analytics or tracking cookies</li>
                <li>Advertising cookies</li>
                <li>Social media cookies</li>
                <li>Performance monitoring cookies</li>
            </ul>
        </section>

        <section>
            <h2>5. Managing Cookies</h2>
            <p>You can control cookies through your browser settings. However, if you block our session cookie, the contact form will not function.</p>
            <p>To manage reCAPTCHA cookies, visit Google's opt-out page or adjust your browser settings.</p>
        </section>

        <section>
            <h2>6. Contact Us</h2>
            <p>If you have questions about our use of cookies, please contact us at:</p>
            <p>Email: <a href="mailto:lucio.saldivar@infinri.com">lucio.saldivar@infinri.com</a></p>
        </section>
    </div>
</div>
