</main><!-- #main-content -->

<footer class="site-footer">
    <div class="site-container">
        <!-- Footer Main -->
        <div class="footer-main">
            <!-- Brand Column -->
            <div class="footer-brand">
                <span class="site-title"><i class="bi bi-heart-pulse-fill"></i> PetShop</span>
                <p>Chúng tôi cung cấp các sản phẩm và dịch vụ chất lượng cao cho thú cưng của bạn. Với hơn 10 năm kinh nghiệm, PetShop cam kết mang đến sự chăm sóc tốt nhất cho những người bạn 4 chân.</p>
                <div class="footer-social">
                    <a href="#" title="Facebook" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" title="Instagram" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" title="Youtube" aria-label="Youtube">
                        <i class="bi bi-youtube"></i>
                    </a>
                    <a href="#" title="TikTok" aria-label="TikTok">
                        <i class="bi bi-tiktok"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-column">
                <h4>Liên kết nhanh</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo home_url('/'); ?>"><i class="bi bi-chevron-right"></i> Trang chủ</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('product'); ?>"><i class="bi bi-chevron-right"></i> Sản phẩm</a></li>
                    <li><a href="<?php echo home_url('/dich-vu/'); ?>"><i class="bi bi-chevron-right"></i> Dịch vụ</a></li>
                    <li><a href="<?php echo home_url('/tin-tuc/'); ?>"><i class="bi bi-chevron-right"></i> Tin tức</a></li>
                    <li><a href="<?php echo home_url('/gioi-thieu/'); ?>"><i class="bi bi-chevron-right"></i> Giới thiệu</a></li>
                    <li><a href="<?php echo home_url('/lien-he/'); ?>"><i class="bi bi-chevron-right"></i> Liên hệ</a></li>
                </ul>
            </div>
            
            <!-- Product Categories -->
            <div class="footer-column">
                <h4>Danh mục sản phẩm</h4>
                <ul class="footer-links">
                    <?php
                    $footer_categories = get_terms(array(
                        'taxonomy' => 'product_category',
                        'hide_empty' => false,
                        'parent' => 0,
                        'number' => 6,
                    ));
                    
                    if (!empty($footer_categories) && !is_wp_error($footer_categories)) :
                        foreach ($footer_categories as $cat) :
                    ?>
                    <li><a href="<?php echo get_term_link($cat); ?>"><i class="bi bi-chevron-right"></i> <?php echo esc_html($cat->name); ?></a></li>
                    <?php 
                        endforeach;
                    else :
                    ?>
                    <li><a href="<?php echo get_post_type_archive_link('product'); ?>"><i class="bi bi-chevron-right"></i> Thức ăn</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('product'); ?>"><i class="bi bi-chevron-right"></i> Phụ kiện</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('product'); ?>"><i class="bi bi-chevron-right"></i> Đồ chơi</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('product'); ?>"><i class="bi bi-chevron-right"></i> Vệ sinh</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Contact & Newsletter -->
            <div class="footer-column footer-newsletter">
                <h4>Liên hệ</h4>
                <ul class="footer-contact">
                    <li>
                        <a href="https://maps.google.com/?q=123+Đường+ABC,+Quận+1,+TP.HCM"
                           target="_blank" rel="noopener"
                           style="color:inherit;display:flex;align-items:flex-start;gap:8px;text-decoration:none;transition:color .2s;"
                           onmouseover="this.style.color='#EC802B'" onmouseout="this.style.color=''">
                            <i class="bi bi-geo-alt-fill" style="color:#EC802B;flex-shrink:0;margin-top:2px;"></i>
                            123 Đường ABC, Quận 1, TP.HCM
                        </a>
                    </li>
                    <li>
                        <a href="tel:0123456789"
                           style="color:inherit;display:flex;align-items:center;gap:8px;text-decoration:none;transition:color .2s;"
                           onmouseover="this.style.color='#EC802B'" onmouseout="this.style.color=''">
                            <i class="bi bi-telephone-fill" style="color:#EC802B;flex-shrink:0;"></i>
                            Hotline: 0123 456 789
                        </a>
                    </li>
                    <li>
                        <a href="mailto:info@petshop.com"
                           style="color:inherit;display:flex;align-items:center;gap:8px;text-decoration:none;transition:color .2s;"
                           onmouseover="this.style.color='#EC802B'" onmouseout="this.style.color=''">
                            <i class="bi bi-envelope-fill" style="color:#EC802B;flex-shrink:0;"></i>
                            info@petshop.com
                        </a>
                    </li>
                </ul>
                
                <p style="margin-top: 20px; margin-bottom: 15px;">Đăng ký nhận tin khuyến mãi:</p>
                <form class="newsletter-form" action="#" method="post">
                    <input type="email" name="email" placeholder="Email của bạn..." required>
                    <button type="submit">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> PetShop. Thiết kế bởi <strong style="color: #EC802B;">Đỗ Hoài Thanh Quyên</strong> - MSSV: 2274802010740</p>
            <div class="footer-payments">
                <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">Thanh toán:</span>
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none">
                    <rect width="40" height="25" rx="4" fill="#1A1F71"/>
                    <text x="8" y="17" fill="white" font-size="10" font-weight="bold">VISA</text>
                </svg>
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none">
                    <rect width="40" height="25" rx="4" fill="#EB001B"/>
                    <circle cx="15" cy="12.5" r="8" fill="#EB001B"/>
                    <circle cx="25" cy="12.5" r="8" fill="#F79E1B"/>
                    <path d="M20 6.5a8 8 0 0 0 0 12" fill="#FF5F00"/>
                </svg>
                <svg width="40" height="25" viewBox="0 0 40 25" fill="none">
                    <rect width="40" height="25" rx="4" fill="#003087"/>
                    <text x="6" y="16" fill="white" font-size="8" font-weight="bold">PayPal</text>
                </svg>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Về đầu trang">
    <i class="bi bi-chevron-up"></i>
</button>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Login Modal
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const closeModal = document.getElementById('closeModal');
    
    if (loginBtn && loginModal) {
        loginBtn.addEventListener('click', function() {
            loginModal.style.display = 'flex';
            loginModal.classList.add('active');
            loginModal.setAttribute('aria-hidden', 'false');
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            loginModal.style.display = 'none';
            loginModal.classList.remove('active');
            loginModal.setAttribute('aria-hidden', 'true');
        });
    }
    
    window.addEventListener('click', function(e) {
        if (e.target === loginModal) {
            loginModal.style.display = 'none';
            loginModal.classList.remove('active');
        }
    });
    
    // Mobile Menu
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
    
    // Back to Top
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    if (backToTop) {
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // ===== POST INTERACTION FEATURES =====
    
    // Like Button Functionality
    const likeBtns = document.querySelectorAll('.like-btn, .like-btn-bottom, .action-icon-btn.like-btn');
    likeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const isLiked = this.classList.toggle('liked');
            const icon = this.querySelector('i');
            
            if (isLiked) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                showToast('❤️ Đã thêm vào yêu thích!', 'success');
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                showToast('💔 Đã bỏ yêu thích', 'info');
            }
            
            // Sync all like buttons for same post
            likeBtns.forEach(otherBtn => {
                if (otherBtn !== this && otherBtn.dataset.postId === postId) {
                    if (isLiked) {
                        otherBtn.classList.add('liked');
                        const otherIcon = otherBtn.querySelector('i');
                        if (otherIcon) {
                            otherIcon.classList.remove('bi-heart');
                            otherIcon.classList.add('bi-heart-fill');
                        }
                    } else {
                        otherBtn.classList.remove('liked');
                        const otherIcon = otherBtn.querySelector('i');
                        if (otherIcon) {
                            otherIcon.classList.remove('bi-heart-fill');
                            otherIcon.classList.add('bi-heart');
                        }
                    }
                }
            });
            
            // Save to localStorage
            let likedPosts = JSON.parse(localStorage.getItem('petshop_liked_posts') || '[]');
            if (isLiked && !likedPosts.includes(postId)) {
                likedPosts.push(postId);
            } else if (!isLiked) {
                likedPosts = likedPosts.filter(id => id !== postId);
            }
            localStorage.setItem('petshop_liked_posts', JSON.stringify(likedPosts));
        });
    });
    
    // Load liked state from localStorage
    const likedPosts = JSON.parse(localStorage.getItem('petshop_liked_posts') || '[]');
    likeBtns.forEach(btn => {
        if (likedPosts.includes(btn.dataset.postId)) {
            btn.classList.add('liked');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            }
        }
    });
    
    // Save/Bookmark Button Functionality
    const saveBtns = document.querySelectorAll('.save-btn, .action-icon-btn.save-btn');
    saveBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const isSaved = this.classList.toggle('saved');
            const icon = this.querySelector('i');
            
            if (isSaved) {
                icon.classList.remove('bi-bookmark');
                icon.classList.add('bi-bookmark-fill');
                showToast('🔖 Đã lưu bài viết!', 'success');
            } else {
                icon.classList.remove('bi-bookmark-fill');
                icon.classList.add('bi-bookmark');
                showToast('📌 Đã bỏ lưu bài viết', 'info');
            }
            
            // Save to localStorage
            let savedPosts = JSON.parse(localStorage.getItem('petshop_saved_posts') || '[]');
            if (isSaved && !savedPosts.includes(postId)) {
                savedPosts.push(postId);
            } else if (!isSaved) {
                savedPosts = savedPosts.filter(id => id !== postId);
            }
            localStorage.setItem('petshop_saved_posts', JSON.stringify(savedPosts));
        });
    });
    
    // Load saved state from localStorage
    const savedPosts = JSON.parse(localStorage.getItem('petshop_saved_posts') || '[]');
    saveBtns.forEach(btn => {
        if (savedPosts.includes(btn.dataset.postId)) {
            btn.classList.add('saved');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-bookmark');
                icon.classList.add('bi-bookmark-fill');
            }
        }
    });
    
    // Share Dropdown Toggle
    const shareToggles = document.querySelectorAll('.share-toggle-btn, .action-icon-btn.share-toggle-btn');
    shareToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.share-dropdown');
            dropdown.classList.toggle('active');
        });
    });
    
    // Close share dropdown when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.share-dropdown.active').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
    
    // Copy Link Functionality
    const copyLinkBtns = document.querySelectorAll('.copy-link-btn');
    copyLinkBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            
            navigator.clipboard.writeText(url).then(() => {
                this.classList.add('copied');
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check-lg"></i> Đã sao chép!';
                showToast('📋 Đã sao chép link!', 'success');
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                showToast('Không thể sao chép link', 'error');
            });
        });
    });
    
    // Comment Like Button
    const commentLikeBtns = document.querySelectorAll('.comment-like-btn');
    commentLikeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const isLiked = this.classList.toggle('liked');
            const icon = this.querySelector('i');
            
            if (isLiked) {
                icon.classList.remove('bi-hand-thumbs-up');
                icon.classList.add('bi-hand-thumbs-up-fill');
            } else {
                icon.classList.remove('bi-hand-thumbs-up-fill');
                icon.classList.add('bi-hand-thumbs-up');
            }
        });
    });
    
    // Toast Notification Function
    function showToast(message, type = 'info') {
        // Remove existing toast
        const existingToast = document.querySelector('.petshop-toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `petshop-toast petshop-toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button class="toast-close"><i class="bi bi-x"></i></button>
        `;
        
        // Styling
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '30px',
            right: '30px',
            padding: '15px 25px',
            background: type === 'success' ? 'linear-gradient(135deg, #66BCB4, #7ECEC6)' : 
                        type === 'error' ? 'linear-gradient(135deg, #E74C3C, #C0392B)' : 
                        'linear-gradient(135deg, #EC802B, #F5994D)',
            color: '#fff',
            borderRadius: '15px',
            boxShadow: '0 10px 40px rgba(0,0,0,0.2)',
            display: 'flex',
            alignItems: 'center',
            gap: '15px',
            zIndex: '10000',
            fontFamily: "'Quicksand', sans-serif",
            fontWeight: '600',
            animation: 'slideInRight 0.4s ease',
            maxWidth: '350px'
        });
        
        // Add animation keyframes if not exists
        if (!document.querySelector('#toast-animation')) {
            const style = document.createElement('style');
            style.id = 'toast-animation';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .petshop-toast .toast-close {
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: #fff;
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.3s;
                }
                .petshop-toast .toast-close:hover {
                    background: rgba(255,255,255,0.4);
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(toast);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        });
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }
    
    // ===== GLOBAL CART FUNCTIONS =====
    
    // Update cart count on page load - dùng cart key theo user
    function updateGlobalCartCount() {
        const cartKey = window.getCartKey ? window.getCartKey() : 'petshop_cart_guest';
        const cart = JSON.parse(localStorage.getItem(cartKey)) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = totalItems;
            el.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }
    
    // Initialize cart count
    updateGlobalCartCount();
    
    // Make it available globally
    window.updateGlobalCartCount = updateGlobalCartCount;
});
</script>

</body>
</html>