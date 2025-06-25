<?php

namespace Tailwind10F;

class Tailwind4Config {
  const DEFAULT = <<<CSS
@layer theme, base, components, utilities;

@import "tailwindcss/theme.css" layer(theme);
@import "tailwindcss/preflight.css" layer(base);
@import "tailwindcss/utilities.css" layer(utilities);
CSS;

  public static function register_settings() {

    add_settings_field(
      'tailwind10f_tailwind4_config',                 // id
        __( 'Tailwind 4 Config', 'tailwind10f' ),     // title
      'Tailwind10F\Tailwind4Config::render',          // callback
      'tailwind10f',                                  // page
      'tailwind10f_section_config',                   // section
      array(
        'label_for'         => 'tailwind10f_tailwind4_config',
        'class'             => 'tailwind10f_row tailwind4config_row',
        'tailwind10f_custom_data' => 'custom',
      )
    );
  }

  public static function render($args) {
    $options = get_option( 'tailwind10f_options' );
    $value = Tailwind4Config::DEFAULT;
    if (isset($options[$args['label_for']])) {
      $value = $options[$args['label_for']];
    }
    ?>
    <textarea
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        placeholder="/^uuid-/"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        class="tailwind10f-ace-input"
      ><?= $value ?></textarea>

    <div id="tailwind4_config_editor"><?= $value ?></div>
    <script>
      (function() {
        let editor = ace.edit("tailwind4_config_editor");
        editor.setTheme("ace/theme/github_dark");
        editor.session.setMode("ace/mode/css");
        editor.setOptions({
            maxLines: 32,
            minLines: 4,
            tabSize: 2,
            wrap: true,
        });
        editor.renderer.setScrollMargin(10, 10);
        let textarea = document.getElementById("<?= esc_attr( $args['label_for'] ); ?>");
        //
        editor.getSession().on('change', function() {
          textarea.value = editor.getSession().getValue();
        });
      })();
    </script>
    <p class="description">
      Tailwind 4 configuration. Enter options in css format.
    </p>
    <?php
  }

}
