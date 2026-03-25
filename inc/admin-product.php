<?php
/**
 * PetShop Admin Product Management
 * Trang quản lý sản phẩm tùy chỉnh - Giao diện đơn giản, dễ sử dụng
 */

if (!defined('ABSPATH')) exit;

// =============================================
// VARIANT HELPERS — tự định nghĩa ở đây để đảm bảo luôn có
// =============================================
function petshop_ensure_variants_table() {
    global $wpdb;
    $table           = $wpdb->prefix . 'petshop_variants';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        $sql = "CREATE TABLE {$table} (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id    BIGINT(20) UNSIGNED NOT NULL,
            size          VARCHAR(50)  DEFAULT NULL,
            color         VARCHAR(100) DEFAULT NULL,
            color_hex     VARCHAR(10)  DEFAULT NULL,
            image_id      BIGINT(20)   UNSIGNED DEFAULT NULL,
            sku           VARCHAR(100) DEFAULT NULL,
            stock         INT(11)      NOT NULL DEFAULT 0,
            variant_price BIGINT(20)   UNSIGNED DEFAULT NULL,
            sort_order    INT(11)      NOT NULL DEFAULT 0,
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    } else {
        // Migration: thêm image_id nếu chưa có
        if (!$wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'image_id'")) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN image_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER color_hex");
        }
        // Migration: thêm variant_price nếu chưa có
        if (!$wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'variant_price'")) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN variant_price BIGINT(20) UNSIGNED DEFAULT NULL AFTER image_id");
        }
    }
}

if (!function_exists('petshop_get_product_variants')) {
    function petshop_get_product_variants($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_variants';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return array();
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE product_id = %d ORDER BY sort_order ASC, id ASC", $product_id),
            ARRAY_A
        ) ?: array();
    }
}

if (!function_exists('petshop_has_variants')) {
    function petshop_has_variants($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_variants';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return false;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $product_id)) > 0;
    }
}

if (!function_exists('petshop_sync_variant_stock')) {
    function petshop_sync_variant_stock($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_variants';
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(stock),0) FROM {$table} WHERE product_id = %d", $product_id));
        update_post_meta($product_id, 'product_stock', $total);
        return $total;
    }
}

if (!function_exists('petshop_save_product_variants')) {
    function petshop_save_product_variants($product_id, $variants_data) {
        global $wpdb;
        petshop_ensure_variants_table();
        $table = $wpdb->prefix . 'petshop_variants';

        // Xóa variants cũ của sản phẩm này
        $wpdb->delete($table, array('product_id' => intval($product_id)));

        if (empty($variants_data) || !is_array($variants_data)) {
            delete_post_meta($product_id, 'product_has_variants');
            update_post_meta($product_id, 'product_stock', get_post_meta($product_id, 'product_stock', true) ?: 0);
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

            // Bỏ qua dòng không có size lẫn color
            if (($size === null || $size === '') && ($color === null || $color === '')) {
                continue;
            }

            $wpdb->insert($table, array(
                'product_id'  => intval($product_id),
                'size'        => $size  ?: null,
                'color'       => $color ?: null,
                'color_hex'   => $color_hex ?: null,
                'image_id'    => $image_id  ?: null,
                'sku'         => $sku   ?: null,
                'stock'       => $stock,
                'variant_price' => $variant_price,
                'sort_order'  => $order++,
            ));
        }

        // Sync tổng stock vào postmeta
        petshop_sync_variant_stock($product_id);
    }
}

// Đảm bảo bảng tồn tại khi vào trang admin sản phẩm
add_action('admin_init', 'petshop_ensure_variants_table');

// =============================================
// ĐĂNG KÝ MENU ADMIN
// =============================================
function petshop_register_product_admin_pages() {
    // Thêm submenu "Thêm sản phẩm mới" với giao diện tùy chỉnh
    add_submenu_page(
        'edit.php?post_type=product',
        'Thêm sản phẩm mới',
        'Thêm sản phẩm mới',
        'edit_posts',
        'petshop-add-product',
        'petshop_add_product_page'
    );
}
add_action('admin_menu', 'petshop_register_product_admin_pages');

// Ẩn menu "Add New" mặc định của WordPress
function petshop_hide_default_add_new() {
    global $submenu;
    if (isset($submenu['edit.php?post_type=product'])) {
        foreach ($submenu['edit.php?post_type=product'] as $key => $item) {
            if ($item[2] === 'post-new.php?post_type=product') {
                unset($submenu['edit.php?post_type=product'][$key]);
            }
        }
    }
}
add_action('admin_menu', 'petshop_hide_default_add_new', 999);

// Redirect từ trang add new mặc định sang trang custom
function petshop_redirect_default_add_new() {
    global $pagenow, $typenow;
    if ($pagenow === 'post-new.php' && $typenow === 'product') {
        wp_redirect(admin_url('edit.php?post_type=product&page=petshop-add-product'));
        exit;
    }
}
add_action('admin_init', 'petshop_redirect_default_add_new');

// =============================================
// XỬ LÝ LƯU SẢN PHẨM
// =============================================
function petshop_handle_product_save() {
    if (!isset($_POST['petshop_product_nonce']) || 
        !wp_verify_nonce($_POST['petshop_product_nonce'], 'petshop_save_product')) {
        return;
    }
    
    if (!current_user_can('edit_posts')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    // Chuẩn bị dữ liệu post
    $post_data = array(
        'post_title'   => sanitize_text_field($_POST['product_name']),
        'post_content' => wp_kses_post($_POST['product_description']),
        'post_excerpt' => sanitize_textarea_field($_POST['product_short_desc']),
        'post_status'  => sanitize_text_field($_POST['product_status']),
        'post_type'    => 'product',
    );
    
    if ($product_id > 0) {
        $post_data['ID'] = $product_id;
        wp_update_post($post_data);
    } else {
        $product_id = wp_insert_post($post_data);
    }
    
    if ($product_id && !is_wp_error($product_id)) {
        // Lưu meta fields
        $meta_fields = array(
            'product_price', 'product_sale_price', 'product_sku', 
            'product_stock', 'product_brand', 'product_origin', 'product_origin_custom',
            'product_weight', 'discount_type', 'discount_value',
            'discount_has_expiry', 'discount_expiry_date', 'discount_expiry_time',
            'product_gallery', 'product_primary_image', 'low_stock_threshold'
        );
        
        $has_variants_post = isset($_POST['enable_variants']) ? true : false;
        
        foreach ($meta_fields as $field) {
            // Nếu sản phẩm có variants, KHÔNG ghi product_stock từ POST
            // vì stock sẽ được tính tự động từ tổng variants
            if ($field === 'product_stock' && $has_variants_post) {
                continue;
            }
            if (isset($_POST[$field])) {
                update_post_meta($product_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Tính giá sale tự động
        $price = floatval($_POST['product_price']);
        $discount_type = sanitize_text_field($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        
        if ($discount_value > 0 && $price > 0) {
            if ($discount_type === 'percent') {
                $sale_price = $price - ($price * $discount_value / 100);
            } else {
                $sale_price = $price - $discount_value;
            }
            $sale_price = max(0, round($sale_price));
            update_post_meta($product_id, 'product_sale_price', $sale_price);
        } else {
            update_post_meta($product_id, 'product_sale_price', '');
        }
        
        // Lưu danh mục
        if (isset($_POST['product_categories'])) {
            $categories = array_map('intval', $_POST['product_categories']);
            wp_set_post_terms($product_id, $categories, 'product_category');
        } else {
            wp_set_post_terms($product_id, array(), 'product_category');
        }
        
        // Set featured image
        if (!empty($_POST['product_primary_image'])) {
            set_post_thumbnail($product_id, intval($_POST['product_primary_image']));
        } elseif (!empty($_POST['product_gallery'])) {
            $gallery = explode(',', $_POST['product_gallery']);
            if (!empty($gallery[0])) {
                set_post_thumbnail($product_id, intval($gallery[0]));
            }
        }

        // =============================================
        // LƯU VARIANTS (size / màu / ảnh / tồn kho)
        // =============================================
        $enable_variants = isset($_POST['enable_variants']) ? 1 : 0;

        if ($enable_variants && function_exists('petshop_save_product_variants')) {
            $variants_raw = isset($_POST['variants']) && is_array($_POST['variants'])
                ? $_POST['variants'] : array();
            petshop_save_product_variants($product_id, $variants_raw);
            // Sau khi lưu variants, product_stock đã được sync tự động
            // → KHÔNG ghi đè bằng giá trị POST nữa
        } else {
            // Không có variants → đảm bảo xóa flag và giữ product_stock từ POST
            delete_post_meta($product_id, 'product_has_variants');
            if (function_exists('petshop_save_product_variants')) {
                petshop_save_product_variants($product_id, array()); // xóa variants cũ nếu có
            }
        }

        // Redirect với thông báo thành công
        $redirect_url = admin_url('edit.php?post_type=product&page=petshop-add-product&product_id=' . $product_id . '&message=saved');
        wp_redirect($redirect_url);
        exit;
    }
}
add_action('admin_post_petshop_save_product', 'petshop_handle_product_save');

// =============================================
// TRANG THÊM/SỬA SẢN PHẨM
// =============================================
function petshop_add_product_page() {
    // Enqueue media uploader
    wp_enqueue_media();
    
    // Lấy product nếu đang edit
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $product = null;
    $is_edit = false;
    
    if ($product_id > 0) {
        $product = get_post($product_id);
        if ($product && $product->post_type === 'product') {
            $is_edit = true;
        }
    }
    
    // Lấy dữ liệu hiện có
    $product_name = $is_edit ? $product->post_title : '';
    $product_desc = $is_edit ? $product->post_content : '';
    $product_short_desc = $is_edit ? $product->post_excerpt : '';
    $product_status = $is_edit ? $product->post_status : 'publish';
    
    $price = $is_edit ? get_post_meta($product_id, 'product_price', true) : '';
    $sale_price = $is_edit ? get_post_meta($product_id, 'product_sale_price', true) : '';
    $sku = $is_edit ? get_post_meta($product_id, 'product_sku', true) : '';
    $stock = $is_edit ? get_post_meta($product_id, 'product_stock', true) : '';
    $low_stock_threshold = $is_edit ? get_post_meta($product_id, 'low_stock_threshold', true) : '5';
    $has_variants      = $is_edit ? get_post_meta($product_id, 'product_has_variants', true) : '';
    $existing_variants = ($is_edit && function_exists('petshop_get_product_variants'))
        ? petshop_get_product_variants($product_id) : array();
    $brand = $is_edit ? get_post_meta($product_id, 'product_brand', true) : '';
    $origin = $is_edit ? get_post_meta($product_id, 'product_origin', true) : '';
    $weight = $is_edit ? get_post_meta($product_id, 'product_weight', true) : '';
    $discount_type = $is_edit ? get_post_meta($product_id, 'discount_type', true) : 'percent';
    $discount_value = $is_edit ? get_post_meta($product_id, 'discount_value', true) : '';
    $discount_has_expiry = $is_edit ? get_post_meta($product_id, 'discount_has_expiry', true) : '';
    $discount_expiry_date = $is_edit ? get_post_meta($product_id, 'discount_expiry_date', true) : '';
    $discount_expiry_time = $is_edit ? get_post_meta($product_id, 'discount_expiry_time', true) : '23:59';
    $origin_custom = $is_edit ? get_post_meta($product_id, 'product_origin_custom', true) : '';
    $gallery_ids = $is_edit ? get_post_meta($product_id, 'product_gallery', true) : '';
    $primary_image = $is_edit ? get_post_meta($product_id, 'product_primary_image', true) : '';
    
    // Lấy danh mục
    $all_categories = get_terms(array(
        'taxonomy' => 'product_category',
        'hide_empty' => false,
    ));
    $selected_categories = $is_edit ? wp_get_post_terms($product_id, 'product_category', array('fields' => 'ids')) : array();
    
    // Hiển thị thông báo
    $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>
    
    <style>
    .petshop-admin-wrap {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 20px;
    }
    
    .petshop-admin-wrap h1 {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .petshop-admin-wrap h1 .dashicons {
        font-size: 28px;
        width: 28px;
        height: 28px;
    }
    
    .petshop-form-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 20px;
    }
    
    @media (max-width: 1024px) {
        .petshop-form-layout {
            grid-template-columns: 1fr;
        }
    }
    
    .petshop-card {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .petshop-card-header {
        padding: 12px 15px;
        border-bottom: 1px solid #c3c4c7;
        background: #f6f7f7;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .petshop-card-body {
        padding: 15px;
    }
    
    .petshop-form-group {
        margin-bottom: 15px;
    }
    
    .petshop-form-group:last-child {
        margin-bottom: 0;
    }
    
    .petshop-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #1d2327;
    }
    
    .petshop-form-group label .required {
        color: #d63638;
    }
    
    .petshop-form-group .description {
        font-size: 12px;
        color: #646970;
        margin-top: 4px;
    }
    
    .petshop-form-group input[type="text"],
    .petshop-form-group input[type="number"],
    .petshop-form-group select,
    .petshop-form-group textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #8c8f94;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .petshop-form-group input:focus,
    .petshop-form-group select:focus,
    .petshop-form-group textarea:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    
    .petshop-form-group textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .petshop-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    @media (max-width: 600px) {
        .petshop-form-row {
            grid-template-columns: 1fr;
        }
    }
    
    .price-input-wrap {
        position: relative;
    }
    
    .price-input-wrap input {
        padding-right: 50px;
    }
    
    .price-input-wrap .currency {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #646970;
        font-weight: 500;
        pointer-events: none;
    }
    
    .price-text {
        font-size: 12px;
        color: #2271b1;
        font-style: italic;
        margin-top: 4px;
        min-height: 16px;
    }
    
    .discount-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    
    .discount-row .discount-value {
        flex: 1;
    }
    
    .discount-row .discount-type {
        width: 140px;
    }
    
    .sale-price-display {
        background: #f0f6fc;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 10px 12px;
        margin-top: 10px;
    }
    
    .sale-price-display .label {
        font-size: 12px;
        color: #646970;
        margin-bottom: 4px;
    }
    
    .sale-price-display .price {
        font-size: 18px;
        font-weight: 700;
        color: #00a32a;
    }
    
    .sale-price-display .original {
        font-size: 14px;
        color: #646970;
        text-decoration: line-through;
        margin-left: 10px;
    }
    
    /* Gallery */
    .gallery-upload-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 12px;
        background: #f6f7f7;
        border: 1px dashed #8c8f94;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .gallery-upload-btn:hover {
        background: #f0f0f1;
        border-color: #2271b1;
    }
    
    .gallery-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .gallery-item {
        position: relative;
        width: 80px;
        height: 80px;
        border-radius: 4px;
        overflow: hidden;
        border: 2px solid #c3c4c7;
    }
    
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .gallery-item .remove-btn {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 20px;
        height: 20px;
        background: #d63638;
        color: #fff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        display: none;
    }
    
    .gallery-item:hover .remove-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gallery-item.is-primary {
        border-color: #2271b1;
    }
    
    .gallery-item.is-primary::after {
        content: "Chính";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: #2271b1;
        color: #fff;
        font-size: 10px;
        text-align: center;
        padding: 2px;
    }
    
    /* Categories */
    .category-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 10px;
    }
    
    .category-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
    }
    
    .category-item input[type="checkbox"] {
        margin: 0;
    }
    
    /* Stock status */
    .stock-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 5px;
    }
    
    .stock-status.in-stock { background: #d7f4d7; color: #0a5c0a; }
    .stock-status.low-stock { background: #fcf0c3; color: #6e4b00; }
    .stock-status.out-of-stock { background: #fcdbdb; color: #8a0000; }
    
    /* Submit buttons */
    .petshop-submit-wrap {
        padding: 15px;
        background: #f6f7f7;
        border-top: 1px solid #c3c4c7;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .button-primary-large {
        padding: 8px 20px !important;
        height: auto !important;
        font-size: 14px !important;
    }
    
    /* Notice */
    .petshop-notice {
        padding: 12px 15px;
        border-left: 4px solid #00a32a;
        background: #fff;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    }
    
    .petshop-notice.success {
        border-left-color: #00a32a;
    }
    
    /* SKU Generator */
    .sku-input-wrap {
        display: flex;
        gap: 8px;
    }
    
    .sku-input-wrap input {
        flex: 1;
    }
    
    .btn-generate-sku {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Origin custom input */
    .origin-custom-wrap {
        margin-top: 8px;
        display: none;
    }
    
    .origin-custom-wrap.show {
        display: block;
    }
    
    /* Discount expiry */
    .discount-expiry-wrap {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed #c3c4c7;
    }
    
    .discount-expiry-options {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .discount-expiry-options label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: normal;
        cursor: pointer;
    }
    
    .discount-date-wrap {
        display: none;
    }
    
    .discount-date-wrap.show {
        display: block;
    }
    
    .discount-date-wrap input[type="date"] {
        width: auto;
    }
    
    .discount-date-wrap input[type="time"] {
        width: auto;
    }
    
    .discount-datetime-row {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }
    
    /* Clear discount button */
    .btn-clear-discount {
        color: #d63638;
        border-color: #d63638;
        margin-top: 10px;
    }
    
    .btn-clear-discount:hover {
        background: #d63638;
        color: #fff;
    }
    
    /* Hierarchical categories */
    .category-tree {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 10px;
    }
    
    .category-parent {
        margin-bottom: 10px;
    }
    
    .category-parent:last-child {
        margin-bottom: 0;
    }
    
    .category-parent-header {
        font-weight: 600;
        color: #1d2327;
        padding: 5px 0;
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    
    .category-parent-header .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        transition: transform 0.2s;
    }
    
    .category-parent.collapsed .category-parent-header .dashicons {
        transform: rotate(-90deg);
    }
    
    .category-children {
        margin-left: 20px;
        padding-left: 10px;
        border-left: 2px solid #e0e0e0;
    }
    
    .category-parent.collapsed .category-children {
        display: none;
    }
    
    .category-child-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 0;
    }
    
    .category-child-item input[type="checkbox"] {
        margin: 0;
    }
    
    .category-no-parent {
        padding: 8px;
        background: #f6f7f7;
        border-radius: 4px;
        margin-bottom: 10px;
    }
    
    .category-no-parent .category-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 0;
    }
    </style>
    
    <div class="petshop-admin-wrap">
        <h1>
            <span class="dashicons dashicons-<?php echo $is_edit ? 'edit' : 'plus-alt'; ?>"></span>
            <?php echo $is_edit ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới'; ?>
        </h1>
        
        <?php if ($message === 'saved'): ?>
        <div class="petshop-notice success">
            <strong>Thành công!</strong> Sản phẩm đã được lưu.
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-add-product'); ?>">Thêm sản phẩm mới</a> | 
            <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Xem danh sách</a>
        </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="petshop-product-form">
            <input type="hidden" name="action" value="petshop_save_product">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <?php wp_nonce_field('petshop_save_product', 'petshop_product_nonce'); ?>
            
            <div class="petshop-form-layout">
                <!-- CỘT CHÍNH -->
                <div class="main-column">
                    
                    <!-- THÔNG TIN CƠ BẢN -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-info-outline"></span>
                            Thông tin cơ bản
                        </div>
                        <div class="petshop-card-body">
                            <div class="petshop-form-group">
                                <label for="product_name">
                                    Tên sản phẩm <span class="required">*</span>
                                </label>
                                <input type="text" id="product_name" name="product_name" 
                                       value="<?php echo esc_attr($product_name); ?>" 
                                       required placeholder="Nhập tên sản phẩm">
                            </div>
                            
                            <div class="petshop-form-row">
                                <div class="petshop-form-group">
                                    <label for="product_sku">Mã sản phẩm (SKU)</label>
                                    <div class="sku-input-wrap">
                                        <input type="text" id="product_sku" name="product_sku" 
                                               value="<?php echo esc_attr($sku); ?>" 
                                               placeholder="VD: PET-001">
                                        <button type="button" class="button btn-generate-sku" id="generate-sku-btn">
                                            <span class="dashicons dashicons-randomize"></span> Tạo mã
                                        </button>
                                    </div>
                                </div>
                                <div class="petshop-form-group">
                                    <label for="product_brand">Thương hiệu</label>
                                    <input type="text" id="product_brand" name="product_brand" 
                                           value="<?php echo esc_attr($brand); ?>" 
                                           placeholder="VD: Royal Canin">
                                </div>
                            </div>
                            
                            <div class="petshop-form-row">
                                <div class="petshop-form-group">
                                    <label for="product_origin">Xuất xứ</label>
                                    <select id="product_origin" name="product_origin">
                                        <option value="">-- Chọn xuất xứ --</option>
                                        <option value="vietnam" <?php selected($origin, 'vietnam'); ?>>Việt Nam</option>
                                        <option value="usa" <?php selected($origin, 'usa'); ?>>Mỹ</option>
                                        <option value="france" <?php selected($origin, 'france'); ?>>Pháp</option>
                                        <option value="germany" <?php selected($origin, 'germany'); ?>>Đức</option>
                                        <option value="japan" <?php selected($origin, 'japan'); ?>>Nhật Bản</option>
                                        <option value="korea" <?php selected($origin, 'korea'); ?>>Hàn Quốc</option>
                                        <option value="thailand" <?php selected($origin, 'thailand'); ?>>Thái Lan</option>
                                        <option value="china" <?php selected($origin, 'china'); ?>>Trung Quốc</option>
                                        <option value="australia" <?php selected($origin, 'australia'); ?>>Úc</option>
                                        <option value="uk" <?php selected($origin, 'uk'); ?>>Anh</option>
                                        <option value="other" <?php selected($origin, 'other'); ?>>Khác</option>
                                    </select>
                                    <div class="origin-custom-wrap <?php echo $origin === 'other' ? 'show' : ''; ?>" id="origin-custom-wrap">
                                        <input type="text" id="product_origin_custom" name="product_origin_custom" 
                                               value="<?php echo esc_attr($origin_custom); ?>" 
                                               placeholder="Nhập tên quốc gia khác...">
                                    </div>
                                </div>
                                <div class="petshop-form-group">
                                    <label for="product_weight">Khối lượng / Quy cách</label>
                                    <input type="text" id="product_weight" name="product_weight" 
                                           value="<?php echo esc_attr($weight); ?>" 
                                           placeholder="VD: 500g, 1kg, 250ml">
                                </div>
                            </div>
                            
                            <div class="petshop-form-group">
                                <label for="product_short_desc">Mô tả ngắn</label>
                                <textarea id="product_short_desc" name="product_short_desc" 
                                          rows="3" placeholder="Mô tả ngắn gọn về sản phẩm (hiển thị ở danh sách)"><?php echo esc_textarea($product_short_desc); ?></textarea>
                            </div>
                            
                            <div class="petshop-form-group">
                                <label for="product_description">Mô tả chi tiết</label>
                                <?php 
                                wp_editor($product_desc, 'product_description', array(
                                    'textarea_name' => 'product_description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'quicktags' => true,
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GIÁ SẢN PHẨM -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-money-alt"></span>
                            Giá sản phẩm
                        </div>
                        <div class="petshop-card-body">
                            <div class="petshop-form-row">
                                <div class="petshop-form-group">
                                    <label for="product_price">
                                        Giá gốc <span class="required">*</span>
                                    </label>
                                    <div class="price-input-wrap">
                                        <input type="number" id="product_price" name="product_price" 
                                               value="<?php echo esc_attr($price); ?>" 
                                               min="0" step="1000" required
                                               placeholder="0">
                                        <span class="currency">VNĐ</span>
                                    </div>
                                    <div class="price-text" id="price-text"></div>
                                </div>
                                <div class="petshop-form-group">
                                    <label>Giá sau giảm</label>
                                    <div class="price-input-wrap">
                                        <input type="text" id="product_sale_price_display" 
                                               value="<?php echo $sale_price ? number_format($sale_price) : ''; ?>" 
                                               readonly disabled
                                               placeholder="Tự động tính">
                                        <span class="currency">VNĐ</span>
                                    </div>
                                    <input type="hidden" name="product_sale_price" id="product_sale_price" value="<?php echo esc_attr($sale_price); ?>">
                                    <p class="description">Giá này được tự động tính từ mức giảm giá bên dưới</p>
                                </div>
                            </div>
                            
                            <div class="petshop-form-group">
                                <label>Giảm giá (để trống nếu không giảm)</label>
                                <div class="discount-row">
                                    <div class="discount-value">
                                        <input type="number" id="discount_value" name="discount_value" 
                                               value="<?php echo esc_attr($discount_value); ?>" 
                                               min="0" placeholder="0">
                                    </div>
                                    <div class="discount-type">
                                        <select id="discount_type" name="discount_type">
                                            <option value="percent" <?php selected($discount_type, 'percent'); ?>>% Phần trăm</option>
                                            <option value="fixed" <?php selected($discount_type, 'fixed'); ?>>VNĐ Cố định</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Thời hạn giảm giá -->
                                <div class="discount-expiry-wrap" id="discount-expiry-wrap">
                                    <label style="margin-bottom: 8px;">Thời hạn giảm giá:</label>
                                    <div class="discount-expiry-options">
                                        <label>
                                            <input type="radio" name="discount_has_expiry" value="" 
                                                   <?php checked($discount_has_expiry, ''); ?> <?php checked($discount_has_expiry, false); ?>>
                                            Vĩnh viễn
                                        </label>
                                        <label>
                                            <input type="radio" name="discount_has_expiry" value="1" 
                                                   <?php checked($discount_has_expiry, '1'); ?>>
                                            Có thời hạn
                                        </label>
                                    </div>
                                    <div class="discount-date-wrap <?php echo $discount_has_expiry === '1' ? 'show' : ''; ?>" id="discount-date-wrap">
                                        <label style="font-weight:normal; margin-bottom:5px;">Kết thúc lúc:</label>
                                        <div class="discount-datetime-row">
                                            <input type="date" id="discount_expiry_date" name="discount_expiry_date" 
                                                   value="<?php echo esc_attr($discount_expiry_date); ?>"
                                                   min="<?php echo date('Y-m-d'); ?>">
                                            <input type="time" id="discount_expiry_time" name="discount_expiry_time" 
                                                   value="<?php echo esc_attr($discount_expiry_time ?: '23:59'); ?>">
                                        </div>
                                        <p class="description">Giảm giá sẽ hết hiệu lực vào thời điểm này. Ví dụ: Flash Sale từ 12:00-14:00</p>
                                    </div>
                                </div>
                                
                                <!-- Nút xóa giảm giá -->
                                <button type="button" class="button btn-clear-discount" id="clear-discount-btn" style="display: none;">
                                    <span class="dashicons dashicons-dismiss"></span> Tắt giảm giá
                                </button>
                            </div>
                            
                            <div class="sale-price-display" id="sale-price-box" style="display: none;">
                                <div class="label">Giá bán cuối cùng:</div>
                                <span class="price" id="final-price">0 VNĐ</span>
                                <span class="original" id="original-price"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- KHO HÀNG -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-archive"></span>
                            Kho hàng
                        </div>
                        <div class="petshop-card-body">
                            <div class="petshop-form-group">
                                <label for="product_stock">Số lượng trong kho</label>
                                <input type="number" id="product_stock" name="product_stock" 
                                       value="<?php echo esc_attr($stock); ?>" 
                                       min="0" placeholder="Để trống = không giới hạn">
                                <div id="stock-status"></div>
                            </div>
                            <div class="petshop-form-group">
                                <label for="low_stock_threshold">Ngưỡng cảnh báo hết hàng</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" 
                                       value="<?php echo esc_attr($low_stock_threshold); ?>" 
                                       min="0" placeholder="5">
                                <small style="color:#666; display:block; margin-top:5px;">
                                    Khi tồn kho ≤ ngưỡng này sẽ hiển thị "Sắp hết hàng"
                                </small>
                            </div>
                            <div style="margin-top:15px; padding:10px; background:#f8f9fa; border-radius:6px; font-size:13px;">
                                <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-inventory'); ?>" style="text-decoration:none;">
                                    <span class="dashicons dashicons-chart-bar" style="font-size:16px; vertical-align:middle;"></span>
                                    Quản lý tồn kho hàng loạt →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- PHÂN LOẠI SẢN PHẨM -->
                    <div class="petshop-card" id="variants-card">
                        <div class="petshop-card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <span style="display:flex;align-items:center;gap:8px;">
                                <span class="dashicons dashicons-tag"></span>
                                Phân loại sản phẩm (Size / Màu sắc / Ảnh)
                            </span>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;font-size:13px;">
                                <input type="checkbox" id="enable_variants" name="enable_variants"
                                       <?php echo $has_variants ? 'checked' : ''; ?>
                                       onchange="toggleVariantsSection(this.checked)">
                                Bật phân loại cho sản phẩm này
                            </label>
                        </div>
                        <div class="petshop-card-body" id="variants-body" style="<?php echo $has_variants ? '' : 'display:none;'; ?>">
                            <p style="color:#666;font-size:13px;margin-bottom:16px;">
                                <span class="dashicons dashicons-info" style="color:#2196F3;vertical-align:middle;margin-right:4px;"></span>
                                Thêm Size và/hoặc Màu sắc. Mỗi tổ hợp có tồn kho và ảnh riêng.
                                Cột <strong>+/- Giá</strong>: nhập số dương/âm nếu phân loại đắt/rẻ hơn giá gốc (để 0 nếu không thay đổi).
                            </p>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                                <div>
                                    <label style="font-weight:600;font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                                        <span class="dashicons dashicons-editor-expand"></span> Danh sách Size
                                    </label>
                                    <div id="size-tags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;min-height:34px;padding:6px;border:1px solid #ddd;border-radius:6px;background:#fafafa;"></div>
                                    <div style="display:flex;gap:6px;">
                                        <input type="text" id="new-size-input" placeholder="VD: S, M, L, XL..." style="flex:1;padding:6px 10px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                                        <button type="button" class="button" onclick="addSizeTag()"><span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span></button>
                                    </div>
                                    <div style="margin-top:6px;display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
                                        <span style="font-size:11px;color:#999;">Gợi ý:</span>
                                        <?php foreach (['S','M','L','XL','XXL','28','30','32','34'] as $s): ?>
                                        <button type="button" onclick="addSizeTag('<?php echo $s; ?>')" style="padding:2px 8px;font-size:11px;border:1px solid #ddd;border-radius:10px;background:#f5f5f5;cursor:pointer;"><?php echo $s; ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight:600;font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                                        <span class="dashicons dashicons-art"></span> Danh sách Màu sắc
                                    </label>
                                    <div id="color-tags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;min-height:34px;padding:6px;border:1px solid #ddd;border-radius:6px;background:#fafafa;"></div>
                                    <div style="display:flex;gap:6px;align-items:center;">
                                        <input type="text" id="new-color-input" placeholder="VD: Đỏ, Xanh, Đen..." style="flex:1;padding:6px 10px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                                        <input type="color" id="new-color-hex" value="#EC802B" style="width:36px;height:32px;border:1px solid #ddd;border-radius:5px;cursor:pointer;padding:2px;">
                                        <button type="button" class="button" onclick="addColorTag()"><span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span></button>
                                    </div>
                                    <div style="margin-top:6px;display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
                                        <span style="font-size:11px;color:#999;">Gợi ý:</span>
                                        <?php
                                        $preset_colors=[['name'=>'Đỏ','hex'=>'#e53935'],['name'=>'Xanh lá','hex'=>'#43a047'],['name'=>'Xanh dương','hex'=>'#1e88e5'],['name'=>'Vàng','hex'=>'#fdd835'],['name'=>'Đen','hex'=>'#212121'],['name'=>'Trắng','hex'=>'#f5f5f5'],['name'=>'Hồng','hex'=>'#e91e63'],['name'=>'Cam','hex'=>'#fb8c00'],['name'=>'Nâu','hex'=>'#795548'],['name'=>'Xám','hex'=>'#9e9e9e']];
                                        foreach ($preset_colors as $pc): ?>
                                        <button type="button" onclick="addColorTag('<?php echo $pc['name']; ?>','<?php echo $pc['hex']; ?>')"
                                                style="padding:2px 8px;font-size:11px;border:1px solid #ddd;border-radius:10px;background:<?php echo $pc['hex']; ?>;color:<?php echo in_array($pc['name'],['Đen','Xanh dương','Xanh lá','Nâu'])?'#fff':'#333'; ?>;cursor:pointer;">
                                            <?php echo $pc['name']; ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;padding:12px;background:#fff3e0;border-radius:8px;border-left:4px solid #EC802B;">
                                <span class="dashicons dashicons-warning" style="color:#EC802B;"></span>
                                <span style="font-size:13px;color:#666;flex:1;">Sau khi thêm/xóa Size hoặc Màu, nhấn nút để cập nhật bảng.</span>
                                <button type="button" class="button button-primary" onclick="generateVariantTable()" style="background:#EC802B;border-color:#EC802B;display:flex;align-items:center;gap:5px;">
                                    <span class="dashicons dashicons-update" style="margin-top:3px;"></span> Cập nhật bảng phân loại
                                </button>
                            </div>
                            <div id="variants-table-wrap" style="overflow-x:auto;"></div>
                            <div style="margin-top:14px;padding:12px 16px;background:#e8f5e9;border-radius:8px;display:flex;align-items:center;gap:10px;">
                                <span class="dashicons dashicons-store" style="color:#43a047;font-size:20px;"></span>
                                <span style="font-size:14px;font-weight:600;color:#2e7d32;">
                                    Tổng tồn kho: <span id="total-variant-stock">0</span> sản phẩm
                                </span>
                                <span style="font-size:12px;color:#666;margin-left:auto;">(Tự động sync vào ô Số lượng kho bên trên)</span>
                            </div>
                        </div>
                    </div>

                </div>
                
                <!-- CỘT PHỤ -->
                <div class="side-column">
                    
                    <!-- HÌNH ẢNH (sidebar) -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-format-gallery"></span>
                            Hình ảnh sản phẩm
                        </div>
                        <div class="petshop-card-body">
                            <button type="button" class="gallery-upload-btn" id="upload-gallery-btn">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                Thêm hình ảnh
                            </button>
                            <p class="description">Click vào ảnh để đặt làm ảnh chính. Ảnh đầu tiên sẽ là ảnh đại diện.</p>
                            
                            <div class="gallery-preview" id="gallery-preview">
                                <?php 
                                if ($gallery_ids) {
                                    $ids = explode(',', $gallery_ids);
                                    foreach ($ids as $index => $id) {
                                        $img_url = wp_get_attachment_image_url($id, 'thumbnail');
                                        if ($img_url) {
                                            $is_primary = ($primary_image && $primary_image == $id) || ($index === 0 && !$primary_image);
                                            echo '<div class="gallery-item' . ($is_primary ? ' is-primary' : '') . '" data-id="' . esc_attr($id) . '">';
                                            echo '<img src="' . esc_url($img_url) . '" alt="">';
                                            echo '<button type="button" class="remove-btn">&times;</button>';
                                            echo '</div>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                            
                            <input type="hidden" name="product_gallery" id="product_gallery" value="<?php echo esc_attr($gallery_ids); ?>">
                            <input type="hidden" name="product_primary_image" id="product_primary_image" value="<?php echo esc_attr($primary_image); ?>">
                        </div>
                    </div>
                    
                    <!-- TRẠNG THÁI & LƯU -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-admin-generic"></span>
                            Xuất bản
                        </div>
                        <div class="petshop-card-body">
                            <div class="petshop-form-group">
                                <label for="product_status">Trạng thái</label>
                                <select id="product_status" name="product_status">
                                    <option value="publish" <?php selected($product_status, 'publish'); ?>>Công khai</option>
                                    <option value="draft" <?php selected($product_status, 'draft'); ?>>Bản nháp</option>
                                    <option value="private" <?php selected($product_status, 'private'); ?>>Riêng tư</option>
                                </select>
                            </div>
                        </div>
                        <div class="petshop-submit-wrap">
                            <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" class="button">Hủy</a>
                            <button type="submit" class="button button-primary button-primary-large">
                                <?php echo $is_edit ? 'Cập nhật' : 'Lưu sản phẩm'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- DANH MỤC -->
                    <div class="petshop-card">
                        <div class="petshop-card-header">
                            <span class="dashicons dashicons-category"></span>
                            Danh mục sản phẩm
                        </div>
                        <div class="petshop-card-body">
                            <?php 
                            // Lấy danh mục cha (parent = 0)
                            $parent_categories = get_terms(array(
                                'taxonomy' => 'product_category',
                                'hide_empty' => false,
                                'parent' => 0,
                            ));
                            
                            if (!empty($parent_categories) && !is_wp_error($parent_categories)): 
                            ?>
                            <div class="category-tree">
                                <?php foreach ($parent_categories as $parent_cat): 
                                    // Lấy danh mục con
                                    $child_categories = get_terms(array(
                                        'taxonomy' => 'product_category',
                                        'hide_empty' => false,
                                        'parent' => $parent_cat->term_id,
                                    ));
                                ?>
                                
                                <?php if (!empty($child_categories) && !is_wp_error($child_categories)): ?>
                                <!-- Danh mục cha có con -->
                                <div class="category-parent" data-parent-id="<?php echo $parent_cat->term_id; ?>">
                                    <div class="category-parent-header">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        <?php echo esc_html($parent_cat->name); ?>
                                        <span style="color: #646970; font-weight: normal; font-size: 12px;">(<?php echo count($child_categories); ?>)</span>
                                    </div>
                                    <div class="category-children">
                                        <?php foreach ($child_categories as $child_cat): ?>
                                        <div class="category-child-item">
                                            <input type="checkbox" name="product_categories[]" 
                                                   id="cat-<?php echo $child_cat->term_id; ?>"
                                                   value="<?php echo $child_cat->term_id; ?>"
                                                   class="child-category-checkbox"
                                                   data-parent="<?php echo $parent_cat->term_id; ?>"
                                                   <?php checked(in_array($child_cat->term_id, $selected_categories)); ?>>
                                            <label for="cat-<?php echo $child_cat->term_id; ?>"><?php echo esc_html($child_cat->name); ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Danh mục cha không có con - cho phép chọn trực tiếp -->
                                <div class="category-no-parent">
                                    <div class="category-item">
                                        <input type="checkbox" name="product_categories[]" 
                                               id="cat-<?php echo $parent_cat->term_id; ?>"
                                               value="<?php echo $parent_cat->term_id; ?>"
                                               <?php checked(in_array($parent_cat->term_id, $selected_categories)); ?>>
                                        <label for="cat-<?php echo $parent_cat->term_id; ?>"><?php echo esc_html($parent_cat->name); ?></label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php endforeach; ?>
                            </div>
                            <p class="description" style="margin-top: 8px;">Chọn danh mục phù hợp cho sản phẩm (có thể chọn nhiều)</p>
                            <?php else: ?>
                            <p class="description">
                                Chưa có danh mục nào. 
                                <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_category&post_type=product'); ?>">Tạo danh mục</a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // ===== Chuyển số thành chữ =====
        function numberToWords(num) {
            if (!num || num == 0) return '';
            
            var units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
            var teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
            
            function readThreeDigits(n) {
                var str = '';
                var hundred = Math.floor(n / 100);
                var remainder = n % 100;
                var ten = Math.floor(remainder / 10);
                var unit = remainder % 10;
                
                if (hundred > 0) {
                    str += units[hundred] + ' trăm ';
                    if (ten === 0 && unit > 0) str += 'lẻ ';
                }
                
                if (ten === 1) {
                    str += teens[unit] + ' ';
                } else if (ten > 1) {
                    str += units[ten] + ' mươi ';
                    if (unit === 1) str += 'mốt ';
                    else if (unit === 5) str += 'lăm ';
                    else if (unit > 0) str += units[unit] + ' ';
                } else if (unit > 0) {
                    str += units[unit] + ' ';
                }
                
                return str;
            }
            
            num = parseInt(num);
            if (num === 0) return 'không';
            
            var result = '';
            var billion = Math.floor(num / 1000000000);
            var million = Math.floor((num % 1000000000) / 1000000);
            var thousand = Math.floor((num % 1000000) / 1000);
            var rest = num % 1000;
            
            if (billion > 0) result += readThreeDigits(billion) + 'tỷ ';
            if (million > 0) result += readThreeDigits(million) + 'triệu ';
            if (thousand > 0) result += readThreeDigits(thousand) + 'nghìn ';
            if (rest > 0) result += readThreeDigits(rest);
            
            result = result.trim() + ' đồng';
            return result.charAt(0).toUpperCase() + result.slice(1);
        }
        
        // ===== Format number =====
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // ===== Tính giá sale =====
        function calculateSalePrice() {
            var price = parseFloat($('#product_price').val()) || 0;
            var discountValue = parseFloat($('#discount_value').val()) || 0;
            var discountType = $('#discount_type').val();
            
            // Hiển thị text cho giá gốc
            $('#price-text').text(numberToWords(price));
            
            if (price > 0 && discountValue > 0) {
                var salePrice;
                if (discountType === 'percent') {
                    if (discountValue > 100) discountValue = 100;
                    salePrice = price - (price * discountValue / 100);
                } else {
                    salePrice = price - discountValue;
                }
                salePrice = Math.max(0, Math.round(salePrice));
                
                $('#product_sale_price').val(salePrice);
                $('#product_sale_price_display').val(formatNumber(salePrice));
                $('#final-price').text(formatNumber(salePrice) + ' VNĐ');
                $('#original-price').text(formatNumber(price) + ' VNĐ');
                $('#sale-price-box').show();
            } else {
                $('#product_sale_price').val('');
                $('#product_sale_price_display').val('');
                $('#sale-price-box').hide();
            }
        }
        
        $('#product_price, #discount_value, #discount_type').on('input change', calculateSalePrice);
        calculateSalePrice();
        
        // ===== Discount expiry toggle =====
        function toggleDiscountExpiry() {
            var hasExpiry = $('input[name="discount_has_expiry"]:checked').val();
            if (hasExpiry === '1') {
                $('#discount-date-wrap').addClass('show');
            } else {
                $('#discount-date-wrap').removeClass('show');
            }
        }
        
        $('input[name="discount_has_expiry"]').on('change', toggleDiscountExpiry);
        toggleDiscountExpiry();
        
        // ===== Clear discount button =====
        $('#clear-discount-btn').on('click', function() {
            if (confirm('Bạn có chắc muốn tắt giảm giá? Tất cả dữ liệu giảm giá sẽ bị xóa.')) {
                $('#discount_value').val('');
                $('#discount_type').val('percent');
                $('input[name="discount_has_expiry"][value=""]').prop('checked', true);
                $('#discount_expiry_date').val('');
                toggleDiscountExpiry();
                calculateSalePrice();
            }
        });
        
        // ===== Generate SKU =====
        $('#generate-sku-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Đang tạo...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'petshop_generate_sku',
                    nonce: '<?php echo wp_create_nonce('petshop_generate_sku'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#product_sku').val(response.data.sku);
                    } else {
                        alert('Không thể tạo mã SKU. Vui lòng thử lại.');
                    }
                },
                error: function() {
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-randomize"></span> Tạo mã');
                }
            });
        });
        
        // ===== Origin custom toggle =====
        function toggleOriginCustom() {
            var origin = $('#product_origin').val();
            if (origin === 'other') {
                $('#origin-custom-wrap').addClass('show');
            } else {
                $('#origin-custom-wrap').removeClass('show');
            }
        }
        
        $('#product_origin').on('change', toggleOriginCustom);
        toggleOriginCustom();
        
        // ===== Category parent toggle =====
        $(document).on('click', '.category-parent-header', function() {
            $(this).closest('.category-parent').toggleClass('collapsed');
        });
        
        // ===== Stock status =====
        function updateStockStatus() {
            var stockVal = $('#product_stock').val();
            var $status = $('#stock-status');
            var threshold = parseInt($('#low_stock_threshold').val()) || 5;
            
            // Nếu để trống = không giới hạn
            if (stockVal === '' || stockVal === null) {
                $status.html('<span class="stock-status in-stock">Không giới hạn (còn hàng)</span>');
            } else {
                var stock = parseInt(stockVal);
                if (stock <= 0) {
                    $status.html('<span class="stock-status out-of-stock">Hết hàng</span>');
                } else if (stock <= threshold) {
                    $status.html('<span class="stock-status low-stock">Sắp hết (' + stock + ' sản phẩm)</span>');
                } else {
                    $status.html('<span class="stock-status in-stock">Còn hàng (' + stock + ' sản phẩm)</span>');
                }
            }
        }
        
        $('#product_stock, #low_stock_threshold').on('input', updateStockStatus);
        updateStockStatus();
        
        // ===== Gallery Upload =====
        var galleryFrame;
        
        $('#upload-gallery-btn').on('click', function(e) {
            e.preventDefault();
            
            if (galleryFrame) {
                galleryFrame.open();
                return;
            }
            
            galleryFrame = wp.media({
                title: 'Chọn hình ảnh sản phẩm',
                button: { text: 'Thêm hình ảnh' },
                multiple: true
            });
            
            galleryFrame.on('select', function() {
                var attachments = galleryFrame.state().get('selection').toJSON();
                attachments.forEach(function(att) {
                    addGalleryImage(att.id, att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
                });
                updateGalleryInput();
            });
            
            galleryFrame.open();
        });
        
        function addGalleryImage(id, url) {
            var isFirst = $('#gallery-preview .gallery-item').length === 0;
            var html = '<div class="gallery-item' + (isFirst ? ' is-primary' : '') + '" data-id="' + id + '">' +
                       '<img src="' + url + '" alt="">' +
                       '<button type="button" class="remove-btn">&times;</button>' +
                       '</div>';
            $('#gallery-preview').append(html);
        }
        
        // Remove image
        $(document).on('click', '.gallery-item .remove-btn', function(e) {
            e.stopPropagation();
            var $item = $(this).closest('.gallery-item');
            var wasPrimary = $item.hasClass('is-primary');
            $item.remove();
            if (wasPrimary) {
                $('#gallery-preview .gallery-item').first().addClass('is-primary');
            }
            updateGalleryInput();
        });
        
        // Set primary
        $(document).on('click', '.gallery-item', function() {
            $('#gallery-preview .gallery-item').removeClass('is-primary');
            $(this).addClass('is-primary');
            updateGalleryInput();
        });
        
        function updateGalleryInput() {
            var ids = [];
            $('#gallery-preview .gallery-item').each(function() {
                ids.push($(this).data('id'));
            });
            $('#product_gallery').val(ids.join(','));
            
            var primaryId = $('#gallery-preview .gallery-item.is-primary').data('id');
            $('#product_primary_image').val(primaryId || '');
        }
    });
    </script>

    <script>
    // ============================================================
    // VARIANTS MANAGER — Size / Màu / Ảnh / Tồn kho
    // ============================================================
    const existingVariants = <?php echo json_encode($existing_variants ?: []); ?>;
    let sizeList=[], colorList=[], variantMediaFrames={};

    (function init(){
        if(!existingVariants.length) return;
        existingVariants.forEach(v=>{
            if(v.size&&!sizeList.includes(v.size)) sizeList.push(v.size);
            if(v.color&&!colorList.find(c=>c.name===v.color)) colorList.push({name:v.color,hex:v.color_hex||'#EC802B'});
        });
        renderSizeTags(); renderColorTags(); generateVariantTable();
    })();

    function toggleVariantsSection(on){document.getElementById('variants-body').style.display=on?'':'none';}

    // SIZE
    function addSizeTag(val){
        const inp=document.getElementById('new-size-input');
        const v=(val||inp.value).trim().toUpperCase();
        if(!v||sizeList.includes(v)){inp.value='';return;}
        sizeList.push(v); renderSizeTags(); inp.value='';
    }
    function removeSize(s){sizeList=sizeList.filter(x=>x!==s);renderSizeTags();}
    function renderSizeTags(){
        document.getElementById('size-tags').innerHTML=sizeList.map(s=>`
        <span style="display:inline-flex;align-items:center;gap:4px;background:#EC802B22;color:#5D4E37;padding:4px 10px;border-radius:20px;font-size:13px;font-weight:600;">
            ${s}<button type="button" onclick="removeSize('${s}')" style="background:none;border:none;cursor:pointer;padding:0;"><span class="dashicons dashicons-no-alt" style="font-size:13px;width:13px;height:13px;color:#d9534f;"></span></button>
        </span>`).join('');
    }
    document.getElementById('new-size-input')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();addSizeTag();}});

    // COLOR
    function addColorTag(nv,hv){
        const ni=document.getElementById('new-color-input'),hi=document.getElementById('new-color-hex');
        const n=(nv||ni.value).trim(),h=hv||hi.value||'#EC802B';
        if(!n||colorList.find(c=>c.name===n)){ni.value='';return;}
        colorList.push({name:n,hex:h}); renderColorTags(); ni.value='';
    }
    function removeColor(n){colorList=colorList.filter(c=>c.name!==n);renderColorTags();}
    function renderColorTags(){
        document.getElementById('color-tags').innerHTML=colorList.map(c=>{
            const dk=isDark(c.hex);
            return `<span style="display:inline-flex;align-items:center;gap:5px;background:${c.hex};color:${dk?'#fff':'#333'};padding:4px 10px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid rgba(0,0,0,0.1);">
                <span style="width:10px;height:10px;background:${c.hex};border-radius:50%;border:2px solid rgba(0,0,0,0.2);display:inline-block;"></span>
                ${c.name}<button type="button" onclick="removeColor('${c.name}')" style="background:none;border:none;cursor:pointer;padding:0;"><span class="dashicons dashicons-no-alt" style="font-size:13px;width:13px;height:13px;color:${dk?'#fff':'#333'};"></span></button>
            </span>`;
        }).join('');
    }
    function isDark(h){if(!h||h.length<4)return false;const r=parseInt(h.slice(1,3),16),g=parseInt(h.slice(3,5),16),b=parseInt(h.slice(5,7),16);return(0.299*r+0.587*g+0.114*b)<140;}
    document.getElementById('new-color-input')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();addColorTag();}});

    // GENERATE TABLE
    function generateVariantTable(){
        const wrap=document.getElementById('variants-table-wrap');
        const hs=sizeList.length>0,hc=colorList.length>0;
        if(!hs&&!hc){wrap.innerHTML='<p style="color:#999;text-align:center;padding:20px;"><span class="dashicons dashicons-info-outline"></span> Thêm Size hoặc Màu ở trên trước.</p>';updateTotalStock();return;}
        let rows=[];
        if(hs&&hc) sizeList.forEach(s=>colorList.forEach(c=>rows.push({size:s,color:c.name,hex:c.hex})));
        else if(hs) sizeList.forEach(s=>rows.push({size:s,color:'',hex:''}));
        else colorList.forEach(c=>rows.push({size:'',color:c.name,hex:c.hex}));

        const findEx=(sz,cl)=>existingVariants.find(v=>(v.size||'')===sz&&(v.color||'')===cl);

        let html=`<table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead><tr style="background:#f8f8f8;">
            ${hs?`<th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;"><span class="dashicons dashicons-editor-expand" style="font-size:13px;vertical-align:middle;margin-right:4px;"></span>Size</th>`:''}
            ${hc?`<th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;"><span class="dashicons dashicons-art" style="font-size:13px;vertical-align:middle;margin-right:4px;"></span>Màu sắc</th>`:''}
            <th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;"><span class="dashicons dashicons-camera" style="font-size:13px;vertical-align:middle;margin-right:4px;"></span>Ảnh phân loại</th>
            <th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;"><span class="dashicons dashicons-archive" style="font-size:13px;vertical-align:middle;margin-right:4px;"></span>Tồn kho</th>
            <th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;">SKU</th>
            <th style="padding:9px 12px;text-align:left;border:1px solid #e0e0e0;" title="Giá bán thực tế của phân loại này. Để trống = dùng giá gốc sản phẩm."><span class="dashicons dashicons-tag" style="font-size:13px;vertical-align:middle;margin-right:4px;"></span>Giá bán (đ) <span style="color:#999;font-size:11px;">tùy chọn</span></th>
        </tr></thead><tbody>`;

        rows.forEach((row,i)=>{
            const ex=findEx(row.size,row.color);
            const stk=ex?ex.stock:0,sku=ex?(ex.sku||''):'',vp=ex?(ex.variant_price||''):'',imgId=ex?(parseInt(ex.image_id)||0):0;
            const dot=row.hex?`<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${row.hex};border:1px solid rgba(0,0,0,0.15);margin-right:5px;vertical-align:middle;"></span>`:'';
            html+=`<tr style="background:${i%2?'#fafafa':'#fff'};">
            ${hs?`<td style="padding:8px 12px;border:1px solid #e0e0e0;font-weight:600;color:#EC802B;">${row.size||'-'}</td>`:''}
            ${hc?`<td style="padding:8px 12px;border:1px solid #e0e0e0;">${dot}${row.color||'-'}</td>`:''}
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div id="vimg-preview-${i}" style="width:48px;height:48px;border-radius:6px;overflow:hidden;border:2px solid #ddd;background:#f5f5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="dashicons dashicons-format-image" style="color:#ccc;font-size:22px;width:22px;height:22px;"></span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <button type="button" class="button" id="vimg-btn-${i}" onclick="pickVImg(${i})" style="font-size:11px;padding:3px 8px;display:flex;align-items:center;gap:4px;">
                            <span class="dashicons dashicons-upload" style="font-size:12px;width:12px;height:12px;margin-top:2px;"></span>
                            ${imgId?'Đổi ảnh':'Chọn ảnh'}
                        </button>
                        <button type="button" id="vimg-rm-${i}" class="button" onclick="rmVImg(${i})"
                                style="font-size:11px;padding:3px 8px;align-items:center;gap:4px;color:#d9534f;border-color:#d9534f;display:${imgId?'flex':'none'};">
                            <span class="dashicons dashicons-trash" style="font-size:12px;width:12px;height:12px;margin-top:2px;"></span> Xóa
                        </button>
                    </div>
                    <input type="hidden" name="variants[${i}][image_id]" id="vimg-id-${i}" value="${imgId}">
                </div>
            </td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">
                <input type="number" name="variants[${i}][stock]" value="${stk}" min="0" onchange="updateTotalStock()"
                       style="width:75px;padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                <input type="hidden" name="variants[${i}][size]"      value="${row.size}">
                <input type="hidden" name="variants[${i}][color]"     value="${row.color}">
                <input type="hidden" name="variants[${i}][color_hex]" value="${row.hex}">
            </td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">
                <input type="text" name="variants[${i}][sku]" value="${sku}" placeholder="Tự tạo nếu để trống"
                       style="width:110px;padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
            </td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">
                <input type="number" name="variants[${i}][variant_price]" value="${vp||''}" min="0" placeholder="Để trống = giá gốc"
                       style="width:120px;padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
            </td></tr>`;
        });
        html+='</tbody></table>';
        wrap.innerHTML=html; updateTotalStock();
        rows.forEach((_,i)=>{const ex=findEx(rows[i].size,rows[i].color);if(ex&&ex.image_id)loadVImg(i,parseInt(ex.image_id));});
    }

    function pickVImg(i){
        if(typeof wp==='undefined'||!wp.media){alert('Media Library chưa sẵn sàng.');return;}
        if(!variantMediaFrames[i]){
            variantMediaFrames[i]=wp.media({title:'Chọn ảnh phân loại',button:{text:'Chọn ảnh này'},multiple:false,library:{type:'image'}});
            variantMediaFrames[i].on('select',function(){
                const a=variantMediaFrames[i].state().get('selection').first().toJSON();
                setVImg(i,a.id,a.sizes?.thumbnail?.url||a.url);
            });
        }
        variantMediaFrames[i].open();
    }
    function setVImg(i,id,url){
        const p=document.getElementById('vimg-preview-'+i),h=document.getElementById('vimg-id-'+i),r=document.getElementById('vimg-rm-'+i),b=document.getElementById('vimg-btn-'+i);
        if(!p||!h)return;
        p.innerHTML=`<img src="${url}" style="width:100%;height:100%;object-fit:cover;">`;
        h.value=id; if(r)r.style.display='flex';
        if(b)b.innerHTML='<span class="dashicons dashicons-upload" style="font-size:12px;width:12px;height:12px;margin-top:2px;"></span> Đổi ảnh';
    }
    function rmVImg(i){
        const p=document.getElementById('vimg-preview-'+i),h=document.getElementById('vimg-id-'+i),r=document.getElementById('vimg-rm-'+i);
        if(!p||!h)return;
        p.innerHTML='<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:22px;width:22px;height:22px;"></span>';
        h.value=''; if(r)r.style.display='none'; delete variantMediaFrames[i];
    }
    async function loadVImg(i,id){
        if(!id)return;
        try{
            const r=await fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=petshop_get_attachment_thumb&id=${id}`,{credentials:'same-origin'});
            const d=await r.json();
            if(d.success&&d.data.url)setVImg(i,id,d.data.url);
        }catch(e){}
    }
    function updateTotalStock(){
        let t=0;
        document.querySelectorAll('[name^="variants["][name$="[stock]"]').forEach(inp=>{t+=parseInt(inp.value)||0;});
        const el=document.getElementById('total-variant-stock');
        if(el)el.textContent=t.toLocaleString('vi-VN');
        // Sync vào product_stock để người dùng thấy tổng
        const si=document.getElementById('product_stock');
        if(si&&document.getElementById('enable_variants')?.checked)si.value=t;
    }
    </script>

    <?php
    // AJAX: thumbnail preview cho variant image
    add_action('wp_ajax_petshop_get_attachment_thumb', function() {
        $id=intval($_GET['id']??0); if(!$id) wp_send_json_error();
        $url=wp_get_attachment_image_url($id,'thumbnail'); if(!$url) wp_send_json_error();
        wp_send_json_success(array('url'=>$url));
    });
    ?>
    <?php
}

// =============================================
// HELPER FUNCTIONS
// =============================================
function petshop_is_discount_active($post_id) {
    // Sử dụng hàm kiểm tra đầy đủ bao gồm cả thời hạn
    return petshop_is_discount_valid($post_id);
}

function petshop_get_display_price($post_id) {
    $price = get_post_meta($post_id, 'product_price', true);
    $sale_price = get_post_meta($post_id, 'product_sale_price', true);
    
    if (petshop_is_discount_active($post_id) && !empty($sale_price)) {
        $discount_type = get_post_meta($post_id, 'discount_type', true);
        $discount_value = get_post_meta($post_id, 'discount_value', true);
        
        // Tính phần trăm giảm giá để hiển thị
        $discount_percent = 0;
        if ($discount_type === 'percent') {
            $discount_percent = floatval($discount_value);
        } elseif (floatval($price) > 0) {
            // Nếu là giảm cố định, tính ra phần trăm
            $discount_percent = round((floatval($price) - floatval($sale_price)) / floatval($price) * 100);
        }
        
        return array(
            'original' => $price,
            'sale' => $sale_price,
            'is_on_sale' => true,
            'discount_value' => $discount_value,
            'discount_type' => $discount_type,
            'discount_percent' => $discount_percent
        );
    }
    
    return array(
        'original' => $price,
        'sale' => null,
        'is_on_sale' => false,
        'discount_value' => 0,
        'discount_type' => '',
        'discount_percent' => 0
    );
}

function petshop_get_product_gallery($post_id, $size = 'medium') {
    $gallery = get_post_meta($post_id, 'product_gallery', true);
    $images = array();
    
    if (!empty($gallery)) {
        $ids = explode(',', $gallery);
        foreach ($ids as $id) {
            $url = wp_get_attachment_image_url($id, $size);
            $full = wp_get_attachment_image_url($id, 'full');
            if ($url) {
                $images[] = array(
                    'id' => $id,
                    'url' => $url,
                    'full' => $full
                );
            }
        }
    }
    
    return $images;
}

// =============================================
// THÊM LINK EDIT VÀO DANH SÁCH SẢN PHẨM
// =============================================
function petshop_product_row_actions($actions, $post) {
    if ($post->post_type === 'product') {
        $edit_link = admin_url('edit.php?post_type=product&page=petshop-add-product&product_id=' . $post->ID);
        $actions['edit'] = '<a href="' . esc_url($edit_link) . '">Sửa</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'petshop_product_row_actions', 10, 2);

// Redirect edit link
function petshop_redirect_edit_product() {
    global $pagenow, $typenow;
    if ($pagenow === 'post.php' && $typenow === 'product' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if ($post_id > 0) {
            wp_redirect(admin_url('edit.php?post_type=product&page=petshop-add-product&product_id=' . $post_id));
            exit;
        }
    }
}
add_action('admin_init', 'petshop_redirect_edit_product');

// =============================================
// AJAX: TẠO MÃ SKU TỰ ĐỘNG
// =============================================
function petshop_generate_sku_ajax() {
    check_ajax_referer('petshop_generate_sku', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Không có quyền'));
    }
    
    // Lấy tất cả SKU đã tồn tại
    global $wpdb;
    $existing_skus = $wpdb->get_col(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'product_sku' AND meta_value != ''"
    );
    
    // Tạo SKU mới không trùng
    $prefix = 'PET';
    $max_attempts = 100;
    $attempt = 0;
    
    do {
        // Tạo mã ngẫu nhiên: PET-XXXX (4 ký tự số + chữ)
        $random_part = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $new_sku = $prefix . '-' . $random_part;
        $attempt++;
    } while (in_array($new_sku, $existing_skus) && $attempt < $max_attempts);
    
    if ($attempt >= $max_attempts) {
        // Fallback: thêm timestamp
        $new_sku = $prefix . '-' . strtoupper(substr(md5(time()), 0, 8));
    }
    
    wp_send_json_success(array('sku' => $new_sku));
}
add_action('wp_ajax_petshop_generate_sku', 'petshop_generate_sku_ajax');

// =============================================
// HELPER: KIỂM TRA GIẢM GIÁ CÒN HIỆU LỰC
// =============================================
function petshop_is_discount_valid($post_id) {
    $discount_value = get_post_meta($post_id, 'discount_value', true);
    if (empty($discount_value) || floatval($discount_value) <= 0) {
        return false;
    }
    
    $has_expiry = get_post_meta($post_id, 'discount_has_expiry', true);
    if ($has_expiry === '1') {
        $expiry_date = get_post_meta($post_id, 'discount_expiry_date', true);
        $expiry_time = get_post_meta($post_id, 'discount_expiry_time', true);
        if (!empty($expiry_date)) {
            // Sử dụng giờ/phút nếu có, mặc định 23:59
            $time_str = !empty($expiry_time) ? $expiry_time : '23:59';
            $expiry_timestamp = strtotime($expiry_date . ' ' . $time_str . ':00');
            if ($expiry_timestamp < current_time('timestamp')) {
                return false; // Đã hết hạn
            }
        }
    }
    
    return true;
}

// Helper: Lấy timestamp kết thúc giảm giá
function petshop_get_discount_expiry_timestamp($post_id) {
    $has_expiry = get_post_meta($post_id, 'discount_has_expiry', true);
    if ($has_expiry !== '1') {
        return null;
    }
    
    $expiry_date = get_post_meta($post_id, 'discount_expiry_date', true);
    $expiry_time = get_post_meta($post_id, 'discount_expiry_time', true);
    
    if (empty($expiry_date)) {
        return null;
    }
    
    $time_str = !empty($expiry_time) ? $expiry_time : '23:59';
    return strtotime($expiry_date . ' ' . $time_str . ':00');
}

// =============================================
// HELPER: LẤY XUẤT XỨ HIỂN THỊ
// =============================================
function petshop_get_origin_display($post_id) {
    $origin = get_post_meta($post_id, 'product_origin', true);
    
    if ($origin === 'other') {
        $custom = get_post_meta($post_id, 'product_origin_custom', true);
        return !empty($custom) ? $custom : 'Khác';
    }
    
    $origins = array(
        'vietnam' => 'Việt Nam',
        'usa' => 'Mỹ',
        'france' => 'Pháp',
        'germany' => 'Đức',
        'japan' => 'Nhật Bản',
        'korea' => 'Hàn Quốc',
        'thailand' => 'Thái Lan',
        'china' => 'Trung Quốc',
        'australia' => 'Úc',
        'uk' => 'Anh',
    );
    
    return isset($origins[$origin]) ? $origins[$origin] : $origin;
}