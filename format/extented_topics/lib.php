<?php

defined('MOODLE_INTERNAL') || die();

// подключаем базовый класс с темами
require_once($CFG->dirroot . '/course/format/topics/lib.php');

/**
 * Наследуем класс от базового класса с темами
 *
 * @package    format_extented_topics
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_extented_topics extends format_topics
{

    /**
     * Добавляем поле в формы редактирования темы
     *
     * @param $foreditform
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function section_format_options($foreditform = false)
    {
        $options = parent::section_format_options($foreditform);

        // если плагин не включен или отсутствует, то не добавляем поле
        if (!get_config('local_open_courses_and_materials_individually', 'enableplugin') ||
            !get_config('local_open_courses_and_materials_individually', 'enabletopicdelay')) {
            return $options;
        }

        // получаем значение задержки по умолчанию из настроек плагина
        $topic_delay_days = get_config('local_open_courses_and_materials_individually', 'topicdelaydays');

        // добавляем поле в форму
        $options['section_delay_field'] = [
            'type' => PARAM_INT,
            'default' => $topic_delay_days,
            'description' => get_string('section_delay_field', 'format_extented_topics'),
            'label' => get_string('section_delay_field', 'format_extented_topics'),
            'element_type' => 'text',
        ];

        return $options;
    }
}

/**
 * обработчик AJAX редактирования названия темы
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_extented_topics_inplace_editable($itemtype, $itemid, $newvalue)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'extented_topics'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
