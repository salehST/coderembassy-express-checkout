# CoderEmbassy Express Checkout

A WordPress plugin for WooCommerce that provides express checkout functionality through shortcodes.

## Features

- **Shortcode-based**: Create multiple express checkout shortcodes
- **Product Selection**: Search and select products for each shortcode
- **AJAX Add to Cart**: Add products to cart without page reload
- **Quick Cart**: Add products to cart instantly when checkbox is clicked
- **Customizable Design**: Control product display width, height, title font size and color
- **Admin Interface**: Easy-to-use admin interface with tab system
- **Responsive Design**: Works on all devices

## Installation

1. Upload the plugin files to `/wp-content/plugins/coderembassy-express-checkout/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Make sure WooCommerce is installed and activated

## Usage

### Creating a Shortcode

1. Go to **Express Checkout** in your WordPress admin menu
2. Click **Add New Shortcode**
3. Give your shortcode a title
4. In the **Settings** tab:
   - Search and select products
   - Enable AJAX Add to Cart (optional)
   - Enable Quick Cart (optional)
5. In the **Design** tab:
   - Set product area width and height
   - Customize title font size and color
6. Publish the shortcode
7. Copy the generated shortcode and paste it on any page or post

### Using the Shortcode

```
[coderembassy_express_checkout id="123"]
```

Replace `123` with your shortcode ID.

### Features Explained

- **AJAX Add to Cart**: When enabled, products are added to cart without page reload
- **Quick Cart**: When enabled, products are added to cart immediately when checkbox is clicked
- **Product Selection**: Users can select multiple products using checkboxes
- **Add to Cart Button**: Adds all selected products to cart at once

## Customization

The plugin uses the `coderembassy` prefix for all CSS classes and IDs, making it easy to customize the appearance.

### CSS Classes

- `.coderembassy-express-checkout` - Main container
- `.coderembassy-products-grid` - Products grid
- `.coderembassy-product-item` - Individual product item
- `.coderembassy-product-checkbox` - Product checkbox
- `.coderembassy-add-to-cart-btn` - Add to cart button

### JavaScript Events

The plugin triggers standard WooCommerce events:
- `added_to_cart` - When products are added to cart

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Support

For support and feature requests, please contact the plugin author.

## Changelog

### 1.0.0
- Initial release
- Shortcode-based express checkout
- AJAX add to cart functionality
- Quick cart feature
- Customizable design options
- Admin interface with tab system
