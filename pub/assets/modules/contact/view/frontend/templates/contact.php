<?php
declare(strict_types=1);
/**
 * Contact Template
 *
 * Pure HTML template for contact page
 * Meta and assets loaded in index.php
 */

use App\Helpers\{Session, Esc};
?>

<!-- Contact Hero -->
<section class="page-hero contact-hero">
    <div class="container">
        <h1 class="page-title contact-title">Get In Touch</h1>
        <p class="page-subtitle contact-subtitle">
            Have a project in mind? Let's discuss how we can work together.
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="page-section contact-section">
    <div class="container">
        <div class="contact-wrapper">
            <!-- Contact Info Cards -->
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon">📧</div>
                    <h3 class="info-title">Email</h3>
                    <p class="info-text">hello@portfolio.com</p>
                    <p class="info-subtitle">Preferred method</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">⚡</div>
                    <h3 class="info-title">Response Time</h3>
                    <p class="info-text">Within 24 hours</p>
                    <p class="info-subtitle">Usually faster</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🌍</div>
                    <h3 class="info-title">Location</h3>
                    <p class="info-text">Remote Worldwide</p>
                    <p class="info-subtitle">Open to collaboration</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">💼</div>
                    <h3 class="info-title">Availability</h3>
                    <p class="info-text">Open to projects</p>
                    <p class="info-subtitle">Let's discuss</p>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <h2 class="form-title">Send a Message</h2>
                <p class="form-description">
                    Fill out the form below and I'll get back to you as soon as possible.
                </p>
                
                <form method="POST" action="/contact" data-ajax class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo Esc::html(Session::csrf()); ?>">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input"
                            required
                            placeholder="Your name"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input"
                            required
                            placeholder="your.email@example.com"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            class="form-input"
                            required
                            placeholder="What's this about?"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-textarea"
                            required
                            placeholder="Tell me about your project..."
                            rows="6"
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg form-submit">
                        <span>Send Message</span>
                        <span class="btn-icon">→</span>
                    </button>
                    
                    <p class="form-note">
                        * Required fields
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
