<?php

namespace Tailwind10F;

class Version {
  public static function register_settings() {
    add_settings_field(
      'tailwind10f_version', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'Tailwind Version', 'tailwind10f' ),
      'Tailwind10F\Version::render',
      'tailwind10f',
      'tailwind10f_section_config',
      array(
        'label_for'         => 'tailwind10f_version',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );
  }

  public static function render($args) {
    $options = get_option( 'tailwind10f_options' );
    ?>
    <select
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
      <option value="3" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '3', false ) ) : ( '' ); ?>>
        <?php esc_html_e( '3', 'tailwind10f' ); ?>
      </option>
      <option value="4" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '4', false ) ) : ( '' ); ?>>
        <?php esc_html_e( '4', 'tailwind10f' ); ?>
      </option>
    </select>
    <?php
  }

}
