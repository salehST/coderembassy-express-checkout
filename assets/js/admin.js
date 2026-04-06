/**
 * CoderEmbassy Express Checkout Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAdminTabs();
        initProductSelector();
        initDesignOptions();
        initMetaboxCopyButton();
        initColumnCopyButton();
        initFormSubmission();
        
        // Reinitialize Select2 after a short delay to fix sizing issues
        setTimeout(function() {
            if ($('#coderembassy_selected_products').length && !$('#coderembassy_selected_products').hasClass('select2-hidden-accessible')) {
                initProductSelector();
            }
        }, 100);
        
        // Handle window resize to fix Select2 sizing
        $(window).on('resize', function() {
            if ($('#coderembassy_selected_products').hasClass('select2-hidden-accessible')) {
                $('#coderembassy_selected_products').select2('destroy');
                setTimeout(function() {
                    initProductSelector();
                }, 50);
            }
        });
    });

    function initAdminTabs() {
        // Restore tab state on page load
        restoreTabState();
        
        // Handle both global tabs (settings page) and metabox tabs
        $('.coderembassy-tab-link').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var targetTab = $link.data('tab');
            var $container = $link.closest('.coderembassy-checkout-modification, .coderembassy-admin-container');
            
            // Update active tab link within the same container
            $container.find('.coderembassy-tab-link').removeClass('active');
            $link.addClass('active');
            
            // Update active tab content within the same container
            $container.find('.coderembassy-tab-content').removeClass('active');
            $container.find('#coderembassy-' + targetTab + '-tab').addClass('active');
            
            // Reinitialize Select2 when switching to settings tab (where product selector is)
            if (targetTab === 'settings') {
                setTimeout(function() {
                    if ($('#coderembassy_selected_products').length && !$('#coderembassy_selected_products').hasClass('select2-hidden-accessible')) {
                        initProductSelector();
                    }
                }, 100);
            }
            
            // Save tab state
            saveTabState($container, targetTab);
        });
    }
    
    function saveTabState($container, targetTab) {
        var containerId = $container.attr('id') || 'default';
        var key = 'coderembassy_tab_' + containerId;
        localStorage.setItem(key, targetTab);
        
        // Also save in URL parameter for page reloads
        var url = new URL(window.location);
        url.searchParams.set('active_tab', targetTab);
        window.history.replaceState({}, '', url);
    }
    
    function restoreTabState() {
        // Check URL parameter first
        var urlParams = new URLSearchParams(window.location.search);
        var activeTab = urlParams.get('active_tab');
        
        if (activeTab) {
            // Restore from URL parameter
            $('.coderembassy-tab-link[data-tab="' + activeTab + '"]').each(function() {
                var $link = $(this);
                var $container = $link.closest('.coderembassy-checkout-modification, .coderembassy-admin-container');
                var containerId = $container.attr('id') || 'default';
                
                // Update active tab link within the same container
                $container.find('.coderembassy-tab-link').removeClass('active');
                $link.addClass('active');
                
                // Update active tab content within the same container
                $container.find('.coderembassy-tab-content').removeClass('active');
                $container.find('#coderembassy-' + activeTab + '-tab').addClass('active');
            });
        } else {
            // Fallback to localStorage
            $('.coderembassy-checkout-modification, .coderembassy-admin-container').each(function() {
                var $container = $(this);
                var containerId = $container.attr('id') || 'default';
                var key = 'coderembassy_tab_' + containerId;
                var savedTab = localStorage.getItem(key);
                
                if (savedTab) {
                    var $link = $container.find('.coderembassy-tab-link[data-tab="' + savedTab + '"]');
                    if ($link.length) {
                        // Update active tab link within the same container
                        $container.find('.coderembassy-tab-link').removeClass('active');
                        $link.addClass('active');
                        
                        // Update active tab content within the same container
                        $container.find('.coderembassy-tab-content').removeClass('active');
                        $container.find('#coderembassy-' + savedTab + '-tab').addClass('active');
                    }
                }
            });
        }
    }
    
    function initFormSubmission() {
        // Handle form submission to preserve tab state
        $('form').on('submit', function() {
            // Save current tab state before form submission
            $('.coderembassy-tab-link.active').each(function() {
                var $link = $(this);
                var targetTab = $link.data('tab');
                var $container = $link.closest('.coderembassy-checkout-modification, .coderembassy-admin-container');
                saveTabState($container, targetTab);
            });
        });
    }

    function initProductSelector() {
        // Check if element exists
        if (!$('#coderembassy_selected_products').length) {
            return;
        }
        
        // Destroy existing Select2 if it exists
        if ($('#coderembassy_selected_products').hasClass('select2-hidden-accessible')) {
            $('#coderembassy_selected_products').select2('destroy');
        }
        
        // Initialize Select2 for product selection
        $('#coderembassy_selected_products').select2({
            ajax: {
                url: coderembassyAdminData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'coderembassy_search_products',
                        search: params.term || '',
                        page: params.page || 1,
                        nonce: coderembassyAdminData.nonce
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    
                    // Handle both success and error responses
                    if (data && data.success && data.data) {
                        return {
                            results: data.data.results || [],
                            pagination: {
                                more: data.data.pagination ? data.data.pagination.more : false
                            }
                        };
                    } else {
                        // Log error for debugging
                        if (data && data.data && data.data.message) {
                            console.error('Product search error:', data.data.message);
                        }
                        return {
                            results: [],
                            pagination: {
                                more: false
                            }
                        };
                    }
                },
                cache: true,
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error, xhr.responseText);
                    return {
                        results: [],
                        pagination: {
                            more: false
                        }
                    };
                }
            },
            placeholder: coderembassyAdminData.i18n.searchProducts,
            minimumInputLength: 3,
            allowClear: true,
            width: '100%',
            templateResult: formatProduct,
            templateSelection: formatProductSelection,
            escapeMarkup: function(markup) {
                return markup;
            },
            language: {
                inputTooShort: function() {
                    return 'Please enter at least 3 characters';
                },
                noResults: function() {
                    return 'No products found';
                },
                searching: function() {
                    return 'Searching...';
                }
            }
        });

        // Handle product selection change - no need for extra display since Select2 handles it
        // $('#coderembassy_selected_products').on('select2:select select2:unselect', function() {
        //     updateSelectedProductsDisplay();
        // });
    }

    function formatProduct(product) {
        if (product.loading) {
            return product.text;
        }

        // Simple product display with just the title
        return product.text;
    }

    function formatProductSelection(product) {
        return product.text;
    }

    function updateSelectedProductsDisplay() {
        var selectedProducts = $('#coderembassy_selected_products').val();
        var $display = $('.coderembassy-selected-products');
        
        if (!$display.length) {
            $display = $('<div class="coderembassy-selected-products"></div>');
            $('#coderembassy_selected_products').after($display);
        }
        
        $display.empty();
        
        if (selectedProducts && selectedProducts.length > 0) {
            selectedProducts.forEach(function(productId) {
                var $option = $('#coderembassy_selected_products option[value="' + productId + '"]');
                var productName = $option.text();
                
                var $productDiv = $(
                    '<div class="coderembassy-selected-product">' +
                        '<span>' + productName + '</span>' +
                        '<span class="remove-product" data-product-id="' + productId + '">×</span>' +
                    '</div>'
                );
                
                $display.append($productDiv);
            });
        }
    }

    // Handle product removal - Select2 handles this automatically
    // $(document).on('click', '.remove-product', function() {
    //     var productId = $(this).data('product-id');
    //     var $select = $('#coderembassy_selected_products');
    //     
    //     // Remove from select2
    //     $select.find('option[value="' + productId + '"]').remove();
    //     $select.trigger('change');
    //     
    //     // Update display
    //     updateSelectedProductsDisplay();
    // });

    function initDesignOptions() {
        // Initialize color pickers
        $('input[type="color"]').wpColorPicker({
            change: function(event, ui) {
                // Preview changes
                previewDesignChanges();
            }
        });

        // Initialize number inputs with units
        $('input[name*="width"], input[name*="height"], input[name*="font_size"]').on('input', function() {
            previewDesignChanges();
        });
    }

    function initMetaboxCopyButton() {
        // Copy button functionality
        $(document).on('click', '.coderembassy-copy-btn', function() {
            var $button = $(this);
            var $input = $button.siblings('.coderembassy-shortcode-input');
            var copiedText = $button.data('copied-text') || 'Copied!';
            
            // Select and copy text
            $input.select();
            $input[0].setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                var originalText = $button.text();
                $button.addClass('copied').text(copiedText);
                
                // Reset after 2 seconds
                setTimeout(function() {
                    $button.removeClass('copied').text(originalText);
                }, 2000);
                
            } catch (err) {
                console.error('Failed to copy text: ', err);
                alert('Failed to copy shortcode. Please select and copy manually.');
            }
        });
    }

    function previewDesignChanges() {
        // This function can be extended to show live preview
        // For now, it's a placeholder for future enhancements
    }

    // Handle form submission
    $('#post').on('submit', function() {
        // Validate required fields
        var selectedProducts = $('#coderembassy_selected_products').val();
        
        if (!selectedProducts || selectedProducts.length === 0) {
            alert('Please select at least one product.');
            $('.coderembassy-tab-link[data-tab="settings"]').click();
            $('#coderembassy_selected_products').focus();
            return false;
        }
    });

    // Initialize copy button functionality for columns
    function initColumnCopyButton() {
        $(document).on('click', '.coderembassy-copy-shortcode-btn', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var shortcode = $button.data('shortcode');
            
            // Create a temporary textarea to copy the shortcode
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                // Try to copy to clipboard
                var successful = document.execCommand('copy');
                
                if (successful) {
                    // Show success feedback
                    $button.addClass('copied');
                    $button.attr('title', 'Copied!');
                    
                    // Reset after 2 seconds
                    setTimeout(function() {
                        $button.removeClass('copied');
                        $button.attr('title', 'Copy shortcode');
                    }, 2000);
                } else {
                    // Fallback: show the shortcode in an alert
                    alert('Shortcode: ' + shortcode);
                }
            } catch (err) {
                // Fallback: show the shortcode in an alert
                alert('Shortcode: ' + shortcode);
            }
            
            $temp.remove();
        });
    }

    // Event delegation ensures these work even if elements are added dynamically
    // (though these specific ones are mostly for static elements in admin)

})(jQuery);
