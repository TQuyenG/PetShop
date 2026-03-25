<?php
/**
 * Template Name: Trang Đánh Giá Sản Phẩm
 * 
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<div class="page-header" style="background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);">
    <div class="container">
        <h1 style="color: #fff;"><i class="bi bi-star"></i> Đánh Giá Sản Phẩm</h1>
        <p style="color: rgba(255,255,255,0.9);">Chia sẻ trải nghiệm của bạn về sản phẩm</p>
    </div>
</div>

<section class="review-section" style="padding: 60px 0;">
    <div class="container">
        <?php petshop_breadcrumb(); ?>
        
        <div style="max-width: 800px; margin: 40px auto 0;">
            <?php 
            // Check if user is logged in
            if (!is_user_logged_in()) : 
            ?>
                <div style="background: #fff; border-radius: 20px; padding: 50px; text-align: center; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <i class="bi bi-person-lock" style="font-size: 4rem; color: #E8CCAD;"></i>
                    <h3 style="margin: 20px 0 10px;">Vui lòng đăng nhập</h3>
                    <p style="color: #7A6B5A; margin-bottom: 25px;">Bạn cần đăng nhập để đánh giá sản phẩm.</p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                    </a>
                </div>
            <?php 
            else :
                // Get products user can review
                $products_to_review = function_exists('petshop_get_products_to_review') ? petshop_get_products_to_review() : array();
                
                if (empty($products_to_review)) :
            ?>
                <div style="background: #fff; border-radius: 20px; padding: 50px; text-align: center; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <i class="bi bi-bag-check" style="font-size: 4rem; color: #66BCB4;"></i>
                    <h3 style="margin: 20px 0 10px;">Chưa có sản phẩm cần đánh giá</h3>
                    <p style="color: #7A6B5A; margin-bottom: 25px;">Bạn đã đánh giá tất cả sản phẩm đã mua hoặc chưa có đơn hàng nào. Hãy mua sắm và quay lại đây sau nhé!</p>
                    <a href="<?php echo home_url('/san-pham/'); ?>" class="btn btn-primary">
                        <i class="bi bi-bag"></i> Mua sắm ngay
                    </a>
                </div>
            <?php 
                else :
                    $selected_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            ?>
                <!-- Review Form -->
                <div style="background: #fff; border-radius: 25px; padding: 40px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1);">
                    <h3 style="text-align: center; margin-bottom: 30px;">
                        <i class="bi bi-star" style="color: #EC802B;"></i> Chọn sản phẩm để đánh giá
                    </h3>
                    
                    <!-- Product Selection Grid -->
                    <div class="product-select-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 35px;">
                        <?php foreach ($products_to_review as $product) : 
                            $is_selected = ($selected_product_id == $product['id']);
                        ?>
                        <div class="product-select-item <?php echo $is_selected ? 'selected' : ''; ?>" 
                             data-product-id="<?php echo esc_attr($product['id']); ?>"
                             data-product-name="<?php echo esc_attr($product['name']); ?>"
                             style="border: 2px solid <?php echo $is_selected ? '#EC802B' : '#E8CCAD'; ?>; 
                                    border-radius: 15px; 
                                    padding: 15px; 
                                    cursor: pointer; 
                                    text-align: center; 
                                    transition: all 0.3s;
                                    background: <?php echo $is_selected ? 'rgba(236, 128, 43, 0.05)' : '#fff'; ?>;">
                            <img src="<?php echo esc_url($product['image']); ?>" 
                                 alt="<?php echo esc_attr($product['name']); ?>" 
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                            <p style="font-size: 0.85rem; color: #5D4E37; margin: 0; font-weight: 500; line-height: 1.3;">
                                <?php echo esc_html(wp_trim_words($product['name'], 5, '...')); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Review Form -->
                    <form id="reviewForm" style="display: <?php echo $selected_product_id ? 'block' : 'none'; ?>;">
                        <input type="hidden" id="productId" name="product_id" value="<?php echo $selected_product_id; ?>">
                        
                        <div id="selectedProductName" style="text-align: center; margin-bottom: 25px; padding: 15px; background: #FDF8F3; border-radius: 12px;">
                            <span style="color: #7A6B5A;">Đang đánh giá:</span>
                            <strong style="color: #5D4E37; display: block; margin-top: 5px;" id="productNameDisplay">
                                <?php 
                                if ($selected_product_id) {
                                    foreach ($products_to_review as $p) {
                                        if ($p['id'] == $selected_product_id) {
                                            echo esc_html($p['name']);
                                            break;
                                        }
                                    }
                                }
                                ?>
                            </strong>
                        </div>
                        
                        <!-- Star Rating -->
                        <div style="text-align: center; margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #5D4E37;">Đánh giá của bạn *</label>
                            <div class="star-rating" id="starRating" style="display: inline-flex; gap: 8px;">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="star" data-rating="<?php echo $i; ?>" style="font-size: 2.5rem; color: #E8CCAD; cursor: pointer; transition: color 0.2s;">
                                    <i class="bi bi-star-fill"></i>
                                </span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="ratingValue" name="rating" value="0">
                            <p id="ratingText" style="color: #7A6B5A; margin: 10px 0 0; font-size: 0.9rem;">Nhấp vào sao để đánh giá</p>
                        </div>
                        
                        <!-- Review Content -->
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #5D4E37;">Nhận xét của bạn *</label>
                            <textarea id="reviewContent" name="review_content" 
                                      style="width: 100%; min-height: 150px; padding: 18px; border: 2px solid #E8CCAD; border-radius: 15px; font-size: 1rem; font-family: 'Quicksand', sans-serif; resize: vertical;"
                                      placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này... (Chất lượng, đóng gói, giao hàng...)"></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" id="submitReviewBtn" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                            <i class="bi bi-send"></i> Gửi đánh giá
                        </button>
                    </form>
                    
                    <!-- Message Area -->
                    <div id="reviewMessage" style="margin-top: 20px;"></div>
                </div>
            <?php 
                endif;
            endif; 
            ?>
        </div>
    </div>
</section>

<style>
.product-select-item:hover {
    border-color: #EC802B !important;
    transform: translateY(-3px);
}
.product-select-item.selected {
    border-color: #EC802B !important;
    background: rgba(236, 128, 43, 0.05) !important;
}
.star-rating .star:hover,
.star-rating .star.active {
    color: #f1c40f !important;
}
#reviewContent:focus {
    border-color: #EC802B;
    outline: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingTexts = {
        1: 'Rất tệ 😞',
        2: 'Tệ 😕',
        3: 'Bình thường 😐',
        4: 'Tốt 😊',
        5: 'Tuyệt vời 🤩'
    };
    
    // Product selection
    document.querySelectorAll('.product-select-item').forEach(item => {
        item.addEventListener('click', function() {
            // Update selected state
            document.querySelectorAll('.product-select-item').forEach(el => {
                el.classList.remove('selected');
                el.style.borderColor = '#E8CCAD';
                el.style.background = '#fff';
            });
            this.classList.add('selected');
            this.style.borderColor = '#EC802B';
            this.style.background = 'rgba(236, 128, 43, 0.05)';
            
            // Update form
            document.getElementById('productId').value = this.dataset.productId;
            document.getElementById('productNameDisplay').textContent = this.dataset.productName;
            document.getElementById('reviewForm').style.display = 'block';
            
            // Reset form
            document.getElementById('ratingValue').value = 0;
            document.getElementById('reviewContent').value = '';
            document.querySelectorAll('.star-rating .star').forEach(star => {
                star.style.color = '#E8CCAD';
                star.classList.remove('active');
            });
            document.getElementById('ratingText').textContent = 'Nhấp vào sao để đánh giá';
        });
    });
    
    // Star rating
    const starRating = document.getElementById('starRating');
    if (starRating) {
        starRating.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                document.getElementById('ratingValue').value = rating;
                document.getElementById('ratingText').textContent = ratingTexts[rating];
                
                starRating.querySelectorAll('.star').forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#f1c40f';
                        s.classList.add('active');
                    } else {
                        s.style.color = '#E8CCAD';
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                starRating.querySelectorAll('.star').forEach((s, index) => {
                    s.style.color = index < rating ? '#f1c40f' : '#E8CCAD';
                });
            });
        });
        
        starRating.addEventListener('mouseleave', function() {
            const currentRating = parseInt(document.getElementById('ratingValue').value);
            starRating.querySelectorAll('.star').forEach((s, index) => {
                s.style.color = index < currentRating ? '#f1c40f' : '#E8CCAD';
            });
        });
    }
    
    // Submit review
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = document.getElementById('productId').value;
            const rating = document.getElementById('ratingValue').value;
            const content = document.getElementById('reviewContent').value.trim();
            const submitBtn = document.getElementById('submitReviewBtn');
            const messageEl = document.getElementById('reviewMessage');
            
            // Validation
            if (!productId) {
                messageEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> Vui lòng chọn sản phẩm</div>';
                return;
            }
            if (!rating || rating == 0) {
                messageEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> Vui lòng chọn số sao</div>';
                return;
            }
            if (!content) {
                messageEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> Vui lòng nhập nội dung đánh giá</div>';
                return;
            }
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang gửi...';
            messageEl.innerHTML = '';
            
            // Send AJAX
            const formData = new FormData();
            formData.append('action', 'petshop_submit_review');
            formData.append('nonce', '<?php echo wp_create_nonce('petshop_review_nonce'); ?>');
            formData.append('product_id', productId);
            formData.append('rating', rating);
            formData.append('review_content', content);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    messageEl.innerHTML = '<div style="padding: 20px; background: #d4edda; color: #155724; border-radius: 10px; text-align: center;"><i class="bi bi-check-circle"></i> ' + data.data.message + '</div>';
                    
                    // Hide form and remove product from list
                    reviewForm.style.display = 'none';
                    const selectedItem = document.querySelector('.product-select-item.selected');
                    if (selectedItem) {
                        selectedItem.style.opacity = '0.5';
                        selectedItem.style.pointerEvents = 'none';
                        selectedItem.innerHTML += '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #66BCB4; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 0.75rem;">Đã đánh giá</div>';
                        selectedItem.style.position = 'relative';
                    }
                    
                    // Check if all products reviewed
                    const remainingProducts = document.querySelectorAll('.product-select-item:not([style*="opacity"])');
                    if (remainingProducts.length === 0) {
                        setTimeout(() => {
                            messageEl.innerHTML += '<div style="margin-top: 20px; padding: 20px; background: #FDF8F3; border-radius: 10px; text-align: center;"><p style="margin: 0 0 15px;">Bạn đã đánh giá tất cả sản phẩm!</p><a href="<?php echo home_url('/san-pham/'); ?>" class="btn btn-primary"><i class="bi bi-bag"></i> Tiếp tục mua sắm</a></div>';
                        }, 1000);
                    }
                } else {
                    messageEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> ' + (data.data?.message || 'Có lỗi xảy ra') + '</div>';
                }
            })
            .catch(err => {
                console.error(err);
                messageEl.innerHTML = '<div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;"><i class="bi bi-exclamation-circle"></i> Có lỗi xảy ra. Vui lòng thử lại.</div>';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send"></i> Gửi đánh giá';
            });
        });
    }
});
</script>

<?php get_footer(); ?>
