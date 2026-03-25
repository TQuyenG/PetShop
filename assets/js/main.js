// =============================================
        // Keyword Filter Row - Arrow & Drag Scroll
        // =============================================
        const keywordRow = document.querySelector('.keyword-filter-row');
        const leftArrow = document.querySelector('.keyword-arrow-left');
        const rightArrow = document.querySelector('.keyword-arrow-right');
        if (keywordRow) {
            // Arrow scroll
            if (leftArrow) {
                leftArrow.addEventListener('click', function() {
                    keywordRow.scrollBy({left: -200, behavior: 'smooth'});
                });
            }
            if (rightArrow) {
                rightArrow.addEventListener('click', function() {
                    keywordRow.scrollBy({left: 200, behavior: 'smooth'});
                });
            }
            // Drag scroll
            let isDown = false;
            let startX;
            let scrollLeft;
            keywordRow.addEventListener('mousedown', (e) => {
                isDown = true;
                keywordRow.classList.add('dragging');
                startX = e.pageX - keywordRow.offsetLeft;
                scrollLeft = keywordRow.scrollLeft;
            });
            keywordRow.addEventListener('mouseleave', () => {
                isDown = false;
                keywordRow.classList.remove('dragging');
            });
            keywordRow.addEventListener('mouseup', () => {
                isDown = false;
                keywordRow.classList.remove('dragging');
            });
            keywordRow.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - keywordRow.offsetLeft;
                const walk = (x - startX) * 2;
                keywordRow.scrollLeft = scrollLeft - walk;
            });
            // Touch support
            keywordRow.addEventListener('touchstart', (e) => {
                isDown = true;
                startX = e.touches[0].pageX - keywordRow.offsetLeft;
                scrollLeft = keywordRow.scrollLeft;
            });
            keywordRow.addEventListener('touchend', () => {
                isDown = false;
            });
            keywordRow.addEventListener('touchmove', (e) => {
                if (!isDown) return;
                const x = e.touches[0].pageX - keywordRow.offsetLeft;
                const walk = (x - startX) * 2;
                keywordRow.scrollLeft = scrollLeft - walk;
            });
        }
    // =============================================
    // Keyword Filter Row - Horizontal Scroll
    // =============================================
    const keywordRow2 = document.querySelector('.keyword-filter-row');
    if (keywordRow2) {
        let isDown = false;
        let startX;
        let scrollLeft;
        keywordRow2.addEventListener('mousedown', (e) => {
            isDown = true;
            keywordRow2.classList.add('dragging');
            startX = e.pageX - keywordRow2.offsetLeft;
            scrollLeft = keywordRow2.scrollLeft;
        });
        keywordRow2.addEventListener('mouseleave', () => {
            isDown = false;
            keywordRow2.classList.remove('dragging');
        });
        keywordRow2.addEventListener('mouseup', () => {
            isDown = false;
            keywordRow2.classList.remove('dragging');
        });
        keywordRow2.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - keywordRow2.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            keywordRow2.scrollLeft = scrollLeft - walk;
        });
        // Touch support
        keywordRow2.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - keywordRow2.offsetLeft;
            scrollLeft = keywordRow2.scrollLeft;
        });
        keywordRow2.addEventListener('touchend', () => {
            isDown = false;
        });
        keywordRow2.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            const x = e.touches[0].pageX - keywordRow2.offsetLeft;
            const walk = (x - startX) * 2;
            keywordRow2.scrollLeft = scrollLeft - walk;
        });
    }
// Banner slider logic
(function() {
    const slider = document.getElementById('petshopBannerSlider');
    if (!slider) return;
    const slides = slider.querySelectorAll('.banner-slide');
    const dots = slider.querySelectorAll('.banner-slider-dot');
    const prevBtn = slider.querySelector('.banner-slider-prev');
    const nextBtn = slider.querySelector('.banner-slider-next');
    let current = 0;
    let timer = null;
    function showSlide(idx) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === idx);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === idx);
        });
        current = idx;
    }
    function nextSlide() {
        showSlide((current + 1) % slides.length);
    }
    function prevSlide() {
        showSlide((current - 1 + slides.length) % slides.length);
    }
    function startAuto() {
        timer = setInterval(nextSlide, 5000);
    }
    function stopAuto() {
        if (timer) clearInterval(timer);
    }
    nextBtn.addEventListener('click', function() {
        stopAuto();
        nextSlide();
        startAuto();
    });
    prevBtn.addEventListener('click', function() {
        stopAuto();
        prevSlide();
        startAuto();
    });
    dots.forEach((dot, i) => {
        dot.addEventListener('click', function() {
            stopAuto();
            showSlide(i);
            startAuto();
        });
    });
    showSlide(0);
    startAuto();
})();
/**
 * PetShop Theme - Main JavaScript
 * 
 * @package PetShop
 * @version 2.0
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Safety: reset overflow nếu bị kẹt từ trang trước (modal/menu chưa đóng)
    document.body.style.overflow = '';
    document.body.classList.remove('menu-open');

    // =============================================
    // Mobile Menu Toggle
    // =============================================
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                mainNav.classList.remove('active');
                menuToggle.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }

    // =============================================
    // Smooth Scroll for Anchor Links
    // =============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                const headerHeight = document.querySelector('.site-header').offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // =============================================
    // Back to Top Button
    // =============================================
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // =============================================
    // Login Modal
    // =============================================
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const closeModal = document.getElementById('closeModal');
    
    if (loginBtn && loginModal) {
        loginBtn.addEventListener('click', function() {
            loginModal.style.display = 'flex';
            loginModal.classList.add('active');
            loginModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });

        const closeLoginModal = function() {
            loginModal.style.display = 'none';
            loginModal.classList.remove('active');
            loginModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        if (closeModal) {
            closeModal.addEventListener('click', closeLoginModal);
        }

        window.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && loginModal.classList.contains('active')) {
                closeLoginModal();
            }
        });
    }

    // =============================================
    // Product Tabs
    // =============================================
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Add tab filter logic here if needed
        });
    });

    // =============================================
    // Add to Cart Animation
    // =============================================
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Track add_to_cart event for analytics
            if (typeof window.petshopTrackEvent === 'function') {
                const productCard = this.closest('.product-card, .product-info');
                const productId = this.dataset.productId || productCard?.dataset?.productId || 0;
                const productName = productCard?.querySelector('.product-title, .product-name, h1, h2, h3')?.textContent?.trim() || '';
                const priceEl = productCard?.querySelector('.price, .product-price');
                const productPrice = priceEl ? parseInt(priceEl.textContent.replace(/[^\d]/g, '')) : 0;
                
                window.petshopTrackEvent('add_to_cart', {
                    product_id: productId,
                    product_name: productName,
                    product_price: productPrice,
                    quantity: 1
                });
            }
            
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="loading">Đang thêm...</span>';
            this.disabled = true;
            
            // Simulate adding to cart
            setTimeout(() => {
                this.innerHTML = '✓ Đã thêm';
                this.style.background = '#4ECDC4';
                this.style.borderColor = '#4ECDC4';
                this.style.color = '#fff';
                
                // Update cart count
                const cartBadge = document.querySelector('.icon-btn .badge');
                if (cartBadge) {
                    const currentCount = parseInt(cartBadge.textContent) || 0;
                    cartBadge.textContent = currentCount + 1;
                    cartBadge.style.animation = 'pulse 0.3s ease';
                }
                
                // Reset button after delay
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = '';
                    this.style.borderColor = '';
                    this.style.color = '';
                    this.disabled = false;
                }, 2000);
            }, 500);
        });
    });

    // =============================================
    // Wishlist Toggle
    // =============================================
    const wishlistButtons = document.querySelectorAll('.action-btn[title="Yêu thích"]');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productCard = this.closest('.product-card, .product-info');
            const productId = this.dataset.productId || productCard?.dataset?.productId || this.getAttribute('data-id') || '';
            if (!productId) return;
            const isActive = this.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            const btn = this;
            btn.disabled = true;
            fetch((typeof petshopData !== 'undefined' ? petshopData.ajaxUrl : '/wp-admin/admin-ajax.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=petshop_toggle_favorite&product_id=${productId}&action_favorite=${action}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('active');
                    if (btn.classList.contains('active')) {
                        btn.innerHTML = '❤️';
                        btn.style.background = '#FF6B6B';
                        btn.style.color = '#fff';
                    } else {
                        btn.innerHTML = '🤍';
                        btn.style.background = '#fff';
                        btn.style.color = '#2C3E50';
                    }
                    showPetshopToast(data.data.message || 'Thành công!');
                } else {
                    showPetshopToast(data.data && data.data.message ? data.data.message : 'Có lỗi xảy ra!', true);
                }
            })
            .catch(() => {
                showPetshopToast('Có lỗi xảy ra!', true);
            })
            .finally(() => { btn.disabled = false; });
        });
    });

    // Hàm hiển thị toast thông báo
    function showPetshopToast(msg, isError) {
        let toast = document.createElement('div');
        toast.className = 'petshop-toast' + (isError ? ' error' : '');
        toast.textContent = msg;
        toast.style.cssText = 'position:fixed;top:80px;right:30px;z-index:9999;background:#fff;padding:14px 28px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.12);color:'+(isError?'#e74c3c':'#2ecc71')+';font-weight:600;font-size:1rem;opacity:0.95;';
        document.body.appendChild(toast);
        setTimeout(()=>{ toast.remove(); }, 2200);
    }

    // =============================================
    // Newsletter Form
    // =============================================
    const newsletterForm = document.querySelector('.newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            
            if (email) {
                button.innerHTML = '✓';
                button.style.background = '#4ECDC4';
                
                setTimeout(() => {
                    alert('Cảm ơn bạn đã đăng ký! Chúng tôi sẽ gửi thông tin khuyến mãi đến email của bạn.');
                    this.reset();
                    button.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/></svg>';
                    button.style.background = '';
                }, 500);
            }
        });
    }

    // =============================================
    // Lazy Load Images
    // =============================================
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // =============================================
    // Animate on Scroll
    // =============================================
    if ('IntersectionObserver' in window) {
        const animateElements = document.querySelectorAll('.feature-card, .product-card, .news-card, .category-card');
        
        const animateObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });
        }, {
            threshold: 0.1
        });

        animateElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            animateObserver.observe(el);
        });
    }

    // =============================================
    // Header Scroll Effect
    // =============================================
    const header = document.querySelector('.site-header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });

    // =============================================
    // Search Form Enhancement
    // =============================================
    const searchForms = document.querySelectorAll('.search-form');
    
    searchForms.forEach(form => {
        const input = form.querySelector('input[type="search"]');
        
        if (input) {
            input.addEventListener('focus', function() {
                form.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                form.classList.remove('focused');
            });
        }
    });

    // =============================================
    // Console Easter Egg
    // =============================================
    console.log('%c🐾 PetShop Theme v2.0', 'font-size: 24px; font-weight: bold; color: #FF6B6B;');
    console.log('%cThiết kế bởi Đỗ Hoài Thanh Quyên - MSSV: 2274802010740', 'font-size: 12px; color: #4ECDC4;');
});

// =============================================
// CSS Keyframes (injected via JS)
// =============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .loading {
        display: inline-block;
        animation: pulse 0.5s ease infinite;
    }
    
    .main-nav.active {
        display: flex !important;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 20px;
        z-index: 1000;
    }
    
    .main-nav.active .nav-menu {
        flex-direction: column;
        width: 100%;
    }
    
    .main-nav.active .nav-menu li a {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    
    .menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }
    
    .menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }
    
    body.menu-open {
        overflow: hidden;
    }
    
    .site-header.scrolled {
        box-shadow: 0 5px 30px rgba(0,0,0,0.15);
    }
`;
document.head.appendChild(style);

// Reset overflow khi navigate bằng back/forward button
window.addEventListener('pageshow', function() {
    document.body.style.overflow = '';
    document.body.classList.remove('menu-open');
    // Đóng tất cả modals nếu còn mở
    const modals = document.querySelectorAll('[id$="Modal"],[id$="-modal"]');
    modals.forEach(m => {
        m.style.display = 'none';
        m.classList.remove('active');
    });
});