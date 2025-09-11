<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class quản lý lên lịch đăng bài
 */
class GACG_Scheduler {
    
    public function __construct() {
        add_action('gacg_scheduled_post', array($this, 'publish_scheduled_post'));
        add_action('init', array($this, 'check_scheduled_posts'));
    }
    
    /**
     * Lên lịch đăng bài
     */
    public function schedule_post($data) {
        global $wpdb;
        
        $title = sanitize_text_field($data['title']);
        $content = wp_kses_post($data['content']);
        $post_type = sanitize_text_field($data['post_type']);
        $publish_date = sanitize_text_field($data['publish_date']);
        $publish_time = sanitize_text_field($data['publish_time']);
        
        if (empty($title) || empty($content) || empty($publish_date)) {
            return new WP_Error('missing_data', 'Thiếu thông tin bắt buộc');
        }
        
        // Kết hợp ngày và giờ
        $publish_datetime = $publish_date . ' ' . ($publish_time ?: '09:00:00');
        $publish_timestamp = strtotime($publish_datetime);
        
        if ($publish_timestamp <= time()) {
            return new WP_Error('invalid_date', 'Thời gian đăng bài phải sau thời điểm hiện tại');
        }
        
        // Chuẩn bị meta data
        $meta_data = array(
            'categories' => $data['categories'] ?? array(),
            'tags' => $data['tags'] ?? array(),
            'featured_image' => $data['featured_image'] ?? '',
            'seo_title' => $data['seo_title'] ?? '',
            'seo_description' => $data['seo_description'] ?? ''
        );
        
        // Lưu vào database
        $table_name = $wpdb->prefix . 'gacg_scheduled_posts';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'content' => $content,
                'post_type' => $post_type,
                'publish_date' => $publish_datetime,
                'status' => 'pending',
                'meta_data' => json_encode($meta_data)
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Lỗi lưu database');
        }
        
        $scheduled_id = $wpdb->insert_id;
        
        // Tạo WordPress cron job
        wp_schedule_single_event($publish_timestamp, 'gacg_scheduled_post', array($scheduled_id));
        
        return $scheduled_id;
    }
    
    /**
     * Đăng bài đã được lên lịch
     */
    public function publish_scheduled_post($scheduled_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gacg_scheduled_posts';
        
        // Lấy thông tin bài viết
        $scheduled_post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'pending'",
            $scheduled_id
        ));
        
        if (!$scheduled_post) {
            return;
        }
        
        $meta_data = json_decode($scheduled_post->meta_data, true);
        
        // Tạo bài viết WordPress
        $post_data = array(
            'post_title' => $scheduled_post->title,
            'post_content' => $scheduled_post->content,
            'post_status' => 'publish',
            'post_type' => $scheduled_post->post_type,
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            // Cập nhật status thành failed
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('id' => $scheduled_id),
                array('%s'),
                array('%d')
            );
            return;
        }
        
        // Thêm categories
        if (!empty($meta_data['categories'])) {
            wp_set_post_categories($post_id, $meta_data['categories']);
        }
        
        // Thêm tags
        if (!empty($meta_data['tags'])) {
            wp_set_post_tags($post_id, $meta_data['tags']);
        }
        
        // Set featured image
        if (!empty($meta_data['featured_image'])) {
            $image_processor = new GACG_Image_Processor();
            $image_processor->set_featured_image($post_id, $meta_data['featured_image'], $scheduled_post->title);
        }
        
        // Thêm SEO meta (nếu có plugin SEO)
        if (!empty($meta_data['seo_title'])) {
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_data['seo_title']);
        }
        
        if (!empty($meta_data['seo_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_data['seo_description']);
        }
        
        // Cập nhật status thành published và lưu post_id
        $wpdb->update(
            $table_name,
            array(
                'status' => 'published',
                'meta_data' => json_encode(array_merge($meta_data, array('wp_post_id' => $post_id)))
            ),
            array('id' => $scheduled_id),
            array('%s', '%s'),
            array('%d')
        );
        
        // Log thành công
        error_log("GACG: Successfully published scheduled post ID {$scheduled_id} as WordPress post ID {$post_id}");
    }
    
    /**
     * Kiểm tra và xử lý bài viết đã lên lịch
     */
    public function check_scheduled_posts() {
        if (!wp_next_scheduled('gacg_check_scheduled_posts')) {
            wp_schedule_event(time(), 'hourly', 'gacg_check_scheduled_posts');
        }
    }
    
    /**
     * Lấy danh sách bài viết đã lên lịch
     */
    public function get_scheduled_posts($status = 'pending', $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gacg_scheduled_posts';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY publish_date ASC LIMIT %d",
            $status,
            $limit
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Hủy bài viết đã lên lịch
     */
    public function cancel_scheduled_post($scheduled_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gacg_scheduled_posts';
        
        // Cập nhật status
        $result = $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('id' => $scheduled_id),
            array('%s'),
            array('%d')
        );
        
        // Hủy cron job
        $scheduled_post = $wpdb->get_row($wpdb->prepare(
            "SELECT publish_date FROM $table_name WHERE id = %d",
            $scheduled_id
        ));
        
        if ($scheduled_post) {
            $timestamp = strtotime($scheduled_post->publish_date);
            wp_unschedule_event($timestamp, 'gacg_scheduled_post', array($scheduled_id));
        }
        
        return $result !== false;
    }
    
    /**
     * Lên lịch đăng bài hàng loạt
     */
    public function bulk_schedule($titles, $interval_hours = 24, $start_date = null) {
        if (empty($titles) || !is_array($titles)) {
            return new WP_Error('invalid_data', 'Danh sách tiêu đề không hợp lệ');
        }
        
        $start_timestamp = $start_date ? strtotime($start_date) : strtotime('+1 day');
        $interval_seconds = $interval_hours * 3600;
        
        $scheduled_posts = array();
        $gemini_api = new GACG_Gemini_API();
        
        foreach ($titles as $index => $title) {
            $publish_timestamp = $start_timestamp + ($index * $interval_seconds);
            $publish_date = date('Y-m-d H:i:s', $publish_timestamp);
            
            // Tạo nội dung tự động
            $content = $gemini_api->generate_content($title);
            
            if (is_wp_error($content)) {
                continue;
            }
            
            $data = array(
                'title' => $title,
                'content' => $content,
                'post_type' => 'post',
                'publish_date' => date('Y-m-d', $publish_timestamp),
                'publish_time' => date('H:i:s', $publish_timestamp)
            );
            
            $scheduled_id = $this->schedule_post($data);
            
            if (!is_wp_error($scheduled_id)) {
                $scheduled_posts[] = $scheduled_id;
            }
        }
        
        return $scheduled_posts;
    }
}