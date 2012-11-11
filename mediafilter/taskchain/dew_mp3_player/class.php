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
 * mod/taskchain/mediafilter/taskchain/dew_mp3_player/class.php
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
 * taskchain_mediaplayer_dew_mp3_player
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_mediaplayer_dew_mp3_player extends taskchain_mediaplayer {
    public $aliases = array('dew');
    public $playerurl = 'dew_mp3_player/dewplayer.swf';
    public $querystring_paramname = 'mp3';
    public $more_options = array(
        'width' => 200, 'height' => 20, 'flashvars' => '',
        // 'bgcolor' => 'FFFFFF', 'wmode' => 'transparent',
        // 'autostart' => 0, 'autoreplay' => 0, 'showtime' => 0, 'randomplay' => 0, 'nopointer' => 0
    );
}
