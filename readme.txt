=== CoderEmbassy Express Checkout ===
Contributors: codersaleh, phpcoderhannan, fazlebari
Tags: checkout, express checkout, one-click checkout, quick buy
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Coderembassy express checkout plugin that allows customers to instantly add products to checkout directly from the product grid or page.

== Description ==

**CoderEmbassy Express Checkout** is a simple yet powerful WooCommerce addon that improves your store’s shopping experience.  
It allows customers to **add products to checkout with a single click** — skipping the traditional cart process.

Perfect for digital products, quick purchases, or flash sale events.

🌐[Demo](https://plugin.coderembassy.com/coderembassy-express-checkout-demo/)| 📄[Documentation](https://plugin.coderembassy.com/docs/) 🚀 |[Get Pro Version](https://plugin.coderembassy.com/express-checkout-landing/)

https://www.youtube.com/watch?v=o7mnPtg6QsI&list=PLKHOA0nBvh9Codg8OXb4T2bKF_wR_F8qW&index=6

### 💡 Key Features
– Enable express checkout buttons anywhere using shortcode.
– Works with both **simple** and **variable** products.
– AJAX-powered — no page reloads.
– Fully compatible with WooCommerce checkout flow.
– Lightweight and optimized for performance.
– Developer-friendly with hooks and filters.

### 🎯 Use Case
If your customers often buy single items or you want to simplify the checkout process, this plugin helps you **reduce cart abandonment** and **increase conversion rates** by sending users directly to checkout.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/coderembassy-express-checkout/` directory, or install it through the WordPress plugins screen directly.
2. Activate the plugin through the ‘Plugins’ screen in WordPress.
3. Make sure WooCommerce is active on your site.
4. Use the shortcode `[coderembassy_express_checkout id="PRODUCT_ID"]` to display the express checkout box for a specific product.

== Usage ==

You can display the express checkout form anywhere using a **shortcode**: [coderembassy_express_checkout id="123"]

Replace `123` with your product ID.

If the product is variable, the variation options will automatically appear as buttons for easy selection.

You can also integrate it programmatically using PHP:

<?php
echo do_shortcode('[coderembassy_express_checkout id="123"]');
?>

== Frequently Asked Questions ==

= Does it support variable products? =
Yes, variable products are supported. The plugin displays all variations as selectable buttons.

= Can I customize the button styles? =
Yes, you can override the CSS or enqueue your own custom stylesheet.

= Does it require WooCommerce? =
Yes, WooCommerce must be installed and active.

= Will it work with my theme? =
It’s tested with most popular WooCommerce themes and should work with any theme following standard WooCommerce templates.

== Screenshots ==
1. Express checkout button under product title.

2. Variation buttons displayed inside product grid.

3. Checkout popup / redirect example.

== Changelog ==

= 1.0.1 - 22-02-2026 =

* Fixed Shortcode copy button issue.
* Fixed Stripe Display field in checkout page issue.
* Compatible With Strip

= 1.0.0 =

Initial release.

Added express checkout shortcode.

Added AJAX functionality for adding products to checkout.

Support for simple and variable products.

== Upgrade Notice ==

= 1.0.0 =
First stable release of CoderEmbassy Express Checkout.