<?php

$ADMIN->add('server',
            new admin_externalpage(
                'compareconfig',
                get_string('compareconfig', 'local_configutil'),
                $CFG->wwwroot.'/local/configutil/compareconfig.php'));

$ADMIN->add('server',
            new admin_externalpage(
                'compareplugins',
                get_string('compareplugins', 'local_configutil'),
                $CFG->wwwroot.'/local/configutil/compareplugins.php'));


