# Blossom CMS — Customisation Plan

Plan for turning the copied Alam Al-Munawara tour CMS (this repository) into the CMS and
website for **Blossom Ewa Mazur**, a manicure, nail art and beauty studio in Gorlice, Poland.

The target frontend is the static CodeIgniter 3 export in `/ci3/`. The target CMS is this
application with everything tour-related, bilingual, and integration-specific removed.

Work through the phases in order. Each phase should end in a working, committable state.

---

## 1. Review summary

### 1.1 The current application (base CMS)

CodeIgniter 3, PHP 8.4 platform, MySQL (`ci_cms` locally, 51 tables). Built for a bilingual
(English/Arabic) Madinah tour operator.

| Area | What exists |
|------|-------------|
| Admin shell | Login, remember-me, password reset, login throttling, admin users, account settings, dashboard, Bootstrap 5 + Bootstrap Icons, shared partials (`breadcrumb`, `crud_alert`, `module_header`, `table_listing_footer`, `avatar_upload`, `file_upload`, `icon_picker_field`, `confirm_modals`, `star_rating`, `short_tag_picker`), Flatpickr, Coloris, CKEditor/CKFinder, drag sorting (`Admin_record_sorter`, `Record_sorting`) |
| Content modules | Pages, Blogs, Blog Categories, FAQs, FAQ Categories, Slider/Sliders, Web Page Sections, Miscellaneous Contents, Content Section Sync, Menu, Footer columns (`Foot`), Customer Reviews, Contact Requests, Email Templates, Form Settings, Website Settings, Countries, Email Test |
| Tour/booking modules | Tours, Tour Categories, Tour Images, Tour Itineraries, Tour Slots, Tour Languages, Tour Guides, Tour Guide Availability, Tour Reviews, Attractions, Vehicles, Bookings, Discount Codes, Referrals, Reports (6 reports), Plan Your Visit |
| Integrations | Google Cloud Translation (+ translation job queue, cron, polling UI), Google Places (booking pickup location), WhatsApp Cloud API (gateway, templates module, webhook, booking notifications), Moyasar payments (SAR), reCAPTCHA Enterprise, Mailgun SMTP |
| Frontend | `Frontend.php` (5,335 lines) with `/en` and `/ar` locale prefixes, Tailwind v3 build using Alam tokens, booking wizard, plan-your-trip wizard, review form, SEO library (`Frontend_seo`), presenter library, sitemap/robots/llms |
| Bilingual data | Almost every content table has `_ar` twin columns; section field tables have a `locale` column; `Localized_model` selects per-locale columns |

**Reusable foundations worth keeping:** `SqlModel`, the admin shell and shared partials,
`Imagethumb`, `Admin_record_sorter`, `Page_menu_hierarchy`, `Content_section_service`
(page sections), `Short_tags`, `EmailService`, `Frontend_seo`, `Seo` controller,
`admin_pagination_helper`, `image_helper`, the CRUD module pattern (Tour Guides) and the
singleton pattern (Website Settings).

### 1.2 The new frontend (`/ci3/`)

A CodeIgniter 3.1.13 export of a Next.js site. It is **presentation only**:

- 49 views in `ci3/application/views/frontend/`, each a complete, **minified single-line HTML
  document** using `<?= ?>` short tags. No shared layout; header and footer are duplicated in
  every file.
- Every service, artist, article and appointment is a separate hard-coded view.
- Styling is a compiled **Tailwind v4.3.3** stylesheet (`assets/css/site.css`) with theme
  tokens (`primary`, `plum`, `lilac`, `petal`, `foreground-soft`, …) and Fraunces + Plus
  Jakarta Sans fonts. The source CSS is not included.
- JS: jQuery 4.0.0, `site.js` (header/menu/reveal), `interactions.js` (filters, validation,
  search), `booking.js` (a 5-step client-side wizard with services/artists/offers hard-coded
  in JS), Swiper 14 (home hero), PhotoSwipe 5 (gallery), Font Awesome Free 7.3.1.
- Forms, login/registration, the booking wizard and the account area are UI mock-ups; nothing
  is persisted.
- `ci3/vendor/` holds CodeIgniter's dev dependencies (PHPUnit etc.) and is not needed.

**Site map of the new frontend**

| Route | Page | Content found |
|-------|------|---------------|
| `/` | Home | Hero carousel, nail services intro, nail art, "fresh from the studio" (gallery), about teaser, artists, how booking works (4 steps), offers, testimonials, location, Instagram feed, CTA |
| `/services` | Services | Grouped menu (Nails / Hair / Beauty) with price "from" and duration |
| `/services/{slug}` | Service detail (14) | Intro, price, time, what's included, before your visit, aftercare, add-ons with prices, shapes & finishes, related gallery, FAQs |
| `/artists`, `/artists/{slug}` | Team (3) | Role, bio, specialties, services offered, weekly availability |
| `/gallery` | Gallery | Photos with caption + category filter (Manicure, Nail Art, Hair, Makeup, Beauty), lightbox |
| `/offers` | Offers | Featured + other bundles: label, title, summary, inclusions, price, old price, duration, validity, linked services |
| `/about` | About | Story, what we're known for, products and tools, team, location |
| `/blog`, `/blog/{slug}` | Journal (6 articles) | Category filter, lead article + grid |
| `/faq` | FAQ | Grouped by Appointments / Nails / The salon |
| `/contact` | Contact | Address, phone, opening hours, map (placeholder), contact form (name, email, phone, subject select, message) |
| `/book` | Booking | Service → Artist → Date & time → Details → Review |
| `/search` | Search | Site search across services/articles |
| `/login`, `/register`, `/forgot-password`, `/account/*`, `/appointment/{ref}` | Customer account | Mock-ups only |
| `/privacy-policy`, `/terms`, `/cancellation-policy` | Legal | Static text |

Site-wide data used by the header/footer: logo (dark and light), phone `+48 512 129 654`,
address, "above the 5.10.15 children's store" note, opening hours per day group, footer
link columns, social/Instagram, copyright. Currency is **PLN (zł)**.

---

## 2. Decisions to confirm before or during the phases

Each item has a recommendation; the plan below assumes the recommendation unless changed.

| # | Decision | Recommendation |
|---|----------|----------------|
| D1 | Booking: real online booking engine, a **booking request** form, or phone-only? | Phase 6 shipped a **booking request** (staff confirm by phone/email). **Changed in Phase 8 (owner's decision, 2026-09-28): real-time booking** against the artists' diaries, confirmed straight away; placeholder artists are bookable; services that need different artists are booked back to back. |
| D2 | Customer accounts (login/register/account/appointments) | **Defer.** Remove these routes from launch; revisit only if D1 becomes a real booking engine. |
| D3 | Online payments | **None.** Remove Moyasar. Salons are paid in the studio. |
| D4 | reCAPTCHA | **Keep reCAPTCHA Enterprise** (already integrated) for the contact and booking-request forms, with a new Google project/keys for Blossom. Remove only `google/cloud-translate` from Composer. |
| D5 | Tailwind version for the frontend | **Tailwind v4 standalone CLI.** The exported markup is written for v4 (`@theme` tokens, v4 arbitrary-value syntax); porting it to v3 risks visual drift. |
| D6 | Instagram section on home | Admin-managed images + profile link (no Instagram API). |
| D7 | Map on contact page | Plain Google Maps embed / "Open in Maps" link (no API key, no Places). |
| D8 | Existing Arabic columns | **Drop** after the code no longer reads them (Phase 4 SQL). |
| D9 | Countries module | **Remove** unless D1/D2 needs a country list. |
| D10 | Local host name | **Decided:** keep `http://ctech-cms.com/` (existing WAMP vhost). |

---

## 3. Module mapping (old → new)

| New site need | Source in current CMS | Action |
|---------------|-----------------------|--------|
| Services + service groups | Tours, Tour Categories, Tour Images | **New** `Services` + `Service_categories` modules, modelled on Tours/Tour Categories |
| Service add-ons, inclusions, "before/aftercare" lists | Tour itineraries (repeatable rows) | Part of Services (child rows or structured fields) |
| Artists / team | Tour Guides | **New** `Artists` module, a direct port of Tour Guides (profile, photo, role, bio, specialties, assigned services) |
| Artist weekly availability (display) | Tour Guide Availability | Simple weekly day toggles on Artist for display; full availability only if D1 becomes a real engine |
| Gallery + gallery categories | Tour Images, `tour-images-upload.js` | **New** `Gallery` + `Gallery_categories` modules (multi-upload, caption, category, sort) |
| Offers / bundles | Tours (pricing), Discount Codes | **New** `Offers` module (label, title, summary, inclusions, price, old price, duration, validity dates, linked services, featured flag) |
| Appointment requests | Bookings, `viewBooking` | **New** `Appointments` module (listing, view, status: New / Confirmed / Completed / Cancelled, internal notes) |
| Testimonials | Customer Reviews | **Keep**, English-only |
| Journal | Blogs, Blog Categories | **Keep**, English-only, route `/blog` |
| FAQ | FAQs, FAQ Categories | **Keep**, English-only; optionally link FAQs to a service |
| Legal + generic pages | Pages | **Keep** |
| Home / About / Contact sections | Web Page Sections, Miscellaneous Contents, `content_sections.php` | **Keep**, redefine sections for Blossom |
| Hero carousel | Slider / Sliders | **Keep** |
| Header/footer menus | Menu, Foot | **Keep** |
| Site-wide details, opening hours, socials | Website Settings | **Keep**, add opening hours + map link + Instagram fields |
| Contact form | Contact Requests, Form Settings, Email Templates | **Keep** |
| Admin users | Admins, Login | **Keep** |
| Dashboard | Home (manage) + `Dashboard_model` | **Rewrite** around appointment requests, contact requests, content counts |

---

## Phase 0 — Baseline, safety and environment

1. **Secrets.** `application/config/constants.php` contains live credentials from the
   previous client (Mailgun SMTP password, Moyasar live/test keys, WhatsApp access token and
   app secret, reCAPTCHA keys, Google Maps key). `application/` is not committed yet — remove
   these values **before the first commit** and ask the previous project owner to rotate them.
   Move any remaining secrets to `application/config/development/` and
   `application/config/production/` (already gitignored) and load them from there.
2. Commit the untouched CMS as a baseline and tag it (for example `alam-cms-baseline`), so the
   tour modules stay available as a reference through `git show alam-cms-baseline:<path>`
   after they are deleted.
3. Create a new local database (for example `blossom_cms`) from a copy of `ci_cms`, point
   `config/database.php` at it, and keep `ci_cms` untouched as a backup.
4. Environment: timezone `Europe/Warsaw` (currently `Asia/Riyadh` in `index.php`), sender name
   and address, local vhost (D10), `PROJECT_TITLE` via Website Settings.
5. Add `/ci3/` to `.gitignore` or commit it as read-only reference — it must never be deployed
   (`ci3/vendor/` in particular). It is deleted in Phase 9.

**Done when:** the CMS runs against `blossom_cms`, no secret is tracked by git, baseline tag exists.

**Status: done (2026-09-27).** Baseline commit tagged `alam-cms-baseline`. Secrets moved to
`application/config/development/constants.php` (template: `secrets.example.php`). `blossom_cms`
created from `ci_cms`; site settings set to the Blossom name, address, phone, `zł` and
copyright. Open items carried forward:

- Blossom sender/contact email address and domain (settings `email`, `sender_email`, and
  `EMAIL_ADDRESS`) — not yet known.
- New reCAPTCHA Enterprise project and keys — forms that verify reCAPTCHA will fail locally
  until they are added.
- Old Alam logos/favicon are still set in Website Settings; replaced in Phase 5.
- The previous project owner should rotate the credentials that were in `constants.php`.

---

## Phase 1 — Build the new admin modules (reference: tour modules)

Build new modules **while the tour modules still exist**, so they can be read side by side.
All new modules are **English-only** — no `_ar` columns, no `Manage_translation_service`, no
language switcher, no translation badges.

Order (each module = controller + listing view + add/edit view + SQL + navigation entry):

1. **Service Categories** — reference `Tour_categories.php`. Fields: name, slug, short
   description, image, sort, status.
2. **Services** — reference `Tours.php` + `addTour.php`. Fields: category, name, slug, summary,
   description (CKEditor), price from, price suffix (e.g. "/ nail"), duration label, duration
   minutes, card image, hero image, included items, before-your-visit items, aftercare items,
   add-ons (label + price), featured flag, SEO fields (page title, meta description, OG),
   sort, status.
3. **Artists** — reference `Tour_guides.php` + `addTourGuide.php` (canonical CRUD). Fields:
   name, slug, role, bio, photo (avatar partial), specialties, assigned services, usual
   working days, placeholder flag, sort, status. **This becomes the new canonical CRUD
   module** in AGENTS.md.
4. **Gallery Categories** + **Gallery** — reference `Tour_images.php` +
   `tour-images-upload.js`. Fields: image, caption, category, optional related service, sort,
   status. Multi-upload.
5. **Offers** — reference `Tours.php` pricing fields. Fields listed in §3; validity dates use
   the shared `.daterange` Flatpickr selector.
6. **Appointments** (booking requests) — reference `Bookings.php` + `viewBooking.php`.
   Listing with search/status filter, detail view, status change (POST form),
   internal notes. No create form in admin unless requested.

Also in this phase:

- Update `views/admin/navigation.php` with a Salon group (Services, Service Categories,
  Artists, Gallery, Offers) and an Appointments entry.
- Register sortable modules with `Admin_record_sorter` where drag sorting is used.
- Provide the `CREATE TABLE` SQL for each module in `docs/sql/phase-1.sql`.

**Done when:** every new module passes list / search / filter / sort / add / edit /
validation-failure / status / delete tests locally.

**Status: done (2026-09-27), branch `phase-1-salon-modules`.** Tables created from
`docs/sql/phase-1.sql` (12 tables, foreign keys included). All modules tested over HTTP; the
tables were emptied afterwards, so content is loaded in Phase 7. Decisions taken while
building:

- Artists use a normal portrait photo upload (1200 × 1500px) rather than the 512px circular
  avatar cropper used by Tour Guides, because the design shows large portrait photos.
- Appointment status changes use a POST form on the detail page (four statuses, any to any so
  mistakes can be corrected) rather than the Enable/Disable AJAX toggle. No emails are sent
  on status change yet (Phase 7).
- Service Categories cannot be deleted while they contain services; deleting a Gallery
  Category leaves its images as "Uncategorised".
- Duplicated services and offers are saved as disabled drafts.
- Shared pieces were added instead of copying helpers into each controller (see "Shared
  building blocks" in AGENTS.md). Legacy modules were not changed to use them.

Not yet verified: a visual check in a browser (the admin was tested over HTTP only).

---

## Phase 2 — Remove tours, bookings and payments

Delete (controllers, views, models, libraries, JS, routes, navigation, constants):

- Controllers `manage/`: Tours, Tour_categories, Tour_images, Tour_itineraries, Tour_slots,
  Tour_languages, Tour_guides, Tour_guide_availability, Tour_reviews, Attractions, Vehicles,
  Bookings, Discount_codes, Referrals, Reports, Plan_your_visit, (Countries per D9).
- Root controllers: Booking_cron, Payments.
- Models: Tour_model, Tour_review_model, Guide_model, Booking_model, Booking_payment_model,
  Manage_booking_model, Plan_your_visit_model, ReportsModel, Review_model (after checking it
  is only used by tour reviews).
- Libraries: Tour_pricing, Moyasar_gateway, Booking_email_service, Discount_email_service.
- Admin views for all of the above, report partials (unless kept for Appointments reports),
  `bookings.js`, `reports.js`, `tour-images-upload.js`, `dashboard.js` (rewrite).
- Helpers: `report_helper.php` (unless kept).
- Constants: `TOUR_*`, `PRICE_TOUR_ID`, `CAR*`, `BOOK_*`/`CONSUME_*`/`CANCEL_HOUR_LIMIT`,
  `DISCOUNT_*`, `MOYASAR_*`, `EXPERIENCE_URI`, `TOUR_URI`.
- Short tags and email templates that refer to bookings, guides, payments or discounts.
- Frontend: booking wizard, plan-your-trip, tour/experience/guide pages, review form and their
  JS (`booking*.js`, `plan-your-trip.js`, `hero-search.js`, `review.js`, `number-stepper.js`).
- Rewrite the dashboard (`manage/Home.php`, `Dashboard_model`, `dashboard.php`) around
  appointment requests, contact requests and content counts.

Grep for `tour`, `booking`, `guide`, `moyasar`, `discount`, `referral`, `vehicle`,
`attraction`, `plan_your` after deletion; the only remaining matches should be intentional.

**Done when:** `/manage` and every remaining module load with no PHP notices; `php -l`
passes on every modified file.

**Status: done (2026-09-27), branch `phase-2-remove-tours`.** Everything listed above was
removed (16 admin controllers, the booking cron and Moyasar payments controllers, 9 models
including the unused `SiteModel`, 5 libraries, `report_helper`, 46 admin views and report
partials, tour/booking admin and frontend JS). The dashboard was rewritten around
appointment requests. Also removed: tour-only code in `custom.js`/`admin.js`, the AJAX "add
tour category" modal, the booking/discount/plan-your-visit short-tag entities, the tour and
payment constants, the tour entries in the sitemap/`llms.txt`, and the profit/tax, tourism
licence and payment-icon fields in Website Settings.

Changes to the plan made during this phase:

- **The old public site was retired now instead of in Phase 5.** Its header, footer, home and
  404 pages all loaded tour data, so untangling it would have been throwaway work.
  `Frontend.php` is now a small controller that serves a holding page
  (`views/frontend/holding.php`) for every public URL and keeps the admin 404 for unmatched
  `/manage` URLs. All old public views and frontend routes were removed.
  Website Settings > Under Construction was set to "Yes" in `blossom_cms`, so robots.txt
  blocks crawlers and the sitemap/`llms.txt` return 404 until launch.
- **Moved to Phase 3:** removing the Countries module (it is wired into Contact Requests) and
  the tour/experience/plan-your-visit messages in Form Settings (they are defined only
  through `config/manage_translations.php`, which Phase 3 removes).
- **Added to Phase 4 SQL:** delete pages 3 (Tours), 4 (Experiences), 5 (Plan Your Visit) and
  11 (Tour Guides) with their page sections and menu entries; delete email templates 2–15;
  drop `site_settings.profit`, `tax`, `license_number`, `license_number_ar`,
  `payment_title`, `payment_title_ar`, `payment_icons`.
- **Added to Phase 5:** delete `views/frontend/holding.php`, set Under Construction back to
  "No" at launch, remove the remaining Alam frontend assets (`assets/frontend/css`, `js`,
  `vendor`, `images/alam`, `xsl`) and the Alam section definitions in
  `config/content_sections.php`.

---

## Phase 3 — Remove integrations and make everything English-only

### 3.1 Google Translation
Remove `Google_translation_service`, `Manage_translation_service`, `Translation_cron`,
`manage/Manage_translations`, `Translation_job_model`, `config/manage_translations.php`,
`helpers/manage_translation_helper.php`, `translation_status_badge.php`,
`manage_language_switcher.php`, `manage_language_switch_modal.php`,
`assets/admin/js/manage-translations.js`, the `translation-cron` and `manage/translations`
routes, `GOOGLE_TRANSLATION_*` constants, and every `useManageTranslations` / locale branch
in the kept modules.

### 3.2 Google Places
Remove `Google_places_service`, `GOOGLE_PLACES_*` and `GOOGLE_MAPS_API_KEY` constants,
`place-autocomplete.js`, and the `booking_places` / `booking_place` endpoints (if Phase 2 has
not already removed them).

### 3.3 WhatsApp
Remove `Whatsapp` controller, `Whatsapp_gateway`, `Whatsapp_template_service`,
`Booking_whatsapp_service`, `manage/Whatsapp_templates`, its views and
`whatsapp-templates.js`, `WHATSAPP_*` constants, `whatsapp/*` routes, WhatsApp short tags
(`config/short_tags.php`, `Short_tags`), WhatsApp references in `EmailService`, admin
`footer.php`, navigation and the frontend language files.

### 3.4 Composer
Change `composer.json` to require only `google/cloud-recaptcha-enterprise` (D4), regenerate
`application/third_party/google_api/` locally, and commit the result. If D4 changes to "no
reCAPTCHA Enterprise", remove `composer.json`, `composer.lock`, `third_party/google_api/` and
`Google_recaptcha` entirely.

### 3.5 English-only
- Kept modules (Pages, Blogs, Blog Categories, FAQs, FAQ Categories, Slider, Customer
  Reviews, Email Templates, Form Settings, Website Settings, Web Page Sections, Miscellaneous
  Contents, Menu, Foot): remove Arabic fields, locale tabs, `_ar` reads/writes and RTL styling.
- `Localized_model`: simplify to plain column reads or remove once nothing extends it (it is
  autoloaded in `config/autoload.php`).
- `config/content_sections.php`: single `en` locale; section-field reads ignore `locale`.
- Delete `application/language/arabic/`; keep `language/english/frontend_lang.php` only if the
  new views use it (otherwise remove it too).
- Routes: drop the `(en|ar)` prefixes, the locale cookie and the locale redirect in
  `Frontend.php`. URLs become exactly those of the new site map (§1.2).
- `Seo` (sitemap, robots, llms) and `Frontend_seo`: remove hreflang/alternate-locale output.

**Done when:** grepping for `_ar'`, `'ar'`, `arabic`, `translation`, `whatsapp`, `places`
returns no functional code, and all kept admin modules save and reload correctly.

**Status: done (2026-09-28), branch `phase-3-english-only`.** Everything in 3.1–3.5 was
removed or converted: the translation stack (services, cron, controller, job model, config,
helper, partials, `manage-translations.js`, routes, `GOOGLE_TRANSLATION_*` and
`GOOGLE_API_CA_BUNDLE` constants), Google Places, WhatsApp (controller, admin module,
gateway, template service, views, JS, routes, constants), `google/cloud-translate` and its
four dependencies, `application/language/arabic/` and `language/english/frontend_lang.php`,
`Localized_model` (the frontend models now extend `SqlModel` and read plain columns), and
the Arabic email view. Every kept admin module is English-only: Arabic fields, locale tabs,
`_ar` reads/writes, RTL styling and translation badges are gone. `Seo`/`Frontend_seo` emit
one un-prefixed URL per record with no hreflang alternates, and the admin "View" links no
longer use `/en/`. The grep above now only matches the Alam frontend assets
(`assets/frontend/js`) and Alam section definitions, which Phase 5 and Phase 6 replace.

Changes to the plan made during this phase:

- **Countries module removed here** (moved from Phase 2). Contact Requests lost its country
  and website-language fields, filters and columns; its listing URL is now
  `index/<sort>/<order>/<keywords>/<offset>`.
- **Form Settings reduced** to the contact form's success message and subject list; the
  tour, experience and plan-your-visit messages went with the translation config.
- **Content sections keep their `locale` column.** Every read and write uses the single `en`
  locale, so no schema change is needed there. The Arabic-draft workflow (copying English
  into `ar` rows and blanking `ar` rows when English changed) was removed.
- **Save button state.** `manage-translations.js` also set the "Saving..." button state. That
  now lives in the shared `.validate` submit handler in `admin.js`, so every
  `[data-save-button]` form gets it, not just the forms that had a language switcher.
- **Slug generator.** `generateSlug()` in `custom.js` lost its Arabic branches and now
  matches `Admin_slug::normalize()` exactly; the `data-slug-language` attribute was removed.
- **Unused `EmailService::currencyUnit()` removed** (booking-only). The email
  `format*()` helpers remain, English-only.
- **Added to Phase 4 SQL:** see the additions below.

---

## Phase 4 — Database clean-up (run manually)

Provide `docs/sql/phase-4-cleanup.sql`, reviewed before execution and run only against
`blossom_cms` after a backup:

- `DROP TABLE` for: `tours`, `tour_*` (all 17), `attractions`, `vehicles`, `discount_codes`,
  `referrals`, `plan_your_visit`, `translation_jobs`, `whatsapp_templates`,
  `ci_sessions_ci2_backup`, `zzz_migration_test`, and `countries` (per D9).
- `ALTER TABLE … DROP COLUMN` for every `_ar` column in the kept tables (`pages`, `blogs`,
  `blog_categories`, `faqs`, `faqs_categories`, `slider`, `customer_reviews`,
  `email_templates`, `form_settings`, `site_settings`).
- Remove tour-specific columns from kept tables (e.g. `form_settings.tour_success`,
  `experience_success`, `plan_success`, `pyt_*`; `site_settings.license_number`, `profit`,
  `tax`, `payment_*`, `currency_unit_ar`).
- `DELETE … WHERE locale = 'ar'` in `web_page_section_fields` and
  `miscellaneous_content_section_fields`.
- Added in Phase 3:
  - `contact_requests`: drop foreign key `fk_country_contact_request`, index
    `contact_requests_country_idx`, and columns `country` and `website`. Do this before
    dropping `countries`; `plan_your_visit.fk_country_pyt` also references `countries`, so
    drop `plan_your_visit` first.
  - `form_settings`: drop `plan_success`, `tour_success`, `experience_success`,
    `pyt_interests`, `pyt_visit_time` and every `_ar` column.
  - `site_settings`: drop `default_language` and `default_bg_ar` (with the other `_ar`
    columns).
  - Email templates 2–15 (Alam plan-your-visit and booking emails) are still in the
    database; delete them as listed under Phase 2.
- Add Blossom fields to `site_settings` (opening hours, map URL, Instagram URL, address note,
  dark/light logos if not covered).
- Empty sample data (bookings, contact requests, login attempts, sessions) before seeding.

**Status: done (2026-09-28), branch `phase-4-db-cleanup`.** `docs/sql/phase-4-cleanup.sql`
was rehearsed twice on a copy of `blossom_cms` (the second run is a no-op), the admin was
smoke-tested against the copy, and the script was then applied to `blossom_cms` after a
backup (`D:\wamp64\backups\blossom_cms-before-phase-4-20260928-131017.sql`, outside the
repository). The database now has 34 tables, no `_ar` columns and only `en` section rows.

Notes from this phase:

- The script is idempotent: column, index and foreign-key changes go through temporary
  `phase4_*` procedures that check `information_schema` first.
- **Blossom fields added to `site_settings`:** `address_note`, `opening_hours` (one
  `Days | Hours` line per day group) and `map_url`, seeded from the `/ci3/` contact page and
  editable under Website Settings > Contact Information. The existing `logo`, `logo_sticky`,
  `logo_white` and `instagram` columns cover the logos and Instagram link.
- The tourism licence line was removed from the email footer along with
  `site_settings.license_number`.
- The Alam section definitions for the deleted pages 3, 4, 5 and 11 are still in
  `config/content_sections.php`; the service skips them because the pages no longer exist.
  They are removed with the other Alam definitions in Phase 5.
- **Not changed (content, not schema):** the four admin accounts, and the Alam blog posts,
  FAQs, reviews, slider, page and section content. These are replaced during content seeding.
  Uploaded Alam image files for the deleted records remain on disk until the Phase 9
  clean-up.

---

## Phase 5 — Frontend foundation

1. **Tailwind v4 pipeline (D5).** Recreate the theme from `ci3/assets/css/site.css` into
   `assets/frontend/css/src/tailwind.css` (`@import "tailwindcss"`, `@theme` tokens for
   colours, fonts, easing, radii, shadows; `@source` for `application/views/frontend` and
   `assets/frontend/js`). Build with the v4 standalone binary into
   `assets/frontend/css/app.css`. Remove the Alam `tailwind.config.js`. Compare the rebuilt
   CSS against `site.css` page by page.
2. **Assets.** Move fonts, logos, jQuery 4, Swiper, PhotoSwipe and Font Awesome from
   `ci3/assets/` into `assets/frontend/`. Remove Alam frontend JS/vendor files that are no
   longer used (select2, intl-tel-input, htmx, datepicker, etc., after checking usage). Do
   not copy `fontawesome-download/`.
3. **Layout partials.** Split the duplicated markup into formatted, readable partials:
   `frontend/layout/head.php`, `header.php` (primary nav from Menu module, phone, Book
   button), `footer.php` (Foot columns, address, hours, legal links), `cta_band.php`,
   `breadcrumb.php`, and card partials (`service_card`, `artist_card`, `offer_card`,
   `article_card`, `gallery_item`, `testimonial`). Replace every `<?=` with `<?php echo` and
   escape output.
4. **Controller.** Replace `Frontend.php` with a lean English-only controller (or split into
   `Services`, `Artists`, `Gallery`, `Offers`, `Blog`, `Pages` frontend controllers) that
   loads site settings once, uses `Frontend_seo` for meta/OG/canonical, and returns proper
   404s for unknown slugs.
5. **Routes.** Match §1.2 exactly (`/services/{slug}`, `/artists/{slug}`, `/blog/{slug}`,
   etc.). Keep `robots.txt`, `sitemap.xml`, `llms.txt`.
6. **JS.** Port `site.js` and `interactions.js` into `assets/frontend/js/`, formatted, with
   hard-coded data replaced by server-rendered markup or JSON endpoints.

**Done when:** the header, footer and an empty home page render with the Blossom design at
desktop and mobile widths.

**Status: done (2026-09-28), branch `phase-5-frontend-foundation`.** The Tailwind v4.3.3
CLI was installed at `D:\wamp64\www\tailwindcss-v4-windows-x64.exe` (the existing
`tailwindcss-windows-x64.exe` is v3.4.17) and the theme, fonts, base, components and CI3
rules were rebuilt into `assets/frontend/css/src/tailwind.css`. The reference fonts, logos,
jQuery 4.0.0, Swiper 14.0.7, PhotoSwipe 5.4.4 and Font Awesome 7.3.1 are under
`assets/frontend/`; the Alam JS, vendor files, `images/alam`, `tailwind.config.js` and the
unused `Frontend_presenter` were removed. `Frontend_layout` renders every page between the
head, header and footer partials; `site.js` was ported. The header and footer were compared
with `/ci3/` at 1440px and 390px (headless Chrome), and the mobile menu was exercised.
`docs/sql/phase-5-content.sql` (content only) renamed the About and FAQ slugs to `about` and
`faq`, added Web Pages records for Services, Artists, Gallery, Offers and Terms, set up the
header menu and Foot menus one (Salon) and two (legal), and set the Blossom logos, address,
opening hours (with Sunday), intro and footer headings.

Changes to the plan made during this phase:

- **Routes and card partials move to Phase 6.** Only `/` (an empty shell with the CTA band)
  and the 404 page exist. Each route of §1.2, its card partial (`service_card`,
  `artist_card`, …) and its part of `interactions.js` are added with the page that uses
  them, so nothing is built without the data it renders. Until then, those URLs return the
  Blossom 404 page.
- **The holding page became the under-construction page.** Instead of deleting it, the
  Alam rule was restored: with Under Construction "Yes", visitors get
  `views/frontend/under_construction.php` (503, noindex) and signed-in administrators see
  the real site. It stays "Yes" until launch.
- **Kept:** `assets/frontend/xsl/sitemap.xsl` (the sitemap still uses it), the Alam
  section definitions in `config/content_sections.php` (replaced page by page in Phase 6,
  when the Blossom sections are defined), and the upload folders of the dropped tour
  modules (Phase 9).
- **Footer mapping:** first column = featured services (Services > Featured, heading
  `foot_col_1`), second = Foot menu one (`foot_col_2`), third = Website Settings contact
  details (`foot_col_4`); legal links = Foot menu two. Footer links use the page's menu
  name, so "About us", "Our artists" and "All services" read "About", "Artists" and
  "Services".
- The optional sticky logo in Website Settings has no counterpart in the design and is not
  used; the default sharing image is now `assets/frontend/images/brand/og-default.jpg`.

---

## Phase 6 — Frontend pages on CMS data

Convert one page at a time; compare against the `/ci3/` version at desktop and mobile.
Each page brings its own route, its card partials, its part of `interactions.js` and (where
it uses Web Page Sections) its Blossom section definitions in
`config/content_sections.php`, replacing the Alam ones (moved here from Phase 5).

1. Home — Slider (hero), Web Page Sections (intro blocks, how-booking-works, location),
   featured Services, Gallery (latest), Artists, featured Offers, Customer Reviews,
   Instagram (D6), CTA.
2. Services listing and service detail (with related gallery, FAQs, add-ons, "Book this").
3. Artists listing and artist detail.
4. Gallery with category filter + PhotoSwipe.
5. Offers.
6. About (Web Page Sections).
7. Journal listing, category filter, article detail (Blogs).
8. FAQ (grouped by FAQ Categories).
9. Contact — settings-driven address/hours/map (D7); form saved to `contact_requests`,
   reCAPTCHA verified, notification + auto-reply through `EmailService` and Email Templates,
   Post/Redirect/Get, server-side validation mirrored by `form-validate`.
10. Legal pages (Pages module).
11. Search — server-side search across services, offers and journal articles.
12. Book (D1) — the 5-step wizard reads services/artists/offers from the database and submits
    an appointment request (saved to `appointments`, reCAPTCHA, emails to salon and client,
    confirmation screen with reference). Pre-selection via `?service=`, `?artist=`, `?offer=`.
13. 404 page in the Blossom design.

Remove the account/login/register/appointment mock-up pages from the build (D2).

**Status: done (2026-09-28), branch `phase-6-frontend-pages`.** Every page of §1.2 except the
account mock-ups is built on CMS data and was compared with `/ci3/` at 1440px and 390px
(headless Chrome); the admin forms each page reads from were saved back unchanged. The
account, login, registration and appointment-lookup pages were never routed (D2), so they
return the 404 page. One commit per page:

| Route | Built from | Notes |
|-------|-----------|-------|
| `/` | Slider, Web Page Sections (page 1), Services, Gallery, Artists, Offers, Customer Reviews | Hero heading `First line\|Highlighted line`; Swiper `rewind` (the reference's loop breaks with 3 slides); Instagram band = Web Page Section + the Instagram link (D6) |
| `/services`, `/services/{slug}` | Services, Service Categories, Miscellaneous Contents (`service_page`, `nail_shapes_finishes`) | New: "Often booked with this" (`service_related`) and "Show shapes and finishes"; the service gallery shows the images linked to that service |
| `/artists`, `/artists/{slug}` | Artists, Customer Reviews, Miscellaneous Contents (`artist_page`) | First artist is the lead card |
| `/gallery` | Gallery | Chips + `?category=`, PhotoSwipe, overlay header |
| `/offers` | Offers | Featured first; validity from the offer dates or note |
| `/about` | Web Page Sections (page 2), Artists | |
| `/blog`, `/blog/{slug}` | Blogs, Blog Categories | Latest post leads; chips + `?category=`; `/blog/category/{slug}` redirects there (301); new optional "Related service" per post (`blogs.blog_service_id`); `Blog_model` rewritten |
| `/faq` | FAQs, FAQ Categories | `<details>` accordions, FAQPage JSON-LD |
| `/contact` | Website Settings, Form Settings, Contact Requests, email template 1 | `Contact_form` library (see below) |
| `/privacy-policy`, `/cancellation-policy`, `/terms` | Pages (page text), Web Page Sections | Numbered contents built from the `<h2>` headings |
| `/book`, `/book/confirmed` | Services, Artists, Offers, Website Settings opening hours, Appointments, email template 2 | Booking request (D1), see below |
| `/search` | Services, Offers, Artists, Blogs, FAQs | Server-side; every word must match; `noindex` |
| 404 | Menu | No reference design: hero + the reference's dashed "not found" card with search and menu links |

Public forms (contact and booking) share one pattern: a one-use session token (global CSRF
is off), server-side validation as the authority, `Google_recaptcha::verify()` (accepts
forms in `development` while reCAPTCHA is not configured, rejects them in every other
environment), then the salon notification (Website Settings > notification emails, built in
code) and the client email from a managed Email Template. The contact form uses
Post/Redirect/Get; the booking wizard posts with AJAX and gets JSON.

The booking wizard (`libraries/Booking_request.php`, `Booking_schedule.php`,
`js/booking.js`) sends an appointment **request**: days and start times come from Website
Settings > Opening Hours (21 days ahead, 30-minute steps, at least an hour's notice, the
last start leaves room for the whole visit) and a chosen artist's working days. The
reference's made-up "unavailable" slots, "Manage appointment", "Add to calendar" and design
notes were left out. The server re-checks every choice and saves the appointment with a
snapshot of its services in one transaction; `/book/confirmed` shows the request from the
session, never from a reference in the URL.

Content and schema, all already run locally (`docs/sql/`, run in this order on another
environment; the content files refer to uploads, so on another environment upload the
images through the admin instead):

- Schema, additive and safe to re-run: `phase-6-services.sql` (`services.service_show_shapes`,
  `service_related`), `phase-6-journal.sql` (`blogs.blog_service_id`).
- Content: `phase-6-services-content.sql`, `-artists-`, `-gallery-`, `-offers-`, `-home-`,
  `-about-`, `-journal-`, `-faq-`, `-contact-`, `-legal-`, `-booking-content.sql`. They
  replace the Alam blog posts, blog and FAQ categories and FAQs, add the Book page (Web
  Pages 42, not in a menu) and email template 2, and give email template 1 Blossom wording.

Changes to the plan made during this phase:

- **No FAQs on service pages.** Item 2 listed them, but the reference service pages have
  none; the questions live on `/faq` only.
- **Shared frontend pieces** were added instead of per-page copies: `page_hero` (with
  optional icon/external actions), `visit_details`, `category_chips` +
  `js/category-filter.js` (gallery and journal), `post_card`/`post_meta`, and the
  `.article-body`, `.legal-body` and `.select-chevron` components in `tailwind.css`.
- **Search** also covers artists and FAQs (the reference searched services, the team and
  the journal).
- **Contact form:** one "Name" field (as in the reference); the first word is stored as
  `first_name` and the rest as `last_name`, so Contact Requests did not change. The
  subject list and success message come from Form Settings.
- **Placeholder notes:** notes the salon should see ("sample articles", "Placeholder
  wording") were kept as editable page-section text where they are content; design-only
  notes ("Map embed to be added", "this is a design preview") were dropped.

Carried over to Phase 7 (all handled there, except the launch settings, which are on its
launch checklist):

- The 15 unused Alam Miscellaneous Contents sections (`get_in_touch`, `where_we_are`,
  `call_or_message`, `email_us`, `still_need_help`, `browse_tours`, `talk_to_us`,
  `plan_with_us`, `featured_posts`, `privacy_policy_card`, `cancellation_policy_card`,
  `need_help_choosing`, `prefer_to_talk`, `tour_navigation`, `tour_price_details`): remove
  their definitions and rows. The Alam "Sample Page" (Web Pages 34) is also still there.
- Launch settings: a sender email (Website Settings is empty, so no email is sent until it
  is set), reCAPTCHA Enterprise keys for Blossom (D4), the Blossom favicon (still the Alam
  one), and the Instagram/Facebook links (empty, so the social icons are hidden).
- JSON-LD beyond what the pages already output (Service, FAQPage, Article): the home
  LocalBusiness and BreadcrumbList; the sitemap entries for the new routes.
- Status-change emails for appointments (Manage > Appointments sends none yet).

---

## Phase 7 — SEO, email and content

- `Frontend_seo`: titles, meta descriptions, canonical, OG/Twitter tags, JSON-LD
  (`BeautySalon`/`NailSalon` LocalBusiness with address, phone, opening hours; `Service`;
  `BlogPosting`; `FAQPage`; `BreadcrumbList`).
- `sitemap.xml` from Pages, Services, Artists, Offers, Blogs; `robots.txt`; `llms.txt`.
- Email Templates for: contact notification, contact auto-reply, appointment request
  (salon), appointment request received (client), appointment confirmed/cancelled (if
  status emails are wanted). Use `EMAIL_*_FORMAT` constants. *(Phase 6 added the contact
  auto-reply (template 1) and "request received" (template 2); both salon notifications
  are built in code by `Contact_form` and `Booking_request`. Still open: status-change
  emails, and whether the salon notifications should become managed templates.)*
- Seed content from the `/ci3/` views: 14 services with prices/durations, 3 artists, gallery
  images, 6 offers, 6 journal articles, FAQs, testimonials, legal pages, settings. A one-off
  seed SQL in `docs/sql/seed.sql` is acceptable. *(Done page by page in Phase 6: the
  `docs/sql/phase-6-*-content.sql` files. Still open: one combined seed for a fresh
  environment, including the uploaded images.)*
- Replace placeholder artists/offer validity once the salon supplies real data.

**Status: code done (2026-09-28), branch `phase-7-seo-email-content`; the launch checklist
below waits on the salon.** Decisions taken with the owner: clients are emailed when a
request is marked Confirmed or Cancelled; the salon's own notifications stay built in code.

- **Clean-up.** The 15 unused Alam Miscellaneous Contents sections and the Alam Sample Page
  (Web Pages 34) were removed (`docs/sql/phase-7-cleanup.sql`, run after a backup of the
  three affected tables).
- **Structured data.** Every page prints one JSON-LD `@graph` from `Frontend_layout`: the
  salon as a `NailSalon` (street, postal code, town and country parsed from the address;
  `openingHoursSpecification` from Website Settings > Opening Hours; logo, map link and
  social profiles), the `WebSite`, the `WebPage` typed per page (`CollectionPage`,
  `AboutPage`, `ContactPage`, `FAQPage` with its questions, `ProfilePage`,
  `SearchResultsPage`) and a `BreadcrumbList` taken from the page's breadcrumb. Record pages
  add a `Service`, `BlogPosting` or `Person` node that points to the salon; the views'
  separate snippets were removed. The Alam `TravelAgency` node and licence identifier are
  gone. Journal posts get `og:type` article with `article:*` tags; pages with an image use
  a large Twitter card. Payment methods are not claimed (not confirmed by the salon).
- **Crawler files.** `sitemap.xml` lists the routed Web Pages, the 14 services (with
  images), the artists and the posts; blog category URLs (now redirects) were dropped.
  `llms.txt` describes the salon (address, phone, hours, booking), the services with prices
  and durations, the offers, the team, the journal and the pages. `robots.txt` also keeps
  `/book/confirmed` out.
- **Emails.** Email templates 3 (Confirmed) and 4 (Cancelled) with the appointment short
  tags (`docs/sql/phase-7-status-emails.sql`), sent by `Booking_request::sendStatusEmail()`
  when Manage > Appointments changes a request to one of those statuses. A failed email is
  reported to staff as an error.
- **Favicon.** The public site now prints the Website Settings favicon (it printed none);
  a placeholder italic "B" monogram (`assets/frontend/images/brand/favicon.png`) replaced
  the Alam icon until the salon supplies one.
- **Seeding.** `docs/sql/README.md` lists every script in run order, marks those that
  replace existing rows, and names the upload folders to copy. Copying the local database
  is the recommended way to build staging/production.
- The opening-hours parser moved to `frontend_parse_opening_hours()` (used by the booking
  schedule and the structured data).

**Launch checklist (needs the salon):**

- Website Settings: sender email (no email is sent until it is set), a site email,
  notification emails, the Instagram and Facebook links (the icons stay hidden while
  empty), and the real favicon.
- reCAPTCHA Enterprise keys for Blossom (D4) in the production constants; without them the
  live contact and booking forms reject every submission.
- Real artist profiles for the two placeholder "Team member" artists, real offer validity
  dates (the offers show a placeholder note), real customer reviews, and the salon's own
  journal posts in place of the sample articles.
- The legal pages, cancellation policy and FAQ answers marked "Placeholder", which need the
  salon's policies (and a legal review for the privacy policy under GDPR).
- Payment methods, if they should be listed (FAQ and structured data).

---

## Phase 8 — Optional: real booking and accounts (only if D1/D2 change)

- Artist working hours and exceptions (reference: Tour Guide Availability + Tour Slots).
- Slot calculation from service duration and artist availability.
- Customer accounts reusing the admin auth patterns (password hashing, reset, throttling,
  remember tokens) in a separate `customers` table.
- Account area: upcoming/past appointments, cancel/reschedule within policy.
- Appointment reports (reuse the shared report partials if kept).

**Status: real-time booking done (2026-09-28), branch `phase-8-realtime-booking`.** The owner
chose real-time booking only (D1 changed); artist working hours and exceptions, customer
accounts (D2 stays deferred) and appointment reports were not built.

- **Availability** (`libraries/Booking_availability.php`). A visit is a run of back-to-back
  segments, one per chosen service in the order chosen. Each needs an artist who offers
  the service, works that weekday (the existing Manage > Artists working days, within the
  salon's opening hours) and has no overlapping booking; every appointment except a
  Cancelled one holds its artists' time. With "any artist" a segment keeps the previous
  segment's artist when possible, otherwise the first free artist in team order.
  `Booking_schedule` now holds only the salon rules (hours, window, slot grid, lead time).
- **Owner's decisions:** placeholder artists are bookable (replace or disable them before
  launch — on the Phase 7 launch checklist); services that no single artist offers are
  booked back to back with different artists (for example the "Nails + hair" offer: Ewa,
  then the beauty therapist).
- **Wizard.** The date step loads free days and times from `/book/availability`; artists
  who do not offer every chosen service are shown but cannot be chosen, and a preselected
  one falls back to "any". "Send request" became "Confirm booking".
- **Booking.** The chosen time is checked again under a MySQL named lock
  (`blossom_booking`) and saved as Confirmed, with each service's artist and start time
  (`docs/sql/phase-8-booking.sql` adds `service_artist_id`, `service_artist_name` and
  `service_start_time` to `appointment_services`). A time taken meanwhile returns the
  visitor to the date step with a message.
- **Emails.** The client gets email template 3 (confirmed) at once, now listing each
  service with its time and artist (`{{schedule}}`); the "request received" template 2
  was removed. The salon notification reads "New booking". Cancelling in Manage >
  Appointments still emails template 4 and frees the time.
- **Wording.** The Book page notes and confirmation, the home page's booking step, the
  artist page labels and the cancellation email no longer speak of requests
  (`docs/sql/phase-8-booking-content.sql`). Manage > Appointments shows each service's
  start time and artist.
- Also fixed: the wizard's date step overflowed its column on phones (the scrolling day
  row stretched the grid column).

Tested over HTTP: slots disappear as each artist fills (including overlapping starts),
mixed services split across artists, an artist is refused for a service they do not
offer, two simultaneous bookings of the same slot leave exactly one, and cancelling frees
the time; the wizard was run end to end in a headless browser at desktop and phone
widths.

---

## Phase 9 — QA, clean-up and deployment

- `php -l` on every modified PHP file; JS syntax check on modified JS.
- Admin: every module's list/search/sort/add/edit/validation/status/delete flow.
- Frontend: every route at desktop and mobile widths, keyboard navigation, focus states,
  skip link, form validation and success states, 404s.
- Grep for leftovers: `alam`, `madinah`, `munawara`, `tour`, `riyadh`, `SAR`, `whatsapp`,
  `translation`, `_ar`.
- Delete `/ci3/` and any unused Alam assets (`assets/frontend/xsl`, unused vendor files,
  uploaded Alam images in `assets/uploads/`).
- Production: `.htaccess_prod`, production database config, HTTPS base URL, SMTP
  credentials, reCAPTCHA production keys, cron jobs (none expected unless Phase 8).

**Status: clean-up done (2026-09-30), branch `phase-9-qa-cleanup`; the signed-in QA and the
production set-up are still open.**

- **Checks.** `php -l` passes on all 1,299 tracked application PHP files and `node --check`
  on the 27 project JS files. The leftover search finds nothing in the code apart from the
  Artists controller's note on where it was ported from, and nothing in the `blossom_cms`
  text columns.
- **Security.** CKFinder (`assets/ckfinder/config.php`) let anyone list, upload, rename and
  delete files in `assets/uploads/`: `CheckAuthentication()` returned `true`. It now allows
  only a signed-in, enabled administrator, read from CodeIgniter's database session (the
  same cookie, IP match, expiry and `admin_users` check as the admin area). Tested with
  anonymous, forged, signed-out, unknown-admin, other-IP and expired sessions. The Alam
  reCAPTCHA demo page (`/recaptcha-enterprise`, whose public `verify` endpoint created
  billable assessments) was removed with its route and robots line.
- **Configuration.** The session and CSRF cookie names no longer use the Alam name
  (`blossom_session`; signed-in administrators are signed out once). `index.php` accepts
  `CI_ENV=staging` (it returned "environment not set correctly"). `.htaccess_prod` also
  blocks `docs/` and `PROJECT_PLAN.md`.
- **Dead code.** Removed the tour-era admin CSS (Guide Availability, vehicle prices, tour
  image upload, booking detail cards, reports and their print rules, itinerary action),
  the unused multiselect sorter (`selected-items-sort.js`, `$useSortableJs`), the
  itinerary and report-screen branches in `admin.js`, the payment-link copy handler in
  `custom.js`, the legacy `images/…` path in `Frontend_seo::imagePath()`, and the
  CKEditor and CKFinder sample folders.
- **Files.** Deleted `/ci3/` and the Alam uploads: the attractions, experiences, guides,
  tour-guide-licenses, tour-guides, tour-images, tour-itineraries, tours and vehicles
  folders, 163 unreferenced Alam images in the folders still in use (all dated before the
  baseline), and the Alam CKFinder uploads and thumbnails (the three images used by the
  journal posts were kept). `assets/` went from 151 MB to 29 MB. `assets/frontend/xsl` was
  kept: it is the sitemap's stylesheet.

- **Browser QA (2026-09-30, Chrome, with an administrator session).** All 24 admin
  screens and 45 public URLs (every page, service, artist and journal post, the category
  filters, the booking pre-selections, search and the legal pages) return 200 with no PHP
  errors or console exceptions; unknown URLs return the 404 page, and the sitemap and
  llms.txt stay hidden while Under Construction is on. No page scrolls sideways at 390px
  or 768px (public and admin). Every public page has one `<h1>`, the skip link, `#main`
  and `alt` on every image; the skip link appears on focus and controls show a focus
  ring. The menu manager (SortableJS), the blog editor (CKEditor, and CKFinder with the
  new session check) and Select2 load. Validation: the contact form marks empty and
  malformed fields and keeps the button enabled; the booking wizard keeps Continue
  disabled until a service is chosen and loads 21 days (Sundays closed) with times that
  follow the lead time; an empty service form marks its required fields; Website Settings
  opens the collapsed section of an empty required field and focuses it. Only blocked
  submissions were tried, so nothing was saved or emailed.

- **Saving flows (2026-09-30, emails captured by the local mail log in `email_logs/`).**
  Contact form: request saved, salon notification and client reply (template 1) logged
  with every short tag filled. Booking: BLM-3849 saved as Confirmed with its service,
  artist and start time, the salon "New booking" and client "confirmed" (template 3,
  with the schedule) emails logged, and the artist's overlapping times removed from
  `/book/availability`. Manage > Appointments: internal note added; Cancelled sent
  template 4 and freed the time. Services: duplicate (disabled copy, unique slug), edit
  (Saving… state), status toggle, bulk delete with no rows left behind. Gallery: Dropzone
  upload (random file name, caption from the file name), image replacement (old file and
  thumbnails removed), drag-sort request saved and restored, single delete removed the
  file. Contact Requests: delete and its empty state. All test records were removed
  afterwards; the appointment through SQL, as appointments have no delete action.
- **Fixed during QA.** Multi-word admin searches returned Apache's 403: Apache 2.4.56+
  refuses a rewritten query string that contains spaces (AH10411), and the front
  controller copied the path into `index.php?url=`. Both `.htaccess` files now use the
  `B` flag (CodeIgniter reads `REQUEST_URI`, so nothing else changes). Saving Website
  Settings stores the phone as `+48512129654`, which the site then printed unspaced;
  `frontend_phone_display()` now formats it (`+48 512 129 654`) in the layout and
  llms.txt, while `tel:` links and structured data keep the compact form.

- **Remaining modules (2026-09-30).** Add, edit and delete were run with labelled test
  data on Artists, Offers, Blog Categories, Blogs (with an image upload), FAQ Categories,
  FAQs, Customer Reviews, Service Categories, Gallery Categories, Image Sliders, slider
  images (with an image upload) and Web Pages; Email Templates, Miscellaneous Contents,
  Web Page Sections, Main Menu, the footer menus, Form Settings and an administrator
  profile were saved unchanged. Every save succeeded. A database snapshot taken before
  and compared after shows the same rows and IDs, and no image files were added or left
  behind. Saving a menu renumbers `menu_order` / `menu_order_one` into a clean sequence
  (several pages shared a value); the menus shown on the site were unchanged, and the
  earlier values were restored afterwards.
- **Also changed.** Manage > Appointments and the dashboard now speak of appointments
  and bookings instead of requests (visible text only). The skip link focuses `<main>`
  (`site.js`, with no outline on `main[tabindex="-1"]`). The slider-image thumbnail link
  was announced as "Edit …" but opens the image preview; it now reads "Preview …", like
  the other listings.

**Still open:**

- Website Settings holds demo placeholders (2026-09-30): Website URL
  `http://ctech-cms.com`, Email `hello@example.com`, Sender Email `no-reply@example.com`,
  Facebook and Instagram the platforms' home pages. Replace them with the salon's own
  details before launch (Phase 7 checklist).
- Not tested: creating an administrator (it needs a password typed on the site) and the
  service category delete guard (a failure would delete real data).
- Production: the production constants file (database, encryption key, SMTP, reCAPTCHA),
  `.htaccess_prod` uploaded as `.htaccess` with the canonical host chosen, HTTPS confirmed.
  `base_url` is built from `$_SERVER['HTTPS']`; if the host ends TLS at a proxy, check that
  links come out as `https://`. No cron jobs are needed.
- The Phase 7 launch checklist (salon content, keys and policies).

---

## Appendix — Files to keep, adapt, remove (quick reference)

**Keep as-is (review only):** `SqlModel`, `AdminLoginAttemptModel`, `AdminRememberTokenModel`,
`PasswordResetModel`, `Imagethumb`, `Ckeditor`, `Ckfinder`, `Encrypt`, `Admin_record_sorter`,
`Page_menu_hierarchy`, `image_helper`, `admin_pagination_helper`, admin partials, `admin.js`,
`color-picker.js`, `icon-picker.js`, `file-upload.js`, `uploads.js`, `avatar-editor.js`,
`sortable-records.js`, `menu-manager.js`, `content-sections.js`, `website-settings.js`.

**Adapt (English-only / new content):** Pages, Blogs, Blog_categories, Faqs, Faqs_categories,
Slider, Sliders, Web_page_sections, Miscellaneous_contents, Content_section_sync, Menu, Foot,
Customer_reviews, Contact_requests, Email_templates, Email_test, Form_settings,
Website_settings, Admins, Login, manage/Home, Seo, `EmailService`, `Short_tags`,
`Content_section_service`, `Frontend_seo`, `Frontend_presenter`, `Google_recaptcha`,
`Recaptcha_enterprise`, `SiteModel`, `Blog_model`, `Faq_model`, `MenuModel`, `FootModel`,
`Webpage_model`, `Web_page_section_model`, `Miscellaneous_content_model`, `Contact_model`,
`Seo_model`, `Dashboard_model`.

**Remove:** everything listed in Phases 2 and 3.
