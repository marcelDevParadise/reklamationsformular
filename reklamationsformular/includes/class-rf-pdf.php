<?php
if (!defined('ABSPATH')) { exit; }

final class RF_PDF {
    public static function generate(array $data, array $brand, string $reference): string {
        require_once RF_DIR . 'lib/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', RF_DIR . 'lib/dompdf');
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('fontCache', sys_get_temp_dir());
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(self::html($data, $brand, $reference), 'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_text(42, 810, $reference . ' | Seite {PAGE_NUM} von {PAGE_COUNT}', null, 8, [0.4, 0.45, 0.5]);
        $bytes = $pdf->output();
        if (substr($bytes, 0, 5) !== '%PDF-' || strlen($bytes) > 5 * 1024 * 1024) { throw new RuntimeException('Ungültige PDF-Ausgabe.'); }
        return $bytes;
    }

    public static function logo(int $id): string {
        if (!$id) { return ''; }
        $path = get_attached_file($id);
        if (!$path || !is_file($path) || filesize($path) > 1024 * 1024) { return ''; }
        $info = @getimagesize($path);
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || $info[0] * $info[1] > 4000000) { return ''; }
        return 'data:' . $info['mime'] . ';base64,' . base64_encode(file_get_contents($path));
    }

    public static function html(array $data, array $brand, string $reference): string {
        ob_start(); require RF_DIR . 'templates/pdf.php'; return ob_get_clean();
    }
}
