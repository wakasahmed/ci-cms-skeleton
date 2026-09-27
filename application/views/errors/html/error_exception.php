<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Exception</title>
</head>
<body>
    <h1><?php echo html_escape(get_class($exception)); ?></h1>
    <p><?php echo html_escape($message); ?></p>
</body>
</html>
