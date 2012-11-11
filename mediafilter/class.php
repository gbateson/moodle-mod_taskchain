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
 * mod/taskchain/mediafilter/class.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// get the standard Moodle mediaplugin filter

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/filter/mediaplugin/filter.php');

/**
 * taskchain_mediafilter
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_mediafilter {

    // media filetypes that this filter can handle
    // this initial list is of the file types that Moodle's standard mediaplugin can handle
    // media file types specified by individual media players will be added to this list
    public $media_filetypes = array(
        // any params allowed (flash audio/video, html5 audio/video)
        'aac'=>'any', 'f4v'=>'any', 'flv'=>'any', 'm4a'=>'any', 'm4v'=>'any',
        'mp4'=>'any', 'oga'=>'any', 'ogg'=>'any', 'ogv'=>'any', 'webm'=>'any',
        // only "d=WIDTHxHEIGHT" param allowed in moodle filter
        'avi'=>'size', 'm4v'=>'size', 'm4a'=>'size', 'mov'=>'size',
        'mp4'=>'size', 'mpeg'=>'size', 'mpg'=>'size', 'swf'=>'size', 'wmv'=>'size',
        // no params allowed in moodle filter
        'mp3'=>'none', 'ra'=>'none', 'ram'=>'none', 'rm'=>'none', 'rv'=>'none'
    );

    public $param_names = 'movie|src|url';
    //  wmp        : url
    //  quicktime  : src
    //  realplayer : src
    //  flash      : movie

    public $tagopen = '(?:(<)|(\\\\u003C))'; // left angle-bracket (uses two parenthese)
    public $tagchars = '(?(1)[^>]|(?(2).(?!\\\\u003E)))*?';  // string of chars inside the tag
    public $tagclose = '(?(1)>|(?(2)\\\\u003E))'; // right angle-bracket (to match the left one)
    public $tagreopen = '(?(1)<|(?(2)\\\\u003C))'; // another left angle-bracket (to match the first one)
    //$tagopen = '(?:(<)|(&lt;)|(&amp;#x003C;))';
    //$tagclose = '(?(2)>|(?(3)&gt;|(?(4)&amp;#x003E;)))';

    public $link_search = '';
    public $object_search = '';
    public $object_searches = array();

    public $js_inline = '';
    public $js_external = '';

    public $players  = array();
    public $defaultplayer = 'moodle';

    public $moodle_flashvars = array('waitForPlay', 'autoPlay', 'buffer');
// bgColour, btnColour, btnBorderColour,
    // iconColour, iconOverColour,
    // trackColour, handleColour, loaderColour,
    // waitForPlay, autoPlay, buffer

    // constructor function

    /**
     * taskchain_mediaplayer
     *
     * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @since      Moodle 2.0
     * @package    mod
     * @subpackage taskchain
     */
    class taskchain_mediaplayer {
    public $aliases = array();
    public $playerurl = '';
    public $flashvars = array();
    public $flashvars_paramname = '';
    public $querystring_paramname = '';
    public $options = array(
        'width' => 0, 'height' => 0, 'build' => 40,
        'quality' => 'high', 'majorversion' => '6', 'flashvars' => null
    );
    public $more_options = array();
    public $media_filetypes = array();
    public $spantext = '';
    public $removelink = true;

    /**
     * contructor for this class
     */
    public function __construct()  {
        $this->options = array_merge($this->options, $this->more_options);
    }

    /**
     * generate
     *
     * @uses $CFG
     * @param xxx $filetype
     * @param xxx $link
     * @param xxx $mediaurl
     * @param xxx $options
     * @return xxx
     * @todo Finish documenting this function
     */
    public function generate($filetype, $link, $mediaurl, $options)  {
        global $CFG;

        // cache language strings
        static $str;
        if (! isset($str->$filetype)) {
            $str->$filetype = $filetype.'audio'; // get_string($filetype.'audio', 'mediaplugin');
        }

        // $id must be unique to prevent it being stored in Moodle's text cache
        static $id_count = 0;
        $id = str_replace('taskchain_mediaplayer_', '', get_class($this)).'_'.time().sprintf('%02d', ($id_count++));

        // add movie id to $options, if necessary
        // this is required in order to allow Flash addCallback on IE
        // 2009/11/30 - it is not necessary for IE8, maybe not necessary at all
        //if (! isset($options['id'])) {
        //    $options['id'] = 'ufo_'.$id;
        //}

        // add movie url to $options, if necessary
        if (! isset($options['movie'])) {
            $options['movie'] = $this->playerurl;
            if ($this->querystring_paramname) {
                $options['movie'] .= '?'.$this->querystring_paramname.'='.$mediaurl;
            }
        }

        // do we need to make sure the mediaurl is added to flashvars?
        if ($this->flashvars_paramname && empty($options['skipmediaurl'])) {
            $find_mediaurl = true;
        } else {
            $find_mediaurl = false;
        }

        // get list of option names to be cleaned
        $search = '/^player|playerurl|querystring_paramname|flashvars_paramname|skipmediaurl$/i';
        $names = preg_grep($search, array_keys($options), PREG_GREP_INVERT);

        // clean the options
        foreach ($names as $name) {

            switch ($name) {

                case 'id':
                    // allow a-z A-Z 0-9 and underscore (could use PARAM_SAFEDIR, but that allows hyphen too)
                    $options[$name] = preg_replace('/\W/', '', $options[$name]);
                    break;

                case 'movie':
                    // clean_param() will reject url if it contains spaces
                    $options[$name] = str_replace(' ', '%20', $options[$name]);
                    $options[$name] = clean_param($options[$name], PARAM_URL);
                    break;

                case 'flashvars':

                    // split flashvars into an array
                    $flashvars = str_replace('&amp;', '&', $options[$name]);
                    $flashvars = explode('&', $flashvars);

                    // loop through $flashvars, cleaning as we go
                    $options[$name] = array();
                    $found_mediaurl = false;
                    foreach ($flashvars as $flashvar) {
                        if (trim($flashvar)=='') {
                            continue;
                        }
                        list($n, $v) = explode('=', $flashvar, 2);
                        $n = clean_param($n, PARAM_ALPHANUM);
                        if ($n==$this->flashvars_paramname) {
                            $found_mediaurl = true;
                            $options[$name][$n] = clean_param($v, PARAM_URL);
                        } else if (array_key_exists($n, $this->flashvars)) {
                            $options[$name][$n] = clean_param($v, $this->flashvars[$n]);
                        } else {
                            // $flashvar not defined for this media player so ignore it
                        }
                    }

                    // add media url to flashvars, if necessary
                    if ($find_mediaurl && ! $found_mediaurl) {
                        $n = $this->flashvars_paramname;
                        $options[$name][$n] = clean_param($mediaurl, PARAM_URL);
                    }

                    // add flashvars values passed via $options
                    foreach ($this->flashvars as $n => $type) {
                        if (isset($options[$n])) {
                            $options[$name][$n] = clean_param($options[$n], $type);
                            unset($options[$n]);
                        }
                    }

                    // rebuild $flashvars
                    $flashvars = array();
                    foreach ($options[$name] as $n => $v) {
                        $flashvars[] = "$n=".$v; // urlencode($v);
                    }

                    // join $namevalues back together
                    $options[$name] = implode('&', $flashvars);
                    unset($flashvars);
                    break;

                default:
                    $quote = '';
                    if (isset($options[$name])) {
                        $value = $options[$name];
                        if (preg_match('/^(\\\\*["'."']".')?(.*)'.'$1'.'$/', $value, $matches)) {
                            $quote = $matches[1];
                            $value = $matches[2];
                        }
                        $options[$name] = $quote.clean_param($value, PARAM_ALPHANUM).$quote;
                    }
            } // end switch $name
        } // end foreach $names

        // re-order options ("movie" first, "flashvars" last)
        $names = array_merge(
            array('id'), array('movie'),
            preg_grep('/^id|movie|flashvars$/i', $names, PREG_GREP_INVERT),
            array('flashvars')
        );

        $args = array();
        $properties = array();
        foreach ($names as $name) {
            if (empty($options[$name])) {
                continue;
            }
            $args[$name] = $options[$name];
            $properties[] = $name.':"'.$this->obfuscate_js(addslashes_js($options[$name])).'"';
        }
        $properties = implode(',', $properties);

        if (strlen($this->spantext)) {
            $spantext = $this->spantext;
        } else {
            $size = '';
            if (isset($options['width'])) {
                $size .= ' width="'.$options['width'].'"';
            }
            if (isset($options['height'])) {
                $size .= ' height="'.$options['height'].'"';
            }
            $spantext = '<img src="'.$CFG->wwwroot.'/pix/spacer.gif"'.$size.' alt="'.$str->$filetype.'" />';
        }

        return $link
            .'<span class="mediaplugin mediaplugin_'.$filetype.'" id="'.$id.'">'.$spantext.'</span>'."\n"
            .'<script type="text/javascript">'."\n"
            .'//<![CDATA['."\n"
            .'  var FO = { '.$properties.' };'."\n"
            .'  UFO.create(FO, "'.$this->obfuscate_js($id).'");'."\n"
            .'  UFO.main("'.$this->obfuscate_js($id).'");'."\n"
            .'//]]>'."\n"
            .'</script>'
        ;
    }

    /**
     * obfuscate_js
     *
     * @uses $CFG
     * @param xxx $str
     * @return xxx
     * @todo Finish documenting this function
     */
    public function obfuscate_js($str)  {
        global $CFG;

        if (empty($CFG->taskchain_enableobfuscate)) {
            return $str;
        }

        $obfuscated = '';
        $strlen = strlen($str);
        for ($i=0; $i<$strlen; $i++) {
            if ($i==0 || mt_rand(0,2)) {
                $obfuscated .= '\\u'.sprintf('%04X', ord($str{$i}));
            } else {
                $obfuscated .= $str{$i};
            }
        }
        return $obfuscated;
    }
}
