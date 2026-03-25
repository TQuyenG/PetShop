<?php
/**
 * PetShop Inventory Management System
 * Hệ thống quản lý tồn kho chuyên nghiệp
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// VARIANT HELPERS (inline)
// =============================================
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
if (!function_exists('petshop_sync_variant_stock')) {
    function petshop_sync_variant_stock($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_variants';
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(stock),0) FROM {$table} WHERE product_id = %d", $product_id));
        update_post_meta($product_id, 'product_stock', $total);
        return $total;
    }
}

// =============================================
// AJAX: KIỂM TRA TỒN KHO REALTIME
// =============================================
function petshop_check_cart_stock() {
    $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
    
    if (empty($product_ids)) {
        wp_send_json_success(array('products' => array()));
    }
    
    $result = array();
    foreach ($product_ids as $product_id) {
        $stock = get_post_meta($product_id, 'product_stock', true);
        $product = get_post($product_id);
        
        // Xác định trạng thái stock
        // Để trống = không giới hạn (-1)
        // 0 = hết hàng
        // > 0 = còn hàng với số lượng cụ thể
        if ($stock === '' || $stock === false || $stock === null) {
            $stock_qty = -1; // -1 = không giới hạn
            $in_stock = true;
        } else {
            $stock_qty = intval($stock);
            $in_stock = $stock_qty > 0;
        }
        
        // Lấy thông tin giá (có thể đã thay đổi)
        $price = get_post_meta($product_id, 'product_price', true);
        $sale_price = get_post_meta($product_id, 'product_sale_price', true);
        $current_price = ($sale_price && floatval($sale_price) < floatval($price)) ? floatval($sale_price) : floatval($price);
        
        // Lấy danh mục
        $categories = get_the_terms($product_id, 'product_category');
        $category = (!empty($categories) && !is_wp_error($categories)) ? $categories[0] : null;
        
        $result[$product_id] = array(
            'id' => $product_id,
            'name' => $product ? $product->post_title : '',
            'stock' => $stock_qty,
            'in_stock' => $in_stock,
            'price' => $current_price,
            'original_price' => floatval($price),
            'image' => get_the_post_thumbnail_url($product_id, 'thumbnail') ?: get_post_meta($product_id, 'product_primary_image', true),
            'url' => get_permalink($product_id),
            'category_name' => $category ? $category->name : '',
            'category_url' => $category ? get_term_link($category) : '',
        );
    }
    
    wp_send_json_success(array('products' => $result));
}
add_action('wp_ajax_petshop_check_cart_stock', 'petshop_check_cart_stock');
add_action('wp_ajax_nopriv_petshop_check_cart_stock', 'petshop_check_cart_stock');

// =============================================
// ĐĂNG KÝ MENU ADMIN
// =============================================
function petshop_register_inventory_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Quản lý tồn kho',
        'Quản lý tồn kho',
        'edit_posts',
        'petshop-inventory',
        'petshop_inventory_page'
    );
    
    add_submenu_page(
        'edit.php?post_type=product',
        'Lịch sử kho',
        'Lịch sử kho',
        'edit_posts',
        'petshop-stock-history',
        'petshop_stock_history_page'
    );
}
add_action('admin_menu', 'petshop_register_inventory_menu');

// =============================================
// THÊM COLUMNS VÀO DANH SÁCH SẢN PHẨM
// =============================================
function petshop_add_product_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['product_sku'] = 'SKU';
            $new_columns['product_price'] = 'Giá';
            $new_columns['product_stock'] = 'Tồn kho';
            $new_columns['stock_status'] = 'Trạng thái';
            $new_columns['product_sold'] = 'Đã bán';
        }
    }
    return $new_columns;
}
add_filter('manage_product_posts_columns', 'petshop_add_product_columns');

function petshop_product_column_content($column, $post_id) {
    switch ($column) {
        case 'product_sku':
            $sku = get_post_meta($post_id, 'product_sku', true);
            echo $sku ? esc_html($sku) : '<span style="color:#999;">—</span>';
            break;
            
        case 'product_price':
            $price = get_post_meta($post_id, 'product_price', true);
            $sale_price = get_post_meta($post_id, 'product_sale_price', true);
            if ($sale_price && $sale_price < $price) {
                echo '<del style="color:#999;">' . number_format($price) . 'đ</del><br>';
                echo '<strong style="color:#e53935;">' . number_format($sale_price) . 'đ</strong>';
            } else {
                echo $price ? number_format($price) . 'đ' : '<span style="color:#999;">—</span>';
            }
            break;
            
        case 'product_stock':
            $stock = get_post_meta($post_id, 'product_stock', true);
            $low_threshold = get_post_meta($post_id, 'low_stock_threshold', true) ?: 5;
            
            // Để trống = không giới hạn (còn hàng)
            if ($stock === '' || $stock === false || $stock === null) {
                echo '<span style="color:#4caf50; font-weight:600;">∞</span>';
            } else {
                $stock = intval($stock);
                if ($stock <= 0) {
                    echo '<span style="color:#e53935; font-weight:600;">0</span>';
                } elseif ($stock <= $low_threshold) {
                    echo '<span style="color:#ff9800; font-weight:600;">' . $stock . '</span>';
                } else {
                    echo '<span style="color:#4caf50; font-weight:600;">' . $stock . '</span>';
                }
            }
            break;
            
        case 'stock_status':
            $stock = get_post_meta($post_id, 'product_stock', true);
            $low_threshold = get_post_meta($post_id, 'low_stock_threshold', true) ?: 5;
            
            // Để trống = không giới hạn (còn hàng)
            if ($stock === '' || $stock === false || $stock === null) {
                $status = 'instock'; // Không giới hạn = còn hàng
            } else {
                $stock = intval($stock);
                if ($stock <= 0) {
                    $status = 'outofstock';
                } elseif ($stock <= $low_threshold) {
                    $status = 'lowstock';
                } else {
                    $status = 'instock';
                }
            }
            
            $status_labels = array(
                'instock' => '<span style="background:#e8f5e9; color:#2e7d32; padding:3px 8px; border-radius:3px; font-size:12px;">Còn hàng</span>',
                'lowstock' => '<span style="background:#fff3e0; color:#ef6c00; padding:3px 8px; border-radius:3px; font-size:12px;">Sắp hết</span>',
                'outofstock' => '<span style="background:#ffebee; color:#c62828; padding:3px 8px; border-radius:3px; font-size:12px;">Hết hàng</span>',
            );
            echo $status_labels[$status] ?? $status_labels['instock'];
            break;
            
        case 'product_sold':
            $sold = get_post_meta($post_id, 'product_sold_count', true);
            echo $sold ? intval($sold) : '0';
            break;
    }
}
add_action('manage_product_posts_custom_column', 'petshop_product_column_content', 10, 2);

// Make columns sortable
function petshop_sortable_columns($columns) {
    $columns['product_stock'] = 'product_stock';
    $columns['product_sold'] = 'product_sold';
    $columns['product_price'] = 'product_price';
    return $columns;
}
add_filter('manage_edit-product_sortable_columns', 'petshop_sortable_columns');

function petshop_sort_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    
    $orderby = $query->get('orderby');
    if ($orderby === 'product_stock') {
        $query->set('meta_key', 'product_stock');
        $query->set('orderby', 'meta_value_num');
    } elseif ($orderby === 'product_sold') {
        $query->set('meta_key', 'product_sold_count');
        $query->set('orderby', 'meta_value_num');
    } elseif ($orderby === 'product_price') {
        $query->set('meta_key', 'product_price');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'petshop_sort_columns_orderby');

// =============================================
// TRANG QUẢN LÝ TỒN KHO
// =============================================
function petshop_inventory_page() {
    // Handle bulk update
    if (isset($_POST['petshop_inventory_nonce']) && 
        wp_verify_nonce($_POST['petshop_inventory_nonce'], 'petshop_update_inventory')) {
        petshop_process_inventory_update();
    }
    
    // Get filter params
    $stock_filter = isset($_GET['stock_filter']) ? sanitize_text_field($_GET['stock_filter']) : '';
    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    
    // Build query
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    if ($search) {
        $args['s'] = $search;
    }
    
    if ($category_filter) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_category',
                'field' => 'term_id',
                'terms' => $category_filter,
            )
        );
    }
    
    if ($stock_filter) {
        switch ($stock_filter) {
            case 'outofstock':
                $args['meta_query'] = array(
                    array(
                        'key' => 'product_stock',
                        'value' => 0,
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    )
                );
                break;
            case 'lowstock':
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_stock',
                        'value' => 0,
                        'compare' => '>',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => 'product_stock',
                        'value' => 10,
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    )
                );
                break;
            case 'instock':
                $args['meta_query'] = array(
                    array(
                        'key' => 'product_stock',
                        'value' => 10,
                        'compare' => '>',
                        'type' => 'NUMERIC'
                    )
                );
                break;
        }
    }
    
    $products = new WP_Query($args);
    $categories = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false));
    
    // Count stats
    $total_products = wp_count_posts('product')->publish;
    $out_of_stock = new WP_Query(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'product_stock', 'value' => 0, 'compare' => '<=', 'type' => 'NUMERIC')
        ),
        'fields' => 'ids'
    ));
    $low_stock = new WP_Query(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array('key' => 'product_stock', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'),
            array('key' => 'product_stock', 'value' => 10, 'compare' => '<=', 'type' => 'NUMERIC')
        ),
        'fields' => 'ids'
    ));
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <i class="bi bi-box-seam" style="font-size: 28px; margin-right: 10px;"></i>
            Quản lý Tồn kho
        </h1>
        
        <style>
            .inventory-stats {
                display: flex;
                gap: 15px;
                margin: 20px 0;
            }
            .stat-box {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 15px 25px;
                min-width: 150px;
                text-align: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            .stat-box .number {
                font-size: 28px;
                font-weight: 700;
                display: block;
            }
            .stat-box .label {
                color: #666;
                font-size: 13px;
            }
            .stat-box.total .number { color: #2271b1; }
            .stat-box.outofstock .number { color: #d63638; }
            .stat-box.lowstock .number { color: #dba617; }
            .stat-box.instock .number { color: #00a32a; }
            
            .inventory-filters {
                background: #fff;
                padding: 15px 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                gap: 15px;
                align-items: center;
                flex-wrap: wrap;
            }
            .inventory-filters select,
            .inventory-filters input[type="text"] {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .inventory-table {
                background: #fff;
                border-collapse: collapse;
                width: 100%;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
            }
            .inventory-table th {
                background: #f8f9fa;
                padding: 12px 15px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #ddd;
            }
            .inventory-table td {
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                vertical-align: middle;
            }
            .inventory-table tr:hover td {
                background: #f8f9fa;
            }
            .inventory-table .product-thumb {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 4px;
            }
            .inventory-table .product-title {
                font-weight: 500;
            }
            .inventory-table .product-sku {
                color: #666;
                font-size: 12px;
            }
            .inventory-table input[type="number"] {
                width: 80px;
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: center;
            }
            .inventory-table input[type="number"]:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 2px rgba(34,113,177,0.2);
            }
            
            .stock-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .stock-badge.instock { background: #e8f5e9; color: #2e7d32; }
            .stock-badge.lowstock { background: #fff3e0; color: #ef6c00; }
            .stock-badge.outofstock { background: #ffebee; color: #c62828; }
            
            .quick-adjust {
                display: flex;
                gap: 5px;
                align-items: center;
            }
            .quick-adjust button {
                width: 28px;
                height: 28px;
                border: 1px solid #ddd;
                background: #f8f9fa;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
            }
            .quick-adjust button:hover {
                background: #e9ecef;
            }
            
            .inventory-actions {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .btn-update-inventory {
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 10px 25px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
            }
            .btn-update-inventory:hover {
                background: #135e96;
            }
            
            .pagination-wrap {
                margin-top: 20px;
                display: flex;
                justify-content: center;
            }
        </style>
        
        <!-- Stats -->
        <div class="inventory-stats">
            <div class="stat-box total">
                <span class="number"><?php echo $total_products; ?></span>
                <span class="label">Tổng sản phẩm</span>
            </div>
            <div class="stat-box outofstock">
                <span class="number"><?php echo $out_of_stock->found_posts; ?></span>
                <span class="label">Hết hàng</span>
            </div>
            <div class="stat-box lowstock">
                <span class="number"><?php echo $low_stock->found_posts; ?></span>
                <span class="label">Sắp hết (≤10)</span>
            </div>
            <div class="stat-box instock">
                <span class="number"><?php echo $total_products - $out_of_stock->found_posts - $low_stock->found_posts; ?></span>
                <span class="label">Còn hàng</span>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="get" class="inventory-filters">
            <input type="hidden" name="post_type" value="product">
            <input type="hidden" name="page" value="petshop-inventory">
            
            <select name="stock_filter">
                <option value="">-- Trạng thái kho --</option>
                <option value="outofstock" <?php selected($stock_filter, 'outofstock'); ?>>Hết hàng</option>
                <option value="lowstock" <?php selected($stock_filter, 'lowstock'); ?>>Sắp hết (≤10)</option>
                <option value="instock" <?php selected($stock_filter, 'instock'); ?>>Còn hàng (>10)</option>
            </select>
            
            <select name="category">
                <option value="">-- Danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>" <?php selected($category_filter, $cat->term_id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Tìm sản phẩm...">
            
            <button type="submit" class="button">Lọc</button>
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-inventory'); ?>" class="button">Reset</a>
        </form>
        
        <!-- Inventory Table -->
        <form method="post" id="inventory-form">
            <?php wp_nonce_field('petshop_update_inventory', 'petshop_inventory_nonce'); ?>
            
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th style="width:50px;"><input type="checkbox" id="select-all"></th>
                        <th style="width:60px;">Ảnh</th>
                        <th>Sản phẩm</th>
                        <th style="width:100px;">SKU</th>
                        <th style="width:120px;">Giá</th>
                        <th style="width:150px;">Tồn kho</th>
                        <th style="width:100px;">Đã bán</th>
                        <th style="width:100px;">Trạng thái</th>
                        <th style="width:120px;">Nhập thêm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->have_posts()): ?>
                        <?php while ($products->have_posts()): $products->the_post();
                            $product_id = get_the_ID();
                            // Lấy ảnh: ưu tiên product_primary_image, sau đó là post thumbnail
                            $thumb = get_post_meta($product_id, 'product_primary_image', true);
                            if (empty($thumb) && has_post_thumbnail($product_id)) {
                                $thumb = get_the_post_thumbnail_url($product_id, 'thumbnail');
                            }
                            $sku = get_post_meta($product_id, 'product_sku', true);
                            $price = get_post_meta($product_id, 'product_price', true);
                            $sale_price = get_post_meta($product_id, 'product_sale_price', true);
                            $stock = get_post_meta($product_id, 'product_stock', true);
                            $sold = get_post_meta($product_id, 'product_sold_count', true) ?: 0;
                            $low_threshold = get_post_meta($product_id, 'low_stock_threshold', true) ?: 5;
                            
                            // Link edit - sử dụng trang custom
                            $edit_link = admin_url('edit.php?post_type=product&page=petshop-add-product&product_id=' . $product_id);
                            
                            // Determine status
                            $stock_int = intval($stock);
                            if ($stock === '' || $stock === false) {
                                $status = 'instock';
                                $stock_display = '∞';
                            } elseif ($stock_int <= 0) {
                                $status = 'outofstock';
                                $stock_display = $stock_int;
                            } elseif ($stock_int <= $low_threshold) {
                                $status = 'lowstock';
                                $stock_display = $stock_int;
                            } else {
                                $status = 'instock';
                                $stock_display = $stock_int;
                            }
                        ?>
                        <tr data-product-id="<?php echo $product_id; ?>">
                            <td><input type="checkbox" name="product_ids[]" value="<?php echo $product_id; ?>"></td>
                            <td>
                                <?php if ($thumb): ?>
                                    <img src="<?php echo esc_url($thumb); ?>" class="product-thumb" alt="">
                                <?php else: ?>
                                    <div style="width:40px;height:40px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                                        <i class="bi bi-image" style="color:#ccc;font-size:22px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-title"><?php the_title(); ?></div>
                                <a href="<?php echo esc_url($edit_link); ?>" class="product-sku" style="color:#2271b1;">Sửa</a>
                            </td>
                            <td><?php echo $sku ? esc_html($sku) : '—'; ?></td>
                            <td>
                                <?php if ($sale_price && $sale_price < $price): ?>
                                    <del style="color:#999;"><?php echo number_format($price); ?>đ</del><br>
                                    <strong style="color:#d63638;"><?php echo number_format($sale_price); ?>đ</strong>
                                <?php else: ?>
                                    <?php echo $price ? number_format($price) . 'đ' : '—'; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="quick-adjust">
                                    <button type="button" onclick="adjustStock(<?php echo $product_id; ?>, -1)">−</button>
                                    <input type="number" 
                                           name="stock[<?php echo $product_id; ?>]" 
                                           value="<?php echo $stock !== '' ? intval($stock) : ''; ?>" 
                                           min="0" 
                                           class="stock-input"
                                           data-original="<?php echo $stock !== '' ? intval($stock) : ''; ?>"
                                           placeholder="∞">
                                    <button type="button" onclick="adjustStock(<?php echo $product_id; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td><?php echo intval($sold); ?></td>
                            <td><span class="stock-badge <?php echo $status; ?>">
                                <?php 
                                echo $status === 'instock' ? 'Còn hàng' : 
                                    ($status === 'lowstock' ? 'Sắp hết' : 'Hết hàng'); 
                                ?>
                            </span></td>
                            <td>
                                <input type="number" 
                                       name="add_stock[<?php echo $product_id; ?>]" 
                                       value="" 
                                       min="0" 
                                       placeholder="Nhập thêm"
                                       style="width:90px;">
                            </td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:#666;">
                                Không tìm thấy sản phẩm nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="inventory-actions">
                <button type="submit" name="action" value="update_stock" class="btn-update-inventory">
                    <i class="bi bi-arrow-repeat" style="margin-right:5px;"></i>
                    Cập nhật tồn kho
                </button>
                <span style="color:#666; font-size:13px;">
                    Bạn có thể chỉnh sửa số lượng trực tiếp hoặc nhập thêm vào cột "Nhập thêm"
                </span>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($products->max_num_pages > 1): ?>
        <div class="pagination-wrap">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $paged,
                'total' => $products->max_num_pages,
                'prev_text' => '&laquo; Trước',
                'next_text' => 'Sau &raquo;',
            ));
            ?>
        </div>
        <?php endif; ?>
        
        <script>
        function adjustStock(productId, delta) {
            const input = document.querySelector(`input[name="stock[${productId}]"]`);
            let val = parseInt(input.value) || 0;
            val = Math.max(0, val + delta);
            input.value = val;
            input.style.background = '#fff3cd';
        }
        
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="product_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        // Highlight changed inputs
        document.querySelectorAll('.stock-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value !== this.dataset.original) {
                    this.style.background = '#fff3cd';
                } else {
                    this.style.background = '';
                }
            });
        });
        </script>
    </div>
    <?php
}

// =============================================
// XỬ LÝ CẬP NHẬT TỒN KHO
// =============================================
function petshop_process_inventory_update() {
    if (!current_user_can('edit_posts')) {
        wp_die('Không có quyền');
    }
    
    $updated = 0;
    $user_id = get_current_user_id();
    $timestamp = current_time('mysql');
    
    // Update direct stock changes
    if (isset($_POST['stock']) && is_array($_POST['stock'])) {
        foreach ($_POST['stock'] as $product_id => $new_stock) {
            $product_id = intval($product_id);
            $new_stock = $new_stock !== '' ? intval($new_stock) : '';
            $old_stock = get_post_meta($product_id, 'product_stock', true);
            
            if ($new_stock !== $old_stock) {
                update_post_meta($product_id, 'product_stock', $new_stock);
                
                // Log the change
                $change = ($new_stock !== '' && $old_stock !== '') ? ($new_stock - intval($old_stock)) : 0;
                petshop_log_stock_change($product_id, $old_stock, $new_stock, 'manual_adjust', 'Điều chỉnh thủ công', $user_id);
                
                $updated++;
            }
        }
    }
    
    // Add stock from "add_stock" column
    if (isset($_POST['add_stock']) && is_array($_POST['add_stock'])) {
        foreach ($_POST['add_stock'] as $product_id => $add_qty) {
            if (empty($add_qty) || intval($add_qty) <= 0) continue;
            
            $product_id = intval($product_id);
            $add_qty = intval($add_qty);
            $old_stock = get_post_meta($product_id, 'product_stock', true);
            $old_stock = $old_stock !== '' ? intval($old_stock) : 0;
            $new_stock = $old_stock + $add_qty;
            
            update_post_meta($product_id, 'product_stock', $new_stock);
            
            // Log the change
            petshop_log_stock_change($product_id, $old_stock, $new_stock, 'stock_in', 'Nhập kho', $user_id);
            
            $updated++;
        }
    }
    
    if ($updated > 0) {
        add_action('admin_notices', function() use ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật tồn kho cho ' . $updated . ' sản phẩm.</p></div>';
        });
    }
}

// =============================================
// GHI LOG THAY ĐỔI KHO
// =============================================
function petshop_log_stock_change($product_id, $old_stock, $new_stock, $type, $note = '', $user_id = 0, $order_id = 0) {
    $logs = get_option('petshop_stock_logs', array());
    
    $logs[] = array(
        'id' => uniqid(),
        'product_id' => $product_id,
        'product_name' => get_the_title($product_id),
        'old_stock' => $old_stock,
        'new_stock' => $new_stock,
        'change' => is_numeric($new_stock) && is_numeric($old_stock) ? ($new_stock - $old_stock) : 0,
        'type' => $type, // stock_in, stock_out, manual_adjust, order, order_cancel
        'note' => $note,
        'user_id' => $user_id,
        'order_id' => $order_id,
        'timestamp' => current_time('mysql'),
    );
    
    // Keep only last 1000 logs
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    update_option('petshop_stock_logs', $logs);
}

// =============================================
// TRANG LỊCH SỬ KHO
// =============================================
function petshop_stock_history_page() {
    $logs = get_option('petshop_stock_logs', array());
    $logs = array_reverse($logs); // Newest first
    
    // Filters
    $filter_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $filter_product = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $filter_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
    
    if ($filter_type) {
        $logs = array_filter($logs, function($log) use ($filter_type) {
            return $log['type'] === $filter_type;
        });
    }
    if ($filter_product) {
        $logs = array_filter($logs, function($log) use ($filter_product) {
            return $log['product_id'] == $filter_product;
        });
    }
    if ($filter_date) {
        $logs = array_filter($logs, function($log) use ($filter_date) {
            return date('Y-m-d', strtotime($log['timestamp'])) === $filter_date;
        });
    }
    
    // Pagination
    $per_page = 50;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $total = count($logs);
    $total_pages = ceil($total / $per_page);
    $logs = array_slice($logs, ($paged - 1) * $per_page, $per_page);
    
    $type_labels = array(
        'stock_in' => array('label' => 'Nhập kho', 'color' => '#4caf50', 'icon' => '<i class="bi bi-box-arrow-in-down"></i>'),
        'stock_out' => array('label' => 'Xuất kho', 'color' => '#f44336', 'icon' => '<i class="bi bi-box-arrow-up"></i>'),
        'manual_adjust' => array('label' => 'Điều chỉnh', 'color' => '#ff9800', 'icon' => '<i class="bi bi-pencil-square"></i>'),
        'order' => array('label' => 'Đơn hàng', 'color' => '#2196f3', 'icon' => '<i class="bi bi-cart-check"></i>'),
        'order_cancel' => array('label' => 'Hủy đơn', 'color' => '#9c27b0', 'icon' => '<i class="bi bi-arrow-counterclockwise"></i>'),
    );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <i class="bi bi-clock-history" style="font-size: 28px; margin-right: 10px;"></i>
            Lịch sử Kho hàng
        </h1>
        
        <style>
            .history-filters {
                background: #fff;
                padding: 15px 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin: 20px 0;
                display: flex;
                gap: 15px;
                align-items: center;
                flex-wrap: wrap;
            }
            .history-table {
                background: #fff;
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
            }
            .history-table th {
                background: #f8f9fa;
                padding: 12px 15px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #ddd;
            }
            .history-table td {
                padding: 10px 15px;
                border-bottom: 1px solid #eee;
            }
            .history-table tr:hover td {
                background: #f8f9fa;
            }
            .type-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
            }
            .change-positive { color: #4caf50; font-weight: 600; }
            .change-negative { color: #f44336; font-weight: 600; }
            .log-note { color: #666; font-size: 12px; }
        </style>
        
        <!-- Filters -->
        <form method="get" class="history-filters">
            <input type="hidden" name="post_type" value="product">
            <input type="hidden" name="page" value="petshop-stock-history">
            
            <select name="type">
                <option value="">-- Loại thay đổi --</option>
                <?php foreach ($type_labels as $key => $info): ?>
                    <option value="<?php echo $key; ?>" <?php selected($filter_type, $key); ?>>
                        <?php echo $info['label']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" name="date" value="<?php echo esc_attr($filter_date); ?>">
            
            <button type="submit" class="button">Lọc</button>
            <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-stock-history'); ?>" class="button">Reset</a>
            
            <span style="margin-left:auto; color:#666;">
                Tổng: <?php echo $total; ?> bản ghi
            </span>
        </form>
        
        <!-- History Table -->
        <table class="history-table">
            <thead>
                <tr>
                    <th style="width:160px;">Thời gian</th>
                    <th>Sản phẩm</th>
                    <th style="width:120px;">Loại</th>
                    <th style="width:100px;">Trước</th>
                    <th style="width:100px;">Sau</th>
                    <th style="width:100px;">Thay đổi</th>
                    <th>Ghi chú</th>
                    <th style="width:120px;">Người thực hiện</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:#666;">
                            Chưa có lịch sử thay đổi kho
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $type_info = $type_labels[$log['type']] ?? array('label' => $log['type'], 'color' => '#999', 'icon' => '•');
                        $user = $log['user_id'] ? get_user_by('ID', $log['user_id']) : null;
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($log['product_id']); ?>">
                                <?php echo esc_html($log['product_name']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="type-badge" style="background: <?php echo $type_info['color']; ?>20; color: <?php echo $type_info['color']; ?>;">
                                <?php echo $type_info['icon']; ?> <?php echo $type_info['label']; ?>
                            </span>
                        </td>
                        <td><?php echo $log['old_stock'] !== '' ? $log['old_stock'] : '—'; ?></td>
                        <td><?php echo $log['new_stock'] !== '' ? $log['new_stock'] : '—'; ?></td>
                        <td>
                            <?php if ($log['change'] > 0): ?>
                                <span class="change-positive">+<?php echo $log['change']; ?></span>
                            <?php elseif ($log['change'] < 0): ?>
                                <span class="change-negative"><?php echo $log['change']; ?></span>
                            <?php else: ?>
                                <span style="color:#999;">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="log-note"><?php echo esc_html($log['note']); ?></span>
                            <?php if ($log['order_id']): ?>
                                <a href="<?php echo admin_url('post.php?post=' . $log['order_id'] . '&action=edit'); ?>" style="font-size:12px;">
                                    #<?php echo $log['order_id']; ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user ? esc_html($user->display_name) : '<span style="color:#999;">Hệ thống</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="margin-top:20px; text-align:center;">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&laquo; Trước',
                'next_text' => 'Sau &raquo;',
            ));
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// =============================================
// AJAX: CẬP NHẬT NHANH TỒN KHO
// =============================================
function petshop_ajax_update_stock() {
    check_ajax_referer('petshop_inventory_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Không có quyền');
    }
    
    $product_id = intval($_POST['product_id']);
    $new_stock = intval($_POST['stock']);
    $old_stock = get_post_meta($product_id, 'product_stock', true);
    
    update_post_meta($product_id, 'product_stock', $new_stock);
    petshop_log_stock_change($product_id, $old_stock, $new_stock, 'manual_adjust', 'Cập nhật nhanh', get_current_user_id());
    
    wp_send_json_success(array('new_stock' => $new_stock));
}
add_action('wp_ajax_petshop_update_stock', 'petshop_ajax_update_stock');

// =============================================
// HOOK: TRỪ KHO KHI ĐẶT HÀNG (Đã có trong orders-reviews.php, cập nhật để log)
// =============================================
function petshop_reduce_stock_on_order($order_id, $cart_items) {
    foreach ($cart_items as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        
        if ($product_id > 0) {
            $old_stock = get_post_meta($product_id, 'product_stock', true);
            if ($old_stock !== '' && $old_stock !== false) {
                $new_stock = max(0, intval($old_stock) - $quantity);
                update_post_meta($product_id, 'product_stock', $new_stock);
                
                // Log
                petshop_log_stock_change(
                    $product_id, 
                    $old_stock, 
                    $new_stock, 
                    'order', 
                    'Đơn hàng mới', 
                    get_current_user_id(), 
                    $order_id
                );
            }
            
            // Update sold count
            $sold = intval(get_post_meta($product_id, 'product_sold_count', true));
            update_post_meta($product_id, 'product_sold_count', $sold + $quantity);
        }
    }
}

// =============================================
// HOOK: HOÀN KHO KHI HỦY ĐƠN
// =============================================
function petshop_restore_stock_on_cancel($order_id, $old_status, $new_status) {
    if ($new_status !== 'cancelled' || $old_status === 'cancelled') {
        return;
    }
    
    $cart_items = json_decode(get_post_meta($order_id, 'cart_items', true), true);
    if (empty($cart_items)) return;
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        
        if ($product_id > 0) {
            $old_stock = get_post_meta($product_id, 'product_stock', true);
            $new_stock = intval($old_stock) + $quantity;
            update_post_meta($product_id, 'product_stock', $new_stock);
            
            // Log
            petshop_log_stock_change(
                $product_id, 
                $old_stock, 
                $new_stock, 
                'order_cancel', 
                'Hủy đơn hàng', 
                get_current_user_id(), 
                $order_id
            );
            
            // Reduce sold count
            $sold = intval(get_post_meta($product_id, 'product_sold_count', true));
            update_post_meta($product_id, 'product_sold_count', max(0, $sold - $quantity));
        }
    }
}

// Hook when order status changes
function petshop_order_status_change_hook($post_id, $post, $update) {
    if ($post->post_type !== 'petshop_order' || !$update) return;
    
    $new_status = get_post_meta($post_id, 'order_status', true);
    $old_status = get_post_meta($post_id, '_prev_order_status', true);
    
    if ($old_status !== $new_status) {
        petshop_restore_stock_on_cancel($post_id, $old_status, $new_status);
        update_post_meta($post_id, '_prev_order_status', $new_status);
    }
}
add_action('save_post', 'petshop_order_status_change_hook', 10, 3);

// =============================================
// THÊM FIELD TỒN KHO VÀO FORM THÊM/SỬA SẢN PHẨM
// =============================================
function petshop_add_inventory_fields_to_product_form() {
    // This will be called in admin-product.php form
}

// =============================================
// AJAX: LẤY VARIANTS CỦA SẢN PHẨM (cho inventory modal)
// =============================================
add_action('wp_ajax_petshop_get_product_variants_admin', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) wp_send_json_error('Invalid product');

    if (!function_exists('petshop_get_product_variants')) {
        wp_send_json_error('Variants not available');
    }
    $variants  = petshop_get_product_variants($product_id);
    $total     = array_sum(array_column($variants, 'stock'));
    $title     = get_the_title($product_id);
    wp_send_json_success(array(
        'variants' => $variants,
        'total'    => $total,
        'title'    => $title,
    ));
});

// AJAX: Cập nhật stock variant
add_action('wp_ajax_petshop_update_variant_stock', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');
    check_ajax_referer('petshop_variant_stock_nonce', 'nonce');

    global $wpdb;
    $table      = $wpdb->prefix . 'petshop_variants';
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $new_stock  = max(0, intval($_POST['stock'] ?? 0));
    $product_id = intval($_POST['product_id'] ?? 0);

    if (!$variant_id || !$product_id) wp_send_json_error('Invalid data');

    $old = $wpdb->get_var($wpdb->prepare("SELECT stock FROM {$table} WHERE id = %d", $variant_id));
    $wpdb->update($table, array('stock' => $new_stock), array('id' => $variant_id));

    // Sync tổng stock sản phẩm
    if (function_exists('petshop_sync_variant_stock')) {
        $new_total = petshop_sync_variant_stock($product_id);
    } else {
        $new_total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(stock) FROM {$table} WHERE product_id = %d", $product_id
        ));
    }

    // Log
    if (function_exists('petshop_log_stock_change')) {
        petshop_log_stock_change($product_id, $old, $new_stock, 'manual_adjust',
            'Điều chỉnh variant #' . $variant_id, get_current_user_id());
    }

    wp_send_json_success(array('new_total' => intval($new_total)));
});

// =============================================
// TRANG QUẢN LÝ VARIANT TỒN KHO
// =============================================
function petshop_register_variant_inventory_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Tồn kho phân loại',
        '🏷️ Tồn kho phân loại',
        'edit_posts',
        'petshop-variant-inventory',
        'petshop_variant_inventory_page'
    );
}
add_action('admin_menu', 'petshop_register_variant_inventory_menu');

function petshop_variant_inventory_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_variants';
    $nonce = wp_create_nonce('petshop_variant_stock_nonce');

    // Kiểm tra bảng tồn tại
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;

    // Lấy danh sách sản phẩm có variants
    $products_with_variants = array();
    if ($table_exists) {
        $product_ids = $wpdb->get_col(
            "SELECT DISTINCT product_id FROM {$table} ORDER BY product_id DESC"
        );
        foreach ($product_ids as $pid) {
            $post = get_post($pid);
            if ($post && $post->post_status === 'publish') {
                $products_with_variants[] = $post;
            }
        }
    }

    // Search filter
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    if ($search && !empty($products_with_variants)) {
        $products_with_variants = array_filter($products_with_variants, function($p) use ($search) {
            return stripos($p->post_title, $search) !== false;
        });
    }
    ?>
    <div class="wrap" id="variant-inventory-wrap">
    <style>
    #variant-inventory-wrap { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
    .vi-header { display:flex; align-items:center; gap:14px; margin-bottom:20px; }
    .vi-header h1 { margin:0; font-size:1.5rem; color:#23282d; }

    .vi-search-bar { display:flex; gap:10px; margin-bottom:20px; align-items:center; }
    .vi-search-bar input { padding:8px 14px; border:1px solid #ddd; border-radius:8px; font-size:0.9rem; width:280px; }

    .vi-product-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:18px; }
    .vi-product-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.07); overflow:hidden; transition:box-shadow .2s; }
    .vi-product-card:hover { box-shadow:0 6px 24px rgba(0,0,0,0.12); }

    .vi-card-header { display:flex; align-items:center; gap:12px; padding:14px 18px; background:linear-gradient(135deg,#FDF8F3,#F5EDE0); border-bottom:1px solid #E8CCAD; }
    .vi-card-header img { width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #E8CCAD; }
    .vi-card-header .vi-product-name { font-weight:700; color:#5D4E37; font-size:0.95rem; flex:1; line-height:1.3; }
    .vi-card-header .vi-total-badge { background:#EC802B; color:#fff; font-size:0.78rem; font-weight:700; padding:4px 12px; border-radius:20px; white-space:nowrap; }

    .vi-variants-table { width:100%; border-collapse:collapse; }
    .vi-variants-table th { padding:9px 14px; text-align:left; font-size:0.78rem; color:#7A6B5A; font-weight:600; text-transform:uppercase; letter-spacing:.5px; background:#fafafa; border-bottom:1px solid #f0ebe4; }
    .vi-variants-table td { padding:9px 14px; border-bottom:1px solid #f5f0ea; font-size:0.88rem; }
    .vi-variants-table tr:last-child td { border-bottom:none; }
    .vi-variants-table tr:hover td { background:#FDF8F3; }

    .vi-size-badge  { display:inline-block; background:#EC802B22; color:#5D4E37; padding:2px 10px; border-radius:12px; font-weight:700; font-size:0.82rem; }
    .vi-color-dot   { display:inline-block; width:12px; height:12px; border-radius:50%; border:1px solid rgba(0,0,0,0.15); margin-right:6px; vertical-align:middle; }
    .vi-color-name  { font-weight:600; }

    .vi-stock-input { width:70px; padding:5px 8px; border:1.5px solid #E8CCAD; border-radius:6px; font-size:0.88rem; text-align:center; transition:border-color .2s; }
    .vi-stock-input:focus { border-color:#EC802B; outline:none; }
    .vi-stock-input.changed { border-color:#ffc107; background:#fff8e1; }

    .vi-save-btn { padding:5px 14px; background:linear-gradient(135deg,#EC802B,#F5994D); color:#fff; border:none; border-radius:7px; cursor:pointer; font-size:0.82rem; font-weight:600; transition:all .2s; }
    .vi-save-btn:hover { transform:translateY(-1px); box-shadow:0 3px 10px rgba(236,128,43,0.3); }
    .vi-save-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }

    .vi-stock-zero  { color:#d9534f; font-weight:700; }
    .vi-stock-low   { color:#f0ad4e; font-weight:700; }
    .vi-stock-ok    { color:#5cb85c; font-weight:700; }

    .vi-empty { text-align:center; padding:60px 20px; color:#aaa; }
    .vi-empty i { font-size:3rem; display:block; margin-bottom:12px; }

    .vi-no-table { background:#fff3cd; border-left:4px solid #ffc107; padding:15px 20px; border-radius:8px; margin-bottom:20px; }

    .vi-edit-link { color:#2196F3; text-decoration:none; font-size:0.78rem; }
    .vi-edit-link:hover { text-decoration:underline; }
    </style>

    <div class="vi-header">
        <span style="font-size:1.8rem;">🏷️</span>
        <h1>Tồn kho theo Phân loại (Size / Màu sắc)</h1>
    </div>

    <?php if (!$table_exists) : ?>
    <div class="vi-no-table">
        <strong>⚠️ Chưa có bảng variants.</strong>
        Vui lòng vào <a href="<?php echo admin_url('edit.php?post_type=product&page=petshop-add-product'); ?>">thêm/sửa 1 sản phẩm</a> để tự động tạo bảng, sau đó quay lại đây.
    </div>
    <?php elseif (empty($products_with_variants)) : ?>
    <div class="vi-empty">
        <i>🏷️</i>
        <p>Chưa có sản phẩm nào có phân loại (Size/Màu).</p>
        <p>Vào <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">danh sách sản phẩm</a> → chỉnh sửa sản phẩm → bật "Phân loại sản phẩm" để thêm.</p>
    </div>
    <?php else : ?>

    <div class="vi-search-bar">
        <form method="get">
            <input type="hidden" name="post_type" value="product">
            <input type="hidden" name="page" value="petshop-variant-inventory">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="🔍 Tìm tên sản phẩm...">
            <button type="submit" class="button">Tìm</button>
            <?php if ($search) : ?><a href="?post_type=product&page=petshop-variant-inventory" class="button">Xóa lọc</a><?php endif; ?>
        </form>
        <span style="color:#666;font-size:0.88rem;">Tổng: <strong><?php echo count($products_with_variants); ?></strong> sản phẩm có phân loại</span>
    </div>

    <div class="vi-product-list" id="viProductList">
    <?php foreach ($products_with_variants as $product) :
        $pid       = $product->ID;
        $variants  = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d ORDER BY sort_order ASC, size ASC, color ASC", $pid
        ), ARRAY_A);
        $total     = array_sum(array_column($variants, 'stock'));
        $thumb_url = get_the_post_thumbnail_url($pid, 'thumbnail') ?: '';
        $edit_url  = admin_url('edit.php?post_type=product&page=petshop-add-product&product_id=' . $pid);

        // Group by size for display
        $hasSizes  = count(array_filter(array_unique(array_column($variants, 'size')))) > 0;
        $hasColors = count(array_filter(array_unique(array_column($variants, 'color')))) > 0;
    ?>
    <div class="vi-product-card">
        <div class="vi-card-header">
            <?php if ($thumb_url) : ?>
            <img src="<?php echo esc_url($thumb_url); ?>" alt="">
            <?php else : ?>
            <div style="width:48px;height:48px;background:#F5EDE0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">📦</div>
            <?php endif; ?>
            <div style="flex:1;min-width:0;">
                <div class="vi-product-name"><?php echo esc_html($product->post_title); ?></div>
                <a href="<?php echo esc_url($edit_url); ?>" class="vi-edit-link">✏️ Chỉnh sửa phân loại</a>
            </div>
            <span class="vi-total-badge" id="total-badge-<?php echo $pid; ?>">
                🏭 <?php echo $total; ?> tổng
            </span>
        </div>

        <table class="vi-variants-table">
            <thead>
                <tr>
                    <?php if ($hasSizes)  : ?><th>📐 Size</th><?php endif; ?>
                    <?php if ($hasColors) : ?><th>🎨 Màu sắc</th><?php endif; ?>
                    <th>📦 Tồn kho</th>
                    <th>SKU</th>
                    <th>Lưu</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($variants as $v) :
                $stk = intval($v['stock']);
                $stk_class = $stk === 0 ? 'vi-stock-zero' : ($stk <= 5 ? 'vi-stock-low' : 'vi-stock-ok');
            ?>
            <tr>
                <?php if ($hasSizes) : ?>
                <td>
                    <?php if ($v['size']) : ?>
                    <span class="vi-size-badge"><?php echo esc_html($v['size']); ?></span>
                    <?php else : ?>
                    <span style="color:#bbb;">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <?php if ($hasColors) : ?>
                <td>
                    <?php if ($v['color']) : ?>
                    <span class="vi-color-dot" style="background:<?php echo esc_attr($v['color_hex'] ?: '#E8CCAD'); ?>;"></span>
                    <span class="vi-color-name"><?php echo esc_html($v['color']); ?></span>
                    <?php else : ?>
                    <span style="color:#bbb;">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <td>
                    <input type="number" min="0"
                           class="vi-stock-input"
                           id="vi-stock-<?php echo $v['id']; ?>"
                           value="<?php echo $stk; ?>"
                           data-original="<?php echo $stk; ?>"
                           data-variant-id="<?php echo $v['id']; ?>"
                           data-product-id="<?php echo $pid; ?>"
                           onchange="viMarkChanged(this)"
                           style="<?php echo $stk===0 ? 'border-color:#ffcdd2;background:#fff5f5;' : ''; ?>">
                    <span class="<?php echo $stk_class; ?>" id="vi-stk-label-<?php echo $v['id']; ?>" style="font-size:0.78rem;margin-left:4px;">
                        <?php echo $stk===0 ? 'Hết' : ($stk<=5 ? 'Sắp hết' : ''); ?>
                    </span>
                </td>

                <td style="color:#999;font-size:0.78rem;font-family:monospace;">
                    <?php echo $v['sku'] ? esc_html($v['sku']) : '—'; ?>
                </td>

                <td>
                    <button class="vi-save-btn"
                            onclick="viSaveStock(<?php echo $v['id']; ?>, <?php echo $pid; ?>, this)">
                        💾 Lưu
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    </div>

    <?php endif; ?>
    </div>

    <script>
    const VI_NONCE   = '<?php echo esc_js($nonce); ?>';
    const VI_AJAX    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

    function viMarkChanged(input) {
        input.classList.toggle('changed', input.value != input.dataset.original);
    }

    async function viSaveStock(variantId, productId, btn) {
        const input    = document.getElementById('vi-stock-' + variantId);
        const newStock = parseInt(input.value) || 0;
        const label    = document.getElementById('vi-stk-label-' + variantId);

        btn.disabled = true; btn.textContent = '⏳';

        const body = new URLSearchParams({
            action: 'petshop_update_variant_stock',
            nonce: VI_NONCE,
            variant_id: variantId,
            product_id: productId,
            stock: newStock,
        });

        const res  = await fetch(VI_AJAX, {method:'POST', credentials:'same-origin', body});
        const data = await res.json();

        if (data.success) {
            input.dataset.original = newStock;
            input.classList.remove('changed');
            input.style.borderColor = newStock === 0 ? '#ffcdd2' : '#c8e6c9';
            input.style.background  = newStock === 0 ? '#fff5f5' : '#f1f8e9';
            setTimeout(() => { input.style.borderColor=''; input.style.background=''; }, 2000);

            // Update label
            if (label) {
                label.textContent = newStock===0 ? 'Hết' : (newStock<=5 ? 'Sắp hết' : '');
                label.className   = 'vi-stock-' + (newStock===0?'zero':newStock<=5?'low':'ok');
            }
            // Update total badge
            const badge = document.getElementById('total-badge-' + productId);
            if (badge) badge.textContent = '🏭 ' + data.data.new_total + ' tổng';

            btn.textContent = '✅'; btn.style.background = '#43a047';
            setTimeout(() => { btn.textContent='💾 Lưu'; btn.style.background=''; btn.disabled=false; }, 1500);
        } else {
            btn.textContent = '❌'; btn.disabled = false;
            setTimeout(() => { btn.textContent='💾 Lưu'; }, 1500);
            alert('Lỗi: ' + (data.data || 'Không xác định'));
        }
    }
    </script>
    <?php
}