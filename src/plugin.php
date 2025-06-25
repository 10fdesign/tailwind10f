<?php

namespace Tailwind10F;

require_once WP_PLUGIN_DIR . '/tailwind10f/src/css_filter.php';
require_once WP_PLUGIN_DIR . '/tailwind10f/src/settings.php';

class Plugin {

  public function create_actions() {

    add_filter( 'template_include', function($t) {
        $GLOBALS['tailwind10f_current_theme_template'] = basename($t);
        return $t;
    }, 1000 );

    add_action('template_redirect', function() {

      ob_start(function ($buffer) {
        Plugin::perform_build($buffer);
        return $buffer;
      });
    }, 10);

    // the priority of the css enqueue callback - can be overriddden to go before/after base styles
    $settings = Settings::get_settings();
    $css_priority = intval($settings['tailwind10f_stylesheet_priority']);

    add_action( 'wp_enqueue_scripts', function() {
      wp_enqueue_style(
        'tailwind10f-css',
        Plugin::stylesheet_url(),
        [],
        Plugin::stylesheet_version()
      );
    }, $css_priority);
  }

  public static function perform_build($buffer = '', $force_api_call = false) {
    $settings = Settings::get_settings();
    if ($settings['tailwind10f_freeze_stylesheet'] == 'on') {
      // early return if the stylesheet is frozen
      return;
    }

    $event = Plugin::new_api_event();

    $last_api_call_time = intval(get_option('tailwind10f_last_api_call_time', '0'));
    $settings_updated_time = intval(get_option('tailwind10f_settings_updated_time', '0'));
    $skip_api_call = true;

    $event['status'] = '(' . ($settings['tailwind10f_api_endpoint'] ?? '?');
    $event['status'] .= '@' . ($settings['tailwind10f_version'] ?? '?') . ') ';

    $custom_blacklist = CSSFilter::custom_blacklist($settings['tailwind10f_custom_blacklist']);

    // create our directory inside wp-content/uploads
    wp_mkdir_p(Plugin::uploads_folder_path());

    // parse html
    $html_classes = Plugin::get_classes_from_html($buffer);
    $html_classes = CSSFilter::filter_classes($html_classes, $custom_blacklist);

    // parse js files (if necessary)
    $js_classes = Plugin::get_classes_from_js();
    $js_classes = CSSFilter::filter_classes($js_classes, $custom_blacklist);

    // get cached classes
    $cached_classes = Plugin::get_classes_from_cached_list();
    $cached_classes = CSSFilter::filter_classes($cached_classes, $custom_blacklist);

    // combine new classes with cache
    $combined_classes = array_unique(array_merge(
      $html_classes,
      $js_classes,
      $cached_classes
    ));

    $combined_classes = array_filter($combined_classes, function($var) {
      return !preg_match('/[^\x20-\x7e]/', $var);
    });

    asort($combined_classes);

    if ($settings_updated_time >= $last_api_call_time) {
      $event['causes']['settings_updated'] = 'true';
      $skip_api_call = false;
    }

    $env = wp_get_environment_type();
    if (in_array($env, ['local'])) {
      $event['causes']['local'] = 'true';
      $skip_api_call = false;
    }

    $new_html_classes = array_diff($html_classes, $cached_classes);
    if (!empty($new_html_classes)) {
      $event['causes']['new_html_classes'] = $new_html_classes;
      $skip_api_call = false;
    }

    $new_js_classes = array_diff($js_classes, $cached_classes);
    if (!empty($new_js_classes)) {
      $event['causes']['new_js_classes'] = $new_js_classes;
      $skip_api_call = false;
    }

    if ($force_api_call) {
      $event['causes']['force_api_call'] = 'force';
      $skip_api_call = false;
    }

    if ($skip_api_call) {
      return $buffer;
    }


    // debug print html classes?
    file_put_contents(Plugin::uploads_folder_path() . '/html_classes.txt', implode("\n", $html_classes));

    // debug print js classes?
    file_put_contents(Plugin::uploads_folder_path() . '/js_classes.txt', implode("\n", $js_classes));


    // parse config
    if ($settings['tailwind10f_version'] == '3') {
      $config = $settings['tailwind10f_tailwind3_config'] ?? false;
      if (empty($config)) {
        $config = "{}";
      }
    } else {
      $config = $settings['tailwind10f_tailwind4_config'] ?? false;
    }

    if ($config == NULL) {
      $event['status'] .= 'Aborted (invalid config)';
      $event['color'] = 'yellow';
      Plugin::save_event($event);
      return $buffer;
    }

    // generate tailwind css
    $before = microtime(true);
    $api_url = Plugin::get_api_url($settings);
    $result = Plugin::generate_tailwind($combined_classes, $config, $api_url);
    $after = microtime(true);

    $event['response_time'] = floor(($after - $before) * 1000.0) . 'ms';

    if ( $result['code'] != 200) { // response was not 200OK
      // TODO: log error in history?
      $event['status'] .= "Error ({$result['code']}): {$result['info']}";
      $event['color'] = 'red';
      Plugin::save_event($event);
      return $buffer;
    }

    $css = $result['css'];

    if (empty($css)) {
      $event['status'] .= "Error: Empty styles returned";
      $event['color'] = 'red';
      Plugin::save_event($event);
      return $buffer;
    }

    $event['status'] .= 'Success';
    Plugin::save_event($event);

    // save the call time
    update_option('tailwind10f_last_api_call_time', time());

    // write new classes back to file
    Plugin::write_cached_classes($combined_classes);

    // save css to file
    Plugin::write_css($css);

    $version = intval(get_option('tailwind10f_stylesheet_version', 1));
    update_option('tailwind10f_stylesheet_version', $version + 1);

    return $buffer;
  }

  private static function get_classes_from_html($buffer) {
    $without_newlines = preg_replace('/[\r\n]/', '', $buffer);
    $re = '/class="([^"]+)"/';
	  preg_match_all($re, $without_newlines, $matches, PREG_SET_ORDER, 0);
	  $classes = array_values(array_unique(
      Plugin::array_flatten(array_map(function ($m) {
        return preg_split('/\s+/', strtolower($m[1]));
      }, $matches))
	  ));
    $classes = array_filter($classes, 'Tailwind10F\Plugin::classname_has_valid_parentheses');
	  asort($classes);
    return $classes;
  }

  // returns true if a classname (string) has matching parentheses
  private static function classname_has_valid_parentheses($classname) {
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

  private static function get_classes_from_js() {
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
    }
    $words = array_unique(Plugin::array_flatten($words));
    return $words;
  }

  private static function get_classes_from_cached_list() {
    $cache = get_option('tailwind10f_class_cache', '');
    $classes = explode("\n", $cache);
    return $classes;
  }

  private static function write_cached_classes($combined_classes) {
    $cache = implode("\n", $combined_classes);
    update_option('tailwind10f_class_cache', $cache);
    return;
  }

  private static function get_api_url($settings) {
    $env = 'live';
    $version = '3';
    if (is_array($settings) && array_key_exists('tailwind10f_api_endpoint', $settings)) {
      $env = $settings['tailwind10f_api_endpoint'];
    }
    if (is_array($settings) && array_key_exists('tailwind10f_version', $settings)) {
      $version = $settings['tailwind10f_version'];
    }

    switch ($env) {
      case 'local':
        return "http://host.docker.internal:3000/v2/tailwind$version";
      case 'staging':
        return "https://api.10fdesign.io/api-staging/v2/tailwind$version";
      case 'live':
      default:
        return "https://api.10fdesign.io/api/v2/tailwind$version";
    }
  }

  private static function generate_tailwind($classes, $config, $api_url) {
	  $req = new \WP_Http();

    $imploded_classes = implode(' ', $classes);

    $result = wp_remote_post($api_url, [
      'data_format' => 'body',
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
      ],
      'body' => json_encode([
          'classes' => $imploded_classes,
          'options' => $config,
      ]),
    ]);

    // $result = $req->post($address, [
    //   'body' => json_encode([
    //       'classes' => urlencode($imploded_classes),
    //       'options' => urlencode($config),
    //   ])
    // ]);

    if (is_wp_error($result)) {
      // log error or something here
      return [
        'code' => -1000,
        'css' => '',
        // 'info' => implode("\n", $result.get_error_messages())
        'info' => var_export($result, false)
      ];
    }

    // if result is not 200 (OK) then we assume something has gone
    // wrong - do not use the body as a css file
    if ($result['response']['code'] != 200) {
      return [
        'code' => $result['response']['code'],
        'css' => '',
        'info' => "API request returned error {$result['response']['code']}",
      ];
    }

    $body = json_decode($result['body']);
    if (intval($body->apiCode < 0)) {
      return [
        'code' => $body->apiCode,
        'css' => '',
        'info' => $body->message,
      ];
    }

	  $css = $body->css;
    return [
      'code' => 200,
      'css' => $css,
      'info' => 'success',
    ];
  }

  private static function write_css($css) {
    // TODO: Error handling
	  file_put_contents(Plugin::stylesheet_path(), $css);
	  // file_put_contents(TAILWIND10F_ADMIN_STYLESHEET, ".is-desktop-preview {\n" . $css . "\n}");
  }

  private static function stylesheet_version() {
    $version = get_option('tailwind10f_stylesheet_version', 1);
    return $version;
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

  protected static function max_history_length() {
    return 25;
  }

  protected static function save_event($event) {
    $history = get_option('tailwind10f_history', []);

    $username = '';

    if ($user = wp_get_current_user()) {
      $username = $user->user_login;
    }

    $columns_to_compare = ['user', 'permalink', 'causes', 'template', 'status'];

    // $increment_last_event_count = true;

    $same_event = false;
    if (count($history) > 0) {
      $same_event = true;
      $previous_event = &$history[0];
      foreach ($columns_to_compare as $col) {
        if ($previous_event[$col] != $event[$col]) {
          $same_event = false;
          break;
        }
      }
      if ($same_event) {
        $previous_event['count'] = intval($previous_event['count']) + 1;
        $previous_event['last time'] = time();
        $previous_event['response_time'] = $event['response_time'];
      }
    }

    if (!$same_event) {
      array_unshift($history, $event);
    }

    // keep under max history length
    $history = array_slice($history, 0, Plugin::max_history_length());

    update_option('tailwind10f_history', $history);
  }

  private static function array_flatten($array = null)
  {
    $result = array();
    if (!is_array($array)) {
      $array = func_get_args();
    }
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $result = array_merge($result, Plugin::array_flatten($value));
      } else {
        $result = array_merge($result, array($key => $value));
      }
    }
    return $result;
  }

  protected static function new_api_event() {
    global $wp;
    $username = '';
    if ($user = wp_get_current_user()) {
      $username = $user->user_login;
    }
    return [
      'status'        => 'unfinished!',
      'user'          => $username,
      'last time'     => time(),
      'template'      => Plugin::get_current_template(),
      'permalink'     => add_query_arg( $wp->query_vars, home_url( $wp->request ) ),
      'count'         => 1,
      'color'         => 'blue',
      'response_time' => '0',
      'causes'        => [
        'local' => '',
        'new_html_classes' => '',
        'new_jss_classes' => '',
        'force_api_call' => '',
        'settings_updated' => '',
      ],
    ];
  }


    protected static function get_current_template( $echo = false ) {
        if( !isset( $GLOBALS['tailwind10f_current_theme_template'] ) )
            return false;
        if( $echo )
            echo $GLOBALS['tailwind10f_current_theme_template'];
        else
            return $GLOBALS['tailwind10f_current_theme_template'];
    }

}
