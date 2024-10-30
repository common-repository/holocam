<?php
/**
 * Plugin Name: holocam.io
 * Plugin URI: http://warptec.com
 * Description: Panorama images upload for Holocam
 * Version: 1.1
 * Author: Warptec
 * Author URI: http://warptec.com
 * Text Domain: holocam
 * Domain Path: /lang
 * 
 */
if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}
 
require_once('posttype.php');
 
require_once('hc_attachmentbox.php');

require_once('apikey.php');

register_activation_hook( __FILE__, 'holocam_hc_install' );

 ?>