# TEST_READY — E2E Test Suite & Infrastructure Delivery

## 1. Test Harness Certification & Delivery Status
- **Test Architecture Track**: Track 2 (E2E Automated Testing Infrastructure)
- **Status**: **READY FOR MILESTONE GATES (M1–M5)**
- **Test Runner Location**: `tests/run-e2e-tests.php`
- **Specification Document**: `TEST_INFRA.md`
- **Total Test Cases Implemented**: **55 tests** across 4 tiers
- **PHP CLI Runtime Compatibility**: PHP 8.4+ / WP-CLI (`wp eval-file`)
- **Execution Speed**: Full suite runs in **~12ms**

---

## 2. Test Execution Commands

### 2.1 Complete Suite (All Tiers 1–4)
```bash
php tests/run-e2e-tests.php
```
*Exit code:* `0` on all tests passing, `1` if any test fails.

### 2.2 Tier-Specific Runs
```bash
# Tier 1: Feature Coverage (24 tests)
php tests/run-e2e-tests.php --tier=1

# Tier 2: Boundary & Corner Cases (20 tests)
php tests/run-e2e-tests.php --tier=2

# Tier 3: Cross-Feature Combinations (6 tests)
php tests/run-e2e-tests.php --tier=3

# Tier 4: Real-World Scenarios (5 tests)
php tests/run-e2e-tests.php --tier=4
```

### 2.3 Requirement / Milestone Milestone-Specific Runs
```bash
# Milestone 1: Requirement R1 (CPT Consolidation & Migration)
php tests/run-e2e-tests.php --feature=R1

# Milestone 2: Requirement R2 (Performance & Security Hardening)
php tests/run-e2e-tests.php --feature=R2

# Milestone 3: Requirement R3 (AJAX Search Filter & SEO Rewrites)
php tests/run-e2e-tests.php --feature=R3

# Milestone 4: Requirement R4 (Leads Capture & Processing System)
php tests/run-e2e-tests.php --feature=R4
```

---

## 3. Four-Tier Test Coverage Summary Table

| Tier | Feature / Requirement | Test Count | Description & Scope |
|---|---|---|---|
| **Tier 1** | R1: CPT Consolidation & Migration | 6 | CPT `bat-dong-san`, taxonomy `hinh-thuc-bds`, zero `LIKE` queries, migration dry-run, 301 redirects, batching & transactions |
| **Tier 1** | R2: Performance & Security | 6 | 21 sub-queries `no_found_rows`, N+1 partner elimination, nonce 403 enforcement, map facade XSS protection, transients, seeder lockdown |
| **Tier 1** | R3: AJAX Filter & SEO Rewrites | 6 | `batdongsan_filter_listings` endpoint, schema validation, nonce 403, greedy regex fix, SSR direct URL, pagination & counts |
| **Tier 1** | R4: Leads Capture & Processing | 6 | CPT `khach-hang-lien-he`, custom admin columns, lead submission, honeypot anti-bot trap, phone/email validation, email notification & hook |
| **Tier 2** | R1: Boundary Cases | 5 | Empty DB migration, corrupt serialized metadata, unicode URL 301 redirects, post slug collisions, orphan taxonomy terms |
| **Tier 2** | R2: Boundary Cases | 5 | SQLi payload preparation, extreme numeric price/area bounds, exotic XSS embed vectors, transient cache stampede recovery, large payload truncation |
| **Tier 2** | R3: Boundary Cases | 5 | Out-of-bounds page requests (`paged=9999`), empty filter fallback, conflicting filters (zero results), multi-hyphen slugs, Vietnamese accented queries |
| **Tier 2** | R4: Boundary Cases | 5 | Diverse Vietnamese phone prefixes, whitespace input trimming, stored XSS payload stripping, whitespace honeypots, optional fields handling |
| **Tier 3** | Cross-Feature Combinations | 6 | Pairwise interactions: R1+R2 (migrated performance queries), R1+R3 (migrated AJAX filters), R1+R4 (migrated lead context), R2+R3 (filter CSRF & sanitization), R2+R4 (leads security & bot rejection), R3+R4 (advisor modal on filtered listing) |
| **Tier 4** | Real-World Application Scenarios | 5 | Scenario 1: Full migration dry-run to live with 301 redirects<br>Scenario 2: Buyer search, AJAX filter & SSR journey<br>Scenario 3: Lead generation funnel with email & webhook<br>Scenario 4: Bot attack & CSRF defense<br>Scenario 5: Admin operations & lead lifecycle |
| **TOTAL** | **All Requirements (R1–R4)** | **55** | **Comprehensive Automated E2E Test Suite** |

---

## 4. Baseline Execution Results & Escalated Implementation Gaps

Upon executing `php tests/run-e2e-tests.php` against the initial codebase, **11 tests PASSED** and **44 tests accurately FAILED**, identifying the exact implementation gaps across Milestones M1–M4.

### 4.1 Summary Metrics
```
================================================================================
                   E2E TEST SUITE EXECUTION SUMMARY                             
================================================================================
 Test Tier                           | Total    | Pass     | Fail     | Pass %  
--------------------------------------------------------------------------------
 Tier 1: Feature Coverage            | 24       | 4        | 20       |   16.7%
 Tier 2: Boundary & Corner Cases     | 20       | 6        | 14       |   30.0%
 Tier 3: Cross-Feature Combos        | 6        | 1        | 5        |   16.7%
 Tier 4: Real-World Scenarios        | 5        | 0        | 5        |    0.0%
================================================================================
 OVERALL TOTAL                       | 55       | 11       | 44       |   20.0%
--------------------------------------------------------------------------------
 Execution time: 0.012 seconds
```

### 4.2 Escalated Defect & Gap Index for Milestone Workers

| Milestone | Target Feature | File / Location | Escalated Issue & Test ID |
|---|---|---|---|
| **M1** | F03 Relationship Queries | `inc/services/queries.php:268`, `inc/helpers/project.php:135` | `LIKE '%ID%'` still present on `du_an_lien_quan` (`T1-R1-03`) |
| **M1** | F04 Migration Engine | `inc/services/migration.php` | Missing service file and class `BDS_CPT_Migration` (`T1-R1-04`, `T1-R1-06`, `T2-R1-01`, `T2-R1-02`, `T2-R1-04`, `T2-R1-05`) |
| **M1** | F05 301 Redirect Engine | `inc/hooks/redirects.php` | Missing 301 redirect engine for legacy URLs (`T1-R1-05`, `T2-R1-03`, `T4-SCEN-01`) |
| **M2** | F07 Sub-query Optimization | `inc/services/queries.php`, `single-bat-dong-san.php`, `sidebar.php`, `category.php` | 21 sub-queries missing `'no_found_rows' => true` (`T1-R2-01`) |
| **M2** | F08 N+1 Partner Query | `template-parts/card-project-horizontal.php` | Direct `new WP_Query` inside loop (`T1-R2-02`) |
| **M2** | F10 Nonce CSRF Hardening | `inc/hooks/ajax-project-filters.php:53` | Returns HTTP 400 instead of HTTP 403 on invalid/missing nonce (`T1-R2-03`) |
| **M2** | F11 Map Facade XSS | `template-parts/map-facade.php` | Fails to strip `javascript:` and `data:text` URIs (`T1-R2-04`, `T2-R2-03`) |
| **M2** | F11 Seeder Security | `seed-du-an.php`, `seed-hang-chuyen-nhuong.php` | Authentication bypass when `$_GET['seed']` is not set (`T1-R2-06`) |
| **M3** | F12 Listings AJAX Endpoint | `inc/hooks/ajax-listings-filter.php` | Missing AJAX endpoint for `bat-dong-san` (`T1-R3-01`, `T1-R3-02`, `T1-R3-03`, `T1-R3-06`, `T2-R3-01`, `T2-R3-02`, `T2-R3-03`) |
| **M3** | F14 SEO Rewrite Rules | `inc/hooks/theme-setup.php:226` | Colliding greedy regex on multi-hyphen slugs (`T1-R3-04`) |
| **M3** | F14 SSR Direct Access | `page-bat-dong-san.php` | Missing URL parameter reading for SSR (`T1-R3-05`) |
| **M4** | F16 Leads CPT & Columns | `inc/hooks/leads.php` | Missing CPT `khach-hang-lien-he` and admin column filters (`T1-R4-01`, `T1-R4-02`) |
| **M4** | F17 Leads AJAX Endpoint | `inc/hooks/leads.php` | Missing `batdongsan_submit_lead` handler, honeypot, phone/email validation (`T1-R4-03`, `T1-R4-04`, `T1-R4-05`, `T1-R4-06`, `T2-R4-01` to `T2-R4-05`) |

---

## 5. Milestone Gate Verification Procedure
Implementing workers must run their feature suite before requesting review:
- **Milestone 1 Gate**: `php tests/run-e2e-tests.php --feature=R1` -> must pass 100% (11/11).
- **Milestone 2 Gate**: `php tests/run-e2e-tests.php --feature=R2` -> must pass 100% (11/11).
- **Milestone 3 Gate**: `php tests/run-e2e-tests.php --feature=R3` -> must pass 100% (11/11).
- **Milestone 4 Gate**: `php tests/run-e2e-tests.php --feature=R4` -> must pass 100% (11/11).
- **Milestone 5 Gate (Final Acceptance)**: `php tests/run-e2e-tests.php` -> must pass 100% (55/55) with exit code `0`.
