<?php
/**
 * PetShop Promotion Menu
 * Menu Khuyến mãi tổng hợp
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// ĐĂNG KÝ MENU KHUYẾN MÃI CHÍNH
// =============================================
function petshop_register_promotion_menu() {
    // Menu chính Khuyến mãi
    add_menu_page(
        'Khuyến mãi',
        'Khuyến mãi',
        'manage_options',
        'petshop-promotions',
        'petshop_promotions_dashboard_page',
        'dashicons-tag',
        28
    );
    
    // Submenu: Tổng quan
    add_submenu_page(
        'petshop-promotions',
        'Tổng quan khuyến mãi',
        'Tổng quan',
        'manage_options',
        'petshop-promotions',
        'petshop_promotions_dashboard_page'
    );
    
    // Submenu: Mã giảm giá (Coupons)
    add_submenu_page(
        'petshop-promotions',
        'Quản lý mã giảm giá',
        'Mã giảm giá',
        'manage_options',
        'petshop-coupons',
        'petshop_coupons_page'
    );
    
    // Submenu: Thêm mã mới (hidden)
    add_submenu_page(
        null,
        'Thêm/Sửa mã giảm giá',
        'Thêm mã giảm giá',
        'manage_options',
        'petshop-coupon-edit',
        'petshop_coupon_edit_page'
    );
    
    // Submenu: Voucher theo Tier
    add_submenu_page(
        'petshop-promotions',
        'Voucher theo hạng',
        'Voucher theo hạng',
        'manage_options',
        'petshop-tier-vouchers',
        'petshop_tier_vouchers_page'
    );
    
    // Submenu: Đổi điểm thưởng
    add_submenu_page(
        'petshop-promotions',
        'Đổi điểm thưởng',
        'Đổi điểm thưởng',
        'manage_options',
        'petshop-points-redemption',
        'petshop_points_redemption_page'
    );
    
    // Submenu: Flash Sale
    add_submenu_page(
        'petshop-promotions',
        'Flash Sale',
        'Flash Sale',
        'manage_options',
        'petshop-flash-sale',
        'petshop_flash_sale_page'
    );
    
    // Submenu: Cài đặt khuyến mãi
    add_submenu_page(
        'petshop-promotions',
        'Cài đặt khuyến mãi',
        'Cài đặt',
        'manage_options',
        'petshop-promotion-settings',
        'petshop_promotion_settings_page'
    );
}
add_action('admin_menu', 'petshop_register_promotion_menu', 26);

// =============================================
// TRANG TỔNG QUAN KHUYẾN MÃI
// =============================================
function petshop_promotions_dashboard_page() {
    global $wpdb;
    
    // Lấy thống kê coupon
    $table_coupons = $wpdb->prefix . 'petshop_coupons';
    $table_usage = $wpdb->prefix . 'petshop_coupon_usage';
    
    // Kiểm tra bảng tồn tại
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_coupons'") === $table_coupons;
    
    $stats = array(
        'total_coupons' => 0,
        'active_coupons' => 0,
        'expired_coupons' => 0,
        'total_usage' => 0,
        'total_discount' => 0,
        'usage_today' => 0,
        'usage_this_month' => 0,
    );
    
    if ($table_exists) {
        $stats['total_coupons'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_coupons");
        $stats['active_coupons'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_coupons WHERE is_active = 1 AND (end_datetime IS NULL OR end_datetime > NOW())");
        $stats['expired_coupons'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_coupons WHERE end_datetime IS NOT NULL AND end_datetime < NOW()");
        
        $usage_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_usage'") === $table_usage;
        if ($usage_exists) {
            $stats['total_usage'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_usage");
            $stats['total_discount'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(discount_amount), 0) FROM $table_usage");
            $stats['usage_today'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_usage WHERE DATE(used_at) = CURDATE()");
            $stats['usage_this_month'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_usage WHERE MONTH(used_at) = MONTH(NOW()) AND YEAR(used_at) = YEAR(NOW())");
        }
    }
    
    // Lấy các coupon active gần đây
    $recent_coupons = array();
    if ($table_exists) {
        $recent_coupons = $wpdb->get_results("
            SELECT * FROM $table_coupons 
            WHERE is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
    }
    
    // Lấy lịch sử sử dụng gần đây
    $recent_usage = array();
    if ($table_exists) {
        $usage_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_usage'") === $table_usage;
        if ($usage_exists) {
            $recent_usage = $wpdb->get_results("
                SELECT u.*, c.code, c.name as coupon_name
                FROM $table_usage u
                LEFT JOIN $table_coupons c ON u.coupon_id = c.id
                ORDER BY u.used_at DESC
                LIMIT 10
            ");
        }
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .promo-wrap { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
    .promo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .promo-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; position: relative; overflow: hidden; }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
    .stat-card.blue::before { background: linear-gradient(135deg, #007bff, #17a2b8); }
    .stat-card.green::before { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-card.orange::before { background: linear-gradient(135deg, #EC802B, #F5994D); }
    .stat-card.purple::before { background: linear-gradient(135deg, #6f42c1, #9561e2); }
    
    .stat-card-content { display: flex; justify-content: space-between; align-items: flex-start; }
    .stat-info h3 { margin: 0 0 5px; font-size: 28px; font-weight: 700; color: #333; }
    .stat-info p { margin: 0; color: #666; font-size: 14px; }
    .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; }
    .stat-icon.blue { background: linear-gradient(135deg, #007bff, #17a2b8); }
    .stat-icon.green { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-icon.orange { background: linear-gradient(135deg, #EC802B, #F5994D); }
    .stat-icon.purple { background: linear-gradient(135deg, #6f42c1, #9561e2); }
    
    .quick-actions { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
    .quick-action { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; text-decoration: none; color: #333; transition: all 0.2s; }
    .quick-action:hover { border-color: #EC802B; transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    .quick-action i { font-size: 32px; color: #EC802B; }
    .quick-action span { font-weight: 600; font-size: 13px; text-align: center; }
    
    .promo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    
    .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
    .card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
    .card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .card-body { padding: 20px; }
    
    .coupon-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .coupon-item:last-child { border-bottom: none; }
    .coupon-code { background: #f8f9fa; padding: 4px 12px; border-radius: 6px; font-family: monospace; font-weight: 600; color: #EC802B; }
    .coupon-info { flex: 1; margin-left: 15px; }
    .coupon-info .name { font-weight: 600; color: #333; }
    .coupon-info .meta { font-size: 12px; color: #888; }
    .coupon-discount { font-weight: 700; color: #28a745; }
    
    .usage-item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .usage-item:last-child { border-bottom: none; }
    .usage-icon { width: 40px; height: 40px; background: #e6f7e6; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #28a745; }
    .usage-info { flex: 1; }
    .usage-info .code { font-weight: 600; color: #333; }
    .usage-info .time { font-size: 12px; color: #888; }
    .usage-amount { font-weight: 700; color: #dc3545; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; color: #fff; }
    .btn-outline { background: #fff; border: 1px solid #ddd; color: #666; }
    .btn-outline:hover { border-color: #EC802B; color: #EC802B; }
    
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .quick-actions { grid-template-columns: repeat(3, 1fr); }
        .promo-grid { grid-template-columns: 1fr; }
    }
    </style>
    
    <div class="promo-wrap">
        <div class="promo-header">
            <h1><i class="bi bi-tag"></i> Quản lý Khuyến mãi</h1>
            <a href="<?php echo admin_url('admin.php?page=petshop-coupon-edit'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tạo mã giảm giá
            </a>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_coupons']); ?></h3>
                        <p>Tổng mã giảm giá</p>
                    </div>
                    <div class="stat-icon blue"><i class="bi bi-ticket-perforated"></i></div>
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['active_coupons']); ?></h3>
                        <p>Đang hoạt động</p>
                    </div>
                    <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_usage']); ?></h3>
                        <p>Lượt sử dụng</p>
                    </div>
                    <div class="stat-icon orange"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
            </div>
            
            <div class="stat-card purple">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_discount']); ?>đ</h3>
                        <p>Tổng giảm giá</p>
                    </div>
                    <div class="stat-icon purple"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?php echo admin_url('admin.php?page=petshop-coupons'); ?>" class="quick-action">
                <i class="bi bi-ticket-perforated"></i>
                <span>Mã giảm giá</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=petshop-tier-vouchers'); ?>" class="quick-action">
                <i class="bi bi-award"></i>
                <span>Voucher theo hạng</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=petshop-points-redemption'); ?>" class="quick-action">
                <i class="bi bi-coin"></i>
                <span>Đổi điểm thưởng</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=petshop-flash-sale'); ?>" class="quick-action">
                <i class="bi bi-lightning"></i>
                <span>Flash Sale</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=petshop-promotion-settings'); ?>" class="quick-action">
                <i class="bi bi-gear"></i>
                <span>Cài đặt</span>
            </a>
        </div>
        
        <!-- Content Grid -->
        <div class="promo-grid">
            <!-- Recent Coupons -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-ticket-perforated" style="color: #EC802B;"></i> Mã giảm giá hoạt động</h3>
                    <a href="<?php echo admin_url('admin.php?page=petshop-coupons'); ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_coupons)): ?>
                    <p style="text-align: center; color: #888; padding: 30px 0;">
                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                        Chưa có mã giảm giá nào
                    </p>
                    <?php else: ?>
                        <?php foreach ($recent_coupons as $coupon): ?>
                        <div class="coupon-item">
                            <span class="coupon-code"><?php echo esc_html($coupon->code); ?></span>
                            <div class="coupon-info">
                                <div class="name"><?php echo esc_html($coupon->name); ?></div>
                                <div class="meta">
                                    <?php 
                                    $types = array('order' => 'Toàn đơn', 'product' => 'Sản phẩm', 'combo' => 'Combo', 'category' => 'Danh mục');
                                    echo $types[$coupon->type] ?? $coupon->type;
                                    ?>
                                    | Đã dùng: <?php echo $coupon->usage_count; ?>/<?php echo $coupon->usage_limit ?: '∞'; ?>
                                </div>
                            </div>
                            <span class="coupon-discount">
                                <?php 
                                if ($coupon->discount_type === 'percent') {
                                    echo '-' . intval($coupon->discount_value) . '%';
                                } else {
                                    echo '-' . number_format($coupon->discount_value) . 'đ';
                                }
                                ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Usage -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history" style="color: #EC802B;"></i> Lịch sử sử dụng</h3>
                    <span style="font-size: 13px; color: #888;">
                        Hôm nay: <?php echo $stats['usage_today']; ?> | Tháng này: <?php echo $stats['usage_this_month']; ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_usage)): ?>
                    <p style="text-align: center; color: #888; padding: 30px 0;">
                        <i class="bi bi-clock" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                        Chưa có lượt sử dụng nào
                    </p>
                    <?php else: ?>
                        <?php foreach ($recent_usage as $usage): ?>
                        <div class="usage-item">
                            <div class="usage-icon"><i class="bi bi-check2"></i></div>
                            <div class="usage-info">
                                <div class="code"><?php echo esc_html($usage->code ?? 'N/A'); ?></div>
                                <div class="time">
                                    <?php echo date('d/m/Y H:i', strtotime($usage->used_at)); ?>
                                    <?php if ($usage->order_id): ?>
                                    | Đơn #<?php echo $usage->order_id; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="usage-amount">-<?php echo number_format($usage->discount_amount); ?>đ</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// =============================================
// TRANG VOUCHER THEO HẠNG THÀNH VIÊN
// =============================================
function petshop_tier_vouchers_page() {
    global $wpdb;
    
    $tiers = array(
        'bronze' => array('name' => 'Đồng', 'color' => '#CD7F32', 'icon' => 'bi-award'),
        'silver' => array('name' => 'Bạc', 'color' => '#C0C0C0', 'icon' => 'bi-gem'),
        'gold' => array('name' => 'Vàng', 'color' => '#FFD700', 'icon' => 'bi-trophy')
    );
    
    // Lấy voucher theo tier từ options
    $tier_vouchers = get_option('petshop_tier_vouchers', array(
        'bronze' => array('discount' => 5, 'type' => 'percent', 'auto_apply' => false),
        'silver' => array('discount' => 10, 'type' => 'percent', 'auto_apply' => false),
        'gold' => array('discount' => 15, 'type' => 'percent', 'auto_apply' => true)
    ));
    
    // Xử lý lưu
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tier_vouchers'])) {
        check_admin_referer('petshop_tier_vouchers');
        
        $new_vouchers = array();
        foreach ($tiers as $key => $tier) {
            $new_vouchers[$key] = array(
                'discount' => floatval($_POST['discount_' . $key] ?? 0),
                'type' => sanitize_text_field($_POST['type_' . $key] ?? 'percent'),
                'min_order' => floatval($_POST['min_order_' . $key] ?? 0),
                'auto_apply' => isset($_POST['auto_apply_' . $key]),
                'birthday_bonus' => floatval($_POST['birthday_bonus_' . $key] ?? 0),
            );
        }
        
        update_option('petshop_tier_vouchers', $new_vouchers);
        $tier_vouchers = $new_vouchers;
        
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt voucher theo hạng!</p></div>';
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .tier-voucher-wrap { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
    .tier-voucher-header { margin-bottom: 25px; }
    .tier-voucher-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0 0 10px; }
    .tier-voucher-header p { color: #666; margin: 0; }
    
    .tier-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
    .tier-card { background: #fff; border-radius: 15px; border: 2px solid #e0e0e0; overflow: hidden; }
    .tier-card-header { padding: 20px; text-align: center; color: #fff; }
    .tier-card-header i { font-size: 48px; display: block; margin-bottom: 10px; }
    .tier-card-header h3 { margin: 0; font-size: 20px; }
    .tier-card-body { padding: 25px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .form-group small { display: block; margin-top: 4px; color: #888; font-size: 12px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .checkbox-group { display: flex; align-items: center; gap: 10px; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #EC802B; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    
    @media (max-width: 992px) {
        .tier-cards { grid-template-columns: 1fr; }
    }
    </style>
    
    <div class="tier-voucher-wrap">
        <div class="tier-voucher-header">
            <h1><i class="bi bi-award"></i> Voucher theo hạng thành viên</h1>
            <p>Cài đặt ưu đãi tự động cho từng hạng thành viên</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('petshop_tier_vouchers'); ?>
            
            <div class="tier-cards">
                <?php foreach ($tiers as $key => $tier): 
                    $voucher = $tier_vouchers[$key] ?? array();
                ?>
                <div class="tier-card">
                    <div class="tier-card-header" style="background: linear-gradient(135deg, <?php echo $tier['color']; ?>, <?php echo $tier['color']; ?>cc);">
                        <i class="bi <?php echo $tier['icon']; ?>"></i>
                        <h3>Hạng <?php echo $tier['name']; ?></h3>
                    </div>
                    <div class="tier-card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Giảm giá</label>
                                <input type="number" name="discount_<?php echo $key; ?>" value="<?php echo $voucher['discount'] ?? 0; ?>" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label>Loại giảm</label>
                                <select name="type_<?php echo $key; ?>">
                                    <option value="percent" <?php selected(($voucher['type'] ?? 'percent'), 'percent'); ?>>Phần trăm (%)</option>
                                    <option value="fixed" <?php selected(($voucher['type'] ?? ''), 'fixed'); ?>>Số tiền (đ)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Đơn tối thiểu (đ)</label>
                            <input type="number" name="min_order_<?php echo $key; ?>" value="<?php echo $voucher['min_order'] ?? 0; ?>" min="0">
                            <small>Để 0 nếu không giới hạn</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Thưởng sinh nhật (%)</label>
                            <input type="number" name="birthday_bonus_<?php echo $key; ?>" value="<?php echo $voucher['birthday_bonus'] ?? 0; ?>" min="0" max="100">
                            <small>Giảm thêm trong tháng sinh nhật</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="auto_apply_<?php echo $key; ?>" id="auto_<?php echo $key; ?>" <?php checked(!empty($voucher['auto_apply'])); ?>>
                                <label for="auto_<?php echo $key; ?>" style="margin: 0; font-weight: normal;">Tự động áp dụng khi thanh toán</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" name="save_tier_vouchers" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Lưu cài đặt
            </button>
        </form>
    </div>
    <?php
}

// =============================================
// TRANG ĐỔI ĐIỂM THƯỞNG
// =============================================
function petshop_points_redemption_page() {
    global $wpdb;
    
    // Lấy cài đặt điểm
    $points_settings = get_option('petshop_points_settings', array(
        'points_per_vnd' => 100,           // 100đ = 1 điểm
        'vnd_per_point' => 10,              // 1 điểm = 10đ khi đổi
        'min_points_redeem' => 1000,        // Tối thiểu 1000 điểm mới đổi được
        'max_discount_percent' => 50,       // Tối đa giảm 50% đơn hàng
        'enabled' => true
    ));
    
    // Các voucher có thể đổi điểm
    $redemption_vouchers = get_option('petshop_redemption_vouchers', array(
        array('points' => 5000, 'discount' => 50000, 'name' => 'Voucher 50K'),
        array('points' => 10000, 'discount' => 100000, 'name' => 'Voucher 100K'),
        array('points' => 20000, 'discount' => 250000, 'name' => 'Voucher 250K'),
    ));
    
    // Xử lý lưu cài đặt
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_points_settings'])) {
        check_admin_referer('petshop_points_settings');
        
        $points_settings = array(
            'points_per_vnd' => floatval($_POST['points_per_vnd'] ?? 100),
            'vnd_per_point' => floatval($_POST['vnd_per_point'] ?? 10),
            'min_points_redeem' => intval($_POST['min_points_redeem'] ?? 1000),
            'max_discount_percent' => intval($_POST['max_discount_percent'] ?? 50),
            'enabled' => isset($_POST['points_enabled'])
        );
        update_option('petshop_points_settings', $points_settings);
        
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt điểm thưởng!</p></div>';
    }
    
    // Xử lý lưu voucher
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_redemption_vouchers'])) {
        check_admin_referer('petshop_redemption_vouchers');
        
        $new_vouchers = array();
        if (!empty($_POST['voucher_points'])) {
            foreach ($_POST['voucher_points'] as $i => $points) {
                if (!empty($points) && !empty($_POST['voucher_discount'][$i])) {
                    $new_vouchers[] = array(
                        'points' => intval($points),
                        'discount' => intval($_POST['voucher_discount'][$i]),
                        'name' => sanitize_text_field($_POST['voucher_name'][$i] ?? 'Voucher')
                    );
                }
            }
        }
        
        update_option('petshop_redemption_vouchers', $new_vouchers);
        $redemption_vouchers = $new_vouchers;
        
        echo '<div class="notice notice-success"><p>Đã lưu danh sách voucher đổi điểm!</p></div>';
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .points-wrap { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
    .points-header { margin-bottom: 25px; }
    .points-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0 0 10px; }
    
    .points-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    
    .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
    .card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .card-body { padding: 25px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .form-group small { display: block; margin-top: 4px; color: #888; font-size: 12px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
    .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; accent-color: #28a745; }
    
    .voucher-list { }
    .voucher-row { display: grid; grid-template-columns: 100px 1fr 120px 40px; gap: 10px; align-items: center; margin-bottom: 10px; }
    .voucher-row input { padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; }
    .voucher-row .remove-btn { background: #dc3545; color: #fff; border: none; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .voucher-row .remove-btn:hover { background: #c82333; }
    
    .add-voucher-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: #f0f0f0; border: 1px dashed #ccc; border-radius: 8px; color: #666; cursor: pointer; margin-top: 10px; }
    .add-voucher-btn:hover { border-color: #EC802B; color: #EC802B; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    
    @media (max-width: 992px) {
        .points-grid { grid-template-columns: 1fr; }
    }
    </style>
    
    <div class="points-wrap">
        <div class="points-header">
            <h1><i class="bi bi-coin"></i> Đổi điểm thưởng</h1>
            <p style="color: #666;">Cài đặt hệ thống tích điểm và đổi điểm lấy voucher</p>
        </div>
        
        <div class="points-grid">
            <!-- Cài đặt điểm -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-gear" style="color: #EC802B;"></i> Cài đặt tích điểm</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php wp_nonce_field('petshop_points_settings'); ?>
                        
                        <div class="checkbox-group" style="margin-bottom: 20px;">
                            <input type="checkbox" name="points_enabled" id="points_enabled" <?php checked($points_settings['enabled']); ?>>
                            <label for="points_enabled" style="margin: 0; font-weight: 600; color: #333;">Bật hệ thống điểm thưởng</label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>VNĐ để có 1 điểm</label>
                                <input type="number" name="points_per_vnd" value="<?php echo $points_settings['points_per_vnd']; ?>" min="1">
                                <small>Chi tiêu bao nhiêu đồng để được 1 điểm</small>
                            </div>
                            <div class="form-group">
                                <label>1 điểm = ? VNĐ khi đổi</label>
                                <input type="number" name="vnd_per_point" value="<?php echo $points_settings['vnd_per_point']; ?>" min="1">
                                <small>Giá trị quy đổi điểm thành tiền</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Điểm tối thiểu để đổi</label>
                                <input type="number" name="min_points_redeem" value="<?php echo $points_settings['min_points_redeem']; ?>" min="0">
                            </div>
                            <div class="form-group">
                                <label>Giảm tối đa (%)</label>
                                <input type="number" name="max_discount_percent" value="<?php echo $points_settings['max_discount_percent']; ?>" min="0" max="100">
                                <small>% tối đa giảm giá đơn hàng bằng điểm</small>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_points_settings" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu cài đặt
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Voucher đổi điểm -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-gift" style="color: #EC802B;"></i> Voucher đổi điểm</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="voucherForm">
                        <?php wp_nonce_field('petshop_redemption_vouchers'); ?>
                        
                        <div class="voucher-list" id="voucherList">
                            <div class="voucher-row" style="font-weight: 600; color: #666; margin-bottom: 15px;">
                                <span>Điểm</span>
                                <span>Tên voucher</span>
                                <span>Giá trị (đ)</span>
                                <span></span>
                            </div>
                            
                            <?php foreach ($redemption_vouchers as $i => $voucher): ?>
                            <div class="voucher-row">
                                <input type="number" name="voucher_points[]" value="<?php echo $voucher['points']; ?>" placeholder="Điểm" min="0">
                                <input type="text" name="voucher_name[]" value="<?php echo esc_attr($voucher['name']); ?>" placeholder="Tên voucher">
                                <input type="number" name="voucher_discount[]" value="<?php echo $voucher['discount']; ?>" placeholder="Giá trị" min="0">
                                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="add-voucher-btn" onclick="addVoucherRow()">
                            <i class="bi bi-plus"></i> Thêm voucher
                        </button>
                        
                        <div style="margin-top: 20px;">
                            <button type="submit" name="save_redemption_vouchers" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Lưu voucher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function addVoucherRow() {
        const list = document.getElementById('voucherList');
        const row = document.createElement('div');
        row.className = 'voucher-row';
        row.innerHTML = `
            <input type="number" name="voucher_points[]" placeholder="Điểm" min="0">
            <input type="text" name="voucher_name[]" placeholder="Tên voucher">
            <input type="number" name="voucher_discount[]" placeholder="Giá trị (đ)" min="0">
            <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        `;
        list.appendChild(row);
    }
    </script>
    <?php
}

// =============================================
// TRANG FLASH SALE
// =============================================
function petshop_flash_sale_page() {
    global $wpdb;
    
    // Lấy danh sách flash sale
    $flash_sales = get_option('petshop_flash_sales', array());
    
    // Xử lý thêm flash sale mới
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_flash_sale'])) {
        check_admin_referer('petshop_flash_sale');
        
        $new_sale = array(
            'id' => uniqid('fs_'),
            'name' => sanitize_text_field($_POST['sale_name']),
            'discount' => floatval($_POST['sale_discount']),
            'discount_type' => sanitize_text_field($_POST['sale_discount_type']),
            'start_time' => sanitize_text_field($_POST['sale_start']),
            'end_time' => sanitize_text_field($_POST['sale_end']),
            'products' => array_map('intval', $_POST['sale_products'] ?? array()),
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $flash_sales[] = $new_sale;
        update_option('petshop_flash_sales', $flash_sales);
        
        echo '<div class="notice notice-success"><p>Đã tạo Flash Sale mới!</p></div>';
    }
    
    // Xử lý xóa flash sale
    if (isset($_GET['delete_sale']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_flash_sale')) {
            $sale_id = sanitize_text_field($_GET['delete_sale']);
            $flash_sales = array_filter($flash_sales, function($s) use ($sale_id) {
                return $s['id'] !== $sale_id;
            });
            update_option('petshop_flash_sales', array_values($flash_sales));
            echo '<div class="notice notice-success"><p>Đã xóa Flash Sale!</p></div>';
        }
    }
    
    // Lấy danh sách sản phẩm
    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .flash-wrap { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
    .flash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .flash-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .card-body { padding: 25px; }
    
    .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .form-group { margin-bottom: 0; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .form-group.full { grid-column: 1 / -1; }
    
    .product-selector { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; }
    .product-item { display: flex; align-items: center; gap: 8px; padding: 6px; border-radius: 4px; }
    .product-item:hover { background: #f8f9fa; }
    .product-item input { width: 16px; height: 16px; }
    
    .sale-table { width: 100%; border-collapse: collapse; }
    .sale-table th, .sale-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
    .sale-table th { background: #f8f9fa; font-weight: 600; }
    .sale-table tr:hover { background: #fafafa; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge-active { background: #d4edda; color: #155724; }
    .badge-scheduled { background: #cce5ff; color: #004085; }
    .badge-ended { background: #f8d7da; color: #721c24; }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    .btn-danger { background: #dc3545; color: #fff; }
    .btn-danger:hover { background: #c82333; color: #fff; }
    
    @media (max-width: 992px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    </style>
    
    <div class="flash-wrap">
        <div class="flash-header">
            <h1><i class="bi bi-lightning"></i> Flash Sale</h1>
        </div>
        
        <!-- Form tạo mới -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-plus-circle" style="color: #EC802B;"></i> Tạo Flash Sale mới</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php wp_nonce_field('petshop_flash_sale'); ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tên Flash Sale</label>
                            <input type="text" name="sale_name" required placeholder="VD: Flash Sale cuối tuần">
                        </div>
                        <div class="form-group">
                            <label>Giảm giá</label>
                            <input type="number" name="sale_discount" required min="0" step="0.01" placeholder="10">
                        </div>
                        <div class="form-group">
                            <label>Loại giảm</label>
                            <select name="sale_discount_type">
                                <option value="percent">Phần trăm (%)</option>
                                <option value="fixed">Số tiền (đ)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="add_flash_sale" class="btn btn-primary" style="width: 100%;">
                                <i class="bi bi-lightning"></i> Tạo Flash Sale
                            </button>
                        </div>
                        <div class="form-group">
                            <label>Thời gian bắt đầu</label>
                            <input type="datetime-local" name="sale_start" required>
                        </div>
                        <div class="form-group">
                            <label>Thời gian kết thúc</label>
                            <input type="datetime-local" name="sale_end" required>
                        </div>
                        <div class="form-group full">
                            <label>Chọn sản phẩm áp dụng</label>
                            <div class="product-selector">
                                <?php foreach ($products as $product): ?>
                                <label class="product-item">
                                    <input type="checkbox" name="sale_products[]" value="<?php echo $product->ID; ?>">
                                    <?php echo esc_html($product->post_title); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Danh sách Flash Sale -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-list-ul" style="color: #EC802B;"></i> Danh sách Flash Sale</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($flash_sales)): ?>
                <p style="text-align: center; color: #888; padding: 40px;">
                    <i class="bi bi-lightning" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                    Chưa có Flash Sale nào
                </p>
                <?php else: ?>
                <table class="sale-table">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Giảm giá</th>
                            <th>Thời gian</th>
                            <th>Sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flash_sales as $sale): 
                            $now = current_time('timestamp');
                            $start = strtotime($sale['start_time']);
                            $end = strtotime($sale['end_time']);
                            
                            if ($now < $start) {
                                $status = 'scheduled';
                                $status_label = 'Chờ bắt đầu';
                            } elseif ($now > $end) {
                                $status = 'ended';
                                $status_label = 'Đã kết thúc';
                            } else {
                                $status = 'active';
                                $status_label = 'Đang diễn ra';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($sale['name']); ?></strong></td>
                            <td>
                                <?php 
                                if ($sale['discount_type'] === 'percent') {
                                    echo '-' . intval($sale['discount']) . '%';
                                } else {
                                    echo '-' . number_format($sale['discount']) . 'đ';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', $start); ?><br>
                                <small style="color: #888;">→ <?php echo date('d/m/Y H:i', $end); ?></small>
                            </td>
                            <td><?php echo count($sale['products'] ?? array()); ?> sản phẩm</td>
                            <td><span class="badge badge-<?php echo $status; ?>"><?php echo $status_label; ?></span></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=petshop-flash-sale&delete_sale=' . $sale['id']), 'delete_flash_sale'); ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận xóa?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// =============================================
// TRANG CÀI ĐẶT KHUYẾN MÃI
// =============================================
function petshop_promotion_settings_page() {
    // Lấy cài đặt
    $settings = get_option('petshop_promotion_settings', array(
        'show_discount_badge' => true,
        'badge_text' => 'SALE',
        'badge_color' => '#dc3545',
        'show_original_price' => true,
        'countdown_enabled' => true,
        'stack_coupons' => false,
        'max_coupons_per_order' => 1,
        'auto_apply_best' => true,
    ));
    
    // Xử lý lưu
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        check_admin_referer('petshop_promotion_settings');
        
        $settings = array(
            'show_discount_badge' => isset($_POST['show_discount_badge']),
            'badge_text' => sanitize_text_field($_POST['badge_text'] ?? 'SALE'),
            'badge_color' => sanitize_hex_color($_POST['badge_color'] ?? '#dc3545'),
            'show_original_price' => isset($_POST['show_original_price']),
            'countdown_enabled' => isset($_POST['countdown_enabled']),
            'stack_coupons' => isset($_POST['stack_coupons']),
            'max_coupons_per_order' => intval($_POST['max_coupons_per_order'] ?? 1),
            'auto_apply_best' => isset($_POST['auto_apply_best']),
        );
        
        update_option('petshop_promotion_settings', $settings);
        echo '<div class="notice notice-success"><p>Đã lưu cài đặt!</p></div>';
    }
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
    .settings-wrap { max-width: 800px; margin: 20px auto; padding: 0 20px; }
    .settings-header { margin-bottom: 25px; }
    .settings-header h1 { display: flex; align-items: center; gap: 10px; font-size: 24px; margin: 0; }
    
    .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .card-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .card-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .card-body { padding: 25px; }
    
    .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
    .setting-row:last-child { border-bottom: none; }
    .setting-info h4 { margin: 0 0 4px; font-size: 14px; }
    .setting-info p { margin: 0; font-size: 12px; color: #888; }
    .setting-control { }
    .setting-control input[type="text"], .setting-control input[type="number"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 150px; }
    .setting-control input[type="color"] { width: 50px; height: 36px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; }
    
    .switch { position: relative; width: 50px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 26px; transition: 0.3s; }
    .switch .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
    .switch input:checked + .slider { background: #28a745; }
    .switch input:checked + .slider:before { transform: translateX(24px); }
    
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: #EC802B; color: #fff; }
    .btn-primary:hover { background: #d6701f; }
    </style>
    
    <div class="settings-wrap">
        <div class="settings-header">
            <h1><i class="bi bi-gear"></i> Cài đặt Khuyến mãi</h1>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('petshop_promotion_settings'); ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-badge-ad" style="color: #EC802B;"></i> Hiển thị giảm giá</h3>
                </div>
                <div class="card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Hiển thị badge giảm giá</h4>
                            <p>Hiển thị nhãn SALE trên sản phẩm đang giảm giá</p>
                        </div>
                        <div class="setting-control">
                            <label class="switch">
                                <input type="checkbox" name="show_discount_badge" <?php checked($settings['show_discount_badge']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Nội dung badge</h4>
                            <p>Text hiển thị trên badge giảm giá</p>
                        </div>
                        <div class="setting-control">
                            <input type="text" name="badge_text" value="<?php echo esc_attr($settings['badge_text']); ?>">
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Màu badge</h4>
                            <p>Màu nền của badge giảm giá</p>
                        </div>
                        <div class="setting-control">
                            <input type="color" name="badge_color" value="<?php echo esc_attr($settings['badge_color']); ?>">
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Hiển thị giá gốc</h4>
                            <p>Hiển thị giá gốc bị gạch ngang bên cạnh giá khuyến mãi</p>
                        </div>
                        <div class="setting-control">
                            <label class="switch">
                                <input type="checkbox" name="show_original_price" <?php checked($settings['show_original_price']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Đồng hồ đếm ngược</h4>
                            <p>Hiển thị countdown cho Flash Sale</p>
                        </div>
                        <div class="setting-control">
                            <label class="switch">
                                <input type="checkbox" name="countdown_enabled" <?php checked($settings['countdown_enabled']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-ticket-perforated" style="color: #EC802B;"></i> Mã giảm giá</h3>
                </div>
                <div class="card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Cho phép dùng nhiều mã</h4>
                            <p>Khách hàng có thể dùng nhiều mã giảm giá trong 1 đơn</p>
                        </div>
                        <div class="setting-control">
                            <label class="switch">
                                <input type="checkbox" name="stack_coupons" <?php checked($settings['stack_coupons']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Số mã tối đa/đơn</h4>
                            <p>Giới hạn số mã giảm giá trong 1 đơn hàng</p>
                        </div>
                        <div class="setting-control">
                            <input type="number" name="max_coupons_per_order" value="<?php echo intval($settings['max_coupons_per_order']); ?>" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <h4>Tự động áp dụng mã tốt nhất</h4>
                            <p>Hệ thống tự chọn mã giảm giá có lợi nhất cho khách</p>
                        </div>
                        <div class="setting-control">
                            <label class="switch">
                                <input type="checkbox" name="auto_apply_best" <?php checked($settings['auto_apply_best']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_settings" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Lưu cài đặt
            </button>
        </form>
    </div>
    <?php
}
