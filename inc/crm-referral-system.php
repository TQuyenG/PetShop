<?php
/**
 * PetShop CRM - Referral System
 * Hệ thống giới thiệu bạn bè
 * 
 * Features:
 * - Tự động tạo mã giới thiệu cho mỗi user
 * - Thưởng điểm khi giới thiệu bạn
 * - Theo dõi cây giới thiệu
 * - Voucher cho người được giới thiệu
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// ...existing code...

// =============================================
// TẠO BẢNG DATABASE REFERRAL
// =============================================
function petshop_create_referral_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Bảng referral records
    $table_referrals = $wpdb->prefix . 'petshop_referrals';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_referrals'") === $table_referrals;
    
    if (!$table_exists) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_referrals (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) UNSIGNED NOT NULL COMMENT 'Người giới thiệu',
            referred_id bigint(20) UNSIGNED NOT NULL COMMENT 'Người được giới thiệu',
            referral_code varchar(20) NOT NULL,
            status enum('pending','active','rewarded','cancelled') DEFAULT 'pending',
            referrer_reward_type varchar(20) DEFAULT 'points',
            referrer_reward_value decimal(15,2) DEFAULT 0,
            referred_reward_type varchar(20) DEFAULT 'discount',
            referred_reward_value decimal(15,2) DEFAULT 0,
            first_order_id bigint(20) UNSIGNED DEFAULT NULL,
            first_order_total decimal(15,2) DEFAULT 0,
            rewarded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY referred_id (referred_id),
            KEY referrer_id (referrer_id),
            KEY referral_code (referral_code),
            KEY status (status)
        ) $charset_collate;");
    }
    
    // Bảng referral rewards history
    $table_rewards = $wpdb->prefix . 'petshop_referral_rewards';
    $table_rewards_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_rewards'") === $table_rewards;
    
    if (!$table_rewards_exists) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_rewards (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            referral_id bigint(20) UNSIGNED NOT NULL,
            reward_type varchar(20) NOT NULL,
            reward_value decimal(15,2) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;");
    }
}
add_action('after_switch_theme', 'petshop_create_referral_tables');
add_action('admin_init', 'petshop_create_referral_tables');

// =============================================
// CẤU HÌNH REFERRAL
// =============================================
function petshop_get_referral_settings() {
    $defaults = array(
        'enabled' => true,
        'referrer_reward_type' => 'points', // points, fixed_amount, percent
        'referrer_reward_value' => 50, // 50 điểm hoặc 50k hoặc 5%
        'referrer_reward_condition' => 'first_order', // signup, first_order
        'referrer_min_order' => 100000, // Đơn hàng tối thiểu để được thưởng
        'referred_reward_type' => 'discount_percent', // discount_percent, discount_fixed, points
        'referred_reward_value' => 10, // Giảm 10%
        'referred_max_discount' => 50000, // Giảm tối đa 50k
        'referred_min_order' => 200000, // Đơn tối thiểu
        'referred_coupon_days' => 30, // Hạn dùng coupon
        'max_referrals_per_user' => 0, // 0 = không giới hạn
        'multi_level' => false, // Có thưởng đa cấp không
        'level_2_percent' => 0, // % thưởng cấp 2
    );
    
    $settings = get_option('petshop_referral_settings', array());
    return wp_parse_args($settings, $defaults);
}

// =============================================
// TẠO MÃ GIỚI THIỆU KHI ĐĂNG KÝ
// =============================================
function petshop_generate_referral_code($user_id) {
    // Lưu vào user_meta thay vì bảng riêng
    $existing = get_user_meta($user_id, 'petshop_referral_code', true);
    
    if ($existing) return $existing;
    
    // Tạo code mới - 8 ký tự
    $code = strtoupper(substr(md5($user_id . time() . rand(1000, 9999)), 0, 8));
    
    // Đảm bảo unique bằng cách kiểm tra trong user_meta
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'petshop_referral_code' AND meta_value = %s",
        $code
    ));
    
    if ($exists) {
        $code = strtoupper(substr(md5($user_id . time() . rand(10000, 99999)), 0, 8));
    }
    
    // Lưu vào user_meta
    update_user_meta($user_id, 'petshop_referral_code', $code);
    
    return $code;
}

// =============================================
// LẤY THÔNG TIN NGƯỜI GIỚI THIỆU TỪ CODE
// =============================================
function petshop_get_referrer_by_code($code) {
    global $wpdb;
    
    // Tìm user có referral_code này trong user_meta
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'petshop_referral_code' AND meta_value = %s",
        $code
    ));
    
    if (!$user_id) return null;
    
    $user = get_userdata($user_id);
    if (!$user) return null;
    
    return (object) array(
        'id' => $user_id,
        'user_id' => $user_id,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'referral_code' => $code
    );
}

// =============================================
// XỬ LÝ ĐĂNG KÝ VỚI MÃ GIỚI THIỆU
// =============================================
function petshop_process_referral_signup($user_id) {
    global $wpdb;
    
    // Kiểm tra có referral code trong session/cookie không
    $referral_code = '';
    if (isset($_COOKIE['petshop_ref'])) {
        $referral_code = sanitize_text_field($_COOKIE['petshop_ref']);
    } elseif (isset($_POST['referral_code'])) {
        $referral_code = sanitize_text_field($_POST['referral_code']);
    }
    
    if (empty($referral_code)) return;
    
    // Lấy thông tin người giới thiệu
    $referrer = petshop_get_referrer_by_code($referral_code);
    if (!$referrer || !$referrer->user_id) return;
    
    // Không tự giới thiệu chính mình
    if ($referrer->user_id == $user_id) return;
    
    // Kiểm tra đã được giới thiệu chưa
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}petshop_referrals WHERE referred_id = %d",
        $user_id
    ));
    if ($exists) return;
    
    $settings = petshop_get_referral_settings();
    
    // Tạo referral record
    $wpdb->insert(
        $wpdb->prefix . 'petshop_referrals',
        array(
            'referrer_id' => $referrer->user_id,
            'referred_id' => $user_id,
            'referral_code' => $referral_code,
            'status' => 'pending',
            'referrer_reward_type' => $settings['referrer_reward_type'],
            'referrer_reward_value' => $settings['referrer_reward_value'],
            'referred_reward_type' => $settings['referred_reward_type'],
            'referred_reward_value' => $settings['referred_reward_value'],
            'created_at' => current_time('mysql'),
        )
    );
    
    // Lưu referrer vào user_meta
    update_user_meta($user_id, 'petshop_referred_by', $referrer->user_id);
    
    // Tạo coupon cho người được giới thiệu nếu có
    if ($settings['referred_reward_type'] === 'discount_percent' || $settings['referred_reward_type'] === 'discount_fixed') {
        petshop_create_referral_coupon($user_id, $settings);
    }
    
    // Xóa cookie
    setcookie('petshop_ref', '', time() - 3600, '/');
    
    // Log activity
    if (function_exists('petshop_log_customer_activity')) {
        petshop_log_customer_activity($user_id, 'referred_signup', "Đăng ký qua mã giới thiệu: $referral_code");
        petshop_log_customer_activity($referrer->user_id, 'referral_signup', "Giới thiệu thành công user #$user_id");
    }
}
add_action('user_register', 'petshop_process_referral_signup', 20);

// =============================================
// TẠO COUPON CHO NGƯỜI ĐƯỢC GIỚI THIỆU
// =============================================
function petshop_create_referral_coupon($user_id, $settings) {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    // Kiểm tra bảng coupon tồn tại
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupons'") === $table_coupons;
    if (!$table_exists) return false;
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $code = 'WELCOME' . strtoupper(substr(md5($user_id . time()), 0, 6));
    
    $discount_type = $settings['referred_reward_type'] === 'discount_percent' ? 'percent' : 'fixed';
    
    $wpdb->insert($table_coupons, array(
        'code' => $code,
        'name' => 'Ưu đãi chào mừng - ' . $user->display_name,
        'description' => 'Mã giảm giá dành cho thành viên mới được giới thiệu',
        'type' => 'order',
        'discount_type' => $discount_type,
        'discount_value' => $settings['referred_reward_value'],
        'min_order_amount' => $settings['referred_min_order'],
        'max_discount_amount' => $settings['referred_max_discount'],
        'usage_limit' => 1,
        'user_limit' => 1,
        'start_datetime' => current_time('mysql'),
        'end_datetime' => date('Y-m-d H:i:s', strtotime('+' . $settings['referred_coupon_days'] . ' days')),
        'is_active' => 1,
        'new_user_only' => 1,
        'created_at' => current_time('mysql'),
    ));
    
    // Lưu coupon code vào user meta
    update_user_meta($user_id, 'petshop_welcome_coupon', $code);
    
    return $code;
}

// =============================================
// XỬ LÝ THƯỞNG KHI ĐƠN HÀNG HOÀN THÀNH
// =============================================
function petshop_process_referral_reward($order_id) {
    global $wpdb;
    
    $order_status = get_post_meta($order_id, 'order_status', true);
    if ($order_status !== 'completed') return;
    
    $user_id = get_post_meta($order_id, 'customer_user_id', true);
    if (!$user_id) return;
    
    // Kiểm tra có referral pending không
    $referral = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}petshop_referrals 
         WHERE referred_id = %d AND status = 'pending'",
        $user_id
    ));
    
    if (!$referral) return;
    
    $settings = petshop_get_referral_settings();
    $order_total = floatval(get_post_meta($order_id, 'order_total', true));
    
    // Kiểm tra đơn hàng đủ điều kiện
    if ($order_total < $settings['referrer_min_order']) return;
    
    // Tính toán phần thưởng cho người giới thiệu
    $reward_value = 0;
    switch ($referral->referrer_reward_type) {
        case 'points':
            $reward_value = floatval($referral->referrer_reward_value);
            if (function_exists('petshop_add_customer_points')) {
                petshop_add_customer_points($referral->referrer_id, $reward_value, 'Thưởng giới thiệu bạn bè - Đơn hàng #' . get_post_meta($order_id, 'order_code', true));
            }
            break;
            
        case 'fixed_amount':
            $reward_value = floatval($referral->referrer_reward_value);
            // Có thể tạo coupon hoặc credit cho người giới thiệu
            petshop_create_reward_coupon($referral->referrer_id, $reward_value);
            break;
            
        case 'percent':
            $reward_value = ($order_total * floatval($referral->referrer_reward_value)) / 100;
            petshop_create_reward_coupon($referral->referrer_id, $reward_value);
            break;
    }
    
    // Cập nhật trạng thái referral
    $wpdb->update(
        $wpdb->prefix . 'petshop_referrals',
        array(
            'status' => 'rewarded',
            'first_order_id' => $order_id,
            'first_order_total' => $order_total,
            'rewarded_at' => current_time('mysql'),
        ),
        array('id' => $referral->id)
    );
    
    // Log reward
    $wpdb->insert(
        $wpdb->prefix . 'petshop_referral_rewards',
        array(
            'user_id' => $referral->referrer_id,
            'referral_id' => $referral->id,
            'reward_type' => $referral->referrer_reward_type,
            'reward_value' => $reward_value,
            'description' => 'Thưởng từ đơn hàng #' . get_post_meta($order_id, 'order_code', true) . ' của người được giới thiệu',
            'created_at' => current_time('mysql'),
        )
    );
    
    // Log activity
    if (function_exists('petshop_log_customer_activity')) {
        petshop_log_customer_activity($referral->referrer_id, 'referral_reward', "Nhận thưởng giới thiệu: $reward_value " . ($referral->referrer_reward_type === 'points' ? 'điểm' : 'đ'));
    }
}
add_action('petshop_order_status_changed', 'petshop_process_referral_reward');

// =============================================
// TẠO COUPON THƯỞNG CHO NGƯỜI GIỚI THIỆU
// =============================================
function petshop_create_reward_coupon($user_id, $amount) {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupons'") === $table_coupons;
    if (!$table_exists) return false;
    
    $code = 'REWARD' . strtoupper(substr(md5($user_id . time()), 0, 6));
    
    $wpdb->insert($table_coupons, array(
        'code' => $code,
        'name' => 'Thưởng giới thiệu bạn bè',
        'description' => 'Mã giảm giá thưởng cho việc giới thiệu bạn bè',
        'type' => 'order',
        'discount_type' => 'fixed',
        'discount_value' => $amount,
        'usage_limit' => 1,
        'user_limit' => 1,
        'start_datetime' => current_time('mysql'),
        'end_datetime' => date('Y-m-d H:i:s', strtotime('+90 days')),
        'is_active' => 1,
        'created_at' => current_time('mysql'),
    ));
    
    // Lưu vào user meta
    $existing = get_user_meta($user_id, 'petshop_reward_coupons', true);
    $existing = is_array($existing) ? $existing : array();
    $existing[] = $code;
    update_user_meta($user_id, 'petshop_reward_coupons', $existing);
    
    return $code;
}

// =============================================
// LẤY THỐNG KÊ GIỚI THIỆU CỦA USER
// =============================================
function petshop_get_user_referral_stats($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_referrals';
    
    $stats = array(
        'total_referrals' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE referrer_id = %d",
            $user_id
        )) ?: 0,
        'active_referrals' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE referrer_id = %d AND status = 'active'",
            $user_id
        )) ?: 0,
        'rewarded_referrals' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE referrer_id = %d AND status = 'rewarded'",
            $user_id
        )) ?: 0,
        'pending_referrals' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE referrer_id = %d AND status = 'pending'",
            $user_id
        )) ?: 0,
        'total_earnings' => $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(reward_value) FROM {$wpdb->prefix}petshop_referral_rewards WHERE user_id = %d",
            $user_id
        )) ?: 0,
    );
    
    return $stats;
}

// =============================================
// LẤY DANH SÁCH NGƯỜI ĐƯỢC GIỚI THIỆU
// =============================================
function petshop_get_user_referrals($user_id, $limit = 20) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, u.display_name, u.user_email
         FROM {$wpdb->prefix}petshop_referrals r
         LEFT JOIN {$wpdb->users} u ON r.referred_id = u.ID
         WHERE r.referrer_id = %d
         ORDER BY r.created_at DESC
         LIMIT %d",
        $user_id,
        $limit
    ));
}

// =============================================
// LƯU REFERRAL CODE TỪ URL VÀO COOKIE
// =============================================
function petshop_save_referral_code() {
    if (isset($_GET['ref']) && !empty($_GET['ref'])) {
        $ref_code = sanitize_text_field($_GET['ref']);
        
        // Kiểm tra code hợp lệ
        $referrer = petshop_get_referrer_by_code($ref_code);
        if ($referrer) {
            setcookie('petshop_ref', $ref_code, time() + (30 * 24 * 60 * 60), '/'); // 30 ngày
        }
    }
}
add_action('init', 'petshop_save_referral_code');

// Menu được đăng ký trong crm-admin-menu.php

// =============================================
// TRANG QUẢN LÝ REFERRAL
// =============================================
function petshop_referral_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_referrals';
    
    // Đảm bảo bảng tồn tại
    petshop_create_referral_tables();
    
    // Lấy danh sách referrals
    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    
    $where = "WHERE 1=1";
    if ($filter_status) {
        $where .= $wpdb->prepare(" AND r.status = %s", $filter_status);
    }
    
    $referrals = $wpdb->get_results(
        "SELECT r.*, 
                u1.display_name as referrer_name, u1.user_email as referrer_email,
                u2.display_name as referred_name, u2.user_email as referred_email
         FROM $table r
         LEFT JOIN {$wpdb->users} u1 ON r.referrer_id = u1.ID
         LEFT JOIN {$wpdb->users} u2 ON r.referred_id = u2.ID
         $where
         ORDER BY r.created_at DESC
         LIMIT 100"
    );
    
    // Thống kê
    $stats = array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
        'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
        'rewarded' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'rewarded'"),
        'total_rewards' => $wpdb->get_var("SELECT SUM(reward_value) FROM {$wpdb->prefix}petshop_referral_rewards"),
    );
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    .referral-wrap { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
    .referral-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .referral-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .referral-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; text-align: center; }
    .stat-card .stat-icon { font-size: 32px; margin-bottom: 10px; }
    .stat-card h3 { margin: 0 0 5px; font-size: 28px; }
    .stat-card p { margin: 0; color: #666; font-size: 13px; }
    
    .filters { display: flex; gap: 10px; margin-bottom: 20px; background: #fff; padding: 15px 20px; border-radius: 10px; border: 1px solid #e0e0e0; }
    .filters select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
    
    .referral-table { width: 100%; background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; border-collapse: collapse; overflow: hidden; }
    .referral-table th, .referral-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    .referral-table th { background: #f8f9fa; font-weight: 600; font-size: 13px; text-transform: uppercase; }
    .referral-table tr:hover { background: #fafafa; }
    
    .user-info { display: flex; align-items: center; gap: 10px; }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #EC802B, #66BCB4); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 14px; }
    .user-name { font-weight: 500; }
    .user-email { font-size: 12px; color: #888; }
    
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-active { background: #cce5ff; color: #004085; }
    .status-rewarded { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    .reward-value { font-weight: 600; color: #EC802B; }
    
    .no-data { text-align: center; padding: 60px; background: #fff; border-radius: 10px; }
    .no-data i { font-size: 64px; color: #ddd; display: block; margin-bottom: 15px; }
    </style>
    
    <div class="referral-wrap">
        <div class="referral-header">
            <h1><i class="bi bi-share"></i> Quản lý Giới thiệu bạn bè</h1>
            <a href="<?php echo admin_url('admin.php?page=petshop-crm-referral-settings'); ?>" class="button button-primary">
                <i class="bi bi-gear"></i> Cài đặt
            </a>
        </div>
        
        <!-- Stats -->
        <div class="referral-stats">
            <div class="stat-card">
                <div class="stat-icon" style="color:#5D4E37;"><i class="bi bi-people"></i></div>
                <h3><?php echo number_format($stats['total'] ?: 0); ?></h3>
                <p>Tổng lượt giới thiệu</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#856404;"><i class="bi bi-hourglass-split"></i></div>
                <h3><?php echo number_format($stats['pending'] ?: 0); ?></h3>
                <p>Đang chờ</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#155724;"><i class="bi bi-check-circle"></i></div>
                <h3><?php echo number_format($stats['rewarded'] ?: 0); ?></h3>
                <p>Đã thưởng</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#EC802B;"><i class="bi bi-gift"></i></div>
                <h3><?php echo number_format($stats['total_rewards'] ?: 0); ?></h3>
                <p>Tổng thưởng (điểm/đ)</p>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="get" class="filters">
            <input type="hidden" name="page" value="petshop-crm-referral">
            <select name="status" onchange="this.form.submit()">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" <?php selected($filter_status, 'pending'); ?>>Đang chờ</option>
                <option value="active" <?php selected($filter_status, 'active'); ?>>Hoạt động</option>
                <option value="rewarded" <?php selected($filter_status, 'rewarded'); ?>>Đã thưởng</option>
                <option value="cancelled" <?php selected($filter_status, 'cancelled'); ?>>Đã hủy</option>
            </select>
        </form>
        
        <!-- Table -->
        <?php if (empty($referrals)): ?>
        <div class="no-data">
            <i class="bi bi-inbox"></i>
            <h3>Chưa có lượt giới thiệu nào</h3>
            <p>Khi khách hàng đăng ký qua link giới thiệu sẽ hiển thị ở đây</p>
        </div>
        <?php else: ?>
        <table class="referral-table">
            <thead>
                <tr>
                    <th>Người giới thiệu</th>
                    <th>Người được GT</th>
                    <th>Mã</th>
                    <th>Phần thưởng</th>
                    <th>Đơn hàng đầu</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referrals as $ref): 
                    $status_labels = array(
                        'pending' => 'Đang chờ',
                        'active' => 'Hoạt động',
                        'rewarded' => 'Đã thưởng',
                        'cancelled' => 'Đã hủy',
                    );
                ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar"><?php echo mb_strtoupper(mb_substr($ref->referrer_name ?: 'U', 0, 1)); ?></div>
                            <div>
                                <div class="user-name"><?php echo esc_html($ref->referrer_name ?: 'N/A'); ?></div>
                                <div class="user-email"><?php echo esc_html($ref->referrer_email); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar" style="background:#66BCB4;"><?php echo mb_strtoupper(mb_substr($ref->referred_name ?: 'U', 0, 1)); ?></div>
                            <div>
                                <div class="user-name"><?php echo esc_html($ref->referred_name ?: 'N/A'); ?></div>
                                <div class="user-email"><?php echo esc_html($ref->referred_email); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code><?php echo esc_html($ref->referral_code); ?></code></td>
                    <td>
                        <span class="reward-value">
                            <?php 
                            echo number_format($ref->referrer_reward_value);
                            echo $ref->referrer_reward_type === 'points' ? ' điểm' : 'đ';
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($ref->first_order_id): ?>
                            <?php echo number_format($ref->first_order_total); ?>đ
                        <?php else: ?>
                            <span style="color:#999;">Chưa có</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $ref->status; ?>">
                            <?php echo $status_labels[$ref->status] ?? $ref->status; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($ref->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// TRANG CÀI ĐẶT REFERRAL
// =============================================
function petshop_crm_referral_settings_page() {
    // Xử lý lưu
    if (isset($_POST['petshop_referral_nonce']) && wp_verify_nonce($_POST['petshop_referral_nonce'], 'petshop_save_referral_settings')) {
        $settings = array(
            'enabled' => isset($_POST['enabled']) ? true : false,
            'referrer_reward_type' => sanitize_text_field($_POST['referrer_reward_type']),
            'referrer_reward_value' => floatval($_POST['referrer_reward_value']),
            'referrer_min_order' => floatval($_POST['referrer_min_order']),
            'referred_reward_type' => sanitize_text_field($_POST['referred_reward_type']),
            'referred_reward_value' => floatval($_POST['referred_reward_value']),
            'referred_max_discount' => floatval($_POST['referred_max_discount']),
            'referred_min_order' => floatval($_POST['referred_min_order']),
            'referred_coupon_days' => intval($_POST['referred_coupon_days']),
            'max_referrals_per_user' => intval($_POST['max_referrals_per_user']),
        );
        
        update_option('petshop_referral_settings', $settings);
        echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt!</p></div>';
    }
    
    $settings = petshop_get_referral_settings();
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    .settings-wrap { max-width: 800px; margin: 20px auto; padding: 0 20px; }
    .settings-header { margin-bottom: 25px; }
    .settings-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0 0 10px; }
    
    .settings-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 20px; }
    .settings-card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; font-weight: 600; font-size: 16px; }
    .settings-card-body { padding: 20px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
    .form-group .description { font-size: 13px; color: #666; margin-top: 5px; }
    .form-group input, .form-group select { padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%; max-width: 400px; }
    .form-group input[type="checkbox"] { width: auto; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .toggle-switch { display: flex; align-items: center; gap: 10px; }
    .toggle-switch input[type="checkbox"] { width: 50px; height: 26px; appearance: none; background: #ddd; border-radius: 13px; position: relative; cursor: pointer; }
    .toggle-switch input[type="checkbox"]::before { content: ''; position: absolute; width: 22px; height: 22px; background: #fff; border-radius: 50%; top: 2px; left: 2px; transition: 0.2s; }
    .toggle-switch input[type="checkbox"]:checked { background: #EC802B; }
    .toggle-switch input[type="checkbox"]:checked::before { left: 26px; }
    </style>
    
    <div class="settings-wrap">
        <div class="settings-header">
            <h1><i class="bi bi-gear"></i> Cài đặt Giới thiệu bạn bè</h1>
            <p style="color:#666;">Cấu hình chương trình giới thiệu bạn bè và phần thưởng</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('petshop_save_referral_settings', 'petshop_referral_nonce'); ?>
            
            <!-- Bật/Tắt -->
            <div class="settings-card">
                <div class="settings-card-header">Trạng thái chương trình</div>
                <div class="settings-card-body">
                    <div class="form-group">
                        <div class="toggle-switch">
                            <input type="checkbox" name="enabled" id="enabled" <?php checked($settings['enabled'], true); ?>>
                            <label for="enabled" style="margin-bottom:0;">Kích hoạt chương trình giới thiệu bạn bè</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Phần thưởng cho người giới thiệu -->
            <div class="settings-card">
                <div class="settings-card-header">Phần thưởng cho người giới thiệu</div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Loại phần thưởng</label>
                            <select name="referrer_reward_type">
                                <option value="points" <?php selected($settings['referrer_reward_type'], 'points'); ?>>Điểm tích lũy</option>
                                <option value="fixed_amount" <?php selected($settings['referrer_reward_type'], 'fixed_amount'); ?>>Tiền cố định (VNĐ)</option>
                                <option value="percent" <?php selected($settings['referrer_reward_type'], 'percent'); ?>>% giá trị đơn hàng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Giá trị thưởng</label>
                            <input type="number" name="referrer_reward_value" value="<?php echo esc_attr($settings['referrer_reward_value']); ?>" step="0.01">
                            <p class="description">Điểm hoặc số tiền (VNĐ) hoặc %</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Đơn hàng tối thiểu để được thưởng</label>
                        <input type="number" name="referrer_min_order" value="<?php echo esc_attr($settings['referrer_min_order']); ?>">
                        <p class="description">Người được giới thiệu phải có đơn hàng tối thiểu bao nhiêu thì người giới thiệu mới được thưởng</p>
                    </div>
                </div>
            </div>
            
            <!-- Ưu đãi cho người được giới thiệu -->
            <div class="settings-card">
                <div class="settings-card-header">Ưu đãi cho người được giới thiệu</div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Loại ưu đãi</label>
                            <select name="referred_reward_type">
                                <option value="discount_percent" <?php selected($settings['referred_reward_type'], 'discount_percent'); ?>>Giảm giá %</option>
                                <option value="discount_fixed" <?php selected($settings['referred_reward_type'], 'discount_fixed'); ?>>Giảm giá cố định (VNĐ)</option>
                                <option value="points" <?php selected($settings['referred_reward_type'], 'points'); ?>>Tặng điểm</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Giá trị ưu đãi</label>
                            <input type="number" name="referred_reward_value" value="<?php echo esc_attr($settings['referred_reward_value']); ?>" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giảm tối đa (VNĐ)</label>
                            <input type="number" name="referred_max_discount" value="<?php echo esc_attr($settings['referred_max_discount']); ?>">
                            <p class="description">Áp dụng khi giảm theo %</p>
                        </div>
                        <div class="form-group">
                            <label>Đơn hàng tối thiểu (VNĐ)</label>
                            <input type="number" name="referred_min_order" value="<?php echo esc_attr($settings['referred_min_order']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Hạn sử dụng coupon (ngày)</label>
                        <input type="number" name="referred_coupon_days" value="<?php echo esc_attr($settings['referred_coupon_days']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Giới hạn -->
            <div class="settings-card">
                <div class="settings-card-header">Giới hạn</div>
                <div class="settings-card-body">
                    <div class="form-group">
                        <label>Số lượt giới thiệu tối đa mỗi người</label>
                        <input type="number" name="max_referrals_per_user" value="<?php echo esc_attr($settings['max_referrals_per_user']); ?>">
                        <p class="description">Nhập 0 để không giới hạn</p>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="button button-primary button-hero">
                <i class="bi bi-check-lg"></i> Lưu cài đặt
            </button>
        </form>
    </div>
    <?php
}

// =============================================
// SHORTCODE: HIỂN THỊ LINK GIỚI THIỆU CHO USER
// =============================================
function petshop_referral_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để xem mã giới thiệu của bạn.</p>';
    }
    
    $user_id = get_current_user_id();
    $settings = petshop_get_referral_settings();
    
    if (!$settings['enabled']) {
        return '<p>Chương trình giới thiệu bạn bè hiện tạm dừng.</p>';
    }
    
    // Lấy hoặc tạo referral code
    $referral_code = petshop_generate_referral_code($user_id);
    $referral_link = home_url('/?ref=' . $referral_code);
    $stats = petshop_get_user_referral_stats($user_id);
    $referrals = petshop_get_user_referrals($user_id, 10);
    
    ob_start();
    ?>
    <div class="referral-section">
        <div class="referral-box">
            <h3><i class="bi bi-gift"></i> Giới thiệu bạn bè - Nhận thưởng</h3>
            <p>Chia sẻ link giới thiệu và nhận <strong><?php echo number_format($settings['referrer_reward_value']); ?> <?php echo $settings['referrer_reward_type'] === 'points' ? 'điểm' : 'đ'; ?></strong> khi bạn bè đặt hàng thành công!</p>
            
            <div class="referral-code-box">
                <label>Mã giới thiệu của bạn:</label>
                <div class="code-display">
                    <span class="code"><?php echo esc_html($referral_code); ?></span>
                    <button type="button" class="copy-btn" onclick="copyText('<?php echo esc_js($referral_code); ?>')">
                        <i class="bi bi-clipboard"></i> Sao chép
                    </button>
                </div>
            </div>
            
            <div class="referral-link-box">
                <label>Link giới thiệu:</label>
                <div class="link-display">
                    <input type="text" value="<?php echo esc_attr($referral_link); ?>" readonly id="referral-link-input">
                    <button type="button" class="copy-btn" onclick="copyLink()">
                        <i class="bi bi-link-45deg"></i> Sao chép link
                    </button>
                </div>
            </div>
            
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>" target="_blank" class="share-btn fb">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
                <a href="https://zalo.me/share/?url=<?php echo urlencode($referral_link); ?>" target="_blank" class="share-btn zalo">
                    Zalo
                </a>
                <a href="mailto:?subject=Mua sắm tại PetShop&body=Đăng ký ngay tại: <?php echo urlencode($referral_link); ?>" class="share-btn email">
                    <i class="bi bi-envelope"></i> Email
                </a>
            </div>
        </div>
        
        <div class="referral-stats-box">
            <h4>Thống kê giới thiệu</h4>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="value"><?php echo $stats['total_referrals']; ?></span>
                    <span class="label">Đã giới thiệu</span>
                </div>
                <div class="stat-item">
                    <span class="value"><?php echo $stats['rewarded_referrals']; ?></span>
                    <span class="label">Đã nhận thưởng</span>
                </div>
                <div class="stat-item">
                    <span class="value"><?php echo $stats['pending_referrals']; ?></span>
                    <span class="label">Đang chờ</span>
                </div>
                <div class="stat-item">
                    <span class="value"><?php echo number_format($stats['total_earnings']); ?></span>
                    <span class="label">Tổng nhận</span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($referrals)): ?>
        <div class="referral-list-box">
            <h4>Danh sách người được giới thiệu</h4>
            <table class="referral-list">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $ref): 
                        $status_labels = array('pending' => 'Chờ mua hàng', 'rewarded' => 'Đã thưởng', 'active' => 'Đang hoạt động');
                        $status_colors = array('pending' => '#856404', 'rewarded' => '#155724', 'active' => '#004085');
                    ?>
                    <tr>
                        <td><?php echo esc_html($ref->display_name ?: 'Ẩn danh'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($ref->created_at)); ?></td>
                        <td><span style="color:<?php echo $status_colors[$ref->status] ?? '#666'; ?>;"><?php echo $status_labels[$ref->status] ?? $ref->status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .referral-section { max-width: 700px; margin: 0 auto; }
    .referral-box { background: linear-gradient(135deg, #FEF3E2, #FFF); padding: 30px; border-radius: 15px; margin-bottom: 20px; border: 2px solid #EC802B; }
    .referral-box h3 { margin: 0 0 15px; color: #EC802B; }
    .referral-code-box, .referral-link-box { margin: 20px 0; }
    .referral-code-box label, .referral-link-box label { display: block; margin-bottom: 8px; font-weight: 600; }
    .code-display, .link-display { display: flex; gap: 10px; }
    .code-display .code { background: #fff; padding: 12px 20px; border-radius: 8px; font-family: monospace; font-size: 18px; font-weight: 700; border: 1px solid #ddd; flex: 1; display: flex; align-items: center; }
    .link-display input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .copy-btn { padding: 12px 20px; background: #EC802B; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px; }
    .copy-btn:hover { background: #D6701F; }
    .share-buttons { display: flex; gap: 10px; margin-top: 20px; }
    .share-btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; color: #fff; font-weight: 600; display: flex; align-items: center; gap: 5px; }
    .share-btn.fb { background: #1877F2; }
    .share-btn.zalo { background: #0068FF; }
    .share-btn.email { background: #66BCB4; }
    .referral-stats-box, .referral-list-box { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
    .referral-stats-box h4, .referral-list-box h4 { margin: 0 0 15px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; }
    .stat-item .value { display: block; font-size: 24px; font-weight: 700; color: #EC802B; }
    .stat-item .label { display: block; font-size: 12px; color: #666; margin-top: 5px; }
    .referral-list { width: 100%; border-collapse: collapse; }
    .referral-list th, .referral-list td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    .referral-list th { background: #f8f9fa; font-size: 13px; }
    </style>
    
    <script>
    function copyText(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Đã sao chép mã: ' + text);
        });
    }
    function copyLink() {
        var input = document.getElementById('referral-link-input');
        input.select();
        navigator.clipboard.writeText(input.value).then(function() {
            alert('Đã sao chép link giới thiệu!');
        });
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('petshop_referral', 'petshop_referral_shortcode');
