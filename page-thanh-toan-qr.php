<?php
/**
 * Template Name: Trang Thanh Toán QR
 * 
 * Trang hiển thị mã QR để thanh toán với đồng hồ đếm ngược
 * 
 * @package PetShop
 */

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = $order_id ? get_post($order_id) : null;

// Kiểm tra order hợp lệ
if (!$order || $order->post_type !== 'petshop_order') {
    wp_redirect(home_url('/thanh-toan/'));
    exit;
}

// Lấy thông tin đơn hàng
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

// Lấy cài đặt thanh toán
$payment_settings = petshop_get_payment_settings();
$transfer_mode = $payment_settings['transfer_mode'] ?? 'demo';
$qr_expire_minutes = intval($payment_settings['qr_expire_minutes'] ?? 15);

// Lấy thông tin ngân hàng
$banks = petshop_get_vietnam_banks();
$bank_id = $payment_settings['bank_id'] ?? '';
$bank_name = isset($banks[$bank_id]) ? $banks[$bank_id]['name'] : $bank_id;
$bank_account = $payment_settings['bank_account'] ?? '';
$bank_holder = $payment_settings['bank_holder'] ?? '';

// Tạo URL QR Code
$qr_description = 'DH' . $order_code;
$vietqr_url = petshop_generate_vietqr_url($order_total, $order_code, $qr_description);

// Nếu đơn hàng đã thanh toán, chuyển sang trang hoàn tất
if ($order_status === 'processing' || $order_status === 'completed') {
    wp_redirect(home_url('/hoan-tat/?order_id=' . $order_id));
    exit;
}

get_header(); 
?>

<!-- Page Header -->
<div class="page-header" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
    <div class="container">
        <h1 style="color: #fff;"><i class="bi bi-qr-code-scan"></i> Thanh Toán Chuyển Khoản</h1>
        <p style="color: rgba(255,255,255,0.9);">Quét mã QR để hoàn tất thanh toán</p>
    </div>
</div>

<section class="qr-payment-section" style="padding: 40px 0 60px;">
    <div class="container">
        
        <!-- Progress Steps -->
        <div class="cart-progress" style="display: flex; justify-content: center; align-items: center; margin: 0 0 40px; gap: 10px;">
            <div class="progress-step completed" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #66BCB4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="color: #66BCB4;">Giỏ hàng</span>
            </div>
            <div style="width: 60px; height: 3px; background: #66BCB4;"></div>
            <div class="progress-step completed" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #66BCB4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="color: #66BCB4;">Đặt hàng</span>
            </div>
            <div style="width: 60px; height: 3px; background: linear-gradient(90deg, #66BCB4 50%, #EC802B 50%);"></div>
            <div class="progress-step active" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-qr-code"></i>
                </div>
                <span style="font-weight: 600; color: #EC802B;">Thanh toán</span>
            </div>
            <div style="width: 60px; height: 3px; background: #E8CCAD;"></div>
            <div class="progress-step" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #E8CCAD; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #7A6B5A;">4</div>
                <span style="color: #7A6B5A;">Hoàn tất</span>
            </div>
        </div>
        
        <!-- Main Content: QR Code + Order Info -->
        <div class="qr-payment-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1000px; margin: 0 auto;">
            
            <!-- Left: QR Code Section -->
            <div class="qr-code-section" style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                
                <?php if ($transfer_mode === 'demo') : ?>
                <!-- Demo Mode Badge -->
                <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 10px 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                    <i class="bi bi-mortarboard-fill"></i> <strong>CHẾ ĐỘ DEMO</strong> - Tự động thành công sau <span id="demoCountdown">5</span>s
                </div>
                <?php endif; ?>
                
                <h3 style="text-align: center; margin-bottom: 25px; color: #059669;">
                    <i class="bi bi-qr-code-scan"></i> Quét mã để thanh toán
                </h3>
                
                <!-- QR Code Display -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 20px; border-radius: 20px; display: inline-block;">
                        <div style="background: #fff; padding: 15px; border-radius: 12px;">
                            <img id="qrCodeImage" src="<?php echo esc_url($vietqr_url); ?>" 
                                 alt="QR Code thanh toán" 
                                 style="width: 220px; height: 220px; display: block;">
                        </div>
                    </div>
                </div>
                
                <!-- Countdown Timer -->
                <div id="qrCountdownBox" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px;">
                        <i class="bi bi-clock-history" style="font-size: 1.5rem; color: #dc2626;"></i>
                        <span style="color: #991b1b; font-weight: 600;">Mã QR hết hạn sau:</span>
                    </div>
                    <div id="countdownTimer" style="font-size: 2.5rem; font-weight: 700; color: #dc2626; font-family: 'Courier New', monospace;">
                        <?php echo str_pad($qr_expire_minutes, 2, '0', STR_PAD_LEFT); ?>:00
                    </div>
                </div>
                
                <!-- Bank Info -->
                <div style="background: #f8fafc; border-radius: 15px; padding: 20px;">
                    <h4 style="margin: 0 0 15px; color: #334155; font-size: 0.95rem;">
                        <i class="bi bi-bank2" style="color: #059669;"></i> Thông tin chuyển khoản
                    </h4>
                    <div style="display: grid; gap: 12px; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b;">Ngân hàng:</span>
                            <strong style="color: #059669;"><?php echo esc_html($bank_name); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b;">Số tài khoản:</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong style="color: #1e293b; letter-spacing: 1px;" id="bankAccountNum"><?php echo esc_html($bank_account); ?></strong>
                                <button type="button" onclick="copyToClipboard('<?php echo esc_js($bank_account); ?>', this)" 
                                        style="background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 5px; cursor: pointer; font-size: 0.75rem;">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b;">Chủ tài khoản:</span>
                            <strong style="color: #1e293b;"><?php echo esc_html($bank_holder); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b;">Số tiền:</span>
                            <strong style="color: #dc2626; font-size: 1.1rem;"><?php echo number_format($order_total, 0, ',', '.'); ?>₫</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b;">Nội dung CK:</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong style="color: #059669;" id="transferContent"><?php echo esc_html($qr_description); ?></strong>
                                <button type="button" onclick="copyToClipboard('<?php echo esc_js($qr_description); ?>', this)" 
                                        style="background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 5px; cursor: pointer; font-size: 0.75rem;">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div style="margin-top: 20px; padding: 15px; background: #fffbeb; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <p style="margin: 0; font-size: 0.85rem; color: #92400e;">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Hướng dẫn:</strong> Mở app ngân hàng → Quét mã QR → Kiểm tra thông tin → Xác nhận chuyển khoản
                    </p>
                </div>
            </div>
            
            <!-- Right: Order Summary -->
            <div class="order-summary-section" style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                
                <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: #5D4E37;">
                    <i class="bi bi-receipt" style="color: #EC802B;"></i> Đơn hàng #<?php echo esc_html($order_code); ?>
                </h3>
                
                <!-- Order Status -->
                <div id="orderStatusBox" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 15px; padding: 20px; margin-bottom: 25px; text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                        <div class="status-spinner" style="width: 24px; height: 24px; border: 3px solid #f59e0b; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <span style="color: #92400e; font-weight: 600; font-size: 1.1rem;">Đang chờ thanh toán...</span>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div style="margin-bottom: 25px;">
                    <h4 style="margin-bottom: 15px; color: #5D4E37; font-size: 0.95rem;">
                        <i class="bi bi-box-seam" style="color: #66BCB4;"></i> Sản phẩm
                    </h4>
                    <div style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
                        <?php if (is_array($cart_items)) : ?>
                            <?php foreach ($cart_items as $item) : ?>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #FDF8F3; border-radius: 10px; margin-bottom: 10px;">
                                <?php if (!empty($item['image'])) : ?>
                                <img src="<?php echo esc_url($item['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <?php endif; ?>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="margin: 0 0 4px; font-weight: 600; color: #5D4E37; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo esc_html($item['name']); ?>
                                    </p>
                                    <p style="margin: 0; color: #7A6B5A; font-size: 0.8rem;">
                                        SL: <?php echo intval($item['quantity']); ?> x <?php echo number_format($item['price'], 0, ',', '.'); ?>₫
                                    </p>
                                </div>
                                <strong style="color: #EC802B; white-space: nowrap;">
                                    <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>₫
                                </strong>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Total -->
                <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 15px; padding: 20px; color: #fff; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.1rem;">Tổng thanh toán:</span>
                        <strong style="font-size: 1.8rem;"><?php echo number_format($order_total, 0, ',', '.'); ?>₫</strong>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px; color: #5D4E37; font-size: 0.95rem;">
                        <i class="bi bi-person" style="color: #EC802B;"></i> Thông tin nhận hàng
                    </h4>
                    <div style="font-size: 0.9rem; color: #5D4E37; line-height: 1.8;">
                        <p style="margin: 0;"><strong><?php echo esc_html($customer_name); ?></strong></p>
                        <p style="margin: 0;"><i class="bi bi-telephone" style="color: #7A6B5A;"></i> <?php echo esc_html($customer_phone); ?></p>
                        <p style="margin: 0;"><i class="bi bi-geo-alt" style="color: #7A6B5A;"></i> <?php echo esc_html($customer_address); ?></p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <button type="button" id="btnCheckPayment" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem;">
                        <i class="bi bi-check-circle"></i> Tôi đã chuyển khoản
                    </button>
                    <a href="<?php echo home_url('/thanh-toan/'); ?>" class="btn btn-outline" style="width: 100%; padding: 15px; font-size: 1rem; text-align: center;">
                        <i class="bi bi-arrow-left"></i> Hủy và quay lại
                    </a>
                </div>
                
                <!-- Help Text -->
                <p style="margin: 20px 0 0; text-align: center; font-size: 0.85rem; color: #7A6B5A;">
                    <i class="bi bi-question-circle"></i> 
                    Cần hỗ trợ? Gọi <a href="tel:0123456789" style="color: #EC802B; font-weight: 600;">0123 456 789</a>
                </p>
            </div>
        </div>
        
    </div>
</section>

<!-- Success Modal -->
<div id="successModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 25px; padding: 50px; max-width: 450px; width: 90%; text-align: center; animation: scaleIn 0.3s ease;">
        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
            <i class="bi bi-check-lg" style="font-size: 3rem; color: #fff;"></i>
        </div>
        <h2 style="color: #059669; margin-bottom: 15px;">Thanh toán thành công!</h2>
        <p style="color: #666; margin-bottom: 25px;">Đơn hàng #<?php echo esc_html($order_code); ?> đã được xác nhận.</p>
        <p style="color: #999; font-size: 0.9rem;">Đang chuyển hướng...</p>
    </div>
</div>

<!-- Expired Modal -->
<div id="expiredModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 25px; padding: 50px; max-width: 450px; width: 90%; text-align: center;">
        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
            <i class="bi bi-x-lg" style="font-size: 3rem; color: #fff;"></i>
        </div>
        <h2 style="color: #dc2626; margin-bottom: 15px;">Mã QR đã hết hạn!</h2>
        <p style="color: #666; margin-bottom: 25px;">Vui lòng tạo đơn hàng mới để tiếp tục thanh toán.</p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button type="button" onclick="refreshQRCode()" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i> Tạo mã mới
            </button>
            <a href="<?php echo home_url('/thanh-toan/'); ?>" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
@keyframes scaleIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.qr-payment-container {
    max-width: 1000px;
}
@media (max-width: 768px) {
    .qr-payment-container {
        grid-template-columns: 1fr !important;
    }
    .cart-progress {
        flex-wrap: wrap;
        gap: 5px !important;
    }
    .cart-progress > div:nth-child(even) {
        width: 30px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderId = <?php echo intval($order_id); ?>;
    const orderCode = '<?php echo esc_js($order_code); ?>';
    const transferMode = '<?php echo esc_js($transfer_mode); ?>';
    const expireMinutes = <?php echo intval($qr_expire_minutes); ?>;
    
    let countdownSeconds = expireMinutes * 60;
    let countdownInterval = null;
    let checkPaymentInterval = null;
    
    // =============================================
    // COUNTDOWN TIMER
    // =============================================
    function updateCountdown() {
        const minutes = Math.floor(countdownSeconds / 60);
        const seconds = countdownSeconds % 60;
        const display = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        
        document.getElementById('countdownTimer').textContent = display;
        
        // Change style when running low
        if (countdownSeconds <= 120) {
            document.getElementById('qrCountdownBox').style.animation = 'pulse 1s infinite';
        }
        
        if (countdownSeconds <= 0) {
            clearInterval(countdownInterval);
            clearInterval(checkPaymentInterval);
            showExpiredModal();
            return;
        }
        
        countdownSeconds--;
    }
    
    countdownInterval = setInterval(updateCountdown, 1000);
    updateCountdown();
    
    // =============================================
    // DEMO MODE - Auto success after 5 seconds
    // =============================================
    <?php if ($transfer_mode === 'demo') : ?>
    let demoCountdown = 5;
    const demoInterval = setInterval(function() {
        demoCountdown--;
        document.getElementById('demoCountdown').textContent = demoCountdown;
        
        if (demoCountdown <= 0) {
            clearInterval(demoInterval);
            clearInterval(countdownInterval);
            clearInterval(checkPaymentInterval);
            
            // Update order status via AJAX
            updateOrderStatus('processing', function() {
                showSuccessModal();
            });
        }
    }, 1000);
    <?php endif; ?>
    
    // =============================================
    // CHECK PAYMENT STATUS (Polling every 5 seconds)
    // =============================================
    function checkPaymentStatus() {
        const formData = new FormData();
        formData.append('action', 'petshop_check_payment_status');
        formData.append('order_id', orderId);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.status === 'paid') {
                clearInterval(countdownInterval);
                clearInterval(checkPaymentInterval);
                showSuccessModal();
            }
        })
        .catch(err => console.error('Check payment error:', err));
    }
    
    // Poll every 5 seconds to check if payment confirmed
    <?php if ($transfer_mode !== 'demo') : ?>
    checkPaymentInterval = setInterval(checkPaymentStatus, 5000);
    <?php endif; ?>
    
    // =============================================
    // MANUAL CONFIRM BUTTON
    // =============================================
    document.getElementById('btnCheckPayment').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang kiểm tra...';
        
        // Simulate checking (in real app, this would verify with bank API/Casso)
        setTimeout(function() {
            <?php if ($transfer_mode === 'demo') : ?>
            // Demo mode - always success
            updateOrderStatus('processing', function() {
                showSuccessModal();
            });
            <?php else : ?>
            // Real mode - show pending message
            const statusBox = document.getElementById('orderStatusBox');
            statusBox.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <span style="color: #92400e; font-weight: 600;">Đang xác nhận thanh toán...</span>
                </div>
                <p style="margin: 10px 0 0; font-size: 0.85rem; color: #a16207;">
                    Vui lòng đợi trong giây lát. Nếu đã chuyển khoản đúng, đơn hàng sẽ được xác nhận tự động.
                </p>
            `;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Đã gửi xác nhận';
            
            // Check again after 3 seconds
            setTimeout(checkPaymentStatus, 3000);
            <?php endif; ?>
        }, 1500);
    });
    
    // =============================================
    // UPDATE ORDER STATUS
    // =============================================
    function updateOrderStatus(status, callback) {
        const formData = new FormData();
        formData.append('action', 'petshop_update_order_status');
        formData.append('order_id', orderId);
        formData.append('status', status);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(err => {
            console.error('Update status error:', err);
            if (callback) callback({success: false});
        });
    }
    
    // =============================================
    // MODALS
    // =============================================
    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.style.display = 'flex';
        
        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = '<?php echo home_url('/hoan-tat/'); ?>?order_id=' + orderId;
        }, 2000);
    }
    
    function showExpiredModal() {
        document.getElementById('expiredModal').style.display = 'flex';
    }
    
    window.refreshQRCode = function() {
        // Reset countdown
        countdownSeconds = expireMinutes * 60;
        document.getElementById('expiredModal').style.display = 'none';
        document.getElementById('qrCountdownBox').style.animation = 'none';
        
        // Restart countdown
        countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Restart payment check
        <?php if ($transfer_mode !== 'demo') : ?>
        checkPaymentInterval = setInterval(checkPaymentStatus, 5000);
        <?php endif; ?>
    };
});

// Copy to clipboard function
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        btn.style.background = '#059669';
        btn.style.color = '#fff';
        
        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.style.background = '#e2e8f0';
            btn.style.color = '';
        }, 1500);
    });
}
</script>

<?php get_footer(); ?>
