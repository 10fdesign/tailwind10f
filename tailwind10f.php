<?php

/**
 *
 * @link
 * @since             0.0.1
 * @package           Tailwind 10F
 *
 * @wordpress-plugin
 *
 * Plugin Name:    Tailwind 10F
 * Plugin URI:     https://10fdesign.io/
 * Update URI:     https://api.10fdesign.io/plugins/plugins-info.json
 * Description:    Tailwind :)
 * Version:        0.3.2
 * Author:         10F Design
 * Author URI:     https://10fdesign.io
 */

require WP_PLUGIN_DIR . '/tailwind10f/src/plugin.php';

define('TAILWIND10F_URL', plugin_dir_url(__FILE__));

use Tailwind10F\Plugin;

if(!function_exists('my_plugin_check_for_updates')) {

  function my_plugin_check_for_updates($update, $plugin_data, $plugin_file) {

      static $response = false;

      if( empty( $plugin_data['UpdateURI'] ) || ! empty($update) )
          return $update;

      if($response === false)
          $response = wp_remote_get( $plugin_data['UpdateURI'] );

      if( empty( $response['body'] ) )
          return $update;

      $custom_plugins_data = json_decode( $response['body'], true );

      if( ! empty( $custom_plugins_data[ $plugin_file ] ) )
          return $custom_plugins_data[ $plugin_file ];
      else
          return $update;

  }

  add_filter('update_plugins_api.10fdesign.io', 'my_plugin_check_for_updates', 10, 3);

}

(new Plugin())->create_actions();
