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
    require_once(dirname(__FILE__) . "/adgear-ad-manager/admin.php");
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
  echo "<!-- adgear adspot embed tag -->\n";
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

?>
