# Test Infrastructure & E2E Specification — BatDongSan Theme

## 1. Overview & Architectural Principles

This document specifies the end-to-end (E2E) automated testing infrastructure for the **BatDongSan WordPress Theme architectural overhaul**, covering Requirements **R1, R2, R3, and R4** as specified in `ORIGINAL_REQUEST.md` and `PROJECT.md`.

### 1.1 Test Philosophy & Constraints
- **Test Integrity**: Every test case validates real system behavior and interface contracts against authoritative specifications. Facade or trivial tests that always pass are strictly prohibited.
- **Authoritative Derivation**: Expected test outcomes are derived directly from `PROJECT.md § Interface Contracts` and `ORIGINAL_REQUEST.md § Acceptance Criteria`.
- **Zero-Dependency CLI Portability**: The test suite executes in pure PHP 8.4 CLI mode (`php tests/run-e2e-tests.php`) as well as in WP-CLI environments (`wp eval-file tests/run-e2e-tests.php`). It provides an in-memory high-fidelity mock WordPress runtime to guarantee fast, deterministic, and isolated execution without external database dependencies or environment pollution.
- **Fail-Fast & Exit Code Contract**: The test runner returns exit code `0` if and only if **100%** of executed tests pass. Any test assertion failure, uncaught exception, or missing requirement returns a non-zero exit code (`1`).

---

## 2. Test Infrastructure Architecture

### 2.1 Directory Structure
```
tests/
├── run-e2e-tests.php                 # CLI / WP-CLI Entrypoint and Runner
├── bootstrap.php                     # Test Environment Bootstrapper & WP Mock Engine
├── framework/
│   ├── TestCase.php                  # Base TestCase with assertion library
│   ├── TestSuite.php                 # Suite aggregator and execution engine
│   ├── MockWordPress.php             # In-memory WordPress runtime, DB, and state spy
│   └── OutputRenderer.php            # Console reporter with ASCII tables and ANSI colors
└── e2e/
    ├── tier1-feature-coverage/
    │   ├── TestR1CptMigration.php    # R1: CPT, Taxonomies, Relationships, Migration, Redirects
    │   ├── TestR2PerformanceSecurity.php # R2: no_found_rows, N+1, Nonces, Sanitization, Transients
    │   ├── TestR3AjaxSearchFilter.php# R3: BĐS AJAX Filter, Pagination, Rewrite Rules, SSR
    │   └── TestR4LeadsProcessing.php # R4: Leads CPT, Admin Columns, Endpoint, Honeypot, Mail
    ├── tier2-boundary-corner/
    │   ├── TestR1BoundaryCases.php   # R1: Empty DB, Corrupt meta, Unicode slugs, Slug collisions
    │   ├── TestR2BoundaryCases.php   # R2: SQLi vectors, XSS payload bypasses, Extreme numbers
    │   ├── TestR3BoundaryCases.php   # R3: Out-of-bounds pagination, Multi-hyphen collisions, Special chars
    │   └── TestR4BoundaryCases.php   # R4: Phone variations, Whitespace honeypots, Incomplete payloads
    ├── tier3-combinations/
    │   └── TestCrossFeatureCombinations.php # Pairwise & cross-requirement interactions
    └── tier4-scenarios/
        └── TestRealWorldScenarios.php       # End-to-end user and admin workflows
```

### 2.2 Execution Modes
1. **Full Suite Execution**:
   ```bash
   php tests/run-e2e-tests.php
   ```
2. **Filtered by Tier**:
   ```bash
   php tests/run-e2e-tests.php --tier=1
   php tests/run-e2e-tests.php --tier=2
   php tests/run-e2e-tests.php --tier=3
   php tests/run-e2e-tests.php --tier=4
   ```
3. **Filtered by Requirement / Feature**:
   ```bash
   php tests/run-e2e-tests.php --feature=R1
   php tests/run-e2e-tests.php --feature=R2
   php tests/run-e2e-tests.php --feature=R3
   php tests/run-e2e-tests.php --feature=R4
   ```
4. **WP-CLI Execution** (if WordPress database is accessible):
   ```bash
   wp eval-file tests/run-e2e-tests.php
   ```

---

## 3. Four-Tier Testing Methodology & Test Inventory

### Tier 1: Feature Coverage (>=5 tests per feature group across R1–R4)

| Test ID | Feature | Test Description | Authoritative Source |
|---|---|---|---|
| `T1-R1-01` | F01 CPT Consolidation | Verify `bat-dong-san` is registered as active CPT with archive support and REST API enabled | `PROJECT.md § F01` |
| `T1-R1-02` | F02 Transaction Taxonomy | Verify taxonomy `hinh-thuc-bds` is registered with terms `so-cap`, `chuyen-nhuong`, `cho-thue` | `PROJECT.md § F02` |
| `T1-R1-03` | F03 Relationship Optimization | Verify complete elimination of `LIKE '%ID%'` queries on `du_an_lien_quan` across all files | `AUDIT.md:37-56`, `PROJECT.md § F03` |
| `T1-R1-04` | F04 Safe Migration CLI/Service | Verify `BDS_CPT_Migration::run(['dry_run' => true])` produces dry-run stats without altering DB | `PROJECT.md § 2` |
| `T1-R1-05` | F05 301 SEO Redirect Engine | Verify legacy URLs (`/hang-chuyen-nhuong/*`, `/nha-dat-ban/*`) trigger HTTP 301 redirects to `/bat-dong-san/*` | `ORIGINAL_REQUEST.md:17` |
| `T1-R1-06` | F04 Migration Execution | Verify live migration migrates legacy posts to `bat-dong-san`, maps terms, and normalizes meta | `PROJECT.md § F04` |
| `T1-R2-01` | F07 Sub-query Optimization | Verify all 21 identified sub-queries in codebase include `'no_found_rows' => true` | `AUDIT.md:57-76`, `PROJECT.md § F07` |
| `T1-R2-02` | F08 N+1 Query Elimination | Verify `template-parts/card-project-*.php` eliminate raw `WP_Query` inside loop iterations | `AUDIT.md:81-98`, `PROJECT.md § F08` |
| `T1-R2-03` | F10 Nonce CSRF Hardening | Verify missing or invalid nonce in `batdongsan_filter_projects` returns HTTP 403 Forbidden | `ORIGINAL_REQUEST.md:49`, `PROJECT.md § F10` |
| `T1-R2-04` | F11 Map Facade XSS Protection | Verify `template-parts/map-facade.php` sanitizes embed URLs, rejecting `javascript:` and bad protocols | `SECURITY_AUDIT.md:75-110` |
| `T1-R2-05` | F09 Transient Caching | Verify price and area range dynamic filter bounds use Transients with expiration | `AUDIT.md:141-150`, `PROJECT.md § F09` |
| `T1-R2-06` | F11 Seeder Protection | Verify `seed-du-an.php` and `seed-hang-chuyen-nhuong.php` block unauthenticated web access | `SECURITY_AUDIT.md:29-70` |
| `T1-R3-01` | F12 Listings AJAX Endpoint | Verify AJAX action `batdongsan_filter_listings` is registered for authenticated & nopriv | `PROJECT.md § 3` |
| `T1-R3-02` | F12 AJAX Filter Response Schema | Verify valid filter request returns JSON with `html`, `pagination`, `total`, `total_pages`, `url` | `PROJECT.md § 3` |
| `T1-R3-03` | F12 Nonce Validation on Filter | Verify missing/invalid nonce on `batdongsan_filter_listings` returns HTTP 403 Forbidden | `ORIGINAL_REQUEST.md:49` |
| `T1-R3-04` | F14 SEO Rewrite Rules | Verify project and listing rewrite rules do not collide on multi-hyphen slugs | `PROJECT.md § F14` |
| `T1-R3-05` | F14 SSR Direct Query Handling | Verify direct access query vars correctly hydrate WP_Query filters on server | `ORIGINAL_REQUEST.md:30, 53` |
| `T1-R3-06` | F13 Project AJAX Pagination | Verify `batdongsan_filter_projects` supports `paged` parameter and returns pagination metadata | `PROJECT.md § F13` |
| `T1-R4-01` | F16 Leads CPT Registration | Verify CPT `khach-hang-lien-he` is registered with admin menu and proper capabilities | `ORIGINAL_REQUEST.md:34` |
| `T1-R4-02` | F16 Leads Custom Admin Columns | Verify admin columns include Name, Phone, Email, Source, Status, Date with formatted values | `ORIGINAL_REQUEST.md:34` |
| `T1-R4-03` | F17 Leads Submission Endpoint | Verify valid lead submission via `batdongsan_submit_lead` saves post and returns `lead_id` | `PROJECT.md § 4` |
| `T1-R4-04` | F17 Honeypot Anti-Bot Trap | Verify submission with non-empty honeypot field is rejected with HTTP 400 Bad Request | `ORIGINAL_REQUEST.md:35`, `PROJECT.md § 4` |
| `T1-R4-05` | F17 Phone & Email Validation | Verify invalid phone number or invalid email returns HTTP 400 validation error | `ORIGINAL_REQUEST.md:35` |
| `T1-R4-06` | F19 Email & Webhook Hook | Verify lead creation triggers `wp_mail` to admin and fires `batdongsan_lead_created` action | `ORIGINAL_REQUEST.md:36`, `PROJECT.md § 4` |

---

### Tier 2: Boundary & Corner Cases (>=5 tests per feature group across R1–R4)

| Test ID | Feature | Boundary / Corner Case Condition | Expected Outcome |
|---|---|---|---|
| `T2-R1-01` | R1 Migration | Migration execution on an empty legacy database (zero legacy posts) | Returns `success=true, migrated_count=0` without errors or exceptions |
| `T2-R1-02` | R1 Migration | Malformed/corrupted serialized string in `du_an_lien_quan` meta (e.g. `s:5:"abc";`) | Normalizes to `0` or null without PHP warning/fatal error |
| `T2-R1-03` | R1 301 Redirect | Legacy URLs with special characters, spaces, or unicode Vietnamese characters | Properly percent-encoded HTTP 301 target URL generated without fatal |
| `T2-R1-04` | R1 Migration | Post slug collision between migrating legacy post and existing `bat-dong-san` post | Generates unique post slug (`-2`) and migrates post preserving data |
| `T2-R1-05` | R1 Migration | Legacy post assigned to deleted or non-existent taxonomy term ID | Assigns default fallback term or skips gracefully without halting batch |
| `T2-R2-01` | R2 Security | SQL Injection payloads in filter parameters (`1' OR '1'='1`, `UNION SELECT`) | Escaped via `$wpdb->prepare()`, executes literal text search without syntax error |
| `T2-R2-02` | R2 Boundaries | Extreme numeric boundaries in price/area filters (`-999`, `1e20`, `NaN`) | Sanitized to safe numeric limits or ignored, no database overflow |
| `T2-R2-03` | R2 Security | Exotic XSS vectors in map embed (`javascript:/*--></script>`, `vbscript:`, `data:text/html`) | Completely sanitized/stripped by map facade, leaving empty safe frame |
| `T2-R2-04` | R2 Caching | Cache stampede / concurrency during transient expiration | Thread-safe rebuild or graceful fallback to default range arrays |
| `T2-R2-05` | R2 Boundaries | Massive string input (>64KB) in text search queries | Truncated and sanitized without memory exhaustion or crash |
| `T2-R3-01` | R3 Filter | Requesting out-of-bounds page number (`paged=999999`) | Returns empty results array with accurate `total` and `total_pages`, no 500 error |
| `T2-R3-02` | R3 Filter | Empty filter submission (all params `0`, empty, or omitted) | Returns standard paginated active listings with default sort |
| `T2-R3-03` | R3 Filter | Mutually exclusive filters yielding zero matching listings | Returns `total=0`, `total_pages=0`, and friendly "Không tìm thấy kết quả" HTML |
| `T2-R3-04` | R3 Rewrites | Multi-hyphen taxonomy slugs (e.g. `biet-thu-lien-ke` in `nam-tu-liem`) | Accurately matched to correct type and area terms without truncation |
| `T2-R3-05` | R3 Filter | Vietnamese accents, quotes, and meta-characters in search query | Normalized and matched accurately against post title and content |
| `T2-R4-01` | R4 Leads | Diverse Vietnamese phone formats (`0901234567`, `+84901234567`, `84901234567`, `03...`, `07...`) | Accepted and normalized to standard format |
| `T2-R4-02` | R4 Leads | Leading and trailing whitespaces, carriage returns in form fields | Trimmed and sanitized cleanly before insertion |
| `T2-R4-03` | R4 Leads | Stored XSS payload in lead `fullname` and `message` fields | Sanitized on input, escaped on output in admin list table columns |
| `T2-R4-04` | R4 Leads | Honeypot field filled with invisible whitespace (`"   "`, `\t`) | Treated as filled honeypot and rejected as automated bot (HTTP 400) |
| `T2-R4-05` | R4 Leads | Submission with only required fields (Name + Phone, empty Email and Message) | Successfully creates lead with null/empty optional fields |

---

### Tier 3: Cross-Feature Combinations (Pairwise Interactions)

| Test ID | Interaction | Tested Workflow & Verification |
|---|---|---|
| `T3-COMBO-01` | R1 + R2 | Migrated listings queried via refactored numeric relationship queries execute without N+1 overhead and leverage transient caching. |
| `T3-COMBO-02` | R1 + R3 | Migrated `bat-dong-san` listings are immediately discoverable and filterable via the new AJAX filter endpoint by taxonomy `hinh-thuc-bds`. |
| `T3-COMBO-03` | R1 + R4 | Submitting a consultation lead from a migrated listing post associates the lead with the normalized post ID and stores it in CPT `khach-hang-lien-he`. |
| `T3-COMBO-04` | R2 + R3 | AJAX filter enforces nonce verification (HTTP 403 on invalid), sanitizes all GET/POST parameters, and utilizes transient cache for filter boundaries. |
| `T3-COMBO-05` | R2 + R4 | Leads endpoint enforces CSRF protection, input sanitization, output escaping in admin columns, and blocks automated bot attacks. |
| `T3-COMBO-06` | R3 + R4 | User filters listings via AJAX, opens Advisor Modal on a filtered listing, and submits a consultation request passing the correct post context. |

---

### Tier 4: Real-World Application Scenarios

| Test ID | Scenario Name | Scenario Description & Verification Flow |
|---|---|---|
| `T4-SCEN-01` | Full Migration & SEO Preservation | End-to-end simulation of migrating a legacy site: 1) Run migration dry-run; 2) Execute live batch migration; 3) Verify data in `bat-dong-san`; 4) Request old legacy URL `/hang-chuyen-nhuong/can-ho-golden-park`; 5) Verify HTTP 301 redirect response targeting `/bat-dong-san/can-ho-golden-park`. |
| `T4-SCEN-02` | Buyer Search, Filter & SSR Journey | 1) Visitor navigates to `/bat-dong-san`; 2) Selects transaction type "Chuyển nhượng" and district "Cầu Giấy"; 3) AJAX fetches matching cards, counts, and pagination; 4) URL is rewritten; 5) Direct HTTP GET on rewritten URL (SSR) returns pre-rendered identical listings without JS. |
| `T4-SCEN-03` | Lead Generation & Admin Dispatch | 1) Visitor views project details page; 2) Opens Advisor Modal; 3) Enters consultation details; 4) Submits via AJAX with valid nonce; 5) Lead saved to `khach-hang-lien-he` with status "Mới"; 6) Admin email dispatched via `wp_mail`; 7) `batdongsan_lead_created` hook executed. |
| `T4-SCEN-04` | Malicious Bot Attack & CSRF Defense | 1) Bot submits lead with filled honeypot -> HTTP 400 Bad Request; 2) Attacker sends AJAX request without nonce -> HTTP 403 Forbidden; 3) Attacker submits SQLi payload in search -> safely prepared; 4) Zero corrupt records created in DB. |
| `T4-SCEN-05` | Admin Operations & Lead Lifecycle | 1) Admin accesses `khach-hang-lien-he` post table; 2) Custom columns render formatted telephone link and status badges; 3) Admin updates status to "Đã gọi" / "Thành công"; 4) Postmeta update persists correctly. |

---

## 4. Acceptance Criteria Traceability Matrix

| Requirement | Acceptance Criteria Item | Covered By Test IDs |
|---|---|---|
| **R1** | 100% posts and metadata migrated from legacy types to `bat-dong-san` without data loss | `T1-R1-01`, `T1-R1-04`, `T1-R1-06`, `T2-R1-01`, `T2-R1-04`, `T4-SCEN-01` |
| **R1** | HTTP 301 permanent redirects for legacy URLs preserving SEO equity | `T1-R1-05`, `T2-R1-03`, `T4-SCEN-01` |
| **R1** | Complete elimination of serialized `LIKE '%ID%'` queries on `du_an_lien_quan` | `T1-R1-03`, `T2-R1-02`, `T3-COMBO-01` |
| **R2** | All PHP files pass syntax check (`php -l`) cleanly | Runner pre-flight syntax check |
| **R2** | Elimination of N+1 queries in loops and card templates | `T1-R2-02`, `T3-COMBO-01` |
| **R2** | All 21 sub-queries have `'no_found_rows' => true` | `T1-R2-01` |
| **R2** | Transient caching for dynamic filter bounds with invalidation hooks | `T1-R2-05`, `T2-R2-04`, `T3-COMBO-04` |
| **R2** | Nonce verification returns HTTP 403 Forbidden on missing/invalid nonces | `T1-R2-03`, `T1-R3-03`, `T2-R4-04`, `T4-SCEN-04` |
| **R2** | Input sanitization, output escaping, and map facade Stored XSS patch | `T1-R2-04`, `T2-R2-01`, `T2-R2-03`, `T2-R4-03` |
| **R3** | AJAX filter updates cards, pagination, and count without full page reload | `T1-R3-01`, `T1-R3-02`, `T1-R3-06`, `T3-COMBO-02`, `T4-SCEN-02` |
| **R3** | SEO friendly rewrite rules without greedy collision | `T1-R3-04`, `T2-R3-04`, `T4-SCEN-02` |
| **R3** | Direct URL access renders server-side (SSR) matched results | `T1-R3-05`, `T4-SCEN-02` |
| **R4** | CPT `khach-hang-lien-he` in WP Admin with full column schema | `T1-R4-01`, `T1-R4-02`, `T4-SCEN-05` |
| **R4** | AJAX lead endpoint with nonce, honeypot anti-bot trap, phone/email validation | `T1-R4-03`, `T1-R4-04`, `T1-R4-05`, `T2-R4-01`, `T4-SCEN-03`, `T4-SCEN-04` |
| **R4** | Admin email notification via `wp_mail` and `batdongsan_lead_created` hook | `T1-R4-06`, `T3-COMBO-03`, `T4-SCEN-03` |
