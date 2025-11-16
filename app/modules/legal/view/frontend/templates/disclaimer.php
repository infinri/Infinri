<?php
declare(strict_types=1);
/**
 * Portfolio Disclaimer
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
            <h2>1. Portfolio Accuracy</h2>
            <p>All projects and work samples displayed in this portfolio are presented for demonstration purposes. While we strive for accuracy, we make no representations or warranties regarding the completeness, accuracy, or reliability of any information presented.</p>
        </section>

        <section>
            <h2>2. Project Attribution</h2>
            <p>All projects displayed are either:</p>
            <ul>
                <li>Personal projects created independently</li>
                <li>Work completed for clients (with permission to display)</li>
                <li>Collaborative projects (with appropriate credit given)</li>
                <li>Sample/demonstration projects</li>
            </ul>
            <p>If you believe any content has been improperly attributed, please contact us immediately.</p>
        </section>

        <section>
            <h2>3. Code Samples</h2>
            <p>Code samples and technical demonstrations are provided "as is" without warranty of any kind. They are intended to showcase technical capabilities and should not be used in production environments without proper review and adaptation.</p>
        </section>

        <section>
            <h2>4. External Links</h2>
            <p>This portfolio may contain links to external websites or resources. We are not responsible for the content, privacy practices, or availability of these external sites.</p>
        </section>

        <section>
            <h2>5. No Professional Advice</h2>
            <p>Information presented in this portfolio should not be construed as professional advice. Any actions you take based on information from this portfolio are strictly at your own risk.</p>
        </section>

        <section>
            <h2>6. Changes and Updates</h2>
            <p>Portfolio content, including project descriptions and technical details, may be updated or removed without notice. We reserve the right to modify or discontinue any aspect of this portfolio at any time.</p>
        </section>

        <section>
            <h2>7. Contact Information</h2>
            <p>For questions regarding this disclaimer or any portfolio content, please contact:</p>
            <p>Email: <a href="mailto:lucio.saldivar@infinri.com">lucio.saldivar@infinri.com</a></p>
        </section>
    </div>
</div>
