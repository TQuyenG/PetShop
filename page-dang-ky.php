<?php
/**
 * Template Name: Trang Đăng Ký
 * 
 * @package PetShop
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/tai-khoan/'));
    exit;
}

$register_error = '';
$register_success = false;
$form_data = array(
    'email' => '',
    'fullname' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'district' => ''
);

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['petshop_register'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'petshop_register_nonce')) {
        $register_error = 'Phiên đăng ký không hợp lệ. Vui lòng thử lại.';
    } else {
        // Get form data
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $fullname = sanitize_text_field($_POST['fullname'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $address = sanitize_text_field($_POST['address'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $district = sanitize_text_field($_POST['district'] ?? '');
        
        // Store form data for repopulation
        $form_data = compact('email', 'fullname', 'phone', 'address', 'city', 'district');
        
        // Validate required fields
        if (empty($email)) {
            $register_error = 'Vui lòng nhập địa chỉ email.';
        } elseif (!is_email($email)) {
            $register_error = 'Địa chỉ email không hợp lệ.';
        } elseif (email_exists($email)) {
            $register_error = 'Email này đã được sử dụng. <a href="' . home_url('/dang-nhap/') . '">Đăng nhập?</a>';
        } elseif (empty($password)) {
            $register_error = 'Vui lòng nhập mật khẩu.';
        } elseif (strlen($password) < 6) {
            $register_error = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Mật khẩu xác nhận không khớp.';
        } else {
            // Generate username from email
            $username = sanitize_user(strtok($email, '@'), true);
            $original_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
            
            // Create user
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $register_error = $user_id->get_error_message();
            } else {
                // Update user info
                $display_name = !empty($fullname) ? $fullname : $username;
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $display_name,
                    'first_name' => $fullname
                ));
                
                // Save additional meta
                if (!empty($phone)) {
                    update_user_meta($user_id, 'petshop_phone', $phone);
                }
                
                // Save address if provided
                if (!empty($address) || !empty($city) || !empty($district)) {
                    $address_data = array(
                        'id' => 1,
                        'name' => $fullname,
                        'phone' => $phone,
                        'address' => $address,
                        'city' => $city,
                        'district' => $district,
                        'ward' => '',
                        'is_default' => true
                    );
                    update_user_meta($user_id, 'petshop_addresses', array($address_data));
                    update_user_meta($user_id, 'petshop_default_address_id', 1);
                }
                
                // Set role
                $user = new WP_User($user_id);
                $user->set_role('petshop_customer');
                
                // Generate referral code if function exists
                if (function_exists('petshop_generate_referral_code')) {
                    petshop_generate_referral_code($user_id);
                }
                
                // Send confirmation email
                petshop_send_registration_email($user_id, $email, $display_name);
                
                $register_success = true;
            }
        }
    }
}

// Send registration confirmation email
function petshop_send_registration_email($user_id, $email, $name) {
    $site_name = get_bloginfo('name');
    $login_url = home_url('/dang-nhap/');
    $shop_url = home_url('/san-pham/');
    
    $subject = "🎉 Chào mừng bạn đến với {$site_name}!";
    
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #EC802B, #F5994D); padding: 30px; text-align: center;'>
            <h1 style='color: #fff; margin: 0;'>🐾 {$site_name}</h1>
            <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0;'>Yêu thương thú cưng</p>
        </div>
        
        <div style='padding: 30px; background: #fff;'>
            <h2 style='color: #5D4E37; margin-top: 0;'>Xin chào {$name}! 👋</h2>
            
            <p style='color: #666; line-height: 1.6;'>
                Cảm ơn bạn đã đăng ký tài khoản tại <strong>{$site_name}</strong>. 
                Chúng tôi rất vui được chào đón bạn!
            </p>
            
            <div style='background: #FDF8F3; padding: 20px; border-radius: 12px; margin: 20px 0;'>
                <h3 style='color: #EC802B; margin-top: 0;'>🎁 Ưu đãi dành cho thành viên mới:</h3>
                <ul style='color: #5D4E37; padding-left: 20px;'>
                    <li>Giảm 10% cho đơn hàng đầu tiên</li>
                    <li>Tích điểm với mỗi đơn hàng</li>
                    <li>Nhận thông báo ưu đãi độc quyền</li>
                    <li>Hỗ trợ khách hàng 24/7</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$shop_url}' style='display: inline-block; background: linear-gradient(135deg, #EC802B, #F5994D); color: #fff; padding: 15px 40px; text-decoration: none; border-radius: 30px; font-weight: bold;'>
                    Bắt đầu mua sắm
                </a>
            </div>
            
            <p style='color: #666; font-size: 14px; margin-bottom: 0;'>
                Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi qua email hoặc chat.
            </p>
        </div>
        
        <div style='background: #5D4E37; padding: 20px; text-align: center;'>
            <p style='color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;'>
                © " . date('Y') . " {$site_name}. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
    );
    
    wp_mail($email, $subject, $message, $headers);
}

// Get Google OAuth URL if configured
$google_client_id = get_option('petshop_google_client_id', '');
$google_oauth_url = '';
if ($google_client_id) {
    $redirect_uri = home_url('/dang-ky/?google_callback=1');
    $google_oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ]);
}

get_header();
?>

<style>
.auth-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #FDF8F3 0%, #F5EDE4 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}

.auth-page::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, rgba(236, 128, 43, 0.1), rgba(102, 188, 180, 0.1));
    border-radius: 50%;
}

.auth-page::after {
    content: '';
    position: absolute;
    bottom: -50px;
    left: -50px;
    width: 200px;
    height: 200px;
    background: linear-gradient(135deg, rgba(102, 188, 180, 0.15), rgba(236, 128, 43, 0.1));
    border-radius: 50%;
}

.auth-container {
    width: 100%;
    max-width: 500px;
    position: relative;
    z-index: 1;
}

.auth-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(93, 78, 55, 0.15);
    overflow: hidden;
}

.auth-header {
    text-align: center;
    padding: 35px 40px 25px;
    background: linear-gradient(135deg, #66BCB4, #7ECEC6);
    color: #fff;
}

.auth-header .logo {
    width: 70px;
    height: 70px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.auth-header .logo i {
    font-size: 2rem;
    color: #66BCB4;
}

.auth-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 10px;
}

.auth-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.auth-body {
    padding: 35px 40px;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.auth-form label {
    display: block;
    font-weight: 600;
    color: #5D4E37;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.auth-form label .required {
    color: #DC2626;
}

.auth-form label .optional {
    color: #999;
    font-weight: 400;
    font-size: 0.85rem;
}

.auth-form .input-group {
    position: relative;
}

.auth-form .input-group i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #7A6B5A;
    font-size: 1.1rem;
}

.auth-form input[type="text"],
.auth-form input[type="email"],
.auth-form input[type="password"],
.auth-form input[type="tel"],
.auth-form select {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 2px solid #E8CCAD;
    border-radius: 12px;
    font-size: 1rem;
    font-family: 'Quicksand', sans-serif;
    transition: all 0.3s;
    box-sizing: border-box;
}

.auth-form select {
    padding-left: 48px;
    cursor: pointer;
    appearance: none;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237A6B5A' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 16px center;
    background-color: #fff;
}

.auth-form input:focus,
.auth-form select:focus {
    border-color: #66BCB4;
    box-shadow: 0 0 0 4px rgba(102, 188, 180, 0.1);
    outline: none;
}

.auth-form .password-toggle {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #7A6B5A;
    cursor: pointer;
    padding: 5px;
}

.auth-form .password-toggle:hover {
    color: #66BCB4;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-section {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px dashed #E8CCAD;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: #5D4E37;
    font-weight: 600;
}

.form-section-title i {
    color: #66BCB4;
}

.btn-register {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #66BCB4, #7ECEC6);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: 700;
    font-family: 'Quicksand', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 8px 25px rgba(102, 188, 180, 0.3);
    margin-top: 25px;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(102, 188, 180, 0.4);
}

.divider {
    display: flex;
    align-items: center;
    margin: 25px 0;
    color: #7A6B5A;
    font-size: 0.9rem;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #E8CCAD;
}

.divider span {
    padding: 0 15px;
}

.social-login {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-social {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    font-family: 'Quicksand', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-google {
    background: #fff;
    border: 2px solid #E8CCAD;
    color: #5D4E37;
}

.btn-google:hover {
    border-color: #EA4335;
    background: #FEF7F6;
}

.btn-google svg {
    width: 20px;
    height: 20px;
}

.auth-footer {
    text-align: center;
    padding: 20px 40px 30px;
    background: #FAFAFA;
    border-top: 1px solid #E8CCAD;
}

.auth-footer p {
    margin: 0;
    color: #7A6B5A;
    font-size: 0.95rem;
}

.auth-footer a {
    color: #66BCB4;
    font-weight: 700;
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 0.95rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.alert-error {
    background: #FEE2E2;
    color: #DC2626;
    border-left: 4px solid #DC2626;
}

.alert-success {
    background: #D1FAE5;
    color: #059669;
    border-left: 4px solid #059669;
}

.alert a {
    color: inherit;
    font-weight: 600;
}

.success-card {
    text-align: center;
    padding: 50px 40px;
}

.success-card .success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
}

.success-card .success-icon i {
    font-size: 3rem;
    color: #059669;
}

.success-card h2 {
    color: #059669;
    margin: 0 0 15px;
}

.success-card p {
    color: #666;
    margin: 0 0 25px;
    line-height: 1.6;
}

.success-card .btn-login {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 35px;
    background: linear-gradient(135deg, #EC802B, #F5994D);
    color: #fff;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 700;
    transition: all 0.3s;
}

.success-card .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(236, 128, 43, 0.4);
}

.back-home {
    text-align: center;
    margin-top: 25px;
}

.back-home a {
    color: #7A6B5A;
    text-decoration: none;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.back-home a:hover {
    color: #66BCB4;
}

.password-strength {
    margin-top: 8px;
    font-size: 0.85rem;
}

.password-strength .strength-bar {
    height: 4px;
    background: #E8CCAD;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.password-strength .strength-fill {
    height: 100%;
    width: 0;
    transition: all 0.3s;
}

.password-strength.weak .strength-fill {
    width: 33%;
    background: #DC2626;
}

.password-strength.medium .strength-fill {
    width: 66%;
    background: #F59E0B;
}

.password-strength.strong .strength-fill {
    width: 100%;
    background: #059669;
}

.password-strength .strength-text {
    color: #7A6B5A;
}

@media (max-width: 520px) {
    .auth-page {
        padding: 20px 15px;
    }
    
    .auth-header {
        padding: 30px 25px 20px;
    }
    
    .auth-header h1 {
        font-size: 1.5rem;
    }
    
    .auth-body {
        padding: 25px;
    }
    
    .auth-footer {
        padding: 20px 25px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <?php if ($register_success) : ?>
            <div class="success-card">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2>Đăng ký thành công!</h2>
                <p>
                    Chào mừng bạn đến với PetShop! 🐾<br>
                    Một email xác nhận đã được gửi đến địa chỉ email của bạn.
                </p>
                <a href="<?php echo home_url('/dang-nhap/'); ?>" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Đăng nhập ngay
                </a>
            </div>
            <?php else : ?>
            
            <div class="auth-header">
                <div class="logo">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h1>Tạo tài khoản</h1>
                <p>Đăng ký để nhận ưu đãi và theo dõi đơn hàng</p>
            </div>
            
            <div class="auth-body">
                <?php if ($register_error) : ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span><?php echo $register_error; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="post" class="auth-form" id="registerForm">
                    <?php wp_nonce_field('petshop_register_nonce'); ?>
                    <input type="hidden" name="petshop_register" value="1">
                    
                    <!-- Required Fields -->
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" required 
                                   placeholder="Nhập địa chỉ email..."
                                   value="<?php echo esc_attr($form_data['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Tối thiểu 6 ký tự..." minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="bi bi-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar"><div class="strength-fill"></div></div>
                            <span class="strength-text"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Nhập lại mật khẩu...">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                <i class="bi bi-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Optional Fields -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-person-vcard"></i>
                            Thông tin cá nhân <span class="optional">(không bắt buộc)</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="fullname">Họ và tên</label>
                            <div class="input-group">
                                <i class="bi bi-person"></i>
                                <input type="text" id="fullname" name="fullname" 
                                       placeholder="Nhập họ và tên..."
                                       value="<?php echo esc_attr($form_data['fullname']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <div class="input-group">
                                <i class="bi bi-telephone"></i>
                                <input type="tel" id="phone" name="phone" 
                                       placeholder="VD: 0909 xxx xxx"
                                       value="<?php echo esc_attr($form_data['phone']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-geo-alt"></i>
                            Địa chỉ giao hàng <span class="optional">(không bắt buộc)</span>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Tỉnh/Thành phố</label>
                                <div class="input-group">
                                    <i class="bi bi-building"></i>
                                    <select id="city" name="city">
                                        <option value="">Chọn tỉnh/thành</option>
                                        <option value="Hà Nội" <?php selected($form_data['city'], 'Hà Nội'); ?>>Hà Nội</option>
                                        <option value="TP. Hồ Chí Minh" <?php selected($form_data['city'], 'TP. Hồ Chí Minh'); ?>>TP. Hồ Chí Minh</option>
                                        <option value="Đà Nẵng" <?php selected($form_data['city'], 'Đà Nẵng'); ?>>Đà Nẵng</option>
                                        <option value="Hải Phòng" <?php selected($form_data['city'], 'Hải Phòng'); ?>>Hải Phòng</option>
                                        <option value="Cần Thơ" <?php selected($form_data['city'], 'Cần Thơ'); ?>>Cần Thơ</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="district">Quận/Huyện</label>
                                <div class="input-group">
                                    <i class="bi bi-signpost-2"></i>
                                    <input type="text" id="district" name="district" 
                                           placeholder="Nhập quận/huyện..."
                                           value="<?php echo esc_attr($form_data['district']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ chi tiết</label>
                            <div class="input-group">
                                <i class="bi bi-house"></i>
                                <input type="text" id="address" name="address" 
                                       placeholder="Số nhà, tên đường, phường/xã..."
                                       value="<?php echo esc_attr($form_data['address']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="bi bi-person-plus"></i>
                        Đăng ký tài khoản
                    </button>
                </form>
                
                <?php if ($google_oauth_url) : ?>
                <div class="divider">
                    <span>hoặc đăng ký với</span>
                </div>
                
                <div class="social-login">
                    <a href="<?php echo esc_url($google_oauth_url); ?>" class="btn-social btn-google">
                        <svg viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Đăng ký với Google
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="auth-footer">
                <p>Đã có tài khoản? <a href="<?php echo home_url('/dang-nhap/'); ?>">Đăng nhập</a></p>
            </div>
            
            <?php endif; ?>
        </div>
        
        <div class="back-home">
            <a href="<?php echo home_url(); ?>">
                <i class="bi bi-arrow-left"></i>
                Quay lại trang chủ
            </a>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    var passwordInput = document.getElementById(inputId);
    var toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    var password = this.value;
    var strength = document.getElementById('passwordStrength');
    var text = strength.querySelector('.strength-text');
    
    strength.classList.remove('weak', 'medium', 'strong');
    
    if (password.length === 0) {
        text.textContent = '';
        return;
    }
    
    var score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 10) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    if (score <= 2) {
        strength.classList.add('weak');
        text.textContent = 'Yếu - Nên thêm số và ký tự đặc biệt';
    } else if (score <= 3) {
        strength.classList.add('medium');
        text.textContent = 'Trung bình';
    } else {
        strength.classList.add('strong');
        text.textContent = 'Mạnh';
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    var password = document.getElementById('password').value;
    if (this.value !== password) {
        this.style.borderColor = '#DC2626';
    } else {
        this.style.borderColor = '#059669';
    }
});
</script>

<?php get_footer(); ?>
