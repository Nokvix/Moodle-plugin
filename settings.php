<?php
defined('MOODLE_INTERNAL') || die();

// Проверяем, есть ли права на просмотр настроек сайта:
if ($hassiteconfig) {

    // Создаём страницу настроек локального плагина.
    $settings = new admin_settingpage(
        'local_open_courses_and_materials_individually',
        get_string('pluginname', 'local_open_courses_and_materials_individually')
    );

    // Включить плагин
    $settings->add(new admin_setting_configcheckbox(
        'local_open_courses_and_materials_individually/enableplugin',
        get_string('enableplugin', 'local_open_courses_and_materials_individually'),
        get_string('enableplugin_desc', 'local_open_courses_and_materials_individually'),
        0 // Значение по умолчанию = выкл.
    ));

    // Задержка курсов в днях
    $settings->add(new admin_setting_configtext(
        'local_open_courses_and_materials_individually/delaydays',
        get_string('delaydays', 'local_open_courses_and_materials_individually'),
        get_string('delaydays_desc', 'local_open_courses_and_materials_individually'),
        7, // по умолчанию
        PARAM_INT
    ));

    // Пользовательское сообщение
    $settings->add(new admin_setting_configtext(
        'local_open_courses_and_materials_individually/custommessage',
        get_string('custommessage', 'local_open_courses_and_materials_individually'),
        get_string('custommessage_desc', 'local_open_courses_and_materials_individually'),
        '', // по умолчанию пусто
        PARAM_TEXT
    ));

    // Включить задержку тем
    $settings->add(new admin_setting_configcheckbox(
        'local_open_courses_and_materials_individually/enabletopicdelay',
        get_string('enabletopicdelay', 'local_open_courses_and_materials_individually'),
        get_string('enabletopicdelay_desc', 'local_open_courses_and_materials_individually'),
        0
    ));

    // Задержка тем в днях
    $settings->add(new admin_setting_configtext(
        'local_open_courses_and_materials_individually/topicdelaydays',
        get_string('topicdelaydays', 'local_open_courses_and_materials_individually'),
        get_string('topicdelaydays_desc', 'local_open_courses_and_materials_individually'),
        7,
        PARAM_INT
    ));

    // Регистрируем страницу настроек в разделе «Локальные плагины».
    $ADMIN->add('localplugins', $settings);
}