<?php
if (!defined('ABSPATH')) {
    exit;
}

// Lấy danh sách bài viết đã lên lịch
global $wpdb;
$table_scheduled = $wpdb->prefix . 'gacg_scheduled_posts';
$scheduled_posts = $wpdb->get_results("SELECT * FROM $table_scheduled ORDER BY publish_date DESC LIMIT 20");
?>

<div class="gacg-admin-wrap">
    <div class="gacg-header">
        <h1>📅 Quản lý lịch đăng bài</h1>
        <p>Xem và quản lý các bài viết đã được lên lịch đăng tự động</p>
    </div>

    <!-- Thống kê nhanh -->
    <div class="gacg-stats">
        <?php
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'pending'");
        $published_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'published'");
        $failed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_scheduled WHERE status = 'failed'");
        ?>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $pending_count; ?></span>
            <div class="gacg-stat-label">Chờ đăng</div>
        </div>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $published_count; ?></span>
            <div class="gacg-stat-label">Đã đăng</div>
        </div>
        <div class="gacg-stat-item">
            <span class="gacg-stat-number"><?php echo $failed_count; ?></span>
            <div class="gacg-stat-label">Thất bại</div>
        </div>
    </div>

    <!-- Danh sách bài viết đã lên lịch -->
    <div class="gacg-card">
        <h3>📋 Danh sách bài viết đã lên lịch</h3>
        
        <?php if (empty($scheduled_posts)): ?>
            <div style="text-align: center; padding: 40px;">
                <p>Chưa có bài viết nào được lên lịch</p>
                <a href="<?php echo admin_url('admin.php?page=gemini-ai-content-create'); ?>" class="gacg-btn">Tạo bài viết đầu tiên</a>
            </div>
        <?php else: ?>
            <table class="gacg-table" id="gacg-scheduled-posts">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Thời gian đăng</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scheduled_posts as $post): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($post->title); ?></strong>
                            <div style="font-size: 0.9em; color: #6c757d;">
                                Tạo: <?php echo date('d/m/Y H:i', strtotime($post->created_at)); ?>
                            </div>
                        </td>
                        <td><?php echo esc_html($post->post_type); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($post->publish_date)); ?></td>
                        <td>
                            <?php
                            $status_badges = array(
                                'pending' => '<span class="gacg-badge gacg-badge-pending">Chờ đăng</span>',
                                'published' => '<span class="gacg-badge gacg-badge-published">Đã đăng</span>',
                                'failed' => '<span class="gacg-badge gacg-badge-failed">Thất bại</span>',
                                'cancelled' => '<span class="gacg-badge gacg-badge-secondary">Đã hủy</span>'
                            );
                            echo $status_badges[$post->status] ?? $post->status;
                            ?>
                        </td>
                        <td>
                            <?php if ($post->status === 'pending'): ?>
                                <button class="gacg-btn gacg-btn-secondary gacg-btn-sm gacg-cancel-scheduled" data-id="<?php echo $post->id; ?>">
                                    Hủy
                                </button>
                            <?php elseif ($post->status === 'published'): ?>
                                <?php
                                // Get WordPress post ID from meta_data
                                $meta_data = json_decode($post->meta_data, true);
                                $wp_post_id = $meta_data['wp_post_id'] ?? null;
                                
                                if ($wp_post_id && get_post($wp_post_id)):
                                ?>
                                    <a href="<?php echo get_edit_post_link($wp_post_id); ?>" class="gacg-btn gacg-btn-sm">✏️ Chỉnh sửa</a>
                                    <a href="<?php echo get_permalink($wp_post_id); ?>" class="gacg-btn gacg-btn-sm" target="_blank">👁️ Xem</a>
                                <?php else: ?>
                                    <span style="color: #ffc107;">⚠️ Không tìm thấy bài viết</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #6c757d;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Hướng dẫn -->
    <div class="gacg-card">
        <h3>💡 Hướng dẫn sử dụng</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <h4>📝 Tạo bài viết hàng loạt</h4>
                <p>Sử dụng tính năng tạo bài viết hàng loạt để tự động tạo và lên lịch nhiều bài viết cùng lúc.</p>
            </div>
            <div>
                <h4>⏰ Quản lý thời gian</h4>
                <p>Bài viết sẽ được đăng tự động vào thời gian đã định. Hệ thống sẽ kiểm tra và đăng bài mỗi giờ.</p>
            </div>
            <div>
                <h4>🔄 Xử lý lỗi</h4>
                <p>Nếu có bài viết bị lỗi, bạn có thể xem chi tiết và thử lại hoặc chỉnh sửa thủ công.</p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Cancel scheduled post
    $('.gacg-cancel-scheduled').on('click', function() {
        var postId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        if (confirm('Bạn có chắc muốn hủy bài viết này?')) {
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_cancel_scheduled_post',
                nonce: gacg_ajax.nonce,
                scheduled_id: postId
            }, function(response) {
                if (response.success) {
                    $row.find('.gacg-badge').replaceWith('<span class="gacg-badge gacg-badge-secondary">Đã hủy</span>');
                    $row.find('.gacg-cancel-scheduled').remove();
                    GACG.showAlert('Đã hủy bài viết thành công', 'success');
                } else {
                    GACG.showAlert('Có lỗi xảy ra: ' + (response.data || 'Unknown error'), 'error');
                }
            });
        }
    });
    
    // Auto refresh every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
});
</script>