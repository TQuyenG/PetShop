<?php
/**
 * Payment Settings - Quản lý cài đặt thanh toán
 * 
 * Phương thức: COD (Tiền mặt) và Chuyển khoản (VNPay)
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================
// ĐĂNG KÝ MENU ADMIN
// =============================================
function petshop_payment_settings_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Cài đặt thanh toán',
        'Thanh toán',
        'manage_options',
        'petshop-payment-settings',
        'petshop_payment_settings_page'
    );
}
add_action('admin_menu', 'petshop_payment_settings_menu', 30);

// =============================================
// LƯU CÀI ĐẶT
// =============================================
function petshop_save_payment_settings() {
    if (!isset($_POST['petshop_payment_nonce']) || !wp_verify_nonce($_POST['petshop_payment_nonce'], 'petshop_payment_settings')) {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Xác định transfer_mode
    $transfer_mode = sanitize_text_field($_POST['transfer_mode'] ?? 'demo');
    $vnpay_enabled = isset($_POST['vnpay_enabled']) ? 1 : 0;
    
    // Validation theo transfer_mode (chỉ khi vnpay_enabled = 1)
    if ($vnpay_enabled) {
        if ($transfer_mode === 'vnpay') {
            // VNPay mode - bắt buộc phải có TMN Code và Hash Secret
            $tmn_code = sanitize_text_field($_POST['vnpay_tmn_code'] ?? '');
            $hash_secret = sanitize_text_field($_POST['vnpay_hash_secret'] ?? '');
            
            if (empty($tmn_code) || empty($hash_secret)) {
                add_settings_error('petshop_payment', 'vnpay_required', 
                    '❌ Chế độ VNPay Gateway yêu cầu nhập đầy đủ TMN Code và Hash Secret!', 'error');
                return;
            }
        } elseif ($transfer_mode === 'vietqr') {
            // VietQR mode - bắt buộc phải có thông tin ngân hàng
            $bank_id = sanitize_text_field($_POST['bank_id'] ?? '');
            $bank_account = sanitize_text_field($_POST['bank_account'] ?? '');
            $bank_holder = sanitize_text_field($_POST['bank_holder'] ?? '');
            
            if (empty($bank_id) || empty($bank_account) || empty($bank_holder)) {
                add_settings_error('petshop_payment', 'vietqr_required', 
                    '❌ Chế độ VietQR yêu cầu nhập đầy đủ: Ngân hàng, Số tài khoản và Tên chủ tài khoản!', 'error');
                return;
            }
        }
        // Demo mode không cần validation gì thêm
    }
    
    $demo_mode = ($transfer_mode === 'demo') ? 1 : 0;
    $qr_enabled = ($transfer_mode === 'vietqr') ? 1 : 0;
    
    $settings = array(
        // COD Settings
        'cod_enabled' => isset($_POST['cod_enabled']) ? 1 : 0,
        'cod_title' => sanitize_text_field($_POST['cod_title'] ?? 'Thanh toán khi nhận hàng (COD)'),
        'cod_description' => sanitize_textarea_field($_POST['cod_description'] ?? ''),
        
        // VNPay Settings
        'vnpay_enabled' => $vnpay_enabled,
        'vnpay_title' => sanitize_text_field($_POST['vnpay_title'] ?? 'Chuyển khoản / QR Code'),
        'vnpay_description' => sanitize_textarea_field($_POST['vnpay_description'] ?? ''),
        'vnpay_sandbox' => isset($_POST['vnpay_sandbox']) ? 1 : 0,
        'vnpay_tmn_code' => sanitize_text_field($_POST['vnpay_tmn_code'] ?? ''),
        'vnpay_hash_secret' => sanitize_text_field($_POST['vnpay_hash_secret'] ?? ''),
        
        // Transfer Mode (demo, vnpay, vietqr)
        'transfer_mode' => $transfer_mode,
        
        // Demo Mode (backward compatibility)
        'demo_mode' => $demo_mode,
        
        // QR Code Settings
        'qr_enabled' => $qr_enabled,
        'qr_expire_minutes' => intval($_POST['qr_expire_minutes'] ?? 15),
        
        // Bank Info
        'bank_id' => sanitize_text_field($_POST['bank_id'] ?? ''),
        'bank_account' => sanitize_text_field($_POST['bank_account'] ?? ''),
        'bank_holder' => strtoupper(sanitize_text_field($_POST['bank_holder'] ?? '')),
        'bank_branch' => sanitize_text_field($_POST['bank_branch'] ?? ''),
    );
    
    update_option('petshop_payment_settings', $settings);
    
    add_settings_error('petshop_payment', 'settings_updated', '✅ Đã lưu cài đặt thanh toán thành công!', 'success');
}
add_action('admin_init', 'petshop_save_payment_settings');

// =============================================
// LẤY CÀI ĐẶT
// =============================================
function petshop_get_payment_settings() {
    $defaults = array(
        'cod_enabled' => 1,
        'cod_title' => 'Thanh toán khi nhận hàng (COD)',
        'cod_description' => 'Thanh toán bằng tiền mặt khi nhận hàng',
        
        'vnpay_enabled' => 1,
        'vnpay_title' => 'Chuyển khoản / QR Code',
        'vnpay_description' => 'Quét mã QR bằng app ngân hàng hoặc chuyển khoản',
        'vnpay_sandbox' => 1,
        'vnpay_tmn_code' => '',
        'vnpay_hash_secret' => '',
        
        // Transfer mode: demo, vnpay, vietqr
        'transfer_mode' => 'demo',
        'demo_mode' => 1,
        
        // QR Code settings
        'qr_enabled' => 0,
        'qr_expire_minutes' => 15,
        
        'bank_id' => 'VCB',
        'bank_account' => '',
        'bank_holder' => '',
        'bank_branch' => '',
    );
    
    $settings = get_option('petshop_payment_settings', array());
    return wp_parse_args($settings, $defaults);
}

// =============================================
// DANH SÁCH NGÂN HÀNG VIỆT NAM (VietQR)
// =============================================
function petshop_get_vietnam_banks() {
    return array(
        'VCB' => array('name' => 'Vietcombank', 'full_name' => 'Ngân hàng TMCP Ngoại thương Việt Nam'),
        'TCB' => array('name' => 'Techcombank', 'full_name' => 'Ngân hàng TMCP Kỹ thương Việt Nam'),
        'MB' => array('name' => 'MB Bank', 'full_name' => 'Ngân hàng TMCP Quân đội'),
        'ACB' => array('name' => 'ACB', 'full_name' => 'Ngân hàng TMCP Á Châu'),
        'VPB' => array('name' => 'VPBank', 'full_name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng'),
        'BIDV' => array('name' => 'BIDV', 'full_name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam'),
        'VIB' => array('name' => 'VIB', 'full_name' => 'Ngân hàng TMCP Quốc tế Việt Nam'),
        'TPB' => array('name' => 'TPBank', 'full_name' => 'Ngân hàng TMCP Tiên Phong'),
        'STB' => array('name' => 'Sacombank', 'full_name' => 'Ngân hàng TMCP Sài Gòn Thương Tín'),
        'HDB' => array('name' => 'HDBank', 'full_name' => 'Ngân hàng TMCP Phát triển TP.HCM'),
        'OCB' => array('name' => 'OCB', 'full_name' => 'Ngân hàng TMCP Phương Đông'),
        'MSB' => array('name' => 'MSB', 'full_name' => 'Ngân hàng TMCP Hàng Hải Việt Nam'),
        'SHB' => array('name' => 'SHB', 'full_name' => 'Ngân hàng TMCP Sài Gòn - Hà Nội'),
        'EIB' => array('name' => 'Eximbank', 'full_name' => 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam'),
        'SCB' => array('name' => 'SCB', 'full_name' => 'Ngân hàng TMCP Sài Gòn'),
        'LPB' => array('name' => 'LienVietPostBank', 'full_name' => 'Ngân hàng TMCP Bưu điện Liên Việt'),
        'SEAB' => array('name' => 'SeABank', 'full_name' => 'Ngân hàng TMCP Đông Nam Á'),
        'ABB' => array('name' => 'ABBANK', 'full_name' => 'Ngân hàng TMCP An Bình'),
        'BAB' => array('name' => 'BacABank', 'full_name' => 'Ngân hàng TMCP Bắc Á'),
        'NAB' => array('name' => 'NamABank', 'full_name' => 'Ngân hàng TMCP Nam Á'),
        'NCB' => array('name' => 'NCB', 'full_name' => 'Ngân hàng TMCP Quốc Dân'),
        'PGB' => array('name' => 'PGBank', 'full_name' => 'Ngân hàng TMCP Xăng dầu Petrolimex'),
        'VAB' => array('name' => 'VietABank', 'full_name' => 'Ngân hàng TMCP Việt Á'),
        'BVB' => array('name' => 'BaoVietBank', 'full_name' => 'Ngân hàng TMCP Bảo Việt'),
        'KLB' => array('name' => 'KienLongBank', 'full_name' => 'Ngân hàng TMCP Kiên Long'),
        'CAKE' => array('name' => 'CAKE', 'full_name' => 'Ngân hàng số CAKE by VPBank'),
        'UBANK' => array('name' => 'Ubank', 'full_name' => 'Ngân hàng số Ubank by VPBank'),
        'TIMO' => array('name' => 'Timo', 'full_name' => 'Ngân hàng số Timo'),
        'VTLMONEY' => array('name' => 'Viettel Money', 'full_name' => 'Viettel Money'),
        'VNPTMONEY' => array('name' => 'VNPT Money', 'full_name' => 'VNPT Money'),
    );
}

// =============================================
// TẠO QR CODE URL (VietQR)
// =============================================
function petshop_generate_vietqr_url($amount, $order_id, $description = '') {
    $settings = petshop_get_payment_settings();
    
    $bank_id = $settings['bank_id'];
    $account_no = $settings['bank_account'];
    $account_name = $settings['bank_holder'];
    
    // Clean description - remove special characters, max 50 chars
    $description = $description ?: 'DH' . $order_id;
    $description = preg_replace('/[^a-zA-Z0-9\s]/', '', $description);
    $description = substr($description, 0, 50);
    
    // VietQR URL format
    $qr_url = sprintf(
        'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
        $bank_id,
        $account_no,
        intval($amount),
        urlencode($description),
        urlencode($account_name)
    );
    
    return $qr_url;
}

// =============================================
// TRANG CÀI ĐẶT
// =============================================
function petshop_payment_settings_page() {
    $settings = petshop_get_payment_settings();
    $banks = petshop_get_vietnam_banks();
    $transfer_mode = $settings['transfer_mode'] ?? 'demo'; // demo, vnpay, vietqr
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-money-alt" style="font-size: 30px; width: 30px; height: 30px;"></span>
            Cài đặt thanh toán
        </h1>
        
        <?php settings_errors('petshop_payment'); ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('petshop_payment_settings', 'petshop_payment_nonce'); ?>
            
            <!-- PHẦN 1: TIỀN MẶT (COD) -->
            <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 12px; margin-top: 20px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #66BCB4 0%, #4a9d96 100%); color: #fff; padding: 20px;">
                    <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-money"></span>
                        Phương thức 1: Tiền mặt (COD)
                    </h2>
                    <p style="margin: 10px 0 0; opacity: 0.9; font-size: 13px;">Khách thanh toán khi nhận hàng</p>
                </div>
                
                <div style="padding: 25px;">
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="width: 150px;">Kích hoạt</th>
                            <td>
                                <label class="petshop-switch">
                                    <input type="checkbox" name="cod_enabled" value="1" <?php checked($settings['cod_enabled'], 1); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span style="margin-left: 10px; color: #666;">Bật/tắt thanh toán tiền mặt</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tiêu đề hiển thị</th>
                            <td>
                                <input type="text" name="cod_title" value="<?php echo esc_attr($settings['cod_title']); ?>" class="regular-text">
                                <p class="description">Ví dụ: Thanh toán khi nhận hàng (COD)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Mô tả</th>
                            <td>
                                <textarea name="cod_description" rows="2" class="large-text"><?php echo esc_textarea($settings['cod_description']); ?></textarea>
                                <p class="description">Ví dụ: Thanh toán bằng tiền mặt khi shipper giao hàng đến</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- PHẦN 2: CHUYỂN KHOẢN ONLINE -->
            <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 12px; margin-top: 25px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #0066B3 0%, #004d86 100%); color: #fff; padding: 20px;">
                    <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-credit-card"></span>
                        Phương thức 2: Chuyển khoản / Thanh toán online
                    </h2>
                    <p style="margin: 10px 0 0; opacity: 0.9; font-size: 13px;">Quét mã QR hoặc chuyển khoản ngân hàng</p>
                </div>
                
                <div style="padding: 25px;">
                    <table class="form-table" style="margin: 0 0 20px;">
                        <tr>
                            <th scope="row" style="width: 150px;">Kích hoạt</th>
                            <td>
                                <label class="petshop-switch">
                                    <input type="checkbox" name="vnpay_enabled" value="1" <?php checked($settings['vnpay_enabled'], 1); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span style="margin-left: 10px; color: #666;">Bật/tắt thanh toán chuyển khoản</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tiêu đề hiển thị</th>
                            <td>
                                <input type="text" name="vnpay_title" value="<?php echo esc_attr($settings['vnpay_title']); ?>" class="regular-text">
                                <p class="description">Ví dụ: Chuyển khoản / Quét mã QR</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Mô tả</th>
                            <td>
                                <textarea name="vnpay_description" rows="2" class="large-text"><?php echo esc_textarea($settings['vnpay_description']); ?></textarea>
                                <p class="description">Ví dụ: Quét mã QR bằng app ngân hàng hoặc chuyển khoản thủ công</p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- 3 LỰA CHỌN CHẾ ĐỘ -->
                    <div style="background: #f8f9fa; border-radius: 12px; padding: 25px; margin-top: 20px;">
                        <h3 style="margin: 0 0 20px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-admin-settings" style="color: #0066B3;"></span>
                            Chọn chế độ thanh toán online
                        </h3>
                        
                        <div class="transfer-mode-options" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            
                            <!-- Option 1: Demo Mode -->
                            <label class="mode-option" style="cursor: pointer;">
                                <input type="radio" name="transfer_mode" value="demo" <?php checked($transfer_mode, 'demo'); ?> style="display: none;">
                                <div class="mode-card" style="border: 3px solid <?php echo $transfer_mode === 'demo' ? '#dba617' : '#e2e8f0'; ?>; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; background: <?php echo $transfer_mode === 'demo' ? '#fffbeb' : '#fff'; ?>;">
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                        <span class="dashicons dashicons-welcome-learn-more" style="font-size: 28px; color: #fff;"></span>
                                    </div>
                                    <h4 style="margin: 0 0 8px; color: #92400e;">🎓 Chế độ Demo</h4>
                                    <p style="margin: 0; font-size: 12px; color: #a16207;">Dành cho đồ án, thực hành</p>
                                    <div style="margin-top: 12px; padding: 8px; background: #fef3c7; border-radius: 6px;">
                                        <span style="font-size: 11px; color: #92400e;">Tự động thành công sau 3 giây</span>
                                    </div>
                                </div>
                            </label>
                            
                            <!-- Option 2: VNPay API -->
                            <label class="mode-option" style="cursor: pointer;">
                                <input type="radio" name="transfer_mode" value="vnpay" <?php checked($transfer_mode, 'vnpay'); ?> style="display: none;">
                                <div class="mode-card" style="border: 3px solid <?php echo $transfer_mode === 'vnpay' ? '#0066B3' : '#e2e8f0'; ?>; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; background: <?php echo $transfer_mode === 'vnpay' ? '#eff6ff' : '#fff'; ?>;">
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #0066B3, #004d86); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                        <span class="dashicons dashicons-cloud" style="font-size: 28px; color: #fff;"></span>
                                    </div>
                                    <h4 style="margin: 0 0 8px; color: #1e40af;">💳 VNPay Gateway</h4>
                                    <p style="margin: 0; font-size: 12px; color: #3b82f6;">Cổng thanh toán chuyên nghiệp</p>
                                    <div style="margin-top: 12px; padding: 8px; background: #dbeafe; border-radius: 6px;">
                                        <span style="font-size: 11px; color: #1e40af;">Cần đăng ký tài khoản merchant</span>
                                    </div>
                                </div>
                            </label>
                            
                            <!-- Option 3: VietQR -->
                            <label class="mode-option" style="cursor: pointer;">
                                <input type="radio" name="transfer_mode" value="vietqr" <?php checked($transfer_mode, 'vietqr'); ?> style="display: none;">
                                <div class="mode-card" style="border: 3px solid <?php echo $transfer_mode === 'vietqr' ? '#059669' : '#e2e8f0'; ?>; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; background: <?php echo $transfer_mode === 'vietqr' ? '#ecfdf5' : '#fff'; ?>;">
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #059669, #047857); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                        <span class="dashicons dashicons-smartphone" style="font-size: 28px; color: #fff;"></span>
                                    </div>
                                    <h4 style="margin: 0 0 8px; color: #047857;">📱 VietQR (Miễn phí)</h4>
                                    <p style="margin: 0; font-size: 12px; color: #10b981;">Quét mã QR chuyển khoản</p>
                                    <div style="margin-top: 12px; padding: 8px; background: #d1fae5; border-radius: 6px;">
                                        <span style="font-size: 11px; color: #047857;">Chỉ cần số tài khoản ngân hàng</span>
                                    </div>
                                </div>
                            </label>
                            
                        </div>
                        
                        <!-- DEMO MODE SECTION -->
                        <div id="demoModeSection" class="mode-section" style="margin-top: 25px; padding: 25px; background: #fffbeb; border: 2px solid #f59e0b; border-radius: 12px; <?php echo $transfer_mode !== 'demo' ? 'display: none;' : ''; ?>">
                            <div style="display: flex; align-items: flex-start; gap: 20px;">
                                <div style="flex-shrink: 0;">
                                    <span class="dashicons dashicons-awards" style="font-size: 48px; color: #f59e0b;"></span>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 10px; color: #92400e;">✨ Chế độ Demo đang được kích hoạt</h4>
                                    <ul style="margin: 0; padding-left: 20px; color: #a16207; line-height: 1.8;">
                                        <li>Thanh toán online sẽ <strong>tự động thành công</strong> sau 3 giây</li>
                                        <li>Không cần nhập thông tin ngân hàng hay VNPay API</li>
                                        <li>Phù hợp để <strong>demo cho giáo viên</strong> hoặc test chức năng</li>
                                        <li>Giao dịch demo sẽ được đánh dấu trong hệ thống</li>
                                    </ul>
                                    <div style="margin-top: 15px; padding: 12px 20px; background: #fef3c7; border-radius: 8px; display: inline-block;">
                                        <span class="dashicons dashicons-yes-alt" style="color: #059669;"></span>
                                        <strong style="color: #92400e;">Không cần cấu hình thêm!</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VNPAY API SECTION -->
                        <div id="vnpayApiSection" class="mode-section" style="margin-top: 25px; padding: 25px; background: #eff6ff; border: 2px solid #0066B3; border-radius: 12px; <?php echo $transfer_mode !== 'vnpay' ? 'display: none;' : ''; ?>">
                            <h4 style="margin: 0 0 20px; color: #1e40af; display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-cloud"></span>
                                Cấu hình VNPay API
                            </h4>
                            
                            <table class="form-table" style="margin: 0;">
                                <tr>
                                    <th scope="row" style="width: 150px;">Sandbox Mode</th>
                                    <td>
                                        <label class="petshop-switch">
                                            <input type="checkbox" name="vnpay_sandbox" value="1" <?php checked($settings['vnpay_sandbox'], 1); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span style="margin-left: 10px; color: #666;">Bật để test trước khi go-live</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">TMN Code <span style="color:red;">*</span></th>
                                    <td>
                                        <input type="text" name="vnpay_tmn_code" value="<?php echo esc_attr($settings['vnpay_tmn_code']); ?>" class="regular-text" placeholder="VD: PETSHOP01">
                                        <p class="description">Mã website được cấp khi đăng ký VNPay Merchant</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Hash Secret <span style="color:red;">*</span></th>
                                    <td>
                                        <input type="password" name="vnpay_hash_secret" value="<?php echo esc_attr($settings['vnpay_hash_secret']); ?>" class="regular-text" placeholder="Chuỗi bí mật 32 ký tự">
                                        <p class="description">Chuỗi bí mật để mã hóa giao dịch (lấy từ VNPay Merchant Portal)</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="margin-top: 20px; padding: 15px; background: #dbeafe; border-radius: 8px;">
                                <p style="margin: 0; font-size: 13px; color: #1e40af;">
                                    <span class="dashicons dashicons-info"></span>
                                    <strong>Hướng dẫn:</strong> Đăng ký tài khoản tại 
                                    <a href="https://vnpay.vn" target="_blank" style="color: #0066B3;">vnpay.vn</a> → 
                                    Đăng nhập <a href="https://sandbox.vnpayment.vn/merchantv2/" target="_blank" style="color: #0066B3;">Merchant Portal</a> → 
                                    Lấy TMN Code và Hash Secret
                                </p>
                            </div>
                        </div>
                        
                        <!-- VIETQR SECTION -->
                        <div id="vietqrSection" class="mode-section" style="margin-top: 25px; padding: 25px; background: #ecfdf5; border: 2px solid #059669; border-radius: 12px; <?php echo $transfer_mode !== 'vietqr' ? 'display: none;' : ''; ?>">
                            <h4 style="margin: 0 0 20px; color: #047857; display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-bank"></span>
                                Thông tin tài khoản ngân hàng nhận tiền
                            </h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px;">
                                <div>
                                    <table class="form-table" style="margin: 0;">
                                        <tr>
                                            <th scope="row" style="width: 130px;">Ngân hàng <span style="color:red;">*</span></th>
                                            <td>
                                                <select name="bank_id" class="regular-text" style="width: 100%;">
                                                    <option value="">-- Chọn ngân hàng --</option>
                                                    <?php foreach ($banks as $code => $bank): ?>
                                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['bank_id'], $code); ?>>
                                                            <?php echo esc_html($bank['name']); ?> - <?php echo esc_html($bank['full_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="description">Chọn từ danh sách 30+ ngân hàng Việt Nam</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Số tài khoản <span style="color:red;">*</span></th>
                                            <td>
                                                <input type="text" name="bank_account" value="<?php echo esc_attr($settings['bank_account']); ?>" class="regular-text" placeholder="Nhập số tài khoản" style="font-family: monospace; letter-spacing: 1px;">
                                                <p class="description">Ví dụ: 0123456789 hoặc 19034567890123</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Tên chủ TK <span style="color:red;">*</span></th>
                                            <td>
                                                <input type="text" name="bank_holder" value="<?php echo esc_attr($settings['bank_holder']); ?>" class="regular-text" placeholder="Tên in hoa, không dấu" style="text-transform: uppercase;">
                                                <p class="description">Ví dụ: NGUYEN VAN A (viết IN HOA, KHÔNG DẤU)</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Chi nhánh</th>
                                            <td>
                                                <input type="text" name="bank_branch" value="<?php echo esc_attr($settings['bank_branch']); ?>" class="regular-text" placeholder="Không bắt buộc">
                                                <p class="description">Ví dụ: Chi nhánh Hồ Chí Minh (tuỳ chọn)</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Thời gian hết hạn QR</th>
                                            <td>
                                                <input type="number" name="qr_expire_minutes" value="<?php echo esc_attr($settings['qr_expire_minutes']); ?>" min="5" max="60" style="width: 80px;"> phút
                                                <p class="description">Ví dụ: 15 (mã QR hết hạn sau 15 phút, khách cần tạo mã mới)</p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- QR Preview -->
                                <div style="text-align: center;">
                                    <div style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                        <p style="margin: 0 0 15px; font-weight: 600; color: #047857;">
                                            <span class="dashicons dashicons-visibility"></span> Xem trước mã QR
                                        </p>
                                        <?php if ($settings['bank_account'] && $settings['bank_holder'] && $settings['bank_id']): ?>
                                            <img id="qrPreview" src="<?php echo esc_url(petshop_generate_vietqr_url(100000, 'DEMO', 'Don hang demo')); ?>" 
                                                 alt="QR Preview" 
                                                 style="max-width: 180px; border-radius: 8px;">
                                            <p style="margin: 10px 0 0; font-size: 12px; color: #666;">
                                                Mẫu: 100.000₫
                                            </p>
                                        <?php else: ?>
                                            <div style="width: 180px; height: 180px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <span style="color: #9ca3af; font-size: 13px;">Nhập đủ thông tin<br>để xem QR</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p style="margin-top: 10px; font-size: 11px; color: #666;">
                                        Quét bằng app ngân hàng để kiểm tra
                                    </p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; padding: 15px; background: #d1fae5; border-radius: 8px;">
                                <p style="margin: 0; font-size: 13px; color: #047857;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <strong>VietQR hoàn toàn miễn phí!</strong> Khách quét mã → App ngân hàng tự điền sẵn thông tin → Chỉ cần xác nhận chuyển tiền
                                </p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Hidden fields for backward compatibility -->
            <input type="hidden" name="demo_mode" id="demo_mode_hidden" value="<?php echo $transfer_mode === 'demo' ? '1' : '0'; ?>">
            <input type="hidden" name="qr_enabled" id="qr_enabled_hidden" value="<?php echo $transfer_mode === 'vietqr' ? '1' : '0'; ?>">
            
            <p class="submit" style="margin-top: 25px;">
                <button type="submit" class="button button-primary button-hero">
                    <span class="dashicons dashicons-saved" style="margin-top: 5px;"></span>
                    Lưu cài đặt
                </button>
            </p>
        </form>
    </div>
    
    <style>
    /* Toggle Switch */
    .petshop-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
    }
    .petshop-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .petshop-switch .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .3s;
        border-radius: 26px;
    }
    .petshop-switch .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    .petshop-switch input:checked + .slider {
        background-color: #00a32a;
    }
    .petshop-switch input:checked + .slider:before {
        transform: translateX(24px);
    }
    
    .form-table th {
        width: 130px;
        padding: 15px 10px 15px 0;
    }
    .form-table td {
        padding: 15px 10px;
    }
    
    /* Mode option hover */
    .mode-option .mode-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .mode-option input:checked + .mode-card {
        transform: translateY(-3px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle mode selection
        $('input[name="transfer_mode"]').on('change', function() {
            const mode = $(this).val();
            
            // Hide all sections
            $('.mode-section').hide();
            
            // Reset all card styles
            $('.mode-card').css({
                'border-color': '#e2e8f0',
                'background': '#fff'
            });
            
            // Show selected section and highlight card
            if (mode === 'demo') {
                $('#demoModeSection').fadeIn(300);
                $(this).next('.mode-card').css({
                    'border-color': '#dba617',
                    'background': '#fffbeb'
                });
                $('#demo_mode_hidden').val('1');
                $('#qr_enabled_hidden').val('0');
            } else if (mode === 'vnpay') {
                $('#vnpayApiSection').fadeIn(300);
                $(this).next('.mode-card').css({
                    'border-color': '#0066B3',
                    'background': '#eff6ff'
                });
                $('#demo_mode_hidden').val('0');
                $('#qr_enabled_hidden').val('0');
            } else if (mode === 'vietqr') {
                $('#vietqrSection').fadeIn(300);
                $(this).next('.mode-card').css({
                    'border-color': '#059669',
                    'background': '#ecfdf5'
                });
                $('#demo_mode_hidden').val('0');
                $('#qr_enabled_hidden').val('1');
            }
        });
    });
    </script>
    <?php
}

// =============================================
// XỬ LÝ THANH TOÁN ONLINE (Demo/VNPay/VietQR)
// =============================================
function petshop_create_vnpay_payment($order_id, $amount, $order_info) {
    $settings = petshop_get_payment_settings();
    $transfer_mode = $settings['transfer_mode'] ?? 'demo';
    
    // Demo mode - return fake success URL after showing QR modal
    if ($transfer_mode === 'demo') {
        return array(
            'success' => true,
            'transfer_mode' => 'demo',
            'redirect_url' => add_query_arg(array(
                'vnp_ResponseCode' => '00',
                'vnp_TxnRef' => $order_id,
                'demo' => '1',
                'order_id' => $order_id
            ), home_url('/vnpay-return/'))
        );
    }
    
    // VietQR mode - Chỉ cần redirect đến trang hoàn tất, QR đã hiển thị ở checkout
    if ($transfer_mode === 'vietqr') {
        // Lưu trạng thái chờ thanh toán
        update_post_meta($order_id, 'payment_status', 'pending');
        update_post_meta($order_id, 'payment_method', 'vietqr');
        
        return array(
            'success' => true,
            'transfer_mode' => 'vietqr',
            'message' => 'Vui lòng chuyển khoản theo thông tin đã hiển thị'
        );
    }
    
    // VNPay mode - Real VNPay integration
    if ($transfer_mode === 'vnpay') {
        if (empty($settings['vnpay_tmn_code']) || empty($settings['vnpay_hash_secret'])) {
            return array(
                'success' => false,
                'message' => 'Chưa cấu hình VNPay API'
            );
        }
        
        $vnp_TmnCode = $settings['vnpay_tmn_code'];
        $vnp_HashSecret = $settings['vnpay_hash_secret'];
        $vnp_Url = $settings['vnpay_sandbox'] ? 
            'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html' : 
            'https://pay.vnpay.vn/vpcpay.html';
        $vnp_ReturnUrl = home_url('/vnpay-return/');
        
        $vnp_TxnRef = $order_id . '_' . time();
        $vnp_OrderInfo = $order_info ?: 'Thanh toan don hang ' . $order_id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $amount * 100; // VNPay yêu cầu nhân 100
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $vnp_CreateDate = date('YmdHis');
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        // Lưu transaction ref vào order
        update_post_meta($order_id, 'vnpay_txn_ref', $vnp_TxnRef);
        update_post_meta($order_id, 'payment_method', 'vnpay');
        
        return array(
            'success' => true,
            'transfer_mode' => 'vnpay',
            'redirect_url' => $vnp_Url
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Chế độ thanh toán không hợp lệ'
    );
}

// =============================================
// XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ VNPAY
// =============================================
function petshop_vnpay_return_handler() {
    if (!isset($_GET['vnp_ResponseCode'])) {
        return;
    }
    
    // Đang ở trang vnpay-return
    if (strpos($_SERVER['REQUEST_URI'], 'vnpay-return') === false) {
        return;
    }
    
    $vnp_ResponseCode = sanitize_text_field($_GET['vnp_ResponseCode']);
    $vnp_TxnRef = sanitize_text_field($_GET['vnp_TxnRef'] ?? '');
    $is_demo = isset($_GET['demo']) && $_GET['demo'] === '1';
    
    // Lấy order_id từ txn_ref
    $order_id = $is_demo ? intval($vnp_TxnRef) : intval(explode('_', $vnp_TxnRef)[0]);
    
    if ($vnp_ResponseCode === '00') {
        // Thanh toán thành công
        if ($order_id) {
            update_post_meta($order_id, 'order_status', 'processing');
            update_post_meta($order_id, 'payment_status', 'paid');
            update_post_meta($order_id, 'payment_date', current_time('mysql'));
            
            if ($is_demo) {
                update_post_meta($order_id, 'payment_note', 'Demo mode - Thanh toán giả lập thành công');
            } else {
                update_post_meta($order_id, 'vnpay_response_code', $vnp_ResponseCode);
                update_post_meta($order_id, 'payment_note', 'VNPay - Thanh toán thành công');
            }
        }
        
        // Redirect to thank you page
        wp_redirect(home_url('/hoan-tat/?order_id=' . $order_id . '&payment=success'));
        exit;
    } else {
        // Thanh toán thất bại
        if ($order_id) {
            update_post_meta($order_id, 'payment_status', 'failed');
            update_post_meta($order_id, 'payment_note', 'VNPay - Thanh toán thất bại. Mã lỗi: ' . $vnp_ResponseCode);
        }
        
        wp_redirect(home_url('/hoan-tat/?order_id=' . $order_id . '&payment=failed'));
        exit;
    }
}
add_action('template_redirect', 'petshop_vnpay_return_handler');

// =============================================
// TẠO TRANG VNPAY RETURN (nếu chưa có)
// =============================================
function petshop_create_vnpay_return_page() {
    $page = get_page_by_path('vnpay-return');
    
    if (!$page) {
        wp_insert_post(array(
            'post_title' => 'VNPay Return',
            'post_name' => 'vnpay-return',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '<!-- VNPay Return Handler -->',
        ));
    }
}
add_action('after_switch_theme', 'petshop_create_vnpay_return_page');
register_activation_hook(__FILE__, 'petshop_create_vnpay_return_page');

// =============================================
// AJAX: TẠO THANH TOÁN VNPAY
// =============================================
function petshop_ajax_create_vnpay_payment() {
    $order_id = intval($_POST['order_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    
    if (!$order_id || !$amount) {
        wp_send_json_error(array('message' => 'Thiếu thông tin đơn hàng'));
    }
    
    $order_info = 'Thanh toan don hang #' . $order_id . ' - PetShop';
    $result = petshop_create_vnpay_payment($order_id, $amount, $order_info);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_petshop_create_vnpay_payment', 'petshop_ajax_create_vnpay_payment');
add_action('wp_ajax_nopriv_petshop_create_vnpay_payment', 'petshop_ajax_create_vnpay_payment');

// =============================================
// AJAX: KIỂM TRA TRẠNG THÁI THANH TOÁN
// =============================================
function petshop_ajax_check_payment_status() {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'Order ID không hợp lệ'));
    }
    
    $order_status = get_post_meta($order_id, 'order_status', true);
    $payment_status = get_post_meta($order_id, 'payment_status', true);
    
    // Nếu đơn hàng đã được xử lý hoặc hoàn thành = đã thanh toán
    if ($order_status === 'processing' || $order_status === 'completed' || $payment_status === 'paid') {
        wp_send_json_success(array(
            'status' => 'paid',
            'order_status' => $order_status,
            'message' => 'Thanh toán thành công'
        ));
    }
    
    wp_send_json_success(array(
        'status' => 'pending',
        'order_status' => $order_status,
        'message' => 'Đang chờ thanh toán'
    ));
}
add_action('wp_ajax_petshop_check_payment_status', 'petshop_ajax_check_payment_status');
add_action('wp_ajax_nopriv_petshop_check_payment_status', 'petshop_ajax_check_payment_status');

// =============================================
// AJAX: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
// =============================================
function petshop_ajax_update_order_status() {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');
    
    if (!$order_id || !$status) {
        wp_send_json_error(array('message' => 'Thiếu thông tin'));
    }
    
    $allowed_statuses = array('pending', 'processing', 'completed', 'cancelled');
    if (!in_array($status, $allowed_statuses)) {
        wp_send_json_error(array('message' => 'Trạng thái không hợp lệ'));
    }
    
    update_post_meta($order_id, 'order_status', $status);
    
    // Nếu chuyển sang processing = thanh toán thành công
    if ($status === 'processing' || $status === 'completed') {
        update_post_meta($order_id, 'payment_status', 'paid');
        update_post_meta($order_id, 'paid_at', current_time('mysql'));
    }
    
    wp_send_json_success(array(
        'message' => 'Cập nhật thành công',
        'order_status' => $status
    ));
}
add_action('wp_ajax_petshop_update_order_status', 'petshop_ajax_update_order_status');
add_action('wp_ajax_nopriv_petshop_update_order_status', 'petshop_ajax_update_order_status');

// =============================================
// WEBHOOK: NHẬN THÔNG BÁO THANH TOÁN TỪ CASSO/SEPAY
// (Dành cho tích hợp thực tế sau này)
// =============================================
function petshop_payment_webhook_handler() {
    // Kiểm tra nếu đây là request webhook
    if (!isset($_GET['petshop_webhook']) || $_GET['petshop_webhook'] !== 'payment') {
        return;
    }
    
    // Lấy dữ liệu từ webhook
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(array('error' => 'Invalid payload'));
        exit;
    }
    
    // Log webhook data (for debugging)
    error_log('PetShop Payment Webhook: ' . print_r($data, true));
    
    // Xử lý theo định dạng của Casso
    // Casso gửi data có dạng: { "data": [{ "description": "DH123456", "amount": 100000, ... }] }
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $transaction) {
            $description = $transaction['description'] ?? '';
            $amount = floatval($transaction['amount'] ?? 0);
            
            // Tìm mã đơn hàng trong nội dung chuyển khoản
            // Nội dung CK có dạng: "DH" + order_code
            if (preg_match('/DH([A-Z0-9]+)/i', $description, $matches)) {
                $order_code = $matches[1];
                
                // Tìm đơn hàng theo mã
                $orders = get_posts(array(
                    'post_type' => 'petshop_order',
                    'meta_key' => 'order_code',
                    'meta_value' => $order_code,
                    'posts_per_page' => 1
                ));
                
                if (!empty($orders)) {
                    $order = $orders[0];
                    $order_total = floatval(get_post_meta($order->ID, 'order_total', true));
                    
                    // Kiểm tra số tiền (cho phép chênh lệch 1000đ do làm tròn)
                    if (abs($amount - $order_total) <= 1000) {
                        // Cập nhật trạng thái đơn hàng
                        update_post_meta($order->ID, 'order_status', 'processing');
                        update_post_meta($order->ID, 'payment_status', 'paid');
                        update_post_meta($order->ID, 'paid_at', current_time('mysql'));
                        update_post_meta($order->ID, 'payment_transaction_id', $transaction['id'] ?? '');
                        
                        error_log('PetShop: Order #' . $order_code . ' marked as paid via webhook');
                    }
                }
            }
        }
    }
    
    // Trả về success
    http_response_code(200);
    echo json_encode(array('success' => true));
    exit;
}
add_action('init', 'petshop_payment_webhook_handler');

// =============================================
// LẤY DANH SÁCH PHƯƠNG THỨC THANH TOÁN CHO FRONTEND
// =============================================
function petshop_get_available_payment_methods() {
    $settings = petshop_get_payment_settings();
    $methods = array();
    
    if ($settings['cod_enabled']) {
        $methods['cod'] = array(
            'id' => 'cod',
            'title' => $settings['cod_title'],
            'description' => $settings['cod_description'],
            'icon' => 'bi-cash-stack',
            'color' => '#66BCB4'
        );
    }
    
    if ($settings['vnpay_enabled']) {
        $methods['vnpay'] = array(
            'id' => 'vnpay',
            'title' => $settings['vnpay_title'],
            'description' => $settings['vnpay_description'],
            'icon' => 'bi-qr-code-scan',
            'color' => '#0066B3',
            'demo_mode' => $settings['demo_mode'],
            'bank_info' => array(
                'name' => $settings['bank_name'],
                'account' => $settings['bank_account'],
                'holder' => $settings['bank_holder'],
                'branch' => $settings['bank_branch'],
            )
        );
    }
    
    return $methods;
}

// =============================================
// THÊM THÔNG TIN THANH TOÁN VÀO ĐƠN HÀNG
// =============================================
function petshop_add_payment_info_to_order_meta($order_id, $payment_method) {
    $settings = petshop_get_payment_settings();
    $payment_labels = array(
        'cod' => $settings['cod_title'],
        'vnpay' => $settings['vnpay_title'],
    );
    
    update_post_meta($order_id, 'payment_method', $payment_method);
    update_post_meta($order_id, 'payment_method_title', $payment_labels[$payment_method] ?? $payment_method);
    
    if ($payment_method === 'cod') {
        update_post_meta($order_id, 'payment_status', 'pending');
    }
}

// =============================================
// HIỂN THỊ TRẠNG THÁI THANH TOÁN TRONG ADMIN
// =============================================
function petshop_get_payment_status_label($status) {
    $labels = array(
        'pending' => array('label' => 'Chờ thanh toán', 'color' => '#dba617', 'bg' => '#fff8e5'),
        'paid' => array('label' => 'Đã thanh toán', 'color' => '#00a32a', 'bg' => '#e7f5e7'),
        'failed' => array('label' => 'Thanh toán thất bại', 'color' => '#d63638', 'bg' => '#fcf0f1'),
        'refunded' => array('label' => 'Đã hoàn tiền', 'color' => '#2271b1', 'bg' => '#f0f6fc'),
    );
    
    return $labels[$status] ?? array('label' => $status, 'color' => '#646970', 'bg' => '#f6f7f7');
}
