<?php

/**
 * Atto text editor integration version file.
 *
 * @package    atto_boxhighlight
 * @author     Joseph Inhofer <jinhofer@umn.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The js files under build were built with the following command line:
// $ PATH=$PATH:~/local/nodejs/node-v0.10.33-linux-x64/bin shifter
// from within lib/editor/atto/plugins/boxhighlight/yui/src/button

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015031900;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2014110400;        // Requires this Moodle version.
$plugin->component = 'atto_boxhighlight';  // Full name of the plugin (used for diagnostics).
