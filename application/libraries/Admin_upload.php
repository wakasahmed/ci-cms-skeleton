<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared upload handling for manage/admin modules.
 *
 * Files are validated by CodeIgniter's Upload library (extension and MIME
 * type against config/mimes.php), limited to UPLOAD_SIZE and stored under a
 * random name, so the original filename is never trusted.
 *
 * Callers pass fixed, code-defined directories relative to FCPATH. Whether a
 * file is still referenced by another record is the caller's decision; this
 * library only removes what it is given.
 */
class Admin_upload
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('image');
    }

    /**
     * Saves one uploaded file from $_FILES[$field].
     *
     * Returns array('filename' => string, 'error' => string). Both are empty
     * when no file was supplied, so optional uploads need no special case.
     */
    public function save($field, $relativeDirectory, $allowedTypes = UPLOAD_IMAGE_MIMES)
    {
        $result = array(
            'filename' => '',
            'error' => '',
        );

        if (empty($_FILES[$field]['name'])) {
            return $result;
        }

        if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = 'The upload did not complete successfully.';

            return $result;
        }

        if ((int) $_FILES[$field]['size'] > (int) UPLOAD_SIZE) {
            $result['error'] = 'The file must be '.UPLOAD_SIZE_MB.' MB or smaller.';

            return $result;
        }

        $uploadPath = $this->absoluteDirectory($relativeDirectory);

        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE)) {
            $result['error'] = 'The upload directory could not be created.';

            return $result;
        }

        $this->CI->load->library('upload');
        $this->CI->upload->initialize(
            array(
                'upload_path' => $uploadPath,
                'allowed_types' => $allowedTypes,
                'max_size' => UPLOAD_SIZE_MB * 1024,
                'encrypt_name' => TRUE,
                'remove_spaces' => TRUE,
            ),
            TRUE
        );

        if (!$this->CI->upload->do_upload($field)) {
            $result['error'] = strip_tags($this->CI->upload->display_errors('', ''));

            return $result;
        }

        $file = $this->CI->upload->data();
        $result['filename'] = $file['file_name'];

        return $result;
    }

    /**
     * Removes a stored file and its cached thumbnails.
     */
    public function delete($relativeDirectory, $filename)
    {
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
            return;
        }

        delete_uploaded_file($this->absoluteDirectory($relativeDirectory), $filename);
    }

    /**
     * Copies a stored image to a new random name in the same directory.
     *
     * Returns the new filename, or '' when the source is missing or not an
     * allowed image type.
     */
    public function copy($relativeDirectory, $filename)
    {
        $filename = basename((string) $filename);

        if ($filename === '') {
            return '';
        }

        $directory = $this->absoluteDirectory($relativeDirectory);
        $source = $directory.$filename;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = array_filter(explode('|', UPLOAD_IMAGE_MIMES));

        if (!is_file($source) || !in_array($extension, $allowed, TRUE)) {
            return '';
        }

        try {
            $copy = bin2hex(random_bytes(16)).'.'.$extension;
        } catch (Throwable $exception) {
            return '';
        }

        return @copy($source, $directory.$copy) ? $copy : '';
    }

    private function absoluteDirectory($relativeDirectory)
    {
        return FCPATH.trim((string) $relativeDirectory, '/\\').DIRECTORY_SEPARATOR;
    }
}
