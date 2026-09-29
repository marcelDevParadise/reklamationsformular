<?php if (!defined('ABSPATH')) { exit; }
$instance = wp_unique_id('rf');
$rf_id = static function (string $suffix) use ($instance): string { return $instance . '-' . $suffix; };
?>
<section class="rf-app" data-instance="<?= esc_attr($instance) ?>" data-config="<?= esc_attr(wp_json_encode($config)) ?>" style="--rf-accent:<?= esc_attr($config['accent']) ?>" aria-label="Reklamationsformular">
    <div class="rf-heading"><span class="rf-eyebrow">REKLAMATION EINREICHEN</span><h2>Reklamationsformular</h2><p>Bitte gib deine Daten und ausschließlich die defekten Artikel an. Du erhältst nach dem Absenden eine PDF-Kopie per E-Mail.</p></div>
    <noscript><p>Bitte JavaScript aktivieren, um das Formular zu verwenden.</p></noscript>
    <div class="rf-status" role="status" aria-live="polite">Formular wird geladen …</div>
    <form class="rf-form" novalidate hidden>
        <div class="rf-trap" aria-hidden="true"><label>Website<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
        <fieldset><legend>Deine Kontaktdaten</legend><p class="rf-muted">Mit <span aria-hidden="true">*</span> markierte Felder sind erforderlich.</p>
            <div class="rf-grid">
                <div class="rf-field" data-field="first_name"><label for="<?= esc_attr($rf_id('first-name')) ?>">Vorname <span>*</span></label><input id="<?= esc_attr($rf_id('first-name')) ?>" name="first_name" maxlength="100" autocomplete="given-name" required><small class="rf-error" data-error-for="first_name"></small></div>
                <div class="rf-field" data-field="last_name"><label for="<?= esc_attr($rf_id('last-name')) ?>">Nachname <span>*</span></label><input id="<?= esc_attr($rf_id('last-name')) ?>" name="last_name" maxlength="100" autocomplete="family-name" required><small class="rf-error" data-error-for="last_name"></small></div>
                <div class="rf-field" data-field="phone"><label for="<?= esc_attr($rf_id('phone')) ?>">Rufnummer <span>optional</span></label><input id="<?= esc_attr($rf_id('phone')) ?>" name="phone" type="tel" maxlength="60" autocomplete="tel"><small class="rf-error" data-error-for="phone"></small></div>
                <div class="rf-field" data-field="customer_number"><label for="<?= esc_attr($rf_id('customer-number')) ?>">Kundennummer <span>optional</span></label><input id="<?= esc_attr($rf_id('customer-number')) ?>" name="customer_number" maxlength="100" pattern="(?:KN|K).*" title="Die Kundennummer muss mit KN oder K beginnen." aria-describedby="<?= esc_attr($rf_id('customer-number-hint')) ?> <?= esc_attr($rf_id('customer-number-error')) ?>"><small id="<?= esc_attr($rf_id('customer-number-hint')) ?>">Beginnt mit KN oder K.</small><small class="rf-error" id="<?= esc_attr($rf_id('customer-number-error')) ?>" data-error-for="customer_number"></small></div>
                <div class="rf-field" data-field="email"><label for="<?= esc_attr($rf_id('email')) ?>">E-Mail-Adresse <span>*</span></label><input id="<?= esc_attr($rf_id('email')) ?>" name="email" type="email" maxlength="254" autocomplete="email" required><small class="rf-error" data-error-for="email"></small></div>
                <div class="rf-field" data-field="order_number"><label for="<?= esc_attr($rf_id('order-number')) ?>">Auftragsnummer/RE-Nr. <span>*</span></label><input id="<?= esc_attr($rf_id('order-number')) ?>" name="order_number" maxlength="100" placeholder="PS-, A-PV-, AST- oder RE-Nr." pattern="(?:PS|A-PV|AST|RE).*" title="Die Auftragsnummer muss mit PS, A-PV, AST oder RE beginnen." aria-describedby="<?= esc_attr($rf_id('order-number-hint')) ?> <?= esc_attr($rf_id('order-number-error')) ?>" required><small id="<?= esc_attr($rf_id('order-number-hint')) ?>">Beginnt mit PS, A-PV, AST oder RE.</small><small class="rf-error" id="<?= esc_attr($rf_id('order-number-error')) ?>" data-error-for="order_number"></small></div>
            </div>
        </fieldset>

        <fieldset data-field="items"><legend>Auflistung der defekten Artikel</legend><p class="rf-muted">Bitte nur defekte Artikel angeben. Es können bis zu 20 Positionen erfasst werden.</p>
            <div class="rf-items" data-items></div>
            <button class="rf-add" type="button" data-add-item><span aria-hidden="true">＋</span> Weiteren Artikel hinzufügen</button>
            <small class="rf-error" data-error-for="items"></small>
        </fieldset>

        <fieldset class="rf-agreement" data-field="refund_agreement"><legend>Einverständniserklärung <span>*</span></legend>
            <label><input type="radio" name="refund_agreement" value="yes" required> Ich erkläre mich ausdrücklich damit einverstanden, dass eine etwaige Rückerstattung auf das Konto gesendet wird, welches zur Zahlung verwendet wurde.</label>
            <label><input type="radio" name="refund_agreement" value="no" required> Ich erkläre mich nicht damit einverstanden, dass eine etwaige Rückerstattung auf das Konto gesendet wird, welches zur Zahlung verwendet wurde.</label>
            <small class="rf-error" data-error-for="refund_agreement"></small>
        </fieldset>

        <aside class="rf-notice"><strong>Hinweis:</strong><p>Bitte gib nur die defekten Artikel im Reklamationsformular an. Angaben zu nicht defekten Artikeln können die Bearbeitungszeit um 48 Stunden verzögern.</p></aside>
        <div class="rf-field" data-field="privacy_confirmed"><label class="rf-check"><input type="checkbox" name="privacy_confirmed" required> Ich bestätige, dass meine Angaben zur Bearbeitung der Reklamation gespeichert und per E-Mail übermittelt werden dürfen. <span>*</span></label><small class="rf-error" data-error-for="privacy_confirmed"></small></div>
        <p class="rf-privacy">Weitere Informationen findest du in den <?php if (get_privacy_policy_url()): ?><a href="<?= esc_url(get_privacy_policy_url()) ?>" target="_blank" rel="noopener noreferrer">Datenschutzhinweisen</a><?php else: ?>Datenschutzhinweisen dieser Website<?php endif; ?>.</p>
        <button class="rf-submit" type="submit">Reklamation absenden</button>
    </form>
    <div class="rf-success" hidden tabindex="-1"><div class="rf-success-icon" aria-hidden="true">✓</div><h3>Deine Reklamation ist gespeichert.</h3><p class="rf-reference"></p><p class="rf-mail-message"></p><a class="rf-submit rf-download" rel="noreferrer">PDF herunterladen</a><p class="rf-muted rf-expiry"></p></div>
    <template data-item-template><div class="rf-item" data-item>
        <div class="rf-field rf-article"><label>Artikel <span>*</span></label><input data-article maxlength="200" placeholder="Artikelname oder Artikelnummer" required><small class="rf-error" data-article-error></small></div>
        <div class="rf-field rf-quantity"><label>Anzahl <span>*</span></label><select data-quantity required><?php for ($i = 1; $i <= 20; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?></select><small class="rf-error" data-quantity-error></small></div>
        <button class="rf-remove" type="button" data-remove-item aria-label="Artikel entfernen">×</button>
    </div></template>
</section>
