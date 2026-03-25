<?php
// Chỉ cho phép chọn 1 danh mục con khi tạo/sửa bài viết
add_filter('wp_terms_checklist_args', function($args, $post_id) {
    if ($args['taxonomy'] === 'category' && get_post_type($post_id) === 'post') {
        $args['walker'] = new Petshop_Single_Subcategory_Walker();
    }
    return $args;
}, 10, 2);

class Petshop_Single_Subcategory_Walker extends Walker {
    var $tree_type = 'category';
    var $db_fields = array('parent' => 'parent', 'id' => 'term_id');

    function start_lvl(&$output, $depth = 0, $args = array()) {
        $output .= '<ul class="children">';
    }
    function end_lvl(&$output, $depth = 0, $args = array()) {
        $output .= '</ul>';
    }
    function start_el(&$output, $term, $depth = 0, $args = array(), $id = 0) {
        $is_parent = ($term->parent == 0);
        $checked = in_array($term->term_id, $args['selected_cats']) ? 'checked="checked"' : '';
        $disabled = $is_parent ? 'disabled' : '';
        $input_type = 'radio';
        $output .= '<li id="category-' . $term->term_id . '"><label>'; 
        $output .= '<input type="' . $input_type . '" name="post_category[]" value="' . $term->term_id . '" ' . $checked . ' ' . $disabled . '> ' . esc_html($term->name);
        $output .= '</label>';
    }
    function end_el(&$output, $term, $depth = 0, $args = array()) {
        $output .= '</li>';
    }
}
