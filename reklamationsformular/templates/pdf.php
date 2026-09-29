<?php if (!defined('ABSPATH')) { exit; }
$e = static function ($value) { return esc_html((string) $value); };
$accent = sanitize_hex_color($brand['accent']) ?: '#2878d0';
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><style>
@page{margin:40pt 42pt 48pt}body{font-family:'DejaVu Sans',sans-serif;color:#202a35;font-size:10pt;line-height:1.4}.header{border-bottom:3pt solid <?= $accent ?>;padding-bottom:10pt;margin-bottom:18pt}.logo{float:right;max-width:110pt;max-height:34pt}.company{font-weight:bold;color:<?= $accent ?>}h1{font-size:23pt;font-weight:normal;margin:7pt 0}.muted{color:#65717c;font-size:8.5pt}h2{font-size:10pt;color:<?= $accent ?>;margin:17pt 0 7pt}table{width:100%;border-collapse:collapse;table-layout:fixed}td,th{padding:6pt 8pt;border-bottom:1pt solid #e3e7ea;text-align:left;vertical-align:top;overflow-wrap:break-word}td.label{width:31%;color:#56636e}.box{margin-top:16pt;padding:12pt;border:1pt solid #dce3e8;background:#f6f8fa;page-break-inside:avoid}.box h2{margin-top:0}
</style></head><body>
<div class="header"><?php if (!empty($brand['logo'])): ?><img class="logo" src="<?= esc_attr($brand['logo']) ?>" alt=""><?php endif; ?><div class="company"><?= $e($brand['company']) ?></div><h1>Reklamationsformular</h1><div class="muted">Vorgang <?= $e($reference) ?> · Eingang <?= $e(wp_date('d.m.Y H:i')) ?></div></div>
<h2>01 · Kundendaten</h2><table>
<tr><td class="label">Name</td><td><?= $e($data['first_name'] . ' ' . $data['last_name']) ?></td></tr>
<?php if ($data['customer_number']): ?><tr><td class="label">Kundennummer</td><td><?= $e($data['customer_number']) ?></td></tr><?php endif; ?>
<tr><td class="label">E-Mail-Adresse</td><td><?= $e($data['email']) ?></td></tr>
<?php if ($data['phone']): ?><tr><td class="label">Rufnummer</td><td><?= $e($data['phone']) ?></td></tr><?php endif; ?>
<tr><td class="label">Auftragsnummer/RE-Nr.</td><td><?= $e($data['order_number']) ?></td></tr></table>
<h2>02 · Defekte Artikel</h2><table><thead><tr><th>Artikelname oder Artikelnummer</th><th style="width:18%">Anzahl</th></tr></thead><tbody><?php foreach ($data['items'] as $item): ?><tr><td><?= $e($item['article']) ?></td><td><?= (int) $item['quantity'] ?></td></tr><?php endforeach; ?></tbody></table>
<div class="box"><h2>03 · Einverständniserklärung</h2><p><?= $e(RF_Validation::agreement_label($data['refund_agreement'])) ?></p><p class="muted">Die Datenschutzhinweise zur Verarbeitung der Reklamation wurden bestätigt.</p></div>
</body></html>
