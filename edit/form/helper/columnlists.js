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
 * @package    mod_taskchain
 * @copyright  2015 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_taskchain_edit_form_helper_columnlists = {

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

                // Get URL of this page.
                let url = new URL(document.location);

                // Ensure hidden values are appended to the URL.
                document.querySelectorAll("form > div > input[type=hidden]:not([name^='_qf__'])").forEach(input => {
                    url.searchParams.set(input.name, input.value);
                });

                // Override value for "columnlistid"
                let i = this.get('selectedIndex');
                let item = this.get('options').item(i);
                url.searchParams.set('columnlistid', item.get('text'));

                // Disable event handler for "onbeforeunload"
                window.onbeforeunload = null;

                // Load new page.
                document.location = url.toString();
            });
        }
    },

    onclick_command_all : function(evt) {
        this.closest("fieldset").querySelectorAll(".fcontainer .checkbox input").forEach(cb => {
            cb.checked = true;
        });
    },
    onclick_command_reset : function(evt) {
        this.closest("fieldset").querySelectorAll(".fcontainer .checkbox input").forEach(cb => {
            cb.checked = cb.defaultChecked;
        });
    },
    onclick_command_none : function(evt) {
        this.closest("fieldset").querySelectorAll(".fcontainer .checkbox input").forEach(cb => {
            cb.checked = false;
        });
    },

    /**
     * setup_selectall
     *
     * @param object Y
     * @param string id "id_selectfield_all" or "id_selectrecord_all"
     * @return void, but will set event handlers on target checkbox
     */
    setup_selectcommands : function(Y, strings) {

        document.querySelectorAll("form > fieldset[id^='id_'][id$='hdr'] > div:not(.fcontainer)").forEach(function(hdr){

            // get id of parent fieldset
            let id = hdr.parentNode.id;

            // remove leading "id_" and trailing "hdr" to get section name (e.g. entrypage)
            let sectionname = id.substring(3, id.length - 3);

            let commands = Object.assign(document.createElement("div"), {
                "id": sectionname + "commands",
                "className": "ml-2 align-self-end"
            });

            Object.keys(strings).forEach(function(strname){
                let command = Object.assign(document.createElement("span"), {
                    "id": sectionname + strname,
                    "className": "selectcommand selectcommand" + strname,
                    "textContent": strings[strname],
                    "onclick": M.mod_taskchain_edit_form_helper_columnlists["onclick_command_" + strname]
                });
                if (commands.childNodes.length) {
                    commands.appendChild(document.createTextNode(' / '));
                }
                commands.appendChild(command);
            });
            hdr.appendChild(commands);
        });
    }
};
