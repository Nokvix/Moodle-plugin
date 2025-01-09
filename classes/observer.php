<?php
namespace local_open_courses_and_materials_individually;

defined('MOODLE_INTERNAL') || die();

use core\event\user_enrolment_created;
use core\event\course_viewed;
use course_modinfo;
use cache_helper;

class observer {
    public static function handle_course_viewed(course_viewed $event) {
        global $DB, $USER;

        if (!get_config('local_open_courses_and_materials_individually', 'enableplugin') ||
            !get_config('local_open_courses_and_materials_individually', 'enabletopicdelay')) {
            return;
        }

        $courseid = $event->courseid;
        $topic_delay_days = get_config('local_open_courses_and_materials_individually', 'topicdelaydays');
        
        // получаем время регистрации на курс
        $course_start = $DB->get_field_sql(
            "SELECT MIN(ue.timestart)
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE e.courseid = ? AND ue.userid = ? AND ue.status = 0",
            array($courseid, $USER->id)
        );

        if (!$course_start) {
            return;
        }

        // получаем все темы курса
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        
        foreach ($sections as $section) {
            if ($section->section == 0) {
                // секция 0 курса всегда видима
                self::update_section_visibility($section->id, true);
                continue;
            }

            // вычисляем время начала видимости темы
            $topic_delay = ($section->section - 1) * $topic_delay_days * 24 * 60 * 60;
            $topic_visible_time = $course_start + $topic_delay;

            // обновляем видимость темы
            $should_be_visible = (time() >= $topic_visible_time);
            self::update_section_visibility($section->id, $should_be_visible);
        }
    }
    
    private static function update_section_visibility($sectionid, $visible) {
        global $DB;
        
        // получить id курса по id секции
        $section = $DB->get_record('course_sections', array('id' => $sectionid), 'course');
        
        $DB->update_record('course_sections', (object)[
            'id' => $sectionid,
            'visible' => $visible ? 1 : 0
        ]);

        rebuild_course_cache($section->course, true);        
        course_modinfo::clear_instance_cache($section->course);       
        cache_helper::purge_by_event('changesinsection');
    }

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