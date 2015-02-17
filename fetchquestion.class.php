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
 * fetch question class
 *
 * This class does the work of fetching a questions associated with a level of difficulty and within
 * a question category
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class fetchquestion {
    /**
     * The maximum number of attempts at finding a tag containing questions
     */
    const MAXTAGRETRY = 5;

    /**
     * The maximum number of tries at finding avaiable questions
     */
    const MAXNUMTRY = 100000;

    /** @var stdClass $adaptivequiz object, properties come from the adaptivequiz table */
    protected $adaptivequiz;

    /**
     * @var bool $debugenabled flag to denote developer debugging is enabled and this class should write message to the debug array
     */
    protected $debugenabled = false;

    /** @var array $debug array containing debugging information */
    protected $debug = array();

    /** @var array $tags an array of tags that used to identify eligible questions for the attempt */
    protected $tags = array();

    /** @var int $level the level of difficutly that will be used to fetch questions */
    protected $level = 1;

    /** @var string $questcatids a string of comma separated question category ids */
    protected $questcatids = '';

    /** @var int $minimumlevel the minimum level achievable in the attempt */
    protected $minimumlevel;

    /** @var int $maximumlevel the maximum level achievable in the attempt */
    protected $maximumlevel;

    /**
     * @var array $tagquestsum an array whose keys are difficulty numbers and values are the sum of questions associated with the
     *      difficulty level
     */
    protected $tagquestsum = array();

    /** @var bool $rebuild a flag used to force the rebuilding of the $tagquestsum property */
    public $rebuild = false;

    /**
     * Constructor initializes data required to retrieve questions associated with tag
     * and within question categories
     * @throws coding_exception throws a coding exception if $level is not a positive integer and if $maximumlevEl is greater
     *      than $minimumlevel
     * @param stdClass $adaptivequiz: adaptivequiz record object from adaptivequiz table
     * @param int $level level of difficuty to look for when fetching a question
     * @param int $minimumlevel the minimum level the student can achieve
     * @param int $maximumlevel the maximum level the student can achieve
     * @param array $tags an array of accepted tags
     */
    public function __construct($adaptivequiz, $level = 1, $minimumlevel, $maximumlevel, $tags = array()) {
        global $SESSION;

        $this->adaptivequiz = $adaptivequiz;
        $this->tags = $tags;
        $this->tags[] = ADAPTIVEQUIZ_QUESTION_TAG;
        $this->minimumlevel = $minimumlevel;
        $this->maximumlevel = $maximumlevel;

        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('Argument 2 is not an positive integer', 'Second parameter must be a positive integer');
        }

        if ($minimumlevel >= $maximumlevel) {
            throw new coding_exception('Minimum level is greater than maximum level',
                'Invalid minimum and maximum parameters passed');
        }

        $this->level = $level;

        // Initialize $tagquestsum property.
        if (!isset($SESSION->adpqtagquestsum)) {
            $SESSION->adpqtagquestsum = array();
            $this->tagquestsum = $SESSION->adpqtagquestsum;
        } else {
            $this->tagquestsum = $SESSION->adpqtagquestsum;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            $this->debugenabled = true;
        }
    }

    /**
     * This function sets the level of difficulty property
     * @param int $level level of difficulty
     * @return void
     */
    public function set_level($level = 1) {
        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('Argument 1 is not an positive integer', 'First parameter must be a positive integer');
        }

        $this->level = $level;
    }

    /**
     * This function returns the level of difficulty property
     * @return int - level of difficulty
     */
    public function get_level() {
        return $this->level;
    }

    /**
     * Reset the maximum question level to search for to a new value
     *
     * @param int $maximumlevel
     * @return void
     * @throws coding_exception if the maximum level is less than minimum level
     */
    public function set_maximum_level($maximumlevel) {
        if ($maximumlevel < $this->minimumlevel) {
            throw new coding_exception('Maximum level is less than minimum level', 'Invalid maximum level set.');
        }
        $this->maximumlevel = $maximumlevel;
    }

    /**
     * Reset the maximum question level to search for to a new value
     *
     * @param int $maximumlevel
     * @return void
     * @throws coding_exception if the minimum level is less than maximum level
     */
    public function set_minimum_level($minimumlevel) {
        if ($minimumlevel > $this->maximumlevel) {
            throw new coding_exception('Minimum level is less than maximum level', 'Invalid minimum level set.');
        }
        $this->minimumlevel = $minimumlevel;
    }

    /**
     * This functions adds a message to the debugging array
     * @param string $message: details of the debugging message
     * @return void
     */
    protected function print_debug($message = '') {
        if ($this->debugenabled) {
            $this->debug[] = $message;
        }
    }

    /**
     * Answer a string view of a variable for debugging purposes
     * @param mixed $variable
     */
    protected function vardump($variable) {
        ob_start();
        var_dump($variable);
        return ob_get_clean();
    }

    /**
     * This function returns the debug array
     * @return array - array of debugging messages
     */
    public function get_debug() {
        return $this->debug;
    }

    /**
     * This functions returns the $tagquestsum class property
     * @return array an array whose keys are difficulty levels and values are the sum of questions associated with the difficulty
     */
    public function get_tagquestsum() {
        return $this->tagquestsum;
    }

    /**
     * This functions sets the $tagquestsum class property
     * @param array an array whose keys are difficulty levels and values are the sum of questions associated with the difficulty
     */
    public function set_tagquestsum($tagquestsum) {
        $this->tagquestsum = $tagquestsum;
    }

    /**
     * This function decrements 1 from the sum of questions in a difficulty level
     * @param array $tagquestsum an array equal to the $tagquestsum property, where the key is the difficulty level and the value
     *      is the total number of
     * questions associated with it.  This parameter will be modified.
     * @param int $level the difficulty level
     * @return array an array whose keys are difficulty levels and values are the sum of questions associated with the difficulty
     */
    public function decrement_question_sum_from_difficulty($tagquestsum, $level) {
        if (array_key_exists($level, $tagquestsum)) {
            $tagquestsum[$level] -= 1;
        }

        return $tagquestsum;
    }

    /**
     * This function first checks if the session variable already contains a mapping of difficulty levels and the number of
     * questions associated with each level. Otherwise it constructos a mapping of difficulty levels and the number of questions
     * in each difficulty level.
     * @param array $tagquestsum an array equal to the $tagquestsum property, where the key is the difficulty level and the value
     *      is the total number of
     * questions associated with it. This parameter will be modified.
     * @param array $tags an array of tags used by the activity
     * @param int $min the minimum difficulty allowed for the attempt
     * @param int $max the maximum difficulty allowed for the attempt
     * @param bool $rebuild true to force the rebuilding the difficulty question count array, otherwise false.  Set to "true" only
     *      for brand new attempts
     * @return array an array whose keys are difficulty levels and values are the sum of questions associated with the difficulty
     */
    public function initalize_tags_with_quest_count($tagquestsum, $tags, $min, $max, $rebuild = false) {
        global $SESSION;

        // Check to see if the tagquestsum argument is initialized.
        $count = count($tagquestsum);
        if (empty($count) || !empty($rebuild)) {
            $tagquestsum = array();
            // Retrieve the question categories set for this activity.
            $questcat = $this->retrieve_question_categories();
            // Traverse through the array of configured tags used by the activity.
            foreach ($tags as $tag) {
                // Retrieve all of id for the configured tag.
                $tagids = $this->retrieve_all_tag_ids($min, $max, $tag);
                // Retrieve a count of all of the questions associated with each tag.
                $tagidquestsum = $this->retrieve_tags_with_question_count($tagids, $questcat, $tag);
                // Traverse the tagidquestsum array and add the values with the values current in the tagquestsum argument.
                foreach ($tagidquestsum as $difflevel => $totalquestindiff) {
                    // If the array key exists, then add the sum to what is already in the array.
                    if (array_key_exists($difflevel, $tagquestsum)) {
                        $tagquestsum[$difflevel] += $totalquestindiff;
                    } else {
                        $tagquestsum[$difflevel] = $totalquestindiff;
                    }
                }
            }
        } else {
            $tagquestsum = $SESSION->adpqtagquestsum;
        }

        return $tagquestsum;
    }

    /**
     * This function retrieves a question associated with a Moodle tag level of difficulty.  If the search for the tag turns up
     * empty the function tries to find another tag whose difficulty level is either higher or lower
     * @param array $excquestids an array of question ids to exclude from the search
     * @return array an array of question ids
     */
    public function fetch_questions($excquestids = array()) {
        $questids = array();

        // Initialize the difficulty tag question sum property for searching.
        $this->tagquestsum = $this->initalize_tags_with_quest_count($this->tagquestsum, $this->tags, $this->minimumlevel,
            $this->maximumlevel, $this->rebuild);

        // If tagquestsum property ie empty then return with nothing.
        if (empty($this->tagquestsum)) {
            $this->print_debug('fetch_questions() - tagquestsum is empty');
            return array();
        }
        // Check if the requested level has available questions.
        if (array_key_exists($this->level, $this->tagquestsum) && 0 < $this->tagquestsum[$this->level]) {
            $tagids = $this->retrieve_tag($this->level);
            $questids = $this->find_questions_with_tags($tagids, $excquestids);
            $this->print_debug('fetch_questions() - Requested level '.$this->level.' has available questions. '.
                $this->tagquestsum[$this->level].' question remaining.');
            return $questids;
        }

        // Look for a level that has avaialbe qustions.
        $level = $this->level;
        for ($i = 1; $i <= self::MAXNUMTRY; $i++) {
            // Check if the offset level is now out of bounds and stop the loop.
            if ($this->minimumlevel > $level - $i && $this->maximumlevel < $level + $i) {
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - searching levels has gone out of bounds of the min and max levels. '.
                    'No questions returned');
                continue;
            }

            // First check a level higher than the originally requested level.
            $newlevel = $level + $i;

            /*
             * If the level is within the boundries set for the attempt and the level exists and the count of question is greater
             * than zero, retrieve the tag id and the questions available
             */
            $condition = $newlevel <= $this->maximumlevel && array_key_exists($newlevel, $this->tagquestsum)
                && 0 < $this->tagquestsum[$newlevel];
            if ($condition) {
                $tagids = $this->retrieve_tag($newlevel);
                $questids = $this->find_questions_with_tags($tagids, $excquestids);
                $this->level = $newlevel;
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - original level could not be found.  Returned a question from level '.
                    $newlevel.' instead');
                continue;
            }

            // Check a level lower than the originally requested level.
            $newlevel = $level - $i;

            /*
             * If the level is within the boundries set for the attempt and the level exists and the count of question is greater
             *  than zero, retrieve the tag id and thequestions available
             */
            $condition = $newlevel >= $this->minimumlevel && array_key_exists($newlevel, $this->tagquestsum)
                && 0 < $this->tagquestsum[$newlevel];
            if ($condition) {
                $tagids = $this->retrieve_tag($newlevel);
                $questids = $this->find_questions_with_tags($tagids, $excquestids);
                $this->level = $newlevel;
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - original level could not be found.  Returned a question from level '
                    .$newlevel.' instead');
                continue;
            }
        }

        return $questids;
    }

    /**
     * This function retrieves all of the tag ids that can be used in this attempt
     * @throws coding_exception if the $tagprefix argument is empty
     * @param int $minimumlevel the minimum level the student can achieve
     * @param int $maximumlevel the maximum level the student can achieve
     * @param string $tagprefix the tag prefix used
     * @param array an array whose keys represent the difficulty level and values are tag ids
     */
    public function retrieve_all_tag_ids($minimumlevel, $maximumlevel, $tagprefix) {
        global $DB;

        $i = 0;
        $params = array();
        $select = '';
        $length = strlen($tagprefix) + 1;
        $substr = '';

        try {
            $substr = $DB->sql_substr('name', $length);
        } catch (coding_exception $e) {
            $this->print_debug('retrieve_all_tag_ids() - Missing tag prefix '.$this->vardump($tagprefix));
            print_error('missingtagprefix', 'adaptivequiz');
        }

        for ($i = $minimumlevel; $i <= $maximumlevel; $i++) {
            $params[$tagprefix.$i] = $tagprefix.$i;
            $select .= ' name = :'.$tagprefix.$i.' OR';
        }

        $select = rtrim($select, 'OR');

        $tagids = $DB->get_records_select_menu('tag', $select, $params, 'id ASC', $substr.', id AS id2');

        if (empty($tagids)) {
            $this->print_debug('retrieve_tag() - no tags found matching minimum level '.$minimumlevel.' and maximum level '.$maximumlevel);
            return array();
        }

        return $tagids;
    }

    /**
     * This function determines how many questions are associated with a tag, for questions contained in the category used by the
     * activity.
     * @throws coding_exception|dml_read_exception if the $tagprefix argument is empty
     * @param array $tagids an array whose key is the difficulty level and value is the tag id representing the difficulty level
     * @param array $categories an array whose key and value is the question category id
     * @param string $tagprefix the tag prefix used by the activity
     * @return array key is the difficulty level and the value the sum of questions associated with the difficulty level
     */
    public function retrieve_tags_with_question_count($tagids, $categories, $tagprefix) {
        global $DB;

        $params = array();
        $tempparam = array();
        $sql = '';
        $includetags = '';
        $includeqcats = '';
        $length = strlen($tagprefix) + 1;
        $substr = '';

        try {
            $substr = $DB->sql_substr('t.name', $length);

            // Create IN() clause for tag ids.
            list($includetags, $tempparam) = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'tagids');
            $params += $tempparam;
            // Create IN() clause for question category ids.
            list($includeqcats, $tempparam) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'qcatids');
            $params += array('itemtype' => 'question') + $tempparam;

            $sql = "SELECT $substr AS difflevel, count(*) AS numofquest
                      FROM {tag} t
                      JOIN {tag_instance} ti ON t.id = ti.tagid
                      JOIN {question} q ON q.id = ti.itemid
                     WHERE ti.itemtype = :itemtype
                           AND ti.tagid $includetags
                           AND q.category $includeqcats
                  GROUP BY t.name
                  ORDER BY t.id ASC";
            $records = $DB->get_records_sql_menu($sql, $params);
            return $records;
        } catch (coding_exception $e) {
            $this->print_debug('retrieve_tags_with_question_count() - Missing tag prefix '.$this->vardump($tagprefix));
            print_error('missingtagprefix', 'adaptivequiz');
        }
    }

    /**
     * This function retrieves all tag ids, used by this activity and associated with a particular level of difficulty
     * @param int $level: the level of difficulty (optional).  If 0 is passed then the function will use the level class property,
     *      otherwise the argument value will be used.
     * @return array - the tag id or false if no tag could be found
     */
    public function retrieve_tag($level = 0) {
        global $DB;

        $select = '';
        $params = array();
        $currentlevel = 0;

        if (empty($level)) {
            $thislevel = $this->level;
        } else {
            $thislevel = $level;
        }

        // Format clause for tag name search.
        foreach ($this->tags as $key => $tag) {
            $params[$tag.$key] = $tag.$level;
            $select .= ' name = :'.$tag.$key.' OR';
        }

        // Remove the last 'OR'.
        $select = rtrim($select, 'OR');

        // Query the tag table for all tags used by the activity.
        $tagids = $DB->get_records_select_menu('tag', $select, $params, 'id ASC', 'id, id AS id2');

        if (empty($tagids)) {
            $this->print_debug('retrieve_tag() - no tags found with level: '.$level);
            return array();
        }

        return $tagids;
    }

    /**
     * This function retrieves questions within the assigned question categories and
     * questions associated with tagids
     * @param array $tagids an array of tag is
     * @param array $exclude an array of question ids to exclude from the search
     * @return array an array whose keys are qustion ids and values are the question names
     */
    public function find_questions_with_tags($tagids = array(), $exclude = array()) {
        global $DB;

        $clause = '';
        $params = array();
        $tempparam = array();
        $exclquestids = '';
        $includetags = '';
        $includeqcats = '';

        // Retrieve question categories used by this activity.
        $questcat = $this->retrieve_question_categories();

        if (empty($questcat) || empty($tagids)) {
            $this->print_debug('find_questions() - No question categories or tagids used by activity');
            return array();
        }

        // Create IN() clause for tag ids.
        list($includetags, $tempparam) = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'tagids');
        $params += $tempparam;
        // Create IN() clause for question ids.
        list($includeqcats, $tempparam) = $DB->get_in_or_equal($questcat, SQL_PARAMS_NAMED, 'qcatids');
        $params += $tempparam;

        // Create IN() clause for question ids to exclude.
        if (!empty($exclude)) {
            list($exclquestids, $tempparam) = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'excqids', false);
            $params += $tempparam;
            $clause = "AND q.id $exclquestids";
        }

        $params += array('itemtype' => 'question');

        // Query the question table for questions associated with a tag instance and within a question category.
        $query = "SELECT q.id, q.name
                    FROM {question} q
              INNER JOIN {tag_instance} ti ON q.id = ti.itemid
                   WHERE ti.itemtype = :itemtype
                         AND ti.tagid $includetags
                         AND q.category $includeqcats
                         $clause
                ORDER BY q.id ASC";

        $records = $DB->get_records_sql($query, $params);

        $this->print_debug('find_questions() - question ids returned: '.$this->vardump($records));

        return $records;
    }

    /**
     * This function retrieves all of the question categories used the activity.
     * @return array an array of quesiton category ids
     */
    protected function retrieve_question_categories() {
        global $DB;

        // Check cached result.
        if (!empty($this->questcatids)) {
            $this->print_debug('retrieve_question_categories() - question category ids (from cache): '.
                $this->vardump($this->questcatids));
            return $this->questcatids;
        }

        $output = '';
        $param = array('instance' => $this->adaptivequiz->id);
        $records = $DB->get_records_menu('adaptivequiz_question', $param, 'questioncategory ASC', 'id,questioncategory');

        // Cache the results.
        $this->questcatids = $records;

        $this->print_debug('retrieve_question_categories() - question category ids: '.$this->vardump($records));

        return $records;
    }

    /**
     * The destruct method saves the difficult level and qustion number mapping to the session variable
     */
    public function __destruct() {
        global $SESSION;
        $SESSION->adpqtagquestsum = $this->tagquestsum;
    }
}