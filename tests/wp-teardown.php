<?php
declare(strict_types=1);

$wordpress = dirname(__DIR__, 2) . '/empfängererklärung_form/.tools/wordpress';
require $wordpress . '/wp-load.php';
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local only'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
deactivate_plugins('reklamationsformular/reklamationsformular.php', true);
echo "Lokales Test-Plugin deaktiviert.\n";
