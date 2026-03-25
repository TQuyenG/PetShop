<?php
/**
 * PetShop CRM - Dashboard & KPI
 * Báº£ng Ä‘iá»u khiá»ƒn vÃ  thá»‘ng kÃª KPI toÃ n diá»‡n
 * 
 * Features:
 * - Dashboard tá»•ng quan vá»›i 5 nhÃ³m KPI
 * - NhÃ³m 1: Doanh thu & ÄÆ¡n hÃ ng (GMV, NMV, AOV, Cancellation Rate, Return Rate)
 * - NhÃ³m 2: Váº­n hÃ nh & Logistics (C2D Leadtime, Shipping Fee Share)
 * - NhÃ³m 3: KhÃ¡ch hÃ ng (Active Users, New Customer Rate, Retention Rate)
 * - NhÃ³m 4: Marketing & Traffic (Conversion Rate, Sessions, Traffic by Source)
 * - NhÃ³m 5: Sáº£n pháº©m & Danh má»¥c (Category Contribution)
 * - Biá»ƒu Ä‘á»“ doanh sá»‘, sessions, pie charts theo nguá»“n
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// Load analytics tracking
require_once get_template_directory() . '/inc/analytics-tracking.php';

// Menu Ä‘Æ°á»£c Ä‘Äƒng kÃ½ trong crm-admin-menu.php

// =============================================
// HÃ€M Láº¤Y THá»NG KÃŠ NÃ‚NG CAO
// =============================================

/**
 * Láº¥y thá»‘ng kÃª toÃ n diá»‡n theo KPI chuáº©n
 */
function petshop_get_advanced_kpi_stats($period = '30days') {
    global $wpdb;
    
    // XÃ¡c Ä‘á»‹nh khoáº£ng thá»i gian
    switch ($period) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case '7days':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $end_date = date('Y-m-d');
            break;
        case '30days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this_year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-m-d');
            break;
        case 'all_time':
            $start_date = '2020-01-01';
            $end_date = date('Y-m-d');
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
    }
    
    // Láº¥y táº¥t cáº£ orders rá»“i filter theo ngÃ y
    $all_orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    // Filter theo khoáº£ng thá»i gian
    $orders = array();
    $start_ts = strtotime($start_date . ' 00:00:00');
    $end_ts = strtotime($end_date . ' 23:59:59');
    
    foreach ($all_orders as $order) {
        $order_date = get_post_meta($order->ID, 'order_date', true);
        if (empty($order_date)) {
            $order_date = $order->post_date;
        }
        $order_ts = strtotime($order_date);
        
        if ($order_ts >= $start_ts && $order_ts <= $end_ts) {
            $orders[] = $order;
        }
    }
    
    // ========== NHÃ“M 1: DOANH THU & ÄÆ N HÃ€NG ==========
    $gmv = 0; // Gross Merchandise Value
    $confirmed_value = 0;
    $cancelled_value = 0;
    $returned_value = 0;
    $total_shipping = 0;
    $total_orders = count($orders);
    $confirmed_orders = 0;
    $cancelled_orders = 0;
    $returned_orders = 0;
    $pending_orders = 0;
    $processing_orders = 0;
    $shipping_orders = 0;
    
    $orders_by_status = array(
        'pending' => 0,
        'confirmed' => 0,
        'processing' => 0,
        'shipping' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'returned' => 0
    );
    
    $revenue_by_day = array();
    $orders_by_day = array();
    $product_sales = array();
    $customer_totals = array();
    $category_revenue = array();
    
    foreach ($orders as $order) {
        $order_status = get_post_meta($order->ID, 'order_status', true) ?: 'pending';
        $order_total = floatval(get_post_meta($order->ID, 'order_total', true));
        $order_subtotal = floatval(get_post_meta($order->ID, 'order_subtotal', true));
        $order_shipping = floatval(get_post_meta($order->ID, 'order_shipping', true));
        $order_date = get_post_meta($order->ID, 'order_date', true);
        $customer_name = get_post_meta($order->ID, 'customer_name', true);
        $customer_email = get_post_meta($order->ID, 'customer_email', true);
        $customer_user_id = get_post_meta($order->ID, 'customer_user_id', true);
        $cart_items = json_decode(get_post_meta($order->ID, 'cart_items', true), true);
        
        // Count by status
        if (isset($orders_by_status[$order_status])) {
            $orders_by_status[$order_status]++;
        }
        
        // Calculate GMV (all confirmed orders including shipping)
        if (in_array($order_status, array('confirmed', 'processing', 'shipping', 'completed'))) {
            $gmv += $order_total;
            $total_shipping += $order_shipping;
            $confirmed_value += $order_total;
            $confirmed_orders++;
        }
        
        if ($order_status === 'cancelled') {
            $cancelled_value += $order_total;
            $cancelled_orders++;
        }
        
        if ($order_status === 'returned') {
            $returned_value += $order_total;
            $returned_orders++;
        }
        
        if ($order_status === 'pending') {
            $pending_orders++;
        }
        
        // Revenue by day (completed only)
        $day_key = date('Y-m-d', strtotime($order_date));
        if (!isset($revenue_by_day[$day_key])) {
            $revenue_by_day[$day_key] = 0;
            $orders_by_day[$day_key] = 0;
        }
        if ($order_status === 'completed') {
            $revenue_by_day[$day_key] += $order_total;
        }
        $orders_by_day[$day_key]++;
        
        // Top products & Category
        if (is_array($cart_items)) {
            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                if (!isset($product_sales[$product_id])) {
                    $product_sales[$product_id] = array(
                        'name' => $item['name'],
                        'quantity' => 0,
                        'revenue' => 0,
                    );
                }
                $qty = intval($item['quantity']);
                $price = floatval($item['price']);
                $product_sales[$product_id]['quantity'] += $qty;
                $product_sales[$product_id]['revenue'] += $price * $qty;
                
                // Category contribution
                $category = $item['category'] ?? 'KhÃ¡c';
                if (!isset($category_revenue[$category])) {
                    $category_revenue[$category] = 0;
                }
                $category_revenue[$category] += $price * $qty;
            }
        }
        
        // Top customers
        $customer_key = $customer_email ?: $customer_user_id;
        if ($customer_key) {
            if (!isset($customer_totals[$customer_key])) {
                $customer_totals[$customer_key] = array(
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'user_id' => $customer_user_id,
                    'orders' => 0,
                    'total' => 0,
                    'first_order' => $order_date,
                );
            }
            $customer_totals[$customer_key]['orders']++;
            if ($order_status === 'completed') {
                $customer_totals[$customer_key]['total'] += $order_total;
            }
        }
    }
    
    // NMV = GMV - Cancelled - Returned
    $nmv = $gmv - $cancelled_value - $returned_value;
    
    // AOV = NMV / Success Orders
    $success_orders = $confirmed_orders - $cancelled_orders - $returned_orders;
    $aov = $success_orders > 0 ? $nmv / $success_orders : 0;
    
    // Cancellation Rate
    $cancellation_rate = $total_orders > 0 ? ($cancelled_orders / $total_orders) * 100 : 0;
    
    // Return Rate
    $return_rate = $success_orders > 0 ? ($returned_orders / $success_orders) * 100 : 0;
    
    // Shipping Fee Share
    $shipping_fee_share = $gmv > 0 ? ($total_shipping / $gmv) * 100 : 0;
    
    // ========== NHÃ“M 3: KHÃCH HÃ€NG ==========
    $unique_customers = count($customer_totals);
    $new_customers = 0;
    $returning_customers = 0;
    
    foreach ($customer_totals as $customer) {
        if ($customer['orders'] === 1) {
            $new_customers++;
        } else {
            $returning_customers++;
        }
    }
    
    $new_customer_rate = $unique_customers > 0 ? ($new_customers / $unique_customers) * 100 : 0;
    $retention_rate = $unique_customers > 0 ? ($returning_customers / $unique_customers) * 100 : 0;
    
    // ========== NHÃ“M 4: TRAFFIC (tá»« analytics) ==========
    $traffic_stats = array();
    if (function_exists('petshop_get_traffic_stats')) {
        $traffic_stats = petshop_get_traffic_stats($start_date, $end_date);
    }
    
    $total_sessions = $traffic_stats['total_sessions'] ?? 0;
    $unique_visitors = $traffic_stats['unique_visitors'] ?? 0;
    
    // Conversion Rate = Orders / Sessions
    $conversion_rate = $total_sessions > 0 ? ($confirmed_orders / $total_sessions) * 100 : 0;
    
    // Sort top products & customers
    uasort($product_sales, function($a, $b) { return $b['revenue'] - $a['revenue']; });
    uasort($customer_totals, function($a, $b) { return $b['total'] - $a['total']; });
    uasort($category_revenue, function($a, $b) { return $b - $a; });
    
    return array(
        // Dates
        'start_date' => $start_date,
        'end_date' => $end_date,
        'period' => $period,
        
        // NhÃ³m 1: Doanh thu & ÄÆ¡n hÃ ng
        'gmv' => $gmv,
        'nmv' => $nmv,
        'aov' => $aov,
        'total_shipping' => $total_shipping,
        'confirmed_value' => $confirmed_value,
        'cancelled_value' => $cancelled_value,
        'returned_value' => $returned_value,
        'total_orders' => $total_orders,
        'confirmed_orders' => $confirmed_orders,
        'cancelled_orders' => $cancelled_orders,
        'returned_orders' => $returned_orders,
        'success_orders' => $success_orders,
        'cancellation_rate' => $cancellation_rate,
        'return_rate' => $return_rate,
        'orders_by_status' => $orders_by_status,
        
        // NhÃ³m 2: Váº­n hÃ nh
        'shipping_fee_share' => $shipping_fee_share,
        
        // NhÃ³m 3: KhÃ¡ch hÃ ng
        'unique_customers' => $unique_customers,
        'new_customers' => $new_customers,
        'returning_customers' => $returning_customers,
        'new_customer_rate' => $new_customer_rate,
        'retention_rate' => $retention_rate,
        
        // NhÃ³m 4: Traffic
        'total_sessions' => $total_sessions,
        'unique_visitors' => $unique_visitors,
        'conversion_rate' => $conversion_rate,
        'traffic_stats' => $traffic_stats,
        
        // NhÃ³m 5: Sáº£n pháº©m
        'top_products' => array_slice($product_sales, 0, 10, true),
        'category_revenue' => array_slice($category_revenue, 0, 10, true),
        
        // Lists
        'top_customers' => array_slice($customer_totals, 0, 10, true),
        'revenue_by_day' => $revenue_by_day,
        'orders_by_day' => $orders_by_day,
    );
}

// =============================================
// HÃ€M Láº¤Y THá»NG KÃŠ CÅ¨ (giá»¯ tÆ°Æ¡ng thÃ­ch)
// =============================================
function petshop_get_dashboard_stats($period = '30days') {
    global $wpdb;
    
    // XÃ¡c Ä‘á»‹nh khoáº£ng thá»i gian
    switch ($period) {
        case 'today':
            $start_date = date('Y-m-d 00:00:00');
            break;
        case '7days':
            $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
            break;
        case '30days':
            $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01 00:00:00');
            break;
        case 'last_month':
            $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
        case 'this_year':
            $start_date = date('Y-01-01 00:00:00');
            break;
        default:
            $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
    }
    
    $end_date = isset($end_date) ? $end_date : date('Y-m-d 23:59:59');
    
    // Láº¥y táº¥t cáº£ orders rá»“i filter theo ngÃ y
    $all_orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    // Filter theo khoáº£ng thá»i gian
    $orders = array();
    $start_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    
    foreach ($all_orders as $order) {
        $order_date = get_post_meta($order->ID, 'order_date', true);
        if (empty($order_date)) {
            $order_date = $order->post_date;
        }
        $order_ts = strtotime($order_date);
        
        if ($order_ts >= $start_ts && $order_ts <= $end_ts) {
            $orders[] = $order;
        }
    }
    
    $stats = array(
        'total_orders' => 0,
        'total_revenue' => 0,
        'completed_orders' => 0,
        'pending_orders' => 0,
        'cancelled_orders' => 0,
        'average_order_value' => 0,
        'orders_by_status' => array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ),
        'revenue_by_day' => array(),
        'orders_by_day' => array(),
        'top_products' => array(),
        'top_customers' => array(),
    );
    
    $product_sales = array();
    $customer_totals = array();
    
    foreach ($orders as $order) {
        $order_status = get_post_meta($order->ID, 'order_status', true);
        $order_total = floatval(get_post_meta($order->ID, 'order_total', true));
        $order_date = get_post_meta($order->ID, 'order_date', true);
        $customer_name = get_post_meta($order->ID, 'customer_name', true);
        $customer_email = get_post_meta($order->ID, 'customer_email', true);
        $cart_items = json_decode(get_post_meta($order->ID, 'cart_items', true), true);
        
        $stats['total_orders']++;
        $stats['orders_by_status'][$order_status] = ($stats['orders_by_status'][$order_status] ?? 0) + 1;
        
        if ($order_status === 'completed') {
            $stats['completed_orders']++;
            $stats['total_revenue'] += $order_total;
        } elseif ($order_status === 'pending') {
            $stats['pending_orders']++;
        } elseif ($order_status === 'cancelled') {
            $stats['cancelled_orders']++;
        }
        
        // Revenue by day
        $day_key = date('Y-m-d', strtotime($order_date));
        if (!isset($stats['revenue_by_day'][$day_key])) {
            $stats['revenue_by_day'][$day_key] = 0;
            $stats['orders_by_day'][$day_key] = 0;
        }
        if ($order_status === 'completed') {
            $stats['revenue_by_day'][$day_key] += $order_total;
        }
        $stats['orders_by_day'][$day_key]++;
        
        // Top products
        if (is_array($cart_items)) {
            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                if (!isset($product_sales[$product_id])) {
                    $product_sales[$product_id] = array(
                        'name' => $item['name'],
                        'quantity' => 0,
                        'revenue' => 0,
                    );
                }
                $product_sales[$product_id]['quantity'] += $item['quantity'];
                $product_sales[$product_id]['revenue'] += floatval($item['price']) * $item['quantity'];
            }
        }
        
        // Top customers
        if ($customer_email) {
            if (!isset($customer_totals[$customer_email])) {
                $customer_totals[$customer_email] = array(
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'orders' => 0,
                    'total' => 0,
                );
            }
            $customer_totals[$customer_email]['orders']++;
            if ($order_status === 'completed') {
                $customer_totals[$customer_email]['total'] += $order_total;
            }
        }
    }
    
    // Calculate average
    if ($stats['completed_orders'] > 0) {
        $stats['average_order_value'] = $stats['total_revenue'] / $stats['completed_orders'];
    }
    
    // Sort and get top products
    uasort($product_sales, function($a, $b) {
        return $b['revenue'] - $a['revenue'];
    });
    $stats['top_products'] = array_slice($product_sales, 0, 10, true);
    
    // Sort and get top customers
    uasort($customer_totals, function($a, $b) {
        return $b['total'] - $a['total'];
    });
    $stats['top_customers'] = array_slice($customer_totals, 0, 10, true);
    
    // Previous period comparison
    $prev_start = date('Y-m-d 00:00:00', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) . ' seconds'));
    $prev_end = $start_date;
    
    $prev_orders = get_posts(array(
        'post_type' => 'petshop_order',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'order_date',
                'value' => array($prev_start, $prev_end),
                'compare' => 'BETWEEN',
                'type' => 'DATETIME'
            ),
            array(
                'key' => 'order_status',
                'value' => 'completed',
            )
        )
    ));
    
    $prev_revenue = 0;
    $prev_order_count = count($prev_orders);
    foreach ($prev_orders as $prev_order) {
        $prev_revenue += floatval(get_post_meta($prev_order->ID, 'order_total', true));
    }
    
    $stats['prev_revenue'] = $prev_revenue;
    $stats['prev_orders'] = $prev_order_count;
    $stats['revenue_change'] = $prev_revenue > 0 ? (($stats['total_revenue'] - $prev_revenue) / $prev_revenue) * 100 : 0;
    $stats['orders_change'] = $prev_order_count > 0 ? (($stats['completed_orders'] - $prev_order_count) / $prev_order_count) * 100 : 0;
    
    return $stats;
}

// =============================================
// Láº¤Y THá»NG KÃŠ KHÃCH HÃ€NG - Tá»ª WORDPRESS USERS
// =============================================
function petshop_get_customer_stats() {
    global $wpdb;
    
    // Query tá»« WordPress users table
    $user_query = new WP_User_Query(array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'count_total' => true
    ));
    
    $total = $user_query->get_total();
    
    // KhÃ¡ch má»›i hÃ´m nay
    $new_today = count(get_users(array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'date_query' => array(
            array('after' => 'today', 'inclusive' => true)
        )
    )));
    
    // KhÃ¡ch má»›i tuáº§n nÃ y
    $new_this_week = count(get_users(array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'date_query' => array(
            array('after' => '7 days ago', 'inclusive' => true)
        )
    )));
    
    // KhÃ¡ch má»›i thÃ¡ng nÃ y
    $new_this_month = count(get_users(array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'date_query' => array(
            array('after' => date('Y-m-01'), 'inclusive' => true)
        )
    )));
    
    // Äáº¿m theo tier dá»±a trÃªn chi tiÃªu thá»±c táº¿
    $tier_counts = array('gold' => 0, 'silver' => 0, 'bronze' => 0);
    $customers = get_users(array(
        'role__in' => array('petshop_customer', 'customer', 'subscriber'),
        'fields' => 'ID'
    ));
    
    foreach ($customers as $user_id) {
        if (function_exists('petshop_crm_get_customer_stats')) {
            $customer_stats = petshop_crm_get_customer_stats($user_id);
            $tier_counts[$customer_stats['tier']]++;
        } else {
            $tier_counts['bronze']++;
        }
    }
    
    return array(
        'total' => $total,
        'new_today' => $new_today,
        'new_this_week' => $new_this_week,
        'new_this_month' => $new_this_month,
        'by_tier' => $tier_counts,
        'active_today' => 0,
    );
}

// =============================================
// KPI SETTINGS - Láº¤Y VÃ€ LÆ¯U Má»¤C TIÃŠU KPI
// =============================================
function petshop_get_kpi_targets() {
    $defaults = array(
        'revenue_monthly' => 50000000,      // 50 triá»‡u/thÃ¡ng
        'revenue_yearly' => 500000000,      // 500 triá»‡u/nÄƒm
        'orders_monthly' => 100,            // 100 Ä‘Æ¡n/thÃ¡ng
        'orders_yearly' => 1000,            // 1000 Ä‘Æ¡n/nÄƒm
        'new_customers_monthly' => 20,      // 20 khÃ¡ch má»›i/thÃ¡ng
        'conversion_rate' => 60,            // 60% tá»· lá»‡ hoÃ n thÃ nh
        'average_order_value' => 500000,    // 500k giÃ¡ trá»‹ TB
    );
    
    $saved = get_option('petshop_kpi_targets', array());
    return wp_parse_args($saved, $defaults);
}

function petshop_save_kpi_targets($targets) {
    update_option('petshop_kpi_targets', $targets);
}

// AJAX handler Ä‘á»ƒ cáº­p nháº­t KPI targets
function petshop_ajax_update_kpi_targets() {
    check_ajax_referer('petshop_crm_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('KhÃ´ng cÃ³ quyá»n truy cáº­p');
    }
    
    $targets = array(
        'revenue_monthly' => floatval($_POST['revenue_monthly'] ?? 50000000),
        'revenue_yearly' => floatval($_POST['revenue_yearly'] ?? 500000000),
        'orders_monthly' => intval($_POST['orders_monthly'] ?? 100),
        'orders_yearly' => intval($_POST['orders_yearly'] ?? 1000),
        'new_customers_monthly' => intval($_POST['new_customers_monthly'] ?? 20),
        'conversion_rate' => floatval($_POST['conversion_rate'] ?? 60),
        'average_order_value' => floatval($_POST['average_order_value'] ?? 500000),
    );
    
    petshop_save_kpi_targets($targets);
    wp_send_json_success('ÄÃ£ lÆ°u má»¥c tiÃªu KPI');
}
add_action('wp_ajax_petshop_update_kpi_targets', 'petshop_ajax_update_kpi_targets');

// TÃ­nh toÃ¡n KPI progress
function petshop_get_kpi_progress($period = 'this_month') {
    $targets = petshop_get_kpi_targets();
    $stats = petshop_get_dashboard_stats($period);
    $customer_stats = petshop_get_customer_stats();
    
    // XÃ¡c Ä‘á»‹nh targets theo period
    $is_yearly = ($period === 'this_year');
    $revenue_target = $is_yearly ? $targets['revenue_yearly'] : $targets['revenue_monthly'];
    $orders_target = $is_yearly ? $targets['orders_yearly'] : $targets['orders_monthly'];
    
    // TÃ­nh pháº§n trÄƒm hoÃ n thÃ nh
    $revenue_percent = $revenue_target > 0 ? min(100, ($stats['total_revenue'] / $revenue_target) * 100) : 0;
    $orders_percent = $orders_target > 0 ? min(100, ($stats['completed_orders'] / $orders_target) * 100) : 0;
    $customers_percent = $targets['new_customers_monthly'] > 0 ? min(100, ($customer_stats['new_this_month'] / $targets['new_customers_monthly']) * 100) : 0;
    
    // Tá»· lá»‡ chuyá»ƒn Ä‘á»•i (completed / total)
    $conversion_rate = $stats['total_orders'] > 0 ? ($stats['completed_orders'] / $stats['total_orders']) * 100 : 0;
    $conversion_percent = $targets['conversion_rate'] > 0 ? min(100, ($conversion_rate / $targets['conversion_rate']) * 100) : 0;
    
    // GiÃ¡ trá»‹ TB Ä‘Æ¡n hÃ ng
    $avg_order = $stats['average_order_value'];
    $avg_order_percent = $targets['average_order_value'] > 0 ? min(100, ($avg_order / $targets['average_order_value']) * 100) : 0;
    
    return array(
        'revenue' => array(
            'current' => $stats['total_revenue'],
            'target' => $revenue_target,
            'percent' => round($revenue_percent, 1),
            'label' => 'Doanh thu',
            'icon' => 'bi-currency-dollar',
            'color' => '#28a745'
        ),
        'orders' => array(
            'current' => $stats['completed_orders'],
            'target' => $orders_target,
            'percent' => round($orders_percent, 1),
            'label' => 'ÄÆ¡n hÃ ng hoÃ n thÃ nh',
            'icon' => 'bi-bag-check',
            'color' => '#EC802B'
        ),
        'new_customers' => array(
            'current' => $customer_stats['new_this_month'],
            'target' => $targets['new_customers_monthly'],
            'percent' => round($customers_percent, 1),
            'label' => 'KhÃ¡ch hÃ ng má»›i',
            'icon' => 'bi-person-plus',
            'color' => '#66BCB4'
        ),
        'conversion' => array(
            'current' => round($conversion_rate, 1),
            'target' => $targets['conversion_rate'],
            'percent' => round($conversion_percent, 1),
            'label' => 'Tá»· lá»‡ hoÃ n thÃ nh',
            'icon' => 'bi-check-circle',
            'color' => '#17a2b8',
            'is_percent' => true
        ),
        'avg_order' => array(
            'current' => $avg_order,
            'target' => $targets['average_order_value'],
            'percent' => round($avg_order_percent, 1),
            'label' => 'GiÃ¡ trá»‹ TB/Ä‘Æ¡n',
            'icon' => 'bi-graph-up',
            'color' => '#6f42c1'
        )
    );
}

// =============================================
// TRANG DASHBOARD - GIAO DIá»†N PRO (POWERBI STYLE)
// =============================================
