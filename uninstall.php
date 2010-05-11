<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) exit();

unregister_setting( 'adgear-settings-group', 'adgear_api_username' );
unregister_setting( 'adgear-settings-group', 'adgear_api_key' );
unregister_setting( 'adgear-settings-group', 'adgear_api_root_url' );
unregister_setting( 'adgear-settings-group', 'adgear_site_id' );

global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'adgear_%'");

?>
