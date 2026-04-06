<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Plugin deactivation class
 * @since 1.0.0
 * @author Fazle Bari <fazlebarisn@gmail.com>
 */
class Deactivator {
    
    /**
     * Deactivate plugin
     * @since 1.0.0
     * @author Fazle Bari <fazlebarisn@gmail.com>
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
