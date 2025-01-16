<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_viewed',
        'callback' => '\local_open_courses_and_materials_individually\observer::handle_course_viewed',
        'internal' => false,
        'priority' => 9999
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_open_courses_and_materials_individually\observer::handle_user_enrolment',
        'internal' => false
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_open_courses_and_materials_individually\observer::check_self_enrolment',
        'internal' => false,
        'priority' => 9998
    ]
];