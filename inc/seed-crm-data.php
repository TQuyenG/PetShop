<?php
/**
 * PetShop CRM - Seed Data Generator
 * Tạo dữ liệu mẫu cho CRM: Khách hàng, Đơn hàng, Analytics
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// TRANG TẠO DỮ LIỆU MẪU - GIAO DIỆN CHÍNH
// =============================================
function petshop_seed_crm_page() {
    $message = '';
    $message_type = 'success';
    
    // Xử lý form khách hàng & đơn hàng
    if (isset($_POST['seed_customers']) && wp_verify_nonce($_POST['seed_nonce'], 'seed_crm_data')) {
        $mode = isset($_POST['customer_mode']) ? sanitize_text_field($_POST['customer_mode']) : 'add';
        $count = isset($_POST['customer_count']) ? intval($_POST['customer_count']) : 20;
        $result = petshop_generate_customer_data($mode, $count);
        $message = $result;
    }
    
    if (isset($_POST['delete_customers']) && wp_verify_nonce($_POST['seed_nonce'], 'seed_crm_data')) {
        $result = petshop_delete_customer_data();
        $message = $result;
    }
    
    // Xử lý form analytics
    if (isset($_POST['seed_analytics']) && wp_verify_nonce($_POST['seed_nonce'], 'seed_crm_data')) {
        $mode = isset($_POST['analytics_mode']) ? sanitize_text_field($_POST['analytics_mode']) : 'replace';
        $days = isset($_POST['analytics_days']) ? intval($_POST['analytics_days']) : 90;
        $result = petshop_generate_analytics_data($mode, $days);
        $message = $result;
    }
    
    if (isset($_POST['delete_analytics']) && wp_verify_nonce($_POST['seed_nonce'], 'seed_crm_data')) {
        $result = petshop_delete_analytics_data();
        $message = $result;
    }
    
    // Lấy thống kê hiện tại
    $current_stats = petshop_get_seed_stats();
    
    ?>
    <style>
        .seed-page {
            max-width: 1200px;
            padding: 20px;
        }
        .seed-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 20px;
        }
        @media (max-width: 1024px) {
            .seed-grid { grid-template-columns: 1fr; }
        }
        .seed-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .seed-card-header {
            background: linear-gradient(135deg, #4361ee 0%, #7c3aed 100%);
            color: white;
            padding: 20px;
        }
        .seed-card-header.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .seed-card-header h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .seed-card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .seed-card-body {
            padding: 24px;
        }
        .seed-info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .seed-info-box.warning {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .seed-info-box h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 14px;
        }
        .seed-info-box.warning h4 {
            color: #92400e;
        }
        .seed-info-box ul {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            line-height: 1.8;
        }
        .seed-form-group {
            margin-bottom: 20px;
        }
        .seed-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        .seed-form-group .description {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }
        .seed-radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .seed-radio-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .seed-radio-option:hover {
            background: #f3f4f6;
        }
        .seed-radio-option.selected {
            background: #eff6ff;
            border-color: #3b82f6;
        }
        .seed-radio-option input[type="radio"] {
            margin-top: 2px;
        }
        .seed-radio-content {
            flex: 1;
        }
        .seed-radio-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .seed-radio-desc {
            font-size: 12px;
            color: #6b7280;
        }
        .seed-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        .seed-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .seed-btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .seed-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .seed-btn-primary {
            background: #4361ee;
            color: white;
        }
        .seed-btn-primary:hover {
            background: #3451d1;
            transform: translateY(-1px);
        }
        .seed-btn-success {
            background: #10b981;
            color: white;
        }
        .seed-btn-success:hover {
            background: #059669;
        }
        .seed-btn-danger {
            background: #ef4444;
            color: white;
        }
        .seed-btn-danger:hover {
            background: #dc2626;
        }
        .seed-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .seed-stat {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        .seed-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .seed-stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .seed-accounts-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .seed-accounts-box h4 {
            margin: 0 0 10px 0;
            color: #166534;
            font-size: 14px;
        }
        .seed-accounts-box code {
            background: #dcfce7;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        .seed-notice {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .seed-notice.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .seed-notice.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
    
    <div class="wrap seed-page">
        <h1 style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <span style="font-size: 32px;">🎲</span> Tạo Dữ Liệu Mẫu CRM
        </h1>
        <p style="color: #6b7280; margin-bottom: 20px;">
            Công cụ tạo dữ liệu mẫu để test và demo hệ thống CRM. Dữ liệu được đánh dấu riêng để dễ dàng xóa sau này.
        </p>
        
        <?php if ($message): ?>
            <div class="seed-notice success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <?php wp_nonce_field('seed_crm_data', 'seed_nonce'); ?>
            
            <div class="seed-grid">
                <!-- KHÁCH HÀNG & ĐƠN HÀNG -->
                <div class="seed-card">
                    <div class="seed-card-header">
                        <h2><span>👥</span> Khách Hàng & Đơn Hàng</h2>
                        <p>Tạo tài khoản khách hàng mẫu với đơn hàng ngẫu nhiên</p>
                    </div>
                    <div class="seed-card-body">
                        <!-- Thống kê hiện tại -->
                        <div class="seed-stats">
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo number_format($current_stats['sample_customers']); ?></div>
                                <div class="seed-stat-label">Khách mẫu hiện có</div>
                            </div>
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo number_format($current_stats['sample_orders']); ?></div>
                                <div class="seed-stat-label">Đơn hàng mẫu</div>
                            </div>
                        </div>
                        
                        <!-- Thông tin -->
                        <div class="seed-info-box">
                            <h4>📋 Dữ liệu sẽ được tạo:</h4>
                            <ul>
                                <li><strong>Khách hàng</strong>: Tên Việt Nam, SĐT, địa chỉ đầy đủ</li>
                                <li><strong>Đơn hàng</strong>: 1-6 đơn/khách, sản phẩm thú cưng</li>
                                <li><strong>Trạng thái</strong>: pending → completed (theo logic thời gian)</li>
                                <li><strong>Username</strong>: khachhang01 → khachhang{n}</li>
                                <li><strong>Password</strong>: 123456 (mặc định)</li>
                            </ul>
                        </div>
                        
                        <!-- Chế độ tạo -->
                        <div class="seed-form-group">
                            <label>Chế độ tạo dữ liệu</label>
                            <div class="seed-radio-group">
                                <label class="seed-radio-option">
                                    <input type="radio" name="customer_mode" value="add" checked>
                                    <div class="seed-radio-content">
                                        <div class="seed-radio-title">➕ Thêm mới (không xóa cũ)</div>
                                        <div class="seed-radio-desc">Thêm khách hàng mới, bỏ qua username đã tồn tại</div>
                                    </div>
                                </label>
                                <label class="seed-radio-option">
                                    <input type="radio" name="customer_mode" value="replace">
                                    <div class="seed-radio-content">
                                        <div class="seed-radio-title">🔄 Thay thế hoàn toàn</div>
                                        <div class="seed-radio-desc">Xóa tất cả dữ liệu mẫu cũ, tạo mới từ đầu</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Số lượng -->
                        <div class="seed-form-group">
                            <label>Số lượng khách hàng</label>
                            <input type="number" name="customer_count" class="seed-input" value="20" min="1" max="100" style="width: 120px;">
                            <p class="description">Mỗi khách sẽ có 1-6 đơn hàng ngẫu nhiên (tối đa 100 khách)</p>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="seed-btn-group">
                            <button type="submit" name="seed_customers" class="seed-btn seed-btn-primary" 
                                    onclick="return confirm('Bạn có chắc muốn tạo dữ liệu khách hàng mẫu?');">
                                🚀 Tạo Khách Hàng
                            </button>
                            <button type="submit" name="delete_customers" class="seed-btn seed-btn-danger"
                                    onclick="return confirm('⚠️ Xóa TẤT CẢ khách hàng và đơn hàng mẫu?\n\nHành động này không thể hoàn tác!');">
                                🗑️ Xóa Tất Cả
                            </button>
                        </div>
                        
                        <!-- Thông tin tài khoản -->
                        <?php if ($current_stats['sample_customers'] > 0): ?>
                        <div class="seed-accounts-box">
                            <h4>📝 Tài khoản mẫu hiện có:</h4>
                            <p>
                                Username: <code>khachhang01</code> đến <code>khachhang<?php echo str_pad($current_stats['sample_customers'], 2, '0', STR_PAD_LEFT); ?></code><br>
                                Password: <code>123456</code>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ANALYTICS DATA -->
                <div class="seed-card">
                    <div class="seed-card-header green">
                        <h2><span>📊</span> Dữ Liệu Analytics</h2>
                        <p>Tạo dữ liệu traffic, pageviews, events cho dashboard</p>
                    </div>
                    <div class="seed-card-body">
                        <!-- Thống kê hiện tại -->
                        <div class="seed-stats">
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo number_format($current_stats['sessions']); ?></div>
                                <div class="seed-stat-label">Sessions</div>
                            </div>
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo number_format($current_stats['pageviews']); ?></div>
                                <div class="seed-stat-label">Pageviews</div>
                            </div>
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo number_format($current_stats['events']); ?></div>
                                <div class="seed-stat-label">Events</div>
                            </div>
                            <div class="seed-stat">
                                <div class="seed-stat-value"><?php echo $current_stats['analytics_days']; ?></div>
                                <div class="seed-stat-label">Ngày có dữ liệu</div>
                            </div>
                        </div>
                        
                        <!-- Thông tin -->
                        <div class="seed-info-box">
                            <h4>📋 Dữ liệu sẽ được tạo:</h4>
                            <ul>
                                <li><strong>Sessions</strong>: 80-250 phiên/ngày, nguồn Google/FB/Direct...</li>
                                <li><strong>Pageviews</strong>: 1-8 trang/phiên</li>
                                <li><strong>Events</strong>: view_product → add_to_cart → checkout → purchase</li>
                                <li><strong>Thiết bị</strong>: Desktop, Mobile, Tablet (tỷ lệ thực tế)</li>
                                <li><strong>Trình duyệt</strong>: Chrome, Firefox, Safari, Edge</li>
                            </ul>
                        </div>
                        
                        <div class="seed-info-box warning">
                            <h4>💡 Ghi chú về Conversion Funnel:</h4>
                            <ul>
                                <li>65% visitors xem sản phẩm</li>
                                <li>35% của họ thêm vào giỏ</li>
                                <li>50% tiến hành checkout</li>
                                <li>55% hoàn tất mua hàng</li>
                            </ul>
                        </div>
                        
                        <!-- Chế độ tạo -->
                        <div class="seed-form-group">
                            <label>Chế độ tạo dữ liệu</label>
                            <div class="seed-radio-group">
                                <label class="seed-radio-option">
                                    <input type="radio" name="analytics_mode" value="add">
                                    <div class="seed-radio-content">
                                        <div class="seed-radio-title">➕ Thêm vào (giữ dữ liệu cũ)</div>
                                        <div class="seed-radio-desc">Thêm sessions mới, cộng dồn với dữ liệu hiện có</div>
                                    </div>
                                </label>
                                <label class="seed-radio-option">
                                    <input type="radio" name="analytics_mode" value="replace" checked>
                                    <div class="seed-radio-content">
                                        <div class="seed-radio-title">🔄 Thay thế hoàn toàn</div>
                                        <div class="seed-radio-desc">Xóa dữ liệu cũ, tạo mới từ đầu</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Số ngày -->
                        <div class="seed-form-group">
                            <label>Số ngày tạo dữ liệu</label>
                            <input type="number" name="analytics_days" class="seed-input" value="90" min="7" max="365" style="width: 120px;">
                            <p class="description">Tạo dữ liệu cho N ngày gần nhất (7-365 ngày)</p>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="seed-btn-group">
                            <button type="submit" name="seed_analytics" class="seed-btn seed-btn-success"
                                    onclick="return confirm('Tạo dữ liệu analytics mẫu?\n\nQuá trình có thể mất vài giây...');">
                                📊 Tạo Analytics
                            </button>
                            <button type="submit" name="delete_analytics" class="seed-btn seed-btn-danger"
                                    onclick="return confirm('⚠️ Xóa TẤT CẢ dữ liệu analytics?\n\nSessions, Pageviews, Events sẽ bị xóa!');">
                                🗑️ Xóa Tất Cả
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
    // Radio option styling
    document.querySelectorAll('.seed-radio-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove selected from all in same group
            const group = this.closest('.seed-radio-group');
            group.querySelectorAll('.seed-radio-option').forEach(opt => opt.classList.remove('selected'));
            // Add selected to this one
            this.closest('.seed-radio-option').classList.add('selected');
        });
        // Initial state
        if (radio.checked) {
            radio.closest('.seed-radio-option').classList.add('selected');
        }
    });
    </script>
    <?php
}

// =============================================
// LẤY THỐNG KÊ HIỆN TẠI
// =============================================
function petshop_get_seed_stats() {
    global $wpdb;
    
    $stats = array(
        'sample_customers' => 0,
        'sample_orders' => 0,
        'sessions' => 0,
        'pageviews' => 0,
        'events' => 0,
        'analytics_days' => 0
    );
    
    // Đếm khách hàng mẫu
    $stats['sample_customers'] = count(get_users(array(
        'meta_key' => 'is_sample_data',
        'meta_value' => '1',
        'fields' => 'ID'
    )));
    
    // Đếm đơn hàng mẫu
    $stats['sample_orders'] = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'petshop_order'
        AND pm.meta_key = 'is_sample_data'
        AND pm.meta_value = '1'
    ");
    
    // Analytics tables
    $sessions_table = $wpdb->prefix . 'petshop_sessions';
    $pageviews_table = $wpdb->prefix . 'petshop_pageviews';
    $events_table = $wpdb->prefix . 'petshop_events';
    
    // Kiểm tra bảng tồn tại
    if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table) {
        $stats['sessions'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $sessions_table");
        $stats['analytics_days'] = (int)$wpdb->get_var("SELECT COUNT(DISTINCT DATE(created_at)) FROM $sessions_table");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table) {
        $stats['pageviews'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $pageviews_table");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$events_table'") === $events_table) {
        $stats['events'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $events_table");
    }
    
    return $stats;
}

// =============================================
// TẠO DỮ LIỆU KHÁCH HÀNG & ĐƠN HÀNG
// =============================================
function petshop_generate_customer_data($mode = 'add', $count = 20) {
    global $wpdb;
    
    // Nếu mode = replace, xóa dữ liệu cũ trước
    if ($mode === 'replace') {
        petshop_delete_customer_data();
    }
    
    // Danh sách tên Việt Nam
    $first_names = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương'];
    $middle_names = ['Văn', 'Thị', 'Hữu', 'Đức', 'Minh', 'Thanh', 'Hoàng', 'Quốc', 'Anh', 'Ngọc'];
    $last_names = ['An', 'Bình', 'Cường', 'Dũng', 'Hà', 'Hải', 'Hùng', 'Khoa', 'Linh', 'Long', 'Mai', 'Nam', 'Phong', 'Quân', 'Sơn', 'Tâm', 'Thảo', 'Trang', 'Tuấn', 'Vy'];
    
    // Địa chỉ mẫu
    $streets = ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Phạm Ngũ Lão', 'Hai Bà Trưng', 'Điện Biên Phủ', 'Cách Mạng Tháng 8', 'Nguyễn Trãi', 'Lý Thường Kiệt', 'Pasteur'];
    $districts = ['Quận 1', 'Quận 3', 'Quận 5', 'Quận 7', 'Quận 10', 'Bình Thạnh', 'Phú Nhuận', 'Tân Bình', 'Gò Vấp', 'Thủ Đức'];
    $cities = ['TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng'];
    
    // Sản phẩm mẫu
    $products = [
        ['name' => 'Thức ăn hạt Royal Canin 2kg', 'price' => 450000],
        ['name' => 'Thức ăn hạt Pedigree 1.5kg', 'price' => 180000],
        ['name' => 'Pate Whiskas cho mèo', 'price' => 25000],
        ['name' => 'Cát vệ sinh mèo 5L', 'price' => 85000],
        ['name' => 'Vòng cổ chó size M', 'price' => 120000],
        ['name' => 'Dây dắt chó tự động', 'price' => 250000],
        ['name' => 'Lồng vận chuyển thú cưng', 'price' => 350000],
        ['name' => 'Đồ chơi chuột cho mèo', 'price' => 35000],
        ['name' => 'Bàn cào móng mèo', 'price' => 280000],
        ['name' => 'Khay vệ sinh cho mèo', 'price' => 150000],
        ['name' => 'Sữa tắm chó mèo 500ml', 'price' => 95000],
        ['name' => 'Bát ăn inox chó mèo', 'price' => 45000],
        ['name' => 'Nệm ngủ cho thú cưng', 'price' => 320000],
        ['name' => 'Vitamin bổ sung cho chó', 'price' => 180000],
        ['name' => 'Bánh thưởng cho chó', 'price' => 65000],
    ];
    
    $statuses = ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled'];
    $payment_methods = ['cod', 'bank_transfer', 'vnpay'];
    
    // Khoảng thời gian: 90 ngày gần nhất
    $end_date = time();
    $start_date = strtotime('-90 days');
    
    $created_users = 0;
    $created_orders = 0;
    
    // Tìm số bắt đầu cho username
    $start_index = 1;
    for ($i = 1; $i <= 1000; $i++) {
        $username = 'khachhang' . str_pad($i, 2, '0', STR_PAD_LEFT);
        if (!username_exists($username)) {
            $start_index = $i;
            break;
        }
    }
    
    // Tạo khách hàng
    for ($i = 0; $i < $count; $i++) {
        $username = 'khachhang' . str_pad($start_index + $i, 2, '0', STR_PAD_LEFT);
        
        if (username_exists($username)) {
            continue;
        }
        
        // Tên random
        $full_name = $first_names[array_rand($first_names)] . ' ' . 
                     $middle_names[array_rand($middle_names)] . ' ' . 
                     $last_names[array_rand($last_names)];
        
        $phone = '09' . rand(10000000, 99999999);
        
        // Tạo user
        $user_id = wp_create_user($username, '123456', $username . '@petshop.test');
        
        if (is_wp_error($user_id)) continue;
        
        // Update user info
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $full_name,
            'first_name' => explode(' ', $full_name)[0],
            'last_name' => end(explode(' ', $full_name))
        ));
        
        // Set role
        $user = new WP_User($user_id);
        $user->set_role('petshop_customer');
        
        // Meta
        update_user_meta($user_id, 'petshop_phone', $phone);
        update_user_meta($user_id, 'is_sample_data', true);
        
        // Address
        $address_data = array(
            array(
                'id' => 'addr_' . uniqid(),
                'label' => 'Nhà riêng',
                'fullname' => $full_name,
                'phone' => $phone,
                'address' => rand(1, 200) . ' ' . $streets[array_rand($streets)],
                'ward' => 'Phường ' . rand(1, 15),
                'ward_text' => 'Phường ' . rand(1, 15),
                'district' => $districts[array_rand($districts)],
                'district_text' => $districts[array_rand($districts)],
                'city' => $cities[array_rand($cities)],
                'city_text' => $cities[array_rand($cities)]
            )
        );
        update_user_meta($user_id, 'petshop_addresses', $address_data);
        update_user_meta($user_id, 'petshop_default_address_id', $address_data[0]['id']);
        
        // Referral code
        $referral_code = strtoupper(substr(md5($user_id . time()), 0, 8));
        update_user_meta($user_id, 'petshop_referral_code', $referral_code);
        
        $created_users++;
        
        // Random registration date
        $register_date = rand($start_date, $end_date - 86400 * 7);
        $wpdb->update($wpdb->users, 
            array('user_registered' => date('Y-m-d H:i:s', $register_date)),
            array('ID' => $user_id)
        );
        
        // Tạo 1-6 đơn hàng
        $num_orders = rand(1, 6);
        $last_order_date = $register_date;
        
        for ($j = 0; $j < $num_orders; $j++) {
            $order_date = $last_order_date + rand(2 * 86400, 15 * 86400);
            if ($order_date > $end_date) {
                $order_date = rand($last_order_date, $end_date);
            }
            $last_order_date = $order_date;
            
            // Products
            $num_products = rand(1, 4);
            $selected_products = array_rand($products, min($num_products, count($products)));
            if (!is_array($selected_products)) {
                $selected_products = array($selected_products);
            }
            
            $cart_items = array();
            $subtotal = 0;
            
            foreach ($selected_products as $prod_idx) {
                $product = $products[$prod_idx];
                $qty = rand(1, 3);
                $cart_items[] = array(
                    'id' => 1000 + $prod_idx,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $qty,
                    'image' => ''
                );
                $subtotal += $product['price'] * $qty;
            }
            
            $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
            $discount = (rand(0, 100) < 20) ? floor($subtotal * rand(5, 15) / 100) : 0;
            $order_total = $subtotal + $shipping_fee - $discount;
            
            // Status based on date
            $days_ago = ($end_date - $order_date) / 86400;
            if ($days_ago < 2) {
                $status = $statuses[rand(0, 2)];
            } elseif ($days_ago < 5) {
                $status = $statuses[rand(2, 3)];
            } elseif ($days_ago < 15) {
                $rand = rand(1, 100);
                if ($rand <= 70) $status = 'completed';
                elseif ($rand <= 90) $status = 'shipping';
                else $status = 'cancelled';
            } else {
                $status = (rand(1, 100) <= 85) ? 'completed' : 'cancelled';
            }
            
            $order_code = 'PET' . date('Ymd', $order_date) . strtoupper(substr(uniqid(), -4));
            
            // Create order
            $order_id = wp_insert_post(array(
                'post_type' => 'petshop_order',
                'post_title' => $order_code,
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_date' => date('Y-m-d H:i:s', $order_date)
            ));
            
            if ($order_id && !is_wp_error($order_id)) {
                $addr = $address_data[0];
                $full_address = $addr['address'] . ', ' . $addr['ward_text'] . ', ' . $addr['district_text'] . ', ' . $addr['city_text'];
                
                update_post_meta($order_id, 'order_code', $order_code);
                update_post_meta($order_id, 'customer_name', $full_name);
                update_post_meta($order_id, 'customer_phone', $phone);
                update_post_meta($order_id, 'customer_email', $username . '@petshop.test');
                update_post_meta($order_id, 'customer_address', $full_address);
                update_post_meta($order_id, 'customer_user_id', $user_id);
                update_post_meta($order_id, 'payment_method', $payment_methods[array_rand($payment_methods)]);
                update_post_meta($order_id, 'order_total', $order_total);
                update_post_meta($order_id, 'order_subtotal', $subtotal);
                update_post_meta($order_id, 'order_shipping', $shipping_fee);
                update_post_meta($order_id, 'order_discount', $discount);
                update_post_meta($order_id, 'order_status', $status);
                update_post_meta($order_id, 'order_date', date('Y-m-d H:i:s', $order_date));
                update_post_meta($order_id, 'cart_items', json_encode($cart_items));
                update_post_meta($order_id, 'is_sample_data', true);
                
                $created_orders++;
            }
        }
    }
    
    return "✅ Đã tạo: <strong>$created_users khách hàng</strong> và <strong>$created_orders đơn hàng</strong>";
}

// =============================================
// XÓA DỮ LIỆU KHÁCH HÀNG
// =============================================
function petshop_delete_customer_data() {
    global $wpdb;
    
    $deleted_users = 0;
    $deleted_orders = 0;
    
    // Xóa đơn hàng mẫu
    $sample_orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'is_sample_data', 'value' => '1')
        )
    ));
    
    foreach ($sample_orders as $order) {
        wp_delete_post($order->ID, true);
        $deleted_orders++;
    }
    
    // Xóa users mẫu
    $sample_users = get_users(array(
        'meta_key' => 'is_sample_data',
        'meta_value' => '1'
    ));
    
    foreach ($sample_users as $user) {
        $wpdb->delete($wpdb->prefix . 'petshop_crm_activity', array('user_id' => $user->ID));
        $wpdb->delete($wpdb->prefix . 'petshop_crm_notes', array('user_id' => $user->ID));
        
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user->ID);
        $deleted_users++;
    }
    
    return "🗑️ Đã xóa: <strong>$deleted_users khách hàng</strong> và <strong>$deleted_orders đơn hàng</strong>";
}

// =============================================
// TẠO DỮ LIỆU ANALYTICS
// =============================================
function petshop_generate_analytics_data($mode = 'replace', $days = 90) {
    global $wpdb;
    
    // Tạo tables nếu chưa có
    $sessions_table = $wpdb->prefix . 'petshop_sessions';
    $pageviews_table = $wpdb->prefix . 'petshop_pageviews';
    $events_table = $wpdb->prefix . 'petshop_events';
    
    $charset = $wpdb->get_charset_collate();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS $sessions_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        visitor_id VARCHAR(64) NOT NULL,
        source VARCHAR(100) DEFAULT 'Direct',
        medium VARCHAR(100) DEFAULT '',
        campaign VARCHAR(100) DEFAULT '',
        device VARCHAR(50) DEFAULT 'desktop',
        browser VARCHAR(50) DEFAULT '',
        country VARCHAR(10) DEFAULT 'VN',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visitor (visitor_id),
        INDEX idx_created (created_at),
        INDEX idx_source (source)
    ) $charset");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS $pageviews_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id BIGINT UNSIGNED,
        page_url VARCHAR(500) NOT NULL,
        page_title VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_created (created_at)
    ) $charset");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS $events_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id BIGINT UNSIGNED,
        event_type VARCHAR(50) NOT NULL,
        event_data TEXT,
        product_id BIGINT UNSIGNED DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_type (event_type),
        INDEX idx_created (created_at)
    ) $charset");
    
    // Nếu mode = replace, xóa dữ liệu cũ
    if ($mode === 'replace') {
        $wpdb->query("TRUNCATE TABLE $sessions_table");
        $wpdb->query("TRUNCATE TABLE $pageviews_table");
        $wpdb->query("TRUNCATE TABLE $events_table");
    }
    
    // Lấy products thật
    $products = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' LIMIT 50");
    if (empty($products)) {
        $products = range(1, 20);
    }
    
    // Data templates
    $sources = array('Google', 'Facebook', 'Direct', 'Email', 'Referral', 'Instagram');
    $devices = array('desktop', 'desktop', 'mobile', 'mobile', 'mobile', 'tablet'); // mobile nhiều hơn
    $browsers = array('Chrome', 'Chrome', 'Chrome', 'Firefox', 'Safari', 'Edge'); // Chrome nhiều hơn
    $pages = array('/', '/san-pham', '/danh-muc/cho', '/danh-muc/meo', '/gio-hang', '/lien-he', '/gioi-thieu');
    
    $sessions_count = 0;
    $pageviews_count = 0;
    $events_count = 0;
    
    // Generate data
    for ($day = $days; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        
        // Weekday có traffic cao hơn
        $dow = date('N', strtotime($date));
        $base_sessions = ($dow >= 6) ? rand(60, 150) : rand(100, 280);
        
        for ($s = 0; $s < $base_sessions; $s++) {
            $visitor_id = 'v_' . md5($date . '_' . $s . '_' . rand(1, 100000));
            $source = $sources[array_rand($sources)];
            $device = $devices[array_rand($devices)];
            $browser = $browsers[array_rand($browsers)];
            $hour = rand(7, 23);
            $minute = rand(0, 59);
            $time = sprintf('%02d:%02d:%02d', $hour, $minute, rand(0, 59));
            
            // Insert session
            $wpdb->insert($sessions_table, array(
                'visitor_id' => $visitor_id,
                'source' => $source,
                'device' => $device,
                'browser' => $browser,
                'created_at' => "$date $time"
            ));
            $session_id = $wpdb->insert_id;
            $sessions_count++;
            
            // Pageviews: 1-10 pages per session
            $pv_count = rand(1, 10);
            for ($p = 0; $p < $pv_count; $p++) {
                $wpdb->insert($pageviews_table, array(
                    'session_id' => $session_id,
                    'page_url' => $pages[array_rand($pages)],
                    'page_title' => 'Page ' . ($p + 1),
                    'created_at' => "$date $time"
                ));
                $pageviews_count++;
            }
            
            // Events funnel (65% → 35% → 50% → 55%)
            if (rand(1, 100) <= 65) {
                $product_id = $products[array_rand($products)];
                $wpdb->insert($events_table, array(
                    'session_id' => $session_id,
                    'event_type' => 'view_product',
                    'product_id' => $product_id,
                    'created_at' => "$date $time"
                ));
                $events_count++;
                
                if (rand(1, 100) <= 35) {
                    $wpdb->insert($events_table, array(
                        'session_id' => $session_id,
                        'event_type' => 'add_to_cart',
                        'product_id' => $product_id,
                        'created_at' => "$date $time"
                    ));
                    $events_count++;
                    
                    if (rand(1, 100) <= 50) {
                        $wpdb->insert($events_table, array(
                            'session_id' => $session_id,
                            'event_type' => 'begin_checkout',
                            'created_at' => "$date $time"
                        ));
                        $events_count++;
                        
                        if (rand(1, 100) <= 55) {
                            $wpdb->insert($events_table, array(
                                'session_id' => $session_id,
                                'event_type' => 'purchase',
                                'created_at' => "$date $time"
                            ));
                            $events_count++;
                        }
                    }
                }
            }
        }
    }
    
    return "✅ Đã tạo analytics cho <strong>$days ngày</strong>:<br>" .
           "• Sessions: <strong>" . number_format($sessions_count) . "</strong><br>" .
           "• Pageviews: <strong>" . number_format($pageviews_count) . "</strong><br>" .
           "• Events: <strong>" . number_format($events_count) . "</strong>";
}

// =============================================
// XÓA DỮ LIỆU ANALYTICS
// =============================================
function petshop_delete_analytics_data() {
    global $wpdb;
    
    $sessions_table = $wpdb->prefix . 'petshop_sessions';
    $pageviews_table = $wpdb->prefix . 'petshop_pageviews';
    $events_table = $wpdb->prefix . 'petshop_events';
    
    $sessions = 0;
    $pageviews = 0;
    $events = 0;
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table) {
        $sessions = (int)$wpdb->get_var("SELECT COUNT(*) FROM $sessions_table");
        $wpdb->query("TRUNCATE TABLE $sessions_table");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table) {
        $pageviews = (int)$wpdb->get_var("SELECT COUNT(*) FROM $pageviews_table");
        $wpdb->query("TRUNCATE TABLE $pageviews_table");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$events_table'") === $events_table) {
        $events = (int)$wpdb->get_var("SELECT COUNT(*) FROM $events_table");
        $wpdb->query("TRUNCATE TABLE $events_table");
    }
    
    return "🗑️ Đã xóa:<br>" .
           "• Sessions: <strong>" . number_format($sessions) . "</strong><br>" .
           "• Pageviews: <strong>" . number_format($pageviews) . "</strong><br>" .
           "• Events: <strong>" . number_format($events) . "</strong>";
}
