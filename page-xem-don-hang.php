<?php
/**
 * Template Name: Xem đơn hàng
 * Cho phép khách xem đơn hàng qua link trong email
 */

get_header();

// Lấy thông tin từ URL
$order_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
$customer_email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';

// Tìm đơn hàng
$order = null;
$error = '';

if ($order_code && $customer_email) {
    $orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => 1,
        'title' => $order_code,
        'post_status' => 'publish'
    ));
    
    if (!empty($orders)) {
        $found_order = $orders[0];
        $saved_email = get_post_meta($found_order->ID, 'customer_email', true);
        
        // Xác thực email
        if (strtolower($saved_email) === strtolower($customer_email)) {
            $order = $found_order;
        } else {
            $error = 'Email không khớp với đơn hàng.';
        }
    } else {
        $error = 'Không tìm thấy đơn hàng.';
    }
} elseif ($order_code || $customer_email) {
    $error = 'Vui lòng cung cấp đầy đủ mã đơn hàng và email.';
}

// Lấy thông tin shop
$shop_settings = get_option('petshop_shop_settings', array());
$shop_name = $shop_settings['shop_name'] ?? 'PetShop';
$shop_phone = $shop_settings['shop_phone'] ?? '0123 456 789';
$shop_email = $shop_settings['shop_email'] ?? 'support@petshop.com';
$shop_address = $shop_settings['shop_address'] ?? '';
?>

<style>
    .order-view-section { padding: 60px 0; background: #f5f5f5; min-height: 70vh; }
    .order-container { max-width: 900px; margin: 0 auto; padding: 0 15px; }
    
    .order-lookup-form { background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 30px; }
    .order-lookup-form h2 { margin: 0 0 25px; color: #5D4E37; }
    .order-lookup-form .form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end; }
    .order-lookup-form .form-group { }
    .order-lookup-form label { display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37; }
    .order-lookup-form input { width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; }
    .order-lookup-form button { background: #66BCB4; color: #fff; border: none; padding: 12px 30px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; height: 48px; }
    .order-lookup-form button:hover { background: #5aa9a2; }
    
    .order-error { background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
    
    .bill-container { background: #fff; box-shadow: 0 2px 20px rgba(0,0,0,0.1); border-radius: 15px; overflow: hidden; }
    .bill-header { background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); color: #fff; padding: 30px; display: flex; justify-content: space-between; align-items: center; }
    .bill-header h1 { font-size: 28px; margin: 0; display: flex; align-items: center; gap: 10px; }
    .bill-header .bill-code { text-align: right; }
    .bill-header .bill-code h2 { font-size: 12px; opacity: 0.9; margin: 0; font-weight: normal; }
    .bill-header .bill-code p { font-size: 24px; font-weight: bold; margin: 5px 0 0; }
    
    .bill-status { padding: 15px 30px; display: flex; align-items: center; gap: 10px; font-weight: 600; }
    .bill-status.pending { background: #fff3cd; color: #856404; }
    .bill-status.processing { background: #cce5ff; color: #004085; }
    .bill-status.shipping { background: #d1ecf1; color: #0c5460; }
    .bill-status.completed { background: #d4edda; color: #155724; }
    .bill-status.cancelled { background: #f8d7da; color: #721c24; }
    
    .bill-body { padding: 30px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
    .info-box h3 { font-size: 13px; color: #666; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #eee; text-transform: uppercase; }
    .info-box p { margin: 8px 0; font-size: 14px; color: #333; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .items-table th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 13px; color: #666; border-bottom: 2px solid #eee; }
    .items-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
    .items-table .item-image { width: 60px; }
    .items-table .item-image img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
    .items-table .item-price { text-align: right; font-weight: 600; }
    
    .summary-box { background: #f8f9fa; border-radius: 10px; padding: 20px; }
    .summary-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; }
    .summary-row.discount { color: #28a745; }
    .summary-row.total { border-top: 2px solid #ddd; margin-top: 10px; padding-top: 15px; font-size: 20px; }
    .summary-row.total .value { color: #d63638; }
    
    .bill-footer { background: #5D4E37; color: #fff; padding: 20px; text-align: center; font-size: 13px; }
    
    .print-btn { background: #EC802B; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; margin-top: 20px; }
    .print-btn:hover { background: #d97428; }
    
    @media print {
        .order-lookup-form, .print-btn, header, footer, .site-header, .site-footer { display: none !important; }
        .order-view-section { padding: 0; background: #fff; }
        .bill-container { box-shadow: none; }
    }
    
    @media (max-width: 768px) {
        .order-lookup-form .form-row { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; }
        .bill-header { flex-direction: column; text-align: center; gap: 20px; }
        .bill-header .bill-code { text-align: center; }
    }
</style>

<section class="order-view-section">
    <div class="order-container">
        
        <!-- Form tra cứu -->
        <div class="order-lookup-form">
            <h2>Tra cứu đơn hàng</h2>
            <form method="get" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Mã đơn hàng</label>
                        <input type="text" name="code" id="code" value="<?php echo esc_attr($order_code); ?>" placeholder="VD: PET20260204XXXX" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email đặt hàng</label>
                        <input type="email" name="email" id="email" value="<?php echo esc_attr($customer_email); ?>" placeholder="email@example.com" required>
                    </div>
                    <button type="submit">Tra cứu</button>
                </div>
            </form>
        </div>
        
        <?php if ($error) : ?>
            <div class="order-error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        
        <?php if ($order) : 
            $order_id = $order->ID;
            $customer_name = get_post_meta($order_id, 'customer_name', true);
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
            $order_status = get_post_meta($order_id, 'order_status', true);
            $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
            
            $payment_labels = array(
                'cod' => 'COD - Thanh toán khi nhận hàng',
                'online' => 'Thanh toán Online',
                'vnpay' => 'VNPay',
                'bank' => 'Chuyển khoản'
            );
            $payment_text = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method;
            
            $status_labels = array(
                'pending' => 'Chờ xác nhận',
                'processing' => 'Đang xử lý',
                'shipping' => 'Đang giao hàng',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy'
            );
            $status_text = isset($status_labels[$order_status]) ? $status_labels[$order_status] : $order_status;
        ?>
        
        <div class="bill-container">
            <div class="bill-header">
                <div>
                    <h1><?php echo esc_html($shop_name); ?></h1>
                    <p style="opacity: 0.9; margin-top: 5px;">Hóa đơn bán hàng</p>
                </div>
                <div class="bill-code">
                    <h2>MÃ ĐƠN HÀNG</h2>
                    <p>#<?php echo esc_html($order_code); ?></p>
                </div>
            </div>
            
            <div class="bill-status <?php echo esc_attr($order_status); ?>">
                Trạng thái: <?php echo esc_html($status_text); ?>
            </div>
            
            <div class="bill-body">
                <div class="info-grid">
                    <div class="info-box">
                        <h3>Thông tin khách hàng</h3>
                        <p><strong>Họ tên:</strong> <?php echo esc_html($customer_name); ?></p>
                        <p><strong>Điện thoại:</strong> <?php echo esc_html($customer_phone); ?></p>
                        <p><strong>Email:</strong> <?php echo esc_html($customer_email); ?></p>
                        <p><strong>Địa chỉ:</strong> <?php echo esc_html($customer_address); ?></p>
                    </div>
                    <div class="info-box">
                        <h3>Thông tin đơn hàng</h3>
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
                            <th style="width: 60px;"></th>
                            <th>Sản phẩm</th>
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
                                <td class="item-image">
                                    <?php if (!empty($item['image'])) : ?>
                                        <img src="<?php echo esc_url($item['image']); ?>" alt="">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($item['name']); ?></strong></td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                                <td><?php echo intval($item['quantity']); ?></td>
                                <td class="item-price"><?php echo number_format($item_total, 0, ',', '.'); ?>đ</td>
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
                    <div class="summary-row discount">
                        <span>Giảm giá<?php echo $coupon_code ? ' (' . esc_html($coupon_code) . ')' : ''; ?>:</span>
                        <span>-<?php echo number_format($total_discount, 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span><strong>TỔNG CỘNG:</strong></span>
                        <span class="value"><strong><?php echo number_format($order_total, 0, ',', '.'); ?>đ</strong></span>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button class="print-btn" onclick="window.print()">In hóa đơn</button>
                </div>
            </div>
            
            <div class="bill-footer">
                <p>Cảm ơn quý khách đã mua hàng tại <?php echo esc_html($shop_name); ?>!</p>
                <p style="margin-top: 5px; opacity: 0.8;">
                    Tel: <?php echo esc_html($shop_phone); ?> | Email: <?php echo esc_html($shop_email); ?>
                </p>
                <?php if ($shop_address) : ?>
                <p style="margin-top: 5px; opacity: 0.7;"><?php echo esc_html($shop_address); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
        
    </div>
</section>

<?php get_footer(); ?>

<?php
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
include_once get_template_directory() . '/inc/admin-sidebar-menu.php';
if (in_array('administrator', $user_roles) || in_array('petshop_manager', $user_roles) || in_array('petshop_staff', $user_roles)) {
    petshop_render_admin_sidebar_menu($user_roles);
    echo '<div style="margin-left:240px;padding:32px 24px;">';
}
?>
