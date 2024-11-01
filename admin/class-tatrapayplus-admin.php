<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.tatrabanka.sk/sk/business/ucty-platby/prijimanie-platieb/tatrapay-plus/
 * @since      1.0.0
 *
 * @package    Tatrapayplus
 * @subpackage Tatrapayplus/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Tatrapayplus
 * @subpackage Tatrapayplus/admin
 * @author     tatrapayplus <tatrapayplus@test.sk>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tatrapayplus_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tatrapayplus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tatrapayplus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tatrapayplus-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tatrapayplus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tatrapayplus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tatrapayplus-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function init_custom_gateway_class() {
		if ( class_exists( 'WooCommerce' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-tatrapayplus-gateway.php';
		}
	}

	public function add_custom_gateway_class( $methods ) {
		$methods[] = 'Tatrapayplus_Gateway';

		return $methods;
	}

	public function enable_gateway_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-tatrapayplus-gateway-blocks-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new Tatrapayplus_Gateway_Blocks_Support() );
				}
			);
		}
	}

	public function shop_paylater_button_in_detail() {
		global $product;
		$gateway_settings = get_option( 'woocommerce_tatrapayplus_settings' );
		$is_enabled       = isset( $gateway_settings['enabled'] ) ? $gateway_settings['enabled'] : 'no';
		$button_theme     = isset( $gateway_settings['button_theme'] ) ? $gateway_settings['button_theme'] : 'light';
		if ( $button_theme === 'disabled' ) {
			return;
		}

		$available_payment_methods = isset( $gateway_settings['available_payment_methods'] ) ? $gateway_settings['available_payment_methods'] : array();
		$is_supported              = \Tatrapayplus\TatrapayplusApiClient\TatraPayPlusService::is_currency_supported_for_specific_methods(
			$product->get_price(),
			get_woocommerce_currency(),
			$available_payment_methods,
			array(
				Tatrapayplus\TatrapayplusApiClient\Model\PaymentMethod::PAY_LATER,
			)
		);

		if ( $is_enabled && $is_supported ) {
			wp_enqueue_script(
				$this->plugin_name . 'nasplatky-button.js',
				'https://moja.tatrabanka.sk/ib-mfes/nasplatky-button/1.0.0/main.js',
				array(),
				$this->version,
				true
			);
			echo "<na-splatky-button style='display: block' theme='" . esc_attr( $button_theme ) . "' amount='" . esc_attr( $product->get_price() ) . "'></na-splatky-button>";
		}
	}

	public function shop_paylater_button_in_catalog() {
		global $product;
		if ( ! $product ) {
			return;
		}

		$gateway_settings = get_option( 'woocommerce_tatrapayplus_settings' );
		$is_enabled       = isset( $gateway_settings['enabled'] ) ? $gateway_settings['enabled'] : 'no';
		$button_theme     = isset( $gateway_settings['button_theme'] ) ? $gateway_settings['button_theme'] : 'light';
		if ( $button_theme === 'disabled' ) {
			return;
		}

		$available_payment_methods = isset( $gateway_settings['available_payment_methods'] ) ? $gateway_settings['available_payment_methods'] : array();
		$is_supported              = \Tatrapayplus\TatrapayplusApiClient\TatraPayPlusService::is_currency_supported_for_specific_methods(
			$product->get_price(),
			get_woocommerce_currency(),
			$available_payment_methods,
			array(
				Tatrapayplus\TatrapayplusApiClient\Model\PaymentMethod::PAY_LATER,
			)
		);
		if ( $is_enabled && $is_supported ) {
			$url = plugin_dir_url( __DIR__ ) . 'plugin_assets/images/' . 'paylater_on_' . $button_theme . '.svg';
			echo '<div style="width: 100%; text-align: center;" class="tatrapayplus-paylater"><img height="42" width="280" loading="lazy" src="' . esc_attr( $url ) . '"></div>';
		}
	}


	public function tatrapayplus_check_status() {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-tatrapayplus-gateway.php';
		$gateway = new Tatrapayplus_Gateway();
		$gateway->check_status_function();
	}
}
