<?php
/** Hetzner Cloud module - Türkçe. Eksik anahtarlar İngilizce dosyadan alınır. */

$_LANG['module_name'] = 'Hetzner Cloud';

$_LANG['tab_overview'] = 'Genel bakış';
$_LANG['tab_graphs'] = 'Grafikler';
$_LANG['tab_backups'] = 'Yedekler';
$_LANG['tab_snapshots'] = 'Anlık görüntüler';
$_LANG['tab_network'] = 'Ağ';
$_LANG['tab_firewall'] = 'Güvenlik duvarı';
$_LANG['tab_iso'] = 'ISO';
$_LANG['tab_rescue'] = 'Kurtarma';
$_LANG['tab_rebuild'] = 'Yeniden kur';
$_LANG['tab_ssh'] = 'SSH anahtarları';
$_LANG['tab_history'] = 'Geçmiş';
$_LANG['tab_settings'] = 'Ayarlar';

$_LANG['status_running'] = 'Çalışıyor';
$_LANG['status_off'] = 'Kapalı';
$_LANG['status_starting'] = 'Başlatılıyor';
$_LANG['status_stopping'] = 'Durduruluyor';
$_LANG['status_initializing'] = 'Hazırlanıyor';
$_LANG['status_migrating'] = 'Taşınıyor';
$_LANG['status_rebuilding'] = 'Yeniden kuruluyor';
$_LANG['status_deleting'] = 'Siliniyor';
$_LANG['status_unknown'] = 'Bilinmiyor';
$_LANG['status_provisioning'] = 'Oluşturuluyor';

$_LANG['server_name'] = 'Sunucu adı';
$_LANG['server_id'] = 'Sunucu kimliği';
$_LANG['ip_address'] = 'IP adresi';
$_LANG['ipv4'] = 'IPv4';
$_LANG['ipv6'] = 'IPv6';
$_LANG['private_ip'] = 'Özel IP';
$_LANG['operating_system'] = 'İşletim sistemi';
$_LANG['server_type'] = 'Sunucu tipi';
$_LANG['location'] = 'Konum';
$_LANG['datacenter'] = 'Veri merkezi';
$_LANG['created'] = 'Oluşturulma';
$_LANG['status'] = 'Durum';
$_LANG['vcpu'] = 'vCPU';
$_LANG['ram'] = 'Bellek';
$_LANG['disk'] = 'Disk';
$_LANG['architecture'] = 'Mimari';
$_LANG['price'] = 'Fiyat';
$_LANG['protection'] = 'Koruma';

$_LANG['power'] = 'Güç';
$_LANG['power_on'] = 'Aç';
$_LANG['power_off'] = 'Kapat';
$_LANG['shutdown'] = 'Düzgün kapat';
$_LANG['reboot'] = 'Yeniden başlat';
$_LANG['reset'] = 'Donanımsal sıfırlama';
$_LANG['console'] = 'Konsol';
$_LANG['open_console'] = 'Konsolu aç';

$_LANG['access_info'] = 'Erişim bilgileri';
$_LANG['username'] = 'Kullanıcı adı';
$_LANG['password'] = 'Parola';
$_LANG['show'] = 'Göster';
$_LANG['hide'] = 'Gizle';
$_LANG['copy'] = 'Kopyala';
$_LANG['copied'] = 'Panoya kopyalandı';
$_LANG['reset_password'] = 'Root parolasını sıfırla';
$_LANG['ssh_command'] = 'SSH komutu';
$_LANG['password_reset_done'] = 'Yeni bir root parolası oluşturuldu ve hizmetinize kaydedildi.';
$_LANG['password_hidden'] = 'Gizli';

$_LANG['cpu_usage'] = 'CPU kullanımı';
$_LANG['disk_io'] = 'Disk G/Ç';
$_LANG['network_traffic'] = 'Ağ trafiği';
$_LANG['range_1h'] = '1 saat';
$_LANG['range_24h'] = '24 saat';
$_LANG['range_7d'] = '7 gün';
$_LANG['range_30d'] = '30 gün';
$_LANG['no_data'] = 'Bu dönem için veri yok.';
$_LANG['read'] = 'Okuma';
$_LANG['write'] = 'Yazma';
$_LANG['inbound'] = 'Gelen';
$_LANG['outbound'] = 'Giden';

$_LANG['bandwidth'] = 'Trafik kullanımı';
$_LANG['included_traffic'] = 'Dahil trafik';
$_LANG['outgoing_traffic'] = 'Yükleme (giden)';
$_LANG['incoming_traffic'] = 'İndirme (gelen)';
$_LANG['traffic_used'] = 'Kullanılan';
$_LANG['traffic_remaining'] = 'Kalan';
$_LANG['traffic_period'] = 'Geçerli faturalama dönemi';
$_LANG['traffic_overage'] = 'Bu dönemin dahil trafiği aşıldı.';

$_LANG['backups'] = 'Otomatik yedekler';
$_LANG['backup_intro'] = 'Hetzner yedi adede kadar dönüşümlü yedek tutar. Etkinleştirmek sunucu fiyatına %20 ekler.';
$_LANG['enable_backups'] = 'Yedeklemeyi aç';
$_LANG['disable_backups'] = 'Yedeklemeyi kapat';
$_LANG['backups_enabled'] = 'Yedekleme açık';
$_LANG['backups_disabled'] = 'Yedekleme kapalı';
$_LANG['backup_window'] = 'Yedekleme aralığı';
$_LANG['no_backups'] = 'Henüz yedek oluşturulmadı.';
$_LANG['confirm_disable_backups'] = 'Kapatmak mevcut tüm yedekleri siler. Devam edilsin mi?';

$_LANG['snapshots'] = 'Anlık görüntüler';
$_LANG['snapshot_intro'] = 'Anlık görüntü, istediğiniz zaman geri yükleyebileceğiniz tam bir sunucu imajıdır.';
$_LANG['create_snapshot'] = 'Anlık görüntü oluştur';
$_LANG['snapshot_name'] = 'Anlık görüntü adı';
$_LANG['no_snapshots'] = 'Anlık görüntünüz yok.';
$_LANG['restore'] = 'Geri yükle';
$_LANG['size'] = 'Boyut';
$_LANG['limit_reached'] = 'Bu hizmet için {limit} sınırına ulaştınız.';
$_LANG['confirm_restore'] = 'Geri yükleme sunucudaki tüm dosyaları bu imajla değiştirir. Devam edilsin mi?';

$_LANG['floating_ips'] = 'Yüzen IP\'ler';
$_LANG['floating_ip_intro'] = 'Yüzen IP\'ler sunucular arasında taşınabilir ve yeniden kurulumdan sonra da kalır.';
$_LANG['create_floating_ip'] = 'Yüzen IP siparişi';
$_LANG['assign'] = 'Ata';
$_LANG['unassign'] = 'Atamayı kaldır';
$_LANG['no_floating_ips'] = 'Bu hizmete atanmış yüzen IP yok.';
$_LANG['protocol'] = 'Protokol';
$_LANG['assigned'] = 'Atandı';
$_LANG['unassigned'] = 'Atanmadı';

$_LANG['reverse_dns'] = 'Ters DNS';
$_LANG['rdns_intro'] = 'IPv4 ve IPv6 adresleriniz için PTR kaydını ayarlayın.';
$_LANG['hostname'] = 'Ana bilgisayar adı';
$_LANG['set_rdns'] = 'PTR kaydet';
$_LANG['rdns_saved'] = 'Ters DNS kaydı güncellendi.';

$_LANG['networks'] = 'Özel ağlar';
$_LANG['network_intro'] = 'Özel ağlar sunucularınızın izole bir 10.x ağı üzerinden haberleşmesini sağlar.';
$_LANG['create_network'] = 'Ağ oluştur';
$_LANG['network_name'] = 'Ağ adı';
$_LANG['ip_range'] = 'IP aralığı';
$_LANG['subnets'] = 'Alt ağlar';
$_LANG['add_subnet'] = 'Alt ağ ekle';
$_LANG['routes'] = 'Rotalar';
$_LANG['add_route'] = 'Rota ekle';
$_LANG['destination'] = 'Hedef';
$_LANG['gateway'] = 'Ağ geçidi';
$_LANG['network_zone'] = 'Ağ bölgesi';
$_LANG['attach'] = 'Sunucuyu bağla';
$_LANG['detach'] = 'Sunucuyu ayır';
$_LANG['no_networks'] = 'Özel ağınız yok.';
$_LANG['attached'] = 'Bağlı';
$_LANG['not_attached'] = 'Bağlı değil';

$_LANG['firewalls'] = 'Güvenlik duvarları';
$_LANG['firewall_intro'] = 'Güvenlik duvarı sunucunun önünde çalışır; engellenen trafik sunucuya hiç ulaşmaz.';
$_LANG['create_firewall'] = 'Güvenlik duvarı oluştur';
$_LANG['firewall_name'] = 'Güvenlik duvarı adı';
$_LANG['rules'] = 'Kurallar';
$_LANG['add_rule'] = 'Kural ekle';
$_LANG['direction'] = 'Yön';
$_LANG['port'] = 'Port';
$_LANG['source_ips'] = 'Kaynak IP\'ler';
$_LANG['destination_ips'] = 'Hedef IP\'ler';
$_LANG['apply_to_server'] = 'Sunucuya uygula';
$_LANG['remove_from_server'] = 'Sunucudan kaldır';
$_LANG['no_firewalls'] = 'Güvenlik duvarınız yok.';
$_LANG['applied'] = 'Uygulandı';
$_LANG['not_applied'] = 'Uygulanmadı';
$_LANG['description'] = 'Açıklama';
$_LANG['any_port'] = 'tümü';

$_LANG['iso'] = 'ISO imajı';
$_LANG['iso_intro'] = 'Kurulum ortamından başlatmak için bir ISO bağlayın. Sunucu diskten önce ISO\'dan açılır.';
$_LANG['mount_iso'] = 'Bağla';
$_LANG['unmount_iso'] = 'Ayır';
$_LANG['mounted_iso'] = 'Şu anda bağlı';
$_LANG['no_iso'] = 'Bağlı ISO yok.';
$_LANG['search_iso'] = 'ISO imajı ara';
$_LANG['iso_mounted_notice'] = 'Bir ISO bağlı. İmajdan açılması için sunucuyu yeniden başlatın.';

$_LANG['rescue_mode'] = 'Kurtarma modu';
$_LANG['rescue_intro'] = 'Kurtarma modu, bozuk bir kurulumu onarmanız için ağdan küçük bir Linux açar. Yalnızca bir sonraki açılış için geçerlidir.';
$_LANG['enable_rescue'] = 'Kurtarma modunu aç';
$_LANG['disable_rescue'] = 'Kurtarma modunu kapat';
$_LANG['rescue_enabled'] = 'Kurtarma modu bir sonraki açılış için hazır';
$_LANG['rescue_password'] = 'Kurtarma parolası';
$_LANG['rescue_type'] = 'Kurtarma sistemi';
$_LANG['rescue_reboot_hint'] = 'Kurtarma sistemine girmek için sunucuyu şimdi yeniden başlatın.';

$_LANG['rebuild'] = 'Sunucuyu yeniden kur';
$_LANG['rebuild_intro'] = 'Sunucuyu temiz bir imajdan yeniden kurun.';
$_LANG['select_os'] = 'Bir işletim sistemi seçin';
$_LANG['rebuild_warning'] = 'Yeniden kurulum sunucudaki tüm dosyaları siler. Geri alınamaz.';
$_LANG['rebuild_started'] = 'Yeniden kurulum başladı. Sunucu birkaç dakika içinde kullanıma hazır olacak.';
$_LANG['type_confirm'] = 'Onaylamak için {word} yazın';

$_LANG['change_package'] = 'Paket değiştir';
$_LANG['current_plan'] = 'Mevcut paket';
$_LANG['new_plan'] = 'Yeni paket';
$_LANG['upgrade_disk'] = 'Diski de büyüt (geri alınamaz)';
$_LANG['change_type_warning'] = 'Değişiklik sırasında sunucu kapatılacak. Disk büyütme işlemi geri alınamaz.';
$_LANG['change_package_done'] = 'Paket başarıyla değiştirildi.';

$_LANG['ssh_keys'] = 'SSH anahtarları';
$_LANG['ssh_intro'] = 'Buraya eklenen SSH anahtarları yeniden kurulumda sunucuya yerleştirilebilir.';
$_LANG['add_ssh_key'] = 'SSH anahtarı ekle';
$_LANG['key_name'] = 'Anahtar adı';
$_LANG['public_key'] = 'Genel anahtar';
$_LANG['no_ssh_keys'] = 'Bu hizmet için kayıtlı SSH anahtarı yok.';
$_LANG['fingerprint'] = 'Parmak izi';
$_LANG['ssh_key_order_label'] = 'SSH genel anahtarı (isteğe bağlı)';

$_LANG['history'] = 'Görev geçmişi';
$_LANG['command'] = 'Komut';
$_LANG['progress'] = 'İlerleme';
$_LANG['started'] = 'Başlangıç';
$_LANG['finished'] = 'Bitiş';
$_LANG['no_history'] = 'Henüz kayıtlı görev yok.';

$_LANG['loading'] = 'Yükleniyor';
$_LANG['save'] = 'Kaydet';
$_LANG['cancel'] = 'İptal';
$_LANG['close'] = 'Kapat';
$_LANG['confirm'] = 'Onayla';
$_LANG['delete'] = 'Sil';
$_LANG['create'] = 'Oluştur';
$_LANG['yes'] = 'Evet';
$_LANG['no'] = 'Hayır';
$_LANG['enabled'] = 'Açık';
$_LANG['disabled'] = 'Kapalı';
$_LANG['error'] = 'Hata';
$_LANG['success'] = 'Başarılı';
$_LANG['warning'] = 'Uyarı';
$_LANG['none'] = 'Yok';
$_LANG['name'] = 'Ad';
$_LANG['type'] = 'Tür';
$_LANG['refresh'] = 'Yenile';
$_LANG['search'] = 'Ara';
$_LANG['actions'] = 'İşlemler';
$_LANG['working'] = 'İşleniyor';
$_LANG['are_you_sure'] = 'Emin misiniz?';
$_LANG['confirm_delete'] = '{name} silinsin mi? Bu işlem geri alınamaz.';
$_LANG['feature_disabled'] = 'Bu özellik ürününüzde kullanılamıyor.';
$_LANG['server_missing'] = 'Bu hizmete henüz bir Hetzner sunucusu bağlı değil. Lütfen destek ile iletişime geçin.';
$_LANG['request_failed'] = 'İstek başarısız oldu. Lütfen tekrar deneyin.';
$_LANG['session_expired'] = 'Oturumunuz sona erdi. Sayfayı yenileyip tekrar deneyin.';
$_LANG['locked_notice'] = 'Bu sunucuda hâlâ başka bir görev çalışıyor. Lütfen bekleyin.';
$_LANG['auto_refresh'] = 'Otomatik yenileme';
$_LANG['last_updated'] = 'Son güncelleme';
$_LANG['just_now'] = 'az önce';
$_LANG['optional'] = 'isteğe bağlı';

/* Add-ons */
$_LANG['tab_addons'] = 'Ek özellikler';
$_LANG['addons_intro'] = 'Ek özellikler ürününüzün yapılandırılabilir seçenekleri olarak satılır. Seçin, WHMCS değişikliği faturalandırsın.';
$_LANG['addons_buy'] = 'Ek özellik satın al veya değiştir';
$_LANG['addons_none'] = 'Bu ürün için ek özellik tanımlanmamış.';
$_LANG['addons_current'] = 'Sahip olduğunuz';
$_LANG['addons_options'] = 'Mevcut';
$_LANG['addons_included'] = 'dahil';
$_LANG['addons_per_unit'] = 'birim başına';
$_LANG['per_month'] = 'Aylık';

/* Metrics dashboard */
$_LANG['range_30m'] = '30 dakika';
$_LANG['range_6h'] = '6 saat';
$_LANG['network_packets'] = 'Ağ paketleri';
$_LANG['disk_iops'] = 'Disk IOPS';
$_LANG['average'] = 'ort';
$_LANG['peak'] = 'tepe';
$_LANG['table_view'] = 'Tablo görünümü';
$_LANG['timestamp'] = 'Zaman';
$_LANG['cpu_scale_hint'] = '1 vCPU = %100';
