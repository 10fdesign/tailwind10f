<?php

namespace Tailwind10F;

class History {

  public static function register_settings() {
    // Register the history section in the "tailwind10f" page.
    add_settings_section(
      'tailwind10f_section_history',
      __( 'Tailwind API History', 'tailwind10f' ),
      'Tailwind10F\History::render',
      'tailwind10f_history'
    );
  }

  public static function render($args) {
    $history = get_option('tailwind10f_history', []);
    ?>
    <?php if (!empty($history)): ?>
      <?php History::build_table($history); ?>
    <?php else: ?>
      <p>No recorded API calls.</p>
    <?php endif;
  }

  private static function build_table($history) {
    $columns = [];
    foreach ($history as $h) {
      foreach (array_keys($h) as $key) {
        $columns[$key] = $key;
      }
    }
    $settings = Settings::get_settings();

    // we don't want to show the "color" column - it's just for styling
    unset($columns['color']);

    // ignore "response_time" - it gets put into the last_time column
    unset($columns['response_time']);

    // make sure causes is always at the end
    unset($columns['causes']);
    $columns = array_values($columns);
    $columns[] = 'causes';

    $col_count = count($columns);
    ?>
    <form id="tailwind10f_force_api_call" method="POST" action="/wp-admin/admin-ajax.php">
      <?php if ($settings['tailwind10f_freeze_stylesheet'] == 'on'): ?>
        <input type="submit" value="Force API Call (Stylesheet is frozen)" class="button" disabled>
      <?php else: ?>
        <input type="submit" value="Force API Call" class="button" >
      <?php endif; ?>
    </form>
    <script>
      let form = document.getElementById("tailwind10f_force_api_call");

      form.addEventListener("submit", function(e) {
        e.preventDefault();

        let inputs = form.querySelectorAll(":scope input");
        inputs.forEach((input) => input.toggleAttribute('disabled', true));

        let formData = new FormData();
        formData.append('action', 'tailwind10f_force_api_call');
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
          })
          .then((response) => response.text())
          .then((data) => {
            window.location.reload();
          })
          .catch((err) => {
            console.error("Error attempting to force Tailwind10F API call: ", err);
          });
      });
    </script>
    <div class="tailwind10f-history">
      <tailwind10f-table style="grid-template-columns: repeat(<?= $col_count - 1; ?>, minmax(10ch, 30ch)) minmax(30ch, 100%);">
        <?php foreach ($columns as $col): ?>
          <tailwind10f-th><?= $col; ?></tailwind10f-th>
        <?php endforeach; ?>
        <?php foreach ($history as $h): ?>
          <tailwind10f-tr data-color="<?= $h['color'] ?? 'blue'; ?>">
            <?php foreach ($columns as $col): ?>
              <?php History::build_cell($h, $col); ?>
            <?php endforeach; ?>
          </tailwind10f-tr>
        <?php endforeach; ?>
      </tailwind10f-table>
    </div>

    <?php
  }

  private static function build_cell($history, $col) {
    $val = '';
    if (array_key_exists($col, $history)) {
      $val = $history[$col] ?? '';
    }
    ?>
    <tailwind10f-td data-label="<?= $col ?>">
      <?php
      switch ($col) {
        case 'causes':
          $causes = $val;
          foreach ($causes as $key => $cause) {
            if (empty($cause)) continue;
            // $func = function($a) {
            //     return $value * 2;
            // };
            if (is_array($cause)) {
              $cause = array_map(function($a) {
                return '<span class="tailwind10f-new-css-class">' . $a . '</span>';
              }, $cause);
              $cause = implode(' ', $cause);
              ?>
                <details>
                  <summary class="tailwind10f-summary"><?= $key; ?></summary>
                  <div><?= $cause; ?></div>
                </details>
              <?php
            } else {
              ?>
                <div><?= $key; ?></div>
              <?php
            }
          }
          break;
        case 'time':
        case 'last time':
          ?>
          <?php if (is_int($val)): ?>
            <div title="<?= date('D, d M Y H:i:s', $val); ?>">
              <?= History::time_ago(intval($val)); ?>
              <?php if (array_key_exists('response_time', $history)): ?>
                (<?= $history['response_time']; ?>)
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php

          break;
        case 'permalink':
          ?>
          <a href="<?= $val; ?>" target="_blank"><?= $val ?></a>
          <?php
          break;
        default:
          echo $val;
          break;
      }
      ?>
    </tailwind10f-td>
    <?
  }

  private static function time_ago($timestamp) {
    $current_time = time();
    $time_diff = $current_time - $timestamp;

    // Time intervals in seconds
    $intervals = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    // Calculate the closest interval
    foreach ($intervals as $seconds => $label) {
        $time_value = floor($time_diff / $seconds);
        if ($time_value >= 1) {
            return $time_value . ' ' . $label . ($time_value > 1 ? 's' : '') . ' ago';
        }
    }

    return 'Just now';
  }

}
