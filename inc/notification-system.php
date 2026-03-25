<?php
/**
 * PetShop Notification System
 * Hệ thống thông báo cho Admin và Khách hàng
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// TẠO BẢNG DATABASE
// =============================================
function petshop_create_notification_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_notifications = $wpdb->prefix . 'petshop_notifications';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_notifications (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        link VARCHAR(500) DEFAULT NULL,
        icon VARCHAR(50) DEFAULT 'bi-bell',
        color VARCHAR(20) DEFAULT '#EC802B',
        is_read TINYINT(1) DEFAULT 0,
        email_sent TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY type (type),
        KEY is_read (is_read),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'petshop_create_notification_tables');
add_action('admin_init', 'petshop_create_notification_tables');

// =============================================
// CÀI ĐẶT THÔNG BÁO
// =============================================
function petshop_get_notification_settings() {
    return get_option('petshop_notification_settings', array(
        // Admin notifications
        'admin_new_order' => true,
        'admin_new_review' => true,
        'admin_new_customer' => true,
        'admin_low_stock' => true,
        'admin_email' => get_option('admin_email'),
        
        // Customer notifications
        'customer_order_confirmed' => true,
        'customer_order_processing' => true,
        'customer_order_shipping' => true,
        'customer_order_completed' => true,
        'customer_order_cancelled' => true,
        'customer_tier_upgrade' => true,
        'customer_new_voucher' => true,
        'customer_points_earned' => true,
        'customer_birthday' => true,
        
        // Email settings
        'send_email' => true,
    ));
}

// =============================================
// TẠO THÔNG BÁO MỚI
// =============================================
function petshop_create_notification($user_id, $type, $title, $message, $args = array()) {
    global $wpdb;
    
    $defaults = array(
        'link' => '',
        'icon' => 'bi-bell',
        'color' => '#EC802B',
        'send_email' => true
    );
    $args = wp_parse_args($args, $defaults);
    
    $table = $wpdb->prefix . 'petshop_notifications';
    
    $wpdb->insert($table, array(
        'user_id' => $user_id,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $args['link'],
        'icon' => $args['icon'],
        'color' => $args['color'],
        'is_read' => 0,
        'email_sent' => 0,
        'created_at' => current_time('mysql')
    ));
    
    $notification_id = $wpdb->insert_id;
    
    // Gửi email nếu được bật
    $settings = petshop_get_notification_settings();
    if ($args['send_email'] && $settings['send_email']) {
        petshop_send_notification_email($user_id, $title, $message, $args['link']);
        $wpdb->update($table, array('email_sent' => 1), array('id' => $notification_id));
    }
    
    return $notification_id;
}

// =============================================
// GỬI EMAIL THÔNG BÁO
// =============================================
function petshop_send_notification_email($user_id, $title, $message, $link = '') {
    $user = get_userdata($user_id);
    if (!$user || !$user->user_email) return false;
    
    $site_name = get_bloginfo('name');
    $subject = "[{$site_name}] {$title}";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #EC802B, #F5994D); padding: 30px; text-align: center;'>
            <h1 style='color: #fff; margin: 0;'>{$site_name}</h1>
        </div>
        <div style='padding: 30px; background: #fff;'>
            <h2 style='color: #333; margin: 0 0 15px;'>{$title}</h2>
            <p style='color: #666; line-height: 1.6;'>{$message}</p>
            " . ($link ? "<p style='margin-top: 25px;'><a href='{$link}' style='display: inline-block; background: #EC802B; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 8px;'>Xem chi tiết</a></p>" : "") . "
        </div>
        <div style='padding: 20px; background: #f5f5f5; text-align: center; color: #888; font-size: 12px;'>
            <p>Bạn nhận được email này vì đã đăng ký tài khoản tại {$site_name}</p>
        </div>
    </div>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($user->user_email, $subject, $body, $headers);
}

// =============================================
// LẤY THÔNG BÁO CỦA USER
// =============================================
function petshop_get_notifications($user_id, $limit = 20, $unread_only = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    $where = "WHERE user_id = %d";
    if ($unread_only) {
        $where .= " AND is_read = 0";
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d",
        $user_id, $limit
    ));
}

// =============================================
// LẤY 1 THÔNG BÁO THEO ID
// =============================================
function petshop_get_notification_by_id($notification_id, $user_id = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    $sql = "SELECT * FROM $table WHERE id = %d";
    $args = array($notification_id);
    
    // Nếu có user_id, kiểm tra quyền truy cập
    if ($user_id > 0) {
        $sql .= " AND user_id = %d";
        $args[] = $user_id;
    }
    
    return $wpdb->get_row($wpdb->prepare($sql, $args));
}

// =============================================
// ĐẾM THÔNG BÁO CHƯA ĐỌC
// =============================================
function petshop_count_unread_notifications($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0",
        $user_id
    ));
}

// =============================================
// ĐÁNH DẤU ĐÃ ĐỌC
// =============================================
function petshop_mark_notification_read($notification_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    return $wpdb->update(
        $table,
        array('is_read' => 1),
        array('id' => $notification_id, 'user_id' => $user_id)
    );
}

function petshop_mark_all_notifications_read($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    return $wpdb->update(
        $table,
        array('is_read' => 1),
        array('user_id' => $user_id, 'is_read' => 0)
    );
}

// =============================================
// HOOKS - TỰ ĐỘNG TẠO THÔNG BÁO
// =============================================

// Thông báo Admin khi có đơn hàng mới
if (!function_exists('petshop_notify_admin_new_order')) {
function petshop_notify_admin_new_order($order_id) {
    $settings = petshop_get_notification_settings();
    if (!$settings['admin_new_order']) return;
    
    $order_code = get_post_meta($order_id, 'order_code', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    
    // Lấy tất cả admin
    $admins = get_users(array('role__in' => array('administrator', 'petshop_manager')));
    
    foreach ($admins as $admin) {
        petshop_create_notification(
            $admin->ID,
            'new_order',
            'Đơn hàng mới #' . $order_code,
            "Khách hàng {$customer_name} vừa đặt đơn hàng trị giá " . number_format($order_total) . "đ",
            array(
                'link' => admin_url('edit.php?post_type=petshop_order'),
                'icon' => 'bi-bag-check',
                'color' => '#28a745'
            )
        );
    }
}
}

// Thông báo Admin khi có khách mới đăng ký
function petshop_notify_admin_new_customer($user_id) {
    $settings = petshop_get_notification_settings();
    if (!$settings['admin_new_customer']) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    // Chỉ thông báo nếu là customer
    if (!in_array('petshop_customer', $user->roles) && !in_array('subscriber', $user->roles)) return;
    
    $admins = get_users(array('role__in' => array('administrator', 'petshop_manager')));
    
    foreach ($admins as $admin) {
        petshop_create_notification(
            $admin->ID,
            'new_customer',
            'Khách hàng mới đăng ký',
            "Khách hàng {$user->display_name} ({$user->user_email}) vừa đăng ký tài khoản",
            array(
                'link' => admin_url('admin.php?page=petshop-crm-customers'),
                'icon' => 'bi-person-plus',
                'color' => '#17a2b8'
            )
        );
    }
}
add_action('user_register', 'petshop_notify_admin_new_customer');

// Thông báo khách khi đơn hàng thay đổi trạng thái
function petshop_notify_customer_order_status($order_id, $new_status, $old_status = '') {
    $settings = petshop_get_notification_settings();
    
    $customer_user_id = get_post_meta($order_id, 'customer_user_id', true);
    if (!$customer_user_id) return;
    
    $order_code = get_post_meta($order_id, 'order_code', true);
    
    $status_messages = array(
        'pending' => array(
            'enabled' => true,
            'title' => '🎉 Đặt hàng thành công!',
            'message' => "Đơn hàng #{$order_code} đã được tiếp nhận. Chúng tôi sẽ xử lý sớm nhất!",
            'icon' => 'bi-bag-check-fill',
            'color' => '#EC802B'
        ),
        'confirmed' => array(
            'enabled' => $settings['customer_order_confirmed'],
            'title' => 'Đơn hàng đã được xác nhận',
            'message' => "Đơn hàng #{$order_code} của bạn đã được xác nhận và đang chuẩn bị.",
            'icon' => 'bi-check-circle',
            'color' => '#17a2b8'
        ),
        'processing' => array(
            'enabled' => $settings['customer_order_processing'],
            'title' => 'Đơn hàng đang được xử lý',
            'message' => "Đơn hàng #{$order_code} của bạn đang được đóng gói.",
            'icon' => 'bi-box-seam',
            'color' => '#6f42c1'
        ),
        'shipping' => array(
            'enabled' => $settings['customer_order_shipping'],
            'title' => 'Đơn hàng đang được giao',
            'message' => "Đơn hàng #{$order_code} đang trên đường giao đến bạn.",
            'icon' => 'bi-truck',
            'color' => '#EC802B'
        ),
        'completed' => array(
            'enabled' => $settings['customer_order_completed'],
            'title' => 'Đơn hàng hoàn thành',
            'message' => "Đơn hàng #{$order_code} đã giao thành công. Cảm ơn bạn đã mua hàng!",
            'icon' => 'bi-bag-check-fill',
            'color' => '#28a745'
        ),
        'cancelled' => array(
            'enabled' => $settings['customer_order_cancelled'],
            'title' => 'Đơn hàng đã bị hủy',
            'message' => "Đơn hàng #{$order_code} đã bị hủy. Liên hệ chúng tôi nếu cần hỗ trợ.",
            'icon' => 'bi-x-circle',
            'color' => '#dc3545'
        ),
    );
    
    if (isset($status_messages[$new_status]) && $status_messages[$new_status]['enabled']) {
        $msg = $status_messages[$new_status];
        petshop_create_notification(
            $customer_user_id,
            'order_status_' . $new_status,
            $msg['title'],
            $msg['message'],
            array(
                'link' => home_url('/xem-don-hang/?id=' . $order_id),
                'icon' => $msg['icon'],
                'color' => $msg['color']
            )
        );
    }
}

// Thông báo khách khi lên hạng
function petshop_notify_customer_tier_upgrade($user_id, $old_tier, $new_tier) {
    $settings = petshop_get_notification_settings();
    if (!$settings['customer_tier_upgrade']) return;
    
    $tier_names = array(
        'bronze' => 'Đồng',
        'silver' => 'Bạc',
        'gold' => 'Vàng'
    );
    
    $new_tier_name = $tier_names[$new_tier] ?? $new_tier;
    
    petshop_create_notification(
        $user_id,
        'tier_upgrade',
        'Chúc mừng! Bạn đã lên hạng ' . $new_tier_name,
        "Bạn đã đạt hạng thành viên {$new_tier_name} với nhiều ưu đãi hấp dẫn!",
        array(
            'link' => home_url('/tai-khoan/?tab=membership'),
            'icon' => 'bi-trophy-fill',
            'color' => '#FFD700'
        )
    );
}

// Thông báo khách khi nhận điểm
function petshop_notify_customer_points_earned($user_id, $points, $order_id) {
    $settings = petshop_get_notification_settings();
    if (!$settings['customer_points_earned']) return;
    
    $order_code = get_post_meta($order_id, 'order_code', true);
    
    petshop_create_notification(
        $user_id,
        'points_earned',
        'Bạn nhận được ' . number_format($points) . ' điểm',
        "Bạn đã nhận được " . number_format($points) . " điểm từ đơn hàng #{$order_code}",
        array(
            'link' => home_url('/tai-khoan/#membership'),
            'icon' => 'bi-coin',
            'color' => '#EC802B',
            'send_email' => false // Không gửi email cho thông báo điểm
        )
    );
}

// =============================================
// AJAX HANDLERS
// =============================================
function petshop_ajax_get_notifications() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    $user_id = get_current_user_id();
    $notifications = petshop_get_notifications($user_id, 20);
    $unread_count = petshop_count_unread_notifications($user_id);
    
    wp_send_json_success(array(
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ));
}
add_action('wp_ajax_petshop_get_notifications', 'petshop_ajax_get_notifications');

function petshop_ajax_mark_notification_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if ($notification_id) {
        petshop_mark_notification_read($notification_id, $user_id);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_petshop_mark_notification_read', 'petshop_ajax_mark_notification_read');

function petshop_ajax_mark_all_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    petshop_mark_all_notifications_read(get_current_user_id());
    wp_send_json_success();
}
add_action('wp_ajax_petshop_mark_all_notifications_read', 'petshop_ajax_mark_all_read');

// =============================================
// HIỂN THỊ BELL ICON TRONG HEADER (FRONTEND)
// =============================================
function petshop_notification_bell_html() {
    if (!is_user_logged_in()) return '';
    
    $user_id = get_current_user_id();
    $unread_count = petshop_count_unread_notifications($user_id);
    $notifications = petshop_get_notifications($user_id, 10);
    
    ob_start();
    ?>
    <div class="notification-bell-wrapper" style="position: relative;">
        <button type="button" class="notification-bell-btn" onclick="toggleNotificationDropdown()" style="background: none; border: none; cursor: pointer; position: relative; padding: 8px;">
            <i class="bi bi-bell" style="font-size: 1.4rem; color: #5D4E37;"></i>
            <?php if ($unread_count > 0): ?>
            <span class="notification-badge" style="position: absolute; top: 2px; right: 2px; background: #dc3545; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center;">
                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
            </span>
            <?php endif; ?>
        </button>
        
        <div id="notificationDropdown" class="notification-dropdown" style="display: none; position: absolute; top: 100%; right: 0; width: 360px; background: #fff; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden;">
            <div style="padding: 15px 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-size: 1rem;">Thông báo</h4>
                <?php if ($unread_count > 0): ?>
                <button type="button" onclick="markAllNotificationsRead()" style="background: none; border: none; color: #EC802B; font-size: 0.85rem; cursor: pointer;">
                    Đánh dấu tất cả đã đọc
                </button>
                <?php endif; ?>
            </div>
            
            <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($notifications)): ?>
                <div style="padding: 40px 20px; text-align: center; color: #888;">
                    <i class="bi bi-bell-slash" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p style="margin: 0;">Chưa có thông báo nào</p>
                </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <a href="<?php echo esc_url($notif->link ?: '#'); ?>" 
                       class="notification-item <?php echo $notif->is_read ? '' : 'unread'; ?>"
                       data-id="<?php echo $notif->id; ?>"
                       onclick="markNotificationRead(<?php echo $notif->id; ?>)"
                       style="display: flex; gap: 12px; padding: 15px 20px; text-decoration: none; border-bottom: 1px solid #f5f5f5; <?php echo $notif->is_read ? '' : 'background: #FDF8F3;'; ?>">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: <?php echo esc_attr($notif->color); ?>20; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="bi <?php echo esc_attr($notif->icon); ?>" style="color: <?php echo esc_attr($notif->color); ?>; font-size: 1.1rem;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <p style="margin: 0 0 4px; font-weight: 600; color: #333; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo esc_html($notif->title); ?>
                            </p>
                            <p style="margin: 0; color: #666; font-size: 0.8rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo esc_html($notif->message); ?>
                            </p>
                            <span style="font-size: 0.75rem; color: #999; margin-top: 5px; display: block;">
                                <?php echo human_time_diff(strtotime($notif->created_at), current_time('timestamp')) . ' trước'; ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="padding: 12px; text-align: center; border-top: 1px solid #f0f0f0;">
                <a href="<?php echo home_url('/tai-khoan/#notifications'); ?>" style="color: #EC802B; text-decoration: none; font-size: 0.9rem;">
                    Xem tất cả thông báo <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <script>
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
    
    function markNotificationRead(id) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_mark_notification_read&notification_id=' + id
        });
    }
    
    function markAllNotificationsRead() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_mark_all_notifications_read'
        }).then(() => {
            document.querySelectorAll('.notification-item.unread').forEach(el => {
                el.classList.remove('unread');
                el.style.background = '';
            });
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.remove();
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.notification-bell-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            document.getElementById('notificationDropdown').style.display = 'none';
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// =============================================
// ADMIN: NOTIFICATION BELL
// =============================================
function petshop_admin_notification_bar() {
    if (!is_user_logged_in()) return;
    
    $user_id = get_current_user_id();
    $unread_count = petshop_count_unread_notifications($user_id);
    $notifications = petshop_get_notifications($user_id, 10);
    ?>
    <style>
    #wp-admin-bar-petshop-notifications .ab-icon:before { content: '\f339'; top: 2px; }
    .petshop-admin-notif-dropdown { width: 380px !important; padding: 0 !important; }
    .petshop-admin-notif-dropdown .notif-header { padding: 12px 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
    .petshop-admin-notif-dropdown .notif-list { max-height: 350px; overflow-y: auto; }
    .petshop-admin-notif-dropdown .notif-item { display: flex; gap: 10px; padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-decoration: none; }
    .petshop-admin-notif-dropdown .notif-item:hover { background: #f8f9fa; }
    .petshop-admin-notif-dropdown .notif-item.unread { background: #fff8e6; }
    .petshop-admin-notif-dropdown .notif-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .petshop-admin-notif-dropdown .notif-content { flex: 1; min-width: 0; }
    .petshop-admin-notif-dropdown .notif-title { font-weight: 600; color: #333; margin: 0 0 3px; font-size: 13px; }
    .petshop-admin-notif-dropdown .notif-message { color: #666; font-size: 12px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .petshop-admin-notif-dropdown .notif-time { font-size: 11px; color: #999; margin-top: 4px; }
    </style>
    <?php
}
add_action('admin_head', 'petshop_admin_notification_bar');

function petshop_add_admin_bar_notification($wp_admin_bar) {
    if (!is_user_logged_in()) return;
    
    $user_id = get_current_user_id();
    $unread_count = petshop_count_unread_notifications($user_id);
    
    $title = '<span class="ab-icon"></span>';
    if ($unread_count > 0) {
        $title .= '<span class="ab-label" style="background:#dc3545;color:#fff;padding:2px 6px;border-radius:10px;font-size:10px;position:relative;top:-2px;">' . ($unread_count > 99 ? '99+' : $unread_count) . '</span>';
    }
    
    $wp_admin_bar->add_node(array(
        'id' => 'petshop-notifications',
        'title' => $title,
        'href' => admin_url('admin.php?page=petshop-notifications'),
        'meta' => array('class' => 'menupop')
    ));
}
add_action('admin_bar_menu', 'petshop_add_admin_bar_notification', 90);

// =============================================
// ADMIN: TRANG CÀI ĐẶT THÔNG BÁO
// Menu đã được gộp vào communication-menu.php
// =============================================
/* Commented out - moved to communication-menu.php
function petshop_register_notification_menu() {
    add_submenu_page(
        'petshop-crm',
        'Thông báo',
        'Thông báo',
        'manage_options',
        'petshop-notifications',
        'petshop_notifications_admin_page'
    );
    
    add_submenu_page(
        'petshop-crm',
        'Cài đặt thông báo',
        'Cài đặt TB',
        'manage_options',
        'petshop-notification-settings',
        'petshop_notification_settings_page'
    );
}
add_action('admin_menu', 'petshop_register_notification_menu', 30);
*/

function petshop_notifications_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notifications';
    
    // Lấy thông báo của admin hiện tại
    $user_id = get_current_user_id();
    $notifications = petshop_get_notifications($user_id, 50);
    
    // Đánh dấu tất cả đã đọc khi xem trang này
    petshop_mark_all_notifications_read($user_id);
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .notif-wrap { max-width: 900px; margin: 20px auto; padding: 0 20px; }
    .notif-header { margin-bottom: 25px; }
    .notif-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .notif-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
    .notif-item { display: flex; gap: 15px; padding: 20px; border-bottom: 1px solid #f0f0f0; }
    .notif-item:last-child { border-bottom: none; }
    .notif-item:hover { background: #fafafa; }
    .notif-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.3rem; }
    .notif-content { flex: 1; }
    .notif-title { font-weight: 600; color: #333; margin: 0 0 5px; font-size: 15px; }
    .notif-message { color: #666; font-size: 14px; margin: 0; line-height: 1.5; }
    .notif-meta { margin-top: 8px; font-size: 12px; color: #999; }
    .notif-action { flex-shrink: 0; }
    .notif-action a { display: inline-block; padding: 8px 16px; background: #EC802B; color: #fff; border-radius: 8px; text-decoration: none; font-size: 13px; }
    .notif-action a:hover { background: #d6701f; }
    
    .empty-state { text-align: center; padding: 60px 20px; color: #888; }
    .empty-state i { font-size: 64px; opacity: 0.3; margin-bottom: 20px; }
    </style>
    
    <div class="notif-wrap">
        <div class="notif-header">
            <h1><i class="bi bi-bell"></i> Thông báo</h1>
        </div>
        
        <div class="notif-card">
            <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="bi bi-bell-slash"></i>
                <p>Chưa có thông báo nào</p>
            </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notif-item">
                    <div class="notif-icon" style="background: <?php echo esc_attr($notif->color); ?>20; color: <?php echo esc_attr($notif->color); ?>;">
                        <i class="bi <?php echo esc_attr($notif->icon); ?>"></i>
                    </div>
                    <div class="notif-content">
                        <h4 class="notif-title"><?php echo esc_html($notif->title); ?></h4>
                        <p class="notif-message"><?php echo esc_html($notif->message); ?></p>
                        <div class="notif-meta">
                            <i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif->created_at)); ?>
                            <?php if ($notif->email_sent): ?>
                            <span style="margin-left: 10px;"><i class="bi bi-envelope-check"></i> Email đã gửi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($notif->link): ?>
                    <div class="notif-action">
                        <a href="<?php echo esc_url($notif->link); ?>">Xem <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function petshop_notification_settings_page() {
    $settings = petshop_get_notification_settings();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notification_settings'])) {
        check_admin_referer('petshop_notification_settings');
        
        $new_settings = array(
            'admin_new_order' => isset($_POST['admin_new_order']),
            'admin_new_review' => isset($_POST['admin_new_review']),
            'admin_new_customer' => isset($_POST['admin_new_customer']),
            'admin_low_stock' => isset($_POST['admin_low_stock']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            
            'customer_order_confirmed' => isset($_POST['customer_order_confirmed']),
            'customer_order_processing' => isset($_POST['customer_order_processing']),
            'customer_order_shipping' => isset($_POST['customer_order_shipping']),
            'customer_order_completed' => isset($_POST['customer_order_completed']),
            'customer_order_cancelled' => isset($_POST['customer_order_cancelled']),
            'customer_tier_upgrade' => isset($_POST['customer_tier_upgrade']),
            'customer_new_voucher' => isset($_POST['customer_new_voucher']),
            'customer_points_earned' => isset($_POST['customer_points_earned']),
            'customer_birthday' => isset($_POST['customer_birthday']),
            
            'send_email' => isset($_POST['send_email']),
        );
        
        update_option('petshop_notification_settings', $new_settings);
        $settings = $new_settings;
        
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt thông báo!</p></div>';
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .notif-settings-wrap { max-width: 900px; margin: 20px auto; padding: 0 20px; }
    .notif-settings-header { margin-bottom: 25px; }
    .notif-settings-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .settings-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .settings-card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .settings-card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .settings-card-body { padding: 20px; }
    
    .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f5f5f5; }
    .setting-row:last-child { border-bottom: none; }
    .setting-info { }
    .setting-info h4 { margin: 0 0 3px; font-size: 14px; color: #333; }
    .setting-info p { margin: 0; font-size: 12px; color: #888; }
    
    .switch { position: relative; width: 46px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 24px; transition: 0.3s; }
    .switch .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
    .switch input:checked + .slider { background: #28a745; }
    .switch input:checked + .slider:before { transform: translateX(22px); }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; }
    .form-group input { padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; width: 300px; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    </style>
    
    <div class="notif-settings-wrap">
        <div class="notif-settings-header">
            <h1><i class="bi bi-gear"></i> Cài đặt thông báo</h1>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('petshop_notification_settings'); ?>
            
            <!-- Email Settings -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h3><i class="bi bi-envelope" style="color: #EC802B;"></i> Cài đặt Email</h3>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Gửi email thông báo</h4>
                            <p>Tự động gửi email kèm theo mỗi thông báo</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="send_email" <?php checked($settings['send_email']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Email nhận thông báo Admin</label>
                        <input type="email" name="admin_email" value="<?php echo esc_attr($settings['admin_email']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Admin Notifications -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h3><i class="bi bi-shield-check" style="color: #EC802B;"></i> Thông báo Admin</h3>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn hàng mới</h4>
                            <p>Thông báo khi có đơn hàng mới</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="admin_new_order" <?php checked($settings['admin_new_order']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đánh giá mới</h4>
                            <p>Thông báo khi có đánh giá sản phẩm mới</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="admin_new_review" <?php checked($settings['admin_new_review']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Khách hàng mới</h4>
                            <p>Thông báo khi có khách hàng đăng ký</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="admin_new_customer" <?php checked($settings['admin_new_customer']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Sản phẩm sắp hết</h4>
                            <p>Thông báo khi sản phẩm sắp hết tồn kho</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="admin_low_stock" <?php checked($settings['admin_low_stock']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Customer Notifications -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <h3><i class="bi bi-person-check" style="color: #EC802B;"></i> Thông báo Khách hàng</h3>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn hàng đã xác nhận</h4>
                            <p>Thông báo khi đơn hàng được xác nhận</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_order_confirmed" <?php checked($settings['customer_order_confirmed']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn đang xử lý</h4>
                            <p>Thông báo khi đơn hàng đang được đóng gói</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_order_processing" <?php checked($settings['customer_order_processing']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn đang giao</h4>
                            <p>Thông báo khi đơn hàng được giao</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_order_shipping" <?php checked($settings['customer_order_shipping']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn hoàn thành</h4>
                            <p>Thông báo khi đơn hàng giao thành công</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_order_completed" <?php checked($settings['customer_order_completed']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đơn bị hủy</h4>
                            <p>Thông báo khi đơn hàng bị hủy</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_order_cancelled" <?php checked($settings['customer_order_cancelled']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Lên hạng thành viên</h4>
                            <p>Thông báo khi khách hàng lên hạng mới</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_tier_upgrade" <?php checked($settings['customer_tier_upgrade']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Voucher mới</h4>
                            <p>Thông báo khi có voucher mới áp dụng</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_new_voucher" <?php checked($settings['customer_new_voucher']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Nhận điểm thưởng</h4>
                            <p>Thông báo khi nhận được điểm từ đơn hàng</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_points_earned" <?php checked($settings['customer_points_earned']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Sinh nhật</h4>
                            <p>Gửi lời chúc và ưu đãi sinh nhật</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="customer_birthday" <?php checked($settings['customer_birthday']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_notification_settings" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Lưu cài đặt
            </button>
        </form>
    </div>
    <?php
}