# Forms Module

Enterprise-grade form builder and processor with validation, persistence, and multi-step support.

---

## Feature Checklist

### Form Builder
- [ ] **Field types** - Text, email, phone, select, checkbox, radio, textarea, file
- [ ] **Field validation** - Required, email, phone, min/max, regex, custom
- [ ] **Conditional fields** - Show/hide based on other field values
- [ ] **Field groups** - Logical grouping of fields
- [ ] **Custom attributes** - CSS classes, placeholders, help text

### Form Processing
- [ ] **CSRF protection** - Token validation (use core Csrf)
- [ ] **Honeypot fields** - Spam prevention
- [ ] **Rate limiting** - Prevent abuse (use core RateLimiter)
- [ ] **File uploads** - Secure file handling
- [ ] **Data sanitization** - XSS prevention
- [ ] **Validation engine** - Use core Validator

### Cookie-Based Persistence
- [ ] **Form state saving** - Auto-save on field change
- [ ] **Draft recovery** - Restore abandoned forms
- [ ] **Multi-step progress** - Track step completion
- [ ] **Pre-fill from cookies** - Remember user preferences
- [ ] **Submission tracking** - Prevent duplicate submissions

### Cookie Implementation Guidelines
```php
// Use core cookie helpers
use function cookie;
use function cookie_get;
use function cookie_string;
use function cookie_json;
use function cookie_forget;

// Save form draft (encrypted, session-based)
$draft = [
    'name' => $request->input('name'),
    'email' => $request->input('email'),
    'step' => 2,
    'updated_at' => time(),
];
$response->withCookie(cookie('form_draft_contact', json_encode($draft), 0)); // Session

// Restore form draft
$draft = cookie_json('form_draft_contact', []);
if (!empty($draft) && ($draft['updated_at'] ?? 0) > time() - 3600) {
    // Restore form state
}

// Multi-step progress
$step = cookie_int('form_step_booking', 1, 1, 5);

// Clear on submission
$response->withCookie(cookie_forget('form_draft_contact'));
```

### Cookie Security Requirements
| Cookie | HttpOnly | Secure | SameSite | Expiration | Encrypted |
|--------|----------|--------|----------|------------|-----------|
| `form_draft_*` | Yes | Yes | Strict | Session | Yes |
| `form_step_*` | Yes | Yes | Strict | Session | No |
| `form_submitted_*` | Yes | Yes | Strict | 1 hour | No |
| `prefill_email` | Yes | Yes | Lax | 30 days | Yes |
| `prefill_name` | Yes | Yes | Lax | 30 days | Yes |

### Multi-Step Forms
- [ ] **Step navigation** - Forward/backward navigation
- [ ] **Progress indicator** - Visual step tracker
- [ ] **Step validation** - Validate before proceeding
- [ ] **Step persistence** - Cookie-based state
- [ ] **Abandonment recovery** - Resume from last step

### Form Submissions
- [ ] **Database storage** - Store submissions
- [ ] **Email notifications** - Send to admin/user
- [ ] **Webhook delivery** - POST to external URLs
- [ ] **PDF generation** - Create submission PDFs
- [ ] **Export functionality** - CSV/Excel export

### Spam Prevention
- [ ] **ReCAPTCHA integration** - Use ReCaptcha module
- [ ] **Honeypot fields** - Hidden trap fields
- [ ] **Time-based checks** - Minimum completion time
- [ ] **Rate limiting** - Per-IP limits
- [ ] **Blacklist/whitelist** - Email/IP filtering

---

## Form State Management

### Auto-Save Strategy
```php
// JavaScript triggers save on field blur/change
// POST to /api/forms/{id}/draft

// Server-side draft storage
public function saveDraft(Request $request, string $formId): Response
{
    $data = $request->only(['field1', 'field2', 'field3']);
    
    // Validate partial data
    $validator = Validator::make($data, $this->getDraftRules($formId));
    
    // Store in cookie (encrypted)
    $draft = [
        'data' => $validator->validated(),
        'step' => $request->input('step', 1),
        'updated_at' => time(),
    ];
    
    return response()
        ->json(['saved' => true])
        ->withCookie(cookie("form_draft_{$formId}", json_encode($draft), 60)); // 1 hour
}
```

### Recovery Flow
```php
// Check for existing draft on form load
public function show(string $formId): Response
{
    $draft = cookie_json("form_draft_{$formId}", []);
    $hasRecoverableDraft = !empty($draft) && ($draft['updated_at'] ?? 0) > time() - 3600;
    
    return view('forms.show', [
        'form' => $this->getForm($formId),
        'draft' => $hasRecoverableDraft ? $draft['data'] : [],
        'showRecoveryPrompt' => $hasRecoverableDraft,
    ]);
}
```

---

## Integration Points

### Events to Dispatch
- `FormSubmitted` - Successful submission
- `FormDraftSaved` - Draft auto-saved
- `FormDraftRecovered` - User restored draft
- `FormValidationFailed` - Validation errors
- `FormSpamDetected` - Spam submission blocked

### Hooks to Provide
- `beforeValidation` - Modify data before validation
- `afterValidation` - Post-validation processing
- `beforeSubmit` - Final checks before storage
- `afterSubmit` - Notifications, webhooks
- `onSpamDetected` - Custom spam handling

---

## Database Tables

- `forms` - Form definitions
- `form_fields` - Field configurations
- `form_submissions` - Submission data
- `form_files` - Uploaded files metadata

---

## Dependencies

- **Core**: Validator, Cookie helpers, CSRF, Session
- **ReCaptcha Module**: Spam protection
- **Mail Module**: Email notifications
- **Marketing Module**: Lead capture integration (optional)
