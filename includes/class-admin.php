<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class quản lý Admin Interface - With Progress Tracking CSS
 */
class GACG_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_gacg_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_gacg_bulk_create_posts', array($this, 'ajax_bulk_create_posts'));
        add_action('wp_ajax_gacg_publish_post', array($this, 'ajax_publish_post'));
        add_action('wp_ajax_gacg_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_gacg_cancel_scheduled_post', array($this, 'ajax_cancel_scheduled_post'));
    }
    
    /**
     * Thêm menu admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'Gemini AI Content',
            'Gemini AI',
            'manage_options',
            'gemini-ai-content',
            array($this, 'dashboard_page'),
            'dashicons-edit-large',
            25
        );
        
        add_submenu_page(
            'gemini-ai-content',
            'Tạo Nội Dung Hàng Loạt',
            'Tạo Hàng Loạt',
            'manage_options',
            'gemini-ai-content-create',
            array($this, 'create_content_page')
        );
        
        add_submenu_page(
            'gemini-ai-content',
            'Quản Lý Lịch',
            'Quản Lý Lịch',
            'manage_options',
            'gemini-ai-content-schedule',
            array($this, 'schedule_page')
        );
        
        add_submenu_page(
            'gemini-ai-content',
            'Cài Đặt',
            'Cài Đặt',
            'manage_options',
            'gemini-ai-content-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Load CSS và JS - UPDATED với progress-tracking.css
     */
    public function enqueue_scripts($hook) {
        // Chỉ load trên trang plugin
        if (strpos($hook, 'gemini-ai-content') === false) {
            return;
        }
        
        // Load CSS files
        wp_enqueue_style(
            'gacg-admin-style', 
            GACG_PLUGIN_URL . 'assets/css/admin-style.css', 
            array(), 
            GACG_VERSION
        );
        
        // Load Progress Tracking CSS
        wp_enqueue_style(
            'gacg-progress-tracking', 
            GACG_PLUGIN_URL . 'assets/css/progress-tracking.css', 
            array('gacg-admin-style'), // Depend on main CSS
            GACG_VERSION
        );
        
        // Load JavaScript
        wp_enqueue_script(
            'gacg-admin-script', 
            GACG_PLUGIN_URL . 'assets/js/admin-script.js', 
            array('jquery'), 
            GACG_VERSION, 
            true
        );
        
        // Localize script với các biến cần thiết
        wp_localize_script('gacg-admin-script', 'gacg_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gacg_nonce'),
            'loading_text' => 'Đang xử lý...',
            'success_text' => 'Thành công!',
            'error_text' => 'Có lỗi xảy ra!',
            'plugin_url' => GACG_PLUGIN_URL,
            'admin_url' => admin_url(),
            'current_user' => wp_get_current_user()->ID
        ));
    }
    
    /**
     * Trang dashboard chính
     */
    public function dashboard_page() {
        include GACG_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }
    
    /**
     * Trang tạo nội dung
     */
    public function create_content_page() {
        include GACG_PLUGIN_PATH . 'templates/content-form.php';
    }
    
    /**
     * Trang lên lịch
     */
    public function schedule_page() {
        include GACG_PLUGIN_PATH . 'templates/scheduler-page.php';
    }
    
    /**
     * Trang cài đặt
     */
    public function settings_page() {
        // Xử lý form submit
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'gacg_settings_nonce')) {
            $this->save_settings();
        }
        
        // Lấy cài đặt hiện tại
        $settings = get_option('gacg_settings', array());
        
        // Default values
        $default_content_prompt = 'Viết một bài viết chi tiết và chuyên nghiệp về chủ đề "[title]" bằng tiếng Việt. Bài viết cần có ít nhất 1500 từ, cấu trúc rõ ràng với các tiêu đề phụ H2, H3, nội dung phong phú và tối ưu SEO. Sử dụng phong cách viết tự nhiên, dễ đọc và hấp dẫn người đọc.';
        $default_image_prompt = 'Tạo hình ảnh chất lượng cao, chuyên nghiệp về chủ đề "[title]". Hình ảnh cần rõ nét, đẹp mắt, có tính minh họa cao và phù hợp với nội dung bài viết. Từ khóa liên quan: [keyword]. Phong cách: hiện đại, tối giản, màu sắc hài hòa.';
        ?>
        
        <div class="gacg-admin-wrap">
            <div class="gacg-header">
                <h1>⚙️ Cài Đặt Gemini AI Content Generator</h1>
                <p>Cấu hình API, prompts và các tùy chọn tối ưu cho hệ thống</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('gacg_settings_nonce'); ?>
                
                <!-- API Settings -->
                <div class="gacg-card">
                    <h3>🔑 Cài Đặt API</h3>
                    
                    <div class="gacg-form-group">
                        <label for="gemini_api_key">Gemini AI API Key *</label>
                        <input type="password" name="gemini_api_key" id="gemini_api_key" 
                               value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>" 
                               class="regular-text" required />
                        <p class="description">
                            Lấy API key miễn phí tại <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        </p>
                        <button type="button" id="test-api-connection" class="gacg-btn gacg-btn-secondary">🔄 Test Kết Nối</button>
                        <div id="api-test-result"></div>
                    </div>
                    
                    <div class="gacg-form-row">
                        <div class="gacg-form-group">
                            <label for="content_model">Model tạo nội dung</label>
                            <select name="content_model" id="content_model">
                                <option value="gemini-1.5-flash" <?php selected($settings['content_model'] ?? 'gemini-1.5-flash', 'gemini-1.5-flash'); ?>>
                                    Gemini 1.5 Flash (nhanh & tối ưu)
                                </option>
                                <option value="gemini-1.5-pro" <?php selected($settings['content_model'] ?? '', 'gemini-1.5-pro'); ?>>
                                    Gemini 1.5 Pro (chất lượng cao)
                                </option>
                                <option value="gemini-1.0-pro" <?php selected($settings['content_model'] ?? '', 'gemini-1.0-pro'); ?>>
                                    Gemini 1.0 Pro (ổn định)
                                </option>
                            </select>
                        </div>
                        
                        <div class="gacg-form-group">
                            <label for="image_model">Model tạo hình ảnh</label>
                            <select name="image_model" id="image_model">
                                <option value="gemini-2.0-flash-exp" <?php selected($settings['image_model'] ?? 'gemini-2.0-flash-exp', 'gemini-2.0-flash-exp'); ?>>
                                    Gemini 2.0 Flash Exp (khuyên dùng)
                                </option>
                                <option value="gemini-1.5-flash" <?php selected($settings['image_model'] ?? '', 'gemini-1.5-flash'); ?>>
                                    Gemini 1.5 Flash (tương thích tốt)
                                </option>
                                <option value="gemini-1.5-pro" <?php selected($settings['image_model'] ?? '', 'gemini-1.5-pro'); ?>>
                                    Gemini 1.5 Pro (chất lượng cao)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Content Prompts -->
                <div class="gacg-card">
                    <h3>📝 Cài Đặt Prompt Nội Dung</h3>
                    
                    <div class="gacg-form-group">
                        <label for="default_content_prompt">Mẫu prompt nội dung</label>
                        <textarea name="default_content_prompt" id="default_content_prompt" rows="6" class="large-text"><?php 
                            echo esc_textarea($settings['default_content_prompt'] ?? $default_content_prompt); 
                        ?></textarea>
                        <p class="description">Sử dụng <code>[title]</code> để chèn tiêu đề bài viết. Prompt này sẽ được dùng cho tất cả các bài viết.</p>
                    </div>
                </div>

                <!-- Image Prompts -->
                <div class="gacg-card">
                    <h3>🖼️ Cài Đặt Prompt Hình Ảnh</h3>
                    
                    <div class="gacg-form-group">
                        <label for="image_prompt_template">Mẫu prompt hình ảnh</label>
                        <textarea name="image_prompt_template" id="image_prompt_template" rows="4" class="large-text"><?php 
                            echo esc_textarea($settings['image_prompt_template'] ?? $default_image_prompt); 
                        ?></textarea>
                        <p class="description">Sử dụng <code>[title]</code> và <code>[keyword]</code> để chèn thông tin động.</p>
                    </div>
                </div>

                <!-- Optimization Settings -->
                <div class="gacg-card">
                    <h3>🔧 Tối Ưu Hóa</h3>
                    
                    <div class="gacg-form-row">
                        <div>
                            <label>
                                <input type="checkbox" name="auto_publish" value="1" <?php checked($settings['auto_publish'] ?? 0, 1); ?> />
                                Tự động đăng bài sau khi tạo
                            </label>
                        </div>
                        
                        <div>
                            <label>
                                <input type="checkbox" name="seo_optimize" value="1" <?php checked($settings['seo_optimize'] ?? 1, 1); ?> />
                                Tự động tối ưu SEO
                            </label>
                        </div>
                    </div>
                    
                    <div class="gacg-form-row">
                        <div>
                            <label>
                                <input type="checkbox" name="auto_webp" value="1" <?php checked($settings['auto_webp'] ?? 1, 1); ?> />
                                Tự động chuyển đổi WEBP
                            </label>
                        </div>
                        
                        <div class="gacg-form-group">
                            <label for="webp_quality">Chất lượng WEBP</label>
                            <input type="number" name="webp_quality" id="webp_quality" 
                                   value="<?php echo esc_attr($settings['webp_quality'] ?? 80); ?>" 
                                   min="10" max="100" />
                            <small>Khuyên dùng 75-90</small>
                        </div>
                    </div>
                    
                    <div class="gacg-form-row">
                        <div class="gacg-form-group">
                            <label for="default_image_count">Số ảnh mặc định trong bài</label>
                            <input type="number" name="default_image_count" id="default_image_count" 
                                   value="<?php echo esc_attr($settings['default_image_count'] ?? 3); ?>" 
                                   min="1" max="10" />
                        </div>
                        
                        <div class="gacg-form-group">
                            <label for="default_internal_links">Số link nội bộ mặc định</label>
                            <input type="number" name="default_internal_links" id="default_internal_links" 
                                   value="<?php echo esc_attr($settings['default_internal_links'] ?? 3); ?>" 
                                   min="1" max="10" />
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <input type="submit" name="submit" value="💾 Lưu Cài Đặt" class="gacg-btn gacg-btn-large gacg-btn-success" />
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-api-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#api-test-result');
                var apiKey = $('#gemini_api_key').val();
                
                if (!apiKey) {
                    $result.html('<div class="gacg-alert gacg-alert-error">Vui lòng nhập API key trước</div>');
                    return;
                }
                
                $btn.prop('disabled', true).text('Đang test...');
                $result.empty();
                
                $.post(ajaxurl, {
                    action: 'gacg_test_api_connection',
                    nonce: '<?php echo wp_create_nonce('gacg_nonce'); ?>',
                    api_key: apiKey
                }, function(response) {
                    $btn.prop('disabled', false).text('🔄 Test Kết Nối');
                    
                    if (response.success) {
                        $result.html('<div class="gacg-alert gacg-alert-success">✅ Kết nối API thành công!</div>');
                    } else {
                        $result.html('<div class="gacg-alert gacg-alert-error">❌ Lỗi: ' + (response.data || 'Không thể kết nối') + '</div>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('🔄 Test Kết Nối');
                    $result.html('<div class="gacg-alert gacg-alert-error">❌ Lỗi kết nối mạng</div>');
                });
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Lưu cài đặt
     */
    private function save_settings() {
        $settings = array(
            'gemini_api_key' => sanitize_text_field($_POST['gemini_api_key'] ?? ''),
            'content_model' => sanitize_text_field($_POST['content_model'] ?? 'gemini-1.5-flash'),
            'image_model' => sanitize_text_field($_POST['image_model'] ?? 'gemini-2.0-flash-exp'),
            'default_content_prompt' => wp_kses_post($_POST['default_content_prompt'] ?? ''),
            'image_prompt_template' => wp_kses_post($_POST['image_prompt_template'] ?? ''),
            'auto_publish' => isset($_POST['auto_publish']) ? 1 : 0,
            'seo_optimize' => isset($_POST['seo_optimize']) ? 1 : 0,
            'auto_webp' => isset($_POST['auto_webp']) ? 1 : 0,
            'webp_quality' => intval($_POST['webp_quality'] ?? 80),
            'default_image_count' => intval($_POST['default_image_count'] ?? 3),
            'default_internal_links' => intval($_POST['default_internal_links'] ?? 3)
        );
        
        $updated = update_option('gacg_settings', $settings);
        
        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Cài đặt đã được lưu thành công!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Có lỗi khi lưu cài đặt. Vui lòng thử lại.</p></div>';
        }
    }
    
    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('gacg_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error('API key không được để trống');
        }
        
        // Test API call
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        
        $test_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => 'Trả lời ngắn gọn: "API kết nối thành công"')
                    )
                )
            ),
            'generationConfig' => array(
                'maxOutputTokens' => 20
            )
        );
        
        $response = wp_remote_post($api_url . '?key=' . $api_key, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($test_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Lỗi kết nối: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            wp_send_json_error('Lỗi API: ' . $data['error']['message']);
        }
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            wp_send_json_success('API kết nối thành công!');
        }
        
        wp_send_json_error('Phản hồi API không hợp lệ');
    }
    
    /**
     * AJAX: Generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('gacg_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (empty($title)) {
            wp_send_json_error('Vui lòng nhập tiêu đề');
        }
        
        $gemini_api = new GACG_Gemini_API();
        $content = $gemini_api->generate_content($title);
        
        if (is_wp_error($content)) {
            wp_send_json_error($content->get_error_message());
        }
        
        wp_send_json_success(array(
            'content' => $content,
            'title' => $title
        ));
    }
    
    /**
     * AJAX: Publish post
     */
    public function ajax_publish_post() {
        check_ajax_referer('gacg_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        $category = intval($_POST['category'] ?? 0);
        $publish_option = sanitize_text_field($_POST['publish_option'] ?? 'draft');
        
        if (empty($title) || empty($content)) {
            wp_send_json_error('Tiêu đề và nội dung không được để trống');
        }
        
        $post_status = ($publish_option === 'publish') ? 'publish' : 'draft';
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_status,
            'post_author' => get_current_user_id(),
            'post_type' => 'post'
        );
        
        if ($category > 0) {
            $post_data['post_category'] = array($category);
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'view_url' => get_permalink($post_id)
        ));
    }
    
    /**
     * AJAX: Bulk create posts
     */
    public function ajax_bulk_create_posts() {
        check_ajax_referer('gacg_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $category = intval($_POST['category'] ?? 0);
        $publish_option = sanitize_text_field($_POST['publish_option'] ?? 'draft');
        
        if (empty($title)) {
            wp_send_json_error('Tiêu đề không được để trống');
        }
        
        // Log the start of generation process
        $this->log_generation_action(0, 'content_generation', 'started', "Bắt đầu tạo nội dung cho: $title");
        
        // Generate content using Gemini API
        $gemini_api = new GACG_Gemini_API();
        $content = $gemini_api->generate_content($title);
        
        if (is_wp_error($content)) {
            $this->log_generation_action(0, 'content_generation', 'failed', $content->get_error_message());
            wp_send_json_error($content->get_error_message());
        }
        
        if (empty($content) || strlen(trim($content)) < 50) {
            $error_msg = 'Nội dung được tạo quá ngắn hoặc không hợp lệ';
            $this->log_generation_action(0, 'content_generation', 'failed', $error_msg);
            wp_send_json_error($error_msg);
        }
        
        $this->log_generation_action(0, 'content_generation', 'completed', "Đã tạo nội dung cho: $title");
        
        // Create WordPress post
        $post_status = ($publish_option === 'publish') ? 'publish' : 'draft';
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_status,
            'post_author' => get_current_user_id(),
            'post_type' => 'post'
        );
        
        if ($category > 0) {
            $post_data['post_category'] = array($category);
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            $this->log_generation_action(0, 'post_creation', 'failed', $post_id->get_error_message());
            wp_send_json_error($post_id->get_error_message());
        }
        
        $this->log_generation_action($post_id, 'post_creation', 'completed', "Đã tạo bài viết WordPress ID: $post_id");
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'view_url' => get_permalink($post_id),
            'content' => $content
        ));
    }
    
    /**
     * AJAX: Cancel scheduled post
     */
    public function ajax_cancel_scheduled_post() {
        check_ajax_referer('gacg_nonce', 'nonce');
        
        $scheduled_id = intval($_POST['scheduled_id'] ?? 0);
        
        if (!$scheduled_id) {
            wp_send_json_error('ID không hợp lệ');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gacg_scheduled_posts';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('id' => $scheduled_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Đã hủy bài viết thành công');
        } else {
            wp_send_json_error('Không thể hủy bài viết');
        }
    }
    
    /**
     * Log generation action to database
     */
    private function log_generation_action($post_id, $action, $status, $message, $processing_time = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gacg_generation_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'processing_time' => $processing_time,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%s')
        );
    }
}