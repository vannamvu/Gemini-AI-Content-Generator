<?php
if (!defined('ABSPATH')) exit;

class GeminiAI_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_settings_page() {
        add_menu_page(
            __('Gemini AI Content', 'gemini-ai-content'),
            __('Gemini AI Content', 'gemini-ai-content'),
            'manage_options',
            'gemini-ai-content',
            array($this, 'settings_page'),
            'dashicons-admin-generic'
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Gemini AI Content Settings', 'gemini-ai-content'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('gemini_ai_settings');
                    do_settings_sections('gemini_ai_settings');
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_assets($hook) {
        if ($hook === 'toplevel_page_gemini-ai-content') {
            wp_enqueue_style('gemini-ai-admin', GEMINI_AI_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('gemini-ai-admin', GEMINI_AI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), null, true);
        }
    }
}
