<?php
/**
 * Plugin Name: ChatGPT WP Helper
 * Description: Creates shortcode for to ask questions using ChatGPT.
 * Version: 1.0.0
 * Author: Ilia Dioleti
 * Author URI: https://github.com/IliaDi/
 **/

require_once plugin_dir_path(__FILE__) . 'wp-chatgpt-shortcode.php';

// Enqueue scripts
function chatbot_chatgpt_enqueue_scripts() {
    wp_enqueue_script('chatbot-chatgpt-js', plugin_dir_url(__FILE__) . 'js/wp-chatgpt-chatbot.js', array('jquery'), '1.0.0', true);

    wp_localize_script('chatbot-chatgpt-js', 'chatbot_chatgpt_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'api_key' => esc_attr(get_option('chatgpt-api-key')),
    ));
}
add_action('wp_enqueue_scripts', 'chatbot_chatgpt_enqueue_scripts');

// Add settings page
function chatgpt_settings_page() {
    add_options_page( 'ChatGPT Plugin Settings', 'ChatGPT', 'manage_options', 'chatgpt-plugin', 'chatgpt_render_settings_page' );
}
add_action( 'admin_menu', 'chatgpt_settings_page' );

// Render settings page
function chatgpt_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>ChatGPT Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'chatgpt-plugin-settings' ); ?>
            <?php do_settings_sections( 'chatgpt-plugin-settings' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register plugin settings
function chatgpt_register_settings() {
    register_setting( 'chatgpt-plugin-settings', 'chatgpt-api-key' );
}
add_action( 'admin_init', 'chatgpt_register_settings' );

// Add settings fields
function chatgpt_settings_fields() {
    add_settings_section( 'chatgpt-api-settings', 'API Settings', 'chatgpt_api_settings_section_callback', 'chatgpt-plugin-settings' );
    add_settings_field( 'chatgpt-api-key', 'API Key', 'chatgpt_api_key_field_callback', 'chatgpt-plugin-settings', 'chatgpt-api-settings' );
}
add_action( 'admin_init', 'chatgpt_settings_fields' );

// API Settings section callback
function chatgpt_api_settings_section_callback() {
    echo 'Enter your ChatGPT API settings below:';
}

// API Key field callback
function chatgpt_api_key_field_callback() {
    $api_key = get_option( 'chatgpt-api-key' );
    echo '<input type="text" name="chatgpt-api-key" value="' . esc_attr( $api_key ) . '" />';
}

// Handle Ajax requests
function chatgpt_send_message() {
    // Get the save API key
    $api_key = esc_attr(get_option('chatgpt-api-key'));
    $model = 'gpt-3.5-turbo';
    $max_tokens = 200;
    $message = sanitize_text_field($_POST['message']);

    // Check API key and message
    if (!$api_key || !$message) {
        wp_send_json_error('Invalid API key or message');
    }

    // Send message to ChatGPT API
    $response = chatgpt_api_call($api_key, $message);

    // Return response
    wp_send_json_success($response);
}

add_action('wp_ajax_chatgpt_send_message', 'chatgpt_send_message');
add_action('wp_ajax_nopriv_chatgpt_send_message', 'chatgpt_send_message');

// Call the ChatGPT API
function chatgpt_api_call($api_key, $message) {
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    $body = array(
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 200,
        'temperature' => 0.7,

        'messages' => array(array('role' => 'user', 'content' => $message)),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 60, 
    );

    $response = wp_remote_post($api_url, $args);

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message().' Please make sure you have entered a valid API key in the Settings page. ';
    }

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['choices']) && !empty($response_body['choices'])) {
        // Handle the response from the chat engine
        return $response_body['choices'][0]['message']['content'];
    } else {
        // Handle any errors that are returned from the chat engine
        return 'Error: Unable to fetch response from ChatGPT API. Please make sure you have entered a valid API key in the Settings page. ';
    }
}
