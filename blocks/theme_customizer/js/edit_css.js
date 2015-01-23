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
 * @copyright  2012 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_theme_customizer = {
    css_files : []
};


/**
 * initialize the edit CSS page
 * @param {object} Y, the YUI instance
 * @param {object} data, list of data from server
 *
 * use M.util.get_string() for language strings from server side
 */
M.block_theme_customizer.init = function(Y, data) {
    var self = this;
    this.Y = Y;        // keep a ref to YUI instance

    var ctn = Y.one('#block_theme_customizer_css_filter_ctn');

    // add the "show all" and "hide all" links
    var link_ctn = Y.Node.create('<div id="block_theme_customizer_css_filter_link_ctn"></div>');

    var show_link = Y.Node.create('<a href="javascript:void(0);">' +
                                  M.util.get_string('show_all', 'block_theme_customizer') + '</a>');
    link_ctn.append(show_link);

    var hide_link = Y.Node.create('<a href="javascript:void(0);">' +
                                  M.util.get_string('hide_all', 'block_theme_customizer') + '</a>');
    link_ctn.append(hide_link);

    ctn.append(link_ctn);

    show_link.on('click', function(e) {self.toggle_css('_ALL_', true);});
    hide_link.on('click', function(e) {self.toggle_css('_ALL_', false);});

    // iterate through the CSS file and add the checkboxes
    self.css_files = [];
    for (var css_file in data['css_files']) {
        var filter_ctn = Y.Node.create('<div class="filter_ctn"></div>');

        var css_name = css_file.substring(0, css_file.indexOf('.'));
        var disabled = data['css_files'][css_file] ? 'checked' : 'disabled' ;
        var checkbox = Y.Node.create('<input type="checkbox" id="block_theme_customizer_filter__' +
                                     css_name + '" ' + disabled + '/>');

        filter_ctn.append(checkbox);
        filter_ctn.append(Y.Node.create('<span class="filter_label ' + disabled + '">' + css_file + '</span>'));

        ctn.append(filter_ctn);

        checkbox.on('change', function(e) {
            var id  = e.currentTarget.getAttribute('id');
            self.toggle_css(id.substr(id.indexOf('__') + 2), e.currentTarget.get('checked'));
        });

        if (data['css_files'][css_file]) {
            self.css_files.push(css_name);
        }
    }
}


/**
 * toggle the rows of specified CSS file
 * @param {string} css_name, name of the CSS file (exclude extension),
 *                           set to _ALL_ to make changes to all files
 * @param {bool} visible, whether to show or hide the corresponding rows
 */
M.block_theme_customizer.toggle_css = function(css_name, visible) {
    var Y = this.Y;

    if (css_name == '_ALL_') {
        // set the checkboxes
        for (var i = 0; i < this.css_files.length; i++) {
            Y.one('#block_theme_customizer_filter__' + this.css_files[i]).set('checked', visible);
        }

        // set display of all the rows
        var rows = Y.Selector.query('#custom_css_entries tr');
        for (var i = 0; i < rows.length; i++) {
            Y.one(rows[i]).setStyle('display', visible ? 'table-row' : 'none');
        }
    }
    else {
        var rows = Y.Selector.query('tr.theme_customizer__' + css_name);

        for (var i = 0; i < rows.length; i++) {
            Y.one(rows[i]).setStyle('display', visible ? 'table-row' : 'none');
        }
    }
}

