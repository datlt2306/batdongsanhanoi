<?php

/**
 * Bootstrap file for BatDongSan E2E Test Suite.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
if (!defined('ARRAY_N')) define('ARRAY_N', 'ARRAY_N');
if (!defined('OBJECT')) define('OBJECT', 'OBJECT');

if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS')) define('HOUR_IN_SECONDS', 3600);
if (!defined('DAY_IN_SECONDS')) define('DAY_IN_SECONDS', 86400);
if (!defined('YEAR_IN_SECONDS')) define('YEAR_IN_SECONDS', 31536000);

require_once __DIR__ . '/framework/TestCase.php';
require_once __DIR__ . '/framework/MockWordPress.php';
require_once __DIR__ . '/framework/OutputRenderer.php';
require_once __DIR__ . '/framework/TestSuite.php';

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

// Global WP_Query Mock
if (!class_exists('WP_Query')) {
    class WP_Query {
        public array $query_vars = [];
        public array $posts = [];
        public int $post_count = 0;
        public int $found_posts = 0;
        public int $max_num_pages = 0;
        protected int $current_post = -1;

        public function __construct(array $args = []) {
            $this->init($args);
        }

        public function init(array $args = []): void {
            $this->query_vars = $args;
            $postType = $args['post_type'] ?? 'post';
            $postTypes = is_array($postType) ? $postType : [$postType];
            $postsPerPage = $args['posts_per_page'] ?? 10;
            $paged = $args['paged'] ?? 1;

            $matched = [];
            foreach (MockWordPress::$posts as $post) {
                if (!in_array($post->post_type, $postTypes, true) && !in_array('any', $postTypes, true)) {
                    continue;
                }
                if (!empty($args['post_status']) && $args['post_status'] !== 'any' && $post->post_status !== $args['post_status']) {
                    continue;
                }
                if (!empty($args['post__in']) && !in_array($post->ID, $args['post__in'], true)) {
                    continue;
                }
                if (!empty($args['post__not_in']) && in_array($post->ID, $args['post__not_in'], true)) {
                    continue;
                }
                $matched[] = $post;
            }

            $this->found_posts = count($matched);

            if ($postsPerPage === -1) {
                $this->posts = !empty($args['fields']) && $args['fields'] === 'ids' ? array_column($matched, 'ID') : $matched;
                $this->max_num_pages = 1;
            } else {
                $offset = ($paged - 1) * $postsPerPage;
                $sliced = array_slice($matched, $offset, $postsPerPage);
                $this->posts = !empty($args['fields']) && $args['fields'] === 'ids' ? array_column($sliced, 'ID') : $sliced;
                $this->max_num_pages = (int) ceil($this->found_posts / $postsPerPage);
            }

            $this->post_count = count($this->posts);
            $this->current_post = -1;
        }

        public function have_posts(): bool {
            return $this->current_post + 1 < $this->post_count;
        }

        public function the_post(): object {
            $this->current_post++;
            $post = $this->posts[$this->current_post];
            if (is_numeric($post)) {
                $post = MockWordPress::getPost((int) $post);
            }
            $GLOBALS['post'] = $post;
            return $post;
        }

        public function rewind_posts(): void {
            $this->current_post = -1;
        }
    }
}

// Global WP_Post Mock
if (!class_exists('WP_Post')) {
    class WP_Post {
        public int $ID = 0;
        public string $post_title = '';
        public string $post_name = '';
        public string $post_content = '';
        public string $post_type = 'post';
        public string $post_status = 'publish';
        public string $post_date = '';
    }
}

// Global WP_Error Mock
if (!class_exists('WP_Error')) {
    class WP_Error {
        protected array $errors = [];

        public function __construct(string $code = '', string $message = '', mixed $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
            }
        }

        public function get_error_message(string $code = ''): string {
            if (empty($code)) {
                $code = array_key_first($this->errors);
            }
            return $this->errors[$code][0] ?? '';
        }

        public function get_error_code(): string {
            return array_key_first($this->errors) ?? '';
        }
    }
}

// Global $wpdb Mock
if (!class_exists('MockWPDB')) {
    class MockWPDB {
        public string $prefix = 'wp_';
        public string $posts = 'wp_posts';
        public string $postmeta = 'wp_postmeta';
        public string $terms = 'wp_terms';
        public string $term_taxonomy = 'wp_term_taxonomy';
        public string $term_relationships = 'wp_term_relationships';
        public array $queries = [];

        public function prepare(string $query, ...$args): string {
            if (count($args) === 1 && is_array($args[0])) {
                $args = $args[0];
            }
            $escaped = array_map(function ($arg) {
                return "'" . addslashes((string) $arg) . "'";
            }, $args);
            $queryFormatted = str_replace(['%s', '%d', '%f'], '%s', $query);
            return vsprintf($queryFormatted, $escaped);
        }

        public function get_results(string $query, string $output = OBJECT): array {
            $this->queries[] = $query;
            return [];
        }

        public function get_col(string $query, int $col_offset = 0): array {
            $this->queries[] = $query;
            return [];
        }

        public function get_var(string $query, int $x = 0, int $y = 0): mixed {
            $this->queries[] = $query;
            return null;
        }

        public function query(string $query): bool|int {
            $this->queries[] = $query;
            return 1;
        }
    }
}

$GLOBALS['wpdb'] = new MockWPDB();

// Mock standard WordPress functions
if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        MockWordPress::addAction($tag, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $tag, ...$args): void {
        MockWordPress::doAction($tag, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        MockWordPress::addFilter($tag, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, mixed $value, ...$args): mixed {
        return MockWordPress::applyFilters($tag, $value, ...$args);
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $tag, callable $callback, int $priority = 10): bool {
        return true;
    }
}

if (!function_exists('remove_action')) {
    function remove_action(string $tag, callable $callback, int $priority = 10): bool {
        return true;
    }
}

if (!function_exists('has_action')) {
    function has_action(string $tag, callable|bool $callback = false): bool {
        return !empty(MockWordPress::$actions[$tag]);
    }
}

if (!function_exists('has_filter')) {
    function has_filter(string $tag, callable|bool $callback = false): bool {
        return !empty(MockWordPress::$filters[$tag]);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string|int $action = -1): string {
        return MockWordPress::createNonce((string) $action);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string|int $action = -1): bool {
        return MockWordPress::verifyNonce($nonce, (string) $action);
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer(string|int $action = -1, string $query_arg = 'nonce', bool $die = true): bool {
        $nonce = $_REQUEST[$query_arg] ?? '';
        $valid = MockWordPress::verifyNonce((string) $nonce, (string) $action);
        if (!$valid && $die) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        return $valid;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success(mixed $data = null, int $status_code = 200): void {
        MockWordPress::sendJson(['success' => true, 'data' => $data], $status_code);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error(mixed $data = null, int $status_code = 400): void {
        MockWordPress::sendJson(['success' => false, 'data' => $data], $status_code);
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json(mixed $response, int $status_code = 200): void {
        MockWordPress::sendJson((array) $response, $status_code);
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = '', string $title = '', array|int $args = []): void {
        $code = is_int($args) ? $args : ($args['response'] ?? 500);
        MockWordPress::sendJson(['error' => $message], $code);
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool {
        return MockWordPress::setTransient($key, $value, $expiration);
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed {
        return MockWordPress::getTransient($key);
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool {
        return MockWordPress::deleteTransient($key);
    }
}

if (!function_exists('get_post')) {
    function get_post(int|object|null $post = null): ?object {
        if ($post === null || $post === 0) {
            return $GLOBALS['post'] ?? null;
        }
        $id = is_object($post) ? ($post->ID ?? 0) : (int) $post;
        return MockWordPress::getPost($id);
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type(int|object|null $post = null): string|false {
        $p = get_post($post);
        return $p ? ($p->post_type ?? 'post') : false;
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail(int|object|null $post = null): bool {
        return false;
    }
}

if (!function_exists('get_post_thumbnail_id')) {
    function get_post_thumbnail_id(int|object|null $post = null): int {
        return 0;
    }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url(int|object|null $post = null, string|array $size = 'post-thumbnail'): string|false {
        return '';
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n(string $format, int|bool $timestamp_with_offset = false, bool $gmt = false): string {
        return date($format, $timestamp_with_offset ?: time());
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date(string $format = '', int|object|null $post = null): string {
        $p = get_post($post);
        return $p ? ($p->post_date ?? date($format ?: 'Y-m-d')) : date($format ?: 'Y-m-d');
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID(): int {
        return $GLOBALS['post']->ID ?? 0;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post(array $postarr, bool $wp_error = false): int|WP_Error {
        return MockWordPress::insertPost($postarr);
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post(array|object $postarr, bool $wp_error = false): int|WP_Error {
        $arr = (array) $postarr;
        return MockWordPress::insertPost($arr);
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post(int $postId, bool $force = false): ?object {
        return MockWordPress::deletePost($postId, $force);
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key = '', bool $single = false): mixed {
        return MockWordPress::getPostMeta($postId, $key, $single);
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): bool {
        return MockWordPress::updatePostMeta($postId, $key, $value);
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $postId, string $key): bool {
        if (isset(MockWordPress::$postmeta[$postId][$key])) {
            unset(MockWordPress::$postmeta[$postId][$key]);
            return true;
        }
        return false;
    }
}

if (!function_exists('get_field')) {
    function get_field(string $key, mixed $postId = null): mixed {
        if ($postId === 'option' || $postId === 'options') {
            return MockWordPress::$postmeta[0][$key] ?? null;
        }
        $id = is_object($postId) ? ($postId->ID ?? 0) : (int) ($postId ?: ($GLOBALS['post']->ID ?? 0));
        return MockWordPress::getPostMeta($id, $key, true);
    }
}

if (!function_exists('update_field')) {
    function update_field(string $key, mixed $value, mixed $postId = null): bool {
        if ($postId === 'option' || $postId === 'options') {
            MockWordPress::$postmeta[0][$key] = $value;
            return true;
        }
        $id = is_object($postId) ? ($postId->ID ?? 0) : (int) ($postId ?: ($GLOBALS['post']->ID ?? 0));
        return MockWordPress::updatePostMeta($id, $key, $value);
    }
}

if (!function_exists('get_post_field')) {
    function get_post_field(string $field, int|object|null $post = null): string {
        $p = get_post($post);
        return $p ? (string) ($p->$field ?? '') : '';
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg(array|string $param1, string|false $param2 = false, string|false $url = false): string {
        if (is_array($param1)) {
            $queryArgs = $param1;
            $url = is_string($param2) ? $param2 : ($_SERVER['REQUEST_URI'] ?? '');
        } else {
            $queryArgs = [$param1 => $param2];
            $url = is_string($url) ? $url : ($_SERVER['REQUEST_URI'] ?? '');
        }
        $parts = explode('?', $url, 2);
        $base = $parts[0];
        $existing = [];
        if (!empty($parts[1])) {
            parse_str($parts[1], $existing);
        }
        $merged = array_merge($existing, $queryArgs);
        return $base . (!empty($merged) ? '?' . http_build_query($merged) : '');
    }
}

if (!function_exists('get_the_terms')) {
    function get_the_terms(int|object $post, string $taxonomy): array|false {
        $id = is_object($post) ? ($post->ID ?? 0) : (int) $post;
        $terms = MockWordPress::getObjectTerms($id, $taxonomy);
        return !empty($terms) ? $terms : false;
    }
}

if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms(int $postId, array|int|string $terms, string $taxonomy, bool $append = false): array {
        return MockWordPress::setObjectTerms($postId, (array) $terms, $taxonomy);
    }
}

if (!function_exists('wp_list_pluck')) {
    function wp_list_pluck(array $list, string|int $field): array {
        $result = [];
        foreach ($list as $key => $value) {
            if (is_object($value)) {
                $result[$key] = $value->$field ?? null;
            } elseif (is_array($value)) {
                $result[$key] = $value[$field] ?? null;
            }
        }
        return $result;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $string, bool $remove_breaks = false): string {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        return trim($string);
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $num_words = 55, string $more = '...'): string {
        $words = preg_split("/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > $num_words) {
            array_pop($words);
            $text = implode(' ', $words) . $more;
        } else {
            $text = implode(' ', $words);
        }
        return $text;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        $filtered = strip_tags($str);
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        return trim($filtered);
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title): string {
        $raw = strtolower(trim($title));
        $raw = preg_replace('/[^a-z0-9-]+/', '-', $raw);
        return trim($raw, '-');
    }
}

if (!function_exists('absint')) {
    function absint(mixed $maybeint): int {
        return abs((int) $maybeint);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed {
        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('is_email')) {
    function is_email(string $email): string|false {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail(string|array $to, string $subject, string $message, string|array $headers = '', array $attachments = []): bool {
        MockWordPress::$dispatched_emails[] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];
        return true;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'http://batdongsanhanoi.local' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string {
        return home_url('/wp-content/themes/batdongsan');
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory(): string {
        return dirname(__DIR__);
    }
}

if (!function_exists('wp_unique_id')) {
    function wp_unique_id(string $prefix = ''): string {
        static $id = 0;
        return $prefix . (++$id);
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(int|object $post = 0): string {
        $p = get_post($post);
        return $p ? home_url('/' . $p->post_type . '/' . $p->post_name) : '';
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title(int|object $post = 0): string {
        $p = get_post($post);
        return $p ? $p->post_title : '';
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect(string $location, int $status = 302): bool {
        MockWordPress::$last_redirect = ['location' => $location, 'status' => $status];
        throw new RedirectExitException($location, $status);
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location, int $status = 302): bool {
        return wp_redirect($location, $status);
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void {
        unset($GLOBALS['post']);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $cap): bool {
        return in_array($cap, MockWordPress::$current_user_caps, true);
    }
}

if (!function_exists('add_rewrite_rule')) {
    function add_rewrite_rule(string $regex, string|array $query, string $after = 'bottom'): void {
        MockWordPress::$rewrite_rules[] = ['regex' => $regex, 'query' => $query, 'after' => $after];
    }
}
