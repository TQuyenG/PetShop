<?php
/**
 * PetShop CRM - Admin Menu Registration
 * Đăng ký menu admin CRM thống nhất
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// ĐĂNG KÝ MENU CRM CHÍNH - GỘP TẤT CẢ VÀO MỘT NƠI
// =============================================
function petshop_crm_register_admin_menu() {
    // Thêm menu dashboard custom PetShop đứng đầu sidebar
    add_menu_page(
        'PetShop',
        'PetShop',
        'read',
        'petshop-admin-dashboard',
        function() {
            include get_template_directory() . '/admin-dashboard-main.php';
        },
        'dashicons-store',
        1
    );

    // Menu chính CRM
    add_menu_page(
        'PetShop CRM',
        'CRM',
        'manage_options',
        'petshop-crm',
        'petshop_crm_dashboard_page',
        'dashicons-businessman',
        27
    );
    
    // Submenu: Dashboard (trang đầu)
    add_submenu_page(
        'petshop-crm',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'petshop-crm',
        'petshop_crm_dashboard_page'
    );
    
    // Submenu: Khách hàng
    add_submenu_page(
        'petshop-crm',
        'Khách hàng',
        'Khách hàng',
        'manage_options',
        'petshop-crm-customers',
        'petshop_crm_customers_page'
    );
    
    // Submenu: Báo cáo
    add_submenu_page(
        'petshop-crm',
        'Báo cáo',
        'Báo cáo',
        'manage_options',
        'petshop-crm-reports',
        'petshop_crm_reports_page'
    );
    
    // Submenu: Giới thiệu bạn
    add_submenu_page(
        'petshop-crm',
        'Giới thiệu bạn',
        'Giới thiệu bạn',
        'manage_options',
        'petshop-crm-referral',
        'petshop_referral_admin_page'
    );
    
    // Submenu: Cài đặt Referral
    add_submenu_page(
        'petshop-crm',
        'Cài đặt Referral',
        'Cài đặt Referral',
        'manage_options',
        'petshop-crm-referral-settings',
        'petshop_referral_settings_page'
    );
    
    // Submenu: Dữ liệu mẫu
    add_submenu_page(
        'petshop-crm',
        'Tạo dữ liệu mẫu',
        'Dữ liệu mẫu',
        'manage_options',
        'petshop-seed-crm',
        'petshop_seed_crm_page'
    );
}
add_action('admin_menu', 'petshop_crm_register_admin_menu', 25);

// =============================================
// LOAD BOOTSTRAP ICONS CHO ADMIN
// =============================================
function petshop_crm_admin_scripts() {
    $screen = get_current_screen();
    
    // Chỉ load cho các trang CRM
    if (strpos($screen->id, 'petshop-crm') !== false) {
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            array(),
            '1.11.3'
        );
        
        // Chart.js cho dashboard
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '4.4.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'petshop_crm_admin_scripts');
