# Project instructions

This is a CodeIgniter 3 application being customised from an existing tour-operator CMS
(Alam Al-Munawara) into the CMS and website for **Blossom Ewa Mazur**, a manicure, nail art
and beauty studio in Gorlice, Poland.

Follow `PROJECT_PLAN.md` for the migration. Work phase by phase and keep each phase in a
working, committable state. When a task and the plan disagree, the user's request wins;
mention the difference.

Preserve the current business logic, database behavior, routes, integrations, and frontend behavior unless the requested task (or the current plan phase) specifically requires changing them.

Review the existing implementation before making changes. Prefer extending existing reusable patterns over creating duplicate implementations.


# Project scope

- **English only.** The CMS and the website have one language. Do not add locales, `/en` or
  `/ar` URL prefixes, `_ar` columns, RTL styles, language switchers, or translation UI.
- **No tours.** Tours, tour categories/images/itineraries/slots/languages, tour guides and
  their availability, tour reviews, attractions, vehicles, tour bookings, payments, discount
  codes, referrals, tour reports and Plan Your Visit were removed in Phase 2. Do not
  reintroduce them. Their code is readable only from the baseline git tag, as a structural
  reference (see "Manage/admin module standards").
- **Public site.** The Blossom frontend is rendered by `Frontend.php` through
  `libraries/Frontend_layout.php` (see "Frontend layout"). While Website Settings > Under
  Construction is "Yes", visitors get `views/frontend/under_construction.php` (503) and
  signed-in administrators see the real site; robots.txt blocks crawlers and the sitemap is
  hidden. It stays "Yes" locally until launch.
- **Removed integrations.** Do not reintroduce Google Cloud Translation, Google Places, the
  WhatsApp Cloud API, or Moyasar payments.
- **Kept integrations.** reCAPTCHA Enterprise (contact and booking-request forms) and SMTP
  email through `EmailService`, unless `PROJECT_PLAN.md` decision D4 changes.
- **Locale data.** Currency is Polish złoty, displayed as `zł` after the amount
  (for example `80 zł`). Timezone is `Europe/Warsaw`.
- **Salon domain.** The core entities are services and service categories, artists (team),
  gallery images and categories, offers, appointment requests, testimonials, journal articles
  (blogs), FAQs and pages.


# Reference frontend (`/ci3/`)

`/ci3/` contains the static design export of the new website. It is a **reference only**:

- Never deploy it, route to it, or load assets from it at runtime.
- Its views are minified, single-line HTML documents with duplicated headers/footers,
  hard-coded content and `<?= ?>` short tags. Never copy them verbatim. Rebuild them as
  formatted views and shared partials under `application/views/frontend/`, using
  `<?php echo` and escaped CMS data.
- Match its rendered markup, classes, spacing and behaviour when rebuilding a page. Compare
  the rebuilt page against the `/ci3/` page at desktop and mobile widths.
- Its forms, booking wizard, login/registration and account pages are mock-ups. Their real
  behaviour is defined by `PROJECT_PLAN.md`, not by the mock-up.
- `/ci3/vendor/` and `/ci3/assets/fontawesome-download/` are not needed and must not be copied.
- `/ci3/` is deleted at the end of the migration.


# Secrets and configuration

Never commit credentials, API keys, tokens, SMTP passwords or webhook secrets.

Keep environment-specific values in `application/config/<ENVIRONMENT>/constants.php`
(`development`, `staging`, `production` — all gitignored), not in
`application/config/constants.php`. CodeIgniter loads the environment file first, so
`application/config/constants.php` declares every secret with `defined('X') OR define('X', '')`
as an empty fallback. `ENVIRONMENT` comes from `CI_ENV` (set in `.htaccess_prod`) and defaults
to `development`.

- Database connection: `DB_HOSTNAME`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
  (read by `config/database.php`).
- Encryption key: `APP_ENCRYPTION_KEY` (read by `config/config.php`).
- Email, SMTP and reCAPTCHA values as listed in the template.

`application/config/secrets.example.php` is the committed template listing every value; it is
never loaded. When adding a new secret, add its empty fallback to `constants.php` and its entry
to the template in the same change.

The local development database is `blossom_cms`. `ci_cms` is the untouched copy of the old
Alam database; do not modify it.

Never reuse credentials inherited from the previous (Alam) project.


# Local testing

Use http://ctech-cms.com/ on the local PC for testing when needed.

The admin area lives at `/manage` and requires a sign-in.

Local-only test account:

- Username: `codex`
- Password: `codex123`

These credentials are for the local development instance only.

Never reuse, expose, copy, or assume these credentials for staging or production environments.


# Scope discipline

Follow the user's requested scope strictly.

Do not perform unrelated:

- refactoring
- architecture changes
- UI redesigns
- database changes
- route changes
- dependency changes
- module migrations
- naming changes
- business logic changes

unless they are required for the requested task.

The canonical CRUD module (see "Manage/admin module standards") is the standard for new manage/admin modules and for existing modules when the requested work involves their structure, architecture, forms, listings, or UI.

A small bug fix, formatting task, content change, validation adjustment, or isolated behavior change does not by itself require rewriting the entire module to match the canonical module.

When performing a formatting-only task:

- Change formatting only.
- Do not refactor working code.
- Do not rename methods, variables, classes, routes, fields, or database columns.
- Do not optimize or rewrite business logic.
- Do not alter queries unless required to fix a syntax issue introduced by formatting.
- Preserve existing behavior exactly.

Do not expand the task beyond what the user requested simply because other improvements are possible.


# Code style and formatting

Keep all source code human-readable.

Never generate, preserve, or intentionally introduce minified or compressed application code.

## PHP formatting

Use clean PSR-12-style formatting where practical while preserving:

- CodeIgniter 3 compatibility
- existing project naming conventions
- existing framework behavior
- existing business logic

Use the following standards:

- Use 4 spaces for indentation.
- Do not use tabs for indentation.
- Write one statement per line.
- Never place multiple methods or functions on the same line.
- Separate class methods with a blank line.
- Keep opening and closing braces consistently formatted.
- Keep method bodies properly indented.
- Keep nested conditions properly indented.
- Avoid unnecessarily dense one-line expressions.
- Do not use PHP short tags. Use `<?php` and `<?php echo` instead of `<?` and `<?= ` in controllers, models, helpers, libraries, and views.

Format these structures across normal readable lines:

- `if`
- `elseif`
- `else`
- `foreach`
- `for`
- `while`
- `switch`
- `try`
- `catch`
- anonymous functions
- function declarations
- method declarations

Do not write code like:

```php
private function clean($v,$max=NULL){$v=trim($v);return $max?$this->truncate($v,$max):$v;}private function display($v){return html_entity_decode($v);}
```

Use readable formatting instead:

```php
private function clean($value, $max = null)
{
    $value = trim($value);

    return $max
        ? $this->truncate($value, $max)
        : $value;
}

private function display($value)
{
    return html_entity_decode(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
```

Break long code into multiple lines when this improves readability, including:

- arrays
- argument lists
- method calls
- chained calls
- query-builder expressions
- ternary expressions
- condition groups
- long strings with concatenation

Do not force short, naturally readable expressions onto unnecessary multiple lines.

Use readable multiline arrays when an array contains several items or long values.

Example:

```php
private function colorFields()
{
    return [
        'banner_background_color_1',
        'banner_background_color_2',
        'banner_title_color_1',
        'banner_title_color_2',
        'banner_heading_color_1',
        'banner_heading_color_2',
        'banner_text_color_1',
        'banner_text_color_2',
    ];
}
```

Do not compress helper methods into one-line implementations.

Preserve existing comments.

Do not remove useful comments merely as part of formatting.

Formatting must never change business logic.


## CodeIgniter views

Keep mixed PHP and HTML templates readable and consistently indented.

For views:

- Indent nested HTML normally.
- Indent PHP inside HTML according to the surrounding structure.
- Format PHP conditions and loops across readable lines.
- Do not combine unrelated PHP statements onto the same line.
- Keep HTML attributes readable.
- Avoid unnecessarily long lines.
- Preserve the rendered markup and existing behavior.
- Do not alter Bootstrap classes or frontend layout merely for formatting.
- Do not change JavaScript behavior unless specifically requested.

When touching an existing file that contains compressed or poorly formatted code, format the affected code according to these standards unless the task explicitly requires preserving its exact formatting.


# Reusable code

Before creating a new helper, library, partial, utility, JavaScript function, component, validation rule, or UI pattern, check whether an equivalent implementation already exists.

Prefer:

- reusable functions
- shared CodeIgniter libraries
- shared helpers
- shared admin partials
- reusable JavaScript modules
- reusable CSS classes

Avoid:

- duplicate helper logic
- duplicate upload implementations
- duplicate validation code
- duplicate modal implementations
- duplicate AJAX patterns
- duplicate field components
- page-specific code when a shared implementation already exists

Do not introduce unnecessary abstractions for simple one-off logic.


# Existing business logic

Preserve current business rules unless the requested task explicitly changes them.

Removing tour, bookings, payments, translation, Google Places and WhatsApp code is expected
work under `PROJECT_PLAN.md`; do it only in the phase (or task) that calls for it, and remove
each feature completely (routes, navigation, constants, views, JS, short tags) rather than
leaving dead references.

Do not casually modify:

- appointment request logic
- service pricing and durations
- offer pricing and validity
- artist/service assignments
- status transitions
- email behavior
- database relationships
- file handling
- authentication
- admin permissions

When changing existing functionality, first understand how the current controller, model, views, JavaScript, database tables, and related services interact.


# Database changes

Do not change the database schema unless required by the requested task.

When a database change is required:

- Keep existing data compatibility in mind.
- Avoid destructive changes unless explicitly requested.
- Use appropriate data types.
- Preserve existing indexes and relationships where possible.
- Validate assumptions against the current schema before writing queries.

If the user asks for a feature that requires a schema change, implement the code and provide the required SQL query separately at the end unless instructed otherwise.

Do not silently execute destructive schema modifications.


# CodeIgniter 3 conventions

Follow CodeIgniter 3 conventions and the existing application structure.

Do not introduce CodeIgniter 4 patterns into this project.

Use existing project conventions for:

- controllers
- models
- helpers
- libraries
- config
- views
- sessions
- validation
- uploads
- routing

Maintain compatibility with the PHP version used by the project and hosting environment.

Do not use newer PHP syntax unless compatibility has been verified.


# Composer packages

Do not keep a project-root `vendor` directory.

Do not rely on running `composer install` on the production or shared hosting environment.

When Composer is required for a third-party integration, keep that integration and its Composer-installed dependencies inside a dedicated directory under:

```text
application/third_party/
```

For example:

```text
application/third_party/google_api/
```

The integration directory may contain its own:

```text
composer.json
composer.lock
vendor/
```

For example:

```text
application/
└── third_party/
    └── google_api/
        ├── composer.json
        ├── composer.lock
        └── vendor/
```

Load the third-party integration through a CodeIgniter library under:

```text
application/libraries/
```

Do not expose Composer-specific implementation details throughout controllers or models when they can be wrapped cleanly inside a CodeIgniter library.

Commit or deploy the populated third-party dependency directory so the application works on hosting where Composer is unavailable.

Do not create or rely on:

```text
/project-root/vendor/
```

Keep CodeIgniter's global `composer_autoload` disabled unless project requirements explicitly change.

If Composer metadata is retained for local dependency updates, configure the relevant integration's Composer setup so dependencies remain inside its corresponding `application/third_party/` directory.


# Manage/admin module standards

Choose the reference implementation according to the module's data shape before building or substantially updating a manage/admin module.

## Basic CRUD modules

The **Artists** module is the canonical structure and UI reference for a basic CRUD module
that manages a collection of records:

```text
application/controllers/manage/Artists.php
application/views/admin/artists.php
application/views/admin/addArtist.php
```

Other Blossom modules built on the same pattern show the conditional capabilities:

- Services (`Services.php`, `addService.php`) — accordion form sections with server-side
  section reopening, CKEditor, repeatable rows (add-ons), several images, SEO fields,
  duplication, and a second listing filter (category).
- Service Categories (`Service_categories.php`) — delete guarded while child records exist.
- Gallery (`Gallery.php`, `galleryUpload.php`) — Dropzone bulk upload that creates one record
  per file.
- Offers (`Offers.php`) — date range validation with the shared `.datepicker`.
- Appointments (`Appointments.php`, `viewAppointment.php`) — read-only records with a detail
  view, POST status changes and author-owned internal notes.

Artists is a port of the legacy Tour Guides module. The legacy tour modules were deleted in
Phase 2 of `PROJECT_PLAN.md` and stay readable from the baseline git tag
(`git show alam-cms-baseline:<path>`). When reading them, take the structure only. Never carry
over their translation integration, language switcher, `_ar` fields, tour/language
relationships, availability, or license uploads. The legacy code is also densely formatted
with tabs; new code follows the formatting rules in this file.

## Shared building blocks for manage/admin modules

Use these instead of per-controller copies:

- `application/libraries/Admin_upload.php` — `save()` validates and stores an upload under a
  random name, `delete()` removes a file and its cached thumbnails, `copy()` duplicates an
  image. Controllers keep the "is this file still referenced" check before deleting.
- `application/libraries/Admin_slug.php` — `normalize()` (matches the admin slug generator in
  `custom.js`) and `unique()` for slug columns.
- `application/libraries/Admin_record_sorter.php` — register every drag-sortable module here.
- `application/helpers/admin_input_helper.php` — `admin_clean_text()`, `admin_clean_lines()`
  ("one per line" textareas), `admin_price_value()`, `admin_format_price()` (`80 zł`),
  `admin_date_value()` / `admin_datepicker_value()` (shared `.datepicker` format),
  `admin_ids()`.
- `application/helpers/admin_listing_helper.php` — `admin_sort_heading()` and
  `admin_datetime_cell()` for listing tables.
- `assets/admin/js/records-listing.js` (always loaded) — listing filters
  (`[data-records-filter]`, `[data-filter-segment]`, `[data-filter-keyword]`) and the bulk
  selection bar (`[data-records-listing]`, `[data-bulk-actions]`). Do not add per-page listing
  scripts.
- `assets/admin/js/repeatable-rows.js` (`$data['useRepeatableRows'] = TRUE`) — add/remove
  form rows (`[data-repeatable]`).
- `assets/admin/js/accordion-validation.js` (`$data['useAccordionValidation'] = TRUE`) —
  reveals invalid required fields inside collapsed accordion panels for
  `form[data-accordion-validation]`.

Validation failures follow one pattern: the controller flashes the posted values
(`<controller>_data`), the invalid field names (`<controller>_invalid`) and `form_error`, then
redirects to the form; the view marks those fields `is-invalid` and opens any accordion section
that contains one.

Use this pattern when the module needs a listing and separate create/edit operations. Adapt only the capabilities the actual module requires, including:

- fields
- relationships
- validation
- uploads
- business rules
- search and filters
- sorting and pagination
- status changes
- duplication
- bulk actions
- deletion

Do not copy module-specific functionality into modules that do not require it. In particular, assignments, availability, drag sorting, uploads, duplication, and bulk deletion are conditional capabilities rather than mandatory CRUD features.

## Singleton modules

Use Website Settings as the canonical structure and UI reference for a singleton module that edits one known record rather than managing a collection.

Canonical files:

Controller:

```text
application/controllers/manage/Website_settings.php
```

Form view:

```text
application/views/admin/webSettings.php
```

Page behavior:

```text
assets/admin/js/website-settings.js
```

A singleton module should:

- define the table, primary key, and fixed record ID explicitly
- authenticate the administrator and enforce the relevant access permission
- load the fixed record into one edit form
- expose a focused save/update action using Post/Redirect/Get
- preserve safe submitted values after validation failure
- report invalid fields and their containing sections predictably
- retain existing optional media when no replacement is uploaded
- use a transaction when uploads or other dependent writes accompany the update
- remove newly uploaded files on failure and replaced files only after a successful commit

A singleton module should not add a listing, add action, delete action, bulk actions, duplication, record status controls, sorting, filtering, or pagination unless the business requirement explicitly calls for them.

Use grouped collapsible sections when a long singleton form benefits from them. Keep section headings, descriptions, required/optional state, error state, and accessible collapse relationships clear. A server-side validation error must mark and reopen every affected section; stored collapse preferences must never hide a section containing an error.

Existing modules should follow the appropriate standard when they are being substantially updated, redesigned, migrated, or brought into the current admin architecture.

Do not retain an inconsistent legacy pattern merely because an older module uses it.


# Controller conventions

Where applicable, define the module's configuration explicitly in the controller, including:

- table
- column prefix
- primary key
- display name
- route/controller slug
- page size
- status column
- listing view
- add/edit view

Authenticate the administrator in the constructor.

Redirect unauthenticated users to:

```text
manage/login
```

before performing module operations.

Keep the following responsibilities clearly separated:

- listing
- add form
- edit form
- create
- update
- delete
- bulk delete
- status change
- duplicate

Only expose operations the module actually supports.

Do not add unused actions simply because another module has them.


## Request validation

Allowlist request-controlled values such as:

- sortable columns
- sort directions
- status values
- pagination values
- record IDs
- filter types
- route-controlled options

Never place unchecked request values into:

- table names
- column names
- SQL sort expressions
- file paths
- include paths
- redirect destinations


## Validation

Use server-side validation as the authority.

Mirror important validation rules in the form for better usability.

On validation failure:

- preserve safe submitted values
- preserve relevant selections
- use appropriate flash data where the current pattern requires it
- return the user to the appropriate add/edit screen
- display a clear error

Do not discard user-entered form data unnecessarily.


## Mutations

Use Post/Redirect/Get behavior after successful mutations.

Use the shared CRUD flash-alert vocabulary so refreshes do not repeat write operations.


## Transactions

Use database transactions when a change spans:

- multiple tables
- relation assignments
- uploads
- dependent database writes
- related records

If any dependent operation fails:

- roll back database changes
- remove newly created files when appropriate
- leave existing files intact when possible
- report a predictable error


## Uploads

Treat uploads as optional unless the business requirement explicitly makes them mandatory.

For supplied files:

- validate MIME type
- validate extension
- enforce size limits
- use the existing shared allowlists where available
- generate safe filenames
- do not trust the original filename
- retain the existing file when no replacement is supplied
- safely remove replaced files
- safely remove deleted files

Never construct file paths directly from unchecked request values.


## IDs and missing records

Cast numeric IDs before use.

Handle missing records gracefully.

Do not produce PHP notices or undefined-index warnings because a requested record does not exist.


## AJAX operations

For AJAX operations such as status changes, return predictable JSON responses.

Use a consistent response shape where practical.

Return appropriate success or failure information without exposing internal errors or sensitive implementation details.


## Module-specific concerns

Keep module-specific functionality conditional.

Features such as:

- avatars
- image uploads
- document uploads
- drag sorting
- duplication
- scheduling
- availability

should only be added where the module actually requires them.


# Listing view conventions

Use the shared admin partials where applicable:

```text
breadcrumb
crud_alert
module_header
table_listing_footer
```

Wrap the module listing in an accessible:

```text
admin-records-listing
```

section with a unique `aria-labelledby` heading.


## Toolbar

Follow the canonical module's toolbar pattern for:

- search
- filters
- filter state
- clear filters

Use visible or visually hidden labels as appropriate.

Keep query and route state predictable.

Provide a clear way to remove active filters.


## Tables

Follow the canonical module's table pattern where appropriate:

- responsive table wrapper
- semantic table headings
- sortable header links
- `aria-sort`
- selection checkboxes
- optional drag handles
- concise record summaries
- status controls
- timestamps
- actions column

Do not add table features that the module does not need.


## Bulk actions

Use the shared bulk-action pattern when bulk deletion or another bulk operation is supported.

Include:

- selected-count feedback
- disabled bulk actions when nothing is selected
- clear confirmation for destructive operations


## Empty states

Provide distinct empty states for:

1. The module contains no records.
2. Records exist, but none match the current search or filters.


## Output escaping

Escape displayed or attribute-bound dynamic text appropriately.

For ordinary text output, use:

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

Cast IDs before output or URL generation where applicable.

Build application URLs through:

```php
base_url()
```

or:

```php
ADMIN_URL
```

according to the established project convention.


## Admin styling

Use:

- Bootstrap
- Bootstrap Icons
- existing admin classes
- existing shared components
- reusable admin CSS

Do not introduce one-off inline styling when the existing admin stylesheet or a reusable class can express the design.


# Add/edit view conventions

Use one shared add/edit view where practical.

Use:

- breadcrumb
- module header
- admin card
- clear Add/Edit title
- consistent Cancel and Save actions


## Form fields

Build fields using:

```text
admin-field
form-label
Bootstrap form controls
```

and the established responsive grid.

Mark required fields with:

```text
is-required
```

Keep HTML/client-side requirements aligned with server-side validation.

Clearly identify optional uploads when useful.


## Validation behavior

Use the common:

```text
.validate
```

flow and:

```text
data-validate
```

rules.

Server-side validation remains authoritative.

When a required or invalid field is inside a closed Bootstrap accordion or other collapsible panel, validation must reveal the field instead of failing against hidden content. On submit or the first invalid event:

- find the first invalid required control
- open its closest collapsed panel through the existing Bootstrap Collapse integration
- wait for the panel's shown event before focusing the control
- display or refresh the normal validation message after the control is visible
- keep the Save button enabled when submission is blocked so the administrator can correct and resubmit

Apply this behavior to native `required` validation and the common `.validate` / `data-validate` flow. Account for controls enhanced by Select2, international phone input, CKEditor, upload components, or other existing admin widgets. Do not disable validation for hidden required fields merely because their collapsible section is closed.

After a server-side validation failure, pass field and section error state back to the view, render invalid controls consistently, and open all sections containing errors on the returned page.

A Save button may:

- change to `Saving...`
- become disabled

only after all required client-side and feature-specific validation succeeds.

Do not permanently disable the button because validation failed.


## Shared field partials

Use existing shared field partials rather than rebuilding their behavior.

Examples include:

```text
admin/partials/avatar_upload.php
admin/partials/file_upload.php
admin/partials/icon_picker_field.php
```

Use additional shared partials when available.

Do not recreate:

- upload previews
- image crop behavior
- file selectors
- icon pickers
- reusable validation UI

inside individual pages unless the module has a genuinely unique requirement.


## Edit behavior

Preserve submitted values after validation errors.

On edit:

- retain existing media unless replaced or removed
- retain existing relationships unless changed
- retain existing selections
- do not treat an optional upload as required simply because the record already has one


## Form actions

Keep primary form actions inside:

```text
admin-form-actions
```

Use the established order:

1. Secondary Cancel or Back action
2. Primary `Save <Entity>` action


## JavaScript and CSS

Put reusable admin behavior in:

```text
assets/admin/js/
```

Put reusable admin presentation in:

```text
assets/admin/css/admin.css
```

Avoid page-specific inline scripts when the behavior can reasonably be shared.

Small inline configuration or server-generated values are acceptable when required for wiring an existing reusable script.


# Language

The CMS and the website are English only.

- Store each text value in a single column. Do not add `_ar` or other per-locale columns.
- The translation stack (Google Cloud Translation, translation jobs and cron, translation
  badges, language switchers, locale tabs) and the Countries module were removed in Phase 3.
  Do not reintroduce them.
- The `_ar` columns, `ar` section-field rows and retired tables were dropped by the Phase 4
  SQL (`docs/sql/phase-4-cleanup.sql`). `ci_cms` still has them; never copy schema or data
  back from it.
- Web page section and miscellaneous content fields keep their `locale` column. Every read
  and write uses the single `en` locale from `config/content_sections.php`.
- The admin "Saving..." state is set by the shared `.validate` submit handler in `admin.js`
  on any `[data-save-button]` (with its `[data-save-label]`) once validation passes. Use it
  instead of page-specific save-button code.


# Security and defensive coding

Treat all request data as untrusted.

Validate and sanitize data according to its intended use.

Use CodeIgniter database bindings or query builder rather than manually concatenating request values into SQL.

Never trust user-controlled values for:

- table names
- column names
- filenames
- paths
- redirects
- sort expressions
- status values
- record IDs

Escape output according to its context.

Do not weaken existing:

- authentication
- authorization
- CSRF protection
- upload validation
- input validation
- output escaping

for convenience.

Do not expose:

- passwords
- API keys
- private tokens
- service credentials
- internal stack traces

in frontend or admin output.


# Frontend and UI preservation

Unless the task specifically requests a UI change:

- preserve existing visual layout
- preserve spacing
- preserve responsive behavior
- preserve classes
- preserve interactions
- preserve frontend behavior

Do not redesign a page as a side effect of a backend change.

When UI work is explicitly requested, reuse the established project visual language and shared components.


# JavaScript

Before adding a new JavaScript library, check whether an existing project dependency can handle the requirement.

Prefer mature ready-made JavaScript or jQuery plugins when appropriate rather than building complex UI widgets from scratch.

Keep reusable behavior in:

```text
assets/admin/js/
```

or the appropriate frontend JavaScript directory.

Avoid duplicate event handlers and duplicate plugin initialization.

When modifying JavaScript:

- preserve existing interactions unless intentionally changed
- avoid global namespace pollution
- guard plugin initialization when elements may not exist
- avoid double initialization
- handle AJAX failures predictably


## Manage/admin date and time controls

The manage/admin area already uses Flatpickr 4.6.13 for date and time controls.

The shared assets are loaded through:

```text
application/views/admin/header.php
application/views/admin/footer.php
```

Shared initialization lives in:

```text
assets/admin/js/admin.js
```

Reuse the existing selectors when adding compatible admin fields:

- `.datepicker` for a single date
- `.daterange` for a date range
- `.timepicker` for a time

Do not add another date/time-picker library or duplicate Flatpickr initialization for manage/admin forms. Use native HTML `type="date"` only when the existing module intentionally follows that pattern and the requested work does not require standardizing it.


## Manage/admin color controls

The manage/admin area already uses Coloris for color-picker fields.

Coloris assets are loaded conditionally through:

```text
application/views/admin/header.php
application/views/admin/footer.php
```

Set the view data flag below when a manage/admin page requires the color picker:

```php
$data['useColorPicker'] = TRUE;
```

Shared initialization and value normalization live in:

```text
assets/admin/js/color-picker.js
```

Use the existing `.color-picker` class for compatible admin fields. The shared implementation supports alpha transparency and normalizes saved values to `rgba(r, g, b, a)`.

Do not add another color-picker library, duplicate Coloris initialization, or implement page-specific color normalization for manage/admin forms.


# CSS

Reuse existing Bootstrap utilities and admin classes before creating new CSS.

For reusable styling, update:

```text
assets/admin/css/admin.css
```

where appropriate.

Avoid:

- repeated inline styles
- duplicate CSS rules
- unnecessary page-specific styles
- arbitrary values when an existing design-system value is available

Do not change unrelated styling while implementing functional work.

## Frontend Tailwind build

The Blossom frontend (`application/views/frontend/`) is styled with **Tailwind CSS v4**,
built with the standalone CLI — no npm, no Node, no `node_modules/`. The `/ci3/` reference
design was authored for v4 (`ci3/assets/css/site.css` is a compiled v4.3.3 stylesheet), so
the project uses v4 rather than the v3 build inherited from the Alam project.

```text
D:\wamp64\www\tailwindcss-v4-windows-x64.exe
```

The binary lives one level above this project, next to the v3 binary other WAMP projects
still use (v3.4.17, used by the Alam project), and must never be committed. Do not use the
v3 binary (`tailwindcss-windows-x64.exe`) for this project. The pinned version is
**v4.3.3** (the official `tailwindcss-windows-x64.exe` release asset, the version the
reference was compiled with); do not upgrade it as a side effect.

v4 is configured in CSS; there is no `tailwind.config.js`:

- `assets/frontend/css/src/tailwind.css` holds `@import "tailwindcss" source(none);`, the
  explicit `@source` paths (`application/views/frontend`,
  `application/helpers/frontend_helper.php`, `assets/frontend/js`), the fonts, the `@theme`
  tokens, the base and component layers, and the reference's server-rendering rules
  (`.reveal`, `header.ci-scrolled`, `.ci-mobile-menu`). `source(none)` keeps Tailwind from
  scanning `/ci3/` and the admin area.
- The theme tokens come from `ci3/assets/css/site.css`: colors (`background`, `foreground`,
  `foreground-soft`, `muted`, `muted-foreground`, `border`, `border-strong`, `primary`,
  `primary-cta`, `primary-ink`, `primary-strong`, `primary-foreground`, `secondary`,
  `secondary-light`, `accent`, `plum`, `plum-deep`, `plum-deeper`, `lilac`, `petal`,
  `destructive`), fonts (`display`: Fraunces, `sans`: Plus Jakarta Sans) and easing
  variables. Keep token names identical to the reference so its markup works unchanged.

`assets/frontend/css/app.css` is **generated. Never edit it by hand.** Every style change goes
into `assets/frontend/css/src/tailwind.css`, then gets rebuilt:

```bash
D:\wamp64\www\tailwindcss-v4-windows-x64.exe -i assets/frontend/css/src/tailwind.css -o assets/frontend/css/app.css --minify
```

Watch while developing:

```bash
D:\wamp64\www\tailwindcss-v4-windows-x64.exe -i assets/frontend/css/src/tailwind.css -o assets/frontend/css/app.css --watch
```

Rebuild after any change to a frontend `.php` view or `.js` asset, not just after CSS changes —
Tailwind only emits classes it finds in the `@source` paths. A new class in a view that has not
been recompiled does not exist in the stylesheet. Never build a class name by string
concatenation.

Frontend third-party assets are hosted locally under `assets/frontend/vendor/` (the versions
used by the reference): jQuery 4.0.0, Swiper 14.0.7 (home hero only), PhotoSwipe 5.4.4
(gallery only) and Font Awesome Free 7.3.1. Load page-specific libraries only on the pages
that need them, through the `vendors` option of `Frontend_layout::render()`.

## Frontend layout

Every public page renders through `Frontend_layout::render($view, $data, $page)`:

- `views/frontend/layout/head.php`, `header.php` and `footer.php` wrap the page view, which
  outputs its own `<main id="main">`. Do not duplicate the header or footer in a page.
- The layout loads Website Settings once per request and passes `$site` (name, phone,
  address lines, address note, opening hours, map link, logos, socials, booking URL),
  `$navigation` (top level of Manage > Menu) and `$footer` (featured services, Manage > Foot
  "one" and "two") to every view.
- Head values come from `Frontend_layout::pageMeta()` (a Web Pages record, or any row with
  the same SEO columns) through `Frontend_seo`. Pass `$page['meta']`, not ad-hoc meta tags.
- Page scripts go in `$page['scripts']` (loaded after jQuery and `site.js`); vendor bundles
  in `$page['vendors']` (`swiper`, `photoswipe`).
- Shared markup lives in `views/frontend/partials/`. Reuse these before writing page
  markup:
  - `page_hero` (breadcrumb, label, heading, lead, actions with optional icon/external,
    facts, chips, optional image), `breadcrumb`, `cta_band` (`card` and `inline`
    variants), `visit_details` (address, note, phone, opening hours).
  - Cards: `service_card`, `service_menu_item`, `service_related_item`, `artist_card`,
    `offer_card`, `post_card`, `post_meta`, `testimonial`.
  - `category_chips` with `js/category-filter.js`: chips that filter the
    `[data-filter-item]` elements of a `[data-category-filter]` container and keep
    `?category=` in the address (the server honours it without JavaScript).
- CI caches view variables across `load->view()` calls in one request, so pass every
  optional partial variable explicitly (`variant`, `light`, `spacing`, …) instead of
  relying on its default.
- `helpers/frontend_helper.php` holds the reference button classes
  (`frontend_button_class()`), `frontend_phone_href()`, `frontend_opening_hours()`,
  `frontend_lines()`, `frontend_price()` / `frontend_service_price()` (`from 80 zł`),
  `frontend_url()` (CMS link values), `frontend_icon_class()` and
  `frontend_html_sections()` (editor HTML split at its `<h2>` headings). Resize images with
  `upload_thumb()`. Escape output with `html_escape()`.
- CMS rich text (blog text, legal page text) is output unescaped inside `.article-body` or
  `.legal-body`, which style the editor's HTML; everything else is escaped.
- Every public page has a Web Pages record whose slug is its URL (`/services`, `/artists`,
  `/gallery`, `/offers`, `/blog`, `/about`, `/faq`, `/contact`, `/book`, the legal pages).
  The record supplies the page's SEO fields, its hero text (banner title = label, banner
  heading = `<h1>`, banner text = lead, banner background = image) and its place in the
  header and footer menus. `Frontend::listingPage()` loads it and 404s when it is missing
  or unpublished.
- Page sections are defined in `config/content_sections.php` under the page's fixed ID
  (1 home, 2 about, 6 FAQ, 7 contact, 8 journal, 9/10/41 legal, 37 services, 38 artists,
  39 gallery, 40 offers, 42 book). Keep those IDs when seeding another environment.
  Texts shared by every service or artist page are Miscellaneous Contents
  (`service_page`, `artist_page`, `nail_shapes_finishes`).
- Content changes that Phase 6 made are recorded as re-runnable SQL in
  `docs/sql/phase-6-*.sql`; follow that pattern for further content seeding.

## Public forms

The contact form (`libraries/Contact_form.php`) and the booking request wizard
(`libraries/Booking_request.php`, `libraries/Booking_schedule.php`, `js/booking.js`) share
one pattern. Follow it for any new public form:

- A one-use session token in the form (global CSRF protection is off); rotate it on every
  submission.
- Server-side validation is the authority; the page's script mirrors it for usability.
- `Google_recaptcha::verify($token, $action, $minScore)` with a form-specific action.
  Without reCAPTCHA keys it accepts forms only in `development` and rejects them in every
  other environment.
- Post/Redirect/Get for plain forms; predictable JSON (`success`, `status`, `message`,
  `errors`, `step`, new `formToken`) for AJAX.
- Save related rows in one transaction.
- Email the salon at Website Settings > notification emails and the client through a
  managed Email Template sent with `EmailService::sendManagedTemplate()`. Short tags are
  registered per entity in `EmailService::$shortTagFields` (`contact`, `appointment`) with
  examples in `config/short_tags.php`; templates 1 (contact) and 2 (appointment request)
  are mapped there.
- Emails need a sender address (Website Settings > sender or site email). Locally
  `EMAIL_HOST` is `log`, so messages are written to `email_logs/` instead of being sent.

Booking requests are requests, not bookings (`PROJECT_PLAN.md` decision D1): the salon
confirms each one in Manage > Appointments. Do not present a request as a confirmed
appointment.

The admin area (`assets/admin/css/admin.css`) is plain CSS/Bootstrap and is not part of this
Tailwind build.


# Documentation and comments

## Frontend email date and time formatting

Use the shared constants from `application/config/constants.php` for
human-readable dates and times in frontend-generated emails:

```php
EMAIL_DATE_FORMAT
EMAIL_TIME_FORMAT
EMAIL_DATETIME_FORMAT
```

Use `EMAIL_DATETIME_FORMAT` for date/time short tags such as `created_at`
and `updated_at`. Reuse these constants for future frontend emails instead
of defining page-, controller-, or template-specific date formats. These
constants affect email display only and must not change database storage
formats.

Add comments where they explain non-obvious business rules, integration constraints, or unusual implementation decisions.

Do not add comments that merely repeat what obvious code already says.

Keep comments concise and useful.

Do not remove meaningful existing comments without a reason.


# Latest documentation

When implementing or changing third-party libraries, APIs, plugins, or dependencies, verify the current official documentation when practical.

Do not rely on outdated implementation patterns if the installed version behaves differently.

However, do not upgrade dependencies merely because a newer version exists unless the task requires it.


# Verification

Verification should match the scope of the requested change.

Do not automatically perform a complete application regression test for every small change.


## PHP

For modified PHP files, run syntax checks when PHP CLI is available:

```bash
php -l path/to/file.php
```

Check all materially modified PHP files.


## JavaScript

For modified JavaScript files, run an appropriate syntax check when tooling is available.

Do not introduce a new build system solely for syntax checking.


## Manage/admin testing

For manage/admin module changes, test the local flows affected by the change.

Depending on the requested scope, this can include:

- listing
- search
- filtering
- sorting
- pagination
- add
- edit
- validation errors
- successful submission
- status changes
- uploads
- media replacement
- bulk actions
- delete
- duplicate

Only test functionality relevant to the change.

A small isolated fix does not require testing every possible module flow unless specifically requested.


## UI verification

When the change affects UI:

- check desktop layout
- check responsive behavior
- check visible labels
- check accessible names
- check relevant ARIA states
- check validation feedback
- check disabled/enabled button behavior

Do not make unrelated UI changes while verifying the requested work.


# Completion report

At the end of the task, provide a concise summary containing:

1. What was changed.
2. Which files were modified.
3. Any database query or migration required.
4. Any dependency added or changed.
5. Which verification checks were performed.
6. Any relevant issue that could not be verified locally.

Do not provide a long implementation essay unless requested.

If no database change is required, do not invent one.

If no dependency change is required, do not add one.
