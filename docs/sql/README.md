# Database scripts

The Blossom database (`blossom_cms`) started as a copy of the Alam database (`ci_cms`) and
was changed phase by phase with the scripts in this folder. Every script is plain SQL for
MySQL 8. Read a script's header before running it.

> **Content scripts marked "Replaces" delete every existing row in their modules before
> inserting the reference content.** They are for building a database, never for one where
> the salon has entered real services, artists, gallery images, reviews, offers, posts or
> FAQs. Every page-content script also writes its page's texts, so re-running one resets
> any wording edited in the admin since.

Never run any of them against `ci_cms`, the untouched Alam copy.

## Building another environment

There are two ways to bring a staging or production database up to date.

**Copy the local database (recommended).** Export `blossom_cms` with
`mysqldump --routines --triggers`, import it on the server, then copy the upload folders
listed below. This carries every admin edit made since the scripts were written.

**Replay the scripts.** Start from a copy of `ci_cms` and run the scripts in this order.
Back up first: `phase-4-cleanup.sql` drops columns and tables.

| # | Script | What it does | Replaces |
|---|--------|--------------|----------|
| 1 | `phase-1.sql` | Creates the salon tables (services, artists, gallery, offers, appointments). | |
| 2 | `phase-4-cleanup.sql` | **Destructive.** Drops the Alam `_ar` columns, the tour/booking tables and the old Arabic section fields; adds the Blossom Website Settings columns. Signs every administrator out. | |
| 3 | `phase-5-content.sql` | Page records for the listings and terms, header and footer menus, logos, address, opening hours. | |
| 4 | `phase-6-services.sql` | Schema: `services.service_show_shapes`, `service_related`. | |
| 5 | `phase-6-journal.sql` | Schema: `blogs.blog_service_id`. | |
| 6 | `phase-6-services-content.sql` | Service categories, the 14 services, add-ons, related services, Nail Shapes & Finishes. | Yes |
| 7 | `phase-6-artists-content.sql` | Gallery categories and images, the artists and their services, placeholder reviews, the Artists page. | Yes |
| 8 | `phase-6-gallery-content.sql` | Gallery page hero, button and call to action. | |
| 9 | `phase-6-offers-content.sql` | The offers and their services, the Offers page. | Yes |
| 10 | `phase-6-home-content.sql` | Home hero slides, featured services, home sections and meta. | Hero slides, home sections |
| 11 | `phase-6-about-content.sql` | About page. | |
| 12 | `phase-6-journal-content.sql` | Journal page, blog categories and the six articles. | Yes |
| 13 | `phase-6-faq-content.sql` | FAQ page, FAQ categories and questions. | Yes |
| 14 | `phase-6-contact-content.sql` | Contact page and email template 1. | |
| 15 | `phase-6-legal-content.sql` | Privacy, cancellation and terms pages. | Page text |
| 16 | `phase-6-booking-content.sql` | Book page (Web Pages 42) and email template 2 (removed again by script 20). | |
| 17 | `phase-7-cleanup.sql` | Removes the unused Alam content sections and the sample page. | |
| 18 | `phase-7-status-emails.sql` | Email templates 3 (confirmed) and 4 (cancelled). | |
| 19 | `phase-8-booking.sql` | Schema: each appointment service's artist and start time (`appointment_services`). | |
| 20 | `phase-8-booking-content.sql` | Real-time booking wording (Book page, confirmation, home booking step, artist pages), email templates 3 and 4, removes template 2. | Those texts |
| 21 | `phase-8-artist-hours.sql` | Schema: `artist_hours` and `artist_time_off`; turns each artist's working days into full working days. | |

Page section definitions (`application/config/content_sections.php`) are keyed by the page
IDs these scripts create (1, 2, 6, 7, 8, 9, 10, 37–42), so keep them when seeding.

## Uploaded files

Uploads are not in git (see `.gitignore`); the content scripts only store their file
names. Copy these folders from the local site to the server, or upload the images again
through the admin:

- `assets/frontend/images/` — every subfolder except `brand/` (which is in git):
  `services`, `artists`, `gallery`, `offers`, `pages`, `content-sections`, `blogs`,
  `slider`, `logo`, `customer-reviews`, `admins`
- `assets/uploads/images/` — images inside journal articles (CKEditor/CKFinder)

Resized copies (`*_WIDTHxHEIGHT.*`) are recreated on demand, so they can be left behind.
Some folders still hold Alam uploads; those are removed in Phase 9.

## Not in these scripts

Credentials and keys (database, SMTP, reCAPTCHA, encryption) live in
`application/config/<ENVIRONMENT>/constants.php`, never in the database or in git. The
launch settings the salon must supply are listed under Phase 7 in `PROJECT_PLAN.md`.
