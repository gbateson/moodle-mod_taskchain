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
 * mod/taskchain/form/base.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/**
 * mod/taskchain/form/base.php
 *
 * This file defines helper classes to assist and streamline
 * the creation of edit forms used in the TaskChain module
 *
 * The normal sequence of processing is:
 * (1) $this->prepare_sections()    corresponds to $this->mform->data_preprocessing()
 * (2) $this->add_sections()        corresponds to $this->mform->definition()
 * (3) $this->validate_sections()   corresponds to $this->mform->validate_sections()
 * (4) $this->fix_sections()        corresponds to $this->mform->data_postprocessing()
 *
 * The classes hierarchy defined here is as follows:
 *
 *   taskchain_form_helper_base
 *     |
 *     |    contains the general purpose methods to loop through form
 *     |    sections and fields and add headers, labels and elements
 *     |
 *     |    PROPERTIES:
 *     |    sections, mform, context, record, recordtype
 *     |
 *     |    METHODS:
 *     |    __get, __set, property_error
 *     |    is_add, is_update, get_originalvalue, get_defaultvalue,
 *     |    get_context, set_context, set_type_text,
 *     |    prepare_sections, prepare_section, prepare_field,
 *     |    get_sections, get_sectionlabel, get_fieldlabel,
 *     |    add_sections, add_section, add_sectionlabel, add_field,
 *     |    validate_sections, validate_section, validate_field
 *     |    fix_sections, fix_section, fix_field, fix_template_notnull
 *     |
 *     +- taskchain_form_helper_record
 *     |    |
 *     |    |  contains the methods to handle headers, labels and elements
 *     |    |  for fields that are common to task and chain records
 *     |    |
 *     |    |  METHODS:
 *     |    |  get_sectionlabel ...
 *     |    |      general / tasks / display
 *     |    |  get_fieldlabel ...
 *     |    |      name / attemptlimit / password / subnet
 *     |    |  add_field ...
 *     |    |      name / sourcefile / sourcelocation
 *     |    |      configfile / configlocation / addtype / tasknames
 *     |    |      timeopen / timeclose / timelimit / delay1 / delay2
 *     |    |      attemptlimit / allowresume / password / subnet
 *     |    |  add_template ...
 *     |    |      time / timer / file / location / textsource
 *     |    |      method / ignore / weighting / limit
 *     |    |  validate_field_sourcefile
 *     |    |  fix_field ...
 *     |    |      name / sourcefile / configfile / tasknames / entrytext / exittext
 *     |    |  fix_template ...
 *     |    |      filearea / textsource
 *     |    |
 *     |    +- taskchain_form_helper_chain (a single chain)
 *     |    |
 *     |    |  contains the methods to add headers, labels and elements
 *     |    |  for fields that are unique to chain records
 *     |    |
 *     |    |  METHODS:
 *     |    |  add_field ...
 *     |    |      entrypage / entrytext / entryoptions / entrycm / entrygrade
 *     |    |      exitpage / exittext / exitoptions / exitcm / exitgrade
 *     |    |      showpopup / allowfreeaccess / attemptgrademethod / grademethod
 *     |    |      gradeignore / gradeweighting / gradelimit / gradeitem
 *     |    |  add_template ...
 *     |    |      page / pagetext / page_options / activitylist
 *     |    |
 *     |    +- taskchain_form_helper_task (a single task)
 *     |    |
 *     |    |  contains the methods to handle headers, labels and elements
 *     |    |  for fields that are unique to task records
 *     |    |
 *     |    +- taskchain_form_helper_condition (a single condition)
 *     |
 *     +- taskchain_form_helper_records
 *          |
 *          +- taskchain_form_helper_tasks (all tasks in a chain)
 *          +- taskchain_form_helper_chains (all chains in a course)
 *          +- taskchain_form_helper_conditions (all conditions for a task)
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');
require_once($CFG->dirroot.'/mod/taskchain/edit/form/QuickForm/heading.php');
require_once($CFG->dirroot.'/mod/taskchain/edit/form/QuickForm/Renderer/TaskChain.php');

$target_renderer = 'TaskChain_MoodleQuickForm_Renderer';
$default_renderer = '_HTML_QuickForm_default_renderer';
if (isset($GLOBALS[$default_renderer]) && get_class($GLOBALS[$default_renderer])==$target_renderer) {
    // do nothing
} else {
    $GLOBALS[$default_renderer] = new $target_renderer();
}
unset($default_renderer, $target_renderer);

/**
 * taskchain_form_helper_base
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
abstract class taskchain_form_helper_base {
    // all properties and get_xxx() and set_xxx() methods
    // should be "private" so that access to properties
    // is controlled by the __get() and __set() methods

    /** default text field size */
    const TEXT_FIELD_SIZE_LONG  = 40;
    const TEXT_FIELD_SIZE_SHORT = 15;

    /** the text field size */
    protected $text_field_size = 0;

    /** reference to global $TC object */
    protected $TC = null;

    /** array to map each form section to an array of fields */
    protected $sections = array();

    /** the default sections/fields shown on forms for multiple records */
    protected $defaultsections = array();

    /** default values for each field in this input form */
    protected $defaultvalues = array();

    /** fields that are only used when adding a new record */
    protected $addonlyfields = array();

    /** the moodle form object which we are "helping" to manipulate */
    protected $mform = null;

    /** the current $context */
    protected $context = null;

    /** if this form is for a single field, this string holds the name of that field */
    protected $singlefield = '';

    /** the current database $record */
    protected $record = null;

    /** type of record */
    protected $recordtype = '';

    /** boolean switch denoting whether or not this $record is one of multiple child $records */
    protected $is_multiple = false;

    /** boolean switch denoting whether or not this record is the default record */
    protected $is_default_record = false;

    /**
     * __construct
     *
     * @param object $mform a MoodleQuickForm
     * @param object $context a context record from the database
     * @param stdClass $record fields from record in the database
     * @param boolean $multiple (optional, default=false)
     * @todo Finish documenting this function
     */
    public function __construct(&$mform, &$context, &$record, $is_multiple=false) {
        global $CFG, $TC;

        if (empty($TC)) {
            $TC = new mod_taskchain();
        }

        $this->TC       = &$TC;
        $this->mform    = $mform;
        $this->context  = $context;
        $this->record   = $record;
        $this->is_multiple = $is_multiple;

        // if this form is for a single field, adjust the $sections array
        if ($field = optional_param('field', '' , PARAM_ALPHANUM)) {
            foreach ($this->sections as $section => $fields) {
                if ($section=='hidden') {
                    continue; // e.g. "id" field
                }
                if (in_array($field, $fields)) {
                    $this->singlefield = $field;
                    break;
                }
            }
            if ($this->singlefield) {
                $this->sections = array('singlefield' => array($field));
            }
            $this->text_field_size = self::TEXT_FIELD_SIZE_SHORT;
        } else {
            $this->text_field_size = self::TEXT_FIELD_SIZE_LONG;
        }

    }

    /////////////////////////////////////////////////////////
    // "magic" methods
    /////////////////////////////////////////////////////////

    /**
     * __get
     *
     * @param xxx $name
     * @return xxx
     * @todo Finish documenting this function
     */
    public function __get($name) {
        $method = 'get_'.$name;
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            $this->property_error('get', $name, $method);
        }
    }

    /**
     * __set
     *
     * @param xxx $name
     * @param xxx $value
     * @todo Finish documenting this function
     */
    public function __set($name, $value) {
        $method = 'set_'.$name;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->property_error('set', $name, $method);
        }
    }

    /**
     * report an eror from a "magic" method
     *
     * @param string $type of magic function error ("get" or "set")
     * @param string $property name of a private or undefined property
     * @param string $method name of a method used to access the $property
     */
    protected function property_error($type, $property, $method) {
        if (property_exists($this, $property)) {
            $hint = 'error_'.$type.'privateproperty';
        } else {
            $hint = 'error_'.$type.'unknownproperty';
        }
        $a = (object)array('class'    => get_class($this),
                           'property' => $property,
                           'method'   => $method.'()');
        $hint = get_string($hint, 'mod_taskchain', $a);
        throw new coding_exception($hint); // $debuginfo
    }

    /////////////////////////////////////////////////////////
    // information methods
    /////////////////////////////////////////////////////////

    /**
     * Detects if we are adding a new record
     * as opposed to updating an existing one
     *
     * Note: we could use any of the following to detect add:
     *   - empty($this->record->add | id | coursemodule | instance)
     *
     * @return bool True if we are adding an new activity instance, false otherwise
     */
    public function is_add() {
        $id = $this->get_originalvalue('id', null);
        return ($id ? false : true);
    }

    /**
     * Detects if we are updating a record
     * as opposed to adding an new one
     *
     * @return bool True if we are adding an new activity instance, false otherwise
     */
    public function is_update() {
        $id = $this->get_originalvalue('id', null);
        return ($id ? true : false);
    }

    /**
     * Detects if we are updating a single field (via ajax)
     *
     * @return bool True if we are updating a single field, false otherwise
     */
    public function is_singlefield() {
        return ($this->singlefield ? true : false);
    }

    /**
     * update_singlefield
     *
     * @param array $data (passed by reference)
     * @return void may modify $this->record
     * @todo Finish documenting this function
     */
    public function update_singlefield(&$data) {
        if ($field = $this->singlefield) {
            $this->record->$field = $data->$field;
            $this->update_record();
        }
    }

    /**
     * display_singlefield
     *
     * @todo Finish documenting this function
     */
    public function display_singlefield() {
        if ($field = $this->singlefield) {
            $value = $this->get_fieldvalue($field);
            $value = $this->format_fieldvalue($field, $value);
            echo $value;
        }
    }

    /**
     * return a field value from the original record
     * this function is useful to see if a value has changed
     *
     * @param string the $field name
     * @param mixed the $default value
     * @return mixed the field value if it exists, $default otherwise
     */
    public function get_originalvalue($field, $default) {
        if (isset($this->record)) {
            $method = 'get_'.$field;
            if (method_exists($this->record, $method)) {
                return $this->record->$method();
            }
            if (isset($this->record->$field)) {
                return $this->record->$field;
            }
        }
        return $default;
    }

    /**
     * get_defaultvalue
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue($field) {
        $method = 'get_defaultvalue_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method($field);
        }
        $value = $this->get_preference($field);
        if (isset($value)) {
            return $value;
        }
        if (isset($this->defaultvalues[$field])) {
            return $this->defaultvalues[$field];
        }
        // throw new moodle_exception(get_class($this)." - missing default value: $field");
        return null; // shouldn't happen !!
    }

    /**
     * get_defaultvalue_template_source
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_defaultvalue_template_source($field) {
        if ($this->is_add()) {
            $value = $this->get_preference($field);
            if (isset($value)) {
                return $value;
            }
            if (isset($this->defaultvalues[$field])) {
                return $this->defaultvalues[$field];
            }
        }
        return mod_taskchain::TEXTSOURCE_SPECIFIC;
    }

    /**
     * is_default_record
     * get or set the $this->is_default_record boolean switch
     *
     * @param mixed $value TRUE, FALSE or NULL
     * @return mixed if $value is NULL then return $this->is_default_record
     *               otherwise assign $value to this->is_default_record
     */
    protected function is_default_record($value=null) {
        if ($value===null) {
            return $this->is_default_record;
        }
        $this->is_default_record = $value;
    }

    /**
     * get_context
     *
     * @return $this->context
     * @todo Finish documenting this function
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * set_context
     *
     * @param integer $contextlevel
     * @param integer $instanceid (optional, default=0)
     * @return void, but will update $this->context
     * @todo Finish documenting this function
     */
    public function set_context($contextlevel, $instanceid=0) {
        $this->context = mod_taskchain::context($contextlevel, $instanceid);
    }

    /**
     * set_type_text
     *
     * @uses $CFG
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function set_type_text($field) {
        global $CFG;
        $name = $this->get_fieldname($field);
        if (empty($CFG->formatstringstriptags)) {
            $this->mform->setType($name, PARAM_CLEAN);
        } else {
            $this->mform->setType($name, PARAM_TEXT);
        }
    }

    /**
     * update_record
     *
     * @return void, but may update db
     * @todo Finish documenting this function
     */
    public function update_record() {
        global $DB;

        if (method_exists($this->record, 'update')) {
            return $this->record->update();
        }

        if (method_exists($this->record, 'to_stdclass')) {
            $stdclass = $this->record->to_stdclass();
        } else {
            $stdclass = $this->record;
        }

        // get records type(s) and record id
        $type = $this->recordtype;
        $types = $this->recordtype.'s';
        $id = $this->get_fieldvalue('id');

        // update main $TC record if necessary
        if (isset($this->TC->$type) && $this->TC->$type->get_id()==$id) {
            foreach (get_object_vars($stdclass) as $name => $value) {
                $method = 'set_'.$name;
                if (method_exists($this->TC->$type, $method)) {
                    $this->TC->$type->$method($value);
                } else {
                    $this->TC->$type->$name = $value;
                }
            }
        }

        // update record in $TC record array, if necessary
        if (isset($this->TC->$types) && array_key_exists($id, $this->TC->$types)) {
            $this->TC->{$types}[$id] = $stdclass;
        }

        // and finally, update the record in the DB
        $table = 'taskchain_'.$types;
        return $DB->update_record($table, $stdclass);
    }

    /**
     * delete_record
     *
     * @return void, but may delete db
     * @todo Finish documenting this function
     */
    public function delete_record() {
        global $DB;

        if (method_exists($this->record, 'delete')) {
            return $this->record->delete();
        }

        // get records type(s) and record id
        $type = $this->recordtype;
        $types = $this->recordtype.'s';
        $id = $this->get_fieldvalue('id');

        // delete any cached records of this $type(s)
        $this->TC->delete_cached_records($types, $type, array($id));

        // delete form fields for this record
        $this->delete_form_elements($id);

        // and finally, delete the record info from the DB
        // specifically this is for "taskchain_delete_tasks"
        $deletefunction = 'taskchain_delete_'.$types;
        if (function_exists($deletefunction)) {
            $deletefunction($id);
        }
    }

    /**
     * get_preferencelength
     *
     * @todo Finish documenting this function
     */
    protected function get_preferencelength($default=255) {
        global $DB;

        $table = 'user_preferences';
        $field = 'value';
        $columns = $DB->get_columns($table);

        if (array_key_exists($field, $columns) && isset($columns[$field]->max_length)) {
            return $columns[$field]->max_length;
        } else {
            return $default; // shouldn't happen !!
        }
    }

    /**
     * get_preferencefields
     *
     * @param boolean (optional, default=false) $include fieldnames from $this->defaultfields array
     * @param boolean (optional, default=false) $exclude fieldnames only used when adding new records
     * @todo Finish documenting this function
     */
    protected function get_preferencefields($include=false, $exclude=false) {
        //$method = 'preferences_fieldnames_'.$this->recordtype;
        //$fields = call_user_func(array('mod_taskchain', $method));

        $fields = array();
        $sections = $this->get_sections();
        foreach ($sections as $sectionname => $sectionfields) {
            if ($sectionname=='headings' || $sectionname=='general' || $sectionname=='actions' || $sectionname=='hidden') {
                continue;
            }
            $fields = array_merge($fields, $sectionfields);
        }
        if ($include) {
            $fields = array_merge($fields, array_keys($this->defaultvalues));
        }
        if ($exclude) {
            $fields = array_diff($fields, $this->addonlyfields);
        }
        return $fields;
    }

    /**
     * get_preferencename
     *
     * @param string the $field whose preference name will be returned
     * @param string $type (optional, default=null) the type of preference
     * @todo Finish documenting this function
     */
    protected function get_preferencename($field) {
        $method = 'get_preferencename_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method($field);
        }
        if ($this->recordtype=='' || $this->recordtype=='taskchain') {
            return 'taskchain_'.$field;
        } else {
            return 'taskchain_'.$this->recordtype.'_'.$field;
        }
    }

    /**
     * get_preference
     *
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_preference($field, $default=null) {
        $method = 'get_preference_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method($field, $default);
        }
        $name = $this->get_preferencename($field);
        return get_user_preferences($name, $default);
    }

    /**
     * get_preferencename_columnlistid
     *
     * @param string $field the field name
     * @return string name of the preference setting for this $field
     * @todo Finish documenting this function
     */
    protected function get_preferencename_columnlistid($field) {
        $type = $this->TC->get_columnlisttype();
        return 'taskchain_'.$type.'_'.$field;
    }

    /**
     * get_preference_columnlistid
     *
     * @param string $field the field name
     * @return mixed user preference for this field, or $this->defaultvalues[$field], or null
     * @todo Finish documenting this function
     */
    protected function get_preference_columnlistid($field, $default=null) {
        $value = optional_param($field, null, PARAM_ALPHANUM);
        if ($value===null || $value==='') {
            if ($default===null) {
                $default = 'default';
            }
            $name = $this->get_preferencename($field);
            $value = get_user_preferences($name, $default);
        }
        return $value;
    }

    /**
     * get_preferences
     *
     * @param array $selectedfields (optional, default=null)
     * @todo Finish documenting this function
     */
    public function get_preferences($selectedfields=null) {
        $fields = $this->get_preferencefields(true);
        if ($selectedfields) {
            $fields = array_intersect($fields, $selectedfields);
        }
        $preferences = array();
        foreach ($fields as $field) {
            $value = $this->get_preference($field);
            if ($value===null) {
                continue;
            }
            $preferences[$field] = $value;
        }
        return $preferences;
    }

    /**
     * get_datavalue
     *
     * @param object $data (passed by reference) recently submitted form $data
     * @param string name of required user preference $field
     * @todo Finish documenting this function
     */
    protected function get_datavalue(&$data, $field) {
        $method = 'get_datavalue_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method($data, $field);
        }
        if (isset($data->$field)) {
            return $data->$field;
        } else {
            return null;
        }
    }

    /**
     * set_preferences
     *
     * @todo Finish documenting this function
     */
    public function set_preferences(&$data) {
        $fields = $this->get_preferencefields(true, $this->is_update());
        $length = $this->get_preferencelength();

        $preferences = array();
        foreach ($fields as $field) {

            $value = $this->get_datavalue($data, $field);
            if (is_null($value)) {
                continue;
            }

            // make sure $value is not too long for "user_preferences.value" field
            if (mod_taskchain::textlib('strlen', $value) > $length) {
                $this->truncate_string($value, $length);
            }

            // add this value to user preferences
            $name = $this->get_preferencename($field);
            $preferences[$name] = $value;

            // update default value in form, if necessary
            $name = 'defaultfield_'.$field;
            if ($this->mform->elementExists($name)) {
                $value = $this->format_fieldvalue($field, $value);
                $this->mform->getElement($name)->setValue($value);
            }
        }

        // update this user's preferences
        set_user_preferences($preferences);
    }

    /**
     * truncate_string
     *
     * @param string $str (passed by refererence)
     * @param integer $length the maximum allowed length for this $str
     * @return void, but may reduce $str to required $length
     */
    protected function truncate_string(&$str, $length) {
        // remove HTML comments
        $str = preg_replace('/\<\!\-\-.*?\-\-\>\s*/s', '', $str);

        // remove script|style blocks
        $str = preg_replace('/<(script|style)[^>]*>.*?<\/\1>\s*/is', '', $str);

        // truncate $str to maximum $length
        $str = mod_taskchain::textlib('substr', $str, 0, $length);

        // remove incomplete trailing HTML tags
        $str = preg_replace('/<[^>]*$/s', '', $str);

        // remove incomplete trailing HTML blocks
        $tags = array('audio', 'embed', 'object', 'video', 'button'); // , 'form', 'table', 'dl', 'ol', 'ul'
        foreach ($tags as $tag) {
            $pos = -1;
            $tag1 = "/<$tag\b[^>]*>/is";
            $tag2 = "/<\/$tag\b[^>]*>/is";
            while (preg_match($tag1, $str, $matches, PREG_OFFSET_CAPTURE, $pos+1)) {
                list($match, $pos) = $matches[0];
                if (preg_match($tag2, $str, $matches, PREG_OFFSET_CAPTURE, $pos + mod_taskchain::textlib('strlen', $match))) {
                    list($match, $pos) = $matches[0];
                    $pos += mod_taskchain::textlib('strlen', $match);
                } else {
                    $str = mod_taskchain::textlib('substr', $str, 0, $pos);
                    break; // finish while loop
                }
            }
        }

        // add closing tags
        $tags = array('b', 'u', 'i', 'font', 'span', 'p', 'div');
        foreach ($tags as $tag) {
            $pos = -1;
            $tag1 = "/<$tag\b[^>]*>/is";
            $tag2 = "/<\/$tag\b[^>]*>/is";
            while (preg_match($tag1, $str, $matches, PREG_OFFSET_CAPTURE, $pos+1)) {
                list($match, $pos) = $matches[0];
                $pos += mod_taskchain::textlib('strlen', $match);
                if (preg_match($tag2, $str, $matches, PREG_OFFSET_CAPTURE, $pos+1)) {
                    list($match, $pos) = $matches[0];
                    $pos += mod_taskchain::textlib('strlen', $match);
                } else {
                    $str .= "</$tag>"; // this may make the string too long again
                }
            }
        }

        // remove plain text chars to reduce length, if necessary
        $i_max = mod_taskchain::textlib('strlen', $str) - 1;
        $state = 0; // 0=outside a tag, 1=within a tag
        for ($i=$i_max; $i>0 && $i_max>=$length; $i--) {
            $char = mod_taskchain::textlib('substr', $str, $i, 1);
            if ($state==0) {
                if ($char=='>') {
                    $state = 1;
                } else {
                    // decrease $i_max and remove char $i
                    $i_max--;
                    $str = mod_taskchain::textlib('substr', $str, 0, $i).
                           mod_taskchain::textlib('substr', $str, $i+1);
                }
            } else {
                if ($char=='<') {
                    $state = 0;
                }
            }
        }
    }

    /////////////////////////////////////////////////////////
    // locate and delete form elements
    /////////////////////////////////////////////////////////

    /**
     * find and return a form element (or null)
     *
     * this method if called from TaskChain form renderer
     * mod/taskchain/edit/form/QuickForm/Renderer/TaskChain.php
     *
     * @param string $name of element
     * @param integer $count the occurrence of an element (for radio buttons)
     * @return form element (or null)
     */
    public function get_element($name, $count) {
        $index = null;
        if ($count==0) {
            if (isset($this->mform->_elementIndex[$name])) {
                $index = $this->mform->_elementIndex[$name];
            }
        } else {
            if (isset($this->mform->_duplicateIndex[$name][$count - 1])) {
                $index = $this->mform->_duplicateIndex[$name][$count - 1];
            }
        }
        if (is_null($index) || empty($this->mform->_elements[$index])) {
            return null; // element not found - shouldn't happen !!
        }
        return $this->mform->_elements[$index];
    }

    /**
     * locate_form_elements
     *
     * @param string $section (optional, default = 'record')
     * @param string $insertBefore (optional, default = 'actionshdr')
     * @todo Finish documenting this function
     */
    protected function locate_form_elements($section='record', $insertBefore='actionshdr') {

        // the $start array maps $recordid to the $elementid of the first form
        // element for the $recordid (i.e. the "header" element for the record)
        $start = array();

        // the $end array maps $recordid to the $elementid of the element
        // immediately AFTER the last form element for the $recordid.
        // Usually this will be the "header" element of the next record
        // but for the last record it will be the $insertBefore element
        $end = array();

        // get a reference to $this->mform's element index
        // Note: $index maps ($elementname => $elementid)
        $index = &$this->mform->_elementIndex;

        $strlen = strlen($section);
        $names = array_keys($index);
        $names = preg_grep('/^'.$section.'[0-9]+$/', $names);

        foreach ($names as $name) {
            $recordid = substr($name, $strlen);
            $start[$recordid] = $index[$name];
        }

        // add element ids to $end array
        foreach ($start as $recordid1 => $elementid1) {
            $elementid = $index[$insertBefore];
            foreach ($start as $recordid2 => $elementid2) {
                if ($recordid1==$recordid2) {
                    continue; // same row
                }
                if ($elementid1 > $elementid2) {
                    continue; // earlier row
                }
                if ($elementid > $elementid2) {
                    $elementid = $elementid2;
                }
            }
            $end[$recordid1] = $elementid;
        }

        // all done
        return array($start, $end);
    }

    /**
     * delete_form_element
     *
     * @param string $id_or_name the id or name of the element to be removed from $this->mform
     * @param boolean $removeRules TRUE to remove rules, otherwise false
     * @todo Finish documenting this function
     */
    protected function delete_form_element($id_or_name, $removeRules=true) {
        // we would like to use $this->mform->removeElement($elementname, $removeRules)
        // but in the case of radio elements, and others with duplicate names,
        // removeElement() assumes we want to remove the first element in the set
        // which is not always want we want, so we proceed "manually", thus ...

        $elementid = null;
        if (is_numeric($id_or_name)) {
            if (array_key_exists($id_or_name, $this->mform->_elements)) {
                $element = $this->mform->_elements[$id_or_name];
                $elementname = $element->getName();
                $elementid = $id_or_name;
            }
        } else {
            foreach ($this->mform->_elements as $id => $element) {
                $elementname = $element->getName();
                if ($elementname==$id_or_name) {
                    $elementid = $id;
                    break;
                }
            }
        }
        if ($elementid===null) {
            return; // shoudn't happen !!
        }

        // remove element from elements array
        unset($this->mform->_elements[$elementid]);

        // get a reference to $this->mform's element index
        $index = &$this->mform->_elementIndex;

        // remove/adjust references to element in main index and duplicate-name index
        // (used for radio elements and other elements which have the duplicate names)
        $elementname = $element->getName();
        if (empty($this->mform->_duplicateIndex[$elementname])) {
            // element has unique name (the usual case for non-radio elements)
            unset($index[$elementname]);
        } else if ($index[$elementname]==$elementid) {
            // radio set with this element referenced in main index
            // this is the case that mform->removeElement() expects
            $index[$elementname] = array_shift($this->mform->_duplicateIndex[$elementname]);
        } else {
            // radio set with this element NOT referenced in main index
            // this is the case that mform->removeElement() does not handle
            $array = &$this->mform->_duplicateIndex[$elementname];
            unset($array[array_search($elementid, $array)]);
            unset($array);
        }

        // release the $index reference
        unset($index);

        // remove rules, if requested (but only if no remaining elements with this $elementname)
        if ($removeRules && empty($this->mform->_duplicateIndex[$elementname])) {
            unset($this->mform->_rules[$elementname], $this->mform->_errors[$elementname]);
        }

        return $element;
    }

    /**
     * delete_form_elements
     *
     * @param mixed either an array of $recordids, or a single $recordid
     * @param string $section (optional, default='record')
     * @param string $insertBefore (optional, default='actionshdr')
     * @todo Finish documenting this function
     */
    protected function delete_form_elements($recordids, $section='record', $insertBefore='actionshdr') {

        if (is_string($recordids) || is_numeric($recordids)) {
            $recordids = array($recordids);
        }

        list($start, $end) = $this->locate_form_elements($section, $insertBefore);

        // main loop through the recordids
        foreach ($recordids as $recordid) {

            if (empty($start[$recordid]) || empty($end[$recordid])) {
                continue; // shouldn't happen !!
            }

            // now loop through all form elements,
            // and delete elements for this recordid
            // i.e. between $start[$recordid] and $end[$recordid]

            foreach (array_keys($this->mform->_elements) as $elementid) {
                if ($elementid < $start[$recordid]) {
                    continue; // before start of record
                }
                if ($elementid >= $end[$recordid]) {
                    break; // after end of record
                }

                // delete the element from $this->mform (remove rules too)
                $element = $this->delete_form_element($elementid, true);
            }
        }
    }

    /**
     * sort_form_elements
     *
     * @param array of sorted $recordids
     * @param string $section (optional, default = 'record')
     * @param string $insertBefore (optional, default = 'actionshdr')
     * @todo Finish documenting this function
     */
    protected function sort_form_elements($recordids, $section='record', $insertBefore='actionshdr') {

        list($start, $end) = $this->locate_form_elements($section, $insertBefore);

        // main loop through the recordids
        foreach ($recordids as $recordid) {

            if (empty($start[$recordid]) || empty($end[$recordid])) {
                continue; // shouldn't happen !!
            }

            // now loop through all form elements, and move
            // (copy-delete-insert) elements for this recordid

            foreach (array_keys($this->mform->_elements) as $elementid) {
                if ($elementid < $start[$recordid]) {
                    continue; // before start of record
                }
                if ($elementid >= $end[$recordid]) {
                    break; // after end of record
                }

                // delete the element from $this->mform (don't remove rules)
                $element = $this->delete_form_element($elementid, false);

                // insert the $element in its proper place
                $this->mform->insertElementBefore($element, $insertBefore);
                unset($element);
            }
        }
    }

    /////////////////////////////////////////////////////////
    // prepare sections and fields
    /////////////////////////////////////////////////////////

    /**
     * prepare_sections
     *
     * @param array $data (passed by reference)
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    public function prepare_sections(&$data) {
        $fields = $this->get_preferencefields();
        foreach ($fields as $field) {
            if (! isset($data[$field])) {
                $data[$field] = $this->get_defaultvalue($field);
            }
        }
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $this->prepare_section($data, $section, $fields);
        }
    }

    /**
     * prepare_section
     *
     * @param array $data (passed by reference)
     * @param string $section name of section
     * @param array $fields within this section
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function prepare_section(&$data, $section, $fields) {
        $method = 'prepare_section_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($data);
        } else {
            foreach ($fields as $field) {
                $this->prepare_field($data, $field);
            }
        }
    }

    /**
     * prepare_field
     *
     * @param array $data (passed by reference)
     * @param string $field name of field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function prepare_field(&$data, $field) {
        $method = 'prepare_field_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($data);
        } else if (isset($data[$field])) {
            // field has already be prepared
        } else {
            // copy value across from record
            $data[$field] = $this->get_originalvalue($field, '');
        }
    }

    /////////////////////////////////////////////////////////
    // get sections and fields ...
    /////////////////////////////////////////////////////////

    /**
     * get_sections
     *
     * @return boolean $all (optional, default=false)
     * @return xxx
     * @todo Finish documenting this function
     */
    public function get_sections($all=false) {
        if (empty($this->is_multiple) || $all) {
            $id = 'all'; // single record form
        } else {
            // one of multiple records on the edit chains/tasks page
            $id = $this->get_preference('columnlistid');
        }

        // all sections
        if ($id=='all') {
            return $this->sections;
        }

        // a specific section
        if (array_key_exists($id, $this->sections)) {
            return array('general' => $this->sections['general'], $id => $this->sections[$id]);
        }

        // get custom column lists
        $type = $this->TC->get_columnlisttype();
        $lists = $this->TC->get_columnlists($type, true);

        $sections = array();
        if (array_key_exists('general', $this->sections)) {
            $sections['general'] = $this->sections['general'];
        }
        if (array_key_exists($id, $lists)) {
            // a custom column list
            $sections[$id] = $lists[$id];
        } else {
            // use default sections
            $sections = array_merge($sections, $this->defaultsections);
        }
        return $sections;
    }

    /**
     * get_sectionlabel
     *
     * @param string $section name of section
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel($section) {
        $method = 'get_sectionlabel_'.$section;
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return get_string($section.'hdr', 'mod_taskchain');
        }
    }

    /**
     * get_sectionlabel_filters
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_filters() {
        return get_string('filters', 'admin');
    }

    /**
     * get_sectionlabel_actions
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_actions() {
        return get_string('actions');
    }

    /**
     * get_sectionlabel_hidden
     *
     * @param string $section name of section
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_sectionlabel_hidden() {
        return '';
    }

    /**
     * get_fieldlabel
     *
     * @param string $field name of field
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldlabel($field) {
        if ($this->singlefield) {
            return '';
        }
        $method = 'get_fieldlabel_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return get_string($field, 'mod_taskchain');
        }
    }

    /**
     * get_fieldname
     *
     * @param string $field name of field
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldname($field) {
        $method = 'get_fieldname_'.$field;
        if (method_exists($this, $method)) {
            $name = $this->$method();
        } else {
            $name = $field;
        }
        if ($this->is_multiple) {
            $name .= '['.$this->get_fieldvalue('id').']';
        }
        return $name;
    }

    /**
     * get_fieldvalue
     *
     * @param string $field name of field
     * @return xxx
     * @todo Finish documenting this function
     */
    protected function get_fieldvalue($field) {
        if ($field=='id' && $this->is_default_record()) {
            return 0;
        }
        $method = 'get_fieldvalue_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        $method = 'get_'.$field;
        if (method_exists($this->record, $method)) {
            return $this->record->$method();
        }
        if (isset($this->record->$field)) {
            return $this->record->$field;
        }
        return null; // shouldn't happen !!
    }

    /**
     * set_field_value
     *
     * @param string $field name of field
     * @param mixed  the new field $value
     * @return boolean true if record was updated, false otherwise
     * @todo Finish documenting this function
     */
    protected function set_fieldvalue($field, $value) {
        $method = 'set_fieldvalue_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method($field, $value);
        }

        // update $this->record->$field
        $method = 'set_'.$field;
        if (method_exists($this->record, $method)) {
            $this->record->$method($value);
        } else if (isset($this->record->$field)) {
            $this->record->$field = $value;
            // update DB record too ?
        }

        // update $this->mform element
        $name = $this->get_fieldname($field);
        if ($this->mform->elementExists($name)) {
            $value = $this->format_fieldvalue($field, $value);
            $this->mform->getElement($name)->setValue($value);
        }
        return true;
    }

    /////////////////////////////////////////////////////////
    // add sections and fields ...
    /////////////////////////////////////////////////////////

    /**
     * add_sections
     *
     * @todo Finish documenting this function
     */
    public function add_sections() {
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $this->add_section($section, $fields);
            // to make individual sections NOT collapsible, do this:
            //if (method_exists($this->mform, 'setExpanded')) {
            //    $this->mform->setExpanded($section.'hdr', true);
            //}
        }
        // make all sections NOT collapsible (Moodle >= 2.5)
        if (method_exists($this->mform, 'setDisableShortForms')) {
            $this->mform->setDisableShortForms();
        }
    }

    /**
     * add_section
     *
     * @param string $section name of section
     * @param array $fields names of fields
     * @todo Finish documenting this function
     */
    protected function add_section($section, $fields) {
        $method = 'add_section_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($section, $fields);
        } else {
            $this->add_sectionlabel($section);
            foreach ($fields as $field) {
                $this->add_field($field);
            }
        }
    }

    /**
     * add_section_label
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function add_sectionlabel($section) {
        $method = 'add_sectionlabel_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($section);
        } else {
            $label = $this->get_sectionlabel($section);
            $this->mform->addElement('header', $section.'hdr', $label);
        }
    }

    /**
     * add_section_label
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function add_sectionlabel_singlefield($section) {
        return '';
    }

    /**
     * add_section_headings
     *
     * @param string $section name of section
     * @param array $fields names of fields
     * @todo Finish documenting this function
     */
    protected function add_section_headings($section, $fields) {
        $level = 1;
        foreach ($fields as $field) {
            $text  = null;
            $level++;
            $class = null;
            $cssid = null;
            if (is_array($field)) {
                if (isset($field['text'])) {
                    $text = $field['text'];
                }
                if (isset($field['level'])) {
                    $level = $field['level'];
                }
                if (isset($field['class'])) {
                    $class = $field['class'];
                }
                if (isset($field['cssid'])) {
                    $cssid = $field['cssid'];
                }
            }
            if (is_null($text)) {
                $text = $this->get_fieldvalue($field);
            }
            if ($text) {
                $this->mform->addElement('heading', $text, $level, $class, $cssid);
            }
        }
    }

    /**
     * add section with no section label and containing only hidden fields
     * each field must have its own "add_hiddenfield_xxx()" method to set the value
     * typically, the add_hiddenfield_xxx() method calls the "add_hiddenfield()" method
     *
     * @param string $section name of section
     * @param array $fields names of fields
     * @todo Finish documenting this function
     */
    protected function add_section_hidden($section, $fields) {
        $params = array();

        // set "id" field name: chainid OR taskid
        $id = $this->recordtype.'id';

        foreach ($fields as $field) {
            if ($field=='id') {
                $name = $this->get_fieldname($id);
            } else {
                $name = $this->get_fieldname($field);
            }
            $value = $this->get_fieldvalue($field);
            $params[$name] = $value;
        }

        $params = $this->TC->merge_params($params);
        foreach ($params as $name => $value) {
            if ($value) {
                if ($name=='sesskey') {
                    continue; // sesskey will be added by mform
                }
                $this->mform->addElement('hidden', $name, $value);
                if (is_numeric($value)) {
                    $param_type = PARAM_INT;
                } else {
                    $param_type = PARAM_ALPHA;
                }
                $this->mform->setType($name, $param_type);
            }
        }
    }

    /**
     * add_field
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function add_field($field) {
        $method = 'add_field_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($field);
        } else {
            // default action is to add a text field
            $name = $this->get_fieldname($field);
            $label = $this->get_fieldlabel($field);
            $this->mform->addElement('text', $name, $label, array('size' => $this->text_field_size));
            $this->add_helpbutton($name, $field, 'taskchain');
            $this->set_type_text($field);
        }
    }

    /**
     * add_template_yesno
     *
     * @param string $field the field name
     * @param mixed $default
     * @param bool $advanced (optional, default=false)
     * @todo Finish documenting this function
     */
    protected function add_template_yesno($field, $advanced=false) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $this->mform->addElement('selectyesno', $name, $label);
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setDefault($name, $this->get_defaultvalue($field));
        $this->add_helpbutton($name, $field, 'taskchain');
        if ($advanced) {
            $this->mform->setAdvanced($name);
        }
    }

    /**
     * add_template_list
     *
     * @param string $field the field name
     * @param mixed $default
     * @param bool $advanced (optional, default=false)
     * @todo Finish documenting this function
     */
    protected function add_template_list($field, $advanced=false) {
        $name = $this->get_fieldname($field);
        $label = $this->get_fieldlabel($field);
        $list = $field.'s_list';
        $this->mform->addElement('select', $name, $label, taskchain_available::$list());
        $this->mform->setType($name, PARAM_INT);
        $this->mform->setDefault($name, $this->get_defaultvalue($field));
        $this->add_helpbutton($name, $field, 'taskchain');
        if ($advanced) {
            $this->mform->setAdvanced($name);
        }
    }

    /////////////////////////////////////////////////////////
    // validate sections and fields
    /////////////////////////////////////////////////////////

    /**
     * validate_sections
     *
     * @param array $errors (passed by reference)
     * @param array $data (passed by reference)
     * @param array $files (passed by reference)
     * @return void may modify $errors and $data
     * @todo Finish documenting this function
     */
    public function validate_sections(&$errors, &$data, &$files) {
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $this->validate_section($errors, $data, $files, $section, $fields);
        }
    }

    /**
     * validate_section
     *
     * @param array $errors (passed by reference)
     * @param array $data (passed by reference)
     * @param array $files (passed by reference)
     * @param string $section name of section
     * @param array $fields within this section
     * @return void may modify $errors and $data
     * @todo Finish documenting this function
     */
    protected function validate_section(&$errors, &$data, &$files, $section, $fields) {
        $method = 'validate_section_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($errors, $data, $files);
        } else {
            foreach ($fields as $field) {
                $this->validate_field($errors, $data, $files, $field);
            }
        }
    }

    /**
     * validate_field
     *
     * @param array $errors (passed by reference)
     * @param array $data (passed by reference)
     * @param array $files (passed by reference)
     * @param string $field name of field
     * @return void may modify $errors and $data
     * @todo Finish documenting this function
     */
    protected function validate_field(&$errors, &$data, &$files, $field) {
        $method = 'validate_field_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($errors, $data, $files);
        }
    }

    /////////////////////////////////////////////////////////
    // fix sections and fields
    /////////////////////////////////////////////////////////

    /**
     * fix_sections
     *
     * @param array $data (passed by reference)
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    public function fix_sections(&$data) {
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $this->fix_section($data, $section, $fields);
        }
    }

    /**
     * fix_section
     *
     * @param array $data (passed by reference)
     * @param string $section name of section
     * @param array $fields within this section
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_section(&$data, $section, $fields) {
        $method = 'fix_section_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($data, $section, $fields);
        } else {
            foreach ($fields as $field) {
                $this->fix_field($data, $field);
            }
        }
    }

    /**
     * fix_field
     *
     * @param array $data (passed by reference)
     * @param string $field name of field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_field(&$data, $field) {
        if ($this->is_multiple) {
            $id = $this->get_fieldvalue('id');
            if (isset($data->$field) && is_array($data->$field) && array_key_exists($id, $data->$field)) {
                $name = $this->get_fieldname($field);
                if ($this->mform->elementExists($name)) {
                    $value = $this->get_fieldvalue($field);
                    $data->{$field}[$id] = $value;
                    $this->mform->getElement($name)->setValue($value);
                } else {
                    unset($data->{$field}[$id]); // e.g. $data->sortorder[99]
                }
            }
        } else {
            $method = 'fix_field_'.$field;
            if (method_exists($this, $method)) {
                $this->$method($data, $field);
            }
        }
    }

    /**
     * fix_template_notnull
     *
     * @param array $data (passed by reference)
     * @param string $field name of field
     * @return void may modify $data
     * @todo Finish documenting this function
     */
    protected function fix_template_notnull(&$data, $field, $default) {
        if (! isset($data->$field)) {
            $data->$field = $default;
        }
    }


    /////////////////////////////////////////////////////////
    // format sections and fields ...
    /////////////////////////////////////////////////////////

    /**
     * format_section_labels
     *
     * @todo Finish documenting this function
     */
    protected function format_section_labels() {
        $this->format_sectionlabel('labels');
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $method = 'format_sectionlabel_'.$section;
            if (method_exists($this, $method)) {
                $this->$method($fields);
            } else {
                foreach ($fields as $field) {
                    $this->format_fieldlabel($field);
                }
            }
        }
    }

    /**
     * format_section_defaults
     *
     * @todo Finish documenting this function
     */
    protected function format_section_defaults() {
        $this->format_sectionlabel('defaults');
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $method = 'format_section_defaults_'.$section;
            if (method_exists($this, $method)) {
                $this->$method($fields);
            } else {
                foreach ($fields as $field) {
                    $this->format_defaultfield($field);
                }
            }
        }
    }

    /**
     * format_section_selects
     *
     * @todo Finish documenting this function
     */
    protected function format_section_selects() {
        $this->format_sectionlabel('selects');
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $method = 'format_section_selects_'.$section;
            if (method_exists($this, $method)) {
                $this->$method($fields);
            } else {
                foreach ($fields as $field) {
                    $this->format_selectfield($field);
                }
            }
        }
    }

    /**
     * format_section_label
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel($section) {
        $method = 'format_sectionlabel_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($section);
        } else {
            $label = $this->get_sectionlabel($section);
            $this->mform->addElement('header', $section.'hdr', $label);
        }
    }

    /**
     * format_sectionlabel_labels
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel_labels($section) {
        $this->mform->addElement('header', $section, '');
    }

    /**
     * format_sectionlabel_defaults
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel_defaults($section) {
        $this->mform->addElement('header', $section, '');
    }

    /**
     * format_sectionlabel_selects
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel_selects($section) {
        $this->mform->addElement('header', $section, '');
    }

    /**
     * format_sectionlabel_record
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel_record($section) {
        $id = $this->get_fieldvalue('id');
        $this->mform->addElement('header', $section.$id, '');
    }

    /**
     * format_sectionlabel_hidden
     *
     * @param string $section name of section
     * @todo Finish documenting this function
     */
    protected function format_sectionlabel_hidden($section) {
        // do nothing
    }

    /**
     * format_fieldlabel
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_fieldlabel($field) {
        $method = 'format_fieldlabel_'.$field;
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $name = $field.'_label';
            $text = $this->get_fieldlabel($field).$this->get_helpicon($field);
            $text = html_writer::tag('span', $text, array('class' => 'headerfield'));
            $this->mform->addElement('static', $name, '', $text);
        }
    }

    /**
     * format_fieldlabel_defaultrecord
     *
     * @todo Finish documenting this function
     */
    protected function format_fieldlabel_defaultrecord() {
        $text = $this->get_fieldlabel('defaultrecord');
        $text = html_writer::tag('span', $text, array('class' => 'headerfield'));
        $this->mform->addElement('static', 'defaultrecord_label', '', $text);
    }

    /**
     * format_fieldlabel_select
     *
     * @todo Finish documenting this function
     */
    protected function format_fieldlabel_selectrecord() {
        $text = $this->get_fieldlabel('selectrecord');
        $text = html_writer::tag('span', $text, array('class' => 'headerfield'));
        $this->mform->addElement('static', 'selectrecord_label', '', $text);
    }

    /**
     * ajax_edit_onclick
     *
     * @param string  $field name of field
     * @param boolean $addformid (optional, default=false)
     * @todo Finish documenting this function
     */
    protected function ajax_edit_onclick($field, $addformid=false) {
        $id = $this->get_fieldvalue('id');
        $type = $this->recordtype;
        $params = array('id'      => $id,
                         'type'    => $type,
                         'field'   => $field,
                         'sesskey' => sesskey());
        $helper = new moodle_url('/mod/taskchain/edit/form/helper.js.php', $params);

        $params = array($helper, $field.'['.$id.']');
        if ($addformid) {
            $params[] = $field.'['.$id.']';
        }
        return 'TC.request("'.implode('", "', $params).'"); return false;';
    }

    /**
     * format_fieldvalue
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_fieldvalue($field, $value) {
        global $CFG, $OUTPUT, $PAGE;

        static $edit = null;
        if ($edit===null) {
            if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
                $edit = (isset($_POST) && count($_POST));
            } else {
                $filepath = '/mod/taskchain/edit/form/helper.js';
                if ($edit = file_exists($CFG->dirroot.$filepath)) {
                    $PAGE->requires->js($filepath);
                }
            }
        }

        $method = 'format_fieldvalue_'.$field;
        if (method_exists($this, $method)) {
            $value = $this->$method($field, $value);
        }

        if ($edit) {
            $name  = $this->get_fieldname($field);
            $label = $this->get_fieldlabel($field);
            $types = $this->recordtype.'s';

            $idfield = $this->recordtype.'id'; // e.g. "chainid" or "taskid"
            $idvalue = $this->get_fieldvalue('id');
            $params  = array($idfield => $idvalue, 'field' => $field);
            $href    = $this->TC->url->edit($types, $params);

            if (file_exists($CFG->dirroot.'/pix/t/editstring.png')) {
                // Moodle >= 2.3
                $icon = 't/editstring';
            } else {
                // Moodle <= 2.2
                $icon = 't/edit';
            }

            $onclick = $this->ajax_edit_onclick($field);
            $params  = array('title' => $label, 'onclick' => $onclick);

            $icon    = $OUTPUT->pix_icon($icon, get_string('edit'));
            $value  .= ($value=='' ? '' : ' ').html_writer::link($href, $icon, $params);
        }

        return $value;
    }

    /**
     * format_defaultfield
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield($field) {
        $method = 'format_defaultfield_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($field);
        } else {
            $name = 'defaultfield_'.$field;
            $text = $this->format_fieldvalue($field, $this->get_defaultvalue($field));
            $text = html_writer::tag('span', $text, array('class' => 'defaultfield'));
            $this->mform->addElement('static', $name, '', $text);
        }
    }

    /**
     * format_defaultfield_id
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_id($field) {
        // do nothing
    }

    /**
     * format_defaultfield_sortorder
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_sortorder($field) {
        $name = 'defaultfield_sortorder';
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * format_defaultfield_edit
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_edit($field) {
        $name = 'defaultfield_'.$field;
        $this->mform->addElement('static', $name, '', '');
    }


    /**
     * format_defaultfield_defaultrecord
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_defaultrecord($field) {
        $this->mform->addElement('radio', $field, null, null, 0);
    }

    /**
     * format_defaultfield_selectrecord
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_defaultfield_selectrecord($field) {
        $name = 'defaultfield_'.$field;
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * format_selectfield
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield($field) {
        $method = 'format_selectfield_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($field);
        } else {
            $name = 'selectfield_'.$field;
            $this->mform->addElement('checkbox', $name, '');
        }
    }

    /**
     * format_selectfield_id
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_id($field) {
        // do nothing
    }

    /**
     * format_selectfield_sortorder
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_sortorder($field) {
        $name = 'selectfield_'.$field;
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * format_selectfield_edit
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_edit($field) {
        $name = 'selectfield_'.$field;
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * format_selectfield_default
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_defaultrecord($field) {
        $name = 'selectfield_'.$field;
        $this->mform->addElement('static', $name, '', '');
    }

    /**
     * format_selectfield_selectrecord
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_selectfield_selectrecord($field) {

        // element names
        $name_rows = $field.'_all';
        $name_cols = 'selectfield_all';
        $name_elements = $field.'_elements';

        // javascript to (de)select all rows/cols
        $js = '';
        $js .= '<script type="text/javascript">'."\n";
        $js .= "//<![CDATA[\n";
        $js .= "    function selectAll(checkbox, nameprefix) {\n";
        $js .= "        var target = new RegExp('^' + nameprefix + '[^a-zA-Z0-9]');\n";
        $js .= "        var inputs = document.getElementsByTagName('input');\n";
        $js .= "        for (var i=0; i<inputs.length; i++) {\n";
        $js .= "            if(inputs[i].type && inputs[i].type=='checkbox') {\n";
        $js .= "                if(inputs[i].name && inputs[i].name.match(target)) {\n";
        $js .= "                    inputs[i].checked = checkbox.checked;\n";
        $js .= "                }\n";
        $js .= "            }\n";
        $js .= "        }\n";
        $js .= "        inputs = null;\n";
        $js .= "    }\n";
        $js .= "    var obj = document.getElementById('id_".$name_rows."');\n";
        $js .= "    if (obj) {\n";
        $js .= "        obj.onclick = function() { selectAll(this, 'selectrecord') }\n";
        $js .= "    }\n";
        $js .= "    var obj = document.getElementById('id_".$name_cols."');\n";
        $js .= "    if (obj) {\n";
        $js .= "        obj.onclick = function() { selectAll(this, 'selectfield') }\n";
        $js .= "    }\n";
        $js .= "//]]>\n";
        $js .= '</script>'."\n";

        $elements = array();
        $elements[] = $this->mform->createElement('checkbox', $name_rows, '');
        $elements[] = $this->mform->createElement('checkbox', $name_cols, '');
        $elements[] = $this->mform->createElement('static', '', '', $js);
        $this->mform->addGroup($elements, $name_elements, '', ' ', false);
    }

    /**
     * format_sections
     *
     * @todo Finish documenting this function
     */
    protected function format_sections() {
        $this->format_sectionlabel('record');
        $sections = $this->get_sections();
        foreach ($sections as $section => $fields) {
            $this->format_section($section, $fields);
        }
    }

    /**
     * format_section
     *
     * @param string $section name of section
     * @param array $fields names of fields
     * @todo Finish documenting this function
     */
    protected function format_section($section, $fields) {
        $method = 'format_section_'.$section;
        if (method_exists($this, $method)) {
            $this->$method($section, $fields);
        } else {
            foreach ($fields as $field) {
                $this->format_field($field);
            }
        }
    }

    /**
     * format_section_hidden
     *
     * @param string $section name of section
     * @param array $fields names of fields
     * @todo Finish documenting this function
     */
    protected function format_section_hidden($section, $fields) {
        // do nothing
    }

    /**
     * format_field
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_field($field) {
        $method = 'format_field_'.$field;
        if (method_exists($this, $method)) {
            $this->$method($field);
        } else {
            $name  = $this->get_fieldname($field);
            $value = $this->get_fieldvalue($field);
            $value = $this->format_fieldvalue($field, $value);
            $this->mform->addElement('static', $name, '', $value);
        }
    }

    /**
     * format_field_sortorder
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_field_sortorder($field) {
        $this->add_field_sortorder($field);
    }

    /**
     * format_field_edit
     *
     * @param string $field name of field
     * @todo Finish documenting this function
     */
    protected function format_field_edit($field) {
        global $CFG, $output;
        switch ($this->recordtype) {

            case 'task':
                $commands = array('update', 'delete', 'preview');
                $scripts = array('edit/task.php', 'edit/task.php', 'attempt.php');
                $params = array(
                    'cnumber'      => mod_taskchain::FORCE_NEW_ATTEMPT,
                    'taskscoreid'  => 0,    'taskattemptid'  => 0, 'tnumber' => 0,
                    'taskid'       => $this->get_fieldvalue('id'),
                    'conditionid'  => 0,    'conditiontype'  => 0, 'inpopup' => 0
                );
                $text = $output->commands($commands, $scripts, 'taskid', $params, false);
                //$params = array('taskid'=>$this->get_fieldvalue('id'), 'cnumber'=>0);
                //$text .= $output->commands(array('preview'), 'attempt.php', '', $params, false);
                break;

            case 'chain':
                $commands = array();
                $scripts = array();
                $params = array();

                $base_params = array(
                    'courseid'     => 0, 'coursemoduleid' => 0,
                    'taskchainid'  => 0, 'chainid'        => 0, 'taskid'   => 0,
                    'chaingradeid' => 0, 'chainattemptid' => 0, 'cnumber'  => 0,
                    'taskscoreid'  => 0, 'taskattemptid'  => 0, 'tnumber'  => 0,
                    'conditionid'  => 0, 'conditiontype'  => 0, 'inpopup'  => 0,
                    'columnlistid' =>'', 'columnlisttype' =>'', 'tab'      => ''
                );

                $commands[] = 'update';
                $scripts[] = $CFG->wwwroot.'/course/modedit.php';
                $params[] = array_merge($base_params, array('update' => $this->TC->coursemodule->id, 'return' => 1));

                $commands[] = 'delete';
                $scripts[] = $CFG->wwwroot.'/course/mod.php';
                $params[] = array_merge($base_params, array('delete' => $this->TC->coursemodule->id));

                $commands[] = 'preview';
                $scripts[] = 'view.php';
                $params[] = array_merge($base_params, array('cnumber' => mod_taskchain::FORCE_NEW_ATTEMPT, 'chainid' => $this->get_fieldvalue('id')));

                $text = $output->commands($commands, $scripts, '', $params, false);
                break;

            default:
                $text = ''; // shouldn't happen !!
        }

        if ($text) {
            $name = $this->get_fieldname($field);
            $this->mform->addElement('static', $name, '', $text);
        }
    }

    /**
     * format_field_defaultrecord
     *
     * @todo Finish documenting this function
     */
    protected function format_field_defaultrecord($field) {
        $value = $this->get_fieldvalue('id');
        $this->mform->addElement('radio', $field, null, null, $value);
    }

    /**
     * format_field_selectrecord
     *
     * @todo Finish documenting this function
     */
    protected function format_field_selectrecord($field) {
        $name = $this->get_fieldname($field);
        $formelement = $this->mform->addElement('checkbox', $name, '');
    }

    /**
     * format_templatevalue_list
     *
     * @param string $field to be formatted
     * @param string $value of the field to be formatted
     * @param string $type (optional, default="") list of available strings
     * @return string human readable form of $field value
     * @todo Finish documenting this function
     */
    protected function format_templatevalue_list() {
        $args = func_get_args();
        $field = array_shift($args);
        $value = array_shift($args);
        $type  = array_shift($args);
        if ($type) {
            $list = $type.'s_list';
        } else {
            $list = $field.'s_list';
        }
        $callback = array('taskchain_available', $list);
        $list = call_user_func_array($callback, $args);
        if (array_key_exists($value, $list)) {
            return $list[$value];
        } else {
            return $value; // unrecognized value - shouldn't happen !!
        }
    }

    /**
     * format_templatevalue_time
     *
     * @param string name of $field to format
     * @param mixed value to be formatted
     * @param string $fmt (optional, default='strftimedatetime')
     * @param string $notime (optional, default='')
     * @todo Finish documenting this function
     */
    protected function format_templatevalue_time($field, $value, $fmt='strftimedatetime', $notime='') {
        if (empty($value)) {
            return $notime;
        }
        if (is_numeric($value)) {
            return userdate($value, get_string($fmt));
        }
        return $value; // probably $value has already been formatted - shouldn't happen !!
    }

    /**
     * format_templatevalue_timer
     *
     * @param string name of $field to format
     * @param mixed value to be formatted
     * @param string $fmt (optional, default='strftimedatetime')
     * @param string $notime (optional, default='')
     * @todo Finish documenting this function
     */
    protected function format_templatevalue_timer($field, $value, $fmt='', $notime='') {
        if ($value > 0) {
           return format_time($value, $fmt);
        } else {
            return $notime;
        }
    }

    /**
     * format_templatevalue_yesno
     *
     * @param string $field to be formatted
     * @param string $value of the field to be formatted
     * @return string human readable form of $field value
     * @todo Finish documenting this function
     */
    protected function format_templatevalue_yesno($field, $value) {
        return get_string(empty($value) ? 'no' : 'yes');
    }

    /**
     * format_longtext
     *
     * if activity/task name is longer than $textlength, it will be truncated
     * to first $headlength chars + " ... " + last $taillength chars
     *
     * @param string $text of activity
     * @param integer $textlength (optional, default=40)
     * @param integer $headlength (optional, default=16)
     * @param integer $taillength (optional, default=16)
     * @param string formatted name, possibly truncated to $textlength chars
     * @todo Finish documenting this function
     */
     protected function format_longtext($text, $textlength=40, $headlength=16, $taillength=16) {
        $text = format_string($text);
        $strlen = mod_taskchain::textlib('strlen', $text);
        if ($strlen > $textlength) {
            $headlength = min($headlength, $strlen);
            $taillength = min($taillength, $strlen - $headlength - 3);
            $head = mod_taskchain::textlib('substr', $text, 0, $headlength);
            $tail = mod_taskchain::textlib('substr', $text, $strlen - $taillength, $taillength);
            $text = $head.' ... '.$tail;
        }
        return $text;
     }

    /////////////////////////////////////////////////////////
    // get javascript
    /////////////////////////////////////////////////////////

    /**
     * return javascript to be inserted in footer of page
     *
     * @return string
     */
    public function get_js() {
        return '';
    }


    /**
     * add_action_buttons
     *
     * @param bool $cancel whether to show cancel button, default true
     * @param string $submit label for submit button, defaults to get_string('savechanges')
     */
    public function add_action_buttons($cancel=true, $submit=null) {
        if ($cancel===true) {
            $cancel = get_string('cancel');
        }
        if ($submit===null) {
            $submit = ($this->singlefield ? get_string('save', 'admin') : get_string('savechanges'));
        }
        if ($this->singlefield) {
            $params = array('onclick' => $this->ajax_edit_onclick($this->singlefield, true));
        } else {
            $params = null;
        }
        if ($cancel) {
            $elements = array(
                $this->mform->createElement('submit', 'submitbutton', $submit, $params),
                $this->mform->createElement('cancel', 'cancelbutton', $cancel)
            );
            $name = 'actionbuttons';
            $this->mform->addGroup($elements, $name, '', array(' '), false);
            $this->mform->closeHeaderBefore($name);
        } else {
            $name = 'submitbutton';
            $this->mform->addElement('submit', $name, $submit, $params);
            $this->mform->closeHeaderBefore($name);
        }
    }

    /**
     * add_helpbutton
     *
     * @param string $fieldname
     * @param string $stringname
     * @param string $component
     */
    public function add_helpbutton($fieldname, $stringname, $component) {
        if ($this->singlefield=='') {
            $this->mform->addHelpButton($fieldname, $stringname, $component);
        }
    }

    /**
     * add_helpbutton
     *
     * @param string $field
     */
    public function get_helpicon($field) {
        global $OUTPUT;

        $method = 'get_helpicon_'.$field;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return ' '.$OUTPUT->help_icon($field, 'taskchain');
    }

    /**
     * add_helpbutton_edit
     */
    public function get_helpicon_edit() {
        return '';
    }
}
