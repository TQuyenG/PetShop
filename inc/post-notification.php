<?php
/**
 * Post Notification Integration
 * Cho phép admin gửi thông báo từ bài viết
 * 
 * @package PetShop
 */

// =============================================
// META BOX: GỬI THÔNG BÁO TỪ BÀI VIẾT
// =============================================
add_action('add_meta_boxes', 'petshop_add_post_notification_metabox');
function petshop_add_post_notification_metabox() {
    // Chỉ hiện cho các post type public
    $post_types = array('post', 'page', 'product');
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'petshop_post_notification',
            '📢 Gửi thông báo bài viết',
            'petshop_post_notification_metabox_callback',
            $post_type,
            'side',
            'default'
        );
    }
}

function petshop_post_notification_metabox_callback($post) {
    // Chỉ hiện khi đã publish
    if ($post->post_status !== 'publish') {
        echo '<div style="padding: 10px; background: #fff8e1; border-radius: 6px; font-size: 13px;">
            <i class="dashicons dashicons-info" style="color: #ff9800;"></i>
            Đăng bài viết trước để có thể gửi thông báo.
        </div>';
        return;
    }
    
    $post_url = get_permalink($post->ID);
    $post_title = $post->post_title;
    
    // Check nếu đã gửi thông báo cho bài này chưa
    $notified = get_post_meta($post->ID, '_petshop_notification_sent', true);
    
    wp_nonce_field('petshop_post_notification', 'petshop_post_notification_nonce');
    ?>
    <style>
        .post-notif-box { padding: 0; }
        .post-notif-box .btn-copy {
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            padding: 10px 12px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        .post-notif-box .btn-copy:hover { background: #e8e8e8; }
        .post-notif-box .btn-copy.copied { background: #d4edda; border-color: #28a745; }
        .post-notif-box .btn-send {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .post-notif-box .btn-send:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(236,128,43,0.3); }
        .post-notif-box .btn-send:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .post-notif-box .sent-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #d4edda;
            color: #155724;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 10px;
        }
        .post-notif-box .link-preview {
            padding: 8px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 11px;
            color: #666;
            word-break: break-all;
            margin-bottom: 10px;
        }
        .post-notif-options { margin: 10px 0; }
        .post-notif-options label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            cursor: pointer;
            margin-bottom: 6px;
        }
    </style>
    
    <div class="post-notif-box">
        <!-- Copy Link -->
        <div class="link-preview"><?php echo esc_html($post_url); ?></div>
        
        <button type="button" class="btn-copy" onclick="copyPostLink(this, '<?php echo esc_js($post_url); ?>')">
            <span class="dashicons dashicons-admin-links"></span>
            <span>Sao chép link bài viết</span>
        </button>
        
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
        
        <!-- Notification Options -->
        <div class="post-notif-options">
            <label>
                <input type="checkbox" name="notif_send_email" value="1" checked>
                <span>Gửi kèm Email</span>
            </label>
            <label>
                <input type="checkbox" name="notif_send_system" value="1" checked>
                <span>Thông báo hệ thống</span>
            </label>
        </div>
        
        <input type="hidden" name="post_notification_post_id" value="<?php echo $post->ID; ?>">
        
        <button type="button" class="btn-send" id="sendPostNotifBtn" onclick="sendPostNotification(<?php echo $post->ID; ?>)">
            <span class="dashicons dashicons-megaphone"></span>
            <span>Gửi thông báo đến tất cả</span>
        </button>
        
        <?php if ($notified): ?>
        <div class="sent-badge">
            <span class="dashicons dashicons-yes-alt"></span>
            Đã gửi thông báo lúc <?php echo date('d/m/Y H:i', strtotime($notified)); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function copyPostLink(btn, url) {
        navigator.clipboard.writeText(url).then(function() {
            btn.classList.add('copied');
            btn.querySelector('span:last-child').textContent = 'Đã sao chép!';
            setTimeout(function() {
                btn.classList.remove('copied');
                btn.querySelector('span:last-child').textContent = 'Sao chép link bài viết';
            }, 2000);
        });
    }
    
    function sendPostNotification(postId) {
        if (!confirm('Gửi thông báo bài viết này đến TẤT CẢ người dùng?')) return;
        
        const btn = document.getElementById('sendPostNotifBtn');
        const sendEmail = document.querySelector('input[name="notif_send_email"]').checked;
        const sendSystem = document.querySelector('input[name="notif_send_system"]').checked;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="dashicons dashicons-update spin"></span> Đang gửi...';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_send_post_notification&post_id=' + postId + 
                  '&send_email=' + (sendEmail ? 1 : 0) + 
                  '&send_system=' + (sendSystem ? 1 : 0) +
                  '&nonce=' + '<?php echo wp_create_nonce('send_post_notification'); ?>'
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> ' + res.data.message;
                btn.style.background = '#28a745';
                location.reload();
            } else {
                alert('Lỗi: ' + res.data);
                btn.disabled = false;
                btn.innerHTML = '<span class="dashicons dashicons-megaphone"></span> Gửi thông báo đến tất cả';
            }
        })
        .catch(err => {
            alert('Có lỗi xảy ra!');
            btn.disabled = false;
            btn.innerHTML = '<span class="dashicons dashicons-megaphone"></span> Gửi thông báo đến tất cả';
        });
    }
    </script>
    
    <style>
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
    <?php
}

// AJAX: Gửi thông báo bài viết
add_action('wp_ajax_petshop_send_post_notification', 'petshop_ajax_send_post_notification');
function petshop_ajax_send_post_notification() {
    if (!wp_verify_nonce($_POST['nonce'], 'send_post_notification')) {
        wp_send_json_error('Nonce không hợp lệ');
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Bạn không có quyền');
    }
    
    $post_id = intval($_POST['post_id']);
    $send_email = intval($_POST['send_email']);
    $send_system = intval($_POST['send_system']);
    
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Bài viết không tồn tại');
    }
    
    $post_url = get_permalink($post_id);
    $post_title = $post->post_title;
    $post_excerpt = wp_trim_words($post->post_content, 30, '...');
    
    // Xác định icon theo post type
    $icons = array(
        'post' => 'bi-newspaper',
        'product' => 'bi-bag',
        'page' => 'bi-file-text',
    );
    $icon = $icons[$post->post_type] ?? 'bi-bell';
    
    // Build channels
    $channels = array();
    if ($send_system) $channels[] = 'system';
    if ($send_email) $channels[] = 'email';
    
    if (empty($channels)) {
        wp_send_json_error('Vui lòng chọn ít nhất 1 kênh gửi');
    }
    
    // Lấy tất cả users
    $users = get_users(array('fields' => 'ID'));
    
    // Gọi hàm gửi
    $notification_data = array(
        'notification_type' => 'system',
        'notification_title' => '📰 ' . $post_title,
        'notification_message' => $post_excerpt,
        'notification_link' => $post_url,
        'recipient_type' => 'all',
        'channels' => $channels,
    );
    
    $result = petshop_process_send_notification($notification_data);
    
    if ($result['success']) {
        // Lưu meta
        update_post_meta($post_id, '_petshop_notification_sent', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => $result['message']
        ));
    } else {
        wp_send_json_error($result['message']);
    }
}

// =============================================
// LINK THÔNG MINH CHO THÔNG BÁO
// =============================================

/**
 * Tạo link thông minh cho thông báo dựa vào loại
 * 
 * @param string $type Loại thông báo
 * @param array $data Dữ liệu bổ sung (order_id, post_id, etc.)
 * @return string URL
 */
function petshop_get_smart_notification_link($type, $data = array()) {
    $notification_page = home_url('/thong-bao/');
    
    switch ($type) {
        // Đơn hàng - dẫn đến chi tiết đơn hàng
        case 'order':
        case 'order_placed':
        case 'order_confirmed':
        case 'order_shipping':
        case 'order_completed':
        case 'order_cancelled':
            if (!empty($data['order_id'])) {
                return home_url('/xem-don-hang/?id=' . $data['order_id']);
            }
            return home_url('/tai-khoan/?tab=orders');
            
        // Điểm thưởng - dẫn đến trang điểm
        case 'points':
        case 'points_earned':
            return home_url('/tai-khoan/?tab=points');
            
        // Hạng thành viên - dẫn đến trang membership
        case 'tier_upgrade':
            return home_url('/tai-khoan/?tab=membership');
            
        // Voucher - dẫn đến trang voucher
        case 'voucher':
        case 'new_voucher':
            return home_url('/tai-khoan/?tab=vouchers');
            
        // Bài viết - dẫn đến bài viết
        case 'post':
        case 'article':
            if (!empty($data['post_id'])) {
                return get_permalink($data['post_id']);
            }
            return home_url('/tin-tuc/');
            
        // Khuyến mãi
        case 'promotion':
        case 'flash_sale':
            if (!empty($data['promo_url'])) {
                return $data['promo_url'];
            }
            return home_url('/khuyen-mai/');
            
        // Mặc định - trang thông báo
        default:
            return $notification_page;
    }
}

/**
 * Tạo thông báo với link thông minh
 */
function petshop_create_smart_notification($user_id, $type, $title, $message, $data = array()) {
    $link = $data['link'] ?? petshop_get_smart_notification_link($type, $data);
    
    $types = function_exists('petshop_get_notification_types') ? petshop_get_notification_types() : array();
    $type_info = $types[$type] ?? array('icon' => 'bi-bell', 'color' => '#EC802B');
    
    return petshop_create_notification($user_id, $type, $title, $message, array(
        'link' => $link,
        'icon' => $data['icon'] ?? $type_info['icon'],
        'color' => $data['color'] ?? $type_info['color'],
        'send_email' => $data['send_email'] ?? false,
    ));
}

// =============================================
// THÊM CỘT "THÔNG BÁO" VÀO DANH SÁCH BÀI VIẾT
// =============================================
add_filter('manage_posts_columns', 'petshop_add_notification_column');
add_filter('manage_pages_columns', 'petshop_add_notification_column');
function petshop_add_notification_column($columns) {
    $columns['notification'] = '📢';
    return $columns;
}

add_action('manage_posts_custom_column', 'petshop_notification_column_content', 10, 2);
add_action('manage_pages_custom_column', 'petshop_notification_column_content', 10, 2);
function petshop_notification_column_content($column, $post_id) {
    if ($column !== 'notification') return;
    
    $notified = get_post_meta($post_id, '_petshop_notification_sent', true);
    
    if ($notified) {
        echo '<span title="Đã gửi: ' . date('d/m/Y H:i', strtotime($notified)) . '" style="color:#28a745;cursor:help;">✓</span>';
    } else {
        $post = get_post($post_id);
        if ($post->post_status === 'publish') {
            echo '<a href="' . admin_url('post.php?post=' . $post_id . '&action=edit#petshop_post_notification') . '" 
                    title="Gửi thông báo" style="color:#EC802B;">
                    <span class="dashicons dashicons-megaphone"></span>
                  </a>';
        } else {
            echo '<span style="color:#ccc;">-</span>';
        }
    }
}

// Column style
add_action('admin_head', function() {
    echo '<style>
        .column-notification { width: 40px; text-align: center; }
    </style>';
});
