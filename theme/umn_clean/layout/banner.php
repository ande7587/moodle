<?php
// Build Course drop-down menu items
$coursemenuitems = array();
if (!empty($PAGE->theme->settings->coursemenulimit)) {
    $sortorder = 'visible DESC';
    // Prevent undefined $CFG->navsortmycoursessort errors.
    if (empty($CFG->navsortmycoursessort)) {
        $CFG->navsortmycoursessort = 'sortorder';
    }
    // Append the chosen sortorder.
    $sortorder = $sortorder . ',' . $CFG->navsortmycoursessort . ' ASC';
    $courses = enrol_get_my_courses(null, $sortorder);
    foreach ($courses as $course) {
        $coursecontext = context_course::instance($course->id);
        if ($course->id != $SITE->id && !$course->visible) {
            if (is_role_switched($course->id)) {
                // user has to be able to access course in order to switch, let's skip the visibility test here
            } else if (!has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                continue;
            }
        }
        $url = new moodle_url('/course/view.php', array('id'=>$course->id));
        $settings = new stdClass();
        $settings->id = $course->id;
        $settings->fullname = format_string($course->fullname, true, array('context' => $coursecontext));
        $settings->shortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $settings->visible = $course->visible;
        $settings->url = $url->out();

        $coursemenuitems[] = $settings;
    }
}

// Build M drop-down menu items
if (!empty($PAGE->theme->settings->custom_values['mmenuitems'])) {
    $mmenuitems = json_decode(stripslashes($PAGE->theme->settings->custom_values['mmenuitems']));
} else if (!empty($PAGE->theme->settings->mmenuitems)) {
    $mmenuitems = json_decode($PAGE->theme->settings->mmenuitems);
} else {
    $mmenuitems = array();
}

// Build Help drop-down menu items
if (!empty($PAGE->theme->settings->custom_values['helpmenuitems'])) {
    $helpmenuitems = json_decode(stripslashes($PAGE->theme->settings->custom_values['helpmenuitems']));
} else if (!empty($PAGE->theme->settings->helpmenuitems) ) {
    $helpmenuitems = json_decode($PAGE->theme->settings->helpmenuitems);
} else {
    $helpmenuitems = array();
}

// Bring in Logo title and link
if (!empty($PAGE->theme->settings->custom_values['logolink'])) {
    $logolink = $PAGE->theme->settings->custom_values['logolink'];
} else if (!empty($PAGE->theme->settings->logolink)) {
    $logolink = $PAGE->theme->settings->logolink;
} else {
    $logolink = '';
}
if (!empty($PAGE->theme->settings->custom_values['logotitle'])) {
    $logotitle = $PAGE->theme->settings->custom_values['logotitle'];
} else if (!empty($PAGE->theme->settings->logotitle)) {
    $logotitle = $PAGE->theme->settings->logotitle;
} else {
    $logotitle = '';
}

?>

<header role="banner" class="moodle-has-zindex">
        <div id="header-banner">
            <div id="header-img-wrap">
            <div id="header-img">
                <div id="my-courses" title="<?php echo get_string('my-courses-button','theme_umn_clean'); ?>">
                </div>
                <?php if(!empty($mmenuitems)) { ?>
                <div id="m-links" title="<?php echo get_string('m-links-button','theme_umn_clean'); ?>">
                </div>
                <?php } ?>
                <div id="logo-umn" title="<?php echo $logotitle; ?>">
                    <a href="<?php echo $logolink; ?>"></a>
                </div>
                <?php echo $OUTPUT->user_menu(); ?>
                <?php if (!empty($helpmenuitems)) { ?>
                <div id="help-panel-logo" title="<?php echo get_string('help-panel-logo','theme_umn_clean'); ?>">
                <?php } ?>
                </div>
            </div>
            </div>

            <?php if (!empty($helpmenuitems)) { ?>
            <div id="help-panel" class="dropdown-panel">
                <div class="arrow-up"></div>
                <div class="panel-content">
                    <ul>
                    <?php foreach ($helpmenuitems as $item) {
                        if (!empty($item->langstringid)) {
                            $title = get_string($item->langstringid, 'theme_umn_clean');
                        } else {
                            $title = empty($item->title) ? 'undefined' : $item->title;
                        }
                        $href = empty($item->href) ? '/#' : $item->href;
                        $id = empty($item->id) ? '' : $item->id;
                        $class = empty($item->class) ? '' : $item->class;
                        $target = empty($item->target) ? '' : $item->target;
                        echo "<li><a href=\"$href\" id=\"$id\" class=\"$class\" target=\"$target\">";
                        if (isset($item->icon)) {
                            echo "<i class=\"fa fa-$item->icon\"></i>";
                        }
                        if (isset($item->langstring)) {
                            echo get_string($item->langstring, 'theme_umn_clean');
                        } else {
                            echo isset($item->title) ? $item->title : 'undefined';
                        }
                        echo '</a></li>';
                     } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>

                <div id="course-panel" class="dropdown-panel">
                    <div class="arrow-up"></div>
                    <div class="panel-content">
                        <ul>
                            <?php foreach ($coursemenuitems as $item) {
                                $title = $item->fullname;
                                $href = $item->url;
                                $id = 'dyanmic_user-course_'.$course->id;
                                $hidden = $item->visible ? '' : 'hidden';
                                echo "<li class=\"$hidden\"><a href=\"$href\" id=\"$id\">$title</a></li>";
                            } ?>
                            <li>
                                <a href="<?php echo $CFG->wwwroot.'/my/'; ?>" id="all-user-courses"><?php echo get_string('all-courses', 'theme_umn_clean'); ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php if (!empty($mmenuitems)) { ?>
                <div id="m-links-panel" class="dropdown-panel">
                    <div class="arrow-up"></div>
                    <div class="panel-content">
                        <ul>
                        <?php foreach ($mmenuitems as $item) {
                            if (!empty($item->langstringid)) {
                                $title = get_string($item->langstringid, 'theme_umn_clean');
                            } else {
                                $title = empty($item->title) ? 'undefined' : $item->title;
                            }
                            $href = empty($item->href) ? '/#' : $item->href;
                            $id = empty($item->id) ? '' : $item->id;
                            $class = empty($item->class) ? '' : $item->class;
                            $target = empty($item->target) ? '' : $item->target;
                            echo "<li><a href=\"$href\" id=\"$id\" class=\"$class\" target=\"$target\">"; // id=\"help-link-getting-help\">";
                            if (isset($item->icon)) {
                                echo "<i class=\"fa fa-$item->icon\"></i>";
                            }
                            if (isset($item->langstring)) {
                                echo get_string($item->langstring, 'theme_umn_clean');
                            } else {
                                echo isset($item->title) ? $item->title : 'undefined';
                            }
                            echo '</a></li>';
                        } ?>
                        </ul>
                    </div>
                </div>
                <?php } ?>
                <div id="lower-user-menu">
                    <?php echo $OUTPUT->user_menu(); ?>
                </div>
            </div>
            <div id="header-heading">
                <div id="header-heading-pic-left"><?php echo $html->heading; ?></div>
                <div id="header-heading-pic-right"></div>
            </div>
</header>

