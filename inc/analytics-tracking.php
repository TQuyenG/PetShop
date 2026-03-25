<?php
/**
 * PetShop Analytics & Tracking System
 * Hệ thống theo dõi Sessions, Page Views, Events
 * 
 * Features:
 * - Tracking sessions & unique visitors
 * - Page views tracking
 * - Event tracking (view, add_to_cart, checkout)
 * - UTM source tracking
 * - Analytics data cho Dashboard KPI
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// TẠO BẢNG DATABASE
// =============================================
function petshop_create_analytics_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Bảng sessions
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    $sql_sessions = "CREATE TABLE IF NOT EXISTS $table_sessions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        visitor_id varchar(64) NOT NULL,
        user_id bigint(20) DEFAULT 0,
        utm_source varchar(100) DEFAULT 'direct',
        utm_medium varchar(100) DEFAULT '',
        utm_campaign varchar(255) DEFAULT '',
        device_type varchar(50) DEFAULT 'desktop',
        browser varchar(100) DEFAULT '',
        ip_address varchar(45) DEFAULT '',
        referrer text,
        landing_page text,
        page_views int(11) DEFAULT 1,
        events int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY session_id (session_id),
        KEY visitor_id (visitor_id),
        KEY utm_source (utm_source),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Bảng page views
    $table_pageviews = $wpdb->prefix . 'petshop_pageviews';
    $sql_pageviews = "CREATE TABLE IF NOT EXISTS $table_pageviews (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        visitor_id varchar(64) NOT NULL,
        user_id bigint(20) DEFAULT 0,
        page_url text NOT NULL,
        page_title varchar(255) DEFAULT '',
        page_type varchar(50) DEFAULT 'page',
        product_id bigint(20) DEFAULT 0,
        category_id bigint(20) DEFAULT 0,
        duration int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY page_type (page_type),
        KEY product_id (product_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Bảng events (add_to_cart, checkout, view, purchase, etc.)
    $table_events = $wpdb->prefix . 'petshop_events';
    $sql_events = "CREATE TABLE IF NOT EXISTS $table_events (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        visitor_id varchar(64) NOT NULL,
        user_id bigint(20) DEFAULT 0,
        event_name varchar(50) NOT NULL,
        event_category varchar(50) DEFAULT '',
        product_id bigint(20) DEFAULT 0,
        product_name varchar(255) DEFAULT '',
        product_price decimal(15,2) DEFAULT 0,
        quantity int(11) DEFAULT 1,
        value decimal(15,2) DEFAULT 0,
        order_id bigint(20) DEFAULT 0,
        utm_source varchar(100) DEFAULT 'direct',
        metadata text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY event_name (event_name),
        KEY product_id (product_id),
        KEY order_id (order_id),
        KEY utm_source (utm_source),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_sessions);
    dbDelta($sql_pageviews);
    dbDelta($sql_events);
}
add_action('after_switch_theme', 'petshop_create_analytics_tables');

// Tạo bảng khi init nếu chưa có
function petshop_check_analytics_tables() {
    global $wpdb;
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_sessions'") !== $table_sessions) {
        petshop_create_analytics_tables();
    }
}
add_action('init', 'petshop_check_analytics_tables');

// =============================================
// FRONTEND TRACKING SCRIPT
// =============================================
function petshop_analytics_tracking_script() {
    if (is_admin()) return;
    
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('petshop_analytics_nonce');
    ?>
    <script>
    (function() {
        // Generate or get visitor ID
        function getVisitorId() {
            let visitorId = localStorage.getItem('petshop_visitor_id');
            if (!visitorId) {
                visitorId = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('petshop_visitor_id', visitorId);
            }
            return visitorId;
        }
        
        // Generate session ID (expires after 30 min of inactivity)
        function getSessionId() {
            const sessionKey = 'petshop_session';
            const sessionTimeout = 30 * 60 * 1000; // 30 minutes
            let session = JSON.parse(sessionStorage.getItem(sessionKey) || '{}');
            
            if (!session.id || (Date.now() - session.lastActivity) > sessionTimeout) {
                session = {
                    id: 's_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    lastActivity: Date.now(),
                    isNew: true
                };
            } else {
                session.lastActivity = Date.now();
                session.isNew = false;
            }
            
            sessionStorage.setItem(sessionKey, JSON.stringify(session));
            return session;
        }
        
        // Get UTM parameters
        function getUtmParams() {
            const urlParams = new URLSearchParams(window.location.search);
            return {
                utm_source: urlParams.get('utm_source') || getStoredUtm('source') || 'direct',
                utm_medium: urlParams.get('utm_medium') || getStoredUtm('medium') || '',
                utm_campaign: urlParams.get('utm_campaign') || getStoredUtm('campaign') || ''
            };
        }
        
        function getStoredUtm(type) {
            return sessionStorage.getItem('petshop_utm_' + type) || '';
        }
        
        function storeUtm(params) {
            Object.keys(params).forEach(key => {
                if (params[key]) {
                    sessionStorage.setItem('petshop_utm_' + key.replace('utm_', ''), params[key]);
                }
            });
        }
        
        // Detect device type
        function getDeviceType() {
            const ua = navigator.userAgent;
            if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) return 'tablet';
            if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) return 'mobile';
            return 'desktop';
        }
        
        // Get page type
        function getPageType() {
            const body = document.body;
            if (body.classList.contains('single-product')) return 'product';
            if (body.classList.contains('tax-product_category')) return 'category';
            if (body.classList.contains('page-template-page-gio-hang')) return 'cart';
            if (body.classList.contains('page-template-page-thanh-toan')) return 'checkout';
            if (body.classList.contains('home') || body.classList.contains('page-template-front-page')) return 'home';
            return 'page';
        }
        
        // Track session/pageview
        function trackSession() {
            const visitorId = getVisitorId();
            const session = getSessionId();
            const utm = getUtmParams();
            storeUtm(utm);
            
            const data = new FormData();
            data.append('action', 'petshop_track_session');
            data.append('nonce', '<?php echo $nonce; ?>');
            data.append('visitor_id', visitorId);
            data.append('session_id', session.id);
            data.append('is_new_session', session.isNew ? '1' : '0');
            data.append('utm_source', utm.utm_source);
            data.append('utm_medium', utm.utm_medium);
            data.append('utm_campaign', utm.utm_campaign);
            data.append('device_type', getDeviceType());
            data.append('page_url', window.location.href);
            data.append('page_title', document.title);
            data.append('page_type', getPageType());
            data.append('referrer', document.referrer);
            
            fetch('<?php echo $ajax_url; ?>', {
                method: 'POST',
                body: data
            });
        }
        
        // Track event
        window.petshopTrackEvent = function(eventName, eventData = {}) {
            const visitorId = getVisitorId();
            const session = getSessionId();
            const utm = getUtmParams();
            
            const data = new FormData();
            data.append('action', 'petshop_track_event');
            data.append('nonce', '<?php echo $nonce; ?>');
            data.append('visitor_id', visitorId);
            data.append('session_id', session.id);
            data.append('event_name', eventName);
            data.append('utm_source', utm.utm_source);
            data.append('event_data', JSON.stringify(eventData));
            
            fetch('<?php echo $ajax_url; ?>', {
                method: 'POST',
                body: data
            });
        };
        
        // Track on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', trackSession);
        } else {
            trackSession();
        }
        
        // Auto track view product
        const productMeta = document.querySelector('meta[name="product-id"]');
        if (productMeta) {
            const productId = productMeta.content;
            const productName = document.querySelector('h1.product-title')?.textContent || document.title;
            const productPrice = document.querySelector('.product-price .price')?.textContent?.replace(/[^\d]/g, '') || 0;
            
            window.petshopTrackEvent('view', {
                product_id: productId,
                product_name: productName,
                product_price: parseInt(productPrice)
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'petshop_analytics_tracking_script', 99);

// =============================================
// AJAX HANDLERS
// =============================================

// Track session & pageview
function petshop_ajax_track_session() {
    global $wpdb;
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'petshop_analytics_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $is_new_session = $_POST['is_new_session'] === '1';
    $utm_source = sanitize_text_field($_POST['utm_source'] ?? 'direct');
    $utm_medium = sanitize_text_field($_POST['utm_medium'] ?? '');
    $utm_campaign = sanitize_text_field($_POST['utm_campaign'] ?? '');
    $device_type = sanitize_text_field($_POST['device_type'] ?? 'desktop');
    $page_url = esc_url_raw($_POST['page_url'] ?? '');
    $page_title = sanitize_text_field($_POST['page_title'] ?? '');
    $page_type = sanitize_text_field($_POST['page_type'] ?? 'page');
    $referrer = esc_url_raw($_POST['referrer'] ?? '');
    
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    $table_pageviews = $wpdb->prefix . 'petshop_pageviews';
    
    $user_id = get_current_user_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Insert or update session
    if ($is_new_session) {
        $wpdb->insert($table_sessions, array(
            'session_id' => $session_id,
            'visitor_id' => $visitor_id,
            'user_id' => $user_id,
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'device_type' => $device_type,
            'browser' => substr($browser, 0, 100),
            'ip_address' => $ip_address,
            'referrer' => $referrer,
            'landing_page' => $page_url,
            'page_views' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
    } else {
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_sessions SET page_views = page_views + 1, updated_at = %s WHERE session_id = %s",
            current_time('mysql'),
            $session_id
        ));
    }
    
    // Get product_id and category_id if applicable
    $product_id = 0;
    $category_id = 0;
    
    if ($page_type === 'product') {
        preg_match('/product\/(\d+)|[?&]p=(\d+)/', $page_url, $matches);
        $product_id = intval($matches[1] ?? $matches[2] ?? 0);
    }
    
    // Insert pageview
    $wpdb->insert($table_pageviews, array(
        'session_id' => $session_id,
        'visitor_id' => $visitor_id,
        'user_id' => $user_id,
        'page_url' => $page_url,
        'page_title' => $page_title,
        'page_type' => $page_type,
        'product_id' => $product_id,
        'category_id' => $category_id,
        'created_at' => current_time('mysql')
    ));
    
    wp_send_json_success();
}
add_action('wp_ajax_petshop_track_session', 'petshop_ajax_track_session');
add_action('wp_ajax_nopriv_petshop_track_session', 'petshop_ajax_track_session');

// Track events
function petshop_ajax_track_event() {
    global $wpdb;
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'petshop_analytics_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $event_name = sanitize_text_field($_POST['event_name'] ?? '');
    $utm_source = sanitize_text_field($_POST['utm_source'] ?? 'direct');
    $event_data = json_decode(stripslashes($_POST['event_data'] ?? '{}'), true);
    
    if (empty($event_name)) {
        wp_send_json_error('Missing event name');
    }
    
    $table_events = $wpdb->prefix . 'petshop_events';
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    
    $user_id = get_current_user_id();
    
    // Insert event
    $wpdb->insert($table_events, array(
        'session_id' => $session_id,
        'visitor_id' => $visitor_id,
        'user_id' => $user_id,
        'event_name' => $event_name,
        'event_category' => $event_data['category'] ?? '',
        'product_id' => intval($event_data['product_id'] ?? 0),
        'product_name' => sanitize_text_field($event_data['product_name'] ?? ''),
        'product_price' => floatval($event_data['product_price'] ?? 0),
        'quantity' => intval($event_data['quantity'] ?? 1),
        'value' => floatval($event_data['value'] ?? $event_data['product_price'] ?? 0),
        'order_id' => intval($event_data['order_id'] ?? 0),
        'utm_source' => $utm_source,
        'metadata' => json_encode($event_data),
        'created_at' => current_time('mysql')
    ));
    
    // Update session events count
    $wpdb->query($wpdb->prepare(
        "UPDATE $table_sessions SET events = events + 1 WHERE session_id = %s",
        $session_id
    ));
    
    wp_send_json_success();
}
add_action('wp_ajax_petshop_track_event', 'petshop_ajax_track_event');
add_action('wp_ajax_nopriv_petshop_track_event', 'petshop_ajax_track_event');

// =============================================
// HÀM LẤY THỐNG KÊ ANALYTICS
// =============================================

/**
 * Lấy thống kê traffic cho dashboard
 */
function petshop_get_traffic_stats($start_date, $end_date) {
    global $wpdb;
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    $table_pageviews = $wpdb->prefix . 'petshop_pageviews';
    $table_events = $wpdb->prefix . 'petshop_events';
    
    // Check if tables exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_sessions'") !== $table_sessions) {
        return petshop_get_empty_traffic_stats();
    }
    
    $start = $start_date . ' 00:00:00';
    $end = $end_date . ' 23:59:59';
    
    // Total sessions
    $total_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_sessions WHERE created_at BETWEEN %s AND %s",
        $start, $end
    )) ?: 0;
    
    // Total unique visitors
    $unique_visitors = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT visitor_id) FROM $table_sessions WHERE created_at BETWEEN %s AND %s",
        $start, $end
    )) ?: 0;
    
    // Total page views
    $total_pageviews = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_pageviews WHERE created_at BETWEEN %s AND %s",
        $start, $end
    )) ?: 0;
    
    // Sessions by source
    $sessions_by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT utm_source, COUNT(*) as count FROM $table_sessions 
         WHERE created_at BETWEEN %s AND %s 
         GROUP BY utm_source ORDER BY count DESC",
        $start, $end
    ), ARRAY_A) ?: array();
    
    // Page views by source
    $pageviews_by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT s.utm_source, COUNT(p.id) as count 
         FROM $table_pageviews p 
         LEFT JOIN $table_sessions s ON p.session_id = s.session_id
         WHERE p.created_at BETWEEN %s AND %s 
         GROUP BY s.utm_source ORDER BY count DESC",
        $start, $end
    ), ARRAY_A) ?: array();
    
    // Sessions by month
    $sessions_by_month = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(created_at, '%%Y-%%m') as month, COUNT(*) as count 
         FROM $table_sessions 
         WHERE created_at BETWEEN %s AND %s 
         GROUP BY month ORDER BY month",
        $start, $end
    ), ARRAY_A) ?: array();
    
    // Sessions by day (for chart)
    $sessions_by_day = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as day, COUNT(*) as count 
         FROM $table_sessions 
         WHERE created_at BETWEEN %s AND %s 
         GROUP BY day ORDER BY day",
        $start, $end
    ), ARRAY_A) ?: array();
    
    // Events count
    $events_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT event_name, COUNT(*) as count FROM $table_events 
         WHERE created_at BETWEEN %s AND %s 
         GROUP BY event_name",
        $start, $end
    ), ARRAY_A) ?: array();
    
    $events_by_name = array();
    foreach ($events_stats as $e) {
        $events_by_name[$e['event_name']] = intval($e['count']);
    }
    
    // Device breakdown
    $devices = $wpdb->get_results($wpdb->prepare(
        "SELECT device_type, COUNT(*) as count FROM $table_sessions 
         WHERE created_at BETWEEN %s AND %s 
         GROUP BY device_type",
        $start, $end
    ), ARRAY_A) ?: array();
    
    return array(
        'total_sessions' => intval($total_sessions),
        'unique_visitors' => intval($unique_visitors),
        'total_pageviews' => intval($total_pageviews),
        'sessions_by_source' => $sessions_by_source,
        'pageviews_by_source' => $pageviews_by_source,
        'sessions_by_month' => $sessions_by_month,
        'sessions_by_day' => $sessions_by_day,
        'events' => $events_by_name,
        'view_count' => $events_by_name['view'] ?? 0,
        'add_to_cart_count' => $events_by_name['add_to_cart'] ?? 0,
        'checkout_count' => $events_by_name['checkout'] ?? 0,
        'devices' => $devices,
    );
}

function petshop_get_empty_traffic_stats() {
    return array(
        'total_sessions' => 0,
        'unique_visitors' => 0,
        'total_pageviews' => 0,
        'sessions_by_source' => array(),
        'pageviews_by_source' => array(),
        'sessions_by_month' => array(),
        'sessions_by_day' => array(),
        'events' => array(),
        'view_count' => 0,
        'add_to_cart_count' => 0,
        'checkout_count' => 0,
        'devices' => array(),
    );
}

/**
 * Lấy đơn hàng theo UTM source
 */
function petshop_get_orders_by_source($start_date, $end_date) {
    global $wpdb;
    $table_events = $wpdb->prefix . 'petshop_events';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_events'") !== $table_events) {
        return array();
    }
    
    // Get confirmed orders by source from events
    $orders_by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT utm_source, COUNT(DISTINCT order_id) as count, SUM(value) as value
         FROM $table_events 
         WHERE event_name = 'purchase' 
         AND created_at BETWEEN %s AND %s
         AND order_id > 0
         GROUP BY utm_source ORDER BY count DESC",
        $start_date . ' 00:00:00', $end_date . ' 23:59:59'
    ), ARRAY_A) ?: array();
    
    return $orders_by_source;
}

// =============================================
// SEED SAMPLE ANALYTICS DATA
// =============================================
function petshop_seed_analytics_data() {
    global $wpdb;
    $table_sessions = $wpdb->prefix . 'petshop_sessions';
    $table_pageviews = $wpdb->prefix . 'petshop_pageviews';
    $table_events = $wpdb->prefix . 'petshop_events';
    
    // Check if already has data
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_sessions");
    if ($count > 100) {
        return "Đã có dữ liệu analytics ($count sessions)";
    }
    
    // Clear existing sample data
    $wpdb->query("TRUNCATE TABLE $table_sessions");
    $wpdb->query("TRUNCATE TABLE $table_pageviews");
    $wpdb->query("TRUNCATE TABLE $table_events");
    
    $utm_sources = array(
        'direct' => 58,
        'google' => 15,
        'facebook' => 11,
        'seo' => 5,
        'affiliate' => 5,
        'email' => 4,
        'others' => 2
    );
    
    $devices = array('desktop' => 60, 'mobile' => 35, 'tablet' => 5);
    $page_types = array('home', 'category', 'product', 'cart', 'checkout', 'page');
    
    // Generate data for last 24 months
    $end_date = strtotime('now');
    $start_date = strtotime('-24 months');
    
    $sessions_created = 0;
    $events_created = 0;
    
    // Monthly session counts - varying traffic pattern
    $monthly_data = array(
        1 => 1000, 2 => 1200, 3 => 1500, 4 => 2000, 5 => 3500, 6 => 5000,
        7 => 8000, 8 => 6000, 9 => 5000, 10 => 7000, 11 => 23000, 12 => 25000,
        13 => 7000, 14 => 9000, 15 => 9000, 16 => 19000, 17 => 9000, 18 => 9000,
        19 => 23000, 20 => 20000, 21 => 18000, 22 => 15000, 23 => 20000, 24 => 23000,
    );
    
    for ($month = 1; $month <= 24; $month++) {
        $month_start = strtotime("+$month months", $start_date);
        $month_end = strtotime('+1 month', $month_start) - 1;
        
        $days_in_month = date('t', $month_start);
        $target_sessions = $monthly_data[$month] ?? rand(5000, 15000);
        $sessions_per_day = ceil($target_sessions / $days_in_month);
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $day_ts = strtotime(date('Y-m-', $month_start) . str_pad($day, 2, '0', STR_PAD_LEFT));
            if ($day_ts > $end_date) break;
            
            // Vary sessions per day
            $daily_sessions = rand(intval($sessions_per_day * 0.7), intval($sessions_per_day * 1.3));
            
            for ($s = 0; $s < $daily_sessions; $s++) {
                $session_id = 's_' . uniqid() . rand(1000, 9999);
                $visitor_id = 'v_' . md5(rand(1, intval($target_sessions * 2.1)));
                
                // Select UTM source based on weights
                $rand = rand(1, 100);
                $cumulative = 0;
                $utm_source = 'direct';
                foreach ($utm_sources as $source => $weight) {
                    $cumulative += $weight;
                    if ($rand <= $cumulative) {
                        $utm_source = $source;
                        break;
                    }
                }
                
                // Select device
                $rand = rand(1, 100);
                $cumulative = 0;
                $device_type = 'desktop';
                foreach ($devices as $device => $weight) {
                    $cumulative += $weight;
                    if ($rand <= $cumulative) {
                        $device_type = $device;
                        break;
                    }
                }
                
                $created_at = date('Y-m-d H:i:s', $day_ts + rand(0, 86399));
                $page_views = rand(1, 8);
                
                // Insert session
                $wpdb->insert($table_sessions, array(
                    'session_id' => $session_id,
                    'visitor_id' => $visitor_id,
                    'user_id' => 0,
                    'utm_source' => $utm_source,
                    'utm_medium' => '',
                    'utm_campaign' => '',
                    'device_type' => $device_type,
                    'browser' => 'Chrome',
                    'ip_address' => long2ip(rand(0, 4294967295)),
                    'referrer' => '',
                    'landing_page' => home_url('/'),
                    'page_views' => $page_views,
                    'events' => 0,
                    'created_at' => $created_at,
                    'updated_at' => $created_at
                ));
                $sessions_created++;
                
                // Generate page views
                for ($pv = 0; $pv < $page_views; $pv++) {
                    $page_type = $page_types[array_rand($page_types)];
                    $wpdb->insert($table_pageviews, array(
                        'session_id' => $session_id,
                        'visitor_id' => $visitor_id,
                        'page_url' => home_url('/' . $page_type),
                        'page_title' => ucfirst($page_type),
                        'page_type' => $page_type,
                        'created_at' => $created_at
                    ));
                }
                
                // Generate events based on funnel
                // View: 82%, Add to cart: 12%, Checkout: 6%
                $rand = rand(1, 100);
                if ($rand <= 82) {
                    $wpdb->insert($table_events, array(
                        'session_id' => $session_id,
                        'visitor_id' => $visitor_id,
                        'event_name' => 'view',
                        'product_id' => rand(1, 50),
                        'utm_source' => $utm_source,
                        'created_at' => $created_at
                    ));
                    $events_created++;
                }
                
                if ($rand <= 12) {
                    $wpdb->insert($table_events, array(
                        'session_id' => $session_id,
                        'visitor_id' => $visitor_id,
                        'event_name' => 'add_to_cart',
                        'product_id' => rand(1, 50),
                        'utm_source' => $utm_source,
                        'created_at' => $created_at
                    ));
                    $events_created++;
                }
                
                if ($rand <= 6) {
                    $wpdb->insert($table_events, array(
                        'session_id' => $session_id,
                        'visitor_id' => $visitor_id,
                        'event_name' => 'checkout',
                        'utm_source' => $utm_source,
                        'created_at' => $created_at
                    ));
                    $events_created++;
                }
            }
        }
    }
    
    return "✅ Đã tạo $sessions_created sessions và $events_created events";
}

// AJAX để seed data
function petshop_ajax_seed_analytics_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền');
    }
    
    $result = petshop_seed_analytics_data();
    wp_send_json_success($result);
}
add_action('wp_ajax_petshop_seed_analytics_data', 'petshop_ajax_seed_analytics_data');
