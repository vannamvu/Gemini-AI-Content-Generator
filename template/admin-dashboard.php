<?php
if (!defined('ABSPATH')) {
    exit;
}

// Lấy thống kê
global $wpdb;
$table_scheduled = $wpdb->prefix . 'gacg_scheduled_posts';

$stats = array(
    'total_scheduled' => $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled"),
    'pending_posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'pending'"),
    'published_posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'published'"),
    'failed_posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'failed'")
);

$recent_posts = $wpdb->get_results("SELECT * FROM $table_scheduled ORDER BY created_at DESC LIMIT 5");
$settings = get_option('gacg_settings', array());
?>

<div class="gacg-admin-wrap">
    <div class="gacg-header">
        <h1>🤖 Gemini AI Content Generator</h1>
        <p>Tạo nội dung tự động với AI, tối ưu SEO và quản lý lên lịch đăng bài thông minh</p>
    </div>

    <!-- Thống kê -->
    <div class="gacg-stats">
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $stats['total_scheduled']; ?></span>
            <div class="gacg-stat-label">Tổng bài viết đã lên lịch</div>
        </div>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $stats['pending_posts']; ?></span>
            <div class="gacg-stat-label">Chờ đăng</div>
        </div>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $stats['published_posts']; ?></span>
            <div class="gacg-stat-label">Đã đăng</div>
        </div>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $stats['failed_posts']; ?></span>
            <div class="gacg-stat-label">Thất bại</div>
        </div>
    </div>

    <!-- Cards chức năng -->
    <div class="gacg-cards">
        <div class="gacg-card">
            <h3>🚀 Tạo Nội Dung AI</h3>
            <p>Tạo bài viết chất lượng cao từ tiêu đề với AI Gemini. Tự động tối ưu SEO và thêm hình ảnh.</p>
            <a href="<?php echo admin_url('admin.php?page=gemini-ai-content-create'); ?>" class="gacg-btn gacg-btn-large">Bắt Đầu Tạo</a>
        </div>

        <div class="gacg-card">
            <h3>📅 Lên Lịch Đăng Bài</h3>
            <p>Lên lịch đăng bài tự động với khoảng thời gian tùy chỉnh. Quản lý nội dung hiệu quả.</p>
            <a href="<?php echo admin_url('admin.php?page=gemini-ai-content-schedule'); ?>" class="gacg-btn gacg-btn-large">Quản Lý Lịch</a>
        </div>

        <div class="gacg-card">
            <h3>⚙️ Cài Đặt Plugin</h3>
            <p>Cấu hình API key, tùy chỉnh prompts và các thiết lập tối ưu cho website của bạn.</p>
            <a href="<?php echo admin_url('admin.php?page=gemini-ai-content-settings'); ?>" class="gacg-btn gacg-btn-large">Cài Đặt</a>
        </div>
    </div>

    <!-- Trạng thái API -->
    <div class="gacg-card">
        <h3>🔗 Trạng Thái Kết Nối</h3>
        <div class="gacg-form-row">
            <div>
                <strong>API Gemini:</strong>
                <?php if (!empty($settings['gemini_api_key'])): ?>
                    <span style="color: #28a745;">✅ Đã cấu hình</span>
                <?php else: ?>
                    <span style="color: #dc3545;">❌ Chưa cấu hình</span>
                    <p><a href="<?php echo admin_url('admin.php?page=gemini-ai-content-settings'); ?>">Cấu hình ngay</a></p>
                <?php endif; ?>
            </div>
            <div>
                <strong>Tối ưu WEBP:</strong>
                <?php if ($settings['image_webp'] ?? 1): ?>
                    <span style="color: #28a745;">✅ Đã bật</span>
                <?php else: ?>
                    <span style="color: #ffc107;">⚠️ Đã tắt</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bài viết gần đây -->
    <?php if (!empty($recent_posts)): ?>
    <div class="gacg-card">
        <h3>📝 Bài Viết Gần Đây</h3>
        <table class="gacg-table">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Loại</th>
                    <th>Thời gian đăng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_posts as $post): ?>
                <tr>
                    <td><?php echo esc_html($post->title); ?></td>
                    <td><?php echo esc_html($post->post_type); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($post->publish_date)); ?></td>
                    <td>
                        <?php
                        $status_badges = array(
                            'pending' => '<span class="gacg-badge gacg-badge-pending">Chờ đăng</span>',
                            'published' => '<span class="gacg-badge gacg-badge-published">Đã đăng</span>',
                            'failed' => '<span class="gacg-badge gacg-badge-failed">Thất bại</span>'
                        );
                        echo $status_badges[$post->status] ?? $post->status;
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Hướng dẫn nhanh -->
    <div class="gacg-card">
        <h3>📖 Hướng Dẫn Nhanh</h3>
        <div class="gacg-form-row">
            <div>
                <h4>1. Cài đặt API Key</h4>
                <p>Truy cập <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a> để tạo API key miễn phí.</p>
            </div>
            <div>
                <h4>2. Tạo nội dung</h4>
                <p>Nhập tiêu đề, chọn prompt và để AI tạo nội dung chất lượng cao cho bạn.</p>
            </div>
            <div>
                <h4>3. Lên lịch đăng</h4>
                <p>Tự động hóa việc đăng bài với hệ thống lên lịch thông minh.</p>
            </div>
        </div>
    </div>

    <!-- Thông tin phiên bản -->
    <div style="text-align: center; margin-top: 30px; color: #6c757d;">
        <p>Gemini AI Content Generator v<?php echo GACG_VERSION; ?> | 
        <a href="https://github.com/vuvannamviet-sys/ldt-isochungnhan" target="_blank">GitHub</a> | 
        <a href="mailto:support@example.com">Hỗ trợ</a></p>
    </div>
</div>

<style>
/* Dashboard specific styles */
.gacg-stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.gacg-card h4 {
    color: #667eea;
    margin-bottom: 10px;
}

.gacg-form-row div {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #667eea;
}
</style>