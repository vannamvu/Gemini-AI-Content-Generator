<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class xử lý hình ảnh tự động
 */
class GACG_Image_Processor {
    
    /**
     * Tạo và upload hình ảnh từ URL
     */
    public function create_image_from_url($image_url, $title, $alt_text = '') {
        if (empty($image_url)) {
            return false;
        }
        
        // Download hình ảnh
        $image_data = wp_remote_get($image_url, array('timeout' => 30));
        
        if (is_wp_error($image_data)) {
            return false;
        }
        
        $image_content = wp_remote_retrieve_body($image_data);
        
        if (empty($image_content)) {
            return false;
        }
        
        // Tạo tên file
        $filename = sanitize_title($title) . '-' . time() . '.jpg';
        
        // Upload file
        $upload = wp_upload_bits($filename, null, $image_content);
        
        if ($upload['error']) {
            return false;
        }
        
        // Tạo attachment
        $attachment_id = $this->create_attachment($upload['file'], $upload['url'], $title, $alt_text);
        
        // Chuyển đổi sang WEBP nếu được bật
        $settings = get_option('gacg_settings', array());
        if ($settings['image_webp'] ?? 1) {
            $this->convert_to_webp($upload['file'], $attachment_id);
        }
        
        return $attachment_id;
    }
    
    /**
     * Tạo attachment trong WordPress
     */
    private function create_attachment($file_path, $file_url, $title, $alt_text) {
        $filetype = wp_check_filetype(basename($file_path), null);
        
        $attachment = array(
            'guid' => $file_url,
            'post_mime_type' => $filetype['type'],
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (!is_wp_error($attachment_id)) {
            // Tạo metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            // Set alt text
            if (!empty($alt_text)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }
        }
        
        return $attachment_id;
    }
    
    /**
     * Chuyển đổi hình ảnh sang WEBP
     */
    public function convert_to_webp($file_path, $attachment_id) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $file_info = pathinfo($file_path);
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        
        // Tạo image resource dựa trên loại file
        $image = null;
        $mime_type = get_post_mime_type($attachment_id);
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
        }
        
        if ($image) {
            // Chuyển đổi sang WEBP
            $success = imagewebp($image, $webp_path, 80);
            imagedestroy($image);
            
            if ($success) {
                // Cập nhật attachment để sử dụng file WEBP
                $upload_dir = wp_upload_dir();
                $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
                
                wp_update_post(array(
                    'ID' => $attachment_id,
                    'guid' => $webp_url
                ));
                
                update_post_meta($attachment_id, '_wp_attached_file', str_replace($upload_dir['basedir'] . '/', '', $webp_path));
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Tạo hình ảnh featured cho bài viết
     */
    public function set_featured_image($post_id, $image_url, $title) {
        $attachment_id = $this->create_image_from_url($image_url, $title);
        
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }
        
        return false;
    }
    
    /**
     * Tối ưu kích thước hình ảnh
     */
    public function optimize_image_size($file_path, $max_width = 1200, $quality = 80) {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }
        
        $image_info = getimagesize($file_path);
        
        if (!$image_info) {
            return false;
        }
        
        list($width, $height, $type) = $image_info;
        
        // Không cần resize nếu hình đã nhỏ hơn max_width
        if ($width <= $max_width) {
            return true;
        }
        
        // Tính tỷ lệ mới
        $ratio = $max_width / $width;
        $new_width = $max_width;
        $new_height = intval($height * $ratio);
        
        // Tạo image resource
        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($file_path);
                break;
        }
        
        if (!$source) {
            return false;
        }
        
        // Tạo hình mới với kích thước đã resize
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Giữ transparency cho PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        // Resize
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Lưu hình đã resize
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($resized, $file_path, $quality);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($resized, $file_path);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($resized, $file_path);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($resized);
        
        return $success;
    }
    
    /**
     * Tạo alt text tự động cho hình ảnh
     */
    public function generate_auto_alt_text($title, $content = '') {
        $gemini_api = new GACG_Gemini_API();
        
        // Trích xuất context từ nội dung
        $context = wp_strip_all_tags($content);
        $context = wp_trim_words($context, 50);
        
        $description = "Hình ảnh minh họa cho bài viết: {$title}";
        if (!empty($context)) {
            $description .= ". Nội dung liên quan: {$context}";
        }
        
        return $gemini_api->generate_alt_text($description);
    }
}