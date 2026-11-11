<?php

/**
 * Category template: Tin Dự Án
 *
 * Hiển thị danh sách bài viết thuộc chuyên mục tin dự án.
 */

get_header();

$category      = get_queried_object();
$category_name = single_cat_title('', false);
$description   = category_description();
?>

<main class="news-layout pb-16">
	<!-- Header với ngày tháng và thông tin thị trường -->
	<div class="bg-surface/80 backdrop-blur-md border-b border-black/[0.06] py-3">
		<div class="max-w-7xl mx-auto px-4">
			<div class="flex justify-between items-center gap-5 text-xs text-charcoal/70 uppercase font-medium max-lg:flex-col max-lg:items-start max-lg:gap-2">
				<span class="text-charcoal-muted">
					<?php echo esc_html(date_i18n('l, \N\g\à\y d/m/Y')); ?>
				</span>
				<span class="text-primary font-semibold tracking-wider">
					<?php esc_html_e('Bất Động Sản Hà Nội - Cổng Thông Tin Dự Án', 'batdongsan'); ?>
				</span>
			</div>
		</div>
	</div>

	<div class="container flex items-start gap-10 mt-8 max-lg:flex-col max-lg:gap-8">
		<div class="flex-1 min-w-0" role="main">
			<header class="mb-8 pb-5 border-b border-black/[0.06]">
				<h1 class="text-2xl md:text-3xl font-bold tracking-tight mb-2 text-charcoal">
					<?php echo esc_html($category_name ?: 'Tin tức dự án bất động sản'); ?>
				</h1>

				<?php if (!empty($description)) : ?>
					<p class="m-0 text-gray-500 text-[15px] leading-relaxed">
						<?php echo wp_kses_post($description); ?>
					</p>
				<?php else : ?>
					<p class="m-0 text-gray-500 text-[15px] leading-relaxed">
						Cập nhật liên tục các tin tức, phân tích và cơ hội đầu tư từ các dự án bất động sản nổi bật.
					</p>
				<?php endif; ?>
			</header>

			<?php if (have_posts()) : ?>
				<?php
				while (have_posts()) :
					the_post();
					get_template_part('template-parts/content', 'news-card');
				endwhile;
				?>

				<nav class="pagination flex justify-between items-center mt-8 pt-6 border-t border-gray-200" aria-label="<?php esc_attr_e('Điều hướng trang', 'batdongsan'); ?>">
					<div class="[&>a]:text-gray-600 [&>a]:font-medium [&>a]:no-underline hover:[&>a]:text-primary">
						<?php previous_posts_link(__('← Bài mới hơn', 'batdongsan')); ?>
					</div>
					<div class="[&>a]:text-gray-600 [&>a]:font-medium [&>a]:no-underline hover:[&>a]:text-primary">
						<?php next_posts_link(__('Bài cũ hơn →', 'batdongsan')); ?>
					</div>
				</nav>
			<?php else : ?>
				<p class="text-gray-500 text-center py-10"><?php esc_html_e('Chưa có bài viết.', 'batdongsan'); ?></p>
			<?php endif; ?>
		</div>

		<aside class="w-[30%] shrink-0 sticky top-24 max-lg:w-full max-lg:static" aria-label="<?php esc_attr_e('Sidebar tin tức', 'batdongsan'); ?>">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</main>

<?php
get_footer();
