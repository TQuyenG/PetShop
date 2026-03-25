<?php
/**
 * Template Name: VNPay Return
 * 
 * Xử lý kết quả thanh toán từ VNPay
 */

get_header();

$settings = petshop_get_payment_settings();
$response_code = isset($_GET['vnp_ResponseCode']) ? sanitize_text_field($_GET['vnp_ResponseCode']) : '';
$transaction_no = isset($_GET['vnp_TransactionNo']) ? sanitize_text_field($_GET['vnp_TransactionNo']) : '';
$order_info = isset($_GET['vnp_OrderInfo']) ? sanitize_text_field($_GET['vnp_OrderInfo']) : '';
$amount = isset($_GET['vnp_Amount']) ? intval($_GET['vnp_Amount']) / 100 : 0;
$bank_code = isset($_GET['vnp_BankCode']) ? sanitize_text_field($_GET['vnp_BankCode']) : '';
$pay_date = isset($_GET['vnp_PayDate']) ? sanitize_text_field($_GET['vnp_PayDate']) : '';
$txn_ref = isset($_GET['vnp_TxnRef']) ? sanitize_text_field($_GET['vnp_TxnRef']) : '';

// Demo mode check
$demo_mode = isset($_GET['demo']) && $_GET['demo'] == '1';
$demo_success = isset($_GET['success']) && $_GET['success'] == '1';

// Xác định trạng thái thanh toán
$payment_success = false;
$status_message = '';
$status_class = '';

if ($demo_mode) {
    // Demo mode - luôn thành công
    if ($demo_success) {
        $payment_success = true;
        $status_message = 'Thanh toán demo thành công!';
        $status_class = 'success';
        $transaction_no = 'DEMO-' . time();
        
        // Lấy order ID từ session hoặc URL
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if ($order_id) {
            // Cập nhật trạng thái đơn hàng
            update_post_meta($order_id, 'order_status', 'processing');
            update_post_meta($order_id, 'payment_status', 'paid');
            update_post_meta($order_id, 'payment_transaction_id', $transaction_no);
            update_post_meta($order_id, 'payment_date', current_time('mysql'));
        }
    } else {
        $payment_success = false;
        $status_message = 'Thanh toán demo thất bại!';
        $status_class = 'error';
    }
} else {
    // Real VNPay response
    if ($response_code == '00') {
        // Verify signature
        $vnp_SecureHash = isset($_GET['vnp_SecureHash']) ? $_GET['vnp_SecureHash'] : '';
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_" && $key != "vnp_SecureHash") {
                $inputData[$key] = $value;
            }
        }
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, $settings['vnpay_hash_secret']);
        
        if ($secureHash == $vnp_SecureHash) {
            $payment_success = true;
            $status_message = 'Thanh toán thành công!';
            $status_class = 'success';
            
            // Lấy order ID từ txn_ref
            $order_id = intval($txn_ref);
            if ($order_id) {
                update_post_meta($order_id, 'order_status', 'processing');
                update_post_meta($order_id, 'payment_status', 'paid');
                update_post_meta($order_id, 'payment_transaction_id', $transaction_no);
                update_post_meta($order_id, 'payment_date', current_time('mysql'));
                update_post_meta($order_id, 'vnpay_bank_code', $bank_code);
            }
        } else {
            $payment_success = false;
            $status_message = 'Chữ ký không hợp lệ!';
            $status_class = 'error';
        }
    } else {
        $payment_success = false;
        
        // VNPay error codes
        $error_messages = array(
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking',
            '10' => 'Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán',
            '12' => 'Thẻ/Tài khoản bị khóa',
            '13' => 'Nhập sai mật khẩu OTP',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định',
            '99' => 'Lỗi không xác định'
        );
        
        $status_message = isset($error_messages[$response_code]) 
            ? $error_messages[$response_code] 
            : 'Thanh toán thất bại (Mã lỗi: ' . $response_code . ')';
        $status_class = 'error';
    }
}

// Get order ID for redirect
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($txn_ref) ? intval($txn_ref) : 0);
?>

<main class="main-content">
    <div class="container">
        <div class="vnpay-return-page">
            <div class="payment-result <?php echo esc_attr($status_class); ?>">
                <div class="result-icon">
                    <?php if ($payment_success): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                
                <h1 class="result-title">
                    <?php echo $payment_success ? 'Thanh toán thành công!' : 'Thanh toán thất bại'; ?>
                </h1>
                
                <p class="result-message"><?php echo esc_html($status_message); ?></p>
                
                <?php if ($payment_success && $amount > 0): ?>
                <div class="payment-details">
                    <div class="detail-row">
                        <span class="label">Số tiền:</span>
                        <span class="value"><?php echo number_format($amount, 0, ',', '.'); ?>₫</span>
                    </div>
                    <?php if ($transaction_no): ?>
                    <div class="detail-row">
                        <span class="label">Mã giao dịch:</span>
                        <span class="value"><?php echo esc_html($transaction_no); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($bank_code): ?>
                    <div class="detail-row">
                        <span class="label">Ngân hàng:</span>
                        <span class="value"><?php echo esc_html($bank_code); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($demo_mode): ?>
                    <div class="demo-badge">
                        <span class="dashicons dashicons-info"></span>
                        Đây là giao dịch demo (không thực)
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="result-actions">
                    <?php if ($payment_success): ?>
                        <a href="<?php echo home_url('/hoan-tat/?order_id=' . $order_id); ?>" class="btn btn-primary">
                            Xem đơn hàng
                        </a>
                    <?php else: ?>
                        <a href="<?php echo home_url('/thanh-toan/'); ?>" class="btn btn-primary">
                            Thử lại
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo home_url('/'); ?>" class="btn btn-secondary">
                        Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.vnpay-return-page {
    padding: 60px 0;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-result {
    background: white;
    border-radius: 16px;
    padding: 48px;
    text-align: center;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.payment-result.success .result-icon {
    color: #10b981;
    background: #d1fae5;
}

.payment-result.error .result-icon {
    color: #ef4444;
    background: #fee2e2;
}

.result-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.result-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 12px;
}

.payment-result.success .result-title {
    color: #059669;
}

.payment-result.error .result-title {
    color: #dc2626;
}

.result-message {
    color: #64748b;
    margin-bottom: 24px;
    font-size: 16px;
}

.payment-details {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #e2e8f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .label {
    color: #64748b;
}

.detail-row .value {
    font-weight: 600;
    color: #1e293b;
}

.demo-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fef3c7;
    color: #92400e;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    margin-top: 16px;
}

.result-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.result-actions .btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.result-actions .btn-primary {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
}

.result-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
}

.result-actions .btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.result-actions .btn-secondary:hover {
    background: #e2e8f0;
}

@media (max-width: 576px) {
    .payment-result {
        padding: 32px 24px;
    }
    
    .result-icon {
        width: 100px;
        height: 100px;
    }
    
    .result-actions {
        flex-direction: column;
    }
    
    .result-actions .btn {
        width: 100%;
    }
}
</style>

<?php get_footer(); ?>
