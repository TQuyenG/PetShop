<?php
/**
 * PetShop Sub-accounts System
 * Hệ thống tài khoản phụ (cho doanh nghiệp)
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// CẤU TRÚC DỮ LIỆU
// =============================================
// User meta:
// - petshop_is_main_account: true/false (có phải tài khoản chính không)
// - petshop_main_account_id: ID tài khoản chính (nếu là sub-account)
// - petshop_sub_account_permissions: array quyền của sub-account
// - petshop_max_sub_accounts: số lượng sub-account tối đa được phép tạo (mặc định theo tier)

// =============================================
// CÀI ĐẶT SUB-ACCOUNTS
// =============================================
function petshop_get_subaccount_settings() {
    return get_option('petshop_subaccount_settings', array(
        'enable_subaccounts' => true,
        'bronze_max_subaccounts' => 1,
        'silver_max_subaccounts' => 3,
        'gold_max_subaccounts' => 5,
        'share_points' => true,  // Sub-accounts chia sẻ điểm với main
        'share_tier' => true,    // Sub-accounts hưởng tier của main
    ));
}

// =============================================
// QUYỀN CỦA SUB-ACCOUNT
// =============================================
function petshop_get_subaccount_permission_options() {
    return array(
        'view_orders' => array(
            'label' => 'Xem đơn hàng',
            'description' => 'Xem danh sách và chi tiết đơn hàng',
            'icon' => 'bi-bag'
        ),
        'create_orders' => array(
            'label' => 'Đặt hàng',
            'description' => 'Tạo đơn hàng mới',
            'icon' => 'bi-bag-plus'
        ),
        'view_points' => array(
            'label' => 'Xem điểm thưởng',
            'description' => 'Xem số điểm và lịch sử điểm',
            'icon' => 'bi-coin'
        ),
        'use_points' => array(
            'label' => 'Sử dụng điểm',
            'description' => 'Đổi điểm lấy voucher',
            'icon' => 'bi-gift'
        ),
        'view_vouchers' => array(
            'label' => 'Xem voucher',
            'description' => 'Xem danh sách voucher có thể dùng',
            'icon' => 'bi-ticket-perforated'
        ),
        'update_profile' => array(
            'label' => 'Cập nhật hồ sơ',
            'description' => 'Sửa thông tin cá nhân của mình',
            'icon' => 'bi-person-gear'
        ),
    );
}

// =============================================
// KIỂM TRA QUYỀN
// =============================================
function petshop_is_main_account($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    // Nếu có sub-accounts thì là main account
    $sub_accounts = petshop_get_sub_accounts($user_id);
    if (!empty($sub_accounts)) return true;
    
    // Nếu không có main_account_id thì cũng là main account
    $main_id = get_user_meta($user_id, 'petshop_main_account_id', true);
    return empty($main_id);
}

function petshop_is_sub_account($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $main_id = get_user_meta($user_id, 'petshop_main_account_id', true);
    return !empty($main_id);
}

function petshop_get_main_account_id($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return 0;
    
    $main_id = get_user_meta($user_id, 'petshop_main_account_id', true);
    return $main_id ? intval($main_id) : $user_id;
}

function petshop_subaccount_can($permission, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    // Main account có tất cả quyền
    if (petshop_is_main_account($user_id)) return true;
    
    $permissions = get_user_meta($user_id, 'petshop_sub_account_permissions', true);
    if (!is_array($permissions)) return false;
    
    return in_array($permission, $permissions);
}

// =============================================
// LẤY DANH SÁCH SUB-ACCOUNTS
// =============================================
function petshop_get_sub_accounts($main_user_id) {
    $args = array(
        'meta_key' => 'petshop_main_account_id',
        'meta_value' => $main_user_id,
        'fields' => 'all'
    );
    
    return get_users($args);
}

// =============================================
// SỐ LƯỢNG SUB-ACCOUNT TỐI ĐA
// =============================================
function petshop_get_max_sub_accounts($user_id) {
    $settings = petshop_get_subaccount_settings();
    
    // Lấy tier của user
    $tier = petshop_get_customer_tier($user_id);
    
    switch ($tier) {
        case 'gold':
            return $settings['gold_max_subaccounts'];
        case 'silver':
            return $settings['silver_max_subaccounts'];
        default:
            return $settings['bronze_max_subaccounts'];
    }
}

// Helper function nếu chưa có
if (!function_exists('petshop_get_customer_tier')) {
    function petshop_get_customer_tier($user_id) {
        $total_spent = petshop_get_customer_total_spent($user_id);
        
        if ($total_spent >= 10000000) return 'gold';
        if ($total_spent >= 3000000) return 'silver';
        return 'bronze';
    }
}

if (!function_exists('petshop_get_customer_total_spent')) {
    function petshop_get_customer_total_spent($user_id) {
        $orders = get_posts(array(
            'post_type' => 'petshop_order',
            'posts_per_page' => -1,
            'meta_query' => array(
                array('key' => 'customer_user_id', 'value' => $user_id),
                array('key' => 'order_status', 'value' => 'completed')
            )
        ));
        
        $total = 0;
        foreach ($orders as $order) {
            $total += floatval(get_post_meta($order->ID, 'order_total', true));
        }
        
        return $total;
    }
}

// =============================================
// TẠO SUB-ACCOUNT
// =============================================
function petshop_create_sub_account($main_user_id, $data) {
    $settings = petshop_get_subaccount_settings();
    
    if (!$settings['enable_subaccounts']) {
        return new WP_Error('disabled', 'Tính năng tài khoản phụ đang tắt');
    }
    
    // Kiểm tra giới hạn
    $current_subs = petshop_get_sub_accounts($main_user_id);
    $max_subs = petshop_get_max_sub_accounts($main_user_id);
    
    if (count($current_subs) >= $max_subs) {
        return new WP_Error('limit_reached', 'Đã đạt giới hạn tài khoản phụ (' . $max_subs . ')');
    }
    
    // Kiểm tra email
    if (email_exists($data['email'])) {
        return new WP_Error('email_exists', 'Email này đã được sử dụng');
    }
    
    // Tạo username từ email
    $username = sanitize_user(explode('@', $data['email'])[0]);
    $username = petshop_generate_unique_username($username);
    
    // Tạo password ngẫu nhiên
    $password = wp_generate_password(12, true);
    
    // Tạo user
    $user_id = wp_create_user($username, $password, $data['email']);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Cập nhật thông tin
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => sanitize_text_field($data['name']),
        'first_name' => sanitize_text_field($data['name']),
    ));
    
    // Set role
    $user = new WP_User($user_id);
    $user->set_role('petshop_customer');
    
    // Set meta
    update_user_meta($user_id, 'petshop_main_account_id', $main_user_id);
    update_user_meta($user_id, 'petshop_sub_account_permissions', $data['permissions'] ?? array());
    update_user_meta($user_id, 'billing_phone', sanitize_text_field($data['phone'] ?? ''));
    
    // Gửi email thông báo
    petshop_send_subaccount_welcome_email($user_id, $main_user_id, $password);
    
    return $user_id;
}

function petshop_generate_unique_username($base) {
    $username = $base;
    $counter = 1;
    
    while (username_exists($username)) {
        $username = $base . $counter;
        $counter++;
    }
    
    return $username;
}

// =============================================
// GỬI EMAIL CHO SUB-ACCOUNT MỚI
// =============================================
function petshop_send_subaccount_welcome_email($sub_user_id, $main_user_id, $password) {
    $sub_user = get_userdata($sub_user_id);
    $main_user = get_userdata($main_user_id);
    
    if (!$sub_user || !$main_user) return false;
    
    $site_name = get_bloginfo('name');
    $login_url = home_url('/tai-khoan/');
    
    $subject = "[{$site_name}] Tài khoản phụ của bạn đã được tạo";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #EC802B, #F5994D); padding: 30px; text-align: center;'>
            <h1 style='color: #fff; margin: 0;'>{$site_name}</h1>
        </div>
        <div style='padding: 30px; background: #fff;'>
            <h2 style='color: #333;'>Chào {$sub_user->display_name}!</h2>
            <p style='color: #666; line-height: 1.6;'>
                Tài khoản {$main_user->display_name} đã tạo cho bạn một tài khoản phụ trên {$site_name}.
            </p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Email:</strong> {$sub_user->user_email}</p>
                <p style='margin: 5px 0;'><strong>Mật khẩu:</strong> {$password}</p>
            </div>
            
            <p style='color: #666;'>Vui lòng đổi mật khẩu sau khi đăng nhập lần đầu.</p>
            
            <p style='margin-top: 25px;'>
                <a href='{$login_url}' style='display: inline-block; background: #EC802B; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 8px;'>Đăng nhập ngay</a>
            </p>
        </div>
    </div>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($sub_user->user_email, $subject, $body, $headers);
}

// =============================================
// CẬP NHẬT SUB-ACCOUNT
// =============================================
function petshop_update_sub_account($sub_user_id, $main_user_id, $data) {
    // Kiểm tra quyền sở hữu
    $actual_main = get_user_meta($sub_user_id, 'petshop_main_account_id', true);
    if (intval($actual_main) !== intval($main_user_id)) {
        return new WP_Error('unauthorized', 'Không có quyền cập nhật tài khoản này');
    }
    
    // Cập nhật permissions
    if (isset($data['permissions'])) {
        update_user_meta($sub_user_id, 'petshop_sub_account_permissions', $data['permissions']);
    }
    
    // Cập nhật tên
    if (!empty($data['name'])) {
        wp_update_user(array(
            'ID' => $sub_user_id,
            'display_name' => sanitize_text_field($data['name']),
            'first_name' => sanitize_text_field($data['name']),
        ));
    }
    
    // Cập nhật phone
    if (isset($data['phone'])) {
        update_user_meta($sub_user_id, 'billing_phone', sanitize_text_field($data['phone']));
    }
    
    return true;
}

// =============================================
// XÓA SUB-ACCOUNT
// =============================================
function petshop_delete_sub_account($sub_user_id, $main_user_id) {
    // Kiểm tra quyền sở hữu
    $actual_main = get_user_meta($sub_user_id, 'petshop_main_account_id', true);
    if (intval($actual_main) !== intval($main_user_id)) {
        return new WP_Error('unauthorized', 'Không có quyền xóa tài khoản này');
    }
    
    // Xóa user
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    return wp_delete_user($sub_user_id, $main_user_id); // Reassign posts to main account
}

// =============================================
// SWITCH ACCOUNT
// =============================================
function petshop_switch_to_account($target_user_id) {
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Chưa đăng nhập');
    }
    
    $current_user_id = get_current_user_id();
    $current_main_id = petshop_get_main_account_id($current_user_id);
    
    // Target phải là main hoặc sub của cùng 1 nhóm
    $target_main_id = petshop_get_main_account_id($target_user_id);
    
    if ($current_main_id !== $target_main_id) {
        return new WP_Error('unauthorized', 'Không thể chuyển sang tài khoản này');
    }
    
    // Lưu original user để có thể switch back
    if (!get_user_meta($current_user_id, 'petshop_original_user_id', true)) {
        update_user_meta($target_user_id, 'petshop_original_user_id', $current_user_id);
    }
    
    // Switch user
    wp_clear_auth_cookie();
    wp_set_current_user($target_user_id);
    wp_set_auth_cookie($target_user_id, true);
    
    return true;
}

// =============================================
// LẤY ĐIỂM VÀ TIER (CHIA SẺ)
// =============================================
function petshop_get_shared_points($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    
    $settings = petshop_get_subaccount_settings();
    
    if ($settings['share_points']) {
        $main_id = petshop_get_main_account_id($user_id);
        return intval(get_user_meta($main_id, 'petshop_points', true));
    }
    
    return intval(get_user_meta($user_id, 'petshop_points', true));
}

function petshop_get_shared_tier($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    
    $settings = petshop_get_subaccount_settings();
    
    if ($settings['share_tier']) {
        $main_id = petshop_get_main_account_id($user_id);
        return petshop_get_customer_tier($main_id);
    }
    
    return petshop_get_customer_tier($user_id);
}

// =============================================
// AJAX HANDLERS
// =============================================

// Lấy danh sách sub-accounts
function petshop_ajax_get_sub_accounts() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    $user_id = get_current_user_id();
    
    // Phải là main account mới xem được
    if (!petshop_is_main_account($user_id)) {
        wp_send_json_error('Chỉ tài khoản chính mới có thể quản lý tài khoản phụ');
    }
    
    $sub_accounts = petshop_get_sub_accounts($user_id);
    $max_accounts = petshop_get_max_sub_accounts($user_id);
    
    $accounts_data = array();
    foreach ($sub_accounts as $sub) {
        $permissions = get_user_meta($sub->ID, 'petshop_sub_account_permissions', true) ?: array();
        $accounts_data[] = array(
            'id' => $sub->ID,
            'name' => $sub->display_name,
            'email' => $sub->user_email,
            'phone' => get_user_meta($sub->ID, 'billing_phone', true),
            'permissions' => $permissions,
            'registered' => $sub->user_registered,
            'last_login' => get_user_meta($sub->ID, 'last_login', true) ?: 'Chưa đăng nhập'
        );
    }
    
    wp_send_json_success(array(
        'accounts' => $accounts_data,
        'current_count' => count($sub_accounts),
        'max_count' => $max_accounts,
        'permission_options' => petshop_get_subaccount_permission_options()
    ));
}
add_action('wp_ajax_petshop_get_sub_accounts', 'petshop_ajax_get_sub_accounts');

// Tạo sub-account
function petshop_ajax_create_sub_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    check_ajax_referer('petshop_subaccount_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    
    if (!petshop_is_main_account($user_id)) {
        wp_send_json_error('Chỉ tài khoản chính mới có thể tạo tài khoản phụ');
    }
    
    $data = array(
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'permissions' => isset($_POST['permissions']) ? array_map('sanitize_text_field', $_POST['permissions']) : array()
    );
    
    if (empty($data['name']) || empty($data['email'])) {
        wp_send_json_error('Vui lòng nhập đầy đủ tên và email');
    }
    
    $result = petshop_create_sub_account($user_id, $data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(array(
        'message' => 'Đã tạo tài khoản phụ thành công! Email với thông tin đăng nhập đã được gửi.',
        'user_id' => $result
    ));
}
add_action('wp_ajax_petshop_create_sub_account', 'petshop_ajax_create_sub_account');

// Cập nhật sub-account
function petshop_ajax_update_sub_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    check_ajax_referer('petshop_subaccount_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $sub_user_id = intval($_POST['sub_user_id'] ?? 0);
    
    if (!$sub_user_id) {
        wp_send_json_error('ID tài khoản không hợp lệ');
    }
    
    $data = array(
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'permissions' => isset($_POST['permissions']) ? array_map('sanitize_text_field', $_POST['permissions']) : array()
    );
    
    $result = petshop_update_sub_account($sub_user_id, $user_id, $data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Đã cập nhật tài khoản phụ');
}
add_action('wp_ajax_petshop_update_sub_account', 'petshop_ajax_update_sub_account');

// Xóa sub-account
function petshop_ajax_delete_sub_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    check_ajax_referer('petshop_subaccount_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $sub_user_id = intval($_POST['sub_user_id'] ?? 0);
    
    if (!$sub_user_id) {
        wp_send_json_error('ID tài khoản không hợp lệ');
    }
    
    $result = petshop_delete_sub_account($sub_user_id, $user_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success('Đã xóa tài khoản phụ');
}
add_action('wp_ajax_petshop_delete_sub_account', 'petshop_ajax_delete_sub_account');

// Switch account
function petshop_ajax_switch_account() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    
    if (!$target_user_id) {
        wp_send_json_error('ID tài khoản không hợp lệ');
    }
    
    $result = petshop_switch_to_account($target_user_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(array(
        'message' => 'Đã chuyển tài khoản',
        'redirect' => home_url('/tai-khoan/')
    ));
}
add_action('wp_ajax_petshop_switch_account', 'petshop_ajax_switch_account');

// =============================================
// FRONTEND: SHORTCODE HIỂN THỊ QUẢN LÝ SUB-ACCOUNTS
// =============================================
function petshop_subaccounts_manager_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng đăng nhập để sử dụng tính năng này.</p>';
    }
    
    $settings = petshop_get_subaccount_settings();
    if (!$settings['enable_subaccounts']) {
        return '<p>Tính năng tài khoản phụ hiện không khả dụng.</p>';
    }
    
    $user_id = get_current_user_id();
    $is_main = petshop_is_main_account($user_id);
    $main_id = petshop_get_main_account_id($user_id);
    
    ob_start();
    ?>
    <div class="subaccounts-manager">
        <?php if ($is_main): ?>
            <!-- Main Account View -->
            <div class="sub-header">
                <div class="sub-header-info">
                    <h3><i class="bi bi-people"></i> Tài khoản phụ</h3>
                    <p class="sub-limit">
                        <span id="subAccountCount">0</span> / <span id="subAccountMax">0</span> tài khoản
                    </p>
                </div>
                <button type="button" class="btn btn-add" onclick="openAddSubAccountModal()">
                    <i class="bi bi-plus-lg"></i> Thêm tài khoản
                </button>
            </div>
            
            <div id="subAccountsList" class="sub-accounts-list">
                <div class="loading-state">
                    <i class="bi bi-arrow-repeat spin"></i> Đang tải...
                </div>
            </div>
            
            <!-- Modal: Add/Edit Sub-account -->
            <div id="subAccountModal" class="sub-modal" style="display: none;">
                <div class="sub-modal-content">
                    <div class="sub-modal-header">
                        <h3 id="modalTitle">Thêm tài khoản phụ</h3>
                        <button type="button" class="close-btn" onclick="closeSubAccountModal()">&times;</button>
                    </div>
                    <div class="sub-modal-body">
                        <form id="subAccountForm">
                            <input type="hidden" name="sub_user_id" id="subUserId" value="">
                            <?php wp_nonce_field('petshop_subaccount_nonce', 'nonce'); ?>
                            
                            <div class="form-group">
                                <label>Họ tên <span class="required">*</span></label>
                                <input type="text" name="name" id="subName" required>
                            </div>
                            
                            <div class="form-group" id="emailGroup">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" id="subEmail" required>
                                <small>Thông tin đăng nhập sẽ được gửi về email này</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="phone" id="subPhone">
                            </div>
                            
                            <div class="form-group">
                                <label>Quyền hạn</label>
                                <div id="permissionsList" class="permissions-grid"></div>
                            </div>
                        </form>
                    </div>
                    <div class="sub-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeSubAccountModal()">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveSubAccount()">
                            <i class="bi bi-check-lg"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Sub-account View -->
            <div class="sub-account-info">
                <div class="info-card">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <h4>Đây là tài khoản phụ</h4>
                        <p>Tài khoản này được quản lý bởi: <strong><?php echo esc_html(get_userdata($main_id)->display_name); ?></strong></p>
                    </div>
                </div>
                
                <?php 
                $permissions = get_user_meta($user_id, 'petshop_sub_account_permissions', true) ?: array();
                $all_permissions = petshop_get_subaccount_permission_options();
                ?>
                <div class="my-permissions">
                    <h4>Quyền hạn của bạn:</h4>
                    <div class="permission-tags">
                        <?php foreach ($all_permissions as $key => $perm): ?>
                            <?php if (in_array($key, $permissions)): ?>
                            <span class="permission-tag">
                                <i class="bi <?php echo $perm['icon']; ?>"></i> <?php echo $perm['label']; ?>
                            </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($permissions)): ?>
                        <span class="no-permission">Chỉ xem</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Account Switcher (cho cả main và sub) -->
        <?php 
        $all_accounts = array();
        
        if ($is_main) {
            // Main thì hiển thị mình và các sub
            $main_user = get_userdata($user_id);
            $all_accounts[] = array(
                'id' => $user_id,
                'name' => $main_user->display_name . ' (Chính)',
                'is_current' => true
            );
            
            foreach (petshop_get_sub_accounts($user_id) as $sub) {
                $all_accounts[] = array(
                    'id' => $sub->ID,
                    'name' => $sub->display_name,
                    'is_current' => false
                );
            }
        } else {
            // Sub thì hiển thị main và các sub cùng nhóm
            $main_user = get_userdata($main_id);
            $all_accounts[] = array(
                'id' => $main_id,
                'name' => $main_user->display_name . ' (Chính)',
                'is_current' => false
            );
            
            foreach (petshop_get_sub_accounts($main_id) as $sub) {
                $all_accounts[] = array(
                    'id' => $sub->ID,
                    'name' => $sub->display_name,
                    'is_current' => ($sub->ID === $user_id)
                );
            }
        }
        
        if (count($all_accounts) > 1):
        ?>
        <div class="account-switcher">
            <h4><i class="bi bi-arrow-left-right"></i> Chuyển tài khoản</h4>
            <div class="account-list">
                <?php foreach ($all_accounts as $acc): ?>
                <button type="button" 
                        class="account-btn <?php echo $acc['is_current'] ? 'current' : ''; ?>"
                        <?php echo $acc['is_current'] ? 'disabled' : ''; ?>
                        onclick="switchToAccount(<?php echo $acc['id']; ?>)">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo esc_html($acc['name']); ?></span>
                    <?php if ($acc['is_current']): ?>
                    <i class="bi bi-check-circle-fill current-badge"></i>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .subaccounts-manager { padding: 20px 0; }
    
    .sub-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .sub-header h3 { margin: 0 0 5px; font-size: 1.3rem; display: flex; align-items: center; gap: 8px; }
    .sub-limit { margin: 0; color: #888; font-size: 0.9rem; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 10px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-add { background: #EC802B; color: #fff; }
    .btn-add:hover { background: #d6701f; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    .btn-secondary { background: #e9ecef; color: #333; }
    .btn-secondary:hover { background: #dee2e6; }
    .btn-danger { background: #dc3545; color: #fff; }
    .btn-danger:hover { background: #c82333; }
    .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
    
    .sub-accounts-list { }
    .sub-account-card { display: flex; align-items: center; gap: 15px; padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 12px; }
    .sub-account-avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #EC802B, #F5994D); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; flex-shrink: 0; }
    .sub-account-info { flex: 1; min-width: 0; }
    .sub-account-name { font-weight: 600; color: #333; margin: 0 0 4px; font-size: 1rem; }
    .sub-account-meta { color: #888; font-size: 0.85rem; margin: 0; display: flex; flex-wrap: wrap; gap: 12px; }
    .sub-account-meta i { margin-right: 4px; }
    .sub-account-actions { display: flex; gap: 8px; flex-shrink: 0; }
    
    .loading-state { text-align: center; padding: 40px; color: #888; }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    .empty-state { text-align: center; padding: 50px 20px; color: #888; }
    .empty-state i { font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 15px; }
    
    /* Modal */
    .sub-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .sub-modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
    .sub-modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .sub-modal-header h3 { margin: 0; }
    .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #888; }
    .sub-modal-body { padding: 20px; overflow-y: auto; flex: 1; }
    .sub-modal-footer { padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
    
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
    .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 0.95rem; }
    .form-group input:focus { border-color: #EC802B; outline: none; }
    .form-group small { display: block; margin-top: 5px; color: #888; font-size: 0.8rem; }
    .required { color: #dc3545; }
    
    .permissions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
    .permission-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
    .permission-item:hover { background: #FDF8F3; }
    .permission-item input { display: none; }
    .permission-item.checked { background: #EC802B15; border: 1px solid #EC802B; }
    .permission-icon { width: 36px; height: 36px; background: #EC802B20; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #EC802B; }
    .permission-text { flex: 1; }
    .permission-text strong { display: block; font-size: 0.85rem; }
    .permission-text small { color: #888; font-size: 0.75rem; }
    .permission-check { width: 20px; height: 20px; border: 2px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .permission-item.checked .permission-check { background: #EC802B; border-color: #EC802B; color: #fff; }
    
    /* Sub-account info card */
    .info-card { display: flex; gap: 15px; padding: 20px; background: #FDF8F3; border: 1px solid #EC802B30; border-radius: 12px; margin-bottom: 25px; }
    .info-card > i { font-size: 1.5rem; color: #EC802B; }
    .info-card h4 { margin: 0 0 5px; color: #333; }
    .info-card p { margin: 0; color: #666; }
    
    .my-permissions { margin-bottom: 25px; }
    .my-permissions h4 { margin: 0 0 12px; font-size: 0.95rem; }
    .permission-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .permission-tag { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #EC802B15; color: #EC802B; border-radius: 20px; font-size: 0.85rem; }
    .no-permission { color: #888; font-style: italic; }
    
    /* Account Switcher */
    .account-switcher { margin-top: 30px; padding-top: 25px; border-top: 1px solid #eee; }
    .account-switcher h4 { margin: 0 0 15px; font-size: 1rem; display: flex; align-items: center; gap: 8px; color: #333; }
    .account-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .account-btn { display: flex; align-items: center; gap: 10px; padding: 12px 18px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-size: 0.9rem; }
    .account-btn:hover:not(:disabled) { background: #FDF8F3; border-color: #EC802B; }
    .account-btn.current { background: #EC802B15; border-color: #EC802B; }
    .account-btn:disabled { cursor: default; }
    .current-badge { color: #28a745; margin-left: auto; }
    </style>
    
    <script>
    var permissionOptions = <?php echo json_encode(petshop_get_subaccount_permission_options()); ?>;
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Load sub-accounts
    function loadSubAccounts() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_get_sub_accounts'
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('subAccountCount').textContent = res.data.current_count;
                document.getElementById('subAccountMax').textContent = res.data.max_count;
                
                renderSubAccounts(res.data.accounts);
                renderPermissions(res.data.permission_options);
            }
        });
    }
    
    function renderSubAccounts(accounts) {
        const container = document.getElementById('subAccountsList');
        
        if (accounts.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>Chưa có tài khoản phụ nào</p>
                    <button class="btn btn-add" onclick="openAddSubAccountModal()">
                        <i class="bi bi-plus-lg"></i> Thêm tài khoản đầu tiên
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = accounts.map(acc => `
            <div class="sub-account-card">
                <div class="sub-account-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div class="sub-account-info">
                    <h4 class="sub-account-name">${acc.name}</h4>
                    <p class="sub-account-meta">
                        <span><i class="bi bi-envelope"></i>${acc.email}</span>
                        ${acc.phone ? `<span><i class="bi bi-phone"></i>${acc.phone}</span>` : ''}
                    </p>
                </div>
                <div class="sub-account-actions">
                    <button class="btn btn-secondary btn-sm" onclick="editSubAccount(${JSON.stringify(acc).replace(/"/g, '&quot;')})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteSubAccount(${acc.id}, '${acc.name}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    function renderPermissions(permissions) {
        const container = document.getElementById('permissionsList');
        container.innerHTML = Object.entries(permissions).map(([key, perm]) => `
            <label class="permission-item" onclick="togglePermission(this)">
                <input type="checkbox" name="permissions[]" value="${key}">
                <div class="permission-icon"><i class="bi ${perm.icon}"></i></div>
                <div class="permission-text">
                    <strong>${perm.label}</strong>
                    <small>${perm.description}</small>
                </div>
                <div class="permission-check"><i class="bi bi-check"></i></div>
            </label>
        `).join('');
    }
    
    function togglePermission(el) {
        const checkbox = el.querySelector('input');
        checkbox.checked = !checkbox.checked;
        el.classList.toggle('checked', checkbox.checked);
    }
    
    function openAddSubAccountModal() {
        document.getElementById('modalTitle').textContent = 'Thêm tài khoản phụ';
        document.getElementById('subUserId').value = '';
        document.getElementById('subName').value = '';
        document.getElementById('subEmail').value = '';
        document.getElementById('subPhone').value = '';
        document.getElementById('emailGroup').style.display = 'block';
        
        // Reset permissions
        document.querySelectorAll('.permission-item').forEach(el => {
            el.classList.remove('checked');
            el.querySelector('input').checked = false;
        });
        
        document.getElementById('subAccountModal').style.display = 'flex';
    }
    
    function editSubAccount(acc) {
        document.getElementById('modalTitle').textContent = 'Sửa tài khoản phụ';
        document.getElementById('subUserId').value = acc.id;
        document.getElementById('subName').value = acc.name;
        document.getElementById('subEmail').value = acc.email;
        document.getElementById('subPhone').value = acc.phone || '';
        document.getElementById('emailGroup').style.display = 'none';
        
        // Set permissions
        document.querySelectorAll('.permission-item').forEach(el => {
            const val = el.querySelector('input').value;
            const isChecked = acc.permissions.includes(val);
            el.classList.toggle('checked', isChecked);
            el.querySelector('input').checked = isChecked;
        });
        
        document.getElementById('subAccountModal').style.display = 'flex';
    }
    
    function closeSubAccountModal() {
        document.getElementById('subAccountModal').style.display = 'none';
    }
    
    function saveSubAccount() {
        const form = document.getElementById('subAccountForm');
        const formData = new FormData(form);
        
        const subUserId = document.getElementById('subUserId').value;
        formData.append('action', subUserId ? 'petshop_update_sub_account' : 'petshop_create_sub_account');
        if (subUserId) {
            formData.append('sub_user_id', subUserId);
        }
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(res.data.message || res.data);
                closeSubAccountModal();
                loadSubAccounts();
            } else {
                alert('Lỗi: ' + res.data);
            }
        });
    }
    
    function deleteSubAccount(id, name) {
        if (!confirm(`Bạn có chắc muốn xóa tài khoản "${name}"?`)) return;
        
        const formData = new FormData();
        formData.append('action', 'petshop_delete_sub_account');
        formData.append('sub_user_id', id);
        formData.append('nonce', document.querySelector('[name="nonce"]').value);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                loadSubAccounts();
            } else {
                alert('Lỗi: ' + res.data);
            }
        });
    }
    
    function switchToAccount(targetId) {
        if (!confirm('Chuyển sang tài khoản khác?')) return;
        
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_switch_account&target_user_id=' + targetId
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.href = res.data.redirect;
            } else {
                alert('Lỗi: ' + res.data);
            }
        });
    }
    
    // Init
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('subAccountsList')) {
            loadSubAccounts();
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('petshop_subaccounts', 'petshop_subaccounts_manager_shortcode');

// =============================================
// ADMIN: CÀI ĐẶT SUB-ACCOUNTS
// =============================================
function petshop_register_subaccount_settings_menu() {
    add_submenu_page(
        'petshop-crm',
        'Cài đặt Tài khoản phụ',
        'Tài khoản phụ',
        'manage_options',
        'petshop-subaccount-settings',
        'petshop_subaccount_settings_page'
    );
}
add_action('admin_menu', 'petshop_register_subaccount_settings_menu', 35);

function petshop_subaccount_settings_page() {
    $settings = petshop_get_subaccount_settings();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subaccount_settings'])) {
        check_admin_referer('petshop_subaccount_settings');
        
        $new_settings = array(
            'enable_subaccounts' => isset($_POST['enable_subaccounts']),
            'bronze_max_subaccounts' => intval($_POST['bronze_max_subaccounts']),
            'silver_max_subaccounts' => intval($_POST['silver_max_subaccounts']),
            'gold_max_subaccounts' => intval($_POST['gold_max_subaccounts']),
            'share_points' => isset($_POST['share_points']),
            'share_tier' => isset($_POST['share_tier']),
        );
        
        update_option('petshop_subaccount_settings', $new_settings);
        $settings = $new_settings;
        
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt!</p></div>';
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .sub-settings-wrap { max-width: 800px; margin: 20px auto; padding: 0 20px; }
    .sub-settings-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0 0 30px; }
    
    .settings-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .settings-card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .settings-card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .settings-card-body { padding: 20px; }
    
    .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f5f5f5; }
    .setting-row:last-child { border-bottom: none; }
    .setting-info h4 { margin: 0 0 3px; font-size: 14px; }
    .setting-info p { margin: 0; font-size: 12px; color: #888; }
    
    .switch { position: relative; width: 46px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 24px; transition: 0.3s; }
    .switch .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
    .switch input:checked + .slider { background: #28a745; }
    .switch input:checked + .slider:before { transform: translateX(22px); }
    
    .tier-limits { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
    .tier-limit { background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; }
    .tier-limit label { display: block; margin-bottom: 8px; font-weight: 600; }
    .tier-limit input { width: 60px; text-align: center; padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
    .tier-bronze { border-left: 3px solid #CD7F32; }
    .tier-silver { border-left: 3px solid #C0C0C0; }
    .tier-gold { border-left: 3px solid #FFD700; }
    
    .btn-submit { display: inline-flex; align-items: center; gap: 6px; padding: 12px 25px; background: #EC802B; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-submit:hover { background: #d6701f; }
    </style>
    
    <div class="sub-settings-wrap">
        <div class="sub-settings-header">
            <h1><i class="bi bi-people-fill"></i> Cài đặt Tài khoản phụ</h1>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('petshop_subaccount_settings'); ?>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <h3><i class="bi bi-gear" style="color: #EC802B;"></i> Cài đặt chung</h3>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Bật tính năng Tài khoản phụ</h4>
                            <p>Cho phép khách hàng tạo và quản lý tài khoản phụ</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_subaccounts" <?php checked($settings['enable_subaccounts']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Chia sẻ điểm thưởng</h4>
                            <p>Tài khoản phụ dùng chung điểm với tài khoản chính</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="share_points" <?php checked($settings['share_points']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Chia sẻ hạng thành viên</h4>
                            <p>Tài khoản phụ hưởng cùng hạng với tài khoản chính</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="share_tier" <?php checked($settings['share_tier']); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-card-header">
                    <h3><i class="bi bi-sliders" style="color: #EC802B;"></i> Giới hạn theo hạng thành viên</h3>
                </div>
                <div class="settings-card-body">
                    <p style="margin: 0 0 15px; color: #666;">Số lượng tài khoản phụ tối đa mà khách hàng có thể tạo:</p>
                    
                    <div class="tier-limits">
                        <div class="tier-limit tier-bronze">
                            <label><i class="bi bi-award"></i> Hạng Đồng</label>
                            <input type="number" name="bronze_max_subaccounts" value="<?php echo $settings['bronze_max_subaccounts']; ?>" min="0" max="10">
                        </div>
                        <div class="tier-limit tier-silver">
                            <label><i class="bi bi-award-fill"></i> Hạng Bạc</label>
                            <input type="number" name="silver_max_subaccounts" value="<?php echo $settings['silver_max_subaccounts']; ?>" min="0" max="10">
                        </div>
                        <div class="tier-limit tier-gold">
                            <label><i class="bi bi-trophy-fill"></i> Hạng Vàng</label>
                            <input type="number" name="gold_max_subaccounts" value="<?php echo $settings['gold_max_subaccounts']; ?>" min="0" max="10">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_subaccount_settings" class="btn-submit">
                <i class="bi bi-check-lg"></i> Lưu cài đặt
            </button>
        </form>
    </div>
    <?php
}

// =============================================
// GHI NHẬN LAST LOGIN
// =============================================
function petshop_record_last_login($user_login, $user) {
    update_user_meta($user->ID, 'last_login', current_time('mysql'));
}
add_action('wp_login', 'petshop_record_last_login', 10, 2);
