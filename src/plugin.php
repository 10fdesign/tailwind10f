<?php

namespace Tailwind10F;

class Plugin {

  public function create_actions() {

    add_action('template_redirect', function() {
      ob_start(function ($buffer) {

        // create our directory inside wp-content/uploads
        wp_mkdir_p(Plugin::uploads_folder_path());

        // parse html
        $html_classes = $this->get_classes_from_html($buffer);
        // $html_classes = [];

        // parse js files (if necessary)
        $js_classes = $this->get_classes_from_js();

        // get old classes in cached class file
        $cached_classes = $this->get_classes_from_cached_list();

        // combine new classes with old classes in class file
        $combined_classes = array_unique(array_merge(
          $html_classes,
          $js_classes,
          $cached_classes
        ));

        $combined_classes = array_filter($combined_classes, function($var) {
          return !preg_match('/[^\x20-\x7e]/', $var);
        });

	      asort($combined_classes);

        $env = wp_get_environment_type();
        $skip_class_check = in_array($env, ['local']);

        if (!$skip_class_check) {
          // TODO: smarter detection of new classes
          if ( sizeof( $cached_classes ) == sizeof( $combined_classes ) ) {
            return $buffer;
          }
        }

        // debug print html classes?
        file_put_contents(Plugin::uploads_folder_path() . '/html_classes.txt', implode("\n", $html_classes));

        // debug print js classes?
        file_put_contents(Plugin::uploads_folder_path() . '/js_classes.txt', implode("\n", $js_classes));

        // generate tailwind css
        $css = $this->generate_tailwind($combined_classes);

        if ($css == false) { // response was not 200OK
          return $buffer;
        }

        // write new classes back to file
        $this->write_cached_classes($combined_classes);

        // save css to file
        $this->write_css($css);

        return $buffer;
      });
    }, 10);

    // the priority of the css enqueue callback - can be overriddden to go before/after base styles
    $css_priority = 10;

    $options = get_option( 'tailwind10f_options' );
    if (!empty($options) && is_array($options) && array_key_exists('tailwind10f_stylesheet_priority', $options) ) {
      $css_priority = intval($options['tailwind10f_stylesheet_priority']);
    }

    add_action( 'wp_enqueue_scripts', function() {
      wp_enqueue_style('tailwind10f-css', Plugin::stylesheet_url(), array(), $this->version());
    }, $css_priority);

    // TODO: Admin styles?
    // add_action( 'admin_enqueue_scripts', function () {
    // 	wp_enqueue_style('tailwind10f-admin-css', Plugin::uploads_folder_path() . '/tailwind10f_admin.css', array(), $this->version());
    // });

  }

  private function get_classes_from_html($buffer) {
    $without_newlines = preg_replace('/[\r\n]/', '', $buffer);
    $re = '/class="([^"]+)"/';
	  preg_match_all($re, $without_newlines, $matches, PREG_SET_ORDER, 0);
	  $classes = array_values(array_unique(
      $this->array_flatten(array_map(function ($m) {
        return preg_split('/\s+/', strtolower($m[1]));
      }, $matches))
	  ));
    $classes = array_filter($classes, [$this, 'classname_has_valid_parentheses']);
	  asort($classes);
    return $classes;
  }

  // returns true if a classname (string) has matching parentheses
  private function classname_has_valid_parentheses($classname) {
    $remainder = $classname;
    // strip out matching parentheses until none are remaining
    while ($remainder != ($replaced = preg_replace('/\([^\(\)]*\)/', '', $remainder))) {
      $remainder = $replaced;
    }
    // if the remainder has any leftover parentheses, it is not valid
    // ---
    // preg_match() returns 1 if the pattern matches given subject,
    // 0 if it does not, or false on failure.
    $result = preg_match('/[\(\)]/', $remainder);
    if ($result == 1 || $result === false) {
      return false;
    }
    return true;
  }

  private function get_classes_from_js() {
    $words = [];
    // get theme (js) files
    // $theme = wp_get_theme();
    // $files = $theme->get_files(['js'], -1);

    // NEW METHOD: only scan files in /js/ directory
    $files = glob( get_stylesheet_directory() . "/js/*" );

    // add files from manual list of files
    if ( file_exists( Plugin::file_list() ) ) {
	  	$extra_files = json_decode(
	       file_get_contents( Plugin::file_list() ) ?? '{}'
	    );
  	} else {
  		$extra_files = [];
  	}
  	foreach( $extra_files as $extra_file ) {
  		array_push( $files, get_stylesheet_directory() . '/' . $extra_file );
  	}

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

    // original pattern here below
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
  	$class_file_contents = file_get_contents(plugin::cached_class_path());
  	if ( $class_file_contents != false ) {
  		$existing_class_list = explode("\n", $class_file_contents);
      return $existing_class_list;
  	}
    return [];
  }

  private function write_cached_classes($combined_classes) {
    file_put_contents(Plugin::cached_class_path(), implode("\n", $combined_classes));
  }

  private function generate_tailwind($classes) {
  	if (file_exists(Plugin::config_path())) {
	  	$config = json_decode(
	       file_get_contents(Plugin::config_path()) ?? '{}'
	    );
  	} else {
  		$config = [];
  	}

	  $req = new \WP_Http();

    switch (wp_get_environment_type()) {
      case 'local':
        # local test url, with docker
        $address = 'http://host.docker.internal:4567/api/v1/tailwind3';
        break;
      case 'development':
      case 'staging':
      case 'production':
      default:
        # production API url
        $address = 'https://api.10fdesign.io/api/v1/tailwind3';
        break;
    }

    $json = json_encode([
      'classes' => implode(' ', $classes),
      'options' => $config,
    ]);

    $imploded_classes = implode(' ', $classes);
    $config = json_encode($config);

    $encoded_config = [];
    $debug = var_export($config);
    // file_put_contents(Plugin::uploads_folder_path() . '/encoded_config.txt', $debug);
    $encoded_config = urlencode($config);
    // if (!empty($config)) {
    // }

    file_put_contents(Plugin::uploads_folder_path() . '/css_imploded_replaced.txt', $imploded_classes);

    $result = $req->post($address, [
      'body' => json_encode([
          'classes' => urlencode($imploded_classes),
          'options' => urlencode($config),
      ])
  ]);

    if (is_wp_error($result)) {
      // log error or something here
      return false;
    }

    // if result is not 200 (OK) then we assume something has gone
    // wrong - do not use the body as a css file
    if ($result['response']['code'] != 200) {
      return false;
    }
    $body = json_decode($result['body']);

	  $css = $body->output;
    return $css;
  }

  private function write_css($css) {
    // TODO: Error handling
	  file_put_contents(Plugin::stylesheet_path(), $css);
	  // file_put_contents(TAILWIND10F_ADMIN_STYLESHEET, ".is-desktop-preview {\n" . $css . "\n}");
  }

  private function version() {
    return time();
  }

  protected static function uploads_folder_path() {
    return trailingslashit( wp_upload_dir()['basedir'] ) . 'tailwind10f';
  }

  protected static function uploads_folder_url() {
    return trailingslashit( wp_upload_dir()['baseurl'] ) . 'tailwind10f';
  }

  protected static function cached_class_path() {
    return Plugin::uploads_folder_path() . '/tailwind_classes.txt';
  }

  protected static function stylesheet_path() {
    return Plugin::uploads_folder_path() . '/tailwind10f.css';
  }

  protected static function stylesheet_url() {
    return Plugin::uploads_folder_url() . '/tailwind10f.css';
  }

  protected static function config_path() {
    return get_stylesheet_directory() . '/tailwind_config.json';
  }

  protected static function file_list() {
    return get_stylesheet_directory() . '/tailwind_files.json';
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
