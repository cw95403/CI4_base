# CLAUDE.md — CI4_base Development Standards

This file defines mandatory standards for all development on this project and any application built from this boilerplate. All rules are prescriptive. Follow them without exception unless a rule explicitly states conditions under which an alternative is permitted.

---

## Table of Contents

1. [Development Environment](#1-development-environment)
2. [Code Standards](#2-code-standards)
3. [Security Hardening](#3-security-hardening)
4. [Authentication & Authorization](#4-authentication--authorization)
5. [Privacy & Legal Compliance](#5-privacy--legal-compliance)
6. [Accessibility Standards](#6-accessibility-standards)
7. [UI/UX Standards](#7-uiux-standards)
8. [Date, Time & Localization](#8-date-time--localization)
9. [Internationalization & Localization](#9-internationalization--localization)
10. [Database Standards](#10-database-standards)
11. [API Standards](#11-api-standards)
12. [Performance Standards](#12-performance-standards)
13. [Logging, Auditing & Monitoring](#13-logging-auditing--monitoring)
14. [Testing Standards](#14-testing-standards)
15. [Git & Deployment](#15-git--deployment)

---

## 1. Development Environment

### Devilbox / Docker
- The development environment is **Devilbox**. The container named `php` runs MySQL, PHP, and HTTP (Apache).
- Always run PHP CLI commands inside the container: `docker exec php php spark <command>`
- Always run database operations inside the container: `docker exec php php spark migrate`
- Never run `composer`, `spark`, or `php` directly on the host machine.

### Environment Files
- Always use `.env` for environment-specific configuration. Never hardcode credentials, keys, or environment-specific values in source files.
- Never commit `.env` to version control. Confirm `.env` is in `.gitignore` before every initial project setup.
- Maintain a `.env.example` file with all required keys and placeholder values. Keep it up to date whenever new env variables are added.
- Set `CI_ENVIRONMENT = production` in all production `.env` files. Never deploy with `CI_ENVIRONMENT = development`.

### Spark Command Reference
```bash
docker exec php php spark migrate              # Run pending migrations
docker exec php php spark migrate:rollback     # Rollback last migration batch
docker exec php php spark db:seed <SeederName> # Run a specific seeder
docker exec php php spark make:controller <Name>
docker exec php php spark make:model <Name>
docker exec php php spark make:migration <Name>
docker exec php php spark cache:clear
docker exec php php spark routes               # List all registered routes
```

---

## 2. Code Standards

### PHP Style
- Always follow **PSR-12** coding style.
- Always use strict types: add `declare(strict_types=1);` at the top of every PHP file.
- Always use meaningful, descriptive names for variables, methods, and classes. Never use single-letter variable names outside of loop counters.
- Always use typed properties and method signatures (PHP 8.1+). Never omit type declarations.
- Never use `var_dump()`, `print_r()`, or `die()` in committed code.

### CodeIgniter 4 Conventions
- Always extend `BaseController` for all controllers.
- Always place business logic in **Services** or **Models**, never in controllers or views.
- Always use CI4's **Query Builder** or **Model** methods for database access. Never write raw SQL strings inline.
- Always use CI4's **Validation** library for all input validation. Never validate input manually with custom regex or conditionals alone.
- Always use CI4's **Response** object to set HTTP status codes. Never `echo` output directly in controllers.
- Always name controllers in PascalCase (e.g., `UserProfile`), models as `<Entity>Model` (e.g., `UserModel`), and views using snake_case paths (e.g., `users/profile_edit`).
- Always place route definitions in `app/Config/Routes.php`. Never use auto-routing.
- Always disable auto-routing: ensure `$routes->setAutoRoute(false)` is set.

### Comments & Documentation
- Always add a DocBlock to every class and public method.
- Always comment non-obvious logic with a plain-English explanation of *why*, not *what*.
- Never leave TODO comments in committed code. Resolve them before committing.

### Dependency Management
- Always run `composer audit` before committing changes that add or update dependencies.
- Never require packages with known high or critical vulnerabilities.
- Always pin major versions in `composer.json`. Never use `*` as a version constraint.

---

## 3. Security Hardening

### HTTP Security Headers
Always send the following HTTP headers on every response. Configure them in a `SecureHeaders` filter applied globally:

```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; object-src 'none'; frame-ancestors 'none';
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

- Always review and tighten the CSP policy for each application. The values above are the minimum baseline.
- Never send a `Server` header that reveals software version. Configure Apache to suppress it.
- Always enforce HTTPS. Redirect all HTTP requests to HTTPS at the server level.

### CSRF Protection
- Always keep CSRF protection enabled (`Config\Security`).
- Always use the `session` CSRF strategy (already configured in this boilerplate).
- Always include the CSRF meta tag in the layout `<head>` for AJAX requests.
- Never whitelist routes from CSRF protection unless they are verified webhook endpoints with signature validation.

### Input Handling
- Always validate all input server-side using CI4's Validation library, regardless of client-side validation.
- Always use parameterized queries via CI4's Query Builder. Never concatenate user input into query strings.
- Always escape output using CI4's `esc()` function when rendering user-supplied data in views.
- Never trust `$_GET`, `$_POST`, `$_COOKIE`, or `$_SERVER` values directly. Always access them through CI4's `Request` object.
- Always validate and whitelist file types, MIME types, and file sizes on upload. Never rely on the file extension alone.
- Always store uploaded files outside the web root or with execution disabled.

### Session Security
- Always regenerate the session ID on login, logout, and privilege escalation.
- Always set session cookies with `HttpOnly`, `Secure`, and `SameSite=Strict` flags.
- Always set a session timeout of no more than 30 minutes of inactivity for authenticated sessions.
- Never store sensitive data (passwords, full credit card numbers, SSNs) in session.

### Secrets & Credentials
- Always store API keys, database passwords, SMTP credentials, and encryption keys in `.env`. Never in config files committed to git.
- Always use CI4's `Encryption` library for encrypting sensitive data at rest.
- Always hash passwords using Shield's bcrypt implementation (cost 12, already configured). Never use MD5, SHA1, or unsalted hashes.

### Rate Limiting & Brute Force
- Always apply rate limiting to login, registration, password reset, and magic link endpoints.
- Always lock accounts after 5 consecutive failed login attempts (configure in Shield's `Auth` config).
- Always use CAPTCHA or equivalent challenge on public-facing forms after repeated failures.

### Dependency & Vulnerability Scanning
- Always run `composer audit` as part of the pre-deployment checklist.
- Always review Shield and framework changelogs before upgrading. Never skip patch versions for security releases.

---

## 4. Authentication & Authorization

### Shield Configuration
- Always use Shield for all authentication. Never implement a custom auth system.
- Always require email verification before allowing full account access.
- Always use the `session` authenticator as the default. Enable `tokens` only for API endpoints.
- Never expose raw token values in logs, error messages, or API responses beyond the initial creation response.

### Roles & Permissions
- Always assign every user to exactly one group at registration (default: `user`).
- Always check permissions at the controller level using Shield's `can()` or `inGroup()` helpers before performing any privileged action.
- Always use the principle of least privilege: grant only the permissions required for the task.
- Never grant `superadmin` group permissions to application-created accounts. Reserve it for system administration.
- Always define new permissions in `Config\AuthGroups` before using them in code. Never use magic permission strings not defined in config.

### Password Policy
- Always enforce the password composition validator.
- Always enforce the password dictionary validator.
- Always enforce the no-personal-info validator.
- Minimum password length: 12 characters.

---

## 5. Privacy & Legal Compliance

### General Principles (Privacy by Design)
- Always collect only the minimum personal data necessary for the feature. Never collect data "just in case."
- Always document what personal data is collected, why, where it is stored, and how long it is retained.
- Always provide users the ability to export their personal data (data portability).
- Always provide users the ability to request deletion of their account and personal data (right to erasure).
- Always anonymize or pseudonymize personal data used in testing and development. Never use real user data in non-production environments.

### GDPR (EU — General Data Protection Regulation)
- Always obtain freely given, specific, informed, and unambiguous consent before setting non-essential cookies.
- Always provide a clear privacy notice before or at the point of data collection.
- Always honor data subject rights: access, rectification, erasure, restriction, portability, and objection.
- Always implement a data breach notification process. Notify the relevant supervisory authority within 72 hours of discovering a breach.
- Always have a Data Processing Agreement (DPA) in place with any third-party service that processes EU user data.
- Never transfer EU personal data to countries outside the EU/EEA without appropriate safeguards (Standard Contractual Clauses or adequacy decision).

### CCPA / CPRA (California)
- Always include a "Do Not Sell or Share My Personal Information" link in the site footer for California users.
- Always honor opt-out requests within 15 business days.
- Always disclose categories of personal information collected and the purposes for collection in the privacy policy.

### PIPEDA (Canada)
- Always obtain meaningful consent before collecting personal information.
- Always provide individuals access to their personal information upon request.
- Always designate a privacy officer (even if it is the sole developer/owner).

### COPPA (United States)
- Never knowingly collect personal information from users under 13 years of age without verifiable parental consent.
- Always include an age gate or age verification on registration if the application could attract users under 13.
- Always delete any personal information collected from a user identified as under 13 immediately upon discovery.

### Cookie Compliance
- Always categorize cookies as: Strictly Necessary, Functional, Analytics, or Marketing.
- Always load a cookie consent banner on first visit for users from the EU, UK, and California.
- Always default non-essential cookies to disabled until consent is given.
- Never set analytics, tracking, or advertising cookies before consent is obtained.
- Always provide a way for users to withdraw consent as easily as they gave it.
- Always log consent with a timestamp, version of the privacy notice accepted, and the user's choice.

### Privacy Policy & Legal Documents
- Always maintain an up-to-date Privacy Policy accessible from every page footer.
- Always maintain Terms of Service accessible from every page footer.
- Always include an effective date and version number on Privacy Policy and Terms of Service.
- Always update the Privacy Policy when data practices change before the change takes effect.

### Data Retention
- Always define and document a retention period for each category of personal data collected.
- Always implement automated deletion or anonymization of data that has exceeded its retention period.
- Never retain personal data for longer than necessary to fulfill the purpose for which it was collected.

---

## 6. Accessibility Standards

### Target Compliance Level
- Always meet **WCAG 2.2 Level AA** as the minimum standard. This satisfies ADA, Section 508, and EN 301 549 requirements.

### Semantic HTML
- Always use the correct semantic HTML element for its purpose (`<nav>`, `<main>`, `<header>`, `<footer>`, `<button>`, `<a>`, `<table>`, etc.).
- Never use `<div>` or `<span>` for interactive elements. Always use `<button>` for actions and `<a>` for navigation.
- Always use heading levels (`<h1>`–`<h6>`) in a logical, hierarchical order. Never skip heading levels.
- Always use one `<h1>` per page.

### Forms
- Always associate every form input with a `<label>` element using `for`/`id` attributes.
- Always provide descriptive error messages that identify the field and explain what correction is needed.
- Always mark required fields with both a visual indicator and `aria-required="true"`.
- Always use `autocomplete` attributes on form fields where appropriate.
- Never rely on placeholder text alone as a label. Placeholders disappear on input and fail accessibility requirements.

### Images & Media
- Always provide meaningful `alt` text for informational images.
- Always use `alt=""` for decorative images (empty alt, not missing alt).
- Always provide captions or transcripts for video and audio content.
- Never use images of text. Always use actual text with CSS styling.

### Color & Contrast
- Always maintain a minimum contrast ratio of **4.5:1** for normal text against its background.
- Always maintain a minimum contrast ratio of **3:1** for large text (18pt or 14pt bold) and UI components.
- Never convey information using color alone. Always pair color with text, pattern, or icon.
- Always test color combinations using a contrast checker before finalizing UI components.

### Keyboard & Focus
- Always ensure every interactive element is reachable and operable via keyboard alone (Tab, Shift+Tab, Enter, Space, arrow keys).
- Always provide a visible focus indicator on all interactive elements. Never use `outline: none` without providing a custom visible alternative.
- Always implement a "Skip to main content" link as the first focusable element on every page.
- Always manage focus correctly in modals: trap focus inside while open, return focus to the trigger element on close.

### ARIA
- Always use ARIA roles, states, and properties only when a native HTML element cannot provide the necessary semantics.
- Always keep ARIA labels accurate and up to date. An incorrect ARIA label is worse than none.
- Always use `aria-live` regions for dynamic content updates (alerts, notifications, form errors).
- Never add ARIA to elements that already have the correct native semantics.

### Testing
- Always test with a screen reader (NVDA on Windows, VoiceOver on macOS/iOS) before marking a feature complete.
- Always run an automated accessibility audit (e.g., axe, Lighthouse) on every new page or component.
- Always test keyboard-only navigation on every new page or form.

---

## 7. UI/UX Standards

### Layout & Responsiveness
- Always design mobile-first. Build the mobile layout first, then use Bootstrap breakpoints to enhance for larger screens.
- Always use Bootstrap 5 grid and utility classes. Never write custom layout CSS when a Bootstrap utility achieves the same result.
- Always test layouts at 320px, 768px, 1024px, and 1440px viewport widths before considering a view complete.
- Never use fixed pixel widths for layout containers. Always use relative units or Bootstrap's grid system.

### Typography
- Always use the project's defined type scale. Never introduce arbitrary font sizes.
- Always use `rem` units for font sizes. Never use `px` for text.
- Always maintain a minimum body font size of 16px (1rem).

### Color System
- Always use the color variables defined in `public/assets/css/app.css`. Never hardcode hex values in templates or inline styles.
- Always verify new color combinations meet WCAG 2.2 AA contrast requirements before use.

### Forms
- Always display validation errors inline, adjacent to the relevant field.
- Always preserve valid user input on form submission errors. Never clear the entire form on a single field error.
- Always disable the submit button and show a loading state while a form submission is in progress.
- Always confirm destructive actions (delete, deactivate) with a second confirmation step.

### Notifications & Feedback
- Always use Bootstrap's alert component for flash messages (success, error, warning, info).
- Always make flash messages dismissible.
- Always include an appropriate ARIA role (`role="alert"` for errors/warnings, `role="status"` for success/info) on notification elements.

### Navigation
- Always indicate the current page in navigation using `aria-current="page"` and a visual active state.
- Always provide breadcrumbs on pages more than one level deep.

---

## 8. Date, Time & Localization

### Storage
- Always store all dates and times in the database as UTC.
- Always use `DATETIME` or `TIMESTAMP` column types. Never store dates as strings or Unix integers unless there is a documented technical requirement.

### Display
- Always display dates to the user in `yyyy-mm-dd` format (ISO 8601).
- Always display times in the user's local timezone. Detect timezone from browser or user profile preference.
- Always display the timezone abbreviation alongside time values where ambiguity is possible (e.g., `2026-03-19 14:30 PST`).
- Never display raw UTC times to end users without conversion.
- Never display dates in `mm/dd/yyyy` or `dd/mm/yyyy` formats. The `yyyy-mm-dd` format is unambiguous internationally and is the only permitted format.

### PHP Handling
- Always set the application default timezone to `UTC` in `app/Config/App.php` (`$appTimezone = 'UTC'`).
- Always use CI4's `Time` class (`CodeIgniter\I18n\Time`) for all date/time operations. Never use PHP's `date()` function directly.
- Always pass the user's timezone when constructing `Time` objects for display purposes.

---

## 9. Internationalization & Localization

### Language Files
- Always place all user-facing strings in CI4 language files under `app/Language/<locale>/`.
- Never hardcode English strings directly in view files. Always use `lang()` helper.
- Always use the key format `FileName.keyName` (e.g., `Auth.loginTitle`).
- Always provide an English (`en`) language file as the baseline for every language file created.

### Character Encoding
- Always use UTF-8 encoding for all files, database connections, and HTTP responses.
- Always include `<meta charset="UTF-8">` in every HTML document.
- Always set the database connection charset to `utf8mb4` and collation to `utf8mb4_unicode_ci`.

### Number & Currency Formatting
- Always use PHP's `NumberFormatter` (via the `intl` extension) for formatting numbers, currencies, and percentages for display.
- Always store currency values as integers (smallest denomination, e.g., cents) in the database. Never store floats for money.
- Never format numbers with hardcoded thousands separators or decimal characters. Always derive them from the user's locale.

### Right-to-Left (RTL) Support
- Always use Bootstrap's RTL stylesheet when the active locale is a right-to-left language (Arabic, Hebrew, Farsi, Urdu).
- Always add `dir="rtl"` to the `<html>` element for RTL locales.
- Always use logical CSS properties (`margin-inline-start` instead of `margin-left`) in custom stylesheets to support both LTR and RTL.

---

## 10. Database Standards

### Naming Conventions
- Always name tables in **lowercase snake_case**, **plural** (e.g., `user_profiles`, `audit_logs`, `password_reset_tokens`).
- Always name columns in **lowercase snake_case** (e.g., `first_name`, `is_active`, `deleted_at`).
- Always name primary keys `id`. Never use `<table>_id` as a primary key name.
- Always name foreign keys `<referenced_table_singular>_id` (e.g., `user_id`, `role_id`).
- Always name junction/pivot tables by combining the two related table names in alphabetical order, singular, separated by underscore (e.g., `permission_role`, `post_tag`).
- Always name indexes `idx_<table>_<column(s)>` (e.g., `idx_users_email`).
- Always name unique indexes `uq_<table>_<column(s)>` (e.g., `uq_users_email`).
- Always name foreign key constraints `fk_<table>_<referenced_table>` (e.g., `fk_posts_users`).
- Never use reserved SQL words as table or column names. Never use abbreviations unless the full word exceeds 64 characters.

### Column Standards
- Always define every column as `NOT NULL` unless NULL is genuinely a meaningful distinct state from an empty value.
- Always provide a sensible `DEFAULT` value for columns that are `NOT NULL` and may not be supplied on every insert.
- Always use `TINYINT(1)` for boolean columns. Name them with an `is_` or `has_` prefix (e.g., `is_active`, `has_verified_email`). Store `1` for true, `0` for false.
- Always use `VARCHAR` with an appropriate length for variable-length strings. Never use `TEXT` when the maximum length is known and bounded.
- Always use `TEXT` (or `MEDIUMTEXT`/`LONGTEXT`) only for content that is genuinely unbounded (e.g., post body, audit log details).
- Always use `DECIMAL(precision, scale)` for monetary values (e.g., `DECIMAL(19,4)`). Never use `FLOAT` or `DOUBLE` for money.
- Always use `DATETIME` for timestamps. Use `TIMESTAMP` only when automatic UTC conversion behavior is explicitly required.
- Always use `CHAR(36)` for UUID columns if UUIDs are used as identifiers, not `VARCHAR`.
- Always use `ENUM` only for values that are fixed, finite, and extremely unlikely to change. Prefer a lookup/reference table otherwise.
- Never store serialized PHP objects or JSON blobs in columns unless using MySQL's native `JSON` column type with a documented rationale.
- Never store file contents in the database. Store file paths/references only.

### Standard Columns (Required on Every Table)
Every table must include these columns in this order at the end of the column list:

```sql
`created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`deleted_at`  DATETIME     NULL     DEFAULT NULL
```

- Always use `deleted_at` for soft deletes. Never physically delete rows from tables that have audit, relational, or historical significance.
- Always configure the CI4 Model's `$useSoftDeletes = true` and `$useTimestamps = true` to match.

### Primary Keys
- Always use an auto-increment `UNSIGNED BIGINT` as the primary key for all tables: `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`.
- Never expose numeric auto-increment IDs in public URLs or API responses. Always use a separate `uuid` or `public_id` column for external references.
- Always add a `uuid` column of type `CHAR(36) NOT NULL` with a unique index on tables whose records will be referenced externally.

### Schema Design
- Always normalize to at least **3NF** (Third Normal Form). Document and justify any intentional denormalization.
- Always define foreign key constraints in migrations. Never rely on application logic alone to maintain referential integrity.
- Always set foreign key actions explicitly: use `ON DELETE RESTRICT` as the default. Use `ON DELETE CASCADE` only when child records have no meaning without the parent and this is intentional. Never use `ON DELETE SET NULL` without a documented reason.
- Always create a migration for every database schema change. Never modify the database schema manually in any environment.
- Always include `created_at`, `updated_at`, and `deleted_at` on every table (see Standard Columns above).

### Indexes
- Always add an index on every foreign key column.
- Always add an index on every column used in `WHERE`, `ORDER BY`, or `JOIN` clauses in frequent queries.
- Always add a unique index on columns that must be unique (e.g., `email`, `uuid`, `slug`).
- Never create redundant indexes (e.g., do not index the leading column of a composite index separately if the composite index already covers it).
- Always review the query execution plan (`EXPLAIN`) for any query that operates on tables exceeding 10,000 rows.

### Character Set & Collation
- Always use `utf8mb4` character set and `utf8mb4_unicode_ci` collation for all tables and string columns.
- Always set this at the database level, table level, and column level for string columns to prevent collation conflicts.

### Migrations
- Always make migrations reversible: implement both `up()` and `down()` methods.
- Always name migrations with a timestamp prefix and descriptive verb-noun name: `2026_03_19_000001_create_users_table.php`, `2026_03_19_000002_add_uuid_to_users_table.php`.
- Always run `docker exec php php spark migrate:status` to confirm migration state before and after deploying schema changes.
- Never modify an already-run migration. Always create a new migration to alter an existing table.

### Seeders
- Always use seeders only for reference/lookup data and initial admin accounts.
- Never seed production databases with test or fake data.
- Always make seeders idempotent (safe to run multiple times without duplicating data).

### Query Standards
- Always use CI4's Query Builder or Model methods. Never write raw SQL strings.
- Always use bound parameters. Never concatenate user-supplied values into query strings.
- Never use `SELECT *`. Always specify the exact columns needed.
- Always paginate queries that could return more than 100 rows. Never return unbounded result sets.
- Always add database indexes on columns used in `WHERE`, `ORDER BY`, and `JOIN` clauses.
- Always review queries with `EXPLAIN` during development for any table join or filter on a large table.

---

## 11. API Standards

### Design
- Always design APIs following REST conventions: use HTTP verbs correctly (`GET` for retrieval, `POST` for creation, `PUT`/`PATCH` for update, `DELETE` for removal).
- Always version APIs from the first release: prefix all API routes with `/api/v1/`.
- Always return JSON responses. Always set `Content-Type: application/json` on all API responses.
- Always use standard HTTP status codes: 200, 201, 204, 400, 401, 403, 404, 409, 422, 429, 500.

### Request & Response Format
- Always return a consistent JSON envelope:
  ```json
  {
    "status": "success" | "error",
    "data": {},
    "message": "Human-readable message",
    "errors": []
  }
  ```
- Always validate all API input with CI4's Validation library. Return `422` with field-level error details on validation failure.
- Never return stack traces, file paths, or internal error details in API responses. Log them server-side only.

### Authentication
- Always authenticate API requests using Shield's access tokens (`Authorization: Bearer <token>`).
- Never accept session cookies for API authentication.
- Always scope tokens to specific permissions. Never issue all-access tokens.
- Always expire tokens. Never create non-expiring tokens for production use.

### Rate Limiting
- Always apply rate limiting to all API endpoints.
- Always return `429 Too Many Requests` with a `Retry-After` header when the limit is exceeded.
- Always include rate limit headers in responses: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.

### Documentation
- Always document every API endpoint: method, path, auth requirement, request parameters, response shape, and error codes.
- Always keep API documentation in sync with the implementation. Treat outdated documentation as a bug.

---

## 12. Performance Standards

### Caching
- Always cache expensive database queries using CI4's Cache library.
- Always cache rendered page fragments that are shared across users and change infrequently.
- Always set explicit cache expiry times. Never use indefinite cache without a documented invalidation strategy.
- Always invalidate related cache entries when underlying data changes.

### Database Performance
- Always review the query log during development to identify N+1 query problems. Resolve them before merging.
- Always add indexes for every foreign key column and any column used in filtering or sorting.
- Always use pagination for any list that could exceed 100 records. Never return unbounded result sets.

### Asset Optimization
- Always minify CSS and JavaScript for production builds.
- Always version static assets (append a hash or version query string) to enable aggressive browser caching.
- Always serve images in modern formats (WebP) with fallbacks. Always specify `width` and `height` attributes on `<img>` tags to prevent layout shift.
- Always use lazy loading (`loading="lazy"`) on images below the fold.

---

## 13. Logging, Auditing & Monitoring

### Application Logging
- Always use CI4's `log_message()` function. Never write to log files directly.
- Always log at the appropriate level: `error` for exceptions and failures, `warning` for unexpected-but-handled states, `info` for significant application events, `debug` for development diagnostics only.
- Never log passwords, tokens, full credit card numbers, SSNs, or any other sensitive data.
- Never expose log output to end users.

### Audit Logging
- Always create an audit log entry for the following events: user login, logout, failed login, password change, account creation, account deletion, role/permission change, and any change to sensitive data.
- Always include in each audit log entry: timestamp (UTC), user ID, IP address, action performed, affected resource, and before/after values where applicable.
- Always store audit logs in a dedicated database table. Never store them only in flat log files.
- Never allow audit log entries to be deleted or modified by the application. Audit logs are append-only.

### Error Handling
- Always use CI4's exception handling. Never suppress exceptions with empty `catch` blocks.
- Always show a user-friendly error page in production. Never display CI4's debug error page outside of development.
- Always log the full exception (message, stack trace, request context) server-side when an unhandled exception occurs.
- Always return appropriate HTTP status codes for error conditions. Never return `200 OK` with an error payload.

### Health Monitoring
- Always implement a `/health` endpoint that returns the application's operational status, database connectivity, and cache connectivity. Return `200` when healthy, `503` when degraded.
- Never expose sensitive configuration, version details, or infrastructure information in the health endpoint response.

---

## 14. Testing Standards

### Coverage Requirements
- Always write unit tests for every Service class method.
- Always write feature tests for every controller action (happy path and error cases).
- Always write tests for every authentication and authorization flow.
- Always write tests for every form validation rule.

### Test Data
- Always use Faker to generate test data. Never use real names, emails, phone numbers, or addresses in test fixtures.
- Always use database transactions or a test database that is reset between test runs. Never run tests against a production or shared development database.
- Always seed only the minimum data required for each test. Never rely on test execution order.

### Test Standards
- Always follow AAA structure: Arrange, Act, Assert.
- Always test one behavior per test method.
- Always name test methods descriptively: `testLoginFailsWithInvalidPassword()`.
- Never mock the database for integration tests. Always test against a real database connection.
- Always run the full test suite before pushing to the repository: `docker exec php php vendor/bin/phpunit`

---

## 15. Git & Deployment

### Branching
- Always create a feature branch for every change: `feature/<short-description>`.
- Always create a hotfix branch for urgent fixes: `hotfix/<short-description>`.
- Always merge into `main` only after the feature is complete, tested, and reviewed.
- Never commit directly to `main`.

### Commit Messages
- Always write commit messages in the imperative mood: "Add user export feature", not "Added" or "Adding".
- Always include a short summary (50 characters or fewer) as the first line.
- Always include a blank line followed by a detailed description for non-trivial changes.
- Never commit commented-out code, debug statements, or unresolved merge conflict markers.

### Pre-Deployment Checklist
- [ ] `CI_ENVIRONMENT` is set to `production` in `.env`
- [ ] Debug toolbar is disabled
- [ ] `.env` is not committed and not web-accessible
- [ ] `composer audit` shows no high or critical vulnerabilities
- [ ] All migrations have been run
- [ ] All tests pass
- [ ] HTTPS is enforced and HTTP redirects to HTTPS
- [ ] Security headers are returning correctly (verify with securityheaders.com)
- [ ] Cookie consent is functioning correctly
- [ ] Error pages return the correct HTTP status codes (not 200)
- [ ] Log directory is writable and outside the web root
- [ ] `writable/` directory is not web-accessible
- [ ] No hardcoded credentials in any committed file

---

*This document is a living standard. Update it when new patterns are established, new legal requirements apply, or existing guidance proves insufficient.*
