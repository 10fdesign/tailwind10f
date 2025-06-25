<?php

namespace Tailwind10F;

class APIEndpoint {
  public static function register_settings() {
    add_settings_field(
      'tailwind10f_api_endpoint', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'API Endpoint', 'tailwind10f' ),
      'Tailwind10F\APIEndpoint::render',
      'tailwind10f',
      'tailwind10f_section_config',
      array(
        'label_for'         => 'tailwind10f_api_endpoint',
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
      <option value="live" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'live', false ) ) : ( '' ); ?>>
        Live
      </option>
      <option value="staging" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'staging', false ) ) : ( '' ); ?>>
        Staging
      </option>
      <?php $env = wp_get_environment_type(); ?>
      <option value="local" <?= ($env == 'local') ? '' : 'disabled' ?> <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'local', false ) ) : ( '' ); ?>>
        Local
      </option>
    </select>
    <?php
  }

}
