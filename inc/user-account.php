<?php
/**
 * PetShop User Account System
 * Hệ thống quản lý tài khoản người dùng
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// AJAX: LƯU THÔNG TIN CÁ NHÂN
// =============================================
function petshop_save_user_profile() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_account_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $user_id = get_current_user_id();
    
    // Sanitize data
    $display_name = sanitize_text_field($_POST['display_name']);
    $phone = sanitize_text_field($_POST['phone']);
    
    // Update user data
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $display_name,
    ));
    
    // Update user meta
    update_user_meta($user_id, 'petshop_phone', $phone);
    
    wp_send_json_success(array('message' => 'Đã lưu thông tin cá nhân'));
}
add_action('wp_ajax_petshop_save_user_profile', 'petshop_save_user_profile');

// =============================================
// AJAX: LƯU ĐỊA CHỈ MỚI
// =============================================
function petshop_save_address() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_account_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $user_id = get_current_user_id();
    
    // Get existing addresses
    $addresses = get_user_meta($user_id, 'petshop_addresses', true);
    if (!is_array($addresses)) {
        $addresses = array();
    }
    
    // Sanitize new address
    $new_address = array(
        'id' => isset($_POST['address_id']) && $_POST['address_id'] ? sanitize_text_field($_POST['address_id']) : uniqid('addr_'),
        'label' => sanitize_text_field($_POST['label']), // Nhà riêng, Văn phòng, etc.
        'fullname' => sanitize_text_field($_POST['fullname']),
        'phone' => sanitize_text_field($_POST['phone']),
        'city' => sanitize_text_field($_POST['city']),
        'city_text' => sanitize_text_field($_POST['city_text']),
        'district' => sanitize_text_field($_POST['district']),
        'district_text' => sanitize_text_field($_POST['district_text']),
        'ward' => sanitize_text_field($_POST['ward']),
        'ward_text' => sanitize_text_field($_POST['ward_text']),
        'address' => sanitize_text_field($_POST['address']),
    );
    
    // Check if updating existing address
    $address_index = -1;
    foreach ($addresses as $index => $addr) {
        if ($addr['id'] === $new_address['id']) {
            $address_index = $index;
            break;
        }
    }
    
    if ($address_index >= 0) {
        $addresses[$address_index] = $new_address;
    } else {
        $addresses[] = $new_address;
    }
    
    // Save addresses
    update_user_meta($user_id, 'petshop_addresses', $addresses);
    
    // If this is first address or set as default
    if (count($addresses) === 1 || (isset($_POST['is_default']) && $_POST['is_default'])) {
        update_user_meta($user_id, 'petshop_default_address_id', $new_address['id']);
    }
    
    wp_send_json_success(array(
        'message' => 'Đã lưu địa chỉ',
        'address' => $new_address,
        'addresses' => $addresses
    ));
}
add_action('wp_ajax_petshop_save_address', 'petshop_save_address');

// =============================================
// AJAX: XÓA ĐỊA CHỈ
// =============================================
function petshop_delete_address() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_account_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $user_id = get_current_user_id();
    $address_id = sanitize_text_field($_POST['address_id']);
    
    $addresses = get_user_meta($user_id, 'petshop_addresses', true);
    if (!is_array($addresses)) {
        wp_send_json_error(array('message' => 'Không tìm thấy địa chỉ'));
    }
    
    // Remove address
    $new_addresses = array();
    foreach ($addresses as $addr) {
        if ($addr['id'] !== $address_id) {
            $new_addresses[] = $addr;
        }
    }
    
    update_user_meta($user_id, 'petshop_addresses', $new_addresses);
    
    // Update default if deleted
    $default_id = get_user_meta($user_id, 'petshop_default_address_id', true);
    if ($default_id === $address_id && !empty($new_addresses)) {
        update_user_meta($user_id, 'petshop_default_address_id', $new_addresses[0]['id']);
    }
    
    wp_send_json_success(array('message' => 'Đã xóa địa chỉ'));
}
add_action('wp_ajax_petshop_delete_address', 'petshop_delete_address');

// =============================================
// AJAX: ĐẶT ĐỊA CHỈ MẶC ĐỊNH
// =============================================
function petshop_set_default_address() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_account_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $user_id = get_current_user_id();
    $address_id = sanitize_text_field($_POST['address_id']);
    
    update_user_meta($user_id, 'petshop_default_address_id', $address_id);
    
    wp_send_json_success(array('message' => 'Đã đặt làm địa chỉ mặc định'));
}
add_action('wp_ajax_petshop_set_default_address', 'petshop_set_default_address');

// =============================================
// LẤY ĐỊA CHỈ MẶC ĐỊNH CỦA USER
// =============================================
function petshop_get_default_address($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return null;
    }
    
    $addresses = get_user_meta($user_id, 'petshop_addresses', true);
    $default_id = get_user_meta($user_id, 'petshop_default_address_id', true);
    
    if (!is_array($addresses) || empty($addresses)) {
        return null;
    }
    
    // Find default address
    foreach ($addresses as $addr) {
        if ($addr['id'] === $default_id) {
            return $addr;
        }
    }
    
    // Return first address if no default set
    return $addresses[0];
}

// =============================================
// LẤY TẤT CẢ ĐỊA CHỈ CỦA USER
// =============================================
function petshop_get_user_addresses($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    $addresses = get_user_meta($user_id, 'petshop_addresses', true);
    return is_array($addresses) ? $addresses : array();
}

// =============================================
// AJAX: LẤY THÔNG TIN CHO TRANG THANH TOÁN
// =============================================
function petshop_get_checkout_info() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'));
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $default_address = petshop_get_default_address($user_id);
    
    wp_send_json_success(array(
        'user' => array(
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, 'petshop_phone', true),
        ),
        'default_address' => $default_address,
    ));
}
add_action('wp_ajax_petshop_get_checkout_info', 'petshop_get_checkout_info');
add_action('wp_ajax_nopriv_petshop_get_checkout_info', 'petshop_get_checkout_info');

// =============================================
// AJAX: LẤY CHI TIẾT ĐƠN HÀNG CHO USER
// =============================================
function petshop_get_order_detail() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_account_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $order_id = intval($_POST['order_id']);
    $user_id = get_current_user_id();
    
    // Verify order belongs to user
    $order_user_id = get_post_meta($order_id, 'customer_user_id', true);
    if (intval($order_user_id) !== $user_id) {
        wp_send_json_error(array('message' => 'Không có quyền xem đơn hàng này'));
    }
    
    // Get order data
    $order = get_post($order_id);
    if (!$order || $order->post_type !== 'petshop_order') {
        wp_send_json_error(array('message' => 'Không tìm thấy đơn hàng'));
    }
    
    $order_code = get_post_meta($order_id, 'order_code', true);
    $order_date = get_post_meta($order_id, 'order_date', true);
    $order_status = get_post_meta($order_id, 'order_status', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    $payment_method = get_post_meta($order_id, 'payment_method', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_phone = get_post_meta($order_id, 'customer_phone', true);
    $customer_email = get_post_meta($order_id, 'customer_email', true);
    $customer_address = get_post_meta($order_id, 'customer_address', true);
    $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
    
    // Status labels
    $status_labels = array(
        'pending' => array('label' => 'Chờ xử lý', 'color' => '#f0ad4e', 'icon' => 'bi-clock'),
        'processing' => array('label' => 'Đang xử lý', 'color' => '#5bc0de', 'icon' => 'bi-gear'),
        'shipping' => array('label' => 'Đang giao', 'color' => '#17a2b8', 'icon' => 'bi-truck'),
        'completed' => array('label' => 'Hoàn thành', 'color' => '#5cb85c', 'icon' => 'bi-check-circle'),
        'cancelled' => array('label' => 'Đã hủy', 'color' => '#d9534f', 'icon' => 'bi-x-circle'),
    );
    $status_info = isset($status_labels[$order_status]) ? $status_labels[$order_status] : $status_labels['pending'];
    
    // Payment method labels
    $payment_labels = array(
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'bank' => 'Chuyển khoản ngân hàng',
        'vietqr' => 'Chuyển khoản VietQR',
        'vnpay' => 'VNPay',
    );
    $payment_label = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method;
    
    // Build HTML
    ob_start();
    ?>
    <div style="padding: 10px 0;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div>
                <h3 style="margin: 0; color: #5D4E37;">
                    <i class="bi bi-receipt" style="color: #EC802B;"></i> 
                    Đơn hàng #<?php echo esc_html($order_code); ?>
                </h3>
                <small style="color: #7A6B5A;">
                    <i class="bi bi-calendar3"></i> <?php echo $order_date ? date('d/m/Y H:i', strtotime($order_date)) : '-'; ?>
                </small>
            </div>
            <span style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; background: <?php echo $status_info['color']; ?>20; color: <?php echo $status_info['color']; ?>;">
                <i class="bi <?php echo $status_info['icon']; ?>"></i>
                <?php echo $status_info['label']; ?>
            </span>
        </div>
        
        <!-- Timeline -->
        <div style="display: flex; justify-content: space-between; padding: 25px 0; border-bottom: 1px solid #eee;">
            <?php
            $timeline_steps = array(
                array('status' => 'pending', 'label' => 'Đặt hàng', 'icon' => 'bi-cart-check'),
                array('status' => 'processing', 'label' => 'Xử lý', 'icon' => 'bi-gear'),
                array('status' => 'shipping', 'label' => 'Giao hàng', 'icon' => 'bi-truck'),
                array('status' => 'completed', 'label' => 'Hoàn thành', 'icon' => 'bi-check-lg'),
            );
            $current_index = array_search($order_status, array('pending', 'processing', 'shipping', 'completed'));
            if ($current_index === false) $current_index = -1;
            
            foreach ($timeline_steps as $index => $step) :
                $is_active = $index <= $current_index;
                $is_cancelled = $order_status === 'cancelled';
            ?>
            <div style="text-align: center; flex: 1; position: relative;">
                <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; 
                    background: <?php echo $is_cancelled ? '#dc3545' : ($is_active ? '#66BCB4' : '#ddd'); ?>; color: #fff;">
                    <i class="bi <?php echo $is_cancelled && $index === 0 ? 'bi-x-lg' : $step['icon']; ?>"></i>
                </div>
                <small style="display: block; margin-top: 8px; color: <?php echo $is_active ? '#5D4E37' : '#aaa'; ?>;">
                    <?php echo $step['label']; ?>
                </small>
                <?php if ($index < count($timeline_steps) - 1) : ?>
                <div style="position: absolute; top: 20px; left: 50%; width: 100%; height: 2px; background: <?php echo $index < $current_index ? '#66BCB4' : '#ddd'; ?>;"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Shipping Info -->
        <div style="padding: 20px 0; border-bottom: 1px solid #eee;">
            <h4 style="margin: 0 0 15px; color: #5D4E37;">
                <i class="bi bi-geo-alt" style="color: #66BCB4;"></i> Thông tin nhận hàng
            </h4>
            <div style="background: #FDF8F3; padding: 15px; border-radius: 10px;">
                <p style="margin: 0 0 8px;"><strong><?php echo esc_html($customer_name); ?></strong></p>
                <p style="margin: 0 0 5px; color: #7A6B5A;"><i class="bi bi-telephone"></i> <?php echo esc_html($customer_phone); ?></p>
                <p style="margin: 0 0 5px; color: #7A6B5A;"><i class="bi bi-envelope"></i> <?php echo esc_html($customer_email); ?></p>
                <p style="margin: 0; color: #7A6B5A;"><i class="bi bi-house"></i> <?php echo esc_html($customer_address); ?></p>
            </div>
        </div>
        
        <!-- Products -->
        <div style="padding: 20px 0; border-bottom: 1px solid #eee;">
            <h4 style="margin: 0 0 15px; color: #5D4E37;">
                <i class="bi bi-bag" style="color: #EC802B;"></i> Sản phẩm đã đặt
            </h4>
            <?php if (is_array($cart_items)) : ?>
                <?php foreach ($cart_items as $item) : ?>
                <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px dashed #eee;">
                    <?php if (!empty($item['image'])) : ?>
                    <img src="<?php echo esc_url($item['image']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <p style="margin: 0 0 5px; color: #5D4E37; font-weight: 500;"><?php echo esc_html($item['name']); ?></p>
                        <small style="color: #7A6B5A;">Số lượng: <?php echo intval($item['quantity']); ?></small>
                    </div>
                    <span style="color: #EC802B; font-weight: 600;"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Summary -->
        <div style="padding: 20px 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #7A6B5A;">Phương thức thanh toán:</span>
                <span><?php echo esc_html($payment_label); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px dashed #eee;">
                <strong style="color: #5D4E37; font-size: 1.1rem;">Tổng cộng:</strong>
                <strong style="color: #EC802B; font-size: 1.3rem;"><?php echo number_format($order_total, 0, ',', '.'); ?>đ</strong>
            </div>
        </div>
        
        <?php if ($order_status === 'completed') : ?>
        <div style="background: #e6ffe6; padding: 15px; border-radius: 10px; text-align: center;">
            <i class="bi bi-check-circle-fill" style="color: #28a745; font-size: 1.5rem;"></i>
            <p style="margin: 10px 0 0; color: #28a745;">Đơn hàng đã hoàn thành. Cảm ơn bạn!</p>
        </div>
        <?php endif; ?>
        
        <?php if ($order_status === 'cancelled') : ?>
        <div style="background: #ffe6e6; padding: 15px; border-radius: 10px; text-align: center;">
            <i class="bi bi-x-circle-fill" style="color: #dc3545; font-size: 1.5rem;"></i>
            <p style="margin: 10px 0 0; color: #dc3545;">Đơn hàng đã bị hủy.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_petshop_get_order_detail', 'petshop_get_order_detail');
