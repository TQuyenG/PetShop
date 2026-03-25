<?php
/**
 * Template Name: Trang Tài Khoản
 * 
 * @package PetShop
 */

// Redirect to custom login if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/dang-nhap/?redirect_to=' . urlencode(home_url('/tai-khoan/'))));
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$user_phone = get_user_meta($user_id, 'petshop_phone', true);
$addresses = petshop_get_user_addresses($user_id);
$default_address_id = get_user_meta($user_id, 'petshop_default_address_id', true);
$user_orders = function_exists('petshop_get_user_orders') ? petshop_get_user_orders($user_id) : array();

// Lấy thống kê tier của khách hàng
$customer_stats = function_exists('petshop_crm_get_customer_stats') ? petshop_crm_get_customer_stats($user_id) : array(
    'total_orders' => 0,
    'total_spent' => 0,
    'completed_orders' => 0,
    'tier' => 'bronze',
    'tier_label' => 'Đồng',
    'points' => 0
);

// Thông tin các tier
$tiers_info = array(
    'bronze' => array(
        'name' => 'Đồng',
        'icon' => 'bi-award',
        'color' => '#CD7F32',
        'min_spent' => 0,
        'next_tier' => 'silver',
        'next_threshold' => 3000000,
        'benefits' => array('Tích điểm 1% giá trị đơn hàng')
    ),
    'silver' => array(
        'name' => 'Bạc',
        'icon' => 'bi-gem',
        'color' => '#C0C0C0',
        'min_spent' => 3000000,
        'next_tier' => 'gold',
        'next_threshold' => 10000000,
        'benefits' => array('Tích điểm 1.5% giá trị đơn hàng', 'Ưu đãi sinh nhật')
    ),
    'gold' => array(
        'name' => 'Vàng',
        'icon' => 'bi-trophy',
        'color' => '#FFD700',
        'min_spent' => 10000000,
        'next_tier' => null,
        'next_threshold' => null,
        'benefits' => array('Tích điểm 2% giá trị đơn hàng', 'Ưu đãi sinh nhật', 'Miễn phí vận chuyển', 'Ưu đãi độc quyền')
    )
);

// Tính tiến độ tier
$current_tier_info = $tiers_info[$customer_stats['tier']];
$total_spent = $customer_stats['total_spent'];

// Progress calculation
$tier_progress = 0;
$amount_to_next_tier = 0;
$next_tier_name = '';

if ($current_tier_info['next_tier']) {
    $current_min = $current_tier_info['min_spent'];
    $next_threshold = $current_tier_info['next_threshold'];
    $next_tier_name = $tiers_info[$current_tier_info['next_tier']]['name'];
    
    $tier_progress = (($total_spent - $current_min) / ($next_threshold - $current_min)) * 100;
    $tier_progress = min(100, max(0, $tier_progress));
    $amount_to_next_tier = max(0, $next_threshold - $total_spent);
} else {
    $tier_progress = 100;
}

// Check if coming from checkout page
$from_checkout = isset($_GET['from']) && $_GET['from'] === 'checkout';

$current_user = wp_get_current_user();

$user_roles = $current_user->roles;


get_header(); 
?>

<!-- Page Header -->
<div class="page-header" style="background: linear-gradient(135deg, #5D4E37 0%, #7A6B5A 100%);">
    <div class="container">
        <h1 style="color: #fff;"><i class="bi bi-person-circle"></i> Tài Khoản Của Tôi</h1>
        <p style="color: rgba(255,255,255,0.9);">Xin chào, <?php echo esc_html($user->display_name); ?>!</p>
    </div>
</div>

<section class="account-section" style="padding: 60px 0;">
    <div class="container">
        <?php petshop_breadcrumb(); ?>
        
        <!-- Back to Checkout Button (only show when from checkout) -->
        <?php if ($from_checkout) : ?>
        <div id="backToCheckoutBanner" style="background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 15px; padding: 20px 30px; margin: 30px 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="color: #fff;">
                <i class="bi bi-info-circle" style="font-size: 1.2rem;"></i>
                <span style="margin-left: 10px;">Bạn đang chọn địa chỉ giao hàng. Sau khi chọn xong, nhấn nút bên cạnh để quay lại thanh toán.</span>
            </div>
            <a href="<?php echo home_url('/thanh-toan/'); ?>" class="btn" style="background: #fff; color: #EC802B; padding: 12px 25px; font-weight: 600;">
                <i class="bi bi-arrow-left"></i> Quay lại Thanh Toán
            </a>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; margin-top: 30px;">
            <!-- Sidebar Menu -->
            <div class="account-sidebar">
                <div style="background: #fff; border-radius: 20px; padding: 25px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <div style="text-align: center; padding-bottom: 20px; border-bottom: 2px solid #FDF8F3; margin-bottom: 20px;">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, <?php echo $current_tier_info['color']; ?>, <?php echo $current_tier_info['color']; ?>cc); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; border: 3px solid <?php echo $current_tier_info['color']; ?>;">
                            <i class="bi <?php echo $current_tier_info['icon']; ?>"></i>
                        </div>
                        <h4 style="margin: 0 0 5px;"><?php echo esc_html($user->display_name); ?></h4>
                        <p style="color: #7A6B5A; font-size: 0.9rem; margin: 0 0 8px;"><?php echo esc_html($user->user_email); ?></p>
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: <?php echo $current_tier_info['color']; ?>20; color: <?php echo $current_tier_info['color']; ?>; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem; font-weight: 600;">
                            <i class="bi <?php echo $current_tier_info['icon']; ?>"></i>
                            Hạng <?php echo $customer_stats['tier_label']; ?>
                        </span>
                    </div>
                    
                    <nav class="account-nav">
                        <a href="#membership" class="account-nav-item" data-section="membership" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s; background: <?php echo $current_tier_info['color']; ?>15;">
                            <i class="bi bi-award" style="color: <?php echo $current_tier_info['color']; ?>;"></i> 
                            <span>Hạng thành viên</span>
                            <span style="background: <?php echo $current_tier_info['color']; ?>; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: auto;"><?php echo $customer_stats['tier_label']; ?></span>
                        </a>
                        <a href="#profile" class="account-nav-item active" data-section="profile" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-person"></i> Thông tin cá nhân
                        </a>
                        <a href="#addresses" class="account-nav-item" data-section="addresses" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-geo-alt"></i> Địa chỉ giao hàng
                        </a>
                        <a href="#orders" class="account-nav-item" data-section="orders" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-bag"></i> Đơn hàng của tôi
                        </a>
                        <a href="#favorites" class="account-nav-item" data-section="favorites" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-heart"></i> Sản phẩm yêu thích
                        </a>
                        <a href="#reviews" class="account-nav-item" data-section="reviews" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-star"></i> Đánh giá của tôi
                        </a>
                        <?php 
                        $unread_notifications = function_exists('petshop_count_unread_notifications') 
                            ? petshop_count_unread_notifications($user_id) : 0;
                        ?>
                        <a href="#notifications" class="account-nav-item" data-section="notifications" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-bell"></i> Thông báo
                            <?php if ($unread_notifications > 0) : ?>
                            <span style="background: #f44336; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: auto;"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if (function_exists('petshop_is_main_account') && (petshop_is_main_account($user_id) || petshop_is_sub_account($user_id))): ?>
                        <a href="#subaccounts" class="account-nav-item" data-section="subaccounts" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #5D4E37; text-decoration: none; margin-bottom: 5px; transition: all 0.3s;">
                            <i class="bi bi-people"></i> Tài khoản phụ
                            <?php 
                            if (petshop_is_main_account($user_id)) {
                                $sub_count = count(petshop_get_sub_accounts($user_id));
                                if ($sub_count > 0): ?>
                            <span style="background: #9C27B0; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: auto;"><?php echo $sub_count; ?></span>
                            <?php endif; } ?>
                        </a>
                        <?php endif; ?>
                        <hr style="border: none; border-top: 1px solid #FDF8F3; margin: 15px 0;">
                        <a href="<?php echo wp_logout_url(home_url('/')); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; color: #d9534f; text-decoration: none; transition: all 0.3s;">
                            <i class="bi bi-box-arrow-right"></i> Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="account-content">
                <!-- Membership Section -->
                <div id="section-membership" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-award" style="color: <?php echo $current_tier_info['color']; ?>;"></i> Hạng thành viên
                    </h3>
                    
                    <!-- Current Tier Card -->
                    <div style="background: linear-gradient(135deg, <?php echo $current_tier_info['color']; ?>20, <?php echo $current_tier_info['color']; ?>10); border: 2px solid <?php echo $current_tier_info['color']; ?>; border-radius: 20px; padding: 30px; margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, <?php echo $current_tier_info['color']; ?>, <?php echo $current_tier_info['color']; ?>cc); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2.5rem;">
                                    <i class="bi <?php echo $current_tier_info['icon']; ?>"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; color: #7A6B5A; font-size: 0.9rem;">Hạng hiện tại</p>
                                    <h2 style="margin: 5px 0; color: <?php echo $current_tier_info['color']; ?>;">Hạng <?php echo $customer_stats['tier_label']; ?></h2>
                                    <p style="margin: 0; color: #5D4E37;">
                                        <i class="bi bi-coin"></i> <strong><?php echo number_format($customer_stats['points']); ?></strong> điểm tích lũy
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <p style="margin: 0; color: #7A6B5A; font-size: 0.9rem;">Tổng chi tiêu</p>
                                <h3 style="margin: 5px 0; color: #EC802B;"><?php echo number_format($total_spent); ?>đ</h3>
                                <p style="margin: 0; color: #5D4E37;">
                                    <i class="bi bi-bag-check"></i> <?php echo $customer_stats['completed_orders']; ?> đơn hoàn thành
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($current_tier_info['next_tier']): ?>
                        <!-- Progress to Next Tier -->
                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid <?php echo $current_tier_info['color']; ?>40;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="color: #5D4E37; font-weight: 600;">
                                    <i class="bi bi-arrow-up-circle" style="color: <?php echo $tiers_info[$current_tier_info['next_tier']]['color']; ?>;"></i>
                                    Tiến độ lên hạng <?php echo $next_tier_name; ?>
                                </span>
                                <span style="color: <?php echo $current_tier_info['color']; ?>; font-weight: 700;">
                                    <?php echo round($tier_progress, 1); ?>%
                                </span>
                            </div>
                            <div style="height: 12px; background: #f0f0f0; border-radius: 6px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $tier_progress; ?>%; background: linear-gradient(90deg, <?php echo $current_tier_info['color']; ?>, <?php echo $tiers_info[$current_tier_info['next_tier']]['color']; ?>); border-radius: 6px; transition: width 0.5s ease;"></div>
                            </div>
                            <p style="margin: 12px 0 0; color: #5D4E37; font-size: 0.95rem;">
                                Còn <strong style="color: #EC802B;"><?php echo number_format($amount_to_next_tier); ?>đ</strong> nữa để lên hạng <strong style="color: <?php echo $tiers_info[$current_tier_info['next_tier']]['color']; ?>;"><?php echo $next_tier_name; ?></strong>
                            </p>
                        </div>
                        <?php else: ?>
                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid <?php echo $current_tier_info['color']; ?>40; text-align: center;">
                            <p style="margin: 0; color: #28a745; font-weight: 600; font-size: 1.1rem;">
                                <i class="bi bi-trophy-fill"></i> Chúc mừng! Bạn đã đạt hạng cao nhất!
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Current Tier Benefits -->
                    <div style="background: #FDF8F3; border-radius: 15px; padding: 25px; margin-bottom: 30px;">
                        <h4 style="margin: 0 0 15px; color: #5D4E37; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-gift" style="color: #EC802B;"></i> Ưu đãi hạng <?php echo $customer_stats['tier_label']; ?>
                        </h4>
                        <ul style="margin: 0; padding-left: 0; list-style: none;">
                            <?php foreach ($current_tier_info['benefits'] as $benefit): ?>
                            <li style="padding: 8px 0; color: #5D4E37; display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-check-circle-fill" style="color: #28a745;"></i>
                                <?php echo esc_html($benefit); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- All Tiers Overview -->
                    <h4 style="margin: 0 0 20px; color: #5D4E37;">
                        <i class="bi bi-bar-chart-steps" style="color: #EC802B;"></i> Các hạng thành viên
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <?php foreach ($tiers_info as $tier_key => $tier): 
                            $is_current = ($tier_key === $customer_stats['tier']);
                            $is_achieved = $total_spent >= $tier['min_spent'];
                        ?>
                        <div style="border: 2px solid <?php echo $is_current ? $tier['color'] : '#e0e0e0'; ?>; border-radius: 15px; padding: 20px; <?php echo $is_current ? 'background: ' . $tier['color'] . '10;' : ''; ?> position: relative; opacity: <?php echo $is_achieved ? '1' : '0.6'; ?>;">
                            <?php if ($is_current): ?>
                            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: <?php echo $tier['color']; ?>; color: #fff; padding: 3px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;">
                                Hạng của bạn
                            </span>
                            <?php endif; ?>
                            
                            <div style="text-align: center; margin-bottom: 15px;">
                                <div style="width: 50px; height: 50px; background: <?php echo $tier['color']; ?>20; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi <?php echo $tier['icon']; ?>" style="font-size: 1.5rem; color: <?php echo $tier['color']; ?>;"></i>
                                </div>
                                <h5 style="margin: 0; color: <?php echo $tier['color']; ?>;">Hạng <?php echo $tier['name']; ?></h5>
                                <p style="margin: 5px 0 0; font-size: 0.85rem; color: #7A6B5A;">
                                    <?php if ($tier['min_spent'] == 0): ?>
                                        Hạng khởi đầu
                                    <?php else: ?>
                                        Từ <?php echo number_format($tier['min_spent']); ?>đ
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <ul style="margin: 0; padding: 0; list-style: none; font-size: 0.85rem;">
                                <?php foreach (array_slice($tier['benefits'], 0, 3) as $benefit): ?>
                                <li style="padding: 4px 0; color: #5D4E37; display: flex; align-items: flex-start; gap: 6px;">
                                    <i class="bi bi-check" style="color: <?php echo $tier['color']; ?>; flex-shrink: 0; margin-top: 2px;"></i>
                                    <span><?php echo esc_html($benefit); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Call to Action -->
                    <?php if ($current_tier_info['next_tier']): ?>
                    <div style="margin-top: 30px; text-align: center; padding: 25px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 15px; color: #fff;">
                        <p style="margin: 0 0 15px; font-size: 1.1rem;">
                            <i class="bi bi-bag-heart"></i> Mua sắm thêm <strong><?php echo number_format($amount_to_next_tier); ?>đ</strong> để lên hạng <?php echo $next_tier_name; ?>!
                        </p>
                        <a href="<?php echo home_url('/san-pham/'); ?>" class="btn" style="background: #fff; color: #EC802B; padding: 12px 30px; font-weight: 600;">
                            <i class="bi bi-bag"></i> Mua sắm ngay
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Section -->
                <div id="section-profile" class="account-section-content" style="background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-person" style="color: #EC802B;"></i> Thông tin cá nhân
                    </h3>
                    
                    <form id="profileForm">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Họ và tên</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" 
                                       style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;">
                            </div>
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Số điện thoại</label>
                                <input type="tel" name="phone" value="<?php echo esc_attr($user_phone); ?>" 
                                       style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif;"
                                       placeholder="0909 xxx xxx">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Email</label>
                            <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled
                                   style="width: 100%; padding: 14px 18px; border: 2px solid #E8CCAD; border-radius: 12px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #f9f9f9; color: #999;">
                            <small style="color: #7A6B5A;">Email không thể thay đổi</small>
                        </div>
                        
                        <div style="margin-top: 25px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Lưu thay đổi
                            </button>
                        </div>
                        
                        <div id="profileMessage" style="margin-top: 15px;"></div>
                    </form>
                </div>
                
                <!-- Addresses Section -->
                <div id="section-addresses" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-geo-alt" style="color: #EC802B;"></i> Địa chỉ giao hàng
                        </h3>
                        <button type="button" id="addAddressBtn" class="btn btn-primary" style="padding: 10px 20px;">
                            <i class="bi bi-plus-lg"></i> Thêm địa chỉ
                        </button>
                    </div>
                    
                    <!-- Address List -->
                    <div id="addressList">
                        <?php if (empty($addresses)) : ?>
                        <div id="noAddressMsg" style="text-align: center; padding: 40px; background: #FDF8F3; border-radius: 15px;">
                            <i class="bi bi-geo-alt" style="font-size: 3rem; color: #E8CCAD;"></i>
                            <p style="color: #7A6B5A; margin-top: 15px;">Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ giao hàng!</p>
                        </div>
                        <?php else : ?>
                            <?php foreach ($addresses as $addr) : 
                                $is_default = ($addr['id'] === $default_address_id);
                            ?>
                            <div class="address-card" data-address-id="<?php echo esc_attr($addr['id']); ?>" 
                                 style="border: 2px solid <?php echo $is_default ? '#EC802B' : '#E8CCAD'; ?>; border-radius: 15px; padding: 20px; margin-bottom: 15px; position: relative; <?php echo $is_default ? 'background: rgba(236, 128, 43, 0.05);' : ''; ?>">
                                
                                <?php if ($is_default) : ?>
                                <span style="position: absolute; top: -10px; left: 20px; background: #EC802B; color: #fff; padding: 3px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="bi bi-check-circle"></i> Mặc định
                                </span>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <strong style="color: #5D4E37;"><?php echo esc_html($addr['fullname']); ?></strong>
                                            <span style="color: #7A6B5A;">|</span>
                                            <span style="color: #7A6B5A;"><?php echo esc_html($addr['phone']); ?></span>
                                            <?php if (!empty($addr['label'])) : ?>
                                            <span style="background: #FDF8F3; padding: 2px 10px; border-radius: 5px; font-size: 0.8rem; color: #7A6B5A;">
                                                <?php echo esc_html($addr['label']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="color: #666; margin: 0; line-height: 1.6;">
                                            <?php echo esc_html($addr['address']); ?><br>
                                            <?php echo esc_html($addr['ward_text'] . ', ' . $addr['district_text'] . ', ' . $addr['city_text']); ?>
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if (!$is_default) : ?>
                                        <button type="button" class="set-default-btn" data-id="<?php echo esc_attr($addr['id']); ?>" 
                                                style="background: none; border: 1px solid #66BCB4; color: #66BCB4; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                                            Đặt mặc định
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="edit-address-btn" data-id="<?php echo esc_attr($addr['id']); ?>"
                                                style="background: none; border: 1px solid #E8CCAD; color: #7A6B5A; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="delete-address-btn" data-id="<?php echo esc_attr($addr['id']); ?>"
                                                style="background: none; border: 1px solid #ffcccc; color: #d9534f; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Address Form (Hidden by default) -->
                    <div id="addressFormWrapper" style="display: none; margin-top: 20px; padding: 25px; background: #FDF8F3; border-radius: 15px;">
                        <h4 id="addressFormTitle" style="margin-bottom: 20px;"><i class="bi bi-plus-circle" style="color: #EC802B;"></i> Thêm địa chỉ mới</h4>
                        
                        <form id="addressForm">
                            <input type="hidden" name="address_id" id="addressId" value="">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Họ tên *</label>
                                    <input type="text" name="fullname" id="addrFullname" required
                                           style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Số điện thoại *</label>
                                    <input type="tel" name="phone" id="addrPhone" required
                                           style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Nhãn</label>
                                    <select name="label" id="addrLabel"
                                            style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">-- Chọn nhãn --</option>
                                        <option value="Nhà riêng">Nhà riêng</option>
                                        <option value="Văn phòng">Văn phòng</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Tỉnh/Thành phố *</label>
                                    <select name="city" id="addrCity" required
                                            style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn tỉnh/thành</option>
                                        <option value="hcm">TP. Hồ Chí Minh</option>
                                        <option value="hn">Hà Nội</option>
                                        <option value="dn">Đà Nẵng</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Quận/Huyện *</label>
                                    <select name="district" id="addrDistrict" required
                                            style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn quận/huyện</option>
                                        <option value="q1">Quận 1</option>
                                        <option value="q2">Quận 2</option>
                                        <option value="q3">Quận 3</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Phường/Xã *</label>
                                    <select name="ward" id="addrWard" required
                                            style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif; background: #fff;">
                                        <option value="">Chọn phường/xã</option>
                                        <option value="p1">Phường Bến Nghé</option>
                                        <option value="p2">Phường Bến Thành</option>
                                        <option value="p3">Phường Cầu Kho</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Địa chỉ cụ thể *</label>
                                <input type="text" name="address" id="addrAddress" required placeholder="Số nhà, tên đường..."
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-size: 1rem; font-family: 'Quicksand', sans-serif;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="is_default" id="addrIsDefault" style="width: 18px; height: 18px; accent-color: #EC802B;">
                                    <span style="color: #5D4E37;">Đặt làm địa chỉ mặc định</span>
                                </label>
                            </div>
                            
                            <div style="display: flex; gap: 15px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Lưu địa chỉ
                                </button>
                                <button type="button" id="cancelAddressBtn" class="btn btn-outline">
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="addressMessage" style="margin-top: 15px;"></div>
                </div>
                
                <!-- Orders Section -->
                <div id="section-orders" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <!-- Header + bộ lọc -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                        <h3 style="margin:0;display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-bag" style="color:#EC802B;"></i> Đơn hàng của tôi
                        </h3>
                    </div>

                    <!-- Thanh tìm kiếm + lọc trạng thái -->
                    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
                        <input type="text" id="orderSearch" placeholder="🔍 Tìm mã đơn, tên sản phẩm..."
                               oninput="filterOrders()"
                               style="flex:1;min-width:200px;padding:10px 15px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:0.9rem;color:#5D4E37;outline:none;">
                        <select id="orderStatusFilter" onchange="filterOrders()"
                                style="padding:10px 15px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:0.9rem;color:#5D4E37;background:#fff;cursor:pointer;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="shipping">Đang giao hàng</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>

                    <?php if (empty($user_orders)) : ?>
                    <div style="text-align: center; padding: 40px; background: #FDF8F3; border-radius: 15px;">
                        <i class="bi bi-bag-x" style="font-size: 3rem; color: #E8CCAD;"></i>
                        <p style="color: #7A6B5A; margin-top: 15px;">Bạn chưa có đơn hàng nào.</p>
                        <a href="<?php echo home_url('/san-pham/'); ?>" class="btn btn-primary" style="margin-top: 10px;">
                            <i class="bi bi-bag"></i> Mua sắm ngay
                        </a>
                    </div>
                    <?php else : ?>
                    <div class="orders-list" id="ordersList">
                        <?php foreach ($user_orders as $order) : 
                            $order_code    = get_post_meta($order->ID, 'order_code', true);
                            $order_total   = get_post_meta($order->ID, 'order_total', true);
                            $order_status  = get_post_meta($order->ID, 'order_status', true);
                            $order_date    = get_post_meta($order->ID, 'order_date', true);
                            $cart_items    = json_decode(get_post_meta($order->ID, 'cart_items', true), true);
                            $coupon_code   = get_post_meta($order->ID, 'coupon_code', true);
                            $payment_method = get_post_meta($order->ID, 'payment_method', true);
                            $has_coupon    = !empty($coupon_code);
                            $is_qr         = ($payment_method === 'qr' || $payment_method === 'bank');
                            $shop_email_c  = $shop_settings['shop_email'] ?? 'support@petshop.com';
                            
                            $status_labels = array(
                                'pending'    => array('label' => 'Chờ xử lý',     'color' => '#f0ad4e', 'bg' => '#fff8e6'),
                                'processing' => array('label' => 'Đang xử lý',    'color' => '#5bc0de', 'bg' => '#e6f7ff'),
                                'shipping'   => array('label' => 'Đang giao hàng','color' => '#17a2b8', 'bg' => '#d1ecf1'),
                                'completed'  => array('label' => 'Hoàn thành',    'color' => '#5cb85c', 'bg' => '#e6ffe6'),
                                'cancelled'  => array('label' => 'Đã hủy',        'color' => '#d9534f', 'bg' => '#ffe6e6'),
                            );
                            $status_info = $status_labels[$order_status] ?? $status_labels['pending'];

                            // Gộp tên sản phẩm để dùng cho tìm kiếm
                            $item_names = '';
                            if (is_array($cart_items)) {
                                $item_names = implode(' ', array_column($cart_items, 'name'));
                            }
                        ?>
                        <div class="order-card"
                             data-status="<?php echo esc_attr($order_status); ?>"
                             data-search="<?php echo esc_attr(strtolower($order_code . ' ' . $item_names)); ?>"
                             style="border:1px solid #E8CCAD;border-radius:15px;margin-bottom:15px;overflow:hidden;">

                            <!-- Header card -->
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:15px 20px;background:#FDF8F3;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <strong style="color:#EC802B;">#<?php echo esc_html($order_code); ?></strong>
                                    <span style="color:#7A6B5A;margin-left:15px;"><?php echo $order_date ? date('d/m/Y H:i', strtotime($order_date)) : '-'; ?></span>
                                </div>
                                <span style="padding:5px 15px;border-radius:20px;font-size:0.85rem;background:<?php echo $status_info['bg']; ?>;color:<?php echo $status_info['color']; ?>;">
                                    <?php echo $status_info['label']; ?>
                                </span>
                            </div>

                            <!-- Body card -->
                            <div style="padding:20px;">
                                <?php if ($is_qr && $order_status === 'pending') : ?>
                                <div style="background:#fff8e1;border-left:4px solid #f0ad4e;border-radius:8px;padding:12px 16px;margin-bottom:15px;font-size:0.9rem;color:#856404;">
                                    <i class="bi bi-qr-code"></i> <strong>Thanh toán QR:</strong>
                                    Vui lòng copy mã đơn hàng <strong><?php echo esc_html($order_code); ?></strong> và liên hệ
                                    <a href="mailto:<?php echo esc_attr($shop_email_c); ?>" style="color:#EC802B;"><?php echo esc_html($shop_email_c); ?></a>
                                    để hoàn tất xác nhận chuyển khoản và được hoàn trả nếu cần.
                                </div>
                                <?php endif; ?>

                                <?php if (is_array($cart_items)) : ?>
                                    <?php foreach (array_slice($cart_items, 0, 2) as $item) : ?>
                                    <div style="display:flex;align-items:center;gap:15px;margin-bottom:10px;">
                                        <?php if (!empty($item['image'])) : ?>
                                        <img src="<?php echo esc_url($item['image']); ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                        <?php endif; ?>
                                        <div style="flex:1;">
                                            <p style="margin:0;color:#5D4E37;"><?php echo esc_html($item['name']); ?></p>
                                            <small style="color:#7A6B5A;">x<?php echo intval($item['quantity']); ?></small>
                                        </div>
                                        <span style="color:#EC802B;font-weight:600;"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (count($cart_items) > 2) : ?>
                                    <p style="color:#7A6B5A;font-size:0.9rem;margin:10px 0 0;">+ <?php echo count($cart_items) - 2; ?> sản phẩm khác</p>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid #F5EDE0;margin-top:15px;">
                                    <span style="color:#7A6B5A;">Tổng tiền:</span>
                                    <strong style="color:#EC802B;font-size:1.1rem;"><?php echo number_format($order_total, 0, ',', '.'); ?>đ</strong>
                                </div>

                                <!-- Action buttons -->
                                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:15px;flex-wrap:wrap;">
                                    <!-- Xem chi tiết -->
                                    <button onclick="viewOrderDetail(<?php echo $order->ID; ?>)"
                                            style="padding:8px 18px;border:1.5px solid #EC802B;border-radius:8px;background:#fff;color:#EC802B;font-weight:600;cursor:pointer;font-size:0.88rem;">
                                        <i class="bi bi-eye"></i> Xem chi tiết
                                    </button>

                                    <!-- Đánh giá: chỉ active khi completed -->
                                    <?php if ($order_status === 'completed') : ?>
                                    <a href="<?php echo home_url('/danh-gia/?order_id=' . $order->ID); ?>"
                                       style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;font-weight:600;text-decoration:none;font-size:0.88rem;display:inline-flex;align-items:center;gap:6px;">
                                        <i class="bi bi-star"></i> Đánh giá
                                    </a>
                                    <?php else : ?>
                                    <button disabled title="Chỉ đánh giá được khi đơn hàng Hoàn thành"
                                            style="padding:8px 18px;border-radius:8px;background:#eee;color:#aaa;font-weight:600;font-size:0.88rem;border:none;cursor:not-allowed;">
                                        <i class="bi bi-star"></i> Đánh giá
                                    </button>
                                    <?php endif; ?>

                                    <!-- Hủy đơn: chỉ khi pending -->
                                    <?php if ($order_status === 'pending') : ?>
                                    <button onclick="confirmCancelOrder(<?php echo $order->ID; ?>, '<?php echo esc_js($order_code); ?>', <?php echo $has_coupon ? 'true' : 'false'; ?>)"
                                            style="padding:8px 18px;border:1.5px solid #d9534f;border-radius:8px;background:#fff;color:#d9534f;font-weight:600;cursor:pointer;font-size:0.88rem;">
                                        <i class="bi bi-x-circle"></i> Hủy đơn
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="ordersEmpty" style="display:none;text-align:center;padding:30px;background:#FDF8F3;border-radius:12px;color:#7A6B5A;">
                        Không tìm thấy đơn hàng phù hợp.
                    </div>
                    <?php endif; ?>

                    <!-- Modal xem chi tiết đơn hàng -->
                    <div id="orderDetailModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:10000;justify-content:center;align-items:center;padding:20px;">
                        <div style="background:#fff;border-radius:20px;max-width:700px;width:100%;max-height:90vh;overflow-y:auto;position:relative;">
                            <button onclick="closeOrderDetail()" style="position:absolute;top:15px;right:15px;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#7A6B5A;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <div id="orderDetailContent" style="padding:30px;">
                                <div style="text-align:center;padding:40px;">
                                    <i class="bi bi-arrow-repeat" style="font-size:2rem;animation:spin 1s linear infinite;"></i>
                                    <p style="margin-top:10px;color:#7A6B5A;">Đang tải...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Popup xác nhận hủy đơn -->
                    <div id="cancelOrderModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:10001;justify-content:center;align-items:center;padding:20px;">
                        <div style="background:#fff;border-radius:20px;max-width:440px;width:100%;padding:35px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                            <div style="width:70px;height:70px;background:#ffe6e6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                                <i class="bi bi-x-circle-fill" style="font-size:2.2rem;color:#d9534f;"></i>
                            </div>
                            <h3 style="color:#5D4E37;margin-bottom:10px;">Xác nhận hủy đơn hàng?</h3>
                            <p id="cancelOrderMsg" style="color:#7A6B5A;margin-bottom:10px;line-height:1.6;"></p>
                            <div id="cancelCouponWarn" style="display:none;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:10px 15px;margin-bottom:18px;font-size:0.88rem;color:#856404;text-align:left;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <strong>Lưu ý:</strong> Nếu hủy đơn hàng, bạn sẽ <strong>không được hoàn lại mã giảm giá</strong> đã dùng.
                            </div>
                            <div style="display:flex;gap:12px;justify-content:center;">
                                <button onclick="closeCancelModal()" style="padding:11px 28px;border:1.5px solid #E8CCAD;border-radius:25px;background:#fff;color:#7A6B5A;font-weight:600;cursor:pointer;">
                                    Giữ đơn hàng
                                </button>
                                <button id="confirmCancelBtn" onclick="executeCancelOrder()"
                                        style="padding:11px 28px;border:none;border-radius:25px;background:linear-gradient(135deg,#d9534f,#c9302c);color:#fff;font-weight:600;cursor:pointer;">
                                    Xác nhận hủy
                                </button>
                            </div>
                        </div>
                    </div>

                    <style>
                        @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
                    </style>

                    <script>
                    // Lọc đơn hàng client-side
                    function filterOrders() {
                        const search = (document.getElementById('orderSearch')?.value || '').toLowerCase();
                        const status = document.getElementById('orderStatusFilter')?.value || '';
                        const cards  = document.querySelectorAll('#ordersList .order-card');
                        let visible  = 0;
                        cards.forEach(card => {
                            const matchStatus = !status || card.dataset.status === status;
                            const matchSearch = !search || card.dataset.search.includes(search);
                            const show = matchStatus && matchSearch;
                            card.style.display = show ? '' : 'none';
                            if (show) visible++;
                        });
                        const empty = document.getElementById('ordersEmpty');
                        if (empty) empty.style.display = visible === 0 ? '' : 'none';
                    }

                    // Popup xác nhận hủy
                    let _cancelOrderId = 0;
                    function confirmCancelOrder(orderId, orderCode, hasCoupon) {
                        _cancelOrderId = orderId;
                        document.getElementById('cancelOrderMsg').textContent =
                            'Bạn có chắc muốn hủy đơn hàng #' + orderCode + '?';
                        document.getElementById('cancelCouponWarn').style.display = hasCoupon ? '' : 'none';
                        document.getElementById('cancelOrderModal').style.display = 'flex';
                    }
                    function closeCancelModal() {
                        document.getElementById('cancelOrderModal').style.display = 'none';
                    }
                    function executeCancelOrder() {
                        const btn = document.getElementById('confirmCancelBtn');
                        btn.disabled = true;
                        btn.textContent = 'Đang xử lý...';
                        fetch(window.PETSHOP_USER?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/x-www-form-urlencoded'},
                            body: 'action=petshop_cancel_order&order_id=' + _cancelOrderId
                        })
                        .then(r => r.json())
                        .then(data => {
                            closeCancelModal();
                            if (data.success) {
                                // Cập nhật badge trạng thái ngay mà không reload
                                const cards = document.querySelectorAll('#ordersList .order-card');
                                cards.forEach(card => {
                                    if (card.querySelector('[onclick*="' + _cancelOrderId + '"]')) {
                                        card.dataset.status = 'cancelled';
                                        const badge = card.querySelector('span[style*="border-radius:20px"]');
                                        if (badge) { badge.textContent = 'Đã hủy'; badge.style.background = '#ffe6e6'; badge.style.color = '#d9534f'; }
                                        // Ẩn nút hủy
                                        card.querySelectorAll('button').forEach(b => {
                                            if (b.textContent.includes('Hủy đơn')) b.remove();
                                        });
                                    }
                                });
                                alert('✅ ' + data.data.message);
                            } else {
                                alert('❌ ' + (data.data?.message || 'Có lỗi xảy ra!'));
                                btn.disabled = false;
                                btn.textContent = 'Xác nhận hủy';
                            }
                        })
                        .catch(() => {
                            closeCancelModal();
                            alert('❌ Có lỗi kết nối, vui lòng thử lại.');
                        });
                    }
                    </script>
                </div>
                
                <!-- Favorites Section -->
                <div id="section-favorites" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <?php
                    $fav_list = get_user_meta($user_id, 'petshop_favorites', true);
                    if (!is_array($fav_list)) $fav_list = array();
                    ?>

                    <!-- Header + bộ lọc -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
                        <h3 style="margin:0;display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-heart-fill" style="color:#EC802B;"></i>
                            Sản phẩm yêu thích
                            <?php if (!empty($fav_list)) : ?>
                            <span style="background:#EC802B;color:#fff;font-size:0.78rem;padding:2px 10px;border-radius:20px;font-weight:600;"><?php echo count($fav_list); ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($fav_list)) : ?>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <select id="fav-sort" onchange="favReload()" style="padding:8px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:0.88rem;color:#5D4E37;background:#fff;cursor:pointer;">
                                <option value="date_desc">Mới thêm trước</option>
                                <option value="date_asc">Cũ thêm trước</option>
                                <option value="name_asc">Tên A→Z</option>
                                <option value="name_desc">Tên Z→A</option>
                                <option value="price_asc">Giá thấp→cao</option>
                                <option value="price_desc">Giá cao→thấp</option>
                            </select>
                            <select id="fav-cat" onchange="favReload()" style="padding:8px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:0.88rem;color:#5D4E37;background:#fff;cursor:pointer;">
                                <option value="">Tất cả danh mục</option>
                                <?php
                                $fav_cats = get_terms(array('taxonomy'=>'product_category','hide_empty'=>false));
                                foreach ($fav_cats as $fc) {
                                    echo '<option value="'.esc_attr($fc->slug).'">'.esc_html($fc->name).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($fav_list)) : ?>
                    <!-- Empty state -->
                    <div style="text-align:center;padding:60px 20px;background:#FDF8F3;border-radius:15px;">
                        <i class="bi bi-heart" style="font-size:4rem;color:#E8CCAD;display:block;margin-bottom:15px;"></i>
                        <p style="color:#7A6B5A;font-size:1.05rem;margin-bottom:20px;">Bạn chưa có sản phẩm yêu thích nào.</p>
                        <a href="<?php echo home_url('/san-pham/'); ?>" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;padding:12px 28px;border-radius:25px;text-decoration:none;font-weight:600;">
                            <i class="bi bi-bag"></i> Khám phá sản phẩm
                        </a>
                    </div>

                    <?php else : ?>
                    <!-- Products grid -->
                    <div class="fav-products-grid" id="favGrid">
                        <?php
                        $fav_query = new WP_Query(array(
                            'post_type'      => 'product',
                            'post__in'       => $fav_list,
                            'orderby'        => 'post__in',
                            'posts_per_page' => -1,
                        ));
                        while ($fav_query->have_posts()) : $fav_query->the_post();
                            $fav_price      = get_post_meta(get_the_ID(), 'product_price', true);
                            $fav_sale_price = get_post_meta(get_the_ID(), 'product_sale_price', true);
                            $fav_stock      = get_post_meta(get_the_ID(), 'product_stock', true);
                            $fav_sku        = get_post_meta(get_the_ID(), 'product_sku', true);
                            $fav_price_info = function_exists('petshop_get_display_price')
                                ? petshop_get_display_price(get_the_ID())
                                : array('is_on_sale'=>false,'sale'=>0,'original'=>$fav_price,'discount_percent'=>0);
                            $fav_rating_raw = get_post_meta(get_the_ID(), 'product_rating', true);
                            $fav_rating     = $fav_rating_raw ? floatval($fav_rating_raw) : 0;
                            $fav_cats       = get_the_terms(get_the_ID(), 'product_category');
                            $fav_cat_slug   = ($fav_cats && !is_wp_error($fav_cats)) ? $fav_cats[0]->slug : '';
                            $fav_cat_name   = ($fav_cats && !is_wp_error($fav_cats)) ? $fav_cats[0]->name : '';
                            $fav_display_price = ($fav_price_info['is_on_sale'] && $fav_price_info['sale'])
                                ? $fav_price_info['sale']
                                : ($fav_price_info['original'] ?: 0);
                        ?>
                        <article class="product-card fav-card"
                                 data-cat="<?php echo esc_attr($fav_cat_slug); ?>"
                                 data-price="<?php echo intval($fav_display_price); ?>"
                                 data-name="<?php echo esc_attr(get_the_title()); ?>"
                                 data-date="<?php echo get_the_date('U'); ?>">

                            <div class="product-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'petshop-product'); ?>" alt="<?php the_title_attribute(); ?>">
                                    </a>
                                <?php else : ?>
                                    <a href="<?php the_permalink(); ?>" class="no-image">
                                        <i class="bi bi-box-seam"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($fav_price_info['is_on_sale'] && $fav_price_info['discount_percent']) : ?>
                                <span class="discount-badge">-<?php echo $fav_price_info['discount_percent']; ?>%</span>
                                <?php endif; ?>

                                <div class="product-actions">
                                    <!-- Bỏ yêu thích -->
                                    <button class="action-btn active fav-btn"
                                            title="Yêu thích"
                                            data-product-id="<?php echo get_the_ID(); ?>"
                                            data-is-favorited="1"
                                            onclick="toggleFavInAccount(this)"
                                            style="background:#FF6B6B;color:#fff;">❤️</button>
                                    <!-- Thêm vào giỏ -->
                                    <button class="action-btn primary add-to-cart-quick"
                                            title="Thêm vào giỏ"
                                            data-id="<?php echo get_the_ID(); ?>"
                                            data-name="<?php echo esc_attr(get_the_title()); ?>"
                                            data-price="<?php echo $fav_display_price; ?>"
                                            data-original-price="<?php echo $fav_price; ?>"
                                            data-image="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'petshop-product'); ?>"
                                            data-url="<?php the_permalink(); ?>"
                                            data-sku="<?php echo esc_attr($fav_sku); ?>">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                    <!-- Xem chi tiết -->
                                    <a href="<?php the_permalink(); ?>" class="action-btn" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="product-info">
                                <?php if ($fav_cats && !is_wp_error($fav_cats)) : ?>
                                <a href="<?php echo get_term_link($fav_cats[0]); ?>" class="product-category">
                                    <?php echo esc_html($fav_cat_name); ?>
                                </a>
                                <?php endif; ?>

                                <h3 class="product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <?php if ($fav_rating > 0) : ?>
                                <div class="product-rating">
                                    <?php
                                    $full = floor($fav_rating); $half = ($fav_rating - $full) >= 0.5;
                                    for ($i = 1; $i <= 5; $i++) :
                                        if ($i <= $full) echo '<i class="bi bi-star-fill filled"></i>';
                                        elseif ($i == $full + 1 && $half) echo '<i class="bi bi-star-half filled"></i>';
                                        else echo '<i class="bi bi-star"></i>';
                                    endfor;
                                    ?>
                                    <span class="rating-count">(<?php echo $fav_rating; ?>)</span>
                                </div>
                                <?php endif; ?>

                                <div class="product-footer">
                                    <div class="product-price">
                                        <?php if ($fav_price_info['is_on_sale'] && $fav_price_info['sale']) : ?>
                                            <span class="current-price"><?php echo number_format($fav_price_info['sale'], 0, ',', '.'); ?>đ</span>
                                            <span class="original-price"><?php echo number_format($fav_price_info['original'], 0, ',', '.'); ?>đ</span>
                                        <?php elseif ($fav_price_info['original']) : ?>
                                            <span class="current-price"><?php echo number_format($fav_price_info['original'], 0, ',', '.'); ?>đ</span>
                                        <?php else : ?>
                                            <span class="contact-price">Liên hệ</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($fav_stock && intval($fav_stock) > 0) : ?>
                                        <span class="stock-status in-stock"><i class="bi bi-check-circle"></i> Còn hàng</span>
                                    <?php elseif ($fav_stock === '0' || $fav_stock === 0) : ?>
                                        <span class="stock-status out-stock"><i class="bi bi-x-circle"></i> Hết hàng</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    <?php endif; ?>

                    <!-- CSS grid + card styles -->
                    <style>
                    .fav-products-grid {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 22px;
                    }
                    @media (max-width: 900px) { .fav-products-grid { grid-template-columns: repeat(2,1fr); } }
                    @media (max-width: 540px)  { .fav-products-grid { grid-template-columns: 1fr; } }

                    /* Reuse archive-product card styles */
                    .fav-products-grid .product-card {
                        background: white;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
                        transition: all 0.3s;
                    }
                    .fav-products-grid .product-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 15px 40px rgba(0,0,0,0.12);
                    }
                    .fav-products-grid .product-image { position:relative; overflow:hidden; }
                    .fav-products-grid .product-image img { width:100%; height:220px; object-fit:cover; transition:transform 0.3s; }
                    .fav-products-grid .product-card:hover .product-image img { transform:scale(1.05); }
                    .fav-products-grid .product-image .no-image {
                        display:flex; align-items:center; justify-content:center;
                        width:100%; height:220px;
                        background:linear-gradient(135deg,#FDF8F3,#F5EDE0);
                        color:#EC802B; font-size:3.5rem; opacity:0.5;
                    }
                    .fav-products-grid .discount-badge {
                        position:absolute; top:15px; left:15px;
                        background:#e74c3c; color:#fff;
                        padding:5px 12px; border-radius:20px; font-size:0.8rem; font-weight:700;
                    }
                    .fav-products-grid .product-actions {
                        position:absolute; bottom:-50px; left:0; right:0;
                        display:flex; justify-content:center; gap:10px; padding:15px;
                        background:linear-gradient(to top,rgba(0,0,0,0.7),transparent);
                        transition:bottom 0.3s;
                    }
                    .fav-products-grid .product-card:hover .product-actions { bottom:0; }
                    .fav-products-grid .action-btn {
                        width:40px; height:40px; border:none; background:#fff;
                        color:#EC802B; border-radius:50%; cursor:pointer;
                        display:flex; align-items:center; justify-content:center;
                        text-decoration:none; transition:all 0.3s; font-size:1rem;
                    }
                    .fav-products-grid .action-btn:hover { transform:scale(1.1); }
                    .fav-products-grid .action-btn.primary { background:#EC802B; color:#fff; }
                    .fav-products-grid .product-info { padding:18px; }
                    .fav-products-grid .product-category {
                        font-size:0.78rem; color:#7A6B5A; text-decoration:none;
                        text-transform:uppercase; letter-spacing:0.5px;
                    }
                    .fav-products-grid .product-category:hover { color:#EC802B; }
                    .fav-products-grid .product-title { font-size:1rem; font-weight:700; margin:7px 0 6px; line-height:1.4; }
                    .fav-products-grid .product-title a { color:#5D4E37; text-decoration:none; }
                    .fav-products-grid .product-title a:hover { color:#EC802B; }
                    .fav-products-grid .product-rating { display:flex; align-items:center; gap:3px; margin-bottom:8px; }
                    .fav-products-grid .product-rating i { color:#ddd; font-size:0.8rem; }
                    .fav-products-grid .product-rating i.filled { color:#F5994D; }
                    .fav-products-grid .rating-count { font-size:0.8rem; color:#999; margin-left:4px; }
                    .fav-products-grid .product-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
                    .fav-products-grid .current-price { font-size:1.15rem; font-weight:700; color:#EC802B; }
                    .fav-products-grid .original-price { font-size:0.85rem; color:#999; text-decoration:line-through; margin-left:6px; }
                    .fav-products-grid .contact-price { font-size:0.95rem; color:#999; }
                    .fav-products-grid .stock-status { font-size:0.78rem; font-weight:600; }
                    .fav-products-grid .in-stock { color:#5cb85c; }
                    .fav-products-grid .out-stock { color:#d9534f; }
                    </style>

                    <!-- JS: sort/filter + toggle yêu thích -->
                    <script>
                    function favReload() {
                        const sort = document.getElementById('fav-sort')?.value || 'date_desc';
                        const cat  = document.getElementById('fav-cat')?.value  || '';
                        const grid = document.getElementById('favGrid');
                        if (!grid) return;
                        const cards = Array.from(grid.querySelectorAll('.fav-card'));
                        cards.forEach(c => { c.style.display = (!cat || c.dataset.cat === cat) ? '' : 'none'; });
                        const visible = cards.filter(c => c.style.display !== 'none');
                        visible.sort((a, b) => {
                            if (sort === 'name_asc')   return a.dataset.name.localeCompare(b.dataset.name, 'vi');
                            if (sort === 'name_desc')  return b.dataset.name.localeCompare(a.dataset.name, 'vi');
                            if (sort === 'price_asc')  return parseInt(a.dataset.price) - parseInt(b.dataset.price);
                            if (sort === 'price_desc') return parseInt(b.dataset.price) - parseInt(a.dataset.price);
                            if (sort === 'date_asc')   return parseInt(a.dataset.date)  - parseInt(b.dataset.date);
                            return parseInt(b.dataset.date) - parseInt(a.dataset.date);
                        });
                        visible.forEach(c => grid.appendChild(c));
                    }

                    function toggleFavInAccount(btn) {
                        const productId = btn.getAttribute('data-product-id');
                        const card = btn.closest('.fav-card');
                        btn.disabled = true;
                        fetch(window.PETSHOP_USER?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                            method: 'POST', credentials: 'same-origin',
                            headers: {'Content-Type':'application/x-www-form-urlencoded'},
                            body: 'action=petshop_toggle_favorite&product_id=' + productId + '&action_favorite=remove'
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                card.style.transition = 'all 0.35s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.85)';
                                setTimeout(() => {
                                    card.remove();
                                    const badge = document.querySelector('#section-favorites h3 span');
                                    const remaining = document.querySelectorAll('#favGrid .fav-card').length;
                                    if (badge) badge.textContent = remaining;
                                    if (remaining === 0) location.reload();
                                }, 350);
                            } else { btn.disabled = false; }
                        })
                        .catch(() => { btn.disabled = false; });
                    }

                    // Hook add-to-cart-quick trong section favorites
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('#section-favorites .add-to-cart-quick').forEach(btn => {
                            if (btn.dataset.favHooked) return;
                            btn.dataset.favHooked = '1';
                            btn.addEventListener('click', function(e) {
                                e.preventDefault();
                                if (typeof window.PETSHOP_USER !== 'undefined' && !window.PETSHOP_USER.isLoggedIn) return;
                                const product = {
                                    id: this.dataset.id, name: this.dataset.name,
                                    price: parseFloat(this.dataset.price)||0,
                                    originalPrice: parseFloat(this.dataset.originalPrice)||0,
                                    image: this.dataset.image, url: this.dataset.url,
                                    sku: this.dataset.sku||'', category:'', quantity:1
                                };
                                const cartKey = window.getCartKey ? window.getCartKey() : 'petshop_cart_guest';
                                let cart = JSON.parse(localStorage.getItem(cartKey))||[];
                                const idx = cart.findIndex(i => i.id === product.id);
                                if (idx > -1) cart[idx].quantity += 1; else cart.push(product);
                                localStorage.setItem(cartKey, JSON.stringify(cart));
                                if (typeof window.updateGlobalCartCount === 'function') window.updateGlobalCartCount();
                                this.innerHTML = '<i class="bi bi-check-lg"></i>';
                                this.style.background = '#66BCB4';
                                setTimeout(() => { this.innerHTML = '<i class="bi bi-cart-plus"></i>'; this.style.background = ''; }, 1500);
                            });
                        });
                    });
                    </script>
                </div>
                <!-- Reviews Section -->
                <div id="section-reviews" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-star" style="color: #EC802B;"></i> Đánh giá của tôi
                    </h3>
                    
                    <?php 
                    $products_to_review = function_exists('petshop_get_products_to_review') ? petshop_get_products_to_review($user_id) : array();
                    ?>
                    
                    <?php if (!empty($products_to_review)) : ?>
                    <div style="background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 15px; padding: 20px; margin-bottom: 25px; color: #fff;">
                        <p style="margin: 0 0 10px;"><i class="bi bi-info-circle"></i> Bạn có <strong><?php echo count($products_to_review); ?></strong> sản phẩm chưa đánh giá</p>
                        <a href="<?php echo home_url('/danh-gia/'); ?>" class="btn" style="background: #fff; color: #EC802B;">
                            <i class="bi bi-pencil-square"></i> Đánh giá ngay
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Get user's reviews
                    $user_reviews = get_comments(array(
                        'user_id' => $user_id,
                        'type' => 'product_review',
                        'status' => 'approve',
                    ));
                    ?>
                    
                    <?php if (empty($user_reviews)) : ?>
                    <div style="text-align: center; padding: 40px; background: #FDF8F3; border-radius: 15px;">
                        <i class="bi bi-chat-left-text" style="font-size: 3rem; color: #E8CCAD;"></i>
                        <p style="color: #7A6B5A; margin-top: 15px;">Bạn chưa có đánh giá nào.</p>
                    </div>
                    <?php else : ?>
                    <div class="reviews-list">
                        <?php foreach ($user_reviews as $review) : 
                            $rating = intval(get_comment_meta($review->comment_ID, 'rating', true));
                            $product = get_post($review->comment_post_ID);
                        ?>
                        <div style="border: 1px solid #E8CCAD; border-radius: 15px; padding: 20px; margin-bottom: 15px;">
                            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <?php if ($product && has_post_thumbnail($product->ID)) : ?>
                                <img src="<?php echo get_the_post_thumbnail_url($product->ID, 'thumbnail'); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin: 0 0 5px;">
                                        <a href="<?php echo get_permalink($review->comment_post_ID); ?>" style="color: #5D4E37; text-decoration: none;">
                                            <?php echo $product ? esc_html($product->post_title) : 'Sản phẩm đã xóa'; ?>
                                        </a>
                                    </h4>
                                    <div style="display: flex; gap: 3px;">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <i class="bi bi-star<?php echo $i <= $rating ? '-fill' : ''; ?>" style="color: <?php echo $i <= $rating ? '#f1c40f' : '#ddd'; ?>; font-size: 0.9rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span style="margin-left: auto; color: #7A6B5A; font-size: 0.85rem;"><?php echo human_time_diff(strtotime($review->comment_date), current_time('timestamp')) . ' trước'; ?></span>
                            </div>
                            <p style="color: #666; margin: 0; line-height: 1.6;"><?php echo esc_html($review->comment_content); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Notifications Section -->
                <div id="section-notifications" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-bell" style="color: #EC802B;"></i> Thông báo
                        </h3>
                        <button id="markAllReadBtn" class="btn" style="background: #f8f9fa; color: #5D4E37; padding: 8px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.85rem;">
                            <i class="bi bi-check-all"></i> Đánh dấu tất cả đã đọc
                        </button>
                    </div>
                    
                    <?php
                    // Lấy thông báo của user
                    $notifications = function_exists('petshop_get_notifications') 
                        ? petshop_get_notifications($user_id, 20) 
                        : array();
                    ?>
                    
                    <?php if (empty($notifications)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: #FDF8F3; border-radius: 15px;">
                        <i class="bi bi-bell-slash" style="font-size: 3rem; color: #E8CCAD;"></i>
                        <p style="color: #7A6B5A; margin-top: 15px;">Bạn chưa có thông báo nào.</p>
                    </div>
                    <?php else: ?>
                    <div class="notifications-list">
                        <?php 
                        $notification_icons = array(
                            'order_confirmed' => array('icon' => 'bi-bag-check', 'color' => '#28a745'),
                            'order_shipping' => array('icon' => 'bi-truck', 'color' => '#17a2b8'),
                            'order_completed' => array('icon' => 'bi-check-circle', 'color' => '#28a745'),
                            'order_cancelled' => array('icon' => 'bi-x-circle', 'color' => '#dc3545'),
                            'tier_upgrade' => array('icon' => 'bi-trophy', 'color' => '#9C27B0'),
                            'points_earned' => array('icon' => 'bi-coin', 'color' => '#FF9800'),
                            'voucher_received' => array('icon' => 'bi-ticket-perforated', 'color' => '#E91E63'),
                            'promotion' => array('icon' => 'bi-tag', 'color' => '#EC802B'),
                            'system' => array('icon' => 'bi-info-circle', 'color' => '#607D8B'),
                        );
                        
                        foreach ($notifications as $notif): 
                            $icon_info = $notification_icons[$notif->type] ?? $notification_icons['system'];
                            $is_unread = !$notif->is_read;
                        ?>
                        <div class="notification-item <?php echo $is_unread ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notif->id; ?>"
                             style="display: flex; gap: 15px; padding: 18px; border-radius: 12px; margin-bottom: 12px; border: 1px solid <?php echo $is_unread ? '#EC802B' : '#e0e0e0'; ?>; background: <?php echo $is_unread ? '#FDF8F3' : '#fff'; ?>; cursor: pointer; transition: all 0.2s;">
                            <div style="width: 45px; height: 45px; background: <?php echo $icon_info['color']; ?>15; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="bi <?php echo $icon_info['icon']; ?>" style="color: <?php echo $icon_info['color']; ?>; font-size: 1.2rem;"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0 0 5px; font-size: 0.95rem; color: #333; font-weight: <?php echo $is_unread ? '600' : '500'; ?>;">
                                    <?php echo esc_html($notif->title); ?>
                                    <?php if ($is_unread): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; background: #EC802B; border-radius: 50%; margin-left: 5px;"></span>
                                    <?php endif; ?>
                                </h4>
                                <p style="margin: 0 0 8px; color: #666; font-size: 0.9rem; line-height: 1.5;"><?php echo esc_html($notif->message); ?></p>
                                <span style="color: #999; font-size: 0.8rem;">
                                    <i class="bi bi-clock"></i> <?php echo human_time_diff(strtotime($notif->created_at), current_time('timestamp')); ?> trước
                                </span>
                            </div>
                            <?php if (!empty($notif->link)): ?>
                            <a href="<?php echo esc_url($notif->link); ?>" style="align-self: center; color: #EC802B; font-size: 0.85rem; text-decoration: none;">
                                Xem <i class="bi bi-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sub-accounts Section -->
                <div id="section-subaccounts" class="account-section-content" style="display: none; background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <?php 
                    if (function_exists('petshop_subaccounts_manager_shortcode')) {
                        echo petshop_subaccounts_manager_shortcode();
                    } else {
                        echo '<p>Tính năng tài khoản phụ không khả dụng.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.account-nav-item:hover,
.account-nav-item.active {
    background: #FDF8F3 !important;
    color: #EC802B !important;
}
.account-nav-item.active {
    font-weight: 600;
}
#profileForm input:focus,
#profileForm select:focus,
#addressForm input:focus,
#addressForm select:focus {
    border-color: #EC802B;
    outline: none;
}
@media (max-width: 992px) {
    .account-section .container > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nonce = '<?php echo wp_create_nonce('petshop_account_nonce'); ?>';
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const fromCheckout = <?php echo $from_checkout ? 'true' : 'false'; ?>;
    
    // Navigation
    document.querySelectorAll('.account-nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            
            // Update active state
            document.querySelectorAll('.account-nav-item').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding section
            document.querySelectorAll('.account-section-content').forEach(sec => sec.style.display = 'none');
            document.getElementById('section-' + section).style.display = 'block';
        });
    });
    
    // If from checkout, auto switch to addresses
    if (fromCheckout) {
        document.querySelector('[data-section="addresses"]').click();
    }
    
    // Profile Form Submit
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'petshop_save_user_profile');
        formData.append('nonce', nonce);
        formData.append('display_name', this.querySelector('[name="display_name"]').value);
        formData.append('phone', this.querySelector('[name="phone"]').value);
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                const msgEl = document.getElementById('profileMessage');
                if (data.success) {
                    msgEl.innerHTML = '<div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 10px;"><i class="bi bi-check-circle"></i> ' + data.data.message + '</div>';
                } else {
                    msgEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> ' + (data.data?.message || 'Có lỗi xảy ra') + '</div>';
                }
            });
    });
    
    // Address Form
    const addressFormWrapper = document.getElementById('addressFormWrapper');
    const addressForm = document.getElementById('addressForm');
    const addressFormTitle = document.getElementById('addressFormTitle');
    
    document.getElementById('addAddressBtn').addEventListener('click', function() {
        addressFormTitle.innerHTML = '<i class="bi bi-plus-circle" style="color: #EC802B;"></i> Thêm địa chỉ mới';
        addressForm.reset();
        document.getElementById('addressId').value = '';
        addressFormWrapper.style.display = 'block';
        addressFormWrapper.scrollIntoView({ behavior: 'smooth' });
    });
    
    document.getElementById('cancelAddressBtn').addEventListener('click', function() {
        addressFormWrapper.style.display = 'none';
    });
    
    // Edit Address
    document.querySelectorAll('.edit-address-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const addressId = this.dataset.id;
            // For simplicity, we'll reload page with edit mode
            // In production, you'd fetch address data via AJAX
            addressFormTitle.innerHTML = '<i class="bi bi-pencil" style="color: #EC802B;"></i> Sửa địa chỉ';
            document.getElementById('addressId').value = addressId;
            addressFormWrapper.style.display = 'block';
            addressFormWrapper.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Delete Address
    document.querySelectorAll('.delete-address-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Bạn có chắc muốn xóa địa chỉ này?')) return;
            
            const addressId = this.dataset.id;
            const card = this.closest('.address-card');
            
            const formData = new FormData();
            formData.append('action', 'petshop_delete_address');
            formData.append('nonce', nonce);
            formData.append('address_id', addressId);
            
            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        card.remove();
                    } else {
                        alert(data.data?.message || 'Có lỗi xảy ra');
                    }
                });
        });
    });
    
    // Set Default Address
    document.querySelectorAll('.set-default-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const addressId = this.dataset.id;
            
            const formData = new FormData();
            formData.append('action', 'petshop_set_default_address');
            formData.append('nonce', nonce);
            formData.append('address_id', addressId);
            
            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.data?.message || 'Có lỗi xảy ra');
                    }
                });
        });
    });
    
    // Address Form Submit
    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const citySelect = document.getElementById('addrCity');
        const districtSelect = document.getElementById('addrDistrict');
        const wardSelect = document.getElementById('addrWard');
        
        const formData = new FormData();
        formData.append('action', 'petshop_save_address');
        formData.append('nonce', nonce);
        formData.append('address_id', document.getElementById('addressId').value);
        formData.append('label', document.getElementById('addrLabel').value);
        formData.append('fullname', document.getElementById('addrFullname').value);
        formData.append('phone', document.getElementById('addrPhone').value);
        formData.append('city', citySelect.value);
        formData.append('city_text', citySelect.options[citySelect.selectedIndex].text);
        formData.append('district', districtSelect.value);
        formData.append('district_text', districtSelect.options[districtSelect.selectedIndex].text);
        formData.append('ward', wardSelect.value);
        formData.append('ward_text', wardSelect.options[wardSelect.selectedIndex].text);
        formData.append('address', document.getElementById('addrAddress').value);
        formData.append('is_default', document.getElementById('addrIsDefault').checked ? '1' : '');
        
        fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data?.message || 'Có lỗi xảy ra');
                }
            });
    });
});

// Xem chi tiết đơn hàng
function viewOrderDetail(orderId) {
    const modal = document.getElementById('orderDetailModal');
    const content = document.getElementById('orderDetailContent');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="bi bi-arrow-repeat" style="font-size: 2rem; animation: spin 1s linear infinite;"></i><p style="margin-top: 10px; color: #7A6B5A;">Đang tải...</p></div>';
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=petshop_get_order_detail&order_id=' + orderId + '&nonce=<?php echo wp_create_nonce('petshop_account_nonce'); ?>'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = data.data.html;
        } else {
            content.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;"><i class="bi bi-exclamation-circle" style="font-size: 2rem;"></i><p style="margin-top: 10px;">' + (data.data?.message || 'Có lỗi xảy ra') + '</p></div>';
        }
    });
}

function closeOrderDetail() {
    document.getElementById('orderDetailModal').style.display = 'none';
}

// Đóng modal khi click nền
document.getElementById('orderDetailModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeOrderDetail();
});

// ===== NOTIFICATION HANDLERS =====
// Đánh dấu tất cả đã đọc
document.getElementById('markAllReadBtn')?.addEventListener('click', function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=petshop_mark_all_notifications_read&nonce=<?php echo wp_create_nonce('petshop_notification_nonce'); ?>'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove unread styling
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.style.background = '#fff';
                item.style.borderColor = '#e0e0e0';
                const dot = item.querySelector('span[style*="background: #EC802B"][style*="border-radius: 50%"]');
                if (dot) dot.remove();
            });
            // Update nav badge
            const badge = document.querySelector('[data-section="notifications"] span[style*="background: #f44336"]');
            if (badge) badge.remove();
        }
    });
});

// Click notification to mark as read
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        if (!this.classList.contains('unread')) return;
        
        const notifId = this.dataset.id;
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=petshop_mark_notification_read&notification_id=' + notifId + '&nonce=<?php echo wp_create_nonce('petshop_notification_nonce'); ?>'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.classList.remove('unread');
                this.style.background = '#fff';
                this.style.borderColor = '#e0e0e0';
                const dot = this.querySelector('span[style*="background: #EC802B"][style*="border-radius: 50%"]');
                if (dot) dot.remove();
            }
        });
    });
});

// ===== SUB-ACCOUNTS =====
// Load sub-accounts when section is opened
document.querySelector('[data-section="subaccounts"]')?.addEventListener('click', function() {
    if (typeof loadSubAccounts === 'function') {
        loadSubAccounts();
    }
});
</script>

<?php get_footer(); ?>