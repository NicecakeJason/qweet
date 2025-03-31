<?php
/**
 * Шаблон для отображения списка апартаментов
 *
 * @package Sun\Apartament
 * @since 1.0.0
 *
 * @var array $grouped_posts Сгруппированные посты по категориям
 */

// Проверяем, есть ли данные для отображения
if (empty($grouped_posts)) {
    return;
}

// Выводим сгруппированные посты по категориям
foreach ($grouped_posts as $group) {
    $term = $group['term'];
    $posts = $group['posts'];

    // Проверяем, есть ли посты в группе
    if (empty($posts)) {
        continue;
    }

    // Вывод названия рубрики (если есть)
    if ($term) {
        echo '<h2 class="section-title h3-title">' . esc_html($term->name) . '</h2>';

        // Вывод описания рубрики (если есть)
        if (!empty($term->description)) {
            echo '<div class="category-description">' . esc_html($term->description) . '</div>';
        }
    }

    // Начало обертки для постов
    echo '<div class="row row-cols-1 row-cols-xl-3 row-cols-lg-2 row-cols-md-2 g-4 mb-5 section-grid">';

    // Вывод постов в этой рубрике
    foreach ($posts as $post) {
        $post_id = $post->ID;
        ?>
        <div id="post-<?php echo $post_id; ?>" <?php post_class('col'); ?> role="article">
            <div class="card">
                <?php
                $gallery_images = $post->meta['gallery'];
                if ($gallery_images) {
                    echo '<div class="slick">';
                    foreach ($gallery_images as $image_id) {
                        // Получаем URL поста
                        $post_url = get_permalink($post_id);

                        // Выводим изображение, обернутое в ссылку на пост
                        echo '<div>';
                        echo '<a href="' . esc_url($post_url) . '">';
                        echo wp_get_attachment_image($image_id, 'large', false, array(
                            'class' => 'img-fluid img-card',
                            'alt' => 'Property Image',
                        ));
                        echo '</a>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <h4 class="detail-room__title amenities-card__title h4-title">
                        <?php echo esc_html($post->post_title); ?>
                    </h4>
                </a>
                <?php
                $square_footage = $post->meta['square_footage'];
                $guest_count = $post->meta['guest_count'];
                $floor_count = $post->meta['floor_count'];

                $square_footage_icon = $post->meta['square_footage_icon'];
                $guest_count_icon = $post->meta['guest_count_icon'];
                $floor_count_icon = $post->meta['floor_count_icon'];

                if ($square_footage || $guest_count || $floor_count) {
                    echo '<div class="card-meta">';
                    echo '<ul class="d-flex justify-content-between">';
                    if ($square_footage) {
                        echo '<li class="card-meta__item">';
                        echo '<div class="wrapper">';
                        if ($square_footage_icon) {
                            echo '<img class="card-icon" src="' . esc_url($square_footage_icon) . '" alt="Площадь" class="icon">';
                        }
                        echo '<span class="card-meta__text">' . esc_html($square_footage) . ' м²</span>';
                        echo '</div>';
                        echo '</li>';
                    }

                    if ($floor_count) {
                        echo '<li class="card-meta__item">';
                        echo '<div class="wrapper">';
                        if ($floor_count_icon) {
                            echo '<img class="card-icon" src="' . esc_url($floor_count_icon) . '" alt="Кровать" class="icon">';
                        }
                        echo '<span class="card-meta__text">' . esc_html($floor_count) . ' этаж</span>';
                        echo '</div>';
                        echo '</li>';
                    }

                    if ($guest_count) {
                        echo '<li class="card-meta__item">';
                        echo '<div class="wrapper">';
                        if ($guest_count_icon) {
                            echo '<img class="card-icon" src="' . esc_url($guest_count_icon) . '" alt="Гости" class="icon">';
                        }
                        echo '<span class="card-meta__text">До ' . esc_html($guest_count) . ' мест</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                ?>
                <div class="clearfix facilities-amenities">
                    <div class="card-cost d-flex justify-content-between align-items-center">
                        <?php
                        // Выводим цену
                        $current_price = $post->meta['current_price'];
                        echo '<div class="d-flex flex-column">';
                        echo '<span class="cost">' . esc_html($current_price) . ' ₽ </span>';
                        echo '<span class="day">за ночь</span>';
                        echo '</div>';
                        ?>
                        <div>
                            <a class="card-btn" href="<?php echo esc_url(get_permalink($post_id)); ?>">Подробнее</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // Закрываем обертку для постов
    echo '</div>';
}