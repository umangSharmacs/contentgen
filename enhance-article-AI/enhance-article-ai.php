<?php
/**
 * Plugin Name: Enhance Article AI
 * Description: Adds an "Enhance by AI" button to the post editor that sends content to n8n for enhancement
 * Version: 1.1.0
 * Author: Umang Sharma
 * Text Domain: enhance-article-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EnhanceArticleAI {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_enhance_button_meta_box'));
        add_action('wp_ajax_enhance_article_ai', array($this, 'handle_enhance_request'));
        add_action('wp_ajax_nopriv_enhance_article_ai', array($this, 'handle_enhance_request'));
    }
    
    public function enqueue_scripts($hook) {
        
        // Only load on post.php and post-new.php
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_script(
            'enhance-article-ai',
            plugin_dir_url(__FILE__) . 'js/enhance-article-ai.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('enhance-article-ai', 'enhanceArticleAI', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('enhance_article_ai_nonce'),
            'enhancing_text' => 'Enhancing...',
            'enhance_text' => 'Enhance by AI'
        ));
        
        wp_enqueue_style(
            'enhance-article-ai',
            plugin_dir_url(__FILE__) . 'css/enhance-article-ai.css',
            array(),
            '1.0.0'
        );
    }
    
    public function add_enhance_button_meta_box() {
        add_meta_box(
            'enhance-article-ai-box',
            'AI Enhancement',
            array($this, 'render_enhance_button'),
            'post',
            'side',
            'high'
        );
    }
    
    public function render_enhance_button($post) {
        ?>
        <div class="enhance-article-ai-container">
            <button type="button" id="enhance-article-ai-btn" class="button button-primary">
                <span class="button-text">Enhance by AI</span>
                <span class="spinner" style="display: none;"></span>
            </button>
            <p class="description">Send your post content to AI for enhancement</p>
        </div>
        <?php
    }
    
    public function handle_enhance_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'enhance_article_ai_nonce')) {
            wp_die('Security check failed');
        }
        
        $post_content = sanitize_textarea_field($_POST['content']);
        $post_title = sanitize_text_field($_POST['title']);
        
        if (empty($post_content)) {
            wp_send_json_error('No content provided');
        }
        
        // Prepare data for n8n
        $data = array(
            'title' => $post_title,
            'content' => $post_content,
            'timestamp' => current_time('timestamp')
        );
        
        // Send to n8n webhook
        $n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
        
        if (empty($n8n_webhook_url)) {
            wp_send_json_error('N8N webhook URL not configured');
        }
        
        $response = wp_remote_post($n8n_webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to send to N8N: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error('N8N returned error code: ' . $response_code);
        }
        
        // Try to decode the response
        $enhanced_content = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not JSON, treat as plain text
            $enhanced_content = array(
                'enhanced_content' => $response_body,
                'original_content' => $post_content
            );
        } else {
            // Handle the specific N8N response format
            error_log(print_r($enhanced_content, true));
            
            $first_item = $enhanced_content;
            error_log(print_r($first_item, true));

            // Extract the fields from your N8N response format
            $enhanced_content = array(
                'enhanced_text' => isset($first_item['enhanced_text']) ? $first_item['enhanced_text'] : '',
                'subcategory' => isset($first_item['subcategory']) ? $first_item['subcategory'] : '',
                'category' => isset($first_item['category']) ? $first_item['category'] : '',
                'tags' => isset($first_item['tags']) ? $first_item['tags'] : '',
                'pillar_page' => isset($first_item['pillar_page']) ? $first_item['pillar_page'] : false,
                'original_content' => $post_content,
                'original_title' => $post_title
            );
        }
        
        wp_send_json_success($enhanced_content);
    }
}

// Initialize the plugin
new EnhanceArticleAI();

// Add settings page
add_action('admin_menu', function() {
    add_options_page(
        'Enhance Article AI Settings',
        'Enhance Article AI',
        'manage_options',
        'enhance-article-ai-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>Enhance Article AI Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('enhance_article_ai_settings');
                    do_settings_sections('enhance_article_ai_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">N8N Webhook URL</th>
                            <td>
                                <input type="url" name="enhance_article_ai_webhook_url" 
                                       value="<?php echo esc_attr(get_option('enhance_article_ai_webhook_url')); ?>" 
                                       class="regular-text" />
                                <p class="description">Enter your N8N webhook URL here</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    );
});

add_action('admin_init', function() {
    register_setting('enhance_article_ai_settings', 'enhance_article_ai_webhook_url');
}); 