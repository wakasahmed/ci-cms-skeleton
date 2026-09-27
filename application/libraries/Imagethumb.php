<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Imagethumb
 *
 * Creates (and caches on disk) resized / centre-cropped thumbnails using the
 * GD extension and returns a public URL pointing at the generated file.
 *
 * The public signature is backwards compatible with the previous version:
 *
 *     $this->imagethumb->image('./assets/frontend/images/logo/logo.png', 120, 0);
 *     $this->imagethumb->image($path, 200);
 *
 * A fourth optional parameter selects the output format:
 *
 *     $this->imagethumb->image($path, 300, 200, 'webp');
 *
 * Accepted values for $output_type:
 *     NULL | '' | 'auto' | 'source'   keep the source format (default)
 *     'webp' | 'jpg' | 'jpeg' | 'png' | 'gif' | 'bmp'
 *     an IMAGETYPE_* constant
 *
 * If the requested format cannot be written by the installed GD build the
 * source format is used instead, so a caller never receives a broken URL.
 *
 * Improvements over the previous implementation:
 *  - WebP support for both input and output.
 *  - Uses GD directly instead of CI's image_lib: the resize and the crop are
 *    performed in a single imagecopyresampled() pass, so the thumbnail is
 *    built from the original pixels instead of from an already re-encoded
 *    intermediate file (better quality, roughly half the disk I/O).
 *  - The alpha channel (PNG / WebP / GIF) is preserved, and images are
 *    flattened onto a background colour when the target format has no alpha.
 *  - JPEG EXIF orientation is honoured.
 *  - Thumbnails are written to a temporary file and renamed into place, so a
 *    concurrent request can never serve a half written image.
 *  - Defensive: missing, unreadable, corrupt or oversized (memory) sources
 *    degrade to the placeholder / original URL instead of raising PHP errors.
 *
 * Requires ext-gd. Compatible with PHP 5.6 up to PHP 7.4.
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Image
 */

// IMAGETYPE_WEBP only exists from PHP 7.1 onwards.
defined('IMAGETYPE_WEBP') OR define('IMAGETYPE_WEBP', 18);
defined('IMAGETYPE_BMP') OR define('IMAGETYPE_BMP', 6);

class Imagethumb
{

    /**
     * Image used when the requested file is missing or unreadable.
     *
     * @var string
     */
    public $placeholder = './assets/frontend/images/no_image.jpg';

    /**
     * Encoding quality / compression settings.
     *
     * @var int
     */
    public $jpeg_quality = 90;
    public $webp_quality = 82;
    public $png_compression = 6;

    /**
     * Write progressive (interlaced) JPEG files.
     *
     * @var bool
     */
    public $interlace_jpeg = TRUE;

    /**
     * Rotate JPEG sources according to their EXIF orientation tag.
     *
     * @var bool
     */
    public $auto_orient = TRUE;

    /**
     * Background (RGB) used when converting an image that has transparency to
     * a format that does not support it (JPEG / BMP).
     *
     * @var array
     */
    public $flatten_background = array(255, 255, 255);

    /**
     * Preserving GIF transparency needs a per-pixel pass (see _save_gif()).
     * Outputs larger than this many pixels are written opaque instead.
     *
     * @var int
     */
    public $gif_transparency_limit = 4000000;

    /**
     * Aliases accepted by the $output_type parameter.
     *
     * @var array
     */
    protected static $_types = array(
        'jpg'  => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'jpe'  => IMAGETYPE_JPEG,
        'png'  => IMAGETYPE_PNG,
        'gif'  => IMAGETYPE_GIF,
        'webp' => IMAGETYPE_WEBP,
        'bmp'  => IMAGETYPE_BMP,
    );

    // ------------------------------------------------------------------------

    /**
     * Returns the public URL of the thumbnail, generating it when needed.
     *
     * @param   string      $image_path     Path to the source image, relative to
     *                                      the front controller ('./assets/...').
     * @param   int         $width          Target width, 0 = derive from height.
     * @param   int         $height         Target height, 0 = derive from width.
     * @param   string|int  $output_type    Output format, NULL keeps the source one.
     * @return  string
     */
    public function image($image_path, $width = 0, $height = 0, $output_type = NULL)
    {
        return $this->_to_url($this->file($image_path, $width, $height, $output_type));
    }

    // ------------------------------------------------------------------------

    /**
     * Same as image() but returns the file system path of the thumbnail.
     * Handy when the caller needs filesize(), getimagesize(), and so on.
     *
     * @param   string      $image_path
     * @param   int         $width
     * @param   int         $height
     * @param   string|int  $output_type
     * @return  string
     */
    public function file($image_path, $width = 0, $height = 0, $output_type = NULL)
    {
        $source = $this->_resolve_source($image_path);

        if ($source === FALSE)
        {
            // Nothing readable to work with: hand the original path back so the
            // markup keeps rendering instead of blowing up.
            return (string) $image_path;
        }

        $width  = max(0, (int) $width);
        $height = max(0, (int) $height);

        $info = $this->_read_info($source);

        if ($info === FALSE)
        {
            return $source;
        }

        list($source_type, $source_width, $source_height) = $info;

        $target_type = $this->_resolve_output_type($output_type, $source_type);
        $cache       = $this->_cache_path($source, $width, $height, $source_type, $target_type);

        if ($this->_is_fresh($cache, $source))
        {
            return $cache;
        }

        // Same format and no resize requested: nothing to re-encode.
        if ($width === 0 && $height === 0 && $target_type === $source_type)
        {
            return $source;
        }

        if ( ! $this->_has_memory_for($source_width, $source_height)
            OR ! $this->_is_writable_dir(dirname($cache))
            OR $this->_generate($source, $cache, $source_type, $target_type, $width, $height) === FALSE)
        {
            return $source;
        }

        return $cache;
    }

    // ------------------------------------------------------------------------

    /**
     * Whether the installed GD build can read and write the given format.
     *
     * @param   string|int  $type   'webp', 'jpg', ... or an IMAGETYPE_* constant.
     * @return  bool
     */
    public function supports($type)
    {
        $type = is_int($type) ? $type : $this->_type_from_alias($type);

        return ($type !== FALSE && $this->_can_write($type) && $this->_can_read($type));
    }

    // ------------------------------------------------------------------------
    // Generation
    // ------------------------------------------------------------------------

    /**
     * Reads the source, resizes / crops it in one pass and writes the result.
     *
     * @param   string  $source
     * @param   string  $cache
     * @param   int     $source_type
     * @param   int     $target_type
     * @param   int     $width
     * @param   int     $height
     * @return  bool
     */
    protected function _generate($source, $cache, $source_type, $target_type, $width, $height)
    {
        $image = $this->_create_from_file($source, $source_type);

        if ($image === FALSE)
        {
            return FALSE;
        }

        if ($this->auto_orient && $source_type === IMAGETYPE_JPEG)
        {
            $image = $this->_apply_orientation($image, $source);
        }

        $source_width  = imagesx($image);
        $source_height = imagesy($image);

        if ($source_width < 1 OR $source_height < 1)
        {
            imagedestroy($image);
            return FALSE;
        }

        list($scaled_width, $scaled_height, $target_width, $target_height)
            = $this->_dimensions($source_width, $source_height, $width, $height);

        // Map the centred crop rectangle back onto the original pixels, so the
        // resize and the crop are done with a single resample.
        $src_x = 0;
        $src_y = 0;
        $src_w = $source_width;
        $src_h = $source_height;

        if ($target_width !== $scaled_width OR $target_height !== $scaled_height)
        {
            $x_axis = max(0, floor(($scaled_width - $target_width) / 2));
            $y_axis = max(0, floor(($scaled_height - $target_height) / 2));

            $src_x = (int) round($x_axis * $source_width / $scaled_width);
            $src_y = (int) round($y_axis * $source_height / $scaled_height);
            $src_w = (int) round($target_width * $source_width / $scaled_width);
            $src_h = (int) round($target_height * $source_height / $scaled_height);

            $src_w = max(1, min($src_w, $source_width - $src_x));
            $src_h = max(1, min($src_h, $source_height - $src_y));
        }

        $canvas = imagecreatetruecolor($target_width, $target_height);

        if ($canvas === FALSE)
        {
            imagedestroy($image);
            return FALSE;
        }

        $this->_prepare_canvas($canvas, $target_type);

        $resampled = imagecopyresampled(
            $canvas, $image,
            0, 0, $src_x, $src_y,
            $target_width, $target_height, $src_w, $src_h
        );

        imagedestroy($image);

        if ($resampled === FALSE)
        {
            imagedestroy($canvas);
            return FALSE;
        }

        $saved = $this->_save($canvas, $cache, $target_type);
        imagedestroy($canvas);

        return $saved;
    }

    // ------------------------------------------------------------------------

    /**
     * Prepares the destination canvas: transparent for formats that carry an
     * alpha channel, flattened onto the background colour otherwise.
     *
     * @param   resource    $canvas
     * @param   int         $target_type
     * @return  void
     */
    protected function _prepare_canvas($canvas, $target_type)
    {
        $width  = imagesx($canvas);
        $height = imagesy($canvas);

        if ($this->_has_alpha($target_type))
        {
            imagealphablending($canvas, FALSE);
            imagesavealpha($canvas, TRUE);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $transparent);

            return;
        }

        $rgb = $this->flatten_background;
        $background = imagecolorallocate(
            $canvas,
            isset($rgb[0]) ? (int) $rgb[0] : 255,
            isset($rgb[1]) ? (int) $rgb[1] : 255,
            isset($rgb[2]) ? (int) $rgb[2] : 255
        );

        imagealphablending($canvas, TRUE);
        imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $background);
    }

    // ------------------------------------------------------------------------

    /**
     * Encodes the canvas into a temporary file and moves it into place, so a
     * parallel request can never pick up a partially written thumbnail.
     *
     * @param   resource    $canvas
     * @param   string      $cache
     * @param   int         $target_type
     * @return  bool
     */
    protected function _save($canvas, $cache, $target_type)
    {
        $temp = $cache.'.'.getmypid().'.tmp';
        $done = FALSE;

        switch ($target_type)
        {
            case IMAGETYPE_JPEG:

                if ($this->interlace_jpeg)
                {
                    imageinterlace($canvas, 1);
                }

                $done = @imagejpeg($canvas, $temp, $this->_clamp($this->jpeg_quality, 0, 100));
                break;

            case IMAGETYPE_PNG:
                $done = @imagepng($canvas, $temp, $this->_clamp($this->png_compression, 0, 9));
                break;

            case IMAGETYPE_GIF:
                $done = $this->_save_gif($canvas, $temp);
                break;

            case IMAGETYPE_WEBP:
                $done = @imagewebp($canvas, $temp, $this->_clamp($this->webp_quality, 0, 100));
                break;

            case IMAGETYPE_BMP:
                $done = function_exists('imagebmp') ? @imagebmp($canvas, $temp) : FALSE;
                break;
        }

        if ($done === FALSE OR ! is_file($temp) OR filesize($temp) === 0)
        {
            is_file($temp) && @unlink($temp);
            return FALSE;
        }

        if (@rename($temp, $cache) === FALSE)
        {
            // Windows refuses rename() when the destination already exists.
            @unlink($cache);

            if (@rename($temp, $cache) === FALSE)
            {
                @unlink($temp);
                return FALSE;
            }
        }

        @chmod($cache, defined('FILE_WRITE_MODE') ? FILE_WRITE_MODE : 0644);

        return TRUE;
    }

    // ------------------------------------------------------------------------

    /**
     * Writes a GIF, keeping the transparent areas transparent.
     *
     * imagegif() silently drops the alpha channel of a truecolor canvas, so the
     * transparent pixels are recorded first, the canvas is quantised to a
     * palette, and those pixels are then painted with a colour that is flagged
     * as the palette transparent index.
     *
     * @param   resource    $canvas
     * @param   string      $temp
     * @return  bool
     */
    protected function _save_gif($canvas, $temp)
    {
        $width  = imagesx($canvas);
        $height = imagesy($canvas);

        if (($width * $height) > $this->gif_transparency_limit)
        {
            return @imagegif($canvas, $temp);
        }

        $mask        = '';
        $transparent = FALSE;

        for ($y = 0; $y < $height; $y++)
        {
            for ($x = 0; $x < $width; $x++)
            {
                if (((imagecolorat($canvas, $x, $y) >> 24) & 0x7F) > 63)
                {
                    $mask .= '1';
                    $transparent = TRUE;
                }
                else
                {
                    $mask .= '0';
                }
            }
        }

        if ($transparent)
        {
            imagealphablending($canvas, TRUE);
            imagetruecolortopalette($canvas, FALSE, 255);
            $key = imagecolorallocate($canvas, 254, 0, 254);

            if ($key !== FALSE)
            {
                for ($y = 0; $y < $height; $y++)
                {
                    $row = $y * $width;

                    for ($x = 0; $x < $width; $x++)
                    {
                        if ($mask[$row + $x] === '1')
                        {
                            imagesetpixel($canvas, $x, $y, $key);
                        }
                    }
                }

                imagecolortransparent($canvas, $key);
            }
        }

        return @imagegif($canvas, $temp);
    }

    // ------------------------------------------------------------------------
    // Geometry
    // ------------------------------------------------------------------------

    /**
     * Works out the "cover" size and the final (cropped) size.
     *
     * The rules are identical to the previous implementation: the image is
     * scaled so that it covers the requested box and is then centre-cropped
     * when both a width and a height were given. A zero width or height means
     * "follow the aspect ratio and do not crop on that axis".
     *
     * @param   int $source_width
     * @param   int $source_height
     * @param   int $width
     * @param   int $height
     * @return  array   array($scaled_width, $scaled_height, $target_width, $target_height)
     */
    protected function _dimensions($source_width, $source_height, $width, $height)
    {
        if ($width === 0 && $height === 0)
        {
            // Format conversion only.
            return array($source_width, $source_height, $source_width, $source_height);
        }

        // When only one dimension is requested, derive the other from the
        // source aspect ratio. Keeping the scaled and target sizes identical
        // ensures that neither axis enters the crop path in _generate().
        if ($width > 0 && $height === 0)
        {
            $auto_height = (int) max(1, ceil($width * $source_height / $source_width));

            return array($width, $auto_height, $width, $auto_height);
        }

        if ($width === 0 && $height > 0)
        {
            $auto_width = (int) max(1, ceil($height * $source_width / $source_height));

            return array($auto_width, $height, $auto_width, $height);
        }

        $ratio = $source_width / $source_height;

        if ($width > $height)
        {
            $scaled_width  = $width;
            $scaled_height = $scaled_width / $ratio;

            if ($scaled_height < $height)
            {
                $scaled_height = $height;
                $scaled_width  = $scaled_height * $ratio;
            }
        }
        else
        {
            $scaled_height = $height;
            $scaled_width  = $scaled_height * $ratio;

            if ($scaled_width < $width)
            {
                $scaled_width  = $width;
                $scaled_height = $scaled_width / $ratio;
            }
        }

        $scaled_width  = (int) max(1, ceil($scaled_width));
        $scaled_height = (int) max(1, ceil($scaled_height));

        $target_width  = min($width, $scaled_width);
        $target_height = min($height, $scaled_height);

        return array($scaled_width, $scaled_height, $target_width, $target_height);
    }

    // ------------------------------------------------------------------------
    // Source handling
    // ------------------------------------------------------------------------

    /**
     * Returns a readable source path, falling back to the placeholder.
     *
     * @param   string  $image_path
     * @return  string|bool     FALSE when nothing readable was found.
     */
    protected function _resolve_source($image_path)
    {
        $image_path = is_string($image_path) ? trim($image_path) : '';

        if ($image_path !== '' && is_file($image_path) && is_readable($image_path) && filesize($image_path) > 0)
        {
            return $image_path;
        }

        $placeholder = (string) $this->placeholder;

        if ($placeholder !== '' && is_file($placeholder) && is_readable($placeholder))
        {
            return $placeholder;
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Detects the image type and size. Falls back to sniffing the file header
     * because getimagesize() only understands WebP from PHP 7.1 onwards.
     *
     * @param   string  $path
     * @return  array|bool  array($type, $width, $height) or FALSE.
     */
    protected function _read_info($path)
    {
        $size = @getimagesize($path);

        if (is_array($size) && ! empty($size[0]) && ! empty($size[1]) && ! empty($size[2]))
        {
            return array((int) $size[2], (int) $size[0], (int) $size[1]);
        }

        if ($this->_is_webp($path) && $this->_can_read(IMAGETYPE_WEBP))
        {
            $image = @imagecreatefromwebp($path);

            if ($this->_is_image($image))
            {
                $info = array(IMAGETYPE_WEBP, imagesx($image), imagesy($image));
                imagedestroy($image);

                return $info;
            }
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * RIFF....WEBP header check.
     *
     * @param   string  $path
     * @return  bool
     */
    protected function _is_webp($path)
    {
        $handle = @fopen($path, 'rb');

        if ($handle === FALSE)
        {
            return FALSE;
        }

        $header = fread($handle, 12);
        fclose($handle);

        return (is_string($header) && strlen($header) === 12
            && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP');
    }

    // ------------------------------------------------------------------------

    /**
     * @param   string  $path
     * @param   int     $type
     * @return  resource|bool
     */
    protected function _create_from_file($path, $type)
    {
        $image = FALSE;

        switch ($type)
        {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($path);
                break;

            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($path);
                break;

            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($path);
                break;

            case IMAGETYPE_WEBP:
                $image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : FALSE;
                break;

            case IMAGETYPE_BMP:
                $image = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : FALSE;
                break;
        }

        return $this->_is_image($image) ? $image : FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Rotates / flips a JPEG according to its EXIF orientation tag, so the
     * thumbnail matches what a browser shows for the original file.
     *
     * @param   resource    $image
     * @param   string      $path
     * @return  resource
     */
    protected function _apply_orientation($image, $path)
    {
        if ( ! function_exists('exif_read_data'))
        {
            return $image;
        }

        $exif = @exif_read_data($path);

        if ( ! is_array($exif) OR empty($exif['Orientation']))
        {
            return $image;
        }

        $degrees = 0;
        $flip    = FALSE;

        switch ((int) $exif['Orientation'])
        {
            case 2:
                $flip = TRUE;
                break;

            case 3:
                $degrees = 180;
                break;

            case 4:
                $degrees = 180;
                $flip = TRUE;
                break;

            case 5:
                $degrees = 270;
                $flip = TRUE;
                break;

            case 6:
                $degrees = 270;
                break;

            case 7:
                $degrees = 90;
                $flip = TRUE;
                break;

            case 8:
                $degrees = 90;
                break;

            default:
                return $image;
        }

        if ($degrees !== 0)
        {
            $rotated = @imagerotate($image, $degrees, 0);

            if ($this->_is_image($rotated))
            {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        if ($flip && function_exists('imageflip'))
        {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    // ------------------------------------------------------------------------
    // Cache / paths
    // ------------------------------------------------------------------------

    /**
     * Builds the cache file name, next to the source image.
     *
     * The historical "name_WxH.ext" pattern is kept when the output format
     * matches the source one, so thumbnails generated by the previous version
     * stay valid. When the format is converted the new extension is appended
     * to the old one ("logo_300x0.png.webp"), which keeps every variant of the
     * same image collision free.
     *
     * @param   string  $source
     * @param   int     $width
     * @param   int     $height
     * @param   int     $source_type
     * @param   int     $target_type
     * @return  string
     */
    protected function _cache_path($source, $width, $height, $source_type, $target_type)
    {
        $info      = pathinfo($source);
        $directory = isset($info['dirname']) ? $info['dirname'] : '.';
        $name      = isset($info['filename']) ? $info['filename'] : 'image';
        $extension = isset($info['extension']) ? $info['extension'] : $this->_extension_for($source_type);

        $suffix = ($target_type === $source_type)
            ? $extension
            : $extension.'.'.$this->_extension_for($target_type);

        return $directory.'/'.$name.'_'.$width.'x'.$height.'.'.$suffix;
    }

    // ------------------------------------------------------------------------

    /**
     * A cached thumbnail is usable while it exists, is not empty and is not
     * older than the source image.
     *
     * @param   string  $cache
     * @param   string  $source
     * @return  bool
     */
    protected function _is_fresh($cache, $source)
    {
        if ( ! is_file($cache) OR filesize($cache) === 0)
        {
            return FALSE;
        }

        return (filemtime($cache) >= filemtime($source));
    }

    // ------------------------------------------------------------------------

    /**
     * Converts a file system path into a public URL.
     *
     * @param   string  $path
     * @return  string
     */
    protected function _to_url($path)
    {
        if ( ! function_exists('base_url'))
        {
            get_instance()->load->helper('url');
        }

        $path = str_replace('\\', '/', (string) $path);

        if (defined('FCPATH'))
        {
            $root = str_replace('\\', '/', FCPATH);

            if ($root !== '' && strpos($path, $root) === 0)
            {
                $path = substr($path, strlen($root));
            }
        }

        if (strpos($path, './') === 0)
        {
            $path = substr($path, 2);
        }

        return base_url().ltrim($path, '/');
    }

    // ------------------------------------------------------------------------

    /**
     * @param   string  $directory
     * @return  bool
     */
    protected function _is_writable_dir($directory)
    {
        return (is_dir($directory) && is_writable($directory));
    }

    // ------------------------------------------------------------------------
    // Format helpers
    // ------------------------------------------------------------------------

    /**
     * Resolves the requested output format, falling back to the source format
     * when the requested one is unknown or unsupported by this GD build.
     *
     * @param   string|int  $output_type
     * @param   int         $source_type
     * @return  int
     */
    protected function _resolve_output_type($output_type, $source_type)
    {
        if ($output_type === NULL OR $output_type === '' OR $output_type === FALSE)
        {
            return $this->_can_write($source_type) ? $source_type : IMAGETYPE_JPEG;
        }

        $requested = is_int($output_type) ? $output_type : $this->_type_from_alias($output_type);

        if ($requested === FALSE OR ! $this->_can_write($requested))
        {
            return $this->_can_write($source_type) ? $source_type : IMAGETYPE_JPEG;
        }

        return $requested;
    }

    // ------------------------------------------------------------------------

    /**
     * @param   string  $alias
     * @return  int|bool
     */
    protected function _type_from_alias($alias)
    {
        $alias = strtolower(trim((string) $alias, " \t\n\r\0\x0B."));

        if ($alias === '' OR $alias === 'auto' OR $alias === 'source' OR $alias === 'original')
        {
            return FALSE;
        }

        return isset(self::$_types[$alias]) ? self::$_types[$alias] : FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * @param   int $type
     * @return  string
     */
    protected function _extension_for($type)
    {
        switch ($type)
        {
            case IMAGETYPE_JPEG: return 'jpg';
            case IMAGETYPE_PNG:  return 'png';
            case IMAGETYPE_GIF:  return 'gif';
            case IMAGETYPE_WEBP: return 'webp';
            case IMAGETYPE_BMP:  return 'bmp';
        }

        return 'jpg';
    }

    // ------------------------------------------------------------------------

    /**
     * @param   int $type
     * @return  bool
     */
    protected function _can_read($type)
    {
        switch ($type)
        {
            case IMAGETYPE_JPEG: return function_exists('imagecreatefromjpeg');
            case IMAGETYPE_PNG:  return function_exists('imagecreatefrompng');
            case IMAGETYPE_GIF:  return function_exists('imagecreatefromgif');
            case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp');
            case IMAGETYPE_BMP:  return function_exists('imagecreatefrombmp');
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * @param   int $type
     * @return  bool
     */
    protected function _can_write($type)
    {
        switch ($type)
        {
            case IMAGETYPE_JPEG: return function_exists('imagejpeg');
            case IMAGETYPE_PNG:  return function_exists('imagepng');
            case IMAGETYPE_GIF:  return function_exists('imagegif');
            case IMAGETYPE_WEBP: return function_exists('imagewebp');
            case IMAGETYPE_BMP:  return function_exists('imagebmp');
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * @param   int $type
     * @return  bool
     */
    protected function _has_alpha($type)
    {
        return ($type === IMAGETYPE_PNG OR $type === IMAGETYPE_WEBP OR $type === IMAGETYPE_GIF);
    }

    // ------------------------------------------------------------------------
    // Misc helpers
    // ------------------------------------------------------------------------

    /**
     * GD returns a resource on PHP 7 and a GdImage object on PHP 8.
     *
     * @param   mixed   $image
     * @return  bool
     */
    protected function _is_image($image)
    {
        return (is_object($image) OR is_resource($image));
    }

    // ------------------------------------------------------------------------

    /**
     * Rough guard against fatal "allowed memory size exhausted" errors on very
     * large sources: GD needs about 4 bytes per pixel, plus overhead.
     *
     * @param   int $width
     * @param   int $height
     * @return  bool
     */
    protected function _has_memory_for($width, $height)
    {
        $limit = $this->_memory_limit();

        if ($limit <= 0)
        {
            return TRUE;
        }

        $needed = (int) ($width * $height * 4 * 1.8) + 2097152;

        if (($limit - memory_get_usage(TRUE)) > $needed)
        {
            return TRUE;
        }

        @ini_set('memory_limit', (int) ceil((memory_get_usage(TRUE) + $needed) / 1048576).'M');
        $limit = $this->_memory_limit();

        return ($limit <= 0 OR ($limit - memory_get_usage(TRUE)) > $needed);
    }

    // ------------------------------------------------------------------------

    /**
     * memory_limit in bytes, 0 when unlimited.
     *
     * @return  int
     */
    protected function _memory_limit()
    {
        $limit = trim((string) @ini_get('memory_limit'));

        if ($limit === '' OR $limit === '-1')
        {
            return 0;
        }

        $value = (int) $limit;

        switch (strtolower(substr($limit, -1)))
        {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }

        return $value;
    }

    // ------------------------------------------------------------------------

    /**
     * @param   int $value
     * @param   int $min
     * @param   int $max
     * @return  int
     */
    protected function _clamp($value, $min, $max)
    {
        return (int) max($min, min($max, (int) $value));
    }

}
