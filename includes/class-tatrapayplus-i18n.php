<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.tatrabanka.sk/sk/business/ucty-platby/prijimanie-platieb/tatrapay-plus/
 * @since      1.0.0
 *
 * @package    Tatrapayplus
 * @subpackage Tatrapayplus/includes
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tatrapayplus_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'tatrapay-payment-gateway',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
