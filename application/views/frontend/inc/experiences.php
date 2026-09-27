<?php
/**
 * Alam Al-Munawara — Experiences (temporary frontend data).
 *
 * Experiences are a SEPARATE product type from Tours. Tours are guided
 * historical/Sirah/heritage journeys (see tours() in inc/data.php);
 * Experiences are outdoor, adventure, desert, nature and Saudi cultural
 * activities in and around Madinah. The two data sets never mix.
 *
 * Everything here is SAMPLE content standing in for what will later come from
 * CodeIgniter 3 controllers/models. Each accessor returns the same shape a
 * model would return, so the views do not change when the source moves to
 * MySQL:
 *
 *   $all  = experiences();               // -> Experience_model->get_all()
 *   $one  = experience('stargazing');    // -> Experience_model->get_by_slug()
 *   $cats = experience_categories();     // -> Experience_category_model->get_all()
 *
 * Record shape — only the fields the frontend actually uses today:
 *
 *   id                 int, stable identifier (stands in for the PK)
 *   slug               string, clean URL segment, unique
 *   name               string, public-facing name
 *   category           string, key into experience_categories()
 *   short_description  string, one or two sentences for the card
 *   image              string, path under images/experiences/ (a -600 sibling
 *                      is served through srcset())
 *   image_alt          string, describes the photograph, not the product
 *   duration           string|null, shown when it is meaningful
 *   difficulty         string|null, neutral wording only
 *   best_time          string|null, time of day or season
 *   group              string|null, who the activity suits
 *   area               string|null, general area, never a named site the
 *                      photograph does not confirm
 *   featured           bool, appears on the homepage / About Us selections
 *   status             string, 'published' | 'draft' — only published render
 *
 * Deliberately NOT set yet: age requirement, fitness requirement, equipment,
 * guide/instructor and safety notes. Those are operational facts the client
 * has not supplied; the detail template already has a place for them.
 *
 * @see IMAGE_SOURCES.md for the licence and source page of every photograph.
 */


/* =========================================================================
 * Categories
 *
 * `label`  — full name, used for section headings and the card badge
 * `short`  — filter button text, kept short enough for a mobile filter row
 * ====================================================================== */
function experience_categories(): array
{
    /* `key` is what the filter chips compare on and what an experience record
       stores; it is never translated. `icon` is a glyph name. Only `label`,
       `short` and `text` are copy. */
    return localize_list(experience_categories_source(), 'experience_categories', 'key');
}

function experience_categories_source(): array
{
    return [
        [
            'key'   => 'hiking-nature',
            'label' => 'Hiking & Nature',
            'short' => 'Hiking & Nature',
            'icon'  => 'hiking',
            'text'  => 'Trails, ridges, valleys and the volcanic ground that surrounds Madinah.',
        ],
        [
            'key'   => 'adventure',
            'label' => 'Adventure',
            'short' => 'Adventure',
            'icon'  => 'mountain',
            'text'  => 'Activity-led days on rock, sand and trail, run with an instructor or guide.',
        ],
        [
            'key'   => 'desert-outdoors',
            'label' => 'Desert & Outdoors',
            'short' => 'Desert & Outdoors',
            'icon'  => 'camp',
            'text'  => 'Evenings and nights outside the city — camps, open sky and slow time.',
        ],
        [
            'key'   => 'riding',
            'label' => 'Riding Experiences',
            'short' => 'Riding',
            'icon'  => 'horse',
            'text'  => 'Time in the saddle, on horseback or on camel, at an unhurried pace.',
        ],
        [
            'key'   => 'local-life',
            'label' => 'Farms & Local Life',
            'short' => 'Local Life',
            'icon'  => 'farm',
            'text'  => 'The farms and date-palm groves that have fed Madinah for centuries.',
        ],
        [
            'key'   => 'culture',
            'label' => 'Saudi Culture & Workshops',
            'short' => 'Culture',
            'icon'  => 'craft',
            'text'  => 'Hands-on sessions in cooking, coffee, craft and Arabic script.',
        ],
    ];
}

/** One category record, or null. */
function experience_category(string $key): ?array
{
    foreach (experience_categories() as $c) {
        if ($c['key'] === $key) {
            return $c;
        }
    }
    return null;
}

/** Category label for a key, falling back to the key itself. */
function experience_category_label(string $key, bool $short = false): string
{
    $c = experience_category($key);
    if ($c === null) {
        return $key;
    }
    return $short ? $c['short'] : $c['label'];
}

/* =========================================================================
 * Experiences
 *
 * Durations, difficulty ratings and timings below are SAMPLE values used to
 * show the layout. They are deliberately broad and non-committal — replace
 * them with the operator's confirmed details before launch.
 * ====================================================================== */
function experiences(): array
{
    static $cache = [];

    $locale = current_locale();
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    /* id, slug, category, image, featured and status are operational and stay
       in this file; the name, description, alt text and the metadata pills are
       translated in inc/lang/content-ar/experiences.php. */
    $cache[$locale] = localize_list(experiences_source(), 'experiences', 'slug');

    return $cache[$locale];
}

function experiences_source(): array
{
    static $items = null;
    if ($items !== null) {
        return $items;
    }

    $items = [
        /* ---- Hiking & Nature ------------------------------------------ */
        [
            'id'                => 1,
            'slug'              => 'hiking',
            'name'              => 'Hiking',
            'category'          => 'hiking-nature',
            'short_description' => 'A guided walk on the trails and open ground outside the city, at a pace that suits the group you arrive with.',
            'image'             => 'images/experiences/hiking.webp',
            'image_alt'         => 'A group of walkers following a trail with packs on their backs',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Easy to moderate',
            'best_time'         => 'Morning or late afternoon',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 2,
            'slug'              => 'mountain-hiking',
            'name'              => 'Mountain Hiking',
            'category'          => 'hiking-nature',
            'short_description' => 'A longer climb onto the higher ground around Madinah, with the city and the surrounding plain opening up as you gain height.',
            'image'             => 'images/experiences/mountain-hiking.webp',
            'image_alt'         => 'Hikers making their way up a bare rocky ridge above the clouds',
            'duration'          => '3 to 4 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Early morning',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => true,
            'status'            => 'published',
        ],
        [
            'id'                => 3,
            'slug'              => 'volcanic-field-hiking',
            'name'              => 'Volcanic-field Hiking',
            'category'          => 'hiking-nature',
            'short_description' => 'A walk across the harrat — the volcanic ground that stretches away from Madinah, dark, broken and unlike anywhere else nearby.',
            'image'             => 'images/experiences/volcanic-field-hiking.webp',
            'image_alt'         => 'Dark volcanic rock and a cinder cone rising from an open plain',
            'duration'          => '3 to 4 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Cooler months',
            'group'             => 'Small groups',
            'area'              => 'Harrat lava field',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 4,
            'slug'              => 'lava-field-exploration',
            'name'              => 'Lava-field Exploration',
            'category'          => 'hiking-nature',
            'short_description' => 'A slower, closer look at the lava flows themselves — how the rock formed, what grows on it, and how the ground reads once someone explains it.',
            'image'             => 'images/experiences/lava-field-exploration.webp',
            'image_alt'         => 'A field of black basalt lava rock under an open sky',
            'duration'          => 'Half day',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Cooler months',
            'group'             => null,
            'area'              => 'Harrat lava field',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 5,
            'slug'              => 'sunrise-hiking',
            'name'              => 'Sunrise Hiking',
            'category'          => 'hiking-nature',
            'short_description' => 'An early start to be on the high ground as the light comes up — the coolest and quietest walk of the day.',
            'image'             => 'images/experiences/sunrise-hiking.webp',
            'image_alt'         => 'A hiker on a ridge at first light with the sun low on the horizon',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Before dawn',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 6,
            'slug'              => 'sunset-hiking',
            'name'              => 'Sunset Hiking',
            'category'          => 'hiking-nature',
            'short_description' => 'A late-afternoon walk timed so the last stretch is done in the low, warm light before the sun drops.',
            'image'             => 'images/experiences/sunset-hiking.webp',
            'image_alt'         => 'A hiker with a backpack silhouetted against the evening sky',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Easy to moderate',
            'best_time'         => 'Late afternoon',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 7,
            'slug'              => 'wadi-exploration',
            'name'              => 'Wadi Exploration',
            'category'          => 'hiking-nature',
            'short_description' => 'Following a dry valley floor between rock walls, with your guide explaining how water, when it comes, still shapes the whole landscape.',
            'image'             => 'images/experiences/wadi-exploration.webp',
            'image_alt'         => 'A walker following a narrow passage between steep rock walls',
            'duration'          => '3 to 4 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Cooler months',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 8,
            'slug'              => 'cave-exploration',
            'name'              => 'Cave Exploration',
            'category'          => 'hiking-nature',
            'short_description' => 'A guided visit into the caves and lava tubes formed in the volcanic rock, with equipment and an instructor provided.',
            'image'             => 'images/experiences/cave-exploration.webp',
            'image_alt'         => 'The mouth of a rock cave opening onto daylight',
            'duration'          => 'Half day',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Year-round',
            'group'             => 'Small groups',
            'area'              => 'Harrat lava field',
            'featured'          => false,
            'status'            => 'published',
        ],

        /* ---- Adventure ------------------------------------------------- */
        [
            'id'                => 9,
            'slug'              => 'rock-climbing',
            'name'              => 'Rock Climbing',
            'category'          => 'adventure',
            'short_description' => 'Climbing on natural rock with an instructor, on routes chosen to match the experience of the people in the group.',
            'image'             => 'images/experiences/rock-climbing.webp',
            'image_alt'         => 'A climber working up a natural rock face',
            'duration'          => '3 to 4 hours',
            'difficulty'        => 'Challenging',
            'best_time'         => 'Cooler months',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 10,
            'slug'              => 'abseiling-rappelling',
            'name'              => 'Abseiling / Rappelling',
            'category'          => 'adventure',
            'short_description' => 'A controlled rope descent down a rock face, run by an instructor with the equipment and the briefing included.',
            'image'             => 'images/experiences/abseiling-rappelling.webp',
            'image_alt'         => 'Two climbers working a rope on a rock face',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Challenging',
            'best_time'         => 'Cooler months',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 11,
            'slug'              => 'quad-biking',
            'name'              => 'Quad Biking / ATV Riding',
            'category'          => 'adventure',
            'short_description' => 'Riding a quad bike over open sand and track, after a briefing and a short practice run on easier ground.',
            'image'             => 'images/experiences/quad-biking.webp',
            'image_alt'         => 'Quad bikes lined up on a desert dune before a ride',
            'duration'          => '1 to 2 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Late afternoon',
            'group'             => null,
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 12,
            'slug'              => 'dune-buggy-riding',
            'name'              => 'Dune Buggy Riding',
            'category'          => 'adventure',
            'short_description' => 'A seated buggy run across the dunes with a driver, or at the wheel yourself once the route has been walked through.',
            'image'             => 'images/experiences/dune-buggy-riding.webp',
            'image_alt'         => 'A dune buggy on sand in open desert',
            'duration'          => '1 to 2 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Late afternoon',
            'group'             => null,
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 13,
            'slug'              => 'off-roading',
            'name'              => '4x4 Off-roading',
            'category'          => 'adventure',
            'short_description' => 'A driven 4x4 route out over sand and rough ground, reaching places the road does not go.',
            'image'             => 'images/experiences/off-roading.webp',
            'image_alt'         => 'A four-wheel-drive vehicle driving across desert dunes',
            'duration'          => 'Half day',
            'difficulty'        => 'Easy',
            'best_time'         => 'Late afternoon',
            'group'             => 'Families and groups',
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 14,
            'slug'              => 'mountain-biking',
            'name'              => 'Mountain Biking',
            'category'          => 'adventure',
            'short_description' => 'A ride on the tracks and trails outside the city, with the route matched to how much riding the group has done before.',
            'image'             => 'images/experiences/mountain-biking.webp',
            'image_alt'         => 'Two mountain bikers riding a dry off-road trail',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Moderate',
            'best_time'         => 'Morning',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 15,
            'slug'              => 'archery',
            'name'              => 'Archery',
            'category'          => 'adventure',
            'short_description' => 'A supervised session on the range, starting from how to hold the bow — no previous experience assumed.',
            'image'             => 'images/experiences/archery.webp',
            'image_alt'         => 'An archer drawing a bow at an outdoor range',
            'duration'          => '1 to 2 hours',
            'difficulty'        => 'Easy',
            'best_time'         => null,
            'group'             => 'Families and groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],

        /* ---- Desert & Outdoors ----------------------------------------- */
        [
            'id'                => 16,
            'slug'              => 'desert-camping',
            'name'              => 'Desert Camping',
            'category'          => 'desert-outdoors',
            'short_description' => 'A night out in the desert in a set-up camp, with dinner cooked on site and the drive out and back arranged for you.',
            'image'             => 'images/experiences/desert-camping.webp',
            'image_alt'         => 'Rows of tents pitched on open desert sand at first light',
            'duration'          => 'Overnight',
            'difficulty'        => 'Easy',
            'best_time'         => 'Cooler months',
            'group'             => 'Families and groups',
            'area'              => 'Madinah desert',
            'featured'          => true,
            'status'            => 'published',
        ],
        [
            'id'                => 17,
            'slug'              => 'glamping',
            'name'              => 'Glamping',
            'category'          => 'desert-outdoors',
            'short_description' => 'The same desert night with more comfort — a furnished tent, proper beds and a prepared dinner.',
            'image'             => 'images/experiences/glamping.webp',
            'image_alt'         => 'Beds made up inside a furnished canvas tent',
            'duration'          => 'Overnight',
            'difficulty'        => 'Easy',
            'best_time'         => 'Cooler months',
            'group'             => 'Couples and families',
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 18,
            'slug'              => 'stargazing',
            'name'              => 'Stargazing',
            'category'          => 'desert-outdoors',
            'short_description' => 'Driving far enough out that the city glow drops away, then sitting with the sky while someone talks you through what is overhead.',
            'image'             => 'images/experiences/stargazing.webp',
            'image_alt'         => 'A star-filled night sky above a dark desert landscape',
            'duration'          => '2 to 3 hours',
            'difficulty'        => null,
            'best_time'         => 'Evening',
            'group'             => 'Families and groups',
            'area'              => 'Madinah desert',
            'featured'          => true,
            'status'            => 'published',
        ],
        [
            'id'                => 19,
            'slug'              => 'desert-bbq',
            'name'              => 'Desert BBQ',
            'category'          => 'desert-outdoors',
            'short_description' => 'An evening meal cooked over the fire out in the open, with seating set up and the sun going down over the sand.',
            'image'             => 'images/experiences/desert-bbq.webp',
            'image_alt'         => 'Skewers of meat and vegetables cooking on an outdoor barbecue',
            'duration'          => '2 to 3 hours',
            'difficulty'        => null,
            'best_time'         => 'Evening',
            'group'             => 'Families and groups',
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 20,
            'slug'              => 'outdoor-photography',
            'name'              => 'Outdoor Photography',
            'category'          => 'desert-outdoors',
            'short_description' => 'A trip out to the landscapes that photograph best, timed for the light rather than for the shortest drive.',
            'image'             => 'images/experiences/outdoor-photography.webp',
            'image_alt'         => 'A photographer standing on a rock ledge photographing a canyon',
            'duration'          => 'Half day',
            'difficulty'        => 'Easy',
            'best_time'         => 'Sunrise or sunset',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 21,
            'slug'              => 'cycling',
            'name'              => 'Cycling',
            'category'          => 'desert-outdoors',
            'short_description' => 'A road ride on the quieter routes around the city, with distance and pace set to suit the group.',
            'image'             => 'images/experiences/cycling.webp',
            'image_alt'         => 'A group of cyclists riding together along an open road',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Easy to moderate',
            'best_time'         => 'Morning',
            'group'             => 'Small groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],

        /* ---- Riding ----------------------------------------------------- */
        [
            'id'                => 22,
            'slug'              => 'horse-riding',
            'name'              => 'Horse Riding',
            'category'          => 'riding',
            'short_description' => 'Time on horseback with a handler alongside, on flat open ground and at a pace set by the rider.',
            'image'             => 'images/experiences/horse-riding.webp',
            'image_alt'         => 'A rider sitting on horseback outdoors',
            'duration'          => '1 to 2 hours',
            'difficulty'        => 'Easy to moderate',
            'best_time'         => 'Morning or late afternoon',
            'group'             => 'Families and groups',
            'area'              => 'Madinah region',
            'featured'          => true,
            'status'            => 'published',
        ],
        [
            'id'                => 23,
            'slug'              => 'camel-riding',
            'name'              => 'Camel Riding',
            'category'          => 'riding',
            'short_description' => 'A short ride at camel pace, led by a handler — the slowest and oldest way to cross this ground.',
            'image'             => 'images/experiences/camel-riding.webp',
            'image_alt'         => 'Camels being led across desert sand',
            'duration'          => '1 hour',
            'difficulty'        => 'Easy',
            'best_time'         => 'Late afternoon',
            'group'             => 'Families and groups',
            'area'              => 'Madinah desert',
            'featured'          => false,
            'status'            => 'published',
        ],

        /* ---- Farms & Local Life ----------------------------------------- */
        [
            'id'                => 24,
            'slug'              => 'farm-experiences',
            'name'              => 'Farm Experiences',
            'category'          => 'local-life',
            'short_description' => 'A morning on a working farm outside the city — what is grown here, how it is watered, and what the season looks like right now.',
            'image'             => 'images/experiences/farm-experiences.webp',
            'image_alt'         => 'A date palm standing over cultivated farmland at dusk',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Easy',
            'best_time'         => 'Morning',
            'group'             => 'Families and groups',
            'area'              => 'Madinah region',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 25,
            'slug'              => 'date-palm-farm-experience',
            'name'              => 'Date-palm Farm Experience',
            'category'          => 'local-life',
            'short_description' => 'A visit to the date-palm groves Madinah is known for, walking the rows and tasting the varieties grown here.',
            'image'             => 'images/experiences/date-palm-farm.webp',
            'image_alt'         => 'Bunches of dates ripening on a date palm',
            'duration'          => '2 to 3 hours',
            'difficulty'        => 'Easy',
            'best_time'         => 'Morning',
            'group'             => 'Families and groups',
            'area'              => 'Palm groves',
            'featured'          => true,
            'status'            => 'published',
        ],

        /* ---- Saudi Culture & Workshops ---------------------------------- */
        [
            'id'                => 26,
            'slug'              => 'saudi-cooking-experience',
            'name'              => 'Traditional Saudi Cooking Experience',
            'category'          => 'culture',
            'short_description' => 'Cooking a traditional dish from the start, then sitting down and eating it with the people who showed you how.',
            'image'             => 'images/experiences/saudi-cooking.webp',
            'image_alt'         => 'A traditional rice and chicken dish served with side dishes',
            'duration'          => '2 to 3 hours',
            'difficulty'        => null,
            'best_time'         => null,
            'group'             => 'Small groups',
            'area'              => 'Indoor venue',
            'featured'          => true,
            'status'            => 'published',
        ],
        [
            'id'                => 27,
            'slug'              => 'saudi-coffee-experience',
            'name'              => 'Saudi Coffee-making Experience',
            'category'          => 'culture',
            'short_description' => 'Roasting, grinding and pouring Saudi coffee the way it is served at home, and why it is served that way.',
            'image'             => 'images/experiences/saudi-coffee.webp',
            'image_alt'         => 'A host pouring Arabic coffee from a traditional dallah pot',
            'duration'          => '1 to 2 hours',
            'difficulty'        => null,
            'best_time'         => null,
            'group'             => 'Small groups',
            'area'              => 'Indoor venue',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 28,
            'slug'              => 'handicraft-workshop',
            'name'              => 'Pottery & Handicraft Workshop',
            'category'          => 'culture',
            'short_description' => 'A hands-on session with a craftsperson, working with your own hands and taking home whatever you make.',
            'image'             => 'images/experiences/handicraft-workshop.webp',
            'image_alt'         => 'Hands shaping wet clay on a potter\'s wheel',
            'duration'          => '2 hours',
            'difficulty'        => null,
            'best_time'         => null,
            'group'             => 'Families and groups',
            'area'              => 'Indoor venue',
            'featured'          => false,
            'status'            => 'published',
        ],
        [
            'id'                => 29,
            'slug'              => 'calligraphy-experience',
            'name'              => 'Arabic Calligraphy Experience',
            'category'          => 'culture',
            'short_description' => 'An introduction to the Arabic scripts and the pen that makes them, with time to practise the strokes yourself.',
            'image'             => 'images/experiences/calligraphy.webp',
            'image_alt'         => 'Arabic calligraphy written in ink beside the reed pens used for it',
            'duration'          => '1 to 2 hours',
            'difficulty'        => null,
            'best_time'         => null,
            'group'             => 'Small groups',
            'area'              => 'Indoor venue',
            'featured'          => false,
            'status'            => 'published',
        ],
    ];

    return $items;
}

/** Published experiences only — what the public pages list. */
function published_experiences(): array
{
    return array_values(array_filter(
        experiences(),
        static function (array $x) { return ($x['status'] ?? 'published') === 'published'; }
    ));
}

/** One experience by slug, or null when the slug is unknown. */
function experience(string $slug): ?array
{
    foreach (published_experiences() as $x) {
        if ($x['slug'] === $slug) {
            return $x;
        }
    }
    return null;
}

/** Published experiences in one category. */
function experiences_in(string $categoryKey): array
{
    return array_values(array_filter(
        published_experiences(),
        static function (array $x) use ($categoryKey) { return $x['category'] === $categoryKey; }
    ));
}

/**
 * Featured experiences for the homepage / About Us.
 *
 * The flagged set is ordered for visual and activity variety rather than by
 * id, so a shorter slice still shows a spread of category types.
 */
function featured_experiences(int $limit = 6): array
{
    $order = [
        'mountain-hiking',
        'stargazing',
        'desert-camping',
        'horse-riding',
        'date-palm-farm-experience',
        'saudi-cooking-experience',
    ];

    $bySlug = [];
    foreach (published_experiences() as $x) {
        if (!empty($x['featured'])) {
            $bySlug[$x['slug']] = $x;
        }
    }

    $out = [];
    foreach ($order as $slug) {
        if (isset($bySlug[$slug])) {
            $out[] = $bySlug[$slug];
            unset($bySlug[$slug]);
        }
    }
    foreach ($bySlug as $x) {
        $out[] = $x;
    }

    return array_slice($out, 0, $limit);
}

/**
 * The metadata pills a card or detail page should show for an experience.
 *
 * Only fields that were actually set come back, so a stargazing card shows
 * "Evening · 2 to 3 hours" while a mountain hike shows duration, difficulty
 * and group. Returns a list of ['icon' => ..., 'label' => ..., 'value' => ...].
 */
function experience_meta(array $x, int $limit = 0): array
{
    /* The third column is a translation KEY, not a label: the card prints
       t() of it, and experience_card() filters on the key rather than
       on the visible text so the "Area" pill is dropped in both locales. */
    $map = [
        ['duration',   'clock',    'meta.duration'],
        ['difficulty', 'walk',     'meta.difficulty'],
        ['best_time',  'sun'    ,  'meta.bestTime'],
        ['group',      'users',    'meta.suits'],
        ['area',       'map-pin',  'meta.area'],
    ];

    $out = [];
    foreach ($map as $m) {
        $v = $x[$m[0]] ?? null;
        if ($v === null || $v === '') {
            continue;
        }
        $out[] = ['icon' => $m[1], 'key' => $m[2], 'label' => t($m[2]), 'value' => (string) $v];
    }

    return $limit > 0 ? array_slice($out, 0, $limit) : $out;
}
