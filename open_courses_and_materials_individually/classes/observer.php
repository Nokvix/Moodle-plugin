<?php

namespace local_open_courses_and_materials_individually;

defined('MOODLE_INTERNAL') || die();

use core\event\user_enrolment_created;
use core\event\course_viewed;
use course_modinfo;
use cache_helper;

class observer
{
    public static function handle_course_viewed(course_viewed $event)
    {
        global $DB, $USER;

        // включен ли плагин
        if (!get_config('local_open_courses_and_materials_individually', 'enableplugin')) {
            return;
        }

        // обработка возможности подписаться на курсы
        self::check_self_enrolment($event);

        // активирона ли задержка тем
        if (!get_config('local_open_courses_and_materials_individually', 'enabletopicdelay')) {
            return;
        }

        $courseid = $event->courseid;
        $topic_delay_days = get_config('local_open_courses_and_materials_individually', 'topicdelaydays');

        // получаем время регистрации на курс
        $course_start = $DB->get_field_sql("
            SELECT MIN(ue.timestart)
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = ? AND ue.userid = ? AND ue.status = 0
        ", array($courseid, $USER->id));

        if (!$course_start) {
            return;
        }

        // получаем все темы курса
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');

        $last_days = 0;
        foreach ($sections as $section) {
            if ($section->section == 0) {
                // секция 0 курса всегда видима
                self::update_section_visibility($section->id, true);
                continue;
            }

            // получаем количество дней задержки из настроек темы
            $days = (int)$DB->get_field_sql("
                SELECT value
                FROM {course_format_options}
                WHERE courseid = ? AND sectionid = ?
            ", array($courseid, $section->id));

            // если нет настроек, используем значение из настроек плагина
            if (!$days) $days = $topic_delay_days;

            $days += $last_days;
            $last_days = $days;

            // вычисляем время начала видимости темы
            $topic_delay = $days * 24 * 60 * 60;
            $topic_visible_time = $course_start + $topic_delay;

            // обновляем видимость темы
            $should_be_visible = (time() >= $topic_visible_time);
            self::update_section_visibility($section->id, $should_be_visible);
        }
    }

    private static function update_section_visibility($sectionid, $visible)
    {
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

    public static function handle_user_enrolment(user_enrolment_created $event)
    {
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
    private static function calculate_midnight_time($timestamp, $days_to_add)
    {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->setTime(0, 0, 0);
        $date->modify('+' . $days_to_add . ' days');
        return $date->getTimestamp();
    }

    /**
     * Обработка возможности самостоятельной подписки на курсы
     * @param $event
     * @return void
     * @throws \dml_exception
     */
    public static function check_self_enrolment($event)
    {
        global $DB;

        // Получаем все курсы
        $courses = $DB->get_records('course');

        // Получаем задержки для курсов
        $delays = [];
        foreach ($courses as $course) {
            $custom_field_value = (int)$DB->get_field_sql("
                SELECT d.value 
                FROM {customfield_data} d
                JOIN {customfield_field} f ON f.id = d.fieldid
                WHERE d.instanceid = ? AND f.shortname = ?
            ", [$course->id, 'self_enrolment_delay']);

            if ($custom_field_value) {
                $delays[$course->id] = $custom_field_value;
            }
        }

        // получаем дату регистрации пользователя
        $user = $event->get_record_snapshot('user', $event->userid);
        $register_time = $user->timecreated;
        $current_time = time() - 86400;

        // Добавляем пользователю возможность подписаться на курсы если уже можно
        foreach ($delays as $course_id => $delay_days) {
            $new_timestart = self::calculate_midnight_time($register_time, $delay_days);
            self::update_course_enrolment($course_id, $new_timestart < $current_time);
        }
    }

    private static function update_course_enrolment($course_id, $enrolable)
    {
        global $DB;

        $enrol_id = ($DB->get_record('enrol', [
            'courseid' => $course_id,
            'enrol' => 'self'
        ], 'id'))->id;

        $DB->update_record('enrol', (object)[
            'id' => $enrol_id,
            'status' => $enrolable ? 0 : 1
        ]);

        rebuild_course_cache($course_id, true);
        course_modinfo::clear_instance_cache($course_id);
        cache_helper::purge_by_event('changesinsection');
    }
}