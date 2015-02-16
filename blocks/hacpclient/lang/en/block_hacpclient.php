<?php

$string['pluginname'] = 'HACP client';
$string['hacpclient:addinstance'] = 'Add a new HACP Client block';

$string['blocktitle'] = 'Block title';
$string['blocktitle_help'] = 'A title entered here will replace the default block title when the block is displayed.';

$string['downloadmetadata'] = 'Download AICC metadata';
$string['showsessions'] = 'Show session information';
$string['sessions'] = 'HACP sessions';

$string['novalidcmipassword'] = 'The LMS starting this session did not provide a valid code.';

$string['showusers'] = 'Show user sessions';

$string['selectrole'] = 'Select role to assign on enrollment';
$string['selectrole_help'] = 'This is the role in the course that users will be assigned if they are enrolled automatically after being sent to this course by the external LMS.';

$string['aupassword'] = 'AU password';
$string['aupassword_help'] = 'This is the password that the Assignable Unit (the Moodle course, in this case) passes to the external LMS to authenticate. (The Cornerstone LMS currently ignores this value.)';
$string['cmipassword'] = 'CMI password';
$string['cmipassword_help'] = 'This is where you can set a password that will be included in the course metadata that is loaded into the external LMS.  When a user accesses Moodle through the LMS the launch data will include this password and allow the user in.  If a user tries to access this Moodle course without being referred by the LMS they will not have this password and Moodle will throw an error.';

$string['nospacesdblquotessquares'] = 'No spaces, double quotes, or square brackets are allowed in this field.';
$string['maxlength'] = 'This field is limited to {$a} characters';

$string['completetrigger'] = 'Trigger for HACP AU complete message';
$string['completetrigger_help'] = 'Selecting "On completion" as the trigger will result in Moodle\'s sending a completion message to the external LMS when the user completes the course as determined by the Moodle course completions feature.  In this case, the block must be added to the main page of the course.

Selecting "On page view" will result in Moodle\'s sending the completion message to the external LMS when the user accesses the page to which the block has been added.  Since viewing the page triggers the completion message, the block probably does not belong on the main page of the course, in this case.';
$string['completetriggernone'] = 'None';
$string['completetriggercompletion'] = 'On completion';
$string['completetriggerview'] = 'On page view';

$string['status'] = 'Status';

$string['statuspassed'] = 'Passed';
$string['statuscompleted'] = 'Completed';
$string['statusfailed'] = 'Failed';
$string['statusincomplete'] = 'Incomplete';
$string['statusbrowsed'] = 'Browsed';
$string['statusnotattempted'] = 'Not attempted';
$string['statusnosession'] = 'No session';
$string['statusmultiplesessions'] = 'Multiple sessions';
$string['statusinvalid'] = 'Invalid';
$string['statuserror'] = 'Error';
$string['statusnotcompleted'] = 'Session started {$a}';

$string['sumuserstatusheader'] = 'User status';
$string['sumusercountheader'] = 'User count';

$string['overallsessions'] = 'Overall session counts';
$string['courseenrollees'] = 'Course enrollees';
$string['enrolleeswsession'] = 'Enrollees with sessions';
$string['enrolleeswosession'] = 'Enrollees without sessions';
$string['nonenrolleeswsession'] = 'Non-enrollees with sessions';

$string['sessionsbyaiccurl'] = 'Session counts by AICC URL';
$string['aiccurlheader'] = 'AICC CMI URL';
$string['sessioncountheader'] = 'Session count';
$string['completedheader'] = 'Completed sessions';
$string['oldincompleteheader'] = 'Old sessions';
$string['errorsheader'] = 'Errors';
$string['maxgetparamtimeheader'] = 'Last access time';
$string['selectheader'] = 'Select';

$string['selectactionlabel'] = 'Select action to perform on selected URL\'s sessions...';
$string['deleteoldsessions'] = 'Delete old sessions';
$string['deleteerrors'] = 'Delete sessions with errors';
$string['retryerrors'] = 'Retry errors';
$string['executeurlaction'] = 'Execute action';

$string['deletenonenrollees'] = 'Delete sessions for users that are not enrolled';

$string['usersessionsall'] = 'User sessions for AICC URL {$a}';
$string['usersessionserrors'] = 'User sessions in error state for AICC URL {$a}';
$string['usersessionsold'] = 'Old user sessions or AICC URL {$a}';

$string['userheader'] = 'User (Moodle username)';
$string['lessonstatusheader'] = 'Lesson status';
$string['getparamtimeheader'] = 'Last getparam';
$string['putparamtimeheader'] = 'Last putparam';
$string['errorheader'] = 'Error?';
$string['aiccsidheader'] = 'AICC SID';

$string['oldincompletemessage'] = '"Old" sessions are those last accessed before {$a} and not completed.';

$string['getparamtesttitle'] = 'getparam';
$string['getparamtestheading'] = 'getparam test result';

$string['enrolledusers'] = 'Enrolled users';
$string['enrolledusersmatching'] = 'Enrolled users matching "{$a}"';
$string['showsessionsforuser'] = 'Show sessions for selected user';

$string['errorretrybutton'] = 'Yes, retry';
$string['errorretryfailed'] = 'Yes, retry failed';

$string['searchforuser'] = 'Search for specific user';

