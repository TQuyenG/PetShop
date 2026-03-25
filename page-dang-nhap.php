<?php
/**
 * Template Name: Trang Đăng Nhập
 * 
 * @package PetShop
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/tai-khoan/'));
    exit;
}

// Handle login
$login_error = '';
$login_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['petshop_login'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'petshop_login_nonce')) {
        $login_error = 'Phiên đăng nhập không hợp lệ. Vui lòng thử lại.';
    } else {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        // Check if username is email
        if (is_email($username)) {
            $user = get_user_by('email', $username);
            if ($user) {
                $username = $user->user_login;
            }
        }
        
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );
        
        $user = wp_signon($creds, is_ssl());
        
        if (is_wp_error($user)) {
            $login_error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            // Update last login
            update_user_meta($user->ID, 'last_login', current_time('mysql'));
            
            // Redirect
            $redirect = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/tai-khoan/');
            wp_redirect($redirect);
            exit;
        }
    }
}

// Get Google OAuth URL if configured
$google_client_id = get_option('petshop_google_client_id', '');
$google_oauth_url = '';
if ($google_client_id) {
    $redirect_uri = home_url('/dang-nhap/?google_callback=1');
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
    max-width: 450px;
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
    padding: 40px 40px 30px;
    background: linear-gradient(135deg, #5D4E37, #7A6B5A);
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
    color: #EC802B;
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
    padding: 40px;
}

.auth-form .form-group {
    margin-bottom: 24px;
}

.auth-form label {
    display: block;
    font-weight: 600;
    color: #5D4E37;
    margin-bottom: 8px;
    font-size: 0.95rem;
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
.auth-form input[type="tel"] {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 2px solid #E8CCAD;
    border-radius: 12px;
    font-size: 1rem;
    font-family: 'Quicksand', sans-serif;
    transition: all 0.3s;
    box-sizing: border-box;
}

.auth-form input:focus {
    border-color: #EC802B;
    box-shadow: 0 0 0 4px rgba(236, 128, 43, 0.1);
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
    color: #EC802B;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 10px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.remember-me input {
    width: 18px;
    height: 18px;
    accent-color: #EC802B;
}

.remember-me span {
    font-size: 0.9rem;
    color: #5D4E37;
}

.forgot-password {
    color: #EC802B;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.forgot-password:hover {
    text-decoration: underline;
}

.btn-login {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #EC802B, #F5994D);
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
    box-shadow: 0 8px 25px rgba(236, 128, 43, 0.3);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(236, 128, 43, 0.4);
}

.divider {
    display: flex;
    align-items: center;
    margin: 30px 0;
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

.btn-wordpress {
    background: #21759B;
    border: 2px solid #21759B;
    color: #fff;
}

.btn-wordpress:hover {
    background: #1a5f7c;
}

.auth-footer {
    text-align: center;
    padding: 25px 40px 35px;
    background: #FAFAFA;
    border-top: 1px solid #E8CCAD;
}

.auth-footer p {
    margin: 0;
    color: #7A6B5A;
    font-size: 0.95rem;
}

.auth-footer a {
    color: #EC802B;
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
    align-items: center;
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
    color: #EC802B;
}

@media (max-width: 480px) {
    .auth-page {
        padding: 20px 15px;
    }
    
    .auth-header {
        padding: 30px 25px 25px;
    }
    
    .auth-header h1 {
        font-size: 1.5rem;
    }
    
    .auth-body {
        padding: 30px 25px;
    }
    
    .auth-footer {
        padding: 20px 25px 30px;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h1>Đăng nhập</h1>
                <p>Chào mừng bạn quay trở lại PetShop!</p>
            </div>
            
            <div class="auth-body">
                <?php if ($login_error) : ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo esc_html($login_error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['registered'])) : ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    Đăng ký thành công! Vui lòng đăng nhập.
                </div>
                <?php endif; ?>
                
                <form method="post" class="auth-form" id="loginForm">
                    <?php wp_nonce_field('petshop_login_nonce'); ?>
                    <input type="hidden" name="petshop_login" value="1">
                    
                    <div class="form-group">
                        <label for="username">Email hoặc Tên đăng nhập</label>
                        <div class="input-group">
                            <i class="bi bi-envelope"></i>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Nhập email hoặc tên đăng nhập..."
                                   value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-group">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Nhập mật khẩu...">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Đăng nhập
                    </button>
                </form>
                
                <div class="divider">
                    <span>hoặc đăng nhập với</span>
                </div>
                
                <div class="social-login">
                    <?php if ($google_oauth_url) : ?>
                    <a href="<?php echo esc_url($google_oauth_url); ?>" class="btn-social btn-google">
                        <svg viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Đăng nhập với Google
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(wp_login_url(home_url('/tai-khoan/'))); ?>" class="btn-social btn-wordpress">
                        <i class="bi bi-wordpress"></i>
                        Đăng nhập WordPress
                    </a>
                </div>
            </div>
            
            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="<?php echo home_url('/dang-ky/'); ?>">Đăng ký ngay</a></p>
            </div>
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
function togglePassword() {
    var passwordInput = document.getElementById('password');
    var toggleIcon = document.getElementById('toggleIcon');
    
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
</script>

<?php get_footer(); ?>
