<?php
/*
Plugin Name: Amoeba Talk create order as a Customer for WooCommerce
Description: Amoeba Talk create order as a Customer for WooCommerce as a Customer allows store administrators to login as a customer on the frontend.
Version: 1.2.6
Author: amoeba
Author URI: https://talk.amoeba.site/
Text Domain: Amoeba Talk create order as a Customer for WooCommerce
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.1
*/

defined('ABSPATH') || exit;

!defined('WPCSA_VERSION') && define('WPCSA_VERSION', '1.2.6');
!defined('WPCSA_LITE') && define('WPCSA_LITE', __FILE__);
!defined('WPCSA_FILE') && define('WPCSA_FILE', __FILE__);
!defined('WPCSA_URI') && define('WPCSA_URI', plugin_dir_url(__FILE__));
!defined('WPC_URI') && define('WPC_URI', WPCSA_URI);

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/hpos.php';

// Declare compatibility with High-Performance Order Storage (HPOS)
add_action('before_woocommerce_init', function () {
    if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true // true (compatible, default) or false (not compatible)
        );
    }
});

add_action('woocommerce_api_loaded', function () {
    include_once('class-wc-api-custom.php');
});


add_action('plugins_loaded', function () {
    load_plugin_textdomain('amb-talk-shop-as-customer', false, basename(__DIR__) . '/languages/');
    include_once('class-wc-amb-talk-shop-as-customer.php');

    return WPCleverWpcsa::instance();
});

add_filter('woocommerce_api_classes', function ($classes) {
    $classes[] = 'WC_API_Custom';
    return $classes;
});

if (!function_exists('WC_Gateway_Shop_As_Client')) {
    add_action('woocommerce_init', function () {
        if (!class_exists('WC_Payment_Gateway')) return;

        include_once('class-wc-gateway-shop-as-client.php');
    });
//
    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Gateway_Shop_As_Client';
        return $gateways;
    });

    add_filter('woocommerce_available_payment_gateways', function ($available_gateways) {
        error_log(print_r($available_gateways, true));
        return $available_gateways;
    });

}
//
//
add_action('plugins_loaded', function () {
    add_action('woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type');
});

/**
 * Custom function to register a payment method type
 */
function oawoo_register_order_approval_payment_method_type()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-payment-woocommerce-block-checkout.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Gateway_Shop_As_Client_Blocks());
        }
    );
}
