<?php
defined('MOODLE_INTERNAL') || die();

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
