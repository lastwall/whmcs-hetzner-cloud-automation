<?php
/** Hetzner Cloud module - Polski. Brakujące klucze pochodzą z pliku angielskiego. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Przegląd';
$_LANG['tab_graphs'] = 'Wykresy';
$_LANG['tab_backups'] = 'Kopie zapasowe';
$_LANG['tab_snapshots'] = 'Migawki';
$_LANG['tab_network'] = 'Sieć';
$_LANG['tab_firewall'] = 'Zapora';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Tryb ratunkowy';
$_LANG['tab_rebuild'] = 'Przeinstalacja';
$_LANG['tab_ssh'] = 'Klucze SSH';
$_LANG['tab_history'] = 'Historia';
$_LANG['tab_settings'] = 'Ustawienia';

$_LANG['status_running'] = 'Działa';
$_LANG['status_off'] = 'Wyłączony';
$_LANG['status_starting'] = 'Uruchamianie';
$_LANG['status_stopping'] = 'Zatrzymywanie';
$_LANG['status_initializing'] = 'Inicjalizacja';
$_LANG['status_migrating'] = 'Migracja';
$_LANG['status_rebuilding'] = 'Przeinstalacja';
$_LANG['status_deleting'] = 'Usuwanie';
$_LANG['status_unknown'] = 'Nieznany';
$_LANG['status_provisioning'] = 'Tworzenie';

$_LANG['server_name'] = 'Nazwa serwera';
$_LANG['server_id'] = 'ID serwera';
$_LANG['ip_address'] = 'Adres IP';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Prywatny IP';
$_LANG['operating_system'] = 'System operacyjny';
$_LANG['server_type'] = 'Typ serwera';
$_LANG['location'] = 'Lokalizacja';
$_LANG['datacenter'] = 'Centrum danych';
$_LANG['created'] = 'Utworzono';
$_LANG['status'] = 'Status';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Pamięć';
$_LANG['disk'] = 'Dysk';
$_LANG['architecture'] = 'Architektura';
$_LANG['price'] = 'Cena';
$_LANG['protection'] = 'Ochrona';

$_LANG['power'] = 'Zasilanie';
$_LANG['power_on'] = 'Włącz';
$_LANG['power_off'] = 'Wyłącz';
$_LANG['shutdown'] = 'Zamknij system';
$_LANG['reboot'] = 'Restart';
$_LANG['reset'] = 'Twardy reset';
$_LANG['console'] = 'Konsola';
$_LANG['open_console'] = 'Otwórz konsolę';

$_LANG['access_info'] = 'Dane dostępowe';
$_LANG['username'] = 'Użytkownik';
$_LANG['password'] = 'Hasło';
$_LANG['show'] = 'Pokaż';
$_LANG['hide'] = 'Ukryj';
$_LANG['copy'] = 'Kopiuj';
$_LANG['copied'] = 'Skopiowano do schowka';
$_LANG['reset_password'] = 'Zresetuj hasło root';
$_LANG['ssh_command'] = 'Polecenie SSH';
$_LANG['password_reset_done'] = 'Nowe hasło root zostało wygenerowane i zapisane w usłudze.';
$_LANG['password_hidden'] = 'Ukryte';

$_LANG['cpu_usage'] = 'Użycie CPU';
$_LANG['disk_io'] = 'We/Wy dysku';
$_LANG['network_traffic'] = 'Ruch sieciowy';
$_LANG['range_1h'] = '1 godzina';
$_LANG['range_24h'] = '24 godziny';
$_LANG['range_7d'] = '7 dni';
$_LANG['range_30d'] = '30 dni';
$_LANG['no_data'] = 'Brak danych dla tego okresu.';
$_LANG['read'] = 'Odczyt';
$_LANG['write'] = 'Zapis';
$_LANG['inbound'] = 'Przychodzący';
$_LANG['outbound'] = 'Wychodzący';

$_LANG['bandwidth'] = 'Zużycie transferu';
$_LANG['included_traffic'] = 'Transfer w pakiecie';
$_LANG['outgoing_traffic'] = 'Wysyłanie (wychodzący)';
$_LANG['incoming_traffic'] = 'Pobieranie (przychodzący)';
$_LANG['traffic_used'] = 'Wykorzystano';
$_LANG['traffic_remaining'] = 'Pozostało';
$_LANG['traffic_period'] = 'Bieżący okres rozliczeniowy';
$_LANG['traffic_overage'] = 'Transfer w pakiecie na ten okres został wyczerpany.';

$_LANG['backups'] = 'Automatyczne kopie zapasowe';
$_LANG['backup_intro'] = 'Hetzner przechowuje do siedmiu rotacyjnych kopii. Włączenie zwiększa cenę serwera o 20 %.';
$_LANG['enable_backups'] = 'Włącz kopie zapasowe';
$_LANG['disable_backups'] = 'Wyłącz kopie zapasowe';
$_LANG['backups_enabled'] = 'Kopie zapasowe są włączone';
$_LANG['backups_disabled'] = 'Kopie zapasowe są wyłączone';
$_LANG['backup_window'] = 'Okno kopii';
$_LANG['no_backups'] = 'Nie utworzono jeszcze żadnej kopii.';
$_LANG['confirm_disable_backups'] = 'Wyłączenie usunie wszystkie istniejące kopie. Kontynuować?';

$_LANG['snapshots'] = 'Migawki';
$_LANG['snapshot_intro'] = 'Migawka to pełny obraz serwera, który można przywrócić w dowolnym momencie.';
$_LANG['create_snapshot'] = 'Utwórz migawkę';
$_LANG['snapshot_name'] = 'Nazwa migawki';
$_LANG['no_snapshots'] = 'Nie masz migawek.';
$_LANG['restore'] = 'Przywróć';
$_LANG['size'] = 'Rozmiar';
$_LANG['limit_reached'] = 'Osiągnięto limit {limit} dla tej usługi.';
$_LANG['confirm_restore'] = 'Przywracanie nadpisze wszystkie pliki serwera tym obrazem. Kontynuować?';

$_LANG['floating_ips'] = 'Pływające IP';
$_LANG['floating_ip_intro'] = 'Pływające IP można przenosić między serwerami i przetrwają przeinstalację.';
$_LANG['create_floating_ip'] = 'Zamów pływające IP';
$_LANG['assign'] = 'Przypisz';
$_LANG['unassign'] = 'Odepnij';
$_LANG['no_floating_ips'] = 'Brak pływających IP przypisanych do tej usługi.';
$_LANG['protocol'] = 'Protokół';
$_LANG['assigned'] = 'Przypisany';
$_LANG['unassigned'] = 'Nieprzypisany';

$_LANG['reverse_dns'] = 'Odwrotny DNS';
$_LANG['rdns_intro'] = 'Ustaw rekord PTR dla adresów IPv4 i IPv6.';
$_LANG['hostname'] = 'Nazwa hosta';
$_LANG['set_rdns'] = 'Zapisz PTR';
$_LANG['rdns_saved'] = 'Rekord odwrotnego DNS zaktualizowany.';

$_LANG['networks'] = 'Sieci prywatne';
$_LANG['network_intro'] = 'Sieci prywatne łączą Twoje serwery odizolowaną siecią 10.x.';
$_LANG['create_network'] = 'Utwórz sieć';
$_LANG['network_name'] = 'Nazwa sieci';
$_LANG['ip_range'] = 'Zakres IP';
$_LANG['subnets'] = 'Podsieci';
$_LANG['add_subnet'] = 'Dodaj podsieć';
$_LANG['routes'] = 'Trasy';
$_LANG['add_route'] = 'Dodaj trasę';
$_LANG['destination'] = 'Cel';
$_LANG['gateway'] = 'Brama';
$_LANG['network_zone'] = 'Strefa sieciowa';
$_LANG['attach'] = 'Podłącz serwer';
$_LANG['detach'] = 'Odłącz serwer';
$_LANG['no_networks'] = 'Nie masz sieci prywatnych.';
$_LANG['attached'] = 'Podłączony';
$_LANG['not_attached'] = 'Niepodłączony';

$_LANG['firewalls'] = 'Zapory';
$_LANG['firewall_intro'] = 'Zapora działa przed serwerem, więc zablokowany ruch nigdy do niego nie dociera.';
$_LANG['create_firewall'] = 'Utwórz zaporę';
$_LANG['firewall_name'] = 'Nazwa zapory';
$_LANG['rules'] = 'Reguły';
$_LANG['add_rule'] = 'Dodaj regułę';
$_LANG['direction'] = 'Kierunek';
$_LANG['port'] = 'Port';
$_LANG['source_ips'] = 'Adresy źródłowe';
$_LANG['destination_ips'] = 'Adresy docelowe';
$_LANG['apply_to_server'] = 'Zastosuj do serwera';
$_LANG['remove_from_server'] = 'Usuń z serwera';
$_LANG['no_firewalls'] = 'Nie masz zapór.';
$_LANG['applied'] = 'Zastosowana';
$_LANG['not_applied'] = 'Niezastosowana';
$_LANG['description'] = 'Opis';
$_LANG['any_port'] = 'dowolny';

$_LANG['iso'] = 'Obraz ISO';
$_LANG['iso_intro'] = 'Zamontuj ISO, aby uruchomić serwer z nośnika instalacyjnego. Serwer startuje z ISO przed dyskiem.';
$_LANG['mount_iso'] = 'Zamontuj';
$_LANG['unmount_iso'] = 'Odmontuj';
$_LANG['mounted_iso'] = 'Obecnie zamontowany';
$_LANG['no_iso'] = 'Żaden obraz ISO nie jest zamontowany.';
$_LANG['search_iso'] = 'Szukaj obrazów ISO';
$_LANG['iso_mounted_notice'] = 'Obraz ISO jest zamontowany. Zrestartuj serwer, aby z niego wystartować.';

$_LANG['rescue_mode'] = 'Tryb ratunkowy';
$_LANG['rescue_intro'] = 'Tryb ratunkowy uruchamia mały system Linux z sieci, aby naprawić uszkodzoną instalację. Działa tylko przy następnym starcie.';
$_LANG['enable_rescue'] = 'Włącz tryb ratunkowy';
$_LANG['disable_rescue'] = 'Wyłącz tryb ratunkowy';
$_LANG['rescue_enabled'] = 'Tryb ratunkowy jest gotowy na następny start';
$_LANG['rescue_password'] = 'Hasło trybu ratunkowego';
$_LANG['rescue_type'] = 'System ratunkowy';
$_LANG['rescue_reboot_hint'] = 'Zrestartuj serwer teraz, aby wejść do systemu ratunkowego.';

$_LANG['rebuild'] = 'Przeinstaluj serwer';
$_LANG['rebuild_intro'] = 'Zainstaluj serwer ponownie z czystego obrazu.';
$_LANG['select_os'] = 'Wybierz system operacyjny';
$_LANG['rebuild_warning'] = 'Przeinstalacja usuwa wszystkie pliki na serwerze. Nie można tego cofnąć.';
$_LANG['rebuild_started'] = 'Przeinstalacja rozpoczęta. Serwer będzie dostępny za kilka minut.';
$_LANG['type_confirm'] = 'Wpisz {word}, aby potwierdzić';

$_LANG['change_package'] = 'Zmień pakiet';
$_LANG['current_plan'] = 'Obecny pakiet';
$_LANG['new_plan'] = 'Nowy pakiet';
$_LANG['upgrade_disk'] = 'Powiększ także dysk (nieodwracalne)';
$_LANG['change_type_warning'] = 'Serwer zostanie wyłączony na czas zmiany. Powiększenia dysku nie da się cofnąć.';
$_LANG['change_package_done'] = 'Pakiet został zmieniony.';

$_LANG['ssh_keys'] = 'Klucze SSH';
$_LANG['ssh_intro'] = 'Dodane tutaj klucze SSH mogą zostać wdrożone przy przeinstalacji serwera.';
$_LANG['add_ssh_key'] = 'Dodaj klucz SSH';
$_LANG['key_name'] = 'Nazwa klucza';
$_LANG['public_key'] = 'Klucz publiczny';
$_LANG['no_ssh_keys'] = 'Brak zapisanych kluczy SSH dla tej usługi.';
$_LANG['fingerprint'] = 'Odcisk palca';
$_LANG['ssh_key_order_label'] = 'Publiczny klucz SSH (opcjonalnie)';

$_LANG['history'] = 'Historia zadań';
$_LANG['command'] = 'Polecenie';
$_LANG['progress'] = 'Postęp';
$_LANG['started'] = 'Start';
$_LANG['finished'] = 'Koniec';
$_LANG['no_history'] = 'Brak zarejestrowanych zadań.';

$_LANG['loading'] = 'Ładowanie';
$_LANG['save'] = 'Zapisz';
$_LANG['cancel'] = 'Anuluj';
$_LANG['close'] = 'Zamknij';
$_LANG['confirm'] = 'Potwierdź';
$_LANG['delete'] = 'Usuń';
$_LANG['create'] = 'Utwórz';
$_LANG['yes'] = 'Tak';
$_LANG['no'] = 'Nie';
$_LANG['enabled'] = 'Włączone';
$_LANG['disabled'] = 'Wyłączone';
$_LANG['error'] = 'Błąd';
$_LANG['success'] = 'Gotowe';
$_LANG['warning'] = 'Uwaga';
$_LANG['none'] = 'Brak';
$_LANG['name'] = 'Nazwa';
$_LANG['type'] = 'Typ';
$_LANG['refresh'] = 'Odśwież';
$_LANG['search'] = 'Szukaj';
$_LANG['actions'] = 'Akcje';
$_LANG['working'] = 'Przetwarzanie';
$_LANG['are_you_sure'] = 'Czy na pewno?';
$_LANG['confirm_delete'] = 'Usunąć {name}? Nie można tego cofnąć.';
$_LANG['feature_disabled'] = 'Ta funkcja nie jest dostępna w Twoim produkcie.';
$_LANG['server_missing'] = 'Do tej usługi nie jest jeszcze podpięty serwer Hetzner. Skontaktuj się z pomocą techniczną.';
$_LANG['request_failed'] = 'Żądanie nie powiodło się. Spróbuj ponownie.';
$_LANG['session_expired'] = 'Sesja wygasła. Odśwież stronę i spróbuj ponownie.';
$_LANG['locked_notice'] = 'Na tym serwerze trwa jeszcze inne zadanie. Poczekaj chwilę.';
$_LANG['auto_refresh'] = 'Automatyczne odświeżanie';
$_LANG['last_updated'] = 'Ostatnia aktualizacja';
$_LANG['just_now'] = 'przed chwilą';
$_LANG['optional'] = 'opcjonalnie';

/* Add-ons */
$_LANG['tab_addons'] = 'Dodatki';
$_LANG['addons_intro'] = 'Dodatkowe funkcje sprzedawane są jako opcje konfigurowalne produktu. Wybierz, a WHMCS wystawi fakturę za zmianę.';
$_LANG['addons_buy'] = 'Kup lub zmień dodatki';
$_LANG['addons_none'] = 'Dla tego produktu nie skonfigurowano dodatków.';
$_LANG['addons_current'] = 'Masz';
$_LANG['addons_options'] = 'Dostępne';
$_LANG['addons_included'] = 'w cenie';
$_LANG['addons_per_unit'] = 'za sztukę';
$_LANG['per_month'] = 'Miesięcznie';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minut';
$_LANG['range_6h'] = '6 godzin';
$_LANG['network_packets'] = 'Pakiety sieciowe';
$_LANG['disk_iops'] = 'IOPS dysku';
$_LANG['average'] = 'śr';
$_LANG['peak'] = 'szczyt';
$_LANG['table_view'] = 'Widok tabeli';
$_LANG['timestamp'] = 'Czas';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100%';
