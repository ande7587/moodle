<?php

/**
 * Version details
 *
 * @package    gradeexport
 * @subpackage ppsftlink
 * @author     Colin Campbell (University of Minnesota)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2014061000;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012112900;        // Requires this Moodle version
$plugin->component = 'gradeexport_ppsftlink'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
    'local_ppsft' => 2014061000       // Required for configuration PeopleSoft URL configuration.
);
