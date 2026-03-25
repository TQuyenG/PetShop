<?php
/**
 * PetShop Custom Login/Register Page
 * Trang đăng nhập/đăng ký tùy chỉnh
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// GOOGLE OAUTH HANDLING
// =============================================
add_action('init', 'petshop_handle_google_oauth');
function petshop_handle_google_oauth() {
    // Handle Google OAuth callback
    if (isset($_GET['google_callback']) && isset($_GET['code'])) {
        $google_client_id = get_option('petshop_google_client_id', '');
        $google_client_secret = get_option('petshop_google_client_secret', '');
        
        if (empty($google_client_id) || empty($google_client_secret)) {
            return;
        }
        
        $code = sanitize_text_field($_GET['code']);
        $is_login = strpos($_SERVER['REQUEST_URI'], '/dang-nhap/') !== false;
        $redirect_uri = $is_login ? home_url('/dang-nhap/?google_callback=1') : home_url('/dang-ky/?google_callback=1');
        
        // Exchange code for access token
        $token_response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $code,
                'client_id' => $google_client_id,
                'client_secret' => $google_client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            )
        ));
        
        if (is_wp_error($token_response)) {
            wp_redirect(home_url('/dang-nhap/?error=google_failed'));
            exit;
        }
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        
        if (!isset($token_data['access_token'])) {
            wp_redirect(home_url('/dang-nhap/?error=google_failed'));
            exit;
        }
        
        // Get user info from Google
        $user_response = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token_data['access_token']
            )
        ));
        
        if (is_wp_error($user_response)) {
            wp_redirect(home_url('/dang-nhap/?error=google_failed'));
            exit;
        }
        
        $google_user = json_decode(wp_remote_retrieve_body($user_response), true);
        
        if (!isset($google_user['email'])) {
            wp_redirect(home_url('/dang-nhap/?error=google_no_email'));
            exit;
        }
        
        $email = sanitize_email($google_user['email']);
        $name = sanitize_text_field($google_user['name'] ?? '');
        $google_id = sanitize_text_field($google_user['id'] ?? '');
        
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if ($user) {
            // Login existing user
            wp_set_auth_cookie($user->ID, true);
            update_user_meta($user->ID, 'last_login', current_time('mysql'));
            update_user_meta($user->ID, 'google_id', $google_id);
            wp_redirect(home_url('/tai-khoan/'));
            exit;
        } else {
            // Register new user
            $username = sanitize_user(strtok($email, '@'), true);
            $original_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
            
            $password = wp_generate_password(16, true, true);
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                wp_redirect(home_url('/dang-ky/?error=register_failed'));
                exit;
            }
            
            // Update user info
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => $name
            ));
            
            update_user_meta($user_id, 'google_id', $google_id);
            
            // Set role
            $user = new WP_User($user_id);
            $user->set_role('petshop_customer');
            
            // Login the new user
            wp_set_auth_cookie($user_id, true);
            update_user_meta($user_id, 'last_login', current_time('mysql'));
            
            wp_redirect(home_url('/tai-khoan/?welcome=1'));
            exit;
        }
    }
}

// =============================================
// GOOGLE OAUTH SETTINGS IN ADMIN
// =============================================
add_action('admin_menu', 'petshop_google_oauth_settings_menu');
function petshop_google_oauth_settings_menu() {
    add_submenu_page(
        'options-general.php',
        'Google OAuth',
        'Google OAuth',
        'manage_options',
        'petshop-google-oauth',
        'petshop_google_oauth_settings_page'
    );
}

function petshop_google_oauth_settings_page() {
    if (isset($_POST['save_google_oauth']) && wp_verify_nonce($_POST['_wpnonce'], 'petshop_google_oauth')) {
        update_option('petshop_google_client_id', sanitize_text_field($_POST['google_client_id']));
        update_option('petshop_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt Google OAuth!</p></div>';
    }
    
    $client_id = get_option('petshop_google_client_id', '');
    $client_secret = get_option('petshop_google_client_secret', '');
    ?>
    <div class="wrap">
        <h1>Cài đặt Google OAuth</h1>
        <p>Để sử dụng đăng nhập Google, bạn cần tạo dự án trên <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</p>
        
        <form method="post">
            <?php wp_nonce_field('petshop_google_oauth'); ?>
            <table class="form-table">
                <tr>
                    <th>Google Client ID</th>
                    <td>
                        <input type="text" name="google_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>Google Client Secret</th>
                    <td>
                        <input type="password" name="google_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>Redirect URIs</th>
                    <td>
                        <code><?php echo home_url('/dang-nhap/?google_callback=1'); ?></code><br>
                        <code><?php echo home_url('/dang-ky/?google_callback=1'); ?></code>
                        <p class="description">Thêm các URL này vào phần "Authorized redirect URIs" trong Google Console.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_google_oauth" class="button-primary" value="Lưu cài đặt">
            </p>
        </form>
    </div>
    <?php
}

// =============================================
// CUSTOM LOGIN PAGE STYLES
// =============================================
function petshop_login_styles() {
    ?>
    <style>
    /* Reset */
    body.login {
        background: linear-gradient(135deg, #FDF8F3 0%, #F5EDE4 100%);
        font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, sans-serif;
        min-height: 100vh;
    }
    
    /* Logo */
    body.login #login h1 a {
        background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/logo.png');
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
        width: 200px;
        height: 80px;
        margin-bottom: 20px;
    }
    
    /* Form Container */
    body.login #login {
        width: 400px;
        padding: 0;
    }
    
    body.login #loginform,
    body.login #registerform,
    body.login #lostpasswordform {
        background: #fff;
        border: none;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(93, 78, 55, 0.15);
        padding: 40px 35px;
        margin-top: 0;
    }
    
    /* Header Text - Login */
    body.login.login-action-login #login::before {
        content: 'Chào mừng bạn đến với PetShop';
        display: block;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #5D4E37;
        margin-bottom: 10px;
    }
    
    body.login.login-action-login #login::after {
        content: 'Đăng nhập để trải nghiệm mua sắm tuyệt vời';
        display: block;
        text-align: center;
        font-size: 0.95rem;
        color: #7A6B5A;
        margin-bottom: 25px;
    }
    
    /* Header Text - Register */
    body.login.login-action-register #login::before {
        content: '🐾 Tạo tài khoản mới';
        display: block;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #5D4E37;
        margin-bottom: 10px;
    }
    
    body.login.login-action-register #login::after {
        content: 'Đăng ký để nhận ưu đãi và theo dõi đơn hàng';
        display: block;
        text-align: center;
        font-size: 0.95rem;
        color: #7A6B5A;
        margin-bottom: 25px;
    }
    
    /* Header Text - Lost Password */
    body.login.login-action-lostpassword #login::before {
        content: '🔐 Quên mật khẩu?';
        display: block;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #5D4E37;
        margin-bottom: 10px;
    }
    
    body.login.login-action-lostpassword #login::after {
        content: 'Nhập email để nhận link đặt lại mật khẩu';
        display: block;
        text-align: center;
        font-size: 0.95rem;
        color: #7A6B5A;
        margin-bottom: 25px;
    }
    
    /* Header Text - Reset Password */
    body.login.login-action-rp #login::before {
        content: '🔑 Đặt lại mật khẩu';
        display: block;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #5D4E37;
        margin-bottom: 10px;
    }
    
    body.login.login-action-rp #login::after {
        content: 'Nhập mật khẩu mới cho tài khoản của bạn';
        display: block;
        text-align: center;
        font-size: 0.95rem;
        color: #7A6B5A;
        margin-bottom: 25px;
    }
    
    /* Labels */
    body.login #loginform label,
    body.login #registerform label,
    body.login #lostpasswordform label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #5D4E37;
        margin-bottom: 8px;
        display: block;
    }
    
    /* Input Fields */
    body.login input[type="text"],
    body.login input[type="password"],
    body.login input[type="email"] {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #E8CCAD;
        border-radius: 12px;
        font-size: 1rem;
        font-family: 'Quicksand', sans-serif;
        background: #fff;
        transition: all 0.3s ease;
        box-sizing: border-box;
        margin-top: 5px;
    }
    
    body.login input[type="text"]:focus,
    body.login input[type="password"]:focus,
    body.login input[type="email"]:focus {
        border-color: #EC802B;
        box-shadow: 0 0 0 4px rgba(236, 128, 43, 0.1);
        outline: none;
    }
    
    /* Submit Button */
    body.login #wp-submit {
        width: 100%;
        padding: 16px 30px;
        background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
        border: none;
        border-radius: 50px;
        color: #fff;
        font-size: 1.1rem;
        font-weight: 700;
        font-family: 'Quicksand', sans-serif;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: none;
        box-shadow: 0 8px 25px rgba(236, 128, 43, 0.3);
        margin-top: 15px;
    }
    
    body.login #wp-submit:hover {
        background: linear-gradient(135deg, #d6721f 0%, #e88a3f 100%);
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(236, 128, 43, 0.4);
    }
    
    /* Remember Me */
    body.login .forgetmenot {
        margin-top: 15px;
    }
    
    body.login .forgetmenot label {
        font-size: 0.9rem;
        color: #7A6B5A;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    body.login input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #EC802B;
    }
    
    /* Links */
    body.login #nav,
    body.login #backtoblog {
        text-align: center;
        margin-top: 20px;
        padding: 0;
    }
    
    body.login #nav a,
    body.login #backtoblog a {
        color: #EC802B;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s;
    }
    
    body.login #nav a:hover,
    body.login #backtoblog a:hover {
        color: #d6721f;
        text-decoration: underline;
    }
    
    /* Error/Success Messages */
    body.login #login_error,
    body.login .message,
    body.login .success {
        border: none;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }
    
    body.login #login_error {
        background: #FEE2E2;
        color: #DC2626;
        border-left: 4px solid #DC2626;
    }
    
    body.login .message {
        background: #DBEAFE;
        color: #1D4ED8;
        border-left: 4px solid #1D4ED8;
    }
    
    body.login .success {
        background: #D1FAE5;
        color: #059669;
        border-left: 4px solid #059669;
    }
    
    /* Hide default WordPress elements */
    body.login .privacy-policy-page-link,
    body.login .language-switcher {
        display: none;
    }
    
    /* Footer */
    body.login #backtoblog {
        margin-top: 25px;
    }
    
    body.login #backtoblog a::before {
        content: '← ';
    }
    
    /* Decorative elements */
    body.login::before {
        content: '';
        position: fixed;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: linear-gradient(135deg, rgba(236, 128, 43, 0.1), rgba(102, 188, 180, 0.1));
        border-radius: 50%;
        z-index: -1;
    }
    
    body.login::after {
        content: '';
        position: fixed;
        bottom: -50px;
        left: -50px;
        width: 200px;
        height: 200px;
        background: linear-gradient(135deg, rgba(102, 188, 180, 0.15), rgba(236, 128, 43, 0.1));
        border-radius: 50%;
        z-index: -1;
    }
    
    /* Responsive */
    @media (max-width: 480px) {
        body.login #login {
            width: 90%;
            margin: 20px auto;
        }
        
        body.login #loginform,
        body.login #registerform,
        body.login #lostpasswordform {
            padding: 30px 25px;
        }
        
        body.login #login::before {
            font-size: 1.3rem;
        }
    }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'petshop_login_styles');

// =============================================
// THAY ĐỔI LOGO LINK VÀ TITLE
// =============================================
function petshop_login_logo_url() {
    return home_url('/');
}
add_filter('login_headerurl', 'petshop_login_logo_url');

function petshop_login_logo_title() {
    return get_bloginfo('name') . ' - Yêu thương thú cưng';
}
add_filter('login_headertext', 'petshop_login_logo_title');

// =============================================
// THAY ĐỔI TEXT TRÊN TRANG LOGIN
// =============================================
function petshop_login_message($message) {
    if (empty($message)) {
        return '';
    }
    return $message;
}
add_filter('login_message', 'petshop_login_message');

// =============================================
// THÊM GOOGLE FONTS
// =============================================
function petshop_login_enqueue_scripts() {
    wp_enqueue_style('google-fonts-quicksand', 'https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap');
}
add_action('login_enqueue_scripts', 'petshop_login_enqueue_scripts');

// =============================================
// REDIRECT SAU KHI ĐĂNG NHẬP
// =============================================
function petshop_login_redirect($redirect_to, $request, $user) {
    // Nếu là admin thì giữ nguyên redirect
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles) || in_array('petshop_manager', $user->roles)) {
            return admin_url();
        }
    }
    
    // Nếu có redirect_to từ URL thì dùng nó
    if (!empty($redirect_to) && $redirect_to !== admin_url()) {
        return $redirect_to;
    }
    
    // Mặc định redirect về trang tài khoản
    return home_url('/tai-khoan/');
}
add_filter('login_redirect', 'petshop_login_redirect', 10, 3);

// =============================================
// REDIRECT SAU KHI ĐĂNG KÝ
// =============================================
function petshop_registration_redirect() {
    return home_url('/tai-khoan/?registered=1');
}
add_filter('registration_redirect', 'petshop_registration_redirect');

// =============================================
// THAY ĐỔI ERROR MESSAGES
// =============================================
function petshop_login_errors($errors) {
    // Thay đổi message lỗi cho bảo mật
    if (isset($errors->errors['invalid_username'])) {
        $errors->errors['invalid_username'][0] = 'Tên đăng nhập hoặc email không đúng.';
    }
    if (isset($errors->errors['incorrect_password'])) {
        $errors->errors['incorrect_password'][0] = 'Mật khẩu không chính xác. <a href="' . wp_lostpassword_url() . '">Quên mật khẩu?</a>';
    }
    if (isset($errors->errors['empty_username'])) {
        $errors->errors['empty_username'][0] = 'Vui lòng nhập tên đăng nhập hoặc email.';
    }
    if (isset($errors->errors['empty_password'])) {
        $errors->errors['empty_password'][0] = 'Vui lòng nhập mật khẩu.';
    }
    return $errors;
}
add_filter('wp_login_errors', 'petshop_login_errors');

// =============================================
// THÊM CUSTOM FOOTER
// =============================================
function petshop_login_footer() {
    ?>
    <div style="text-align: center; margin-top: 30px; padding: 20px; color: #7A6B5A; font-size: 0.85rem;">
        <p style="margin: 0;">© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. Yêu thương thú cưng 🐾</p>
    </div>
    <?php
}
add_action('login_footer', 'petshop_login_footer');

// =============================================
// THAY ĐỔI "← Go to site" TEXT
// =============================================
function petshop_login_site_url_title() {
    return 'Quay lại ' . get_bloginfo('name');
}
add_filter('login_site_html_link', function($link) {
    return str_replace('&larr;', '←', $link);
});

// =============================================
// THÊM TRƯỜNG SỐ ĐIỆN THOẠI VÀO FORM ĐĂNG KÝ
// =============================================
function petshop_register_form_fields() {
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $fullname = isset($_POST['fullname']) ? sanitize_text_field($_POST['fullname']) : '';
    ?>
    <p>
        <label for="fullname">Họ và tên<br />
            <input type="text" name="fullname" id="fullname" class="input" value="<?php echo esc_attr($fullname); ?>" size="25" required />
        </label>
    </p>
    <p>
        <label for="phone">Số điện thoại<br />
            <input type="tel" name="phone" id="phone" class="input" value="<?php echo esc_attr($phone); ?>" size="25" placeholder="0909 xxx xxx" />
        </label>
    </p>
    <?php
}
add_action('register_form', 'petshop_register_form_fields');

// =============================================
// VALIDATE FORM ĐĂNG KÝ
// =============================================
function petshop_registration_errors($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['fullname']) || trim($_POST['fullname']) === '') {
        $errors->add('fullname_error', '<strong>Lỗi</strong>: Vui lòng nhập họ và tên.');
    }
    return $errors;
}
add_filter('registration_errors', 'petshop_registration_errors', 10, 3);

// =============================================
// LƯU THÔNG TIN BỔ SUNG KHI ĐĂNG KÝ
// =============================================
function petshop_user_register($user_id) {
    // Lưu họ tên
    if (isset($_POST['fullname'])) {
        $fullname = sanitize_text_field($_POST['fullname']);
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $fullname,
            'first_name' => $fullname
        ));
    }
    
    // Lưu số điện thoại
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'petshop_phone', sanitize_text_field($_POST['phone']));
    }
    
    // Set role là petshop_customer
    $user = new WP_User($user_id);
    $user->set_role('petshop_customer');
    
    // Tạo mã giới thiệu
    if (function_exists('petshop_generate_referral_code')) {
        petshop_generate_referral_code($user_id);
    }
}
add_action('user_register', 'petshop_user_register');

// =============================================
// THAY ĐỔI LABEL VÀ THÔNG BÁO ĐĂNG KÝ
// =============================================
function petshop_register_messages() {
    // Thêm script để thay đổi text
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Thay đổi label Username thành Email hoặc Tên đăng nhập
        var userLabel = document.querySelector('#registerform label[for="user_login"]');
        if (userLabel) {
            userLabel.innerHTML = 'Tên đăng nhập<br><input type="text" name="user_login" id="user_login" class="input" value="" size="20" autocapitalize="off" autocomplete="username">';
        }
        
        var emailLabel = document.querySelector('#registerform label[for="user_email"]');
        if (emailLabel) {
            emailLabel.innerHTML = 'Email<br><input type="email" name="user_email" id="user_email" class="input" value="" size="25">';
        }
        
        // Thay đổi text nút đăng ký
        var submitBtn = document.querySelector('#registerform #wp-submit');
        if (submitBtn) {
            submitBtn.value = 'Đăng ký ngay';
        }
        
        // Thay đổi text "Registration confirmation will be emailed to you."
        var regMessage = document.querySelector('#reg_passmail');
        if (regMessage) {
            regMessage.innerHTML = 'Mật khẩu sẽ được gửi về email của bạn.';
        }
    });
    </script>
    <?php
}
add_action('login_footer', 'petshop_register_messages');

// =============================================
// THÊM LINK ĐĂNG KÝ/ĐĂNG NHẬP RÕ RÀNG HƠN
// =============================================
function petshop_login_form_bottom() {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
    
    if ($action === 'login') {
        ?>
        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #E8CCAD;">
            <p style="color: #7A6B5A; margin: 0;">Chưa có tài khoản?</p>
            <a href="<?php echo wp_registration_url(); ?>" style="display: inline-block; margin-top: 10px; padding: 12px 30px; background: #66BCB4; color: #fff; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                🐾 Đăng ký ngay
            </a>
        </div>
        <?php
    } elseif ($action === 'register') {
        ?>
        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #E8CCAD;">
            <p style="color: #7A6B5A; margin: 0;">Đã có tài khoản?</p>
            <a href="<?php echo wp_login_url(); ?>" style="display: inline-block; margin-top: 10px; padding: 12px 30px; background: #EC802B; color: #fff; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                Đăng nhập
            </a>
        </div>
        <?php
    }
}
add_action('login_form', 'petshop_login_form_bottom', 99);
add_action('register_form', 'petshop_login_form_bottom', 99);
