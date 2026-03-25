<?php
if (is_category() || is_post_type_archive('post') || is_page('tin-tuc')) {
    include get_template_directory() . '/archive-news.php';
    return;
}
// ...existing code for other cases...
