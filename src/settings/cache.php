<?php

namespace Tailwind10F;

class Cache {

  public static function register_settings() {
    add_settings_section(
      'tailwind10f_section_cache', // id
      __( 'Tailwind Cache', 'tailwind10f' ), // title
      'Tailwind10F\Cache::render', // callback
      'tailwind10f_cache' // page
    );
  }

  public static function render() {
    $cache = get_option('tailwind10f_class_cache', '');
    $classes = explode("\n", $cache);
    $classes = array_filter($classes, function($el) {
      return !empty($el);
    });
    ?>
      <form id="tailwind10f_clear_cache" method="POST" action="/wp-admin/admin-ajax.php">
        <input type="submit" value="Clear CSS Cache" class="button" >
      </form>
      <script>
        let form = document.getElementById("tailwind10f_clear_cache");

        form.addEventListener("submit", function(e) {

          e.preventDefault();

          let inputs = form.querySelectorAll(":scope input");
          inputs.forEach((input) => input.toggleAttribute('disabled', true));

          let formData = new FormData();
          formData.append('action', 'tailwind10f_clear_cache');
          fetch('/wp-admin/admin-ajax.php', {
              method: 'POST',
              body: formData
            })
            .then((response) => response.text())
            .then((data) => {
              window.location.reload();
            })
            .catch((err) => {
              console.error("Error attempting to clear tailwind cache: ", err);
            });
        });
      </script>
      <?php if (empty($classes)): ?>
        <p>No CSS has been cached.</p>
      <?php else: ?>
        <div class="tailwind10f-cache-list">
          <?php foreach ($classes as $class): ?>
            <span class="tailwind10f-cached-class"><?= $class ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php
  }

}
