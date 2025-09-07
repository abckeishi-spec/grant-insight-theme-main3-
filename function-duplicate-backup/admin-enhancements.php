<?php
/**
 * 管理画面拡張
 * 
 * このファイルでは、WordPress管理画面の機能拡張やカスタマイズを行います。
 * 投稿一覧のカラム追加、カスタムダッシュボードウィジェットなどが含まれます。
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * 管理画面カスタマイズ（強化版）
 */
function gi_admin_init() {
    // 管理画面スタイル
    add_action("admin_head", function() {
        echo 
        "<style>
        .gi-admin-notice {
            border-left: 4px solid #10b981;
            background: #ecfdf5;
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .gi-admin-notice h3 {
            color: #047857;
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        .gi-admin-notice p {
            color: #065f46;
            margin: 0;
        }
        </style>";
    });
    
    // 投稿一覧カラム追加
    add_filter("manage_grant_posts_columns", "gi_add_grant_columns");
    add_action("manage_grant_posts_custom_column", "gi_grant_column_content", 10, 2);
}
add_action("admin_init", "gi_admin_init");

// 助成金カラム
function gi_add_grant_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === "title") {
            $new_columns["gi_prefecture"] = "都道府県";
            $new_columns["gi_amount"] = "金額";
            $new_columns["gi_organization"] = "実施組織";
            $new_columns["gi_status"] = "ステータス";
        }
    }
    return $new_columns;
}

function gi_grant_column_content($column, $post_id) {
    switch ($column) {
        case "gi_prefecture":
            $prefecture_terms = get_the_terms($post_id, "grant_prefecture");
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                echo gi_safe_escape($prefecture_terms[0]->name);
            } else {
                echo "－";
            }
            break;
        case "gi_amount":
            $amount = gi_safe_get_meta($post_id, "max_amount");
            echo $amount ? gi_safe_number_format($amount) . "万円" : "－";
            break;
        case "gi_organization":
            echo gi_safe_escape(gi_safe_get_meta($post_id, "organization", "－"));
            break;
        case "gi_status":
            $status = gi_map_application_status_ui(gi_safe_get_meta($post_id, "application_status", "open"));
            $status_labels = array(
                "active" => "<span style=\"color: #059669;\">募集中</span>",
                "upcoming" => "<span style=\"color: #d97706;\">募集予定</span>",
                "closed" => "<span style=\"color: #dc2626;\">募集終了</span>"
            );
            echo $status_labels[$status] ?? $status;
            break;
    }
}


