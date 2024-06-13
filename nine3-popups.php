<?php
// phpcs:ignorefile
/**
 * Plugin Name: 93digital Popup Maker
 * Plugin URI: https://93digital.co.uk/
 * Description: Creates a new post type and allows the user to create popups using a Gutenberg editor. Requires ACF.
 * Version: 1.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: 93digital
 * Author URI: https://93digital.co.uk/
 * License: GPLv2 or later
 * Text Domain: nine3popup
 *
 * @package nine3popup
 */

namespace nine3popup;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Base filepath and URL constants, without a trailing slash.
define( 'NINE3_POPUPS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'NINE3_POPUPS_URI', plugins_url( plugin_basename( __DIR__ ) ) );

/**
 * 'spl_autoload_register' callback function.
 * Autoloads all the required plugin classes, found in the /classes directory (relative to the plugin's root).
 *
 * @param string $class The name of the class being instantiated inculding its namespaces.
 */
function autoloader( $class ) {
	// $class returns the classname including any namespaces - this removes the namespace so we can locate the class's file.
	$raw_class = explode( '\\', $class );
	$filename  = str_replace( '_', '-', strtolower( end( $raw_class ) ) );

	$filepath = __DIR__ . '/class/class-' . $filename . '.php';

	if ( file_exists( $filepath ) ) {
		include_once $filepath;
	}
}
spl_autoload_register( __NAMESPACE__ . '\autoloader' );

/**
 * Init class.
 */
$nine3_popup = new Nine3_Popup();
