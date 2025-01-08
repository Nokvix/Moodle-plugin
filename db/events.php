<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_open_courses_and_materials_individually\observer::handle_user_enrolment',
        'internal' => false
    ]
];