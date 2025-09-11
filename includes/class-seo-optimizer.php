<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class tối ưu SEO cho nội dung
 */
class GACG_SEO_Optimizer {
    
    /**
     * Tối ưu nội dung cho SEO
     */
    public function optimize_content($content, $title) {
        // Thêm internal links
        $content = $this->add_internal_links($content);
        
        // Tối ưu cấu trúc heading
        $content = $this->optimize_headings($content, $title);
        
        // Thêm schema markup
        $content = $this->add_schema_markup($content, $title);
        
        return $content;
    }
    
    /**
     * Thêm internal links tự động
     */
    public function add_internal_links($content) {
        // Lấy danh sách bài viết liên quan
        $related_posts = $this->get_related_posts($content);
        
        if (empty($related_posts)) {
            return $content;
        }
        
        $link_count = 0;
        $max_links = 3; // Tối đa 3 internal links
        
        foreach ($related_posts as $post) {
            if ($link_count >= $max_links) {
                break;
            }
            
            $post_title = get_the_title($post->ID);
            $post_url = get_permalink($post->ID);
            
            // Tìm từ khóa phù hợp để thêm link
            $keywords = $this->extract_keywords($post_title);
            
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false && $link_count < $max_links) {
                    // Thay thế từ khóa đầu tiên bằng link
                    $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                    $replacement = '<a href="' . $post_url . '" title="' . esc_attr($post_title) . '">' . $keyword . '</a>';
                    
                    $content = preg_replace($pattern, $replacement, $content, 1);
                    $link_count++;
                    break;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Tối ưu cấu trúc heading
     */
    public function optimize_headings($content, $title) {
        // Đảm bảo có H1 (thường là title)
        if (stripos($content, '<h1') === false) {
            $content = '<h1>' . $title . '</h1>' . $content;
        }
        
        // Kiểm tra và sửa cấu trúc heading
        $content = $this->fix_heading_structure($content);
        
        return $content;
    }
    
    /**
     * Sửa cấu trúc heading để tuân thủ SEO
     */
    private function fix_heading_structure($content) {
        // Pattern để tìm các heading
        $pattern = '/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        $current_level = 1;
        
        foreach ($matches as $match) {
            $level = intval($match[1]);
            $heading_text = $match[2];
            $full_match = $match[0];
            
            // Điều chỉnh level nếu cần
            if ($level > $current_level + 1) {
                $new_level = $current_level + 1;
                $new_heading = '<h' . $new_level . '>' . $heading_text . '</h' . $new_level . '>';
                $content = str_replace($full_match, $new_heading, $content);
                $current_level = $new_level;
            } else {
                $current_level = $level;
            }
        }
        
        return $content;
    }
    
    /**
     * Thêm schema markup
     */
    public function add_schema_markup($content, $title) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'author' => array(
                '@type' => 'Person',
                'name' => get_bloginfo('name')
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'datePublished' => date('c'),
            'dateModified' => date('c')
        );
        
        $schema_json = '<script type="application/ld+json">' . json_encode($schema) . '</script>';
        
        // Thêm schema vào cuối nội dung
        $content .= $schema_json;
        
        return $content;
    }
    
    /**
     * Lấy bài viết liên quan
     */
    private function get_related_posts($content, $limit = 5) {
        // Trích xuất từ khóa từ nội dung
        $keywords = $this->extract_content_keywords($content);
        
        if (empty($keywords)) {
            return array();
        }
        
        // Tìm bài viết có chứa từ khóa
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            's' => implode(' ', array_slice($keywords, 0, 3)),
            'meta_query' => array(
                array(
                    'key' => '_gacg_processed',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        
        $query = new WP_Query($args);
        
        return $query->posts;
    }
    
    /**
     * Trích xuất từ khóa từ nội dung
     */
    private function extract_content_keywords($content, $limit = 10) {
        // Loại bỏ HTML tags
        $text = wp_strip_all_tags($content);
        
        // Chuyển về chữ thường
        $text = strtolower($text);
        
        // Loại bỏ dấu câu
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Tách thành các từ
        $words = preg_split('/\s+/', $text);
        
        // Loại bỏ từ ngắn và stop words
        $stop_words = array('và', 'của', 'có', 'là', 'được', 'cho', 'với', 'từ', 'trong', 'một', 'các', 'này', 'đó', 'để', 'về', 'như', 'sau', 'trước');
        
        $keywords = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 3 && !in_array($word, $stop_words)) {
                $keywords[] = $word;
            }
        }
        
        // Đếm tần suất và lấy từ khóa phổ biến nhất
        $word_count = array_count_values($keywords);
        arsort($word_count);
        
        return array_slice(array_keys($word_count), 0, $limit);
    }
    
    /**
     * Trích xuất từ khóa từ tiêu đề
     */
    private function extract_keywords($title) {
        $words = explode(' ', strtolower($title));
        $keywords = array();
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        return $keywords;
    }
    
    /**
     * Tạo meta description tự động
     */
    public function generate_meta_description($content, $title) {
        $gemini_api = new GACG_Gemini_API();
        return $gemini_api->generate_meta_description($content, $title);
    }
    
    /**
     * Tối ưu title cho SEO
     */
    public function optimize_title($title) {
        // Giới hạn độ dài title (50-60 ký tự)
        if (strlen($title) > 60) {
            $title = wp_trim_words($title, 8, '...');
        }
        
        // Thêm site name nếu cần
        $site_name = get_bloginfo('name');
        if (stripos($title, $site_name) === false) {
            $title .= ' - ' . $site_name;
        }
        
        return $title;
    }
    
    /**
     * Phân tích mật độ từ khóa
     */
    public function analyze_keyword_density($content, $keyword) {
        $text = wp_strip_all_tags($content);
        $text = strtolower($text);
        $keyword = strtolower($keyword);
        
        $total_words = str_word_count($text);
        $keyword_count = substr_count($text, $keyword);
        
        $density = ($keyword_count / $total_words) * 100;
        
        return array(
            'keyword' => $keyword,
            'count' => $keyword_count,
            'total_words' => $total_words,
            'density' => round($density, 2),
            'optimal' => ($density >= 1 && $density <= 3)
        );
    }
}