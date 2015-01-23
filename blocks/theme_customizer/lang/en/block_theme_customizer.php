<?php

$string['pluginname'] = 'Theme customizer';
$string['blockname'] = 'Theme Customizer';
$string['blocktitle'] = 'Theme Customizer';
$string['usage'] = 'Theme Customizer Help';

$string['theme_customizer:manage'] = 'Manage custom theme';
$string['theme_customizer:use'] = 'Edit custom theme';
$string['theme_customizer:addinstance'] = 'Add a new Theme Customizer block';

// settings
$string['settings'] = 'Theme customizer settings';
$string['update_settings'] = 'Theme customizer settings';
$string['setting_prefix'] = 'Theme name prefix (to distinguish from normal themes)';
$string['setting_output_dir'] = 'Output dir for building themes';
$string['setting_parents'] = 'Parent themes (comma separated)';
$string['setting_env_theme'] = 'Environment Theme';
$string['setting_notification_recipients'] = 'Notification recipients (comma separated emails)';
$string['submit_update_settings'] = 'Save settings';

/** edit theme page */
$string['edit_theme'] = 'Edit theme';
$string['submit_build_theme'] = 'Build theme';

$string['manage_ownership_header'] = 'Ownership';
$string['add_owner_link'] = 'Add owners';
$string['submit_ownership_changes'] = 'Save ownerships';

$string['manage_detail_header'] = 'Theme properties';
$string['theme_shortname'] = 'Short name';
$string['theme_fullname'] = 'Full name';
$string['theme_parent'] = 'Parent theme';
$string['theme_description'] = 'Description';
$string['theme_notify'] = 'Notification';
$string['theme_category_level'] = 'Category level';
$string['submit_detail_changes'] = 'Save theme properties';

$string['category_level_none']         = 'Unspecified';
$string['category_level_campus']       = 'Campus';
$string['category_level_college']      = 'College';
$string['category_level_department']   = 'Department';
$string['category_level_course']       = 'Course';

$string['manage_custom_setting_values']  = 'Manage custom settings';
$string['custom_setting_name']           = 'Custom setting';
$string['custom_setting_value']          = 'Custom setting value';
$string['add_custom_setting_value']      = 'Add {no} more custom settings';


$string['manage_predefined_entry'] = 'CSS Predefined Entries';
$string['submit_update_predefined_entry'] = 'Save predefined entries';
$string['no_template_entry'] = 'No template entry defined by admin';

$string['manage_css_files'] = 'CSS Files';
$string['css_priority'] = 'Loading order: core > admin > blocks > calendar > course > dock > grade > message > modules > question > user > banner';

$string['manage_graphic_files'] = 'Graphic Files';
$string['update_graphic_link'] = 'Update graphic files';
$string['graphic_file_usage'] = 'To include the graphic files in CSS, use <a href="http://docs.moodle.org/dev/Themes_2.0#Making_use_of_images">Moodle syntax</a>. Example: "background:url([[pix:theme|banner_bg]])" to include banner_bg.png';

$string['update_graphic_files'] = 'Update graphic files';
$string['submit_update_graphic'] = 'Save changes';

// admin page
$string['admin_theme'] = 'Manage themes';
$string['delete_theme_confirm'] = 'Are you sure you want to delete theme "{$a->name}"? All associated owner, CSS files, and graphic files will be deleted.';
$string['delete_theme'] = 'Delete a theme';

$string['delete_template_entry_confirm'] = 'Are you sure you want to delete template entry "{$a->name}"?';
$string['delete_template_entry'] = 'Delete a template entry';

// user page
$string['customize_theme'] = 'Customize theme';


// add_owner page
$string['add_owner'] = 'Add theme owners';
$string['submit_add_owner'] = 'Add owner';
$string['owner_username_input'] = 'Full usernames, separated by commas or newlines';

// remove owner page
$string['remove_owner'] = 'Remove owner';
$string['remove_owner_confirm'] = 'Are you sure you want to remove {$a->name} from the theme?';

// add_theme page
$string['add_theme'] = 'Add a new theme';
$string['submit_add_theme'] = 'Create theme';

// edit_template page
$string['entry_css_identifier'] = 'CSS identifier';
$string['entry_css_value'] = 'CSS value';
$string['entry_description'] = 'Description';
$string['css_file'] = 'CSS file';
$string['entry_file_id'] = 'CSS file';
$string['submit_update_template_entry'] = 'Save entries';

// edit_css page
$string['edit_custom_entry'] = 'Edit custom CSS entries';
$string['submit_update_custom_entry'] = 'Save entries';
$string['submit_update_custom_entry_build'] = 'Save entries and build theme';
$string['submit_update_custom_entry_delete'] = 'Delete selected entries';
$string['no_custom_entry'] = 'There are no custom entries.';
$string['add_custom_entry'] = 'Add a custom CSS entry';
$string['new_entry'] = 'New entry';
$string['submit_add_custom_entry'] = 'Add entry';
$string['delete_entry_confirm'] = 'Are you sure you want to delete {$a->count} entries of theme "{$a->theme_name}"?';
$string['delete_entry'] = 'delete';
$string['delete_entry_title'] = 'Delete CSS entry';
$string['show_all'] = 'Show all';
$string['hide_all'] = 'Hide all';

// import_theme page
$string['upload_theme_archive_import'] = 'Upload theme to be imported';
$string['submit_theme_archive'] = 'Next';
$string['naming_import_theme'] = 'Import theme name';

// restore_theme page
$string['upload_theme_archive_restore'] = 'Upload theme to be restored';
$string['submit_theme_restore'] = 'Restore to theme';
$string['restore_theme_explain'] = 'Restore a theme archive into this theme. Note that existing CSS entries and graphic files will be deleted. Theme properties (name, description) and owners remain intact.';

// manage notification page
$string['submit_update_notification'] = 'Update notifications';
$string['edit_notification'] = 'Select themes to be notified about';

// errors
$string['no_editing_template'] = 'Cannot edit template theme.';
$string['not_owner'] = 'You are not an owner of this theme.';
$string['no_username'] = 'Please provide at least one valid username.';
$string['unknown_action'] = 'Unknown action.';
$string['invalid_css'] = 'Invalid CSS file specified ({$a->filename}).';
$string['duplicate_css_identifier'] = 'The entry identifier "{$a->identifier}" alread existed in CSS file {$a->filename}. You might want to use your browser\'s BACK button to correct the unsaved changes.';
$string['uknown_fieldprefix'] = 'Unknown field prefix submitted "{$a->field}".';
$string['invalid_entry_id'] = 'Invalid entry id to delete: {$a->entry_id}';
$string['invalid_theme_def'] = 'Invalid theme definition ({$a->filename})';
$string['cannot_extract_archive'] = 'Cannot extract theme archive ({$a->filename})';
$string['cannot_save_upload'] = 'Cannot save uploaded archive theme file';
$string['duplicate_name'] = 'A theme already existed with shortname "{$a->name}". Please select a different shortname.';
$string['invalid_shortname'] = 'Invalid shortname "{$a->shortname}". Please use only lowercase a-z, 0-9, and underscores.';
$string['missing_files'] = 'Cannot find one or more existing files. You can still upload new files then save changes to start fresh with new files.';

// page name on navigation bar
$string['page_user'] = 'Customize themes';
$string['page_admin'] = 'Manage themes';
$string['page_edit_theme'] = 'Edit theme';
$string['page_import_theme'] = 'Import a theme';
$string['page_restore_theme'] = 'Restore a theme';
$string['page_add_theme'] = 'Add a custom theme';
$string['page_edit_template'] = 'Edit template entry';
$string['page_add_owner'] = 'Add theme owner';
$string['page_edit_css'] = 'Edit theme CSS';
$string['page_update_graphic'] = 'Update theme graphic files';
$string['page_manage_notification'] = 'Manage theme notification';
