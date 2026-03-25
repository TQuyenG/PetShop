<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>?v=<?php echo time(); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <!-- Header Top Bar -->
    <div class="header-top">
        <div class="site-container">
            <div class="header-top-inner">
                <div class="header-contact">
                    <a href="tel:0123456789" title="Gọi ngay">
                        <i class="bi bi-telephone-fill"></i>
                        0123 456 789
                    </a>
                    <a href="mailto:info@petshop.com" title="Gửi email cho chúng tôi">
                        <i class="bi bi-envelope-fill"></i>
                        info@petshop.com
                    </a>
                </div>
                <div class="header-social">
                    <a href="#" title="Facebook" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" title="Instagram" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" title="Youtube" aria-label="Youtube">
                        <i class="bi bi-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header Main -->
    <div class="header-main">
        <div class="site-container">
            <div class="header-inner">
                <!-- Logo & Brand -->
                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-brand">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/LogoPetshop.png" alt="PetShop Logo" style="width:50px;height:50px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,0.08);background:#fff;">
                    <div class="brand-text">
                        <span class="site-title">PetShop</span>
                        <span class="site-tagline">Yêu thương thú cưng</span>
                    </div>
                </a>
                
                <!-- Main Navigation -->
                <nav class="main-nav">
                    <?php
                    if (has_nav_menu('primary')) {
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'container'      => '',
                            'menu_class'     => 'nav-menu',
                            'fallback_cb'    => false,
                        ));
                    } else {
                    ?>
                    <ul class="nav-menu">
                        <li class="<?php echo is_front_page() ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo home_url('/'); ?>">Trang chủ</a>
                        </li>
                        <li class="menu-item-has-children has-mega-menu <?php echo is_post_type_archive('product') || is_singular('product') || is_tax('product_category') ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo get_post_type_archive_link('product'); ?>">
                                Sản phẩm
                                <i class="bi bi-chevron-down"></i>
                            </a>
                            <!-- Mega Menu Dropdown -->
                            <div class="mega-menu">
                                <div class="mega-menu-inner">
                                    <!-- Cột Tất cả sản phẩm -->
                                    <div class="mega-menu-column mega-menu-all">
                                        <a href="<?php echo get_post_type_archive_link('product'); ?>" class="mega-menu-all-link">
                                            <i class="bi bi-grid-3x3-gap-fill"></i>
                                            <span>
                                                <strong>Tất cả sản phẩm</strong>
                                                <?php 
                                                $total_products = wp_count_posts('product');
                                                echo '<small>' . $total_products->publish . ' sản phẩm</small>';
                                                ?>
                                            </span>
                                        </a>
                                    </div>
                                    <!-- Các danh mục -->
                                    <div class="mega-menu-categories">
                                        <?php
                                        // Lấy danh mục sản phẩm cha từ database
                                        $product_categories = get_terms(array(
                                            'taxonomy' => 'product_category',
                                            'hide_empty' => false,
                                            'parent' => 0,
                                            'orderby' => 'name',
                                        ));
                                        
                                        if (!empty($product_categories) && !is_wp_error($product_categories)) :
                                            // Icon mapping cho các danh mục
                                            $cat_icons = array(
                                                'thuc-an' => 'bi-cup-hot-fill',
                                                'phu-kien' => 'bi-bag-heart-fill',
                                                'do-choi' => 'bi-controller',
                                                'chuong-nha' => 'bi-house-heart-fill',
                                                've-sinh-cham-soc' => 'bi-droplet-fill',
                                                'y-te-thuoc' => 'bi-capsule',
                                            );
                                            
                                            foreach ($product_categories as $cat) :
                                                $icon = isset($cat_icons[$cat->slug]) ? $cat_icons[$cat->slug] : 'bi-tag-fill';
                                                
                                                // Lấy danh mục con
                                                $child_cats = get_terms(array(
                                                    'taxonomy' => 'product_category',
                                                    'hide_empty' => false,
                                                    'parent' => $cat->term_id,
                                                ));
                                                
                                                // Tính tổng số sản phẩm từ danh mục cha + tất cả danh mục con
                                                $total_cat_count = $cat->count;
                                                if (!empty($child_cats) && !is_wp_error($child_cats)) {
                                                    foreach ($child_cats as $child_cat) {
                                                        $total_cat_count += $child_cat->count;
                                                    }
                                                }
                                        ?>
                                        <div class="mega-menu-column">
                                            <a href="<?php echo get_term_link($cat); ?>" class="mega-menu-title">
                                                <i class="bi <?php echo $icon; ?>"></i>
                                                <?php echo esc_html($cat->name); ?>
                                                <span class="count"><?php echo $total_cat_count; ?></span>
                                            </a>
                                            
                                            <?php if (!empty($child_cats) && !is_wp_error($child_cats)) : ?>
                                            <ul class="mega-menu-list">
                                                <?php foreach ($child_cats as $child) : ?>
                                                <li>
                                                    <a href="<?php echo get_term_link($child); ?>">
                                                        <?php echo esc_html($child->name); ?>
                                                        <span class="count">(<?php echo $child->count; ?>)</span>
                                                    </a>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                    
                                    <!-- Banner khuyến mãi -->
                                    <div class="mega-menu-banner">
                                        <div class="promo-banner">
                                            <i class="bi bi-lightning-fill"></i>
                                            <div class="promo-text">
                                                <strong>Flash Sale!</strong>
                                                <span>Giảm đến 50%</span>
                                            </div>
                                            <a href="<?php echo get_post_type_archive_link('product'); ?>?sale=1" class="promo-btn">Xem ngay</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <!-- <li class="menu-item-has-children <?php echo is_page('dich-vu') ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo home_url('/dich-vu/'); ?>">Dịch vụ</a>
                            <ul class="sub-menu">
                                <li><a href="<?php echo home_url('/dich-vu/'); ?>"><i class="bi bi-stars"></i> Tất cả dịch vụ</a></li>
                                <li><a href="<?php echo home_url('/dich-vu/#grooming'); ?>"><i class="bi bi-scissors"></i> Spa & Grooming</a></li>
                                <li><a href="<?php echo home_url('/dich-vu/#veterinary'); ?>"><i class="bi bi-heart-pulse"></i> Khám & Điều trị</a></li>
                                <li><a href="<?php echo home_url('/dich-vu/#hotel'); ?>"><i class="bi bi-house-heart"></i> Khách sạn thú cưng</a></li>
                            </ul>
                        </li> -->
                        <li class="<?php echo is_page('tin-tuc') || (is_single() && get_post_type() == 'post') ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo home_url('/tin-tuc/'); ?>">Tin tức</a>
                        </li>
                        <li class="<?php echo is_page('gioi-thieu') ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo home_url('/gioi-thieu/'); ?>">Giới thiệu</a>
                        </li>
                        <li class="<?php echo is_page('lien-he') ? 'current-menu-item' : ''; ?>">
                            <a href="<?php echo home_url('/lien-he/'); ?>">Liên hệ</a>
                        </li>
                    </ul>
                    <?php } ?>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="search" name="s" placeholder="Tìm kiếm..." value="<?php echo get_search_query(); ?>" aria-label="Tìm kiếm">
                        <button type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                    
                    <div class="header-icons">
                        <a href="<?php echo home_url('/gio-hang/'); ?>" class="icon-btn" id="cartBtn" aria-label="Giỏ hàng" title="Giỏ hàng">
                            <i class="bi bi-bag"></i>
                            <span class="badge cart-count" style="display: none;">0</span>
                        </a>
                        
                        <?php if (is_user_logged_in()) : 
                            $current_user = wp_get_current_user();
                            $unread_notif_count = function_exists('petshop_count_unread_notifications') 
                                ? petshop_count_unread_notifications(get_current_user_id()) : 0;
                        ?>
                        <!-- Notification Bell -->
                        <div class="notification-bell-container" id="notificationBell">
                            <button type="button" class="icon-btn notification-bell-btn" aria-label="Thông báo" title="Thông báo">
                                <i class="bi bi-bell"></i>
                                <span class="badge notification-count" style="<?php echo $unread_notif_count > 0 ? '' : 'display:none;'; ?>"><?php echo $unread_notif_count > 9 ? '9+' : $unread_notif_count; ?></span>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notif-dropdown-header">
                                    <h4><i class="bi bi-bell-fill"></i> Thông báo</h4>
                                    <div class="notif-actions">
                                        <button type="button" id="markAllReadHeaderBtn" title="Đánh dấu tất cả đã đọc">
                                            <i class="bi bi-check-all"></i>
                                        </button>
                                        <a href="<?php echo home_url('/thong-bao/'); ?>" title="Xem tất cả">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="notif-dropdown-tabs">
                                    <button class="notif-tab active" data-filter="all">Tất cả</button>
                                    <button class="notif-tab" data-filter="unread">Chưa đọc</button>
                                </div>
                                
                                <div class="notif-dropdown-body" id="notificationList">
                                    <div class="notif-loading">
                                        <i class="bi bi-arrow-repeat spin"></i> Đang tải...
                                    </div>
                                </div>
                                
                                <div class="notif-dropdown-footer">
                                    <a href="<?php echo home_url('/thong-bao/'); ?>">
                                        Xem tất cả thông báo <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Dropdown -->
                        <div class="account-dropdown-wrapper" style="position: relative;">
                            <button type="button" class="icon-btn account-btn" id="accountBtn" aria-label="Tài khoản" title="Tài khoản">
                                <i class="bi bi-person-check"></i>
                            </button>
                            
                            <div class="account-dropdown" id="accountDropdown">
                                <div class="account-dropdown-header">
                                    <div class="account-avatar">
                                        <?php echo get_avatar(get_current_user_id(), 50); ?>
                                    </div>
                                    <div class="account-info">
                                        <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>
                                        <span><?php echo esc_html(wp_get_current_user()->user_email); ?></span>
                                    </div>
                                </div>
                                <div class="account-dropdown-body">
                                    <a href="<?php echo home_url('/tai-khoan/'); ?>">
                                        <i class="bi bi-person"></i> Tài khoản của tôi
                                    </a>
                                    <a href="javascript:void(0)" onclick="goToAccount('orders')">
                                        <i class="bi bi-bag"></i> Đơn hàng
                                    </a>
                                    <a href="javascript:void(0)" onclick="goToAccount('addresses')">
                                        <i class="bi bi-geo-alt"></i> Địa chỉ giao hàng
                                    </a>
                                    <a href="javascript:void(0)" onclick="goToAccount('favorites')">
                                        <i class="bi bi-heart"></i> Sản phẩm yêu thích
                                    </a>
                                    <?php if (current_user_can('manage_options') || current_user_can('petshop_manager')) : ?>
                                    <hr style="margin: 8px 0; border: none; border-top: 1px solid #e9ecef;">
                                    <a href="<?php echo admin_url('admin.php?page=petshop-admin-dashboard'); ?>">
                                        <i class="bi bi-speedometer2"></i> Quản trị
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="account-dropdown-footer">
                                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">
                                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else : ?>
                        <!-- Guest - Login/Register Dropdown -->
                        <div class="account-dropdown-wrapper" style="position: relative;">
                            <button type="button" class="icon-btn" id="loginBtn" aria-label="Đăng nhập" title="Đăng nhập">
                                <i class="bi bi-person"></i>
                            </button>
                            
                            <div class="account-dropdown" id="guestDropdown">
                                <div class="guest-dropdown-header">
                                    <i class="bi bi-person-circle" style="font-size: 2.5rem; color: #EC802B;"></i>
                                    <p>Đăng nhập để trải nghiệm mua sắm tốt hơn!</p>
                                </div>
                                <div class="guest-dropdown-body">
                                    <a href="<?php echo home_url('/dang-nhap/'); ?>" class="btn-login-main">
                                        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                                    </a>
                                    <a href="<?php echo home_url('/dang-ky/'); ?>" class="btn-register-main">
                                        <i class="bi bi-person-plus"></i> Đăng ký
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- User Login Status for JavaScript -->
<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
    window.PETSHOP_USER = {
        isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
        loginUrl: '<?php echo esc_url(home_url('/tai-khoan/')); ?>',
        userId: <?php echo is_user_logged_in() ? get_current_user_id() : '0'; ?>,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>'
    };
    
    // Hàm global để lấy cart key theo user
    window.getCartKey = function() {
        const userId = window.PETSHOP_USER?.userId || 0;
        return userId > 0 ? 'petshop_cart_user_' + userId : 'petshop_cart_guest';
    };
    
    // Hàm global để update cart count
    window.updateGlobalCartCount = function() {
        const cartKey = window.getCartKey();
        const cart = JSON.parse(localStorage.getItem(cartKey)) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = totalItems;
            el.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    };
    
    // Update cart count khi page load
    document.addEventListener('DOMContentLoaded', window.updateGlobalCartCount);
</script>

<!-- Notification Bell Styles -->
<style>
.notification-bell-container { position: relative; }
.notification-bell-btn.icon-btn { 
    /* Reset any button defaults */
    padding: 0;
}
.notification-bell-btn .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: #fff;
    font-size: 10px;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: -50px;
    width: 380px;
    max-height: 500px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 50px rgba(0,0,0,0.2);
    z-index: 10000;
    display: none;
    overflow: hidden;
    animation: slideDown 0.3s ease;
}
.notification-dropdown.show { display: block; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notif-dropdown-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.notif-dropdown-header h4 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
.notif-actions { display: flex; gap: 10px; }
.notif-actions button, .notif-actions a {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.notif-actions button:hover, .notif-actions a:hover { background: rgba(255,255,255,0.3); }

.notif-dropdown-tabs {
    display: flex;
    border-bottom: 1px solid #eee;
    padding: 0 10px;
}
.notif-tab {
    flex: 1;
    padding: 12px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #888;
    transition: all 0.2s;
}
.notif-tab:hover { color: #EC802B; }
.notif-tab.active { color: #EC802B; border-bottom-color: #EC802B; }

.notif-dropdown-body {
    max-height: 350px;
    overflow-y: auto;
}

.notif-item {
    display: flex;
    gap: 12px;
    padding: 15px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}
.notif-item:hover { background: #FDF8F3; }
.notif-item.unread { background: #FDF8F3; }
.notif-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #EC802B;
}
.notif-item { position: relative; }

.notif-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-icon i { font-size: 18px; }

.notif-content { flex: 1; min-width: 0; }
.notif-title {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 4px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 6px;
}
.notif-title .unread-dot {
    width: 8px;
    height: 8px;
    background: #EC802B;
    border-radius: 50%;
}
.notif-message {
    font-size: 12px;
    color: #666;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.notif-time {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.notif-dropdown-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}
.notif-dropdown-footer a {
    color: #EC802B;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.notif-dropdown-footer a:hover { text-decoration: underline; }

.notif-empty {
    text-align: center;
    padding: 40px 20px;
    color: #888;
}
.notif-empty i { font-size: 40px; opacity: 0.3; display: block; margin-bottom: 10px; }

.notif-loading {
    text-align: center;
    padding: 30px;
    color: #888;
}
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 480px) {
    .notification-dropdown {
        position: fixed;
        top: 60px;
        left: 10px;
        right: 10px;
        width: auto;
    }
}
</style>

<!-- Notification Real-time Script -->
<?php if (is_user_logged_in()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.querySelector('.notification-bell-btn');
    const dropdown = document.getElementById('notificationDropdown');
    const notifList = document.getElementById('notificationList');
    const notifCount = document.querySelector('.notification-count');
    const ajaxUrl = window.PETSHOP_USER.ajaxUrl;
    
    let lastNotifId = 0;
    let currentFilter = 'all';
    let pollInterval;
    
    // Toggle dropdown
    bellBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!dropdown?.contains(e.target) && e.target !== bellBtn) {
            dropdown?.classList.remove('show');
        }
    });
    
    // Tab switching
    document.querySelectorAll('.notif-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            loadNotifications();
        });
    });
    
    // Mark all read
    document.getElementById('markAllReadHeaderBtn')?.addEventListener('click', function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_mark_all_read_v2'
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                updateBadge(0);
                document.querySelectorAll('.notif-item.unread').forEach(item => {
                    item.classList.remove('unread');
                    item.querySelector('.unread-dot')?.remove();
                });
            }
        });
    });
    
    // Load notifications
    function loadNotifications() {
        fetch(ajaxUrl + '?action=petshop_get_notifications_realtime&last_id=0')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    renderNotifications(res.data.notifications);
                    updateBadge(res.data.unread_count);
                    lastNotifId = res.data.max_id;
                }
            });
    }
    
    // Render notifications
    function renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            notifList.innerHTML = `
                <div class="notif-empty">
                    <i class="bi bi-bell-slash"></i>
                    <p>Chưa có thông báo nào</p>
                </div>
            `;
            return;
        }
        
        // Filter
        let filtered = notifications;
        if (currentFilter === 'unread') {
            filtered = notifications.filter(n => !n.is_read);
        }
        
        if (filtered.length === 0) {
            notifList.innerHTML = `
                <div class="notif-empty">
                    <i class="bi bi-check-circle"></i>
                    <p>Đã đọc tất cả thông báo</p>
                </div>
            `;
            return;
        }
        
        notifList.innerHTML = filtered.map(n => {
            // Xác định link: nếu có link custom → dùng link đó, không thì dẫn đến trang chi tiết thông báo
            let notifUrl = n.link || '<?php echo home_url('/thong-bao/?view='); ?>' + n.id;
            
            return `
            <a href="${notifUrl}" class="notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" onclick="markNotificationRead(${n.id})">
                <div class="notif-icon" style="background: ${n.color}20;">
                    <i class="bi ${n.icon}" style="color: ${n.color};"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">
                        ${n.title}
                        ${n.is_read ? '' : '<span class="unread-dot"></span>'}
                    </div>
                    <div class="notif-message">${n.message}</div>
                    <div class="notif-time">
                        <i class="bi bi-clock"></i> ${n.time_ago}
                        <span style="margin-left: auto; background: ${n.color}20; color: ${n.color}; padding: 2px 8px; border-radius: 10px; font-size: 10px;">${n.type_label}</span>
                    </div>
                </div>
            </a>
        `}).join('');
    }
    
    // Update badge
    function updateBadge(count) {
        if (notifCount) {
            notifCount.textContent = count > 9 ? '9+' : count;
            notifCount.style.display = count > 0 ? 'flex' : 'none';
        }
    }
    
    // Mark as read
    window.markNotificationRead = function(id) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=petshop_mark_notification_read_v2&notification_id=' + id
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const item = document.querySelector(`.notif-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    item.querySelector('.unread-dot')?.remove();
                }
                updateBadge(res.data.unread_count);
            }
        });
    };
    
    // Real-time polling (every 30 seconds)
    function startPolling() {
        pollInterval = setInterval(function() {
            fetch(ajaxUrl + '?action=petshop_get_notifications_realtime&last_id=' + lastNotifId)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        updateBadge(res.data.unread_count);
                        
                        // If new notifications arrived and dropdown is open, refresh
                        if (res.data.notifications.length > 0 && dropdown.classList.contains('show')) {
                            loadNotifications();
                        }
                        
                        // Show browser notification for new ones
                        if (res.data.notifications.length > 0 && Notification.permission === 'granted') {
                            res.data.notifications.forEach(n => {
                                if (!n.is_read) {
                                    new Notification('PetShop: ' + n.title, {
                                        body: n.message,
                                        icon: '<?php echo get_template_directory_uri(); ?>/assets/images/logo.png'
                                    });
                                }
                            });
                        }
                        
                        lastNotifId = res.data.max_id;
                    }
                });
        }, 30000); // 30 seconds
    }
    
    // Request notification permission
    if (window.Notification && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Initial load and start polling
    loadNotifications();
    startPolling();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearInterval(pollInterval);
    });
});
</script>
<?php endif; ?>

<!-- Account Dropdown Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Account dropdown toggle (logged in users)
    var accountBtn = document.getElementById('accountBtn');
    var accountDropdown = document.getElementById('accountDropdown');
    
    if (accountBtn && accountDropdown) {
        accountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            accountDropdown.classList.toggle('active');
            
            // Close other dropdowns
            var guestDropdown = document.getElementById('guestDropdown');
            if (guestDropdown) guestDropdown.classList.remove('active');
        });
    }
    
    // Guest dropdown toggle
    var loginBtn = document.getElementById('loginBtn');
    var guestDropdown = document.getElementById('guestDropdown');
    
    if (loginBtn && guestDropdown) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            guestDropdown.classList.toggle('active');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (accountDropdown && !accountDropdown.contains(e.target) && e.target !== accountBtn && !accountBtn?.contains(e.target)) {
            accountDropdown.classList.remove('active');
        }
        if (guestDropdown && loginBtn && !guestDropdown.contains(e.target) && e.target !== loginBtn && !loginBtn?.contains(e.target)) {
            guestDropdown.classList.remove('active');
        }
    });
    
    // goToAccount: điều hướng tới trang tài khoản và mở đúng section
    window.goToAccount = function(section) {
        const accountUrl = '<?php echo esc_url(home_url('/tai-khoan/')); ?>';
        const currentBase = window.location.origin + window.location.pathname.replace(/\/+$/, '');
        const targetBase = accountUrl.replace(/\/+$/, '');
        
        if (currentBase === targetBase) {
            // Đang ở trang tài khoản → click nav item trực tiếp
            const target = document.querySelector('[data-section="' + section + '"]');
            if (target) {
                target.click();
                window.history.pushState(null, '', '#' + section);
            }
            // Đóng dropdown
            if (accountDropdown) accountDropdown.classList.remove('active');
        } else {
            // Chuyển trang
            window.location.href = accountUrl + '#' + section;
        }
    };
});
</script>