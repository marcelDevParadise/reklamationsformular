<?php
if (!defined('ABSPATH')) { exit; }

final class RF_Store {
    public static function table(): string { global $wpdb; return $wpdb->prefix . 'rf_complaints'; }

    public static function activate(bool $network_wide = false): void {
        if (is_multisite() && $network_wide) { wp_die('Bitte das Reklamationsformular für jede Website einzeln aktivieren.'); }
        $missing = array_filter(['dom', 'mbstring'], static function ($extension) { return !extension_loaded($extension); });
        if ($missing || !file_exists(RF_DIR . 'lib/dompdf/autoload.inc.php')) {
            wp_die('Das Reklamationsformular benötigt die PHP-Erweiterungen DOM und mbstring sowie das vollständige Plugin-ZIP mit Dompdf.');
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table(); $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reference varchar(50) NOT NULL,
            request_hash char(64) NOT NULL,
            payload_hash char(64) NOT NULL,
            created_at datetime NOT NULL,
            order_number varchar(100) NOT NULL,
            customer_number varchar(100) NOT NULL DEFAULT '',
            customer_name varchar(201) NOT NULL,
            customer_email varchar(254) NOT NULL,
            service_email varchar(254) NOT NULL,
            data longtext NOT NULL,
            pdf longtext NOT NULL,
            token_hash char(64) NOT NULL,
            token_expires bigint(20) NOT NULL,
            customer_status varchar(20) NOT NULL DEFAULT 'pending',
            service_status varchar(20) NOT NULL DEFAULT 'pending',
            customer_attempt datetime DEFAULT NULL,
            service_attempt datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_hash (request_hash),
            UNIQUE KEY reference (reference),
            KEY created_at (created_at),
            KEY order_number (order_number)
        ) $charset;");
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) { wp_die('Die Reklamationstabelle konnte nicht angelegt werden.'); }
        $role = get_role('administrator');
        if ($role) { $role->add_cap('manage_rf_complaints'); }
        update_option('rf_db_version', RF_VERSION, false);
    }

    public static function get(int $id): ?object { global $wpdb; return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id)); }
    public static function by_request(string $hash): ?object { global $wpdb; return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE request_hash = %s', $hash)); }
    public static function insert(array $row): int { global $wpdb; return $wpdb->insert(self::table(), $row) === false ? 0 : (int) $wpdb->insert_id; }
    public static function remove(array $ids): int {
        global $wpdb; $ids = array_values(array_filter(array_map('absint', $ids)));
        return $ids ? (int) $wpdb->query('DELETE FROM ' . self::table() . ' WHERE id IN (' . implode(',', $ids) . ')') : 0;
    }

    public static function send(int $id, string $target, bool $retry = false): void {
        if (!in_array($target, ['customer', 'service'], true)) { return; }
        global $wpdb;
        $table = self::table(); $status = $target . '_status'; $attempt = $target . '_attempt';
        $allowed = $retry ? "('pending','failed')" : "('pending')";
        $claimed = $wpdb->query($wpdb->prepare("UPDATE $table SET $status = 'sending', $attempt = %s WHERE id = %d AND ($status IN $allowed OR ($status = 'sending' AND $attempt < %s))", gmdate('Y-m-d H:i:s'), $id, gmdate('Y-m-d H:i:s', time() - 600)));
        if (!$claimed) { return; }
        $row = self::get($id); if (!$row) { return; }
        $data = json_decode($row->data, true); $settings = $data['_mail'];
        $replacements = [
            '{vorgang}' => $row->reference, '{name}' => $data['first_name'] . ' ' . $data['last_name'],
            '{vorname}' => $data['first_name'], '{nachname}' => $data['last_name'], '{email}' => $data['email'],
            '{rufnummer}' => $data['phone'], '{kundennummer}' => $data['customer_number'], '{auftragsnummer}' => $data['order_number'],
            '{artikel}' => RF_Validation::items_text($data['items']), '{einverstaendnis}' => RF_Validation::agreement_label($data['refund_agreement']),
            '{unternehmen}' => $data['_brand']['company'],
        ];
        $subject = strtr($settings[$target . '_subject'], $replacements);
        $body = strtr($settings[$target . '_body'], $replacements);
        $bytes = base64_decode($row->pdf, true);
        $attach = static function ($mailer) use ($bytes, $row): void { $mailer->addStringAttachment($bytes, $row->reference . '.pdf', 'base64', 'application/pdf'); };
        $ok = false;
        try {
            add_action('phpmailer_init', $attach, PHP_INT_MAX);
            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            if ($target === 'service' && is_email($data['email'])) { $headers[] = 'Reply-To: ' . sanitize_email($data['email']); }
            $ok = wp_mail($target === 'customer' ? $data['email'] : $row->service_email, sanitize_text_field($subject), $body, $headers);
        } catch (Throwable $error) { $ok = false; }
        finally { remove_action('phpmailer_init', $attach, PHP_INT_MAX); }
        $wpdb->update($table, [$status => $ok ? 'handed_off' : 'failed'], ['id' => $id]);
    }
}
