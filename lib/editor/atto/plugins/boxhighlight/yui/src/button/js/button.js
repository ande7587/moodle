/*
 * @package    atto_boxhighlight
 * @author     Joseph Inhofer <jinhofer@umn.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Based on atto_noautolink by Andrew Davis
 */

/**
 * @module moodle-atto_boxhighlight-button
 */

/**
 * Atto text editor boxhighlight plugin.
 *
 * @namespace M.atto_boxhighlight
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_boxhighlight').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {
        this.addButton({
            icon: 'e/Option1',
            iconComponent: 'atto_boxhighlight',
            callback: this._boxhighlight,
            tags: '.boxhighlight'
        });
    },

    /**
     * Add Box Highlight to the selected region.
     *
     * @method _boxhighlight
     * @param {EventFacade} e
     * @private
     */
    _boxhighlight: function() {
        // Toggle inline selection class
        this.get('host').toggleInlineSelectionClass(['boxhighlight']);
    }
});
