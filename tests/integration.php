<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

final class WP_Error {
    private $code; private $message; private $data;
    public function __construct($code = '', $message = '', $data = null) { $this->code = $code; $this->message = $message; $this->data = $data; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function is_email($value): bool { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }

require dirname(__DIR__) . '/reklamationsformular/includes/class-rf-validation.php';

$assertions = 0;
function check($condition, string $message): void {
    global $assertions; $assertions++;
    if (!$condition) { fwrite(STDERR, "Fehlgeschlagen: $message\n"); exit(1); }
}

$valid = [
    'first_name' => 'Erika', 'last_name' => 'Musterfrau', 'phone' => '+49 123 456',
    'customer_number' => 'K-123', 'email' => 'erika@example.test', 'order_number' => 'RE-2026-10',
    'items' => [['article' => 'Artikel A', 'quantity' => 2], ['article' => 'SKU-99', 'quantity' => 1]],
    'refund_agreement' => 'yes', 'privacy_confirmed' => true,
];

$result = RF_Validation::validate($valid);
check(!is_wp_error($result), 'gültige Reklamation wird angenommen');
check(count($result['items']) === 2, 'mehrere Artikel bleiben erhalten');
check($result['items'][0]['quantity'] === 2, 'Menge wird als Integer gespeichert');
check(RF_Validation::items_text($result['items']) === "2 × Artikel A\n1 × SKU-99", 'Artikel-Platzhalter ist lesbar');

foreach (['PS123', 'A-PV-456', 'AST789', 'RE-2026-10'] as $orderNumber) {
    $with_prefix = $valid; $with_prefix['order_number'] = $orderNumber;
    check(!is_wp_error(RF_Validation::validate($with_prefix)), 'gültiges Auftragspräfix wird angenommen: ' . $orderNumber);
}
foreach (['KN123', 'K-456'] as $customerNumber) {
    $with_prefix = $valid; $with_prefix['customer_number'] = $customerNumber;
    check(!is_wp_error(RF_Validation::validate($with_prefix)), 'gültiges Kundenpräfix wird angenommen: ' . $customerNumber);
}

$missing = $valid; $missing['first_name'] = ''; $missing['email'] = 'falsch'; $missing['items'] = [];
$result = RF_Validation::validate($missing); $fields = $result->get_error_data()['fields'];
check(is_wp_error($result), 'ungültige Reklamation wird abgelehnt');
check(isset($fields['first_name']), 'fehlender Vorname wird gemeldet');
check(isset($fields['email']), 'ungültige E-Mail wird gemeldet');
check(isset($fields['items']), 'leere Artikelliste wird gemeldet');

$bad_prefixes = $valid; $bad_prefixes['order_number'] = 'AUF-123'; $bad_prefixes['customer_number'] = 'C-456';
$result = RF_Validation::validate($bad_prefixes); $fields = $result->get_error_data()['fields'];
check(isset($fields['order_number']), 'ungültiges Auftragspräfix wird abgelehnt');
check(isset($fields['customer_number']), 'ungültiges Kundenpräfix wird abgelehnt');

$bad_item = $valid; $bad_item['items'][0]['quantity'] = 0; $bad_item['items'][1]['article'] = '';
$result = RF_Validation::validate($bad_item); $fields = $result->get_error_data()['fields'];
check(isset($fields['item_0_quantity']), 'Menge unter 1 wird abgelehnt');
check(isset($fields['item_1_article']), 'leerer Artikel wird abgelehnt');

$bad_choice = $valid; $bad_choice['refund_agreement'] = 'maybe'; $bad_choice['privacy_confirmed'] = false;
$result = RF_Validation::validate($bad_choice); $fields = $result->get_error_data()['fields'];
check(isset($fields['refund_agreement']), 'unbekannte Rückerstattungsoption wird abgelehnt');
check(isset($fields['privacy_confirmed']), 'fehlende Datenschutzbestätigung wird abgelehnt');

$sanitized = $valid; $sanitized['first_name'] = '<b>Erika</b>';
$result = RF_Validation::validate($sanitized);
check($result['first_name'] === 'Erika', 'Textfelder werden bereinigt');
check(strpos(RF_Validation::agreement_label('no'), 'Nicht einverstanden') === 0, 'Nein-Auswahl wird eindeutig ausgegeben');

echo $assertions . " Assertions bestanden.\n";
