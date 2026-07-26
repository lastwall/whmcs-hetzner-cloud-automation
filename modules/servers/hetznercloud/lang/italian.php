<?php
/** Hetzner Cloud module - Italiano. Le chiavi mancanti ricadono sull'inglese. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Panoramica';
$_LANG['tab_graphs'] = 'Grafici';
$_LANG['tab_backups'] = 'Backup';
$_LANG['tab_snapshots'] = 'Snapshot';
$_LANG['tab_network'] = 'Rete';
$_LANG['tab_firewall'] = 'Firewall';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Ripristino';
$_LANG['tab_rebuild'] = 'Reinstalla';
$_LANG['tab_ssh'] = 'Chiavi SSH';
$_LANG['tab_history'] = 'Cronologia';
$_LANG['tab_settings'] = 'Impostazioni';

$_LANG['status_running'] = 'In esecuzione';
$_LANG['status_off'] = 'Spento';
$_LANG['status_starting'] = 'Avvio in corso';
$_LANG['status_stopping'] = 'Arresto in corso';
$_LANG['status_initializing'] = 'Inizializzazione';
$_LANG['status_migrating'] = 'Migrazione';
$_LANG['status_rebuilding'] = 'Reinstallazione';
$_LANG['status_deleting'] = 'Eliminazione';
$_LANG['status_unknown'] = 'Sconosciuto';
$_LANG['status_provisioning'] = 'Provisioning';

$_LANG['server_name'] = 'Nome del server';
$_LANG['server_id'] = 'ID server';
$_LANG['ip_address'] = 'Indirizzo IP';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'IP privato';
$_LANG['operating_system'] = 'Sistema operativo';
$_LANG['server_type'] = 'Tipo di server';
$_LANG['location'] = 'Posizione';
$_LANG['datacenter'] = 'Datacenter';
$_LANG['created'] = 'Creato';
$_LANG['status'] = 'Stato';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Memoria';
$_LANG['disk'] = 'Disco';
$_LANG['architecture'] = 'Architettura';
$_LANG['price'] = 'Prezzo';
$_LANG['protection'] = 'Protezione';

$_LANG['power'] = 'Alimentazione';
$_LANG['power_on'] = 'Accendi';
$_LANG['power_off'] = 'Spegni';
$_LANG['shutdown'] = 'Arresto pulito';
$_LANG['reboot'] = 'Riavvia';
$_LANG['reset'] = 'Reset';
$_LANG['console'] = 'Console';
$_LANG['open_console'] = 'Apri console';

$_LANG['access_info'] = 'Dati di accesso';
$_LANG['username'] = 'Utente';
$_LANG['password'] = 'Password';
$_LANG['show'] = 'Mostra';
$_LANG['hide'] = 'Nascondi';
$_LANG['copy'] = 'Copia';
$_LANG['copied'] = 'Copiato negli appunti';
$_LANG['reset_password'] = 'Reimposta la password di root';
$_LANG['ssh_command'] = 'Comando SSH';
$_LANG['password_reset_done'] = 'È stata generata una nuova password di root e salvata nel servizio.';
$_LANG['password_hidden'] = 'Nascosta';

$_LANG['cpu_usage'] = 'Utilizzo CPU';
$_LANG['disk_io'] = 'I/O disco';
$_LANG['network_traffic'] = 'Traffico di rete';
$_LANG['range_1h'] = '1 ora';
$_LANG['range_24h'] = '24 ore';
$_LANG['range_7d'] = '7 giorni';
$_LANG['range_30d'] = '30 giorni';
$_LANG['no_data'] = 'Nessun dato per questo periodo.';
$_LANG['read'] = 'Lettura';
$_LANG['write'] = 'Scrittura';
$_LANG['inbound'] = 'In entrata';
$_LANG['outbound'] = 'In uscita';

$_LANG['bandwidth'] = 'Consumo di traffico';
$_LANG['included_traffic'] = 'Traffico incluso';
$_LANG['outgoing_traffic'] = 'Upload (in uscita)';
$_LANG['incoming_traffic'] = 'Download (in entrata)';
$_LANG['traffic_used'] = 'Utilizzato';
$_LANG['traffic_remaining'] = 'Residuo';
$_LANG['traffic_period'] = 'Periodo di fatturazione corrente';
$_LANG['traffic_overage'] = 'Il traffico incluso di questo periodo è esaurito.';

$_LANG['backups'] = 'Backup automatici';
$_LANG['backup_intro'] = 'Hetzner conserva fino a sette backup a rotazione. Attivarli aggiunge il 20 % al prezzo del server.';
$_LANG['enable_backups'] = 'Attiva i backup';
$_LANG['disable_backups'] = 'Disattiva i backup';
$_LANG['backups_enabled'] = 'I backup sono attivi';
$_LANG['backups_disabled'] = 'I backup sono disattivati';
$_LANG['backup_window'] = 'Finestra di backup';
$_LANG['no_backups'] = 'Non è ancora stato creato alcun backup.';
$_LANG['confirm_disable_backups'] = 'La disattivazione elimina tutti i backup esistenti. Continuare?';

$_LANG['snapshots'] = 'Snapshot';
$_LANG['snapshot_intro'] = 'Uno snapshot è un\'immagine completa del server, ripristinabile in qualsiasi momento.';
$_LANG['create_snapshot'] = 'Crea snapshot';
$_LANG['snapshot_name'] = 'Nome dello snapshot';
$_LANG['no_snapshots'] = 'Non hai snapshot.';
$_LANG['restore'] = 'Ripristina';
$_LANG['size'] = 'Dimensione';
$_LANG['limit_reached'] = 'Hai raggiunto il limite di {limit} per questo servizio.';
$_LANG['confirm_restore'] = 'Il ripristino sovrascrive tutti i file del server con questa immagine. Continuare?';

$_LANG['floating_ips'] = 'IP flottanti';
$_LANG['floating_ip_intro'] = 'Le IP flottanti si spostano tra i tuoi server e sopravvivono a una reinstallazione.';
$_LANG['create_floating_ip'] = 'Richiedi IP flottante';
$_LANG['assign'] = 'Assegna';
$_LANG['unassign'] = 'Rimuovi assegnazione';
$_LANG['no_floating_ips'] = 'Nessuna IP flottante assegnata a questo servizio.';
$_LANG['protocol'] = 'Protocollo';
$_LANG['assigned'] = 'Assegnata';
$_LANG['unassigned'] = 'Non assegnata';

$_LANG['reverse_dns'] = 'DNS inverso';
$_LANG['rdns_intro'] = 'Imposta il record PTR per i tuoi indirizzi IPv4 e IPv6.';
$_LANG['hostname'] = 'Hostname';
$_LANG['set_rdns'] = 'Salva PTR';
$_LANG['rdns_saved'] = 'Record DNS inverso aggiornato.';

$_LANG['networks'] = 'Reti private';
$_LANG['network_intro'] = 'Le reti private collegano i tuoi server su una rete 10.x isolata.';
$_LANG['create_network'] = 'Crea rete';
$_LANG['network_name'] = 'Nome della rete';
$_LANG['ip_range'] = 'Intervallo IP';
$_LANG['subnets'] = 'Sottoreti';
$_LANG['add_subnet'] = 'Aggiungi sottorete';
$_LANG['routes'] = 'Rotte';
$_LANG['add_route'] = 'Aggiungi rotta';
$_LANG['destination'] = 'Destinazione';
$_LANG['gateway'] = 'Gateway';
$_LANG['network_zone'] = 'Zona di rete';
$_LANG['attach'] = 'Collega server';
$_LANG['detach'] = 'Scollega server';
$_LANG['no_networks'] = 'Non hai reti private.';
$_LANG['attached'] = 'Collegato';
$_LANG['not_attached'] = 'Non collegato';

$_LANG['firewalls'] = 'Firewall';
$_LANG['firewall_intro'] = 'Il firewall agisce prima del server: il traffico bloccato non lo raggiunge mai.';
$_LANG['create_firewall'] = 'Crea firewall';
$_LANG['firewall_name'] = 'Nome del firewall';
$_LANG['rules'] = 'Regole';
$_LANG['add_rule'] = 'Aggiungi regola';
$_LANG['direction'] = 'Direzione';
$_LANG['port'] = 'Porta';
$_LANG['source_ips'] = 'IP di origine';
$_LANG['destination_ips'] = 'IP di destinazione';
$_LANG['apply_to_server'] = 'Applica al server';
$_LANG['remove_from_server'] = 'Rimuovi dal server';
$_LANG['no_firewalls'] = 'Non hai firewall.';
$_LANG['applied'] = 'Applicato';
$_LANG['not_applied'] = 'Non applicato';
$_LANG['description'] = 'Descrizione';
$_LANG['any_port'] = 'qualsiasi';

$_LANG['iso'] = 'Immagine ISO';
$_LANG['iso_intro'] = 'Monta una ISO per avviare da un supporto di installazione. Il server avvia dalla ISO prima del disco.';
$_LANG['mount_iso'] = 'Monta';
$_LANG['unmount_iso'] = 'Smonta';
$_LANG['mounted_iso'] = 'Attualmente montata';
$_LANG['no_iso'] = 'Nessuna ISO montata.';
$_LANG['search_iso'] = 'Cerca immagini ISO';
$_LANG['iso_mounted_notice'] = 'Una ISO è montata. Riavvia il server per avviarlo dall\'immagine.';

$_LANG['rescue_mode'] = 'Modalità ripristino';
$_LANG['rescue_intro'] = 'La modalità ripristino avvia un piccolo Linux dalla rete per riparare un\'installazione danneggiata. Vale solo per il prossimo avvio.';
$_LANG['enable_rescue'] = 'Attiva modalità ripristino';
$_LANG['disable_rescue'] = 'Disattiva modalità ripristino';
$_LANG['rescue_enabled'] = 'La modalità ripristino è pronta per il prossimo avvio';
$_LANG['rescue_password'] = 'Password di ripristino';
$_LANG['rescue_type'] = 'Sistema di ripristino';
$_LANG['rescue_reboot_hint'] = 'Riavvia ora il server per entrare nel sistema di ripristino.';

$_LANG['rebuild'] = 'Reinstalla il server';
$_LANG['rebuild_intro'] = 'Reinstalla il server da un\'immagine nuova.';
$_LANG['select_os'] = 'Seleziona un sistema operativo';
$_LANG['rebuild_warning'] = 'La reinstallazione cancella tutti i file del server. Operazione irreversibile.';
$_LANG['rebuild_started'] = 'Reinstallazione avviata. Il server sarà di nuovo disponibile tra pochi minuti.';
$_LANG['type_confirm'] = 'Digita {word} per confermare';

$_LANG['change_package'] = 'Cambia piano';
$_LANG['current_plan'] = 'Piano attuale';
$_LANG['new_plan'] = 'Nuovo piano';
$_LANG['upgrade_disk'] = 'Aumenta anche il disco (irreversibile)';
$_LANG['change_type_warning'] = 'Il server verrà spento durante il cambio. L\'aumento del disco non è reversibile.';
$_LANG['change_package_done'] = 'Piano cambiato con successo.';

$_LANG['ssh_keys'] = 'Chiavi SSH';
$_LANG['ssh_intro'] = 'Le chiavi SSH aggiunte qui possono essere installate durante una reinstallazione.';
$_LANG['add_ssh_key'] = 'Aggiungi chiave SSH';
$_LANG['key_name'] = 'Nome della chiave';
$_LANG['public_key'] = 'Chiave pubblica';
$_LANG['no_ssh_keys'] = 'Nessuna chiave SSH salvata per questo servizio.';
$_LANG['fingerprint'] = 'Impronta';
$_LANG['ssh_key_order_label'] = 'Chiave pubblica SSH (facoltativa)';

$_LANG['history'] = 'Cronologia attività';
$_LANG['command'] = 'Comando';
$_LANG['progress'] = 'Avanzamento';
$_LANG['started'] = 'Inizio';
$_LANG['finished'] = 'Fine';
$_LANG['no_history'] = 'Nessuna attività registrata finora.';

$_LANG['loading'] = 'Caricamento';
$_LANG['save'] = 'Salva';
$_LANG['cancel'] = 'Annulla';
$_LANG['close'] = 'Chiudi';
$_LANG['confirm'] = 'Conferma';
$_LANG['delete'] = 'Elimina';
$_LANG['create'] = 'Crea';
$_LANG['yes'] = 'Sì';
$_LANG['no'] = 'No';
$_LANG['enabled'] = 'Attivo';
$_LANG['disabled'] = 'Disattivato';
$_LANG['error'] = 'Errore';
$_LANG['success'] = 'Operazione riuscita';
$_LANG['warning'] = 'Attenzione';
$_LANG['none'] = 'Nessuno';
$_LANG['name'] = 'Nome';
$_LANG['type'] = 'Tipo';
$_LANG['refresh'] = 'Aggiorna';
$_LANG['search'] = 'Cerca';
$_LANG['actions'] = 'Azioni';
$_LANG['working'] = 'In corso';
$_LANG['are_you_sure'] = 'Sei sicuro?';
$_LANG['confirm_delete'] = 'Eliminare {name}? Operazione irreversibile.';
$_LANG['feature_disabled'] = 'Questa funzione non è disponibile nel tuo prodotto.';
$_LANG['server_missing'] = 'A questo servizio non è ancora associato un server Hetzner. Contatta il supporto.';
$_LANG['request_failed'] = 'Richiesta non riuscita. Riprova.';
$_LANG['session_expired'] = 'La sessione è scaduta. Ricarica la pagina e riprova.';
$_LANG['locked_notice'] = 'Su questo server è ancora in corso un\'altra attività. Attendi.';
$_LANG['auto_refresh'] = 'Aggiornamento automatico';
$_LANG['last_updated'] = 'Ultimo aggiornamento';
$_LANG['just_now'] = 'adesso';
$_LANG['optional'] = 'facoltativo';

/* Add-ons */
$_LANG['tab_addons'] = 'Componenti aggiuntivi';
$_LANG['addons_intro'] = 'Gli extra sono venduti come opzioni configurabili del tuo prodotto. Scegli e WHMCS fattura la modifica.';
$_LANG['addons_buy'] = 'Acquista o modifica i componenti';
$_LANG['addons_none'] = 'Nessun componente aggiuntivo configurato per questo prodotto.';
$_LANG['addons_current'] = 'Hai';
$_LANG['addons_options'] = 'Disponibili';
$_LANG['addons_included'] = 'incluso';
$_LANG['addons_per_unit'] = 'per unità';
$_LANG['per_month'] = 'Al mese';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minuti';
$_LANG['range_6h'] = '6 ore';
$_LANG['network_packets'] = 'Pacchetti di rete';
$_LANG['disk_iops'] = 'IOPS del disco';
$_LANG['average'] = 'media';
$_LANG['peak'] = 'picco';
$_LANG['table_view'] = 'Vista tabella';
$_LANG['timestamp'] = 'Ora';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100 %';
