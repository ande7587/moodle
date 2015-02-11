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
 *
 * @package    block_theme_customizer
 * @copyright  2014 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_theme_customizer = {};


/**
 * initialize the edit CSS page
 * @param {object} Y, the YUI instance
 * @param {object} data, list of data from server
 *
 * use M.util.get_string() for language strings from server side
 */
M.block_theme_customizer.init = function(Y, data) {
    this.Y = Y;        // keep a ref to YUI instance
    var self  = this;
    M.block_theme_customizer.data = data;

    // add event listener to toggle custom values depending on category level
    var category_level = Y.one('#id_theme_category_level');
    category_level.on('change', M.block_theme_customizer.toggle_custom_value_form);

    M.block_theme_customizer.toggle_custom_value_form();
};


M.block_theme_customizer.toggle_custom_value_form = function() {
    var data = M.block_theme_customizer.data;
    var category_level = Y.one('#id_theme_category_level');
    var custom_value_form = Y.one('#id_manage_custom_setting_values');

    if (!category_level || !custom_value_form) {
        return false;
    }
};
