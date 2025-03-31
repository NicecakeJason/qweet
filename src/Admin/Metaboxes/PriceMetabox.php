<?php
namespace Sun\Apartament\Admin\Metaboxes;

use Sun\Apartament\Services\PriceService;

/**
 * Метабокс для управления ценами апартамента
 *
 * @since 1.0.0
 */
class PriceMetabox {

    /**
     * Сервис управления ценами
     *
     * @var PriceService
     */
    private $price_service;

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->price_service = new PriceService();
    }

    /**
     * Регистрация метабокса
     */
    public function register() {
        add_action('add_meta_boxes', [$this, 'add_meta_box_apartament_price']);
        add_action('save_post', [$this, 'save_metabox_price'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Добавляем скрипты и стили для административной панели
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        if (($hook == 'post.php' || $hook == 'post-new.php') && 
            (isset($post) && $post->post_type == 'apartament' || 
             isset($_GET['post_type']) && $_GET['post_type'] == 'apartament')) {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            
            // Добавляем стили для метабокса цен
            add_action('admin_head', function() {
                ?>
                <style>
                    .sunapartament-price-container { margin: 15px 0; }
                    .sunapartament-date-selector { margin-bottom: 15px; }
                    .sunapartament-date-selector label,
                    .sunapartament-date-selector select,
                    .sunapartament-date-selector button { margin-right: 10px; }
                    .sunapartament-days-container {
                        display: grid;
                        grid-template-columns: repeat(7, 1fr);
                        gap: 10px;
                    }
                    .sunapartament-day-price {
                        padding: 10px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                    }
                    .sunapartament-day-price label {
                        display: block;
                        margin-bottom: 5px;
                    }
                    .sunapartament-day-price input {
                        width: 100%;
                    }
                    .sunapartament-tabs {
                        margin-bottom: 15px;
                        border-bottom: 1px solid #ccc;
                    }
                    .sunapartament-tab {
                        background: #f7f7f7;
                        border: 1px solid #ccc;
                        border-bottom: none;
                        padding: 8px 12px;
                        margin-right: 5px;
                        cursor: pointer;
                    }
                    .sunapartament-tab.active {
                        background: #fff;
                        border-bottom: 1px solid #fff;
                        margin-bottom: -1px;
                    }
                    .sunapartament-tab-content {
                        display: none;
                        padding: 15px;
                        border: 1px solid #ccc;
                        border-top: none;
                    }
                    .sunapartament-tab-content.active {
                        display: block;
                    }
                    .sunapartament-bulk-row {
                        margin-bottom: 10px;
                    }
                    .sunapartament-bulk-row label {
                        display: inline-block;
                        width: 120px;
                    }
                    .sunapartament-day-header {
                        font-weight: bold;
                        text-align: center;
                        padding: 5px;
                    }
                </style>
                <?php
            });
        }
    }

    /**
     * Добавляем метабокс для цен по дням
     */
    public function add_meta_box_apartament_price() {
        add_meta_box(
            'sunapartament_price',
            'Цены по дням',
            [$this, 'metabox_sunapartament_price_html'],
            'apartament',
            'normal',
            'default'
        );
    }

    /**
     * HTML для метабокса с ценами по дням
     */
    public function metabox_sunapartament_price_html($post) {
        wp_nonce_field('sunapartament_price', 'sunapartament_price_nonce');

        // Получаем сохраненные цены
        $prices_json = get_post_meta($post->ID, 'sunapartament_daily_prices', true);
        
        echo '<div class="sunapartament-price-container">';
        
        // Вкладки для разных режимов
        echo '<div class="sunapartament-tabs">';
        echo '<button type="button" class="sunapartament-tab active" data-tab="calendar">Календарь</button>';
        echo '<button type="button" class="sunapartament-tab" data-tab="bulk">Массовое редактирование</button>';
        echo '</div>';
        
        // Вкладка календаря
        echo '<div class="sunapartament-tab-content active" id="tab-calendar">';
        
        // Выбор года и месяца для календаря
        echo '<div class="sunapartament-date-selector">';
        echo '<label for="sunapartament_year">Год:</label>';
        echo '<select id="sunapartament_year" name="sunapartament_year">';
        for ($year = date('Y') - 1; $year <= date('Y') + 3; $year++) {
            echo '<option value="' . $year . '">' . $year . '</option>';
        }
        echo '</select>';
        
        echo '<label for="sunapartament_month">Месяц:</label>';
        echo '<select id="sunapartament_month" name="sunapartament_month">';
        for ($month = 1; $month <= 12; $month++) {
            $selected = ($month == date('n')) ? 'selected' : '';
            echo '<option value="' . $month . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $month, 10)) . '</option>';
        }
        echo '</select>';
        
        echo '<button type="button" id="sunapartament_load_month" class="button">Загрузить</button>';
        echo '</div>';
        
        // Контейнер для календаря дней
        echo '<div id="sunapartament_days_container" class="sunapartament-days-container">';
        // JavaScript загрузит сюда дни выбранного месяца
        echo '</div>';
        
        echo '</div>'; // Конец вкладки календаря
        
        // Вкладка массового редактирования
        echo '<div class="sunapartament-tab-content" id="tab-bulk">';
        
        echo '<div class="sunapartament-bulk-edit">';
        echo '<h3>Установить цену для периода</h3>';
        
        echo '<div class="sunapartament-bulk-row">';
        echo '<label for="bulk_start_date">Начальная дата:</label>';
        echo '<input type="text" id="bulk_start_date" class="datepicker" placeholder="YYYY-MM-DD">';
        echo '</div>';
        
        echo '<div class="sunapartament-bulk-row">';
        echo '<label for="bulk_end_date">Конечная дата:</label>';
        echo '<input type="text" id="bulk_end_date" class="datepicker" placeholder="YYYY-MM-DD">';
        echo '</div>';
        
        echo '<div class="sunapartament-bulk-row">';
        echo '<label for="bulk_price">Цена:</label>';
        echo '<input type="number" min="0" id="bulk_price">';
        echo '</div>';
        
        echo '<button type="button" id="bulk_apply" class="button button-primary">Применить</button>';
        echo '</div>';
        
        echo '</div>'; // Конец вкладки массового редактирования
        
        // Скрытое поле для хранения всех цен в формате JSON
        echo '<input type="hidden" id="sunapartament_daily_prices" name="sunapartament_daily_prices" value="' . esc_attr($prices_json) . '">';
        
        echo '</div>'; // Конец контейнера
        
        // JavaScript для интерфейса
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Инициализация с текущими данными
                var pricesData = <?php echo !empty($prices_json) ? $prices_json : '{}'; ?>;
                
                // Инициализация вкладок
                $(".sunapartament-tab").on("click", function() {
                    $(".sunapartament-tab").removeClass("active");
                    $(this).addClass("active");
                    
                    var tabId = $(this).data("tab");
                    $(".sunapartament-tab-content").removeClass("active");
                    $("#tab-" + tabId).addClass("active");
                });
                
                // Инициализация датапикеров
                $(".datepicker").datepicker({
                    dateFormat: "yy-mm-dd",
                    changeMonth: true,
                    changeYear: true
                });
                
                // Загрузка дней выбранного месяца
                $("#sunapartament_load_month").on("click", function() {
                    var year = $("#sunapartament_year").val();
                    var month = $("#sunapartament_month").val();
                    loadDaysForMonth(year, month);
                });
                
                // Обработка изменений цен
                $(document).on("change", ".day-price-input", function() {
                    var year = $(this).data("year");
                    var month = $(this).data("month");
                    var day = $(this).data("day");
                    var price = $(this).val();
                    
                    updatePrice(year, month, day, price);
                });
                
                // Массовое редактирование
                $("#bulk_apply").on("click", function() {
                    var startDate = $("#bulk_start_date").val();
                    var endDate = $("#bulk_end_date").val();
                    var price = $("#bulk_price").val();
                    
                    if (!startDate || !endDate || !price) {
                        alert("Пожалуйста, заполните все поля");
                        return;
                    }
                    
                    var start = new Date(startDate);
                    var end = new Date(endDate);
                    
                    if (start > end) {
                        alert("Начальная дата должна быть меньше или равна конечной");
                        return;
                    }
                    
                    // Применяем цену для каждого дня в периоде
                    var current = new Date(start);
                    while (current <= end) {
                        var year = current.getFullYear();
                        var month = current.getMonth() + 1;
                        var day = current.getDate();
                        
                        updatePrice(year, month, day, price);
                        
                        current.setDate(current.getDate() + 1);
                    }
                    
                    alert("Цены успешно применены");
                    
                    // Если открыт календарь, обновляем его
                    if ($("#tab-calendar").hasClass("active")) {
                        var year = $("#sunapartament_year").val();
                        var month = $("#sunapartament_month").val();
                        loadDaysForMonth(year, month);
                    }
                });
                
                // Функция обновления цены
                function updatePrice(year, month, day, price) {
                    if (!pricesData[year]) {
                        pricesData[year] = {};
                    }
                    
                    if (!pricesData[year][month]) {
                        pricesData[year][month] = {};
                    }
                    
                    pricesData[year][month][day] = price;
                    
                    // Обновляем скрытое поле
                    $("#sunapartament_daily_prices").val(JSON.stringify(pricesData));
                }
                
                // Функция загрузки дней месяца
                function loadDaysForMonth(year, month) {
                    var container = $("#sunapartament_days_container");
                    container.empty();
                    
                    // Определяем количество дней в месяце
                    var daysInMonth = new Date(year, month, 0).getDate();
                    
                    // Определяем день недели первого дня месяца (0 = воскресенье)
                    var firstDayOfMonth = new Date(year, month - 1, 1).getDay();
                    if (firstDayOfMonth === 0) firstDayOfMonth = 7; // Преобразуем 0 (воскресенье) в 7
                    
                    // Добавляем заголовки дней недели
                    var daysOfWeek = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];
                    for (var i = 0; i < 7; i++) {
                        container.append("<div class='sunapartament-day-header'>" + daysOfWeek[i] + "</div>");
                    }
                    
                    // Добавляем пустые ячейки до первого дня месяца
                    for (var i = 1; i < firstDayOfMonth; i++) {
                        container.append("<div></div>");
                    }
                    
                    // Добавляем дни месяца
                    for (var day = 1; day <= daysInMonth; day++) {
                        var price = pricesData[year] && pricesData[year][month] && pricesData[year][month][day] 
                            ? pricesData[year][month][day] 
                            : "";
                        
                        var dayDiv = $("<div class='sunapartament-day-price'></div>");
                        var label = $("<label for='price_" + year + "_" + month + "_" + day + "'>" + day + "</label>");
                        var input = $("<input type='number' min='0' id='price_" + year + "_" + month + "_" + day + "' class='day-price-input' value='" + price + "'>");
                        
                        input.attr("data-year", year);
                        input.attr("data-month", month);
                        input.attr("data-day", day);
                        
                        dayDiv.append(label).append(input);
                        container.append(dayDiv);
                    }
                }
                
                // Загружаем текущий месяц при загрузке страницы
                var currentYear = new Date().getFullYear();
                var currentMonth = new Date().getMonth() + 1;
                $("#sunapartament_year").val(currentYear);
                $("#sunapartament_month").val(currentMonth);
                loadDaysForMonth(currentYear, currentMonth);
            });
        </script>
        <?php
    }

    /**
     * Сохранение данных из метабокса
     */
    public function save_metabox_price($post_id, $post) {
        if (!isset($_POST['sunapartament_price_nonce']) || !wp_verify_nonce($_POST['sunapartament_price_nonce'], 'sunapartament_price')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ($post->post_type != 'apartament') {
            return $post_id;
        }

        // Сохраняем цены в формате JSON
        if (isset($_POST['sunapartament_daily_prices'])) {
            $prices_json = sanitize_textarea_field($_POST['sunapartament_daily_prices']);
            update_post_meta($post_id, 'sunapartament_daily_prices', $prices_json);
        }
    }

    /**
     * Отображение информации о бронировании
     */
    public function display_booking_info($post_id, $start_date, $end_date) {
        $prices_data = $this->price_service->get_prices_for_period($post_id, $start_date, $end_date);
        
        $output = '<div class="booking-info">';
        $output .= '<p>Период: с ' . date_i18n('d.m.Y', strtotime($start_date)) . ' по ' . date_i18n('d.m.Y', strtotime($end_date)) . '</p>';
        $output .= '<p>Количество ночей: ' . $prices_data['nights'] . '</p>';
        $output .= '<p>Итоговая стоимость: ' . number_format($prices_data['total_price'], 0, '.', ' ') . ' руб.</p>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Отображение текущей цены
     */
    public function display_current_price($post_id) {
        return $this->price_service->get_price_for_date($post_id, current_time('Y-m-d'));
    }
}