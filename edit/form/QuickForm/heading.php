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
 * static heading element
 *
 * Contains class for static type element
 *
 * @package   core_form
 * @author    Gordon Bateson <gordonbateson@gmail.com> (based on "warning" by Jamie Pratt)
 * @copyright 2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('HTML/QuickForm/static.php');

/**
 * static heading
 *
 * overrides {@link HTML_QuickForm_static} to display staic heading.
 *
 * @package   core_form
 * @category  form
 * @author    Gordon Bateson <gordonbateson@gmail.com> (based on "MoodleQuickForm_warning" by Jamie Pratt)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_heading extends HTML_QuickForm_static{
    /** @var string Form element type */
    public $_elementTemplateType = 'warning'; // Defined in lib/forms.

    /** @var string html for help button, if empty then no help */
    public $_helpbutton = '';

    /** @var int heading level (1-6), default is null (=2) */
    public $_level = '';

    /** @var string heading class, default is null (="main") */
    public $_class = '';

    /** @var string heading css id, default is null (="") */
    public $_cssid = '';

    /**
     * constructor
     *
     * @param string $text heading text (optional, default=null)
     * @param string $level heading level (optional, default=null)
     * @param string $class heading class (optional, default=null)
     * @param string $cssid heading css id (optional, default=null)
     */
    public function __construct($text = null, $level = null, $class = null, $cssid = null) {
        parent::__construct('', null, $text); // Name='', label=null.
        $this->_type  = 'heading';
        $this->_level = $level;
        $this->_class = $class;
        $this->_cssid = $cssid;
    }

    /*
     * Old constructor for Moodle <= 2.9
     */
    /**
     * MoodleQuickForm_heading
     *
     * @param mixed $text (optional, default=null)
     * @param mixed $level (optional, default=null)
     * @param mixed $class (optional, default=null)
     * @param mixed $cssid (optional, default=null)
     */
    public function MoodleQuickForm_heading($text = null, $level = null, $class = null, $cssid = null) {
        parent::HTML_QuickForm_static(null, null, $text); // Name=null, label=null.
        $this->_type  = 'heading';
        $this->_level = $level;
        $this->_class = $class;
        $this->_cssid = $cssid;
    }

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    public function toHtml() {
        global $OUTPUT;
        return $OUTPUT->heading($this->_text, $this->_level, $this->_class, $this->_cssid);
    }

    /**
     * get html for help button
     *
     * @return string html for help button
     */
    public function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * Gets the type of form element
     *
     * @return string
     */
    public function getElementTemplateType() {
        return $this->_elementTemplateType;
    }
}

MoodleQuickForm::registerElementType('heading', __FILE__, 'MoodleQuickForm_heading');
