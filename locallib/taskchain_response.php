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
 * mod/taskchain/locallib/taskchain_response.php
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
 * taskchain_response
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class taskchain_response extends taskchain_base {

    /** db field: id (primary key, default=null) */
    private $id                  = null;

    /** db field: attemptid (integer, default=0) */
    private $attemptid           = 0;

    /** db field: questionid (integer, default=0) */
    private $questionid          = 0;

    /** db field: score (integer, default=0) */
    private $score               = 0;

    /** db field: weighting (integer, default=0) */
    private $weighting           = 0;

    /** db field: hints (integer, default=0) */
    private $hints               = 0;

    /** db field: clues (integer, default=0) */
    private $clues               = 0;

    /** db field: checks (integer, default=0) */
    private $checks              = 0;

    /** db field: correct (string (255), default='') */
    private $correct             = '';

    /** db field: wrong (string (255), default='') */
    private $wrong               = '';

    /** db field: ignored (string (255), default='') */
    private $ignored             = '';

    /**
     * get the "id" property
     *
     * @return primary key the current id $value
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * set the "id" property
     *
     * @param primary key the new id $value
     */
    public function set_id($value) {
        $this->id = $value;
    }

    /**
     * get the "attemptid" property
     *
     * @return integer the current attemptid $value
     */
    public function get_attemptid() {
        return $this->attemptid;
    }

    /**
     * set the "attemptid" property
     *
     * @param integer the new attemptid $value
     */
    public function set_attemptid($value) {
        $this->attemptid = $value;
    }

    /**
     * get the "questionid" property
     *
     * @return integer the current questionid $value
     */
    public function get_questionid() {
        return $this->questionid;
    }

    /**
     * set the "questionid" property
     *
     * @param integer the new questionid $value
     */
    public function set_questionid($value) {
        $this->questionid = $value;
    }

    /**
     * get the "score" property
     *
     * @return integer the current score $value
     */
    public function get_score() {
        return $this->score;
    }

    /**
     * set the "score" property
     *
     * @param integer the new score $value
     */
    public function set_score($value) {
        $this->score = $value;
    }

    /**
     * get the "weighting" property
     *
     * @return integer the current weighting $value
     */
    public function get_weighting() {
        return $this->weighting;
    }

    /**
     * set the "weighting" property
     *
     * @param integer the new weighting $value
     */
    public function set_weighting($value) {
        $this->weighting = $value;
    }

    /**
     * get the "hints" property
     *
     * @return integer the current hints $value
     */
    public function get_hints() {
        return $this->hints;
    }

    /**
     * set the "hints" property
     *
     * @param integer the new hints $value
     */
    public function set_hints($value) {
        $this->hints = $value;
    }

    /**
     * get the "clues" property
     *
     * @return integer the current clues $value
     */
    public function get_clues() {
        return $this->clues;
    }

    /**
     * set the "clues" property
     *
     * @param integer the new clues $value
     */
    public function set_clues($value) {
        $this->clues = $value;
    }

    /**
     * get the "checks" property
     *
     * @return integer the current checks $value
     */
    public function get_checks() {
        return $this->checks;
    }

    /**
     * set the "checks" property
     *
     * @param integer the new checks $value
     */
    public function set_checks($value) {
        $this->checks = $value;
    }

    /**
     * get the "correct" property
     *
     * @return string (255) the current correct $value
     */
    public function get_correct() {
        return $this->correct;
    }

    /**
     * set the "correct" property
     *
     * @param string (255) the new correct $value
     */
    public function set_correct($value) {
        $this->correct = $value;
    }

    /**
     * get the "wrong" property
     *
     * @return string (255) the current wrong $value
     */
    public function get_wrong() {
        return $this->wrong;
    }

    /**
     * set the "wrong" property
     *
     * @param string (255) the new wrong $value
     */
    public function set_wrong($value) {
        $this->wrong = $value;
    }

    /**
     * get the "ignored" property
     *
     * @return string (255) the current ignored $value
     */
    public function get_ignored() {
        return $this->ignored;
    }

    /**
     * set the "ignored" property
     *
     * @param string (255) the new ignored $value
     */
    public function set_ignored($value) {
        $this->ignored = $value;
    }
}

