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
 * Scheduled task for the cohort to group sync enrolment plugin.
 *
 * Moodle 5.x no longer executes the legacy per-plugin cron() function
 * (see version.php $plugin->cron), therefore the synchronisation is
 * wrapped in this scheduled task instead.
 *
 * @package   enrol_groupsync
 * @category  task
 * @copyright 2026 BWS Uster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_groupsync\task;

/**
 * Scheduled task running the cohort to group synchronisation.
 *
 * @copyright 2026 BWS Uster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'enrol_groupsync');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/groupsync/locallib.php');
        enrol_groupsync_sync();
    }
}
