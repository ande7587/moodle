/**
 * @package     atto_fontfamily
 * @copyright   2015 Joseph Inhofer <jinhofer@umn.edu>
 * @license     http://www.gnu.com/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle_atto_fontfamily-button
 */

/**
 * Atto text editor fontfamily plugin
 *
 * @namespace   M.atto_fontfamily
 * @class       button
 * @extends     M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_fontfamily').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function(param) {
        var items = [];
        var families = param.options.split(';');
        if (families.toString() === '') {
            families = ["Trebuchet|'Trebuchet MS', Verdana, Arial, Helvetica, sans-serif",
                        "Arial|arial, helvetica, sans-serif",
                        "Courier New|'courier new', courier, monospace",
                        "Georgia|georgia, 'times new roman', times, serif",
                        "Tahoma|tahoma, arial, helvetica, sans-serif",
                        "Times New Roman|'times new roman', times, serif",
                        "Verdana|verdana, arial, helvetica, sans-serif",
                        "Impact|impact",
                        "Wingdings|wingdings"];
        }
        Y.Array.each(families, function(family) {
            var splitFam = family.split('|'),
                name = splitFam[0],
                face;
            if(splitFam[1]) {
                face = splitFam[1];
            }
            else {
                face = name;
            }
            if(face !== 'wingdings') {
                items.push({
                    text: '<font face="' + face + '">' + name + '</font>',
                    callbackArgs: face
                });
            }
            else {
                items.push({
                    text: name,
                    callbackArgs: face
                });
            }
        });
        if(items !== []) {
            this.addToolbarMenu({
                icon: 'e/fontfamily',
                iconComponent: 'atto_fontfamily',
                globalItemConfig: {
                    callback: this._changeStyle
                },
                items: items
            });
        }
    },

    _changeStyle: function(e, face) {
        this.get('host').formatSelectionInlineStyle({
            fontFamily: face
        });

        this.markUpdated();
    }
});
