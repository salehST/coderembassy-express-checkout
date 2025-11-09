<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Main plugin class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Plugin {
    
    /**
     * Plugin instance
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private static $instance = null;
    
    /**
     * Plugin version
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public $version = CODEREMBASSY_EXPRESS_CHECKOUT_VERSION;
    
    /**
     * Get plugin instance
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize hooks
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    
    /**
     * Initialize components
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function init_components() {
        new CePostType();
        new Admin\Admin();
        new Frontend\Shortcode();
        new Ajax\AjaxHandler();
    }
    
    
    /**
     * Initialize plugin
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function init() {
        // Text domain is automatically loaded by WordPress for plugins hosted on WordPress.org
    }
    
    /**
     * Enqueue frontend scripts and styles
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function enqueue_scripts() {
        // Only enqueue if we have shortcodes on the page
        global $post;
        if (!$post || (!has_shortcode($post->post_content, 'ce_checkout') && !has_shortcode($post->post_content, 'coderembassy_express_checkout'))) {
            return;
        }
        
        wp_enqueue_script(
            'coderembassy-express-checkout-frontend',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wc-add-to-cart'),
            $this->version,
            true
        );
        
        wp_enqueue_style(
            'coderembassy-express-checkout-frontend',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );
        
        // Localize script
        wp_localize_script('coderembassy-express-checkout-frontend', 'coderembassyData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coderembassy_express_checkout_nonce'),
            'i18n' => array(
                'addSelectedToCart' => esc_html__('Add Selected to Cart', 'coderembassy-express-checkout'),
                'noProductsSelected' => esc_html__('Please select at least one product.', 'coderembassy-express-checkout'),
                'addingToCart' => esc_html__('Adding to cart...', 'coderembassy-express-checkout'),
                'addedToCart' => esc_html__('Added to cart!', 'coderembassy-express-checkout'),
                'error' => esc_html__('An error occurred. Please try again.', 'coderembassy-express-checkout'),
            )
        ));
        
        // Add inline script to handle success messages from data attributes
        $inline_script = "
            jQuery(document).ready(function($) {
                $('.coderembassy-express-checkout').each(function() {
                    var \$container = $(this);
                    var successMessage = \$container.data('success-message');
                    if (successMessage && typeof showSuccessNotification === 'function') {
                        showSuccessNotification(successMessage);
                    }
                });
            });
        ";
        wp_add_inline_script('coderembassy-express-checkout-frontend', $inline_script);
    }
    
    /**
     * Enqueue admin scripts and styles
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        // Only load on our plugin pages
        $current_screen = get_current_screen();
        $should_load = false;
        
        // Check if we're on our post type pages
        if ($post_type === 'ce_shortcode') {
            $should_load = true;
        }
        
        // Check if we're on our configuration page
        if (isset($current_screen->id) && strpos($current_screen->id, 'coderembassy') !== false) {
            $should_load = true;
        }
        
        // Check if we're on our submenu pages
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking current admin page, not processing form data
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (strpos($page, 'coderembassy') !== false) {
            $should_load = true;
        }
        
        if (!$should_load) {
            return;
        }
        
        // Enqueue Select2 - ensure CSS is loaded even if WooCommerce only loads JS
        wp_enqueue_script('select2');
        
        // Force enqueue Select2 CSS - try multiple sources
        if (!wp_style_is('select2', 'enqueued') && !wp_style_is('select2', 'done')) {
            // Try WordPress core first
            if (file_exists(ABSPATH . WPINC . '/css/select2.min.css')) {
                wp_enqueue_style('select2', includes_url('css/select2.min.css'), array(), '4.0.3');
            } else {
                wp_enqueue_style(
                    'select2',
                    plugin_dir_url(dirname(__FILE__)) . 'assets/css/select2.min.css',
                    array(),
                    '4.0.13'
                );
            }
        }
        
        // Enqueue WordPress Color Picker
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        
        // Enqueue our admin script
        wp_enqueue_script(
            'coderembassy-express-checkout-admin',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'select2', 'wp-color-picker'),
            $this->version,
            true
        );
        
        // Enqueue our admin styles
        wp_enqueue_style(
            'coderembassy-express-checkout-admin',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            $this->version
        );
        
        // Localize admin script
        wp_localize_script('coderembassy-express-checkout-admin', 'coderembassyAdminData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => array(
                'searchProducts' => esc_html__('Type at least 3 characters to search products...', 'coderembassy-express-checkout'),
                'noProductsFound' => esc_html__('No products found', 'coderembassy-express-checkout'),
            )
        ));
    }
}
