<?php
namespace local_roleloginredirect;

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer for redirecting users after login based on their roles.
 *
 * This observer runs when a user logs in. It checks the user's assigned roles,
 * compares them to the configured redirect and exclusion lists, and if eligible,
 * redirects them to a designated course. Optionally, it auto-enrols the user
 * into that course if they are not already enrolled.
 *
 * @package   local_roleloginredirect
 * @category  event
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handles user_loggedin event to redirect specific roles to a course.
     *
     * @param \core\event\base $event The user_loggedin event object.
     * @return void
     */
    public static function user_loggedin(\core\event\base $event): void {
        global $DB, $SESSION;

        // --- Retrieve and validate plugin configuration.
        $config = get_config('local_roleloginredirect');
        $courseid = isset($config->courseid) ? (int)$config->courseid : 0;
        if (empty($config->roleids) || $courseid <= 0) {
            return;
        }

        $userid = (int)$event->userid;

        // --- Skip if user id is invalid
        if ($userid <= 0) {
            return;
        }

        // --- Skip site administrators entirely.
        if (is_siteadmin($userid)) {
            return;
        }

        // --- Get all user roles using Moodle API (system context).
        $userroles = $DB->get_records('role_assignments', ['userid' => $userid], '', 'id, roleid');

        if (empty($userroles)) {
            return;
        }

        // --- Build a quick lookup array of this user's role IDs.
        $userroleids = array_map(function($r) { return (int)$r->roleid; }, $userroles);

        // --- Skip users with excluded roles.
        if (!empty($config->excludedroleids)) {                                  
            $excludedroleids = array_filter(array_map('intval', explode(',', $config->excludedroleids))); 
            if (array_intersect($userroleids, $excludedroleids)) {              
                return; 
            }                                                                   
        }     

        // --- Convert configured roles (from plugin settings) into an integer array.
        $roleids = array_filter(array_map('intval', explode(',', $config->roleids)));
        if (empty($roleids)) {
            return;
        }

        // --- Check if the user has ANY of the configured roles.
        list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        $sql = "SELECT 1
                  FROM {role_assignments}
                 WHERE userid = :userid
                   AND roleid $in_sql";
        if (!$DB->record_exists_sql($sql, $params)) {
            return;
        }

        // --- Validate that the target course exists and is visible.
        $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        // --- Auto-enrol user into the target course if not already enrolled.
        global $CFG;
        require_once($CFG->dirroot . '/enrol/manual/lib.php');

        $coursecontext = \context_course::instance($course->id);
        if (!is_enrolled($coursecontext, $userid)) {
            $enrol = enrol_get_plugin('manual');
            if ($enrol) {
                $instances = enrol_get_instances($course->id, false);
                foreach ($instances as $instance) {
                    if ($instance->enrol === 'manual') {
                        $roleid = (int)($config->enrolrole ?? $DB->get_field('role', 'id', ['shortname' => 'student']) ?? 0);
                        $enrol->enrol_user($instance, $userid, $roleid);
                        break;
                    }
                }
            }
        }


        // --- Redirect user to the target course.
        $target = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $SESSION->wantsurl = $target->out(false);
    }
}
