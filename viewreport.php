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
 * Adaptive quiz view report script
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/lib/grouplib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

$id = required_param('cmid', PARAM_INT);
$sortdir = optional_param('sortdir', 'ASC', PARAM_ALPHA);
$sort = optional_param('sort', 'lastname', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*');
$PAGE->set_url('/mod/adaptivequiz/viewreport.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$output = $PAGE->get_renderer('mod_adaptivequiz');

/* Initialized parameter array for sql query */
$param = array('instance' => $cm->instance, 'attemptstate' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED);

/* Constructo order by clause */
$orderby = adaptivequiz_construct_view_report_orderby($sort, $sortdir);

/* Output the groups selector */
$groupsel = $output->print_groups_selector($cm, $course, $context, $USER->id);

$groupjoin = '';
$groupwhere = '';

/* Determine the currently active group id */
if (empty($groupid)) {
    $allowedgroups = groups_get_activity_allowed_groups($cm, $USER->id);
    $groupid = groups_get_activity_group($cm, true, $allowedgroups);
}

/* Create the group sql join and where clause */
if (0 != $groupid) {
    $groupjoin = ' INNER JOIN {groups_members} gm ON u.id = gm.userid ';
    $groupwhere = ' AND gm.groupid = :groupid ';
    $param['groupid'] = $groupid;
}

/* Retreive a list of attempts made by each use, displaying the sum of attempts and showing the lowest standard error calculated of the user's attempts */
$sql = "SELECT u.id, u.firstname, u.lastname, aa.standarderror AS stderror, a.highestlevel, a.lowestlevel, aa.id AS attemptid, aa.measure,
               (SELECT COUNT(*)
                  FROM {adaptivequiz_attempt} caa
                 WHERE caa.userid = u.id
                       AND caa.instance = aa.instance) AS attempts
          FROM {adaptivequiz_attempt} aa
          JOIN {user} u ON u.id = aa.userid
          JOIN {adaptivequiz} a ON a.id = aa.instance
        $groupjoin
         WHERE aa.instance = :instance
               AND aa.attemptstate = :attemptstate
               AND aa.standarderror = (SELECT MIN(saa.standarderror)
                                         FROM {adaptivequiz_attempt} saa
                                        WHERE saa.userid = aa.userid
                                              AND saa.instance = aa.instance)
        $groupwhere
      GROUP BY aa.userid
        $orderby";
$startfrom = $page * ADAPTIVEQUIZ_REC_PER_PAGE;
$records = $DB->get_records_sql($sql, $param, $startfrom, ADAPTIVEQUIZ_REC_PER_PAGE);
/* Count the total number of records returned */
$recordscount = $DB->get_records_sql($sql, $param);

/* Set selected groupid */
$output->groupid = $groupid;

/* print header information */
$header = $output->print_header();
/* Output attempts table */
$reporttable = $output->print_report_table($records, $cm, $sort, $sortdir);
/* Output paging bar */
$pagingbar = $output->print_paging_bar(count($recordscount), $page, ADAPTIVEQUIZ_REC_PER_PAGE);
/* Output footer information */
$footer = $output->print_footer();

echo $header;
echo $groupsel;
echo $pagingbar;
echo $reporttable;
echo $pagingbar;
echo $footer;
