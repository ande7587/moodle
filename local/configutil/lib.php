<?php


function build_config_export_filename($prefix) {
    global $CFG;

    $wwwroot = $CFG->wwwroot;
    if (preg_match('|^https://([\w\./-]+[\w])$|', $wwwroot, $matches)) {
        $instancename = str_replace('/', '_', $matches[1]);
        $timestring = strftime('%y%m%dT%H%M');
        return "{$prefix}_{$instancename}_{$timestring}.txt";
    } else {
        throw new Exception("Invalid wwwroot string: $wwwroot");
    }
}


function configutil_send_export_headers($downloadfilename) {
    global $CFG;

    // Download header setting taken from grade/export/txt/grade_export_txt.php.
    /// Print header to force download
    if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
        @header('Cache-Control: max-age=10');
        @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        @header('Pragma: ');
    } else { //normal http - prevent caching at all cost
        @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        @header('Pragma: no-cache');
    }
    header("Content-Type: application/download\n");

    header("Content-Disposition: attachment; filename=\"$downloadfilename\"");

}
