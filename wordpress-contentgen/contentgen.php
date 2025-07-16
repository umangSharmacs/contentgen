<?php
/**
 * Plugin Name: ContentGen - Research Tweet Manager
 * Plugin URI: https://yourdomain.com/contentgen
 * Description: A WordPress plugin for managing research tweets and content generation from n8n workflows with bidirectional data flow
 * Version: 1.9.9
 * Author: Umang Sharma
 * License: GPL v2 or later
 * Text Domain: contentgen
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CONTENTGEN_VERSION', '1.9.9');
define('CONTENTGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTENTGEN_PLUGIN_PATH', plugin_dir_path(__FILE__));

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
        
        // Add shortcode
        add_shortcode('contentgen_dashboard', array($this, 'dashboard_shortcode'));
        
        // Create database tables on activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function activate() {
        $this->create_tables();
        
        // Set default options
        add_option('contentgen_webhook_secret', wp_generate_password(32, false));
        add_option('contentgen_outgoing_webhook_url', '');
        add_option('contentgen_outgoing_webhook_secret', wp_generate_password(32, false));
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
            tweet text,
            tweet_few_shot text,
            doi varchar(255),
            cancer_type varchar(255),
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
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
        
        ?>
        <div class="wrap">
            <h1>ContentGen Settings</h1>
            
            <h2>Incoming Webhook (from n8n)</h2>
            <p><strong>URL:</strong> <code><?php echo admin_url('admin-ajax.php?action=contentgen_webhook'); ?></code></p>
            <p><strong>Secret:</strong> <code><?php echo esc_html($incoming_secret); ?></code></p>
            
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
        </div>
        
        <script>
        jQuery(document).ready(function($) {
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
        });
        </script>
        <?php
    }
    
    public function enqueue_scripts() {
        // Load the built React application (static reference to latest build)
        wp_enqueue_style('contentgen-styles', CONTENTGEN_PLUGIN_URL . 'assets/assets/index-1752703313223.css', array(), CONTENTGEN_VERSION);
        wp_enqueue_script('contentgen-script', CONTENTGEN_PLUGIN_URL . 'assets/assets/index-1752703313184.js', array(), CONTENTGEN_VERSION, true);

        wp_localize_script('contentgen-script', 'contentgen_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentgen_nonce'),
            'plugin_url' => CONTENTGEN_PLUGIN_URL,
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
        wp_enqueue_style('contentgen-admin-styles', CONTENTGEN_PLUGIN_URL . 'assets/css/admin.css', array(), CONTENTGEN_VERSION);
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
                'tweet' => isset($data['tweet']) ? (string)$data['tweet'] : (isset($data['Tweet']) ? (string)$data['Tweet'] : ''),
                'tweet_few_shot' => isset($data['tweetFewShot']) ? (string)$data['tweetFewShot'] : (isset($data['Tweet (Few shot learning)']) ? (string)$data['Tweet (Few shot learning)'] : (isset($data['Tweet Few Shot']) ? (string)$data['Tweet Few Shot'] : '')),
                'doi' => isset($data['doi']) ? (string)$data['doi'] : (isset($data['DOI']) ? (string)$data['DOI'] : ''),
                'cancer_type' => isset($data['cancerType']) ? (string)$data['cancerType'] : (isset($data['Cancer Type']) ? (string)$data['Cancer Type'] : (isset($data['Specific Cancer type']) ? (string)$data['Specific Cancer type'] : '')),
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
        
        error_log('ContentGen: Querying table: ' . $table_name);
        
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC",
                'pending'
            ),
            ARRAY_A
        );
        
        error_log('ContentGen: Found ' . count($data) . ' records');
        error_log('ContentGen: Data: ' . json_encode($data));
        
        wp_send_json_success($data);
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
        
        $payload = array(
            'type' => 'accepted_tweets',
            'data' => $tweets,
            'timestamp' => current_time('c'),
            'source' => 'wordpress_contentgen'
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret
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
        
        $payload = array(
            'type' => $data_type,
            'data' => $data_content,
            'timestamp' => current_time('c'),
            'source' => 'wordpress_contentgen'
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'webhook-secret' => $webhook_secret
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
            strings: {
                loading: '<?php echo __('Loading...', 'contentgen'); ?>',
                error: '<?php echo __('An error occurred', 'contentgen'); ?>',
                success: '<?php echo __('Success!', 'contentgen'); ?>'
            }
        };
        
        console.log('ContentGen shortcode loaded');
        console.log('Plugin URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>');
        console.log('Expected JS URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>assets/assets/index-1752630789451.js');
        console.log('Expected CSS URL:', '<?php echo CONTENTGEN_PLUGIN_URL; ?>assets/assets/index-1752630789497.css');
        console.log('Container found:', !!document.getElementById('contentgen-app'));
        console.log('WordPress data provided:', window.contentgen_ajax);
        
        // Check for scripts after DOM is loaded
        function checkScripts() {
            console.log('ContentGen: Checking for loaded scripts...');
            console.log('CSS loaded:', !!document.querySelector('link[href*="index-1752543051935.css"]'));
            console.log('JS loaded:', !!document.querySelector('script[src*="index-1752543051878.js"]'));
            
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
}

// Initialize the plugin
new ContentGen(); 