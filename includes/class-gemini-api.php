<?php
if (!defined('ABSPATH')) exit;

class GeminiAI_API {
    private $api_key;
    private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $debug = true; // Set true to enable debug logging

    public function __construct() {
        $this->api_key = get_option('gemini_ai_api_key');
        if ($this->debug) error_log('[GeminiAI_API] Constructed with API key: ' . $this->api_key);
    }

    public function validate_api_key() {
        if (empty($this->api_key)) {
            $this->log('API key missing');
            return new WP_Error('no_api_key', __('Gemini API key is not configured', 'gemini-ai-content'));
        }
        // Optionally: Test a simple connection here
        $result = $this->generate_content('Test kết nối API Gemini', array('max_tokens' => 10));
        if (is_wp_error($result)) {
            $this->log('API key invalid: ' . $result->get_error_message());
            return $result;
        }
        $this->log('API key valid');
        return true;
    }

    public function generate_content($prompt, $options = array()) {
        $defaults = array(
            'model' => 'gemini-pro',
            'max_tokens' => 2048,
            'temperature' => 0.7
        );
        $options = wp_parse_args($options, $defaults);

        $url = $this->base_url . $options['model'] . ':generateContent?key=' . $this->api_key;
        $body = array(
            'contents' => array(
                array('parts' => array(array('text' => $prompt)))
            ),
            'generationConfig' => array(
                'temperature' => $options['temperature'],
                'maxOutputTokens' => $options['max_tokens']
            )
        );
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 60
        ));
        if (is_wp_error($response)) {
            $this->log('API error: ' . $response->get_error_message());
            return $response;
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code !== 200) {
            $this->log('API response error: ' . $body);
            return new WP_Error('api_error', $body);
        }
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        $this->log('Invalid API response');
        return new WP_Error('invalid_response', __('Invalid response from Gemini API', 'gemini-ai-content'));
    }

    private function log($message) {
        if ($this->debug) error_log('[GeminiAI_API] ' . $message);
    }
}
