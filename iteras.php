<?php
/**
 * ITERAS
 *
 * @package   iteras
 * @author    ITERAS Team <team@iteras.dk>
 * @license   GPL-2.0+
 * @link      https://www.iteras.dk
 * @copyright 2024 ITERAS ApS
 *
 * @wordpress-plugin
 * Plugin Name:       ITERAS
 * Plugin URI:        https://app.iteras.dk
 * Description:       Integration with ITERAS, a cloud-based state-of-the-art system for managing subscriptions/memberships and payments.
 * Version:           1.7.0
 * Author:            ITERAS
 * Author URI:        https://www.iteras.dk
 * Text Domain:       iteras
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * Requires PHP:      4.0
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

$local_settings = plugin_dir_path( __FILE__ ) . 'local-settings.php';
if (file_exists($local_settings))
  include(plugin_dir_path( __FILE__ ) . 'local-settings.php');
else {
  define("ITERAS_BASE_URL", "https://app.iteras.dk");  
  define("ITERAS_DEBUG", false);
}
define("ITERAS_PLUGIN_PATH", plugin_dir_path( __FILE__ ));

require_once( plugin_dir_path( __FILE__ ) . 'includes/debug.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/common.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/iteras-public.php' );

register_activation_hook( __FILE__, array( 'Iteras', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Iteras', 'deactivate' ) );
register_uninstall_hook(__FILE__, array( 'Iteras', 'uninstall' ));

add_action( 'plugins_loaded', array( 'Iteras', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

// remove DOING_AJAX part to enable ajax
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

  require_once( plugin_dir_path( __FILE__ ) . 'admin/iteras-admin.php' );
  add_action( 'plugins_loaded', array( 'Iteras_Admin', 'get_instance' ) );

}
