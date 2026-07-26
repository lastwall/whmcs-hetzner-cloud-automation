<?php
/** Hetzner Cloud module - Español. Las claves ausentes usan el inglés. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Resumen';
$_LANG['tab_graphs'] = 'Gráficas';
$_LANG['tab_backups'] = 'Copias de seguridad';
$_LANG['tab_snapshots'] = 'Snapshots';
$_LANG['tab_network'] = 'Red';
$_LANG['tab_firewall'] = 'Cortafuegos';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Rescate';
$_LANG['tab_rebuild'] = 'Reinstalar';
$_LANG['tab_ssh'] = 'Claves SSH';
$_LANG['tab_history'] = 'Historial';
$_LANG['tab_settings'] = 'Ajustes';

$_LANG['status_running'] = 'En ejecución';
$_LANG['status_off'] = 'Apagado';
$_LANG['status_starting'] = 'Arrancando';
$_LANG['status_stopping'] = 'Deteniendo';
$_LANG['status_initializing'] = 'Inicializando';
$_LANG['status_migrating'] = 'Migrando';
$_LANG['status_rebuilding'] = 'Reinstalando';
$_LANG['status_deleting'] = 'Eliminando';
$_LANG['status_unknown'] = 'Desconocido';
$_LANG['status_provisioning'] = 'Aprovisionando';

$_LANG['server_name'] = 'Nombre del servidor';
$_LANG['server_id'] = 'ID del servidor';
$_LANG['ip_address'] = 'Dirección IP';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'IP privada';
$_LANG['operating_system'] = 'Sistema operativo';
$_LANG['server_type'] = 'Tipo de servidor';
$_LANG['location'] = 'Ubicación';
$_LANG['datacenter'] = 'Centro de datos';
$_LANG['created'] = 'Creado';
$_LANG['status'] = 'Estado';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Memoria';
$_LANG['disk'] = 'Disco';
$_LANG['architecture'] = 'Arquitectura';
$_LANG['price'] = 'Precio';
$_LANG['protection'] = 'Protección';

$_LANG['power'] = 'Encendido';
$_LANG['power_on'] = 'Encender';
$_LANG['power_off'] = 'Apagar';
$_LANG['shutdown'] = 'Apagado ordenado';
$_LANG['reboot'] = 'Reiniciar';
$_LANG['reset'] = 'Reinicio forzado';
$_LANG['console'] = 'Consola';
$_LANG['open_console'] = 'Abrir consola';

$_LANG['access_info'] = 'Datos de acceso';
$_LANG['username'] = 'Usuario';
$_LANG['password'] = 'Contraseña';
$_LANG['show'] = 'Mostrar';
$_LANG['hide'] = 'Ocultar';
$_LANG['copy'] = 'Copiar';
$_LANG['copied'] = 'Copiado al portapapeles';
$_LANG['reset_password'] = 'Restablecer contraseña de root';
$_LANG['ssh_command'] = 'Comando SSH';
$_LANG['password_reset_done'] = 'Se ha generado una nueva contraseña de root y se ha guardado en su servicio.';
$_LANG['password_hidden'] = 'Oculta';

$_LANG['cpu_usage'] = 'Uso de CPU';
$_LANG['disk_io'] = 'E/S de disco';
$_LANG['network_traffic'] = 'Tráfico de red';
$_LANG['range_1h'] = '1 hora';
$_LANG['range_24h'] = '24 horas';
$_LANG['range_7d'] = '7 días';
$_LANG['range_30d'] = '30 días';
$_LANG['no_data'] = 'No hay datos para este periodo.';
$_LANG['read'] = 'Lectura';
$_LANG['write'] = 'Escritura';
$_LANG['inbound'] = 'Entrante';
$_LANG['outbound'] = 'Saliente';

$_LANG['bandwidth'] = 'Consumo de tráfico';
$_LANG['included_traffic'] = 'Tráfico incluido';
$_LANG['outgoing_traffic'] = 'Subida (saliente)';
$_LANG['incoming_traffic'] = 'Bajada (entrante)';
$_LANG['traffic_used'] = 'Consumido';
$_LANG['traffic_remaining'] = 'Restante';
$_LANG['traffic_period'] = 'Periodo de facturación actual';
$_LANG['traffic_overage'] = 'Ha superado el tráfico incluido de este periodo.';

$_LANG['backups'] = 'Copias automáticas';
$_LANG['backup_intro'] = 'Hetzner mantiene hasta siete copias rotativas. Activarlas añade un 20 % al precio del servidor.';
$_LANG['enable_backups'] = 'Activar copias';
$_LANG['disable_backups'] = 'Desactivar copias';
$_LANG['backups_enabled'] = 'Las copias están activadas';
$_LANG['backups_disabled'] = 'Las copias están desactivadas';
$_LANG['backup_window'] = 'Ventana de copia';
$_LANG['no_backups'] = 'Todavía no se ha creado ninguna copia.';
$_LANG['confirm_disable_backups'] = 'Al desactivarlas se eliminan todas las copias existentes. ¿Continuar?';

$_LANG['snapshots'] = 'Snapshots';
$_LANG['snapshot_intro'] = 'Un snapshot es una imagen completa del servidor que puede restaurar cuando quiera.';
$_LANG['create_snapshot'] = 'Crear snapshot';
$_LANG['snapshot_name'] = 'Nombre del snapshot';
$_LANG['no_snapshots'] = 'No tiene snapshots.';
$_LANG['restore'] = 'Restaurar';
$_LANG['size'] = 'Tamaño';
$_LANG['limit_reached'] = 'Ha alcanzado el límite de {limit} para este servicio.';
$_LANG['confirm_restore'] = 'Restaurar sobrescribe todos los archivos del servidor con esta imagen. ¿Continuar?';

$_LANG['floating_ips'] = 'IP flotantes';
$_LANG['floating_ip_intro'] = 'Las IP flotantes se mueven entre servidores y sobreviven a una reinstalación.';
$_LANG['create_floating_ip'] = 'Solicitar IP flotante';
$_LANG['assign'] = 'Asignar';
$_LANG['unassign'] = 'Desasignar';
$_LANG['no_floating_ips'] = 'No hay IP flotantes asignadas a este servicio.';
$_LANG['protocol'] = 'Protocolo';
$_LANG['assigned'] = 'Asignada';
$_LANG['unassigned'] = 'Sin asignar';

$_LANG['reverse_dns'] = 'DNS inverso';
$_LANG['rdns_intro'] = 'Configure el registro PTR de sus direcciones IPv4 e IPv6.';
$_LANG['hostname'] = 'Nombre de host';
$_LANG['set_rdns'] = 'Guardar PTR';
$_LANG['rdns_saved'] = 'Registro de DNS inverso actualizado.';

$_LANG['networks'] = 'Redes privadas';
$_LANG['network_intro'] = 'Las redes privadas comunican sus servidores por una red 10.x aislada.';
$_LANG['create_network'] = 'Crear red';
$_LANG['network_name'] = 'Nombre de la red';
$_LANG['ip_range'] = 'Rango IP';
$_LANG['subnets'] = 'Subredes';
$_LANG['add_subnet'] = 'Añadir subred';
$_LANG['routes'] = 'Rutas';
$_LANG['add_route'] = 'Añadir ruta';
$_LANG['destination'] = 'Destino';
$_LANG['gateway'] = 'Puerta de enlace';
$_LANG['network_zone'] = 'Zona de red';
$_LANG['attach'] = 'Conectar servidor';
$_LANG['detach'] = 'Desconectar servidor';
$_LANG['no_networks'] = 'No tiene redes privadas.';
$_LANG['attached'] = 'Conectado';
$_LANG['not_attached'] = 'No conectado';

$_LANG['firewalls'] = 'Cortafuegos';
$_LANG['firewall_intro'] = 'El cortafuegos actúa antes del servidor: el tráfico bloqueado nunca llega a él.';
$_LANG['create_firewall'] = 'Crear cortafuegos';
$_LANG['firewall_name'] = 'Nombre del cortafuegos';
$_LANG['rules'] = 'Reglas';
$_LANG['add_rule'] = 'Añadir regla';
$_LANG['direction'] = 'Dirección';
$_LANG['port'] = 'Puerto';
$_LANG['source_ips'] = 'IP de origen';
$_LANG['destination_ips'] = 'IP de destino';
$_LANG['apply_to_server'] = 'Aplicar al servidor';
$_LANG['remove_from_server'] = 'Quitar del servidor';
$_LANG['no_firewalls'] = 'No tiene cortafuegos.';
$_LANG['applied'] = 'Aplicado';
$_LANG['not_applied'] = 'No aplicado';
$_LANG['description'] = 'Descripción';
$_LANG['any_port'] = 'cualquiera';

$_LANG['iso'] = 'Imagen ISO';
$_LANG['iso_intro'] = 'Monte una ISO para arrancar desde un medio de instalación. El servidor arranca de la ISO antes que del disco.';
$_LANG['mount_iso'] = 'Montar';
$_LANG['unmount_iso'] = 'Desmontar';
$_LANG['mounted_iso'] = 'Montada actualmente';
$_LANG['no_iso'] = 'No hay ninguna ISO montada.';
$_LANG['search_iso'] = 'Buscar imágenes ISO';
$_LANG['iso_mounted_notice'] = 'Hay una ISO montada. Reinicie el servidor para arrancar desde ella.';

$_LANG['rescue_mode'] = 'Modo rescate';
$_LANG['rescue_intro'] = 'El modo rescate arranca un Linux mínimo por red para reparar una instalación dañada. Solo afecta al siguiente arranque.';
$_LANG['enable_rescue'] = 'Activar modo rescate';
$_LANG['disable_rescue'] = 'Desactivar modo rescate';
$_LANG['rescue_enabled'] = 'El modo rescate está armado para el siguiente arranque';
$_LANG['rescue_password'] = 'Contraseña de rescate';
$_LANG['rescue_type'] = 'Sistema de rescate';
$_LANG['rescue_reboot_hint'] = 'Reinicie el servidor ahora para entrar en el sistema de rescate.';

$_LANG['rebuild'] = 'Reinstalar servidor';
$_LANG['rebuild_intro'] = 'Reinstalar el servidor desde una imagen nueva.';
$_LANG['select_os'] = 'Seleccione un sistema operativo';
$_LANG['rebuild_warning'] = 'Reinstalar borra todos los archivos del servidor. No se puede deshacer.';
$_LANG['rebuild_started'] = 'Reinstalación iniciada. El servidor estará disponible en unos minutos.';
$_LANG['type_confirm'] = 'Escriba {word} para confirmar';

$_LANG['change_package'] = 'Cambiar de plan';
$_LANG['current_plan'] = 'Plan actual';
$_LANG['new_plan'] = 'Nuevo plan';
$_LANG['upgrade_disk'] = 'Ampliar también el disco (irreversible)';
$_LANG['change_type_warning'] = 'El servidor se apagará durante el cambio. Ampliar el disco no se puede revertir.';
$_LANG['change_package_done'] = 'Plan cambiado correctamente.';

$_LANG['ssh_keys'] = 'Claves SSH';
$_LANG['ssh_intro'] = 'Las claves SSH añadidas aquí pueden desplegarse al reinstalar el servidor.';
$_LANG['add_ssh_key'] = 'Añadir clave SSH';
$_LANG['key_name'] = 'Nombre de la clave';
$_LANG['public_key'] = 'Clave pública';
$_LANG['no_ssh_keys'] = 'No hay claves SSH guardadas para este servicio.';
$_LANG['fingerprint'] = 'Huella';
$_LANG['ssh_key_order_label'] = 'Clave pública SSH (opcional)';

$_LANG['history'] = 'Historial de tareas';
$_LANG['command'] = 'Comando';
$_LANG['progress'] = 'Progreso';
$_LANG['started'] = 'Inicio';
$_LANG['finished'] = 'Fin';
$_LANG['no_history'] = 'Aún no hay tareas registradas.';

$_LANG['loading'] = 'Cargando';
$_LANG['save'] = 'Guardar';
$_LANG['cancel'] = 'Cancelar';
$_LANG['close'] = 'Cerrar';
$_LANG['confirm'] = 'Confirmar';
$_LANG['delete'] = 'Eliminar';
$_LANG['create'] = 'Crear';
$_LANG['yes'] = 'Sí';
$_LANG['no'] = 'No';
$_LANG['enabled'] = 'Activado';
$_LANG['disabled'] = 'Desactivado';
$_LANG['error'] = 'Error';
$_LANG['success'] = 'Correcto';
$_LANG['warning'] = 'Aviso';
$_LANG['none'] = 'Ninguno';
$_LANG['name'] = 'Nombre';
$_LANG['type'] = 'Tipo';
$_LANG['refresh'] = 'Actualizar';
$_LANG['search'] = 'Buscar';
$_LANG['actions'] = 'Acciones';
$_LANG['working'] = 'Procesando';
$_LANG['are_you_sure'] = '¿Está seguro?';
$_LANG['confirm_delete'] = '¿Eliminar {name}? No se puede deshacer.';
$_LANG['feature_disabled'] = 'Esta función no está disponible en su producto.';
$_LANG['server_missing'] = 'Este servicio todavía no tiene un servidor Hetzner asociado. Contacte con soporte.';
$_LANG['request_failed'] = 'La solicitud ha fallado. Inténtelo de nuevo.';
$_LANG['session_expired'] = 'Su sesión ha caducado. Recargue la página e inténtelo de nuevo.';
$_LANG['locked_notice'] = 'Todavía hay otra tarea en curso en este servidor. Espere.';
$_LANG['auto_refresh'] = 'Actualización automática';
$_LANG['last_updated'] = 'Última actualización';
$_LANG['just_now'] = 'ahora mismo';
$_LANG['optional'] = 'opcional';

/* Add-ons */
$_LANG['tab_addons'] = 'Complementos';
$_LANG['addons_intro'] = 'Los extras se venden como opciones configurables de su producto. Elija lo que necesite y WHMCS factura el cambio.';
$_LANG['addons_buy'] = 'Comprar o cambiar complementos';
$_LANG['addons_none'] = 'No hay complementos configurados para este producto.';
$_LANG['addons_current'] = 'Tiene';
$_LANG['addons_options'] = 'Disponible';
$_LANG['addons_included'] = 'incluido';
$_LANG['addons_per_unit'] = 'por unidad';
$_LANG['per_month'] = 'Al mes';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minutos';
$_LANG['range_6h'] = '6 horas';
$_LANG['network_packets'] = 'Paquetes de red';
$_LANG['disk_iops'] = 'IOPS de disco';
$_LANG['average'] = 'med';
$_LANG['peak'] = 'pico';
$_LANG['table_view'] = 'Vista de tabla';
$_LANG['timestamp'] = 'Hora';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100 %';
