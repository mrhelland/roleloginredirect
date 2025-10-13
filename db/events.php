<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\\core\\event\\user_loggedin',
        'callback'    => '\\local_roleloginredirect\\observer::user_loggedin',
        'includefile' => '/local/roleloginredirect/classes/observer.php',
        'internal'    => false,
        'priority'    => 5000,
    ],
];
