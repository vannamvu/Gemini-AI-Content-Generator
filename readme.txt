=== Gemini AI Content Generator ===
Contributors: Vu Van Nam Viet
Tags: ai, content, gemini, automation, seo
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tạo nội dung tự động bằng AI Gemini với tối ưu SEO và quản lý lên lịch đăng bài thông minh.

== Description ==

**Gemini AI Content Generator** là plugin WordPress mạnh mẽ giúp bạn tạo nội dung chất lượng cao một cách tự động bằng công nghệ AI Gemini của Google.

### ✨ Tính Năng Chính

* **🚀 Tạo bài viết hàng loạt**: Nhập nhiều tiêu đề cùng lúc, plugin tự động tạo nội dung chi tiết
* **🤖 AI Gemini tích hợp**: Sử dụng model Gemini 1.5 Flash cho tốc độ và chất lượng tối ưu
* **📅 Lên lịch đăng bài**: Tự động đăng bài theo lịch trình đã định
* **🖼️ Tối ưu hình ảnh**: Tự động chuyển đổi sang WEBP và tối ưu kích thước
* **🔗 Liên kết nội bộ**: Tự động chèn link các bài viết liên quan
* **🎯 Tối ưu SEO**: Tự động tạo meta description, title SEO
* **⚙️ Tùy chỉnh Prompt**: Cài đặt prompt riêng cho từng loại nội dung

### 🛠️ Cài Đặt

1. Tải plugin về và giải nén vào thư mục `/wp-content/plugins/`
2. Kích hoạt plugin từ trang Plugins trong WordPress Admin
3. Truy cập **Gemini AI > Cài Đặt** để cấu hình API key
4. Lấy API key miễn phí tại [Google AI Studio](https://makersuite.google.com/app/apikey)
5. Bắt đầu tạo nội dung!

### 📖 Hướng Dẫn Sử Dụng

#### Tạo Bài Viết Hàng Loạt:
1. Vào **Gemini AI > Tạo Hàng Loạt**
2. Nhập danh sách tiêu đề (mỗi dòng một tiêu đề)
3. Chọn danh mục và tùy chọn đăng bài
4. Cấu hình hình ảnh và liên kết nội bộ
5. Nhấn "Bắt đầu tạo bài"

#### Lên Lịch Đăng Bài:
1. Chọn "Lên lịch" trong tùy chọn đăng
2. Chọn ngày giờ bắt đầu
3. Cài đặt số bài mỗi lần đăng và khoảng cách thời gian
4. Plugin sẽ tự động đăng bài theo lịch

#### Quản Lý Nội Dung:
1. Vào **Gemini AI > Quản Lý Lịch** để xem bài viết đã lên lịch
2. Có thể hủy hoặc chỉnh sửa bài viết
3. Theo dõi tiến trình đăng bài tự động

### ⚙️ Cấu Hình

#### API Settings:
* **Gemini API Key**: Lấy từ Google AI Studio
* **Model nội dung**: Gemini 1.5 Flash (khuyên dùng)
* **Model hình ảnh**: Gemini 2.0 Flash Exp

#### Content Settings:
* **Prompt nội dung**: Tùy chỉnh cách AI viết bài
* **Prompt hình ảnh**: Cài đặt cách tạo hình ảnh
* **Số ảnh mặc định**: 1-10 ảnh mỗi bài
* **Số link nội bộ**: 2-6 link tự động

#### Optimization:
* **Chuyển đổi WEBP**: Tự động tối ưu hình ảnh
* **Chất lượng WEBP**: 75-90 (khuyên dùng 80)
* **Tối ưu SEO**: Tự động tạo meta tags

### 🔧 Yêu Cầu Hệ Thống

* WordPress 5.0+
* PHP 7.4+
* Gemini AI API Key (miễn phí)
* cURL extension
* GD Library (cho xử lý ảnh)

### 🆘 Hỗ Trợ

Nếu gặp vấn đề, vui lòng:

1. Kiểm tra API key đã cấu hình đúng chưa
2. Đảm bảo server có kết nối internet ổn định
3. Kiểm tra log lỗi trong **Gemini AI > Dashboard**
4. Liên hệ support qua GitHub Issues

### 🔄 Changelog

#### 1.0.0
* Phiên bản đầu tiên
* Tích hợp Gemini AI API
* Tạo bài viết hàng loạt
* Lên lịch đăng bài tự động
* Tối ưu hình ảnh WEBP
* Liên kết nội bộ tự động
* Giao diện admin tiếng Việt

== Installation ==

1. Upload plugin files to `/wp-content/plugins/gemini-ai-content-generator/`
2. Activate plugin through WordPress admin
3. Go to Gemini AI > Settings to configure API key
4. Get free API key from Google AI Studio
5. Start creating content!

== Frequently Asked Questions ==

= API key có miễn phí không? =
Có, Google cung cấp API key miễn phí với quota hàng tháng đủ dùng cho website cá nhân và doanh nghiệp nhỏ.

= Plugin có hoạt động offline không? =
Không, plugin cần kết nối internet để gọi API Gemini của Google.

= Có thể tùy chỉnh prompt không? =
Có, bạn có thể tùy chỉnh prompt cho cả nội dung và hình ảnh trong phần Cài đặt.

= Bài viết có chất lượng như thế nào? =
AI Gemini tạo ra nội dung chất lượng cao, tuy nhiên nên review và chỉnh sửa nhẹ để phù hợp với phong cách website.

== Screenshots ==

1. Dashboard tổng quan
2. Form tạo bài viết hàng loạt
3. Quản lý lịch đăng bài
4. Trang cài đặt plugin
5. Kết quả tạo bài tự động

== Upgrade Notice ==

= 1.0.0 =
Phiên bản đầu tiên của plugin. Hãy backup website trước khi cài đặt.