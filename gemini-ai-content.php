<?php
/**
 * Plugin Name: Gemini AI Content Generator
 * Plugin URI: https://github.com/vuvannamviet-sys/Gemini-AI-Content-Generator
 * Description: Tạo nội dung tự động bằng AI Gemini với tối ưu SEO và quản lý lên lịch đăng bài thông minh. Hỗ trợ tạo bài viết hàng loạt, tối ưu hình ảnh WEBP, liên kết nội bộ tự động.
 * Version: 1.0.0
 * Author: Vũ Văn Nam Việt
 * Author URI: mailto:vuvannamviet@gmail.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gemini-ai-content-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GACG_VERSION', '1.0.0');
define('GACG_PLUGIN_FILE', __FILE__);
define('GACG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GACG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GACG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Gemini_AI_Content_Generator {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('Gemini_AI_Content_Generator', 'uninstall'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('gemini-ai-content-generator', false, dirname(GACG_PLUGIN_BASENAME) . '/languages');
        
        // Include required files
        $this->include_files();
        
        // Initialize components
        $this->init_components();
        
        // Add action links
        add_filter('plugin_action_links_' . GACG_PLUGIN_BASENAME, array($this, 'action_links'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        $includes = array(
            'includes/class-gemini-api.php',
            'includes/class-admin.php',
            'includes/class-scheduler.php',
            'includes/class-image-processor.php',
            'includes/class-seo-optimizer.php'
        );
        
        foreach ($includes as $file) {
            $file_path = GACG_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new GACG_Admin();
        }
        
        // Initialize scheduler
        new GACG_Scheduler();
        
        // Initialize other components as needed
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Gemini AI Content Generator activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('gacg_process_scheduled_posts');
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Gemini AI Content Generator deactivated');
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove options
        delete_option('gacg_settings');
        delete_option('gacg_version');
        
        // Drop database tables
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gacg_scheduled_posts");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gacg_generation_log");
        
        // Clear scheduled events
        wp_clear_scheduled_hook('gacg_process_scheduled_posts');
        
        // Log uninstall
        error_log('Gemini AI Content Generator uninstalled');
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Scheduled posts table
        $table_scheduled = $wpdb->prefix . 'gacg_scheduled_posts';
        $sql_scheduled = "CREATE TABLE $table_scheduled (
            id int(11) NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            content longtext,
            post_type varchar(20) DEFAULT 'post',
            publish_date date NOT NULL,
            publish_time time NOT NULL,
            status varchar(20) DEFAULT 'pending',
            categories text,
            tags text,
            meta_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY publish_date (publish_date)
        ) $charset_collate;";
        
        // Generation log table
        $table_log = $wpdb->prefix . 'gacg_generation_log';
        $sql_log = "CREATE TABLE $table_log (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11),
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            processing_time float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY action (action),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_scheduled);
        dbDelta($sql_log);
        
        // Update version
        update_option('gacg_version', GACG_VERSION);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_settings = array(
            'gemini_api_key' => '',
            'content_model' => 'gemini-1.5-flash',
            'image_model' => 'gemini-2.0-flash-exp',
            'default_content_prompt' => 'Viết một bài viết chi tiết và chuyên nghiệp về chủ đề "[title]" bằng tiếng Việt. Bài viết cần có ít nhất 1500 từ, cấu trúc rõ ràng với các tiêu đề phụ H2, H3, nội dung phong phú và tối ưu SEO. Sử dụng phong cách viết tự nhiên, dễ đọc và hấp dẫn người đọc.',
            'image_prompt_template' => 'Tạo hình ảnh chất lượng cao, chuyên nghiệp về chủ đề "[title]". Hình ảnh cần rõ nét, đẹp mắt, có tính minh họa cao và phù hợp với nội dung bài viết. Từ khóa liên quan: [keyword]. Phong cách: hiện đại, tối giản, màu sắc hài hòa.',
            'auto_publish' => 0,
            'seo_optimize' => 1,
            'auto_webp' => 1,
            'webp_quality' => 80,
            'default_image_count' => 3,
            'default_internal_links' => 3
        );
        
        add_option('gacg_settings', $default_settings);
    }
    
    /**
     * Add action links
     */
    public function action_links($links) {
        $action_links = array(
            '<a href="' . admin_url('admin.php?page=gemini-ai-content-settings') . '">Cài đặt</a>',
            '<a href="' . admin_url('admin.php?page=gemini-ai-content-create') . '">Tạo bài viết</a>',
            '<a href="https://github.com/vuvannamviet-sys/Gemini-AI-Content-Generator" target="_blank">GitHub</a>'
        );
        
        return array_merge($action_links, $links);
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>Gemini AI Content Generator:</strong> Plugin yêu cầu PHP phiên bản 7.4 hoặc cao hơn. Bạn đang sử dụng PHP <?php echo PHP_VERSION; ?>.</p>
        </div>
        <?php
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>Gemini AI Content Generator:</strong> Plugin yêu cầu WordPress phiên bản 5.0 hoặc cao hơn. Bạn đang sử dụng WordPress <?php echo get_bloginfo('version'); ?>.</p>
        </div>
        <?php
    }
}

// Initialize plugin
Gemini_AI_Content_Generator::get_instance();