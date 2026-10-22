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
 * Local stuff for cohort to group plugin.
 *
 * @package    enrol_groupsync
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Sync all cohort course links.
 * @param int $courseid one course, empty mean all
 * @param bool $verbose verbose CLI output
 * @return int 0 means ok, 1 means error, 2 means plugin disabled
 */
function enrol_groupsync_sync($courseid = null, $verbose = false) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/group/lib.php");

    // Purge all roles if cohort sync disabled, those can be recreated later here by cron or CLI.
    if (!enrol_is_enabled('groupsync')) {
        if ($verbose) {
            mtrace('Cohort to group sync plugin is disabled, removing all group memberships.');
        }

        $sql = "SELECT gm.groupid, gm.userid
                  FROM {groups_members} gm
                 WHERE gm.component = 'enrol_groupsync'";
        $rs = $DB->get_recordset_sql($sql, array());
        foreach ($rs as $gm) {
            groups_remove_member($gm->groupid, $gm->userid);
        }
        $rs->close();

        return 2;
    }

    // Unfortunately this may take a long time, this script can be interrupted without problems.
    @set_time_limit(0);

    if ($verbose) {
        mtrace('Starting cohort to group membership synchronisation...');
    }

    if ($courseid) {
        $params = array('enabled' => ENROL_INSTANCE_ENABLED, 'courseid' => $courseid);
        $courseselect = "AND e.courseid = :courseid";

    } else {
        $params = array('enabled' => ENROL_INSTANCE_ENABLED);
        $courseselect = "";
    }

    // Cleanup first.
    // Local change (BWS Uster, 2026): The upstream cleanup was tied to the
    // instance that created the membership (gm.itemid = e.id). Memberships from
    // previous instance generations (e.g. after a yearly rollover recreates
    // instances while the course groups persist) could therefore never be
    // removed. This cleanup removes a membership whenever the user is not
    // covered by ANY active cohort mapping for this group. This heals leftovers
    // from old instance generations and correctly handles groups that are
    // mapped from multiple cohorts.
    $sql = "SELECT DISTINCT gm.groupid, gm.userid, g.courseid
              FROM {groups_members} gm
              JOIN {groups} g ON (g.id = gm.groupid)
             WHERE gm.component = 'enrol_groupsync'
               AND NOT EXISTS (
                       SELECT 1
                         FROM {enrol} e2
                         JOIN {cohort_members} cm2 ON cm2.cohortid = e2.customint1
                              AND cm2.userid = gm.userid
                        WHERE e2.courseid = g.courseid
                          AND e2.enrol = 'groupsync'
                          AND e2.status = :enabled
                          AND e2.customint2 = g.id
                   ) $courseselect";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $gm) {
        groups_remove_member($gm->groupid, $gm->userid);
        if ($verbose) {
            mtrace("Removing user $gm->userid from group $gm->groupid in course $gm->courseid");
        }
    }
    $rs->close();

    // Add missing group memberships.
    $sql = "SELECT DISTINCT e.id, e.customint2, e.courseid, ue.userid
              FROM {enrol} e
              JOIN {enrol} oe ON (oe.courseid = e.courseid and oe.enrol <> 'groupsync')
              JOIN {user_enrolments} ue ON (ue.enrolid = oe.id)
              JOIN {cohort} c ON (c.id = e.customint1)
              JOIN {cohort_members} cm ON (cm.cohortid = c.id AND cm.userid = ue.userid)
              JOIN {groups} g ON (g.courseid = e.courseid AND g.id = e.customint2)
         LEFT JOIN {groups_members} gm ON (gm.userid = ue.userid AND gm.groupid = g.id)
             WHERE e.enrol = 'groupsync' AND e.status = :enabled $courseselect AND gm.id IS NULL
          ORDER BY e.id ASC";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $instance) {
        if (groups_add_member($instance->customint2, $instance->userid, 'enrol_groupsync', $instance->id)) {
            if ($verbose) {
                mtrace("Adding user $instance->userid to group $instance->customint2 in course $instance->courseid");
            }
        } else {
            if ($verbose) {
                mtrace("Could not add $instance->userid to group $instance->customint2 in course $instance->courseid");
            }
        }
    }
    $rs->close();

    if ($verbose) {
        mtrace('...synchronisation finished.');
    }

    return 0;
}
