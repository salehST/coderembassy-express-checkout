<?php

/**
 * Plugin Name: CoderEmbassy Express Checkout
 * Plugin URI: https://coderembassy.com/coderembassy-express-checkout/
 * Description: A shortcode-based WooCommerce plugin for express checkout with customizable product selection.
 * Version: 1.1.0
 * Author: codersaleh
 * Author URI: https://coderembassy.com
 * Text Domain: coderembassy-express-checkout
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
define('CODEREMBASSY_EXPRESS_CHECKOUT_VERSION', '1.1.0');
define('CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_FILE', __FILE__);
define('CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check WooCommerce compatibility
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('CoderEmbassy Express Checkout requires WooCommerce to be installed and active.', 'coderembassy-express-checkout');
        echo '</p></div>';
    });
    return;
}

/**
 * Check WooCommerce version compatibility
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
add_action('admin_init', function () {
    if (class_exists('WooCommerce')) {
        $wc_version = WC()->version;
        $required_version = '5.0.0';

        if (version_compare($wc_version, $required_version, '<')) {
            add_action('admin_notices', function () use ($required_version, $wc_version) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    // translators: %1$s is the required WooCommerce version, %2$s is the current WooCommerce version
                    esc_html__('CoderEmbassy Express Checkout requires WooCommerce version %1$s or higher. You are running version %2$s.', 'coderembassy-express-checkout'),
                    esc_html($required_version),
                    esc_html($wc_version)
                );
                echo '</p></div>';
            });
        }
    }
});

/**
 * Declare WooCommerce compatibility
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

/**
 * Autoloader
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
spl_autoload_register(function ($class) {
    $prefix = 'CoderEmbassy\\ExpressCheckout\\';
    $base_dir = CODEREMBASSY_EXPRESS_CHECKOUT_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize plugin
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
add_action('plugins_loaded', function () {
    CoderEmbassy\ExpressCheckout\Plugin::get_instance();
});

/**
 * Activate plugin
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
register_activation_hook(__FILE__, function () {
    CoderEmbassy\ExpressCheckout\Activator::activate();
});

/**
 * Deactivate plugin
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
register_deactivation_hook(__FILE__, function () {
    CoderEmbassy\ExpressCheckout\Deactivator::deactivate();
});
