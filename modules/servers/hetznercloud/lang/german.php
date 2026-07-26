<?php
/** Hetzner Cloud module - Deutsch. Fehlende Schlüssel fallen auf Englisch zurück. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Übersicht';
$_LANG['tab_graphs'] = 'Diagramme';
$_LANG['tab_backups'] = 'Backups';
$_LANG['tab_snapshots'] = 'Snapshots';
$_LANG['tab_network'] = 'Netzwerk';
$_LANG['tab_firewall'] = 'Firewall';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Rescue';
$_LANG['tab_rebuild'] = 'Neu aufsetzen';
$_LANG['tab_ssh'] = 'SSH-Keys';
$_LANG['tab_history'] = 'Verlauf';
$_LANG['tab_settings'] = 'Einstellungen';

$_LANG['status_running'] = 'Läuft';
$_LANG['status_off'] = 'Ausgeschaltet';
$_LANG['status_starting'] = 'Startet';
$_LANG['status_stopping'] = 'Wird beendet';
$_LANG['status_initializing'] = 'Wird initialisiert';
$_LANG['status_migrating'] = 'Wird migriert';
$_LANG['status_rebuilding'] = 'Wird neu aufgesetzt';
$_LANG['status_deleting'] = 'Wird gelöscht';
$_LANG['status_unknown'] = 'Unbekannt';
$_LANG['status_provisioning'] = 'Wird bereitgestellt';

$_LANG['server_name'] = 'Servername';
$_LANG['server_id'] = 'Server-ID';
$_LANG['ip_address'] = 'IP-Adresse';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Private IP';
$_LANG['operating_system'] = 'Betriebssystem';
$_LANG['server_type'] = 'Servertyp';
$_LANG['location'] = 'Standort';
$_LANG['datacenter'] = 'Rechenzentrum';
$_LANG['created'] = 'Erstellt';
$_LANG['status'] = 'Status';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Arbeitsspeicher';
$_LANG['disk'] = 'Festplatte';
$_LANG['architecture'] = 'Architektur';
$_LANG['price'] = 'Preis';
$_LANG['protection'] = 'Schutz';

$_LANG['power'] = 'Energie';
$_LANG['power_on'] = 'Einschalten';
$_LANG['power_off'] = 'Ausschalten';
$_LANG['shutdown'] = 'Herunterfahren';
$_LANG['reboot'] = 'Neu starten';
$_LANG['reset'] = 'Reset';
$_LANG['console'] = 'Konsole';
$_LANG['open_console'] = 'Konsole öffnen';

$_LANG['access_info'] = 'Zugangsdaten';
$_LANG['username'] = 'Benutzername';
$_LANG['password'] = 'Passwort';
$_LANG['show'] = 'Anzeigen';
$_LANG['hide'] = 'Verbergen';
$_LANG['copy'] = 'Kopieren';
$_LANG['copied'] = 'In die Zwischenablage kopiert';
$_LANG['reset_password'] = 'Root-Passwort zurücksetzen';
$_LANG['ssh_command'] = 'SSH-Befehl';
$_LANG['password_reset_done'] = 'Ein neues Root-Passwort wurde erzeugt und im Dienst gespeichert.';
$_LANG['password_hidden'] = 'Verborgen';

$_LANG['cpu_usage'] = 'CPU-Auslastung';
$_LANG['disk_io'] = 'Festplatten-I/O';
$_LANG['network_traffic'] = 'Netzwerkverkehr';
$_LANG['range_1h'] = '1 Stunde';
$_LANG['range_24h'] = '24 Stunden';
$_LANG['range_7d'] = '7 Tage';
$_LANG['range_30d'] = '30 Tage';
$_LANG['no_data'] = 'Für diesen Zeitraum liegen keine Daten vor.';
$_LANG['read'] = 'Lesen';
$_LANG['write'] = 'Schreiben';
$_LANG['inbound'] = 'Eingehend';
$_LANG['outbound'] = 'Ausgehend';

$_LANG['bandwidth'] = 'Traffic-Verbrauch';
$_LANG['included_traffic'] = 'Inklusiv-Traffic';
$_LANG['outgoing_traffic'] = 'Upload (ausgehend)';
$_LANG['incoming_traffic'] = 'Download (eingehend)';
$_LANG['traffic_used'] = 'Verbraucht';
$_LANG['traffic_remaining'] = 'Verbleibend';
$_LANG['traffic_period'] = 'Aktueller Abrechnungszeitraum';
$_LANG['traffic_overage'] = 'Der Inklusiv-Traffic dieses Zeitraums ist aufgebraucht.';

$_LANG['backups'] = 'Automatische Backups';
$_LANG['backup_intro'] = 'Hetzner hält bis zu sieben rollierende Backups vor. Backups kosten 20 % Aufschlag auf den Serverpreis.';
$_LANG['enable_backups'] = 'Backups aktivieren';
$_LANG['disable_backups'] = 'Backups deaktivieren';
$_LANG['backups_enabled'] = 'Backups sind aktiviert';
$_LANG['backups_disabled'] = 'Backups sind deaktiviert';
$_LANG['backup_window'] = 'Backup-Fenster';
$_LANG['no_backups'] = 'Es wurden noch keine Backups erstellt.';
$_LANG['confirm_disable_backups'] = 'Beim Deaktivieren werden alle vorhandenen Backups gelöscht. Fortfahren?';

$_LANG['snapshots'] = 'Snapshots';
$_LANG['snapshot_intro'] = 'Ein Snapshot ist ein vollständiges Abbild des Servers, das jederzeit zurückgespielt werden kann.';
$_LANG['create_snapshot'] = 'Snapshot erstellen';
$_LANG['snapshot_name'] = 'Snapshot-Name';
$_LANG['no_snapshots'] = 'Keine Snapshots vorhanden.';
$_LANG['restore'] = 'Wiederherstellen';
$_LANG['size'] = 'Größe';
$_LANG['limit_reached'] = 'Das Limit von {limit} für diesen Dienst ist erreicht.';
$_LANG['confirm_restore'] = 'Beim Wiederherstellen werden alle Daten auf dem Server durch dieses Abbild ersetzt. Fortfahren?';

$_LANG['floating_ips'] = 'Floating IPs';
$_LANG['floating_ip_intro'] = 'Floating IPs lassen sich zwischen Servern verschieben und überstehen ein Neuaufsetzen.';
$_LANG['create_floating_ip'] = 'Floating IP bestellen';
$_LANG['assign'] = 'Zuweisen';
$_LANG['unassign'] = 'Zuweisung aufheben';
$_LANG['no_floating_ips'] = 'Diesem Dienst sind keine Floating IPs zugewiesen.';
$_LANG['protocol'] = 'Protokoll';
$_LANG['assigned'] = 'Zugewiesen';
$_LANG['unassigned'] = 'Nicht zugewiesen';

$_LANG['reverse_dns'] = 'Reverse DNS';
$_LANG['rdns_intro'] = 'PTR-Eintrag für Ihre IPv4- und IPv6-Adressen setzen.';
$_LANG['hostname'] = 'Hostname';
$_LANG['set_rdns'] = 'PTR speichern';
$_LANG['rdns_saved'] = 'Reverse-DNS-Eintrag aktualisiert.';

$_LANG['networks'] = 'Private Netzwerke';
$_LANG['network_intro'] = 'Private Netzwerke verbinden Ihre Server über ein isoliertes 10.x-Netz.';
$_LANG['create_network'] = 'Netzwerk erstellen';
$_LANG['network_name'] = 'Netzwerkname';
$_LANG['ip_range'] = 'IP-Bereich';
$_LANG['subnets'] = 'Subnetze';
$_LANG['add_subnet'] = 'Subnetz hinzufügen';
$_LANG['routes'] = 'Routen';
$_LANG['add_route'] = 'Route hinzufügen';
$_LANG['destination'] = 'Ziel';
$_LANG['gateway'] = 'Gateway';
$_LANG['network_zone'] = 'Netzwerkzone';
$_LANG['attach'] = 'Server verbinden';
$_LANG['detach'] = 'Server trennen';
$_LANG['no_networks'] = 'Keine privaten Netzwerke vorhanden.';
$_LANG['attached'] = 'Verbunden';
$_LANG['not_attached'] = 'Nicht verbunden';

$_LANG['firewalls'] = 'Firewalls';
$_LANG['firewall_intro'] = 'Firewalls greifen vor dem Server, blockierter Traffic erreicht ihn gar nicht erst.';
$_LANG['create_firewall'] = 'Firewall erstellen';
$_LANG['firewall_name'] = 'Firewall-Name';
$_LANG['rules'] = 'Regeln';
$_LANG['add_rule'] = 'Regel hinzufügen';
$_LANG['direction'] = 'Richtung';
$_LANG['port'] = 'Port';
$_LANG['source_ips'] = 'Quell-IPs';
$_LANG['destination_ips'] = 'Ziel-IPs';
$_LANG['apply_to_server'] = 'Auf Server anwenden';
$_LANG['remove_from_server'] = 'Vom Server entfernen';
$_LANG['no_firewalls'] = 'Keine Firewalls vorhanden.';
$_LANG['applied'] = 'Angewendet';
$_LANG['not_applied'] = 'Nicht angewendet';
$_LANG['description'] = 'Beschreibung';
$_LANG['any_port'] = 'beliebig';

$_LANG['iso'] = 'ISO-Abbild';
$_LANG['iso_intro'] = 'Binden Sie ein ISO ein, um vom Installationsmedium zu starten. Der Server bootet vom ISO vor der Festplatte.';
$_LANG['mount_iso'] = 'Einbinden';
$_LANG['unmount_iso'] = 'Aushängen';
$_LANG['mounted_iso'] = 'Aktuell eingebunden';
$_LANG['no_iso'] = 'Kein ISO eingebunden.';
$_LANG['search_iso'] = 'ISO-Abbilder suchen';
$_LANG['iso_mounted_notice'] = 'Ein ISO ist eingebunden. Starten Sie den Server neu, damit davon gebootet wird.';

$_LANG['rescue_mode'] = 'Rescue-System';
$_LANG['rescue_intro'] = 'Das Rescue-System startet ein kleines Linux über das Netzwerk, um eine defekte Installation zu reparieren. Es gilt nur für den nächsten Start.';
$_LANG['enable_rescue'] = 'Rescue-System aktivieren';
$_LANG['disable_rescue'] = 'Rescue-System deaktivieren';
$_LANG['rescue_enabled'] = 'Rescue-System ist für den nächsten Start scharf geschaltet';
$_LANG['rescue_password'] = 'Rescue-Passwort';
$_LANG['rescue_type'] = 'Rescue-System';
$_LANG['rescue_reboot_hint'] = 'Starten Sie den Server jetzt neu, um in das Rescue-System zu booten.';

$_LANG['rebuild'] = 'Server neu aufsetzen';
$_LANG['rebuild_intro'] = 'Den Server aus einem frischen Abbild neu installieren.';
$_LANG['select_os'] = 'Betriebssystem auswählen';
$_LANG['rebuild_warning'] = 'Beim Neuaufsetzen werden alle Daten gelöscht. Das lässt sich nicht rückgängig machen.';
$_LANG['rebuild_started'] = 'Neuaufsetzen gestartet. Der Server ist in wenigen Minuten wieder erreichbar.';
$_LANG['type_confirm'] = 'Zur Bestätigung {word} eingeben';

$_LANG['change_package'] = 'Tarif wechseln';
$_LANG['current_plan'] = 'Aktueller Tarif';
$_LANG['new_plan'] = 'Neuer Tarif';
$_LANG['upgrade_disk'] = 'Festplatte mit vergrößern (nicht umkehrbar)';
$_LANG['change_type_warning'] = 'Der Server wird für den Wechsel ausgeschaltet. Eine vergrößerte Festplatte lässt sich später nicht verkleinern.';
$_LANG['change_package_done'] = 'Tarif erfolgreich gewechselt.';

$_LANG['ssh_keys'] = 'SSH-Keys';
$_LANG['ssh_intro'] = 'Hier hinterlegte SSH-Keys können beim Neuaufsetzen ausgerollt werden.';
$_LANG['add_ssh_key'] = 'SSH-Key hinzufügen';
$_LANG['key_name'] = 'Key-Name';
$_LANG['public_key'] = 'Öffentlicher Schlüssel';
$_LANG['no_ssh_keys'] = 'Für diesen Dienst sind keine SSH-Keys gespeichert.';
$_LANG['fingerprint'] = 'Fingerabdruck';
$_LANG['ssh_key_order_label'] = 'Öffentlicher SSH-Schlüssel (optional)';

$_LANG['history'] = 'Aufgabenverlauf';
$_LANG['command'] = 'Befehl';
$_LANG['progress'] = 'Fortschritt';
$_LANG['started'] = 'Gestartet';
$_LANG['finished'] = 'Beendet';
$_LANG['no_history'] = 'Noch keine Aufgaben aufgezeichnet.';

$_LANG['loading'] = 'Wird geladen';
$_LANG['save'] = 'Speichern';
$_LANG['cancel'] = 'Abbrechen';
$_LANG['close'] = 'Schließen';
$_LANG['confirm'] = 'Bestätigen';
$_LANG['delete'] = 'Löschen';
$_LANG['create'] = 'Erstellen';
$_LANG['yes'] = 'Ja';
$_LANG['no'] = 'Nein';
$_LANG['enabled'] = 'Aktiviert';
$_LANG['disabled'] = 'Deaktiviert';
$_LANG['error'] = 'Fehler';
$_LANG['success'] = 'Erfolgreich';
$_LANG['warning'] = 'Warnung';
$_LANG['none'] = 'Keine';
$_LANG['name'] = 'Name';
$_LANG['type'] = 'Typ';
$_LANG['refresh'] = 'Aktualisieren';
$_LANG['search'] = 'Suchen';
$_LANG['actions'] = 'Aktionen';
$_LANG['working'] = 'In Arbeit';
$_LANG['are_you_sure'] = 'Sind Sie sicher?';
$_LANG['confirm_delete'] = '{name} löschen? Das lässt sich nicht rückgängig machen.';
$_LANG['feature_disabled'] = 'Diese Funktion ist in Ihrem Produkt nicht verfügbar.';
$_LANG['server_missing'] = 'Diesem Dienst ist noch kein Hetzner-Server zugeordnet. Bitte wenden Sie sich an den Support.';
$_LANG['request_failed'] = 'Die Anfrage ist fehlgeschlagen. Bitte erneut versuchen.';
$_LANG['session_expired'] = 'Ihre Sitzung ist abgelaufen. Seite neu laden und erneut versuchen.';
$_LANG['locked_notice'] = 'Auf dem Server läuft noch eine andere Aufgabe. Bitte warten.';
$_LANG['auto_refresh'] = 'Automatisch aktualisieren';
$_LANG['last_updated'] = 'Zuletzt aktualisiert';
$_LANG['just_now'] = 'gerade eben';
$_LANG['optional'] = 'optional';

/* Add-ons */
$_LANG['tab_addons'] = 'Zusatzoptionen';
$_LANG['addons_intro'] = 'Zusatzleistungen werden als konfigurierbare Optionen Ihres Produkts verkauft. Wählen Sie aus, WHMCS stellt die Änderung in Rechnung.';
$_LANG['addons_buy'] = 'Zusatzoptionen buchen';
$_LANG['addons_none'] = 'Für dieses Produkt sind keine Zusatzoptionen eingerichtet.';
$_LANG['addons_current'] = 'Aktuell';
$_LANG['addons_options'] = 'Verfügbar';
$_LANG['addons_included'] = 'inklusive';
$_LANG['addons_per_unit'] = 'pro Einheit';
$_LANG['per_month'] = 'Pro Monat';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 Minuten';
$_LANG['range_6h'] = '6 Stunden';
$_LANG['network_packets'] = 'Netzwerkpakete';
$_LANG['disk_iops'] = 'Festplatten-IOPS';
$_LANG['average'] = 'Ø';
$_LANG['peak'] = 'Spitze';
$_LANG['table_view'] = 'Tabellenansicht';
$_LANG['timestamp'] = 'Zeit';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100 %';
