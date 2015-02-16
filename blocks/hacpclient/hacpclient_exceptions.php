<?php

function hacpclient_log_exception($ex, $message='') {

    // See default_exception_handler in lib/setuplib.php.
    $info = get_exception_info($ex);

    $message = empty($message) ? '' : "$message; ";

    $backtrace = format_backtrace($info->backtrace);

    error_log("$message$info->message; $backtrace; $info->debuginfo");
}

class hacpclient_exception extends Exception {}


