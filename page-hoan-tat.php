<?php
/**
 * Template Name: Trang Hoàn Tất Đơn Hàng
 * 
 * @package PetShop
 */

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = $order_id ? get_post($order_id) : null;

// Xác định có phải đang chờ thanh toán VietQR không
$payment_pending = isset($_GET['payment']) && $_GET['payment'] === 'pending';

// Nếu có order_id, lấy thông tin từ database
if ($order && $order->post_type === 'petshop_order') {
    $order_code = get_post_meta($order_id, 'order_code', true);
    $customer_name = get_post_meta($order_id, 'customer_name', true);
    $customer_phone = get_post_meta($order_id, 'customer_phone', true);
    $customer_email = get_post_meta($order_id, 'customer_email', true);
    $customer_address = get_post_meta($order_id, 'customer_address', true);
    $payment_method = get_post_meta($order_id, 'payment_method', true);
    $order_total = get_post_meta($order_id, 'order_total', true);
    $order_status = get_post_meta($order_id, 'order_status', true);
    $order_date = get_post_meta($order_id, 'order_date', true);
    $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
    
    // Lấy thông tin VietQR nếu cần
    $vietqr_url = '';
    $payment_settings = petshop_get_payment_settings();
    if ($payment_pending && $payment_method === 'online' && $payment_settings['transfer_mode'] === 'vietqr') {
        $vietqr_url = petshop_generate_vietqr_url($order_total, $order_code);
        $qr_expire_minutes = $payment_settings['qr_expire_minutes'] ?? 15;
        
        // Lấy tên ngân hàng từ bank_id
        $banks = petshop_get_vietnam_banks();
        $bank_id = $payment_settings['bank_id'];
        $bank_name = isset($banks[$bank_id]) ? $banks[$bank_id] : $bank_id;
    }
    
    $payment_labels = array(
        'cod' => 'COD - Thanh toán khi nhận hàng',
        'bank' => 'Chuyển khoản ngân hàng',
        'momo' => 'Ví MoMo',
        'vnpay' => 'VNPay / Thẻ ATM',
        'online' => 'Thanh toán Online'
    );
    
    $status_labels = array(
        'pending' => array('label' => 'Chờ thanh toán', 'color' => '#f0ad4e'),
        'processing' => array('label' => 'Đang xử lý', 'color' => '#5bc0de'),
        'completed' => array('label' => 'Hoàn thành', 'color' => '#5cb85c'),
        'cancelled' => array('label' => 'Đã hủy', 'color' => '#d9534f'),
    );
}

get_header(); ?>

<!-- Page Header -->
<div class="page-header" style="background: linear-gradient(135deg, <?php echo $payment_pending ? '#EC802B 0%, #F5994D' : '#66BCB4 0%, #7ECEC6'; ?> 100%);">
    <div class="container">
        <h1 style="color: #fff;">
            <i class="bi bi-<?php echo $payment_pending ? 'qr-code' : 'check-circle'; ?>"></i> 
            <?php echo $payment_pending ? 'Chờ Thanh Toán' : 'Đặt Hàng Thành Công'; ?>
        </h1>
        <p style="color: rgba(255,255,255,0.9);">
            <?php echo $payment_pending ? 'Vui lòng chuyển khoản để hoàn tất đơn hàng' : 'Cảm ơn bạn đã tin tưởng PetShop!'; ?>
        </p>
    </div>
</div>

<section class="success-section" style="padding: 60px 0;">
    <div class="container">
        <!-- Cart Progress -->
        <div class="cart-progress" style="display: flex; justify-content: center; align-items: center; margin: 30px 0 50px; gap: 10px;">
            <div class="progress-step completed" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #66BCB4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="color: #66BCB4;">Giỏ hàng</span>
            </div>
            <div style="width: 80px; height: 3px; background: #66BCB4;"></div>
            <div class="progress-step completed" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #66BCB4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="color: #66BCB4;">Thanh toán</span>
            </div>
            <div style="width: 80px; height: 3px; background: #66BCB4;"></div>
            <div class="progress-step active" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="font-weight: 600; color: #66BCB4;">Hoàn tất</span>
            </div>
        </div>
        
        <!-- Success Content -->
        <div style="max-width: 700px; margin: 0 auto; text-align: center;">
            
            <?php if ($payment_pending && !empty($vietqr_url)) : ?>
            <!-- VietQR Payment Section -->
            <div style="background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 25px; padding: 35px; margin-bottom: 30px; color: #fff;">
                <div style="background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 25px;">
                    <img src="<?php echo esc_url($vietqr_url); ?>" alt="QR Code thanh toán" 
                         style="max-width: 280px; width: 100%; height: auto; border-radius: 10px;">
                </div>
                
                <h3 style="margin-bottom: 15px; color: #fff;">Quét mã QR để thanh toán</h3>
                
                <div style="background: rgba(255,255,255,0.2); border-radius: 15px; padding: 20px; text-align: left; margin-bottom: 20px;">
                    <p style="margin: 0 0 10px; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.9;">Ngân hàng:</span>
                        <strong><?php echo esc_html($bank_name); ?></strong>
                    </p>
                    <p style="margin: 0 0 10px; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.9;">Số tài khoản:</span>
                        <strong><?php echo esc_html($payment_settings['bank_account']); ?></strong>
                    </p>
                    <p style="margin: 0 0 10px; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.9;">Chủ tài khoản:</span>
                        <strong><?php echo esc_html($payment_settings['bank_holder']); ?></strong>
                    </p>
                    <p style="margin: 0 0 10px; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.9;">Số tiền:</span>
                        <strong style="font-size: 1.2rem;"><?php echo number_format($order_total, 0, ',', '.'); ?>đ</strong>
                    </p>
                    <p style="margin: 0; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.9;">Nội dung CK:</span>
                        <strong><?php echo esc_html($order_code); ?></strong>
                    </p>
                </div>
                
                <!-- Countdown Timer -->
                <div style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px; opacity: 0.9;"><i class="bi bi-clock"></i> Thời gian còn lại:</p>
                    <div id="qrCountdownHoanTat" style="font-size: 2rem; font-weight: 700; font-family: monospace;">
                        <?php echo str_pad($qr_expire_minutes, 2, '0', STR_PAD_LEFT); ?>:00
                    </div>
                </div>
                
                <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">
                    <i class="bi bi-info-circle"></i> Sau khi chuyển khoản, đơn hàng sẽ được xác nhận trong vài phút
                </p>
            </div>
            
            <script>
            (function() {
                var countdownEl = document.getElementById('qrCountdownHoanTat');
                var totalSeconds = <?php echo intval($qr_expire_minutes) * 60; ?>;
                
                var timer = setInterval(function() {
                    totalSeconds--;
                    
                    if (totalSeconds <= 0) {
                        clearInterval(timer);
                        countdownEl.innerHTML = '<span style="color: #ff6b6b;">Hết hạn</span>';
                        return;
                    }
                    
                    var minutes = Math.floor(totalSeconds / 60);
                    var seconds = totalSeconds % 60;
                    countdownEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                    
                    if (totalSeconds <= 60) {
                        countdownEl.style.color = '#ff6b6b';
                    }
                }, 1000);
            })();
            </script>
            
            <?php else : ?>
            <!-- Normal Success Icon -->
            <div class="success-icon" style="width: 120px; height: 120px; background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); border-radius: 50%; margin: 0 auto 30px; display: flex; align-items: center; justify-content: center; animation: scaleIn 0.5s ease;">
                <i class="bi bi-check-lg" style="font-size: 4rem; color: #fff;"></i>
            </div>
            
            <h2 style="color: #66BCB4; margin-bottom: 15px;">Đơn hàng đã được đặt thành công!</h2>
            <p style="color: #7A6B5A; font-size: 1.1rem; margin-bottom: 30px;">Chúng tôi đã nhận được đơn hàng của bạn và sẽ xử lý trong thời gian sớm nhất.</p>
            <?php endif; ?>
            
            <?php if ($order) : ?>
            <!-- Order Info Box - Real Data from Database -->
            <div style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); text-align: left; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #FDF8F3; margin-bottom: 20px;">
                    <div>
                        <span style="color: #7A6B5A; font-size: 0.9rem;">Mã đơn hàng</span>
                        <h3 style="color: #EC802B; margin: 5px 0 0;">#<?php echo esc_html($order_code); ?></h3>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: #7A6B5A; font-size: 0.9rem;">Ngày đặt</span>
                        <h4 style="margin: 5px 0 0;"><?php echo $order_date ? date('d/m/Y H:i', strtotime($order_date)) : date('d/m/Y H:i'); ?></h4>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div>
                        <h4 style="margin-bottom: 10px;"><i class="bi bi-geo-alt" style="color: #EC802B;"></i> Địa chỉ giao hàng</h4>
                        <p style="color: #5D4E37; line-height: 1.7; margin: 0;">
                            <?php echo esc_html($customer_name); ?><br>
                            <?php echo esc_html($customer_phone); ?><br>
                            <?php echo esc_html($customer_address); ?>
                        </p>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 10px;"><i class="bi bi-wallet2" style="color: #EC802B;"></i> Thanh toán</h4>
                        <p style="color: #5D4E37; margin: 0 0 10px;">
                            <strong>Phương thức:</strong> <?php echo isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method; ?><br>
                            <strong>Trạng thái:</strong> 
                            <span style="color: <?php echo isset($status_labels[$order_status]) ? $status_labels[$order_status]['color'] : '#f0ad4e'; ?>;">
                                <?php echo isset($status_labels[$order_status]) ? $status_labels[$order_status]['label'] : 'Chờ xử lý'; ?>
                            </span>
                        </p>
                        <p style="font-size: 1.3rem; font-weight: 700; color: #EC802B; margin: 0;"><?php echo number_format($order_total, 0, ',', '.'); ?>đ</p>
                    </div>
                </div>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #FDF8F3;">
                    <h4 style="margin-bottom: 15px;"><i class="bi bi-box-seam" style="color: #EC802B;"></i> Sản phẩm đã đặt</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (is_array($cart_items)) : ?>
                            <?php foreach ($cart_items as $item) : ?>
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;color:#5D4E37;gap:10px;">
                                <span>
                                    <?php echo esc_html($item['name']); ?>
                                    <?php if (!empty($item['variantLabel'])) : ?>
                                    <span style="display:inline-block;background:#FDF8F3;color:#EC802B;padding:1px 8px;border-radius:10px;font-size:0.78rem;font-weight:600;border:1px solid #E8CCAD;margin-left:4px;">
                                        <i class="bi bi-tag"></i> <?php echo esc_html($item['variantLabel']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <span style="color:#7A6B5A;"> x<?php echo intval($item['quantity']); ?></span>
                                </span>
                                <span style="font-weight:600;white-space:nowrap;"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            

            
            <?php else : ?>
            <!-- Fallback: No order data -->
            <div id="orderInfoBox" style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); text-align: center; margin-bottom: 30px;">
                <i class="bi bi-check-circle" style="font-size: 3rem; color: #66BCB4;"></i>
                <p style="color: #7A6B5A; margin-top: 15px;">Đơn hàng của bạn đã được ghi nhận.</p>
            </div>
            <?php endif; ?>
            
            <!-- Estimated Delivery -->
            <div style="background: linear-gradient(135deg, #FDF8F3 0%, #fff 100%); border-radius: 20px; padding: 25px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                    <i class="bi bi-truck" style="font-size: 2rem; color: #66BCB4;"></i>
                    <div style="text-align: left;">
                        <span style="color: #7A6B5A; font-size: 0.9rem;">Dự kiến giao hàng</span>
                        <h4 style="margin: 0; color: #5D4E37;"><?php echo date('d/m/Y', strtotime('+2 days')); ?> - <?php echo date('d/m/Y', strtotime('+4 days')); ?></h4>
                    </div>
                </div>
            </div>
            
            <!-- What's Next -->
            <div style="background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08);">
                <h4 style="margin-bottom: 20px;"><i class="bi bi-info-circle" style="color: #EC802B;"></i> Tiếp theo</h4>
                <div style="display: flex; justify-content: center; gap: 40px; text-align: center; flex-wrap: wrap;">
                    <div>
                        <div style="width: 50px; height: 50px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-envelope-check" style="font-size: 1.3rem; color: #EC802B;"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: #5D4E37; margin: 0;">Kiểm tra email<br>xác nhận đơn hàng</p>
                    </div>
                    <div>
                        <div style="width: 50px; height: 50px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-telephone" style="font-size: 1.3rem; color: #66BCB4;"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: #5D4E37; margin: 0;">Chờ nhân viên<br>gọi xác nhận</p>
                    </div>
                    <div>
                        <div style="width: 50px; height: 50px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-house-check" style="font-size: 1.3rem; color: #EDC55B;"></i>
                        </div>
                        <p style="font-size: 0.9rem; color: #5D4E37; margin: 0;">Nhận hàng và<br>thanh toán</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo home_url('/tai-khoan/#orders'); ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;text-decoration:none;border-radius:14px;font-weight:700;font-size:0.95rem;box-shadow:0 4px 15px rgba(236,128,43,0.35);transition:all .2s;"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(236,128,43,0.45)';"
                   onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(236,128,43,0.35)';">
                    <i class="bi bi-bag-check-fill"></i> Các đơn hàng đã mua
                </a>
                <?php endif; ?>
                <a href="<?php echo home_url('/san-pham/'); ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:#fff;color:#EC802B;text-decoration:none;border-radius:14px;font-weight:700;font-size:0.95rem;border:2px solid #E8CCAD;transition:all .2s;"
                   onmouseover="this.style.borderColor='#EC802B';"
                   onmouseout="this.style.borderColor='#E8CCAD';">
                    <i class="bi bi-shop"></i> Tiếp tục mua sắm
                </a>
                <a href="<?php echo home_url('/'); ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:14px 24px;background:#FDF8F3;color:#5D4E37;text-decoration:none;border-radius:14px;font-weight:600;font-size:0.95rem;border:2px solid #E8CCAD;transition:all .2s;"
                   onmouseover="this.style.background='#F5EDE0';"
                   onmouseout="this.style.background='#FDF8F3';">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
            </div>
            
            <!-- Contact Support -->
            <div style="margin-top: 40px; padding: 25px; background: linear-gradient(135deg, #5D4E37 0%, #7A6B5A 100%); border-radius: 20px; color: #fff;">
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 15px;">Có thắc mắc về đơn hàng? Liên hệ ngay:</p>
                <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                    <a href="tel:0123456789" style="color: #fff; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-telephone"></i> 0123 456 789
                    </a>
                    <a href="mailto:support@petshop.com" style="color: #fff; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-envelope"></i> support@petshop.com
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes scaleIn {
    from { transform: scale(0); }
    to { transform: scale(1); }
}
@media (max-width: 768px) {
    .cart-progress {
        flex-wrap: wrap;
    }
}
</style>

<?php get_footer(); ?>