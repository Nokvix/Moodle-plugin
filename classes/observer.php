<?php
namespace local_open_courses_and_materials_individually;

defined('MOODLE_INTERNAL') || die();

use core\event\user_enrolment_created;
use core\event\course_viewed;
use course_modinfo;
use cache_helper;

class observer {
    // здесь будет handle_course_viewed
    // ещё нужно реализовать update_section_visibility

    public static function handle_user_enrolment(user_enrolment_created $event) {
        global $DB;
        
        // Проверка наличия плагина и задержки
        if (!get_config('local_open_courses_and_materials_individually', 'enableplugin')) {
            return;
        }
        // вычисление времени начала доступа к курсу
        $delay_days = get_config('local_open_courses_and_materials_individually', 'delaydays');
        if (!$delay_days) {
            return;
        }

        $enrolment_data = $event->get_record_snapshot('user_enrolments', $event->objectid);
        $current_time = time();
        
        // Вычисление даты начала видимости курса равной дате регистрации плюс задержка в днях
        $new_timestart = self::calculate_midnight_time($current_time, $delay_days);

        // обновление времени начала доступа к курсу
        $DB->set_field('user_enrolments', 'timestart', $new_timestart, ['id' => $enrolment_data->id]);

        debugging('Updated enrolment timestart for user ' . $event->relateduserid . 
                 ' to start at ' . userdate($new_timestart), DEBUG_DEVELOPER);        
    }

    /**
     * вычисление времени полуночи для переданного дня, т.к. студент может быть записан например в 18:00, 
     * если сместить время на заданные дни то курс в этом случае будет доступен не с начала дня, а также с
     * 18:00, что не соответствует ожидаемому поведению
     */
    private static function calculate_midnight_time($timestamp, $days_to_add) {        
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->setTime(0, 0, 0);             
        $date->modify('+' . $days_to_add . ' days');        
        return $date->getTimestamp();
    }
}