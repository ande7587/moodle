<?php

define('HACP_VERSION', '4.0');

class hacpclient_hacp_adapter {

    public function send_request($aicc_url, $postdata) {

        // If we don't specify the '&' separator for http_build_query,
        // it uses '&amp;'. We don't want that.
        $postdatastring = http_build_query($postdata, '', '&');

        $ch = curl_init($aicc_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdatastring);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // For debugging; note that we are sending stderr to another file for now.
        #curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, fopen('/tmp/curlopt.out', 'a'));

        $response = curl_exec($ch);

        if (!$response) {
            throw new hacpclient_exception(curl_error($ch));
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != '200') {
            error_log('HTTP status code on HACP call: '.$http_code);
        }

        curl_close($ch);

        $parsed = $this->parse_response($response);

        return $parsed;
    }

    public function getparam($aicc_url, $session_id, $password=null) {
        $postdata = array('command'     => 'getparam',
                          'version'     => HACP_VERSION,
                          'session_id'  => $session_id);

        if ($password !== null) {
            $postdata['AU_password'] = $password;
        }
        $inimap = $this->send_request($aicc_url, $postdata);
        if (empty($inimap['core']['student_id'])) {
            throw new hacpclient_exception("Invalid session $session_id; no student_id found.");
        }
        return $inimap;
    }

    public function putparam($aicc_url, $session_id, $aicc_data, $password=null) {
        $postdata = array('command'     => 'putparam',
                          'version'     => HACP_VERSION,
                          'session_id'  => $session_id);

        if ($password !== null) {
            $postdata['AU_password'] = $password;
        }

        $postdata['AICC_Data'] = $aicc_data;

        return $this->send_request($aicc_url, $postdata);
    }

    # TODO: Need unit test for this.
    private function parse_response($response) {
        // See cmi001v4.pdf 6.4.3 HACP Response Message Format.

        // The PREG_SPLIT_NO_EMPTY removes all blank lines. Assuming ok for now.
        $lines = preg_split('/\v+/', $response, -1, PREG_SPLIT_NO_EMPTY);

        if (preg_match('/^\s*(error)\s*=\s*(\d)\s*$/i', array_shift($lines), $matches)) {
            if ($matches[2] !== '0') {
                throw new hacpclient_exception($response);
            }
        } else {
            // Writing to the error_log outside of the normal exception handling because
            // HTML does not get through some of the cleaning.

            error_log('Invalid HACP response: '.substr($response, 0, 500).'...');
            throw new hacpclient_exception("Invalid HACP response: Details are in the error log.");
        }

        while (! empty($lines)
               && ! preg_match('/^\s*(aicc_data)\s*=\s*(\S.*)?\s*$/i', $lines[0], $matches))
        {
            array_shift($lines);
        }

        // The aicc_data line could contain "[core]", a semicolon comment, or nothing else.

        if (empty($matches[1])) {
            // No aicc_data returned.
            return null;
        }

        if (empty($matches[2])) {
            // The [core] group must start on the next line.]
            array_shift($lines);
        } else {
            // Replace the aicc_data line with just the [core] group.
            $lines[0] = $matches[2];
        }

        $ini = $this->parse_ini_array($lines);

        ###print_r($response);

        return $ini;
    }

    # TODO: Need unit test for this.
    // Converts all group names and name-value keys to lower case.
    private function parse_ini_array($lines) {

        // As we are building each group, we keep a freeform version
        // in case that's what it ends up being.  The specification
        // calls for either free-form or name-value for a given group.

        $group = 'NONE';
        $groups = array();

        foreach ($lines as $line) {

            // Skip over comments.
            if (strlen($line) > 0 and $line[0] === ';') {
                continue;
            }

            if (preg_match('/\s*\[(.+)\]/', $line, $matches)) {
                // Starting a new group.
                $group = strtolower(trim($matches[1]));
                $groups[$group] = array();
                $groups[$group][HACP_INI_FREEFORM_KEY] = '';
            } else {
                // Append every non-comment within the group to the freeform value.
                $groups[$group][HACP_INI_FREEFORM_KEY] .= $line."\r\n";

                if (preg_match('/^\s*([^=\[\]\s]+)\s*=\s*(\S.*)?\s*$/', $line, $nvmatches)) {
                    // Add the name value pair to the array. We drop all keys to lower case.
                    $name = strtolower($nvmatches[1]);
                    $value = trim(array_key_exists(2, $nvmatches) ? $nvmatches[2] : '');
                    $groups[$group][$name] = $value;
                }
            }
        }
        return $groups;
    }
}

