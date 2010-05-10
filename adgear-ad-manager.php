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
  }
}

function adgear_output_site_embed_tag() {
  $embed_code = get_option('adgear_site_embed_code');
  if ( !$embed_code ) return;

  echo "<!-- adgear site embed tag -->\n";
  echo $embed_code;
}

function adgear_warning_css_properties() {
  return "background:#c33;margin:inherited 2em;padding:.5em;color:#000;";
}

function adgear_ad() {
  echo "<!-- adgear adspot embed tag -->\n";
  $embed_code = "";
  if ( adgear_is_dynamic_site() ) {
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
    $id = func_get_arg(0);
    $key = 'adgear_adspot_embed_code_'.$id;
    $embed_code = get_option( $key, "<p style='".adgear_warning_css_properties()."'><strong>WARNING</strong>: AdSpot $id is unknown and cannot be served.</p>" );
  }

  return $embed_code;
}

function adgear_is_dynamic_site() {
  return get_option( 'adgear_site_is_dynamic', FALSE );
}

function adgear_formats() {
  $csv = get_option("adgear_formats_csv");
  if ( $csv == "" ) return array();

  $formats = array();
  foreach( explode( "\n", $csv ) as $line ) {
    $row = explode( ",", $line );
    if ( count( $row ) > 0 ) {
      $formats[] = array( "id" => $row[1], "name" => $row[0], "width" => $row[2], "height" => $row[3] );
    }
  }

  return $formats;
}

function adgear_ad_spots() {
  $csv = get_option("adgear_ad_spots_csv");
  if ( $csv == "" ) return array();

  $formats = adgear_formats();
  $adspots = array();
  foreach( explode( "\n", $csv ) as $line ) {
    $row = explode( ",", $line );
    if ( count( $row ) > 0 ) {
      $width  = NULL;
      $height = NULL;
      foreach( $formats as $format ) {
        if ( $format["id"] == $row[2] ) {
          $width  = $format["width"];
          $height = $format["height"];
          break;
        }
      }

      $adspots[] = array( "id" => $row[1], "name" => $row[0], "width" => $width, "height" => $height );
    }
  }

  return $adspots;
}

function adgear_ad_handler($atts) {
  extract(shortcode_atts(array(
    "id"      => "",
    "format"  => "",
    "path"    => "",
    "slugify" => "",
    "single"  => "",
  ), $atts));

  // If this tag should render only on single posts page, and we're not on a single post, abort
  if ($single == 'yes' && !is_single()) return "";

  // If this tag should render only on listing pages, and we're on a single post, abort
  if ($single == 'no'  &&  is_single()) return "";

//  echo "<pre><code>";
//  var_dump(array(
//    "id"              => $id,
//    "format"          => $format,
//    "path"            => $path,
//    "slugify"         => $slugify,
//    "single"          => $single,
//    "site_is_dynamic" => adgear_is_dynamic_site(),
//  ));
//  echo "</code></pre>";

  if ( !adgear_is_dynamic_site() && $id ) {
    return adgear_ad( $id );
  } else if ( adgear_is_dynamic_site() && $format && $path ) {
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
  } else if ( adgear_is_dynamic_site() && $format ) {
    return adgear_ad( $format, array() );
  } else {
    return "<p style='".adgear_warning_css_properties()."'><strong>WARNING</strong>: AdGear Ad Manager did not understand the embed code. This would be because you used a dynamic embed code on a dynamic site, or the reverse.</p>";
  }
}
add_shortcode('adgear_ad', 'adgear_ad_handler');

function adgear_adspot_selector_ui($args) {
  extract($args);
?>
  <select class="adgear_adspot_selector" id="<?php echo $id; ?>" name="<?php echo $name; ?>" >
<?php
    foreach( adgear_ad_spots() as $adspot ) {
?>
      <option <?php if ( $selected == $adspot["id"] ) { echo "selected"; } ?> value="<?php echo $adspot["id"] ?>"><?php echo $adspot["name"]; ?><?php if ( $adspot["width"] ) { ?>&nbsp;(<?php echo $adspot["width"]; ?>&times;<?php echo $adspot["height"]; ?>)<?php } ?></option>
<?php
    }
?>
  </select>
<?php
}

function adgear_single_selector_ui($args) {
  extract($args);
?>
  <select class="adgear_single_selector" id="<?php echo $id; ?>" name="<?php echo $name; ?>">
    <option <?php if ( $selected == 'all' ) { echo "selected"; } ?> value="all">On all pages</option>
    <option <?php if ( $selected == 'yes' ) { echo "selected"; } ?> value="yes">On single post pages only</option>
    <option <?php if ( $selected == 'no'  ) { echo "selected"; } ?> value="no">On list pages only</option>
  </select>
<?php
}

function adgear_slugify_selector_ui($args) {
  extract($args);
?>
  <select class="adgear_slugify_selector" id="<?php echo $id; ?>" name="<?php echo $name; ?>">
    <option <?php if ($selected == "yes") { echo "selected"; } ?> value="yes">Yes</option>
    <option <?php if ($selected == "no")  { echo "selected"; } ?> value="no">No</option>
  </select>
<?php
}

function adgear_path_type_selector_ui($args) {
  extract($args);
?>
  <select class="adgear_path_type_selector" name="<?php echo $name; ?>" id="<?php echo $id; ?>">
    <option <?php if ($selected == "categories") { echo "selected"; } ?> value="categories">Using the post's categories</option>
    <option <?php if ($selected == "tags")       { echo "selected"; } ?> value="tags">Using the post's tags</option>
    <option <?php if ($selected == "path")       { echo "selected"; } ?> value="path">Using a static path:</option>
  </select>
  <br/>
  <input class="adgear_path" name="<?php echo $path_name; ?>" id="<?php echo $path_id; ?>" type="text" size="40" style="width:95%<?php if ( $selected != "path" ) { echo ';display:none'; } ?>" value="<?php echo $path_selected; ?>"/>
<?php
}

function adgear_format_selector_ui($args) {
  extract($args);
?>
  <select class="adgear_format_selector" name="<?php echo $name; ?>" id="<?php echo $id; ?>">
<?php if ( $include_blank ) { ?>
    <option value="">Choose an Ad format&hellip;</option>
<?php
}
  foreach( adgear_formats() as $format ) {
?>
    <option <?php if ( $selected == $format['id'] ) { echo "selected"; } ?> value="<?php echo $format["id"]; ?>"><?php echo $format["name"]; ?><?php if ( $format["width"] ) { ?> (<?php echo $format["width"]; ?>&times;<?php echo $format["height"]; ?>)<?php } ?></option>
<?php
  }
?>
  </select>
<?php
}

/* Sidebar Widget */

class AdGearAdWidget extends WP_Widget {
  function AdGearAdWidget() {
    $widget_ops = array(
      'classname'   => 'adgear_ad',
      'description' => 'Display your AdGear Ads in your sidebars' );
    $this->WP_Widget('adgear_ad', 'AdGear Ad', $widget_ops);
  }

  function widget($args, $instance) {
    extract($args, EXTR_SKIP);

    // If this tag should render only on single posts page, and we're not on a single post, abort
    if ($instance['single'] == 'yes' && !is_single()) return "";

    // If this tag should render only on listing pages, and we're on a single post, abort
    if ($instance['single'] == 'no'  &&  is_single()) return "";

    echo $before_widget;

    if ( adgear_is_dynamic_site() ) {
      $path = array( 'Some Random Path' );
      echo adgear_ad( $instance['format_id'], $path );
    } else {
      echo adgear_ad( $instance['adspot_id'] );
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    if ( adgear_is_dynamic_site() ) {
      $keys = array( 'format_id', 'path_type', 'path', 'slugify', 'single' );
    } else {
      $keys = array( 'adspot_id', 'single' );
    }

    foreach( $keys as $key ) {
      $instance[$key] = strip_tags( $new_instance[$key] );
    }

    return $instance;
  }

  function form($instance) {
    if ( adgear_is_dynamic_site() ) {
      $options = array( 'format_id' => '', 'path_type' => 'categories', 'path' => '', 'slugify' => 'yes', 'single' => 'all' );
    } else {
      $options = array( 'adspot_id' => '', 'single' => 'all' );
    }

    $instance = wp_parse_args( (array) $instance, $options );
    $single   = strip_tags($instance['single']);

    echo '<div class="adgear-meta" style="margin:0;padding:0">';
    if ( adgear_is_dynamic_site() ) {
      $format_id = strip_tags($instance['format_id']);
      $path_type = strip_tags($instance['path_type']);
      $path      = strip_tags($instance['path']);
      $slugify   = strip_tags($instance['slugify']);
?>
      <p>
        <label for="<?php echo $this->get_field_id('format_id'); ?>">Format:</label>
        <?php adgear_format_selector_ui( array( 'id' => $this->get_field_id('format_id'), 'name' => $this->get_field_name('format_id'), 'selected' => $format_id, 'include_blank' => true )); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('path_type'); ?>">Path type:</label>
        <?php adgear_path_type_selector_ui( array( 'id' => $this->get_field_id('path_type'), 'name' => $this->get_field_name('path_type'), 'selected' => $path_type, 'path_id' => $this->get_field_id('path'), 'path_name' => $this->get_field_name('path'), 'path_selected' => $path )); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('slugify'); ?>">Use post's slug in path:</label>
        <?php adgear_slugify_selector_ui( array( 'id' => $this->get_field_id('slugify'), 'name' => $this->get_field_name('slugify'), 'selected' => $slugify )); ?>
      </p>
<?php
    } else {
      $adspot_id = strip_tags($instance['adspot_id']);
?>
      <p>
        <label for="<?php echo $this->get_field_id('adspot_id'); ?>">Ad Spot:</label>
        <?php adgear_adspot_selector_ui( array( 'id' => $this->get_field_id('adspot_id'), 'name' => $this->get_field_name('adspot_id'), 'selected' => $adspot_id )); ?>
      </p>
<?php
    }
?>
      <p>
        <label for="<?php $this->get_field_id('single'); ?>">When to show this ad:</label>
        <?php adgear_single_selector_ui( array( 'id' => $this->get_field_id('single'), 'name' => $this->get_field_name('single'), 'selected' => $single)) ?>
      </p>
      <div style="display:none;margin:0;padding:0">
        <input type="hidden" value="<?php echo adgear_is_dynamic_site(); ?>" id="adgear_site_is_dynamic"/>
      </div>
    </div>
<?php
  }
}

add_action( 'widgets_init', create_function('', 'return register_widget("AdGearAdWidget");') );

?>
