<?php
defined('MOODLE_INTERNAL') || die();



if ($hassiteconfig) {
    $settings = new admin_settingpage('local_open_course_materials', get_string('pluginname', 'local_open_course_materials'));

    $settings->add(new admin_setting_configtext(
        'local_open_course_materials/course_access_days',
        get_string('course_access_days', 'local_open_course_materials'),
        get_string('course_access_days_desc', 'local_open_course_materials'),
        7, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_open_course_materials/module_access_days',
        get_string('module_access_days', 'local_open_course_materials'),
        get_string('module_access_days_desc', 'local_open_course_materials'),
        3, PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);

    require_once(__DIR__.'/lib.php');

    // Подключение обработчика к форме курса.
    $settings->add(new admin_setting_configcheckbox(
        'local_open_course_materials/open_access_days',
        get_string('open_access_days', 'local_open_course_materials'),
        get_string('open_access_days_desc', 'local_open_course_materials'),
        0
    ));

    $CFG->extend_course_edit_form = 'local_open_course_materials_extend_course_edit_form';

}


