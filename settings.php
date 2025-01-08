<?php
defined('MOODLE_INTERNAL') || die();



if ($hassiteconfig) {
    $settings = new admin_settingpage('local_open_courses_and_materials_individually', get_string('pluginname', 'local_open_courses_and_materials_individually'));

    $settings->add(new admin_setting_configtext(
        'local_open_courses_and_materials_individually/course_access_days',
        get_string('course_access_days', 'local_open_courses_and_materials_individually'),
        get_string('course_access_days_desc', 'local_open_courses_and_materials_individually'),
        7, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_open_courses_and_materials_individually/module_access_days',
        get_string('module_access_days', 'local_open_courses_and_materials_individually'),
        get_string('module_access_days_desc', 'local_open_courses_and_materials_individually'),
        3, PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);

    require_once(__DIR__.'/lib.php');

    // Подключение обработчика к форме курса.
    $settings->add(new admin_setting_configcheckbox(
        'local_open_courses_and_materials_individually/open_access_days',
        get_string('open_access_days', 'local_open_courses_and_materials_individually'),
        get_string('open_access_days_desc', 'local_open_courses_and_materials_individually'),
        0
    ));

    $CFG->extend_course_edit_form = 'local_open_courses_and_materials_individually_extend_course_edit_form';

}


