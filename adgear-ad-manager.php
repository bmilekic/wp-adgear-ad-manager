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
    add_action('update_option_adgear_site_id', 'adgear_update_site_embed_code', 10, 2);
  } else {
    add_action('wp_head', 'adgear_output_site_embed_tag');
  }
}

function adgear_output_site_embed_tag() {
  $embed_code = get_option('adgear_site_embed_code');
  if ( !$embed_code ) return;

  echo $embed_code;
}

function adgear_update_site_embed_code($old_value, $new_value) {
  if ( $old_value == $new_value ) return;

  $sites = adgear_get_service_data( 'list_sites' );
  foreach( $sites["sites"] as $site ) {
    if ( $site["id"] == $new_value ) {
      update_option( 'adgear_site_embed_code', $site['embed_code'] );
      break;
    }
  }
}

/* Returns JSON decoded data from a call to the AdGear API.
 * If the configuration options are still blank, returns an empty array.
 */
function adgear_get_service_data( $service_name ) {
  $ch = curl_init();

  $username = get_option('adgear_api_username');
  if ($username == FALSE) return array();

  $password = get_option('adgear_api_key');
  $root_url = get_option('adgear_api_root_url');

  $timeout = 5;
  curl_setopt($ch, CURLOPT_URL, $root_url.".json");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

  $service_data = json_decode(curl_exec($ch), TRUE);

  $service_url = "";
  foreach( $service_data["_urls"] as $service ) {
    if ( $service["name"] == $service_name ) {
      $service_url = $service["url"];
    }
  }

  curl_setopt($ch, CURLOPT_URL, $service_url);
  $data = json_decode(curl_exec($ch), TRUE);

  curl_close($ch);
  return $data;
}

function adgear_create_menu() {
  add_submenu_page( 'options-general.php', 'AdGear Settings', 'AdGear Settings', 'administrator', __FILE__, 'adgear_settings_page' );
  add_action( 'admin_init', 'adgear_register_settings' );
}

function adgear_register_settings() {
  register_setting( 'adgear-settings-group', 'adgear_api_username' );
  register_setting( 'adgear-settings-group', 'adgear_api_key' );
  register_setting( 'adgear-settings-group', 'adgear_api_root_url' );
  register_setting( 'adgear-settings-group', 'adgear_site_id' );
  register_setting( 'adgear-settings-group', 'adgear_site_embed_code' );
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
    <tr valign="top">
      <th scope="row">AdGear Site</th>
      <td>
<?php
  if ( get_option('adgear_api_username') && get_option('adgear_api_key') && get_option('adgear_api_root_url') ) {
    /* API username set, so we presume we can talk to AdGear */
    $sites = adgear_get_service_data( 'list_sites' );
?>
        <select name="adgear_site_id">
<?php
    foreach($sites["sites"] as $site) {
?>
          <option value="<?php echo $site["id"]; ?>"<?php if ( $site["id"] == get_option('adgear_site_id') ) { echo ' selected="selected"'; } ?>><?php echo $site["name"]; ?></option>
<?php
    }
?>
        </select>
<?php
  } else {
    /* Configuration not set yet */
?>
        <p><?php _e('Set your AdGear credentials above, then save the settings to see which sites are already configured.') ?></p>
<?php
  }
?>
      </td>
    </tr>
  </table>
  <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  </p>
</form>
</div>
<?php } ?>
