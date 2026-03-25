<?php
/**
 * PetShop Orders & Reviews System
 * Hệ thống đơn hàng và đánh giá sản phẩm
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// VARIANT STOCK HELPERS (inline để đảm bảo luôn available)
// =============================================
if (!function_exists('petshop_reduce_variant_stock')) {
    function petshop_reduce_variant_stock($variant_id, $quantity) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_variants';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return false;
        $variant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $variant_id), ARRAY_A);
        if (!$variant) return false;
        $new_stock = max(0, intval($variant['stock']) - intval($quantity));
        $wpdb->update($table, array('stock' => $new_stock), array('id' => $variant_id));
        // Sync tổng stock vào postmeta
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(stock),0) FROM {$table} WHERE product_id = %d", $variant['product_id']));
        update_post_meta($variant['product_id'], 'product_stock', $total);
        return true;
    }
}

// =============================================
// ĐĂNG KÝ CUSTOM POST TYPE: ORDERS
// =============================================
function petshop_register_order_post_type() {
    register_post_type('petshop_order', array(
        'labels' => array(
            'name' => 'Đơn hàng',
            'singular_name' => 'Đơn hàng',
            'menu_name' => 'Đơn hàng',
            'all_items' => 'Tất cả đơn hàng',
            'view_item' => 'Xem đơn hàng',
            'edit_item' => 'Sửa đơn hàng',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-cart',
        'supports' => array('title'),
        'capability_type' => 'post',
    ));
}
add_action('init', 'petshop_register_order_post_type');

// =============================================
// AJAX: LƯU ĐƠN HÀNG
// =============================================
function petshop_save_order() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_checkout_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    // Parse order data - handle JSON string
    $order_data_raw = isset($_POST['order_data']) ? $_POST['order_data'] : '';
    $order_data = is_string($order_data_raw) ? json_decode(stripslashes($order_data_raw), true) : $order_data_raw;
    
    if (empty($order_data)) {
        wp_send_json_error(array('message' => 'No order data'));
    }
    
    // Sanitize data
    $customer_name = sanitize_text_field($order_data['customer_name']);
    $customer_phone = sanitize_text_field($order_data['customer_phone']);
    $customer_email = sanitize_email($order_data['customer_email']);
    $customer_address = sanitize_textarea_field($order_data['customer_address']);
    $payment_method = sanitize_text_field($order_data['payment_method']);
    $order_note = sanitize_textarea_field($order_data['order_note']);
    $cart_items = $order_data['cart_items'];
    $order_total = floatval($order_data['order_total']);
    
    // Thông tin giá và coupon - tách biệt rõ ràng
    $subtotal = isset($order_data['subtotal']) ? floatval($order_data['subtotal']) : $order_total;
    $shipping_fee = isset($order_data['shipping_fee']) ? floatval($order_data['shipping_fee']) : 0; // Phí ship gốc
    $shipping_discount = isset($order_data['shipping_discount']) ? floatval($order_data['shipping_discount']) : 0; // Giảm phí ship
    $final_shipping = isset($order_data['final_shipping']) ? floatval($order_data['final_shipping']) : $shipping_fee; // Phí ship cuối
    $discount = isset($order_data['discount']) ? floatval($order_data['discount']) : 0; // Giảm giá sản phẩm
    $coupon_id = isset($order_data['coupon_id']) ? intval($order_data['coupon_id']) : 0;
    $coupon_code = isset($order_data['coupon_code']) ? sanitize_text_field($order_data['coupon_code']) : '';
    
    // Generate order code
    $order_code = 'PET' . date('Ymd') . strtoupper(substr(uniqid(), -4));
    
    // Create order post
    $order_id = wp_insert_post(array(
        'post_type' => 'petshop_order',
        'post_title' => $order_code,
        'post_status' => 'publish',
        'post_author' => get_current_user_id() ?: 1,
    ));
    
    if ($order_id && !is_wp_error($order_id)) {
        // Save order meta
        update_post_meta($order_id, 'order_code', $order_code);
        update_post_meta($order_id, 'customer_name', $customer_name);
        update_post_meta($order_id, 'customer_phone', $customer_phone);
        update_post_meta($order_id, 'customer_email', $customer_email);
        update_post_meta($order_id, 'customer_address', $customer_address);
        update_post_meta($order_id, 'payment_method', $payment_method);
        update_post_meta($order_id, 'order_note', $order_note);
        update_post_meta($order_id, 'order_total', $order_total);
        update_post_meta($order_id, 'order_subtotal', $subtotal);
        update_post_meta($order_id, 'order_shipping', $shipping_fee); // Phí ship gốc
        update_post_meta($order_id, 'order_shipping_discount', $shipping_discount); // Giảm phí ship
        update_post_meta($order_id, 'order_final_shipping', $final_shipping); // Phí ship thực trả
        update_post_meta($order_id, 'order_discount', $discount); // Giảm giá sản phẩm
        update_post_meta($order_id, 'coupon_id', $coupon_id);
        update_post_meta($order_id, 'coupon_code', $coupon_code);
        update_post_meta($order_id, 'order_status', 'pending'); // pending, processing, completed, cancelled
        update_post_meta($order_id, 'order_date', current_time('mysql'));
        update_post_meta($order_id, 'cart_items', json_encode($cart_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($order_id, '_prev_order_status', 'pending'); // For tracking status changes
        
        // Save user ID if logged in
        if (is_user_logged_in()) {
            update_post_meta($order_id, 'customer_user_id', get_current_user_id());
        }
        
        // Ghi nhận sử dụng coupon nếu có - tính tổng giảm (sản phẩm + ship)
        $total_discount = $discount + $shipping_discount;
        if ($coupon_id > 0 && function_exists('petshop_record_coupon_usage')) {
            petshop_record_coupon_usage($coupon_id, $order_id, $total_discount);
        }
        
        // Update product sold count and reduce stock with logging
        foreach ($cart_items as $item) {
            $product_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            
            if ($product_id > 0) {
                // Update sold count
                $sold_count = intval(get_post_meta($product_id, 'product_sold_count', true));
                update_post_meta($product_id, 'product_sold_count', $sold_count + $quantity);
                
                // Reduce stock — ưu tiên theo variant nếu có
                $variant_id = isset($item['variantId']) ? intval($item['variantId']) : 0;
                if ($variant_id > 0 && function_exists('petshop_reduce_variant_stock')) {
                    // Trừ stock variant cụ thể, hàm tự sync product_stock tổng
                    petshop_reduce_variant_stock($variant_id, $quantity);
                } else {
                    // Sản phẩm không có variant — trừ stock thông thường
                    $current_stock = get_post_meta($product_id, 'product_stock', true);
                    if ($current_stock !== '' && $current_stock !== false) {
                        $new_stock = max(0, intval($current_stock) - $quantity);
                        update_post_meta($product_id, 'product_stock', $new_stock);
                        
                        // Log stock change
                        if (function_exists('petshop_log_stock_change')) {
                            petshop_log_stock_change(
                                $product_id,
                                $current_stock,
                                $new_stock,
                                'order',
                                'Đơn hàng #' . $order_code,
                                get_current_user_id() ?: 0,
                                $order_id
                            );
                        }
                    }
                }
            }
        }
        
        // =============================================
        // GỬI EMAIL XÁC NHẬN ĐƠN HÀNG CHO KHÁCH
        // =============================================
        petshop_send_order_confirmation_email($order_id);

        // =============================================
        // THÔNG BÁO CHUÔNG REAL-TIME CHO KHÁCH (gọi TRƯỚC wp_send_json_success)
        // =============================================
        if (is_user_logged_in() && function_exists('petshop_notify_customer_order_status')) {
            $customer_uid = get_post_meta($order_id, 'customer_user_id', true);
            if (!$customer_uid) $customer_uid = get_current_user_id();
            if ($customer_uid) {
                petshop_notify_customer_order_status($order_id, 'pending');
            }
        }
        
        // =============================================
        // THÔNG BÁO CHO ADMIN CÓ ĐƠN HÀNG MỚI
        // =============================================
        petshop_notify_admin_new_order($order_id);
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'order_code' => $order_code,
            'message' => 'Order created successfully'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to create order'));
    }
}
add_action('wp_ajax_petshop_save_order', 'petshop_save_order');
add_action('wp_ajax_nopriv_petshop_save_order', 'petshop_save_order');

// =============================================
// KIỂM TRA USER ĐÃ MUA SẢN PHẨM CHƯA
// =============================================
function petshop_user_has_purchased($product_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Query orders by this user
    $orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'customer_user_id',
                'value' => $user_id,
            ),
            array(
                'key' => 'order_status',
                'value' => array('completed', 'processing', 'pending'),
                'compare' => 'IN'
            )
        )
    ));
    
    foreach ($orders as $order) {
        $cart_items = json_decode(get_post_meta($order->ID, 'cart_items', true), true);
        if (is_array($cart_items)) {
            foreach ($cart_items as $item) {
                if (intval($item['id']) === intval($product_id)) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

// =============================================
// KIỂM TRA USER ĐÃ ĐÁNH GIÁ SẢN PHẨM CHƯA
// =============================================
function petshop_user_has_reviewed($product_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $reviews = get_comments(array(
        'post_id' => $product_id,
        'user_id' => $user_id,
        'type' => 'product_review',
        'status' => 'all',
    ));
    
    return !empty($reviews);
}

// =============================================
// LẤY ĐÁNH GIÁ CỦA SẢN PHẨM
// =============================================
function petshop_get_product_reviews($product_id, $limit = 10) {
    $reviews = get_comments(array(
        'post_id' => $product_id,
        'type' => 'product_review',
        'status' => 'approve',
        'number' => $limit,
        'orderby' => 'comment_date',
        'order' => 'DESC',
    ));
    
    return $reviews;
}

// =============================================
// TÍNH ĐIỂM ĐÁNH GIÁ TRUNG BÌNH
// =============================================
function petshop_get_average_rating($product_id) {
    $reviews = get_comments(array(
        'post_id' => $product_id,
        'type' => 'product_review',
        'status' => 'approve',
    ));
    
    if (empty($reviews)) {
        return array(
            'average' => 0,
            'count' => 0,
        );
    }
    
    $total = 0;
    foreach ($reviews as $review) {
        $rating = intval(get_comment_meta($review->comment_ID, 'rating', true));
        $total += $rating;
    }
    
    return array(
        'average' => round($total / count($reviews), 1),
        'count' => count($reviews),
    );
}

// =============================================
// LẤY SỐ LƯỢNG ĐÃ BÁN
// =============================================
function petshop_get_sold_count($product_id) {
    $sold = get_post_meta($product_id, 'product_sold_count', true);
    return $sold ? intval($sold) : 0;
}

// =============================================
// AJAX: GỬI ĐÁNH GIÁ
// =============================================
function petshop_submit_review() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_review_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập để đánh giá'));
    }
    
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review_content = sanitize_textarea_field($_POST['review_content']);
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    // Validate
    if ($rating < 1 || $rating > 5) {
        wp_send_json_error(array('message' => 'Vui lòng chọn số sao'));
    }
    
    if (empty($review_content)) {
        wp_send_json_error(array('message' => 'Vui lòng nhập nội dung đánh giá'));
    }
    
    // Check if user purchased this product
    if (!petshop_user_has_purchased($product_id)) {
        wp_send_json_error(array('message' => 'Bạn cần mua sản phẩm này để đánh giá'));
    }
    
    // Check if already reviewed
    if (petshop_user_has_reviewed($product_id)) {
        wp_send_json_error(array('message' => 'Bạn đã đánh giá sản phẩm này rồi'));
    }
    
    // Insert review as comment
    $comment_data = array(
        'comment_post_ID' => $product_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content' => $review_content,
        'comment_type' => 'product_review',
        'user_id' => $user_id,
        'comment_approved' => 1, // Auto approve
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        // Save rating as comment meta
        update_comment_meta($comment_id, 'rating', $rating);
        
        // Update product average rating cache
        $rating_data = petshop_get_average_rating($product_id);
        update_post_meta($product_id, 'average_rating', $rating_data['average']);
        update_post_meta($product_id, 'review_count', $rating_data['count']);
        
        wp_send_json_success(array(
            'message' => 'Cảm ơn bạn đã đánh giá!',
            'review_id' => $comment_id,
        ));
    } else {
        wp_send_json_error(array('message' => 'Không thể gửi đánh giá. Vui lòng thử lại.'));
    }
}
add_action('wp_ajax_petshop_submit_review', 'petshop_submit_review');

// =============================================
// ADMIN: HIỂN THỊ COLUMNS CHO ORDERS
// =============================================
function petshop_order_columns($columns) {
    return array(
        'cb' => $columns['cb'],
        'title' => 'Mã đơn hàng',
        'customer' => 'Khách hàng',
        'total' => 'Tổng tiền',
        'status' => 'Trạng thái',
        'date' => 'Ngày đặt',
    );
}
add_filter('manage_petshop_order_posts_columns', 'petshop_order_columns');

function petshop_order_column_content($column, $post_id) {
    switch ($column) {
        case 'customer':
            $name = get_post_meta($post_id, 'customer_name', true);
            $phone = get_post_meta($post_id, 'customer_phone', true);
            echo esc_html($name) . '<br><small>' . esc_html($phone) . '</small>';
            break;
        case 'total':
            $total = get_post_meta($post_id, 'order_total', true);
            echo number_format($total, 0, ',', '.') . 'đ';
            break;
        case 'status':
            $status = get_post_meta($post_id, 'order_status', true);
            $statuses = array(
                'pending' => '<span style="color: #f0ad4e;">Chờ xử lý</span>',
                'processing' => '<span style="color: #5bc0de;">Đang xử lý</span>',
                'completed' => '<span style="color: #5cb85c;">Hoàn thành</span>',
                'cancelled' => '<span style="color: #d9534f;">Đã hủy</span>',
            );
            echo isset($statuses[$status]) ? $statuses[$status] : $status;
            break;
    }
}
add_action('manage_petshop_order_posts_custom_column', 'petshop_order_column_content', 10, 2);

// =============================================
// ADMIN: META BOX CHO ORDER DETAILS
// =============================================
function petshop_order_meta_boxes() {
    add_meta_box(
        'petshop_order_details',
        'Chi tiết đơn hàng',
        'petshop_order_details_callback',
        'petshop_order',
        'normal',
        'high'
    );
    
    add_meta_box(
        'petshop_order_status',
        'Trạng thái đơn hàng',
        'petshop_order_status_callback',
        'petshop_order',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'petshop_order_meta_boxes');

function petshop_order_details_callback($post) {
    $customer_name = get_post_meta($post->ID, 'customer_name', true);
    $customer_phone = get_post_meta($post->ID, 'customer_phone', true);
    $customer_email = get_post_meta($post->ID, 'customer_email', true);
    $customer_address = get_post_meta($post->ID, 'customer_address', true);
    $payment_method = get_post_meta($post->ID, 'payment_method', true);
    $order_note = get_post_meta($post->ID, 'order_note', true);
    $order_total = get_post_meta($post->ID, 'order_total', true);
    $order_subtotal = get_post_meta($post->ID, 'order_subtotal', true);
    $order_shipping = get_post_meta($post->ID, 'order_final_shipping', true);
    $order_discount = get_post_meta($post->ID, 'order_discount', true);
    $shipping_discount = get_post_meta($post->ID, 'order_shipping_discount', true);
    $coupon_code = get_post_meta($post->ID, 'coupon_code', true);
    $cart_items = json_decode(get_post_meta($post->ID, 'cart_items', true), true);
    $order_date = get_post_meta($post->ID, 'order_date', true);
    $email_sent = get_post_meta($post->ID, 'email_sent', true);
    
    $payment_labels = array(
        'cod' => 'COD - Thanh toán khi nhận hàng',
        'online' => 'Thanh toán Online',
        'vnpay' => 'VNPay',
        'bank' => 'Chuyển khoản ngân hàng'
    );
    
    ?>
    <style>
        .order-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .order-info-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #EC802B; }
        .order-info-box h4 { margin: 0 0 12px; color: #23282d; font-size: 14px; }
        .order-info-box p { margin: 6px 0; font-size: 13px; }
        .order-items-table { width: 100%; border-collapse: collapse; }
        .order-items-table th, .order-items-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .order-items-table th { background: #f5f5f5; font-size: 13px; }
        .order-items-table td { font-size: 13px; }
        .order-summary { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .order-summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; border-bottom: 1px dashed #ddd; }
        .order-summary-row:last-child { border-bottom: none; }
        .order-summary-row.total { font-size: 16px; font-weight: bold; border-top: 2px solid #ddd; margin-top: 10px; padding-top: 15px; }
        .order-actions-bar { background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        .email-status { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; padding: 5px 10px; border-radius: 20px; }
        .email-status.sent { background: #d4edda; color: #155724; }
        .email-status.not-sent { background: #f8d7da; color: #721c24; }
    </style>
    
    <!-- Quick Actions -->
    <div class="order-actions-bar">
        <a href="<?php echo admin_url('admin-ajax.php?action=petshop_view_bill&order_id=' . $post->ID); ?>" 
           class="button button-primary" target="_blank">
            <span class="dashicons dashicons-media-text" style="vertical-align: middle; margin-right: 5px;"></span>
            Xem & In Bill
        </a>
        
        <span class="email-status <?php echo $email_sent ? 'sent' : 'not-sent'; ?>">
            <?php if ($email_sent) : ?>
                <span class="dashicons dashicons-yes-alt"></span>
                Email đã gửi lúc <?php echo date('d/m/Y H:i', strtotime($email_sent)); ?>
            <?php else : ?>
                <span class="dashicons dashicons-warning"></span>
                Chưa gửi email
            <?php endif; ?>
        </span>
        
        <?php if (!$email_sent) : ?>
        <button type="button" class="button" onclick="resendOrderEmail(<?php echo $post->ID; ?>)">
            <span class="dashicons dashicons-email" style="vertical-align: middle;"></span> Gửi lại email
        </button>
        <?php endif; ?>
    </div>
    
    <div class="order-info-grid">
        <div class="order-info-box">
            <h4><span class="dashicons dashicons-admin-users"></span> Thông tin khách hàng</h4>
            <p><strong>Họ tên:</strong> <?php echo esc_html($customer_name); ?></p>
            <p><strong>SĐT:</strong> <a href="tel:<?php echo esc_attr($customer_phone); ?>"><?php echo esc_html($customer_phone); ?></a></p>
            <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($customer_email); ?>"><?php echo esc_html($customer_email); ?></a></p>
            <p><strong>Địa chỉ:</strong> <?php echo esc_html($customer_address); ?></p>
        </div>
        <div class="order-info-box">
            <h4><span class="dashicons dashicons-info"></span> Thông tin đơn hàng</h4>
            <p><strong>Ngày đặt:</strong> <?php echo $order_date ? date('d/m/Y H:i', strtotime($order_date)) : '-'; ?></p>
            <p><strong>Thanh toán:</strong> <?php echo isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method; ?></p>
            <?php if ($coupon_code) : ?>
            <p><strong>Mã giảm giá:</strong> <code style="background: #e7f5e7; padding: 2px 8px; border-radius: 3px;"><?php echo esc_html($coupon_code); ?></code></p>
            <?php endif; ?>
            <?php if ($order_note) : ?>
            <p><strong>Ghi chú:</strong> <em><?php echo esc_html($order_note); ?></em></p>
            <?php endif; ?>
        </div>
    </div>
    
    <h4 style="margin-bottom: 10px;"><span class="dashicons dashicons-products" style="color: #EC802B;"></span> Sản phẩm đặt mua</h4>
    <table class="order-items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th style="text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php if (is_array($cart_items)) : ?>
                <?php foreach ($cart_items as $item) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($item['name']); ?></strong>
                        <?php if (!empty($item['sku'])) : ?>
                            <br><small style="color: #666;">SKU: <?php echo esc_html($item['sku']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                    <td><?php echo intval($item['quantity']); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</strong></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="order-summary">
        <?php if ($order_subtotal) : ?>
        <div class="order-summary-row">
            <span>Tạm tính:</span>
            <span><?php echo number_format($order_subtotal, 0, ',', '.'); ?>đ</span>
        </div>
        <?php endif; ?>
        
        <?php if ($order_shipping) : ?>
        <div class="order-summary-row">
            <span>Phí vận chuyển:</span>
            <span><?php echo number_format($order_shipping, 0, ',', '.'); ?>đ</span>
        </div>
        <?php endif; ?>
        
        <?php 
        $total_discount = floatval($order_discount) + floatval($shipping_discount);
        if ($total_discount > 0) : 
        ?>
        <div class="order-summary-row" style="color: #28a745;">
            <span>Giảm giá:</span>
            <span>-<?php echo number_format($total_discount, 0, ',', '.'); ?>đ</span>
        </div>
        <?php endif; ?>
        
        <div class="order-summary-row total">
            <span>TỔNG CỘNG:</span>
            <span style="color: #d63638;"><?php echo number_format($order_total, 0, ',', '.'); ?>đ</span>
        </div>
    </div>
    
    <script>
    function resendOrderEmail(orderId) {
        if (!confirm('Gửi lại email xác nhận đơn hàng cho khách?')) return;
        
        jQuery.post(ajaxurl, {
            action: 'petshop_resend_order_email',
            order_id: orderId
        }, function(response) {
            if (response.success) {
                alert('Đã gửi email thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + response.data.message);
            }
        });
    }
    </script>
    <?php
}

function petshop_order_status_callback($post) {
    $current_status = get_post_meta($post->ID, 'order_status', true) ?: 'pending';
    wp_nonce_field('petshop_order_status', 'petshop_order_status_nonce');
    ?>
    <select name="order_status" style="width: 100%;">
        <option value="pending" <?php selected($current_status, 'pending'); ?>>Chờ xử lý</option>
        <option value="processing" <?php selected($current_status, 'processing'); ?>>Đang xử lý</option>
        <option value="completed" <?php selected($current_status, 'completed'); ?>>Hoàn thành</option>
        <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>>Đã hủy</option>
    </select>
    <p class="description">Cập nhật trạng thái đơn hàng</p>
    <?php
}

function petshop_save_order_status($post_id) {
    if (!isset($_POST['petshop_order_status_nonce']) || 
        !wp_verify_nonce($_POST['petshop_order_status_nonce'], 'petshop_order_status')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['order_status'])) {
        $new_status = sanitize_text_field($_POST['order_status']);
        $old_status = get_post_meta($post_id, 'order_status', true);
        
        // Cập nhật trạng thái
        update_post_meta($post_id, 'order_status', $new_status);
        
        // Nếu trạng thái thay đổi, gửi email
        if ($new_status !== $old_status) {
            // Dùng do_action để trigger hook
            do_action('petshop_order_status_changed', $post_id, $new_status, $old_status);
        }
    }
}
add_action('save_post_petshop_order', 'petshop_save_order_status');

// Hook xử lý khi status thay đổi
function petshop_handle_status_change($order_id, $new_status, $old_status) {
    $settings = get_option('petshop_shop_settings', array());
    
    // Gửi email theo trạng thái mới
    switch ($new_status) {
        case 'processing':
            if (!empty($settings['email_order_processing'])) {
                petshop_send_order_status_email($order_id, 'processing');
            }
            break;
        case 'shipping':
            if (!empty($settings['email_order_shipping'])) {
                petshop_send_order_status_email($order_id, 'shipping');
            }
            break;
        case 'completed':
            if (!empty($settings['email_order_completed'])) {
                petshop_send_order_status_email($order_id, 'completed');
            }
            break;
        case 'cancelled':
            if (!empty($settings['email_order_cancelled'])) {
                petshop_send_order_status_email($order_id, 'cancelled');
            }
            break;
    }
}
add_action('petshop_order_status_changed', 'petshop_handle_status_change', 10, 3);

// =============================================
// LẤY ĐƠN HÀNG CỦA USER
// =============================================
function petshop_get_user_orders($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    $orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'meta_key' => 'customer_user_id',
        'meta_value' => $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    
    return $orders;
}

// =============================================
// LẤY SẢN PHẨM ĐÃ MUA CỦA USER (CHƯA ĐÁNH GIÁ)
// =============================================
function petshop_get_products_to_review($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    $orders = petshop_get_user_orders($user_id);
    $products_to_review = array();
    
    foreach ($orders as $order) {
        $cart_items = json_decode(get_post_meta($order->ID, 'cart_items', true), true);
        if (is_array($cart_items)) {
            foreach ($cart_items as $item) {
                $product_id = intval($item['id']);
                // Check if product exists and not reviewed yet
                if ($product_id > 0 && get_post($product_id) && !petshop_user_has_reviewed($product_id, $user_id)) {
                    $products_to_review[$product_id] = array(
                        'id' => $product_id,
                        'name' => $item['name'],
                        'image' => $item['image'] ?? get_the_post_thumbnail_url($product_id, 'thumbnail'),
                        'order_id' => $order->ID,
                        'order_code' => get_post_meta($order->ID, 'order_code', true),
                    );
                }
            }
        }
    }
    
    return array_values($products_to_review);
}

// =============================================
// SHORTCODE: FORM ĐÁNH GIÁ SẢN PHẨM
// =============================================
function petshop_review_form_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="notice notice-warning">Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để đánh giá sản phẩm.</div>';
    }
    
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $products_to_review = petshop_get_products_to_review();
    
    if (empty($products_to_review)) {
        return '<div class="notice notice-info">Bạn chưa có sản phẩm nào cần đánh giá. Hãy mua sắm và quay lại đây sau nhé!</div>';
    }
    
    ob_start();
    ?>
    <style>
    .review-form-wrap { max-width: 700px; margin: 0 auto; }
    .product-select-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .product-select-item { border: 2px solid #eee; border-radius: 10px; padding: 15px; cursor: pointer; text-align: center; transition: all 0.3s; }
    .product-select-item:hover { border-color: var(--color-primary); }
    .product-select-item.selected { border-color: var(--color-primary); background: rgba(236, 128, 43, 0.05); }
    .product-select-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
    .product-select-item .name { font-size: 0.9rem; color: #333; font-weight: 500; }
    .rating-select { display: flex; gap: 10px; justify-content: center; margin: 20px 0; }
    .rating-select .star { font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s; }
    .rating-select .star:hover, .rating-select .star.active { color: #f1c40f; }
    .review-textarea { width: 100%; min-height: 120px; padding: 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 1rem; resize: vertical; }
    .review-textarea:focus { border-color: var(--color-primary); outline: none; }
    .submit-review-btn { width: 100%; padding: 15px; background: var(--color-primary); color: white; border: none; border-radius: 10px; font-size: 1.1rem; cursor: pointer; font-weight: 600; }
    .submit-review-btn:hover { background: #d97428; }
    .submit-review-btn:disabled { background: #ccc; cursor: not-allowed; }
    </style>
    
    <div class="review-form-wrap">
        <h3 style="text-align: center; margin-bottom: 20px;">Chọn sản phẩm để đánh giá</h3>
        
        <div class="product-select-grid">
            <?php foreach ($products_to_review as $product) : ?>
            <div class="product-select-item <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>" 
                 data-product-id="<?php echo $product['id']; ?>"
                 onclick="selectProductToReview(this)">
                <img src="<?php echo esc_url($product['image']); ?>" alt="">
                <div class="name"><?php echo esc_html($product['name']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <form id="review-form" style="display: <?php echo $product_id ? 'block' : 'none'; ?>;">
            <input type="hidden" name="product_id" id="review-product-id" value="<?php echo $product_id; ?>">
            
            <h4 style="text-align: center;">Đánh giá của bạn</h4>
            <div class="rating-select" id="rating-select">
                <span class="star" data-rating="1"><i class="bi bi-star-fill"></i></span>
                <span class="star" data-rating="2"><i class="bi bi-star-fill"></i></span>
                <span class="star" data-rating="3"><i class="bi bi-star-fill"></i></span>
                <span class="star" data-rating="4"><i class="bi bi-star-fill"></i></span>
                <span class="star" data-rating="5"><i class="bi bi-star-fill"></i></span>
            </div>
            <input type="hidden" name="rating" id="review-rating" value="0">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Nhận xét của bạn</label>
                <textarea class="review-textarea" name="review_content" id="review-content" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>
            </div>
            
            <button type="submit" class="submit-review-btn" id="submit-review-btn">
                <i class="bi bi-send"></i> Gửi đánh giá
            </button>
        </form>
        
        <div id="review-message" style="margin-top: 20px; text-align: center;"></div>
    </div>
    
    <script>
    function selectProductToReview(el) {
        document.querySelectorAll('.product-select-item').forEach(item => item.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('review-product-id').value = el.dataset.productId;
        document.getElementById('review-form').style.display = 'block';
    }
    
    // Star rating
    document.querySelectorAll('.rating-select .star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('review-rating').value = rating;
            document.querySelectorAll('.rating-select .star').forEach((s, index) => {
                s.classList.toggle('active', index < rating);
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = this.dataset.rating;
            document.querySelectorAll('.rating-select .star').forEach((s, index) => {
                s.style.color = index < rating ? '#f1c40f' : '#ddd';
            });
        });
    });
    
    document.querySelector('.rating-select').addEventListener('mouseleave', function() {
        const currentRating = document.getElementById('review-rating').value;
        document.querySelectorAll('.rating-select .star').forEach((s, index) => {
            s.style.color = index < currentRating ? '#f1c40f' : '#ddd';
        });
    });
    
    // Submit review
    document.getElementById('review-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submit-review-btn');
        const msgEl = document.getElementById('review-message');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang gửi...';
        
        const formData = new FormData();
        formData.append('action', 'petshop_submit_review');
        formData.append('nonce', '<?php echo wp_create_nonce('petshop_review_nonce'); ?>');
        formData.append('product_id', document.getElementById('review-product-id').value);
        formData.append('rating', document.getElementById('review-rating').value);
        formData.append('review_content', document.getElementById('review-content').value);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                msgEl.innerHTML = '<div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 10px;"><i class="bi bi-check-circle"></i> ' + data.data.message + '</div>';
                document.getElementById('review-form').reset();
                document.getElementById('review-form').style.display = 'none';
                // Remove reviewed product from list
                document.querySelector('.product-select-item.selected').remove();
            } else {
                msgEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> ' + data.data.message + '</div>';
            }
        })
        .catch(err => {
            msgEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;">Có lỗi xảy ra, vui lòng thử lại.</div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Gửi đánh giá';
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('petshop_review_form', 'petshop_review_form_shortcode');

// =============================================
// GỬI EMAIL XÁC NHẬN ĐƠN HÀNG CHO KHÁCH HÀNG
// =============================================
function petshop_send_order_confirmation_email($order_id) {
    $order_code = get_post_meta($order_id, 'order_code', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_email = get_post_meta($order_id, 'customer_email', true);
    $customer_phone = get_post_meta($order_id, 'customer_phone', true);
    $customer_address = get_post_meta($order_id, 'customer_address', true);
    $payment_method = get_post_meta($order_id, 'payment_method', true);
    $order_note = get_post_meta($order_id, 'order_note', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    $order_subtotal = get_post_meta($order_id, 'order_subtotal', true);
    $order_shipping = get_post_meta($order_id, 'order_final_shipping', true);
    $order_discount = get_post_meta($order_id, 'order_discount', true);
    $shipping_discount = get_post_meta($order_id, 'order_shipping_discount', true);
    $coupon_code = get_post_meta($order_id, 'coupon_code', true);
    $order_date = get_post_meta($order_id, 'order_date', true);
    $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
    
    if (empty($customer_email)) {
        return false;
    }
    
    // Lấy thông tin shop từ settings
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? 'PetShop';
    $shop_phone = $shop_settings['shop_phone'] ?? '0123 456 789';
    $shop_email = $shop_settings['shop_email'] ?? 'support@petshop.com';
    $shop_address = $shop_settings['shop_address'] ?? '';
    
    $payment_labels = array(
        'cod' => 'COD - Thanh toán khi nhận hàng',
        'online' => 'Thanh toán Online',
        'vnpay' => 'VNPay',
        'bank' => 'Chuyển khoản ngân hàng'
    );
    $payment_text = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method;
    
    // Link xem hóa đơn (public) và trang đơn hàng
    $bill_url = home_url('/xem-don-hang/?code=' . $order_code . '&email=' . urlencode($customer_email));
    $account_orders_url = home_url('/tai-khoan/#orders');
    
    // Tạo nội dung email HTML - Giống giao diện Bill
    $subject = 'Xac nhan don hang #' . $order_code . ' - ' . $shop_name;
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Segoe UI, Arial, sans-serif; background: #f5f5f5;">
        <div style="max-width: 700px; margin: 20px auto; background: #ffffff; box-shadow: 0 2px 20px rgba(0,0,0,0.1);">
            
            <!-- Header - Giống Bill -->
            <div style="background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); color: #fff; padding: 30px;">
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <h1 style="margin: 0; font-size: 28px;">' . esc_html($shop_name) . '</h1>
                            <p style="margin: 5px 0 0; opacity: 0.9;">Hoa don ban hang</p>
                        </td>
                        <td style="text-align: right;">
                            <p style="margin: 0; font-size: 12px; opacity: 0.9;">MA DON HANG</p>
                            <p style="margin: 5px 0 0; font-size: 24px; font-weight: bold;">#' . esc_html($order_code) . '</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Success Banner -->
            <div style="background: #d4edda; padding: 15px 30px; border-left: 4px solid #28a745;">
                <p style="margin: 0; color: #155724; font-size: 16px;">
                    [OK] Don hang da duoc dat thanh cong!
                </p>
            </div>
            
            <!-- Body -->
            <div style="padding: 30px;">
            
                <!-- Info Grid - 2 cột giống Bill -->
                <table style="width: 100%; margin-bottom: 30px;">
                    <tr>
                        <td style="width: 48%; vertical-align: top; padding-right: 15px;">
                            <h3 style="font-size: 13px; color: #666; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #eee;">
                                THONG TIN KHACH HANG
                            </h3>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Ho ten:</strong> ' . esc_html($customer_name) . '</p>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Dien thoai:</strong> ' . esc_html($customer_phone) . '</p>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Email:</strong> ' . esc_html($customer_email) . '</p>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Dia chi:</strong> ' . esc_html($customer_address) . '</p>
                        </td>
                        <td style="width: 48%; vertical-align: top; padding-left: 15px;">
                            <h3 style="font-size: 13px; color: #666; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #eee;">
                                THONG TIN DON HANG
                            </h3>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Ngay dat:</strong> ' . ($order_date ? date('d/m/Y H:i', strtotime($order_date)) : date('d/m/Y H:i')) . '</p>
                            <p style="margin: 8px 0; font-size: 14px;"><strong>Thanh toan:</strong> ' . esc_html($payment_text) . '</p>
                            ' . ($order_note ? '<p style="margin: 8px 0; font-size: 14px;"><strong>Ghi chu:</strong> ' . esc_html($order_note) . '</p>' : '') . '
                        </td>
                    </tr>
                </table>
                
                <!-- Products Table - Giống Bill -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; font-size: 13px; color: #666; border-bottom: 2px solid #eee;">San pham</th>
                            <th style="padding: 12px; text-align: center; font-size: 13px; color: #666; border-bottom: 2px solid #eee;">Don gia</th>
                            <th style="padding: 12px; text-align: center; font-size: 13px; color: #666; border-bottom: 2px solid #eee;">SL</th>
                            <th style="padding: 12px; text-align: right; font-size: 13px; color: #666; border-bottom: 2px solid #eee;">Thanh tien</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (is_array($cart_items)) {
        foreach ($cart_items as $item) {
            $item_total = floatval($item['price']) * intval($item['quantity']);
            $message .= '
                        <tr>
                            <td style="padding: 15px 12px; border-bottom: 1px solid #eee;">
                                <strong style="color: #333;">' . esc_html($item['name']) . '</strong>
                            </td>
                            <td style="padding: 15px 12px; border-bottom: 1px solid #eee; text-align: center; font-size: 14px;">
                                ' . number_format($item['price'], 0, ',', '.') . 'd
                            </td>
                            <td style="padding: 15px 12px; border-bottom: 1px solid #eee; text-align: center; font-size: 14px;">
                                ' . intval($item['quantity']) . '
                            </td>
                            <td style="padding: 15px 12px; border-bottom: 1px solid #eee; text-align: right;">
                                <strong style="color: #333;">' . number_format($item_total, 0, ',', '.') . 'd</strong>
                            </td>
                        </tr>';
        }
    }
    
    $message .= '
                    </tbody>
                </table>
                
                <!-- Summary Box - Giống Bill -->
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px;">';
    
    if ($order_subtotal) {
        $message .= '
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px;">
                        <span>Tam tinh:</span>
                        <span>' . number_format($order_subtotal, 0, ',', '.') . 'd</span>
                    </div>';
    }
    
    if ($order_shipping) {
        $message .= '
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px;">
                        <span>Phi van chuyen:</span>
                        <span>' . number_format($order_shipping, 0, ',', '.') . 'd</span>
                    </div>';
    }
    
    $total_discount = floatval($order_discount) + floatval($shipping_discount);
    if ($total_discount > 0) {
        $message .= '
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; color: #28a745;">
                        <span>Giam gia' . ($coupon_code ? ' (' . esc_html($coupon_code) . ')' : '') . ':</span>
                        <span>-' . number_format($total_discount, 0, ',', '.') . 'd</span>
                    </div>';
    }
    
    $message .= '
                    <div style="border-top: 2px solid #ddd; margin-top: 10px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px;">
                        <strong>TONG CONG:</strong>
                        <strong style="color: #d63638;">' . number_format($order_total, 0, ',', '.') . 'd</strong>
                    </div>
                </div>
                
                <!-- Button xem đơn hàng -->
                <div style="text-align: center; margin-top: 30px;">
                    <a href="' . esc_url($bill_url) . '" style="display: inline-block; background: #EC802B; color: #fff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;">
                        Xem hoa don
                    </a>
                </div>
                
                <!-- Next Steps -->
                <div style="background: #fff3e0; border-radius: 10px; padding: 20px; margin-top: 25px; border-left: 4px solid #EC802B;">
                    <h4 style="margin: 0 0 10px; color: #e65100;">[!] Buoc tiep theo</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #666; line-height: 1.8;">
                        <li>Chung toi se goi dien xac nhan don hang</li>
                        <li>Du kien giao hang: ' . date('d/m/Y', strtotime('+2 days')) . ' - ' . date('d/m/Y', strtotime('+4 days')) . '</li>
                        ' . ($payment_method === 'cod' ? '<li>Vui long chuan bi tien mat khi nhan hang</li>' : '') . '
                    </ul>
                </div>
            </div>
            
            <!-- Footer - Giống Bill -->
            <div style="background: #5D4E37; padding: 25px; text-align: center; color: #fff;">
                <p style="margin: 0 0 10px;">Cam on quy khach da mua hang tai ' . esc_html($shop_name) . '!</p>
                <p style="margin: 0; font-size: 14px;">
                    Tel: ' . esc_html($shop_phone) . ' | Email: ' . esc_html($shop_email) . '
                </p>
                ' . ($shop_address ? '<p style="margin: 10px 0 0; font-size: 12px; opacity: 0.8;">' . esc_html($shop_address) . '</p>' : '') . '
            </div>
        </div>
    </body>
    </html>';
    
    // Headers cho email HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
    );
    
    // Gửi email
    $sent = wp_mail($customer_email, $subject, $message, $headers);
    
    // Log kết quả
    if ($sent) {
        update_post_meta($order_id, 'email_sent', current_time('mysql'));
    }
    
    return $sent;
}

// =============================================
// THÔNG BÁO CHO ADMIN KHI CÓ ĐƠN HÀNG MỚI
// =============================================
function petshop_notify_admin_new_order($order_id) {
    $order_code = get_post_meta($order_id, 'order_code', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_phone = get_post_meta($order_id, 'customer_phone', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    
    // Lưu thông báo vào database
    $notifications = get_option('petshop_admin_notifications', array());
    
    $notifications[] = array(
        'id' => uniqid(),
        'type' => 'new_order',
        'order_id' => $order_id,
        'order_code' => $order_code,
        'customer_name' => $customer_name,
        'customer_phone' => $customer_phone,
        'order_total' => $order_total,
        'time' => current_time('mysql'),
        'read' => false
    );
    
    // Giữ tối đa 50 thông báo
    $notifications = array_slice($notifications, -50);
    
    update_option('petshop_admin_notifications', $notifications);
    
    // Gửi email cho admin
    $admin_email = get_option('admin_email');
    $subject = '🔔 Đơn hàng mới #' . $order_code . ' - PetShop';
    
    $message = '
    <html>
    <body style="font-family: Arial, sans-serif; padding: 20px;">
        <div style="max-width: 500px; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="background: linear-gradient(135deg, #EC802B, #F5994D); padding: 20px; text-align: center;">
                <h2 style="color: #fff; margin: 0;">🔔 Đơn hàng mới!</h2>
            </div>
            <div style="padding: 25px;">
                <p style="font-size: 16px; margin-bottom: 20px;">Bạn có đơn hàng mới cần xử lý:</p>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Mã đơn:</td>
                        <td style="padding: 8px 0; text-align: right;"><strong style="color: #EC802B;">#' . esc_html($order_code) . '</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Khách hàng:</td>
                        <td style="padding: 8px 0; text-align: right;">' . esc_html($customer_name) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">SĐT:</td>
                        <td style="padding: 8px 0; text-align: right;">' . esc_html($customer_phone) . '</td>
                    </tr>
                    <tr style="border-top: 1px solid #eee;">
                        <td style="padding: 12px 0; font-size: 18px;"><strong>Tổng tiền:</strong></td>
                        <td style="padding: 12px 0; text-align: right; font-size: 20px; color: #d63638;"><strong>' . number_format($order_total, 0, ',', '.') . 'đ</strong></td>
                    </tr>
                </table>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '" 
                       style="display: inline-block; background: #EC802B; color: #fff; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                        Xem đơn hàng →
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($admin_email, $subject, $message, $headers);
}

// =============================================
// HIỂN THỊ THÔNG BÁO ĐƠN HÀNG MỚI TRONG ADMIN
// =============================================
function petshop_admin_order_notifications() {
    $notifications = get_option('petshop_admin_notifications', array());
    $unread_count = 0;
    
    foreach ($notifications as $notif) {
        if (!$notif['read']) {
            $unread_count++;
        }
    }
    
    if ($unread_count > 0) {
        ?>
        <style>
        #petshop-order-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(236, 128, 43, 0.4);
            z-index: 999999;
            animation: slideInRight 0.5s ease;
            cursor: pointer;
        }
        #petshop-order-notification:hover {
            transform: scale(1.02);
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        </style>
        <div id="petshop-order-notification" onclick="window.location.href='<?php echo admin_url('edit.php?post_type=petshop_order'); ?>'">
            <strong>🔔 Có <?php echo $unread_count; ?> đơn hàng mới!</strong>
            <br><small>Nhấn để xem chi tiết</small>
        </div>
        <?php
    }
}
add_action('admin_footer', 'petshop_admin_order_notifications');

// =============================================
// ĐÁNH DẤU ĐÃ ĐỌC THÔNG BÁO KHI XEM DANH SÁCH ĐƠN HÀNG
// =============================================
function petshop_mark_notifications_read() {
    global $pagenow, $typenow;
    
    if ($pagenow === 'edit.php' && $typenow === 'petshop_order') {
        $notifications = get_option('petshop_admin_notifications', array());
        
        foreach ($notifications as &$notif) {
            $notif['read'] = true;
        }
        
        update_option('petshop_admin_notifications', $notifications);
    }
}
add_action('admin_init', 'petshop_mark_notifications_read');

// =============================================
// THÊM CỘT "BILL" VÀO DANH SÁCH ĐƠN HÀNG ADMIN
// =============================================
function petshop_order_columns_add_bill($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'status') {
            $new_columns['bill'] = 'Hóa đơn';
        }
    }
    return $new_columns;
}
add_filter('manage_petshop_order_posts_columns', 'petshop_order_columns_add_bill', 20);

function petshop_order_column_bill_content($column, $post_id) {
    if ($column === 'bill') {
        echo '<a href="' . admin_url('admin-ajax.php?action=petshop_view_bill&order_id=' . $post_id) . '" class="button" target="_blank" style="font-size: 12px;">
            <span class="dashicons dashicons-media-text" style="vertical-align: middle;"></span> Xem Bill
        </a>';
    }
}
add_action('manage_petshop_order_posts_custom_column', 'petshop_order_column_bill_content', 10, 2);

// =============================================
// AJAX: XEM BILL ĐƠN HÀNG (POPUP/PRINT)
// =============================================
function petshop_ajax_view_bill() {
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if (!$order_id || !current_user_can('manage_options')) {
        wp_die('Không có quyền truy cập');
    }
    
    $order_code = get_post_meta($order_id, 'order_code', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_phone = get_post_meta($order_id, 'customer_phone', true);
    $customer_email = get_post_meta($order_id, 'customer_email', true);
    $customer_address = get_post_meta($order_id, 'customer_address', true);
    $payment_method = get_post_meta($order_id, 'payment_method', true);
    $order_note = get_post_meta($order_id, 'order_note', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    $order_subtotal = get_post_meta($order_id, 'order_subtotal', true);
    $order_shipping = get_post_meta($order_id, 'order_final_shipping', true);
    $order_discount = get_post_meta($order_id, 'order_discount', true);
    $shipping_discount = get_post_meta($order_id, 'order_shipping_discount', true);
    $coupon_code = get_post_meta($order_id, 'coupon_code', true);
    $order_date = get_post_meta($order_id, 'order_date', true);
    $order_status = get_post_meta($order_id, 'order_status', true);
    $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
    
    $payment_labels = array(
        'cod' => 'COD - Thanh toán khi nhận hàng',
        'online' => 'Thanh toán Online',
        'vnpay' => 'VNPay',
        'bank' => 'Chuyển khoản'
    );
    $payment_text = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method;
    
    // Lấy thông tin shop từ settings
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? 'PetShop';
    $shop_phone = $shop_settings['shop_phone'] ?? '0123 456 789';
    $shop_email = $shop_settings['shop_email'] ?? 'support@petshop.com';
    $shop_address = $shop_settings['shop_address'] ?? '';
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Hóa đơn #<?php echo esc_html($order_code); ?> - <?php echo esc_html($shop_name); ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .bill-container { max-width: 800px; margin: 0 auto; background: #fff; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
            .bill-header { background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); color: #fff; padding: 30px; display: flex; justify-content: space-between; align-items: center; }
            .bill-header h1 { font-size: 28px; display: flex; align-items: center; gap: 10px; }
            .bill-header .bill-code { text-align: right; }
            .bill-header .bill-code h2 { font-size: 14px; opacity: 0.9; }
            .bill-header .bill-code p { font-size: 24px; font-weight: bold; }
            .bill-body { padding: 30px; }
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
            .info-box h3 { font-size: 14px; color: #666; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #eee; }
            .info-box p { margin: 8px 0; font-size: 14px; }
            .info-box strong { color: #333; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .items-table th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 13px; color: #666; border-bottom: 2px solid #eee; }
            .items-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
            .items-table .item-name { font-weight: 600; color: #333; }
            .items-table .item-price { text-align: right; }
            .summary-box { background: #f8f9fa; border-radius: 10px; padding: 20px; }
            .summary-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; }
            .summary-row.total { border-top: 2px solid #ddd; margin-top: 10px; padding-top: 15px; font-size: 20px; }
            .summary-row.total .value { color: #d63638; }
            .bill-footer { background: #5D4E37; color: #fff; padding: 20px; text-align: center; font-size: 13px; }
            .print-btn { position: fixed; top: 20px; right: 20px; background: #EC802B; color: #fff; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); }
            .print-btn:hover { background: #d97428; }
            @media print {
                body { background: #fff; padding: 0; }
                .bill-container { box-shadow: none; }
                .print-btn { display: none; }
            }
        </style>
    </head>
    <body>
        <button class="print-btn" onclick="window.print()">🖨️ In hóa đơn</button>
        
        <div class="bill-container">
            <div class="bill-header">
                <div>
                    <h1><i class="bi bi-shop"></i> <?php echo esc_html($shop_name); ?></h1>
                    <p style="opacity: 0.9; margin-top: 5px;">Hóa đơn bán hàng</p>
                </div>
                <div class="bill-code">
                    <h2>MÃ ĐƠN HÀNG</h2>
                    <p>#<?php echo esc_html($order_code); ?></p>
                </div>
            </div>
            
            <div class="bill-body">
                <div class="info-grid">
                    <div class="info-box">
                        <h3><i class="bi bi-geo-alt-fill" style="color: #EC802B;"></i> THÔNG TIN KHÁCH HÀNG</h3>
                        <p><strong>Họ tên:</strong> <?php echo esc_html($customer_name); ?></p>
                        <p><strong>Điện thoại:</strong> <?php echo esc_html($customer_phone); ?></p>
                        <p><strong>Email:</strong> <?php echo esc_html($customer_email); ?></p>
                        <p><strong>Địa chỉ:</strong> <?php echo esc_html($customer_address); ?></p>
                    </div>
                    <div class="info-box">
                        <h3><i class="bi bi-clipboard-check" style="color: #66BCB4;"></i> THÔNG TIN ĐƠN HÀNG</h3>
                        <p><strong>Ngày đặt:</strong> <?php echo $order_date ? date('d/m/Y H:i', strtotime($order_date)) : '-'; ?></p>
                        <p><strong>Thanh toán:</strong> <?php echo esc_html($payment_text); ?></p>
                        <?php if ($order_note) : ?>
                        <p><strong>Ghi chú:</strong> <?php echo esc_html($order_note); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>SL</th>
                            <th style="text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (is_array($cart_items)) : ?>
                            <?php foreach ($cart_items as $item) : 
                                $item_total = floatval($item['price']) * intval($item['quantity']);
                            ?>
                            <tr>
                                <td>
                                    <span class="item-name"><?php echo esc_html($item['name']); ?></span>
                                    <?php if (!empty($item['sku'])) : ?>
                                    <br><small style="color: #999;">SKU: <?php echo esc_html($item['sku']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                                <td><?php echo intval($item['quantity']); ?></td>
                                <td class="item-price"><strong><?php echo number_format($item_total, 0, ',', '.'); ?>đ</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="summary-box">
                    <?php if ($order_subtotal) : ?>
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span><?php echo number_format($order_subtotal, 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order_shipping) : ?>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span><?php echo number_format($order_shipping, 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    $total_discount = floatval($order_discount) + floatval($shipping_discount);
                    if ($total_discount > 0) : 
                    ?>
                    <div class="summary-row" style="color: #28a745;">
                        <span>Giảm giá<?php echo $coupon_code ? ' (' . esc_html($coupon_code) . ')' : ''; ?>:</span>
                        <span>-<?php echo number_format($total_discount, 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span><strong>TỔNG CỘNG:</strong></span>
                        <span class="value"><strong><?php echo number_format($order_total, 0, ',', '.'); ?>đ</strong></span>
                    </div>
                </div>
            </div>
            
            <div class="bill-footer">
                <p>Cảm ơn quý khách đã mua hàng tại <?php echo esc_html($shop_name); ?>!</p>
                <p style="margin-top: 5px; opacity: 0.8;">
                    <i class="bi bi-telephone-fill"></i> <?php echo esc_html($shop_phone); ?> | 
                    <i class="bi bi-envelope-fill"></i> <?php echo esc_html($shop_email); ?>
                </p>
                <?php if ($shop_address) : ?>
                <p style="margin-top: 5px; opacity: 0.7;"><i class="bi bi-geo-alt"></i> <?php echo esc_html($shop_address); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
add_action('wp_ajax_petshop_view_bill', 'petshop_ajax_view_bill');

// =============================================
// AJAX: GỬI LẠI EMAIL XÁC NHẬN ĐƠN HÀNG
// =============================================
function petshop_ajax_resend_order_email() {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id || !current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Không có quyền'));
    }
    
    $result = petshop_send_order_confirmation_email($order_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Đã gửi email thành công'));
    } else {
        wp_send_json_error(array('message' => 'Không thể gửi email. Kiểm tra cấu hình SMTP.'));
    }
}
add_action('wp_ajax_petshop_resend_order_email', 'petshop_ajax_resend_order_email');

// =============================================
// MENU: CÀI ĐẶT CỬA HÀNG / EMAIL
// =============================================
function petshop_shop_settings_menu() {
    add_submenu_page(
        'edit.php?post_type=petshop_order',
        'Cài đặt cửa hàng',
        'Cài đặt cửa hàng',
        'manage_options',
        'petshop-shop-settings',
        'petshop_shop_settings_page'
    );
}
add_action('admin_menu', 'petshop_shop_settings_menu', 20);

// =============================================
// TRANG CÀI ĐẶT CỬA HÀNG / EMAIL
// =============================================
function petshop_shop_settings_page() {
    // Lưu settings
    if (isset($_POST['petshop_shop_nonce']) && wp_verify_nonce($_POST['petshop_shop_nonce'], 'petshop_shop_settings')) {
        $settings = array(
            'shop_name' => sanitize_text_field($_POST['shop_name'] ?? 'PetShop'),
            'shop_phone' => sanitize_text_field($_POST['shop_phone'] ?? ''),
            'shop_email' => sanitize_email($_POST['shop_email'] ?? ''),
            'shop_address' => sanitize_textarea_field($_POST['shop_address'] ?? ''),
            'email_from_name' => sanitize_text_field($_POST['email_from_name'] ?? ''),
            'email_from_address' => sanitize_email($_POST['email_from_address'] ?? ''),
            'email_order_confirmed' => isset($_POST['email_order_confirmed']) ? 1 : 0,
            'email_order_processing' => isset($_POST['email_order_processing']) ? 1 : 0,
            'email_order_shipping' => isset($_POST['email_order_shipping']) ? 1 : 0,
            'email_order_completed' => isset($_POST['email_order_completed']) ? 1 : 0,
            'email_order_cancelled' => isset($_POST['email_order_cancelled']) ? 1 : 0,
        );
        update_option('petshop_shop_settings', $settings);
        echo '<div class="notice notice-success"><p><span class="dashicons dashicons-yes-alt"></span> Đã lưu cài đặt thành công!</p></div>';
    }
    
    $settings = get_option('petshop_shop_settings', array());
    $defaults = array(
        'shop_name' => 'PetShop',
        'shop_phone' => '0123 456 789',
        'shop_email' => get_option('admin_email'),
        'shop_address' => '',
        'email_from_name' => 'PetShop',
        'email_from_address' => get_option('admin_email'),
        'email_order_confirmed' => 1,
        'email_order_processing' => 1,
        'email_order_shipping' => 1,
        'email_order_completed' => 1,
        'email_order_cancelled' => 1,
    );
    $settings = wp_parse_args($settings, $defaults);
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-store" style="margin-right: 10px;"></span>Cài đặt cửa hàng</h1>
        
        <form method="post">
            <?php wp_nonce_field('petshop_shop_settings', 'petshop_shop_nonce'); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Thông tin cửa hàng -->
                <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                        <span class="dashicons dashicons-store" style="color: #EC802B;"></span> Thông tin cửa hàng
                    </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="shop_name">Tên cửa hàng</label></th>
                            <td>
                                <input type="text" name="shop_name" id="shop_name" value="<?php echo esc_attr($settings['shop_name']); ?>" class="regular-text">
                                <p class="description">Tên hiển thị trên bill, email</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="shop_phone">Số điện thoại</label></th>
                            <td>
                                <input type="text" name="shop_phone" id="shop_phone" value="<?php echo esc_attr($settings['shop_phone']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="shop_email">Email liên hệ</label></th>
                            <td>
                                <input type="email" name="shop_email" id="shop_email" value="<?php echo esc_attr($settings['shop_email']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="shop_address">Địa chỉ</label></th>
                            <td>
                                <textarea name="shop_address" id="shop_address" rows="3" class="large-text"><?php echo esc_textarea($settings['shop_address']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Cài đặt Email -->
                <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                        <span class="dashicons dashicons-email-alt" style="color: #66BCB4;"></span> Cài đặt Email
                    </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="email_from_name">Tên người gửi</label></th>
                            <td>
                                <input type="text" name="email_from_name" id="email_from_name" value="<?php echo esc_attr($settings['email_from_name']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="email_from_address">Email gửi đi</label></th>
                            <td>
                                <input type="email" name="email_from_address" id="email_from_address" value="<?php echo esc_attr($settings['email_from_address']); ?>" class="regular-text">
                                <p class="description">Email này sẽ hiển thị là người gửi</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3 style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                        <span class="dashicons dashicons-bell"></span> Thông báo tự động
                    </h3>
                    <p class="description">Chọn email tự động gửi cho khách khi:</p>
                    
                    <div style="margin-top: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                            <input type="checkbox" name="email_order_confirmed" value="1" <?php checked($settings['email_order_confirmed'], 1); ?>>
                            <span><strong>Đặt hàng thành công</strong> - Gửi email xác nhận + bill</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                            <input type="checkbox" name="email_order_processing" value="1" <?php checked($settings['email_order_processing'], 1); ?>>
                            <span><strong>Đang xử lý</strong> - Đơn hàng đang được chuẩn bị</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: #e6f7ff; border-radius: 5px; border-left: 3px solid #5bc0de;">
                            <input type="checkbox" name="email_order_shipping" value="1" <?php checked($settings['email_order_shipping'], 1); ?>>
                            <span><strong>Đang giao hàng</strong> - Thông báo đơn đang trên đường giao</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: #e6ffe6; border-radius: 5px; border-left: 3px solid #5cb85c;">
                            <input type="checkbox" name="email_order_completed" value="1" <?php checked($settings['email_order_completed'], 1); ?>>
                            <span><strong>Hoàn thành</strong> - Đơn hàng đã giao thành công</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: #ffe6e6; border-radius: 5px; border-left: 3px solid #d9534f;">
                            <input type="checkbox" name="email_order_cancelled" value="1" <?php checked($settings['email_order_cancelled'], 1); ?>>
                            <span><strong>Đã hủy</strong> - Thông báo đơn hàng bị hủy</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Test Email -->
            <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin-top: 20px; border-left: 4px solid #4CAF50;">
                <h3 style="margin: 0 0 10px;"><span class="dashicons dashicons-email"></span> Kiểm tra Email</h3>
                <p>Cấu hình SMTP tại menu <strong>Đơn hàng > Cài đặt SMTP</strong> để email hoạt động đúng.</p>
                <button type="button" onclick="testEmail()" class="button">
                    <span class="dashicons dashicons-email" style="vertical-align: middle;"></span> Gửi email test
                </button>
                <span id="testEmailResult" style="margin-left: 15px;"></span>
            </div>
            
            <p style="margin-top: 20px;">
                <input type="submit" class="button button-primary button-hero" value="Lưu cài đặt">
            </p>
        </form>
    </div>
    
    <script>
    function testEmail() {
        var result = document.getElementById('testEmailResult');
        result.innerHTML = '<span style="color: #666;">Đang gửi...</span>';
        
        jQuery.post(ajaxurl, {
            action: 'petshop_test_email'
        }, function(response) {
            if (response.success) {
                result.innerHTML = '<span style="color: #5cb85c;"><span class="dashicons dashicons-yes-alt"></span> Đã gửi email test đến ' + response.data.email + '</span>';
            } else {
                result.innerHTML = '<span style="color: #d9534f;"><span class="dashicons dashicons-dismiss"></span> ' + response.data.message + '</span>';
            }
        });
    }
    </script>
    <?php
}

// =============================================
// AJAX: TEST EMAIL
// =============================================
function petshop_ajax_test_email() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Không có quyền'));
    }
    
    $settings = get_option('petshop_shop_settings', array());
    $shop_name = $settings['shop_name'] ?? 'PetShop';
    $to = get_option('admin_email');
    
    $subject = '[' . $shop_name . '] Email test';
    $message = '
    <html>
    <body style="font-family: Arial, sans-serif; padding: 20px;">
        <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="background: linear-gradient(135deg, #66BCB4, #7ECEC6); padding: 25px; text-align: center; color: #fff;">
                <h2 style="margin: 0;">Email Test Thành Công!</h2>
            </div>
            <div style="padding: 25px;">
                <p>Chúc mừng! Hệ thống email của <strong>' . esc_html($shop_name) . '</strong> đang hoạt động bình thường.</p>
                <p>Các email thông báo đơn hàng sẽ được gửi tự động đến khách hàng.</p>
                <p style="color: #666; font-size: 13px; margin-top: 20px;">Thời gian: ' . current_time('d/m/Y H:i:s') . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $sent = wp_mail($to, $subject, $message, $headers);
    
    if ($sent) {
        wp_send_json_success(array('email' => $to));
    } else {
        wp_send_json_error(array('message' => 'Không thể gửi email. Kiểm tra cấu hình SMTP.'));
    }
}
add_action('wp_ajax_petshop_test_email', 'petshop_ajax_test_email');

// =============================================
// GỬI EMAIL KHI THAY ĐỔI TRẠNG THÁI
// =============================================
function petshop_send_order_status_email($order_id, $status) {
    $order_code = get_post_meta($order_id, 'order_code', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_email = get_post_meta($order_id, 'customer_email', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    
    if (empty($customer_email)) {
        return false;
    }
    
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? 'PetShop';
    $shop_phone = $shop_settings['shop_phone'] ?? '0123 456 789';
    
    // Cấu hình theo trạng thái
    $status_config = array(
        'processing' => array(
            'subject' => '[Đang xử lý] Đơn hàng #' . $order_code . ' đang được chuẩn bị',
            'icon' => '⚙️',
            'color' => '#5bc0de',
            'title' => 'Đơn hàng đang được xử lý',
            'message' => 'Đơn hàng của bạn đang được chuẩn bị và sẽ sớm được giao.',
        ),
        'shipping' => array(
            'subject' => '[Đang giao] Đơn hàng #' . $order_code . ' đang được giao đến bạn',
            'icon' => '✈️',
            'color' => '#17a2b8',
            'title' => 'Đơn hàng đang trên đường giao',
            'message' => 'Đơn hàng của bạn đã được giao cho đơn vị vận chuyển. Vui lòng giữ điện thoại để nhận hàng.',
        ),
        'completed' => array(
            'subject' => '[Hoàn thành] Đơn hàng #' . $order_code . ' đã giao thành công',
            'icon' => '✔️',
            'color' => '#28a745',
            'title' => 'Giao hàng thành công!',
            'message' => 'Đơn hàng của bạn đã được giao thành công. Cảm ơn bạn đã mua sắm tại ' . $shop_name . '!',
        ),
        'cancelled' => array(
            'subject' => '[Đã hủy] Đơn hàng #' . $order_code . ' đã bị hủy',
            'icon' => '✖️',
            'color' => '#dc3545',
            'title' => 'Đơn hàng đã bị hủy',
            'message' => 'Rất tiếc, đơn hàng của bạn đã bị hủy. Nếu có thắc mắc, vui lòng liên hệ với chúng tôi.',
        ),
    );
    
    if (!isset($status_config[$status])) {
        return false;
    }
    
    $config = $status_config[$status];
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff;">
            <div style="background: ' . $config['color'] . '; padding: 30px; text-align: center; color: #fff;">
                <span style="font-size: 3rem;">' . $config['icon'] . '</span>
                <h1 style="margin: 15px 0 0; font-size: 24px;">' . $config['title'] . '</h1>
            </div>
            
            <div style="padding: 30px;">
                <p style="font-size: 16px; color: #333;">Xin chào <strong>' . esc_html($customer_name) . '</strong>,</p>
                <p style="font-size: 16px; color: #555; line-height: 1.6;">' . $config['message'] . '</p>
                
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 25px 0;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 8px 0; color: #666;">Mã đơn hàng:</td>
                            <td style="padding: 8px 0; text-align: right;"><strong style="color: #EC802B;">#' . esc_html($order_code) . '</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666;">Tổng tiền:</td>
                            <td style="padding: 8px 0; text-align: right;"><strong style="color: #333;">' . number_format($order_total, 0, ',', '.') . 'đ</strong></td>
                        </tr>
                    </table>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . home_url('/tai-khoan/#orders') . '" style="display: inline-block; background: ' . $config['color'] . '; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Xem đơn hàng
                    </a>
                </div>
            </div>
            
            <div style="background: #5D4E37; padding: 20px; text-align: center; color: #fff; font-size: 13px;">
                <p style="margin: 0;">Cần hỗ trợ? Liên hệ: ' . esc_html($shop_phone) . '</p>
                <p style="margin: 10px 0 0; opacity: 0.7;">© ' . date('Y') . ' ' . esc_html($shop_name) . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($customer_email, $config['subject'], $message, $headers);
}