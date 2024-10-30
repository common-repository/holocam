<?php

if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

global $hc_db_version;
$hc_db_version = '1.0';

/**
 * Create table in db after plugin installation
 * set settings option 
 * @author Szilard
 * 
 */

function holocam_hc_install() {
	global $wpdb;
	global $hc_db_version;

	$table_name = $wpdb->prefix . 'hcapikey';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		apikey varchar(55) DEFAULT '' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";
  $query = "INSERT INTO `$table_name` (`id`, `apikey`) VALUES (NULL, '');";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'hc_db_version', $hc_db_version );
  
  $wpdb->query($query);
  
  $holocam_zipdel = 1;
  add_option( 'holocam_zipdel', $holocam_zipdel );
}

 ?>