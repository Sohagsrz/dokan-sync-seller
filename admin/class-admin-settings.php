<?php

class WSS_Admin_Settings {

    /**
     * Output the settings page
     */
    public static function output_settings() {
        // Save settings
        if (isset($_POST['wss_save_settings'])) {
            update_option('wss_marketplace_api_url', sanitize_text_field($_POST['wss_marketplace_api_url']));
            update_option('wss_marketplace_api', sanitize_text_field($_POST['wss_marketplace_api']));
            echo '<div class="updated"><p>Settings saved.</p></div>';

        }

        // Get current settings
        $api_url = get_option('wss_marketplace_api_url', '');

        ?>
        <div class="wrap wds_sync-settings">
            <h1>Seller Sync Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wss_marketplace_api_url">Marketplace API URL</label></th>
                        <td>
                            <input type="text" id="wss_marketplace_api_url" name="wss_marketplace_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" />
                            <p class="description">Enter the API URL where product data will be sent when updated.</p>
                        </td>
                    </tr>
                    <!-- api key -->
                    <tr>
                        <th><label for="site_own_api"><?php _e( 'Your API Key', 'dokan
-addon-sync' ); ?></label></th>
<td>
                            <input type="text" id="wss_marketplace_api" name="wss_marketplace_api" value="<?php echo esc_attr(get_option('wss_marketplace_api', '')); ?>" class="regular-text" />
                            <p class="description">Enter the API key.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wss_save_settings" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }
    // output_orders
    public static function output_orders() {
        // Get orders
        $orders =  self::get_orders();
        if(isset($orders['message']))   {
            echo '<div class="notice notice-error"><p>' . $orders['message'] . '</p></div>';
            $orders = [];
        } 
        $orders= is_array($orders) ? $orders : [];
         

        ?>
        <div class="wrap wds_sync-orders">
            <h1>Orders</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) :  
                        ?>
                        <tr>
                            <td>Order #<?php echo $order['id']; ?></td>
                            <td>
                                <!-- ago time -->
                                 <b><?php echo human_time_diff(strtotime($order['date_created']['date']), current_time('timestamp')) . ' ago'; ?>
                               </b> <br/>
                               <?php echo $order['date_created']['date']; ?></td>
                            <td>
                                <!-- currency -->
                                <?php echo $order['currency']; ?>
                                <?php echo $order['total']; ?></td>
                            <td><?php echo $order['billing']['first_name']; ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td>
                                <a href="<?php echo get_option('wss_marketplace_api_url') . '/dashboard/orders/?idview=' . $order['id']; ?>" class="button button-primary" target="_blank">View</a>
                            </td>

                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- pagination -->
             <!-- <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo count($orders); ?> items</span>
                    <span class="pagination links"><a class="prev-page" href="#">Prev </a><span class="paging-input"><span class="tablenav-paging-text">1-10 of 10</span></span><a class="next-page" href="#">»</a></span>
                    </div>
                </div> -->

        </div>
        <?php
    }
    // get orders
    public static function get_orders() {
        // Get orders
        $apiKey = get_option('wss_marketplace_api', '');
        $apiUrl = get_option('wss_marketplace_api_url', '');
        $response = wp_remote_get($apiUrl . '/wp-json/dokan-sync/v1/orders?api_key=' . $apiKey);
        $orders = json_decode(wp_remote_retrieve_body($response),1);
        if (is_wp_error($response) || !is_array($orders)) {
            return [];
        }
        return $orders;

    }

}
