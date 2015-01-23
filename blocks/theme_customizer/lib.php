<?PHP

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * block plugin to allow user to customize certain themes
 *
 * @package   block
 * @subpackage theme_customizer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright University of Minnesota 2011
 */


class theme_customizer {

    const template_shortname = '__template__';
    const dir_mod            = 0777;

    const notify_no          = 0;
    const notify_yes         = 1;

    const level_none         = 0;
    const level_campus       = 20;
    const level_college      = 30;
    const level_department   = 40;
    const level_course       = 50;

    // class variables
    protected $theme_prefix  = 'auto_';
    protected $output_dir    = 'theme';
    protected $parents       = array();
    protected $gpl           = '';

    /**
     * initialize the class
     */
    public function __construct() {
        $this->gpl = <<< EOB
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

EOB;
        // initialize from config
        $configs = get_config('block_theme_customizer');

        $this->theme_prefix = $configs->prefix;
        $this->output_dir   = $configs->output_dir;
        $this->parents      = preg_split('/[\s,;]+/', $configs->parents);
    }


    /**
     * load a theme data from DB (theme, css files, css entry, graphic file, owner)
     *
     * @param array $filters:
     *         'theme_id'           => <int>
     *         'theme_shortname'    => <string>
     *         'css_file_name'      => <string>
     *         'css_entry_id'       => <int>
     * @param array $include, optional (default null to get all) which tables to include
     * @param array $exclude, optional (default null to exclude none) which tables to exclude
     * @return array, or false if no theme found
     */
    public function load_theme_data($filters, $include = null, $exclude = null) {
        global $DB;

        // determine which tables to get
        $tables = array('theme_css_file', 'theme_css_entry', 'theme_owner', 'theme_custom_setting');

        if (!is_null($include)) {
            $tables = array_intersect($tables, $include);
        }

        if (!is_null($exclude)) {
            $tables = array_diff($tables, $exclude);
        }

        $tables = array_flip($tables);

        // query the theme record from DB, getting all records at once (memory vs querying cost)
        $select_sql =
            "SELECT theme.id AS theme__id,
                    theme.shortname AS theme__shortname,
                    theme.fullname AS theme__fullname,
                    theme.parent AS theme__parent,
                    theme.description AS theme__description,
                    theme.last_compiled AS theme__last_compiled,
                    theme.last_content_hash AS theme__last_content_hash,
                    theme.notify AS theme__notify,
                    theme.category_level AS theme__category_level ";

        $from_sql = "FROM {block_theme} theme ";


        // check if theme_css_file need to be included
        if (isset($tables['theme_css_file']) || isset($tables['theme_css_entry'])) {
            $select_sql .= ",
                    theme_css_file.id AS theme_css_file__id,
                    theme_css_file.name AS theme_css_file__name,
                    theme_css_file.path AS theme_css_file__path,
                    theme_css_file.timemodified AS theme_css_file__timemodified";

            $from_sql .= " LEFT JOIN {block_theme_css_file} theme_css_file ON theme.id = theme_css_file.theme_id";
        }

        // check if theme_css_entry needs to be included
        if (isset($tables['theme_css_entry'])) {
            $select_sql .= ",
                    theme_css_entry.id AS theme_css_entry__id,
                    theme_css_entry.css_identifier AS theme_css_entry__css_identifier,
                    theme_css_entry.css_value AS theme_css_entry__css_value,
                    theme_css_entry.description AS theme_css_entry__description,
                    theme_css_entry.timecreated AS theme_css_entry__timecreated,
                    theme_css_entry.timemodified AS theme_css_entry__timemodified";

            $from_sql .= " LEFT JOIN {block_theme_css_entry} theme_css_entry ON theme_css_file.id = theme_css_entry.css_file_id";
        }


        // check if theme_owner and user need to be included
        if (isset($tables['theme_owner'])) {
            $select_sql .= ",
                    theme_owner.id AS theme_owner__id,
                    theme_owner.edit_level AS theme_owner__edit_level,
                    user.id AS user__id,
                    user.username AS user__username,
                    user.firstname AS user__firstname,
                    user.lastname AS user__lastname";

            $from_sql .= " LEFT JOIN {block_theme_owner} theme_owner ON theme.id = theme_owner.theme_id
                           LEFT JOIN {user} user ON theme_owner.user_id = user.id";
        }

        // check if custom_setting need to be included
        if (isset($tables['theme_custom_setting'])) {
            $select_sql .= ",
                    theme_custom_setting.id AS theme_custom_setting__id,
                    theme_custom_setting.setting_name AS theme_custom_setting__setting_name,
                    theme_custom_setting.setting_value AS theme_custom_setting__setting_value";

            $from_sql .= " LEFT JOIN {block_theme_custom_setting} theme_custom_setting
                                ON theme.id = theme_custom_setting.theme_id ";
        }

        $query = $select_sql . ' ' . $from_sql;

        // add the filters
        $expressions    = array();    // sql expressions
        $params         = array();    // filter values to pass together with the query

        $allowed_filters = array('theme_id'          => 'theme.id',
                                 'theme_shortname'   => 'theme.shortname');

        foreach ($filters as $field => $value) {
            if (isset($allowed_filters[$field])) {
                if (!is_array($value)) {
                    // single value
                    $expressions[]      = " {$allowed_filters[$field]} = :{$field} ";
                    $params[$field]     = $value;
                }
                else {
                    // list of values
                    $i     = 0;
                    $stubs = array();

                    foreach ($value as $val) {
                        $stubs[] = ":{$field}_{$i}";
                        $params[$field.'_'.$i] = $value;
                    }

                    $expressions[] = " {$allowed_filters[$field]} IN (" . implode(',', $stubs) . ')';
                }
            }
        }

        if (count($expressions) > 0) {
            $query .= ' WHERE ' . implode(' AND ', $expressions);
        }

        $rs = $DB->get_recordset_sql($query, $params);

        if (!$rs->valid()) {
            return false;
        }

        $data = array();

        // process the queried data
        foreach ($rs as $row) {
            $theme_id = $row->theme__id;

            // create theme entry if not exists
            if (!isset($data[$theme_id])) {
                $data[$theme_id] = array(
                    'id'               => $theme_id,
                    'shortname'        => $row->theme__shortname,
                    'fullname'         => $row->theme__fullname,
                    'parent'           => $row->theme__parent,
                    'description'      => $row->theme__description,
                    'last_compiled'    => $row->theme__last_compiled,
                    'last_content_hash'=> $row->theme__last_content_hash,
                    'notify'           => $row->theme__notify,
                    'category_level'   => $row->theme__category_level,
                    'owners'           => array(),
                    'css_files'        => array(),
                    'graphic_files'    => array(),
                    'custom_settings'  => array()
                );
            }

            $theme = & $data[$theme_id];

            // add theme_css_file and theme_css_entry
            if (isset($tables['theme_css_file']) && !is_null($row->theme_css_file__id)) {
                $css_file_name = $row->theme_css_file__name;

                // create css_file entry if not exists
                if (!isset($theme['css_files'][$css_file_name])) {
                    $theme['css_files'][$css_file_name] = array(
                        'id'            => $row->theme_css_file__id,
                        'name'          => $row->theme_css_file__name,
                        'path'          => $row->theme_css_file__path,
                        'timemodified'  => $row->theme_css_file__timemodified,
                        'entries'       => array()
                    );
                }

                $entries = & $theme['css_files'][$css_file_name]['entries'];

                // check for css_entry
                if (isset($tables['theme_css_entry']) && !is_null($row->theme_css_entry__id)) {
                    $css_entry_id = $row->theme_css_entry__id;

                    if (!isset($entries[$css_entry_id])) {
                        $entries[$css_entry_id] = array(
                            'id'                => $row->theme_css_entry__id,
                            'css_file_id'       => $row->theme_css_file__id,
                            'css_identifier'    => $row->theme_css_entry__css_identifier,
                            'css_value'         => $row->theme_css_entry__css_value,
                            'description'       => $row->theme_css_entry__description,
                            'timecreated'       => $row->theme_css_entry__timecreated,
                            'timemodified'      => $row->theme_css_entry__timemodified
                        );
                    }
                }
            }

            // add theme_owner
            if (isset($tables['theme_owner']) && !is_null($row->user__id)) {
                $user_id = $row->user__id;

                // create owner entry if not exists
                if (!isset($theme['owners'][$user_id])) {
                    $theme['owners'][$user_id] = array(
                        'edit_level'    => $row->theme_owner__edit_level,
                        'username'      => $row->user__username,
                        'firstname'     => $row->user__firstname,
                        'lastname'      => $row->user__lastname
                    );
                }
            }

            // add theme_custom_setting
            if (isset($tables['theme_custom_setting']) && !is_null($row->theme_custom_setting__id)) {
                $setting_name = $row->theme_custom_setting__setting_name;

                if (!isset($theme['custom_settings'][$setting_name])) {
                    $theme['custom_settings'][$setting_name] = array(
                        'id'            => $row->theme_custom_setting__id,
                        'setting_name'  => $setting_name,
                        'setting_value' => $row->theme_custom_setting__setting_value);
                }
            }
        }

        $rs->close(); // release resource on DBMS - important

        // query the files of the themes
        $fs = get_file_storage();
        $sitecontext = context_system::instance();

        foreach ($data as $theme_id => & $theme) {
            if ($theme['shortname'] == self::template_shortname) {
                // ignore the template theme
                continue;
            }

            $files = $fs->get_area_files($sitecontext->id, 'block_theme_customizer', 'graphic', $theme_id);

            foreach ($files as $file)  {
                $filename = $file->get_filename();

                if (!$file->is_directory()) {
                    $theme['graphic_files'][$file->get_id()] = array(
                        'name'        => $filename,
                        'path'        => $file->get_filepath(),
                        'hash'        => $file->get_contenthash());
                }
            }
        }

        return $data;
    }


    /**
     * compile a custome theme and save the files to the specified location
     *
     * @param int $theme_id
     * @return bool
     */
    public function compile_theme($theme_id) {
        global $DB;

        // load the theme
        $data = $this->load_theme_data(array('theme_id' => $theme_id), null, array('theme_owner'));

        if ($data == false) {
            return false;
        }

        if (!isset($data[$theme_id])) {
            throw new Exception('Cannot find theme id ' . $theme_id);
        }

        // check for ouput directory
        $dir = $this->output_dir;

        $theme = $data[$theme_id];
        $this->create_theme_dir_structure($theme, $dir);    // exception bubble up

        $theme_name = $this->theme_prefix . $theme['shortname'];

        // change to theme dir to compile the files
        chdir("{$dir}/{$theme_name}");

        // calculate the hash of all content
        $hash_state = '';

        // create the version file
        $content = $this->compile_version_file($theme);
        $hash_state .= 'version'.md5($content).';';

        $file     = fopen('version.php', 'w');
        $result   = fwrite($file, $content);
        if ($result === false) {
            throw new Exception('Cannot write to version.php');
        }
        fclose($file);

        // create the config file
        $content  = $this->compile_config_file($theme);
        $hash_state .= 'config:'.md5($content).';';

        $file     = fopen('config.php', 'w');
        $result   = fwrite($file, $content);
        if ($result === false) {
            throw new Exception('Cannot write to config.php');
        }
        fclose($file);

        // create the lang file
        $content = $this->compile_lang_file($theme);
        $hash_state .= 'lang:'.md5($content).';';

        chdir("{$dir}/{$theme_name}/lang/en");
        $file     = fopen("theme_{$theme_name}.php", 'w');
        $result   = fwrite($file, $content);
        if ($result === false) {
            throw new Exception("Cannot write to lang/theme_{$theme_name}.php");
        }
        fclose($file);

        // create the CSS files
        chdir("{$dir}/{$theme_name}/style");
        $css = $this->compile_css_files($theme);

        foreach ($css as $file_name => $content) {
            $hash_state .= 'css:'.$file_name.':'.md5($content).';';

            $file     = fopen($file_name, 'w');
            $result   = fwrite($file, $content);
            if ($result === false) {
                throw new Exception("Cannot write to style/{$file_name}");
            }
            fclose($file);

            // also try to delete the file if content is empty,
            // the blank-writing above is a fallback in case the below fails
            if (trim($content) == '') {
                @unlink($file_name);
            }
        }

        // create the graphic files
        chdir("{$dir}/{$theme_name}/pix");
        $fs = get_file_storage();
        $sitecontext = context_system::instance();

        $graphic_files = $fs->get_area_files($sitecontext->id, 'block_theme_customizer', 'graphic', $theme_id);

        foreach ($graphic_files as $graphic_file) {
            if ($graphic_file->is_directory()) {
                continue; // ignore directory
            }

            $file_name = $graphic_file->get_filename();
            $content = $graphic_file->get_content();

            $hash_state .= 'graphic:'.$file_name.':'.md5($content).';';

            $file      = fopen($file_name, 'w');
            $result    = fwrite($file, $content);
            if ($result === false) {
                throw new Exception("Cannot write to pix/{$file_name}");
            }
            fclose($file);
        }

        // notify about theme change if applicable
        $new_content_hash = md5($hash_state);
        if ($theme['notify'] == self::notify_yes && $theme['last_content_hash'] != $new_content_hash) {
            $this->notify_theme_change($theme);
        }

        // update theme record
        $theme_record = new stdClass();
        $theme_record->id                = $theme['id'];
        $theme_record->last_compiled     = time();
        $theme_record->last_content_hash = $new_content_hash;

        $DB->update_record('block_theme', $theme_record);
    }


    /**
     * helper function to help initiating a theme directory
     * @param array $theme theme data as returned by load_theme_data()
     * @param string $dir
     */
    protected function create_theme_dir_structure($theme, $dir) {
        $theme_name = $this->theme_prefix . $theme['shortname'];

        // create the output directory if needed
        if (!is_dir($dir)) {
            if ( ! mkdir($dir, self::dir_mod) ) {
                throw new Exception("Cannot create theme directory at {$dir}");
            }
        }

        // create the theme dir if needed
        if (!is_dir("{$dir}/{$theme_name}")) {
            if ( ! mkdir("{$dir}/{$theme_name}", self::dir_mod) ) {
                throw new Exception("Cannot create theme directory at {$dir}/{$theme_name}");
            }
        }

        if ( ! chdir("{$dir}/{$theme_name}") ) {
            throw new Exception("Cannot change directory to {$dir}/{$theme_name}");
        }

        // delete the existing graphic files
        $pix_dir = $dir . '/' . $theme_name . '/pix';

        if (is_dir($pix_dir)) {
            $objects = scandir($pix_dir);

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($pix_dir . '/' . $object) == "dir") {
                        rmdir($pix_dir . '/' . $object);
                    }
                    else {
                        unlink($pix_dir . '/' . $object);
                    }
                }
             }

             reset($objects);
        }

        // create the sub dirs if needed
        $subdirs = array('lang/en', 'pix', 'style');

        foreach ($subdirs as $subdir) {
            if (!is_dir($subdir)) {
                if ( ! mkdir($subdir, self::dir_mod, true) ) {
                    throw new Exception("Cannot create theme directory at {$dir}/{$theme_name}/{$subdir}");
                }
            }
        }

        return true;
    }


    /**
     * helper function to compile the version.php content
     * @param array $theme
     * @return string
     */
    protected function compile_version_file($theme) {
        $theme_name = "'".addcslashes('theme_'.$this->theme_prefix . $theme['shortname'], "\\'")."'";
        $theme_version = get_config('block_theme_customizer', 'version');
        $theme_dependencies ='array()';
        // TODO step through dependencies and use the greatest required version number
        $required_version = 2013110500;

        $str = <<< EOF
<?php
{$this->gpl}

/**
 * Version info for an auto-generated custom theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

\$plugin->version      = {$theme_version};
\$plugin->requires     = {$required_version};
\$plugin->component    = {$theme_name};
\$plugin->dependencies = {$theme_dependencies};
EOF;

        return $str;
    }

    /**
     * helper function to compile the config.php content
     * @param array $theme
     * @return string
     */
    protected function compile_config_file($theme) {
        $theme_name = addcslashes($this->theme_prefix . $theme['shortname'], "\\'");

        // Theme Customizer Parents Setting
        $global_parents_str = implode(',', array_map(function($str) {
            return "'" . addcslashes($str, "\\'") . "'";
        }, $this->parents));

        // Custom Theme Parents
        $theme_parents_str = implode(',', array_map(function($str) {
            return "'" . addcslashes($str, "\\'") . "'";
        }, $this->get_theme_parents($theme)));

        $parents_with_env_str = $theme_parents_str
            . ($theme_parents_str ? ',' : '')
            . '$CFG->theme_umnbase_env,'
            . $global_parents_str;

        $parents_str = $theme_parents_str
            . ($theme_parents_str ? ',' : '')
            . $global_parents_str;

        // consider the custom settings
        if (isset($theme['custom_settings'])) {
            $values = array();
            foreach ($theme['custom_settings'] as $name => $setting) {
                $values[] = "'{$name}' => '".addslashes($setting['setting_value'])."'";
            }
            $custom_settings_str = 'array(' . implode(",\n", $values) . ')';
        }
        else {
            $custom_settings_str = 'array()';
        }

        $str = <<< EOF
<?php

{$this->gpl}

/**
 * Configuration for an auto-generated custom theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

\$THEME->name = '{$theme_name}';
\$THEME->doctype = 'html5';

\$CFG->theme_umnbase_env = empty(\$CFG->theme_umbase_env)
    ? get_config('block_theme_customizer', 'env_theme')
    : \$CFG->theme_umnbase_env;

if (!empty(\$CFG->theme_umnbase_env) && \$CFG->theme_umnbase_env != \$THEME->name) {
    if (is_array(\$CFG->theme_umnbase_env)) {
        \$THEME->parents = array_merge(array({$theme_parents_str}), \$CFG->theme_umnbase_env, array({$global_parents_str}));
    }
    else {
        \$THEME->parents = array({$parents_with_env_str});
    }
}
else {
    \$THEME->parents = array({$parents_str});
}

\$THEME->rendererfactory = 'theme_overridden_renderer_factory'; // use our renderer
\$THEME->supportscssoptimisation = false;
\$THEME->yuicssmodules = array();

// if we don't define a sheet here, parent's will be used instead
\$THEME->sheets = array(
    'core',     /** Must come first**/
    'admin',
    'blocks',
    'calendar',
    'course',
    'dock',
    'grade',
    'message',
    'modules',
    'question',
    'user',
    'banner',
    'css3'      /** Sets up CSS 3 + browser specific styles **/
);

\$THEME->editor_sheets = array('editor');

if (!isset(\$THEME->settings)) {
    \$THEME->settings = new stdObj();
}

for (\$i = count(\$THEME->parents); \$i > 0; \$i--) {
    \$themename = \$THEME->parents[\$i-1];
    \$themeconfig = get_config('theme_'.\$themename);
    \$THEME->settings = (object) array_merge((array) \$THEME->settings, (array) \$themeconfig);
}

if (!isset(\$THEME->settings->custom_values)) {
    \$THEME->settings->custom_values = array();
}

\$THEME->settings->custom_values = array_merge({$custom_settings_str}, \$THEME->settings->custom_values);

EOF;

        return $str;
    }




    /**
     * helper function to compile the settings.php content
     * @param array $theme
     * @return string
     */
    protected function compile_settings_file($theme) {
        $str = <<< EOF
<?php

{$this->gpl}

/**
 * Setting for an auto-generated custom theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


<?php

defined('MOODLE_INTERNAL') || die;


EOF;

        return $str;
    }


    /**
     * helper function to compile the config.php content
     * @param array $theme
     * @return string
     */
    protected function compile_lang_file($theme) {
        $fullname     = addcslashes($theme['fullname'], "\\'");
        $description  = addcslashes($theme['description'], "\\'");

        $str = <<< EOF
<?php

{$this->gpl}

\$string['pluginname'] = '{$fullname}';
\$string['configtitle'] = '{$fullname}';
\$string['region-side-post'] = 'Right';
\$string['region-side-pre'] = 'Left';
\$string['region-content'] = 'Content';
\$string['choosereadme'] = '{$description}';
EOF;

        return $str;
    }



    /**
     * helper function to compile the CSS content
     * @param array $theme
     * @return array
     */
    protected function compile_css_files($theme) {
        $css = array();

        foreach ($theme['css_files'] as $file_name => $file) {
            $css[$file_name] = '';

            foreach ($file['entries'] as $entry_id => $entry) {
                if (!empty($entry['css_value'])) {
                    $css[$file_name] .= $entry['css_identifier'] . ' {' . $entry['css_value'] . "}\n";
                }
            }
        }

        return $css;
    }


    /**
     * update template entries of a theme based on what available in template
     *
     * @param int $theme_id
     * @param array $entries
     *         <template_css_entry_id> => <theme_value>
     * @param array $theme, optional, from load_theme_data()
     * @param array $template, optional, from load_theme_data()
     * @return bool
     * @throws moodle_exception
     */
    public function update_predefined_entries($theme_id, $entries, $theme = null, $template = null) {
        global $DB;

        // load the theme and template if needed
        if (is_null($theme)) {
            $data  = $this->load_theme_data(array('theme_id' => $theme_id), array('theme_css_file', 'theme_css_entry'));
            $theme = $data[$theme_id];
        }

        if (is_null($template)) {
            $data     = $this->load_theme_data(array('theme_shortname' => self::template_shortname),
                                               array('theme_css_file', 'theme_css_entry'));
            $template = current($data);
        }

        // combine all template CSS entries into one place to match with submitted data
        $template_entries = array();        // entry_id => css_identifier

        foreach ($template['css_files'] as $filename => $file) {
            foreach ($file['entries'] as $entry_id => $entry) {
                $template_entries[$entry_id] = array('identifier'   => $entry['css_identifier'],
                                                     'filename'     => $filename);
            }
        }

        // create a map from css_identifier to entry_id for the theme
        $theme_entries = array();    // css_identifier => entry_id
        $existed_files = array();    // keep track of the theme's existing CSS files so that we don't have to create them

        foreach ($theme['css_files'] as $filename => $file) {
            $existed_files[$filename] = $file['id'];

            foreach ($file['entries'] as $entry_id => $entry) {
                $theme_entries[$entry['css_identifier']] = $entry_id;
            }
        }


        // process each submitted entry
        foreach ($entries as $tp_entry_id => $new_value) {
            // only process valid template entries
            if (isset($template_entries[$tp_entry_id])) {
                $file_name = $template_entries[$tp_entry_id]['filename'];

                // create the CSS file if needed
                if (!isset($existed_files[$file_name])) {
                    $css_file = new stdClass();
                    $css_file->theme_id     = $theme_id;
                    $css_file->name         = $file_name;
                    $css_file->path         = 'style';
                    $css_file->timemodified = time();

                    $css_file_id = $DB->insert_record('block_theme_css_file', $css_file);
                    $existed_files[$file_name] = $css_file_id;
                }

                $identifier = $template_entries[$tp_entry_id]['identifier'];
                $entry = new stdClass();

                $entry->css_identifier = $identifier;
                $entry->css_value      = $new_value;
                $entry->timemodified   = time();

                if (isset($theme_entries[$identifier])) {
                    // update
                    $entry->id = $theme_entries[$identifier];
                    $DB->update_record('block_theme_css_entry', $entry);
                }
                else {
                    // insert
                    $entry->css_file_id    = $existed_files[$file_name];
                    $entry->description    = 'from template';
                    $entry->timecreated    = time();

                    $DB->insert_record('block_theme_css_entry', $entry);
                }
            }
        }

        return true;
    }



    /**
     *
     * check ownership if not a theme manager
     * @param mixed $theme theme ID, or theme array from $this->load_theme_data()
     */
    public function verify_theme_ownership($theme) {
        global $USER;

        $sitecontext = context_system::instance();

        if (has_capability('block/theme_customizer:manage', $sitecontext)) {
            return true;
        }

        // load the theme if needed
        if (is_string($theme) || count($theme['owners']) == 0) {
            $theme_id = (is_array($theme) && isset($theme['id'])) ? $theme['id'] : $theme;

            $data = $this->load_theme_data(array('theme_id' => $theme_id), array('theme_owner'));
            $theme = $data[$theme_id];
        }

        foreach ($theme['owners'] as $user_id => $owner) {
            if ($USER->id == $user_id) {
                return true;
            }
        }

        return false;
    }



    /**
     * generate the navigation tree for a page of theme_customizer
     * @param string $page
     */
    public function setup_navbar($page, $theme_id = '', $file_name = '', $entry_id = '') {
        global $PAGE;

        if (has_capability('block/theme_customizer:manage', context_system::instance())) {
            $tree = array(
                'add_owner'        => 'edit_theme',
                'edit_css'         => 'edit_theme',
                'update_graphic'   => 'edit_theme',
                'edit_theme'       => '',
                'import_theme'     => '',
                'restore_theme'    => '',
                'add_theme'        => '',
                'edit_template'    => '',
                'manage_notification' => '');

            $node = $PAGE->navigation->add(get_string('page_admin', 'block_theme_customizer'), "/blocks/theme_customizer/admin.php");
        }
        else {
            $tree = array(
                'add_owner'        => 'edit_theme',
                'edit_css'         => 'edit_theme',
                'update_graphic'   => 'edit_theme',
                'edit_theme'       => '',
                'restore_theme'    => '');

            $node = $PAGE->navigation->add(get_string('page_user', 'block_theme_customizer'), "/blocks/theme_customizer/user.php");
        }

        // unknown page
        if (!isset($tree[$page])) {
            return false;
        }

        // find the path
        $path = array();
        while ( $page != '' ) {
            $path[] = $page;
            $page   = $tree[$page];
        }

        $path = array_reverse($path);

        // add to navigation
        foreach ($path as $page) {
            // form the url
            switch ($page) {
                case 'add_owner':
                case 'update_graphic':
                case 'edit_theme':
                case 'restore_theme':
                case 'edit_css':
                    $params = array('theme_id' => $theme_id);
                    break;

                case 'edit_template':
                    $params = array('entry_id' => $entry_id);
                    break;

                default:
                    $params = array();
            }

            $url = new moodle_url("/blocks/theme_customizer/{$page}.php", $params);
            $node = $node->add(get_string('page_'.$page, 'block_theme_customizer'), $url);
        }

        return true;
    }


    /**
     * perform backup of a theme
     * @param int $theme_id
     * @return string temp filename (include path)
     * @throws Exception
     */
    public function export_theme($theme_id) {
        global $CFG;

        // load the theme data
        $themes = $this->load_theme_data(array('theme_id' => $theme_id));
        $theme = $themes[$theme_id];

        // add the theme_customizer version
        $theme['theme_customizer_version'] = get_config('block_theme_customizer', 'version');

        // create a temporary dir
        $dir_name = md5(sesskey().microtime()).'_'.$theme['shortname'];
        $temp_dir = 'theme_customizer_export/' . $dir_name;
        make_temp_directory($temp_dir);
        $abs_temp_dir = $CFG->tempdir.'/'.$temp_dir;

        // write theme data into JSON file
        $data_file = $abs_temp_dir.'/theme.json';

        if (!$handle = fopen($data_file, 'w')) {
            throw new moodle_exception('cannotcreatetempdir');
        }

        fwrite($handle, json_encode($theme));
        fclose($handle);

        $files = array();        // list of files to zip later
        $files['theme.json'] = $abs_temp_dir.'/theme.json';

        // load the graphic files into the temp area
        $fs = get_file_storage();
        $sitecontext = context_system::instance();

        $graphic_files   = $fs->get_area_files($sitecontext->id, 'block_theme_customizer', 'graphic', $theme_id);
        $graphic_dir     = $temp_dir.'/pix';
        $abs_graphic_dir = $CFG->tempdir.'/'.$graphic_dir;

        make_temp_directory($graphic_dir);

        foreach ($graphic_files as $graphic_file) {
            if ($graphic_file->is_directory()) {
                continue; // ignore directory
            }

            $file_name = $graphic_file->get_filename();
            $file      = fopen($abs_graphic_dir.'/'.$file_name, 'w');
            $result    = fwrite($file, $graphic_file->get_content());
            if ($result === false) {
                throw new Exception("Cannot write to pix/{$file_name}");
            }
            fclose($file);

            $files['pix/'.$file_name] = $abs_graphic_dir.'/'.$file_name;
        }

        // Calculate the zip fullpath
        $zipfile = "{$CFG->tempdir}/theme_customizer_export/{$dir_name}.mtz";

        // Get the zip packer
        $zippacker = get_file_packer('application/zip');

        // zip the whole theme temp dir
        $zippacker->archive_to_pathname($files, $zipfile);

        // clean up the theme temp dir
        remove_dir("{$CFG->tempdir}/theme_customizer_export/{$dir_name}");

        // return the zip filename
        return $zipfile;
    }



    /**
     * import a theme from a temp directory (expanded from an export file)
     * @param string $theme_dir full path to the temp dir
     * @param string $shortname
     * @param string $fullname
     * @return bool
     * @throws Exception
     */
    public function import_theme($theme_dir, $shortname = null, $fullname = null) {
        global $DB, $USER;

        // load the theme description (JSON) file
        $file_content = file_get_contents($theme_dir . '/theme.json');

        if (!$file_content) {
            throw new Exception(get_string('invalid_theme_def', 'block_theme_customizer', array('filename' => 'theme.json')));
        }

        $theme_struct = json_decode($file_content, true);

        // create the theme record
        $theme_rec  = new stdClass();
        $theme_rec->shortname     = is_null($shortname) ? $theme_struct['shortname'] : $shortname;
        $theme_rec->fullname      = is_null($fullname) ? $theme_struct['fullname'] : $fullname;
        $theme_rec->description   = $theme_struct['description'];
        $theme_rec->parent        = isset($theme_struct['parent']) ? $theme_struct['parent'] : '';
        $theme_rec->category_level= $theme_struct['category_level'];
        $theme_rec->notify        = $theme_struct['notify'];
        $theme_rec->timemodified  = time();
        $theme_rec->last_compiled = 0;

        $theme_id = $DB->insert_record('block_theme', $theme_rec, true);

        // create the CSS file records
        foreach ($theme_struct['css_files'] as $css_file) {
            $css_file_rec = new stdClass();
            $css_file_rec->theme_id     = $theme_id;
            $css_file_rec->name         = $css_file['name'];
            $css_file_rec->path         = $css_file['path'];
            $css_file_rec->timemodified = time();

            $css_file_id = $DB->insert_record('block_theme_css_file', $css_file_rec, true);

            // create the CSS entry records
            foreach ($css_file['entries'] as $css_entry) {
                $css_entry_rec = new stdClass();
                $css_entry_rec->css_file_id     = $css_file_id;
                $css_entry_rec->css_identifier  = $css_entry['css_identifier'];
                $css_entry_rec->css_value       = $css_entry['css_value'];
                $css_entry_rec->description     = $css_entry['description'];
                $css_entry_rec->timecreated     = time();
                $css_entry_rec->timemodified    = time();

                $DB->insert_record('block_theme_css_entry', $css_entry_rec);
            }
        }

        // look up owners and add if match (using ID and username)
        $user_ids = array_keys($theme_struct['owners']);

        if (count($user_ids) > 0) {
            $users = $DB->get_records_list('user', 'id', $user_ids);

            foreach ($users as $user) {
                $candidate = $theme_struct['owners'][$user->id];

                if ($user->username == $candidate['username']) {
                    $owner_rec = new stdClass();
                    $owner_rec->theme_id     = $theme_id;
                    $owner_rec->user_id      = $user->id;
                    $owner_rec->edit_level   = $candidate['edit_level'];

                    $DB->insert_record('block_theme_owner', $owner_rec);
                }
            }
        }

        // add custom settings if available
        if (isset($theme_struct['custom_settings'])) {
            foreach ($theme_struct['custom_settings'] as $setting) {
                $setting_rec = new stdClass();
                $setting_rec->theme_id      = $theme_id;
                $setting_rec->setting_name  = $setting['setting_name'];
                $setting_rec->setting_value = $setting['setting_value'];

                $DB->insert_record('block_theme_custom_setting', $setting_rec);
            }
        }


        // add graphic files
        $sitecontext   = context_system::instance();
        $graphic_files = get_directory_list($theme_dir . '/pix', '', false, false);
        $fs            = get_file_storage();

        foreach ($graphic_files as $filename) {
            $file_record = array(
                'contextid'   => $sitecontext->id,
                'component'   => 'block_theme_customizer',
                'filearea'    => 'graphic',
                'itemid'      => $theme_id,
                'filepath'    => '/',
                'filename'    => basename($filename),
                'timecreated' => time(),
                'timemodified'=> time(),
                'userid'      => $USER->id);

            $fs->create_file_from_pathname($file_record, $theme_dir . '/pix/' . $filename);
        }

        return true;
    }


    /**
     * restore a theme from a temp directory (expanded from an export file),
     * only perform tasks that a theme owner can do:
     * - keep the existing theme record and owners
     * - delete existing CSS files, CSS entries, and graphic files
     * - import CSS files, CSS entries, and graphic files from the provided theme_dir
     *
     * @param int $theme_id
     * @param string $theme_dir full path to the temp dir
     * @return bool
     * @throws Exception
     */
    public function restore_theme($theme_id, $theme_dir) {
        global $DB, $USER;

        // load the theme description (JSON) file
        $file_content = file_get_contents($theme_dir . '/theme.json');

        if (!$file_content) {
            throw new Exception(get_string('invalid_theme_def', 'block_theme_customizer', array('filename' => 'theme.json')));
        }

        $theme_struct = json_decode($file_content, true);

        // delete the existing CSS files, CSS entries, and graphic files
        $themes = $this->load_theme_data(array('theme_id' => $theme_id));
        $existing_theme = $themes[$theme_id];

        $css_file_ids = array();
        foreach ($existing_theme['css_files'] as $file) {
            $css_file_ids[] = $file['id'];
        }

        $DB->delete_records_list('block_theme_css_entry', 'css_file_id', $css_file_ids);
        $DB->delete_records_list('block_theme_css_file', 'id', $css_file_ids);

        $site_context = context_system::instance();
        $fs = get_file_storage();

        $fs->delete_area_files($site_context->id, 'block_theme_customizer', 'graphic', $theme_id);


        // restore new CSS file records
        foreach ($theme_struct['css_files'] as $css_file) {
            $css_file_rec = new stdClass();
            $css_file_rec->theme_id     = $theme_id;
            $css_file_rec->name         = $css_file['name'];
            $css_file_rec->path         = $css_file['path'];
            $css_file_rec->timemodified = time();

            $css_file_id = $DB->insert_record('block_theme_css_file', $css_file_rec, true);

            // restore new CSS entry records
            foreach ($css_file['entries'] as $css_entry) {
                $css_entry_rec = new stdClass();
                $css_entry_rec->css_file_id     = $css_file_id;
                $css_entry_rec->css_identifier  = $css_entry['css_identifier'];
                $css_entry_rec->css_value       = $css_entry['css_value'];
                $css_entry_rec->description     = $css_entry['description'];
                $css_entry_rec->timecreated     = time();
                $css_entry_rec->timemodified    = time();

                $DB->insert_record('block_theme_css_entry', $css_entry_rec);
            }
        }

        // add graphic files
        $sitecontext   = context_system::instance();
        $graphic_files = get_directory_list($theme_dir . '/pix', '', false, false);
        $fs            = get_file_storage();

        foreach ($graphic_files as $filename) {
            $file_record = array(
                'contextid'   => $sitecontext->id,
                'component'   => 'block_theme_customizer',
                'filearea'    => 'graphic',
                'itemid'      => $theme_id,
                'filepath'    => '/',
                'filename'    => basename($filename),
                'timecreated' => time(),
                'timemodified'=> time(),
                'userid'      => $USER->id);

            $fs->create_file_from_pathname($file_record, $theme_dir . '/pix/' . $filename);
        }

        return true;
    }


    /**
     * clear the theme cache
     *
     * @param int $theme_id
     * @param array $theme theme data struct, null if not available
     * @return bool
     */
    public function clear_theme_cache($theme_id, $theme = null) {
        global $CFG;

        require_once("{$CFG->libdir}/filelib.php");

        // load the theme if needed
        if (is_null($theme)) {
            $data = $this->load_theme_data(array('theme_id' => $theme_id), null, array('theme_owner'));

            if ($data == false) {
                return false;
            }

            if (!isset($data[$theme_id])) {
                throw new Exception('Cannot find theme id ' . $theme_id);
            }

            $theme = $data[$theme_id];
        }

        return fulldelete("{$CFG->localcachedir}/theme/{$CFG->themerev}/{$this->theme_prefix}{$theme['shortname']}");
    }


    /**
     * helper function to return required parent themes
     * @param array $theme
     * @param array $parents
     * @return array
     */
    protected function get_theme_parents($theme, $parents = array()) {
        if (!empty($theme['parent'])) {
            do {
                if ($themes = $this->load_theme_data(array('theme_shortname' => str_replace($this->theme_prefix, '', $theme['parent'])))) {
                    $theme = null;
                    foreach ($themes as $theme_id => $theme_config) {
                        $parents[] = $this->theme_prefix . $theme_config['shortname'];
                        $theme = $theme_config;
                    }
                } else {
                    break;
                }
            } while (!empty($theme) && !empty($theme['parent']));
        }

        return $parents;
    }


    /**
     * for a user, get the list of themes that the user owns
     * @param int $user_id
     * @return array of theme shortnames
     */
    public function get_owner_themes($user_id) {
        global $DB;

        $query = 'SELECT theme.shortname AS theme__shortname
                  FROM {block_theme_owner} theme_owner
                       INNER JOIN {block_theme} theme ON theme_owner.theme_id = theme.id
                  WHERE theme_owner.user_id = :user_id';

        $result = $DB->get_recordset_sql($query, array('user_id' => $user_id));

        $themes = array();
        foreach ($result as $row) {
            $theme_name = $this->theme_prefix . $row->theme__shortname;
            $themes[$theme_name] = $theme_name;
        }

        $result->close();
        return $themes;
    }


    /**
     * clean shortname for a theme to be a safe dirname
     *
     * @param string $shortname
     */
    public function validate_shortname($shortname) {
        return str_replace('-', '', clean_param(strtolower($shortname), PARAM_SAFEDIR));
    }


    /**
     * send email to notification list about a theme change
     *
     * @param mixed $theme, could be theme array/object, or theme_id
     * @return bool
     */
    public function notify_theme_change($theme) {
        global $CFG;

        // get the theme name
        if (is_object($theme)) {
            $theme_name = $theme->fullname.' ('.$theme->shortname.')';
        }
        else {
            if (is_string($theme)) {
                $theme = $this->load_theme_data(array('theme_id' => $theme_id), array('theme'));
            }
            $theme_name = $theme['fullname'].' ('.$theme['shortname'].')';
        }

        // get the list of recipients
        $recipients = get_config('block_theme_customizer', 'notification_recipients');
        $recipients = array_unique(preg_split(
                            "/[\s,;]+/",
                            trim(str_replace(array("\t","\r","\n",'"',"'"), ' ', $recipients)),
                            -1,
                            PREG_SPLIT_NO_EMPTY));

        if (count($recipients) == 0) {
            return false;
        }

        // mail
        $mail = get_mailer();

        $mail->From    = $CFG->noreplyaddress;
        $mail->Subject = 'Theme change notification';

        foreach ($recipients as $address) {
            $mail->AddAddress($address);
        }

        $mail->Body = 'Theme "'.$theme_name.'" has been built with new contents.';

        return $mail->Send();
    }


    /**
     *
     */
    public function get_theme_dir($theme) {
        // check for ouput directory
        $dir = $this->output_dir;

        $theme_name = $this->theme_prefix . $theme['shortname'];

        return "{$dir}/{$theme_name}";
    }
}
