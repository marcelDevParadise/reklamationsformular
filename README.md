# Reklamationsformular

WordPress-Plugin im Unterordner `reklamationsformular`.

## Verwendung

- Plugin aktivieren.
- Unter **Reklamationen > Einstellungen** konfigurieren und freischalten.
- Shortcode `[reklamationsformular]` in eine WordPress-Seite einfügen.
- Vor der Live-Nutzung E-Mail-Zustellung, PDF-Anhang, Datenschutzhinweise und mobile Darstellung mit einer Test-Reklamation prüfen.

Das Plugin zielt auf WordPress 6.6+ und PHP 7.4+.

## GitHub-Updates

Der Updater erwartet öffentliche Releases im Repository `marcelDevParadise/reklamationsformular`. Das Release muss eine passende Plugin-ZIP, `update.json` und `SHA256SUMS.txt` enthalten. Mit `php scripts/release.php` werden diese Dateien lokal erzeugt; ein Tag `vX.Y.Z` löst den Release-Workflow aus.

Version 1.1.0 oder neuer wird einmalig manuell installiert. Anschließend können automatische Updates im WordPress-Pluginbereich aktiviert werden.
