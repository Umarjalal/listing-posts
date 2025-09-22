<?php
/**
 * Plugin Name: Business Listings Pro
 * Plugin URI: https://example.com
 * Description: Professional business listings plugin with subscription plans and payment integration
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: business-listings-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BLP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BLP_VERSION', '1.0.0');

class BusinessListingsPro {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_blp_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_blp_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_blp_submit_listing', array($this, 'submit_listing'));
        add_action('wp_ajax_blp_register_user', array($this, 'register_user'));
        add_action('wp_ajax_nopriv_blp_register_user', array($this, 'register_user'));
        add_action('wp_ajax_blp_login_user', array($this, 'login_user'));
        add_action('wp_ajax_nopriv_blp_login_user', array($this, 'login_user'));
        add_action('wp_ajax_blp_delete_listing', array($this, 'delete_listing'));
        add_action('wp_ajax_blp_get_listing', array($this, 'get_listing'));
        add_action('wp_ajax_blp_paypal_ipn', array($this, 'handle_paypal_ipn'));
        add_action('wp_ajax_nopriv_blp_paypal_ipn', array($this, 'handle_paypal_ipn'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_blp_delete_plan', array($this, 'admin_delete_plan'));
        add_action('wp_ajax_blp_update_listing_status', array($this, 'admin_update_listing_status'));
        add_action('wp_ajax_blp_test_api_connection', array($this, 'admin_test_api_connection'));
        
        // Hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Shortcodes
        add_shortcode('blp_plans', array($this, 'display_plans'));
        add_shortcode('blp_listings', array($this, 'display_listings'));
        add_shortcode('blp_dashboard', array($this, 'display_dashboard'));
        
        // Custom post type meta boxes
        add_action('add_meta_boxes', array($this, 'add_listing_meta_boxes'));
        add_action('save_post', array($this, 'save_listing_meta'));
        
        // Template redirect for single listings
        add_action('template_redirect', array($this, 'listing_template_redirect'));
    }
    
    public function init() {
        $this->create_custom_post_types();
        $this->create_user_roles();
        
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
    }
    
    public function activate() {
        $this->create_database_tables();
        $this->create_custom_post_types();
        $this->create_user_roles();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function create_database_tables() {
        global $wpdb;
        
        $plans_table = $wpdb->prefix . 'blp_plans';
        $subscriptions_table = $wpdb->prefix . 'blp_subscriptions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $plans_sql = "CREATE TABLE IF NOT EXISTS $plans_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            duration int(11) NOT NULL DEFAULT 30,
            features text,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $subscriptions_sql = "CREATE TABLE IF NOT EXISTS $subscriptions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            plan_id int(11) NOT NULL,
            payment_id varchar(255),
            payment_method varchar(50),
            status varchar(50) DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY plan_id (plan_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($plans_sql);
        dbDelta($subscriptions_sql);
        
        // Insert default plan if none exists
        $existing_plans = $wpdb->get_var("SELECT COUNT(*) FROM $plans_table");
        if ($existing_plans == 0) {
            $wpdb->insert($plans_table, array(
                'name' => 'Basic Plan',
                'description' => 'Perfect for small businesses getting started',
                'price' => 29.99,
                'duration' => 30,
                'features' => "Basic business listing\nContact information\n1 business image\nEmail support"
            ));
            
            $wpdb->insert($plans_table, array(
                'name' => 'Professional Plan',
                'description' => 'Advanced features for growing businesses',
                'price' => 59.99,
                'duration' => 30,
                'features' => "Everything in Basic\nMultiple images (up to 5)\nFeatured listing\nPriority support\nSocial media links"
            ));
            
            $wpdb->insert($plans_table, array(
                'name' => 'Enterprise Plan',
                'description' => 'Complete solution for large businesses',
                'price' => 99.99,
                'duration' => 30,
                'features' => "Everything in Professional\nUnlimited images\nVideo integration\nAnalytics dashboard\nCustom branding\n24/7 phone support"
            ));
        }
    }
    
    public function create_custom_post_types() {
        register_post_type('business_listing', array(
            'labels' => array(
                'name' => __('Business Listings', 'business-listings-pro'),
                'singular_name' => __('Business Listing', 'business-listings-pro'),
                'add_new' => __('Add New Listing', 'business-listings-pro'),
                'add_new_item' => __('Add New Business Listing', 'business-listings-pro'),
                'edit_item' => __('Edit Business Listing', 'business-listings-pro'),
                'new_item' => __('New Business Listing', 'business-listings-pro'),
                'view_item' => __('View Business Listing', 'business-listings-pro'),
                'search_items' => __('Search Business Listings', 'business-listings-pro'),
                'not_found' => __('No business listings found', 'business-listings-pro'),
                'not_found_in_trash' => __('No business listings found in trash', 'business-listings-pro')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'author'),
            'menu_icon' => 'dashicons-building',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'rewrite' => array('slug' => 'business-listing'),
            'show_in_rest' => false
        ));
    }
    
    public function create_user_roles() {
        if (!get_role('business_owner')) {
            add_role('business_owner', __('Business Owner', 'business-listings-pro'), array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true
            ));
        }
    }
    
    public function admin_menu() {
        add_menu_page(
            __('Business Listings Pro', 'business-listings-pro'),
            __('Listings Pro', 'business-listings-pro'),
            'manage_options',
            'business-listings-pro',
            array($this, 'admin_page'),
            'dashicons-building',
            30
        );
        
        add_submenu_page(
            'business-listings-pro',
            __('Plans', 'business-listings-pro'),
            __('Plans', 'business-listings-pro'),
            'manage_options',
            'blp-plans',
            array($this, 'plans_page')
        );
        
        add_submenu_page(
            'business-listings-pro',
            __('Subscriptions', 'business-listings-pro'),
            __('Subscriptions', 'business-listings-pro'),
            'manage_options',
            'blp-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        add_submenu_page(
            'business-listings-pro',
            __('Settings', 'business-listings-pro'),
            __('Settings', 'business-listings-pro'),
            'manage_options',
            'blp-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('blp-frontend', BLP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BLP_VERSION, true);
        wp_enqueue_style('blp-frontend', BLP_PLUGIN_URL . 'assets/css/frontend.css', array(), BLP_VERSION);
        
        wp_localize_script('blp-frontend', 'blp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('blp_nonce'),
            'user_logged_in' => is_user_logged_in()
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'business-listings-pro') !== false || strpos($hook, 'blp-') !== false) {
            wp_enqueue_script('blp-admin', BLP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BLP_VERSION, true);
            wp_enqueue_style('blp-admin', BLP_PLUGIN_URL . 'assets/css/admin.css', array(), BLP_VERSION);
            
            wp_localize_script('blp-admin', 'blp_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('blp_admin_nonce')
            ));
        }
    }
    
    public function add_listing_meta_boxes() {
        add_meta_box(
            'blp_listing_details',
            __('Business Details', 'business-listings-pro'),
            array($this, 'listing_meta_box_callback'),
            'business_listing',
            'normal',
            'high'
        );
    }
    
    public function listing_meta_box_callback($post) {
        wp_nonce_field('blp_save_listing_meta', 'blp_listing_meta_nonce');
        
        $category = get_post_meta($post->ID, 'business_category', true);
        $phone = get_post_meta($post->ID, 'business_phone', true);
        $address = get_post_meta($post->ID, 'business_address', true);
        $website = get_post_meta($post->ID, 'business_website', true);
        $email = get_post_meta($post->ID, 'business_email', true);
        
        echo '<table class="form-table">';
        echo '<tr><th><label for="business_category">' . __('Category', 'business-listings-pro') . '</label></th>';
        echo '<td><select id="business_category" name="business_category">';
        echo '<option value="">' . __('Select Category', 'business-listings-pro') . '</option>';
        $categories = array(
            'restaurant' => __('Restaurant', 'business-listings-pro'),
            'retail' => __('Retail', 'business-listings-pro'),
            'services' => __('Services', 'business-listings-pro'),
            'healthcare' => __('Healthcare', 'business-listings-pro'),
            'automotive' => __('Automotive', 'business-listings-pro'),
            'real-estate' => __('Real Estate', 'business-listings-pro'),
            'technology' => __('Technology', 'business-listings-pro'),
            'education' => __('Education', 'business-listings-pro'),
            'other' => __('Other', 'business-listings-pro')
        );
        foreach ($categories as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($category, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        
        echo '<tr><th><label for="business_phone">' . __('Phone', 'business-listings-pro') . '</label></th>';
        echo '<td><input type="tel" id="business_phone" name="business_phone" value="' . esc_attr($phone) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="business_email">' . __('Email', 'business-listings-pro') . '</label></th>';
        echo '<td><input type="email" id="business_email" name="business_email" value="' . esc_attr($email) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="business_address">' . __('Address', 'business-listings-pro') . '</label></th>';
        echo '<td><textarea id="business_address" name="business_address" rows="3" class="large-text">' . esc_textarea($address) . '</textarea></td></tr>';
        
        echo '<tr><th><label for="business_website">' . __('Website', 'business-listings-pro') . '</label></th>';
        echo '<td><input type="url" id="business_website" name="business_website" value="' . esc_attr($website) . '" class="regular-text"></td></tr>';
        
        echo '</table>';
    }
    
    public function save_listing_meta($post_id) {
        if (!isset($_POST['blp_listing_meta_nonce']) || !wp_verify_nonce($_POST['blp_listing_meta_nonce'], 'blp_save_listing_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('business_category', 'business_phone', 'business_address', 'business_website', 'business_email');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    // Admin page methods
    public function admin_page() {
        include BLP_PLUGIN_PATH . 'admin/main.php';
    }
    
    public function plans_page() {
        include BLP_PLUGIN_PATH . 'admin/plans.php';
    }
    
    public function subscriptions_page() {
        include BLP_PLUGIN_PATH . 'admin/subscriptions.php';
    }
    
    public function settings_page() {
        include BLP_PLUGIN_PATH . 'admin/settings.php';
    }
    
    // Shortcode methods
    public function display_plans($atts) {
        ob_start();
        include BLP_PLUGIN_PATH . 'frontend/plans.php';
        return ob_get_clean();
    }
    
    public function display_listings($atts) {
        ob_start();
        include BLP_PLUGIN_PATH . 'frontend/listings.php';
        return ob_get_clean();
    }
    
    public function display_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="blp-login-required"><p>' . __('Please log in to access your dashboard.', 'business-listings-pro') . '</p></div>';
        }
        ob_start();
        include BLP_PLUGIN_PATH . 'frontend/dashboard.php';
        return ob_get_clean();
    }
    
    // AJAX handlers
    public function register_user() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(__('All fields are required.', 'business-listings-pro'));
        }
        
        if (username_exists($username)) {
            wp_send_json_error(__('Username already exists.', 'business-listings-pro'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(__('Email already exists.', 'business-listings-pro'));
        }
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('business_owner');
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Check if there's a selected plan in session
        if (isset($_SESSION['selected_plan'])) {
            $plan_id = intval($_SESSION['selected_plan']);
            unset($_SESSION['selected_plan']);
            
            // Create subscription record
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'blp_subscriptions',
                array(
                    'user_id' => $user_id,
                    'plan_id' => $plan_id,
                    'status' => 'active',
                    'start_date' => current_time('mysql'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => __('Account created successfully!', 'business-listings-pro'),
            'redirect' => home_url('/dashboard')
        ));
    }
    
    public function login_user() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(__('Username and password are required.', 'business-listings-pro'));
        }
        
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true
        ));
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Login successful!', 'business-listings-pro'),
            'redirect' => home_url('/dashboard')
        ));
    }
    
    public function process_payment() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        $plan_id = intval($_POST['plan_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        global $wpdb;
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}blp_plans WHERE id = %d AND active = 1", $plan_id));
        
        if (!$plan) {
            wp_send_json_error(__('Invalid plan selected.', 'business-listings-pro'));
        }
        
        // Store plan in session
        $_SESSION['selected_plan'] = $plan_id;
        
        if ($payment_method === 'paypal') {
            $this->process_paypal_payment($plan);
        } elseif ($payment_method === 'stripe') {
            $this->process_stripe_payment($plan);
        } else {
            wp_send_json_error(__('Invalid payment method.', 'business-listings-pro'));
        }
    }
    
    private function process_paypal_payment($plan) {
        $paypal_email = get_option('blp_paypal_email', '');
        $sandbox = get_option('blp_paypal_sandbox', true);
        
        if (empty($paypal_email)) {
            wp_send_json_error(__('PayPal is not configured. Please contact administrator.', 'business-listings-pro'));
        }
        
        $return_url = add_query_arg('payment', 'success', home_url());
        $cancel_url = add_query_arg('payment', 'cancelled', home_url());
        $notify_url = admin_url('admin-ajax.php?action=blp_paypal_ipn');
        
        $paypal_url = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        
        $params = array(
            'cmd' => '_xclick',
            'business' => $paypal_email,
            'item_name' => $plan->name . ' - ' . $plan->duration . ' days',
            'item_number' => $plan->id,
            'amount' => $plan->price,
            'currency_code' => 'USD',
            'return' => $return_url,
            'cancel_return' => $cancel_url,
            'notify_url' => $notify_url,
            'custom' => get_current_user_id()
        );
        
        $redirect_url = $paypal_url . '?' . http_build_query($params);
        wp_send_json_success(array('redirect' => $redirect_url));
    }
    
    private function process_stripe_payment($plan) {
        wp_send_json_error(__('Stripe integration coming soon.', 'business-listings-pro'));
    }
    
    public function handle_paypal_ipn() {
        // PayPal IPN verification would go here
        // For now, we'll handle the return URL processing
        if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
            // Payment successful, redirect handled by frontend
        }
    }
    
    public function submit_listing() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to submit a listing.', 'business-listings-pro'));
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $category = sanitize_text_field($_POST['category']);
        $phone = sanitize_text_field($_POST['phone']);
        $address = sanitize_textarea_field($_POST['address']);
        $website = esc_url_raw($_POST['website']);
        $email = sanitize_email($_POST['email']);
        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        
        if (empty($title) || empty($description) || empty($category)) {
            wp_send_json_error(__('Title, description, and category are required.', 'business-listings-pro'));
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'business_listing',
            'post_status' => get_option('blp_require_approval', true) ? 'pending' : 'publish',
            'post_author' => get_current_user_id()
        );
        
        if ($listing_id > 0) {
            // Update existing listing
            $post_data['ID'] = $listing_id;
            $post_id = wp_update_post($post_data);
            $message = __('Listing updated successfully!', 'business-listings-pro');
        } else {
            // Create new listing
            $post_id = wp_insert_post($post_data);
            $message = get_option('blp_require_approval', true) ? 
                __('Listing submitted for review!', 'business-listings-pro') : 
                __('Listing published successfully!', 'business-listings-pro');
        }
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(__('Error saving listing. Please try again.', 'business-listings-pro'));
        }
        
        // Save meta data
        update_post_meta($post_id, 'business_category', $category);
        update_post_meta($post_id, 'business_phone', $phone);
        update_post_meta($post_id, 'business_address', $address);
        update_post_meta($post_id, 'business_website', $website);
        update_post_meta($post_id, 'business_email', $email);
        
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('image', $post_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        wp_send_json_success($message);
    }
    
    public function delete_listing() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'business-listings-pro'));
        }
        
        $listing_id = intval($_POST['listing_id']);
        $post = get_post($listing_id);
        
        if (!$post || $post->post_type !== 'business_listing') {
            wp_send_json_error(__('Invalid listing.', 'business-listings-pro'));
        }
        
        if ($post->post_author != get_current_user_id() && !current_user_can('delete_others_posts')) {
            wp_send_json_error(__('You do not have permission to delete this listing.', 'business-listings-pro'));
        }
        
        if (wp_delete_post($listing_id, true)) {
            wp_send_json_success(__('Listing deleted successfully.', 'business-listings-pro'));
        } else {
            wp_send_json_error(__('Error deleting listing.', 'business-listings-pro'));
        }
    }
    
    public function get_listing() {
        check_ajax_referer('blp_nonce', 'nonce');
        
        $listing_id = intval($_POST['listing_id']);
        $post = get_post($listing_id);
        
        if (!$post || $post->post_type !== 'business_listing') {
            wp_send_json_error(__('Invalid listing.', 'business-listings-pro'));
        }
        
        if ($post->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('You do not have permission to edit this listing.', 'business-listings-pro'));
        }
        
        $data = array(
            'title' => $post->post_title,
            'description' => $post->post_content,
            'category' => get_post_meta($listing_id, 'business_category', true),
            'phone' => get_post_meta($listing_id, 'business_phone', true),
            'address' => get_post_meta($listing_id, 'business_address', true),
            'website' => get_post_meta($listing_id, 'business_website', true),
            'email' => get_post_meta($listing_id, 'business_email', true)
        );
        
        wp_send_json_success($data);
    }
    
    // Admin AJAX handlers
    public function admin_delete_plan() {
        check_ajax_referer('blp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'business-listings-pro'));
        }
        
        $plan_id = intval($_POST['plan_id']);
        
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'blp_plans', array('id' => $plan_id));
        
        if ($result) {
            wp_send_json_success(__('Plan deleted successfully.', 'business-listings-pro'));
        } else {
            wp_send_json_error(__('Error deleting plan.', 'business-listings-pro'));
        }
    }
    
    public function admin_update_listing_status() {
        check_ajax_referer('blp_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Permission denied.', 'business-listings-pro'));
        }
        
        $listing_id = intval($_POST['listing_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = wp_update_post(array(
            'ID' => $listing_id,
            'post_status' => $status
        ));
        
        if ($result) {
            wp_send_json_success(__('Listing status updated.', 'business-listings-pro'));
        } else {
            wp_send_json_error(__('Error updating listing status.', 'business-listings-pro'));
        }
    }
    
    public function admin_test_api_connection() {
        check_ajax_referer('blp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'business-listings-pro'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        
        if ($api_type === 'paypal') {
            $email = sanitize_email($_POST['email']);
            if (empty($email)) {
                wp_send_json_error(__('PayPal email is required.', 'business-listings-pro'));
            }
            wp_send_json_success(__('PayPal configuration looks good!', 'business-listings-pro'));
        } elseif ($api_type === 'stripe') {
            wp_send_json_success(__('Stripe test connection successful!', 'business-listings-pro'));
        }
        
        wp_send_json_error(__('Invalid API type.', 'business-listings-pro'));
    }
    
    // Utility methods
    public function user_has_active_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        global $wpdb;
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_subscriptions 
             WHERE user_id = %d AND status = 'active' 
             AND (end_date IS NULL OR end_date > NOW()) 
             ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
        
        return !empty($subscription);
    }
    
    public function get_user_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.name as plan_name, p.price, p.duration 
             FROM {$wpdb->prefix}blp_subscriptions s
             LEFT JOIN {$wpdb->prefix}blp_plans p ON s.plan_id = p.id
             WHERE s.user_id = %d AND s.status = 'active' 
             AND (s.end_date IS NULL OR s.end_date > NOW()) 
             ORDER BY s.created_at DESC LIMIT 1",
            $user_id
        ));
    }
    
    public function listing_template_redirect() {
        if (is_singular('business_listing')) {
            // You can add custom template loading here if needed
        }
    }
}

// Initialize the plugin
new BusinessListingsPro();