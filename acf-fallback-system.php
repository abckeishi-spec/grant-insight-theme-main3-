<?php
/**
 * Grant Insight ACF Fallback System
 * Advanced Custom Fields 非依存フォールバックシステム
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF フォールバックシステムクラス
 */
class GI_ACF_Fallback_System {
    
    private static $instance = null;
    private $acf_available = false;
    private $field_mappings = array();
    
    /**
     * シングルトンインスタンス取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初期化
     */
    public function __construct() {
        $this->check_acf_availability();
        $this->setup_field_mappings();
        $this->setup_hooks();
        $this->register_fallback_functions();
    }
    
    /**
     * ACFの利用可能性をチェック
     */
    private function check_acf_availability() {
        $this->acf_available = function_exists('get_field') && class_exists('ACF');
        
        if (!$this->acf_available) {
            // ACFが利用できない場合の警告ログ
            error_log('GI ACF Fallback: ACF is not available, using fallback system');
        }
    }
    
    /**
     * フィールドマッピングの設定
     */
    private function setup_field_mappings() {
        // 助成金関連フィールド
        $this->field_mappings['grant'] = array(
            'amount_min' => array(
                'type' => 'number',
                'default' => 0,
                'meta_key' => '_gi_amount_min'
            ),
            'amount_max' => array(
                'type' => 'number',
                'default' => 0,
                'meta_key' => '_gi_amount_max'
            ),
            'deadline' => array(
                'type' => 'date',
                'default' => '',
                'meta_key' => '_gi_deadline'
            ),
            'application_period_start' => array(
                'type' => 'date',
                'default' => '',
                'meta_key' => '_gi_application_period_start'
            ),
            'application_period_end' => array(
                'type' => 'date',
                'default' => '',
                'meta_key' => '_gi_application_period_end'
            ),
            'eligibility' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_eligibility'
            ),
            'required_documents' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_required_documents'
            ),
            'contact_info' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_contact_info'
            ),
            'website_url' => array(
                'type' => 'url',
                'default' => '',
                'meta_key' => '_gi_website_url'
            ),
            'application_url' => array(
                'type' => 'url',
                'default' => '',
                'meta_key' => '_gi_application_url'
            ),
            'difficulty_level' => array(
                'type' => 'select',
                'default' => 'medium',
                'meta_key' => '_gi_difficulty_level',
                'choices' => array('easy' => '易しい', 'medium' => '普通', 'hard' => '難しい')
            ),
            'success_rate' => array(
                'type' => 'number',
                'default' => 0,
                'meta_key' => '_gi_success_rate'
            )
        );
        
        // ツール関連フィールド
        $this->field_mappings['tool'] = array(
            'tool_type' => array(
                'type' => 'select',
                'default' => 'diagnostic',
                'meta_key' => '_gi_tool_type',
                'choices' => array('diagnostic' => '診断', 'calculator' => '計算', 'checker' => 'チェック')
            ),
            'difficulty' => array(
                'type' => 'select',
                'default' => 'beginner',
                'meta_key' => '_gi_difficulty',
                'choices' => array('beginner' => '初心者', 'intermediate' => '中級者', 'advanced' => '上級者')
            ),
            'estimated_time' => array(
                'type' => 'number',
                'default' => 5,
                'meta_key' => '_gi_estimated_time'
            ),
            'tool_url' => array(
                'type' => 'url',
                'default' => '',
                'meta_key' => '_gi_tool_url'
            )
        );
        
        // 事例関連フィールド
        $this->field_mappings['case_study'] = array(
            'company_name' => array(
                'type' => 'text',
                'default' => '',
                'meta_key' => '_gi_company_name'
            ),
            'industry' => array(
                'type' => 'text',
                'default' => '',
                'meta_key' => '_gi_industry'
            ),
            'grant_amount' => array(
                'type' => 'number',
                'default' => 0,
                'meta_key' => '_gi_grant_amount'
            ),
            'success_factors' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_success_factors'
            ),
            'challenges' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_challenges'
            ),
            'results' => array(
                'type' => 'textarea',
                'default' => '',
                'meta_key' => '_gi_results'
            )
        );
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // メタボックスの追加（ACFが利用できない場合）
        if (!$this->acf_available) {
            add_action('add_meta_boxes', array($this, 'add_fallback_meta_boxes'));
            add_action('save_post', array($this, 'save_fallback_meta_data'));
        }
        
        // 管理画面での警告表示
        add_action('admin_notices', array($this, 'show_acf_status_notice'));
    }
    
    /**
     * フォールバック関数の登録
     */
    private function register_fallback_functions() {
        // get_field関数のフォールバック
        if (!function_exists('get_field')) {
            function get_field($field_name, $post_id = null) {
                return GI_ACF_Fallback_System::getInstance()->get_field_fallback($field_name, $post_id);
            }
        }
        
        // update_field関数のフォールバック
        if (!function_exists('update_field')) {
            function update_field($field_name, $value, $post_id = null) {
                return GI_ACF_Fallback_System::getInstance()->update_field_fallback($field_name, $value, $post_id);
            }
        }
        
        // have_rows関数のフォールバック
        if (!function_exists('have_rows')) {
            function have_rows($field_name, $post_id = null) {
                return GI_ACF_Fallback_System::getInstance()->have_rows_fallback($field_name, $post_id);
            }
        }
        
        // the_row関数のフォールバック
        if (!function_exists('the_row')) {
            function the_row() {
                return GI_ACF_Fallback_System::getInstance()->the_row_fallback();
            }
        }
        
        // get_sub_field関数のフォールバック
        if (!function_exists('get_sub_field')) {
            function get_sub_field($field_name) {
                return GI_ACF_Fallback_System::getInstance()->get_sub_field_fallback($field_name);
            }
        }
    }
    
    /**
     * フィールド取得のフォールバック
     */
    public function get_field_fallback($field_name, $post_id = null) {
        if ($this->acf_available) {
            return get_field($field_name, $post_id);
        }
        
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $post_type = get_post_type($post_id);
        
        // フィールドマッピングから設定を取得
        if (isset($this->field_mappings[$post_type][$field_name])) {
            $field_config = $this->field_mappings[$post_type][$field_name];
            $meta_key = $field_config['meta_key'];
            $default = $field_config['default'];
            
            $value = get_post_meta($post_id, $meta_key, true);
            
            if ($value === '' || $value === false) {
                return $default;
            }
            
            // 型に応じた変換
            switch ($field_config['type']) {
                case 'number':
                    return is_numeric($value) ? (float)$value : $default;
                
                case 'date':
                    return $value ? date('Y-m-d', strtotime($value)) : $default;
                
                case 'url':
                    return filter_var($value, FILTER_VALIDATE_URL) ? $value : $default;
                
                default:
                    return $value;
            }
        }
        
        // 標準のメタフィールドとして取得を試行
        $value = get_post_meta($post_id, $field_name, true);
        return $value !== '' ? $value : '';
    }
    
    /**
     * フィールド更新のフォールバック
     */
    public function update_field_fallback($field_name, $value, $post_id = null) {
        if ($this->acf_available) {
            return update_field($field_name, $value, $post_id);
        }
        
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $post_type = get_post_type($post_id);
        
        // フィールドマッピングから設定を取得
        if (isset($this->field_mappings[$post_type][$field_name])) {
            $field_config = $this->field_mappings[$post_type][$field_name];
            $meta_key = $field_config['meta_key'];
            
            // データの検証とサニタイズ
            $sanitized_value = $this->sanitize_field_value($value, $field_config['type']);
            
            return update_post_meta($post_id, $meta_key, $sanitized_value);
        }
        
        // 標準のメタフィールドとして更新
        return update_post_meta($post_id, $field_name, sanitize_text_field($value));
    }
    
    /**
     * リピーターフィールドのフォールバック（簡易版）
     */
    public function have_rows_fallback($field_name, $post_id = null) {
        // 簡易的なリピーターフィールド対応
        // 実際のACFリピーターフィールドの完全な代替は複雑なため、基本的な配列データのみ対応
        $data = $this->get_field_fallback($field_name, $post_id);
        
        if (is_array($data) && !empty($data)) {
            if (!isset($this->repeater_data)) {
                $this->repeater_data = array();
            }
            
            $this->repeater_data[$field_name] = $data;
            $this->repeater_index[$field_name] = 0;
            
            return count($data) > 0;
        }
        
        return false;
    }
    
    /**
     * リピーター行の取得フォールバック
     */
    public function the_row_fallback() {
        // 簡易的な実装
        return true;
    }
    
    /**
     * サブフィールド取得のフォールバック
     */
    public function get_sub_field_fallback($field_name) {
        // 簡易的な実装
        return '';
    }
    
    /**
     * フィールド値のサニタイズ
     */
    private function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            
            case 'date':
                return sanitize_text_field($value);
            
            case 'url':
                return esc_url_raw($value);
            
            case 'textarea':
                return sanitize_textarea_field($value);
            
            case 'text':
            case 'select':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * フォールバックメタボックスの追加
     */
    public function add_fallback_meta_boxes() {
        $post_types = array('grant', 'tool', 'case_study');
        
        foreach ($post_types as $post_type) {
            if (isset($this->field_mappings[$post_type])) {
                add_meta_box(
                    'gi_' . $post_type . '_fields',
                    ucfirst($post_type) . ' フィールド',
                    array($this, 'render_fallback_meta_box'),
                    $post_type,
                    'normal',
                    'high',
                    array('post_type' => $post_type)
                );
            }
        }
    }
    
    /**
     * フォールバックメタボックスのレンダリング
     */
    public function render_fallback_meta_box($post, $metabox) {
        $post_type = $metabox['args']['post_type'];
        $fields = $this->field_mappings[$post_type];
        
        wp_nonce_field('gi_fallback_meta_box', 'gi_fallback_meta_box_nonce');
        
        echo '<table class="form-table">';
        
        foreach ($fields as $field_name => $field_config) {
            $value = $this->get_field_fallback($field_name, $post->ID);
            $label = ucwords(str_replace('_', ' ', $field_name));
            
            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field_name) . '">' . esc_html($label) . '</label></th>';
            echo '<td>';
            
            switch ($field_config['type']) {
                case 'textarea':
                    echo '<textarea id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" rows="4" cols="50">' . esc_textarea($value) . '</textarea>';
                    break;
                
                case 'select':
                    echo '<select id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '">';
                    foreach ($field_config['choices'] as $choice_value => $choice_label) {
                        $selected = selected($value, $choice_value, false);
                        echo '<option value="' . esc_attr($choice_value) . '"' . $selected . '>' . esc_html($choice_label) . '</option>';
                    }
                    echo '</select>';
                    break;
                
                case 'number':
                    echo '<input type="number" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" step="0.01" />';
                    break;
                
                case 'date':
                    echo '<input type="date" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" />';
                    break;
                
                case 'url':
                    echo '<input type="url" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                    break;
                
                case 'text':
                default:
                    echo '<input type="text" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                    break;
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * フォールバックメタデータの保存
     */
    public function save_fallback_meta_data($post_id) {
        // nonce検証
        if (!isset($_POST['gi_fallback_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['gi_fallback_meta_box_nonce'], 'gi_fallback_meta_box')) {
            return;
        }
        
        // 自動保存の場合はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        if (isset($this->field_mappings[$post_type])) {
            foreach ($this->field_mappings[$post_type] as $field_name => $field_config) {
                if (isset($_POST[$field_name])) {
                    $value = $_POST[$field_name];
                    $this->update_field_fallback($field_name, $value, $post_id);
                }
            }
        }
    }
    
    /**
     * ACF状態の通知表示
     */
    public function show_acf_status_notice() {
        if (!$this->acf_available && current_user_can('manage_options')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Grant Insight:</strong> Advanced Custom Fields プラグインが検出されませんでした。フォールバックシステムを使用しています。</p>';
            echo '<p>完全な機能を利用するには、ACF プラグインをインストール・有効化することをお勧めします。</p>';
            echo '</div>';
        }
    }
    
    /**
     * ACFの利用可能性を取得
     */
    public function is_acf_available() {
        return $this->acf_available;
    }
    
    /**
     * フィールドマッピングを取得
     */
    public function get_field_mappings($post_type = null) {
        if ($post_type) {
            return isset($this->field_mappings[$post_type]) ? $this->field_mappings[$post_type] : array();
        }
        
        return $this->field_mappings;
    }
}

// ACF フォールバックシステムの初期化
if (!function_exists('gi_init_acf_fallback')) {
    function gi_init_acf_fallback() {
        GI_ACF_Fallback_System::getInstance();
    }
    add_action('init', 'gi_init_acf_fallback', 1);
}

// ヘルパー関数
if (!function_exists('gi_is_acf_available')) {
    function gi_is_acf_available() {
        $fallback_system = GI_ACF_Fallback_System::getInstance();
        return $fallback_system->is_acf_available();
    }
}

if (!function_exists('gi_get_field_safe')) {
    function gi_get_field_safe($field_name, $post_id = null, $default = '') {
        $value = get_field($field_name, $post_id);
        return ($value !== false && $value !== '') ? $value : $default;
    }
}

