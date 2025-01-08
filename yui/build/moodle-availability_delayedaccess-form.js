YUI.add('moodle-local_open_courses_and_materials_individually-form', function(Y, NAME) {

    // Создаём пространство имён, если вдруг не создано
    M.local_open_courses_and_materials_individually = M.local_open_courses_and_materials_individually || {};

    // Наследуемся от базового класса плагина форм
    M.local_open_courses_and_materials_individually.form = Y.Object(M.core_availability.plugin);

    // Параметры для отрисовки:
    M.local_open_courses_and_materials_individually.form.timeVariants = null;
    M.local_open_courses_and_materials_individually.form.beforeText   = null;
    M.local_open_courses_and_materials_individually.form.afterText    = null;
    M.local_open_courses_and_materials_individually.form.sectionFlag  = null;

    /**
     * Инициализация с сервера (get_javascript_init_params).
     */
    M.local_open_courses_and_materials_individually.form.initInner = function(timeVariants, beforeText, afterText, isSection) {
        this.timeVariants = timeVariants;
        this.beforeText   = beforeText;
        this.afterText    = afterText;
        this.sectionFlag  = isSection;
    };

    /**
     * Создаёт DOM-элемент условия в интерфейсе.
     */
    M.local_open_courses_and_materials_individually.form.getNode = function(conditionData) {
        var container = '<span class="availability-delayedaccess">';
        container += '<span class="delayedaccess_before">' + this.beforeText + '</span> ';

        container += '<label><select name="delayed_count">';
        // Предположим, разрешаем интервал от 1 до 90 (минут, часов и т.д.)
        for (var i = 1; i <= 90; i++) {
            container += '<option value="' + i + '">' + i + '</option>';
        }
        container += '</select></label> ';

        container += '<label><select name="delayed_unit">';
        for (var j = 0; j < this.timeVariants.length; j++) {
            container += '<option value="' + this.timeVariants[j].field + '">';
            container += this.timeVariants[j].display + '</option>';
        }
        container += '</select></label> ';

        container += '<span class="delayedaccess_after">' + this.afterText + '</span>';
        container += '</span>'; // Закрытие контейнера

        // Преобразуем HTML-строку в YUI-нод
        var node = Y.Node.create(container);

        // Устанавливаем начальные значения из conditionData.
        var cval = (conditionData.n !== undefined) ? conditionData.n : 1;
        node.one('select[name=delayed_count]').set('value', cval);

        var uval = (conditionData.d !== undefined) ? conditionData.d : 2;
        node.one('select[name=delayed_unit]').set('value', uval);

        // Добавляем реакцию на изменение полей:
        if (!M.local_open_courses_and_materials_individually.form.hasDelegated) {
            M.local_open_courses_and_materials_individually.form.hasDelegated = true;
            var baseField = Y.one('.availability-field');
            if (baseField) {
                baseField.delegate('change', function() {
                    M.core_availability.form.update();
                }, '.availability-delayedaccess select');
            }
        }

        return node;
    };

    /**
     * Сохраняет значения в объект.
     */
    M.local_open_courses_and_materials_individually.form.fillValue = function(valueObj, node) {
        valueObj.n = parseInt(node.one('select[name=delayed_count]').get('value'), 10);
        valueObj.d = parseInt(node.one('select[name=delayed_unit]').get('value'), 10);
    };

    /**
     * При ошибках тоже заполним, чтобы было консистентно.
     */
    M.local_open_courses_and_materials_individually.form.fillErrors = function(errorArr, node) {
        this.fillValue({}, node);
    };

}, '@VERSION@', {
    "requires": ["base", "node", "event", "moodle-core_availability-form"]
});