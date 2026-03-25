<?php
/**
 * Template Name: Trang Giỏ Hàng
 * Giao diện kiểu Shopee với phân chia sản phẩm còn hàng/hết hàng
 * 
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-bag-check"></i> Giỏ Hàng</h1>
        <p>Xem lại các sản phẩm bạn đã chọn</p>
    </div>
</div>

<section class="cart-section" style="padding: 60px 0;">
    <div class="container">
        <?php petshop_breadcrumb(); ?>
        
        <!-- Cart Progress -->
        <div class="cart-progress" style="display: flex; justify-content: center; align-items: center; margin: 30px 0 50px; gap: 10px;">
            <div class="progress-step active" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700;">1</div>
                <span style="font-weight: 600; color: #EC802B;">Giỏ hàng</span>
            </div>
            <div style="width: 80px; height: 3px; background: #E8CCAD;"></div>
            <div class="progress-step" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #E8CCAD; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #7A6B5A; font-weight: 700;">2</div>
                <span style="color: #7A6B5A;">Thanh toán</span>
            </div>
            <div style="width: 80px; height: 3px; background: #E8CCAD;"></div>
            <div class="progress-step" style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; background: #E8CCAD; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #7A6B5A; font-weight: 700;">3</div>
                <span style="color: #7A6B5A;">Hoàn tất</span>
            </div>
        </div>
        
        <!-- Stock Change Notification Modal -->
        <div id="stockChangeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: #fff; border-radius: 20px; padding: 30px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                <i class="bi bi-exclamation-triangle" style="font-size: 4rem; color: #ff9800; display: block; margin-bottom: 15px;"></i>
                <h3 style="margin-bottom: 15px; color: #5D4E37;">Có thay đổi trong giỏ hàng</h3>
                <p id="stockChangeMessage" style="color: #7A6B5A; margin-bottom: 25px;"></p>
                <button onclick="closeStockModal()" class="btn btn-primary" style="padding: 12px 40px;">Đã hiểu</button>
            </div>
        </div>
        
        <!-- Empty Cart Message (hidden by default) -->
        <div id="emptyCart" style="display: none; text-align: center; padding: 80px 20px;">
            <i class="bi bi-bag-x" style="font-size: 5rem; color: #E8CCAD; margin-bottom: 20px; display: block;"></i>
            <h2 style="margin-bottom: 15px; color: #5D4E37;">Giỏ hàng trống</h2>
            <p style="color: #7A6B5A; margin-bottom: 30px;">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="<?php echo get_post_type_archive_link('product'); ?>" class="btn btn-primary">
                <i class="bi bi-cart-plus"></i> Mua sắm ngay
            </a>
        </div>
        
        <!-- Cart Content -->
        <div id="cartContent" class="cart-container" style="display: none; grid-template-columns: 1fr 380px; gap: 40px; margin-top: 30px;">
            <!-- Cart Items -->
            <div class="cart-items">
                <!-- Available Products Section -->
                <div id="availableSection">
                    <div class="cart-header" style="display: grid; grid-template-columns: auto 3fr 1fr 1fr 1fr auto; gap: 20px; padding: 15px 20px; background: #FDF8F3; border-radius: 15px; font-weight: 600; color: #5D4E37; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="selectAll" style="width: 18px; height: 18px; accent-color: #EC802B; cursor: pointer;">
                            <span>Tất cả</span>
                        </label>
                        <span>Sản phẩm</span>
                        <span style="text-align: center;">Đơn giá</span>
                        <span style="text-align: center;">Số lượng</span>
                        <span style="text-align: center;">Thành tiền</span>
                        <span></span>
                    </div>
                    <div id="availableItemsList"></div>
                </div>
                
                <!-- Out of Stock Section (Shopee style) -->
                <div id="outOfStockSection" style="display: none; margin-top: 30px;">
                    <div style="background: #ffebee; padding: 15px 20px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-exclamation-circle-fill" style="color: #c62828; font-size: 1.3rem;"></i>
                            <span style="font-weight: 600; color: #c62828;">Sản phẩm không thể mua</span>
                            <span id="outOfStockCount" style="background: #c62828; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 0.85rem;"></span>
                        </div>
                        <button id="clearOutOfStockBtn" style="background: none; border: none; color: #c62828; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                            <i class="bi bi-trash3"></i> Xóa tất cả
                        </button>
                    </div>
                    <div id="outOfStockItemsList" style="background: #fff; border: 2px solid #ffcdd2; border-top: none; border-radius: 0 0 15px 15px; padding: 15px;"></div>
                </div>
                
                <!-- Cart Actions -->
                <div class="cart-actions" style="display: flex; justify-content: space-between; margin-top: 25px;">
                    <a href="<?php echo get_post_type_archive_link('product'); ?>" class="btn btn-outline">
                        <i class="bi bi-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                    <div style="display: flex; gap: 10px;">
                        <button id="deleteSelectedBtn" class="btn btn-outline" style="color: #E74C3C; border-color: #E74C3C; display: none;">
                            <i class="bi bi-trash3"></i> Xóa đã chọn (<span id="selectedCount">0</span>)
                        </button>
                        <button id="clearCartBtn" class="btn btn-outline" style="color: #E74C3C; border-color: #E74C3C;">
                            <i class="bi bi-trash3"></i> Xóa tất cả
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <div style="background: #fff; border-radius: 25px; padding: 30px; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); position: sticky; top: 100px;">
                    <h3 style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #FDF8F3;">
                        <i class="bi bi-receipt"></i> Tóm tắt đơn hàng
                    </h3>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: #7A6B5A;">Tạm tính (<span id="summaryCount">0</span> sản phẩm)</span>
                        <span id="summarySubtotal" style="font-weight: 600;">0đ</span>
                    </div>
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: #7A6B5A;">Phí vận chuyển</span>
                        <span id="summaryShipping" style="font-weight: 600;">0đ</span>
                    </div>
                    <div id="shippingDiscountRow" class="summary-row" style="display: none; justify-content: space-between; margin-bottom: 15px; color: #66BCB4;">
                        <span><i class="bi bi-truck"></i> Giảm phí ship</span>
                        <span id="summaryShippingDiscount" style="font-weight: 600;">-0đ</span>
                    </div>
                    <div id="discountRow" class="summary-row" style="display: none; justify-content: space-between; margin-bottom: 20px; color: #EC802B;">
                        <span><i class="bi bi-tag"></i> Giảm giá sản phẩm</span>
                        <span id="summaryDiscount" style="font-weight: 600;">-0đ</span>
                    </div>
                    
                    <!-- Coupon -->
                    <div class="coupon-form" style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5D4E37;">Mã giảm giá</label>
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="text" id="couponInput" placeholder="Nhập mã..." style="flex: 1; padding: 12px 15px; border: 2px solid #E8CCAD; border-radius: 10px; font-family: 'Quicksand', sans-serif;">
                            <button id="applyCouponBtn" class="btn btn-outline" style="padding: 12px 20px;">Áp dụng</button>
                        </div>
                        <button type="button" id="viewCouponsBtn" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #FFF5EB 0%, #FDF8F3 100%); border: 2px dashed #EC802B; border-radius: 10px; color: #EC802B; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                            <i class="bi bi-ticket-perforated"></i> Xem tất cả mã giảm giá
                        </button>
                        <span id="couponMessage" style="display: none; font-size: 0.85rem; margin-top: 8px;"></span>
                    </div>
                    
                    <div class="summary-total" style="display: flex; justify-content: space-between; padding-top: 20px; border-top: 2px solid #FDF8F3; margin-bottom: 25px;">
                        <span style="font-size: 1.1rem; font-weight: 700;">Tổng cộng</span>
                        <span id="summaryTotal" style="font-size: 1.5rem; font-weight: 700; color: #EC802B;">0đ</span>
                    </div>
                    
                    <button id="checkoutBtn" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="bi bi-credit-card"></i> Tiến hành thanh toán
                    </button>
                    
                    <!-- Trust Badges -->
                    <div class="trust-badges" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #FDF8F3;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #7A6B5A; font-size: 0.9rem;">
                            <i class="bi bi-shield-check" style="color: #66BCB4;"></i>
                            Thanh toán an toàn & bảo mật
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #7A6B5A; font-size: 0.9rem;">
                            <i class="bi bi-truck" style="color: #66BCB4;"></i>
                            Giao hàng nhanh 2-4 giờ
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; color: #7A6B5A; font-size: 0.9rem;">
                            <i class="bi bi-arrow-repeat" style="color: #66BCB4;"></i>
                            Đổi trả trong 7 ngày
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Coupon Modal (Shopee Style) -->
<div id="couponModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="coupon-modal-content" style="background: #fff; border-radius: 20px; width: 90%; max-width: 500px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
        <div class="coupon-modal-header" style="padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);">
            <div style="display: flex; align-items: center; gap: 10px; color: #fff;">
                <i class="bi bi-ticket-perforated" style="font-size: 1.5rem;"></i>
                <h3 style="margin: 0; font-size: 1.1rem;">Chọn mã giảm giá</h3>
            </div>
            <button onclick="closeCouponModal()" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; line-height: 1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="coupon-modal-body" style="flex: 1; overflow-y: auto; padding: 0;">
            <!-- Available Coupons -->
            <div class="coupon-section">
                <div style="padding: 15px 20px; background: #f6f6f6; font-weight: 600; color: #5D4E37; display: flex; align-items: center; gap: 8px; position: sticky; top: 0; z-index: 1;">
                    <i class="bi bi-check-circle-fill" style="color: #66BCB4;"></i>
                    <span>Có thể áp dụng</span>
                    <span id="availableCount" style="background: #66BCB4; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: auto;">0</span>
                </div>
                <div id="availableCoupons" style="padding: 15px;"></div>
            </div>
            
            <!-- Unavailable Coupons -->
            <div class="coupon-section">
                <div style="padding: 15px 20px; background: #f6f6f6; font-weight: 600; color: #7A6B5A; display: flex; align-items: center; gap: 8px; position: sticky; top: 0; z-index: 1;">
                    <i class="bi bi-x-circle-fill" style="color: #999;"></i>
                    <span>Chưa đủ điều kiện</span>
                    <span id="unavailableCount" style="background: #999; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: auto;">0</span>
                </div>
                <div id="unavailableCoupons" style="padding: 15px;"></div>
            </div>
            
            <!-- Loading -->
            <div id="couponLoading" style="padding: 50px; text-align: center; color: #999;">
                <i class="bi bi-arrow-repeat" style="font-size: 2rem; animation: spin 1s linear infinite;"></i>
                <p style="margin: 10px 0 0;">Đang tải mã giảm giá...</p>
            </div>
            
            <!-- Empty State -->
            <div id="couponEmpty" style="display: none; padding: 50px; text-align: center; color: #999;">
                <i class="bi bi-ticket-perforated" style="font-size: 3rem; opacity: 0.3;"></i>
                <p style="margin: 15px 0 0;">Không có mã giảm giá khả dụng</p>
            </div>
        </div>
    </div>
</div>

<style>
.remove-item:hover {
    background: #FDEAEA !important;
    color: #E74C3C !important;
}
.qty-btn {
    color: #5D4E37;
    font-weight: 600;
}
.qty-btn:hover {
    background: #EC802B !important;
    color: white !important;
}
.cart-item:hover {
    box-shadow: 0 10px 30px rgba(93, 78, 55, 0.15) !important;
}
.cart-item.out-of-stock {
    opacity: 0.7;
    background: #fafafa !important;
}
.cart-item.out-of-stock img {
    filter: grayscale(50%);
}
.stock-warning {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    display: inline-block;
    margin-top: 5px;
}
@media (max-width: 992px) {
    .cart-container {
        grid-template-columns: 1fr !important;
    }
    .cart-header {
        display: none !important;
    }
    .cart-item {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
    .item-info {
        flex-direction: column;
        text-align: center;
    }
    .cart-progress {
        flex-wrap: wrap;
    }
}

/* Coupon Modal Styles */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.coupon-card-item {
    background: #fff;
    border: 2px solid #E8CCAD;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
}
.coupon-card-item:hover {
    border-color: #EC802B;
    box-shadow: 0 5px 15px rgba(236, 128, 43, 0.15);
}
.coupon-card-item.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #fafafa;
}
.coupon-card-item.disabled:hover {
    border-color: #E8CCAD;
    box-shadow: none;
}
.coupon-card-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
}
.coupon-card-item.disabled::before {
    background: #ccc;
}
.coupon-card-item.freeship::before {
    background: linear-gradient(135deg, #66BCB4 0%, #8DD5CE 100%);
}
.coupon-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #EC802B;
    margin-bottom: 5px;
}
.coupon-card-item.disabled .coupon-value {
    color: #999;
}
.coupon-card-item.freeship .coupon-value {
    color: #66BCB4;
}
.coupon-name {
    font-weight: 600;
    color: #5D4E37;
    margin-bottom: 5px;
}
.coupon-condition {
    font-size: 0.85rem;
    color: #7A6B5A;
    margin-bottom: 8px;
}
.coupon-expire {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8rem;
    color: #999;
}
.coupon-reason {
    background: #fff3e0;
    color: #e65100;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    margin-top: 8px;
}
.coupon-select-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    padding: 8px 20px;
    background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
    color: #fff;
    border: none;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.3s;
}
.coupon-select-btn:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 5px 15px rgba(236, 128, 43, 0.3);
}
#viewCouponsBtn:hover {
    background: #EC802B;
    color: #fff;
    border-color: #EC802B;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lấy settings từ PHP
    <?php 
    $shipping_settings = petshop_get_shipping_settings_for_js();
    ?>
    const SHIPPING_FEE = <?php echo $shipping_settings['shipping_fee']; ?>;
    const FREE_SHIPPING_THRESHOLD = <?php echo $shipping_settings['free_shipping_threshold']; ?>;
    const ENABLE_FREE_SHIPPING = <?php echo $shipping_settings['enable_free_shipping'] ? 'true' : 'false'; ?>;
    const AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    let appliedCoupon = null;
    let selectedItems = new Set();
    let stockInfo = {};
    
    // Lấy key giỏ hàng theo user
    function getCartKey() {
        const userId = window.PETSHOP_USER?.userId || 0;
        return userId > 0 ? `petshop_cart_user_${userId}` : 'petshop_cart_guest';
    }
    
    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    }
    
    function getCart() {
        const key = getCartKey();
        return JSON.parse(localStorage.getItem(key)) || [];
    }
    
    function saveCart(cart) {
        const key = getCartKey();
        localStorage.setItem(key, JSON.stringify(cart));
        if (typeof window.updateGlobalCartCount === 'function') {
            window.updateGlobalCartCount();
        }
    }
    
    window.closeStockModal = function() {
        document.getElementById('stockChangeModal').style.display = 'none';
    }
    
    function showStockChangeModal(messages) {
        const modal = document.getElementById('stockChangeModal');
        const messageEl = document.getElementById('stockChangeMessage');
        messageEl.innerHTML = messages.join('<br>');
        modal.style.display = 'flex';
    }
    
    async function checkStockRealtime() {
        const cart = getCart();
        if (cart.length === 0) return { changes: [], outOfStock: [] };
        
        const productIds = cart.map(item => item.id);
        
        const formData = new FormData();
        formData.append('action', 'petshop_check_cart_stock');
        productIds.forEach(id => formData.append('product_ids[]', id));
        
        try {
            const response = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                stockInfo = data.data.products;
                
                const changes = [];
                const outOfStock = [];
                let cartUpdated = false;
                
                cart.forEach((item, index) => {
                    const serverInfo = stockInfo[item.id];
                    if (!serverInfo) return;
                    
                    if (!serverInfo.in_stock) {
                        outOfStock.push(item.name);
                    }
                    
                    if (serverInfo.price !== item.price) {
                        changes.push(`"${item.name}" đã đổi giá từ ${formatMoney(item.price)} → ${formatMoney(serverInfo.price)}`);
                        cart[index].price = serverInfo.price;
                        cart[index].originalPrice = serverInfo.original_price;
                        cartUpdated = true;
                    }
                    
                    if (serverInfo.stock > 0 && item.quantity > serverInfo.stock) {
                        changes.push(`"${item.name}" chỉ còn ${serverInfo.stock} sản phẩm, đã điều chỉnh số lượng`);
                        cart[index].quantity = serverInfo.stock;
                        cartUpdated = true;
                    }
                    
                    cart[index].stock = serverInfo.stock;
                    cart[index].in_stock = serverInfo.in_stock;
                    cart[index].category_name = serverInfo.category_name;
                    cart[index].category_url = serverInfo.category_url;
                });
                
                if (cartUpdated) {
                    saveCart(cart);
                }
                
                return { changes, outOfStock };
            }
        } catch (error) {
            console.error('Error checking stock:', error);
        }
        
        return { changes: [], outOfStock: [] };
    }
    
    // Lấy danh sách sản phẩm đã thông báo
    function getNotifiedProducts() {
        const userId = window.PETSHOP_USER?.userId || 0;
        const key = `petshop_notified_out_of_stock_${userId}`;
        return JSON.parse(localStorage.getItem(key)) || [];
    }
    
    // Lưu danh sách sản phẩm đã thông báo
    function saveNotifiedProducts(products) {
        const userId = window.PETSHOP_USER?.userId || 0;
        const key = `petshop_notified_out_of_stock_${userId}`;
        localStorage.setItem(key, JSON.stringify(products));
    }
    
    // Xóa thông báo khi đóng modal
    window.closeStockModal = function() {
        document.getElementById('stockChangeModal').style.display = 'none';
        // Lưu danh sách sản phẩm hết hàng đã thông báo
        const cart = getCart();
        const outOfStockIds = [];
        cart.forEach(item => {
            const info = stockInfo[item.id];
            if (info && !info.in_stock) {
                outOfStockIds.push(item.id);
            }
        });
        
        // Cập nhật danh sách đã thông báo
        const notified = getNotifiedProducts();
        const newNotified = [...new Set([...notified, ...outOfStockIds])];
        saveNotifiedProducts(newNotified);
    }
    
    async function initCart() {
        const { changes, outOfStock } = await checkStockRealtime();
        
        // Lọc ra những sản phẩm hết hàng MỚI (chưa thông báo)
        const notifiedProducts = getNotifiedProducts();
        const cart = getCart();
        const newOutOfStock = [];
        
        cart.forEach(item => {
            const info = stockInfo[item.id];
            if (info && !info.in_stock) {
                // Chỉ thêm vào thông báo nếu chưa từng thông báo
                if (!notifiedProducts.includes(item.id)) {
                    newOutOfStock.push(item.name);
                }
            }
        });
        
        const messages = [];
        if (newOutOfStock.length > 0) {
            messages.push(`<strong>Sản phẩm mới hết hàng:</strong> ${newOutOfStock.join(', ')}`);
        }
        if (changes.length > 0) {
            messages.push(...changes);
        }
        
        if (messages.length > 0) {
            showStockChangeModal(messages);
        }
        
        renderCart();
    }
    
    function renderCart() {
        const cart = getCart();
        const cartContent = document.getElementById('cartContent');
        const emptyCart = document.getElementById('emptyCart');
        const availableItemsList = document.getElementById('availableItemsList');
        const outOfStockItemsList = document.getElementById('outOfStockItemsList');
        const outOfStockSection = document.getElementById('outOfStockSection');
        
        if (cart.length === 0) {
            cartContent.style.display = 'none';
            emptyCart.style.display = 'block';
            selectedItems.clear();
            return;
        }
        
        cartContent.style.display = 'grid';
        emptyCart.style.display = 'none';
        
        const availableItems = [];
        const outOfStockItems = [];
        
        cart.forEach((item, index) => {
            const info = stockInfo[item.id];
            // stock = 0 là hết hàng, stock = -1 hoặc undefined là không giới hạn (còn hàng)
            const isOutOfStock = info ? !info.in_stock : (item.stock === 0);
            
            if (isOutOfStock) {
                outOfStockItems.push({ ...item, index });
                selectedItems.delete(index);
            } else {
                availableItems.push({ ...item, index });
            }
        });
        
        const validSelected = new Set();
        selectedItems.forEach(idx => {
            if (availableItems.some(item => item.index === idx)) {
                validSelected.add(idx);
            }
        });
        selectedItems = validSelected;
        
        let availableHtml = '';
        availableItems.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const imgSrc = item.image || 'https://via.placeholder.com/100x100?text=No+Image';
            const isChecked = selectedItems.has(item.index);
            const serverInfo = stockInfo[item.id];
            const stockQty = serverInfo ? serverInfo.stock : (item.stock || -1);
            const showStockWarning = stockQty > 0 && stockQty <= 5;
            
            availableHtml += `
                <div class="cart-item" data-index="${item.index}" style="display: grid; grid-template-columns: auto 3fr 1fr 1fr 1fr auto; gap: 20px; padding: 20px; background: #fff; border-radius: 20px; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08); margin-bottom: 15px; align-items: center; ${isChecked ? 'border: 2px solid #EC802B;' : 'border: 2px solid transparent;'}">
                    <div class="item-checkbox">
                        <input type="checkbox" class="item-select" data-index="${item.index}" ${isChecked ? 'checked' : ''} style="width: 18px; height: 18px; accent-color: #EC802B; cursor: pointer;">
                    </div>
                    <div class="item-info" style="display: flex; gap: 15px; align-items: center;">
                        <a href="${item.url || '#'}">
                            <img src="${imgSrc}" alt="${item.name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 15px;">
                        </a>
                        <div>
                            <h4 style="margin-bottom:5px;"><a href="${item.url || '#'}" style="color:#5D4E37;text-decoration:none;">${item.name}</a></h4>
                            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:4px;">
                                ${item.variantLabel
                                    ? `<span style="display:inline-flex;align-items:center;gap:4px;background:#FDF8F3;color:#EC802B;padding:2px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;border:1px solid #E8CCAD;">
                                           <i class="bi bi-tag"></i> ${item.variantLabel}
                                       </span>`
                                    : ''}
                                <button onclick="cartChangeVariant(${item.index})"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border:1px solid #E8CCAD;border-radius:12px;font-size:0.78rem;font-weight:600;background:#fff;color:#7A6B5A;cursor:pointer;transition:all .15s;"
                                    onmouseover="this.style.borderColor='#EC802B';this.style.color='#EC802B';"
                                    onmouseout="this.style.borderColor='#E8CCAD';this.style.color='#7A6B5A';">
                                    <i class="bi bi-pencil-square"></i>
                                    ${item.variantLabel ? 'Đổi phân loại' : 'Chọn phân loại'}
                                </button>
                            </div>
                            <span style="color:#7A6B5A;font-size:0.88rem;">SKU: ${item.sku || 'N/A'}</span>
                            ${showStockWarning ? `<div class="stock-warning"><i class="bi bi-exclamation-triangle"></i> Chỉ còn ${stockQty} sản phẩm</div>` : ''}
                        </div>
                    </div>
                    <div class="item-price" style="text-align: center; color: #5D4E37; font-weight: 600;">
                        ${formatMoney(item.price)}
                        ${item.originalPrice > item.price ? '<br><small style="text-decoration: line-through; color: #999;">' + formatMoney(item.originalPrice) + '</small>' : ''}
                    </div>
                    <div class="item-quantity" style="display: flex; align-items: center; justify-content: center;">
                        <div style="display: flex; border: 2px solid #E8CCAD; border-radius: 10px; overflow: hidden;">
                            <button class="qty-btn qty-decrease" data-index="${item.index}" style="width: 35px; height: 35px; border: none; background: #FDF8F3; cursor: pointer; font-size: 1rem;">-</button>
                            <input type="number" value="${item.quantity}" min="1" ${stockQty > 0 ? 'max="' + stockQty + '"' : ''} data-index="${item.index}" class="qty-input" style="width: 45px; height: 35px; border: none; text-align: center; font-size: 1rem;">
                            <button class="qty-btn qty-increase" data-index="${item.index}" style="width: 35px; height: 35px; border: none; background: #FDF8F3; cursor: pointer; font-size: 1rem;">+</button>
                        </div>
                    </div>
                    <div class="item-total" style="text-align: center; color: #EC802B; font-weight: 700; font-size: 1.1rem;">${formatMoney(itemTotal)}</div>
                    <button class="remove-item" data-index="${item.index}" style="width: 40px; height: 40px; border: none; background: #FDF8F3; border-radius: 10px; cursor: pointer; color: #7A6B5A; transition: all 0.3s;" title="Xóa">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            `;
        });
        
        availableItemsList.innerHTML = availableHtml || '<p style="text-align: center; color: #999; padding: 30px;">Không có sản phẩm còn hàng</p>';
        
        if (outOfStockItems.length > 0) {
            outOfStockSection.style.display = 'block';
            document.getElementById('outOfStockCount').textContent = outOfStockItems.length;
            
            let outOfStockHtml = '';
            outOfStockItems.forEach(item => {
                const imgSrc = item.image || 'https://via.placeholder.com/80x80?text=No+Image';
                const serverInfo = stockInfo[item.id];
                const categoryUrl = serverInfo?.category_url || item.category_url || '<?php echo get_post_type_archive_link('product'); ?>';
                const categoryName = serverInfo?.category_name || item.category_name || 'Danh mục';
                
                outOfStockHtml += `
                    <div class="cart-item out-of-stock" data-index="${item.index}" style="display: flex; gap: 15px; padding: 15px; border-bottom: 1px solid #ffcdd2; align-items: center;">
                        <div style="position: relative;">
                            <img src="${imgSrc}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #fff; font-size: 0.7rem; font-weight: 600;">HẾT HÀNG</span>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin-bottom: 5px; font-size: 0.95rem;"><a href="${item.url || '#'}" style="color: #999; text-decoration: none;">${item.name}</a></h4>
                            <span style="color: #999; font-size: 0.85rem; text-decoration: line-through;">${formatMoney(item.price)}</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="${categoryUrl}" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.85rem; color: #EC802B; border-color: #EC802B;">
                                <i class="bi bi-search"></i> Xem SP tương tự
                            </a>
                            <button class="remove-item" data-index="${item.index}" style="width: 35px; height: 35px; border: none; background: #ffebee; border-radius: 8px; cursor: pointer; color: #c62828;" title="Xóa">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            outOfStockItemsList.innerHTML = outOfStockHtml;
        } else {
            outOfStockSection.style.display = 'none';
        }
        
        updateSummary();
        attachEventListeners();
        updateSelectAllState();
        updateDeleteSelectedBtn();
    }
    
    function updateSummary() {
        const cart = getCart();
        
        let selectedCart = [];
        selectedItems.forEach(idx => {
            const item = cart[idx];
            if (item) {
                const info = stockInfo[item.id];
                const isInStock = info ? info.in_stock : (item.stock !== 0);
                if (isInStock) {
                    selectedCart.push(item);
                }
            }
        });
        
        const totalItems = selectedCart.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = selectedCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        let shipping = 0;
        let discount = 0; // Giảm giá sản phẩm
        let shippingDiscount = 0; // Giảm phí ship
        
        if (selectedCart.length > 0) {
            // Tính phí ship cơ bản - kiểm tra có bật miễn phí ship không
            if (ENABLE_FREE_SHIPPING && subtotal >= FREE_SHIPPING_THRESHOLD) {
                shipping = 0;
            } else {
                shipping = SHIPPING_FEE;
            }
            
            if (appliedCoupon) {
                // Xử lý coupon freeship - chỉ giảm phí ship, không giảm giá sản phẩm
                if (appliedCoupon.is_freeship) {
                    shippingDiscount = Math.min(appliedCoupon.shipping_discount || SHIPPING_FEE, shipping);
                    discount = 0; // Không giảm giá sản phẩm
                } else {
                    // Coupon giảm giá sản phẩm - lấy từ server
                    if (appliedCoupon.discount) {
                        discount = appliedCoupon.discount;
                    } else if (appliedCoupon.type === 'percent') {
                        discount = subtotal * (appliedCoupon.value / 100);
                    } else if (appliedCoupon.type === 'fixed') {
                        discount = appliedCoupon.value;
                    }
                    
                    // Đảm bảo giảm giá không vượt quá subtotal sản phẩm
                    discount = Math.min(discount, subtotal);
                }
            }
            
            // Áp dụng giảm phí ship
            shipping = Math.max(0, shipping - shippingDiscount);
        }
        
        // Tổng = Subtotal - Giảm giá sản phẩm + Phí ship (đã trừ giảm ship)
        const total = Math.max(0, subtotal - discount + shipping);
        
        // Lưu phí ship gốc để hiển thị
        const originalShipping = subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
        
        document.getElementById('summaryCount').textContent = totalItems;
        document.getElementById('summarySubtotal').textContent = formatMoney(subtotal);
        
        // Hiển thị phí ship gốc (trước khi giảm)
        if (selectedCart.length > 0) {
            if (shippingDiscount > 0 && originalShipping > 0) {
                // Có giảm phí ship - hiển thị phí gốc
                document.getElementById('summaryShipping').textContent = formatMoney(originalShipping);
            } else {
                document.getElementById('summaryShipping').textContent = originalShipping === 0 ? 'Miễn phí' : formatMoney(originalShipping);
            }
        } else {
            document.getElementById('summaryShipping').textContent = '0đ';
        }
        
        // Hiển thị giảm phí ship (nếu có)
        const shippingDiscountRow = document.getElementById('shippingDiscountRow');
        if (shippingDiscount > 0) {
            shippingDiscountRow.style.display = 'flex';
            document.getElementById('summaryShippingDiscount').textContent = '-' + formatMoney(shippingDiscount);
        } else {
            shippingDiscountRow.style.display = 'none';
        }
        
        // Hiển thị giảm giá sản phẩm (nếu có)
        const discountRow = document.getElementById('discountRow');
        if (discount > 0) {
            discountRow.style.display = 'flex';
            document.getElementById('summaryDiscount').textContent = '-' + formatMoney(discount);
        } else {
            discountRow.style.display = 'none';
        }
        
        document.getElementById('summaryTotal').textContent = formatMoney(total);
        
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (selectedItems.size === 0) {
            checkoutBtn.style.opacity = '0.5';
            checkoutBtn.style.pointerEvents = 'none';
            checkoutBtn.innerHTML = '<i class="bi bi-credit-card"></i> Chọn sản phẩm để thanh toán';
        } else {
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.pointerEvents = 'auto';
            checkoutBtn.innerHTML = `<i class="bi bi-credit-card"></i> Thanh toán (${selectedItems.size} sản phẩm)`;
        }
    }
    
    function updateSelectAllState() {
        const cart = getCart();
        const availableCount = cart.filter(item => {
            const info = stockInfo[item.id];
            return info ? info.in_stock : (item.stock !== 0);
        }).length;
        
        const selectAll = document.getElementById('selectAll');
        if (availableCount > 0 && selectedItems.size === availableCount) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else if (selectedItems.size > 0) {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    }
    
    function updateDeleteSelectedBtn() {
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const countSpan = document.getElementById('selectedCount');
        if (selectedItems.size > 0) {
            deleteBtn.style.display = 'flex';
            countSpan.textContent = selectedItems.size;
        } else {
            deleteBtn.style.display = 'none';
        }
    }
    
    function attachEventListeners() {
        document.querySelectorAll('.item-select').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                if (this.checked) {
                    selectedItems.add(index);
                } else {
                    selectedItems.delete(index);
                }
                renderCart();
            });
        });
        
        document.querySelectorAll('.qty-decrease').forEach(btn => {
            btn.addEventListener('click', function() {
                updateQuantity(parseInt(this.dataset.index), -1);
            });
        });
        
        document.querySelectorAll('.qty-increase').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                const cart = getCart();
                const item = cart[index];
                if (item) {
                    const info = stockInfo[item.id];
                    const maxQty = (info && info.stock > 0) ? info.stock : 999;
                    if (item.quantity < maxQty) {
                        updateQuantity(index, 1);
                    } else {
                        alert(`Số lượng tối đa có thể mua là ${maxQty}`);
                    }
                }
            });
        });
        
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                let value = parseInt(this.value) || 1;
                const cart = getCart();
                const item = cart[index];
                if (item) {
                    const info = stockInfo[item.id];
                    const maxQty = (info && info.stock > 0) ? info.stock : 999;
                    if (value > maxQty) {
                        value = maxQty;
                        alert(`Số lượng tối đa có thể mua là ${maxQty}`);
                    }
                }
                setQuantity(index, value);
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                removeItem(parseInt(this.dataset.index));
            });
        });
    }
    
    document.getElementById('selectAll').addEventListener('change', function() {
        const cart = getCart();
        if (this.checked) {
            cart.forEach((item, index) => {
                const info = stockInfo[item.id];
                const isInStock = info ? info.in_stock : (item.stock !== 0);
                if (isInStock) {
                    selectedItems.add(index);
                }
            });
        } else {
            selectedItems.clear();
        }
        renderCart();
    });
    
    document.getElementById('deleteSelectedBtn').addEventListener('click', function() {
        if (selectedItems.size === 0) return;
        
        if (confirm(`Bạn có chắc muốn xóa ${selectedItems.size} sản phẩm đã chọn?`)) {
            const cart = getCart();
            const newCart = cart.filter((_, index) => !selectedItems.has(index));
            selectedItems.clear();
            saveCart(newCart);
            initCart();
        }
    });
    
    document.getElementById('clearOutOfStockBtn').addEventListener('click', function() {
        const cart = getCart();
        const newCart = cart.filter(item => {
            const info = stockInfo[item.id];
            return info ? info.in_stock : (item.stock !== 0);
        });
        saveCart(newCart);
        initCart();
    });
    
    function updateQuantity(index, delta) {
        const cart = getCart();
        if (cart[index]) {
            cart[index].quantity = Math.max(1, cart[index].quantity + delta);
            saveCart(cart);
            renderCart();
        }
    }
    
    function setQuantity(index, value) {
        const cart = getCart();
        if (cart[index]) {
            cart[index].quantity = Math.max(1, value);
            saveCart(cart);
            renderCart();
        }
    }
    
    function removeItem(index) {
        const cart = getCart();
        if (cart[index]) {
            const itemName = cart[index].name;
            cart.splice(index, 1);
            selectedItems.delete(index);
            const newSelected = new Set();
            selectedItems.forEach(idx => {
                if (idx > index) {
                    newSelected.add(idx - 1);
                } else {
                    newSelected.add(idx);
                }
            });
            selectedItems = newSelected;
            saveCart(cart);
            initCart();
            if (typeof showToast === 'function') {
                showToast('Đã xóa "' + itemName + '"', 'success');
            }
        }
    }
    
    document.getElementById('clearCartBtn').addEventListener('click', function() {
        if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')) {
            const cartKey = getCartKey();
            localStorage.removeItem(cartKey);
            appliedCoupon = null;
            selectedItems.clear();
            stockInfo = {};
            if (typeof window.updateGlobalCartCount === 'function') {
                window.updateGlobalCartCount();
            }
            renderCart();
        }
    });
    
    document.getElementById('applyCouponBtn').addEventListener('click', async function() {
        const code = document.getElementById('couponInput').value.trim().toUpperCase();
        const messageEl = document.getElementById('couponMessage');
        const btn = this;
        
        if (code === '') {
            messageEl.textContent = 'Vui lòng nhập mã giảm giá';
            messageEl.style.display = 'block';
            messageEl.style.color = '#E74C3C';
            return;
        }
        
        // Disable button while loading
        btn.disabled = true;
        btn.textContent = 'Đang kiểm tra...';
        
        // Get cart items for validation
        const cart = getCart();
        const cartItems = cart.map(item => ({
            id: item.id,
            price: item.price,
            quantity: item.quantity
        }));
        
        // Call AJAX to validate coupon
        const formData = new FormData();
        formData.append('action', 'petshop_validate_coupon');
        formData.append('code', code);
        formData.append('cart_items', JSON.stringify(cartItems));
        
        try {
            const response = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                appliedCoupon = {
                    id: data.data.coupon.id,
                    code: data.data.coupon.code,
                    name: data.data.coupon.name,
                    type: data.data.coupon.discount_type === 'percent' ? 'percent' : 'fixed',
                    value: parseFloat(data.data.coupon.discount_value),
                    discount: data.data.discount, // Giảm giá sản phẩm
                    shipping_discount: data.data.shipping_discount || 0, // Giảm phí ship
                    is_freeship: data.data.is_freeship || false,
                    message: data.data.message
                };
                
                messageEl.innerHTML = '✓ ' + data.data.message;
                messageEl.style.display = 'block';
                messageEl.style.color = '#66BCB4';
                
                // Save coupon to localStorage for checkout
                localStorage.setItem('petshop_applied_coupon', JSON.stringify(appliedCoupon));
            } else {
                messageEl.textContent = data.data.message || 'Mã giảm giá không hợp lệ';
                messageEl.style.display = 'block';
                messageEl.style.color = '#E74C3C';
                appliedCoupon = null;
                localStorage.removeItem('petshop_applied_coupon');
            }
        } catch (error) {
            console.error('Error validating coupon:', error);
            messageEl.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
            messageEl.style.display = 'block';
            messageEl.style.color = '#E74C3C';
        }
        
        btn.disabled = false;
        btn.textContent = 'Áp dụng';
        updateSummary();
    });
    
    // Check for saved coupon or combo coupon on page load
    async function checkSavedCoupon() {
        // Check combo coupon first
        const comboCoupon = localStorage.getItem('petshop_combo_coupon');
        if (comboCoupon) {
            document.getElementById('couponInput').value = comboCoupon;
            document.getElementById('applyCouponBtn').click();
            localStorage.removeItem('petshop_combo_coupon');
            return;
        }
        
        // Check saved coupon
        const savedCoupon = localStorage.getItem('petshop_applied_coupon');
        if (savedCoupon) {
            try {
                appliedCoupon = JSON.parse(savedCoupon);
                document.getElementById('couponInput').value = appliedCoupon.code;
                const messageEl = document.getElementById('couponMessage');
                messageEl.innerHTML = '✓ ' + appliedCoupon.message;
                messageEl.style.display = 'block';
                messageEl.style.color = '#66BCB4';
            } catch (e) {
                localStorage.removeItem('petshop_applied_coupon');
            }
        }
    }
    
    document.getElementById('checkoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        if (selectedItems.size === 0) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán!');
            return;
        }
        
        const cart = getCart();
        const checkoutItems = [];
        selectedItems.forEach(idx => {
            const item = cart[idx];
            if (item) {
                const info = stockInfo[item.id];
                const isInStock = info ? info.in_stock : (item.stock !== 0);
                if (isInStock) {
                    checkoutItems.push(item);
                }
            }
        });
        
        if (checkoutItems.length === 0) {
            alert('Không có sản phẩm còn hàng để thanh toán!');
            return;
        }
        
        localStorage.setItem('petshop_checkout', JSON.stringify(checkoutItems));
        window.location.href = '<?php echo home_url('/thanh-toan/'); ?>';
    });
    
    initCart();
    checkSavedCoupon();
    
    // ==========================================
    // COUPON MODAL FUNCTIONS (Shopee Style)
    // ==========================================
    
    window.closeCouponModal = function() {
        document.getElementById('couponModal').style.display = 'none';
    }
    
    window.selectCoupon = function(code) {
        document.getElementById('couponInput').value = code;
        closeCouponModal();
        document.getElementById('applyCouponBtn').click();
    }
    
    async function loadAvailableCoupons() {
        const modal = document.getElementById('couponModal');
        const loadingEl = document.getElementById('couponLoading');
        const emptyEl = document.getElementById('couponEmpty');
        const availableContainer = document.getElementById('availableCoupons');
        const unavailableContainer = document.getElementById('unavailableCoupons');
        
        // Show modal & loading
        modal.style.display = 'flex';
        loadingEl.style.display = 'block';
        emptyEl.style.display = 'none';
        availableContainer.innerHTML = '';
        unavailableContainer.innerHTML = '';
        
        // Get cart items
        const cart = getCart();
        const cartItems = cart.map(item => ({
            id: item.id,
            price: item.price,
            quantity: item.quantity
        }));
        
        const formData = new FormData();
        formData.append('action', 'petshop_get_available_coupons');
        formData.append('cart_items', JSON.stringify(cartItems));
        
        try {
            const response = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            loadingEl.style.display = 'none';
            
            if (data.success) {
                const available = data.data.available || [];
                const unavailable = data.data.unavailable || [];
                
                document.getElementById('availableCount').textContent = available.length;
                document.getElementById('unavailableCount').textContent = unavailable.length;
                
                if (available.length === 0 && unavailable.length === 0) {
                    emptyEl.style.display = 'block';
                    return;
                }
                
                // Render available coupons
                available.forEach(coupon => {
                    availableContainer.innerHTML += renderCouponCard(coupon, true);
                });
                
                // Render unavailable coupons
                unavailable.forEach(coupon => {
                    unavailableContainer.innerHTML += renderCouponCard(coupon, false);
                });
            } else {
                emptyEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading coupons:', error);
            loadingEl.style.display = 'none';
            emptyEl.style.display = 'block';
            emptyEl.innerHTML = '<i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.3;"></i><p style="margin: 15px 0 0;">Có lỗi xảy ra, vui lòng thử lại</p>';
        }
    }
    
    function renderCouponCard(coupon, canUse) {
        const isFreeship = coupon.coupon_group === 'freeship';
        const discountText = coupon.discount_type === 'percent' 
            ? `Giảm ${coupon.discount_value}%` 
            : `Giảm ${formatMoney(coupon.discount_value)}`;
        
        let conditionText = '';
        if (coupon.min_order_amount > 0) {
            conditionText += `Đơn tối thiểu ${formatMoney(coupon.min_order_amount)}`;
        }
        if (coupon.max_discount_amount > 0 && coupon.discount_type === 'percent') {
            conditionText += (conditionText ? ' • ' : '') + `Giảm tối đa ${formatMoney(coupon.max_discount_amount)}`;
        }
        
        let expireText = '';
        if (coupon.end_datetime) {
            const endDate = new Date(coupon.end_datetime);
            const now = new Date();
            const diffDays = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
            if (diffDays <= 3) {
                expireText = `<span style="color: #e74c3c;"><i class="bi bi-clock"></i> Còn ${diffDays} ngày</span>`;
            } else {
                expireText = `<i class="bi bi-clock"></i> HSD: ${endDate.toLocaleDateString('vi-VN')}`;
            }
        }
        
        const estimatedSave = canUse && coupon.estimated_discount > 0 
            ? `<div style="color: #66BCB4; font-size: 0.85rem; font-weight: 600;">Tiết kiệm ${formatMoney(coupon.estimated_discount)}</div>` 
            : '';
        
        return `
            <div class="coupon-card-item ${isFreeship ? 'freeship' : ''} ${!canUse ? 'disabled' : ''}" ${canUse ? `onclick="selectCoupon('${coupon.code}')"` : ''}>
                <div style="padding-right: ${canUse ? '100px' : '0'};">
                    <div class="coupon-value">${discountText}</div>
                    <div class="coupon-name">${coupon.name}</div>
                    <div class="coupon-condition">${conditionText || 'Áp dụng cho tất cả đơn hàng'}</div>
                    ${estimatedSave}
                    <div class="coupon-expire">${expireText}</div>
                    ${!canUse && coupon.reason ? `<div class="coupon-reason"><i class="bi bi-info-circle"></i> ${coupon.reason}</div>` : ''}
                </div>
                ${canUse ? '<button class="coupon-select-btn" type="button">Dùng ngay</button>' : ''}
            </div>
        `;
    }
    
    // Button event
    document.getElementById('viewCouponsBtn').addEventListener('click', loadAvailableCoupons);
    
    // Close modal on click outside
    document.getElementById('couponModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCouponModal();
        }
    });
});
</script>


<!-- ===== POPUP ĐỔI PHÂN LOẠI TRONG GIỎ HÀNG ===== -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;padding:16px;">
    <div onclick="cvClose()" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(3px);"></div>
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:460px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);animation:cvIn .28s ease;">
        <button onclick="cvClose()" style="position:absolute;top:12px;right:12px;z-index:2;background:#f5f5f5;border:none;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:1rem;color:#666;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="cv-body" style="padding:24px 24px 20px;"></div>
    </div>
</div>
<style>
@keyframes cvIn{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:none}}
</style>
<script>
(function(){
const AJAX = window.PETSHOP_USER?.ajaxUrl || '/wp-admin/admin-ajax.php';
let cvIdx=-1, cvVars=[], cvHasS=false, cvHasC=false, cvSelS=null, cvSelC=null;

window.cartChangeVariant = async function(cartIdx) {
    const ck   = window.getCartKey ? window.getCartKey() : 'petshop_cart_guest';
    const cart = JSON.parse(localStorage.getItem(ck))||[];
    // Tìm item theo index field hoặc array index
    const item = cart.find(i=>i.index===cartIdx) || cart[cartIdx];
    if (!item) return;

    cvIdx   = cartIdx;
    cvSelS  = item.selectedSize  || null;
    cvSelC  = item.selectedColor || null;

    const modal = document.getElementById('cv-modal');
    modal.style.display = 'flex';
    document.getElementById('cv-body').innerHTML =
        '<div style="text-align:center;padding:40px 0;color:#7A6B5A;"><i class="bi bi-arrow-repeat" style="font-size:1.6rem;animation:spin 1s linear infinite;display:inline-block;"></i><br><span style="margin-top:10px;display:block;">Đang tải...</span></div>';
    document.body.style.overflow = 'hidden';

    const fd = new FormData();
    fd.append('action','petshop_quick_view');
    fd.append('product_id', item.id);
    try {
        const res  = await fetch(AJAX,{method:'POST',credentials:'same-origin',body:fd});
        const data = await res.json();
        if (!data.success){cvClose();return;}
        cvRender(data.data, item);
    } catch(e){cvClose();}
};

function cvRender(p, item) {
    cvVars = p.variants||[];
    cvHasS = p.sizes  && p.sizes.length>0;
    cvHasC = p.colors && p.colors.length>0;

    // Khoảng giá
    const prices = cvVars.map(v=>v.variant_price).filter(x=>x>0);
    const priceMin = prices.length ? Math.min(...prices) : null;
    const priceMax = prices.length ? Math.max(...prices) : null;
    const basePrice = p.price_info?.is_on_sale ? p.price_info.sale : (p.price_info?.original||p.price);
    let priceHtml = '';
    if (priceMin && priceMin!==priceMax)
        priceHtml = `<span style="color:#EC802B;font-weight:700;">${fmt(priceMin)} – ${fmt(priceMax)}</span>`;
    else if (priceMin)
        priceHtml = `<span style="color:#EC802B;font-weight:700;">${fmt(priceMin)}</span>`;
    else
        priceHtml = `<span style="color:#EC802B;font-weight:700;">${fmt(basePrice)}</span>`;

    // Sizes
    let sizesHtml = '';
    if (cvHasS) {
        const stkMap={};
        cvVars.forEach(v=>{if(v.size)stkMap[v.size]=(stkMap[v.size]||0)+v.stock;});
        sizesHtml=`<div style="margin-bottom:14px;">
            <div style="font-weight:700;color:#5D4E37;margin-bottom:8px;font-size:.9rem;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-rulers" style="color:#EC802B;"></i> Kích thước:
                <strong id="cv-lbl-s" style="color:#EC802B;">${cvSelS||''}</strong>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">${p.sizes.map(s=>{
                const stk=stkMap[s]||0, act=s===cvSelS;
                return `<button class="cv-sb" data-size="${s}" onclick="cvPickS(this)"
                    ${stk<=0?'disabled':''}
                    style="padding:7px 16px;border:2px solid ${act?'#EC802B':'#E8CCAD'};border-radius:8px;background:${act?'#EC802B':'#fff'};color:${act?'#fff':'#5D4E37'};font-weight:700;font-size:.88rem;cursor:${stk>0?'pointer':'not-allowed'};opacity:${stk>0?1:.4};transition:all .15s;">${s}</button>`;
            }).join('')}</div></div>`;
    }

    // Colors — deduplicate
    let colorsHtml = '';
    if (cvHasC) {
        const seen={}, uC=[];
        p.colors.forEach(c=>{if(!seen[c.name]){seen[c.name]=1;uC.push(c);}});
        const stkMap={};
        cvVars.forEach(v=>{if(v.color)stkMap[v.color]=(stkMap[v.color]||0)+v.stock;});
        colorsHtml=`<div style="margin-bottom:14px;">
            <div style="font-weight:700;color:#5D4E37;margin-bottom:8px;font-size:.9rem;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-palette" style="color:#EC802B;"></i> Màu sắc:
                <strong id="cv-lbl-c" style="color:#EC802B;">${cvSelC||''}</strong>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">${uC.map(c=>{
                const stk=stkMap[c.name]||0, act=c.name===cvSelC;
                return `<button class="cv-cb" data-color="${c.name}" data-hex="${c.hex||'#E8CCAD'}" onclick="cvPickC(this)"
                    ${stk<=0?'disabled':''}
                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:2px solid ${act?'#EC802B':'#E8CCAD'};border-radius:25px;background:${act?'#FDF8F3':'#fff'};cursor:${stk>0?'pointer':'not-allowed'};font-size:.85rem;font-weight:600;color:#5D4E37;opacity:${stk>0?1:.4};transition:all .15s;">
                    <span style="width:13px;height:13px;border-radius:50%;background:${c.hex||'#E8CCAD'};border:1.5px solid rgba(0,0,0,0.15);flex-shrink:0;"></span>
                    ${c.name}</button>`;
            }).join('')}</div></div>`;
    }

    const thumb = p.thumb ? `<img src="${p.thumb}" style="width:60px;height:60px;object-fit:cover;border-radius:10px;flex-shrink:0;">` : '';
    document.getElementById('cv-body').innerHTML = `
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #F5EDE0;">
            ${thumb}
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;color:#5D4E37;font-size:.95rem;line-height:1.3;">${p.name}</div>
                <div style="margin-top:4px;" id="cv-price-display">${priceHtml}</div>
            </div>
        </div>
        ${sizesHtml}${colorsHtml}
        <div id="cv-warn" style="display:none;padding:8px 12px;background:#ffebee;border-radius:8px;color:#c62828;font-size:.84rem;margin-bottom:10px;">
            <i class="bi bi-exclamation-circle"></i> Vui lòng chọn đủ phân loại
        </div>
        <div id="cv-stock" style="font-size:.84rem;color:#7A6B5A;margin-bottom:14px;min-height:18px;"></div>
        <button onclick="cvApply()" style="width:100%;padding:13px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;">
            <i class="bi bi-check2-circle"></i> Xác nhận
        </button>`;

    // Restore active state nếu đã có lựa chọn cũ
    if (cvSelS) document.querySelector(`.cv-sb[data-size="${cvSelS}"]`)?.classList.add('_act');
    if (cvSelC) document.querySelector(`.cv-cb[data-color="${cvSelC}"]`)?.classList.add('_act');
    cvUpdateInfo();
}

function fmt(n){return parseInt(n||0).toLocaleString('vi-VN')+'đ';}

function cvPickS(btn){
    document.querySelectorAll('.cv-sb').forEach(b=>{b.style.borderColor='#E8CCAD';b.style.background='#fff';b.style.color='#5D4E37';});
    btn.style.borderColor='#EC802B';btn.style.background='#EC802B';btn.style.color='#fff';
    cvSelS=btn.dataset.size;
    const lbl=document.getElementById('cv-lbl-s'); if(lbl)lbl.textContent=cvSelS;
    if(cvHasC) cvFilterColors(cvSelS);
    cvUpdateInfo();
}
function cvPickC(btn){
    document.querySelectorAll('.cv-cb').forEach(b=>{b.style.borderColor='#E8CCAD';b.style.background='#fff';});
    btn.style.borderColor='#EC802B';btn.style.background='#FDF8F3';
    cvSelC=btn.dataset.color;
    const lbl=document.getElementById('cv-lbl-c'); if(lbl)lbl.textContent=cvSelC;
    cvUpdateInfo();
}
function cvFilterColors(size){
    const avail={};
    cvVars.filter(v=>v.size===size).forEach(v=>{avail[v.color]=(avail[v.color]||0)+v.stock;});
    document.querySelectorAll('.cv-cb').forEach(b=>{
        const s=avail[b.dataset.color]||0;
        b.disabled=s<=0; b.style.opacity=s>0?'1':'0.4';
        if(s<=0&&cvSelC===b.dataset.color){
            cvSelC=null;
            const lbl=document.getElementById('cv-lbl-c');if(lbl)lbl.textContent='';
            b.style.borderColor='#E8CCAD';b.style.background='#fff';
        }
    });
    cvUpdateInfo();
}
function cvUpdateInfo(){
    const ok=(!cvHasS||cvSelS)&&(!cvHasC||cvSelC);
    const si=document.getElementById('cv-stock'); if(!ok){if(si)si.innerHTML='';return;}
    const v=cvVars.find(x=>(!cvHasS||x.size===cvSelS)&&(!cvHasC||x.color===cvSelC));
    if(!v)return;
    if(si) si.innerHTML=v.stock>0
        ?`<i class="bi bi-check-circle-fill" style="color:#5cb85c;"></i> Còn ${v.stock} sản phẩm`
        :`<i class="bi bi-x-circle-fill" style="color:#d9534f;"></i> Hết hàng`;
    // Cập nhật giá
    const pd=document.getElementById('cv-price-display');
    if(pd&&v.variant_price>0) pd.innerHTML=`<span style="color:#EC802B;font-weight:700;">${fmt(v.variant_price)}</span>`;
    document.getElementById('cv-warn').style.display='none';
}
function cvApply(){
    if((cvHasS&&!cvSelS)||(cvHasC&&!cvSelC)){
        document.getElementById('cv-warn').style.display=''; return;
    }
    const variant=(cvHasS||cvHasC)?cvVars.find(x=>(!cvHasS||x.size===cvSelS)&&(!cvHasC||x.color===cvSelC)):null;
    if(variant&&variant.stock<=0){
        const w=document.getElementById('cv-warn');
        w.innerHTML='<i class="bi bi-x-circle"></i> Lựa chọn này đã hết hàng';
        w.style.display='';return;
    }
    const ck=window.getCartKey?window.getCartKey():'petshop_cart_guest';
    let cart=JSON.parse(localStorage.getItem(ck))||[];
    // Tìm theo index field
    let idx=cart.findIndex(i=>i.index===cvIdx);
    if(idx===-1) idx=cvIdx; // fallback
    if(idx<0||idx>=cart.length){cvClose();return;}

    const label=[cvSelS,cvSelC].filter(Boolean).join(' / ');
    cart[idx].variantId     = variant?String(variant.id):'';
    cart[idx].selectedSize  = cvSelS  ||'';
    cart[idx].selectedColor = cvSelC  ||'';
    cart[idx].variantLabel  = label;
    if(variant?.variant_price&&variant.variant_price>0) cart[idx].price=variant.variant_price;
    if(variant?.sku) cart[idx].sku=variant.sku;
    if(variant?.stock!==undefined) cart[idx].stock=variant.stock;

    localStorage.setItem(ck,JSON.stringify(cart));
    cvClose();
    if(typeof renderCart==='function') renderCart();
    else location.reload();
}
window.cvClose=function(){
    document.getElementById('cv-modal').style.display='none';
    document.body.style.overflow='';
    cvIdx=-1;cvSelS=null;cvSelC=null;cvVars=[];
};
})();
</script>

<?php get_footer(); ?>