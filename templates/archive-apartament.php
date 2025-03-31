<?php
/**
 * 
 * Template Name: Апартаменты2
 * Шаблон архивной страницы апартаментов
 *
 * @package Sun\Apartament
 */

get_header('flat');
?>

<main>
    <section class="section section-breadcrumbs">
        <div class="container">
        <?php 
        if (function_exists('custom_breadcrumbs')) {
            custom_breadcrumbs();
        }
        ?>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <h2 class="section-title h3-title"><?php esc_html_e('Апартаменты', 'sun-apartament'); ?></h2>

            <div class="row row-cols-1 row-cols-xl-3 row-cols-lg-2 row-cols-md-2 g-4 section-grid">
                <?php
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

                if (!empty($_POST['submit'])) {
                    $args = array(
                        'post_type' => 'apartament',
                        'posts_per_page' => 10,
                        'paged' => $paged,
                        'meta_query' => array('relation' => 'AND'),
                        'tax_query' => array('relation' => 'AND'),
                    );

                    if (isset($_POST['sun_apartament_type']) && $_POST['sun_apartament_type'] != '') {
                        array_push($args['meta_query'], array(
                            'key' => 'sun_apartament_type',
                            'value' => sanitize_text_field($_POST['sun_apartament_type']),
                        ));
                    }

                    if (isset($_POST['sun_apartament_price']) && $_POST['sun_apartament_price'] != '') {
                        array_push($args['meta_query'], array(
                            'key' => 'sun_apartament_price',
                            'value' => sanitize_text_field($_POST['sun_apartament_price']),
                            'type' => 'numeric',
                            'compare' => '<=',
                        ));
                    }

                    if (isset($_POST['sun_apartament_apartament-type']) && $_POST['sun_apartament_apartament-type'] != '') {
                        array_push($args['tax_query'], array(
                            'taxonomy' => 'apartament-type',
                            'terms' => $_POST['sun_apartament_apartament-type'],
                        ));
                    }

                    $apartaments = new WP_Query($args);

                    if ($apartaments->have_posts()) {
                        while ($apartaments->have_posts()) {
                            $apartaments->the_post();
                            // Используем новый сервис шаблонов
                            $template_service = new \Sun\Apartament\Services\TemplateService();
                            $template_service->get_template_part('partials/content', array('price_service' => new \Sun\Apartament\Services\PriceService()));
                        }
                    } else {
                        echo '<p>' . esc_html__('Апартаменты не найдены', 'sun-apartament') . '</p>';
                    }

                    // Пагинация для пользовательского запроса
                    echo paginate_links(array(
                        'total' => $apartaments->max_num_pages,
                        'current' => $paged,
                        'prev_text' => __('&laquo; Предыдущая', 'sun-apartament'),
                        'next_text' => __('Следующая &raquo;', 'sun-apartament'),
                    ));

                    wp_reset_postdata();
                } else {
                    if (have_posts()) {
                        while (have_posts()) {
                            the_post();
                            // Используем новый сервис шаблонов
                            $template_service = new \Sun\Apartament\Services\TemplateService();
                            $template_service->get_template_part('partials/content', array('price_service' => new \Sun\Apartament\Services\PriceService()));
                        }
                    } else {
                        echo '<p>' . esc_html__('Апартаменты не найдены', 'sun-apartament') . '</p>';
                    }
                }
                ?>
            </div>
        </div>
        
        <?php if (!empty($wp_query->max_num_pages) && $wp_query->max_num_pages > 1): ?>
        <div class="custom-pagination-container text-center">
            <?php 
            echo paginate_links(array(
                'total' => $wp_query->max_num_pages,
                'current' => $paged,
                'prev_text' => '<span class="custom-prev-class">&laquo; ' . esc_html__('Предыдущая', 'sun-apartament') . '</span>',
                'next_text' => '<span class="custom-next-class">' . esc_html__('Следующая', 'sun-apartament') . ' &raquo;</span>',
                'before_page_number' => '<span class="custom-page-number">',
                'after_page_number' => '</span>',
            ));
            ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php
get_footer();
?>