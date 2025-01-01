<?php

/**
 *
 * @link
 * @since             0.0.1
 * @package           Tailpress
 *
 * @wordpress-plugin
 *
 * Plugin Name:    Tailwind 10F
 * Plugin URI:     https://10fdesign.io/
 * Update URI:     https://raw.githubusercontent.com/10fdesign/tailwind10f/refs/heads/main/tailwind10f.zip
 * Description:    Tailwind :)
 * Version:        0.0.1
 * Author:         10F Design
 * Author URI:     https://10fdesign.io
 */

require WP_PLUGIN_DIR . '/tailwind10f/src/plugin.php';

define('TAILWIND10F_URL', plugin_dir_url(__FILE__ ));

use Tailwind10F\Plugin;

(new Plugin())->create_actions();
