<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot .'/local/theme_customizer/lib.php');



//============== MAIN ROUTINE =============

$start_stamp = microtime();

// create an instance
$theme_customizer = new local_theme_customizer();

echo "\nStart compiling themes ... ";

$result = $theme_customizer->compile_theme('2', '/home/noname/oit/prj/moodle/git/docs/theme');

echo "\n\nResult: ", $result;

echo "\n\nTime spent: ", microtime_diff($start_stamp, microtime());
echo "\nMemory peak usage: ", memory_get_peak_usage(true), "\n";