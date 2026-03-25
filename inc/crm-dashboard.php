<?php
/**
 * CRM Dashboard - Power BI Style với Grid Resize
 * Dashboard phân tích dữ liệu với drag & drop, grid resize, widget builder
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// ...existing code...

// ===== HÀM LẤY THỐNG KÊ =====

function petshop_get_order_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $where = "WHERE p.post_type = 'petshop_order' AND p.post_status = 'publish'";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND p.post_date >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND p.post_date <= %s", $end_date . ' 23:59:59');
    }
    
    $orders = $wpdb->get_results("
        SELECT p.ID, p.post_date,
               pm_status.meta_value as order_status,
               pm_total.meta_value as order_total,
               pm_customer.meta_value as customer_name
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
        LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
        LEFT JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = 'customer_name'
        $where
        ORDER BY p.post_date DESC
    ");
    
    $stats = array(
        'total_orders' => 0,
        'confirmed_orders' => 0,
        'cancelled_orders' => 0,
        'pending_orders' => 0,
        'gross_revenue' => 0,
        'avg_order_value' => 0,
        'orders_by_status' => array(),
        'orders_by_month' => array(),
        'orders_by_day' => array(),
        'recent_orders' => array()
    );
    
    $revenue_orders = 0;
    foreach ($orders as $order) {
        $stats['total_orders']++;
        $total = floatval($order->order_total);
        $status = $order->order_status ?: 'pending';
        
        if (count($stats['recent_orders']) < 10) {
            $stats['recent_orders'][] = array(
                'id' => $order->ID,
                'date' => $order->post_date,
                'customer' => $order->customer_name,
                'total' => $total,
                'status' => $status
            );
        }
        
        if (!isset($stats['orders_by_status'][$status])) {
            $stats['orders_by_status'][$status] = array('count' => 0, 'total' => 0);
        }
        $stats['orders_by_status'][$status]['count']++;
        $stats['orders_by_status'][$status]['total'] += $total;
        
        if (in_array($status, array('completed', 'processing', 'confirmed', 'shipping'))) {
            $stats['confirmed_orders']++;
            $stats['gross_revenue'] += $total;
            $revenue_orders++;
        } elseif ($status === 'cancelled') {
            $stats['cancelled_orders']++;
        } elseif ($status === 'pending') {
            $stats['pending_orders']++;
        }
        
        $month = date('Y-m', strtotime($order->post_date));
        if (!isset($stats['orders_by_month'][$month])) {
            $stats['orders_by_month'][$month] = array('count' => 0, 'total' => 0);
        }
        $stats['orders_by_month'][$month]['count']++;
        $stats['orders_by_month'][$month]['total'] += $total;
        
        $day = date('Y-m-d', strtotime($order->post_date));
        if (!isset($stats['orders_by_day'][$day])) {
            $stats['orders_by_day'][$day] = array('count' => 0, 'total' => 0);
        }
        $stats['orders_by_day'][$day]['count']++;
        $stats['orders_by_day'][$day]['total'] += $total;
    }
    
    if ($revenue_orders > 0) {
        $stats['avg_order_value'] = $stats['gross_revenue'] / $revenue_orders;
    }
    
    ksort($stats['orders_by_month']);
    ksort($stats['orders_by_day']);
    
    return $stats;
}

function petshop_get_traffic_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $sessions_table = $wpdb->prefix . 'petshop_sessions';
    $pageviews_table = $wpdb->prefix . 'petshop_pageviews';
    
    $stats = array(
        'total_sessions' => 0,
        'total_pageviews' => 0,
        'unique_visitors' => 0,
        'avg_pages_per_session' => 0,
        'sessions_by_source' => array(),
        'sessions_by_device' => array(),
        'sessions_by_browser' => array(),
        'sessions_by_day' => array(),
        'sessions_by_hour' => array()
    );
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") !== $sessions_table) {
        return $stats;
    }
    
    $where = "WHERE 1=1";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND created_at >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND created_at <= %s", $end_date . ' 23:59:59');
    }
    
    $stats['total_sessions'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $sessions_table $where");
    $stats['unique_visitors'] = (int)$wpdb->get_var("SELECT COUNT(DISTINCT visitor_id) FROM $sessions_table $where");
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table) {
        $stats['total_pageviews'] = (int)$wpdb->get_var("
            SELECT COUNT(*) FROM $pageviews_table pv
            INNER JOIN $sessions_table s ON pv.session_id = s.id
            $where
        ");
    }
    
    if ($stats['total_sessions'] > 0) {
        $stats['avg_pages_per_session'] = round($stats['total_pageviews'] / $stats['total_sessions'], 2);
    }
    
    $sources = $wpdb->get_results("SELECT source, COUNT(*) as count FROM $sessions_table $where GROUP BY source ORDER BY count DESC");
    foreach ($sources as $row) {
        $stats['sessions_by_source'][$row->source ?: 'Direct'] = (int)$row->count;
    }
    
    $devices = $wpdb->get_results("SELECT device, COUNT(*) as count FROM $sessions_table $where GROUP BY device ORDER BY count DESC");
    foreach ($devices as $row) {
        $stats['sessions_by_device'][$row->device ?: 'Unknown'] = (int)$row->count;
    }
    
    $browsers = $wpdb->get_results("SELECT browser, COUNT(*) as count FROM $sessions_table $where GROUP BY browser ORDER BY count DESC");
    foreach ($browsers as $row) {
        $stats['sessions_by_browser'][$row->browser ?: 'Unknown'] = (int)$row->count;
    }
    
    $days = $wpdb->get_results("SELECT DATE(created_at) as day, COUNT(*) as count FROM $sessions_table $where GROUP BY DATE(created_at) ORDER BY day");
    foreach ($days as $row) {
        $stats['sessions_by_day'][$row->day] = (int)$row->count;
    }
    
    $hours = $wpdb->get_results("SELECT HOUR(created_at) as hour, COUNT(*) as count FROM $sessions_table $where GROUP BY HOUR(created_at) ORDER BY hour");
    for ($h = 0; $h < 24; $h++) {
        $stats['sessions_by_hour'][$h] = 0;
    }
    foreach ($hours as $row) {
        $stats['sessions_by_hour'][(int)$row->hour] = (int)$row->count;
    }
    
    return $stats;
}

function petshop_get_user_stats() {
    global $wpdb;
    
    $stats = array(
        'total_users' => 0,
        'new_users_today' => 0,
        'new_users_week' => 0,
        'new_users_month' => 0
    );
    
    $stats['total_users'] = (int)$wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
        AND um.meta_value LIKE '%petshop_customer%'
    ");
    
    $stats['new_users_today'] = (int)$wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
        AND um.meta_value LIKE '%petshop_customer%'
        AND DATE(u.user_registered) = %s
    ", date('Y-m-d')));
    
    $stats['new_users_week'] = (int)$wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
        AND um.meta_value LIKE '%petshop_customer%'
        AND u.user_registered >= %s
    ", date('Y-m-d', strtotime('-7 days'))));
    
    return $stats;
}

function petshop_get_event_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $events_table = $wpdb->prefix . 'petshop_events';
    
    $stats = array(
        'view_product' => 0,
        'add_to_cart' => 0,
        'begin_checkout' => 0,
        'purchase' => 0,
        'conversion_rate' => 0
    );
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$events_table'") !== $events_table) {
        return $stats;
    }
    
    $where = "WHERE 1=1";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND created_at >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND created_at <= %s", $end_date . ' 23:59:59');
    }
    
    $events = $wpdb->get_results("SELECT event_type, COUNT(*) as count FROM $events_table $where GROUP BY event_type");
    
    foreach ($events as $row) {
        if (isset($stats[$row->event_type])) {
            $stats[$row->event_type] = (int)$row->count;
        }
    }
    
    if ($stats['view_product'] > 0) {
        $stats['conversion_rate'] = round(($stats['purchase'] / $stats['view_product']) * 100, 2);
    }
    
    return $stats;
}

function petshop_get_product_stats() {
    global $wpdb;
    
    $stats = array(
        'total_products' => 0,
        'in_stock' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0
    );
    
    $stats['total_products'] = (int)$wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} 
        WHERE post_type = 'product' AND post_status = 'publish'
    ");
    
    $products = $wpdb->get_results("
        SELECT p.ID, pm.meta_value as stock
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'product_stock'
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ");
    
    foreach ($products as $product) {
        $stock = intval($product->stock);
        if ($stock <= 0) $stats['out_of_stock']++;
        elseif ($stock <= 10) $stats['low_stock']++;
        else $stats['in_stock']++;
    }
    
    return $stats;
}

// ===== LƯU/LẤY CẤU HÌNH USER =====

function petshop_get_dashboard_config($user_id) {
    $config = get_user_meta($user_id, 'petshop_dashboard_config_v2', true);
    if (!$config) {
        $config = petshop_get_default_dashboard_config();
    }
    return $config;
}

function petshop_get_default_dashboard_config() {
    return array(
        'widgets' => array(
            array('id' => 'w1', 'dataSource' => 'sessions', 'chartType' => 'kpi', 'x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Sessions'),
            array('id' => 'w2', 'dataSource' => 'pageviews', 'chartType' => 'kpi', 'x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Pageviews'),
            array('id' => 'w3', 'dataSource' => 'visitors', 'chartType' => 'kpi', 'x' => 2, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Visitors'),
            array('id' => 'w4', 'dataSource' => 'pages_per_session', 'chartType' => 'kpi', 'x' => 3, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Pages/Session'),
            array('id' => 'w5', 'dataSource' => 'orders', 'chartType' => 'kpi', 'x' => 4, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Đơn hàng'),
            array('id' => 'w6', 'dataSource' => 'revenue', 'chartType' => 'kpi', 'x' => 5, 'y' => 0, 'w' => 1, 'h' => 1, 'title' => 'Doanh thu'),
            array('id' => 'w7', 'dataSource' => 'sessions_by_day', 'chartType' => 'line', 'x' => 0, 'y' => 1, 'w' => 3, 'h' => 2, 'title' => 'Sessions theo ngày'),
            array('id' => 'w8', 'dataSource' => 'traffic_source', 'chartType' => 'doughnut', 'x' => 3, 'y' => 1, 'w' => 2, 'h' => 2, 'title' => 'Nguồn Traffic'),
            array('id' => 'w9', 'dataSource' => 'devices', 'chartType' => 'pie', 'x' => 5, 'y' => 1, 'w' => 1, 'h' => 2, 'title' => 'Thiết bị'),
            array('id' => 'w10', 'dataSource' => 'orders_by_month', 'chartType' => 'bar', 'x' => 0, 'y' => 3, 'w' => 3, 'h' => 2, 'title' => 'Đơn hàng theo tháng'),
            array('id' => 'w11', 'dataSource' => 'funnel', 'chartType' => 'horizontalBar', 'x' => 3, 'y' => 3, 'w' => 3, 'h' => 2, 'title' => 'Conversion Funnel'),
        )
    );
}

add_action('wp_ajax_petshop_save_dashboard_config', 'petshop_ajax_save_dashboard_config');
function petshop_ajax_save_dashboard_config() {
    check_ajax_referer('petshop_dashboard', 'nonce');
    
    $config = isset($_POST['config']) ? json_decode(stripslashes($_POST['config']), true) : null;
    if (!$config) {
        wp_send_json_error('Invalid config');
    }
    
    update_user_meta(get_current_user_id(), 'petshop_dashboard_config_v2', $config);
    wp_send_json_success();
}

add_action('wp_ajax_petshop_reset_dashboard_config', 'petshop_ajax_reset_dashboard_config');
function petshop_ajax_reset_dashboard_config() {
    check_ajax_referer('petshop_dashboard', 'nonce');
    delete_user_meta(get_current_user_id(), 'petshop_dashboard_config_v2');
    wp_send_json_success();
}

// ===== TRANG DASHBOARD CHÍNH =====

function petshop_crm_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền truy cập trang này.');
    }
    
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    
    $order_stats = petshop_get_order_stats($start_date, $end_date);
    $traffic_stats = petshop_get_traffic_stats($start_date, $end_date);
    $user_stats = petshop_get_user_stats();
    $event_stats = petshop_get_event_stats($start_date, $end_date);
    $product_stats = petshop_get_product_stats();
    
    $config = petshop_get_dashboard_config(get_current_user_id());
    
    // Prepare all data for JS
    $dashboard_data = array(
        'sessions' => $traffic_stats['total_sessions'],
        'pageviews' => $traffic_stats['total_pageviews'],
        'visitors' => $traffic_stats['unique_visitors'],
        'pages_per_session' => $traffic_stats['avg_pages_per_session'],
        'orders' => $order_stats['total_orders'],
        'revenue' => $order_stats['gross_revenue'],
        'users' => $user_stats['total_users'],
        'products' => $product_stats['total_products'],
        'sessions_by_day' => $traffic_stats['sessions_by_day'],
        'traffic_source' => $traffic_stats['sessions_by_source'],
        'devices' => $traffic_stats['sessions_by_device'],
        'browsers' => $traffic_stats['sessions_by_browser'],
        'hours' => $traffic_stats['sessions_by_hour'],
        'orders_by_month' => $order_stats['orders_by_month'],
        'orders_by_day' => $order_stats['orders_by_day'],
        'order_status' => $order_stats['orders_by_status'],
        'funnel' => $event_stats,
        'recent_orders' => $order_stats['recent_orders'],
        'new_users_today' => $user_stats['new_users_today'],
        'low_stock' => $product_stats['low_stock']
    );
    
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
    /* ===== POWER BI DASHBOARD - GRID LAYOUT ===== */
    :root {
        --grid-cols: 6;
        --grid-gap: 12px;
        --cell-height: 130px;
        --header-bg: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
        --card-bg: #ffffff;
        --border-color: #e5e7eb;
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --primary-color: #EC802B;
        --primary-light: #F5994D;
        --secondary-color: #66BCB4;
        --secondary-light: #7ECEC6;
        --accent-blue: #66BCB4;
        --accent-green: #10b981;
        --accent-orange: #EC802B;
        --accent-purple: #66BCB4;
        --accent-red: #ef4444;
    }
    
    * { box-sizing: border-box; }
    
    .pbi-dashboard {
        background: #f3f4f6;
        min-height: 100vh;
        margin: -20px 0 0 -20px;
        width: calc(100% + 20px);
        padding-bottom: 50px;
    }
    
    /* Header */
    .pbi-header {
        background: var(--header-bg);
        color: white;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 32px;
        z-index: 100;
        box-shadow: 0 4px 15px rgba(236, 128, 43, 0.3);
        gap: 16px;
    }
    
    .pbi-header h1 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    
    .pbi-header-center {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    
    .pbi-header-filter {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .pbi-header-filter label {
        font-size: 12px;
        color: rgba(255,255,255,0.9);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .pbi-header-filter .pbi-input {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .pbi-header-filter .pbi-input:focus {
        background: rgba(255,255,255,0.25);
        border-color: rgba(255,255,255,0.5);
    }
    
    .pbi-header-filter .pbi-btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .pbi-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }
    
    .pbi-btn {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .pbi-btn-light {
        background: rgba(255,255,255,0.2);
        color: white;
        backdrop-filter: blur(10px);
    }
    .pbi-btn-light:hover { background: rgba(255,255,255,0.3); }
    
    .pbi-btn-white {
        background: white;
        color: #EC802B;
    }
    .pbi-btn-white:hover { background: #f3f4f6; transform: translateY(-1px); }
    
    .pbi-btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .pbi-btn-primary:hover { background: #D6701F; }
    
    .pbi-btn-success {
        background: var(--secondary-color);
        color: white;
    }
    .pbi-btn-success:hover { background: #5AA9A2; }
    
    .pbi-btn-danger {
        background: var(--accent-red);
        color: white;
    }
    
    /* Toolbar (Edit Mode Hint only) */
    .pbi-toolbar {
        background: #fffbeb;
        border-bottom: 1px solid #fbbf24;
        padding: 10px 24px;
        display: none;
        align-items: center;
        gap: 20px;
    }
    
    .pbi-toolbar.show {
        display: flex;
    }
    
    .pbi-input {
        padding: 8px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .pbi-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(236, 128, 43, 0.15);
    }
    
    .pbi-edit-mode-hint {
        margin-left: auto;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #FEF3E2;
        border-radius: 8px;
        color: #92400e;
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .pbi-edit-mode-hint.hidden { display: none; }
    
    /* Grid Container */
    .pbi-grid {
        padding: 24px;
        padding-top: 28px;
        display: grid;
        grid-template-columns: repeat(var(--grid-cols), 1fr);
        grid-auto-rows: var(--cell-height);
        gap: var(--grid-gap);
        position: relative;
    }
    
    /* Grid Overlay for resize preview */
    .pbi-grid-overlay {
        position: absolute;
        top: 28px;
        left: 24px;
        right: 24px;
        bottom: 24px;
        display: none;
        grid-template-columns: repeat(var(--grid-cols), 1fr);
        grid-auto-rows: var(--cell-height);
        gap: var(--grid-gap);
        pointer-events: none;
        z-index: 5;
    }
    
    .pbi-grid.edit-mode .pbi-grid-overlay {
        display: grid;
    }
    
    .pbi-grid-cell {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        background: rgba(236, 128, 43, 0.03);
    }
    
    /* Widget */
    .pbi-widget {
        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        position: relative;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s;
        overflow: hidden;
    }
    
    .pbi-widget:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .pbi-grid.edit-mode .pbi-widget {
        cursor: move;
    }
    
    .pbi-grid.edit-mode .pbi-widget:hover {
        box-shadow: 0 0 0 2px var(--primary-color), 0 4px 15px rgba(0,0,0,0.15);
    }
    
    .pbi-widget.dragging {
        opacity: 0.7;
        z-index: 100;
    }
    
    .pbi-widget.resizing {
        z-index: 100;
    }
    
    /* Widget Header */
    .pbi-widget-header {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f9fafb;
        min-height: 46px;
    }
    
    .pbi-widget-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pbi-widget-title i {
        color: var(--primary-color);
    }
    
    .pbi-widget-actions {
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .pbi-widget:hover .pbi-widget-actions,
    .pbi-grid.edit-mode .pbi-widget-actions {
        opacity: 1;
    }
    
    .pbi-widget-btn {
        width: 28px;
        height: 28px;
        border: none;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        font-size: 14px;
        transition: all 0.15s;
    }
    
    .pbi-widget-btn:hover {
        background: #e5e7eb;
        color: var(--text-primary);
    }
    
    .pbi-widget-btn.delete:hover {
        background: #fef2f2;
        color: var(--accent-red);
    }
    
    /* Time Period Selector */
    .pbi-time-selector {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-right: 8px;
    }
    
    .pbi-time-btn {
        padding: 3px 8px;
        font-size: 11px;
        border: 1px solid var(--border-color);
        background: white;
        border-radius: 4px;
        cursor: pointer;
        color: var(--text-secondary);
        font-weight: 500;
        transition: all 0.15s;
    }
    
    .pbi-time-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .pbi-time-btn.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }
    
    /* Widget Action Dropdown */
    .pbi-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .pbi-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 140px;
        z-index: 100;
        display: none;
        overflow: hidden;
    }
    
    .pbi-dropdown-menu.show {
        display: block;
    }
    
    .pbi-dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: 13px;
        color: var(--text-primary);
        cursor: pointer;
        transition: background 0.15s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }
    
    .pbi-dropdown-item:hover {
        background: #f3f4f6;
    }
    
    .pbi-dropdown-item i {
        color: var(--text-secondary);
        font-size: 14px;
    }
    
    .pbi-dropdown-divider {
        border-top: 1px solid var(--border-color);
        margin: 4px 0;
    }
    
    /* Power BI Embed Section */
    .pbi-embed-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin: 24px;
        margin-top: 0;
        overflow: hidden;
    }
    
    .pbi-embed-header {
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    
    .pbi-embed-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pbi-embed-header h3 i {
        color: #f2c811;
    }
    
    .pbi-embed-body {
        padding: 0;
        height: 550px;
    }
    
    .pbi-embed-body iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    .pbi-embed-actions {
        display: flex;
        gap: 8px;
    }
    
    .pbi-embed-btn {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-secondary);
        transition: all 0.15s;
    }
    
    .pbi-embed-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .pbi-embed-section.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        margin: 0;
        border-radius: 0;
        z-index: 9999;
    }
    
    .pbi-embed-section.fullscreen .pbi-embed-body {
        height: calc(100vh - 60px);
    }
    
    /* Widget Body */
    .pbi-widget-body {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }
    
    /* Resize Handles */
    .pbi-resize-handle {
        position: absolute;
        background: transparent;
        z-index: 10;
        display: none;
    }
    
    .pbi-grid.edit-mode .pbi-resize-handle {
        display: block;
    }
    
    .pbi-resize-handle-e {
        right: 0;
        top: 10%;
        width: 8px;
        height: 80%;
        cursor: ew-resize;
    }
    
    .pbi-resize-handle-s {
        bottom: 0;
        left: 10%;
        width: 80%;
        height: 8px;
        cursor: ns-resize;
    }
    
    .pbi-resize-handle-se {
        right: 0;
        bottom: 0;
        width: 20px;
        height: 20px;
        cursor: nwse-resize;
    }
    
    .pbi-resize-handle-se::after {
        content: '';
        position: absolute;
        right: 4px;
        bottom: 4px;
        width: 10px;
        height: 10px;
        border-right: 2px solid #9ca3af;
        border-bottom: 2px solid #9ca3af;
    }
    
    .pbi-grid.edit-mode .pbi-widget:hover .pbi-resize-handle-se::after {
        border-color: var(--primary-color);
    }
    
    /* KPI Card */
    .pbi-kpi-body {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        height: 100%;
    }
    
    .pbi-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    
    .pbi-kpi-icon.blue { background: linear-gradient(135deg, #D1EDEB 0%, #B8E4E1 100%); color: var(--secondary-color); }
    .pbi-kpi-icon.green { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: var(--accent-green); }
    .pbi-kpi-icon.orange { background: linear-gradient(135deg, #FDEAD7 0%, #FBD5B0 100%); color: var(--primary-color); }
    .pbi-kpi-icon.purple { background: linear-gradient(135deg, #D1EDEB 0%, #B8E4E1 100%); color: var(--secondary-color); }
    .pbi-kpi-icon.red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: var(--accent-red); }
    
    .pbi-kpi-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
        flex: 1;
        min-width: 0;
    }
    
    .pbi-kpi-label {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .pbi-kpi-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
    }
    
    .pbi-kpi-change {
        font-size: 11px;
        margin-top: 8px;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    .pbi-kpi-change.up { background: #d1fae5; color: #059669; }
    .pbi-kpi-change.down { background: #fee2e2; color: #dc2626; }
    
    /* Chart Container */
    .pbi-chart-wrap {
        flex: 1;
        min-height: 0;
        position: relative;
    }
    
    .pbi-chart-wrap canvas {
        max-width: 100%;
        max-height: 100%;
    }
    
    /* Table */
    .pbi-table-wrap {
        flex: 1;
        overflow: auto;
    }
    
    .pbi-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    
    .pbi-table th {
        text-align: left;
        padding: 10px 12px;
        background: #f9fafb;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
        position: sticky;
        top: 0;
    }
    
    .pbi-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .pbi-table tr:hover td {
        background: #f9fafb;
    }
    
    .pbi-progress {
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .pbi-progress-bar {
        height: 100%;
        background: var(--primary-color);
        border-radius: 3px;
    }
    
    .pbi-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .pbi-badge-success { background: #D1EDEB; color: #4A9D96; }
    .pbi-badge-warning { background: #FDEAD7; color: #D67020; }
    .pbi-badge-danger { background: #fee2e2; color: #dc2626; }
    .pbi-badge-info { background: #FDEAD7; color: #EC802B; }
    
    .pbi-no-data {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-secondary);
        text-align: center;
    }
    
    .pbi-no-data i {
        font-size: 40px;
        margin-bottom: 12px;
        opacity: 0.3;
    }
    
    /* ===== WIDGET BUILDER MODAL ===== */
    .pbi-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }
    
    .pbi-modal-overlay.active {
        display: flex;
    }
    
    .pbi-modal {
        background: white;
        border-radius: 16px;
        width: 95%;
        max-width: 900px;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    }
    
    .pbi-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
        color: white;
    }
    
    .pbi-modal-header h2 {
        margin: 0;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pbi-modal-close {
        width: 36px;
        height: 36px;
        border: none;
        background: rgba(255,255,255,0.2);
        border-radius: 8px;
        color: white;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .pbi-modal-close:hover { background: rgba(255,255,255,0.3); }
    
    .pbi-modal-body {
        padding: 0;
        overflow: hidden;
        flex: 1;
        display: flex;
    }
    
    /* New Layout: Preview Left, Tools Right */
    .pbi-builder-preview {
        flex: 1;
        padding: 24px;
        background: #f9fafb;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    
    .pbi-builder-preview h4 {
        margin: 0 0 16px 0;
        font-size: 14px;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pbi-builder-preview h4 i {
        color: var(--primary-color);
    }
    
    .pbi-preview-box {
        flex: 1;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
    }
    
    .pbi-builder-sidebar {
        width: 320px;
        border-left: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        flex-shrink: 0;
    }
    
    .pbi-sidebar-section {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .pbi-sidebar-section:last-child {
        border-bottom: none;
    }
    
    .pbi-sidebar-section h4 {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: var(--text-primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pbi-sidebar-section h4 i {
        color: var(--primary-color);
        font-size: 14px;
    }
    
    @media (max-width: 800px) {
        .pbi-modal-body { flex-direction: column; }
        .pbi-builder-sidebar { width: 100%; border-left: none; border-top: 1px solid var(--border-color); max-height: 50vh; }
    }
    
    .pbi-builder-section h4 {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pbi-builder-section h4 i {
        color: var(--primary-color);
    }
    
    /* Data Source Selection */
    .pbi-source-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .pbi-source-item {
        padding: 10px 6px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    
    .pbi-source-item:hover {
        border-color: var(--primary-color);
        background: #FEF3E2;
    }
    
    .pbi-source-item.selected {
        border-color: var(--primary-color);
        background: #FDEAD7;
    }
    
    .pbi-source-item i {
        font-size: 18px;
        color: var(--primary-color);
        display: block;
        margin-bottom: 4px;
    }
    
    .pbi-source-item span {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-primary);
        line-height: 1.2;
        display: block;
    }
    
    /* Chart Type Selection */
    .pbi-chart-types {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .pbi-chart-type {
        padding: 10px 6px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    
    .pbi-chart-type:hover {
        border-color: var(--secondary-color);
        background: #E8F5F4;
    }
    
    .pbi-chart-type.selected {
        border-color: var(--secondary-color);
        background: #D1EDEB;
    }
    
    .pbi-chart-type i {
        font-size: 18px;
        color: var(--secondary-color);
        display: block;
        margin-bottom: 4px;
    }
    
    .pbi-chart-type span {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-primary);
        line-height: 1.2;
        display: block;
    }
    
    /* Preview Placeholder */
    .pbi-preview-placeholder {
        text-align: center;
        color: var(--text-secondary);
    }
    
    .pbi-preview-placeholder i {
        font-size: 48px;
        opacity: 0.3;
        margin-bottom: 12px;
        display: block;
    }
    
    .pbi-preview-chart {
        width: 100%;
        height: 100%;
    }
    
    /* Title Input */
    .pbi-title-input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .pbi-title-input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .pbi-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #f9fafb;
    }
    </style>
    
    <div class="pbi-dashboard">
        <!-- Header with integrated filter -->
        <div class="pbi-header">
            <h1>
                <i class="bi bi-speedometer2"></i>
                CRM Dashboard
            </h1>
            
            <div class="pbi-header-center">
                <form method="get" class="pbi-header-filter">
                    <input type="hidden" name="page" value="petshop-crm">
                    <label><i class="bi bi-calendar3"></i> Từ:</label>
                    <input type="date" name="start_date" class="pbi-input" value="<?php echo esc_attr($start_date); ?>">
                    <label>Đến:</label>
                    <input type="date" name="end_date" class="pbi-input" value="<?php echo esc_attr($end_date); ?>">
                    <button type="submit" class="pbi-btn pbi-btn-light">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                </form>
            </div>
            
            <div class="pbi-header-actions">
                <button class="pbi-btn pbi-btn-white hidden" onclick="saveDashboard()" id="saveDashboardBtn">
                    <i class="bi bi-check-lg"></i> Lưu
                </button>
                <button class="pbi-btn pbi-btn-light" onclick="toggleEditMode()" id="editModeBtn">
                    <i class="bi bi-pencil-square"></i> Chỉnh sửa
                </button>
                <button class="pbi-btn pbi-btn-light" onclick="openWidgetBuilder()">
                    <i class="bi bi-plus-lg"></i> Thêm Widget
                </button>
                <button class="pbi-btn pbi-btn-light" onclick="resetDashboard()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
        
        <!-- Edit Mode Toolbar -->
        <div class="pbi-toolbar" id="editModeToolbar">
            <i class="bi bi-info-circle" style="color: #d97706;"></i>
            <span style="color: #92400e; font-size: 13px;">Kéo để di chuyển • Kéo góc/cạnh để resize • Click <strong>Lưu</strong> khi xong</span>
        </div>
        
        <!-- Grid Container -->
        <div class="pbi-grid" id="dashboardGrid">
            <!-- Grid overlay for visual feedback -->
            <div class="pbi-grid-overlay" id="gridOverlay">
                <?php for ($i = 0; $i < 36; $i++): ?>
                <div class="pbi-grid-cell"></div>
                <?php endfor; ?>
            </div>
            
            <!-- Widgets will be rendered by JS -->
        </div>
        
        <!-- Power BI Embed Section -->
        <div class="pbi-embed-section" id="powerbiSection">
            <div class="pbi-embed-header">
                <h3><i class="bi bi-bar-chart-fill"></i> Power BI Report</h3>
                <div class="pbi-embed-actions">
                    <button class="pbi-embed-btn" onclick="togglePBIFullscreen()" title="Toàn màn hình">
                        <i class="bi bi-fullscreen"></i> Mở rộng
                    </button>
                    <button class="pbi-embed-btn" onclick="window.open('https://app.powerbi.com/reportEmbed?reportId=e248bd24-3ab5-407f-8579-74b7261e25f6&autoAuth=true&ctid=3011a54b-0a5d-4929-bf02-a00787877c6a', '_blank')" title="Mở trong tab mới">
                        <i class="bi bi-box-arrow-up-right"></i> Tab mới
                    </button>
                </div>
            </div>
            <div class="pbi-embed-body" id="pbiEmbedBody">
                <iframe 
                    title="TestPBI" 
                    src="https://app.powerbi.com/reportEmbed?reportId=e248bd24-3ab5-407f-8579-74b7261e25f6&autoAuth=true&ctid=3011a54b-0a5d-4929-bf02-a00787877c6a&actionBarEnabled=true&reportCopilotInEmbed=true" 
                    allowFullScreen="true">
                </iframe>
            </div>
        </div>
    </div>
    
    <!-- Widget Builder Modal -->
    <div class="pbi-modal-overlay" id="widgetBuilderModal">
        <div class="pbi-modal">
            <div class="pbi-modal-header">
                <h2><i class="bi bi-magic"></i> Tạo Widget mới</h2>
                <button class="pbi-modal-close" onclick="closeWidgetBuilder()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="pbi-modal-body">
                <!-- Preview Section (Left) -->
                <div class="pbi-builder-preview">
                    <h4><i class="bi bi-eye"></i> Xem trước</h4>
                    <div class="pbi-preview-box" id="previewBox">
                        <div class="pbi-preview-placeholder">
                            <i class="bi bi-bar-chart-line"></i>
                            <p>Chọn nguồn dữ liệu và loại biểu đồ để xem trước</p>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar (Right) -->
                <div class="pbi-builder-sidebar">
                    <div class="pbi-sidebar-section">
                        <h4><i class="bi bi-fonts"></i> Tiêu đề Widget</h4>
                        <input type="text" class="pbi-title-input" id="widgetTitle" placeholder="Nhập tiêu đề...">
                    </div>
                    
                    <div class="pbi-sidebar-section">
                        <h4><i class="bi bi-database"></i> Nguồn dữ liệu</h4>
                        <div class="pbi-source-grid" id="sourceGrid">
                            <div class="pbi-source-item" data-source="sessions">
                                <i class="bi bi-activity"></i>
                                <span>Sessions</span>
                            </div>
                            <div class="pbi-source-item" data-source="pageviews">
                                <i class="bi bi-eye"></i>
                                <span>Pageviews</span>
                            </div>
                            <div class="pbi-source-item" data-source="visitors">
                                <i class="bi bi-people"></i>
                                <span>Visitors</span>
                            </div>
                            <div class="pbi-source-item" data-source="orders">
                                <i class="bi bi-cart3"></i>
                                <span>Đơn hàng</span>
                            </div>
                            <div class="pbi-source-item" data-source="revenue">
                                <i class="bi bi-currency-dollar"></i>
                                <span>Doanh thu</span>
                            </div>
                            <div class="pbi-source-item" data-source="users">
                                <i class="bi bi-person-check"></i>
                                <span>Khách hàng</span>
                            </div>
                            <div class="pbi-source-item" data-source="products">
                                <i class="bi bi-box"></i>
                                <span>Sản phẩm</span>
                            </div>
                            <div class="pbi-source-item" data-source="sessions_by_day">
                                <i class="bi bi-graph-up"></i>
                                <span>Sessions/Ngày</span>
                            </div>
                            <div class="pbi-source-item" data-source="traffic_source">
                                <i class="bi bi-diagram-3"></i>
                                <span>Nguồn Traffic</span>
                            </div>
                            <div class="pbi-source-item" data-source="devices">
                                <i class="bi bi-phone"></i>
                                <span>Thiết bị</span>
                            </div>
                            <div class="pbi-source-item" data-source="browsers">
                                <i class="bi bi-globe"></i>
                                <span>Trình duyệt</span>
                            </div>
                            <div class="pbi-source-item" data-source="hours">
                                <i class="bi bi-clock"></i>
                                <span>Traffic/Giờ</span>
                            </div>
                            <div class="pbi-source-item" data-source="orders_by_day">
                                <i class="bi bi-calendar3"></i>
                                <span>Đơn/Ngày</span>
                            </div>
                            <div class="pbi-source-item" data-source="orders_by_month">
                                <i class="bi bi-bar-chart"></i>
                                <span>Đơn/Tháng</span>
                            </div>
                            <div class="pbi-source-item" data-source="order_status">
                                <i class="bi bi-check2-circle"></i>
                                <span>Trạng thái</span>
                            </div>
                            <div class="pbi-source-item" data-source="funnel">
                                <i class="bi bi-funnel"></i>
                                <span>Funnel</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pbi-sidebar-section">
                        <h4><i class="bi bi-bar-chart-line"></i> Loại biểu đồ</h4>
                        <div class="pbi-chart-types" id="chartTypes">
                            <div class="pbi-chart-type" data-type="kpi">
                                <i class="bi bi-123"></i>
                                <span>Số liệu</span>
                            </div>
                            <div class="pbi-chart-type" data-type="line">
                                <i class="bi bi-graph-up"></i>
                                <span>Đường</span>
                            </div>
                            <div class="pbi-chart-type" data-type="bar">
                                <i class="bi bi-bar-chart"></i>
                                <span>Cột dọc</span>
                            </div>
                            <div class="pbi-chart-type" data-type="horizontalBar">
                                <i class="bi bi-bar-chart-horizontal"></i>
                                <span>Cột ngang</span>
                            </div>
                            <div class="pbi-chart-type" data-type="pie">
                                <i class="bi bi-pie-chart"></i>
                                <span>Tròn</span>
                            </div>
                            <div class="pbi-chart-type" data-type="doughnut">
                                <i class="bi bi-circle"></i>
                                <span>Donut</span>
                            </div>
                            <div class="pbi-chart-type" data-type="area">
                                <i class="bi bi-graph-down"></i>
                                <span>Diện tích</span>
                            </div>
                            <div class="pbi-chart-type" data-type="table">
                                <i class="bi bi-table"></i>
                                <span>Bảng</span>
                            </div>
                            <div class="pbi-chart-type" data-type="radar">
                                <i class="bi bi-pentagon"></i>
                                <span>Radar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pbi-modal-footer">
                <button class="pbi-btn" style="background: #e5e7eb; color: #374151;" onclick="closeWidgetBuilder()">Hủy</button>
                <button class="pbi-btn pbi-btn-success" onclick="saveWidgetFromBuilder()" id="addWidgetBtn" disabled>
                    <i class="bi bi-check-lg"></i> <span id="addWidgetBtnText">Thêm Widget</span>
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // ===== DASHBOARD DATA =====
    const dashboardData = <?php echo json_encode($dashboard_data); ?>;
    let dashboardConfig = <?php echo json_encode($config); ?>;
    let isEditMode = false;
    let previewChart = null;
    let editingWidgetId = null; // Track which widget is being edited
    
    const chartColors = ['#EC802B', '#66BCB4', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#06b6d4', '#84cc16'];
    
    // Data source definitions - with time mode support
    const dataSources = {
        sessions: { label: 'Sessions', icon: 'bi-activity', color: 'blue', getValue: () => dashboardData.sessions || 0 },
        pageviews: { label: 'Pageviews', icon: 'bi-eye', color: 'green', getValue: () => dashboardData.pageviews || 0 },
        visitors: { label: 'Visitors', icon: 'bi-people', color: 'purple', getValue: () => dashboardData.visitors || 0 },
        pages_per_session: { label: 'Pages/Session', icon: 'bi-layers', color: 'orange', getValue: () => dashboardData.pages_per_session || 0 },
        orders: { label: 'Đơn hàng', icon: 'bi-cart3', color: 'blue', getValue: () => dashboardData.orders || 0 },
        revenue: { label: 'Doanh thu', icon: 'bi-currency-dollar', color: 'green', getValue: () => formatRevenue(dashboardData.revenue || 0) },
        users: { label: 'Khách hàng', icon: 'bi-person-check', color: 'purple', getValue: () => dashboardData.users || 0 },
        products: { label: 'Sản phẩm', icon: 'bi-box', color: 'orange', getValue: () => dashboardData.products || 0 },
        sessions_by_day: { 
            label: 'Sessions/Thời gian', 
            icon: 'bi-graph-up', 
            hasTimeMode: true,
            rawData: () => dashboardData.sessions_by_day || {},
            getData: function(mode = 'day') { return aggregateTimeData(this.rawData(), mode); }
        },
        traffic_source: { label: 'Nguồn Traffic', icon: 'bi-diagram-3', getData: () => dashboardData.traffic_source || {} },
        devices: { label: 'Thiết bị', icon: 'bi-phone', getData: () => dashboardData.devices || {} },
        browsers: { label: 'Trình duyệt', icon: 'bi-globe', getData: () => dashboardData.browsers || {} },
        hours: { label: 'Traffic/Giờ', icon: 'bi-clock', getData: () => dashboardData.hours || {} },
        orders_by_day: { 
            label: 'Đơn/Thời gian', 
            icon: 'bi-calendar3', 
            hasTimeMode: true,
            rawData: () => {
                if (!dashboardData.orders_by_day) return {};
                const data = {};
                Object.entries(dashboardData.orders_by_day).forEach(([k, v]) => data[k] = v.count || 0);
                return data;
            },
            getData: function(mode = 'day') { return aggregateTimeData(this.rawData(), mode); }
        },
        revenue_by_day: { 
            label: 'Doanh thu/Thời gian', 
            icon: 'bi-cash-stack', 
            hasTimeMode: true,
            rawData: () => {
                if (!dashboardData.orders_by_day) return {};
                const data = {};
                Object.entries(dashboardData.orders_by_day).forEach(([k, v]) => data[k] = v.total || 0);
                return data;
            },
            getData: function(mode = 'day') { return aggregateTimeData(this.rawData(), mode); }
        },
        orders_by_month: { 
            label: 'Đơn/Tháng', 
            icon: 'bi-bar-chart', 
            hasTimeMode: true,
            timeDefault: 'month',
            rawData: () => {
                if (!dashboardData.orders_by_month) return {};
                const data = {};
                Object.entries(dashboardData.orders_by_month).forEach(([k, v]) => data[k] = v.count || v.total || 0);
                return data;
            },
            getData: function(mode = 'month') { 
                // This already has monthly data, aggregate further if needed
                const raw = this.rawData();
                if (mode === 'month') return raw;
                if (mode === 'quarter') return aggregateMonthlyToQuarter(raw);
                if (mode === 'year') return aggregateMonthlyToYear(raw);
                return raw;
            }
        },
        order_status: { label: 'Trạng thái đơn', icon: 'bi-check2-circle', getData: () => {
            if (!dashboardData.order_status) return {};
            const data = {};
            Object.entries(dashboardData.order_status).forEach(([k, v]) => data[k] = v.count || 0);
            return data;
        }},
        funnel: { label: 'Funnel', icon: 'bi-funnel', getData: () => {
            if (!dashboardData.funnel) return {};
            return {
                'Xem SP': dashboardData.funnel.view_product || 0,
                'Thêm giỏ': dashboardData.funnel.add_to_cart || 0,
                'Checkout': dashboardData.funnel.begin_checkout || 0,
                'Mua hàng': dashboardData.funnel.purchase || 0
            };
        }}
    };
    
    // Time aggregation functions
    function aggregateTimeData(data, mode) {
        if (!data || !Object.keys(data).length) return {};
        
        const aggregated = {};
        
        Object.entries(data).forEach(([dateStr, value]) => {
            let key;
            const date = new Date(dateStr);
            
            switch(mode) {
                case 'day':
                    key = dateStr;
                    break;
                case 'week':
                    // Get week number
                    const startOfYear = new Date(date.getFullYear(), 0, 1);
                    const weekNum = Math.ceil(((date - startOfYear) / 86400000 + startOfYear.getDay() + 1) / 7);
                    key = `${date.getFullYear()}-W${String(weekNum).padStart(2, '0')}`;
                    break;
                case 'month':
                    key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                    break;
                case 'quarter':
                    const quarter = Math.ceil((date.getMonth() + 1) / 3);
                    key = `${date.getFullYear()}-Q${quarter}`;
                    break;
                case 'year':
                    key = `${date.getFullYear()}`;
                    break;
                default:
                    key = dateStr;
            }
            
            aggregated[key] = (aggregated[key] || 0) + value;
        });
        
        // Sort keys
        const sorted = {};
        Object.keys(aggregated).sort().forEach(k => sorted[k] = aggregated[k]);
        return sorted;
    }
    
    function aggregateMonthlyToQuarter(data) {
        const aggregated = {};
        Object.entries(data).forEach(([monthStr, value]) => {
            const [year, month] = monthStr.split('-');
            const quarter = Math.ceil(parseInt(month) / 3);
            const key = `${year}-Q${quarter}`;
            aggregated[key] = (aggregated[key] || 0) + value;
        });
        return aggregated;
    }
    
    function aggregateMonthlyToYear(data) {
        const aggregated = {};
        Object.entries(data).forEach(([monthStr, value]) => {
            const year = monthStr.split('-')[0];
            aggregated[year] = (aggregated[year] || 0) + value;
        });
        return aggregated;
    }
    
    // Store widget time modes
    const widgetTimeModes = {};
    
    function formatRevenue(value) {
        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
        if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
        return value;
    }
    
    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }
    
    // ===== RENDER DASHBOARD =====
    function renderDashboard() {
        const grid = document.getElementById('dashboardGrid');
        const overlay = document.getElementById('gridOverlay');
        
        // Clear existing widgets (keep overlay)
        Array.from(grid.children).forEach(child => {
            if (child !== overlay) child.remove();
        });
        
        // Render each widget
        dashboardConfig.widgets.forEach(widget => {
            const el = createWidgetElement(widget);
            grid.appendChild(el);
        });
        
        // Initialize charts
        setTimeout(() => {
            dashboardConfig.widgets.forEach(widget => {
                if (widget.chartType !== 'kpi' && widget.chartType !== 'table') {
                    initWidgetChart(widget);
                }
            });
        }, 100);
    }
    
    function createWidgetElement(widget) {
        const el = document.createElement('div');
        el.className = 'pbi-widget';
        el.dataset.id = widget.id;
        el.style.gridColumn = `${widget.x + 1} / span ${widget.w}`;
        el.style.gridRow = `${widget.y + 1} / span ${widget.h}`;
        
        const source = dataSources[widget.dataSource];
        const icon = source ? source.icon : 'bi-graph-up';
        const hasTimeMode = source && source.hasTimeMode && widget.chartType !== 'kpi';
        const defaultTimeMode = source?.timeDefault || 'day';
        
        // Initialize time mode for this widget
        if (hasTimeMode && !widgetTimeModes[widget.id]) {
            widgetTimeModes[widget.id] = defaultTimeMode;
        }
        
        const timeModeHtml = hasTimeMode ? `
            <div class="pbi-time-selector" data-widget="${widget.id}">
                <button class="pbi-time-btn ${widgetTimeModes[widget.id] === 'day' ? 'active' : ''}" onclick="changeTimeMode('${widget.id}', 'day')" title="Theo ngày">Ngày</button>
                <button class="pbi-time-btn ${widgetTimeModes[widget.id] === 'week' ? 'active' : ''}" onclick="changeTimeMode('${widget.id}', 'week')" title="Theo tuần">Tuần</button>
                <button class="pbi-time-btn ${widgetTimeModes[widget.id] === 'month' ? 'active' : ''}" onclick="changeTimeMode('${widget.id}', 'month')" title="Theo tháng">Tháng</button>
                <button class="pbi-time-btn ${widgetTimeModes[widget.id] === 'quarter' ? 'active' : ''}" onclick="changeTimeMode('${widget.id}', 'quarter')" title="Theo quý">Quý</button>
                <button class="pbi-time-btn ${widgetTimeModes[widget.id] === 'year' ? 'active' : ''}" onclick="changeTimeMode('${widget.id}', 'year')" title="Theo năm">Năm</button>
            </div>
        ` : '';
        
        el.innerHTML = `
            <div class="pbi-widget-header">
                <span class="pbi-widget-title"><i class="bi ${icon}"></i> ${widget.title}</span>
                <div class="pbi-widget-actions">
                    ${timeModeHtml}
                    <button class="pbi-widget-btn" onclick="viewWidgetDetail('${widget.id}', '${widget.dataSource}')" title="Xem chi tiết"><i class="bi bi-eye"></i></button>
                    <div class="pbi-dropdown">
                        <button class="pbi-widget-btn" onclick="toggleExportMenu(event, '${widget.id}')" title="Xuất dữ liệu"><i class="bi bi-download"></i></button>
                        <div class="pbi-dropdown-menu" id="exportMenu_${widget.id}">
                            <button class="pbi-dropdown-item" onclick="exportWidget('${widget.id}', 'csv')">
                                <i class="bi bi-filetype-csv"></i> Xuất CSV
                            </button>
                            <button class="pbi-dropdown-item" onclick="exportWidget('${widget.id}', 'excel')">
                                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                            </button>
                            <button class="pbi-dropdown-item" onclick="exportWidget('${widget.id}', 'json')">
                                <i class="bi bi-filetype-json"></i> Xuất JSON
                            </button>
                            <div class="pbi-dropdown-divider"></div>
                            <button class="pbi-dropdown-item" onclick="printWidget('${widget.id}')">
                                <i class="bi bi-printer"></i> In
                            </button>
                        </div>
                    </div>
                    <button class="pbi-widget-btn" onclick="editWidget('${widget.id}')" title="Sửa"><i class="bi bi-pencil"></i></button>
                    <button class="pbi-widget-btn delete" onclick="deleteWidget('${widget.id}')" title="Xóa"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="pbi-widget-body">
                ${renderWidgetContent(widget)}
            </div>
            <div class="pbi-resize-handle pbi-resize-handle-e"></div>
            <div class="pbi-resize-handle pbi-resize-handle-s"></div>
            <div class="pbi-resize-handle pbi-resize-handle-se"></div>
        `;
        
        // Add drag/resize handlers
        setupWidgetInteraction(el, widget);
        
        return el;
    }
    
    function renderWidgetContent(widget) {
        const source = dataSources[widget.dataSource];
        if (!source) return '<div class="pbi-no-data"><i class="bi bi-question-circle"></i><p>Không tìm thấy dữ liệu</p></div>';
        
        if (widget.chartType === 'kpi') {
            const value = typeof source.getValue === 'function' ? source.getValue() : 0;
            return `
                <div class="pbi-kpi-body">
                    <div class="pbi-kpi-icon ${source.color || 'blue'}"><i class="bi ${source.icon}"></i></div>
                    <div class="pbi-kpi-content">
                        <div class="pbi-kpi-label">${source.label}</div>
                        <div class="pbi-kpi-value">${typeof value === 'number' ? formatNumber(value) : value}</div>
                    </div>
                </div>
            `;
        }
        
        if (widget.chartType === 'table') {
            const source = dataSources[widget.dataSource];
            const timeMode = widgetTimeModes[widget.id] || 'day';
            const data = source.hasTimeMode 
                ? (typeof source.getData === 'function' ? source.getData(timeMode) : {})
                : (typeof source.getData === 'function' ? source.getData() : {});
            if (!Object.keys(data).length) {
                return '<div class="pbi-no-data"><i class="bi bi-inbox"></i><p>Chưa có dữ liệu</p></div>';
            }
            const total = Object.values(data).reduce((a, b) => a + b, 0);
            let html = '<div class="pbi-table-wrap"><table class="pbi-table"><thead><tr><th>Mục</th><th>Giá trị</th><th>Tỷ lệ</th></tr></thead><tbody>';
            Object.entries(data).forEach(([key, value]) => {
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                html += `<tr><td><strong>${key}</strong></td><td>${formatNumber(value)}</td><td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="pbi-progress" style="width:80px;"><div class="pbi-progress-bar" style="width:${percent}%"></div></div>
                        <span>${percent}%</span>
                    </div>
                </td></tr>`;
            });
            html += '</tbody></table></div>';
            return html;
        }
        
        // Chart
        return `<div class="pbi-chart-wrap"><canvas id="chart_${widget.id}"></canvas></div>`;
    }
    
    // Store chart instances to allow updates
    const chartInstances = {};
    
    function initWidgetChart(widget) {
        const canvas = document.getElementById('chart_' + widget.id);
        if (!canvas) return;
        
        const source = dataSources[widget.dataSource];
        if (!source || typeof source.getData !== 'function') return;
        
        // Get time mode for this widget
        const timeMode = widgetTimeModes[widget.id] || 'day';
        const data = source.hasTimeMode ? source.getData(timeMode) : source.getData();
        if (!Object.keys(data).length) return;
        
        const labels = Object.keys(data);
        const values = Object.values(data);
        
        let chartType = widget.chartType;
        let indexAxis = 'x';
        
        if (chartType === 'horizontalBar') {
            chartType = 'bar';
            indexAxis = 'y';
        }
        if (chartType === 'area') {
            chartType = 'line';
        }
        
        const config = {
            type: chartType === 'radar' ? 'radar' : chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: widget.title,
                    data: values,
                    backgroundColor: chartType === 'line' || widget.chartType === 'area' 
                        ? 'rgba(236, 128, 43, 0.15)' 
                        : chartColors,
                    borderColor: chartType === 'line' || widget.chartType === 'area' ? '#EC802B' : chartColors,
                    borderWidth: chartType === 'line' || widget.chartType === 'area' ? 2 : 1,
                    fill: widget.chartType === 'area',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: indexAxis,
                plugins: {
                    legend: { 
                        display: ['pie', 'doughnut'].includes(chartType),
                        position: 'right'
                    }
                },
                scales: ['pie', 'doughnut', 'radar'].includes(chartType) ? {} : {
                    x: { display: true, grid: { display: false } },
                    y: { display: true, beginAtZero: true }
                }
            }
        };
        
        // Destroy existing chart if any
        if (chartInstances[widget.id]) {
            chartInstances[widget.id].destroy();
        }
        
        chartInstances[widget.id] = new Chart(canvas, config);
    }
    
    // Change time mode for a widget
    function changeTimeMode(widgetId, mode) {
        widgetTimeModes[widgetId] = mode;
        
        // Update active button
        const selector = document.querySelector(`.pbi-time-selector[data-widget="${widgetId}"]`);
        if (selector) {
            selector.querySelectorAll('.pbi-time-btn').forEach(btn => btn.classList.remove('active'));
            selector.querySelector(`.pbi-time-btn[onclick*="'${mode}'"]`)?.classList.add('active');
        }
        
        // Find widget config
        const widget = dashboardConfig.widgets.find(w => w.id === widgetId);
        if (!widget) return;
        
        // Refresh widget content
        const widgetEl = document.querySelector(`.pbi-widget[data-id="${widgetId}"]`);
        if (!widgetEl) return;
        
        // If table, re-render content
        if (widget.chartType === 'table') {
            const body = widgetEl.querySelector('.pbi-widget-body');
            if (body) {
                body.innerHTML = renderWidgetContent(widget);
            }
        } else if (widget.chartType !== 'kpi') {
            // Re-init chart
            initWidgetChart(widget);
        }
    }
    
    // ===== WIDGET INTERACTION (DRAG & RESIZE) =====
    function setupWidgetInteraction(el, widget) {
        let isDragging = false;
        let isResizing = false;
        let startX, startY, startRect;
        let resizeDir = '';
        
        const grid = document.getElementById('dashboardGrid');
        const gridRect = () => grid.getBoundingClientRect();
        const cellWidth = () => (gridRect().width - 20 * 2 - 12 * 5) / 6;
        const cellHeight = () => 120;
        const gap = 12;
        const padding = 20;
        
        // Drag
        el.querySelector('.pbi-widget-header').addEventListener('mousedown', function(e) {
            if (!isEditMode || e.target.closest('.pbi-widget-btn')) return;
            
            isDragging = true;
            el.classList.add('dragging');
            startX = e.clientX;
            startY = e.clientY;
            startRect = el.getBoundingClientRect();
            
            document.addEventListener('mousemove', onDrag);
            document.addEventListener('mouseup', onDragEnd);
        });
        
        function onDrag(e) {
            if (!isDragging) return;
            
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            
            // Visual feedback
            el.style.transform = `translate(${dx}px, ${dy}px)`;
            el.style.zIndex = '100';
        }
        
        function onDragEnd(e) {
            if (!isDragging) return;
            isDragging = false;
            
            el.classList.remove('dragging');
            el.style.transform = '';
            el.style.zIndex = '';
            
            // Calculate new grid position
            const rect = el.getBoundingClientRect();
            const gr = gridRect();
            
            const newX = Math.round((rect.left - gr.left - padding) / (cellWidth() + gap));
            const newY = Math.round((rect.top - gr.top - padding) / (cellHeight() + gap));
            
            const clampedX = Math.max(0, Math.min(6 - widget.w, newX));
            const clampedY = Math.max(0, newY);
            
            // Update config
            widget.x = clampedX;
            widget.y = clampedY;
            
            el.style.gridColumn = `${widget.x + 1} / span ${widget.w}`;
            el.style.gridRow = `${widget.y + 1} / span ${widget.h}`;
            
            document.removeEventListener('mousemove', onDrag);
            document.removeEventListener('mouseup', onDragEnd);
        }
        
        // Resize
        el.querySelectorAll('.pbi-resize-handle').forEach(handle => {
            handle.addEventListener('mousedown', function(e) {
                if (!isEditMode) return;
                e.stopPropagation();
                
                isResizing = true;
                el.classList.add('resizing');
                startX = e.clientX;
                startY = e.clientY;
                
                if (handle.classList.contains('pbi-resize-handle-e')) resizeDir = 'e';
                else if (handle.classList.contains('pbi-resize-handle-s')) resizeDir = 's';
                else resizeDir = 'se';
                
                document.addEventListener('mousemove', onResize);
                document.addEventListener('mouseup', onResizeEnd);
            });
        });
        
        function onResize(e) {
            if (!isResizing) return;
            
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            
            const cw = cellWidth() + gap;
            const ch = cellHeight() + gap;
            
            let newW = widget.w;
            let newH = widget.h;
            
            if (resizeDir.includes('e')) {
                newW = Math.max(1, Math.min(6 - widget.x, widget.w + Math.round(dx / cw)));
            }
            if (resizeDir.includes('s')) {
                newH = Math.max(1, widget.h + Math.round(dy / ch));
            }
            
            el.style.gridColumn = `${widget.x + 1} / span ${newW}`;
            el.style.gridRow = `${widget.y + 1} / span ${newH}`;
        }
        
        function onResizeEnd(e) {
            if (!isResizing) return;
            isResizing = false;
            el.classList.remove('resizing');
            
            // Get final size from style
            const col = el.style.gridColumn.match(/span (\d+)/);
            const row = el.style.gridRow.match(/span (\d+)/);
            
            widget.w = col ? parseInt(col[1]) : widget.w;
            widget.h = row ? parseInt(row[1]) : widget.h;
            
            document.removeEventListener('mousemove', onResize);
            document.removeEventListener('mouseup', onResizeEnd);
            
            // Re-render chart if needed
            if (widget.chartType !== 'kpi' && widget.chartType !== 'table') {
                const canvas = document.getElementById('chart_' + widget.id);
                if (canvas) {
                    const chart = Chart.getChart(canvas);
                    if (chart) chart.resize();
                }
            }
        }
    }
    
    // ===== EDIT MODE =====
    function toggleEditMode() {
        isEditMode = !isEditMode;
        const grid = document.getElementById('dashboardGrid');
        const btn = document.getElementById('editModeBtn');
        const toolbar = document.getElementById('editModeToolbar');
        const saveBtn = document.getElementById('saveDashboardBtn');
        
        grid.classList.toggle('edit-mode', isEditMode);
        toolbar.classList.toggle('show', isEditMode);
        saveBtn.classList.toggle('hidden', !isEditMode);
        
        if (isEditMode) {
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Xong';
            btn.classList.add('pbi-btn-white');
            btn.classList.remove('pbi-btn-light');
            btn.style.color = '#EC802B';
        } else {
            btn.innerHTML = '<i class="bi bi-pencil-square"></i> Chỉnh sửa';
            btn.classList.remove('pbi-btn-white');
            btn.classList.add('pbi-btn-light');
            btn.style.color = '';
        }
    }
    
    // ===== WIDGET BUILDER =====
    let selectedSource = null;
    let selectedChartType = null;
    
    function openWidgetBuilder() {
        editingWidgetId = null;
        selectedSource = null;
        selectedChartType = null;
        document.getElementById('widgetTitle').value = '';
        document.getElementById('addWidgetBtn').disabled = true;
        document.querySelectorAll('.pbi-source-item, .pbi-chart-type').forEach(el => el.classList.remove('selected'));
        
        // Reset modal header and button text for add mode
        document.querySelector('#widgetBuilderModal .pbi-modal-header h2').innerHTML = '<i class="bi bi-magic"></i> Tạo Widget mới';
        document.getElementById('addWidgetBtnText').textContent = 'Thêm Widget';
        
        document.getElementById('previewBox').innerHTML = `
            <div class="pbi-preview-placeholder">
                <i class="bi bi-bar-chart-line"></i>
                <p>Chọn nguồn dữ liệu và loại biểu đồ để xem trước</p>
            </div>
        `;
        
        document.getElementById('widgetBuilderModal').classList.add('active');
    }
    
    function closeWidgetBuilder() {
        document.getElementById('widgetBuilderModal').classList.remove('active');
        editingWidgetId = null;
        if (previewChart) {
            previewChart.destroy();
            previewChart = null;
        }
    }
    
    // Source selection
    document.querySelectorAll('.pbi-source-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.pbi-source-item').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            selectedSource = this.dataset.source;
            
            // Auto-fill title
            const source = dataSources[selectedSource];
            if (source && !document.getElementById('widgetTitle').value) {
                document.getElementById('widgetTitle').value = source.label;
            }
            
            updatePreview();
            updateAddButton();
        });
    });
    
    // Chart type selection
    document.querySelectorAll('.pbi-chart-type').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.pbi-chart-type').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            selectedChartType = this.dataset.type;
            
            updatePreview();
            updateAddButton();
        });
    });
    
    function updateAddButton() {
        document.getElementById('addWidgetBtn').disabled = !(selectedSource && selectedChartType);
    }
    
    function updatePreview() {
        if (!selectedSource || !selectedChartType) return;
        
        const box = document.getElementById('previewBox');
        const source = dataSources[selectedSource];
        
        if (previewChart) {
            previewChart.destroy();
            previewChart = null;
        }
        
        if (selectedChartType === 'kpi') {
            const value = typeof source.getValue === 'function' ? source.getValue() : 0;
            box.innerHTML = `
                <div class="pbi-kpi-body" style="width: 100%;">
                    <div class="pbi-kpi-icon ${source.color || 'blue'}"><i class="bi ${source.icon}"></i></div>
                    <div class="pbi-kpi-value">${typeof value === 'number' ? formatNumber(value) : value}</div>
                    <div class="pbi-kpi-label">${source.label}</div>
                </div>
            `;
            return;
        }
        
        if (selectedChartType === 'table') {
            const data = typeof source.getData === 'function' ? source.getData() : {};
            if (!Object.keys(data).length) {
                box.innerHTML = '<div class="pbi-no-data"><i class="bi bi-inbox"></i><p>Chưa có dữ liệu</p></div>';
                return;
            }
            const total = Object.values(data).reduce((a, b) => a + b, 0);
            let html = '<div class="pbi-table-wrap" style="width:100%;max-height:200px;"><table class="pbi-table"><thead><tr><th>Mục</th><th>Giá trị</th><th>%</th></tr></thead><tbody>';
            Object.entries(data).slice(0, 5).forEach(([key, value]) => {
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                html += `<tr><td>${key}</td><td>${formatNumber(value)}</td><td>${percent}%</td></tr>`;
            });
            html += '</tbody></table></div>';
            box.innerHTML = html;
            return;
        }
        
        // Chart preview
        const data = typeof source.getData === 'function' ? source.getData() : {};
        if (!Object.keys(data).length) {
            box.innerHTML = '<div class="pbi-no-data"><i class="bi bi-inbox"></i><p>Chưa có dữ liệu cho biểu đồ này</p></div>';
            return;
        }
        
        box.innerHTML = '<canvas id="previewCanvas" style="width:100%;height:200px;"></canvas>';
        
        const canvas = document.getElementById('previewCanvas');
        let chartType = selectedChartType;
        let indexAxis = 'x';
        
        if (chartType === 'horizontalBar') {
            chartType = 'bar';
            indexAxis = 'y';
        }
        if (chartType === 'area') {
            chartType = 'line';
        }
        
        previewChart = new Chart(canvas, {
            type: chartType === 'radar' ? 'radar' : chartType,
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: source.label,
                    data: Object.values(data),
                    backgroundColor: chartType === 'line' || selectedChartType === 'area' 
                        ? 'rgba(236, 128, 43, 0.15)' 
                        : chartColors,
                    borderColor: chartType === 'line' || selectedChartType === 'area' ? '#EC802B' : chartColors,
                    borderWidth: 2,
                    fill: selectedChartType === 'area',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: indexAxis,
                plugins: { legend: { display: ['pie', 'doughnut'].includes(chartType), position: 'right' } }
            }
        });
    }
    
    // ===== WIDGET ACTIONS =====
    function deleteWidget(id) {
        if (!confirm('Xóa widget này?')) return;
        dashboardConfig.widgets = dashboardConfig.widgets.filter(w => w.id !== id);
        renderDashboard();
    }
    
    function editWidget(id) {
        const widget = dashboardConfig.widgets.find(w => w.id === id);
        if (!widget) return;
        
        editingWidgetId = id;
        
        // Update modal header
        document.querySelector('#widgetBuilderModal .pbi-modal-header h2').innerHTML = '<i class="bi bi-pencil-square"></i> Chỉnh sửa Widget';
        document.getElementById('addWidgetBtnText').textContent = 'Lưu thay đổi';
        
        // Pre-select data source
        selectedSource = widget.dataSource;
        document.querySelectorAll('.pbi-source-item').forEach(el => {
            el.classList.toggle('selected', el.dataset.source === widget.dataSource);
        });
        
        // Pre-select chart type
        selectedChartType = widget.chartType;
        document.querySelectorAll('.pbi-chart-type').forEach(el => {
            el.classList.toggle('selected', el.dataset.type === widget.chartType);
        });
        
        // Set title
        document.getElementById('widgetTitle').value = widget.title || '';
        
        // Enable button and update preview
        document.getElementById('addWidgetBtn').disabled = false;
        updatePreview();
        
        document.getElementById('widgetBuilderModal').classList.add('active');
    }
    
    function saveWidgetFromBuilder() {
        const title = document.getElementById('widgetTitle').value || dataSources[selectedSource].label;
        
        if (editingWidgetId) {
            // Edit existing widget
            const widget = dashboardConfig.widgets.find(w => w.id === editingWidgetId);
            if (widget) {
                widget.dataSource = selectedSource;
                widget.chartType = selectedChartType;
                widget.title = title;
                
                // Adjust size based on chart type
                if (selectedChartType === 'kpi' && widget.w > 2) widget.w = 1;
                if (selectedChartType !== 'kpi' && widget.w < 2) widget.w = 2;
                if (selectedChartType === 'kpi' && widget.h > 2) widget.h = 1;
                if (selectedChartType !== 'kpi' && widget.h < 2) widget.h = 2;
            }
        } else {
            // Add new widget
            let maxY = 0;
            dashboardConfig.widgets.forEach(w => {
                maxY = Math.max(maxY, w.y + w.h);
            });
            
            const newWidget = {
                id: 'w' + Date.now(),
                dataSource: selectedSource,
                chartType: selectedChartType,
                title: title,
                x: 0,
                y: maxY,
                w: selectedChartType === 'kpi' ? 1 : 2,
                h: selectedChartType === 'kpi' ? 1 : 2
            };
            
            dashboardConfig.widgets.push(newWidget);
        }
        
        closeWidgetBuilder();
        renderDashboard();
    }
    
    // ===== SAVE/RESET =====
    function saveDashboard() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'petshop_save_dashboard_config',
                nonce: '<?php echo wp_create_nonce('petshop_dashboard'); ?>',
                config: JSON.stringify(dashboardConfig)
            },
            success: function(response) {
                if (response.success) {
                    alert('Đã lưu cấu hình dashboard!');
                    if (isEditMode) toggleEditMode();
                } else {
                    alert('Lỗi: ' + (response.data || 'Không thể lưu'));
                }
            },
            error: function() {
                alert('Lỗi kết nối server');
            }
        });
    }
    
    function resetDashboard() {
        if (!confirm('Reset dashboard về mặc định?\n\nTất cả tùy chỉnh sẽ bị xóa.')) return;
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'petshop_reset_dashboard_config',
                nonce: '<?php echo wp_create_nonce('petshop_dashboard'); ?>'
            },
            success: function() {
                location.reload();
            }
        });
    }
    
    // ===== VIEW, EXPORT, PRINT =====
    
    // Data source to detail page mapping - uses correct WordPress admin URLs
    const detailPages = {
        // Traffic data - dẫn đến trang Báo cáo CRM
        sessions: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        pageviews: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        visitors: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        pages_per_session: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        sessions_by_day: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        traffic_source: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        devices: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        browsers: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        hours: { url: 'admin.php?page=petshop-crm-reports&tab=traffic', label: 'Báo cáo Traffic' },
        
        // Orders - dẫn đến danh sách đơn hàng
        orders: { url: 'edit.php?post_type=petshop_order', label: 'Danh sách đơn hàng' },
        orders_by_day: { url: 'admin.php?page=petshop-crm-reports&tab=orders', label: 'Báo cáo đơn hàng' },
        orders_by_month: { url: 'admin.php?page=petshop-crm-reports&tab=orders', label: 'Báo cáo đơn hàng' },
        order_status: { url: 'edit.php?post_type=petshop_order', label: 'Danh sách đơn hàng' },
        
        // Revenue - dẫn đến báo cáo doanh thu
        revenue: { url: 'admin.php?page=petshop-crm-reports&tab=revenue', label: 'Báo cáo doanh thu' },
        revenue_by_day: { url: 'admin.php?page=petshop-crm-reports&tab=revenue', label: 'Báo cáo doanh thu' },
        
        // Users - dẫn đến quản lý khách hàng CRM
        users: { url: 'admin.php?page=petshop-crm-customers', label: 'Quản lý khách hàng' },
        
        // Products - dẫn đến danh sách sản phẩm
        products: { url: 'edit.php?post_type=product', label: 'Danh sách sản phẩm' },
        
        // Funnel - dẫn đến trang báo cáo conversion
        funnel: { url: 'admin.php?page=petshop-crm-reports&tab=funnel', label: 'Phân tích Funnel' }
    };
    
    function viewWidgetDetail(widgetId, dataSource) {
        const detail = detailPages[dataSource];
        if (detail) {
            const url = `<?php echo admin_url(); ?>${detail.url}`;
            window.open(url, '_blank');
        } else {
            alert('Không có trang chi tiết cho nguồn dữ liệu này');
        }
    }
    
    function toggleExportMenu(event, widgetId) {
        event.stopPropagation();
        
        // Close all other menus
        document.querySelectorAll('.pbi-dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
        
        const menu = document.getElementById('exportMenu_' + widgetId);
        menu.classList.toggle('show');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.pbi-dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    });
    
    function getWidgetData(widgetId) {
        const widget = dashboardConfig.widgets.find(w => w.id === widgetId);
        if (!widget) return null;
        
        const source = dataSources[widget.dataSource];
        if (!source) return null;
        
        let data = {};
        if (typeof source.getValue === 'function') {
            data = { [widget.title]: source.getValue() };
        } else if (typeof source.getData === 'function') {
            const timeMode = widgetTimeModes[widgetId] || 'day';
            data = source.hasTimeMode ? source.getData(timeMode) : source.getData();
        }
        
        return { widget, source, data };
    }
    
    function exportWidget(widgetId, format) {
        const result = getWidgetData(widgetId);
        if (!result) return alert('Không thể lấy dữ liệu');
        
        const { widget, data } = result;
        let content, filename, mimeType;
        
        switch(format) {
            case 'csv':
                content = 'Mục,Giá trị\n';
                Object.entries(data).forEach(([key, value]) => {
                    content += `"${key}","${value}"\n`;
                });
                filename = `${widget.title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.csv`;
                mimeType = 'text/csv;charset=utf-8;';
                break;
                
            case 'excel':
                // Create simple XML Excel format
                content = '<?xml version="1.0"?>\n<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">\n<Worksheet ss:Name="Data"><Table>\n';
                content += '<Row><Cell><Data ss:Type="String">Mục</Data></Cell><Cell><Data ss:Type="String">Giá trị</Data></Cell></Row>\n';
                Object.entries(data).forEach(([key, value]) => {
                    content += `<Row><Cell><Data ss:Type="String">${key}</Data></Cell><Cell><Data ss:Type="Number">${value}</Data></Cell></Row>\n`;
                });
                content += '</Table></Worksheet></Workbook>';
                filename = `${widget.title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xls`;
                mimeType = 'application/vnd.ms-excel';
                break;
                
            case 'json':
                content = JSON.stringify({
                    title: widget.title,
                    dataSource: widget.dataSource,
                    exportDate: new Date().toISOString(),
                    data: data
                }, null, 2);
                filename = `${widget.title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.json`;
                mimeType = 'application/json';
                break;
        }
        
        // Download file
        const blob = new Blob([content], { type: mimeType });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Close dropdown
        document.querySelectorAll('.pbi-dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
    
    function printWidget(widgetId) {
        const widgetEl = document.querySelector(`.pbi-widget[data-id="${widgetId}"]`);
        if (!widgetEl) return;
        
        const result = getWidgetData(widgetId);
        if (!result) return;
        
        const { widget, data } = result;
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${widget.title} - In báo cáo</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 40px; }
                    h1 { color: #EC802B; margin-bottom: 10px; }
                    .meta { color: #666; margin-bottom: 30px; font-size: 14px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background: #f5f5f5; font-weight: 600; }
                    .footer { margin-top: 40px; color: #999; font-size: 12px; text-align: center; }
                    @media print {
                        body { padding: 20px; }
                    }
                </style>
            </head>
            <body>
                <h1>${widget.title}</h1>
                <p class="meta">Nguồn: ${result.source.label} | Xuất lúc: ${new Date().toLocaleString('vi-VN')}</p>
                <table>
                    <thead><tr><th>Mục</th><th>Giá trị</th></tr></thead>
                    <tbody>
                        ${Object.entries(data).map(([key, value]) => 
                            `<tr><td>${key}</td><td>${typeof value === 'number' ? formatNumber(value) : value}</td></tr>`
                        ).join('')}
                    </tbody>
                </table>
                <p class="footer">PetShop CRM Dashboard - ${window.location.hostname}</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
        }, 250);
        
        // Close dropdown
        document.querySelectorAll('.pbi-dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
    
    // ===== POWER BI =====
    function togglePBIFullscreen() {
        const section = document.getElementById('powerbiSection');
        const body = document.getElementById('pbiEmbedBody');
        
        if (section.classList.contains('fullscreen')) {
            section.classList.remove('fullscreen');
            body.style.height = '550px';
            document.body.style.overflow = '';
        } else {
            section.classList.add('fullscreen');
            body.style.height = 'calc(100vh - 80px)';
            document.body.style.overflow = 'hidden';
        }
    }
    
    // ===== INIT =====
    document.addEventListener('DOMContentLoaded', function() {
        renderDashboard();
    });
    </script>
    <?php
}

// ===== REPORTS PAGE =====
function petshop_crm_reports_page() {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'traffic';
    
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    
    // Get data
    $order_stats = petshop_get_order_stats($start_date, $end_date);
    $traffic_stats = petshop_get_traffic_stats($start_date, $end_date);
    $event_stats = petshop_get_event_stats($start_date, $end_date);
    
    $tabs = array(
        'traffic' => array('icon' => 'bi-activity', 'label' => 'Traffic'),
        'orders' => array('icon' => 'bi-cart3', 'label' => 'Đơn hàng'),
        'revenue' => array('icon' => 'bi-currency-dollar', 'label' => 'Doanh thu'),
        'funnel' => array('icon' => 'bi-funnel', 'label' => 'Funnel')
    );
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
    .pbi-reports {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #f3f4f6;
        margin: -10px -20px;
        min-height: 100vh;
    }
    
    .pbi-reports-header {
        background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%);
        color: white;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 32px;
        z-index: 100;
        box-shadow: 0 4px 15px rgba(236, 128, 43, 0.3);
    }
    
    .pbi-reports-header h1 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pbi-reports-filter {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .pbi-reports-filter label {
        font-size: 12px;
        color: rgba(255,255,255,0.9);
    }
    
    .pbi-reports-filter input[type="date"] {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.15);
        color: white;
        font-size: 12px;
    }
    
    .pbi-reports-filter button {
        padding: 6px 14px;
        border-radius: 6px;
        border: none;
        background: rgba(255,255,255,0.2);
        color: white;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
    }
    
    .pbi-reports-filter button:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .pbi-reports-tabs {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 0 24px;
        display: flex;
        gap: 4px;
    }
    
    .pbi-tab {
        padding: 14px 20px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s;
    }
    
    .pbi-tab:hover {
        color: #EC802B;
    }
    
    .pbi-tab.active {
        color: #EC802B;
        border-bottom-color: #EC802B;
    }
    
    .pbi-reports-content {
        padding: 24px;
    }
    
    .pbi-report-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .pbi-report-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .pbi-report-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .pbi-report-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    
    .pbi-report-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pbi-report-card-title i {
        color: #EC802B;
    }
    
    .pbi-stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .pbi-stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .pbi-stat-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 20px;
        margin-top: 8px;
    }
    
    .pbi-stat-change.up { background: #d1fae5; color: #059669; }
    .pbi-stat-change.down { background: #fee2e2; color: #dc2626; }
    
    .pbi-chart-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    
    .pbi-chart-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 16px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pbi-chart-card h3 i {
        color: #EC802B;
    }
    
    .pbi-chart-container {
        height: 300px;
        position: relative;
    }
    
    .pbi-table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .pbi-table-card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pbi-table-card h3 i {
        color: #EC802B;
    }
    
    .pbi-data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .pbi-data-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .pbi-data-table td {
        padding: 12px 16px;
        font-size: 13px;
        color: #1f2937;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .pbi-data-table tr:hover {
        background: #fafafa;
    }
    
    .pbi-progress-bar {
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .pbi-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #EC802B 0%, #66BCB4 100%);
        border-radius: 4px;
    }
    
    .pbi-funnel-step {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .pbi-funnel-step:last-child {
        border-bottom: none;
    }
    
    .pbi-funnel-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-right: 16px;
    }
    
    .pbi-funnel-icon.step1 { background: #dbeafe; color: #2563eb; }
    .pbi-funnel-icon.step2 { background: #fef3c7; color: #d97706; }
    .pbi-funnel-icon.step3 { background: #d1fae5; color: #059669; }
    .pbi-funnel-icon.step4 { background: #fce7f3; color: #db2777; }
    
    .pbi-funnel-info {
        flex: 1;
    }
    
    .pbi-funnel-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }
    
    .pbi-funnel-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }
    
    .pbi-funnel-value {
        text-align: right;
    }
    
    .pbi-funnel-number {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .pbi-funnel-rate {
        font-size: 12px;
        color: #6b7280;
    }
    
    .pbi-export-btn {
        padding: 8px 16px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #374151;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .pbi-export-btn:hover {
        border-color: #EC802B;
        color: #EC802B;
    }
    
    .pbi-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    
    @media (max-width: 1200px) {
        .pbi-report-grid { grid-template-columns: repeat(2, 1fr); }
        .pbi-row { grid-template-columns: 1fr; }
    }
    </style>
    
    <div class="pbi-reports">
        <!-- Header -->
        <div class="pbi-reports-header">
            <h1><i class="bi bi-file-earmark-bar-graph"></i> Báo cáo CRM</h1>
            <form method="get" class="pbi-reports-filter">
                <input type="hidden" name="page" value="petshop-crm-reports">
                <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>">
                <label>Từ:</label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label>Đến:</label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <button type="submit"><i class="bi bi-funnel"></i> Lọc</button>
            </form>
        </div>
        
        <!-- Tabs -->
        <div class="pbi-reports-tabs">
            <?php foreach ($tabs as $tab_id => $tab): ?>
            <a href="<?php echo admin_url('admin.php?page=petshop-crm-reports&tab=' . $tab_id . '&start_date=' . $start_date . '&end_date=' . $end_date); ?>" 
               class="pbi-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
                <i class="bi <?php echo $tab['icon']; ?>"></i>
                <?php echo $tab['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Content -->
        <div class="pbi-reports-content">
            <?php
            switch ($current_tab) {
                case 'traffic':
                    petshop_render_traffic_report($traffic_stats, $start_date, $end_date);
                    break;
                case 'orders':
                    petshop_render_orders_report($order_stats, $start_date, $end_date);
                    break;
                case 'revenue':
                    petshop_render_revenue_report($order_stats, $start_date, $end_date);
                    break;
                case 'funnel':
                    petshop_render_funnel_report($event_stats, $start_date, $end_date);
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

// Traffic Report
function petshop_render_traffic_report($stats, $start_date, $end_date) {
    ?>
    <!-- KPI Cards -->
    <div class="pbi-report-grid">
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-activity"></i> Sessions</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['total_sessions']); ?></div>
            <div class="pbi-stat-label">Tổng phiên truy cập</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-eye"></i> Pageviews</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['total_pageviews']); ?></div>
            <div class="pbi-stat-label">Tổng lượt xem trang</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-people"></i> Visitors</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['unique_visitors']); ?></div>
            <div class="pbi-stat-label">Khách truy cập duy nhất</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-layers"></i> Pages/Session</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['avg_pages_per_session'], 2); ?></div>
            <div class="pbi-stat-label">Trang/phiên trung bình</div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="pbi-row">
        <div class="pbi-chart-card">
            <h3><i class="bi bi-graph-up"></i> Sessions theo ngày</h3>
            <div class="pbi-chart-container">
                <canvas id="sessionsChart"></canvas>
            </div>
        </div>
        <div class="pbi-chart-card">
            <h3><i class="bi bi-clock"></i> Traffic theo giờ</h3>
            <div class="pbi-chart-container">
                <canvas id="hoursChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tables Row -->
    <div class="pbi-row">
        <div class="pbi-table-card">
            <h3><i class="bi bi-diagram-3"></i> Nguồn Traffic</h3>
            <table class="pbi-data-table">
                <thead>
                    <tr><th>Nguồn</th><th>Sessions</th><th>Tỷ lệ</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $total = array_sum($stats['sessions_by_source']);
                    foreach ($stats['sessions_by_source'] as $source => $count): 
                        $percent = $total > 0 ? ($count / $total * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($source); ?></strong></td>
                        <td><?php echo number_format($count); ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="pbi-progress-bar" style="width:100px;">
                                    <div class="pbi-progress-fill" style="width:<?php echo $percent; ?>%"></div>
                                </div>
                                <span><?php echo number_format($percent, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pbi-table-card">
            <h3><i class="bi bi-phone"></i> Thiết bị & Trình duyệt</h3>
            <table class="pbi-data-table">
                <thead>
                    <tr><th>Thiết bị</th><th>Sessions</th><th>Trình duyệt</th><th>Sessions</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $devices = array_slice($stats['sessions_by_device'], 0, 5, true);
                    $browsers = array_slice($stats['sessions_by_browser'], 0, 5, true);
                    $device_keys = array_keys($devices);
                    $browser_keys = array_keys($browsers);
                    $max = max(count($device_keys), count($browser_keys));
                    for ($i = 0; $i < $max; $i++):
                    ?>
                    <tr>
                        <td><?php echo isset($device_keys[$i]) ? esc_html($device_keys[$i]) : '-'; ?></td>
                        <td><?php echo isset($device_keys[$i]) ? number_format($devices[$device_keys[$i]]) : '-'; ?></td>
                        <td><?php echo isset($browser_keys[$i]) ? esc_html($browser_keys[$i]) : '-'; ?></td>
                        <td><?php echo isset($browser_keys[$i]) ? number_format($browsers[$browser_keys[$i]]) : '-'; ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sessions by Day Chart
        const sessionsData = <?php echo json_encode($stats['sessions_by_day']); ?>;
        new Chart(document.getElementById('sessionsChart'), {
            type: 'line',
            data: {
                labels: Object.keys(sessionsData),
                datasets: [{
                    label: 'Sessions',
                    data: Object.values(sessionsData),
                    borderColor: '#EC802B',
                    backgroundColor: 'rgba(236, 128, 43, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Hours Chart
        const hoursData = <?php echo json_encode($stats['sessions_by_hour']); ?>;
        new Chart(document.getElementById('hoursChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(hoursData).map(h => h + ':00'),
                datasets: [{
                    label: 'Sessions',
                    data: Object.values(hoursData),
                    backgroundColor: '#66BCB4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>
    <?php
}

// Orders Report
function petshop_render_orders_report($stats, $start_date, $end_date) {
    ?>
    <!-- KPI Cards -->
    <div class="pbi-report-grid">
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-cart3"></i> Tổng đơn</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['total_orders']); ?></div>
            <div class="pbi-stat-label">Đơn hàng trong kỳ</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-check-circle"></i> Đã xác nhận</span>
            </div>
            <div class="pbi-stat-value" style="color:#059669;"><?php echo number_format($stats['confirmed_orders']); ?></div>
            <div class="pbi-stat-label">Đơn thành công</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-hourglass-split"></i> Chờ xử lý</span>
            </div>
            <div class="pbi-stat-value" style="color:#d97706;"><?php echo number_format($stats['pending_orders']); ?></div>
            <div class="pbi-stat-label">Đang chờ</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-x-circle"></i> Đã hủy</span>
            </div>
            <div class="pbi-stat-value" style="color:#dc2626;"><?php echo number_format($stats['cancelled_orders']); ?></div>
            <div class="pbi-stat-label">Đơn bị hủy</div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="pbi-row">
        <div class="pbi-chart-card">
            <h3><i class="bi bi-calendar3"></i> Đơn hàng theo ngày</h3>
            <div class="pbi-chart-container">
                <canvas id="ordersByDayChart"></canvas>
            </div>
        </div>
        <div class="pbi-chart-card">
            <h3><i class="bi bi-bar-chart"></i> Đơn hàng theo tháng</h3>
            <div class="pbi-chart-container">
                <canvas id="ordersByMonthChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Status Table -->
    <div class="pbi-table-card">
        <h3><i class="bi bi-clipboard-data"></i> Phân bổ trạng thái đơn hàng</h3>
        <table class="pbi-data-table">
            <thead>
                <tr><th>Trạng thái</th><th>Số lượng</th><th>Tổng giá trị</th><th>Tỷ lệ</th></tr>
            </thead>
            <tbody>
                <?php 
                $status_labels = array(
                    'pending' => 'Chờ xử lý',
                    'confirmed' => 'Đã xác nhận',
                    'processing' => 'Đang xử lý',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn thành',
                    'cancelled' => 'Đã hủy'
                );
                foreach ($stats['orders_by_status'] as $status => $data): 
                    $percent = $stats['total_orders'] > 0 ? ($data['count'] / $stats['total_orders'] * 100) : 0;
                ?>
                <tr>
                    <td><strong><?php echo isset($status_labels[$status]) ? $status_labels[$status] : $status; ?></strong></td>
                    <td><?php echo number_format($data['count']); ?></td>
                    <td><?php echo number_format($data['total']); ?>đ</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="pbi-progress-bar" style="width:100px;">
                                <div class="pbi-progress-fill" style="width:<?php echo $percent; ?>%"></div>
                            </div>
                            <span><?php echo number_format($percent, 1); ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Orders -->
    <div class="pbi-table-card">
        <h3><i class="bi bi-receipt"></i> Đơn hàng gần đây</h3>
        <table class="pbi-data-table">
            <thead>
                <tr><th>ID</th><th>Khách hàng</th><th>Ngày</th><th>Giá trị</th><th>Trạng thái</th><th>Hành động</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats['recent_orders'] as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo esc_html($order['customer'] ?: 'Khách vãng lai'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                    <td><?php echo number_format($order['total']); ?>đ</td>
                    <td>
                        <?php 
                        $status_colors = array(
                            'pending' => '#d97706',
                            'confirmed' => '#2563eb',
                            'processing' => '#7c3aed',
                            'shipping' => '#0891b2',
                            'completed' => '#059669',
                            'cancelled' => '#dc2626'
                        );
                        $color = isset($status_colors[$order['status']]) ? $status_colors[$order['status']] : '#6b7280';
                        $label = isset($status_labels[$order['status']]) ? $status_labels[$order['status']] : $order['status'];
                        ?>
                        <span style="color:<?php echo $color; ?>;font-weight:500;"><?php echo $label; ?></span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('post.php?post=' . $order['id'] . '&action=edit'); ?>" 
                           style="color:#EC802B;text-decoration:none;">Xem</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Orders by Day
        const ordersByDay = <?php 
            $daily = array();
            foreach ($stats['orders_by_day'] as $day => $data) {
                $daily[$day] = $data['count'];
            }
            echo json_encode(array_slice($daily, -30, null, true)); 
        ?>;
        new Chart(document.getElementById('ordersByDayChart'), {
            type: 'line',
            data: {
                labels: Object.keys(ordersByDay),
                datasets: [{
                    label: 'Đơn hàng',
                    data: Object.values(ordersByDay),
                    borderColor: '#EC802B',
                    backgroundColor: 'rgba(236, 128, 43, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Orders by Month
        const ordersByMonth = <?php 
            $monthly = array();
            foreach ($stats['orders_by_month'] as $month => $data) {
                $monthly[$month] = $data['count'];
            }
            echo json_encode($monthly); 
        ?>;
        new Chart(document.getElementById('ordersByMonthChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(ordersByMonth),
                datasets: [{
                    label: 'Đơn hàng',
                    data: Object.values(ordersByMonth),
                    backgroundColor: '#66BCB4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>
    <?php
}

// Revenue Report
function petshop_render_revenue_report($stats, $start_date, $end_date) {
    $avg_order = $stats['total_orders'] > 0 ? $stats['gross_revenue'] / $stats['confirmed_orders'] : 0;
    ?>
    <!-- KPI Cards -->
    <div class="pbi-report-grid pbi-report-grid-3">
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-currency-dollar"></i> Tổng doanh thu</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['gross_revenue']); ?>đ</div>
            <div class="pbi-stat-label">Doanh thu trong kỳ</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-receipt"></i> Giá trị TB/đơn</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($avg_order); ?>đ</div>
            <div class="pbi-stat-label">Trung bình mỗi đơn</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-check-circle"></i> Đơn thành công</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($stats['confirmed_orders']); ?></div>
            <div class="pbi-stat-label">Đơn có doanh thu</div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="pbi-chart-card">
        <h3><i class="bi bi-graph-up-arrow"></i> Doanh thu theo ngày</h3>
        <div class="pbi-chart-container" style="height:350px;">
            <canvas id="revenueByDayChart"></canvas>
        </div>
    </div>
    
    <div class="pbi-row">
        <div class="pbi-chart-card">
            <h3><i class="bi bi-bar-chart"></i> Doanh thu theo tháng</h3>
            <div class="pbi-chart-container">
                <canvas id="revenueByMonthChart"></canvas>
            </div>
        </div>
        <div class="pbi-table-card" style="margin-bottom:0;">
            <h3><i class="bi bi-table"></i> Chi tiết doanh thu theo tháng</h3>
            <table class="pbi-data-table">
                <thead>
                    <tr><th>Tháng</th><th>Số đơn</th><th>Doanh thu</th><th>TB/đơn</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['orders_by_month'] as $month => $data): 
                        $avg = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo $month; ?></strong></td>
                        <td><?php echo number_format($data['count']); ?></td>
                        <td><?php echo number_format($data['total']); ?>đ</td>
                        <td><?php echo number_format($avg); ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue by Day
        const revenueByDay = <?php 
            $daily = array();
            foreach ($stats['orders_by_day'] as $day => $data) {
                $daily[$day] = $data['total'];
            }
            echo json_encode(array_slice($daily, -30, null, true)); 
        ?>;
        new Chart(document.getElementById('revenueByDayChart'), {
            type: 'line',
            data: {
                labels: Object.keys(revenueByDay),
                datasets: [{
                    label: 'Doanh thu',
                    data: Object.values(revenueByDay),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Revenue by Month
        const revenueByMonth = <?php 
            $monthly = array();
            foreach ($stats['orders_by_month'] as $month => $data) {
                $monthly[$month] = $data['total'];
            }
            echo json_encode($monthly); 
        ?>;
        new Chart(document.getElementById('revenueByMonthChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(revenueByMonth),
                datasets: [{
                    label: 'Doanh thu',
                    data: Object.values(revenueByMonth),
                    backgroundColor: '#EC802B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>
    <?php
}

// Funnel Report
function petshop_render_funnel_report($stats, $start_date, $end_date) {
    $view = $stats['view_product'] ?? 0;
    $cart = $stats['add_to_cart'] ?? 0;
    $checkout = $stats['begin_checkout'] ?? 0;
    $purchase = $stats['purchase'] ?? 0;
    
    $rate_cart = $view > 0 ? ($cart / $view * 100) : 0;
    $rate_checkout = $cart > 0 ? ($checkout / $cart * 100) : 0;
    $rate_purchase = $checkout > 0 ? ($purchase / $checkout * 100) : 0;
    $rate_overall = $view > 0 ? ($purchase / $view * 100) : 0;
    ?>
    
    <!-- KPI Cards -->
    <div class="pbi-report-grid">
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-eye"></i> Xem sản phẩm</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($view); ?></div>
            <div class="pbi-stat-label">Lượt xem SP</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-cart-plus"></i> Thêm giỏ</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($cart); ?></div>
            <div class="pbi-stat-label"><?php echo number_format($rate_cart, 1); ?>% từ xem SP</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-credit-card"></i> Checkout</span>
            </div>
            <div class="pbi-stat-value"><?php echo number_format($checkout); ?></div>
            <div class="pbi-stat-label"><?php echo number_format($rate_checkout, 1); ?>% từ giỏ hàng</div>
        </div>
        <div class="pbi-report-card">
            <div class="pbi-report-card-header">
                <span class="pbi-report-card-title"><i class="bi bi-bag-check"></i> Mua hàng</span>
            </div>
            <div class="pbi-stat-value" style="color:#059669;"><?php echo number_format($purchase); ?></div>
            <div class="pbi-stat-label"><?php echo number_format($rate_purchase, 1); ?>% từ checkout</div>
        </div>
    </div>
    
    <!-- Funnel Visual -->
    <div class="pbi-row">
        <div class="pbi-chart-card">
            <h3><i class="bi bi-funnel"></i> Biểu đồ Funnel</h3>
            <div class="pbi-chart-container" style="height:300px;">
                <canvas id="funnelChart"></canvas>
            </div>
        </div>
        
        <div class="pbi-table-card" style="margin-bottom:0;">
            <h3><i class="bi bi-list-check"></i> Chi tiết các bước</h3>
            
            <div class="pbi-funnel-step">
                <div class="pbi-funnel-icon step1"><i class="bi bi-eye"></i></div>
                <div class="pbi-funnel-info">
                    <div class="pbi-funnel-title">Xem sản phẩm</div>
                    <div class="pbi-funnel-subtitle">Khách xem chi tiết sản phẩm</div>
                </div>
                <div class="pbi-funnel-value">
                    <div class="pbi-funnel-number"><?php echo number_format($view); ?></div>
                    <div class="pbi-funnel-rate">100%</div>
                </div>
            </div>
            
            <div class="pbi-funnel-step">
                <div class="pbi-funnel-icon step2"><i class="bi bi-cart-plus"></i></div>
                <div class="pbi-funnel-info">
                    <div class="pbi-funnel-title">Thêm vào giỏ</div>
                    <div class="pbi-funnel-subtitle">Khách thêm SP vào giỏ hàng</div>
                </div>
                <div class="pbi-funnel-value">
                    <div class="pbi-funnel-number"><?php echo number_format($cart); ?></div>
                    <div class="pbi-funnel-rate"><?php echo number_format($rate_cart, 1); ?>%</div>
                </div>
            </div>
            
            <div class="pbi-funnel-step">
                <div class="pbi-funnel-icon step3"><i class="bi bi-credit-card"></i></div>
                <div class="pbi-funnel-info">
                    <div class="pbi-funnel-title">Bắt đầu Checkout</div>
                    <div class="pbi-funnel-subtitle">Khách đi đến trang thanh toán</div>
                </div>
                <div class="pbi-funnel-value">
                    <div class="pbi-funnel-number"><?php echo number_format($checkout); ?></div>
                    <div class="pbi-funnel-rate"><?php echo number_format($view > 0 ? $checkout / $view * 100 : 0, 1); ?>%</div>
                </div>
            </div>
            
            <div class="pbi-funnel-step">
                <div class="pbi-funnel-icon step4"><i class="bi bi-bag-check"></i></div>
                <div class="pbi-funnel-info">
                    <div class="pbi-funnel-title">Hoàn tất mua hàng</div>
                    <div class="pbi-funnel-subtitle">Khách đặt hàng thành công</div>
                </div>
                <div class="pbi-funnel-value">
                    <div class="pbi-funnel-number"><?php echo number_format($purchase); ?></div>
                    <div class="pbi-funnel-rate"><?php echo number_format($rate_overall, 1); ?>%</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conversion Summary -->
    <div class="pbi-chart-card" style="margin-top:24px;">
        <h3><i class="bi bi-percent"></i> Tỷ lệ chuyển đổi giữa các bước</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:10px 0;">
            <div style="text-align:center;padding:20px;background:#f9fafb;border-radius:10px;">
                <div style="font-size:28px;font-weight:700;color:#2563eb;"><?php echo number_format($rate_cart, 1); ?>%</div>
                <div style="font-size:13px;color:#6b7280;margin-top:6px;">Xem → Thêm giỏ</div>
            </div>
            <div style="text-align:center;padding:20px;background:#f9fafb;border-radius:10px;">
                <div style="font-size:28px;font-weight:700;color:#d97706;"><?php echo number_format($rate_checkout, 1); ?>%</div>
                <div style="font-size:13px;color:#6b7280;margin-top:6px;">Thêm giỏ → Checkout</div>
            </div>
            <div style="text-align:center;padding:20px;background:#f9fafb;border-radius:10px;">
                <div style="font-size:28px;font-weight:700;color:#059669;"><?php echo number_format($rate_purchase, 1); ?>%</div>
                <div style="font-size:13px;color:#6b7280;margin-top:6px;">Checkout → Mua hàng</div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('funnelChart'), {
            type: 'bar',
            data: {
                labels: ['Xem SP', 'Thêm giỏ', 'Checkout', 'Mua hàng'],
                datasets: [{
                    label: 'Số lượng',
                    data: [<?php echo $view; ?>, <?php echo $cart; ?>, <?php echo $checkout; ?>, <?php echo $purchase; ?>],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ec4899']
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true },
                    y: { grid: { display: false } }
                }
            }
        });
    });
    </script>
    <?php
}
