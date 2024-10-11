<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\NumberUtil;
use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

class WC_Gateway_Shop_As_Client extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'shop_as_client';
        $this->method_title = 'Shop as Client';
        $this->method_description = 'This is a special payment method only for administrators.';
        $this->has_fields = false; // Không có custom fields

        $this->init_form_fields(); // Khởi tạo form settings
        $this->init_settings();    // Load settings

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    // Define how the payment method should be displayed in the block editor
    public function payment_fields()
    {
        // You can add any custom fields or text here if necessary
        // For example, displaying the description
        if ($this->description) {
            echo wp_kses_post(wpautop($this->description));
        }
    }

    // Add support for block-based checkout
    public function get_icon()
    {
        // Return an icon for this payment method (optional)
        return apply_filters('woocommerce_gateway_icon', '', $this->id);
    }

    public function is_available()
    {
        $cookie_value = isset($_COOKIE['wpcsa_ambtalk']) ? $_COOKIE['wpcsa_ambtalk'] : '';

        if (!$cookie_value) {
            return false;
        }
        if (!is_user_logged_in()) {
            return false;
        }

        if (!current_user_can('administrator') && !$cookie_value) {
            return false;
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $order->update_status( 'pending', __( 'Order is waiting for payment completion.', 'woocommerce' ) );

        // Redirect to the thank you page
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    // Generate the fake checkout URL
    public function get_fake_checkout_url($order)
    {
        return home_url('/fake-checkout/') . '?order_id=' . $order->get_id();
    }

    // Admin form fields
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Shop as Client Payment',
                'default' => 'no',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Shop as Client',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'You are shopping on behalf of a client.',
            ),
        );
    }
}