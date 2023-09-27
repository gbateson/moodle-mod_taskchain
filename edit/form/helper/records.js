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
     * fix_css_classes
     *
     * @param object Y
     * @return void, but will set properties of this object
     */
    fix_css_classes : function(Y) {

        // add "fitem_xxx" id in Boost theme e.g. fitem_id_defaultrecord_1
        var divs = document.querySelectorAll("div.form-group:not([id])");
        for (var d in divs) {
            if (divs[d].querySelector) {
                var input = divs[d].querySelector("input[id]");
                if (input) {
                    divs[d].id = "fitem_" + input.id;
                }
            }
        }

        // add CSS class to DIVs in Boost
        var divs = document.querySelectorAll("div.form-group");
        for (var d in divs) {
            if (divs[d].id) {
                var id = divs[d].id;
                switch (true) {
                    case (id=="fitem_id_applydefaults_selectedtasks"):
                    case (id=="fitem_id_applydefaults_filteredtasks"):
                        divs[d].className += " applydefaults";
                        break;
                    case (id.substr(0, 23)=="fitem_id_recordsaction_"):
                        // e.g. fitem_id_recordsaction_addtasks
                        divs[d].className += " recordsaction";
                        break;
                    case (id.substr(0, 9)=="fitem_id_"):
                        // e.g. fitem_id_recordsaction_addtasks
                        divs[d].className += " " + id.substr(9).replace("_start", "");
                        break;
                    case (id.substr(0, 10)=="fgroup_id_"):
                        // e.g. fgroup_id_movetasksafter_elements
                        divs[d].className += " " + id.substr(10).replace("_elements", "");
                        break;
                }
            }
        }

        // add "fitemtitle" class in Boost theme
        var divs = document.querySelectorAll("div.col-md-3");
        for (var d in divs) {
            if (divs[d].className) {
                divs[d].className += " fitemtitle";
            }
        }
    },

    /**
     * setup_selectall_checkboxes
     *
     * @param object Y
     * @param string id "id_columnlistid"
     * @return void, but will set event handlers on target checkbox
     */
    setup_columnlist : function(Y, id) {
        var list = document.getElementById(id);
        if (list) {
            Y.one(list).on("change", function(e){
                window.onbeforeunload = null;
                this.get("form").submit();
            });
        }
        var btn = document.getElementById(id + "submit");
        if (btn) {
            btn.style.setProperty("display", "none");
        }
    },

    /**
     * setup_selectall
     *
     * @param object Y
     * @param string id "id_selectfield_all" or "id_selectrecord_all"
     * @return void, but will set event handlers on target checkbox
     */
    setup_selectall : function(Y, id) {
        var selectall = document.getElementById(id);
        if (selectall) {
            Y.one(selectall).on("change", function(e){
                var n = this.get("name").substr(0, this.get("name").lastIndexOf("_"));
                var s = "input[type=checkbox][name^=" + n + "]:not([name$=_all])";
                document.querySelectorAll(s).forEach(cb => cb.checked = this.get("checked"));
            });
        }
    },

    /**
     * define_action_elements
     *
     * @param object Y
     * @param string name
     * @param string field
     * @param string formid
     * @param array actions
     * @return void, but will set properties of this object
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

        // add "id" in Boost theme e.g. fitem_id_defaultrecord_1
        document.querySelectorAll("div.form-group:not([id])").forEach(function(div){
            var input = div.querySelector("input[id]");
            if (input) {
                div.setAttribute("id", "fitem_" + input.id);
            }
        });

        var fitemId = new RegExp("^(?:fgroup|fitem)_id_(?:(?:defaultfield|selectfield)_)?([a-z]+).*$");

        var maxRight = 0;
        var maxWidths = new Array();

        var s = "fieldset[id^=labels],"
              + "fieldset[id^=defaults],"
              + "fieldset[id^=selects],"
              + "fieldset[id^=record]";
        document.querySelectorAll(s).forEach(function(fieldset){
            fieldset.querySelectorAll("div.fcontainer").forEach(function(fcontainer){
                fcontainer.style.setProperty("width", "unset");
            });
            var maxHeight = 0;
            fieldset.querySelectorAll("div.fitem").forEach(function(fitem){
                maxRight = Math.max(maxRight, fitem.offsetLeft + fitem.offsetWidth);
                var colname = fitem.id.replace(fitemId, "$1");
                fitem.querySelectorAll(".felement").forEach(function(felement){
                    maxHeight = Math.max(maxHeight, felement.offsetHeight);
                    if (maxWidths[colname] === undefined) {
                        maxWidths[colname] = felement.offsetWidth;
                    } else {
                        maxWidths[colname] = Math.max(maxWidths[colname], felement.offsetWidth);
                    }
                });
            });
            fieldset.querySelectorAll("div.fitem").forEach(function(fitem){
                fitem.style.setProperty("min-height", maxHeight + "px");
            });
        });

        document.querySelectorAll(s).forEach(function(fieldset){
            if (maxRight) {
                const w = (maxRight - fieldset.offsetLeft);
                fieldset.style.setProperty("width", w + "px");
            }
            fieldset.querySelectorAll("div.fitem").forEach(function(fitem){
                var colname = fitem.id.replace(fitemId, "$1");
                fitem.style.setProperty("min-width", maxWidths[colname] + "px");
            });
        });
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
