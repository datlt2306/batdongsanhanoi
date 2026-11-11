# WordPress Security Audit — BatDongSan Theme

## Executive Summary
This document provides a comprehensive security audit of the custom `batdongsan` theme. The audit focused on identifying vulnerabilities, analyzing data flows, mapping the attack surface, and providing remediation strategies.

A critical security flaw was identified in the development/seeding scripts (`seed-du-an.php` and `seed-hang-chuyen-nhuong.php`) which allows unauthenticated remote attackers to trigger database wipes and data injection. Additionally, potential stored XSS vectors and database performance/abuse risks were reviewed.

---

## Attack Surface
The custom theme exposes the following interfaces and data entry points:
* **Public Seeding Scripts (Direct File Execution)**: 
  * `seed-du-an.php`
  * `seed-hang-chuyen-nhuong.php`
* **AJAX Endpoint**:
  * Action: `batdongsan_filter_projects` (Registered via `wp_ajax_batdongsan_filter_projects` and `wp_ajax_nopriv_batdongsan_filter_projects`)
* **URL/GET Parameters**:
  * Search and filter parameters such as `project-name`, `type`, `khu_vuc`, `price`, `area`, `status`, and `order`.
* **Database Queries**:
  * Custom SQL dynamic clauses added via `posts_where` hooks (e.g. `batdongsan_ajax_filter_posts_by_name`, `bds_filter_posts_by_name`) using `$wpdb->prepare()`.
  * Meta queries executing on user-supplied parameters.
* **ACF Admin Metadata Inputs**:
  * Fields like `map_embed` (Google Map iframe integration) processed and rendered on the frontend.

---

## Critical Findings

### Finding: SEC-01
* **ID**: SEC-01
* **Title**: Unauthenticated Database Wipe and Re-Seeding (Authentication Bypass)
* **Severity**: CRITICAL
* **Confidence**: CONFIRMED
* **Location**: [seed-du-an.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/seed-du-an.php) and [seed-hang-chuyen-nhuong.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/seed-hang-chuyen-nhuong.php)
* **Affected Flow**: Direct script execution via web browser.
* **Attack Prerequisites**: No authentication is required. The attacker only needs to know or guess the URL to the theme's seeding script.
* **Evidence**:
  In `seed-du-an.php`:
  ```php
  // Kiểm tra quyền admin (nếu chạy qua browser)
  if (isset($_GET['seed']) && $_GET['seed'] === '1') {
      if (!current_user_can('manage_options')) {
          die('Bạn không có quyền thực hiện thao tác này.');
      }
  }
  ...
  } else {
      // Chạy qua WP-CLI hoặc code
      // Tự động xóa dữ liệu cũ khi chạy seed
      $deleted = delete_old_du_an_posts();
      ...
  }
  ```
* **Data Flow**: 
  HTTP GET `/wp-content/themes/batdongsan/seed-du-an.php` (no parameters) → skips checking `isset($_GET['seed'])` → falls into the `else` block → calls `delete_old_du_an_posts()` and deletes all current `du-an` posts → re-inserts seed data.
* **Vulnerability**: The authentication check is conditionally applied only when `$_GET['seed'] === '1'`. If the request is made without `seed=1`, the permission check is bypassed, executing the data-destructive and resource-intensive re-seed block.
* **Potential Impact**: Complete loss of all customer/agent listings under CPT `du-an` and `hang-chuyen-nhuong` (Denial of Service, Data Destruction, and Data Corruption).
* **Recommended Fix**: Ensure that any web browser-initiated execution of these files is strictly authenticated, or prevent direct web access entirely. For example, check if `WP_CLI` is defined before running, or mandate a strong authorization key.
  ```php
  if (php_sapi_name() !== 'cli' && (!defined('WP_CLI') || !WP_CLI)) {
      if (!current_user_can('manage_options')) {
          wp_die('Unauthorized access.');
      }
  }
  ```
  Alternatively, move seeding scripts outside of the web root or disable/delete them in production environments.
* **Compatibility Risk**: None (seeding scripts are only intended for development).
* **Verification Method**: Make an unauthenticated GET request to the script endpoint (without `?seed=1`) and observe if data is deleted and re-seeded.
* **Map to OWASP/CWE**: CWE-287 (Improper Authentication), OWASP A01:2021-Broken Access Control.

---

## High Findings

### Finding: SEC-02
* **ID**: SEC-02
* **Title**: Potential Stored XSS via Google Map Embed Field (`map_embed`)
* **Severity**: HIGH
* **Confidence**: POTENTIAL
* **Location**: [template-parts/map-facade.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/template-parts/map-facade.php) and [single-du-an.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/single-du-an.php)
* **Affected Flow**: Admin metadata entry → Database storage → Client rendering.
* **Attack Prerequisites**: Attacker must have capability to edit the `du-an` post fields (e.g. compromised Contributor/Editor credentials, or via the unauthenticated seeding script in SEC-01).
* **Evidence**:
  In `template-parts/map-facade.php`:
  ```php
  $embed_code = $args['embed_code'] ?? '';
  ...
  $iframe_src = '';
  if (preg_match('/src=["\']([^"\']+)["\']/', $embed_code, $matches)) {
      $iframe_src = $matches[1];
  }
  ...
  <div data-map-src="<?php echo esc_attr($iframe_src); ?>">
  ```
  In `js/main.js`:
  ```javascript
  const mapSrc = facade.dataset.mapSrc;
  ...
  const iframe = document.createElement('iframe');
  iframe.src = mapSrc;
  ```
* **Data Flow**: 
  ACF input field (`map_embed`) → Database → `$map_embed` variable in `single-du-an.php` → Passed to `map-facade.php` → Regex extracts the value of `src` attribute → Printed in `data-map-src` via `esc_attr` → Loaded dynamically via JS into `iframe.src`.
* **Vulnerability**: If `map_embed` contains `<iframe src="javascript:alert(document.domain)"></iframe>`, the regex extracts `javascript:alert(document.domain)`. When loaded dynamically via `iframe.src = mapSrc`, the Javascript executes in the context of the parent domain.
* **Potential Impact**: Remote Code Execution/Stored Cross-Site Scripting (XSS) in the context of authenticated sessions of users viewing the project pages (including administrators).
* **Recommended Fix**: Restrict the dynamic protocol to HTTP/HTTPS or strictly validate the extracted URL format using `wp_kses_bad_protocol()` or a regex whitelist ensuring it strictly targets google.com/maps domain.
* **Compatibility Risk**: Low, only affects custom or malformed map URLs.
* **Verification Method**: Input `<iframe src="javascript:alert(1)"></iframe>` in the `map_embed` ACF field and click "Nhấp để xem bản đồ" on the frontend to check if code is executed.
* **Map to OWASP/CWE**: CWE-79 (Cross-site Scripting), OWASP A03:2021-Injection.

---

## Medium Findings

### Finding: SEC-03
* **ID**: SEC-03
* **Title**: Missing Rate Limiting and Database Abuse Risk on Search Endpoint
* **Severity**: MEDIUM
* **Confidence**: HIGH CONFIDENCE
* **Location**: [inc/hooks/ajax-project-filters.php](file:///Users/ken/Local%20Sites/batdongsanhanoi/app/public/wp-content/themes/batdongsan/inc/hooks/ajax-project-filters.php)
* **Affected Flow**: Unauthenticated visitors performing advanced filters.
* **Attack Prerequisites**: None.
* **Evidence**:
  The `batdongsan_ajax_filter_projects` function runs custom text-search matching using SQL `LIKE`:
  ```php
  $where .= $wpdb->prepare(
      " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
      $like,
      $like
  );
  ```
* **Data Flow**:
  Visitor sends POST request to `/wp-admin/admin-ajax.php` with custom search query → Database query is generated with double-ended wildcard search (`%keyword%`) on `post_content` and `post_title`.
* **Vulnerability**: While the input parameters are sanitized and properly prepared using `$wpdb->prepare()` to prevent SQL injection, double-ended wildcards (`%text%`) prevent the MySQL engine from utilizing standard indexes, triggering full table scans. There are no application-level rate limits on this search.
* **Potential Impact**: Resource exhaustion and database-level Denial of Service (DoS) if queried repeatedly with complex/long queries.
* **Recommended Fix**: Implement rate limiting on the AJAX search endpoint at the web application layer or firewall (e.g. WAF, Cloudflare, or Wordfence). Alternatively, enforce a minimum character limit (e.g. 3+ characters) for search queries.
* **Compatibility Risk**: Low.
* **Verification Method**: Automate multiple concurrent requests to the AJAX search endpoint and observe system resource spikes.
* **Map to OWASP/CWE**: CWE-400 (Uncontrolled Resource Consumption), OWASP A05:2021-Security Misconfiguration.

---

## Low Findings

### Finding: SEC-04
* **ID**: SEC-04
* **Title**: Exposure of Development and Seeding Scripts in Web Root
* **Severity**: LOW
* **Confidence**: CONFIRMED
* **Location**: Directory root (`/seed-du-an.php` and `/seed-hang-chuyen-nhuong.php`)
* **Affected Flow**: Direct URL access.
* **Attack Prerequisites**: Attacker scans the webroot.
* **Evidence**:
  Files are located in the main directory of the theme.
* **Vulnerability**: Development utilities and data seeding scripts are left inside the active theme directory.
* **Potential Impact**: Heightens the attack surface and exposes administrative logic/information.
* **Recommended Fix**: Move these files out of the production build entirely. They should reside in a non-accessible directory or be deleted during deployment.
* **Compatibility Risk**: None.
* **Verification Method**: Attempt to access the files directly via HTTP in staging/production.
* **Map to OWASP/CWE**: CWE-552 (Files or Directories Accessible to External Parties).

---

## WordPress Configuration
* `WP_DEBUG_DISPLAY`: Standard WordPress themes do not control this directly, but the theme correctly suppresses WP version exposure and XML-RPC access out-of-the-box (in `functions.php`).
* Directory Listings: Standard server/Apache configuration should block directory indexing on `/wp-content/themes/batdongsan/`.

---

## AJAX / REST
* Custom AJAX actions registered: `batdongsan_filter_projects`
* Handled correctly with Nonce checks to verify intent, though nonces do not replace authorization. Because this is a read-only search flow, missing authentication is expected, but lack of rate-limiting could lead to database pressure.

---

## Authentication & Authorization
* Enforced correctly in admin templates and standard WordPress flows.
* The only authorization bypass found is in the direct execution of the seeding files (`seed-du-an.php` and `seed-hang-chuyen-nhuong.php`).

---

## Input / Output Security
* The theme uses appropriate sanitation on user input (`sanitize_text_field(wp_unslash(...))`).
* Most frontend outputs utilize proper escaping like `esc_html`, `esc_attr`, and `esc_url`.

---

## Database Security
* SQL Injection controls are solid: all dynamic variables are handled through `$wpdb->prepare()`. No unsafe dynamic queries were found.

---

## File Security
* No file upload fields exist in the custom theme codebase.
* File inclusion and dynamic file operations are kept local (`get_template_directory() . '/paths/...'`). No dynamic parameter traversal exists.

---

## Secrets
* No hardcoded API keys, database credentials, or secret keys were found in the codebase.

---

## Dependencies
* Tailwind CSS CLI used is version 4.0.0.
* No composer dependencies are used.

---

## Recommended Remediation Roadmap

### Immediate Fixes (Phase 1)
1. **Remove or Secure Seeding Scripts**:
   * Block direct access to `seed-du-an.php` and `seed-hang-chuyen-nhuong.php` in production.
   * Restrict access in development via CLI verification or strong API keys.
2. **Sanitize `map_embed` Protocols**:
   * Sanitize output of extracted iframe sources in `template-parts/map-facade.php` to prevent execution of `javascript:` protocol.

### Hardening Fixes (Phase 2)
1. **Implement AJAX Search Controls**:
   * Limit search query execution rates using a WAF or rate-limiting plugin.
   * Add client-side and server-side minimum character length constraints.
