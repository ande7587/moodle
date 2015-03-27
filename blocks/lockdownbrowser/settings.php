<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2014 Respondus, Inc.  All Rights Reserved.
// Date: November 25, 2014.

if (!isset($CFG)) {
    require_once("../../config.php");
}

require_once("$CFG->dirroot/blocks/lockdownbrowser/locklib.php");

// existence check necessary due to Moodle handling of this file
if (!function_exists("lockdownbrowser_getsettingsstring")) {
    function lockdownbrowser_getsettingsstring($identifier) {
        global $CFG;
        $component = "block_lockdownbrowser";
        if (isset($CFG) && $CFG->version >= 2012062500) {
            // Moodle 2.3.0+.
            return new lang_string($identifier, $component);
        } else {
            // Prior to Moodle 2.3.0.
            return get_string($identifier, $component);
        }
    }
}

$settings->add(
    new admin_setting_heading(
        "lockdown_blockdescheader",
        lockdownbrowser_getsettingsstring("blockdescheader"),
        lockdownbrowser_getsettingsstring("blockdescription")
    )
);

$lockdownbrowser_version_file = "$CFG->dirroot/blocks/lockdownbrowser/version.php";
$lockdownbrowser_version      = "(error: version not found)";
if (is_readable($lockdownbrowser_version_file)) {
    $lockdownbrowser_contents = file_get_contents($lockdownbrowser_version_file);
    if ($lockdownbrowser_contents !== false) {
        $lockdownbrowser_parts = explode("=", $lockdownbrowser_contents);
        if (count($lockdownbrowser_parts) > 0) {
            $lockdownbrowser_parts   = explode(";", $lockdownbrowser_parts[1]);
            $lockdownbrowser_version = trim($lockdownbrowser_parts[0]);
        }
    }
}
$settings->add(
    new admin_setting_heading(
        "lockdown_blockversionheader",
        lockdownbrowser_getsettingsstring("blockversionheader"),
        $lockdownbrowser_version //. " (internal release)"
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_adminsettingsheader",
        lockdownbrowser_getsettingsstring("adminsettingsheader"),
        lockdownbrowser_getsettingsstring("adminsettingsheaderinfo")
    )
);

$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servername",
        lockdownbrowser_getsettingsstring("servername"),
        lockdownbrowser_getsettingsstring("servernameinfo"),
        $CFG->block_lockdownbrowser_ldb_servername,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serverid",
        lockdownbrowser_getsettingsstring("serverid"),
        lockdownbrowser_getsettingsstring("serveridinfo"),
        $CFG->block_lockdownbrowser_ldb_serverid,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_serversecret",
        lockdownbrowser_getsettingsstring("serversecret"),
        lockdownbrowser_getsettingsstring("serversecretinfo"),
        $CFG->block_lockdownbrowser_ldb_serversecret,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_servertype",
        lockdownbrowser_getsettingsstring("servertype"),
        lockdownbrowser_getsettingsstring("servertypeinfo"),
        $CFG->block_lockdownbrowser_ldb_servertype,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_ldb_download",
        lockdownbrowser_getsettingsstring("downloadurl"),
        lockdownbrowser_getsettingsstring("downloadinfo"),
        $CFG->block_lockdownbrowser_ldb_download,
        PARAM_TEXT
    )
);

$settings->add(
    new admin_setting_heading(
        "lockdown_authenticationsettingsheader",
        lockdownbrowser_getsettingsstring("authenticationsettingsheader", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("authenticationsettingsheaderinfo", "block_lockdownbrowser")
    )
);
$settings->add(
    new admin_setting_configtext(
        "block_lockdownbrowser_monitor_username",
        lockdownbrowser_getsettingsstring("username", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("usernameinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_username,
        PARAM_TEXT
    )
);
$settings->add(
    new admin_setting_configpasswordunmask(
        "block_lockdownbrowser_monitor_password",
        lockdownbrowser_getsettingsstring("password", "block_lockdownbrowser"),
        lockdownbrowser_getsettingsstring("passwordinfo", "block_lockdownbrowser"),
        $CFG->block_lockdownbrowser_monitor_password,
        PARAM_TEXT
    )
);

// status string
$ist = "";
if (!isset($_COOKIE[$CFG->block_lockdownbrowser_ldb_session_cookie . $CFG->sessioncookie])) {
    $ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $ist .= "Warning: Moodle session cookie check failed.</div>";
}
if (!isset($CFG->customscripts)) {
    $ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $ist .= "Warning: " . '$CFG->customscripts' . " is not set.</div>";
} else if (!file_exists("$CFG->customscripts/mod/quiz/attempt.php")
  || !file_exists("$CFG->customscripts/mod/quiz/view.php")
  || !file_exists("$CFG->customscripts/mod/quiz/review.php")
) {
    $ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
    $ist .= "Warning: " . '$CFG->customscripts' . " is set ($CFG->customscripts), ";
    $ist .= "but the lockdownbrowser scripts were not found.</div>";
}
if (!during_initial_install() && empty($CFG->upgraderunning)) {
    $old_mod_installed = $DB->get_manager()->table_exists(new xmldb_table("lockdown_settings"));
    if (!$old_mod_installed) {
        clearstatcache();
        $old_mod_folder = "$CFG->dirroot/mod/lockdown";
        $old_mod_installed = (file_exists($old_mod_folder) && is_dir($old_mod_folder));
    }
    if ($old_mod_installed) {
        $ist .= "<div style='font-size: 125%; color:red; text-align: center; padding: 30px'>";
        $ist .= "Error: /mod/lockdown module has not been uninstalled. Please see the ";
        $ist .= "Administrator Guide for LockDown Browser - Moodle.</div>";
    }
}
$ist .= "<div style='text-align: center'>" . lockdownbrowser_getsettingsstring('tokens_free') . ": ";
if (!during_initial_install() && empty($CFG->upgraderunning)) {
    $dbman = $DB->get_manager();
    $toke_ok = $dbman->table_exists(new xmldb_table("block_lockdownbrowser_toke"));
    $sess_ok = $dbman->table_exists(new xmldb_table("block_lockdownbrowser_sess"));
    if ($toke_ok && $sess_ok) {
        $tf = lockdownbrowser_tokens_free();
    }
}
if (isset($tf) && ($tf > 0)) {
    $ist .= "$tf";
} else {
    $ist .= "0 (is mcrypt enabled?)";
}
$ist .= "<br>" . lockdownbrowser_getsettingsstring('test_server')
  . ": <a href='$CFG->wwwroot/blocks/lockdownbrowser/tokentest.php' target='_blank'>"
  . "/blocks/lockdownbrowser/tokentest.php</a>";
$ist .= "</div>";

$settings->add(
    new admin_setting_heading(
        "lockdown_adminstatus",
        lockdownbrowser_getsettingsstring("adminstatus"),
        $ist
    )
);

