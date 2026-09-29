<?php
if (!defined('ABSPATH')) { exit; }

final class RF_Validation {
    public const FIELDS = [
        'first_name' => ['Vorname', 100, true],
        'last_name' => ['Nachname', 100, true],
        'phone' => ['Rufnummer', 60, false],
        'customer_number' => ['Kundennummer', 100, false],
        'email' => ['E-Mail-Adresse', 254, true],
        'order_number' => ['Auftragsnummer/RE-Nr.', 100, true],
    ];

    /** @return array|WP_Error */
    public static function validate(array $input) {
        $data = []; $errors = [];
        foreach (self::FIELDS as $key => $definition) {
            list($label, $limit, $required) = $definition;
            $raw = $input[$key] ?? '';
            if (!is_string($raw)) { $raw = ''; $errors[$key] = 'Bitte einen gültigen Wert eingeben.'; }
            $data[$key] = sanitize_text_field($raw);
            if (mb_strlen($data[$key]) > $limit) { $errors[$key] = 'Maximal ' . $limit . ' Zeichen sind erlaubt.'; }
            if ($required && $data[$key] === '') { $errors[$key] = $label . ' fehlt.'; }
        }
        if (!is_email($data['email'])) { $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.'; }
        if ($data['order_number'] !== '' && !preg_match('/\A(?:PS|A-PV|AST|RE)/', $data['order_number'])) {
            $errors['order_number'] = 'Die Auftragsnummer muss mit PS, A-PV, AST oder RE beginnen.';
        }
        if ($data['customer_number'] !== '' && !preg_match('/\A(?:KN|K)/', $data['customer_number'])) {
            $errors['customer_number'] = 'Die Kundennummer muss mit KN oder K beginnen.';
        }

        $raw_items = $input['items'] ?? null;
        $data['items'] = [];
        if (!is_array($raw_items) || !$raw_items || count($raw_items) > 20) {
            $errors['items'] = 'Bitte mindestens einen und höchstens 20 defekte Artikel angeben.';
        } else {
            foreach (array_values($raw_items) as $index => $item) {
                if (!is_array($item)) { $errors['items'] = 'Die Artikelliste ist ungültig.'; break; }
                $article = is_string($item['article'] ?? null) ? sanitize_text_field($item['article']) : '';
                $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
                if ($article === '' || mb_strlen($article) > 200) { $errors['item_' . $index . '_article'] = 'Bitte Artikelname oder Artikelnummer angeben (maximal 200 Zeichen).'; }
                if ($quantity === false) { $errors['item_' . $index . '_quantity'] = 'Bitte eine Menge zwischen 1 und 100 wählen.'; }
                $data['items'][] = ['article' => $article, 'quantity' => $quantity === false ? 0 : (int) $quantity];
            }
        }

        $agreement = is_string($input['refund_agreement'] ?? null) ? $input['refund_agreement'] : '';
        if (!in_array($agreement, ['yes', 'no'], true)) { $errors['refund_agreement'] = 'Bitte genau eine Option auswählen.'; }
        $data['refund_agreement'] = $agreement;

        if (($input['privacy_confirmed'] ?? false) !== true) { $errors['privacy_confirmed'] = 'Bitte die Datenschutzhinweise bestätigen.'; }
        $data['privacy_confirmed'] = true;

        if ($errors) { return new WP_Error('invalid_fields', 'Bitte prüfe die markierten Angaben.', ['status' => 422, 'fields' => $errors]); }
        return $data;
    }

    public static function agreement_label(string $value): string {
        return $value === 'yes'
            ? 'Einverstanden, dass eine etwaige Rückerstattung auf das für die Zahlung verwendete Konto gesendet wird.'
            : 'Nicht einverstanden, dass eine etwaige Rückerstattung auf das für die Zahlung verwendete Konto gesendet wird.';
    }

    public static function items_text(array $items): string {
        $lines = [];
        foreach ($items as $item) { $lines[] = $item['quantity'] . ' × ' . $item['article']; }
        return implode("\n", $lines);
    }
}
