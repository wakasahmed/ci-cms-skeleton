<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
| example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
| http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
| $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
| $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'frontend';
$route['manage'] = 'manage/home';
$route['robots\.txt'] = 'seo/robots';
$route['sitemap\.xml'] = 'seo/sitemap';
$route['llms\.txt'] = 'seo/llms';
$route['recaptcha-enterprise'] = "recaptcha_enterprise/index";
$route['recaptcha-enterprise/verify'] = "recaptcha_enterprise/verify";
$route['manage/translations/statuses'] = 'manage/manage_translations/statuses';
$route['manage/translations/translate'] = 'manage/manage_translations/translate';
$route['translation-cron/process'] = 'translation_cron/process';
$route['translation-cron/process/(:num)'] = 'translation_cron/process/$1';
$route['whatsapp/webhook'] = 'whatsapp/webhook';
$route['whatsapp/test'] = 'whatsapp/test';
$route['manage/web-pages/(:num)/sections'] = 'manage/web_page_sections/edit/$1';
$route['manage/web-pages/(:num)/sections/save'] = 'manage/web_page_sections/save/$1';
$route['manage/miscellaneous-contents/changestatus/(:num)/(:any)'] = 'manage/miscellaneous_contents/changestatus/$1/$2';
$route['manage/miscellaneous-contents/(:any)/edit'] = 'manage/miscellaneous_contents/edit/$1';
$route['manage/miscellaneous-contents/(:any)/save'] = 'manage/miscellaneous_contents/save/$1';
$route['en'] = 'frontend/index';
$route['ar'] = 'frontend/index';
$route['en/(:any)/save-step'] = 'frontend/save_plan_your_trip_step';
$route['ar/(:any)/save-step'] = 'frontend/save_plan_your_trip_step';
$route['(en|ar)/'.BLOG_CATEGORY_URI.'(:any)'] = 'frontend/blog_category/$1/$2';
$route['(en|ar)/'.BLOG_URI.'(:any)'] = 'frontend/blog_post/$1/$2';
$route['(en|ar)/'.TOUR_URI.'(:any)'] = 'frontend/tour_experience/$1/$2';
$route['(en|ar)/'.EXPERIENCE_URI.'(:any)'] = 'frontend/tour_experience/$1/$2';
$route['(en|ar)/booking-options'] = 'frontend/booking_options';
$route['(en|ar)/booking-availability'] = 'frontend/booking_availability';
$route['(en|ar)/booking-discount'] = 'frontend/booking_discount';
$route['(en|ar)/booking-places'] = 'frontend/booking_places';
$route['(en|ar)/booking-place'] = 'frontend/booking_place';
$route['(en|ar)/payments/prepare'] = 'payments/prepare';
$route['(en|ar)/payments/record'] = 'payments/record';
$route['(en|ar)/payments/return'] = 'payments/paymentReturn';
$route['(en|ar)/payments/webhook'] = 'payments/webhook';
$route['(en|ar)/review/(:num)/([a-f0-9]{64})'] = 'frontend/review/$1/$2/$3';
$route['en/(:any)'] = 'frontend/page/en/$1';
$route['ar/(:any)'] = 'frontend/page/ar/$1';
$route['404_override'] = 'frontend/error_404';
$route['translate_uri_dashes'] = TRUE;


/* End of file routes.php */
/* Location: ./application/config/routes.php */
