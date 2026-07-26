<?php
/** Hetzner Cloud module - Русский. Отсутствующие ключи берутся из английского файла. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Обзор';
$_LANG['tab_graphs'] = 'Графики';
$_LANG['tab_backups'] = 'Бэкапы';
$_LANG['tab_snapshots'] = 'Снапшоты';
$_LANG['tab_network'] = 'Сеть';
$_LANG['tab_firewall'] = 'Фаервол';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Rescue';
$_LANG['tab_rebuild'] = 'Переустановка';
$_LANG['tab_ssh'] = 'SSH-ключи';
$_LANG['tab_history'] = 'История';
$_LANG['tab_settings'] = 'Настройки';

$_LANG['status_running'] = 'Работает';
$_LANG['status_off'] = 'Выключен';
$_LANG['status_starting'] = 'Запускается';
$_LANG['status_stopping'] = 'Останавливается';
$_LANG['status_initializing'] = 'Инициализация';
$_LANG['status_migrating'] = 'Миграция';
$_LANG['status_rebuilding'] = 'Переустановка';
$_LANG['status_deleting'] = 'Удаление';
$_LANG['status_unknown'] = 'Неизвестно';
$_LANG['status_provisioning'] = 'Создаётся';

$_LANG['server_name'] = 'Имя сервера';
$_LANG['server_id'] = 'ID сервера';
$_LANG['ip_address'] = 'IP-адрес';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Приватный IP';
$_LANG['operating_system'] = 'Операционная система';
$_LANG['server_type'] = 'Тип сервера';
$_LANG['location'] = 'Локация';
$_LANG['datacenter'] = 'Дата-центр';
$_LANG['created'] = 'Создан';
$_LANG['status'] = 'Статус';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Память';
$_LANG['disk'] = 'Диск';
$_LANG['architecture'] = 'Архитектура';
$_LANG['price'] = 'Цена';
$_LANG['protection'] = 'Защита';

$_LANG['power'] = 'Питание';
$_LANG['power_on'] = 'Включить';
$_LANG['power_off'] = 'Выключить';
$_LANG['shutdown'] = 'Корректное выключение';
$_LANG['reboot'] = 'Перезагрузить';
$_LANG['reset'] = 'Жёсткий сброс';
$_LANG['console'] = 'Консоль';
$_LANG['open_console'] = 'Открыть консоль';

$_LANG['access_info'] = 'Доступ';
$_LANG['username'] = 'Пользователь';
$_LANG['password'] = 'Пароль';
$_LANG['show'] = 'Показать';
$_LANG['hide'] = 'Скрыть';
$_LANG['copy'] = 'Копировать';
$_LANG['copied'] = 'Скопировано в буфер обмена';
$_LANG['reset_password'] = 'Сбросить пароль root';
$_LANG['ssh_command'] = 'Команда SSH';
$_LANG['password_reset_done'] = 'Новый пароль root создан и сохранён в услуге.';
$_LANG['password_hidden'] = 'Скрыт';

$_LANG['cpu_usage'] = 'Загрузка CPU';
$_LANG['disk_io'] = 'Дисковый ввод-вывод';
$_LANG['network_traffic'] = 'Сетевой трафик';
$_LANG['range_1h'] = '1 час';
$_LANG['range_24h'] = '24 часа';
$_LANG['range_7d'] = '7 дней';
$_LANG['range_30d'] = '30 дней';
$_LANG['no_data'] = 'Нет данных за этот период.';
$_LANG['read'] = 'Чтение';
$_LANG['write'] = 'Запись';
$_LANG['inbound'] = 'Входящий';
$_LANG['outbound'] = 'Исходящий';

$_LANG['bandwidth'] = 'Расход трафика';
$_LANG['included_traffic'] = 'Включённый трафик';
$_LANG['outgoing_traffic'] = 'Отдача (исходящий)';
$_LANG['incoming_traffic'] = 'Приём (входящий)';
$_LANG['traffic_used'] = 'Израсходовано';
$_LANG['traffic_remaining'] = 'Осталось';
$_LANG['traffic_period'] = 'Текущий расчётный период';
$_LANG['traffic_overage'] = 'Включённый трафик за этот период исчерпан.';

$_LANG['backups'] = 'Автоматические бэкапы';
$_LANG['backup_intro'] = 'Hetzner хранит до семи бэкапов по кругу. Включение добавляет 20 % к цене сервера.';
$_LANG['enable_backups'] = 'Включить бэкапы';
$_LANG['disable_backups'] = 'Отключить бэкапы';
$_LANG['backups_enabled'] = 'Бэкапы включены';
$_LANG['backups_disabled'] = 'Бэкапы отключены';
$_LANG['backup_window'] = 'Окно бэкапа';
$_LANG['no_backups'] = 'Бэкапы ещё не создавались.';
$_LANG['confirm_disable_backups'] = 'Отключение удалит все существующие бэкапы. Продолжить?';

$_LANG['snapshots'] = 'Снапшоты';
$_LANG['snapshot_intro'] = 'Снапшот — полный образ сервера, который можно восстановить в любой момент.';
$_LANG['create_snapshot'] = 'Создать снапшот';
$_LANG['snapshot_name'] = 'Название снапшота';
$_LANG['no_snapshots'] = 'Снапшотов нет.';
$_LANG['restore'] = 'Восстановить';
$_LANG['size'] = 'Размер';
$_LANG['limit_reached'] = 'Достигнут лимит {limit} для этой услуги.';
$_LANG['confirm_restore'] = 'Восстановление перезапишет все файлы сервера содержимым образа. Продолжить?';

$_LANG['floating_ips'] = 'Плавающие IP';
$_LANG['floating_ip_intro'] = 'Плавающие IP переносятся между серверами и сохраняются после переустановки.';
$_LANG['create_floating_ip'] = 'Заказать плавающий IP';
$_LANG['assign'] = 'Назначить';
$_LANG['unassign'] = 'Снять назначение';
$_LANG['no_floating_ips'] = 'Плавающие IP для этой услуги не назначены.';
$_LANG['protocol'] = 'Протокол';
$_LANG['assigned'] = 'Назначен';
$_LANG['unassigned'] = 'Не назначен';

$_LANG['reverse_dns'] = 'Обратный DNS';
$_LANG['rdns_intro'] = 'Задайте PTR-запись для адресов IPv4 и IPv6.';
$_LANG['hostname'] = 'Имя хоста';
$_LANG['set_rdns'] = 'Сохранить PTR';
$_LANG['rdns_saved'] = 'PTR-запись обновлена.';

$_LANG['networks'] = 'Приватные сети';
$_LANG['network_intro'] = 'Приватные сети связывают ваши серверы через изолированную сеть 10.x.';
$_LANG['create_network'] = 'Создать сеть';
$_LANG['network_name'] = 'Название сети';
$_LANG['ip_range'] = 'Диапазон IP';
$_LANG['subnets'] = 'Подсети';
$_LANG['add_subnet'] = 'Добавить подсеть';
$_LANG['routes'] = 'Маршруты';
$_LANG['add_route'] = 'Добавить маршрут';
$_LANG['destination'] = 'Назначение';
$_LANG['gateway'] = 'Шлюз';
$_LANG['network_zone'] = 'Сетевая зона';
$_LANG['attach'] = 'Подключить сервер';
$_LANG['detach'] = 'Отключить сервер';
$_LANG['no_networks'] = 'Приватных сетей нет.';
$_LANG['attached'] = 'Подключён';
$_LANG['not_attached'] = 'Не подключён';

$_LANG['firewalls'] = 'Фаерволы';
$_LANG['firewall_intro'] = 'Фаервол работает перед сервером — заблокированный трафик до него не доходит.';
$_LANG['create_firewall'] = 'Создать фаервол';
$_LANG['firewall_name'] = 'Название фаервола';
$_LANG['rules'] = 'Правила';
$_LANG['add_rule'] = 'Добавить правило';
$_LANG['direction'] = 'Направление';
$_LANG['port'] = 'Порт';
$_LANG['source_ips'] = 'IP источника';
$_LANG['destination_ips'] = 'IP назначения';
$_LANG['apply_to_server'] = 'Применить к серверу';
$_LANG['remove_from_server'] = 'Снять с сервера';
$_LANG['no_firewalls'] = 'Фаерволов нет.';
$_LANG['applied'] = 'Применён';
$_LANG['not_applied'] = 'Не применён';
$_LANG['description'] = 'Описание';
$_LANG['any_port'] = 'любой';

$_LANG['iso'] = 'ISO-образ';
$_LANG['iso_intro'] = 'Подключите ISO, чтобы загрузиться с установочного носителя. Сервер грузится с ISO раньше диска.';
$_LANG['mount_iso'] = 'Подключить';
$_LANG['unmount_iso'] = 'Отключить';
$_LANG['mounted_iso'] = 'Подключён сейчас';
$_LANG['no_iso'] = 'ISO не подключён.';
$_LANG['search_iso'] = 'Поиск ISO-образов';
$_LANG['iso_mounted_notice'] = 'ISO подключён. Перезагрузите сервер, чтобы загрузиться с образа.';

$_LANG['rescue_mode'] = 'Режим восстановления';
$_LANG['rescue_intro'] = 'Rescue загружает по сети минимальный Linux для починки сломанной системы. Действует только на следующую загрузку.';
$_LANG['enable_rescue'] = 'Включить rescue';
$_LANG['disable_rescue'] = 'Выключить rescue';
$_LANG['rescue_enabled'] = 'Rescue включён для следующей загрузки';
$_LANG['rescue_password'] = 'Пароль rescue';
$_LANG['rescue_type'] = 'Система восстановления';
$_LANG['rescue_reboot_hint'] = 'Перезагрузите сервер сейчас, чтобы войти в rescue.';

$_LANG['rebuild'] = 'Переустановить сервер';
$_LANG['rebuild_intro'] = 'Переустановите сервер из чистого образа.';
$_LANG['select_os'] = 'Выберите операционную систему';
$_LANG['rebuild_warning'] = 'Переустановка удалит все файлы сервера. Отменить нельзя.';
$_LANG['rebuild_started'] = 'Переустановка запущена. Сервер будет доступен через несколько минут.';
$_LANG['type_confirm'] = 'Введите {word} для подтверждения';

$_LANG['change_package'] = 'Сменить тариф';
$_LANG['current_plan'] = 'Текущий тариф';
$_LANG['new_plan'] = 'Новый тариф';
$_LANG['upgrade_disk'] = 'Также увеличить диск (необратимо)';
$_LANG['change_type_warning'] = 'На время смены сервер будет выключен. Увеличение диска обратно не отменяется.';
$_LANG['change_package_done'] = 'Тариф успешно изменён.';

$_LANG['ssh_keys'] = 'SSH-ключи';
$_LANG['ssh_intro'] = 'Добавленные здесь ключи могут быть развёрнуты при переустановке сервера.';
$_LANG['add_ssh_key'] = 'Добавить SSH-ключ';
$_LANG['key_name'] = 'Название ключа';
$_LANG['public_key'] = 'Публичный ключ';
$_LANG['no_ssh_keys'] = 'Для этой услуги ключей не сохранено.';
$_LANG['fingerprint'] = 'Отпечаток';
$_LANG['ssh_key_order_label'] = 'Публичный SSH-ключ (необязательно)';

$_LANG['history'] = 'История задач';
$_LANG['command'] = 'Команда';
$_LANG['progress'] = 'Прогресс';
$_LANG['started'] = 'Начало';
$_LANG['finished'] = 'Завершено';
$_LANG['no_history'] = 'Задач пока нет.';

$_LANG['loading'] = 'Загрузка';
$_LANG['save'] = 'Сохранить';
$_LANG['cancel'] = 'Отмена';
$_LANG['close'] = 'Закрыть';
$_LANG['confirm'] = 'Подтвердить';
$_LANG['delete'] = 'Удалить';
$_LANG['create'] = 'Создать';
$_LANG['yes'] = 'Да';
$_LANG['no'] = 'Нет';
$_LANG['enabled'] = 'Включено';
$_LANG['disabled'] = 'Отключено';
$_LANG['error'] = 'Ошибка';
$_LANG['success'] = 'Готово';
$_LANG['warning'] = 'Внимание';
$_LANG['none'] = 'Нет';
$_LANG['name'] = 'Название';
$_LANG['type'] = 'Тип';
$_LANG['refresh'] = 'Обновить';
$_LANG['search'] = 'Поиск';
$_LANG['actions'] = 'Действия';
$_LANG['working'] = 'Выполняется';
$_LANG['are_you_sure'] = 'Вы уверены?';
$_LANG['confirm_delete'] = 'Удалить {name}? Отменить нельзя.';
$_LANG['feature_disabled'] = 'Эта функция недоступна в вашем продукте.';
$_LANG['server_missing'] = 'К этой услуге ещё не привязан сервер Hetzner. Обратитесь в поддержку.';
$_LANG['request_failed'] = 'Запрос не выполнен. Попробуйте ещё раз.';
$_LANG['session_expired'] = 'Сессия истекла. Обновите страницу и повторите.';
$_LANG['locked_notice'] = 'На сервере выполняется другая задача. Подождите.';
$_LANG['auto_refresh'] = 'Автообновление';
$_LANG['last_updated'] = 'Обновлено';
$_LANG['just_now'] = 'только что';
$_LANG['optional'] = 'необязательно';

/* Add-ons */
$_LANG['tab_addons'] = 'Дополнения';
$_LANG['addons_intro'] = 'Дополнительные возможности продаются как настраиваемые опции продукта. Выберите нужное — WHMCS выставит счёт за изменение.';
$_LANG['addons_buy'] = 'Купить или изменить дополнения';
$_LANG['addons_none'] = 'Для этого продукта дополнения не настроены.';
$_LANG['addons_current'] = 'У вас';
$_LANG['addons_options'] = 'Доступно';
$_LANG['addons_included'] = 'включено';
$_LANG['addons_per_unit'] = 'за единицу';
$_LANG['per_month'] = 'В месяц';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 минут';
$_LANG['range_6h'] = '6 часов';
$_LANG['network_packets'] = 'Сетевые пакеты';
$_LANG['disk_iops'] = 'IOPS диска';
$_LANG['average'] = 'сред';
$_LANG['peak'] = 'пик';
$_LANG['table_view'] = 'Таблица';
$_LANG['timestamp'] = 'Время';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100 %';
