<?php defined('BASEPATH') or exit('No direct script access allowed');

// The site is English only. Section fields keep a locale column, so this is
// the single locale they are read and written with.
$config['content_locales'] = array(
    'en' => array('label' => 'English', 'direction' => 'ltr'),
);

$config['default_content_locale'] = 'en';
$config['content_section_image_path'] = 'assets/frontend/images/content-sections/';
$config['content_section_image_types'] = 'gif|jpg|jpeg|png|webp';
$config['content_section_image_max_kb'] = 10240;
$config['content_section_audit_path'] = APPPATH . 'logs/content_sections_audit.log';

// Legal pages (/privacy-policy, /cancellation-policy, /terms). The body is the
// page's Page Text: each <h2> starts a numbered section in the contents list.
$legalPageSections = array(
    array(
        'key' => 'notice',
        'label' => 'Notice',
        'fields' => array(
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Notice above the text (optional)', 'group' => null, 'required' => false, 'layout' => 'full'),
        ),
    ),
    array(
        'key' => 'help',
        'label' => 'Questions Box',
        'fields' => array(
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
        ),
    ),
);

$config['web_page_sections'] = array(
    9 => $legalPageSections,
    10 => $legalPageSections,
    41 => $legalPageSections,
    // Home (/). The hero slides come from Manage > Sliders (the page's slider).
    1 => array(
        array(
            'key' => 'hero',
            'label' => 'Hero',
            'fields' => array(
                array('key' => 'finishes_note', 'type' => 'text', 'label' => 'Note beside the finish colours', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'services_intro',
            'label' => 'Services',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'textarea', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'full'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'other_services',
            'label' => 'Other Services',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'link_text', 'type' => 'text', 'label' => 'Link Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'row_1_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Row 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_1_price', 'type' => 'text', 'label' => 'Price Text', 'group' => 'Row 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Row 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_1_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Row 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_1_image', 'type' => 'image', 'label' => 'Image', 'group' => 'Row 1', 'required' => false, 'layout' => 'full'),
                array('key' => 'row_2_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Row 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_2_price', 'type' => 'text', 'label' => 'Price Text', 'group' => 'Row 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Row 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_2_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Row 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_2_image', 'type' => 'image', 'label' => 'Image', 'group' => 'Row 2', 'required' => false, 'layout' => 'full'),
                array('key' => 'row_3_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Row 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_3_price', 'type' => 'text', 'label' => 'Price Text', 'group' => 'Row 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_3_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Row 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_3_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Row 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'row_3_image', 'type' => 'image', 'label' => 'Image', 'group' => 'Row 3', 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'nail_styles',
            'label' => 'Nail Styles',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
                array('key' => 'image_1', 'type' => 'image', 'label' => 'Image 1', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_2', 'type' => 'image', 'label' => 'Image 2', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_3', 'type' => 'image', 'label' => 'Image 3', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_4', 'type' => 'image', 'label' => 'Image 4', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_5', 'type' => 'image', 'label' => 'Image 5', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_6', 'type' => 'image', 'label' => 'Image 6', 'group' => 'Photos (in the order of the finishes)', 'required' => false, 'layout' => 'third'),
            ),
        ),
        array(
            'key' => 'gallery',
            'label' => 'Gallery',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'group' => null, 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'about',
            'label' => 'About',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'point_1_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'point_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'point_2_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'point_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'point_3_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'point_3_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'link_text', 'type' => 'text', 'label' => 'Link Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'image_1', 'type' => 'image', 'label' => 'Large Image', 'group' => 'Images', 'required' => false, 'layout' => 'half'),
                array('key' => 'image_2', 'type' => 'image', 'label' => 'Small Image', 'group' => 'Images', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'artists',
            'label' => 'Artists',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'link_text', 'type' => 'text', 'label' => 'Link Text', 'group' => null, 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'why',
            'label' => 'Why Blossom',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'item_1_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_1_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_4_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 4', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_4_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 4', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_4_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Point 4', 'required' => false, 'layout' => 'third'),
            ),
        ),
        array(
            'key' => 'booking',
            'label' => 'Booking Steps',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'step_1_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Step 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Step 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_2_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Step 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Step 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_3_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Step 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_3_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Step 3', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_4_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Step 4', 'required' => false, 'layout' => 'half'),
                array('key' => 'step_4_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Step 4', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'offers',
            'label' => 'Offers',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'link_text', 'type' => 'text', 'label' => 'Link Text', 'group' => null, 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'testimonials',
            'label' => 'Reviews',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'location',
            'label' => 'Location',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'instagram',
            'label' => 'Instagram',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'link_text', 'type' => 'text', 'label' => 'Link Text (profile name)', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'image_1', 'type' => 'image', 'label' => 'Image 1', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_2', 'type' => 'image', 'label' => 'Image 2', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_3', 'type' => 'image', 'label' => 'Image 3', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_4', 'type' => 'image', 'label' => 'Image 4', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_5', 'type' => 'image', 'label' => 'Image 5', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
                array('key' => 'image_6', 'type' => 'image', 'label' => 'Image 6', 'group' => 'Photos', 'required' => false, 'layout' => 'third'),
            ),
        ),
        array(
            'key' => 'final_cta',
            'label' => 'Final Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // About (/about). The hero comes from the page's banner fields.
    2 => array(
        array(
            'key' => 'approach',
            'label' => 'How We Work',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'item_1_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_1_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_1_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Point 1', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_2_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Point 2', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_title', 'type' => 'text', 'label' => 'Title', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
                array('key' => 'item_3_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Point 3', 'required' => false, 'layout' => 'third'),
            ),
        ),
        array(
            'key' => 'nails_first',
            'label' => 'Nails First',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents (a blank line starts a new paragraph)', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'button_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_1_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_2_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'image_1', 'type' => 'image', 'label' => 'Large Image', 'group' => 'Images', 'required' => false, 'layout' => 'half'),
                array('key' => 'image_2', 'type' => 'image', 'label' => 'Small Image', 'group' => 'Images', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'products',
            'label' => 'Products and Tools',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'note', 'type' => 'textarea', 'label' => 'Note (optional, shown smaller)', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'team',
            'label' => 'Team',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'location',
            'label' => 'Location',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'image', 'type' => 'image', 'label' => 'Image', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    '7' => array(
        array(
            'key' => 'contact_form',
            'label' => 'Contact Form',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'text-editor', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'contact_form_privacy',
            'label' => 'Contact Form Privacy Contents',
            'fields' => array(
                array('key' => 'contents', 'type' => 'text-editor', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Services (/services). The hero comes from the page's banner fields.
    37 => array(
        array(
            'key' => 'featured_services',
            'label' => 'Featured Services',
            'fields' => array(
                array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'full_menu',
            'label' => 'Full Menu',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Gallery (/gallery). The hero comes from the page's banner fields.
    39 => array(
        array(
            'key' => 'hero_link',
            'label' => 'Hero Button',
            'fields' => array(
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => null, 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Offers (/offers). The hero comes from the page's banner fields; featured
    // offers are listed first, the rest under "More Offers".
    40 => array(
        array(
            'key' => 'more_offers',
            'label' => 'More Offers',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'note', 'type' => 'textarea', 'label' => 'Note under the offers (optional)', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'services_link',
            'label' => 'Services Link',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // FAQ (/faq). The hero comes from the page's banner fields; the questions
    // come from Manage > FAQs, grouped by their (visible) FAQ categories.
    6 => array(
        array(
            'key' => 'sidebar',
            'label' => 'Sidebar',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'help_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Phone Box', 'required' => false, 'layout' => 'half'),
                array('key' => 'help_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Phone Box', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'questions',
            'label' => 'Questions',
            'fields' => array(
                array('key' => 'note', 'type' => 'textarea', 'label' => 'Note under the questions (optional)', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'links',
            'label' => 'Prices and Policies',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'button_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_1_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
                array('key' => 'button_2_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Contact (/contact). The hero comes from the page's banner fields; the
    // address, phone, opening hours and map link from Website Settings; the
    // form subjects and success message from Manage > Form Settings.
    7 => array(
        array(
            'key' => 'details',
            'label' => 'Where to Find Us',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'map_button_text', 'type' => 'text', 'label' => 'Map Button Text', 'group' => null, 'required' => false, 'layout' => 'half'),
            ),
        ),
        array(
            'key' => 'form',
            'label' => 'Contact Form',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'group' => null, 'required' => false, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents (a blank line starts a new paragraph; [text in square brackets] links to the booking page)', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'note', 'type' => 'textarea', 'label' => 'Note under the button', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Journal (/blog). The hero comes from the page's banner fields; the
    // latest published post is shown as the lead article.
    8 => array(
        array(
            'key' => 'articles',
            'label' => 'Articles',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'note', 'type' => 'textarea', 'label' => 'Note under the articles (optional)', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
    // Artists (/artists). The hero comes from the page's banner fields; the
    // first artist in Manage > Artists is shown as the lead artist.
    38 => array(
        array(
            'key' => 'team',
            'label' => 'Team',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
                array('key' => 'placeholder_note', 'type' => 'textarea', 'label' => 'Note shown while placeholder profiles are listed', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
        array(
            'key' => 'cta',
            'label' => 'Call to Action',
            'fields' => array(
                array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
                array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            ),
        ),
    ),
);

$config['miscellaneous_content_sections'] = array(
    // Headings and call to action shared by every artist page (/artists/{slug}).
    array(
        'key' => 'artist_page',
        'label' => 'Artist Page',
        'fields' => array(
            array('key' => 'services_heading', 'type' => 'text', 'label' => 'Services Heading ({name} is replaced by the first name)', 'group' => 'Services', 'required' => true, 'layout' => 'full'),
            array('key' => 'days_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Working Days', 'required' => false, 'layout' => 'half'),
            array('key' => 'days_button', 'type' => 'text', 'label' => 'Button Text', 'group' => 'Working Days', 'required' => false, 'layout' => 'half'),
            array('key' => 'days_note', 'type' => 'textarea', 'label' => 'Note', 'group' => 'Working Days', 'required' => false, 'layout' => 'full'),
            array('key' => 'work_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Recent Work', 'required' => false, 'layout' => 'half'),
            array('key' => 'work_button', 'type' => 'text', 'label' => 'Button Text', 'group' => 'Recent Work', 'required' => false, 'layout' => 'half'),
            array('key' => 'work_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Recent Work', 'required' => false, 'layout' => 'full'),
            array('key' => 'reviews_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Reviews', 'required' => false, 'layout' => 'half'),
            array('key' => 'reviews_note', 'type' => 'textarea', 'label' => 'Note', 'group' => 'Reviews', 'required' => false, 'layout' => 'full'),
            array('key' => 'cta_heading', 'type' => 'text', 'label' => 'Heading ({name} is replaced by the first name)', 'group' => 'Call to Action', 'required' => true, 'layout' => 'full'),
            array('key' => 'cta_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Call to Action', 'required' => false, 'layout' => 'full'),
        ),
    ),
    // Headings and call to action shared by every service page (/services/{slug}).
    array(
        'key' => 'service_page',
        'label' => 'Service Page',
        'fields' => array(
            array('key' => 'included_heading', 'type' => 'text', 'label' => 'What\'s Included Heading', 'group' => 'Details', 'required' => true, 'layout' => 'half'),
            array('key' => 'before_heading', 'type' => 'text', 'label' => 'Before Your Visit Heading', 'group' => 'Details', 'required' => false, 'layout' => 'half'),
            array('key' => 'aftercare_heading', 'type' => 'text', 'label' => 'Aftercare Heading', 'group' => 'Details', 'required' => false, 'layout' => 'half'),
            array('key' => 'addons_heading', 'type' => 'text', 'label' => 'Add-ons Heading', 'group' => 'Add-ons', 'required' => false, 'layout' => 'half'),
            array('key' => 'addons_note', 'type' => 'text', 'label' => 'Add-ons Note', 'group' => 'Add-ons', 'required' => false, 'layout' => 'full'),
            array('key' => 'gallery_heading', 'type' => 'text', 'label' => 'Gallery Heading', 'group' => 'Related Content', 'required' => false, 'layout' => 'third'),
            array('key' => 'artists_heading', 'type' => 'text', 'label' => 'Artists Heading', 'group' => 'Related Content', 'required' => false, 'layout' => 'third'),
            array('key' => 'related_heading', 'type' => 'text', 'label' => 'Related Services Heading', 'group' => 'Related Content', 'required' => false, 'layout' => 'third'),
            array('key' => 'cta_heading', 'type' => 'text', 'label' => 'Heading ({service} is replaced by the service name)', 'group' => 'Call to Action', 'required' => true, 'layout' => 'full'),
            array('key' => 'cta_text', 'type' => 'textarea', 'label' => 'Text', 'group' => 'Call to Action', 'required' => false, 'layout' => 'full'),
        ),
    ),
    array(
        'key' => 'get_in_touch',
        'label' => 'Get In Touch',
        'fields' => array(
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
        ),
    ),
    array(
        'key' => 'where_we_are',
        'label' => 'Where We Are',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'call_or_message',
        'label' => 'Call or Message',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'email_us',
        'label' => 'Email Us',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'still_need_help',
        'label' => 'Still Need Help?',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'browse_tours',
        'label' => 'Browse the tours',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'talk_to_us',
        'label' => 'Talk to Us',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'plan_with_us',
        'label' => 'Plan with Us',
        'fields' => array(
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_1_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_1_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button 1', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_1_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_1_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 1', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_2_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_2_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button 2', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_2_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_2_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button 2', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'featured_posts',
        'label' => 'Featured Posts',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
             array('key' => 'link_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Link', 'required' => false, 'layout' => 'half'),
            array('key' => 'link_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Link', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'link_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Link', 'required' => false, 'layout' => 'half'),
            array('key' => 'link_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Link', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'privacy_policy_card',
        'label' => 'Privacy Policy Card',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'cancellation_policy_card',
        'label' => 'Cancellation Policy Card',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'need_help_choosing',
        'label' => 'Need help choosing?',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'prefer_to_talk',
        'label' => 'Prefer to Talk?',
        'fields' => array(
            array('key' => 'icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => false, 'layout' => 'third'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Contents', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'button_icon', 'type' => 'icon-picker', 'label' => 'Icon', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_icon_pos', 'type' => 'select', 'label' => 'Icon Position', 'group' => 'Button', 'required' => false, 'layout' => 'half', 'options' => array('Left' => 'Left', 'Right' => 'Right')),
            array('key' => 'button_text', 'type' => 'text', 'label' => 'Text', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
            array('key' => 'button_url', 'type' => 'text', 'label' => 'URL', 'group' => 'Button', 'required' => false, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'tour_navigation',
        'label' => 'Tour / Experience Navigation',
        'fields' => array(
            array('key' => 'overview', 'type' => 'text', 'label' => 'Overview', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'places', 'type' => 'text', 'label' => 'Places & Highlights', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'itinerary', 'type' => 'text', 'label' => 'Itinerary', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'practical_information', 'type' => 'text', 'label' => 'Practical Information', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'gallery', 'type' => 'text', 'label' => 'Gallery', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'vehicles', 'type' => 'text', 'label' => 'Vehicles', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'guides', 'type' => 'text', 'label' => 'Guides', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'faqs', 'type' => 'text', 'label' => 'FAQs', 'group' => null, 'required' => true, 'layout' => 'half'),
        ),
    ),
    array(
        'key' => 'tour_price_details',
        'label' => 'Price Details',
        'fields' => array(
            array('key' => 'tour', 'type' => 'textarea', 'label' => 'Tour', 'group' => null, 'required' => false, 'layout' => 'half'),
            array('key' => 'experience', 'type' => 'textarea', 'label' => 'Experience', 'group' => null, 'required' => false, 'layout' => 'half'),
        ),
    ),
    // Shown on the services with "Show shapes and finishes" ticked (Manage > Services).
    array(
        'key' => 'nail_shapes_finishes',
        'label' => 'Nail Shapes & Finishes',
        'fields' => array(
            array('key' => 'pre_heading', 'type' => 'text', 'label' => 'Pre-Heading', 'group' => null, 'required' => false, 'layout' => 'half'),
            array('key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'group' => null, 'required' => true, 'layout' => 'half'),
            array('key' => 'contents', 'type' => 'textarea', 'label' => 'Introduction', 'group' => null, 'required' => false, 'layout' => 'full'),
            array('key' => 'shapes_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Shapes', 'required' => false, 'layout' => 'half'),
            array('key' => 'shapes', 'type' => 'textarea', 'label' => 'Shapes (one per line: Name | Description)', 'group' => 'Shapes', 'required' => false, 'layout' => 'full'),
            array('key' => 'finishes_heading', 'type' => 'text', 'label' => 'Heading', 'group' => 'Finishes', 'required' => false, 'layout' => 'half'),
            array('key' => 'finishes', 'type' => 'textarea', 'label' => 'Finishes (one per line: Name | Description | #colour)', 'group' => 'Finishes', 'required' => false, 'layout' => 'full'),
        ),
    ),
);
