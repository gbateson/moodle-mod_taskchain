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
require_once($CFG->dirroot.'/mod/taskchain/lib.php');

// admin_setting_xxx classes are defined in "lib/adminlib.php"
// new admin_setting_configcheckbox($name, $visiblename, $description, $defaultsetting);

// show TaskChains on MyMoodle page (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enablemymoodle', get_string('enablemymoodle', 'taskchain'), get_string('configenablemymoodle', 'taskchain'), 1)
);

// enable caching of browser content for each task (default=1)
$str = get_string('clearcache', 'taskchain');
$url = new moodle_url('/mod/taskchain/utilities/clear_cache.php', array('sesskey' => sesskey()));
$link = html_writer::link($url, $str, array('class' => 'small', 'style'=> 'white-space: nowrap', 'onclick' => "this.target='_blank'"))."\n";
$settings->add(
    new admin_setting_configcheckbox('taskchain_enablecache', get_string('enablecache', 'taskchain'), get_string('configenablecache', 'taskchain').' '.$link, 1)
);

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

unset($str, $url, $link);

// restrict cron job to certain hours of the day (default=never)
$timezone = get_user_timezone_offset();
if (abs($timezone) > 13) {
    $timezone = 0;
} else if ($timezone>0) {
    $timezone = $timezone - 24;
}
$options = array();
for ($i=0; $i<=23; $i++) {
    $options[($i - $timezone) % 24] = gmdate('H:i', $i * HOURSECS);
}
$settings->add(
    new admin_setting_configmultiselect('taskchain_enablecron', get_string('enablecron', 'taskchain'), get_string('configenablecron', 'taskchain'), array(), $options)
);
unset($timezone, $options);

// enable embedding of swf media objects intaskchain tasks (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enableswf', get_string('enableswf', 'taskchain'), get_string('configenableswf', 'taskchain'), 1)
);

// enable obfuscation of javascript in html files (default=1)
$settings->add(
    new admin_setting_configcheckbox('taskchain_enableobfuscate', get_string('enableobfuscate', 'taskchain'), get_string('configenableobfuscate', 'taskchain'), 1)
);

// taskchain navigation frame height (default=85)
$settings->add(
    new admin_setting_configtext('taskchain_frameheight', get_string('frameheight', 'taskchain'), get_string('configframeheight', 'taskchain'), 85, PARAM_INT, 4)
);

// lock taskchain navigation frame so it is not scrollable (default=0)
$settings->add(
    new admin_setting_configcheckbox('taskchain_lockframe', get_string('lockframe', 'taskchain'), get_string('configlockframe', 'taskchain'), 0)
);

// store raw xml details of TaskChain task attempts (default=1)
$str = get_string('cleardetails', 'taskchain');
$url = new moodle_url('/mod/taskchain/utilities/clear_details.php', array('sesskey' => sesskey()));
$link = html_writer::link($url, $str, array('class' => 'small', 'style'=> 'white-space: nowrap', 'onclick' => "this.target='_blank'"))."\n";
$settings->add(
    new admin_setting_configcheckbox('taskchain_storedetails', get_string('storedetails', 'taskchain'), get_string('configstoredetails', 'taskchain').' '.$link, 0)
);

// maximum duration of a single calendar event (default=5 mins)
$setting = new admin_setting_configtext('taskchain_maxeventlength', get_string('maxeventlength', 'taskchain'), get_string('configmaxeventlength', 'taskchain'), 5, PARAM_INT, 4);
$setting->set_updatedcallback('taskchain_refresh_events');
$settings->add($setting);
