<?php

class WSS_Api_Handlers {

    //permission check method
    public static function permission_check() {
           // WC not enabled, target reflection class is probably not registered
           if ( ! function_exists( 'WC' ) ) {
            return false;
        }

        $method = new ReflectionMethod( 'WC_REST_Authentication', 'perform_basic_authentication' );
        $method->setAccessible( true );

        return $method->invoke( new WC_REST_Authentication ) !== false;
        
    }
    
    /**
     * Register custom API routes
     */
    public static function register_routes() {
        register_rest_route('wss/v1', '/products/count', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_product_count'),
            // wc rest api permission
            'permission_callback' =>  [__CLASS__, 'permission_check']
        ));
        // get products list
        register_rest_route('wss/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_products'),
            'permission_callback' =>  [__CLASS__, 'permission_check']
        ));

        register_rest_route('wss/v1', '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_product_by_id'),
            'permission_callback' =>  [__CLASS__, 'permission_check']
        ));

        register_rest_route('wss/v1', '/products/category/(?P<slug>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_products_by_category'),
            'permission_callback' =>  [__CLASS__, 'permission_check']
        ));
    }
    //get_products
    public static function get_products($data) {
        $per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 10;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $products = wc_get_products(array(
            'limit' => $per_page,
            'page' => $page,
        ));

        $products_data = array();
        foreach ($products as $product) {
            $products_data[] = self::prepare_product_data($product);
        }

        return new WP_REST_Response($products_data, 200);
    }

    /**
     * Get total product count
     */
    public static function get_product_count() {
        $per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 10;
        $product_count =   wp_count_posts('product')->publish;
        $total_pages = ceil($product_count / $per_page);
        // estimated time to complete
        $time = $total_pages * 5;

        return new WP_REST_Response(
            array(
                'total_products' => $product_count,
                'total_pages' => $total_pages,
                'estimated_time' => $time,
            ),
            200
        );
    }

    /**
     * Get product details by ID
     */
    public static function get_product_by_id($data) {
        $product_id = $data['id'];
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('no_product', 'Invalid product ID', array('status' => 404));
        }

        return new WP_REST_Response(self::prepare_product_data($product), 200);
    }

    /**
     * Get products by category slug
     */
    public static function get_products_by_category($data) {
        $category_slug = $data['slug'];

        $products = wc_get_products(array(
            'category' => array($category_slug),
            'limit' => -1,
        ));

        $products_data = array();
        foreach ($products as $product) {
            $products_data[] = self::prepare_product_data($product);
        }

        return new WP_REST_Response($products_data, 200);
    }

    /**
     * Prepare product data for API response
     */
    public static function prepare_product_data($product) {
        $data = array(
            'id' => $product->get_id(),
            'type' => $product->get_type(),

            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'display_image' => $product->get_image_id(),
            'display_image_url' => wp_get_attachment_url($product->get_image_id()),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'status' => $product->get_status(),
            'permalink' => $product->get_permalink(),
            'date_created' => $product->get_date_created()->getTimestamp(),
            'date_modified' => $product->get_date_modified()->getTimestamp(),

            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(), 
            'stock_quantity' => $product->get_stock_quantity(),
            'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
            'images' => $product->get_gallery_image_ids(),
            'attributes' => $product->get_attributes(), 
            // 'meta_data' => $product->get_meta_data(),
            'all' => $product->get_data(),
        );
        // all attrbute title
        $data['attributes'] = array_map(function($attr) {
            // taxonomy product_attributes obj
            $attr->taxonomy = get_taxonomy($attr->get_taxonomy())->labels->singular_name;
            $attr->data_attr = $attr->get_data();
            $attr->options = array_map(function($term) {
                return $term->name;
            }, get_terms($attr->get_taxonomy(), array('hide_empty' => false)));


            return $attr;
        }, $data['attributes']);    
        // attribute title and value
         
        $imges_ids_urls =[];
        foreach ($data['images'] as $img_id) {
            $imges_ids_urls[$img_id] = wp_get_attachment_url($img_id);
        }
        $data['images_data'] = $imges_ids_urls;
        if ($product->get_type() === 'variable') {
            $variations = $product->get_children();
            $data['variations'] = array();

            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $data['variations'][] = array(
                    'id' => $variation_id,
                    'price' => $variation->get_price(),
                    'sku' => $variation->get_sku(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'attributes' => $variation->get_attributes(),
                    'image_id' => $variation->get_image_id(),
                    'img_url' => wp_get_attachment_url($variation->get_image_id()),
                    'all' => $variation->get_data(),
                );
            }
        }

        return $data;
    }
}

// Register routes when REST API is initialized
add_action('rest_api_init', array('WSS_Api_Handlers', 'register_routes'));
