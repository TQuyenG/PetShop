<?php
/**
 * Template: Single Post (Chi tiết bài viết)
 * 
 * @package PetShop
 */
get_header(); ?>

<div class="single-post-page">
    <div class="container">
        <!-- Breadcrumb -->
        <?php petshop_breadcrumb(); ?>
        
        <div class="single-post-container">
            <!-- Main Content -->
            <article class="post-main">
                <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                
                <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
                
                <div class="post-content-wrap">
                    <header class="post-header">
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        <div class="post-meta-row">
                            <div class="post-meta">
                                <span>
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo get_the_date('d/m/Y'); ?>
                                </span>
                                <span>
                                    <i class="bi bi-person"></i>
                                    <?php the_author(); ?>
                                </span>
                                <span>
                                    <i class="bi bi-clock"></i>
                                    <?php echo petshop_get_reading_time(); ?>
                                </span>
                                <?php
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
                                        echo '<a href="' . get_category_link($parent->term_id) . '" class="post-category post-category-parent">' . $parent->name . '</a>';
                                    }
                                    echo '<a href="' . get_category_link($child->term_id) . '" class="post-category post-category-child">' . $child->name . '</a>';
                                }
                                ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="post-actions">
                                <button class="action-icon-btn like-btn" data-post-id="<?php echo get_the_ID(); ?>" data-tooltip="Yêu thích">
                                    <i class="bi bi-heart"></i>
                                    <span class="tooltip-text">Yêu thích</span>
                                </button>
                                <button class="action-icon-btn save-btn" data-post-id="<?php echo get_the_ID(); ?>" data-tooltip="Lưu bài">
                                    <i class="bi bi-bookmark"></i>
                                    <span class="tooltip-text">Lưu bài</span>
                                </button>
                                <div class="share-dropdown">
                                    <button class="action-icon-btn share-toggle-btn" data-tooltip="Chia sẻ">
                                        <i class="bi bi-share"></i>
                                        <span class="tooltip-text">Chia sẻ</span>
                                    </button>
                                    <div class="share-dropdown-menu">
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" target="_blank" class="share-item">
                                            <i class="bi bi-facebook"></i> Facebook
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-item">
                                            <i class="bi bi-twitter-x"></i> Twitter/X
                                        </a>
                                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php the_permalink(); ?>" target="_blank" class="share-item">
                                            <i class="bi bi-linkedin"></i> LinkedIn
                                        </a>
                                        <a href="https://pinterest.com/pin/create/button/?url=<?php the_permalink(); ?>" target="_blank" class="share-item">
                                            <i class="bi bi-pinterest"></i> Pinterest
                                        </a>
                                        <a href="https://t.me/share/url?url=<?php the_permalink(); ?>" target="_blank" class="share-item">
                                            <i class="bi bi-telegram"></i> Telegram
                                        </a>
                                        <button class="share-item copy-link-btn" data-url="<?php the_permalink(); ?>">
                                            <i class="bi bi-link-45deg"></i> Sao chép link
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>
                    
                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php 
                    $tags = get_the_tags();
                    if ($tags) : ?>
                    <div class="post-tags">
                        <strong><i class="bi bi-tags"></i> Tags:</strong>
                        <?php foreach ($tags as $tag) : ?>
                        <a href="<?php echo get_tag_link($tag->term_id); ?>"><?php echo $tag->name; ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bottom Action Bar -->
                    <div class="post-bottom-actions">
                        <div class="bottom-action-left">
                            <button class="bottom-action-btn like-btn-bottom" data-post-id="<?php echo get_the_ID(); ?>">
                                <i class="bi bi-heart"></i>
                                <span>Thích bài viết này</span>
                            </button>
                        </div>
                        <div class="bottom-action-right">
                            <strong><i class="bi bi-share"></i> Chia sẻ:</strong>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" class="share-btn facebook" target="_blank" title="Facebook">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>" class="share-btn twitter" target="_blank" title="Twitter">
                                    <i class="bi bi-twitter-x"></i>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php the_permalink(); ?>" class="share-btn linkedin" target="_blank" title="LinkedIn">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                                <a href="https://pinterest.com/pin/create/button/?url=<?php the_permalink(); ?>" class="share-btn pinterest" target="_blank" title="Pinterest">
                                    <i class="bi bi-pinterest"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Author Box -->
                    <div class="author-box">
                        <div class="author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 80); ?>
                        </div>
                        <div class="author-info">
                            <span class="author-label">Tác giả</span>
                            <h4 class="author-name"><?php the_author(); ?></h4>
                            <p class="author-bio"><?php echo get_the_author_meta('description') ?: 'Người yêu thú cưng và đam mê chia sẻ kiến thức chăm sóc thú cưng.'; ?></p>
                            <div class="author-social">
                                <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                                <a href="#" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                                <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="comments-section">
                        <h3 class="comments-title">
                            <i class="bi bi-chat-dots"></i>
                            Bình luận 
                            <span class="comment-count">(<?php echo get_comments_number(); ?>)</span>
                        </h3>
                        
                        <?php if (comments_open()) : ?>
                        <!-- Comment Form -->
                        <div class="comment-form-wrapper">
                            <h4><i class="bi bi-pencil-square"></i> Để lại bình luận của bạn</h4>
                            <?php 
                            $commenter = wp_get_current_commenter();
                            $req = get_option('require_name_email');
                            $aria_req = ($req ? " aria-required='true'" : '');
                            
                            $comments_args = array(
                                'title_reply'          => '',
                                'title_reply_to'       => '<i class="bi bi-reply"></i> Trả lời %s',
                                'cancel_reply_link'    => '<i class="bi bi-x-lg"></i> Hủy',
                                'label_submit'         => 'Gửi bình luận',
                                'class_submit'         => 'btn btn-primary submit-comment-btn',
                                'comment_field'        => '<div class="comment-form-textarea"><textarea id="comment" name="comment" placeholder="Viết bình luận của bạn..." rows="4" aria-required="true"></textarea></div>',
                                'fields'               => array(
                                    'author' => '<div class="comment-form-fields"><div class="form-group"><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" placeholder="Họ tên *"' . $aria_req . ' /></div>',
                                    'email'  => '<div class="form-group"><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" placeholder="Email *"' . $aria_req . ' /></div>',
                                    'url'    => '<div class="form-group"><input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '" placeholder="Website (tùy chọn)" /></div></div>',
                                ),
                                'submit_button'        => '<button type="submit" name="%1$s" id="%2$s" class="%3$s"><i class="bi bi-send"></i> %4$s</button>',
                            );
                            comment_form($comments_args);
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Comments List -->
                        <div class="comments-list">
                            <?php
                            $comments = get_comments(array(
                                'post_id' => get_the_ID(),
                                'status'  => 'approve',
                                'order'   => 'ASC'
                            ));
                            
                            if ($comments) :
                                foreach ($comments as $comment) :
                            ?>
                            <div class="comment-item" id="comment-<?php echo $comment->comment_ID; ?>">
                                <div class="comment-avatar">
                                    <?php echo get_avatar($comment, 50); ?>
                                </div>
                                <div class="comment-body">
                                    <div class="comment-header">
                                        <span class="comment-author"><?php echo $comment->comment_author; ?></span>
                                        <span class="comment-date">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($comment->comment_date)); ?>
                                        </span>
                                    </div>
                                    <div class="comment-content">
                                        <?php echo wpautop($comment->comment_content); ?>
                                    </div>
                                    <div class="comment-actions">
                                        <?php 
                                        comment_reply_link(array(
                                            'reply_text' => '<i class="bi bi-reply"></i> Trả lời',
                                            'depth'      => 1,
                                            'max_depth'  => 3,
                                        ), $comment->comment_ID, get_the_ID()); 
                                        ?>
                                        <button class="comment-like-btn" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                            <i class="bi bi-hand-thumbs-up"></i> Thích
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            else :
                            ?>
                            <div class="no-comments">
                                <i class="bi bi-chat-square-text"></i>
                                <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; endif; ?>
            </article>
            
            <!-- Sidebar -->
            <aside class="post-sidebar">
                <!-- Search -->
                <div class="sidebar-widget">
                    <h3><i class="bi bi-search"></i> Tìm kiếm</h3>
                    <form class="sidebar-search" action="<?php echo home_url('/'); ?>" method="get">
                        <input type="search" name="s" placeholder="Tìm kiếm bài viết...">
                    </form>
                </div>
                
                <!-- Categories -->
                <div class="sidebar-widget">
                    <h3><i class="bi bi-folder"></i> Danh mục</h3>
                    <ul class="sidebar-categories">
                        <?php
                        $parent_cats = get_categories(array('taxonomy'=>'category','parent'=>0,'hide_empty'=>false));
                        foreach ($parent_cats as $parent_cat) :
                            // Đếm tổng số bài viết của parent + tất cả child
                            $child_cats = get_categories(array('taxonomy'=>'category','parent'=>$parent_cat->term_id,'hide_empty'=>false));
                            $child_ids = array_map(function($c){return $c->term_id;}, $child_cats);
                            $all_ids = array_merge(array($parent_cat->term_id), $child_ids);
                            $count = 0;
                            if ($all_ids) {
                                $count = wp_count_posts('post')->publish;
                                $args = array(
                                    'cat' => implode(',', $all_ids),
                                    'posts_per_page' => -1,
                                    'fields' => 'ids',
                                );
                                $posts = get_posts($args);
                                $count = count($posts);
                            }
                        ?>
                        <li class="sidebar-category-parent">
                            <a href="<?php echo get_category_link($parent_cat->term_id); ?>" style="font-weight:700;color:#5D4E37;">
                                <?php echo $parent_cat->name; ?>
                                <span style="background:#EDC55B;color:#fff;border-radius:12px;padding:2px 10px;margin-left:8px;font-size:0.95em;"><?php echo $count; ?></span>
                            </a>
                            <?php if ($child_cats) : ?>
                                <ul class="sidebar-subcategories">
                                    <?php foreach ($child_cats as $child) :
                                        $child_count = $child->count;
                                    ?>
                                    <li class="sidebar-category-child">
                                        <a href="<?php echo get_category_link($child->term_id); ?>" style="font-weight:400;color:#66BCB4;margin-left:18px;">
                                            <?php echo $child->name; ?>
                                            <span style="background:#FDF8F3;color:#EC802B;border-radius:12px;padding:2px 10px;margin-left:8px;font-size:0.95em;"><?php echo $child_count; ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Recent Posts -->
                <div class="sidebar-widget">
                    <h3><i class="bi bi-clock-history"></i> Bài viết gần đây</h3>
                    <?php
                    $recent_posts = new WP_Query(array(
                        'posts_per_page' => 4,
                        'post__not_in'   => array(get_the_ID()),
                    ));
                    
                    while ($recent_posts->have_posts()) : $recent_posts->the_post();
                    ?>
                    <div class="recent-post">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('petshop-thumbnail'); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php the_permalink(); ?>">
                                <img src="https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=150" alt="<?php the_title(); ?>">
                            </a>
                        <?php endif; ?>
                        <div class="recent-post-info">
                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                            <span><i class="bi bi-calendar3"></i> <?php echo get_the_date('d/m/Y'); ?></span>
                        </div>
                    </div>
                    <?php 
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </aside>
        </div>
        
        <!-- Related Posts -->
        <div class="related-posts">
            <h2><i class="bi bi-journal-text"></i> Bài viết liên quan</h2>
            <div class="news-grid">
                <?php
                $related_query = new WP_Query(array(
                    'posts_per_page' => 3,
                    'post__not_in'   => array(get_the_ID()),
                    'orderby'        => 'rand',
                ));
                
                while ($related_query->have_posts()) : $related_query->the_post();
                ?>
                <article class="news-card">
                    <div class="news-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('petshop-featured'); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php the_permalink(); ?>">
                                <img src="https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600" alt="<?php the_title(); ?>">
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="news-content">
                        <div class="news-meta">
                            <span><i class="bi bi-calendar3"></i> <?php echo get_the_date('d/m/Y'); ?></span>
                        </div>
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <a href="<?php the_permalink(); ?>" class="read-more">
                            Đọc thêm <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php 
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
