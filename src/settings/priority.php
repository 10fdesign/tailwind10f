<?php

namespace Tailwind10F;

class Priority {
  public static function register_settings() {
    add_settings_field(
      'tailwind10f_stylesheet_priority', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'Tailwind Stylesheet Priority', 'tailwind10f' ),
      'Tailwind10F\Priority::render',
      'tailwind10f',
      'tailwind10f_section_config',
      array(
        'label_for'         => 'tailwind10f_stylesheet_priority',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );
  }

  public static function render($args) {
    $options = get_option( 'tailwind10f_options' );
    $value = 10;
    if (isset($options[$args['label_for']]) && !empty($options[$args['label_for']])) {
      $value = $options[$args['label_for']];
    }
    ?>
    <input
        type="number"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        placeholder="10"
        value="<?= $value; ?>"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
    <?php
  }

}
