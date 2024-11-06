<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Проверяет доступ к курсу на основе даты регистрации на сайт.
 *
 * @param int $courseid ID курса.
 * @return bool True, если доступ разрешен.
 */
function local_open_course_materials_can_access_course($courseid) {
    global $USER, $DB;

    // Дата регистрации пользователя на сайте.
    $user_registration_date = $DB->get_field('user', 'timecreated', ['id' => $USER->id]);

    // Индивидуальная задержка открытия курса.
    $course_access_days = $DB->get_field('course', 'open_access_days', ['id' => $courseid]);
    if ($course_access_days === null) {
        $course_access_days = get_config('local_open_course_materials', 'course_access_days') ?? 7;
    }

    // Вычисляем дату открытия курса.
    $course_availability_date = strtotime("+{$course_access_days} days", $user_registration_date);
    return time() >= $course_availability_date;
}

/**
 * Проверяет доступ к модулю курса на основе даты регистрации на курс.
 *
 * @param int $courseid ID курса.
 * @param int $moduleid ID модуля.
 * @return bool True, если доступ разрешен.
 */
function local_open_course_materials_can_access_module($courseid, $moduleid) {
    global $USER, $DB;

    // Дата регистрации пользователя на курс.
    $enrolment_record = $DB->get_record_sql("
        SELECT ue.timecreated
        FROM {user_enrolments} ue
        JOIN {enrol} e ON e.id = ue.enrolid
        WHERE ue.userid = :userid AND e.courseid = :courseid
    ", ['userid' => $USER->id, 'courseid' => $courseid]);

    if (!$enrolment_record) {
        return false; // Если пользователь не зарегистрирован на курс.
    }

    $user_course_registration_date = $enrolment_record->timecreated;

    // Задержка открытия модуля.
    $module_access_days = $DB->get_field('course_modules', 'open_access_days', ['id' => $moduleid]);
    if ($module_access_days === null) {
        $module_access_days = get_config('local_open_course_materials', 'module_access_days') ?? 3;
    }

    // Вычисляем дату открытия модуля.
    $module_availability_date = strtotime("+{$module_access_days} days", $user_course_registration_date);
    return time() >= $module_availability_date;
}

/**
 * Добавляет настройку "Задержка открытия" в форму курса.
 *
 * @param moodleform $form
 * @param stdClass $data
 */
function local_open_course_materials_extend_course_edit_form($form, $data) {
    // Проверка, является ли форма объектом класса course_edit_form.
    if ($form instanceof \core_course\edit_form) {
        $mform = $form->_form;

        // Добавляем текстовое поле для указания задержки открытия курса.
        $mform->addElement('text', 'open_access_days', get_string('open_access_days', 'local_open_course_materials'), ['size' => '4']);
        $mform->setType('open_access_days', PARAM_INT);
        $mform->setDefault('open_access_days', 0); // По умолчанию 0 дней.
        $mform->addRule('open_access_days', null, 'numeric', null, 'client');
    }
}

function local_open_course_materials_after_course_updated($event) {
    global $DB;

    $data = $event->get_data();
    $courseid = $data['courseid'];

    // Проверяем наличие задержки открытия и обновляем запись.
    if (isset($data['other']['open_access_days'])) {
        $DB->set_field('course', 'open_access_days', $data['other']['open_access_days'], ['id' => $courseid]);
    }
}

