<?php
if (!defined('ABSPATH')) { exit; }

final class RF_Admin {
    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('admin_post_rf_archive_action', [self::class, 'action']);
    }

    public static function menu(): void {
        add_menu_page('Reklamationen', 'Reklamationen', 'manage_rf_complaints', 'rf-archive', [self::class, 'archive'], 'dashicons-warning', 58);
        add_submenu_page('rf-archive', 'Einstellungen', 'Einstellungen', 'manage_options', 'rf-settings', [self::class, 'settings']);
    }

    public static function assets(): void {
        if (!in_array(sanitize_key($_GET['page'] ?? ''), ['rf-settings', 'rf-archive'], true)) { return; }
        wp_enqueue_style('rf-admin', RF_URL . 'assets/admin.css', [], RF_VERSION);
        wp_enqueue_script('rf-admin', RF_URL . 'assets/admin.js', [], RF_VERSION, true);
        if (($_GET['page'] ?? '') === 'rf-settings') { wp_enqueue_media(); }
    }

    public static function register(): void { register_setting('rf_settings_group', 'rf_settings', ['type' => 'array', 'sanitize_callback' => [self::class, 'sanitize']]); }

    public static function sanitize($input): array {
        $old = RF_Plugin::settings(); if (!is_array($input)) { return $old; } $out = $old;
        foreach (['company' => 150, 'customer_subject' => 200, 'service_subject' => 200, 'customer_body' => 5000, 'service_body' => 5000] as $field => $limit) {
            $value = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
            if ($value === '' || mb_strlen($value) > $limit) { add_settings_error('rf_settings', $field, 'Bitte alle Texte ausfüllen und die Zeichenlimits beachten.'); continue; }
            $out[$field] = substr($field, -5) === '_body' ? sanitize_textarea_field($value) : sanitize_text_field($value);
        }
        $email = is_string($input['service_email'] ?? null) ? sanitize_email($input['service_email']) : '';
        if (!is_email($email)) { add_settings_error('rf_settings', 'email', 'Bitte eine gültige Service-E-Mail-Adresse angeben.'); } else { $out['service_email'] = $email; }
        $out['accent'] = sanitize_hex_color(is_string($input['accent'] ?? null) ? $input['accent'] : '') ?: $old['accent'];
        $logo = absint($input['logo_id'] ?? 0);
        if ($logo && !RF_PDF::logo($logo)) { add_settings_error('rf_settings', 'logo', 'Bitte ein PNG- oder JPG-Logo bis 1 MB und maximal 4 Millionen Pixel auswählen.'); } else { $out['logo_id'] = $logo; }
        $out['enabled'] = !empty($input['enabled']) && is_email($email);
        return $out;
    }

    public static function settings(): void {
        if (!current_user_can('manage_options')) { return; } $s = RF_Plugin::settings();
        ?>
        <div class="wrap rf-admin"><h1>Reklamationsformular · Einstellungen</h1>
        <p>Formular auf einer Seite einbinden: <code>[reklamationsformular]</code></p>
        <p>Plugin-Updates kommen aus den öffentlichen <a href="<?= esc_url(RF_Updater::REPOSITORY . '/releases') ?>" target="_blank" rel="noopener noreferrer">GitHub-Releases</a>. Unter <a href="<?= esc_url(admin_url('plugins.php')) ?>">Plugins</a> können automatische Updates aktiviert oder Updates manuell geprüft werden.</p><?php settings_errors('rf_settings'); ?>
        <form action="options.php" method="post"><?php settings_fields('rf_settings_group'); ?><table class="form-table" role="presentation"><tbody>
        <tr><th><label for="rf-enabled">Formular freischalten</label></th><td><label><input id="rf-enabled" name="rf_settings[enabled]" type="checkbox" value="1" <?php checked($s['enabled']); ?>> Öffentliches Formular aktivieren. Die Service-E-Mail-Adresse ist geprüft.</label></td></tr>
        <tr><th><label for="rf-company">Unternehmensname</label></th><td><input class="regular-text" id="rf-company" name="rf_settings[company]" maxlength="150" required value="<?= esc_attr($s['company']) ?>"></td></tr>
        <tr><th>Logo</th><td><input type="hidden" id="rf-logo-id" name="rf_settings[logo_id]" value="<?= (int) $s['logo_id'] ?>"><div id="rf-logo-preview"><?php if ($s['logo_id']) { echo wp_get_attachment_image((int) $s['logo_id'], 'thumbnail'); } ?></div><button class="button" type="button" data-logo-select>Logo auswählen</button> <button class="button" type="button" data-logo-remove>Entfernen</button><p class="description">PNG oder JPG, bis 1 MB, maximal 4 Millionen Pixel.</p></td></tr>
        <tr><th><label for="rf-accent">Akzentfarbe</label></th><td><input type="color" id="rf-accent" name="rf_settings[accent]" value="<?= esc_attr($s['accent']) ?>"></td></tr>
        <tr><th><label for="rf-service-email">Service-E-Mail</label></th><td><input type="email" class="regular-text" id="rf-service-email" name="rf_settings[service_email]" required value="<?= esc_attr($s['service_email']) ?>"><p class="description">Hierhin gehen neue Reklamationen. Antworten gehen dank Reply-To an den Kunden.</p></td></tr>
        <?php foreach (['customer' => 'Kundenkopie', 'service' => 'Service-Nachricht'] as $key => $label): ?>
        <tr><th colspan="2"><h2><?= esc_html($label) ?></h2></th></tr>
        <tr><th><label for="rf-<?= esc_attr($key) ?>-subject">Betreff</label></th><td><input class="large-text" id="rf-<?= esc_attr($key) ?>-subject" name="rf_settings[<?= esc_attr($key) ?>_subject]" maxlength="200" required value="<?= esc_attr($s[$key . '_subject']) ?>"></td></tr>
        <tr><th><label for="rf-<?= esc_attr($key) ?>-body">Nachricht</label></th><td><textarea class="large-text" rows="7" id="rf-<?= esc_attr($key) ?>-body" name="rf_settings[<?= esc_attr($key) ?>_body]" maxlength="5000" required><?= esc_textarea($s[$key . '_body']) ?></textarea><p class="description">Platzhalter: <code>{vorgang}</code>, <code>{name}</code>, <code>{vorname}</code>, <code>{nachname}</code>, <code>{email}</code>, <code>{rufnummer}</code>, <code>{kundennummer}</code>, <code>{auftragsnummer}</code>, <code>{artikel}</code>, <code>{einverstaendnis}</code>, <code>{unternehmen}</code>.</p></td></tr>
        <?php endforeach; ?></tbody></table><p>Gespeicherte PDFs, Empfänger und E-Mail-Texte werden durch spätere Änderungen nicht verändert.</p><?php submit_button(); ?></form></div>
        <?php
    }

    private static function status(string $value): string { return ['pending' => 'Ausstehend', 'sending' => 'Versand läuft / Ergebnis offen', 'handed_off' => 'An Mailversand übergeben', 'failed' => 'Versand fehlgeschlagen'][$value] ?? 'Unbekannt'; }
    private static function download_url(int $id): string { return wp_nonce_url(add_query_arg(['action' => 'rf_download', 'id' => $id], admin_url('admin-post.php')), 'rf_download_' . $id); }

    public static function archive(): void {
        if (!current_user_can('manage_rf_complaints')) { return; }
        if (!empty($_GET['view'])) { self::detail(absint($_GET['view'])); return; }
        global $wpdb;
        $search = isset($_GET['s']) && is_string($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $page = max(1, absint($_GET['paged'] ?? 1)); $table = RF_Store::table(); $where = '1=1'; $params = [];
        if ($search !== '') { $like = '%' . $wpdb->esc_like($search) . '%'; $where .= ' AND (reference LIKE %s OR order_number LIKE %s OR customer_number LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)'; $params = [$like, $like, $like, $like, $like]; }
        $condition = $params ? $wpdb->prepare($where, $params) : $where;
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $condition");
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id,reference,created_at,order_number,customer_number,customer_name,customer_email,customer_status,service_status FROM $table WHERE $condition ORDER BY id DESC LIMIT 20 OFFSET %d", ($page - 1) * 20));
        ?>
        <div class="wrap rf-admin"><h1>Reklamationen <span class="rf-count"><?= $total ?></span></h1>
        <?php if (isset($_GET['deleted'])): ?><div class="notice notice-success"><p><?= absint($_GET['deleted']) ?> Reklamation(en) gelöscht.</p></div><?php endif; ?>
        <form method="get" class="rf-filters"><input type="hidden" name="page" value="rf-archive"><label>Suche <input type="search" name="s" value="<?= esc_attr($search) ?>" placeholder="Name, E-Mail, Auftrag, Vorgang"></label><button class="button">Filtern</button><a href="<?= esc_url(admin_url('admin.php?page=rf-archive')) ?>">Zurücksetzen</a></form>
        <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" data-archive-form><input type="hidden" name="action" value="rf_archive_action"><input type="hidden" name="operation" value="delete"><?php wp_nonce_field('rf_archive_action'); ?>
        <div class="rf-bulk"><strong data-selection-count>0 ausgewählt</strong><button type="submit" class="button rf-delete" disabled>Ausgewählte löschen</button><span>Die Auswahl gilt für diese Ergebnisseite.</span></div>
        <div class="rf-table-scroll"><table class="wp-list-table widefat fixed striped"><thead><tr><td class="check-column"><input type="checkbox" data-select-all aria-label="Alle auswählen"></td><th>Vorgang / Datum</th><th>Auftrag / Kunde</th><th>Kunde</th><th>Kundenkopie</th><th>Service</th><th>PDF</th></tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="7">Keine Reklamationen gefunden.</td></tr><?php endif; foreach ($rows as $row): ?><tr>
        <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?= (int) $row->id ?>" aria-label="<?= esc_attr($row->reference) ?> auswählen"></th>
        <td><a href="<?= esc_url(add_query_arg(['page' => 'rf-archive', 'view' => $row->id], admin_url('admin.php'))) ?>"><strong><?= esc_html($row->reference) ?></strong></a><br><?= esc_html(get_date_from_gmt($row->created_at, 'd.m.Y H:i')) ?></td>
        <td><?= esc_html($row->order_number) ?><?php if ($row->customer_number): ?><br><small>Kd. <?= esc_html($row->customer_number) ?></small><?php endif; ?></td><td><?= esc_html($row->customer_name) ?><br><a href="mailto:<?= esc_attr($row->customer_email) ?>"><?= esc_html($row->customer_email) ?></a></td>
        <td><?= esc_html(self::status($row->customer_status)) ?></td><td><?= esc_html(self::status($row->service_status)) ?></td><td><a class="button" href="<?= esc_url(self::download_url((int) $row->id)) ?>">PDF</a></td></tr><?php endforeach; ?>
        </tbody></table></div></form>
        <?php if ($total > 20): ?><div class="tablenav"><div class="tablenav-pages"><?= paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => (int) ceil($total / 20)]) ?></div></div><?php endif; ?></div>
        <?php
    }

    private static function detail(int $id): void {
        $row = RF_Store::get($id); if (!$row) { wp_die('Reklamation nicht gefunden.'); } $data = json_decode($row->data, true);
        ?>
        <div class="wrap rf-admin"><p><a href="<?= esc_url(admin_url('admin.php?page=rf-archive')) ?>">← Zum Archiv</a></p><h1><?= esc_html($row->reference) ?></h1>
        <div class="rf-admin-card"><dl class="rf-detail-list"><dt>Eingang</dt><dd><?= esc_html(get_date_from_gmt($row->created_at, 'd.m.Y H:i')) ?></dd><dt>Name</dt><dd><?= esc_html($data['first_name'] . ' ' . $data['last_name']) ?></dd><dt>E-Mail</dt><dd><a href="mailto:<?= esc_attr($data['email']) ?>"><?= esc_html($data['email']) ?></a></dd><dt>Rufnummer</dt><dd><?= esc_html($data['phone'] ?: '—') ?></dd><dt>Kundennummer</dt><dd><?= esc_html($data['customer_number'] ?: '—') ?></dd><dt>Auftragsnummer/RE-Nr.</dt><dd><?= esc_html($data['order_number']) ?></dd><dt>Einverständnis</dt><dd><?= esc_html(RF_Validation::agreement_label($data['refund_agreement'])) ?></dd></dl>
        <h2>Defekte Artikel</h2><table class="widefat striped"><thead><tr><th>Artikel</th><th>Anzahl</th></tr></thead><tbody><?php foreach ($data['items'] as $item): ?><tr><td><?= esc_html($item['article']) ?></td><td><?= (int) $item['quantity'] ?></td></tr><?php endforeach; ?></tbody></table>
        <p><a class="button button-primary" href="<?= esc_url(self::download_url($id)) ?>">PDF herunterladen</a></p></div>
        <div class="rf-admin-card"><h2>E-Mail-Versand</h2><p>Kundenkopie: <strong><?= esc_html(self::status($row->customer_status)) ?></strong><br>Service: <strong><?= esc_html(self::status($row->service_status)) ?></strong></p>
        <?php foreach (['customer' => 'Kundenkopie erneut senden', 'service' => 'Service-Nachricht erneut senden'] as $target => $label): if ($row->{$target . '_status'} === 'handed_off') { continue; } ?><form class="rf-inline" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>"><input type="hidden" name="action" value="rf_archive_action"><input type="hidden" name="operation" value="retry"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="target" value="<?= esc_attr($target) ?>"><?php wp_nonce_field('rf_archive_action'); ?><button class="button"><?= esc_html($label) ?></button></form><?php endforeach; ?></div></div>
        <?php
    }

    public static function action(): void {
        if (!current_user_can('manage_rf_complaints')) { wp_die('Keine Berechtigung.', '', ['response' => 403]); }
        check_admin_referer('rf_archive_action'); $operation = sanitize_key($_POST['operation'] ?? '');
        if ($operation === 'delete') {
            $deleted = RF_Store::remove(is_array($_POST['ids'] ?? null) ? wp_unslash($_POST['ids']) : []);
            wp_safe_redirect(add_query_arg(['page' => 'rf-archive', 'deleted' => $deleted], admin_url('admin.php'))); exit;
        }
        if ($operation === 'retry') {
            $id = absint($_POST['id'] ?? 0); $target = sanitize_key($_POST['target'] ?? ''); RF_Store::send($id, $target, true);
            wp_safe_redirect(add_query_arg(['page' => 'rf-archive', 'view' => $id], admin_url('admin.php'))); exit;
        }
        wp_die('Ungültige Aktion.', '', ['response' => 400]);
    }
}
