<?php
/**
 * Plugin Name: Enhance Article AI
 * Description: Adds an "Enhance by AI" button to the post editor that sends content to n8n for enhancement. Includes daily automatic enhancement of published articles.
 * Version: 3.0.0
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
        add_action('wp_ajax_preview_enhancement_articles', array($this, 'preview_enhancement_articles'));
        
        // Schedule the daily enhancement task
        add_action('init', array($this, 'schedule_daily_enhancement'));
        add_action('enhance_article_ai_daily_cron', array($this, 'process_daily_enhancements'));
        
        // Add batch processing hook
        add_action('enhance_article_ai_batch_processing', array($this, 'process_batch_enhancement'));
        
        // Add AJAX handlers for batch processing
        add_action('wp_ajax_process_enhancement_batch', array($this, 'ajax_process_batch'));
        add_action('wp_ajax_get_batch_progress', array($this, 'ajax_get_batch_progress'));
        
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
            'timeout' => 180,
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
     * Process manual enhancements with date filtering using batch processing
     */
    public function process_manual_enhancements_with_dates($start_date = '', $end_date = '', $batch_size = 5, $offset = 0) {
        try {
            global $wpdb;
        
        // Get N8N webhook URL
        $n8n_webhook_url = get_option('enhance_article_ai_webhook_url', '');
        
        if (empty($n8n_webhook_url)) {
            error_log('Enhance Article AI: N8N webhook URL not configured for manual enhancement');
            return;
        }
        
        // Build the date filter query
        $date_conditions = array();
        $query_params = array('article', 'publish');
        
        if (!empty($start_date)) {
            $date_conditions[] = "DATE(post_date) >= %s";
            $query_params[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $date_conditions[] = "DATE(post_date) <= %s";
            $query_params[] = $end_date;
        }
        
        // If no date filters provided, default to today
        if (empty($date_conditions)) {
            $date_conditions[] = "DATE(post_date) = %s";
            $query_params[] = current_time('Y-m-d');
        }
        
        $date_where_clause = implode(' AND ', $date_conditions);
        
        // Query for published articles with date filtering and batch processing
        $articles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_content, post_modified, post_date
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND {$date_where_clause}
                 ORDER BY post_date DESC
                 LIMIT %d OFFSET %d",
                array_merge($query_params, array($batch_size, $offset))
            )
        );
        
        // Get total count for progress tracking
        $total_articles_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND {$date_where_clause}",
                $query_params
            )
        );
        
        if (empty($articles)) {
            // Check if this is the end of processing
            if ($offset >= $total_articles_count) {
                error_log('Enhance Article AI: Batch processing completed - no more articles to process');
                $this->cleanup_batch_processing($start_date, $end_date);
                return;
            } else {
                error_log('Enhance Article AI: No articles found in current batch, but more articles exist');
                return;
            }
        }
        
        $processed_count = 0;
        $error_count = 0;
        $skipped_count = 0;
        $current_batch_size = count($articles);
        $current_batch = floor($offset / $batch_size) + 1;
        $total_batches = ceil($total_articles_count / $batch_size);
        
        error_log(sprintf(
            'Enhance Article AI: Processing batch %d/%d (%d articles) - Total articles: %d (Date range: %s to %s)',
            $current_batch,
            $total_batches,
            $current_batch_size,
            $total_articles_count,
            $start_date ?: 'today',
            $end_date ?: 'today'
        ));
        
        foreach ($articles as $index => $article) {
            $current_position = $index + 1;
            
            try {
                // Log progress
                error_log(sprintf(
                    'Enhance Article AI: Processing article %d/%d in batch %d/%d - ID: %d, Title: "%s"',
                    $current_position,
                    $current_batch_size,
                    $current_batch,
                    $total_batches,
                    $article->ID,
                    substr($article->post_title, 0, 50)
                ));
                
                // Check if article has content
                if (empty($article->post_content)) {
                    $skipped_count++;
                    error_log(sprintf(
                        'Enhance Article AI: Skipping article ID %d - no content to enhance',
                        $article->ID
                    ));
                    continue;
                }
                
                // Process article with retry mechanism
                $enhanced_content = $this->enhance_article_content_with_retry($article, $n8n_webhook_url);
                
                if ($enhanced_content) {
                    // Update the post with enhanced content - one at a time
                    $update_result = $this->update_article_safely($article->ID, $enhanced_content['enhanced_text']);
                    
                    if ($update_result) {
                        $processed_count++;
                        
                        // Log successful enhancement
                        error_log(sprintf(
                            'Enhance Article AI: ✅ Successfully enhanced article %d/%d - ID: %d: "%s"',
                            $current_position,
                            $total_articles,
                            $article->ID,
                            substr($article->post_title, 0, 50)
                        ));
                        
                        // Log to history
                        $this->log_enhancement_history($article->ID, $article->post_title, 'success', 'Manual enhancement completed');
                        
                    } else {
                        $error_count++;
                        error_log(sprintf(
                            'Enhance Article AI: ❌ Failed to update article %d/%d - ID: %d: "%s"',
                            $current_position,
                            $total_articles,
                            $article->ID,
                            substr($article->post_title, 0, 50)
                        ));
                        
                        // Log to history
                        $this->log_enhancement_history($article->ID, $article->post_title, 'error', 'Failed to update post');
                    }
                } else {
                    $error_count++;
                    error_log(sprintf(
                        'Enhance Article AI: ❌ Failed to enhance article %d/%d - ID: %d: "%s"',
                        $current_position,
                        $total_articles,
                        $article->ID,
                        substr($article->post_title, 0, 50)
                    ));
                    
                    // Log to history
                    $this->log_enhancement_history($article->ID, $article->post_title, 'error', 'Failed to enhance content');
                }
                
                // Add delay between articles to prevent concurrent updates and server overload
                if ($current_position < $current_batch_size) {
                    error_log(sprintf(
                        'Enhance Article AI: Waiting 3 seconds before processing next article... (%d remaining in batch)',
                        $current_batch_size - $current_position
                    ));
                    sleep(3); // Increased delay to 3 seconds
                }
                
            } catch (Exception $e) {
                $error_count++;
                error_log(sprintf(
                    'Enhance Article AI: ❌ Exception while processing article %d/%d - ID: %d: %s',
                    $current_position,
                    $total_articles,
                    $article->ID,
                    $e->getMessage()
                ));
                
                // Log to history
                $this->log_enhancement_history($article->ID, $article->post_title, 'error', 'Exception: ' . $e->getMessage());
            }
        }
        
        // Log batch summary
        error_log(sprintf(
            'Enhance Article AI: Batch %d/%d completed. Processed: %d, Errors: %d, Skipped: %d (Date range: %s to %s)',
            $current_batch,
            $total_batches,
            $processed_count,
            $error_count,
            $skipped_count,
            $start_date ?: 'today',
            $end_date ?: 'today'
        ));
        
        // Store batch progress
        $this->store_batch_progress($start_date, $end_date, $offset + $current_batch_size, $total_articles_count, $processed_count, $error_count, $skipped_count);
        
        // Schedule next batch if there are more articles
        $next_offset = $offset + $batch_size;
        if ($next_offset < $total_articles_count) {
            error_log(sprintf(
                'Enhance Article AI: More articles to process (offset: %d, remaining: %d articles)',
                $next_offset,
                $total_articles_count - $next_offset
            ));
            
            // Store next batch info for AJAX processing
            $this->store_next_batch_info($start_date, $end_date, $batch_size, $next_offset, $total_articles_count);
            
            // Try WordPress cron as backup, but don't rely on it
            $scheduled = wp_schedule_single_event(time() + 10, 'enhance_article_ai_batch_processing', array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'batch_size' => $batch_size,
                'offset' => $next_offset
            ));
            
            if (is_wp_error($scheduled)) {
                error_log('Enhance Article AI: WordPress cron scheduling failed: ' . $scheduled->get_error_message());
            } else {
                error_log('Enhance Article AI: WordPress cron scheduled as backup for ' . date('Y-m-d H:i:s', time() + 10));
            }
        } else {
            error_log('Enhance Article AI: All batches completed - processing finished');
            $this->cleanup_batch_processing($start_date, $end_date);
        }
        
        } catch (Exception $e) {
            error_log('Enhance Article AI: Fatal error in manual enhancement: ' . $e->getMessage());
            error_log('Enhance Article AI: Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Process batch enhancement (called by scheduled event)
     */
    public function process_batch_enhancement($args) {
        try {
            // Handle both old and new parameter formats
            if (is_array($args)) {
                $start_date = $args['start_date'] ?? '';
                $end_date = $args['end_date'] ?? '';
                $batch_size = $args['batch_size'] ?? 5;
                $offset = $args['offset'] ?? 0;
            } else {
                // Legacy format - individual parameters
                $start_date = $args;
                $end_date = func_get_arg(1) ?? '';
                $batch_size = func_get_arg(2) ?? 5;
                $offset = func_get_arg(3) ?? 0;
            }
            
            error_log(sprintf(
                'Enhance Article AI: Processing scheduled batch - Start: %s, End: %s, Size: %d, Offset: %d',
                $start_date,
                $end_date,
                $batch_size,
                $offset
            ));
            
            $this->process_manual_enhancements_with_dates($start_date, $end_date, $batch_size, $offset);
            
        } catch (Exception $e) {
            error_log('Enhance Article AI: Batch processing error: ' . $e->getMessage());
            error_log('Enhance Article AI: Batch processing stack trace: ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Store batch processing progress
     */
    private function store_batch_progress($start_date, $end_date, $processed_offset, $total_articles, $processed_count, $error_count, $skipped_count) {
        $progress_key = 'enhance_article_ai_batch_progress_' . md5($start_date . $end_date);
        
        $progress = get_option($progress_key, array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_articles' => $total_articles,
            'processed_offset' => 0,
            'total_processed' => 0,
            'total_errors' => 0,
            'total_skipped' => 0,
            'start_time' => time(),
            'last_update' => time()
        ));
        
        $progress['processed_offset'] = $processed_offset;
        $progress['total_processed'] += $processed_count;
        $progress['total_errors'] += $error_count;
        $progress['total_skipped'] += $skipped_count;
        $progress['last_update'] = time();
        
        update_option($progress_key, $progress);
    }
    
    /**
     * Cleanup batch processing data
     */
    private function cleanup_batch_processing($start_date, $end_date) {
        $progress_key = 'enhance_article_ai_batch_progress_' . md5($start_date . $end_date);
        $progress = get_option($progress_key, array());
        
        if (!empty($progress)) {
            $total_time = time() - $progress['start_time'];
            error_log(sprintf(
                'Enhance Article AI: Batch processing completed! Total: %d, Processed: %d, Errors: %d, Skipped: %d, Time: %ds',
                $progress['total_articles'],
                $progress['total_processed'],
                $progress['total_errors'],
                $progress['total_skipped'],
                $total_time
            ));
            
            // Clean up progress data
            delete_option($progress_key);
        }
    }
    
    /**
     * Store next batch information for AJAX processing
     */
    private function store_next_batch_info($start_date, $end_date, $batch_size, $next_offset, $total_articles) {
        $batch_key = 'enhance_article_ai_next_batch_' . md5($start_date . $end_date);
        
        $batch_info = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'batch_size' => $batch_size,
            'offset' => $next_offset,
            'total_articles' => $total_articles,
            'created_at' => time()
        );
        
        update_option($batch_key, $batch_info);
        error_log('Enhance Article AI: Stored next batch info for AJAX processing');
    }
    
    /**
     * AJAX handler for processing batches
     */
    public function ajax_process_batch() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'enhance_article_ai_batch')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // Get the next batch info from stored data
        $batch_key = 'enhance_article_ai_next_batch_' . md5($start_date . $end_date);
        $next_batch = get_option($batch_key, array());
        
        if (empty($next_batch)) {
            wp_send_json_error('No batch information found');
            return;
        }
        
        $batch_size = $next_batch['batch_size'];
        $offset = $next_batch['offset'];
        
        error_log(sprintf(
            'Enhance Article AI: AJAX batch processing - Start: %s, End: %s, Size: %d, Offset: %d',
            $start_date,
            $end_date,
            $batch_size,
            $offset
        ));
        
        // Process the batch
        $this->process_manual_enhancements_with_dates($start_date, $end_date, $batch_size, $offset);
        
        // Return success
        wp_send_json_success(array(
            'message' => 'Batch processed successfully',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'batch_size' => $batch_size,
            'offset' => $offset
        ));
    }
    
    /**
     * AJAX handler for getting batch progress
     */
    public function ajax_get_batch_progress() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'enhance_article_ai_batch')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $progress_key = 'enhance_article_ai_batch_progress_' . md5($start_date . $end_date);
        $progress = get_option($progress_key, array());
        
        $batch_key = 'enhance_article_ai_next_batch_' . md5($start_date . $end_date);
        $next_batch = get_option($batch_key, array());
        
        wp_send_json_success(array(
            'progress' => $progress,
            'next_batch' => $next_batch,
            'has_more' => !empty($next_batch) && $next_batch['offset'] < $next_batch['total_articles']
        ));
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
            'timeout' => 180,
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
     * Enhance article content with retry mechanism
     */
    private function enhance_article_content_with_retry($article, $n8n_webhook_url, $max_retries = 2) {
        $attempt = 1;
        
        while ($attempt <= $max_retries) {
            error_log(sprintf(
                'Enhance Article AI: Attempt %d/%d to enhance article ID %d',
                $attempt,
                $max_retries,
                $article->ID
            ));
            
            $result = $this->enhance_article_content($article, $n8n_webhook_url);
            
            if ($result !== false) {
                if ($attempt > 1) {
                    error_log(sprintf(
                        'Enhance Article AI: Successfully enhanced article ID %d on attempt %d',
                        $article->ID,
                        $attempt
                    ));
                }
                return $result;
            }
            
            if ($attempt < $max_retries) {
                $delay = $attempt * 2; // Progressive delay: 2s, 4s
                error_log(sprintf(
                    'Enhance Article AI: Attempt %d failed for article ID %d, retrying in %d seconds...',
                    $attempt,
                    $article->ID,
                    $delay
                ));
                sleep($delay);
            }
            
            $attempt++;
        }
        
        error_log(sprintf(
            'Enhance Article AI: All %d attempts failed for article ID %d',
            $max_retries,
            $article->ID
        ));
        
        return false;
    }
    
    /**
     * Safely update article content with database lock prevention
     */
    private function update_article_safely($post_id, $enhanced_content) {
        // Get fresh post data to avoid conflicts
        $post = get_post($post_id);
        
        if (!$post) {
            error_log(sprintf('Enhance Article AI: Post ID %d not found during update', $post_id));
            return false;
        }
        
        // Check if post is still published
        if ($post->post_status !== 'publish') {
            error_log(sprintf('Enhance Article AI: Post ID %d is no longer published (status: %s)', $post_id, $post->post_status));
            return false;
        }
        
        // Prepare update data
        $update_data = array(
            'ID' => $post_id,
            'post_content' => $enhanced_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        );
        
        // Attempt to update with error checking
        $update_result = wp_update_post($update_data, true);
        
        if (is_wp_error($update_result)) {
            error_log(sprintf(
                'Enhance Article AI: WordPress error updating post ID %d: %s',
                $post_id,
                $update_result->get_error_message()
            ));
            return false;
        }
        
        if ($update_result === 0) {
            error_log(sprintf('Enhance Article AI: wp_update_post returned 0 for post ID %d', $post_id));
            return false;
        }
        
        // Verify the update was successful
        $updated_post = get_post($post_id);
        if (!$updated_post || $updated_post->post_content !== $enhanced_content) {
            error_log(sprintf('Enhance Article AI: Content verification failed for post ID %d', $post_id));
            return false;
        }
        
        return true;
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
     * Preview articles that would be enhanced with given date filters
     */
    public function preview_enhancement_articles() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'preview_enhancement_articles')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // Build the date filter query
        $date_conditions = array();
        $query_params = array('article', 'publish');
        
        if (!empty($start_date)) {
            $date_conditions[] = "DATE(post_date) >= %s";
            $query_params[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $date_conditions[] = "DATE(post_date) <= %s";
            $query_params[] = $end_date;
        }
        
        // If no date filters provided, default to today
        if (empty($date_conditions)) {
            $date_conditions[] = "DATE(post_date) = %s";
            $query_params[] = current_time('Y-m-d');
        }
        
        $date_where_clause = implode(' AND ', $date_conditions);
        
        // Query for published articles with date filtering
        $articles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_date, post_modified
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND {$date_where_clause}
                 ORDER BY post_date DESC
                 LIMIT 20",
                $query_params
            )
        );
        
        $total_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND {$date_where_clause}",
                $query_params
            )
        );
        
        $output = '<div style="margin-top: 10px;">';
        $output .= '<p><strong>Total articles to be enhanced: ' . intval($total_count) . '</strong></p>';
        
        if (!empty($articles)) {
            $output .= '<p><strong>Sample articles (showing first 20):</strong></p>';
            $output .= '<ul style="max-height: 200px; overflow-y: auto; background: white; padding: 10px; border: 1px solid #ddd;">';
            
            foreach ($articles as $article) {
                $output .= '<li style="margin-bottom: 8px;">';
                $output .= '<strong>ID ' . $article->ID . ':</strong> ' . esc_html(substr($article->post_title, 0, 60)) . (strlen($article->post_title) > 60 ? '...' : '') . '<br>';
                $output .= '<small style="color: #666;">Published: ' . $article->post_date . ' | Modified: ' . $article->post_modified . '</small>';
                $output .= '</li>';
            }
            
            $output .= '</ul>';
            
            if ($total_count > 20) {
                $output .= '<p><em>... and ' . ($total_count - 20) . ' more articles</em></p>';
            }
        } else {
            $output .= '<p style="color: #d63638;"><strong>No articles found matching the specified criteria.</strong></p>';
        }
        
        $output .= '</div>';
        
        wp_send_json_success($output);
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
                try {
                    global $enhance_article_ai_instance;
                    if ($enhance_article_ai_instance) {
                        // Get date filters from form
                        $start_date = isset($_POST['enhancement_start_date']) ? sanitize_text_field($_POST['enhancement_start_date']) : '';
                        $end_date = isset($_POST['enhancement_end_date']) ? sanitize_text_field($_POST['enhancement_end_date']) : '';
                        
                        // If no dates provided, use today (default behavior)
                        if (empty($start_date) && empty($end_date)) {
                            $enhance_article_ai_instance->process_daily_enhancements();
                        } else {
                            $enhance_article_ai_instance->process_manual_enhancements_with_dates($start_date, $end_date);
                        }
                        
                        update_option('enhance_article_ai_last_run', current_time('mysql', false));
                        echo '<div class="notice notice-success"><p>Manual enhancement started! First batch of 5 articles is processing. Remaining batches will process automatically every 5 seconds.</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>Plugin instance not found</p></div>';
                    }
                } catch (Exception $e) {
                    error_log('Enhance Article AI: Settings page error: ' . $e->getMessage());
                    echo '<div class="notice notice-error"><p>Error starting manual enhancement: ' . esc_html($e->getMessage()) . '</p></div>';
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
                                <div style="margin-bottom: 15px;">
                                    <label for="enhancement_start_date" style="display: block; margin-bottom: 5px; font-weight: bold;">Start Date (optional):</label>
                                    <input type="date" id="enhancement_start_date" name="enhancement_start_date" 
                                           value="<?php echo esc_attr(isset($_POST['enhancement_start_date']) ? $_POST['enhancement_start_date'] : ''); ?>" 
                                           style="margin-right: 10px;" />
                                    
                                    <label for="enhancement_end_date" style="display: block; margin-bottom: 5px; font-weight: bold;">End Date (optional):</label>
                                    <input type="date" id="enhancement_end_date" name="enhancement_end_date" 
                                           value="<?php echo esc_attr(isset($_POST['enhancement_end_date']) ? $_POST['enhancement_end_date'] : ''); ?>" />
                                    
                                    <div style="margin-top: 10px;">
                                        <button type="button" id="clear_dates" class="button button-link" style="font-size: 12px; margin-right: 10px;">
                                            Clear Dates
                                        </button>
                                        <button type="button" id="preview_articles" class="button button-link" style="font-size: 12px;">
                                            Preview Articles
                                        </button>
                                    </div>
                                    <div id="preview_results" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa; display: none;">
                                        <strong>Preview Results:</strong>
                                        <div id="preview_content"></div>
                                    </div>
                                </div>
                                
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const clearDatesBtn = document.getElementById('clear_dates');
                                    const previewBtn = document.getElementById('preview_articles');
                                    const startDateInput = document.getElementById('enhancement_start_date');
                                    const endDateInput = document.getElementById('enhancement_end_date');
                                    const previewResults = document.getElementById('preview_results');
                                    const previewContent = document.getElementById('preview_content');
                                    
                                    if (clearDatesBtn) {
                                        clearDatesBtn.addEventListener('click', function() {
                                            startDateInput.value = '';
                                            endDateInput.value = '';
                                            previewResults.style.display = 'none';
                                        });
                                    }
                                    
                                    if (previewBtn) {
                                        previewBtn.addEventListener('click', function() {
                                            const startDate = startDateInput.value;
                                            const endDate = endDateInput.value;
                                            
                                            // Show loading
                                            previewContent.innerHTML = 'Loading...';
                                            previewResults.style.display = 'block';
                                            
                                            // Make AJAX request to preview articles
                                            const formData = new FormData();
                                            formData.append('action', 'preview_enhancement_articles');
                                            formData.append('start_date', startDate);
                                            formData.append('end_date', endDate);
                                            formData.append('nonce', '<?php echo wp_create_nonce('preview_enhancement_articles'); ?>');
                                            
                                            fetch(ajaxurl, {
                                                method: 'POST',
                                                body: formData
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    previewContent.innerHTML = data.data;
                                                } else {
                                                    previewContent.innerHTML = 'Error: ' + data.data;
                                                }
                                            })
                                            .catch(error => {
                                                previewContent.innerHTML = 'Error loading preview: ' + error.message;
                                            });
                                        });
                                    }
                                    
                                    // Validate date range
                                    function validateDateRange() {
                                        const startDate = startDateInput.value;
                                        const endDate = endDateInput.value;
                                        
                                        if (startDate && endDate && startDate > endDate) {
                                            alert('Start date cannot be after end date.');
                                            endDateInput.value = '';
                                        }
                                    }
                                    
                                    if (startDateInput) {
                                        startDateInput.addEventListener('change', validateDateRange);
                                    }
                                    if (endDateInput) {
                                        endDateInput.addEventListener('change', validateDateRange);
                                    }
                                });
                                </script>
                                
                                <button type="submit" name="run_manual_enhancement" class="button button-secondary">
                                    Run Enhancement Now
                                </button>
                                <p class="description">
                                    Manually trigger the enhancement process for published articles.<br>
                                    <strong>• Leave dates empty:</strong> Enhance today's articles only (default behavior)<br>
                                    <strong>• Set start date only:</strong> Enhance articles from that date to today<br>
                                    <strong>• Set end date only:</strong> Enhance articles from beginning to that date<br>
                                    <strong>• Set both dates:</strong> Enhance articles published within that date range<br>
                                    <em>Note: Only articles with post_type = 'article' and post_status = 'publish' will be processed.</em><br>
                                    <strong>🔄 Automated Batch Processing:</strong> Articles are processed in batches of 5 to avoid PHP execution limits. After the first batch, remaining batches process automatically every 5 seconds.
                                </p>
                                
                                <?php
                                // Show batch processing progress if available
                                try {
                                    $start_date_for_progress = isset($_POST['enhancement_start_date']) ? sanitize_text_field($_POST['enhancement_start_date']) : '';
                                    $end_date_for_progress = isset($_POST['enhancement_end_date']) ? sanitize_text_field($_POST['enhancement_end_date']) : '';
                                    $progress_key = 'enhance_article_ai_batch_progress_' . md5($start_date_for_progress . $end_date_for_progress);
                                    $progress = get_option($progress_key, array());
                                    
                                    $batch_key = 'enhance_article_ai_next_batch_' . md5($start_date_for_progress . $end_date_for_progress);
                                    $next_batch = get_option($batch_key, array());
                                    
                                    if (!empty($progress) && isset($progress['processed_offset']) && isset($progress['total_articles']) && $progress['processed_offset'] < $progress['total_articles']): ?>
                                        <div id="batch-progress-container" style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 4px solid #0073aa;">
                                            <h4>🔄 Batch Processing in Progress</h4>
                                            <p><strong>Progress:</strong> <span id="progress-text"><?php echo esc_html($progress['processed_offset']); ?> / <?php echo esc_html($progress['total_articles']); ?></span> articles processed</p>
                                            <p><strong>Results:</strong> <span id="results-text"><?php echo esc_html($progress['total_processed'] ?? 0); ?> processed, <?php echo esc_html($progress['total_errors'] ?? 0); ?> errors, <?php echo esc_html($progress['total_skipped'] ?? 0); ?> skipped</span></p>
                                            <p><strong>Last Update:</strong> <span id="last-update-text"><?php echo esc_html(date('Y-m-d H:i:s', $progress['last_update'] ?? time())); ?></span></p>
                                            
                                            <?php if (!empty($next_batch) && $next_batch['offset'] < $next_batch['total_articles']): ?>
                                                <div style="margin-top: 10px;">
                                                    <button type="button" id="continue-batch-processing" class="button button-primary">
                                                        Start Automated Processing
                                                    </button>
                                                    <span id="batch-status" style="margin-left: 10px;"></span>
                                                </div>
                                            <?php else: ?>
                                                <p><em>✅ All batches completed!</em></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const continueBtn = document.getElementById('continue-batch-processing');
                                            const statusSpan = document.getElementById('batch-status');
                                            const progressText = document.getElementById('progress-text');
                                            const resultsText = document.getElementById('results-text');
                                            const lastUpdateText = document.getElementById('last-update-text');
                                            
                                            // Auto-start processing if there are more batches
                                            if (continueBtn && !continueBtn.disabled) {
                                                setTimeout(() => {
                                                    processNextBatch();
                                                }, 3000); // Start after 3 seconds
                                            }
                                            
                                            function processNextBatch() {
                                                if (continueBtn) {
                                                    continueBtn.disabled = true;
                                                    continueBtn.textContent = 'Processing...';
                                                }
                                                statusSpan.textContent = 'Processing next batch...';
                                                
                                                const formData = new FormData();
                                                formData.append('action', 'process_enhancement_batch');
                                                formData.append('start_date', '<?php echo esc_js($start_date_for_progress); ?>');
                                                formData.append('end_date', '<?php echo esc_js($end_date_for_progress); ?>');
                                                formData.append('nonce', '<?php echo wp_create_nonce('enhance_article_ai_batch'); ?>');
                                                
                                                fetch(ajaxurl, {
                                                    method: 'POST',
                                                    body: formData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        statusSpan.textContent = 'Batch completed! Checking for more...';
                                                        
                                                        // Update progress display
                                                        updateProgressDisplay();
                                                        
                                                        // Check if there are more batches after a delay
                                                        setTimeout(() => {
                                                            checkForMoreBatches();
                                                        }, 2000);
                                                    } else {
                                                        statusSpan.textContent = 'Error: ' + data.data;
                                                        if (continueBtn) {
                                                            continueBtn.disabled = false;
                                                            continueBtn.textContent = 'Start Automated Processing';
                                                        }
                                                    }
                                                })
                                                .catch(error => {
                                                    statusSpan.textContent = 'Error: ' + error.message;
                                                    if (continueBtn) {
                                                        continueBtn.disabled = false;
                                                        continueBtn.textContent = 'Start Automated Processing';
                                                    }
                                                });
                                            }
                                            
                                            function updateProgressDisplay() {
                                                // Fetch updated progress
                                                const progressFormData = new FormData();
                                                progressFormData.append('action', 'get_batch_progress');
                                                progressFormData.append('start_date', '<?php echo esc_js($start_date_for_progress); ?>');
                                                progressFormData.append('end_date', '<?php echo esc_js($end_date_for_progress); ?>');
                                                progressFormData.append('nonce', '<?php echo wp_create_nonce('enhance_article_ai_batch'); ?>');
                                                
                                                fetch(ajaxurl, {
                                                    method: 'POST',
                                                    body: progressFormData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success && data.data.progress) {
                                                        const progress = data.data.progress;
                                                        progressText.textContent = progress.processed_offset + ' / ' + progress.total_articles;
                                                        resultsText.textContent = progress.total_processed + ' processed, ' + progress.total_errors + ' errors, ' + progress.total_skipped + ' skipped';
                                                        lastUpdateText.textContent = new Date(progress.last_update * 1000).toLocaleString();
                                                    }
                                                })
                                                .catch(error => {
                                                    console.log('Error updating progress:', error);
                                                });
                                            }
                                            
                                            function checkForMoreBatches() {
                                                const progressFormData = new FormData();
                                                progressFormData.append('action', 'get_batch_progress');
                                                progressFormData.append('start_date', '<?php echo esc_js($start_date_for_progress); ?>');
                                                progressFormData.append('end_date', '<?php echo esc_js($end_date_for_progress); ?>');
                                                progressFormData.append('nonce', '<?php echo wp_create_nonce('enhance_article_ai_batch'); ?>');
                                                
                                                fetch(ajaxurl, {
                                                    method: 'POST',
                                                    body: progressFormData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        if (data.data.has_more) {
                                                            // Update the button with new batch info
                                                            if (continueBtn) {
                                                                continueBtn.disabled = false;
                                                                continueBtn.textContent = 'Continue Processing Next Batch';
                                                            }
                                                            statusSpan.textContent = 'More batches available. Processing next batch in 5 seconds...';
                                                            
                                                            // Auto-process next batch after 5 seconds
                                                            setTimeout(() => {
                                                                processNextBatch();
                                                            }, 5000);
                                                        } else {
                                                            statusSpan.textContent = '✅ All batches completed!';
                                                            if (continueBtn) {
                                                                continueBtn.style.display = 'none';
                                                            }
                                                        }
                                                    }
                                                })
                                                .catch(error => {
                                                    console.log('Error checking for more batches:', error);
                                                    statusSpan.textContent = 'Error checking progress. Please refresh the page.';
                                                });
                                            }
                                            
                                            // Manual button click handler (backup)
                                            if (continueBtn) {
                                                continueBtn.addEventListener('click', processNextBatch);
                                            }
                                        });
                                        </script>
                                    <?php endif;
                                } catch (Exception $e) {
                                    error_log('Enhance Article AI: Error displaying progress: ' . $e->getMessage());
                                } ?>
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
            'timeout' => 180,
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