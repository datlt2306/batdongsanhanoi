<?php

/**
 * Template hiển thị 1 item dự án trong trang /du-an
 * Biến $value là object WP_Post của post type du-an
 */
?>
<article class="card card--project project-item">
  <a
    class="card__thumb"
    href="<?= get_permalink($value->ID); ?>"
    aria-label="Xem chi tiết dự án <?= esc_attr($value->post_title); ?>"
    title="Xem chi tiết dự án <?= esc_attr($value->post_title); ?>">
    <?= bds_get_optimized_image(get_post_thumbnail_id($value->ID), 'bds-card', ['class' => 'project-item__image'], true); ?>
    <span class="card__thumb-overlay"></span>
  </a>
  <div class="card__body project-item__content">
    <h3 class="card__title project-item__title">
      <a href="<?= get_permalink($value->ID); ?>"><?= esc_html($value->post_title); ?></a>
    </h3>
    <div class="card__location">
      <span aria-hidden="true" class="icon-pin">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/>
          <circle cx="12" cy="11" r="2.5"/>
        </svg>
      </span>
      <span><?= esc_html(get_field('address', $value->ID)); ?></span>
    </div>
    <?php
    $quy_mo = get_field('quy_mo', $value->ID);
    if ($quy_mo) : ?>
      <div class="card__location">
        <span aria-hidden="true" class="icon-pin">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
            <path d="M6 12H4a2 2 0 0 0-2 2v8h4"/>
            <path d="M18 9h2a2 2 0 0 1 2 2v11h-4"/>
            <path d="M10 6h4"/>
            <path d="M10 10h4"/>
            <path d="M10 14h4"/>
            <path d="M10 18h4"/>
          </svg>
        </span>
        <span><?= esc_html($quy_mo); ?></span>
      </div>
    <?php endif; ?>
    <div class="card__divider"></div>
    <div class="card__footer">
      <span class="price">
        <?php $price = get_field('price', $value->ID); ?>
        <?= ($price && $price > 0) ? esc_html($price) . ' tr/m2' : 'Liên hệ'; ?>
      </span>
      <a class="card__cta inline-flex items-center gap-1" href="<?= get_permalink($value->ID); ?>">Chi tiết <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a>
    </div>
  </div>
</article>