<?php 
/**
 * Plugin Name: DNS Program Scheduler
 * Description: Manage program schedules for Delaware Nature Society
 * Version: 1.1
 * Author: Ryan Leeson
 * Author URI: http://ryanleeson.com
 * License: GPL2
 */

require_once 'classes/program-administrator.php';
require_once 'classes/meta-boxes.php';
require_once 'classes/taxonomies.php';

define( 'CURRENCY_FORMAT', '/\b\d{1,3}(?:,?\d{3})*(?:\.\d{2})?\b/' );
define( 'DATE_FORMAT', '/^((0?[1-9]|1[012])[- \/\.](0?[1-9]|[12][0-9]|3[01])[- \/\.](19|20)?[0-9]{2})*$/' );
define( 'DNS_PROGRAM_CHARACTER_LIMIT', 500 );
define( 'DNS_PROGRAM_NUMBER_FORMAT', '/^[a-zA-Z0-9]{3}-[a-zA-Z0-9]{3}-[a-zA-Z0-9]{2}$/' );
define( 'DNS_PROGRAM_PLUGIN_VERSION', '1.1' );

function dns_enqueue_scripts() {
	wp_enqueue_script( 'jquery-validate', plugins_url( 'js/jquery.validate.min.js', __FILE__ ), 
		array( 'jquery' ) );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-timepicker', plugins_url( 'js/jquery.timepicker.min.js', __FILE__), 
		array( 'jquery' ) );
	wp_enqueue_script( 'dnsprograms-js', plugins_url( 'js/dnsprograms.js' , __FILE__ ), 
		array( 'jquery-validate' ), '1.0', true );
	wp_enqueue_style( 'dnsprograms-css', plugins_url( 'css/dnsprograms.css', __FILE__ ) );
	wp_enqueue_style( 'timepicker-css', plugins_url( 'css/jquery.timepicker.css', __FILE__ ) );
	wp_enqueue_style( 'jquery-ui-smoothness', plugins_url( 'css/jquery-ui-1.10.3.custom.min.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'dns_enqueue_scripts' );

global $dns_meta_boxes, $dns_taxonomies, $dns_program_admin;

$dns_meta_boxes = new dns_meta_boxes();
$dns_taxonomies = new dns_taxonomies();
$dns_program_admin = new dns_program_admin();
