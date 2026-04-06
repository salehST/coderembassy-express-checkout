/**
 * CoderEmbassy Express Checkout Frontend JavaScript
 */

(function ($) {
    'use strict';

    // Catch payment gateway initialization errors and display them (like default WooCommerce checkout)
    // woo-stripe-payment catches Stripe() errors but only logs to console - never shows user.
    // We catch unhandled errors and inject notices so users get the same feedback as default checkout.
    var ceecPaymentErrorShown = false;
    var ceecPaymentErrorMessages = [];

    function ceecInjectPaymentErrorNotices(includeNoMethodsMsg) {
        if (!ceecPaymentErrorMessages.length || !$('.coderembassy-express-checkout').length || !$('#payment').length) return;
        var $existing = $('#payment .ceec-payment-error-notice');
        if ($existing.length) return;
        var notices = (typeof coderembassyData !== 'undefined' && coderembassyData.paymentNotices) ? coderembassyData.paymentNotices : {};
        var items = ceecPaymentErrorMessages.map(function (m) {
            return '<li>' + $('<div>').text(m).html() + '</li>';
        });
        var hasWorkingMethod = false;
        if (includeNoMethodsMsg !== false) {
            $('#payment .wc_payment_methods .wc_payment_method').each(function () {
                var $box = $(this).find('.payment_box');
                if (!$box.length) return;
                var hasContent = $box.find('iframe').length > 0 ||
                    $box.find('input[type="text"], input[type="tel"], .StripeElement, [id*="card-element"]').length > 0 ||
                    $box.text().trim().length > 30;
                if (hasContent) { hasWorkingMethod = true; return false; }
            });
        }
        if (includeNoMethodsMsg !== false && !hasWorkingMethod) {
            var noMethodsMsg = notices.noPaymentMethods || 'There are no payment methods available. Please contact us for help placing your order.';
            items.push('<li>' + $('<div>').text(noMethodsMsg).html() + '</li>');
        }
        var html = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout ceec-payment-error-notice"><ul class="woocommerce-error">' + items.join('') + '</ul></div>';
        $('#payment').prepend(html);
    }

    window.addEventListener('error', function (e) {
        if (ceecPaymentErrorShown) return;
        var msg = (e.message || '') + (e.error && e.error.message ? ' ' + e.error.message : '');
        var isPaymentError = /IntegrationError|publishable key|empty string|Cannot read properties of null.*elements|payment_method|stripe|wc-stripe/i.test(msg);
        if (!isPaymentError) return;
        if (!$('.coderembassy-express-checkout').length || !$('#payment').length) return;

        ceecPaymentErrorShown = true;
        var notices = (typeof coderembassyData !== 'undefined' && coderembassyData.paymentNotices) ? coderembassyData.paymentNotices : {};
        var noticeMsg = msg.indexOf('publishable key') > -1
            ? (notices.stripeKeyError || "There was an error registering the payment method with id 'stripe_cc': Error: Please call Stripe() with your publishable key. You used an empty string.")
            : msg;
        ceecPaymentErrorMessages = [noticeMsg];
        ceecInjectPaymentErrorNotices();
    });

    // Diagnostic logging - enable via ?ceec_debug=1 in URL, or window.CEEC_DEBUG = true in console before init
    var ceecDebugEnabled = (typeof window !== 'undefined' && (
        window.CEEC_DEBUG ||
        (typeof coderembassyData !== 'undefined' && coderembassyData.debug) ||
        (typeof window.location !== 'undefined' && window.location.search.indexOf('ceec_debug=1') !== -1)
    ));
    function ceecLog() {
        if (ceecDebugEnabled) {
            var args = ['[CEEC]'].concat(Array.prototype.slice.call(arguments));
            console.log.apply(console, args);
        }
    }
    function ceecWarn() {
        var args = ['[CEEC]'].concat(Array.prototype.slice.call(arguments));
        console.warn.apply(console, args);
    }
    function ceecError() {
        var args = ['[CEEC]'].concat(Array.prototype.slice.call(arguments));
        console.error.apply(console, args);
    }

    $(document).ready(function () {
        if (ceecDebugEnabled) {
            window.addEventListener('error', function (e) {
                ceecError('Global error:', e.message, e.filename, e.lineno, e.colno, e.error);
            });
            ceecLog('CEEC debug enabled. Add ?ceec_debug=1 to URL or set window.CEEC_DEBUG=true');
        }
        ceecBindCheckoutAddressRefresh();
        initExpressCheckout();
    });

    /**
     * When checkout lives inside the shortcode, country/state may use SelectWoo or load after
     * LiteSpeed-delayed scripts. Always fire Woo's checkout refresh on address changes.
     * Bound once on body (delegated).
     */
    function ceecBindCheckoutAddressRefresh() {
        if ($(document.body).data('ceec-address-refresh-bound')) {
            return;
        }
        $(document.body).data('ceec-address-refresh-bound', true);
        var selector = '.coderembassy-checkout-section form.checkout #billing_country, ' +
            '.coderembassy-checkout-section form.checkout #shipping_country, ' +
            '.coderembassy-checkout-section form.checkout #billing_state, ' +
            '.coderembassy-checkout-section form.checkout #shipping_state, ' +
            '.coderembassy-checkout-section form.checkout #billing_postcode, ' +
            '.coderembassy-checkout-section form.checkout #shipping_postcode, ' +
            '.coderembassy-checkout-section form.checkout #billing_city, ' +
            '.coderembassy-checkout-section form.checkout #shipping_city';
        $(document.body).on('change.ceec_checkout_address', selector, function () {
            ceecLog('Address field change, triggering update_checkout');
            $(document.body).trigger('update_checkout');
        });
    }

    /**
     * After checkout HTML is injected (AJAX), WooCommerce checkout.js may not have bound yet
     * (especially with deferred JS). Re-run init and refresh shipping methods.
     */
    function ceecReinitializeInjectedCheckout($checkoutContent) {
        var $form = $checkoutContent.find('form.checkout').first();
        if (!$form.length) {
            ceecWarn('ceecReinitializeInjectedCheckout: no form.checkout in container');
            return;
        }
        if ($.fn.selectWoo) {
            $form.find('select.country_to_state, select.state_select').each(function () {
                var $el = $(this);
                if (!$el.hasClass('enhanced')) {
                    $el.selectWoo();
                }
            });
        }
        $(document.body).trigger('init_checkout');
        $form.trigger('init_checkout');
        $(document.body).trigger('update_checkout');
    }

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

            // Initialize quantity +/- stepper buttons
            initQtyButtons($container);

            // Sync product card state (checked + qty) from WooCommerce cart on page load
            syncCartStateOnLoad($container);

            // Prefer checkout form inside our shortcode section (avoid wrong form if theme adds another).
            var $checkoutForm = $('.coderembassy-checkout-section form.checkout').first();
            if (!$checkoutForm.length) {
                $checkoutForm = $('form.checkout').first();
            }
            ceecLog('Checkout init: form.checkout found=', $checkoutForm.length, 'is_checkout=', typeof wc_checkout_params !== 'undefined');
            if ($checkoutForm.length) {
                // Listen for checkout events to debug payment section
                $(document.body).on('init_checkout updated_checkout', function () {
                    ceecLog('Checkout event:', this.type || 'unknown');
                    ceecProbePaymentSection();
                    if (ceecPaymentErrorShown) {
                        setTimeout(ceecInjectPaymentErrorNotices, 50);
                    }
                });
                setTimeout(function () {
                    ceecLog('Triggering init_checkout and update_checkout');
                    $(document.body).trigger('init_checkout');
                    $checkoutForm.trigger('update_checkout');
                    var $selectedMethod = $checkoutForm.find('input[name="payment_method"]:checked');
                    ceecLog('Selected payment method:', $selectedMethod.length ? $selectedMethod.val() : 'none');
                    if ($selectedMethod.length) {
                        $selectedMethod.trigger('click').trigger('change');
                    }
                    // Probe after a delay to see if Stripe/gateway mounted
                    setTimeout(ceecProbePaymentSection, 500);
                    setTimeout(ceecProbePaymentSection, 2000);

                    // If Stripe card fields haven't mounted after 1.5s, trigger update_checkout retry
                    setTimeout(function () {
                        var $stripeCc = $('#payment .payment_method_stripe_cc .payment_box');
                        if ($stripeCc.length && $stripeCc.find('iframe').length === 0 && $checkoutForm.length) {
                            ceecLog('Stripe iframes missing, triggering update_checkout retry');
                            $(document.body).trigger('update_checkout');
                        }
                    }, 1500);

                    // After 2.5s, trigger update_checkout to refresh PayPal, Google Pay, MobilePay.
                    // These gateways render buttons via JS; a delayed refresh gives scripts time to load.
                    setTimeout(function () {
                        var hasAltMethods = $('#payment .payment_method_ppcp, #payment .payment_method_ppcp_googlepay, #payment .payment_method_stripe_mobilepay, #payment .payment_method_stripe_googlepay').length > 0;
                        if (hasAltMethods && $checkoutForm.length && !$('body').hasClass('processing')) {
                            ceecLog('Triggering update_checkout for PayPal/Google Pay/MobilePay');
                            $(document.body).trigger('update_checkout');
                        }
                    }, 2500);

                    // Fallback: if payment fields never mount (e.g. gateway init failed), show notice
                    setTimeout(function () {
                        ceecShowPaymentInitErrorIfNeeded();
                    }, 3000);
                }, 50);
            } else {
                ceecWarn('Checkout form not found - payment section may not load');
            }
        });
    }

    /**
     * If a payment method (e.g. Stripe) failed to mount its input fields, show a notice.
     * Only runs when NO payment methods are working - not when PayPal/Google Pay etc. work.
     */
    function ceecShowPaymentInitErrorIfNeeded() {
        if (ceecPaymentErrorShown) return;
        if (!$('.coderembassy-express-checkout').length || !$('#payment').length) return;

        var $methods = $('#payment .wc_payment_methods .wc_payment_method');
        var hasWorkingMethod = false;
        var cardMethodFailed = false;

        $methods.each(function () {
            var $li = $(this);
            var id = $li.find('input[name="payment_method"]').val() || '';
            var $box = $li.find('.payment_box');
            if (!$box.length) return;
            var $iframes = $box.find('iframe');
            var hasInputs = $box.find('input[type="text"], input[type="tel"], .StripeElement, [id*="card-element"]').length > 0;
            var boxText = $box.text().trim();
            var hasContent = $iframes.length > 0 || hasInputs || boxText.length > 30;

            if (hasContent && !/stripe_cc|stripe_mobilepay/i.test(id)) {
                hasWorkingMethod = true;
            }
            if (/stripe_cc|stripe_mobilepay/i.test(id) && !$iframes.length && !hasInputs && boxText.length < 80) {
                cardMethodFailed = true;
            }
        });

        if (!cardMethodFailed || hasWorkingMethod) return;

        ceecPaymentErrorShown = true;
        var notices = (typeof coderembassyData !== 'undefined' && coderembassyData.paymentNotices) ? coderembassyData.paymentNotices : {};
        var msg = notices.stripeKeyError || "There was an error registering the payment method with id 'stripe_cc': Error: Please call Stripe() with your publishable key. You used an empty string.";
        ceecPaymentErrorMessages = [msg];
        ceecInjectPaymentErrorNotices();
    }

    /**
     * Probe payment section for debugging - logs DOM state of Stripe/gateway elements
     */
    function ceecProbePaymentSection() {
        if (!ceecDebugEnabled) return;
        try {
            var $payment = $('#payment');
            var $methods = $('.wc_payment_methods .wc_payment_method');
            var info = {
                paymentDiv: $payment.length,
                paymentMethods: $methods.length,
                methods: {},
                scripts: {
                    wc_checkout_params: typeof wc_checkout_params !== 'undefined',
                    Stripe: typeof Stripe !== 'undefined',
                    jQuery: typeof $ !== 'undefined'
                }
            };
            $methods.each(function () {
                var $li = $(this);
                var id = $li.find('input[name="payment_method"]').val() || 'unknown';
                var $box = $li.find('.payment_box');
                var $stripeEl = $box.find('#wc-stripe-card-element, .wc-stripe-card-element, [id*="stripe"], [class*="stripe"]');
                var $iframes = $box.find('iframe');
                info.methods[id] = {
                    paymentBox: $box.length,
                    stripeElements: $stripeEl.length,
                    iframes: $iframes.length,
                    boxHasContent: $box.length ? $box.text().trim().length > 0 : false
                };
            });
            ceecLog('Payment section probe:', JSON.stringify(info, null, 2));
        } catch (err) {
            ceecError('Probe error:', err);
        }
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

            if ($input.is(':checked')) { // For radio: check if another product is already selected/in-cart
                if (isRadio && (ajaxCart || quickCart)) {
                    var $prevSelected = $container.find('.coderembassy-product-item.selected').not($productItem);
                    if ($prevSelected.length > 0) {
                        var prevName = $prevSelected.find('.coderembassy-product-title, .coderembassy-product-name, h3, h4').first().text().trim() || 'the selected product';
                        var newName = $productItem.find('.coderembassy-product-title, .coderembassy-product-name, h3, h4').first().text().trim() || 'this product';

                        showCustomConfirm('"' + prevName + '" is already in your cart.\n\nSelecting "' + newName + '" will remove it from the cart.\n\nDo you want to continue?', function () {
                            // onConfirm
                            // Remove old product from cart first, then add new
                            var prevProductId = $prevSelected.find('input[data-product-id]').data('product-id');
                            $prevSelected.removeClass('selected');
                            $prevSelected.find('.coderembassy-product-quantity').removeClass('active');
                            $prevSelected.find('.coderembassy-product-radio-input').prop('checked', false);

                            // Apply new selection visually immediately
                            $productItem.addClass('selected');
                            $productItem.find('.coderembassy-product-quantity').addClass('active');
                            $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                            $container.find('.coderembassy-product-item').not($productItem).find('.coderembassy-product-quantity').removeClass('active');
                            $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                            updateAddToCartButton($container);

                            removeFromCartAjax($container, prevProductId, function () {
                                if (quickCart && $input.is(':checked')) {
                                    var productData = getProductData($productItem, productId);
                                    addToCartQuick($container, [productData], $productItem);
                                }
                            });
                        }, function () {
                            // onCancel
                            // User cancelled: restore previous radio
                            $input.prop('checked', false);
                            updateAddToCartButton($container);
                        });
                        return; // Stop here, wait for callback
                    }
                }

                $productItem.addClass('selected');
                $productItem.find('.coderembassy-product-quantity').addClass('active');

                // For radio buttons, unselect all other products (visual only — cart already handled above)
                if (isRadio) {
                    $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                    $container.find('.coderembassy-product-item').not($productItem).find('.coderembassy-product-quantity').removeClass('active');
                    $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                }

                // Add to cart based on mode
                if (quickCart) {
                    var productData = getProductData($productItem, productId);
                    addToCartQuick($container, [productData], $productItem);
                }

            } else {
                $productItem.removeClass('selected');
                $productItem.find('.coderembassy-product-quantity').removeClass('active');

                if (ajaxCart || quickCart) {
                    removeFromCartAjax($container, productId);
                }
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

            // Don't trigger if clicking inside the quantity stepper area
            if ($(e.target).closest('.coderembassy-product-quantity, .coderembassy-qty-controls').length > 0) {
                return;
            }

            var $productItem = $(this);
            var $input = $productItem.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input');
            var productId = $input.length ? $input.data('product-id') : null;
            var isRadio = $input.is('input[type="radio"]');

            // Don't allow clicking if product input is disabled (e.g. variations not selected)
            if (!$input.length || $input.prop('disabled')) {
                return;
            }

            if (productId) {

                if ($input.is(':checked')) { // Already checked — uncheck (radios shouldn't toggle off on re-click, checkboxes can)
                    if (! isRadio) {
                        $input.prop('checked', false);
                        $productItem.removeClass('selected');
                        $productItem.find('.coderembassy-product-quantity').removeClass('active');
                        if (ajaxCart || quickCart) {
                            removeFromCartAjax($container, productId);
                        }
                        updateAddToCartButton($container);
                    }
                    // Radio: clicking the already-selected item does nothing
                } else { // Not yet checked — for radio with existing cart item, confirm swap
                    if (isRadio && (ajaxCart || quickCart)) {
                        var $prevSelected = $container.find('.coderembassy-product-item.selected').not($productItem);
                        if ($prevSelected.length > 0) {
                            var prevName = $prevSelected.find('.coderembassy-product-title, .coderembassy-product-name, h3, h4').first().text().trim() || 'the selected product';
                            var newName = $productItem.find('.coderembassy-product-title, .coderembassy-product-name, h3, h4').first().text().trim() || 'this product';

                            showCustomConfirm('"' + prevName + '" is already in your cart.\n\nSelecting "' + newName + '" will remove it from the cart.\n\nDo you want to continue?', function () { // onConfirm
                                var prevProductId = $prevSelected.find('input[data-product-id]').data('product-id');
                                $prevSelected.removeClass('selected');
                                $prevSelected.find('.coderembassy-product-quantity').removeClass('active');
                                $prevSelected.find('.coderembassy-product-radio-input').prop('checked', false);

                                // Select the new product visually
                                $input.prop('checked', true);
                                $productItem.addClass('selected');
                                $productItem.find('.coderembassy-product-quantity').addClass('active');
                                $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                                $container.find('.coderembassy-product-item').not($productItem).find('.coderembassy-product-quantity').removeClass('active');
                                $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                                updateAddToCartButton($container);

                                removeFromCartAjax($container, prevProductId, function () {
                                    if (quickCart && $input.is(':checked')) {
                                        var productData = getProductData($productItem, productId);
                                        addToCartQuick($container, [productData], $productItem);
                                    }
                                });
                            }, function () {
                                // onCancel
                                // do nothing
                            });
                            return; // Stop here, wait for callback
                        }
                    }

                    // Select the new product
                    $input.prop('checked', true);
                    $productItem.addClass('selected');
                    $productItem.find('.coderembassy-product-quantity').addClass('active');

                    // For radio buttons, unselect all other products visually
                    if (isRadio) {
                        $container.find('.coderembassy-product-item').not($productItem).removeClass('selected');
                        $container.find('.coderembassy-product-item').not($productItem).find('.coderembassy-product-quantity').removeClass('active');
                        $container.find('.coderembassy-product-radio-input').not($input).prop('checked', false);
                    }

                    // Add to cart based on mode
                    if (quickCart) {
                        var productData = getProductData($productItem, productId);
                        addToCartQuick($container, [productData], $productItem);
                    }

                    updateAddToCartButton($container);
                }
            }
        });
    }


    function initVariationHandlers($container) { // Initialize variation state for products with variations
        $container.find('.coderembassy-product-item.has-variations').each(function () {
            var $productItem = $(this);
            var $variationGroup = $productItem.find('.coderembassy-product-variations');
            var $productInput = $productItem.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input');

            // Enable all variation radio buttons
            $variationGroup.find('input[type="radio"]').prop('disabled', false);

            // Initially disable only the product selector for variable products
            $productInput.prop('disabled', true);
            $productItem.addClass('variation-incomplete');
        });

        // Handle variation selection with state preservation
        $container.find('.coderembassy-variation-option input[type="radio"]').on('change', function () {
            var $variationGroup = $(this).closest('.coderembassy-product-variations');
            var productId = $variationGroup.data('product-id');
            var $productItem = $variationGroup.closest('.coderembassy-product-item');
            var $productInput = $productItem.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input');
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
                $productInput.prop('disabled', false);
                $productItem.removeClass('variation-incomplete');

                // Store variation attributes on product selector (backend will find the variation ID)
                var variationAttributes = {};
                $variationGroup.find('input[type="radio"]:checked').each(function () {
                    var attribute = $(this).data('attribute');
                    var value = $(this).val();
                    variationAttributes[attribute] = value;
                });

                if (Object.keys(variationAttributes).length > 0) {
                    $productInput.data('variation-attributes', variationAttributes);
                }

                // Auto-select the product when all variations are chosen
                $productInput.prop('checked', true);
                $productItem.addClass('selected auto-selected');
                $productItem.find('.coderembassy-product-quantity').addClass('active');

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
                $productInput.prop('disabled', true);
                $productItem.addClass('variation-incomplete');
                $productInput.data('variation-id', null);
                $productInput.data('variation-attributes', null);

                // Uncheck the product if not all variations are selected
                $productInput.prop('checked', false);
                $productItem.removeClass('selected');
                $productItem.find('.coderembassy-product-quantity').removeClass('active');

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

    function initQtyButtons($container) { // Use event delegation so it works even if product cards are re-rendered
        $container.off('click.coderembassy-qty');

        $container.on('click.coderembassy-qty', '.coderembassy-qty-plus, .coderembassy-qty-minus', function (e) {
            e.preventDefault();
            e.stopPropagation(); // Don't bubble up to product item click handler

            var $btn = $(this);
            var $qtyInput = $btn.closest('.coderembassy-qty-controls').find('.coderembassy-qty-input');
            var currentVal = parseInt($qtyInput.val(), 10) || 1;
            var max = parseInt($qtyInput.attr('max'), 10) || 99;
            var min = parseInt($qtyInput.attr('min'), 10) || 1;

            if ($btn.hasClass('coderembassy-qty-plus')) {
                if (currentVal < max) {
                    $qtyInput.val(currentVal + 1);
                }
            } else {
                if (currentVal > min) {
                    $qtyInput.val(currentVal - 1);
                }
            }

            // If the product is already selected (in cart), update the cart quantity
            var $productItem = $btn.closest('.coderembassy-product-item');
            var isSelected = $productItem.hasClass('selected');
            var ajaxCart = $container.data('ajax-cart') == '1' || $container.data('ajax-cart') === 1;
            var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;

            if (isSelected && (ajaxCart || quickCart)) {
                var productId = $productItem.find('input[data-product-id]').data('product-id');
                var newQty = parseInt($qtyInput.val(), 10) || 1;
                // Debounce so rapid clicks only fire one request
                clearTimeout($qtyInput.data('coderembassy-qty-timer'));
                $qtyInput.data('coderembassy-qty-timer', setTimeout(function () {
                    updateCartQuantityAjax($container, productId, newQty);
                }, 350));
            }
        });

        // Prevent typing in quantity field from bubbling to product item click handler
        $container.on('click.coderembassy-qty', '.coderembassy-qty-input', function (e) {
            e.stopPropagation();
        });

        // Sanitize typed quantity values on change; also update cart if selected
        $container.on('change.coderembassy-qty', '.coderembassy-qty-input', function () {
            var $input = $(this);
            var val = parseInt($input.val(), 10);
            var max = parseInt($input.attr('max'), 10) || 99;
            var min = parseInt($input.attr('min'), 10) || 1;
            if (isNaN(val) || val < min) {
                $input.val(min);
                val = min;
            } else if (val > max) {
                $input.val(max);
                val = max;
            }

            var $productItem = $input.closest('.coderembassy-product-item');
            var isSelected = $productItem.hasClass('selected');
            var ajaxCart = $container.data('ajax-cart') == '1' || $container.data('ajax-cart') === 1;
            var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;

            if (isSelected && (ajaxCart || quickCart)) {
                var productId = $productItem.find('input[data-product-id]').data('product-id');
                updateCartQuantityAjax($container, productId, val);
            }
        });
    }

    /**
     * Update the quantity of a product already in the cart.
     */
    function updateCartQuantityAjax($container, productId, quantity) {
        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_update_cart_quantity',
                product_id: productId,
                quantity: quantity,
                nonce: coderembassyData.nonce
            },
            success: function (response) {
                if (response.success) { // Refresh mini-cart count
                    if (response.data.cart_count !== undefined) {
                        $('.cart-contents-count, .cart-count, .woocommerce-cart-count').text(response.data.cart_count);
                    }
                    // Reload checkout section to reflect updated totals
                    showCheckoutSection($container);
                    // Trigger WooCommerce fragment refresh
                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('updated_wc_div');
                }
            }
        });
    }

    /**
     * On page load: fetch current cart contents and sync product card state.
     * - Marks matching product cards as selected (checked).
     * - Sets their qty input to the cart quantity.
     * - Shows the checkout section if any in-cart products are found.
     */
    function syncCartStateOnLoad($container) {
        var ajaxCart = $container.data('ajax-cart') == '1' || $container.data('ajax-cart') === 1;
        var quickCart = $container.data('quick-cart') == '1' || $container.data('quick-cart') === 1;

        // Only sync in AJAX or Quick Cart mode
        if (! ajaxCart && ! quickCart) {
            return;
        }

        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_get_cart_contents',
                nonce: coderembassyData.nonce
            },
            success: function (response) {
                if (! response.success || ! response.data.items) {
                    return;
                }

                var cartItems = response.data.items; // { "product_id": quantity, ... }
                var hasCartItems = Object.keys(cartItems).length > 0;

                if (! hasCartItems) {
                    return;
                }

                // Walk each product card and sync state
                $container.find('.coderembassy-product-item').each(function () {
                    var $item = $(this);
                    var $input = $item.find('input[data-product-id]');
                    var productId = String($input.data('product-id'));

                    if (cartItems.hasOwnProperty(productId)) {
                        var qty = cartItems[productId];

                        // Mark as selected
                        $input.prop('checked', true);
                        $item.addClass('selected');
                        $item.find('.coderembassy-product-quantity').addClass('active');

                        // Set qty input to match cart
                        $item.find('.coderembassy-qty-input').val(qty);
                    }
                });

                // Update the add-to-cart button state
                updateAddToCartButton($container);

                // Show checkout section if cart has items
                showCheckoutSection($container);
            }
        });
    }


    function getProductData($productItem, productId) {
        // Use product selector input only (not variation radios)
        var $productInput = $productItem.find('.coderembassy-product-checkbox-input, .coderembassy-product-radio-input');
        var variationAttributes = $productInput.length ? $productInput.data('variation-attributes') : null;
        var quantity = parseInt($productItem.find('.coderembassy-qty-input').val(), 10) || 1;

        // For variable products, ensure variation from current UI if not on input (e.g. after cart restore)
        if ($productItem.hasClass('has-variations')) {
            var $variationGroup = $productItem.find('.coderembassy-product-variations');
            if ($variationGroup.length) {
                var fromRadios = {};
                $variationGroup.find('input[type="radio"]:checked').each(function () {
                    var attr = $(this).data('attribute');
                    if (attr) {
                        fromRadios[attr] = $(this).val();
                    }
                });
                if (Object.keys(fromRadios).length > 0) {
                    variationAttributes = fromRadios;
                    if ($productInput.length) {
                        $productInput.data('variation-attributes', fromRadios);
                    }
                }
            }
        }

        var productData = {
            product_id: productId,
            quantity: quantity
        };
        if (variationAttributes && Object.keys(variationAttributes).length > 0) {
            productData.variation = variationAttributes;
        }
        return productData;
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
                            if (response.success && response.data.checkout_form) {
                                var $checkoutContent = $container.find('.coderembassy-checkout-content');
                                var formHtml = '';

                                if (coderembassyData.checkoutLayoutStyle === 'custom') { // Custom 2-column layout requested
                                    formHtml = '<div class="coderembassy-checkout-form coderembassy-custom-layout">' + response.data.checkout_form + '</div>';
                                } else { // Default Theme layout: strictly raw output for native theme compatibility
                                    formHtml = '<div class="coderembassy-checkout-form">' + response.data.checkout_form + '</div>';
                                }
                                $checkoutContent.html(formHtml);
                                ceecReinitializeInjectedCheckout($checkoutContent);
                                $(document.body).trigger('updated_checkout');
                            }
                            if (typeof wc_cart_fragments_params !== 'undefined') { // Important: trigger added_to_cart with fragments so side carts open
                                if (response.data.cart_fragments && response.data.cart_hash) {
                                    $(document.body).trigger('added_to_cart', [response.data.cart_fragments, response.data.cart_hash, $button]);
                                } else {
                                    $(document.body).trigger('wc_fragment_refresh');
                                }
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
            }
        });
    }

    function addToCartQuick($container, productIds, $productItem) { // Add visual feedback
        $productItem.addClass('quick-cart-added');

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
            if (typeof productData === 'object' && productData.product_id) { // New format: {product_id: 49, quantity: 2, variation: {...}}
                $form.append('<input type="hidden" name="product_ids[]" value="' + productData.product_id + '">');

                // Add quantity
                var qty = productData.quantity || 1;
                $form.append('<input type="hidden" name="quantities[' + productData.product_id + ']" value="' + qty + '">');

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

        // Hide quantity inputs and reset to 1
        $container.find('.coderembassy-product-quantity').removeClass('active');
        $container.find('.coderembassy-qty-input').val(1);

        // Update add to cart button
        updateAddToCartButton($container);
    }

    /**
     * Remove a specific product from the WooCommerce cart via AJAX.
     * Called whenever a product is unchecked in AJAX or Quick Cart mode.
     */
    function removeFromCartAjax($container, productId, callback) {
        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'coderembassy_remove_from_cart',
                nonce: coderembassyData.nonce,
                product_id: productId
            },
            success: function (response) {
                if (response.success) { // Trigger WooCommerce specific events for side carts
                    if (response.data.cart_fragments && response.data.cart_hash) {
                        $(document.body).trigger('removed_from_cart', [response.data.cart_fragments, response.data.cart_hash, null]);
                    } else {
                        $(document.body).trigger('wc_fragment_refresh');
                    } $(document.body).trigger('updated_wc_div');

                    // Refresh the checkout section to reflect updated cart
                    var $checkoutSection = getCheckoutSection($container);
                    if ($checkoutSection.length && $checkoutSection.is(':visible')) {
                        var $checkoutContent = $checkoutSection.find('.coderembassy-checkout-content');
                        if (response.data.cart_count === 0) { // Cart is now empty — show empty state
                            $checkoutContent.html('<div class="coderembassy-empty-cart">' + (
                                coderembassyData.i18n.emptyCart || 'Your cart is empty.'
                            ) + '</div>');
                        } else { // Still items in cart — reload checkout form
                            loadCheckoutForm($checkoutContent);
                        }
                    }
                }
                if (typeof callback === 'function') {
                    callback(response);
                }
            },
            error: function (xhr, status, error) {
                if (typeof callback === 'function') {
                    callback({success: false, error: error});
                }
            }
        });
    }

    /**
     * Show custom confirmation modal string styling
     */
    function showCustomConfirm(message, onConfirm, onCancel) {
        var modalHtml = '<div class="coderembassy-confirm-modal-backdrop">' + '<div class="coderembassy-confirm-modal">' + '<div class="coderembassy-confirm-message">' + message.replace(/\n/g, '<br>') + '</div>' + '<div class="coderembassy-confirm-actions">' + '<button class="coderembassy-btn coderembassy-btn-secondary coderembassy-cancel-btn">' + (
            coderembassyData.i18n.cancelText || 'Cancel'
        ) + '</button>' + '<button class="coderembassy-btn coderembassy-btn-primary coderembassy-confirm-btn">' + (
            coderembassyData.i18n.confirmText || 'Yes, continue'
        ) + '</button>' + '</div>' + '</div>' + '</div>';

        var $modal = $(modalHtml).appendTo('body');

        $modal.hide().fadeIn(150);

        $modal.find('.coderembassy-cancel-btn').on('click', function (e) {
            e.preventDefault();
            $modal.fadeOut(150, function () {
                $(this).remove();
            });
            if (typeof onCancel === 'function') 
                onCancel();
            
        });

        $modal.find('.coderembassy-confirm-btn').on('click', function (e) {
            e.preventDefault();
            $modal.fadeOut(150, function () {
                $(this).remove();
            });
            if (typeof onConfirm === 'function') 
                onConfirm();
            
        });
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
        var $checkoutSection = getCheckoutSection($container);

        if ($checkoutSection.length > 0) { // Show the checkout section
            $checkoutSection.show();

            var $checkoutContent = $checkoutSection.find('.coderembassy-checkout-content');

            // Always reload the checkout form so it reflects the latest cart contents
            // (covers first load, subsequent adds, and quantity changes)
            loadCheckoutForm($checkoutContent);
        }
    }

    /**
     * Find the checkout section — it is rendered as a SIBLING of the
     * .coderembassy-express-checkout container, not a child of it.
     */
    function getCheckoutSection($container) { // 1. Try immediate next sibling
        var $section = $container.nextAll('.coderembassy-checkout-section').first();
        if ($section.length) 
            return $section;
        

        // 2. Try within the same parent (covers wrappers like <div class="entry-content">)
        $section = $container.parent().find('.coderembassy-checkout-section').first();
        if ($section.length) 
            return $section;
        

        // 3. Global fallback
        return $('.coderembassy-checkout-section').first();
    }

    /**
     * Initialize payment method visibility and ensure one method is selected.
     * Required when checkout form is injected via AJAX (WC checkout.js bound to the old form).
     */
    function ceecInitPaymentMethods($container) {
        var $payment_methods = $container.find('input[name="payment_method"]');
        if ($payment_methods.length === 0) {
            return;
        }
        if ($payment_methods.filter(':checked').length === 0) {
            $payment_methods.eq(0).prop('checked', true);
        }
        var checkedId = $payment_methods.filter(':checked').eq(0).attr('id');
        if (checkedId) {
            $container.find('div.payment_box').hide();
            $container.find('div.payment_box.' + checkedId).show();
        }
    }

    /**
     * Bind delegated payment method switch (once). Works for AJAX-injected checkout forms.
     */
    function ceecBindPaymentMethodSwitch() {
        if ($(document.body).data('ceec-payment-method-bound')) {
            return;
        }
        $(document.body).data('ceec-payment-method-bound', true);
        $(document.body).on('click.ceec_payment_method', '.coderembassy-checkout-content input[name="payment_method"]', function (e) {
            e.stopPropagation();
            var $input = $(this);
            var $container = $input.closest('.coderembassy-checkout-content');
            var targetId = $input.attr('id');
            var $targetBox = $container.find('div.payment_box.' + targetId);
            if ($input.is(':checked') && $targetBox.length) {
                $container.find('div.payment_box').filter(':visible').slideUp(230);
                $targetBox.slideDown(230);
            } else if ($container.find('.payment_methods input.input-radio').length <= 1) {
                $container.find('div.payment_box').show();
            }
            if ($input.data('order_button_text')) {
                $container.find('#place_order').text($input.data('order_button_text'));
            } else {
                var $placeOrder = $container.find('#place_order');
                if ($placeOrder.data('value')) {
                    $placeOrder.text($placeOrder.data('value'));
                }
            }
            $(document.body).trigger('payment_method_selected');
        });
    }

    function loadCheckoutForm($checkoutContent) { // Clear existing content first
        $checkoutContent.empty();

        // Show loading message
        $checkoutContent.append('<div class="coderembassy-checkout-loading">Loading checkout form...</div>');

        // Ensure delegated payment method handler is bound (once)
        ceecBindPaymentMethodSwitch();

        // Make AJAX request to get checkout form
        $.ajax({
            url: coderembassyData.ajaxUrl,
            type: 'POST',
            cache: false,
            data: {
                action: 'coderembassy_get_checkout_form',
                nonce: coderembassyData.nonce,
                _t: Date.now() // cache-buster
            },
            success: function (response) { // Remove loading message
                $checkoutContent.find('.coderembassy-checkout-loading').remove();
                if (response.success && response.data.checkout_form) {
                    $checkoutContent.html('<div class="coderembassy-checkout-form">' + response.data.checkout_form + '</div>');

                    // Re-initialize WooCommerce's shipping toggle so it works inside our container.
                    // WC normally handles this in checkout.js which runs on the native checkout page;
                    // because we inject the form via AJAX we must replicate it here.
                    var $shippingCheckbox = $checkoutContent.find('#ship-to-different-address-checkbox');
                    var $shippingAddress = $checkoutContent.find('.shipping_address');

                    // Apply correct initial visibility
                    if ($shippingCheckbox.length && $shippingAddress.length) {
                        if ($shippingCheckbox.is(':checked')) {
                            $shippingAddress.show();
                        } else {
                            $shippingAddress.hide();
                        }
                        // Toggle on change
                        $shippingCheckbox.off('change.wc_shipping').on('change.wc_shipping', function () {
                            if ($(this).is(':checked')) {
                                $shippingAddress.slideDown();
                            } else {
                                $shippingAddress.slideUp();
                            }
                        });
                    }

                    // Payment method visibility: WC checkout.js bound to original form, so re-init for AJAX-injected form
                    ceecInitPaymentMethods($checkoutContent);

                    ceecReinitializeInjectedCheckout($checkoutContent);
                    $(document.body).trigger('updated_checkout');

                } else {
                    var errMsg = (response.data && response.data.message) ? response.data.message : 'Unable to load checkout form. Please refresh the page.';
                    $checkoutContent.html('<div class="coderembassy-checkout-form"><p class="coderembassy-checkout-error">' + errMsg + '</p></div>');
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
