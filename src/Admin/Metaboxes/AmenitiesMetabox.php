<?php
namespace Sun\Apartament\Admin\Metaboxes;

/**
 * Метабокс для управления удобствами апартаментов
 *
 * @since 1.0.0
 */
class AmenitiesMetabox {

    /**
     * Регистрация метабокса
     */
    public function register() {
        add_action('add_meta_boxes', [$this, 'add_meta_box_property']);
        add_action('save_post', [$this, 'save_metabox'], 10, 2);
    }

    /**
     * Добавление метабоксов
     */
    public function add_meta_box_property() {
        // Метабокс для основных удобств
        add_meta_box(
            'sunapartament_basic_amenities',
            'Основные удобства',
            [$this, 'metabox_basic_amenities_html'],
            'apartament',
            'normal',
            'default'
        );

        // Метабокс для дополнительных удобств
        add_meta_box(
            'sunapartament_additional_amenities',
            'Дополнительные удобства',
            [$this, 'metabox_additional_amenities_html'],
            'apartament',
            'normal',
            'default'
        );
    }

    /**
     * HTML для основных удобств
     */
    public function metabox_basic_amenities_html($post) {
        wp_nonce_field('sunapartament_basic_amenities', 'sunapartament_basic_amenities_nonce');

        $square_footage = get_post_meta($post->ID, 'sunapartament_square_footage', true);
        $guest_count = get_post_meta($post->ID, 'sunapartament_guest_count', true);
        $floor_count = get_post_meta($post->ID, 'sunapartament_floor_count', true);

        $square_footage_icon = get_post_meta($post->ID, 'sunapartament_square_footage_icon', true);
        $guest_count_icon = get_post_meta($post->ID, 'sunapartament_guest_count_icon', true);
        $floor_count_icon = get_post_meta($post->ID, 'sunapartament_floor_count_icon', true);

        echo '
        <p>
            <label for="sunapartament_square_footage">' . esc_html__('Квадратура квартиры:', 'sunapartament') . '</label>
            <input type="number" id="sunapartament_square_footage" name="sunapartament_square_footage" value="' . esc_attr($square_footage) . '">
            <label for="sunapartament_square_footage_icon">' . esc_html__('Иконка:', 'sunapartament') . '</label>
            <input type="text" id="sunapartament_square_footage_icon" name="sunapartament_square_footage_icon" value="' . esc_attr($square_footage_icon) . '">
            <button type="button" class="upload-icon-button button" data-target="sunapartament_square_footage_icon">Загрузить иконку</button>
        </p>
        <p>
            <label for="sunapartament_guest_count">' . esc_html__('Количество гостей:', 'sunapartament') . '</label>
            <input type="number" id="sunapartament_guest_count" name="sunapartament_guest_count" value="' . esc_attr($guest_count) . '">
            <label for="sunapartament_guest_count_icon">' . esc_html__('Иконка:', 'sunapartament') . '</label>
            <input type="text" id="sunapartament_guest_count_icon" name="sunapartament_guest_count_icon" value="' . esc_attr($guest_count_icon) . '">
            <button type="button" class="upload-icon-button button" data-target="sunapartament_guest_count_icon">Загрузить иконку</button>
        </p>
        <p>
            <label for="sunapartament_floor_count">' . esc_html__('Этаж:', 'sunapartament') . '</label>
            <input type="number" id="sunapartament_floor_count" name="sunapartament_floor_count" value="' . esc_attr($floor_count) . '">
            <label for="sunapartament_floor_count_icon">' . esc_html__('Иконка:', 'sunapartament') . '</label>
            <input type="text" id="sunapartament_floor_count_icon" name="sunapartament_floor_count_icon" value="' . esc_attr($floor_count_icon) . '">
            <button type="button" class="upload-icon-button button" data-target="sunapartament_floor_count_icon">Загрузить иконку</button>
        </p>
        ';
    }

    /**
     * HTML для дополнительных удобств
     */
    public function metabox_additional_amenities_html($post) {
        wp_nonce_field('sunapartament_additional_amenities', 'sunapartament_additional_amenities_nonce');

        $amenities = get_post_meta($post->ID, 'sunapartament_additional_amenities', true);
        $amenities = $amenities ? $amenities : [];

        echo '<div id="sunapartament-additional-amenities">';
        foreach ($amenities as $index => $amenity) {
            echo '
            <div class="amenity">
                <p>
                    <label for="sunapartament_amenity_name_' . $index . '">' . esc_html__('Название удобства:', 'sunapartament') . '</label>
                    <input type="text" id="asunapartament_amenity_name_' . $index . '" name="sunapartament_additional_amenities[' . $index . '][name]" value="' . esc_attr($amenity['name']) . '">
                </p>
                <p>
                    <label for="sunapartament_amenity_icon_' . $index . '">' . esc_html__('Иконка:', 'sunapartament') . '</label>
                    <input type="text" id="sunapartament_amenity_icon_' . $index . '" name="sunapartament_additional_amenities[' . $index . '][icon]" value="' . esc_attr($amenity['icon']) . '">
                    <button type="button" class="upload-icon-button button" data-target="sunapartament_amenity_icon_' . $index . '">Загрузить иконку</button>
                </p>
                <p>
                    <label for="sunapartament_amenity_category_' . $index . '">' . esc_html__('Категория:', 'sunapartament') . '</label>
                    <select id="sunapartament_amenity_category_' . $index . '" name="sunapartament_additional_amenities[' . $index . '][category]">
                        <option value="beds" ' . selected('beds', $amenity['category'], false) . '>Кровати</option>
                        <option value="internet" ' . selected('internet', $amenity['category'], false) . '>Интернет</option>
                        <option value="furniture" ' . selected('furniture', $amenity['category'], false) . '>Мебель</option>
                        <option value="bathroom" ' . selected('bathroom', $amenity['category'], false) . '>Ванная комната</option>
                        <option value="kitchen" ' . selected('kitchen', $amenity['category'], false) . '>Кухня</option>
                        <option value="video" ' . selected('video', $amenity['category'], false) . '>Видео/аудио</option>
                        <option value="electronics" ' . selected('electronics', $amenity['category'], false) . '>Электроника</option>
                        <option value="area" ' . selected('area', $amenity['category'], false) . '>Внутренний двор и вид из окна</option>
                        <option value="other" ' . selected('other', $amenity['category'], false) . '>Прочее</option>
                    </select>
                </p>
                <button type="button" class="remove-amenity button">Удалить удобство</button>
            </div>
            ';
        }
        echo '</div>
        <button type="button" id="add-amenity" class="button">Добавить удобство</button>
        ';
    }

    /**
     * Сохранение данных
     */
    public function save_metabox($post_id, $post) {
        // Проверка nonce для основных удобств
        if (!isset($_POST['sunapartament_basic_amenities_nonce']) || !wp_verify_nonce($_POST['sunapartament_basic_amenities_nonce'], 'sunapartament_basic_amenities')) {
            return $post_id;
        }

        // Проверка nonce для дополнительных удобств
        if (!isset($_POST['sunapartament_additional_amenities_nonce']) || !wp_verify_nonce($_POST['sunapartament_additional_amenities_nonce'], 'sunapartament_additional_amenities')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ($post->post_type != 'apartament') {
            return $post_id;
        }

        // Проверка прав доступа
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Сохранение основных удобств
        if (isset($_POST['sunapartament_square_footage'])) {
            update_post_meta($post_id, 'sunapartament_square_footage', sanitize_text_field($_POST['sunapartament_square_footage']));
        }
        if (isset($_POST['sunapartament_guest_count'])) {
            update_post_meta($post_id, 'sunapartament_guest_count', sanitize_text_field($_POST['sunapartament_guest_count']));
        }
        if (isset($_POST['sunapartament_floor_count'])) {
            update_post_meta($post_id, 'sunapartament_floor_count', sanitize_text_field($_POST['sunapartament_floor_count']));
        }

        // Сохранение иконок для основных удобств
        if (isset($_POST['sunapartament_square_footage_icon'])) {
            update_post_meta($post_id, 'sunapartament_square_footage_icon', esc_url_raw($_POST['sunapartament_square_footage_icon']));
        }
        if (isset($_POST['sunapartament_guest_count_icon'])) {
            update_post_meta($post_id, 'sunapartament_guest_count_icon', esc_url_raw($_POST['sunapartament_guest_count_icon']));
        }
        if (isset($_POST['sunapartament_floor_count_icon'])) {
            update_post_meta($post_id, 'sunapartament_floor_count_icon', esc_url_raw($_POST['sunapartament_floor_count_icon']));
        }

        // Сохранение дополнительных удобств
        if (isset($_POST['sunapartament_additional_amenities'])) {
            $additional_amenities = array_map(function($amenity) {
                return [
                    'name' => sanitize_text_field($amenity['name']),
                    'icon' => esc_url_raw($amenity['icon']),
                    'category' => sanitize_text_field($amenity['category']),
                ];
            }, $_POST['sunapartament_additional_amenities']);

            update_post_meta($post_id, 'sunapartament_additional_amenities', $additional_amenities);
        } else {
            delete_post_meta($post_id, 'sunapartament_additional_amenities');
        }
    }
}