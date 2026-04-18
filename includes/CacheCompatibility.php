<?php

namespace CoderEmbassy\ExpressCheckout;

/**
 * Page-cache compatibility: express checkout is session/cart-dependent and must not be
 * served as static HTML. Works alongside wp-admin exclusions (LiteSpeed, Redis, CDN).
 *
 * @see https://docs.litespeedtech.com/lscache/lscwp/api/ LiteSpeed Cache API
 */
class CacheCompatibility
{

    /**
     * Register hooks (call once from Plugin).
     */
    public static function register()
    {
        // Early: LiteSpeed can decide cacheability before template output.
        add_action('wp', array(__CLASS__, 'maybe_disable_caching'), 0);

        // Filters evaluated later in the same request — use lazy checks via $plugin.
        add_filter('rocket_skip_cache', array(__CLASS__, 'filter_rocket_skip_cache'), 999, 1);
        add_filter('litespeed_control_cacheable', array(__CLASS__, 'filter_litespeed_cacheable'), 999, 1);

        // WP-Optimize page cache (when present)
        add_filter('wpo_can_cache_page', array(__CLASS__, 'filter_wpo_can_cache_page'), 999, 1);

        // Breeze (Cloudways)
        add_filter('breeze_prevent_cache', array(__CLASS__, 'filter_breeze_prevent_cache'), 999, 1);

        // HTTP caches / some CDNs
        add_action('send_headers', array(__CLASS__, 'maybe_send_nocache_headers'), 0);
    }

    /**
     * @param Plugin $plugin Plugin instance (static context).
     */
    private static function plugin()
    {
        return Plugin::get_instance();
    }

    /**
     * Disable full-page caching for the current request when the page contains our shortcode.
     */
    public static function maybe_disable_caching()
    {
        $plugin = self::plugin();
        if (!$plugin->is_express_checkout_page()) {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        // LiteSpeed Cache for WordPress — official control API.
        if (function_exists('do_action')) {
            do_action('litespeed_control_set_nocache', 'coderembassy-express-checkout');
        }

        // WP-Optimize
        if (!defined('WPO_DISABLE_CACHE')) {
            define('WPO_DISABLE_CACHE', true);
        }

        /**
         * Allow other plugins/themes to react (e.g. additional cache plugins).
         */
        do_action('coderembassy_express_checkout_disable_page_cache');
    }

    /**
     * @param bool $skip Current skip state.
     * @return bool
     */
    public static function filter_rocket_skip_cache($skip)
    {
        return self::plugin()->is_express_checkout_page() ? true : $skip;
    }

    /**
     * @param bool $cacheable Whether LiteSpeed may cache this response.
     * @return bool
     */
    public static function filter_litespeed_cacheable($cacheable)
    {
        return self::plugin()->is_express_checkout_page() ? false : $cacheable;
    }

    /**
     * @param bool $can_cache Whether WP-Optimize may cache this page.
     * @return bool
     */
    public static function filter_wpo_can_cache_page($can_cache)
    {
        return self::plugin()->is_express_checkout_page() ? false : $can_cache;
    }

    /**
     * @param bool $prevent Current state.
     * @return bool
     */
    public static function filter_breeze_prevent_cache($prevent)
    {
        return self::plugin()->is_express_checkout_page() ? true : $prevent;
    }

    /**
     * Send standard WordPress no-cache headers on express checkout pages.
     */
    public static function maybe_send_nocache_headers()
    {
        if (!self::plugin()->is_express_checkout_page()) {
            return;
        }
        if (!headers_sent()) {
            nocache_headers();
        }
    }
}
