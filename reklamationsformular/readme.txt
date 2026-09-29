=== Reklamationsformular ===
Contributors: reklamationsformular
Tags: reklamation, formular, pdf
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Eigenständiges Kundenformular für Reklamationen mit mehreren Artikelpositionen, PDF, E-Mail und geschütztem Archiv.

== Installation ==

1. Den Ordner `reklamationsformular` als ZIP packen oder die fertige ZIP hochladen und das Plugin aktivieren.
2. Unter Reklamationen > Einstellungen Unternehmensname, Service-E-Mail, optional Logo und Akzentfarbe eintragen.
3. Service-E-Mail prüfen, Formular freischalten und speichern.
4. Eine WordPress-Seite mit dem Shortcode `[reklamationsformular]` veröffentlichen.
5. Eine Test-Reklamation absenden und beide E-Mails inklusive PDF-Anhang prüfen.

Das Plugin benötigt PHP 7.4 oder neuer sowie DOM und mbstring. Dompdf und seine Abhängigkeiten sind im Plugin enthalten.

== Funktionen ==

Das Formular erfasst Vorname, Nachname, optionale Ruf- und Kundennummer, E-Mail-Adresse, Auftragsnummer/RE-Nr., bis zu 20 defekte Artikelpositionen und eine eindeutige Auswahl zur Rückerstattung auf das ursprüngliche Zahlungskonto.

Nach erfolgreicher Übermittlung werden die Reklamation und das erzeugte PDF in einer eigenen WordPress-Datenbanktabelle gespeichert. Kunden erhalten eine E-Mail-Kopie und einen 30 Minuten gültigen Download. Die Service-E-Mail enthält das PDF und setzt die Kundenadresse als Reply-To.

Im WordPress-Menü Reklamationen stehen Suche, Detailansicht, Versandstatus, erneuter Versand, PDF-Download und endgültige Sammellöschung zur Verfügung. Deaktivierung und Deinstallation löschen keine Reklamationen.

== Automatische Updates über GitHub ==

Version 1.1.0 oder neuer muss einmalig als Plugin-ZIP hochgeladen werden. Danach kann unter Plugins > Installierte Plugins beim Reklamationsformular die automatische Aktualisierung aktiviert werden. Manuelle Aktualisierung und die normale WordPress-Updateprüfung bleiben verfügbar.

Das Plugin prüft ungefähr alle zwölf Stunden die öffentlichen Release-Metadaten unter `https://github.com/marcelDevParadise/reklamationsformular/releases/latest/download/update.json`. Angeboten werden nur stabile Versionen mit passender Release-ZIP sowie kompatiblen WordPress- und PHP-Anforderungen. Ein GitHub-Konto oder Zugriffstoken wird auf dem Webserver nicht benötigt.

Bei der Updateprüfung erhält GitHub technisch bedingt die Server-IP und die üblichen HTTP-Verbindungsdaten. Reklamationen, Kundendaten und PDFs werden nicht übertragen. Einstellungen und Archiv bleiben bei Updates erhalten.

== Datenschutz und externe Dienste ==

Das Plugin verwendet keine externen Captcha-, PDF- oder Formulardienste. Spam-Schutz erfolgt durch Honeypot, signierte kurzlebige Sitzung, Mindestwartezeit, Herkunftsprüfung und IP-basiertes Rate-Limit mit gesalzenem Hash. Die IP-Adresse wird nicht im Reklamationsarchiv gespeichert.

Der Mailversand verwendet WordPress `wp_mail`. Mailanbieter oder SMTP-Plugins können eigene externe Dienste verwenden. Die tatsächliche E-Mail-Zustellung und die Datenschutzhinweise müssen vor Live-Nutzung geprüft werden.

== Changelog ==

= 1.1.1 =
Mehrere Shortcode-Instanzen auf derselben Seite funktionieren unabhängig voneinander, beispielsweise ein Popup und ein Formular im Seiteninhalt.

= 1.1.0 =
Öffentliche GitHub-Updates über den normalen WordPress-Updater. Release-Metadaten, Paketname sowie WordPress- und PHP-Anforderungen werden vor einem Update geprüft.

= 1.0.2 =
Auftragsnummern müssen mit PS, A-PV, AST oder RE beginnen. Optionale Kundennummern müssen mit KN oder K beginnen.

= 1.0.1 =
Installationspaket für WordPress-Hosting unter Linux korrigiert.

= 1.0.0 =
Erste Version mit öffentlichem Formular, dynamischen Artikelpositionen, serverseitiger Validierung, PDF, Kunden-/Service-Mail und geschütztem Backend-Archiv.
