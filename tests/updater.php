<?php
require dirname(__DIR__, 2) . '/empfängererklärung_form/.tools/wordpress/wp-load.php';
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local only.'); }
$checks = 0;
function rf_update_expect(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++; echo 'PASS: ' . $label . "\n";
}

$checker = RF_Updater::checker();
$metadata = json_decode(file_get_contents(dirname(__DIR__) . '/dist/update.json'), true);
$fixture = $metadata; $fixture['version'] = '9.9.9';
$fixture['download_url'] = RF_Updater::REPOSITORY . '/releases/download/v9.9.9/reklamationsformular-9.9.9.zip';
$http_status = 200; $requests = [];
$mock = static function ($pre, $args, $url) use (&$fixture, &$http_status, &$requests) {
    if (strpos($url, RF_Updater::METADATA_URL) !== 0) { return $pre; }
    $requests[] = $url;
    if ($http_status === 0) { return new WP_Error('offline', 'Simulated network outage'); }
    return ['headers' => [], 'body' => is_string($fixture) ? $fixture : wp_json_encode($fixture), 'response' => ['code' => $http_status, 'message' => 'Test'], 'cookies' => []];
};
add_filter('pre_http_request', $mock, 100, 3);
try {
    rf_update_expect($checker === RF_Updater::checker(), 'one updater instance');
    $result = $checker->checkForUpdates();
    rf_update_expect($result && $result->version === '9.9.9', 'new stable release is detected');
    rf_update_expect(end($requests) === RF_Updater::METADATA_URL, 'metadata request contains no customer or site data');
    $updates = get_site_transient('update_plugins'); $key = 'reklamationsformular/reklamationsformular.php';
    rf_update_expect(isset($updates->response[$key]) && $updates->response[$key]->new_version === '9.9.9', 'release appears in native WordPress updater');
    rf_update_expect($updates->response[$key]->package === $fixture['download_url'], 'native updater uses expected release ZIP');
    $good = $fixture;
    foreach (['https://attacker.example/plugin.zip', RF_Updater::REPOSITORY . '/archive/refs/heads/main.zip', RF_Updater::REPOSITORY . '/releases/download/v9.9.8/reklamationsformular-9.9.8.zip', ''] as $url) {
        $fixture = $good; $fixture['download_url'] = $url;
        rf_update_expect($checker->requestUpdate() === null, 'unexpected package URL rejected');
    }
    $fixture = $good; $fixture['version'] = '9.9.9-beta'; rf_update_expect($checker->requestUpdate() === null, 'pre-release rejected');
    $fixture = $good; $fixture['requires_php'] = '99.0'; rf_update_expect($checker->requestUpdate() === null, 'unsupported PHP rejected');
    $fixture = $good; $fixture['requires'] = '99.0'; rf_update_expect($checker->requestUpdate() === null, 'unsupported WordPress rejected');
    $fixture = '{broken'; rf_update_expect($checker->requestUpdate() === null, 'invalid JSON safely ignored');
    $http_status = 404; rf_update_expect($checker->requestUpdate() === null, 'missing release safely ignored');
    $http_status = 0; rf_update_expect($checker->requestUpdate() === null, 'network outage does not break plugin');
} finally {
    remove_filter('pre_http_request', $mock, 100); $checker->resetUpdateState();
}
echo "$checks updater assertions passed.\n";
