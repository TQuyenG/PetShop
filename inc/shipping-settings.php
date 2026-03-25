<?php
/**
 * Shipping Settings Management
 * Quản lý cài đặt phí vận chuyển
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================
// ĐĂNG KÝ SETTINGS
// =============================================
function petshop_register_shipping_settings() {
    // Đăng ký option group
    register_setting('petshop_shipping_options', 'petshop_shipping_settings', array(
        'sanitize_callback' => 'petshop_sanitize_shipping_settings',
        'default' => array(
            'shipping_fee' => 30000,
            'free_shipping_threshold' => 500000,
            'enable_free_shipping' => 1,
            'shipping_zones' => array(),
        )
    ));
}
add_action('admin_init', 'petshop_register_shipping_settings');

// Sanitize settings
function petshop_sanitize_shipping_settings($input) {
    $sanitized = array();
    
    $sanitized['shipping_fee'] = isset($input['shipping_fee']) ? floatval($input['shipping_fee']) : 30000;
    $sanitized['free_shipping_threshold'] = isset($input['free_shipping_threshold']) ? floatval($input['free_shipping_threshold']) : 500000;
    $sanitized['enable_free_shipping'] = isset($input['enable_free_shipping']) ? 1 : 0;
    
    // Shipping zones (nếu có)
    if (isset($input['shipping_zones']) && is_array($input['shipping_zones'])) {
        $sanitized['shipping_zones'] = array();
        foreach ($input['shipping_zones'] as $zone) {
            if (!empty($zone['name'])) {
                $sanitized['shipping_zones'][] = array(
                    'name' => sanitize_text_field($zone['name']),
                    'fee' => floatval($zone['fee']),
                    'cities' => sanitize_textarea_field($zone['cities'] ?? '')
                );
            }
        }
    } else {
        $sanitized['shipping_zones'] = array();
    }
    
    return $sanitized;
}

// =============================================
// THÊM MENU ADMIN
// =============================================
function petshop_add_shipping_settings_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Cài đặt vận chuyển',
        'Vận chuyển',
        'manage_options',
        'petshop-shipping-settings',
        'petshop_shipping_settings_page'
    );
}
add_action('admin_menu', 'petshop_add_shipping_settings_menu');

// =============================================
// TRANG CÀI ĐẶT
// =============================================
function petshop_shipping_settings_page() {
    // Lấy settings hiện tại
    $settings = get_option('petshop_shipping_settings', array(
        'shipping_fee' => 30000,
        'free_shipping_threshold' => 500000,
        'enable_free_shipping' => 1,
        'shipping_zones' => array(),
    ));
    
    // Xử lý save nếu có
    if (isset($_POST['petshop_save_shipping']) && check_admin_referer('petshop_shipping_settings_nonce')) {
        $new_settings = array(
            'shipping_fee' => floatval($_POST['shipping_fee']),
            'free_shipping_threshold' => floatval($_POST['free_shipping_threshold']),
            'enable_free_shipping' => isset($_POST['enable_free_shipping']) ? 1 : 0,
            'shipping_zones' => array(),
        );
        
        // Parse shipping zones
        if (isset($_POST['zone_name']) && is_array($_POST['zone_name'])) {
            foreach ($_POST['zone_name'] as $i => $name) {
                if (!empty($name)) {
                    $new_settings['shipping_zones'][] = array(
                        'name' => sanitize_text_field($name),
                        'fee' => floatval($_POST['zone_fee'][$i] ?? 0),
                        'cities' => sanitize_textarea_field($_POST['zone_cities'][$i] ?? '')
                    );
                }
            }
        }
        
        update_option('petshop_shipping_settings', $new_settings);
        $settings = $new_settings;
        
        echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt vận chuyển thành công!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Cài đặt vận chuyển</h1>
        
        <style>
            .shipping-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .shipping-card h2 {
                margin: 0 0 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .shipping-form-row {
                display: grid;
                grid-template-columns: 200px 1fr;
                gap: 15px;
                align-items: center;
                margin-bottom: 20px;
            }
            .shipping-form-row label {
                font-weight: 600;
                color: #1d2327;
            }
            .shipping-form-row input[type="number"],
            .shipping-form-row input[type="text"] {
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                width: 100%;
                max-width: 300px;
            }
            .shipping-form-row .description {
                color: #646970;
                font-size: 12px;
                margin-top: 5px;
            }
            .zone-item {
                background: #f6f7f7;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 10px;
                display: grid;
                grid-template-columns: 1fr 150px auto;
                gap: 15px;
                align-items: start;
            }
            .zone-item input[type="text"],
            .zone-item input[type="number"],
            .zone-item textarea {
                width: 100%;
                padding: 8px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .zone-item textarea {
                min-height: 60px;
                resize: vertical;
            }
            .remove-zone-btn {
                background: #dc3545;
                color: #fff;
                border: none;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
            }
            .remove-zone-btn:hover {
                background: #c82333;
            }
            #addZoneBtn {
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            #addZoneBtn:hover {
                background: #135e96;
            }
            .info-box {
                background: #e7f3ff;
                border-left: 4px solid #0073aa;
                padding: 12px 15px;
                margin: 15px 0;
                border-radius: 0 4px 4px 0;
            }
            .info-box strong {
                color: #0073aa;
            }
        </style>
        
        <form method="post" action="">
            <?php wp_nonce_field('petshop_shipping_settings_nonce'); ?>
            
            <!-- Cài đặt cơ bản -->
            <div class="shipping-card">
                <h2>Cài đặt cơ bản</h2>
                
                <div class="shipping-form-row">
                    <label for="shipping_fee">Phí vận chuyển mặc định</label>
                    <div>
                        <input type="number" name="shipping_fee" id="shipping_fee" 
                               value="<?php echo esc_attr($settings['shipping_fee']); ?>" 
                               min="0" step="1000">
                        <p class="description">Phí ship cơ bản áp dụng cho tất cả đơn hàng (VNĐ)</p>
                    </div>
                </div>
                
                <div class="shipping-form-row">
                    <label for="enable_free_shipping">Miễn phí vận chuyển</label>
                    <div>
                        <label style="font-weight: normal;">
                            <input type="checkbox" name="enable_free_shipping" id="enable_free_shipping" value="1" 
                                   <?php checked($settings['enable_free_shipping'], 1); ?>>
                            Bật miễn phí vận chuyển khi đạt giá trị đơn hàng tối thiểu
                        </label>
                    </div>
                </div>
                
                <div class="shipping-form-row" id="thresholdRow" style="<?php echo $settings['enable_free_shipping'] ? '' : 'display:none;'; ?>">
                    <label for="free_shipping_threshold">Ngưỡng miễn phí ship</label>
                    <div>
                        <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" 
                               value="<?php echo esc_attr($settings['free_shipping_threshold']); ?>" 
                               min="0" step="10000">
                        <p class="description">Đơn hàng từ giá trị này trở lên sẽ được miễn phí vận chuyển (VNĐ)</p>
                    </div>
                </div>
                
                <div class="info-box">
                    <strong>Lưu ý:</strong> Phí vận chuyển này áp dụng cho tất cả đơn hàng. 
                    Mã giảm giá loại "Freeship" sẽ giảm trực tiếp vào phí này, không ảnh hưởng đến giá sản phẩm.
                </div>
            </div>
            
            <!-- Khu vực vận chuyển (tùy chọn nâng cao) -->
            <div class="shipping-card">
                <h2>Khu vực vận chuyển <small style="color:#999;font-weight:normal;">(Tùy chọn nâng cao)</small></h2>
                
                <p style="color:#666;margin-bottom:20px;">
                    Thêm các khu vực vận chuyển với phí ship khác nhau. Nếu không có khu vực nào được thiết lập, 
                    hệ thống sẽ sử dụng phí ship mặc định.
                </p>
                
                <div id="shippingZones">
                    <?php if (!empty($settings['shipping_zones'])): ?>
                        <?php foreach ($settings['shipping_zones'] as $i => $zone): ?>
                        <div class="zone-item">
                            <div>
                                <label style="font-size:12px;color:#666;">Tên khu vực</label>
                                <input type="text" name="zone_name[]" value="<?php echo esc_attr($zone['name']); ?>" placeholder="VD: Nội thành Hà Nội">
                                <label style="font-size:12px;color:#666;margin-top:10px;display:block;">Thành phố/Tỉnh (mỗi dòng 1 địa điểm)</label>
                                <textarea name="zone_cities[]" placeholder="Hà Nội&#10;Hải Phòng"><?php echo esc_textarea($zone['cities']); ?></textarea>
                            </div>
                            <div>
                                <label style="font-size:12px;color:#666;">Phí ship (VNĐ)</label>
                                <input type="number" name="zone_fee[]" value="<?php echo esc_attr($zone['fee']); ?>" min="0" step="1000">
                            </div>
                            <div style="padding-top:20px;">
                                <button type="button" class="remove-zone-btn" onclick="this.closest('.zone-item').remove();">Xóa</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="addZoneBtn">
                    <span>+</span> Thêm khu vực
                </button>
            </div>
            
            <!-- Hiển thị giá trị đang áp dụng -->
            <div class="shipping-card" style="background:#f0f9f0;border-color:#46b450;">
                <h2>Tóm tắt cài đặt hiện tại</h2>
                <table class="widefat" style="border:none;background:transparent;">
                    <tr>
                        <td style="padding:10px;"><strong>Phí ship mặc định:</strong></td>
                        <td style="padding:10px;color:#d63638;font-weight:600;">
                            <?php echo number_format($settings['shipping_fee']); ?>đ
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;"><strong>Miễn phí ship khi đơn từ:</strong></td>
                        <td style="padding:10px;color:#00a32a;font-weight:600;">
                            <?php if ($settings['enable_free_shipping']): ?>
                                <?php echo number_format($settings['free_shipping_threshold']); ?>đ trở lên
                            <?php else: ?>
                                <span style="color:#666;">Không áp dụng</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;"><strong>Số khu vực đặc biệt:</strong></td>
                        <td style="padding:10px;">
                            <?php echo count($settings['shipping_zones']); ?> khu vực
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <button type="submit" name="petshop_save_shipping" class="button button-primary button-large">
                    Lưu cài đặt
                </button>
            </p>
        </form>
    </div>
    
    <script>
    document.getElementById('enable_free_shipping').addEventListener('change', function() {
        document.getElementById('thresholdRow').style.display = this.checked ? '' : 'none';
    });
    
    document.getElementById('addZoneBtn').addEventListener('click', function() {
        const template = `
            <div class="zone-item">
                <div>
                    <label style="font-size:12px;color:#666;">Tên khu vực</label>
                    <input type="text" name="zone_name[]" placeholder="VD: Nội thành Hà Nội">
                    <label style="font-size:12px;color:#666;margin-top:10px;display:block;">Thành phố/Tỉnh (mỗi dòng 1 địa điểm)</label>
                    <textarea name="zone_cities[]" placeholder="Hà Nội&#10;Hải Phòng"></textarea>
                </div>
                <div>
                    <label style="font-size:12px;color:#666;">Phí ship (VNĐ)</label>
                    <input type="number" name="zone_fee[]" value="30000" min="0" step="1000">
                </div>
                <div style="padding-top:20px;">
                    <button type="button" class="remove-zone-btn" onclick="this.closest('.zone-item').remove();">Xóa</button>
                </div>
            </div>
        `;
        document.getElementById('shippingZones').insertAdjacentHTML('beforeend', template);
    });
    </script>
    <?php
}

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Lấy phí ship cho đơn hàng
 * @param float $subtotal Tổng tiền sản phẩm
 * @param string $city Thành phố (tùy chọn)
 * @return array ['fee' => phí ship, 'is_free' => có miễn phí không]
 */
function petshop_get_shipping_fee($subtotal = 0, $city = '') {
    $settings = get_option('petshop_shipping_settings', array(
        'shipping_fee' => 30000,
        'free_shipping_threshold' => 500000,
        'enable_free_shipping' => 1,
        'shipping_zones' => array(),
    ));
    
    $fee = floatval($settings['shipping_fee']);
    $is_free = false;
    
    // Kiểm tra khu vực đặc biệt
    if (!empty($city) && !empty($settings['shipping_zones'])) {
        foreach ($settings['shipping_zones'] as $zone) {
            $cities = array_filter(array_map('trim', explode("\n", $zone['cities'])));
            foreach ($cities as $zone_city) {
                if (stripos($city, $zone_city) !== false) {
                    $fee = floatval($zone['fee']);
                    break 2;
                }
            }
        }
    }
    
    // Kiểm tra miễn phí ship
    if ($settings['enable_free_shipping'] && $subtotal >= $settings['free_shipping_threshold']) {
        $is_free = true;
        $fee = 0;
    }
    
    return array(
        'fee' => $fee,
        'is_free' => $is_free,
        'threshold' => $settings['free_shipping_threshold'],
        'enable_free_shipping' => $settings['enable_free_shipping']
    );
}

/**
 * Lấy cài đặt shipping để dùng trong JavaScript
 */
function petshop_get_shipping_settings_for_js() {
    $settings = get_option('petshop_shipping_settings', array(
        'shipping_fee' => 30000,
        'free_shipping_threshold' => 500000,
        'enable_free_shipping' => 1,
    ));
    
    return array(
        'shipping_fee' => floatval($settings['shipping_fee']),
        'free_shipping_threshold' => floatval($settings['free_shipping_threshold']),
        'enable_free_shipping' => (bool) $settings['enable_free_shipping'],
    );
}

/**
 * Localize script với shipping settings
 */
function petshop_enqueue_shipping_settings() {
    $settings = petshop_get_shipping_settings_for_js();
    
    // Add inline script after main.js or any existing script
    wp_add_inline_script('petshop-main', 
        'var PETSHOP_SHIPPING = ' . json_encode($settings) . ';', 
        'before'
    );
}
// Hook này sẽ được gọi trong front-end nếu cần
