<?php defined('BASEPATH') OR exit('No direct script access allowed');
echo 'An uncaught Exception was encountered', PHP_EOL, 'Type: ', get_class($exception), PHP_EOL, 'Message: ', $message, PHP_EOL, 'Filename: ', $exception->getFile(), PHP_EOL, 'Line Number: ', $exception->getLine(), PHP_EOL;
