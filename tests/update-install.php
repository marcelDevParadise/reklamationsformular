<?php
// Simuliert eine ältere Installation und führt das echte WordPress-Upgrade aus dem Release-ZIP aus.
$root = dirname(__DIR__);
$wordpress = dirname(__DIR__, 2) . '/empfängererklärung_form/.tools/wordpress';
$installed = $wordpress . '/wp-content/plugins/reklamationsformular/reklamationsformular.php';
$config = file_get_contents($wordpress . '/wp-config.php');
if (strpos($config, "define('WP_ENVIRONMENT_TYPE', 'local')") === false) { throw new RuntimeException('Local test configuration required.'); }
$original_main = file_get_contents($installed);
$metadata = json_decode(file_get_contents($root . '/dist/update.json'), true);
$package = $root . '/dist/reklamationsformular-' . $metadata['version'] . '.zip';
$fixture_id = 0; $settings_before = null;
file_put_contents($installed, str_replace($metadata['version'], '1.0.99', $original_main));
try {
    define('FS_METHOD', 'direct');
    define('DOING_CRON', true);
    require $wordpress . '/wp-load.php';
    if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local only.'); }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    $settings_before = get_option('rf_settings');
    update_option('rf_settings', array_merge(RF_Plugin::settings(), ['company' => 'Update-Testfirma']), false);
    $settings_expected = get_option('rf_settings');
    $fixture_id = RF_Store::insert([
        'reference' => 'RF-UPDATE-' . bin2hex(random_bytes(6)), 'request_hash' => bin2hex(random_bytes(32)), 'payload_hash' => bin2hex(random_bytes(32)),
        'created_at' => gmdate('Y-m-d H:i:s'), 'order_number' => 'RE-UPDATE-1', 'customer_number' => 'KN-1',
        'customer_name' => 'Update Test', 'customer_email' => 'update@example.test', 'service_email' => 'service@example.test',
        'data' => wp_json_encode(['original' => 'unchanged']), 'pdf' => base64_encode('%PDF-test'),
        'token_hash' => bin2hex(random_bytes(32)), 'token_expires' => time() + 1800,
    ]);
    if (!$fixture_id) { throw new RuntimeException('Cannot create archive fixture.'); }
    $archive_before = serialize(RF_Store::get($fixture_id));
    $http_mock = static function ($pre, $args, $url) use ($metadata) {
        if ($url !== RF_Updater::METADATA_URL) { return $pre; }
        return ['headers' => [], 'body' => wp_json_encode($metadata), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
    };
    add_filter('pre_http_request', $http_mock, 100, 3);
    $download_mock = static function ($reply, $url) use ($metadata, $package) {
        if ($url !== $metadata['download_url']) { return $reply; }
        $temporary = wp_tempnam('rf-upgrade.zip'); copy($package, $temporary); return $temporary;
    };
    add_filter('upgrader_pre_download', $download_mock, 10, 2);
    RF_Updater::checker()->checkForUpdates();
    $key = 'reklamationsformular/reklamationsformular.php'; $updates = get_site_transient('update_plugins');
    if (($updates->response[$key]->new_version ?? '') !== $metadata['version']) { throw new RuntimeException('Update not offered to older installation.'); }
    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin()); $result = $upgrader->upgrade($key);
    if (!$result || is_wp_error($result)) { throw new RuntimeException('WordPress update failed: ' . print_r($upgrader->skin->get_errors(), true)); }
    if (get_plugin_data($installed, false, false)['Version'] !== $metadata['version']) { throw new RuntimeException('Wrong version installed.'); }
    if (!is_plugin_active($key)) { throw new RuntimeException('Plugin was not kept active.'); }
    if ($archive_before !== serialize(RF_Store::get($fixture_id)) || $settings_expected !== get_option('rf_settings')) { throw new RuntimeException('Update changed archive or settings.'); }
    if (!is_file(dirname($installed) . '/lib/plugin-update-checker/plugin-update-checker.php') || !is_file(dirname($installed) . '/lib/dompdf/autoload.inc.php')) { throw new RuntimeException('Installed release is incomplete.'); }
    echo "PASS: WordPress installed the newer release; plugin remained active; archive and settings were preserved.\n";
} finally {
    if ($fixture_id && class_exists('RF_Store')) { RF_Store::remove([$fixture_id]); }
    if ($settings_before !== null && function_exists('update_option')) { update_option('rf_settings', $settings_before, false); }
    if (is_file($installed)) { file_put_contents($installed, $original_main); }
    if (class_exists('RF_Updater')) { RF_Updater::checker()->resetUpdateState(); }
}
