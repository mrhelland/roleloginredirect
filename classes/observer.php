<?php
namespace local_roleloginredirect;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function user_loggedin(\core\event\base $event): void {
        global $DB, $SESSION;

        $config = get_config('local_roleloginredirect');
        $courseid = isset($config->courseid) ? (int)$config->courseid : 0;
        if (empty($config->roleids) || $courseid <= 0) {
            return;
        }

        $userid = (int)$event->userid;

        // Skip if user id is invalid
        if ($userid <= 0) {
            return;
        }

        // 🆕 --- Skip administrators entirely ---
        if (is_siteadmin($userid)) {
            return;
        }

        // 🆕 --- Get all roles for this user (in any context) ---
        $userroles = $DB->get_records('role_assignments', ['userid' => $userid], '', 'id, roleid');

        if (empty($userroles)) {
            return;
        }

        // Build a quick lookup array of this user's role IDs.
        $userroleids = array_map(function($r) { return (int)$r->roleid; }, $userroles);

        // --- Immediately return if user has an excluded role ---
        if (!empty($config->excludedroleids)) {                                  
            $excludedroleids = array_filter(array_map('intval', explode(',', $config->excludedroleids))); 
            if (array_intersect($userroleids, $excludedroleids)) {              
                return; 
            }                                                                   
        }     

        // --- Convert configured roles (from plugin settings) into an integer array ---
        $roleids = array_filter(array_map('intval', explode(',', $config->roleids)));
        if (empty($roleids)) {
            return;
        }

        // --- Check if the user has *any* of the configured roles ---
        list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        $sql = "SELECT 1
                  FROM {role_assignments}
                 WHERE userid = :userid
                   AND roleid $in_sql";
        if (!$DB->record_exists_sql($sql, $params)) {
            return;
        }

        // --- Validate course exists and is viewable ---
        $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        // --- Auto-enroll user into target course if not already enrolled ---
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


        // --- Redirect to course page ---
        $target = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $SESSION->wantsurl = $target->out(false);
    }
}
