<?php
/**
 * PetShop Email & SMTP Configuration
 * Cấu hình gửi email không cần plugin
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// CẤU HÌNH SMTP
// =============================================
function petshop_smtp_config($phpmailer) {
    // Lấy cài đặt từ database
    $smtp_settings = get_option('petshop_smtp_settings', array());
    
    // Nếu chưa có cài đặt hoặc SMTP bị tắt, dùng wp_mail mặc định
    if (empty($smtp_settings['enabled'])) {
        return;
    }
    
    $phpmailer->isSMTP();
    $phpmailer->Host       = $smtp_settings['host'] ?? 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = intval($smtp_settings['port'] ?? 587);
    $phpmailer->SMTPSecure = $smtp_settings['encryption'] ?? 'tls';
    $phpmailer->Username   = $smtp_settings['username'] ?? '';
    $phpmailer->Password   = $smtp_settings['password'] ?? '';
    
    // Debug mode - bật khi cần kiểm tra lỗi
    $phpmailer->SMTPDebug = 0; // 0 = off, 2 = messages only, 3 = messages + connection
    
    // Tắt xác minh SSL certificate (cho localhost)
    $phpmailer->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // From Email & Name - PHẢI dùng email SMTP để Gmail chấp nhận
    $from_email = $smtp_settings['username']; // Gmail yêu cầu From = Username
    $shop_settings = get_option('petshop_shop_settings', array());
    $from_name = !empty($shop_settings['email_from_name']) ? $shop_settings['email_from_name'] : get_bloginfo('name');
    
    $phpmailer->From     = $from_email;
    $phpmailer->FromName = $from_name;
    
    // Đảm bảo Sender cũng đúng
    $phpmailer->Sender   = $from_email;
}
add_action('phpmailer_init', 'petshop_smtp_config', 999); // Priority cao để ghi đè

// Override WordPress default From email
function petshop_mail_from($email) {
    $smtp_settings = get_option('petshop_smtp_settings', array());
    if (!empty($smtp_settings['enabled']) && !empty($smtp_settings['username'])) {
        return $smtp_settings['username'];
    }
    return $email;
}
add_filter('wp_mail_from', 'petshop_mail_from', 999);

// Override WordPress default From name
function petshop_mail_from_name($name) {
    $smtp_settings = get_option('petshop_smtp_settings', array());
    if (!empty($smtp_settings['enabled'])) {
        $shop_settings = get_option('petshop_shop_settings', array());
        return !empty($shop_settings['email_from_name']) ? $shop_settings['email_from_name'] : get_bloginfo('name');
    }
    return $name;
}
add_filter('wp_mail_from_name', 'petshop_mail_from_name', 999);

// =============================================
// THÊM MENU CÀI ĐẶT SMTP
// =============================================
function petshop_smtp_settings_menu() {
    add_submenu_page(
        'edit.php?post_type=petshop_order',
        'Cài đặt SMTP',
        'Cài đặt SMTP',
        'manage_options',
        'petshop-smtp-settings',
        'petshop_smtp_settings_page'
    );
}
add_action('admin_menu', 'petshop_smtp_settings_menu', 25);

// =============================================
// TRANG CÀI ĐẶT SMTP
// =============================================
function petshop_smtp_settings_page() {
    // Lưu settings
    if (isset($_POST['petshop_smtp_nonce']) && wp_verify_nonce($_POST['petshop_smtp_nonce'], 'petshop_smtp_settings')) {
        $settings = array(
            'enabled' => isset($_POST['smtp_enabled']) ? 1 : 0,
            'host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
            'port' => intval($_POST['smtp_port'] ?? 587),
            'encryption' => sanitize_text_field($_POST['smtp_encryption'] ?? 'tls'),
            'username' => sanitize_email($_POST['smtp_username'] ?? ''),
            'password' => $_POST['smtp_password'] ?? '', // Không sanitize password
        );
        update_option('petshop_smtp_settings', $settings);
        echo '<div class="notice notice-success"><p><span class="dashicons dashicons-yes-alt"></span> Đã lưu cài đặt SMTP!</p></div>';
    }
    
    $settings = get_option('petshop_smtp_settings', array());
    $defaults = array(
        'enabled' => 0,
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
    );
    $settings = wp_parse_args($settings, $defaults);
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-email-alt" style="margin-right: 10px;"></span>Cài đặt SMTP</h1>
        
        <form method="post">
            <?php wp_nonce_field('petshop_smtp_settings', 'petshop_smtp_nonce'); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 20px;">
                <!-- Cài đặt SMTP -->
                <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                        <span class="dashicons dashicons-admin-network" style="color: #66BCB4;"></span> Cấu hình SMTP Server
                    </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th>Kích hoạt SMTP</th>
                            <td>
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="smtp_enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                                    <span>Sử dụng SMTP để gửi email</span>
                                </label>
                                <p class="description">Nếu tắt, WordPress sẽ dùng hàm mail() mặc định của server</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="smtp_host">SMTP Host</label></th>
                            <td>
                                <input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr($settings['host']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="smtp_port">Port</label></th>
                            <td>
                                <input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr($settings['port']); ?>" style="width: 100px;">
                                <p class="description">587 (TLS) hoặc 465 (SSL)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="smtp_encryption">Mã hóa</label></th>
                            <td>
                                <select name="smtp_encryption" id="smtp_encryption">
                                    <option value="tls" <?php selected($settings['encryption'], 'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected($settings['encryption'], 'ssl'); ?>>SSL</option>
                                    <option value="" <?php selected($settings['encryption'], ''); ?>>Không mã hóa</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="smtp_username">Username (Email)</label></th>
                            <td>
                                <input type="email" name="smtp_username" id="smtp_username" value="<?php echo esc_attr($settings['username']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="smtp_password">Password</label></th>
                            <td>
                                <input type="password" name="smtp_password" id="smtp_password" value="<?php echo esc_attr($settings['password']); ?>" class="regular-text">
                                <p class="description">Với Gmail, sử dụng <strong>App Password</strong></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Hướng dẫn -->
                <div>
                    <!-- Gmail -->
                    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #EA4335;">
                            <span class="dashicons dashicons-google"></span> Cấu hình với Gmail
                        </h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr><td style="padding: 8px 0; border-bottom: 1px solid #eee;">SMTP Host:</td><td style="padding: 8px 0; border-bottom: 1px solid #eee;"><code>smtp.gmail.com</code></td></tr>
                            <tr><td style="padding: 8px 0; border-bottom: 1px solid #eee;">Port:</td><td style="padding: 8px 0; border-bottom: 1px solid #eee;"><code>587</code></td></tr>
                            <tr><td style="padding: 8px 0; border-bottom: 1px solid #eee;">Mã hóa:</td><td style="padding: 8px 0; border-bottom: 1px solid #eee;"><code>TLS</code></td></tr>
                            <tr><td style="padding: 8px 0;">Password:</td><td style="padding: 8px 0;"><strong>App Password 16 ký tự</strong></td></tr>
                        </table>
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" class="button" style="margin-top: 15px;">
                            Tạo App Password <span class="dashicons dashicons-external" style="font-size: 16px; height: 16px;"></span>
                        </a>
                    </div>
                    
                    <!-- Các dịch vụ khác -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #66BCB4;">
                        <h4 style="margin: 0 0 15px;">Các SMTP phổ biến khác:</h4>
                        <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                            <li><strong>Outlook:</strong> smtp.office365.com, Port 587</li>
                            <li><strong>Yahoo:</strong> smtp.mail.yahoo.com, Port 587</li>
                            <li><strong>Zoho:</strong> smtp.zoho.com, Port 587</li>
                            <li><strong>SendGrid:</strong> smtp.sendgrid.net, Port 587</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <p style="margin-top: 25px;">
                <input type="submit" class="button button-primary button-hero" value="Lưu cài đặt SMTP">
                <button type="button" onclick="testSMTP()" class="button button-secondary button-hero" style="margin-left: 10px;">
                    <span class="dashicons dashicons-email" style="vertical-align: middle;"></span> Test gửi email
                </button>
                <span id="smtpTestResult" style="margin-left: 15px;"></span>
            </p>
        </form>
    </div>
    
    <script>
    function testSMTP() {
        var result = document.getElementById('smtpTestResult');
        result.innerHTML = '<span style="color: #666;"><span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Đang gửi...</span>';
        
        jQuery.post(ajaxurl, {
            action: 'petshop_smtp_test'
        }, function(response) {
            if (response.success) {
                result.innerHTML = '<span style="color: #5cb85c;"><span class="dashicons dashicons-yes-alt"></span> Đã gửi email test đến ' + response.data.email + '</span>';
            } else {
                result.innerHTML = '<span style="color: #d9534f;"><span class="dashicons dashicons-dismiss"></span> ' + response.data.message + '</span>';
            }
        }).fail(function() {
            result.innerHTML = '<span style="color: #d9534f;"><span class="dashicons dashicons-dismiss"></span> Lỗi kết nối AJAX</span>';
        });
    }
    </script>
    
    <style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    <?php
}

// =============================================
// AJAX: TEST GỬI EMAIL SMTP
// =============================================
function petshop_smtp_test_email() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Không có quyền'));
    }
    
    // Kiểm tra OpenSSL
    if (!extension_loaded('openssl')) {
        wp_send_json_error(array('message' => 'PHP chưa bật extension OpenSSL. Vào php.ini và bỏ dấu ; trước extension=openssl'));
    }
    
    $smtp_settings = get_option('petshop_smtp_settings', array());
    
    if (empty($smtp_settings['enabled'])) {
        wp_send_json_error(array('message' => 'Chưa bật SMTP. Hãy tick vào "Sử dụng SMTP" và lưu lại.'));
    }
    
    if (empty($smtp_settings['username']) || empty($smtp_settings['password'])) {
        wp_send_json_error(array('message' => 'Chưa điền Username hoặc Password'));
    }
    
    $to = get_option('admin_email');
    $subject = '[PetShop] Test SMTP Email';
    $message = '<html><body style="font-family: Arial; padding: 20px;">
        <h2 style="color: #28a745;">Email Test Thành Công!</h2>
        <p>SMTP đang hoạt động với cấu hình:</p>
        <ul>
            <li>Host: ' . esc_html($smtp_settings['host']) . '</li>
            <li>Port: ' . esc_html($smtp_settings['port']) . '</li>
            <li>Username: ' . esc_html($smtp_settings['username']) . '</li>
        </ul>
        <p style="color: #666;">Thời gian: ' . current_time('d/m/Y H:i:s') . '</p>
    </body></html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // Gửi email
    $sent = wp_mail($to, $subject, $message, $headers);
    
    if ($sent) {
        wp_send_json_success(array('email' => $to));
    } else {
        // Lấy lỗi từ PHPMailer
        global $phpmailer;
        $error_message = 'Không thể gửi email.';
        
        if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
            $error_message = $phpmailer->ErrorInfo;
        }
        
        wp_send_json_error(array('message' => $error_message));
    }
}
add_action('wp_ajax_petshop_smtp_test', 'petshop_smtp_test_email');

// =============================================
// XỬ LÝ GỬI FORM LIÊN HỆ (AJAX)
// =============================================
function petshop_submit_contact_form() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'petshop_contact_form')) {
        wp_send_json_error(array('message' => 'Invalid request'));
    }
    
    // Sanitize data
    $name = sanitize_text_field($_POST['name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $subject_type = sanitize_text_field($_POST['subject'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    
    // Validate
    if (empty($name) || empty($phone) || empty($email) || empty($message)) {
        wp_send_json_error(array('message' => 'Vui lòng điền đầy đủ thông tin'));
    }
    
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Email không hợp lệ'));
    }
    
    // Subject types
    $subject_labels = array(
        'product' => 'Hỏi về sản phẩm',
        'service' => 'Hỏi về dịch vụ',
        'order' => 'Về đơn hàng',
        'support' => 'Hỗ trợ kỹ thuật',
        'other' => 'Khác',
    );
    $subject_label = isset($subject_labels[$subject_type]) ? $subject_labels[$subject_type] : 'Liên hệ chung';
    
    // Get shop settings
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? 'PetShop';
    $shop_email = $shop_settings['shop_email'] ?? get_option('admin_email');
    
    // Save to database (as custom post type)
    $contact_id = wp_insert_post(array(
        'post_type' => 'petshop_contact',
        'post_title' => 'Liên hệ từ ' . $name,
        'post_status' => 'publish',
    ));
    
    if ($contact_id) {
        update_post_meta($contact_id, 'contact_name', $name);
        update_post_meta($contact_id, 'contact_phone', $phone);
        update_post_meta($contact_id, 'contact_email', $email);
        update_post_meta($contact_id, 'contact_subject', $subject_label);
        update_post_meta($contact_id, 'contact_message', $message);
        update_post_meta($contact_id, 'contact_date', current_time('mysql'));
        update_post_meta($contact_id, 'contact_status', 'new');
    }
    
    // Email to admin
    $admin_subject = '[' . $shop_name . '] Liên hệ mới: ' . $subject_label;
    $admin_message = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #66BCB4, #7ECEC6); padding: 25px; text-align: center; color: #fff;">
                <h2 style="margin: 0;">Tin nhắn liên hệ mới</h2>
            </div>
            <div style="padding: 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 12px; border: 1px solid #eee; width: 120px;"><strong>Họ tên:</strong></td>
                        <td style="padding: 12px; border: 1px solid #eee;">' . esc_html($name) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;"><strong>Điện thoại:</strong></td>
                        <td style="padding: 12px; border: 1px solid #eee;"><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 12px; border: 1px solid #eee;"><strong>Email:</strong></td>
                        <td style="padding: 12px; border: 1px solid #eee;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;"><strong>Chủ đề:</strong></td>
                        <td style="padding: 12px; border: 1px solid #eee;">' . esc_html($subject_label) . '</td>
                    </tr>
                </table>
                
                <div style="margin-top: 20px; padding: 20px; background: #FDF8F3; border-radius: 10px; border-left: 4px solid #EC802B;">
                    <strong style="display: block; margin-bottom: 10px;">Nội dung:</strong>
                    <p style="margin: 0; line-height: 1.7; white-space: pre-wrap;">' . esc_html($message) . '</p>
                </div>
                
                <div style="text-align: center; margin-top: 25px;">
                    <a href="' . admin_url('edit.php?post_type=petshop_contact') . '" style="display: inline-block; background: #EC802B; color: #fff; padding: 12px 25px; border-radius: 5px; text-decoration: none;">
                        Xem trong Admin
                    </a>
                </div>
            </div>
            <div style="background: #5D4E37; padding: 15px; text-align: center; color: #fff; font-size: 12px;">
                ' . esc_html($shop_name) . ' - ' . current_time('d/m/Y H:i') . '
            </div>
        </div>
    </body>
    </html>';
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    );
    
    $sent = wp_mail($shop_email, $admin_subject, $admin_message, $headers);
    
    // Auto-reply to customer
    $customer_subject = '[' . $shop_name . '] Cảm ơn bạn đã liên hệ!';
    $customer_message = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #EC802B, #F5994D); padding: 30px; text-align: center; color: #fff;">
                <h2 style="margin: 0;">🐾 ' . esc_html($shop_name) . '</h2>
            </div>
            <div style="padding: 30px;">
                <p style="font-size: 16px;">Xin chào <strong>' . esc_html($name) . '</strong>,</p>
                <p style="font-size: 16px; line-height: 1.7;">Cảm ơn bạn đã liên hệ với chúng tôi! Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất.</p>
                
                <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 25px 0;">
                    <strong>Nội dung bạn đã gửi:</strong>
                    <p style="margin: 10px 0 0; line-height: 1.6; color: #666;">' . esc_html($message) . '</p>
                </div>
                
                <p style="font-size: 14px; color: #666;">Nếu cần hỗ trợ gấp, vui lòng gọi hotline: <strong>' . esc_html($shop_settings['shop_phone'] ?? '0123 456 789') . '</strong></p>
                
                <p style="margin-top: 30px;">Trân trọng,<br><strong>Đội ngũ ' . esc_html($shop_name) . '</strong></p>
            </div>
            <div style="background: #5D4E37; padding: 15px; text-align: center; color: #fff; font-size: 12px;">
                © ' . date('Y') . ' ' . esc_html($shop_name) . ' - Yêu thương dành cho thú cưng
            </div>
        </div>
    </body>
    </html>';
    
    wp_mail($email, $customer_subject, $customer_message, array('Content-Type: text/html; charset=UTF-8'));
    
    wp_send_json_success(array(
        'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.',
        'sent' => $sent
    ));
}
add_action('wp_ajax_petshop_contact_form', 'petshop_submit_contact_form');
add_action('wp_ajax_nopriv_petshop_contact_form', 'petshop_submit_contact_form');

// =============================================
// ĐĂNG KÝ POST TYPE: LIÊN HỆ
// =============================================
function petshop_register_contact_post_type() {
    register_post_type('petshop_contact', array(
        'labels' => array(
            'name' => 'Liên hệ',
            'singular_name' => 'Liên hệ',
            'all_items' => 'Tất cả liên hệ',
            'search_items' => 'Tìm kiếm',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email',
        'supports' => array('title'),
        'capability_type' => 'post',
    ));
}
add_action('init', 'petshop_register_contact_post_type');

// =============================================
// CUSTOM COLUMNS CHO LIÊN HỆ
// =============================================
function petshop_contact_columns($columns) {
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Tiêu đề',
        'contact_phone' => 'Điện thoại',
        'contact_email' => 'Email',
        'contact_subject' => 'Chủ đề',
        'contact_status' => 'Trạng thái',
        'date' => 'Ngày gửi',
    );
    return $columns;
}
add_filter('manage_petshop_contact_posts_columns', 'petshop_contact_columns');

function petshop_contact_column_content($column, $post_id) {
    switch ($column) {
        case 'contact_phone':
            $phone = get_post_meta($post_id, 'contact_phone', true);
            echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
            break;
        case 'contact_email':
            $email = get_post_meta($post_id, 'contact_email', true);
            echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            break;
        case 'contact_subject':
            echo esc_html(get_post_meta($post_id, 'contact_subject', true));
            break;
        case 'contact_status':
            $status = get_post_meta($post_id, 'contact_status', true);
            $status_labels = array(
                'new' => array('label' => 'Mới', 'color' => '#17a2b8'),
                'read' => array('label' => 'Đã đọc', 'color' => '#6c757d'),
                'replied' => array('label' => 'Đã phản hồi', 'color' => '#28a745'),
            );
            $info = isset($status_labels[$status]) ? $status_labels[$status] : $status_labels['new'];
            echo '<span style="background: ' . $info['color'] . '; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px;">' . $info['label'] . '</span>';
            break;
    }
}
add_action('manage_petshop_contact_posts_custom_column', 'petshop_contact_column_content', 10, 2);

// =============================================
// META BOX CHO CHI TIẾT LIÊN HỆ
// =============================================
function petshop_contact_meta_box() {
    add_meta_box(
        'petshop_contact_details',
        'Chi tiết liên hệ',
        'petshop_contact_meta_box_callback',
        'petshop_contact',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'petshop_contact_meta_box');

function petshop_contact_meta_box_callback($post) {
    $name = get_post_meta($post->ID, 'contact_name', true);
    $phone = get_post_meta($post->ID, 'contact_phone', true);
    $email = get_post_meta($post->ID, 'contact_email', true);
    $subject = get_post_meta($post->ID, 'contact_subject', true);
    $message = get_post_meta($post->ID, 'contact_message', true);
    $date = get_post_meta($post->ID, 'contact_date', true);
    $status = get_post_meta($post->ID, 'contact_status', true);
    
    wp_nonce_field('petshop_contact_meta', 'petshop_contact_meta_nonce');
    ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <table class="form-table">
                <tr>
                    <th>Họ tên:</th>
                    <td><strong><?php echo esc_html($name); ?></strong></td>
                </tr>
                <tr>
                    <th>Điện thoại:</th>
                    <td><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
                </tr>
                <tr>
                    <th>Chủ đề:</th>
                    <td><?php echo esc_html($subject); ?></td>
                </tr>
                <tr>
                    <th>Ngày gửi:</th>
                    <td><?php echo $date ? date('d/m/Y H:i', strtotime($date)) : '-'; ?></td>
                </tr>
                <tr>
                    <th>Trạng thái:</th>
                    <td>
                        <select name="contact_status">
                            <option value="new" <?php selected($status, 'new'); ?>>Mới</option>
                            <option value="read" <?php selected($status, 'read'); ?>>Đã đọc</option>
                            <option value="replied" <?php selected($status, 'replied'); ?>>Đã phản hồi</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div>
            <h4 style="margin-top: 0;">Nội dung tin nhắn:</h4>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; border-left: 4px solid #EC802B; white-space: pre-wrap; line-height: 1.7;">
                <?php echo esc_html($message); ?>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="mailto:<?php echo esc_attr($email); ?>?subject=Re: <?php echo esc_attr($subject); ?>" class="button button-primary">
                    <span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Trả lời email
                </a>
            </div>
        </div>
    </div>
    <?php
}

function petshop_save_contact_meta($post_id) {
    if (!isset($_POST['petshop_contact_meta_nonce']) || 
        !wp_verify_nonce($_POST['petshop_contact_meta_nonce'], 'petshop_contact_meta')) {
        return;
    }
    
    if (isset($_POST['contact_status'])) {
        update_post_meta($post_id, 'contact_status', sanitize_text_field($_POST['contact_status']));
    }
}
add_action('save_post_petshop_contact', 'petshop_save_contact_meta');

// =============================================
// ĐẾM SỐ LIÊN HỆ MỚI
// =============================================
function petshop_new_contacts_count() {
    return count(get_posts(array(
        'post_type' => 'petshop_contact',
        'post_status' => 'publish',
        'meta_key' => 'contact_status',
        'meta_value' => 'new',
        'posts_per_page' => -1,
        'fields' => 'ids',
    )));
}

// Thêm badge vào menu
function petshop_contact_menu_badge() {
    global $menu;
    $count = petshop_new_contacts_count();
    
    if ($count > 0) {
        foreach ($menu as $key => $value) {
            if (isset($value[2]) && $value[2] === 'edit.php?post_type=petshop_contact') {
                $menu[$key][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
            }
        }
    }
}
add_action('admin_menu', 'petshop_contact_menu_badge', 999);
