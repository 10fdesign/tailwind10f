<?php

namespace Tailwind10F;

require_once TAILWIND10F_DIR . '/src/plugin.php';
require_once TAILWIND10F_DIR . '/src/css_filter.php';
require_once TAILWIND10F_DIR . '/src/settings/api_endpoint.php';
require_once TAILWIND10F_DIR . '/src/settings/blacklist.php';
require_once TAILWIND10F_DIR . '/src/settings/cache.php';
require_once TAILWIND10F_DIR . '/src/settings/freeze_stylesheet.php';
require_once TAILWIND10F_DIR . '/src/settings/history.php';
require_once TAILWIND10F_DIR . '/src/settings/priority.php';
require_once TAILWIND10F_DIR . '/src/settings/tailwind3config.php';
require_once TAILWIND10F_DIR . '/src/settings/tailwind4config.php';
require_once TAILWIND10F_DIR . '/src/settings/version.php';

class Settings {

  public static function init() {
    register_setting( 'tailwind10f', 'tailwind10f_options' );
    add_action('admin_menu', 'Tailwind10F\Settings::admin_menu');
    add_action('admin_init', 'Tailwind10F\Settings::admin_init');
    add_action('admin_enqueue_scripts', 'Tailwind10F\Settings::admin_enqueue_scripts');
    add_action('add_option_tailwind10f_options', 'Tailwind10F\Settings::options_updated');
    add_action('update_option_tailwind10f_options', 'Tailwind10F\Settings::options_updated');

    add_action('wp_ajax_tailwind10f_clear_cache', 'Tailwind10F\Settings::clear_css_cache');
    add_action('wp_ajax_tailwind10f_force_api_call', 'Tailwind10F\Settings::tailwind10f_force_api_call');
  }

  // Add the top level menu page.
  public static function admin_menu() {
    add_submenu_page(
      'tools.php',
      'Tailwind 10F',
      'Tailwind 10F',
      'manage_options',
      'tailwind10f',
      'Tailwind10F\Settings::settings_page'
    );
  }

  // admin_init hook
  public static function admin_init($suffix) {
    add_settings_section(
      'tailwind10f_section_config',                  // id
      __( 'Tailwind Configuration', 'tailwind10f' ), // title
      NULL,                                          // callback
      'tailwind10f',                                 // page
      []                                             // args
    );

    Version::register_settings();
    APIEndpoint::register_settings();
    Tailwind3Config::register_settings();
    Tailwind4Config::register_settings();
    Priority::register_settings();
    Blacklist::register_settings();
    Cache::register_settings();
    History::register_settings();
    FreezeStylesheet::register_settings();
  }

  // admin_enqueue_scripts hook
  public static function admin_enqueue_scripts() {
    wp_enqueue_script(
      'tailwind10f-ace',
      TAILWIND10F_URL . '/ace-builds/src-min-noconflict/ace.js',
      []
    );

    wp_enqueue_script(
      'tailwind10f-settings',
      TAILWIND10F_URL . '/js/settings.js',
      [],
      '1.0',
      true
    );

    wp_enqueue_style(
      'tailwind10f-admin',
      TAILWIND10F_URL . '/css/admin.css',
      []
    );
  }

  public static function options_updated($value) {
    $debug = var_export($value, true);
    update_option('tailwind10f_settings_updated_time', time());
  }

  public static function clear_css_cache($request) {
    update_option('tailwind10f_class_cache', '');
    return array(
      "success" => false
    );
  }

  public static function tailwind10f_force_api_call($request) {
    Plugin::perform_build('', true);
    return array(
      "success" => true
    );
  }

  // Settings page html callback
  public static function settings_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
      // add settings saved message with the class of "updated"
      add_settings_error( 'tailwind10f_messages', 'tailwind10f_message', __( 'Settings saved.', 'tailwind10f' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'tailwind10f_messages' );
    ?>
    <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

      <?php
        $tabs = [
          'config' => 'Configuration',
          'cache' => 'Cache',
          'history' => 'History',
        ];
        $current_tab = 'config'; // default open tab
        if (isset($_GET['tab']) && array_key_exists($_GET['tab'], $tabs)) {
          $current_tab = $_GET['tab'];
        }

        echo '<h2 class="nav-tab-wrapper">';
        foreach( $tabs as $tab => $name ){
            $x = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$x' href='?page=tailwind10f&tab=$tab'>$name</a>";

        }
        echo '</h2>';
        switch ($current_tab) {
          default:
          case 'config':
            Settings::config_page();
            break;
          case 'cache':
            Settings::cache_page();
            break;
          case 'history':
            Settings::history_page();
            break;
        }
      ?>
    </div>
    <?php
  }

  public static function config_page() {
    ?>
      <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "tailwind10f"
        settings_fields( 'tailwind10f' );
        // output setting sections and their fields
        // (sections are registered for "tailwind10f", each field is registered to a specific section)
        do_settings_sections( 'tailwind10f' );
        // output save settings button
        submit_button( 'Save Settings' );
        ?>
      </form>
    <?php
  }

  public static function cache_page() {
    settings_fields( 'tailwind10f_cache' );
    do_settings_sections( 'tailwind10f_cache' );
  }

  public static function history_page() {
    settings_fields( 'tailwind10f_history' );
    do_settings_sections( 'tailwind10f_history' );
  }

  public static function get_settings() {
    $options = get_option( 'tailwind10f_options', []);
    $options = wp_parse_args($options, [
      'tailwind10f_version' => 3,
      'tailwind10f_stylesheet_priority' => 10,
      'tailwind10f_freeze_stylesheet' => false,
      'tailwind10f_custom_blacklist' => '',
      'tailwind10f_tailwind3_config' => Tailwind3Config::DEFAULT,
      'tailwind10f_tailwind4_config' => Tailwind4Config::DEFAULT,
      'tailwind10f_api_endpoint' => 'live',
    ]);
    return $options;
  }
}
