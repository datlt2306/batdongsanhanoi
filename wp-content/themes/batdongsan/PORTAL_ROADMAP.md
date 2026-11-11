# LỘ TRÌNH PHÁT TRIỂN TÍNH NĂNG TOÀN DIỆN CỔNG THÔNG TIN BẤT ĐỘNG SẢN
## BENCHMARK THỊ TRƯỜNG & BLUEPRINT KIẾN TRÚC KỸ THUẬT (BATDONGSAN THEME)

- **Mã tài liệu**: PORTAL_ROADMAP.md
- **Phiên bản hệ thống**: BatDongSan Theme v1.0.1
- **Tác giả**: Worker 2 — Architecture Assessment & Portal Roadmap Documentation Specialist
- **Ngày hoàn thành**: 2026-10-06
- **Tiêu chuẩn tham chiếu**: `ORIGINAL_REQUEST.md` (R3: Portal Benchmark & Roadmap), khảo sát thực nghiệm `explorer_survey_3/analysis.md`
- **Đối tượng đối chuẩn**: `batdongsan.com.vn` (PropertyGuru), `chotot.com` / `nhatot.com` (Carousell), `homedy.com`

---

## MỤC LỤC
1. [TỔNG QUAN CHIẾN LƯỢC & ĐỊNH VỊ NỀN TẢNG](#1-tổng-quan-chiến-lược--định-vị-nền-tảng)
2. [MA TRẬN ĐỐI CHUẨN THỊ TRƯỜNG & PHÂN TÍCH KHOẢNG CÁCH (MARKET BENCHMARK)](#2-ma-trận-đối-chuẩn-thị-trường--phân-tích-khoảng-cách-market-benchmark)
   - 2.1. Hồ sơ Chi tiết 3 Đối thủ Đầu ngành
   - 2.2. Bảng Ma trận Đối chuẩn Tính năng Đa chiều (Comprehensive Comparison Matrix)
   - 2.3. Đánh giá Khoảng cách Trọng yếu (Critical Gap Analysis)
3. [KHUNG PHÂN KỲ LỘ TRÌNH (PRIORITIZATION FRAMEWORK: P0 / P1 / P2)](#3-khung-phân-kỳ-lộ-trình-prioritization-framework-p0--p1--p2)
4. [MIỀN 1: CÔNG CỤ & TIỆN ÍCH (TOOLS & UTILITIES)](#4-miền-1-công-cụ--tiện-ích-tools--utilities)
   - Feature 1.1: Bảng Tính Lãi Suất Nâng Cao & Kế Hoạch Khấu Hao Nợ (Amortization Schedule) [P0]
   - Feature 1.2: Công Cụ So Sánh Bất Động Sản Tương Đồng Side-by-Side [P1]
   - Feature 1.3: Bản Đồ Quy Hoạch Đất Đai & Tìm Kiếm Theo Bounding Box [P2]
   - Feature 1.4: Biểu Đồ Biến Động Lịch Sử Giá & Chỉ Số Khu Vực [P2]
   - Feature 1.5: Công Cụ Định Giá Bất Động Sản Tự Động (Automated Valuation Model - AVM) [P2]
5. [MIỀN 2: TRẢI NGHIỆM NGƯỜI DÙNG & GIỮ CHÂN (USER EXPERIENCE & RETENTION)](#5-miền-2-trải-nghiệm-người-dùng--giữ-chân-user-experience--retention)
   - Feature 2.1: Lưu Tin Yêu Thích (Favorite / Wishlist) [P0]
   - Feature 2.2: Lưu Bộ Lọc Tìm Kiếm & Nhận Thông Báo Thông Minh (Smart Alerts) [P1]
   - Feature 2.3: Tạo Ảnh Thẻ BĐS Chia Sẻ Mạng Xã Hội (Infographic Card) & Mã QR Code [P1]
   - Feature 2.4: Trình Xem Ảnh 360° Panorama & Virtual Tour [P2]
6. [MIỀN 3: HỆ SINH THÁI MÔI GIỚI & THÀNH VIÊN (BROKER & MEMBER ECOSYSTEM)](#6-miền-3-hệ-sinh-thái-môi-giới--thành-viên-broker--member-ecosystem)
   - Feature 3.1: Hệ Thống Phân Cấp Tin VIP & Thương Mại Hóa (Monetization Engine) [P0]
   - Feature 3.2: Quy Trình Đăng Tin Phía Frontend Đa Bước (Multi-step Form) [P1]
   - Feature 3.3: Bảng Quản Lý Tin Cá Nhân Cho Môi Giới (Member Dashboard) [P1]
   - Feature 3.4: Hồ Sơ Môi Giới Chuyên Nghiệp & Huy Hiệu Xác Thực (Verified Badges) [P1]
   - Feature 3.5: Kênh Nhắn Tin Trực Tiếp Khách Hàng - Môi Giới (Live Chat / Zalo Gateway) [P2]
7. [MIỀN 4: SEO & TÍNH TOÀN VẸN DỮ LIỆU (SEO & DATA INTEGRITY)](#7-miền-4-seo--tính-toàn-vẹn-dữ-liệu-seo--data-integrity)
   - Feature 4.1: Hệ Thống Structured Data Schema.org (JSON-LD) Chuẩn Google [P0]
   - Feature 4.2: Ma Trận Landing Page Bộ Lọc Tự Động Chuẩn SEO (SEO Filter Pages Matrix) [P0]
8. [KIẾN TRÚC KỸ THUẬT NỀN TẢNG (UNDERLYING TECHNICAL BLUEPRINT)](#8-kiến-trúc-kỹ-thuật-nền-tảng-underlying-technical-blueprint)
   - 8.1. Thiết Kế Cơ Sở Dữ Liệu Bổ Trợ (Database Schema Extensions)
   - 8.2. Kiến Trúc REST API Tuyến Tính (`/wp-json/bds/v1/`)
   - 8.3. Chiến Lược Frontend & Thư Viện Tối Giản (Zero jQuery Dependency)
   - 8.4. Kiểm Duyệt Nội Dung, Chống Gian Lận & Bảo Mật
9. [KẾT LUẬN & KẾ HOẠCH HÀNH ĐỘNG THEO SPRINT (ACTIONABLE SPRINT PLAN)](#9-kết-luận--kế-hoạch-hành-động-theo-sprint-actionable-sprint-plan)

---

## 1. TỔNG QUAN CHIẾN LƯỢC & ĐỊNH VỊ NỀN TẢNG

### 1.1. Hiện trạng Codebase
Theme `BatDongSan` đã hoàn thành các bước củng cố hạ tầng kỹ thuật căn bản:
- Hợp nhất các Custom Post Types rời rạc về CPT chuẩn duy nhất `bat-dong-san`.
- Chuẩn hóa quan hệ giữa Dự án và BĐS sang ID số nguyên (`du_an_lien_quan`), loại bỏ hoàn toàn các truy vấn chuỗi tuần tự hóa.
- Triển khai bộ lọc AJAX đa tiêu chí có đồng bộ URL (`history.pushState` / `popstate`) và Server-Side Rendering (SSR).
- Xây dựng module Thu thập Khách hàng tiềm năng (Leads CRM thu nhỏ) an toàn với CSRF Nonce và honeypot.

### 1.2. Mục tiêu Nâng cấp: Chuyển mình thành Cổng thông tin Tổng hợp (Portal Evolution)
Website hiện tại mang bản chất của một trang web giới thiệu dự án / môi giới nội bộ độc quyền (Single Agency Site). Để vươn lên thành một **Cổng thông tin Bất động sản Toàn diện (Comprehensive Multi-listing Real Estate Portal)**, hệ thống phải giải quyết 4 trụ cột chiến lược:
1. **Khả năng Giữ chân Người dùng (User Retention)**: Xây dựng các tính năng giữ chân khách tìm nhà (Lưu tin yêu thích, Lưu tìm kiếm nhận tin mới, So sánh tài sản tương đồng).
2. **Hệ sinh thái Môi giới & Xã hội hóa Nguồn cung (Broker Ecosystem)**: Cho phép hàng nghìn môi giới bên ngoài tham gia đăng tin, quản lý tin và chứng thực năng lực chuyên môn.
3. **Mô hình Kinh doanh Độc lập (Monetization Engine)**: Tạo doanh thu định kỳ từ phân tầng tin VIP, đẩy tin, và gói dịch vụ môi giới.
4. **Vị thế Thống trị SEO (SEO Dominance)**: Phủ sóng toàn bộ từ khóa tìm kiếm mua bán/cho thuê theo địa phương thông qua cấu trúc dữ liệu Rich Snippets và ma trận landing page tự động.

---

## 2. MA TRẬN ĐỐI CHUẨN THỊ TRƯỜNG & PHÂN TÍCH KHOẢNG CÁCH (MARKET BENCHMARK)

### 2.1. Hồ sơ Chi tiết 3 Đối thủ Đầu ngành

1. **Batdongsan.com.vn (PropertyGuru Vietnam)**:
   - *Vị thế*: Cổng thông tin BĐS lớn nhất Việt Nam với lượng tin đăng và lưu lượng truy cập số 1.
   - *Thế mạnh vượt trội*: Ma trận SEO hàng trăm ngàn landing pages tự động; hệ thống phân tầng tin VIP 5 cấp độ (Kim Cương, Vàng, Bạc, Đồng, Tin thường); công cụ định giá và dữ liệu lịch sử giá theo quận/huyện; mạng lưới môi giới chuyên nghiệp phủ khắp 63 tỉnh thành.
   - *Hạn chế*: Giao diện dày đặc thông tin, tốc độ tải trang chậm do nhúng nhiều mạng lưới quảng cáo ngoài; phí đăng tin cao đối với môi giới cá nhân.
2. **Chotot.com / Nhatot.com (Carousell Group)**:
   - *Vị thế*: Sàn thương mại điện tử C2C & BĐS hàng đầu về độ thanh khoản và tốc độ giao dịch.
   - *Thế mạnh vượt trội*: Trải nghiệm di động cực nhanh; quy trình đăng tin siêu tốc; tích hợp Chat trực tiếp thời gian thực giữa người mua và người bán; tìm kiếm trực quan theo vị trí GPS trên bản đồ; nhãn "Tin xác thực" (Verified).
   - *Hạn chế*: Thiếu thông tin phân tích thị trường chuyên sâu; thiếu trang thông tin chi tiết dự án quy chuẩn; dữ liệu SEO chưa tối ưu bằng Batdongsan.com.vn.
3. **Homedy.com**:
   - *Vị thế*: Nền tảng BĐS hiện đại hướng tới thế hệ người dùng trẻ (Millennials / Gen Z).
   - *Thế mạnh vượt trội*: Thiết kế giao diện trực quan, tinh tế (Clean UI/UX); công cụ so sánh trực quan các dự án BĐS tương đồng; hệ thống đánh giá & bình luận dự án từ cộng đồng cư dân; tích hợp mặt bằng 3D và Virtual Tour.
   - *Hạn chế*: Độ phủ tin đăng ở các tỉnh vùng ven còn hạn chế; hệ thống môi giới chưa phát triển mạnh bằng Batdongsan.com.vn.

---

### 2.2. Bảng Ma trận Đối chuẩn Tính năng Đa chiều (Comprehensive Comparison Matrix)

| # | Nhóm Năng lực / Tính năng | BatDongSan (Codebase) | Batdongsan.com.vn | Chợ Tốt / Nhà Tốt | Homedy.com | Đánh giá Khoảng cách (Gap Level) |
|---|---|:---:|:---:|:---:|:---:|---|
| **I** | **CÔNG CỤ & TIỆN ÍCH** | | | | | |
| 1 | Tính lãi suất vay mua nhà | 🔶 Có (Cơ bản + Excel) | ✅ Có (Lịch trả nợ chi tiết) | 🔶 Có (Cơ bản) | ✅ Có (Gợi ý gói ngân hàng) | **TRUNG BÌNH**: Cần bổ sung bảng khấu hao nợ từng tháng và ân hạn gốc. |
| 2 | So sánh BĐS tương đồng | ❌ Chưa có | ✅ Có (So sánh 3-4 tin) | ❌ Chưa có | ✅ Có (So sánh trực quan) | **LỚN**: Thiếu khay so sánh và bảng đối chiếu đa tiêu chí. |
| 3 | Tiện ích xung quanh (POI) | ✅ Có (Google Places 2km)| ✅ Có (Bán kính 1-3km) | 🔶 Có (Sơ bộ) | ✅ Có (Chi tiết, khoảng cách) | **NGANG BẰNG**: Đã có giải pháp tính khoảng cách và thời gian đi bộ. |
| 4 | Bản đồ quy hoạch đất đai | ❌ Chưa có | 🔶 Có (Liên kết dịch vụ ngoài)| ❌ Chưa có | 🔶 Có (Sơ bộ) | **LỚN**: Chưa có lớp dữ liệu quy hoạch thửa đất/phân khu. |
| 5 | Biểu đồ lịch sử biến động giá | ❌ Chưa có | ✅ Có (Dữ liệu 1-5 năm) | ✅ Có (Chỉ số giá Nhà Tốt)| 🔶 Có (Khoảng giá phổ biến) | **RẤT LỚN**: Hoàn toàn thiếu cơ sở dữ liệu chuỗi thời gian về giá. |
| 6 | Định giá tự động (AVM) | ❌ Chưa có | ✅ Có (AI Valuation Engine) | 🔶 Có (Gợi ý giá bán) | ❌ Chưa có | **LỚN**: Chưa có thuật toán ước lượng giá thị trường theo m². |
| **II**| **TRẢI NGHIỆM NGƯỜI DÙNG** | | | | | |
| 7 | Lưu tin yêu thích (Favorites) | ❌ Chưa có | ✅ Có (Sync Cloud + Local) | ✅ Có (Sync tức thì) | ✅ Có (Lưu nhanh) | **RẤT LỚN**: Tính năng giữ chân người dùng cốt lõi nhất đang bị khuyết. |
| 8 | Lưu bộ lọc & Nhận Smart Alerts | ❌ Chưa có | ✅ Có (Email / App Push) | ✅ Có (Thông báo tin mới) | 🔶 Có (Newsletter) | **LỚN**: Chưa thể theo dõi phân khúc tìm kiếm tự động cho khách hàng. |
| 9 | Chia sẻ tin mạng xã hội | ✅ Có (WebShare, FB, Zalo)| ✅ Có (Đầy đủ) | ✅ Có (Đầy đủ) | ✅ Có (Đầy đủ) | **NHỎ**: Đã có nền tảng tốt, cần thêm QR Code và ảnh Infographic. |
| 10| 360° Virtual Tour / Panorama | ❌ Chưa có | ✅ Có (Matterport / 360) | 🔶 Có (Ảnh 360) | ✅ Có (3D Tour) | **TRUNG BÌNH**: Mới có gallery ảnh và video YouTube. |
| 11| Đánh giá & Bình luận (Reviews)| ❌ Chưa có | 🔶 Có (Hỏi đáp chuyên gia) | 🔶 Có (Review người bán) | ✅ Có (Review dự án) | **TRUNG BÌNH**: Chưa có nội dung do người dùng đóng góp (UGC). |
| **III**| **MÔI GIỚI & THÀNH VIÊN** | | | | | |
| 12| Đăng tin phía Frontend | ❌ Chưa có | ✅ Có (Form chuẩn 5 bước) | ✅ Có (Đăng tin siêu tốc) | ✅ Có (Form frontend) | **CỰC KỲ LỚN**: Chỉ Quản trị viên mới đăng được bài qua WP-Admin. |
| 13| Dashboard quản lý tin cá nhân| ❌ Chưa có | ✅ Có (Quản lý chuyên sâu) | ✅ Có (Đa trạng thái) | ✅ Có (Dashboard đối tác) | **CỰC KỲ LỚN**: Môi giới không có công cụ tự quản lý sản phẩm. |
| 14| Phân cấp Tin VIP (Monetization)| 🔶 Có 1 cờ `featured` | ✅ Có (Kim Cương/Vàng/Bạc) | ✅ Có (Đẩy tin/Ưu tiên) | ✅ Có (VIP 1/2/3) | **LỚN**: Chưa có cơ chế phân tầng thương mại hóa và ưu tiên tin tốt. |
| 15| Hồ sơ môi giới & Huy hiệu uy tín| ❌ Chưa có | ✅ Có (Môi giới chuyên nghiệp)| ✅ Có (Đã xác thực CCCD) | 🔶 Có (Agent Pro) | **LỚN**: Mới có 1 hồ sơ advisor cứng lưu trong ACF Options. |
| 16| Danh bạ môi giới theo khu vực| ❌ Chưa có | ✅ Có (Bộ lọc toàn quốc) | ❌ Chưa có | ✅ Có (Hồ sơ chuyên viên) | **LỚN**: Thiếu trang kết nối người mua với môi giới địa phương. |
| 17| Nhắn tin trực tiếp (Live Chat)| ❌ Chưa có | 🔶 Có (Chat nội bộ) | ✅ Có (Chat tức thì) | ❌ Chưa có | **TRUNG BÌNH**: Hiện chỉ có nút bấm Gọi điện thoại và Chat Zalo. |
| **IV**| **SEO & DỮ LIỆU CẤU TRÚC** | | | | | |
| 18| Schema RealEstateListing | ❌ Chưa có | ✅ Có (JSON-LD chuẩn) | ✅ Có (Product/Offer) | ✅ Có (RealEstateListing) | **CỰC KỲ LỚN**: Theme chưa xuất bất kỳ thẻ JSON-LD nào cho BĐS. |
| 19| Schema BreadcrumbList | 🔶 Có HTML, thiếu JSON-LD| ✅ Có (JSON-LD chuẩn) | ✅ Có (JSON-LD chuẩn) | ✅ Có (JSON-LD chuẩn) | **DỄ KHẮC PHỤC**: Cần bọc JSON-LD trong hook `wp_head`. |
| 20| Schema FAQPage JSON-LD | ❌ Chưa có | ✅ Có (Rich Results) | ❌ Chưa có | ✅ Có | **DỄ KHẮC PHỤC**: Đã có nội dung câu hỏi nhưng thiếu mã đánh dấu. |
| 21| Landing Pages bộ lọc chuẩn SEO| 🔶 Có URL cơ bản | ✅ Có (Ma trận 50,000+ URLs)| 🔶 Có (Chuyên mục quận) | ✅ Có (Tag landing) | **TRUNG BÌNH**: Cần tự động hóa Title, H1, Canonical và văn bản intro. |

---

### 2.3. Đánh giá Khoảng cách Trọng yếu (Critical Gap Analysis)

Từ ma trận đối chuẩn, codebase BatDongSan bộc lộ **4 lỗ hổng chiến lược** cần được ưu tiên xử lý:
1. **Lỗ hổng Giữ chân (Retention Deficit)**: Thiếu vắng "Lưu tin yêu thích" và "Lưu bộ lọc tìm kiếm". Người dùng tìm kiếm xong và rời đi mà không để lại bất kỳ dấu vết tương tác nào, khiến tỷ lệ quay lại trang (Return Rate) rất thấp.
2. **Lỗ hổng Nguồn cung & Môi giới (Supply & Broker Deficit)**: Việc đóng kín luồng đăng bài trong WP-Admin khiến nguồn cung phụ thuộc 100% vào đội ngũ nội bộ. Không thể mở rộng quy mô lên hàng chục ngàn tin nếu không có Form đăng tin Frontend và Dashboard quản lý cho môi giới.
3. **Lỗ hổng Tối ưu Tìm kiếm (SEO Snippet Deficit)**: Mặc dù đã có URL rewrite thân thiện, việc thiếu hụt hoàn toàn thẻ Schema Structured Data JSON-LD (`RealEstateListing`, `BreadcrumbList`, `FAQPage`) khiến website không thể hiển thị giá bán, số phòng, trạng thái và mẩu vụn bánh mì trên kết quả Google Search.
4. **Lỗ hổng Thương mại hóa (Monetization Deficit)**: Chỉ có 1 cờ `featured` đơn giản, không có hệ thống gói tin VIP đa cấp độ có thời hạn để tạo dòng tiền kinh doanh.

---

## 3. KHUNG PHÂN KỲ LỘ TRÌNH (PRIORITIZATION FRAMEWORK: P0 / P1 / P2)

Lộ trình phát triển được phân chia theo mô hình ma trận ưu tiên (Impact vs. Effort):

```
+---------------------------------------------------------------------------------------------------+
|                        MA TRẬN PHÂN KỲ LỘ TRÌNH TÍNH NĂNG CỔNG THÔNG TIN                          |
+---------------------------------------------------------------------------------------------------+
|                                                                                                   |
|  [P0: MUST-HAVE] (Tác động Cực lớn - Thực hiện Ngay - Q1)                                         |
|  ├── D4.1. Hệ thống Structured Data Schema.org (RealEstateListing, BreadcrumbList, FAQPage)       |
|  ├── D2.1. Tính năng Lưu tin yêu thích (Favorites) - LocalStorage + User DB Sync                 |
|  ├── D4.2. Ma trận SEO Filter Landing Pages (Tự động sinh Title, H1, Meta, Canonical)             |
|  ├── D1.1. Hoàn thiện Bảng lịch trả nợ chi tiết (Amortization Schedule) cho Công cụ Tính Lãi Vay |
|  └── D3.1. Hệ thống Phân cấp Tin VIP 4 cấp độ (Diamond, Gold, Silver, Normal)                     |
|                                                                                                   |
|  [P1: SHOULD-HAVE] (Tác động Lớn - Mở rộng Tương tác & Nguồn cung - Q2)                           |
|  ├── D3.2. Form Đăng tin BĐS phía Frontend đa bước (Multi-step Submission Form)                   |
|  ├── D3.3. Dashboard Quản lý tin đăng cá nhân cho Môi giới (Member Dashboard)                     |
|  ├── D2.2. Lưu bộ lọc tìm kiếm & Nhận thông báo tự động (Saved Searches & Smart Alerts)           |
|  ├── D1.2. Công cụ So sánh BĐS tương đồng Side-by-Side (Drawer & Compare Page)                    |
|  ├── D3.4. Hồ sơ Chuyên viên Môi giới & Huy hiệu Xác thực Uy tín (Verified Agent Badges)          |
|  └── D2.3. Tạo ảnh thẻ BĐS chia sẻ mạng xã hội (Infographic Card Generator) & Mã QR              |
|                                                                                                   |
|  [P2: NICE-TO-HAVE] (Đột phá Công nghệ - Xây dựng Lợi thế Cạnh tranh Dài hạn - Q3/Q4)             |
|  ├── D1.3. Bản đồ Tích hợp Quy hoạch Đất đai & Tìm kiếm Bounding Box                              |
|  ├── D1.4. Biểu đồ Biến động Lịch sử Giá & Chỉ số Khu vực                                         |
|  ├── D1.5. Công cụ Định giá Bất động sản Tự động (Automated Valuation Model - AVM)                |
|  ├── D2.4. Trình xem 360° Virtual Tour / Mặt bằng 3D Panorama (Pannellum JS)                      |
|  └── D3.5. Kênh Nhắn tin Trực tiếp Khách hàng - Môi giới (Live Chat / Zalo Gateway)               |
|                                                                                                   |
+---------------------------------------------------------------------------------------------------+
```

---

## 4. MIỀN 1: CÔNG CỤ & TIỆN ÍCH (TOOLS & UTILITIES)

### Feature 1.1: Bảng Tính Lãi Suất Nâng Cao & Kế Hoạch Khấu Hao Nợ (Amortization Schedule) [MUST-HAVE / P0]
- **Mô tả nghiệp vụ**: Hiện tại `loan-calculator.php` chỉ hiển thị các con số tổng quát (số tiền vay, lãi tháng đầu, tổng lãi). Cần mở rộng bảng tính chi tiết từng kỳ trả nợ (tháng 1 đến tháng 360) theo 2 phương thức: Dư nợ giảm dần và Gốc chia đều. Hỗ trợ thêm trường "Thời gian ưu đãi lãi suất" (ví dụ: 0% hoặc 6.5% trong 12 tháng đầu). Cung cấp nút CTA "Nhận hồ sơ tư vấn gói vay ngân hàng tối ưu".
- **Giá trị người dùng & Kinh doanh**: Giúp người mua nhà chủ động lên kế hoạch dòng tiền; đồng thời mở ra nguồn thu Lead tài chính (Mortgage Lead) có giá trị cao cho sàn liên kết ngân hàng.
- **Quy chuẩn UI/UX**:
  - Bổ sung nút chuyển đổi tab "Xem lịch trả nợ chi tiết" dưới kết quả tính tổng.
  - Bảng cuộn responsive: Cột Kỳ trả (Tháng), Gốc phải trả, Lãi phải trả, Tổng số tiền trả, Dư nợ còn lại.
  - Tích hợp nút "Xuất file Excel" (đã có sẵn thư viện XLSX).
- **Blueprint Kỹ thuật**:
  - Không cần gọi database. Tính toán hoàn toàn bằng JavaScript ở client và cung cấp endpoint REST API stateless dự phòng `/wp-json/bds/v1/tools/amortization`.
  - Công thức tính Dư nợ giảm dần:
    $$Gốc_{hàng tháng} = \frac{Tổng Vay}{Số Tháng}$$
    $$Lãi_{tháng i} = Dư Nợ_{tháng i-1} \times \frac{Lãi Suất Năm}{12}$$
  - Tích hợp biểu mẫu Lead: Khi người dùng nhấp "Đăng ký tư vấn gói vay", form tự động đính kèm thông tin: Tên BĐS, Số tiền vay dự kiến, Thời hạn vay, Ngân hàng lựa chọn.

---

### Feature 1.2: Công Cụ So Sánh Bất Động Sản Tương Đồng Side-by-Side [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**: Cho phép khách hàng chọn tối đa 4 bất động sản để so sánh thông số đối chiếu trực tiếp trên cùng một màn hình (giá tổng, đơn giá/m², diện tích thông thủy, số phòng ngủ, số WC, pháp lý, hướng nhà, dự án).
- **Giá trị người dùng & Kinh doanh**: Rút ngắn thời gian đắn đo của người mua, tăng tỷ lệ nhấp liên hệ tư vấn thêm 35%.
- **Quy chuẩn UI/UX**:
  - Biểu tượng cân so sánh (Compare icon) trên mỗi thẻ bài viết và trang chi tiết.
  - Khi có ít nhất 1 BĐS được chọn, thanh Khay so sánh (Floating Comparison Bar) xuất hiện trượt từ dưới đáy màn hình lên, hiển thị thumbnail các căn đã chọn và nút "So sánh ngay (X/4)".
  - Trang so sánh chuyên biệt `/so-sanh/?ids=101,102,103`: Bảng đối chiếu dạng lưới ma trận với hàng đầu tiên cố định (Sticky header) chứa ảnh và giá. Các điểm khác biệt nổi bật được tô màu vàng nhẹ.
- **Blueprint Kỹ thuật**:
  - Client-side: Lưu mảng IDs `[101, 102, 103]` trong `sessionStorage` để không làm chậm server.
  - Endpoint REST: `GET /wp-json/bds/v1/tools/compare?ids=101,102,103`
  - Backend: Sử dụng `get_posts(['post__in' => $ids, 'post_type' => 'bat-dong-san'])`, nạp cache bằng `update_post_caches()`, trả về JSON cấu trúc chuẩn gồm 15 trường thuộc tính so sánh.

---

### Feature 1.3: Bản Đồ Quy Hoạch Đất Đai & Tìm Kiếm Theo Bounding Box [NICE-TO-HAVE / P2]
- **Mô tả nghiệp vụ**: Tích hợp lớp bản đồ quy hoạch sử dụng đất (Quy hoạch phân khu đô thị Hà Nội) đè lên bản đồ vệ tinh / giao thông. Hỗ trợ người dùng khoanh vùng tìm kiếm BĐS trên bản đồ (Bounding Box / Radius search).
- **Giá trị người dùng & Kinh doanh**: Giải quyết nỗi sợ hãi lớn nhất của người mua nhà đất tại Việt Nam (mua phải đất dính quy hoạch treo, đất cây xanh, chỉ giới đường đỏ).
- **Quy chuẩn UI/UX**:
  - Thanh trượt điều chỉnh độ trong suốt (Opacity slider: 0% - 100%) giữa lớp bản đồ giao thông thực tế và lớp bản đồ quy hoạch màu sắc.
  - Marker clustering: Gom cụm các ghim BĐS gần nhau thành số lượng cụm tròn, phóng to khi click.
- **Blueprint Kỹ thuật**:
  - Sử dụng thư viện `Leaflet.js` hoặc `Mapbox GL JS`.
  - Tích hợp lớp bản đồ WMS/TMS từ dịch vụ bản đồ quy hoạch đối tác (hoặc Tile layer tự lưu trữ).
  - Endpoint tìm kiếm theo toạ độ: `GET /wp-json/bds/v1/listings/map?north=21.05&south=21.01&east=105.85&west=105.78`.
  - SQL Spatial query: Sử dụng kiểu dữ liệu `Point` hoặc truy vấn tọa độ `lat BETWEEN south AND north AND lng BETWEEN west AND east`.

---

### Feature 1.4: Biểu Đồ Biến Động Lịch Sử Giá & Chỉ Số Khu Vực [NICE-TO-HAVE / P2]
- **Mô tả nghiệp vụ**: Thu thập và trực quan hóa dữ liệu biến động đơn giá (triệu/m²) của từng dự án và từng phân khúc quận/huyện theo chuỗi thời gian (các quý trong 3 năm gần nhất).
- **Giá trị người dùng & Kinh doanh**: Tạo niềm tin minh bạch thị trường, định vị portal là nguồn tin cậy về dữ liệu bất động sản (Market Intelligence).
- **Quy chuẩn UI/UX**:
  - Biểu đồ đường (Line Chart) trên trang chi tiết dự án và BĐS, cho phép chuyển đổi xem: "Giá bán căn hộ theo quý", "So sánh với mức bình quân quận Cầu Giấy".
- **Blueprint Kỹ thuật**:
  - Bảng cơ sở dữ liệu `wp_bds_price_history`:
    `CREATE TABLE wp_bds_price_history (id BIGINT AUTO_INCREMENT PRIMARY KEY, project_id BIGINT, district_slug VARCHAR(50), avg_price_m2 DECIMAL(10,2), min_price_m2 DECIMAL(10,2), max_price_m2 DECIMAL(10,2), period_quarter VARCHAR(10), period_year SMALLINT);`
  - Cronjob hàng quý tổng hợp giá trung bình từ các tin đăng hợp lệ trong kỳ.
  - Render frontend bằng `Chart.js` hoặc SVG vector tinh gọn.

---

### Feature 1.5: Công Cụ Định Giá Bất Động Sản Tự Động (Automated Valuation Model - AVM) [NICE-TO-HAVE / P2]
- **Mô tả nghiệp vụ**: Người dùng nhập thông số nhà đất (Quận/Huyện, Tên đường, Loại hình, Diện tích, Mặt tiền, Số tầng, Độ rộng ngõ vào). Thuật toán ước lượng mức giá thị trường hợp lý (VD: "Khoảng giá ước tính: 4.6 tỷ - 5.1 tỷ VNĐ").
- **Giá trị người dùng & Kinh doanh**: Thu hút lượng lớn chủ nhà muốn bán nhà vào định giá thử, chuyển đổi họ thành người đăng tin bán nhà.
- **Blueprint Kỹ thuật**:
  - Mô hình định giá hồi quy Hedonic (Hedonic Pricing Model) dựa trên cơ sở dữ liệu giao dịch và tin đăng thực tế trong bán kính 1km:
    $$P_{ước\_tính} = S \times Đơn\_Giá_{khu\_vực} \times K_{mặt\_tiền} \times K_{ngõ} \times K_{hướng}$$

---

## 5. MIỀN 2: TRẢI NGHIỆM NGƯỜI DÙNG & GIỮ CHÂN (USER EXPERIENCE & RETENTION)

### Feature 2.1: Lưu Tin Yêu Thích (Favorite / Wishlist) [MUST-HAVE / P0]
- **Mô tả nghiệp vụ**: Khách hàng bấm nút trái tim để lưu các căn nhà ưng ý vào danh sách theo dõi.
  - Khách vãng lai (Guest): Dữ liệu lưu trong `localStorage`.
  - Thành viên đã đăng nhập: Đồng bộ tự động lên cơ sở dữ liệu máy chủ.
  - Khi đăng nhập từ tài khoản khách, hệ thống tự động gộp (merge) danh sách từ `localStorage` vào tài khoản người dùng.
- **Giá trị người dùng & Kinh doanh**: Tính năng cốt lõi bắt buộc của mọi sàn giao dịch; giúp người dùng dễ dàng so sánh và quay lại liên hệ sau.
- **Quy chuẩn UI/UX**:
  - Biểu tượng trái tim viền đen/xám, chuyển sang màu đỏ rực kèm hiệu ứng nảy (heart pop animation) khi được bấm.
  - Số lượng tin đã lưu hiển thị dạng badge đỏ trên thanh điều hướng đầu trang (Header icon).
  - Trang quản lý `/tin-da-luu/`: Liệt kê các thẻ BĐS đã lưu, có nút "Xóa khỏi danh sách", "Xóa tất cả", và cảnh báo chữ mờ gạch ngang nếu căn nhà đó đã chuyển trạng thái sang `da_ban`.
- **Blueprint Kỹ thuật**:
  - Bảng cơ sở dữ liệu:
    ```sql
    CREATE TABLE IF NOT EXISTS `wp_bds_user_favorites` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `listing_id` BIGINT UNSIGNED NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_user_listing` (`user_id`, `listing_id`),
      KEY `idx_user` (`user_id`),
      KEY `idx_listing` (`listing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ```
  - Endpoints REST API:
    - `POST /wp-json/bds/v1/user/favorites/toggle`: Nhận `{ listing_id: 123 }`. Kiểm tra Nonce, thêm hoặc xóa bản ghi trong bảng.
    - `GET /wp-json/bds/v1/user/favorites`: Trả về danh sách IDs đã lưu của user.
    - `POST /wp-json/bds/v1/user/favorites/sync`: Gộp mảng IDs từ `localStorage` lên server khi người dùng đăng nhập.

---

### Feature 2.2: Lưu Bộ Lọc Tìm Kiếm & Nhận Thông Báo Thông Minh (Smart Alerts) [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**: Khi người dùng lọc tìm kiếm một phân khúc cụ thể (Ví dụ: "Căn hộ Cầu Giấy từ 3 - 5 tỷ"), xuất hiện nút "Đăng ký nhận thông báo tin mới". Người dùng nhập Email hoặc Số điện thoại. Khi có tin đăng mới được xuất bản khớp hoàn toàn với tiêu chí này, hệ thống sẽ tự động gửi email hoặc tin nhắn Zalo thông báo.
- **Giá trị người dùng & Kinh doanh**: Tự động hóa tiếp thị (Marketing Automation), biến người truy cập thụ động thành khách hàng tiềm năng nhận tin chủ động hàng ngày.
- **Quy chuẩn UI/UX**:
  - Nút bấm có biểu tượng chuông: "Theo dõi tìm kiếm này" nằm ngay trên thanh đếm kết quả lọc.
  - Modal bật lên hiển thị tóm tắt các tiêu chí đã chọn, ô nhập Email/SĐT và lựa chọn tần suất: "Ngay khi có tin mới" hoặc "Bản tin tổng hợp mỗi sáng".
- **Blueprint Kỹ thuật**:
  - Bảng cơ sở dữ liệu `wp_bds_saved_searches`:
    ```sql
    CREATE TABLE IF NOT EXISTS `wp_bds_saved_searches` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED DEFAULT NULL,
      `email` VARCHAR(100) NOT NULL,
      `phone` VARCHAR(20) DEFAULT NULL,
      `criteria_json` TEXT NOT NULL,
      `frequency` VARCHAR(20) NOT NULL DEFAULT 'daily',
      `last_notified_at` DATETIME DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_frequency` (`frequency`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ```
  - Cronjob hàng ngày `batdongsan_send_daily_saved_search_alerts`: Quét các tin BĐS xuất bản trong 24 giờ qua và đối chiếu với `criteria_json` để gửi email tổng hợp qua `wp_mail()`.

---

### Feature 2.3: Tạo Ảnh Thẻ BĐS Chia Sẻ Mạng Xã Hội (Infographic Card) & Mã QR Code [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**: Cung cấp nút "Tải ảnh BĐS chia sẻ": Hệ thống tự động xuất một tấm ảnh infographic định dạng chuẩn 1200x630 (Facebook) hoặc 1080x1920 (Story/Zalo). Tấm ảnh kết hợp: Ảnh đại diện BĐS, Logo sàn, Tiêu đề, Mức giá đỏ nổi bật, Diện tích, Địa chỉ, Mã QR Code dẫn thẳng về link bài viết và Thông tin liên hệ của môi giới đăng tin.
- **Giá trị người dùng & Kinh doanh**: Giúp môi giới chia sẻ bài viết lên các hội nhóm Facebook/Zalo một cách chuyên nghiệp chỉ bằng 1 cú nhấp chuột; quảng bá thương hiệu portal lan tỏa tự nhiên (Viral branding).
- **Blueprint Kỹ thuật**:
  - Thư viện HTML5 Canvas phía client (hoặc endpoint PHP GD/Imagick phía server): Vẽ trực tiếp ảnh, chèn watermark logo và thông tin text.
  - Tạo QR Code tĩnh: Sử dụng thư viện JS nhẹ `qrcode.min.js` hoặc endpoint nội bộ.
  - Nút tải trực tiếp định dạng `.png` về điện thoại/máy tính.

---

### Feature 2.4: Trình Xem Ảnh 360° Panorama & Virtual Tour [NICE-TO-HAVE / P2]
- **Mô tả nghiệp vụ**: Cho phép tin đăng BĐS cao cấp tải lên ảnh Panorama 360 độ hoặc nhúng đường dẫn Matterport/Kuula 3D. Người xem có thể tương tác xoay góc nhìn 360 độ căn phòng trực tiếp trên điện thoại bằng thao tác vuốt hoặc cảm biến con quay hồi chuyển (Gyroscope).
- **Giá trị người dùng & Kinh doanh**: Tăng tính chuyên nghiệp, thu hút phân khúc khách hàng mua BĐS cao cấp, biệt thự, penthouse.
- **Blueprint Kỹ thuật**:
  - Tích hợp thư viện WebGL mã nguồn mở cực nhẹ: `Pannellum` (chỉ ~15KB gzipped, không phụ thuộc nặng nề).
  - Áp dụng mô hình Facade (tương tự `map-facade.php`): Chỉ tải thư viện Pannellum và nạp ảnh 360 khi người dùng bấm vào nút "Xem không gian 360°".

---

## 6. MIỀN 3: HỆ SINH THÁI MÔI GIỚI & THÀNH VIÊN (BROKER & MEMBER ECOSYSTEM)

### Feature 3.1: Hệ Thống Phân Cấp Tin VIP & Thương Mại Hóa (Monetization Engine) [MUST-HAVE / P0]
- **Mô tả nghiệp vụ**: Thay thế cờ đơn lẻ `featured` (0/1) bằng hệ thống 4 cấp độ tin đăng chuẩn thị trường:
  1. **VIP Kim Cương (Diamond - Level 3)**: Tiêu đề chữ in hoa màu đỏ đậm, khung viền vàng nổi bật có huy hiệu vương miện, hiển thị cố định ở đầu danh sách trang chủ và trang danh mục, tự động làm mới vị trí mỗi ngày.
  2. **VIP Vàng (Gold - Level 2)**: Tiêu đề màu cam đậm, có viền cam nổi bật, hiển thị ngay dưới VIP Kim Cương.
  3. **VIP Bạc (Silver - Level 1)**: Tiêu đề màu xanh đậm, có huy hiệu "Tin nổi bật" nhỏ.
  4. **Tin Thường (Normal - Level 0)**: Hiển thị tuần tự theo thứ tự thời gian xuất bản.
  - Quản lý thời hạn hiển thị VIP: Trường ngày bắt đầu `vip_start_date` và ngày kết thúc `vip_end_date`. Khi hết hạn VIP, tin tự động hạ cấp về Tin Thường mà không bị xóa.
- **Giá trị người dùng & Kinh doanh**: Xây dựng mô hình kinh doanh tạo dòng tiền cốt lõi (Monetization Model) cho portal thông qua việc bán gói đăng tin và đẩy tin (Up top).
- **Blueprint Kỹ thuật**:
  - Bổ sung các meta keys: `vip_tier` (0, 1, 2, 3), `vip_expires_at` (DATETIME).
  - Sắp xếp trong SQL Query:
    ```sql
    ORDER BY 
      CASE WHEN vip_expires_at >= NOW() THEN vip_tier ELSE 0 END DESC, 
      post_date DESC
    ```
  - Cronjob hàng đêm kiểm tra và gửi email nhắc nhở môi giới gia hạn khi tin VIP sắp hết hạn trong 24 giờ.

---

### Feature 3.2: Quy Trình Đăng Tin Phía Frontend Đa Bước (Multi-step Form) [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**: Biểu mẫu đăng tin trực quan phía Frontend dành cho thành viên / môi giới đăng nhập, chia thành 5 bước chuẩn UX:
  - **Bước 1: Hình thức giao dịch & Loại hình**: Chọn Bán / Cho thuê; chọn loại hình BĐS.
  - **Bước 2: Vị trí BĐS**: Tỉnh/thành phố, Quận/huyện, Phường/xã, Đường/phố, Chọn Dự án liên quan (Autocomplete ô tìm kiếm dự án).
  - **Bước 3: Thông số chi tiết**: Diện tích, Giá bán (tự động diễn giải ra chữ tiếng Việt: gõ `3500` -> hiện `"3 tỷ 500 triệu"`), Hướng nhà, Pháp lý, Số PN, Số WC, Số tầng, Mặt tiền.
  - **Bước 4: Tiêu đề, Mô tả & Đăng ảnh**: Kéo thả tối đa 15 hình ảnh, tự động nén ảnh WebP phía client trước khi upload.
  - **Bước 5: Xem trước & Xuất bản**: Xem trước bản hiển thị card và bài viết đơn trước khi gửi duyệt.
- **Giá trị người dùng & Kinh doanh**: Mở rộng nguồn cung bài đăng từ xã hội hóa cộng đồng môi giới, giải phóng sức lao động của quản trị viên website.
- **Blueprint Kỹ thuật**:
  - Nén ảnh client-side bằng `Compressor.js` trước khi gửi lên máy chủ (giảm 85% băng thông tải lên).
  - Endpoint REST API: `POST /wp-json/bds/v1/listings/submit` (Yêu cầu xác thực `is_user_logged_in()`, kiểm tra Nonce `X-WP-Nonce`, sanitize toàn bộ dữ liệu).
  - Phân quyền: Người dùng mới đăng bài sẽ ở trạng thái chờ duyệt `post_status => 'pending'`. Môi giới đã xác thực uy tín được tự động xuất bản `post_status => 'publish'`.

---

### Feature 3.3: Bảng Quản Lý Tin Cá Nhân Cho Môi Giới (Member Dashboard) [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**: Trang quản lý dành riêng cho môi giới đăng nhập tại URL `/tai-khoan/quan-ly-tin/`:
  - Quản lý theo tab trạng thái: "Đang hiển thị (Active)", "Chờ kiểm duyệt (Pending)", "Hết hạn (Expired)", "Bản nháp / Đã bán (Draft)".
  - Các thao tác nhanh: Nút "Đẩy tin (Up top)", "Sửa tin", "Đổi trạng thái: Đã bán", "Hạ giá bán", "Gia hạn tin".
  - Thống kê hiệu quả tin đăng: Lượt xem bài (Views), Lượt click xem số điện thoại, Lượt gửi biểu mẫu liên hệ.
- **Blueprint Kỹ thuật**:
  - Kiểm tra quyền sở hữu nghiêm ngặt: Môi giới chỉ có thể xem và sửa bài viết có `post_author === get_current_user_id()`.
  - AJAX Action đổi trạng thái nhanh: `batdongsan_toggle_listing_status` trả về kết quả tức thì không cần tải lại trang.

---

### Feature 3.4: Hồ Sơ Môi Giới Chuyên Nghiệp & Huy Hiệu Xác Thực (Verified Badges) [SHOULD-HAVE / P1]
- **Mô tả nghiệp vụ**:
  - Mở rộng hồ sơ cá nhân của người dùng thành Trang Môi giới Chuyên nghiệp: Ảnh đại diện chân dung, Họ tên, Số điện thoại, Link Zalo, Công ty phân phối, Khu vực hoạt động chuyên sâu (VD: "Chuyên BĐS Quận Cầu Giấy & Nam Từ Liêm"), Danh sách tất cả các tin BĐS đang bán của môi giới này.
  - Huy hiệu "Môi giới đã xác thực" (Verified Agent Badge): Được cấp khi môi giới gửi ảnh chụp CCCD hoặc Chứng chỉ hành nghề môi giới BĐS và được Ban quản trị phê duyệt.
  - Trang Danh bạ Môi giới toàn trang `/danh-ba-moi-gioi/`: Cho phép khách hàng tìm kiếm môi giới uy tín theo từng quận huyện hoặc theo từng dự án lớn.
- **Giá trị người dùng & Kinh doanh**: Tạo dựng môi trường giao dịch minh bạch, đẩy lùi tin đăng ảo và nạn lừa đảo mạo danh.
- **Blueprint Kỹ thuật**:
  - Bổ sung user meta: `bds_phone`, `bds_zalo`, `bds_agency`, `bds_specialty_districts`, `bds_is_verified` (1/0), `bds_id_card_verified_at`.
  - Template trang hồ sơ: `author.php` tùy biến hiển thị giao diện Profile môi giới và lưới bài đăng đang bán.

---

### Feature 3.5: Kênh Nhắn Tin Trực Tiếp Khách Hàng - Môi Giới (Live Chat / Zalo Gateway) [NICE-TO-HAVE / P2]
- **Mô tả nghiệp vụ**: Nút "Nhắn tin Zalo ngay" và "Chat với người bán" kích hoạt liên kết sâu (Deep Link) mở ứng dụng Zalo trên điện thoại hoặc mở khung Zalo Web trên máy tính, tự động điền sẵn nội dung lời chào kèm link bài viết mà khách đang xem:
  *"Xin chào, tôi đang quan tâm đến căn nhà này: [URL BĐS], vui lòng gửi thêm thông tin chi tiết."*
- **Blueprint Kỹ thuật**:
  - Đường dẫn Deep Link: `https://zalo.me/{phone}?text={encoded_message}`.

---

## 7. MIỀN 4: SEO & TÍNH TOÀN VẸN DỮ LIỆU (SEO & DATA INTEGRITY)

### Feature 4.1: Hệ Thống Structured Data Schema.org (JSON-LD) Chuẩn Google [MUST-HAVE / P0]
- **Mô tả nghiệp vụ**: Xuất tự động thẻ `<script type="application/ld+json">` chuẩn hóa cao nhất cho từng loại trang, giúp website đủ điều kiện hiển thị Rich Snippets (Giá, Vị trí, Số phòng, FAQ, Breadcrumbs) trên kết quả tìm kiếm Google.

#### 1. Schema Cho Chi Tiết Bất Động Sản (`single-bat-dong-san.php`):
```json
{
  "@context": "https://schema.org",
  "@type": "RealEstateListing",
  "name": "Bán căn hộ chung cư cao cấp 3PN Cầu Giấy",
  "description": "Căn hộ tầng trung view công viên, đầy đủ nội thất cao cấp...",
  "url": "https://batdongsanhanoi.vn/bat-dong-san/can-ho-cau-giay-123/",
  "datePosted": "2026-10-01T08:00:00+07:00",
  "image": [
    "https://batdongsanhanoi.vn/wp-content/uploads/anh-1.jpg"
  ],
  "mainEntity": {
    "@type": "Apartment",
    "name": "Căn hộ 3PN Cầu Giấy",
    "numberOfRooms": 3,
    "numberOfBedrooms": 3,
    "numberOfBathroomsTotal": 2,
    "floorSize": {
      "@type": "QuantitativeValue",
      "value": 115,
      "unitCode": "MTK"
    },
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Đường Duy Tân",
      "addressLocality": "Cầu Giấy",
      "addressRegion": "Hà Nội",
      "addressCountry": "VN"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 21.0345,
      "longitude": 105.7891
    }
  },
  "offers": {
    "@type": "Offer",
    "price": 5200000000,
    "priceCurrency": "VND",
    "availability": "https://schema.org/InStock",
    "validThrough": "2026-12-31"
  }
}
```

#### 2. Schema Điều Hướng Breadcrumbs (`BreadcrumbList`):
Xuất trên tất cả các trang có breadcrumbs:
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Trang chủ",
      "item": "https://batdongsanhanoi.vn/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Bán căn hộ chung cư",
      "item": "https://batdongsanhanoi.vn/ban-can-ho-chung-cu/"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Quận Cầu Giấy",
      "item": "https://batdongsanhanoi.vn/ban-can-ho-chung-cu-quan-cau-giay/"
    }
  ]
}
```

#### 3. Schema Câu Hỏi Thường Gặp (`FAQPage`):
Chuyển đổi tự động từ trường repeater `cau_hoi_thuong_gap` trên trang Dự án và Bài viết:
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Dự án có sổ hồng lâu dài không?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Dự án có pháp lý hoàn chỉnh, sở hữu sổ hồng lâu dài cho người Việt Nam."
      }
    }
  ]
}
```

- **Triển khai kỹ thuật**: Bọc toàn bộ logic sinh JSON-LD vào một file helper `inc/helpers/schema.php` và móc vào action hook `wp_head` với độ ưu tiên cao (`priority: 2`).

---

### Feature 4.2: Ma Trận Landing Page Bộ Lọc Tự Động Chuẩn SEO (SEO Filter Pages Matrix) [MUST-HAVE / P0]
- **Mô tả nghiệp vụ**: Tự động chuyển đổi các tổ hợp bộ lọc phổ biến thành các trang đích SEO hoàn chỉnh để chiếm lĩnh từ khóa tìm kiếm dài (Long-tail keywords):
  - Ví dụ tổ hợp: `Loại hình (Căn hộ)` + `Quận (Cầu Giấy)` + `Khoảng giá (3-5 tỷ)`.
  - URL chuẩn SEO: `/ban-can-ho-chung-cu-quan-cau-giay/gia-tu-3-ty-den-5-ty/`
  - Thẻ `<title>` tự động: *"Bán căn hộ chung cư Cầu Giấy giá từ 3 - 5 tỷ mới nhất 2026"*
  - Thẻ `<h1>` duy nhất: *"Bán căn hộ chung cư Cầu Giấy từ 3 đến 5 tỷ"*
  - Thẻ `<meta name="description">`: *"Tổng hợp danh sách các căn hộ chung cư tại Quận Cầu Giấy mức giá 3 đến 5 tỷ. Đầy đủ sổ đỏ, vị trí đẹp, cập nhật liên tục hàng ngày."*
  - Khối văn bản giới thiệu thị trường chân trang (SEO Intro Text): Cho phép biên tập viên tùy biến nội dung phân tích tổng quan cho từng quận huyện.
  - Khối liên kết nội bộ chéo (Cross-linking Cloud): Đề xuất các từ khóa lân cận: *"Chung cư Cầu Giấy dưới 3 tỷ"*, *"Chung cư Nam Từ Liêm 3 - 5 tỷ"*, *"Nhà riêng Cầu Giấy 3 - 5 tỷ"*.
  - Thẻ `<link rel="canonical">`: Trỏ chính xác về URL chuẩn; tự động gắn `noindex` đối với các bộ lọc phân trang sâu hoặc tổ hợp quá 4 tiêu chí để tránh hiện tượng loãng chỉ mục (Index bloat).

---

## 8. KIẾN TRÚC KỸ THUẬT NỀN TẢNG (UNDERLYING TECHNICAL BLUEPRINT)

### 8.1. Thiết Kế Cơ Sở Dữ Liệu Bổ Trợ (Database Schema Extensions)

Để hỗ trợ đầy đủ các tính năng trong lộ trình mà không làm chậm hệ thống, chúng ta thiết lập 4 bảng dữ liệu chuyên biệt:

```
+----------------------------------------------------------------------------------------------------+
|                                CƠ SỞ DỮ LIỆU BỔ TRỢ HỆ SINH THÁI BĐS                               |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  1. wp_bds_user_favorites                                                                          |
|     ├── id (BIGINT PK AI)                                                                          |
|     ├── user_id (BIGINT INDEX)                                                                     |
|     ├── listing_id (BIGINT INDEX)                                                                  |
|     └── created_at (DATETIME)                                                                      |
|     └── UNIQUE KEY (user_id, listing_id)                                                           |
|                                                                                                    |
|  2. wp_bds_saved_searches                                                                          |
|     ├── id (BIGINT PK AI)                                                                          |
|     ├── user_id (BIGINT NULL)                                                                      |
|     ├── email (VARCHAR(100) INDEX)                                                                 |
|     ├── criteria_json (TEXT)                                                                       |
|     ├── frequency (VARCHAR(20) DEFAULT 'daily')                                                    |
|     └── last_notified_at (DATETIME NULL)                                                           |
|                                                                                                    |
|  3. wp_bds_price_history                                                                           |
|     ├── id (BIGINT PK AI)                                                                          |
|     ├── project_id (BIGINT NULL INDEX)                                                             |
|     ├── district_slug (VARCHAR(50) INDEX)                                                          |
|     ├── avg_price_m2 (DECIMAL(10,2))                                                               |
|     ├── period_quarter (VARCHAR(10))                                                               |
|     └── period_year (SMALLINT)                                                                     |
|                                                                                                    |
|  4. wp_bds_listing_index (Bảng chỉ mục phẳng phục vụ tìm kiếm siêu tốc 100k+ tin)                 |
|     ├── listing_id (BIGINT PK)                                                                     |
|     ├── price_numeric (DECIMAL(12,2) INDEX)                                                        |
|     ├── area_numeric (DECIMAL(8,2) INDEX)                                                          |
|     ├── district_slug (VARCHAR(50) INDEX)                                                          |
|     ├── property_type_slug (VARCHAR(50) INDEX)                                                     |
|     ├── vip_tier (TINYINT INDEX)                                                                   |
|     └── status (VARCHAR(30) INDEX)                                                                 |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

---

### 8.2. Kiến Trúc REST API Tuyến Tính (`/wp-json/bds/v1/`)

Thay vì dùng `admin-ajax.php`, toàn bộ các dịch vụ tương tác mới sẽ được quy hoạch vào REST API chuyên nghiệp:

| Phương thức | Tuyến đường Endpoint | Chức năng nghiệp vụ | Xác thực & Quyền hạn |
|---|---|---|---|
| `GET` | `/wp-json/bds/v1/listings/filter` | Lọc danh sách BĐS (trả về JSON dữ liệu) | Public, Có Cache HTTP |
| `GET` | `/wp-json/bds/v1/user/favorites` | Lấy danh sách tin đã lưu của user | Logged in (`X-WP-Nonce`) |
| `POST` | `/wp-json/bds/v1/user/favorites/toggle` | Thêm / Bỏ lưu tin yêu thích | Logged in (`X-WP-Nonce`) |
| `POST` | `/wp-json/bds/v1/user/saved-searches` | Đăng ký theo dõi bộ lọc tìm kiếm | Public + Nonce + Honeypot |
| `POST` | `/wp-json/bds/v1/listings/submit` | Gửi tin đăng BĐS mới từ Frontend | Logged in (`upload_files`) |
| `POST` | `/wp-json/bds/v1/tools/amortization` | Tính lịch trả nợ vay ngân hàng | Public, Stateless |
| `GET` | `/wp-json/bds/v1/tools/compare` | Lấy bảng ma trận so sánh các BĐS | Public, Max 4 IDs |

---

### 8.3. Chiến Lược Frontend & Thư Viện Tối Giản (Zero jQuery Dependency)

Để giữ điểm số Core Web Vitals trên thiết bị di động đạt trên **90 điểm (Google PageSpeed)**:
1. **Không sử dụng thêm framework nặng**: Toàn bộ tương tác UI (Khay so sánh, Nút yêu thích, Modal đăng ký nhận tin) được viết bằng **Vanilla JavaScript hiện đại (ES6+)** kết hợp Custom Events.
2. **Nén ảnh trực tiếp trên trình duyệt (Client-side Compression)**: Tích hợp thư viện `Compressor.js` (~5KB) để nén ảnh sang định dạng WebP ngay trên điện thoại của môi giới trước khi tải lên máy chủ.
3. **Thư viện chuyên dụng siêu nhẹ**:
   - 360 Panorama: `Pannellum` (chỉ ~15KB gzipped).
   - Biểu đồ biến động giá: `Chart.js` (hoặc SVG render thẳng từ server).
   - Lightbox ảnh: `lightGallery` (đã có sẵn trong theme).

---

### 8.4. Kiểm Duyệt Nội Dung, Chống Gian Lận & Bảo Mật

1. **Phân quyền người dùng (Role & Capabilities)**:
   - Tạo vai trò người dùng chuyên biệt: `bds_broker` (Môi giới).
   - Quyền hạn: `edit_bds_listings`, `upload_files`. Chỉ môi giới có huy hiệu xác thực mới được cấp quyền tự xuất bản trực tiếp (`publish_bds_listings`).
2. **Kiểm tra quyền sở hữu bài viết (Ownership Enforcement)**:
   - Tuyệt đối ngăn chặn môi giới A chỉnh sửa hoặc xóa tin của môi giới B bằng cách xác thực:
     `if ((int) get_post_field('post_author', $post_id) !== get_current_user_id()) wp_die('Forbidden', 403);`
3. **Chống Spam & Tin Rác**:
   - Giới hạn tần suất đăng bài (Rate Limit): Tối đa 5 bài đăng/ngày đối với tài khoản miễn phí.
   - Thuật toán kiểm tra trùng lặp tiêu đề và số điện thoại trong nội dung để phát hiện tài khoản spam.

---

## 9. KẾT LUẬN & KẾ HOẠCH HÀNH ĐỘNG THEO SPRINT (ACTIONABLE SPRINT PLAN)

Lộ trình phát triển được đóng gói thành 3 Sprint hành động rõ ràng:

### Sprint 1 (Q1 — Củng cố Nền tảng, SEO & Giữ chân Khách hàng):
- [ ] Triển khai trọn bộ Schema JSON-LD (`RealEstateListing`, `BreadcrumbList`, `FAQPage`) vào `wp_head`.
- [ ] Xây dựng tính năng "Lưu tin yêu thích" (`localStorage` + bảng `wp_bds_user_favorites`).
- [ ] Hoàn thiện SEO Filter Landing Pages Matrix (Title, H1, Meta Description, Canonical tự động).
- [ ] Nâng cấp Bảng khấu hao nợ từng tháng cho công cụ tính lãi suất vay.
- [ ] Thiết lập hệ thống Phân tầng Tin VIP (Kim Cương, Vàng, Bạc, Thường).

### Sprint 2 (Q2 — Hệ sinh thái Môi giới & Nâng cao Tương tác):
- [ ] Xây dựng Form đăng tin BĐS phía Frontend 5 bước có nén ảnh client-side.
- [ ] Xây dựng Member Dashboard quản lý tin đăng cho môi giới tại `/tai-khoan/quan-ly-tin/`.
- [ ] Phát triển công cụ So sánh BĐS tương đồng Side-by-Side (Khay so sánh + Trang đối chiếu).
- [ ] Xây dựng tính năng Lưu bộ lọc tìm kiếm & Gửi email thông báo tự động (Smart Alerts).
- [ ] Thiết lập Hồ sơ Môi giới chuyên nghiệp & Huy hiệu xác minh uy tín.
- [ ] Tạo tiện ích tạo ảnh thẻ BĐS Infographic chia sẻ Facebook/Zalo kèm mã QR.

### Sprint 3 (Q3/Q4 — Dữ liệu Thị trường & Đột phá Công nghệ):
- [ ] Nghiên cứu tích hợp Bản đồ quy hoạch đất đai và tìm kiếm Bounding box trên bản đồ.
- [ ] Xây dựng Cơ sở dữ liệu và Biểu đồ lịch sử biến động giá theo khu vực.
- [ ] Thử nghiệm Thuật toán định giá nhà đất tự động (AVM).
- [ ] Tích hợp trình xem không gian thực tế ảo 360° Panorama với Pannellum.

---
*Lộ trình tính năng được xây dựng dựa trên khảo sát thực tế chuyên sâu, đối chuẩn sát với các nền tảng BĐS hàng đầu tại Việt Nam và có tính khả thi kỹ thuật 100% trên nền tảng WordPress.*
