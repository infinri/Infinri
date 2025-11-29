# Marketing Module

Enterprise-grade marketing and analytics features for lead generation, tracking, and conversion optimization.

---

## Feature Checklist

### Lead Generation
- [ ] **Contact form integration** - Capture leads from forms
- [ ] **Email capture popups** - Exit intent, timed, scroll-triggered
- [ ] **Lead scoring** - Assign scores based on behavior
- [ ] **Lead source tracking** - UTM parameters, referrers
- [ ] **CRM integration hooks** - Export to external CRMs

### Cookie-Based Tracking
- [ ] **Visitor identification** - Anonymous visitor ID cookie
- [ ] **Session tracking** - Track sessions across pages
- [ ] **Return visitor detection** - Recognize returning visitors
- [ ] **Campaign attribution** - Store UTM params in cookies
- [ ] **Last visited page** - Track navigation for personalization
- [ ] **Referrer storage** - First-touch attribution

### Cookie Implementation Guidelines
```php
// Use core cookie helpers with secure defaults
use function cookie;
use function cookie_get;
use function cookie_string;
use function cookie_int;
use function cookie_json;

// Visitor ID (long-lived, anonymous)
$visitorId = cookie_string('visitor_id', '');
if (!$visitorId) {
    $visitorId = bin2hex(random_bytes(16));
    $response->withCookie(cookie('visitor_id', $visitorId, 60 * 24 * 365)); // 1 year
}

// UTM tracking (session-based)
$utm = cookie_json('utm_params', []);

// Page visit count
$visits = cookie_int('page_visits', 0, 0, 10000);
```

### Cookie Security Requirements
| Cookie | HttpOnly | Secure | SameSite | Expiration | Encrypted |
|--------|----------|--------|----------|------------|-----------|
| `visitor_id` | Yes | Yes | Lax | 1 year | No (anonymous) |
| `utm_params` | Yes | Yes | Lax | Session | No |
| `last_page` | Yes | Yes | Lax | 30 days | No |
| `referrer` | Yes | Yes | Lax | 30 days | No |
| `lead_score` | Yes | Yes | Lax | 30 days | Yes |

### Analytics
- [ ] **Page view tracking** - Internal analytics
- [ ] **Event tracking** - Custom events (clicks, scrolls, etc.)
- [ ] **Conversion tracking** - Goal completions
- [ ] **Funnel analysis** - Multi-step conversion tracking
- [ ] **A/B test integration** - Variant tracking

### Email Marketing
- [ ] **Newsletter signup** - Double opt-in flow
- [ ] **Email preferences** - Subscription management
- [ ] **Unsubscribe handling** - One-click unsubscribe
- [ ] **Email tracking pixels** - Open tracking
- [ ] **Click tracking** - Link click analytics

### SEO Tools
- [ ] **Sitemap generation** - XML sitemaps
- [ ] **Meta tag management** - Dynamic meta tags
- [ ] **Schema.org markup** - Structured data
- [ ] **Canonical URLs** - Duplicate content handling
- [ ] **Robots.txt management** - Crawler directives

### Social Media
- [ ] **Share buttons** - Social sharing widgets
- [ ] **Open Graph tags** - Facebook/LinkedIn previews
- [ ] **Twitter Cards** - Twitter previews
- [ ] **Social login hooks** - OAuth integration points

---

## Privacy Compliance

### GDPR Requirements
- [ ] Cookie consent banner integration
- [ ] Granular consent categories (necessary, analytics, marketing)
- [ ] Consent storage and audit trail
- [ ] Right to erasure (cookie deletion)
- [ ] Data export capability

### Cookie Consent Categories
```php
// Check consent before setting marketing cookies
if (hasConsent('marketing')) {
    $response->withCookie(cookie('visitor_id', $id, 60 * 24 * 365));
}

// Analytics cookies require analytics consent
if (hasConsent('analytics')) {
    $response->withCookie(cookie('page_visits', $count, 60 * 24 * 30));
}
```

---

## Integration Points

### Events to Dispatch
- `LeadCaptured` - New lead created
- `LeadScoreUpdated` - Score changed
- `ConversionTracked` - Goal completed
- `NewsletterSubscribed` - Email opt-in
- `NewsletterUnsubscribed` - Email opt-out

### Hooks to Provide
- `beforeLeadCapture` - Validate/modify lead data
- `afterLeadCapture` - Post-processing, notifications
- `onPageView` - Analytics tracking
- `onConversion` - Conversion processing

---

## Database Tables

- `leads` - Lead information
- `lead_scores` - Score history
- `lead_sources` - Attribution data
- `conversions` - Conversion events
- `newsletter_subscribers` - Email list
- `analytics_events` - Event log (optional, Redis preferred)

---

## Dependencies

- **Core**: Cookie helpers, Session, Cache
- **Mail Module**: Email sending
- **ReCaptcha Module**: Form spam protection
