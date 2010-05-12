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

add_action('admin_menu', 'adgear_create_menu');
add_action('update_option_adgear_site_id', 'adgear_update_site_embed_code', 10, 2);
add_action('update_option_adgear_site_id', 'adgear_update_formats_csv', 10, 2);

add_action('admin_menu', 'adgear_admin_menu');
add_filter('admin_print_scripts', 'adgear_admin_head');

function adgear_cleanup_obsolete_ad_spot_data() {
  $adspots = adgear_ad_spots();
  foreach( $adspots as $adspot ) {
    $key = 'adgear_adspot_embed_code_'. $adspot["id"];
    if ( $key != "" ) delete_option( $key );
  }
}

function adgear_update_formats_csv($old_value, $new_value) {
  $formats = adgear_get_service_data( 'list_formats' );
  $rows = array();
  foreach( $formats["formats"] as $format ) {
    $id       = $format["id"];
    $name     = $format["name"];
    $width    = $format["width"];
    $height   = $format["height"];

    $rows[] = "$name,$id,$width,$height";
  }

  natcasesort( $rows );
  update_option( 'adgear_formats_csv', implode( "\n", $rows ) );
}

function adgear_update_site_embed_code($old_value, $new_value) {
  if ( $old_value == $new_value ) return;

  $sites = adgear_get_service_data( 'list_sites' );

  foreach( $sites["sites"] as $site ) {
    if ( $site["id"] == $new_value ) {
      update_option( 'adgear_site_embed_code', $site["embed_code"] );

      adgear_cleanup_obsolete_ad_spot_data();

      if ( $site["dynamic"] ) {
        update_option( 'adgear_site_is_dynamic', TRUE );
        update_option( 'adgear_site_universal_embed_code', $site["universal_embed_code"] );
        delete_option( 'adgear_ad_spots_csv' );
      } else {
        update_option( 'adgear_site_is_dynamic', FALSE);
        delete_option( 'adgear_site_universal_embed_code' );

        foreach( $site["_urls"] as $service ) {
          if ( $service["name"] == "list_ad_spots" ) {
            $ad_spots = adgear_object_to_array( adgear_api_call( $service["url"] ) );
            $rows = array();

            foreach($ad_spots["ad_spots"] as $ad_spot) {
              $rows[] = implode( ",", array( $ad_spot['name'], $ad_spot['id'], $ad_spot['format_id'] ) );
              update_option( 'adgear_adspot_embed_code_'.$ad_spot['id'], $ad_spot['embed_code'] );
            }

            natcasesort( $rows );
            update_option( 'adgear_ad_spots_csv', implode( "\n", $rows ) );
            break;
          }
        }
      }

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

  $service_data = json_decode( curl_exec($ch), true );
  if ( is_null( $service_data ) ) {
    // TODO: Signal error condition somehow
    return array();
  }

  $service_data = adgear_object_to_array( $service_data );
  $service_url = "";
  foreach( $service_data["_urls"] as $service ) {
    if ( $service["name"] == $service_name ) {
      $service_url = $service["url"];
    }
  }

  curl_setopt($ch, CURLOPT_URL, $service_url);
  $data = json_decode(curl_exec($ch), true);
  curl_close($ch);

  return adgear_object_to_array( $data );
}

function adgear_object_to_array( $object ) {
  if ( is_string( $object ) || is_numeric( $object ) || is_null( $object ) || is_bool( $object ) ) {
    $array = $object;
  } else if ( is_array( $object ) || is_object( $object ) ) {
    $array = array();
    foreach( $object as $key => $value ) {
      $array[$key] = adgear_object_to_array( $value );
    }
  } else {
    echo "What have I got???\n";
    var_dump( $object );
  }

  return $array;
}

function adgear_api_call( $url ) {
  $ch = curl_init();

  $username = get_option('adgear_api_username');
  if ($username == FALSE) return array();

  $password = get_option('adgear_api_key');
  $root_url = get_option('adgear_api_root_url');

  $timeout = 5;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

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
}

function adgear_admin_menu() {
  // You have to add one to the "post" writing/editing page, and one to the "page" writing/editing page
  add_meta_box('adgear_meta_box', 'AdGear Ad Manager', 'adgear_meta_box_form', 'post', 'normal');
  add_meta_box('adgear_meta_box', 'AdGear Ad Manager', 'adgear_meta_box_form', 'page', 'normal');
}

function adgear_meta_box_form() {
?>
        <table id="adgear-meta" class="form-table adgear-meta">
<?php if ( adgear_is_dynamic_site() ) { ?>
            <tr valign="top">
                <th scope="row"><label for="adgear_format_id"><?php _e('Ad Format:')?></label></th>
                <td>
                  <?php adgear_format_selector_ui( array( 'id' => 'adgear_format_id', 'name' => 'adgear[format_id]', 'selected' => '', 'include_blank' => true )); ?>
                </td>
            </tr>
            <tr valign="top">
              <th scope="row"><label for="adgear_type"><?php _e('Path type:')?></label></th>
              <td>
                <?php adgear_path_type_selector_ui( array( 'id' => 'adgear_type', 'name' => 'adgear[type]', 'selected' => 'categories', 'path_id' => 'adgear_path', 'path_name' => 'adgear[path]', 'path_selected' => '' ) ); ?>
              </td>
            </tr>
            <tr valign="top">
              <th scope="row"><label for="adgear_slugify"><?php _e('Use post\'s slug in path:')?></label></th>
              <td>
                <?php adgear_slugify_selector_ui( array( 'id' => 'adgear_slugify', 'name' => 'adgear[slugify]', 'selected' => 'yes') ); ?>
              </td>
            </tr>
<?php } /* dynamic site */ else /* static site */ { ?>
            <tr valign="top">
                <th scope="row"><label for="adgear_adspot_id"><?php _e('Ad Spot:')?></label></th>
                <td>
                  <?php adgear_adspot_selector_ui( array( 'id' => 'adgear_adspot_id', 'name' => 'adgear[adspot_id]', 'selected' => '' )); ?>
                </td>
            </tr>
<?php } /* static site */ ?>
            <tr valign="top">
              <th scope="row"><label for="adgear_single"><?php _e('When to show this ad:')?></label></th>
              <td>
                <?php adgear_single_selector_ui( array( 'id' => 'adgear_single', 'name' => 'adgear[single]', 'selected' => 'all' )); ?>
              </td>
            </tr>
          <tr valign="top">
            <th scope="row"><label for="adgear_embed_code"><?php _e('Embed Code:')?></label></th>
            <td>
            <input type="text" id="adgear_embed_code" name="adgear[embed_code]" size="40" style="width:95%;" autocomplete="off" />
            </td>
          </tr>
        </table>
        <p class="submit">
          <input type="button" id="adgear_send_embed_code_to_editor" value="<?php _e('Send Embed Code to Editor &raquo;'); ?>" />
        </p>
        <div style="display:none;margin:0;padding:0">
          <input type="hidden" name="adgear[is_dynamic]" value="<?php echo adgear_is_dynamic_site(); ?>" id="adgear_site_is_dynamic"/>
        </div>
        <script type="text/javascript"><?php
// Correctly set a value in the embed code field
                    if ( adgear_is_dynamic_site() ) {
                      echo "adgearDynamicSiteChange";
                    } else {
                      echo "adgearStaticSiteChange";
                    } ?>(jQuery, "#adgear-meta");</script>
<?php
}

function adgear_admin_head () {
  $plugin_url = WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) );
  wp_enqueue_script('adgearAdmin', $plugin_url.'adgear-meta.js', array('jquery'), '1.0.0');
}

function adgear_settings_page() {
  $plugin_url = WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) );
?>
<div class="wrap">
<h2>AdGear <img height="40" alt="" src="<?php echo $plugin_url.'/../adgear-logo.png'; ?>"/> Settings</h2>
<?php
if ( function_exists( 'curl_init' ) ) {
?>
<form method="post" action="options.php">
  <?php settings_fields( 'adgear-settings-group' ); ?>
  <table class="form-table">
    <tr valign="top">
      <th scope="row"><?php echo _e('API Username'); ?></th>
      <td><input type="text" name="adgear_api_username" value="<?php echo get_option('adgear_api_username', ''); ?>" /></td>
    </tr>
    <tr valign="top">
      <th scope="row"><?php echo _e('API Digest Key'); ?></th>
      <td><input type="text" name="adgear_api_key" size="68" value="<?php echo get_option('adgear_api_key', ''); ?>" /></td>
    </tr>
    <tr valign="top">
      <th scope="row"><?php echo _e('API Root URL'); ?></th>
      <td><input type="text" name="adgear_api_root_url" size="40" value="<?php echo get_option('adgear_api_root_url', 'http://api.admin.adgear.com/'); ?>" /></td>
    </tr>
    <tr valign="top">
      <th scope="row"><?php echo _e('AdGear Site'); ?></th>
      <td>
<?php
  if ( get_option('adgear_api_username') && get_option('adgear_api_key') && get_option('adgear_api_root_url') ) {
    /* API username set, so we presume we can talk to AdGear */
    $sites = adgear_get_service_data( 'list_sites' );
    if ( empty( $sites ) ) {
      echo '<span style="color:#c33;">';
      echo _e('Your AdGear credentials appear to be incorrect: we couldn\'t find any configured sites in your account. Create a site in AdGear or verify your credentials.');
      echo "</span>";
    } else {
?>
        <select name="adgear_site_id">
          <option value=""><?php echo _e('Choose your site&hellip;'); ?></option>
<?php
    foreach($sites["sites"] as $site) {
?>
          <option value="<?php echo $site["id"]; ?>"<?php if ( $site["id"] == get_option('adgear_site_id') ) { echo ' selected="selected"'; } ?>><?php echo $site["name"]; ?></option>
<?php
    }
?>
        </select>
<?php
    }
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
<?php
  if ( get_option('adgear_api_username') && get_option('adgear_api_key') && get_option('adgear_api_root_url') ) {
    $sites = adgear_get_service_data( 'list_sites' );
    if ( !empty( $sites ) ) {
?>
    <input type="submit" name="adgear_reload_adgear_data" class="button-secondary" value="<?php _e('Reload AdGear AdSpots and Formats') ?>" />
<?php
    }
  }
?>
  </p>
<?php
  if ( get_option('adgear_api_username') && get_option('adgear_api_key') && get_option('adgear_api_root_url') && count( adgear_formats() ) == 0 ) {
    echo "<p>". _e('Your database does not contain any formats. Use the <em>Reload AdGear AdSpots and Formats</em> button above to prime your database.') ."</p>";
  }
?>
  <p><?php echo _e("To remove this plugin and all it's configuration, visit the <a href='". get_bloginfo( 'wpurl' )."/wp-admin/plugins.php'>plugins</a> page."); ?></p>
</form>
<?php
} /* curl_init function exists */ else {
?>
    <p><?php echo _e('Sorry, but cURL support is required for this plugin to work. Follow the instructions on <a href="http://www.php.net/manual/en/curl.installation.php">PHP: Installation</a> for details.'); ?></p>
<?php
} /* curl_init function does not exist */
  echo "</div>";
}

/* TODO: Find the correct way to update settings which aren't part of the admin page.
 * FIXME: Security hole. Intention isn't verified, but doing this is non-destructive. It does open a hole to a DoS attack.
 * There's probably a better way to do this, but I haven't found it yet.
 */
if ( array_key_exists( 'adgear_reload_adgear_data',  $_POST ) ) {
  /* This function reloads both the site and ad spot embed codes. */
  adgear_update_site_embed_code( '', get_option( 'adgear_site_id' ) );

  /* Reloads all formats from AdGear */
  adgear_update_formats_csv( '', '' );
}

?>
