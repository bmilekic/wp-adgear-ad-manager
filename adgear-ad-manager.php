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

    if ( 'yes' == get_option( 'adgear_enable_shortcode_in_sidebar' ) ) {
      add_filter('widget_text', 'do_shortcode');
    }
  }
}

function adgear_output_site_embed_tag() {
  $embed_code = get_option('adgear_site_embed_code');
  if ( !$embed_code ) return;

  echo "<!-- adgear site embed tag -->\n";
  echo $embed_code;
}

function adgear_ad() {
  $embed_code = "";
  if ( get_option( 'adgear_site_is_dynamic' ) ) {
    $format = func_get_arg(0);
    $path   = func_get_arg(1);

    // Switch on $format
    $embed_code = get_option( 'adgear_site_universal_embed_code' );
    $embed_code = str_replace( "__CHIP_KEY__", get_option( 'adgear_site_chip_key' ), $embed_code );
    $embed_code = str_replace( "__FORMAT_ID__", $format, $embed_code );
    if ( $path ) {
      $embed_code = preg_replace( '/"path"\s*:\s*\[.*\]/', '"path":'.json_encode( $path ), $embed_code );
    } else {
      // We might be called with only a single arg, and func_get_arg() returns FALSE in that case
      $embed_code = preg_replace( '/"path"\s*:\s*\[.*\]/', '"path":'.json_encode( array() ), $embed_code );
    }
  } else {
    $csv = get_option( 'adgear_ad_spots_csv' );
    if ( $csv ) {
      $match = func_get_arg(0);
      foreach( explode( "\n", $csv ) as $line ) {
        $row = explode( ",", $line );
        if ( $row[0] == $match || (is_array($row) && count( $row ) == 3 && $row[1] == $match ) ) {
          $key = 'adgear_adspot_embed_code_'. $row[0];
          $embed_code = get_option( $key );
          break;
        }
      }
    }
  }

  return $embed_code;
}

function adgear_ad_handler($atts) {
  extract(shortcode_atts(array(
    "id"      => "",
    "name"    => "",
    "format"  => "",
    "path"    => "",
    "slugify" => "",
    "single"  => "",
  ), $atts));

  // If this tag should render only on single posts page, and we're not on a single post, abort
  if ($single == 'yes' && !is_single()) return "";

  // If this tag should render only on listing pages, and we're on a single post, abort
  if ($single == 'no'  &&  is_single()) return "";

  if ( $id ) {
    return adgear_ad( $id );
  } else if ( $name ) {
    return adgear_ad( $name );
  } else if ( $format && $path ) {
    $pathname = array();

    switch( $path ) {
    case "by_categories":
      global $post;
      $postcats = get_the_category($post->ID);
      if ( $postcats ) {
        foreach( $postcats as $cat ) {
          $pathname[] = $cat->cat_name;
        }
      }
      sort( $pathname );
      break;

    case "by_tags":
      global $post;
      $posttags = get_the_tags($post->ID);
      if ( $posttags ) {
        foreach( $posttags as $tag ) {
          $pathname[] = $tag->name;
        }
      }
      sort( $pathname );
      break;

    default:
      $pathname = explode( ',', $path );
      break;
    }

    if ( $slugify == "1" || $slugify == "yes" ) {
      $post = get_post( get_the_ID() );
      $pathname[] = $post->post_name;
    }

    return adgear_ad( $format, $pathname);
  } else if ( $format ) {
    return adgear_ad( $format, array() );
  } else {
    return "<!-- adgear_serve_ad_tag could not understand atts -->";
  }
}
add_shortcode('adgear_ad', 'adgear_ad_handler');

function adgear_cleanup_obsolete_ad_spot_data() {
  $csv = get_option( 'adgear_ad_spots_csv' );
  if ( $csv ) {
    foreach( explode( "\n", $csv ) as $line ) {
      $row = explode( ",", $line );
      $key = 'adgear_adspot_embed_code_'. $row[0];
      if ( $key != "" ) delete_option( $key );
    }
  }
}

function adgear_update_site_embed_code($old_value, $new_value) {
  if ( $old_value == $new_value ) return;

  $log = "";
  $sites = adgear_get_service_data( 'list_sites' );

  foreach( $sites["sites"] as $site ) {
    if ( $site["id"] == $new_value ) {
      update_option( 'adgear_site_embed_code', $site["embed_code"] );
      update_option( 'adgear_site_chip_key', $site["chip_key"] );

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
            $ad_spots = adgear_api_call( $service["url"] );
            $csv = "";

            foreach($ad_spots["ad_spots"] as $ad_spot) {
              $csv .= $ad_spot['id'] .','. $ad_spot['name'] .','. $ad_spot['format_id'] . "\n";
              update_option( 'adgear_adspot_embed_code_'.$ad_spot['id'], $ad_spot['embed_code'] );
            }

            update_option( 'adgear_ad_spots_csv', $csv );
            break;
          }
        }
      }

      break;
    }
  }

  update_option( 'adgear_log', $log );
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
  register_setting( 'adgear-settings-group', 'adgear_enable_shortcode_in_sidebar' );
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
      <th scope="row">Enable shortcodes in sidebar</th>
      <td>
        <select name="adgear_enable_shortcode_in_sidebar">
          <option value="no"<?php echo (get_option( 'adgear_enable_shortcode_in_sidebar' ) != "yes") ? " selected" : "" ?>>No</option>
          <option value="yes"<?php echo (get_option( 'adgear_enable_shortcode_in_sidebar' ) == "yes") ? " selected" : "" ?>>Yes</option>
        </select>
        <p>There are security implications in enabling this setting.</p>
      </td>
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
