<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Tatrapayplus_Gateway_Blocks_Support extends AbstractPaymentMethodType {
	protected $name = 'tatrapayplus';

	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
	}

	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-tatrapayplus-blocks-integration',
			plugin_dir_url( __DIR__ ) . 'build/index.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			TATRAPAYPLUS_VERSION,
			true
		);
		wp_set_script_translations( 'wc-tatrapayplus-blocks-integration', 'tatrapayplus', plugin_dir_path( __DIR__ ) . 'languages/' );

		return array( 'wc-tatrapayplus-blocks-integration' );
	}

	public function get_payment_method_data() {
		$enable_comfort_pay        = $this->get_setting( 'enable_comfort_pay', 'no' ) === 'yes' ? true : false;
		$available_payment_methods = $this->get_setting( 'available_payment_methods', array() );
		$without_paylater_message  = __( 'Pay securely through the tatrapay+ payment gateway with a payment card or bank transfer.', 'tatrapay-payment-gateway' );
		$paylater_message          = __( 'Pay securely using the tatrapay+ payment gateway with a payment card, bank transfer or the Na splátkyTB method.', 'tatrapay-payment-gateway' );

		return array(
			'title'                     => $this->get_setting( 'title' ),
			'description'               => $this->get_setting( 'description' ),
			'supports'                  => $this->get_setting( 'supports' ),
			'showSaveOption'            => $enable_comfort_pay,
			'showSavedCards'            => $enable_comfort_pay,
			'icons'                     => Tatrapayplus_Gateway::get_icons( $available_payment_methods ),
			'available_payment_methods' => $available_payment_methods,
			'without_paylater_message'  => $without_paylater_message,
			'paylater_message'          => $paylater_message,
		);
	}
}
