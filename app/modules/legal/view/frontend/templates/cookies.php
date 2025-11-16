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
            <p>Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and understanding how you use our site.</p>
        </section>

        <section>
            <h2>2. Types of Cookies We Use</h2>
            
            <h3>Essential Cookies</h3>
            <p>These cookies are necessary for the website to function properly. They enable core functionality such as security, network management, and accessibility.</p>

            <h3>Session Cookies</h3>
            <p>We use session cookies to maintain your session state and CSRF protection. These cookies are deleted when you close your browser.</p>

            <h3>Performance Cookies</h3>
            <p>These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.</p>
        </section>

        <section>
            <h2>3. Third-Party Cookies</h2>
            <p>We may use third-party services that set cookies on your device. These include:</p>
            <ul>
                <li>Analytics services to understand website usage</li>
                <li>Email service providers for contact form functionality</li>
            </ul>
        </section>

        <section>
            <h2>4. Managing Cookies</h2>
            <p>You can control and/or delete cookies as you wish. You can delete all cookies that are already on your computer and you can set most browsers to prevent them from being placed.</p>
            <p>However, if you do this, you may have to manually adjust some preferences every time you visit our site, and some services and functionalities may not work.</p>
        </section>

        <section>
            <h2>5. Contact Us</h2>
            <p>If you have questions about our use of cookies, please contact us at:</p>
            <p>Email: <a href="mailto:lucio.saldivar@infinri.com">lucio.saldivar@infinri.com</a></p>
        </section>
    </div>
</div>
