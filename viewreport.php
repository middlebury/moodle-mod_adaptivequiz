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

/* Initialized parameter array for sql query */
$param = array('instance' => $cm->instance, 'attemptstate' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED);

/* Constructo order by clause */
$orderby = adaptivequiz_construct_view_report_orderby($sort, $sortdir);

$groupjoin = '';
$groupwhere = '';

if (0 != $groupid) {
    $groupjoin = ' INNER JOIN {groups_members} gm ON u.id = gm.userid ';
    $groupwhere = ' AND gm.groupid = :groupid ';
    $param['groupid'] = $groupid;
}

/* Retreive a list of attempts made by each use, displaying the sum of attempts and showing the lowest standard error calculated of the user's attempts */
$sql = "SELECT u.id, u.firstname, u.lastname, MIN(aa.standarderror) AS standarderror, COUNT(*) AS attempts
          FROM {adaptivequiz_attempt} aa
          JOIN {user} u
            ON u.id = aa.userid
        $groupjoin
         WHERE aa.instance = :instance
           AND aa.attemptstate = :attemptstate
        $groupwhere
      GROUP BY aa.userid
        $orderby";
$startfrom = $page * ADAPTIVEQUIZ_REC_PER_PAGE;
$records = $DB->get_records_sql($sql, $param, $startfrom, ADAPTIVEQUIZ_REC_PER_PAGE);

/* Count the total number of records returned */
$recordscount = $DB->get_records_sql($sql, $param);

$output = $PAGE->get_renderer('mod_adaptivequiz');

/* Set selected groupid */
$output::$groupid = $groupid;

/* print header information */
$header = $output->print_reporting_page_header();
/* Output attempts table */
$reporttable = $output->print_report_table($records, $cm, $sort, $sortdir);
/* Output paging bar */
$pagingbar = $output->print_paging_bar(count($recordscount), $page, ADAPTIVEQUIZ_REC_PER_PAGE);
/* Output the groups selector */
$groupsel = $output->print_groups_selector($cm, $course, $context, $USER->id);
/* Output footer information */
$footer = $output->print_reporting_page_footer();

echo $header;
echo $groupsel;
echo $pagingbar;
echo $reporttable;
echo $pagingbar;
echo $footer;