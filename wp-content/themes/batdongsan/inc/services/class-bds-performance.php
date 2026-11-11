<?php
/**
 * BDS_Performance – Comprehensive Performance & Core Web Vitals Optimization Engine
 *
 * Implements:
 * 1. WordPress Core Head Cleanup & Dequeue Bloat
 * 2. Dynamic LCP Image Preload with Responsive imagesrcset
 * 3. Smart Script Deferring & reCAPTCHA v3 async/defer exception
 * 4. Delayed Analytics & GTM Engine (saves ~143 KiB JS at initial parse)
 * 5. Level 3 Cloud Image Architecture (Zero-Crop VPS + R2/ImageKit Dynamic Rewrite + Media Library Proxy)
 * 6. Server Security & Performance HTTP Headers
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

class BDS_Performance {

    /**
     * Singleton instance
     */
    private static ?BDS_Performance $instance = null;

    /**
     * ImageKit or R2 CDN base URL
     */
    private string $cdn_url;

    public static function instance(): BDS_Performance {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->cdn_url = defined('BATDONGSAN_IMAGEKIT_URL') 
            ? untrailingslashit(BATDONGSAN_IMAGEKIT_URL)
            : (defined('BATDONGSAN_R2_CDN_URL') ? untrailingslashit(BATDONGSAN_R2_CDN_URL) : 'https://pub-df1b5df747f143958eeaaea51f7b6282.r2.dev');

        $this->init_head_cleanup();
        $this->init_script_loader_strategy();
        $this->init_analytics_delay();
        $this->init_lcp_preload();
        $this->init_speculation_rules();
        $this->init_navigation_feedback();
        $this->init_level3_cloud_images();
        $this->init_security_headers();
    }

    /* =========================================================================
     * 1. WORDPRESS CORE HEAD CLEANUP
     * ========================================================================= */

    private function init_head_cleanup(): void {
        // Disable emoji scripts & styles completely
        add_action('init', function () {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

            // Remove TinyMCE emoji plugin
            add_filter('tiny_mce_plugins', function ($plugins) {
                return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
            });

            // Remove s.w.org DNS prefetch
            add_filter('wp_resource_hints', function ($urls, $relation_type) {
                if ('dns-prefetch' === $relation_type) {
                    $urls = array_filter($urls, function ($url) {
                        return strpos($url, 's.w.org') === false;
                    });
                }
                return $urls;
            }, 10, 2);
        });

        // Remove redundant links in <head>
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');

        // Disable pingbacks
        add_filter('pings_open', '__return_false', 999);
        add_filter('xmlrpc_methods', function ($methods) {
            unset($methods['pingback.ping']);
            return $methods;
        });

        // Dequeue bloat styles on frontend
        add_action('wp_enqueue_scripts', function () {
            if (!is_user_logged_in()) {
                wp_deregister_style('dashicons');
            }

            // Dequeue Gutenberg block library styles (Tailwind theme handles UI)
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('global-styles');
            wp_dequeue_style('classic-theme-styles');
        }, 100);
    }

    /* =========================================================================
     * 2. DYNAMIC LCP IMAGE PRELOAD WITH RESPONSIVE IMAGESRCSET
     * ========================================================================= */

    private function init_lcp_preload(): void {
        add_action('wp_head', [$this, 'output_lcp_preload'], 1);
    }

    public function output_lcp_preload(): void {
        $lcp_image_id = 0;

        // Front-page: get first hero banner
        if (is_front_page()) {
            $banner_query = new WP_Query([
                'post_type' => 'banner',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'tax_query' => [
                    [
                        'taxonomy' => 'loai-banner',
                        'field' => 'slug',
                        'terms' => 'trang-chu',
                    ],
                ],
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            ]);

            if ($banner_query->have_posts()) {
                $banner_query->the_post();
                $lcp_image_id = get_field('banner_image', get_the_ID()) ?: get_post_thumbnail_id();
                wp_reset_postdata();
            }
        } elseif (is_singular()) {
            $lcp_image_id = get_post_thumbnail_id();
        }

        if (!$lcp_image_id) {
            return;
        }

        $target_size = is_front_page() ? 'bds-hero' : 'full';
        $hero_src = wp_get_attachment_image_url($lcp_image_id, $target_size);
        if (!$hero_src) {
            $hero_src = wp_get_attachment_image_url($lcp_image_id, 'full');
        }
        if (!$hero_src) {
            return;
        }

        // Check if WebP sibling exists on disk
        $upload_dir = wp_get_upload_dir();
        $relative_path = str_replace($upload_dir['baseurl'], '', $hero_src);
        $file_path = $upload_dir['basedir'] . $relative_path;
        $webp_file = preg_replace('/\.(png|jpe?g)$/i', '.webp', $file_path);

        if (file_exists($webp_file)) {
            $webp_url = preg_replace('/\.(png|jpe?g)$/i', '.webp', $hero_src);
            echo sprintf(
                '<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high">' . "\n",
                esc_url($this->rewrite_image_url($webp_url))
            );
            return;
        }

        // Fallback: standard preload with responsive srcset
        $full_src = wp_get_attachment_image_url($lcp_image_id, 'full') ?: $hero_src;
        $full_src = $this->rewrite_image_url($full_src);

        $srcset_parts = [];
        $sizes_to_check = [
            768 => 'medium_large',
            1400 => 'bds-hero',
            1920 => 'full'
        ];

        foreach ($sizes_to_check as $w => $size_name) {
            $src = wp_get_attachment_image_url($lcp_image_id, $size_name);
            if ($src) {
                $srcset_parts[] = esc_url($this->rewrite_image_url($src)) . " {$w}w";
            }
        }

        $imagesrcset_attr = !empty($srcset_parts) ? ' imagesrcset="' . implode(', ', array_unique($srcset_parts)) . '"' : '';
        $imagesizes_attr = ' imagesizes="(max-width: 768px) 100vw, (max-width: 1440px) 1335px, 1920px"';

        $file_type = function_exists('wp_check_filetype') ? wp_check_filetype($full_src) : ['type' => 'image/jpeg'];
        $mime_type = $file_type['type'] ?: 'image/jpeg';

        echo sprintf(
            '<link rel="preload" as="image" href="%s"%s%s type="%s" fetchpriority="high">' . "\n",
            esc_url($full_src),
            $imagesrcset_attr,
            $imagesizes_attr,
            esc_attr($mime_type)
        );
    }

    /**
     * Speculation Rules API - Instant internal page navigations with prerender
     */
    private function init_speculation_rules(): void {
        add_action('wp_head', function () {
            if (is_admin()) {
                return;
            }
            ?>
<script type="speculationrules">
{
  "prerender": [
    {
      "where": {
        "and": [
          { "href_matches": "/*" },
          { "not": { "href_matches": "/wp-admin/*" } },
          { "not": { "href_matches": "/wp-login.php*" } },
          { "not": { "href_matches": "/*\\?*s=*" } }
        ]
      },
      "eagerness": "conservative"
    }
  ]
}
</script>
            <?php
        }, 3);
    }

    /**
     * Instant tactile click feedback (YouTube/Apple style top progress bar)
     */
    private function init_navigation_feedback(): void {
        add_action('wp_body_open', function () {
            if (is_admin()) {
                return;
            }
            ?>
<div id="bds-nav-progress" aria-hidden="true" style="position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg,#b20000 0%,#e53935 60%,#ff8a80 100%);z-index:9999999;pointer-events:none;opacity:0;transition:width 0.25s cubic-bezier(0.1,0.05,0.1,1),opacity 0.2s ease;box-shadow:0 0 10px rgba(178,0,0,0.6);"></div>
<script>
(function(){
    var bar = document.getElementById('bds-nav-progress');
    if (!bar) return;
    var timer = null;
    function start() {
        if (!bar) return;
        clearTimeout(timer);
        bar.style.transition = 'width 0.25s cubic-bezier(0.1,0.05,0.1,1), opacity 0.15s ease';
        bar.style.opacity = '1';
        bar.style.width = '35%';
        timer = setTimeout(function(){
            bar.style.width = '70%';
            timer = setTimeout(function(){
                bar.style.width = '88%';
            }, 300);
        }, 120);
    }
    function done() {
        if (!bar) return;
        clearTimeout(timer);
        bar.style.width = '100%';
        timer = setTimeout(function(){
            bar.style.opacity = '0';
            setTimeout(function(){
                bar.style.width = '0%';
            }, 200);
        }, 150);
    }
    document.addEventListener('click', function(e) {
        var a = e.target.closest('a');
        if (!a || !a.href) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target && a.target !== '_self') return;
        if (a.hasAttribute('download')) return;
        var url = a.href;
        if (url.indexOf(location.origin) !== 0) return;
        if (url.indexOf('#') !== -1 && url.split('#')[0] === location.href.split('#')[0]) return;
        if (url.indexOf('wp-admin') !== -1 || url.indexOf('wp-login') !== -1) return;
        if (url.match(/\.(pdf|zip|rar|docx?|xlsx?|jpg|jpeg|png|webp|svg)$/i)) return;
        start();
    }, { capture: true, passive: true });
    window.addEventListener('pageshow', function() {
        done();
    });
    window.addEventListener('pagehide', function() {
        if (bar && bar.style.opacity === '1') {
            bar.style.width = '100%';
        }
    });
})();
</script>
            <?php
        }, 1);
    }

    /* =========================================================================
     * 3. SMART SCRIPT LOADING & RECAPTCHA V3 EXCEPTION
     * ========================================================================= */

    private function init_script_loader_strategy(): void {
        add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
            if (is_admin()) {
                return $tag;
            }

            // Google reCAPTCHA v3 exception: must have BOTH async & defer, fetchpriority="low"
            if (strpos($src, 'recaptcha') !== false || strpos($handle, 'recaptcha') !== false) {
                if (strpos($tag, 'async') === false) {
                    $tag = str_replace(' src=', ' async src=', $tag);
                }
                if (strpos($tag, 'defer') === false) {
                    $tag = str_replace(' src=', ' defer src=', $tag);
                }
                if (strpos($tag, 'fetchpriority') === false) {
                    $tag = str_replace(' src=', ' fetchpriority="low" src=', $tag);
                }
                return $tag;
            }

            // Exclude inline or already modified scripts
            if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
                return $tag;
            }

            // Defer client-side scripts to avoid blocking INP
            $non_deferred = ['jquery-core']; // Keep core jQuery synchronous if strictly required by inline scripts
            if (!in_array($handle, $non_deferred, true)) {
                $tag = str_replace(' src=', ' defer fetchpriority="low" src=', $tag);
            }

            return $tag;
        }, 10, 3);
    }

    /* =========================================================================
     * 4. DELAYED ANALYTICS & TAG MANAGER (Saves ~143 KiB JS)
     * ========================================================================= */

    private function init_analytics_delay(): void {
        // Block SiteKit / GA4 synchronous tags
        add_filter('googlesitekit_tagmanager_tag_blocked', '__return_true');
        add_filter('googlesitekit_analytics-4_tag_blocked', '__return_true');

        // Output delayed loader in footer
        add_action('wp_footer', function () {
            ?>
            <script>
            (function() {
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                window.gtag = gtag;
                gtag('js', new Date());
                gtag('config', 'G-WR0KENN040');

                var gtmLoaded = false;
                function loadGTM() {
                    if (gtmLoaded) return;
                    gtmLoaded = true;
                    var s = document.createElement('script');
                    s.async = true;
                    s.fetchPriority = 'low';
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=G-WR0KENN040';
                    document.head.appendChild(s);
                }

                // Load on first user gesture or after idle delay
                ['scroll', 'touchstart', 'mousemove', 'keydown', 'click'].forEach(function(evt) {
                    window.addEventListener(evt, loadGTM, { once: true, passive: true });
                });

                if ('requestIdleCallback' in window) {
                    requestIdleCallback(function() { setTimeout(loadGTM, 3500); });
                } else {
                    setTimeout(loadGTM, 3500);
                }
            })();
            </script>
            <?php
        }, 99);
    }

    /* =========================================================================
     * 5. LEVEL 3 CLOUD IMAGE ARCHITECTURE (ZERO-CROP + DYNAMIC THUMBNAILS)
     * ========================================================================= */

    private function init_level3_cloud_images(): void {
        $cdn_enabled = (defined('BATDONGSAN_ENABLE_R2_CDN') && BATDONGSAN_ENABLE_R2_CDN)
            || (defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL));

        // Chỉ kích hoạt Zero-Crop và Dynamic URL Rewrite khi có Cloud CDN Resizer (ImageKit/R2) đang hoạt động.
        // Tránh tình trạng local/origin Nginx phải serve toàn bộ ảnh gốc kích thước khổng lồ vì không có worker xử lý ?tr=w-xxx.
        if (!$cdn_enabled) {
            return;
        }

        // 1. Prevent WordPress from cropping intermediate thumbnail files on VPS disk
        add_filter('intermediate_image_sizes_advanced', '__return_empty_array', 999);
        add_filter('intermediate_image_sizes', '__return_empty_array', 999);

        // 2. Cap upload threshold at 1920px (prevents 4K/8K bloat)
        add_filter('big_image_size_threshold', fn() => 1920);

        // 3. Media Library Proxy in WP-Admin
        // Ensures WP Admin displays sharp 150x150 thumbnails even if only 1 original file exists
        add_filter('image_downsize', [$this, 'proxy_image_downsize'], 10, 3);
        add_filter('wp_prepare_attachment_for_js', [$this, 'proxy_prepare_attachment_for_js'], 10, 3);

        // 4. Output Buffer for Dynamic Thumbnail Rewrite & CDN Routing
        add_action('template_redirect', function () {
            if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
                return;
            }
            ob_start([$this, 'rewrite_html_images']);
        }, 2);
    }

    /**
     * Proxy image_downsize: on-the-fly thumbnail URL generator
     */
    public function proxy_image_downsize($downsize, int $id, $size) {
        if (!wp_attachment_is_image($id)) {
            return false;
        }

        $file_url = wp_get_attachment_url($id);
        if (!$file_url) {
            return false;
        }

        // Determine target dimensions
        $width = 0;
        $height = 0;
        if (is_array($size)) {
            $width = (int) ($size[0] ?? 0);
            $height = (int) ($size[1] ?? 0);
        } elseif (is_string($size)) {
            global $_wp_additional_image_sizes;
            if (isset($_wp_additional_image_sizes[$size])) {
                $width = (int) $_wp_additional_image_sizes[$size]['width'];
                $height = (int) $_wp_additional_image_sizes[$size]['height'];
            } else {
                $width = (int) get_option("{$size}_size_w", 0);
                $height = (int) get_option("{$size}_size_h", 0);
            }
        }

        // If specific dimensions requested, append transformation query
        if ($width > 0 || $height > 0) {
            $transform = [];
            if ($width > 0) $transform[] = "w-{$width}";
            if ($height > 0) $transform[] = "h-{$height}";
            
            $file_url = $this->rewrite_image_url($file_url);
            $separator = (strpos($file_url, '?') !== false) ? '&' : '?';
            $file_url .= $separator . 'tr=' . implode(',', $transform);

            return [$file_url, $width, $height, true];
        }

        return [$this->rewrite_image_url($file_url), $width, $height, false];
    }

    /**
     * Proxy wp_prepare_attachment_for_js for WP-Admin Media Library
     */
    public function proxy_prepare_attachment_for_js(array $response, $attachment, $meta): array {
        if ($response['type'] === 'image' && !empty($response['url'])) {
            $original_url = $response['url'];
            $separator = (strpos($original_url, '?') !== false) ? '&' : '?';

            // Ensure thumbnail size is available for admin grid
            if (empty($response['sizes']['thumbnail']['url'])) {
                $response['sizes']['thumbnail'] = [
                    'url' => $original_url . $separator . 'tr=w-150,h-150,cm-pad_resize',
                    'width' => 150,
                    'height' => 150,
                    'orientation' => 'landscape',
                ];
            }
            if (empty($response['sizes']['medium']['url'])) {
                $response['sizes']['medium'] = [
                    'url' => $original_url . $separator . 'tr=w-300,h-300',
                    'width' => 300,
                    'height' => 300,
                    'orientation' => 'landscape',
                ];
            }
        }
        return $response;
    }

    /**
     * Rewrite URL to CDN domain and transform crop filenames
     */
    public function rewrite_image_url(string $url): string {
        if (empty($url)) {
            return $url;
        }

        $cdn_enabled = (defined('BATDONGSAN_ENABLE_R2_CDN') && BATDONGSAN_ENABLE_R2_CDN)
            || (defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL));

        if (!$cdn_enabled) {
            return $url;
        }

        // 1. Transform WordPress crop filenames: image-150x150.jpg -> image.jpg?tr=w-150,h-150
        $url = preg_replace('/-(\d+)x(\d+)\.(jpe?g|png|webp|gif)/i', '.$3?tr=w-$1,h-$2', $url);

        // 2. Rewrite domain to CDN if enabled
        if ($cdn_enabled) {
            $origins = [
                untrailingslashit(site_url()),
                untrailingslashit(home_url()),
                'https://batdongsanhanoi.net.vn',
            ];
            foreach ($origins as $origin) {
                if (strpos($url, $origin . '/wp-content/uploads') === 0) {
                    $url = str_replace($origin . '/wp-content/uploads', $this->cdn_url, $url);
                    break;
                }
            }
        }

        return $url;
    }

    /**
     * HTML Output buffer rewrite for images
     */
    public function rewrite_html_images(string $html): string {
        if (empty($html) || !is_string($html)) {
            return $html;
        }

        // Dynamic Thumbnail Rewrite: Matches image-150x150.jpg inside src/srcset and rewrites to image.jpg?tr=w-150,h-150
        $html = preg_replace_callback(
            '/(https?:\/\/[^"\'\s<>]+\/(?:wp-content\/uploads\/|\d{4}\/\d{2}\/)[^"\'\s<>]+)-(\d+)x(\d+)\.(jpe?g|png|webp|gif)/i',
            function ($m) {
                return $m[1] . '.' . $m[4] . '?tr=w-' . $m[2] . ',h-' . $m[3];
            },
            $html
        );

        return $html;
    }

    /* =========================================================================
     * 6. SERVER SECURITY & PERFORMANCE HEADERS
     * ========================================================================= */

    private function init_security_headers(): void {
        add_action('send_headers', function () {
            if (headers_sent()) {
                return;
            }
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        });
    }
}

// Bootstrap Performance Engine
BDS_Performance::instance();
