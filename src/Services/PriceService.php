<?php
namespace Sun\Apartament\Services;

use Sun\Apartament\Core\Apartament;

/**
 * Сервис для управления ценами
 *
 * @since 1.0.0
 */
class PriceService extends AbstractService {
    /**
     * Регистрирует сервис
     *
     * @return void
     */
    public function register() {
        add_action('admin_menu', [$this, 'add_price_settings_page']);
        add_action('admin_init', [$this, 'register_price_settings']);
        add_action('wp_ajax_update_daily_price', [$this, 'ajax_update_daily_price']);
        add_action('wp_ajax_bulk_update_prices', [$this, 'ajax_bulk_update_prices']);
    }
    
    /**
     * Добавляет страницу настроек цен
     * 
     * @return void
     */
    public function add_price_settings_page() {
        add_submenu_page(
            'edit.php?post_type=apartament',
            'Управление ценами',
            'Управление ценами',
            'manage_options',
            'apartament-prices',
            [$this, 'render_price_settings_page']
        );
    }
    
    /**
     * Регистрирует настройки цен
     * 
     * @return void
     */
    public function register_price_settings() {
        register_setting('apartament_price_settings', 'sun_apartament_default_price');
        register_setting('apartament_price_settings', 'sun_apartament_weekend_price_multiplier');
        register_setting('apartament_price_settings', 'sun_apartament_high_season_dates');
        register_setting('apartament_price_settings', 'sun_apartament_high_season_multiplier');
        
        add_settings_section(
            'apartament_price_settings_section',
            'Настройки цен по умолчанию',
            [$this, 'render_price_settings_section'],
            'apartament_price_settings'
        );
        
        add_settings_field(
            'sun_apartament_default_price',
            'Базовая цена за ночь (руб.)',
            [$this, 'render_default_price_field'],
            'apartament_price_settings',
            'apartament_price_settings_section'
        );
        
        add_settings_field(
            'sun_apartament_weekend_price_multiplier',
            'Коэффициент цены на выходные',
            [$this, 'render_weekend_multiplier_field'],
            'apartament_price_settings',
            'apartament_price_settings_section'
        );
        
        add_settings_field(
            'sun_apartament_high_season_dates',
            'Даты высокого сезона',
            [$this, 'render_high_season_dates_field'],
            'apartament_price_settings',
            'apartament_price_settings_section'
        );
        
        add_settings_field(
            'sun_apartament_high_season_multiplier',
            'Коэффициент цены в высокий сезон',
            [$this, 'render_high_season_multiplier_field'],
            'apartament_price_settings',
            'apartament_price_settings_section'
        );
    }
    
    /**
     * Выводит описание секции настроек
     * 
     * @return void
     */
    public function render_price_settings_section() {
        echo '<p>Здесь вы можете настроить базовые параметры цен, которые будут использоваться по умолчанию для всех апартаментов.</p>';
    }
    
    /**
     * Выводит поле для базовой цены
     * 
     * @return void
     */
    public function render_default_price_field() {
        $default_price = get_option('sun_apartament_default_price', 3000);
        echo '<input type="number" min="0" step="100" name="sun_apartament_default_price" value="' . esc_attr($default_price) . '" class="regular-text" />';
        echo '<p class="description">Базовая цена за ночь, которая будет использоваться для всех апартаментов, если не указано иное.</p>';
    }
    
    /**
     * Выводит поле для коэффициента цены на выходные
     * 
     * @return void
     */
    public function render_weekend_multiplier_field() {
        $weekend_multiplier = get_option('sun_apartament_weekend_price_multiplier', 1.2);
        echo '<input type="number" min="1" step="0.1" name="sun_apartament_weekend_price_multiplier" value="' . esc_attr($weekend_multiplier) . '" class="regular-text" />';
        echo '<p class="description">Коэффициент, на который умножается базовая цена в выходные дни (суббота, воскресенье).</p>';
    }
    
    /**
     * Выводит поле для дат высокого сезона
     * 
     * @return void
     */
    public function render_high_season_dates_field() {
        $high_season_dates = get_option('sun_apartament_high_season_dates', '');
        echo '<textarea name="sun_apartament_high_season_dates" rows="4" cols="50" class="large-text">' . esc_textarea($high_season_dates) . '</textarea>';
        echo '<p class="description">Укажите даты высокого сезона в формате YYYY-MM-DD - YYYY-MM-DD, по одному периоду на строку.</p>';
    }
    
    /**
     * Выводит поле для коэффициента цены в высокий сезон
     * 
     * @return void
     */
    public function render_high_season_multiplier_field() {
        $high_season_multiplier = get_option('sun_apartament_high_season_multiplier', 1.5);
        echo '<input type="number" min="1" step="0.1" name="sun_apartament_high_season_multiplier" value="' . esc_attr($high_season_multiplier) . '" class="regular-text" />';
        echo '<p class="description">Коэффициент, на который умножается базовая цена в даты высокого сезона.</p>';
    }
    
    /**
     * Отображает страницу настроек цен
     * 
     * @return void
     */
    public function render_price_settings_page() {
        ?>
        <div class="wrap">
            <h1>Управление ценами апартаментов</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general-settings" class="nav-tab nav-tab-active">Общие настройки</a>
                <a href="#apartament-prices" class="nav-tab">Цены по апартаментам</a>
                <a href="#bulk-operations" class="nav-tab">Массовые операции</a>
            </h2>
            
            <div id="general-settings" class="tab-content active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('apartament_price_settings');
                    do_settings_sections('apartament_price_settings');
                    submit_button();
                    ?>
                </form>
            </div>
            
            <div id="apartament-prices" class="tab-content" style="display: none;">
                <h2>Цены по апартаментам</h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Апартамент</th>
                            <th>Базовая цена</th>
                            <th>Цена в выходные</th>
                            <th>Цена в высокий сезон</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $apartaments = get_posts([
                            'post_type' => 'apartament',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);
                        
                        $default_price = get_option('sun_apartament_default_price', 3000);
                        $weekend_multiplier = get_option('sun_apartament_weekend_price_multiplier', 1.2);
                        $high_season_multiplier = get_option('sun_apartament_high_season_multiplier', 1.5);
                        
                        foreach ($apartaments as $apartament) {
                            $apartament_obj = new Apartament($apartament->ID);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($apartament->ID); ?>">
                                        <?php echo esc_html($apartament->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $base_price = get_post_meta($apartament->ID, '_apartament_base_price', true);
                                    if (empty($base_price)) {
                                        $base_price = $default_price;
                                    }
                                    ?>
                                    <input type="number" class="small-text" min="0" step="100" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="base" value="<?php echo esc_attr($base_price); ?>" />
                                    <button type="button" class="button update-price" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="base">Сохранить</button>
                                </td>
                                <td>
                                    <?php
                                    $weekend_price = get_post_meta($apartament->ID, '_apartament_weekend_price', true);
                                    if (empty($weekend_price)) {
                                        $weekend_price = $base_price * $weekend_multiplier;
                                    }
                                    ?>
                                    <input type="number" class="small-text" min="0" step="100" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="weekend" value="<?php echo esc_attr($weekend_price); ?>" />
                                    <button type="button" class="button update-price" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="weekend">Сохранить</button>
                                </td>
                                <td>
                                    <?php
                                    $high_season_price = get_post_meta($apartament->ID, '_apartament_high_season_price', true);
                                    if (empty($high_season_price)) {
                                        $high_season_price = $base_price * $high_season_multiplier;
                                    }
                                    ?>
                                    <input type="number" class="small-text" min="0" step="100" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="high_season" value="<?php echo esc_attr($high_season_price); ?>" />
                                    <button type="button" class="button update-price" data-apartament-id="<?php echo $apartament->ID; ?>" data-price-type="high_season">Сохранить</button>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $apartament->ID . '&action=edit&tab=prices'); ?>" class="button">
                                        Подробные настройки
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div id="bulk-operations" class="tab-content" style="display: none;">
                <h2>Массовые операции с ценами</h2>
                
                <div class="card">
                    <h3>Установка цен для периода</h3>
                    
                    <form id="bulk-price-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Апартамент</th>
                                <td>
                                    <select name="apartament_id" id="bulk_apartament_id">
                                        <option value="all">Все апартаменты</option>
                                        <?php
                                        foreach ($apartaments as $apartament) {
                                            echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Начальная дата</th>
                                <td>
                                    <input type="date" name="start_date" id="bulk_start_date" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Конечная дата</th>
                                <td>
                                    <input type="date" name="end_date" id="bulk_end_date" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Цена</th>
                                <td>
                                    <input type="number" name="price" id="bulk_price" min="0" step="100" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Тип операции</th>
                                <td>
                                    <select name="operation_type" id="bulk_operation_type">
                                        <option value="set">Установить точную цену</option>
                                        <option value="increase">Увеличить на сумму</option>
                                        <option value="decrease">Уменьшить на сумму</option>
                                        <option value="increase_percent">Увеличить на процент</option>
                                        <option value="decrease_percent">Уменьшить на процент</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Дни недели</th>
                                <td>
                                    <label><input type="checkbox" name="weekdays[]" value="1" checked> Понедельник</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="2" checked> Вторник</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="3" checked> Среда</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="4" checked> Четверг</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="5" checked> Пятница</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="6" checked> Суббота</label><br>
                                    <label><input type="checkbox" name="weekdays[]" value="7" checked> Воскресенье</label><br>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="bulk_update_prices">Применить</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Переключение вкладок
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                // Активация таба
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Показ контента активного таба
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });
            
            // Обработка сохранения цены
            $('.update-price').on('click', function() {
                var button = $(this);
                var apartament_id = button.data('apartament-id');
                var price_type = button.data('price-type');
                var price = $('input[data-apartament-id="' + apartament_id + '"][data-price-type="' + price_type + '"]').val();
                
                button.prop('disabled', true).text('Сохранение...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_daily_price',
                        security: '<?php echo wp_create_nonce('update_price_nonce'); ?>',
                        apartament_id: apartament_id,
                        price_type: price_type,
                        price: price
                    },
                    success: function(response) {
                        if (response.success) {
                            button.text('Сохранено!');
                            setTimeout(function() {
                                button.prop('disabled', false).text('Сохранить');
                            }, 1000);
                        } else {
                            alert('Ошибка: ' + response.data.message);
                            button.prop('disabled', false).text('Сохранить');
                        }
                    },
                    error: function() {
                        alert('Произошла ошибка при сохранении цены');
                        button.prop('disabled', false).text('Сохранить');
                    }
                });
            });
            
            // Обработка формы массового обновления цен
            $('#bulk-price-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submit_button = $('#bulk_update_prices');
                
                submit_button.prop('disabled', true).text('Применение...');
                
                // Собираем данные формы
                var apartament_id = $('#bulk_apartament_id').val();
                var start_date = $('#bulk_start_date').val();
                var end_date = $('#bulk_end_date').val();
                var price = $('#bulk_price').val();
                var operation_type = $('#bulk_operation_type').val();
                var weekdays = [];
                
                $('input[name="weekdays[]"]:checked').each(function() {
                    weekdays.push($(this).val());
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bulk_update_prices',
                        security: '<?php echo wp_create_nonce('bulk_update_prices_nonce'); ?>',
                        apartament_id: apartament_id,
                        start_date: start_date,
                        end_date: end_date,
                        price: price,
                        operation_type: operation_type,
                        weekdays: weekdays
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Цены успешно обновлены для ' + response.data.updated_dates + ' дат в ' + response.data.updated_apartaments + ' апартаментах.');
                        } else {
                            alert('Ошибка: ' + response.data.message);
                        }
                        
                        submit_button.prop('disabled', false).text('Применить');
                    },
                    error: function() {
                        alert('Произошла ошибка при обновлении цен');
                        submit_button.prop('disabled', false).text('Применить');
                    }
                });
            });
            
            // Показываем первую вкладку при загрузке
            $('.nav-tab-wrapper a:first').click();
        });
        </script>
        <?php
    }
    
    /**
     * AJAX-обработчик обновления цены
     * 
     * @return void
     */
    public function ajax_update_daily_price() {
        // Проверка nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_price_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
            return;
        }
        
        // Проверка прав
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'У вас недостаточно прав для выполнения этой операции.']);
            return;
        }
        
        // Проверка параметров
        if (!isset($_POST['apartament_id']) || !isset($_POST['price_type']) || !isset($_POST['price'])) {
            wp_send_json_error(['message' => 'Неверные параметры запроса.']);
            return;
        }
        
        $apartament_id = intval($_POST['apartament_id']);
        $price_type = sanitize_text_field($_POST['price_type']);
        $price = floatval($_POST['price']);
        
        // Валидация типа цены
        $valid_price_types = ['base', 'weekend', 'high_season'];
        if (!in_array($price_type, $valid_price_types)) {
            wp_send_json_error(['message' => 'Неверный тип цены.']);
            return;
        }
        
        // Сохраняем цену
        $meta_key = '_apartament_' . $price_type . '_price';
        update_post_meta($apartament_id, $meta_key, $price);
        
        wp_send_json_success(['message' => 'Цена успешно обновлена.']);
    }
    
    /**
     * AJAX-обработчик массового обновления цен
     * 
     * @return void
     */
    public function ajax_bulk_update_prices() {
        // Проверка nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'bulk_update_prices_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
            return;
        }
        
        // Проверка прав
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'У вас недостаточно прав для выполнения этой операции.']);
            return;
        }
        
        // Проверка параметров
        if (!isset($_POST['start_date']) || !isset($_POST['end_date']) || !isset($_POST['price']) || !isset($_POST['operation_type'])) {
            wp_send_json_error(['message' => 'Неверные параметры запроса.']);
            return;
        }
        
        $apartament_id = isset($_POST['apartament_id']) ? sanitize_text_field($_POST['apartament_id']) : 'all';
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $price = floatval($_POST['price']);
        $operation_type = sanitize_text_field($_POST['operation_type']);
        $weekdays = isset($_POST['weekdays']) ? array_map('intval', (array)$_POST['weekdays']) : [1, 2, 3, 4, 5, 6, 7];
        
        // Валидация дат
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        
        if ($start > $end) {
            wp_send_json_error(['message' => 'Начальная дата должна быть меньше или равна конечной.']);
            return;
        }
        
        // Валидация типа операции
        $valid_operations = ['set', 'increase', 'decrease', 'increase_percent', 'decrease_percent'];
        if (!in_array($operation_type, $valid_operations)) {
            wp_send_json_error(['message' => 'Неверный тип операции.']);
            return;
        }
        
        // Получаем список апартаментов
        if ($apartament_id === 'all') {
            $apartaments = get_posts([
                'post_type' => 'apartament',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
        } else {
            $apartaments = [intval($apartament_id)];
        }
        
        if (empty($apartaments)) {
            wp_send_json_error(['message' => 'Апартаменты не найдены.']);
            return;
        }
        
        // Обновляем цены
        $updated_dates = 0;
        $updated_apartaments = 0;
        
        foreach ($apartaments as $apt_id) {
            $apartament = new Apartament($apt_id);
            
            if (!$apartament->get_id()) {
                continue;
            }
            
            // Получаем текущие цены
            $daily_prices = get_post_meta($apt_id, 'sunapartament_daily_prices', true);
            $daily_prices = $daily_prices ? json_decode($daily_prices, true) : [];
            
            // Перебираем все даты в диапазоне
            $current = clone $start;
            $dates_updated = false;
            
            while ($current <= $end) {
                $weekday = intval($current->format('N')); // 1 (понедельник) до 7 (воскресенье)
                
                // Проверяем, входит ли день недели в выбранные
                if (!in_array($weekday, $weekdays)) {
                    $current->modify('+1 day');
                    continue;
                }
                
                $year = $current->format('Y');
                $month = $current->format('n');
                $day = $current->format('j');
                
                // Создаем структуру массива, если необходимо
                if (!isset($daily_prices[$year])) {
                    $daily_prices[$year] = [];
                }
                
                if (!isset($daily_prices[$year][$month])) {
                    $daily_prices[$year][$month] = [];
                }
                
                // Получаем текущую цену
                $current_price = isset($daily_prices[$year][$month][$day]) ? floatval($daily_prices[$year][$month][$day]) : 0;
                
                // Если текущая цена не установлена, используем базовую цену
                if ($current_price <= 0) {
                    $current_price = get_post_meta($apt_id, '_apartament_base_price', true);
                    
                    // Если базовая цена не установлена, используем цену по умолчанию
                    if (empty($current_price)) {
                        $current_price = get_option('sun_apartament_default_price', 3000);
                    }
                }
                
                // Применяем операцию
                $new_price = $current_price;
                
                switch ($operation_type) {
                    case 'set':
                        $new_price = $price;
                        break;
                    case 'increase':
                        $new_price = $current_price + $price;
                        break;
                    case 'decrease':
                        $new_price = max(0, $current_price - $price);
                        break;
                    case 'increase_percent':
                        $new_price = $current_price * (1 + $price / 100);
                        break;
                    case 'decrease_percent':
                        $new_price = $current_price * (1 - $price / 100);
                        break;
                }
                
                // Убеждаемся, что цена не отрицательная
                $new_price = max(0, $new_price);
                
                // Обновляем цену
                $daily_prices[$year][$month][$day] = $new_price;
                $updated_dates++;
                $dates_updated = true;
                
                // Переходим к следующей дате
                $current->modify('+1 day');
            }
            
            // Сохраняем обновленные цены
            if ($dates_updated) {
                update_post_meta($apt_id, 'sunapartament_daily_prices', json_encode($daily_prices));
                $updated_apartaments++;
            }
        }
        
        wp_send_json_success([
            'message' => 'Цены успешно обновлены.',
            'updated_dates' => $updated_dates,
            'updated_apartaments' => $updated_apartaments
        ]);
    }
    
    /**
     * Получает цену апартамента на указанную дату
     * 
     * @param int $apartament_id ID апартамента
     * @param string $date Дата в формате Y-m-d
     * @return float Цена
     */
    public function get_price_for_date($apartament_id, $date) {
        $date_obj = new \DateTime($date);
        $year = $date_obj->format('Y');
        $month = $date_obj->format('n');
        $day = $date_obj->format('j');
        $weekday = $date_obj->format('N'); // 1 (понедельник) до 7 (воскресенье)
        
        // Получаем сохраненные цены
        $prices_json = get_post_meta($apartament_id, 'sunapartament_daily_prices', true);
        $prices = $prices_json ? json_decode($prices_json, true) : [];
        
        // Проверяем наличие конкретной цены на указанную дату
        if (isset($prices[$year][$month][$day]) && is_numeric($prices[$year][$month][$day])) {
            return (float)$prices[$year][$month][$day];
        }
        
        // Если конкретная цена не найдена, определяем цену исходя из правил
        
        // Проверяем, является ли дата выходным днем (суббота или воскресенье)
        $is_weekend = ($weekday == 6 || $weekday == 7);
        
        // Проверяем, попадает ли дата в высокий сезон
        $is_high_season = $this->is_high_season_date($date);
        
        // Получаем базовую цену апартамента
        $base_price = get_post_meta($apartament_id, '_apartament_base_price', true);
        
        // Если базовая цена не установлена, используем цену по умолчанию
        if (empty($base_price)) {
            $base_price = get_option('sun_apartament_default_price', 3000);
        }
        
        // Если это выходной, применяем соответствующую цену или коэффициент
        if ($is_weekend) {
            $weekend_price = get_post_meta($apartament_id, '_apartament_weekend_price', true);
            
            if (!empty($weekend_price)) {
                return (float)$weekend_price;
            } else {
                // Если цена на выходные не установлена, применяем коэффициент
                $weekend_multiplier = get_option('sun_apartament_weekend_price_multiplier', 1.2);
                return $base_price * $weekend_multiplier;
            }
        }
        
        // Если это высокий сезон, применяем соответствующую цену или коэффициент
        if ($is_high_season) {
            $high_season_price = get_post_meta($apartament_id, '_apartament_high_season_price', true);
            
            if (!empty($high_season_price)) {
                return (float)$high_season_price;
            } else {
                // Если цена в высокий сезон не установлена, применяем коэффициент
                $high_season_multiplier = get_option('sun_apartament_high_season_multiplier', 1.5);
                return $base_price * $high_season_multiplier;
            }
        }
        
        // В остальных случаях возвращаем базовую цену
        return (float)$base_price;
    }
    
    /**
     * Проверяет, попадает ли дата в высокий сезон
     * 
     * @param string $date Дата в формате Y-m-d
     * @return bool Результат проверки
     */
    private function is_high_season_date($date) {
        $date_obj = new \DateTime($date);
        
        // Получаем настройки высокого сезона
        $high_season_dates = get_option('sun_apartament_high_season_dates', '');
        
        if (empty($high_season_dates)) {
            return false;
        }
        
        // Разбиваем строку на периоды
        $periods = explode("\n", $high_season_dates);
        
        foreach ($periods as $period) {
            $period = trim($period);
            
            if (empty($period)) {
                continue;
            }
            
            // Разбиваем период на начальную и конечную даты
            $dates = explode('-', $period);
            
            if (count($dates) != 2) {
                continue;
            }
            
            $start = new \DateTime(trim($dates[0]));
            $end = new \DateTime(trim($dates[1]));
            
            // Проверяем, попадает ли дата в период
            if ($date_obj >= $start && $date_obj <= $end) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получает цены апартамента на период
     * 
     * @param int $apartament_id ID апартамента
     * @param string $start_date Начальная дата в формате Y-m-d
     * @param string $end_date Конечная дата в формате Y-m-d
     * @return array Информация о ценах на период
     */
    public function get_prices_for_period($apartament_id, $start_date, $end_date) {
        // Преобразование строковых дат
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        
        // Установка времени в 00:00:00
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        
        // Рассчитываем количество ночей
        $nights = $end->diff($start)->days;
        
        // Рассчитываем цены
        $total_price = 0;
        $daily_prices = [];
        
        $current = clone $start;
        for ($i = 0; $i < $nights; $i++) {
            $date_str = $current->format('Y-m-d');
            $price = $this->get_price_for_date($apartament_id, $date_str);
            
            $daily_prices[$date_str] = $price;
            $total_price += $price;
            
            $current->modify('+1 day');
        }
        
        return [
            'nights' => $nights,
            'total_price' => $total_price,
            'daily_prices' => $daily_prices
        ];
    }
}