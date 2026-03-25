<?php
/**
 * PetShop Communication Menu
 * Menu tổng hợp Thông báo & Liên hệ
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// ĐĂNG KÝ MENU CHÍNH: TRUYỀN THÔNG
// =============================================
function petshop_communication_register_menu() {
    // Menu chính
    add_menu_page(
        'Truyền thông',
        'Truyền thông',
        'manage_options',
        'petshop-communication',
        'petshop_communication_dashboard_page',
        'dashicons-megaphone',
        28
    );
    
    // Dashboard
    add_submenu_page(
        'petshop-communication',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'petshop-communication',
        'petshop_communication_dashboard_page'
    );
    
    // Gửi thông báo (Admin gửi)
    add_submenu_page(
        'petshop-communication',
        'Gửi thông báo',
        'Gửi thông báo',
        'manage_options',
        'petshop-send-notification',
        'petshop_send_notification_page'
    );
    
    // Lịch sử thông báo đã gửi
    add_submenu_page(
        'petshop-communication',
        'Lịch sử gửi',
        'Lịch sử gửi',
        'manage_options',
        'petshop-notification-history',
        'petshop_notification_history_page'
    );
    
    // Thông báo tự động
    add_submenu_page(
        'petshop-communication',
        'Thông báo tự động',
        'Thông báo tự động',
        'manage_options',
        'petshop-auto-notification',
        'petshop_auto_notification_settings_page'
    );
    
    // Liên hệ
    add_submenu_page(
        'petshop-communication',
        'Phản hồi khách hàng',
        'Phản hồi KH',
        'edit_posts',
        'petshop-contacts',
        'petshop_contacts_page'
    );
    
    // Cài đặt Email/SMTP
    add_submenu_page(
        'petshop-communication',
        'Cài đặt Email',
        'Cài đặt Email',
        'manage_options',
        'petshop-email-settings',
        'petshop_smtp_settings_page'
    );
    
    // Mẫu thông báo
    add_submenu_page(
        'petshop-communication',
        'Mẫu thông báo',
        'Mẫu thông báo',
        'manage_options',
        'petshop-email-templates',
        'petshop_email_templates_page'
    );
}
add_action('admin_menu', 'petshop_communication_register_menu', 26);

// =============================================
// MENU RIÊNG: PHẢN HỒI KHÁCH HÀNG (sidebar WP)
// Hiện với tất cả admin, quản lý, nhân viên
// =============================================
add_action('admin_menu', 'petshop_register_contacts_top_menu', 27);
function petshop_register_contacts_top_menu() {
    // Đếm số liên hệ mới
    global $wpdb;
    $new_count = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'contact_status' AND pm.meta_value = 'new'
         WHERE p.post_type = 'petshop_contact' AND p.post_status = 'publish'"
    );
    $badge = $new_count > 0 ? ' <span class="awaiting-mod update-plugins count-' . $new_count . '"><span class="plugin-count">' . $new_count . '</span></span>' : '';

    add_menu_page(
        'Phản hồi khách hàng',
        'Phản hồi KH' . $badge,
        'edit_posts',
        'petshop-contacts',
        'petshop_contacts_page',
        'dashicons-format-chat',
        29
    );
}

// Ẩn menu Liên hệ riêng (đã gộp vào Communication)
function petshop_hide_contact_menu() {
    remove_menu_page('edit.php?post_type=petshop_contact');
}
add_action('admin_menu', 'petshop_hide_contact_menu', 999);

// =============================================
// DASHBOARD TRUYỀN THÔNG
// =============================================
function petshop_communication_dashboard_page() {
    global $wpdb;
    $notif_table = $wpdb->prefix . 'petshop_notifications';
    
    // Thống kê
    $total_sent = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}petshop_notification_logs WHERE 1=1"
    )) ?: 0;
    
    $total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$notif_table}") ?: 0;
    $unread_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$notif_table} WHERE is_read = 0") ?: 0;
    $total_contacts = wp_count_posts('petshop_contact')->publish ?: 0;
    $new_contacts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'contact_status' AND meta_value = 'new'") ?: 0;
    
    // Lấy 5 thông báo gần nhất đã gửi
    $recent_logs = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}petshop_notification_logs ORDER BY created_at DESC LIMIT 5"
    ) ?: array();
    
    // Lấy 5 liên hệ mới nhất
    $recent_contacts = get_posts(array(
        'post_type' => 'petshop_contact',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    ?>
    <div class="wrap petshop-comm-dashboard">
        <h1><span class="dashicons dashicons-megaphone"></span> Truyền thông - Dashboard</h1>
        
        <style>
            .petshop-comm-dashboard { max-width: 1400px; }
            .comm-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
            .comm-stat-card {
                background: #fff;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                text-align: center;
            }
            .comm-stat-card .icon {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
                font-size: 24px;
            }
            .comm-stat-card .value { font-size: 32px; font-weight: 700; color: #333; }
            .comm-stat-card .label { color: #888; font-size: 14px; margin-top: 5px; }
            
            .comm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 30px; }
            .comm-panel {
                background: #fff;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .comm-panel h3 { margin: 0 0 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
            .comm-panel h3 i { margin-right: 8px; color: #EC802B; }
            
            .comm-quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px; }
            .comm-quick-action {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
                text-decoration: none;
                color: #333;
                transition: all 0.2s;
            }
            .comm-quick-action:hover { background: #FDF8F3; transform: translateY(-2px); }
            .comm-quick-action i { font-size: 24px; color: #EC802B; }
            .comm-quick-action span { font-weight: 500; }
            
            .comm-list-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .comm-list-item:last-child { border-bottom: none; }
            .comm-list-item .info { flex: 1; }
            .comm-list-item .title { font-weight: 500; color: #333; }
            .comm-list-item .meta { font-size: 12px; color: #888; margin-top: 3px; }
            .comm-list-item .badge {
                padding: 4px 10px;
                border-radius: 15px;
                font-size: 11px;
                font-weight: 500;
            }
            
            @media (max-width: 900px) {
                .comm-row { grid-template-columns: 1fr; }
                .comm-quick-actions { grid-template-columns: 1fr; }
            }
        </style>
        
        <!-- Thống kê -->
        <div class="comm-stats">
            <div class="comm-stat-card">
                <div class="icon" style="background: #e8f5e9; color: #28a745;">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="value"><?php echo number_format($total_sent); ?></div>
                <div class="label">Thông báo đã gửi</div>
            </div>
            
            <div class="comm-stat-card">
                <div class="icon" style="background: #fff3e0; color: #EC802B;">
                    <span class="dashicons dashicons-bell"></span>
                </div>
                <div class="value"><?php echo number_format($total_notifications); ?></div>
                <div class="label">Tổng thông báo</div>
            </div>
            
            <div class="comm-stat-card">
                <div class="icon" style="background: #e3f2fd; color: #17a2b8;">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="value"><?php echo number_format($unread_notifications); ?></div>
                <div class="label">Chưa đọc</div>
            </div>
            
            <div class="comm-stat-card">
                <div class="icon" style="background: #fce4ec; color: #E91E63;">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="value"><?php echo number_format($total_contacts); ?></div>
                <div class="label">Liên hệ (<?php echo $new_contacts; ?> mới)</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="comm-panel">
            <h3><span class="dashicons dashicons-admin-tools"></span> Thao tác nhanh</h3>
            <div class="comm-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=petshop-send-notification'); ?>" class="comm-quick-action">
                    <span class="dashicons dashicons-megaphone"></span>
                    <span>Gửi thông báo mới</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=petshop-contacts'); ?>" class="comm-quick-action">
                    <span class="dashicons dashicons-email"></span>
                    <span>Xem liên hệ <?php if ($new_contacts > 0): ?><span style="background:#dc3545;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:5px;"><?php echo $new_contacts; ?></span><?php endif; ?></span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=petshop-auto-notification'); ?>" class="comm-quick-action">
                    <span class="dashicons dashicons-update"></span>
                    <span>Cài đặt thông báo tự động</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=petshop-email-settings'); ?>" class="comm-quick-action">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span>Cài đặt Email/SMTP</span>
                </a>
            </div>
        </div>
        
        <!-- Recent -->
        <div class="comm-row">
            <div class="comm-panel">
                <h3><span class="dashicons dashicons-clock"></span> Thông báo gửi gần đây</h3>
                <?php if (empty($recent_logs)): ?>
                    <p style="color:#888; text-align:center; padding:30px;">Chưa có thông báo nào được gửi</p>
                <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                    <div class="comm-list-item">
                        <div class="info">
                            <div class="title"><?php echo esc_html($log->title); ?></div>
                            <div class="meta">
                                <?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?> • 
                                <?php echo $log->recipient_count; ?> người nhận
                            </div>
                        </div>
                        <span class="badge" style="background:#e8f5e9;color:#28a745;">Đã gửi</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p style="margin-top:15px;"><a href="<?php echo admin_url('admin.php?page=petshop-notification-history'); ?>">Xem tất cả →</a></p>
            </div>
            
            <div class="comm-panel">
                <h3><span class="dashicons dashicons-email"></span> Liên hệ gần đây</h3>
                <?php if (empty($recent_contacts)): ?>
                    <p style="color:#888; text-align:center; padding:30px;">Chưa có liên hệ nào</p>
                <?php else: ?>
                    <?php foreach ($recent_contacts as $contact): 
                        $status = get_post_meta($contact->ID, 'contact_status', true);
                        $status_info = array(
                            'new' => array('label' => 'Mới', 'bg' => '#e3f2fd', 'color' => '#17a2b8'),
                            'read' => array('label' => 'Đã đọc', 'bg' => '#f5f5f5', 'color' => '#6c757d'),
                            'replied' => array('label' => 'Đã phản hồi', 'bg' => '#e8f5e9', 'color' => '#28a745'),
                        );
                        $info = $status_info[$status] ?? $status_info['new'];
                    ?>
                    <div class="comm-list-item">
                        <div class="info">
                            <div class="title"><?php echo esc_html($contact->post_title); ?></div>
                            <div class="meta">
                                <?php echo esc_html(get_post_meta($contact->ID, 'contact_email', true)); ?> • 
                                <?php echo human_time_diff(strtotime($contact->post_date), current_time('timestamp')); ?> trước
                            </div>
                        </div>
                        <span class="badge" style="background:<?php echo $info['bg']; ?>;color:<?php echo $info['color']; ?>;">
                            <?php echo $info['label']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p style="margin-top:15px;"><a href="<?php echo admin_url('admin.php?page=petshop-contacts'); ?>">Xem tất cả →</a></p>
            </div>
        </div>
    </div>
    <?php
}

// =============================================
// TRANG LIÊN HỆ
// =============================================
function petshop_contacts_page() {
    if (!current_user_can('edit_posts')) wp_die('Không có quyền.');
    // Xử lý cập nhật trạng thái
    if (isset($_POST['update_contact_status']) && isset($_POST['contact_id'])) {
        $contact_id = intval($_POST['contact_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        update_post_meta($contact_id, 'contact_status', $new_status);
        echo '<div class="notice notice-success"><p>Đã cập nhật trạng thái!</p></div>';
    }
    
    // Xử lý phản hồi
    if (isset($_POST['reply_contact']) && isset($_POST['contact_id'])) {
        $contact_id = intval($_POST['contact_id']);
        $reply_message = sanitize_textarea_field($_POST['reply_message']);
        $email = get_post_meta($contact_id, 'contact_email', true);
        $name = get_post_meta($contact_id, 'contact_name', true);
        
        // Gửi email phản hồi
        $shop_settings = get_option('petshop_shop_settings', array());
        $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
        
        $subject = "Phản hồi từ {$shop_name}";
        $message = petshop_get_email_template('reply', array(
            'name' => $name,
            'message' => $reply_message,
            'shop_name' => $shop_name,
        ));
        
        $sent = wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        
        if ($sent) {
            update_post_meta($contact_id, 'contact_status', 'replied');
            update_post_meta($contact_id, 'contact_reply', $reply_message);
            update_post_meta($contact_id, 'contact_reply_date', current_time('mysql'));
            echo '<div class="notice notice-success"><p>Đã gửi phản hồi thành công!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Gửi email thất bại. Kiểm tra cài đặt SMTP.</p></div>';
        }
    }
    
    // Lấy filter
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $paged = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;
    
    $args = array(
        'post_type' => 'petshop_contact',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    if ($status_filter) {
        $args['meta_query'] = array(
            array(
                'key' => 'contact_status',
                'value' => $status_filter,
            )
        );
    }
    
    $query = new WP_Query($args);
    $contacts = $query->posts;
    $total = $query->found_posts;
    $total_pages = ceil($total / $per_page);
    
    // Đếm theo status
    $count_new = count(get_posts(array('post_type' => 'petshop_contact', 'posts_per_page' => -1, 'meta_key' => 'contact_status', 'meta_value' => 'new', 'fields' => 'ids')));
    $count_read = count(get_posts(array('post_type' => 'petshop_contact', 'posts_per_page' => -1, 'meta_key' => 'contact_status', 'meta_value' => 'read', 'fields' => 'ids')));
    $count_replied = count(get_posts(array('post_type' => 'petshop_contact', 'posts_per_page' => -1, 'meta_key' => 'contact_status', 'meta_value' => 'replied', 'fields' => 'ids')));
    ?>
    <div class="wrap petshop-contacts-page">
        <h1><span class="dashicons dashicons-email"></span> Quản lý Liên hệ</h1>
        
        <style>
            .petshop-contacts-page { max-width: 1400px; }
            .contacts-filters {
                display: flex;
                gap: 10px;
                margin: 20px 0;
                flex-wrap: wrap;
            }
            .contacts-filter-btn {
                padding: 10px 20px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 25px;
                text-decoration: none;
                color: #666;
                font-size: 14px;
            }
            .contacts-filter-btn:hover { border-color: #EC802B; color: #EC802B; }
            .contacts-filter-btn.active { background: #EC802B; border-color: #EC802B; color: #fff; }
            
            .contacts-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            .contacts-table th, .contacts-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
            .contacts-table th { background: #f8f9fa; font-weight: 600; }
            .contacts-table tr:hover { background: #fafafa; }
            
            .contact-status {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 500;
            }
            .status-new { background: #e3f2fd; color: #1976D2; }
            .status-read { background: #f5f5f5; color: #666; }
            .status-replied { background: #e8f5e9; color: #28a745; }
            
            .contact-actions { display: flex; gap: 5px; }
            .contact-action-btn {
                width: 32px;
                height: 32px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: #666;
            }
            .contact-action-btn:hover { border-color: #EC802B; color: #EC802B; }
            
            .contact-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                align-items: center;
                justify-content: center;
            }
            .contact-modal.active { display: flex; }
            .contact-modal-content {
                background: #fff;
                border-radius: 15px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            .contact-modal-header {
                padding: 20px 25px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .contact-modal-header h3 { margin: 0; }
            .contact-modal-close {
                width: 30px;
                height: 30px;
                border: none;
                background: #f0f0f0;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
            }
            .contact-modal-body { padding: 25px; }
            .contact-detail-row { margin-bottom: 15px; }
            .contact-detail-row label { font-weight: 600; color: #666; font-size: 12px; text-transform: uppercase; }
            .contact-detail-row .value { margin-top: 5px; color: #333; }
            
            .pagination { display: flex; gap: 5px; margin-top: 20px; justify-content: center; }
            .pagination a, .pagination span {
                padding: 8px 14px;
                border: 1px solid #ddd;
                border-radius: 5px;
                text-decoration: none;
                color: #666;
            }
            .pagination a:hover { border-color: #EC802B; color: #EC802B; }
            .pagination .current { background: #EC802B; border-color: #EC802B; color: #fff; }
        </style>
        
        <!-- Filters -->
        <div class="contacts-filters">
            <a href="<?php echo remove_query_arg('status'); ?>" 
               class="contacts-filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                Tất cả (<?php echo $total; ?>)
            </a>
            <a href="<?php echo add_query_arg('status', 'new'); ?>" 
               class="contacts-filter-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>">
                Mới (<?php echo $count_new; ?>)
            </a>
            <a href="<?php echo add_query_arg('status', 'read'); ?>" 
               class="contacts-filter-btn <?php echo $status_filter === 'read' ? 'active' : ''; ?>">
                Đã đọc (<?php echo $count_read; ?>)
            </a>
            <a href="<?php echo add_query_arg('status', 'replied'); ?>" 
               class="contacts-filter-btn <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">
                Đã phản hồi (<?php echo $count_replied; ?>)
            </a>
        </div>
        
        <!-- Table -->
        <table class="contacts-table">
            <thead>
                <tr>
                    <th>Người gửi</th>
                    <th>Chủ đề</th>
                    <th>Nội dung</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:50px;color:#888;">Không có liên hệ nào</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): 
                        $name = get_post_meta($contact->ID, 'contact_name', true);
                        $email = get_post_meta($contact->ID, 'contact_email', true);
                        $phone = get_post_meta($contact->ID, 'contact_phone', true);
                        $subject = get_post_meta($contact->ID, 'contact_subject', true);
                        $message = get_post_meta($contact->ID, 'contact_message', true);
                        $status = get_post_meta($contact->ID, 'contact_status', true) ?: 'new';
                    ?>
                    <tr data-contact-id="<?php echo $contact->ID; ?>">
                        <td>
                            <strong><?php echo esc_html($name); ?></strong><br>
                            <small><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></small><br>
                            <small><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></small>
                        </td>
                        <td><?php echo esc_html($subject); ?></td>
                        <td><?php echo esc_html(wp_trim_words($message, 15)); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($contact->post_date)); ?></td>
                        <td>
                            <span class="contact-status status-<?php echo $status; ?>">
                                <?php 
                                echo $status === 'new' ? 'Mới' : ($status === 'read' ? 'Đã đọc' : 'Đã phản hồi');
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="contact-actions">
                                <button type="button" class="contact-action-btn view-contact" 
                                        data-id="<?php echo $contact->ID; ?>"
                                        data-name="<?php echo esc_attr($name); ?>"
                                        data-email="<?php echo esc_attr($email); ?>"
                                        data-phone="<?php echo esc_attr($phone); ?>"
                                        data-subject="<?php echo esc_attr($subject); ?>"
                                        data-message="<?php echo esc_attr($message); ?>"
                                        data-date="<?php echo date('d/m/Y H:i', strtotime($contact->post_date)); ?>"
                                        title="Xem">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="contact-action-btn reply-contact"
                                        data-id="<?php echo $contact->ID; ?>"
                                        data-name="<?php echo esc_attr($name); ?>"
                                        data-email="<?php echo esc_attr($email); ?>"
                                        title="Phản hồi">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                </button>
                                <?php if ($status === 'new'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact->ID; ?>">
                                    <input type="hidden" name="new_status" value="read">
                                    <button type="submit" name="update_contact_status" class="contact-action-btn" title="Đánh dấu đã đọc">
                                        <span class="dashicons dashicons-yes"></span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $paged): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- View Modal -->
    <div class="contact-modal" id="viewContactModal">
        <div class="contact-modal-content">
            <div class="contact-modal-header">
                <h3>Chi tiết liên hệ</h3>
                <button class="contact-modal-close" onclick="document.getElementById('viewContactModal').classList.remove('active')">×</button>
            </div>
            <div class="contact-modal-body">
                <div class="contact-detail-row">
                    <label>Họ tên</label>
                    <div class="value" id="modal-name"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Email</label>
                    <div class="value" id="modal-email"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Điện thoại</label>
                    <div class="value" id="modal-phone"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Chủ đề</label>
                    <div class="value" id="modal-subject"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Nội dung</label>
                    <div class="value" id="modal-message" style="white-space:pre-wrap;"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Ngày gửi</label>
                    <div class="value" id="modal-date"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reply Modal -->
    <div class="contact-modal" id="replyContactModal">
        <div class="contact-modal-content">
            <div class="contact-modal-header">
                <h3>Phản hồi liên hệ</h3>
                <button class="contact-modal-close" onclick="document.getElementById('replyContactModal').classList.remove('active')">×</button>
            </div>
            <div class="contact-modal-body">
                <form method="post">
                    <input type="hidden" name="contact_id" id="reply-contact-id">
                    <p><strong>Gửi đến:</strong> <span id="reply-to-info"></span></p>
                    <p>
                        <label><strong>Nội dung phản hồi:</strong></label><br>
                        <textarea name="reply_message" rows="6" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;" required></textarea>
                    </p>
                    <p>
                        <button type="submit" name="reply_contact" class="button button-primary" style="background:#EC802B;border-color:#EC802B;">
                            <span class="dashicons dashicons-email-alt" style="margin-top:4px;"></span> Gửi phản hồi
                        </button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    document.querySelectorAll('.view-contact').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal-name').textContent = this.dataset.name;
            document.getElementById('modal-email').textContent = this.dataset.email;
            document.getElementById('modal-phone').textContent = this.dataset.phone;
            document.getElementById('modal-subject').textContent = this.dataset.subject;
            document.getElementById('modal-message').textContent = this.dataset.message;
            document.getElementById('modal-date').textContent = this.dataset.date;
            document.getElementById('viewContactModal').classList.add('active');
        });
    });
    
    document.querySelectorAll('.reply-contact').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reply-contact-id').value = this.dataset.id;
            document.getElementById('reply-to-info').textContent = this.dataset.name + ' (' + this.dataset.email + ')';
            document.getElementById('replyContactModal').classList.add('active');
        });
    });
    
    document.querySelectorAll('.contact-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
    </script>
    <?php
}

// =============================================
// TRANG GỬI THÔNG BÁO - NÂNG CẤP
// =============================================
function petshop_send_notification_page() {
    $notification_types = function_exists('petshop_get_notification_types') ? petshop_get_notification_types() : array();
    
    // Kiểm tra SMTP
    $smtp_settings = get_option('petshop_smtp_settings', array());
    $smtp_enabled = !empty($smtp_settings['enabled']);
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
    $admin_email = get_option('admin_email');
    
    // Lấy templates đã lưu từ trang Mẫu thông báo
    $saved_templates = get_option('petshop_email_templates', array());
    
    // Default templates để hiển thị - với loại thông báo tương ứng
    $default_templates = array(
        'welcome' => array('name' => 'Chào mừng thành viên', 'icon' => 'bi-hand-wave', 'type' => 'system'),
        'order_placed' => array('name' => 'Xác nhận đặt hàng', 'icon' => 'bi-cart-check', 'type' => 'order'),
        'order_confirmed' => array('name' => 'Xác nhận đơn hàng', 'icon' => 'bi-check-circle', 'type' => 'order'),
        'order_shipping' => array('name' => 'Đang giao hàng', 'icon' => 'bi-truck', 'type' => 'order'),
        'order_completed' => array('name' => 'Hoàn thành đơn', 'icon' => 'bi-gift', 'type' => 'order'),
        'points_earned' => array('name' => 'Nhận điểm thưởng', 'icon' => 'bi-coin', 'type' => 'points'),
        'tier_upgrade' => array('name' => 'Nâng hạng thành viên', 'icon' => 'bi-trophy', 'type' => 'membership'),
        'birthday' => array('name' => 'Chúc mừng sinh nhật', 'icon' => 'bi-cake2', 'type' => 'system'),
        'promotion' => array('name' => 'Thông báo khuyến mãi', 'icon' => 'bi-tag', 'type' => 'promotion'),
        'flash_sale' => array('name' => 'Flash Sale', 'icon' => 'bi-lightning', 'type' => 'flash_sale'),
        'voucher_gift' => array('name' => 'Tặng Voucher', 'icon' => 'bi-gift', 'type' => 'voucher'),
        'reply' => array('name' => 'Phản hồi liên hệ', 'icon' => 'bi-chat-dots', 'type' => 'system'),
    );
    
    // All available variables
    $all_variables = array(
        'Khách hàng' => array(
            '{customer_name}' => 'Tên khách hàng',
            '{customer_email}' => 'Email khách hàng',
            '{customer_phone}' => 'Số điện thoại',
            '{customer_address}' => 'Địa chỉ',
            '{customer_username}' => 'Tên đăng nhập',
        ),
        'Xưng hô' => array(
            '{title}' => 'Danh xưng (Anh/Chị)',
            '{dear}' => 'Kính gửi (Quý khách/Anh/Chị)',
            '{greeting}' => 'Lời chào (Xin chào/Chào)',
            '{pronoun}' => 'Đại từ (bạn/anh/chị)',
        ),
        'Thời gian' => array(
            '{current_date}' => 'Ngày hiện tại (01/03/2026)',
            '{current_day}' => 'Ngày (01)',
            '{current_month}' => 'Tháng (03)',
            '{current_year}' => 'Năm (2026)',
            '{current_time}' => 'Giờ:Phút (14:30)',
            '{current_hour}' => 'Giờ (14)',
            '{current_minute}' => 'Phút (30)',
            '{day_period}' => 'Buổi (sáng/chiều/tối)',
            '{day_of_week}' => 'Thứ (Thứ Hai)',
            '{month_name}' => 'Tên tháng (Tháng Ba)',
        ),
        'Đơn hàng' => array(
            '{order_code}' => 'Mã đơn hàng',
            '{order_total}' => 'Tổng tiền đơn',
            '{order_date}' => 'Ngày đặt hàng',
            '{order_status}' => 'Trạng thái đơn',
            '{tracking_code}' => 'Mã vận đơn',
            '{shipping_method}' => 'Phương thức giao hàng',
            '{payment_method}' => 'Phương thức thanh toán',
            '{delivery_date}' => 'Ngày giao hàng dự kiến',
        ),
        'Điểm & Hạng' => array(
            '{points}' => 'Điểm nhận được',
            '{total_points}' => 'Tổng điểm hiện tại',
            '{new_tier}' => 'Hạng mới',
            '{current_tier}' => 'Hạng hiện tại',
            '{points_to_next}' => 'Điểm cần để lên hạng',
            '{points_expiry}' => 'Ngày hết hạn điểm',
        ),
        'Voucher' => array(
            '{voucher_code}' => 'Mã voucher',
            '{voucher_value}' => 'Giá trị voucher',
            '{voucher_expiry}' => 'Ngày hết hạn voucher',
            '{voucher_min_order}' => 'Đơn tối thiểu áp dụng',
        ),
        'Shop' => array(
            '{shop_name}' => 'Tên cửa hàng',
            '{shop_phone}' => 'SĐT cửa hàng',
            '{shop_email}' => 'Email cửa hàng',
            '{shop_address}' => 'Địa chỉ cửa hàng',
            '{shop_website}' => 'Website',
            '{shop_hotline}' => 'Hotline hỗ trợ',
        ),
        'Liên kết' => array(
            '{site_url}' => 'URL trang chủ',
            '{login_url}' => 'Link đăng nhập',
            '{account_url}' => 'Link tài khoản',
            '{order_url}' => 'Link xem đơn hàng',
            '{unsubscribe_url}' => 'Link hủy đăng ký',
        ),
    );
    
    // Xử lý gửi thông báo
    if (isset($_POST['send_notification']) && wp_verify_nonce($_POST['notification_nonce'], 'send_notification')) {
        $result = petshop_process_send_notification($_POST);
        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    ?>
    <!-- Load Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <div class="wrap petshop-send-notification">
        <h1><i class="bi bi-megaphone"></i> Gửi thông báo</h1>
        
        <?php if (!$smtp_enabled): ?>
        <div class="notice notice-warning" style="margin-top:15px;">
            <p><i class="bi bi-exclamation-triangle"></i> <strong>SMTP chưa được cấu hình!</strong> 
            Để gửi email, vui lòng <a href="<?php echo admin_url('admin.php?page=petshop-email-settings'); ?>">cấu hình SMTP</a> trước.</p>
        </div>
        <?php endif; ?>
        
        <style>
            .petshop-send-notification { margin-top: 20px; }
            .petshop-send-notification h1 { display: flex; align-items: center; gap: 10px; }
            .petshop-send-notification h1 i { color: #EC802B; }
            
            .notification-layout {
                display: flex;
                gap: 25px;
                margin-top: 25px;
            }
            .notification-main {
                flex: 1;
                min-width: 0;
            }
            .notification-sidebar {
                width: 280px;
                flex-shrink: 0;
            }
            
            .notification-form {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            
            /* Steps Header */
            .form-steps {
                display: flex;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                padding: 0;
            }
            .form-step {
                flex: 1;
                padding: 16px 15px;
                color: rgba(255,255,255,0.7);
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
                border-bottom: 3px solid transparent;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 14px;
            }
            .form-step:hover { background: rgba(255,255,255,0.1); }
            .form-step.active {
                color: #fff;
                background: rgba(255,255,255,0.15);
                border-bottom-color: #fff;
            }
            .form-step .step-num {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 12px;
            }
            .form-step.active .step-num { background: #fff; color: #EC802B; }
            .form-step i { font-size: 16px; }
            
            /* Form Panels */
            .form-panel {
                display: none;
                padding: 25px;
            }
            .form-panel.active { display: block; }
            
            .panel-title {
                font-size: 16px;
                font-weight: 700;
                color: #333;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .panel-title i { color: #EC802B; font-size: 20px; }
            
            /* Template Selector */
            .template-selector-group {
                display: flex;
                gap: 15px;
                align-items: flex-end;
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            .template-selector-group .field-group { flex: 1; margin-bottom: 0; }
            .template-selector-group select {
                padding: 12px 16px;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                font-size: 14px;
                width: 100%;
            }
            .template-selector-group select:focus {
                border-color: #EC802B;
                outline: none;
            }
            .btn-manage-templates {
                padding: 10px 16px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff !important;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 500;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                transition: all 0.2s;
            }
            .btn-manage-templates:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(236, 128, 43, 0.4);
            }
            .load-template-btn {
                padding: 12px 24px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                white-space: nowrap;
            }
            .load-template-btn:hover { transform: translateY(-2px); }
            
            /* Field Group */
            .field-group { margin-bottom: 18px; }
            .field-group label {
                display: flex;
                align-items: center;
                gap: 6px;
                font-weight: 600;
                color: #333;
                margin-bottom: 8px;
                font-size: 13px;
            }
            .field-group label i { color: #EC802B; font-size: 15px; }
            .field-group label small { font-weight: 400; color: #888; margin-left: 5px; }
            .field-group input[type="text"],
            .field-group input[type="email"],
            .field-group input[type="url"],
            .field-group textarea,
            .field-group select {
                width: 100%;
                padding: 11px 14px;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                font-size: 14px;
                transition: all 0.2s;
                box-sizing: border-box;
            }
            .field-group input:focus,
            .field-group textarea:focus,
            .field-group select:focus {
                border-color: #EC802B;
                outline: none;
                box-shadow: 0 0 0 3px rgba(236,128,43,0.1);
            }
            .field-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .field-row-3 {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            
            /* Editor Toolbar */
            .editor-toolbar {
                background: #f8f9fa;
                border: 2px solid #e8e8e8;
                border-bottom: none;
                border-radius: 10px 10px 0 0;
                padding: 8px 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 3px;
                align-items: center;
            }
            .toolbar-group {
                display: flex;
                gap: 2px;
                padding-right: 8px;
                margin-right: 8px;
                border-right: 1px solid #ddd;
            }
            .toolbar-group:last-child { border-right: none; margin-right: 0; padding-right: 0; }
            .toolbar-btn {
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 13px;
                color: #333;
            }
            .toolbar-btn i.bi { font-size: 14px; color: #333; }
            .toolbar-btn:hover { background: #EC802B; color: #fff; border-color: #EC802B; }
            .toolbar-btn:hover i.bi { color: #fff; }
            .toolbar-select {
                padding: 5px 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 12px;
                background: #fff;
                cursor: pointer;
            }
            .toolbar-color {
                width: 26px;
                height: 26px;
                padding: 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
            }
            
            /* Content Editor */
            .content-editor {
                min-height: 200px;
                padding: 18px;
                border: 2px solid #e8e8e8;
                border-radius: 0 0 10px 10px;
                font-size: 14px;
                line-height: 1.7;
                background: #fff;
                overflow-y: auto;
            }
            .content-editor:focus {
                outline: none;
                border-color: #EC802B;
            }
            
            /* Notification Types */
            .type-selector {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .type-btn {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                border: 2px solid #e8e8e8;
                border-radius: 20px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 12px;
                font-weight: 500;
            }
            .type-btn:hover { border-color: var(--type-color, #EC802B); }
            .type-btn.active { 
                background: var(--type-color, #EC802B); 
                color: #fff; 
                border-color: var(--type-color, #EC802B); 
            }
            .type-btn.active i { color: #fff !important; }
            .type-btn input { display: none; }
            .type-btn i { font-size: 14px; }
            
            /* Recipient Cards */
            .recipient-cards {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 10px;
            }
            .recipient-card {
                padding: 15px 12px;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
            }
            .recipient-card:hover { border-color: #EC802B; }
            .recipient-card.active { border-color: #EC802B; background: #FFF8F4; }
            .recipient-card input { display: none; }
            .recipient-card i { font-size: 24px; color: #EC802B; display: block; margin-bottom: 6px; }
            .recipient-card .title { font-weight: 600; font-size: 13px; color: #333; }
            .recipient-card .desc { font-size: 10px; color: #888; margin-top: 3px; }
            
            /* Sub Options */
            .sub-options {
                margin-top: 18px;
                padding: 18px;
                background: #f8f9fa;
                border-radius: 10px;
                display: none;
            }
            .sub-options.show { display: block; }
            .sub-options h4 {
                margin: 0 0 12px;
                font-size: 13px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .sub-options h4 i { color: #EC802B; }
            
            .tier-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .tier-chip {
                padding: 8px 14px;
                border: 2px solid #e8e8e8;
                border-radius: 20px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .tier-chip:hover { border-color: #EC802B; }
            .tier-chip.active { border-color: #EC802B; background: #EC802B; color: #fff; }
            .tier-chip input { display: none; }
            
            /* User Search */
            .user-search-wrapper {
                display: flex;
                gap: 10px;
                margin-bottom: 12px;
            }
            .user-search-wrapper input { flex: 1; padding: 10px 14px; }
            .user-search-wrapper button {
                padding: 10px 20px;
                background: #EC802B;
                color: #fff;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 13px;
            }
            .search-results-list {
                max-height: 150px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
                margin-bottom: 12px;
                display: none;
            }
            .search-result-item {
                padding: 10px 14px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
            }
            .search-result-item:hover { background: #f8f9fa; }
            .search-result-item:last-child { border-bottom: none; }
            .search-result-item i { color: #EC802B; }
            
            .selected-users-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .selected-user-tag {
                display: flex;
                align-items: center;
                gap: 5px;
                padding: 6px 12px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 16px;
                font-size: 12px;
            }
            .selected-user-tag .remove {
                cursor: pointer;
                opacity: 0.8;
                font-size: 14px;
            }
            .selected-user-tag .remove:hover { opacity: 1; }
            
            /* Channel Selection */
            .channel-options {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }
            .channel-option {
                padding: 16px;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s;
                position: relative;
            }
            .channel-option:hover { border-color: #EC802B; }
            .channel-option.active { border-color: #EC802B; background: #FFF8F4; }
            .channel-option.disabled { opacity: 0.5; cursor: not-allowed; }
            .channel-option input { display: none; }
            .channel-option .check {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 22px;
                height: 22px;
                background: #EC802B;
                border-radius: 50%;
                display: none;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 12px;
            }
            .channel-option.active .check { display: flex; }
            .channel-option-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
            }
            .channel-option-header i { font-size: 22px; color: #EC802B; }
            .channel-option-header strong { font-size: 14px; }
            .channel-option p { margin: 0; font-size: 11px; color: #888; }
            
            /* Email Config Section */
            .email-config-section {
                margin-top: 20px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 12px;
                display: none;
            }
            .email-config-section.show { display: block; }
            .email-config-section h4 {
                margin: 0 0 18px;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 8px;
                color: #333;
            }
            .email-config-section h4 i { color: #EC802B; }
            
            /* Navigation Buttons */
            .form-navigation {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 25px;
                background: #f8f9fa;
                border-top: 1px solid #eee;
            }
            .nav-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                border-radius: 25px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            .nav-btn.prev {
                background: #fff;
                border: 2px solid #ddd;
                color: #666;
            }
            .nav-btn.prev:hover { border-color: #EC802B; color: #EC802B; }
            .nav-btn.next {
                background: linear-gradient(135deg, #EC802B, #F5994D);
                border: none;
                color: #fff;
            }
            .nav-btn.next:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(236,128,43,0.3); }
            .nav-btn.submit {
                background: linear-gradient(135deg, #28a745, #34c759);
                border: none;
                color: #fff;
                padding: 12px 30px;
            }
            .nav-btn.submit:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(40,167,69,0.3); }
            .nav-btn i { font-size: 14px; }
            
            .preview-btn {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 10px 18px;
                background: #fff;
                border: 2px solid #ddd;
                border-radius: 20px;
                color: #666;
                cursor: pointer;
                font-size: 12px;
            }
            .preview-btn:hover { border-color: #EC802B; color: #EC802B; }
            
            /* Sidebar - Variables */
            .variables-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                position: sticky;
                top: 40px;
            }
            .variables-header {
                padding: 15px 18px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 12px 12px 0 0;
            }
            .variables-header h3 {
                margin: 0;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .variables-header p {
                margin: 5px 0 0;
                font-size: 11px;
                opacity: 0.9;
            }
            .variables-body {
                padding: 12px;
                max-height: 500px;
                overflow-y: auto;
            }
            .variable-group {
                margin-bottom: 12px;
            }
            .variable-group:last-child { margin-bottom: 0; }
            .variable-group-title {
                font-size: 11px;
                font-weight: 600;
                color: #888;
                text-transform: uppercase;
                margin-bottom: 6px;
                padding-bottom: 4px;
                border-bottom: 1px solid #eee;
            }
            .variable-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 7px 10px;
                background: #f8f9fa;
                border-radius: 6px;
                margin-bottom: 4px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 12px;
            }
            .variable-item:hover {
                background: #EC802B;
                color: #fff;
            }
            .variable-item code {
                background: rgba(0,0,0,0.08);
                padding: 2px 5px;
                border-radius: 3px;
                font-size: 10px;
            }
            .variable-item:hover code {
                background: rgba(255,255,255,0.2);
            }
            
            /* Summary */
            .send-summary {
                margin-top: 20px;
                padding: 18px;
                background: #f0fff4;
                border-radius: 10px;
                border: 1px solid #c6f6d5;
            }
            .send-summary h4 {
                margin: 0 0 8px;
                color: #276749;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
            }
            .send-summary p {
                margin: 0;
                color: #2f855a;
                font-size: 13px;
            }
            
            /* Step 2 Section Subtitle */
            .section-subtitle {
                font-size: 14px;
                font-weight: 600;
                color: #333;
                margin: 0 0 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .section-subtitle i { color: #EC802B; }
            
            /* Step 3 - Confirmation Styles */
            .confirmation-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 25px;
            }
            .confirm-card {
                background: #f8f9fa;
                border-radius: 12px;
                border: 1px solid #e8e8e8;
                overflow: hidden;
            }
            .confirm-card-header {
                padding: 12px 15px;
                background: #fff;
                border-bottom: 1px solid #e8e8e8;
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 600;
                font-size: 13px;
            }
            .confirm-card-header i { color: #EC802B; font-size: 16px; }
            .confirm-card-header span { flex: 1; }
            .edit-step-btn {
                background: none;
                border: 1px solid #ddd;
                border-radius: 15px;
                padding: 4px 10px;
                font-size: 11px;
                color: #666;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 4px;
                transition: all 0.2s;
            }
            .edit-step-btn:hover { border-color: #EC802B; color: #EC802B; }
            .confirm-card-body { padding: 15px; }
            .confirm-item {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 8px;
            }
            .confirm-item:last-child { margin-bottom: 0; }
            .confirm-label { 
                color: #888; 
                font-size: 12px; 
                min-width: 70px;
            }
            .confirm-value { 
                font-size: 13px; 
                color: #333;
                font-weight: 500;
            }
            .confirm-channels {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .channel-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 12px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 500;
            }
            .channel-badge i { font-size: 12px; }
            
            /* Email Preview */
            .email-preview-section {
                margin-bottom: 25px;
            }
            .email-preview-frame {
                background: #fff;
                border: 2px solid #e8e8e8;
                border-radius: 12px;
                overflow: hidden;
                max-height: 400px;
                overflow-y: auto;
            }
            .email-preview-content {
                padding: 0;
            }
            
            /* System Notification Preview */
            .system-preview-section {
                margin-bottom: 25px;
            }
            .system-preview-card {
                background: #fff;
                border: 2px solid #e8e8e8;
                border-radius: 12px;
                overflow: hidden;
                max-width: 400px;
            }
            .system-preview-header {
                padding: 12px 15px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .system-preview-header i { font-size: 18px; }
            .system-preview-title {
                flex: 1;
                font-weight: 600;
                font-size: 14px;
            }
            .system-preview-time {
                font-size: 11px;
                opacity: 0.8;
            }
            .system-preview-body {
                padding: 15px;
                font-size: 13px;
                line-height: 1.6;
                color: #555;
                max-height: 150px;
                overflow-y: auto;
            }
            .system-preview-footer {
                padding: 10px 15px;
                border-top: 1px solid #eee;
                background: #f8f9fa;
            }
            .system-preview-btn {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #EC802B;
                text-decoration: none;
                font-size: 12px;
                font-weight: 600;
            }
            .system-preview-btn:hover { text-decoration: underline; }
            
            /* Final Confirm Box */
            .final-confirm-box {
                display: flex;
                align-items: center;
                gap: 18px;
                padding: 20px;
                background: linear-gradient(135deg, #d4edda, #c3e6cb);
                border-radius: 12px;
                border: 1px solid #28a745;
            }
            .final-confirm-icon {
                width: 50px;
                height: 50px;
                background: #28a745;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 24px;
            }
            .final-confirm-text h4 {
                margin: 0 0 5px;
                color: #155724;
                font-size: 16px;
            }
            .final-confirm-text p {
                margin: 0;
                color: #155724;
                font-size: 13px;
            }
            
            /* Link Insert Modal */
            .link-modal {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 100001;
                align-items: center;
                justify-content: center;
            }
            .link-modal.show { display: flex; }
            .link-modal-content {
                background: #fff;
                border-radius: 12px;
                width: 90%;
                max-width: 450px;
                box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            }
            .link-modal-header {
                padding: 15px 20px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 12px 12px 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .link-modal-header h4 { margin: 0; font-size: 15px; }
            .link-modal-close {
                background: rgba(255,255,255,0.2);
                border: none;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                color: #fff;
                cursor: pointer;
                font-size: 16px;
            }
            .link-modal-body { padding: 20px; }
            .link-modal-body .field-group { margin-bottom: 15px; }
            .link-modal-body .field-group:last-child { margin-bottom: 0; }
            .link-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            .link-modal-footer button {
                padding: 10px 20px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }
            .link-modal-footer .btn-cancel {
                background: #fff;
                border: 1px solid #ddd;
                color: #666;
            }
            .link-modal-footer .btn-insert {
                background: linear-gradient(135deg, #EC802B, #F5994D);
                border: none;
                color: #fff;
            }
            
            @media (max-width: 992px) {
                .notification-layout { flex-direction: column; }
                .notification-sidebar { width: 100%; }
                .confirmation-grid { grid-template-columns: 1fr; }
            }
            @media (max-width: 768px) {
                .form-steps { flex-direction: column; }
                .form-step { padding: 12px 15px; }
                .field-row, .field-row-3 { grid-template-columns: 1fr; }
                .channel-options { grid-template-columns: 1fr; }
                .recipient-cards { grid-template-columns: repeat(2, 1fr); }
            }
        </style>
        
        <form method="post" id="notificationForm">
            <?php wp_nonce_field('send_notification', 'notification_nonce'); ?>
            
            <div class="notification-layout">
                <div class="notification-main">
                    <div class="notification-form">
                        <!-- Steps Header -->
                        <div class="form-steps">
                            <div class="form-step active" data-step="1">
                                <span class="step-num">1</span>
                                <i class="bi bi-pencil-square"></i>
                                <span>Nội dung</span>
                            </div>
                            <div class="form-step" data-step="2">
                                <span class="step-num">2</span>
                                <i class="bi bi-people"></i>
                                <span>Người nhận & Kênh</span>
                            </div>
                            <div class="form-step" data-step="3">
                                <span class="step-num">3</span>
                                <i class="bi bi-check-circle"></i>
                                <span>Xác nhận</span>
                            </div>
                        </div>
                        
                        <!-- Step 1: Content -->
                        <div class="form-panel active" data-panel="1">
                            <div class="panel-title">
                                <i class="bi bi-pencil-square"></i>
                                Soạn nội dung thông báo
                            </div>
                            
                            <!-- Load from saved templates -->
                            <div class="template-selector-group">
                                <div class="field-group" style="flex:1;margin-bottom:0;">
                                    <label><i class="bi bi-file-earmark-text"></i> Sử dụng mẫu có sẵn <small>(Chọn để tự động tải nội dung)</small></label>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <select id="templateSelector" style="flex:1;">
                                            <option value="" data-type="system">-- Tự viết nội dung mới --</option>
                                            <?php foreach ($default_templates as $key => $tpl): 
                                                $has_content = !empty($saved_templates[$key]['content']);
                                            ?>
                                            <option value="<?php echo $key; ?>" 
                                                    data-subject="<?php echo esc_attr($saved_templates[$key]['subject'] ?? ''); ?>"
                                                    data-content="<?php echo esc_attr($saved_templates[$key]['content'] ?? ''); ?>"
                                                    data-type="<?php echo esc_attr($tpl['type']); ?>"
                                                    <?php echo !$has_content ? 'disabled' : ''; ?>>
                                                <?php echo $tpl['name']; ?><?php echo !$has_content ? ' (chưa soạn)' : ''; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <a href="<?php echo admin_url('admin.php?page=petshop-email-templates'); ?>" class="btn-manage-templates" title="Quản lý mẫu thông báo">
                                            <i class="bi bi-gear"></i> Quản lý mẫu
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notification Type -->
                            <div class="field-group">
                                <label><i class="bi bi-bookmark"></i> Loại thông báo</label>
                                <div class="type-selector">
                                    <?php foreach ($notification_types as $key => $type): ?>
                                    <label class="type-btn <?php echo $key === 'system' ? 'active' : ''; ?>" style="--type-color: <?php echo $type['color']; ?>">
                                        <input type="radio" name="notification_type" value="<?php echo $key; ?>" <?php checked($key, 'system'); ?>>
                                        <i class="bi <?php echo $type['icon']; ?>" style="color: <?php echo $type['color']; ?>"></i>
                                        <span><?php echo $type['label']; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Title -->
                            <div class="field-group">
                                <label><i class="bi bi-type-h1"></i> Tiêu đề thông báo <small>*</small></label>
                                <input type="text" name="notification_title" id="notifTitle" required placeholder="VD: Thông báo nghỉ lễ 30/4 - 1/5">
                            </div>
                            
                            <!-- Content with Toolbar -->
                            <div class="field-group">
                                <label><i class="bi bi-text-paragraph"></i> Nội dung <small>(Click biến ở sidebar để chèn)</small></label>
                                
                                <div class="editor-toolbar">
                                    <div class="toolbar-group">
                                        <select class="toolbar-select font-size-select" title="Cỡ chữ">
                                            <option value="">Cỡ chữ</option>
                                            <option value="1">Rất nhỏ</option>
                                            <option value="2">Nhỏ</option>
                                            <option value="3">Bình thường</option>
                                            <option value="4">Vừa</option>
                                            <option value="5">Lớn</option>
                                            <option value="6">Rất lớn</option>
                                            <option value="7">Cực lớn</option>
                                        </select>
                                        <select class="toolbar-select font-family-select" title="Phông chữ">
                                            <option value="">Phông chữ</option>
                                            <option value="Arial">Arial</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Courier New">Courier New</option>
                                        </select>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="bold" title="In đậm (Ctrl+B)"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="italic" title="In nghiêng (Ctrl+I)"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="underline" title="Gạch chân (Ctrl+U)"><i class="bi bi-type-underline"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="strikeThrough" title="Gạch ngang"><i class="bi bi-type-strikethrough"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <input type="color" class="toolbar-color" data-command="foreColor" value="#000000" title="Màu chữ">
                                        <input type="color" class="toolbar-color" data-command="hiliteColor" value="#ffff00" title="Màu nền">
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="justifyLeft" title="Căn trái"><i class="bi bi-text-left"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="justifyCenter" title="Căn giữa"><i class="bi bi-text-center"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="justifyRight" title="Căn phải"><i class="bi bi-text-right"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="insertUnorderedList" title="Danh sách chấm"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="insertOrderedList" title="Danh sách số"><i class="bi bi-list-ol"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="indent" title="Thụt vào"><i class="bi bi-text-indent-left"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="outdent" title="Thụt ra"><i class="bi bi-text-indent-right"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn btn-link" title="Chèn liên kết"><i class="bi bi-link-45deg"></i></button>
                                        <button type="button" class="toolbar-btn btn-image" title="Chèn hình ảnh"><i class="bi bi-image"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="removeFormat" title="Xóa định dạng"><i class="bi bi-eraser"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="undo" title="Hoàn tác"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="redo" title="Làm lại"><i class="bi bi-arrow-clockwise"></i></button>
                                    </div>
                                </div>
                                
                                <div class="content-editor" contenteditable="true" id="notifMessage" placeholder="Nhập nội dung thông báo..."></div>
                                <input type="hidden" name="notification_message" id="notifMessageHidden">
                            </div>
                        </div>
                        
                        <!-- Step 2: Recipients & Channels (Merged) -->
                        <div class="form-panel" data-panel="2">
                            <div class="panel-title">
                                <i class="bi bi-people"></i>
                                Chọn người nhận & kênh gửi
                            </div>
                            
                            <!-- Recipient Selection -->
                            <div class="step2-section">
                                <h4 class="section-subtitle"><i class="bi bi-person-check"></i> Người nhận</h4>
                                <div class="recipient-cards">
                                    <label class="recipient-card active">
                                        <input type="radio" name="recipient_type" value="all" checked>
                                        <i class="bi bi-people-fill"></i>
                                        <div class="title">Tất cả</div>
                                        <div class="desc">Gửi đến tất cả</div>
                                    </label>
                                    <label class="recipient-card">
                                        <input type="radio" name="recipient_type" value="customers">
                                        <i class="bi bi-cart-check"></i>
                                        <div class="title">Khách hàng</div>
                                        <div class="desc">Đã đăng ký</div>
                                    </label>
                                    <label class="recipient-card">
                                        <input type="radio" name="recipient_type" value="tier">
                                        <i class="bi bi-award"></i>
                                        <div class="title">Theo hạng</div>
                                        <div class="desc">Hạng thành viên</div>
                                    </label>
                                    <label class="recipient-card">
                                        <input type="radio" name="recipient_type" value="specific">
                                        <i class="bi bi-person-check"></i>
                                        <div class="title">Chọn người</div>
                                        <div class="desc">Cụ thể</div>
                                    </label>
                                    <label class="recipient-card">
                                        <input type="radio" name="recipient_type" value="staff">
                                        <i class="bi bi-person-badge"></i>
                                        <div class="title">Nhân viên</div>
                                        <div class="desc">Admin/Editor</div>
                                    </label>
                                </div>
                                
                                <!-- Tier Selection -->
                                <div class="sub-options" id="tierSubOptions">
                                    <h4><i class="bi bi-award"></i> Chọn hạng thành viên</h4>
                                    <div class="tier-chips">
                                        <label class="tier-chip">
                                            <input type="checkbox" name="tiers[]" value="gold">
                                            <i class="bi bi-gem" style="color:#FFD700"></i> Vàng
                                        </label>
                                        <label class="tier-chip">
                                            <input type="checkbox" name="tiers[]" value="silver">
                                            <i class="bi bi-gem" style="color:#C0C0C0"></i> Bạc
                                        </label>
                                        <label class="tier-chip">
                                            <input type="checkbox" name="tiers[]" value="bronze">
                                            <i class="bi bi-gem" style="color:#CD7F32"></i> Đồng
                                        </label>
                                        <label class="tier-chip">
                                            <input type="checkbox" name="tiers[]" value="member">
                                            <i class="bi bi-person"></i> Thành viên
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- User Search -->
                                <div class="sub-options" id="userSubOptions">
                                    <h4><i class="bi bi-search"></i> Tìm kiếm người dùng</h4>
                                    <div class="user-search-wrapper">
                                        <input type="text" id="userSearchInput" placeholder="Nhập tên, email hoặc SĐT...">
                                        <button type="button" onclick="searchUsers()">
                                            <i class="bi bi-search"></i> Tìm
                                        </button>
                                    </div>
                                    <div class="search-results-list" id="searchResultsList"></div>
                                    <div class="selected-users-list" id="selectedUsersList"></div>
                                    <input type="hidden" name="selected_user_ids" id="selectedUserIds">
                                </div>
                            </div>
                            
                            <!-- Channel Selection -->
                            <div class="step2-section" style="margin-top:25px;">
                                <h4 class="section-subtitle"><i class="bi bi-broadcast"></i> Kênh gửi</h4>
                                <div class="channel-options">
                                    <label class="channel-option active">
                                        <input type="checkbox" name="channels[]" value="system" checked>
                                        <span class="check"><i class="bi bi-check"></i></span>
                                        <div class="channel-option-header">
                                            <i class="bi bi-bell"></i>
                                            <strong>Hệ thống</strong>
                                        </div>
                                        <p>Thông báo trên website</p>
                                    </label>
                                    
                                    <label class="channel-option <?php echo !$smtp_enabled ? 'disabled' : ''; ?>" id="emailChannelOption">
                                        <input type="checkbox" name="channels[]" value="email" <?php echo !$smtp_enabled ? 'disabled' : ''; ?>>
                                        <span class="check"><i class="bi bi-check"></i></span>
                                        <div class="channel-option-header">
                                            <i class="bi bi-envelope"></i>
                                            <strong>Email</strong>
                                        </div>
                                        <p><?php echo $smtp_enabled ? 'Gửi qua email' : '⚠️ Cần SMTP'; ?></p>
                                    </label>
                                    
                                    <label class="channel-option disabled">
                                        <input type="checkbox" name="channels[]" value="sms" disabled>
                                        <span class="check"><i class="bi bi-check"></i></span>
                                        <div class="channel-option-header">
                                            <i class="bi bi-phone"></i>
                                            <strong>SMS</strong>
                                        </div>
                                        <p>🔜 Sắp ra mắt</p>
                                    </label>
                                </div>
                                
                                <!-- Email Configuration -->
                                <div class="email-config-section" id="emailConfigSection">
                                    <h4><i class="bi bi-envelope-paper"></i> Cấu hình Email</h4>
                                    
                                    <div class="field-row-3">
                                        <div class="field-group">
                                            <label><i class="bi bi-person"></i> Tên người gửi</label>
                                            <input type="text" name="email_from_name" value="<?php echo esc_attr($shop_name); ?>" placeholder="<?php echo esc_attr($shop_name); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label><i class="bi bi-envelope"></i> Email người gửi</label>
                                            <input type="email" name="email_from_email" value="<?php echo esc_attr($admin_email); ?>" placeholder="<?php echo esc_attr($admin_email); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label><i class="bi bi-reply"></i> Reply-To</label>
                                            <input type="email" name="email_reply_to" placeholder="support@example.com">
                                        </div>
                                    </div>
                                    
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label><i class="bi bi-type-h3"></i> Tiêu đề Email <small>(để trống = dùng tiêu đề thông báo)</small></label>
                                            <input type="text" name="email_subject" placeholder="Tiêu đề email riêng...">
                                        </div>
                                    </div>
                                    
                                    <div class="field-row">
                                        <div class="field-group">
                                            <label><i class="bi bi-people"></i> CC <small>(ngăn cách bằng dấu phẩy)</small></label>
                                            <input type="text" name="email_cc" placeholder="email1@mail.com, email2@mail.com">
                                        </div>
                                        <div class="field-group">
                                            <label><i class="bi bi-eye-slash"></i> BCC</label>
                                            <input type="text" name="email_bcc" placeholder="admin@mail.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 3: Confirmation & Preview -->
                        <div class="form-panel" data-panel="3">
                            <div class="panel-title">
                                <i class="bi bi-check-circle"></i>
                                Xác nhận thông báo
                            </div>
                            
                            <!-- Summary Cards -->
                            <div class="confirmation-grid">
                                <!-- Content Summary -->
                                <div class="confirm-card">
                                    <div class="confirm-card-header">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Nội dung thông báo</span>
                                        <button type="button" class="edit-step-btn" data-goto="1"><i class="bi bi-pencil"></i> Sửa</button>
                                    </div>
                                    <div class="confirm-card-body">
                                        <div class="confirm-item">
                                            <span class="confirm-label">Loại:</span>
                                            <span class="confirm-value" id="confirmType">Hệ thống</span>
                                        </div>
                                        <div class="confirm-item">
                                            <span class="confirm-label">Tiêu đề:</span>
                                            <span class="confirm-value" id="confirmTitle">-</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Recipients Summary -->
                                <div class="confirm-card">
                                    <div class="confirm-card-header">
                                        <i class="bi bi-people"></i>
                                        <span>Người nhận</span>
                                        <button type="button" class="edit-step-btn" data-goto="2"><i class="bi bi-pencil"></i> Sửa</button>
                                    </div>
                                    <div class="confirm-card-body">
                                        <div class="confirm-item">
                                            <span class="confirm-label">Đối tượng:</span>
                                            <span class="confirm-value" id="confirmRecipients">Tất cả người dùng</span>
                                        </div>
                                        <div class="confirm-item">
                                            <span class="confirm-label">Số lượng ước tính:</span>
                                            <span class="confirm-value" id="confirmCount">-</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Channels Summary -->
                                <div class="confirm-card">
                                    <div class="confirm-card-header">
                                        <i class="bi bi-broadcast"></i>
                                        <span>Kênh gửi</span>
                                        <button type="button" class="edit-step-btn" data-goto="2"><i class="bi bi-pencil"></i> Sửa</button>
                                    </div>
                                    <div class="confirm-card-body">
                                        <div class="confirm-channels" id="confirmChannels">
                                            <span class="channel-badge"><i class="bi bi-bell"></i> Hệ thống</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Preview -->
                            <div class="email-preview-section" id="emailPreviewSection" style="display:none;">
                                <h4 class="section-subtitle"><i class="bi bi-envelope-open"></i> Xem trước Email</h4>
                                <div class="email-preview-frame">
                                    <div class="email-preview-content" id="emailPreviewContent"></div>
                                </div>
                            </div>
                            
                            <!-- System Notification Preview -->
                            <div class="system-preview-section">
                                <h4 class="section-subtitle"><i class="bi bi-bell"></i> Xem trước thông báo hệ thống</h4>
                                <div class="system-preview-card">
                                    <div class="system-preview-header">
                                        <i class="bi bi-bell-fill"></i>
                                        <span class="system-preview-title" id="systemPreviewTitle">Tiêu đề thông báo</span>
                                        <span class="system-preview-time">Vừa xong</span>
                                    </div>
                                    <div class="system-preview-body" id="systemPreviewBody">
                                        Nội dung thông báo...
                                    </div>
                                    <div class="system-preview-footer">
                                        <a href="#" class="system-preview-btn">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Final Confirm -->
                            <div class="final-confirm-box">
                                <div class="final-confirm-icon">
                                    <i class="bi bi-send-check"></i>
                                </div>
                                <div class="final-confirm-text">
                                    <h4>Sẵn sàng gửi thông báo!</h4>
                                    <p>Kiểm tra lại thông tin trên và nhấn <strong>"Gửi thông báo"</strong> để hoàn tất.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Navigation -->
                        <div class="form-navigation">
                            <button type="button" class="nav-btn prev" id="prevBtn" style="display:none;">
                                <i class="bi bi-arrow-left"></i> Quay lại
                            </button>
                            <div style="flex:1;"></div>
                            <button type="button" class="preview-btn" onclick="previewNotification()">
                                <i class="bi bi-eye"></i> Xem trước
                            </button>
                            <button type="button" class="nav-btn next" id="nextBtn">
                                Tiếp theo <i class="bi bi-arrow-right"></i>
                            </button>
                            <button type="submit" name="send_notification" class="nav-btn submit" id="submitBtn" style="display:none;">
                                <i class="bi bi-send-fill"></i> Gửi thông báo
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar - Variables -->
                <div class="notification-sidebar">
                    <div class="variables-card">
                        <div class="variables-header">
                            <h3><i class="bi bi-code-square"></i> Biến tùy chỉnh</h3>
                            <p>Click để chèn vào nội dung</p>
                        </div>
                        <div class="variables-body">
                            <?php foreach ($all_variables as $group_name => $vars): ?>
                            <div class="variable-group">
                                <div class="variable-group-title"><?php echo esc_html($group_name); ?></div>
                                <?php foreach ($vars as $var => $desc): ?>
                                <div class="variable-item" data-variable="<?php echo esc_attr($var); ?>">
                                    <span><?php echo esc_html($desc); ?></span>
                                    <code><?php echo esc_html($var); ?></code>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Preview Modal -->
    <div id="previewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:100000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;max-width:650px;width:90%;max-height:85vh;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
            <div style="padding:18px 24px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:600;display:flex;align-items:center;gap:8px;"><i class="bi bi-eye"></i> Xem trước Email</span>
                <button onclick="document.getElementById('previewModal').style.display='none'" style="border:none;background:rgba(255,255,255,0.2);width:32px;height:32px;border-radius:50%;cursor:pointer;color:#fff;font-size:18px;">×</button>
            </div>
            <div id="previewContent" style="padding:25px;overflow-y:auto;max-height:calc(85vh - 60px);"></div>
        </div>
    </div>
    
    <!-- Link Insert Modal -->
    <div id="linkModal" class="link-modal">
        <div class="link-modal-content">
            <div class="link-modal-header">
                <h4><i class="bi bi-link-45deg"></i> Chèn liên kết</h4>
                <button type="button" class="link-modal-close" onclick="closeLinkModal()">&times;</button>
            </div>
            <div class="link-modal-body">
                <div class="field-group">
                    <label><i class="bi bi-type"></i> Văn bản hiển thị</label>
                    <input type="text" id="linkText" placeholder="Nhấn vào đây...">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-link"></i> URL</label>
                    <input type="url" id="linkUrl" placeholder="https://example.com">
                </div>
            </div>
            <div class="link-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeLinkModal()">Hủy</button>
                <button type="button" class="btn-insert" onclick="insertLink()">Chèn liên kết</button>
            </div>
        </div>
    </div>
    
    <!-- Image Insert Modal -->
    <div id="imageModal" class="link-modal">
        <div class="link-modal-content">
            <div class="link-modal-header">
                <h4><i class="bi bi-image"></i> Chèn hình ảnh</h4>
                <button type="button" class="link-modal-close" onclick="closeImageModal()">&times;</button>
            </div>
            <div class="link-modal-body">
                <div class="field-group">
                    <label><i class="bi bi-link"></i> URL hình ảnh</label>
                    <input type="url" id="imageUrl" placeholder="https://example.com/image.jpg">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-textarea"></i> Mô tả (alt text)</label>
                    <input type="text" id="imageAlt" placeholder="Mô tả hình ảnh...">
                </div>
                <div class="field-group">
                    <label><i class="bi bi-arrows-angle-expand"></i> Chiều rộng (px)</label>
                    <input type="number" id="imageWidth" placeholder="400" value="400">
                </div>
            </div>
            <div class="link-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeImageModal()">Hủy</button>
                <button type="button" class="btn-insert" onclick="insertImage()">Chèn hình ảnh</button>
            </div>
        </div>
    </div>
    
    <script>
    let currentStep = 1;
    const totalSteps = 3;
    let selectedUsers = [];
    let savedSelection = null;
    
    // Notification type labels
    const typeLabels = {
        'system': 'Hệ thống',
        'order': 'Đơn hàng',
        'promotion': 'Khuyến mãi',
        'points': 'Điểm thưởng',
        'voucher': 'Voucher',
        'flash_sale': 'Flash Sale',
        'membership': 'Hạng thành viên'
    };
    
    // Recipient type labels
    const recipientLabels = {
        'all': 'Tất cả người dùng',
        'customers': 'Khách hàng đã đăng ký',
        'tier': 'Theo hạng thành viên',
        'specific': 'Người dùng cụ thể',
        'staff': 'Nhân viên (Admin/Editor)'
    };
    
    // Channel labels
    const channelLabels = {
        'system': { label: 'Hệ thống', icon: 'bi-bell' },
        'email': { label: 'Email', icon: 'bi-envelope' },
        'sms': { label: 'SMS', icon: 'bi-phone' }
    };
    
    // Step Navigation
    function updateStepUI() {
        document.querySelectorAll('.form-step').forEach((step, i) => {
            step.classList.toggle('active', i + 1 <= currentStep);
        });
        document.querySelectorAll('.form-panel').forEach((panel, i) => {
            panel.classList.toggle('active', i + 1 === currentStep);
        });
        document.getElementById('prevBtn').style.display = currentStep > 1 ? 'flex' : 'none';
        document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'flex' : 'none';
        document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'flex' : 'none';
        
        // Update confirmation on step 3
        if (currentStep === 3) {
            updateConfirmationSummary();
        }
    }
    
    // Update Confirmation Summary
    function updateConfirmationSummary() {
        // Type
        const activeType = document.querySelector('.type-btn.active input');
        document.getElementById('confirmType').textContent = typeLabels[activeType?.value] || 'Hệ thống';
        
        // Title
        const title = document.getElementById('notifTitle').value || '-';
        document.getElementById('confirmTitle').textContent = title;
        
        // Recipients
        const recipientType = document.querySelector('input[name="recipient_type"]:checked')?.value || 'all';
        let recipientText = recipientLabels[recipientType];
        
        if (recipientType === 'tier') {
            const selectedTiers = [];
            document.querySelectorAll('input[name="tiers[]"]:checked').forEach(t => {
                const tierNames = { gold: 'Vàng', silver: 'Bạc', bronze: 'Đồng', member: 'Thành viên' };
                selectedTiers.push(tierNames[t.value]);
            });
            if (selectedTiers.length > 0) {
                recipientText = 'Hạng: ' + selectedTiers.join(', ');
            }
        } else if (recipientType === 'specific' && selectedUsers.length > 0) {
            recipientText = selectedUsers.map(u => u.name).join(', ');
        }
        document.getElementById('confirmRecipients').textContent = recipientText;
        
        // Estimate count (you can enhance this with AJAX)
        document.getElementById('confirmCount').textContent = recipientType === 'specific' ? selectedUsers.length + ' người' : 'Đang tính...';
        
        // Channels
        const channelsContainer = document.getElementById('confirmChannels');
        channelsContainer.innerHTML = '';
        let hasEmail = false;
        document.querySelectorAll('input[name="channels[]"]:checked').forEach(ch => {
            const info = channelLabels[ch.value];
            channelsContainer.innerHTML += `<span class="channel-badge"><i class="bi ${info.icon}"></i> ${info.label}</span>`;
            if (ch.value === 'email') hasEmail = true;
        });
        
        // Email Preview
        const emailSection = document.getElementById('emailPreviewSection');
        if (hasEmail) {
            emailSection.style.display = 'block';
            updateEmailPreview();
        } else {
            emailSection.style.display = 'none';
        }
        
        // System Preview
        const message = document.getElementById('notifMessage').innerHTML || '';
        document.getElementById('systemPreviewTitle').textContent = title;
        document.getElementById('systemPreviewBody').innerHTML = message;
    }
    
    // Update Email Preview with exact content
    function updateEmailPreview() {
        const title = document.getElementById('notifTitle').value || 'Tiêu đề thông báo';
        const message = document.getElementById('notifMessage').innerHTML || 'Nội dung thông báo...';
        const shopName = '<?php echo esc_js($shop_name); ?>';
        
        document.getElementById('emailPreviewContent').innerHTML = `
            <div style="max-width:100%;font-family:Arial,sans-serif;">
                <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:28px;text-align:center;">
                    <h1 style="color:#fff;margin:0;font-size:22px;">${shopName}</h1>
                </div>
                <div style="background:#fff;padding:28px;border:1px solid #eee;border-top:none;">
                    <h2 style="color:#333;margin:0 0 18px;font-size:18px;">${title}</h2>
                    <div style="color:#555;line-height:1.8;font-size:14px;">${message}</div>
                    <div style="text-align:center;margin-top:28px;">
                        <a href="#" style="display:inline-block;background:#EC802B;color:#fff;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:600;">Xem chi tiết</a>
                    </div>
                </div>
                <div style="background:#f8f9fa;padding:18px;text-align:center;">
                    <p style="margin:0;color:#888;font-size:12px;">© ${new Date().getFullYear()} ${shopName}. All rights reserved.</p>
                </div>
            </div>
        `;
    }
    
    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentStep < totalSteps) {
            if (currentStep === 1) {
                const title = document.getElementById('notifTitle').value;
                const message = document.getElementById('notifMessage').innerHTML.trim();
                if (!title || !message) {
                    alert('Vui lòng nhập tiêu đề và nội dung thông báo!');
                    return;
                }
                // Sync content to hidden field
                document.getElementById('notifMessageHidden').value = message;
            }
            if (currentStep === 2) {
                // Validate channels selected
                const channels = document.querySelectorAll('input[name="channels[]"]:checked');
                if (channels.length === 0) {
                    alert('Vui lòng chọn ít nhất một kênh gửi!');
                    return;
                }
            }
            currentStep++;
            updateStepUI();
        }
    });
    
    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStepUI();
        }
    });
    
    document.querySelectorAll('.form-step').forEach(step => {
        step.addEventListener('click', function() {
            const targetStep = parseInt(this.dataset.step);
            if (targetStep <= currentStep) {
                currentStep = targetStep;
                updateStepUI();
            }
        });
    });
    
    // Edit step buttons in confirmation
    document.querySelectorAll('.edit-step-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentStep = parseInt(this.dataset.goto);
            updateStepUI();
        });
    });
    
    // Auto Load Template when selecting from dropdown
    document.getElementById('templateSelector').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const subject = option.dataset.subject || '';
        const content = option.dataset.content || '';
        const type = option.dataset.type || 'system';
        
        // Load title and content
        if (option.value) {
            if (subject) document.getElementById('notifTitle').value = subject;
            if (content) document.getElementById('notifMessage').innerHTML = content;
        } else {
            // Reset for custom content
            document.getElementById('notifTitle').value = '';
            document.getElementById('notifMessage').innerHTML = '';
        }
        
        // Auto select notification type
        document.querySelectorAll('.type-btn').forEach(btn => {
            const input = btn.querySelector('input');
            if (input.value === type) {
                document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                input.checked = true;
            }
        });
    });
    
    // Toolbar commands
    document.querySelectorAll('.toolbar-btn[data-command]').forEach(btn => {
        btn.addEventListener('click', function() {
            const command = this.dataset.command;
            document.execCommand(command, false, null);
            document.getElementById('notifMessage').focus();
        });
    });
    
    // Font size
    document.querySelector('.font-size-select').addEventListener('change', function() {
        if (this.value) {
            document.execCommand('fontSize', false, this.value);
        }
        this.value = '';
        document.getElementById('notifMessage').focus();
    });
    
    // Font family
    document.querySelector('.font-family-select').addEventListener('change', function() {
        if (this.value) {
            document.execCommand('fontName', false, this.value);
        }
        this.value = '';
        document.getElementById('notifMessage').focus();
    });
    
    // Color pickers
    document.querySelectorAll('.toolbar-color').forEach(picker => {
        picker.addEventListener('input', function() {
            document.execCommand(this.dataset.command, false, this.value);
        });
    });
    
    // Link Modal functions
    function saveSelection() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            savedSelection = sel.getRangeAt(0);
        }
    }
    
    function restoreSelection() {
        if (savedSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedSelection);
        }
    }
    
    document.querySelector('.btn-link').addEventListener('click', function() {
        saveSelection();
        const selectedText = window.getSelection().toString();
        document.getElementById('linkText').value = selectedText;
        document.getElementById('linkUrl').value = '';
        document.getElementById('linkModal').classList.add('show');
    });
    
    function closeLinkModal() {
        document.getElementById('linkModal').classList.remove('show');
    }
    
    function insertLink() {
        const text = document.getElementById('linkText').value;
        const url = document.getElementById('linkUrl').value;
        
        if (!url) {
            alert('Vui lòng nhập URL!');
            return;
        }
        
        closeLinkModal();
        restoreSelection();
        
        const link = `<a href="${url}" target="_blank" style="color:#EC802B;">${text || url}</a>`;
        document.execCommand('insertHTML', false, link);
        document.getElementById('notifMessage').focus();
    }
    
    // Image Modal functions
    document.querySelector('.btn-image').addEventListener('click', function() {
        saveSelection();
        document.getElementById('imageUrl').value = '';
        document.getElementById('imageAlt').value = '';
        document.getElementById('imageWidth').value = '400';
        document.getElementById('imageModal').classList.add('show');
    });
    
    function closeImageModal() {
        document.getElementById('imageModal').classList.remove('show');
    }
    
    function insertImage() {
        const url = document.getElementById('imageUrl').value;
        const alt = document.getElementById('imageAlt').value;
        const width = document.getElementById('imageWidth').value || '400';
        
        if (!url) {
            alert('Vui lòng nhập URL hình ảnh!');
            return;
        }
        
        closeImageModal();
        restoreSelection();
        
        const img = `<img src="${url}" alt="${alt}" style="max-width:${width}px;height:auto;display:block;margin:10px 0;">`;
        document.execCommand('insertHTML', false, img);
        document.getElementById('notifMessage').focus();
    }
    
    // Close modals on backdrop click
    document.getElementById('linkModal').addEventListener('click', function(e) {
        if (e.target === this) closeLinkModal();
    });
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) closeImageModal();
    });
    
    // Type Selection
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Recipient Selection
    document.querySelectorAll('.recipient-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.recipient-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            const value = this.querySelector('input').value;
            document.getElementById('tierSubOptions').classList.toggle('show', value === 'tier');
            document.getElementById('userSubOptions').classList.toggle('show', value === 'specific');
        });
    });
    
    // Tier Chips
    document.querySelectorAll('.tier-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            this.classList.toggle('active');
            this.querySelector('input').checked = this.classList.contains('active');
        });
    });
    
    // Channel Selection
    document.querySelectorAll('.channel-option').forEach(opt => {
        opt.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) {
                e.preventDefault();
                return;
            }
            const checkbox = this.querySelector('input');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('active', checkbox.checked);
            
            if (checkbox.value === 'email') {
                document.getElementById('emailConfigSection').classList.toggle('show', checkbox.checked);
            }
        });
    });
    
    // Variable insertion
    document.querySelectorAll('.variable-item').forEach(item => {
        item.addEventListener('click', function() {
            const variable = this.dataset.variable;
            const editor = document.getElementById('notifMessage');
            editor.focus();
            document.execCommand('insertText', false, variable);
        });
    });
    
    // User Search
    function searchUsers() {
        const query = document.getElementById('userSearchInput').value;
        if (query.length < 2) {
            alert('Nhập ít nhất 2 ký tự để tìm kiếm');
            return;
        }
        
        fetch(ajaxurl + '?action=petshop_search_users_for_notification&query=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(res => {
                const list = document.getElementById('searchResultsList');
                if (res.success && res.data.length > 0) {
                    list.style.display = 'block';
                    list.innerHTML = res.data.map(u => 
                        `<div class="search-result-item" onclick="addUser(${u.id}, '${u.name.replace(/'/g, "\\'")}', '${u.email}')">
                            <i class="bi bi-person-circle"></i>
                            <span><strong>${u.name}</strong> - ${u.email}</span>
                        </div>`
                    ).join('');
                } else {
                    list.style.display = 'block';
                    list.innerHTML = '<div class="search-result-item"><i class="bi bi-x-circle"></i> Không tìm thấy</div>';
                }
            });
    }
    
    function addUser(id, name, email) {
        if (selectedUsers.find(u => u.id === id)) return;
        selectedUsers.push({id, name, email});
        renderSelectedUsers();
        document.getElementById('searchResultsList').style.display = 'none';
        document.getElementById('userSearchInput').value = '';
    }
    
    function removeUser(id) {
        selectedUsers = selectedUsers.filter(u => u.id !== id);
        renderSelectedUsers();
    }
    
    function renderSelectedUsers() {
        const container = document.getElementById('selectedUsersList');
        container.innerHTML = selectedUsers.map(u => 
            `<span class="selected-user-tag">
                <i class="bi bi-person"></i> ${u.name}
                <span class="remove" onclick="removeUser(${u.id})">×</span>
            </span>`
        ).join('');
        document.getElementById('selectedUserIds').value = selectedUsers.map(u => u.id).join(',');
    }
    
    // Preview (for button in navigation)
    function previewNotification() {
        const title = document.getElementById('notifTitle').value || 'Tiêu đề thông báo';
        const message = document.getElementById('notifMessage').innerHTML || 'Nội dung thông báo...';
        const shopName = '<?php echo esc_js($shop_name); ?>';
        
        document.getElementById('previewContent').innerHTML = `
            <div style="max-width:550px;margin:0 auto;font-family:Arial,sans-serif;">
                <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:28px;text-align:center;border-radius:12px 12px 0 0;">
                    <h1 style="color:#fff;margin:0;font-size:22px;">${shopName}</h1>
                </div>
                <div style="background:#fff;padding:28px;border:1px solid #eee;border-top:none;">
                    <h2 style="color:#333;margin:0 0 18px;font-size:18px;">${title}</h2>
                    <div style="color:#555;line-height:1.8;font-size:14px;">${message}</div>
                    <div style="text-align:center;margin-top:28px;">
                        <a href="#" style="display:inline-block;background:#EC802B;color:#fff;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:600;">Xem chi tiết</a>
                    </div>
                </div>
                <div style="background:#f8f9fa;padding:18px;text-align:center;border-radius:0 0 12px 12px;">
                    <p style="margin:0;color:#888;font-size:12px;">© ${new Date().getFullYear()} ${shopName}. All rights reserved.</p>
                </div>
            </div>
        `;
        document.getElementById('previewModal').style.display = 'flex';
    }
    
    document.getElementById('previewModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    
    // Enter key search
    document.getElementById('userSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchUsers();
        }
    });
    
    // Sync content before form submit
    document.getElementById('notificationForm').addEventListener('submit', function() {
        document.getElementById('notifMessageHidden').value = document.getElementById('notifMessage').innerHTML;
    });
    </script>
    <?php
}

// Templates có sẵn
function petshop_get_notification_templates() {
    return array(
        'holiday' => array(
            'name' => 'Nghỉ lễ',
            'desc' => 'Thông báo nghỉ lễ',
            'icon' => '🎉',
            'type' => 'system',
            'title' => 'Thông báo nghỉ lễ',
            'message' => "Kính gửi Quý khách hàng,\n\nPetShop xin thông báo lịch nghỉ lễ:\n- Thời gian nghỉ: [Ngày bắt đầu] - [Ngày kết thúc]\n- Thời gian làm việc lại: [Ngày]\n\nTrong thời gian nghỉ, Quý khách vẫn có thể đặt hàng trực tuyến. Đơn hàng sẽ được xử lý ngay khi chúng tôi làm việc trở lại.\n\nChúc Quý khách và gia đình có kỳ nghỉ vui vẻ!\n\nTrân trọng,\nPetShop",
        ),
        'promotion' => array(
            'name' => 'Khuyến mãi',
            'desc' => 'Thông báo khuyến mãi',
            'icon' => '🏷️',
            'type' => 'promotion',
            'title' => '🔥 SALE UP TO 50% - Chỉ trong hôm nay!',
            'message' => "Chào {customer_name}!\n\n🎁 ƯU ĐÃI ĐẶC BIỆT dành riêng cho bạn:\n\n✅ Giảm đến 50% tất cả sản phẩm\n✅ Freeship đơn từ 300K\n✅ Tặng voucher 50K cho đơn tiếp theo\n\n⏰ Thời gian: Chỉ trong hôm nay!\n\nNhanh tay mua sắm ngay kẻo lỡ!",
        ),
        'flash_sale' => array(
            'name' => 'Flash Sale',
            'desc' => 'Khuyến mãi chớp nhoáng',
            'icon' => '⚡',
            'type' => 'flash_sale',
            'title' => '⚡ FLASH SALE - 2 GIỜ DUY NHẤT!',
            'message' => "🔥 FLASH SALE BẮT ĐẦU!\n\n⏰ Thời gian: [Giờ bắt đầu] - [Giờ kết thúc]\n\n🎯 Ưu đãi HOT:\n• Giảm 30-70% hàng trăm sản phẩm\n• Số lượng có hạn\n\nĐừng bỏ lỡ cơ hội này!",
        ),
        'new_product' => array(
            'name' => 'Sản phẩm mới',
            'desc' => 'Giới thiệu sản phẩm mới',
            'icon' => '🆕',
            'type' => 'system',
            'title' => '🆕 Sản phẩm mới đã có mặt!',
            'message' => "Chào {customer_name}!\n\nChúng tôi vừa cập nhật BST mới với nhiều sản phẩm chất lượng:\n\n📦 [Tên sản phẩm 1]\n📦 [Tên sản phẩm 2]\n📦 [Tên sản phẩm 3]\n\n👉 Ghé shop ngay để khám phá nhé!",
        ),
        'points_reminder' => array(
            'name' => 'Nhắc điểm',
            'desc' => 'Nhắc sử dụng điểm',
            'icon' => '🪙',
            'type' => 'points',
            'title' => '🪙 Bạn có điểm thưởng chưa sử dụng!',
            'message' => "Chào {customer_name}!\n\nBạn đang có điểm thưởng trong tài khoản. Hãy sử dụng để được giảm giá cho đơn hàng tiếp theo nhé!\n\n💡 Mẹo: 100 điểm = 1.000đ giảm giá\n\nĐừng để điểm hết hạn!",
        ),
        'thank_you' => array(
            'name' => 'Cảm ơn',
            'desc' => 'Cảm ơn khách hàng',
            'icon' => '💝',
            'type' => 'system',
            'title' => '💝 Cảm ơn bạn đã đồng hành!',
            'message' => "Kính gửi {customer_name}!\n\nCảm ơn bạn đã tin tưởng và đồng hành cùng PetShop thời gian qua.\n\nChúng tôi luôn nỗ lực mang đến sản phẩm chất lượng và dịch vụ tốt nhất cho bạn và thú cưng.\n\n🎁 Để tri ân, đây là voucher giảm giá đặc biệt dành cho bạn!\n\nTrân trọng,\nPetShop",
        ),
    );
}

// =============================================
// XỬ LÝ GỬI THÔNG BÁO - NÂNG CẤP
// =============================================
function petshop_process_send_notification($data) {
    global $wpdb;
    
    $type = sanitize_text_field($data['notification_type']);
    $title = sanitize_text_field($data['notification_title']);
    $message = wp_kses_post($data['notification_message']);
    $link = esc_url_raw($data['notification_link'] ?? '');
    $recipient_type = sanitize_text_field($data['recipient_type']);
    $channels = isset($data['channels']) ? array_map('sanitize_text_field', $data['channels']) : array('system');
    
    // Email options
    $email_subject = sanitize_text_field($data['email_subject'] ?? '') ?: $title;
    $email_reply_to = sanitize_email($data['email_reply_to'] ?? '');
    $email_cc = sanitize_text_field($data['email_cc'] ?? '');
    $email_bcc = sanitize_text_field($data['email_bcc'] ?? '');
    $email_from_name = sanitize_text_field($data['email_from_name'] ?? '');
    $email_from_email = sanitize_email($data['email_from_email'] ?? '');
    
    $types = function_exists('petshop_get_notification_types') ? petshop_get_notification_types() : array();
    $type_info = $types[$type] ?? array('icon' => 'bi-bell', 'color' => '#EC802B');
    
    // Lấy danh sách user
    $user_ids = array();
    
    switch ($recipient_type) {
        case 'all':
            $users = get_users(array('fields' => 'ID'));
            $user_ids = $users;
            break;
            
        case 'customers':
            $users = get_users(array('role' => 'customer', 'fields' => 'ID'));
            $user_ids = $users;
            // Nếu không có role customer, lấy subscriber
            if (empty($user_ids)) {
                $users = get_users(array('role' => 'subscriber', 'fields' => 'ID'));
                $user_ids = $users;
            }
            break;
            
        case 'tier':
            $tiers = isset($data['tiers']) ? array_map('sanitize_text_field', $data['tiers']) : array();
            foreach ($tiers as $tier) {
                if (function_exists('petshop_get_users_by_tier')) {
                    $tier_users = petshop_get_users_by_tier($tier);
                    $user_ids = array_merge($user_ids, $tier_users);
                }
            }
            $user_ids = array_unique($user_ids);
            break;
            
        case 'specific':
            $ids = sanitize_text_field($data['selected_user_ids'] ?? '');
            $user_ids = array_filter(array_map('intval', explode(',', $ids)));
            break;
            
        case 'staff':
            $users = get_users(array('role__in' => array('administrator', 'editor', 'petshop_manager', 'petshop_staff'), 'fields' => 'ID'));
            $user_ids = $users;
            break;
    }
    
    if (empty($user_ids)) {
        return array('success' => false, 'message' => 'Không tìm thấy người nhận nào.');
    }
    
    $sent_count = 0;
    $total_recipients = count($user_ids);
    
    // Gửi thông báo hệ thống ngay lập tức
    foreach ($user_ids as $user_id) {
        $user = get_userdata($user_id);
        $customer_name = $user ? $user->display_name : 'Khách hàng';
        
        // Replace variables in message
        $personalized_message = petshop_replace_notification_variables($message, $user_id);
        $personalized_title = petshop_replace_notification_variables($title, $user_id);
        
        // Gửi thông báo hệ thống
        if (in_array('system', $channels)) {
            petshop_create_notification($user_id, $type, $personalized_title, $personalized_message, array(
                'link' => $link,
                'icon' => $type_info['icon'],
                'color' => $type_info['color'],
                'send_email' => false, // Không gửi email từ đây
            ));
            $sent_count++;
        }
    }
    
    // Nếu có gửi email, sử dụng queue để tránh timeout
    $email_queued = 0;
    if (in_array('email', $channels)) {
        $queue_data = array(
            'user_ids' => $user_ids,
            'title' => $title,
            'message' => $message,
            'email_subject' => $email_subject,
            'link' => $link,
            'type_info' => $type_info,
            'email_reply_to' => $email_reply_to,
            'email_cc' => $email_cc,
            'email_bcc' => $email_bcc,
            'email_from_name' => $email_from_name,
            'email_from_email' => $email_from_email,
            'batch_size' => 10, // Gửi 10 email mỗi batch
            'current_index' => 0,
            'sent' => 0,
            'failed' => 0,
        );
        
        // Lưu queue vào database
        $queue_id = petshop_create_email_queue($queue_data);
        
        if ($queue_id) {
            // Đăng ký cron job để gửi email theo batch
            if (!wp_next_scheduled('petshop_process_email_queue')) {
                wp_schedule_single_event(time() + 5, 'petshop_process_email_queue');
            }
            $email_queued = count($user_ids);
        }
    }
    
    // Lưu log
    $wpdb->insert(
        $wpdb->prefix . 'petshop_notification_logs',
        array(
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'recipient_type' => $recipient_type,
            'recipient_count' => $total_recipients,
            'channels' => implode(',', $channels),
            'sent_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        )
    );
    
    $msg = "Đã gửi thông báo hệ thống đến {$sent_count} người.";
    if ($email_queued > 0) {
        $msg .= " Email đang được gửi nền đến {$email_queued} người (tránh timeout).";
    }
    
    return array('success' => true, 'message' => $msg);
}

// =============================================
// HỆ THỐNG QUEUE GỬI EMAIL (TRÁNH TIMEOUT)
// =============================================
function petshop_create_email_queue($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'petshop_email_queue';
    
    // Tạo table nếu chưa có
    petshop_create_email_queue_table();
    
    $wpdb->insert($table, array(
        'queue_data' => maybe_serialize($data),
        'status' => 'pending',
        'created_at' => current_time('mysql'),
    ));
    
    return $wpdb->insert_id;
}

function petshop_create_email_queue_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_email_queue';
    $charset = $wpdb->get_charset_collate();
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue_data LONGTEXT,
            status VARCHAR(20) DEFAULT 'pending',
            processed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

// Xử lý email queue theo batch
add_action('petshop_process_email_queue', 'petshop_process_email_queue_batch');
function petshop_process_email_queue_batch() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_email_queue';
    
    // Lấy queue pending
    $queue = $wpdb->get_row("SELECT * FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
    
    if (!$queue) return;
    
    $data = maybe_unserialize($queue->queue_data);
    if (!$data || empty($data['user_ids'])) {
        $wpdb->update($table, array('status' => 'completed'), array('id' => $queue->id));
        return;
    }
    
    $batch_size = $data['batch_size'] ?? 10;
    $current_index = $data['current_index'] ?? 0;
    $user_ids = $data['user_ids'];
    $total = count($user_ids);
    
    // Lấy batch users
    $batch_users = array_slice($user_ids, $current_index, $batch_size);
    
    if (empty($batch_users)) {
        // Hoàn thành
        $wpdb->update($table, array(
            'status' => 'completed',
            'processed_at' => current_time('mysql'),
        ), array('id' => $queue->id));
        return;
    }
    
    // Build headers một lần
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    if (!empty($data['email_from_name']) && !empty($data['email_from_email'])) {
        $headers[] = 'From: ' . $data['email_from_name'] . ' <' . $data['email_from_email'] . '>';
    }
    
    if (!empty($data['email_reply_to'])) {
        $headers[] = 'Reply-To: ' . $data['email_reply_to'];
    }
    
    if (!empty($data['email_cc'])) {
        $cc_emails = array_map('trim', explode(',', $data['email_cc']));
        foreach ($cc_emails as $cc) {
            if (is_email($cc)) {
                $headers[] = 'Cc: ' . $cc;
            }
        }
    }
    
    if (!empty($data['email_bcc'])) {
        $bcc_emails = array_map('trim', explode(',', $data['email_bcc']));
        foreach ($bcc_emails as $bcc) {
            if (is_email($bcc)) {
                $headers[] = 'Bcc: ' . $bcc;
            }
        }
    }
    
    $sent = $data['sent'] ?? 0;
    $failed = $data['failed'] ?? 0;
    
    foreach ($batch_users as $user_id) {
        $user = get_userdata($user_id);
        if (!$user || !$user->user_email) {
            $failed++;
            continue;
        }
        
        $personalized_title = petshop_replace_notification_variables($data['title'], $user_id);
        $personalized_message = petshop_replace_notification_variables($data['message'], $user_id);
        $personalized_subject = petshop_replace_notification_variables($data['email_subject'], $user_id);
        
        $email_content = petshop_get_notification_email_template(
            $personalized_title,
            $personalized_message,
            $data['link'],
            $data['type_info'],
            $user->display_name
        );
        
        if (wp_mail($user->user_email, $personalized_subject, $email_content, $headers)) {
            $sent++;
        } else {
            $failed++;
        }
        
        // Nghỉ 0.5 giây giữa mỗi email để tránh quá tải
        usleep(500000);
    }
    
    // Cập nhật progress
    $data['current_index'] = $current_index + $batch_size;
    $data['sent'] = $sent;
    $data['failed'] = $failed;
    
    if ($data['current_index'] >= $total) {
        // Hoàn thành
        $wpdb->update($table, array(
            'queue_data' => maybe_serialize($data),
            'status' => 'completed',
            'processed_at' => current_time('mysql'),
        ), array('id' => $queue->id));
        
        // Log kết quả
        error_log("PetShop Email Queue #{$queue->id}: Completed - Sent: $sent, Failed: $failed");
    } else {
        // Còn tiếp, cập nhật và schedule tiếp
        $wpdb->update($table, array(
            'queue_data' => maybe_serialize($data),
            'status' => 'processing',
        ), array('id' => $queue->id));
        
        // Schedule batch tiếp theo sau 10 giây
        wp_schedule_single_event(time() + 10, 'petshop_process_email_queue');
    }
}

// Replace variables trong thông báo
function petshop_replace_notification_variables($content, $user_id = 0) {
    $user = $user_id ? get_userdata($user_id) : null;
    $shop_settings = get_option('petshop_shop_settings', array());
    
    // Thời gian hiện tại
    $current_hour = (int)date('G');
    if ($current_hour >= 5 && $current_hour < 12) {
        $day_period = 'sáng';
    } elseif ($current_hour >= 12 && $current_hour < 18) {
        $day_period = 'chiều';
    } else {
        $day_period = 'tối';
    }
    
    // Tên ngày trong tuần
    $day_names = array('Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy');
    $day_of_week = $day_names[(int)date('w')];
    
    // Tên tháng
    $month_names = array('', 'Tháng Một', 'Tháng Hai', 'Tháng Ba', 'Tháng Tư', 'Tháng Năm', 'Tháng Sáu', 'Tháng Bảy', 'Tháng Tám', 'Tháng Chín', 'Tháng Mười', 'Tháng Mười Một', 'Tháng Mười Hai');
    $month_name = $month_names[(int)date('n')];
    
    // Xác định danh xưng
    $gender = $user ? get_user_meta($user_id, 'gender', true) : '';
    $title = ($gender === 'female') ? 'Chị' : (($gender === 'male') ? 'Anh' : 'Anh/Chị');
    $dear = ($gender === 'female') ? 'Chị' : (($gender === 'male') ? 'Anh' : 'Quý khách');
    $pronoun = ($gender === 'female') ? 'chị' : (($gender === 'male') ? 'anh' : 'bạn');
    
    $replacements = array(
        // Khách hàng
        '{customer_name}' => $user ? $user->display_name : 'Quý khách',
        '{customer_email}' => $user ? $user->user_email : '',
        '{customer_phone}' => $user ? get_user_meta($user_id, 'phone', true) : '',
        '{customer_address}' => $user ? get_user_meta($user_id, 'billing_address', true) : '',
        '{customer_username}' => $user ? $user->user_login : '',
        '{name}' => $user ? $user->display_name : 'Quý khách',
        
        // Xưng hô
        '{title}' => $title,
        '{dear}' => $dear,
        '{greeting}' => 'Xin chào',
        '{pronoun}' => $pronoun,
        
        // Thời gian
        '{current_date}' => date_i18n('d/m/Y'),
        '{current_day}' => date('d'),
        '{current_month}' => date('m'),
        '{current_year}' => date('Y'),
        '{current_time}' => date_i18n('H:i'),
        '{current_hour}' => date('H'),
        '{current_minute}' => date('i'),
        '{day_period}' => $day_period,
        '{day_of_week}' => $day_of_week,
        '{month_name}' => $month_name,
        '{year}' => date('Y'),
        
        // Shop
        '{shop_name}' => $shop_settings['shop_name'] ?? get_bloginfo('name'),
        '{shop_phone}' => $shop_settings['phone'] ?? '',
        '{shop_email}' => $shop_settings['email'] ?? get_option('admin_email'),
        '{shop_address}' => $shop_settings['address'] ?? '',
        '{shop_website}' => home_url(),
        '{shop_hotline}' => $shop_settings['hotline'] ?? $shop_settings['phone'] ?? '',
        
        // Liên kết
        '{site_url}' => home_url(),
        '{login_url}' => wp_login_url(),
        '{account_url}' => home_url('/tai-khoan/'),
        '{order_url}' => home_url('/xem-don-hang/'),
        '{unsubscribe_url}' => home_url('/huy-dang-ky/'),
    );
    
    // Điểm & Hạng
    if ($user) {
        $points = get_user_meta($user_id, 'petshop_points', true);
        $tier = get_user_meta($user_id, 'petshop_tier', true);
        $replacements['{total_points}'] = number_format($points ?: 0);
        $replacements['{points_earned}'] = number_format($points ?: 0);
        $replacements['{current_tier}'] = $tier ?: 'Thành viên';
        $replacements['{new_tier}'] = $tier ?: 'Thành viên';
    }
    
    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

// Template email cho thông báo
function petshop_get_notification_email_template($title, $message, $link, $type_info, $customer_name = '') {
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
    
    $html = '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
        <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:30px;text-align:center;border-radius:10px 10px 0 0;">
            <h1 style="color:#fff;margin:0;font-size:24px;">' . esc_html($shop_name) . '</h1>
        </div>
        <div style="background:#fff;padding:30px;border:1px solid #eee;border-top:none;">';
    
    // Greeting with customer name
    if (!empty($customer_name)) {
        $html .= '<p style="color:#333;font-size:16px;margin:0 0 20px;">Xin chào <strong>' . esc_html($customer_name) . '</strong>,</p>';
    }
    
    $html .= '<div style="text-align:center;margin-bottom:20px;">
                <span style="display:inline-block;width:60px;height:60px;background:' . $type_info['color'] . '20;border-radius:50%;line-height:60px;font-size:28px;">';
    
    // Fallback icon (emoji)
    $icon_map = array(
        'bi-bag-check' => '🛍️',
        'bi-tag' => '🏷️',
        'bi-ticket-perforated' => '🎫',
        'bi-coin' => '🪙',
        'bi-trophy' => '🏆',
        'bi-info-circle' => 'ℹ️',
        'bi-lightning' => '⚡',
        'bi-clock' => '⏰',
        'bi-bell' => '🔔',
    );
    $html .= $icon_map[$type_info['icon']] ?? '📢';
    
    $html .= '</span>
            </div>
            <h2 style="color:#333;text-align:center;margin:0 0 20px;">' . esc_html($title) . '</h2>
            <div style="color:#555;line-height:1.8;white-space:pre-wrap;">' . nl2br(esc_html($message)) . '</div>';
    
    if (!empty($link)) {
        $html .= '
            <div style="text-align:center;margin-top:30px;">
                <a href="' . esc_url($link) . '" style="display:inline-block;background:#EC802B;color:#fff;padding:15px 35px;border-radius:30px;text-decoration:none;font-weight:600;">Xem chi tiết</a>
            </div>';
    }
    
    $html .= '
        </div>
        <div style="background:#f8f9fa;padding:20px;text-align:center;border-radius:0 0 10px 10px;">
            <p style="margin:0;color:#888;font-size:13px;">© ' . date('Y') . ' ' . esc_html($shop_name) . '. All rights reserved.</p>
        </div>
    </div>';
    
    return $html;
}

// AJAX search users
function petshop_ajax_search_users_for_notification() {
    $query = sanitize_text_field($_GET['query'] ?? '');
    
    $users = get_users(array(
        'search' => '*' . $query . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => 20,
    ));
    
    $results = array();
    foreach ($users as $user) {
        $results[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
        );
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_petshop_search_users_for_notification', 'petshop_ajax_search_users_for_notification');

// =============================================
// TRANG LỊCH SỬ GỬI THÔNG BÁO
// =============================================
function petshop_notification_history_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notification_logs';
    
    $paged = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $total_pages = ceil($total / $per_page);
    
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
    
    $types = function_exists('petshop_get_notification_types') ? petshop_get_notification_types() : array();
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-backup"></span> Lịch sử gửi thông báo</h1>
        
        <style>
            .history-table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-top:20px; }
            .history-table th, .history-table td { padding:15px; text-align:left; border-bottom:1px solid #f0f0f0; }
            .history-table th { background:#f8f9fa; font-weight:600; }
            .history-table tr:hover { background:#fafafa; }
            .badge { display:inline-block; padding:4px 12px; border-radius:15px; font-size:12px; }
        </style>
        
        <table class="history-table">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Tiêu đề</th>
                    <th>Loại</th>
                    <th>Đối tượng</th>
                    <th>Số người nhận</th>
                    <th>Kênh gửi</th>
                    <th>Người gửi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:50px;color:#888;">Chưa có lịch sử gửi thông báo</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $type_info = $types[$log->type] ?? array('label' => 'Thông báo', 'color' => '#6c757d');
                        $sender = get_userdata($log->sent_by);
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?></td>
                        <td><strong><?php echo esc_html($log->title); ?></strong></td>
                        <td>
                            <span class="badge" style="background:<?php echo $type_info['color']; ?>20;color:<?php echo $type_info['color']; ?>;">
                                <?php echo $type_info['label']; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $recipient_labels = array(
                                'all' => 'Tất cả',
                                'customers' => 'Khách hàng',
                                'tier' => 'Theo hạng',
                                'specific' => 'Chọn cụ thể',
                                'staff' => 'Nhân viên',
                            );
                            echo $recipient_labels[$log->recipient_type] ?? $log->recipient_type;
                            ?>
                        </td>
                        <td><?php echo number_format($log->recipient_count); ?></td>
                        <td>
                            <?php 
                            $channels = explode(',', $log->channels);
                            foreach ($channels as $ch) {
                                if ($ch === 'system') echo '<span class="dashicons dashicons-bell" title="Hệ thống"></span> ';
                                if ($ch === 'email') echo '<span class="dashicons dashicons-email" title="Email"></span> ';
                                if ($ch === 'sms') echo '<span class="dashicons dashicons-smartphone" title="SMS"></span> ';
                            }
                            ?>
                        </td>
                        <td><?php echo $sender ? esc_html($sender->display_name) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;gap:5px;margin-top:20px;justify-content:center;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $paged): ?>
                    <span style="padding:8px 14px;background:#EC802B;color:#fff;border-radius:5px;"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>" style="padding:8px 14px;border:1px solid #ddd;border-radius:5px;text-decoration:none;color:#666;"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// TRANG CÀI ĐẶT THÔNG BÁO TỰ ĐỘNG
// =============================================
function petshop_auto_notification_settings_page() {
    // Lưu settings
    if (isset($_POST['save_auto_notif_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'auto_notif_settings')) {
        $settings = array(
            // User actions
            'on_register' => isset($_POST['on_register']),
            'on_login' => isset($_POST['on_login']),
            'on_password_change' => isset($_POST['on_password_change']),
            'on_profile_update' => isset($_POST['on_profile_update']),
            
            // Order actions
            'on_order_placed' => isset($_POST['on_order_placed']),
            'on_order_confirmed' => isset($_POST['on_order_confirmed']),
            'on_order_shipping' => isset($_POST['on_order_shipping']),
            'on_order_completed' => isset($_POST['on_order_completed']),
            'on_order_cancelled' => isset($_POST['on_order_cancelled']),
            
            // Points & Tier
            'on_points_earned' => isset($_POST['on_points_earned']),
            'on_tier_upgrade' => isset($_POST['on_tier_upgrade']),
            'on_voucher_received' => isset($_POST['on_voucher_received']),
            
            // Reminders
            'cart_reminder' => isset($_POST['cart_reminder']),
            'cart_reminder_hours' => intval($_POST['cart_reminder_hours'] ?? 24),
            'birthday_wish' => isset($_POST['birthday_wish']),
            
            // Admin notifications
            'admin_new_order' => isset($_POST['admin_new_order']),
            'admin_new_customer' => isset($_POST['admin_new_customer']),
            'admin_new_contact' => isset($_POST['admin_new_contact']),
            'admin_low_stock' => isset($_POST['admin_low_stock']),
        );
        
        update_option('petshop_auto_notification_settings', $settings);
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt!</p></div>';
    }
    
    $settings = get_option('petshop_auto_notification_settings', array());
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-update"></span> Cài đặt thông báo tự động</h1>
        
        <style>
            .auto-notif-settings { max-width: 900px; margin-top: 25px; }
            .settings-section {
                background: #fff;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .settings-section h3 {
                margin: 0 0 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .settings-section h3 .dashicons { color: #EC802B; }
            .setting-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 0;
                border-bottom: 1px solid #f5f5f5;
            }
            .setting-row:last-child { border-bottom: none; }
            .setting-info { flex: 1; }
            .setting-info .title { font-weight: 500; color: #333; }
            .setting-info .desc { font-size: 13px; color: #888; margin-top: 3px; }
            .toggle-switch {
                position: relative;
                width: 50px;
                height: 26px;
            }
            .toggle-switch input { opacity: 0; width: 0; height: 0; }
            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0; left: 0; right: 0; bottom: 0;
                background: #ccc;
                border-radius: 26px;
                transition: 0.3s;
            }
            .toggle-slider:before {
                content: "";
                position: absolute;
                height: 20px; width: 20px;
                left: 3px; bottom: 3px;
                background: #fff;
                border-radius: 50%;
                transition: 0.3s;
            }
            .toggle-switch input:checked + .toggle-slider { background: #EC802B; }
            .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
            
            .inline-input {
                display: inline-block;
                width: 60px;
                padding: 5px 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
                text-align: center;
            }
        </style>
        
        <form method="post" class="auto-notif-settings">
            <?php wp_nonce_field('auto_notif_settings'); ?>
            
            <!-- User Actions -->
            <div class="settings-section">
                <h3><span class="dashicons dashicons-admin-users"></span> Hành động người dùng</h3>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đăng ký tài khoản</div>
                        <div class="desc">Gửi email chào mừng khi người dùng đăng ký mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_register" <?php checked(!empty($settings['on_register'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đăng nhập</div>
                        <div class="desc">Thông báo khi có đăng nhập mới vào tài khoản</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_login" <?php checked(!empty($settings['on_login'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đổi mật khẩu</div>
                        <div class="desc">Thông báo xác nhận khi đổi mật khẩu thành công</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_password_change" <?php checked(!empty($settings['on_password_change'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Cập nhật hồ sơ</div>
                        <div class="desc">Thông báo khi thông tin hồ sơ được cập nhật</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_profile_update" <?php checked(!empty($settings['on_profile_update'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Order Actions -->
            <div class="settings-section">
                <h3><span class="dashicons dashicons-cart"></span> Đơn hàng</h3>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đặt hàng thành công</div>
                        <div class="desc">Email xác nhận đơn hàng đã được đặt</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_order_placed" <?php checked(!empty($settings['on_order_placed'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Xác nhận đơn hàng</div>
                        <div class="desc">Thông báo khi shop xác nhận đơn hàng</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_order_confirmed" <?php checked(!empty($settings['on_order_confirmed'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đang giao hàng</div>
                        <div class="desc">Thông báo khi đơn hàng được giao cho shipper</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_order_shipping" <?php checked(!empty($settings['on_order_shipping'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Hoàn thành đơn hàng</div>
                        <div class="desc">Thông báo và cảm ơn khi đơn hàng hoàn tất</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_order_completed" <?php checked(!empty($settings['on_order_completed'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Hủy đơn hàng</div>
                        <div class="desc">Thông báo khi đơn hàng bị hủy</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_order_cancelled" <?php checked(!empty($settings['on_order_cancelled'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Points & Tier -->
            <div class="settings-section">
                <h3><span class="dashicons dashicons-awards"></span> Điểm thưởng & Hạng thành viên</h3>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Nhận điểm thưởng</div>
                        <div class="desc">Thông báo khi khách hàng được cộng điểm</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_points_earned" <?php checked(!empty($settings['on_points_earned'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Nâng hạng thành viên</div>
                        <div class="desc">Thông báo chúc mừng khi lên hạng mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_tier_upgrade" <?php checked(!empty($settings['on_tier_upgrade'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Nhận voucher</div>
                        <div class="desc">Thông báo khi được tặng voucher mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="on_voucher_received" <?php checked(!empty($settings['on_voucher_received'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Reminders -->
            <div class="settings-section">
                <h3><span class="dashicons dashicons-clock"></span> Nhắc nhở</h3>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Nhắc giỏ hàng bỏ quên</div>
                        <div class="desc">Gửi email nhắc sau <input type="number" name="cart_reminder_hours" value="<?php echo intval($settings['cart_reminder_hours'] ?? 24); ?>" class="inline-input" min="1"> giờ</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="cart_reminder" <?php checked(!empty($settings['cart_reminder'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Chúc mừng sinh nhật</div>
                        <div class="desc">Gửi email và voucher vào ngày sinh nhật</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="birthday_wish" <?php checked(!empty($settings['birthday_wish'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Admin Notifications -->
            <div class="settings-section">
                <h3><span class="dashicons dashicons-admin-generic"></span> Thông báo cho Admin</h3>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Đơn hàng mới</div>
                        <div class="desc">Thông báo khi có đơn hàng mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="admin_new_order" <?php checked(!empty($settings['admin_new_order'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Khách hàng mới</div>
                        <div class="desc">Thông báo khi có khách đăng ký mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="admin_new_customer" <?php checked(!empty($settings['admin_new_customer'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Liên hệ mới</div>
                        <div class="desc">Thông báo khi có form liên hệ mới</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="admin_new_contact" <?php checked(!empty($settings['admin_new_contact'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="title">Sắp hết hàng</div>
                        <div class="desc">Cảnh báo khi sản phẩm sắp hết tồn kho</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="admin_low_stock" <?php checked(!empty($settings['admin_low_stock'])); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <p>
                <button type="submit" name="save_auto_notif_settings" class="button button-primary button-large" style="background:#EC802B;border-color:#EC802B;padding:10px 30px;">
                    <span class="dashicons dashicons-yes" style="margin-top:4px;"></span> Lưu cài đặt
                </button>
            </p>
        </form>
    </div>
    <?php
}

// =============================================
// TRANG MẪU THÔNG BÁO
// =============================================
function petshop_email_templates_page() {
    // Lưu templates
    if (isset($_POST['save_email_templates']) && wp_verify_nonce($_POST['_wpnonce'], 'email_templates')) {
        $templates = array();
        foreach ($_POST['templates'] as $key => $data) {
            $templates[$key] = array(
                'subject' => sanitize_text_field($data['subject'] ?? ''),
                'content' => wp_kses_post($data['content'] ?? ''),
            );
        }
        update_option('petshop_email_templates', $templates);
        echo '<div class="notice notice-success"><p>Đã lưu mẫu thông báo!</p></div>';
    }
    
    $templates = get_option('petshop_email_templates', array());
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
    
    $default_templates = array(
        'welcome' => array(
            'name' => 'Chào mừng thành viên mới',
            'icon' => '<i class="bi bi-hand-wave"></i>',
            'default_subject' => 'Chào mừng bạn đến với ' . $shop_name,
            'default_content' => 'Chào mừng <strong>{customer_name}</strong> đã đến với {shop_name}!<br><br>Chúng tôi rất vui được phục vụ bạn. Hãy khám phá các sản phẩm và dịch vụ tuyệt vời của chúng tôi.',
        ),
        'order_placed' => array(
            'name' => 'Xác nhận đặt hàng',
            'icon' => '<i class="bi bi-cart-check"></i>',
            'default_subject' => 'Đơn hàng #{order_code} đã được tiếp nhận',
            'default_content' => 'Cảm ơn <strong>{customer_name}</strong>!<br><br>Đơn hàng <strong>#{order_code}</strong> của bạn đã được tiếp nhận.<br>Tổng giá trị: <strong>{order_total}</strong>',
        ),
        'order_confirmed' => array(
            'name' => 'Xác nhận đơn hàng',
            'icon' => '<i class="bi bi-check-circle"></i>',
            'default_subject' => 'Đơn hàng #{order_code} đã được xác nhận',
            'default_content' => 'Đơn hàng <strong>#{order_code}</strong> của bạn đã được xác nhận và đang được chuẩn bị.',
        ),
        'order_shipping' => array(
            'name' => 'Đang giao hàng',
            'icon' => '<i class="bi bi-truck"></i>',
            'default_subject' => 'Đơn hàng #{order_code} đang được giao',
            'default_content' => 'Đơn hàng <strong>#{order_code}</strong> đang được giao đến bạn!<br><br>Mã vận đơn: <strong>{tracking_code}</strong>',
        ),
        'order_completed' => array(
            'name' => 'Hoàn thành đơn hàng',
            'icon' => '<i class="bi bi-gift"></i>',
            'default_subject' => 'Đơn hàng #{order_code} đã hoàn thành',
            'default_content' => 'Đơn hàng <strong>#{order_code}</strong> đã hoàn thành.<br><br>Bạn nhận được <strong>{points_earned}</strong> điểm thưởng. Cảm ơn bạn đã tin tưởng!',
        ),
        'points_earned' => array(
            'name' => 'Nhận điểm thưởng',
            'icon' => '<i class="bi bi-coin"></i>',
            'default_subject' => 'Bạn vừa nhận được {points} điểm',
            'default_content' => 'Chúc mừng <strong>{customer_name}</strong>!<br><br>Bạn vừa nhận được <strong>{points}</strong> điểm.<br>Tổng điểm hiện tại: <strong>{total_points}</strong>.',
        ),
        'tier_upgrade' => array(
            'name' => 'Nâng hạng thành viên',
            'icon' => '<i class="bi bi-trophy"></i>',
            'default_subject' => 'Chúc mừng! Bạn đã lên hạng {new_tier}',
            'default_content' => 'Chúc mừng <strong>{customer_name}</strong>!<br><br>Bạn đã được nâng hạng lên <strong>{new_tier}</strong>!<br><br>Quyền lợi mới: {benefits}',
        ),
        'birthday' => array(
            'name' => 'Chúc mừng sinh nhật',
            'icon' => '<i class="bi bi-cake2"></i>',
            'default_subject' => 'Chúc mừng sinh nhật {customer_name}!',
            'default_content' => 'Chúc mừng sinh nhật <strong>{customer_name}</strong>!<br><br>Đây là voucher đặc biệt dành riêng cho bạn: <strong>{voucher_code}</strong>',
        ),
        'promotion' => array(
            'name' => 'Thông báo khuyến mãi',
            'icon' => '<i class="bi bi-tag"></i>',
            'default_subject' => 'Ưu đãi đặc biệt dành cho bạn!',
            'default_content' => 'Xin chào <strong>{customer_name}</strong>,<br><br>Chúng tôi có chương trình khuyến mãi đặc biệt dành cho bạn!',
        ),
        'reply' => array(
            'name' => 'Phản hồi liên hệ',
            'icon' => '<i class="bi bi-chat-dots"></i>',
            'default_subject' => 'Re: Phản hồi từ ' . $shop_name,
            'default_content' => 'Xin chào <strong>{name}</strong>,<br><br>{message}<br><br>Trân trọng,<br>{shop_name}',
        ),
    );
    
    // All available variables - đồng bộ với trang Gửi thông báo
    $all_variables = array(
        'Khách hàng' => array(
            '{customer_name}' => 'Tên khách hàng',
            '{customer_email}' => 'Email khách hàng',
            '{customer_phone}' => 'Số điện thoại',
            '{customer_address}' => 'Địa chỉ',
            '{customer_username}' => 'Tên đăng nhập',
            '{name}' => 'Tên (liên hệ)',
        ),
        'Xưng hô' => array(
            '{title}' => 'Danh xưng (Anh/Chị)',
            '{dear}' => 'Kính gửi (Quý khách/Anh/Chị)',
            '{greeting}' => 'Lời chào (Xin chào/Chào)',
            '{pronoun}' => 'Đại từ (bạn/anh/chị)',
        ),
        'Thời gian' => array(
            '{current_date}' => 'Ngày hiện tại (01/03/2026)',
            '{current_day}' => 'Ngày (01)',
            '{current_month}' => 'Tháng (03)',
            '{current_year}' => 'Năm (2026)',
            '{current_time}' => 'Giờ:Phút (14:30)',
            '{current_hour}' => 'Giờ (14)',
            '{current_minute}' => 'Phút (30)',
            '{day_period}' => 'Buổi (sáng/chiều/tối)',
            '{day_of_week}' => 'Thứ (Thứ Hai)',
            '{month_name}' => 'Tên tháng (Tháng Ba)',
        ),
        'Đơn hàng' => array(
            '{order_code}' => 'Mã đơn hàng',
            '{order_total}' => 'Tổng tiền đơn',
            '{order_items}' => 'Danh sách sản phẩm',
            '{order_date}' => 'Ngày đặt hàng',
            '{order_status}' => 'Trạng thái đơn',
            '{tracking_code}' => 'Mã vận đơn',
            '{shipping_address}' => 'Địa chỉ giao hàng',
            '{shipping_method}' => 'Phương thức giao hàng',
            '{payment_method}' => 'Phương thức thanh toán',
            '{delivery_date}' => 'Ngày giao hàng dự kiến',
        ),
        'Điểm & Hạng' => array(
            '{points}' => 'Điểm nhận được',
            '{total_points}' => 'Tổng điểm hiện tại',
            '{points_earned}' => 'Điểm tích lũy',
            '{new_tier}' => 'Hạng mới',
            '{current_tier}' => 'Hạng hiện tại',
            '{benefits}' => 'Quyền lợi mới',
            '{points_to_next}' => 'Điểm cần để lên hạng',
            '{points_expiry}' => 'Ngày hết hạn điểm',
        ),
        'Voucher' => array(
            '{voucher_code}' => 'Mã voucher',
            '{voucher_value}' => 'Giá trị voucher',
            '{voucher_expiry}' => 'Ngày hết hạn voucher',
            '{voucher_min_order}' => 'Đơn tối thiểu áp dụng',
        ),
        'Shop' => array(
            '{shop_name}' => 'Tên cửa hàng',
            '{shop_phone}' => 'SĐT cửa hàng',
            '{shop_email}' => 'Email cửa hàng',
            '{shop_address}' => 'Địa chỉ cửa hàng',
            '{shop_website}' => 'Website',
            '{shop_hotline}' => 'Hotline hỗ trợ',
        ),
        'Liên kết' => array(
            '{site_url}' => 'URL trang chủ',
            '{login_url}' => 'Link đăng nhập',
            '{account_url}' => 'Link tài khoản',
            '{order_url}' => 'Link xem đơn hàng',
            '{unsubscribe_url}' => 'Link hủy đăng ký',
        ),
        'Khác' => array(
            '{message}' => 'Nội dung tin nhắn',
        ),
    );
    ?>
    <!-- Load Bootstrap Icons inline -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <div class="wrap petshop-email-templates">
        <h1><i class="bi bi-file-earmark-text" style="margin-right:8px;"></i> Mẫu thông báo</h1>
        
        <style>
            .petshop-email-templates { 
                margin-top: 20px; 
            }
            .email-templates-container {
                display: flex;
                gap: 30px;
                margin-top: 25px;
            }
            .templates-main {
                flex: 1;
                min-width: 0;
            }
            .templates-sidebar {
                width: 320px;
                flex-shrink: 0;
            }
            .label-icon.bi,
            i.bi.label-icon {
                font-size: 14px;
                width: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #EC802B;
            }
            
            /* All bi icons default color */
            .petshop-email-templates i.bi {
                color: inherit;
            }
            
            /* Template Tabs */
            .template-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 25px;
                background: #fff;
                padding: 15px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .template-tab {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 15px;
                background: #f0f0f1;
                border: 2px solid transparent;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 13px;
            }
            .template-tab:hover { background: #e8e8e9; }
            .template-tab.active {
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-color: #EC802B;
            }
            .template-tab i.bi { 
                font-size: 16px; 
                color: #EC802B;
            }
            .template-tab.active i.bi {
                color: #fff;
            }
            
            /* Template Editor */
            .template-editor {
                display: none;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .template-editor.active { display: block; }
            
            .editor-header {
                padding: 20px 25px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
            }
            .editor-header h3 {
                margin: 0;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .editor-header h3 i.bi {
                font-size: 22px;
                color: #fff;
            }
            
            .editor-body { padding: 25px; }
            
            /* Form Grid */
            .email-form-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            .email-form-grid.three-cols {
                grid-template-columns: repeat(3, 1fr);
            }
            .form-field { }
            .form-field.full-width { grid-column: 1 / -1; }
            .form-field label {
                display: block;
                font-weight: 600;
                color: #333;
                margin-bottom: 6px;
                font-size: 13px;
            }
            .form-field input,
            .form-field textarea {
                width: 100%;
                padding: 10px 14px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .form-field input:focus,
            .form-field textarea:focus {
                border-color: #EC802B;
                box-shadow: 0 0 0 3px rgba(236,128,43,0.1);
                outline: none;
            }
            
            /* Toolbar */
            .editor-toolbar {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-bottom: none;
                border-radius: 8px 8px 0 0;
                padding: 8px 12px;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                align-items: center;
            }
            .toolbar-group {
                display: flex;
                gap: 2px;
                padding-right: 10px;
                margin-right: 10px;
                border-right: 1px solid #ddd;
            }
            .toolbar-group:last-child {
                border-right: none;
                margin-right: 0;
                padding-right: 0;
            }
            .toolbar-btn {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 14px;
                color: #333;
            }
            .toolbar-btn i.bi {
                font-size: 16px;
                color: #333;
            }
            .toolbar-btn:hover {
                background: #EC802B;
                color: #fff;
                border-color: #EC802B;
            }
            .toolbar-btn:hover i.bi {
                color: #fff;
            }
            .toolbar-btn.active {
                background: #EC802B;
                color: #fff;
                border-color: #EC802B;
            }
            .toolbar-select {
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 13px;
                background: #fff;
                cursor: pointer;
            }
            .toolbar-select:focus {
                outline: none;
                border-color: #EC802B;
            }
            .toolbar-color {
                width: 28px;
                height: 28px;
                padding: 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
            }
            
            /* Content Editor */
            .content-editor {
                min-height: 250px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 0 0 8px 8px;
                font-size: 14px;
                line-height: 1.7;
                background: #fff;
                overflow-y: auto;
            }
            .content-editor:focus {
                outline: none;
                border-color: #EC802B;
            }
            
            /* Attachments */
            .attachments-section {
                margin-top: 20px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            .attachments-section h4 {
                margin: 0 0 15px;
                font-size: 14px;
                color: #333;
            }
            .attachment-upload {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .upload-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: #fff;
                border: 2px dashed #ddd;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .upload-btn:hover {
                border-color: #EC802B;
                color: #EC802B;
            }
            .attachment-list {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 15px;
            }
            .attachment-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
            }
            .attachment-item .remove {
                color: #dc3545;
                cursor: pointer;
            }
            .attachment-item img {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 4px;
            }
            
            /* Sidebar - Variables */
            .variables-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.08);
                position: sticky;
                top: 40px;
            }
            .variables-header {
                padding: 18px 20px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 12px 12px 0 0;
            }
            .variables-header h3 {
                margin: 0;
                font-size: 15px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .variables-header p {
                margin: 5px 0 0;
                font-size: 12px;
                opacity: 0.9;
            }
            
            /* Icon alignment fix */
            .form-field label {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .label-icon {
                font-size: 14px;
                width: 18px;
                text-align: center;
                flex-shrink: 0;
            }
            .variables-body {
                padding: 15px;
                max-height: 600px;
                overflow-y: auto;
            }
            .variable-group {
                margin-bottom: 15px;
            }
            .variable-group:last-child { margin-bottom: 0; }
            .variable-group-title {
                font-size: 12px;
                font-weight: 600;
                color: #888;
                text-transform: uppercase;
                margin-bottom: 8px;
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }
            .variable-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 8px 10px;
                background: #f8f9fa;
                border-radius: 6px;
                margin-bottom: 5px;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 13px;
            }
            .variable-item:hover {
                background: #EC802B;
                color: #fff;
            }
            .variable-item code {
                background: rgba(0,0,0,0.1);
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 11px;
            }
            .variable-item:hover code {
                background: rgba(255,255,255,0.2);
            }
            
            /* Preview Section */
            .preview-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.08);
                margin-top: 20px;
            }
            .preview-header {
                padding: 15px 20px;
                background: #2c3e50;
                color: #fff;
                border-radius: 12px 12px 0 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .preview-body {
                padding: 20px;
                background: #f5f5f5;
                border-radius: 0 0 12px 12px;
            }
            .preview-email {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow: hidden;
                max-width: 600px;
                margin: 0 auto;
            }
            
            /* Save Button */
            .save-templates-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border: none;
                padding: 14px 35px;
                border-radius: 30px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                margin-top: 25px;
            }
            .save-templates-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(236,128,43,0.4);
            }
            
            /* Link/Image Insert Modal */
            .tpl-modal {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 100001;
                align-items: center;
                justify-content: center;
            }
            .tpl-modal.show { display: flex; }
            .tpl-modal-content {
                background: #fff;
                border-radius: 12px;
                width: 90%;
                max-width: 450px;
                box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            }
            .tpl-modal-header {
                padding: 15px 20px;
                background: linear-gradient(135deg, #EC802B, #F5994D);
                color: #fff;
                border-radius: 12px 12px 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .tpl-modal-header h4 { margin: 0; font-size: 15px; display: flex; align-items: center; gap: 8px; }
            .tpl-modal-header h4 i.bi { color: #fff; }
            .tpl-modal-close {
                background: rgba(255,255,255,0.2);
                border: none;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                color: #fff;
                cursor: pointer;
                font-size: 16px;
            }
            .tpl-modal-body { padding: 20px; }
            .tpl-modal-body .form-field { margin-bottom: 15px; }
            .tpl-modal-body .form-field:last-child { margin-bottom: 0; }
            .tpl-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            .tpl-modal-footer button {
                padding: 10px 20px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }
            .tpl-modal-footer .btn-cancel {
                background: #fff;
                border: 1px solid #ddd;
                color: #666;
            }
            .tpl-modal-footer .btn-insert {
                background: linear-gradient(135deg, #EC802B, #F5994D);
                border: none;
                color: #fff;
            }
        </style>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('email_templates'); ?>
            
            <div class="email-templates-container">
                <div class="templates-main">
                    <!-- Template Tabs -->
                    <div class="template-tabs">
                        <?php $first = true; foreach ($default_templates as $key => $tpl): ?>
                        <div class="template-tab <?php echo $first ? 'active' : ''; ?>" data-template="<?php echo $key; ?>">
                            <?php echo $tpl['icon']; ?>
                            <span><?php echo esc_html($tpl['name']); ?></span>
                        </div>
                        <?php $first = false; endforeach; ?>
                    </div>
                    
                    <!-- Template Editors -->
                    <?php $first = true; foreach ($default_templates as $key => $tpl): 
                        $saved = $templates[$key] ?? array();
                    ?>
                    <div class="template-editor <?php echo $first ? 'active' : ''; ?>" data-template="<?php echo $key; ?>">
                        <div class="editor-header">
                            <h3><?php echo $tpl['icon']; ?> <?php echo esc_html($tpl['name']); ?></h3>
                        </div>
                        <div class="editor-body">
                            <!-- Email Settings Grid -->
                            <div class="email-form-grid">
                                <div class="form-field full-width">
                                    <label><i class="bi bi-pencil label-icon"></i> Tiêu đề email (Subject)</label>
                                    <input type="text" name="templates[<?php echo $key; ?>][subject]" 
                                           value="<?php echo esc_attr($saved['subject'] ?? $tpl['default_subject']); ?>"
                                           placeholder="<?php echo esc_attr($tpl['default_subject']); ?>">
                                </div>
                            </div>
                            
                            <!-- Rich Text Toolbar -->
                            <div class="form-field full-width" style="margin-top: 20px;">
                                <label><i class="bi bi-file-text label-icon"></i> Nội dung email</label>
                                
                                <div class="editor-toolbar">
                                    <div class="toolbar-group">
                                        <select class="toolbar-select font-size-select" title="Cỡ chữ">
                                            <option value="">Cỡ chữ</option>
                                            <option value="1">Rất nhỏ</option>
                                            <option value="2">Nhỏ</option>
                                            <option value="3">Bình thường</option>
                                            <option value="4">Vừa</option>
                                            <option value="5">Lớn</option>
                                            <option value="6">Rất lớn</option>
                                            <option value="7">Cực lớn</option>
                                        </select>
                                        <select class="toolbar-select font-family-select" title="Phông chữ">
                                            <option value="">Phông chữ</option>
                                            <option value="Arial">Arial</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Courier New">Courier New</option>
                                        </select>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="bold" title="In đậm (Ctrl+B)"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="italic" title="In nghiêng (Ctrl+I)"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="underline" title="Gạch chân (Ctrl+U)"><i class="bi bi-type-underline"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="strikeThrough" title="Gạch ngang"><i class="bi bi-type-strikethrough"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <input type="color" class="toolbar-color" data-command="foreColor" value="#000000" title="Màu chữ">
                                        <input type="color" class="toolbar-color" data-command="hiliteColor" value="#ffff00" title="Màu nền">
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="justifyLeft" title="Căn trái"><i class="bi bi-text-left"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="justifyCenter" title="Căn giữa"><i class="bi bi-text-center"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="justifyRight" title="Căn phải"><i class="bi bi-text-right"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="insertUnorderedList" title="Danh sách chấm"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="insertOrderedList" title="Danh sách số"><i class="bi bi-list-ol"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="indent" title="Thụt vào"><i class="bi bi-text-indent-left"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="outdent" title="Thụt ra"><i class="bi bi-text-indent-right"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn btn-link" title="Chèn link"><i class="bi bi-link-45deg"></i></button>
                                        <button type="button" class="toolbar-btn btn-image" title="Chèn ảnh"><i class="bi bi-image"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" data-command="removeFormat" title="Xóa định dạng"><i class="bi bi-eraser"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="undo" title="Hoàn tác"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <button type="button" class="toolbar-btn" data-command="redo" title="Làm lại"><i class="bi bi-arrow-clockwise"></i></button>
                                    </div>
                                </div>
                                
                                <div class="content-editor" contenteditable="true" data-template="<?php echo $key; ?>"><?php 
                                    echo wp_kses_post($saved['content'] ?? $tpl['default_content']); 
                                ?></div>
                                <input type="hidden" name="templates[<?php echo $key; ?>][content]" class="content-hidden" value="<?php echo esc_attr($saved['content'] ?? $tpl['default_content']); ?>">
                            </div>
                            
                            <!-- Attachments -->
                            <div class="attachments-section">
                                <h4><i class="bi bi-paperclip"></i> Tệp đính kèm</h4>
                                <div class="attachment-upload">
                                    <label class="upload-btn">
                                        <i class="bi bi-upload"></i>
                                        <span>Tải lên tệp</span>
                                        <input type="file" name="attachments[<?php echo $key; ?>][]" multiple style="display:none;">
                                    </label>
                                    <button type="button" class="upload-btn btn-media-library" data-template="<?php echo $key; ?>">
                                        <i class="bi bi-images"></i>
                                        <span>Thư viện Media</span>
                                    </button>
                                </div>
                                <div class="attachment-list" data-template="<?php echo $key; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                    
                    <button type="submit" name="save_email_templates" class="save-templates-btn">
                        <i class="bi bi-check-circle"></i> Lưu tất cả mẫu thông báo
                    </button>
                </div>
                
                <!-- Sidebar - Variables Reference -->
                <div class="templates-sidebar">
                    <div class="variables-card">
                        <div class="variables-header">
                            <h3><i class="bi bi-code-square"></i> Danh sách biến</h3>
                            <p>Click vào biến để chèn vào nội dung</p>
                        </div>
                        <div class="variables-body">
                            <?php foreach ($all_variables as $group_name => $vars): ?>
                            <div class="variable-group">
                                <div class="variable-group-title"><?php echo esc_html($group_name); ?></div>
                                <?php foreach ($vars as $var => $desc): ?>
                                <div class="variable-item" data-variable="<?php echo esc_attr($var); ?>">
                                    <span><?php echo esc_html($desc); ?></span>
                                    <code><?php echo esc_html($var); ?></code>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Preview -->
                    <div class="preview-card">
                        <div class="preview-header">
                            <i class="bi bi-eye"></i>
                            <span>Xem trước email</span>
                        </div>
                        <div class="preview-body">
                            <button type="button" class="button btn-preview-email" style="width:100%;">
                                <i class="bi bi-display"></i> Xem trước trong popup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Preview Modal -->
        <div id="email-preview-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:100000;padding:30px;overflow:auto;">
            <div style="max-width:700px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
                <div style="padding:15px 20px;background:linear-gradient(135deg, #EC802B, #F5994D);color:#fff;display:flex;justify-content:space-between;align-items:center;">
                    <span><i class="bi bi-envelope-open"></i> Xem trước thông báo</span>
                    <button type="button" onclick="document.getElementById('email-preview-modal').style.display='none';" style="background:none;border:none;color:#fff;font-size:24px;cursor:pointer;">&times;</button>
                </div>
                <div id="email-preview-content" style="padding:20px;background:#f5f5f5;"></div>
            </div>
        </div>
        
        <!-- Link Insert Modal -->
        <div id="tplLinkModal" class="tpl-modal">
            <div class="tpl-modal-content">
                <div class="tpl-modal-header">
                    <h4><i class="bi bi-link-45deg"></i> Chèn liên kết</h4>
                    <button type="button" class="tpl-modal-close" id="closeLinkModal">&times;</button>
                </div>
                <div class="tpl-modal-body">
                    <div class="form-field">
                        <label><i class="bi bi-type label-icon"></i> Văn bản hiển thị</label>
                        <input type="text" id="tplLinkText" placeholder="Nhấn vào đây...">
                    </div>
                    <div class="form-field">
                        <label><i class="bi bi-link label-icon"></i> URL</label>
                        <input type="url" id="tplLinkUrl" placeholder="https://example.com">
                    </div>
                </div>
                <div class="tpl-modal-footer">
                    <button type="button" class="btn-cancel" id="cancelLinkModal">Hủy</button>
                    <button type="button" class="btn-insert" id="insertLinkBtn">Chèn liên kết</button>
                </div>
            </div>
        </div>
        
        <!-- Image Insert Modal -->
        <div id="tplImageModal" class="tpl-modal">
            <div class="tpl-modal-content">
                <div class="tpl-modal-header">
                    <h4><i class="bi bi-image"></i> Chèn hình ảnh</h4>
                    <button type="button" class="tpl-modal-close" id="closeImageModal">&times;</button>
                </div>
                <div class="tpl-modal-body">
                    <div class="form-field">
                        <label><i class="bi bi-link label-icon"></i> URL hình ảnh</label>
                        <input type="url" id="tplImageUrl" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="form-field">
                        <label><i class="bi bi-textarea label-icon"></i> Mô tả (alt text)</label>
                        <input type="text" id="tplImageAlt" placeholder="Mô tả hình ảnh...">
                    </div>
                    <div class="form-field">
                        <label><i class="bi bi-arrows-angle-expand label-icon"></i> Chiều rộng (px)</label>
                        <input type="number" id="tplImageWidth" placeholder="400" value="400">
                    </div>
                </div>
                <div class="tpl-modal-footer">
                    <button type="button" class="btn-cancel" id="cancelImageModal">Hủy</button>
                    <button type="button" class="btn-insert" id="insertImageBtn">Chèn hình ảnh</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var savedSelection = null;
            var activeEditorElement = null;
            
            // Save selection before opening modal
            function saveSelection() {
                var sel = window.getSelection();
                if (sel.rangeCount > 0) {
                    savedSelection = sel.getRangeAt(0);
                }
            }
            
            // Restore selection after modal
            function restoreSelection() {
                if (savedSelection && activeEditorElement) {
                    activeEditorElement.focus();
                    var sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedSelection);
                }
            }
            
            // Tab switching
            $('.template-tab').on('click', function() {
                var template = $(this).data('template');
                $('.template-tab').removeClass('active');
                $(this).addClass('active');
                $('.template-editor').removeClass('active');
                $('.template-editor[data-template="' + template + '"]').addClass('active');
            });
            
            // Toolbar commands
            $('.toolbar-btn[data-command]').on('click', function() {
                var command = $(this).data('command');
                document.execCommand(command, false, null);
                $(this).closest('.template-editor').find('.content-editor').focus();
            });
            
            // Font size
            $('.font-size-select').on('change', function() {
                var value = $(this).val();
                if (value) {
                    document.execCommand('fontSize', false, value);
                }
                $(this).val('');
                $(this).closest('.template-editor').find('.content-editor').focus();
            });
            
            // Font family
            $('.font-family-select').on('change', function() {
                var value = $(this).val();
                if (value) {
                    document.execCommand('fontName', false, value);
                }
                $(this).val('');
                $(this).closest('.template-editor').find('.content-editor').focus();
            });
            
            // Color pickers
            $('.toolbar-color').on('input', function() {
                var command = $(this).data('command');
                var value = $(this).val();
                document.execCommand(command, false, value);
            });
            
            // Insert link - Open Modal
            $('.btn-link').on('click', function() {
                activeEditorElement = $(this).closest('.template-editor').find('.content-editor')[0];
                saveSelection();
                var selectedText = window.getSelection().toString();
                $('#tplLinkText').val(selectedText);
                $('#tplLinkUrl').val('');
                $('#tplLinkModal').addClass('show');
            });
            
            // Close link modal
            $('#closeLinkModal, #cancelLinkModal').on('click', function() {
                $('#tplLinkModal').removeClass('show');
            });
            
            // Insert link
            $('#insertLinkBtn').on('click', function() {
                var text = $('#tplLinkText').val();
                var url = $('#tplLinkUrl').val();
                
                if (!url) {
                    alert('Vui lòng nhập URL!');
                    return;
                }
                
                $('#tplLinkModal').removeClass('show');
                restoreSelection();
                
                var link = '<a href="' + url + '" target="_blank" style="color:#EC802B;">' + (text || url) + '</a>';
                document.execCommand('insertHTML', false, link);
            });
            
            // Insert image - Open Modal
            $('.btn-image').on('click', function() {
                activeEditorElement = $(this).closest('.template-editor').find('.content-editor')[0];
                saveSelection();
                $('#tplImageUrl').val('');
                $('#tplImageAlt').val('');
                $('#tplImageWidth').val('400');
                $('#tplImageModal').addClass('show');
            });
            
            // Close image modal
            $('#closeImageModal, #cancelImageModal').on('click', function() {
                $('#tplImageModal').removeClass('show');
            });
            
            // Insert image
            $('#insertImageBtn').on('click', function() {
                var url = $('#tplImageUrl').val();
                var alt = $('#tplImageAlt').val();
                var width = $('#tplImageWidth').val() || '400';
                
                if (!url) {
                    alert('Vui lòng nhập URL hình ảnh!');
                    return;
                }
                
                $('#tplImageModal').removeClass('show');
                restoreSelection();
                
                var img = '<img src="' + url + '" alt="' + alt + '" style="max-width:' + width + 'px;height:auto;display:block;margin:10px 0;">';
                document.execCommand('insertHTML', false, img);
            });
            
            // Close modals on backdrop click
            $('.tpl-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });
            
            // Media Library
            $('.btn-media-library').on('click', function() {
                var template = $(this).data('template');
                var frame = wp.media({
                    title: 'Chọn ảnh hoặc tệp đính kèm',
                    multiple: true,
                    library: { type: '' }
                });
                
                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    var list = $('.attachment-list[data-template="' + template + '"]');
                    
                    attachments.forEach(function(att) {
                        var preview = att.type === 'image' ? '<img src="' + att.url + '">' : '<span class="dashicons dashicons-media-default"></span>';
                        list.append(
                            '<div class="attachment-item" data-url="' + att.url + '">' +
                                preview +
                                '<span>' + att.filename + '</span>' +
                                '<span class="remove dashicons dashicons-no-alt"></span>' +
                            '</div>'
                        );
                    });
                });
                
                frame.open();
            });
            
            // Remove attachment
            $(document).on('click', '.attachment-item .remove', function() {
                $(this).closest('.attachment-item').remove();
            });
            
            // Insert variable
            $('.variable-item').on('click', function() {
                var variable = $(this).data('variable');
                var activeEditor = $('.template-editor.active .content-editor');
                
                if (activeEditor.length) {
                    activeEditor.focus();
                    document.execCommand('insertText', false, variable);
                }
            });
            
            // Update hidden input on content change
            $('.content-editor').on('input blur', function() {
                var content = $(this).html();
                $(this).closest('.form-field').find('.content-hidden').val(content);
            });
            
            // File upload preview
            $('input[type="file"]').on('change', function() {
                var template = $(this).attr('name').match(/\[(\w+)\]/)[1];
                var list = $('.attachment-list[data-template="' + template + '"]');
                var files = this.files;
                
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var preview = file.type.startsWith('image/') 
                        ? '<img src="' + URL.createObjectURL(file) + '">' 
                        : '<span class="dashicons dashicons-media-default"></span>';
                    
                    list.append(
                        '<div class="attachment-item">' +
                            preview +
                            '<span>' + file.name + '</span>' +
                            '<span class="remove dashicons dashicons-no-alt"></span>' +
                        '</div>'
                    );
                }
            });
            
            // Preview email
            $('.btn-preview-email').on('click', function() {
                var activeEditor = $('.template-editor.active');
                var subject = activeEditor.find('input[name*="[subject]"]').val();
                var fromName = activeEditor.find('input[name*="[from_name]"]').val();
                var content = activeEditor.find('.content-editor').html();
                
                var shopName = '<?php echo esc_js($shop_name); ?>';
                
                var preview = `
                    <div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 15px rgba(0,0,0,0.1);">
                        <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:25px;text-align:center;">
                            <h1 style="color:#fff;margin:0;font-size:22px;">${shopName}</h1>
                        </div>
                        <div style="padding:25px;">
                            <div style="margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #eee;">
                                <strong>Tiêu đề:</strong> ${subject}<br>
                                <strong>Từ:</strong> ${fromName}
                            </div>
                            <div style="line-height:1.7;">${content}</div>
                        </div>
                        <div style="background:#f8f9fa;padding:15px;text-align:center;font-size:12px;color:#888;">
                            © <?php echo date('Y'); ?> ${shopName}. All rights reserved.
                        </div>
                    </div>
                `;
                
                $('#email-preview-content').html(preview);
                $('#email-preview-modal').fadeIn(200);
            });
            
            // Close modal on outside click
            $('#email-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(200);
                }
            });
            
            // Keyboard shortcuts
            $('.content-editor').on('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key.toLowerCase()) {
                        case 'b':
                            e.preventDefault();
                            document.execCommand('bold', false, null);
                            break;
                        case 'i':
                            e.preventDefault();
                            document.execCommand('italic', false, null);
                            break;
                        case 'u':
                            e.preventDefault();
                            document.execCommand('underline', false, null);
                            break;
                    }
                }
            });
        });
        </script>
    </div>
    <?php
}

// Helper function để lấy email template
function petshop_get_email_template($key, $vars = array()) {
    $templates = get_option('petshop_email_templates', array());
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
    
    $default_contents = array(
        'welcome' => 'Chào mừng <strong>{customer_name}</strong> đã đến với {shop_name}!<br><br>Chúng tôi rất vui được phục vụ bạn.',
        'order_placed' => 'Cảm ơn <strong>{customer_name}</strong>!<br><br>Đơn hàng <strong>#{order_code}</strong> của bạn đã được tiếp nhận.',
        'reply' => 'Xin chào <strong>{name}</strong>,<br><br>{message}<br><br>Trân trọng,<br>{shop_name}',
    );
    
    // Check if new format (array with subject, content, etc.) or old format (string)
    $template_data = $templates[$key] ?? null;
    $content = '';
    
    if (is_array($template_data)) {
        // New format
        $content = $template_data['content'] ?? ($default_contents[$key] ?? '');
    } else {
        // Old format (string) or default
        $content = $template_data ?? ($default_contents[$key] ?? '');
    }
    
    // Replace variables
    foreach ($vars as $var => $value) {
        $content = str_replace('{' . $var . '}', $value, $content);
    }
    
    // Replace shop_name variable
    $content = str_replace('{shop_name}', $shop_name, $content);
    
    // Wrap in HTML
    return '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
        <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:30px;text-align:center;border-radius:10px 10px 0 0;">
            <h1 style="color:#fff;margin:0;">' . esc_html($shop_name) . '</h1>
        </div>
        <div style="background:#fff;padding:30px;border:1px solid #eee;border-top:none;">
            <div style="color:#333;line-height:1.8;">' . wp_kses_post($content) . '</div>
        </div>
        <div style="background:#f8f9fa;padding:20px;text-align:center;border-radius:0 0 10px 10px;">
            <p style="margin:0;color:#888;font-size:13px;">© ' . date('Y') . ' ' . esc_html($shop_name) . '</p>
        </div>
    </div>';
}

/**
 * Lấy thông tin đầy đủ của email template (bao gồm subject, from, cc, bcc)
 */
function petshop_get_email_template_full($key, $vars = array()) {
    $templates = get_option('petshop_email_templates', array());
    $shop_settings = get_option('petshop_shop_settings', array());
    $shop_name = $shop_settings['shop_name'] ?? get_bloginfo('name');
    $admin_email = get_option('admin_email');
    
    $template_data = $templates[$key] ?? array();
    
    $defaults = array(
        'subject' => '',
        'from_name' => $shop_name,
        'from_email' => $admin_email,
        'reply_to' => '',
        'cc' => '',
        'bcc' => '',
        'content' => '',
    );
    
    $result = wp_parse_args($template_data, $defaults);
    
    // Replace variables in all fields
    foreach ($vars as $var => $value) {
        $result['subject'] = str_replace('{' . $var . '}', $value, $result['subject']);
        $result['content'] = str_replace('{' . $var . '}', $value, $result['content']);
    }
    
    // Replace shop_name
    $result['subject'] = str_replace('{shop_name}', $shop_name, $result['subject']);
    $result['content'] = str_replace('{shop_name}', $shop_name, $result['content']);
    
    // Build HTML content
    $result['html_content'] = '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
        <div style="background:linear-gradient(135deg,#EC802B,#F5994D);padding:30px;text-align:center;border-radius:10px 10px 0 0;">
            <h1 style="color:#fff;margin:0;">' . esc_html($shop_name) . '</h1>
        </div>
        <div style="background:#fff;padding:30px;border:1px solid #eee;border-top:none;">
            <div style="color:#333;line-height:1.8;">' . wp_kses_post($result['content']) . '</div>
        </div>
        <div style="background:#f8f9fa;padding:20px;text-align:center;border-radius:0 0 10px 10px;">
            <p style="margin:0;color:#888;font-size:13px;">© ' . date('Y') . ' ' . esc_html($shop_name) . '</p>
        </div>
    </div>';
    
    // Build headers array
    $result['headers'] = array('Content-Type: text/html; charset=UTF-8');
    
    if (!empty($result['from_name']) && !empty($result['from_email'])) {
        $result['headers'][] = 'From: ' . $result['from_name'] . ' <' . $result['from_email'] . '>';
    }
    
    if (!empty($result['reply_to'])) {
        $result['headers'][] = 'Reply-To: ' . $result['reply_to'];
    }
    
    if (!empty($result['cc'])) {
        $cc_emails = array_map('trim', explode(',', $result['cc']));
        foreach ($cc_emails as $cc) {
            if (is_email($cc)) {
                $result['headers'][] = 'Cc: ' . $cc;
            }
        }
    }
    
    if (!empty($result['bcc'])) {
        $bcc_emails = array_map('trim', explode(',', $result['bcc']));
        foreach ($bcc_emails as $bcc) {
            if (is_email($bcc)) {
                $result['headers'][] = 'Bcc: ' . $bcc;
            }
        }
    }
    
    return $result;
}

// =============================================
// TẠO BẢNG LOG NOTIFICATION
// =============================================
function petshop_create_notification_log_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_notification_logs';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text,
            type varchar(50) DEFAULT 'system',
            recipient_type varchar(50),
            recipient_count int(11) DEFAULT 0,
            channels varchar(100),
            sent_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('init', 'petshop_create_notification_log_table');

// Load Bootstrap Icons
function petshop_communication_admin_scripts() {
    $screen = get_current_screen();
    
    if (strpos($screen->id, 'petshop-communication') !== false || 
        strpos($screen->id, 'petshop-send-notification') !== false ||
        strpos($screen->id, 'petshop-contacts') !== false ||
        strpos($screen->id, 'petshop-email-templates') !== false ||
        strpos($screen->id, 'petshop-notification') !== false ||
        strpos($screen->id, 'petshop-auto-notification') !== false) {
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            array(),
            '1.11.3'
        );
    }
}
add_action('admin_enqueue_scripts', 'petshop_communication_admin_scripts');

// =============================================
// AJAX: XỬ LÝ FORM LIÊN HỆ
// =============================================
add_action('wp_ajax_petshop_contact_form',        'petshop_handle_contact_form');
add_action('wp_ajax_nopriv_petshop_contact_form', 'petshop_handle_contact_form');
function petshop_handle_contact_form() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'petshop_contact_form'))
        wp_send_json_error(array('message' => 'Yêu cầu không hợp lệ'));
    $name    = sanitize_text_field($_POST['name']    ?? '');
    $phone   = sanitize_text_field($_POST['phone']   ?? '');
    $email   = sanitize_email($_POST['email']        ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? 'Liên hệ chung');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    if (!$name || !$message)
        wp_send_json_error(array('message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'));
    $post_id = wp_insert_post(array('post_title'=>'['.$subject.'] '.$name,'post_status'=>'publish','post_type'=>'petshop_contact','post_content'=>$message));
    if (!$post_id || is_wp_error($post_id))
        wp_send_json_error(array('message' => 'Không thể lưu, vui lòng thử lại'));
    update_post_meta($post_id,'contact_name',$name);
    update_post_meta($post_id,'contact_phone',$phone);
    update_post_meta($post_id,'contact_email',$email);
    update_post_meta($post_id,'contact_subject',$subject);
    update_post_meta($post_id,'contact_message',$message);
    update_post_meta($post_id,'contact_status','new');
    update_post_meta($post_id,'contact_date',current_time('mysql'));
    if (is_user_logged_in()) update_post_meta($post_id,'contact_user_id',get_current_user_id());
    petshop_notify_staff_new_contact($post_id,$name,$email,$subject,$message);
    petshop_notify_staff_contact_bell($post_id,$name,$subject);
    petshop_send_contact_confirmation_email($email,$name,$subject);
    wp_send_json_success(array('message'=>'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong vòng 24 giờ.','contact_id'=>$post_id));
}
function petshop_notify_staff_new_contact($cid,$name,$email,$subject,$message){
    $staff=get_users(array('capability'=>'edit_posts','fields'=>array('ID','user_email')));
    $url=admin_url('admin.php?page=petshop-contacts');
    $site=get_bloginfo('name');
    $short=wp_trim_words($message,30,'...');
    foreach($staff as $u){
        if(!$u->user_email)continue;
        $body="<div style='font-family:Arial,sans-serif;max-width:600px;'><div style='background:linear-gradient(135deg,#EC802B,#F5994D);padding:25px;text-align:center;border-radius:10px 10px 0 0;'><h2 style='color:#fff;margin:0;'>📨 Liên hệ mới</h2></div><div style='background:#fff;padding:25px;border:1px solid #eee;border-radius:0 0 10px 10px;'><p><strong>Từ:</strong> ".esc_html($name)." &lt;<a href='mailto:".esc_attr($email)."'>".esc_html($email)."</a>&gt;</p><p><strong>Chủ đề:</strong> ".esc_html($subject)."</p><p><strong>Nội dung:</strong><br>".nl2br(esc_html($short))."</p><div style='text-align:center;margin-top:20px;'><a href='".esc_url($url)."' style='background:#EC802B;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;'>Xem &amp; Phản hồi →</a></div></div></div>";
        wp_mail($u->user_email,"[{$site}] Liên hệ mới: {$subject}",$body,array('Content-Type: text/html; charset=UTF-8'));
    }
}
function petshop_notify_staff_contact_bell($cid,$sender,$subject){
    if(!function_exists('petshop_create_notification'))return;
    $staff=get_users(array('capability'=>'edit_posts','fields'=>array('ID')));
    $link=admin_url('admin.php?page=petshop-contacts');
    foreach($staff as $u){petshop_create_notification($u->ID,'system','Liên hệ mới: '.$subject,$sender.' vừa gửi liên hệ qua website.',array('link'=>$link,'icon'=>'bi-envelope-open-fill','color'=>'#EC802B','send_email'=>false));}
}
function petshop_send_contact_confirmation_email($email,$name,$subject){
    if(!is_email($email))return;
    $site=get_bloginfo('name');
    $body="<div style='font-family:Arial,sans-serif;max-width:600px;'><div style='background:linear-gradient(135deg,#EC802B,#F5994D);padding:28px;text-align:center;border-radius:10px 10px 0 0;'><h1 style='color:#fff;margin:0;font-size:1.4rem;'>{$site}</h1></div><div style='background:#fff;padding:28px;border:1px solid #eee;border-radius:0 0 10px 10px;'><h2 style='color:#5D4E37;'>Xin chào ".esc_html($name)."!</h2><p style='color:#666;'>Đã nhận liên hệ về: <strong style='color:#EC802B;'>".esc_html($subject)."</strong></p><p style='color:#666;'>Chúng tôi sẽ phản hồi trong vòng <strong>24 giờ làm việc</strong>.</p></div></div>";
    wp_mail($email,"[{$site}] Đã nhận liên hệ của bạn",$body,array('Content-Type: text/html; charset=UTF-8'));
}
add_action('wp_ajax_petshop_reply_contact','petshop_ajax_reply_contact');
function petshop_ajax_reply_contact(){
    if(!current_user_can('edit_posts'))wp_send_json_error('Unauthorized');
    check_ajax_referer('petshop_reply_contact','nonce');
    $cid=intval($_POST['contact_id']??0);
    $msg=sanitize_textarea_field($_POST['reply_message']??'');
    $send_email=!empty($_POST['send_email']);
    $send_bell=!empty($_POST['send_bell']);
    if(!$cid||!$msg)wp_send_json_error('Thiếu thông tin');
    $contact=get_post($cid);
    if(!$contact||$contact->post_type!=='petshop_contact')wp_send_json_error('Không tìm thấy');
    $replies=json_decode(get_post_meta($cid,'contact_replies',true)?:'[]',true);
    $replies[]=array('staff_id'=>get_current_user_id(),'staff_name'=>wp_get_current_user()->display_name,'message'=>$msg,'date'=>current_time('mysql'),'sent_email'=>$send_email,'sent_bell'=>$send_bell);
    update_post_meta($cid,'contact_replies',json_encode($replies,JSON_UNESCAPED_UNICODE));
    update_post_meta($cid,'contact_status','replied');
    update_post_meta($cid,'contact_reply',$msg);
    update_post_meta($cid,'contact_reply_date',current_time('mysql'));
    $cname=get_post_meta($cid,'contact_name',true);
    $cemail=get_post_meta($cid,'contact_email',true);
    $subj=get_post_meta($cid,'contact_subject',true)?:'Liên hệ';
    $uid=get_post_meta($cid,'contact_user_id',true);
    $site=get_bloginfo('name');
    if($send_email&&is_email($cemail)){
        $body="<div style='font-family:Arial,sans-serif;max-width:600px;'><div style='background:linear-gradient(135deg,#EC802B,#F5994D);padding:25px;text-align:center;border-radius:10px 10px 0 0;'><h2 style='color:#fff;margin:0;'>{$site} — Phản hồi</h2></div><div style='background:#fff;padding:25px;border:1px solid #eee;border-radius:0 0 10px 10px;'><p style='color:#5D4E37;'>Xin chào <strong>".esc_html($cname)."</strong>,</p><p>Phản hồi cho: <strong style='color:#EC802B;'>".esc_html($subj)."</strong></p><div style='background:#FDF8F3;border-left:4px solid #EC802B;padding:18px;border-radius:0 10px 10px 0;margin:16px 0;'><p style='color:#5D4E37;line-height:1.7;margin:0;'>".nl2br(esc_html($msg))."</p></div><p style='color:#666;font-size:.9rem;'>Trân trọng,<br><strong>{$site}</strong></p></div></div>";
        wp_mail($cemail,"[{$site}] Phản hồi: {$subj}",$body,array('Content-Type: text/html; charset=UTF-8'));
    }
    if($send_bell&&$uid&&function_exists('petshop_create_notification'))
        petshop_create_notification($uid,'system','Phản hồi: '.$subj,wp_trim_words($msg,20,'...'),array('link'=>home_url('/tai-khoan/#contacts'),'icon'=>'bi-chat-dots-fill','color'=>'#EC802B','send_email'=>false));
    wp_send_json_success(array('message'=>'Đã gửi phản hồi thành công'));
}