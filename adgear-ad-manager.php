<?php
/*
 * Plugin Name: AdGear Ad Manager
 * Plugin URI: http://github.com/bloom/adgear-ad-manager
 * Description: Serve AdGear ads through your blog
 * Version: 1.1.0
 * Author: Bloom Digital Platforms
 * Author URI: http://github.com/bloom/adgear-ad-mananger
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

  /* Same callback because we clean the DB of everything that could have existed prior to this install. */
  register_activation_hook( 'adgear-ad-manager', 'adgear_deactivate' );
  register_deactivation_hook( 'adgear-ad-manager', 'adgear_deactivate' );
  if ( function_exists('register_uninstall_hook') ) {
    register_uninstall_hook(__FILE__, 'adgear_uninstall');
  }

  if ( is_admin() ) {
    require_once(dirname(__FILE__) . "/adgear-ad-manager/admin.php");
  } else {
    add_action('wp_head', 'adgear_output_site_embed_tag');
  }
}

/* Fully remove all adgear settings from the options DB. */
function adgear_uninstall() {
  require_once( dirname(__FILE__).'/uninstall.php' );
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

function adgear_ad_internal() {
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

function adgear_ad($atts) {
  extract(shortcode_atts(array(
    "id"          => "",
    "format"      => "",
    "path"        => "",
    "slugify"     => "",
    "single"      => "",
    "path_pre"    => "",
    "path_middle" => "",
    "path_post"   => "",
  ), $atts));

  // If this tag should render only on single posts page, and we're not on a single post, abort
  if ($single == 'yes' && !is_single()) return "";

  // If this tag should render only on listing pages, and we're on a single post, abort
  if ($single == 'no'  &&  is_single()) return "";

  // echo "<pre><code>";
  // var_dump(array(
  //   "id"              => $id,
  //   "format"          => $format,
  //   "path"            => $path,
  //   "path_pre"        => $path_pre,
  //   "path_middle"     => $path_middle,
  //   "path_post"       => $path_post,
  //   "slugify"         => $slugify,
  //   "single"          => $single,
  //   "site_is_dynamic" => adgear_is_dynamic_site(),
  // ));
  // echo "</code></pre>";

  if ( !adgear_is_dynamic_site() && $id ) {
    return adgear_ad_internal( $id );
  } else if ( adgear_is_dynamic_site() && $format && $path ) {
    $pathname = explode( ',', $path_pre);

    switch( $path ) {
    case "by_categories":
      global $post;
      $postcats = get_the_category($post->ID);
      $cats = array();
      if ( $postcats ) {
        foreach( $postcats as $cat ) {
          $cats[] = $cat->cat_name;
        }
      }
      sort( $cats );
      $pathname = array_merge( $pathname, $cats );
      break;

    case "by_tags":
      global $post;
      $posttags = get_the_tags($post->ID);
      $tags = array();
      if ( $posttags ) {
        foreach( $posttags as $tag ) {
          $tags[] = $tag->name;
        }
      }
      sort( $tags );
      $pathname = array_merge( $pathname, $tags );
      break;

    default:
      $pathname = array_merge( $pathname, explode( ',', $path ) );
      break;
    }

    $pathname = array_merge( $pathname, explode( ',', $path_middle ) );

    if ( $slugify == "1" || $slugify == "yes" ) {
      $post = get_post( get_the_ID() );
      $pathname[] = $post->post_name;
    }

    $pathname = array_merge( $pathname, explode( ',', $path_post ) );

    return adgear_ad_internal( $format, $pathname);
  } else if ( adgear_is_dynamic_site() && $format ) {
    return adgear_ad_internal( $format, array() );
  } else {
    return "<p style='".adgear_warning_css_properties()."'><strong>WARNING</strong>: AdGear Ad Manager did not understand the embed code. This would be because you used a dynamic embed code on a dynamic site, or the reverse.</p>";
  }
}
add_shortcode('adgear_ad', 'adgear_ad');

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

function adgear_path_pre_ui($args) {
  extract($args);
?>
  <input class="adgear_path_pre" type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"/>
<?php
}

function adgear_path_middle_ui($args) {
  extract($args);
?>
  <input class="adgear_path_middle" type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"/>
<?php
}

function adgear_path_post_ui($args) {
  extract($args);
?>
  <input class="adgear_path_post" type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"/>
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

    $pathname = array();

    if ( adgear_is_dynamic_site() ) {
      switch( $instance['path_type'] ) {
      case 'categories':
        global $post;
        $postcats = get_the_category( $post->ID );
        if ( $postcats ) {
          foreach( $postcats as $cat ) {
            $pathname[] = $cat->cat_name;
          }
        }
        sort( $pathname );
        break;

      case 'tags':
        global $post;
        $posttags = get_the_tags( $post->ID );
        if ( $posttags ) {
          foreach( $posttags as $tag ) {
            $pathname[] = $tag->name;
          }
        }
        sort( $pathname );
        break;

      case 'path':
        $pathname = explode( ',', $instance['path'] );
        break;

      default:
        echo "<p style='".adgear_warning_css_properties()."'><strong>WARNING</strong>: Bad path_type specified: cannot serve (internal error). Submit a bug report to support@adgear.com.</p>";
        echo $after_widget;
        return;
      }

      if ( $instance['slugify'] == "yes" ) {
        global $post;
        $pathname[] = $post->post_name;
      }

      echo adgear_ad_internal( $instance['format_id'], $pathname );
    } else {
      echo adgear_ad_internal( $instance['adspot_id'] );
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    if ( adgear_is_dynamic_site() ) {
      $keys = array( 'format_id', 'path_type', 'path_pre', 'path', 'path_middle', 'slugify', 'path_post', 'single' );
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
      $format_id   = strip_tags($instance['format_id']);
      $path_type   = strip_tags($instance['path_type']);
      $path        = strip_tags($instance['path']);
      $slugify     = strip_tags($instance['slugify']);

      /* Backwards compatibility: don't show ugly error messages when the keys don't exist */
      if ( array_key_exists( 'path_pre', $instance ) ) {
        $path_pre    = strip_tags($instance['path_pre']);
        $path_middle = strip_tags($instance['path_middle']);
        $path_post   = strip_tags($instance['path_post']);
      } else {
        $path_pre = $path_middle = $path_post = "";
      }
?>
      <p>
      <label for="<?php echo $this->get_field_id('format_id'); ?>"><?php _e('Ad Format:'); ?></label>
        <?php adgear_format_selector_ui( array( 'id' => $this->get_field_id('format_id'), 'name' => $this->get_field_name('format_id'), 'selected' => $format_id, 'include_blank' => true )); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('path_pre'); ?>"><?php _e('Path before:'); ?></label>
        <?php adgear_path_pre_ui( array( 'id' => $this->get_field_id('path_pre'), 'name' => $this->get_field_name('path_pre'), 'value' => $path_pre ) ); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('path_type'); ?>"><?php _e('Path type:'); ?></label>
        <?php adgear_path_type_selector_ui( array( 'id' => $this->get_field_id('path_type'), 'name' => $this->get_field_name('path_type'), 'selected' => $path_type, 'path_id' => $this->get_field_id('path'), 'path_name' => $this->get_field_name('path'), 'path_selected' => $path )); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('path_middle'); ?>"><?php _e('Path middle:'); ?></label>
        <?php adgear_path_middle_ui( array( 'id' => $this->get_field_id('path_middle'), 'name' => $this->get_field_name('path_middle'), 'value' => $path_middle ) ); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('slugify'); ?>"><?php _e('Use post\'s slug in path:'); ?></label>
        <?php adgear_slugify_selector_ui( array( 'id' => $this->get_field_id('slugify'), 'name' => $this->get_field_name('slugify'), 'selected' => $slugify )); ?>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('path_post'); ?>"><?php _e('Path after:'); ?></label>
        <?php adgear_path_post_ui( array( 'id' => $this->get_field_id('path_post'), 'name' => $this->get_field_name('path_post'), 'value' => $path_post ) ); ?>
      </p>
<?php
    } else {
      $adspot_id = strip_tags($instance['adspot_id']);
?>
      <p>
        <label for="<?php echo $this->get_field_id('adspot_id'); ?>"><?php _e('Ad spot:'); ?></label>
        <?php adgear_adspot_selector_ui( array( 'id' => $this->get_field_id('adspot_id'), 'name' => $this->get_field_name('adspot_id'), 'selected' => $adspot_id )); ?>
      </p>
<?php
    }
?>
      <p>
        <label for="<?php $this->get_field_id('single'); ?>"><?php _e('When to show this ad:'); ?></label>
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
