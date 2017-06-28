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
 * mod/taskchain/edit/form/helper/records.js
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2015 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_taskchain_edit_form_helper_records = {

    /**
     * define_action_elements
     *
     * @param object Y
     * @param string name
     * @param string field
     * @param string formid
     * @param array actions
     * @return void, but will set properties of this oboject
     */
    setup_action_elements : function(Y, fieldsetid, fieldname, actions) {
        this.fieldsetid = "actionshdr";
        this.fieldname = fieldname;

        // cache the array of actions
        this.actions = actions;

        // cache regexp to detect id of action element
        this.actionregexp = actions.join("|");

        // setup action onclick to hide/show action settings
        for (var i in this.actions) {
            var obj = document.getElementById("id_" + this.fieldname + "_" + this.actions[i]);
            if (obj==null) {
                continue; // invalid id - shouldn't happen !!
            }
            var node = Y.one(obj);
            if (obj.checked) {
                M.mod_taskchain_edit_form_helper_records.toggle_actions(node);
            }
            node.on("click", function(e){
                M.mod_taskchain_edit_form_helper_records.toggle_actions(this);
            });
        }
    },

    /**
     * set_fitem_heights_and_widths
     *
     * @param
     * @return
     * @todo Finish documenting this function
     */
    set_fitem_heights_and_widths : function(Y) {
        var fieldsets = document.getElementsByTagName("FIELDSET")
        if (fieldsets) {
            var hdrFieldsetId = new RegExp("^labels|defaults|selects|(record[0-9]+)$");
            var fcontainerClass = new RegExp("\\b"+"fcontainer"+"\\b");
            var felementClass = new RegExp("\\b"+"felement"+"\\b");
            var fitemClass = new RegExp("\\b"+"fitem"+"\\b");
            var fitemId = new RegExp("^(?:fgroup|fitem)_id_(?:(?:defaultfield|selectfield)_)?([a-z]+).*$");
            var maxWidths = new Array();
            var f_max = fieldsets.length;
            for (var f=0; f<f_max; f++) {
                if (fieldsets[f].id.match(hdrFieldsetId)) {
                    var divs = fieldsets[f].getElementsByTagName("DIV");
                    if (divs) {
                        var maxRight = 0;
                        var maxHeight = 0;
                        var d_max = divs.length;
                        for (var d=0; d<d_max; d++) {
                            if (divs[d].className && divs[d].className.match(fitemClass)) {
                                if (divs[d].offsetLeft && divs[d].offsetWidth) {
                                    maxRight = Math.max(maxRight, divs[d].offsetLeft + divs[d].offsetWidth);
                                }
                                if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {
                                    if (divs[d].style.width) {
                                        divs[d].style.width = null;
                                    }
                                }
                                var col = divs[d].id.replace(fitemId, "$1");
                                var c_max = divs[d].childNodes.length;
                                for (var c=0; c<c_max; c++) {
                                    var child = divs[d].childNodes[c];
                                    if (child.className && child.className.match(felementClass)) {
                                        if (child.offsetHeight) {
                                            maxHeight = Math.max(maxHeight, child.offsetHeight);
                                        }
                                        if (child.offsetWidth) {
                                            if (maxWidths[col]==null) {
                                                maxWidths[col] = 0;
                                            }
                                            maxWidths[col] = Math.max(maxWidths[col], child.offsetWidth);
                                        }
                                    }
                                    var child = null;
                                }
                            }
                        }
                        for (var d=0; d<d_max; d++) {
                            if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {
                                if (divs[d].className && divs[d].className.match(fitemClass)) {
                                    divs[d].style.height = maxHeight + "px";
                                }
                            }
                        }
                        if (maxRight) {
                            fieldsets[f].style.width = (maxRight - fieldsets[f].offsetLeft) + "px";
                        }
                     }
                     divs = null;
                }
            }
            for (var f=0; f<f_max; f++) {
                if (fieldsets[f].id.match(hdrFieldsetId)) {
                    var divs = fieldsets[f].getElementsByTagName("DIV");
                    if (divs) {
                        var d_max = divs.length;
                        for (var d=0; d<d_max; d++) {
                            if (divs[d].parentNode && divs[d].parentNode.className && divs[d].parentNode.className.match(fcontainerClass)) {
                                var col = divs[d].id.replace(fitemId, "$1");
                                if (col) {
                                    if (maxWidths[col] && maxWidths[col] != divs[d].offsetWidth) {
                                        divs[d].style.width = maxWidths[col] + "px";
                                    }
                                }
                            }
                        }
                     }
                     divs = null;
                }
            }
            hdrFieldsetId = null;
            fcontainerClass = null;
            felementClass = null;
            fitemClass = null;
            fitemId = null;
        }
        fieldsets = null;
    },

    /**
     * set_bottom_borders
     *
     * @param
     * @return
     * @todo Finish documenting this function
     */
    set_bottom_borders : function(Y) {
        var obj = null;
        var divs = null;
        var d_max = 0;
        if (obj = document.getElementById(this.fieldsetid)) {
            if (divs = obj.getElementsByTagName("DIV")) {
                d_max = divs.length;
            }
        }
        var targetid1 = new RegExp("^(fitem|fgroup)_id_" + this.fieldname + "_(" + this.actionregexp + ")$");
        for (var d=0; d<d_max; d++) {
            var node = null;
            var m = divs[d].id.match(targetid1);
            if (m && m.length) {
                node = divs[d];
                var targetid2 = new RegExp("^(fitem|fgroup)_id_" + m[2]);
                while (node.nextElementSibling && node.nextElementSibling.id.match(targetid2)) {
                   node = node.nextElementSibling;
                }
            }
            if (node) {
                if (node.id.match(targetid1)) {
                    // action element - do nothing
                } else {
                    node.style.borderBottomColor = "#333333";
                    node.style.borderBottomStyle = "solid";
                    node.style.borderBottomWidth = "1px";
                    node.style.paddingBottom = "6px";
                }
            }
            node = null;
        }
        targetid = null;
        divs = null;
        obj = null;
    },

    /**
     * toggle_actions
     *
     * @param
     * @return
     * @todo Finish documenting this function
     */
    toggle_actions : function(node) {

        if (node==null) {
            return; // shouldn't happen !!
        }

        var id = node.get("id");
        var name = node.get("name");

        if (node && id && name) {
            var targetid1 = new RegExp("^(fitem|fgroup)_id_(" + this.actionregexp + ")");
            var targetid2 = new RegExp("^(fitem|fgroup)_id_" + id.substr(4 + name.length));
        } else {
            var targetid1 = "";
            var targetid2 = "";
        }

        var divs = null;
        var d_max = 0;
        if (targetid1 && targetid2) {
            var fieldset = document.getElementById(this.fieldsetid);
            if (fieldset) {
                if (divs = fieldset.getElementsByTagName("DIV")) {
                    d_max = divs.length;
                }
            }
            fieldset = null;
        }
        for (var d=0; d<d_max; d++) {
            if (divs[d].id.match(targetid1)) {
                if (divs[d].id.match(targetid2)) {
                    divs[d].style.display = "";
                } else {
                    divs[d].style.display = "none";
                }
            }
        }
        divs = null;
        return true;
    }
};
