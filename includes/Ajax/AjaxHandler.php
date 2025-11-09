<?php

namespace CoderEmbassy\ExpressCheckout\Ajax;

/**
 * AJAX Handler class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class AjaxHandler {
    
    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function __construct() {
        add_action('wp_ajax_coderembassy_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_nopriv_coderembassy_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_coderembassy_search_products', array($this, 'search_products'));
        add_action('wp_ajax_coderembassy_get_checkout_form', array($this, 'get_checkout_form'));
        add_action('wp_ajax_nopriv_coderembassy_get_checkout_form', array($this, 'get_checkout_form'));
        
        // Handle form submission for non-AJAX mode
        add_action('wp_loaded', array($this, 'handle_form_submission'));
    }
    
    /**
     * Add to cart AJAX handler
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function add_to_cart() {
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'coderembassy_express_checkout_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'coderembassy-express-checkout')
            ));
        }

        $products_data = array();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is sanitized via sanitize_products_data() method below
        $raw_products_data = isset($_POST['products_data']) ? wp_unslash($_POST['products_data']) : null;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is sanitized via sanitize_products_data() method below
        $raw_product_ids = isset($_POST['product_ids']) ? wp_unslash($_POST['product_ids']) : null;
        
        if ($raw_products_data !== null) {
            $products_data = $this->sanitize_products_data($raw_products_data);
        } elseif ($raw_product_ids !== null) {
            $products_data = $this->sanitize_products_data($raw_product_ids);
        }
        
        if (empty($products_data)) {
            wp_send_json_error(array(
                'message' => esc_html__('No products selected.', 'coderembassy-express-checkout')
            ));
        }
        
        $added_products = array();
        $failed_products = array();
        
        foreach ($products_data as $product_data) {
            // Handle both old format (just product ID) and new format (product data object)
            if (is_array($product_data)) {
                $product_id = isset($product_data['product_id']) ? intval($product_data['product_id']) : 0;
                $variation_id = isset($product_data['variation_id']) ? $product_data['variation_id'] : null;
                $variation_attributes = isset($product_data['variation']) ? $product_data['variation'] : array();
            } else {
                // Backward compatibility - just product ID
                $product_id = intval($product_data);
                $variation_id = null;
                $variation_attributes = array();
            }
            
            if (!$product_id) {
                $failed_products[] = $product_id;
                continue;
            }
            
            $product = wc_get_product($product_id);
            
            if (!$product || !$product->is_purchasable()) {
                $failed_products[] = $product_id;
                continue;
            }
            
            // Handle variable products
            if ($product->is_type('variable')) {
                if (empty($variation_attributes)) {
                    $failed_products[] = $product_id;
                    continue;
                }
                
                // Find the matching variation
                $variation = $this->find_matching_variation($product, $variation_attributes);
                
                if (!$variation) {
                    $failed_products[] = $product_id;
                    continue;
                }
                
                $variation_id = $variation->get_id();
            }
            
            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation_attributes);
            
            if ($cart_item_key) {
                $added_products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'cart_key' => $cart_item_key,
                    'variation_id' => $variation_id
                );
            } else {
                $failed_products[] = $product_id;
            }
        }
        
        // Trigger cart updated action
        do_action('woocommerce_cart_updated');
        
        // Generate mini cart content for AJAX updates
        $cart_fragments = array();
        
        // Only generate mini cart content to avoid conflicts
        if (function_exists('woocommerce_mini_cart')) {
            ob_start();
            woocommerce_mini_cart();
            $mini_cart_content = ob_get_clean();
            if (!empty($mini_cart_content)) {
                $cart_fragments['div.widget_shopping_cart_content'] = $mini_cart_content;
            }
        }
        
        $response_data = array(
            'added_products' => $added_products,
            'failed_products' => $failed_products,
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_fragments' => $cart_fragments,
            'message' => sprintf(
                // translators: %d is the number of products added to cart
                _n(
                    '%d product added to cart.',
                    '%d products added to cart.',
                    count($added_products),
                    'coderembassy-express-checkout'
                ),
                count($added_products)
            )
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Search products AJAX handler
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function search_products() {
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to perform this action.', 'coderembassy-express-checkout')
            ));
        }

        // Get nonce from POST or GET (Select2 sends as GET)
        $nonce = '';
        if (isset($_REQUEST['nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce']));
        }
        
        if (empty($nonce) || !wp_verify_nonce($nonce, 'coderembassy_admin_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'coderembassy-express-checkout')
            ));
        }
        
        // Get search term from POST or GET (Select2 sends as GET)
        $search_term = '';
        if (isset($_REQUEST['search'])) {
            $search_term = sanitize_text_field(wp_unslash($_REQUEST['search']));
        }
        
        $page = 1;
        if (isset($_REQUEST['page'])) {
            $page = intval(wp_unslash($_REQUEST['page']));
        }
        
        $per_page = 20;
        
        // Only search if term is at least 3 characters (matching minimumInputLength in Select2)
        if (empty($search_term) || strlen(trim($search_term)) < 3) {
            // If no search term or less than 3 characters, return empty results
            wp_send_json_success(array(
                'results' => array(),
                'pagination' => array(
                    'more' => false
                )
            ));
        }
        
        // Use WP_Query for better search functionality
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
            's' => trim($search_term)
        );
        
        $query = new \WP_Query($query_args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);
                
                // Double check it's a product and not a variation
                if ($product && !$product->is_type('variation') && $product->is_purchasable()) {
                    $results[] = array(
                        'id' => $product_id,
                        'text' => $product->get_name()
                    );
                }
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'pagination' => array(
                'more' => $query->max_num_pages > $page
            )
        ));
    }
    
    /**
     * Fallback error handler for AJAX requests
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function handle_ajax_error() {
        wp_send_json_error(array(
            'message' => esc_html__('An error occurred while processing your request.', 'coderembassy-express-checkout')
        ));
    }
    
    /**
     * Handle form submission for non-AJAX mode
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function handle_form_submission() {
        // Check if this is our form submission
        if (!isset($_POST['action']) || sanitize_text_field(wp_unslash($_POST['action'])) !== 'coderembassy_add_to_cart_form') {
            return;
        }
        
        // Verify nonce
        $nonce = isset($_POST['coderembassy_express_checkout_nonce']) ? sanitize_text_field(wp_unslash($_POST['coderembassy_express_checkout_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'coderembassy_express_checkout_nonce')) {
            wp_die(esc_html__('Security check failed.', 'coderembassy-express-checkout'));
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', wp_unslash($_POST['product_ids'])) : array();
        
        // Ensure WooCommerce cart is properly initialized
        if (!WC()->cart) {
            WC()->cart = new \WC_Cart();
        }
        
        // Initialize cart session to ensure it's ready for operations
        WC()->cart->get_cart_contents_count();
        
        if (empty($product_ids)) {
            wp_die(esc_html__('No products selected.', 'coderembassy-express-checkout'));
        }
        
        $added_products = array();
        $failed_products = array();
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            
            if (!$product || !$product->is_purchasable()) {
                $failed_products[] = $product_id;
                continue;
            }
            
            // Check if this product has variation data
            $variation_attributes = array();
            if (isset($_POST['variation'][$product_id])) {
                $variation_attributes = array_map('sanitize_text_field', wp_unslash($_POST['variation'][$product_id]));
            }
            
            // Handle variable products
            if ($product->is_type('variable') && !empty($variation_attributes)) {
                // Find the matching variation
                $variation = $this->find_matching_variation($product, $variation_attributes);
                
                if (!$variation) {
                    $failed_products[] = $product_id;
                    continue;
                }
                
                $variation_id = $variation->get_id();
                $cart_item_key = WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation_attributes);
            } else {
                // Simple product or variable product without variations
                $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
            }
            
            if ($cart_item_key) {
                $added_products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'cart_key' => $cart_item_key
                );
            } else {
                $failed_products[] = $product_id;
            }
        }
        
        // Trigger cart updated action
        do_action('woocommerce_cart_updated');
        
        // Set success message
        if (!empty($added_products)) {
            $message = sprintf(
                // translators: %d is the number of products added to cart
                _n(
                    '%d product added to cart.',
                    '%d products added to cart.',
                    count($added_products),
                    'coderembassy-express-checkout'
                ),
                count($added_products)
            );
            
            // Store success message in session for display after redirect
            if (!session_id()) {
                session_start();
            }
            $_SESSION['coderembassy_success_message'] = $message;
        }
        
        // Redirect back to the same page to prevent form resubmission
        $redirect_url = remove_query_arg('coderembassy_message');
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get checkout form AJAX handler
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function get_checkout_form() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'coderembassy_express_checkout_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'coderembassy-express-checkout')
            ));
        }
        
        // Check if WooCommerce is active and cart has items
        if (!function_exists('WC') || !WC() || !WC()->cart || WC()->cart->is_empty()) {
            wp_send_json_error(array(
                'message' => esc_html__('Cart is empty.', 'coderembassy-express-checkout')
            ));
        }
        
        // Generate checkout form
        ob_start();
        
        // Render WooCommerce checkout form
        if (function_exists('woocommerce_checkout_form')) {
            woocommerce_checkout_form();
        } else {
            // Fallback: use WooCommerce checkout shortcode
            echo do_shortcode('[woocommerce_checkout]');
        }
        
        $checkout_form = ob_get_clean();
        
        wp_send_json_success(array(
            'checkout_form' => $checkout_form
        ));
    }
    
    /**
     * Find matching variation for a variable product
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function find_matching_variation($product, $variation_attributes) {
        if (!$product->is_type('variable')) {
            return false;
        }
        
        $variations = $product->get_available_variations();
        
        foreach ($variations as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);
            
            if (!$variation) {
                continue;
            }
            
            $variation_attributes_data = $variation->get_variation_attributes();
            $match = true;
            
            foreach ($variation_attributes as $attribute_name => $attribute_value) {
                $attribute_slug = 'attribute_' . sanitize_title($attribute_name);
                
                if (!isset($variation_attributes_data[$attribute_slug]) || 
                    $variation_attributes_data[$attribute_slug] !== $attribute_value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                return $variation;
            }
        }
        
        return false;
    }

    /**
     * Recursively sanitize array data
     * @since 1.0.0
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitize_products_data($data) {
        if (is_array($data)) {
            return array_map(array($this, 'sanitize_products_data'), $data);
        }
        return sanitize_text_field($data);
    }
}


