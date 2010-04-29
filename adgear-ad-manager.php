<?php
/*
 * Plugin Name: AdGear WP Plugin
 * Plugin URI: http://github.com/bloom/adgear-wp-plugin
 * Description: Server AdGear ads through your blog
 * Version: 0.1-alpha
 * Author: Bloom Digital Platforms
 * Author URI: http://github.com/bloom/adgear-wp-plugin
 * License: GPL2
 * */
?>
<?php
/*  Copyright 2010  Bloom Digital Platforms  ( adgear-wp@bloomdigital.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
error_reporting( E_ALL );

adgear_init();

function adgear_init() {
	define( 'ADGEAR_PATH', dirname( __FILE__ ) );
	define( 'ADGEAR_URL', get_bloginfo( 'wpurl' ) . '/wp-content/plugins/adgear-wp-plugin' );

  if ( is_admin() ) {
    add_action('admin_menu', 'adgear_create_menu');
  }
}

function adgear_create_menu() {
  add_submenu_page( 'options-general.php', 'AdGear Settings', 'AdGear Settings', 'administrator', __FILE__, 'adgear_settings_page' );
  add_action( 'admin_init', 'adgear_register_settings' );
}

function adgear_register_settings() {
  register_setting( 'adgear-settings-group', 'adgear_api_username' );
  register_setting( 'adgear-settings-group', 'adgear_api_key' );
  register_setting( 'adgear-settings-group', 'adgear_api_root_url' );
}

function adgear_settings_page() {
?>
<div class="wrap">
<h2>AdGear Settings</h2>

<form method="post" action="options.php">
  <?php settings_fields( 'adgear-settings-group' ); ?>
  <table class="form-table">
    <tr valign="top">
      <th scope="row">API Username</th>
      <td><input type="text" name="adgear_api_username" value="<?php echo get_option('adgear_api_username', ''); ?>" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">API Digest Key</th>
      <td><input type="text" name="adgear_api_key" size="68" value="<?php echo get_option('adgear_api_key', ''); ?>" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">API Root URL</th>
      <td><input type="text" name="adgear_api_root_url" size="40" value="<?php echo get_option('adgear_api_root_url', 'http://api.admin.adgear.com/'); ?>" /></td>
    </tr>
  </table>
  <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  </p>
</form>
</div>
<?php } ?>
