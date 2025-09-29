<?php
/**
 * Plugin Name: ContentGen - Research Tweet Manager
 * Plugin URI: https://yourdomain.com/contentgen
 * Description: A WordPress plugin for managing research tweets and content generation from n8n workflows with bidirectional data flow
 * Version: 4.0.4timezone
 * Author: Umang Sharma
 * License: GPL v2 or later
 * Text Domain: contentgen
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CONTENTGEN_VERSION', '4.0.4');
define('CONTENTGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTENTGEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CONTENTGEN_CSS_FILE', 'index-1759166483421.css');
define('CONTENTGEN_JS_FILE', 'index-1759166483364.js');

class ContentGen {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Incoming webhook from n8n
        add_action('wp_ajax_contentgen_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_contentgen_webhook', array($this, 'handle_webhook'));
        
        // Data management
        add_action('wp_ajax_contentgen_get_research_data', array($this, 'get_research_data'));
        add_action('wp_ajax_nopriv_contentgen_get_research_data', array($this, 'get_research_data'));
        add_action('wp_ajax_contentgen_get_queries', array($this, 'get_queries'));
        add_action('wp_ajax_nopriv_contentgen_get_queries', array($this, 'get_queries'));
        add_action('wp_ajax_contentgen_export_tweets', array($this, 'export_tweets'));
        add_action('wp_ajax_nopriv_contentgen_export_tweets', array($this, 'export_tweets'));
        add_action('wp_ajax_contentgen_accept_tweet', array($this, 'accept_tweet'));
        add_action('wp_ajax_nopriv_contentgen_accept_tweet', array($this, 'accept_tweet'));
        add_action('wp_ajax_contentgen_decline_tweet', array($this, 'decline_tweet'));
        add_action('wp_ajax_nopriv_contentgen_decline_tweet', array($this, 'decline_tweet'));
        add_action('wp_ajax_contentgen_update_tweet', array($this, 'update_tweet'));
        add_action('wp_ajax_nopriv_contentgen_update_tweet', array($this, 'update_tweet'));
        
        // Outgoing webhook to n8n
        add_action('wp_ajax_contentgen_send_to_n8n', array($this, 'send_to_n8n'));
        add_action('wp_ajax_nopriv_contentgen_send_to_n8n', array($this, 'send_to_n8n'));
        add_action('wp_ajax_contentgen_test_outgoing_webhook', array($this, 'test_outgoing_webhook'));
        add_action('wp_ajax_nopriv_contentgen_test_outgoing_webhook', array($this, 'test_outgoing_webhook'));
        
        // Test AJAX endpoint
        add_action('wp_ajax_contentgen_test_ajax', array($this, 'test_ajax'));
        add_action('wp_ajax_nopriv_contentgen_test_ajax', array($this, 'test_ajax'));
        
        // Scheduler endpoints
        add_action('wp_ajax_contentgen_schedule_tweets', array($this, 'schedule_tweets'));
        add_action('wp_ajax_nopriv_contentgen_schedule_tweets', array($this, 'schedule_tweets'));
        add_action('wp_ajax_contentgen_get_scheduled_tweets', array($this, 'get_scheduled_tweets'));
        add_action('wp_ajax_nopriv_contentgen_get_scheduled_tweets', array($this, 'get_scheduled_tweets'));
        add_action('wp_ajax_contentgen_update_scheduled_tweet', array($this, 'update_scheduled_tweet'));
        add_action('wp_ajax_nopriv_contentgen_update_scheduled_tweet', array($this, 'update_scheduled_tweet'));
        
        // Admin testing functionality
        add_action('wp_ajax_contentgen_admin_get_scheduled_tweets', array($this, 'admin_get_scheduled_tweets'));
        add_action('wp_ajax_contentgen_get_scheduled_queries', array($this, 'get_scheduled_queries'));
        add_action('wp_ajax_contentgen_test_scheduler_logic', array($this, 'test_scheduler_logic'));
        add_action('wp_ajax_contentgen_check_cron_status', array($this, 'check_cron_status'));
        add_action('wp_ajax_contentgen_run_cron_manually', array($this, 'run_cron_manually'));
        add_action('wp_ajax_contentgen_reschedule_cron', array($this, 'reschedule_cron'));
        
        // Public cron endpoint for external cron jobs (Bluehost) - DEPRECATED
        add_action('wp_ajax_contentgen_external_cron', array($this, 'handle_external_cron'));
        add_action('wp_ajax_nopriv_contentgen_external_cron', array($this, 'handle_external_cron'));
        
        // n8n polling endpoint - check for due tweets in last 15 minutes
        add_action('wp_ajax_contentgen_poll_scheduled_tweets', array($this, 'handle_n8n_poll'));
        add_action('wp_ajax_nopriv_contentgen_poll_scheduled_tweets', array($this, 'handle_n8n_poll'));
        
        // Add shortcode
        add_shortcode('contentgen_dashboard', array($this, 'dashboard_shortcode'));
        
        // Create database tables on activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add scheduler cron hook
        add_action('contentgen_process_scheduled_tweets_hook', array($this, 'process_scheduled_tweets'));
        
        // Add custom cron schedule
        add_filter('cron_schedules', array($this, 'add_cron_schedule'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function add_cron_schedule($schedules) {
        $schedules['contentgen_5min'] = array(
            'interval' => 300, // 5 minutes in seconds
            'display' => 'Every 5 Minutes (ContentGen Scheduler)'
        );
        return $schedules;
    }
    
    public function activate() {
        $this->create_tables();
        $this->migrate_scheduled_tweets_table(); // Fix existing table structure
        
        // Set default options
        add_option('contentgen_webhook_secret', wp_generate_password(32, false));
        add_option('contentgen_outgoing_webhook_url', '');
        add_option('contentgen_outgoing_webhook_secret', wp_generate_password(32, false));
        
        // Schedule the cron job to run every 5 minutes
        if (!wp_next_scheduled('contentgen_process_scheduled_tweets_hook')) {
            wp_schedule_event(time(), 'contentgen_5min', 'contentgen_process_scheduled_tweets_hook');
        }
    }
    
    public function deactivate() {
        // Clear the scheduled cron job
        $timestamp = wp_next_scheduled('contentgen_process_scheduled_tweets_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'contentgen_process_scheduled_tweets_hook');
        }
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Research data table
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pmid int NOT NULL,
            date_added date NOT NULL,
            journal varchar(255),
            title text,
            tweet text,
            tweet_few_shot text,
            doi varchar(255),
            type varchar(255),
            query varchar(255),
            summary text,
            abstract longtext,
            twitter_hashtags text,
            twitter_accounts text,
            score float,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pmid (pmid)
        ) $charset_collate;";
        
        // Accepted tweets table
        $accepted_table = $wpdb->prefix . 'contentgen_accepted_tweets';
        $sql2 = "CREATE TABLE $accepted_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pmid int NOT NULL,
            tweet text NOT NULL,
            exported_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_to_n8n tinyint(1) DEFAULT 0,
            n8n_response text,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Outgoing data table
        $outgoing_table = $wpdb->prefix . 'contentgen_outgoing_data';
        $sql3 = "CREATE TABLE $outgoing_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data_type varchar(50) NOT NULL,
            data_content longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            n8n_response text,
            retry_count int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Scheduled tweets table for scheduler feature
        $scheduled_table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        $sql4 = "CREATE TABLE $scheduled_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pmid varchar(255) NOT NULL,
            query varchar(255) NOT NULL,
            tweet_content text NOT NULL,
            scheduled_datetime datetime NOT NULL COMMENT 'Stored in UTC',
            status varchar(20) DEFAULT 'pending',
            tweet_data longtext,
            created_at datetime NULL COMMENT 'WordPress timezone',
            updated_at datetime NULL COMMENT 'WordPress timezone',
            attempts int DEFAULT 0,
            last_error text NULL,
            sent_at datetime NULL COMMENT 'WordPress timezone',
            PRIMARY KEY (id),
            KEY scheduled_datetime (scheduled_datetime),
            KEY status (status),
            KEY query (query)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
    }
    
    private function migrate_scheduled_tweets_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            return; // Table doesn't exist, no migration needed
        }
        
        // Remove DEFAULT CURRENT_TIMESTAMP from existing columns
        $wpdb->query("ALTER TABLE {$table} 
            MODIFY COLUMN created_at datetime NULL COMMENT 'WordPress timezone',
            MODIFY COLUMN updated_at datetime NULL COMMENT 'WordPress timezone'");
            
        error_log('ContentGen: Migrated scheduled tweets table to remove MySQL timezone defaults');
    }
    
    public function add_admin_menu() {
        add_options_page(
            'ContentGen Settings',
            'ContentGen',
            'manage_options',
            'contentgen-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('contentgen_outgoing_webhook_url', sanitize_url($_POST['outgoing_webhook_url']));
            update_option('contentgen_outgoing_webhook_secret', sanitize_text_field($_POST['outgoing_webhook_secret']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $outgoing_url = get_option('contentgen_outgoing_webhook_url', '');
        $outgoing_secret = get_option('contentgen_outgoing_webhook_secret', '');
        $incoming_secret = get_option('contentgen_webhook_secret', '');
        $cron_secret = get_option('contentgen_cron_secret', '');
        
        ?>
        <div class="wrap">
            <h1>ContentGen Settings</h1>
            
            <h2>Incoming Webhook (from n8n)</h2>
            <p><strong>URL:</strong> <code><?php echo admin_url('admin-ajax.php?action=contentgen_webhook'); ?></code></p>
            <p><strong>Secret:</strong> <code><?php echo esc_html($incoming_secret); ?></code></p>
            
            <h2>n8n Polling Endpoint (Recommended)</h2>
            <p><strong>URL:</strong> <code><?php echo admin_url('admin-ajax.php?action=contentgen_poll_scheduled_tweets'); ?></code></p>
            <p><strong>Method:</strong> GET or POST</p>
            <p><strong>Authentication:</strong> None required - publicly accessible</p>
            <p class="description">
                <strong>How it works:</strong> n8n polls this endpoint every few minutes. 
                It checks for scheduled tweets from the last 15 minutes, marks them as sent, and returns the tweet data.
            </p>
            
            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h4>📋 n8n Workflow Setup Steps</h4>
                <ol>
                    <li><strong>Add Schedule Trigger Node</strong>
                        <ul style="margin-left: 20px;">
                            <li>Interval: Every 5-10 minutes</li>
                        </ul>
                    </li>
                    
                    <li><strong>Add HTTP Request Node</strong>
                        <ul style="margin-left: 20px;">
                            <li>Method: <code>GET</code></li>
                            <li>URL: <code><?php echo admin_url('admin-ajax.php?action=contentgen_poll_scheduled_tweets'); ?></code></li>
                            <li>Headers: <em>None required</em></li>
                        </ul>
                    </li>
                    
                    <li><strong>Add IF Node (Check for tweets)</strong>
                        <ul style="margin-left: 20px;">
                            <li>Condition: <code>{{ $json.tweets_count > 0 }}</code></li>
                        </ul>
                    </li>
                    
                    <li><strong>Add Item Lists Node (Process each tweet)</strong>
                        <ul style="margin-left: 20px;">
                            <li>Field to Split Out: <code>tweets</code></li>
                            <li>This creates one execution per tweet</li>
                        </ul>
                    </li>
                    
                    <li><strong>Process Tweets (your choice):</strong>
                        <ul style="margin-left: 20px;">
                            <li>Send to Twitter API</li>
                            <li>Save to database</li>
                            <li>Send to other platforms</li>
                            <li>Access tweet data: <code>{{ $json.tweet_content }}</code>, <code>{{ $json.query }}</code>, etc.</li>
                        </ul>
                    </li>
                </ol>
                
                <h4>📊 Response Format</h4>
                <p><strong>When tweets found:</strong></p>
                <pre style="background: #f8f9fa; padding: 10px; margin: 5px 0; font-size: 11px; overflow-x: auto;">{
  "success": true,
  "tweets_count": 2,
  "tweets": [
    {
      "id": 123,
      "pmid": "12345678",
      "query": "cancer",
      "tweet_content": "Amazing breakthrough...",
      "scheduled_datetime": "2024-01-15 14:30:00",
      "sent_at": "2024-01-15 14:32:15"
    }
  ],
  "timestamp": "2024-01-15 14:32:15 UTC"
}</pre>
                
                <p><strong>When no tweets found:</strong></p>
                <pre style="background: #f8f9fa; padding: 10px; margin: 5px 0; font-size: 11px;">{
  "success": true,
  "tweets_count": 0,
  "tweets": []
}</pre>
                
                <h4>🧪 Test the Endpoint</h4>
                <p>Click to test (will open in new tab):</p>
                <a href="<?php echo admin_url('admin-ajax.php?action=contentgen_poll_scheduled_tweets'); ?>" 
                   target="_blank" 
                   class="button button-secondary">Test Polling Endpoint</a>
            </div>
            
            
            
            <h2>Outgoing Webhook (to n8n)</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">n8n Webhook URL</th>
                        <td>
                            <input type="url" name="outgoing_webhook_url" value="<?php echo esc_attr($outgoing_url); ?>" class="regular-text" />
                            <p class="description">The n8n webhook URL to send data to</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook Secret</th>
                        <td>
                            <input type="text" name="outgoing_webhook_secret" value="<?php echo esc_attr($outgoing_secret); ?>" class="regular-text" />
                            <p class="description">Secret key for authenticating outgoing requests</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Test Outgoing Webhook</h2>
            <button id="testOutgoingWebhook" class="button button-secondary">Test Connection</button>
            <div id="testResult"></div>
            
            <h2>Scheduled Tweets</h2>
            <div class="scheduled-tweets-testing">
                <h3>Filter Scheduled Tweets</h3>
                <div class="filter-controls">
                    <label for="filterQuery">Query:</label>
                    <select id="filterQuery" style="margin-right: 10px;">
                        <option value="">All Queries</option>
                    </select>
                    
                    <label for="filterStatus">Status:</label>
                    <select id="filterStatus" style="margin-right: 10px;">
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                        <option value="">All Statuses</option>
                    </select>
                    
                    <button id="loadScheduledTweets" class="button button-secondary">Load Tweets</button>
                </div>
                
                <div id="scheduledTweetsResult" style="margin-top: 20px;"></div>
                <div id="schedulerTestResult" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load available queries for filter
            loadAvailableQueries();
            
            $('#testOutgoingWebhook').click(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'contentgen_test_outgoing_webhook',
                        nonce: '<?php echo wp_create_nonce('contentgen_test'); ?>'
                    },
                    success: function(response) {
                        $('#testResult').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    },
                    error: function() {
                        $('#testResult').html('<div class="notice notice-error"><p>Test failed</p></div>');
                    }
                });
            });
            
            $('#loadScheduledTweets').click(function() {
                loadScheduledTweets();
            });
            
            // Only Load Tweets remains
            
            function loadAvailableQueries() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'contentgen_get_scheduled_queries',
                        nonce: '<?php echo wp_create_nonce('contentgen_admin_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">All Queries</option>';
                            response.data.forEach(function(query) {
                                options += '<option value="' + query + '">' + query + '</option>';
                            });
                            $('#filterQuery').html(options);
                        }
                    }
                });
            }
            
            function loadScheduledTweets() {
                var query = $('#filterQuery').val();
                var status = $('#filterStatus').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'contentgen_admin_get_scheduled_tweets',
                        query: query,
                        status: status,
                        nonce: '<?php echo wp_create_nonce('contentgen_admin_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayScheduledTweets(response.data);
                        } else {
                            $('#scheduledTweetsResult').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#scheduledTweetsResult').html('<div class="notice notice-error"><p>Failed to load scheduled tweets</p></div>');
                    }
                });
            }
            
            // Removed cron/testing helpers from UI
            
            function displayScheduledTweets(tweets) {
                if (tweets.length === 0) {
                    $('#scheduledTweetsResult').html('<p>No scheduled tweets found.</p>');
                    return;
                }
                
                var html = '<h4>Scheduled Tweets (' + tweets.length + ')</h4>';
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>ID</th><th>PMID</th><th>Query</th><th>Status</th><th>Scheduled (UTC)</th><th>Created</th><th>Tweet Content</th></tr></thead>';
                html += '<tbody>';
                
                tweets.forEach(function(tweet) {
                    html += '<tr>';
                    html += '<td>' + tweet.id + '</td>';
                    html += '<td>' + tweet.pmid + '</td>';
                    html += '<td>' + tweet.query + '</td>';
                    html += '<td>' + tweet.status + '</td>';
                    html += '<td>' + tweet.scheduled_datetime + '</td>';
                    html += '<td>' + tweet.created_at + '</td>';
                    html += '<td title="' + tweet.tweet_content + '">' + tweet.tweet_content.substring(0, 100) + '...</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#scheduledTweetsResult').html(html);
            }
        });
        </script>
        <?php
    }
    
    public function enqueue_scripts() {
        // Load the built React application (static reference to latest build)
        wp_enqueue_style('contentgen-styles', CONTENTGEN_PLUGIN_URL . 'assets/assets/' . CONTENTGEN_CSS_FILE, array(), CONTENTGEN_VERSION);
        wp_enqueue_script('contentgen-script', CONTENTGEN_PLUGIN_URL . 'assets/assets/' . CONTENTGEN_JS_FILE, array(), CONTENTGEN_VERSION, true);

        wp_localize_script('contentgen-script', 'contentgen_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentgen_nonce'),
            'plugin_url' => CONTENTGEN_PLUGIN_URL,
            'version' => CONTENTGEN_VERSION,
            'strings' => array(
                'loading' => __('Loading...', 'contentgen'),
                'error' => __('An error occurred', 'contentgen'),
                'success' => __('Success!', 'contentgen')
            )
        ));

        // Add error handling for script loading
        add_action('wp_footer', array($this, 'add_script_error_handling'));
    }
    
    public function add_script_error_handling() {
        ?>
        <script>
        // Check if React app loaded properly
        setTimeout(function() {
            if (document.getElementById('contentgen-app') && 
                document.getElementById('contentgen-app').innerHTML.includes('Loading ContentGen')) {
                console.error('ContentGen: React app failed to load or mount');
                document.getElementById('contentgen-app').innerHTML = 
                    '<div style="padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">' +
                    '<h3>ContentGen Loading Error</h3>' +
                    '<p>The React application failed to load. Please check the browser console for errors.</p>' +
                    '<p>Plugin URL: <?php echo CONTENTGEN_PLUGIN_URL; ?></p>' +
                    '</div>';
            }
        }, 5000); // Check after 5 seconds
        </script>
        <?php
    }
    
    public function admin_enqueue_scripts() {
        // Admin styles are not needed for this plugin
        // wp_enqueue_style('contentgen-admin-styles', CONTENTGEN_PLUGIN_URL . 'assets/css/admin.css', array(), CONTENTGEN_VERSION);
    }
    
    private function log_webhook_data($data) {
        try {
            $log_file = ABSPATH . 'webhook-debug.log';
            $timestamp = date('Y-m-d H:i:s');
            $log_entry = "[$timestamp] " . $data . "\n";
            $log_entry .= "----------------------------------------\n";
            
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Silently fail if logging fails
            error_log("ContentGen logging failed: " . $e->getMessage());
        }
    }
    
    public function handle_webhook() {
        try {
            $input = file_get_contents('php://input');
            $this->log_webhook_data("Raw input received: " . $input);
            $data = json_decode($input, true);

            if (!$data) {
                $this->log_webhook_data("ERROR: Invalid JSON - " . json_last_error_msg());
                wp_die('Invalid JSON', 'Bad Request', array('response' => 400));
            }

            // Log incoming data structure
            $this->log_webhook_data("JSON decoded successfully: " . json_encode($data, JSON_PRETTY_PRINT));

            // NEW FORMAT: { body: { items: [ { json: {...}, pairedItem: [...] }, ... ] } }
            if (isset($data['body']['items']) && is_array($data['body']['items'])) {
                $this->log_webhook_data("Processing items array with " . count($data['body']['items']) . " items (body.items format)");
                $results = $this->process_batch_data(array_map(function($item) {
                    return $item['json'] ?? [];
                }, $data['body']['items']));
                $this->log_webhook_data("Batch processing complete: " . json_encode($results, JSON_PRETTY_PRINT));
                wp_send_json_success(array(
                    'message' => 'Batch data processed successfully',
                    'processed_count' => count($results['success']),
                    'failed_count' => count($results['failed']),
                    'success_items' => $results['success'],
                    'failed_items' => $results['failed']
                ));
            }
            // OLD FORMAT: { items: [json_obj, ...] }
            elseif (isset($data['items']) && is_array($data['items'])) {
                $this->log_webhook_data("Processing items array with " . count($data['items']) . " items (items format)");
                if (count($data['items']) === 1) {
                    $result = $this->process_incoming_data($data['items'][0]);
                    if ($result) {
                        $this->log_webhook_data("Single item processed successfully: " . json_encode($result, JSON_PRETTY_PRINT));
                        wp_send_json_success(array(
                            'message' => 'Data received and processed successfully',
                            'data' => $result
                        ));
                    } else {
                        $this->log_webhook_data("ERROR: Failed to process single item");
                        wp_send_json_error('Failed to process data');
                    }
                } else {
                    $results = $this->process_batch_data($data['items']);
                    $this->log_webhook_data("Batch processing complete: " . json_encode($results, JSON_PRETTY_PRINT));
                    wp_send_json_success(array(
                        'message' => 'Batch data processed successfully',
                        'processed_count' => count($results['success']),
                        'failed_count' => count($results['failed']),
                        'success_items' => $results['success'],
                        'failed_items' => $results['failed']
                    ));
                }
            }
            // Legacy batch format
            elseif (isset($data['batch']) && $data['batch'] === true && isset($data['items']) && is_array($data['items'])) {
                $this->log_webhook_data("Processing legacy batch format");
                $results = $this->process_batch_data($data['items']);
                wp_send_json_success(array(
                    'message' => 'Batch data processed successfully',
                    'processed_count' => count($results['success']),
                    'failed_count' => count($results['failed']),
                    'success_items' => $results['success'],
                    'failed_items' => $results['failed']
                ));
            } else {
                // Handle single item in direct format
                $this->log_webhook_data("Processing single item in direct format");
                $result = $this->process_incoming_data($data);
                if ($result) {
                    $this->log_webhook_data("Single item processed successfully: " . json_encode($result, JSON_PRETTY_PRINT));
                    wp_send_json_success(array(
                        'message' => 'Data received and processed successfully',
                        'data' => $result
                    ));
                } else {
                    $this->log_webhook_data("ERROR: Failed to process single item");
                    wp_send_json_error('Failed to process data');
                }
            }
        } catch (Exception $e) {
            $this->log_webhook_data("ERROR: " . $e->getMessage());
            wp_send_json_error('Internal server error: ' . $e->getMessage());
        }
    }
    
    private function process_batch_data($items) {
        $success_items = array();
        $failed_items = array();
        
        foreach ($items as $index => $item) {
            try {
                $result = $this->process_incoming_data($item);
                if ($result) {
                    $success_items[] = array(
                        'index' => $index,
                        'pmid' => $item['pmid'] ?? $item['PMID'] ?? '',
                        'status' => 'success',
                        'data' => $result
                    );
                } else {
                    $failed_items[] = array(
                        'index' => $index,
                        'pmid' => $item['pmid'] ?? $item['PMID'] ?? '',
                        'status' => 'failed',
                        'error' => 'Failed to process item'
                    );
                }
            } catch (Exception $e) {
                $failed_items[] = array(
                    'index' => $index,
                    'pmid' => $item['pmid'] ?? $item['PMID'] ?? '',
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return array(
            'success' => $success_items,
            'failed' => $failed_items
        );
    }
    
    private function process_incoming_data($data) {
        try {
            global $wpdb;

            // Go down one more level - 
            // You should do:
            if (isset($data['json'])) {
                $data = $data['json'];
            }
            $table_name = $wpdb->prefix . 'contentgen_research_data';
            
            // TODO : Check if this works - IT DOES NOT 
            // $wpdb->query("TRUNCATE TABLE $table_name");

            // Log the raw data
            $this->log_webhook_data("Processing data (NO SANITIZATION): " . json_encode($data, JSON_PRETTY_PRINT));

            // Insert all fields with proper data types
            $processed_data = array(
                'pmid' => isset($data['pmid']) ? (int)$data['pmid'] : (isset($data['PMID']) ? (int)$data['PMID'] : 0),
                'date_added' => isset($data['date']) ? $data['date'] : (isset($data['Date']) ? $data['Date'] : (isset($data['Date ']) ? $data['Date '] : current_time('Y-m-d'))),
                'journal' => isset($data['journal']) ? (string)$data['journal'] : (isset($data['Journal']) ? (string)$data['Journal'] : ''),
                'title' => isset($data['title']) ? (string)$data['title'] : (isset($data['Title']) ? (string)$data['Title'] : ''),
                'tweet' => isset($data['tweet']) ? (string)$data['tweet'] : (isset($data['Tweet']) ? (string)$data['Tweet'] : ''),
                'tweet_few_shot' => isset($data['tweetFewShot']) ? (string)$data['tweetFewShot'] : (isset($data['Tweet (Few shot learning)']) ? (string)$data['Tweet (Few shot learning)'] : (isset($data['Tweet Few Shot']) ? (string)$data['Tweet Few Shot'] : '')),
                'doi' => isset($data['doi']) ? (string)$data['doi'] : (isset($data['DOI']) ? (string)$data['DOI'] : ''),
                'type' => isset($data['type']) ? (string)$data['type'] : (isset($data['cancerType']) ? (string)$data['cancerType'] : (isset($data['Cancer Type']) ? (string)$data['Cancer Type'] : (isset($data['Specific Cancer type']) ? (string)$data['Specific Cancer type'] : ''))),
                'query' => isset($data['query']) ? (string)$data['query'] : (isset($data['Query']) ? (string)$data['Query'] : ''),
                'summary' => isset($data['summary']) ? (string)$data['summary'] : (isset($data['Summary']) ? (string)$data['Summary'] : ''),
                'abstract' => isset($data['abstract']) ? (string)$data['abstract'] : (isset($data['Abstract']) ? (string)$data['Abstract'] : ''),
                'twitter_hashtags' => isset($data['twitterHashtags']) ? (string)$data['twitterHashtags'] : (isset($data['Twitter Hashtags']) ? (string)$data['Twitter Hashtags'] : (isset($data['Twiter Hashtags']) ? (string)$data['Twiter Hashtags'] : '')),
                'twitter_accounts' => isset($data['twitterAccounts']) ? (string)$data['twitterAccounts'] : (isset($data['Twitter accounts tagged']) ? (string)$data['Twitter accounts tagged'] : (isset($data['Twiiter accounts tagged']) ? (string)$data['Twiiter accounts tagged'] : '')),
                'score' => isset($data['score']) ? (float)$data['score'] : (isset($data['Score']) ? (float)$data['Score'] : 0.0),
                'status' => 'pending'
            );

            $this->log_webhook_data("Processed data (NO SANITIZATION): " . json_encode($processed_data, JSON_PRETTY_PRINT));

            $result = $wpdb->replace($table_name, $processed_data);
            $this->log_webhook_data("Database result: " . ($result ? "SUCCESS" : "FAILED") . " - Last SQL: " . $wpdb->last_query);
            return $result ? $processed_data : false;
        } catch (Exception $e) {
            $this->log_webhook_data("ERROR in process_incoming_data: " . $e->getMessage());
            return false;
        }
    }
    
    public function get_research_data() {
        error_log('ContentGen: get_research_data called');
        error_log('ContentGen: POST data: ' . print_r($_POST, true));
        error_log('ContentGen: REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
        
        // Check if nonce is provided
        if (!isset($_POST['nonce'])) {
            error_log('ContentGen: ERROR - No nonce provided');
            wp_send_json_error('No nonce provided');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'contentgen_nonce')) {
            error_log('ContentGen: ERROR - Invalid nonce');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        error_log('ContentGen: Nonce verified successfully');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        // Get query parameter if provided
        $selected_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : null;
        
        error_log('ContentGen: Querying table: ' . $table_name);
        error_log('ContentGen: Selected query filter: ' . ($selected_query ? $selected_query : 'none'));
        
        if ($selected_query) {
            // Filter by specific query, treating empty/null query values as 'cancer'
            $data = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name 
                     WHERE status = %s 
                     AND (
                         query = %s 
                         OR (query IS NULL AND %s = 'cancer')
                         OR (query = '' AND %s = 'cancer')
                     )
                     ORDER BY created_at DESC",
                    'pending',
                    $selected_query,
                    $selected_query,
                    $selected_query
                ),
                ARRAY_A
            );
        } else {
            // No query filter - return all pending records
            $data = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC",
                    'pending'
                ),
                ARRAY_A
            );
        }
        
        error_log('ContentGen: Found ' . count($data) . ' records for query: ' . ($selected_query ? $selected_query : 'all'));
        error_log('ContentGen: Data: ' . json_encode($data));
        
        wp_send_json_success($data);
    }
    
    public function get_queries() {
        error_log('ContentGen: get_queries called');
        
        // Check if nonce is provided
        if (!isset($_POST['nonce'])) {
            error_log('ContentGen: ERROR - No nonce provided for get_queries');
            wp_send_json_error('No nonce provided');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'contentgen_nonce')) {
            error_log('ContentGen: ERROR - Invalid nonce for get_queries');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        error_log('ContentGen: get_queries nonce verified successfully');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        error_log('ContentGen: Getting unique queries from table: ' . $table_name);
        
        // Get unique queries, treating empty/null values as 'cancer'
        $queries = $wpdb->get_results(
            "SELECT DISTINCT 
                CASE 
                    WHEN query IS NULL OR query = '' THEN 'cancer'
                    ELSE query 
                END as query_name
            FROM $table_name 
            ORDER BY query_name ASC",
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            error_log('ContentGen: Database error in get_queries: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        // Extract just the query names from the result
        $unique_queries = array_map(function($row) {
            return $row['query_name'];
        }, $queries);
        
        error_log('ContentGen: Found unique queries: ' . json_encode($unique_queries));
        
        wp_send_json_success($unique_queries);
    }


    public function export_tweets() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        $tweets = json_decode(stripslashes($_POST['tweets']), true);
        error_log('ContentGen: tweets data = ' . print_r($tweets, true));
        
        if (!$tweets || !is_array($tweets)) {
            wp_send_json_error('Invalid tweets data');
        }
        
        global $wpdb;
        $accepted_table = $wpdb->prefix . 'contentgen_accepted_tweets';
        $research_table = $wpdb->prefix . 'contentgen_research_data';
        
        // $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($tweets as $tweet) {
                // $wpdb->insert($accepted_table, array(
                //     'pmid' => sanitize_text_field($tweet['pmid']),
                //     'tweet' => sanitize_textarea_field($tweet['tweet'])
                // ));
                
                // Delete from research data using direct SQL
                $pmid = (int)$tweet['pmid'];
                $sql = $wpdb->prepare("DELETE FROM $research_table WHERE pmid = %d", $pmid);
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    error_log('Delete failed for pmid: ' . $tweet['pmid'] . ' - Last error: ' . $wpdb->last_error);
                } else {
                    error_log('Delete result for pmid ' . $tweet['pmid'] . ': ' . $result . ' rows affected');
                }

                $this->log_webhook_data("Deleted from research data: " . $tweet['pmid']);
            }
            
            // $wpdb->query('COMMIT');
            
            // Send to n8n if configured
            $this->send_accepted_tweets_to_n8n($tweets);
            
            wp_send_json_success('Tweets exported successfully');
            
        } catch (Exception $e) {
            // $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to export tweets: ' . $e->getMessage());
        }
    }
    
    private function send_accepted_tweets_to_n8n($tweets) {
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        $webhook_secret = get_option('contentgen_outgoing_webhook_secret', '');
        
        if (empty($webhook_url)) {
            return false;
        }
        
        // Extract query type from first tweet for header routing
        $query_type = 'cancer'; // default
        if (!empty($tweets) && isset($tweets[0]['query'])) {
            $query_type = $tweets[0]['query'];
        }
        
        $payload = array(
            'type' => 'accepted_tweets',
            'data' => $tweets,
            'timestamp' => current_time('c'),
            'source' => 'wordpress_contentgen'
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret,
                'X-Query-Type' => $query_type,
                'X-Content-Source' => 'wordpress_contentgen'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('ContentGen: Failed to send to n8n - ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log the response
        global $wpdb;
        $outgoing_table = $wpdb->prefix . 'contentgen_outgoing_data';
        $wpdb->insert($outgoing_table, array(
            'data_type' => 'accepted_tweets',
            'data_content' => json_encode($payload),
            'status' => $response_code === 200 ? 'sent' : 'failed',
            'n8n_response' => $response_body
        ));
        
        return $response_code === 200;
    }
    
    public function send_to_n8n() {
        $this->log_webhook_data('ContentGen: send_to_n8n function called');
        $this->log_webhook_data('ContentGen: POST data = ' . print_r($_POST, true));
        
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        // Handle both old format (data_type, data_content) and new format (data)
        if (isset($_POST['data'])) {
            // New format from React component
            $data_content = json_decode(stripslashes($_POST['data']), true);
            $data_type = 'final_content_selection';
        } else {
            // Old format for backward compatibility
            $data_type = sanitize_text_field($_POST['data_type']);
            $data_content = $_POST['data_content'];
        }
        
        // Get accepted and declined PMIDs
        $accepted_pmids = isset($_POST['accepted_pmids']) ? json_decode(stripslashes($_POST['accepted_pmids']), true) : array();
        $declined_pmids = isset($_POST['declined_pmids']) ? json_decode(stripslashes($_POST['declined_pmids']), true) : array();
        
        // Check if we should delete data after sending
        $delete_after_send = isset($_POST['delete_after_send']) ? (bool)$_POST['delete_after_send'] : false;
        
        $this->log_webhook_data('ContentGen: Accepted PMIDs = ' . print_r($accepted_pmids, true));
        $this->log_webhook_data('ContentGen: Declined PMIDs = ' . print_r($declined_pmids, true));
        
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        $webhook_secret = get_option('contentgen_outgoing_webhook_secret', '');
        
        if (empty($webhook_url)) {
            wp_send_json_error('Outgoing webhook URL not configured');
        }
        
        // Add query type header for routing
        $query_type = isset($data_content['query']) ? $data_content['query'] : 'cancer';
        
        $payload = array(
            'type' => $data_type,
            'data' => $data_content,
            'timestamp' => current_time('c'),
            'source' => 'wordpress_contentgen'
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret,
                'X-Query-Type' => $query_type,
                'X-Content-Source' => 'wordpress_contentgen'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to send to n8n: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log the response
        global $wpdb;
        $outgoing_table = $wpdb->prefix . 'contentgen_outgoing_data';
        $wpdb->insert($outgoing_table, array(
            'data_type' => $data_type,
            'data_content' => json_encode($payload),
            'status' => $response_code === 200 ? 'sent' : 'failed',
            'n8n_response' => $response_body
        ));
        
        if ($response_code === 200) {
            // Delete data from research table if requested
            if ($delete_after_send) {
                $this->delete_pmids_from_research_table($accepted_pmids, $declined_pmids);
            }
            
            wp_send_json_success('Data sent to n8n successfully');
        } else {
            wp_send_json_error('n8n returned error: ' . $response_code);
        }
    }

    private function delete_pmids_from_research_table($accepted_pmids, $declined_pmids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        $this->log_webhook_data('ContentGen: Deleting PMIDs from research table');
        $this->log_webhook_data('ContentGen: Accepted PMIDs to delete = ' . print_r($accepted_pmids, true));
        $this->log_webhook_data('ContentGen: Declined PMIDs to delete = ' . print_r($declined_pmids, true));
        
        // Combine all PMIDs to delete
        $all_pmids_to_delete = array_merge($accepted_pmids, $declined_pmids);
        $all_pmids_to_delete = array_unique($all_pmids_to_delete);
        
        $this->log_webhook_data('ContentGen: All PMIDs to delete = ' . print_r($all_pmids_to_delete, true));
        
        // Delete each PMID
        $deleted_count = 0;
        foreach ($all_pmids_to_delete as $pmid) {
            $result = $wpdb->delete($table_name, array('pmid' => (int)$pmid));
            if ($result !== false) {
                $deleted_count += $result;
                $this->log_webhook_data('ContentGen: Deleted pmid ' . $pmid . ' - rows affected: ' . $result);
            } else {
                $this->log_webhook_data('ContentGen: Failed to delete pmid ' . $pmid . ' - error: ' . $wpdb->last_error);
            }
        }
        
        $this->log_webhook_data('ContentGen: Total rows deleted: ' . $deleted_count);
        return $deleted_count;
    }
    
    public function test_outgoing_webhook() {
        check_ajax_referer('contentgen_test', 'nonce');
        
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        
        if (empty($webhook_url)) {
            wp_send_json_error('Outgoing webhook URL not configured');
        }
        
        $test_payload = array(
            'type' => 'test',
            'message' => 'Test from WordPress ContentGen',
            'timestamp' => current_time('c')
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($test_payload),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            wp_send_json_success('Connection successful! n8n webhook is working.');
        } else {
            wp_send_json_error('Connection failed. HTTP code: ' . $response_code);
        }
    }
    
    public function test_ajax() {
        error_log('ContentGen: test_ajax called');
        error_log('ContentGen: POST data: ' . print_r($_POST, true));
        
        wp_send_json_success(array(
            'message' => 'AJAX is working!',
            'timestamp' => current_time('c'),
            'post_data' => $_POST
        ));
    }
    
    public function accept_tweet() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        $pmid = sanitize_text_field($_POST['pmid']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'accepted'),
            array('pmid' => $pmid)
        );
        
        if ($result !== false) {
            wp_send_json_success('Tweet accepted');
        } else {
            wp_send_json_error('Failed to accept tweet');
        }
    }
    
    public function decline_tweet() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        $pmid = sanitize_text_field($_POST['pmid']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'declined'),
            array('pmid' => $pmid)
        );
        
        if ($result !== false) {
            wp_send_json_success('Tweet declined');
        } else {
            wp_send_json_error('Failed to decline tweet');
        }
    }
    
    public function update_tweet() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        $pmid = sanitize_text_field($_POST['pmid']);
        $tweet = sanitize_textarea_field($_POST['tweet']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentgen_research_data';
        
        $result = $wpdb->update(
            $table_name,
            array('tweet' => $tweet),
            array('pmid' => $pmid)
        );
        
        if ($result !== false) {
            wp_send_json_success('Tweet updated');
        } else {
            wp_send_json_error('Failed to update tweet');
        }
    }
    
    public function dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Research Tweet Manager'
        ), $atts);
        
        // Ensure scripts are loaded (use registered handles, not hardcoded files)
        wp_enqueue_style('contentgen-styles');
        wp_enqueue_script('contentgen-script');
        
        ob_start();
        ?>
        <div id="contentgen-app" class="contentgen-container">
            <div class="contentgen-loading">Loading ContentGen...</div>
        </div>
        
        <!-- Test AJAX button -->
        <button id="testAjax" style="margin: 10px 0; padding: 10px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Test AJAX Connection
        </button>
        <div id="ajaxTestResult" style="margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; display: none;"></div>
        
        <!-- Note: Scripts are loaded via WordPress enqueue with cache busting -->
        
        <script>
        // Provide WordPress data directly to React app
        window.contentgen_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('contentgen_nonce'); ?>',
            plugin_url: '<?php echo CONTENTGEN_PLUGIN_URL; ?>',
            version: '<?php echo CONTENTGEN_VERSION; ?>',
            strings: {
                loading: '<?php echo __('Loading...', 'contentgen'); ?>',
                error: '<?php echo __('An error occurred', 'contentgen'); ?>',
                success: '<?php echo __('Success!', 'contentgen'); ?>'
            }
        };
        
        console.log('ContentGen shortcode loaded');
        console.log('Plugin URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>');
        console.log('Expected JS URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>assets/assets/' . CONTENTGEN_JS_FILE);
        console.log('Expected CSS URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>assets/assets/' . CONTENTGEN_CSS_FILE);
        console.log('Container found:', !!document.getElementById('contentgen-app'));
        console.log('WordPress data provided:', window.contentgen_ajax);
        
        // Check for scripts after DOM is loaded
        function checkScripts() {
            console.log('ContentGen: Checking for loaded scripts...');
            console.log('CSS loaded:', !!document.querySelector('link[href*="' . CONTENTGEN_CSS_FILE . '"]'));
            console.log('JS loaded:', !!document.querySelector('script[src*="' . CONTENTGEN_JS_FILE . '"]'));
            
            // List all script tags for debugging
            const allScripts = document.querySelectorAll('script[src]');
            console.log('ContentGen: All script tags found:', allScripts.length);
            allScripts.forEach((script, index) => {
                console.log(`ContentGen: Script ${index}:`, script.src);
            });
            
            // List all link tags for debugging
            const allLinks = document.querySelectorAll('link[href]');
            console.log('ContentGen: All link tags found:', allLinks.length);
            allLinks.forEach((link, index) => {
                if (link.href.includes('contentgen')) {
                    console.log(`ContentGen: Link ${index}:`, link.href);
                }
            });
        }
        
        // Check immediately
        checkScripts();
        
        // Check again after DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkScripts);
        } else {
            setTimeout(checkScripts, 100);
        }
        
        // Ensure the container exists and React app can mount
        if (document.getElementById('contentgen-app')) {
            console.log('ContentGen app container found');
        } else {
            console.error('ContentGen app container not found');
        }
        
        // Check if React app loads
        setTimeout(function() {
            if (document.getElementById('contentgen-app') && 
                document.getElementById('contentgen-app').innerHTML.includes('Loading ContentGen')) {
                console.error('ContentGen: React app failed to load or mount');
            } else {
                console.log('ContentGen: React app loaded successfully');
            }
        }, 3000);
        
        // Test AJAX functionality
        document.getElementById('testAjax').addEventListener('click', function() {
            console.log('ContentGen: Testing AJAX connection...');
            console.log('ContentGen: AJAX URL:', window.contentgen_ajax.ajax_url);
            console.log('ContentGen: Nonce:', window.contentgen_ajax.nonce);
            
            const resultDiv = document.getElementById('ajaxTestResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing AJAX connection...';
            
            fetch(window.contentgen_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'contentgen_test_ajax',
                    nonce: window.contentgen_ajax.nonce,
                    test: 'data'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('ContentGen: AJAX test response:', data);
                resultDiv.innerHTML = '<strong>AJAX Test Result:</strong><br>' + JSON.stringify(data, null, 2);
                resultDiv.style.backgroundColor = data.success ? '#d4edda' : '#f8d7da';
                resultDiv.style.color = data.success ? '#155724' : '#721c24';
            })
            .catch(error => {
                console.error('ContentGen: AJAX test error:', error);
                resultDiv.innerHTML = '<strong>AJAX Test Error:</strong><br>' + error.message;
                resultDiv.style.backgroundColor = '#f8d7da';
                resultDiv.style.color = '#721c24';
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_youtube_webhook() {
        // Similar to handle_webhook but specifically for YouTube content
        // You can add YouTube-specific processing here if needed
        return $this->handle_webhook();
    }
    
    // === SCHEDULER FUNCTIONS ===
    
    public function schedule_tweets() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        $tweets_data = json_decode(stripslashes($_POST['tweets_data']), true);
        
        if (!$tweets_data || !is_array($tweets_data)) {
            wp_send_json_error('Invalid tweets data');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        $scheduled_count = 0;
        $timezone_info = array();
        
        foreach ($tweets_data as $tweet_data) {
            // Convert scheduled datetime to UTC
            $timezone_offset = isset($tweet_data['timezone_offset_minutes']) ? intval($tweet_data['timezone_offset_minutes']) : null;
            $scheduled_datetime = $this->convert_to_utc($tweet_data['scheduled_datetime'], $timezone_offset);
            
            // Log timezone conversion for debugging
            $timezone_info[] = array(
                'pmid' => $tweet_data['pmid'],
                'original_datetime' => $tweet_data['scheduled_datetime'],
                'converted_utc_datetime' => $scheduled_datetime,
                'timezone_offset_minutes' => $timezone_offset,
                'timezone_offset_hours' => $timezone_offset ? $timezone_offset / 60 : null,
                'wp_timezone' => wp_timezone_string(),
                'stored_as' => 'UTC'
            );
            
            $result = $wpdb->insert($table, array(
                'pmid' => sanitize_text_field($tweet_data['pmid']),
                'query' => sanitize_text_field($tweet_data['query']),
                'tweet_content' => sanitize_textarea_field($tweet_data['tweet_content']),
                'scheduled_datetime' => $scheduled_datetime,
                'tweet_data' => json_encode($tweet_data),
                'status' => 'pending',
                'created_at' => current_time('mysql'),  // Explicitly use WordPress timezone
                'updated_at' => current_time('mysql')   // Explicitly use WordPress timezone
            ));
            
            if ($result) {
                $scheduled_count++;
            } else {
                error_log('ContentGen Schedule: Failed to insert tweet ' . $tweet_data['pmid'] . ' - ' . $wpdb->last_error);
            }
        }
        
        // Log timezone conversion info
        error_log('ContentGen Schedule: Timezone conversions - ' . json_encode($timezone_info, JSON_PRETTY_PRINT));
        
        wp_send_json_success(array(
            'message' => "Successfully scheduled {$scheduled_count} tweets",
            'scheduled_count' => $scheduled_count,
            'timezone_info' => $timezone_info
        ));
    }
    
    private function convert_to_utc($datetime_string, $timezone_offset_minutes = null) {
        try {
            // Log input for debugging
            error_log("ContentGen Schedule: Converting to UTC - Input: {$datetime_string}");
            error_log("ContentGen Schedule: Timezone offset (minutes): " . ($timezone_offset_minutes ?? 'null'));
            
            // If we have timezone offset from frontend, use it for conversion
            if ($timezone_offset_minutes !== null) {
                // JavaScript getTimezoneOffset() returns positive minutes for timezones west of UTC
                // So we need to ADD the offset to convert to UTC
                $timestamp = strtotime($datetime_string);
                $utc_timestamp = $timestamp + ($timezone_offset_minutes * 60);
                $utc_formatted = gmdate('Y-m-d H:i:s', $utc_timestamp);
                
                error_log("ContentGen Schedule: Frontend timezone conversion - Local: {$datetime_string} -> UTC: {$utc_formatted} (offset: +{$timezone_offset_minutes} min)");
            } else {
                // Fallback: assume input is in WordPress timezone
                $wp_timezone = wp_timezone();
                $wp_timezone_string = wp_timezone_string();
                
                error_log("ContentGen Schedule: WordPress timezone fallback - Source Timezone: {$wp_timezone_string}");
                
                // Create DateTime object in WordPress timezone
                $datetime = new DateTime($datetime_string, $wp_timezone);
                
                // Convert to UTC
                $datetime->setTimezone(new DateTimeZone('UTC'));
                $utc_formatted = $datetime->format('Y-m-d H:i:s');
                
                error_log("ContentGen Schedule: WordPress timezone conversion - Local: {$datetime_string} -> UTC: {$utc_formatted}");
            }
            
            error_log("ContentGen Schedule: Current UTC time for reference: " . gmdate('Y-m-d H:i:s'));
            
            return $utc_formatted;
        } catch (Exception $e) {
            // Fallback: use current UTC time if parsing fails
            error_log('ContentGen Schedule: DateTime parsing error - ' . $e->getMessage());
            return gmdate('Y-m-d H:i:s');
        }
    }
    
    public function get_scheduled_tweets() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : null;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending';
        
        // If status is 'all', don't filter by status
        if ($status === 'all') {
            $sql = "SELECT * FROM $table WHERE 1=1";
            $params = array();
        } else {
            $sql = "SELECT * FROM $table WHERE status = %s";
            $params = array($status);
        }
        
        if ($query) {
            $sql .= " AND query = %s";
            $params[] = $query;
        }
        
        $sql .= " ORDER BY scheduled_datetime ASC";
        
        $scheduled_tweets = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        wp_send_json_success($scheduled_tweets);
    }
    
    public function admin_get_scheduled_tweets() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        $sql = "SELECT * FROM $table WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        if (!empty($query)) {
            $sql .= " AND query = %s";
            $params[] = $query;
        }
        
        $sql .= " ORDER BY scheduled_datetime DESC LIMIT 50";
        
        if (!empty($params)) {
            $scheduled_tweets = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            $scheduled_tweets = $wpdb->get_results($sql, ARRAY_A);
        }
        
        wp_send_json_success($scheduled_tweets);
    }
    
    public function update_scheduled_tweet() {
        check_ajax_referer('contentgen_nonce', 'nonce');
        
        // Get parameters
        $tweet_id = isset($_POST['tweet_id']) ? intval($_POST['tweet_id']) : 0;
        $tweet_content = isset($_POST['tweet_content']) ? sanitize_textarea_field($_POST['tweet_content']) : '';
        $scheduled_datetime = isset($_POST['scheduled_datetime']) ? sanitize_text_field($_POST['scheduled_datetime']) : '';
        
        // Validate parameters
        if (!$tweet_id || !$tweet_content || !$scheduled_datetime) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Validate and convert datetime
        try {
            // Convert from local datetime-local input to UTC for database storage
            $local_datetime = new DateTime($scheduled_datetime, wp_timezone());
            $utc_datetime = clone $local_datetime;
            $utc_datetime->setTimezone(new DateTimeZone('UTC'));
            $utc_string = $utc_datetime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            wp_send_json_error('Invalid datetime format');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        // Check if tweet exists and get current data
        $existing_tweet = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $tweet_id),
            ARRAY_A
        );
        
        if (!$existing_tweet) {
            wp_send_json_error('Tweet not found');
            return;
        }
        
        // Only allow editing pending tweets
        if ($existing_tweet['status'] !== 'pending') {
            wp_send_json_error('Cannot edit tweets that have already been sent or failed');
            return;
        }
        
        // Update the tweet
        $updated = $wpdb->update(
            $table,
            array(
                'tweet_content' => $tweet_content,
                'scheduled_datetime' => $utc_string,
                'updated_at' => current_time('mysql', true) // UTC time
            ),
            array('id' => $tweet_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            error_log('ContentGen: Failed to update scheduled tweet - ' . $wpdb->last_error);
            wp_send_json_error('Database update failed');
            return;
        }
        
        // Log the update
        error_log("ContentGen: Updated scheduled tweet {$tweet_id} - Content: " . substr($tweet_content, 0, 50) . "... DateTime: {$utc_string} UTC");
        
        wp_send_json_success(array(
            'message' => 'Tweet updated successfully',
            'tweet_id' => $tweet_id,
            'updated_content' => $tweet_content,
            'updated_datetime' => $utc_string,
            'local_datetime' => $scheduled_datetime
        ));
    }
    
    public function get_scheduled_queries() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        $queries = $wpdb->get_results(
            "SELECT DISTINCT query FROM $table ORDER BY query ASC",
            ARRAY_A
        );
        
        $query_list = array_map(function($row) { return $row['query']; }, $queries);
        
        wp_send_json_success($query_list);
    }
    
    public function test_scheduler_logic() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        // Get current UTC time info
        $current_utc_time = gmdate('Y-m-d H:i:s');
        $current_timestamp = strtotime($current_utc_time . ' UTC');
        $window_start = gmdate('Y-m-d H:i:s', $current_timestamp - 300); // 5 minutes ago UTC
        $window_end = gmdate('Y-m-d H:i:s', $current_timestamp + 300);   // 5 minutes from now UTC
        
        // Get due tweets (same logic as cron job)
        $due_tweets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'pending' 
             AND scheduled_datetime BETWEEN %s AND %s 
             AND attempts < 3
             ORDER BY scheduled_datetime ASC",
            $window_start,
            $window_end
        ), ARRAY_A);
        
        // Get all pending tweets for comparison
        $all_pending = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'pending' ORDER BY scheduled_datetime ASC",
            ARRAY_A
        );
        
        $result = "=== SCHEDULER LOGIC TEST ===\n\n";
        $result .= "Current UTC Time: {$current_utc_time}\n";
        $result .= "Processing Window: {$window_start} to {$window_end}\n";
        $result .= "WordPress Timezone: " . wp_timezone_string() . "\n\n";
        
        $result .= "TWEETS DUE NOW (within 5-minute window): " . count($due_tweets) . "\n";
        if (count($due_tweets) > 0) {
            foreach ($due_tweets as $tweet) {
                $result .= "- ID {$tweet['id']}: PMID {$tweet['pmid']}, Scheduled: {$tweet['scheduled_datetime']} UTC\n";
            }
        } else {
            $result .= "No tweets are due for processing right now.\n";
        }
        
        $result .= "\nALL PENDING TWEETS: " . count($all_pending) . "\n";
        foreach ($all_pending as $tweet) {
            $scheduled_time = strtotime($tweet['scheduled_datetime'] . ' UTC');
            $time_diff = $scheduled_time - $current_timestamp;
            $time_diff_minutes = round($time_diff / 60);
            
            $result .= "- ID {$tweet['id']}: PMID {$tweet['pmid']}, Scheduled: {$tweet['scheduled_datetime']} UTC";
            $result .= " (in {$time_diff_minutes} minutes)\n";
        }
        
        wp_send_json_success($result);
    }
    
    public function check_cron_status() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        $result = "=== CRON JOB STATUS ===\n\n";
        
        // Check if cron is scheduled
        $next_scheduled = wp_next_scheduled('contentgen_process_scheduled_tweets_hook');
        if ($next_scheduled) {
            $next_run = date('Y-m-d H:i:s', $next_scheduled);
            $time_until = $next_scheduled - time();
            $minutes_until = round($time_until / 60);
            $result .= "✅ Cron Job is SCHEDULED\n";
            $result .= "Next run: {$next_run} (in {$minutes_until} minutes)\n";
        } else {
            $result .= "❌ Cron Job is NOT SCHEDULED\n";
        }
        
        // Check webhook configuration
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        $webhook_secret = get_option('contentgen_outgoing_webhook_secret', '');
        
        if (empty($webhook_url)) {
            $result .= "❌ Webhook URL is NOT configured\n";
        } else {
            $result .= "✅ Webhook URL is configured: " . substr($webhook_url, 0, 50) . "...\n";
        }
        
        if (empty($webhook_secret)) {
            $result .= "❌ Webhook Secret is NOT configured\n";
        } else {
            $result .= "✅ Webhook Secret is configured\n";
        }
        
        // Current time info
        $result .= "\nCurrent UTC Time: " . gmdate('Y-m-d H:i:s') . "\n";
        $result .= "WordPress Timezone: " . wp_timezone_string() . "\n";
        
        // Check for due tweets
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        $current_utc_time = gmdate('Y-m-d H:i:s');
        $current_timestamp = strtotime($current_utc_time . ' UTC');
        $window_start = gmdate('Y-m-d H:i:s', $current_timestamp - 300);
        $window_end = gmdate('Y-m-d H:i:s', $current_timestamp + 300);
        
        $due_tweets = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as count FROM $table 
             WHERE status = 'pending' 
             AND scheduled_datetime BETWEEN %s AND %s 
             AND attempts < 3",
            $window_start,
            $window_end
        ), ARRAY_A);
        
        $due_count = $due_tweets[0]['count'] ?? 0;
        $result .= "\nTweets due for processing RIGHT NOW: {$due_count}\n";
        
        if ($due_count > 0) {
            $result .= "⚠️  There are tweets waiting to be processed!\n";
        }
        
        wp_send_json_success($result);
    }
    
    public function run_cron_manually() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        $result = "=== MANUAL CRON EXECUTION ===\n\n";
        $result .= "Execution started at: " . gmdate('Y-m-d H:i:s') . " UTC\n\n";
        
        // Capture any output from the cron job
        ob_start();
        
        try {
            // Run the actual cron job function
            $this->process_scheduled_tweets();
            
            $output = ob_get_clean();
            
            $result .= "✅ Cron job executed successfully\n\n";
            
            if (!empty($output)) {
                $result .= "Output:\n" . $output . "\n";
            }
            
            // Check how many tweets were processed
            global $wpdb;
            $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
            
            $recent_sent = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE status = 'sent' 
                 AND sent_at >= %s
                 ORDER BY sent_at DESC",
                gmdate('Y-m-d H:i:s', time() - 60) // Last minute
            ), ARRAY_A);
            
            $processed_count = count($recent_sent);
            $result .= "Tweets processed in this run: {$processed_count}\n";
            
            if ($processed_count > 0) {
                $result .= "\nRecently sent tweets:\n";
                foreach ($recent_sent as $tweet) {
                    $result .= "- ID {$tweet['id']}: PMID {$tweet['pmid']}, Sent at: {$tweet['sent_at']}\n";
                }
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            $result .= "❌ Error during cron execution: " . $e->getMessage() . "\n";
        }
        
        wp_send_json_success($result);
    }
    
    public function reschedule_cron() {
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Access denied');
            return;
        }
        
        check_ajax_referer('contentgen_admin_test', 'nonce');
        
        $result = "=== CRON RESCHEDULING ===\n\n";
        
        // Clear any existing scheduled hook
        $existing_schedule = wp_next_scheduled('contentgen_process_scheduled_tweets_hook');
        if ($existing_schedule) {
            wp_unschedule_event($existing_schedule, 'contentgen_process_scheduled_tweets_hook');
            $result .= "✅ Cleared existing cron schedule (was: " . date('Y-m-d H:i:s', $existing_schedule) . ")\n";
        } else {
            $result .= "ℹ️  No existing cron schedule found\n";
        }
        
        // Schedule new cron job to run every 5 minutes, starting now
        if (wp_schedule_event(time(), 'contentgen_5min', 'contentgen_process_scheduled_tweets_hook')) {
            $next_run = wp_next_scheduled('contentgen_process_scheduled_tweets_hook');
            $result .= "✅ Rescheduled cron job successfully\n";
            $result .= "Next run: " . date('Y-m-d H:i:s', $next_run) . " (in " . round(($next_run - time()) / 60) . " minutes)\n";
        } else {
            $result .= "❌ Failed to reschedule cron job\n";
        }
        
        wp_send_json_success($result);
    }
    
    public function handle_external_cron() {
        // Verify request is from external cron
        $cron_secret = get_option('contentgen_cron_secret', '');
        $provided_secret = isset($_GET['secret']) ? sanitize_text_field($_GET['secret']) : '';
        
        // If no secret is set, create one
        if (empty($cron_secret)) {
            $cron_secret = wp_generate_password(32, false);
            update_option('contentgen_cron_secret', $cron_secret);
        }
        
        // Verify secret or allow if secret is not provided (for initial setup)
        if (!empty($provided_secret) && $provided_secret !== $cron_secret) {
            http_response_code(401);
            wp_die('Unauthorized access', 'Unauthorized', array('response' => 401));
        }
        
        // Log cron execution
        error_log('ContentGen External Cron: Called at ' . gmdate('Y-m-d H:i:s') . ' UTC - Sending test request to n8n');
        
        try {
            // Always send a test request to n8n to verify cron is working
            $n8n_response = $this->send_cron_test_to_n8n();
            
            // Return success response
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'message' => 'External cron executed successfully - Test request sent to n8n',
                'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
                'n8n_response' => $n8n_response,
                'secret' => empty($provided_secret) ? $cron_secret : null // Only return secret if not provided
            ));
        } catch (Exception $e) {
            error_log('ContentGen External Cron Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(array(
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC'
            ));
        }
        
        wp_die(); // Important: prevent further WordPress output
    }
    
    public function handle_n8n_poll() {
        // Log polling request (no authentication required)
        error_log('ContentGen n8n Poll: Checking for scheduled tweets at ' . gmdate('Y-m-d H:i:s') . ' UTC');
        
        try {
            // Get due tweets from the last 15 minutes
            $due_tweets = $this->get_due_scheduled_tweets(15);
            
            if (empty($due_tweets)) {
                // No tweets due
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => true,
                    'message' => 'No scheduled tweets due',
                    'tweets_count' => 0,
                    'tweets' => array(),
                    'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
                    'window_minutes' => 15
                ));
            } else {
                // Mark tweets as sent and return them
                $processed_tweets = $this->mark_tweets_as_sent($due_tweets);
                
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Scheduled tweets found and marked as sent',
                    'tweets_count' => count($processed_tweets),
                    'tweets' => $processed_tweets,
                    'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
                    'window_minutes' => 15
                ));
                
                error_log('ContentGen n8n Poll: Returned ' . count($processed_tweets) . ' due tweets');
            }
        } catch (Exception $e) {
            error_log('ContentGen n8n Poll Error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC'
            ));
        }
        
        wp_die(); // Important: prevent further WordPress output
    }
    
    private function get_due_scheduled_tweets($window_minutes = 15) {
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        // Get current UTC time and calculate window
        $current_utc_time = gmdate('Y-m-d H:i:s');
        $current_timestamp = strtotime($current_utc_time . ' UTC');
        $window_start = gmdate('Y-m-d H:i:s', $current_timestamp - ($window_minutes * 60)); // X minutes ago
        $window_end = gmdate('Y-m-d H:i:s', $current_timestamp + 300); // 5 minutes from now (buffer)
        
        error_log("ContentGen n8n Poll: Checking UTC window {$window_start} to {$window_end} (Current UTC: {$current_utc_time})");
        
        $due_tweets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'pending' 
             AND scheduled_datetime BETWEEN %s AND %s 
             AND attempts < 3
             ORDER BY scheduled_datetime ASC",
            $window_start,
            $window_end
        ), ARRAY_A);
        
        return $due_tweets;
    }
    
    private function mark_tweets_as_sent($tweets) {
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        $processed_tweets = array();
        
        foreach ($tweets as $tweet) {
            // Mark as sent in database
            $wpdb->update($table, 
                array(
                    'status' => 'sent',
                    'sent_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $tweet['id'])
            );
            
            // Prepare tweet data for n8n (without metadata)
            $processed_tweets[] = array(
                'id' => $tweet['id'],
                'pmid' => $tweet['pmid'],
                'query' => $tweet['query'],
                'tweet_content' => $tweet['tweet_content'],
                'scheduled_datetime' => $tweet['scheduled_datetime'],
                'sent_at' => current_time('mysql')
            );
            
            error_log("ContentGen n8n Poll: Marked tweet {$tweet['id']} (PMID: {$tweet['pmid']}) as sent");
        }
        
        return $processed_tweets;
    }
    
    private function send_cron_test_to_n8n() {
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        $webhook_secret = get_option('contentgen_outgoing_webhook_secret', '');
        
        if (empty($webhook_url)) {
            error_log('ContentGen Cron Test: No webhook URL configured');
            return array(
                'status' => 'error',
                'message' => 'No webhook URL configured'
            );
        }
        
        // Create test payload
        $payload = array(
            'type' => 'cron_test',
            'message' => 'Test from WordPress ContentGen External Cron',
            'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
            'source' => 'wordpress_contentgen_external_cron',
            'test_data' => array(
                'cron_working' => true,
                'server_time' => gmdate('Y-m-d H:i:s'),
                'wordpress_timezone' => wp_timezone_string()
            )
        );
        
        error_log('ContentGen Cron Test: Sending test payload to n8n: ' . json_encode($payload));
        
        // Send request to n8n
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret,
                'X-Query-Type' => 'cron_test',
                'X-Content-Source' => 'wordpress_contentgen_external_cron'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('ContentGen Cron Test: HTTP Error - ' . $response->get_error_message());
            return array(
                'status' => 'error',
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log('ContentGen Cron Test: n8n responded with code ' . $response_code . ', body: ' . $response_body);
        
        return array(
            'status' => $response_code === 200 ? 'success' : 'error',
            'http_code' => $response_code,
            'response_body' => $response_body,
            'webhook_url' => $webhook_url
        );
    }
    
    public function process_scheduled_tweets() {
        global $wpdb;
        $table = $wpdb->prefix . 'contentgen_scheduled_tweets';
        
        // Get tweets that are due (current time ± 5 minutes) - all in UTC
        $current_utc_time = gmdate('Y-m-d H:i:s');  // Current UTC time
        $current_timestamp = strtotime($current_utc_time . ' UTC');
        $window_start = gmdate('Y-m-d H:i:s', $current_timestamp - 300); // 5 minutes ago UTC
        $window_end = gmdate('Y-m-d H:i:s', $current_timestamp + 300);   // 5 minutes from now UTC
        
        error_log("ContentGen Scheduler: Processing UTC window {$window_start} to {$window_end} (Current UTC: {$current_utc_time})");
        
        $due_tweets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'pending' 
             AND scheduled_datetime BETWEEN %s AND %s 
             AND attempts < 3
             ORDER BY scheduled_datetime ASC",
            $window_start,
            $window_end
        ), ARRAY_A);
        
        $webhook_url = get_option('contentgen_outgoing_webhook_url', '');
        $webhook_secret = get_option('contentgen_outgoing_webhook_secret', '');
        
        if (empty($webhook_url)) {
            error_log('ContentGen Scheduler: No webhook URL configured');
            return;
        }
        
        foreach ($due_tweets as $tweet) {
            $success = $this->send_scheduled_tweet($tweet, $webhook_url, $webhook_secret);
            
            if ($success) {
                // Mark as sent
                $wpdb->update($table, 
                    array(
                        'status' => 'sent',
                        'sent_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $tweet['id'])
                );
                error_log("ContentGen Scheduler: Successfully sent tweet {$tweet['id']} for query {$tweet['query']}");
            } else {
                // Increment attempts and schedule retry with exponential backoff
                $attempts = intval($tweet['attempts']) + 1;
                $retry_time = gmdate('Y-m-d H:i:s', $current_timestamp + (300 * $attempts)); // Exponential backoff in UTC
                
                $wpdb->update($table,
                    array(
                        'attempts' => $attempts,
                        'scheduled_datetime' => $retry_time,
                        'last_error' => 'Failed to send to n8n webhook',
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $tweet['id'])
                );
                error_log("ContentGen Scheduler: Failed to send tweet {$tweet['id']}, attempt {$attempts}, retrying at {$retry_time}");
            }
        }
    }
    
    private function send_scheduled_tweet($tweet, $webhook_url, $webhook_secret) {
        $payload = array(
            'type' => 'scheduled_tweet',
            'query' => $tweet['query'],
            'text' => $tweet['tweet_content'],
            'metadata' => json_decode($tweet['tweet_data'], true),
            'timestamp' => current_time('c'),
            'source' => 'wordpress_contentgen_scheduler'
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret,
                'X-Query-Type' => $tweet['query'],
                'X-Content-Source' => 'wordpress_contentgen_scheduler'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('ContentGen Scheduler: HTTP Error - ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
}

// Initialize the plugin
new ContentGen(); 