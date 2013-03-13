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
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 class fetchquestion {
    /**
     * The maximum number of attempts at finding a tag containing questions
     */
    const MAXTAGRETRY = 5;

    /** @var stdClass $adaptivequiz object, properties come from the adaptivequiz table */
    protected $adaptivequiz;

    /** @var bool $debugenabled flag to denote developer debugging is enabled and this class should write message to the debug array */
    protected $debugenabled = false;

    /** @var array $debug array containing debugging information */
    protected $debug = array();

    /**
     * @var bool $searchup this variable is used when a search for a question turns up empty.  The search must try to find another question
     * that is either of higher or lower difficulty.  If set to true the class will continue the search using difficulty levels higher than the original
     * level.  Otherwise it will look for lower diffuclty questions
     */
    protected $searchup = false;

    /** @var array $tags an array of tags that used to identify eligible questions for the attempt */
    protected $tags = array();

    /** @var int $level the level of difficutly that will be used to fetch questions */
    protected $level = 1;

    /** @var string $questcatids a string of comma separated question category ids */
    protected $questcatids = '';

    /**
     * Constructor initializes data required to retrieve questions associated with tag
     * and within question categories
     * @param stdClass $adaptivequiz: adaptivequiz record object from adaptivequiz table
     * @param int $level: level of difficuty to look for when fetching a question
     * @param array $tags: an array of accepted tags
     * @return void
     */
    public function __construct($adaptivequiz, $level = 1, $tags = array()) {
        $this->adaptivequiz = $adaptivequiz;
        $this->tags = $tags;
        $this->tags[] = ADAPTIVEQUIZ_QUESTION_TAG;

        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('Argument 2 is not an positive integer', 'Second parameter must be a positive integer');
        }

        $this->level = $level;

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
     * This function sets the class variable $searchup flag
     * @param bool $searchup: set to true to retry searching more difficult questions. Set to false to search
     * for less difficult questions
     * @return void
     */
    public function set_searchup($searchup = false) {
        $this->searchup = $searchup;
    }

    /**
     * This functions returns the class variable @searchup flag
     * @return bool - value of the search up class variable
     */
    public function get_searchup() {
        return $this->searchup;
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
     * This function returns the debug array
     * @return array - array of debugging messages
     */
    public function get_debug() {
        return $this->debug;
    }

    /**
     * This function retrieves a question associated with a Moodle tag level of difficulty.  If the search for the tag turns up empty
     * the function tries to find another tag whose difficulty level is either higher or lower (depending on the $searchup flag).
     * If no questions are found associated with the tag(s), then the function searches for a tag whose difficulty is either higher or lower.
     * The function will search no more than 5 levels higher or lower.
     * @param array $excquestids: an array of question ids to exclude from the search
     * @return array - an array of question ids
     */
    public function fetch_questions($excquestids = array()) {
        $level = $this->level;
        $questids = array();
        $tagids = array();
        $questids = array();

        for ($i = 0; $i < self::MAXTAGRETRY; $i++) {
            // find tags containing the difficulty level
            $tagids = $this->retrieve_tag($level);

            // If tag ids are found start to look for questions associated with the tag
            if (!empty($tagids)) {
                // Look for questions associated with the tag ids
                $questids = $this->find_questions_with_tags($tagids, $excquestids);

                // Questions found, leave the for loop
                if (!empty($questids)) {
                    $i = self::MAXTAGRETRY;
                    continue;
                }
            }

            // Tags or questions were empty, search for tags with a higher or lower level of difficulty
            if ($this->get_searchup()) {
                $level++;
                $this->print_debug('fetch_question() - Searching up from level: '.$this->level.'. Trying level '.$level);
                $this->print_debug('fetch_question() - Question ids: '.print_r($questids, true));
            } else {
                $level--;
                $this->print_debug('fetch_question() - Searching down from level: '.$this->level.'. Trying level '.$level);
                $this->print_debug('fetch_question() - Question ids: '.print_r($questids, true));
            }
        }

        return $questids;
    }

    /**
     * This function retrieves all tag ids, used by this activity and associated with a particular level of difficulty
     * @param int $level: the level of difficulty (optional).  If 0 is passed then the function will use the level class property, otherwise the argument value will be used.
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

        // Format clause for tag name search
        foreach ($this->tags as $key => $tag) {
            $params[$tag.$key] = $tag.$level;
            $select .= ' name = :'.$tag.$key.' OR';
        }

        // Remove the last 'OR'
        $select = rtrim($select, 'OR');

        // Query the tag table for all tags used by the activity
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
     * @param array $tagids: an array of tag is
     * @param array $exclude: an array of question ids to exclude from the search
     * @return array - an array of question ids
     */
    public function find_questions_with_tags($tagids = array(), $exclude = array()) {
        global $DB;

        $clause = '';
        $params = array();
        $exclquestids = '';
        $includetags = '';

        // Retrieve question categories used by this activity
        $questcat = $this->retrieve_question_categories();

        if (empty($questcat) || empty($tagids)) {
            $this->print_debug('find_questions() - No question categories or tagids used by activity');
            return array();
        }

        // Create IN() clause for tag ids
        list($includetags, $params) = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'tagids');

        // Create IN() clause for question ids to exclude
        if (!empty($exclude)) {
            $tempparam = array();
            list($exclquestids, $tempparam) = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'excqids', false);
            $params += $tempparam;
            $clause = "AND q.id $exclquestids";
        }

        $params += array('itemtype' => 'question', 'category' => $questcat);

        // Query the question table for questions associated with a tag instance and within a question category
        $query = "SELECT q.id, q.name
                    FROM {question} q
              INNER JOIN {tag_instance} ti ON q.id = ti.itemid
                   WHERE ti.itemtype = :itemtype
                         AND ti.tagid $includetags
                         AND q.category IN (:category)
                         $clause
                ORDER BY q.id ASC";

        $records = $DB->get_records_sql($query, $params);

        $this->print_debug('find_questions() - question ids returned: '.print_r($records, true));

        return $records;
    }

    /**
     * This function retrieves all of the question categories used the activity.
     * @return string - a comma separated list of question category ids used by this activitsy
     */
    protected function retrieve_question_categories() {
        global $DB;

        // Check cached result
        if (!empty($this->questcatids)) {
            $this->print_debug('retrieve_question_categories() - question category ids (from cache): '.$this->questcatids);
            return $this->questcatids;
        }

        $output = '';
        $param = array('instance' => $this->adaptivequiz->id);
        $records = $DB->get_records('adaptivequiz_question', $param, 'questioncategory ASC', 'id,questioncategory');

        if (!empty($records)) {
            foreach ($records as $record) {
                $output .= $record->questioncategory.',';
            }

            $output = rtrim($output, ',');
        }

        // Cache the results
        $this->questcatids = $output;

        $this->print_debug('retrieve_question_categories() - question category ids: '.$this->questcatids);

        return $output;
    }
}