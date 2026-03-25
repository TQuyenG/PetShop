<?php
/**
 * PetShop CRM Customer Management
 * Quản lý khách hàng CRM - Kết nối với WordPress Users và petshop_order
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// ...existing code...

// =============================================
// KHỞI TẠO CRM - CHỈ TẠO BẢNG PHỤ TRỢ
// =============================================
function petshop_crm_init() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Chỉ tạo bảng ghi chú và hoạt động - KHÔNG tạo bảng khách hàng riêng
    // Bảng ghi chú CRM cho khách hàng
    $table_notes = $wpdb->prefix . 'petshop_crm_notes';
    $sql_notes = "CREATE TABLE IF NOT EXISTS $table_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) NOT NULL,
        note TEXT NOT NULL,
        created_by BIGINT(20) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) $charset_collate;";
    
    // Bảng hoạt động CRM
    $table_activity = $wpdb->prefix . 'petshop_crm_activity';
    $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        order_id BIGINT(20) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_activity_type (activity_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_notes);
    dbDelta($sql_activity);
}
add_action('after_switch_theme', 'petshop_crm_init');
add_action('admin_init', 'petshop_crm_init');

// =============================================
// TÍNH TOÁN THỐNG KÊ KHÁCH HÀNG TỪ ĐƠN HÀNG
// =============================================
function petshop_crm_get_customer_stats($user_id) {
    global $wpdb;
    
    if (!$user_id) {
        return array(
            'total_orders' => 0,
            'total_spent' => 0,
            'completed_orders' => 0,
            'tier' => 'bronze',
            'tier_label' => 'Đồng',
            'points' => 0,
            'first_order_date' => null,
            'last_order_date' => null
        );
    }
    
    // Lấy tất cả đơn hàng của user từ petshop_order
    $orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'customer_user_id',
                'value' => $user_id
            )
        ),
        'orderby' => 'date',
        'order' => 'ASC'
    ));
    
    $total_orders = count($orders);
    $completed_orders = 0;
    $total_spent = 0;
    $first_order_date = null;
    $last_order_date = null;
    
    foreach ($orders as $order) {
        $status = get_post_meta($order->ID, 'order_status', true);
        $order_total = floatval(get_post_meta($order->ID, 'order_total', true));
        $order_date = get_post_meta($order->ID, 'order_date', true);
        
        // Chỉ tính đơn hoàn thành
        if ($status === 'completed') {
            $completed_orders++;
            $total_spent += $order_total;
        }
        
        // Ngày đơn hàng đầu tiên và cuối cùng
        if (!$first_order_date) {
            $first_order_date = $order_date;
        }
        $last_order_date = $order_date;
    }
    
    // Xác định tier dựa trên tổng chi tiêu
    $tier = 'bronze';
    $tier_label = 'Đồng';
    
    if ($total_spent >= 10000000) { // 10 triệu
        $tier = 'gold';
        $tier_label = 'Vàng';
    } elseif ($total_spent >= 3000000) { // 3 triệu
        $tier = 'silver';
        $tier_label = 'Bạc';
    }
    
    // Tính điểm (1% giá trị đơn hàng)
    $points = floor($total_spent / 100);
    
    return array(
        'total_orders' => $total_orders,
        'total_spent' => $total_spent,
        'completed_orders' => $completed_orders,
        'tier' => $tier,
        'tier_label' => $tier_label,
        'points' => $points,
        'first_order_date' => $first_order_date,
        'last_order_date' => $last_order_date
    );
}

// =============================================
// LẤY DANH SÁCH KHÁCH HÀNG (TỪ WORDPRESS USERS)
// =============================================
function petshop_crm_get_customers($args = array()) {
    $defaults = array(
        'search' => '',
        'tier' => '',
        'orderby' => 'registered',
        'order' => 'DESC',
        'paged' => 1,
        'per_page' => 20
    );
    $args = wp_parse_args($args, $defaults);
    
    // Query users có role customer hoặc đã đặt hàng
    $user_args = array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'orderby' => $args['orderby'],
        'order' => $args['order'],
        'number' => $args['per_page'],
        'paged' => $args['paged']
    );
    
    // Tìm kiếm
    if (!empty($args['search'])) {
        $user_args['search'] = '*' . $args['search'] . '*';
        $user_args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }
    
    $user_query = new WP_User_Query($user_args);
    $users = $user_query->get_results();
    $total = $user_query->get_total();
    
    $customers = array();
    foreach ($users as $user) {
        $stats = petshop_crm_get_customer_stats($user->ID);
        
        // Filter theo tier nếu có
        if (!empty($args['tier']) && $stats['tier'] !== $args['tier']) {
            continue;
        }
        
        $customers[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'petshop_phone', true),
            'registered' => $user->user_registered,
            'stats' => $stats
        );
    }
    
    return array(
        'customers' => $customers,
        'total' => $total,
        'pages' => ceil($total / $args['per_page'])
    );
}

// =============================================
// LẤY CHI TIẾT KHÁCH HÀNG
// =============================================
function petshop_crm_get_customer_detail($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return null;
    }
    
    $stats = petshop_crm_get_customer_stats($user_id);
    
    // Lấy địa chỉ
    $addresses = get_user_meta($user_id, 'petshop_addresses', true);
    if (!is_array($addresses)) {
        $addresses = array();
    }
    
    // Lấy ghi chú CRM
    global $wpdb;
    $notes = $wpdb->get_results($wpdb->prepare(
        "SELECT n.*, u.display_name as created_by_name 
         FROM {$wpdb->prefix}petshop_crm_notes n
         LEFT JOIN {$wpdb->users} u ON n.created_by = u.ID
         WHERE n.user_id = %d 
         ORDER BY n.created_at DESC",
        $user_id
    ));
    
    // Lấy hoạt động gần đây
    $activities = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}petshop_crm_activity 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT 20",
        $user_id
    ));
    
    return array(
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'phone' => get_user_meta($user_id, 'petshop_phone', true),
        'registered' => $user->user_registered,
        'addresses' => $addresses,
        'stats' => $stats,
        'notes' => $notes,
        'activities' => $activities
    );
}

// =============================================
// LẤY LỊCH SỬ ĐƠN HÀNG CỦA KHÁCH
// =============================================
function petshop_crm_get_customer_orders($user_id, $limit = -1) {
    $orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => $limit,
        'meta_query' => array(
            array(
                'key' => 'customer_user_id',
                'value' => $user_id
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    $result = array();
    foreach ($orders as $order) {
        $result[] = array(
            'id' => $order->ID,
            'order_code' => get_post_meta($order->ID, 'order_code', true),
            'order_total' => floatval(get_post_meta($order->ID, 'order_total', true)),
            'order_status' => get_post_meta($order->ID, 'order_status', true),
            'order_date' => get_post_meta($order->ID, 'order_date', true),
            'payment_method' => get_post_meta($order->ID, 'payment_method', true),
            'cart_items' => json_decode(get_post_meta($order->ID, 'cart_items', true), true)
        );
    }
    
    return $result;
}

// =============================================
// THÊM GHI CHÚ CHO KHÁCH HÀNG
// =============================================
function petshop_crm_add_note($user_id, $note) {
    global $wpdb;
    
    return $wpdb->insert(
        $wpdb->prefix . 'petshop_crm_notes',
        array(
            'user_id' => $user_id,
            'note' => sanitize_textarea_field($note),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%d', '%s')
    );
}

// =============================================
// GHI HOẠT ĐỘNG KHÁCH HÀNG
// =============================================
function petshop_crm_log_activity($user_id, $type, $description, $order_id = 0) {
    global $wpdb;
    
    return $wpdb->insert(
        $wpdb->prefix . 'petshop_crm_activity',
        array(
            'user_id' => $user_id,
            'activity_type' => $type,
            'description' => $description,
            'order_id' => $order_id,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%d', '%s')
    );
}

// Hook vào đơn hàng mới để ghi hoạt động
add_action('petshop_order_created', function($order_id) {
    $user_id = get_post_meta($order_id, 'customer_user_id', true);
    if ($user_id) {
        $order_code = get_post_meta($order_id, 'order_code', true);
        $order_total = get_post_meta($order_id, 'order_total', true);
        petshop_crm_log_activity(
            $user_id,
            'order_created',
            sprintf('Đặt đơn hàng #%s - %s', $order_code, number_format($order_total) . 'đ'),
            $order_id
        );
    }
});

// Hook vào khi đơn hàng thay đổi trạng thái
add_action('petshop_order_status_changed', function($order_id, $new_status, $old_status) {
    $user_id = get_post_meta($order_id, 'customer_user_id', true);
    if ($user_id) {
        $order_code = get_post_meta($order_id, 'order_code', true);
        $status_labels = array(
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        );
        $status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
        petshop_crm_log_activity(
            $user_id,
            'order_status_' . $new_status,
            sprintf('Đơn hàng #%s chuyển sang: %s', $order_code, $status_label),
            $order_id
        );
    }
}, 10, 3);

// =============================================
// ADMIN PAGE: QUẢN LÝ KHÁCH HÀNG CRM
// =============================================
function petshop_crm_customers_page() {
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $tier = isset($_GET['tier']) ? sanitize_text_field($_GET['tier']) : '';
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
    
    // Xem chi tiết khách hàng
    if ($view === 'detail' && $customer_id > 0) {
        petshop_crm_customer_detail_view($customer_id);
        return;
    }
    
    // Danh sách khách hàng
    $result = petshop_crm_get_customers(array(
        'search' => $search,
        'tier' => $tier,
        'paged' => $paged
    ));
    
    ?>
    <div class="wrap petshop-crm">
        <h1><i class="bi bi-people"></i> Quản lý Khách hàng CRM</h1>
        
        <style>
        .petshop-crm { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .petshop-crm h1 { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .crm-filters { background: #fff; padding: 15px 20px; border: 1px solid #ddd; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .crm-filters input, .crm-filters select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .crm-filters input[type="text"] { width: 250px; }
        .crm-table { width: 100%; background: #fff; border: 1px solid #ddd; border-collapse: collapse; }
        .crm-table th, .crm-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .crm-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .crm-table tr:hover { background: #f8f9fa; }
        .tier-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .tier-gold { background: #fff3cd; color: #856404; }
        .tier-silver { background: #e9ecef; color: #495057; }
        .tier-bronze { background: #f8d7da; color: #721c24; }
        .crm-stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .crm-stat-card { background: #fff; border: 1px solid #ddd; padding: 20px; flex: 1; text-align: center; }
        .crm-stat-card h3 { margin: 0 0 8px 0; font-size: 28px; color: #333; }
        .crm-stat-card p { margin: 0; color: #666; }
        .btn-view { padding: 5px 12px; background: #333; color: #fff; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-view:hover { background: #555; color: #fff; }
        .pagination { margin-top: 20px; display: flex; gap: 5px; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #333; }
        .pagination .current { background: #333; color: #fff; border-color: #333; }
        </style>
        
        <?php
        // Thống kê tổng quan
        $all_customers = petshop_crm_get_customers(array('per_page' => 1000));
        $tier_stats = array('gold' => 0, 'silver' => 0, 'bronze' => 0);
        $total_revenue = 0;
        
        foreach ($all_customers['customers'] as $c) {
            $tier_stats[$c['stats']['tier']]++;
            $total_revenue += $c['stats']['total_spent'];
        }
        ?>
        
        <div class="crm-stats">
            <div class="crm-stat-card">
                <h3><?php echo number_format($all_customers['total']); ?></h3>
                <p>Tổng khách hàng</p>
            </div>
            <div class="crm-stat-card">
                <h3><?php echo number_format($tier_stats['gold']); ?></h3>
                <p>Khách Vàng</p>
            </div>
            <div class="crm-stat-card">
                <h3><?php echo number_format($tier_stats['silver']); ?></h3>
                <p>Khách Bạc</p>
            </div>
            <div class="crm-stat-card">
                <h3><?php echo number_format($tier_stats['bronze']); ?></h3>
                <p>Khách Đồng</p>
            </div>
            <div class="crm-stat-card">
                <h3><?php echo number_format($total_revenue); ?>đ</h3>
                <p>Tổng doanh thu từ KH</p>
            </div>
        </div>
        
        <div class="crm-filters">
            <form method="get" action="" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="petshop-crm-customers">
                
                <input type="text" name="search" placeholder="Tìm tên, email, SĐT..." value="<?php echo esc_attr($search); ?>">
                
                <select name="tier">
                    <option value="">-- Tất cả hạng --</option>
                    <option value="gold" <?php selected($tier, 'gold'); ?>>Vàng</option>
                    <option value="silver" <?php selected($tier, 'silver'); ?>>Bạc</option>
                    <option value="bronze" <?php selected($tier, 'bronze'); ?>>Đồng</option>
                </select>
                
                <button type="submit" class="button">Lọc</button>
                <a href="<?php echo admin_url('admin.php?page=petshop-crm-customers'); ?>" class="button">Reset</a>
            </form>
        </div>
        
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Hạng</th>
                    <th>Đơn hàng</th>
                    <th>Chi tiêu</th>
                    <th>Điểm</th>
                    <th>Ngày tham gia</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($result['customers'])): ?>
                <tr><td colspan="8" style="text-align: center; padding: 40px;">Không có khách hàng nào</td></tr>
            <?php else: foreach ($result['customers'] as $customer): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($customer['name']); ?></strong>
                        <br><small style="color: #888;">#<?php echo $customer['id']; ?></small>
                    </td>
                    <td>
                        <?php echo esc_html($customer['email']); ?>
                        <?php if ($customer['phone']): ?>
                            <br><small><?php echo esc_html($customer['phone']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="tier-badge tier-<?php echo $customer['stats']['tier']; ?>">
                            <?php echo $customer['stats']['tier_label']; ?>
                        </span>
                    </td>
                    <td><?php echo $customer['stats']['total_orders']; ?> đơn</td>
                    <td><strong><?php echo number_format($customer['stats']['total_spent']); ?>đ</strong></td>
                    <td><?php echo number_format($customer['stats']['points']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($customer['registered'])); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=petshop-crm-customers&view=detail&customer_id=' . $customer['id']); ?>" class="btn-view">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        
        <?php if ($result['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                <?php if ($i == $paged): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// VIEW: CHI TIẾT KHÁCH HÀNG
// =============================================
function petshop_crm_customer_detail_view($customer_id) {
    $customer = petshop_crm_get_customer_detail($customer_id);
    if (!$customer) {
        echo '<div class="wrap"><h1>Không tìm thấy khách hàng</h1></div>';
        return;
    }
    
    $orders = petshop_crm_get_customer_orders($customer_id);
    ?>
    <div class="wrap petshop-crm">
        <style>
        .petshop-crm { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .crm-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .crm-header h1 { margin: 0; }
        .btn-back { padding: 8px 15px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 4px; }
        .crm-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .crm-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        .crm-card h3 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 16px; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f5f5f5; }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 120px; color: #666; }
        .info-value { flex: 1; font-weight: 500; }
        .tier-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .tier-gold { background: #fff3cd; color: #856404; }
        .tier-silver { background: #e9ecef; color: #495057; }
        .tier-bronze { background: #f8d7da; color: #721c24; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th, .orders-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .orders-table th { background: #f8f9fa; font-weight: 600; font-size: 13px; }
        .status-badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .notes-list { max-height: 300px; overflow-y: auto; }
        .note-item { padding: 10px; background: #f8f9fa; margin-bottom: 8px; border-radius: 4px; }
        .note-item p { margin: 0 0 5px 0; }
        .note-item small { color: #888; }
        .note-form textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; }
        .note-form button { margin-top: 10px; padding: 8px 15px; background: #333; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .activity-item { padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .activity-item:last-child { border-bottom: none; }
        .activity-time { color: #888; font-size: 12px; }
        </style>
        
        <div class="crm-header">
            <a href="<?php echo admin_url('admin.php?page=petshop-crm-customers'); ?>" class="btn-back">← Quay lại</a>
            <h1><?php echo esc_html($customer['name']); ?></h1>
            <span class="tier-badge tier-<?php echo $customer['stats']['tier']; ?>">
                Hạng <?php echo $customer['stats']['tier_label']; ?>
            </span>
        </div>
        
        <div class="crm-grid">
            <div class="crm-sidebar">
                <!-- Thông tin cơ bản -->
                <div class="crm-card">
                    <h3>Thông tin khách hàng</h3>
                    <div class="info-row">
                        <span class="info-label">ID:</span>
                        <span class="info-value">#<?php echo $customer['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo esc_html($customer['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Điện thoại:</span>
                        <span class="info-value"><?php echo $customer['phone'] ? esc_html($customer['phone']) : '-'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tham gia:</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($customer['registered'])); ?></span>
                    </div>
                </div>
                
                <!-- Thống kê -->
                <div class="crm-card">
                    <h3>Thống kê mua hàng</h3>
                    <div class="info-row">
                        <span class="info-label">Tổng đơn:</span>
                        <span class="info-value"><?php echo $customer['stats']['total_orders']; ?> đơn</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hoàn thành:</span>
                        <span class="info-value"><?php echo $customer['stats']['completed_orders']; ?> đơn</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tổng chi tiêu:</span>
                        <span class="info-value" style="color: #28a745; font-weight: 600;">
                            <?php echo number_format($customer['stats']['total_spent']); ?>đ
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Điểm tích lũy:</span>
                        <span class="info-value"><?php echo number_format($customer['stats']['points']); ?> điểm</span>
                    </div>
                </div>
                
                <!-- Ghi chú CRM -->
                <div class="crm-card">
                    <h3>Ghi chú CRM</h3>
                    <div class="notes-list">
                        <?php if (empty($customer['notes'])): ?>
                            <p style="color: #888; font-style: italic;">Chưa có ghi chú</p>
                        <?php else: foreach ($customer['notes'] as $note): ?>
                            <div class="note-item">
                                <p><?php echo nl2br(esc_html($note->note)); ?></p>
                                <small>
                                    <?php echo $note->created_by_name ?: 'Admin'; ?> - 
                                    <?php echo date('d/m/Y H:i', strtotime($note->created_at)); ?>
                                </small>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    
                    <div class="note-form" style="margin-top: 15px;">
                        <form method="post" action="">
                            <?php wp_nonce_field('crm_add_note', 'crm_note_nonce'); ?>
                            <input type="hidden" name="action" value="crm_add_note">
                            <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                            <textarea name="note" rows="3" placeholder="Thêm ghi chú..."></textarea>
                            <button type="submit">Lưu ghi chú</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="crm-content">
                <!-- Lịch sử đơn hàng -->
                <div class="crm-card">
                    <h3>Lịch sử đơn hàng (<?php echo count($orders); ?>)</h3>
                    <?php if (empty($orders)): ?>
                        <p style="color: #888; padding: 20px 0; text-align: center;">Chưa có đơn hàng</p>
                    <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Sản phẩm</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): 
                            $status_labels = array(
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'processing' => 'Đang xử lý',
                                'shipping' => 'Đang giao',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy'
                            );
                            $payment_labels = array(
                                'cod' => 'COD',
                                'bank_transfer' => 'Chuyển khoản',
                                'vnpay' => 'VNPAY'
                            );
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $order['id'] . '&action=edit'); ?>" target="_blank">
                                        #<?php echo esc_html($order['order_code']); ?>
                                    </a>
                                </td>
                                <td><?php echo $order['order_date'] ? date('d/m/Y', strtotime($order['order_date'])) : '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($order['cart_items']) {
                                        $items_count = count($order['cart_items']);
                                        echo $items_count . ' sản phẩm';
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo number_format($order['order_total']); ?>đ</strong></td>
                                <td><?php echo isset($payment_labels[$order['payment_method']]) ? $payment_labels[$order['payment_method']] : $order['payment_method']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                        <?php echo isset($status_labels[$order['order_status']]) ? $status_labels[$order['order_status']] : $order['order_status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                
                <!-- Hoạt động gần đây -->
                <div class="crm-card">
                    <h3>Hoạt động gần đây</h3>
                    <?php if (empty($customer['activities'])): ?>
                        <p style="color: #888; font-style: italic;">Chưa có hoạt động</p>
                    <?php else: ?>
                        <?php foreach ($customer['activities'] as $activity): ?>
                            <div class="activity-item">
                                <span><?php echo esc_html($activity->description); ?></span>
                                <div class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activity->created_at)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Địa chỉ -->
                <?php if (!empty($customer['addresses'])): ?>
                <div class="crm-card">
                    <h3>Địa chỉ giao hàng (<?php echo count($customer['addresses']); ?>)</h3>
                    <?php foreach ($customer['addresses'] as $addr): ?>
                        <div style="padding: 10px; background: #f8f9fa; margin-bottom: 8px; border-radius: 4px;">
                            <strong><?php echo esc_html($addr['label'] ?? 'Địa chỉ'); ?></strong>
                            <br>
                            <?php echo esc_html($addr['fullname'] ?? ''); ?> - <?php echo esc_html($addr['phone'] ?? ''); ?>
                            <br>
                            <?php 
                            echo esc_html(implode(', ', array_filter([
                                $addr['address'] ?? '',
                                $addr['ward_text'] ?? '',
                                $addr['district_text'] ?? '',
                                $addr['city_text'] ?? ''
                            ])));
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// Xử lý thêm ghi chú
add_action('admin_init', function() {
    if (isset($_POST['action']) && $_POST['action'] === 'crm_add_note') {
        if (!wp_verify_nonce($_POST['crm_note_nonce'], 'crm_add_note')) {
            return;
        }
        
        $customer_id = intval($_POST['customer_id']);
        $note = sanitize_textarea_field($_POST['note']);
        
        if ($customer_id && $note) {
            petshop_crm_add_note($customer_id, $note);
            wp_redirect(admin_url('admin.php?page=petshop-crm-customers&view=detail&customer_id=' . $customer_id . '&note_added=1'));
            exit;
        }
    }
});

// =============================================
// AJAX HANDLERS
// =============================================
add_action('wp_ajax_crm_get_customer_stats', function() {
    $user_id = intval($_POST['user_id']);
    $stats = petshop_crm_get_customer_stats($user_id);
    wp_send_json_success($stats);
});

add_action('wp_ajax_crm_add_note', function() {
    check_ajax_referer('crm_ajax_nonce', 'nonce');
    
    $user_id = intval($_POST['user_id']);
    $note = sanitize_textarea_field($_POST['note']);
    
    if ($user_id && $note) {
        petshop_crm_add_note($user_id, $note);
        wp_send_json_success(array('message' => 'Đã thêm ghi chú'));
    } else {
        wp_send_json_error(array('message' => 'Dữ liệu không hợp lệ'));
    }
});
