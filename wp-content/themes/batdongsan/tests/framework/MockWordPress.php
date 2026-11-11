<?php

/**
 * In-Memory WordPress Environment & State Spy for E2E Testing.
 */

namespace BDS\Tests\Framework;

class AjaxExitException extends \Exception {
    protected array $response;
    protected int $statusCode;

    public function __construct(array $response, int $statusCode = 200) {
        $this->response = $response;
        $this->statusCode = $statusCode;
        parent::__construct("AJAX Exit: " . json_encode($response), $statusCode);
    }

    public function getResponse(): array {
        return $this->response;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }
}

class RedirectExitException extends \Exception {
    protected string $location;
    protected int $status;

    public function __construct(string $location, int $status = 302) {
        $this->location = $location;
        $this->status = $status;
        parent::__construct("Redirect to $location ($status)", $status);
    }

    public function getLocation(): string {
        return $this->location;
    }

    public function getStatus(): int {
        return $this->status;
    }
}

class MockWordPress {
    public static array $posts = [];
    public static array $postmeta = [];
    public static array $taxonomies = [];
    public static array $terms = [];
    public static array $object_terms = [];
    public static array $transients = [];
    public static array $actions = [];
    public static array $filters = [];
    public static array $persisted_actions = [];
    public static array $persisted_filters = [];
    public static array $rewrite_rules = [];
    public static array $dispatched_emails = [];
    public static array $fired_actions = [];
    public static ?array $last_redirect = null;
    public static ?array $last_ajax_response = null;
    public static int $last_status_code = 200;
    public static int $next_post_id = 1000;
    public static int $next_term_id = 100;
    public static array $current_user_caps = ['manage_options'];

    public static function reset(): void {
        self::$posts = [];
        self::$postmeta = [];
        self::$taxonomies = [];
        self::$terms = [];
        self::$object_terms = [];
        self::$transients = [];
        self::$actions = self::$persisted_actions;
        self::$filters = self::$persisted_filters;
        self::$rewrite_rules = [];
        self::$dispatched_emails = [];
        self::$fired_actions = [];
        self::$last_redirect = null;
        self::$last_ajax_response = null;
        self::$last_status_code = 200;
        self::$next_post_id = 1000;
        self::$next_term_id = 100;
        self::$current_user_caps = ['manage_options'];

        // Register default terms and taxonomies for BatDongSan
        self::registerDefaultTaxonomies();
    }

    protected static function registerDefaultTaxonomies(): void {
        self::$taxonomies['hinh-thuc-bds'] = ['object_type' => ['bat-dong-san']];
        self::$taxonomies['loai_hinh_bat_dong_san'] = ['object_type' => ['bat-dong-san']];
        self::$taxonomies['khu-vuc'] = ['object_type' => ['bat-dong-san', 'du-an']];
        self::$taxonomies['khu-vuc-du-an'] = ['object_type' => ['du-an']];
        self::$taxonomies['loai_hinh_du_an'] = ['object_type' => ['du-an']];

        self::insertTerm('Sơ cấp', 'hinh-thuc-bds', 'so-cap', 31);
        self::insertTerm('Chuyển nhượng', 'hinh-thuc-bds', 'chuyen-nhuong', 29);
        self::insertTerm('Cho thuê', 'hinh-thuc-bds', 'cho-thue', 30);

        self::insertTerm('Căn hộ chung cư', 'loai_hinh_bat_dong_san', 'can-ho-chung-cu', 33);
        self::insertTerm('Biệt thự liền kề', 'loai_hinh_bat_dong_san', 'biet-thu-lien-ke', 34);

        self::insertTerm('Cầu Giấy', 'khu-vuc', 'cau-giay', 50);
        self::insertTerm('Nam Từ Liêm', 'khu-vuc', 'nam-tu-liem', 51);
        self::insertTerm('Bắc Từ Liêm', 'khu-vuc', 'bac-tu-liem', 52);
    }

    public static function insertPost(array $args): int {
        $id = $args['ID'] ?? self::$next_post_id++;
        $slug = $args['post_name'] ?? sanitize_title($args['post_title'] ?? "post-$id");
        $type = $args['post_type'] ?? 'post';
        $status = $args['post_status'] ?? 'publish';
        $title = $args['post_title'] ?? '';
        $content = $args['post_content'] ?? '';
        $date = $args['post_date'] ?? date('Y-m-d H:i:s');

        $postObj = (object) [
            'ID' => (int) $id,
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => $type,
            'post_status' => $status,
            'post_date' => $date,
            'post_modified' => $date,
        ];

        self::$posts[$id] = $postObj;

        if (!empty($args['meta_input']) && is_array($args['meta_input'])) {
            foreach ($args['meta_input'] as $k => $v) {
                self::updatePostMeta($id, $k, $v);
            }
        }

        if (!empty($args['tax_input']) && is_array($args['tax_input'])) {
            foreach ($args['tax_input'] as $tax => $terms) {
                self::setObjectTerms($id, (array) $terms, $tax);
            }
        }

        return $id;
    }

    public static function getPost(int $id): ?object {
        return self::$posts[$id] ?? null;
    }

    public static function deletePost(int $id, bool $forceDelete = false): ?object {
        if (!isset(self::$posts[$id])) {
            return null;
        }
        $post = self::$posts[$id];
        unset(self::$posts[$id]);
        unset(self::$postmeta[$id]);
        unset(self::$object_terms[$id]);
        self::doAction('delete_post', $id, $post);
        return $post;
    }

    public static function updatePostMeta(int $postId, string $key, mixed $value): bool {
        if (!isset(self::$postmeta[$postId])) {
            self::$postmeta[$postId] = [];
        }
        self::$postmeta[$postId][$key] = is_array($value) ? $value : [$value];
        return true;
    }

    public static function getPostMeta(int $postId, string $key = '', bool $single = false): mixed {
        if (!isset(self::$postmeta[$postId])) {
            return $single ? '' : [];
        }
        if ($key === '') {
            return self::$postmeta[$postId];
        }
        if (!isset(self::$postmeta[$postId][$key])) {
            return $single ? '' : [];
        }
        $val = self::$postmeta[$postId][$key];
        return $single ? (is_array($val) ? reset($val) : $val) : $val;
    }

    public static function insertTerm(string $name, string $taxonomy, string $slug = '', ?int $termId = null): object {
        $id = $termId ?? self::$next_term_id++;
        $slug = $slug ?: sanitize_title($name);
        $term = (object) [
            'term_id' => $id,
            'name' => $name,
            'slug' => $slug,
            'taxonomy' => $taxonomy,
        ];
        self::$terms[$id] = $term;
        return $term;
    }

    public static function setObjectTerms(int $postId, array $terms, string $taxonomy): array {
        if (!isset(self::$object_terms[$postId])) {
            self::$object_terms[$postId] = [];
        }
        $termIds = [];
        foreach ($terms as $t) {
            if (is_numeric($t)) {
                $termIds[] = (int) $t;
            } elseif (is_string($t)) {
                // Find or insert
                $found = false;
                foreach (self::$terms as $term) {
                    if ($term->taxonomy === $taxonomy && ($term->slug === $t || $term->name === $t)) {
                        $termIds[] = $term->term_id;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newTerm = self::insertTerm($t, $taxonomy);
                    $termIds[] = $newTerm->term_id;
                }
            }
        }
        self::$object_terms[$postId][$taxonomy] = array_unique($termIds);
        return $termIds;
    }

    public static function getObjectTerms(int $postId, string $taxonomy): array {
        $ids = self::$object_terms[$postId][$taxonomy] ?? [];
        $result = [];
        foreach ($ids as $id) {
            if (isset(self::$terms[$id])) {
                $result[] = self::$terms[$id];
            }
        }
        return $result;
    }

    public static function setTransient(string $key, mixed $value, int $expiration = 0): bool {
        self::$transients[$key] = [
            'value' => $value,
            'expires' => $expiration > 0 ? time() + $expiration : 0,
        ];
        return true;
    }

    public static function getTransient(string $key): mixed {
        if (!isset(self::$transients[$key])) {
            return false;
        }
        $item = self::$transients[$key];
        if ($item['expires'] > 0 && $item['expires'] < time()) {
            unset(self::$transients[$key]);
            return false;
        }
        return $item['value'];
    }

    public static function deleteTransient(string $key): bool {
        if (isset(self::$transients[$key])) {
            unset(self::$transients[$key]);
            return true;
        }
        return false;
    }

    public static function addAction(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        self::$actions[$tag][$priority][] = ['callback' => $callback, 'accepted_args' => $accepted_args];
        self::$persisted_actions[$tag][$priority][] = ['callback' => $callback, 'accepted_args' => $accepted_args];
    }

    public static function doAction(string $tag, ...$args): void {
        self::$fired_actions[] = ['tag' => $tag, 'args' => $args];
        if (empty(self::$actions[$tag])) {
            return;
        }
        ksort(self::$actions[$tag]);
        foreach (self::$actions[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $item) {
                call_user_func_array($item['callback'], array_slice($args, 0, $item['accepted_args']));
            }
        }
    }

    public static function addFilter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        self::$filters[$tag][$priority][] = ['callback' => $callback, 'accepted_args' => $accepted_args];
        self::$persisted_filters[$tag][$priority][] = ['callback' => $callback, 'accepted_args' => $accepted_args];
    }

    public static function applyFilters(string $tag, mixed $value, ...$args): mixed {
        if (empty(self::$filters[$tag])) {
            return $value;
        }
        ksort(self::$filters[$tag]);
        $val = $value;
        foreach (self::$filters[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $item) {
                $params = array_merge([$val], array_slice($args, 0, $item['accepted_args'] - 1));
                $val = call_user_func_array($item['callback'], $params);
            }
        }
        return $val;
    }

    public static function createNonce(string|int $action = -1): string {
        return substr(md5('bds_salt_' . $action), 0, 10);
    }

    public static function verifyNonce(string $nonce, string|int $action = -1): bool {
        return $nonce === self::createNonce($action);
    }

    public static function sendJson(array $response, int $statusCode = 200): void {
        self::$last_ajax_response = $response;
        self::$last_status_code = $statusCode;
        throw new AjaxExitException($response, $statusCode);
    }

    public static function simulateAjax(string $action, array $postData = []): array {
        $_POST = array_merge(['action' => $action], $postData);
        $_REQUEST = array_merge($_REQUEST, $_POST);
        self::$last_ajax_response = null;
        self::$last_status_code = 200;

        try {
            self::doAction("wp_ajax_{$action}");
            self::doAction("wp_ajax_nopriv_{$action}");
            return [
                'status_code' => self::$last_status_code,
                'response' => self::$last_ajax_response,
                'clean_exit' => false,
            ];
        } catch (AjaxExitException $e) {
            return [
                'status_code' => $e->getStatusCode(),
                'response' => $e->getResponse(),
                'clean_exit' => true,
            ];
        }
    }
}
