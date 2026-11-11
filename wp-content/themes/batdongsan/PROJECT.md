# Project: BatDongSan Filter Fixes, Architecture Assessment & Portal Roadmap

## Architecture
- **Filter Pipeline Architecture**:
  - Frontend: Dual-form UI (`header.php` mobile modal vs `page-bat-dong-san.php` desktop horizontal bar) synchronized with `js/main.js`.
  - Client-Side Synchronization: `history.pushState` & `popstate` event handlers preserving query parameters and updating dropdown button trigger labels.
  - Query Routing & SSR: Pretty URL rewrite rules in `inc/hooks/theme-setup.php` routed to `page-bat-dong-san.php` and `page-du-an.php` with robust slug and parameter resolution.
  - Backend Query Construction: AJAX endpoints (`inc/hooks/ajax-listings-filter.php`, `inc/hooks/ajax-project-filters.php`) and service queries (`inc/services/queries.php`) utilizing optimized `tax_query` and `meta_query` with proper relation, data casting, and pagination.
- **Data Model & Scalability**:
  - CPT `bat-dong-san` and `du-an`.
  - Normalization of quantitative fields (price and area) to numeric indexed postmeta to eliminate PHP memory-exhausting `-1` full table fetches.
  - Transient caching for global filter options in `header.php` and filter ranges to prevent cascading site freezes.
  - Cache warming with `update_post_caches()` in AJAX loops to prevent N+1 metadata queries.
- **Portal Feature Architecture**:
  - SEO Structured Data (`RealEstateListing`, `BreadcrumbList`, `FAQPage`).
  - User engagement & retention (Favorites, Saved Searches & Alerts, Comparison).
  - Multi-broker & verified member ecosystem.
  - Market intelligence (loan schedule, price history, planning maps).

---

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| F01 | DOM Form Index Fix | Fix `js/main.js` form selector to bind to active/submitted form, preventing desktop pagination from reading empty mobile form | M1 | R1 (Bug 1) |
| F02 | URL Rewrite & SSR District Fix | Update rewrite rules in `theme-setup.php` and parameter resolution in `page-bat-dong-san.php` so district is not dropped on SSR | M1 | R1 (Bug 2) |
| F03 | Property Type Slug Resolution | Support both numeric ID and term slug in SSR query in `page-bat-dong-san.php` | M1 | R1 (Bug 3) |
| F04 | Project Status Field & Value Fix | Align meta key to `'trang_thai'` and value format (`'dang_mo_ban'`) across queries, template, and AJAX | M1 | R1 (Bug 4) |
| F05 | Price Parsing & Slug Precision | Fix `format.php` million/billion multiplier logic and prevent integer truncation of decimal prices in `filter-slug.php` | M1 | R1 (Bug 5) |
| F06 | Additional Filter Attributes | Support bedroom (`so_phong_ngu`), bathroom (`so_phong_ve_sinh`), orientation (`huong_nha`), and legal status (`phap_ly`) | M1 | R1 (Bug 6) |
| F07 | Suburban District Options | Expand district options in `filter-options.php` to include Hoài Đức, Đan Phượng, Gia Lâm, Đông Anh, Sóc Sơn,... | M1 | R1 (Bug 7) |
| F08 | Header Filter Caching & Memory Optimization | Add transient caching for `header.php` filter options and eliminate in-memory array slicing query bottlenecks | M1 | R1 (Bug 8), R2 |
| F09 | History Popstate Label Restoration | Restore dropdown trigger button labels and handle direct/rewritten URL popstate in `js/main.js` | M1 | R1 (Bug 9) |
| F10 | Project SSR Pagination | Ensure project listing page renders SSR pagination markup when total pages > 1 | M1 | R1 (Bug 10) |
| F11 | Filter Test Matrix & Verification Script | Implement comprehensive test matrix script validating single-criteria, multi-criteria, and boundary cases with 100% pass | M2 | R1 Test Matrix |
| F12 | Architecture Assessment Report | Document in-depth data model, query bottlenecks, postmeta scan issues, caching strategies, and scaling plan for 10k-100k+ listings | M3 | R2 Report |
| F13 | Portal Benchmark & Feature Roadmap | Document comprehensive benchmark vs batdongsan.com.vn, chotot, homedy, and prioritized Must/Should/Nice roadmap | M4 | R3 Report |
| F14 | Comprehensive Review, Challenge & Audit | Dual reviewer review, adversarial challenger tests, and forensic auditor integrity verification | M5 | Acceptance |

---

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Filter Bug Remediation & Code Fixes | F01, F02, F03, F04, F05, F06, F07, F08, F09, F10 | none | IN_PROGRESS |
| M2 | Filter Test Matrix & Verification | F11 | M1 | PLANNED |
| M3 | Architecture Assessment & Scalability Report | F12 | none | PLANNED |
| M4 | Portal Benchmark & Feature Roadmap Report | F13 | none | PLANNED |
| M5 | Multi-agent Review, Challenge & Integrity Audit | F14 | M1, M2, M3, M4 | PLANNED |

---

## Code Layout
- `js/main.js`: Form selector, event handling, AJAX pagination, pushState/popstate sync, dropdown button text.
- `inc/hooks/theme-setup.php`: URL rewrite rules for district and filter combinations.
- `page-bat-dong-san.php`: SSR query processing, slug/ID resolution, filter state preservation.
- `page-du-an.php`: SSR query processing, status meta key alignment, pagination container.
- `inc/hooks/ajax-listings-filter.php`: AJAX listings filter query handling, attributes support, cache warming.
- `inc/hooks/ajax-project-filters.php`: AJAX project filter query handling, status key alignment.
- `inc/services/queries.php`: Project status queries, filter options queries, transient caching.
- `inc/helpers/format.php`: Price string parser with robust million/billion handling.
- `inc/helpers/filter-slug.php`: Slug generator and parser with decimal price support.
- `inc/helpers/filter-options.php`: Complete Hanoi district list, cached option getters.
- `tests/test-filter-matrix.php`: Automated test matrix runner verifying all filter criteria.
- `ARCHITECTURE_ASSESSMENT.md`: Comprehensive technical architecture and scalability report.
- `PORTAL_ROADMAP.md`: Real estate portal benchmark and prioritized feature roadmap.
