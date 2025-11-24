# First Migration Target: Contact Module

**Purpose:** Document Contact module migration as golden example  
**Audience:** Developers  
**Status:** Template for all future module migrations

---

## 📐 Overview

The Contact module is our **first migration target** because:
- ✅ Simple, well-defined functionality
- ✅ Has both GET (form) and POST (submit) logic
- ✅ Requires validation
- ✅ Could benefit from database storage (optional)
- ✅ Good complexity - not too simple, not too complex

Once Contact is migrated, we use it as the template for all other modules.

---

## 🎯 Migration Goals

### What Success Looks Like

**After migration:**
- ✅ `ContactServiceProvider` registers the module
- ✅ `ContactController` handles HTTP logic
- ✅ `ContactService` handles business logic
- ✅ Database stores submissions (Phase 3+)
- ✅ Old `index.php` works as adapter (temporary)
- ✅ New route `/contact` works through new system
- ✅ Zero downtime, backward compatible

---

## 📂 Current Structure (Before)

```
app/modules/contact/
├── index.php                # Controller + view logic
├── api.php                  # Form submission handling
└── view/
    └── frontend/
        ├── css/
        │   └── contact.css
        ├── js/
        │   ├── contact.js
        │   └── recaptcha.js
        └── templates/
            └── contact.php
```

**Current flow:**
1. User visits `/contact`
2. `pub/index.php` routes to `app/modules/contact/index.php`
3. `index.php` loads meta, assets, includes template
4. POST request includes `api.php`, returns JSON

---

## 📂 Target Structure (After Phase 4)

```
app/Modules/Contact/
├── ContactServiceProvider.php   # Service provider
├── module.json                   # Module metadata
├── routes.php                    # Route definitions
├── schema.php                    # Database schema (Phase 3+)
├── Controllers/
│   └── ContactController.php     # HTTP layer
├── Services/
│   └── ContactService.php        # Business logic
├── Models/
│   └── ContactSubmission.php     # Database model (Phase 3+)
├── Requests/
│   └── ContactRequest.php        # Validation rules
├── View/
│   ├── frontend/
│   │   ├── css/
│   │   │   └── contact.css
│   │   ├── js/
│   │   │   ├── contact.js
│   │   │   └── recaptcha.js
│   │   └── templates/
│   │       └── contact.php
│   └── admin/
│       └── templates/
│           └── submissions.php   # View submissions (Phase 5)
└── Setup/
    └── Patch/
        └── Data/
            └── SeedTestSubmissions.php
```

---

## 🔄 Migration Steps

### Step 1: Create Service Provider (Phase 4)

**File:** `app/Modules/Contact/ContactServiceProvider.php`

```php
<?php

namespace App\Modules\Contact;

use App\Core\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{
    /**
     * Register module services
     */
    public function register(): void
    {
        // Bind service interface to implementation
        $this->app->singleton(
            ContactServiceInterface::class,
            ContactService::class
        );
    }
    
    /**
     * Bootstrap module
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/View', 'contact');
        
        // Load schema (Phase 3+)
        if ($this->app->bound('schema')) {
            $this->loadSchemaFrom(__DIR__ . '/schema.php');
        }
        
        // Publish assets
        $this->publishes([
            __DIR__ . '/View/frontend/css' => public_path('assets/modules/contact/css'),
            __DIR__ . '/View/frontend/js' => public_path('assets/modules/contact/js'),
        ], 'contact-assets');
    }
}
```

---

### Step 2: Create Routes (Phase 2+)

**File:** `app/Modules/Contact/routes.php`

```php
<?php

use App\Modules\Contact\Controllers\ContactController;

// Show contact form
router()->get('/contact', [ContactController::class, 'show'])
    ->name('contact.show');

// Handle form submission
router()->post('/contact', [ContactController::class, 'submit'])
    ->name('contact.submit')
    ->middleware(['throttle:60,1']); // Rate limit: 60 per minute
```

---

### Step 3: Create Controller (Phase 2+)

**File:** `app/Modules/Contact/Controllers/ContactController.php`

```php
<?php

namespace App\Modules\Contact\Controllers;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Modules\Contact\Services\ContactServiceInterface;
use App\Modules\Contact\Requests\ContactRequest;

class ContactController extends Controller
{
    public function __construct(
        private ContactServiceInterface $contactService
    ) {}
    
    /**
     * Show contact form
     */
    public function show(Request $request): Response
    {
        // Set SEO meta tags
        meta()->setTitle('Contact Us - Infinri');
        meta()->setDescription('Get in touch with our team');
        
        // Load assets
        assets()->addCss('modules/contact/css/contact.css');
        assets()->addJs('modules/contact/js/contact.js');
        assets()->addJs('modules/contact/js/recaptcha.js');
        
        // Render view
        return view('contact::frontend.templates.contact');
    }
    
    /**
     * Handle form submission
     */
    public function submit(ContactRequest $request): JsonResponse
    {
        try {
            // Validate reCAPTCHA
            if (!$this->contactService->verifyRecaptcha($request->input('recaptcha_token'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed'
                ], 422);
            }
            
            // Process submission
            $submission = $this->contactService->submit([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Send notification email
            $this->contactService->sendNotification($submission);
            
            return response()->json([
                'success' => true,
                'message' => 'Thank you! We\'ll get back to you soon.'
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }
}
```

---

### Step 4: Create Service (Phase 1+)

**File:** `app/Modules/Contact/Services/ContactService.php`

```php
<?php

namespace App\Modules\Contact\Services;

use App\Modules\Contact\Models\ContactSubmission;

class ContactService implements ContactServiceInterface
{
    public function __construct(
        private MailInterface $mail,
        private CacheInterface $cache
    ) {}
    
    /**
     * Verify reCAPTCHA token
     */
    public function verifyRecaptcha(string $token): bool
    {
        // Cache result for 5 minutes
        $cacheKey = "recaptcha:{$token}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached === 'valid';
        }
        
        // Verify with Google
        $response = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $token,
            ])
        );
        
        $result = json_decode($response, true);
        $isValid = ($result['success'] ?? false) && ($result['score'] ?? 0) >= 0.5;
        
        // Cache result
        $this->cache->put($cacheKey, $isValid ? 'valid' : 'invalid', 300);
        
        return $isValid;
    }
    
    /**
     * Submit contact form
     */
    public function submit(array $data): ContactSubmission
    {
        // Save to database (Phase 3+)
        $submission = ContactSubmission::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip_address' => $data['ip'],
            'user_agent' => $data['user_agent'],
            'status' => 'new',
        ]);
        
        return $submission;
    }
    
    /**
     * Send notification email
     */
    public function sendNotification(ContactSubmission $submission): void
    {
        $this->mail->send('contact.notification', [
            'submission' => $submission,
        ], function($message) use ($submission) {
            $message->to(env('CONTACT_EMAIL', 'hello@infinri.com'));
            $message->subject("New Contact: {$submission->subject}");
            $message->replyTo($submission->email);
        });
    }
}
```

---

### Step 5: Create Validation (Phase 4)

**File:** `app/Modules/Contact/Requests/ContactRequest.php`

```php
<?php

namespace App\Modules\Contact\Requests;

use App\Core\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'recaptcha_token' => ['required', 'string'],
        ];
    }
    
    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'subject.required' => 'Please enter a subject',
            'message.required' => 'Please enter a message',
            'message.min' => 'Message must be at least 10 characters',
        ];
    }
}
```

---

### Step 6: Create Database Schema (Phase 3)

**File:** `app/Modules/Contact/schema.php`

```php
<?php

return [
    'tables' => [
        'contact_submissions' => [
            'columns' => [
                'id' => ['type' => 'id'],
                'name' => ['type' => 'string', 'length' => 100],
                'email' => ['type' => 'string', 'length' => 255],
                'subject' => ['type' => 'string', 'length' => 200],
                'message' => ['type' => 'text'],
                'ip_address' => ['type' => 'string', 'length' => 45, 'nullable' => true],
                'user_agent' => ['type' => 'string', 'length' => 500, 'nullable' => true],
                'status' => [
                    'type' => 'enum',
                    'values' => ['new', 'read', 'replied', 'spam'],
                    'default' => 'new'
                ],
            ],
            'indexes' => [
                ['columns' => ['email']],
                ['columns' => ['status']],
                ['columns' => ['created_at']],
            ],
            'timestamps' => true,
        ],
    ],
    
    'extensions' => [
        'pg_trgm', // For fuzzy search on name/email
    ],
];
```

---

### Step 7: Create Model (Phase 3)

**File:** `app/Modules/Contact/Models/ContactSubmission.php`

```php
<?php

namespace App\Modules\Contact\Models;

use App\Core\Database\Model;

class ContactSubmission extends Model
{
    protected string $table = 'contact_submissions';
    
    protected array $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'ip_address',
        'user_agent',
        'status',
    ];
    
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }
    
    /**
     * Mark as replied
     */
    public function markAsReplied(): void
    {
        $this->update(['status' => 'replied']);
    }
    
    /**
     * Mark as spam
     */
    public function markAsSpam(): void
    {
        $this->update(['status' => 'spam']);
    }
}
```

---

### Step 8: Create Backward Compatibility Adapter

**Keep:** `app/modules/contact/index.php` (temporary)

```php
<?php

// Temporary adapter - forwards to new controller
// TODO: Remove after all modules migrated

use App\Modules\Contact\Controllers\ContactController;

// Check if new system is available
if (app()->bound(ContactController::class)) {
    // Use new controller
    $controller = app()->make(ContactController::class);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo $controller->submit(request())->getContent();
        exit;
    }
    
    echo $controller->show(request())->getContent();
    exit;
}

// Fallback to old system
// ... existing code ...
```

---

## 📋 Migration Checklist

### Phase 1-2 (No Database Yet)
- [ ] Create `ContactServiceProvider`
- [ ] Create `routes.php`
- [ ] Create `ContactController`
- [ ] Create `ContactService` (without DB)
- [ ] Create adapter in old `index.php`
- [ ] Test `/contact` works (both old and new)
- [ ] Verify assets load correctly
- [ ] Verify form submission works

### Phase 3 (Add Database)
- [ ] Create `schema.php`
- [ ] Create `ContactSubmission` model
- [ ] Update `ContactService` to use database
- [ ] Run `php bin/console schema:install`
- [ ] Test database storage
- [ ] Verify queries work

### Phase 4 (Add Validation & Features)
- [ ] Create `ContactRequest` validation
- [ ] Add rate limiting middleware
- [ ] Add spam protection
- [ ] Add email notifications
- [ ] Test all validation rules

### Phase 5+ (Admin Panel)
- [ ] Create admin view for submissions
- [ ] Add status management
- [ ] Add search/filter
- [ ] Add export functionality

---

## 🧪 Testing Strategy

### Unit Tests
```php
// tests/Unit/Modules/Contact/ContactServiceTest.php
class ContactServiceTest extends TestCase
{
    public function test_verify_recaptcha_with_valid_token()
    {
        $service = new ContactService($this->mockMail(), $this->mockCache());
        
        $this->assertTrue($service->verifyRecaptcha('valid_token'));
    }
    
    public function test_submit_saves_to_database()
    {
        $service = new ContactService($this->mockMail(), $this->mockCache());
        
        $submission = $service->submit([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test',
            'message' => 'Hello world',
            'ip' => '127.0.0.1',
            'user_agent' => 'Test',
        ]);
        
        $this->assertDatabaseHas('contact_submissions', [
            'email' => 'john@example.com',
        ]);
    }
}
```

### Integration Tests
```php
// tests/Integration/Modules/Contact/ContactFlowTest.php
class ContactFlowTest extends TestCase
{
    public function test_full_contact_submission_flow()
    {
        // Visit form
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Contact Us');
        
        // Submit form
        $response = $this->post('/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'This is a test message',
            'recaptcha_token' => 'test_token',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Verify database
        $this->assertDatabaseHas('contact_submissions', [
            'email' => 'jane@example.com',
            'status' => 'new',
        ]);
        
        // Verify email sent
        $this->assertMailSent('contact.notification');
    }
}
```

---

## 🎯 Success Criteria

**Contact module migration is complete when:**
- ✅ All tests pass
- ✅ Both old and new routes work
- ✅ Form submission stores in database
- ✅ Email notifications sent
- ✅ Zero production errors
- ✅ Documentation updated
- ✅ Code reviewed and approved

**Then use Contact as template for:**
- Legal module
- Services module
- About module
- Footer module
- Header module

---

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Status:** Migration template  
**Next:** Start Phase 1, migrate Contact in Phase 4
