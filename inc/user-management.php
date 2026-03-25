<?php
/**
 * User Management - Hệ thống phân quyền PetShop
 * 
 * Roles: Administrator > Manager > Staff > Customer
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================
// XÓA CÁC ROLE KHÔNG CẦN THIẾT
// =============================================
function petshop_remove_unused_roles() {
    // Các role cần xóa
    $roles_to_remove = array(
        'editor',
        'author', 
        'contributor',
        'subscriber',
        'shop_manager',  // WooCommerce
        'customer',      // WooCommerce (sẽ tạo lại)
    );
    
    foreach ($roles_to_remove as $role) {
        if (get_role($role)) {
            remove_role($role);
        }
    }
}

// =============================================
// TẠO CÁC ROLE CỦA PETSHOP
// =============================================
function petshop_create_custom_roles() {
    // Xóa role cũ nếu có để tạo lại
    remove_role('petshop_manager');
    remove_role('petshop_staff');
    remove_role('petshop_customer');
    
    // ===================
    // ROLE: MANAGER
    // ===================
    add_role('petshop_manager', 'Quản lý', array(
        // WordPress cơ bản
        'read' => true,
        'upload_files' => true,
        
        // Quản lý bài viết
        'edit_posts' => true,
        'edit_others_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'delete_published_posts' => true,
        
        // Quản lý trang
        'edit_pages' => true,
        'edit_others_pages' => true,
        'edit_published_pages' => true,
        'publish_pages' => true,
        'delete_pages' => true,
        'delete_others_pages' => true,
        'delete_published_pages' => true,
        
        // Quản lý danh mục
        'manage_categories' => true,
        
        // Quản lý comments
        'moderate_comments' => true,
        
        // Quản lý người dùng (chỉ staff và customer)
        'list_users' => true,
        'create_users' => true,
        'edit_users' => true,
        
        // KHÔNG CÓ các quyền sau (chỉ Admin có):
        // - delete_users
        // - promote_users (thay đổi role)
        // - edit_theme_options
        // - switch_themes
        // - edit_themes
        // - install_plugins
        // - activate_plugins
        // - update_plugins
        // - delete_plugins
        // - manage_options
        // - export
        // - import
        
        // Quyền PetShop
        'manage_petshop' => true,
        'manage_petshop_orders' => true,
        'view_petshop_orders' => true,
        'edit_petshop_orders' => true,
        'delete_petshop_orders' => true,
        'manage_petshop_products' => true,
        'manage_petshop_inventory' => true,
        'manage_petshop_coupons' => true,
        'view_petshop_reports' => true,
        'manage_petshop_staff' => true,
    ));
    
    // ===================
    // ROLE: STAFF
    // ===================
    add_role('petshop_staff', 'Nhân viên', array(
        // Chỉ có quyền đọc cơ bản
        // Các quyền khác sẽ được cấp riêng cho từng người
        'read' => true,
        'upload_files' => true,
        
        // Bài viết: chỉ tạo và sửa của mình, KHÔNG publish, KHÔNG xóa
        'edit_posts' => true,
        // Không có: publish_posts, delete_posts, edit_others_posts
    ));
    
    // ===================
    // ROLE: CUSTOMER
    // ===================
    add_role('petshop_customer', 'Khách hàng', array(
        'read' => true,
        // Không có quyền gì khác
    ));
}

// =============================================
// KHỞI TẠO HỆ THỐNG
// =============================================
function petshop_init_roles() {
    // Chỉ chạy 1 lần khi kích hoạt theme hoặc khi cần reset
    $roles_version = get_option('petshop_roles_version', '0');
    
    if ($roles_version !== '2.0') {
        petshop_remove_unused_roles();
        petshop_create_custom_roles();
        petshop_add_admin_capabilities();
        update_option('petshop_roles_version', '2.0');
    }
}
add_action('admin_init', 'petshop_init_roles');
add_action('after_switch_theme', 'petshop_init_roles');

// Đảm bảo Admin có đầy đủ quyền PetShop
function petshop_add_admin_capabilities() {
    $admin = get_role('administrator');
    if ($admin) {
        $caps = array(
            'manage_petshop',
            'manage_petshop_orders',
            'view_petshop_orders',
            'edit_petshop_orders',
            'delete_petshop_orders',
            'manage_petshop_products',
            'manage_petshop_inventory',
            'manage_petshop_coupons',
            'view_petshop_reports',
            'manage_petshop_staff',
            'manage_petshop_users',
            'manage_petshop_settings',
        );
        
        foreach ($caps as $cap) {
            $admin->add_cap($cap);
        }
    }
}

// =============================================
// ĐỊNH NGHĨA CÁC QUYỀN STAFF CÓ THỂ ĐƯỢC CẤP
// =============================================
function petshop_get_staff_permissions() {
    return array(
        'posts' => array(
            'label' => 'Quản lý bài viết',
            'description' => 'Tạo và chỉnh sửa bài viết. Bài viết cần Admin/Manager duyệt trước khi đăng.',
            'icon' => 'dashicons-admin-post',
            'permissions' => array(
                'edit_posts' => true,
                'edit_published_posts' => true,
            )
        ),
        'orders' => array(
            'label' => 'Quản lý đơn hàng',
            'description' => 'Xem danh sách đơn hàng, theo dõi và cập nhật trạng thái đơn hàng.',
            'icon' => 'dashicons-cart',
            'permissions' => array(
                'view_petshop_orders' => true,
                'edit_petshop_orders' => true,
            )
        ),
        'products' => array(
            'label' => 'Quản lý sản phẩm',
            'description' => 'Xem, thêm mới và chỉnh sửa thông tin sản phẩm.',
            'icon' => 'dashicons-archive',
            'permissions' => array(
                'manage_petshop_products' => true,
            )
        ),
        'inventory' => array(
            'label' => 'Quản lý tồn kho',
            'description' => 'Xem số lượng tồn kho và cập nhật khi nhập/xuất hàng.',
            'icon' => 'dashicons-clipboard',
            'permissions' => array(
                'manage_petshop_inventory' => true,
            )
        ),
        'coupons' => array(
            'label' => 'Xem mã giảm giá',
            'description' => 'Chỉ được xem danh sách mã giảm giá, không được tạo/sửa/xóa.',
            'icon' => 'dashicons-tickets-alt',
            'permissions' => array(
                'view_petshop_coupons' => true,
            )
        ),
        'reports' => array(
            'label' => 'Xem báo cáo',
            'description' => 'Xem báo cáo doanh thu và thống kê cửa hàng.',
            'icon' => 'dashicons-chart-bar',
            'permissions' => array(
                'view_petshop_reports' => true,
            )
        ),
        'customers' => array(
            'label' => 'Xem khách hàng',
            'description' => 'Xem thông tin và lịch sử mua hàng của khách hàng.',
            'icon' => 'dashicons-groups',
            'permissions' => array(
                'view_petshop_customers' => true,
            )
        ),
    );
}

// =============================================
// LƯU VÀ KIỂM TRA QUYỀN STAFF
// =============================================

// Lấy quyền của staff
function petshop_get_staff_assigned_permissions($user_id) {
    $permissions = get_user_meta($user_id, 'petshop_staff_permissions', true);
    return is_array($permissions) ? $permissions : array();
}

// Kiểm tra staff có quyền cụ thể không
function petshop_staff_can($permission, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    // Admin và Manager có mọi quyền
    if (in_array('administrator', $user->roles) || in_array('petshop_manager', $user->roles)) {
        return true;
    }
    
    // Staff kiểm tra quyền được cấp
    if (in_array('petshop_staff', $user->roles)) {
        $assigned = petshop_get_staff_assigned_permissions($user_id);
        return in_array($permission, $assigned);
    }
    
    return false;
}

// Lưu quyền cho staff
function petshop_save_staff_permissions($user_id, $permissions) {
    update_user_meta($user_id, 'petshop_staff_permissions', $permissions);
}

// =============================================
// THÊM CỘT VÀO TRANG USERS
// =============================================
function petshop_add_user_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'email') {
            $new_columns['user_role'] = 'Vai trò';
            $new_columns['phone'] = 'Điện thoại';
            $new_columns['orders'] = 'Đơn hàng';
            $new_columns['total_spent'] = 'Chi tiêu';
        }
    }
    
    return $new_columns;
}
add_filter('manage_users_columns', 'petshop_add_user_columns');

// Hiển thị dữ liệu cột
function petshop_show_user_column_data($value, $column_name, $user_id) {
    $user = get_userdata($user_id);
    
    switch ($column_name) {
        case 'user_role':
            if (in_array('administrator', $user->roles)) {
                return '<span style="background:#2271b1;color:#fff;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;">Admin</span>';
            } elseif (in_array('petshop_manager', $user->roles)) {
                return '<span style="background:#00a32a;color:#fff;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;">Quản lý</span>';
            } elseif (in_array('petshop_staff', $user->roles)) {
                $perms = petshop_get_staff_assigned_permissions($user_id);
                $count = count($perms);
                return '<span style="background:#dba617;color:#fff;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;">Nhân viên</span>' . 
                       ($count > 0 ? '<br><small style="color:#666;">' . $count . ' quyền</small>' : '<br><small style="color:#999;">Chưa phân quyền</small>');
            } elseif (in_array('petshop_customer', $user->roles)) {
                return '<span style="background:#646970;color:#fff;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;">Khách hàng</span>';
            }
            return '<span style="color:#999;">—</span>';
            
        case 'phone':
            $phone = get_user_meta($user_id, 'billing_phone', true);
            return $phone ? esc_html($phone) : '<span style="color:#999;">—</span>';
            
        case 'orders':
            $count = petshop_count_user_orders($user_id);
            return $count > 0 ? '<a href="' . admin_url('edit.php?post_type=petshop_order&customer_id=' . $user_id) . '">' . $count . '</a>' : '0';
            
        case 'total_spent':
            $total = petshop_get_user_total_spent($user_id);
            return $total > 0 ? '<strong style="color:#00a32a;">' . number_format($total) . 'đ</strong>' : '<span style="color:#999;">—</span>';
    }
    
    return $value;
}
add_filter('manage_users_custom_column', 'petshop_show_user_column_data', 10, 3);

// =============================================
// FILTER USERS THEO ROLE
// =============================================
function petshop_add_user_role_filter($which) {
    if ($which !== 'top') return;
    
    $current_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    ?>
    <select name="role" style="float:none;margin-left:10px;">
        <option value="">Tất cả vai trò</option>
        <option value="administrator" <?php selected($current_role, 'administrator'); ?>>Admin</option>
        <option value="petshop_manager" <?php selected($current_role, 'petshop_manager'); ?>>Quản lý</option>
        <option value="petshop_staff" <?php selected($current_role, 'petshop_staff'); ?>>Nhân viên</option>
        <option value="petshop_customer" <?php selected($current_role, 'petshop_customer'); ?>>Khách hàng</option>
    </select>
    <?php
}
add_action('restrict_manage_users', 'petshop_add_user_role_filter');

// =============================================
// BULK ACTIONS
// =============================================
function petshop_user_bulk_actions($actions) {
    // Xóa action mặc định
    unset($actions['promote']);
    
    // Thêm actions mới
    $actions['set_manager'] = 'Đặt làm Quản lý';
    $actions['set_staff'] = 'Đặt làm Nhân viên';
    $actions['set_customer'] = 'Đặt làm Khách hàng';
    
    return $actions;
}
add_filter('bulk_actions-users', 'petshop_user_bulk_actions');

// Xử lý bulk actions
function petshop_handle_user_bulk_actions($redirect_to, $action, $user_ids) {
    $role_map = array(
        'set_manager' => 'petshop_manager',
        'set_staff' => 'petshop_staff',
        'set_customer' => 'petshop_customer',
    );
    
    if (!isset($role_map[$action])) {
        return $redirect_to;
    }
    
    $new_role = $role_map[$action];
    $count = 0;
    
    foreach ($user_ids as $user_id) {
        $user = get_userdata($user_id);
        
        // Không cho phép thay đổi role của admin
        if (in_array('administrator', $user->roles)) {
            continue;
        }
        
        // Manager không được thay đổi role của manager khác
        if (!current_user_can('administrator') && in_array('petshop_manager', $user->roles)) {
            continue;
        }
        
        $user->set_role($new_role);
        $count++;
    }
    
    return add_query_arg('petshop_role_updated', $count, $redirect_to);
}
add_filter('handle_bulk_actions-users', 'petshop_handle_user_bulk_actions', 10, 3);

// Thông báo
function petshop_user_bulk_action_notice() {
    if (!empty($_REQUEST['petshop_role_updated'])) {
        $count = intval($_REQUEST['petshop_role_updated']);
        printf('<div class="notice notice-success is-dismissible"><p>Đã cập nhật vai trò cho %d người dùng.</p></div>', $count);
    }
}
add_action('admin_notices', 'petshop_user_bulk_action_notice');

// =============================================
// TRANG PROFILE - PHÂN QUYỀN STAFF
// =============================================
function petshop_add_staff_permissions_section($user) {
    // Chỉ Admin và Manager mới thấy section này
    if (!current_user_can('administrator') && !current_user_can('manage_petshop_staff')) {
        return;
    }
    
    // Chỉ hiển thị cho Staff
    if (!in_array('petshop_staff', $user->roles)) {
        // Hiển thị thông tin cơ bản cho các role khác
        petshop_show_basic_user_info($user);
        return;
    }
    
    $assigned_permissions = petshop_get_staff_assigned_permissions($user->ID);
    $all_permissions = petshop_get_staff_permissions();
    ?>
    <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">
        <span class="dashicons dashicons-admin-users" style="margin-right: 8px;"></span>
        Phân quyền nhân viên
    </h3>
    
    <p style="color: #666; margin-bottom: 20px;">
        Chọn các quyền mà nhân viên này được phép thực hiện. Nhân viên chỉ có thể làm những việc được phân công.
    </p>
    
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Quyền hạn</th>
            <td>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <?php foreach ($all_permissions as $key => $perm) : 
                        $is_checked = in_array($key, $assigned_permissions);
                    ?>
                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 15px; background: <?php echo $is_checked ? '#e7f5e7' : '#f6f7f7'; ?>; border-radius: 8px; cursor: pointer; border: 2px solid <?php echo $is_checked ? '#00a32a' : 'transparent'; ?>; transition: all 0.2s;">
                        <input type="checkbox" name="petshop_staff_permissions[]" value="<?php echo esc_attr($key); ?>" 
                               <?php checked($is_checked); ?> 
                               style="margin-top: 3px;">
                        <div>
                            <span class="dashicons <?php echo esc_attr($perm['icon']); ?>" style="color: <?php echo $is_checked ? '#00a32a' : '#2271b1'; ?>; float: left; margin-right: 8px;"></span>
                            <strong style="color: #1d2327;"><?php echo esc_html($perm['label']); ?></strong>
                            <p style="margin: 5px 0 0; color: #666; font-size: 12px;"><?php echo esc_html($perm['description']); ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">Số điện thoại</th>
            <td>
                <input type="text" name="billing_phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'billing_phone', true)); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    
    <style>
    input[type="checkbox"]:checked + div strong { color: #00a32a; }
    </style>
    <?php
}
add_action('show_user_profile', 'petshop_add_staff_permissions_section');
add_action('edit_user_profile', 'petshop_add_staff_permissions_section');

// Hiển thị thông tin cơ bản
function petshop_show_basic_user_info($user) {
    ?>
    <h3>Thông tin PetShop</h3>
    <table class="form-table">
        <tr>
            <th><label for="billing_phone">Số điện thoại</label></th>
            <td>
                <input type="text" name="billing_phone" id="billing_phone" 
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'billing_phone', true)); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="billing_address">Địa chỉ</label></th>
            <td>
                <textarea name="billing_address" id="billing_address" rows="3" class="regular-text"><?php 
                    echo esc_textarea(get_user_meta($user->ID, 'billing_address', true)); 
                ?></textarea>
            </td>
        </tr>
        <?php if (in_array('petshop_customer', $user->roles)) : ?>
        <tr>
            <th>Thống kê</th>
            <td>
                <p><strong>Số đơn hàng:</strong> <?php echo petshop_count_user_orders($user->ID); ?></p>
                <p><strong>Tổng chi tiêu:</strong> <?php echo number_format(petshop_get_user_total_spent($user->ID)); ?>đ</p>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
}

// Lưu quyền staff
function petshop_save_staff_permissions_profile($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    // Lưu số điện thoại
    if (isset($_POST['billing_phone'])) {
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
    }
    
    // Lưu địa chỉ
    if (isset($_POST['billing_address'])) {
        update_user_meta($user_id, 'billing_address', sanitize_textarea_field($_POST['billing_address']));
    }
    
    // Lưu quyền staff
    $user = get_userdata($user_id);
    if (in_array('petshop_staff', $user->roles)) {
        $permissions = isset($_POST['petshop_staff_permissions']) ? array_map('sanitize_text_field', $_POST['petshop_staff_permissions']) : array();
        petshop_save_staff_permissions($user_id, $permissions);
    }
}
add_action('personal_options_update', 'petshop_save_staff_permissions_profile');
add_action('edit_user_profile_update', 'petshop_save_staff_permissions_profile');

// =============================================
// THÊM MENU HƯỚNG DẪN PHÂN QUYỀN
// =============================================
function petshop_add_roles_guide_menu() {
    add_submenu_page(
        'users.php',
        'Hướng dẫn phân quyền',
        'Hướng dẫn phân quyền',
        'manage_options',
        'petshop-roles-guide',
        'petshop_roles_guide_page'
    );
}
add_action('admin_menu', 'petshop_add_roles_guide_menu');

// Trang hướng dẫn
function petshop_roles_guide_page() {
    $all_permissions = petshop_get_staff_permissions();
    ?>
    <div class="wrap">
        <h1>Hướng dẫn phân quyền PetShop</h1>
        
        <div style="max-width: 1000px; margin-top: 20px;">
            <!-- Tổng quan -->
            <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px; margin-bottom: 25px;">
                <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-info-outline" style="color: #2271b1;"></span>
                    Tổng quan hệ thống
                </h2>
                <p style="color: #666; font-size: 14px;">
                    Hệ thống PetShop có 4 vai trò (role) với quyền hạn từ cao đến thấp:
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
                    <!-- Admin -->
                    <div style="background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; padding: 20px; border-radius: 10px; text-align: center;">
                        <span class="dashicons dashicons-shield-alt" style="font-size: 40px; width: auto; height: auto;"></span>
                        <h3 style="margin: 10px 0 5px;">Admin</h3>
                        <p style="margin: 0; font-size: 12px; opacity: 0.9;">Toàn quyền hệ thống</p>
                    </div>
                    
                    <!-- Manager -->
                    <div style="background: linear-gradient(135deg, #00a32a 0%, #008a20 100%); color: #fff; padding: 20px; border-radius: 10px; text-align: center;">
                        <span class="dashicons dashicons-businessman" style="font-size: 40px; width: auto; height: auto;"></span>
                        <h3 style="margin: 10px 0 5px;">Quản lý</h3>
                        <p style="margin: 0; font-size: 12px; opacity: 0.9;">Quản lý cửa hàng</p>
                    </div>
                    
                    <!-- Staff -->
                    <div style="background: linear-gradient(135deg, #dba617 0%, #c69500 100%); color: #fff; padding: 20px; border-radius: 10px; text-align: center;">
                        <span class="dashicons dashicons-id" style="font-size: 40px; width: auto; height: auto;"></span>
                        <h3 style="margin: 10px 0 5px;">Nhân viên</h3>
                        <p style="margin: 0; font-size: 12px; opacity: 0.9;">Theo phân công</p>
                    </div>
                    
                    <!-- Customer -->
                    <div style="background: linear-gradient(135deg, #646970 0%, #50575e 100%); color: #fff; padding: 20px; border-radius: 10px; text-align: center;">
                        <span class="dashicons dashicons-admin-users" style="font-size: 40px; width: auto; height: auto;"></span>
                        <h3 style="margin: 10px 0 5px;">Khách hàng</h3>
                        <p style="margin: 0; font-size: 12px; opacity: 0.9;">Mua sắm</p>
                    </div>
                </div>
            </div>
            
            <!-- Chi tiết từng role -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Admin -->
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px; border-top: 4px solid #2271b1;">
                    <h2 style="margin-top: 0; color: #2271b1;">
                        <span class="dashicons dashicons-shield-alt"></span> Admin
                    </h2>
                    <p style="color: #666;">Quản trị viên có toàn quyền trên hệ thống:</p>
                    <ul style="margin: 15px 0; padding-left: 20px; color: #1d2327; list-style: none;">
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý tất cả người dùng</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Thay đổi vai trò người dùng</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Cài đặt theme, plugin</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Cấu hình hệ thống</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Xóa dữ liệu</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Export/Import dữ liệu</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Tất cả quyền của Manager</li>
                    </ul>
                </div>
                
                <!-- Manager -->
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px; border-top: 4px solid #00a32a;">
                    <h2 style="margin-top: 0; color: #00a32a;">
                        <span class="dashicons dashicons-businessman"></span> Quản lý
                    </h2>
                    <p style="color: #666;">Quản lý cửa hàng với các quyền:</p>
                    <ul style="margin: 15px 0; padding-left: 20px; color: #1d2327; list-style: none;">
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý bài viết, trang (CRUD)</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Duyệt bài viết của Staff</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý đơn hàng (CRUD)</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý sản phẩm (CRUD)</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý tồn kho</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Quản lý mã giảm giá</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Xem báo cáo</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Phân quyền cho Staff</li>
                        <li><span class="dashicons dashicons-dismiss" style="color: #d63638;"></span> Không cài đặt plugin/theme</li>
                        <li><span class="dashicons dashicons-dismiss" style="color: #d63638;"></span> Không thay đổi cấu hình</li>
                    </ul>
                </div>
            </div>
            
            <!-- Staff Permissions -->
            <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px; margin-top: 25px; border-top: 4px solid #dba617;">
                <h2 style="margin-top: 0; color: #dba617;">
                    <span class="dashicons dashicons-id"></span> Nhân viên - Quyền có thể phân công
                </h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Mỗi nhân viên chỉ có những quyền được Admin hoặc Quản lý phân công. Vào <strong>Users → chọn nhân viên → Edit</strong> để phân quyền.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <?php foreach ($all_permissions as $key => $perm) : ?>
                    <div style="background: #f6f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #dba617;">
                        <h4 style="margin: 0 0 8px; display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons <?php echo esc_attr($perm['icon']); ?>" style="color: #dba617;"></span>
                            <?php echo esc_html($perm['label']); ?>
                        </h4>
                        <p style="margin: 0; color: #666; font-size: 13px;"><?php echo esc_html($perm['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="background: #fff8e5; border: 1px solid #dba617; border-radius: 8px; padding: 15px; margin-top: 20px;">
                    <p style="margin: 0; color: #8c6d00;">
                        <strong><span class="dashicons dashicons-warning" style="color: #dba617;"></span> Lưu ý quan trọng:</strong><br>
                        • Bài viết của Staff luôn cần Admin/Manager duyệt trước khi đăng công khai.<br>
                        • Staff không có quyền xóa bất kỳ dữ liệu nào.<br>
                        • Staff chỉ được xem mã giảm giá, không được tạo/sửa/xóa.
                    </p>
                </div>
            </div>
            
            <!-- Customer -->
            <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px; margin-top: 25px; border-top: 4px solid #646970;">
                <h2 style="margin-top: 0; color: #646970;">
                    <span class="dashicons dashicons-admin-users"></span> Khách hàng
                </h2>
                <p style="color: #666;">Người dùng đăng ký để mua hàng:</p>
                <ul style="margin: 15px 0; padding-left: 20px; color: #1d2327; list-style: none;">
                    <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Đăng nhập để mua hàng</li>
                    <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Xem lịch sử đơn hàng của mình</li>
                    <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Cập nhật thông tin cá nhân</li>
                    <li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Viết đánh giá sản phẩm đã mua</li>
                    <li><span class="dashicons dashicons-dismiss" style="color: #d63638;"></span> Không truy cập trang quản trị</li>
                </ul>
            </div>
            
            <!-- Hướng dẫn nhanh -->
            <div style="background: #e7f5e7; border: 1px solid #00a32a; border-radius: 8px; padding: 25px; margin-top: 25px;">
                <h2 style="margin-top: 0; color: #00a32a;">
                    <span class="dashicons dashicons-lightbulb"></span> Hướng dẫn nhanh
                </h2>
                <ol style="margin: 15px 0; padding-left: 20px; color: #1d2327; line-height: 2;">
                    <li>Vào <strong>Users</strong> trong menu bên trái</li>
                    <li>Tìm người dùng cần thay đổi vai trò</li>
                    <li><strong>Cách 1:</strong> Tick chọn → Bulk Actions → Chọn vai trò → Apply</li>
                    <li><strong>Cách 2:</strong> Click <strong>Edit</strong> để vào trang profile</li>
                    <li>Với Staff: cuộn xuống phần "Phân quyền nhân viên" để tick các quyền cần cấp</li>
                    <li>Nhấn <strong>Update User</strong> để lưu</li>
                </ol>
            </div>
        </div>
    </div>
    <?php
}

// =============================================
// GIỚI HẠN DROPDOWN ROLE KHI EDIT USER
// =============================================
function petshop_editable_roles($roles) {
    // Chỉ giữ lại các role của PetShop
    $allowed = array('administrator', 'petshop_manager', 'petshop_staff', 'petshop_customer');
    
    foreach ($roles as $role => $details) {
        if (!in_array($role, $allowed)) {
            unset($roles[$role]);
        }
    }
    
    // Manager không được chọn admin
    if (!current_user_can('administrator')) {
        unset($roles['administrator']);
        unset($roles['petshop_manager']);
    }
    
    return $roles;
}
add_filter('editable_roles', 'petshop_editable_roles');

// =============================================
// THÔNG BÁO KHI BÀI VIẾT CẦN DUYỆT
// =============================================
function petshop_pending_posts_notification() {
    if (!current_user_can('publish_posts')) return;
    
    $pending = get_posts(array(
        'post_status' => 'pending',
        'post_type' => 'post',
        'numberposts' => -1,
    ));
    
    if (count($pending) > 0) {
        $url = admin_url('edit.php?post_status=pending');
        printf(
            '<div class="notice notice-warning"><p><strong><span class="dashicons dashicons-edit" style="color: #dba617;"></span> Có %d bài viết đang chờ duyệt.</strong> <a href="%s">Xem và duyệt ngay</a></p></div>',
            count($pending),
            esc_url($url)
        );
    }
}
add_action('admin_notices', 'petshop_pending_posts_notification');

// Staff tạo bài viết sẽ tự động là pending
function petshop_set_staff_post_pending($data, $postarr) {
    $user = wp_get_current_user();
    
    if (in_array('petshop_staff', $user->roles)) {
        // Nếu không phải là update (tạo mới hoặc publish)
        if ($data['post_status'] === 'publish') {
            $data['post_status'] = 'pending';
        }
    }
    
    return $data;
}
add_filter('wp_insert_post_data', 'petshop_set_staff_post_pending', 10, 2);

// =============================================
// HELPER FUNCTIONS
// =============================================
function petshop_count_user_orders($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_orders';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        return 0;
    }
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE customer_id = %d",
        $user_id
    ));
}

function petshop_get_user_total_spent($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_orders';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        return 0;
    }
    
    return (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total) FROM $table WHERE customer_id = %d AND status NOT IN ('cancelled', 'refunded')",
        $user_id
    ));
}

// =============================================
// ẨN ADMIN BAR CHO CUSTOMER
// =============================================
function petshop_hide_admin_bar_for_customers() {
    $user = wp_get_current_user();
    if (in_array('petshop_customer', $user->roles)) {
        return false;
    }
    return true;
}
add_filter('show_admin_bar', 'petshop_hide_admin_bar_for_customers');

// Redirect customer khi cố truy cập admin
function petshop_redirect_customers_from_admin() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();
        if (in_array('petshop_customer', $user->roles)) {
            wp_redirect(home_url('/tai-khoan/'));
            exit;
        }
    }
}
add_action('admin_init', 'petshop_redirect_customers_from_admin');

// =============================================
// FORCE RESET ROLES (cho lần đầu tiên)
// =============================================
function petshop_force_reset_roles() {
    if (isset($_GET['petshop_reset_roles']) && current_user_can('administrator')) {
        delete_option('petshop_roles_version');
        petshop_init_roles();
        wp_redirect(admin_url('users.php?page=petshop-roles-guide&reset=1'));
        exit;
    }
}
add_action('admin_init', 'petshop_force_reset_roles');

// Thông báo reset thành công
function petshop_reset_roles_notice() {
    if (isset($_GET['reset']) && $_GET['reset'] == '1' && isset($_GET['page']) && $_GET['page'] === 'petshop-roles-guide') {
        echo '<div class="notice notice-success is-dismissible"><p><strong><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> Đã cập nhật hệ thống phân quyền thành công!</strong> Các role dư thừa đã được xóa.</p></div>';
    }
}
add_action('admin_notices', 'petshop_reset_roles_notice');
