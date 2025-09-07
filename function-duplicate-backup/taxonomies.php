<?php
/**
 * カスタムタクソノミー
 * 
 * このファイルでは、WordPressのカスタムタクソノミーを定義および登録します。
 * 助成金カテゴリー、都道府県、助成金タグ、ツールカテゴリー、成功事例カテゴリー、申請のコツカテゴリーなどが含まれます。
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * カスタムタクソノミー登録（完全版・都道府県対応・修正版）
 */
function gi_register_taxonomies() {
    // 助成金カテゴリー
    register_taxonomy("grant_category", "grant", array(
        "labels" => array(
            "name" => "助成金カテゴリー",
            "singular_name" => "助成金カテゴリー",
            "search_items" => "カテゴリーを検索",
            "all_items" => "すべてのカテゴリー",
            "parent_item" => "親カテゴリー",
            "parent_item_colon" => "親カテゴリー:",
            "edit_item" => "カテゴリーを編集",
            "update_item" => "カテゴリーを更新",
            "add_new_item" => "新しいカテゴリーを追加",
            "new_item_name" => "新しいカテゴリー名"
        ),
        "description" => "助成金・補助金をカテゴリー別に分類します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => true,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "grant-category",
            "with_front" => false,
            "hierarchical" => true
        )
    ));
    
    // 都道府県タクソノミー
    register_taxonomy("grant_prefecture", "grant", array(
        "labels" => array(
            "name" => "対象都道府県",
            "singular_name" => "都道府県",
            "search_items" => "都道府県を検索",
            "all_items" => "すべての都道府県",
            "edit_item" => "都道府県を編集",
            "update_item" => "都道府県を更新",
            "add_new_item" => "新しい都道府県を追加",
            "new_item_name" => "新しい都道府県名"
        ),
        "description" => "助成金・補助金の対象都道府県を管理します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => false,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "prefecture",
            "with_front" => false
        )
    ));
    
    // 助成金タグ
    register_taxonomy("grant_tag", "grant", array(
        "labels" => array(
            "name" => "助成金タグ",
            "singular_name" => "助成金タグ",
            "search_items" => "タグを検索",
            "all_items" => "すべてのタグ",
            "edit_item" => "タグを編集",
            "update_item" => "タグを更新",
            "add_new_item" => "新しいタグを追加",
            "new_item_name" => "新しいタグ名"
        ),
        "description" => "助成金・補助金をタグで分類します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => false,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "grant-tag",
            "with_front" => false
        )
    ));
    
    // ツールカテゴリー
    register_taxonomy("tool_category", "tool", array(
        "labels" => array(
            "name" => "ツールカテゴリー",
            "singular_name" => "ツールカテゴリー",
            "search_items" => "カテゴリーを検索",
            "all_items" => "すべてのカテゴリー",
            "parent_item" => "親カテゴリー",
            "parent_item_colon" => "親カテゴリー:",
            "edit_item" => "カテゴリーを編集",
            "update_item" => "カテゴリーを更新",
            "add_new_item" => "新しいカテゴリーを追加",
            "new_item_name" => "新しいカテゴリー名"
        ),
        "description" => "ビジネスツールをカテゴリー別に分類します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => true,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "tool-category",
            "with_front" => false,
            "hierarchical" => true
        )
    ));
    
    // 成功事例カテゴリー
    register_taxonomy("case_study_category", "case_study", array(
        "labels" => array(
            "name" => "成功事例カテゴリー",
            "singular_name" => "成功事例カテゴリー",
            "search_items" => "カテゴリーを検索",
            "all_items" => "すべてのカテゴリー",
            "parent_item" => "親カテゴリー",
            "parent_item_colon" => "親カテゴリー:",
            "edit_item" => "カテゴリーを編集",
            "update_item" => "カテゴリーを更新",
            "add_new_item" => "新しいカテゴリーを追加",
            "new_item_name" => "新しいカテゴリー名"
        ),
        "description" => "成功事例をカテゴリー別に分類します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => true,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "case-category",
            "with_front" => false,
            "hierarchical" => true
        )
    ));

    // 【修正】申請のコツカテゴリー（不足していたタクソノミー）
    register_taxonomy("grant_tip_category", "grant_tip", array(
        "labels" => array(
            "name" => "申請のコツカテゴリー",
            "singular_name" => "申請のコツカテゴリー",
            "search_items" => "カテゴリーを検索",
            "all_items" => "すべてのカテゴリー",
            "parent_item" => "親カテゴリー",
            "parent_item_colon" => "親カテゴリー:",
            "edit_item" => "カテゴリーを編集",
            "update_item" => "カテゴリーを更新",
            "add_new_item" => "新しいカテゴリーを追加",
            "new_item_name" => "新しいカテゴリー名"
        ),
        "description" => "申請のコツをカテゴリー別に分類します",
        "public" => true,
        "publicly_queryable" => true,
        "hierarchical" => true,
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "show_in_rest" => true,
        "show_tagcloud" => true,
        "show_admin_column" => true,
        "query_var" => true,
        "rewrite" => array(
            "slug" => "grant-tip-category",
            "with_front" => false,
            "hierarchical" => true
        )
    ));
}
add_action("init", "gi_register_taxonomies");

/**
 * デフォルト都道府県データの挿入
 */
function gi_insert_default_prefectures() {
    $prefectures = array(
        "全国対応", "北海道", "青森県", "岩手県", "宮城県", "秋田県", "山形県", "福島県",
        "茨城県", "栃木県", "群馬県", "埼玉県", "千葉県", "東京都", "神奈川県",
        "新潟県", "富山県", "石川県", "福井県", "山梨県", "長野県", "岐阜県",
        "静岡県", "愛知県", "三重県", "滋賀県", "京都府", "大阪府", "兵庫県",
        "奈良県", "和歌山県", "鳥取県", "島根県", "岡山県", "広島県", "山口県",
        "徳島県", "香川県", "愛媛県", "高知県", "福岡県", "佐賀県", "長崎県",
        "熊本県", "大分県", "宮崎県", "鹿児島県", "沖縄県"
    );

    foreach ($prefectures as $prefecture) {
        if (!term_exists($prefecture, "grant_prefecture")) {
            wp_insert_term(
                $prefecture,
                "grant_prefecture",
                array(
                    "slug" => sanitize_title($prefecture)
                )
            );
        }
    }
}
add_action("init", "gi_insert_default_prefectures");


