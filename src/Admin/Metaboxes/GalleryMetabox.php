<?php
namespace Sun\Apartament\Admin\Metaboxes;

/**
 * Метабокс для управления галереей изображений апартамента
 *
 * @since 1.0.0
 */
class GalleryMetabox {

    /**
     * Регистрация метабокса
     */
    public function register() {
        add_action('add_meta_boxes', [$this, 'add_meta_box_apartament']);
        add_action('save_post', [$this, 'save_metabox'], 10, 2);
    }

    /**
     * Добавление метабокса
     */
    public function add_meta_box_apartament() {
        add_meta_box(
            'sunapartamentimg_gallery',
            'Слайдер фотографий',
            [$this, 'metabox_gallery_html'],
            'apartament',
            'normal',
            'default'
        );
    }

    /**
     * HTML для метабокса галереи
     */
    public function metabox_gallery_html($post) {
        wp_nonce_field('sunapartament_gallery', 'sunapartament_gallery_nonce');

        $gallery_images = get_post_meta($post->ID, 'sunapartament_gallery', true);
        $gallery_images = $gallery_images ? $gallery_images : [];

        echo '<div class="sunapartament-gallery">';
        echo '<ul class="sunapartament-gallery-images">';
        foreach ($gallery_images as $image_id) {
            echo '<li class="d-flex justify-content-between">' . wp_get_attachment_image($image_id, 'thumbnail') . '<a href="#" class="remove-image align-self-center btn btn-danger" data-image-id="' . esc_attr($image_id) . '">X</a></li>';
        }
        echo '</ul>';
        echo '<input type="hidden" id="sunapartament_gallery_ids" name="sunapartament_gallery_ids" value="' . esc_attr(implode(',', $gallery_images)) . '">';
        echo '<button type="button" class="upload-gallery-images btn btn-primary">Загрузить фотографии</button>';
        echo '</div>';
    }

    /**
     * Сохранение данных метабокса
     */
    public function save_metabox($post_id, $post) {
        if (!isset($_POST['sunapartament_gallery_nonce']) || !wp_verify_nonce($_POST['sunapartament_gallery_nonce'], 'sunapartament_gallery')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ($post->post_type != 'apartament') {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        if (isset($_POST['sunapartament_gallery_ids'])) {
            $gallery_images = array_map('intval', explode(',', $_POST['sunapartament_gallery_ids']));
            update_post_meta($post_id, 'sunapartament_gallery', $gallery_images);
        } else {
            delete_post_meta($post_id, 'sunapartament_gallery');
        }
    }
}