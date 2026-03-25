<?php
/**
 * Template Name: Sản phẩm yêu thích
 * 
 * @package PetShop
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/dang-nhap/?redirect_to=' . urlencode(home_url('/san-pham-yeu-thich/'))));
    exit;
}

$user_id = get_current_user_id();
$favorites = get_user_meta($user_id, 'petshop_favorites', true);
if (!is_array($favorites)) $favorites = array();

// Lọc, phân loại
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'date';
$order_dir = isset($_GET['order_dir']) ? sanitize_text_field($_GET['order_dir']) : 'DESC';

$args = array(
    'post_type' => 'product',
    'post__in' => $favorites,
    'orderby' => $order,
    'order' => $order_dir,
    'posts_per_page' => 12,
);
if ($category) {
    $args['tax_query'] = array([
        'taxonomy' => 'product_category',
        'field' => 'slug',
        'terms' => $category,
    ]);
}

$query = new WP_Query($args);
get_header();
?>
<div class="page-header" style="background: linear-gradient(135deg, #5D4E37 0%, #7A6B5A 100%);">
    <div class="container">
        <h1 style="color: #fff;"><i class="bi bi-heart"></i> Sản phẩm yêu thích</h1>
    </div>
</div>
<section class="favorite-section" style="padding: 60px 0;">
    <div class="container">
        <form method="get" class="favorite-filter" style="margin-bottom: 30px; display: flex; gap: 20px;">
            <select name="category">
                <option value="">Tất cả danh mục</option>
                <?php
                $cats = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false));
                foreach ($cats as $cat) {
                    echo '<option value="' . esc_attr($cat->slug) . '"' . selected($category, $cat->slug, false) . '>' . esc_html($cat->name) . '</option>';
                }
                ?>
            </select>
            <select name="order">
                <option value="date" <?php selected($order, 'date'); ?>>Mới nhất</option>
                <option value="title" <?php selected($order, 'title'); ?>>Tên A-Z</option>
            </select>
            <select name="order_dir">
                <option value="DESC" <?php selected($order_dir, 'DESC'); ?>>Giảm dần</option>
                <option value="ASC" <?php selected($order_dir, 'ASC'); ?>>Tăng dần</option>
            </select>
            <button type="submit" class="btn">Lọc</button>
        </form>
        <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px;">
            <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                <div class="product-card" style="background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 18px; position: relative;">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) {
                            the_post_thumbnail('medium', ['style' => 'width:100%;height:180px;object-fit:cover;border-radius:12px;']);
                        } ?>
                        <h3 style="font-size: 1.1rem; margin: 12px 0 6px; color: #5D4E37; font-weight: 700; "><?php the_title(); ?></h3>
                    </a>
                    <div style="color: #EC802B; font-weight: 600; margin-bottom: 8px;">
                        <?php echo get_post_meta(get_the_ID(), 'product_sale_price', true) ?: get_post_meta(get_the_ID(), 'product_price', true); ?> đ
                    </div>
                    <button class="btn"
                            title="Yêu thích"
                            data-product-id="<?php the_ID(); ?>"
                            data-is-favorited="1"
                            onclick="removeFavCard(this)"
                            style="background:#f44336;color:#fff;border:none;cursor:pointer;width:100%;">
                        ❤️ Bỏ yêu thích
                    </button>
                </div>
            <?php endwhile; else: ?>
                <p>Bạn chưa có sản phẩm yêu thích nào.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<script>
function removeFavCard(btn) {
    const productId = btn.getAttribute('data-product-id');
    const card = btn.closest('.product-card');
    btn.disabled = true;
    fetch((typeof petshopData !== 'undefined' ? petshopData.ajaxUrl : '/wp-admin/admin-ajax.php'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=petshop_toggle_favorite&product_id=' + productId + '&action_favorite=remove'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        } else {
            btn.disabled = false;
            alert(data.data ? data.data.message : 'Có lỗi xảy ra!');
        }
    })
    .catch(() => { btn.disabled = false; });
}
</script>
<?php get_footer(); ?>