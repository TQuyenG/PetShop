<?php
/**
 * CRM Dashboard - PetShop Theme
 * Dashboard phân tích dữ liệu kiểu Power BI
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===== HÀM LẤY THỐNG KÊ =====

/**
 * Lấy thống kê đơn hàng theo khoảng thời gian
 */
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
        'returned_orders' => 0,
        'pending_orders' => 0,
        'gross_revenue' => 0,
        'net_revenue' => 0,
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
        
        // Recent orders (top 10)
        if (count($stats['recent_orders']) < 10) {
            $stats['recent_orders'][] = array(
                'id' => $order->ID,
                'date' => $order->post_date,
                'customer' => $order->customer_name,
                'total' => $total,
                'status' => $status
            );
        }
        
        // By status
        if (!isset($stats['orders_by_status'][$status])) {
            $stats['orders_by_status'][$status] = array('count' => 0, 'total' => 0);
        }
        $stats['orders_by_status'][$status]['count']++;
        $stats['orders_by_status'][$status]['total'] += $total;
        
        // Count by type
        if (in_array($status, array('completed', 'processing', 'confirmed', 'shipping'))) {
            $stats['confirmed_orders']++;
            $stats['gross_revenue'] += $total;
            $stats['net_revenue'] += $total;
            $revenue_orders++;
        } elseif ($status === 'cancelled') {
            $stats['cancelled_orders']++;
        } elseif ($status === 'refunded') {
            $stats['returned_orders']++;
        } elseif ($status === 'pending') {
            $stats['pending_orders']++;
        }
        
        // By month
        $month = date('Y-m', strtotime($order->post_date));
        if (!isset($stats['orders_by_month'][$month])) {
            $stats['orders_by_month'][$month] = array('count' => 0, 'total' => 0);
        }
        $stats['orders_by_month'][$month]['count']++;
        $stats['orders_by_month'][$month]['total'] += $total;
        
        // By day
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
    
    return $stats;
}

/**
 * Lấy thống kê traffic
 */
function petshop_get_traffic_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $sessions_table = $wpdb->prefix . 'petshop_sessions';
    $pageviews_table = $wpdb->prefix . 'petshop_pageviews';
    
    $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table;
    $pageviews_exists = $wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table;
    
    $stats = array(
        'total_sessions' => 0,
        'unique_visitors' => 0,
        'total_pageviews' => 0,
        'avg_pages_per_session' => 0,
        'sessions_by_day' => array(),
        'sessions_by_hour' => array(),
        'traffic_sources' => array(),
        'devices' => array(),
        'browsers' => array(),
        'top_pages' => array()
    );
    
    if (!$sessions_exists) {
        return $stats;
    }
    
    $where = "WHERE 1=1";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND DATE(created_at) >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND DATE(created_at) <= %s", $end_date);
    }
    
    // Basic counts
    $stats['total_sessions'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $sessions_table $where");
    $stats['unique_visitors'] = (int)$wpdb->get_var("SELECT COUNT(DISTINCT visitor_id) FROM $sessions_table $where");
    
    if ($pageviews_exists) {
        $stats['total_pageviews'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $pageviews_table $where");
        if ($stats['total_sessions'] > 0) {
            $stats['avg_pages_per_session'] = round($stats['total_pageviews'] / $stats['total_sessions'], 2);
        }
        
        // Top pages
        $top_pages = $wpdb->get_results("
            SELECT page_url, COUNT(*) as views
            FROM $pageviews_table
            $where
            GROUP BY page_url
            ORDER BY views DESC
            LIMIT 10
        ");
        foreach ($top_pages as $page) {
            $stats['top_pages'][$page->page_url] = (int)$page->views;
        }
    }
    
    // Sessions by day
    $by_day = $wpdb->get_results("
        SELECT DATE(created_at) as day, COUNT(*) as count
        FROM $sessions_table
        $where
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    foreach ($by_day as $row) {
        $stats['sessions_by_day'][$row->day] = (int)$row->count;
    }
    
    // Sessions by hour
    $by_hour = $wpdb->get_results("
        SELECT HOUR(created_at) as hour, COUNT(*) as count
        FROM $sessions_table
        $where
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    for ($h = 0; $h < 24; $h++) {
        $stats['sessions_by_hour'][$h] = 0;
    }
    foreach ($by_hour as $row) {
        $stats['sessions_by_hour'][(int)$row->hour] = (int)$row->count;
    }
    
    // Traffic sources
    $sources = $wpdb->get_results("
        SELECT source, COUNT(*) as count
        FROM $sessions_table
        $where
        GROUP BY source
        ORDER BY count DESC
    ");
    foreach ($sources as $row) {
        $stats['traffic_sources'][$row->source ?: 'Direct'] = (int)$row->count;
    }
    
    // Devices
    $devices = $wpdb->get_results("
        SELECT device, COUNT(*) as count
        FROM $sessions_table
        $where
        GROUP BY device
        ORDER BY count DESC
    ");
    foreach ($devices as $row) {
        $stats['devices'][$row->device ?: 'Unknown'] = (int)$row->count;
    }
    
    // Browsers
    $browsers = $wpdb->get_results("
        SELECT browser, COUNT(*) as count
        FROM $sessions_table
        $where
        GROUP BY browser
        ORDER BY count DESC
    ");
    foreach ($browsers as $row) {
        $stats['browsers'][$row->browser ?: 'Unknown'] = (int)$row->count;
    }
    
    return $stats;
}

/**
 * Lấy thống kê người dùng
 */
function petshop_get_user_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $where = "";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND user_registered >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND user_registered <= %s", $end_date . ' 23:59:59');
    }
    
    $stats = array(
        'total_users' => 0,
        'new_users' => 0,
        'users_by_day' => array(),
        'customers' => array(
            'gold' => array('count' => 0, 'total_spent' => 0),
            'silver' => array('count' => 0, 'total_spent' => 0),
            'bronze' => array('count' => 0, 'total_spent' => 0)
        ),
        'top_customers' => array()
    );
    
    $stats['total_users'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE 1=1 $where");
    
    // New users in last 30 days
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $stats['new_users'] = (int)$wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->users} 
        WHERE user_registered >= %s
    ", $thirty_days_ago));
    
    // Users by day
    $by_day = $wpdb->get_results("
        SELECT DATE(user_registered) as day, COUNT(*) as count
        FROM {$wpdb->users}
        WHERE 1=1 $where
        GROUP BY DATE(user_registered)
        ORDER BY day ASC
    ");
    foreach ($by_day as $row) {
        $stats['users_by_day'][$row->day] = (int)$row->count;
    }
    
    // Customer tiers and top customers
    $users = $wpdb->get_results("
        SELECT u.ID, u.display_name, u.user_email
        FROM {$wpdb->users} u
    ");
    
    $customer_data = array();
    foreach ($users as $user) {
        $total_spent = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(15,2))), 0)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
            WHERE p.post_type = 'petshop_order' 
            AND p.post_status = 'publish'
            AND p.post_author = %d
            AND pm_status.meta_value IN ('completed', 'processing', 'confirmed', 'shipping')
        ", $user->ID));
        
        $spent = floatval($total_spent);
        $customer_data[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'spent' => $spent
        );
        
        if ($spent >= 10000000) {
            $stats['customers']['gold']['count']++;
            $stats['customers']['gold']['total_spent'] += $spent;
        } elseif ($spent >= 3000000) {
            $stats['customers']['silver']['count']++;
            $stats['customers']['silver']['total_spent'] += $spent;
        } else {
            $stats['customers']['bronze']['count']++;
            $stats['customers']['bronze']['total_spent'] += $spent;
        }
    }
    
    // Top 10 customers
    usort($customer_data, function($a, $b) {
        return $b['spent'] - $a['spent'];
    });
    $stats['top_customers'] = array_slice($customer_data, 0, 10);
    
    return $stats;
}

/**
 * Lấy thống kê sự kiện (events)
 */
function petshop_get_event_stats($start_date = null, $end_date = null) {
    global $wpdb;
    
    $events_table = $wpdb->prefix . 'petshop_events';
    
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$events_table'") === $events_table;
    
    $stats = array(
        'view_product' => 0,
        'add_to_cart' => 0,
        'begin_checkout' => 0,
        'purchase' => 0,
        'events_by_day' => array(),
        'top_products' => array()
    );
    
    if (!$exists) {
        return $stats;
    }
    
    $where = "WHERE 1=1";
    if ($start_date) {
        $where .= $wpdb->prepare(" AND DATE(created_at) >= %s", $start_date);
    }
    if ($end_date) {
        $where .= $wpdb->prepare(" AND DATE(created_at) <= %s", $end_date);
    }
    
    $events = $wpdb->get_results("
        SELECT event_type, COUNT(*) as count
        FROM $events_table
        $where
        GROUP BY event_type
    ");
    
    foreach ($events as $event) {
        if (isset($stats[$event->event_type])) {
            $stats[$event->event_type] = (int)$event->count;
        }
    }
    
    // Events by day
    $by_day = $wpdb->get_results("
        SELECT DATE(created_at) as day, event_type, COUNT(*) as count
        FROM $events_table
        $where
        GROUP BY DATE(created_at), event_type
        ORDER BY day ASC
    ");
    foreach ($by_day as $row) {
        if (!isset($stats['events_by_day'][$row->day])) {
            $stats['events_by_day'][$row->day] = array();
        }
        $stats['events_by_day'][$row->day][$row->event_type] = (int)$row->count;
    }
    
    // Top viewed products
    $top_products = $wpdb->get_results("
        SELECT product_id, COUNT(*) as views
        FROM $events_table
        $where AND event_type = 'view_product' AND product_id IS NOT NULL
        GROUP BY product_id
        ORDER BY views DESC
        LIMIT 10
    ");
    foreach ($top_products as $product) {
        $product_title = get_the_title($product->product_id) ?: 'Sản phẩm #' . $product->product_id;
        $stats['top_products'][$product->product_id] = array(
            'title' => $product_title,
            'views' => (int)$product->views
        );
    }
    
    return $stats;
}

/**
 * Lấy thống kê sản phẩm
 */
function petshop_get_product_stats() {
    global $wpdb;
    
    $stats = array(
        'total_products' => 0,
        'in_stock' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0,
        'by_category' => array()
    );
    
    // Count products
    $stats['total_products'] = (int)$wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} 
        WHERE post_type = 'product' AND post_status = 'publish'
    ");
    
    // Stock status
    $products = $wpdb->get_results("
        SELECT p.ID, pm_stock.meta_value as stock_qty
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
        WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ");
    
    foreach ($products as $product) {
        $qty = intval($product->stock_qty);
        if ($qty <= 0) {
            $stats['out_of_stock']++;
        } elseif ($qty <= 5) {
            $stats['low_stock']++;
        } else {
            $stats['in_stock']++;
        }
    }
    
    // By category
    $categories = get_terms(array(
        'taxonomy' => 'product_category',
        'hide_empty' => false
    ));
    
    if (!is_wp_error($categories)) {
        foreach ($categories as $cat) {
            $stats['by_category'][$cat->name] = $cat->count;
        }
    }
    
    return $stats;
}

// ===== DASHBOARD PAGE =====

function petshop_crm_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Không có quyền truy cập');
    }
    
    // Filter params
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Get all stats
    $order_stats = petshop_get_order_stats($start_date, $end_date);
    $traffic_stats = petshop_get_traffic_stats($start_date, $end_date);
    $user_stats = petshop_get_user_stats($start_date, $end_date);
    $event_stats = petshop_get_event_stats($start_date, $end_date);
    $product_stats = petshop_get_product_stats();
    
    // Conversion calculations
    $sessions = $traffic_stats['total_sessions'] ?: 1;
    $funnel = array(
        'sessions' => $sessions,
        'view_product' => $event_stats['view_product'],
        'add_to_cart' => $event_stats['add_to_cart'],
        'checkout' => $event_stats['begin_checkout'],
        'purchase' => $order_stats['confirmed_orders']
    );
    
    // Customer totals
    $total_customers = $user_stats['customers']['gold']['count'] + $user_stats['customers']['silver']['count'] + $user_stats['customers']['bronze']['count'];
    $total_customers = $total_customers ?: 1;
    
    ?>
    <style>
        :root {
            --primary: #4361ee;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
            --gray: #6b7280;
            --light: #f8f9fa;
            --dark: #1a1a2e;
            --border: #e5e7eb;
        }
        
        .pbi-dashboard {
            padding: 20px;
            background: var(--light);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }
        
        .pbi-dashboard * {
            box-sizing: border-box;
        }
        
        /* Header */
        .pbi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .pbi-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pbi-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .pbi-filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .pbi-filter-group label {
            font-size: 13px;
            color: var(--gray);
            font-weight: 500;
        }
        
        .pbi-input {
            padding: 6px 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 13px;
            background: white;
        }
        
        .pbi-btn {
            padding: 6px 14px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .pbi-btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .pbi-btn-primary:hover {
            background: #3451d1;
        }
        
        .pbi-btn-success {
            background: var(--success);
            color: white;
        }
        
        .pbi-btn-success:hover {
            background: #059669;
        }
        
        /* Grid Layout */
        .pbi-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .pbi-grid-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .pbi-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .pbi-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .pbi-grid-2-1 {
            grid-template-columns: 2fr 1fr;
        }
        
        .pbi-grid-1-2 {
            grid-template-columns: 1fr 2fr;
        }
        
        @media (max-width: 1200px) {
            .pbi-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .pbi-grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .pbi-grid-4, .pbi-grid-3, .pbi-grid-2, .pbi-grid-2-1, .pbi-grid-1-2 {
                grid-template-columns: 1fr;
            }
        }
        
        /* Cards */
        .pbi-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: box-shadow 0.2s;
            position: relative;
        }
        
        .pbi-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .pbi-card-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbfc;
        }
        
        .pbi-card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .pbi-card-title i {
            color: var(--gray);
        }
        
        .pbi-card-body {
            padding: 16px;
        }
        
        .pbi-card-actions {
            display: flex;
            gap: 6px;
        }
        
        .pbi-card-btn {
            padding: 4px 8px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            color: var(--gray);
            transition: all 0.2s;
        }
        
        .pbi-card-btn:hover {
            background: var(--light);
            color: var(--dark);
        }
        
        /* Size controls */
        .pbi-size-controls {
            display: flex;
            gap: 4px;
        }
        
        .pbi-size-btn {
            width: 24px;
            height: 24px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pbi-size-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* KPI Cards */
        .pbi-kpi {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .pbi-kpi:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .pbi-kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .pbi-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .pbi-kpi-icon.blue { background: #dbeafe; color: var(--primary); }
        .pbi-kpi-icon.green { background: #d1fae5; color: var(--success); }
        .pbi-kpi-icon.yellow { background: #fef3c7; color: var(--warning); }
        .pbi-kpi-icon.red { background: #fee2e2; color: var(--danger); }
        .pbi-kpi-icon.purple { background: #ede9fe; color: var(--purple); }
        .pbi-kpi-icon.gray { background: #f3f4f6; color: var(--gray); }
        
        .pbi-kpi-change {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .pbi-kpi-change.up { background: #d1fae5; color: #059669; }
        .pbi-kpi-change.down { background: #fee2e2; color: #dc2626; }
        
        .pbi-kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .pbi-kpi-label {
            font-size: 13px;
            color: var(--gray);
            margin-top: 4px;
        }
        
        .pbi-kpi-detail {
            font-size: 11px;
            color: var(--gray);
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            display: none;
        }
        
        .pbi-kpi:hover .pbi-kpi-detail {
            display: block;
        }
        
        /* Chart Container */
        .pbi-chart {
            height: 280px;
            position: relative;
        }
        
        .pbi-chart.small {
            height: 200px;
        }
        
        .pbi-chart.large {
            height: 350px;
        }
        
        /* Tables */
        .pbi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .pbi-table th,
        .pbi-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .pbi-table th {
            background: #fafbfc;
            font-weight: 600;
            color: var(--gray);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pbi-table tr:hover td {
            background: #f8fafc;
        }
        
        .pbi-table tr.highlighted td {
            background: #eff6ff;
        }
        
        .pbi-table-scroll {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* Status badges */
        .pbi-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .pbi-badge.completed { background: #d1fae5; color: #059669; }
        .pbi-badge.processing { background: #dbeafe; color: #2563eb; }
        .pbi-badge.pending { background: #fef3c7; color: #d97706; }
        .pbi-badge.confirmed { background: #c7d2fe; color: #4338ca; }
        .pbi-badge.shipping { background: #e0e7ff; color: #4f46e5; }
        .pbi-badge.cancelled { background: #fee2e2; color: #dc2626; }
        .pbi-badge.refunded { background: #f3f4f6; color: #6b7280; }
        
        /* Funnel */
        .pbi-funnel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        
        .pbi-funnel-step {
            flex: 1;
            text-align: center;
            padding: 0 10px;
            position: relative;
        }
        
        .pbi-funnel-step::after {
            content: '→';
            position: absolute;
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--border);
            font-size: 20px;
        }
        
        .pbi-funnel-step:last-child::after {
            display: none;
        }
        
        .pbi-funnel-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .pbi-funnel-label {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }
        
        .pbi-funnel-rate {
            font-size: 11px;
            color: var(--success);
            margin-top: 2px;
        }
        
        /* Time filter per card */
        .pbi-time-filter {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .pbi-time-filter select {
            padding: 4px 8px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 11px;
            background: white;
        }
        
        /* Resize handle */
        .pbi-resizable {
            resize: both;
            overflow: auto;
            min-height: 200px;
            min-width: 300px;
        }
        
        /* Tooltip enhancements */
        .pbi-tooltip {
            position: absolute;
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            max-width: 250px;
        }
        
        .pbi-tooltip-title {
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .pbi-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 4px;
        }
        
        .pbi-tooltip-label {
            color: #9ca3af;
        }
        
        /* Section title */
        .pbi-section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Power BI embed */
        .pbi-embed {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 8px;
        }
        
        /* Loading spinner */
        .pbi-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: var(--gray);
        }

        /* Percentage bar */
        .pbi-progress {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .pbi-progress-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }
    </style>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div class="wrap pbi-dashboard">
        <!-- Header -->
        <div class="pbi-header">
            <h1 class="pbi-title">
                <i class="bi bi-speedometer2"></i> Dashboard Analytics
            </h1>
            
            <form class="pbi-filters" method="get">
                <input type="hidden" name="page" value="petshop-crm">
                
                <div class="pbi-filter-group">
                    <label>Từ</label>
                    <input type="date" name="start_date" class="pbi-input" value="<?php echo esc_attr($start_date); ?>">
                </div>
                
                <div class="pbi-filter-group">
                    <label>Đến</label>
                    <input type="date" name="end_date" class="pbi-input" value="<?php echo esc_attr($end_date); ?>">
                </div>
                
                <div class="pbi-filter-group">
                    <label>Năm</label>
                    <select name="year" class="pbi-input">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <button type="submit" class="pbi-btn pbi-btn-primary">
                    <i class="bi bi-funnel"></i> Lọc
                </button>
            </form>
        </div>
        
        <!-- KPI Row 1: Traffic -->
        <div class="pbi-section-title"><i class="bi bi-graph-up"></i> TRAFFIC & VISITORS</div>
        <div class="pbi-grid pbi-grid-4">
            <div class="pbi-kpi" title="Tổng số phiên truy cập">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon blue"><i class="bi bi-activity"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($traffic_stats['total_sessions']); ?></div>
                <div class="pbi-kpi-label">Sessions</div>
                <div class="pbi-kpi-detail">Tổng lượt truy cập trong khoảng thời gian đã chọn</div>
            </div>
            
            <div class="pbi-kpi" title="Số người dùng duy nhất">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon purple"><i class="bi bi-people"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($traffic_stats['unique_visitors']); ?></div>
                <div class="pbi-kpi-label">Unique Visitors</div>
                <div class="pbi-kpi-detail">Số khách truy cập không trùng lặp</div>
            </div>
            
            <div class="pbi-kpi" title="Tổng lượt xem trang">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon green"><i class="bi bi-eye"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($traffic_stats['total_pageviews']); ?></div>
                <div class="pbi-kpi-label">Pageviews</div>
                <div class="pbi-kpi-detail">Tổng số trang được xem</div>
            </div>
            
            <div class="pbi-kpi" title="Trung bình trang/phiên">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon yellow"><i class="bi bi-layers"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo $traffic_stats['avg_pages_per_session']; ?></div>
                <div class="pbi-kpi-label">Pages/Session</div>
                <div class="pbi-kpi-detail">Số trang xem trung bình mỗi phiên</div>
            </div>
        </div>
        
        <!-- KPI Row 2: Users -->
        <div class="pbi-section-title"><i class="bi bi-person-badge"></i> NGƯỜI DÙNG & KHÁCH HÀNG</div>
        <div class="pbi-grid pbi-grid-4">
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon green"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($user_stats['total_users']); ?></div>
                <div class="pbi-kpi-label">Tổng người dùng</div>
                <div class="pbi-kpi-detail">Tất cả tài khoản đã đăng ký</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon blue"><i class="bi bi-person-plus"></i></div>
                    <span class="pbi-kpi-change up">+<?php echo $user_stats['new_users']; ?></span>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($user_stats['new_users']); ?></div>
                <div class="pbi-kpi-label">Người dùng mới (30 ngày)</div>
                <div class="pbi-kpi-detail">Đăng ký trong 30 ngày gần nhất</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon yellow"><i class="bi bi-trophy"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($user_stats['customers']['gold']['count']); ?></div>
                <div class="pbi-kpi-label">Khách Gold (≥10tr)</div>
                <div class="pbi-kpi-detail">Tổng chi: <?php echo number_format($user_stats['customers']['gold']['total_spent']); ?>đ</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon gray"><i class="bi bi-award"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($user_stats['customers']['silver']['count']); ?></div>
                <div class="pbi-kpi-label">Khách Silver (≥3tr)</div>
                <div class="pbi-kpi-detail">Tổng chi: <?php echo number_format($user_stats['customers']['silver']['total_spent']); ?>đ</div>
            </div>
        </div>
        
        <!-- KPI Row 3: Orders -->
        <div class="pbi-section-title"><i class="bi bi-box-seam"></i> ĐƠN HÀNG & DOANH THU</div>
        <div class="pbi-grid pbi-grid-4">
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon blue"><i class="bi bi-cart-check"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($order_stats['total_orders']); ?></div>
                <div class="pbi-kpi-label">Tổng đơn hàng</div>
                <div class="pbi-kpi-detail">Tất cả đơn hàng trong kỳ</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon green"><i class="bi bi-check-circle"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($order_stats['confirmed_orders']); ?></div>
                <div class="pbi-kpi-label">Đơn thành công</div>
                <div class="pbi-kpi-detail">Hoàn thành, đang xử lý, đang giao</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon green"><i class="bi bi-currency-dollar"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($order_stats['gross_revenue'] / 1000000, 1); ?>M</div>
                <div class="pbi-kpi-label">Doanh thu (VNĐ)</div>
                <div class="pbi-kpi-detail">Giá trị: <?php echo number_format($order_stats['gross_revenue']); ?>đ</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon purple"><i class="bi bi-receipt"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($order_stats['avg_order_value']); ?>đ</div>
                <div class="pbi-kpi-label">Giá trị TB/đơn</div>
                <div class="pbi-kpi-detail">Average Order Value</div>
            </div>
        </div>
        
        <!-- Conversion Funnel -->
        <div class="pbi-card" style="margin-bottom: 16px;">
            <div class="pbi-card-header">
                <h3 class="pbi-card-title"><i class="bi bi-funnel"></i> Conversion Funnel</h3>
            </div>
            <div class="pbi-card-body">
                <div class="pbi-funnel">
                    <div class="pbi-funnel-step">
                        <div class="pbi-funnel-value"><?php echo number_format($funnel['sessions']); ?></div>
                        <div class="pbi-funnel-label">Sessions</div>
                        <div class="pbi-funnel-rate">100%</div>
                    </div>
                    <div class="pbi-funnel-step">
                        <div class="pbi-funnel-value"><?php echo number_format($funnel['view_product']); ?></div>
                        <div class="pbi-funnel-label">Xem SP</div>
                        <div class="pbi-funnel-rate"><?php echo round($funnel['view_product'] / $sessions * 100, 1); ?>%</div>
                    </div>
                    <div class="pbi-funnel-step">
                        <div class="pbi-funnel-value"><?php echo number_format($funnel['add_to_cart']); ?></div>
                        <div class="pbi-funnel-label">Thêm giỏ</div>
                        <div class="pbi-funnel-rate"><?php echo round($funnel['add_to_cart'] / $sessions * 100, 1); ?>%</div>
                    </div>
                    <div class="pbi-funnel-step">
                        <div class="pbi-funnel-value"><?php echo number_format($funnel['checkout']); ?></div>
                        <div class="pbi-funnel-label">Checkout</div>
                        <div class="pbi-funnel-rate"><?php echo round($funnel['checkout'] / $sessions * 100, 1); ?>%</div>
                    </div>
                    <div class="pbi-funnel-step">
                        <div class="pbi-funnel-value"><?php echo number_format($funnel['purchase']); ?></div>
                        <div class="pbi-funnel-label">Mua hàng</div>
                        <div class="pbi-funnel-rate"><?php echo round($funnel['purchase'] / $sessions * 100, 1); ?>%</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Sessions by Day -->
            <div class="pbi-card pbi-resizable" id="card-sessions">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-graph-up"></i> Sessions theo ngày</h3>
                    <div class="pbi-card-actions">
                        <div class="pbi-time-filter">
                            <select onchange="filterChartTime('sessions', this.value)">
                                <option value="7">7 ngày</option>
                                <option value="14">14 ngày</option>
                                <option value="30" selected>30 ngày</option>
                                <option value="90">90 ngày</option>
                            </select>
                        </div>
                        <div class="pbi-size-controls">
                            <button class="pbi-size-btn" onclick="resizeCard('card-sessions', 'small')" title="Thu nhỏ"><i class="bi bi-dash"></i></button>
                            <button class="pbi-size-btn" onclick="resizeCard('card-sessions', 'large')" title="Phóng to"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-chart" id="chart-sessions">
                        <canvas id="sessionsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Sessions by Day Table -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-table"></i> Chi tiết Sessions theo ngày</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-table-scroll">
                        <table class="pbi-table" id="sessionsTable">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Sessions</th>
                                    <th>Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_sessions_sum = array_sum($traffic_stats['sessions_by_day']) ?: 1;
                                $days = array_slice($traffic_stats['sessions_by_day'], -30, null, true);
                                foreach (array_reverse($days, true) as $day => $count): 
                                ?>
                                <tr data-day="<?php echo $day; ?>">
                                    <td><?php echo date('d/m/Y', strtotime($day)); ?></td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div class="pbi-progress" style="width: 60px;">
                                                <div class="pbi-progress-bar" style="width: <?php echo round($count / max($days) * 100); ?>%; background: var(--primary);"></div>
                                            </div>
                                            <span><?php echo round($count / $total_sessions_sum * 100, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Traffic Sources Chart -->
            <div class="pbi-card pbi-resizable" id="card-sources">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-pie-chart"></i> Nguồn Traffic</h3>
                    <div class="pbi-size-controls">
                        <button class="pbi-size-btn" onclick="resizeCard('card-sources', 'small')"><i class="bi bi-dash"></i></button>
                        <button class="pbi-size-btn" onclick="resizeCard('card-sources', 'large')"><i class="bi bi-plus"></i></button>
                    </div>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-chart" id="chart-sources">
                        <canvas id="sourcesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Traffic Sources Table -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-table"></i> Chi tiết nguồn Traffic</h3>
                </div>
                <div class="pbi-card-body">
                    <table class="pbi-table" id="sourcesTable">
                        <thead>
                            <tr>
                                <th>Nguồn</th>
                                <th>Sessions</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_src = array_sum($traffic_stats['traffic_sources']) ?: 1;
                            foreach ($traffic_stats['traffic_sources'] as $source => $count): 
                            ?>
                            <tr data-source="<?php echo esc_attr($source); ?>">
                                <td><strong><?php echo esc_html($source); ?></strong></td>
                                <td><?php echo number_format($count); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="pbi-progress" style="width: 80px;">
                                            <div class="pbi-progress-bar" style="width: <?php echo round($count / $total_src * 100); ?>%; background: var(--primary);"></div>
                                        </div>
                                        <span><?php echo round($count / $total_src * 100, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 3: Devices & Browsers -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Devices -->
            <div class="pbi-card" id="card-devices">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-phone"></i> Thiết bị</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-chart small">
                        <canvas id="devicesChart"></canvas>
                    </div>
                    <table class="pbi-table" style="margin-top: 16px;">
                        <tbody>
                            <?php 
                            $total_dev = array_sum($traffic_stats['devices']) ?: 1;
                            foreach ($traffic_stats['devices'] as $device => $count): 
                            ?>
                            <tr>
                                <td><i class="bi bi-<?php echo $device === 'mobile' ? 'phone' : ($device === 'tablet' ? 'tablet' : 'laptop'); ?>"></i> <?php echo ucfirst($device); ?></td>
                                <td><?php echo number_format($count); ?></td>
                                <td><?php echo round($count / $total_dev * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Browsers -->
            <div class="pbi-card" id="card-browsers">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-globe"></i> Trình duyệt</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-chart small">
                        <canvas id="browsersChart"></canvas>
                    </div>
                    <table class="pbi-table" style="margin-top: 16px;">
                        <tbody>
                            <?php 
                            $total_br = array_sum($traffic_stats['browsers']) ?: 1;
                            foreach ($traffic_stats['browsers'] as $browser => $count): 
                            ?>
                            <tr>
                                <td><?php echo esc_html($browser); ?></td>
                                <td><?php echo number_format($count); ?></td>
                                <td><?php echo round($count / $total_br * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Orders Chart (Full Width) -->
        <div class="pbi-card pbi-resizable" id="card-orders" style="margin-bottom: 16px;">
            <div class="pbi-card-header">
                <h3 class="pbi-card-title"><i class="bi bi-bar-chart"></i> Đơn hàng & Doanh thu theo tháng (<?php echo $selected_year; ?>)</h3>
                <div class="pbi-card-actions">
                    <div class="pbi-time-filter">
                        <select onchange="window.location.href='?page=petshop-crm&year='+this.value">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="pbi-size-controls">
                        <button class="pbi-size-btn" onclick="resizeCard('card-orders', 'small')"><i class="bi bi-dash"></i></button>
                        <button class="pbi-size-btn" onclick="resizeCard('card-orders', 'large')"><i class="bi bi-plus"></i></button>
                    </div>
                </div>
            </div>
            <div class="pbi-card-body">
                <div class="pbi-chart" id="chart-orders">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Orders Tables -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Orders by Status -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-list-check"></i> Đơn hàng theo trạng thái</h3>
                </div>
                <div class="pbi-card-body">
                    <table class="pbi-table" id="statusTable">
                        <thead>
                            <tr>
                                <th>Trạng thái</th>
                                <th>Số lượng</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $status_labels = array(
                                'pending' => 'Chờ xử lý',
                                'confirmed' => 'Đã xác nhận',
                                'processing' => 'Đang xử lý',
                                'shipping' => 'Đang giao',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy',
                                'refunded' => 'Hoàn tiền'
                            );
                            foreach ($order_stats['orders_by_status'] as $status => $data): 
                                $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            ?>
                            <tr data-status="<?php echo esc_attr($status); ?>">
                                <td><span class="pbi-badge <?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></span></td>
                                <td><strong><?php echo number_format($data['count']); ?></strong></td>
                                <td><?php echo number_format($data['total']); ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Orders by Month Table -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-calendar3"></i> Đơn hàng theo tháng</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-table-scroll">
                        <table class="pbi-table" id="monthTable">
                            <thead>
                                <tr>
                                    <th>Tháng</th>
                                    <th>Số đơn</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach (array_reverse($order_stats['orders_by_month'], true) as $month => $data): 
                                ?>
                                <tr data-month="<?php echo $month; ?>">
                                    <td><?php echo date('m/Y', strtotime($month . '-01')); ?></td>
                                    <td><strong><?php echo number_format($data['count']); ?></strong></td>
                                    <td><?php echo number_format($data['total']); ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders & Top Customers -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Recent Orders -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-clock-history"></i> Đơn hàng gần đây</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-table-scroll">
                        <table class="pbi-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Khách hàng</th>
                                    <th>Giá trị</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_stats['recent_orders'] as $order): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                                    <td><?php echo esc_html($order['customer'] ?: 'Khách vãng lai'); ?></td>
                                    <td><strong><?php echo number_format($order['total']); ?>đ</strong></td>
                                    <td><span class="pbi-badge <?php echo esc_attr($order['status']); ?>"><?php echo esc_html($status_labels[$order['status']] ?? $order['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-star"></i> Top khách hàng</h3>
                </div>
                <div class="pbi-card-body">
                    <div class="pbi-table-scroll">
                        <table class="pbi-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng chi tiêu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($user_stats['top_customers'] as $customer): 
                                    if ($customer['spent'] <= 0) continue;
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <i class="bi bi-trophy-fill" style="color: <?php echo $rank == 1 ? '#f59e0b' : ($rank == 2 ? '#9ca3af' : '#d97706'); ?>;"></i>
                                        <?php else: ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($customer['name'] ?: $customer['email']); ?></strong>
                                    </td>
                                    <td><strong><?php echo number_format($customer['spent']); ?>đ</strong></td>
                                </tr>
                                <?php 
                                    $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Stats -->
        <div class="pbi-section-title"><i class="bi bi-box"></i> SẢN PHẨM</div>
        <div class="pbi-grid pbi-grid-4">
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon blue"><i class="bi bi-box-seam"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($product_stats['total_products']); ?></div>
                <div class="pbi-kpi-label">Tổng sản phẩm</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon green"><i class="bi bi-check-circle"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($product_stats['in_stock']); ?></div>
                <div class="pbi-kpi-label">Còn hàng</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon yellow"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($product_stats['low_stock']); ?></div>
                <div class="pbi-kpi-label">Sắp hết (≤5)</div>
            </div>
            
            <div class="pbi-kpi">
                <div class="pbi-kpi-header">
                    <div class="pbi-kpi-icon red"><i class="bi bi-x-circle"></i></div>
                </div>
                <div class="pbi-kpi-value"><?php echo number_format($product_stats['out_of_stock']); ?></div>
                <div class="pbi-kpi-label">Hết hàng</div>
            </div>
        </div>
        
        <!-- Top Pages & Products -->
        <div class="pbi-grid pbi-grid-2">
            <!-- Top Pages -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-file-earmark-text"></i> Top trang xem nhiều</h3>
                </div>
                <div class="pbi-card-body">
                    <table class="pbi-table">
                        <thead>
                            <tr>
                                <th>Trang</th>
                                <th>Lượt xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traffic_stats['top_pages'] as $url => $views): ?>
                            <tr>
                                <td><?php echo esc_html($url ?: '/'); ?></td>
                                <td><strong><?php echo number_format($views); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Products Viewed -->
            <div class="pbi-card">
                <div class="pbi-card-header">
                    <h3 class="pbi-card-title"><i class="bi bi-eye"></i> Sản phẩm xem nhiều</h3>
                </div>
                <div class="pbi-card-body">
                    <table class="pbi-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Lượt xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($event_stats['top_products'] as $id => $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['title']); ?></td>
                                <td><strong><?php echo number_format($product['views']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sessions by Hour -->
        <div class="pbi-card pbi-resizable" id="card-hours" style="margin-bottom: 16px;">
            <div class="pbi-card-header">
                <h3 class="pbi-card-title"><i class="bi bi-clock"></i> Sessions theo giờ trong ngày</h3>
                <div class="pbi-size-controls">
                    <button class="pbi-size-btn" onclick="resizeCard('card-hours', 'small')"><i class="bi bi-dash"></i></button>
                    <button class="pbi-size-btn" onclick="resizeCard('card-hours', 'large')"><i class="bi bi-plus"></i></button>
                </div>
            </div>
            <div class="pbi-card-body">
                <div class="pbi-chart" id="chart-hours">
                    <canvas id="hoursChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Power BI Embed -->
        <div class="pbi-card" style="margin-bottom: 16px;">
            <div class="pbi-card-header">
                <h3 class="pbi-card-title"><i class="bi bi-bar-chart-fill"></i> Power BI Report</h3>
            </div>
            <div class="pbi-card-body" style="padding: 0;">
                <iframe 
                    class="pbi-embed"
                    title="Power BI Report" 
                    src="https://app.powerbi.com/view?r=eyJrIjoiZjE3OTk1YjYtMjcyNS00Mzc4LWJmNmMtMjYyOTg5ZjdlMzcxIiwidCI6ImVjYTdhYTFjLTM5NGEtNGFiZS1iMDIyLWZhMzY2NTA2MmU5NiIsImMiOjEwfQ%3D%3D" 
                    frameborder="0" 
                    allowFullScreen="true">
                </iframe>
            </div>
        </div>
    </div>
    
    <script>
    // Chart.js defaults
    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.85)';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
    
    // Data from PHP
    const sessionsData = <?php echo json_encode($traffic_stats['sessions_by_day']); ?>;
    const sourcesData = <?php echo json_encode($traffic_stats['traffic_sources']); ?>;
    const devicesData = <?php echo json_encode($traffic_stats['devices']); ?>;
    const browsersData = <?php echo json_encode($traffic_stats['browsers']); ?>;
    const hoursData = <?php echo json_encode($traffic_stats['sessions_by_hour']); ?>;
    const ordersByMonth = <?php echo json_encode($order_stats['orders_by_month']); ?>;
    const selectedYear = <?php echo $selected_year; ?>;
    
    // Color palette
    const colors = ['#4361ee', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];
    
    // Sessions Line Chart
    const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
    const sessionsChart = new Chart(sessionsCtx, {
        type: 'line',
        data: {
            labels: Object.keys(sessionsData).slice(-30),
            datasets: [{
                label: 'Sessions',
                data: Object.values(sessionsData).slice(-30),
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#4361ee'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => 'Ngày: ' + items[0].label,
                        label: (item) => 'Sessions: ' + item.raw.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        maxTicksLimit: 10,
                        callback: function(val, index) {
                            const label = this.getLabelForValue(val);
                            return label ? label.slice(5) : '';
                        }
                    }
                }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
    
    // Traffic Sources Doughnut
    const sourcesCtx = document.getElementById('sourcesChart').getContext('2d');
    const sourcesChart = new Chart(sourcesCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(sourcesData),
            datasets: [{
                data: Object.values(sourcesData),
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { padding: 15, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: (item) => {
                            const total = item.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((item.raw / total) * 100).toFixed(1);
                            return `${item.label}: ${item.raw.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
    
    // Devices Chart
    const devicesCtx = document.getElementById('devicesChart').getContext('2d');
    new Chart(devicesCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(devicesData),
            datasets: [{
                data: Object.values(devicesData),
                backgroundColor: ['#4361ee', '#10b981', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '70%'
        }
    });
    
    // Browsers Chart
    const browsersCtx = document.getElementById('browsersChart').getContext('2d');
    new Chart(browsersCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(browsersData),
            datasets: [{
                data: Object.values(browsersData),
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '70%'
        }
    });
    
    // Sessions by Hour Bar Chart
    const hoursCtx = document.getElementById('hoursChart').getContext('2d');
    new Chart(hoursCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(hoursData).map(h => h + ':00'),
            datasets: [{
                label: 'Sessions',
                data: Object.values(hoursData),
                backgroundColor: 'rgba(67, 97, 238, 0.8)',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => 'Giờ ' + items[0].label,
                        label: (item) => 'Sessions: ' + item.raw.toLocaleString()
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
    
    // Orders Combo Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    const months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    const monthLabels = months.map(m => selectedYear + '-' + m);
    const orderCounts = monthLabels.map(m => ordersByMonth[m]?.count || 0);
    const orderTotals = monthLabels.map(m => ordersByMonth[m]?.total || 0);
    
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: months.map(m => 'T' + parseInt(m)),
            datasets: [
                {
                    type: 'bar',
                    label: 'Số đơn',
                    data: orderCounts,
                    backgroundColor: 'rgba(67, 97, 238, 0.8)',
                    borderRadius: 4,
                    yAxisID: 'y'
                },
                {
                    type: 'line',
                    label: 'Doanh thu',
                    data: orderTotals,
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (item) => {
                            if (item.dataset.label === 'Doanh thu') {
                                return 'Doanh thu: ' + item.raw.toLocaleString() + 'đ';
                            }
                            return 'Số đơn: ' + item.raw;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Số đơn' },
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Doanh thu (VND)' },
                    beginAtZero: true,
                    grid: { display: false }
                },
                x: { grid: { display: false } }
            }
        }
    });
    
    // Table row hover -> highlight chart
    document.querySelectorAll('#sourcesTable tbody tr').forEach((row, index) => {
        row.addEventListener('mouseenter', () => {
            sourcesChart.setActiveElements([{datasetIndex: 0, index: index}]);
            sourcesChart.update();
            row.classList.add('highlighted');
        });
        row.addEventListener('mouseleave', () => {
            sourcesChart.setActiveElements([]);
            sourcesChart.update();
            row.classList.remove('highlighted');
        });
    });
    
    // Resize card function
    function resizeCard(cardId, size) {
        const card = document.getElementById(cardId);
        const chart = card.querySelector('.pbi-chart');
        if (!chart) return;
        
        chart.classList.remove('small', 'large');
        if (size !== 'normal') {
            chart.classList.add(size);
        }
        
        // Trigger chart resize
        const canvas = chart.querySelector('canvas');
        if (canvas && canvas.chart) {
            canvas.chart.resize();
        }
        
        // Force Chart.js to resize
        window.dispatchEvent(new Event('resize'));
    }
    
    // Time filter for charts
    function filterChartTime(chartType, days) {
        if (chartType === 'sessions') {
            const allDays = Object.keys(sessionsData);
            const allValues = Object.values(sessionsData);
            const filtered = allDays.slice(-parseInt(days));
            const filteredValues = allValues.slice(-parseInt(days));
            
            sessionsChart.data.labels = filtered;
            sessionsChart.data.datasets[0].data = filteredValues;
            sessionsChart.update();
        }
    }
    
    </script>
    <?php
}

// ===== REPORTS PAGE =====

function petshop_crm_reports_page() {
    ?>
    <div class="wrap">
        <h1><i class="bi bi-file-earmark-bar-graph"></i> Báo cáo CRM</h1>
        <p>Trang báo cáo chi tiết sẽ được phát triển trong phiên bản tiếp theo.</p>
    </div>
    <?php
}
