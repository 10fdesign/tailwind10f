<?php

namespace Tailwind10F;

class Blacklist {
  public static function register_settings() {
    add_settings_field(
      'tailwind10f_custom_blacklist',                     // id
        __( 'Tailwind Class Blacklist', 'tailwind10f' ),  // title
      'Tailwind10F\Blacklist::render',                    // callback
      'tailwind10f',                                      // page
      'tailwind10f_section_config',                       // section
      [                                                   // args
        'label_for'         => 'tailwind10f_custom_blacklist',
        'class'             => 'tailwind10f_row',
        'tailwind10f_custom_data' => 'custom',
      ]
    );
  }

  public static function render($args) {
    $options = get_option( 'tailwind10f_options' );
    $value = '';
    if (isset($options[$args['label_for']]) && !empty($options[$args['label_for']])) {
      $value = $options[$args['label_for']];
    }
    $invalid_patterns = [];
    $split_by_line = explode("\n", $value);
    foreach ($split_by_line as $line) {
      $pattern = trim($line);
      if (empty($pattern)) {
        continue;
      }
      if (!CSSFilter::pattern_is_valid($pattern)) {
        $invalid_patterns[] = $pattern;
      }
    }
    ?>
    <textarea
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['tailwind10f_custom_data'] ); ?>"
        placeholder="/^uuid-/"
        name="tailwind10f_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        class="tailwind10f-ace-input"
      ><?= $value ?></textarea>

    <div id="blacklist_editor"><?= $value ?></div>
    <script>
      (function() {
        let editor = ace.edit("blacklist_editor");
        editor.setTheme("ace/theme/github_dark");
        editor.session.setMode("ace/mode/ruby");
        editor.setOptions({
            maxLines: 24,
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
      Add regex patterns to blacklist classes here. Patterns should
      include slashes and be separated by line breaks, i.e., <span class="example-pattern">/^uuid-/</span>
    </p>
    <?php if (count($invalid_patterns) > 0): ?>
      <p class="description">
        <strong>
          <?php esc_html_e('Invalid pattern(s) detected:', 'tailwind10f'); ?>
        </strong>
      </p>
      <div>
        <?php foreach ($invalid_patterns as $pattern): ?>
          <span class="tailwind10f-invalid-pattern"><?= $pattern; ?></span>
        <? endforeach; ?>
      </div>
    <?php endif; ?>
    <?php
  }
}
