<?php

namespace Tailwind10F;

class FreezeStylesheet {
  public static function register_settings() {
    add_settings_field(
      'tailwind10f_freeze_stylesheet', // As of WP 4.6 this value is used only internally.
                              // Use $args' label_for to populate the id inside the callback.
        __( 'Freeze Stylesheet', 'tailwind10f' ),
      'Tailwind10F\FreezeStylesheet::render',
      'tailwind10f',
      'tailwind10f_section_config',
      array(
        'label_for'         => 'tailwind10f_freeze_stylesheet',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );
  }

  public static function render($args) {
    $options = get_option( 'tailwind10f_options' );
    $value = false;
    if (isset($options[$args['label_for']])) {
      $value = ($options[$args['label_for']] == "on");
    }
    $checked = ($value == true) ? 'checked' : '';
    ?>
    <input
        type="checkbox"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        <?= $checked ?> >
    <p class="description">
      When checked, the plugin will not do requests to the API and the current stylesheet will be kept as is.
    </p>
    <?php
  }

}
