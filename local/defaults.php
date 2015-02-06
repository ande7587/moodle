<?php
// 20150206 Defaults provided for prod 2.8 by Drew.

$defaults['assign']['alwaysshowdescription'] = '0';
$defaults['assign']['sendlatenotifications'] = '1';
$defaults['assign']['sendstudentnotifications'] = '0';
$defaults['assignsubmission_file']['maxbytes'] = '20971520';
$defaults['block_course_overview']['forcedefaultmaxcourses'] = '0';
$defaults['block_course_overview']['showchildren'] = '0';
$defaults['block_course_overview']['showwelcomearea'] = '0';
$defaults['block_section_links']['numsections1'] = '30';
$defaults['block_section_links']['numsections2'] = '50';
$defaults['block_theme_customizer']['parents'] = 'umn_clean, bootstrapbase';
$defaults['book']['requiremodintro'] = '0';
$defaults['editor_tinymce']['customconfig'] = ' "valid_children":"+body[style]",
 "table_styles":"All border=all_border;No border=no_border;Outer border only=outer_border_only",
 "valid_elements":"script[src|type],*[*],#div[*]"}';
$defaults['editor_tinymce']['customtoolbar'] = 'wrap,formatselect,wrap,bold,italic,wrap,bullist,numlist,wrap,link,unlink,wrap,image

undo,redo,wrap,underline,strikethrough,sub,sup,wrap,justifyleft,justifycenter,justifyright,wrap,outdent,indent,wrap,forecolor,backcolor,wrap,ltr,rtl

fontselect,fontsizeselect,wrap,code,search,replace,wrap,nonbreaking,charmap,table,wrap,cleanup,removeformat,pastetext,pasteword,wrap,fullscreen';
$defaults['enrol_guest']['defaultenrol'] = '0';

$defaults['enrol_manual']['expirynotify'] = '2';
$defaults['enrol_self']['newenrols'] = '0';
$defaults['enrol_umnauto']['terms'] = array (
  'enabled' =>
  array (
    0 => '1163',
    1 => '1159',
    2 => '1155',
    3 => '1153',
    4 => '1149',
  ),
  'default' => '1155',
);
$defaults['filter_tex']['latexpreamble'] = '\\usepackage[latin1]{inputenc}
\\usepackage{amsmath}
\\usepackage{amsfonts}
\\RequirePackage{amsmath,amssymb,latexsym}
';
$defaults['folder']['requiremodintro'] = '0';
$defaults['imscp']['requiremodintro'] = '0';
$defaults['lesson']['flowviewer_feedback_link'] = 'https://ay14.moodle.umn.edu/mod/forum/discuss.php?d=36378';
$defaults['lesson']['flowviewer_help_link'] = 'http://it.umn.edu/moodle-24-26-lesson-flowviewer';
$defaults['lesson']['requiremodintro'] = '0';
$defaults['library_resources']['link_cr_cat'] = 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?mode=Basic&vid=CROOKSTON&tab=default_tab';
$defaults['library_resources']['link_dl_cat'] = 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?mode=Basic&vid=DULUTH&tab=default_tab';
$defaults['library_resources']['link_librarian_chat_tc'] = 'http://www.questionpoint.org/crs/servlet/org.oclc.admin.BuildForm?&page=frame&institution=12947&type=2&language=1';
$defaults['library_resources']['link_mr_cat'] = 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?mode=Basic&vid=MORRIS&tab=default_tab';
$defaults['library_resources']['link_tc_cat'] = 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?mode=Basic&vid=TWINCITIES&tab=default_tab';
$defaults['local_ppsft']['gradelinkaccessurl'] = 'https://cs.tst.psoft.umn.edu/psp/cstst/EMPLOYEE/CAMP/s/WEBLIB_UM_MOODL.ISCRIPT1.FieldFormula.IScript_AuthCheck';
$defaults['local_ppsft']['gradelinkgradeposturl'] = 'https://cs.tst.psoft.umn.edu/psc/cstst/EMPLOYEE/CAMP/s/WEBLIB_UM_MOODL.ISCRIPT1.FieldFormula.IScript_FinalGrades';
$defaults['logstore_database']['includeactions'] = array (
  'c' => 1,
  'r' => 1,
  'u' => 1,
  'd' => 1,
);
$defaults['logstore_database']['includelevels'] = array (
  1 => 1,
  2 => 1,
  0 => 1,
);
$defaults['moodle']['allowblockstodock'] = '0';
$defaults['moodle']['allowcoursethemes'] = '1';
$defaults['moodle']['allowguestmymoodle'] = '0';
$defaults['moodle']['alternateloginurl'] = '/local/login/shibpassive.php';
$defaults['moodle']['autolang'] = '0';
$defaults['moodle']['badges_defaultissuercontact'] = 'moodle@umn.edu';
$defaults['moodle']['badges_defaultissuername'] = 'UMN Moodle 2.8 Academic Year 2015-2016';
$defaults['moodle']['block_html_allowcssclasses'] = '1';
$defaults['moodle']['calendar_exportsalt'] = 'WoSSwRJZcGklm3vtwZBLvgAXjPOvlzekxHCXHeN90LPRW342cWqddOpYwQqL';
$defaults['moodle']['calendar_maxevents'] = '20';
$defaults['moodle']['calendar_startwday'] = '1';
$defaults['moodle']['cookiesecure'] = '1';
$defaults['moodle']['core_media_enable_swf'] = '0';
$defaults['moodle']['coursecontact'] = array (
  3 => 1,
  19 => 1,
);
$defaults['moodle']['courserequestadditionalroles'] = array (
  3 => 1,
  11 => 1,
  19 => 1,
  4 => 1,
);
$defaults['moodle']['courserequestemailsender'] = 'mcourses@umn.edu';
$defaults['moodle']['creatornewroleid'] = '3';
$defaults['moodle']['customusermenuitems'] = 'messages,message|/message/index.php|message
myfiles,moodle|/user/files.php|download
mybadges,badges|/badges/mybadges.php|award';
$defaults['moodle']['data_enablerssfeeds'] = '1';
$defaults['moodle']['dbsessions'] = '1';
$defaults['moodle']['debug'] = 32767;
$defaults['moodle']['defaulthomepage'] = '1';
$defaults['moodle']['defaultpreference_autosubscribe'] = '0';
$defaults['moodle']['defaultpreference_trackforums'] = '1';
$defaults['moodle']['defaultsourcecourseid'] = '2';
$defaults['moodle']['devicedetectregex'] = array (
);
$defaults['moodle']['doctonewwindow'] = '1';
$defaults['moodle']['enableavailability'] = '1';
$defaults['moodle']['enableblogs'] = '0';
$defaults['moodle']['enablecompletion'] = '1';
$defaults['moodle']['enablecourserequests'] = '1';
$defaults['moodle']['enabledevicedetection'] = '0';
$defaults['moodle']['enablenotes'] = '0';
$defaults['moodle']['enableoutcomes'] = '1';
$defaults['moodle']['enableplagiarism'] = '1';
$defaults['moodle']['enablerssfeeds'] = '1';
$defaults['moodle']['enablesafebrowserintegration'] = '1';
$defaults['moodle']['enabletgzbackups'] = '1';
$defaults['moodle']['enabletrusttext'] = '1';
$defaults['moodle']['enablewebservices'] = '1';
$defaults['moodle']['enablewsdocumentation'] = '1';
$defaults['moodle']['extramemorylimit'] = '2048M';
$defaults['moodle']['forcelogin'] = '1';
$defaults['moodle']['forceloginforprofileimage'] = '1';
$defaults['moodle']['forum_enablerssfeeds'] = '1';
$defaults['moodle']['forum_enabletimedposts'] = '1';
$defaults['moodle']['forum_maxbytes'] = '5242880';
$defaults['moodle']['frontpage'] = array (
  0 => '0',
);
$defaults['moodle']['frontpageloggedin'] = array (
  0 => '0',
  1 => '7',
  2 => '5',
  3 => '2',
  4 => '6',
);
$defaults['moodle']['fullname'] = 'Moodle 2.8 Academic Year 2015-2016';
$defaults['moodle']['glossary_enablerssfeeds'] = '1';
$defaults['moodle']['grade_aggregateonlygraded'] = array (
  'value' => '1',
  'forced' => false,
  'adv' => false,
);
$defaults['moodle']['grade_aggregateoutcomes'] = array (
  'value' => '0',
  'forced' => true,
  'adv' => true,
);
$defaults['moodle']['grade_droplow'] = array (
  'value' => '0',
  'forced' => false,
  'adv' => false,
);
$defaults['moodle']['grade_export_userprofilefields'] = 'firstname,lastname';
$defaults['moodle']['grade_includescalesinaggregation'] = '0';
$defaults['moodle']['grade_keephigh'] = array (
  'value' => '0',
  'forced' => false,
  'adv' => false,
);
$defaults['moodle']['grade_overridecat'] = '0';
$defaults['moodle']['gradebookroles'] = array (
  5 => 1,
  12 => 1,
  15 => 1,
);
$defaults['moodle']['gradeexport'] = array (
  'txt' => 1,
);
$defaults['moodle']['gradepointmax'] = '300';
$defaults['moodle']['groupenrolmentkeypolicy'] = '0';
$defaults['moodle']['langlist'] = 'en_us,de,es,fr,it,no,fi,sv,ru,ko,ja,zh_cn';
$defaults['moodle']['langmenu'] = '0';
$defaults['moodle']['legacyfilesinnewcourses'] = '1';
$defaults['moodle']['messaging'] = '0';
$defaults['moodle']['navexpandmycourses'] = '0';
$defaults['moodle']['navshowcategories'] = '0';
$defaults['moodle']['navshowfrontpagemods'] = '0';
$defaults['moodle']['navsortmycoursessort'] = 'fullname';
$defaults['moodle']['newsitems'] = '0';
$defaults['moodle']['passwordpolicy'] = '0';
$defaults['moodle']['profileroles'] = array (
  1 => 1,
  3 => 1,
  19 => 1,
  4 => 1,
  5 => 1,
);
$defaults['moodle']['recovergradesdefault'] = '1';
$defaults['moodle']['restorernewroleid'] = '3';
$defaults['moodle']['sessioncookie'] = 'ay15';
$defaults['moodle']['sessioncookiepath'] = '/';
$defaults['moodle']['shortname'] = 'AY15';
$defaults['moodle']['summary'] = '<p>Welcome to the University of Minnesota Moodle Site for Academic Year 2015-2016</p>';
$defaults['moodle']['supportemail'] = 'moodle@umn.edu';
$defaults['moodle']['supportname'] = 'Moodle Support';
$defaults['moodle']['supportpage'] = 'http://z.umn.edu/moodlehelp';
$defaults['moodle']['unlimitedgrades'] = '1';
$defaults['moodlecourse']['format'] = 'topics';
$defaults['moodlecourse']['legacyfiles'] = '2';
$defaults['moodlecourse']['maxbytes'] = '1048576000';
$defaults['moodlecourse']['maxsections'] = '150';
$defaults['moodlecourse']['numsections'] = '18';
$defaults['page']['displayoptions'] = array (
  0 => '5',
  1 => '6',
);
$defaults['page']['requiremodintro'] = '0';
$defaults['quiz']['questionsperpage'] = '0';
$defaults['quiz']['reviewattempt'] = '65536';
$defaults['quiz']['reviewcorrectness'] = '0';
$defaults['quiz']['reviewgeneralfeedback'] = '0';
$defaults['quiz']['reviewmarks'] = '0';
$defaults['quiz']['reviewoverallfeedback'] = '0';
$defaults['quiz']['reviewrightanswer'] = '0';
$defaults['quiz']['reviewspecificfeedback'] = '0';
$defaults['quiz']['shuffleanswers'] = '0';
$defaults['resource']['display'] = '3';
$defaults['resource']['displayoptions'] = array (
  0 => '0',
  1 => '1',
  2 => '2',
  3 => '3',
  4 => '4',
  5 => '5',
  6 => '6',
);
$defaults['resource']['framesize'] = '210';
$defaults['resource']['printintro'] = '0';
$defaults['resource']['requiremodintro'] = '0';
$defaults['scorm']['forcejavascript'] = '0';
$defaults['url']['display'] = '3';
$defaults['url']['displayoptions'] = array (
  0 => '0',
  1 => '1',
  2 => '2',
  3 => '3',
  4 => '5',
  5 => '6',
);
$defaults['url']['framesize'] = '210';
$defaults['url']['printintro'] = '0';
$defaults['url']['requiremodintro'] = '0';
