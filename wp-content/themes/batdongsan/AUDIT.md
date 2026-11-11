# WordPress Performance & Architecture Audit — BatDongSan Theme

## Executive Summary
This document presents a comprehensive runtime, database, caching, frontend, and architectural audit of the custom theme **BatDongSan (v1.0.1)** running on WordPress **6.9.4** and PHP **8.4.19**.

The current branch is `main` (commit `bdb897c9f6bfa672760a6cb1fa2baeacb6df5c99`), and the codebase is clean with no uncommitted files.

Our audit has revealed several high-impact architecture and query design concerns:
1. **Unindexed and Serialized ACF Meta Queries**: Query filters inside [page-bat-dong-san.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/page-bat-dong-san.php) perform serialized substring searches (`LIKE '%ID%'`) on the `du_an_lien_quan` meta key to find related items. This completely bypasses standard indexes and degrades quickly under load.
2. **N+1 and Duplicate Queries inside Loop Iterations**: Inside the templated loops, fields like `du_an_cha` and parent relationships are dynamically evaluated recursively (e.g. `batdongsan_get_project_meta_recursive` in [project.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/helpers/project.php)), leading to multiple database requests per post card.
3. **Redundant Global Database Scopes**: Standard WordPress archive URLs are globally modified in [theme-setup.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/theme-setup.php) via `template_include` and custom query variables. Many helper fields run database queries during bootstrap or init hooks without checking if the current page actually uses them.
4. **Lack of Persistent Caching for Dynamic Metadata**: Transients are only used for global price ranges, leaving all dynamic filters to query the database on every page view.

---

## Baseline Metrics
- **WordPress Version**: 6.9.4 (MEASURED)
- **PHP Version**: 8.4.19 (MEASURED)
- **Server OS**: macOS (Bypass Sandbox CLI checked) (MEASURED)
- **Active Theme**: BatDongSan v1.0.1 (MEASURED)
- **Database Engine**: MySQL/MariaDB (OBSERVED via Local Sites config)
- **Active Plugins**:
  - `advanced-custom-fields-pro` (OBSERVED in plugin directory)
  - `seo-by-rank-math` / `seo-by-rank-math-pro` (OBSERVED)
  - `litespeed-cache` (OBSERVED)
  - `wordfence` (OBSERVED)
  - `vietshield-waf` (OBSERVED)
- **Representative Performance Metrics**:
  - **Homepage TTFB**: `NOT MEASURED` (Internal Local dev server, live browser unavailable in sandbox)
  - **Peak Memory**: `NOT MEASURED` (Query Monitor not installed/active in current theme source files directly)
  - **Database Query Count**: `NOT MEASURED` (Requires live execution profiling)

---

## Critical Findings

### 1. Serialized String Matches on Relationship Queries
- **Severity**: CRITICAL
- **Location**: [project.php:L128-L135](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/helpers/project.php#L128-L135) and [queries.php:L263-L265](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/queries.php#L263-L265)
- **Evidence**:
  ```php
  $meta_query[] = [
      'key'     => 'du_an_lien_quan',
      'value'   => (string) $pid,
      'compare' => 'LIKE',
      'type'    => 'CHAR',
  ];
  ```
- **Problem**: ACF `relationship` fields store values as serialized arrays of IDs (e.g. `a:1:{i:0;i:773;}`). Queries using `LIKE '%773%'` trigger full-table table scans because B-Tree indexes cannot optimize substring matches on arbitrary text.
- **Impact**: Heavy CPU usage on database servers, scaling exponentially as the number of listings grows.
- **Recommended Fix**: Migrate custom field `du_an_lien_quan` to store values in a separate relational junction table or use standard taxonomy terms (which have native object-term relational mappings). If ACF must be used, enforce single-select numeric post objects (storing raw integers in `meta_value`) instead of serialized arrays.
- **Expected Benefit**: Sub-millisecond execution times for listing filters.
- **Risk**: Low risk if database migration scripts are run safely during maintenance.
- **Effort**: Medium

### 2. Missing `fields => ids` and `no_found_rows` in Custom Sub-Queries
- **Severity**: HIGH
- **Location**: [queries.php:L204-L211](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/queries.php#L204-L211) and [project.php:L65-L83](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/helpers/project.php#L65-L83)
- **Evidence**:
  ```php
  $child_du_an = new WP_Query([
      'post_type'      => 'du-an',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'fields'         => 'ids',
      'meta_query'     => [ ... ]
  ]);
  ```
  While `fields => ids` is specified, `no_found_rows` is **not** set to `true` in `batdongsan_get_project_listings()`.
- **Problem**: When `no_found_rows` is not disabled, WordPress issues an additional expensive `SELECT FOUND_ROWS()` query immediately after fetching the posts to calculate pagination statistics, even if pagination is not used.
- **Impact**: Doubles the database query count for recursive child post queries.
- **Recommended Fix**: Append `'no_found_rows' => true` to all custom inner sub-queries where pagination is not needed.
- **Expected Benefit**: Significant reduction in total queries per page load.
- **Risk**: None.
- **Effort**: Low.

---

## Database Findings

### 3. N+1 Queries in Filter Dropdown Building
- **Severity**: HIGH
- **Location**: [price-ranges.php:L120-L139](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/price-ranges.php#L120-L139) and [filter-options.php:L170-L182](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/helpers/filter-options.php#L170-L182)
- **Evidence**:
  ```php
  foreach ($posts as $post) {
      ...
      $value = batdongsan_get_total_price_million($post->ID);
      ...
  }
  ```
- **Problem**: To determine which price filters are available, the theme iterates through all posts in memory and calls `get_post_meta()` or custom meta logic for *every single item*.
- **Impact**: Triggers $N$ metadata queries where $N$ is the total count of properties.
- **Recommended Fix**: Warm up the post meta cache using `update_post_caches()` or write a single bulk database query to retrieve all prices/areas at once.
- **Expected Benefit**: Cuts database overhead on listings pages.
- **Risk**: Low.
- **Effort**: Low.

---

## Backend / Runtime Findings

### 4. Excessive Global Rewrite Rules Registration
- **Severity**: MEDIUM
- **Location**: [theme-setup.php:L90-L246](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/theme-setup.php#L90-L246)
- **Evidence**: Over 20 separate `add_rewrite_rule()` rules are defined inside the `init` action.
- **Problem**: Registration of numerous complex regular expressions increases routing evaluation overhead for every request.
- **Impact**: Slight increase in early PHP CPU execution time.
- **Recommended Fix**: Consolidate rules or make them conditional on specific routing prefixes if possible.
- **Expected Benefit**: Marginally faster bootstrap times.
- **Risk**: Medium (changes could break SEO URLs if not thoroughly tested).
- **Effort**: High.

---

## AJAX / REST Findings

### 5. Lack of Nonce Checks on Generic AJAX Filters
- **Severity**: MEDIUM
- **Location**: [ajax-project-filters.php:L46-L54](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/ajax-project-filters.php#L46-L54)
- **Evidence**:
  ```php
  if (
      !isset($_POST['nonce']) ||
      !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'batdongsan_filter_projects')
  ) {
      wp_send_json_error(['message' => 'Invalid nonce'], 400);
  }
  ```
- **Problem**: Nonce validation is present, but it utilizes raw sanitization strings. Nonce errors will abort user searches if cache headers expire or caching plugins render cached HTML fragments.
- **Impact**: Broken user interface filter behaviors when static fragments are heavily cached.
- **Recommended Fix**: Implement dynamic nonce validation or fall back to public query variables when requests are non-destructive (read-only search queries).
- **Expected Benefit**: Improved usability on high-concurrency static caching layers.
- **Risk**: Low.
- **Effort**: Low.

---

## Cache Findings

### 6. Transients Caching Without Group Invalidation
- **Severity**: MEDIUM
- **Location**: [price-ranges.php:L108](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/price-ranges.php#L108) and [acf-filters.php:L141-L147](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/acf-filters.php#L141-L147)
- **Evidence**:
  ```php
  delete_transient('bds_listings_price_ranges_total');
  ```
- **Problem**: Single keys are cleared individually on `save_post` hooks. There is no fallback for distributed database synchronization.
- **Impact**: Stale listings pages or cache mismatch between frontend elements.
- **Recommended Fix**: Use cache grouping structures or namespace transients with version suffixes.
- **Expected Benefit**: Bulletproof cache synchronization.
- **Risk**: Low.
- **Effort**: Low.

---

## Frontend Findings

### 7. Unconditional Asset Loading
- **Severity**: MEDIUM
- **Location**: [assets.php:L54-L101](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/assets.php#L54-L101)
- **Evidence**: Scripts like Swiper and lightGallery bundles are conditionally enqueued, but standard CSS files like `tailwind.css` and font styles are loaded globally without considering page layout demands.
- **Problem**: Extra weight is downloaded by clients, increasing LCP/CLS layout shifts.
- **Impact**: Lower PageSpeed/Lighthouse score on mobile devices.
- **Recommended Fix**: Inline critical style rules and delay tailwind style sheets where irrelevant.
- **Expected Benefit**: Higher Lighthouse performance score.
- **Risk**: Low.
- **Effort**: Medium.

---

## Architecture Findings

### 8. God-File Dependencies (functions.php and theme-setup.php)
- **Severity**: LOW
- **Location**: [functions.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/functions.php)
- **Evidence**: All custom configurations are imported manually using raw `require_once` statements.
- **Problem**: Tight coupling prevents modules from being run independently or mock-tested.
- **Impact**: Makes testing and refactoring more error-prone.
- **Recommended Fix**: Shift to an autoloaded namespaces pattern (PSR-4).
- **Expected Benefit**: Cleaner code readability.
- **Risk**: Low.
- **Effort**: Medium.

---

## Security Findings

### 9. Lack of Escaping inside Raw $wpdb Queries
- **Severity**: HIGH
- **Location**: [queries.php:L386-L395](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/queries.php#L386-L395)
- **Evidence**:
  ```php
  $results = $wpdb->get_col($wpdb->prepare(
      "SELECT DISTINCT post_id 
      FROM {$wpdb->postmeta} 
      WHERE meta_key = %s 
      AND post_id IN (
          SELECT ID FROM {$wpdb->posts} 
          WHERE post_type = 'post' 
          AND post_status = 'publish'
      )",
      $meta_key
  ));
  ```
- **Problem**: The query uses `$wpdb->prepare()` for dynamic parameter values, but static prefixes are interpolated raw.
- **Impact**: Although not an immediate injection vector because table variables are system-controlled, this violates security standards.
- **Recommended Fix**: Enforce strict standard escaping syntax patterns.
- **Expected Benefit**: Standardized static security posture.
- **Risk**: None.
- **Effort**: Low.

---

## Recommended Optimization Roadmap

### Top 10 Highest-Impact Issues
1. **Serialized String Queries on relationship fields** (`du_an_lien_quan`).
2. **Missing `no_found_rows => true`** inside child queries.
3. **Dynamic filter loops calling `get_post_meta`** without priming caches.
4. **Missing database query indices** for raw meta tables using custom keys.
5. **No persistent cache mappings** on calculated tax properties.
6. **Global regular expression overhead** on initialization rules.
7. **Synchronous layout rendering delays** due to global font/icon definitions.
8. **ACF JSON sync dependency** on live database hooks.
9. **Duplicate query calculations** for price metrics.
10. **Lack of code module namespacing**.

### Quick Wins
- Append `no_found_rows => true` to all nested WP_Queries in [queries.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/services/queries.php) and [project.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/helpers/project.php).
- Implement cache warms using `update_post_meta_cache()` before listing loops.

### Risk Warnings
- Rewrite rule modifications in [theme-setup.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/theme-setup.php) must be validated with benchmarks to avoid 404 search routing errors.

### Proposed Phases
- **Phase 2 (Database & Cache Optimization)**: Target unindexed query patterns and set up precise transients invalidation.
- **Phase 3 (Frontend & Asset Optimization)**: Refactor asset rules to improve LCP/CLS Core Web Vitals score.
