<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Plugin activation class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Activator {
    
    /**
     * Activate plugin
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public static function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $default_options = array(
            'coderembassy_express_checkout_version' => CODEREMBASSY_EXPRESS_CHECKOUT_VERSION,
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}
