<?php

// Build M drop-down menu items
if (!empty($PAGE->theme->settings->custom_values['footersettings'])) {
    $footersettings = json_decode(stripslashes($PAGE->theme->settings->custom_values['footersettings']));
} else if (!empty($PAGE->theme->settings->footersettings)) {
    $footersettings = json_decode($PAGE->theme->settings->footersettings);
} else {
    $footersettings = array();
}

if (!empty($footersettings->privacylink)) {
    $privacytitle = $footersettings->privacylink->title;
    $privacylink = $footersettings->privacylink->href;
} else {
    $privacylink = 'http://www1.umn.edu/twincities/privacy/';
    $privacytitle = 'Privacy';
}

$campustitle = !empty($footersettings->campustitle) ? $footersettings->campustitle : '';

$campuslinks = array();

if (!empty($footersettings->campuslinks)) {
    foreach ($footersettings->campuslinks as $link) {
        $campuslinks[] = $link;
    }
}
?>

<footer id="page-footer">
    <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
    <p class="helplink"><?php echo $OUTPUT->page_doc_link(); ?></p>
    <?php
    echo $html->footnote;
    echo $OUTPUT->login_info();
    echo $OUTPUT->home_link();
    echo $OUTPUT->standard_footer_html();
    ?>
</footer>

<footer id="umn-footer">
    <div id="umn-footer-wrap">
    <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
    <?php if (!empty($campuslinks)) { ?>
    <nav id="umn-footer-links">
        <h2><?php echo $campustitle; ?>:</h2>
        <ul>
            <?php foreach($campuslinks as $link) { ?>
            <li>
                <a target="_blank" href="<?php echo $link->href; ?>"><?php echo $link->title; ?></a>
            </li>
            <?php } ?>
        </ul>
    </nav>
    <?php } ?>
    <ul id="umn-copyright">
        <li>© 2011–2015 Regents of the University of Minnesota. All rights reserved.</li>
        <li>The University of Minnesota is an equal opportunity educator and employer.</li>
        <li>
            <a class="umn-privacy" target="_blank" href="<?php echo $privacylink; ?>"><?php echo $privacytitle; ?></a>
        </li>
    </ul>
    </div>
</footer>
