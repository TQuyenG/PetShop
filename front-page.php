<?php
/**
 * Template: Front Page (Trang chủ)
 * 
 * @package PetShop
 */
get_header(); ?>

<!-- Banner Slider Section -->
<section class="hero-section banner-slider-section">
    <div class="container">
        <div class="banner-slider" id="petshopBannerSlider">
            <?php
            $slides = array(
                array(
                    'img' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    'title' => 'Chăm sóc <span>thú cưng</span><br>của bạn như gia đình',
                    'desc' => 'Cung cấp thức ăn cao cấp, phụ kiện chất lượng và dịch vụ tư vấn chuyên nghiệp cho chó, mèo và các loại thú cưng khác. Hơn 10,000+ sản phẩm chính hãng.',
                    'badge' => 'Chào mừng đến PetShop',
                ),
                array(
                    'img' => 'https://images.pexels.com/photos/1108099/pexels-photo-1108099.jpeg?auto=compress&w=800&q=80',
                    'title' => 'Thú cưng khỏe mạnh<br>Gia đình hạnh phúc',
                    'desc' => 'Dịch vụ chăm sóc, khám bệnh và tư vấn sức khỏe cho thú cưng. Đội ngũ bác sĩ tận tâm, chuyên nghiệp.',
                    'badge' => 'Dịch vụ sức khỏe',
                ),
                array(
                    'img' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    'title' => 'Phụ kiện & Đồ chơi<br>Cho thú cưng vui vẻ',
                    'desc' => 'Phụ kiện, đồ chơi, chuồng, nệm, quần áo cho thú cưng đa dạng, chất lượng cao, giá tốt.',
                    'badge' => 'Phụ kiện & Đồ chơi',
                ),
            );
            foreach ($slides as $i => $slide) :
            ?>
            <div class="banner-slide<?php echo $i === 0 ? ' active' : ''; ?>">
                <div class="hero-inner">
                    <div class="hero-content">
                        <span class="hero-badge">
                            <i class="bi bi-lightning-fill"></i>
                            <?php echo $slide['badge']; ?>
                        </span>
                        <h1 class="hero-title"><?php echo $slide['title']; ?></h1>
                        <p class="hero-desc"><?php echo $slide['desc']; ?></p>
                        <div class="hero-buttons">
                            <a href="<?php echo home_url('/san-pham/'); ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-bag-check"></i>
                                Mua sắm ngay
                            </a>
                            <a href="#features" class="btn btn-outline btn-lg">Tìm hiểu thêm</a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="<?php echo $slide['img']; ?>" alt="Banner slide">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="banner-slider-controls">
                <!-- Đã xóa hai icon button mũi tên điều hướng -->
            </div>
            <div class="banner-slider-dots">
                <?php foreach ($slides as $i => $slide) : ?>
                <button class="banner-slider-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-slide="<?php echo $i; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Tại sao chọn chúng tôi</span>
            <h2 class="section-title">Dịch vụ tốt nhất cho thú cưng</h2>
            <p class="section-desc">Chúng tôi cam kết mang đến sản phẩm chất lượng và dịch vụ chu đáo nhất</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-award"></i>
                </div>
                <h3>Sản phẩm chính hãng</h3>
                <p>100% sản phẩm nhập khẩu chính hãng, có giấy chứng nhận</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-truck"></i>
                </div>
                <h3>Giao hàng nhanh</h3>
                <p>Giao hàng trong 2h nội thành, 1-3 ngày toàn quốc</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <h3>Giá cả hợp lý</h3>
                <p>Cam kết giá tốt nhất, hoàn tiền nếu tìm thấy rẻ hơn</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-headset"></i>
                </div>
                <h3>Hỗ trợ 24/7</h3>
                <p>Đội ngũ tư vấn chuyên nghiệp, hỗ trợ mọi lúc</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Danh mục</span>
            <h2 class="section-title">Khám phá theo danh mục</h2>
            <p class="section-desc">Tìm sản phẩm phù hợp cho thú cưng của bạn</p>
        </div>
        <div class="categories-grid">
            <?php
            // Demo ảnh cho từng danh mục (cố định theo thứ tự)
            $demo_images = [
                'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=400&q=80', // chó
                'https://images.unsplash.com/photo-1518715308788-3005759c61d3?auto=format&fit=crop&w=400&q=80', // mèo
                'https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=400&q=80', // thú cưng khác
                'https://images.unsplash.com/photo-1518715308788-3005759c61d3?auto=format&fit=crop&w=400&q=80',
                'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=400&q=80',
                'https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=400&q=80',
            ];
            $product_categories = get_terms(array(
                'taxonomy' => 'product_category',
                'hide_empty' => false,
                'parent' => 0,
                'number' => 9,
            ));
            $cat_styles = array(
                'thuc-an' => array('icon' => 'bi-basket', 'color' => '#EC802B'),
                'phu-kien' => array('icon' => 'bi-bag-heart', 'color' => '#66BCB4'),
                'do-choi' => array('icon' => 'bi-controller', 'color' => '#EDC55B'),
                'chuong-nha' => array('icon' => 'bi-house', 'color' => '#E8CCAD'),
                've-sinh-cham-soc' => array('icon' => 'bi-droplet', 'color' => '#66BCB4'),
                'y-te-thuoc' => array('icon' => 'bi-capsule', 'color' => '#EC802B'),
            );
            if (empty($product_categories) || is_wp_error($product_categories)) {
                $product_categories = array(
                    (object)array('name' => 'Thức ăn', 'slug' => 'thuc-an', 'count' => 0, 'term_id' => 1),
                    (object)array('name' => 'Phụ kiện', 'slug' => 'phu-kien', 'count' => 0, 'term_id' => 2),
                    (object)array('name' => 'Đồ chơi', 'slug' => 'do-choi', 'count' => 0, 'term_id' => 3),
                );
            }
            $total = count($product_categories);
            $slide_count = ceil($total/3);
            ?>
            <div class="asym-cat-slider">
                <?php for($slide=0;$slide<$slide_count;$slide++): ?>
                <div class="asym-cat-slide" data-slide="<?php echo $slide; ?>" style="display:<?php echo $slide==0?'block':'none'; ?>;">
                    <div class="asym-cat-layout">
                        <?php for($j=0;$j<3;$j++): $i=$slide*3+$j; if(!isset($product_categories[$i])) break; $cat=$product_categories[$i]; $style=isset($cat_styles[$cat->slug])?$cat_styles[$cat->slug]:array('icon'=>'bi-tag','color'=>'#EC802B'); $img=$demo_images[$i%count($demo_images)]; ?>
                        <?php if($j==0): ?>
                        <div class="cat-box cat-box-a">
                            <a href="<?php echo get_term_link($cat); ?>" class="cat-card cat-main">
                                <div class="cat-img" style="background:<?php echo $style['color']; ?>22;overflow:hidden;">
                                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cat->name); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                                </div>
                                <div class="cat-title"><?php echo esc_html($cat->name); ?></div>
                                <div class="cat-desc">Có <?php echo intval($cat->count); ?> sản phẩm</div>
                            </a>
                        </div>
                        <?php elseif($j==1): ?>
                        <div class="cat-box cat-box-bc">
                            <div class="cat-box-b">
                                <a href="<?php echo get_term_link($cat); ?>" class="cat-card cat-small">
                                    <div class="cat-img" style="background:<?php echo $style['color']; ?>22;overflow:hidden;">
                                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cat->name); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                                    </div>
                                    <div class="cat-title"><?php echo esc_html($cat->name); ?></div>
                                    <div class="cat-desc">Có <?php echo intval($cat->count); ?> sản phẩm</div>
                                </a>
                            </div>
                        <?php elseif($j==2): ?>
                            <div class="cat-box-c">
                                <a href="<?php echo get_term_link($cat); ?>" class="cat-card cat-wide">
                                    <div class="cat-img" style="background:<?php echo $style['color']; ?>22;overflow:hidden;">
                                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($cat->name); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                                    </div>
                                    <div class="cat-title"><?php echo esc_html($cat->name); ?></div>
                                    <div class="cat-desc">Có <?php echo intval($cat->count); ?> sản phẩm</div>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endfor; ?>
                <button class="cat-next-btn" id="catNextBtn"><b>Xem tiếp -></b></button>
            </div>
        <style>
        .asym-cat-slider { position:relative; width:100%; max-width:900px; margin:0 auto; }
        .asym-cat-slide { width:100%; min-height:320px; display:none; }
        .asym-cat-slide[data-slide="0"] { display:block; }
        .asym-cat-layout { display:flex; gap:24px; justify-content:center; align-items:center; }
        .cat-box-a { flex:1.2; }
        .cat-box-bc { flex:1; display:flex; flex-direction:column; gap:18px; }
        .cat-box-b { flex:1; }
        .cat-box-c { flex:1; }
        .cat-card { display:flex; flex-direction:column; align-items:center; justify-content:center; border:3px solid #222; border-radius:18px; background:#fff; box-shadow:0 2px 12px #0001; transition:.2s; text-align:center; text-decoration:none; margin:0 auto; }
        .cat-main { min-width:220px; min-height:220px; font-size:1.3rem; padding:32px 12px 18px; }
        .cat-small { min-width:120px; min-height:100px; font-size:1rem; padding:18px 8px 10px; }
        .cat-wide { min-width:220px; min-height:80px; font-size:1rem; padding:18px 8px 10px; }
        .cat-img { width:70px; height:70px; border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; background:#eee; }
        .cat-title { font-weight:700; color:#ff8800; margin-bottom:6px; }
        .cat-desc { color:#888; font-size:0.95em; }
        .cat-next-btn { margin:24px auto 0; display:block; background:none; border:2px dashed #ff8800; color:#ff8800; font-size:2rem; font-weight:700; border-radius:18px; padding:8px 32px; cursor:pointer; transition:.2s; }
        .cat-next-btn:hover { background:#ff880022; }
        @media (max-width: 700px) {
            .asym-cat-layout { flex-direction:column; gap:12px; }
            .cat-box-bc { flex-direction:row; gap:12px; }
            .cat-main, .cat-wide { min-width:unset; width:100%; }
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let current = 0;
            const slides = document.querySelectorAll('.asym-cat-slide');
            const btn = document.getElementById('catNextBtn');
            if (!btn || slides.length < 2) return;
            btn.addEventListener('click', function() {
                slides[current].style.display = 'none';
                current = (current + 1) % slides.length;
                slides[current].style.display = 'block';
            });
        });
        </script>
        </div>
    </div>
</section> -->

<!-- Products Section -->
<section id="products" class="products-section">
    <div class="container">
        <div class="section-header-flex" style="flex-direction:column;align-items:center;justify-content:center;text-align:center;">
            <div class="section-header" style="margin: 0 auto;">
                <span class="section-badge">Sản phẩm</span>
                <h2 class="section-title">Sản phẩm nổi bật</h2>
            </div>
            <div class="products-tabs" style="justify-content:center;">
                <?php
                // Lấy category filter từ URL
                $current_cat_filter = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : 'all';
                ?>
                <a href="<?php echo esc_url(remove_query_arg('cat')); ?>" class="tab-btn <?php echo $current_cat_filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
                <?php
                // Lấy danh mục cho tabs
                $tab_categories = get_terms(array(
                    'taxonomy' => 'product_category',
                    'hide_empty' => false,
                    'parent' => 0,
                    'number' => 5,
                ));
                if (!empty($tab_categories) && !is_wp_error($tab_categories)) :
                    foreach ($tab_categories as $tab_cat) :
                ?>
                <a href="<?php echo esc_url(add_query_arg('cat', $tab_cat->slug)); ?>" class="tab-btn <?php echo $current_cat_filter === $tab_cat->slug ? 'active' : ''; ?>"><?php echo esc_html($tab_cat->name); ?></a>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>
        
        <div class="products-grid">
            <?php
            // Query args
            $product_args = array(
                'post_type'      => 'product',
                'posts_per_page' => 8,
                'orderby'        => 'date',
                'order'          => 'DESC',
            );
            
            // Thêm filter theo danh mục nếu có
            if ($current_cat_filter !== 'all') {
                $product_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_category',
                        'field'    => 'slug',
                        'terms'    => $current_cat_filter,
                        'include_children' => true,
                    ),
                );
            }
            
            // Query sản phẩm từ custom post type
            $products_query = new WP_Query($product_args);
            
            if ($products_query->have_posts()) :
                while ($products_query->have_posts()) : $products_query->the_post();
                    $price = get_post_meta(get_the_ID(), 'product_price', true);
                    $sale_price = get_post_meta(get_the_ID(), 'product_sale_price', true);
                    $product_cats = get_the_terms(get_the_ID(), 'product_category');
                    $cat_name = ($product_cats && !is_wp_error($product_cats)) ? $product_cats[0]->name : 'Sản phẩm';
                    $cat_link = ($product_cats && !is_wp_error($product_cats)) ? get_term_link($product_cats[0]) : get_post_type_archive_link('product');
            ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('petshop-product'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php the_permalink(); ?>">
                            <img src="https://images.unsplash.com/photo-1583337130417-33c15b6ca1fa?w=400" alt="<?php the_title(); ?>">
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($sale_price) && $sale_price < $price) : ?>
                    <div class="product-badges">
                        <span class="badge badge-sale">-<?php echo round((($price - $sale_price) / $price) * 100); ?>%</span>
                    </div>
                    <?php endif; ?>
                    <div class="product-actions">
                        <a href="<?php the_permalink(); ?>" class="action-btn" title="Xem nhanh"><i class="bi bi-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <a href="<?php echo esc_url($cat_link); ?>" class="product-category"><?php echo esc_html($cat_name); ?></a>
                    <h3 class="product-name">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                        <span class="rating-count">(<?php echo rand(10, 50); ?>)</span>
                    </div>
                    <?php echo petshop_get_product_price_html(get_the_ID()); ?>
                </div>
                <div class="product-footer">
                    <a class="add-to-cart buy-now-btn" href="<?php the_permalink(); ?>">
                        Mua ngay <i class="bi bi-lightning-fill"></i>
                    </a>
                </div>
            <!-- Login Modal for guest actions -->
            <div id="petshop-login-modal" style="display:none;position:fixed;z-index:99999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                    <div style="background:#fff;padding:32px 28px 18px 28px;border-radius:18px;max-width:350px;width:90vw;box-shadow:0 8px 40px #0002;text-align:center;position:relative;">
                            <button onclick="document.getElementById('petshop-login-modal').style.display='none'" style="position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.5rem;color:#888;cursor:pointer;"><i class="bi bi-x"></i></button>
                            <i class="bi bi-person-circle" style="font-size:3rem;color:#EC802B;"></i>
                            <h3 style="margin:12px 0 8px 0;">Bạn cần đăng nhập</h3>
                            <p style="color:#666;font-size:1rem;">Vui lòng đăng nhập để sử dụng chức năng này.</p>
                            <a href="<?php echo home_url('/dang-nhap/'); ?>" class="btn btn-primary" style="margin-top:12px;display:inline-block;">Đăng nhập</a>
                            <div style="margin-top:8px;font-size:0.95em;">Chưa có tài khoản? <a href="<?php echo home_url('/dang-ky/'); ?>">Đăng ký</a></div>
                    </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.product-actions .action-btn, .product-actions .fav-btn, .product-actions .btn-quick-view').forEach(function(btn) {
                            btn.addEventListener('click', function(e) {
                                    if(window.PETSHOP_USER && !window.PETSHOP_USER.isLoggedIn) {
                                            e.preventDefault();
                                            document.getElementById('petshop-login-modal').style.display = 'flex';
                                            return false;
                                    }
                            });
                    });
            });
            </script>
            </div>
            <?php 
                endwhile;
                wp_reset_postdata();
            else :
                // Hiển thị sản phẩm mẫu nếu chưa có sản phẩm
                $sample_products = array(
                    array('name' => 'Thức ăn Royal Canin', 'price' => 450000, 'sale' => 380000, 'img' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=400'),
                    array('name' => 'Pate cho mèo Whiskas', 'price' => 35000, 'sale' => 0, 'img' => 'https://images.unsplash.com/photo-1574158622682-e40e69881006?w=400'),
                    array('name' => 'Vòng cổ LED cho chó', 'price' => 120000, 'sale' => 99000, 'img' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=400'),
                    array('name' => 'Đồ chơi bóng cho chó', 'price' => 45000, 'sale' => 0, 'img' => 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=400'),
                    array('name' => 'Cát vệ sinh mèo', 'price' => 180000, 'sale' => 150000, 'img' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400'),
                    array('name' => 'Lồng vận chuyển', 'price' => 350000, 'sale' => 0, 'img' => 'https://images.unsplash.com/photo-1583511655826-05700442976d?w=400'),
                    array('name' => 'Sữa tắm cho thú cưng', 'price' => 95000, 'sale' => 79000, 'img' => 'https://images.unsplash.com/photo-1596854407944-bf87f6fdd49e?w=400'),
                    array('name' => 'Nệm ngủ cho mèo', 'price' => 250000, 'sale' => 0, 'img' => 'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=400'),
                );
                
                foreach ($sample_products as $product) :
            ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="<?php echo get_post_type_archive_link('product'); ?>">
                        <img src="<?php echo $product['img']; ?>" alt="<?php echo $product['name']; ?>">
                    </a>
                    <?php if ($product['sale'] > 0) : ?>
                    <div class="product-badges">
                        <span class="badge badge-sale">-<?php echo round((($product['price'] - $product['sale']) / $product['price']) * 100); ?>%</span>
                    </div>
                    <?php endif; ?>
                    <div class="product-actions">
                        <button class="action-btn" title="Yêu thích"><i class="bi bi-heart"></i></button>
                        <button class="action-btn" title="Xem nhanh"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="product-info">
                    <span class="product-category">Sản phẩm mẫu</span>
                    <h3 class="product-name">
                        <a href="<?php echo get_post_type_archive_link('product'); ?>"><?php echo $product['name']; ?></a>
                    </h3>
                    <div class="product-rating">
                        <span class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                        <span class="rating-count">(<?php echo rand(10, 50); ?>)</span>
                    </div>
                    <div class="product-price">
                        <?php if ($product['sale'] > 0) : ?>
                        <span class="price-old"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                        <span class="price-current"><?php echo number_format($product['sale'], 0, ',', '.'); ?>đ</span>
                        <?php else : ?>
                        <span class="price-current"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-footer">
                    <button class="add-to-cart">
                        <i class="bi bi-bag-plus"></i>
                        Thêm vào giỏ
                    </button>
                </div>
            </div>
            <?php 
                endforeach;
            endif;
            ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="<?php echo get_post_type_archive_link('product'); ?>" class="btn btn-outline btn-lg">
                Xem tất cả sản phẩm <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Nhận ưu đãi đặc biệt ngay hôm nay!</h2>
            <p>Đăng ký thành viên để nhận ngay voucher giảm giá 20% cho đơn hàng đầu tiên</p>
            <a href="#" class="btn btn-lg">
                <i class="bi bi-gift"></i>
                Nhận ưu đãi ngay
            </a>
        </div>
    </div>
</section>

<!-- News Section -->
<section class="news-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Blog & Tin tức</span>
            <h2 class="section-title">Kiến thức chăm sóc thú cưng</h2>
            <p class="section-desc">Cập nhật những thông tin hữu ích về cách chăm sóc thú cưng</p>
        </div>
        
        <div class="news-grid">
            <?php
            $news_query = new WP_Query(array(
                'post_type'      => 'post',
                'posts_per_page' => 3,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ));
            
            if ($news_query->have_posts()) :
                while ($news_query->have_posts()) : $news_query->the_post();
            ?>
            <article class="news-card">
                <div class="news-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('petshop-featured'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php the_permalink(); ?>">
                            <img src="https://images.unsplash.com/photo-1544568100-847a948585b9?w=600" alt="<?php the_title(); ?>">
                        </a>
                    <?php endif; ?>
                </div>
                <div class="news-content">
                    <div class="news-meta">
                        <span><i class="bi bi-calendar3"></i> <?php echo get_the_date('d/m/Y'); ?></span>
                        <span><i class="bi bi-person"></i> <?php the_author(); ?></span>
                    </div>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <p class="news-excerpt"><?php echo petshop_excerpt(20); ?></p>
                    <a href="<?php the_permalink(); ?>" class="read-more">
                        Đọc thêm <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php 
                endwhile;
                wp_reset_postdata();
            else :
                // Sample news
                $sample_news = array(
                    array('title' => '10 cách chăm sóc chó con mới sinh', 'date' => '15/01/2026', 'img' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600'),
                    array('title' => 'Thức ăn nào tốt nhất cho mèo?', 'date' => '12/01/2026', 'img' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600'),
                    array('title' => 'Dấu hiệu thú cưng bị ốm', 'date' => '10/01/2026', 'img' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600'),
                );
                
                foreach ($sample_news as $news) :
            ?>
            <article class="news-card">
                <div class="news-image">
                    <a href="#">
                        <img src="<?php echo $news['img']; ?>" alt="<?php echo $news['title']; ?>">
                    </a>
                </div>
                <div class="news-content">
                    <div class="news-meta">
                        <span><i class="bi bi-calendar3"></i> <?php echo $news['date']; ?></span>
                        <span><i class="bi bi-person"></i> Admin</span>
                    </div>
                    <h3><a href="#"><?php echo $news['title']; ?></a></h3>
                    <p class="news-excerpt">Tìm hiểu những kiến thức bổ ích để chăm sóc thú cưng của bạn một cách tốt nhất...</p>
                    <a href="#" class="read-more">
                        Đọc thêm <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php 
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>

<script>
(function() {
    var slider  = document.getElementById('petshopBannerSlider');
    if (!slider) return;

    var slides  = slider.querySelectorAll('.banner-slide');
    var dots    = slider.querySelectorAll('.banner-slider-dot');
    var prevBtn = slider.querySelector('.banner-slider-prev');
    var nextBtn = slider.querySelector('.banner-slider-next');
    var current = 0;
    var total   = slides.length;
    var timer;

    function goTo(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (index + total) % total;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function startAuto() {
        timer = setInterval(function() { goTo(current + 1); }, 5000);
    }

    function resetAuto() {
        clearInterval(timer);
        startAuto();
    }

    if (nextBtn) nextBtn.addEventListener('click', function() { goTo(current + 1); resetAuto(); });
    if (prevBtn) prevBtn.addEventListener('click', function() { goTo(current - 1); resetAuto(); });

    dots.forEach(function(dot, i) {
        dot.addEventListener('click', function() { goTo(i); resetAuto(); });
    });

    startAuto();
})();
</script>

<?php get_footer(); ?>