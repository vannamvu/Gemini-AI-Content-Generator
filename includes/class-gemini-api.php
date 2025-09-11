<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class xử lý API Gemini AI
 */
class GACG_Gemini_API {
    
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct() {
        $settings = get_option('gacg_settings', array());
        $this->api_key = $settings['gemini_api_key'] ?? '';
    }
    
    /**
     * Tạo nội dung từ tiêu đề
     */
    public function generate_content($title, $prompt_id = null) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Vui lòng cấu hình API key trong cài đặt');
        }
        
        // Lấy prompt từ database
        $prompt = $this->get_prompt($prompt_id);
        
        // Thay thế {title} trong prompt
        $final_prompt = str_replace('{title}', $title, $prompt);
        
        // Chuẩn bị data gửi API
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $final_prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048
            )
        );
        
        // Gửi request
        $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message']);
        }
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return new WP_Error('no_content', 'Không thể tạo nội dung');
    }
    
    /**
     * Lấy prompt từ database
     */
    private function get_prompt($prompt_id) {
        global $wpdb;
        
        if (empty($prompt_id)) {
            return 'Viết một bài viết chi tiết và chuyên nghiệp về chủ đề "{title}" bằng tiếng Việt. Bài viết cần có ít nhất 1000 từ, cấu trúc rõ ràng với các tiêu đề phụ, và tối ưu SEO.';
        }
        
        $table_name = $wpdb->prefix . 'gacg_custom_prompts';
        $prompt = $wpdb->get_var($wpdb->prepare(
            "SELECT prompt_text FROM $table_name WHERE id = %d",
            $prompt_id
        ));
        
        return $prompt ?: 'Viết một bài viết chi tiết về "{title}" bằng tiếng Việt.';
    }
    
    /**
     * Tạo alt text cho hình ảnh
     */
    public function generate_alt_text($image_description) {
        if (empty($this->api_key)) {
            return '';
        }
        
        $prompt = "Tạo alt text SEO cho hình ảnh về: {$image_description}. Alt text cần ngắn gọn, mô tả chính xác và tối ưu SEO.";
        
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.5,
                'maxOutputTokens' => 100
            )
        );
        
        $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return strip_tags($data['candidates'][0]['content']['parts'][0]['text']);
        }
        
        return '';
    }
    
    /**
     * Tạo meta description
     */
    public function generate_meta_description($content, $title) {
        if (empty($this->api_key)) {
            return '';
        }
        
        $prompt = "Tạo meta description SEO cho bài viết có tiêu đề '{$title}'. Meta description cần dài 150-160 ký tự, hấp dẫn và chứa từ khóa chính.";
        
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.6,
                'maxOutputTokens' => 80
            )
        );
        
        $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return strip_tags($data['candidates'][0]['content']['parts'][0]['text']);
        }
        
        return '';
    }
}