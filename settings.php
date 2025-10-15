<?php
defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use moodle_exception;
use required_capability_exception;

try {
    // Check for permission to access site configuration.
    if ($hassiteconfig) {
        $settings = new admin_settingpage(
            'local_roleloginredirect',
            get_string('settingsheading', 'local_roleloginredirect')
        );

        // Only load role list when rendering the full settings tree in the admin UI.
        if (!empty($ADMIN->fulltree)) {
            global $DB;
            $rolesmenu = [];
            foreach ($DB->get_records('role', null, 'sortorder ASC') as $r) {
                $label = trim($r->shortname . (!empty($r->name) ? " ({$r->name})" : ''));
                $rolesmenu[(int)$r->id] = $label;
            }

            $settings->add(new admin_setting_configmultiselect(
                'local_roleloginredirect/roleids',
                get_string('roleid', 'local_roleloginredirect'),
                get_string('roleid_desc', 'local_roleloginredirect'),
                [],
                $rolesmenu
            ));

            $settings->add(new admin_setting_configtext(
                'local_roleloginredirect/courseid',
                get_string('courseid', 'local_roleloginredirect'),
                get_string('courseid_desc', 'local_roleloginredirect'),
                '',
                PARAM_INT
            ));
        }

        $ADMIN->add('localplugins', $settings);
    }
} catch (required_capability_exception $e) {
    // Redirect non-privileged users to the home page with an error notification.
    redirect(
        new moodle_url('/'),
        get_string('accessdenied', 'local_roleloginredirect'),
        null,
        notification::NOTIFY_ERROR
    );

} catch (moodle_exception $e) {
    // Handle any other general exceptions (optional).
    redirect(
        new moodle_url('/'),
        get_string('unexpectederror', 'error'),
        null,
        notification::NOTIFY_ERROR
    );
}

