<?php
/*
Plugin Name: Kiyoh reviews
Plugin URI: https://dutchwebdesign.nl/
Description: Dutchwebdesign Kiyoh reviews plugin
Version: 0.1.2
Author: Dutchwebdesign
Author URI: https://dutchwebdesign.nl/
Text Domain: dwd
*/

define('KIYOH__PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once(KIYOH__PLUGIN_DIR . 'class.kiyoh_company_widget.php');
require_once(KIYOH__PLUGIN_DIR . 'class.kiyoh.php');

register_activation_hook(__FILE__, array('Kiyoh', 'plugin_activation'));

add_action('init', array('Kiyoh', 'init'));
add_action('init', array('Kiyoh', 'init_widgets'), 0);