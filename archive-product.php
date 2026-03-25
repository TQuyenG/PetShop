<?php
/**
 * Template: Trang danh sách sản phẩm
 * 
 * @package PetShop
 */



get_header();

// Xử lý thêm/xóa sản phẩm yêu thích (backend, không đổi giao diện)
$favorites = array();
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'petshop_favorites', true);
    if (!is_array($favorites)) $favorites = array();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_product_id'])) {
        $fav_id = intval($_POST['favorite_product_id']);
        if (isset($_POST['action_favorite']) && $_POST['action_favorite'] === 'add') {
            if (!in_array($fav_id, $favorites)) {
                $favorites[] = $fav_id;
                update_user_meta($user_id, 'petshop_favorites', $favorites);
            }
        } elseif (isset($_POST['action_favorite']) && $_POST['action_favorite'] === 'remove') {
            $favorites = array_diff($favorites, [$fav_id]);
            update_user_meta($user_id, 'petshop_favorites', $favorites);
        }
        // Tránh submit lại form khi refresh
        wp_redirect(add_query_arg(null, null));
        exit;
    }
}

// Lấy danh mục hiện tại (nếu có)
$current_category = get_queried_object();
$is_category_page = is_tax('product_category');

// Lấy các tham số lọc từ URL
$current_sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';
$current_price_filter = isset($_GET['price']) ? sanitize_text_field($_GET['price']) : '';

// Hàm đếm số sản phẩm bao gồm cả danh mục con
function petshop_get_category_product_count($term_id) {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_category',
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true, // Bao gồm cả danh mục con
            ),
        ),
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

// Xây dựng query args cho sản phẩm
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$args = array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
);

// Nếu đang ở trang danh mục
if ($is_category_page) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_category',
            'field' => 'term_id',
            'terms' => $current_category->term_id,
            'include_children' => true,
        ),
    );
}

// Xử lý sắp xếp
switch ($current_sort) {
    case 'oldest':
        $args['orderby'] = 'date';
        $args['order'] = 'ASC';
        break;
    case 'price_low':
        $args['meta_key'] = 'product_price';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'ASC';
        break;
    case 'price_high':
        $args['meta_key'] = 'product_price';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'popular':
        $args['meta_key'] = 'product_views';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'best_selling':
        $args['meta_key'] = 'product_sold';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'rating':
        $args['meta_key'] = 'product_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'newest':
    default:
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
}

// Xử lý lọc theo giá
if ($current_price_filter) {
    switch ($current_price_filter) {
        case 'under100':
            $args['meta_query'][] = array(
                'key' => 'product_price',
                'value' => 100000,
                'compare' => '<',
                'type' => 'NUMERIC',
            );
            break;
        case '100to500':
            $args['meta_query'][] = array(
                'relation' => 'AND',
                array(
                    'key' => 'product_price',
                    'value' => 100000,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => 'product_price',
                    'value' => 500000,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ),
            );
            break;
        case '500to1000':
            $args['meta_query'][] = array(
                'relation' => 'AND',
                array(
                    'key' => 'product_price',
                    'value' => 500000,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => 'product_price',
                    'value' => 1000000,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ),
            );
            break;
        case 'over1000':
            $args['meta_query'][] = array(
                'key' => 'product_price',
                'value' => 1000000,
                'compare' => '>',
                'type' => 'NUMERIC',
            );
            break;
    }
}

// Thực hiện query
$products_query = new WP_Query($args);

// Tạo URL base cho filter
$base_url = $is_category_page ? get_term_link($current_category) : get_post_type_archive_link('product');
?>

<main class="main-content">
    <!-- Page Header -->
    <section class="page-header" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%) !important; padding: 60px 0; text-align: center;">
        <div class="container">
            <?php if ($is_category_page) : ?>
                <h1 class="page-title" style="color: #5D4E37 !important;"><?php echo esc_html($current_category->name); ?></h1>
                <?php if ($current_category->description) : ?>
                    <p style="font-size: 1.1rem; color: #7A6B5A !important; margin-top: 10px;"><?php echo esc_html($current_category->description); ?></p>
                <?php endif; ?>
            <?php else : ?>
                <h1 class="page-title" style="color: #5D4E37 !important;"><i class="bi bi-bag-heart" style="color: #EC802B;"></i> Sản Phẩm</h1>
                <p style="font-size: 1.1rem; color: #7A6B5A !important; margin-top: 10px;">Sản phẩm chất lượng cao cho thú cưng của bạn</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Breadcrumb -->
    <section style="background: var(--color-light); padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
        <div class="container">
            <nav style="font-size: 0.9rem;">
                <a href="<?php echo home_url(); ?>" style="color: var(--color-dark); text-decoration: none;">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
                <span style="margin: 0 10px; color: #999;">/</span>
                <?php if ($is_category_page) : ?>
                    <a href="<?php echo get_post_type_archive_link('product'); ?>" style="color: var(--color-dark); text-decoration: none;">Sản phẩm</a>
                    <span style="margin: 0 10px; color: #999;">/</span>
                    <span style="color: var(--color-primary);"><?php echo esc_html($current_category->name); ?></span>
                <?php else : ?>
                    <span style="color: var(--color-primary);">Sản phẩm</span>
                <?php endif; ?>
            </nav>
        </div>
    </section>

    <!-- Products Section -->
    <section style="padding: 60px 0; background: var(--color-bg);">
        <div class="container">
            <div class="products-layout">
                
                <!-- Sidebar - Danh mục -->
                <aside class="product-sidebar">
                    <div class="sidebar-box">
                        <h3 class="sidebar-title">
                            <i class="bi bi-grid" style="color: var(--color-primary);"></i> Danh mục sản phẩm
                        </h3>
                        
                        <?php
                        $parent_categories = get_terms(array(
                            'taxonomy' => 'product_category',
                            'hide_empty' => false,
                            'parent' => 0,
                        ));
                        
                        if (!empty($parent_categories) && !is_wp_error($parent_categories)) :
                        ?>
                        <ul class="category-list">
                            <li>
                                <a href="<?php echo get_post_type_archive_link('product'); ?>" 
                                   class="category-link <?php echo !$is_category_page ? 'active' : ''; ?>">
                                    <i class="bi bi-bag-heart"></i> Tất cả sản phẩm
                                </a>
                            </li>
                        <?php foreach ($parent_categories as $parent_cat) : 
                                $child_categories = get_terms(array(
                                    'taxonomy' => 'product_category',
                                    'hide_empty' => false,
                                    'parent' => $parent_cat->term_id,
                                ));
                                $is_active = $is_category_page && $current_category->term_id == $parent_cat->term_id;
                                // Đếm tổng sản phẩm bao gồm cả danh mục con
                                $total_count = petshop_get_category_product_count($parent_cat->term_id);
                            ?>
                            <li>
                                <a href="<?php echo get_term_link($parent_cat); ?>" 
                                   class="category-link <?php echo $is_active ? 'active' : ''; ?>">
                                    <?php echo esc_html($parent_cat->name); ?>
                                    <span class="count">(<?php echo $total_count; ?>)</span>
                                </a>
                                
                                <?php if (!empty($child_categories) && !is_wp_error($child_categories)) : ?>
                                <ul class="sub-category-list">
                                    <?php foreach ($child_categories as $child_cat) : 
                                        $child_active = $is_category_page && $current_category->term_id == $child_cat->term_id;
                                    ?>
                                    <li>
                                        <a href="<?php echo get_term_link($child_cat); ?>" 
                                           class="<?php echo $child_active ? 'active' : ''; ?>">
                                            <i class="bi bi-<?php echo $child_active ? 'check-circle-fill' : 'circle'; ?>"></i>
                                            <?php echo esc_html($child_cat->name); ?>
                                            <span>(<?php echo $child_cat->count; ?>)</span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else : ?>
                            <p style="color: #666; font-size: 0.9rem;">Chưa có danh mục nào.</p>
                        <?php endif; ?>
                        
                        <!-- Filter by Price -->
                        <div class="filter-section">
                            <h4 class="filter-title">
                                <i class="bi bi-funnel" style="color: var(--color-primary);"></i> Lọc theo giá
                            </h4>
                            <div class="price-filters">
                                <?php
                                $price_options = array(
                                    '' => 'Tất cả mức giá',
                                    'under100' => 'Dưới 100.000đ',
                                    '100to500' => '100.000đ - 500.000đ',
                                    '500to1000' => '500.000đ - 1.000.000đ',
                                    'over1000' => 'Trên 1.000.000đ',
                                );
                                foreach ($price_options as $value => $label) :
                                    $filter_url = add_query_arg(array('price' => $value, 'sort' => $current_sort), $base_url);
                                    if ($value === '') {
                                        $filter_url = remove_query_arg('price', add_query_arg('sort', $current_sort, $base_url));
                                    }
                                ?>
                                <a href="<?php echo esc_url($filter_url); ?>" class="price-filter-item <?php echo $current_price_filter === $value ? 'active' : ''; ?>">
                                    <i class="bi bi-<?php echo $current_price_filter === $value ? 'check-circle-fill' : 'circle'; ?>"></i>
                                    <span><?php echo $label; ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Clear Filters -->
                        <?php if ($current_price_filter || $current_sort !== 'newest') : ?>
                        <div style="margin-top: 20px;">
                            <a href="<?php echo esc_url($base_url); ?>" class="clear-filters">
                                <i class="bi bi-x-circle"></i> Xóa bộ lọc
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="products-main">
                    <!-- Toolbar -->
                    <div class="products-toolbar">
                        <p class="results-count">
                            Hiển thị <strong><?php echo $products_query->found_posts; ?></strong> sản phẩm
                        </p>
                        <div class="toolbar-actions">
                            <select class="sort-select" id="sortSelect" onchange="applySort(this.value)">
                                <?php
                                $sort_options = array(
                                    'newest' => 'Mới nhất',
                                    'oldest' => 'Cũ nhất',
                                    'price_low' => 'Giá: Thấp đến cao',
                                    'price_high' => 'Giá: Cao đến thấp',
                                    'best_selling' => 'Bán chạy nhất',
                                    'popular' => 'Xem nhiều nhất',
                                    'rating' => 'Đánh giá cao nhất',
                                );
                                foreach ($sort_options as $value => $label) :
                                ?>
                                <option value="<?php echo $value; ?>" <?php selected($current_sort, $value); ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="view-modes">
                                <button class="view-btn active" data-view="grid"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                                <button class="view-btn" data-view="list"><i class="bi bi-list-ul"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Tags -->
                    <?php if ($current_price_filter || $current_sort !== 'newest') : ?>
                    <div class="active-filters">
                        <?php if ($current_sort !== 'newest') : ?>
                        <span class="filter-tag">
                            <?php echo $sort_options[$current_sort]; ?>
                            <a href="<?php echo esc_url(add_query_arg('sort', 'newest', remove_query_arg('sort'))); ?>"><i class="bi bi-x"></i></a>
                        </span>
                        <?php endif; ?>
                        <?php if ($current_price_filter) : ?>
                        <span class="filter-tag">
                            <?php echo $price_options[$current_price_filter]; ?>
                            <a href="<?php echo esc_url(remove_query_arg('price')); ?>"><i class="bi bi-x"></i></a>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Products Grid -->
                    <?php if ($products_query->have_posts()) : ?>
                    <div class="products-grid" id="productsGrid">
                        <?php while ($products_query->have_posts()) : $products_query->the_post(); 
                            $price = get_post_meta(get_the_ID(), 'product_price', true);
                            $sale_price = get_post_meta(get_the_ID(), 'product_sale_price', true);
                            $stock = get_post_meta(get_the_ID(), 'product_stock', true);
                            $sku = get_post_meta(get_the_ID(), 'product_sku', true);
                            $views = get_post_meta(get_the_ID(), 'product_views', true);
                            $sold = get_post_meta(get_the_ID(), 'product_sold', true);
                            $rating = get_post_meta(get_the_ID(), 'product_rating', true);
                            $rating = $rating ? $rating : rand(35, 50) / 10; // Random 3.5-5.0 nếu chưa có
                            
                            // Lấy thông tin giảm giá có thời hạn
                            $price_info = petshop_get_display_price(get_the_ID());
                        ?>
                        <article class="product-card">
                            <div class="product-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php the_post_thumbnail_url('petshop-product'); ?>" alt="<?php the_title_attribute(); ?>">
                                    </a>
                                <?php else : ?>
                                    <a href="<?php the_permalink(); ?>" class="no-image">
                                        <i class="bi bi-box-seam"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($price_info['is_on_sale'] && $price_info['discount_percent']) : ?>
                                <span class="discount-badge">-<?php echo $price_info['discount_percent']; ?>%</span>
                                <?php endif; ?>
                                
                                <!-- Quick Actions -->
                                <div class="product-actions">
                                    <?php $is_fav = in_array(get_the_ID(), $favorites); ?>
                                    <button class="action-btn fav-btn<?php echo $is_fav ? ' active' : ''; ?>"
                                            title="Yêu thích"
                                            data-product-id="<?php echo get_the_ID(); ?>"
                                            data-is-favorited="<?php echo $is_fav ? '1' : '0'; ?>"
                                            onclick="toggleFavBtn(this)"
                                            style="<?php echo $is_fav ? 'background:#FF6B6B;color:#fff;' : ''; ?>">
                                        <?php echo $is_fav ? '❤️' : '🤍'; ?>
                                    </button>
                                    <button class="action-btn primary btn-quick-view"
                                            title="Thêm vào giỏ"
                                            data-product-id="<?php echo get_the_ID(); ?>"
                                            onclick="openQuickView(<?php echo get_the_ID(); ?>)">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                    <a href="<?php the_permalink(); ?>" class="action-btn" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <?php 
                                $product_cats = get_the_terms(get_the_ID(), 'product_category');
                                if ($product_cats && !is_wp_error($product_cats)) :
                                ?>
                                <a href="<?php echo get_term_link($product_cats[0]); ?>" class="product-category">
                                    <?php echo esc_html($product_cats[0]->name); ?>
                                </a>
                                <?php endif; ?>
                                
                                <h3 class="product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <!-- Rating -->
                                <div class="product-rating">
                                    <?php 
                                    $full_stars = floor($rating);
                                    $has_half = ($rating - $full_stars) >= 0.5;
                                    for ($i = 1; $i <= 5; $i++) : 
                                        if ($i <= $full_stars) :
                                    ?>
                                        <i class="bi bi-star-fill filled"></i>
                                    <?php elseif ($i == $full_stars + 1 && $has_half) : ?>
                                        <i class="bi bi-star-half filled"></i>
                                    <?php else : ?>
                                        <i class="bi bi-star"></i>
                                    <?php 
                                        endif;
                                    endfor; 
                                    ?>
                                    <span class="rating-count">(<?php echo $rating; ?>)</span>
                                </div>
                                
                                <div class="product-footer">
                                    <div class="product-price">
                                        <?php if ($price_info['is_on_sale'] && $price_info['sale']) : ?>
                                            <span class="current-price"><?php echo number_format($price_info['sale'], 0, ',', '.'); ?>đ</span>
                                            <span class="original-price"><?php echo number_format($price_info['original'], 0, ',', '.'); ?>đ</span>
                                        <?php elseif ($price_info['original']) : ?>
                                            <span class="current-price"><?php echo number_format($price_info['original'], 0, ',', '.'); ?>đ</span>
                                        <?php else : ?>
                                            <span class="contact-price">Liên hệ</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($stock && $stock > 0) : ?>
                                        <span class="stock-status in-stock">
                                            <i class="bi bi-check-circle"></i> Còn hàng
                                        </span>
                                    <?php elseif ($stock === '0') : ?>
                                        <span class="stock-status out-stock">
                                            <i class="bi bi-x-circle"></i> Hết hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <?php
                        echo paginate_links(array(
                            'total' => $products_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '<i class="bi bi-chevron-left"></i>',
                            'next_text' => '<i class="bi bi-chevron-right"></i>',
                            'type' => 'list',
                            'add_args' => array(
                                'sort' => $current_sort,
                                'price' => $current_price_filter,
                            ),
                        ));
                        ?>
                    </div>

                    <?php else : ?>
                    <!-- No Products -->
                    <div class="no-products">
                        <i class="bi bi-box-seam"></i>
                        <h3>Chưa có sản phẩm</h3>
                        <p>Danh mục này chưa có sản phẩm nào. Vui lòng quay lại sau!</p>
                        <a href="<?php echo get_post_type_archive_link('product'); ?>" class="btn-primary">
                            <i class="bi bi-arrow-left"></i> Xem tất cả sản phẩm
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
/* Products Layout */
.products-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 40px;
}

/* Sidebar */
.product-sidebar {
    position: relative;
}

.sidebar-box {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    position: sticky;
    top: 100px;
}

.sidebar-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Category List */
.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list > li {
    margin-bottom: 10px;
}

.category-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    background: var(--color-light);
    color: var(--color-dark);
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.category-link:hover {
    background: #f5e6d8;
    color: var(--color-primary);
}

.category-link.active {
    background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
    color: #fff !important;
    box-shadow: 0 4px 15px rgba(236, 128, 43, 0.3);
}

.category-link .count {
    font-size: 0.85rem;
    opacity: 0.7;
}

.category-link.active .count {
    opacity: 1;
    color: #fff !important;
}

.sub-category-list {
    list-style: none;
    padding: 10px 0 0 20px;
    margin: 0;
}

.sub-category-list li {
    margin-bottom: 8px;
}

.sub-category-list a {
    color: #5D4E37;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    padding: 6px 10px;
    border-radius: 8px;
}

.sub-category-list a i {
    font-size: 0.7rem;
    color: #999;
}

.sub-category-list a:hover {
    color: var(--color-primary);
    background: #FDF8F3;
}

.sub-category-list a:hover i {
    color: var(--color-primary);
}

.sub-category-list a.active {
    color: #fff !important;
    font-weight: 700;
    background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%);
    box-shadow: 0 3px 10px rgba(102, 188, 180, 0.3);
}

.sub-category-list a.active i {
    color: #fff !important;
}

.sub-category-list a.active span {
    color: #fff !important;
}

.sub-category-list a span {
    color: #999;
    font-size: 0.8rem;
    margin-left: auto;
}

/* Filter Section */
.filter-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.filter-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.price-filters {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: background 0.3s;
}

.filter-option:hover {
    background: var(--color-light);
}

.filter-option input {
    accent-color: var(--color-primary);
}

/* Products Main */
.products-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 15px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.results-count {
    margin: 0;
    color: #666;
}

.toolbar-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.sort-select {
    padding: 10px 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    cursor: pointer;
    background: white;
    color: var(--color-dark);
    font-weight: 500;
    min-width: 180px;
}

.sort-select:hover,
.sort-select:focus {
    border-color: var(--color-primary);
    outline: none;
}

.view-modes {
    display: flex;
    gap: 5px;
}

.view-btn {
    width: 40px;
    height: 40px;
    border: 2px solid #E8CCAD;
    background: #fff;
    color: #5D4E37 !important;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn i {
    color: inherit !important;
}

.view-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary) !important;
    background: #FDF8F3;
}

.view-btn.active {
    border-color: var(--color-primary);
    background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(236, 128, 43, 0.3);
}

.view-btn.active i {
    color: #fff !important;
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

/* Product Card */
.product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.product-image {
    position: relative;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-image .no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 220px;
    background: linear-gradient(135deg, var(--color-light) 0%, var(--color-accent) 100%);
    color: var(--color-primary);
    font-size: 4rem;
    opacity: 0.5;
}

.discount-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #e74c3c;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

/* Product Actions */
.product-actions {
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 15px;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    transition: bottom 0.3s;
}

.product-card:hover .product-actions {
    bottom: 0;
}

.action-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: white;
    color: var(--color-primary);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.action-btn:hover {
    transform: scale(1.1);
}

.action-btn.primary {
    background: var(--color-primary);
    color: white;
}

/* Product Info */
.product-info {
    padding: 20px;
}

.product-category {
    font-size: 0.8rem;
    color: var(--color-secondary);
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-category:hover {
    color: var(--color-primary);
}

.product-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 8px 0;
    line-height: 1.4;
}

.product-title a {
    color: var(--color-dark);
    text-decoration: none;
}

.product-title a:hover {
    color: var(--color-primary);
}

/* Rating */
.product-rating {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-bottom: 10px;
}

.product-rating i {
    color: #ddd;
    font-size: 0.8rem;
}

.product-rating i.filled {
    color: var(--color-accent);
}

.rating-count {
    font-size: 0.8rem;
    color: #999;
    margin-left: 5px;
}

/* Product Footer */
.product-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.current-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--color-primary);
}

.original-price {
    font-size: 0.9rem;
    color: #999;
    text-decoration: line-through;
    margin-left: 8px;
}

.contact-price {
    font-size: 1rem;
    color: #999;
}

.stock-status {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.stock-status.in-stock {
    color: var(--color-secondary);
    background: rgba(102, 188, 180, 0.1);
}

.stock-status.out-stock {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.1);
}

/* Pagination */
.pagination-wrapper {
    margin-top: 50px;
    display: flex;
    justify-content: center;
}

.page-numbers {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 8px;
}

.page-numbers li a,
.page-numbers li span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.page-numbers li a {
    background: white;
    color: var(--color-dark);
    border: 1px solid #eee;
}

.page-numbers li a:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.page-numbers li span.current {
    background: var(--color-primary);
    color: white;
}

/* No Products */
.no-products {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
}

.no-products i {
    font-size: 5rem;
    color: var(--color-light);
}

.no-products h3 {
    font-size: 1.5rem;
    color: var(--color-dark);
    margin: 20px 0 10px;
}

.no-products p {
    color: #666;
    margin-bottom: 20px;
}

.no-products .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 30px;
    background: var(--color-primary);
    color: #EC802B;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 1024px) {
    .products-layout {
        grid-template-columns: 1fr;
    }
    
    .product-sidebar {
        display: none;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .products-toolbar {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
}

/* Active Filters Tags */
.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: var(--color-primary);
    color: white;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 500;
}

.filter-tag a {
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.filter-tag a:hover {
    opacity: 1;
}

/* Clear Filters */
.clear-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f8d7da;
    color: #721c24;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s;
}

.clear-filters:hover {
    background: #f5c6cb;
}

/* List View Mode */
.products-grid.list-view {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.products-grid.list-view .product-card {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.products-grid.list-view .product-image {
    height: 200px;
}

.products-grid.list-view .product-info {
    padding: 20px 20px 20px 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.products-grid.list-view .product-title {
    font-size: 1.2rem;
}
</style>

<script>
// Apply sort
function applySort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}

// View mode toggle
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const grid = document.getElementById('productsGrid');
        if (this.dataset.view === 'list') {
            grid.classList.add('list-view');
        } else {
            grid.classList.remove('list-view');
        }
        
        // Save preference
        localStorage.setItem('petshop_view_mode', this.dataset.view);
    });
});

// Restore view mode preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('petshop_view_mode');
    if (savedView === 'list') {
        document.querySelector('.view-btn[data-view="list"]').click();
    }
    
    // Quick add to cart
});

// ============================================================
// QUICK VIEW MODAL
// ============================================================
const QV_AJAX = window.PETSHOP_USER?.ajaxUrl || '/wp-admin/admin-ajax.php';

window.openQuickView = async function(productId) {
    const modal = document.getElementById('petshop-qv-modal');
    if (!modal) return;
    const bodyEl = document.getElementById('qv-body');
    if (bodyEl) bodyEl.innerHTML = '<div class="qv-loading"><i class="bi bi-arrow-repeat qv-spin"></i> Đang tải...</div>';

    // Hiện modal bằng style trực tiếp (tránh inline style override CSS class)
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    try {
        const fd = new FormData();
        fd.append('action', 'petshop_quick_view');
        fd.append('product_id', productId);
        const res  = await fetch(QV_AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const data = await res.json();
        if (!data.success) { closeQuickView(); return; }
        renderQuickView(data.data);
    } catch(err) {
        closeQuickView();
        console.error('Quick view error:', err);
    }
};

window.closeQuickView = function() {
    const modal = document.getElementById('petshop-qv-modal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    window._qvSelectedSize  = null;
    window._qvSelectedColor = null;
    window._qvVariants      = [];
    window._qvHasSizes      = false;
    window._qvHasColors     = false;
    window._qvData          = null;
};

function renderQuickView(p) {
    window._qvData      = p;
    window._qvVariants  = p.variants || [];
    window._qvHasSizes  = p.sizes && p.sizes.length > 0;
    window._qvHasColors = p.colors && p.colors.length > 0;
    window._qvSelectedSize  = null;
    window._qvSelectedColor = null;

    // Price display
    const basePrice = p.price_info?.is_on_sale ? p.price_info.sale : p.price_info?.original || p.price;
    let priceHtml = '';
    if (p.price_range && p.price_range.min !== p.price_range.max) {
        priceHtml = `<span class="qv-price">${fmtMoney(p.price_range.min)} – ${fmtMoney(p.price_range.max)}</span>`;
    } else if (p.price_info?.is_on_sale) {
        priceHtml = `<span class="qv-price">${fmtMoney(p.price_info.sale)}</span>
                     <span class="qv-price-orig">${fmtMoney(p.price_info.original)}</span>`;
    } else {
        priceHtml = `<span class="qv-price">${fmtMoney(basePrice)}</span>`;
    }

    // Sizes HTML
    let sizesHtml = '';
    if (window._qvHasSizes) {
        const sizeStocks = {};
        window._qvVariants.forEach(v => {
            if (v.size) sizeStocks[v.size] = (sizeStocks[v.size]||0) + v.stock;
        });
        sizesHtml = `<div class="qv-attr-row">
            <span class="qv-attr-label"><i class="bi bi-rulers"></i> Kích thước: <strong id="qv-sel-size"></strong></span>
            <div class="qv-opts" id="qv-size-opts">
                ${p.sizes.map(s => {
                    const stk = sizeStocks[s]||0;
                    return `<button type="button" class="qv-size-btn" data-size="${s}" onclick="qvSelectSize(this)"
                        ${stk<=0?'disabled':''} style="${stk<=0?'opacity:.4;cursor:not-allowed;':''}">${s}</button>`;
                }).join('')}
            </div>
        </div>`;
    }

    // Colors HTML
    let colorsHtml = '';
    if (window._qvHasColors) {
        colorsHtml = `<div class="qv-attr-row">
            <span class="qv-attr-label"><i class="bi bi-palette"></i> Màu sắc: <strong id="qv-sel-color"></strong></span>
            <div class="qv-opts" id="qv-color-opts">
                ${p.colors.map(c => {
                    const totalStk = window._qvVariants.filter(v=>v.color===c.name).reduce((a,v)=>a+v.stock,0);
                    return `<button type="button" class="qv-color-btn" data-color="${c.name}" data-hex="${c.hex||'#E8CCAD'}" onclick="qvSelectColor(this)"
                        ${totalStk<=0?'disabled':''} style="background:${c.hex||'#E8CCAD'};${totalStk<=0?'opacity:.4;cursor:not-allowed;':''}">
                        <span class="qv-color-dot" style="background:${c.hex||'#E8CCAD'};border:2px solid rgba(0,0,0,0.15);"></span>
                        ${c.name}
                    </button>`;
                }).join('')}
            </div>
        </div>`;
    }

    // Stock info
    const stockInfo = p.has_variants
        ? '<span id="qv-stock-info" style="font-size:0.85rem;color:#7A6B5A;"></span>'
        : `<span style="font-size:0.85rem;color:${p.stock>0?'#5cb85c':'#d9534f'};">${p.stock>0?'<i class="bi bi-check-circle-fill"></i> Còn hàng':'<i class="bi bi-x-circle-fill"></i> Hết hàng'}</span>`;

    document.getElementById('qv-body').innerHTML = `
    <div class="qv-grid">
        <div class="qv-img-wrap">
            <img id="qv-main-img" src="${p.thumb||''}" alt="${p.name}" style="width:100%;border-radius:12px;object-fit:cover;max-height:320px;">
        </div>
        <div class="qv-info">
            ${p.cat_name?`<span class="qv-cat">${p.cat_name}</span>`:''}
            <h2 class="qv-title">${p.name}</h2>
            <div class="qv-prices" id="qv-price-wrap">${priceHtml}</div>
            ${stockInfo}
            ${sizesHtml}${colorsHtml}
            <div id="qv-variant-warning" style="display:none;padding:8px 12px;background:#ffebee;border-radius:8px;color:#c62828;font-size:0.85rem;margin-top:8px;">
                <i class="bi bi-exclamation-circle"></i> Vui lòng chọn đầy đủ phân loại
            </div>
            <div class="qv-actions">
                <div class="qv-qty-row">
                    <button onclick="qvQty(-1)">-</button>
                    <input type="number" id="qv-qty" value="1" min="1" max="${p.stock||999}">
                    <button onclick="qvQty(1)">+</button>
                </div>
                <button class="qv-add-btn" onclick="qvAddToCart()">
                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                </button>
            </div>
            <a href="${p.url}" class="qv-detail-link"><i class="bi bi-eye"></i> Xem chi tiết sản phẩm</a>
        </div>
    </div>`;
}

function fmtMoney(n){return parseInt(n||0).toLocaleString('vi-VN')+'đ';}

function qvSelectSize(btn) {
    document.querySelectorAll('.qv-size-btn').forEach(b=>{b.classList.remove('active');});
    btn.classList.add('active');
    window._qvSelectedSize = btn.dataset.size;
    document.getElementById('qv-sel-size').textContent = btn.dataset.size;
    // Re-filter colors by size
    if (window._qvHasColors) qvUpdateColorsBySize(btn.dataset.size);
    qvUpdateVariantInfo();
}

function qvSelectColor(btn) {
    document.querySelectorAll('.qv-color-btn').forEach(b=>{b.classList.remove('active');});
    btn.classList.add('active');
    window._qvSelectedColor = btn.dataset.color;
    document.getElementById('qv-sel-color').textContent = btn.dataset.color;
    qvUpdateVariantInfo();
}

function qvUpdateColorsBySize(size) {
    const avail = {};
    window._qvVariants.filter(v=>v.size===size).forEach(v=>{ avail[v.color]=(avail[v.color]||0)+v.stock; });
    document.querySelectorAll('.qv-color-btn').forEach(btn=>{
        const s = avail[btn.dataset.color]||0;
        btn.disabled = s<=0; btn.style.opacity = s>0?'1':'0.4';
    });
    if (window._qvSelectedColor && (avail[window._qvSelectedColor]||0)<=0) {
        window._qvSelectedColor=null;
        document.getElementById('qv-sel-color').textContent='';
        document.querySelectorAll('.qv-color-btn').forEach(b=>b.classList.remove('active'));
    }
    qvUpdateVariantInfo();
}

function qvUpdateVariantInfo() {
    const ok = (!window._qvHasSizes||window._qvSelectedSize) && (!window._qvHasColors||window._qvSelectedColor);
    if (!ok) {
        const si = document.getElementById('qv-stock-info'); if(si) si.textContent='';
        return;
    }
    const v = window._qvVariants.find(x=>
        (!window._qvHasSizes||x.size===window._qvSelectedSize) &&
        (!window._qvHasColors||x.color===window._qvSelectedColor)
    );
    if (!v) return;
    // Đổi ảnh
    if (v.image_url) {
        const img = document.getElementById('qv-main-img');
        if (img) { img.style.opacity='0.5'; setTimeout(()=>{img.src=v.image_url;img.style.opacity='1';},200); }
    }
    // Đổi giá
    if (v.variant_price) {
        document.getElementById('qv-price-wrap').innerHTML = `<span class="qv-price">${fmtMoney(v.variant_price)}</span>`;
    }
    // Stock
    const maxQty = v.stock || 999;
    const qtyInput = document.getElementById('qv-qty');
    if (qtyInput) qtyInput.max = maxQty;
    const si = document.getElementById('qv-stock-info');
    if (si) si.innerHTML = v.stock>0
        ? `<i class="bi bi-check-circle-fill" style="color:#5cb85c;"></i> Còn ${v.stock} sản phẩm`
        : `<i class="bi bi-x-circle-fill" style="color:#d9534f;"></i> Hết hàng`;
    document.getElementById('qv-variant-warning').style.display='none';
}

function qvQty(delta) {
    const inp = document.getElementById('qv-qty');
    if (!inp) return;
    inp.value = Math.max(1, Math.min(parseInt(inp.max)||999, (parseInt(inp.value)||1)+delta));
}

function qvAddToCart() {
    if (typeof window.PETSHOP_USER !== 'undefined' && !window.PETSHOP_USER.isLoggedIn) {
        closeQuickView(); showLoginRequired(); return;
    }
    const p = window._qvData;
    if (!p) return;
    // Validate variants
    if ((window._qvHasSizes&&!window._qvSelectedSize)||(window._qvHasColors&&!window._qvSelectedColor)) {
        document.getElementById('qv-variant-warning').style.display='';
        return;
    }
    const variant = window._qvHasSizes||window._qvHasColors ? window._qvVariants.find(x=>
        (!window._qvHasSizes||x.size===window._qvSelectedSize)&&
        (!window._qvHasColors||x.color===window._qvSelectedColor)) : null;

    if (variant && variant.stock <= 0) {
        document.getElementById('qv-variant-warning').innerHTML='<i class="bi bi-x-circle"></i> Lựa chọn này đã hết hàng';
        document.getElementById('qv-variant-warning').style.display='';
        return;
    }

    const qty        = parseInt(document.getElementById('qv-qty').value) || 1;
    const finalPrice = variant?.variant_price || (p.price_info?.is_on_sale ? p.price_info.sale : p.price_info?.original || p.price);
    const label      = [window._qvSelectedSize, window._qvSelectedColor].filter(Boolean).join(' / ');

    const cartItem = {
        id:            String(p.id),
        name:          p.name,
        price:         finalPrice,
        originalPrice: p.price,
        image:         p.thumb,
        url:           p.url,
        sku:           variant?.sku || p.sku,
        category:      p.cat_name,
        quantity:      qty,
        stock:         variant?.stock || p.stock,
        variantId:     variant ? String(variant.id) : '',
        selectedSize:  window._qvSelectedSize || '',
        selectedColor: window._qvSelectedColor || '',
        variantLabel:  label,
    };

    const cartKey = window.getCartKey ? window.getCartKey() : 'petshop_cart_guest';
    let cart = JSON.parse(localStorage.getItem(cartKey)) || [];
    // Tìm item trùng cả id + variant
    const idx = cart.findIndex(i => i.id===cartItem.id && i.variantId===cartItem.variantId);
    if (idx > -1) cart[idx].quantity += qty;
    else cart.push(cartItem);
    localStorage.setItem(cartKey, JSON.stringify(cart));
    if (typeof window.updateGlobalCartCount==='function') window.updateGlobalCartCount();

    closeQuickView();
    showFavNotification(`Đã thêm "${p.name}"${label?' ('+label+')':''} vào giỏ hàng!`, true);
}

// Show login required modal
function showLoginRequired() {
    const existingModal = document.getElementById('loginRequiredModal');
    if (existingModal) existingModal.remove();
    
    const modal = document.createElement('div');
    modal.id = 'loginRequiredModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10001; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;';
    modal.innerHTML = `
        <div style="background: #fff; border-radius: 20px; padding: 40px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-person-lock" style="font-size: 2.5rem; color: #fff;"></i>
            </div>
            <h3 style="color: #5D4E37; margin-bottom: 15px; font-size: 1.3rem;">Vui lòng đăng nhập</h3>
            <p style="color: #7A6B5A; margin-bottom: 25px; line-height: 1.6;">Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="document.getElementById('loginRequiredModal').remove();" style="padding: 12px 25px; background: #f0f0f0; border: none; border-radius: 25px; color: #666; cursor: pointer; font-weight: 600;">Để sau</button>
                <a href="${window.PETSHOP_USER?.loginUrl || '<?php echo home_url('/tai-khoan/'); ?>'}" style="padding: 12px 25px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border: none; border-radius: 25px; color: #fff; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                </a>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
}

// Toggle Yêu thích
function toggleFavBtn(btn) {
    if (typeof window.PETSHOP_USER !== 'undefined' && !window.PETSHOP_USER.isLoggedIn) {
        showLoginRequired();
        return;
    }
    const productId = btn.getAttribute('data-product-id');
    const isFav = btn.getAttribute('data-is-favorited') === '1';
    const action = isFav ? 'remove' : 'add';
    btn.disabled = true;
    const ajaxUrl = window.PETSHOP_USER?.ajaxUrl || '/wp-admin/admin-ajax.php';
    fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=petshop_toggle_favorite&product_id=' + productId + '&action_favorite=' + action
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (action === 'add') {
                btn.setAttribute('data-is-favorited', '1');
                btn.classList.add('active');
                btn.style.background = '#FF6B6B';
                btn.style.color = '#fff';
                btn.innerHTML = '❤️';
                showFavNotification('Đã lưu vào yêu thích!', true);
            } else {
                btn.setAttribute('data-is-favorited', '0');
                btn.classList.remove('active');
                btn.style.background = '';
                btn.style.color = '';
                btn.innerHTML = '🤍';
                showFavNotification('Đã xóa khỏi yêu thích!', false);
            }
            btn.style.transform = 'scale(1.35)';
            setTimeout(() => { btn.style.transform = ''; }, 220);
        } else {
            alert(data.data ? data.data.message : 'Có lỗi xảy ra!');
        }
    })
    .catch(() => alert('Có lỗi kết nối!'))
    .finally(() => { btn.disabled = false; });
}

// Thông báo yêu thích
function showFavNotification(message, isAdd) {
    const existing = document.querySelector('.fav-notification');
    if (existing) existing.remove();
    const notification = document.createElement('div');
    notification.className = 'fav-notification';
    notification.innerHTML = `
        <i class="bi bi-${isAdd ? 'heart-fill' : 'heart'}" style="font-size:1.1rem;"></i>
        <span>${message}</span>
        <a href="<?php echo home_url('/tai-khoan/'); ?>#favorites" class="view-fav-link">Xem yêu thích</a>
    `;
    notification.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 15px 25px;
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(231,76,60,0.4);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInUp 0.3s ease;
        font-weight: 500;
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOutDown 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Quick notification
function showQuickNotification(message) {
    const existing = document.querySelector('.quick-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'quick-notification';
    notification.innerHTML = `
        <i class="bi bi-check-circle-fill"></i>
        <span>${message}</span>
        <a href="<?php echo home_url('/gio-hang/'); ?>" class="view-cart-link">Xem giỏ</a>
    `;
    notification.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 15px 25px;
        background: linear-gradient(135deg, #66BCB4, #7ECEC6);
        color: white;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(102, 188, 180, 0.4);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInUp 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        notification.style.animation = 'slideOutDown 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<style>
@keyframes slideInUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes slideOutDown {
    from { transform: translateY(0); opacity: 1; }
    to { transform: translateY(100%); opacity: 0; }
}
.quick-notification .view-cart-link,
.fav-notification .view-fav-link {
    color: white;
    text-decoration: underline;
    margin-left: 10px;
}
.add-to-cart-quick.added {
    background: #66BCB4 !important;
}
</style>


<!-- Quick View Modal -->
<div id="petshop-qv-modal">
    <div class="qv-backdrop" onclick="closeQuickView()"></div>
    <div class="qv-box">
        <button class="qv-close" onclick="closeQuickView()"><i class="bi bi-x-lg"></i></button>
        <div class="qv-body" id="qv-body"></div>
    </div>
</div>

<style>
/* Quick View Modal */
#petshop-qv-modal { position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:16px; }
.qv-backdrop { position:fixed;inset:0;background:rgba(0,0,0,0.55);backdrop-filter:blur(3px); }
.qv-box { position:relative;background:#fff;border-radius:20px;width:100%;max-width:820px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.25);animation:qvIn .3s ease; }
@keyframes qvIn{from{opacity:0;transform:scale(.95) translateY(20px)}to{opacity:1;transform:none}}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.qv-close { position:absolute;top:14px;right:14px;z-index:2;background:#f5f5f5;border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#666;transition:all .2s; }
.qv-close:hover{background:#EC802B;color:#fff;}
.qv-loading{text-align:center;padding:60px;color:#7A6B5A;font-size:1rem;}
.qv-spin{animation:spin 1s linear infinite;display:inline-block;}
.qv-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;}
.qv-img-wrap{padding:24px;border-right:1px solid #f5f0ea;}
.qv-img-wrap img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;transition:opacity .2s;}
.qv-info{padding:24px;display:flex;flex-direction:column;gap:10px;}
.qv-cat{font-size:.78rem;color:#7A6B5A;text-transform:uppercase;letter-spacing:.5px;font-weight:600;}
.qv-title{font-size:1.2rem;font-weight:800;color:#5D4E37;margin:0;line-height:1.3;}
.qv-prices{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.qv-price{font-size:1.5rem;font-weight:800;color:#EC802B;}
.qv-price-orig{font-size:1rem;color:#aaa;text-decoration:line-through;}
.qv-attr-row{display:flex;flex-direction:column;gap:8px;}
.qv-attr-label{font-weight:700;color:#5D4E37;font-size:.9rem;display:flex;align-items:center;gap:6px;}
.qv-opts{display:flex;flex-wrap:wrap;gap:8px;}
.qv-size-btn{padding:7px 16px;border:2px solid #E8CCAD;border-radius:8px;background:#fff;color:#5D4E37;font-weight:700;cursor:pointer;font-size:.88rem;transition:all .2s;}
.qv-size-btn:hover,.qv-size-btn.active{border-color:#EC802B;background:#EC802B;color:#fff;}
.qv-color-btn{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border:2px solid #E8CCAD;border-radius:25px;background:#fff;cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;}
.qv-color-btn.active{border-color:#EC802B;background:#FDF8F3;}
.qv-color-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0;}
.qv-actions{display:flex;gap:10px;align-items:center;margin-top:4px;}
.qv-qty-row{display:flex;align-items:center;border:2px solid #E8CCAD;border-radius:10px;overflow:hidden;}
.qv-qty-row button{width:36px;height:38px;border:none;background:#FDF8F3;cursor:pointer;font-size:1.1rem;font-weight:700;color:#5D4E37;}
.qv-qty-row input{width:48px;height:38px;border:none;text-align:center;font-size:1rem;font-weight:600;color:#5D4E37;}
.qv-add-btn{flex:1;height:42px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;}
.qv-add-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(236,128,43,.4);}
.qv-detail-link{color:#7A6B5A;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:5px;margin-top:auto;}
.qv-detail-link:hover{color:#EC802B;}
@media(max-width:640px){.qv-grid{grid-template-columns:1fr;}.qv-img-wrap{border-right:none;border-bottom:1px solid #f5f0ea;padding:16px;}.qv-info{padding:16px;}.qv-box{max-height:95vh;}}
</style>
<?php get_footer(); ?>