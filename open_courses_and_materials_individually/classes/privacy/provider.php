<?php
namespace local_open_courses_and_materials_individually\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Сообщает системе конфиденциальности, что плагин не хранит данных.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}