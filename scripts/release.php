<?php
// Plattformunabhängiger Release-Build: Plugin-ZIP, Update-Metadaten und Prüfsummen.
$root = dirname(__DIR__);
$plugin = $root . '/reklamationsformular';
$main = file_get_contents($plugin . '/reklamationsformular.php');
$readme = file_get_contents($plugin . '/readme.txt');

function rf_release_header(string $text, string $name): string {
    if (!preg_match('/^[ \t*]*' . preg_quote($name, '/') . ':\s*(.+)$/m', $text, $match)) { throw new RuntimeException('Fehlender Header: ' . $name); }
    return trim($match[1]);
}

$version = rf_release_header($main, 'Version');
if (!preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $version)) { throw new RuntimeException('Stabile Version x.y.z erforderlich.'); }
if (rf_release_header($readme, 'Stable tag') !== $version || strpos($main, "define('RF_VERSION', '$version')") === false) { throw new RuntimeException('Plugin-Version, Konstante und Stable tag müssen übereinstimmen.'); }
if (isset($argv[1]) && $argv[1] !== 'v' . $version) { throw new RuntimeException('Git-Tag stimmt nicht mit der Plugin-Version überein.'); }

$repository = 'https://github.com/marcelDevParadise/reklamationsformular';
if (rf_release_header($main, 'Update URI') !== $repository || rf_release_header($main, 'Plugin URI') !== $repository) { throw new RuntimeException('Unerwartete Update-Quelle.'); }
foreach (['lib/dompdf/autoload.inc.php', 'lib/plugin-update-checker/plugin-update-checker.php', 'includes/class-rf-updater.php'] as $required) {
    if (!is_file($plugin . '/' . $required)) { throw new RuntimeException('Fehlende Abhängigkeit: ' . $required); }
}

$dist = $root . '/dist';
if (!is_dir($dist) && !mkdir($dist, 0777, true)) { throw new RuntimeException('dist-Verzeichnis kann nicht erstellt werden.'); }
$name = 'reklamationsformular-' . $version . '.zip';
$zip = new ZipArchive();
if ($zip->open($dist . '/' . $name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { throw new RuntimeException('ZIP kann nicht erstellt werden.'); }
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isLink()) { throw new RuntimeException('Symlinks sind im Plugin-Paket nicht erlaubt.'); }
    if (!$file->isFile()) { continue; }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    if (preg_match('~(^|/)(\.git|\.tools|tests|node_modules|\.env)(/|$)|\.(log|jsonl|sqlite|zip)$~i', $relative)) { throw new RuntimeException('Unerwartete Paketdatei: ' . $relative); }
    $files[$relative] = $file->getPathname();
}
ksort($files);
foreach ($files as $relative => $path) { if (!$zip->addFile($path, $relative)) { throw new RuntimeException('Datei kann nicht hinzugefügt werden: ' . $relative); } }
if (!$zip->close()) { throw new RuntimeException('ZIP kann nicht abgeschlossen werden.'); }

$zip = new ZipArchive();
if ($zip->open($dist . '/' . $name, ZipArchive::CHECKCONS) !== true || $zip->numFiles !== count($files)) { throw new RuntimeException('ZIP-Prüfung fehlgeschlagen.'); }
foreach ($files as $relative => $path) {
    if (hash('sha256', $zip->getFromName($relative)) !== hash_file('sha256', $path)) { throw new RuntimeException('ZIP-Inhalt weicht ab: ' . $relative); }
}
$zip->close();

if (!preg_match('/^= ' . preg_quote($version, '/') . ' =\s*\R(.*?)(?=^= |\z)/ms', $readme, $notes)) { throw new RuntimeException('Release-Hinweise fehlen.'); }
$notes = trim($notes[1]);
$metadata = [
    'name' => 'Reklamationsformular', 'slug' => 'reklamationsformular', 'version' => $version,
    'homepage' => $repository, 'author' => 'marcelDevParadise',
    'download_url' => $repository . '/releases/download/v' . $version . '/' . $name,
    'requires' => rf_release_header($main, 'Requires at least'), 'requires_php' => rf_release_header($main, 'Requires PHP'),
    'tested' => rf_release_header($readme, 'Tested up to'),
    'sections' => [
        'description' => 'Kunden-Reklamationen mit mehreren Artikelpositionen, PDF, E-Mail und geschütztem Archiv.',
        'changelog' => '<p>' . nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) . '</p>',
    ],
];
file_put_contents($dist . '/update.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
$setup = $version === '1.1.0' ? "\n\nEinmaliger Wechsel: Diese Version manuell in WordPress hochladen und die vorhandene Version ersetzen. Danach können automatische Updates aktiviert werden." : '';
file_put_contents($dist . '/release-notes.md', $notes . $setup . "\n");
$hashes = '';
foreach ([$name, 'update.json'] as $asset) { $hashes .= hash_file('sha256', $dist . '/' . $asset) . '  ' . $asset . "\n"; }
file_put_contents($dist . '/SHA256SUMS.txt', $hashes);
echo 'Erstellt: ' . $name . ' (' . count($files) . " Dateien) und update.json\n" . $hashes;
