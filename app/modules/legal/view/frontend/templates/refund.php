<?php
declare(strict_types=1);
/**
 * Refund & Cancellation Policy
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
            <h2>1. Service Overview</h2>
            <p>This policy applies to all services offered through Infinri, including but not limited to web development, consulting, and custom software solutions.</p>
        </section>

        <section>
            <h2>2. Project Deposits</h2>
            <p>All projects require an initial deposit before work commences:</p>
            <ul>
                <li>Deposits are typically 30-50% of the total project cost</li>
                <li>Deposits are non-refundable once work has begun</li>
                <li>Deposits secure your place in the project queue</li>
            </ul>
        </section>

        <section>
            <h2>3. Cancellation Policy</h2>
            
            <h3>Client-Initiated Cancellation</h3>
            <p>If you wish to cancel a project:</p>
            <ul>
                <li><strong>Before work begins:</strong> Full refund minus 10% administrative fee</li>
                <li><strong>During development:</strong> You will be charged for work completed plus materials/licenses purchased</li>
                <li><strong>After delivery:</strong> No refunds available</li>
            </ul>

            <h3>Service Provider-Initiated Cancellation</h3>
            <p>In rare cases where we must cancel a project, you will receive a full refund of all payments made.</p>
        </section>

        <section>
            <h2>4. Refund Process</h2>
            <p>Approved refunds will be processed within 14 business days using the original payment method. Please allow additional time for your financial institution to process the refund.</p>
        </section>

        <section>
            <h2>5. Scope Changes</h2>
            <p>Significant changes to project scope may result in additional charges. We will provide a revised quote for your approval before proceeding with scope changes.</p>
        </section>

        <section>
            <h2>6. Subscription Services</h2>
            <p>For any recurring subscription services:</p>
            <ul>
                <li>Cancel anytime before the next billing cycle</li>
                <li>No refunds for partial months</li>
                <li>Access continues until the end of the paid period</li>
            </ul>
        </section>

        <section>
            <h2>7. Dispute Resolution</h2>
            <p>If you have concerns about charges or services, please contact us immediately. We aim to resolve all disputes amicably and professionally.</p>
        </section>

        <section>
            <h2>8. Contact Us</h2>
            <p>For refund requests or questions about this policy:</p>
            <p>Email: <a href="mailto:lucio.saldivar@infinri.com">lucio.saldivar@infinri.com</a></p>
        </section>
    </div>
</div>
