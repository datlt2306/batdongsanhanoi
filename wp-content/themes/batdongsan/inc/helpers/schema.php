<?php
/**
 * Schema.org JSON-LD Structured Data Helpers
 *
 * Implements native Google-compliant & AI Bot-ready structured data for:
 * - RealEstateListing (single-bat-dong-san.php)
 * - Place / ApartmentComplex (single-du-an.php)
 * - FAQPage (single-du-an.php and pages with FAQ)
 * - BreadcrumbList (single properties, property archives, single projects, project archives)
 * - WebSite with SearchAction (front-page.php)
 * - Organization & RealEstateAgent with contactPoint, geo, knowsAbout (front-page.php)
 *
 * Injected cleanly into wp_head and integrated with Rank Math JSON-LD graph.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encode an array to safe JSON-LD string.
 *
 * Uses wp_json_encode() when available with JSON_HEX_TAG to prevent </script> injection.
 *
 * @param array $schema Schema data array
 * @return string Safe JSON string
 */
function batdongsan_encode_json_ld(array $schema): string {
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP;
    if (function_exists('wp_json_encode')) {
        return (string) wp_json_encode($schema, $flags);
    }
    return (string) json_encode($schema, $flags);
}

/**
 * Clean and decode text for Schema.org JSON-LD to prevent raw HTML entities.
 *
 * @param string $text Raw text with possible HTML or entities
 * @return string Decoded clean UTF-8 text
 */
function batdongsan_clean_schema_text(string $text): string {
    $clean = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($text) : strip_tags($text);
    return trim(html_entity_decode($clean, ENT_QUOTES, 'UTF-8'));
}

/**
 * Output a single Schema.org JSON-LD script block.
 *
 * @param array $schema Schema data array
 * @return void
 */
function batdongsan_output_schema_json_ld(array $schema): void {
    if (empty($schema)) {
        return;
    }

    $json = batdongsan_encode_json_ld($schema);
    if (!empty($json) && $json !== '{}' && $json !== '[]') {
        echo "<script type=\"application/ld+json\">\n" . $json . "\n</script>\n";
    }
}

/**
 * Generate RealEstateListing schema for single-bat-dong-san.php.
 *
 * Google-compliant Schema for RealEstateListing:
 * - Top-level: name, description, price, priceCurrency (VND), url, image, datePosted, numberOfRooms, floorSize, address
 * - Sub-entities: mainEntity (Apartment / SingleFamilyResidence / Accommodation), offers (Offer with VND price & InStock)
 *
 * @param int $post_id Post ID (defaults to current post)
 * @return array Schema data array or empty array if invalid
 */
function batdongsan_get_schema_real_estate_listing(int $post_id = 0): array {
    if ($post_id <= 0) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
    }
    if ($post_id <= 0) {
        return [];
    }

    $raw_title = function_exists('get_the_title') ? get_the_title($post_id) : '';
    $title = batdongsan_clean_schema_text($raw_title);
    if ($title === '') {
        $title = 'Bất động sản Hà Nội';
    }

    // 1. Description: excerpt -> content -> ACF thiet_ke -> fallback
    $description = '';
    if (function_exists('get_the_excerpt')) {
        $excerpt = get_the_excerpt($post_id);
        if (!empty($excerpt)) {
            $description = batdongsan_clean_schema_text($excerpt);
        }
    }
    if ($description === '' && function_exists('get_post_field')) {
        $content = get_post_field('post_content', $post_id);
        if (!empty($content)) {
            $clean_content = batdongsan_clean_schema_text($content);
            $description = function_exists('wp_trim_words') ? wp_trim_words($clean_content, 40, '...') : substr($clean_content, 0, 200);
        }
    }
    if ($description === '' && function_exists('get_field')) {
        $thiet_ke = get_field('thiet_ke', $post_id);
        if (!empty($thiet_ke) && is_string($thiet_ke)) {
            $clean_thiet_ke = batdongsan_clean_schema_text($thiet_ke);
            $description = function_exists('wp_trim_words') ? wp_trim_words($clean_thiet_ke, 40, '...') : substr($clean_thiet_ke, 0, 200);
        }
    }
    if ($description === '') {
        $description = $title . ' - Bất động sản cao cấp vị trí đắc địa tại Hà Nội.';
    }

    // 2. Canonical URL
    $url = function_exists('get_permalink') ? get_permalink($post_id) : '';
    $url = function_exists('esc_url') ? esc_url($url) : $url;

    // 3. Date Posted (ISO 8601)
    $ngay_dang = function_exists('get_field') ? get_field('ngay_dang', $post_id) : '';
    $date_posted = '';
    if (!empty($ngay_dang)) {
        $ts = strtotime($ngay_dang);
        $date_posted = $ts ? date('c', $ts) : date('c');
    } elseif (function_exists('get_the_date')) {
        $date_posted = get_the_date('c', $post_id);
    }
    if (!$date_posted) {
        $date_posted = date('c');
    }

    // 4. Image inventory (Thumbnail + Gallery)
    $images = [];
    if (function_exists('has_post_thumbnail') && has_post_thumbnail($post_id)) {
        $thumb_url = function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($post_id, 'full') : '';
        if (!empty($thumb_url)) {
            $images[] = function_exists('esc_url') ? esc_url($thumb_url) : $thumb_url;
        }
    }
    $gallery = function_exists('get_field') ? get_field('gallery_nha_dat', $post_id) : [];
    if (is_array($gallery)) {
        foreach ($gallery as $img) {
            $img_id = is_array($img) && isset($img['ID']) ? (int) $img['ID'] : (is_numeric($img) ? (int) $img : 0);
            if ($img_id > 0 && function_exists('wp_get_attachment_image_url')) {
                $img_url = wp_get_attachment_image_url($img_id, 'full');
                if ($img_url && !in_array($img_url, $images, true)) {
                    $images[] = function_exists('esc_url') ? esc_url($img_url) : $img_url;
                }
            } elseif (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                if (!in_array($img, $images, true)) {
                    $images[] = function_exists('esc_url') ? esc_url($img) : $img;
                }
            }
        }
    }

    // 5. Price & Currency (VND)
    $price_million = function_exists('batdongsan_get_total_price_million')
        ? batdongsan_get_total_price_million($post_id)
        : null;
    $price_vnd = 0.0;
    if ($price_million !== null && $price_million > 0) {
        $price_vnd = (float) round($price_million * 1000000);
    } else {
        $raw_kg = function_exists('get_field') ? get_field('khoang_gia', $post_id) : '';
        if (is_numeric($raw_kg) && (float) $raw_kg > 0) {
            $price_vnd = (float) $raw_kg;
        }
    }

    // 6. Number of Rooms & Bedrooms
    $so_phong_ngu = function_exists('get_field') ? get_field('so_phong_ngu', $post_id) : null;
    if (empty($so_phong_ngu) && function_exists('get_post_meta')) {
        $so_phong_ngu = get_post_meta($post_id, 'so_phong_ngu', true);
    }
    if (empty($so_phong_ngu) && preg_match('/(\d+)\s*PN/i', $title, $matches)) {
        $so_phong_ngu = $matches[1];
    }
    $number_of_rooms = !empty($so_phong_ngu) ? (int) $so_phong_ngu : 1;

    $so_phong_ve_sinh = function_exists('get_field') ? get_field('so_phong_ve_sinh', $post_id) : null;
    if (empty($so_phong_ve_sinh) && function_exists('get_post_meta')) {
        $so_phong_ve_sinh = get_post_meta($post_id, 'so_phong_ve_sinh', true);
    }
    $bathrooms = !empty($so_phong_ve_sinh) ? (int) $so_phong_ve_sinh : 0;

    // 7. Floor Size (m²)
    $area_number = function_exists('batdongsan_get_area_number')
        ? batdongsan_get_area_number($post_id)
        : null;
    if (!$area_number && function_exists('get_field')) {
        $dien_tich = get_field('dien_tich', $post_id);
        if (is_numeric($dien_tich) && (float) $dien_tich > 0) {
            $area_number = (float) $dien_tich;
        } elseif (is_string($dien_tich) && preg_match('/([\d\.,]+)/u', $dien_tich, $m)) {
            $area_number = (float) str_replace(',', '.', $m[1]);
        }
    }
    $floor_size_value = $area_number ?: 0;

    // 8. Address Structure (Locality, Region 'Hà Nội', Country 'VN')
    $dia_chi = function_exists('get_field') ? get_field('dia_chi', $post_id) : '';
    $street_address = is_string($dia_chi) ? (function_exists('sanitize_text_field') ? sanitize_text_field($dia_chi) : strip_tags($dia_chi)) : '';
    $address_locality = 'Hà Nội';

    $khu_vuc_terms = function_exists('get_the_terms') ? get_the_terms($post_id, 'khu-vuc') : false;
    if ($khu_vuc_terms && !is_wp_error($khu_vuc_terms) && !empty($khu_vuc_terms)) {
        $address_locality = $khu_vuc_terms[0]->name;
    } elseif ($street_address !== '') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $street_address))));
        $part_count = count($parts);
        if ($part_count >= 2) {
            $last_part = $parts[$part_count - 1];
            $second_last = $parts[$part_count - 2];
            if (mb_stripos($last_part, 'Hà Nội') !== false && !empty($second_last)) {
                $address_locality = $second_last;
            } else {
                $address_locality = $last_part;
            }
        }
    }

    // Determine Entity Type
    $entity_type = 'Accommodation';
    $loai_hinh_terms = function_exists('get_the_terms') ? get_the_terms($post_id, 'loai_hinh_bat_dong_san') : false;
    if ($loai_hinh_terms && !is_wp_error($loai_hinh_terms) && !empty($loai_hinh_terms)) {
        $slug = $loai_hinh_terms[0]->slug ?? '';
        if (str_contains($slug, 'chung-cu') || str_contains($slug, 'can-ho')) {
            $entity_type = 'Apartment';
        } elseif (str_contains($slug, 'biet-thu') || str_contains($slug, 'nha-pho') || str_contains($slug, 'nha-rieng') || str_contains($slug, 'nha-mat-pho')) {
            $entity_type = 'SingleFamilyResidence';
        }
    }

    $postal_address = [
        '@type'           => 'PostalAddress',
        'addressLocality' => $address_locality,
        'addressRegion'   => 'Hà Nội',
        'addressCountry'  => 'VN',
    ];
    if ($street_address !== '') {
        $postal_address['streetAddress'] = $street_address;
    }

    $floor_size_obj = [
        '@type'    => 'QuantitativeValue',
        'value'    => $floor_size_value,
        'unitCode' => 'MTK',
        'unitText' => 'm²',
    ];

    // Build mainEntity
    $main_entity = [
        '@type'         => $entity_type,
        'name'          => $title,
        'numberOfRooms' => $number_of_rooms,
        'floorSize'     => $floor_size_obj,
        'address'       => $postal_address,
    ];
    if ($number_of_rooms > 0) {
        $main_entity['numberOfBedrooms'] = $number_of_rooms;
    }
    if ($bathrooms > 0) {
        $main_entity['numberOfBathroomsTotal'] = $bathrooms;
    }

    // Geo coordinates
    $google_map = function_exists('get_field') ? get_field('google_map', $post_id) : null;
    if (is_array($google_map) && !empty($google_map['lat']) && !empty($google_map['lng'])) {
        $main_entity['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $google_map['lat'],
            'longitude' => (float) $google_map['lng'],
        ];
    }

    // Build schema
    $schema = [
        '@context'      => 'https://schema.org',
        '@type'         => 'RealEstateListing',
        '@id'           => $url . '#listing',
        'name'          => $title,
        'description'   => $description,
        'url'           => $url,
        'datePosted'    => $date_posted,
        'price'         => $price_vnd,
        'priceCurrency' => 'VND',
        'numberOfRooms' => $number_of_rooms,
        'floorSize'     => $floor_size_obj,
        'address'       => $postal_address,
        'mainEntity'    => $main_entity,
        'offers'        => [
            '@type'         => 'Offer',
            'price'         => $price_vnd,
            'priceCurrency' => 'VND',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $url,
        ],
    ];

    if (!empty($images)) {
        $schema['image'] = $images;
        $schema['mainEntity']['image'] = $images;
    }

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_real_estate_listing', $schema, $post_id);
    }
    return $schema;
}

/**
 * Generate Schema.org structured data for single du-an (Project).
 *
 * Emits compound Place / ApartmentComplex / RealEstateListing with:
 * - name, description, url, image, address, geo, amenityFeature, offers, provider
 *
 * @param int $post_id Post ID (defaults to current post)
 * @return array Schema data array or empty array if invalid
 */
function batdongsan_get_schema_du_an(int $post_id = 0): array {
    if ($post_id <= 0) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
    }
    if ($post_id <= 0) {
        return [];
    }

    $raw_title = function_exists('get_the_title') ? get_the_title($post_id) : '';
    $title = batdongsan_clean_schema_text($raw_title);
    if ($title === '') {
        $title = 'Dự án Bất động sản Hà Nội';
    }

    // Description
    $description = '';
    if (function_exists('get_the_excerpt')) {
        $excerpt = get_the_excerpt($post_id);
        if (!empty($excerpt)) {
            $description = batdongsan_clean_schema_text($excerpt);
        }
    }
    if ($description === '' && function_exists('get_post_field')) {
        $content = get_post_field('post_content', $post_id);
        if (!empty($content)) {
            $clean_content = batdongsan_clean_schema_text($content);
            $description = function_exists('wp_trim_words') ? wp_trim_words($clean_content, 40, '...') : substr($clean_content, 0, 200);
        }
    }
    if ($description === '') {
        $description = 'Dự án ' . $title . ' - Vị trí đắc địa, quy hoạch đồng bộ tại Hà Nội.';
    }

    $url = function_exists('get_permalink') ? get_permalink($post_id) : '';
    $url = function_exists('esc_url') ? esc_url($url) : $url;

    // Images
    $images = [];
    if (function_exists('has_post_thumbnail') && has_post_thumbnail($post_id)) {
        $thumb_url = function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($post_id, 'full') : '';
        if (!empty($thumb_url)) {
            $images[] = function_exists('esc_url') ? esc_url($thumb_url) : $thumb_url;
        }
    }
    $gallery = function_exists('get_field') ? get_field('gallery_du_an', $post_id) : [];
    if (is_array($gallery)) {
        foreach ($gallery as $img) {
            $img_id = is_array($img) && isset($img['ID']) ? (int) $img['ID'] : (is_numeric($img) ? (int) $img : 0);
            if ($img_id > 0 && function_exists('wp_get_attachment_image_url')) {
                $img_url = wp_get_attachment_image_url($img_id, 'full');
                if ($img_url && !in_array($img_url, $images, true)) {
                    $images[] = function_exists('esc_url') ? esc_url($img_url) : $img_url;
                }
            } elseif (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                if (!in_array($img, $images, true)) {
                    $images[] = function_exists('esc_url') ? esc_url($img) : $img;
                }
            }
        }
    }

    // Address
    $address_str = function_exists('get_field') ? get_field('address', $post_id) : '';
    $street_address = is_string($address_str) ? (function_exists('sanitize_text_field') ? sanitize_text_field($address_str) : strip_tags($address_str)) : '';
    $address_locality = 'Hà Nội';

    $khu_vuc_terms = function_exists('get_the_terms') ? (get_the_terms($post_id, 'khu-vuc-du-an') ?: get_the_terms($post_id, 'khu-vuc')) : false;
    if ($khu_vuc_terms && !is_wp_error($khu_vuc_terms) && !empty($khu_vuc_terms)) {
        $address_locality = $khu_vuc_terms[0]->name;
    } elseif ($street_address !== '') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $street_address))));
        $part_count = count($parts);
        if ($part_count >= 2) {
            $last_part = $parts[$part_count - 1];
            $second_last = $parts[$part_count - 2];
            if (mb_stripos($last_part, 'Hà Nội') !== false && !empty($second_last)) {
                $address_locality = $second_last;
            } else {
                $address_locality = $last_part;
            }
        }
    }

    $postal_address = [
        '@type'           => 'PostalAddress',
        'addressLocality' => $address_locality,
        'addressRegion'   => 'Hà Nội',
        'addressCountry'  => 'VN',
    ];
    if ($street_address !== '') {
        $postal_address['streetAddress'] = $street_address;
    }

    // Price
    $raw_price = function_exists('get_field') ? get_field('price', $post_id) : '';
    $price_vnd = 0.0;
    if (is_numeric($raw_price) && (float) $raw_price > 0) {
        $price_vnd = (float) $raw_price >= 1000 ? (float) $raw_price : (float) round((float) $raw_price * 1000000000);
    }

    // Geo
    $geo_data = [
        '@type'     => 'GeoCoordinates',
        'latitude'  => 21.028511,
        'longitude' => 105.804817,
    ];
    $google_map = function_exists('get_field') ? get_field('google_map', $post_id) : null;
    if (is_array($google_map) && !empty($google_map['lat']) && !empty($google_map['lng'])) {
        $geo_data = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $google_map['lat'],
            'longitude' => (float) $google_map['lng'],
        ];
    }

    // Amenities (ACF tien_ich)
    $amenity_features = [];
    $tien_ich_raw = function_exists('get_field') ? get_field('tien_ich', $post_id) : '';
    if (!empty($tien_ich_raw) && is_string($tien_ich_raw)) {
        $lines = array_filter(array_map('trim', explode("\n", $tien_ich_raw)));
        foreach ($lines as $line) {
            $clean_line = trim(preg_replace('/[\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}]/u', '', $line));
            if (!empty($clean_line)) {
                $amenity_features[] = [
                    '@type' => 'LocationFeatureSpecification',
                    'name'  => $clean_line,
                    'value' => true,
                ];
            }
        }
    }

    // Developer / Provider (ACF chu_dau_tu)
    $chu_dau_tu = function_exists('get_field') ? get_field('chu_dau_tu', $post_id) : '';
    $provider = null;
    if (!empty($chu_dau_tu) && is_string($chu_dau_tu)) {
        $provider = [
            '@type' => 'Organization',
            'name'  => sanitize_text_field($chu_dau_tu),
        ];
    }

    $schema = [
        '@context'         => 'https://schema.org',
        '@type'            => ['Place', 'ApartmentComplex', 'RealEstateListing'],
        '@id'              => $url . '#project',
        'name'             => $title,
        'description'      => $description,
        'url'              => $url,
        'address'          => $postal_address,
        'geo'              => $geo_data,
        'containedInPlace' => [
            '@type' => 'City',
            'name'  => 'Hà Nội',
        ],
    ];

    if (!empty($images)) {
        $schema['image'] = $images;
    }
    if (!empty($amenity_features)) {
        $schema['amenityFeature'] = array_slice($amenity_features, 0, 10);
    }
    if ($provider) {
        $schema['provider'] = $provider;
    }
    if ($price_vnd > 0) {
        $schema['offers'] = [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => 'VND',
            'lowPrice'      => $price_vnd,
            'availability'  => 'https://schema.org/InStock',
            'url'           => $url,
        ];
    }

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_du_an', $schema, $post_id);
    }
    return $schema;
}

/**
 * Generate Schema.org FAQPage structured data.
 *
 * Reads repeater 'cau_hoi_thuong_gap' or provides fallback project FAQs.
 *
 * @param int $post_id Post ID
 * @return array FAQPage schema array or empty array if none
 */
function batdongsan_get_schema_faq_page(int $post_id = 0): array {
    if ($post_id <= 0) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
    }
    if ($post_id <= 0) {
        return [];
    }

    $faq_items = function_exists('get_field') ? get_field('cau_hoi_thuong_gap', $post_id) : [];
    if (empty($faq_items) && function_exists('get_post_type') && get_post_type($post_id) === 'du-an') {
        $ban_giao = function_exists('get_field') ? get_field('ban_giao', $post_id) : '';
        $faq_items = [
            [
                'cau_hoi'     => 'Thời gian bàn giao dự án là khi nào?',
                'cau_tra_loi' => 'Dự án dự kiến bàn giao vào ' . ($ban_giao ?: 'năm 2026') . ', tùy theo tiến độ từng phân khu cụ thể.',
            ],
            [
                'cau_hoi'     => 'Ngân hàng hỗ trợ vay tối đa bao nhiêu phần trăm giá trị?',
                'cau_tra_loi' => 'Thông thường ngân hàng hỗ trợ vay tối đa tới 70% giá trị tài sản, thời hạn lên đến 20–25 năm tuỳ chính sách từng ngân hàng đối tác.',
            ],
            [
                'cau_hoi'     => 'Pháp lý dự án như thế nào?',
                'cau_tra_loi' => 'Dự án có đầy đủ quy hoạch 1/500, giấy phép xây dựng và hồ sơ pháp lý minh bạch theo quy định hiện hành.',
            ],
        ];
    }

    if (empty($faq_items) || !is_array($faq_items)) {
        return [];
    }

    $questions = [];
    foreach ($faq_items as $item) {
        $q = isset($item['cau_hoi']) ? batdongsan_clean_schema_text((string) $item['cau_hoi']) : '';
        $a = isset($item['cau_tra_loi']) ? batdongsan_clean_schema_text((string) $item['cau_tra_loi']) : '';
        if ($q !== '' && $a !== '') {
            $questions[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $a,
                ],
            ];
        }
    }

    if (empty($questions)) {
        return [];
    }

    $url = function_exists('get_permalink') ? get_permalink($post_id) : '';
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        '@id'        => ($url ? $url . '#faq' : ''),
        'mainEntity' => $questions,
    ];

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_faq_page', $schema, $post_id);
    }
    return $schema;
}

/**
 * Resolve current hierarchical breadcrumbs for the active request.
 *
 * @return array Array of items: [['label' => string, 'url' => string], ...]
 */
function batdongsan_resolve_current_breadcrumbs(): array {
    $crumbs = [];

    // Item 1: Always Home
    $crumbs[] = [
        'label' => 'Trang chủ',
        'url'   => function_exists('home_url') ? home_url('/') : '/',
    ];

    $is_front = (function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home());
    if ($is_front) {
        return $crumbs;
    }

    // 1. Single bat-dong-san
    if (function_exists('is_singular') && is_singular('bat-dong-san')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        $parts = function_exists('batdongsan_get_bat_dong_san_breadcrumb')
            ? batdongsan_get_bat_dong_san_breadcrumb($post_id)
            : [];
        if (!empty($parts)) {
            $crumbs = $parts;
        } else {
            $crumbs[] = [
                'label' => 'Bất động sản',
                'url'   => function_exists('home_url') ? home_url('/bat-dong-san/') : '/bat-dong-san/',
            ];
        }
        if ($post_id > 0) {
            $crumbs[] = [
                'label' => function_exists('get_the_title') ? get_the_title($post_id) : '',
                'url'   => function_exists('get_permalink') ? get_permalink($post_id) : '',
            ];
        }
        return $crumbs;
    }

    // 2. Single du-an
    if (function_exists('is_singular') && is_singular('du-an')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        $parts = function_exists('batdongsan_get_du_an_breadcrumb')
            ? batdongsan_get_du_an_breadcrumb($post_id)
            : [];
        foreach ($parts as $part) {
            $crumbs[] = $part;
        }
        if ($post_id > 0) {
            $crumbs[] = [
                'label' => function_exists('get_the_title') ? get_the_title($post_id) : '',
                'url'   => function_exists('get_permalink') ? get_permalink($post_id) : '',
            ];
        }
        return $crumbs;
    }

    // 3. Property Archive / Listing Page
    $is_bds_archive = (function_exists('is_post_type_archive') && is_post_type_archive('bat-dong-san'))
        || (function_exists('is_page') && is_page('bat-dong-san'))
        || (function_exists('is_page_template') && is_page_template('page-bat-dong-san.php'))
        || (function_exists('get_query_var') && (
            get_query_var('bds_ban_slug') ||
            get_query_var('bds_khu_vuc') ||
            get_query_var('bds_filter_slug') ||
            get_query_var('bds_type_slug')
        ));

    if ($is_bds_archive) {
        $crumbs[] = [
            'label' => 'Bất động sản',
            'url'   => function_exists('home_url') ? home_url('/bat-dong-san/') : '/bat-dong-san/',
        ];

        // Type
        $type_slug = function_exists('get_query_var') ? (get_query_var('bds_type_slug') ?: get_query_var('bds_ban_slug')) : '';
        if (!$type_slug && isset($_GET['type'])) {
            $type_slug = sanitize_text_field(wp_unslash($_GET['type']));
        }
        if ($type_slug && function_exists('get_term_by')) {
            $term = get_term_by('slug', $type_slug, 'loai_hinh_bat_dong_san');
            if ($term && !is_wp_error($term)) {
                $crumbs[] = [
                    'label' => $term->name,
                    'url'   => function_exists('home_url') ? home_url('/ban-' . $term->slug . '/') : '/ban-' . $term->slug . '/',
                ];
            }
        }

        // District
        $khu_vuc = function_exists('get_query_var') ? get_query_var('bds_khu_vuc') : '';
        if (!$khu_vuc && isset($_GET['khu_vuc'])) {
            $khu_vuc = sanitize_text_field(wp_unslash($_GET['khu_vuc']));
        }
        if ($khu_vuc) {
            $area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : [];
            $district_label = $area_options[$khu_vuc] ?? ucwords(str_replace('-', ' ', $khu_vuc));
            $crumbs[] = [
                'label' => $district_label,
                'url'   => function_exists('home_url') ? home_url('/bat-dong-san-quan-' . $khu_vuc . '/') : '/bat-dong-san-quan-' . $khu_vuc . '/',
            ];
        }

        return $crumbs;
    }

    // 4. Project Archive / Listing Page
    $is_du_an_archive = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'))
        || (function_exists('get_query_var') && (
            get_query_var('du_an_khu_vuc') ||
            get_query_var('du_an_type_slug') ||
            get_query_var('du_an_price_slug') ||
            get_query_var('du_an_slug_1')
        ));

    if ($is_du_an_archive) {
        $crumbs[] = [
            'label' => 'Dự án',
            'url'   => function_exists('home_url') ? home_url('/du-an') : '/du-an',
        ];

        $khu_vuc = function_exists('get_query_var') ? (get_query_var('du_an_khu_vuc') ?: get_query_var('du_an_slug_1')) : '';
        if (!$khu_vuc && isset($_GET['khu_vuc'])) {
            $khu_vuc = sanitize_text_field(wp_unslash($_GET['khu_vuc']));
        }
        if ($khu_vuc) {
            $area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : [];
            $district_label = $area_options[$khu_vuc] ?? ucwords(str_replace('-', ' ', $khu_vuc));
            $crumbs[] = [
                'label' => $district_label,
                'url'   => function_exists('home_url') ? home_url('/du-an-' . $khu_vuc) : '/du-an-' . $khu_vuc,
            ];
        }

        return $crumbs;
    }

    return $crumbs;
}

/**
 * Generate BreadcrumbList schema.
 *
 * @param array $items Optional array of breadcrumb parts with 'label' and 'url'
 * @return array BreadcrumbList schema array or empty array
 */
function batdongsan_get_schema_breadcrumb_list(array $items = []): array {
    if (empty($items)) {
        $items = batdongsan_resolve_current_breadcrumbs();
    }
    if (empty($items)) {
        return [];
    }

    $elements = [];
    $position = 1;

    foreach ($items as $crumb) {
        $name = isset($crumb['label']) ? batdongsan_clean_schema_text((string) $crumb['label']) : '';
        if ($name === '') {
            continue;
        }
        $url = isset($crumb['url']) ? (function_exists('esc_url') ? esc_url($crumb['url']) : $crumb['url']) : '';

        $element = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $name,
        ];
        if ($url !== '') {
            $element['item'] = $url;
        }

        $elements[] = $element;
        $position++;
    }

    if (empty($elements)) {
        return [];
    }

    $schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_breadcrumb_list', $schema, $items);
    }
    return $schema;
}

/**
 * Generate WebSite schema with SearchAction for the front page.
 *
 * Target: /bat-dong-san/?s={search_term_string}
 *
 * @return array WebSite schema array
 */
function batdongsan_get_schema_website(): array {
    $site_url = function_exists('home_url') ? home_url('/') : '/';
    $site_name = 'Bất Động Sản Hà Nội';

    $search_target = function_exists('home_url')
        ? home_url('/bat-dong-san/?s={search_term_string}')
        : '/bat-dong-san/?s={search_term_string}';

    $schema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => $site_url . '#website',
        'name'            => $site_name,
        'alternateName'   => ['BatDongSanHaNoi', 'batdongsanhanoi.net.vn'],
        'url'             => $site_url,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $search_target,
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_website', $schema);
    }
    return $schema;
}

/**
 * Generate Organization and RealEstateAgent schema with contactPoint for the front page.
 *
 * Enhanced for AI bots (ChatGPT, Gemini, Perplexity) with knowsAbout, areaServed, geo, and hours.
 *
 * @return array Organization schema array
 */
function batdongsan_get_schema_organization(): array {
    $site_url = function_exists('home_url') ? home_url('/') : '/';
    $brand_name = 'Bất Động Sản Hà Nội';

    // Logo resolution
    $logo_url = '';
    if (function_exists('has_custom_logo') && has_custom_logo() && function_exists('get_theme_mod') && function_exists('wp_get_attachment_image_url')) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full') ?: '';
        }
    }
    if (empty($logo_url)) {
        $site_icon = function_exists('get_site_icon_url') ? get_site_icon_url() : '';
        $logo_url = $site_icon ?: (function_exists('home_url') ? home_url('/wp-content/themes/batdongsan/images/logo.png') : '/wp-content/themes/batdongsan/images/logo.png');
    }

    // Contact info
    $telephone = '0934 69 9191';
    $email = 'sales@batdongsanhanoi.net.vn';
    if (function_exists('batdongsan_get_advisor_data')) {
        $advisor = batdongsan_get_advisor_data();
        if (!empty($advisor['advisor_phone'])) {
            $telephone = $advisor['advisor_phone'];
        }
        if (!empty($advisor['advisor_email'])) {
            $email = $advisor['advisor_email'];
        }
    }

    $contact_point = [
        '@type'             => 'ContactPoint',
        'telephone'         => $telephone,
        'contactType'       => 'customer service',
        'areaServed'        => 'VN',
        'availableLanguage' => ['vi', 'Vietnamese'],
    ];
    if (!empty($email)) {
        $contact_point['email'] = $email;
    }

    $schema = [
        '@context'                  => 'https://schema.org',
        '@type'                     => 'Organization',
        '@id'                       => $site_url . '#organization',
        'name'                      => $brand_name,
        'url'                       => $site_url,
        'logo'                      => $logo_url,
        'image'                     => $logo_url,
        'telephone'                 => $telephone,
        'email'                     => $email,
        'priceRange'                => '1.000.000.000 VND - 50.000.000.000 VND',
        'contactPoint'              => $contact_point,
        'address'                   => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Hà Nội',
            'addressRegion'   => 'Hà Nội',
            'addressCountry'  => 'VN',
        ],
        'geo'                       => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => 21.028511,
            'longitude' => 105.804817,
        ],
        'areaServed'                => [
            'Hà Nội', 'Cầu Giấy', 'Ba Đình', 'Tây Hồ', 'Nam Từ Liêm', 'Gia Lâm',
            'Long Biên', 'Đống Đa', 'Hoàng Mai', 'Thanh Xuân', 'Hà Đông', 'Bắc Từ Liêm'
        ],
        'knowsAbout'                => [
            'Bất động sản Hà Nội',
            'Mua bán nhà đất Hà Nội',
            'Căn hộ chung cư Hà Nội',
            'Biệt thự liền kề',
            'Dự án bất động sản Hà Nội',
            'Quy hoạch đô thị Hà Nội',
        ],
        'openingHoursSpecification' => [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens'     => '08:00',
            'closes'    => '21:00',
        ],
        'sameAs'                    => [
            'https://zalo.me/0934699191',
        ],
    ];

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_schema_organization', $schema);
    }
    return $schema;
}

/**
 * Determine and retrieve active schemas for the current view.
 *
 * @return array Map of [schema_key => schema_array]
 */
function batdongsan_get_active_schemas(): array {
    $schemas = [];

    $is_front = (function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home());
    if ($is_front) {
        $website = batdongsan_get_schema_website();
        if (!empty($website)) {
            $schemas['website'] = $website;
        }

        $org = batdongsan_get_schema_organization();
        if (!empty($org)) {
            $schemas['organization'] = $org;
        }
        return $schemas;
    }

    // Single property
    if (function_exists('is_singular') && is_singular('bat-dong-san')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        $listing = batdongsan_get_schema_real_estate_listing($post_id);
        if (!empty($listing)) {
            $schemas['real_estate_listing'] = $listing;
        }

        $faq = batdongsan_get_schema_faq_page($post_id);
        if (!empty($faq)) {
            $schemas['faq'] = $faq;
        }

        $breadcrumbs = batdongsan_get_schema_breadcrumb_list();
        if (!empty($breadcrumbs)) {
            $schemas['breadcrumbs'] = $breadcrumbs;
        }
        return $schemas;
    }

    // Single project
    if (function_exists('is_singular') && is_singular('du-an')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        $project = batdongsan_get_schema_du_an($post_id);
        if (!empty($project)) {
            $schemas['project'] = $project;
        }

        $faq = batdongsan_get_schema_faq_page($post_id);
        if (!empty($faq)) {
            $schemas['faq'] = $faq;
        }

        $breadcrumbs = batdongsan_get_schema_breadcrumb_list();
        if (!empty($breadcrumbs)) {
            $schemas['breadcrumbs'] = $breadcrumbs;
        }
        return $schemas;
    }

    // Property archive
    $is_bds_archive = (function_exists('is_post_type_archive') && is_post_type_archive('bat-dong-san'))
        || (function_exists('is_page') && is_page('bat-dong-san'))
        || (function_exists('is_page_template') && is_page_template('page-bat-dong-san.php'))
        || (function_exists('get_query_var') && (
            get_query_var('bds_ban_slug') ||
            get_query_var('bds_khu_vuc') ||
            get_query_var('bds_filter_slug') ||
            get_query_var('bds_type_slug')
        ));

    if ($is_bds_archive) {
        $breadcrumbs = batdongsan_get_schema_breadcrumb_list();
        if (!empty($breadcrumbs)) {
            $schemas['breadcrumbs'] = $breadcrumbs;
        }
        return $schemas;
    }

    // Project archive
    $is_du_an_archive = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'))
        || (function_exists('get_query_var') && (
            get_query_var('du_an_khu_vuc') ||
            get_query_var('du_an_type_slug') ||
            get_query_var('du_an_price_slug') ||
            get_query_var('du_an_slug_1')
        ));

    if ($is_du_an_archive) {
        $breadcrumbs = batdongsan_get_schema_breadcrumb_list();
        if (!empty($breadcrumbs)) {
            $schemas['breadcrumbs'] = $breadcrumbs;
        }
        return $schemas;
    }

    // Other pages
    $breadcrumbs = batdongsan_get_schema_breadcrumb_list();
    if (!empty($breadcrumbs) && !empty($breadcrumbs['itemListElement']) && count($breadcrumbs['itemListElement']) > 1) {
        $schemas['breadcrumbs'] = $breadcrumbs;
    }

    if (function_exists('apply_filters')) {
        return apply_filters('batdongsan_active_schemas', $schemas);
    }
    return $schemas;
}

/**
 * Rank Math Schema Graph Filter Integration.
 *
 * Injects RealEstateListing, Place/ApartmentComplex, FAQPage, and Hierarchical Breadcrumbs
 * into Rank Math's unified @graph schema output.
 *
 * @param array $data Current schema graph data
 * @param mixed $jsonld JsonLD instance
 * @return array Enriched schema data
 */
function batdongsan_rank_math_json_ld_integration($data, $jsonld) {
    if (!is_array($data)) {
        $data = [];
    }

    if (function_exists('is_singular')) {
        if (is_singular('bat-dong-san')) {
            $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
            $listing = batdongsan_get_schema_real_estate_listing($post_id);
            if (!empty($listing)) {
                unset($listing['@context']);
                $data['real_estate_listing'] = $listing;
            }
            $faq = batdongsan_get_schema_faq_page($post_id);
            if (!empty($faq)) {
                unset($faq['@context']);
                $data['faq'] = $faq;
            }
        } elseif (is_singular('du-an')) {
            $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
            $project = batdongsan_get_schema_du_an($post_id);
            if (!empty($project)) {
                unset($project['@context']);
                $data['project'] = $project;
            }
            $faq = batdongsan_get_schema_faq_page($post_id);
            if (!empty($faq)) {
                unset($faq['@context']);
                $data['faq'] = $faq;
            }
        }
    }

    if ((function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home())) {
        $org = batdongsan_get_schema_organization();
        if (!empty($org)) {
            unset($org['@context']);
            $data['publisher'] = array_merge($data['publisher'] ?? [], $org);
        }
    }

    // Replace Breadcrumbs with our rich breadcrumbs if on pretty filter or custom post
    $crumbs = batdongsan_get_schema_breadcrumb_list();
    if (!empty($crumbs) && !empty($crumbs['itemListElement'])) {
        unset($crumbs['@context']);
        $canon = function_exists('wp_get_canonical_url') ? wp_get_canonical_url() : home_url($_SERVER['REQUEST_URI'] ?? '/');
        $crumbs['@id'] = $canon . '#breadcrumb';
        $data['BreadcrumbList'] = $crumbs;
    }

    return $data;
}
if (function_exists('add_filter')) {
    add_filter('rank_math/json_ld', 'batdongsan_rank_math_json_ld_integration', 99, 2);
}

/**
 * Render structured data JSON-LD in wp_head (priority 20).
 *
 * @return void
 */
function batdongsan_render_structured_data(): void {
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    // If Rank Math is active and schema is enabled in Rank Math, let Rank Math output the unified graph to avoid duplication
    $skip_due_to_rank_math = class_exists('RankMath')
        && method_exists('RankMath\Helper', 'is_module_active')
        && \RankMath\Helper::is_module_active('rich-snippet');

    if ($skip_due_to_rank_math && !apply_filters('batdongsan_force_render_native_schema', false)) {
        return;
    }

    $schemas = batdongsan_get_active_schemas();
    if (function_exists('apply_filters')) {
        $schemas = apply_filters('batdongsan_schemas_to_render', $schemas);
    }

    if (empty($schemas) || !is_array($schemas)) {
        return;
    }

    foreach ($schemas as $schema) {
        if (!empty($schema) && is_array($schema)) {
            batdongsan_output_schema_json_ld($schema);
        }
    }
}

/**
 * Alias for batdongsan_render_structured_data to satisfy hook naming contracts.
 *
 * @return void
 */
function batdongsan_output_json_ld_schema(): void {
    batdongsan_render_structured_data();
}

// Hook into wp_head with priority 20
if (function_exists('add_action')) {
    add_action('wp_head', 'batdongsan_render_structured_data', 20);
}
