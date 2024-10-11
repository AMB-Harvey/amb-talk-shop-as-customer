<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_Shop_As_Client_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'shop_as_client';// your payment gateway name

    public function initialize()
    {
        $this->settings = get_option('woocommerce_shop_as_client_settings', []);

        $gateways = WC()->payment_gateways->payment_gateways();

        $this->gateway = $gateways['shop_as_client'];

        //$this->gateway = new WC_Gateway_Shop_As_Client();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-shop_as_client-blocks-integration',
            plugin_dir_url(__FILE__) . 'js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities'
            ],
            null,
            true
        );

        return ['wc-shop_as_client-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'id' => $this->gateway->id,
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }
}