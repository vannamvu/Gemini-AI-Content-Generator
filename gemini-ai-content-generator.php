<?php
/**
 * Plugin Name: Gemini AI Content Generator
 * Plugin URI: https://github.com/vuvannamviet-sys/Gemini-AI-Content-Generator
 * Description: WordPress plugin tạo nội dung tự động bằng AI Gemini với tối ưu SEO và quản lý lên lịch đăng bài thông minh
 * Version: 1.0.0
 * Author: VuVanNamViet
 * License: GPL v2 or later
 * Text Domain: gemini-ai-content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GEMINI_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEMINI_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GEMINI_AI_VERSION', '1.0.0');

// Include required files
require_once GEMINI_AI_PLUGIN_PATH . 'includes/class-gemini-api.php';
require_once GEMINI_AI_PLUGIN_PATH . 'includes/class-admin.php';
require_once GEMINI_AI_PLUGIN_PATH . 'includes/class-scheduler.php';
require_once GEMINI_AI_PLUGIN_PATH . 'includes/class-image-processor.php';
require_once GEMINI_AI_PLUGIN_PATH . 'includes/class-seo-optimizer.php';

/**
 * Main plugin class
 */
class GeminiAIContentGenerator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize admin interface
        if (is_admin()) {
            new GeminiAI_Admin();
        }
        
        // Initialize scheduler
        new GeminiAI_Scheduler();
        
        // Load text domain
        load_plugin_textdomain('gemini-ai-content', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('gemini_ai_process_queue')) {
            wp_schedule_event(time(), 'hourly', 'gemini_ai_process_queue');
        }
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('gemini_ai_process_queue');
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Content generation requests table
        $table_name = $wpdb->prefix . 'gemini_content_requests';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            keywords text NOT NULL,
            content_type varchar(50) NOT NULL,
            target_length int(11) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            progress int(3) DEFAULT 0,
            progress_details text,
            generated_content longtext,
            seo_score int(3) DEFAULT 0,
            scheduled_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Progress tracking table
        $progress_table = $wpdb->prefix . 'gemini_progress_tracking';
        
        $progress_sql = "CREATE TABLE $progress_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            step varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            started_at datetime,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY request_id (request_id)
        ) $charset_collate;";
        
        dbDelta($progress_sql);
    }
}

// Initialize the plugin
new GeminiAIContentGenerator();
