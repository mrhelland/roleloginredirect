<?php
defined('MOODLE_INTERNAL') || die();

// Check for permission to access site configuration.
if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_roleloginredirect',
        get_string('settingsheading', 'local_roleloginredirect')
    );

    // Only load role list when rendering the full settings tree in the admin UI.
    if ($ADMIN->fulltree) {
        global $DB;

        // --- Build the list of all roles ---
        $rolesmenu = [];
        foreach ($DB->get_records('role', null, 'sortorder ASC') as $r) {
            $label = trim($r->shortname . (!empty($r->name) ? " ({$r->name})" : ''));
            $rolesmenu[(int)$r->id] = $label;
        }

        // --- Detect role shortnames dynamically ---
        $roleids_by_shortname = $DB->get_records_menu('role', null, '', 'shortname, id');

        
        // --- Default redirect roles (if they exist)
        $default_redirect_roles = [];
        foreach (['parent', 'observer', 'professional'] as $shortname) {
            if (!empty($roleids_by_shortname[$shortname])) {
                $default_redirect_roles[] = (int)$roleids_by_shortname[$shortname];
            }
        }

        // --- Default excluded roles (if they exist)
        $default_excluded_roles = [];
        foreach (['editingteacher', 'teacher', 'manager'] as $shortname) {
            if (!empty($roleids_by_shortname[$shortname])) {
                $default_excluded_roles[] = (int)$roleids_by_shortname[$shortname];
            }
        }


        // --- Roles to redirect ---
        $settings->add(new admin_setting_configmultiselect(
            'local_roleloginredirect/roleids',
            get_string('roleid', 'local_roleloginredirect'),
            get_string('roleid_desc', 'local_roleloginredirect'),
            $default_redirect_roles,
            $rolesmenu
        ));

        // --- Roles to exclude from redirection (override list) ---
        $settings->add(new admin_setting_configmultiselect(
            'local_roleloginredirect/excludedroleids',
            get_string('excludedroleids', 'local_roleloginredirect'),
            get_string('excludedroleids_desc', 'local_roleloginredirect'),
            $default_excluded_roles,
            $rolesmenu
        ));

        // --- Target course ID ---
        $settings->add(new admin_setting_configtext(
            'local_roleloginredirect/courseid',
            get_string('courseid', 'local_roleloginredirect'),
            get_string('courseid_desc', 'local_roleloginredirect'),
            '',
            PARAM_INT
        ));

        // --- What role to give redirected users in the target course ---
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $lowestroleid = $DB->get_field('role', 'id', ['shortname' => 'guest']);
        $settings->add(new admin_setting_configselect(
            'local_roleloginredirect/enrolrole',
            get_string('enrolrole', 'local_roleloginredirect'),
            get_string('enrolrole_desc', 'local_roleloginredirect'),
            ($studentroleid ?: $lowestroleid), // default to student role, or guest role if student is not found
            $rolesmenu
        ));
    }

    // Add the settings page to the Local plugins category.
    $ADMIN->add('localplugins', $settings);
}


