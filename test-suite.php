<?php
/**
 * Grant Insight Test Suite
 * テストスイート - 改良機能の動作確認
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * テストスイートクラス
 */
class GI_Test_Suite {
    
    private $test_results = array();
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    /**
     * 全テストの実行
     */
    public function run_all_tests() {
        $this->reset_results();
        
        echo "<h1>Grant Insight テストスイート実行結果</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px 0;'>\n";
        
        // 基本機能テスト
        $this->test_basic_functions();
        
        // パフォーマンステスト
        $this->test_performance_features();
        
        // セキュリティテスト
        $this->test_security_features();
        
        // AJAX機能テスト
        $this->test_ajax_functions();
        
        // データベーステスト
        $this->test_database_functions();
        
        // 統合機能テスト
        $this->test_integration_features();
        
        // 結果サマリー
        $this->display_summary();
        
        echo "</div>\n";
        
        return $this->test_results;
    }
    
    /**
     * 結果のリセット
     */
    private function reset_results() {
        $this->test_results = array();
        $this->total_tests = 0;
        $this->passed_tests = 0;
        $this->failed_tests = 0;
    }
    
    /**
     * テスト実行
     */
    private function run_test($test_name, $test_function) {
        $this->total_tests++;
        
        try {
            $start_time = microtime(true);
            $result = call_user_func($test_function);
            $execution_time = microtime(true) - $start_time;
            
            if ($result) {
                $this->passed_tests++;
                $status = 'PASS';
                $color = 'green';
            } else {
                $this->failed_tests++;
                $status = 'FAIL';
                $color = 'red';
            }
            
            $this->test_results[] = array(
                'name' => $test_name,
                'status' => $status,
                'execution_time' => $execution_time,
                'result' => $result
            );
            
            echo sprintf(
                "<div style='color: %s;'>%s [%s] (%.4fs)</div>\n",
                $color,
                $test_name,
                $status,
                $execution_time
            );
            
        } catch (Exception $e) {
            $this->failed_tests++;
            $this->test_results[] = array(
                'name' => $test_name,
                'status' => 'ERROR',
                'execution_time' => 0,
                'result' => false,
                'error' => $e->getMessage()
            );
            
            echo sprintf(
                "<div style='color: red;'>%s [ERROR] - %s</div>\n",
                $test_name,
                $e->getMessage()
            );
        }
    }
    
    /**
     * 基本機能テスト
     */
    private function test_basic_functions() {
        echo "<h2>基本機能テスト</h2>\n";
        
        $this->run_test('テーマ定数の定義確認', function() {
            return defined('GI_THEME_VERSION') && defined('GI_THEME_PREFIX');
        });
        
        $this->run_test('カスタム投稿タイプの登録確認', function() {
            return post_type_exists('grant') && post_type_exists('tool') && post_type_exists('case_study');
        });
        
        $this->run_test('カスタムタクソノミーの登録確認', function() {
            return taxonomy_exists('grant_category') && taxonomy_exists('prefecture') && taxonomy_exists('tool_category');
        });
        
        $this->run_test('必須関数の存在確認', function() {
            return function_exists('gi_setup') && 
                   function_exists('gi_ajax_load_grants') && 
                   function_exists('gi_safe_number_format');
        });
        
        $this->run_test('デフォルトデータの存在確認', function() {
            $prefectures = get_terms(array('taxonomy' => 'prefecture', 'hide_empty' => false));
            $categories = get_terms(array('taxonomy' => 'grant_category', 'hide_empty' => false));
            return count($prefectures) >= 47 && count($categories) >= 5;
        });
    }
    
    /**
     * パフォーマンステスト
     */
    private function test_performance_features() {
        echo "<h2>パフォーマンステスト</h2>\n";
        
        $this->run_test('キャッシュシステムの動作確認', function() {
            if (!class_exists('GI_Cache_System')) {
                return false;
            }
            
            $cache = GI_Cache_System::getInstance();
            $test_key = 'test_cache_' . time();
            $test_data = array('test' => 'data', 'timestamp' => time());
            
            // キャッシュ設定
            $cache->set($test_key, $test_data, 60);
            
            // キャッシュ取得
            $cached_data = $cache->get($test_key);
            
            // キャッシュ削除
            $cache->delete($test_key);
            
            return $cached_data === $test_data;
        });
        
        $this->run_test('一括データ取得の動作確認', function() {
            if (!function_exists('gi_bulk_get_post_meta')) {
                return false;
            }
            
            // テスト用投稿を作成
            $post_id = wp_insert_post(array(
                'post_title' => 'Test Grant',
                'post_type' => 'grant',
                'post_status' => 'publish'
            ));
            
            if (!$post_id) {
                return false;
            }
            
            // メタデータを追加
            update_post_meta($post_id, 'amount_max', '1000000');
            update_post_meta($post_id, 'deadline', '2024-12-31');
            
            // 一括取得テスト
            $bulk_data = gi_bulk_get_post_meta(array($post_id));
            
            // テスト投稿を削除
            wp_delete_post($post_id, true);
            
            return isset($bulk_data[$post_id]['amount_max']) && 
                   $bulk_data[$post_id]['amount_max'] === '1000000';
        });
        
        $this->run_test('クエリ最適化の動作確認', function() {
            if (!class_exists('GI_Query_Optimizer')) {
                return false;
            }
            
            $args = array(
                'post_type' => 'grant',
                'posts_per_page' => 5,
                'meta_query' => array(
                    array('key' => 'amount_max', 'value' => 0, 'compare' => '>'),
                    array('key' => 'empty_field', 'value' => '', 'compare' => '=') // 空の条件
                )
            );
            
            $optimized_query = GI_Query_Optimizer::optimize_search_query($args);
            
            return $optimized_query instanceof WP_Query;
        });
        
        $this->run_test('リソース最適化の確認', function() {
            return class_exists('GI_Resource_Optimizer') && 
                   method_exists('GI_Resource_Optimizer', 'conditional_enqueue') &&
                   method_exists('GI_Resource_Optimizer', 'remove_unnecessary_scripts');
        });
    }
    
    /**
     * セキュリティテスト
     */
    private function test_security_features() {
        echo "<h2>セキュリティテスト</h2>\n";
        
        $this->run_test('セキュリティ強化クラスの存在確認', function() {
            return class_exists('GI_Security_Enhancement');
        });
        
        $this->run_test('nonce検証強化の動作確認', function() {
            if (!class_exists('GI_Security_Enhancement')) {
                return false;
            }
            
            // 有効なnonceのテスト
            $valid_nonce = wp_create_nonce('gi_ajax_nonce');
            $result1 = GI_Security_Enhancement::verify_nonce_enhanced($valid_nonce, array('gi_ajax_nonce'));
            
            // 無効なnonceのテスト
            $invalid_nonce = 'invalid_nonce_12345';
            $result2 = GI_Security_Enhancement::verify_nonce_enhanced($invalid_nonce, array('gi_ajax_nonce'));
            
            return $result1 === true && $result2 === false;
        });
        
        $this->run_test('データサニタイズ強化の動作確認', function() {
            if (!class_exists('GI_Security_Enhancement')) {
                return false;
            }
            
            $test_data = array(
                'text' => '<script>alert("xss")</script>Hello',
                'email' => 'test@example.com',
                'number' => '123.45',
                'array' => array('item1', '<script>alert("xss")</script>item2')
            );
            
            $sanitized = GI_Security_Enhancement::sanitize_data_enhanced($test_data['text']);
            $sanitized_email = GI_Security_Enhancement::sanitize_data_enhanced($test_data['email'], 'email');
            $sanitized_number = GI_Security_Enhancement::sanitize_data_enhanced($test_data['number'], 'float');
            $sanitized_array = GI_Security_Enhancement::sanitize_data_enhanced($test_data['array']);
            
            return strpos($sanitized, '<script>') === false &&
                   $sanitized_email === 'test@example.com' &&
                   $sanitized_number === 123.45 &&
                   is_array($sanitized_array);
        });
        
        $this->run_test('暗号化機能の動作確認', function() {
            if (!class_exists('GI_Security_Enhancement')) {
                return false;
            }
            
            $original_data = 'sensitive personal information';
            $encrypted = GI_Security_Enhancement::encrypt_personal_data($original_data);
            $decrypted = GI_Security_Enhancement::decrypt_personal_data($encrypted);
            
            return $encrypted !== $original_data && $decrypted === $original_data;
        });
    }
    
    /**
     * AJAX機能テスト
     */
    private function test_ajax_functions() {
        echo "<h2>AJAX機能テスト</h2>\n";
        
        $this->run_test('AJAX関数の存在確認', function() {
            return function_exists('gi_ajax_load_grants') &&
                   function_exists('gi_ajax_advanced_search');
        });
        
        $this->run_test('最適化AJAX関数の存在確認', function() {
            return function_exists('gi_ajax_load_grants_optimized');
        });
        
        $this->run_test('セキュアAJAX関数の存在確認', function() {
            return function_exists('gi_ajax_load_grants_secure');
        });
        
        $this->run_test('AJAX アクションフックの登録確認', function() {
            global $wp_filter;
            
            $ajax_hooks = array(
                'wp_ajax_gi_load_grants',
                'wp_ajax_nopriv_gi_load_grants',
                'wp_ajax_gi_advanced_search',
                'wp_ajax_nopriv_gi_advanced_search'
            );
            
            foreach ($ajax_hooks as $hook) {
                if (!isset($wp_filter[$hook])) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * データベーステスト
     */
    private function test_database_functions() {
        echo "<h2>データベーステスト</h2>\n";
        
        $this->run_test('データベース最適化クラスの存在確認', function() {
            return class_exists('GI_Database_Optimizer');
        });
        
        $this->run_test('期限切れキャッシュクリーンアップの動作確認', function() {
            if (!class_exists('GI_Database_Optimizer')) {
                return false;
            }
            
            // 期限切れのtransientを作成
            set_transient('gi_test_expired', 'test_data', -1);
            
            // クリーンアップ実行
            GI_Database_Optimizer::cleanup_expired_cache();
            
            // 期限切れtransientが削除されているか確認
            return get_transient('gi_test_expired') === false;
        });
        
        $this->run_test('データベース接続の確認', function() {
            global $wpdb;
            
            $result = $wpdb->get_var("SELECT 1");
            return $result === '1';
        });
        
        $this->run_test('カスタムテーブルの存在確認', function() {
            global $wpdb;
            
            // 基本的なWordPressテーブルの存在確認
            $tables = array(
                $wpdb->posts,
                $wpdb->postmeta,
                $wpdb->terms,
                $wpdb->term_taxonomy,
                $wpdb->term_relationships
            );
            
            foreach ($tables as $table) {
                $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                if ($result !== $table) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * 統合機能テスト
     */
    private function test_integration_features() {
        echo "<h2>統合機能テスト</h2>\n";
        
        $this->run_test('統合マスターの初期化確認', function() {
            return defined('GI_INTEGRATION_MASTER_LOADED') && 
                   GI_INTEGRATION_MASTER_LOADED === true;
        });
        
        $this->run_test('統合マスタークラスの存在確認', function() {
            return class_exists('GI_Integration_Master');
        });
        
        $this->run_test('統合ステータス取得の動作確認', function() {
            if (!function_exists('gi_get_integration_status')) {
                return false;
            }
            
            $status = gi_get_integration_status();
            
            return is_array($status) && 
                   isset($status['version']) && 
                   isset($status['loaded_modules']);
        });
        
        $this->run_test('統合アクティブ状態の確認', function() {
            return function_exists('gi_is_integration_active') && 
                   gi_is_integration_active() === true;
        });
        
        $this->run_test('スケジュールイベントの登録確認', function() {
            return wp_next_scheduled('gi_daily_cleanup') !== false ||
                   wp_next_scheduled('gi_weekly_optimization') !== false;
        });
    }
    
    /**
     * 結果サマリーの表示
     */
    private function display_summary() {
        echo "<h2>テスト結果サマリー</h2>\n";
        
        $success_rate = $this->total_tests > 0 ? ($this->passed_tests / $this->total_tests) * 100 : 0;
        
        echo "<div style='background: #fff; border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>\n";
        echo "<strong>総テスト数:</strong> {$this->total_tests}<br>\n";
        echo "<strong style='color: green;'>成功:</strong> {$this->passed_tests}<br>\n";
        echo "<strong style='color: red;'>失敗:</strong> {$this->failed_tests}<br>\n";
        echo "<strong>成功率:</strong> " . number_format($success_rate, 1) . "%<br>\n";
        
        if ($success_rate >= 90) {
            echo "<div style='color: green; font-weight: bold; margin-top: 10px;'>✅ 優秀 - システムは正常に動作しています</div>\n";
        } elseif ($success_rate >= 70) {
            echo "<div style='color: orange; font-weight: bold; margin-top: 10px;'>⚠️ 注意 - いくつかの問題があります</div>\n";
        } else {
            echo "<div style='color: red; font-weight: bold; margin-top: 10px;'>❌ 警告 - 重大な問題があります</div>\n";
        }
        
        echo "</div>\n";
        
        // 失敗したテストの詳細
        if ($this->failed_tests > 0) {
            echo "<h3>失敗したテスト</h3>\n";
            echo "<div style='background: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin: 10px 0;'>\n";
            
            foreach ($this->test_results as $result) {
                if ($result['status'] !== 'PASS') {
                    echo "<div>• {$result['name']} - {$result['status']}";
                    if (isset($result['error'])) {
                        echo " ({$result['error']})";
                    }
                    echo "</div>\n";
                }
            }
            
            echo "</div>\n";
        }
        
        // パフォーマンス情報
        echo "<h3>パフォーマンス情報</h3>\n";
        echo "<div style='background: #f0f8ff; border: 1px solid #b0d4f1; padding: 10px; margin: 10px 0;'>\n";
        echo "<strong>メモリ使用量:</strong> " . size_format(memory_get_peak_usage(true)) . "<br>\n";
        echo "<strong>実行時間:</strong> " . number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . "ms<br>\n";
        echo "<strong>データベースクエリ数:</strong> " . get_num_queries() . "<br>\n";
        echo "</div>\n";
    }
    
    /**
     * テスト結果の取得
     */
    public function get_results() {
        return array(
            'total_tests' => $this->total_tests,
            'passed_tests' => $this->passed_tests,
            'failed_tests' => $this->failed_tests,
            'success_rate' => $this->total_tests > 0 ? ($this->passed_tests / $this->total_tests) * 100 : 0,
            'test_results' => $this->test_results
        );
    }
}

// テストスイートの実行（管理者のみ）
if (is_admin() && current_user_can('manage_options') && isset($_GET['run_gi_tests'])) {
    $test_suite = new GI_Test_Suite();
    $test_suite->run_all_tests();
    exit;
}

