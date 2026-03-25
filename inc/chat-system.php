<?php
/**
 * PetShop Chat System
 * Hệ thống Chat & Conversation Monitor
 * 
 * Tính năng:
 * - Customer Support Chat: Khách hàng chat với staff
 * - Internal Messaging: Chat nội bộ 1-1 và nhóm
 * - Conversation Monitor: Admin giám sát tất cả cuộc hội thoại
 * 
 * @package PetShop
 */

if (!defined('ABSPATH')) exit;

// =============================================
// CONSTANTS
// =============================================
define('PETSHOP_CHAT_VERSION', '1.0.0');

// Conversation types
define('PETSHOP_CONV_SUPPORT', 'support');    // Khách hàng chat với staff
define('PETSHOP_CONV_DIRECT', 'direct');      // Chat 1-1 nội bộ
define('PETSHOP_CONV_GROUP', 'group');        // Chat nhóm

// Default Staff Group
define('PETSHOP_DEFAULT_STAFF_GROUP', 'Nhóm nhân viên');  // Tên nhóm mặc định

// Conversation statuses
define('PETSHOP_CONV_OPEN', 'open');          // Chưa có ai nhận
define('PETSHOP_CONV_ASSIGNED', 'assigned');  // Đã có staff nhận
define('PETSHOP_CONV_CLOSED', 'closed');      // Đã đóng/giải quyết

// Message types  
define('PETSHOP_MSG_TEXT', 'text');
define('PETSHOP_MSG_IMAGE', 'image');
define('PETSHOP_MSG_FILE', 'file');
define('PETSHOP_MSG_SYSTEM', 'system');       // Tin nhắn hệ thống

// =============================================
// DATABASE TABLES
// =============================================
function petshop_chat_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table 1: Conversations
    $table_conversations = $wpdb->prefix . 'petshop_conversations';
    $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type VARCHAR(20) NOT NULL DEFAULT 'support',
        title VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        customer_id BIGINT(20) UNSIGNED DEFAULT NULL,
        assigned_to BIGINT(20) UNSIGNED DEFAULT NULL,
        assigned_at DATETIME DEFAULT NULL,
        priority VARCHAR(20) DEFAULT 'normal',
        tags TEXT DEFAULT NULL,
        is_starred TINYINT(1) DEFAULT 0,
        last_message_id BIGINT(20) UNSIGNED DEFAULT NULL,
        last_message_at DATETIME DEFAULT NULL,
        created_by BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        closed_at DATETIME DEFAULT NULL,
        closed_by BIGINT(20) UNSIGNED DEFAULT NULL,
        PRIMARY KEY (id),
        KEY type (type),
        KEY status (status),
        KEY customer_id (customer_id),
        KEY assigned_to (assigned_to),
        KEY created_at (created_at),
        KEY last_message_at (last_message_at),
        KEY is_starred (is_starred)
    ) $charset_collate;";
    
    // Table 2: Messages
    $table_messages = $wpdb->prefix . 'petshop_chat_messages';
    $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT(20) UNSIGNED NOT NULL,
        sender_id BIGINT(20) UNSIGNED NOT NULL,
        sender_type VARCHAR(20) NOT NULL DEFAULT 'user',
        message_type VARCHAR(20) NOT NULL DEFAULT 'text',
        content TEXT NOT NULL,
        attachment_url VARCHAR(500) DEFAULT NULL,
        attachment_name VARCHAR(255) DEFAULT NULL,
        reply_to_id BIGINT(20) UNSIGNED DEFAULT NULL,
        reactions TEXT DEFAULT NULL,
        is_pinned TINYINT(1) DEFAULT 0,
        is_deleted TINYINT(1) DEFAULT 0,
        is_edited TINYINT(1) DEFAULT 0,
        read_by TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id),
        KEY sender_id (sender_id),
        KEY created_at (created_at),
        KEY is_pinned (is_pinned)
    ) $charset_collate;";
    
    // Table 3: Conversation Participants
    $table_participants = $wpdb->prefix . 'petshop_conversation_participants';
    $sql_participants = "CREATE TABLE IF NOT EXISTS $table_participants (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'member',
        is_muted TINYINT(1) DEFAULT 0,
        last_read_at DATETIME DEFAULT NULL,
        last_read_message_id BIGINT(20) UNSIGNED DEFAULT NULL,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        left_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY conv_user (conversation_id, user_id),
        KEY user_id (user_id),
        KEY conversation_id (conversation_id)
    ) $charset_collate;";
    
    // Table 4: Typing indicators & online status (optional but useful)
    $table_presence = $wpdb->prefix . 'petshop_chat_presence';
    $sql_presence = "CREATE TABLE IF NOT EXISTS $table_presence (
        user_id BIGINT(20) UNSIGNED NOT NULL,
        conversation_id BIGINT(20) UNSIGNED DEFAULT NULL,
        is_typing TINYINT(1) DEFAULT 0,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        KEY conversation_id (conversation_id),
        KEY last_seen (last_seen)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_conversations);
    dbDelta($sql_messages);
    dbDelta($sql_participants);
    dbDelta($sql_presence);
    
    // Migration: Thêm cột is_starred nếu chưa có
    $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_conversations LIKE 'is_starred'");
    if (empty($col_exists)) {
        $wpdb->query("ALTER TABLE $table_conversations ADD COLUMN is_starred TINYINT(1) DEFAULT 0");
        $wpdb->query("ALTER TABLE $table_conversations ADD INDEX is_starred (is_starred)");
    }
    
    // Migration: Thêm các cột messenger features cho messages
    $columns_to_add = array(
        'reactions' => 'TEXT DEFAULT NULL',
        'is_pinned' => 'TINYINT(1) DEFAULT 0',
        'is_deleted' => 'TINYINT(1) DEFAULT 0',
        'is_edited' => 'TINYINT(1) DEFAULT 0',
        'reply_to_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL',
    );
    
    foreach ($columns_to_add as $col_name => $col_def) {
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_messages LIKE '$col_name'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $table_messages ADD COLUMN $col_name $col_def");
            if ($col_name === 'is_pinned') {
                $wpdb->query("ALTER TABLE $table_messages ADD INDEX is_pinned (is_pinned)");
            }
        }
    }
    
    // Tạo nhóm chat mặc định "Tất cả nhân viên" nếu chưa có
    petshop_create_default_staff_group();
}
add_action('after_switch_theme', 'petshop_chat_create_tables');
add_action('admin_init', 'petshop_chat_create_tables');

// Tạo nhóm chat mặc định cho staff
function petshop_create_default_staff_group() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    // Kiểm tra đã có nhóm chung chưa
    $existing = $wpdb->get_var("SELECT id FROM $table WHERE type = 'group' AND title = 'Nhóm nhân viên' LIMIT 1");
    
    if (!$existing) {
        // Tạo nhóm mới
        $admin_id = 1; // Admin ID mặc định
        $admins = get_users(array('role' => 'administrator', 'number' => 1));
        if (!empty($admins)) {
            $admin_id = $admins[0]->ID;
        }
        
        $wpdb->insert($table, array(
            'type' => PETSHOP_CONV_GROUP,
            'title' => 'Nhóm nhân viên',
            'status' => 'open',
            'created_by' => $admin_id,
            'created_at' => current_time('mysql'),
        ));
        
        $group_id = $wpdb->insert_id;
        
        if ($group_id) {
            // Thêm tất cả admin, manager, staff vào nhóm
            $staff_users = get_users(array(
                'role__in' => array('administrator', 'petshop_manager', 'petshop_staff'),
            ));
            
            $table_participants = $wpdb->prefix . 'petshop_conversation_participants';
            foreach ($staff_users as $user) {
                $role = 'member';
                if (in_array('administrator', $user->roles)) {
                    $role = 'admin';
                } elseif (in_array('petshop_manager', $user->roles)) {
                    $role = 'admin';
                }
                
                $wpdb->insert($table_participants, array(
                    'conversation_id' => $group_id,
                    'user_id' => $user->ID,
                    'role' => $role,
                    'joined_at' => current_time('mysql'),
                ));
            }
            
            // Tin nhắn chào mừng
            petshop_send_system_message($group_id, 'Chào mừng đến với nhóm chat nhân viên PetShop! 🎉');
        }
    } else {
        // Đảm bảo tất cả staff đều có trong nhóm
        petshop_ensure_staff_in_default_group($existing);
    }
}

/**
 * Lấy ID của nhóm nhân viên mặc định
 */
function petshop_get_default_staff_group_id() {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE type = 'group' AND title = %s LIMIT 1",
        PETSHOP_DEFAULT_STAFF_GROUP
    ));
}

/**
 * Kiểm tra conversation có phải nhóm nhân viên mặc định không
 */
function petshop_is_default_staff_group($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    $title = $wpdb->get_var($wpdb->prepare(
        "SELECT title FROM $table WHERE id = %d AND type = 'group'",
        $conversation_id
    ));
    return $title === PETSHOP_DEFAULT_STAFF_GROUP;
}

/**
 * Đảm bảo tất cả staff đều có trong nhóm mặc định
 */
function petshop_ensure_staff_in_default_group($group_id) {
    global $wpdb;
    $table_participants = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Lấy tất cả staff users
    $staff_users = get_users(array(
        'role__in' => array('administrator', 'petshop_manager', 'petshop_staff'),
    ));
    
    foreach ($staff_users as $user) {
        // Kiểm tra đã có trong nhóm chưa
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_participants WHERE conversation_id = %d AND user_id = %d",
            $group_id, $user->ID
        ));
        
        if (!$exists) {
            $role = 'member';
            if (in_array('administrator', $user->roles) || in_array('petshop_manager', $user->roles)) {
                $role = 'admin';
            }
            
            $wpdb->insert($table_participants, array(
                'conversation_id' => $group_id,
                'user_id' => $user->ID,
                'role' => $role,
                'joined_at' => current_time('mysql'),
            ));
        }
    }
}

/**
 * Hook khi user mới được tạo - tự động thêm vào nhóm nhân viên nếu là staff
 */
add_action('user_register', 'petshop_auto_add_staff_to_default_group');
add_action('set_user_role', 'petshop_on_user_role_change', 10, 3);

function petshop_auto_add_staff_to_default_group($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return;
    
    // Chỉ thêm nếu là staff role
    $staff_roles = array('administrator', 'petshop_manager', 'petshop_staff');
    $is_staff = !empty(array_intersect($staff_roles, $user->roles));
    
    if ($is_staff) {
        $group_id = petshop_get_default_staff_group_id();
        if ($group_id) {
            global $wpdb;
            $table_participants = $wpdb->prefix . 'petshop_conversation_participants';
            
            // Kiểm tra đã có chưa
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_participants WHERE conversation_id = %d AND user_id = %d",
                $group_id, $user_id
            ));
            
            if (!$exists) {
                $role = (in_array('administrator', $user->roles) || in_array('petshop_manager', $user->roles)) ? 'admin' : 'member';
                $wpdb->insert($table_participants, array(
                    'conversation_id' => $group_id,
                    'user_id' => $user_id,
                    'role' => $role,
                    'joined_at' => current_time('mysql'),
                ));
            }
        }
    }
}

function petshop_on_user_role_change($user_id, $new_role, $old_roles) {
    petshop_auto_add_staff_to_default_group($user_id);
}

// =============================================
// PERMISSIONS & ACCESS CONTROL
// =============================================

/**
 * Kiểm tra user có quyền truy cập chat system không
 */
function petshop_can_access_chat($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    // Tất cả user đã đăng nhập đều có thể chat
    return true;
}

/**
 * Kiểm tra user có phải staff (có thể nhận chat support) không
 */
function petshop_is_chat_staff($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $staff_roles = array('administrator', 'petshop_manager', 'petshop_staff');
    return !empty(array_intersect($staff_roles, $user->roles));
}

/**
 * Kiểm tra user có quyền monitor (xem tất cả chat) không
 */
function petshop_can_monitor_chat($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    // Chỉ Admin và Manager có quyền monitor
    $monitor_roles = array('administrator', 'petshop_manager');
    return !empty(array_intersect($monitor_roles, $user->roles));
}

/**
 * Kiểm tra user có quyền truy cập conversation cụ thể không
 */
function petshop_can_access_conversation($conversation_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    // Admin/Manager có thể xem tất cả (monitor mode)
    if (petshop_can_monitor_chat($user_id)) {
        return true;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Kiểm tra có phải participant không
    $is_participant = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $user_id
    ));
    
    if ($is_participant) return true;
    
    // Kiểm tra có phải customer của conversation support không
    $table_conv = $wpdb->prefix . 'petshop_conversations';
    $conv = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_conv WHERE id = %d",
        $conversation_id
    ));
    
    if ($conv && $conv->type === PETSHOP_CONV_SUPPORT && $conv->customer_id == $user_id) {
        return true;
    }
    
    return false;
}

/**
 * Kiểm tra user có thể gửi tin nhắn vào conversation không
 */
function petshop_can_send_message($conversation_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    if (!petshop_can_access_conversation($conversation_id, $user_id)) {
        return false;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $conversation_id));
    
    if (!$conv) return false;
    
    // Conversation đã đóng -> không gửi được
    if ($conv->status === PETSHOP_CONV_CLOSED) {
        return false;
    }
    
    // Support chat - Xử lý quyền gửi
    if ($conv->type === PETSHOP_CONV_SUPPORT) {
        // Customer luôn gửi được (nếu chưa đóng)
        if ($conv->customer_id == $user_id) {
            return true;
        }
        
        // Staff phải nhận cuộc hội thoại mới được gửi
        if (petshop_is_chat_staff($user_id)) {
            // Chưa có ai nhận (open) -> không ai được gửi (phải nhấn "Nhận trả lời" trước)
            if ($conv->status === PETSHOP_CONV_OPEN) {
                return false;
            }
            // Đã có người nhận (assigned) -> chỉ người đó được gửi
            if ($conv->status === PETSHOP_CONV_ASSIGNED) {
                return ($conv->assigned_to == $user_id);
            }
        }
        
        return false;
    }
    
    // Group/Direct chat - kiểm tra participant
    return true;
}

// =============================================
// CONVERSATION FUNCTIONS
// =============================================

/**
 * Tạo conversation mới
 */
function petshop_create_conversation($args) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    $defaults = array(
        'type' => PETSHOP_CONV_SUPPORT,
        'title' => null,
        'status' => PETSHOP_CONV_OPEN,
        'customer_id' => null,
        'created_by' => get_current_user_id(),
        'priority' => 'normal',
    );
    
    $data = wp_parse_args($args, $defaults);
    
    // Auto-generate title for support chat
    if ($data['type'] === PETSHOP_CONV_SUPPORT && empty($data['title'])) {
        $customer = get_userdata($data['customer_id'] ?: $data['created_by']);
        $data['title'] = 'Hỗ trợ: ' . ($customer ? $customer->display_name : 'Khách #' . $data['created_by']);
    }
    
    $result = $wpdb->insert($table, array(
        'type' => $data['type'],
        'title' => $data['title'],
        'status' => $data['status'],
        'customer_id' => $data['customer_id'],
        'priority' => $data['priority'],
        'created_by' => $data['created_by'],
        'created_at' => current_time('mysql'),
    ));
    
    if (!$result) return false;
    
    $conversation_id = $wpdb->insert_id;
    
    // Thêm creator vào participants
    petshop_add_participant($conversation_id, $data['created_by'], 'admin');
    
    // Nếu là support chat, thông báo cho tất cả staff
    if ($data['type'] === PETSHOP_CONV_SUPPORT) {
        petshop_notify_new_support_chat($conversation_id);
    }
    
    return $conversation_id;
}

/**
 * Lấy thông tin conversation
 */
function petshop_get_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    $conv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $conversation_id));
    
    if ($conv) {
        // Thêm thông tin bổ sung cho conversation
        $conv->is_starred = (bool) ($conv->is_starred ?? 0);
        
        // Customer info
        if ($conv->customer_id) {
            $customer = get_userdata($conv->customer_id);
            if ($customer) {
                $conv->customer_name = $customer->display_name;
                $conv->customer_email = $customer->user_email;
                $conv->customer_avatar = get_avatar_url($conv->customer_id, array('size' => 40));
            }
        }
        
        // Assigned staff info
        if ($conv->assigned_to) {
            $staff = get_userdata($conv->assigned_to);
            $conv->assigned_name = $staff ? $staff->display_name : 'Nhân viên #' . $conv->assigned_to;
            $conv->assigned_avatar = $staff ? get_avatar_url($conv->assigned_to, array('size' => 40)) : '';
        } else {
            $conv->assigned_name = '';
            $conv->assigned_avatar = '';
        }
    }
    
    return $conv;
}

/**
 * Lấy danh sách conversations của user
 */
function petshop_get_user_conversations($user_id = null, $type = null, $limit = 50) {
    if (!$user_id) $user_id = get_current_user_id();
    
    global $wpdb;
    $table_conv = $wpdb->prefix . 'petshop_conversations';
    $table_part = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Lấy ID của nhóm nhân viên mặc định
    $default_staff_group_id = petshop_get_default_staff_group_id();
    
    // Build type filter
    $type_filter = '';
    if ($type) {
        $type_filter = $wpdb->prepare(" AND c.type = %s", $type);
    }
    
    // Khởi tạo results
    $results = array();
    $found_default_group = false;
    
    // Nếu là staff, cũng hiển thị support chats đang open (chưa assign)
    if (petshop_is_chat_staff($user_id)) {
        if ($type === 'support') {
            // Chỉ lấy support conversations
            $sql = $wpdb->prepare(
                "SELECT DISTINCT c.* FROM $table_conv c
                LEFT JOIN $table_part p ON c.id = p.conversation_id
                WHERE c.type = 'support' AND (
                    (p.user_id = %d AND p.left_at IS NULL)
                    OR c.status = 'open'
                )
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT %d",
                $user_id, $limit
            );
        } elseif ($type === 'group') {
            // Chỉ lấy group conversations
            $sql = $wpdb->prepare(
                "SELECT DISTINCT c.* FROM $table_conv c
                INNER JOIN $table_part p ON c.id = p.conversation_id
                WHERE c.type = 'group' AND p.user_id = %d AND p.left_at IS NULL
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT %d",
                $user_id, $limit
            );
        } elseif ($type === 'direct') {
            // Chỉ lấy direct conversations
            $sql = $wpdb->prepare(
                "SELECT DISTINCT c.* FROM $table_conv c
                INNER JOIN $table_part p ON c.id = p.conversation_id
                WHERE c.type = 'direct' AND p.user_id = %d AND p.left_at IS NULL
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT %d",
                $user_id, $limit
            );
        } else {
            // Lấy tất cả
            $sql = $wpdb->prepare(
                "SELECT DISTINCT c.* FROM $table_conv c
                LEFT JOIN $table_part p ON c.id = p.conversation_id
                WHERE (p.user_id = %d AND p.left_at IS NULL)
                   OR (c.type = 'support' AND c.status = 'open')
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT %d",
                $user_id, $limit
            );
        }
        
        $conversations = $wpdb->get_results($sql);
        
        // Nếu là staff, luôn thêm nhóm nhân viên mặc định vào đầu danh sách (nếu chưa có)
        if ($default_staff_group_id) {
            // Kiểm tra user có trong nhóm không
            $is_member = $wpdb->get_var($wpdb->prepare(
                "SELECT 1 FROM $table_part WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
                $default_staff_group_id, $user_id
            ));
            
            if ($is_member) {
                // Lấy thông tin nhóm mặc định
                $default_group = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_conv WHERE id = %d",
                    $default_staff_group_id
                ));
                
                if ($default_group) {
                    // Thêm nhóm mặc định vào đầu và loại bỏ nếu trùng
                    $results[] = $default_group;
                    $found_default_group = true;
                }
            }
        }
        
        // Thêm các conversation khác (loại bỏ nhóm mặc định nếu đã thêm)
        foreach ($conversations as $conv) {
            if ($found_default_group && $conv->id == $default_staff_group_id) {
                continue;
            }
            $results[] = $conv;
        }
        
        return $results;
    } else {
        // Customer - chỉ lấy conversation của họ
        $sql = $wpdb->prepare(
            "SELECT c.* FROM $table_conv c
            INNER JOIN $table_part p ON c.id = p.conversation_id
            WHERE p.user_id = %d AND p.left_at IS NULL $type_filter
            ORDER BY c.last_message_at DESC, c.created_at DESC
            LIMIT %d",
            $user_id, $limit
        );
        
        return $wpdb->get_results($sql);
    }
}

/**
 * Staff nhận cuộc hội thoại support
 */
function petshop_assign_conversation($conversation_id, $staff_id = null) {
    if (!$staff_id) $staff_id = get_current_user_id();
    
    if (!petshop_is_chat_staff($staff_id)) {
        return new WP_Error('no_permission', 'Bạn không có quyền nhận cuộc hội thoại này');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    $conv = petshop_get_conversation($conversation_id);
    if (!$conv) {
        return new WP_Error('not_found', 'Không tìm thấy cuộc hội thoại');
    }
    
    if ($conv->type !== PETSHOP_CONV_SUPPORT) {
        return new WP_Error('invalid_type', 'Chỉ có thể assign cuộc hội thoại hỗ trợ');
    }
    
    if ($conv->status === PETSHOP_CONV_ASSIGNED && $conv->assigned_to != $staff_id) {
        $assigned_user = get_userdata($conv->assigned_to);
        return new WP_Error('already_assigned', 'Cuộc hội thoại đã được ' . ($assigned_user ? $assigned_user->display_name : 'nhân viên khác') . ' nhận');
    }
    
    $result = $wpdb->update($table, array(
        'status' => PETSHOP_CONV_ASSIGNED,
        'assigned_to' => $staff_id,
        'assigned_at' => current_time('mysql'),
    ), array('id' => $conversation_id));
    
    if ($result !== false) {
        // Thêm staff vào participants
        petshop_add_participant($conversation_id, $staff_id, 'staff');
        
        // Gửi tin nhắn hệ thống
        $staff = get_userdata($staff_id);
        petshop_send_system_message($conversation_id, $staff->display_name . ' đã tham gia cuộc trò chuyện');
        
        return true;
    }
    
    return new WP_Error('update_failed', 'Không thể cập nhật cuộc hội thoại');
}

/**
 * Staff rời cuộc hội thoại (im lặng, không thông báo khách)
 */
function petshop_unassign_conversation($conversation_id, $staff_id = null, $close = false) {
    if (!$staff_id) $staff_id = get_current_user_id();
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    $conv = petshop_get_conversation($conversation_id);
    if (!$conv) {
        return new WP_Error('not_found', 'Không tìm thấy cuộc hội thoại');
    }
    
    // Chỉ người được assign hoặc admin mới có thể unassign
    if ($conv->assigned_to != $staff_id && !petshop_can_monitor_chat($staff_id)) {
        return new WP_Error('no_permission', 'Bạn không có quyền thực hiện thao tác này');
    }
    
    // Không đóng, chỉ bỏ assign để nhân viên khác nhận
    $update_data = array(
        'assigned_to' => null,
        'assigned_at' => null,
        'status' => PETSHOP_CONV_OPEN, // Giữ open để ai cũng có thể nhận
    );
    
    $result = $wpdb->update($table, $update_data, array('id' => $conversation_id));
    
    // Không gửi thông báo cho khách hàng, chỉ chuyển im lặng
    return $result !== false;
}

/**
 * Toggle star (đánh dấu quan trọng) cho conversation
 */
function petshop_toggle_conversation_star($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    // Lấy trạng thái hiện tại
    $current = $wpdb->get_var($wpdb->prepare(
        "SELECT is_starred FROM $table WHERE id = %d",
        $conversation_id
    ));
    
    $new_value = $current ? 0 : 1;
    return $wpdb->update($table, array('is_starred' => $new_value), array('id' => $conversation_id));
}

/**
 * Mở lại conversation đã đóng
 */
function petshop_reopen_conversation($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    return $wpdb->update($table, array(
        'status' => PETSHOP_CONV_OPEN,
        'closed_at' => null,
        'closed_by' => null,
    ), array('id' => $conversation_id));
}

// =============================================
// PARTICIPANT FUNCTIONS
// =============================================

/**
 * Thêm user vào conversation
 */
function petshop_add_participant($conversation_id, $user_id, $role = 'member') {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Kiểm tra đã có chưa
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE conversation_id = %d AND user_id = %d",
        $conversation_id, $user_id
    ));
    
    if ($existing) {
        // Nếu đã rời thì cho join lại
        if ($existing->left_at) {
            return $wpdb->update($table, array(
                'left_at' => null,
                'role' => $role,
                'joined_at' => current_time('mysql'),
            ), array('id' => $existing->id));
        }
        return true; // Đã là thành viên
    }
    
    return $wpdb->insert($table, array(
        'conversation_id' => $conversation_id,
        'user_id' => $user_id,
        'role' => $role,
        'joined_at' => current_time('mysql'),
    ));
}

/**
 * Xóa user khỏi conversation
 */
function petshop_remove_participant($conversation_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversation_participants';
    
    return $wpdb->update($table, array(
        'left_at' => current_time('mysql'),
    ), array(
        'conversation_id' => $conversation_id,
        'user_id' => $user_id,
    ));
}

/**
 * Lấy danh sách participants của conversation
 */
function petshop_get_participants($conversation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversation_participants';
    
    $participants = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE conversation_id = %d AND left_at IS NULL ORDER BY joined_at ASC",
        $conversation_id
    ));
    
    // Thêm thông tin user
    foreach ($participants as &$p) {
        $user = get_userdata($p->user_id);
        $p->user_name = $user ? $user->display_name : 'Unknown';
        $p->user_email = $user ? $user->user_email : '';
        $p->avatar_url = get_avatar_url($p->user_id, array('size' => 50));
    }
    
    return $participants;
}

// =============================================
// MESSAGE FUNCTIONS
// =============================================

/**
 * Gửi tin nhắn
 */
function petshop_send_message($conversation_id, $content, $type = PETSHOP_MSG_TEXT, $attachment = null, $reply_to = null) {
    $user_id = get_current_user_id();
    
    if (!petshop_can_send_message($conversation_id, $user_id)) {
        return new WP_Error('no_permission', 'Bạn không thể gửi tin nhắn vào cuộc hội thoại này');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $sender_type = petshop_is_chat_staff($user_id) ? 'staff' : 'customer';
    
    $message_data = array(
        'conversation_id' => $conversation_id,
        'sender_id' => $user_id,
        'sender_type' => $sender_type,
        'message_type' => $type,
        'content' => wp_kses_post($content),
        'created_at' => current_time('mysql'),
    );
    
    if ($attachment) {
        $message_data['attachment_url'] = $attachment['url'];
        $message_data['attachment_name'] = $attachment['name'];
    }
    
    if ($reply_to) {
        $message_data['reply_to_id'] = $reply_to;
    }
    
    $result = $wpdb->insert($table, $message_data);
    
    if (!$result) {
        return new WP_Error('insert_failed', 'Không thể gửi tin nhắn');
    }
    
    $message_id = $wpdb->insert_id;
    
    // Cập nhật last_message cho conversation
    $table_conv = $wpdb->prefix . 'petshop_conversations';
    $wpdb->update($table_conv, array(
        'last_message_id' => $message_id,
        'last_message_at' => current_time('mysql'),
    ), array('id' => $conversation_id));
    
    // Đánh dấu người gửi đã đọc
    petshop_mark_conversation_read($conversation_id, $user_id, $message_id);
    
    // Thông báo cho participants khác
    petshop_notify_new_message($conversation_id, $message_id);
    
    return $message_id;
}

/**
 * Gửi tin nhắn hệ thống
 */
function petshop_send_system_message($conversation_id, $content) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $wpdb->insert($table, array(
        'conversation_id' => $conversation_id,
        'sender_id' => 0,
        'sender_type' => 'system',
        'message_type' => PETSHOP_MSG_SYSTEM,
        'content' => $content,
        'created_at' => current_time('mysql'),
    ));
    
    $message_id = $wpdb->insert_id;
    
    // Cập nhật last_message
    $table_conv = $wpdb->prefix . 'petshop_conversations';
    $wpdb->update($table_conv, array(
        'last_message_id' => $message_id,
        'last_message_at' => current_time('mysql'),
    ), array('id' => $conversation_id));
    
    return $message_id;
}

/**
 * Lấy tin nhắn của conversation
 */
function petshop_get_messages($conversation_id, $limit = 50, $before_id = null, $after_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $where = "conversation_id = %d";
    $params = array($conversation_id);
    
    if ($before_id) {
        $where .= " AND id < %d";
        $params[] = $before_id;
    }
    
    if ($after_id) {
        $where .= " AND id > %d";
        $params[] = $after_id;
    }
    
    $params[] = $limit;
    
    $order = $after_id ? 'ASC' : 'DESC';
    
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE $where ORDER BY id $order LIMIT %d",
        ...$params
    ));
    
    // Nếu query DESC thì reverse lại để hiển thị đúng thứ tự
    if (!$after_id) {
        $messages = array_reverse($messages);
    }
    
    // Thêm thông tin sender
    foreach ($messages as &$msg) {
        // Cast boolean fields  
        $msg->is_deleted = (bool) ($msg->is_deleted ?? 0);
        $msg->is_pinned = (bool) ($msg->is_pinned ?? 0);
        $msg->is_edited = (bool) ($msg->is_edited ?? 0);
        
        if ($msg->sender_id > 0) {
            $sender = get_userdata($msg->sender_id);
            $msg->sender_name = $sender ? $sender->display_name : 'Unknown';
            $msg->sender_avatar = get_avatar_url($msg->sender_id, array('size' => 40));
        } else {
            $msg->sender_name = 'Hệ thống';
            $msg->sender_avatar = '';
        }
        
        // Đảm bảo id là integer
        $msg->id = intval($msg->id);
        
        // Thêm timestamp (milliseconds)
        $msg->timestamp = strtotime($msg->created_at) * 1000;
        
        // Reply info
        if ($msg->reply_to_id) {
            $reply_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT id, content, sender_id FROM $table WHERE id = %d",
                $msg->reply_to_id
            ));
            if ($reply_msg) {
                $reply_sender = get_userdata($reply_msg->sender_id);
                $msg->reply_to = array(
                    'id' => intval($reply_msg->id),
                    'content' => wp_trim_words($reply_msg->content, 10),
                    'sender_name' => $reply_sender ? $reply_sender->display_name : 'Unknown',
                );
            }
        }
    }
    
    return $messages;
}

/**
 * Đánh dấu đã đọc
 */
function petshop_mark_conversation_read($conversation_id, $user_id = null, $message_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversation_participants';
    
    $update_data = array('last_read_at' => current_time('mysql'));
    
    if ($message_id) {
        $update_data['last_read_message_id'] = $message_id;
    }
    
    return $wpdb->update($table, $update_data, array(
        'conversation_id' => $conversation_id,
        'user_id' => $user_id,
    ));
}

/**
 * Đếm tin nhắn chưa đọc
 */
function petshop_count_unread_messages($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    
    global $wpdb;
    $table_msg = $wpdb->prefix . 'petshop_chat_messages';
    $table_part = $wpdb->prefix . 'petshop_conversation_participants';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_msg m
        INNER JOIN $table_part p ON m.conversation_id = p.conversation_id
        WHERE p.user_id = %d 
          AND p.left_at IS NULL
          AND m.sender_id != %d
          AND m.is_deleted = 0
          AND (p.last_read_message_id IS NULL OR m.id > p.last_read_message_id)",
        $user_id, $user_id
    ));
}

/**
 * Đếm tin nhắn chưa đọc trong một conversation cụ thể
 */
function petshop_count_unread_in_conversation($conversation_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    
    global $wpdb;
    $table_msg = $wpdb->prefix . 'petshop_chat_messages';
    $table_part = $wpdb->prefix . 'petshop_conversation_participants';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_msg m
        INNER JOIN $table_part p ON m.conversation_id = p.conversation_id
        WHERE p.conversation_id = %d 
          AND p.user_id = %d 
          AND p.left_at IS NULL
          AND m.sender_id != %d
          AND m.is_deleted = 0
          AND (p.last_read_message_id IS NULL OR m.id > p.last_read_message_id)",
        $conversation_id, $user_id, $user_id
    ));
}

// =============================================
// NOTIFICATION FUNCTIONS
// =============================================

/**
 * Thông báo cho staff có chat support mới
 */
function petshop_notify_new_support_chat($conversation_id) {
    $conv = petshop_get_conversation($conversation_id);
    if (!$conv) return;
    
    $customer = get_userdata($conv->customer_id ?: $conv->created_by);
    $customer_name = $customer ? $customer->display_name : 'Khách hàng';
    
    // Lấy tất cả staff
    $staff_users = get_users(array(
        'role__in' => array('administrator', 'petshop_manager', 'petshop_staff'),
    ));
    
    foreach ($staff_users as $staff) {
        // Sử dụng hệ thống notification có sẵn
        if (function_exists('petshop_add_notification')) {
            petshop_add_notification(array(
                'user_id' => $staff->ID,
                'type' => 'chat_support',
                'title' => '💬 Yêu cầu hỗ trợ mới',
                'message' => $customer_name . ' cần được hỗ trợ',
                'link' => admin_url('admin.php?page=petshop-chat&conv=' . $conversation_id),
                'icon' => 'bi-chat-dots',
                'color' => '#17a2b8',
            ));
        }
    }
}

/**
 * Thông báo tin nhắn mới
 */
function petshop_notify_new_message($conversation_id, $message_id) {
    global $wpdb;
    $table_msg = $wpdb->prefix . 'petshop_chat_messages';
    
    $message = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_msg WHERE id = %d", $message_id));
    if (!$message || $message->message_type === PETSHOP_MSG_SYSTEM) return;
    
    $sender = get_userdata($message->sender_id);
    $sender_name = $sender ? $sender->display_name : 'Ai đó';
    
    $participants = petshop_get_participants($conversation_id);
    
    foreach ($participants as $p) {
        // Không thông báo cho người gửi
        if ($p->user_id == $message->sender_id) continue;
        
        if (function_exists('petshop_add_notification')) {
            petshop_add_notification(array(
                'user_id' => $p->user_id,
                'type' => 'chat_message',
                'title' => '💬 Tin nhắn mới từ ' . $sender_name,
                'message' => wp_trim_words(strip_tags($message->content), 10),
                'link' => admin_url('admin.php?page=petshop-chat&conv=' . $conversation_id),
                'icon' => 'bi-chat-text',
                'color' => '#28a745',
            ));
        }
    }
}

// =============================================
// GROUP CHAT FUNCTIONS
// =============================================

/**
 * Tạo nhóm chat mới
 */
function petshop_create_group_chat($title, $member_ids = array()) {
    $creator_id = get_current_user_id();
    
    if (!petshop_is_chat_staff($creator_id)) {
        return new WP_Error('no_permission', 'Chỉ nhân viên mới có thể tạo nhóm chat');
    }
    
    $conversation_id = petshop_create_conversation(array(
        'type' => PETSHOP_CONV_GROUP,
        'title' => $title,
        'created_by' => $creator_id,
    ));
    
    if (!$conversation_id) {
        return new WP_Error('create_failed', 'Không thể tạo nhóm');
    }
    
    // Thêm các thành viên
    foreach ($member_ids as $member_id) {
        if ($member_id != $creator_id) {
            petshop_add_participant($conversation_id, $member_id, 'member');
        }
    }
    
    petshop_send_system_message($conversation_id, 'Nhóm "' . $title . '" đã được tạo');
    
    return $conversation_id;
}

/**
 * Đổi tên nhóm
 */
function petshop_rename_group($conversation_id, $new_title) {
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    
    $conv = petshop_get_conversation($conversation_id);
    if (!$conv || $conv->type !== PETSHOP_CONV_GROUP) {
        return new WP_Error('invalid', 'Không tìm thấy nhóm');
    }
    
    $old_title = $conv->title;
    
    $result = $wpdb->update($table, array('title' => $new_title), array('id' => $conversation_id));
    
    if ($result !== false) {
        $user = wp_get_current_user();
        petshop_send_system_message($conversation_id, $user->display_name . ' đã đổi tên nhóm từ "' . $old_title . '" thành "' . $new_title . '"');
        return true;
    }
    
    return new WP_Error('update_failed', 'Không thể đổi tên');
}

// =============================================
// AJAX HANDLERS
// =============================================

// Lấy danh sách conversations
add_action('wp_ajax_petshop_get_conversations', 'petshop_ajax_get_conversations');
function petshop_ajax_get_conversations() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Vui lòng đăng nhập');
    }
    
    $type = sanitize_text_field($_GET['type'] ?? '');
    $conversations = petshop_get_user_conversations(get_current_user_id(), $type ?: null);
    
    // Lấy ID của nhóm nhân viên mặc định
    $default_staff_group_id = petshop_get_default_staff_group_id();
    
    $result = array();
    foreach ($conversations as $conv) {
        $last_message = null;
        if ($conv->last_message_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'petshop_chat_messages';
            $last_message = $wpdb->get_row($wpdb->prepare(
                "SELECT content, sender_id, message_type FROM $table WHERE id = %d",
                $conv->last_message_id
            ));
        }
        
        $assigned_user = $conv->assigned_to ? get_userdata($conv->assigned_to) : null;
        $is_default_staff_group = ($conv->id == $default_staff_group_id);
        
        $result[] = array(
            'id' => $conv->id,
            'type' => $conv->type,
            'title' => $conv->title,
            'status' => $conv->status,
            'assigned_to' => $conv->assigned_to,
            'assigned_name' => $assigned_user ? $assigned_user->display_name : null,
            'last_message' => $last_message ? array(
                'content' => wp_trim_words(strip_tags($last_message->content), 8),
                'type' => $last_message->message_type,
            ) : null,
            'last_message_at' => $conv->last_message_at,
            'last_message_timestamp' => $conv->last_message_at ? strtotime($conv->last_message_at) * 1000 : null,
            'created_at' => $conv->created_at,
            'is_mine' => ($conv->assigned_to == get_current_user_id()),
            'is_default_staff_group' => $is_default_staff_group,
        );
    }
    
    wp_send_json_success($result);
}

// Lấy tin nhắn của conversation
add_action('wp_ajax_petshop_get_messages', 'petshop_ajax_get_messages');
function petshop_ajax_get_messages() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    
    if (!petshop_can_access_conversation($conversation_id)) {
        wp_send_json_error('Không có quyền truy cập');
    }
    
    $before_id = intval($_GET['before_id'] ?? 0) ?: null;
    $after_id = intval($_GET['after_id'] ?? 0) ?: null;
    
    $messages = petshop_get_messages($conversation_id, 50, $before_id, $after_id);
    $conv = petshop_get_conversation($conversation_id);
    $participants = petshop_get_participants($conversation_id);
    
    // Đánh dấu đã đọc
    if (!empty($messages)) {
        $last_msg = end($messages);
        petshop_mark_conversation_read($conversation_id, get_current_user_id(), $last_msg->id);
    }
    
    wp_send_json_success(array(
        'conversation' => array(
            'id' => $conv->id,
            'type' => $conv->type,
            'title' => $conv->title,
            'status' => $conv->status,
            'assigned_to' => $conv->assigned_to,
            'can_send' => petshop_can_send_message($conversation_id),
            'is_default_staff_group' => petshop_is_default_staff_group($conversation_id),
        ),
        'messages' => $messages,
        'participants' => $participants,
        'current_user_id' => get_current_user_id(),
    ));
}

// Gửi tin nhắn
add_action('wp_ajax_petshop_send_message', 'petshop_ajax_send_message');
function petshop_ajax_send_message() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $content = wp_kses_post($_POST['content'] ?? '');
    $reply_to = intval($_POST['reply_to_id'] ?? 0) ?: null;
    $message_type = sanitize_text_field($_POST['message_type'] ?? 'text');
    $attachment_url = esc_url($_POST['attachment_url'] ?? '');
    $attachment_name = sanitize_text_field($_POST['attachment_name'] ?? '');
    
    // Cho phép gửi tin nhắn trống nếu có attachment
    if (empty($content) && empty($attachment_url)) {
        wp_send_json_error('Nội dung không được để trống');
    }
    
    // Set message type
    $msg_type = PETSHOP_MSG_TEXT;
    if ($message_type === 'image') $msg_type = PETSHOP_MSG_IMAGE;
    elseif ($message_type === 'video') $msg_type = 'video';
    elseif ($message_type === 'file') $msg_type = PETSHOP_MSG_FILE;
    
    // Attachment info
    $attachment = null;
    if ($attachment_url) {
        $attachment = array(
            'url' => $attachment_url,
            'name' => $attachment_name
        );
    }
    
    $result = petshop_send_message($conversation_id, $content, $msg_type, $attachment, $reply_to);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(array('message_id' => $result));
}

// Tạo conversation support mới (khách hàng)
add_action('wp_ajax_petshop_start_support_chat', 'petshop_ajax_start_support_chat');
function petshop_ajax_start_support_chat() {
    $user_id = get_current_user_id();
    
    // Kiểm tra có conversation support đang mở không
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table WHERE type = 'support' AND customer_id = %d AND status != 'closed' ORDER BY id DESC LIMIT 1",
        $user_id
    ));
    
    if ($existing) {
        wp_send_json_success(array('conversation_id' => $existing->id, 'existing' => true));
        return;
    }
    
    // Tạo mới
    $conversation_id = petshop_create_conversation(array(
        'type' => PETSHOP_CONV_SUPPORT,
        'customer_id' => $user_id,
        'created_by' => $user_id,
    ));
    
    if (!$conversation_id) {
        wp_send_json_error('Không thể tạo cuộc hội thoại');
    }
    
    // Tin nhắn đầu tiên (nếu có)
    $initial_message = sanitize_textarea_field($_POST['message'] ?? '');
    if ($initial_message) {
        petshop_send_message($conversation_id, $initial_message);
    }
    
    wp_send_json_success(array('conversation_id' => $conversation_id, 'existing' => false));
}

// Staff nhận cuộc hội thoại
add_action('wp_ajax_petshop_assign_conversation', 'petshop_ajax_assign_conversation');
function petshop_ajax_assign_conversation() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    
    $result = petshop_assign_conversation($conversation_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success();
}

// Staff rời cuộc hội thoại
add_action('wp_ajax_petshop_unassign_conversation', 'petshop_ajax_unassign_conversation');
function petshop_ajax_unassign_conversation() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    
    $result = petshop_unassign_conversation($conversation_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success();
}

// Toggle star conversation
add_action('wp_ajax_petshop_toggle_star', 'petshop_ajax_toggle_star');
function petshop_ajax_toggle_star() {
    if (!petshop_is_chat_staff()) {
        wp_send_json_error('Không có quyền');
    }
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $result = petshop_toggle_conversation_star($conversation_id);
    
    if ($result === false) {
        wp_send_json_error('Không thể cập nhật');
    }
    
    // Lấy trạng thái mới
    global $wpdb;
    $is_starred = $wpdb->get_var($wpdb->prepare(
        "SELECT is_starred FROM {$wpdb->prefix}petshop_conversations WHERE id = %d",
        $conversation_id
    ));
    
    wp_send_json_success(array('is_starred' => (bool) $is_starred));
}

// Upload attachment
add_action('wp_ajax_petshop_upload_attachment', 'petshop_ajax_upload_attachment');
add_action('wp_ajax_nopriv_petshop_upload_attachment', 'petshop_ajax_upload_attachment');
function petshop_ajax_upload_attachment() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Chưa đăng nhập');
    }
    
    if (empty($_FILES['file'])) {
        wp_send_json_error('Không có file');
    }
    
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $file = $_FILES['file'];
    
    // Validate file type using WordPress function
    $allowed_types = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $wp_filetype = wp_check_filetype($file['name'], $allowed_types);
    
    if (!$wp_filetype['ext'] || !$wp_filetype['type']) {
        wp_send_json_error('Loại file không được hỗ trợ. Cho phép: jpg, png, gif, webp, mp4, webm, pdf, doc, docx, xls, xlsx');
    }
    
    // Determine file type and size limit
    $mime_type = $wp_filetype['type'];
    $file_type = 'file';
    
    if (strpos($mime_type, 'image/') === 0) {
        $file_type = 'image';
        $max_size = 5 * 1024 * 1024; // 5MB for images
        $size_label = '5MB';
    } elseif (strpos($mime_type, 'video/') === 0) {
        $file_type = 'video';
        $max_size = 20 * 1024 * 1024; // 20MB for videos
        $size_label = '20MB';
    } else {
        $max_size = 10 * 1024 * 1024; // 10MB for documents
        $size_label = '10MB';
    }
    
    if ($file['size'] > $max_size) {
        wp_send_json_error("File quá lớn. Tối đa {$size_label} cho loại file này.");
    }
    
    // Upload file
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (isset($upload['error'])) {
        wp_send_json_error($upload['error']);
    }
    
    wp_send_json_success(array(
        'url' => $upload['url'],
        'name' => $file['name'],
        'type' => $file_type,
        'mime' => $mime_type,
        'size' => $file['size']
    ));
}

// =============================================
// MESSENGER-LIKE FEATURES
// =============================================

// Add/Remove Reaction to message
add_action('wp_ajax_petshop_toggle_reaction', 'petshop_ajax_toggle_reaction');
function petshop_ajax_toggle_reaction() {
    $message_id = intval($_POST['message_id'] ?? 0);
    $reaction = sanitize_text_field($_POST['reaction'] ?? '');
    $user_id = get_current_user_id();
    
    $allowed_reactions = array('like', 'love', 'haha', 'wow', 'sad', 'angry');
    if (!in_array($reaction, $allowed_reactions)) {
        wp_send_json_error('Reaction không hợp lệ');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $current = $wpdb->get_var($wpdb->prepare("SELECT reactions FROM $table WHERE id = %d", $message_id));
    $reactions = $current ? json_decode($current, true) : array();
    
    // Toggle reaction
    if (isset($reactions[$reaction]) && in_array($user_id, $reactions[$reaction])) {
        $reactions[$reaction] = array_diff($reactions[$reaction], array($user_id));
        if (empty($reactions[$reaction])) unset($reactions[$reaction]);
    } else {
        // Remove user from other reactions first
        foreach ($reactions as $r => $users) {
            $reactions[$r] = array_diff($users, array($user_id));
            if (empty($reactions[$r])) unset($reactions[$r]);
        }
        $reactions[$reaction][] = $user_id;
    }
    
    $wpdb->update($table, array('reactions' => json_encode($reactions)), array('id' => $message_id));
    wp_send_json_success(array('reactions' => $reactions));
}

// Edit message (own messages only, within 15 minutes)
add_action('wp_ajax_petshop_edit_message', 'petshop_ajax_edit_message');
function petshop_ajax_edit_message() {
    $message_id = intval($_POST['message_id'] ?? 0);
    $new_content = wp_kses_post($_POST['content'] ?? '');
    $user_id = get_current_user_id();
    
    if (empty($new_content)) {
        wp_send_json_error('Nội dung không được trống');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    
    if (!$msg || $msg->sender_id != $user_id) {
        wp_send_json_error('Không có quyền sửa');
    }
    
    $wpdb->update($table, array(
        'content' => $new_content,
        'is_edited' => 1,
        'updated_at' => current_time('mysql')
    ), array('id' => $message_id));
    
    wp_send_json_success();
}

// Delete message (soft delete)
add_action('wp_ajax_petshop_delete_message', 'petshop_ajax_delete_message');
function petshop_ajax_delete_message() {
    $message_id = intval($_POST['message_id'] ?? 0);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $message_id));
    
    // Owner or admin can delete
    if (!$msg || ($msg->sender_id != $user_id && !petshop_can_monitor_chat())) {
        wp_send_json_error('Không có quyền xóa');
    }
    
    $wpdb->update($table, array('is_deleted' => 1), array('id' => $message_id));
    wp_send_json_success();
}

// Pin/Unpin message
add_action('wp_ajax_petshop_pin_message', 'petshop_ajax_pin_message');
function petshop_ajax_pin_message() {
    $message_id = intval($_POST['message_id'] ?? 0);
    
    if (!petshop_is_chat_staff()) {
        wp_send_json_error('Không có quyền');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $current = $wpdb->get_var($wpdb->prepare("SELECT is_pinned FROM $table WHERE id = %d", $message_id));
    $wpdb->update($table, array('is_pinned' => $current ? 0 : 1), array('id' => $message_id));
    
    wp_send_json_success(array('is_pinned' => !$current));
}

// Set typing indicator
add_action('wp_ajax_petshop_set_typing', 'petshop_ajax_set_typing');
function petshop_ajax_set_typing() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $is_typing = intval($_POST['is_typing'] ?? 0);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_presence';
    
    $wpdb->replace($table, array(
        'user_id' => $user_id,
        'conversation_id' => $is_typing ? $conversation_id : null,
        'is_typing' => $is_typing,
        'last_seen' => current_time('mysql')
    ));
    
    wp_send_json_success();
}

// Get typing users in conversation
add_action('wp_ajax_petshop_get_typing', 'petshop_ajax_get_typing');
function petshop_ajax_get_typing() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_presence';
    
    $typing = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id FROM $table 
         WHERE conversation_id = %d AND is_typing = 1 AND user_id != %d 
         AND last_seen > DATE_SUB(NOW(), INTERVAL 10 SECOND)",
        $conversation_id, $user_id
    ));
    
    $names = array();
    foreach ($typing as $t) {
        $user = get_userdata($t->user_id);
        if ($user) $names[] = $user->display_name;
    }
    
    wp_send_json_success(array('typing' => $names));
}

// Get shared media in conversation
add_action('wp_ajax_petshop_get_shared_media', 'petshop_ajax_get_shared_media');
function petshop_ajax_get_shared_media() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $media = $wpdb->get_results($wpdb->prepare(
        "SELECT id, attachment_url, attachment_name, message_type, created_at 
         FROM $table 
         WHERE conversation_id = %d AND attachment_url IS NOT NULL AND is_deleted = 0
         ORDER BY created_at DESC LIMIT 50",
        $conversation_id
    ));
    
    wp_send_json_success(array('media' => $media));
}

// Get pinned messages in conversation
add_action('wp_ajax_petshop_get_pinned_messages', 'petshop_ajax_get_pinned_messages');
function petshop_ajax_get_pinned_messages() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $pinned = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as sender_name 
         FROM $table m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.conversation_id = %d AND m.is_pinned = 1 AND m.is_deleted = 0
         ORDER BY m.created_at DESC",
        $conversation_id
    ));
    
    wp_send_json_success(array('pinned' => $pinned));
}

// Get conversation details (enhanced for info panel)
add_action('wp_ajax_petshop_get_conversation_details', 'petshop_ajax_get_conversation_details');
function petshop_ajax_get_conversation_details() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $msg_table = $wpdb->prefix . 'petshop_chat_messages';
    
    $conv = petshop_get_conversation($conversation_id);
    if (!$conv) {
        wp_send_json_error('Không tìm thấy');
    }
    
    $details = array(
        'id' => $conv->id,
        'type' => $conv->type,
        'status' => $conv->status,
        'created_at' => $conv->created_at,
        'is_starred' => (bool) $conv->is_starred,
    );
    
    // Message stats
    $details['stats'] = array(
        'total_messages' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_table WHERE conversation_id = %d AND is_deleted = 0",
            $conversation_id
        )),
        'media_count' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_table WHERE conversation_id = %d AND attachment_url IS NOT NULL AND is_deleted = 0",
            $conversation_id
        )),
        'pinned_count' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_table WHERE conversation_id = %d AND is_pinned = 1 AND is_deleted = 0",
            $conversation_id
        )),
    );
    
    // Customer info for support chat
    if ($conv->type === 'support' && $conv->customer_id) {
        $customer = get_userdata($conv->customer_id);
        if ($customer) {
            $details['customer'] = array(
                'id' => $customer->ID,
                'name' => $customer->display_name,
                'email' => $customer->user_email,
                'phone' => get_user_meta($customer->ID, 'billing_phone', true),
                'avatar' => get_avatar_url($customer->ID, array('size' => 80)),
                'registered' => $customer->user_registered,
            );
            
            // WooCommerce order history
            if (function_exists('wc_get_orders')) {
                $orders = wc_get_orders(array(
                    'customer' => $customer->ID,
                    'limit' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                
                $details['customer']['orders'] = array();
                foreach ($orders as $order) {
                    $details['customer']['orders'][] = array(
                        'id' => $order->get_id(),
                        'status' => $order->get_status(),
                        'total' => $order->get_total(),
                        'date' => $order->get_date_created()->format('Y-m-d H:i'),
                    );
                }
                
                $details['customer']['total_orders'] = wc_get_customer_order_count($customer->ID);
                $details['customer']['total_spent'] = wc_get_customer_total_spent($customer->ID);
            }
            
            // Previous conversations count
            $details['customer']['prev_conversations'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $conv_table WHERE customer_id = %d",
                $customer->ID
            ));
        }
    }
    
    // Assigned staff
    if ($conv->assigned_to) {
        $staff = get_userdata($conv->assigned_to);
        if ($staff) {
            $details['assigned'] = array(
                'id' => $staff->ID,
                'name' => $staff->display_name,
                'email' => $staff->user_email,
                'avatar' => get_avatar_url($staff->ID, array('size' => 50)),
            );
        }
    }
    
    // Participants for group/direct
    if ($conv->type !== 'support') {
        $part_table = $wpdb->prefix . 'petshop_conversation_participants';
        $participants = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email 
             FROM $part_table p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.conversation_id = %d AND p.left_at IS NULL",
            $conversation_id
        ));
        
        $details['participants'] = array();
        foreach ($participants as $p) {
            $details['participants'][] = array(
                'id' => $p->user_id,
                'name' => $p->display_name,
                'email' => $p->user_email,
                'role' => $p->role,
                'avatar' => get_avatar_url($p->user_id, array('size' => 50)),
                'joined_at' => $p->joined_at,
            );
        }
    }
    
    // Check if this is the default staff group
    $details['is_default_staff_group'] = petshop_is_default_staff_group($conversation_id);
    
    wp_send_json_success($details);
}

// Quick replies templates
add_action('wp_ajax_petshop_get_quick_replies', 'petshop_ajax_get_quick_replies');
function petshop_ajax_get_quick_replies() {
    $replies = get_option('petshop_quick_replies', array(
        array('title' => 'Chào khách', 'content' => 'Xin chào! Cảm ơn bạn đã liên hệ PetShop. Tôi có thể giúp gì cho bạn?'),
        array('title' => 'Cảm ơn', 'content' => 'Cảm ơn bạn đã mua hàng tại PetShop! Nếu có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi.'),
        array('title' => 'Kiểm tra đơn', 'content' => 'Tôi sẽ kiểm tra đơn hàng của bạn ngay. Xin vui lòng cho tôi mã đơn hàng hoặc số điện thoại đặt hàng.'),
        array('title' => 'Giao hàng', 'content' => 'Thời gian giao hàng trung bình là 2-3 ngày trong nội thành và 3-5 ngày với các tỉnh khác.'),
        array('title' => 'Đổi trả', 'content' => 'PetShop hỗ trợ đổi trả trong 7 ngày nếu sản phẩm còn nguyên tem, chưa sử dụng. Bạn vui lòng cung cấp thêm thông tin để tôi hỗ trợ nhé.'),
        array('title' => 'Tư vấn', 'content' => 'Bạn đang quan tâm đến sản phẩm nào? Tôi sẵn sàng tư vấn cho bạn!'),
        array('title' => 'Chờ xử lý', 'content' => 'Vui lòng chờ trong giây lát, tôi đang kiểm tra thông tin cho bạn.'),
        array('title' => 'Kết thúc', 'content' => 'Cảm ơn bạn đã liên hệ! Nếu cần hỗ trợ thêm, đừng ngần ngại nhắn tin lại nhé. Chúc bạn một ngày tốt lành! 🐾'),
    ));
    
    wp_send_json_success($replies);
}

// Save quick replies (admin only)
add_action('wp_ajax_petshop_save_quick_replies', 'petshop_ajax_save_quick_replies');
function petshop_ajax_save_quick_replies() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền');
    }
    
    $replies = $_POST['replies'] ?? array();
    $sanitized = array();
    
    foreach ($replies as $r) {
        $sanitized[] = array(
            'title' => sanitize_text_field($r['title'] ?? ''),
            'content' => wp_kses_post($r['content'] ?? ''),
        );
    }
    
    update_option('petshop_quick_replies', $sanitized);
    wp_send_json_success();
}

// Search messages in conversation
add_action('wp_ajax_petshop_search_messages', 'petshop_ajax_search_messages');
function petshop_ajax_search_messages() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    $query = sanitize_text_field($_GET['query'] ?? '');
    
    if (strlen($query) < 2) {
        wp_send_json_error('Từ khóa quá ngắn');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT m.id, m.content, m.created_at, m.sender_id, u.display_name as sender_name
         FROM $table m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.conversation_id = %d AND m.content LIKE %s AND m.is_deleted = 0
         ORDER BY m.created_at DESC LIMIT 20",
        $conversation_id, '%' . $wpdb->esc_like($query) . '%'
    ));
    
    wp_send_json_success(array('results' => $results));
}

// =============================================
// GROUP MANAGEMENT AJAX HANDLERS
// =============================================

// Rename conversation
add_action('wp_ajax_petshop_rename_conversation', 'petshop_ajax_rename_conversation');
function petshop_ajax_rename_conversation() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $user_id = get_current_user_id();
    
    // Prevent renaming default staff group
    if (petshop_is_default_staff_group($conversation_id)) {
        wp_send_json_error('Không thể đổi tên nhóm nhân viên mặc định');
    }
    
    if (empty($title)) {
        wp_send_json_error('Tên không được trống');
    }
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $part_table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Check if user is participant or admin
    $is_participant = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $part_table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $user_id
    ));
    
    if (!$is_participant && !petshop_can_monitor_chat()) {
        wp_send_json_error('Không có quyền');
    }
    
    $wpdb->update($conv_table, array('title' => $title), array('id' => $conversation_id));
    
    // Add system message
    petshop_send_message($conversation_id, "đã đổi tên nhóm thành \"$title\"", 'system');
    
    wp_send_json_success();
}

// Add member to group
add_action('wp_ajax_petshop_add_member', 'petshop_ajax_add_member');
function petshop_ajax_add_member() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $new_user_id = intval($_POST['user_id'] ?? 0);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $part_table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Check conversation type
    $conv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conv_table WHERE id = %d", $conversation_id));
    if (!$conv || $conv->type !== 'group') {
        wp_send_json_error('Chỉ có thể thêm thành viên vào nhóm');
    }
    
    // Check if user is participant
    $is_participant = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $part_table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $user_id
    ));
    
    if (!$is_participant && !petshop_can_monitor_chat()) {
        wp_send_json_error('Không có quyền');
    }
    
    // Check if already member
    $already_member = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $part_table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $new_user_id
    ));
    
    if ($already_member) {
        wp_send_json_error('Đã là thành viên');
    }
    
    // Add member
    $wpdb->insert($part_table, array(
        'conversation_id' => $conversation_id,
        'user_id' => $new_user_id,
        'role' => 'member',
        'joined_at' => current_time('mysql'),
    ));
    
    $new_user = get_userdata($new_user_id);
    petshop_send_message($conversation_id, "đã thêm " . $new_user->display_name . " vào nhóm", 'system');
    
    wp_send_json_success();
}

// Remove member from group
add_action('wp_ajax_petshop_remove_member', 'petshop_ajax_remove_member');
function petshop_ajax_remove_member() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $remove_user_id = intval($_POST['user_id'] ?? 0);
    $user_id = get_current_user_id();
    
    // Prevent removing from default staff group
    if (petshop_is_default_staff_group($conversation_id)) {
        wp_send_json_error('Không thể xóa thành viên khỏi nhóm nhân viên mặc định');
    }
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $part_table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Check conversation type
    $conv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conv_table WHERE id = %d", $conversation_id));
    if (!$conv || $conv->type !== 'group') {
        wp_send_json_error('Không phải nhóm');
    }
    
    // Check if user is admin of group or system admin
    $is_admin = $wpdb->get_var($wpdb->prepare(
        "SELECT role FROM $part_table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $user_id
    ));
    
    if ($is_admin !== 'admin' && !petshop_can_monitor_chat()) {
        wp_send_json_error('Chỉ admin nhóm mới có thể xóa thành viên');
    }
    
    // Remove member
    $wpdb->update($part_table, 
        array('left_at' => current_time('mysql')), 
        array('conversation_id' => $conversation_id, 'user_id' => $remove_user_id)
    );
    
    $removed_user = get_userdata($remove_user_id);
    petshop_send_message($conversation_id, "đã xóa " . $removed_user->display_name . " khỏi nhóm", 'system');
    
    wp_send_json_success();
}

// Leave group
add_action('wp_ajax_petshop_leave_group', 'petshop_ajax_leave_group');
function petshop_ajax_leave_group() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $user_id = get_current_user_id();
    
    // Prevent leaving default staff group
    if (petshop_is_default_staff_group($conversation_id)) {
        wp_send_json_error('Không thể rời khỏi nhóm nhân viên mặc định');
    }
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $part_table = $wpdb->prefix . 'petshop_conversation_participants';
    
    // Check conversation type
    $conv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conv_table WHERE id = %d", $conversation_id));
    if (!$conv || !in_array($conv->type, array('group', 'direct'))) {
        wp_send_json_error('Không thể rời');
    }
    
    // Leave
    $wpdb->update($part_table, 
        array('left_at' => current_time('mysql')), 
        array('conversation_id' => $conversation_id, 'user_id' => $user_id)
    );
    
    $user = get_userdata($user_id);
    petshop_send_message($conversation_id, $user->display_name . " đã rời khỏi nhóm", 'system');
    
    wp_send_json_success();
}

// Delete group (admin only)
add_action('wp_ajax_petshop_delete_group', 'petshop_ajax_delete_group');
function petshop_ajax_delete_group() {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $user_id = get_current_user_id();
    
    // Prevent deleting default staff group
    if (petshop_is_default_staff_group($conversation_id)) {
        wp_send_json_error('Không thể xóa nhóm nhân viên mặc định');
    }
    
    global $wpdb;
    $conv_table = $wpdb->prefix . 'petshop_conversations';
    $part_table = $wpdb->prefix . 'petshop_conversation_participants';
    $msg_table = $wpdb->prefix . 'petshop_chat_messages';
    
    // Check if user is admin of group or system admin
    $is_admin = $wpdb->get_var($wpdb->prepare(
        "SELECT role FROM $part_table WHERE conversation_id = %d AND user_id = %d AND left_at IS NULL",
        $conversation_id, $user_id
    ));
    
    if ($is_admin !== 'admin' && !petshop_can_monitor_chat()) {
        wp_send_json_error('Chỉ admin nhóm mới có thể xóa nhóm');
    }
    
    // Delete all messages
    $wpdb->delete($msg_table, array('conversation_id' => $conversation_id));
    
    // Delete all participants
    $wpdb->delete($part_table, array('conversation_id' => $conversation_id));
    
    // Delete conversation
    $wpdb->delete($conv_table, array('id' => $conversation_id));
    
    wp_send_json_success();
}

// Get shared media with date grouping
add_action('wp_ajax_petshop_get_shared_media_grouped', 'petshop_ajax_get_shared_media_grouped');
function petshop_ajax_get_shared_media_grouped() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    $type = sanitize_text_field($_GET['type'] ?? 'all'); // all, image, video, file
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_chat_messages';
    
    $where = "conversation_id = %d AND attachment_url IS NOT NULL AND is_deleted = 0";
    $params = array($conversation_id);
    
    if ($type === 'image') {
        $where .= " AND message_type = 'image'";
    } else if ($type === 'video') {
        $where .= " AND message_type = 'video'";
    } else if ($type === 'file') {
        $where .= " AND message_type = 'file'";
    }
    
    $media = $wpdb->get_results($wpdb->prepare(
        "SELECT id, attachment_url, attachment_name, message_type, created_at 
         FROM $table 
         WHERE $where
         ORDER BY created_at DESC LIMIT 100",
        ...$params
    ));
    
    // Group by date
    $grouped = array();
    foreach ($media as $m) {
        $date = date('Y-m-d', strtotime($m->created_at));
        $date_label = date('d/m/Y', strtotime($m->created_at));
        
        // Today, Yesterday logic
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($date === $today) {
            $date_label = 'Hôm nay';
        } else if ($date === $yesterday) {
            $date_label = 'Hôm qua';
        }
        
        if (!isset($grouped[$date])) {
            $grouped[$date] = array(
                'label' => $date_label,
                'items' => array()
            );
        }
        
        $grouped[$date]['items'][] = $m;
    }
    
    wp_send_json_success(array('grouped' => $grouped));
}

// Lấy danh sách conversations với filters
add_action('wp_ajax_petshop_get_filtered_conversations', 'petshop_ajax_get_filtered_conversations');
function petshop_ajax_get_filtered_conversations() {
    if (!petshop_is_chat_staff()) {
        wp_send_json_error('Không có quyền');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'petshop_conversations';
    $current_user_id = get_current_user_id();
    $is_admin = petshop_can_monitor_chat(); // Admin hoặc Manager
    
    $filter = sanitize_text_field($_GET['filter'] ?? 'all');
    $staff_filter = intval($_GET['staff_id'] ?? 0);
    $starred_only = intval($_GET['starred'] ?? 0);
    
    $where = "type = 'support'";
    $params = array();
    
    // NHÂN VIÊN THƯỜNG: Chỉ thấy của mình + chưa ai nhận
    // ADMIN/MANAGER: Thấy tất cả
    if (!$is_admin) {
        // Nhân viên chỉ thấy: assigned_to = mình HOẶC assigned_to IS NULL
        $where .= " AND (assigned_to = %d OR assigned_to IS NULL)";
        $params[] = $current_user_id;
    }
    
    // Filter bổ sung theo tab
    switch ($filter) {
        case 'mine':
            // Reset where for mine filter
            if (!$is_admin) {
                // Nhân viên: Chỉ của mình
                $where = "type = 'support' AND assigned_to = %d";
                $params = array($current_user_id);
            } else {
                $where .= " AND assigned_to = %d";
                $params[] = $current_user_id;
            }
            break;
        case 'new':
            // Chỉ chưa ai nhận
            if (!$is_admin) {
                $where = "type = 'support' AND assigned_to IS NULL";
                $params = array();
            } else {
                $where .= " AND assigned_to IS NULL";
            }
            break;
        case 'all':
        default:
            // Đã xử lý ở trên
            break;
    }
    
    // Admin có thể lọc theo nhân viên
    if ($is_admin && $staff_filter > 0) {
        $where .= " AND assigned_to = %d";
        $params[] = $staff_filter;
    }
    
    // Lọc chỉ starred
    if ($starred_only) {
        $where .= " AND is_starred = 1";
    }
    
    // Sắp xếp: Starred trước, rồi của tôi, rồi mới, rồi theo tin nhắn cuối
    $sql = "SELECT * FROM $table WHERE $where ORDER BY 
        is_starred DESC,
        CASE 
            WHEN assigned_to = %d THEN 0
            WHEN assigned_to IS NULL THEN 1
            ELSE 2
        END ASC,
        last_message_at DESC LIMIT 100";
    
    // Add current user id for ORDER BY
    $params[] = $current_user_id;
    
    if (!empty($params)) {
        $conversations = $wpdb->get_results($wpdb->prepare($sql, ...$params));
    } else {
        $conversations = $wpdb->get_results($sql);
    }
    
    // Add extra info
    foreach ($conversations as &$conv) {
        // Customer info
        if ($conv->customer_id) {
            $customer = get_userdata($conv->customer_id);
            $conv->customer_name = $customer ? $customer->display_name : 'Khách #' . $conv->customer_id;
            $conv->customer_avatar = get_avatar_url($conv->customer_id, array('size' => 40));
        } else {
            $conv->customer_name = 'Khách ẩn danh';
            $conv->customer_avatar = '';
        }
        
        // Assigned staff info
        if ($conv->assigned_to) {
            $staff = get_userdata($conv->assigned_to);
            $conv->assigned_name = $staff ? $staff->display_name : 'Nhân viên #' . $conv->assigned_to;
        } else {
            $conv->assigned_name = '';
        }
        
        // Star status
        $conv->is_starred = (bool) ($conv->is_starred ?? 0);
        
        // Last message preview
        $last_msg = $wpdb->get_row($wpdb->prepare(
            "SELECT content, sender_id FROM {$wpdb->prefix}petshop_chat_messages WHERE conversation_id = %d ORDER BY id DESC LIMIT 1",
            $conv->id
        ));
        if ($last_msg) {
            $conv->last_message_preview = wp_trim_words($last_msg->content, 10, '...');
        } else {
            $conv->last_message_preview = 'Chưa có tin nhắn';
        }
        
        // Unread count for this staff
        $conv->unread_count = petshop_count_unread_in_conversation($conv->id, $current_user_id);
        
        // Is mine
        $conv->is_mine = ($conv->assigned_to == $current_user_id);
    }
    
    // Get staff list for admin filter
    $staff_list = array();
    if ($is_admin) {
        $staff_users = get_users(array(
            'role__in' => array('administrator', 'petshop_manager', 'petshop_staff'),
            'orderby' => 'display_name',
        ));
        foreach ($staff_users as $user) {
            $staff_list[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
            );
        }
    }
    
    wp_send_json_success(array(
        'conversations' => $conversations,
        'staff_list' => $staff_list,
        'is_admin' => $is_admin,
    ));
}

// Tạo nhóm chat
add_action('wp_ajax_petshop_create_group', 'petshop_ajax_create_group');
function petshop_ajax_create_group() {
    $title = sanitize_text_field($_POST['title'] ?? '');
    $member_ids = array_map('intval', $_POST['members'] ?? array());
    
    if (empty($title)) {
        wp_send_json_error('Tên nhóm không được để trống');
    }
    
    $result = petshop_create_group_chat($title, $member_ids);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(array('conversation_id' => $result));
}

// Polling tin nhắn mới
add_action('wp_ajax_petshop_poll_messages', 'petshop_ajax_poll_messages');
function petshop_ajax_poll_messages() {
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    $last_message_id = intval($_GET['last_message_id'] ?? 0);
    
    if (!petshop_can_access_conversation($conversation_id)) {
        wp_send_json_error('Không có quyền');
    }
    
    // Lấy thông tin conversation
    $conv = petshop_get_conversation($conversation_id);
    
    $messages = petshop_get_messages($conversation_id, 50, null, $last_message_id);
    
    // Đánh dấu đã đọc
    if (!empty($messages)) {
        $last_msg = end($messages);
        petshop_mark_conversation_read($conversation_id, get_current_user_id(), $last_msg->id);
    }
    
    wp_send_json_success(array(
        'messages' => $messages,
        'has_new' => !empty($messages),
        'conversation' => array(
            'id' => $conv->id,
            'status' => $conv->status,
            'assigned_to' => $conv->assigned_to,
        ),
    ));
}

// Đếm unread
add_action('wp_ajax_petshop_count_unread', 'petshop_ajax_count_unread');
function petshop_ajax_count_unread() {
    wp_send_json_success(array(
        'count' => petshop_count_unread_messages(),
    ));
}

// Lấy danh sách staff để tạo nhóm/chat (Lấy tất cả từ DB không phụ thuộc online status)
add_action('wp_ajax_petshop_get_staff_list', 'petshop_ajax_get_staff_list');
function petshop_ajax_get_staff_list() {
    if (!petshop_is_chat_staff()) {
        wp_send_json_error('Không có quyền');
    }
    
    $current_user_id = get_current_user_id();
    $include_self = isset($_GET['include_self']) && $_GET['include_self'] === '1';
    
    $args = array(
        'role__in' => array('administrator', 'petshop_manager', 'petshop_staff'),
        'orderby' => 'display_name',
        'order' => 'ASC',
    );
    
    if (!$include_self) {
        $args['exclude'] = array($current_user_id);
    }
    
    $staff_users = get_users($args);
    
    // Lấy online status từ presence table
    global $wpdb;
    $presence_table = $wpdb->prefix . 'petshop_chat_presence';
    $online_users = array();
    $online_check = $wpdb->get_results("SELECT user_id FROM $presence_table WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    foreach ($online_check as $row) {
        $online_users[$row->user_id] = true;
    }
    
    $result = array();
    foreach ($staff_users as $user) {
        $role_name = 'Nhân viên';
        if (in_array('administrator', $user->roles)) {
            $role_name = 'Quản trị viên';
        } elseif (in_array('petshop_manager', $user->roles)) {
            $role_name = 'Quản lý';
        }
        
        $result[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'role' => $role_name,
            'role_name' => $role_name,
            'avatar' => get_avatar_url($user->ID, array('size' => 50)),
            'is_online' => isset($online_users[$user->ID]),
        );
    }
    
    wp_send_json_success($result);
}

// Tạo chat 1-1 (Direct Message)
add_action('wp_ajax_petshop_start_direct_chat', 'petshop_ajax_start_direct_chat');
function petshop_ajax_start_direct_chat() {
    $target_user_id = intval($_POST['user_id'] ?? 0);
    
    if (!$target_user_id || !petshop_is_chat_staff()) {
        wp_send_json_error('Không hợp lệ');
    }
    
    $current_user_id = get_current_user_id();
    
    // Kiểm tra xem đã có conversation 1-1 chưa
    global $wpdb;
    $table_conv = $wpdb->prefix . 'petshop_conversations';
    $table_part = $wpdb->prefix . 'petshop_conversation_participants';
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT c.id FROM $table_conv c
        INNER JOIN $table_part p1 ON c.id = p1.conversation_id AND p1.user_id = %d
        INNER JOIN $table_part p2 ON c.id = p2.conversation_id AND p2.user_id = %d
        WHERE c.type = 'direct'
        LIMIT 1",
        $current_user_id, $target_user_id
    ));
    
    if ($existing) {
        wp_send_json_success(array('conversation_id' => $existing, 'existing' => true));
        return;
    }
    
    // Tạo conversation mới
    $target_user = get_userdata($target_user_id);
    $current_user = wp_get_current_user();
    
    $conversation_id = petshop_create_conversation(array(
        'type' => PETSHOP_CONV_DIRECT,
        'title' => $target_user->display_name,
        'created_by' => $current_user_id,
    ));
    
    if (!$conversation_id) {
        wp_send_json_error('Không thể tạo cuộc trò chuyện');
    }
    
    // Thêm target user vào participants
    petshop_add_participant($conversation_id, $target_user_id, 'member');
    
    wp_send_json_success(array('conversation_id' => $conversation_id, 'existing' => false));
}

// =============================================
// ADMIN MENU
// =============================================
add_action('admin_menu', 'petshop_chat_admin_menu');
function petshop_chat_admin_menu() {
    // Chat icon SVG base64
    $chat_icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/></svg>');
    
    // Menu chính - Chỉ có 1 trang duy nhất
    add_menu_page(
        'Chat & Tin nhắn',
        'Chat',
        'read',
        'petshop-chat',
        'petshop_chat_page',
        $chat_icon,
        26
    );
    
    // Ẩn submenu "Chat" mặc định
    add_submenu_page(
        'petshop-chat',
        'Chat & Tin nhắn',
        'Chat',
        'read',
        'petshop-chat',
        'petshop_chat_page'
    );
}

// =============================================
// ADMIN PAGES
// =============================================

/**
 * Trang Chat chính
 */
function petshop_chat_page() {
    $current_conv_id = intval($_GET['conv'] ?? 0);
    ?>
    <!-- Load Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { box-sizing: border-box; }
        
        /* Hide WordPress footer */
        #wpfooter { display: none !important; }
        #footer, .clear { display: none !important; }
        
        .petshop-chat-wrap {
            display: flex;
            height: calc(100vh - 32px);
            background: #f0f2f5;
            margin: 0 0 0 -20px;
            width: calc(100% + 20px);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            position: relative;
        }
        
        /* Ensure nothing shows above WordPress admin bar */
        body.admin-bar .petshop-chat-wrap {
            height: calc(100vh - 32px);
        }
        
        /* Sidebar */
        .chat-sidebar {
            width: 340px;
            min-width: 280px;
            max-width: 400px;
            background: #fff;
            border-right: 1px solid #e4e6eb;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .chat-sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #e4e6eb;
            flex-shrink: 0;
        }
        
        .chat-sidebar-header h2 {
            margin: 0 0 12px;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chat-sidebar-header h2 i {
            color: #EC802B;
        }
        
        .chat-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .chat-tab {
            padding: 6px 12px;
            border: none;
            background: #f0f2f5;
            border-radius: 16px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .chat-tab.active {
            background: #EC802B;
            color: #fff;
        }
        
        .chat-tab:hover:not(.active) {
            background: #e4e6eb;
        }
        
        /* Support filters */
        .filter-btn {
            padding: 4px 10px;
            border: 1px solid #e4e6eb;
            background: #fff;
            border-radius: 12px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .filter-btn.active {
            background: #EC802B;
            color: #fff;
            border-color: #EC802B;
        }
        
        .filter-select {
            padding: 5px 8px;
            border: 1px solid #e4e6eb;
            border-radius: 6px;
            font-size: 12px;
            background: #fff;
            cursor: pointer;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #EC802B;
        }
        
        /* Conversation labels */
        .conv-label {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 4px;
        }
        
        .label-selector {
            padding: 4px 8px;
            border: 1px solid #e4e6eb;
            border-radius: 4px;
            font-size: 12px;
            background: #fff;
            cursor: pointer;
        }
        
        .label-selector option[value="new"] { color: #17a2b8; }
        .label-selector option[value="processing"] { color: #007bff; }
        .label-selector option[value="waiting"] { color: #856404; }
        .label-selector option[value="urgent"] { color: #dc3545; }
        .label-selector option[value="resolved"] { color: #28a745; }
        
        #labelFilter option[value="new"] { color: #17a2b8; }
        #labelFilter option[value="processing"] { color: #007bff; }
        #labelFilter option[value="waiting"] { color: #856404; }
        #labelFilter option[value="urgent"] { color: #dc3545; }
        #labelFilter option[value="resolved"] { color: #28a745; }
        
        .chat-search {
            padding: 10px 16px;
            border-bottom: 1px solid #e4e6eb;
            flex-shrink: 0;
        }
        
        .chat-search-input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: none;
            background: #f0f2f5;
            border-radius: 20px;
            font-size: 14px;
            position: relative;
        }
        
        .chat-search-input:focus {
            outline: none;
            background: #e4e6eb;
        }
        
        .chat-search-wrap {
            position: relative;
        }
        
        .chat-search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #65676b;
            font-size: 14px;
        }
        
        .conversation-list {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.15s;
            gap: 12px;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .conversation-item:hover {
            background: #f5f5f5;
        }
        
        .conversation-item.active {
            background: #FFF5EC;
            border-left: 3px solid #EC802B;
        }
        
        .conversation-item.unread {
            background: #fff9e6;
        }
        
        .conv-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .conv-avatar.group {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .conv-avatar.support {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }
        
        .conv-avatar.direct {
            background: linear-gradient(135deg, #fd7e14, #e85d04);
        }
        
        .conv-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .conv-title {
            font-weight: 600;
            font-size: 14px;
            color: #050505;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conv-preview {
            font-size: 13px;
            color: #65676b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }
        
        .conv-meta {
            text-align: right;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        
        .conv-time {
            font-size: 11px;
            color: #65676b;
        }
        
        .conv-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .conv-badge.open {
            background: #fff3cd;
            color: #856404;
        }
        
        .conv-badge.assigned {
            background: #d4edda;
            color: #155724;
        }
        
        .conv-badge.mine {
            background: #FFF5EC;
            color: #EC802B;
        }
        
        .unread-count {
            min-width: 18px;
            height: 18px;
            background: #dc3545;
            color: #fff;
            border-radius: 9px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            margin-top: 4px;
        }
        
        .conversation-item.my-conv {
            border-left: 3px solid #EC802B;
        }
        
        /* Conversation Section Headers */
        .conversation-section {
            margin-bottom: 4px;
        }
        
        .section-header {
            padding: 10px 16px 6px;
            font-size: 11px;
            font-weight: 700;
            color: #65676b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #e4e6eb;
            margin-bottom: 4px;
            background: #f8f9fa;
        }
        
        .section-header i {
            font-size: 12px;
        }
        
        /* Default Staff Group */
        .conversation-item.default-staff-group {
            background: linear-gradient(to right, #fff5eb, #ffffff);
            border-left: 3px solid #EC802B;
        }
        
        .conversation-item.default-staff-group:hover {
            background: linear-gradient(to right, #ffefe0, #f0f2f5);
        }
        
        .conversation-item.default-staff-group.active {
            background: linear-gradient(to right, #ffe4cc, #e7f3ff);
        }
        
        .conv-badge.default-group {
            background: #EC802B;
            color: #fff;
            padding: 2px 6px;
        }
        
        .conv-badge.default-group i {
            font-size: 10px;
        }
        
        .chat-sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #e4e6eb;
            flex-shrink: 0;
            display: flex;
            gap: 8px;
        }
        
        .chat-sidebar-footer button {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .btn-new-group {
            background: #EC802B;
            color: #fff;
        }
        
        .btn-new-group:hover {
            background: #d97326;
        }
        
        .btn-new-chat {
            background: #f0f2f5;
            color: #333;
        }
        
        .btn-new-chat:hover {
            background: #e4e6eb;
        }
        
        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            min-width: 0;
        }
        
        .chat-main-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #65676b;
            padding: 20px;
            text-align: center;
        }
        
        .chat-main-empty i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
            color: #EC802B;
        }
        
        .chat-main-empty h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #333;
        }
        
        .chat-main-empty p {
            margin: 0;
            font-size: 14px;
        }
        
        .chat-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e4e6eb;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }
        
        .chat-header-info {
            flex: 1;
            min-width: 150px;
        }
        
        .chat-header-title {
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .chat-header-status {
            font-size: 13px;
            color: #65676b;
            margin-top: 2px;
        }
        
        .chat-header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .chat-header-actions button {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-assign {
            background: #28a745;
            color: #fff;
        }
        
        .btn-assign:hover {
            background: #218838;
        }
        
        .btn-leave {
            background: #f0f2f5;
            color: #65676b;
        }
        
        .btn-leave:hover {
            background: #e4e6eb;
        }
        
        .btn-close-chat {
            background: #dc3545;
            color: #fff;
        }
        
        .btn-close-chat:hover {
            background: #c82333;
        }
        
        /* Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #fafafa;
        }
        
        .message-group {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            max-width: 100%;
        }
        
        .message-group.own {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            align-self: flex-end;
            object-fit: cover;
        }
        
        .message-group.own .message-avatar {
            display: none;
        }
        
        .message-content {
            max-width: min(70%, 500px);
        }
        
        .message-sender {
            font-size: 12px;
            color: #65676b;
            margin-bottom: 2px;
            padding-left: 12px;
        }
        
        .message-group.own .message-sender {
            display: none;
        }
        
        .message-bubble {
            padding: 10px 14px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.4;
            word-wrap: break-word;
            word-break: break-word;
        }
        
        .message-group:not(.own) .message-bubble {
            background: #f0f2f5;
            color: #050505;
        }
        
        .message-group.own .message-bubble {
            background: #fff;
            color: #050505;
            border: 2px solid #EC802B;
        }
        
        .message-system {
            text-align: center;
            padding: 8px;
        }
        
        .message-system .message-bubble {
            display: inline-block;
            background: #e9ecef;
            color: #65676b;
            font-size: 12px;
            border-radius: 12px;
            padding: 6px 12px;
            border: none;
        }
        
        .message-time {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
            padding-left: 12px;
        }
        
        .message-group.own .message-time {
            text-align: right;
            padding-right: 12px;
            padding-left: 0;
        }
        
        /* Message actions */
        .message-actions {
            display: none;
            padding-top: 4px;
        }
        
        .message-group:hover .message-actions {
            display: flex;
            gap: 4px;
        }
        
        .msg-action-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            color: #65676b;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .msg-action-btn:hover {
            background: #e4e6eb;
            color: #EC802B;
        }
        
        /* Reply preview */
        .message-reply {
            background: rgba(0,0,0,0.05);
            padding: 6px 10px;
            border-radius: 10px;
            border-left: 3px solid #EC802B;
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        .message-reply-sender {
            font-weight: 600;
            font-size: 11px;
            color: #EC802B;
        }
        
        /* Chat Input Area Container */
        .chat-input-area {
            position: relative;
            flex-shrink: 0;
        }
        
        /* Quick Replies Panel */
        .quick-replies-panel {
            position: absolute;
            bottom: 100%;
            left: 16px;
            right: 16px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            z-index: 100;
            max-height: 280px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .quick-replies-header {
            padding: 12px 16px;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 1px solid #e4e6eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
        }
        
        .quick-replies-header button {
            width: 28px;
            height: 28px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quick-replies-header button:hover {
            background: #e4e6eb;
        }
        
        .quick-replies-list {
            max-height: 220px;
            overflow-y: auto;
        }
        
        .quick-reply-item {
            padding: 10px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.2s;
        }
        
        .quick-reply-item:hover {
            background: #f8f9fa;
        }
        
        .quick-reply-title {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 2px;
            color: #EC802B;
        }
        
        .quick-reply-preview {
            font-size: 12px;
            color: #65676b;
        }

        /* Chat Input */
        .chat-input-wrap {
            padding: 12px 16px;
            border-top: 1px solid #e4e6eb;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-shrink: 0;
            background: #fff;
            position: relative;
        }
        
        .chat-input-wrap.disabled {
            background: #f8f9fa;
        }
        
        .chat-input-disabled-msg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-size: 13px;
            background: #f8f9fa;
            padding: 6px 16px;
            border-radius: 20px;
        }
        
        .chat-input {
            flex: 1;
            position: relative;
        }
        
        .chat-input textarea {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #e4e6eb;
            background: #f8f9fa;
            border-radius: 20px;
            font-size: 14px;
            resize: none;
            max-height: 100px;
            min-height: 42px;
            font-family: inherit;
            line-height: 1.4;
        }
        
        .chat-input textarea:focus {
            outline: none;
            border-color: #EC802B;
            background: #fff;
        }
        
        .chat-input textarea:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }
        
        .chat-send-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .chat-send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(236, 128, 43, 0.4);
        }
        
        .chat-send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Monitor badge */
        .monitor-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: #fff;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            padding: 20px;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e4e6eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #f0f2f5;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .modal-close:hover {
            background: #e4e6eb;
        }
        
        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #e4e6eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            color: #333;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #EC802B;
        }
        
        .member-list {
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid #e4e6eb;
            border-radius: 8px;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            gap: 12px;
            cursor: pointer;
            transition: background 0.15s;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .member-item:hover {
            background: #f8f9fa;
        }
        
        .member-item.selected {
            background: #FFF5EC;
        }
        
        .member-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #EC802B;
            cursor: pointer;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        
        .member-role {
            font-size: 12px;
            color: #65676b;
        }
        
        .btn-primary {
            background: #EC802B;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary:hover {
            background: #d97326;
        }
        
        .btn-secondary {
            background: #f0f2f5;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-secondary:hover {
            background: #e4e6eb;
        }
        
        /* Loading */
        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #65676b;
            flex-direction: column;
            gap: 10px;
        }
        
        .loading-spinner i {
            animation: spin 1s linear infinite;
            font-size: 24px;
            color: #EC802B;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #65676b;
        }
        
        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 12px;
            display: block;
        }
        
        /* Mobile sidebar toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #EC802B;
            color: #fff;
            border: none;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-size: 20px;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .chat-sidebar {
                width: 300px;
                min-width: 260px;
            }
            
            .message-content {
                max-width: 80%;
            }
        }
        
        @media (max-width: 768px) {
            .petshop-chat-wrap {
                flex-direction: column;
                height: calc(100vh - 46px);
            }
            
            .chat-sidebar {
                width: 100%;
                max-width: 100%;
                height: 100%;
                position: absolute;
                top: 0;
                left: 0;
                z-index: 100;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .chat-sidebar.open {
                transform: translateX(0);
            }
            
            .chat-main {
                height: 100%;
            }
            
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .chat-header {
                padding: 10px 12px;
            }
            
            .chat-header .conv-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
            
            .chat-header-title {
                font-size: 14px;
            }
            
            .chat-header-actions button {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .chat-messages {
                padding: 12px;
            }
            
            .message-content {
                max-width: 85%;
            }
            
            .message-bubble {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .chat-input-wrap {
                padding: 10px 12px;
            }
            
            .chat-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 4px;
            }
            
            .chat-tab {
                flex-shrink: 0;
            }
            
            .modal-content {
                max-height: 90vh;
                margin: 10px;
            }
            
            .back-btn {
                display: flex;
                width: 36px;
                height: 36px;
                border: none;
                background: #f0f2f5;
                border-radius: 50%;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                margin-right: 8px;
                flex-shrink: 0;
            }
        }
        
        @media (min-width: 769px) {
            .back-btn {
                display: none;
            }
        }
        
        /* Info Panel */
        .chat-info-panel {
            width: 300px;
            min-width: 250px;
            background: #fff;
            border-left: 1px solid #e4e6eb;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        
        .info-panel-header {
            padding: 16px;
            border-bottom: 1px solid #e4e6eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-panel-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .info-close-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .info-panel-content {
            padding: 16px;
            overflow-y: auto;
            flex: 1;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-section-title {
            font-size: 12px;
            color: #65676b;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }
        
        .info-item-icon {
            width: 32px;
            height: 32px;
            background: #f0f2f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65676b;
        }
        
        .info-item-text {
            flex: 1;
        }
        
        .info-item-label {
            font-size: 12px;
            color: #65676b;
        }
        
        .info-item-value {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* File upload button */
        .chat-input-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            padding-right: 8px;
        }
        
        .chat-action-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65676b;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .chat-action-btn:hover {
            background: #f0f2f5;
            color: #EC802B;
        }
        
        .chat-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Reply preview */
        .reply-preview {
            padding: 8px 16px;
            background: #f8f9fa;
            border-left: 3px solid #EC802B;
            margin: 8px 16px 0;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reply-preview-content {
            flex: 1;
            min-width: 0;
        }
        
        .reply-preview-name {
            font-size: 12px;
            font-weight: 600;
            color: #EC802B;
        }
        
        .reply-preview-text {
            font-size: 13px;
            color: #65676b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .reply-preview-close {
            width: 24px;
            height: 24px;
            border: none;
            background: #e4e6eb;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        /* Star button */
        .star-btn {
            color: #ccc;
            transition: color 0.2s;
        }
        
        .star-btn.starred {
            color: #ffc107;
        }
        
        .star-btn:hover {
            color: #ffc107;
        }
        
        /* Attachment preview in message */
        .message-attachment {
            margin-top: 8px;
        }
        
        .message-attachment img {
            max-width: 250px;
            max-height: 200px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .message-attachment video {
            max-width: 280px;
            max-height: 200px;
            border-radius: 8px;
        }
        
        .message-attachment-file {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f0f2f5;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
        }
        
        .message-attachment-file i {
            font-size: 20px;
            color: #EC802B;
        }
        
        .message-attachment-file span {
            font-size: 13px;
        }
        
        /* Upload preview */
        .upload-preview {
            padding: 8px 16px;
            background: #f8f9fa;
            margin: 8px 16px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-preview img {
            max-width: 60px;
            max-height: 60px;
            border-radius: 4px;
        }
        
        .upload-preview-info {
            flex: 1;
            min-width: 0;
        }
        
        .upload-preview-name {
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .upload-preview-size {
            font-size: 11px;
            color: #65676b;
        }
        
        /* ==========================================
           MESSENGER-LIKE FEATURES CSS
           ========================================== */
        
        /* Reactions */
        .message-reactions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 4px 0;
        }
        
        .reaction-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            background: #f0f2f5;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .reaction-badge:hover,
        .reaction-badge.active {
            background: #fff0e6;
            border-color: #EC802B;
        }
        
        .reaction-picker {
            position: fixed;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 24px;
            padding: 6px 10px;
            display: flex;
            gap: 4px;
            z-index: 10001;
            animation: fadeInScale 0.2s ease;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .reaction-picker button {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            font-size: 24px;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .reaction-picker button:hover {
            background: #f0f2f5;
            transform: scale(1.2);
        }
        
        /* Pinned messages */
        .message-group.pinned {
            background: #fff8f0;
            border-radius: 12px;
            padding: 4px;
            margin: 2px -4px;
        }
        
        .pinned-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #EC802B;
            padding: 2px 8px;
            background: #fff0e6;
            border-radius: 10px;
            margin-bottom: 4px;
        }
        
        .message-deleted {
            text-align: center;
            color: #999;
            font-size: 12px;
            font-style: italic;
            padding: 8px 16px;
        }
        
        .edited-indicator {
            font-size: 11px;
            color: #999;
            font-style: italic;
        }
        
        .message-group.highlight {
            animation: highlightPulse 2s ease;
        }
        
        @keyframes highlightPulse {
            0%, 100% { background: transparent; }
            50% { background: #fff8e6; }
        }
        
        /* Edit message form */
        .edit-message-form textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #EC802B;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            min-height: 60px;
        }
        
        .edit-message-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 8px;
        }
        
        .edit-message-actions button {
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .edit-message-actions .btn-cancel {
            background: #e4e6eb;
            color: #333;
        }
        
        .edit-message-actions .btn-save {
            background: #EC802B;
            color: #fff;
        }
        
        /* Typing indicator */
        .typing-indicator {
            padding: 8px 16px 16px;
        }
        
        .typing-content {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #65676b;
        }
        
        .typing-dots {
            display: flex;
            gap: 3px;
        }
        
        .typing-dots span {
            width: 6px;
            height: 6px;
            background: #65676b;
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }
        
        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
        
        /* Search panel */
        .chat-search-panel {
            position: absolute;
            top: 60px;
            left: 0;
            right: 0;
            background: #fff;
            padding: 12px 16px;
            border-bottom: 1px solid #e4e6eb;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .chat-search-panel input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            padding: 8px 12px;
            background: #f0f2f5;
            border-radius: 20px;
        }
        
        .chat-search-input {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chat-search-input i {
            color: #65676b;
        }
        
        .chat-search-input button {
            border: none;
            background: none;
            cursor: pointer;
            color: #65676b;
            font-size: 18px;
        }
        
        .chat-search-results {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 12px;
        }
        
        .search-result-item {
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .search-result-sender {
            font-weight: 500;
            font-size: 12px;
            color: #EC802B;
        }
        
        .search-result-content {
            font-size: 13px;
            margin: 4px 0;
        }
        
        .search-result-content mark {
            background: #fff0cc;
            padding: 0 2px;
        }
        
        .search-result-time {
            font-size: 11px;
            color: #999;
        }
        
        .search-hint,
        .search-no-result {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 13px;
        }
        
        /* Enhanced info panel */
        .info-tabs {
            display: flex;
            border-bottom: 1px solid #e4e6eb;
            margin: -16px -16px 16px;
            padding: 0 8px;
        }
        
        .info-tab {
            flex: 1;
            padding: 12px 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 12px;
            color: #65676b;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .info-tab:hover {
            background: #f8f9fa;
        }
        
        .info-tab.active {
            color: #EC802B;
            border-bottom-color: #EC802B;
        }
        
        .info-tab i {
            margin-right: 4px;
        }
        
        .info-stats {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .info-stat {
            flex: 1;
            text-align: center;
        }
        
        .info-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #EC802B;
        }
        
        .info-stat-label {
            font-size: 11px;
            color: #65676b;
        }
        
        .info-customer-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        
        .info-customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .info-customer-name {
            font-weight: 600;
            font-size: 15px;
        }
        
        .info-customer-meta {
            font-size: 12px;
            color: #65676b;
        }
        
        .info-customer-meta i {
            margin-right: 4px;
        }
        
        .info-customer-stats {
            margin: 8px 0;
        }
        
        .info-orders {
            margin-top: 8px;
        }
        
        .info-order {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 12px;
        }
        
        .info-order:hover {
            background: #f8f9fa;
        }
        
        .info-order-id {
            font-weight: 500;
            color: #EC802B;
        }
        
        .info-order-total {
            margin-left: auto;
            font-weight: 500;
        }
        
        .info-order-date {
            color: #999;
            font-size: 11px;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        
        /* Media container */
        .info-media-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        /* Media grid */
        .info-media-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }
        
        .info-media-item {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        
        .info-media-item img,
        .info-media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-media-item.video i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .info-media-file {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-size: 12px;
        }
        
        .info-media-file i {
            font-size: 20px;
            color: #EC802B;
        }
        
        /* Pinned list */
        .info-pinned-item {
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            border-left: 3px solid #EC802B;
            margin-bottom: 8px;
            background: #f8f9fa;
        }
        
        .info-pinned-item:hover {
            background: #fff0e6;
        }
        
        .info-pinned-sender {
            font-weight: 500;
            font-size: 12px;
            color: #EC802B;
        }
        
        .info-pinned-content {
            font-size: 13px;
            margin: 4px 0;
            color: #333;
            word-break: break-word;
        }
        
        .info-pinned-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        
        .info-pinned-time {
            font-size: 11px;
            color: #999;
        }
        
        .info-pinned-media {
            width: 100%;
            max-width: 120px;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 4px;
            position: relative;
        }
        
        .info-pinned-media img,
        .info-pinned-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-pinned-media.video i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .info-pinned-file {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #333;
            margin-top: 4px;
        }
        
        .info-pinned-file i {
            color: #EC802B;
        }
        
        .empty-state-small {
            text-align: center;
            padding: 30px 16px;
            color: #999;
            font-size: 13px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }
        
        /* Pinned Messages Bar */
        .pinned-messages-bar {
            background: linear-gradient(90deg, #fff8f0, #fff);
            border-bottom: 1px solid #ffd9b8;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .pinned-messages-bar:hover {
            background: #fff0e6;
        }
        
        .pinned-bar-icon {
            color: #EC802B;
            font-size: 16px;
        }
        
        .pinned-bar-content {
            flex: 1;
            min-width: 0;
        }
        
        .pinned-bar-label {
            font-size: 11px;
            color: #EC802B;
            font-weight: 600;
        }
        
        .pinned-bar-text {
            font-size: 13px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pinned-bar-count {
            background: #EC802B;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .pinned-bar-nav {
            display: flex;
            gap: 4px;
        }
        
        .pinned-bar-nav button {
            width: 24px;
            height: 24px;
            border: none;
            background: #e4e6eb;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .pinned-bar-nav button:hover {
            background: #EC802B;
            color: #fff;
        }
        
        /* Emoji & Sticker Picker */
        .emoji-picker-panel,
        .sticker-picker-panel {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 16px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            z-index: 200;
            max-height: 360px;
            overflow: hidden;
        }
        
        .emoji-picker-panel {
            width: 340px;
        }
        
        .sticker-picker-panel {
            width: 340px;
        }
        
        .emoji-picker-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e4e6eb;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .emoji-picker-header button {
            width: 28px;
            height: 28px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .emoji-picker-header button:hover {
            background: #e4e6eb;
        }
        
        .emoji-picker-search {
            padding: 8px 12px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .emoji-picker-search input {
            width: 100%;
            padding: 6px 12px;
            border: 1px solid #e4e6eb;
            border-radius: 20px;
            font-size: 13px;
            outline: none;
        }
        
        .emoji-picker-search input:focus {
            border-color: #EC802B;
        }
        
        .emoji-picker-tabs {
            display: flex;
            padding: 4px;
            gap: 2px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .emoji-picker-tabs button {
            flex: 1;
            padding: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .emoji-picker-tabs button:hover,
        .emoji-picker-tabs button.active {
            background: #f0f2f5;
        }
        
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 2px;
            padding: 8px;
            max-height: 220px;
            overflow-y: auto;
        }
        
        .emoji-item {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 6px;
            font-size: 22px;
            transition: all 0.15s;
        }
        
        .emoji-item:hover {
            background: #fff0e6;
            transform: scale(1.25);
        }
        
        .sticker-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 12px;
            max-height: 270px;
            overflow-y: auto;
        }
        
        .sticker-item {
            aspect-ratio: 1;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.2s;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .sticker-item:hover {
            border-color: #EC802B;
            transform: scale(1.08);
            background: #fff0e6;
        }
        
        .sticker-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .sticker-picker-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e4e6eb;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sticker-picker-header button {
            width: 28px;
            height: 28px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sticker-picker-header button:hover {
            background: #e4e6eb;
        }
        
        .sticker-category {
            grid-column: 1 / -1;
            padding: 8px 4px 4px;
            font-size: 12px;
            font-weight: 600;
            color: #65676b;
            border-bottom: 1px solid #e4e6eb;
            margin-bottom: 4px;
        }
        
        /* Group Management in Info Panel */
        .info-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .info-action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: none;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #333;
            transition: all 0.2s;
            text-align: left;
            width: 100%;
        }
        
        .info-action-btn:hover {
            background: #f0f2f5;
        }
        
        .info-action-btn i {
            width: 20px;
            text-align: center;
            color: #65676b;
        }
        
        .info-action-btn.danger {
            color: #dc3545;
        }
        
        .info-action-btn.danger i {
            color: #dc3545;
        }
        
        .info-action-btn.danger:hover {
            background: #fff0f0;
        }
        
        /* Info Notice for Default Groups */
        .info-notice {
            padding: 12px;
            background: #fff8e6;
            border: 1px solid #ffe0a6;
            border-radius: 8px;
            font-size: 12px;
            color: #997000;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .info-notice i {
            color: #EC802B;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        /* Media History by Date */
        .media-date-group {
            margin-bottom: 16px;
        }
        
        .media-date-header {
            font-size: 12px;
            font-weight: 600;
            color: #65676b;
            padding: 8px 0;
            border-bottom: 1px solid #e4e6eb;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .media-date-header i {
            color: #EC802B;
        }
        
        .media-type-tabs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            margin-bottom: 16px;
            background: #f0f2f5;
            padding: 4px;
            border-radius: 10px;
        }
        
        .media-type-tab {
            padding: 8px 4px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.2s;
            text-align: center;
            color: #65676b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        
        .media-type-tab i {
            font-size: 16px;
        }
        
        .media-type-tab:hover {
            background: rgba(255,255,255,0.7);
            color: #333;
        }
        
        .media-type-tab.active {
            background: #EC802B;
            color: #fff;
        }
        
        .media-content {
            max-height: calc(100vh - 400px);
            overflow-y: auto;
        }
        
        .file-list-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .file-list-item:hover {
            background: #f0f2f5;
        }
        
        .file-list-icon {
            width: 40px;
            height: 40px;
            background: #EC802B;
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .file-list-info {
            flex: 1;
            min-width: 0;
        }
        
        .file-list-name {
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-list-meta {
            font-size: 11px;
            color: #999;
        }
        
        /* Add member modal */
        .member-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .member-item:hover {
            background: #f8f9fa;
        }
        
        .member-item img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
        }
        
        .member-item-info {
            flex: 1;
        }
        
        .member-item-name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .member-item-role {
            font-size: 12px;
            color: #65676b;
        }
        
        .member-item-action {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .member-item-action.remove {
            background: #fff0f0;
            color: #dc3545;
        }
        
        .member-item-action.add {
            background: #e6f7e6;
            color: #28a745;
        }
        
        /* Chat Modal */
        .chat-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10002;
        }
        
        .chat-modal {
            background: #fff;
            border-radius: 12px;
            width: 400px;
            max-width: 90vw;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .chat-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .chat-modal-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .chat-modal-header button {
            width: 32px;
            height: 32px;
            border: none;
            background: #f0f2f5;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-modal-header button:hover {
            background: #e4e6eb;
        }
        
        .chat-modal-body {
            padding: 16px 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        @media (max-width: 1100px) {
            .chat-info-panel {
                display: none !important;
            }
        }
    </style>
    
    <div class="petshop-chat-wrap">
        <!-- Sidebar -->
        <div class="chat-sidebar" id="chatSidebar">
            <div class="chat-sidebar-header">
                <h2><i class="bi bi-chat-dots-fill"></i> Chat</h2>
                <div class="chat-tabs">
                    <button class="chat-tab active" data-type="all">Tất cả</button>
                    <?php if (petshop_is_chat_staff()): ?>
                    <button class="chat-tab" data-type="support">Hỗ trợ KH</button>
                    <button class="chat-tab" data-type="group">Nhóm</button>
                    <button class="chat-tab" data-type="direct">Riêng</button>
                    <?php endif; ?>
                </div>
                
                <!-- Support chat filters (shown when support tab active) -->
                <?php if (petshop_is_chat_staff()): ?>
                <div class="support-filters" id="supportFilters" style="display:none; margin-top: 12px;">
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
                        <?php if (petshop_can_monitor_chat()): ?>
                        <button class="filter-btn active" data-filter="all">Tất cả</button>
                        <?php endif; ?>
                        <button class="filter-btn <?php echo !petshop_can_monitor_chat() ? 'active' : ''; ?>" data-filter="mine">Của tôi</button>
                        <button class="filter-btn" data-filter="new">Chờ nhận</button>
                        <button class="filter-btn" data-filter="starred" title="Đã đánh dấu"><i class="bi bi-star-fill" style="color:#ffc107"></i></button>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if (petshop_can_monitor_chat()): ?>
                        <select id="staffFilter" class="filter-select" style="flex:1; min-width: 100px;">
                            <option value="">-- Nhân viên --</option>
                        </select>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="chat-search">
                <div class="chat-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="chatSearch" class="chat-search-input" placeholder="Tìm kiếm...">
                </div>
            </div>
            
            <div class="conversation-list" id="conversationList">
                <div class="loading-spinner">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Đang tải...</span>
                </div>
            </div>
            
            <?php if (petshop_is_chat_staff()): ?>
            <div class="chat-sidebar-footer">
                <button class="btn-new-group" onclick="showCreateGroupModal()">
                    <i class="bi bi-people-fill"></i> Tạo nhóm
                </button>
                <button class="btn-new-chat" onclick="showNewChatModal()">
                    <i class="bi bi-chat-left-text"></i> Chat mới
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main" id="chatMain">
            <div class="chat-main-empty">
                <i class="bi bi-chat-square-dots"></i>
                <h3>Chọn cuộc trò chuyện</h3>
                <p>Chọn từ danh sách bên trái để bắt đầu chat</p>
            </div>
        </div>
        
        <!-- Conversation Info Panel -->
        <div class="chat-info-panel" id="chatInfoPanel" style="display: none;">
            <div class="info-panel-header">
                <h4>Thông tin</h4>
                <button class="info-close-btn" onclick="toggleInfoPanel()"><i class="bi bi-x"></i></button>
            </div>
            <div class="info-panel-content" id="infoPanelContent">
                <!-- Dynamic content -->
            </div>
        </div>
        
        <!-- Mobile toggle button -->
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
    </div>
    
    <!-- Create Group Modal -->
    <div class="modal-overlay" id="createGroupModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-people-fill"></i> Tạo nhóm chat</h3>
                <button class="modal-close" onclick="hideCreateGroupModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên nhóm</label>
                    <input type="text" id="groupName" placeholder="Nhập tên nhóm...">
                </div>
                <div class="form-group">
                    <label>Chọn thành viên</label>
                    <div class="member-list" id="memberList">
                        <div class="loading-spinner">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="hideCreateGroupModal()">Hủy</button>
                <button class="btn-primary" onclick="createGroup()">
                    <i class="bi bi-check-lg"></i> Tạo nhóm
                </button>
            </div>
        </div>
    </div>
    
    <!-- New Chat Modal (1-1) -->
    <div class="modal-overlay" id="newChatModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-chat-left-text"></i> Chat mới</h3>
                <button class="modal-close" onclick="hideNewChatModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Chọn người để chat</label>
                    <div class="member-list" id="directChatList">
                        <div class="loading-spinner">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let currentConversationId = <?php echo $current_conv_id ?: 'null'; ?>;
        let currentFilter = 'all';
        let pollInterval = null;
        let lastMessageId = 0;
        const currentUserId = <?php echo get_current_user_id(); ?>;
        const isStaff = <?php echo petshop_is_chat_staff() ? 'true' : 'false'; ?>;
        const canMonitor = <?php echo petshop_can_monitor_chat() ? 'true' : 'false'; ?>;
        
        // Đọc filter từ URL nếu có
        const urlParams = new URLSearchParams(window.location.search);
        const urlFilter = urlParams.get('filter');
        if (urlFilter && ['support', 'group', 'direct', 'internal'].includes(urlFilter)) {
            currentFilter = urlFilter === 'internal' ? 'group' : urlFilter;
            // Activate corresponding tab
            $(document).ready(function() {
                $('.chat-tab').removeClass('active');
                $(`.chat-tab[data-type="${currentFilter}"]`).addClass('active');
            });
        }
        
        // Mobile sidebar toggle
        window.toggleSidebar = function() {
            $('#chatSidebar').toggleClass('open');
        };
        
        // Support filters state - Nhân viên thường mặc định "Của tôi", Admin mặc định "Tất cả"
        let supportFilter = canMonitor ? 'all' : 'mine';
        let starredOnly = false;
        let staffFilter = 0;
        let staffList = [];
        let replyToMessage = null;
        let pendingAttachment = null;
        let infoPanelOpen = false;
        
        // Load conversations
        function loadConversations() {
            // If showing support conversations, use filtered endpoint
            if (currentFilter === 'support') {
                loadSupportConversations();
            } else {
                $.get(ajaxurl, {
                    action: 'petshop_get_conversations',
                    type: currentFilter === 'all' ? '' : currentFilter
                }, function(response) {
                    if (response.success) {
                        renderConversations(response.data);
                    }
                });
            }
        }
        
        // Load support conversations with filters
        function loadSupportConversations() {
            $.get(ajaxurl, {
                action: 'petshop_get_filtered_conversations',
                filter: supportFilter,
                starred: starredOnly ? 1 : 0,
                staff_id: staffFilter
            }, function(response) {
                if (response.success) {
                    staffList = response.data.staff_list || [];
                    
                    // Populate staff filter dropdown
                    if (response.data.is_admin && staffList.length > 0) {
                        const $staffSelect = $('#staffFilter');
                        if ($staffSelect.length && $staffSelect.find('option').length <= 1) {
                            staffList.forEach(function(s) {
                                $staffSelect.append(`<option value="${s.id}">${escapeHtml(s.name)}</option>`);
                            });
                        }
                    }
                    
                    renderSupportConversations(response.data.conversations);
                }
            });
        }
        
        // Render support conversations with star
        function renderSupportConversations(conversations) {
            const $list = $('#conversationList');
            $list.empty();
            
            if (conversations.length === 0) {
                $list.html(`
                    <div class="empty-state">
                        <i class="bi bi-chat-square"></i>
                        <p>Không có cuộc trò chuyện nào</p>
                    </div>
                `);
                return;
            }
            
            conversations.forEach(function(conv) {
                const initial = conv.customer_name ? conv.customer_name.charAt(0).toUpperCase() : '?';
                
                // Status badge
                let badge = '';
                if (conv.assigned_to === null) {
                    badge = '<span class="conv-badge open">Mới</span>';
                } else if (conv.is_mine) {
                    badge = '<span class="conv-badge mine">Của tôi</span>';
                } else {
                    badge = '<span class="conv-badge assigned">' + escapeHtml(conv.assigned_name || '') + '</span>';
                }
                
                // Star indicator
                let starIcon = conv.is_starred ? 
                    '<i class="bi bi-star-fill" style="color:#ffc107;font-size:12px;"></i>' : '';
                
                // Unread indicator
                let unreadBadge = '';
                if (conv.unread_count > 0) {
                    unreadBadge = `<span class="unread-count">${conv.unread_count}</span>`;
                }
                
                const timeAgo = conv.last_message_at ? formatTimeAgo(conv.last_message_at) : '';
                
                const $item = $(`
                    <div class="conversation-item ${currentConversationId == conv.id ? 'active' : ''} ${conv.is_mine ? 'my-conv' : ''}" 
                         data-id="${conv.id}" data-type="support" data-starred="${conv.is_starred ? 1 : 0}">
                        <div class="conv-avatar support">${initial}</div>
                        <div class="conv-info">
                            <div class="conv-title">
                                ${escapeHtml(conv.customer_name || 'Khách hàng')}
                                ${starIcon}
                            </div>
                            <div class="conv-preview">${escapeHtml(conv.last_message_preview || '')}</div>
                        </div>
                        <div class="conv-meta">
                            <div class="conv-time">${timeAgo}</div>
                            ${badge}
                            ${unreadBadge}
                        </div>
                    </div>
                `);
                
                $item.click(function() {
                    openConversation(conv.id);
                    if (window.innerWidth <= 768) {
                        $('#chatSidebar').removeClass('open');
                    }
                });
                
                $list.append($item);
            });
        }
        
        function renderConversations(conversations) {
            const $list = $('#conversationList');
            $list.empty();
            
            if (conversations.length === 0) {
                $list.html(`
                    <div class="empty-state">
                        <i class="bi bi-chat-square"></i>
                        <p>Chưa có cuộc trò chuyện nào</p>
                    </div>
                `);
                return;
            }
            
            // Separate default staff group from others
            const defaultGroup = conversations.find(c => c.is_default_staff_group);
            const otherConversations = conversations.filter(c => !c.is_default_staff_group);
            
            // Render "Chung" section - Default staff group always at top
            if (defaultGroup) {
                $list.append(`
                    <div class="conversation-section">
                        <div class="section-header">
                            <i class="bi bi-people-fill"></i> Chung
                        </div>
                    </div>
                `);
                renderConversationItem(defaultGroup, $list);
            }
            
            // Render "Khác" section - Other conversations
            if (otherConversations.length > 0) {
                $list.append(`
                    <div class="conversation-section">
                        <div class="section-header">
                            <i class="bi bi-chat-left-text"></i> Khác
                        </div>
                    </div>
                `);
                
                otherConversations.forEach(function(conv) {
                    renderConversationItem(conv, $list);
                });
            }
        }
        
        function renderConversationItem(conv, $list) {
            const avatarClass = conv.type || '';
            const initial = conv.title ? conv.title.charAt(0).toUpperCase() : '?';
            const isDefaultStaffGroup = conv.is_default_staff_group;
            
            let badge = '';
            if (conv.type === 'support') {
                if (conv.status === 'open') {
                    badge = '<span class="conv-badge open">Chờ nhận</span>';
                } else if (conv.is_mine) {
                    badge = '<span class="conv-badge mine">Đang trả lời</span>';
                } else if (conv.assigned_to) {
                    badge = '<span class="conv-badge assigned">' + escapeHtml(conv.assigned_name || '') + '</span>';
                }
            }
            
            // Default staff group badge
            if (isDefaultStaffGroup) {
                badge = '<span class="conv-badge default-group"><i class="bi bi-pin-fill"></i></span>';
            }
            
            const timeAgo = conv.last_message_at ? formatTimeAgo(conv.last_message_at, conv.last_message_timestamp) : '';
            const preview = conv.last_message ? conv.last_message.content : 'Chưa có tin nhắn';
            
            const $item = $(`
                <div class="conversation-item ${currentConversationId == conv.id ? 'active' : ''} ${isDefaultStaffGroup ? 'default-staff-group' : ''}" 
                     data-id="${conv.id}" data-type="${conv.type}" data-is-default="${isDefaultStaffGroup ? '1' : '0'}">
                    <div class="conv-avatar ${avatarClass}">${initial}</div>
                    <div class="conv-info">
                        <div class="conv-title">${escapeHtml(conv.title || 'Không có tiêu đề')}</div>
                        <div class="conv-preview">${escapeHtml(preview)}</div>
                    </div>
                    <div class="conv-meta">
                        <div class="conv-time">${timeAgo}</div>
                        ${badge}
                    </div>
                </div>
            `);
            
            $item.click(function() {
                openConversation(conv.id);
                // Close sidebar on mobile
                if (window.innerWidth <= 768) {
                    $('#chatSidebar').removeClass('open');
                }
            });
            
            $list.append($item);
        }
        
        function openConversation(convId) {
            currentConversationId = convId;
            
            // Update active state
            $('.conversation-item').removeClass('active');
            $(`.conversation-item[data-id="${convId}"]`).addClass('active');
            
            // Stop old polling
            if (pollInterval) {
                clearInterval(pollInterval);
            }
            
            // Load messages
            loadMessages(convId);
            
            // Start polling
            pollInterval = setInterval(function() {
                pollNewMessages(convId);
            }, 3000);
        }
        
        function loadMessages(convId) {
            $('#chatMain').html(`
                <div class="loading-spinner" style="flex:1; display:flex;">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Đang tải tin nhắn...</span>
                </div>
            `);
            
            $.get(ajaxurl, {
                action: 'petshop_get_messages',
                conversation_id: convId
            }, function(response) {
                if (response.success) {
                    renderChatArea(response.data);
                } else {
                    $('#chatMain').html(`
                        <div class="empty-state" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                            <i class="bi bi-exclamation-circle"></i>
                            <p>${response.data || 'Không thể tải tin nhắn'}</p>
                        </div>
                    `);
                }
            }).fail(function() {
                $('#chatMain').html(`
                    <div class="empty-state" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                        <i class="bi bi-wifi-off"></i>
                        <p>Lỗi kết nối. Vui lòng thử lại.</p>
                    </div>
                `);
            });
        }
        
        function renderChatArea(data) {
            const conv = data.conversation;
            const messages = data.messages;
            const participants = data.participants || [];
            const canSend = conv.can_send;
            
            let headerActions = '';
            const isStarred = conv.is_starred || false;
            
            // Search button - always available
            const searchBtn = `<button class="chat-action-btn" onclick="toggleSearch()" title="Tìm kiếm">
                <i class="bi bi-search"></i>
            </button>`;
            
            if (conv.type === 'support' && isStaff) {
                // Star button
                const starBtn = `<button class="chat-action-btn star-btn ${isStarred ? 'starred' : ''}" onclick="toggleStar(${conv.id})" title="Đánh dấu quan trọng">
                    <i class="bi bi-star${isStarred ? '-fill' : ''}"></i>
                </button>`;
                
                // Info panel button
                const infoBtn = `<button class="chat-action-btn" onclick="toggleInfoPanel()" title="Thông tin">
                    <i class="bi bi-info-circle"></i>
                </button>`;
                
                if (conv.status === 'open') {
                    headerActions = `${searchBtn} ${starBtn}
                        <button class="btn-assign" onclick="assignConversation(${conv.id})">
                            <i class="bi bi-check-lg"></i> Nhận trả lời
                        </button>
                        ${infoBtn}`;
                } else if (conv.assigned_to == currentUserId) {
                    headerActions = `${searchBtn} ${starBtn}
                        <button class="btn-leave" onclick="leaveConversation(${conv.id})">
                            <i class="bi bi-arrow-left-right"></i> Chuyển
                        </button>
                        ${infoBtn}`;
                } else {
                    headerActions = `${searchBtn} ${starBtn} ${infoBtn}`;
                }
            } else if (conv.type !== 'support') {
                // Info panel button for internal chat
                headerActions = `${searchBtn} <button class="chat-action-btn" onclick="toggleInfoPanel()" title="Thông tin">
                    <i class="bi bi-info-circle"></i>
                </button>`;
            } else {
                // For customer view
                headerActions = searchBtn;
            }
            
            // Monitor badge
            let monitorBadge = '';
            if (canMonitor && conv.type === 'support' && conv.assigned_to && conv.assigned_to != currentUserId) {
                monitorBadge = '<span class="monitor-badge"><i class="bi bi-eye"></i> Giám sát</span>';
            }
            
            let statusText = '';
            if (conv.type === 'support') {
                if (conv.status === 'open') {
                    statusText = '<span style="color:#ffc107">● Đang chờ nhân viên</span>';
                } else if (conv.status === 'assigned') {
                    statusText = '<span style="color:#28a745">● Đang hỗ trợ</span>';
                } else if (conv.status === 'closed') {
                    statusText = '<span style="color:#6c757d">● Đã đóng</span>';
                }
            } else {
                statusText = participants.length + ' thành viên';
            }
            
            let html = `
                <div class="chat-header">
                    <button class="back-btn" onclick="toggleSidebar()">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="conv-avatar ${conv.type || ''}">${conv.title ? conv.title.charAt(0).toUpperCase() : '?'}</div>
                    <div class="chat-header-info">
                        <div class="chat-header-title">${escapeHtml(conv.title || '')} ${monitorBadge}</div>
                        <div class="chat-header-status">${statusText}</div>
                    </div>
                    <div class="chat-header-actions">${headerActions}</div>
                </div>
                <div id="pinnedMessagesBar" class="pinned-messages-bar" style="display:none;"></div>
                <div class="chat-messages" id="chatMessages">
            `;
            
            // Load pinned messages for top bar
            loadPinnedBar(conv.id);
            
            if (messages.length === 0) {
                html += `
                    <div class="empty-state">
                        <i class="bi bi-chat-left-dots"></i>
                        <p>Chưa có tin nhắn. Hãy bắt đầu cuộc trò chuyện!</p>
                    </div>
                `;
            } else {
                // Render messages
                let lastSenderId = null;
                
                messages.forEach(function(msg, index) {
                    if (msg.is_deleted) {
                        html += `<div class="message-deleted"><i class="bi bi-x-circle"></i> Tin nhắn đã bị xóa</div>`;
                        return;
                    }
                    
                    if (msg.message_type === 'system') {
                        html += `
                            <div class="message-system">
                                <div class="message-bubble">${escapeHtml(msg.content)}</div>
                            </div>
                        `;
                        lastSenderId = null;
                    } else {
                        const isOwn = msg.sender_id == currentUserId;
                        const showSender = msg.sender_id !== lastSenderId && !isOwn;
                        
                        // Attachment HTML
                        let attachmentHtml = '';
                        if (msg.attachment_url) {
                            if (msg.message_type === 'image') {
                                attachmentHtml = `<div class="message-attachment"><img src="${msg.attachment_url}" onclick="window.open(this.src, '_blank')" alt=""></div>`;
                            } else if (msg.message_type === 'video') {
                                attachmentHtml = `<div class="message-attachment"><video src="${msg.attachment_url}" controls></video></div>`;
                            } else if (msg.message_type === 'file') {
                                attachmentHtml = `<div class="message-attachment"><a href="${msg.attachment_url}" target="_blank" class="message-attachment-file"><i class="bi bi-file-earmark"></i><span>${escapeHtml(msg.attachment_name || 'File')}</span></a></div>`;
                            }
                        }
                        
                        // Reactions HTML
                        let reactionsHtml = '';
                        if (msg.reactions) {
                            const reactions = typeof msg.reactions === 'string' ? JSON.parse(msg.reactions) : msg.reactions;
                            if (Object.keys(reactions).length > 0) {
                                reactionsHtml = '<div class="message-reactions">';
                                const emojiMap = {like:'👍',love:'❤️',haha:'😄',wow:'😮',sad:'😢',angry:'😠'};
                                for (const [type, users] of Object.entries(reactions)) {
                                    if (users && users.length > 0) {
                                        const hasMe = users.includes(currentUserId);
                                        reactionsHtml += `<span class="reaction-badge ${hasMe ? 'active' : ''}" onclick="toggleReaction(${msg.id},'${type}')">${emojiMap[type] || type} ${users.length}</span>`;
                                    }
                                }
                                reactionsHtml += '</div>';
                            }
                        }
                        
                        // Message actions with context menu
                        const canEdit = isOwn && !msg.attachment_url;
                        const canDelete = isOwn || isStaff;
                        const canPin = isStaff;
                        
                        // Escape for HTML attributes
                        const safeSenderName = (msg.sender_name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const safePreview = (msg.content || '').substring(0,50).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        
                        let actionsHtml = `
                            <div class="message-actions">
                                <button class="msg-action-btn" onclick="showReactionPicker(event,${msg.id})" title="Thả cảm xúc"><i class="bi bi-emoji-smile"></i></button>
                                <button class="msg-action-btn reply-btn" data-msg-id="${msg.id}" data-sender="${safeSenderName}" data-preview="${safePreview}" title="Trả lời"><i class="bi bi-reply"></i></button>
                                ${canEdit ? `<button class="msg-action-btn" onclick="editMessage(${msg.id})" title="Sửa"><i class="bi bi-pencil"></i></button>` : ''}
                                ${canDelete ? `<button class="msg-action-btn" onclick="deleteMessage(${msg.id})" title="Xóa"><i class="bi bi-trash"></i></button>` : ''}
                                ${canPin ? `<button class="msg-action-btn" onclick="pinMessage(${msg.id})" title="${msg.is_pinned ? 'Bỏ ghim' : 'Ghim'}"><i class="bi bi-pin${msg.is_pinned ? '-fill' : ''}"></i></button>` : ''}
                            </div>
                        `;
                        
                        // Pin badge
                        const pinnedBadge = msg.is_pinned ? '<span class="pinned-badge"><i class="bi bi-pin-fill"></i> Đã ghim</span>' : '';
                        
                        // Edited indicator  
                        const editedText = msg.is_edited ? ' <span class="edited-indicator">(đã sửa)</span>' : '';
                        
                        html += `
                            <div class="message-group ${isOwn ? 'own' : ''} ${msg.is_pinned ? 'pinned' : ''}" data-id="${msg.id}">
                                <img class="message-avatar" src="${msg.sender_avatar || ''}" alt="" onerror="this.style.display='none'">
                                <div class="message-content">
                                    ${showSender ? `<div class="message-sender">${escapeHtml(msg.sender_name || 'Unknown')}</div>` : ''}
                                    ${pinnedBadge}
                                    ${msg.reply_to ? `
                                        <div class="message-reply" onclick="scrollToMessage(${msg.reply_to.id})">
                                            <div class="message-reply-sender">${escapeHtml(msg.reply_to.sender_name || '')}</div>
                                            <div>${escapeHtml(msg.reply_to.content || '')}</div>
                                        </div>
                                    ` : ''}
                                    <div class="message-bubble">${formatMessage(msg.content)}${attachmentHtml}${editedText}</div>
                                    ${reactionsHtml}
                                    ${actionsHtml}
                                    <div class="message-time">${formatTime(msg.created_at, msg.timestamp)}</div>
                                </div>
                            </div>
                        `;
                        
                        lastSenderId = msg.sender_id;
                    }
                });
            }
            
            // Typing indicator placeholder
            html += '<div id="typingIndicator" class="typing-indicator" style="display:none;"></div>';
            
            html += '</div>';
            
            // Input area
            let disabledReason = '';
            if (!canSend) {
                if (conv.status === 'closed') {
                    disabledReason = 'Cuộc trò chuyện đã đóng';
                } else if (conv.type === 'support' && conv.status === 'open') {
                    disabledReason = 'Nhấn "Nhận trả lời" để bắt đầu hỗ trợ';
                } else if (conv.type === 'support' && conv.status === 'assigned' && conv.assigned_to != currentUserId) {
                    disabledReason = 'Nhân viên khác đang hỗ trợ';
                }
            }
            
            html += `
                <div id="replyPreview" style="display:none;"></div>
                <div id="uploadPreview" style="display:none;"></div>
                <div id="quickRepliesPanel" class="quick-replies-panel" style="display:none;"></div>
                <div class="chat-input-area">
                    <div id="emojiPicker" class="emoji-picker-panel" style="display:none;"></div>
                    <div id="stickerPicker" class="sticker-picker-panel" style="display:none;"></div>
                    <div class="chat-input-wrap ${!canSend ? 'disabled' : ''}">
                    ${!canSend ? `<div class="chat-input-disabled-msg">${disabledReason}</div>` : ''}
                    <div class="chat-input-actions">
                        <input type="file" id="fileInput" style="display:none" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleFileSelect(this)">
                        <button class="chat-action-btn" onclick="document.getElementById('fileInput').click()" title="Gửi file (tối đa 5MB ảnh, 20MB video, 10MB file)" ${!canSend ? 'disabled' : ''}>
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <button class="chat-action-btn" onclick="toggleEmojiPicker()" title="Emoji" ${!canSend ? 'disabled' : ''}>
                            <i class="bi bi-emoji-smile"></i>
                        </button>
                        <button class="chat-action-btn" onclick="toggleStickerPicker()" title="Sticker" ${!canSend ? 'disabled' : ''}>
                            <i class="bi bi-stickies"></i>
                        </button>
                        ${isStaff ? `<button class="chat-action-btn" onclick="toggleQuickReplies()" title="Tin nhắn nhanh" ${!canSend ? 'disabled' : ''}>
                            <i class="bi bi-lightning"></i>
                        </button>` : ''}
                    </div>
                    <div class="chat-input">
                        <textarea id="messageInput" rows="1" placeholder="${canSend ? 'Nhập tin nhắn...' : ''}" 
                                  ${!canSend ? 'disabled' : ''}></textarea>
                    </div>
                    <button class="chat-send-btn" id="sendBtn" ${!canSend ? 'disabled' : ''}>
                        <i class="bi bi-send-fill"></i>
                    </button>
                    </div>
                </div>
            `;
            
            $('#chatMain').html(html);
            
            // Update info panel
            updateInfoPanel(conv, participants);
            
            // Scroll to bottom
            const $messages = $('#chatMessages');
            if ($messages.length && $messages[0].scrollHeight) {
                $messages.scrollTop($messages[0].scrollHeight);
            }
            
            // Update last message id for polling
            if (messages.length > 0) {
                lastMessageId = messages[messages.length - 1].id;
            }
            
            // Event handlers
            $('#messageInput').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Auto-resize textarea
            $('#messageInput').on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
            
            $('#sendBtn').click(sendMessage);
            
            // Reply button event handler (using event delegation)
            $('#chatMessages').off('click', '.reply-btn').on('click', '.reply-btn', function() {
                const $btn = $(this);
                const msgId = $btn.data('msg-id');
                const senderName = $btn.data('sender');
                const preview = $btn.data('preview');
                setReply(msgId, senderName, preview);
            });
        }
        
        function sendMessage() {
            const $input = $('#messageInput');
            const content = $input.val().trim();
            
            if ((!content && !pendingAttachment) || !currentConversationId) return;
            
            // Disable send button temporarily
            $('#sendBtn').prop('disabled', true);
            
            // If there's an attachment, upload first
            if (pendingAttachment) {
                const formData = new FormData();
                formData.append('action', 'petshop_upload_attachment');
                formData.append('file', pendingAttachment);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Send message with attachment info
                            sendMessageWithAttachment(content, response.data);
                        } else {
                            alert(response.data || 'Không thể upload file');
                            $('#sendBtn').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Lỗi kết nối');
                        $('#sendBtn').prop('disabled', false);
                    }
                });
            } else {
                // Send text only
                sendMessageWithAttachment(content, null);
            }
            
            $input.val('').css('height', 'auto');
        }
        
        function sendMessageWithAttachment(content, attachment) {
            const data = {
                action: 'petshop_send_message',
                conversation_id: currentConversationId,
                content: content || ''
            };
            
            if (replyToMessage) {
                data.reply_to_id = replyToMessage.id;
            }
            
            if (attachment) {
                data.message_type = attachment.type;
                data.attachment_url = attachment.url;
                data.attachment_name = attachment.name;
            }
            
            $.post(ajaxurl, data, function(response) {
                $('#sendBtn').prop('disabled', false);
                if (response.success) {
                    clearReply();
                    clearUpload();
                    pollNewMessages(currentConversationId);
                } else {
                    alert(response.data || 'Không thể gửi tin nhắn');
                }
            }).fail(function() {
                $('#sendBtn').prop('disabled', false);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            });
        }
        
        function pollNewMessages(convId) {
            if (convId !== currentConversationId) return;
            
            $.get(ajaxurl, {
                action: 'petshop_poll_messages',
                conversation_id: convId,
                last_message_id: lastMessageId
            }, function(response) {
                if (response.success && response.data.has_new) {
                    appendMessages(response.data.messages);
                }
            });
        }
        
        function appendMessages(messages) {
            const $container = $('#chatMessages');
            if (!$container.length) return;
            
            // Remove empty state if exists
            $container.find('.empty-state').remove();
            
            const wasAtBottom = $container[0].scrollHeight - $container.scrollTop() - $container.height() < 100;
            
            messages.forEach(function(msg) {
                // Check if message already exists
                if ($container.find(`[data-id="${msg.id}"]`).length > 0) return;
                
                if (msg.message_type === 'system') {
                    $container.append(`
                        <div class="message-system">
                            <div class="message-bubble">${escapeHtml(msg.content)}</div>
                        </div>
                    `);
                } else {
                    const isOwn = msg.sender_id == currentUserId;
                    
                    $container.append(`
                        <div class="message-group ${isOwn ? 'own' : ''}" data-id="${msg.id}">
                            <img class="message-avatar" src="${msg.sender_avatar || ''}" alt="" onerror="this.style.display='none'">
                            <div class="message-content">
                                ${!isOwn ? `<div class="message-sender">${escapeHtml(msg.sender_name || '')}</div>` : ''}
                                <div class="message-bubble">${formatMessage(msg.content)}</div>
                                <div class="message-time">${formatTime(msg.created_at, msg.timestamp)}</div>
                            </div>
                        </div>
                    `);
                }
                
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            
            // Scroll if was at bottom
            if (wasAtBottom) {
                $container.scrollTop($container[0].scrollHeight);
            }
        }
        
        // Tab switching
        $('.chat-tab').click(function() {
            $('.chat-tab').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('type');
            
            // Show/hide support filters
            if (currentFilter === 'support') {
                $('#supportFilters').show();
            } else {
                $('#supportFilters').hide();
            }
            
            loadConversations();
        });
        
        // Support filter buttons
        $('.filter-btn').click(function() {
            const filter = $(this).data('filter');
            
            // Toggle starred filter
            if (filter === 'starred') {
                starredOnly = !starredOnly;
                $(this).toggleClass('active', starredOnly);
            } else {
                // Regular filters are mutually exclusive
                $('.filter-btn:not([data-filter="starred"])').removeClass('active');
                $(this).addClass('active');
                supportFilter = filter;
            }
            
            loadConversations();
        });
        
        // Staff filter (admin only)
        $('#staffFilter').change(function() {
            staffFilter = parseInt($(this).val()) || 0;
            loadConversations();
        });
        
        // Search
        $('#chatSearch').on('input', function() {
            const query = $(this).val().toLowerCase();
            $('.conversation-item').each(function() {
                const title = $(this).find('.conv-title').text().toLowerCase();
                $(this).toggle(title.includes(query));
            });
        });
        
        // Helper functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatMessage(content) {
            if (!content) return '';
            let html = escapeHtml(content);
            html = html.replace(/\n/g, '<br>');
            html = html.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color:inherit;text-decoration:underline;">$1</a>');
            return html;
        }
        
        function formatTime(datetime, timestamp) {
            // Ưu tiên dùng timestamp nếu có
            let date;
            if (timestamp) {
                date = new Date(timestamp);
            } else if (datetime) {
                // Fallback: parse datetime string
                let dateStr = datetime;
                if (typeof dateStr === 'string' && !dateStr.includes('T') && !dateStr.includes('+')) {
                    dateStr = dateStr.replace(' ', 'T') + '+07:00';
                }
                date = new Date(dateStr);
            } else {
                return '';
            }
            return date.toLocaleTimeString('vi-VN', { 
                hour: '2-digit', 
                minute: '2-digit',
                timeZone: 'Asia/Ho_Chi_Minh'
            });
        }
        
        function formatTimeAgo(datetime, timestamp) {
            // Ưu tiên dùng timestamp nếu có
            let date;
            if (timestamp) {
                date = new Date(timestamp);
            } else if (datetime) {
                let dateStr = datetime;
                if (typeof dateStr === 'string' && !dateStr.includes('T') && !dateStr.includes('+')) {
                    dateStr = dateStr.replace(' ', 'T') + '+07:00';
                }
                date = new Date(dateStr);
            } else {
                return '';
            }
            
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 0) return 'Vừa xong'; // Future date protection
            if (diff < 60) return 'Vừa xong';
            if (diff < 3600) return Math.floor(diff / 60) + ' phút';
            if (diff < 86400) return Math.floor(diff / 3600) + ' giờ';
            if (diff < 604800) return Math.floor(diff / 86400) + ' ngày';
            
            return date.toLocaleDateString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });
        }
        
        // Actions
        window.assignConversation = function(convId) {
            if (!confirm('Bạn muốn nhận trả lời cuộc hội thoại này?')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_assign_conversation',
                conversation_id: convId
            }, function(response) {
                if (response.success) {
                    loadMessages(convId);
                    loadConversations();
                } else {
                    alert(response.data || 'Có lỗi xảy ra');
                }
            });
        };
        
        window.leaveConversation = function(convId) {
            if (!confirm('Chuyển cuộc trò chuyện cho nhân viên khác?')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_unassign_conversation',
                conversation_id: convId
            }, function(response) {
                if (response.success) {
                    loadMessages(convId);
                    loadConversations();
                } else {
                    alert(response.data || 'Có lỗi xảy ra');
                }
            });
        };
        
        // Toggle star
        window.toggleStar = function(convId) {
            $.post(ajaxurl, {
                action: 'petshop_toggle_star',
                conversation_id: convId
            }, function(response) {
                if (response.success) {
                    loadMessages(convId);
                    loadConversations();
                } else {
                    alert(response.data || 'Có lỗi xảy ra');
                }
            });
        };
        
        // Info panel
        let currentConvData = null;
        let infoPanelTab = 'info';
        let convDetails = null;
        
        function updateInfoPanel(conv, participants) {
            currentConvData = {conv, participants};
            
            if (!infoPanelOpen) return;
            
            // Fetch detailed data
            $.get(ajaxurl, {
                action: 'petshop_get_conversation_details',
                conversation_id: conv.id
            }, function(response) {
                if (response.success) {
                    convDetails = response.data;
                    renderInfoPanel();
                }
            });
        }
        
        function renderInfoPanel() {
            if (!convDetails) return;
            
            const conv = convDetails;
            
            // Tabs
            let tabs = `
                <div class="info-tabs">
                    <button class="info-tab ${infoPanelTab === 'info' ? 'active' : ''}" onclick="switchInfoTab('info')">
                        <i class="bi bi-info-circle"></i> Thông tin
                    </button>
                    <button class="info-tab ${infoPanelTab === 'media' ? 'active' : ''}" onclick="switchInfoTab('media')">
                        <i class="bi bi-image"></i> Media
                    </button>
                    <button class="info-tab ${infoPanelTab === 'pinned' ? 'active' : ''}" onclick="switchInfoTab('pinned')">
                        <i class="bi bi-pin"></i> Đã ghim
                    </button>
                </div>
            `;
            
            let content = '';
            
            if (infoPanelTab === 'info') {
                content = renderInfoTabContent();
            } else if (infoPanelTab === 'media') {
                content = '<div class="info-media-container" id="infoMediaGrid"><div class="loading">Đang tải...</div></div>';
                loadSharedMedia();
            } else if (infoPanelTab === 'pinned') {
                content = '<div class="info-pinned-list" id="infoPinnedList"><div class="loading">Đang tải...</div></div>';
                loadPinnedMessages();
            }
            
            $('#infoPanelContent').html(tabs + '<div class="info-tab-content">' + content + '</div>');
        }
        
        function renderInfoTabContent() {
            const conv = convDetails;
            let html = '';
            
            // Stats
            html += `
                <div class="info-stats">
                    <div class="info-stat">
                        <div class="info-stat-value">${conv.stats?.total_messages || 0}</div>
                        <div class="info-stat-label">Tin nhắn</div>
                    </div>
                    <div class="info-stat">
                        <div class="info-stat-value">${conv.stats?.media_count || 0}</div>
                        <div class="info-stat-label">Media</div>
                    </div>
                    <div class="info-stat">
                        <div class="info-stat-value">${conv.stats?.pinned_count || 0}</div>
                        <div class="info-stat-label">Đã ghim</div>
                    </div>
                </div>
            `;
            
            // Customer info
            if (conv.customer) {
                const c = conv.customer;
                html += `
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="bi bi-person"></i> Khách hàng
                        </div>
                        <div class="info-customer-card">
                            <img class="info-customer-avatar" src="${c.avatar || ''}" onerror="this.style.display='none'">
                            <div>
                                <div class="info-customer-name">${escapeHtml(c.name)}</div>
                                <div class="info-customer-meta">${escapeHtml(c.email)}</div>
                                ${c.phone ? `<div class="info-customer-meta"><i class="bi bi-telephone"></i> ${escapeHtml(c.phone)}</div>` : ''}
                            </div>
                        </div>
                        <div class="info-customer-stats">
                            <div class="info-item">
                                <div class="info-item-icon"><i class="bi bi-calendar3"></i></div>
                                <div class="info-item-text">
                                    <div class="info-item-label">Ngày đăng ký</div>
                                    <div class="info-item-value">${formatDate(c.registered)}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-icon"><i class="bi bi-chat-dots"></i></div>
                                <div class="info-item-text">
                                    <div class="info-item-label">Lần chat</div>
                                    <div class="info-item-value">${c.prev_conversations || 0} cuộc hội thoại</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Order info
                if (c.total_orders > 0) {
                    html += `
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="bi bi-bag"></i> Đơn hàng (${c.total_orders || 0})
                            </div>
                            <div class="info-customer-stats">
                                <div class="info-item">
                                    <div class="info-item-icon"><i class="bi bi-currency-dollar"></i></div>
                                    <div class="info-item-text">
                                        <div class="info-item-label">Tổng chi tiêu</div>
                                        <div class="info-item-value" style="color:#28a745;font-weight:600;">${formatCurrency(c.total_spent)}</div>
                                    </div>
                                </div>
                            </div>
                    `;
                    
                    if (c.orders && c.orders.length) {
                        html += '<div class="info-orders">';
                        c.orders.forEach(function(o) {
                            const statusClass = {'pending':'warning','processing':'info','completed':'success','cancelled':'danger'}[o.status] || 'secondary';
                            html += `
                                <div class="info-order" onclick="window.open('/wp-admin/post.php?post=${o.id}&action=edit','_blank')">
                                    <div class="info-order-id">#${o.id}</div>
                                    <span class="badge badge-${statusClass}">${o.status}</span>
                                    <div class="info-order-total">${formatCurrency(o.total)}</div>
                                    <div class="info-order-date">${formatDate(o.date)}</div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    html += '</div>';
                }
            }
            
            // Assigned staff
            if (conv.assigned) {
                html += `
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="bi bi-headset"></i> Nhân viên hỗ trợ
                        </div>
                        <div class="info-item">
                            <img src="${conv.assigned.avatar || ''}" style="width:32px;height:32px;border-radius:50%;" onerror="this.style.display='none'">
                            <div class="info-item-text">
                                <div class="info-item-value">${escapeHtml(conv.assigned.name)}</div>
                                <div class="info-item-label">${escapeHtml(conv.assigned.email)}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Participants for group
            if (conv.participants && conv.participants.length) {
                const isDefaultGroup = conv.is_default_staff_group;
                html += `
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="bi bi-people"></i> Thành viên (${conv.participants.length})
                            ${isDefaultGroup ? '<small style="margin-left:auto;color:#65676b;font-weight:400;"><i class="bi bi-lock"></i> Tự động</small>' : ''}
                            ${!isDefaultGroup && conv.type === 'group' ? `<button style="margin-left:auto;border:none;background:none;color:#EC802B;cursor:pointer;" onclick="showAddMemberModal()"><i class="bi bi-person-plus"></i></button>` : ''}
                        </div>
                `;
                conv.participants.forEach(function(p) {
                    const canRemove = !isDefaultGroup && conv.type === 'group' && p.id != currentUserId;
                    html += `
                        <div class="member-item">
                            <img src="${p.avatar || ''}" onerror="this.style.display='none'">
                            <div class="member-item-info">
                                <div class="member-item-name">${escapeHtml(p.name || '')} ${p.role === 'admin' ? '<small style="color:#EC802B">(Admin)</small>' : ''}</div>
                                <div class="member-item-role">${escapeHtml(p.email || '')}</div>
                            </div>
                            ${canRemove ? `<button class="member-item-action remove" onclick="removeMember(${p.id})" title="Xóa"><i class="bi bi-x"></i></button>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            // Group actions - skip for default staff group
            if ((conv.type === 'group' || conv.type === 'direct') && !conv.is_default_staff_group) {
                    // Normal group - show all options
                    html += `
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="bi bi-gear"></i> Tùy chọn
                            </div>
                            <div class="info-actions">
                                <button class="info-action-btn" onclick="renameConversation()">
                                    <i class="bi bi-pencil"></i> Đổi tên hội thoại
                                </button>
                                <button class="info-action-btn" onclick="toggleStar(${conv.id})">
                                    <i class="bi bi-star${conv.is_starred ? '-fill' : ''}"></i> ${conv.is_starred ? 'Bỏ đánh dấu' : 'Đánh dấu quan trọng'}
                                </button>
                                ${conv.type === 'group' ? `
                                    <button class="info-action-btn" onclick="showAddMemberModal()">
                                        <i class="bi bi-person-plus"></i> Thêm thành viên
                                    </button>
                                ` : ''}
                                <button class="info-action-btn danger" onclick="leaveGroup()">
                                    <i class="bi bi-box-arrow-right"></i> Rời khỏi ${conv.type === 'group' ? 'nhóm' : 'cuộc trò chuyện'}
                                </button>
                                ${conv.type === 'group' ? `
                                    <button class="info-action-btn danger" onclick="deleteGroup()">
                                        <i class="bi bi-trash"></i> Xóa nhóm
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
            }
            
            // Conversation meta
            html += `
                <div class="info-section">
                    <div class="info-section-title">
                        <i class="bi bi-clock-history"></i> Thời gian
                    </div>
                    <div class="info-item">
                        <div class="info-item-icon"><i class="bi bi-calendar-plus"></i></div>
                        <div class="info-item-text">
                            <div class="info-item-label">Tạo lúc</div>
                            <div class="info-item-value">${formatDate(conv.created_at)}</div>
                        </div>
                    </div>
                </div>
            `;
            
            return html;
        }
        
        let currentMediaType = 'all';
        
        function loadSharedMedia() {
            $.get(ajaxurl, {
                action: 'petshop_get_shared_media_grouped',
                conversation_id: currentConversationId,
                type: currentMediaType
            }, function(response) {
                if (response.success) {
                    const grouped = response.data.grouped;
                    
                    // Type tabs  
                    let html = `
                        <div class="media-type-tabs">
                            <button class="media-type-tab ${currentMediaType === 'all' ? 'active' : ''}" onclick="filterMediaType('all')">
                                <i class="bi bi-grid-3x3"></i>
                                <span>Tất cả</span>
                            </button>
                            <button class="media-type-tab ${currentMediaType === 'image' ? 'active' : ''}" onclick="filterMediaType('image')">
                                <i class="bi bi-image"></i>
                                <span>Ảnh</span>
                            </button>
                            <button class="media-type-tab ${currentMediaType === 'video' ? 'active' : ''}" onclick="filterMediaType('video')">
                                <i class="bi bi-camera-video"></i>
                                <span>Video</span>
                            </button>
                            <button class="media-type-tab ${currentMediaType === 'file' ? 'active' : ''}" onclick="filterMediaType('file')">
                                <i class="bi bi-file-earmark"></i>
                                <span>File</span>
                            </button>
                        </div>
                        <div class="media-content">
                    `;
                    
                    if (Object.keys(grouped).length === 0) {
                        const emptyMsg = {
                            'all': 'Chưa có media nào',
                            'image': 'Chưa có ảnh',
                            'video': 'Chưa có video',
                            'file': 'Chưa có file'
                        }[currentMediaType];
                        html += `<div class="empty-state-small"><i class="bi bi-folder2-open"></i><br>${emptyMsg}</div>`;
                    } else {
                        for (const [date, data] of Object.entries(grouped)) {
                            html += `<div class="media-date-group">
                                <div class="media-date-header"><i class="bi bi-calendar3"></i> ${data.label}</div>`;
                            
                            if (currentMediaType === 'file') {
                                // File list view
                                data.items.forEach(function(m) {
                                    const ext = m.attachment_name ? m.attachment_name.split('.').pop().toUpperCase() : 'FILE';
                                    html += `
                                        <a class="file-list-item" href="${m.attachment_url}" target="_blank">
                                            <div class="file-list-icon"><i class="bi bi-file-earmark"></i></div>
                                            <div class="file-list-info">
                                                <div class="file-list-name">${escapeHtml(m.attachment_name || 'File')}</div>
                                                <div class="file-list-meta">${ext}</div>
                                            </div>
                                            <i class="bi bi-download" style="color:#65676b"></i>
                                        </a>
                                    `;
                                });
                            } else {
                                // Grid view for images/videos
                                html += '<div class="info-media-grid">';
                                data.items.forEach(function(m) {
                                    if (m.message_type === 'image') {
                                        html += `<div class="info-media-item" onclick="window.open('${m.attachment_url}','_blank')">
                                            <img src="${m.attachment_url}" alt="">
                                        </div>`;
                                    } else if (m.message_type === 'video') {
                                        html += `<div class="info-media-item video" onclick="window.open('${m.attachment_url}','_blank')">
                                            <video src="${m.attachment_url}"></video>
                                            <i class="bi bi-play-circle"></i>
                                        </div>`;
                                    } else if (currentMediaType === 'all') {
                                        html += `<a class="info-media-file" href="${m.attachment_url}" target="_blank" style="grid-column:span 3;">
                                            <i class="bi bi-file-earmark"></i>
                                            <span>${escapeHtml(m.attachment_name)}</span>
                                        </a>`;
                                    }
                                });
                                html += '</div>';
                            }
                            
                            html += '</div>';
                        }
                    }
                    // Close media-content div
                    html += '</div>';
                    $('#infoMediaGrid').html(html);
                }
            });
        }
        
        window.filterMediaType = function(type) {
            currentMediaType = type;
            loadSharedMedia();
        };
        
        function loadPinnedMessages() {
            $.get(ajaxurl, {
                action: 'petshop_get_pinned_messages',
                conversation_id: currentConversationId
            }, function(response) {
                if (response.success) {
                    let html = '';
                    if (response.data.pinned.length === 0) {
                        html = '<div class="empty-state-small">Chưa có tin nhắn được ghim</div>';
                    } else {
                        response.data.pinned.forEach(function(p) {
                            // Check if it's an image/video message
                            let contentHtml = '';
                            if (p.message_type === 'image' && p.attachment_url) {
                                contentHtml = `<div class="info-pinned-media"><img src="${p.attachment_url}" alt="Ảnh ghim"></div>`;
                            } else if (p.message_type === 'video' && p.attachment_url) {
                                contentHtml = `<div class="info-pinned-media video"><video src="${p.attachment_url}"></video><i class="bi bi-play-circle"></i></div>`;
                            } else if (p.message_type === 'file' && p.attachment_url) {
                                contentHtml = `<div class="info-pinned-file"><i class="bi bi-file-earmark"></i> ${escapeHtml(p.attachment_name || 'File')}</div>`;
                            } else {
                                contentHtml = `<div class="info-pinned-content">${escapeHtml((p.content || '').substring(0,100))}</div>`;
                            }
                            
                            html += `
                                <div class="info-pinned-item" onclick="scrollToMessage(${p.id})">
                                    <div class="info-pinned-header">
                                        <div class="info-pinned-sender">${escapeHtml(p.sender_name || 'Unknown')}</div>
                                        <div class="info-pinned-time">${formatDate(p.created_at)}</div>
                                    </div>
                                    ${contentHtml}
                                </div>
                            `;
                        });
                    }
                    $('#infoPinnedList').html(html);
                }
            });
        }
        
        window.switchInfoTab = function(tab) {
            infoPanelTab = tab;
            renderInfoPanel();
        };
        
        window.scrollToMessage = function(msgId) {
            const $msg = $(`[data-id="${msgId}"]`);
            if ($msg.length) {
                const $container = $('#chatMessages');
                $container.scrollTop($msg.offset().top - $container.offset().top + $container.scrollTop() - 50);
                $msg.addClass('highlight');
                setTimeout(function() { $msg.removeClass('highlight'); }, 2000);
            }
        };
        
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr.replace(' ','T'));
            return d.toLocaleDateString('vi-VN', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(amount || 0);
        }
        
        window.toggleInfoPanel = function() {
            infoPanelOpen = !infoPanelOpen;
            $('#chatInfoPanel').toggle(infoPanelOpen);
            
            if (infoPanelOpen && currentConvData) {
                updateInfoPanel(currentConvData.conv, currentConvData.participants);
            }
        };
        
        // Reply functions
        window.setReply = function(msgId, senderName, preview) {
            replyToMessage = {id: msgId, sender: senderName, preview: preview};
            
            $('#replyPreview').html(`
                <div class="reply-preview">
                    <div class="reply-preview-content">
                        <div class="reply-preview-name">${escapeHtml(senderName)}</div>
                        <div class="reply-preview-text">${escapeHtml(preview)}</div>
                    </div>
                    <button class="reply-preview-close" onclick="clearReply()"><i class="bi bi-x"></i></button>
                </div>
            `).show();
            
            $('#messageInput').focus();
        };
        
        window.clearReply = function() {
            replyToMessage = null;
            $('#replyPreview').hide().empty();
        };
        
        // File upload
        window.handleFileSelect = function(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            
            // Different size limits for different file types
            let maxSize, sizeLabel;
            if (file.type.startsWith('image/')) {
                maxSize = 5 * 1024 * 1024; // 5MB for images
                sizeLabel = '5MB';
            } else if (file.type.startsWith('video/')) {
                maxSize = 20 * 1024 * 1024; // 20MB for videos
                sizeLabel = '20MB';
            } else {
                maxSize = 10 * 1024 * 1024; // 10MB for documents
                sizeLabel = '10MB';
            }
            
            if (file.size > maxSize) {
                alert(`File quá lớn. Tối đa ${sizeLabel} cho loại file này.`);
                input.value = '';
                return;
            }
            
            // Preview
            let previewHtml = '';
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewHtml = `
                        <div class="upload-preview">
                            <img src="${e.target.result}">
                            <div class="upload-preview-info">
                                <div class="upload-preview-name">${escapeHtml(file.name)}</div>
                                <div class="upload-preview-size">${formatFileSize(file.size)}</div>
                            </div>
                            <button class="reply-preview-close" onclick="clearUpload()"><i class="bi bi-x"></i></button>
                        </div>
                    `;
                    $('#uploadPreview').html(previewHtml).show();
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                previewHtml = `
                    <div class="upload-preview">
                        <i class="bi bi-film" style="font-size:40px;color:#EC802B;"></i>
                        <div class="upload-preview-info">
                            <div class="upload-preview-name">${escapeHtml(file.name)}</div>
                            <div class="upload-preview-size">${formatFileSize(file.size)}</div>
                        </div>
                        <button class="reply-preview-close" onclick="clearUpload()"><i class="bi bi-x"></i></button>
                    </div>
                `;
                $('#uploadPreview').html(previewHtml).show();
            } else {
                previewHtml = `
                    <div class="upload-preview">
                        <i class="bi bi-file-earmark" style="font-size:40px;color:#EC802B;"></i>
                        <div class="upload-preview-info">
                            <div class="upload-preview-name">${escapeHtml(file.name)}</div>
                            <div class="upload-preview-size">${formatFileSize(file.size)}</div>
                        </div>
                        <button class="reply-preview-close" onclick="clearUpload()"><i class="bi bi-x"></i></button>
                    </div>
                `;
                $('#uploadPreview').html(previewHtml).show();
            }
            
            pendingAttachment = file;
        };
        
        window.clearUpload = function() {
            pendingAttachment = null;
            $('#uploadPreview').hide().empty();
            $('#fileInput').val('');
        };
        
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
        
        // =============================================
        // MESSENGER-LIKE FEATURES JS
        // =============================================
        
        // Reaction picker
        let reactionPickerTimeout;
        window.showReactionPicker = function(event, msgId) {
            event.stopPropagation();
            hideReactionPicker();
            
            const picker = document.createElement('div');
            picker.className = 'reaction-picker';
            picker.id = 'reactionPicker';
            picker.innerHTML = `
                <button onclick="toggleReaction(${msgId},'like')">👍</button>
                <button onclick="toggleReaction(${msgId},'love')">❤️</button>
                <button onclick="toggleReaction(${msgId},'haha')">😄</button>
                <button onclick="toggleReaction(${msgId},'wow')">😮</button>
                <button onclick="toggleReaction(${msgId},'sad')">😢</button>
                <button onclick="toggleReaction(${msgId},'angry')">😠</button>
            `;
            
            document.body.appendChild(picker);
            
            const rect = event.target.getBoundingClientRect();
            picker.style.top = (rect.top - 50) + 'px';
            picker.style.left = (rect.left - 100) + 'px';
            
            reactionPickerTimeout = setTimeout(hideReactionPicker, 5000);
        };
        
        function hideReactionPicker() {
            const picker = document.getElementById('reactionPicker');
            if (picker) picker.remove();
            clearTimeout(reactionPickerTimeout);
        }
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.reaction-picker') && !e.target.closest('.msg-action-btn')) {
                hideReactionPicker();
            }
        });
        
        window.toggleReaction = function(msgId, reaction) {
            hideReactionPicker();
            $.post(ajaxurl, {
                action: 'petshop_toggle_reaction',
                message_id: msgId,
                reaction: reaction
            }, function(response) {
                if (response.success) {
                    updateMessageReactions(msgId, response.data.reactions);
                }
            });
        };
        
        function updateMessageReactions(msgId, reactions) {
            const $msg = $('[data-id="' + msgId + '"]');
            if (!$msg.length) return;
            
            let html = '';
            const emojiMap = {like:'👍',love:'❤️',haha:'😄',wow:'😮',sad:'😢',angry:'😠'};
            
            if (reactions && Object.keys(reactions).length > 0) {
                html = '<div class="message-reactions">';
                for (const [type, users] of Object.entries(reactions)) {
                    if (users && users.length > 0) {
                        const hasMe = users.includes(currentUserId);
                        html += `<span class="reaction-badge ${hasMe ? 'active' : ''}" onclick="toggleReaction(${msgId},'${type}')">${emojiMap[type] || type} ${users.length}</span>`;
                    }
                }
                html += '</div>';
            }
            
            $msg.find('.message-reactions').remove();
            $msg.find('.message-actions').before(html);
        }
        
        // Edit message
        window.editMessage = function(msgId) {
            const $msg = $('[data-id="' + msgId + '"]');
            const $bubble = $msg.find('.message-bubble');
            const currentText = $bubble.text().replace('(đã sửa)', '').trim();
            
            $bubble.html(`
                <div class="edit-message-form">
                    <textarea class="edit-message-input">${escapeHtml(currentText)}</textarea>
                    <div class="edit-message-actions">
                        <button class="btn-cancel" onclick="cancelEdit(${msgId})">Hủy</button>
                        <button class="btn-save" onclick="saveEdit(${msgId})">Lưu</button>
                    </div>
                </div>
            `);
            $bubble.find('textarea').focus().select();
        };
        
        window.cancelEdit = function(msgId) {
            loadConversation(currentConversationId);
        };
        
        window.saveEdit = function(msgId) {
            const $msg = $('[data-id="' + msgId + '"]');
            const newContent = $msg.find('.edit-message-input').val().trim();
            
            if (!newContent) {
                alert('Nội dung không được trống');
                return;
            }
            
            $.post(ajaxurl, {
                action: 'petshop_edit_message',
                message_id: msgId,
                content: newContent
            }, function(response) {
                if (response.success) {
                    loadConversation(currentConversationId);
                } else {
                    alert(response.data || 'Không thể sửa tin nhắn');
                }
            });
        };
        
        // Delete message
        window.deleteMessage = function(msgId) {
            if (!confirm('Xóa tin nhắn này?')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_delete_message',
                message_id: msgId
            }, function(response) {
                if (response.success) {
                    $('[data-id="' + msgId + '"]').fadeOut(300, function() {
                        $(this).replaceWith('<div class="message-deleted"><i class="bi bi-x-circle"></i> Tin nhắn đã bị xóa</div>');
                    });
                } else {
                    alert(response.data || 'Không thể xóa');
                }
            });
        };
        
        // Pin message
        window.pinMessage = function(msgId) {
            $.post(ajaxurl, {
                action: 'petshop_pin_message',
                message_id: msgId
            }, function(response) {
                if (response.success) {
                    const $msg = $('[data-id="' + msgId + '"]');
                    $msg.toggleClass('pinned', response.data.is_pinned);
                    
                    if (response.data.is_pinned) {
                        if (!$msg.find('.pinned-badge').length) {
                            $msg.find('.message-sender, .message-content').first().after(
                                '<span class="pinned-badge"><i class="bi bi-pin-fill"></i> Đã ghim</span>'
                            );
                        }
                        $msg.find('.bi-pin').removeClass('bi-pin').addClass('bi-pin-fill');
                    } else {
                        $msg.find('.pinned-badge').remove();
                        $msg.find('.bi-pin-fill').removeClass('bi-pin-fill').addClass('bi-pin');
                    }
                }
            });
        };
        
        // Typing indicator
        let typingTimeout;
        function sendTyping(isTyping) {
            if (!currentConversationId) return;
            $.post(ajaxurl, {
                action: 'petshop_set_typing',
                conversation_id: currentConversationId,
                is_typing: isTyping ? 1 : 0
            });
        }
        
        $(document).on('input', '#messageInput', function() {
            sendTyping(true);
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function() {
                sendTyping(false);
            }, 3000);
        });
        
        function checkTypingIndicator() {
            if (!currentConversationId) return;
            $.get(ajaxurl, {
                action: 'petshop_get_typing',
                conversation_id: currentConversationId
            }, function(response) {
                if (response.success && response.data.typing.length > 0) {
                    const names = response.data.typing.slice(0, 3).join(', ');
                    $('#typingIndicator').html(`
                        <div class="typing-content">
                            <span class="typing-dots"><span></span><span></span><span></span></span>
                            <span>${escapeHtml(names)} đang nhập...</span>
                        </div>
                    `).show();
                } else {
                    $('#typingIndicator').hide();
                }
            });
        }
        
        setInterval(checkTypingIndicator, 2000);
        
        // Quick replies
        let quickReplies = [];
        
        function loadQuickReplies() {
            $.get(ajaxurl, {action: 'petshop_get_quick_replies'}, function(response) {
                if (response.success) {
                    quickReplies = response.data;
                }
            });
        }
        loadQuickReplies();
        
        window.toggleQuickReplies = function() {
            const $panel = $('#quickRepliesPanel');
            
            // Hide other pickers
            $('#emojiPicker, #stickerPicker').hide();
            
            if ($panel.is(':visible')) {
                $panel.hide();
                return;
            }
            
            // Populate panel content
            let html = '<div class="quick-replies-header"><span>Tin nhắn nhanh</span><button onclick="$(\'#quickRepliesPanel\').hide()"><i class="bi bi-x"></i></button></div><div class="quick-replies-list">';
            if (quickReplies.length === 0) {
                html += '<div style="padding:16px;text-align:center;color:#65676b;">Chưa có mẫu tin nhắn nhanh</div>';
            } else {
                quickReplies.forEach(function(r, i) {
                    html += `<div class="quick-reply-item" onclick="useQuickReply(${i})">
                        <div class="quick-reply-title">${escapeHtml(r.title)}</div>
                        <div class="quick-reply-preview">${escapeHtml(r.content.substring(0,50))}...</div>
                    </div>`;
                });
            }
            html += '</div>';
            
            $panel.html(html).show();
        };
        
        window.useQuickReply = function(index) {
            if (quickReplies[index]) {
                $('#messageInput').val(quickReplies[index].content);
                $('#quickRepliesPanel').hide();
                $('#messageInput').focus();
            }
        };
        
        // Search in conversation
        window.toggleSearch = function() {
            const $search = $('#chatSearchPanel');
            if ($search.length) {
                $search.toggle();
                if ($search.is(':visible')) {
                    $search.find('input').focus();
                }
                return;
            }
            
            const html = `
                <div id="chatSearchPanel" class="chat-search-panel">
                    <div class="chat-search-input">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Tìm tin nhắn..." id="searchInput">
                        <button onclick="$('#chatSearchPanel').hide()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="chat-search-results" id="searchResults"></div>
                </div>
            `;
            
            $('.chat-header').after(html);
            $('#searchInput').focus().on('keyup', debounce(performSearch, 300));
        };
        
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        function performSearch() {
            const query = $('#searchInput').val().trim();
            if (query.length < 2) {
                $('#searchResults').html('<div class="search-hint">Nhập ít nhất 2 ký tự</div>');
                return;
            }
            
            $.get(ajaxurl, {
                action: 'petshop_search_messages',
                conversation_id: currentConversationId,
                query: query
            }, function(response) {
                if (response.success) {
                    let html = '';
                    if (response.data.results.length === 0) {
                        html = '<div class="search-no-result">Không tìm thấy</div>';
                    } else {
                        response.data.results.forEach(function(r) {
                            html += `
                                <div class="search-result-item" onclick="scrollToMessage(${r.id});$('#chatSearchPanel').hide();">
                                    <div class="search-result-sender">${escapeHtml(r.sender_name)}</div>
                                    <div class="search-result-content">${highlightText(r.content, query)}</div>
                                    <div class="search-result-time">${formatDate(r.created_at)}</div>
                                </div>
                            `;
                        });
                    }
                    $('#searchResults').html(html);
                }
            });
        }
        
        function highlightText(text, query) {
            const regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
            return escapeHtml(text).replace(regex, '<mark>$1</mark>');
        }
        
        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // =============================================
        // EMOJI & STICKER PICKER
        // =============================================
        
        const emojiCategories = {
            'smileys': {icon: '😀', emojis: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','😊','😇','🥰','😍','🤩','😘','😗','😚','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','😮‍💨','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','🥸','😎','🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖']},
            'gestures': {icon: '👋', emojis: ['👋','🤚','🖐️','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁️','👅','👄']},
            'animals': {icon: '🐶', emojis: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐽','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🐣','🐥','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🪱','🐛','🦋','🐌','🐞','🐜','🦟','🦗','🪳','🕷️','🕸️','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🦣','🐘','🦛','🦏']},
            'food': {icon: '🍔', emojis: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🦴','🌭','🍔','🍟','🍕','🫓','🥪','🥙','🧆','🌮','🌯','🫔','🥗','🥘','🫕','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🥛','🍼','☕','🫖','🍵','🧃','🥤','🧋','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾']},
            'objects': {icon: '💡', emojis: ['⌚','📱','💻','⌨️','🖥️','🖨️','🖱️','🖲️','💽','💾','💿','📀','📼','📷','📸','📹','🎥','📽️','🎞️','📞','☎️','📟','📠','📺','📻','🎙️','🎚️','🎛️','🧭','⏱️','⏲️','⏰','🕰️','⌛','⏳','📡','🔋','🔌','💡','🔦','🕯️','🪔','🧯','🛢️','💸','💵','💴','💶','💷','💰','💳','🧾','💎','⚖️','🧰','🔧','🔨','⚒️','🛠️','⛏️','🔩','⚙️','🧱','⛓️','🧲','🔫','💣','🧨','🪓','🔪','🗡️','⚔️','🛡️','🚬','⚰️','🪦','⚱️','🏺','🔮','📿','🧿','💈','⚗️','🔭','🔬','🕳️','🩹','🩺','💊','💉','🩸','🧬','🦠','🧫','🧪','🌡️','🧹','🧺','🧻','🚽','🚰','🚿','🛁','🛀','🧼','🪥','🪒','🧽','🧴','🛎️','🔑','🗝️','🚪','🪑','🛋️','🛏️','🛌','🧸','🖼️','🪞','🪟','🛒']},
            'hearts': {icon: '❤️', emojis: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓']},
        };
        
        let currentEmojiCategory = 'smileys';
        
        window.toggleEmojiPicker = function() {
            const $picker = $('#emojiPicker');
            
            if ($picker.is(':visible')) {
                $picker.hide();
                return;
            }
            
            // Hide other pickers
            $('#stickerPicker, #quickRepliesPanel').hide();
            
            // Build picker if empty
            if (!$picker.html()) {
                let tabs = '<div class="emoji-picker-tabs">';
                for (const [cat, data] of Object.entries(emojiCategories)) {
                    tabs += `<button class="${cat === currentEmojiCategory ? 'active' : ''}" onclick="switchEmojiCategory('${cat}')">${data.icon}</button>`;
                }
                tabs += '</div>';
                
                $picker.html(`
                    <div class="emoji-picker-header">
                        <span>Emoji</span>
                        <button onclick="$('#emojiPicker').hide()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="emoji-picker-search">
                        <input type="text" placeholder="Tìm emoji..." oninput="searchEmoji(this.value)">
                    </div>
                    ${tabs}
                    <div class="emoji-grid" id="emojiGrid"></div>
                `);
            }
            
            renderEmojiGrid();
            $picker.show();
        };
        
        window.switchEmojiCategory = function(cat) {
            currentEmojiCategory = cat;
            $('.emoji-picker-tabs button').removeClass('active');
            $(`.emoji-picker-tabs button:contains('${emojiCategories[cat].icon}')`).addClass('active');
            renderEmojiGrid();
        };
        
        function renderEmojiGrid(filter = '') {
            let emojis = emojiCategories[currentEmojiCategory].emojis;
            if (filter) {
                emojis = Object.values(emojiCategories).flatMap(c => c.emojis);
            }
            
            let html = '';
            emojis.forEach(e => {
                html += `<span class="emoji-item" onclick="insertEmoji('${e}')">${e}</span>`;
            });
            
            $('#emojiGrid').html(html);
        }
        
        window.searchEmoji = function(query) {
            // Simple filter - just show all for now
            renderEmojiGrid(query);
        };
        
        window.insertEmoji = function(emoji) {
            const $input = $('#messageInput');
            const cursorPos = $input[0].selectionStart;
            const text = $input.val();
            $input.val(text.substring(0, cursorPos) + emoji + text.substring(cursorPos));
            $input.focus();
            $input[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
        };
        
        // Stickers - emoji lớn dễ nhìn
        const stickerPacks = [
            {
                name: 'Biểu cảm',
                stickers: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😚','😋','😛','🤪','😜','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱']
            },
            {
                name: 'Thú cưng',
                stickers: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🐤','🐣','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦗','🕷️','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🐘','🦛','🦏','🐪','🐫','🦒','🦘','🦬','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🦙','🐐','🦌','🐕','🐩','🦮','🐈','🐓','🦃','🦤','🦚','🦜','🦢','🦩','🐇','🦝','🦨','🦡','🦫','🦦','🦥','🐁','🐀','🐿️','🦔']
            },
            {
                name: 'Ký hiệu',
                stickers: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','🆔','⚛️','🉑','☢️','☣️','📴','📳','🈶','🈚','🈸','🈺','🈷️','✴️','🆚','💮','🉐','㊙️','㊗️','🈴','🈵','🈹','🈲','🅰️','🅱️','🆎','🆑','🅾️','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨️','🚷','🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼️','⁉️','🔅','🔆','〽️','⚠️','🚸','🔱','⚜️','🔰','♻️','✅','🈯','💹','❇️','✳️','❎','🌐','💠','Ⓜ️','🌀','💤','🏧','🚾','♿','🅿️','🛗','🈳','🈂️','🛂','🛃','🛄','🛅','🚹','🚺','🚼','⚧','🚻','🚮','🎦','📶','🈁','🔣','ℹ️','🔤','🔡','🔠','🆖','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','🔢','#️⃣','*️⃣','⏏️','▶️','⏸️','⏯️','⏹️','⏺️','⏭️','⏮️','⏩','⏪','⏫','⏬','◀️','🔼','🔽','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️','↕️','↔️','↪️','↩️','⤴️','⤵️','🔀','🔁','🔂','🔄','🔃','🎵','🎶','➕','➖','➗','✖️','🟰','♾️','💲','💱','™️','©️','®️','👁️‍🗨️','🔚','🔙','🔛','🔝','🔜','〰️','➰','➿','✔️','☑️','🔘','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤','🔺','🔻','🔸','🔹','🔶','🔷','🔳','🔲','▪️','▫️','◾','◽','◼️','◻️','🟥','🟧','🟨','🟩','🟦','🟪','⬛','⬜','🟫','🔈','🔇','🔉','🔊','🔔','🔕','📣','📢','💬','💭','🗯️','♠️','♣️','♥️','♦️','🃏','🎴','🀄','🕐','🕑','🕒','🕓','🕔','🕕','🕖','🕗','🕘','🕙','🕚','🕛','🕜','🕝','🕞','🕟','🕠','🕡','🕢','🕣','🕤','🕥','🕦','🕧']
            }
        ];
        
        window.toggleStickerPicker = function() {
            const $picker = $('#stickerPicker');
            
            if ($picker.is(':visible')) {
                $picker.hide();
                return;
            }
            
            $('#emojiPicker, #quickRepliesPanel').hide();
            
            if (!$picker.html()) {
                let html = `
                    <div class="sticker-picker-header">
                        <span>Sticker</span>
                        <button onclick="$('#stickerPicker').hide()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="sticker-grid">
                `;
                
                stickerPacks.forEach((pack, i) => {
                    html += `<div class="sticker-category">${pack.name}</div>`;
                    pack.stickers.forEach(s => {
                        html += `<div class="sticker-item" onclick="sendSticker('${s}')">${s}</div>`;
                    });
                });
                
                html += '</div>';
                $picker.html(html);
            }
            
            $picker.show();
        };
        
        window.sendSticker = function(sticker) {
            $('#stickerPicker').hide();
            $('#messageInput').val(sticker);
            sendMessage();
        };
        
        // =============================================
        // PINNED MESSAGES BAR
        // =============================================
        
        let pinnedMessages = [];
        let currentPinnedIndex = 0;
        
        function loadPinnedBar(convId) {
            $.get(ajaxurl, {
                action: 'petshop_get_pinned_messages',
                conversation_id: convId
            }, function(response) {
                if (response.success && response.data.pinned.length > 0) {
                    pinnedMessages = response.data.pinned;
                    currentPinnedIndex = 0;
                    renderPinnedBar();
                } else {
                    $('#pinnedMessagesBar').hide();
                }
            });
        }
        
        function renderPinnedBar() {
            if (pinnedMessages.length === 0) {
                $('#pinnedMessagesBar').hide();
                return;
            }
            
            const msg = pinnedMessages[currentPinnedIndex];
            const total = pinnedMessages.length;
            
            // Determine content preview
            let contentPreview = '';
            if (msg.message_type === 'image') {
                contentPreview = '<i class="bi bi-image"></i> Ảnh';
            } else if (msg.message_type === 'video') {
                contentPreview = '<i class="bi bi-camera-video"></i> Video';
            } else if (msg.message_type === 'file') {
                contentPreview = '<i class="bi bi-file-earmark"></i> ' + (msg.attachment_name || 'File');
            } else {
                contentPreview = escapeHtml((msg.content || '').substring(0, 50)) + (msg.content && msg.content.length > 50 ? '...' : '');
            }
            
            $('#pinnedMessagesBar').html(`
                <i class="pinned-bar-icon bi bi-pin-angle-fill"></i>
                <div class="pinned-bar-content" onclick="scrollToMessage(${msg.id})">
                    <div class="pinned-bar-label">Tin nhắn đã ghim</div>
                    <div class="pinned-bar-text">${contentPreview}</div>
                </div>
                ${total > 1 ? `
                    <span class="pinned-bar-count">${currentPinnedIndex + 1}/${total}</span>
                    <div class="pinned-bar-nav">
                        <button onclick="event.stopPropagation(); prevPinned()"><i class="bi bi-chevron-up"></i></button>
                        <button onclick="event.stopPropagation(); nextPinned()"><i class="bi bi-chevron-down"></i></button>
                    </div>
                ` : ''}
            `).show();
        }
        
        window.prevPinned = function() {
            currentPinnedIndex = (currentPinnedIndex - 1 + pinnedMessages.length) % pinnedMessages.length;
            renderPinnedBar();
        };
        
        window.nextPinned = function() {
            currentPinnedIndex = (currentPinnedIndex + 1) % pinnedMessages.length;
            renderPinnedBar();
        };
        
        // =============================================
        // GROUP MANAGEMENT
        // =============================================
        
        window.renameConversation = function() {
            if (!currentConversationId) return;
            
            const newName = prompt('Nhập tên mới cho hội thoại:');
            if (!newName || !newName.trim()) return;
            
            $.post(ajaxurl, {
                action: 'petshop_rename_conversation',
                conversation_id: currentConversationId,
                title: newName.trim()
            }, function(response) {
                if (response.success) {
                    loadConversation(currentConversationId);
                    loadConversations();
                } else {
                    alert(response.data || 'Không thể đổi tên');
                }
            });
        };
        
        window.showAddMemberModal = function() {
            if (!currentConversationId) return;
            
            loadStaffList(function(staff) {
                let html = '<div class="member-list">';
                staff.forEach(s => {
                    html += `
                        <div class="member-item" onclick="addMember(${s.id})">
                            <img src="${s.avatar}" onerror="this.style.display='none'">
                            <div class="member-item-info">
                                <div class="member-item-name">${escapeHtml(s.name)}</div>
                                <div class="member-item-role">${escapeHtml(s.role_name)}</div>
                            </div>
                            <button class="member-item-action add"><i class="bi bi-plus"></i></button>
                        </div>
                    `;
                });
                html += '</div>';
                
                showModal('Thêm thành viên', html);
            });
        };
        
        window.addMember = function(userId) {
            $.post(ajaxurl, {
                action: 'petshop_add_member',
                conversation_id: currentConversationId,
                user_id: userId
            }, function(response) {
                if (response.success) {
                    closeModal();
                    loadConversation(currentConversationId);
                } else {
                    alert(response.data || 'Không thể thêm thành viên');
                }
            });
        };
        
        window.removeMember = function(userId) {
            if (!confirm('Xóa thành viên này khỏi nhóm?')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_remove_member',
                conversation_id: currentConversationId,
                user_id: userId
            }, function(response) {
                if (response.success) {
                    loadConversation(currentConversationId);
                } else {
                    alert(response.data || 'Không thể xóa thành viên');
                }
            });
        };
        
        window.leaveGroup = function() {
            if (!confirm('Bạn có chắc muốn rời khỏi nhóm này?')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_leave_group',
                conversation_id: currentConversationId
            }, function(response) {
                if (response.success) {
                    currentConversationId = null;
                    $('#chatMain').html('<div class="chat-main-empty"><i class="bi bi-chat-square-dots"></i><h3>Đã rời nhóm</h3></div>');
                    loadConversations();
                } else {
                    alert(response.data || 'Không thể rời nhóm');
                }
            });
        };
        
        window.deleteGroup = function() {
            if (!confirm('XÓA NHÓM? Tất cả tin nhắn sẽ bị xóa vĩnh viễn!')) return;
            if (!confirm('Bạn chắc chắn chứ? Hành động này không thể hoàn tác!')) return;
            
            $.post(ajaxurl, {
                action: 'petshop_delete_group',
                conversation_id: currentConversationId
            }, function(response) {
                if (response.success) {
                    currentConversationId = null;
                    $('#chatMain').html('<div class="chat-main-empty"><i class="bi bi-chat-square-dots"></i><h3>Đã xóa nhóm</h3></div>');
                    loadConversations();
                } else {
                    alert(response.data || 'Không thể xóa nhóm');
                }
            });
        };
        
        function showModal(title, content) {
            const html = `
                <div class="chat-modal-overlay" onclick="closeModal()">
                    <div class="chat-modal" onclick="event.stopPropagation()">
                        <div class="chat-modal-header">
                            <h3>${title}</h3>
                            <button onclick="closeModal()"><i class="bi bi-x"></i></button>
                        </div>
                        <div class="chat-modal-body">${content}</div>
                    </div>
                </div>
            `;
            $('body').append(html);
        }
        
        window.closeModal = function() {
            $('.chat-modal-overlay').remove();
        };
        
        // Load staff list for modal
        function loadStaffList(callback) {
            $.get(ajaxurl, {
                action: 'petshop_get_staff_list'
            }, function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    callback([]);
                }
            }).fail(function() {
                callback([]);
            });
        }
        
        // Create group modal
        window.showCreateGroupModal = function() {
            $('#groupName').val('');
            $('#createGroupModal').show();
            
            loadStaffList(function(staffList) {
                const $list = $('#memberList');
                if (staffList.length === 0) {
                    $list.html('<div class="empty-state"><i class="bi bi-people"></i><p>Không có nhân viên nào</p></div>');
                    return;
                }
                
                let html = '';
                staffList.forEach(function(staff) {
                    html += `
                        <label class="member-item">
                            <input type="checkbox" value="${staff.id}">
                            <img class="member-avatar" src="${staff.avatar}" alt="" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2240%22>${staff.name.charAt(0)}</text></svg>'">
                            <div class="member-info">
                                <div class="member-name">${escapeHtml(staff.name)}</div>
                                <div class="member-role">${escapeHtml(staff.role)}</div>
                            </div>
                        </label>
                    `;
                });
                $list.html(html);
            });
        };
        
        window.hideCreateGroupModal = function() {
            $('#createGroupModal').hide();
        };
        
        window.createGroup = function() {
            const title = $('#groupName').val().trim();
            if (!title) {
                alert('Vui lòng nhập tên nhóm');
                return;
            }
            
            const members = [];
            $('#memberList input:checked').each(function() {
                members.push(parseInt($(this).val()));
            });
            
            $.post(ajaxurl, {
                action: 'petshop_create_group',
                title: title,
                members: members
            }, function(response) {
                if (response.success) {
                    hideCreateGroupModal();
                    loadConversations();
                    openConversation(response.data.conversation_id);
                } else {
                    alert(response.data || 'Không thể tạo nhóm');
                }
            });
        };
        
        // New chat (1-1) modal
        window.showNewChatModal = function() {
            $('#newChatModal').show();
            
            loadStaffList(function(staffList) {
                const $list = $('#directChatList');
                if (staffList.length === 0) {
                    $list.html('<div class="empty-state"><i class="bi bi-people"></i><p>Không có nhân viên nào</p></div>');
                    return;
                }
                
                let html = '';
                staffList.forEach(function(staff) {
                    const onlineIndicator = staff.is_online ? 
                        '<span style="width:8px;height:8px;background:#28a745;border-radius:50%;display:inline-block;margin-left:6px;" title="Online"></span>' : '';
                    
                    html += `
                        <div class="member-item" onclick="startDirectChat(${staff.id})">
                            <img class="member-avatar" src="${staff.avatar}" alt="" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2240%22>${staff.name.charAt(0)}</text></svg>'">
                            <div class="member-info">
                                <div class="member-name">${escapeHtml(staff.name)}${onlineIndicator}</div>
                                <div class="member-role">${escapeHtml(staff.role)}</div>
                            </div>
                            <i class="bi bi-chevron-right" style="color:#999"></i>
                        </div>
                    `;
                });
                $list.html(html);
            });
        };
        
        window.hideNewChatModal = function() {
            $('#newChatModal').hide();
        };
        
        window.startDirectChat = function(userId) {
            $.post(ajaxurl, {
                action: 'petshop_start_direct_chat',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    hideNewChatModal();
                    loadConversations();
                    openConversation(response.data.conversation_id);
                } else {
                    alert(response.data || 'Không thể tạo cuộc trò chuyện');
                }
            });
        };
        
        // Close modal on overlay click
        $('.modal-overlay').click(function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Initial load
        loadConversations();
        
        // Refresh conversations periodically
        setInterval(loadConversations, 30000);
        
        // Load specific conversation if URL param
        if (currentConversationId) {
            setTimeout(function() {
                openConversation(currentConversationId);
            }, 500);
        }
    });
    </script>
    <?php
}

/**
 * Redirect other pages to main chat page (using JS to avoid headers already sent)
 */
function petshop_chat_support_page() {
    $url = admin_url('admin.php?page=petshop-chat&filter=support');
    echo '<script>window.location.href = "' . esc_url($url) . '";</script>';
    echo '<p>Đang chuyển hướng... <a href="' . esc_url($url) . '">Nhấn vào đây nếu không tự động chuyển</a></p>';
}

function petshop_chat_internal_page() {
    $url = admin_url('admin.php?page=petshop-chat&filter=internal');
    echo '<script>window.location.href = "' . esc_url($url) . '";</script>';
    echo '<p>Đang chuyển hướng... <a href="' . esc_url($url) . '">Nhấn vào đây nếu không tự động chuyển</a></p>';
}

/**
 * Conversation Monitor Page
 */
function petshop_chat_monitor_page() {
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <div class="wrap">
        <h1><i class="bi bi-eye" style="margin-right: 8px;"></i> Giám sát hội thoại</h1>
        <p>Xem tất cả cuộc hội thoại đang diễn ra giữa nhân viên và khách hàng.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <p><a href="<?php echo admin_url('admin.php?page=petshop-chat'); ?>" class="button button-primary button-hero">
                <i class="bi bi-chat-dots" style="margin-right: 5px;"></i> Mở Chat
            </a></p>
            <p style="color: #666; margin-top: 15px;">
                <strong>Lưu ý:</strong> Khi bạn xem cuộc hội thoại của nhân viên khác, bạn đang ở chế độ <strong>Giám sát (Monitor)</strong>. 
                Bạn chỉ có thể xem, không thể gửi tin nhắn trừ khi "Take over" cuộc hội thoại.
            </p>
        </div>
        
        <div style="margin-top: 30px;">
            <h2>Thống kê nhanh</h2>
            <?php
            global $wpdb;
            $table = $wpdb->prefix . 'petshop_conversations';
            
            $open_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE type = 'support' AND status = 'open'");
            $assigned_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE type = 'support' AND status = 'assigned'");
            $today_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE type = 'support' AND DATE(created_at) = %s",
                current_time('Y-m-d')
            ));
            ?>
            <div style="display: flex; gap: 20px;">
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #856404;"><?php echo $open_count; ?></div>
                    <div style="color: #856404;">Đang chờ nhận</div>
                </div>
                <div style="background: #d4edda; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #155724;"><?php echo $assigned_count; ?></div>
                    <div style="color: #155724;">Đang xử lý</div>
                </div>
                <div style="background: #cce5ff; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold; color: #004085;"><?php echo $today_count; ?></div>
                    <div style="color: #004085;">Hôm nay</div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// =============================================
// AUTO-ADD NEW STAFF TO DEFAULT GROUP
// =============================================
add_action('set_user_role', 'petshop_auto_add_staff_to_group', 10, 3);
function petshop_auto_add_staff_to_group($user_id, $role, $old_roles) {
    $staff_roles = array('administrator', 'petshop_manager', 'petshop_staff');
    
    if (in_array($role, $staff_roles)) {
        global $wpdb;
        $table = $wpdb->prefix . 'petshop_conversations';
        
        // Tìm nhóm mặc định
        $group_id = $wpdb->get_var("SELECT id FROM $table WHERE type = 'group' AND title = 'Nhóm nhân viên' LIMIT 1");
        
        if ($group_id) {
            $participant_role = in_array($role, array('administrator', 'petshop_manager')) ? 'admin' : 'member';
            petshop_add_participant($group_id, $user_id, $participant_role);
        }
    }
}

// =============================================
// FRONTEND CHAT WIDGET
// =============================================

/**
 * Hiển thị nút chat ở frontend cho tất cả khách (kể cả chưa đăng nhập)
 */
add_action('wp_footer', 'petshop_frontend_chat_widget');
function petshop_frontend_chat_widget() {
    if (is_admin()) return;
    // Staff cũng thấy widget để test - có thể bật lại sau
    // if (is_user_logged_in() && petshop_is_chat_staff()) return;
    
    $is_logged_in = is_user_logged_in();
    $user = $is_logged_in ? wp_get_current_user() : null;
    $user_name = $user ? $user->display_name : 'Khách';
    $login_url = wp_login_url(get_permalink());
    
    // Chatbot FAQ data
    $faq_items = array(
        array('q' => 'Làm sao để đặt hàng?', 'a' => 'Bạn có thể đặt hàng bằng cách: 1) Chọn sản phẩm và thêm vào giỏ hàng, 2) Vào trang Giỏ hàng, 3) Nhập thông tin giao hàng, 4) Chọn phương thức thanh toán và hoàn tất đơn hàng.'),
        array('q' => 'Phí vận chuyển bao nhiêu?', 'a' => 'Phí vận chuyển phụ thuộc vào địa chỉ giao hàng: Nội thành: 20.000đ, Ngoại thành: 30.000đ. Miễn phí vận chuyển cho đơn hàng từ 500.000đ.'),
        array('q' => 'Thời gian giao hàng?', 'a' => 'Đơn hàng sẽ được giao trong vòng 2-3 ngày làm việc đối với nội thành, 3-5 ngày đối với tỉnh/thành khác.'),
        array('q' => 'Chính sách đổi trả?', 'a' => 'Chúng tôi hỗ trợ đổi trả trong vòng 7 ngày kể từ khi nhận hàng nếu sản phẩm còn nguyên seal, chưa qua sử dụng và có lỗi từ nhà sản xuất.'),
        array('q' => 'Thanh toán như thế nào?', 'a' => 'Chúng tôi hỗ trợ nhiều hình thức thanh toán: COD (thanh toán khi nhận hàng), Chuyển khoản ngân hàng, VNPay, và thanh toán qua ví điện tử.'),
        array('q' => 'Chat với nhân viên', 'a' => '__CHAT_STAFF__'), // Đánh dấu đặc biệt
    );
    
    // Các câu chào hỏi và phản hồi tự động
    $greetings = array(
        'chào' => 'Xin chào bạn! 👋 Rất vui được hỗ trợ bạn. Bạn cần giúp đỡ gì ạ?',
        'hello' => 'Hello! 👋 Chào mừng bạn đến với PetShop. Tôi có thể giúp gì cho bạn?',
        'hi' => 'Hi! 👋 Chào bạn! Bạn muốn hỏi về sản phẩm hay dịch vụ nào ạ?',
        'xin chào' => 'Xin chào! 😊 Chào mừng bạn đến với PetShop. Tôi sẵn sàng hỗ trợ bạn!',
        'hey' => 'Hey! 👋 Chào bạn! Có điều gì tôi có thể giúp bạn không?',
        'tạm biệt' => 'Tạm biệt bạn! 👋 Cảm ơn bạn đã ghé thăm PetShop. Hẹn gặp lại!',
        'bye' => 'Bye bye! 👋 Cảm ơn bạn! Chúc bạn một ngày tốt lành!',
        'goodbye' => 'Goodbye! 👋 Rất vui được hỗ trợ bạn. Hẹn gặp lại nhé!',
        'ok' => 'Tuyệt vời! 👍 Bạn còn cần hỗ trợ gì thêm không ạ?',
        'okay' => 'OK! 👍 Nếu bạn cần thêm thông tin gì, cứ hỏi nhé!',
        'oke' => 'Oke! 👍 Tôi sẵn sàng giúp đỡ bạn thêm nếu cần!',
        'cảm ơn' => 'Không có gì ạ! 😊 Rất vui được giúp đỡ bạn. Chúc bạn mua sắm vui vẻ!',
        'thanks' => 'You\'re welcome! 😊 Cảm ơn bạn đã tin tưởng PetShop!',
        'thank you' => 'You\'re welcome! 😊 Rất vui được hỗ trợ bạn!',
        'cám ơn' => 'Dạ không có gì ạ! 😊 Chúc bạn một ngày tốt lành!',
        'giúp' => 'Tôi sẵn sàng giúp bạn! 💪 Bạn muốn hỏi về vấn đề gì ạ?',
        'help' => 'Tôi ở đây để giúp bạn! 💪 Hãy cho tôi biết bạn cần gì nhé!',
        'hỗ trợ' => 'Tôi sẵn sàng hỗ trợ bạn! 💪 Bạn cần giúp đỡ về vấn đề gì ạ?',
    );
    ?>
    <style>
        #petshop-chat-widget {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 2147483647 !important; /* Maximum z-index */
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        #petshop-chat-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(236, 128, 43, 0.4);
            transition: all 0.3s;
            position: relative;
        }
        
        #petshop-chat-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(236, 128, 43, 0.5);
        }
        
        #petshop-chat-button svg {
            width: 28px;
            height: 28px;
            fill: #fff;
        }
        
        .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            background: #dc3545;
            color: #fff;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }
        
        #petshop-chat-popup {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 380px;
            max-height: 580px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        #petshop-chat-popup.open {
            display: flex;
            animation: chatPopupOpen 0.3s ease;
        }
        
        @keyframes chatPopupOpen {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .chat-popup-header {
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        
        .chat-popup-header-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-popup-header-avatar svg {
            width: 24px;
            height: 24px;
            fill: #fff;
        }
        
        .chat-popup-header-info { flex: 1; }
        .chat-popup-header-title { font-weight: 700; font-size: 16px; }
        .chat-popup-header-status { font-size: 13px; opacity: 0.9; }
        
        .chat-popup-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.2);
            color: #fff;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-popup-close:hover { background: rgba(255,255,255,0.3); }
        
        .chat-popup-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #f8f9fa;
            min-height: 300px;
        }
        
        .popup-message {
            display: flex;
            gap: 8px;
            max-width: 100%;
        }
        
        .popup-message.own { flex-direction: row-reverse; }
        
        .popup-message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
        }
        
        .popup-message.own .popup-message-avatar { display: none; }
        
        .popup-message-content { max-width: 75%; }
        
        .popup-message-bubble {
            padding: 10px 14px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .popup-message:not(.own) .popup-message-bubble {
            background: #fff;
            color: #333;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .popup-message.own .popup-message-bubble {
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
        }
        
        .popup-message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            padding-left: 14px;
        }
        
        .popup-message.own .popup-message-time {
            text-align: right;
            padding-right: 14px;
            padding-left: 0;
        }
        
        /* Quick Replies */
        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            padding: 0 5px;
        }
        
        .quick-reply-btn {
            background: #fff;
            border: 1px solid #EC802B;
            color: #EC802B;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .quick-reply-btn:hover {
            background: #EC802B;
            color: #fff;
        }
        
        /* Login prompt */
        .login-prompt {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 12px;
            margin: 10px 0;
            text-align: center;
        }
        
        .login-prompt p {
            margin: 0 0 10px;
            color: #856404;
            font-size: 14px;
        }
        
        .login-prompt a {
            display: inline-block;
            padding: 8px 20px;
            background: #EC802B;
            color: #fff;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .login-prompt a:hover {
            background: #d97326;
        }
        
        /* Chat Input */
        .chat-popup-input {
            padding: 12px 16px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            background: #fff;
            flex-shrink: 0;
        }
        
        .chat-popup-input textarea {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            resize: none;
            font-size: 14px;
            font-family: inherit;
            max-height: 80px;
        }
        
        .chat-popup-input textarea:focus {
            outline: none;
            border-color: #EC802B;
        }
        
        .chat-popup-send {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #EC802B, #F5994D);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .chat-popup-send:hover { transform: scale(1.05); }
        .chat-popup-send:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .chat-popup-send svg { width: 18px; height: 18px; fill: #fff; }
        
        /* Typing indicator */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 10px 14px;
            background: #fff;
            border-radius: 18px;
            width: fit-content;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }
        
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        /* Back button */
        .back-to-bot-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
            margin-right: 5px;
            display: none;
        }
        
        .back-to-bot-btn:hover { color: #fff; }
        
        @media (max-width: 480px) {
            #petshop-chat-popup {
                width: calc(100vw - 30px);
                max-height: calc(100vh - 150px);
                right: 0;
                bottom: 75px;
            }
        }
    </style>
    
    <!-- Floating quick contact buttons (Zalo + Phone) -->
    <div id="petshop-quick-contacts" style="position:fixed;bottom:100px;right:24px;display:flex;flex-direction:column;gap:10px;z-index:9998;">
        <!-- Zalo -->
        <a href="https://zalo.me/0123456789" target="_blank" rel="noopener"
           id="qc-zalo"
           title="Chat Zalo"
           style="width:48px;height:48px;background:linear-gradient(135deg,#0068FF,#00C6FF);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,104,255,.4);text-decoration:none;transition:all .25s;transform:scale(0);opacity:0;">
            <svg width="26" height="26" viewBox="0 0 40 40" fill="white">
                <path d="M20 2C10.06 2 2 9.85 2 19.54c0 5.5 2.68 10.4 6.87 13.55l-.9 5.9 6.5-3.4c1.7.47 3.5.72 5.53.72 9.94 0 18-7.85 18-17.54C38 9.85 29.94 2 20 2zm1.8 23.6l-4.6-4.9-8.9 4.9 9.8-10.4 4.7 4.9 8.8-4.9-9.8 10.4z"/>
            </svg>
        </a>
        <!-- Gọi điện -->
        <a href="tel:0123456789"
           id="qc-phone"
           title="Gọi ngay: 0123 456 789"
           style="width:48px;height:48px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(236,128,43,.4);text-decoration:none;transition:all .25s;transform:scale(0);opacity:0;">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="white">
                <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/>
            </svg>
        </a>
    </div>

    <style>
    #petshop-quick-contacts a:hover { transform: scale(1.12) !important; }
    @keyframes qcIn { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }
    </style>

    <div id="petshop-chat-widget">
        <button id="petshop-chat-button" onclick="PetshopChat.toggle()">
            <svg viewBox="0 0 24 24"><path d="M12 3c5.5 0 10 3.58 10 8s-4.5 8-10 8c-1.24 0-2.43-.18-3.53-.5C5.55 21 2 21 2 21c2.33-2.33 2.7-3.9 2.75-4.5C3.05 15.07 2 13.13 2 11c0-4.42 4.5-8 10-8z"/></svg>
            <span class="chat-badge" id="chatBadge">0</span>
        </button>
        
        <div id="petshop-chat-popup">
            <div class="chat-popup-header">
                <button class="back-to-bot-btn" id="backToBotBtn" onclick="PetshopChat.backToBot()">←</button>
                <div class="chat-popup-header-avatar">
                    <svg viewBox="0 0 24 24"><path d="M12 3c5.5 0 10 3.58 10 8s-4.5 8-10 8c-1.24 0-2.43-.18-3.53-.5C5.55 21 2 21 2 21c2.33-2.33 2.7-3.9 2.75-4.5C3.05 15.07 2 13.13 2 11c0-4.42 4.5-8 10-8z"/></svg>
                </div>
                <div class="chat-popup-header-info">
                    <div class="chat-popup-header-title" id="chatTitle">Hỗ trợ PetShop</div>
                    <div class="chat-popup-header-status" id="chatStatus">Trả lời tự động 24/7</div>
                </div>
                <button class="chat-popup-close" onclick="PetshopChat.toggle()">×</button>
            </div>

            <!-- Quick contact bar inside chat popup -->
            <div style="display:flex;gap:8px;padding:10px 14px;background:#FDF8F3;border-bottom:1px solid #F5EDE0;">
                <a href="tel:0123456789"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;border-radius:10px;text-decoration:none;font-size:.82rem;font-weight:700;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="white"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
                    Gọi điện
                </a>
                <a href="https://zalo.me/0123456789" target="_blank" rel="noopener"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;background:linear-gradient(135deg,#0068FF,#00C6FF);color:#fff;border-radius:10px;text-decoration:none;font-size:.82rem;font-weight:700;">
                    <svg width="14" height="14" viewBox="0 0 40 40" fill="white"><path d="M20 2C10.06 2 2 9.85 2 19.54c0 5.5 2.68 10.4 6.87 13.55l-.9 5.9 6.5-3.4c1.7.47 3.5.72 5.53.72 9.94 0 18-7.85 18-17.54C38 9.85 29.94 2 20 2zm1.8 23.6l-4.6-4.9-8.9 4.9 9.8-10.4 4.7 4.9 8.8-4.9-9.8 10.4z"/></svg>
                    Chat Zalo
                </a>
            </div>

            <div class="chat-popup-messages" id="chatPopupMessages"></div>
            
            <div class="chat-popup-input">
                <textarea id="chatPopupInput" rows="1" placeholder="Nhập tin nhắn..."></textarea>
                <button class="chat-popup-send" id="chatPopupSend" onclick="PetshopChat.send()">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>
    </div>
    
    <script>
    const PetshopChat = (function() {
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo $is_logged_in ? get_current_user_id() : 0; ?>;
        const userName = '<?php echo esc_js($user_name); ?>';
        const loginUrl = '<?php echo esc_js($login_url); ?>';
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // FAQ data from PHP
        const faqItems = <?php echo json_encode($faq_items); ?>;
        
        // Greetings data from PHP
        const greetings = <?php echo json_encode($greetings); ?>;
        
        // Helper functions for AJAX without jQuery
        function ajaxPost(data) {
            const formData = new FormData();
            Object.keys(data).forEach(key => formData.append(key, data[key]));
            return fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(res => res.json());
        }
        
        function ajaxGet(data) {
            // Add cache-busting parameter
            data._t = Date.now();
            const params = new URLSearchParams(data).toString();
            return fetch(ajaxurl + '?' + params, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(res => res.json());
        }
        
        let isOpen = false;
        let mode = 'bot'; // 'bot' or 'staff'
        let currentConvId = null;
        let lastMessageId = 0;
        let pollInterval = null;
        
        function toggle() {
            const popup = document.getElementById('petshop-chat-popup');
            isOpen = !isOpen;
            
            if (isOpen) {
                popup.classList.add('open');
                if (mode === 'bot') {
                    showBotWelcome();
                }
            } else {
                popup.classList.remove('open');
                stopPolling();
            }
        }
        
        function showBotWelcome() {
            const container = document.getElementById('chatPopupMessages');
            document.getElementById('chatTitle').textContent = 'Hỗ trợ PetShop';
            document.getElementById('chatStatus').textContent = 'Trả lời tự động 24/7';
            document.getElementById('backToBotBtn').style.display = 'none';
            
            container.innerHTML = '';
            
            // Bot welcome message
            addBotMessage('Xin chào ' + userName + '! 👋\n\nTôi là trợ lý ảo của PetShop. Tôi có thể giúp bạn với các câu hỏi thường gặp.\n\nHãy chọn một chủ đề bên dưới hoặc nhập câu hỏi của bạn:');
            
            // Quick replies
            showQuickReplies();
        }
        
        function showQuickReplies() {
            const container = document.getElementById('chatPopupMessages');
            
            let html = '<div class="quick-replies">';
            faqItems.forEach(function(item, index) {
                html += '<button class="quick-reply-btn" onclick="PetshopChat.selectFaq(' + index + ')">' + escapeHtml(item.q) + '</button>';
            });
            html += '</div>';
            
            container.insertAdjacentHTML('beforeend', html);
            container.scrollTop = container.scrollHeight;
        }
        
        function selectFaq(index) {
            const item = faqItems[index];
            
            // Remove quick replies
            const qr = document.querySelector('.quick-replies');
            if (qr) qr.remove();
            
            // Show user's question
            addUserMessage(item.q);
            
            // Check if this is "Chat with staff"
            if (item.a === '__CHAT_STAFF__') {
                setTimeout(function() {
                    if (isLoggedIn) {
                        switchToStaffChat();
                    } else {
                        showLoginPrompt();
                    }
                }, 500);
            } else {
                // Show bot answer
                setTimeout(function() {
                    addBotMessage(item.a);
                    // Show quick replies again
                    setTimeout(showQuickReplies, 300);
                }, 800);
            }
        }
        
        function showLoginPrompt() {
            const container = document.getElementById('chatPopupMessages');
            container.insertAdjacentHTML('beforeend', `
                <div class="login-prompt">
                    <p>Để chat trực tiếp với nhân viên, bạn cần đăng nhập.</p>
                    <a href="${loginUrl}">Đăng nhập ngay</a>
                </div>
            `);
            container.scrollTop = container.scrollHeight;
            
            setTimeout(showQuickReplies, 300);
        }
        
        function switchToStaffChat() {
            mode = 'staff';
            document.getElementById('chatTitle').textContent = 'Chat với nhân viên';
            document.getElementById('chatStatus').textContent = 'Đang kết nối...';
            document.getElementById('backToBotBtn').style.display = 'block';
            
            const container = document.getElementById('chatPopupMessages');
            container.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            
            // Create or get conversation using fetch API
            ajaxPost({
                action: 'petshop_start_support_chat'
            }).then(function(response) {
                if (response.success) {
                    currentConvId = response.data.conversation_id;
                    loadStaffMessages();
                    startPolling();
                } else {
                    container.innerHTML = '';
                    addBotMessage('Có lỗi xảy ra: ' + (response.data || 'Không thể kết nối. Vui lòng thử lại sau.'));
                    setTimeout(showQuickReplies, 300);
                    mode = 'bot';
                }
            }).catch(function() {
                container.innerHTML = '';
                addBotMessage('Không thể kết nối. Vui lòng kiểm tra kết nối mạng và thử lại.');
                setTimeout(showQuickReplies, 300);
                mode = 'bot';
            });
        }
        
        function loadStaffMessages() {
            if (!currentConvId) return;
            
            ajaxGet({
                action: 'petshop_get_messages',
                conversation_id: currentConvId
            }).then(function(response) {
                if (response.success) {
                    renderStaffMessages(response.data);
                }
            }).catch(function(err) {
                console.error('Failed to load messages:', err);
            });
        }
        
        function renderStaffMessages(data) {
            const container = document.getElementById('chatPopupMessages');
            const messages = data.messages;
            const conv = data.conversation;
            
            // Update status - no more "closed" since we keep conversations open
            let status = 'Đang chờ nhân viên...';
            if (conv.status === 'assigned') {
                status = '🟢 Đang trò chuyện';
            }
            document.getElementById('chatStatus').textContent = status;
            
            container.innerHTML = '';
            
            if (messages.length === 0) {
                addBotMessage('Bạn đã được kết nối với bộ phận hỗ trợ. Vui lòng nhập tin nhắn để bắt đầu.');
            } else {
                messages.forEach(function(msg) {
                    appendStaffMessage(msg, false);
                });
            }
            
            container.scrollTop = container.scrollHeight;
            
            if (messages.length > 0) {
                lastMessageId = messages[messages.length - 1].id;
            }
        }
        
        function appendStaffMessage(msg, scroll = true) {
            const container = document.getElementById('chatPopupMessages');
            const isOwn = msg.sender_id == currentUserId;
            const isSystem = msg.message_type === 'system';
            
            let html = '';
            const msgId = msg.id || Date.now();
            if (isSystem) {
                // Don't show system messages to customer (silent transfer)
                return;
            } else {
                const timeStr = formatTime(msg.created_at, msg.timestamp);
                
                // Show staff name and avatar for staff messages
                let senderInfo = '';
                let avatarHtml = '';
                if (!isOwn) {
                    const staffName = msg.sender_name || 'Nhân viên';
                    const staffAvatar = msg.sender_avatar;
                    
                    if (staffAvatar) {
                        avatarHtml = `<img src="${staffAvatar}" class="popup-message-avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="">`;
                    } else {
                        avatarHtml = '<div class="popup-message-avatar">👤</div>';
                    }
                    senderInfo = `<div style="font-size:11px;color:#666;margin-bottom:2px;">${escapeHtml(staffName)}</div>`;
                }
                
                html = `
                    <div class="popup-message ${isOwn ? 'own' : ''}" data-msg-id="${msgId}">
                        ${avatarHtml}
                        <div class="popup-message-content">
                            ${senderInfo}
                            <div class="popup-message-bubble">${escapeHtml(msg.content).replace(/\n/g, '<br>')}</div>
                            <div class="popup-message-time">${timeStr}</div>
                        </div>
                    </div>
                `;
            }
            
            container.insertAdjacentHTML('beforeend', html);
            
            if (scroll) {
                container.scrollTop = container.scrollHeight;
            }
            
            // Only update lastMessageId for real (integer) message IDs
            const msgIdInt = parseInt(msg.id);
            if (!isNaN(msgIdInt) && msgIdInt > 0 && msgIdInt < 100000000) {
                lastMessageId = Math.max(lastMessageId, msgIdInt);
            }
        }
        
        function send() {
            const input = document.getElementById('chatPopupInput');
            const content = input.value.trim();
            
            if (!content) return;
            input.value = '';
            
            if (mode === 'bot') {
                handleBotMessage(content);
            } else {
                sendStaffMessage(content);
            }
        }
        
        function handleBotMessage(content) {
            // Remove quick replies if any
            const qr = document.querySelector('.quick-replies');
            if (qr) qr.remove();
            
            addUserMessage(content);
            
            const lowerContent = content.toLowerCase().trim();
            let matched = false;
            
            // First check greetings
            for (const [keyword, response] of Object.entries(greetings)) {
                if (lowerContent.includes(keyword) || keyword.includes(lowerContent)) {
                    setTimeout(function() {
                        addBotMessage(response);
                        setTimeout(showQuickReplies, 300);
                    }, 600);
                    matched = true;
                    break;
                }
            }
            
            // Then check FAQ if no greeting matched
            if (!matched) {
                for (let i = 0; i < faqItems.length - 1; i++) { // Exclude last item (chat with staff)
                    const keywords = faqItems[i].q.toLowerCase().split(' ');
                    const matchCount = keywords.filter(k => k.length > 2 && lowerContent.includes(k)).length;
                    
                    if (matchCount >= 2 || lowerContent.includes(faqItems[i].q.toLowerCase().substring(0, 10))) {
                        setTimeout(function() {
                            addBotMessage(faqItems[i].a);
                            setTimeout(showQuickReplies, 300);
                        }, 800);
                        matched = true;
                        break;
                    }
                }
            }
            
            if (!matched) {
                setTimeout(function() {
                    if (isLoggedIn) {
                        addBotMessage('Tôi không tìm thấy câu trả lời phù hợp. Bạn có muốn chat trực tiếp với nhân viên không?');
                        const container = document.getElementById('chatPopupMessages');
                        container.insertAdjacentHTML('beforeend', `
                            <div class="quick-replies">
                                <button class="quick-reply-btn" onclick="PetshopChat.selectFaq(${faqItems.length - 1})">Chat với nhân viên</button>
                            </div>
                        `);
                    } else {
                        addBotMessage('Tôi không tìm thấy câu trả lời phù hợp. Vui lòng đăng nhập để chat với nhân viên hỗ trợ.');
                        showLoginPrompt();
                    }
                }, 800);
            }
        }
        
        function sendStaffMessage(content) {
            if (!currentConvId) {
                ajaxPost({
                    action: 'petshop_start_support_chat',
                    message: content
                }).then(function(response) {
                    if (response.success) {
                        currentConvId = response.data.conversation_id;
                        loadStaffMessages();
                        startPolling();
                    }
                }).catch(function(err) {
                    console.error('Failed to start chat:', err);
                });
            } else {
                // Optimistic UI - show message immediately
                // Use 'temp_' prefix to differentiate from real message IDs
                const tempMsg = {
                    id: 'temp_' + Date.now(),
                    sender_id: currentUserId,
                    content: content,
                    message_type: 'text',
                    timestamp: Date.now()
                };
                appendStaffMessage(tempMsg);
                
                ajaxPost({
                    action: 'petshop_send_message',
                    conversation_id: currentConvId,
                    content: content
                }).then(function(response) {
                    if (!response.success) {
                        alert('Không thể gửi tin nhắn: ' + (response.data || 'Lỗi không xác định'));
                    }
                }).catch(function(err) {
                    console.error('Failed to send message:', err);
                });
            }
        }
        
        function startPolling() {
            stopPolling();
            // Poll immediately first, then every 2 seconds
            pollNewMessages();
            pollInterval = setInterval(pollNewMessages, 2000);
        }
        
        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
        
        function pollNewMessages() {
            if (!currentConvId || !isOpen || mode !== 'staff') return;
            
            ajaxGet({
                action: 'petshop_poll_messages',
                conversation_id: currentConvId,
                last_message_id: lastMessageId
            }).then(function(response) {
                if (response.success) {
                    // Update conversation status
                    const conv = response.data.conversation;
                    if (conv) {
                        updateConversationStatus(conv.status);
                    }
                    
                    // Process new messages
                    if (response.data.has_new) {
                        const newMsgs = response.data.messages;
                        newMsgs.forEach(function(msg) {
                            // Check if message already exists in DOM
                            const existingMsg = document.querySelector('[data-msg-id="' + msg.id + '"]');
                            if (!existingMsg && msg.sender_id != currentUserId) {
                                appendStaffMessage(msg);
                            }
                            // Update lastMessageId only for real messages (integer IDs)
                            const msgIdInt = parseInt(msg.id);
                            if (!isNaN(msgIdInt) && msgIdInt > 0) {
                                lastMessageId = Math.max(lastMessageId, msgIdInt);
                            }
                        });
                    }
                }
            }).catch(function(err) {
                console.error('Polling error:', err);
            });
        }
        
        function updateConversationStatus(status) {
            const statusEl = document.getElementById('chatStatus');
            
            if (status === 'assigned') {
                statusEl.textContent = '🟢 Đang trò chuyện';
            } else if (status === 'open') {
                statusEl.textContent = 'Đang chờ nhân viên...';
            }
        }
        
        function startNewChat() {
            currentConvId = null;
            lastMessageId = 0;
            switchToStaffChat();
        }
        
        function backToBot() {
            mode = 'bot';
            currentConvId = null;
            lastMessageId = 0;
            stopPolling();
            showBotWelcome();
        }
        
        function addBotMessage(text) {
            const container = document.getElementById('chatPopupMessages');
            const html = `
                <div class="popup-message">
                    <div class="popup-message-avatar">🤖</div>
                    <div class="popup-message-content">
                        <div class="popup-message-bubble">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            container.scrollTop = container.scrollHeight;
        }
        
        function addUserMessage(text) {
            const container = document.getElementById('chatPopupMessages');
            const html = `
                <div class="popup-message own">
                    <div class="popup-message-content">
                        <div class="popup-message-bubble">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            container.scrollTop = container.scrollHeight;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatTime(datetime, timestamp) {
            let date;
            if (timestamp) {
                date = new Date(timestamp);
            } else if (datetime) {
                let dateStr = datetime;
                if (typeof dateStr === 'string' && !dateStr.includes('T') && !dateStr.includes('+')) {
                    dateStr = dateStr.replace(' ', 'T') + '+07:00';
                }
                date = new Date(dateStr);
            } else {
                return '';
            }
            return date.toLocaleTimeString('vi-VN', { 
                hour: '2-digit', 
                minute: '2-digit',
                timeZone: 'Asia/Ho_Chi_Minh'
            });
        }
        
        // Update unread badge
        function updateUnreadBadge() {
            if (!isLoggedIn) return;
            if (typeof jQuery === 'undefined') return;
            
            jQuery.get(ajaxurl, {
                action: 'petshop_count_unread'
            }, function(response) {
                if (response.success) {
                    const badge = document.getElementById('chatBadge');
                    if (badge) {
                        const count = response.data.count;
                        badge.textContent = count;
                        badge.style.display = count > 0 ? 'flex' : 'none';
                    }
                }
            });
        }
        
        // Initialize chat - make sure DOM is ready
        function initChat() {
            // Handle Enter key
            const input = document.getElementById('chatPopupInput');
            if (input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        send();
                    }
                });
            }
            
            // Update unread badge for logged in users
            if (isLoggedIn) {
                updateUnreadBadge();
                setInterval(updateUnreadBadge, 30000);
            }
        }
        
        // Run init when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChat);
        } else {
            initChat();
        }
        
        return {
            toggle: toggle,
            send: send,
            selectFaq: selectFaq,
            backToBot: backToBot,
            startNewChat: startNewChat
        };
    })();
    </script>
    <?php
}

// AJAX for non-logged in users
add_action('wp_ajax_nopriv_petshop_start_support_chat', 'petshop_ajax_guest_chat_prompt');
function petshop_ajax_guest_chat_prompt() {
    wp_send_json_error('Vui lòng đăng nhập để chat với chúng tôi');
}