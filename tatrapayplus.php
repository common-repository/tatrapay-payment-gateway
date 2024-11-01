<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.tatrabanka.sk/sk/business/ucty-platby/prijimanie-platieb/tatrapay-plus/
 * @since             1.0
 * @package           Tatrapayplus
 *
 * @wordpress-plugin
 * Plugin Name:       tatrapay+ Payment Gateway
 * Plugin URI:        https://www.tatrabanka.sk/sk/business/ucty-platby/prijimanie-platieb/tatrapay-plus/
 * Description:       Latest payment processing solution from Tatrabanka. Accept Pay Later, credit/debit cards and bank accounts.
 * Version:           1.0.10
 * Author:            devtatrabanka
 * Author URI:        https://www.tatrabanka.sk/sk/personal/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tatrapay-payment-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TATRAPAYPLUS_VERSION', '1.0.10' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-tatrapayplus.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function tatrapayplus_run() {

	$plugin = new Tatrapayplus();
	$plugin->run();
}

tatrapayplus_run();
