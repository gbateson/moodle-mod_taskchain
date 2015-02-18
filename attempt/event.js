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
 * mod/taskchain/attempt/event.js
 *
 * @package   mod-taskchain
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

///////////////////////////////////////////
// cross-platform/device event API
///////////////////////////////////////////

/**
 * HP_fix_function
 *
 * @param  mixed fnc : the function (object) or code (string)
 * @return function
 */
function HP_fix_function(fnc) {
    if (typeof(fnc)=='function') {
        return fnc;
    } else {
        return new Function('event', fnc);
    }
}

/**
 * HP_fix_event
 *
 * @param  string evt : the name of the event (without leading 'on')
 * @param  object obj : the destination object for evt
 * @return array evts : names of events which obj can handle
 */
function HP_fix_event(evt, obj) {
    var i = 0;
    var evts = new Array();

    switch (evt) {
        case 'click'      : if ('ontap'        in obj) evts[i++] = 'tap';        break;
        case 'mousedown'  : if ('ontouchstart' in obj) evts[i++] = 'touchstart'; break;
        case 'mousemove'  : if ('ontouchmove'  in obj) evts[i++] = 'touchmove';  break;
        case 'mouseup'    : if ('ontouchend'   in obj) evts[i++] = 'touchend';   break;
        case 'tap'        : if ('onclick'      in obj) evts[i++] = 'click';      break;
        case 'touchend'   : if ('onmouseup'    in obj) evts[i++] = 'mouseup';    break;
        case 'touchmove'  : if ('onmousemove'  in obj) evts[i++] = 'mousemove';  break;
        case 'touchstart' : if ('onmousedown'  in obj) evts[i++] = 'mousedown';  break;
    }

    var onevent = 'on' + evt;
    if (onevent in obj) {
        evts[i++] = evt;
    }

    return evts;
}

/**
 * HP_add_listener
 *
 * @param  object obj : an HTML element
 * @param  string evt : the name of the event (without leading 'on')
 * @param  string fnc : the name of the event handler funtion
 * @param  boolean useCapture (optional, default = false)
 * @return void, but may add event handler to DOM
 */
function HP_add_listener(obj, evt, fnc, useCapture) {

    // convert fnc to Function, if necessary
    fnc = HP_fix_function(fnc);

    // convert mouse <=> touch events
    var evts = HP_fix_event(evt, obj);

    // add event handler(s)
    var i_max = evts.length;
    for (var i=0; i<i_max; i++) {
        evt = evts[i];

        // transfer object's old event handler (if any)
        var onevent = 'on' + evt;
        if (obj[onevent]) {
            var old_fnc = obj[onevent];
            obj[onevent] = null;
            HP_add_listener(obj, evt, old_fnc, useCapture);
        }

        // add new event handler
        if (obj.addEventListener) {
            obj.addEventListener(evt, fnc, (useCapture ? true : false));
        } else if (obj.attachEvent) {
            obj.attachEvent(onevent, fnc);
        } else { // old browser NS4, IE5 ...
            if (! obj.evts) {
                obj.evts = new Array();
            }
            if (obj.evts && ! obj.evts[onevent]) {
                obj.evts[onevent] = new Array();
            }
            if (obj.evts && obj.evts[onevent] && ! obj.evts[onevent]) {
                obj.evts[onevent][obj.evts[onevent].length] = fnc;
                obj[onevent] = new Function('HP_handle_event(this, \"'+onevent+'\")');
            }
        }
    }
}

/**
 * HP_remove_listener
 *
 * @param  object obj : an HTML element
 * @param  string evt : the name of the event (without leading 'on')
 * @param  string fnc : the name of the event handler funtion
 * @param  boolean useCapture (optional, default = false)
 * @return void, but may remove event handler to DOM
 */
function HP_remove_listener(obj, evt, fnc, useCapture) {

    // convert fnc to Function, if necessary
    fnc = HP_fix_function(fnc);

    // convert mouse <=> touch events
    var evts = HP_fix_event(evt, obj);

    // remove event handler(s)
    var i_max = evts.length;
    for (var i=0; i<i_max; i++) {
        evt = evts[i];

        var onevent = 'on' + evt;
        if (obj.removeEventListener) {
            obj.removeEventListener(evt, fnc, (useCapture ? true : false));
        } else if (obj.attachEvent) {
            obj.detachEvent(onevent, fnc);
        } else if (obj.evts && obj.evts[onevent]) {
            var i_max = obj.evts[onevent].length;
            for (var i=(i_max - 1); i>=0; i--) {
                if (obj.evts[onevent][i]==fnc) {
                    obj.evts[onevent].splice(i, 1);
                }
            }
        }
    }
}

/**
 * HP_handle_event
 *
 * @param  object obj : an HTML element
 * @param  string onevent : the name of the event
 * @return void, but may execute event handler
 */
function HP_handle_event(obj, onevent) {
    if (obj.evts[onevent]) {
        var i_max = obj.evts[onevent].length
        for (var i=0; i<i_max; i++) {
            obj.evts[onevent][i]();
        }
    }
}

/**
 * HP_disable_event
 *
 * @param  object evt : an javascript event object
 * @return may return false (older browsers)
 */
function HP_disable_event(evt) {
    if (evt==null) {
        evt = window.event;
    }
    if (evt.preventDefault) {
        evt.preventDefault();
    } else { // IE <= 8
        evt.returnValue = false;
    }
    return false;
}

///////////////////////////////////////////
// DOM extraction utilities
///////////////////////////////////////////

/**
 * GetTextFromNodeN
 *
 * @param xxx obj
 * @param xxx className
 * @param xxx n
 * @return xxx
 */
function GetTextFromNodeN(obj, className, n) {
    // returns the text under the nth node of obj with the target class name
    var txt = '';
    if (obj && className) {
        if (typeof(n)=='undefined') {
            n = 0;
        }
        var nodes = GetNodesByClassName(obj, className);
        if (n<nodes.length) {
            txt += GetTextFromNode(nodes[n]);
        }
    }
    return txt;
};

/**
 * GetNodesByClassName
 *
 * @param xxx obj
 * @param xxx className
 * @return xxx
 */
function GetNodesByClassName(obj, className) {
    // returns an array of nodes with the target classname
    var nodes = new Array();
    if (obj) {
        if (className && obj.className==className) {
            nodes.push(obj);
        } else if (obj.childNodes) {
            for (var i=0; i<obj.childNodes.length; i++) {
                nodes = nodes.concat(GetNodesByClassName(obj.childNodes[i], className));
            }
        }
    }
    return nodes;
};

/**
 * GetTextFromNode
 *
 * @param xxx obj
 * @return xxx
 */
function GetTextFromNode(obj) {
    // return text in (and under) a single DOM node
    var txt = '';
    if (obj) {
        if (obj.nodeType==3) {
            txt = obj.nodeValue + ' ';
        }
        if (obj.childNodes) {
            for (var i=0; i<obj.childNodes.length; i++) {
                txt += GetTextFromNode(obj.childNodes[i]);
            }
        }
    }
    return txt;
};

///////////////////////////////////////////
// object / array  manipulation utilities
///////////////////////////////////////////

/**
 * print_object
 *
 * @param xxx obj
 * @param xxx name
 * @param xxx tabs
 * @return xxx
 */
function print_object(obj, name, tabs) {
    var s = '';
    if (! tabs) {
        tabs = 0;
    }
    for (var i=0; i<tabs; i++) {
        s += '    '; // 1 tab  = 4 spaces
    }
    if (name != null) {
        s += name + ' ';
    }
    var t = typeof(obj);
    s += ' ' + t.toUpperCase() + ' : ';
    switch (t.toLowerCase()) {
        case 'boolean':
        case 'number':
        case 'string':
            s += obj + '\n';
            break;
        case 'object': // or array
            if (obj && obj.nodeType) {
                s += 'html node' + '\n';
            } else {
                s += '\n';
                var keys = object_keys(obj); // properties and methods
                var x;
                while (x = keys.shift()) {
                    s += print_object(obj[x], '['+x+']', tabs+1);
                }
            }
            break;
        case 'function':
            var f = obj.toString();
            s += f.substring(9, f.indexOf('{', 9)) + '\n';
            break;
        default:
            s += 'unrecognized object type:' + t + '\n';
    }
    return s;
};

/**
 * print_object_keys
 *
 * @param xxx obj
 * @param xxx flag
 * @return xxx
 */
function print_object_keys(obj, flag) {
    var s = '';
    var keys = object_keys(obj, flag);
    var x;
    while (x = keys.shift()) {
        s += ', ' + x;
    }
    return s.substring(2);
};

/**
 * object_keys
 *
 * @param xxx obj
 * @param xxx flag
 * @return xxx
 */
function object_keys(obj, flag) {
    // flag
    //     0 : return properties and methods (default)
    //     1 : return properties only (i.e. skip methods)
    //     2 : return methods only (i.e. skip properties)

    var keys = new Array();

    // check obj is indeed an object
    if (obj && typeof(obj)=='object') {

        if (typeof(flag)=='undefined') {
            // default flag value
            flag = 0;
        }

        // numeric keys
        if (obj.length) {
            var i_max = obj.length;
        } else {
            var i_max = 0;
        }
        for (var i=0; i<i_max; i++) {
            var t = typeof(obj[i]);
            if (t=='undefined') {
                // skip null values
                continue;
            }
            if (flag==1 && t=='function') {
                // skip methods
                continue;
            }
            if (flag==2 && t!='function') {
                // skip properties
                continue;
            }
            keys.push('' + i);
        }

        // non-numeric keys
        var numeric_key = new RegExp('^\\d+$');
        for (var x in obj) {
            var t = typeof(x);
            if (t=='number' || (t=='string' && x.match(numeric_key))) {
                // skip numeric keys (IE)
                continue;
            }
            var t = typeof(obj[x]);
            if (t=='undefined') {
                // skip null values
                continue;
            }
            if (flag==1 && t=='function') {
                // skip methods
                continue;
            }
            if (flag==2 && t!='function') {
                // skip properties
                continue;
            }
            keys.push('' + x);
        } // end for x in obj

    } // end if obj
    return keys;
};

/**
 * object_destroy
 *
 * @param xxx obj
 */
function object_destroy(obj) {
    // check this is an object (but is not a DOM node)
    if (obj && typeof(obj)=='object' && !obj.nodeType) {
        var keys = object_keys(obj); // properties and methods
        var x;
        while (x = keys.shift()) {
            if (typeof(obj[x])=='object') {
                object_destroy(obj[x]);
            }
        }
        obj = null;
    }
};

///////////////////////////////////////////
// string formatting utilities
///////////////////////////////////////////

/**
 * pad
 *
 * @param xxx i
 * @param xxx l
 * @return xxx
 */
function pad(i, l) {
    var s = (i + '');
    while (s.length<l) {
        s = '0' + s;
    }
    return s;
};

/**
 * trim
 *
 * @param xxx s
 * @return xxx
 */
function trim(s) {
    switch (typeof(s)) {
        case 'string'   : return s.replace(new RegExp('^\\s+|\\s+$', 'g'), '');
        case 'undefined': return '';
        default         : return s;
    }
};
