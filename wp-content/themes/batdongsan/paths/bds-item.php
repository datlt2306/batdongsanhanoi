<div class="bds-item item-hang-chuyen-nhuong">
    <a href="<?= get_permalink($value->ID) ?>" class="bds-item__images">
        <span><?= bds_get_optimized_image(get_post_thumbnail_id($value->ID), 'bds-card', ['class' => 'bds-item__image'], true) ?></span>
        <?php
        $gallery = get_field('gallery', $value->ID);
        if ($gallery && is_array($gallery)) {
            $dem = 0;
            foreach ($gallery as $imgItem) {
                if ($dem < 5) {
                    echo '<span class="bds-item__image-wrapper">' . bds_get_optimized_image($imgItem, 'bds-card', ['class' => 'bds-item__image'], true) . '</span>';
                }
                $dem++;
            }
        }
        ?>
    </a>
    <div class="bds-item__content">
        <h3 class="bds-item__title"><a href="<?= get_permalink($value->ID) ?>" title="<?= esc_attr($value->post_title) ?>"><?= esc_html($value->post_title) ?></a></h3>
        <span><?= esc_html(get_field('address', $value->ID)) ?></span>
        <div class="bds-item__info">
            <span class="bds-item__price"><?= get_field('gia_ban', $value->ID) ? get_field('gia_ban', $value->ID) . ' tỷ' : 'Thỏa thuận' ?> - <?= get_field('dien_tich', $value->ID) ?> m2</span>
            <span>
                <?= get_field('so_phong_ngu', $value->ID) ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block ml-1">
                    <path d="M3 7v13M21 7v13M3 17h18M3 11h18M7 11V8a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v3"/>
                </svg>
            </span>
            <span>
                <?= get_field('toilet', $value->ID) ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block ml-1">
                    <path d="M4 12h16c0 4.4-3.6 8-8 8s-8-3.6-8-8ZM6 12V5a2 2 0 0 1 2-2h1M4 21l1.5-2M20 21l-1.5-2"/>
                </svg>
            </span>
            <span>
                <?php
                $address = get_field('dia_chi', $value->ID);
                if ($address) {
                    $address_arr = explode(',', $address);
                    $len = count($address_arr);
                    if ($len >= 2) {
                        echo esc_html($address_arr[$len - 2] . ', ' . $address_arr[$len - 1]);
                    } else {
                        echo esc_html($address);
                    }
                }
                ?>
            </span>
        </div>
        <p>
            <?php
            echo wp_trim_words($value->post_content, 20);
            ?>
        </p>
    </div>
</div>
<hr>
<a href="tel:<?= get_field('phone', 'option') ?>" class="bds-item__contact"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
    </svg>
    Liên hệ ngay <?= get_field('phone', 'option') ?></a>
</div>