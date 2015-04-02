<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Send parameters to JS
 * @param $elementid
 * @param $options
 * @param $foptions
 * return Array $params that contains the plugin config settings
 */

function atto_fontfamily_params_for_js($elementid, $options, $fpoptions) {
    $params = array('options' => get_config('atto_fontfamily', 'options'));
    return $params;
}
