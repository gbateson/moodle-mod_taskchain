//<![CDATA[

/**
 * implement CSS selectors with JavaScript
 *
 * especially the pseudo selectors :nth-child and :not
 */

/**
 * get_elements_by_css_selector_tag
 *
 * @param array m
 * @param array elements
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_tag(m, elements) {
    if (m && m[1]) {
        var x = new Array();
        var i_max = elements.length;
        for (var i=0; i<i_max; i++) {
            var tags = elements[i].getElementsByTagName(m[1]);
            for (var ii=0; ii<tags.length; ii++) {
                x.push(tags[ii]);
            }
            tags = null;
        }
        elements = x;
        x = null;
    }
    return elements;
}

/**
 * get_elements_by_css_selector_id
 *
 * @param array m
 * @param array elements
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_id(m, elements) {
    if (m && m[1]) {
        var x = new Array();
        var i_max = elements.length;
        for (var i=0; i<i_max; i++) {
            // Chrome FIELDSET has no getElementById() method
            if (elements[i].getElementById) {
                x.push(elements[i].getElementById(m[1]));
            } else {
                x.push(document.getElementById(m[1]));
            }
        }
        elements = x;
        x = null;
    }
    return elements;
}

/**
 * get_elements_by_css_selector_class
 *
 * @param array m
 * @param array elements
 * @param string TAGNAME
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_class(m, elements, TAGNAME) {
    if (m && m[1]) {
        var x = new Array();

        var regexp_dot = new RegExp("\\.", "g");
        var targetClassName = new RegExp("\\b" + m[1] + "\\b");

        var i_max = elements.length;
        for (var i=0; i<i_max; i++) {

            if (elements[i].className.match(targetClassName)) {
                x.push(elements[i]);
            }

            var tags = elements[i].getElementsByClassName(m[1].replace(regexp_dot, " "));
            for (var ii=0; ii<tags.length; ii++) {
                if (TAGNAME=="" || TAGNAME==tags[ii].tagName) {
                    x.push(tags[ii]);
                }
            }
            tags = null;
        }
        elements = x;
        x = null;
    }
    return elements;
}

/**
 * get_elements_by_css_selector_pseudo_nthchild_params
 *
 * @param string n
 * @return array (n => '', multiplier => '' offset => '')
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_pseudo_nthchild_params(n) {
    if (n=="odd") {
        return new Array("n"=>2, "multiplier"=>1, "offset"=>2);
    }

    if (n=="even") {
        return new Array("n"=>2, "multiplier"=>0, "offset"=>2);
    }

    // numeric n
    var r = new RegExp("^[0-9]+$");
    var m = r.exec(n);
    if (m && m.length) {
        return new Array("n"=>parseInt(n), "multiplier"=>0, "offset"=>0);
    }

    // multiplier x n + offset
    var r = new RegExp("^([0-9]+)n(\\+)([0-9]+)$");
    var m = r.exec(n);
    if (m && m.length) {
        return new Array("n"=>1, "multiplier"=>parseInt(m[3]), "offset"=>parseInt(m[1]));
    }

    // offset + multiplier x n
    var r = new RegExp("^([0-9]+)(\\+)([0-9]+)n$");
    var m = r.exec(n);
    if (m && m.length) {
        return new Array("n"=>1, "multiplier"=>parseInt(m[1]), "offset"=>parseInt(m[3]));
    }

    // unrecognized "n" - shouldn't happen
    return new Array("n"=>0, "multiplier"=>0, "offset"=>0);
}

/**
 * get_elements_by_css_selector_pseudo_nthchild
 *
 * @param array x
 * @param object element
 * @param array elements
 * @param string n
 * @return array x
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_pseudo_nthchild(x, element, n) {

    var params = get_elements_by_css_selector_pseudo_nthchild_params(n);

    // the quickest way to determine "i", the element index, is to
    // count the number of text nodes (=1) in previous sibling nodes
    var i = (0 - params["offset"]);
    var child = element;
    while (child) {
        if (child.nodeType==1) {
            i++;
        }
        child = child.previousSibling;
    }
    child = null;

    if (i < 0) {
        var ok = false;
    } else if (params["multiplier"]==0) {
        var ok = (i==params["n"]);
    } else {
        var ok = ((i % params["multiplier"])==0);
    }
    if (ok) {
        x.push(element);
    }
    return x;
}

/**
 * get_elements_by_css_selector_pseudo_not
 *
 * @param array x
 * @param object element
 * @param string not
 * @return array x
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_pseudo_not(x, element, not) {
    var matched = false;

    var regexp_space  = new RegExp("\\s+");
    var nots = not.split(regexp_space);

    var i_max = nots.length;
    for (var i=0; i<i_max; i++) {

        switch (true) {
            case nots[i].substr(0, 1)=="#":
                if (element.id==nots[i].substr(1)) {
                    matched = true;
                }
                break;

            case nots[i].substr(0, 1)==".":
                if (element.className==nots[i].substr(1)) {
                    matched = true;
                }
                break;
        }
    }
    if (! matched) {
        x.push(element);
    }
    return x;
}

/**
 * get_elements_by_css_selector_pseudo
 *
 * @param array m
 * @param array elements
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector_pseudo(m, elements) {
    if (m && m[1]) {
        var x = new Array();

        var i_max = elements.length;
        for (var i=0; i<i_max; i++) {

            switch (true) {
                case (m[1].substr(0, 9)=="nth-child"):
                    var n = m[1].substring(10, m[1].length-1);
                    x = get_elements_by_css_selector_pseudo_nthchild(x, elements[i], n);
                    break;

                case (m[1].substr(0, 3)=="not"):
                    var not = m[1].substring(4, m[1].length-1);
                    x = get_elements_by_css_selector_pseudo_not(x, elements[i], not);
                    break;

                default:
                    if(!window.gdb)window.gdb=!confirm("Unrecognized pseudo-class: " + m[1] + " ?");
            }
        }

        elements = x;
        x = null;
    }
    return elements;
}

/**
 * get_elements_by_css_selector
 *
 * @param array css_selector
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selector(css_selector) {

    var elements = new Array(document);

    var regexp_tag    = new RegExp("^([a-zA-Z0-9_]+)");
    var regexp_id     = new RegExp("\\#([a-zA-Z0-9_-]+)");
    var regexp_class  = new RegExp("\\.([a-zA-Z0-9._-]+)");
    var regexp_pseudo = new RegExp("\\:([a-zA-Z0-9_-]+(\\([^\\)]*\\)))");

    var i_max = css_selector.length;
    for (var i=0; i<i_max; i++) {

        // restrict search to tag, id and class
        var css = css_selector[i];
        var pos = css.indexOf("(");
        if (pos >= 0) {
            var css = css.substr(0, pos);
        }

        var m = css.match(regexp_tag);
        var TAGNAME = ((m && m[1]) ? m[1].toUpperCase : "");
        elements = get_elements_by_css_selector_tag(m, elements);

        var m = css.match(regexp_id);
        elements = get_elements_by_css_selector_id(m, elements);

        var m = css.match(regexp_class);
        elements = get_elements_by_css_selector_class(m, elements, TAGNAME);

        css = css_selector[i];
        var m = css.match(regexp_pseudo);
        elements = get_elements_by_css_selector_pseudo (m, elements);

        if (elements.length==0) {
            break;
        }

    } // end for i (css_selector)

    return elements;
}

/**
 * get_elements_by_css_selectors
 *
 * @param array css_selectors
 * @return array elements
 * @todo Finish documenting this function
 */
function get_elements_by_css_selectors(css_selectors) {
    var elements = new Array();

    var i_max = css_selectors.length;
    for (var i=0; i<i_max; i++) {

        var x = get_elements_by_css_selector(css_selectors[i]);
        var ii_max = x.length;
        for (ii=0; ii<ii_max; ii++) {
            elements.push(x[ii]);
        }
        x = null;
    }

    return elements;
}

/**
 * parse_css_styles
 *
 * @param string css_styles
 * @return array css_styles
 * @todo Finish documenting this function
 */
function parse_css_styles(css_styles) {
    var regexp_semicolon = new RegExp("\\s*;\\s*");
    var regexp_colon     = new RegExp("\\s*:\\s*");
    css_styles = css_styles.split(regexp_semicolon);

    var i_max = css_styles.length;
    for (var i=0; i<i_max; i++) {
        css_styles[i] = css_styles[i].split(regexp_colon);
    }

    return css_styles;
}

/**
 * parse_css_selector
 *
 * @param string css_selector
 * @return array css_selector
 * @todo Finish documenting this function
 */
function parse_css_selector(css_selector) {
    var regexp_space = new RegExp("\\s+");
    css_selector = css_selector.split(regexp_space);

    var i_max = css_selector.length;
    for (var i=0; i<i_max; i++) {

        // fix brackets in the pseudo classes
        // actually we should count these in case there are two in the array item
        if (css_selector[i].indexOf("(")>=0) {
            for (var ii=i; ii<i_max; ii++) {
                if (css_selector[ii].indexOf(")")>=0) {
                    break;
                }
            }
            ii++;
            var str = css_selector.slice(i, ii).join(" ");
            css_selector.splice(i, ii-i, str);
            i_max = (i_max - (ii - i));
        }
    }

    return css_selector;
}

/**
 * parse_css_selectors
 *
 * @param string css_selectors
 * @return array css_selectors
 * @todo Finish documenting this function
 */
function parse_css_selectors(css_selectors) {
    var regexp_comma = new RegExp("\\s*,\\s*");
    css_selectors = css_selectors.split(regexp_comma);

    var i_max = css_selectors.length;
    for (var i=0; i<i_max; i++) {
        css_selectors[i] = parse_css_selector(css_selectors[i]);
    }

    return css_selectors;
}

/**
 * apply_css_styles
 *
 * @param string css_selectors
 * @param array css_styles
 * @return void, but may update DOM elements
 * @todo Finish documenting this function
 */
function apply_css_styles(css_selectors, css_styles) {

    css_styles = parse_css_styles(css_styles);
    css_selectors = parse_css_selectors(css_selectors);

    var elements = get_elements_by_css_selectors(css_selectors);
    if (elements) {

        var i_max = elements.length;
        for (var i=0; i<i_max; i++) {

            var ii_max = css_styles.length;
            for (var ii=0; ii<ii_max; ii++) {

                var name = css_styles[ii][0];
                var value = css_styles[ii][1];
                elements[i].style[name] = value;
            }
        }
    }
    elements = null;
}

apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #labels $defaults $selects #actionshdr):nth-child(2n+6) ", "background-color: #eeeeaa;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #labels $defaults $selects #actionshdr):nth-child(2n+7) ", "background-color: #ffffee;");

apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(1)," +
                 "#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(2)", "width: 40px;");

apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(3)," +
                 "#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(4)", "width: 50px;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(5)", "width: 180px;");

apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix#selects div.fitem:nth-child(4) fieldset.fgroup", "text-align: right;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix#selects div.fitem:nth-child(4) fieldset.fgroup input", "margin-left: 3px; margin-right: 2px;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(1) div.felement", "text-align: center;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(1) div.felement input", "text-align: center; width: 2.0em;");
apply_css_styles("#page-mod-taskchain-edit-tasks form.mform fieldset.clearfix:not(#filtershdr #actionshdr) div.fitem:nth-child(5) div.felement", "text-align: left;");

/*
var window.cssprefix = "";
with (navigator) switch (true) {
    case (userAgent.indexOf("WebKit")>=0)  : cssprefix = "-webkit-"; break;
    case (userAgent.indexOf("Opera")>=0)   : cssprefix = "-o-";      break;
    case (userAgent.indexOf("MSIE")>=0)    : cssprefix = "-ms-";     break;
    case (userAgent.indexOf("Mozilla")>=0) : cssprefix = "-moz-";    break;
}

if (window.cssprefix) {
    divs[i].style[window.cssprefix + "transition"] = "all 1s ease-in";
}
divs[i].style.transition = "all 1s ease-in";
*/
//]]>
