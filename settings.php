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
 * mod/taskchain/settings.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/mod/taskchain/locallib.php');

// admin_setting_xxx classes are defined in "lib/adminlib.php"
// new admin_setting_xxx($name, $visiblename, $description, $defaultsetting);

// show TaskChains on MyMoodle page (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enablemymoodle', get_string('enablemymoodle', 'mod_taskchain'), get_string('configenablemymoodle', 'mod_taskchain'), 1)
);

// enable caching of browser content for each task (default=1)
$str = get_string('clearcache', 'mod_taskchain');
$url = new moodle_url('/mod/taskchain/tools/clear_cache.php', array('sesskey' => sesskey()));
$link = html_writer::link($url, $str, array('class' => 'small', 'style'=> 'white-space: nowrap', 'onclick' => "this.target='_blank'"))."\n";
$settings->add(
    new admin_setting_configcheckbox('taskchain_enablecache', get_string('enablecache', 'mod_taskchain'), get_string('configenablecache', 'mod_taskchain').' '.$link, 1)
);

// enable embedding of swf media objects intaskchain tasks (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enableswf', get_string('enableswf', 'mod_taskchain'), get_string('configenableswf', 'mod_taskchain'), 1)
);

// enable obfuscation of javascript in html files (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enableobfuscate', get_string('enableobfuscate', 'mod_taskchain'), get_string('configenableobfuscate', 'mod_taskchain'), 1)
);

// bodystyles
$options = array(
    mod_taskchain::BODYSTYLES_BACKGROUND => get_string('bodystylesbackground', 'mod_taskchain'),
    mod_taskchain::BODYSTYLES_COLOR      => get_string('bodystylescolor',      'mod_taskchain'),
    mod_taskchain::BODYSTYLES_FONT       => get_string('bodystylesfont',       'mod_taskchain'),
    mod_taskchain::BODYSTYLES_MARGIN     => get_string('bodystylesmargin',     'mod_taskchain')
);
$settings->add(
    new admin_setting_configmultiselect('taskchain_bodystyles', get_string('bodystyles', 'mod_taskchain'), get_string('configbodystyles', 'mod_taskchain'), array(), $options)
);

// taskchain navigation frame height (default=85)
$settings->add(
    new admin_setting_configtext('taskchain_frameheight', get_string('frameheight', 'mod_taskchain'), get_string('configframeheight', 'mod_taskchain'), 85, PARAM_INT, 4)
);

// lock taskchain navigation frame so it is not scrollable (default=0)
$settings->add(
    new admin_setting_configcheckbox('taskchain_lockframe', get_string('lockframe', 'mod_taskchain'), get_string('configlockframe', 'mod_taskchain'), 0)
);

// store raw xml details of TaskChain task attempts (default=1)
$str = get_string('cleardetails', 'mod_taskchain');
$url = new moodle_url('/mod/taskchain/tools/clear_details.php', array('sesskey' => sesskey()));
$link = html_writer::link($url, $str, array('class' => 'small', 'style'=> 'white-space: nowrap', 'onclick' => "this.target='_blank'"))."\n";
$settings->add(
    new admin_setting_configcheckbox('taskchain_storedetails', get_string('storedetails', 'mod_taskchain'), get_string('configstoredetails', 'mod_taskchain').' '.$link, 0)
);

// maximum duration of a single calendar event (default=5 mins)
$setting = new admin_setting_configtext('taskchain_maxeventlength', get_string('maxeventlength', 'mod_taskchain'), get_string('configmaxeventlength', 'mod_taskchain'), 5, PARAM_INT, 4);
$setting->set_updatedcallback('taskchain_refresh_events');
$settings->add($setting);

// dispose of temporary variables used above
unset($str, $url, $link, $options, $i);

