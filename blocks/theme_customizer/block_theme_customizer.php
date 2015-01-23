<?php


class block_theme_customizer extends block_base {

    /**
     * initialize the plugin
     */
    function init() {
        $this->title = get_string('blocktitle', 'block_theme_customizer');
    }


    /**
     * @see block_base::applicable_formats()
     */
    function applicable_formats() {
        return array('site-index' => true, 'my-index' => true);
    }


    /**
     * no need to have multiple blocks to perform the same functionality
     */
    function instance_allow_multiple() {
        return false;
    }


    /**
     * @see block_base::get_content()
     */
    function get_content() {
        global $CFG, $PAGE, $USER, $COURSE, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        // display admin or user page depending capability
        $sitecontext = context_system::instance();

        if (has_capability('block/theme_customizer:manage', $sitecontext)) {
            $this->content->text = '<a href="'.$CFG->wwwroot.'/blocks/theme_customizer/admin.php">Manage themes</a>';
        }
        else {
            $this->content->text = '<a href="'.$CFG->wwwroot.'/blocks/theme_customizer/user.php">Manage themes</a>';
        }

        return $this->content;
    }


    /**
     * this block has global config
     * @see block_base::has_config()
     */
    function has_config() {
        return true;
    }
}
