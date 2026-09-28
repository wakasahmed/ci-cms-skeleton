-- ---------------------------------------------------------------------------
-- Phase 7 — Appointment status emails (PROJECT_PLAN.md)
--
-- Email templates 3 and 4: sent to the client when staff change a request's
-- status to Confirmed or Cancelled in Manage > Appointments. They use the
-- appointment short tags (config/short_tags.php). Safe to run again; an
-- existing template is left as edited in the admin.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `email_templates` (`id`, `name`, `subject`, `heading`, `contents`)
SELECT
    3,
    'Appointment Confirmed (Customer Email)',
    'Your appointment is confirmed: {{date}} at {{time}}',
    'Your appointment is confirmed',
    '<p>Hi {{first_name}},</p>\n\n<p>Good news — your appointment at Blossom Ewa Mazur is confirmed. We look forward to seeing you.</p>\n\n<p><strong>Reference:</strong> {{reference}}<br />\n<strong>Services:</strong> {{services}}<br />\n<strong>Artist:</strong> {{artist}}<br />\n<strong>When:</strong> {{date}} at {{time}}<br />\n<strong>Time needed:</strong> {{duration}}<br />\n<strong>Estimated total:</strong> {{estimated_total}}</p>\n\n<p><strong>Where:</strong> 2 Piekarska Street, 38-300 Gorlice — on the floor above the 5.10.15 children’s store.</p>\n\n<p>If you can no longer make it, please call the salon as early as you can and quote your reference.</p>\n\n<p>See you soon,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE NOT EXISTS (SELECT 1 FROM `email_templates` WHERE `id` = 3);

INSERT INTO `email_templates` (`id`, `name`, `subject`, `heading`, `contents`)
SELECT
    4,
    'Appointment Cancelled (Customer Email)',
    'Your appointment request {{reference}} has been cancelled',
    'Your appointment has been cancelled',
    '<p>Hi {{first_name}},</p>\n\n<p>Your appointment request {{reference}} for {{date}} at {{time}} ({{services}}) has been cancelled.</p>\n\n<p>If this is unexpected, or you would like another time, please call the salon or send a new request on our website — we would be happy to find you a slot.</p>\n\n<p>Kind regards,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE NOT EXISTS (SELECT 1 FROM `email_templates` WHERE `id` = 4);
