<?php
/**
 * Template: Archive News (Tin tức & Blog)
 * Dùng cho cả /tin-tuc/ và /category/tin-tuc/
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-newspaper"></i> Tin Tức & Blog</h1>
        <p>Cập nhật những kiến thức hữu ích về chăm sóc thú cưng</p>
    </div>
</div>

<section class="news-section" style="padding-top: 40px;">
    <div class="container">
        <div class="news-breadcrumb-row" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <?php
            petshop_breadcrumb();
            if (!empty($_GET['cat'])) {
                $cat_id = intval($_GET['cat']);
                $cat_obj = get_category($cat_id);
                if ($cat_obj) {
                    $parent_obj = ($cat_obj->parent) ? get_category($cat_obj->parent) : null;
                    if ($parent_obj) {
                        echo '<span class="breadcrumb-sep">›</span> <span class="breadcrumb-cat">' . esc_html($parent_obj->name) . '</span>';
                    }
                    echo '<span class="breadcrumb-sep">›</span> <span class="breadcrumb-cat">' . esc_html($cat_obj->name) . '</span>';
                }
            }
            ?>
        </div>
        <!-- Filter -->
        <!-- Keyword Filter Row (separate line, using tags) -->
        <form method="get" class="news-keyword-filter" style="margin: 0 0 18px 0; display: flex; justify-content: center;">
            <div class="keyword-filter-row keyword-filter-full">
                <span style="display:inline-flex;align-items:center;margin-right:8px;color:#EDC55B;font-size:1.1rem;">
                    <i class="bi bi-lightning"></i> Từ khóa hot:
                </span>
                <button type="button" class="keyword-arrow keyword-arrow-left"><i class="bi bi-chevron-left"></i></button>
                <?php
                // Lấy các tag phổ biến nhất
                $tags = get_tags(array('orderby'=>'count','order'=>'DESC','number'=>10));
                foreach ($tags as $tag) {
                    $active = (isset($_GET['keyword']) && $_GET['keyword'] == $tag->name) ? 'active' : '';
                    echo '<button type="submit" name="keyword" value="' . esc_attr($tag->name) . '" class="keyword-btn ' . $active . '">' . esc_html($tag->name) . '</button>';
                }
                ?>
                <button type="button" class="keyword-arrow keyword-arrow-right"><i class="bi bi-chevron-right"></i></button>
            </div>
        </form>
        <!-- Main Filter Row -->
        <form method="get" class="news-filters" style="margin: 0 0 30px 0; display: flex; flex-wrap: wrap; gap: 18px; align-items: center; justify-content: center;">
            <div class="category-dropdown">
                <select name="cat">
                    <option value="">Tất cả danh mục</option>
                    <?php
                    $categories = get_categories(array('taxonomy'=>'category','parent' => 0));
                    foreach ($categories as $parent_cat) :
                        echo '<optgroup label="' . esc_html($parent_cat->name) . '">';
                        $children = get_categories(array('taxonomy'=>'category','parent' => $parent_cat->term_id));
                        foreach ($children as $cat) :
                            $selected = (is_category($cat->term_id) || (isset($_GET['cat']) && $_GET['cat'] == $cat->term_id)) ? 'selected' : '';
                            echo '<option value="' . $cat->term_id . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                        endforeach;
                        echo '</optgroup>';
                    endforeach;
                    ?>
                </select>
            </div>
            <input type="text" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Tìm kiếm bài viết..." class="filter-input">
            <input type="text" name="keyword" value="<?php echo esc_attr(isset($_GET['keyword']) ? $_GET['keyword'] : ''); ?>" placeholder="Từ khóa..." class="filter-input">
            <select name="sort" class="filter-select">
                <option value="newest" <?php if (!isset($_GET['sort']) || $_GET['sort']==='newest') echo 'selected'; ?>>Mới nhất</option>
                <option value="oldest" <?php if (isset($_GET['sort']) && $_GET['sort']==='oldest') echo 'selected'; ?>>Cũ nhất</option>
                <option value="popular" <?php if (isset($_GET['sort']) && $_GET['sort']==='popular') echo 'selected'; ?>>Xem nhiều nhất</option>
            </select>
            <button type="submit" class="btn btn-primary filter-btn">Lọc</button>
        </form>
        </form>
        <div class="news-grid">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type'      => 'post',
                'posts_per_page' => 9,
                'paged'          => $paged,
            );
            // Lọc theo danh mục, bao gồm cả parent nếu chọn child
            if (!empty($_GET['cat'])) {
                $cat_id = intval($_GET['cat']);
                $cat_obj = get_category($cat_id);
                if ($cat_obj && $cat_obj->parent) {
                    $args['cat'] = $cat_obj->parent . ',' . $cat_id;
                } else {
                    $args['cat'] = $cat_id;
                }
            } elseif (is_category()) {
                $cat_id = get_queried_object_id();
                $cat_obj = get_category($cat_id);
                if ($cat_obj && $cat_obj->parent) {
                    $args['cat'] = $cat_obj->parent . ',' . $cat_id;
                } else {
                    $args['cat'] = $cat_id;
                }
            }
            // Lọc theo từ khóa
            if (!empty($_GET['keyword'])) {
                $args['s'] = sanitize_text_field($_GET['keyword']);
            } elseif (!empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }
            // Sắp xếp
            if (!empty($_GET['sort'])) {
                if ($_GET['sort'] === 'oldest') {
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                } elseif ($_GET['sort'] === 'popular') {
                    $args['meta_key'] = 'post_views_count';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                } else {
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                }
            } else {
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            }
            $news_query = new WP_Query($args);
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
            <?php endwhile; ?>
            <!-- Pagination -->
            <div class="pagination" style="grid-column: 1 / -1;">
                <?php
                echo paginate_links(array(
                    'total'     => $news_query->max_num_pages,
                    'prev_text' => '<i class="bi bi-chevron-left"></i> Trước',
                    'next_text' => 'Sau <i class="bi bi-chevron-right"></i>',
                ));
                ?>
            </div>
            <?php else : ?>
            <div class="no-posts">
                <i class="bi bi-search" style="font-size: 4rem; color: #E8CCAD;"></i>
                <h3>Không tìm thấy kết quả</h3>
                <p>Không có bài viết nào phù hợp với yêu cầu của bạn.</p>
                <a href="<?php echo home_url('/'); ?>" class="btn btn-primary">
                    <i class="bi bi-house"></i> Về trang chủ
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
