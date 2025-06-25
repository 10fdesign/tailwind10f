<?php

/**
 *
 * @link
 * @since             0.0.1
 * @package           Tailwind 10F
 *
 * @wordpress-plugin
 *
 * Plugin Name:    Tailwind 10F
 * Plugin URI:     https://10fdesign.io/
 * Update URI:     https://api.10fdesign.io/plugins/plugins-info.json
 * Description:    Tailwind :)
 * Version:        0.4.4
 * Author:         10F Design
 * Author URI:     https://10fdesign.io
 */

namespace Tailwind10F;

define("Tailwind10F\TAILWIND10F_DIR", WP_PLUGIN_DIR . '/tailwind10f');
define("Tailwind10F\TAILWIND10F_URL", plugin_dir_url(__FILE__));

require_once TAILWIND10F_DIR . '/src/plugin.php';
require_once TAILWIND10F_DIR . '/src/settings.php';
require_once TAILWIND10F_DIR . '/src/update.php';

Settings::init();
Update::init();
(new Plugin())->create_actions();
