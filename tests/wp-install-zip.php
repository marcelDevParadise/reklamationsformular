<?php
declare(strict_types=1);

$wordpress = dirname(__DIR__, 2) . '/empfängererklärung_form/.tools/wordpress';
$main = file_get_contents(dirname(__DIR__) . '/reklamationsformular/reklamationsformular.php');
if (!preg_match('/^[ \t*]*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)$/m', $main, $match)) { throw new RuntimeException('Plugin-Version fehlt.'); }
$zip = dirname(__DIR__) . '/dist/reklamationsformular-' . $match[1] . '.zip';
require $wordpress . '/wp-load.php';
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local only'); }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

$skin = new Automatic_Upgrader_Skin();
$upgrader = new Plugin_Upgrader($skin);
$installed = $upgrader->install($zip, ['overwrite_package' => true]);
if (is_wp_error($installed)) { throw new RuntimeException('ZIP-Installation: ' . $installed->get_error_message()); }
if ($installed !== true) { throw new RuntimeException('ZIP-Installation fehlgeschlagen: ' . implode(' | ', $skin->get_errors()->get_error_messages())); }

$plugin = 'reklamationsformular/reklamationsformular.php';
$activated = activate_plugin($plugin);
if (is_wp_error($activated)) { throw new RuntimeException('Aktivierung: ' . $activated->get_error_message()); }
if (!is_plugin_active($plugin) || !class_exists('RF_Plugin') || !class_exists('RF_Store')) { throw new RuntimeException('Plugin ist nach der Aktivierung nicht vollständig geladen.'); }

global $wpdb;
$table = RF_Store::table();
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) { throw new RuntimeException('Archivtabelle fehlt nach der Aktivierung.'); }
echo "ZIP installiert und Plugin unter PHP " . PHP_VERSION . " aktiviert.\n";
