<?php
/** Hetzner Cloud module - Nederlands. Ontbrekende sleutels vallen terug op Engels. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Overzicht';
$_LANG['tab_graphs'] = 'Grafieken';
$_LANG['tab_backups'] = 'Back-ups';
$_LANG['tab_snapshots'] = 'Snapshots';
$_LANG['tab_network'] = 'Netwerk';
$_LANG['tab_firewall'] = 'Firewall';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Rescue';
$_LANG['tab_rebuild'] = 'Herinstalleren';
$_LANG['tab_ssh'] = 'SSH-sleutels';
$_LANG['tab_history'] = 'Geschiedenis';
$_LANG['tab_settings'] = 'Instellingen';

$_LANG['status_running'] = 'Actief';
$_LANG['status_off'] = 'Uitgeschakeld';
$_LANG['status_starting'] = 'Starten';
$_LANG['status_stopping'] = 'Stoppen';
$_LANG['status_initializing'] = 'Initialiseren';
$_LANG['status_migrating'] = 'Migreren';
$_LANG['status_rebuilding'] = 'Herinstalleren';
$_LANG['status_deleting'] = 'Verwijderen';
$_LANG['status_unknown'] = 'Onbekend';
$_LANG['status_provisioning'] = 'Wordt aangemaakt';

$_LANG['server_name'] = 'Servernaam';
$_LANG['server_id'] = 'Server-ID';
$_LANG['ip_address'] = 'IP-adres';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Privé-IP';
$_LANG['operating_system'] = 'Besturingssysteem';
$_LANG['server_type'] = 'Servertype';
$_LANG['location'] = 'Locatie';
$_LANG['datacenter'] = 'Datacenter';
$_LANG['created'] = 'Aangemaakt';
$_LANG['status'] = 'Status';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Geheugen';
$_LANG['disk'] = 'Schijf';
$_LANG['architecture'] = 'Architectuur';
$_LANG['price'] = 'Prijs';
$_LANG['protection'] = 'Beveiliging';

$_LANG['power'] = 'Stroom';
$_LANG['power_on'] = 'Inschakelen';
$_LANG['power_off'] = 'Uitschakelen';
$_LANG['shutdown'] = 'Netjes afsluiten';
$_LANG['reboot'] = 'Herstarten';
$_LANG['reset'] = 'Reset';
$_LANG['console'] = 'Console';
$_LANG['open_console'] = 'Console openen';

$_LANG['access_info'] = 'Toegangsgegevens';
$_LANG['username'] = 'Gebruikersnaam';
$_LANG['password'] = 'Wachtwoord';
$_LANG['show'] = 'Tonen';
$_LANG['hide'] = 'Verbergen';
$_LANG['copy'] = 'Kopiëren';
$_LANG['copied'] = 'Gekopieerd naar klembord';
$_LANG['reset_password'] = 'Root-wachtwoord opnieuw instellen';
$_LANG['ssh_command'] = 'SSH-opdracht';
$_LANG['password_reset_done'] = 'Er is een nieuw root-wachtwoord aangemaakt en bij je dienst opgeslagen.';
$_LANG['password_hidden'] = 'Verborgen';

$_LANG['cpu_usage'] = 'CPU-gebruik';
$_LANG['disk_io'] = 'Schijf-I/O';
$_LANG['network_traffic'] = 'Netwerkverkeer';
$_LANG['range_1h'] = '1 uur';
$_LANG['range_24h'] = '24 uur';
$_LANG['range_7d'] = '7 dagen';
$_LANG['range_30d'] = '30 dagen';
$_LANG['no_data'] = 'Geen gegevens voor deze periode.';
$_LANG['read'] = 'Lezen';
$_LANG['write'] = 'Schrijven';
$_LANG['inbound'] = 'Inkomend';
$_LANG['outbound'] = 'Uitgaand';

$_LANG['bandwidth'] = 'Dataverbruik';
$_LANG['included_traffic'] = 'Inbegrepen verkeer';
$_LANG['outgoing_traffic'] = 'Upload (uitgaand)';
$_LANG['incoming_traffic'] = 'Download (inkomend)';
$_LANG['traffic_used'] = 'Verbruikt';
$_LANG['traffic_remaining'] = 'Resterend';
$_LANG['traffic_period'] = 'Huidige factuurperiode';
$_LANG['traffic_overage'] = 'Het inbegrepen verkeer van deze periode is verbruikt.';

$_LANG['backups'] = 'Automatische back-ups';
$_LANG['backup_intro'] = 'Hetzner bewaart maximaal zeven roulerende back-ups. Inschakelen kost 20 % extra op de serverprijs.';
$_LANG['enable_backups'] = 'Back-ups inschakelen';
$_LANG['disable_backups'] = 'Back-ups uitschakelen';
$_LANG['backups_enabled'] = 'Back-ups staan aan';
$_LANG['backups_disabled'] = 'Back-ups staan uit';
$_LANG['backup_window'] = 'Back-upvenster';
$_LANG['no_backups'] = 'Er zijn nog geen back-ups gemaakt.';
$_LANG['confirm_disable_backups'] = 'Uitschakelen verwijdert alle bestaande back-ups. Doorgaan?';

$_LANG['snapshots'] = 'Snapshots';
$_LANG['snapshot_intro'] = 'Een snapshot is een volledige kopie van je server die je op elk moment kunt terugzetten.';
$_LANG['create_snapshot'] = 'Snapshot maken';
$_LANG['snapshot_name'] = 'Naam van de snapshot';
$_LANG['no_snapshots'] = 'Je hebt geen snapshots.';
$_LANG['restore'] = 'Terugzetten';
$_LANG['size'] = 'Grootte';
$_LANG['limit_reached'] = 'De limiet van {limit} voor deze dienst is bereikt.';
$_LANG['confirm_restore'] = 'Terugzetten overschrijft alle bestanden op de server met deze image. Doorgaan?';

$_LANG['floating_ips'] = 'Floating IP\'s';
$_LANG['floating_ip_intro'] = 'Floating IP\'s verplaats je tussen servers en overleven een herinstallatie.';
$_LANG['create_floating_ip'] = 'Floating IP bestellen';
$_LANG['assign'] = 'Toewijzen';
$_LANG['unassign'] = 'Toewijzing opheffen';
$_LANG['no_floating_ips'] = 'Geen floating IP\'s toegewezen aan deze dienst.';
$_LANG['protocol'] = 'Protocol';
$_LANG['assigned'] = 'Toegewezen';
$_LANG['unassigned'] = 'Niet toegewezen';

$_LANG['reverse_dns'] = 'Reverse DNS';
$_LANG['rdns_intro'] = 'Stel het PTR-record in voor je IPv4- en IPv6-adressen.';
$_LANG['hostname'] = 'Hostnaam';
$_LANG['set_rdns'] = 'PTR opslaan';
$_LANG['rdns_saved'] = 'Reverse-DNS-record bijgewerkt.';

$_LANG['networks'] = 'Privénetwerken';
$_LANG['network_intro'] = 'Met privénetwerken praten je servers via een afgeschermd 10.x-netwerk met elkaar.';
$_LANG['create_network'] = 'Netwerk aanmaken';
$_LANG['network_name'] = 'Netwerknaam';
$_LANG['ip_range'] = 'IP-bereik';
$_LANG['subnets'] = 'Subnetten';
$_LANG['add_subnet'] = 'Subnet toevoegen';
$_LANG['routes'] = 'Routes';
$_LANG['add_route'] = 'Route toevoegen';
$_LANG['destination'] = 'Bestemming';
$_LANG['gateway'] = 'Gateway';
$_LANG['network_zone'] = 'Netwerkzone';
$_LANG['attach'] = 'Server koppelen';
$_LANG['detach'] = 'Server loskoppelen';
$_LANG['no_networks'] = 'Je hebt geen privénetwerken.';
$_LANG['attached'] = 'Gekoppeld';
$_LANG['not_attached'] = 'Niet gekoppeld';

$_LANG['firewalls'] = 'Firewalls';
$_LANG['firewall_intro'] = 'Firewalls werken vóór je server: geblokkeerd verkeer bereikt hem nooit.';
$_LANG['create_firewall'] = 'Firewall aanmaken';
$_LANG['firewall_name'] = 'Firewallnaam';
$_LANG['rules'] = 'Regels';
$_LANG['add_rule'] = 'Regel toevoegen';
$_LANG['direction'] = 'Richting';
$_LANG['port'] = 'Poort';
$_LANG['source_ips'] = 'Bron-IP\'s';
$_LANG['destination_ips'] = 'Bestemmings-IP\'s';
$_LANG['apply_to_server'] = 'Toepassen op server';
$_LANG['remove_from_server'] = 'Van server verwijderen';
$_LANG['no_firewalls'] = 'Je hebt geen firewalls.';
$_LANG['applied'] = 'Toegepast';
$_LANG['not_applied'] = 'Niet toegepast';
$_LANG['description'] = 'Omschrijving';
$_LANG['any_port'] = 'alle';

$_LANG['iso'] = 'ISO-image';
$_LANG['iso_intro'] = 'Koppel een ISO om vanaf installatiemedia te starten. De server start eerst van de ISO en dan pas van de schijf.';
$_LANG['mount_iso'] = 'Koppelen';
$_LANG['unmount_iso'] = 'Ontkoppelen';
$_LANG['mounted_iso'] = 'Nu gekoppeld';
$_LANG['no_iso'] = 'Er is geen ISO gekoppeld.';
$_LANG['search_iso'] = 'ISO-images zoeken';
$_LANG['iso_mounted_notice'] = 'Er is een ISO gekoppeld. Herstart de server om ervan op te starten.';

$_LANG['rescue_mode'] = 'Rescue-modus';
$_LANG['rescue_intro'] = 'De rescue-modus start een klein Linux-systeem via het netwerk om een kapotte installatie te repareren. Geldt alleen voor de volgende start.';
$_LANG['enable_rescue'] = 'Rescue-modus inschakelen';
$_LANG['disable_rescue'] = 'Rescue-modus uitschakelen';
$_LANG['rescue_enabled'] = 'Rescue-modus staat klaar voor de volgende start';
$_LANG['rescue_password'] = 'Rescue-wachtwoord';
$_LANG['rescue_type'] = 'Rescue-systeem';
$_LANG['rescue_reboot_hint'] = 'Herstart de server nu om in het rescue-systeem te starten.';

$_LANG['rebuild'] = 'Server herinstalleren';
$_LANG['rebuild_intro'] = 'Installeer de server opnieuw vanaf een verse image.';
$_LANG['select_os'] = 'Kies een besturingssysteem';
$_LANG['rebuild_warning'] = 'Herinstalleren wist alle bestanden op de server. Dit kan niet ongedaan worden gemaakt.';
$_LANG['rebuild_started'] = 'Herinstallatie gestart. De server is over enkele minuten weer bereikbaar.';
$_LANG['type_confirm'] = 'Typ {word} om te bevestigen';

$_LANG['change_package'] = 'Pakket wijzigen';
$_LANG['current_plan'] = 'Huidig pakket';
$_LANG['new_plan'] = 'Nieuw pakket';
$_LANG['upgrade_disk'] = 'Ook de schijf vergroten (onomkeerbaar)';
$_LANG['change_type_warning'] = 'De server gaat tijdens de wijziging uit. Een grotere schijf kan later niet worden verkleind.';
$_LANG['change_package_done'] = 'Pakket gewijzigd.';

$_LANG['ssh_keys'] = 'SSH-sleutels';
$_LANG['ssh_intro'] = 'Hier toegevoegde SSH-sleutels kunnen bij een herinstallatie worden uitgerold.';
$_LANG['add_ssh_key'] = 'SSH-sleutel toevoegen';
$_LANG['key_name'] = 'Naam van de sleutel';
$_LANG['public_key'] = 'Publieke sleutel';
$_LANG['no_ssh_keys'] = 'Geen SSH-sleutels opgeslagen voor deze dienst.';
$_LANG['fingerprint'] = 'Vingerafdruk';
$_LANG['ssh_key_order_label'] = 'Publieke SSH-sleutel (optioneel)';

$_LANG['history'] = 'Takenoverzicht';
$_LANG['command'] = 'Opdracht';
$_LANG['progress'] = 'Voortgang';
$_LANG['started'] = 'Gestart';
$_LANG['finished'] = 'Voltooid';
$_LANG['no_history'] = 'Nog geen taken vastgelegd.';

$_LANG['loading'] = 'Laden';
$_LANG['save'] = 'Opslaan';
$_LANG['cancel'] = 'Annuleren';
$_LANG['close'] = 'Sluiten';
$_LANG['confirm'] = 'Bevestigen';
$_LANG['delete'] = 'Verwijderen';
$_LANG['create'] = 'Aanmaken';
$_LANG['yes'] = 'Ja';
$_LANG['no'] = 'Nee';
$_LANG['enabled'] = 'Ingeschakeld';
$_LANG['disabled'] = 'Uitgeschakeld';
$_LANG['error'] = 'Fout';
$_LANG['success'] = 'Gelukt';
$_LANG['warning'] = 'Let op';
$_LANG['none'] = 'Geen';
$_LANG['name'] = 'Naam';
$_LANG['type'] = 'Type';
$_LANG['refresh'] = 'Vernieuwen';
$_LANG['search'] = 'Zoeken';
$_LANG['actions'] = 'Acties';
$_LANG['working'] = 'Bezig';
$_LANG['are_you_sure'] = 'Weet je het zeker?';
$_LANG['confirm_delete'] = '{name} verwijderen? Dit kan niet ongedaan worden gemaakt.';
$_LANG['feature_disabled'] = 'Deze functie is niet beschikbaar in jouw product.';
$_LANG['server_missing'] = 'Aan deze dienst is nog geen Hetzner-server gekoppeld. Neem contact op met support.';
$_LANG['request_failed'] = 'Het verzoek is mislukt. Probeer het opnieuw.';
$_LANG['session_expired'] = 'Je sessie is verlopen. Herlaad de pagina en probeer het opnieuw.';
$_LANG['locked_notice'] = 'Er loopt nog een andere taak op deze server. Even geduld.';
$_LANG['auto_refresh'] = 'Automatisch vernieuwen';
$_LANG['last_updated'] = 'Laatst bijgewerkt';
$_LANG['just_now'] = 'zojuist';
$_LANG['optional'] = 'optioneel';

/* Add-ons */
$_LANG['tab_addons'] = 'Extra opties';
$_LANG['addons_intro'] = 'Extra\'s worden verkocht als configureerbare opties van je product. Kies wat je nodig hebt; WHMCS factureert de wijziging.';
$_LANG['addons_buy'] = 'Extra opties kopen of wijzigen';
$_LANG['addons_none'] = 'Voor dit product zijn geen extra opties ingesteld.';
$_LANG['addons_current'] = 'Je hebt';
$_LANG['addons_options'] = 'Beschikbaar';
$_LANG['addons_included'] = 'inbegrepen';
$_LANG['addons_per_unit'] = 'per stuk';
$_LANG['per_month'] = 'Per maand';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minuten';
$_LANG['range_6h'] = '6 uur';
$_LANG['network_packets'] = 'Netwerkpakketten';
$_LANG['disk_iops'] = 'Schijf-IOPS';
$_LANG['average'] = 'gem';
$_LANG['peak'] = 'piek';
$_LANG['table_view'] = 'Tabelweergave';
$_LANG['timestamp'] = 'Tijd';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100%';
