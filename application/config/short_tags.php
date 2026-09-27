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
);
