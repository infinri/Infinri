# Manual Testing Guide

This guide provides a structured approach to manually testing the Infinri application.

## Prerequisites

- A running local development server
- Web browser (Chrome, Firefox, Safari, or Edge)
- Developer tools (F12 in most browsers)

## Quick Start

1. Start your local development server:
   ```bash
   php -S localhost:8000 -t public
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

## Testing Checklist

### Core Functionality

- [ ] **Home Page**
  - [ ] Loads without errors
  - [ ] Displays correct content
  - [ ] Navigation links work

- [ ] **Authentication**
  - [ ] Login functionality
  - [ ] Logout functionality
  - [ ] Registration (if applicable)
  - [ ] Password reset (if applicable)

- [ ] **Key Pages**
  - [ ] About page
  - [ ] Contact page
  - [ ] Any other main sections

### Responsive Testing

- [ ] Test on different screen sizes:
  - [ ] Desktop (1200px+)
  - [ ] Laptop (992px - 1199px)
  - [ ] Tablet (768px - 991px)
  - [ ] Mobile (up to 767px)

### Browser Compatibility

Test in different browsers:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari (if on Mac)
- [ ] Edge (if on Windows)

### Common Test Cases

1. **Form Submissions**
   - [ ] Required field validation
   - [ ] Input formatting (emails, phone numbers, etc.)
   - [ ] Success messages
   - [ ] Error messages

2. **Navigation**
   - [ ] All links work
   - [ ] Browser back/forward navigation
   - [ ] Active link highlighting

3. **Performance**
   - [ ] Page load times
   - [ ] Image optimization
   - [ ] JavaScript errors in console

## Testing Tools

### Browser Developer Tools

- **Chrome/Firefox DevTools**: Press F12 or right-click → Inspect
  - Elements: Inspect and modify HTML/CSS
  - Console: View JavaScript errors and logs
  - Network: Monitor API calls and load times
  - Lighthouse: Run performance audits

### Testing Extensions

- **Window Resizer**: Test responsive designs
- **Web Developer**: Various web development tools
- **Lighthouse**: Performance, accessibility, and SEO auditing

## Reporting Issues

When you find an issue, please document:

1. **Steps to Reproduce**
   - Exact steps to make the issue occur
   - Expected vs. actual results

2. **Environment**
   - Browser and version
   - Device/OS
   - Screen size (if relevant)

3. **Screenshots/Console Logs**
   - Any error messages
   - Screenshots of the issue

## Tips for Effective Testing

1. **Test Early, Test Often**
   - Don't wait until the end to start testing

2. **Be Methodical**
   - Follow the same path each time to ensure consistency

3. **Document Everything**
   - Keep track of what you've tested and what you found

4. **Test Edge Cases**
   - Try unusual inputs and scenarios
   - Test with different user roles if applicable

5. **Regression Testing**
   - When fixing one issue, verify you didn't break something else
