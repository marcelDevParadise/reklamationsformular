<?php
declare(strict_types=1);

$wordpress = dirname(__DIR__, 2) . '/empfängererklärung_form/.tools/wordpress';
require $wordpress . '/wp-load.php';
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local only'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'reklamationsformular/reklamationsformular.php';
if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
}
$settings = RF_Plugin::settings();
$settings['enabled'] = true;
$settings['service_email'] = 'service@example.test';
$settings['company'] = 'Paradise Test';
update_option('rf_settings', $settings, false);

$ensure_page = static function (string $slug, string $title, string $content): void {
    $page = get_page_by_path($slug);
    $values = ['post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'page'];
    if ($page) { $values['ID'] = $page->ID; $id = wp_update_post($values, true); }
    else { $id = wp_insert_post($values, true); }
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
};
$ensure_page('reklamation', 'Reklamation', '[reklamationsformular]');
$ensure_page('reklamation-mehrfach', 'Reklamation mehrfach', '[reklamationsformular][reklamationsformular]');
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rf_rate_%'");
echo "Lokale WordPress-Testseiten bereit.\n";
