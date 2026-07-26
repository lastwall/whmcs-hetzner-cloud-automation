<?php
/**
 * Hetzner Cloud module - English (base language).
 *
 * Copy this file to lang/<whmcs-language>.php and translate the values.
 * Any key you leave out falls back to the English string below.
 */

$_LANG['module_name'] = 'Hetzner Cloud';

/* Tabs */
$_LANG['tab_overview'] = 'Overview';
$_LANG['tab_graphs'] = 'Graphs';
$_LANG['tab_backups'] = 'Backups';
$_LANG['tab_snapshots'] = 'Snapshots';
$_LANG['tab_network'] = 'Network';
$_LANG['tab_firewall'] = 'Firewall';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Rescue';
$_LANG['tab_rebuild'] = 'Rebuild';
$_LANG['tab_ssh'] = 'SSH Keys';
$_LANG['tab_history'] = 'History';
$_LANG['tab_settings'] = 'Settings';

/* Status */
$_LANG['status_running'] = 'Running';
$_LANG['status_off'] = 'Powered off';
$_LANG['status_starting'] = 'Starting';
$_LANG['status_stopping'] = 'Stopping';
$_LANG['status_initializing'] = 'Initialising';
$_LANG['status_migrating'] = 'Migrating';
$_LANG['status_rebuilding'] = 'Rebuilding';
$_LANG['status_deleting'] = 'Deleting';
$_LANG['status_unknown'] = 'Unknown';
$_LANG['status_provisioning'] = 'Provisioning';

/* Server facts */
$_LANG['server_name'] = 'Server name';
$_LANG['server_id'] = 'Server ID';
$_LANG['ip_address'] = 'IP address';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Private IP';
$_LANG['operating_system'] = 'Operating system';
$_LANG['server_type'] = 'Server type';
$_LANG['location'] = 'Location';
$_LANG['datacenter'] = 'Datacenter';
$_LANG['created'] = 'Created';
$_LANG['status'] = 'Status';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Memory';
$_LANG['disk'] = 'Disk';
$_LANG['architecture'] = 'Architecture';
$_LANG['price'] = 'Price';
$_LANG['protection'] = 'Protection';

/* Power */
$_LANG['power'] = 'Power';
$_LANG['power_on'] = 'Power on';
$_LANG['power_off'] = 'Power off';
$_LANG['shutdown'] = 'Shut down';
$_LANG['reboot'] = 'Reboot';
$_LANG['reset'] = 'Reset';
$_LANG['console'] = 'Console';
$_LANG['open_console'] = 'Open console';

/* Access */
$_LANG['access_info'] = 'Access details';
$_LANG['username'] = 'Username';
$_LANG['password'] = 'Password';
$_LANG['show'] = 'Show';
$_LANG['hide'] = 'Hide';
$_LANG['copy'] = 'Copy';
$_LANG['copied'] = 'Copied to clipboard';
$_LANG['reset_password'] = 'Reset root password';
$_LANG['ssh_command'] = 'SSH command';
$_LANG['password_reset_done'] = 'A new root password has been generated and saved to your service.';
$_LANG['password_hidden'] = 'Hidden';

/* Graphs */
$_LANG['cpu_usage'] = 'CPU usage';
$_LANG['disk_io'] = 'Disk I/O';
$_LANG['network_traffic'] = 'Network traffic';
$_LANG['range_1h'] = '1 hour';
$_LANG['range_24h'] = '24 hours';
$_LANG['range_7d'] = '7 days';
$_LANG['range_30d'] = '30 days';
$_LANG['no_data'] = 'No data available for this period.';
$_LANG['read'] = 'Read';
$_LANG['write'] = 'Write';
$_LANG['inbound'] = 'Inbound';
$_LANG['outbound'] = 'Outbound';

/* Bandwidth */
$_LANG['bandwidth'] = 'Bandwidth usage';
$_LANG['included_traffic'] = 'Included traffic';
$_LANG['outgoing_traffic'] = 'Upload (outgoing)';
$_LANG['incoming_traffic'] = 'Download (incoming)';
$_LANG['traffic_used'] = 'Used';
$_LANG['traffic_remaining'] = 'Remaining';
$_LANG['traffic_period'] = 'Current billing period';
$_LANG['traffic_overage'] = 'You have exceeded the included traffic for this period.';

/* Backups */
$_LANG['backups'] = 'Automatic backups';
$_LANG['backup_intro'] = 'Hetzner keeps up to seven rolling backups of your server. Enabling backups adds 20% to the server price.';
$_LANG['enable_backups'] = 'Enable backups';
$_LANG['disable_backups'] = 'Disable backups';
$_LANG['backups_enabled'] = 'Backups are enabled';
$_LANG['backups_disabled'] = 'Backups are disabled';
$_LANG['backup_window'] = 'Backup window';
$_LANG['no_backups'] = 'No backups have been created yet.';
$_LANG['confirm_disable_backups'] = 'Disabling backups deletes every existing backup. Continue?';

/* Snapshots */
$_LANG['snapshots'] = 'Snapshots';
$_LANG['snapshot_intro'] = 'A snapshot is a full image of your server that you can restore at any time.';
$_LANG['create_snapshot'] = 'Create snapshot';
$_LANG['snapshot_name'] = 'Snapshot name';
$_LANG['no_snapshots'] = 'You have no snapshots.';
$_LANG['restore'] = 'Restore';
$_LANG['size'] = 'Size';
$_LANG['limit_reached'] = 'You have reached the limit of {limit} for this service.';
$_LANG['confirm_restore'] = 'Restoring overwrites every file on the server with the contents of this image. Continue?';

/* Floating IPs */
$_LANG['floating_ips'] = 'Floating IPs';
$_LANG['floating_ip_intro'] = 'Floating IPs can be moved between your servers and survive a rebuild.';
$_LANG['create_floating_ip'] = 'Order floating IP';
$_LANG['assign'] = 'Assign';
$_LANG['unassign'] = 'Unassign';
$_LANG['no_floating_ips'] = 'No floating IPs assigned to this service.';
$_LANG['protocol'] = 'Protocol';
$_LANG['assigned'] = 'Assigned';
$_LANG['unassigned'] = 'Not assigned';

/* Reverse DNS */
$_LANG['reverse_dns'] = 'Reverse DNS';
$_LANG['rdns_intro'] = 'Set the PTR record for your IPv4 and IPv6 addresses.';
$_LANG['hostname'] = 'Hostname';
$_LANG['set_rdns'] = 'Save PTR';
$_LANG['rdns_saved'] = 'Reverse DNS record updated.';

/* Networks */
$_LANG['networks'] = 'Private networks';
$_LANG['network_intro'] = 'Private networks let your servers talk to each other over an isolated 10.x network.';
$_LANG['create_network'] = 'Create network';
$_LANG['network_name'] = 'Network name';
$_LANG['ip_range'] = 'IP range';
$_LANG['subnets'] = 'Subnets';
$_LANG['add_subnet'] = 'Add subnet';
$_LANG['routes'] = 'Routes';
$_LANG['add_route'] = 'Add route';
$_LANG['destination'] = 'Destination';
$_LANG['gateway'] = 'Gateway';
$_LANG['network_zone'] = 'Network zone';
$_LANG['attach'] = 'Attach server';
$_LANG['detach'] = 'Detach server';
$_LANG['no_networks'] = 'You have no private networks.';
$_LANG['attached'] = 'Attached';
$_LANG['not_attached'] = 'Not attached';

/* Firewall */
$_LANG['firewalls'] = 'Firewalls';
$_LANG['firewall_intro'] = 'Firewalls are applied outside your server, so blocked traffic never reaches it.';
$_LANG['create_firewall'] = 'Create firewall';
$_LANG['firewall_name'] = 'Firewall name';
$_LANG['rules'] = 'Rules';
$_LANG['add_rule'] = 'Add rule';
$_LANG['direction'] = 'Direction';
$_LANG['port'] = 'Port';
$_LANG['source_ips'] = 'Source IPs';
$_LANG['destination_ips'] = 'Destination IPs';
$_LANG['apply_to_server'] = 'Apply to server';
$_LANG['remove_from_server'] = 'Remove from server';
$_LANG['no_firewalls'] = 'You have no firewalls.';
$_LANG['applied'] = 'Applied';
$_LANG['not_applied'] = 'Not applied';
$_LANG['description'] = 'Description';
$_LANG['any_port'] = 'any';

/* ISO */
$_LANG['iso'] = 'ISO image';
$_LANG['iso_intro'] = 'Mount an ISO to boot your server from installation media. The server boots from the ISO before the disk.';
$_LANG['mount_iso'] = 'Mount';
$_LANG['unmount_iso'] = 'Unmount';
$_LANG['mounted_iso'] = 'Currently mounted';
$_LANG['no_iso'] = 'No ISO is mounted.';
$_LANG['search_iso'] = 'Search ISO images';
$_LANG['iso_mounted_notice'] = 'An ISO is mounted. Reboot the server for it to boot from the image.';

/* Rescue */
$_LANG['rescue_mode'] = 'Rescue mode';
$_LANG['rescue_intro'] = 'Rescue mode boots a small Linux system from the network so you can repair a broken installation. It applies to the next boot only.';
$_LANG['enable_rescue'] = 'Enable rescue mode';
$_LANG['disable_rescue'] = 'Disable rescue mode';
$_LANG['rescue_enabled'] = 'Rescue mode is armed for the next boot';
$_LANG['rescue_password'] = 'Rescue password';
$_LANG['rescue_type'] = 'Rescue system';
$_LANG['rescue_reboot_hint'] = 'Reboot the server now to boot into the rescue system.';

/* Rebuild */
$_LANG['rebuild'] = 'Rebuild server';
$_LANG['rebuild_intro'] = 'Reinstall the server from a fresh image.';
$_LANG['select_os'] = 'Select an operating system';
$_LANG['rebuild_warning'] = 'Rebuilding erases every file on the server. This cannot be undone.';
$_LANG['rebuild_started'] = 'Rebuild started. The server will be available again in a few minutes.';
$_LANG['type_confirm'] = 'Type {word} to confirm';

/* Change package */
$_LANG['change_package'] = 'Change plan';
$_LANG['current_plan'] = 'Current plan';
$_LANG['new_plan'] = 'New plan';
$_LANG['upgrade_disk'] = 'Also grow the disk (irreversible)';
$_LANG['change_type_warning'] = 'The server will be powered off during the change. Growing the disk cannot be reversed later.';
$_LANG['change_package_done'] = 'Plan changed successfully.';

/* SSH keys */
$_LANG['ssh_keys'] = 'SSH keys';
$_LANG['ssh_intro'] = 'SSH keys added here can be deployed when the server is rebuilt.';
$_LANG['add_ssh_key'] = 'Add SSH key';
$_LANG['key_name'] = 'Key name';
$_LANG['public_key'] = 'Public key';
$_LANG['no_ssh_keys'] = 'No SSH keys stored for this service.';
$_LANG['fingerprint'] = 'Fingerprint';
$_LANG['ssh_key_order_label'] = 'SSH public key (optional)';

/* History */
$_LANG['history'] = 'Task history';
$_LANG['command'] = 'Command';
$_LANG['progress'] = 'Progress';
$_LANG['started'] = 'Started';
$_LANG['finished'] = 'Finished';
$_LANG['no_history'] = 'No tasks recorded yet.';

/* Generic */
$_LANG['loading'] = 'Loading';
$_LANG['save'] = 'Save';
$_LANG['cancel'] = 'Cancel';
$_LANG['close'] = 'Close';
$_LANG['confirm'] = 'Confirm';
$_LANG['delete'] = 'Delete';
$_LANG['create'] = 'Create';
$_LANG['yes'] = 'Yes';
$_LANG['no'] = 'No';
$_LANG['enabled'] = 'Enabled';
$_LANG['disabled'] = 'Disabled';
$_LANG['error'] = 'Error';
$_LANG['success'] = 'Success';
$_LANG['warning'] = 'Warning';
$_LANG['none'] = 'None';
$_LANG['name'] = 'Name';
$_LANG['type'] = 'Type';
$_LANG['refresh'] = 'Refresh';
$_LANG['search'] = 'Search';
$_LANG['actions'] = 'Actions';
$_LANG['working'] = 'Working';
$_LANG['are_you_sure'] = 'Are you sure?';
$_LANG['confirm_delete'] = 'Delete {name}? This cannot be undone.';
$_LANG['feature_disabled'] = 'This feature is not available on your product.';
$_LANG['server_missing'] = 'This service has no Hetzner server attached yet. Please contact support.';
$_LANG['request_failed'] = 'The request failed. Please try again.';
$_LANG['session_expired'] = 'Your session expired. Reload the page and try again.';
$_LANG['locked_notice'] = 'Another task is still running on this server. Please wait.';
$_LANG['auto_refresh'] = 'Auto refresh';
$_LANG['last_updated'] = 'Last updated';
$_LANG['just_now'] = 'just now';
$_LANG['optional'] = 'optional';

/* Add-ons */
$_LANG['tab_addons'] = 'Add-ons';
$_LANG['addons_intro'] = 'Extra features are sold as configurable options on your product. Pick what you need and WHMCS invoices the change.';
$_LANG['addons_buy'] = 'Buy or change add-ons';
$_LANG['addons_none'] = 'No add-ons are configured for this product.';
$_LANG['addons_current'] = 'You have';
$_LANG['addons_options'] = 'Available';
$_LANG['addons_included'] = 'included';
$_LANG['addons_per_unit'] = 'per unit';
$_LANG['per_month'] = 'Per month';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 minutes';
$_LANG['range_6h'] = '6 hours';
$_LANG['network_packets'] = 'Network packets';
$_LANG['disk_iops'] = 'Disk IOPS';
$_LANG['average'] = 'avg';
$_LANG['peak'] = 'peak';
$_LANG['table_view'] = 'Table view';
$_LANG['timestamp'] = 'Time';
$_LANG['cpu_scale_hint'] = '1 vCPU = 100%';
