<?php

/**
 * Unit tests for block theme_customizer lib
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package block
 * @subpackage theme_customizer
 * @copyright University of Minnesota 2012
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/blocks/theme_customizer/lib.php');
require_once($CFG->dirroot . '/local/testlib/UnitTestCaseUsingDBDump.php');
require_once($CFG->dirroot . '/local/testlib/Webservice_util.php');


class theme_customizer_lib_test extends UnitTestCase {


    function setUp() {
        $this->load_mysql_dump('moodle2_7', array('use_myisam' => true));
    }

    function tearDown() {
    }


    /**
     * test clear_theme_cache
     */
    function test_clear_theme_cache() {
        $theme_id = 2;

        // turn off designer mode to force the cache on
        set_config('themedesignermode', 0);

        $theme_lib = new theme_customizer();
        $theme_lib->compile_theme($theme_id);


        //===== CASE 1: existing theme cache, theme ID only =====
        // Output: theme cache deleted
        // verify that the cache is created

        // CASE 2: non-existing theme cache
        // Output: nothing

        // CASE 3: passing loaded theme structure
        // Output: theme cache deleted

        // CASE 4: invalid theme ID
        // Output: exception

    }
}