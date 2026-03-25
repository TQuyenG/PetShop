<?php
/**
 * Template Name: Trang Thông Báo
 * 
 * @package PetShop
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/thong-bao/')));
    exit;
}

$user_id = get_current_user_id();
$current_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
$current_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$view_id = isset($_GET['view']) ? intval($_GET['view']) : 0;

// Xử lý actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize_text_field($_GET['action']);
    $notif_id = intval($_GET['id']);
    
    if ($action === 'read') {
        petshop_mark_notification_read($notif_id, $user_id);
        wp_redirect(remove_query_arg(array('action', 'id')));
        exit;
    } elseif ($action === 'delete' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_notif_' . $notif_id)) {
        petshop_delete_notification($notif_id, $user_id);
        wp_redirect(remove_query_arg(array('action', 'id', '_wpnonce')));
        exit;
    }
}

if (isset($_GET['mark_all_read'])) {
    petshop_mark_all_notifications_read($user_id);
    wp_redirect(remove_query_arg('mark_all_read'));
    exit;
}

if (isset($_GET['delete_all_read']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_all_read')) {
    petshop_delete_all_read_notifications($user_id);
    wp_redirect(remove_query_arg(array('delete_all_read', '_wpnonce')));
    exit;
}

// Nếu đang xem chi tiết 1 thông báo
if ($view_id > 0) {
    $single_notification = petshop_get_notification_by_id($view_id, $user_id);
    if ($single_notification) {
        // Tự động đánh dấu đã đọc
        if (!$single_notification->is_read) {
            petshop_mark_notification_read($view_id, $user_id);
        }
    }
}

// Lấy thông báo
$notifications = petshop_get_notifications($user_id, 100);
$unread_count = petshop_count_unread_notifications($user_id);
$notification_types = function_exists('petshop_get_notification_types') ? petshop_get_notification_types() : array();

// Lọc
if ($current_filter === 'unread') {
    $notifications = array_filter($notifications, function($n) { return !$n->is_read; });
} elseif ($current_filter === 'read') {
    $notifications = array_filter($notifications, function($n) { return $n->is_read; });
}

if ($current_type && $current_type !== 'all') {
    $notifications = array_filter($notifications, function($n) use ($current_type) { 
        return $n->type === $current_type; 
    });
}

// Nhóm theo ngày
$grouped_notifications = array();
foreach ($notifications as $notif) {
    $date = date('Y-m-d', strtotime($notif->created_at));
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($date === $today) {
        $date_label = 'Hôm nay';
    } elseif ($date === $yesterday) {
        $date_label = 'Hôm qua';
    } else {
        $date_label = date_i18n('d/m/Y', strtotime($notif->created_at));
    }
    
    if (!isset($grouped_notifications[$date_label])) {
        $grouped_notifications[$date_label] = array();
    }
    $grouped_notifications[$date_label][] = $notif;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
include_once get_template_directory() . '/inc/admin-sidebar-menu.php';
if (in_array('administrator', $user_roles) || in_array('petshop_manager', $user_roles) || in_array('petshop_staff', $user_roles)) {
    petshop_render_admin_sidebar_menu($user_roles);
    echo '<div style="margin-left:240px;padding:32px 24px;">';
}

get_header();
?>

<style>
.notif-page { padding: 40px 0 80px; background: #f8f9fa; min-height: 70vh; }
.notif-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }

.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}
.notif-header h1 {
    margin: 0;
    font-size: 28px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #333;
}
.notif-header h1 i { color: #EC802B; }
.notif-header .unread-badge {
    background: #dc3545;
    color: #fff;
    font-size: 14px;
    padding: 4px 12px;
    border-radius: 20px;
}
.notif-header-actions { display: flex; gap: 10px; }
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 25px;
    font-size: 13px;
    color: #555;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-action:hover { border-color: #EC802B; color: #EC802B; }
.btn-action.danger:hover { border-color: #dc3545; color: #dc3545; }

.notif-filters {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    align-items: center;
}
.filter-tabs {
    display: flex;
    background: #fff;
    border-radius: 30px;
    padding: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.filter-tab {
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
}
.filter-tab:hover { color: #EC802B; }
.filter-tab.active { background: linear-gradient(135deg, #EC802B, #F5994D); color: #fff; }

.type-filter {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.type-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    font-size: 12px;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}
.type-btn:hover { border-color: #EC802B; }
.type-btn.active { background: #EC802B; border-color: #EC802B; color: #fff; }
.type-btn i { font-size: 14px; }

.notif-date-group { margin-bottom: 25px; }
.notif-date-label {
    font-size: 13px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding-left: 5px;
}

.notif-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}
.notif-item {
    display: flex;
    gap: 15px;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s;
    position: relative;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #FDF8F3; }
.notif-item.unread { background: #FDF8F3; }
.notif-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #EC802B;
}

.notif-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-icon i { font-size: 22px; }

.notif-content { flex: 1; min-width: 0; }
.notif-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.notif-title .unread-dot {
    width: 10px;
    height: 10px;
    background: #EC802B;
    border-radius: 50%;
    flex-shrink: 0;
}
.notif-message {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 10px;
}
.notif-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
.notif-time {
    font-size: 12px;
    color: #999;
    display: flex;
    align-items: center;
    gap: 5px;
}
.notif-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}
.notif-link {
    font-size: 13px;
    color: #EC802B;
    text-decoration: none;
    font-weight: 500;
}
.notif-link:hover { text-decoration: underline; }

.notif-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}
.notif-action-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #e0e0e0;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #888;
    text-decoration: none;
    transition: all 0.2s;
}
.notif-action-btn:hover { border-color: #EC802B; color: #EC802B; }
.notif-action-btn.danger:hover { border-color: #dc3545; color: #dc3545; }

.notif-empty {
    text-align: center;
    padding: 80px 30px;
    background: #fff;
    border-radius: 16px;
}
.notif-empty i { font-size: 60px; color: #ddd; margin-bottom: 20px; }
.notif-empty h3 { color: #888; margin: 0 0 10px; }
.notif-empty p { color: #aaa; margin: 0; }

/* Single Notification Detail */
.notif-detail-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.notif-detail-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #EC802B, #F5994D);
    color: #fff;
}
.notif-detail-header .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 15px;
}
.notif-detail-header .back-link:hover { color: #fff; }
.notif-detail-header h2 { margin: 0; font-size: 22px; }
.notif-detail-header .meta {
    display: flex;
    gap: 20px;
    margin-top: 12px;
    font-size: 13px;
    opacity: 0.9;
}
.notif-detail-body {
    padding: 30px;
}
.notif-detail-body .message {
    font-size: 15px;
    line-height: 1.8;
    color: #444;
}
.notif-detail-body .action-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 25px;
    padding: 14px 28px;
    background: linear-gradient(135deg, #EC802B, #F5994D);
    color: #fff;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    transition: all 0.2s;
}
.notif-detail-body .action-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(236,128,43,0.3);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .notif-header { flex-direction: column; align-items: flex-start; }
    .notif-filters { flex-direction: column; gap: 15px; }
    .filter-tabs { width: 100%; justify-content: center; }
    .type-filter { justify-content: center; }
    .notif-item { flex-wrap: wrap; }
    .notif-actions { flex-direction: row; width: 100%; justify-content: flex-end; margin-top: 10px; }
}
</style>

<div class="notif-page">
    <div class="notif-container">
    
    <?php if ($view_id > 0 && isset($single_notification) && $single_notification): 
        // === XEM CHI TIẾT 1 THÔNG BÁO ===
        $type_info = $notification_types[$single_notification->type] ?? array(
            'label' => 'Thông báo',
            'icon' => 'bi-bell',
            'color' => '#EC802B'
        );
    ?>
        <div class="notif-detail-card">
            <div class="notif-detail-header">
                <a href="<?php echo home_url('/thong-bao/'); ?>" class="back-link">
                    <i class="bi bi-arrow-left"></i> Quay lại danh sách
                </a>
                <h2><?php echo esc_html($single_notification->title); ?></h2>
                <div class="meta">
                    <span><i class="bi bi-clock"></i> <?php echo date_i18n('d/m/Y H:i', strtotime($single_notification->created_at)); ?></span>
                    <span><i class="bi <?php echo $type_info['icon']; ?>"></i> <?php echo $type_info['label']; ?></span>
                </div>
            </div>
            <div class="notif-detail-body">
                <div class="message"><?php echo nl2br(wp_kses_post($single_notification->message)); ?></div>
                
                <?php if (!empty($single_notification->link)): ?>
                <a href="<?php echo esc_url($single_notification->link); ?>" class="action-link">
                    <i class="bi bi-arrow-right-circle"></i> Xem chi tiết
                </a>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- === DANH SÁCH THÔNG BÁO === -->
        
        <!-- Header -->
        <div class="notif-header">
            <h1>
                <i class="bi bi-bell-fill"></i>
                Thông báo
                <?php if ($unread_count > 0): ?>
                <span class="unread-badge"><?php echo $unread_count; ?> chưa đọc</span>
                <?php endif; ?>
            </h1>
            
            <div class="notif-header-actions">
                <?php if ($unread_count > 0): ?>
                <a href="<?php echo add_query_arg('mark_all_read', '1'); ?>" class="btn-action">
                    <i class="bi bi-check-all"></i> Đánh dấu tất cả đã đọc
                </a>
                <?php endif; ?>
                
                <?php 
                $read_count = count(array_filter($notifications ?: array(), function($n) { return $n->is_read; }));
                if ($read_count > 0): 
                ?>
                <a href="<?php echo wp_nonce_url(add_query_arg('delete_all_read', '1'), 'delete_all_read'); ?>" 
                   class="btn-action danger" 
                   onclick="return confirm('Xóa tất cả thông báo đã đọc?')">
                    <i class="bi bi-trash"></i> Xóa đã đọc
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="notif-filters">
            <div class="filter-tabs">
                <a href="<?php echo remove_query_arg('filter'); ?>" 
                   class="filter-tab <?php echo $current_filter === 'all' ? 'active' : ''; ?>">
                    Tất cả
                </a>
                <a href="<?php echo add_query_arg('filter', 'unread'); ?>" 
                   class="filter-tab <?php echo $current_filter === 'unread' ? 'active' : ''; ?>">
                    Chưa đọc <?php if ($unread_count > 0) echo "($unread_count)"; ?>
                </a>
                <a href="<?php echo add_query_arg('filter', 'read'); ?>" 
                   class="filter-tab <?php echo $current_filter === 'read' ? 'active' : ''; ?>">
                    Đã đọc
                </a>
            </div>
            
            <div class="type-filter">
                <a href="<?php echo remove_query_arg('type'); ?>" 
                   class="type-btn <?php echo empty($current_type) ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i> Tất cả loại
                </a>
                <?php foreach ($notification_types as $type_key => $type_info): ?>
                <a href="<?php echo add_query_arg('type', $type_key); ?>" 
                   class="type-btn <?php echo $current_type === $type_key ? 'active' : ''; ?>"
                   style="<?php echo $current_type === $type_key ? "background:{$type_info['color']};border-color:{$type_info['color']};" : ''; ?>">
                    <i class="bi <?php echo $type_info['icon']; ?>" style="<?php echo $current_type !== $type_key ? "color:{$type_info['color']};" : ''; ?>"></i>
                    <?php echo $type_info['label']; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Notifications List -->
        <?php if (empty($grouped_notifications)): ?>
        <div class="notif-empty">
            <i class="bi bi-bell-slash"></i>
            <h3>Không có thông báo nào</h3>
            <p>
                <?php if ($current_filter === 'unread'): ?>
                    Bạn đã đọc tất cả thông báo
                <?php elseif ($current_filter === 'read'): ?>
                    Không có thông báo đã đọc
                <?php else: ?>
                    Bạn sẽ nhận được thông báo khi có hoạt động mới
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
            <?php foreach ($grouped_notifications as $date_label => $notifs): ?>
            <div class="notif-date-group">
                <div class="notif-date-label"><?php echo $date_label; ?></div>
                <div class="notif-card">
                    <?php foreach ($notifs as $notif): 
                        $type_info = $notification_types[$notif->type] ?? array(
                            'label' => 'Thông báo',
                            'icon' => 'bi-bell',
                            'color' => '#EC802B'
                        );
                    ?>
                    <div class="notif-item <?php echo $notif->is_read ? '' : 'unread'; ?>">
                        <div class="notif-icon" style="background: <?php echo $type_info['color']; ?>20;">
                            <i class="bi <?php echo $type_info['icon']; ?>" style="color: <?php echo $type_info['color']; ?>;"></i>
                        </div>
                        
                        <div class="notif-content">
                            <div class="notif-title">
                                <?php echo esc_html($notif->title); ?>
                                <?php if (!$notif->is_read): ?>
                                <span class="unread-dot"></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="notif-message"><?php echo nl2br(esc_html($notif->message)); ?></div>
                            
                            <div class="notif-meta">
                                <span class="notif-time">
                                    <i class="bi bi-clock"></i>
                                    <?php echo human_time_diff(strtotime($notif->created_at), current_time('timestamp')); ?> trước
                                </span>
                                
                                <span class="notif-type-badge" style="background: <?php echo $type_info['color']; ?>20; color: <?php echo $type_info['color']; ?>;">
                                    <i class="bi <?php echo $type_info['icon']; ?>"></i>
                                    <?php echo $type_info['label']; ?>
                                </span>
                                
                                <?php if (!empty($notif->link)): ?>
                                <a href="<?php echo esc_url($notif->link); ?>" class="notif-link">
                                    Xem chi tiết <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="notif-actions">
                            <?php if (!$notif->is_read): ?>
                            <a href="<?php echo add_query_arg(array('action' => 'read', 'id' => $notif->id)); ?>" 
                               class="notif-action-btn" title="Đánh dấu đã đọc">
                                <i class="bi bi-check"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'id' => $notif->id)), 'delete_notif_' . $notif->id); ?>" 
                               class="notif-action-btn danger" 
                               title="Xóa"
                               onclick="return confirm('Xóa thông báo này?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    <?php endif; // End view single vs list ?>
    </div>
</div>

<script>
// Real-time update badge
document.addEventListener('DOMContentLoaded', function() {
    setInterval(function() {
        fetch(window.PETSHOP_USER?.ajaxUrl + '?action=petshop_get_notifications_realtime&last_id=0')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const badge = document.querySelector('.unread-badge');
                    if (badge) {
                        badge.textContent = res.data.unread_count + ' chưa đọc';
                    }
                }
            });
    }, 30000);
});
</script>

<?php get_footer(); ?>
