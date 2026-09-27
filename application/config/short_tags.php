<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Notification short tag entities
|--------------------------------------------------------------------------
|
| The tag names themselves come from EmailService::shortTagFields(), the same
| list the parse*ShortTags() methods replace, so a tag is only ever added in
| one place. This file only adds what the code does not have:
|
| label     heading shown above the tag picker
| examples  sample value per tag: shown as a hint in the tag picker and sent
|           to Meta, which requires one for each WhatsApp named parameter at
|           review time. A tag without an example here falls back to a
|           readable version of its name, so add one when adding a tag.
|
| booking covers tours, experiences and the tour guide notifications.
|
*/
$config['short_tag_entities'] = array(
    'contact' => array(
        'label' => 'Contact request',
        'examples' => array(
            'id' => '118',
            'first_name' => 'Ahmed',
            'last_name' => 'Khan',
            'email' => 'ahmed@example.com',
            'subject' => 'Group tour enquiry',
            'phone' => '+966501234567',
            'message' => 'We are a family of four visiting in October.',
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => '12 Oct 2026, 9:30 AM',
            'updated_at' => '12 Oct 2026, 9:30 AM',
            'country' => 'Pakistan',
            'website' => 'https://example.com',
        ),
    ),
    'plan_your_visit' => array(
        'label' => 'Plan your visit request',
        'examples' => array(
            'id' => '57',
            'name' => 'Ahmed Khan',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567',
            'country' => 'Pakistan',
            'arrival_date' => '12 Oct 2026',
            'departure_date' => '16 Oct 2026',
            'guests' => '4',
            'step_completed' => '3',
            'preferred_language' => 'English',
            'preferred_time' => 'Morning',
            'interests' => 'Historical sites',
            'message' => 'We would like a morning tour.',
            'created_at' => '12 Oct 2026, 9:30 AM',
            'updated_at' => '12 Oct 2026, 9:30 AM',
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0',
            'website' => 'https://example.com',
        ),
    ),
    'booking' => array(
        'label' => 'Booking',
        'examples' => array(
            'book_id' => '1024',
            'book_name' => 'Ahmed Khan',
            'book_email' => 'ahmed@example.com',
            'book_phone' => '+966501234567',
            'book_address' => 'Al Haram Hotel, Madinah',
            'book_res_code' => 'ALM-10234',
            'book_tour_name' => 'Madinah Ziyarat Tour',
            'book_date' => '12 Oct 2026',
            'book_guests' => '4',
            'book_slot_name' => 'Morning',
            'book_slot_hours' => '3',
            'book_slot_start_time' => '8:00 AM',
            'book_slot_end_time' => '11:00 AM',
            'book_lang_name' => 'English',
            'book_tour_guide_name' => 'Yusuf Ali',
            'book_old_tour_guide_name' => 'Omar Saeed',
            'book_vehicle_name' => 'Toyota HiAce',
            'book_original_total' => '600.00',
            'book_discount_amount' => '60.00',
            'book_tour_total' => '540.00',
            'book_profit_percent' => '20',
            'book_profit_amount' => '108.00',
            'book_tax_percent' => '15',
            'book_tax_amount' => '70.43',
            'book_paid_amount' => '540.00',
            'book_currency' => 'SAR',
            'book_payment_method' => 'Card',
            'book_payment_date' => '10 Oct 2026',
            'book_transaction_id' => 'pay_7Hk2mQ9x',
            'book_ref_name' => 'Sara Ahmed',
            'book_promo_code' => 'SARA10',
            'book_ref_commission' => '27.00',
            'book_refund_amount' => '540.00',
            'book_refunded_at' => '14 Oct 2026',
            'book_notes' => 'Please call on arrival',
            'book_review_url' => 'https://alam.example.com/en/review/1024',
            'book_review_url_btn' => 'Leave a Review button',
        ),
    ),
    'discount' => array(
        'label' => 'Discount code',
        'examples' => array(
            'discount_id' => '12',
            'discount_name' => 'Sara partner code',
            'discount_ref_id' => '7',
            'discount_code' => 'SARA10',
            'discount_type' => '%',
            'discount_value' => '10',
            'discount_ref_commission_type' => 'Percentage',
            'discount_ref_commission' => '5',
            'discount_expiry' => '31 Dec 2026',
            'discount_no_of_uses' => '100',
            'discount_status' => 'Enable',
            'discount_added' => '1 Oct 2026',
            'discount_updated' => '5 Oct 2026',
            'discount_ref_name' => 'Sara Ahmed',
        ),
    ),
);

/*
|--------------------------------------------------------------------------
| Entity per notification
|--------------------------------------------------------------------------
|
| Keyed by template ID. Email and WhatsApp templates for the same notification
| share the ID the code sends, so they use the same entity's tags.
|
*/
$config['short_tag_template_entities'] = array(
    1 => 'contact',
    2 => 'plan_your_visit',
    3 => 'booking',
    4 => 'booking',
    5 => 'booking',
    6 => 'booking',
    7 => 'booking',
    8 => 'booking',
    9 => 'booking',
    10 => 'booking',
    11 => 'discount',
    12 => 'discount',
    13 => 'booking',
    14 => 'booking',
    15 => 'booking',
);
