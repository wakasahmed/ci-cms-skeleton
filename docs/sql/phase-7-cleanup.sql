-- ---------------------------------------------------------------------------
-- Phase 7 — Content clean-up (PROJECT_PLAN.md, carried over from Phase 6)
--
-- Removes the Alam content that no page reads any more:
--   * 15 Miscellaneous Contents sections (tour and Alam contact cards) whose
--     definitions were removed from config/content_sections.php;
--   * the Alam "Sample Page" (Web Pages 34), which has no route.
--
-- Content only (no schema change) and safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

DELETE f
FROM `miscellaneous_content_section_fields` f
JOIN `miscellaneous_content_sections` s ON s.`id` = f.`section_id`
WHERE s.`section_key` IN (
    'get_in_touch', 'where_we_are', 'call_or_message', 'email_us', 'still_need_help',
    'browse_tours', 'talk_to_us', 'plan_with_us', 'featured_posts', 'privacy_policy_card',
    'cancellation_policy_card', 'need_help_choosing', 'prefer_to_talk', 'tour_navigation',
    'tour_price_details'
);

DELETE FROM `miscellaneous_content_sections`
WHERE `section_key` IN (
    'get_in_touch', 'where_we_are', 'call_or_message', 'email_us', 'still_need_help',
    'browse_tours', 'talk_to_us', 'plan_with_us', 'featured_posts', 'privacy_policy_card',
    'cancellation_policy_card', 'need_help_choosing', 'prefer_to_talk', 'tour_navigation',
    'tour_price_details'
);

DELETE FROM `web_page_sections` WHERE `page_id` = 34;
DELETE FROM `pages` WHERE `page_id` = 34 AND `page_slug` = 'sample-page';
