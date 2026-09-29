<?php
/**
 * Plugin Name: Reklamationsformular
 * Description: Kunden-Reklamationen mit mehreren Artikelpositionen, PDF, E-Mail und geschütztem Archiv.
 * Version: 1.1.2
 * Plugin URI: https://github.com/marcelDevParadise/reklamationsformular
 * Update URI: https://github.com/marcelDevParadise/reklamationsformular
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Paradise-Shisha
 * License: GPL-2.0-or-later
 * Text Domain: reklamationsformular
 */
if (!defined('ABSPATH')) { exit; }

define('RF_VERSION', '1.1.2');
define('RF_DIR', __DIR__ . '/');
define('RF_URL', plugin_dir_url(__FILE__));

require_once RF_DIR . 'includes/class-rf-validation.php';
require_once RF_DIR . 'includes/class-rf-store.php';
require_once RF_DIR . 'includes/class-rf-pdf.php';
require_once RF_DIR . 'includes/class-rf-plugin.php';
require_once RF_DIR . 'includes/class-rf-admin.php';
require_once RF_DIR . 'includes/class-rf-updater.php';

register_activation_hook(__FILE__, ['RF_Store', 'activate']);
add_action('plugins_loaded', static function () {
    RF_Updater::init();
    RF_Plugin::init();
    if (is_admin()) { RF_Admin::init(); }
});
