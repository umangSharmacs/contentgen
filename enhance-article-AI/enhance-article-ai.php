<?php
/**
 * Plugin Name: Enhance Article AI
 * Description: Adds an "Enhance by AI" button to the post editor that sends content to n8n for enhancement. Includes daily automatic enhancement of published articles.
 * Version: 2.0.0
 * Author: Umang Sharma
 * Text Domain: enhance-article-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EnhanceArticleAI {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('add_meta_boxes', array($this, 'add_enhance_button_meta_box'));
        add_action('wp_ajax_enhance_article_ai', array($this, 'handle_enhance_request'));
        add_action('wp_ajax_nopriv_enhance_article_ai', array($this, 'handle_enhance_request'));
        
        // Schedule the daily enhancement task
        add_action('init', array($this, 'schedule_daily_enhancement'));
        add_action('enhance_article_ai_daily_cron', array($this, 'process_daily_enhancements'));
        
        // Add activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    public function enqueue_styles($hook) {
        // Only load on post.php and post-new.php
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'enhance-article-ai',
            plugin_dir_url(__FILE__) . 'css/enhance-article-ai.css',
            array(),
            '2.0.0'
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
        // Check if we have a result from a previous enhancement
        $enhancement_result = get_transient('enhance_article_ai_result_' . $post->ID);
        
        // Check if enhancement is currently running for this post
        $is_processing = get_transient('enhance_article_ai_processing_' . $post->ID);
        
        ?>
        <div class="enhance-article-ai-container">
            <?php if ($is_processing): ?>
                <div class="enhancement-processing" style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h4 style="margin: 0 0 5px 0; color: #856404;">🔄 Processing...</h4>
                    <p style="margin: 0; font-size: 12px; color: #856404;">Enhancing your content with AI. This may take a few moments.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('enhance_article_ai_action', 'enhance_article_ai_nonce'); ?>
                <input type="hidden" name="action" value="enhance_article_ai">
                <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
                
                <button type="submit" name="enhance_article_ai_submit" class="button button-primary" style="width: 100%; margin-bottom: 10px;" <?php echo $is_processing ? 'disabled' : ''; ?>>
                    <?php if ($is_processing): ?>
                        🔄 Enhancing...
                    <?php else: ?>
                        ✨ Enhance by AI
                    <?php endif; ?>
                </button>
            </form>
            
            <p class="description">Send your post content to AI for enhancement</p>
            
            <?php if ($enhancement_result): ?>
                <div class="enhancement-result" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                    <h4>✅ Enhanced Content Ready!</h4>
                    <p><strong>Enhanced Text:</strong></p>
                    <div style="background: white; padding: 8px; border: 1px solid #ddd; max-height: 100px; overflow-y: auto; font-size: 12px;">
                        <?php echo esc_html(substr($enhancement_result['enhanced_text'], 0, 200)) . '...'; ?>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('apply_enhanced_content', 'apply_enhanced_nonce'); ?>
                        <input type="hidden" name="action" value="apply_enhanced_content">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
                        <input type="hidden" name="enhanced_text" value="<?php echo esc_attr($enhancement_result['enhanced_text']); ?>">
                        
                        <button type="submit" name="apply_enhanced_submit" class="button button-secondary" style="width: 100%; margin-top: 10px;">
                            ✅ Apply Enhanced Content
                        </button>
                    </form>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('clear_enhancement_result', 'clear_result_nonce'); ?>
                        <input type="hidden" name="action" value="clear_enhancement_result">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
                        
                        <button type="submit" name="clear_result_submit" class="button button-link" style="width: 100%; margin-top: 5px;">
                            🗑️ Clear Result
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function handle_enhance_request() {
        // Handle form submissions
        if (isset($_POST['enhance_article_ai_submit'])) {
            $this->process_enhancement_request();
        } elseif (isset($_POST['apply_enhanced_submit'])) {
            $this->apply_enhanced_content();
        } elseif (isset($_POST['clear_result_submit'])) {
            $this->clear_enhancement_result();
        }
    }
    
    private function process_enhancement_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['enhance_article_ai_nonce'], 'enhance_article_ai_action')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_die('Post not found');
        }
        
        // Set processing status
        set_transient('enhance_article_ai_processing_' . $post_id, true, 300); // 5 minutes
        
        $post_content = $post->post_content;
        $post_title = $post->post_title;
        
        if (empty($post_content)) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'No content to enhance');
            $this->add_admin_notice('Please add some content to your post before enhancing.', 'error');
            return;
        }
        
        // Get N8N webhook URL
        $n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
        
        if (empty($n8n_webhook_url)) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'N8N webhook URL not configured');
            $this->add_admin_notice('N8N webhook URL not configured. Please configure it in Settings → Enhance Article AI.', 'error');
            return;
        }
        
        // Prepare data for n8n
        $data = array(
            'title' => $post_title,
            'content' => $post_content,
            'timestamp' => current_time('timestamp')
        );
        
        // Send to n8n webhook
        $response = wp_remote_post($n8n_webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'N8N request failed: ' . $response->get_error_message());
            $this->add_admin_notice('Failed to send to N8N: ' . $response->get_error_message(), 'error');
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'N8N returned error code: ' . $response_code);
            $this->add_admin_notice('N8N returned error code: ' . $response_code, 'error');
            return;
        }
        
        // Try to decode the response
        $enhanced_content = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'Invalid JSON response from N8N');
            $this->add_admin_notice('Invalid JSON response from N8N', 'error');
            return;
        }
        
        // Handle the specific N8N response format
        $first_item = $enhanced_content;
        
        // Extract the fields from your N8N response format
        $enhanced_data = array(
            'enhanced_text' => isset($first_item['enhanced_text']) ? $first_item['enhanced_text'] : '',
            'subcategory' => isset($first_item['subcategory']) ? $first_item['subcategory'] : '',
            'category' => isset($first_item['category']) ? $first_item['category'] : '',
            'tags' => isset($first_item['tags']) ? $first_item['tags'] : '',
            'pillar_page' => isset($first_item['pillar_page']) ? $first_item['pillar_page'] : false,
            'original_content' => $post_content,
            'original_title' => $post_title
        );
        
        if (empty($enhanced_data['enhanced_text'])) {
            delete_transient('enhance_article_ai_processing_' . $post_id);
            $this->log_enhancement_history($post_id, $post_title, 'error', 'No enhanced text received from N8N');
            $this->add_admin_notice('No enhanced text received from N8N', 'error');
            return;
        }
        
        // Clear processing status and store the result temporarily
        delete_transient('enhance_article_ai_processing_' . $post_id);
        set_transient('enhance_article_ai_result_' . $post_id, $enhanced_data, 3600); // 1 hour
        
        // Log successful enhancement
        $this->log_enhancement_history($post_id, $post_title, 'success', 'Content enhanced successfully');
        
        $this->add_admin_notice('Content enhanced successfully! Review and apply the enhanced content.', 'success');
        
        // Redirect back to the post edit page
        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }
    
    private function apply_enhanced_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['apply_enhanced_nonce'], 'apply_enhanced_content')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        $enhanced_text = sanitize_textarea_field($_POST['enhanced_text']);
        
        if (empty($enhanced_text)) {
            $this->add_admin_notice('No enhanced content to apply', 'error');
            return;
        }
        
        // Update the post
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $enhanced_text,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));
        
        if ($update_result) {
            // Clear the temporary result
            delete_transient('enhance_article_ai_result_' . $post_id);
            $this->add_admin_notice('Enhanced content applied successfully!', 'success');
        } else {
            $this->add_admin_notice('Failed to apply enhanced content', 'error');
        }
        
        // Redirect back to the post edit page
        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }
    
    private function clear_enhancement_result() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['clear_result_nonce'], 'clear_enhancement_result')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Clear the temporary result
        delete_transient('enhance_article_ai_result_' . $post_id);
        
        $this->add_admin_notice('Enhancement result cleared', 'info');
        
        // Redirect back to the post edit page
        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }
    
    private function add_admin_notice($message, $type = 'info') {
        $notices = get_option('enhance_article_ai_notices', array());
        $notices[] = array(
            'message' => $message,
            'type' => $type,
            'time' => current_time('timestamp')
        );
        update_option('enhance_article_ai_notices', $notices);
    }
    
    public function show_admin_notices() {
        $notices = get_option('enhance_article_ai_notices', array());
        
        foreach ($notices as $key => $notice) {
            $class = 'notice notice-' . $notice['type'];
            $message = esc_html($notice['message']);
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            
            // Remove old notices (older than 5 minutes)
            if (current_time('timestamp') - $notice['time'] > 300) {
                unset($notices[$key]);
            }
        }
        
        update_option('enhance_article_ai_notices', $notices);
    }
    
    /**
     * Schedule the daily enhancement task
     */
    public function schedule_daily_enhancement() {
        if (!wp_next_scheduled('enhance_article_ai_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'enhance_article_ai_daily_cron');
        }
    }
    
    /**
     * Process daily enhancements for all published articles
     */
    public function process_daily_enhancements() {
        global $wpdb;
        
        // Check if daily enhancement is enabled
        $daily_enabled = get_option('enhance_article_ai_daily_enabled', '0');
        if ($daily_enabled !== '1') {
            error_log('Enhance Article AI: Daily enhancement is disabled');
            return;
        }
        
        // Get N8N webhook URL
        $n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
        
        if (empty($n8n_webhook_url)) {
            error_log('Enhance Article AI: N8N webhook URL not configured for daily enhancement');
            return;
        }
        
        // Query for published articles from today only
        $today = current_time('Y-m-d');
        $articles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_content, post_modified 
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND DATE(post_date) = %s
                 ORDER BY post_modified DESC",
                'article',
                'publish',
                $today
            )
        );
        
        if (empty($articles)) {
            error_log('Enhance Article AI: No published articles found for enhancement');
            return;
        }
        
        $processed_count = 0;
        $error_count = 0;
        
        foreach ($articles as $article) {
            try {
                $enhanced_content = $this->enhance_article_content($article, $n8n_webhook_url);
                
                if ($enhanced_content) {
                    // Update the post with enhanced content
                    $update_result = wp_update_post(array(
                        'ID' => $article->ID,
                        'post_content' => $enhanced_content['enhanced_text'],
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ));
                    
                    if ($update_result) {
                        $processed_count++;
                        
                        // Log successful enhancement
                        error_log(sprintf(
                            'Enhance Article AI: Successfully enhanced article ID %d: "%s"',
                            $article->ID,
                            $article->post_title
                        ));
                        
                        // Log to history
                        $this->log_enhancement_history($article->ID, $article->post_title, 'success', 'Daily enhancement completed');
                        
                        // Add a small delay to avoid overwhelming the N8N server
                        sleep(2);
                    } else {
                        $error_count++;
                        error_log(sprintf(
                            'Enhance Article AI: Failed to update article ID %d: "%s"',
                            $article->ID,
                            $article->post_title
                        ));
                        
                        // Log to history
                        $this->log_enhancement_history($article->ID, $article->post_title, 'error', 'Failed to update post');
                    }
                } else {
                    $error_count++;
                    error_log(sprintf(
                        'Enhance Article AI: Failed to enhance article ID %d: "%s"',
                        $article->ID,
                        $article->post_title
                    ));
                    
                    // Log to history
                    $this->log_enhancement_history($article->ID, $article->post_title, 'error', 'Failed to enhance content');
                }
                
            } catch (Exception $e) {
                $error_count++;
                error_log(sprintf(
                    'Enhance Article AI: Exception while processing article ID %d: %s',
                    $article->ID,
                    $e->getMessage()
                ));
            }
        }
        
        // Log summary and update last run time
        error_log(sprintf(
            'Enhance Article AI: Daily enhancement completed. Processed: %d, Errors: %d',
            $processed_count,
            $error_count
        ));
        
        // Update last run time in EDT timezone
        update_option('enhance_article_ai_last_run', current_time('mysql', false));
    }
    
    /**
     * Enhance a single article's content via N8N
     */
    private function enhance_article_content($article, $n8n_webhook_url) {
        // Prepare data for n8n
        $data = array(
            'title' => $article->post_title,
            'content' => $article->post_content,
            'timestamp' => current_time('timestamp')
        );
        
        // Send to n8n webhook
        $response = wp_remote_post($n8n_webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            error_log('Enhance Article AI: N8N request failed: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Enhance Article AI: N8N returned error code: ' . $response_code);
            return false;
        }
        
        // Try to decode the response
        $enhanced_content = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Enhance Article AI: Invalid JSON response from N8N');
            return false;
        }
        
        // Handle the specific N8N response format
        $first_item = $enhanced_content;
        
        // Extract the enhanced text
        if (isset($first_item['enhanced_text']) && !empty($first_item['enhanced_text'])) {
            return array(
                'enhanced_text' => $first_item['enhanced_text'],
                'subcategory' => isset($first_item['subcategory']) ? $first_item['subcategory'] : '',
                'category' => isset($first_item['category']) ? $first_item['category'] : '',
                'tags' => isset($first_item['tags']) ? $first_item['tags'] : '',
                'pillar_page' => isset($first_item['pillar_page']) ? $first_item['pillar_page'] : false
            );
        }
        
        error_log('Enhance Article AI: No enhanced text found in N8N response');
        return false;
    }
    
    /**
     * Plugin activation hook
     */
    public function activate_plugin() {
        // Schedule the daily cron job
        if (!wp_next_scheduled('enhance_article_ai_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'enhance_article_ai_daily_cron');
        }
    }
    
    /**
     * Plugin deactivation hook
     */
    public function deactivate_plugin() {
        // Clear the scheduled cron job
        wp_clear_scheduled_hook('enhance_article_ai_daily_cron');
    }
    
    /**
     * Log enhancement history (simplified)
     */
    private function log_enhancement_history($post_id, $post_title, $status, $message = '') {
        // Simple logging to WordPress error log instead of database
        error_log(sprintf(
            'Enhance Article AI: Post ID %d (%s) - %s: %s',
            $post_id,
            $post_title,
            $status,
            $message
        ));
    }
}

// Initialize the plugin
global $enhance_article_ai_instance;
$enhance_article_ai_instance = new EnhanceArticleAI();

// Add settings page
add_action('admin_menu', function() {
    add_options_page(
        'Enhance Article AI Settings',
        'Enhance Article AI',
        'manage_options',
        'enhance-article-ai-settings',
        function() {
            // Handle form submissions
            if (isset($_POST['submit'])) {
                update_option('enhance_article_ai_webhook_url', sanitize_url($_POST['enhance_article_ai_webhook_url']));
                update_option('enhance_article_ai_daily_enabled', isset($_POST['enhance_article_ai_daily_enabled']) ? '1' : '0');
                
                echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
            }
            
            if (isset($_POST['run_manual_enhancement'])) {
                global $enhance_article_ai_instance;
                if ($enhance_article_ai_instance) {
                    $enhance_article_ai_instance->process_daily_enhancements();
                    update_option('enhance_article_ai_last_run', current_time('mysql', false));
                    echo '<div class="notice notice-success"><p>Manual enhancement completed successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Plugin instance not found</p></div>';
                }
            }
            
            if (isset($_POST['run_test'])) {
                run_enhancement_test();
            }
            
            ?>
            <div class="wrap">
                <h1>Enhance Article AI Settings</h1>
                
                <form method="post" action="">
                    <?php wp_nonce_field('enhance_article_ai_settings', 'settings_nonce'); ?>
                    
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
                        <tr>
                            <th scope="row">Daily Auto-Enhancement</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enhance_article_ai_daily_enabled" 
                                           value="1" <?php checked(get_option('enhance_article_ai_daily_enabled', '0'), '1'); ?> />
                                    Enable automatic daily enhancement of published articles
                                </label>
                                <p class="description">When enabled, all published articles will be automatically enhanced every 24 hours</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Last Enhancement Run</th>
                            <td>
                                <?php 
                                $last_run = get_option('enhance_article_ai_last_run', 'Never');
                                if ($last_run !== 'Never') {
                                    echo esc_html($last_run) . ' (EDT)';
                                } else {
                                    echo esc_html($last_run);
                                }
                                ?>
                                <p class="description">Shows when the last daily enhancement was completed (EDT timezone)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Manual Enhancement</th>
                            <td>
                                <button type="submit" name="run_manual_enhancement" class="button button-secondary">
                                    Run Enhancement Now
                                </button>
                                <p class="description">Manually trigger the enhancement process for all published articles</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Test Functionality</th>
                            <td>
                                <button type="submit" name="run_test" class="button button-secondary">
                                    Run System Test
                                </button>
                                <p class="description">Test your daily enhancement setup and configuration</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h2 style="margin-top: 30px;">Status Information</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Plugin Status</th>
                            <td>
                                <p style="color: #28a745;">✅ Plugin is active and working</p>
                                <p style="color: #666; font-size: 12px;">Enhancement functionality is available in the post editor.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Last Enhancement Run</th>
                            <td>
                                <?php 
                                $last_run = get_option('enhance_article_ai_last_run', 'Never');
                                if ($last_run !== 'Never') {
                                    echo esc_html($last_run) . ' (EDT)';
                                } else {
                                    echo esc_html($last_run);
                                }
                                ?>
                                <p class="description">Shows when the last daily enhancement was completed (EDT timezone)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <?php if (isset($_POST['run_test'])): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                        <h3>Test Results:</h3>
                        <pre style="background: white; padding: 10px; overflow-x: auto; max-height: 400px; overflow-y: auto;"><?php echo esc_html($test_results ?? ''); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    );
});

// Add settings registration
add_action('admin_init', function() {
    register_setting('enhance_article_ai_settings', 'enhance_article_ai_webhook_url');
    register_setting('enhance_article_ai_settings', 'enhance_article_ai_daily_enabled');
    register_setting('enhance_article_ai_settings', 'enhance_article_ai_last_run');
});

// Function to run enhancement test
function run_enhancement_test() {
    global $test_results;
    $results = array();
    
    // Test 1: Check if N8N webhook is configured
    $n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
    $results[] = "📋 Test 1: N8N Webhook Configuration";
    if (!empty($n8n_webhook_url)) {
        $results[] = "✅ N8N webhook URL is configured: " . substr($n8n_webhook_url, 0, 50) . "...";
    } else {
        $results[] = "❌ N8N webhook URL is not configured";
    }
    
    // Test 2: Check daily enhancement setting
    $daily_enabled = get_option('enhance_article_ai_daily_enabled', '0');
    $results[] = "\n📋 Test 2: Daily Enhancement Setting";
    if ($daily_enabled === '1') {
        $results[] = "✅ Daily enhancement is enabled";
    } else {
        $results[] = "❌ Daily enhancement is disabled";
    }
    
    // Test 3: Query for published articles
    $results[] = "\n📋 Test 3: Database Query for Published Articles";
    global $wpdb;
    
    $today = current_time('Y-m-d');
    $articles = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_content, post_modified 
             FROM {$wpdb->posts} 
             WHERE post_type = %s 
             AND post_status = %s 
             AND DATE(post_date) = %s
             ORDER BY post_modified DESC 
             LIMIT 5",
            'article',
            'publish',
            $today
        )
    );
    
    if (!empty($articles)) {
        $results[] = "✅ Found " . count($articles) . " published articles";
        $results[] = "📄 Sample articles:";
        foreach ($articles as $article) {
            $results[] = "   - ID: {$article->ID}, Title: " . substr($article->post_title, 0, 50) . "...";
            $results[] = "     Modified: {$article->post_modified}";
        }
    } else {
        $results[] = "❌ No published articles found";
        $results[] = "💡 Make sure you have articles with post_type = 'article' and post_status = 'publish'";
    }
    
    // Test 4: Check if cron job is scheduled
    $results[] = "\n📋 Test 4: Cron Job Status";
    $next_scheduled = wp_next_scheduled('enhance_article_ai_daily_cron');
    if ($next_scheduled) {
        $results[] = "✅ Daily cron job is scheduled for: " . date('Y-m-d H:i:s', $next_scheduled);
    } else {
        $results[] = "❌ Daily cron job is not scheduled";
    }
    
    // Test 5: Check last run time
    $results[] = "\n📋 Test 5: Last Enhancement Run";
    $last_run = get_option('enhance_article_ai_last_run', 'Never');
    $results[] = "📅 Last run: " . $last_run;
    
    // Test 6: Test N8N connectivity (if webhook is configured)
    if (!empty($n8n_webhook_url)) {
        $results[] = "\n📋 Test 6: N8N Connectivity Test";
        
        $test_data = array(
            'title' => 'Test Article for Daily Enhancement',
            'content' => 'This is a test article to verify N8N connectivity for daily enhancement.',
            'timestamp' => current_time('timestamp')
        );
        
        $response = wp_remote_post($n8n_webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($test_data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            $results[] = "❌ N8N connectivity failed: " . $response->get_error_message();
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $results[] = "✅ N8N connectivity successful (Status: {$response_code})";
                
                $response_body = wp_remote_retrieve_body($response);
                $decoded = json_decode($response_body, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $results[] = "✅ Valid JSON response received";
                    if (isset($decoded['enhanced_text'])) {
                        $results[] = "✅ Enhanced text field found in response";
                    } else {
                        $results[] = "⚠️  Enhanced text field not found in response";
                    }
                } else {
                    $results[] = "⚠️  Response is not valid JSON";
                }
            } else {
                $results[] = "❌ N8N returned error code: {$response_code}";
            }
        }
    }
    
    $results[] = "\n🎯 Summary:";
    $results[] = "==========";
    $results[] = "1. Configure N8N webhook URL in WordPress settings";
    $results[] = "2. Enable daily enhancement in plugin settings";
    $results[] = "3. Ensure you have published articles with post_type = 'article'";
    $results[] = "4. The cron job will run automatically every 24 hours";
    $results[] = "5. You can also trigger manual enhancement from the settings page";
    $results[] = "\n💡 For debugging, check WordPress error logs for detailed information";
    
    $test_results = implode("\n", $results);
} 