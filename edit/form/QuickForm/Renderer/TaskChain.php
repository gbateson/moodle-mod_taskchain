<?php

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
 * mod/taskchain/form/QuickForm/Renderer/TaskChain.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * TaskChain MoodleQuickForm renderer
 *
 * A renderer for MoodleQuickForm that allows element specific CSS classes
 *
 * Stylesheet is part of standard theme and should be automatically included.
 *
 * @copyright  2010 Gordon Bateson <gordonbateson@gmail.com>
 *             based on MoodleQuickForm_Renderer by Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class TaskChain_MoodleQuickForm_Renderer extends MoodleQuickForm_Renderer{

    /**
     * Constructor
     */
    function __construct() {
        if (method_exists('MoodleQuickForm_Renderer', '__construct')) {
            parent::__construct();
        } else {
            parent::MoodleQuickForm_Renderer();
        }
    }

    /**
     * renderHeader
     * force header fieldset id to be the default id used in Moodle <= 2.4
     *
     * @param object $header (passed by reference)
     */
    function renderHeader(&$header){
        global $SCRIPT;
        if ($name = $header->getName()) {
            if ($SCRIPT=='/mod/taskchain/edit/tasks.php' || $SCRIPT=='/mod/taskchain/edit/chains.php') {
                // Moodle >= 2.5 adds a "id_" prefix to the header ids
                // but this messages with the TaskChain CSS styles
                // so we use the old "id" that had no prefix
                $header->updateAttributes(array('id' => $name));
            }
        }
        return parent::renderHeader($header);
    }

    /**
     * renderElement
     *
     * @param object $element
     * @param mixed $required
     * @param mixed $error
     */
    function renderElement(&$element, $required, $error){
        if (method_exists($element, 'raiseError')) {
            // on Moodle >= 2.3 the ids are OK
        } else {
            // on Moodle 2.0 - 2.1 we must fix the id
            // by default the id is something like "id_2696f9"
            // but we want "id_applydefaults_selectedtasks"
            // Note: Moodle 2.2 ids are OK, but it does not harm ...
            $type = $element->getType();
            if ($type=='radio' || $type=='checkbox') {
                // basically, the id is "id_"  followed by the element name
                // we also fix "[" and "]" e.g. sortorder[1] -> sortorder_1
                // and for radio buttons, we append "_" and the element value
                $id = 'id_'.$element->getName();
                $id = strtr($id, array('[' => '_', ']' => ''));
                if ($type=='radio') {
                    $id .= (is_null($element->getValue()) ? '' : '_'.$element->getValue());
                }
                $element->updateAttributes(array('id' => $id));
            }
        }

        // for form elements that have '[' and ']' in the name
        // the standard QuickForm behavior does not transfer
        // incoming form values to the outgoing form,
        // so we try to fix it manually here, at least for
        // "checkbox" and "radio" elements

        $type = $element->getType();
        if ($type=='checkbox' || $type=='radio') {
            $name = $element->getName();
            if ($pos = strpos($name, '[')) {
                $i = substr($name, $pos+1, -1);
                $name = substr($name, 0, $pos);
                if (isset($_POST[$name]) && is_array($_POST[$name])) {
                    $element->setChecked(empty($_POST[$name][$i]) ? false : true);
                }
            }
        }

        // proceed as normal
        parent::renderElement($element, $required, $error);
    }

   /**
    * Helper method for renderElement
    *
    * @param    string      Element name
    * @param    mixed       Element label (if using an array of labels, you should set the appropriate template)
    * @param    bool        Whether an element is required
    * @param    string      Error message associated with the element
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    function _prepareTemplate($name, $label, $required, $error, $type=null, $value=null) {
        global $mform;
        static $count = array();

        // get standard html for this element using MoodleQuickForm_Renderer
        $html = parent::_prepareTemplate($name, $label, $required, $error);

        // add field-specific class name
        if ($name) {
            // remove unwanted prefixes and suffixes from element name, to get CSS class
            $search = array('/^selectfield_/',     // leading "selectfield_"   (elements in the "#selects" row)
                            '/^defaultfield_/',    // leading "defaultfield_"  (elements in the "#defaults" row)
                            '/(?:\\[\\w+\\])+$/',  // trailing [99]            (elements in the "#record99" row)
                            '/_elements$/',        // trailing _elements       (elements in a group of elements)
                            '/_label$/');          // trailing _label          (elements in the "#labels" row)
            $class = preg_replace($search, '', $name);

            // insert new class name into HTML class attribute
            $search = '/(?<=class=")[^"]*(?=")/';
            $html = preg_replace($search, '$0 '.$class, $html, 1);
        }

        // get $name and $value for this element
        //     One day it may be possible to pass the $name and $value directly to this method,
        //     but for now we have rely on the TaskChain mform object to fetch the information.
        if (isset($mform) && method_exists($mform, 'get_element')) {
            if (isset($count[$name])) {
                $count[$name] ++;
            } else {
                $count[$name] = 0;
            }
            if ($element = $mform->get_element($name, $count[$name])) {
                $type = $element->getType();
                $value = $element->getValue();
            }
        }

        // make id unique for each radio element
        //     The standard MoodleQuickForm radio elements all have the same id
        //     which is the value of the first radio element appended to the element name.
        //     This is not what we want, nor is it what I believe was intended, but anyway...,
        //     We replace the suffix on the standard id with the value of the current element,
        //     so that each item of the radio group has a unique id which we can use for CSS.
        if (isset($type)) {
            if (substr($html, 0, 14)=="\n\t\t<div class=") {
                // fix Moodle 2.0 - 2.1 (add id and type-specific CSS class)
                $id = ($type=='group' ? 'fgroup' : 'fitem');
                $id .= '_id_'.$name;
                if ($type=='radio' && isset($value)) {
                    $id .= '_'.$value;
                }
                $search = '/class="fitem\\b/';
                $replace = 'id="'.$id.'" $0 fitem_f'.$type;
                $html = preg_replace($search, $replace, $html, 1);
            } else if ($type=='radio' && isset($value)) {
                // fix Moodle >= 2.2 (make id unique, by replacing id suffix with $value)
                $search = '/(?<=id=")([^"]*)(_[^_"]*)(?=")/';
                $html = preg_replace($search, '$1_'.$value, $html, 1);
            }
        }

        return $html;
    }
}

