<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Фильтры курсов, доступных пользователю.
 *
 * @param array $courses
 * @return array
 */
function local_open_courses_and_materials_individually_pre_get_enrolled_courses($courses) {
    global $DB, $USER;

    if (!get_config('local_open_courses_and_materials_individually', 'enableplugin')) {
        return $courses;
    }

    $current_time = time();
    $filtered_courses = array();

    foreach ($courses as $course) {
        // Получить время начала курса
        $timestart = $DB->get_field_sql(
            "SELECT MIN(ue.timestart)
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE e.courseid = ? AND ue.userid = ? AND ue.status = 0",
            array($course->id, $USER->id)
        );

        // Показывать только курсы, которые начались
        if ($timestart && $current_time >= $timestart) {
            $filtered_courses[] = $course;
        }
    }

    return $filtered_courses;
}
