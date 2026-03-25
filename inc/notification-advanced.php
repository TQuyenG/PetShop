<?php
/**
 * PetShop Advanced Notification System
 * Hệ thống thông báo nâng cao với real-time và multi-channel
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// LOẠI THÔNG BÁO
// =============================================
function petshop_get_notification_types() {
    return array(
        'order' => array(
            'label' => 'Đơn hàng',
            'icon' => 'bi-bag-check',
            'color' => '#28a745'
        ),
        'promotion' => array(
            'label' => 'Khuyến mãi',
            'icon' => 'bi-tag',
            'color' => '#EC802B'
        ),
        'voucher' => array(
            'label' => 'Voucher',
            'icon' => 'bi-ticket-perforated',
            'color' => '#E91E63'
        ),
        'points' => array(
            'label' => 'Điểm thưởng',
            'icon' => 'bi-coin',
            'color' => '#FF9800'
        ),
        'tier' => array(
            'label' => 'Hạng thành viên',
            'icon' => 'bi-trophy',
            'color' => '#9C27B0'
        ),
        'system' => array(
            'label' => 'Hệ thống',
            'icon' => 'bi-info-circle',
            'color' => '#17a2b8'
        ),
        'flash_sale' => array(
            'label' => 'Flash Sale',
            'icon' => 'bi-lightning',
            'color' => '#dc3545'
        ),
        'reminder' => array(
            'label' => 'Nhắc nhở',
            'icon' => 'bi-clock',
            'color' => '#6c757d'
        ),
    );
}

// =============================================
// KÊNH GỬI THÔNG BÁO
// =============================================
function petshop_get_notification_channels() {
    return array(
        'system' => array(
            'label' => 'Thông báo hệ thống',
            'icon' => 'bi-bell',
            'description' => 'Hiển thị trên website'
        ),
        'email' => array(
            'label' => 'Email',
            'icon' => 'bi-envelope',
            'description' => 'Gửi qua email'
        ),
        'sms' => array(
            'label' => 'SMS',
            'icon' => 'bi-phone',
            'description' => 'Gửi tin nhắn SMS'
        ),
    );
}

// =============================================
// GỬI THÔNG BÁO NÂNG CAO
// =============================================
function petshop_send_advanced_notification($args) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    $defaults = array(
        'user_ids' => array(),      // Mảng user_id hoặc 'all', 'tier:gold', 'tier:silver', 'tier:bronze'
        'type' => 'system',
        'title' => '',
        'message' => '',
        'link' => '',
        'channels' => array('system'), // system, email, sms
        'schedule' => '', // empty = send now, or datetime
    );
    
    $args = wp_parse_args($args, $defaults);
    $types = petshop_get_notification_types();
    $type_info = $types[$args['type']] ?? $types['system'];
    
    // Xác định danh sách user
    $user_ids = array();
    
    if (is_array($args['user_ids'])) {
        if (in_array('all', $args['user_ids'])) {
            // Tất cả customers
            $users = get_users(array('role__in' => array('subscriber', 'customer', 'petshop_customer'), 'fields' => 'ID'));
            $user_ids = array_map('intval', $users);
        } elseif (!empty($args['user_ids'])) {
            foreach ($args['user_ids'] as $uid) {
                if (strpos($uid, 'tier:') === 0) {
                    // Lọc theo tier
                    $tier = str_replace('tier:', '', $uid);
                    $tier_users = petshop_get_users_by_tier($tier);
                    $user_ids = array_merge($user_ids, $tier_users);
                } else {
                    $user_ids[] = intval($uid);
                }
            }
        }
    }
    
    $user_ids = array_unique($user_ids);
    $sent_count = 0;
    $results = array('system' => 0, 'email' => 0, 'sms' => 0);
    
    foreach ($user_ids as $user_id) {
        // 1. Thông báo hệ thống
        if (in_array('system', $args['channels'])) {
            $inserted = $wpdb->insert($table, array(
                'user_id' => $user_id,
                'type' => $args['type'],
                'title' => $args['title'],
                'message' => $args['message'],
                'link' => $args['link'],
                'icon' => $type_info['icon'],
                'color' => $type_info['color'],
                'is_read' => 0,
                'email_sent' => 0,
                'created_at' => current_time('mysql')
            ));
            if ($inserted) $results['system']++;
        }
        
        // 2. Email
        if (in_array('email', $args['channels'])) {
            $email_sent = petshop_send_notification_email_advanced($user_id, $args);
            if ($email_sent) $results['email']++;
        }
        
        // 3. SMS
        if (in_array('sms', $args['channels'])) {
            $sms_sent = petshop_send_notification_sms($user_id, $args);
            if ($sms_sent) $results['sms']++;
        }
        
        $sent_count++;
    }
    
    return array(
        'total_users' => $sent_count,
        'results' => $results
    );
}

// =============================================
// LẤY USER THEO TIER
// =============================================
function petshop_get_users_by_tier($tier) {
    $users = get_users(array(
        'role__in' => array('subscriber', 'customer', 'petshop_customer'),
        'fields' => 'ID'
    ));
    
    $tier_users = array();
    foreach ($users as $user_id) {
        if (function_exists('petshop_get_customer_tier')) {
            $user_tier = petshop_get_customer_tier($user_id);
            if ($user_tier === $tier) {
                $tier_users[] = intval($user_id);
            }
        }
    }
    
    return $tier_users;
}

// =============================================
// GỬI EMAIL THÔNG BÁO
// =============================================
function petshop_send_notification_email_advanced($user_id, $args) {
    $user = get_userdata($user_id);
    if (!$user || !$user->user_email) return false;
    
    $types = petshop_get_notification_types();
    $type_info = $types[$args['type']] ?? $types['system'];
    
    $site_name = get_bloginfo('name');
    $subject = "[{$site_name}] " . $args['title'];
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); padding: 30px; text-align: center; border-radius: 15px 15px 0 0;'>
            <h1 style='color: #fff; margin: 0; font-size: 24px;'>{$site_name}</h1>
        </div>
        <div style='background: #fff; padding: 30px; border-radius: 0 0 15px 15px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <span style='display: inline-block; width: 60px; height: 60px; background: {$type_info['color']}20; border-radius: 50%; line-height: 60px;'>
                    <i class='{$type_info['icon']}' style='font-size: 28px; color: {$type_info['color']};'></i>
                </span>
            </div>
            <h2 style='color: #333; margin: 0 0 15px; text-align: center;'>{$args['title']}</h2>
            <p style='color: #666; line-height: 1.8; font-size: 15px;'>" . nl2br(esc_html($args['message'])) . "</p>
            " . ($args['link'] ? "
            <p style='text-align: center; margin-top: 25px;'>
                <a href='{$args['link']}' style='display: inline-block; background: linear-gradient(135deg, #EC802B, #F5994D); color: #fff; padding: 14px 35px; text-decoration: none; border-radius: 30px; font-weight: 600;'>Xem chi tiết</a>
            </p>" : "") . "
        </div>
        <p style='text-align: center; color: #999; font-size: 12px; margin-top: 20px;'>
            Bạn nhận được email này vì đã đăng ký tài khoản tại {$site_name}
        </p>
    </div>";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($user->user_email, $subject, $body, $headers);
}

// =============================================
// GỬI SMS (Placeholder - cần tích hợp SMS gateway)
// =============================================
function petshop_send_notification_sms($user_id, $args) {
    $phone = get_user_meta($user_id, 'billing_phone', true);
    if (!$phone) $phone = get_user_meta($user_id, 'petshop_phone', true);
    
    if (!$phone) return false;
    
    // TODO: Tích hợp SMS Gateway (Twilio, SpeedSMS, VNPT...)
    // Hiện tại chỉ log
    $sms_log = get_option('petshop_sms_log', array());
    $sms_log[] = array(
        'phone' => $phone,
        'message' => $args['title'] . ': ' . wp_trim_words($args['message'], 30),
        'time' => current_time('mysql')
    );
    update_option('petshop_sms_log', array_slice($sms_log, -100)); // Keep last 100
    
    return true; // Simulated success
}

// =============================================
// XÓA THÔNG BÁO
// =============================================
function petshop_delete_notification($notification_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    return $wpdb->delete($table, array(
        'id' => $notification_id,
        'user_id' => $user_id
    ));
}

function petshop_delete_all_read_notifications($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    return $wpdb->delete($table, array(
        'user_id' => $user_id,
        'is_read' => 1
    ));
}

// =============================================
// AJAX: LẤY THÔNG BÁO REAL-TIME
// =============================================
add_action('wp_ajax_petshop_get_notifications_realtime', 'petshop_ajax_get_notifications_realtime');
function petshop_ajax_get_notifications_realtime() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized');
    }
    
    $user_id = get_current_user_id();
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    // Lấy notifications mới hơn last_id
    $where = "WHERE user_id = %d";
    $params = array($user_id);
    
    if ($last_id > 0) {
        $where .= " AND id > %d";
        $params[] = $last_id;
    }
    
    if ($type_filter && $type_filter !== 'all') {
        $where .= " AND type = %s";
        $params[] = $type_filter;
    }
    
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 50",
        ...$params
    ));
    
    $unread_count = petshop_count_unread_notifications($user_id);
    
    // Format data
    $types = petshop_get_notification_types();
    $formatted = array();
    
    foreach ($notifications as $notif) {
        $type_info = $types[$notif->type] ?? $types['system'];
        $formatted[] = array(
            'id' => $notif->id,
            'type' => $notif->type,
            'type_label' => $type_info['label'],
            'icon' => $type_info['icon'],
            'color' => $type_info['color'],
            'title' => $notif->title,
            'message' => $notif->message,
            'link' => $notif->link,
            'is_read' => (bool) $notif->is_read,
            'created_at' => $notif->created_at,
            'time_ago' => human_time_diff(strtotime($notif->created_at), current_time('timestamp')) . ' trước'
        );
    }
    
    wp_send_json_success(array(
        'notifications' => $formatted,
        'unread_count' => $unread_count,
        'max_id' => !empty($notifications) ? max(array_column($notifications, 'id')) : $last_id
    ));
}

// =============================================
// AJAX: ĐÁNH DẤU ĐÃ ĐỌC
// =============================================
add_action('wp_ajax_petshop_mark_notification_read_v2', 'petshop_ajax_mark_read_v2');
function petshop_ajax_mark_read_v2() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized');
    }
    
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if ($notification_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_notifications';
        $wpdb->update($table, 
            array('is_read' => 1), 
            array('id' => $notification_id, 'user_id' => $user_id)
        );
    }
    
    wp_send_json_success(array(
        'unread_count' => petshop_count_unread_notifications($user_id)
    ));
}

// =============================================
// AJAX: ĐÁNH DẤU TẤT CẢ ĐÃ ĐỌC
// =============================================
add_action('wp_ajax_petshop_mark_all_read_v2', 'petshop_ajax_mark_all_read_v2');
function petshop_ajax_mark_all_read_v2() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized');
    }
    
    $user_id = get_current_user_id();
    petshop_mark_all_notifications_read($user_id);
    
    wp_send_json_success(array('unread_count' => 0));
}

// =============================================
// AJAX: XÓA THÔNG BÁO
// =============================================
add_action('wp_ajax_petshop_delete_notification', 'petshop_ajax_delete_notification');
function petshop_ajax_delete_notification() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized');
    }
    
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if ($notification_id) {
        petshop_delete_notification($notification_id, $user_id);
    }
    
    wp_send_json_success(array(
        'unread_count' => petshop_count_unread_notifications($user_id)
    ));
}

// =============================================
// AJAX: XÓA TẤT CẢ ĐÃ ĐỌC
// =============================================
add_action('wp_ajax_petshop_delete_all_read', 'petshop_ajax_delete_all_read');
function petshop_ajax_delete_all_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized');
    }
    
    $user_id = get_current_user_id();
    petshop_delete_all_read_notifications($user_id);
    
    wp_send_json_success();
}

// =============================================
// AJAX: GỬI THÔNG BÁO (ADMIN)
// =============================================
add_action('wp_ajax_petshop_admin_send_notification', 'petshop_ajax_admin_send_notification');
function petshop_ajax_admin_send_notification() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền');
    }
    
    check_ajax_referer('petshop_admin_notification', 'nonce');
    
    $user_ids = isset($_POST['user_ids']) ? array_map('sanitize_text_field', $_POST['user_ids']) : array();
    $type = sanitize_text_field($_POST['type'] ?? 'system');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $link = esc_url_raw($_POST['link'] ?? '');
    $channels = isset($_POST['channels']) ? array_map('sanitize_text_field', $_POST['channels']) : array('system');
    
    if (empty($title) || empty($message)) {
        wp_send_json_error('Vui lòng nhập tiêu đề và nội dung');
    }
    
    if (empty($user_ids)) {
        wp_send_json_error('Vui lòng chọn người nhận');
    }
    
    if (empty($channels)) {
        wp_send_json_error('Vui lòng chọn ít nhất một kênh gửi');
    }
    
    $result = petshop_send_advanced_notification(array(
        'user_ids' => $user_ids,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link,
        'channels' => $channels
    ));
    
    wp_send_json_success(array(
        'message' => sprintf('Đã gửi thông báo đến %d người dùng', $result['total_users']),
        'details' => $result['results']
    ));
}

// =============================================
// AJAX: TÌM KIẾM USER (CHO ADMIN)
// =============================================
add_action('wp_ajax_petshop_search_users', 'petshop_ajax_search_users');
function petshop_ajax_search_users() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $search = sanitize_text_field($_GET['search'] ?? '');
    
    $users = get_users(array(
        'search' => '*' . $search . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => 20,
        'role__in' => array('subscriber', 'customer', 'petshop_customer', 'administrator')
    ));
    
    $results = array();
    foreach ($users as $user) {
        $tier = function_exists('petshop_get_customer_tier') ? petshop_get_customer_tier($user->ID) : '';
        $results[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'tier' => $tier
        );
    }
    
    wp_send_json_success($results);
}

// =============================================
// ADMIN PAGE: GỬI THÔNG BÁO NÂNG CAO
// Menu đã được gộp vào communication-menu.php
// =============================================
/* Commented out - moved to communication-menu.php
add_action('admin_menu', 'petshop_register_advanced_notification_menu', 99);
function petshop_register_advanced_notification_menu() {
    // Thay thế trang gửi thông báo cũ
    remove_submenu_page('petshop-notifications', 'petshop-send-notification');
    
    add_submenu_page(
        'petshop-notifications',
        'Gửi thông báo',
        'Gửi thông báo',
        'manage_options',
        'petshop-send-notification-advanced',
        'petshop_advanced_send_notification_page'
    );
}
*/

function petshop_advanced_send_notification_page() {
    $types = petshop_get_notification_types();
    $channels = petshop_get_notification_channels();
    
    // Lấy danh sách user cho dropdown
    $customers = get_users(array(
        'role__in' => array('subscriber', 'customer', 'petshop_customer'),
        'number' => 100,
        'orderby' => 'display_name'
    ));
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-megaphone" style="font-size: 30px; color: #EC802B;"></span>
            Gửi thông báo
        </h1>
        
        <style>
            .notif-form { max-width: 900px; margin-top: 20px; }
            .notif-card { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .notif-card h3 { margin: 0 0 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
            .notif-card h3 i { color: #EC802B; }
            
            .form-row { margin-bottom: 20px; }
            .form-row label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
            .form-row input[type="text"],
            .form-row input[type="url"],
            .form-row textarea,
            .form-row select { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
            .form-row textarea { min-height: 120px; resize: vertical; }
            .form-row small { display: block; margin-top: 5px; color: #888; }
            
            .recipient-options { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px; }
            .recipient-option { 
                display: flex; align-items: center; gap: 10px; 
                padding: 15px 20px; background: #f8f9fa; border: 2px solid #e9ecef;
                border-radius: 10px; cursor: pointer; transition: all 0.2s;
            }
            .recipient-option:hover { border-color: #EC802B; }
            .recipient-option.selected { border-color: #EC802B; background: #FDF8F3; }
            .recipient-option input { display: none; }
            .recipient-option i { font-size: 24px; color: #EC802B; }
            .recipient-option span strong { display: block; }
            .recipient-option span small { color: #888; }
            
            .user-search-box { margin-top: 15px; display: none; }
            .user-search-box.visible { display: block; }
            .user-search-input { position: relative; }
            .user-search-input input { padding-right: 40px; }
            .user-search-input .search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #888; }
            
            .selected-users { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; min-height: 40px; padding: 10px; background: #f8f9fa; border-radius: 8px; }
            .selected-user { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #EC802B; color: #fff; border-radius: 20px; font-size: 13px; }
            .selected-user .remove { cursor: pointer; opacity: 0.8; }
            .selected-user .remove:hover { opacity: 1; }
            
            .user-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; max-height: 250px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
            .user-dropdown.show { display: block; }
            .user-dropdown-item { padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
            .user-dropdown-item:hover { background: #FDF8F3; }
            .user-dropdown-item .tier { font-size: 11px; padding: 2px 8px; border-radius: 10px; background: #f0f0f0; }
            .user-dropdown-item .tier.gold { background: #FFD700; color: #333; }
            .user-dropdown-item .tier.silver { background: #C0C0C0; }
            .user-dropdown-item .tier.bronze { background: #CD7F32; color: #fff; }
            
            .type-options { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
            .type-option { 
                padding: 15px; text-align: center; border: 2px solid #e9ecef;
                border-radius: 10px; cursor: pointer; transition: all 0.2s;
            }
            .type-option:hover { border-color: #EC802B; }
            .type-option.selected { border-color: #EC802B; background: #FDF8F3; }
            .type-option input { display: none; }
            .type-option i { font-size: 24px; display: block; margin-bottom: 8px; }
            .type-option span { font-size: 13px; font-weight: 500; }
            
            .channel-options { display: flex; gap: 15px; }
            .channel-option { 
                flex: 1; padding: 20px; border: 2px solid #e9ecef;
                border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.2s;
            }
            .channel-option:hover { border-color: #EC802B; }
            .channel-option.selected { border-color: #EC802B; background: #FDF8F3; }
            .channel-option input { display: none; }
            .channel-option i { font-size: 30px; color: #EC802B; display: block; margin-bottom: 10px; }
            .channel-option strong { display: block; margin-bottom: 5px; }
            .channel-option small { color: #888; }
            
            .btn-send { 
                background: linear-gradient(135deg, #EC802B, #F5994D); 
                color: #fff; border: none; padding: 15px 40px; 
                font-size: 16px; font-weight: 600; border-radius: 30px;
                cursor: pointer; display: inline-flex; align-items: center; gap: 10px;
                transition: all 0.3s;
            }
            .btn-send:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(236, 128, 43, 0.4); }
            .btn-send:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
            
            .result-message { padding: 15px 20px; border-radius: 10px; margin-top: 20px; display: none; }
            .result-message.success { background: #d4edda; color: #155724; display: block; }
            .result-message.error { background: #f8d7da; color: #721c24; display: block; }
        </style>
        
        <form id="advancedNotificationForm" class="notif-form">
            <?php wp_nonce_field('petshop_admin_notification', 'nonce'); ?>
            
            <!-- Người nhận -->
            <div class="notif-card">
                <h3><i class="bi bi-people"></i> Người nhận</h3>
                
                <div class="recipient-options">
                    <label class="recipient-option" data-target="all">
                        <input type="checkbox" name="recipient_type[]" value="all">
                        <i class="bi bi-globe"></i>
                        <span>
                            <strong>Tất cả khách hàng</strong>
                            <small><?php echo count($customers); ?> người</small>
                        </span>
                    </label>
                    <label class="recipient-option" data-target="tier:gold">
                        <input type="checkbox" name="recipient_type[]" value="tier:gold">
                        <i class="bi bi-trophy-fill" style="color: #FFD700;"></i>
                        <span>
                            <strong>Hạng Vàng</strong>
                            <small>VIP members</small>
                        </span>
                    </label>
                    <label class="recipient-option" data-target="tier:silver">
                        <input type="checkbox" name="recipient_type[]" value="tier:silver">
                        <i class="bi bi-gem" style="color: #C0C0C0;"></i>
                        <span>
                            <strong>Hạng Bạc</strong>
                            <small>Silver members</small>
                        </span>
                    </label>
                    <label class="recipient-option" data-target="tier:bronze">
                        <input type="checkbox" name="recipient_type[]" value="tier:bronze">
                        <i class="bi bi-award" style="color: #CD7F32;"></i>
                        <span>
                            <strong>Hạng Đồng</strong>
                            <small>Bronze members</small>
                        </span>
                    </label>
                    <label class="recipient-option" data-target="specific">
                        <input type="checkbox" name="recipient_type[]" value="specific">
                        <i class="bi bi-person-plus"></i>
                        <span>
                            <strong>Chọn cụ thể</strong>
                            <small>Tìm và chọn</small>
                        </span>
                    </label>
                </div>
                
                <div id="userSearchBox" class="user-search-box">
                    <label>Tìm kiếm khách hàng</label>
                    <div class="user-search-input">
                        <input type="text" id="userSearchInput" placeholder="Nhập tên, email hoặc số điện thoại...">
                        <i class="bi bi-search search-icon"></i>
                        <div id="userDropdown" class="user-dropdown"></div>
                    </div>
                    <div id="selectedUsers" class="selected-users">
                        <span style="color: #888;">Chưa chọn người nhận cụ thể</span>
                    </div>
                </div>
                
                <input type="hidden" name="user_ids" id="userIdsInput" value="">
            </div>
            
            <!-- Loại thông báo -->
            <div class="notif-card">
                <h3><i class="bi bi-tag"></i> Loại thông báo</h3>
                <div class="type-options">
                    <?php foreach ($types as $key => $type): ?>
                    <label class="type-option <?php echo $key === 'promotion' ? 'selected' : ''; ?>">
                        <input type="radio" name="type" value="<?php echo $key; ?>" <?php checked($key, 'promotion'); ?>>
                        <i class="bi <?php echo $type['icon']; ?>" style="color: <?php echo $type['color']; ?>;"></i>
                        <span><?php echo $type['label']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Nội dung -->
            <div class="notif-card">
                <h3><i class="bi bi-chat-left-text"></i> Nội dung</h3>
                
                <div class="form-row">
                    <label>Tiêu đề <span style="color: #dc3545;">*</span></label>
                    <input type="text" name="title" required placeholder="VD: Flash Sale - Giảm 50% hôm nay!">
                </div>
                
                <div class="form-row">
                    <label>Nội dung <span style="color: #dc3545;">*</span></label>
                    <textarea name="message" required placeholder="Nhập nội dung thông báo chi tiết..."></textarea>
                </div>
                
                <div class="form-row">
                    <label>Link đính kèm (tùy chọn)</label>
                    <input type="url" name="link" placeholder="https://...">
                    <small>Người dùng sẽ được chuyển đến link này khi nhấp vào thông báo</small>
                </div>
            </div>
            
            <!-- Kênh gửi -->
            <div class="notif-card">
                <h3><i class="bi bi-send"></i> Kênh gửi</h3>
                <div class="channel-options">
                    <?php foreach ($channels as $key => $channel): ?>
                    <label class="channel-option <?php echo $key === 'system' ? 'selected' : ''; ?>">
                        <input type="checkbox" name="channels[]" value="<?php echo $key; ?>" <?php checked($key, 'system'); ?>>
                        <i class="bi <?php echo $channel['icon']; ?>"></i>
                        <strong><?php echo $channel['label']; ?></strong>
                        <small><?php echo $channel['description']; ?></small>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="btn-send" id="sendBtn">
                    <i class="bi bi-send-fill"></i> Gửi thông báo
                </button>
            </div>
            
            <div id="resultMessage" class="result-message"></div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            var selectedUserIds = [];
            
            // Toggle recipient options
            $('.recipient-option').on('click', function() {
                $(this).toggleClass('selected');
                $(this).find('input').prop('checked', $(this).hasClass('selected'));
                
                // Show/hide user search box
                if ($('[data-target="specific"]').hasClass('selected')) {
                    $('#userSearchBox').addClass('visible');
                } else {
                    $('#userSearchBox').removeClass('visible');
                }
                
                updateUserIds();
            });
            
            // Toggle type options
            $('.type-option').on('click', function() {
                $('.type-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Toggle channel options
            $('.channel-option').on('click', function() {
                $(this).toggleClass('selected');
                $(this).find('input').prop('checked', $(this).hasClass('selected'));
            });
            
            // User search
            var searchTimeout;
            $('#userSearchInput').on('input', function() {
                clearTimeout(searchTimeout);
                var search = $(this).val();
                
                if (search.length < 2) {
                    $('#userDropdown').removeClass('show').empty();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $.get(ajaxurl, {
                        action: 'petshop_search_users',
                        search: search
                    }, function(res) {
                        if (res.success && res.data.length) {
                            var html = '';
                            res.data.forEach(function(user) {
                                if (selectedUserIds.indexOf(user.id.toString()) === -1) {
                                    html += '<div class="user-dropdown-item" data-id="' + user.id + '" data-name="' + user.name + '">';
                                    html += '<span>' + user.name + ' <small style="color:#888;">(' + user.email + ')</small></span>';
                                    if (user.tier) {
                                        html += '<span class="tier ' + user.tier + '">' + user.tier.toUpperCase() + '</span>';
                                    }
                                    html += '</div>';
                                }
                            });
                            $('#userDropdown').html(html || '<div style="padding:15px;color:#888;">Không tìm thấy</div>').addClass('show');
                        } else {
                            $('#userDropdown').html('<div style="padding:15px;color:#888;">Không tìm thấy</div>').addClass('show');
                        }
                    });
                }, 300);
            });
            
            // Select user from dropdown
            $(document).on('click', '.user-dropdown-item', function() {
                var id = $(this).data('id').toString();
                var name = $(this).data('name');
                
                if (selectedUserIds.indexOf(id) === -1) {
                    selectedUserIds.push(id);
                    renderSelectedUsers();
                }
                
                $('#userSearchInput').val('');
                $('#userDropdown').removeClass('show');
                updateUserIds();
            });
            
            // Remove selected user
            $(document).on('click', '.selected-user .remove', function() {
                var id = $(this).parent().data('id').toString();
                selectedUserIds = selectedUserIds.filter(function(uid) { return uid !== id; });
                renderSelectedUsers();
                updateUserIds();
            });
            
            function renderSelectedUsers() {
                var container = $('#selectedUsers');
                if (selectedUserIds.length === 0) {
                    container.html('<span style="color: #888;">Chưa chọn người nhận cụ thể</span>');
                    return;
                }
                
                var html = '';
                selectedUserIds.forEach(function(id) {
                    // We need to store names too, but for simplicity just show ID
                    html += '<span class="selected-user" data-id="' + id + '">';
                    html += 'User #' + id;
                    html += '<span class="remove"><i class="bi bi-x"></i></span></span>';
                });
                container.html(html);
            }
            
            function updateUserIds() {
                var ids = [];
                
                // Check for tier selections
                $('.recipient-option.selected input').each(function() {
                    var val = $(this).val();
                    if (val !== 'specific') {
                        ids.push(val);
                    }
                });
                
                // Add specific user IDs
                if ($('[data-target="specific"]').hasClass('selected')) {
                    ids = ids.concat(selectedUserIds);
                }
                
                $('#userIdsInput').val(ids.join(','));
            }
            
            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-search-input').length) {
                    $('#userDropdown').removeClass('show');
                }
            });
            
            // Submit form
            $('#advancedNotificationForm').on('submit', function(e) {
                e.preventDefault();
                
                var userIds = $('#userIdsInput').val();
                if (!userIds) {
                    alert('Vui lòng chọn người nhận');
                    return;
                }
                
                var channels = [];
                $('input[name="channels[]"]:checked').each(function() {
                    channels.push($(this).val());
                });
                
                if (channels.length === 0) {
                    alert('Vui lòng chọn ít nhất một kênh gửi');
                    return;
                }
                
                var $btn = $('#sendBtn');
                $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> Đang gửi...');
                
                $.post(ajaxurl, {
                    action: 'petshop_admin_send_notification',
                    nonce: $('[name="nonce"]').val(),
                    user_ids: userIds.split(','),
                    type: $('input[name="type"]:checked').val(),
                    title: $('input[name="title"]').val(),
                    message: $('textarea[name="message"]').val(),
                    link: $('input[name="link"]').val(),
                    channels: channels
                }, function(res) {
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Gửi thông báo');
                    
                    if (res.success) {
                        $('#resultMessage').removeClass('error').addClass('success')
                            .html('<i class="bi bi-check-circle"></i> ' + res.data.message + 
                                  '<br><small>Hệ thống: ' + res.data.details.system + 
                                  ' | Email: ' + res.data.details.email + 
                                  ' | SMS: ' + res.data.details.sms + '</small>');
                        
                        // Reset form
                        $('#advancedNotificationForm')[0].reset();
                        $('.recipient-option, .type-option, .channel-option').removeClass('selected');
                        $('[data-target="all"]').addClass('selected').find('input').prop('checked', true);
                        $('[value="promotion"]').parent().addClass('selected');
                        $('[value="system"]').parent().addClass('selected').find('input').prop('checked', true);
                        selectedUserIds = [];
                        renderSelectedUsers();
                        $('#userSearchBox').removeClass('visible');
                    } else {
                        $('#resultMessage').removeClass('success').addClass('error')
                            .html('<i class="bi bi-exclamation-circle"></i> ' + res.data);
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}
