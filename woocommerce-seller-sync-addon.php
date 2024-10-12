<?php
/**
 * Plugin Name: WooCommerce Seller Sync Addon
 * Plugin URI: https://sohagsrz.me
 * Description: A custom addon for syncing features with marketplace.
 * Version: 1.0
 * Author: Sohag Srz
 * Author URI: https://facebook.com/sohagsrz
 * License: GPLv2 or later
 * 
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants
define('WSS_VERSION', '1.0.0');
define('WSS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WSS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WSS_PLUGIN_PATH . 'includes/class-api-handlers.php';
require_once WSS_PLUGIN_PATH . 'admin/class-admin-settings.php';

/**
 * Initialize the plugin
 */
class WSS_WooCommerce_Seller_Sync_Addon {

    public function __construct() {
        // Hook into WooCommerce product update action
        add_action('woocommerce_update_product', array($this, 'trigger_url_on_product_update'), 10, 1);
        add_action('woocommerce_delete_product', array($this, 'trigger_url_on_product_update'), 10, 1);
        //trashed_post
        add_action('trashed_post', array($this, 'trigger_url_on_product_update'), 10, 1);
        // to trash
        add_action('before_delete_post', array($this, 'trigger_url_on_product_update'), 10, 1);
        
        add_action('save_post', array($this, 'trigger_url_on_product_update'), 10, 1);

        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
 
        // Enqueue styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register custom REST API routes
     */
    public function register_rest_api() {
        // API endpoints registered in class-api-handlers.php
    }

    /**
     * Trigger URL when a product is updated
     */
    public function trigger_url_on_product_update($product_id) {
        if(get_post_type($product_id) !== 'product') {
            return;
        }
        //check api and webhooks
        if (!get_option('wss_marketplace_api_url', false) || !get_option('wss_marketplace_api', false)
        || empty(get_option('wss_marketplace_api_url')) || empty(get_option('wss_marketplace_api'))

        ) {
            return;
        }
        
        $product = wc_get_product($product_id);
        $product_data = WSS_Api_Handlers::prepare_product_data($product);
         

        $trigger_url = get_option('wss_marketplace_api_url').'/wp-json/dokan-sync/v1/webhook';
        $trigger_url = add_query_arg('api_key', get_option('wss_marketplace_api'), $trigger_url);
        
        if ($trigger_url) {
            wp_remote_post($trigger_url, array(
                'method'    => 'POST',
                'body'      => json_encode($product_data),
                'headers'   => array(
                    'Content-Type' => 'application/json',
                ),
            ));
        } 
    }

    /**
     * Register admin menu for settings
     */
    public function register_admin_menu() {
        add_menu_page(
            'Seller Sync Settings',
            'Seller Sync',
            'manage_options',
            'seller-sync-settings',
            array('WSS_Admin_Settings', 'output_settings'),
            'dashicons-admin-tools',
            60
        );
        // orders
        add_submenu_page(
            'seller-sync-settings',
            'Orders',
            'Orders',
            'manage_options',
            'seller-sync-orders',
            array('WSS_Admin_Settings', 'output_orders')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        wp_enqueue_style('wss-admin-css', WSS_PLUGIN_URL . 'assets/css/style.css', array(), WSS_VERSION);
    }
}

// Initialize the plugin
new WSS_WooCommerce_Seller_Sync_Addon();
