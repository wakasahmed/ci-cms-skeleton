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
| examples  sample value per tag, shown as a hint in the tag picker. A tag
|           without an example here falls back to a readable version of its
|           name, so add one when adding a tag.
|
*/
$config['short_tag_entities'] = array(
    'contact' => array(
        'label' => 'Contact request',
        'examples' => array(
            'id' => '118',
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.com',
            'subject' => 'A question about a service',
            'phone' => '+48500000000',
            'message' => 'Could I book a gel manicure with nail art on Saturday?',
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => '12 Oct 2026, 9:30 AM',
            'updated_at' => '12 Oct 2026, 9:30 AM',
            'country' => 'Poland',
            'website' => 'https://example.com',
        ),
    ),
    'appointment' => array(
        'label' => 'Appointment request',
        'examples' => array(
            'reference' => 'BLM-4102',
            'first_name' => 'Anna',
            'customer_name' => 'Anna Kowalska',
            'customer_email' => 'anna@example.com',
            'customer_phone' => '+48 500 000 000',
            'contact_preference' => 'Phone',
            'services' => 'Gel Manicure, Hair Styling',
            'schedule' => '10:00 am Gel Manicure with Ewa Mazur / 11:15 am Hair Styling with Anna',
            'artist' => 'Ewa Mazur, Anna',
            'offer' => 'Manicure + nail art',
            'date' => 'Oct 12, 2026',
            'time' => '10:30 am',
            'duration' => '1 hr 45 min',
            'estimated_total' => '140 zł',
            'notes' => 'I have gel on from last month.',
            'created_at' => 'Oct 10, 2026 09:30 am',
        ),
    ),
    'customer' => array(
        'label' => 'Customer account',
        'examples' => array(
            'first_name' => 'Anna',
            'customer_name' => 'Anna Kowalska',
            'customer_email' => 'anna@example.com',
            'link' => 'https://example.com/account/verify/…',
            'expires' => '48 hours',
        ),
    ),
);

/*
|--------------------------------------------------------------------------
| Entity per notification
|--------------------------------------------------------------------------
|
| Keyed by email template ID.
|
*/
$config['short_tag_template_entities'] = array(
    1 => 'contact',
    3 => 'appointment',
    4 => 'appointment',
    5 => 'customer',
    6 => 'customer',
    7 => 'customer',
);
