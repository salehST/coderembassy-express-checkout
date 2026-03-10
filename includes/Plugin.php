<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Main plugin class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Plugin
{

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
    public static function get_instance()
    {
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
    private function __construct()
    {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize hooks
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function init_hooks()
    {
        add_action('init', array($this, 'init'));
        
        // TRICK THE THEME AND GATEWAYS (but safely after template load):
        // We pretend the page is a checkout page starting from wp_enqueue_scripts
        // Treat shortcode page as checkout for the entire request so themes and WooCommerce
        // load their checkout/compatibility CSS and JS (is_checkout() will be true).
        add_action('wp', array($this, 'maybe_force_checkout_context'), 1);

        // Trick gateways during AJAX checkout requests (like update_order_review) so they return payment fields
        add_filter('woocommerce_is_checkout', array($this, 'is_checkout_override_for_ajax'));

        // PayPal (WooCommerce PayPal Payments): ensure scripts load on our shortcode page
        add_filter('woocommerce_paypal_payments_should_enqueue_scripts', array($this, 'paypal_should_enqueue_scripts'));

        // PayPal button placement: show inline with PayPal option (like default checkout) instead of at end
        add_filter('woocommerce_paypal_payments_checkout_button_renderer_hook', array($this, 'paypal_checkout_button_renderer_hook'));

        // Stripe card form: hide Country field in Payment Element (billing comes from checkout form)
        add_filter('wc_stripe_localize_script_credit-card', array($this, 'stripe_cc_hide_billing_address_in_element'), 10, 2);

        // Add checkout body classes to ensure themes apply layout styles correctly
        add_filter('body_class', array($this, 'add_checkout_body_class'));
        
        // Load our assets as late as possible so theme styles are already present
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // ── Woodmart Theme Compatibility ──
        // Woodmart aggressively hijacks checkout via:
        //   1) Custom Layout Builder (class-checkout.php → has_custom_layout)
        //   2) Template override (woocommerce/checkout/form-checkout.php)
        //   3) Extra wrapper divs on woocommerce_checkout_order_review hook
        // We must counteract all three on OUR shortcode pages.
        add_filter('woodmart_layout_id', array($this, 'disable_woodmart_checkout_layout_id'));
        add_action('wp', array($this, 'remove_woodmart_checkout_hooks'));
        add_filter('wc_get_template', array($this, 'force_core_checkout_template'), 9999, 5);

        // Wrap "Your order" heading + order review in one div so the grid has a single column-2 cell
        add_action('woocommerce_checkout_before_order_review_heading', array($this, 'wrap_order_review_open'), 1);
        add_action('woocommerce_checkout_after_order_review', array($this, 'wrap_order_review_close'), 999);

        // Output notices again after payment section on our pages so gateway-added notices appear
        add_action('woocommerce_review_order_after_payment', array($this, 'output_payment_notices'), 5);

        // Fire custom action so gateway plugins (PayPal, etc.) can enqueue scripts on our page
        add_action('wp_enqueue_scripts', array($this, 'fire_gateway_script_hook'), 99999);
    }

    /**
     * Fire action for gateway plugins to enqueue checkout scripts on our shortcode page.
     * Plugins can hook: add_action('coderembassy_express_checkout_enqueue_gateway_scripts', ...)
     */
    public function fire_gateway_script_hook() {
        if ($this->is_our_checkout_page()) {
            do_action('coderembassy_express_checkout_enqueue_gateway_scripts');
        }
    }

    /**
     * Output WooCommerce notices after the payment section.
     * WooCommerce outputs notices at woocommerce_before_checkout_form (before payment).
     * Payment gateways add notices during payment_fields, so those are added later.
     * This ensures notices from gateways (e.g. Stripe config errors) show in the payment section.
     */
    public function output_payment_notices() {
        if ($this->is_our_checkout_page() && function_exists('woocommerce_output_all_notices')) {
            woocommerce_output_all_notices();
        }
    }

    /**
     * Open wrapper around order review heading + order_review (only on our shortcode page).
     */
    public function wrap_order_review_open() {
        if (!$this->is_our_checkout_page()) {
            return;
        }
        echo '<div id="ceec-order-summary-wrapper" class="ceec-order-summary">';
    }

    /**
     * Close wrapper after order_review (only on our shortcode page).
     */
    public function wrap_order_review_close() {
        if (!$this->is_our_checkout_page()) {
            return;
        }
        echo '</div>';
    }

    /**
     * Check if the current page/post contains our shortcode.
     * Works with classic content and Elementor (shortcode in Shortcode widget).
     *
     * @return bool
     */
    private function is_our_checkout_page() {
        global $post;
        if (!$post) {
            return false;
        }
        $shortcode_names = array('coderembassy_checkout', 'ceec_checkout', 'ce_checkout', 'coderembassy_express_checkout');
        if (!empty($post->post_content)) {
            foreach ($shortcode_names as $tag) {
                if (has_shortcode($post->post_content, $tag)) {
                    return true;
                }
            }
        }
        // Elementor: shortcode may be in a Shortcode widget stored in _elementor_data (JSON)
        $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
        if (is_string($elementor_data) && $elementor_data !== '') {
            foreach ($shortcode_names as $tag) {
                if (strpos($elementor_data, $tag) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 1) Nullify Woodmart's custom checkout layout ID so has_custom_layout() returns false.
     *    The filter `woodmart_layout_id` is applied inside Main::get_layout_id().
     */
    public function disable_woodmart_checkout_layout_id($layout_id) {
        if ($this->is_our_checkout_page()) {
            return false;
        }
        return $layout_id;
    }

    /**
     * 2) Remove Woodmart-specific actions that inject extra wrapper divs
     *    around the checkout order review table.
     */
    public function remove_woodmart_checkout_hooks() {
        if (!$this->is_our_checkout_page()) {
            return;
        }
        // Woodmart adds wrapper divs around the order review table
        remove_action('woocommerce_checkout_order_review', 'woodmart_open_table_wrapper_div', 7);
        remove_action('woocommerce_checkout_order_review', 'woodmart_close_table_wrapper_div', 13);
    }

    /**
     * 3) Force WooCommerce's core form-checkout.php instead of Woodmart's template override.
     *    Woodmart's version uses different CSS classes (customer-details vs col2-set)
     *    which breaks our custom layout.
     */
    public function force_core_checkout_template($template, $template_name, $args, $template_path, $default_path) {
        if ($template_name !== 'checkout/form-checkout.php') {
            return $template;
        }
        if (!$this->is_our_checkout_page()) {
            return $template;
        }
        // Point to WooCommerce's own plugin template, bypassing the theme override
        $wc_plugin_path = WC()->plugin_path() . '/templates/';
        $core_template  = $wc_plugin_path . $template_name;
        if (file_exists($core_template)) {
            return $core_template;
        }
        return $template;
    }

    /**
     * When the page contains our shortcode, make is_checkout() return true for the entire request.
     * This makes the shortcode page behave as an actual checkout page so themes and WooCommerce
     * enqueue their checkout/compatibility CSS and JS (e.g. Astra woocommerce-grid.min.css, WoodMart styles).
     */
    public function maybe_force_checkout_context() {
        if ($this->is_our_checkout_page()) {
            add_filter('woocommerce_is_checkout', '__return_true');
        }
    }

    /**
     * WooCommerce PayPal Payments: ensure PayPal/Google Pay scripts load on our shortcode page.
     * The plugin may check is_checkout() or use this filter to decide whether to enqueue.
     */
    public function paypal_should_enqueue_scripts($should_enqueue) {
        if ($this->is_our_checkout_page()) {
            return true;
        }
        return $should_enqueue;
    }

    /**
     * PayPal checkout button: render inside PayPal gateway area (like default checkout)
     * instead of at the end of the payment section.
     */
    public function paypal_checkout_button_renderer_hook($hook) {
        if ($this->is_our_checkout_page()) {
            return 'ppcp_start_button_wrapper_ppcp_gateway';
        }
        return $hook;
    }

    /**
     * Stripe card form: hide Country/billing address in Payment Element on express checkout.
     * Billing details come from the checkout form instead.
     *
     * @param array  $data         Localized script data.
     * @param string $object_name  Script object name.
     * @return array
     */
    public function stripe_cc_hide_billing_address_in_element($data, $object_name) {
        if (!$this->is_our_checkout_page()) {
            return $data;
        }
        $opts = isset($data['paymentElementOptions']) ? $data['paymentElementOptions'] : array();
        if (!is_array($opts)) {
            $opts = array();
        }
        $opts['fields'] = isset($opts['fields']) ? $opts['fields'] : array();
        if (!is_array($opts['fields'])) {
            $opts['fields'] = array();
        }
        $opts['fields']['billingDetails'] = isset($opts['fields']['billingDetails'])
            ? (array) $opts['fields']['billingDetails']
            : array();
        $opts['fields']['billingDetails']['address'] = 'never';
        $data['paymentElementOptions'] = $opts;
        return $data;
    }

    /**
     * Override is_checkout during AJAX requests so payment gateways render correctly
     */
    public function is_checkout_override_for_ajax($is_checkout) {
        if ($is_checkout) {
            return $is_checkout;
        }

        if (wp_doing_ajax() && isset($_SERVER['HTTP_REFERER'])) {
            $referer = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
            $post_id = url_to_postid($referer);
            if ($post_id) {
                $post = get_post($post_id);
                if ($post && (has_shortcode($post->post_content, 'coderembassy_checkout') || has_shortcode($post->post_content, 'ceec_checkout') || has_shortcode($post->post_content, 'ce_checkout') || has_shortcode($post->post_content, 'coderembassy_express_checkout'))) {
                    return true;
                }
            }
        }
        
        return $is_checkout;
    }
    
    /**
     * Explicitly enqueue woo-stripe-payment checkout scripts and styles.
     * Ensures Stripe Elements (card fields) load on our shortcode page when
     * the normal payment_fields path might not run early enough.
     */
    private function enqueue_woo_stripe_checkout_scripts() {
        if (!function_exists('stripe_wc') || !WC()->payment_gateways()) {
            return;
        }
        $scripts = stripe_wc()->scripts();
        if (!$scripts) {
            return;
        }
        foreach (WC()->payment_gateways()->payment_gateways() as $gateway) {
            if ($gateway instanceof \WC_Payment_Gateway_Stripe && $gateway->is_available()) {
                $gateway->enqueue_frontend_scripts('checkout');
            }
        }
    }

    /**
     * Add woocommerce-checkout and optional compatibility body classes
     */
    public function add_checkout_body_class($classes) {
        if (!$this->is_our_checkout_page()) {
            return $classes;
        }
        if (!in_array('woocommerce-checkout', $classes)) {
            $classes[] = 'woocommerce-checkout';
        }
        if (!in_array('woocommerce-page', $classes)) {
            $classes[] = 'woocommerce-page';
        }
        if (!in_array('ceec-express-page', $classes)) {
            $classes[] = 'ceec-express-page';
        }
        // When "Pro theme compatibility CSS" is enabled, add class so frontend.css applies aggressive overrides
        $options = get_option('coderembassy_global_options', array());
        if (!empty($options['pro_theme_compatibility_css']) && !in_array('ceec-compat-css', $classes)) {
            $classes[] = 'ceec-compat-css';
        }
        return $classes;
    }


    /**
     * Initialize components
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    private function init_components()
    {
        new CePostType();
        new Admin\Admin();
        new Frontend\Shortcode();
        new Ajax\AjaxHandler();

        // Allow pro version to hook into plugin initialization
        do_action('coderembassy_express_checkout_init');
    }


    /**
     * Initialize plugin
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function init()
    {
        // Text domain is automatically loaded by WordPress for plugins hosted on WordPress.org
    }

    /**
     * Enqueue frontend scripts and styles
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function enqueue_scripts()
    {
        // Only enqueue if we have shortcodes on the page
        global $post;

        $is_checkout = false;
        if (function_exists('is_checkout') && is_checkout()) {
            $is_checkout = true;
        }
        if (function_exists('is_cart') && is_cart()) {
            $is_checkout = true;
        }

        $has_shortcode = $this->is_our_checkout_page();

        if (!$is_checkout && !$has_shortcode) {
            return;
        }

        // Ensure WooCommerce checkout scripts and styles are formally enqueued just in case we are on a custom page
        if (function_exists('is_woocommerce') && function_exists('WC')) {
            // Load core WC scripts if they haven't been already
            if (class_exists('WC_Frontend_Scripts')) {
                \WC_Frontend_Scripts::load_scripts();
            }

            // Core WC styles
            wp_enqueue_style('woocommerce-layout');
            wp_enqueue_style('woocommerce-smallscreen');
            wp_enqueue_style('woocommerce-general');

            // Core WC scripts needed for checkout
            wp_enqueue_script('wc-add-to-cart');
            wp_enqueue_script('woocommerce');
            wp_enqueue_script('wc-cart-fragments');

            // Checkout specific scripts
            wp_enqueue_script('selectWoo');
            wp_enqueue_script('wc-country-select');
            wp_enqueue_script('wc-address-i18n');
            wp_enqueue_script('wc-checkout');
            wp_enqueue_script('wc-password-strength-meter');

            // Trigger the woocommerce_frontend_scripts action to allow gateways to load their scripts
            // We force is_checkout to be true ONLY for the duration of this specific action hook
            add_filter('woocommerce_is_checkout', '__return_true');
            do_action('woocommerce_frontend_scripts');
            do_action('woocommerce_enqueue_styles');
            remove_filter('woocommerce_is_checkout', '__return_true');

            // Explicitly enqueue woo-stripe-payment checkout scripts on our shortcode page.
            // The plugin normally enqueues via payment_fields (which runs when checkout form renders),
            // but it checks is_checkout() which can be false before our filter runs in some contexts.
            // This ensures Stripe Elements (card fields) load and render correctly.
            if ($has_shortcode && class_exists('WC_Payment_Gateway_Stripe')) {
                $this->enqueue_woo_stripe_checkout_scripts();
            }
        }

        wp_enqueue_script(
            'coderembassy-express-checkout-frontend',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wc-add-to-cart', 'wc-checkout'),
            $this->version,
            true
        );

        // Enqueue without WC deps so CSS always loads even if WC styles are not present (e.g. Elementor-only page)
        wp_enqueue_style(
            'coderembassy-express-checkout-frontend',
            CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );
        // Default card design: always print so plugin design wins on all themes
        add_action('wp_head', array($this, 'print_ceec_default_card_css'), 99998);
        // Checkout layout overrides only when "Pro theme compatibility CSS" is enabled
        $options = get_option('coderembassy_global_options', array());
        $compat_css_enabled = !empty($options['pro_theme_compatibility_css']);
        if ($compat_css_enabled) {
            add_action('wp_head', array($this, 'print_ceec_critical_css'), 99999);
        }

        // Get global options
        $options = get_option('coderembassy_global_options', array());
        $checkout_layout_style = isset($options['checkout_layout_style']) ? $options['checkout_layout_style'] : 'custom';
        
        if (!defined('CODEREMBASSY_EXPRESS_CHECKOUT_PRO_VERSION')) {
            $checkout_layout_style = 'custom';
        }

        /*
        if ($checkout_layout_style === 'custom') {
            $override_css = '
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce,
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout form.checkout {
                    width: 100% !important;
                    float: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce .columns,
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce .row {
                    display: block !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout #order_review,
                #ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce-checkout-review-order {
                    width: 100% !important;
                    float: none !important;
                    clear: both !important;
                }
            ';
            wp_add_inline_style('coderembassy-express-checkout-frontend', $override_css);
        }
        */

        // Localize script
        wp_localize_script('coderembassy-express-checkout-frontend', 'coderembassyData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coderembassy_express_checkout_nonce'),
            'checkoutLayoutStyle' => $checkout_layout_style,
            'i18n' => array(
                'addSelectedToCart' => esc_html__('Add Selected to Cart', 'coderembassy-express-checkout'),
                'noProductsSelected' => esc_html__('Please select at least one product.', 'coderembassy-express-checkout'),
                'addingToCart' => esc_html__('Adding to cart...', 'coderembassy-express-checkout'),
                'addedToCart' => esc_html__('Added to cart!', 'coderembassy-express-checkout'),
                'error' => esc_html__('An error occurred. Please try again.', 'coderembassy-express-checkout'),
            ),
            'paymentNotices' => array(
                'stripeKeyError' => __('There was an error registering the payment method with id \'stripe_cc\': Error: Please call Stripe() with your publishable key. You used an empty string.', 'coderembassy-express-checkout'),
                'gatewayInitError' => __('There was an error registering the payment method. Please check your payment gateway configuration (e.g. Stripe publishable key) in WooCommerce → Settings → Payments.', 'coderembassy-express-checkout'),
                'noPaymentMethods' => __('There are no payment methods available. Please contact us for help placing your order.', 'coderembassy-express-checkout'),
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
     * Print default card design CSS in head so plugin design always shows on shortcode page.
     * Runs on every page that contains our shortcode (free and pro themes).
     */
    public function print_ceec_default_card_css() {
        if (!$this->is_our_checkout_page()) {
            return;
        }
        $css = '
/* CEEC default card design (plugin design, not theme) */
.coderembassy-express-checkout .coderembassy-products-grid,
body.ceec-express-page .coderembassy-express-checkout .coderembassy-products-grid {
    display: grid !important;
    flex-wrap: unset !important;
    flex-direction: unset !important;
    margin-bottom: 30px !important;
}
.coderembassy-express-checkout .coderembassy-product-item,
body.ceec-express-page .coderembassy-express-checkout .coderembassy-product-item {
    display: flex !important;
    flex-direction: column !important;
    flex: none !important;
    width: auto !important;
    min-height: 0 !important;
    position: relative !important;
    border: 2px solid #e1e5e9 !important;
    border-radius: 12px !important;
    padding: 15px !important;
    background: #fff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07) !important;
    overflow: visible !important;
}
.coderembassy-express-checkout .coderembassy-product-body,
body.ceec-express-page .coderembassy-express-checkout .coderembassy-product-body {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    min-height: 0 !important;
}
.coderembassy-express-checkout .coderembassy-product-footer,
body.ceec-express-page .coderembassy-express-checkout .coderembassy-product-footer {
    flex-shrink: 0 !important;
    margin-top: 10px !important;
    padding-top: 10px !important;
    border-top: 1px solid #f0f0f0 !important;
}
';
        echo '<style id="ceec-default-card-css" type="text/css">' . wp_strip_all_tags($css) . "</style>\n";
    }

    /**
     * Print critical CSS for checkout form layout when "Pro theme compatibility CSS" is enabled.
     */
    public function print_ceec_critical_css() {
        if (!$this->is_our_checkout_page()) {
            return;
        }
        $options = get_option('coderembassy_global_options', array());
        if (empty($options['pro_theme_compatibility_css'])) {
            return;
        }
        $css = '
/* CEEC checkout form layout (when Pro theme compatibility CSS is enabled) */
#ceec-custom-checkout-wrapper.coderembassy-custom-layout {
    width: 100% !important;
    max-width: 100% !important;
}
@media (min-width: 768px) {
#ceec-custom-checkout-wrapper.coderembassy-custom-layout form.checkout {
    display: grid !important;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) !important;
    grid-column-gap: 24px !important;
    align-items: start !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce-NoticeGroup-checkout,
#ceec-custom-checkout-wrapper.coderembassy-custom-layout .woocommerce-NoticeGroup {
    grid-column: 1 / -1 !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout #customer_details,
#ceec-custom-checkout-wrapper.coderembassy-custom-layout #customer_details.customer-details {
    grid-column: 1 / span 1 !important;
    display: block !important;
    width: 100% !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout #ceec-order-summary-wrapper {
    grid-column: 2 / span 1 !important;
    display: block !important;
    width: 100% !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout #ceec-order-summary-wrapper #order_review_heading {
    margin-bottom: 8px !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout #ceec-order-summary-wrapper #order_review {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout .col2-set {
    display: block !important;
    margin: 0 !important;
    float: none !important;
    width: 100% !important;
}
#ceec-custom-checkout-wrapper.coderembassy-custom-layout .col2-set .col-1,
#ceec-custom-checkout-wrapper.coderembassy-custom-layout .col2-set .col-2 {
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
}
}
';
        echo '<style id="ceec-critical-css" type="text/css">' . wp_strip_all_tags($css) . "</style>\n";
    }

    /**
     * Enqueue admin scripts and styles
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public function admin_enqueue_scripts($hook)
    {
        global $post_type;

        // Only load on our plugin pages
        $current_screen = get_current_screen();
        $should_load = false;

        // Check if we're on our post type pages
        if ($post_type === 'coderembassy_ec') {
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
            'nonce' => wp_create_nonce('coderembassy_admin_nonce'),
            'i18n' => array(
                'searchProducts' => esc_html__('Type at least 3 characters to search products...', 'coderembassy-express-checkout'),
                'noProductsFound' => esc_html__('No products found', 'coderembassy-express-checkout'),
            )
        ));
    }
}
