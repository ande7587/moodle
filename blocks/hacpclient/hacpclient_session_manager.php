<?php

define('HACP_LESSON_STATUS_PASSED'      , 'passed');
define('HACP_LESSON_STATUS_COMPLETED'   , 'completed');
define('HACP_LESSON_STATUS_FAILED'      , 'failed');
define('HACP_LESSON_STATUS_INCOMPLETE'  , 'incomplete');
define('HACP_LESSON_STATUS_BROWSED'     , 'browsed');
define('HACP_LESSON_STATUS_NOTATTEMPTED', 'notattempted');

class hacpclient_session_manager {

    private $hacp_adapter;

    public function __construct($hacp_adapter) {
        $this->hacp_adapter = $hacp_adapter;
    }

    public function getparam($aicc_url, $sessionid, $aupassword) {

        try {
            $response = $this->hacp_adapter->getparam($aicc_url, $sessionid, $aupassword);
            debugging('In getparam: '.print_r($response, true), DEBUG_DEVELOPER);
        } catch (Exception $ex) {
            error_log("Rethrowing exception associated with getparam for $aicc_url session id $sessionid.");
            throw $ex;
        }
        return $response;
    }

    /**
     * Inserts or updates local session record.
     */
    public function create_or_update_session($hacpclientid,
                                             $userid,
                                             $aicc_url,
                                             $aicc_sid,
                                             $getparamresponse)
    {
        global $DB;

        $lesson_status = $getparamresponse['core']['lesson_status'];
        $lesson_status = $this->parse_lesson_status($lesson_status);

        $hacpsession = array('hacpclientid'=>$hacpclientid, 'userid'=>$userid, 'aicc_url'=>$aicc_url);

        if ($existinghacpsession = $DB->get_record('block_hacpclient_sessions', $hacpsession)) {
            if ($existinghacpsession->aicc_sid !== $aicc_sid
                or $existinghacpsession->lesson_status != $lesson_status)
            {
                $existinghacpsession->aicc_sid = $aicc_sid;
                $existinghacpsession->lesson_status = $lesson_status;
                $existinghacpsession->getparamtime = time();
                $existinghacpsession->errorcode = HACPCLIENT_ERROR_NONE;
                $DB->update_record('block_hacpclient_sessions', $existinghacpsession);
            }
        } else {
            $hacpsession['aicc_sid'] = $aicc_sid;
            $hacpsession['lesson_status'] = $lesson_status;
            $hacpsession['getparamtime'] = time();
            // Intentionally resetting errorcode on successful getparam.
            $hacpsession['errorcode'] = HACPCLIENT_ERROR_NONE;
            $hacpsessionid = $DB->insert_record('block_hacpclient_sessions', $hacpsession);
        }
    }

    public function convert_status_to_display_string($status) {
        switch ($status) {
            case HACP_LESSON_STATUS_PASSED:
                return get_string('statuspassed'      , 'block_hacpclient');
            case HACP_LESSON_STATUS_COMPLETED:
                return get_string('statuscompleted'   , 'block_hacpclient');
            case HACP_LESSON_STATUS_FAILED:
                return get_string('statusfailed'      , 'block_hacpclient');
            case HACP_LESSON_STATUS_INCOMPLETE:
                return get_string('statusincomplete'  , 'block_hacpclient');
            case HACP_LESSON_STATUS_BROWSED:
                return get_string('statusbrowsed'     , 'block_hacpclient');
            case HACP_LESSON_STATUS_NOTATTEMPTED:
                return get_string('statusnotattempted', 'block_hacpclient');
            default:
                return get_string('statusinvalid'     , 'block_hacpclient');
        }
    }

    private function parse_lesson_status($lessonstatus) {
        // According to the spec, only the first character (case-insensitive)
        // is significant.

        $statuschar = strtolower($lessonstatus[0]);

        switch ($statuschar) {
            case 'p':
                return HACP_LESSON_STATUS_PASSED;
            case 'c':
                return HACP_LESSON_STATUS_COMPLETED;
            case 'f':
                return HACP_LESSON_STATUS_FAILED;
            case 'i':
                return HACP_LESSON_STATUS_INCOMPLETE;
            case 'b':
                return HACP_LESSON_STATUS_BROWSED;
            case 'n':
                return HACP_LESSON_STATUS_NOTATTEMPTED;
            default:
                throw new Exception('Unexpected lesson status: '.$lessonstatus);
        }
    }

    private function set_sent_lesson_status($hacpsession, $status) {
        global $DB;

        $hacpsession->lesson_status = $status;
        $hacpsession->putparamtime = time();
        $hacpsession->errorcode = HACPCLIENT_ERROR_NONE;
        $DB->update_record('block_hacpclient_sessions', $hacpsession);
    }

    private function set_errorcode($hacpsession, $errorcode) {
        global $DB;

        $hacpsession->errorcode = $errorcode;
        $DB->update_record('block_hacpclient_sessions', $hacpsession);
    }

    /**
     *
     */
    public function send_complete_no_throw($hacpsession, $score, $aupassword) {
        try {
            $this->send_complete($hacpsession, $score, $aupassword);
        } catch (hacpclient_exception $ex) {
            hacpclient_log_exception($ex,
                "Error marking lesson complete in send_complete for session record with id $hacpsession->id");
        }
    }

    public function send_complete($hacpsession, $score, $aupassword) {

        $aiccdata = $this->get_aiccdata_for_completion($score);

        $completionstatus = $this->get_completion_status();

        try {
            $this->hacp_adapter->putparam($hacpsession->aicc_url,
                                          $hacpsession->aicc_sid,
                                          $aiccdata,
                                          $aupassword);

            $this->set_sent_lesson_status($hacpsession, $completionstatus);
        } catch (hacpclient_exception $ex) {

            $errorcode = HACPCLIENT_ERROR_ON_COMPLETE_MSG;
            $this->set_errorcode($hacpsession, $errorcode);
            
            // rethrow
            throw $ex;
        }
    }

    static public function get_completed_statuses() {
        // TODO: Make this configurable? What about 'failed'?
        return array('passed', 'completed');
    }

    // Returns something like "'passed','completed'" for use in SQL IN clause.
    static public function get_completed_statuses_sql() {
        return implode(',',
                       array_map(function ($s) {return "'$s'";} ,
                                 self::get_completed_statuses()));
    }

    // This is the lesson_status value to send in the putparam on completion.
    static private function get_completion_status() {
        // TODO: Make configurable? Will some want to pass "completed"?
        return "passed";
    }

    private function get_aiccdata_for_completion($score) {

        # TODO: See also cmi001v4.pdf 6.6.2.
        #                cmi001v3-5.pdf 5.2.
        #                Cornerstone's AICC Integration Requirements.pdf
        #       Might need more data elements.

        $completionstatus = $this->get_completion_status();

        // Just making up time for now. Might not need, but is listed in AICC spec
        // as required.
        $aiccdata =<<<DATA
[CORE]
Lesson_Location=end
Lesson_Status=$completionstatus
score=$score
time=00:59:59
DATA;

        // Ensure that lines end with CRLF before returning. Using PCRE lookbehind.
        return preg_replace('/(?<!\r)\n/', "\r\n", $aiccdata);
    }

}

