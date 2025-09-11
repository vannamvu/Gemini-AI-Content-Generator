<?php
if (!defined('ABSPATH')) {
    exit;
}

// Lấy categories
$categories = get_categories(array('hide_empty' => false));
$settings = get_option('gacg_settings', array());
?>

<div class="gacg-admin-wrap">
    <div class="gacg-header">
        <h1>🚀 Tạo bài viết hàng loạt với Gemini</h1>
        <p>Nhập nhiều tiêu đề cùng lúc. Plugin sẽ tự động tạo từng bài viết một cách chi tiết và có hình ảnh</p>
    </div>

    <form id="gacg-bulk-content-form" method="post">
        <?php wp_nonce_field('gacg_bulk_content_nonce'); ?>
        
        <div class="gacg-cards">
            <!-- Danh sách tiêu đề -->
            <div class="gacg-card">
                <h3>📝 Danh sách tiêu đề bài viết</h3>
                <div class="gacg-form-group">
                    <textarea id="gacg-title-list" name="title_list" rows="10" placeholder="Mỗi dòng một tiêu đề bài viết. Ví dụ:&#10;Mối tình qua mạng: Ảo mộng hay phép màu thời đại số?&#10;Kết bạn online: Tại sao cảng dễ bắt chuyện hơn cảng khi thúc được người chân thành?&#10;5 lỗi kép khiến crush 'rung rinh' chỉ qua vài tin nhắn đầu tiên" required></textarea>
                    <div class="gacg-char-counter" id="title-count">0 tiêu đề</div>
                </div>
                <p><small>Bạn có thể tùy chỉnh các mẫu câu lệnh (prompt) trong trang <a href="<?php echo admin_url('admin.php?page=gemini-ai-content-settings'); ?>">Cài đặt</a></small></p>
            </div>

            <!-- Cài đặt nội dung -->
            <div class="gacg-card">
                <h3>⚙️ Cài đặt nội dung</h3>
                
                <div class="gacg-form-row">
                    <div class="gacg-form-group">
                        <label for="gacg-category">Danh mục</label>
                        <select id="gacg-category" name="category" required>
                            <option value="">Chọn danh mục bài viết</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->term_id; ?>"><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="gacg-form-group">
                        <label for="gacg-publish-option">Tùy chọn đăng</label>
                        <select id="gacg-publish-option" name="publish_option" required>
                            <option value="draft">Lưu nháp</option>
                            <option value="publish">Đăng ngay</option>
                            <option value="schedule">Lên lịch</option>
                        </select>
                    </div>
                </div>

                <!-- Tùy chọn lên lịch -->
                <div id="schedule-options" style="display: none;">
                    <div class="gacg-form-row">
                        <div class="gacg-form-group">
                            <label for="gacg-start-date">Ngày bắt đầu đăng</label>
                            <input type="datetime-local" id="gacg-start-date" name="start_date">
                            <small>Chọn ngày và giờ bắt đầu cho loạt bài viết đầu tiên.</small>
                        </div>

                        <div class="gacg-form-group">
                            <label for="gacg-posts-per-batch">Số bài mỗi lần đăng</label>
                            <select id="gacg-posts-per-batch" name="posts_per_batch">
                                <option value="1">1 bài</option>
                                <option value="2">2 bài</option>
                                <option value="3">3 bài</option>
                                <option value="4">4 bài</option>
                                <option value="5">5 bài</option>
                                <option value="6">6 bài</option>
                                <option value="7">7 bài</option>
                            </select>
                        </div>

                        <div class="gacg-form-group">
                            <label for="gacg-interval">Thời gian giãn cách</label>
                            <select id="gacg-interval" name="interval">
                                <option value="4">4 giờ</option>
                                <option value="6">6 giờ</option>
                                <option value="8">8 giờ</option>
                                <option value="12">12 giờ</option>
                                <option value="24" selected>24 giờ (1 ngày)</option>
                                <option value="48">48 giờ (2 ngày)</option>
                                <option value="72">72 giờ (3 ngày)</option>
                            </select>
                            <small>Khoảng thời gian (tính bằng giờ) giữa các lần đăng bài tiếp theo.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cài đặt hình ảnh -->
            <div class="gacg-card">
                <h3>🖼️ Cài đặt hình ảnh</h3>
                
                <div class="gacg-form-row">
                    <div class="gacg-form-group">
                        <label for="gacg-image-count">Số lượng ảnh trong bài</label>
                        <input type="number" id="gacg-image-count" name="image_count" value="3" min="1" max="10">
                    </div>

                    <div class="gacg-form-group">
                        <label for="gacg-image-size">Kích thước ảnh chèn</label>
                        <select id="gacg-image-size" name="image_size">
                            <option value="large">Lớn</option>
                            <option value="medium" selected>Trung bình</option>
                            <option value="small">Nhỏ</option>
                        </select>
                    </div>

                    <div class="gacg-form-group">
                        <label for="gacg-image-ratio">Khổ hình</label>
                        <select id="gacg-image-ratio" name="image_ratio">
                            <option value="1:1">Vuông (1:1)</option>
                            <option value="16:9" selected>Chữ nhật (16:9)</option>
                            <option value="4:3">Chữ nhật (4:3)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Internal Links -->
            <div class="gacg-card">
                <h3>🔗 Cài đặt liên kết nội bộ</h3>
                
                <div class="gacg-form-row">
                    <div>
                        <label>
                            <input type="checkbox" id="gacg-auto-internal-links" name="auto_internal_links" value="1" checked>
                            Tự động chèn link nội bộ
                        </label>
                        <p>Tự động chèn link các bài viết đã có trong nội dung</p>
                    </div>

                    <div class="gacg-form-group">
                        <label for="gacg-internal-links-count">Số lượng link cần chèn</label>
                        <select id="gacg-internal-links-count" name="internal_links_count">
                            <option value="2" selected>2 link</option>
                            <option value="3">3 link</option>
                            <option value="4">4 link</option>
                            <option value="5">5 link</option>
                            <option value="6">6 link</option>
                        </select>
                        <small>Số lượng link ngẫu nhiên sẽ được chèn vào nội dung</small>
                    </div>
                </div>
            </div>

            <!-- Tối ưu hóa -->
            <div class="gacg-card">
                <h3>🔧 Tối ưu hóa hình ảnh</h3>
                
                <div class="gacg-form-row">
                    <div>
                        <label>
                            <input type="checkbox" name="auto_webp" value="1" checked>
                            Chuyển đổi sang WEBP
                        </label>
                        <p>Tự động chuyển đổi hình ảnh sang định dạng WEBP để tối ưu tốc độ</p>
                    </div>

                    <div class="gacg-form-group">
                        <label for="gacg-webp-quality">Chất lượng WEBP</label>
                        <input type="number" id="gacg-webp-quality" name="webp_quality" value="80" min="10" max="100">
                        <small>Chất lượng nén ảnh cho ảnh WEBP (khuyên dùng từ 75-90). Số càng nhỏ, dung lượng càng nhẹ nhưng chất lượng ảnh giảm</small>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="gacg-card" style="text-align: center;">
                <button type="submit" id="gacg-start-bulk-creation" class="gacg-btn gacg-btn-large gacg-btn-success">
                    🚀 Bắt đầu tạo bài
                </button>
                
                <!-- Progress Bar -->
                <div id="gacg-progress-container" style="display: none; margin-top: 20px;">
                    <div class="gacg-progress">
                        <div class="gacg-progress-bar" style="width: 0%"></div>
                    </div>
                    <div id="gacg-progress-text">Đang xử lý...</div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Count titles
    $('#gacg-title-list').on('input', function() {
        var titles = $(this).val().split('\n').filter(function(title) {
            return title.trim() !== '';
        });
        $('#title-count').text(titles.length + ' tiêu đề');
    });

    // Show/hide schedule options
    $('#gacg-publish-option').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#schedule-options').show();
        } else {
            $('#schedule-options').hide();
        }
    });

    // Form submission
    $('#gacg-bulk-content-form').on('submit', function(e) {
        e.preventDefault();
        
        var titles = $('#gacg-title-list').val().split('\n').filter(function(title) {
            return title.trim() !== '';
        });
        
        if (titles.length === 0) {
            alert('Vui lòng nhập ít nhất 1 tiêu đề bài viết');
            return;
        }
        
        if (!$('#gacg-category').val()) {
            alert('Vui lòng chọn danh mục bài viết');
            return;
        }
        
        // Start bulk creation
        GACG.startBulkCreation(titles, $(this).serialize());
    });
});
</script>

<style>
/* Bulk creation specific styles */
.gacg-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.gacg-form-row.three-cols {
    grid-template-columns: 1fr 1fr 1fr;
}

#schedule-options {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    margin-top: 15px;
}

.gacg-progress-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

#gacg-progress-text {
    text-align: center;
    margin-top: 10px;
    font-weight: 600;
    color: #667eea;
}

@media (max-width: 768px) {
    .gacg-form-row {
        grid-template-columns: 1fr;
    }
}
</style>