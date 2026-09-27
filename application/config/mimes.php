<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| MIME TYPES
| -------------------------------------------------------------------
| This file contains an array of mime types.  It is used by the
| Upload class to help identify allowed file types.
|
| An extension that is missing from this list can never be uploaded:
| CI_Upload::is_allowed_filetype() rejects any extension it cannot find
| here, even when the extension is listed in the upload allowed_types.
|
| Where an extension maps to several mime types, the FIRST one is the
| canonical (IANA registered) type - get_mime_by_extension() and
| force_download() return that one, so keep it first. The remaining
| entries exist to tolerate what browsers and operating systems report
| when uploading.
|
| Grouped and refreshed against the IANA media types registry and the
| MDN common types list.
|
*/

return array(

    /* ---------------------------------------------------------------
     | Images
     | --------------------------------------------------------------- */
    'jpeg' => array('image/jpeg', 'image/pjpeg'),
    'jpg' => array('image/jpeg', 'image/pjpeg'),
    'jpe' => array('image/jpeg', 'image/pjpeg'),
    'jfif' => array('image/jpeg', 'image/pjpeg'),
    'pjpeg' => array('image/pjpeg', 'image/jpeg'),
    'pjp' => array('image/jpeg', 'image/pjpeg'),
    'png' => array('image/png', 'image/x-png'),
    'apng' => array('image/apng', 'image/png'),
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'avif' => array('image/avif', 'image/avif-sequence'),
    'heic' => array('image/heic', 'image/heif', 'image/heic-sequence'),
    'heif' => array('image/heif', 'image/heic', 'image/heif-sequence'),
    'bmp' => array('image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-ms-bmp', 'image/x-windows-bmp', 'image/x-win-bitmap'),
    'tiff' => array('image/tiff', 'image/x-tiff'),
    'tif' => array('image/tiff', 'image/x-tiff'),
    'ico' => array('image/vnd.microsoft.icon', 'image/x-icon', 'image/icon', 'text/plain'),
    'svg' => array('image/svg+xml', 'text/xml', 'application/xml'),
    'svgz' => array('image/svg+xml', 'application/gzip'),
    'psd' => array('image/vnd.adobe.photoshop', 'application/x-photoshop', 'application/photoshop', 'application/psd'),
    'jp2' => array('image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'),
    'jpf' => array('image/jpx', 'image/jp2'),
    'jpx' => 'image/jpx',
    'jxl' => 'image/jxl',
    'xcf' => 'image/x-xcf',
    'ai' => array('application/pdf', 'application/postscript'),
    'eps' => 'application/postscript',
    'ps' => 'application/postscript',

    /* ---------------------------------------------------------------
     | Archives / compressed
     | --------------------------------------------------------------- */
    'zip' => array('application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/s-compressed', 'multipart/x-zip'),
    'zipx' => array('application/zip', 'application/x-zip-compressed'),
    '7z' => array('application/x-7z-compressed', 'application/octet-stream'),
    'rar' => array('application/vnd.rar', 'application/x-rar', 'application/rar', 'application/x-rar-compressed', 'application/octet-stream'),
    'gz' => array('application/gzip', 'application/x-gzip', 'application/x-gunzip', 'application/gzipped', 'application/gzip-compressed', 'application/octet-stream'),
    'gzip' => array('application/gzip', 'application/x-gzip'),
    'bz' => array('application/x-bzip', 'application/bzip'),
    'bz2' => array('application/x-bzip2', 'application/bzip2', 'application/octet-stream'),
    'xz' => array('application/x-xz', 'application/octet-stream'),
    'zst' => array('application/zstd', 'application/octet-stream'),
    'tar' => array('application/x-tar', 'application/tar', 'application/x-gtar', 'application/octet-stream'),
    'tgz' => array('application/x-tar', 'application/x-gzip-compressed', 'application/x-gtar'),
    'tbz' => array('application/x-tar', 'application/x-bzip-compressed-tar'),
    'gtar' => 'application/x-gtar',
    'z' => 'application/x-compress',
    'cab' => array('application/vnd.ms-cab-compressed', 'application/octet-stream'),
    'sit' => 'application/x-stuffit',
    'sea' => 'application/octet-stream',
    'hqx' => 'application/mac-binhex40',
    'cpt' => 'application/mac-compactpro',

    /* ---------------------------------------------------------------
     | Documents - PDF and OpenDocument
     | --------------------------------------------------------------- */
    'pdf' => array('application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf', 'application/x-download'),
    'odt' => array('application/vnd.oasis.opendocument.text', 'application/zip'),
    'ott' => array('application/vnd.oasis.opendocument.text-template', 'application/zip'),
    'ods' => array('application/vnd.oasis.opendocument.spreadsheet', 'application/zip'),
    'odp' => array('application/vnd.oasis.opendocument.presentation', 'application/zip'),
    'odg' => array('application/vnd.oasis.opendocument.graphics', 'application/zip'),
    'odf' => array('application/vnd.oasis.opendocument.formula', 'application/zip'),
    'epub' => array('application/epub+zip', 'application/zip'),
    'mobi' => array('application/x-mobipocket-ebook', 'application/octet-stream'),

    /* ---------------------------------------------------------------
     | Documents - Microsoft Office
     | --------------------------------------------------------------- */
    'doc' => array('application/msword', 'application/vnd.ms-office', 'application/octet-stream'),
    'dot' => array('application/msword', 'application/vnd.ms-office'),
    'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip'),
    'dotx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'application/zip', 'application/msword'),
    'docm' => array('application/vnd.ms-word.document.macroEnabled.12', 'application/zip', 'application/msword'),
    'xls' => array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/octet-stream'),
    'xlt' => array('application/vnd.ms-excel', 'application/excel'),
    'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/msword', 'application/x-zip'),
    'xltx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'application/zip', 'application/vnd.ms-excel'),
    'xlsm' => array('application/vnd.ms-excel.sheet.macroEnabled.12', 'application/zip', 'application/vnd.ms-excel'),
    'xlsb' => array('application/vnd.ms-excel.sheet.binary.macroEnabled.12', 'application/zip', 'application/vnd.ms-excel'),
    'ppt' => array('application/vnd.ms-powerpoint', 'application/powerpoint', 'application/vnd.ms-office', 'application/msword', 'application/octet-stream'),
    'pot' => array('application/vnd.ms-powerpoint', 'application/powerpoint'),
    'pps' => array('application/vnd.ms-powerpoint', 'application/powerpoint'),
    'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/vnd.ms-powerpoint', 'application/msword', 'application/x-zip'),
    'potx' => array('application/vnd.openxmlformats-officedocument.presentationml.template', 'application/zip', 'application/vnd.ms-powerpoint'),
    'ppsx' => array('application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'application/zip', 'application/vnd.ms-powerpoint'),
    'pptm' => array('application/vnd.ms-powerpoint.presentation.macroEnabled.12', 'application/zip', 'application/vnd.ms-powerpoint'),
    'word' => array('application/msword', 'application/octet-stream'),
    'xl' => 'application/excel',
    'pub' => array('application/x-mspublisher', 'application/octet-stream'),
    'vsd' => array('application/vnd.visio', 'application/octet-stream'),

    /* ---------------------------------------------------------------
     | Documents - Apple iWork
     | --------------------------------------------------------------- */
    'pages' => array('application/vnd.apple.pages', 'application/x-iwork-pages-sffpages', 'application/zip'),
    'numbers' => array('application/vnd.apple.numbers', 'application/x-iwork-numbers-sffnumbers', 'application/zip'),
    'key' => array('application/vnd.apple.keynote', 'application/x-iwork-keynote-sffkey', 'application/zip'),

    /* ---------------------------------------------------------------
     | Text and data
     | --------------------------------------------------------------- */
    'txt' => 'text/plain',
    'text' => 'text/plain',
    'log' => array('text/plain', 'text/x-log'),
    'md' => array('text/markdown', 'text/x-markdown', 'text/plain'),
    'rtf' => array('application/rtf', 'text/rtf'),
    'rtx' => 'text/richtext',
    'csv' => array('text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'application/octet-stream'),
    'tsv' => array('text/tab-separated-values', 'text/plain'),
    'json' => array('application/json', 'text/json', 'text/plain'),
    'xml' => array('application/xml', 'text/xml'),
    'xsl' => array('application/xml', 'text/xml', 'application/xslt+xml'),
    'yaml' => array('application/yaml', 'text/yaml', 'text/plain'),
    'yml' => array('application/yaml', 'text/yaml', 'text/plain'),
    'ics' => array('text/calendar', 'application/octet-stream'),
    'vcf' => array('text/vcard', 'text/x-vcard', 'text/plain'),
    'srt' => array('application/x-subrip', 'text/plain'),
    'vtt' => array('text/vtt', 'text/plain'),
    'css' => array('text/css', 'text/plain'),
    'html' => array('text/html', 'text/plain'),
    'htm' => array('text/html', 'text/plain'),
    'shtml' => array('text/html', 'text/plain'),
    'xhtml' => 'application/xhtml+xml',
    'xht' => 'application/xhtml+xml',
    'js' => array('text/javascript', 'application/javascript', 'application/x-javascript'),
    'mjs' => array('text/javascript', 'application/javascript'),
    'eml' => 'message/rfc822',

    /* ---------------------------------------------------------------
     | Audio
     | --------------------------------------------------------------- */
    'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3', 'audio/x-mpeg', 'application/octet-stream'),
    'mpga' => 'audio/mpeg',
    'mp2' => 'audio/mpeg',
    'm4a' => array('audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/aac', 'application/octet-stream'),
    'aac' => array('audio/aac', 'audio/x-aac', 'audio/aacp', 'application/octet-stream'),
    'ogg' => array('audio/ogg', 'application/ogg', 'video/ogg'),
    'oga' => array('audio/ogg', 'application/ogg'),
    'opus' => array('audio/opus', 'audio/ogg'),
    'weba' => 'audio/webm',
    'flac' => array('audio/flac', 'audio/x-flac', 'application/octet-stream'),
    'wav' => array('audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave', 'application/octet-stream'),
    'wma' => array('audio/x-ms-wma', 'video/x-ms-asf', 'application/octet-stream'),
    'amr' => array('audio/amr', 'audio/3gpp', 'application/octet-stream'),
    'mid' => array('audio/midi', 'audio/x-midi'),
    'midi' => array('audio/midi', 'audio/x-midi'),
    'aif' => array('audio/x-aiff', 'audio/aiff', 'audio/x-au'),
    'aiff' => array('audio/x-aiff', 'audio/aiff', 'audio/x-au'),
    'aifc' => 'audio/x-aiff',
    'au' => array('audio/basic', 'audio/x-au'),
    'snd' => 'audio/basic',
    'caf' => array('audio/x-caf', 'application/octet-stream'),
    '3ga' => array('audio/3gpp', 'application/octet-stream'),
    'ra' => 'audio/x-realaudio',
    'ram' => 'audio/x-pn-realaudio',
    'rm' => 'audio/x-pn-realaudio',
    'rpm' => 'audio/x-pn-realaudio-plugin',

    /* ---------------------------------------------------------------
     | Video
     | --------------------------------------------------------------- */
    'mp4' => array('video/mp4', 'video/x-m4v', 'application/octet-stream'),
    'm4v' => array('video/x-m4v', 'video/mp4'),
    'webm' => 'video/webm',
    'mkv' => array('video/x-matroska', 'application/octet-stream'),
    'ogv' => array('video/ogg', 'application/ogg'),
    'mov' => array('video/quicktime', 'application/octet-stream'),
    'qt' => 'video/quicktime',
    'avi' => array('video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo', 'application/octet-stream'),
    'wmv' => array('video/x-ms-wmv', 'video/x-ms-asf', 'application/octet-stream'),
    'flv' => 'video/x-flv',
    'f4v' => array('video/x-f4v', 'video/mp4'),
    '3gp' => array('video/3gpp', 'audio/3gpp', 'application/octet-stream'),
    '3g2' => array('video/3gpp2', 'audio/3gpp2', 'application/octet-stream'),
    'mpeg' => 'video/mpeg',
    'mpg' => 'video/mpeg',
    'mpe' => 'video/mpeg',
    'ts' => array('video/mp2t', 'application/octet-stream'),
    'm2ts' => array('video/mp2t', 'application/octet-stream'),
    'mts' => array('video/mp2t', 'application/octet-stream'),
    'movie' => 'video/x-sgi-movie',
    'rv' => 'video/vnd.rn-realvideo',
    'asf' => array('video/x-ms-asf', 'application/octet-stream'),

    /* ---------------------------------------------------------------
     | Fonts
     | --------------------------------------------------------------- */
    'woff' => array('font/woff', 'application/font-woff', 'application/x-font-woff'),
    'woff2' => array('font/woff2', 'application/font-woff2'),
    'ttf' => array('font/ttf', 'application/x-font-ttf', 'application/font-sfnt'),
    'otf' => array('font/otf', 'application/x-font-otf', 'application/font-sfnt'),
    'eot' => array('application/vnd.ms-fontobject', 'application/octet-stream'),

    /* ---------------------------------------------------------------
     | Other / legacy - retained so existing behaviour does not change
     | --------------------------------------------------------------- */
    'bin' => array('application/macbinary', 'application/mac-binary', 'application/octet-stream', 'application/x-binary', 'application/x-macbinary'),
    'dms' => 'application/octet-stream',
    'lha' => 'application/octet-stream',
    'lzh' => 'application/octet-stream',
    'exe' => array('application/octet-stream', 'application/x-msdownload', 'application/vnd.microsoft.portable-executable'),
    'class' => 'application/octet-stream',
    'so' => 'application/octet-stream',
    'dll' => 'application/octet-stream',
    'oda' => 'application/oda',
    'smi' => 'application/smil',
    'smil' => 'application/smil',
    'mif' => 'application/vnd.mif',
    'wbxml' => 'application/wbxml',
    'wmlc' => 'application/wmlc',
    'dcr' => 'application/x-director',
    'dir' => 'application/x-director',
    'dxr' => 'application/x-director',
    'dvi' => 'application/x-dvi',
    'php' => array('application/x-httpd-php', 'text/x-php', 'text/plain'),
    'php3' => 'application/x-httpd-php',
    'php4' => 'application/x-httpd-php',
    'phtml' => 'application/x-httpd-php',
    'phps' => 'application/x-httpd-php-source',
    'swf' => 'application/x-shockwave-flash',
    'sql' => array('application/sql', 'text/plain', 'application/octet-stream'),
    'apk' => array('application/vnd.android.package-archive', 'application/octet-stream'),
    'dmg' => array('application/x-apple-diskimage', 'application/octet-stream'),
    'iso' => array('application/x-iso9660-image', 'application/octet-stream'),

);


/* End of file mimes.php */
/* Location: ./application/config/mimes.php */
