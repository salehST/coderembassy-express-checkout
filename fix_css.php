<?php
$path = 'c:/xampp/htdocs/wptest/wp-content/plugins/coderembassy-express-checkout/assets/css/frontend.css';
$content = file_get_contents($path);

// Fix common corruption patterns
$content = str_replace(' - ', '-', $content);
$content = str_replace(' : ', ': ', $content);
$content = str_replace(' px', 'px', $content);
$content = str_replace(' %', '%', $content);
$content = str_replace(' fr', 'fr', $content);
$content = str_replace(' ! important', '!important', $content);
$content = str_replace(' . ', '.', $content);
$content = str_replace(' {', ' {', $content);
$content = str_replace(' ;', ';', $content);
$content = str_replace(' }', '}', $content);
$content = str_replace(' ( ', '(', $content);
$content = str_replace(' ) ', ')', $content);
$content = str_replace(' / ', '/', $content);
$content = str_replace(' , ', ', ', $content);
$content = str_replace(' @ keyframes', '@keyframes', $content);
$content = preg_replace('/(\d) s /', '$1s ', $content);

// Final cleanup for some nested selectors that might have been mangled differently
$content = str_replace('.coderembassy-custom-layout.woocommerce-billing-fields', '.coderembassy-custom-layout .woocommerce-billing-fields', $content);
$content = str_replace('.coderembassy-custom-layout.woocommerce-shipping-fields', '.coderembassy-custom-layout .woocommerce-shipping-fields', $content);
$content = str_replace('.coderembassy-custom-layout.woocommerce-additional-fields', '.coderembassy-custom-layout .woocommerce-additional-fields', $content);
$content = str_replace('.coderembassy-custom-layout.woocommerce-checkout-review-order', '.coderembassy-custom-layout .woocommerce-checkout-review-order', $content);
$content = str_replace('.coderembassy-custom-layout.form-row', '.coderembassy-custom-layout .form-row', $content);
$content = str_replace('.coderembassy-custom-layout.col2-set', '.coderembassy-custom-layout .col2-set', $content);

file_put_contents($path, $content);
echo "Fixed CSS corruption\n";
