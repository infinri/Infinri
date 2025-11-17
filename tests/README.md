# Testing

## Manual Email Test

Test your Brevo API integration with a real email send.

### Setup

1. Configure your `.env` file with Brevo credentials:

```env
# Required
BREVO_API_KEY=xkeysib-your-actual-api-key-here
BREVO_SENDER_EMAIL=noreply@yourdomain.com
BREVO_RECIPIENT_EMAIL=your-email@example.com

# Optional
BREVO_SENDER_NAME=Infinri Portfolio
BREVO_RECIPIENT_NAME=Your Name
```

2. Get your API key from: [Brevo Dashboard → Settings → API Keys](https://app.brevo.com/settings/keys/api)

3. Verify your sender domain at: [Brevo Dashboard → Settings → Senders](https://app.brevo.com/settings/senders)

### Run Test

```bash
php tests/manual-email-test.php
```

The script will:
- ✓ Check environment variables
- ✓ Display test email details
- ✓ Ask for confirmation before sending
- ✓ Send email via Brevo API
- ✓ Provide helpful error messages if something fails

### Troubleshooting

**Sender Domain Not Verified**

1. Go to: **Settings** → **Senders & IP**
2. Add and verify your domain (or use a Brevo subdomain)
3. Use the verified email as `BREVO_SENDER_EMAIL`

**API Key Invalid**

1. Generate new key: [API Keys Settings](https://app.brevo.com/settings/keys/api)
2. Copy the full key (starts with `xkeysib-`)
3. Update `BREVO_API_KEY` in `.env`
4. Ensure key has "Transactional Emails" permission

**Class Not Found Error**

```bash
composer install --no-dev
```

This installs the `getbrevo/brevo` PHP SDK required for email sending.

## Production Testing

Once the manual test works, test the actual contact form:

1. Visit your site: `/contact`
2. Fill out the form completely
3. Submit and verify email arrives
4. Check spam folder if needed
5. Verify email formatting and reply-to address

## Why Manual Testing?

This project uses manual testing instead of unit tests because:

- **Real-world validation**: Tests actual Brevo API, not mocks
- **Immediate feedback**: See exactly what customers will see
- **Simpler maintenance**: No complex test framework setup
- **Production-ready**: If manual test works, production will work

The contact form includes comprehensive validation, security, and error handling that's been tested in real usage scenarios.
