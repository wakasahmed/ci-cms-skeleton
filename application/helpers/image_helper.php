<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Image helper
 *
 * Shared rendering for image thumbnails across the admin and the front end.
 * Wraps the Imagethumb library so a listing only has to describe what it wants
 * instead of repeating the "does the file exist / build a thumb / wrap it in a
 * fancybox link / fall back to an initial" markup in every view.
 *
 * @package     CodeIgniter
 * @subpackage  Helpers
 * @category    Image
 */

/**
 * Stand-in image used by the 'placeholder' option.
 */
defined('IMAGE_THUMB_PLACEHOLDER') OR define('IMAGE_THUMB_PLACEHOLDER', './assets/frontend/images/no_image.jpg');

/**
 * Directory the favicon source is uploaded into (see Website_settings::$uploadFields).
 */
defined('FAVICON_SOURCE_DIRECTORY') OR define('FAVICON_SOURCE_DIRECTORY', 'assets/frontend/images/logo/');

if ( ! function_exists('upload_accept_types'))
{
    /**
     * Build a browser file-input accept value from CodeIgniter's MIME registry.
     *
     * The first MIME configured for an extension is its canonical type. Unknown
     * extensions use the standard .extension accept syntax so custom allowlists
     * continue to work without a separate hard-coded map.
     */
    function upload_accept_types($allowed_types)
    {
        $extensions = is_array($allowed_types)
            ? $allowed_types
            : explode('|', (string) $allowed_types);
        $mimeTypes =& get_mimes();
        $accept = array();

        foreach ($extensions as $extension)
        {
            $extension = strtolower(ltrim(trim((string) $extension), '.'));
            if ($extension === '')
            {
                continue;
            }

            $configuredMimes = isset($mimeTypes[$extension])
                ? (array) $mimeTypes[$extension]
                : array();
            $acceptType = ! empty($configuredMimes)
                ? reset($configuredMimes)
                : '.'.$extension;

            if ($acceptType !== '' && ! in_array($acceptType, $accept, TRUE))
            {
                $accept[] = $acceptType;
            }
        }

        return implode(',', $accept);
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('upload_file_preview'))
{
    /**
     * Render a shared admin preview for an uploaded image or document.
     * Images use image_thumb() so they open in the shared admin lightbox.
     */
    function upload_file_preview($file_path, $options = array())
    {
        $options = array_merge(array('alt' => 'Uploaded file', 'shape' => 'square', 'compact' => FALSE, 'size' => NULL), is_array($options) ? $options : array());
        $relative = image_thumb_path($file_path);
        if ($relative === FALSE)
        {
            return '';
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $imageTypes = defined('UPLOAD_IMAGE_MIMES') ? explode('|', UPLOAD_IMAGE_MIMES) : array('png', 'jpg', 'jpeg', 'webp');
        if (in_array($extension, $imageTypes, TRUE))
        {
            $circle = in_array(strtolower((string) $options['shape']), array('circle', 'round'), TRUE);
            $size = $options['size'] !== NULL ? $options['size'] : ($options['compact'] ? 42 : ($circle ? 132 : array(180, 132)));
            return image_thumb($relative, 240, 0, 'webp', $options['shape'], TRUE, array(
                'alt' => $options['alt'],
                'class' => $options['compact'] ? 'admin-file-preview-image is-compact' : 'admin-file-preview-image',
                'size' => $size,
                'radius' => '.5rem',
            ));
        }

        $icons = array('pdf' => 'bi-file-earmark-pdf', 'doc' => 'bi-file-earmark-word', 'docx' => 'bi-file-earmark-word');
        $icon = isset($icons[$extension]) ? $icons[$extension] : 'bi-file-earmark';
        $filename = basename($relative);
        return '<a class="admin-document-preview'.($options['compact'] ? ' is-compact' : '').'" href="'.image_thumb_escape(image_thumb_url($relative)).'" target="_blank" rel="noopener" aria-label="Open '.image_thumb_escape($filename).'">'
            .'<i class="bi '.image_thumb_escape($icon).'" aria-hidden="true"></i>'
            .'<span>'.image_thumb_escape($filename).'</span></a>';
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('image_thumb'))
{
    /**
     * Renders a thumbnail, optionally wrapped in a fancybox (lightbox) link.
     *
     * NOTE the parameter order: $height comes before $width. The Imagethumb
     * library takes them the other way round ($width, $height) so do not copy
     * a call from one to the other without swapping them.
     *
     * Missing files never produce a broken image: when the file cannot be
     * found the 'fallback' text is rendered inside the same box instead (the
     * initial-letter avatars used by the listing screens), or nothing at all
     * when no fallback was given.
     *
     * echo image_thumb(
     * './assets/frontend/images/tour-guides/'.$image,
     * 80, 80, 'webp', 'circle', TRUE,
     * array('alt' => $name, 'fallback' => 'A', 'size' => 42)
     * );
     *
     * @param string $image_path Path to the source image, relative to the
     * front controller ('./assets/...' or
     * 'assets/...') or an FCPATH absolute path.
     * @param int $height Thumbnail height, 0 = follow the ratio.
     * @param int $width Thumbnail width, 0 = follow the ratio.
     * @param string|int $output_type Output format: 'webp' (default), 'jpg',
     * 'png', 'gif', or NULL to keep the source
     * format. See the Imagethumb library.
     * @param string $shape 'circle' or 'square' (square uses a small
     * border radius, see the 'radius' option).
     * @param bool $lightbox TRUE (default) wraps the thumbnail in a
     * fancybox link opening the full size image.
     * @param array $options Extra options:
     * 'alt'        => img alt text
     * 'fallback'   => text shown when the image is missing
     * 'placeholder'=> stand-in image used when the source is
     * missing: TRUE for the site no_image.jpg,
     * or a path of your own. Takes precedence
     * over 'fallback'.
     * 'href'       => link target used when $lightbox is FALSE,
     * and for the fallback box
     * 'aria_label' => aria-label for the link
     * 'class'      => extra CSS classes for the wrapper
     * 'size'       => rendered box size in px, int for a
     * square or array(width, height).
     * Defaults to the thumbnail size.
     * 'radius'     => CSS border radius for the square shape
     * 'group'      => data-fancybox-group value, to browse a
     * listing as a gallery
     * 'lazy'       => FALSE to opt out of lazy loading
     * @return string
     */
    function image_thumb($image_path, $height = 0, $width = 0, $output_type = 'webp', $shape = 'square', $lightbox = TRUE, $options = array())
    {
        $options = array_merge(array(
            'alt'         => '',
            'fallback'    => '',
            'placeholder' => FALSE,
            'href'        => '',
            'aria_label'  => '',
            'class'       => '',
            'size'        => 0,
            'radius'      => '',
            'group'       => '',
            'lazy'        => TRUE,
        ), is_array($options) ? $options : array());

        $height = max(0, (int) $height);
        $width  = max(0, (int) $width);

        $relative = image_thumb_path($image_path);

        // Fall back to the stand-in image before falling back to a letter.
        if ($relative === FALSE && $options['placeholder'] !== FALSE)
        {
            $relative = image_thumb_path(
                ($options['placeholder'] === TRUE) ? IMAGE_THUMB_PLACEHOLDER : $options['placeholder']
            );
        }
        $shape    = strtolower(trim((string) $shape));
        $circle   = ($shape === 'circle' OR $shape === 'round');

        // ----- the rendered box -------------------------------------------
        if (is_array($options['size']))
        {
            $box_width  = isset($options['size'][0]) ? (int) $options['size'][0] : 0;
            $box_height = isset($options['size'][1]) ? (int) $options['size'][1] : $box_width;
        }
        elseif ((int) $options['size'] > 0)
        {
            $box_width = $box_height = (int) $options['size'];
        }
        else
        {
            $box_width  = $width;
            $box_height = $height;
        }

        // A fancybox link is only rendered when there is an image to open.
        $lightbox = ((bool) $lightbox && $relative !== FALSE);

        $classes = array();
        $lightbox && $classes[] = 'fancybox';
        $classes[] = 'admin-thumb';
        $classes[] = $circle ? 'admin-thumb--circle' : 'admin-thumb--square';

        // object-fit cover needs both sides pinned; otherwise let the image
        // keep its natural size inside the box.
        if ($box_width < 1 OR $box_height < 1)
        {
            $classes[] = 'admin-thumb--auto';
        }

        if ($options['class'] !== '')
        {
            $classes[] = $options['class'];
        }

        $style = '';
        $box_width > 0 && $style .= 'width:'.$box_width.'px;';
        $box_height > 0 && $style .= 'height:'.$box_height.'px;';

        // Only ever let plain CSS length values through into the attribute.
        if ( ! $circle && $options['radius'] !== '' && preg_match('#^[0-9a-z%. /]+$#i', (string) $options['radius']))
        {
            $style .= 'border-radius:'.$options['radius'].';';
        }

        $attributes = ' class="'.image_thumb_escape(implode(' ', $classes)).'"';
        $style === '' OR $attributes .= ' style="'.image_thumb_escape($style).'"';

        if ($options['aria_label'] !== '')
        {
            $attributes .= ' aria-label="'.image_thumb_escape($options['aria_label']).'"';
        }

        // ----- no usable file: render the fallback box ---------------------
        if ($relative === FALSE)
        {
            // Nothing to show at all: render nothing rather than an empty box.
            if ($options['fallback'] === '')
            {
                return '';
            }

            $inner = '<span aria-hidden="true">'.image_thumb_escape($options['fallback']).'</span>';

            return ($options['href'] === '')
                ? '<span'.$attributes.'>'.$inner.'</span>'
                : '<a'.$attributes.' href="'.image_thumb_escape($options['href']).'">'.$inner.'</a>';
        }

        // ----- the thumbnail ----------------------------------------------
        $CI =& get_instance();
        isset($CI->imagethumb) OR $CI->load->library('imagethumb');

        $source = $CI->imagethumb->image('./'.$relative, $width, $height, $output_type);

        $image = '<img src="'.image_thumb_escape($source).'"';
        $width > 0 && $image .= ' width="'.$width.'"';
        $height > 0 && $image .= ' height="'.$height.'"';
        $image .= ' alt="'.image_thumb_escape($options['alt']).'"';
        $options['lazy'] && $image .= ' loading="lazy" decoding="async"';
        $image .= '>';

        if ($lightbox)
        {
            $group = ($options['group'] === '')
                ? ''
                : ' data-fancybox-group="'.image_thumb_escape($options['group']).'"';

            return '<a'.$attributes.$group.' href="'.image_thumb_escape(image_thumb_url($relative)).'">'.$image.'</a>';
        }

        return ($options['href'] === '')
            ? '<span'.$attributes.'>'.$image.'</span>'
            : '<a'.$attributes.' href="'.image_thumb_escape($options['href']).'">'.$image.'</a>';
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('image_thumb_src'))
{
    /**
     * The thumbnail URL on its own, for the odd place that needs the src
     * without any markup around it (an og:image tag, an inline style, ...).
     *
     * @param string $image_path
     * @param int $height
     * @param int $width
     * @param string|int $output_type
     * @return string Empty string when the source file does not exist.
     */
    function image_thumb_src($image_path, $height = 0, $width = 0, $output_type = 'webp')
    {
        $relative = image_thumb_path($image_path);

        if ($relative === FALSE)
        {
            return '';
        }

        $CI =& get_instance();
        isset($CI->imagethumb) OR $CI->load->library('imagethumb');

        return $CI->imagethumb->image('./'.$relative, max(0, (int) $width), max(0, (int) $height), $output_type);
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('upload_thumb'))
{
    /**
     * Resolves a frontend module upload to a resized public URL.
     *
     * Only the stored filename's basename is accepted. Missing or invalid
     * uploads use the supplied frontend fallback image.
     *
     * @param string $subdir
     * @param string|null $filename
     * @param int $width
     * @param int $height
     * @param string $fallback
     * @return string
     */
    function upload_thumb($subdir, $filename, $width, $height, $fallback)
    {
        $relative = '';
        $filename = trim((string) $filename);

        if ($filename !== '')
        {
            $safe = basename($filename);
            if ($safe !== '' && $safe !== '.' && $safe !== '..')
            {
                $candidate = 'assets/frontend/images/'.trim($subdir, '/').'/'.$safe;
                if (is_file(FCPATH.$candidate))
                {
                    $relative = $candidate;
                }
            }
        }

        if ($relative === '')
        {
            $fallback = ltrim((string) $fallback, '/');
            if ($fallback === '')
            {
                return '';
            }
            $relative = preg_match('#^(css|images|js|vendor)/#', $fallback) === 1
                ? 'assets/frontend/'.$fallback
                : $fallback;
        }

        $src = image_thumb_src($relative, (int) $height, (int) $width, 'webp');

        return $src !== '' ? $src : base_url($relative);
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('upload_thumb_candidates'))
{
    /**
     * Resolves the first available module upload to a resized public URL.
     *
     * Candidate filenames are checked in priority order. The ordinary
     * frontend fallback is used only when none of those uploads exists.
     *
     * @param string $subdir
     * @param array $filenames
     * @param int $width
     * @param int $height
     * @param string $fallback
     * @return string
     */
    function upload_thumb_candidates($subdir, array $filenames, $width, $height, $fallback = '')
    {
        foreach ($filenames as $filename)
        {
            $src = upload_thumb($subdir, $filename, $width, $height, '');
            if ($src !== '')
            {
                return $src;
            }
        }

        return upload_thumb($subdir, null, $width, $height, $fallback);
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('image_thumb_path'))
{
    /**
     * Normalises a source path to one relative to the front controller and
     * confirms that it points at a readable file inside the project.
     *
     * @param string $image_path
     * @return string|bool 'assets/...' or FALSE.
     */
    function image_thumb_path($image_path)
    {
        $path = is_string($image_path) ? trim($image_path) : '';

        if ($path === '' OR ! defined('FCPATH'))
        {
            return FALSE;
        }

        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', FCPATH);

        if ($root !== '' && strpos($path, $root) === 0)
        {
            $path = substr($path, strlen($root));
        }

        if (strpos($path, './') === 0)
        {
            $path = substr($path, 2);
        }

        $path = ltrim($path, '/');

        // Refuse traversal, and anything that is not a real file.
        if ($path === '' OR strpos($path, '..') !== FALSE OR ! is_file(FCPATH.$path))
        {
            return FALSE;
        }

        return $path;
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('image_thumb_url'))
{
    /**
     * Public URL of a project relative path, each segment URL encoded so that
     * spaces and non-ASCII file names survive.
     *
     * @param string $relative_path
     * @return string
     */
    function image_thumb_url($relative_path)
    {
        return base_url(implode('/', array_map('rawurlencode', explode('/', $relative_path))));
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('image_thumb_escape'))
{
    /**
     * @param string $value
     * @return string
     */
    function image_thumb_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('delete_uploaded_file'))
{
    /**
     * Deletes an uploaded file, plus every cached thumbnail variant that
     * Imagethumb generated from it (see Imagethumb::_cache_path()), so a
     * record delete or a replaced upload never leaves orphaned thumbnails
     * behind on disk.
     *
     * Safe to call for a document upload (pdf/doc/docx): only the original
     * is removed in that case, since Imagethumb only ever generates variants
     * for image types.
     *
     * The caller is responsible for any "is this filename still referenced
     * by another record" check before calling this (see the per-module
     * deleteXxxFile() methods) - this function unconditionally removes what
     * it is given.
     *
     * @param string $directory Absolute directory the file lives in.
     * @param string $filename  Bare filename, no path segments.
     * @return void
     */
    function delete_uploaded_file($directory, $filename)
    {
        $filename = is_string($filename) ? trim($filename) : '';

        // basename() equality rejects any path segment (including '..').
        if ($filename === '' OR basename($filename) !== $filename)
        {
            return;
        }

        $directory = rtrim(str_replace('\\', '/', (string) $directory), '/');

        if ($directory === '' OR ! is_dir($directory))
        {
            return;
        }

        $path = $directory.'/'.$filename;
        is_file($path) && @unlink($path);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageTypes = defined('UPLOAD_IMAGE_MIMES') ? explode('|', UPLOAD_IMAGE_MIMES) : array('png', 'jpg', 'jpeg', 'webp');

        if (in_array($extension, $imageTypes, TRUE))
        {
            delete_uploaded_file_variants($directory, $filename);
        }
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('delete_uploaded_file_variants'))
{
    /**
     * Removes every cached Imagethumb variant of an uploaded image.
     *
     * A variant filename always starts with the original file's name (minus
     * extension), followed by "_{width}x{height}." and the original
     * extension, optionally followed by ".{output-extension}" when the
     * thumbnail was converted to a different format:
     *
     *     example.png ->  example_132x0.png            (same format)
     *                     example_132x132.png.webp     (converted format)
     *
     * Anchoring on the original name AND its original extension keeps this
     * unambiguous even when two different source files share the same name
     * with a different extension (example.png and example.jpg).
     *
     * @param string $directory Absolute directory the file lives in.
     * @param string $filename  Bare filename of the original image.
     * @return void
     */
    function delete_uploaded_file_variants($directory, $filename)
    {
        $stem = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($stem === '' OR $extension === '' OR ! is_dir($directory))
        {
            return;
        }

        $pattern = '/^'.preg_quote($stem, '/').'_\d+x\d+\.'.preg_quote($extension, '/').'(\.[a-z0-9]+)?$/';

        foreach (scandir($directory) as $entry)
        {
            if ($entry === '.' OR $entry === '..' OR ! preg_match($pattern, $entry))
            {
                continue;
            }

            $variantPath = $directory.'/'.$entry;
            is_file($variantPath) && @unlink($variantPath);
        }
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('favicon_source_path'))
{
    /**
     * Resolves and validates the favicon source uploaded through the website
     * settings ('site_settings.favicon', id = 1): the stored filename must
     * name a real, readable image inside FAVICON_SOURCE_DIRECTORY.
     *
     * The record is only fetched once per request, so repeated calls (admin
     * header markup, a future frontend header, ...) never duplicate the query.
     *
     * @return string|bool 'assets/frontend/images/logo/xxx.png' or FALSE.
     */
    function favicon_source_path()
    {
        static $resolved = NULL;
        static $path      = FALSE;

        if ($resolved === TRUE)
        {
            return $path;
        }

        $resolved = TRUE;

        $CI =& get_instance();
        $filename = $CI->SqlModel->getSingleField('favicon', 'site_settings', array('id' => 1));
        $filename = is_string($filename) ? basename(trim($filename)) : '';

        if ($filename === '')
        {
            return $path;
        }

        $relative = image_thumb_path(FAVICON_SOURCE_DIRECTORY.$filename);

        // Must resolve to a file inside the expected upload directory (guards
        // against a stored value that basename() alone would not neutralise)
        // and must actually be a readable image, never the generic placeholder.
        if ($relative === FALSE
            OR strpos($relative, FAVICON_SOURCE_DIRECTORY) !== 0
            OR $relative === ltrim(IMAGE_THUMB_PLACEHOLDER, './')
            OR @getimagesize(FCPATH.$relative) === FALSE)
        {
            return $path;
        }

        $path = $relative;

        return $path;
    }
}

// ------------------------------------------------------------------------

if ( ! function_exists('favicon_tags'))
{
    /**
     * Renders the favicon <link> tags for the current site, built from the
     * 'site_settings.favicon' source via Imagethumb. Shared between the
     * admin area and the front end so both point at the same source of
     * truth and the same generated files.
     *
     * PNG is used throughout (Imagethumb has no ICO support, and a renamed
     * non-PNG file would misreport its own 'type' attribute). When the
     * installed GD build cannot write PNG, no tags are rendered rather than
     * mislabeling the generated format.
     *
     * @return string Escaped <link> markup (one per variant), or '' when no
     *                valid favicon exists.
     */
    function favicon_tags()
    {
        static $markup = NULL;

        if ($markup !== NULL)
        {
            return $markup;
        }

        $markup = '';

        $relative = favicon_source_path();

        if ($relative === FALSE)
        {
            return $markup;
        }

        $CI =& get_instance();
        isset($CI->imagethumb) OR $CI->load->library('imagethumb');

        if ( ! $CI->imagethumb->supports('png'))
        {
            return $markup;
        }

        // rel/sizes/type follow current browser and platform expectations:
        // 16/32/48 for browser tabs and search, 180 for iOS home screens,
        // 192/512 for Android/PWA style application icons.
        $variants = array(
            array('size' => 16,  'rel' => 'icon'),
            array('size' => 32,  'rel' => 'icon'),
            array('size' => 48,  'rel' => 'icon'),
            array('size' => 180, 'rel' => 'apple-touch-icon'),
            array('size' => 192, 'rel' => 'icon'),
            array('size' => 512, 'rel' => 'icon'),
        );

        $links = array();

        foreach ($variants as $variant)
        {
            $size = $variant['size'];
            $url  = $CI->imagethumb->image('./'.$relative, $size, $size, 'png');

            if ($url === '')
            {
                continue;
            }

            $links[] = '<link rel="'.image_thumb_escape($variant['rel']).'" type="image/png" sizes="'.$size.'x'.$size.'" href="'.image_thumb_escape($url).'">';
        }

        $markup = implode("\n    ", $links);

        return $markup;
    }
}
