<?php
/**
 * PetShop Theme Functions
 * 
 * @package PetShop
 * @version 2.0
 */

// Include modules
require_once get_template_directory() . '/inc/seed-products.php';
require_once get_template_directory() . '/inc/orders-reviews.php';
require_once get_template_directory() . '/inc/user-account.php';
require_once get_template_directory() . '/inc/inventory-management.php';
require_once get_template_directory() . '/inc/coupon-management.php';
require_once get_template_directory() . '/inc/shipping-settings.php';
require_once get_template_directory() . '/inc/user-management.php';
require_once get_template_directory() . '/inc/payment-settings.php';
require_once get_template_directory() . '/inc/email-smtp.php';
require_once get_template_directory() . '/inc/custom-login.php';

// Promotion Modules
require_once get_template_directory() . '/inc/category-single-subcategory.php';
require_once get_template_directory() . '/inc/promotion-menu.php';

// CRM Modules
require_once get_template_directory() . '/inc/crm-admin-menu.php';
require_once get_template_directory() . '/inc/crm-customer-management.php';
require_once get_template_directory() . '/inc/crm-dashboard.php';
require_once get_template_directory() . '/inc/crm-referral-system.php';
require_once get_template_directory() . '/inc/seed-crm-data.php';

// Notification & Sub-accounts
require_once get_template_directory() . '/inc/notification-system.php';
require_once get_template_directory() . '/inc/sub-accounts.php';
require_once get_template_directory() . '/inc/notification-advanced.php';
require_once get_template_directory() . '/inc/post-notification.php';

// Communication Menu (Truyền thông)
require_once get_template_directory() . '/inc/communication-menu.php';

// Chat System (Hệ thống chat & giám sát)
require_once get_template_directory() . '/inc/chat-system.php';

// =============================================
// CUSTOM TEMPLATE REDIRECT FOR SPECIAL PAGES
// =============================================
// Map slug → [template file, page title]
function petshop_get_custom_pages() {
    return array(
        'hoan-tat'     => array('page-hoan-tat.php',         'Đặt hàng thành công'),
        'vnpay-return' => array('page-vnpay-return.php',     'Kết quả thanh toán'),
        'danh-gia'     => array('page-danh-gia.php',         'Đánh giá sản phẩm'),
        'tai-khoan'    => array('page-tai-khoan.php',        'Tài khoản của tôi'),
        'thong-bao'    => array('page-thong-bao.php',        'Thông báo'),
        'gio-hang'     => array('page-gio-hang.php',         'Giỏ hàng'),
        'thanh-toan'   => array('page-thanh-toan.php',       'Thanh toán'),
        'xem-don-hang' => array('page-xem-don-hang.php',     'Chi tiết đơn hàng'),
    );
}

function petshop_custom_template_redirect() {
    global $wp_query;

    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    // Bỏ subdirectory WordPress nếu có
    $site_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
    if ($site_path && strpos($request_uri, $site_path) === 0) {
        $request_uri = trim(substr($request_uri, strlen($site_path)), '/');
    }

    // Lấy slug đầu tiên (trước dấu /)
    $slug = strtok($request_uri, '/');

    $pages = petshop_get_custom_pages();

    if (isset($pages[$slug])) {
        list($template_file, $page_title) = $pages[$slug];
        $template_path = get_template_directory() . '/' . $template_file;

        if (file_exists($template_path)) {
            // Báo WordPress đây là trang hợp lệ — tránh 404
            status_header(200);
            $wp_query->is_404  = false;
            $wp_query->is_page = true;

            // Set tiêu đề tab trình duyệt
            add_filter('document_title_parts', function($title) use ($page_title) {
                $title['title'] = $page_title;
                return $title;
            });

            include $template_path;
            exit;
        }
    }
}
add_action('template_redirect', 'petshop_custom_template_redirect', 1);

// =============================================
// THEME SETUP
// =============================================
function petshop_theme_setup() {
    // Add theme support
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo', array(
        'width'       => 200,
        'height'      => 80,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('automatic-feed-links');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Menu chính', 'petshop'),
        'footer'  => __('Menu Footer', 'petshop'),
    ));
    
    // Add image sizes
    add_image_size('petshop-featured', 800, 450, true);
    add_image_size('petshop-product', 400, 400, true);
    add_image_size('petshop-thumbnail', 150, 150, true);
}
add_action('after_setup_theme', 'petshop_theme_setup');

// =============================================
// ENQUEUE SCRIPTS & STYLES
// =============================================
function petshop_enqueue_assets() {
    // Main stylesheet
    wp_enqueue_style('petshop-style', get_stylesheet_uri(), array(), '2.0');
    
    // Google Fonts
    wp_enqueue_style('petshop-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', array(), null);
    
    // Main script
    wp_enqueue_script('petshop-main', get_template_directory_uri() . '/assets/js/main.js', array(), '2.0', true);
    
    // Localize script
    wp_localize_script('petshop-main', 'petshopData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'homeUrl' => home_url('/'),
        'themeUrl' => get_template_directory_uri(),
    ));
}
add_action('wp_enqueue_scripts', 'petshop_enqueue_assets');

// =============================================
// REGISTER SIDEBARS
// =============================================
function petshop_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar chính', 'petshop'),
        'id'            => 'sidebar-main',
        'description'   => __('Widget hiển thị ở sidebar chính', 'petshop'),
        'before_widget' => '<div id="%1$s" class="sidebar-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget 1', 'petshop'),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget 2', 'petshop'),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'petshop_widgets_init');

// =============================================
// CUSTOM POST TYPE: PRODUCT
// =============================================
function petshop_register_product_post_type() {
    $labels = array(
        'name'               => 'Sản phẩm',
        'singular_name'      => 'Sản phẩm',
        'add_new'            => 'Thêm mới',
        'add_new_item'       => 'Thêm sản phẩm mới',
        'edit_item'          => 'Sửa sản phẩm',
        'new_item'           => 'Sản phẩm mới',
        'view_item'          => 'Xem sản phẩm',
        'search_items'       => 'Tìm sản phẩm',
        'not_found'          => 'Không tìm thấy sản phẩm',
        'not_found_in_trash' => 'Không có sản phẩm trong thùng rác',
        'menu_name'          => 'Sản phẩm',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'publicly_queryable' => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'san-pham', 'with_front' => false),
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon'          => 'dashicons-cart',
        'show_in_rest'       => true,
    );

    register_post_type('product', $args);
    
    // Register Product Category Taxonomy
    register_taxonomy('product_category', 'product', array(
        'labels' => array(
            'name'          => 'Danh mục sản phẩm',
            'singular_name' => 'Danh mục',
            'add_new_item'  => 'Thêm danh mục mới',
        ),
        'hierarchical' => true,
        'rewrite'      => array('slug' => 'danh-muc-san-pham'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'petshop_register_product_post_type');

// =============================================
// TẠO DANH MỤC SẢN PHẨM MẶC ĐỊNH
// =============================================
function petshop_create_default_product_categories() {
    // Danh mục mặc định cho Pet Shop
    $default_categories = array(
        array(
            'name' => 'Thức ăn',
            'slug' => 'thuc-an',
            'description' => 'Thức ăn cho thú cưng',
            'children' => array(
                array('name' => 'Thức ăn cho chó', 'slug' => 'thuc-an-cho-cho'),
                array('name' => 'Thức ăn cho mèo', 'slug' => 'thuc-an-cho-meo'),
                array('name' => 'Thức ăn cho hamster', 'slug' => 'thuc-an-cho-hamster'),
                array('name' => 'Thức ăn cho chim', 'slug' => 'thuc-an-cho-chim'),
            )
        ),
        array(
            'name' => 'Phụ kiện',
            'slug' => 'phu-kien',
            'description' => 'Phụ kiện cho thú cưng',
            'children' => array(
                array('name' => 'Vòng cổ & Dây xích', 'slug' => 'vong-co-day-xich'),
                array('name' => 'Quần áo', 'slug' => 'quan-ao'),
                array('name' => 'Nơ & Phụ kiện trang trí', 'slug' => 'no-phu-kien-trang-tri'),
            )
        ),
        array(
            'name' => 'Đồ chơi',
            'slug' => 'do-choi',
            'description' => 'Đồ chơi cho thú cưng',
            'children' => array(
                array('name' => 'Đồ chơi cho chó', 'slug' => 'do-choi-cho-cho'),
                array('name' => 'Đồ chơi cho mèo', 'slug' => 'do-choi-cho-meo'),
            )
        ),
        array(
            'name' => 'Chuồng & Nhà',
            'slug' => 'chuong-nha',
            'description' => 'Chuồng và nhà cho thú cưng',
            'children' => array(
                array('name' => 'Chuồng cho chó', 'slug' => 'chuong-cho-cho'),
                array('name' => 'Chuồng cho mèo', 'slug' => 'chuong-cho-meo'),
                array('name' => 'Lồng cho chim', 'slug' => 'long-cho-chim'),
                array('name' => 'Bể cá', 'slug' => 'be-ca'),
            )
        ),
        array(
            'name' => 'Vệ sinh & Chăm sóc',
            'slug' => 've-sinh-cham-soc',
            'description' => 'Sản phẩm vệ sinh và chăm sóc thú cưng',
            'children' => array(
                array('name' => 'Sữa tắm', 'slug' => 'sua-tam'),
                array('name' => 'Bàn chải & Lược', 'slug' => 'ban-chai-luoc'),
                array('name' => 'Khay vệ sinh', 'slug' => 'khay-ve-sinh'),
                array('name' => 'Cát vệ sinh', 'slug' => 'cat-ve-sinh'),
            )
        ),
        array(
            'name' => 'Y tế & Thuốc',
            'slug' => 'y-te-thuoc',
            'description' => 'Sản phẩm y tế và thuốc cho thú cưng',
            'children' => array(
                array('name' => 'Vitamin & Bổ sung', 'slug' => 'vitamin-bo-sung'),
                array('name' => 'Thuốc trị ve rận', 'slug' => 'thuoc-tri-ve-ran'),
                array('name' => 'Thuốc xổ giun', 'slug' => 'thuoc-xo-giun'),
            )
        ),
    );

    foreach ($default_categories as $category) {
        // Kiểm tra danh mục cha đã tồn tại chưa
        $parent_term = term_exists($category['slug'], 'product_category');
        
        if (!$parent_term) {
            // Tạo danh mục cha
            $parent_term = wp_insert_term(
                $category['name'],
                'product_category',
                array(
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                )
            );
        }
        
        // Tạo danh mục con
        if (!is_wp_error($parent_term) && isset($category['children'])) {
            $parent_id = is_array($parent_term) ? $parent_term['term_id'] : $parent_term;
            
            foreach ($category['children'] as $child) {
                if (!term_exists($child['slug'], 'product_category')) {
                    wp_insert_term(
                        $child['name'],
                        'product_category',
                        array(
                            'slug' => $child['slug'],
                            'parent' => $parent_id,
                        )
                    );
                }
            }
        }
    }
}

// Chạy khi kích hoạt theme
function petshop_theme_activation() {
    // Đăng ký post type và taxonomy trước
    petshop_register_product_post_type();
    
    // Tạo danh mục mặc định
    petshop_create_default_product_categories();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'petshop_theme_activation');

// Cũng có thể chạy thủ công qua admin
function petshop_add_admin_menu_for_categories() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Tạo danh mục mặc định',
        'Tạo danh mục mặc định',
        'manage_options',
        'petshop-create-categories',
        'petshop_create_categories_page'
    );
}
add_action('admin_menu', 'petshop_add_admin_menu_for_categories');

function petshop_create_categories_page() {
    if (isset($_POST['create_categories']) && check_admin_referer('petshop_create_categories')) {
        petshop_create_default_product_categories();
        echo '<div class="notice notice-success"><p>Đã tạo các danh mục sản phẩm mặc định!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Tạo danh mục sản phẩm mặc định</h1>
        <p>Click nút bên dưới để tạo các danh mục sản phẩm mặc định cho Pet Shop:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Thức ăn</strong> (Cho chó, mèo, hamster, chim)</li>
            <li><strong>Phụ kiện</strong> (Vòng cổ, quần áo, nơ trang trí)</li>
            <li><strong>Đồ chơi</strong> (Cho chó, cho mèo)</li>
            <li><strong>Chuồng & Nhà</strong> (Chuồng chó, mèo, lồng chim, bể cá)</li>
            <li><strong>Vệ sinh & Chăm sóc</strong> (Sữa tắm, bàn chải, khay vệ sinh)</li>
            <li><strong>Y tế & Thuốc</strong> (Vitamin, thuốc ve rận, xổ giun)</li>
        </ul>
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('petshop_create_categories'); ?>
            <button type="submit" name="create_categories" class="button button-primary button-large">
                <span class="dashicons dashicons-category" style="margin-top: 4px;"></span>
                Tạo danh mục mặc định
            </button>
        </form>
    </div>
    <?php
}

// =============================================
// PRODUCT META BOX (V2 - New Admin Interface)
// =============================================
require_once get_template_directory() . '/inc/admin-product.php';

// =============================================
// REWRITE RULES
// =============================================
function petshop_rewrite_rules() {
    add_rewrite_rule('^tin-tuc/?$', 'index.php?pagename=tin-tuc', 'top');
    add_rewrite_rule('^san-pham/([^/]+)/?$', 'index.php?post_type=product&name=$matches[1]', 'top');
    add_rewrite_rule('^san-pham/?$', 'index.php?post_type=product', 'top');
}
add_action('init', 'petshop_rewrite_rules');

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Format price in Vietnamese format
 */
function petshop_format_price($price) {
    if (empty($price)) return '';
    return number_format($price, 0, ',', '.') . '₫';
}

/**
 * Get product price HTML
 */
function petshop_get_product_price_html($post_id) {
    $price = get_post_meta($post_id, 'product_price', true);
    $sale_price = get_post_meta($post_id, 'product_sale_price', true);
    
    $html = '<div class="product-price">';
    if (!empty($sale_price) && $sale_price < $price) {
        $html .= '<span class="price-old">' . petshop_format_price($price) . '</span>';
        $html .= '<span class="price-current">' . petshop_format_price($sale_price) . '</span>';
    } elseif (!empty($price)) {
        $html .= '<span class="price-current">' . petshop_format_price($price) . '</span>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Get reading time
 */
function petshop_get_reading_time($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200);
    return $reading_time . ' phút đọc';
}

/**
 * Custom excerpt
 */
function petshop_excerpt($limit = 25, $post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $excerpt = get_the_excerpt($post_id);
    
    if (empty($excerpt)) {
        $excerpt = get_post_field('post_content', $post_id);
    }
    
    return wp_trim_words($excerpt, $limit, '...');
}

// =============================================
// AUTO CREATE REQUIRED PAGES
// =============================================
function petshop_create_pages() {
    // Define pages to create
    $pages = array(
        array(
            'slug' => 'tin-tuc',
            'title' => 'Tin tức',
            'template' => 'page-tin-tuc.php'
        ),
        array(
            'slug' => 'dang-nhap',
            'title' => 'Đăng nhập',
            'template' => 'page-dang-nhap.php'
        ),
        array(
            'slug' => 'dang-ky',
            'title' => 'Đăng ký',
            'template' => 'page-dang-ky.php'
        )
    );
    
    foreach ($pages as $page_data) {
        $existing_page = get_page_by_path($page_data['slug']);
        if (!$existing_page) {
            $page_id = wp_insert_post(array(
                'post_title'   => $page_data['title'],
                'post_name'    => $page_data['slug'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_post_meta($page_id, '_wp_page_template', $page_data['template']);
            }
        }
    }
}
add_action('after_switch_theme', 'petshop_create_pages');

// =============================================
// FLUSH REWRITE ON ACTIVATION
// =============================================
function petshop_activation() {
    petshop_register_product_post_type();
    petshop_rewrite_rules();
    petshop_create_pages();
    petshop_create_variants_table();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'petshop_activation');

// =============================================
// TẠO BẢNG PRODUCT VARIANTS
// =============================================
function petshop_create_variants_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $charset_collate = $wpdb->get_charset_collate();

    // Kiểm tra bảng đã tồn tại chưa
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
        return;
    }

    $sql = "CREATE TABLE {$table} (
        id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id    BIGINT(20) UNSIGNED NOT NULL,
        size          VARCHAR(50)  DEFAULT NULL COMMENT 'VD: S, M, L, XL, 28, 30...',
        color         VARCHAR(100) DEFAULT NULL COMMENT 'VD: Đỏ, Xanh, Đen...',
        color_hex     VARCHAR(10)  DEFAULT NULL COMMENT 'Mã màu hex, VD: #FF0000',
        image_id      BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Attachment ID ảnh riêng của phân loại',
        sku           VARCHAR(100) DEFAULT NULL,
        stock         INT(11)      NOT NULL DEFAULT 0,
        variant_price BIGINT(20)   UNSIGNED DEFAULT NULL COMMENT 'Giá thực của phân loại này (NULL = dùng giá sản phẩm)',
        sort_order    INT(11)      NOT NULL DEFAULT 0,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY size (size),
        KEY color (color)
    ) {$charset_collate};";

    // Migration: thêm image_id nếu chưa có
    $col_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'image_id'");
    if (empty($col_exists)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN image_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER color_hex");
    }
    // Migration: thêm variant_price (thay thế price_diff)
    $vp_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'variant_price'");
    if (empty($vp_exists)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN variant_price BIGINT(20) UNSIGNED DEFAULT NULL AFTER image_id");
    }
    // Migration: xóa price_diff cũ nếu còn (an toàn)
    $pd_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'price_diff'");
    if (!empty($pd_exists)) {
        // Copy data rồi xóa
        $wpdb->query("UPDATE {$table} SET variant_price = NULL WHERE price_diff = 0 AND variant_price IS NULL");
        // Không drop để an toàn, chỉ bỏ dùng
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('admin_init', 'petshop_create_variants_table');

// =============================================
// HELPER FUNCTIONS CHO VARIANTS
// =============================================

/**
 * Lấy tất cả variants của 1 sản phẩm
 */
function petshop_get_product_variants($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE product_id = %d ORDER BY sort_order ASC, size ASC, color ASC",
        $product_id
    ), ARRAY_A);
}

/**
 * Lấy 1 variant theo id
 */
function petshop_get_variant($variant_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d", $variant_id
    ), ARRAY_A);
}

/**
 * Lấy variant theo product_id + size + color
 */
function petshop_find_variant($product_id, $size = null, $color = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $where = $wpdb->prepare("product_id = %d", $product_id);
    if ($size  !== null) $where .= $wpdb->prepare(" AND size = %s",  $size);
    if ($color !== null) $where .= $wpdb->prepare(" AND color = %s", $color);
    return $wpdb->get_row("SELECT * FROM {$table} WHERE {$where}", ARRAY_A);
}

/**
 * Kiểm tra sản phẩm có variants không
 */
function petshop_has_variants($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $product_id
    ));
    return intval($count) > 0;
}

/**
 * Lấy danh sách size unique của sản phẩm (có stock > 0 hoặc tất cả)
 */
function petshop_get_product_sizes($product_id, $in_stock_only = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $stock_clause = $in_stock_only ? "AND stock > 0" : "";
    $results = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT size FROM {$table}
         WHERE product_id = %d AND size IS NOT NULL AND size != '' {$stock_clause}
         ORDER BY sort_order ASC",
        $product_id
    ));
    return array_filter($results);
}

/**
 * Lấy danh sách màu của sản phẩm theo size (hoặc tất cả)
 */
function petshop_get_product_colors($product_id, $size = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    if ($size) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT color, color_hex, image_id, SUM(stock) as total_stock
             FROM {$table}
             WHERE product_id = %d AND size = %s AND color IS NOT NULL AND color != ''
             GROUP BY color, color_hex, image_id
             ORDER BY MIN(sort_order) ASC",
            $product_id, $size
        ), ARRAY_A);
    } else {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT color, color_hex, image_id, SUM(stock) as total_stock
             FROM {$table}
             WHERE product_id = %d AND color IS NOT NULL AND color != ''
             GROUP BY color, color_hex, image_id
             ORDER BY MIN(sort_order) ASC",
            $product_id
        ), ARRAY_A);
    }
    return $results;
}

/**
 * Tính tổng stock của sản phẩm từ variants và sync vào postmeta
 */
function petshop_sync_variant_stock($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(stock) FROM {$table} WHERE product_id = %d", $product_id
    ));
    $total = intval($total);
    update_post_meta($product_id, 'product_stock', $total);
    return $total;
}

/**
 * Trừ stock variant khi đặt hàng
 * @return bool true nếu thành công
 */
function petshop_reduce_variant_stock($variant_id, $quantity) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $variant = petshop_get_variant($variant_id);
    if (!$variant) return false;

    $new_stock = max(0, intval($variant['stock']) - intval($quantity));
    $wpdb->update($table, array('stock' => $new_stock), array('id' => $variant_id));
    petshop_sync_variant_stock($variant['product_id']);
    return true;
}

/**
 * Lưu danh sách variants từ POST data vào DB
 */
function petshop_save_product_variants($product_id, $variants_data) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';

    // Xóa variants cũ
    $wpdb->delete($table, array('product_id' => $product_id));

    if (empty($variants_data)) {
        // Không có variant — xóa flag
        delete_post_meta($product_id, 'product_has_variants');
        return;
    }

    update_post_meta($product_id, 'product_has_variants', 1);
    $order = 0;
    foreach ($variants_data as $v) {
        $size       = isset($v['size'])       ? sanitize_text_field($v['size'])       : null;
        $color      = isset($v['color'])      ? sanitize_text_field($v['color'])      : null;
        $color_hex  = isset($v['color_hex'])  ? sanitize_text_field($v['color_hex'])  : null;
        $image_id   = isset($v['image_id'])   ? intval($v['image_id'])                : null;
        $sku        = isset($v['sku'])        ? sanitize_text_field($v['sku'])        : null;
        $stock      = isset($v['stock'])      ? max(0, intval($v['stock']))           : 0;
        $variant_price = isset($v['variant_price']) && $v['variant_price'] !== '' ? intval($v['variant_price']) : null;

        if (($size === null || $size === '') && ($color === null || $color === '')) continue;

        $wpdb->insert($table, array(
            'product_id'    => $product_id,
            'size'          => $size      ?: null,
            'color'         => $color     ?: null,
            'color_hex'     => $color_hex ?: null,
            'image_id'      => $image_id  ?: null,
            'sku'           => $sku       ?: null,
            'stock'         => $stock,
            'variant_price' => $variant_price,
            'sort_order'    => $order++,
        ));
    }

    // Sync tổng stock vào postmeta
    petshop_sync_variant_stock($product_id);
}

// =============================================
// BREADCRUMB
// =============================================
function petshop_breadcrumb() {
    $separator = '<span class="sep">›</span>';
    echo '<nav class="breadcrumb">';
    echo '<a href="' . home_url() . '">Trang chủ</a>';
    if (is_single()) {
        echo $separator;
        $categories = get_the_category();
        if (!empty($categories)) {
            // Lấy danh mục nhỏ nhất
            $child = null;
            foreach ($categories as $cat) {
                if ($cat->parent) {
                    $child = $cat;
                    break;
                }
            }
            if (!$child) $child = $categories[0];
            // Lấy danh mục lớn
            $parent = ($child->parent) ? get_category($child->parent) : null;
            if ($parent) {
                echo '<a href="' . get_category_link($parent->term_id) . '">' . $parent->name . '</a>' . $separator;
            }
            echo '<a href="' . get_category_link($child->term_id) . '">' . $child->name . '</a>' . $separator;
        }
        echo '<span class="current">' . get_the_title() . '</span>';
    } elseif (is_page()) {
        echo $separator;
        echo '<span class="current">' . get_the_title() . '</span>';
    } elseif (is_category()) {
        $cat = get_queried_object();
        if ($cat && $cat->parent) {
            $parent = get_category($cat->parent);
            echo $separator;
            echo '<a href="' . get_category_link($parent->term_id) . '">' . $parent->name . '</a>' . $separator;
        } else {
            echo $separator;
        }
        echo '<span class="current">' . single_cat_title('', false) . '</span>';
    } elseif (is_archive()) {
        echo $separator;
        echo '<span class="current">' . post_type_archive_title('', false) . '</span>';
    } elseif (is_search()) {
        echo $separator;
        echo '<span class="current">Tìm kiếm: ' . get_search_query() . '</span>';
    }
    echo '</nav>';
}

// Login redirect được xử lý trong inc/custom-login.php

// =============================================
// REMOVE ADMIN BAR FOR NON-ADMINS
// =============================================
function petshop_remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'petshop_remove_admin_bar');

// JS: Chỉ cho phép chọn 1 danh mục con (radio), disable danh mục lớn
add_action('admin_footer', function() {
    global $pagenow;
    if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var catBox = document.querySelector("#categorychecklist");
            if (!catBox) return;
            var items = catBox.querySelectorAll("li");
            items.forEach(function(li) {
                var cbInput = li.querySelector("input[type=checkbox]");
                if (!cbInput) return;
                var isParent = cbInput.getAttribute("data-parent") == "0" || li.classList.contains("cat-parent");
                if (isParent) {
                    cbInput.disabled = true;
                    cbInput.style.display = "none";
                    li.style.opacity = 0.5;
                } else {
                    cbInput.type = "radio";
                }
            });
            catBox.addEventListener("change", function(e) {
                if (e.target.type === "radio") {
                    items.forEach(function(li) {
                        var inputRadio = li.querySelector("input[type=radio]");
                        if (inputRadio && inputRadio !== e.target) inputRadio.checked = false;
                    });
                    // Auto-select parent
                    var selected = e.target;
                    var parentId = selected.getAttribute("data-parent");
                    if (parentId && parentId != "0") {
                        var parentInput = catBox.querySelector(\'input[value="\' + parentId + \'\"]\');
                        if (parentInput) parentInput.checked = true;
                    }
                }
            });
        });
        </script>';
    }
});

// Xử lý AJAX thêm/xóa sản phẩm yêu thích
add_action('wp_ajax_petshop_toggle_favorite', 'petshop_toggle_favorite_callback');
add_action('wp_ajax_nopriv_petshop_toggle_favorite', 'petshop_toggle_favorite_callback');
function petshop_toggle_favorite_callback() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Bạn cần đăng nhập để sử dụng chức năng này.']);
    }
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'petshop_favorites', true);
    if (!is_array($favorites)) $favorites = array();
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $action = isset($_POST['action_favorite']) ? $_POST['action_favorite'] : '';
    if ($product_id) {
        if ($action === 'add') {
            if (!in_array($product_id, $favorites)) {
                $favorites[] = $product_id;
                update_user_meta($user_id, 'petshop_favorites', $favorites);
            }
            wp_send_json_success(['message' => 'Đã lưu vào yêu thích!']);
        } elseif ($action === 'remove') {
            $favorites = array_diff($favorites, [$product_id]);
            update_user_meta($user_id, 'petshop_favorites', $favorites);
            wp_send_json_success(['message' => 'Đã xóa khỏi yêu thích!']);
        }
    }
    wp_send_json_error(['message' => 'Thao tác không hợp lệ.']);
}

// =============================================
// AJAX: QUICK VIEW SẢN PHẨM (popup xem nhanh)
// =============================================
add_action('wp_ajax_petshop_quick_view', 'petshop_ajax_quick_view');
add_action('wp_ajax_nopriv_petshop_quick_view', 'petshop_ajax_quick_view');
function petshop_ajax_quick_view() {
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) wp_send_json_error('Invalid product');

    $post = get_post($product_id);
    if (!$post || $post->post_type !== 'product') wp_send_json_error('Not found');

    $price        = get_post_meta($product_id, 'product_price', true);
    $stock        = get_post_meta($product_id, 'product_stock', true);
    $sku          = get_post_meta($product_id, 'product_sku', true);
    $price_info   = function_exists('petshop_get_display_price') ? petshop_get_display_price($product_id) : array('is_on_sale'=>false,'sale'=>null,'original'=>$price,'discount_percent'=>0);
    $thumb        = get_the_post_thumbnail_url($product_id, 'large') ?: '';
    $has_variants = function_exists('petshop_has_variants') ? petshop_has_variants($product_id) : false;
    $variants     = $has_variants && function_exists('petshop_get_product_variants') ? petshop_get_product_variants($product_id) : array();

    // Tính khoảng giá nếu có variants
    $price_range  = null;
    if ($has_variants && !empty($variants)) {
        $prices = array_filter(array_column($variants, 'variant_price'), fn($p) => $p !== null && $p > 0);
        if (!empty($prices)) {
            $price_range = array('min' => min($prices), 'max' => max($prices));
        }
    }

    // Build variants for JS
    $variants_js = array();
    foreach ($variants as $v) {
        $img_url = '';
        if (!empty($v['image_id'])) {
            $img_url = wp_get_attachment_image_url($v['image_id'], 'large') ?: '';
        }
        $variants_js[] = array(
            'id'            => intval($v['id']),
            'size'          => $v['size'],
            'color'         => $v['color'],
            'color_hex'     => $v['color_hex'],
            'stock'         => intval($v['stock']),
            'variant_price' => $v['variant_price'] !== null ? intval($v['variant_price']) : null,
            'sku'           => $v['sku'],
            'image_url'     => $img_url,
        );
    }

    // Unique sizes & colors
    $sizes  = array_values(array_unique(array_filter(array_column($variants, 'size'))));
    $colors_raw = array();
    foreach ($variants as $v) {
        if ($v['color'] && !isset($colors_raw[$v['color']])) {
            $colors_raw[$v['color']] = array('name'=>$v['color'],'hex'=>$v['color_hex']??'#E8CCAD');
        }
    }
    $colors = array_values($colors_raw);

    // Categories
    $cats     = get_the_terms($product_id, 'product_category');
    $cat_name = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';

    wp_send_json_success(array(
        'id'           => $product_id,
        'name'         => $post->post_title,
        'excerpt'      => wp_trim_words($post->post_excerpt ?: $post->post_content, 25, '...'),
        'url'          => get_permalink($product_id),
        'thumb'        => $thumb,
        'price'        => intval($price),
        'price_info'   => $price_info,
        'price_range'  => $price_range,
        'stock'        => intval($stock),
        'sku'          => $sku,
        'cat_name'     => $cat_name,
        'has_variants' => $has_variants,
        'variants'     => $variants_js,
        'sizes'        => $sizes,
        'colors'       => $colors,
    ));
}

// Tăng giới hạn upload cho plugin All-in-One WP Migration
add_filter( 'ai1wm_max_file_size', function ( $size ) {
    return 512 * 1024 * 1024; // Thiết lập giới hạn là 512MB
} );