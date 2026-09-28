-- ---------------------------------------------------------------------------
-- Phase 8 — Real-time booking content (PROJECT_PLAN.md, decision D1 changed)
--
-- Online bookings are now confirmed straight away, so:
--   * the Book page (Web Pages 42) notes and confirmation texts, the home
--     page's "how booking works" step 4 and the artist page labels no longer
--     speak of requests or a "not connected" diary;
--   * email template 3 (appointment confirmed) is sent when a booking is
--     made, and now lists each service with its time and artist
--     ({{schedule}});
--   * email template 2 ("request received") is no longer sent and is removed;
--   * email template 4 (cancelled) no longer speaks of a request.
--
-- Content only. Safe to run again; it overwrites these texts.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `web_page_section_fields` f
JOIN `web_page_sections` s ON s.`id` = f.`section_id`
SET f.`field_value` = CASE f.`field_key`
    WHEN 'schedule_note' THEN 'These are the free times in the salon’s diary right now. Your booking is confirmed as soon as you finish.'
    WHEN 'review_note' THEN 'Nail art is priced per nail, so the final amount is agreed with you at the table. Payment is taken at the salon — nothing is charged online.'
END
WHERE s.`page_id` = 42 AND s.`section_key` = 'wizard' AND f.`field_key` IN ('schedule_note', 'review_note');

UPDATE `web_page_section_fields` f
JOIN `web_page_sections` s ON s.`id` = f.`section_id`
SET f.`field_value` = CASE f.`field_key`
    WHEN 'heading' THEN 'Your appointment is confirmed'
    WHEN 'contents' THEN 'We’ve got you booked in and look forward to seeing you.'
    WHEN 'note' THEN 'Need to change or cancel? Call the salon as early as you can and quote your reference.'
END
WHERE s.`page_id` = 42 AND s.`section_key` = 'confirmation' AND f.`field_key` IN ('heading', 'contents', 'note');

UPDATE `web_page_section_fields` f
JOIN `web_page_sections` s ON s.`id` = f.`section_id`
SET f.`field_value` = 'Confirmed straight away by email'
WHERE s.`page_id` = 1 AND s.`section_key` = 'booking' AND f.`field_key` = 'step_4_text';

UPDATE `miscellaneous_content_section_fields` f
JOIN `miscellaneous_content_sections` s ON s.`id` = f.`section_id`
SET f.`field_value` = CASE f.`field_key`
    WHEN 'cta_text' THEN 'Choose a service and a time that suits you — your booking is confirmed straight away.'
    WHEN 'days_note' THEN 'Book online to see the free times on these days.'
END
WHERE s.`section_key` = 'artist_page' AND f.`field_key` IN ('cta_text', 'days_note');

UPDATE `email_templates` SET
    `subject` = 'Your appointment is confirmed: {{date}} at {{time}}',
    `heading` = 'Your appointment is confirmed',
    `contents` = '<p>Hi {{first_name}},</p>\n\n<p>Thank you for booking with Blossom Ewa Mazur — your appointment is confirmed. We look forward to seeing you.</p>\n\n<p><strong>Reference:</strong> {{reference}}<br />\n<strong>Date:</strong> {{date}}<br />\n<strong>Your visit:</strong><br />\n{{schedule}}<br />\n<strong>Time needed:</strong> {{duration}}<br />\n<strong>Estimated total:</strong> {{estimated_total}}</p>\n\n<p><strong>Where:</strong> 2 Piekarska Street, 38-300 Gorlice — on the floor above the 5.10.15 children’s store.</p>\n\n<p>If you can no longer make it, please call the salon as early as you can and quote your reference.</p>\n\n<p>See you soon,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE `id` = 3;

DELETE FROM `email_templates` WHERE `id` = 2;

-- Email template 4: a cancelled booking is no longer a "request".
UPDATE `email_templates` SET
    `subject` = 'Your appointment {{reference}} has been cancelled',
    `contents` = '<p>Hi {{first_name}},</p>\n\n<p>Your appointment {{reference}} on {{date}} at {{time}} ({{services}}) has been cancelled.</p>\n\n<p>If this is unexpected, or you would like another time, please call the salon or book again on our website — we would be happy to find you a slot.</p>\n\n<p>Kind regards,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE `id` = 4;
