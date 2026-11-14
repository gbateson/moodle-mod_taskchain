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
 * mod/taskchain/edit/form/helper.js.php
 *
 * @package    mod_taskchain
 * @copyright  2013 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

if (window.TC==null) {
    window.TC  = new Object();
    TC.error   = 'Error';
    TC.wwwroot = location.href.replace(new RegExp('^(.*?)/mod/taskchain/.*$'), '$1');
    TC.loading = 'loading';
}

TC.get_formitem = function(id) {

    if (! id) {
        return null;
    }

    var obj = document.getElementById('fitem_id_' + id);
    if (obj==null) {
        // Moodle >= 3.1 removes "[" and "]" from element ids
        id = id.replace('[', '_').replace(']', '');
        obj = document.getElementById('fitem_id_' + id);
    }
    if (! obj) {
        return null;
    }

    obj = obj.firstChild;
    while (obj) {
        if (obj.nodeType==1) { // ELEMENT_NODE
            if (obj.tagName=='DIV') {
                if (obj.className.match(new RegExp('\\bfelement\\b'))) {
                    if (obj.className.match(new RegExp('\\bfstatic\\b'))) {
                        return obj;
                    }
                }
            }
        }
        obj = obj.nextSibling
    }

    return null;
}

TC.get_formdata = function(formid) {

    var obj = TC.get_formitem(formid);
    if (! obj) {
        return null;
    }

    var formdata = null;

    var tags = new Array('input', 'select');
    var i_max = tags.length;

    for (var i=0; i<i_max; i++) {
        var tag = tags[i];

        var elements = obj.getElementsByTagName(tag);
        if (! elements) {
            continue;
        }

        var ii_max = elements.length;
        for (var ii=0; ii<ii_max; ii++) {

            var name = elements[ii].name;
            if (! name) {
                continue;
            }

            var value = null;
            switch (tag) {
                case 'input':
                    var t = elements[ii].type;
                    if (t=='text' || t=='hidden') {
                        value = elements[ii].value;
                    }
                    if ((t=='checkbox' || t=='radio') && elements[ii].checked) {
                        value = elements[ii].value;
                    }
                    break;
                case 'select':
                    var value = elements[ii].options[elements[ii].selectedIndex].value;
                    break;
            }

            if (value || value==='0' || value==='') {
                if (formdata===null) {
                    var formdata = new FormData();
                }
                formdata.append(name, value);
            }
        }
    }

    return formdata;
}

TC.response = function(url, targetids) {
    var updated = false;
    if (TC.xmlhttp.readyState==4) {

        if (typeof(targetids)=='string') {
            targetids = targetids.split(',');
        }

        var i_max = targetids.length;
        for (var i=0; i<i_max; i++) {
            var obj = TC.get_formitem(targetids[i]);
            if (obj) {
                if (TC.xmlhttp.status==200) {
                    obj.innerHTML = TC.xmlhttp.responseText;
                } else {
                    obj.innerHTML = (i ? '' : TC.error + ': ' + TC.xmlhttp.status);
                }
                updated = true;
            }
            obj = null;
        }
    }
    if (updated) {
        if (window.M && M.mod_taskchain_edit_form_helper_records) {
            M.mod_taskchain_edit_form_helper_records.set_fitem_heights_and_widths();
        } else if (window.set_fitem_heights_and_widths) {
            set_fitem_heights_and_widths();
        }
    }
}

TC.request = function(url, targetids, formid) {
    url = url.replace(new RegExp('&amp;', 'g'), '&');

    if (typeof(targetids)=='string') {
        targetids = targetids.split(',');
    }

    var formdata = TC.get_formdata(formid);

    var i_max = targetids.length;
    for (var i=0; i<i_max; i++) {
        var obj = TC.get_formitem(targetids[i]);
        if (obj) {
            obj.innerHTML = (i ? '' : '<img src="'+TC.wwwroot+'/pix/i/ajaxloader.gif" alt="'+TC.loading+' ..." />');
            obj = null;
        }
    }

    TC.xmlhttp = false;
    if (window.XMLHttpRequest) {
        // modern browser (incl. IE7+)
        TC.xmlhttp = new XMLHttpRequest();
    } else if (window.ActiveXObject) { // IE6, IE5
        TC.xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    if (TC.xmlhttp) {
        TC.xmlhttp.onreadystatechange = function() {
            TC.response(url, targetids);
        }
        if (formdata) {
            var method = 'POST';
        } else {
            var method = 'GET';
        }
        TC.xmlhttp.open(method, url, true);
        TC.xmlhttp.send(formdata);
    }
}