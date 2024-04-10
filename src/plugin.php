<?php

namespace Tailwind10F;


define('TAILWIND10F_STYLESHEET', WP_PLUGIN_DIR . '/tailwind10f/tailwind10f.css');
define('TAILWIND10F_CACHED_CLASS_LIST', WP_PLUGIN_DIR . '/tailwind10f/tailwind_classes.txt');
define('TAILWIND10F_CONFIG_FILE', get_stylesheet_directory() . '/tailwind_config.json');

class Plugin {

  public function create_actions() {

    add_action('template_redirect', function() {
      ob_start(function ($buffer) {
        // parse html
        $html_classes = $this->get_classes_from_html($buffer);

        // parse js files (if necessary)
        $js_classes = $this->get_classes_from_js();

        // debug print js classes?
        // file_put_contents(WP_PLUGIN_DIR . '/tailwind10f/js_classes.txt', implode("\n", $js_classes));

        // get old classes in cached class file
        $cached_classes = $this->get_classes_from_cached_list();

        // combine new classes with old classes in class file
        $combined_classes = array_unique(array_merge(
          $html_classes,
          $js_classes,
          $cached_classes
        ));
	      asort($combined_classes);

        if ( sizeof( $cached_classes ) == sizeof( $combined_classes ) ) {
        	return $buffer;
        }

        // write new classes back to file
        $this->write_cached_classes($combined_classes);

        // generate tailwind css
        $css = $this->generate_tailwind($combined_classes);

        // save css to file
        $this->write_css($css);

        return $buffer;
      });
    }, 10);

    add_action( 'wp_enqueue_scripts', function() {
      wp_enqueue_style('tailwind10f-css', TAILWIND10F_URL . 'tailwind10f.css', array(), $this->version());
    });
  }

  private function get_classes_from_html($buffer) {
    $re = '/class="([^"]+)"/';
	  preg_match_all($re, $buffer, $matches, PREG_SET_ORDER, 0);
	  $classes = array_values(array_unique(
	      $this->array_flatten(array_map(function ($m) {
	          return explode(' ', strtolower($m[1]));
	      }, $matches))
	  ));
	  asort($classes);
    return $classes;
  }

  private function get_classes_from_js() {
    $words = [];
    // get theme (js) files
    $theme = wp_get_theme();
    $files = $theme->get_files(['js'], -1);
    // $pattern = '(((\-|:)?(\[.*\]|\w+))+)'; // initial version

    $pattern = <<<EOD
    /
      (                       # entire pattern is captured
        \-?                   # optional '-' prefix, as in -z-10
        (                     # _required_ main word:
          \[[^"\'\s{}]+\]     #   anything but "'\s{} inside square brackets,
          |                   #   OR
          ([a-z]([\w\.\/])*)  #   a letter followed by word characters/periods/slashes
        )
        (                     # any number of follow up sequences:
          (\-|:)              #   must have a - or : as a separator, as in [&>div]:flex or m-0
          (                   #   follow up word:
            \[[^"\'\s{}]+\]   #     anything but "'\s{} inside square brackets,
            |                 #     OR
            (\w[\w\.\/]*)     #     at least \w with any number of \w or \. or slashes
          )
        )*
      )
    /x
    EOD;

    $pattern = '(\-?(\[[^"\'\s{}]+\]|([a-z]([\w\.\/])*))((\-|:)(\[[^"\'\s{}]+\]|(\w[\w\.\/]*)))*)';
    // print list of files
    // file_put_contents(WP_PLUGIN_DIR . '/tailwind10f/filelist.txt', implode("\n", $files));
    // for each theme file:
    $i = 0;
    foreach($files as $file) {
      if (empty($file)) continue;
      // get all words
      $content = file_get_contents($file);
      preg_match_all($pattern, $content, $matches, PREG_SET_ORDER, 0);
      $new_words = array_map(function($m) {
        return $m[0];
      }, $matches);
      $new_words = array_filter($new_words, function($word) {
        return !is_numeric($word);
      });
      $words[] = $new_words;
      // print words in each file
      // file_put_contents(WP_PLUGIN_DIR . '/tailwind10f/' . $i . '.txt', implode("\n", $new_words));
      $i++;

    }
    $words = array_unique($this->array_flatten($words));
    return $words;
  }

  private function get_classes_from_cached_list() {
  	$class_file_contents = file_get_contents(TAILWIND10F_CACHED_CLASS_LIST);
  	if ( $class_file_contents != false ) {
  		$existing_class_list = explode("\n", $class_file_contents);
      return $existing_class_list;
  	}
    return [];
  }

  private function write_cached_classes($combined_classes) {
    file_put_contents(TAILWIND10F_CACHED_CLASS_LIST, implode("\n", $combined_classes));
  }

  private function generate_tailwind($classes) {
  	if ( file_exists( TAILWIND10F_CONFIG_FILE ) ) {
	  	$config = json_decode(
	       file_get_contents( TAILWIND10F_CONFIG_FILE ) ?? '{}'
	    );
  	} else {
  		$config = [];
  	}

	  $req = new \WP_Http();
	  $result = $req->post('https://tailwind.restedapi.com/api/v1', [
	      'body' => json_encode([
	          'text' => implode(' ', $classes),
	          'options' => $config,
	      ])
	  ]);
	  $css = $result['body'];
    return $css;
  }

  private function write_css($css) {
    // TODO: Error handling
	  file_put_contents(TAILWIND10F_STYLESHEET, $css);
  }

  private function version() {
    return time();
  }

  private function array_flatten($array = null)
  {
    $result = array();
    if (!is_array($array)) {
      $array = func_get_args();
    }
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $result = array_merge($result, $this->array_flatten($value));
      } else {
        $result = array_merge($result, array($key => $value));
      }
    }
    return $result;
  }

}
