<?php
/**
 * PetShop Coupon Management System
 * Hệ thống quản lý mã giảm giá - Giảm theo sản phẩm, combo, toàn đơn hàng
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// Menu đã được đăng ký trong promotion-menu.php

// =============================================
// TẠO BẢNG DATABASE
// =============================================
function petshop_create_coupon_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Bảng coupons chính
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    // Kiểm tra bảng đã tồn tại chưa
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupons'") === $table_coupons;
    
    if (!$table_exists) {
        // Sử dụng query trực tiếp thay vì dbDelta để đảm bảo tạo được bảng
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_coupons (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            type varchar(20) NOT NULL DEFAULT 'order',
            discount_type varchar(20) NOT NULL DEFAULT 'percent',
            discount_value decimal(15,2) NOT NULL DEFAULT 0,
            min_order_amount decimal(15,2) DEFAULT 0,
            max_order_amount decimal(15,2) DEFAULT NULL,
            max_discount_amount decimal(15,2) DEFAULT NULL,
            usage_limit int(11) DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            user_limit int(11) DEFAULT 1,
            per_order_limit int(11) DEFAULT 1,
            start_datetime datetime DEFAULT NULL,
            end_datetime datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            coupon_group varchar(50) DEFAULT NULL,
            apply_to_category text DEFAULT NULL,
            exclude_category text DEFAULT NULL,
            first_order_only tinyint(1) DEFAULT 0,
            new_user_only tinyint(1) DEFAULT 0,
            stackable tinyint(1) DEFAULT 0,
            display_on_cart tinyint(1) DEFAULT 1,
            priority int(11) DEFAULT 10,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY type (type),
            KEY is_active (is_active)
        ) $charset_collate;");
    } else {
        // Thêm các cột mới nếu bảng đã tồn tại
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_coupons");
        $existing_columns = wp_list_pluck($columns, 'Field');
        
        $new_columns = array(
            'max_order_amount' => "ALTER TABLE $table_coupons ADD COLUMN max_order_amount decimal(15,2) DEFAULT NULL AFTER min_order_amount",
            'per_order_limit' => "ALTER TABLE $table_coupons ADD COLUMN per_order_limit int(11) DEFAULT 1 AFTER user_limit",
            'coupon_group' => "ALTER TABLE $table_coupons ADD COLUMN coupon_group varchar(50) DEFAULT NULL AFTER is_active",
            'apply_to_category' => "ALTER TABLE $table_coupons ADD COLUMN apply_to_category text DEFAULT NULL AFTER coupon_group",
            'exclude_category' => "ALTER TABLE $table_coupons ADD COLUMN exclude_category text DEFAULT NULL AFTER apply_to_category",
            'first_order_only' => "ALTER TABLE $table_coupons ADD COLUMN first_order_only tinyint(1) DEFAULT 0 AFTER exclude_category",
            'new_user_only' => "ALTER TABLE $table_coupons ADD COLUMN new_user_only tinyint(1) DEFAULT 0 AFTER first_order_only",
            'stackable' => "ALTER TABLE $table_coupons ADD COLUMN stackable tinyint(1) DEFAULT 0 AFTER new_user_only",
            'display_on_cart' => "ALTER TABLE $table_coupons ADD COLUMN display_on_cart tinyint(1) DEFAULT 1 AFTER stackable",
            'priority' => "ALTER TABLE $table_coupons ADD COLUMN priority int(11) DEFAULT 10 AFTER display_on_cart",
        );
        
        foreach ($new_columns as $column => $sql) {
            if (!in_array($column, $existing_columns)) {
                $wpdb->query($sql);
            }
        }
    }
    
    // Bảng sản phẩm áp dụng coupon (cho type = product)
    $table_coupon_products = $wpdb->prefix . 'petshop_coupon_products';
    $table_products_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupon_products'") === $table_coupon_products;
    
    if (!$table_products_exists) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_coupon_products (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            coupon_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY coupon_id (coupon_id),
            KEY product_id (product_id)
        ) $charset_collate;");
    }
    
    // Bảng combo (cho type = combo)
    $table_combos = $wpdb->prefix . 'petshop_coupon_combos';
    $table_combos_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_combos'") === $table_combos;
    
    if (!$table_combos_exists) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_combos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            coupon_id bigint(20) UNSIGNED NOT NULL,
            main_product_id bigint(20) UNSIGNED NOT NULL,
            combo_product_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY coupon_id (coupon_id),
            KEY main_product_id (main_product_id)
        ) $charset_collate;");
    }
    
    // Bảng lịch sử sử dụng coupon
    $table_usage = $wpdb->prefix . 'petshop_coupon_usage';
    $table_usage_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_usage'") === $table_usage;
    
    if (!$table_usage_exists) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_usage (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            coupon_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            discount_amount decimal(15,2) NOT NULL DEFAULT 0,
            used_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY coupon_id (coupon_id),
            KEY user_id (user_id)
        ) $charset_collate;");
    }
}
add_action('after_switch_theme', 'petshop_create_coupon_tables');
add_action('admin_init', 'petshop_create_coupon_tables');

// =============================================
// TRANG DANH SÁCH MÃ GIẢM GIÁ
// =============================================
function petshop_coupons_page() {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    // Đảm bảo bảng đã được tạo
    petshop_create_coupon_tables();
    
    // Kiểm tra bảng tồn tại
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupons'") === $table_coupons;
    
    if (!$table_exists) {
        echo '<div class="wrap"><div class="notice notice-error"><p><strong>Lỗi:</strong> Không thể tạo bảng database. Vui lòng kiểm tra quyền truy cập database.</p></div></div>';
        return;
    }
    
    // Xử lý xóa
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['coupon_id'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_coupon_' . $_GET['coupon_id'])) {
            $coupon_id = intval($_GET['coupon_id']);
            $wpdb->delete($table_coupons, array('id' => $coupon_id));
            $wpdb->delete($wpdb->prefix . 'petshop_coupon_products', array('coupon_id' => $coupon_id));
            $wpdb->delete($wpdb->prefix . 'petshop_coupon_combos', array('coupon_id' => $coupon_id));
            echo '<div class="notice notice-success is-dismissible"><p>Đã xóa mã giảm giá!</p></div>';
        }
    }
    
    // Xử lý toggle active
    if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['coupon_id'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'toggle_coupon_' . $_GET['coupon_id'])) {
            $coupon_id = intval($_GET['coupon_id']);
            $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $table_coupons WHERE id = %d", $coupon_id));
            $wpdb->update($table_coupons, array('is_active' => $current ? 0 : 1), array('id' => $coupon_id));
            echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật trạng thái!</p></div>';
        }
    }
    
    // Lấy danh sách coupons
    $coupons = $wpdb->get_results("SELECT * FROM $table_coupons ORDER BY created_at DESC");
    
    ?>
    <style>
    .coupon-wrap { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
    .coupon-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .coupon-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #c3c4c7; }
    .stat-card h3 { margin: 0 0 5px; font-size: 28px; }
    .stat-card p { margin: 0; color: #646970; }
    .coupon-table { width: 100%; background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; border-collapse: collapse; }
    .coupon-table th, .coupon-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
    .coupon-table th { background: #f6f7f7; font-weight: 600; }
    .coupon-table tr:hover { background: #f9f9f9; }
    .coupon-code { font-family: monospace; font-weight: 700; font-size: 14px; background: #f0f6fc; padding: 4px 10px; border-radius: 4px; }
    .coupon-type { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .type-order { background: #e8f5e9; color: #2e7d32; }
    .type-product { background: #e3f2fd; color: #1565c0; }
    .type-combo { background: #fff3e0; color: #ef6c00; }
    .coupon-status { padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    .status-active { background: #d4edda; color: #155724; }
    .status-inactive { background: #f8d7da; color: #721c24; }
    .status-expired { background: #fff3cd; color: #856404; }
    .coupon-actions a { margin-right: 10px; text-decoration: none; }
    .coupon-actions .delete { color: #d63638; }
    .no-coupons { text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; }
    .no-coupons .dashicons { font-size: 48px; width: 48px; height: 48px; color: #ccc; margin-bottom: 15px; }
    </style>
    
    <div class="coupon-wrap">
        <?php
        // Debug info nếu được bật
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            $debug_info = array(
                'Table exists' => $table_exists ? 'Yes' : 'No',
                'Table name' => $table_coupons,
                'Coupons count' => is_array($coupons) ? count($coupons) : 'Error',
                'Last DB Error' => $wpdb->last_error ?: 'None',
            );
            echo '<div class="notice notice-info"><p><strong>Debug Info:</strong><br>';
            foreach ($debug_info as $key => $value) {
                echo esc_html($key) . ': ' . esc_html($value) . '<br>';
            }
            echo '</p></div>';
        }
        ?>
        
        <div class="coupon-header">
            <h1><span class="dashicons dashicons-tickets-alt" style="font-size:28px;vertical-align:middle;margin-right:10px;"></span>Quản lý Mã giảm giá</h1>
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-coupon-edit'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span> Thêm mã giảm giá
            </a>
        </div>
        
        <?php
        // Stats
        $total = count($coupons);
        $active = $wpdb->get_var("SELECT COUNT(*) FROM $table_coupons WHERE is_active = 1");
        $total_used = $wpdb->get_var("SELECT SUM(usage_count) FROM $table_coupons");
        $total_discount = $wpdb->get_var("SELECT SUM(discount_amount) FROM {$wpdb->prefix}petshop_coupon_usage");
        ?>
        
        <div class="coupon-stats">
            <div class="stat-card">
                <h3><?php echo $total; ?></h3>
                <p>Tổng mã giảm giá</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $active; ?></h3>
                <p>Đang hoạt động</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($total_used ?: 0); ?></h3>
                <p>Lượt sử dụng</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($total_discount ?: 0); ?>đ</h3>
                <p>Tổng giảm giá</p>
            </div>
        </div>
        
        <?php if (empty($coupons)): ?>
        <div class="no-coupons">
            <span class="dashicons dashicons-tickets-alt"></span>
            <h2>Chưa có mã giảm giá nào</h2>
            <p>Tạo mã giảm giá để thu hút khách hàng!</p>
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-coupon-edit'); ?>" class="button button-primary">Tạo mã giảm giá đầu tiên</a>
        </div>
        <?php else: ?>
        <table class="coupon-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Loại</th>
                    <th>Giá trị</th>
                    <th>Đã dùng</th>
                    <th>Giới hạn</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): 
                    $type_labels = array('order' => 'Toàn đơn', 'product' => 'Sản phẩm', 'combo' => 'Combo');
                    $type_classes = array('order' => 'type-order', 'product' => 'type-product', 'combo' => 'type-combo');
                    
                    // Check expired
                    $is_expired = false;
                    if ($coupon->end_datetime && strtotime($coupon->end_datetime) < current_time('timestamp')) {
                        $is_expired = true;
                    }
                    
                    // Check usage limit
                    $is_limit_reached = false;
                    if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
                        $is_limit_reached = true;
                    }
                ?>
                <tr>
                    <td><span class="coupon-code"><?php echo esc_html($coupon->code); ?></span></td>
                    <td><strong><?php echo esc_html($coupon->name); ?></strong></td>
                    <td><span class="coupon-type <?php echo $type_classes[$coupon->type]; ?>"><?php echo $type_labels[$coupon->type]; ?></span></td>
                    <td>
                        <?php if ($coupon->discount_type === 'percent'): ?>
                            <strong><?php echo $coupon->discount_value; ?>%</strong>
                            <?php if ($coupon->max_discount_amount): ?>
                                <br><small>Tối đa: <?php echo number_format($coupon->max_discount_amount); ?>đ</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <strong><?php echo number_format($coupon->discount_value); ?>đ</strong>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $coupon->usage_count; ?></td>
                    <td>
                        <?php if ($coupon->usage_limit): ?>
                            <?php echo $coupon->usage_count; ?>/<?php echo $coupon->usage_limit; ?>
                        <?php else: ?>
                            Không giới hạn
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon->start_datetime || $coupon->end_datetime): ?>
                            <?php if ($coupon->start_datetime): ?>
                                <small>Từ: <?php echo date('d/m/Y H:i', strtotime($coupon->start_datetime)); ?></small><br>
                            <?php endif; ?>
                            <?php if ($coupon->end_datetime): ?>
                                <small>Đến: <?php echo date('d/m/Y H:i', strtotime($coupon->end_datetime)); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            Không giới hạn
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_expired): ?>
                            <span class="coupon-status status-expired">Hết hạn</span>
                        <?php elseif ($is_limit_reached): ?>
                            <span class="coupon-status status-expired">Hết lượt</span>
                        <?php elseif ($coupon->is_active): ?>
                            <span class="coupon-status status-active">Hoạt động</span>
                        <?php else: ?>
                            <span class="coupon-status status-inactive">Tạm dừng</span>
                        <?php endif; ?>
                    </td>
                    <td class="coupon-actions">
                        <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-coupon-edit&coupon_id=' . $coupon->id); ?>">Sửa</a>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=product&page=petshop-coupons&action=toggle&coupon_id=' . $coupon->id), 'toggle_coupon_' . $coupon->id); ?>">
                            <?php echo $coupon->is_active ? 'Tạm dừng' : 'Kích hoạt'; ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=product&page=petshop-coupons&action=delete&coupon_id=' . $coupon->id), 'delete_coupon_' . $coupon->id); ?>" class="delete" onclick="return confirm('Xóa mã giảm giá này?');">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// TRANG THÊM/SỬA MÃ GIẢM GIÁ
// =============================================
function petshop_coupon_edit_page() {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    // Xử lý lưu
    if (isset($_POST['petshop_coupon_nonce']) && wp_verify_nonce($_POST['petshop_coupon_nonce'], 'petshop_save_coupon')) {
        $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
        
        // Xử lý datetime
        $start_datetime = null;
        $end_datetime = null;
        
        if (!empty($_POST['has_time_limit'])) {
            if (!empty($_POST['start_date'])) {
                $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : '00:00';
                $start_datetime = $_POST['start_date'] . ' ' . $start_time . ':00';
            }
            if (!empty($_POST['end_date'])) {
                $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : '23:59';
                $end_datetime = $_POST['end_date'] . ' ' . $end_time . ':00';
            }
        }
        
        $data = array(
            'code' => strtoupper(sanitize_text_field($_POST['code'])),
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'type' => sanitize_text_field($_POST['type']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'min_order_amount' => floatval($_POST['min_order_amount']),
            'max_order_amount' => !empty($_POST['max_order_amount']) ? floatval($_POST['max_order_amount']) : null,
            'max_discount_amount' => !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null,
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'user_limit' => !empty($_POST['user_limit']) ? intval($_POST['user_limit']) : 1,
            'per_order_limit' => !empty($_POST['per_order_limit']) ? intval($_POST['per_order_limit']) : 1,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'coupon_group' => !empty($_POST['coupon_group']) ? sanitize_text_field($_POST['coupon_group']) : null,
            'apply_to_category' => !empty($_POST['apply_to_category']) ? json_encode(array_map('intval', $_POST['apply_to_category'])) : null,
            'exclude_category' => !empty($_POST['exclude_category']) ? json_encode(array_map('intval', $_POST['exclude_category'])) : null,
            'first_order_only' => isset($_POST['first_order_only']) ? 1 : 0,
            'new_user_only' => isset($_POST['new_user_only']) ? 1 : 0,
            'stackable' => isset($_POST['stackable']) ? 1 : 0,
            'display_on_cart' => isset($_POST['display_on_cart']) ? 1 : 0,
            'priority' => !empty($_POST['priority']) ? intval($_POST['priority']) : 10,
        );
        
        if ($coupon_id > 0) {
            $wpdb->update($table_coupons, $data, array('id' => $coupon_id));
        } else {
            $wpdb->insert($table_coupons, $data);
            $coupon_id = $wpdb->insert_id;
        }
        
        // Lưu sản phẩm áp dụng
        $wpdb->delete($wpdb->prefix . 'petshop_coupon_products', array('coupon_id' => $coupon_id));
        if ($_POST['type'] === 'product' && !empty($_POST['product_ids'])) {
            foreach ($_POST['product_ids'] as $product_id) {
                $wpdb->insert($wpdb->prefix . 'petshop_coupon_products', array(
                    'coupon_id' => $coupon_id,
                    'product_id' => intval($product_id)
                ));
            }
        }
        
        // Lưu combo
        $wpdb->delete($wpdb->prefix . 'petshop_coupon_combos', array('coupon_id' => $coupon_id));
        if ($_POST['type'] === 'combo' && !empty($_POST['combo_product_ids'])) {
            // Lưu combo_mode
            $combo_mode = isset($_POST['combo_mode']) ? sanitize_text_field($_POST['combo_mode']) : 'any_triggers';
            update_option('petshop_combo_mode_' . $coupon_id, $combo_mode);
            
            // Xác định main_product_id nếu mode = main_required
            $main_product_id = 0;
            if ($combo_mode === 'main_required' && !empty($_POST['main_product_id'])) {
                $main_product_id = intval($_POST['main_product_id']);
            }
            
            // Lưu các sản phẩm combo
            foreach ($_POST['combo_product_ids'] as $combo_product_id) {
                $wpdb->insert($wpdb->prefix . 'petshop_coupon_combos', array(
                    'coupon_id' => $coupon_id,
                    'main_product_id' => $main_product_id, // 0 nếu any_triggers
                    'combo_product_id' => intval($combo_product_id)
                ));
            }
        }
        
        echo '<div class="notice notice-success"><p>Đã lưu mã giảm giá!</p></div>';
        
        // Redirect để load lại data
        echo '<script>window.location.href = "' . admin_url('edit.php?post_type=product&page=petshop-coupon-edit&coupon_id=' . $coupon_id . '&saved=1') . '";</script>';
        return;
    }
    
    // Lấy data nếu edit
    $coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
    $coupon = null;
    $selected_products = array();
    $combo_main = 0;
    $combo_products = array();
    
    if ($coupon_id > 0) {
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_coupons WHERE id = %d", $coupon_id));
        
        // Lấy sản phẩm đã chọn
        $selected_products = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}petshop_coupon_products WHERE coupon_id = %d",
            $coupon_id
        ));
        
        // Lấy combo
        $combo_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}petshop_coupon_combos WHERE coupon_id = %d",
            $coupon_id
        ));
        if ($combo_data) {
            $combo_main = $combo_data[0]->main_product_id;
            $combo_products = wp_list_pluck($combo_data, 'combo_product_id');
        }
    }
    
    // Lấy tất cả sản phẩm
    $all_products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    // Lấy danh mục sản phẩm
    $product_categories = get_terms(array(
        'taxonomy' => 'product_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    if (is_wp_error($product_categories)) {
        $product_categories = array();
    }
    
    // Lấy combo_mode nếu có
    $combo_mode = 'any_triggers'; // Mặc định: Bất kỳ SP trong combo đều gợi ý
    if ($coupon_id > 0) {
        $saved_mode = get_option('petshop_combo_mode_' . $coupon_id, 'any_triggers');
        $combo_mode = $saved_mode;
    }
    
    ?>
    <style>
    .coupon-edit-wrap { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
    .coupon-form { display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
    @media (max-width: 900px) { .coupon-form { grid-template-columns: 1fr; } }
    .coupon-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; margin-bottom: 20px; }
    .coupon-card-header { padding: 15px; border-bottom: 1px solid #e0e0e0; background: #f6f7f7; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .coupon-card-body { padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group input[type="time"], .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }
    .form-group textarea { min-height: 80px; }
    .form-group .description { color: #646970; font-size: 12px; margin-top: 5px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .type-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .type-option { border: 2px solid #c3c4c7; border-radius: 8px; padding: 15px; cursor: pointer; text-align: center; transition: all 0.2s; }
    .type-option:hover { border-color: #2271b1; }
    .type-option.active { border-color: #2271b1; background: #f0f6fc; }
    .type-option input { display: none; }
    .type-option .dashicons { font-size: 28px; width: 28px; height: 28px; color: #2271b1; margin-bottom: 8px; }
    .type-option h4 { margin: 0 0 5px; }
    .type-option p { margin: 0; font-size: 12px; color: #646970; }
    .product-selector { max-height: 300px; overflow-y: auto; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; background: #fafafa; }
    .product-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 4px; cursor: pointer; background: #fff; margin-bottom: 5px; border: 1px solid #e0e0e0; }
    .product-item:hover { background: #f0f6fc; border-color: #2271b1; }
    .product-item.selected { background: #e6f3ff; border-color: #2271b1; }
    .product-item input { margin: 0; }
    .product-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
    .product-item .info { flex: 1; }
    .product-item .info strong { display: block; font-size: 13px; }
    .product-item .info small { color: #646970; }
    .product-item .category-badge { font-size: 10px; background: #e0e0e0; padding: 2px 6px; border-radius: 3px; color: #555; }
    .time-row { display: grid; grid-template-columns: 1fr 100px; gap: 10px; margin-bottom: 10px; }
    .submit-wrap { padding: 15px; background: #f6f7f7; border-top: 1px solid #e0e0e0; display: flex; gap: 10px; justify-content: flex-end; }
    .conditional-section { display: none; }
    .conditional-section.show { display: block; }
    .code-input-wrap { display: flex; gap: 10px; }
    .code-input-wrap input { flex: 1; text-transform: uppercase; }
    
    /* Product Search & Filter */
    .product-filter-wrap { display: flex; gap: 10px; margin-bottom: 10px; }
    .product-filter-wrap input { flex: 1; }
    .product-filter-wrap select { min-width: 150px; }
    .selected-count { font-size: 12px; color: #2271b1; margin-bottom: 8px; font-weight: 600; }
    
    /* Combo Mode Options */
    .combo-mode-options { display: flex; gap: 15px; margin-bottom: 20px; }
    .combo-mode-option { flex: 1; border: 2px solid #c3c4c7; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.2s; }
    .combo-mode-option:hover { border-color: #2271b1; background: #fafafa; }
    .combo-mode-option.active { border-color: #2271b1; background: #f0f6fc; }
    .combo-mode-option input { display: none; }
    .combo-mode-option h5 { margin: 0 0 5px; display: flex; align-items: center; gap: 8px; }
    .combo-mode-option h5 .dashicons { color: #2271b1; }
    .combo-mode-option p { margin: 0; font-size: 12px; color: #646970; }
    
    /* Main Product Picker */
    .main-product-picker { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; margin-bottom: 15px; }
    .main-product-selected { display: flex; align-items: center; gap: 10px; padding: 10px; background: #e6f3ff; border-radius: 4px; margin-bottom: 10px; }
    .main-product-selected img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
    .main-product-selected .info { flex: 1; }
    .main-product-selected .remove-btn { color: #d63638; cursor: pointer; }
    .no-main-product { padding: 15px; text-align: center; color: #666; background: #f6f7f7; border-radius: 4px; }
    </style>
    
    <div class="coupon-edit-wrap">
        <h1>
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-coupons'); ?>" style="text-decoration:none;color:#666;">
                <span class="dashicons dashicons-arrow-left-alt" style="vertical-align:middle;"></span>
            </a>
            <?php echo $coupon ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá mới'; ?>
        </h1>
        
        <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success"><p>Đã lưu mã giảm giá thành công!</p></div>
        <?php endif; ?>
        
        <form method="post" id="coupon-form">
            <input type="hidden" name="coupon_id" value="<?php echo $coupon_id; ?>">
            <?php wp_nonce_field('petshop_save_coupon', 'petshop_coupon_nonce'); ?>
            
            <div class="coupon-form">
                <!-- CỘT CHÍNH -->
                <div class="main-col">
                    <!-- Thông tin cơ bản -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-info-outline"></span> Thông tin cơ bản
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label>Mã giảm giá <span style="color:#d63638;">*</span></label>
                                <div class="code-input-wrap">
                                    <input type="text" name="code" id="coupon-code" value="<?php echo $coupon ? esc_attr($coupon->code) : ''; ?>" required placeholder="VD: SALE50" style="text-transform:uppercase;">
                                    <button type="button" class="button" id="generate-code-btn">
                                        <span class="dashicons dashicons-randomize" style="vertical-align:middle;"></span> Tạo mã
                                    </button>
                                </div>
                                <p class="description">Mã này khách hàng sẽ nhập khi thanh toán</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Tên mã giảm giá <span style="color:#d63638;">*</span></label>
                                <input type="text" name="name" value="<?php echo $coupon ? esc_attr($coupon->name) : ''; ?>" required placeholder="VD: Giảm 50% Black Friday">
                            </div>
                            
                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea name="description" placeholder="Mô tả chi tiết về chương trình giảm giá..."><?php echo $coupon ? esc_textarea($coupon->description) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loại giảm giá -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-tag"></span> Loại giảm giá
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <div class="type-options">
                                    <label class="type-option <?php echo (!$coupon || $coupon->type === 'order') ? 'active' : ''; ?>">
                                        <input type="radio" name="type" value="order" <?php checked(!$coupon || $coupon->type === 'order'); ?>>
                                        <span class="dashicons dashicons-cart"></span>
                                        <h4>Toàn đơn hàng</h4>
                                        <p>Áp dụng cho tổng đơn</p>
                                    </label>
                                    <label class="type-option <?php echo ($coupon && $coupon->type === 'product') ? 'active' : ''; ?>">
                                        <input type="radio" name="type" value="product" <?php checked($coupon && $coupon->type === 'product'); ?>>
                                        <span class="dashicons dashicons-products"></span>
                                        <h4>Sản phẩm cụ thể</h4>
                                        <p>Chỉ cho sản phẩm đã chọn</p>
                                    </label>
                                    <label class="type-option <?php echo ($coupon && $coupon->type === 'combo') ? 'active' : ''; ?>">
                                        <input type="radio" name="type" value="combo" <?php checked($coupon && $coupon->type === 'combo'); ?>>
                                        <span class="dashicons dashicons-networking"></span>
                                        <h4>Combo</h4>
                                        <p>Mua kèm được giảm giá</p>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Section: Sản phẩm cụ thể -->
                            <div class="conditional-section" id="product-section" <?php echo ($coupon && $coupon->type === 'product') ? 'style="display:block;"' : ''; ?>>
                                <div class="form-group">
                                    <label>Chọn sản phẩm áp dụng</label>
                                    <div class="product-filter-wrap">
                                        <input type="text" id="product-search" placeholder="Tìm kiếm sản phẩm...">
                                        <select id="product-category-filter">
                                            <option value="">-- Tất cả danh mục --</option>
                                            <?php foreach ($product_categories as $cat): ?>
                                            <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="selected-count" id="product-selected-count">Đã chọn: <?php echo count($selected_products); ?> sản phẩm</div>
                                    <div class="product-selector" id="product-list">
                                        <?php foreach ($all_products as $product): 
                                            $thumb = get_the_post_thumbnail_url($product->ID, 'thumbnail') ?: 'https://via.placeholder.com/40';
                                            $price = get_post_meta($product->ID, 'product_price', true);
                                            $cats = wp_get_post_terms($product->ID, 'product_category', array('fields' => 'all'));
                                            $cat_ids = wp_list_pluck($cats, 'term_id');
                                            $cat_names = wp_list_pluck($cats, 'name');
                                            $is_selected = in_array($product->ID, $selected_products);
                                        ?>
                                        <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>" data-categories="<?php echo implode(',', $cat_ids); ?>">
                                            <input type="checkbox" name="product_ids[]" value="<?php echo $product->ID; ?>" <?php checked($is_selected); ?>>
                                            <img src="<?php echo esc_url($thumb); ?>" alt="">
                                            <div class="info">
                                                <strong><?php echo esc_html($product->post_title); ?></strong>
                                                <small><?php echo $price ? number_format($price) . 'đ' : 'Chưa có giá'; ?></small>
                                                <?php if ($cat_names): ?>
                                                <span class="category-badge"><?php echo esc_html(implode(', ', $cat_names)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section: Combo -->
                            <div class="conditional-section" id="combo-section" <?php echo ($coupon && $coupon->type === 'combo') ? 'style="display:block;"' : ''; ?>>
                                <!-- Chọn loại Combo -->
                                <div class="form-group">
                                    <label>Loại Combo</label>
                                    <div class="combo-mode-options">
                                        <label class="combo-mode-option <?php echo $combo_mode === 'any_triggers' ? 'active' : ''; ?>">
                                            <input type="radio" name="combo_mode" value="any_triggers" <?php checked($combo_mode, 'any_triggers'); ?>>
                                            <h5><span class="dashicons dashicons-networking"></span> Mua 1 gợi ý combo</h5>
                                            <p>Mua bất kỳ sản phẩm nào trong combo đều gợi ý mua các sản phẩm còn lại để được giảm giá</p>
                                        </label>
                                        <label class="combo-mode-option <?php echo $combo_mode === 'main_required' ? 'active' : ''; ?>">
                                            <input type="radio" name="combo_mode" value="main_required" <?php checked($combo_mode, 'main_required'); ?>>
                                            <h5><span class="dashicons dashicons-star-filled"></span> Cần sản phẩm chính</h5>
                                            <p>Chỉ khi mua sản phẩm chính mới hiển thị gợi ý mua kèm sản phẩm phụ để được giảm</p>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Sản phẩm chính (chỉ hiện khi mode = main_required) -->
                                <div class="form-group" id="main-product-section" style="<?php echo $combo_mode === 'main_required' ? '' : 'display:none;'; ?>">
                                    <label>Sản phẩm chính <span style="color:#d63638;">*</span></label>
                                    <div class="main-product-picker">
                                        <div class="product-filter-wrap">
                                            <input type="text" id="main-product-search" placeholder="Tìm sản phẩm chính...">
                                            <select id="main-product-category-filter">
                                                <option value="">-- Tất cả danh mục --</option>
                                                <?php foreach ($product_categories as $cat): ?>
                                                <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <?php if ($combo_main > 0): 
                                            $main_product = get_post($combo_main);
                                            $main_thumb = get_the_post_thumbnail_url($combo_main, 'thumbnail') ?: 'https://via.placeholder.com/50';
                                            $main_price = get_post_meta($combo_main, 'product_price', true);
                                        ?>
                                        <div class="main-product-selected" id="main-product-display">
                                            <img src="<?php echo esc_url($main_thumb); ?>" alt="">
                                            <div class="info">
                                                <strong><?php echo esc_html($main_product->post_title); ?></strong>
                                                <small><?php echo $main_price ? number_format($main_price) . 'đ' : ''; ?></small>
                                            </div>
                                            <span class="remove-btn" onclick="clearMainProduct()">
                                                <span class="dashicons dashicons-dismiss"></span>
                                            </span>
                                        </div>
                                        <?php else: ?>
                                        <div class="no-main-product" id="main-product-display">
                                            <span class="dashicons dashicons-plus-alt2"></span> Chọn sản phẩm chính bên dưới
                                        </div>
                                        <?php endif; ?>
                                        
                                        <input type="hidden" name="main_product_id" id="main-product-id" value="<?php echo $combo_main; ?>">
                                        
                                        <div class="product-selector" id="main-product-list" style="max-height:200px;">
                                            <?php foreach ($all_products as $product): 
                                                $thumb = get_the_post_thumbnail_url($product->ID, 'thumbnail') ?: 'https://via.placeholder.com/40';
                                                $price = get_post_meta($product->ID, 'product_price', true);
                                                $cats = wp_get_post_terms($product->ID, 'product_category', array('fields' => 'all'));
                                                $cat_ids = wp_list_pluck($cats, 'term_id');
                                                $cat_names = wp_list_pluck($cats, 'name');
                                            ?>
                                            <div class="product-item <?php echo $combo_main == $product->ID ? 'selected' : ''; ?>" 
                                                 data-id="<?php echo $product->ID; ?>" 
                                                 data-name="<?php echo esc_attr($product->post_title); ?>"
                                                 data-thumb="<?php echo esc_url($thumb); ?>"
                                                 data-price="<?php echo $price ? number_format($price) . 'đ' : ''; ?>"
                                                 data-categories="<?php echo implode(',', $cat_ids); ?>"
                                                 onclick="selectMainProduct(this)">
                                                <img src="<?php echo esc_url($thumb); ?>" alt="">
                                                <div class="info">
                                                    <strong><?php echo esc_html($product->post_title); ?></strong>
                                                    <small><?php echo $price ? number_format($price) . 'đ' : 'Chưa có giá'; ?></small>
                                                    <?php if ($cat_names): ?>
                                                    <span class="category-badge"><?php echo esc_html(implode(', ', $cat_names)); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <p class="description">Sản phẩm này sẽ hiển thị thông báo "Mua kèm combo được giảm giá"</p>
                                </div>
                                
                                <!-- Sản phẩm trong combo -->
                                <div class="form-group">
                                    <label id="combo-products-label">
                                        <?php echo $combo_mode === 'main_required' ? 'Sản phẩm mua kèm (sản phẩm phụ)' : 'Các sản phẩm trong Combo'; ?>
                                    </label>
                                    <div class="product-filter-wrap">
                                        <input type="text" id="combo-search" placeholder="Tìm kiếm sản phẩm...">
                                        <select id="combo-category-filter">
                                            <option value="">-- Tất cả danh mục --</option>
                                            <?php foreach ($product_categories as $cat): ?>
                                            <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="selected-count" id="combo-selected-count">Đã chọn: <?php echo count($combo_products); ?> sản phẩm</div>
                                    <div class="product-selector" id="combo-product-list">
                                        <?php foreach ($all_products as $product): 
                                            $thumb = get_the_post_thumbnail_url($product->ID, 'thumbnail') ?: 'https://via.placeholder.com/40';
                                            $price = get_post_meta($product->ID, 'product_price', true);
                                            $cats = wp_get_post_terms($product->ID, 'product_category', array('fields' => 'all'));
                                            $cat_ids = wp_list_pluck($cats, 'term_id');
                                            $cat_names = wp_list_pluck($cats, 'name');
                                            $is_selected = in_array($product->ID, $combo_products);
                                        ?>
                                        <label class="product-item <?php echo $is_selected ? 'selected' : ''; ?>" data-categories="<?php echo implode(',', $cat_ids); ?>">
                                            <input type="checkbox" name="combo_product_ids[]" value="<?php echo $product->ID; ?>" <?php checked($is_selected); ?>>
                                            <img src="<?php echo esc_url($thumb); ?>" alt="">
                                            <div class="info">
                                                <strong><?php echo esc_html($product->post_title); ?></strong>
                                                <small><?php echo $price ? number_format($price) . 'đ' : 'Chưa có giá'; ?></small>
                                                <?php if ($cat_names): ?>
                                                <span class="category-badge"><?php echo esc_html(implode(', ', $cat_names)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description" id="combo-products-description">
                                        <?php echo $combo_mode === 'main_required' 
                                            ? 'Khi mua sản phẩm chính cùng các sản phẩm này sẽ được giảm giá' 
                                            : 'Khi mua bất kỳ sản phẩm nào trong danh sách, hệ thống sẽ gợi ý mua thêm các sản phẩm còn lại để được giảm'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Giá trị giảm -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-money-alt"></span> Giá trị giảm
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kiểu giảm</label>
                                    <select name="discount_type" id="discount-type-select">
                                        <option value="percent" <?php selected(!$coupon || $coupon->discount_type === 'percent'); ?>>% Phần trăm</option>
                                        <option value="fixed" <?php selected($coupon && $coupon->discount_type === 'fixed'); ?>>VNĐ Cố định</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Giá trị <span style="color:#d63638;">*</span></label>
                                    <input type="number" name="discount_value" id="discount-value-input" value="<?php echo $coupon ? $coupon->discount_value : ''; ?>" required min="0" step="<?php echo (!$coupon || $coupon->discount_type === 'percent') ? '1' : '1000'; ?>" placeholder="<?php echo (!$coupon || $coupon->discount_type === 'percent') ? 'VD: 20' : 'VD: 50000'; ?>">
                                    <p class="description" id="discount-hint"><?php echo (!$coupon || $coupon->discount_type === 'percent') ? 'Nhập % giảm giá (VD: 20 = giảm 20%)' : 'Nhập số tiền giảm (VD: 50000 = giảm 50.000đ)'; ?></p>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Giá trị đơn tối thiểu</label>
                                    <input type="number" name="min_order_amount" value="<?php echo $coupon ? $coupon->min_order_amount : ''; ?>" min="0" step="1000" placeholder="0">
                                    <p class="description">Đơn hàng phải đạt giá trị này mới áp dụng được</p>
                                </div>
                                <div class="form-group">
                                    <label>Giảm tối đa (nếu giảm %)</label>
                                    <input type="number" name="max_discount_amount" value="<?php echo $coupon ? $coupon->max_discount_amount : ''; ?>" min="0" step="1000" placeholder="Không giới hạn">
                                    <p class="description">Số tiền giảm tối đa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CỘT PHỤ -->
                <div class="side-col">
                    <!-- Trạng thái -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-admin-generic"></span> Trạng thái
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?php checked(!$coupon || $coupon->is_active); ?>>
                                    <span>Kích hoạt mã giảm giá</span>
                                </label>
                            </div>
                        </div>
                        <div class="submit-wrap">
                            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-coupons'); ?>" class="button">Hủy</a>
                            <button type="submit" class="button button-primary">
                                <?php echo $coupon ? 'Cập nhật' : 'Tạo mã giảm giá'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Giới hạn sử dụng -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-lock"></span> Giới hạn
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label>Tổng lượt sử dụng</label>
                                <input type="number" name="usage_limit" value="<?php echo $coupon ? $coupon->usage_limit : ''; ?>" min="0" placeholder="Không giới hạn">
                                <p class="description">Để trống = không giới hạn</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Mỗi khách hàng</label>
                                <input type="number" name="user_limit" value="<?php echo $coupon ? $coupon->user_limit : '1'; ?>" min="1" placeholder="1">
                                <p class="description">Số lần mỗi user được dùng</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thời gian -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-clock"></span> Thời gian hiệu lực
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="has_time_limit" id="has-time-limit" value="1" <?php checked($coupon && ($coupon->start_datetime || $coupon->end_datetime)); ?>>
                                    <span>Giới hạn thời gian</span>
                                </label>
                            </div>
                            
                            <div id="time-limit-section" style="<?php echo ($coupon && ($coupon->start_datetime || $coupon->end_datetime)) ? '' : 'display:none;'; ?>">
                                <div class="form-group">
                                    <label>Bắt đầu</label>
                                    <div class="time-row">
                                        <input type="date" name="start_date" value="<?php echo $coupon && $coupon->start_datetime ? date('Y-m-d', strtotime($coupon->start_datetime)) : ''; ?>">
                                        <input type="time" name="start_time" value="<?php echo $coupon && $coupon->start_datetime ? date('H:i', strtotime($coupon->start_datetime)) : '00:00'; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Kết thúc</label>
                                    <div class="time-row">
                                        <input type="date" name="end_date" value="<?php echo $coupon && $coupon->end_datetime ? date('Y-m-d', strtotime($coupon->end_datetime)) : ''; ?>">
                                        <input type="time" name="end_time" value="<?php echo $coupon && $coupon->end_datetime ? date('H:i', strtotime($coupon->end_datetime)) : '23:59'; ?>">
                                    </div>
                                </div>
                                <p class="description" style="margin-top:-10px;">Để săn Flash Sale, ví dụ: 12:00 - 14:00</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Điều kiện nâng cao -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-filter"></span> Điều kiện nâng cao
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label>Nhóm mã giảm giá</label>
                                <select name="coupon_group">
                                    <option value="">-- Không phân nhóm --</option>
                                    <option value="discount" <?php selected($coupon && $coupon->coupon_group === 'discount'); ?>>Giảm giá</option>
                                    <option value="freeship" <?php selected($coupon && $coupon->coupon_group === 'freeship'); ?>>Freeship</option>
                                    <option value="first_order" <?php selected($coupon && $coupon->coupon_group === 'first_order'); ?>>Đơn đầu tiên</option>
                                    <option value="category" <?php selected($coupon && $coupon->coupon_group === 'category'); ?>>Theo danh mục</option>
                                    <option value="flash_sale" <?php selected($coupon && $coupon->coupon_group === 'flash_sale'); ?>>Flash Sale</option>
                                </select>
                                <p class="description">Phân loại mã để dễ quản lý</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Áp dụng cho danh mục</label>
                                <select name="apply_to_category[]" multiple style="height:100px;">
                                    <?php 
                                    $selected_cats = ($coupon && $coupon->apply_to_category) ? json_decode($coupon->apply_to_category, true) : array();
                                    foreach ($product_categories as $cat): 
                                    ?>
                                    <option value="<?php echo $cat->term_id; ?>" <?php selected(in_array($cat->term_id, $selected_cats)); ?>><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Để trống = tất cả danh mục. Ctrl+click để chọn nhiều</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Giá trị đơn tối đa</label>
                                <input type="number" name="max_order_amount" value="<?php echo $coupon && $coupon->max_order_amount ? $coupon->max_order_amount : ''; ?>" min="0" step="1000" placeholder="Không giới hạn">
                                <p class="description">Đơn hàng phải dưới mức này mới áp dụng</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Độ ưu tiên</label>
                                <input type="number" name="priority" value="<?php echo $coupon ? ($coupon->priority ?: 10) : 10; ?>" min="1" max="100">
                                <p class="description">Số nhỏ = hiện trước. (1-100)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Điều kiện đặc biệt -->
                    <div class="coupon-card">
                        <div class="coupon-card-header">
                            <span class="dashicons dashicons-admin-users"></span> Điều kiện đặc biệt
                        </div>
                        <div class="coupon-card-body">
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="first_order_only" value="1" <?php checked($coupon && $coupon->first_order_only); ?>>
                                    <span>Chỉ đơn hàng đầu tiên</span>
                                </label>
                                <p class="description" style="margin-left:26px;">Chỉ user chưa từng mua hàng mới dùng được</p>
                            </div>
                            
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="new_user_only" value="1" <?php checked($coupon && $coupon->new_user_only); ?>>
                                    <span>Chỉ khách hàng mới</span>
                                </label>
                                <p class="description" style="margin-left:26px;">User đăng ký trong 7 ngày gần nhất</p>
                            </div>
                            
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="stackable" value="1" <?php checked($coupon && $coupon->stackable); ?>>
                                    <span>Có thể kết hợp</span>
                                </label>
                                <p class="description" style="margin-left:26px;">Cho phép dùng chung với mã khác</p>
                            </div>
                            
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:normal;cursor:pointer;">
                                    <input type="checkbox" name="display_on_cart" value="1" <?php checked(!$coupon || $coupon->display_on_cart); ?>>
                                    <span>Hiển thị trên giỏ hàng</span>
                                </label>
                                <p class="description" style="margin-left:26px;">Hiện trong danh sách mã giảm giá khả dụng</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
    // Hàm chọn sản phẩm chính
    function selectMainProduct(el) {
        var id = el.dataset.id;
        var name = el.dataset.name;
        var thumb = el.dataset.thumb;
        var price = el.dataset.price;
        
        // Cập nhật hidden input
        document.getElementById('main-product-id').value = id;
        
        // Cập nhật hiển thị
        var display = document.getElementById('main-product-display');
        display.className = 'main-product-selected';
        display.innerHTML = '<img src="' + thumb + '" alt="">' +
            '<div class="info"><strong>' + name + '</strong><small>' + price + '</small></div>' +
            '<span class="remove-btn" onclick="clearMainProduct()"><span class="dashicons dashicons-dismiss"></span></span>';
        
        // Highlight item
        document.querySelectorAll('#main-product-list .product-item').forEach(function(item) {
            item.classList.remove('selected');
        });
        el.classList.add('selected');
    }
    
    // Hàm xóa sản phẩm chính
    function clearMainProduct() {
        document.getElementById('main-product-id').value = '';
        var display = document.getElementById('main-product-display');
        display.className = 'no-main-product';
        display.innerHTML = '<span class="dashicons dashicons-plus-alt2"></span> Chọn sản phẩm chính bên dưới';
        
        document.querySelectorAll('#main-product-list .product-item').forEach(function(item) {
            item.classList.remove('selected');
        });
    }
    
    // Hàm filter sản phẩm theo search và category
    function filterProducts(listId, searchVal, categoryVal) {
        var search = searchVal.toLowerCase();
        document.querySelectorAll('#' + listId + ' .product-item').forEach(function(item) {
            var name = item.querySelector('strong').textContent.toLowerCase();
            var cats = item.dataset.categories ? item.dataset.categories.split(',') : [];
            
            var matchSearch = !search || name.includes(search);
            var matchCat = !categoryVal || cats.includes(categoryVal);
            
            item.style.display = (matchSearch && matchCat) ? '' : 'none';
        });
    }
    
    // Cập nhật số lượng đã chọn
    function updateSelectedCount(listId, countId) {
        var count = document.querySelectorAll('#' + listId + ' input[type="checkbox"]:checked').length;
        document.getElementById(countId).textContent = 'Đã chọn: ' + count + ' sản phẩm';
    }
    
    jQuery(document).ready(function($) {
        // Toggle type options
        $('.type-option').on('click', function() {
            $('.type-option').removeClass('active');
            $(this).addClass('active');
            
            var type = $(this).find('input').val();
            $('.conditional-section').hide();
            if (type === 'product') {
                $('#product-section').show();
            } else if (type === 'combo') {
                $('#combo-section').show();
            }
        });
        
        // Toggle time limit
        $('#has-time-limit').on('change', function() {
            $('#time-limit-section').toggle(this.checked);
        });
        
        // Generate random code
        $('#generate-code-btn').on('click', function() {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var code = '';
            for (var i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            $('#coupon-code').val(code);
        });
        
        // ===== DISCOUNT TYPE CHANGE =====
        $('#discount-type-select').on('change', function() {
            var type = $(this).val();
            var $input = $('#discount-value-input');
            var $hint = $('#discount-hint');
            
            if (type === 'percent') {
                $input.attr('step', '1').attr('placeholder', 'VD: 20');
                $hint.text('Nhập % giảm giá (VD: 20 = giảm 20%)');
            } else {
                $input.attr('step', '1000').attr('placeholder', 'VD: 50000');
                $hint.text('Nhập số tiền giảm (VD: 50000 = giảm 50.000đ)');
            }
        });
        
        // ===== COMBO MODE TOGGLE =====
        $('.combo-mode-option').on('click', function() {
            $('.combo-mode-option').removeClass('active');
            $(this).addClass('active');
            
            var mode = $(this).find('input').val();
            
            if (mode === 'main_required') {
                $('#main-product-section').show();
                $('#combo-products-label').text('Sản phẩm mua kèm (sản phẩm phụ)');
                $('#combo-products-description').text('Khi mua sản phẩm chính cùng các sản phẩm này sẽ được giảm giá');
            } else {
                $('#main-product-section').hide();
                $('#combo-products-label').text('Các sản phẩm trong Combo');
                $('#combo-products-description').text('Khi mua bất kỳ sản phẩm nào trong danh sách, hệ thống sẽ gợi ý mua thêm các sản phẩm còn lại để được giảm');
            }
        });
        
        // ===== PRODUCT SEARCH & FILTER =====
        // Section: Sản phẩm cụ thể
        $('#product-search').on('input', function() {
            filterProducts('product-list', $(this).val(), $('#product-category-filter').val());
        });
        $('#product-category-filter').on('change', function() {
            filterProducts('product-list', $('#product-search').val(), $(this).val());
        });
        $('#product-list input[type="checkbox"]').on('change', function() {
            $(this).closest('.product-item').toggleClass('selected', this.checked);
            updateSelectedCount('product-list', 'product-selected-count');
        });
        
        // Section: Main Product (Sản phẩm chính)
        $('#main-product-search').on('input', function() {
            filterProducts('main-product-list', $(this).val(), $('#main-product-category-filter').val());
        });
        $('#main-product-category-filter').on('change', function() {
            filterProducts('main-product-list', $('#main-product-search').val(), $(this).val());
        });
        
        // Section: Combo products
        $('#combo-search').on('input', function() {
            filterProducts('combo-product-list', $(this).val(), $('#combo-category-filter').val());
        });
        $('#combo-category-filter').on('change', function() {
            filterProducts('combo-product-list', $('#combo-search').val(), $(this).val());
        });
        $('#combo-product-list input[type="checkbox"]').on('change', function() {
            $(this).closest('.product-item').toggleClass('selected', this.checked);
            updateSelectedCount('combo-product-list', 'combo-selected-count');
        });
    });
    </script>
    <?php
}

// =============================================
// API: KIỂM TRA VÀ ÁP DỤNG MÃ GIẢM GIÁ
// =============================================
function petshop_validate_coupon($code, $cart_items = array(), $user_id = 0) {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    $coupon = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_coupons WHERE code = %s AND is_active = 1",
        strtoupper($code)
    ));
    
    if (!$coupon) {
        return array('valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hiệu lực');
    }
    
    // Kiểm tra thời gian
    $now = current_time('mysql');
    if ($coupon->start_datetime && $now < $coupon->start_datetime) {
        return array('valid' => false, 'message' => 'Mã giảm giá chưa đến thời gian sử dụng');
    }
    if ($coupon->end_datetime && $now > $coupon->end_datetime) {
        return array('valid' => false, 'message' => 'Mã giảm giá đã hết hạn');
    }
    
    // Kiểm tra giới hạn tổng
    if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
        return array('valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng');
    }
    
    // Kiểm tra giới hạn user
    if ($user_id > 0 && $coupon->user_limit) {
        $user_usage = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}petshop_coupon_usage WHERE coupon_id = %d AND user_id = %d",
            $coupon->id, $user_id
        ));
        if ($user_usage >= $coupon->user_limit) {
            return array('valid' => false, 'message' => 'Bạn đã sử dụng hết lượt của mã này');
        }
    }
    
    // Tính tổng đơn hàng
    $cart_total = 0;
    $applicable_total = 0;
    $cart_product_ids = array();
    
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $cart_total += $item_total;
        $cart_product_ids[] = $item['id'];
    }
    
    // Kiểm tra đơn tối thiểu
    if ($coupon->min_order_amount > 0 && $cart_total < $coupon->min_order_amount) {
        return array(
            'valid' => false, 
            'message' => 'Đơn hàng cần tối thiểu ' . number_format($coupon->min_order_amount) . 'đ để sử dụng mã này'
        );
    }
    
    // Xử lý theo loại coupon
    $discount = 0;
    
    if ($coupon->type === 'order') {
        // Giảm toàn đơn
        $applicable_total = $cart_total;
    } elseif ($coupon->type === 'product') {
        // Giảm sản phẩm cụ thể
        $coupon_products = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}petshop_coupon_products WHERE coupon_id = %d",
            $coupon->id
        ));
        
        foreach ($cart_items as $item) {
            if (in_array($item['id'], $coupon_products)) {
                $applicable_total += $item['price'] * $item['quantity'];
            }
        }
        
        if ($applicable_total == 0) {
            return array('valid' => false, 'message' => 'Mã này chỉ áp dụng cho một số sản phẩm nhất định');
        }
    } elseif ($coupon->type === 'combo') {
        // Kiểm tra combo
        $combo_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}petshop_coupon_combos WHERE coupon_id = %d",
            $coupon->id
        ));
        
        if ($combo_data) {
            $main_product = $combo_data[0]->main_product_id;
            $combo_products = wp_list_pluck($combo_data, 'combo_product_id');
            
            $has_main = in_array($main_product, $cart_product_ids);
            $has_combo = count(array_intersect($combo_products, $cart_product_ids)) > 0;
            
            if (!$has_main || !$has_combo) {
                return array('valid' => false, 'message' => 'Mã này chỉ áp dụng khi mua combo sản phẩm');
            }
            
            // Tính tổng combo
            foreach ($cart_items as $item) {
                if ($item['id'] == $main_product || in_array($item['id'], $combo_products)) {
                    $applicable_total += $item['price'] * $item['quantity'];
                }
            }
        }
    }
    
    // Kiểm tra xem coupon có phải là freeship không
    $is_freeship = ($coupon->coupon_group === 'freeship');
    
    // Tính số tiền giảm
    $discount = 0;
    $shipping_discount = 0;
    
    if ($is_freeship) {
        // Coupon freeship - không giảm giá sản phẩm, chỉ miễn phí ship
        // Lấy phí ship từ settings
        $shipping_settings = get_option('petshop_shipping_settings', array('shipping_fee' => 30000));
        $shipping_discount = floatval($shipping_settings['shipping_fee']);
        $message = $coupon->name . ' - Miễn phí vận chuyển';
    } else {
        // Coupon giảm giá sản phẩm
        if ($coupon->discount_type === 'percent') {
            $discount = $applicable_total * ($coupon->discount_value / 100);
            if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                $discount = $coupon->max_discount_amount;
            }
        } else {
            $discount = $coupon->discount_value;
        }
        
        $discount = min($discount, $applicable_total); // Không giảm quá tổng sản phẩm
        $message = $coupon->name . ' - Giảm ' . number_format($discount) . 'đ';
    }
    
    return array(
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $discount,
        'shipping_discount' => $shipping_discount,
        'is_freeship' => $is_freeship,
        'message' => $message
    );
}

// AJAX endpoint
function petshop_ajax_validate_coupon() {
    $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $cart_items_raw = isset($_POST['cart_items']) ? $_POST['cart_items'] : array();
    $user_id = get_current_user_id();
    
    // Parse JSON nếu cart_items là string
    if (is_string($cart_items_raw)) {
        $cart_items = json_decode(stripslashes($cart_items_raw), true);
        if (!$cart_items) {
            $cart_items = array();
        }
    } else {
        $cart_items = $cart_items_raw;
    }
    
    // Ensure cart_items has proper structure
    $normalized_items = array();
    foreach ($cart_items as $item) {
        if (isset($item['id']) && isset($item['price']) && isset($item['quantity'])) {
            $normalized_items[] = array(
                'id' => intval($item['id']),
                'price' => floatval($item['price']),
                'quantity' => intval($item['quantity'])
            );
        }
    }
    
    $result = petshop_validate_coupon($code, $normalized_items, $user_id);
    
    if ($result['valid']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_petshop_validate_coupon', 'petshop_ajax_validate_coupon');
add_action('wp_ajax_nopriv_petshop_validate_coupon', 'petshop_ajax_validate_coupon');

// =============================================
// LẤY COMBO CHO SẢN PHẨM
// =============================================
function petshop_get_product_combo($product_id) {
    global $wpdb;
    
    $combo = null;
    $coupon_id = 0;
    $combo_mode = 'any_triggers';
    
    // Thử tìm combo có sản phẩm chính (mode = main_required)
    $main_combo = $wpdb->get_row($wpdb->prepare("
        SELECT c.*, cp.coupon_id as combo_coupon_id
        FROM {$wpdb->prefix}petshop_coupons c
        INNER JOIN {$wpdb->prefix}petshop_coupon_combos cp ON c.id = cp.coupon_id
        WHERE cp.main_product_id = %d AND cp.main_product_id > 0 
        AND c.is_active = 1 AND c.type = 'combo'
        AND (c.end_datetime IS NULL OR c.end_datetime > NOW())
        AND (c.start_datetime IS NULL OR c.start_datetime <= NOW())
        LIMIT 1
    ", $product_id));
    
    if ($main_combo) {
        $combo = $main_combo;
        $coupon_id = $main_combo->combo_coupon_id;
        $combo_mode = 'main_required';
    } else {
        // Thử tìm combo không có sản phẩm chính mà sản phẩm này nằm trong combo (mode = any_triggers)
        $any_combo = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, cp.coupon_id as combo_coupon_id
            FROM {$wpdb->prefix}petshop_coupons c
            INNER JOIN {$wpdb->prefix}petshop_coupon_combos cp ON c.id = cp.coupon_id
            WHERE cp.combo_product_id = %d AND (cp.main_product_id = 0 OR cp.main_product_id IS NULL)
            AND c.is_active = 1 AND c.type = 'combo'
            AND (c.end_datetime IS NULL OR c.end_datetime > NOW())
            AND (c.start_datetime IS NULL OR c.start_datetime <= NOW())
            LIMIT 1
        ", $product_id));
        
        if ($any_combo) {
            $combo = $any_combo;
            $coupon_id = $any_combo->combo_coupon_id;
            $combo_mode = 'any_triggers';
        }
    }
    
    if (!$combo) {
        return null;
    }
    
    // Lấy combo_mode từ option (nếu có)
    $saved_mode = get_option('petshop_combo_mode_' . $coupon_id, $combo_mode);
    
    // Lấy các sản phẩm trong combo
    if ($saved_mode === 'main_required') {
        // Chỉ lấy sản phẩm phụ (không phải sản phẩm chính)
        $combo_products = $wpdb->get_results($wpdb->prepare("
            SELECT cp.combo_product_id, p.post_title, pm.meta_value as price, pm2.meta_value as sale_price
            FROM {$wpdb->prefix}petshop_coupon_combos cp
            INNER JOIN {$wpdb->posts} p ON cp.combo_product_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'product_price'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'product_sale_price'
            WHERE cp.coupon_id = %d
        ", $coupon_id));
    } else {
        // Mode any_triggers: lấy tất cả sản phẩm khác trong combo (trừ sản phẩm hiện tại)
        $combo_products = $wpdb->get_results($wpdb->prepare("
            SELECT cp.combo_product_id, p.post_title, pm.meta_value as price, pm2.meta_value as sale_price
            FROM {$wpdb->prefix}petshop_coupon_combos cp
            INNER JOIN {$wpdb->posts} p ON cp.combo_product_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'product_price'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'product_sale_price'
            WHERE cp.coupon_id = %d AND cp.combo_product_id != %d
        ", $coupon_id, $product_id));
    }
    
    // Lấy thông tin coupon
    $coupon = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}petshop_coupons WHERE id = %d",
        $coupon_id
    ));
    
    return array(
        'coupon' => $coupon,
        'products' => $combo_products,
        'combo_mode' => $saved_mode,
        'current_product_id' => $product_id
    );
}

// AJAX endpoint lấy combo
function petshop_ajax_get_product_combo() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product ID'));
    }
    
    $combo = petshop_get_product_combo($product_id);
    
    if ($combo) {
        // Thêm thông tin ảnh và URL cho các sản phẩm combo
        foreach ($combo['products'] as &$product) {
            $product->image = get_the_post_thumbnail_url($product->combo_product_id, 'petshop-product') ?: '';
            $product->url = get_permalink($product->combo_product_id);
            $product->display_price = $product->sale_price ?: $product->price;
        }
        wp_send_json_success($combo);
    } else {
        wp_send_json_error(array('message' => 'No combo found'));
    }
}
add_action('wp_ajax_petshop_get_product_combo', 'petshop_ajax_get_product_combo');
add_action('wp_ajax_nopriv_petshop_get_product_combo', 'petshop_ajax_get_product_combo');

// =============================================
// GHI NHẬN SỬ DỤNG COUPON
// =============================================
function petshop_record_coupon_usage($coupon_id, $order_id = null, $discount_amount = 0) {
    global $wpdb;
    
    $wpdb->insert($wpdb->prefix . 'petshop_coupon_usage', array(
        'coupon_id' => $coupon_id,
        'user_id' => get_current_user_id(),
        'order_id' => $order_id,
        'discount_amount' => $discount_amount
    ));
    
    // Tăng usage_count
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}petshop_coupons SET usage_count = usage_count + 1 WHERE id = %d",
        $coupon_id
    ));
}
// =============================================
// AJAX: LẤY THÔNG TIN COMBO PRODUCTS
// =============================================
function petshop_ajax_get_combo_products_info() {
    $product_ids = isset($_POST['product_ids']) ? json_decode(stripslashes($_POST['product_ids']), true) : array();
    
    if (empty($product_ids)) {
        wp_send_json_error(array('message' => 'No product IDs'));
        return;
    }
    
    $products = array();
    foreach ($product_ids as $product_id) {
        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') continue;
        
        $price = get_post_meta($product_id, 'product_price', true);
        $sale_price = get_post_meta($product_id, 'product_sale_price', true);
        $stock = get_post_meta($product_id, 'product_stock', true);
        $sku = get_post_meta($product_id, 'product_sku', true);
        
        // Check if discount is valid
        $display_price = $price;
        if (function_exists('petshop_is_discount_valid') && petshop_is_discount_valid($product_id)) {
            $display_price = $sale_price ?: $price;
        }
        
        $cats = get_the_terms($product_id, 'product_category');
        $category = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';
        
        $products[] = array(
            'id' => $product_id,
            'name' => $post->post_title,
            'price' => floatval($display_price),
            'original_price' => floatval($price),
            'image' => get_the_post_thumbnail_url($product_id, 'petshop-product') ?: '',
            'url' => get_permalink($product_id),
            'sku' => $sku,
            'category' => $category,
            'stock' => $stock !== '' ? intval($stock) : 999
        );
    }
    
    wp_send_json_success(array('products' => $products));
}
add_action('wp_ajax_petshop_get_combo_products_info', 'petshop_ajax_get_combo_products_info');
add_action('wp_ajax_nopriv_petshop_get_combo_products_info', 'petshop_ajax_get_combo_products_info');

// =============================================
// API: LẤY DANH SÁCH MÃ GIẢM GIÁ KHẢ DỤNG (Style Shopee)
// =============================================
function petshop_get_available_coupons($cart_items = array(), $user_id = 0) {
    global $wpdb;
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    
    $now = current_time('mysql');
    
    // Lấy tất cả coupon đang active và hiển thị trên giỏ hàng
    $coupons = $wpdb->get_results("
        SELECT * FROM $table_coupons 
        WHERE is_active = 1 
        AND display_on_cart = 1
        AND (start_datetime IS NULL OR start_datetime <= '$now')
        AND (end_datetime IS NULL OR end_datetime > '$now')
        AND (usage_limit IS NULL OR usage_count < usage_limit)
        ORDER BY priority ASC, discount_value DESC
    ");
    
    if (empty($coupons)) {
        return array('available' => array(), 'unavailable' => array());
    }
    
    // Tính tổng giỏ hàng
    $cart_total = 0;
    $cart_product_ids = array();
    $cart_categories = array();
    
    foreach ($cart_items as $item) {
        $cart_total += floatval($item['price']) * intval($item['quantity']);
        $cart_product_ids[] = intval($item['id']);
        
        // Lấy danh mục của sản phẩm
        $cats = wp_get_post_terms(intval($item['id']), 'product_category', array('fields' => 'ids'));
        if (!is_wp_error($cats)) {
            $cart_categories = array_merge($cart_categories, $cats);
        }
    }
    $cart_categories = array_unique($cart_categories);
    
    // Kiểm tra đơn hàng đầu tiên
    $is_first_order = true;
    if ($user_id > 0) {
        // Check nếu user đã có đơn hàng trước đó (từ wp_posts với post_type = 'shop_order' hoặc custom)
        $order_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}petshop_coupon_usage WHERE user_id = %d",
            $user_id
        ));
        $is_first_order = ($order_count == 0);
    }
    
    $available = array();
    $unavailable = array();
    
    foreach ($coupons as $coupon) {
        $coupon_info = array(
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'description' => $coupon->description,
            'type' => $coupon->type,
            'discount_type' => $coupon->discount_type,
            'discount_value' => floatval($coupon->discount_value),
            'min_order_amount' => floatval($coupon->min_order_amount),
            'max_order_amount' => floatval($coupon->max_order_amount),
            'max_discount_amount' => floatval($coupon->max_discount_amount),
            'coupon_group' => $coupon->coupon_group,
            'end_datetime' => $coupon->end_datetime,
            'can_use' => true,
            'reason' => ''
        );
        
        // Kiểm tra điều kiện sử dụng
        $can_use = true;
        $reason = '';
        
        // 1. Kiểm tra đơn tối thiểu
        if ($coupon->min_order_amount > 0 && $cart_total < $coupon->min_order_amount) {
            $can_use = false;
            $remaining = $coupon->min_order_amount - $cart_total;
            $reason = 'Mua thêm ' . number_format($remaining) . 'đ để sử dụng';
        }
        
        // 2. Kiểm tra đơn tối đa (nếu có)
        if ($can_use && $coupon->max_order_amount > 0 && $cart_total > $coupon->max_order_amount) {
            $can_use = false;
            $reason = 'Chỉ áp dụng cho đơn dưới ' . number_format($coupon->max_order_amount) . 'đ';
        }
        
        // 3. Kiểm tra đơn đầu tiên
        if ($can_use && $coupon->first_order_only && !$is_first_order) {
            $can_use = false;
            $reason = 'Chỉ dành cho đơn hàng đầu tiên';
        }
        
        // 4. Kiểm tra user mới
        if ($can_use && $coupon->new_user_only && $user_id > 0) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $registered = strtotime($user->user_registered);
                $seven_days_ago = strtotime('-7 days');
                if ($registered < $seven_days_ago) {
                    $can_use = false;
                    $reason = 'Chỉ dành cho khách hàng mới';
                }
            }
        }
        
        // 5. Kiểm tra giới hạn user
        if ($can_use && $user_id > 0 && $coupon->user_limit > 0) {
            $user_usage = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}petshop_coupon_usage WHERE coupon_id = %d AND user_id = %d",
                $coupon->id, $user_id
            ));
            if ($user_usage >= $coupon->user_limit) {
                $can_use = false;
                $reason = 'Bạn đã dùng hết lượt của mã này';
            }
        }
        
        // 6. Kiểm tra loại coupon product (phải có sản phẩm trong giỏ)
        if ($can_use && $coupon->type === 'product') {
            $coupon_products = $wpdb->get_col($wpdb->prepare(
                "SELECT product_id FROM {$wpdb->prefix}petshop_coupon_products WHERE coupon_id = %d",
                $coupon->id
            ));
            $matching = array_intersect($cart_product_ids, $coupon_products);
            if (empty($matching)) {
                $can_use = false;
                $reason = 'Mã này chỉ áp dụng cho một số sản phẩm';
            }
        }
        
        // 7. Kiểm tra danh mục áp dụng
        if ($can_use && !empty($coupon->apply_to_category)) {
            $apply_cats = json_decode($coupon->apply_to_category, true);
            if (is_array($apply_cats) && !empty($apply_cats)) {
                $matching_cats = array_intersect($cart_categories, $apply_cats);
                if (empty($matching_cats)) {
                    $can_use = false;
                    // Lấy tên danh mục
                    $cat_names = array();
                    foreach ($apply_cats as $cat_id) {
                        $term = get_term($cat_id, 'product_category');
                        if ($term && !is_wp_error($term)) {
                            $cat_names[] = $term->name;
                        }
                    }
                    $reason = 'Chỉ áp dụng cho: ' . implode(', ', $cat_names);
                }
            }
        }
        
        // 8. Kiểm tra combo (phải có đủ sản phẩm)
        if ($can_use && $coupon->type === 'combo') {
            $combo_data = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}petshop_coupon_combos WHERE coupon_id = %d",
                $coupon->id
            ));
            
            if ($combo_data) {
                $main_product = $combo_data[0]->main_product_id;
                $combo_products = wp_list_pluck($combo_data, 'combo_product_id');
                $combo_mode = get_option('petshop_combo_mode_' . $coupon->id, 'any_triggers');
                
                if ($combo_mode === 'main_required') {
                    // Phải có sản phẩm chính và ít nhất 1 sản phẩm combo
                    $has_main = in_array($main_product, $cart_product_ids);
                    $has_combo = count(array_intersect($combo_products, $cart_product_ids)) > 0;
                    
                    if (!$has_main || !$has_combo) {
                        $can_use = false;
                        $main_post = get_post($main_product);
                        $reason = 'Cần mua kèm "' . ($main_post ? $main_post->post_title : '') . '" và sản phẩm combo';
                    }
                } else {
                    // any_triggers: cần ít nhất 2 sản phẩm trong combo
                    $all_combo = array_merge($combo_products);
                    $matching_combo = array_intersect($all_combo, $cart_product_ids);
                    if (count($matching_combo) < 2) {
                        $can_use = false;
                        $reason = 'Cần mua từ 2 sản phẩm combo trở lên';
                    }
                }
            }
        }
        
        // Tính discount ước tính
        $estimated_discount = 0;
        if ($can_use) {
            if ($coupon->discount_type === 'percent') {
                $estimated_discount = $cart_total * ($coupon->discount_value / 100);
                if ($coupon->max_discount_amount && $estimated_discount > $coupon->max_discount_amount) {
                    $estimated_discount = $coupon->max_discount_amount;
                }
            } else {
                $estimated_discount = $coupon->discount_value;
            }
            $estimated_discount = min($estimated_discount, $cart_total);
        }
        
        $coupon_info['can_use'] = $can_use;
        $coupon_info['reason'] = $reason;
        $coupon_info['estimated_discount'] = $estimated_discount;
        
        if ($can_use) {
            $available[] = $coupon_info;
        } else {
            $unavailable[] = $coupon_info;
        }
    }
    
    // Sắp xếp available theo estimated_discount giảm dần
    usort($available, function($a, $b) {
        return $b['estimated_discount'] - $a['estimated_discount'];
    });
    
    return array(
        'available' => $available,
        'unavailable' => $unavailable,
        'cart_total' => $cart_total
    );
}

// AJAX endpoint: Lấy danh sách coupon khả dụng
function petshop_ajax_get_available_coupons() {
    $cart_items_raw = isset($_POST['cart_items']) ? $_POST['cart_items'] : array();
    $user_id = get_current_user_id();
    
    // Parse JSON nếu cart_items là string
    if (is_string($cart_items_raw)) {
        $cart_items = json_decode(stripslashes($cart_items_raw), true);
        if (!$cart_items) {
            $cart_items = array();
        }
    } else {
        $cart_items = $cart_items_raw;
    }
    
    // Normalize items
    $normalized_items = array();
    foreach ($cart_items as $item) {
        if (isset($item['id']) && isset($item['price']) && isset($item['quantity'])) {
            $normalized_items[] = array(
                'id' => intval($item['id']),
                'price' => floatval($item['price']),
                'quantity' => intval($item['quantity'])
            );
        }
    }
    
    $result = petshop_get_available_coupons($normalized_items, $user_id);
    
    wp_send_json_success($result);
}
add_action('wp_ajax_petshop_get_available_coupons', 'petshop_ajax_get_available_coupons');
add_action('wp_ajax_nopriv_petshop_get_available_coupons', 'petshop_ajax_get_available_coupons');
add_action('wp_ajax_petshop_get_combo_products_info', 'petshop_ajax_get_combo_products_info');
add_action('wp_ajax_nopriv_petshop_get_combo_products_info', 'petshop_ajax_get_combo_products_info');