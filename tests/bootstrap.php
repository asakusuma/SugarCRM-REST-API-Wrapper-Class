<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../src'));

// Ensure src/ is on include_path
set_include_path(
    implode(
        PATH_SEPARATOR,
        array(
            realpath(dirname(__FILE__) . '/../src'),
            realpath(dirname(__FILE__) . '/../tests'),
            get_include_path(),
        )
    )
);

require_once __DIR__ . '/../vendor/autoload.php';
