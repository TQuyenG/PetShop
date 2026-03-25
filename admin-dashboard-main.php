<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();

if (is_admin()) {
    echo '<div style="width:100vw;height:calc(100vh - 32px);background:#f6f7f9;position:fixed;top:32px;left:0;z-index:0;">';
    echo '<div style="max-width:1100px;margin:48px auto;padding:48px 32px;background:#fff;border-radius:24px;box-shadow:0 8px 32px rgba(0,0,0,0.10);position:relative;overflow:hidden;">';
    echo '<div style="position:absolute;top:-60px;right:-60px;width:260px;height:260px;background:linear-gradient(135deg,#ffb347,#ffcc33,#ffecb3);border-radius:50%;opacity:0.13;"></div>';
    echo '<div style="display:flex;align-items:center;gap:24px;margin-bottom:32px;">';
    echo '<img src="' . get_template_directory_uri() . '/assets/images/LogoPetshop.png" alt="PetShop Logo" style="width:96px;height:96px;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.08);object-fit:cover;background:#fff;">';
    echo '<span style="font-size:2.8rem;font-weight:700;color:#ff9800;text-shadow:0 2px 8px #ffe0b2;">PetShop Dashboard</span>';
    echo '</div>';
    echo '<div style="display:flex;gap:32px;flex-wrap:wrap;margin-bottom:40px;">';
    echo '<div style="flex:1;min-width:220px;background:#fff3e0;border-radius:16px;padding:32px 24px;text-align:center;box-shadow:0 2px 8px rgba(255,152,0,0.08);">';
    echo '<i class="bi bi-person-circle" style="font-size:2.6rem;color:#ff9800;margin-bottom:12px;"></i>';
    echo '<div style="font-size:1.4rem;font-weight:600;color:#ff9800;">Chào mừng</div>';
    echo '<div style="font-size:1.2rem;color:#333;margin-top:8px;">' . esc_html($current_user->display_name) . '</div>';
    echo '</div>';
    echo '<div style="flex:2;min-width:320px;background:#fffde7;border-radius:16px;padding:32px 24px;text-align:left;box-shadow:0 2px 8px rgba(255,235,59,0.08);">';
    echo '<i class="bi bi-info-circle" style="font-size:2.2rem;color:#ffb300;margin-bottom:12px;"></i>';
    echo '<div style="font-size:1.2rem;color:#333;font-weight:500;">Đây là trang dashboard giới thiệu dành cho quản trị viên PetShop.</div>';
    echo '<div style="color:#666;margin-top:12px;font-size:1.1rem;">Bạn có thể truy cập các chức năng quản trị từ menu bên trái.</div>';
    echo '</div>';
    echo '</div>';
    echo '<a href="' . home_url() . '" class="button button-primary" style="margin-top:24px;background:#ff9800;border:none;font-size:1.1rem;padding:14px 36px;border-radius:10px;">Về trang chủ</a>';
    echo '</div>';
    echo '</div>';
}
