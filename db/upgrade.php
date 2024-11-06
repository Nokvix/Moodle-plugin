<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_open_course_materials_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2021051702) {
        $dbman = $DB->get_manager();

        // Добавляем поле 'open_access_days' в таблицу курса.
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_access_days', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'summary');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Добавляем поле 'open_access_days' в таблицу модулей курса.
        $table = new xmldb_table('course_modules');
        $field = new xmldb_field('open_access_days', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'completion');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021051702, 'local', 'open_course_materials');
    }

    return true;
}
