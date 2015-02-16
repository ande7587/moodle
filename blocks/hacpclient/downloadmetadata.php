<?php

// This generates and sends as a response, the metadata for this
// AICC Assignable Unit.

require_once '../../config.php';
require_once('lib.php');

require_login();

$blockinstanceid = required_param('hacpclientid', PARAM_INT);

$blockcontext = context_block::instance($blockinstanceid);

# TODO: Consider whether we should use a different capability.
require_capability('moodle/block:edit', $blockcontext);

$block = hacpclient_get_block($blockinstanceid);
$coursecontext = $block->context->get_course_context();

$course = $DB->get_record('course', array('id'=>$coursecontext->instanceid));

$downloadfilename = preg_replace('/\W/', '', $course->shortname);
$downloadfilename = "meta_$downloadfilename.zip";

hacpclient_send_metadata_download_headers($downloadfilename);

$zip = new ZipArchive();

$tempdir = make_temp_directory('hacpclient');
$tempfilename = tempnam($tempdir, 'meta');

if ($zip->open($tempfilename, ZIPARCHIVE::CREATE) !== true) {
    throw new Exception('error starting zip');
}

$basefilename = 'aicc';

$crs = hacpclient_get_content_for_crs_file($block, $course);
$zip->addFromString($basefilename.'.crs', $crs);

$au = hacpclient_get_content_for_au_file($block);
$zip->addFromString($basefilename.'.au', $au);

$cst = hacpclient_get_content_for_cst_file();
$zip->addFromString($basefilename.'.cst', $cst);

$des = hacpclient_get_content_for_des_file();
$zip->addFromString($basefilename.'.des', $des);

$zip->close();

$filedata = file_get_contents($tempfilename);

echo $filedata;

unlink($tempfilename);

