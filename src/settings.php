<?php

namespace Tailwind10F;

class Settings {

  public static function init() {
    // Register a new setting for "tailwind10f" page.
    register_setting( 'tailwind10f', 'tailwind10f_options' );

    // Register our tailwind10f options_page to the admin_menu action hook.
    add_action( 'admin_menu', 'Tailwind10F\Settings::options_page' );

    add_action('admin_init', 'Tailwind10F\Settings::admin_init');
  }

  public static function admin_init() {
    // Register a new section in the "tailwind10f" page.
    add_settings_section(
      'tailwind10f_section_developers',
      __( 'Tailwind configuration', 'tailwind10f' ), 'Tailwind10F\Settings::tailwind10f_section_developers_callback',
      'tailwind10f'
    );

    // Register a new field in the "tailwind10f_section_developers" section, inside the "tailwind10f" page.
    add_settings_field(
      'tailwind10f_version', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'Tailwind Version', 'tailwind10f' ),
      'Tailwind10F\Settings::tailwind_version',
      'tailwind10f',
      'tailwind10f_section_developers',
      array(
        'label_for'         => 'tailwind10f_version',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );

    // Register a new field in the "tailwind10f_section_developers" section, inside the "tailwind10f" page.
    add_settings_field(
      'tailwind10f_stylesheet_priority', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'Tailwind Stylesheet Priority', 'tailwind10f' ),
      'Tailwind10F\Settings::tailwind10f_stylesheet_priority',
      'tailwind10f',
      'tailwind10f_section_developers',
      array(
        'label_for'         => 'tailwind10f_stylesheet_priority',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );
  }

  /**
   * Add the top level menu page.
   */
  public static function options_page() {
    add_submenu_page(
      'tools.php',
      'Tailwind 10F',
      'Tailwind 10F',
      'manage_options',
      'tailwind10f',
      'Tailwind10F\Settings::options_page_html'
    );
  }


  /**
   * Top level menu callback function
   */
  public static function options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
      // add settings saved message with the class of "updated"
      add_settings_error( 'tailwind10f_messages', 'tailwind10f_message', __( 'Settings Saved', 'tailwind10f' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'tailwind10f_messages' );
    ?>
    <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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
    </div>
    <?php
  }


  /**
   * Developers section callback function.
   *
   * @param array $args  The settings array, defining title, id, callback.
   */
  public static function tailwind10f_section_developers_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Configure tailwind here.', 'tailwind10f' ); ?></p>
    <?php
  }

  // tailwind version selection
  public static function tailwind_version($args) {
    $options = get_option( 'tailwind10f_options' );
    ?>
    <select
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
      <option value="3" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '3', false ) ) : ( '' ); ?>>
        <?php esc_html_e( '3', 'tailwind10f' ); ?>
      </option>
      <option value="4" disabled <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '4', false ) ) : ( '' ); ?>>
        <?php esc_html_e( '4', 'tailwind10f' ); ?>
      </option>
    </select>
    <p class="description">
      <?php esc_html_e( '4.x coming soon!', 'tailwind10f' ); ?>
    </p>
    <?php
  }

  public static function tailwind10f_stylesheet_priority($args) {
    $options = get_option( 'tailwind10f_options' );
    ?>
    <input
        type="number"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        default="10"
        value="<?php echo isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '10'; ?>"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
    <?php
  }

}
