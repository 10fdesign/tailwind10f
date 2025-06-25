<?php

namespace Tailwind10F;

class Update {

  public static function init() {
    add_filter(
      'update_plugins_api.10fdesign.io',
      'Tailwind10F\Update::check_for_updates',
      10,
      3
    );
  }

  public static function check_for_updates($update, $plugin_data, $plugin_file) {
    static $response = false;

    if (empty( $plugin_data['UpdateURI'] ) || !empty($update)) {
      return $update;
    }

    if ($response === false) {
      $response = wp_remote_get( $plugin_data['UpdateURI'] );
    }

    if (empty($response['body'])) {
      return $update;
    }

    $custom_plugins_data = json_decode($response['body'], true);

    if(!empty($custom_plugins_data[$plugin_file])) {
      return $custom_plugins_data[$plugin_file];
    }
    return $update;
  }

}
