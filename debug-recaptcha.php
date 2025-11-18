<!DOCTYPE html>
<html>
<head>
    <title>reCAPTCHA Debug Test</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0af; }
        pre { background: #000; padding: 10px; border: 1px solid #333; }
        button { padding: 10px 20px; margin: 10px; }
        #results { margin-top: 20px; }
    </style>
</head>
<body>

<h1>🔧 reCAPTCHA Debug Test</h1>

<?php
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/vendor/autoload.php';

use App\Base\Helpers\ReCaptcha;

$siteKey = ReCaptcha::getSiteKey();
$enabled = ReCaptcha::isEnabled();

echo "<div class='info'>";
echo "reCAPTCHA Enabled: " . ($enabled ? '✅ YES' : '❌ NO') . "<br>";
echo "Site Key: " . ($siteKey ? substr($siteKey, 0, 20) . '...' : '❌ EMPTY') . "<br>";
echo "</div>";

if (!$enabled || !$siteKey) {
    echo "<div class='error'>❌ reCAPTCHA not properly configured!</div>";
    exit;
}
?>

<h2>Step 1: Test reCAPTCHA Loading</h2>
<div id="step1">Loading reCAPTCHA script...</div>

<h2>Step 2: Test Token Generation</h2>
<button onclick="testToken()">Generate reCAPTCHA Token</button>
<div id="step2"></div>

<h2>Step 3: Test Form Submission</h2>
<form id="testForm" onsubmit="return testSubmit(event)">
    <input type="hidden" name="recaptcha_token" id="recaptchaToken">
    <button type="submit">Test Submit with reCAPTCHA</button>
</form>
<div id="step3"></div>

<div id="results"></div>

<!-- Load reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($siteKey); ?>"></script>

<script>
const siteKey = '<?php echo addslashes($siteKey); ?>';

// Step 1: Check if reCAPTCHA loaded
grecaptcha.ready(function() {
    document.getElementById('step1').innerHTML = '<span class="success">✅ reCAPTCHA script loaded successfully</span>';
    console.log('reCAPTCHA ready with site key:', siteKey);
});

// Step 2: Test token generation
function testToken() {
    const step2 = document.getElementById('step2');
    step2.innerHTML = '<span class="info">⏳ Generating token...</span>';
    
    grecaptcha.ready(function() {
        grecaptcha.execute(siteKey, { action: 'test' })
            .then(function(token) {
                step2.innerHTML = `<span class="success">✅ Token generated: ${token.substring(0, 50)}...</span>`;
                console.log('Generated token:', token);
            })
            .catch(function(err) {
                step2.innerHTML = `<span class="error">❌ Token generation failed: ${err}</span>`;
                console.error('reCAPTCHA error:', err);
            });
    });
}

// Step 3: Test form submission
function testSubmit(event) {
    event.preventDefault();
    
    const step3 = document.getElementById('step3');
    step3.innerHTML = '<span class="info">⏳ Testing form submission...</span>';
    
    grecaptcha.ready(function() {
        grecaptcha.execute(siteKey, { action: 'contact_form' })
            .then(function(token) {
                document.getElementById('recaptchaToken').value = token;
                step3.innerHTML = `<span class="success">✅ Form ready with token: ${token.substring(0, 50)}...</span>`;
                
                // Test actual submission to your API
                testApiSubmission(token);
            })
            .catch(function(err) {
                step3.innerHTML = `<span class="error">❌ Form submission failed: ${err}</span>`;
                console.error('reCAPTCHA error:', err);
            });
    });
    
    return false;
}

// Test API submission
function testApiSubmission(token) {
    const results = document.getElementById('results');
    results.innerHTML = '<h2>Step 4: Test API Verification</h2><span class="info">⏳ Testing API...</span>';
    
    fetch('/contact', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'name': 'Test User',
            'email': 'test@example.com',
            'subject': 'reCAPTCHA Test',
            'message': 'This is a test message',
            'service_interest': 'web_development',
            'recaptcha_token': token,
            'csrf_token': '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            results.innerHTML += '<br><span class="success">✅ API verification successful!</span>';
        } else {
            results.innerHTML += `<br><span class="error">❌ API verification failed: ${data.message || 'Unknown error'}</span>`;
        }
        console.log('API response:', data);
    })
    .catch(err => {
        results.innerHTML += `<br><span class="error">❌ API request failed: ${err}</span>`;
        console.error('API error:', err);
    });
}

// Auto-test on load
window.addEventListener('load', function() {
    setTimeout(testToken, 2000); // Auto-test token generation after 2 seconds
});
</script>

<h2>⚠️ Security Notice</h2>
<p class="warning">DELETE THIS FILE after testing!</p>

<h2>📋 Debug Info</h2>
<pre>
Site Key: <?php echo htmlspecialchars($siteKey); ?>
Enabled: <?php echo $enabled ? 'true' : 'false'; ?>
Current URL: <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>
User Agent: <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?>
</pre>

</body>
</html>
