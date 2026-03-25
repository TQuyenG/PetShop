<?php
/**
 * Template Name: Trang Thanh Toán
 * 
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-credit-card"></i> Thanh Toán</h1>
        <p>Hoàn tất đơn hàng của bạn</p>
    </div>
</div>

<section class="checkout-section" style="padding: 60px 0;">
    <div class="container">
        <?php petshop_breadcrumb(); ?>
        
        <!-- Back Button -->
        <div style="margin-bottom: 20px;">
            <a href="<?php echo home_url('/gio-hang/'); ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                <i class="bi bi-arrow-left"></i> Quay lại giỏ hàng
            </a>
        </div>
        
        <!-- Cart Progress -->
        <div class="cart-progress" style="display: flex; justify-content: center; align-items: center; margin: 30px 0 50px; gap: 10px;">
            <a href="<?php echo home_url('/gio-hang/'); ?>" class="progress-step completed" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <div style="width: 40px; height: 40px; background: #66BCB4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <span style="color: #66BCB4;">Giỏ hàng</span>
            </a>
            <div style="width: 80px; height: 3px; background: linear-gradient(90deg, #66BCB4 50%, #E8CCAD 50%);"></div>
            <div class="progress-step active" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700;">2</div>
                <span style="font-weight: 600; color: #EC802B;">Thanh toán</span>
            </div>
            <div style="width: 80px; height: 3px; background: #E8CCAD;"></div>
            <div class="progress-step" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #E8CCAD; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #7A6B5A; font-weight: 700;">3</div>
                <span style="color: #7A6B5A;">Hoàn tất</span>
            </div>
        </div>
        
        <form class="checkout-form" method="post" novalidate>
            <div class="checkout-container" style="display: grid; grid-template-columns: 1fr 420px; gap: 40px; margin-top: 30px;">
                <!-- Checkout Form -->
                <div class="checkout-details">
                    <!-- Shipping Info -->
                    <div class="checkout-box" style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-geo-alt" style="color: #EC802B;"></i> Thông tin giao hàng
                            </h3>
                            <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo home_url('/tai-khoan/?from=checkout'); ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">
                                <i class="bi bi-geo-alt"></i> Đổi địa chỉ
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Saved Address Info (for logged in users) -->
                        <div id="savedAddressInfo" style="display: none; padding: 20px; background: #FDF8F3; border-radius: 15px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <strong id="savedName" style="color: #5D4E37;"></strong>
                                        <span style="color: #7A6B5A;">|</span>
                                        <span id="savedPhone" style="color: #7A6B5A;"></span>
                                        <span style="background: #EC802B; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 0.75rem;">Mặc định</span>
                                    </div>
                                    <p id="savedAddress" style="color: #666; margin: 0;"></p>
                                </div>
                                <button type="button" id="editAddressBtn" style="background: none; border: none; color: #EC802B; cursor: pointer; font-size: 0.9rem;">
                                    <i class="bi bi-pencil"></i> Sửa
                                </button>
                            </div>
                        </div>
                        
                        <!-- Manual Address Form -->
                        <div id="manualAddressForm">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Họ tên *</label>
                                    <input type="text" name="fullname" id="checkoutFullname" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;" placeholder="Nhập họ tên">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Số điện thoại *</label>
                                    <input type="tel" name="phone" id="checkoutPhone" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;" placeholder="0909 xxx xxx">
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Email *</label>
                                <input type="email" name="email" id="checkoutEmail" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;" placeholder="email@example.com">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Tỉnh/Thành phố *</label>
                                    <select name="city" id="checkoutCity" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn tỉnh/thành</option>
                                        <option value="hcm">TP. Hồ Chí Minh</option>
                                        <option value="hn">Hà Nội</option>
                                        <option value="dn">Đà Nẵng</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Quận/Huyện *</label>
                                    <select name="district" id="checkoutDistrict" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn quận/huyện</option>
                                        <option value="q1">Quận 1</option>
                                        <option value="q2">Quận 2</option>
                                        <option value="q3">Quận 3</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Phường/Xã *</label>
                                    <select name="ward" id="checkoutWard" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn phường/xã</option>
                                        <option value="p1">Phường Bến Nghé</option>
                                        <option value="p2">Phường Bến Thành</option>
                                        <option value="p3">Phường Cầu Kho</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Địa chỉ cụ thể *</label>
                                <input type="text" name="address" id="checkoutAddress" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;" placeholder="Số nhà, tên đường...">
                            </div>
                            
                            <?php if (is_user_logged_in()) : ?>
                            <div class="form-group" style="margin-top: 15px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="save_address" id="saveAddressCheckbox" style="width: 18px; height: 18px; accent-color: #EC802B;">
                                    <span style="color: #5D4E37;">Lưu địa chỉ này cho lần sau</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Ghi chú</label>
                            <textarea name="notes" rows="3" style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif; resize: vertical;" placeholder="Ghi chú về đơn hàng, thời gian giao hàng..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="checkout-box" style="background: #fff; border-radius: 25px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                        <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-wallet2" style="color: #EC802B;"></i> Phương thức thanh toán
                        </h3>
                        
                        <?php 
                        $payment_settings = petshop_get_payment_settings();
                        $transfer_mode = $payment_settings['transfer_mode'] ?? 'demo';
                        $banks = petshop_get_vietnam_banks();
                        $bank_name = isset($banks[$payment_settings['bank_id']]) ? $banks[$payment_settings['bank_id']]['name'] : '';
                        
                        // Kiểm tra xem thanh toán online có hợp lệ không
                        $online_payment_valid = false;
                        $online_payment_mode = '';
                        
                        if ($payment_settings['vnpay_enabled']) {
                            if ($transfer_mode === 'demo') {
                                $online_payment_valid = true;
                                $online_payment_mode = 'demo';
                            } elseif ($transfer_mode === 'vnpay' && !empty($payment_settings['vnpay_tmn_code']) && !empty($payment_settings['vnpay_hash_secret'])) {
                                $online_payment_valid = true;
                                $online_payment_mode = 'vnpay';
                            } elseif ($transfer_mode === 'vietqr' && !empty($payment_settings['bank_id']) && !empty($payment_settings['bank_account']) && !empty($payment_settings['bank_holder'])) {
                                $online_payment_valid = true;
                                $online_payment_mode = 'vietqr';
                            }
                        }
                        ?>
                        
                        <div class="payment-methods">
                            <!-- COD Option -->
                            <?php if ($payment_settings['cod_enabled']) : ?>
                            <label class="payment-option" style="display: flex; align-items: flex-start; gap: 15px; padding: 20px; border: 2px solid #E8CCAD; border-radius: 15px; cursor: pointer; margin-bottom: 15px; transition: all 0.3s;">
                                <input type="radio" name="payment" value="cod" checked style="margin-top: 3px; accent-color: #EC802B;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <i class="bi bi-cash-coin" style="font-size: 1.3rem; color: #66BCB4;"></i>
                                        <strong><?php echo esc_html($payment_settings['cod_title']); ?></strong>
                                    </div>
                                    <p style="color: #7A6B5A; font-size: 0.9rem; margin: 0;"><?php echo esc_html($payment_settings['cod_description']); ?></p>
                                </div>
                            </label>
                            <?php endif; ?>
                            
                            <!-- Online Payment Option (Demo/VNPay/VietQR) -->
                            <?php if ($online_payment_valid) : ?>
                            <label class="payment-option" style="display: flex; align-items: flex-start; gap: 15px; padding: 20px; border: 2px solid #E8CCAD; border-radius: 15px; cursor: pointer; margin-bottom: 15px; transition: all 0.3s;">
                                <input type="radio" name="payment" value="online" <?php echo !$payment_settings['cod_enabled'] ? 'checked' : ''; ?> style="margin-top: 3px; accent-color: #EC802B;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <i class="bi bi-qr-code-scan" style="font-size: 1.3rem; color: #0066B3;"></i>
                                        <strong><?php echo esc_html($payment_settings['vnpay_title']); ?></strong>
                                        <?php if ($online_payment_mode === 'demo') : ?>
                                        <span style="background: #dba617; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px;">DEMO</span>
                                        <?php elseif ($online_payment_mode === 'vnpay') : ?>
                                        <span style="background: #0066B3; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px;">VNPay</span>
                                        <?php elseif ($online_payment_mode === 'vietqr') : ?>
                                        <span style="background: #059669; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px;">VietQR</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="color: #7A6B5A; font-size: 0.9rem; margin: 0;"><?php echo esc_html($payment_settings['vnpay_description']); ?></p>
                                </div>
                            </label>
                            <?php endif; ?>
                            
                            <?php if (!$payment_settings['cod_enabled'] && !$online_payment_valid) : ?>
                            <div style="text-align: center; padding: 30px; background: #f6f7f7; border-radius: 12px;">
                                <i class="bi bi-exclamation-circle" style="font-size: 2rem; color: #dba617;"></i>
                                <p style="margin: 10px 0 0; color: #666;">Chưa có phương thức thanh toán nào được kích hoạt</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Online Payment Info Section (hiện khi chọn online) -->
                        <?php if ($online_payment_valid) : ?>
                        <div id="onlinePaymentInfo" style="display: none; margin-top: 20px;">
                            
                            <?php if ($online_payment_mode === 'demo') : ?>
                            <!-- Demo Mode Notice -->
                            <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #f59e0b; border-radius: 16px; padding: 25px;">
                                <div style="display: flex; align-items: flex-start; gap: 15px;">
                                    <div style="width: 50px; height: 50px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="bi bi-mortarboard-fill" style="font-size: 1.5rem; color: #fff;"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0 0 10px; color: #92400e;">🎓 Chế độ Demo (Đồ án)</h4>
                                        <p style="margin: 0; color: #a16207; font-size: 0.9rem; line-height: 1.6;">
                                            Khi nhấn <strong>Đặt hàng</strong>, hệ thống sẽ hiển thị mã QR giả định và tự động xác nhận thanh toán thành công sau 3 giây.<br>
                                            <em>Đây là chế độ demo, không cần chuyển khoản thật.</em>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php elseif ($online_payment_mode === 'vnpay') : ?>
                            <!-- VNPay Notice -->
                            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #0066B3; border-radius: 16px; padding: 25px;">
                                <div style="display: flex; align-items: flex-start; gap: 15px;">
                                    <div style="width: 50px; height: 50px; background: #0066B3; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="bi bi-credit-card-fill" style="font-size: 1.5rem; color: #fff;"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0 0 10px; color: #1e40af;">💳 Thanh toán qua VNPay</h4>
                                        <p style="margin: 0; color: #3b82f6; font-size: 0.9rem; line-height: 1.6;">
                                            Sau khi nhấn <strong>Đặt hàng</strong>, bạn sẽ được chuyển đến cổng thanh toán VNPay để hoàn tất giao dịch.<br>
                                            <em>Hỗ trợ: Internet Banking, Thẻ ATM, Thẻ quốc tế, Ví điện tử</em>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php elseif ($online_payment_mode === 'vietqr') : ?>
                            <!-- VietQR - Hiển thị QR Code -->
                            <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #059669; border-radius: 16px; padding: 25px;">
                                <h4 style="margin: 0 0 20px; color: #047857; display: flex; align-items: center; gap: 10px;">
                                    <i class="bi bi-qr-code-scan" style="font-size: 1.3rem;"></i> 
                                    Quét mã QR để thanh toán
                                </h4>
                                
                                <div style="display: flex; flex-wrap: wrap; gap: 25px; align-items: flex-start;">
                                    <div style="flex: 0 0 auto; text-align: center;">
                                        <div id="qrCodeContainer" style="background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: inline-block;">
                                            <img id="qrCodeImage" src="<?php echo esc_url(petshop_generate_vietqr_url(0, 'PREVIEW', 'Don hang')); ?>" 
                                                 alt="QR Code" 
                                                 style="width: 180px; height: 180px; display: block;">
                                        </div>
                                        
                                        <!-- Countdown Timer -->
                                        <div id="qrCountdown" style="margin-top: 12px; padding: 10px 20px; background: #fee2e2; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px;">
                                            <i class="bi bi-clock-history" style="color: #dc2626;"></i>
                                            <span style="color: #dc2626; font-weight: 600;">
                                                Hết hạn sau: <span id="countdownTimer"><?php echo intval($payment_settings['qr_expire_minutes']); ?>:00</span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                            <h5 style="margin: 0 0 15px; color: #1e293b; font-size: 0.95rem;">
                                                <i class="bi bi-bank2" style="color: #059669;"></i> Thông tin tài khoản
                                            </h5>
                                            <div style="display: grid; gap: 12px; font-size: 0.9rem;">
                                                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0;">
                                                    <span style="color: #64748b;">Ngân hàng:</span>
                                                    <strong style="color: #059669;"><?php echo esc_html($bank_name); ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0;">
                                                    <span style="color: #64748b;">Số TK:</span>
                                                    <strong style="color: #1e293b; letter-spacing: 1px;"><?php echo esc_html($payment_settings['bank_account']); ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0;">
                                                    <span style="color: #64748b;">Chủ TK:</span>
                                                    <strong style="color: #1e293b;"><?php echo esc_html($payment_settings['bank_holder']); ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span style="color: #64748b;">Số tiền:</span>
                                                    <strong id="qrAmount" style="color: #dc2626; font-size: 1.1rem;">0₫</strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-top: 15px; padding: 12px 15px; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;">
                                            <p style="margin: 0; font-size: 0.85rem; color: #92400e;">
                                                <i class="bi bi-info-circle"></i> 
                                                Nội dung CK: <strong id="qrContent">DH[MaDH]</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 10px; text-align: center;">
                                    <p style="margin: 0; color: #047857; font-size: 0.9rem;">
                                        <i class="bi bi-shield-check"></i>
                                        Quét mã bằng <strong>app ngân hàng</strong> để thanh toán tự động
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <div style="background: #fff; border-radius: 25px; padding: 30px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); position: sticky; top: 100px;">
                        <h3 style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #FDF8F3;">
                            <i class="bi bi-bag-check"></i> Đơn hàng của bạn
                        </h3>
                        
                        <!-- Order Items - will be populated by JavaScript -->
                        <div id="orderItems" class="order-items" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                            <!-- Items loaded dynamically -->
                        </div>
                        
                        <!-- Empty order message -->
                        <div id="emptyOrder" style="display: none; text-align: center; padding: 30px;">
                            <i class="bi bi-bag-x" style="font-size: 3rem; color: #E8CCAD;"></i>
                            <p style="margin-top: 15px; color: #7A6B5A;">Không có sản phẩm để thanh toán</p>
                            <a href="<?php echo get_post_type_archive_link('product'); ?>" class="btn btn-outline" style="margin-top: 10px;">Mua sắm ngay</a>
                        </div>
                        
                        <!-- Coupon Section -->
                        <div id="couponSection" style="margin-bottom: 20px; padding: 15px; background: #FDF8F3; border-radius: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="font-weight: 600; color: #5D4E37;"><i class="bi bi-ticket-perforated" style="color: #EC802B;"></i> Mã giảm giá</span>
                                <button type="button" id="changeCouponBtn" style="background: none; border: none; color: #EC802B; cursor: pointer; font-size: 0.9rem;">Thay đổi</button>
                            </div>
                            <!-- No coupon state -->
                            <div id="noCouponState">
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="couponCodeInput" placeholder="Nhập mã..." style="flex: 1; padding: 10px 15px; border: 2px solid #E8CCAD; border-radius: 8px; font-family: 'Quicksand', sans-serif;">
                                    <button type="button" id="applyCouponBtn" class="btn btn-outline" style="padding: 10px 15px;">Áp dụng</button>
                                </div>
                                <span id="couponError" style="display: none; color: #E74C3C; font-size: 0.85rem; margin-top: 8px;"></span>
                            </div>
                            <!-- Applied coupon state -->
                            <div id="appliedCouponState" style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 12px 15px; border-radius: 8px; border: 2px solid #66BCB4;">
                                    <div>
                                        <div style="font-weight: 600; color: #66BCB4;"><i class="bi bi-check-circle-fill"></i> <span id="appliedCouponCode"></span></div>
                                        <div style="font-size: 0.85rem; color: #7A6B5A;" id="appliedCouponName"></div>
                                    </div>
                                    <button type="button" id="removeCouponBtn" style="background: none; border: none; color: #E74C3C; cursor: pointer;"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary -->
                        <div id="orderSummarySection">
                            <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                <span style="color: #7A6B5A;">Tạm tính</span>
                                <span id="checkoutSubtotal" style="font-weight: 600;">0đ</span>
                            </div>
                            <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                <span style="color: #7A6B5A;">Phí vận chuyển</span>
                                <span id="checkoutShipping" style="font-weight: 600;">0đ</span>
                            </div>
                            <div id="checkoutShippingDiscountRow" class="summary-row" style="display: none; justify-content: space-between; margin-bottom: 12px; color: #66BCB4;">
                                <span><i class="bi bi-truck"></i> Giảm phí ship (<span id="shippingDiscountCode"></span>)</span>
                                <span id="checkoutShippingDiscount" style="font-weight: 600;">-0đ</span>
                            </div>
                            <div id="checkoutDiscountRow" class="summary-row" style="display: none; justify-content: space-between; margin-bottom: 20px; color: #EC802B;">
                                <span><i class="bi bi-tag-fill"></i> Giảm giá (<span id="discountCouponCode"></span>)</span>
                                <span id="checkoutDiscount" style="font-weight: 600;">-0đ</span>
                            </div>
                            
                            <div class="summary-total" style="display: flex; justify-content: space-between; padding-top: 20px; border-top: 2px solid #FDF8F3; margin-bottom: 25px;">
                                <span style="font-size: 1.1rem; font-weight: 700;">Tổng cộng</span>
                                <span id="checkoutTotal" style="font-size: 1.5rem; font-weight: 700; color: #EC802B;">0đ</span>
                            </div>
                            
                            <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg" style="width: 100%;">
                                <i class="bi bi-check-circle"></i> Đặt hàng
                            </button>
                        </div>
                        
                        <p style="text-align: center; margin-top: 15px; color: #7A6B5A; font-size: 0.85rem;">
                            <i class="bi bi-shield-check" style="color: #66BCB4;"></i>
                            Bằng việc đặt hàng, bạn đồng ý với <a href="#" style="color: #EC802B;">điều khoản dịch vụ</a>
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<style>
.checkout-form input:focus,
.checkout-form select:focus,
.checkout-form textarea:focus {
    outline: none;
    border-color: #EC802B;
}
.payment-option:has(input:checked) {
    border-color: #EC802B;
    background: #FDF8F3;
}
@media (max-width: 992px) {
    .checkout-container {
        grid-template-columns: 1fr !important;
    }
    .cart-progress {
        flex-wrap: wrap;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lấy settings từ PHP
    <?php 
    $shipping_settings = petshop_get_shipping_settings_for_js();
    ?>
    const SHIPPING_FEE = <?php echo $shipping_settings['shipping_fee']; ?>;
    const FREE_SHIPPING_THRESHOLD = <?php echo $shipping_settings['free_shipping_threshold']; ?>;
    const ENABLE_FREE_SHIPPING = <?php echo $shipping_settings['enable_free_shipping'] ? 'true' : 'false'; ?>;
    const AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
    const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    
    let defaultAddressData = null;
    let useDefaultAddress = false;
    let appliedCoupon = null; // Lưu thông tin mã giảm giá
    
    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    }
    
    // Load default address for logged in users
    if (isLoggedIn) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=petshop_get_checkout_info')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.default_address) {
                    defaultAddressData = data.data.default_address;
                    const addr = data.data.default_address;
                    const user = data.data.user;
                    
                    // Show saved address info
                    document.getElementById('savedAddressInfo').style.display = 'block';
                    document.getElementById('savedName').textContent = addr.fullname;
                    document.getElementById('savedPhone').textContent = addr.phone;
                    document.getElementById('savedAddress').textContent = addr.address + ', ' + addr.ward_text + ', ' + addr.district_text + ', ' + addr.city_text;
                    
                    // Fill form with default address
                    document.getElementById('checkoutFullname').value = addr.fullname;
                    document.getElementById('checkoutPhone').value = addr.phone;
                    document.getElementById('checkoutEmail').value = user.email || '';
                    document.getElementById('checkoutCity').value = addr.city;
                    document.getElementById('checkoutDistrict').value = addr.district;
                    document.getElementById('checkoutWard').value = addr.ward;
                    document.getElementById('checkoutAddress').value = addr.address;
                    
                    // Hide manual form, show saved address
                    const manForm = document.getElementById('manualAddressForm');
                    manForm.style.display = 'none';
                    manForm.querySelectorAll('input,select,textarea').forEach(el => {
                        el.disabled = true;
                    });
                    useDefaultAddress = true;
                } else if (data.success && data.data.user) {
                    // No default address but has user info
                    const user = data.data.user;
                    document.getElementById('checkoutFullname').value = user.display_name || '';
                    document.getElementById('checkoutEmail').value = user.email || '';
                    document.getElementById('checkoutPhone').value = user.phone || '';
                }
            });
    }
    
    // Edit address button - show manual form
    document.getElementById('editAddressBtn')?.addEventListener('click', function() {
        document.getElementById('savedAddressInfo').style.display = 'none';
        const manFormShow = document.getElementById('manualAddressForm');
        manFormShow.style.display = 'block';
        manFormShow.querySelectorAll('input,select,textarea').forEach(el => {
            el.disabled = false;
        });
        useDefaultAddress = false;
    });
    
    // Lấy danh sách sản phẩm thanh toán
    function getCheckoutItems() {
        return JSON.parse(localStorage.getItem('petshop_checkout')) || [];
    }
    
    function renderOrderItems() {
        const items = getCheckoutItems();
        const orderItemsEl = document.getElementById('orderItems');
        const emptyOrderEl = document.getElementById('emptyOrder');
        const summarySection = document.getElementById('orderSummarySection');
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        
        if (items.length === 0) {
            orderItemsEl.style.display = 'none';
            summarySection.style.display = 'none';
            emptyOrderEl.style.display = 'block';
            return;
        }
        
        orderItemsEl.style.display = 'block';
        summarySection.style.display = 'block';
        emptyOrderEl.style.display = 'none';
        
        let html = '';
        items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const imgSrc = item.image || 'https://via.placeholder.com/60x60?text=No+Image';
            
            html += `
                <div class="order-item" style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #FDF8F3;">
                    <img src="${imgSrc}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 3px;">${item.name}</h4>
                        <span style="color: #7A6B5A; font-size: 0.85rem;">x${item.quantity}</span>
                    </div>
                    <span style="font-weight: 600; color: #5D4E37;">${formatMoney(itemTotal)}</span>
                </div>
            `;
        });
        
        orderItemsEl.innerHTML = html;
        
        // Tính tổng
        updateOrderSummary();
    }
    
    // Tính toán và cập nhật tổng đơn hàng
    function updateOrderSummary() {
        const items = getCheckoutItems();
        const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Phí ship cơ bản - kiểm tra enable free shipping
        let originalShipping = SHIPPING_FEE;
        if (ENABLE_FREE_SHIPPING && subtotal >= FREE_SHIPPING_THRESHOLD) {
            originalShipping = 0;
        }
        
        // Tính giảm giá từ coupon - tách biệt giảm sản phẩm và giảm ship
        let discount = 0; // Giảm giá sản phẩm
        let shippingDiscount = 0; // Giảm phí ship
        
        if (appliedCoupon) {
            if (appliedCoupon.is_freeship) {
                // Coupon freeship - chỉ giảm phí ship
                shippingDiscount = Math.min(appliedCoupon.shipping_discount || SHIPPING_FEE, originalShipping);
                discount = 0;
            } else if (appliedCoupon.discount > 0) {
                // Coupon giảm giá sản phẩm
                discount = Math.min(appliedCoupon.discount, subtotal);
            }
        }
        
        // Phí ship sau khi giảm
        const finalShipping = Math.max(0, originalShipping - shippingDiscount);
        
        // Tổng = Subtotal - Giảm giá sản phẩm + Phí ship (đã trừ giảm)
        const total = Math.max(0, subtotal - discount + finalShipping);
        
        document.getElementById('checkoutSubtotal').textContent = formatMoney(subtotal);
        
        // Hiển thị phí ship gốc
        if (shippingDiscount > 0 && originalShipping > 0) {
            document.getElementById('checkoutShipping').textContent = formatMoney(originalShipping);
        } else {
            document.getElementById('checkoutShipping').textContent = originalShipping === 0 ? 'Miễn phí' : formatMoney(originalShipping);
        }
        
        // Hiển thị giảm phí ship nếu có
        const shippingDiscountRow = document.getElementById('checkoutShippingDiscountRow');
        if (shippingDiscount > 0) {
            shippingDiscountRow.style.display = 'flex';
            document.getElementById('checkoutShippingDiscount').textContent = '-' + formatMoney(shippingDiscount);
            document.getElementById('shippingDiscountCode').textContent = appliedCoupon.code;
        } else {
            shippingDiscountRow.style.display = 'none';
        }
        
        // Hiển thị giảm giá sản phẩm nếu có
        const discountRow = document.getElementById('checkoutDiscountRow');
        if (discount > 0) {
            discountRow.style.display = 'flex';
            document.getElementById('checkoutDiscount').textContent = '-' + formatMoney(discount);
            document.getElementById('discountCouponCode').textContent = appliedCoupon.code;
        } else {
            discountRow.style.display = 'none';
        }
        
        document.getElementById('checkoutTotal').textContent = formatMoney(total);
    }
    
    // Load mã giảm giá đã áp dụng từ giỏ hàng
    function loadSavedCoupon() {
        const savedCoupon = localStorage.getItem('petshop_applied_coupon');
        if (savedCoupon) {
            try {
                const couponData = JSON.parse(savedCoupon);
                // Validate lại coupon với cart items hiện tại
                validateAndApplyCoupon(couponData.code);
            } catch (e) {
                localStorage.removeItem('petshop_applied_coupon');
            }
        }
    }
    
    // Validate và áp dụng coupon
    async function validateAndApplyCoupon(code) {
        const items = getCheckoutItems();
        const cartItems = items.map(item => ({
            id: item.id,
            price: item.price,
            quantity: item.quantity
        }));
        
        const formData = new FormData();
        formData.append('action', 'petshop_validate_coupon');
        formData.append('code', code);
        formData.append('cart_items', JSON.stringify(cartItems));
        
        try {
            const response = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                appliedCoupon = {
                    id: data.data.coupon.id,
                    code: data.data.coupon.code,
                    name: data.data.coupon.name,
                    discount_type: data.data.coupon.discount_type,
                    discount_value: parseFloat(data.data.coupon.discount_value),
                    discount: data.data.discount, // Giảm giá sản phẩm
                    shipping_discount: data.data.shipping_discount || 0, // Giảm phí ship
                    is_freeship: data.data.is_freeship || false,
                    message: data.data.message
                };
                
                // Cập nhật UI
                showAppliedCoupon();
                updateOrderSummary();
                
                // Lưu lại vào localStorage
                localStorage.setItem('petshop_applied_coupon', JSON.stringify(appliedCoupon));
                
                return true;
            } else {
                showCouponError(data.data.message || 'Mã giảm giá không hợp lệ');
                return false;
            }
        } catch (error) {
            console.error('Error validating coupon:', error);
            showCouponError('Có lỗi xảy ra, vui lòng thử lại');
            return false;
        }
    }
    
    // Hiển thị mã đã áp dụng
    function showAppliedCoupon() {
        document.getElementById('noCouponState').style.display = 'none';
        document.getElementById('appliedCouponState').style.display = 'block';
        document.getElementById('appliedCouponCode').textContent = appliedCoupon.code;
        document.getElementById('appliedCouponName').textContent = appliedCoupon.message || appliedCoupon.name;
        document.getElementById('changeCouponBtn').style.display = 'none';
    }
    
    // Hiển thị lỗi coupon
    function showCouponError(message) {
        const errorEl = document.getElementById('couponError');
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        setTimeout(() => {
            errorEl.style.display = 'none';
        }, 5000);
    }
    
    // Xóa mã giảm giá
    function removeCoupon() {
        appliedCoupon = null;
        localStorage.removeItem('petshop_applied_coupon');
        
        document.getElementById('noCouponState').style.display = 'block';
        document.getElementById('appliedCouponState').style.display = 'none';
        document.getElementById('changeCouponBtn').style.display = 'block';
        document.getElementById('couponCodeInput').value = '';
        
        updateOrderSummary();
    }
    
    // Event listeners cho coupon
    document.getElementById('applyCouponBtn')?.addEventListener('click', async function() {
        const code = document.getElementById('couponCodeInput').value.trim().toUpperCase();
        if (!code) {
            showCouponError('Vui lòng nhập mã giảm giá');
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Đang kiểm tra...';
        
        await validateAndApplyCoupon(code);
        
        this.disabled = false;
        this.textContent = 'Áp dụng';
    });
    
    document.getElementById('couponCodeInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('applyCouponBtn').click();
        }
    });
    
    document.getElementById('removeCouponBtn')?.addEventListener('click', removeCoupon);
    
    document.getElementById('changeCouponBtn')?.addEventListener('click', function() {
        // Focus vào input
        document.getElementById('couponCodeInput').focus();
    });
    
    // Xử lý form submit
    document.querySelector('.checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const items = getCheckoutItems();
        if (items.length === 0) {
            alert('Không có sản phẩm để thanh toán!');
            return;
        }

        // ============================================================
        // JS Validation thủ công — thay thế HTML5 native validation
        // (vì có fields bị hidden/disabled theo điều kiện)
        // ============================================================
        const form = e.target;
        const isManualForm = document.getElementById('manualAddressForm').style.display !== 'none';
        const isSavedAddress = document.getElementById('savedAddressInfo').style.display !== 'none';

        function showFieldError(fieldEl, message) {
            fieldEl.style.borderColor = '#e74c3c';
            let errEl = fieldEl.parentNode.querySelector('.field-error-msg');
            if (!errEl) {
                errEl = document.createElement('div');
                errEl.className = 'field-error-msg';
                errEl.style.cssText = 'color:#e74c3c;font-size:.82rem;margin-top:4px;';
                fieldEl.parentNode.appendChild(errEl);
            }
            errEl.textContent = message;
            if (!hasScrolled) { fieldEl.scrollIntoView({behavior:'smooth',block:'center'}); hasScrolled=true; }
        }
        function clearErrors() {
            form.querySelectorAll('.field-error-msg').forEach(el=>el.remove());
            form.querySelectorAll('input,select,textarea').forEach(el=>el.style.borderColor='');
        }

        clearErrors();
        let hasScrolled = false;
        let isValid = true;

        if (isManualForm || (!isSavedAddress && !isManualForm)) {
            const fullnameEl = form.querySelector('[name="fullname"]');
            const phoneEl    = form.querySelector('[name="phone"]');
            const cityEl     = form.querySelector('[name="city"]');
            const districtEl = form.querySelector('[name="district"]');
            const wardEl     = form.querySelector('[name="ward"]');
            const addrEl     = form.querySelector('[name="address"]');

            if (fullnameEl && !fullnameEl.value.trim()) { showFieldError(fullnameEl,'Vui lòng nhập họ tên'); isValid=false; }
            if (phoneEl && !phoneEl.value.trim()) { showFieldError(phoneEl,'Vui lòng nhập số điện thoại'); isValid=false; }
            if (cityEl && !cityEl.value) { showFieldError(cityEl,'Vui lòng chọn tỉnh/thành phố'); isValid=false; }
            if (districtEl && !districtEl.value) { showFieldError(districtEl,'Vui lòng chọn quận/huyện'); isValid=false; }
            if (wardEl && !wardEl.value) { showFieldError(wardEl,'Vui lòng chọn phường/xã'); isValid=false; }
            if (addrEl && !addrEl.value.trim()) { showFieldError(addrEl,'Vui lòng nhập địa chỉ cụ thể'); isValid=false; }
        }

        if (!isValid) return;
        // ============================================================

        // Lấy thông tin form
        // Khi dùng savedAddress, các field bị disabled — đọc từ savedAddressInfo
        const isSaved = document.getElementById('savedAddressInfo').style.display !== 'none';
        let fullname, phone, cityText, districtText, wardText, address;

        if (isSaved) {
            // Lấy từ địa chỉ đã lưu hiển thị
            fullname    = document.getElementById('savedName')?.textContent?.trim()    || '';
            phone       = document.getElementById('savedPhone')?.textContent?.trim()   || '';
            const savedAddrText = document.getElementById('savedAddress')?.textContent?.trim() || '';
            address     = savedAddrText;
            cityText     = ''; districtText = ''; wardText = '';
        } else {
            fullname    = form.querySelector('[name="fullname"]')?.value.trim() || '';
            phone       = form.querySelector('[name="phone"]')?.value.trim()    || '';
            const city  = form.querySelector('[name="city"]');
            cityText    = city ? (city.options[city.selectedIndex]?.text || '') : '';
            const dist  = form.querySelector('[name="district"]');
            districtText= dist ? (dist.options[dist.selectedIndex]?.text || '') : '';
            const ward  = form.querySelector('[name="ward"]');
            wardText    = ward ? (ward.options[ward.selectedIndex]?.text || '') : '';
            address     = form.querySelector('[name="address"]')?.value.trim() || '';
        }
        const email = form.querySelector('[name="email"]')?.value.trim() || '';
        const notes   = form.querySelector('[name="notes"]')?.value.trim() || '';
        const payment = form.querySelector('[name="payment"]:checked')?.value || 'cod';

        // city/district/ward value codes (for saving address)
        const cityVal     = isSaved ? '' : (form.querySelector('[name="city"]')?.value     || '');
        const districtVal = isSaved ? '' : (form.querySelector('[name="district"]')?.value  || '');
        const wardVal     = isSaved ? '' : (form.querySelector('[name="ward"]')?.value      || '');

        // Build full address
        const fullAddress = isSaved
            ? address
            : [address, wardText, districtText, cityText].filter(Boolean).join(', ');
        
        // Calculate total - tách biệt giảm giá sản phẩm và giảm phí ship
        const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Tính phí ship gốc với setting
        let originalShipping = SHIPPING_FEE;
        if (ENABLE_FREE_SHIPPING && subtotal >= FREE_SHIPPING_THRESHOLD) {
            originalShipping = 0;
        }
        
        let discount = 0; // Giảm giá sản phẩm
        let shippingDiscount = 0; // Giảm phí ship
        let couponId = null;
        let couponCode = '';
        
        // Thêm thông tin coupon nếu có
        if (appliedCoupon) {
            couponId = appliedCoupon.id;
            couponCode = appliedCoupon.code;
            
            if (appliedCoupon.is_freeship) {
                // Freeship coupon - chỉ giảm phí ship
                shippingDiscount = Math.min(appliedCoupon.shipping_discount || SHIPPING_FEE, originalShipping);
                discount = 0;
            } else if (appliedCoupon.discount > 0) {
                // Product discount coupon
                discount = Math.min(appliedCoupon.discount, subtotal);
            }
        }
        
        // Phí ship sau khi giảm
        const finalShipping = Math.max(0, originalShipping - shippingDiscount);
        
        // Tổng = Subtotal - Giảm giá sản phẩm + Phí ship cuối
        const total = Math.max(0, subtotal - discount + finalShipping);
        
        // Prepare order data
        const orderData = {
            customer_name: fullname,
            customer_phone: phone,
            customer_email: email,
            customer_address: fullAddress,
            payment_method: payment,
            order_note: notes,
            cart_items: items.map(item => ({
                id:            item.id,
                name:          item.name,
                price:         item.price,
                quantity:      item.quantity,
                image:         item.image         || '',
                sku:           item.sku            || '',
                variantId:     item.variantId      || '',
                selectedSize:  item.selectedSize   || '',
                selectedColor: item.selectedColor  || '',
                variantLabel:  item.variantLabel   || '',
            })),
            subtotal: subtotal,
            shipping_fee: originalShipping, // Phí ship gốc
            shipping_discount: shippingDiscount, // Giảm phí ship
            final_shipping: finalShipping, // Phí ship thực tế sau giảm
            discount: discount, // Giảm giá sản phẩm
            coupon_id: couponId,
            coupon_code: couponCode,
            order_total: total
        };
        
        // Track checkout event for analytics
        if (typeof window.petshopTrackEvent === 'function') {
            window.petshopTrackEvent('checkout', {
                value: total,
                items_count: cart.length,
                payment_method: paymentMethod
            });
        }
        
        // Disable submit button
        const submitBtn = document.getElementById('placeOrderBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang xử lý...';
        
        // Send AJAX request to save order
        const formData = new FormData();
        formData.append('action', 'petshop_save_order');
        formData.append('nonce', '<?php echo wp_create_nonce('petshop_checkout_nonce'); ?>');
        formData.append('order_data', JSON.stringify(orderData));
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Lưu địa chỉ nếu đã chọn checkbox
                const saveAddressCheckbox = document.getElementById('saveAddressCheckbox');
                if (isLoggedIn && saveAddressCheckbox && saveAddressCheckbox.checked && !useDefaultAddress) {
                    const addressData = new FormData();
                    addressData.append('action', 'petshop_save_address');
                    addressData.append('nonce', '<?php echo wp_create_nonce('petshop_account_nonce'); ?>');
                    addressData.append('address_data', JSON.stringify({
                        label: 'Địa chỉ giao hàng',
                        fullname: fullname,
                        phone: phone,
                        city: cityVal,
                        city_text: cityText,
                        district: districtVal,
                        district_text: districtText,
                        ward: wardVal,
                        ward_text: wardText,
                        address: address
                    }));
                    addressData.append('set_default', '1');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: addressData
                    });
                }
                
                // Kiểm tra Buy Now hay thanh toán từ giỏ hàng
                const urlParams = new URLSearchParams(window.location.search);
                const isBuyNow = urlParams.get('buy_now') === '1';
                
                if (!isBuyNow) {
                    // Nếu thanh toán từ giỏ hàng, xóa các sản phẩm đã thanh toán khỏi giỏ
                    const cartKey = window.getCartKey ? window.getCartKey() : 'petshop_cart_guest';
                    const cart = JSON.parse(localStorage.getItem(cartKey)) || [];
                    const checkoutIds = items.map(item => item.id);
                    const newCart = cart.filter(item => !checkoutIds.includes(item.id));
                    localStorage.setItem(cartKey, JSON.stringify(newCart));
                }
                
                // Lưu order info vào session storage để hiển thị ở trang hoàn tất
                sessionStorage.setItem('petshop_last_order', JSON.stringify({
                    order_id: data.data.order_id,
                    order_code: data.data.order_code,
                    ...orderData
                }));
                
                // Xóa checkout storage
                localStorage.removeItem('petshop_checkout');
                localStorage.removeItem('petshop_applied_coupon');
                
                // Cập nhật cart count
                if (typeof window.updateGlobalCartCount === 'function') {
                    window.updateGlobalCartCount();
                }
                
                // Kiểm tra phương thức thanh toán
                if (payment === 'online') {
                    // Thanh toán online - Chuyển sang trang QR để thanh toán
                    const transferMode = paymentSettings.onlinePaymentMode;
                    
                    if (transferMode === 'vnpay') {
                        // VNPay Gateway - cần gọi API tạo URL
                        const onlineData = new FormData();
                        onlineData.append('action', 'petshop_create_vnpay_payment');
                        onlineData.append('order_id', data.data.order_id);
                        onlineData.append('amount', total);
                        
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: onlineData
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.success && result.data.redirect_url) {
                                window.location.href = result.data.redirect_url;
                            } else {
                                alert('Lỗi: ' + (result.data?.message || 'Không thể tạo giao dịch VNPay'));
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Đặt hàng';
                            }
                        });
                    } else {
                        // Demo hoặc VietQR - Chuyển sang trang QR riêng
                        window.location.href = '<?php echo home_url('/thanh-toan-qr/'); ?>?order_id=' + data.data.order_id;
                    }
                } else {
                    // COD - Chuyển sang trang hoàn tất
                    window.location.href = '<?php echo home_url('/hoan-tat/'); ?>?order_id=' + data.data.order_id;
                }
            } else {
                alert('Lỗi: ' + (data.data?.message || 'Không thể đặt hàng. Vui lòng thử lại.'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Đặt hàng';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Đặt hàng';
        });
    });
    
    // Render khi load trang
    renderOrderItems();
    loadSavedCoupon();
    
    // Payment Settings từ PHP
    const paymentSettings = {
        transferMode: '<?php echo esc_js($transfer_mode); ?>',
        bankId: '<?php echo esc_js($payment_settings['bank_id'] ?? ''); ?>',
        bankAccount: '<?php echo esc_js($payment_settings['bank_account'] ?? ''); ?>',
        bankHolder: '<?php echo esc_js($payment_settings['bank_holder'] ?? ''); ?>',
        qrExpireMinutes: <?php echo intval($payment_settings['qr_expire_minutes'] ?? 15); ?>,
        onlinePaymentValid: <?php echo $online_payment_valid ? 'true' : 'false'; ?>,
        onlinePaymentMode: '<?php echo esc_js($online_payment_mode); ?>'
    };
    
    let qrCountdownInterval = null;
    
    // Hàm tạo URL QR VietQR
    function generateVietQRUrl(amount, orderId, description) {
        description = description || 'DH' + orderId;
        description = description.replace(/[^a-zA-Z0-9\s]/g, '').substring(0, 50);
        
        return `https://img.vietqr.io/image/${paymentSettings.bankId}-${paymentSettings.bankAccount}-compact2.png?amount=${Math.round(amount)}&addInfo=${encodeURIComponent(description)}&accountName=${encodeURIComponent(paymentSettings.bankHolder)}`;
    }
    
    // Hàm cập nhật QR Code khi thay đổi giỏ hàng
    function updateQRCode() {
        const total = calculateTotal();
        const qrImage = document.getElementById('qrCodeImage');
        const qrAmount = document.getElementById('qrAmount');
        const qrContent = document.getElementById('qrContent');
        
        if (qrImage && paymentSettings.onlinePaymentMode === 'vietqr') {
            // Tạo order reference tạm thời
            const tempOrderId = Date.now().toString().slice(-6);
            const description = 'DH' + tempOrderId;
            
            qrImage.src = generateVietQRUrl(total, tempOrderId, description);
            
            if (qrAmount) qrAmount.textContent = formatMoney(total);
            if (qrContent) qrContent.textContent = description;
        }
    }
    
    // Hàm bắt đầu countdown cho QR
    function startQRCountdown() {
        // Clear previous interval
        if (qrCountdownInterval) {
            clearInterval(qrCountdownInterval);
        }
        
        const countdownEl = document.getElementById('countdownTimer');
        if (!countdownEl) return;
        
        let totalSeconds = paymentSettings.qrExpireMinutes * 60;
        
        function updateCountdown() {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color when less than 2 minutes
            const container = document.getElementById('qrCountdown');
            if (totalSeconds <= 120 && container) {
                container.style.background = '#fef2f2';
                container.style.animation = 'pulse 1s infinite';
            }
            
            if (totalSeconds <= 0) {
                clearInterval(qrCountdownInterval);
                countdownEl.textContent = 'Hết hạn!';
                
                // Refresh QR code
                const refreshBtn = document.createElement('button');
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Tạo mã mới';
                refreshBtn.style.cssText = 'margin-left: 10px; padding: 5px 15px; border: none; background: #059669; color: white; border-radius: 5px; cursor: pointer; font-size: 0.85rem;';
                refreshBtn.onclick = function() {
                    updateQRCode();
                    startQRCountdown();
                    this.remove();
                };
                
                if (container) container.appendChild(refreshBtn);
            }
            
            totalSeconds--;
        }
        
        updateCountdown();
        qrCountdownInterval = setInterval(updateCountdown, 1000);
    }
    
    // Xử lý hiển thị thông tin theo phương thức thanh toán
    document.querySelectorAll('input[name="payment"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const onlinePaymentInfo = document.getElementById('onlinePaymentInfo');
            
            if (this.value === 'online') {
                if (onlinePaymentInfo) {
                    onlinePaymentInfo.style.display = 'block';
                    
                    // Nếu là VietQR thì cập nhật QR và bắt đầu countdown
                    if (paymentSettings.onlinePaymentMode === 'vietqr') {
                        updateQRCode();
                        startQRCountdown();
                    }
                }
            } else {
                if (onlinePaymentInfo) onlinePaymentInfo.style.display = 'none';
                // Stop countdown when not using online payment
                if (qrCountdownInterval) {
                    clearInterval(qrCountdownInterval);
                }
            }
        });
    });
    
    // Add pulse animation for countdown
    const pulseStyle = document.createElement('style');
    pulseStyle.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    `;
    document.head.appendChild(pulseStyle);
});

// Demo Payment Modal
function showDemoPaymentModal(orderId, amount) {
    const modal = document.createElement('div');
    modal.id = 'demoPaymentModal';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7); z-index: 10001;
        display: flex; align-items: center; justify-content: center;
    `;
    modal.innerHTML = `
        <div style="background: #fff; border-radius: 20px; padding: 40px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #0066B3 0%, #004d86 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="bi bi-qr-code-scan" style="font-size: 3rem; color: #fff;"></i>
            </div>
            
            <h3 style="color: #333; margin-bottom: 15px;">Đang xử lý thanh toán</h3>
            
            <div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                <p style="color: #666; margin: 0 0 10px;">Số tiền thanh toán</p>
                <p style="font-size: 2rem; font-weight: 700; color: #0066B3; margin: 0;">${formatMoney(amount)}</p>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 20px;">
                <div class="spinner" style="width: 24px; height: 24px; border: 3px solid #e0e0e0; border-top-color: #0066B3; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <span style="color: #666;">Đang kết nối VNPay...</span>
            </div>
            
            <div id="demoCountdown" style="background: #fff8e5; border: 1px solid #dba617; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                <p style="margin: 0; color: #8c6d00; font-size: 0.9rem;">
                    <i class="bi bi-info-circle"></i> Chế độ Demo - Tự động thành công sau <strong id="countdownNum">3</strong> giây
                </p>
            </div>
            
            <p style="color: #999; font-size: 0.85rem; margin: 0;">
                Đây là giao diện demo. Trong môi trường thực, bạn sẽ được chuyển đến trang thanh toán VNPay.
            </p>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add spinner animation
    const style = document.createElement('style');
    style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
    
    // Countdown
    let count = 3;
    const countdownEl = document.getElementById('countdownNum');
    const interval = setInterval(() => {
        count--;
        countdownEl.textContent = count;
        
        if (count <= 0) {
            clearInterval(interval);
            // Redirect to success page
            window.location.href = '<?php echo home_url('/vnpay-return/'); ?>?vnp_ResponseCode=00&vnp_TxnRef=' + orderId + '&demo=1';
        }
    }, 1000);
}
</script>

<?php get_footer(); ?>