/**
 * CoderEmbassy Express Checkout Frontend JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        initExpressCheckout();
    });

    function initExpressCheckout() {
        $('.coderembassy-express-checkout').each(function () {
            var $container = $(this);

            // Skip if already initialized
            if ($container.data('coderembassy-initialized')) {
                return;
            }

            var shortcodeId = $container.data('shortcode-id');
            var ajaxCartValue = $container.data('ajax-cart');
            var ajaxCart = ajaxCartValue == '1' || ajaxCartValue === 1;
            var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;

            // Mark as initialized
            $container.data('coderembassy-initialized', true);

            // Initialize product checkboxes
            initProductCheckboxes($container, quickCart);

            // Initialize product click handlers only for quick cart
            if (quickCart) {
                initProductClickHandlers($container);
            }

            // Initialize quantity handlers
            initQuantityHandlers($container, quickCart);

            // Initialize variation handlers
            initVariationHandlers($container);

            // Force enable all variation inputs multiple times to ensure they stay enabled
            function forceEnableVariations() {
                $container.find('.coderembassy-variation-option input[type="radio"]').prop('disabled', false);
            }

            // Run immediately and then multiple times with delays
            forceEnableVariations();
            setTimeout(forceEnableVariations, 100);
            setTimeout(forceEnableVariations, 300);
            setTimeout(forceEnableVariations, 500);

            // Also run on any variation change to ensure they stay enabled
            $container.find('.coderembassy-variation-option input[type="radio"]').on('change', function () {
                setTimeout(forceEnableVariations, 10);
            });

            // Prevent any external interference with variation radio buttons
            $container.find('.coderembassy-variation-option input[type="radio"]').on('click', function (e) {
                e.stopPropagation();
                // Ensure this radio is checked and others in the same group are unchecked
                var $this = $(this);
                var name = $this.attr('name');
                $container.find('input[name="' + name + '"]').not($this).prop('checked', false);
                $this.prop('checked', true);
            });

            // Watch for any external changes to radio buttons and restore state
            var $variationRadios = $container.find('.coderembassy-variation-option input[type="radio"]');
            var lastKnownState = {};

            // Store initial state
            $variationRadios.each(function () {
                var $radio = $(this);
                var key = $radio.attr('name') + '_' + $radio.val();
                lastKnownState[key] = $radio.is(':checked');
            });

            // Store reference to lastKnownState for use in variation handlers
            $container.data('lastKnownState', lastKnownState);

            // Monitor for changes every 100ms
            setInterval(function () {
                $variationRadios.each(function () {
                    var $radio = $(this);
                    var key = $radio.attr('name') + '_' + $radio.val();
                    var currentState = $radio.is(':checked');

                    // If state changed unexpectedly, restore it
                    if (lastKnownState[key] !== currentState) {
                        $radio.prop('checked', lastKnownState[key]);
                    }
                });
            }, 100);

            // Initialize add to cart button
            initAddToCartButton($container, ajaxCart);
        });
    }

    function initProductCheckboxes($container, quickCart) { // Remove any existing event handlers to prevent duplicates
        $container.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input').off('change.coderembassy');

        var ajaxCart = $container.data('ajax-cart') == '1' || $container.data('ajax-cart') === 1;

        // Handle both checkbox and radio inputs
        $container.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input').on('change.coderembassy', function () {
            var $input = $(this);
            var $productItem = $input.closest('.coderembassy-product-item');
            var productId = $input.data('product-id');
            var isRadio = $input.is('input[type="radio"]');
            var hasVariations = $productItem.hasClass('has-variations');

            if ($input.is(':checked')) {
                $productItem.addClass('selected');

                // For radio buttons, unselect all other products
                if (isRadio) {
                    $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                    $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                }

                // Add to cart based on mode
                if (quickCart) { // Quick cart functionality - add to cart immediately when checked
                    var productData = getProductData($productItem, productId);
                    addToCartQuick($container, [productData], $productItem);
                }
                // For regular AJAX add to cart, products are only added when "Add Selected to Cart" button is clicked
                // For variable products with AJAX cart, they'll be added when variations are selected

            } else {
                $productItem.removeClass('selected');
            } updateAddToCartButton($container);
        });
    }

    function initProductClickHandlers($container) { // Remove any existing event handlers to prevent duplicates
        $container.find('.coderembassy-product-item').off('click.coderembassy');

        var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;
        var ajaxCart = $container.data('ajax-cart') == '1' || $container.data('ajax-cart') === 1;

        // Add click handlers to product items
        $container.find('.coderembassy-product-item').on('click.coderembassy', function (e) { // Don't trigger if clicking on checkbox/radio or its label
            if ($(e.target).is('input[type="checkbox"], input[type="radio"], label')) {
                return;
            }

            // Don't trigger if clicking on title link
            if ($(e.target).is('a') || $(e.target).closest('a').length > 0) {
                return;
            }

            var $productItem = $(this);
            var $input = $productItem.find('input[type="checkbox"], input[type="radio"]');
            var productId = $input.data('product-id');
            var isRadio = $input.is('input[type="radio"]');
            var hasVariations = $productItem.hasClass('has-variations');

            // Don't allow clicking if input is disabled (variations not selected)
            if ($input.prop('disabled')) {
                return;
            }

            if (productId) { // Toggle the input state
                if ($input.is(':checked')) { // If already checked, uncheck it
                    $input.prop('checked', false);
                    $productItem.removeClass('selected');
                } else { // If not checked, check it
                    $input.prop('checked', true);
                    $productItem.addClass('selected');

                    // For radio buttons, unselect all other products
                    if (isRadio) {
                        $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                        $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                    }

                    // Add to cart based on mode
                    if (quickCart) { // Quick Cart: Add to cart immediately
                        var productData = getProductData($productItem, productId);
                        addToCartQuick($container, [productData], $productItem);
                    }
                    // For regular AJAX add to cart, products are only added when "Add Selected to Cart" button is clicked
                    // For variable products with AJAX cart, they'll be added when variations are selected
                }

                // Update add to cart button state
                updateAddToCartButton($container);
            }
        });
    }

    function initVariationHandlers($container) { // Initialize variation state for products with variations
        $container.find('.coderembassy-product-item.has-variations').each(function () {
            var $productItem = $(this);
            var $variationGroup = $productItem.find('.coderembassy-product-variations');
            var $input = $productItem.find('input[type="checkbox"], input[type="radio"]');

            // Enable all variation radio buttons
            $variationGroup.find('input[type="radio"]').prop('disabled', false);

            // Initially disable only the checkbox/radio for variable products
            $input.prop('disabled', true);
            $productItem.addClass('variation-incomplete');
        });

        // Handle variation selection with state preservation
        $container.find('.coderembassy-variation-option input[type="radio"]').on('change', function () {
            var $variationGroup = $(this).closest('.coderembassy-product-variations');
            var productId = $variationGroup.data('product-id');
            var $productItem = $variationGroup.closest('.coderembassy-product-item');
            var $input = $productItem.find('input[type="checkbox"], input[type="radio"]');
            var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;


            // Store current selection state before any processing
            var currentSelections = {};
            $variationGroup.find('input[type="radio"]:checked').each(function () {
                var $radio = $(this);
                currentSelections[$radio.attr('name')] = $radio.val();
            });

            // Update the global state tracking
            var lastKnownState = $container.data('lastKnownState') || {};
            $variationGroup.find('input[type="radio"]').each(function () {
                var $radio = $(this);
                var key = $radio.attr('name') + '_' + $radio.val();
                lastKnownState[key] = $radio.is(':checked');
            });
            $container.data('lastKnownState', lastKnownState);


            // Restore any lost selections after a short delay
            setTimeout(function () {
                var needsRestore = false;
                $variationGroup.find('input[type="radio"]').each(function () {
                    var $radio = $(this);
                    var name = $radio.attr('name');
                    var value = $radio.val();

                    // If this radio should be checked but isn't, restore it
                    if (currentSelections[name] === value && ! $radio.is(':checked')) {
                        $radio.prop('checked', true);
                        needsRestore = true;
                    }
                });

                if (needsRestore) { // Re-trigger the change event after restoration
                    $variationGroup.find('input[type="radio"]:checked').first().trigger('change');
                }
            }, 50);

            // Check if all variations are selected
            var allVariationsSelected = checkAllVariationsSelected($variationGroup);

            if (allVariationsSelected) { // Enable product selection
                $input.prop('disabled', false);
                $productItem.removeClass('variation-incomplete');

                // Store variation attributes (backend will find the variation ID)
                var variationAttributes = {};
                $variationGroup.find('input[type="radio"]:checked').each(function () {
                    var attribute = $(this).data('attribute');
                    var value = $(this).val();
                    variationAttributes[attribute] = value;
                });

                if (Object.keys(variationAttributes).length > 0) {
                    $input.data('variation-attributes', variationAttributes);
                }

                // Auto-select the product when all variations are chosen
                $input.prop('checked', true);
                $productItem.addClass('selected auto-selected');

                // Remove auto-selected class after animation
                setTimeout(function () {
                    $productItem.removeClass('auto-selected');
                }, 600);

                // If quick cart is enabled, add to cart immediately
                if (quickCart) {
                    var productData = getProductData($productItem, productId);
                    addToCartQuick($container, [productData], $productItem);
                }
                // Note: When Quick Cart is disabled, products are only added when "Add Selected to Cart" button is clicked

                // Update add to cart button state
                updateAddToCartButton($container);

            } else { // Disable product selection
                $input.prop('disabled', true);
                $productItem.addClass('variation-incomplete');
                $input.data('variation-id', null);

                // Uncheck the product if not all variations are selected
                $input.prop('checked', false);
                $productItem.removeClass('selected');

                // Update add to cart button state
                updateAddToCartButton($container);
            }
        });

        // Remove the problematic label click handler - let radio buttons work naturally
    }

    function checkAllVariationsSelected($variationGroup) {
        var $groups = $variationGroup.find('.coderembassy-variation-group');
        var allSelected = true;

        $groups.each(function (index) {
            var $group = $(this);
            var $selected = $group.find('input[type="radio"]:checked');
            if ($selected.length === 0) {
                allSelected = false;
                return false; // break
            }
        });

        return allSelected;
    }

    // Removed getSelectedVariationId function - now using variation attributes directly

    function initAddToCartButton($container, ajaxCart) { // Remove any existing event handlers to prevent duplicates
        $container.find('.coderembassy-add-to-cart-btn').off('click.coderembassy');
        $container.find('.coderembassy-add-to-cart-btn').on('click.coderembassy', function (e) {
            e.preventDefault();

            var selectedProducts = getSelectedProducts($container);

            if (selectedProducts.length === 0) {
                showMessage($container, 'error', coderembassyData.i18n.noProductsSelected || 'Please select at least one product.');
                return;
            }

            if (ajaxCart) {
                addToCartAjax($container, selectedProducts, null);
            } else {
                addToCartForm($container, selectedProducts);
            }
        });
    }

    function getProductData($productItem, productId) {
        var $input = $productItem.find('input[type="checkbox"], input[type="radio"]');
        var variationAttributes = $input.data('variation-attributes');
        var quantity = $productItem.find('.coderembassy-product-quantity input').val();

        var productData = {
            product_id: productId,
            quantity: quantity ? parseInt(quantity) : 1
        };

        // Add variation data if available
        if (variationAttributes && Object.keys(variationAttributes).length > 0) {
            productData.variation = variationAttributes;
        }

        return productData;
    }

    function initQuantityHandlers($container, quickCart) {
        $container.find('.coderembassy-product-quantity input').on('change', function () {
            var $quantityInput = $(this);
            var $productItem = $quantityInput.closest('.coderembassy-product-item');
            var $input = $productItem.find('input[type="checkbox"], input[type="radio"]');
            var productId = $input.data('product-id');

            // Only update cart if the product is selected
            if ($input.is(':checked') && productId) {
                if (quickCart) {
                    var productData = getProductData($productItem, productId);
                    addToCartQuick($container, [productData], $productItem);
                }

                // Update add to cart button text/state
                updateAddToCartButton($container);
            }
        });

        // Prevent product click handler when clicking quantity input
        $container.find('.coderembassy-product-quantity').on('click', function (e) {
            e.stopPropagation();
        });
    }

    function getSelectedProducts($container) {
        var selectedProducts = [];

        $container.find('.coderembassy-product-checkbox-input:checked, .coderembassy-product-radio-input:checked').each(function () {
            var $input = $(this);
            var productId = $input.data('product-id');
            var $productItem = $input.closest('.coderembassy-product-item');

            if (productId) {
                var productData = getProductData($productItem, productId);
                selectedProducts.push(productData);
            }
        });

        return selectedProducts;
    }

    function updateAddToCartButton($container) {
        var selectedCount = $container.find('.coderembassy-product-checkbox-input:checked, .coderembassy-product-radio-input:checked').length;
        var $button = $container.find('.coderembassy-add-to-cart-btn');

        if (selectedCount > 0) {
            $button.prop('disabled', false);
            $button.text(coderembassyData.i18n.addSelectedToCart || 'Add Selected to Cart (' + selectedCount + ')');
        } else {
            $button.prop('disabled', true);
            $button.text(coderembassyData.i18n.addSelectedToCart || 'Add Selected to Cart');
        }
    }

    function addToCartAjax($container, productIds, $productItem) {
        var $button = $container.find('.coderembassy-add-to-cart-btn');
        var $loading = $container.find('.coderembassy-loading');

        // Temporarily disable WooCommerce's automatic cart fragment updates to prevent conflicts
        var originalCartHashKey = null;
        var originalWcAjaxUrl = null;

        if (typeof wc_add_to_cart_params !== 'undefined') {
            originalCartHashKey = wc_add_to_cart_params.cart_hash_key;
            originalWcAjaxUrl = wc_add_to_cart_params.wc_ajax_url;
            wc_add_to_cart_params.cart_hash_key = 'coderembassy_cart_hash_' + Date.now();
            wc_add_to_cart_params.wc_ajax_url = 'disabled';
        }

        // Temporarily disable WooCommerce add to cart events
        $(document.body).off('added_to_cart');
        $(document.body).off('wc_fragment_refresh');

        // Set a flag to prevent WooCommerce from processing our AJAX response
        window.coderembassy_processing = true;

        // Show loading state
        $button.prop('disabled', true);
        $loading.show();

        // Make AJAX request

        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_add_to_cart',
                products_data: productIds, // Changed from product_ids to products_data to match backend
                nonce: coderembassyData.nonce
            },
            success: function (response) {
                if (response.success) {
                    showMessage($container, 'success', response.data.message);

                    // Clear selected products after successful add to cart
                    clearSelectedProducts($container);

                    // Show checkout section if it exists and was hidden
                    showCheckoutSection($container);

                    // Update cart count with comprehensive selectors
                    if (response.data.cart_count !== undefined) {
                        var cartCountSelectors = [
                            '.cart-contents-count',
                            '.cart-count',
                            '.header-cart-count',
                            '.cart-counter',
                            '.woocommerce-cart-count',
                            '.cart-items-count',
                            '.site-header-cart .count',
                            '.site-header-cart .cart-contents-count',
                            '.woocommerce-mini-cart .count',
                            '.widget_shopping_cart .count',
                            '.cart-total .count',
                            '.header-cart .count',
                            '.mini-cart .count',
                            '.cart-icon .count',
                            '.cart-badge .count',
                            '.cart-number',
                            '.cart-quantity',
                            '.cart-items',
                            '.items-count',
                            '.total-items'
                        ];

                        var updatedCount = 0;
                        $.each(cartCountSelectors, function (index, selector) {
                            var $elements = $(selector);
                            if ($elements.length > 0) {
                                $elements.text(response.data.cart_count);
                                updatedCount += $elements.length;
                            }
                        });

                        // Also try to update any element that contains a number and is near cart-related elements
                        $('.site-header-cart, .woocommerce-mini-cart, .widget_shopping_cart, .header-cart, .mini-cart').each(function () {
                            var $container = $(this);
                            $container.find('*').each(function () {
                                var $element = $(this);
                                var text = $element.text().trim();
                                // If element contains only a number and is likely a cart count
                                if (/^\d+$/.test(text) && $element.children().length === 0) {
                                    $element.text(response.data.cart_count);
                                }
                            });
                        });

                        // Trigger WooCommerce's own cart update mechanism with a delay
                        setTimeout(function () {
                            if (typeof wc_cart_fragments_params !== 'undefined') {
                                $(document.body).trigger('wc_fragment_refresh');
                                $(document.body).trigger('update_checkout');
                            }
                        }, 100);
                    }

                    // Update mini cart content if it exists
                    if (response.data.cart_fragments && response.data.cart_fragments['div.widget_shopping_cart_content']) {
                        var miniCartContent = response.data.cart_fragments['div.widget_shopping_cart_content'];

                        // Try multiple selectors for different themes
                        var $miniCart = $('.site-header-cart .widget_shopping_cart_content, .woocommerce-mini-cart .widget_shopping_cart_content, .widget_shopping_cart .widget_shopping_cart_content, .header-cart .widget_shopping_cart_content, .mini-cart .widget_shopping_cart_content');

                        if ($miniCart.length > 0) {
                            $miniCart.html(miniCartContent);
                        } else { // Fallback: try to find any element with the exact class
                            var $fallbackCart = $('div.widget_shopping_cart_content');
                            if ($fallbackCart.length > 0) {
                                $fallbackCart.html(miniCartContent);
                            }
                        }
                    }
                } else {
                    showMessage($container, 'error', response.data.message || coderembassyData.i18n.error);
                }
            },
            error: function (xhr, status, error) {
                var errorMessage = coderembassyData.i18n.error || 'An error occurred. Please try again.';

                // Try to get more specific error message from response
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch (e) { // Use default error message
                    }
                }

                showMessage($container, 'error', errorMessage);
            },
            complete: function () {
                $button.prop('disabled', false);
                $loading.hide();

                // Clear the processing flag
                window.coderembassy_processing = false;

                // Restore original WooCommerce settings
                if (typeof wc_add_to_cart_params !== 'undefined') {
                    if (originalCartHashKey !== null) {
                        wc_add_to_cart_params.cart_hash_key = originalCartHashKey;
                    }
                    if (originalWcAjaxUrl !== null) {
                        wc_add_to_cart_params.wc_ajax_url = originalWcAjaxUrl;
                    }
                }
            }
        });
    }

    function addToCartQuick($container, productIds, $productItem) { // Add visual feedback
        $productItem.addClass('quick-cart-added');

        // Temporarily disable WooCommerce's automatic cart fragment updates to prevent conflicts
        var originalCartHashKey = null;
        var originalWcAjaxUrl = null;

        if (typeof wc_add_to_cart_params !== 'undefined') {
            originalCartHashKey = wc_add_to_cart_params.cart_hash_key;
            originalWcAjaxUrl = wc_add_to_cart_params.wc_ajax_url;
            wc_add_to_cart_params.cart_hash_key = 'coderembassy_cart_hash_' + Date.now();
            wc_add_to_cart_params.wc_ajax_url = 'disabled';
        }

        // Temporarily disable WooCommerce add to cart events
        $(document.body).off('added_to_cart');
        $(document.body).off('wc_fragment_refresh');

        // Make AJAX request
        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_add_to_cart',
                products_data: productIds, // Changed from product_ids to products_data to match backend
                nonce: coderembassyData.nonce
            },
            success: function (response) {
                if (response.success) { // Show success notification
                    showMessage($container, 'success', response.data.message);

                    // Show checkout section if it exists and was hidden
                    showCheckoutSection($container);

                    // Update cart count with comprehensive selectors
                    if (response.data.cart_count !== undefined) {
                        var cartCountSelectors = [
                            '.cart-contents-count',
                            '.cart-count',
                            '.header-cart-count',
                            '.cart-counter',
                            '.woocommerce-cart-count',
                            '.cart-items-count',
                            '.site-header-cart .count',
                            '.site-header-cart .cart-contents-count',
                            '.woocommerce-mini-cart .count',
                            '.widget_shopping_cart .count',
                            '.cart-total .count',
                            '.header-cart .count',
                            '.mini-cart .count',
                            '.cart-icon .count',
                            '.cart-badge .count',
                            '.cart-number',
                            '.cart-quantity',
                            '.cart-items',
                            '.items-count',
                            '.total-items'
                        ];

                        var updatedCount = 0;
                        $.each(cartCountSelectors, function (index, selector) {
                            var $elements = $(selector);
                            if ($elements.length > 0) {
                                $elements.text(response.data.cart_count);
                                updatedCount += $elements.length;
                            }
                        });

                        // Also try to update any element that contains a number and is near cart-related elements
                        $('.site-header-cart, .woocommerce-mini-cart, .widget_shopping_cart, .header-cart, .mini-cart').each(function () {
                            var $container = $(this);
                            $container.find('*').each(function () {
                                var $element = $(this);
                                var text = $element.text().trim();
                                // If element contains only a number and is likely a cart count
                                if (/^\d+$/.test(text) && $element.children().length === 0) {
                                    $element.text(response.data.cart_count);
                                }
                            });
                        });

                        // Trigger WooCommerce's own cart update mechanism with a delay
                        setTimeout(function () {
                            if (typeof wc_cart_fragments_params !== 'undefined') {
                                $(document.body).trigger('wc_fragment_refresh');
                            }
                        }, 100);
                    }

                    // Update mini cart content if it exists
                    if (response.data.cart_fragments && response.data.cart_fragments['div.widget_shopping_cart_content']) {
                        var miniCartContent = response.data.cart_fragments['div.widget_shopping_cart_content'];

                        // Try multiple selectors for different themes
                        var $miniCart = $('.site-header-cart .widget_shopping_cart_content, .woocommerce-mini-cart .widget_shopping_cart_content, .widget_shopping_cart .widget_shopping_cart_content, .header-cart .widget_shopping_cart_content, .mini-cart .widget_shopping_cart_content');

                        if ($miniCart.length > 0) {
                            $miniCart.html(miniCartContent);
                        } else { // Fallback: try to find any element with the exact class
                            var $fallbackCart = $('div.widget_shopping_cart_content');
                            if ($fallbackCart.length > 0) {
                                $fallbackCart.html(miniCartContent);
                            }
                        }
                    }
                } else {
                    showMessage($container, 'error', response.data.message || coderembassyData.i18n.error);
                }
            },
            error: function (xhr, status, error) {
                var errorMessage = coderembassyData.i18n.error || 'An error occurred. Please try again.';

                // Try to get more specific error message from response
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch (e) { // Use default error message
                    }
                }

                showMessage($container, 'error', errorMessage);
            },
            complete: function () { // Remove visual feedback after animation
                setTimeout(function () {
                    $productItem.removeClass('quick-cart-added');
                }, 600);

                // Restore original WooCommerce settings
                if (typeof wc_add_to_cart_params !== 'undefined') {
                    if (originalCartHashKey !== null) {
                        wc_add_to_cart_params.cart_hash_key = originalCartHashKey;
                    }
                    if (originalWcAjaxUrl !== null) {
                        wc_add_to_cart_params.wc_ajax_url = originalWcAjaxUrl;
                    }
                }
            }
        });
    }

    function addToCartForm($container, productIds) { // Create a form for non-AJAX submission
        var $form = $('<form method="post" action="">');

        // Add nonce for security
        $form.append('<input type="hidden" name="coderembassy_express_checkout_nonce" value="' + coderembassyData.nonce + '">');

        // Add action
        $form.append('<input type="hidden" name="action" value="coderembassy_add_to_cart_form">');

        // Add selected product IDs
        $.each(productIds, function (index, productData) {
            if (typeof productData === 'object' && productData.product_id) { // New format: {product_id: 49, variation: {...}}
                $form.append('<input type="hidden" name="product_ids[]" value="' + productData.product_id + '">');

                // Add variation data if present
                if (productData.variation && Object.keys(productData.variation).length > 0) {
                    $.each(productData.variation, function (attr, value) {
                        $form.append('<input type="hidden" name="variation[' + productData.product_id + '][' + attr + ']" value="' + value + '">');
                    });
                }
            } else { // Old format: just product ID number
                $form.append('<input type="hidden" name="product_ids[]" value="' + productData + '">');
            }
        });

        // Add shortcode ID if available
        var shortcodeId = $container.data('shortcode-id');
        if (shortcodeId) {
            $form.append('<input type="hidden" name="shortcode_id" value="' + shortcodeId + '">');
        }

        // Append form to body and submit
        $('body').append($form);
        $form.submit();
    }

    function clearSelectedProducts($container) { // Uncheck all selected products
        $container.find('.coderembassy-product-checkbox-input:checked, .coderembassy-product-radio-input:checked').prop('checked', false);

        // Remove selected class from product items
        $container.find('.coderembassy-product-item.selected').removeClass('selected');

        // Update add to cart button
        updateAddToCartButton($container);
    }

    function showMessage($container, type, message) { // Use the new popup notification system
        showNotification(type, message);
    }

    function showNotification(type, message, duration) { // Default duration is 5 seconds
        duration = duration || 5000;

        // Create notification container if it doesn't exist
        if ($('#coderembassy-notification-container').length === 0) {
            $('body').append('<div id="coderembassy-notification-container"></div>');
        }

        var $container = $('#coderembassy-notification-container');

        // Create notification element
        var notificationId = 'notification-' + Date.now();
        var iconClass = 'dashicons-warning'; // default
        if (type === 'success') {
            iconClass = 'dashicons-yes-alt';
        } else if (type === 'error') {
            iconClass = 'dashicons-warning';
        } else if (type === 'info') {
            iconClass = 'dashicons-info';
        }
        var notificationClass = 'coderembassy-notification coderembassy-notification-' + type;

        var $notification = $('<div id="' + notificationId + '" class="' + notificationClass + '">' + '<div class="coderembassy-notification-content">' + '<div class="coderembassy-notification-icon">' + '<span class="dashicons ' + iconClass + '"></span>' + '</div>' + '<div class="coderembassy-notification-message">' + message + '</div>' + '<div class="coderembassy-notification-close">' + '<span class="dashicons dashicons-no-alt"></span>' + '</div>' + '</div>' + '</div>');

        // Add to container
        $container.append($notification);

        // Animate in
        setTimeout(function () {
            $notification.addClass('coderembassy-notification-show');
        }, 10);

        // Auto-hide after duration
        var hideTimeout = setTimeout(function () {
            hideNotification($notification);
        }, duration);

        // Close button click handler
        $notification.find('.coderembassy-notification-close').on('click', function () {
            clearTimeout(hideTimeout);
            hideNotification($notification);
        });

        // Click anywhere on notification to close
        $notification.on('click', function () {
            clearTimeout(hideTimeout);
            hideNotification($notification);
        });
    }

    function hideNotification($notification) {
        $notification.removeClass('coderembassy-notification-show');
        setTimeout(function () {
            $notification.remove();
        }, 300);
    }

    // Convenience functions for different notification types
    function showSuccessNotification(message, duration) {
        showNotification('success', message, duration);
    }

    function showErrorNotification(message, duration) {
        showNotification('error', message, duration);
    }

    function showInfoNotification(message, duration) {
        showNotification('info', message, duration);
    }

    function showCheckoutSection($container) {
        var $checkoutSection = $container.find('.coderembassy-checkout-section');

        if ($checkoutSection.length > 0) { // Show the checkout section
            $checkoutSection.show();

            // Update the checkout content to show the form instead of empty cart message
            var $checkoutContent = $checkoutSection.find('.coderembassy-checkout-content');
            var $emptyCartMessage = $checkoutContent.find('.coderembassy-empty-cart');
            var $checkoutForm = $checkoutContent.find('.coderembassy-checkout-form');

            if ($emptyCartMessage.length > 0) { // Hide empty cart message
                $emptyCartMessage.hide();

                // Always load checkout form via AJAX since the server-side condition was for empty cart
                loadCheckoutForm($checkoutContent);
            } else if ($checkoutForm.length === 0) { // If no empty cart message and no checkout form, load it
                loadCheckoutForm($checkoutContent);
            } else { // Show existing checkout form
                $checkoutForm.show();
            }
        }
    }

    function loadCheckoutForm($checkoutContent) { // Clear existing content first
        $checkoutContent.empty();

        // Show loading message
        $checkoutContent.append('<div class="coderembassy-checkout-loading">Loading checkout form...</div>');

        // Make AJAX request to get checkout form
        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_get_checkout_form',
                nonce: coderembassyData.nonce
            },
            success: function (response) { // Remove loading message
                $checkoutContent.find('.coderembassy-checkout-loading').remove();
                if (response.success && response.data.checkout_form) {
                    $checkoutContent.html('<div class="coderembassy-checkout-form">' + response.data.checkout_form + '</div>');

                } else {
                    $checkoutContent.html('<div class="coderembassy-checkout-form"><p class="coderembassy-checkout-error">' + 'Unable to load checkout form. Please refresh the page.' + '</p></div>');
                }
            },
            error: function (xhr, status, error) { // Remove loading message
                $checkoutContent.find('.coderembassy-checkout-loading').remove();

                $checkoutContent.html('<div class="coderembassy-checkout-form"><p class="coderembassy-checkout-error">' + 'Failed to load checkout form. Please refresh the page.' + '</p></div>');
            }
        });
    }

    // Initialize on page load
    initExpressCheckout();

})(jQuery);
