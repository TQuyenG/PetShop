<?php
/**
 * Template: Chi tiết sản phẩm
 * 
 * @package PetShop
 */



get_header();

// Xử lý thêm/xóa sản phẩm yêu thích (backend, không đổi giao diện)
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
        wp_redirect(get_permalink());
        exit;
    }
}

// Lấy thông tin sản phẩm
$price = get_post_meta(get_the_ID(), 'product_price', true);
$sale_price = get_post_meta(get_the_ID(), 'product_sale_price', true);
$sku = get_post_meta(get_the_ID(), 'product_sku', true);
$stock = get_post_meta(get_the_ID(), 'product_stock', true);
// Variants
$has_variants     = function_exists('petshop_has_variants') && petshop_has_variants(get_the_ID());
$product_variants = $has_variants ? petshop_get_product_variants(get_the_ID()) : array();
$variant_sizes    = $has_variants ? petshop_get_product_sizes(get_the_ID())    : array();
$variant_colors   = $has_variants ? petshop_get_product_colors(get_the_ID())   : array();

// Lấy danh mục sản phẩm
$product_cats = get_the_terms(get_the_ID(), 'product_category');
?>

<main class="main-content">
    <!-- Breadcrumb -->
    <section style="background: var(--color-light); padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
        <div class="container">
            <nav style="font-size: 0.9rem;">
                <a href="<?php echo home_url(); ?>" style="color: var(--color-dark); text-decoration: none;">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
                <span style="margin: 0 10px; color: #999;">/</span>
                <a href="<?php echo get_post_type_archive_link('product'); ?>" style="color: var(--color-dark); text-decoration: none;">Sản phẩm</a>
                <?php if ($product_cats && !is_wp_error($product_cats)) : ?>
                    <span style="margin: 0 10px; color: #999;">/</span>
                    <a href="<?php echo get_term_link($product_cats[0]); ?>" style="color: var(--color-dark); text-decoration: none;">
                        <?php echo esc_html($product_cats[0]->name); ?>
                    </a>
                <?php endif; ?>
                <span style="margin: 0 10px; color: #999;">/</span>
                <span style="color: var(--color-primary);"><?php the_title(); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Detail -->
    <section style="padding: 60px 0;">
        <div class="container">
            <div class="product-detail" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">
                
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image" style="position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                        <?php if (has_post_thumbnail()) : ?>
                            <img src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>" 
                                 style="width: 100%; height: 500px; object-fit: cover;" id="mainImage">
                        <?php else : ?>
                            <div style="width: 100%; height: 500px; background: linear-gradient(135deg, var(--color-light) 0%, var(--color-accent) 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-box-seam" style="font-size: 6rem; color: var(--color-primary); opacity: 0.5;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Sử dụng helper function để kiểm tra giảm giá còn hiệu lực
                        $price_info = petshop_get_display_price(get_the_ID());
                        if ($price_info['is_on_sale'] && $price_info['discount_percent']) : 
                        ?>
                        <span style="position: absolute; top: 20px; left: 20px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 8px 16px; border-radius: 25px; font-weight: 700; box-shadow: 0 4px 15px rgba(231,76,60,0.4);">
                            <i class="bi bi-lightning-fill"></i> -<?php echo $price_info['discount_percent']; ?>%
                        </span>
                        <?php endif; ?>
                        
                        <button style="position: absolute; top: 20px; right: 20px; width: 45px; height: 45px; background: white; border: none; border-radius: 50%; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" title="Yêu thích">
                            <i class="bi bi-heart" style="font-size: 1.2rem; color: var(--color-primary);"></i>
                        </button>
                    </div>
                    
                    <!-- Thumbnails -->
                    <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap;">
                        <?php 
                        // Lấy gallery images từ admin
                        $gallery_images = petshop_get_product_gallery(get_the_ID(), 'thumbnail');
                        
                        if (!empty($gallery_images)) :
                            foreach ($gallery_images as $index => $img) :
                                $full_url = wp_get_attachment_image_url($img['id'], 'large');
                        ?>
                        <div style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; cursor: pointer; border: 3px solid <?php echo $index === 0 ? 'var(--color-primary)' : 'transparent'; ?>; transition: border-color 0.3s;" class="thumb-item" onclick="changeMainImage('<?php echo esc_url($full_url); ?>', this)">
                            <img src="<?php echo esc_url($img['url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php 
                            endforeach;
                        else :
                            // Hiển thị ảnh đại diện như thumbnail
                            if (has_post_thumbnail()) :
                        ?>
                        <div style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; cursor: pointer; border: 3px solid var(--color-primary);" class="thumb-item">
                            <img src="<?php the_post_thumbnail_url('thumbnail'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php 
                            endif;
                        endif; 
                        ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="product-info-detail">
                    <?php if ($product_cats && !is_wp_error($product_cats)) : ?>
                    <a href="<?php echo get_term_link($product_cats[0]); ?>" style="display: inline-block; background: var(--color-light); color: var(--color-secondary); padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-bottom: 15px;">
                        <i class="bi bi-tag"></i> <?php echo esc_html($product_cats[0]->name); ?>
                    </a>
                    <?php endif; ?>
                    
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--color-dark); margin-bottom: 15px; line-height: 1.3;">
                        <?php the_title(); ?>
                    </h1>
                    
                    <!-- Rating - Real data from database -->
                    <?php 
                    // Lấy thông tin đánh giá thực từ database
                    $rating_data = function_exists('petshop_get_average_rating') ? petshop_get_average_rating(get_the_ID()) : array('average' => 0, 'count' => 0);
                    $average_rating = $rating_data['average'];
                    $review_count = $rating_data['count'];
                    $sold_count = function_exists('petshop_get_sold_count') ? petshop_get_sold_count(get_the_ID()) : 0;
                    ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 3px;">
                            <?php for ($i = 1; $i <= 5; $i++) : 
                                $star_color = 'var(--color-accent)';
                                if ($i > $average_rating) {
                                    if ($i - $average_rating < 1 && $average_rating > 0) {
                                        $star_color = 'var(--color-accent)'; // half star - simplified to full
                                    } else {
                                        $star_color = '#ddd';
                                    }
                                }
                            ?>
                                <i class="bi bi-star-fill" style="color: <?php echo $star_color; ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if ($review_count > 0) : ?>
                        <span style="color: #666;"><?php echo $average_rating; ?> (<?php echo $review_count; ?> đánh giá)</span>
                        <?php else : ?>
                        <span style="color: #999;">Chưa có đánh giá</span>
                        <?php endif; ?>
                        <span style="color: #999;">|</span>
                        <span style="color: var(--color-secondary);"><i class="bi bi-bag-check"></i> Đã bán <?php echo number_format($sold_count); ?></span>
                    </div>
                    
                    <!-- Price -->
                    <div style="background: var(--color-bg); padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;">
                        <?php if ($sale_price && $sale_price < $price) : ?>
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <span style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">
                                    <?php echo number_format($sale_price, 0, ',', '.'); ?>đ
                                </span>
                                <span style="font-size: 1.2rem; color: #999; text-decoration: line-through;">
                                    <?php echo number_format($price, 0, ',', '.'); ?>đ
                                </span>
                                <span style="background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    Tiết kiệm <?php echo number_format($price - $sale_price, 0, ',', '.'); ?>đ
                                </span>
                            </div>
                            
                            <?php 
                            // Kiểm tra xem có thời hạn giảm giá không
                            $discount_has_expiry = get_post_meta(get_the_ID(), 'discount_has_expiry', true);
                            $discount_expiry_date = get_post_meta(get_the_ID(), 'discount_expiry_date', true);
                            $discount_expiry_time = get_post_meta(get_the_ID(), 'discount_expiry_time', true);
                            
                            if ($discount_has_expiry === '1' && !empty($discount_expiry_date)) :
                                $time_str = !empty($discount_expiry_time) ? $discount_expiry_time : '23:59';
                                $expiry_timestamp = strtotime($discount_expiry_date . ' ' . $time_str . ':00');
                                $now = current_time('timestamp');
                                if ($expiry_timestamp > $now) :
                            ?>
                            <div id="discount-countdown" style="margin-top: 15px; padding: 12px 15px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border-radius: 10px; color: white;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span style="font-size: 0.9rem;"><i class="bi bi-clock-history"></i> Khuyến mãi kết thúc sau:</span>
                                    <div id="countdown-timer" style="display: flex; gap: 8px; font-weight: 700;" data-expiry="<?php echo $expiry_timestamp; ?>">
                                        <span class="countdown-item" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                                            <span id="countdown-days">00</span>d
                                        </span>
                                        <span class="countdown-item" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                                            <span id="countdown-hours">00</span>h
                                        </span>
                                        <span class="countdown-item" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                                            <span id="countdown-minutes">00</span>m
                                        </span>
                                        <span class="countdown-item" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                                            <span id="countdown-seconds">00</span>s
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endif;
                            endif; 
                            ?>
                            
                        <?php elseif ($price) : ?>
                            <span style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">
                                <?php echo number_format($price, 0, ',', '.'); ?>đ
                            </span>
                        <?php else : ?>
                            <span style="font-size: 1.5rem; color: var(--color-primary); font-weight: 600;">Liên hệ để biết giá</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Short Description -->
                    <?php if (has_excerpt()) : ?>
                    <div style="color: #666; line-height: 1.8; margin-bottom: 25px;">
                        <?php the_excerpt(); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Product Meta -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 30px;">
                        <?php if ($sku) : ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-upc-scan" style="color: var(--color-primary);"></i>
                            <span style="color: #666;">SKU: <strong style="color: var(--color-dark);"><?php echo esc_html($sku); ?></strong></span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php 
                            $low_stock_threshold = get_post_meta(get_the_ID(), 'low_stock_threshold', true);
                            $low_stock_threshold = $low_stock_threshold !== '' ? intval($low_stock_threshold) : 5;
                            
                            if ($stock !== '' && $stock !== false) :
                                $stock_int = intval($stock);
                                if ($stock_int <= 0) : ?>
                                <i class="bi bi-x-circle-fill" style="color: #e74c3c;"></i>
                                <span style="color: #e74c3c; font-weight: 600;">Hết hàng</span>
                            <?php elseif ($stock_int <= $low_stock_threshold) : ?>
                                <i class="bi bi-exclamation-circle-fill" style="color: #ff9800;"></i>
                                <span style="color: #ff9800; font-weight: 600;">Chỉ còn <?php echo $stock_int; ?> sản phẩm</span>
                            <?php else : ?>
                                <i class="bi bi-check-circle-fill" style="color: var(--color-secondary);"></i>
                                <span style="color: var(--color-secondary); font-weight: 600;">Còn <?php echo $stock_int; ?> sản phẩm</span>
                            <?php endif; ?>
                            <?php else : ?>
                                <i class="bi bi-check-circle-fill" style="color: var(--color-secondary);"></i>
                                <span style="color: var(--color-secondary); font-weight: 600;">Còn hàng</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($product_cats && !is_wp_error($product_cats)) : ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-folder" style="color: var(--color-primary);"></i>
                            <span style="color: #666;">Danh mục: 
                                <?php 
                                $cat_links = array();
                                foreach ($product_cats as $cat) {
                                    $cat_links[] = '<a href="' . get_term_link($cat) . '" style="color: var(--color-dark); font-weight: 600; text-decoration: none;">' . esc_html($cat->name) . '</a>';
                                }
                                echo implode(', ', $cat_links);
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-truck" style="color: var(--color-primary);"></i>
                            <span style="color: #666;">Giao hàng: <strong style="color: var(--color-dark);">Miễn phí</strong></span>
                        </div>
                    </div>
                    
                    <!-- Quantity & Add to Cart -->
                    <?php
                    $is_out_of_stock = !$has_variants && ($stock !== '' && $stock !== false && intval($stock) <= 0);
                    $max_qty         = ($stock !== '' && $stock !== false) ? intval($stock) : 999;

                    if ($has_variants) :
                        // Build variants JSON for JS
                        $variants_js = array();
                        foreach ($product_variants as $pv) {
                            $img_url = '';
                            if (!empty($pv['image_id'])) {
                                $img_url = wp_get_attachment_image_url($pv['image_id'], 'large') ?: '';
                            }
                            $v_price = (isset($pv['variant_price']) && $pv['variant_price'] !== null && $pv['variant_price'] !== '')
                                ? intval($pv['variant_price']) : null;
                            $variants_js[] = array(
                                'id'            => intval($pv['id']),
                                'size'          => $pv['size'],
                                'color'         => $pv['color'],
                                'color_hex'     => $pv['color_hex'],
                                'stock'         => intval($pv['stock']),
                                'variant_price' => $v_price,
                                'sku'           => $pv['sku'],
                                'image_url'     => $img_url,
                            );
                        }
                        $default_img = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: '';
                    ?>
                    <?php
                    // Khoảng giá
                    $vp_all = array_filter(
                        array_column($product_variants, 'variant_price'),
                        fn($p) => $p !== null && $p !== '' && intval($p) > 0
                    );
                    $vp_min = !empty($vp_all) ? min(array_map('intval', $vp_all)) : null;
                    $vp_max = !empty($vp_all) ? max(array_map('intval', $vp_all)) : null;
                    ?>
                    <?php if ($vp_min !== null) : ?>
                    <div id="sp-price-range-wrap" style="margin-bottom:16px;padding:14px 18px;background:var(--color-bg);border-radius:12px;">
                        <?php if ($vp_min !== $vp_max) : ?>
                        <div style="font-size:0.88rem;color:#7A6B5A;margin-bottom:4px;">Khoảng giá:</div>
                        <span style="font-size:1.5rem;font-weight:800;color:var(--color-primary);"><?php echo number_format($vp_min,0,',','.'); ?>đ</span>
                        <span style="color:#bbb;margin:0 6px;">–</span>
                        <span style="font-size:1.5rem;font-weight:800;color:var(--color-primary);"><?php echo number_format($vp_max,0,',','.'); ?>đ</span>
                        <?php endif; ?>
                        <div id="sp-variant-price-row" style="display:none;margin-top:6px;">
                            <i class="bi bi-tag" style="color:var(--color-primary);"></i>
                            <span style="font-size:0.88rem;color:#7A6B5A;">Giá phân loại này: </span>
                            <strong id="sp-variant-price-val" style="color:var(--color-primary);font-size:1.1rem;"></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- PHÂN LOẠI SẢN PHẨM -->
                    <div id="variant-selector" style="margin-bottom:25px;">
                        <?php if (!empty($variant_sizes)) : ?>
                        <div style="margin-bottom:16px;">
                            <div style="font-weight:700;color:var(--color-dark);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-rulers" style="color:var(--color-primary);"></i>
                                Kích thước:
                                <span id="selected-size-label" style="color:var(--color-primary);font-weight:600;"></span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <?php foreach ($variant_sizes as $sz) :
                                    $sz_stock = array_sum(array_column(array_filter($product_variants, fn($v) => $v['size']==$sz), 'stock'));
                                ?>
                                <button type="button" class="variant-size-btn"
                                        data-size="<?php echo esc_attr($sz); ?>"
                                        data-stock="<?php echo $sz_stock; ?>"
                                        onclick="spSelectSize(this)"
                                        <?php echo $sz_stock <= 0 ? 'disabled' : ''; ?>
                                        style="position:relative;min-width:48px;padding:8px 16px;border:2px solid <?php echo $sz_stock>0?'#E8CCAD':'#e0e0e0'; ?>;border-radius:8px;background:<?php echo $sz_stock>0?'#fff':'#f5f5f5'; ?>;color:<?php echo $sz_stock>0?'var(--color-dark)':'#ccc'; ?>;font-weight:600;cursor:<?php echo $sz_stock>0?'pointer':'not-allowed'; ?>;font-size:0.9rem;transition:all 0.2s;">
                                    <?php echo esc_html($sz); ?>
                                    <?php if ($sz_stock <= 0) : ?>
                                    <span style="position:absolute;top:50%;left:0;width:100%;height:1px;background:#ccc;transform:rotate(-10deg);pointer-events:none;"></span>
                                    <?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($variant_colors)) :
                            $seen_c = []; $unique_colors = [];
                            foreach ($variant_colors as $vc) {
                                if (!isset($seen_c[$vc['color']])) {
                                    $seen_c[$vc['color']] = true;
                                    $unique_colors[] = $vc;
                                }
                            }
                        ?>
                        <div style="margin-bottom:16px;">
                            <div style="font-weight:700;color:var(--color-dark);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-palette" style="color:var(--color-primary);"></i>
                                Màu sắc:
                                <span id="selected-color-label" style="color:var(--color-primary);font-weight:600;"></span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;" id="color-options">
                                <?php foreach ($unique_colors as $vc) :
                                    $vc_stock = intval($vc['total_stock']);
                                    $hex = $vc['color_hex'] ?: '#E8CCAD';
                                ?>
                                <button type="button" class="variant-color-btn"
                                        data-color="<?php echo esc_attr($vc['color']); ?>"
                                        data-hex="<?php echo esc_attr($hex); ?>"
                                        data-stock="<?php echo $vc_stock; ?>"
                                        onclick="spSelectColor(this)"
                                        <?php echo $vc_stock <= 0 ? 'disabled' : ''; ?>
                                        style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border:2px solid #E8CCAD;border-radius:25px;background:#fff;cursor:<?php echo $vc_stock>0?'pointer':'not-allowed'; ?>;font-size:0.88rem;font-weight:600;color:<?php echo $vc_stock>0?'var(--color-dark)':'#bbb'; ?>;transition:all 0.2s;opacity:<?php echo $vc_stock>0?'1':'0.45'; ?>;">
                                    <span style="width:16px;height:16px;border-radius:50%;background:<?php echo $hex; ?>;border:2px solid rgba(0,0,0,0.12);flex-shrink:0;"></span>
                                    <?php echo esc_html($vc['color']); ?>
                                    <?php if ($vc_stock <= 0) : ?><span style="font-size:0.75rem;">(Hết)</span><?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Thông tin variant đang chọn -->
                        <div id="variant-info" style="display:none;padding:12px 16px;background:var(--color-bg);border-radius:10px;font-size:0.9rem;"></div>
                        <div id="variant-warning" style="display:none;padding:10px 14px;background:#ffebee;border-radius:8px;color:#c62828;font-size:0.88rem;margin-top:8px;">
                            <i class="bi bi-exclamation-circle"></i> Vui lòng chọn đầy đủ phân loại trước khi mua.
                        </div>

                        <input type="hidden" id="variantData"       value='<?php echo esc_attr(json_encode($variants_js)); ?>'>
                        <input type="hidden" id="defaultProductImg" value="<?php echo esc_attr($default_img); ?>">
                        <input type="hidden" id="selectedVariantId" value="">
                        <input type="hidden" id="hasSizes"  value="<?php echo !empty($variant_sizes)?'1':'0'; ?>">
                        <input type="hidden" id="hasColors" value="<?php echo !empty($variant_colors)?'1':'0'; ?>">
                    </div>
                    <?php endif; ?>

                    <?php if ($is_out_of_stock) : ?>
                    <div style="background: #ffebee; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 25px;">
                        <i class="bi bi-box-seam" style="font-size: 2rem; color: #c62828; display: block; margin-bottom: 10px;"></i>
                        <p style="color: #c62828; font-weight: 600; margin: 0;">Sản phẩm hiện đã hết hàng</p>
                        <p style="color: #666; font-size: 0.9rem; margin: 10px 0 0 0;">Vui lòng quay lại sau hoặc liên hệ shop để được thông báo khi có hàng.</p>
                    </div>
                    <?php else : ?>
                    
                    <!-- COMBO OFFER SECTION -->
                    <?php 
                    $combo_offer = function_exists('petshop_get_product_combo') ? petshop_get_product_combo(get_the_ID()) : null;
                    if ($combo_offer && $combo_offer['coupon'] && count($combo_offer['products']) > 0): 
                        $combo_coupon = $combo_offer['coupon'];
                        $combo_products = $combo_offer['products'];
                        $combo_mode = isset($combo_offer['combo_mode']) ? $combo_offer['combo_mode'] : 'any_triggers';
                        
                        // Xác định text hiển thị dựa trên mode
                        if ($combo_mode === 'any_triggers') {
                            $combo_title = 'ĐỀ XUẤT MUA COMBO';
                            $combo_desc = 'Mua thêm các sản phẩm sau để nhận ưu đãi combo:';
                        } else {
                            $combo_title = 'MUA COMBO GIẢM NGAY!';
                            $combo_desc = 'Mua kèm các sản phẩm sau để được giảm giá:';
                        }
                    ?>
                    <div id="combo-offer-section" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border: 2px solid #ff9800; border-radius: 15px; padding: 20px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-fire" style="font-size: 1.5rem; color: #e65100;"></i>
                                <span style="font-weight: 700; color: #e65100; font-size: 1.1rem;"><i class="bi bi-fire"></i> <?php echo $combo_title; ?></span>
                            </div>
                            <span style="background: #e65100; color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: 700;">
                                <?php if ($combo_coupon->discount_type === 'percent'): ?>
                                    -<?php echo intval($combo_coupon->discount_value); ?>%
                                <?php else: ?>
                                    -<?php echo number_format($combo_coupon->discount_value); ?>đ
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <p style="color: #5d4037; margin: 0 0 15px; font-size: 0.95rem;">
                            <?php echo $combo_desc; ?>
                        </p>
                        
                        <div class="combo-products" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px;">
                            <?php foreach ($combo_products as $cp): 
                                $cp_thumb = get_the_post_thumbnail_url($cp->combo_product_id, 'thumbnail') ?: 'https://via.placeholder.com/60';
                                $cp_price = $cp->sale_price ?: $cp->price;
                            ?>
                            <a href="<?php echo get_permalink($cp->combo_product_id); ?>" class="combo-product-item" style="display: flex; align-items: center; gap: 10px; background: #fff; padding: 10px 15px; border-radius: 10px; text-decoration: none; color: inherit; flex: 1; min-width: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s;">
                                <img src="<?php echo esc_url($cp_thumb); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <div style="flex: 1;">
                                    <strong style="display: block; font-size: 0.9rem; color: #333; line-height: 1.3;"><?php echo esc_html($cp->post_title); ?></strong>
                                    <span style="color: var(--color-primary); font-weight: 600;"><?php echo number_format($cp_price); ?>đ</span>
                                </div>
                                <i class="bi bi-plus-circle-fill" style="color: #ff9800; font-size: 1.2rem;"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" onclick="addComboToCart()" class="combo-add-btn" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                            <i class="bi bi-cart-plus"></i> Thêm cả combo vào giỏ hàng
                        </button>
                        
                        <input type="hidden" id="comboProducts" value='<?php echo json_encode(array_map(function($p) { return $p->combo_product_id; }, $combo_products)); ?>'>
                        <input type="hidden" id="comboCouponCode" value="<?php echo esc_attr($combo_coupon->code); ?>">
                    </div>
                    <style>
                    .combo-product-item:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
                    .combo-add-btn:hover { transform: scale(1.02); box-shadow: 0 5px 20px rgba(255,152,0,0.4); }
                    </style>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; border: 2px solid var(--color-light); border-radius: 12px; overflow: hidden;">
                            <button class="qty-btn" onclick="changeQty(-1)" style="width: 45px; height: 45px; border: none; background: var(--color-bg); cursor: pointer; font-size: 1.2rem; color: var(--color-dark);">-</button>
                            <input type="number" id="productQty" value="1" min="1" max="<?php echo $max_qty; ?>" style="width: 60px; height: 45px; border: none; text-align: center; font-size: 1.1rem; font-weight: 600;">
                            <button class="qty-btn" onclick="changeQty(1)" style="width: 45px; height: 45px; border: none; background: var(--color-bg); cursor: pointer; font-size: 1.2rem; color: var(--color-dark);">+</button>
                        </div>
                        
                        <button class="sp-add-to-cart-btn" onclick="addToCart(false)">
                            <i class="bi bi-bag-plus"></i> Thêm vào giỏ hàng
                        </button>
                    </div>
                    
                    <!-- Buy Now -->
                    <button onclick="addToCart(true)" class="sp-buy-now-btn" style="width: 100%; border: none; cursor: pointer;">
                        <i class="bi bi-lightning-fill"></i> Mua ngay
                    </button>
                    <?php endif; ?>
                    
                    <!-- Hidden Product Data -->
                    <input type="hidden" id="productId" value="<?php echo get_the_ID(); ?>">
                    <input type="hidden" id="productName" value="<?php echo esc_attr(get_the_title()); ?>">
                    <input type="hidden" id="productPrice" value="<?php echo $sale_price ? $sale_price : $price; ?>">
                    <input type="hidden" id="productOriginalPrice" value="<?php echo $price; ?>">
                    <input type="hidden" id="productImage" value="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'petshop-product'); ?>">
                    <input type="hidden" id="productUrl" value="<?php the_permalink(); ?>">
                    <input type="hidden" id="productSku" value="<?php echo $sku; ?>">
                    <input type="hidden" id="productStock" value="<?php echo $max_qty; ?>">
                    <?php 
                    $cat_name = '';
                    if ($product_cats && !is_wp_error($product_cats)) {
                        $cat_name = $product_cats[0]->name;
                    }
                    ?>
                    <input type="hidden" id="productCategory" value="<?php echo esc_attr($cat_name); ?>">
                    
                    <!-- Trust Badges -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding: 20px; background: var(--color-bg); border-radius: 15px;">
                        <div style="text-align: center;">
                            <i class="bi bi-shield-check" style="font-size: 1.5rem; color: var(--color-secondary);"></i>
                            <p style="margin: 5px 0 0; font-size: 0.85rem; color: #666;">Bảo hành<br><strong>30 ngày</strong></p>
                        </div>
                        <div style="text-align: center;">
                            <i class="bi bi-arrow-repeat" style="font-size: 1.5rem; color: var(--color-primary);"></i>
                            <p style="margin: 5px 0 0; font-size: 0.85rem; color: #666;">Đổi trả<br><strong>7 ngày</strong></p>
                        </div>
                        <div style="text-align: center;">
                            <i class="bi bi-truck" style="font-size: 1.5rem; color: var(--color-accent);"></i>
                            <p style="margin: 5px 0 0; font-size: 0.85rem; color: #666;">Giao hàng<br><strong>Toàn quốc</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Tabs -->
    <section style="padding: 0 0 60px;">
        <div class="container">
            <div style="background: white; border-radius: 20px; box-shadow: 0 5px 30px rgba(0,0,0,0.05); overflow: hidden;">
                <!-- Tabs Header -->
                <div style="display: flex; border-bottom: 1px solid var(--color-light);">
                    <button class="sp-tab-btn active" data-tab="description" style="flex: 1; padding: 20px; background: rgba(236, 128, 43, 0.08); border: none; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--color-primary); border-bottom: 3px solid var(--color-primary);">
                        <i class="bi bi-file-text"></i> Mô tả sản phẩm
                    </button>
                    <button class="sp-tab-btn" data-tab="specs" style="flex: 1; padding: 20px; background: transparent; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; color: #666; border-bottom: 3px solid transparent;">
                        <i class="bi bi-list-check"></i> Thông số
                    </button>
                    <button class="sp-tab-btn" data-tab="reviews" style="flex: 1; padding: 20px; background: transparent; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; color: #666; border-bottom: 3px solid transparent;">
                        <i class="bi bi-star"></i> Đánh giá (<?php echo $review_count; ?>)
                    </button>
                </div>
                
                <!-- Tab Content -->
                <div class="tab-content" style="padding: 30px;">
                    <div id="tab-description" class="tab-pane active">
                        <div style="line-height: 1.9; color: #555;">
                            <?php the_content(); ?>
                            
                            <?php if (empty(get_the_content())) : ?>
                            <p>Sản phẩm chất lượng cao, được nhập khẩu chính hãng từ các thương hiệu uy tín trên thế giới.</p>
                            <h4 style="color: var(--color-dark); margin: 20px 0 10px;">Đặc điểm nổi bật:</h4>
                            <ul style="padding-left: 20px;">
                                <li>Nguyên liệu tự nhiên, an toàn cho thú cưng</li>
                                <li>Được kiểm định chất lượng nghiêm ngặt</li>
                                <li>Phù hợp với mọi giống và lứa tuổi</li>
                                <li>Bảo quản dễ dàng, thời hạn sử dụng dài</li>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div id="tab-specs" class="tab-pane" style="display: none;">
                        <?php 
                        $origin_display = petshop_get_origin_display(get_the_ID());
                        $brand = get_post_meta(get_the_ID(), 'product_brand', true);
                        $weight = get_post_meta(get_the_ID(), 'product_weight', true);
                        ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; width: 200px; background: #f8f9fa; color: #333;">Mã sản phẩm (SKU)</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary); font-weight: 500;"><?php echo $sku ? esc_html($sku) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Danh mục</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary);">
                                    <?php echo $product_cats ? esc_html($product_cats[0]->name) : 'Chưa phân loại'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Tình trạng</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff;">
                                    <?php if ($stock && $stock > 0) : ?>
                                        <span style="color: var(--color-secondary); font-weight: 500;">Còn hàng</span>
                                    <?php else : ?>
                                        <span style="color: #e74c3c; font-weight: 500;">Hết hàng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($brand) : ?>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Thương hiệu</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary);"><?php echo esc_html($brand); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Xuất xứ</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary);"><?php echo $origin_display ? esc_html($origin_display) : 'Đang cập nhật'; ?></td>
                            </tr>
                            <?php if ($weight) : ?>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Khối lượng</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary);"><?php echo esc_html($weight); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td style="padding: 15px; border: 1px solid #eee; font-weight: 600; background: #f8f9fa; color: #333;">Bảo hành</td>
                                <td style="padding: 15px; border: 1px solid #eee; background: #fff; color: var(--color-primary);">30 ngày</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="tab-reviews" class="tab-pane" style="display: none;">
                        <?php 
                        // Lấy danh sách đánh giá thực từ database
                        $reviews = function_exists('petshop_get_product_reviews') ? petshop_get_product_reviews(get_the_ID(), 20) : array();
                        ?>
                        
                        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 40px;">
                            <!-- Rating Summary -->
                            <div style="text-align: center; padding: 30px; background: var(--color-bg); border-radius: 15px;">
                                <div style="font-size: 4rem; font-weight: 800; color: var(--color-primary);"><?php echo $average_rating > 0 ? number_format($average_rating, 1) : '0'; ?></div>
                                <div style="display: flex; justify-content: center; gap: 5px; margin: 10px 0;">
                                    <?php for ($i = 1; $i <= 5; $i++) : 
                                        $summary_star_color = ($i <= round($average_rating)) ? 'var(--color-accent)' : '#ddd';
                                    ?>
                                        <i class="bi bi-star-fill" style="color: <?php echo $summary_star_color; ?>; font-size: 1.2rem;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p style="color: #666;">Dựa trên <?php echo $review_count; ?> đánh giá</p>
                                
                                <?php if (is_user_logged_in() && function_exists('petshop_user_has_purchased') && petshop_user_has_purchased(get_the_ID())) : ?>
                                    <?php if (function_exists('petshop_user_has_reviewed') && !petshop_user_has_reviewed(get_the_ID())) : ?>
                                    <a href="<?php echo home_url('/danh-gia/?product_id=' . get_the_ID()); ?>" class="btn btn-primary" style="margin-top: 15px; display: inline-flex; align-items: center; gap: 8px;">
                                        <i class="bi bi-pencil-square"></i> Viết đánh giá
                                    </a>
                                    <?php else : ?>
                                    <p style="margin-top: 15px; color: var(--color-secondary); font-size: 0.9rem;"><i class="bi bi-check-circle"></i> Bạn đã đánh giá sản phẩm này</p>
                                    <?php endif; ?>
                                <?php elseif (is_user_logged_in()) : ?>
                                    <p style="margin-top: 15px; color: #999; font-size: 0.9rem;">Mua sản phẩm để đánh giá</p>
                                <?php else : ?>
                                    <p style="margin-top: 15px; color: #999; font-size: 0.9rem;"><a href="<?php echo wp_login_url(get_permalink()); ?>">Đăng nhập</a> để đánh giá</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Reviews List -->
                            <div>
                                <?php if (!empty($reviews)) : ?>
                                    <?php foreach ($reviews as $review) : 
                                        $review_rating = intval(get_comment_meta($review->comment_ID, 'rating', true));
                                        $review_author = $review->comment_author;
                                        $review_date = human_time_diff(strtotime($review->comment_date), current_time('timestamp')) . ' trước';
                                        $author_initial = mb_strtoupper(mb_substr($review_author, 0, 1, 'UTF-8'), 'UTF-8');
                                        // Random background color for avatar
                                        $colors = array('var(--color-primary)', 'var(--color-secondary)', 'var(--color-accent)', '#66BCB4', '#9b59b6');
                                        $avatar_color = $colors[array_rand($colors)];
                                    ?>
                                    <div style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
                                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                            <div style="width: 50px; height: 50px; background: <?php echo $avatar_color; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                                <?php echo esc_html($author_initial); ?>
                                            </div>
                                            <div>
                                                <strong style="color: var(--color-dark);"><?php echo esc_html($review_author); ?></strong>
                                                <div style="display: flex; gap: 3px;">
                                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                        <i class="bi bi-star<?php echo $i <= $review_rating ? '-fill' : ''; ?>" style="color: <?php echo $i <= $review_rating ? 'var(--color-accent)' : '#ddd'; ?>; font-size: 0.8rem;"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <span style="margin-left: auto; color: #999; font-size: 0.9rem;"><?php echo esc_html($review_date); ?></span>
                                        </div>
                                        <p style="color: #666; line-height: 1.7;"><?php echo esc_html($review->comment_content); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div style="text-align: center; padding: 40px; background: var(--color-bg); border-radius: 15px;">
                                        <i class="bi bi-chat-left-text" style="font-size: 3rem; color: #ddd;"></i>
                                        <p style="color: #999; margin-top: 15px;">Chưa có đánh giá nào cho sản phẩm này.</p>
                                        <?php if (is_user_logged_in() && function_exists('petshop_user_has_purchased') && petshop_user_has_purchased(get_the_ID())) : ?>
                                        <p style="color: #666;">Hãy là người đầu tiên đánh giá!</p>
                                        <a href="<?php echo home_url('/danh-gia/?product_id=' . get_the_ID()); ?>" class="btn btn-primary" style="margin-top: 10px;">
                                            <i class="bi bi-pencil-square"></i> Viết đánh giá
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <section style="padding: 0 0 80px;">
        <div class="container">
            <h2 style="font-size: 1.8rem; font-weight: 700; color: var(--color-dark); margin-bottom: 30px;">
                <i class="bi bi-lightning" style="color: var(--color-primary);"></i> Sản phẩm liên quan
            </h2>
            
            <div class="products-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px;">
                <?php
                // Query sản phẩm liên quan
                $related_args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 4,
                    'post__not_in' => array(get_the_ID()),
                    'orderby' => 'rand',
                );
                
                // Nếu có danh mục, lấy sản phẩm cùng danh mục
                if ($product_cats && !is_wp_error($product_cats)) {
                    $related_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_category',
                            'field' => 'term_id',
                            'terms' => $product_cats[0]->term_id,
                        ),
                    );
                }
                
                $related_query = new WP_Query($related_args);
                
                if ($related_query->have_posts()) :
                    while ($related_query->have_posts()) : $related_query->the_post();
                        $r_price = get_post_meta(get_the_ID(), 'product_price', true);
                        $r_sale_price = get_post_meta(get_the_ID(), 'product_sale_price', true);
                ?>
                <article class="product-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: all 0.3s;">
                    <div class="product-image" style="position: relative; overflow: hidden;">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url('petshop-product'); ?>" alt="<?php the_title_attribute(); ?>" 
                                     style="width: 100%; height: 200px; object-fit: cover; transition: transform 0.3s;">
                            <?php else : ?>
                                <div style="width: 100%; height: 200px; background: var(--color-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--color-primary); opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div style="padding: 20px;">
                        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 10px;">
                            <a href="<?php the_permalink(); ?>" style="color: var(--color-dark); text-decoration: none;"><?php the_title(); ?></a>
                        </h3>
                        <div>
                            <?php if ($r_sale_price) : ?>
                                <span style="font-size: 1.1rem; font-weight: 700; color: var(--color-primary);"><?php echo number_format($r_sale_price, 0, ',', '.'); ?>đ</span>
                                <span style="font-size: 0.9rem; color: #999; text-decoration: line-through; margin-left: 8px;"><?php echo number_format($r_price, 0, ',', '.'); ?>đ</span>
                            <?php elseif ($r_price) : ?>
                                <span style="font-size: 1.1rem; font-weight: 700; color: var(--color-primary);"><?php echo number_format($r_price, 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php 
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                <p style="grid-column: span 4; text-align: center; color: #666;">Chưa có sản phẩm liên quan.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<style>
/* Single Product Page Specific Styles */
.qty-btn:hover {
    background: #EC802B !important;
    color: white !important;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.product-card:hover img {
    transform: scale(1.05);
}

.sp-tab-btn {
    transition: all 0.3s ease;
}

.sp-tab-btn:hover {
    color: var(--color-primary) !important;
    background: rgba(236, 128, 43, 0.05) !important;
}

.sp-tab-btn.active {
    color: var(--color-primary) !important;
    background: rgba(236, 128, 43, 0.08) !important;
    border-bottom: 3px solid var(--color-primary) !important;
}

@media (max-width: 1024px) {
    .product-detail {
        grid-template-columns: 1fr !important;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
// Tab functionality
document.querySelectorAll('.sp-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove active from all buttons
        document.querySelectorAll('.sp-tab-btn').forEach(b => {
            b.classList.remove('active');
            b.style.color = '#666';
            b.style.borderBottom = '3px solid transparent';
            b.style.background = 'transparent';
        });
        
        // Add active to clicked button
        this.classList.add('active');
        this.style.color = 'var(--color-primary)';
        this.style.borderBottom = '3px solid var(--color-primary)';
        this.style.background = 'rgba(236, 128, 43, 0.08)';
        
        // Hide all tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.display = 'none';
        });
        
        // Show selected tab pane
        document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
    });
});

// Change main image when clicking thumbnail
function changeMainImage(newSrc, thumbElement) {
    // Update main image
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = newSrc;
            mainImage.style.opacity = '1';
        }, 150);
    }
    
    // Update active thumbnail border
    document.querySelectorAll('.thumb-item').forEach(item => {
        item.style.borderColor = 'transparent';
    });
    if (thumbElement) {
        thumbElement.style.borderColor = 'var(--color-primary)';
    }
}

// Change quantity
function changeQty(delta) {
    const input = document.getElementById('productQty');
    let value = parseInt(input.value) || 1;
    const max = parseInt(input.max) || 999;
    
    value += delta;
    if (value < 1) value = 1;
    if (value > max) value = max;
    
    input.value = value;
}

// Add to cart function
function addToCart(buyNow = false) {
    // Kiểm tra variant nếu sản phẩm có phân loại
    if (document.getElementById('variantData')) {
        if (!spCheckVariantSelected()) return;
    }

    // Nếu "Mua ngay" - cho phép cả khách chưa đăng nhập
    if (!buyNow) {
        // Thêm vào giỏ hàng - cần đăng nhập
        if (typeof window.PETSHOP_USER !== 'undefined' && !window.PETSHOP_USER.isLoggedIn) {
            showLoginRequiredModal();
            return;
        }
    }
    
    const stock = parseInt(document.getElementById('productStock')?.value) || 999;
    const requestedQty = parseInt(document.getElementById('productQty').value) || 1;
    
    // Check stock
    if (stock <= 0) {
        showNotification('Sản phẩm đã hết hàng!', 'error');
        return;
    }
    
    if (requestedQty > stock) {
        showNotification('Số lượng yêu cầu vượt quá tồn kho. Chỉ còn ' + stock + ' sản phẩm.', 'error');
        return;
    }
    
    const variantId    = document.getElementById('selectedVariantId')?.value || '';
    const selectedSize = window._selectedSize  || '';
    const selColor     = window._selectedColor || '';
    const variantLabel = [selectedSize, selColor].filter(Boolean).join(' / ');

    const product = {
        id:            document.getElementById('productId').value,
        name:          document.getElementById('productName').value,
        price:         parseFloat(document.getElementById('productPrice').value) || 0,
        originalPrice: parseFloat(document.getElementById('productOriginalPrice').value) || 0,
        image:         document.getElementById('productImage').value,
        url:           document.getElementById('productUrl').value,
        sku:           document.getElementById('productSku').value,
        category:      document.getElementById('productCategory').value,
        quantity:      requestedQty,
        stock:         stock,
        variantId:     variantId,
        selectedSize:  selectedSize,
        selectedColor: selColor,
        variantLabel:  variantLabel,
    };
    
    // Get existing cart - dùng key theo user
    const cartKey = getCartKey();
    let cart = JSON.parse(localStorage.getItem(cartKey)) || [];
    
    // Check if product already in cart — so sánh cả variantId để không gộp nhầm phân loại khác
    const existingIndex = cart.findIndex(item =>
        item.id === product.id && (item.variantId || '') === (product.variantId || '')
    );
    
    if (existingIndex > -1) {
        // Check if adding more would exceed stock
        const newQty = cart[existingIndex].quantity + product.quantity;
        if (newQty > stock) {
            showNotification('Không thể thêm. Trong giỏ đã có ' + cart[existingIndex].quantity + ' sản phẩm. Kho chỉ còn ' + stock + '.', 'error');
            return;
        }
        // Update quantity
        cart[existingIndex].quantity = newQty;
        cart[existingIndex].stock = stock;
    } else {
        // Add new product
        cart.push(product);
    }
    
    if (buyNow) {
        // Lưu sản phẩm để thanh toán ngay (không cần đăng nhập)
        const checkoutItems = [product];
        localStorage.setItem('petshop_checkout', JSON.stringify(checkoutItems));
        // Redirect to checkout
        window.location.href = '<?php echo home_url('/thanh-toan/'); ?>?buy_now=1';
    } else {
        // Thêm vào giỏ hàng (cần đăng nhập - đã check ở trên)
        // Save cart - dùng key theo user
        const cartKey = getCartKey();
        localStorage.setItem(cartKey, JSON.stringify(cart));
        
        // Update cart count in header
        updateCartCount();
        
        // Show success message
        showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
    }
}

// Lấy key giỏ hàng theo user
function getCartKey() {
    const userId = window.PETSHOP_USER?.userId || 0;
    return userId > 0 ? `petshop_cart_user_${userId}` : 'petshop_cart_guest';
}

// Update cart count
function updateCartCount() {
    const cartKey = getCartKey();
    const cart = JSON.parse(localStorage.getItem(cartKey)) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    // Update all cart count badges
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = totalItems;
        el.style.display = totalItems > 0 ? 'flex' : 'none';
    });
}

// ============================================================
// VARIANTS SELECTOR JS — Single Product Page
// ============================================================
(function initVariants() {
    const vdEl = document.getElementById('variantData');
    if (!vdEl) return;
    window._allVariants    = JSON.parse(vdEl.value || '[]');
    window._selectedSize   = null;
    window._selectedColor  = null;
    window._hasSizes       = document.getElementById('hasSizes')?.value  === '1';
    window._hasColors      = document.getElementById('hasColors')?.value === '1';
    window._defaultImg     = document.getElementById('defaultProductImg')?.value || '';
})();

function spSelectSize(btn) {
    document.querySelectorAll('.variant-size-btn').forEach(b => {
        b.style.borderColor='#E8CCAD'; b.style.background='#fff'; b.style.color='var(--color-dark)';
    });
    btn.style.borderColor = 'var(--color-primary)';
    btn.style.background  = 'var(--color-primary)';
    btn.style.color       = '#fff';
    window._selectedSize  = btn.dataset.size;
    document.getElementById('selected-size-label').textContent = btn.dataset.size;
    if (window._hasColors) spUpdateColorsBySize(window._selectedSize);
    spUpdateVariantInfo();
}

function spSelectColor(btn) {
    document.querySelectorAll('.variant-color-btn').forEach(b => {
        b.style.borderColor='#E8CCAD'; b.style.background='#fff';
    });
    btn.style.borderColor = 'var(--color-primary)';
    btn.style.background  = '#FDF8F3';
    window._selectedColor = btn.dataset.color;
    document.getElementById('selected-color-label').textContent = btn.dataset.color;
    spUpdateVariantInfo();
}

function spUpdateColorsBySize(size) {
    const avail = window._allVariants
        .filter(v => v.size === size)
        .reduce((acc, v) => { acc[v.color] = (acc[v.color]||0) + v.stock; return acc; }, {});
    document.querySelectorAll('.variant-color-btn').forEach(btn => {
        const stock = avail[btn.dataset.color] || 0;
        btn.disabled      = stock <= 0;
        btn.style.opacity = stock > 0 ? '1' : '0.4';
        btn.style.cursor  = stock > 0 ? 'pointer' : 'not-allowed';
    });
    if (window._selectedColor && (avail[window._selectedColor]||0) <= 0) {
        window._selectedColor = null;
        document.getElementById('selected-color-label').textContent = '';
        document.querySelectorAll('.variant-color-btn').forEach(b => {
            b.style.borderColor='#E8CCAD'; b.style.background='#fff';
        });
    }
    spUpdateVariantInfo();
}

function spUpdateVariantInfo() {
    const infoEl   = document.getElementById('variant-info');
    const vidEl    = document.getElementById('selectedVariantId');
    const stockEl  = document.getElementById('productStock');
    const needSize = window._hasSizes, needColor = window._hasColors;
    const ok       = (!needSize||window._selectedSize) && (!needColor||window._selectedColor);
    if (!ok) { if(infoEl) infoEl.style.display='none'; if(vidEl) vidEl.value=''; return; }

    const variant = window._allVariants.find(v =>
        (!needSize  || v.size  === window._selectedSize) &&
        (!needColor || v.color === window._selectedColor)
    );
    if (!variant) { if(infoEl) infoEl.style.display='none'; return; }

    // Update hidden inputs
    if (vidEl)   vidEl.value   = variant.id;
    if (stockEl) stockEl.value = variant.stock;

    // Update main product image
    spSwitchVariantImage(variant);

    // Price diff
    // Cập nhật giá theo variant_price
    const spPriceRow = document.getElementById('sp-variant-price-row');
    const spPriceVal = document.getElementById('sp-variant-price-val');
    if (variant.variant_price !== null && variant.variant_price > 0) {
        document.getElementById('productPrice').value = variant.variant_price;
        if (spPriceRow) spPriceRow.style.display = '';
        if (spPriceVal) spPriceVal.textContent = variant.variant_price.toLocaleString('vi-VN') + 'đ';
    } else {
        const base = parseFloat(document.getElementById('productOriginalPrice')?.value) || 0;
        document.getElementById('productPrice').value = base;
        if (spPriceRow) spPriceRow.style.display = 'none';
    }

    // Show stock info
    if (infoEl) {
        infoEl.style.display = '';
        const icon  = variant.stock > 0
            ? '<i class="bi bi-check-circle-fill" style="color:#5cb85c;"></i>'
            : '<i class="bi bi-x-circle-fill" style="color:#d9534f;"></i>';
        const label = variant.stock > 0
            ? `Còn <strong>${variant.stock}</strong> sản phẩm`
            : '<strong>Hết hàng</strong> với lựa chọn này';
        infoEl.innerHTML = `${icon} ${label}`;
    }
    const warnEl = document.getElementById('variant-warning');
    if (warnEl) warnEl.style.display = 'none';
}

function spSwitchVariantImage(variant) {
    const mainImg = document.getElementById('mainImage');
    if (!mainImg) return;
    const targetUrl = (variant && variant.image_url) ? variant.image_url : window._defaultImg;
    if (!targetUrl) return;
    mainImg.style.opacity = '0.6';
    mainImg.style.transition = 'opacity 0.2s';
    setTimeout(() => {
        mainImg.src = targetUrl;
        mainImg.onload = () => { mainImg.style.opacity = '1'; };
        mainImg.onerror = () => { mainImg.src = window._defaultImg; mainImg.style.opacity = '1'; };
    }, 200);
}

function spCheckVariantSelected() {
    const needSize  = window._hasSizes;
    const needColor = window._hasColors;
    if (needSize  && !window._selectedSize)  { document.getElementById('variant-warning').style.display=''; return false; }
    if (needColor && !window._selectedColor) { document.getElementById('variant-warning').style.display=''; return false; }
    const vid = document.getElementById('selectedVariantId')?.value;
    if (vid) {
        const v = window._allVariants.find(x => String(x.id) === String(vid));
        if (v && v.stock <= 0) { showNotification('Lựa chọn này đã hết hàng!', 'error'); return false; }
    }
    return true;
}

// Show notification
function showNotification(message, type = 'success') {
    // Remove existing notification
    const existing = document.querySelector('.cart-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
        <span>${message}</span>
        <a href="<?php echo home_url('/gio-hang/'); ?>" style="margin-left: 15px; color: white; text-decoration: underline;">Xem giỏ hàng</a>
    `;
    notification.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 15px 25px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #66BCB4, #7ECEC6)' : '#e74c3c'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInUp 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutDown 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);

// Show login required modal
function showLoginRequiredModal() {
    // Remove existing modal if any
    const existingModal = document.getElementById('loginRequiredModal');
    if (existingModal) existingModal.remove();
    
    const modal = document.createElement('div');
    modal.id = 'loginRequiredModal';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6); z-index: 10001;
        display: flex; align-items: center; justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    modal.innerHTML = `
        <div style="background: #fff; border-radius: 20px; padding: 40px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-person-lock" style="font-size: 2.5rem; color: #fff;"></i>
            </div>
            <h3 style="color: #5D4E37; margin-bottom: 15px; font-size: 1.3rem;">Vui lòng đăng nhập</h3>
            <p style="color: #7A6B5A; margin-bottom: 25px; line-height: 1.6;">Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng và tiến hành mua sắm.</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="document.getElementById('loginRequiredModal').remove();" style="padding: 12px 25px; background: #f0f0f0; border: none; border-radius: 25px; color: #666; cursor: pointer; font-weight: 600;">Để sau</button>
                <a href="${window.PETSHOP_USER?.loginUrl || '<?php echo home_url('/tai-khoan/'); ?>'}" style="padding: 12px 25px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border: none; border-radius: 25px; color: #fff; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập ngay
                </a>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close on background click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Add keyframe styles for login modal
const loginModalStyle = document.createElement('style');
loginModalStyle.textContent = `
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
`;
document.head.appendChild(loginModalStyle);

// Countdown Timer for discount expiry
function initDiscountCountdown() {
    const countdownTimer = document.getElementById('countdown-timer');
    if (!countdownTimer) return;
    
    const expiryTimestamp = parseInt(countdownTimer.dataset.expiry) * 1000; // Convert to milliseconds
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = expiryTimestamp - now;
        
        if (distance <= 0) {
            // Countdown finished
            const countdownEl = document.getElementById('discount-countdown');
            if (countdownEl) {
                countdownEl.innerHTML = '<div style="text-align: center; padding: 5px;"><i class="bi bi-exclamation-circle"></i> Khuyến mãi đã kết thúc!</div>';
            }
            return;
        }
        
        // Calculate time units
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Update display
        document.getElementById('countdown-days').textContent = days.toString().padStart(2, '0');
        document.getElementById('countdown-hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    // Update immediately and then every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Initialize countdown on page load
document.addEventListener('DOMContentLoaded', initDiscountCountdown);

// Add combo to cart
async function addComboToCart() {
    // Kiểm tra đăng nhập trước khi thêm combo
    if (typeof window.PETSHOP_USER !== 'undefined' && !window.PETSHOP_USER.isLoggedIn) {
        showLoginRequiredModal();
        return;
    }
    
    const mainProduct = {
        id: document.getElementById('productId').value,
        name: document.getElementById('productName').value,
        price: parseFloat(document.getElementById('productPrice').value) || 0,
        originalPrice: parseFloat(document.getElementById('productOriginalPrice').value) || 0,
        image: document.getElementById('productImage').value,
        url: document.getElementById('productUrl').value,
        sku: document.getElementById('productSku').value,
        category: document.getElementById('productCategory').value,
        quantity: 1,
        stock: parseInt(document.getElementById('productStock').value) || 999
    };
    
    const comboProductIds = JSON.parse(document.getElementById('comboProducts')?.value || '[]');
    const comboCouponCode = document.getElementById('comboCouponCode')?.value || '';
    
    // Get combo products info via AJAX
    const formData = new FormData();
    formData.append('action', 'petshop_get_combo_products_info');
    formData.append('product_ids', JSON.stringify(comboProductIds));
    
    try {
        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            const cartKey = getCartKey();
            let cart = JSON.parse(localStorage.getItem(cartKey)) || [];
            
            // Add main product
            const existingMainIndex = cart.findIndex(item => item.id === mainProduct.id);
            if (existingMainIndex > -1) {
                cart[existingMainIndex].quantity += 1;
            } else {
                cart.push(mainProduct);
            }
            
            // Add combo products
            data.data.products.forEach(cp => {
                const existingIndex = cart.findIndex(item => item.id == cp.id);
                if (existingIndex > -1) {
                    cart[existingIndex].quantity += 1;
                } else {
                    cart.push({
                        id: cp.id,
                        name: cp.name,
                        price: cp.price,
                        originalPrice: cp.original_price,
                        image: cp.image,
                        url: cp.url,
                        sku: cp.sku || '',
                        category: cp.category || '',
                        quantity: 1,
                        stock: cp.stock || 999
                    });
                }
            });
            
            // Save cart
            localStorage.setItem(cartKey, JSON.stringify(cart));
            
            // Save combo coupon to apply later
            if (comboCouponCode) {
                localStorage.setItem('petshop_combo_coupon', comboCouponCode);
            }
            
            updateCartCount();
            showNotification('Đã thêm combo vào giỏ hàng! Mã giảm giá sẽ được tự động áp dụng.', 'success');
        } else {
            showNotification('Có lỗi xảy ra, vui lòng thử lại.', 'error');
        }
    } catch (error) {
        console.error('Error adding combo:', error);
        showNotification('Có lỗi xảy ra, vui lòng thử lại.', 'error');
    }
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
</style>

<?php get_footer(); ?>