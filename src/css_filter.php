<?php

namespace Tailwind10F;

class CSSFilter {

  /*
   * Returns an array of patterns that should not be sent to the Tailwind API.
   */
  protected static function blacklist_patterns() {
    return [

      // empty string disallowed
      '/^$/',

      // 'wp-' prefix disallowed
      '/^wp-/',

      // common wordpress classes
      '/^author-/',
      '/^category-\d/',
      '/^menu-item-\d/',
      '/^page-id-\d/',
      '/^page-item-\d/',
      '/^page-paged-\d/',
      '/^paged-\d/',
      '/^parent-pageid-\d/',
      '/^post-\d/',
      '/^postid-\d/',
      '/^search-paged-\d/',
      '/^slug-/',
      '/^tag-\d/',
      '/^term-/',

      // problematic wordpress/tailwind collision classes
      '/^size-/', // wordpress adds a size class to images depending on the size selected, eg. 'size-full'

      // common javascript "classes"
      '/^this\./',
      '/^window\./',

      // common plugin prefixes
      '/^shiftnav-/',
      '/^tribe-/',
      '/^woocommerce-/',
      '/^yoast-/',
      '/^wpseo-/',

      // misc
      '/^uuid-/',

    ];
  }

  /*
   * Returns a copy of `$classes` with blacklisted classes removed.
   */
  public static function filter_classes($classes, $custom_blacklist) {
    $valid_classes = [];
    $patterns = array_merge(CSSFilter::blacklist_patterns(), $custom_blacklist);


    foreach ($classes as $class) {
      $blacklisted = false;

      foreach ($patterns as $pattern) {
        if (@preg_match($pattern, $class)) {
          $blacklisted = true;
          break;
        }
      }
      if ($blacklisted) {
        continue;
      }
      $valid_classes[] = $class;
    }
    return $valid_classes;
  }

  // generate the array of patterns using the settings string
  public static function custom_blacklist($blacklist_string) {
    $patterns = [];
    $split_by_line = explode("\n", $blacklist_string);

    foreach ($split_by_line as $line) {
      $pattern = trim($line);
      if (empty($pattern)) { // string is empty :)
        continue;
      }
      if (!CSSFilter::pattern_is_valid($pattern)) { // pattern is invalid
        continue;
      }
      $patterns[] = $pattern;
    }
    return $patterns;
  }

  public static function pattern_is_valid($pattern) {
    return !(@preg_match($pattern, NULL) === false);
  }
}
