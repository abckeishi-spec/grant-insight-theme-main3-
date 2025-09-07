<?php
/**
 * ヘルパー関数
 * 
 * このファイルでは、テーマ全体で再利用可能なヘルパー関数を定義します。
 * 安全なデータ取得、フォーマット、URL生成などの関数が含まれます。
 */

if (!defined('ABSPATH')) {
    exit;
}

// 【修正】未定義関数の追加

// 締切日のフォーマット関数
function gi_get_formatted_deadline($post_id) {
    $deadline = gi_safe_get_meta($post_id, 'deadline_date');
    if (!$deadline) {
        // 旧フィールドも確認
        $deadline = gi_safe_get_meta($post_id, 'deadline');
    }
    
    if (!$deadline) {
        return '';
    }
    
    // 数値の場合（UNIXタイムスタンプ）
    if (is_numeric($deadline)) {
        return date('Y年m月d日', intval($deadline));
    }
    
    // 文字列の場合
    $timestamp = strtotime($deadline);
    if ($timestamp !== false) {
        return date('Y年m月d日', $timestamp);
    }
    
    return $deadline;
}

/**
 * 【修正】メタフィールドの同期処理（ACF対応）
 */
function gi_sync_grant_meta_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;
    if (!current_user_can('edit_post', $post_id)) return;

    // 金額の数値版を作成
    $amount_text = get_post_meta($post_id, 'max_amount', true);
    if (!$amount_text) {
        // ACFフィールドも確認
        $amount_text = get_field('max_amount', $post_id);
    }
    
    if ($amount_text) {
        // 数値のみを抽出
        $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
        if ($amount_numeric) {
            update_post_meta($post_id, 'max_amount_numeric', intval($amount_numeric));
        }
    }

    // 日付の数値版を作成
    $deadline = get_post_meta($post_id, 'deadline', true);
    if (!$deadline) {
        // ACFフィールドも確認
        $deadline = get_field('deadline', $post_id);
    }
    
    if ($deadline) {
        if (is_numeric($deadline)) {
            update_post_meta($post_id, 'deadline_date', intval($deadline));
        } else {
            $deadline_numeric = strtotime($deadline);
            if ($deadline_numeric !== false) {
                update_post_meta($post_id, 'deadline_date', $deadline_numeric);
            }
        }
    }

    // ステータスの同期
    $status = get_post_meta($post_id, 'status', true);
    if (!$status) {
        $status = get_field('application_status', $post_id);
    }
    
    if ($status) {
        update_post_meta($post_id, 'application_status', $status);
    } else {
        // デフォルトステータス
        update_post_meta($post_id, 'application_status', 'open');
    }

    // 組織名の同期
    $organization = get_field('organization', $post_id);
    if ($organization) {
        update_post_meta($post_id, 'organization', $organization);
    }
}
add_action('save_post', 'gi_sync_grant_meta_on_save', 20, 3);

/**
 * 動的パス取得関数（完全版）
 */

// アセットURL取得
function gi_get_asset_url($path) {
    $path = ltrim($path, '/');
    return get_template_directory_uri() . '/' . $path;
}

// アップロードURL取得
function gi_get_upload_url($filename) {
    $upload_dir = wp_upload_dir();
    $filename = ltrim($filename, '/');
    return $upload_dir['baseurl'] . '/' . $filename;
}

// メディアURL取得（自動検出機能付き）
function gi_get_media_url($filename, $fallback = true) {
    if (empty($filename)) {
        return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
    }
    
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    $filename = str_replace([
        'http://keishi0804.xsrv.jp/wp-content/uploads/',
        'https://keishi0804.xsrv.jp/wp-content/uploads/',
        '/wp-content/uploads/'
    ], '', $filename);
    
    $filename = ltrim($filename, '/');
    
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $filename;
    
    if (file_exists($file_path)) {
        return $upload_dir['baseurl'] . '/' . $filename;
    }
    
    $current_year = date('Y');
    $current_month = date('m');
    
    $possible_paths = [
        $current_year . '/' . $current_month . '/' . $filename,
        $current_year . '/' . $filename,
        'uploads/' . $filename,
        'media/' . $filename
    ];
    
    foreach ($possible_paths as $path) {
        $full_path = $upload_dir['basedir'] . '/' . $path;
        if (file_exists($full_path)) {
            return $upload_dir['baseurl'] . '/' . $path;
        }
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/placeholder.jpg');
    }
    
    return '';
}

// 動画URL取得
function gi_get_video_url($filename, $fallback = true) {
    $url = gi_get_media_url($filename, false);
    
    if (!empty($url)) {
        return $url;
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/videos/placeholder.mp4');
    }
    
    return '';
}

// ロゴURL取得
function gi_get_logo_url($fallback = true) {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    $hero_logo = get_theme_mod('gi_hero_logo');
    if ($hero_logo) {
        return gi_get_media_url($hero_logo, false);
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/logo.png');
    }
    
    return '';
}

/**
 * 補助ヘルパー: 金額（円）を万円表示用に整形
 */
function gi_format_amount_man($amount_yen, $amount_text = '') {
    $yen = is_numeric($amount_yen) ? intval($amount_yen) : 0;
    if ($yen > 0) {
        return gi_safe_number_format(intval($yen / 10000));
    }
    if (!empty($amount_text)) {
        if (preg_match('/([0-9,]+)\s*万円/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
        if (preg_match('/([0-9,]+)/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
    }
    return '0';
}

/**
 * 補助ヘルパー: ACFのapplication_statusをUI用にマッピング
 */
function gi_map_application_status_ui($app_status) {
    switch ($app_status) {
        case 'open':
            return 'active';
        case 'upcoming':
            return 'upcoming';
        case 'closed':
            return 'closed';
        default:
            return 'active';
    }
}

/**
 * お気に入り一覧取得
 */
function gi_get_user_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
    } else {
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        if (!is_array($favorites)) $favorites = array();
    }
    
    return array_map('intval', $favorites);
}

/**
 * Sync ACF prefecture meta to grant_prefecture taxonomy on save
 */
function gi_sync_grant_prefectures_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;

    // Try common meta keys
    $meta_values = array();
    $candidates = array('prefecture', 'prefectures', 'grant_prefecture');
    foreach ($candidates as $key) {
        $val = gi_safe_get_meta($post_id, $key, '');
        if (!empty($val)) {
            if (is_array($val)) {
                $meta_values = $val;
            } else {
                // Split by comma or pipe if stored as text
                $meta_values = preg_split('/[,|]/u', $val);
            }
            break;
        }
    }
    if (empty($meta_values)) return;

    $term_ids = array();
    foreach ($meta_values as $raw) {
        $name = trim(wp_strip_all_tags($raw));
        if ($name === '') continue;
        $term = get_term_by('name', $name, 'grant_prefecture');
        if (!$term) {
            // Try slug match
            $term = get_term_by('slug', sanitize_title($name), 'grant_prefecture');
        }
        if ($term && !is_wp_error($term)) {
            $term_ids[] = intval($term->term_id);
        }
    }
    if (!empty($term_ids)) {
        wp_set_post_terms($post_id, $term_ids, 'grant_prefecture', false);
    }
}
add_action('save_post', 'gi_sync_grant_prefectures_on_save', 20, 3);

/**
 * 投稿カテゴリー取得
 */
function gi_get_post_categories($post_id) {
    $post_type = get_post_type($post_id);
    $taxonomy = $post_type . '_category';
    
    if (!taxonomy_exists($taxonomy)) {
        return array();
    }
    
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return array();
    }
    
    return array_map(function($term) {
        return array(
            'name' => $term->name,
            'slug' => $term->slug,
            'link' => get_term_link($term)
        );
    }, $terms);
}

/**
 * 【修正】カード表示関数（完全版）
 */
function gi_render_grant_card($post_id, $view = 'grid') {
    if (!$post_id || !get_post($post_id)) {
        return '';
    }

    $post = get_post($post_id);
    $user_favorites = gi_get_user_favorites();

    // 都道府県取得
    $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
    $prefecture = '';
    if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
        $prefecture = $prefecture_terms[0]->name;
    }

    // カテゴリー取得
    $category_terms = get_the_terms($post_id, 'grant_category');
    $main_category = '';
    $related_categories = array();
    
    if ($category_terms && !is_wp_error($category_terms)) {
        $main_category = $category_terms[0]->name;
        if (count($category_terms) > 1) {
            for ($i = 1; $i < count($category_terms); $i++) {
                $related_categories[] = $category_terms[$i]->name;
            }
        }
    }

    // データ取得
    $data = array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'excerpt' => gi_safe_excerpt(get_post_field('post_excerpt', $post_id), 150),
        'permalink' => get_permalink($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
        'date' => get_the_date('Y-m-d', $post_id),
        'prefecture' => $prefecture,
        'main_category' => $main_category,
        'related_categories' => $related_categories,
        'amount' => gi_format_amount_man(gi_safe_get_meta($post_id, 'max_amount_numeric', 0), gi_safe_get_meta($post_id, 'max_amount', '')),
        'organization' => gi_safe_escape(gi_safe_get_meta($post_id, 'organization')),
        'deadline' => gi_get_formatted_deadline($post_id),
        'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')),
        'is_favorite' => in_array($post_id, $user_favorites)
    );

    if ($view === 'list') {
        return gi_render_grant_card_list($data);
    } else {
        return gi_render_grant_card_grid($data);
    }
}

/**
 * グリッドカード表示
 */
function gi_render_grant_card_grid($grant) {
    ob_start();
    ?>
    <div class="grant-card bg-white rounded-xl shadow-sm border hover:shadow-lg transition-all duration-300 overflow-hidden animate-fade-in-up">
        <div class="relative">
            <?php if ($grant['thumbnail']) : ?>
                <img src="<?php echo gi_safe_url($grant['thumbnail']); ?>" alt="<?php echo gi_safe_attr($grant['title']); ?>" class="w-full h-48 object-cover">
            <?php else : ?>
                <div class="w-full h-48 bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <i class="fas fa-coins text-4xl text-white"></i>
                </div>
            <?php endif; ?>
            
            <!-- ステータスバッジ -->
            <div class="absolute top-3 left-3">
                <?php echo gi_get_status_badge($grant['status']); ?>
            </div>
            
            <!-- お気に入りボタン -->
            <button class="favorite-btn absolute top-3 right-3 w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center transition-all duration-200 <?php echo $grant['is_favorite'] ? 'text-red-500' : 'text-gray-400'; ?>"
                    data-post-id="<?php echo $grant['id']; ?>">
                <i class="fas fa-heart text-sm"></i>
            </button>
        </div>
        
        <div class="p-6">
            <!-- 都道府県・カテゴリ -->
            <div class="mb-3">
                <?php if ($grant['prefecture']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full mr-2 mb-1">
                        📍 <?php echo gi_safe_escape($grant['prefecture']); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($grant['main_category']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full mb-1">
                        <?php echo gi_safe_escape($grant['main_category']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- タイトル -->
            <h3 class="text-lg font-bold text-gray-800 mb-3 leading-tight hover:text-emerald-600 transition-colors">
                <a href="<?php echo gi_safe_url($grant['permalink']); ?>"><?php echo gi_safe_escape($grant['title']); ?></a>
            </h3>
            
            <!-- 詳細情報 -->
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-center">
                    <i class="fas fa-building w-4 text-center mr-2 text-gray-400"></i>
                    <span><?php echo $grant['organization'] ?: 'N/A'; ?></span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-yen-sign w-4 text-center mr-2 text-gray-400"></i>
                    <span>最大 <strong class="text-red-500 font-bold text-base"><?php echo $grant['amount']; ?></strong> 万円</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt w-4 text-center mr-2 text-gray-400"></i>
                    <span><?php echo $grant['deadline'] ?: '随時'; ?></span>
                </div>
            </div>
            
            <!-- 関連カテゴリ -->
            <?php if (!empty($grant['related_categories'])) : ?>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <?php foreach ($grant['related_categories'] as $rel_cat) : ?>
                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-md mr-1 mb-1">
                            <?php echo gi_safe_escape($rel_cat); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * リストカード表示
 */
function gi_render_grant_card_list($grant) {
    ob_start();
    ?>
    <div class="grant-card-list bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300 flex items-center p-4 animate-fade-in">
        <div class="flex-grow pr-6">
            <div class="flex items-center mb-2">
                <?php if ($grant['prefecture']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full mr-2">
                        📍 <?php echo gi_safe_escape($grant['prefecture']); ?>
                    </span>
                <?php endif; ?>
                <?php if ($grant['main_category']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">
                        <?php echo gi_safe_escape($grant['main_category']); ?>
                    </span>
                <?php endif; ?>
            </div>
            <h3 class="text-md font-bold text-gray-800 hover:text-emerald-600 transition-colors">
                <a href="<?php echo gi_safe_url($grant['permalink']); ?>"><?php echo gi_safe_escape($grant['title']); ?></a>
            </h3>
            <p class="text-sm text-gray-500 mt-1"><?php echo $grant['organization'] ?: 'N/A'; ?></p>
        </div>
        <div class="flex-shrink-0 w-48 text-right">
            <div class="text-lg font-bold text-red-500">
                最大 <?php echo $grant['amount']; ?> 万円
            </div>
            <div class="text-sm text-gray-600 mt-1">
                締切: <?php echo $grant['deadline'] ?: '随時'; ?>
            </div>
        </div>
        <div class="flex-shrink-0 ml-4">
            <button class="favorite-btn w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-all duration-200 <?php echo $grant['is_favorite'] ? 'text-red-500' : 'text-gray-400'; ?>"
                    data-post-id="<?php echo $grant['id']; ?>">
                <i class="fas fa-heart"></i>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ステータスバッジ取得
 */
function gi_get_status_badge($status) {
    $badge_map = array(
        'active' => '<span class="px-3 py-1 text-xs font-bold text-white bg-emerald-500 rounded-full shadow">募集中</span>',
        'upcoming' => '<span class="px-3 py-1 text-xs font-bold text-white bg-blue-500 rounded-full shadow">予告</span>',
        'closed' => '<span class="px-3 py-1 text-xs font-bold text-white bg-gray-400 rounded-full shadow">終了</span>'
    );
    return $badge_map[$status] ?? $badge_map['active'];
}


