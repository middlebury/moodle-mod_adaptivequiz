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
 * Adaptive quiz renderer class
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/adaptivequiz/requiredpassword.class.php');

class mod_adaptivequiz_questions_renderer extends plugin_renderer_base {
    /** @var string $sortdir the sorting direction being used */
    protected $sortdir = '';
    /** @var moodle_url $sorturl the current base url used for keeping the table sorted */
    protected $sorturl = '';
    /** @var int $groupid variable used to reference the groupid that is currently being used to filter by */
    public $groupid = 0;
    /** @var array options that should be used for opening the secure popup. */
    protected static $popupoptions = array(
        'left' => 0,
        'top' => 0,
        'fullscreen' => true,
        'scrollbars' => false,
        'resizeable' => false,
        'directories' => false,
        'toolbar' => false,
        'titlebar' => false,
        'location' => false,
        'status' => false,
        'menubar' => false
    );

    /**
     * This function returns page header information to be printed to the page
     * @return string HTML markup for header inforation
     */
    public function print_header() {
        return $this->header();
    }

    /**
     * This function returns page footer information to be printed to the page
     * @return string HTML markup for footer inforation
     */
    public function print_footer() {
        return $this->footer();
    }
    
    /**
     * This function generates the HTML required to display the initial reports table
     * @param array $records attempt records from adaptivequiz_attempt table
     * @param stdClass $cm course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return string HTML markup
     */
    public function get_report_table($headers, $records, $cm, $baseurl, $sort, $sortdir) {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head =  $this->format_report_table_headers($headers, $cm, $baseurl, $sort, $sortdir);
        $table->align = array('center', 'center', 'center');
        $table->size = array('', '', '');

        $table->data = $records;
        return html_writer::table($table);
    }

    /**
     * This function creates the table header links that will be used to allow instructor to sort the data
     * @param stdClass $cm a course module object set to the instance of the activity
     * @param string $sort the column the the table is to be sorted by
     * @param string $sortdir the direction of the sort
     * @return array an array of column headers (firstname / lastname, number of attempts, standard error)
     */
    public function format_report_table_headers($headers, $cm, $baseurl, $sort, $sortdir) {
        global $OUTPUT;
        
        /* Create header links */
        $header_contents = array();
        foreach ($headers as $col_key => $col_name) {
            if ($sort == $col_key) {
                $col_seperator = ' ';
                if ($sortdir == 'DESC') {
                    $col_sortdir = 'ASC';
                    $imageparam = array('src' => $OUTPUT->pix_url('t/up'), 'alt' => '');
                    $col_icon = html_writer::empty_tag('img', $imageparam);
                } else {
                    $col_sortdir = 'DESC';
                    $imageparam = array('src' => $OUTPUT->pix_url('t/down'), 'alt' => '');
                    $col_icon = html_writer::empty_tag('img', $imageparam);
                }
            } else {
                $col_sortdir = 'ASC';
                $col_seperator = '';
                $col_icon = '';
            }
            
            $url = new moodle_url($baseurl, array('cmid' => $cm->id, 'sort' => $col_key, 'sortdir' => $col_sortdir));
            
            $header_contents[] = html_writer::link($url, $col_name.$col_seperator.$col_icon);
        }
        return $header_contents;
    }

    /**
     * This function prints paging information
     * @param int $totalrecords the total number of records returned
     * @param int $page the current page the user is on
     * @param int $perpage the number of records displayed on one page
     * @return string HTML markup
     */
    public function print_paging_bar($totalrecords, $page, $perpage, $cm, $baseurl, $sort, $sortdir) {
        global $OUTPUT;

        $url = new moodle_url($baseurl, array('cmid' => $cm->id, 'sort' => $sort, 'sortdir' => $sortdir));

        $output = '';
        $output .= $OUTPUT->paging_bar($totalrecords, $page, $perpage, $url);
        return $output;
    }
}
