<?php
/** Hetzner Cloud module - Français. Les clés manquantes retombent sur l'anglais. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Aperçu';
$_LANG['tab_graphs'] = 'Graphiques';
$_LANG['tab_backups'] = 'Sauvegardes';
$_LANG['tab_snapshots'] = 'Snapshots';
$_LANG['tab_network'] = 'Réseau';
$_LANG['tab_firewall'] = 'Pare-feu';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Secours';
$_LANG['tab_rebuild'] = 'Réinstaller';
$_LANG['tab_ssh'] = 'Clés SSH';
$_LANG['tab_history'] = 'Historique';
$_LANG['tab_settings'] = 'Paramètres';

$_LANG['status_running'] = 'En marche';
$_LANG['status_off'] = 'Éteint';
$_LANG['status_starting'] = 'Démarrage';
$_LANG['status_stopping'] = 'Arrêt en cours';
$_LANG['status_initializing'] = 'Initialisation';
$_LANG['status_migrating'] = 'Migration';
$_LANG['status_rebuilding'] = 'Réinstallation';
$_LANG['status_deleting'] = 'Suppression';
$_LANG['status_unknown'] = 'Inconnu';
$_LANG['status_provisioning'] = 'Provisionnement';

$_LANG['server_name'] = 'Nom du serveur';
$_LANG['server_id'] = 'ID du serveur';
$_LANG['ip_address'] = 'Adresse IP';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'IP privée';
$_LANG['operating_system'] = 'Système d\'exploitation';
$_LANG['server_type'] = 'Type de serveur';
$_LANG['location'] = 'Localisation';
$_LANG['datacenter'] = 'Datacenter';
$_LANG['created'] = 'Créé le';
$_LANG['status'] = 'État';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Mémoire';
$_LANG['disk'] = 'Disque';
$_LANG['architecture'] = 'Architecture';
$_LANG['price'] = 'Prix';
$_LANG['protection'] = 'Protection';

$_LANG['power'] = 'Alimentation';
$_LANG['power_on'] = 'Démarrer';
$_LANG['power_off'] = 'Éteindre';
$_LANG['shutdown'] = 'Arrêter proprement';
$_LANG['reboot'] = 'Redémarrer';
$_LANG['reset'] = 'Réinitialiser';
$_LANG['console'] = 'Console';
$_LANG['open_console'] = 'Ouvrir la console';

$_LANG['access_info'] = 'Informations d\'accès';
$_LANG['username'] = 'Utilisateur';
$_LANG['password'] = 'Mot de passe';
$_LANG['show'] = 'Afficher';
$_LANG['hide'] = 'Masquer';
$_LANG['copy'] = 'Copier';
$_LANG['copied'] = 'Copié dans le presse-papiers';
$_LANG['reset_password'] = 'Réinitialiser le mot de passe root';
$_LANG['ssh_command'] = 'Commande SSH';
$_LANG['password_reset_done'] = 'Un nouveau mot de passe root a été généré et enregistré sur votre service.';
$_LANG['password_hidden'] = 'Masqué';

$_LANG['cpu_usage'] = 'Utilisation CPU';
$_LANG['disk_io'] = 'E/S disque';
$_LANG['network_traffic'] = 'Trafic réseau';
$_LANG['range_1h'] = '1 heure';
$_LANG['range_24h'] = '24 heures';
$_LANG['range_7d'] = '7 jours';
$_LANG['range_30d'] = '30 jours';
$_LANG['no_data'] = 'Aucune donnée pour cette période.';
$_LANG['read'] = 'Lecture';
$_LANG['write'] = 'Écriture';
$_LANG['inbound'] = 'Entrant';
$_LANG['outbound'] = 'Sortant';

$_LANG['bandwidth'] = 'Consommation de trafic';
$_LANG['included_traffic'] = 'Trafic inclus';
$_LANG['outgoing_traffic'] = 'Envoi (sortant)';
$_LANG['incoming_traffic'] = 'Réception (entrant)';
$_LANG['traffic_used'] = 'Consommé';
$_LANG['traffic_remaining'] = 'Restant';
$_LANG['traffic_period'] = 'Période de facturation en cours';
$_LANG['traffic_overage'] = 'Le trafic inclus de cette période est dépassé.';

$_LANG['backups'] = 'Sauvegardes automatiques';
$_LANG['backup_intro'] = 'Hetzner conserve jusqu\'à sept sauvegardes glissantes. Les activer ajoute 20 % au prix du serveur.';
$_LANG['enable_backups'] = 'Activer les sauvegardes';
$_LANG['disable_backups'] = 'Désactiver les sauvegardes';
$_LANG['backups_enabled'] = 'Les sauvegardes sont activées';
$_LANG['backups_disabled'] = 'Les sauvegardes sont désactivées';
$_LANG['backup_window'] = 'Fenêtre de sauvegarde';
$_LANG['no_backups'] = 'Aucune sauvegarde n\'a encore été créée.';
$_LANG['confirm_disable_backups'] = 'La désactivation supprime toutes les sauvegardes existantes. Continuer ?';

$_LANG['snapshots'] = 'Snapshots';
$_LANG['snapshot_intro'] = 'Un snapshot est une image complète du serveur, restaurable à tout moment.';
$_LANG['create_snapshot'] = 'Créer un snapshot';
$_LANG['snapshot_name'] = 'Nom du snapshot';
$_LANG['no_snapshots'] = 'Vous n\'avez aucun snapshot.';
$_LANG['restore'] = 'Restaurer';
$_LANG['size'] = 'Taille';
$_LANG['limit_reached'] = 'La limite de {limit} est atteinte pour ce service.';
$_LANG['confirm_restore'] = 'La restauration écrase tous les fichiers du serveur par cette image. Continuer ?';

$_LANG['floating_ips'] = 'IP flottantes';
$_LANG['floating_ip_intro'] = 'Les IP flottantes se déplacent entre vos serveurs et survivent à une réinstallation.';
$_LANG['create_floating_ip'] = 'Commander une IP flottante';
$_LANG['assign'] = 'Attribuer';
$_LANG['unassign'] = 'Détacher';
$_LANG['no_floating_ips'] = 'Aucune IP flottante attribuée à ce service.';
$_LANG['protocol'] = 'Protocole';
$_LANG['assigned'] = 'Attribuée';
$_LANG['unassigned'] = 'Non attribuée';

$_LANG['reverse_dns'] = 'DNS inverse';
$_LANG['rdns_intro'] = 'Définissez l\'enregistrement PTR de vos adresses IPv4 et IPv6.';
$_LANG['hostname'] = 'Nom d\'hôte';
$_LANG['set_rdns'] = 'Enregistrer le PTR';
$_LANG['rdns_saved'] = 'Enregistrement DNS inverse mis à jour.';

$_LANG['networks'] = 'Réseaux privés';
$_LANG['network_intro'] = 'Les réseaux privés font communiquer vos serveurs sur un réseau 10.x isolé.';
$_LANG['create_network'] = 'Créer un réseau';
$_LANG['network_name'] = 'Nom du réseau';
$_LANG['ip_range'] = 'Plage IP';
$_LANG['subnets'] = 'Sous-réseaux';
$_LANG['add_subnet'] = 'Ajouter un sous-réseau';
$_LANG['routes'] = 'Routes';
$_LANG['add_route'] = 'Ajouter une route';
$_LANG['destination'] = 'Destination';
$_LANG['gateway'] = 'Passerelle';
$_LANG['network_zone'] = 'Zone réseau';
$_LANG['attach'] = 'Attacher le serveur';
$_LANG['detach'] = 'Détacher le serveur';
$_LANG['no_networks'] = 'Vous n\'avez aucun réseau privé.';
$_LANG['attached'] = 'Attaché';
$_LANG['not_attached'] = 'Non attaché';

$_LANG['firewalls'] = 'Pare-feu';
$_LANG['firewall_intro'] = 'Le pare-feu agit en amont du serveur : le trafic bloqué ne l\'atteint jamais.';
$_LANG['create_firewall'] = 'Créer un pare-feu';
$_LANG['firewall_name'] = 'Nom du pare-feu';
$_LANG['rules'] = 'Règles';
$_LANG['add_rule'] = 'Ajouter une règle';
$_LANG['direction'] = 'Sens';
$_LANG['port'] = 'Port';
$_LANG['source_ips'] = 'IP sources';
$_LANG['destination_ips'] = 'IP destinations';
$_LANG['apply_to_server'] = 'Appliquer au serveur';
$_LANG['remove_from_server'] = 'Retirer du serveur';
$_LANG['no_firewalls'] = 'Vous n\'avez aucun pare-feu.';
$_LANG['applied'] = 'Appliqué';
$_LANG['not_applied'] = 'Non appliqué';
$_LANG['description'] = 'Description';
$_LANG['any_port'] = 'tous';

$_LANG['iso'] = 'Image ISO';
$_LANG['iso_intro'] = 'Montez une ISO pour démarrer sur un média d\'installation. Le serveur démarre sur l\'ISO avant le disque.';
$_LANG['mount_iso'] = 'Monter';
$_LANG['unmount_iso'] = 'Démonter';
$_LANG['mounted_iso'] = 'Actuellement montée';
$_LANG['no_iso'] = 'Aucune ISO montée.';
$_LANG['search_iso'] = 'Rechercher une image ISO';
$_LANG['iso_mounted_notice'] = 'Une ISO est montée. Redémarrez le serveur pour démarrer dessus.';

$_LANG['rescue_mode'] = 'Mode secours';
$_LANG['rescue_intro'] = 'Le mode secours démarre un petit système Linux par le réseau pour réparer une installation cassée. Il ne vaut que pour le prochain démarrage.';
$_LANG['enable_rescue'] = 'Activer le mode secours';
$_LANG['disable_rescue'] = 'Désactiver le mode secours';
$_LANG['rescue_enabled'] = 'Le mode secours est armé pour le prochain démarrage';
$_LANG['rescue_password'] = 'Mot de passe du mode secours';
$_LANG['rescue_type'] = 'Système de secours';
$_LANG['rescue_reboot_hint'] = 'Redémarrez le serveur maintenant pour entrer dans le système de secours.';

$_LANG['rebuild'] = 'Réinstaller le serveur';
$_LANG['rebuild_intro'] = 'Réinstaller le serveur depuis une image neuve.';
$_LANG['select_os'] = 'Choisissez un système d\'exploitation';
$_LANG['rebuild_warning'] = 'La réinstallation efface tous les fichiers du serveur. Action irréversible.';
$_LANG['rebuild_started'] = 'Réinstallation lancée. Le serveur sera de nouveau disponible dans quelques minutes.';
$_LANG['type_confirm'] = 'Saisissez {word} pour confirmer';

$_LANG['change_package'] = 'Changer d\'offre';
$_LANG['current_plan'] = 'Offre actuelle';
$_LANG['new_plan'] = 'Nouvelle offre';
$_LANG['upgrade_disk'] = 'Agrandir aussi le disque (irréversible)';
$_LANG['change_type_warning'] = 'Le serveur sera éteint pendant le changement. L\'agrandissement du disque est définitif.';
$_LANG['change_package_done'] = 'Offre changée avec succès.';

$_LANG['ssh_keys'] = 'Clés SSH';
$_LANG['ssh_intro'] = 'Les clés SSH ajoutées ici peuvent être déployées lors d\'une réinstallation.';
$_LANG['add_ssh_key'] = 'Ajouter une clé SSH';
$_LANG['key_name'] = 'Nom de la clé';
$_LANG['public_key'] = 'Clé publique';
$_LANG['no_ssh_keys'] = 'Aucune clé SSH enregistrée pour ce service.';
$_LANG['fingerprint'] = 'Empreinte';
$_LANG['ssh_key_order_label'] = 'Clé publique SSH (facultatif)';

$_LANG['history'] = 'Historique des tâches';
$_LANG['command'] = 'Commande';
$_LANG['progress'] = 'Progression';
$_LANG['started'] = 'Début';
$_LANG['finished'] = 'Fin';
$_LANG['no_history'] = 'Aucune tâche enregistrée pour l\'instant.';

$_LANG['loading'] = 'Chargement';
$_LANG['save'] = 'Enregistrer';
$_LANG['cancel'] = 'Annuler';
$_LANG['close'] = 'Fermer';
$_LANG['confirm'] = 'Confirmer';
$_LANG['delete'] = 'Supprimer';
$_LANG['create'] = 'Créer';
$_LANG['yes'] = 'Oui';
$_LANG['no'] = 'Non';
$_LANG['enabled'] = 'Activé';
$_LANG['disabled'] = 'Désactivé';
$_LANG['error'] = 'Erreur';
$_LANG['success'] = 'Succès';
$_LANG['warning'] = 'Attention';
$_LANG['none'] = 'Aucun';
$_LANG['name'] = 'Nom';
$_LANG['type'] = 'Type';
$_LANG['refresh'] = 'Actualiser';
$_LANG['search'] = 'Rechercher';
$_LANG['actions'] = 'Actions';
$_LANG['working'] = 'En cours';
$_LANG['are_you_sure'] = 'Confirmez-vous ?';
$_LANG['confirm_delete'] = 'Supprimer {name} ? Action irréversible.';
$_LANG['feature_disabled'] = 'Cette fonction n\'est pas disponible sur votre produit.';
$_LANG['server_missing'] = 'Aucun serveur Hetzner n\'est encore rattaché à ce service. Contactez le support.';
$_LANG['request_failed'] = 'La requête a échoué. Merci de réessayer.';
$_LANG['session_expired'] = 'Votre session a expiré. Rechargez la page et réessayez.';
$_LANG['locked_notice'] = 'Une autre tâche est encore en cours sur ce serveur. Patientez.';
$_LANG['auto_refresh'] = 'Actualisation auto';
$_LANG['last_updated'] = 'Dernière mise à jour';
$_LANG['just_now'] = 'à l\'instant';
$_LANG['optional'] = 'facultatif';

/* Add-ons */
$_LANG['tab_addons'] = 'Options';
$_LANG['addons_intro'] = 'Les suppléments sont vendus comme options configurables de votre produit. Choisissez, WHMCS facture la modification.';
$_LANG['addons_buy'] = 'Acheter ou modifier des options';
$_LANG['addons_none'] = 'Aucune option n\'est configurée pour ce produit.';
$_LANG['addons_current'] = 'Actuellement';
$_LANG['addons_options'] = 'Disponible';
$_LANG['addons_included'] = 'inclus';
$_LANG['addons_per_unit'] = 'par unité';
$_LANG['per_month'] = 'Par mois';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minutes';
$_LANG['range_6h'] = '6 heures';
$_LANG['network_packets'] = 'Paquets réseau';
$_LANG['disk_iops'] = 'IOPS disque';
$_LANG['average'] = 'moy';
$_LANG['peak'] = 'pic';
$_LANG['table_view'] = 'Vue tableau';
$_LANG['timestamp'] = 'Heure';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100 %';
