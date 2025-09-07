<?php
/**
 * Search and Filter Stability Enhancement
 * 
 * Task 12: 検索・フィルター安定化
 * Implements improved search functionality with zero results handling,
 * filter persistence, and enhanced autocomplete
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 12.1 検索結果0件時改善
 * Handle zero search results with suggestions
 */
function gi_handle_zero_search_results($query_vars) {
    $suggestions = array(
        'similar_categories' => array(),
        'relaxed_conditions' => array(),
        'popular_grants' => array()
    );
    
    // Get similar categories based on search term
    if (!empty($query_vars['s'])) {
        $suggestions['similar_categories'] = gi_get_similar_categories($query_vars['s']);
    }
    
    // Suggest relaxed search conditions
    $suggestions['relaxed_conditions'] = gi_suggest_relaxed_conditions($query_vars);
    
    // Get popular grants
    $suggestions['popular_grants'] = gi_get_popular_grants(5);
    
    return $suggestions;
}

/**
 * Get similar categories based on search term
 */
function gi_get_similar_categories($search_term) {
    $all_categories = get_terms(array(
        'taxonomy' => 'grant_category',
        'hide_empty' => false
    ));
    
    $similar = array();
    $search_lower = mb_strtolower($search_term);
    
    foreach ($all_categories as $category) {
        $name_lower = mb_strtolower($category->name);
        $description_lower = mb_strtolower($category->description);
        
        // Calculate similarity score
        $name_similarity = 0;
        similar_text($search_lower, $name_lower, $name_similarity);
        
        // Check if search term is contained in category
        if (strpos($name_lower, $search_lower) !== false || 
            strpos($description_lower, $search_lower) !== false ||
            $name_similarity > 50) {
            
            $similar[] = array(
                'term_id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'link' => get_term_link($category),
                'similarity' => $name_similarity
            );
        }
    }
    
    // Sort by similarity score
    usort($similar, function($a, $b) {
        return $b['similarity'] - $a['similarity'];
    });
    
    return array_slice($similar, 0, 5);
}

/**
 * Suggest relaxed search conditions
 */
function gi_suggest_relaxed_conditions($query_vars) {
    $suggestions = array();
    
    // If multiple filters are applied, suggest removing some
    if (!empty($query_vars['tax_query']) && count($query_vars['tax_query']) > 1) {
        $suggestions[] = array(
            'type' => 'remove_filter',
            'message' => 'フィルターを減らして検索範囲を広げる',
            'action' => 'reduce_filters'
        );
    }
    
    // If date range is narrow, suggest expanding
    if (!empty($query_vars['meta_query'])) {
        foreach ($query_vars['meta_query'] as $meta) {
            if (isset($meta['key']) && $meta['key'] === 'application_deadline') {
                $suggestions[] = array(
                    'type' => 'expand_date',
                    'message' => '期限の範囲を広げて検索',
                    'action' => 'expand_date_range'
                );
                break;
            }
        }
    }
    
    // If search term is long, suggest shorter terms
    if (!empty($query_vars['s']) && mb_strlen($query_vars['s']) > 10) {
        $suggestions[] = array(
            'type' => 'shorten_search',
            'message' => 'より短いキーワードで検索',
            'action' => 'shorten_keyword'
        );
    }
    
    // Suggest searching all grants
    $suggestions[] = array(
        'type' => 'search_all',
        'message' => 'すべての補助金から検索',
        'action' => 'clear_filters',
        'link' => get_post_type_archive_link('grant')
    );
    
    return $suggestions;
}

/**
 * Get popular grants
 */
function gi_get_popular_grants($limit = 5) {
    $cache_key = 'gi_popular_grants_' . $limit;
    $popular_grants = get_transient($cache_key);
    
    if (false === $popular_grants) {
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => 'view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );
        
        $query = new WP_Query($args);
        $popular_grants = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $popular_grants[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'link' => get_permalink(),
                    'views' => get_post_meta(get_the_ID(), 'view_count', true),
                    'max_amount' => get_field('max_amount'),
                    'deadline' => get_field('application_deadline')
                );
            }
            wp_reset_postdata();
        }
        
        set_transient($cache_key, $popular_grants, HOUR_IN_SECONDS);
    }
    
    return $popular_grants;
}

/**
 * 12.2 フィルター条件保存
 * Save and restore filter conditions
 */
function gi_save_filter_conditions() {
    ?>
    <script>
    (function() {
        // Filter storage object
        const FilterStorage = {
            storageKey: 'gi_search_filters',
            historyKey: 'gi_search_history',
            maxHistory: 10,
            
            // Save current filters to localStorage
            saveFilters: function(filters) {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(filters));
                    this.addToHistory(filters);
                } catch (e) {
                    console.error('Failed to save filters:', e);
                }
            },
            
            // Load saved filters
            loadFilters: function() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    return saved ? JSON.parse(saved) : null;
                } catch (e) {
                    console.error('Failed to load filters:', e);
                    return null;
                }
            },
            
            // Add to search history
            addToHistory: function(filters) {
                try {
                    let history = this.getHistory();
                    const timestamp = new Date().toISOString();
                    
                    // Create history entry
                    const entry = {
                        filters: filters,
                        timestamp: timestamp,
                        id: Date.now()
                    };
                    
                    // Add to beginning of array
                    history.unshift(entry);
                    
                    // Limit to max history
                    if (history.length > this.maxHistory) {
                        history = history.slice(0, this.maxHistory);
                    }
                    
                    localStorage.setItem(this.historyKey, JSON.stringify(history));
                } catch (e) {
                    console.error('Failed to save history:', e);
                }
            },
            
            // Get search history
            getHistory: function() {
                try {
                    const saved = localStorage.getItem(this.historyKey);
                    return saved ? JSON.parse(saved) : [];
                } catch (e) {
                    console.error('Failed to load history:', e);
                    return [];
                }
            },
            
            // Clear all saved data
            clear: function() {
                localStorage.removeItem(this.storageKey);
                localStorage.removeItem(this.historyKey);
            }
        };
        
        // Expose to global scope
        window.GIFilterStorage = FilterStorage;
        
        // Auto-save filters on change
        document.addEventListener('DOMContentLoaded', function() {
            // Watch for filter changes
            const filterForm = document.querySelector('.grant-search-form');
            if (filterForm) {
                filterForm.addEventListener('change', function(e) {
                    const formData = new FormData(filterForm);
                    const filters = {};
                    
                    for (let [key, value] of formData.entries()) {
                        if (value) {
                            filters[key] = value;
                        }
                    }
                    
                    FilterStorage.saveFilters(filters);
                });
                
                // Restore filters on page load
                const savedFilters = FilterStorage.loadFilters();
                if (savedFilters) {
                    Object.keys(savedFilters).forEach(key => {
                        const input = filterForm.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.value = savedFilters[key];
                            // Trigger change event for dependent elements
                            input.dispatchEvent(new Event('change'));
                        }
                    });
                }
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'gi_save_filter_conditions');

/**
 * Save user preferences for logged-in users
 */
function gi_save_user_search_preferences($user_id, $filters) {
    if (!$user_id) {
        return false;
    }
    
    // Save as user meta
    update_user_meta($user_id, 'gi_search_preferences', $filters);
    
    // Save to search history
    $history = get_user_meta($user_id, 'gi_search_history', true);
    if (!is_array($history)) {
        $history = array();
    }
    
    // Add new entry
    array_unshift($history, array(
        'filters' => $filters,
        'timestamp' => current_time('mysql'),
    ));
    
    // Limit to 10 entries
    $history = array_slice($history, 0, 10);
    
    update_user_meta($user_id, 'gi_search_history', $history);
    
    return true;
}

/**
 * AJAX handler for saving user preferences
 */
function gi_ajax_save_search_preferences() {
    // Verify nonce
    if (!check_ajax_referer('gi_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました。'));
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'ログインが必要です。'));
        return;
    }
    
    $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
    
    // Sanitize filters
    $sanitized_filters = array();
    foreach ($filters as $key => $value) {
        $sanitized_filters[sanitize_key($key)] = sanitize_text_field($value);
    }
    
    $result = gi_save_user_search_preferences($user_id, $sanitized_filters);
    
    if ($result) {
        wp_send_json_success(array('message' => '検索条件を保存しました。'));
    } else {
        wp_send_json_error(array('message' => '保存に失敗しました。'));
    }
}
add_action('wp_ajax_gi_save_search_preferences', 'gi_ajax_save_search_preferences');

/**
 * 12.3 オートコンプリート強化
 * Enhanced autocomplete functionality
 */
function gi_enhanced_autocomplete() {
    ?>
    <script>
    (function() {
        const AutocompleteEnhanced = {
            minChars: 2,
            maxSuggestions: 10,
            debounceDelay: 300,
            cache: {},
            
            init: function() {
                const searchInputs = document.querySelectorAll('.grant-search-input');
                searchInputs.forEach(input => {
                    this.setupAutocomplete(input);
                });
            },
            
            setupAutocomplete: function(input) {
                let debounceTimer;
                const container = this.createSuggestionsContainer(input);
                
                input.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    const query = e.target.value.trim();
                    
                    if (query.length < this.minChars) {
                        this.hideSuggestions(container);
                        return;
                    }
                    
                    debounceTimer = setTimeout(() => {
                        this.fetchSuggestions(query, container, input);
                    }, this.debounceDelay);
                });
                
                // Handle keyboard navigation
                input.addEventListener('keydown', (e) => {
                    this.handleKeyboardNavigation(e, container, input);
                });
                
                // Hide on click outside
                document.addEventListener('click', (e) => {
                    if (!input.contains(e.target) && !container.contains(e.target)) {
                        this.hideSuggestions(container);
                    }
                });
            },
            
            createSuggestionsContainer: function(input) {
                const container = document.createElement('div');
                container.className = 'autocomplete-suggestions';
                container.style.cssText = `
                    position: absolute;
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.5rem;
                    max-height: 300px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                `;
                
                // Position container below input
                const inputRect = input.getBoundingClientRect();
                container.style.top = (inputRect.bottom + window.scrollY) + 'px';
                container.style.left = inputRect.left + 'px';
                container.style.width = inputRect.width + 'px';
                
                document.body.appendChild(container);
                return container;
            },
            
            fetchSuggestions: async function(query, container, input) {
                // Check cache first
                if (this.cache[query]) {
                    this.displaySuggestions(this.cache[query], container, input, query);
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'gi_enhanced_autocomplete');
                    formData.append('nonce', '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>');
                    formData.append('query', query);
                    
                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.cache[query] = data.data;
                        this.displaySuggestions(data.data, container, input, query);
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
            },
            
            displaySuggestions: function(suggestions, container, input, query) {
                container.innerHTML = '';
                
                if (!suggestions || suggestions.length === 0) {
                    this.hideSuggestions(container);
                    return;
                }
                
                suggestions.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.dataset.index = index;
                    div.style.cssText = `
                        padding: 0.75rem 1rem;
                        cursor: pointer;
                        transition: background-color 0.2s;
                    `;
                    
                    // Highlight matching text
                    const highlightedText = this.highlightMatch(item.text, query);
                    
                    div.innerHTML = `
                        <div class="suggestion-main">
                            <span class="suggestion-type" style="
                                display: inline-block;
                                padding: 0.125rem 0.5rem;
                                background: ${item.type === 'category' ? '#dbeafe' : '#fef3c7'};
                                color: ${item.type === 'category' ? '#1e40af' : '#92400e'};
                                font-size: 0.75rem;
                                border-radius: 0.25rem;
                                margin-right: 0.5rem;
                            ">${item.type === 'category' ? 'カテゴリ' : '都道府県'}</span>
                            <span class="suggestion-text">${highlightedText}</span>
                        </div>
                        ${item.count ? `<div class="suggestion-count" style="
                            font-size: 0.875rem;
                            color: #6b7280;
                            margin-top: 0.25rem;
                        ">${item.count}件の補助金</div>` : ''}
                    `;
                    
                    // Hover effect
                    div.addEventListener('mouseenter', () => {
                        div.style.backgroundColor = '#f3f4f6';
                    });
                    
                    div.addEventListener('mouseleave', () => {
                        div.style.backgroundColor = '';
                    });
                    
                    // Click handler
                    div.addEventListener('click', () => {
                        input.value = item.text;
                        this.hideSuggestions(container);
                        
                        // Trigger search
                        input.dispatchEvent(new Event('change'));
                        input.form?.dispatchEvent(new Event('submit'));
                    });
                    
                    container.appendChild(div);
                });
                
                container.style.display = 'block';
            },
            
            highlightMatch: function(text, query) {
                const regex = new RegExp(`(${query})`, 'gi');
                return text.replace(regex, '<strong style="color: #6366f1;">$1</strong>');
            },
            
            hideSuggestions: function(container) {
                container.style.display = 'none';
                container.innerHTML = '';
            },
            
            handleKeyboardNavigation: function(e, container, input) {
                const items = container.querySelectorAll('.suggestion-item');
                if (items.length === 0) return;
                
                let currentIndex = -1;
                items.forEach((item, index) => {
                    if (item.classList.contains('active')) {
                        currentIndex = index;
                    }
                });
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        currentIndex = (currentIndex + 1) % items.length;
                        this.setActiveItem(items, currentIndex);
                        break;
                        
                    case 'ArrowUp':
                        e.preventDefault();
                        currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                        this.setActiveItem(items, currentIndex);
                        break;
                        
                    case 'Enter':
                        if (currentIndex >= 0) {
                            e.preventDefault();
                            items[currentIndex].click();
                        }
                        break;
                        
                    case 'Escape':
                        this.hideSuggestions(container);
                        break;
                }
            },
            
            setActiveItem: function(items, index) {
                items.forEach((item, i) => {
                    if (i === index) {
                        item.classList.add('active');
                        item.style.backgroundColor = '#f3f4f6';
                    } else {
                        item.classList.remove('active');
                        item.style.backgroundColor = '';
                    }
                });
            }
        };
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            AutocompleteEnhanced.init();
        });
        
        // Expose to global scope
        window.GIAutocompleteEnhanced = AutocompleteEnhanced;
    })();
    </script>
    <?php
}
add_action('wp_footer', 'gi_enhanced_autocomplete');

/**
 * AJAX handler for enhanced autocomplete
 */
function gi_ajax_enhanced_autocomplete() {
    // Verify nonce
    if (!check_ajax_referer('gi_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました。'));
        return;
    }
    
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (empty($query) || mb_strlen($query) < 2) {
        wp_send_json_success(array());
        return;
    }
    
    $suggestions = array();
    
    // Search categories
    $categories = get_terms(array(
        'taxonomy' => 'grant_category',
        'hide_empty' => false,
        'search' => $query,
        'number' => 5
    ));
    
    foreach ($categories as $category) {
        $suggestions[] = array(
            'type' => 'category',
            'text' => $category->name,
            'value' => $category->slug,
            'count' => $category->count
        );
    }
    
    // Search prefectures
    $prefectures = array(
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );
    
    $query_lower = mb_strtolower($query);
    foreach ($prefectures as $prefecture) {
        if (mb_strpos(mb_strtolower($prefecture), $query_lower) !== false) {
            // Count grants for this prefecture
            $count = gi_get_prefecture_count($prefecture);
            
            $suggestions[] = array(
                'type' => 'prefecture',
                'text' => $prefecture,
                'value' => $prefecture,
                'count' => $count
            );
        }
    }
    
    // Sort by relevance (exact matches first)
    usort($suggestions, function($a, $b) use ($query_lower) {
        $a_lower = mb_strtolower($a['text']);
        $b_lower = mb_strtolower($b['text']);
        
        // Exact match priority
        if ($a_lower === $query_lower) return -1;
        if ($b_lower === $query_lower) return 1;
        
        // Starting with query priority
        $a_starts = mb_strpos($a_lower, $query_lower) === 0;
        $b_starts = mb_strpos($b_lower, $query_lower) === 0;
        
        if ($a_starts && !$b_starts) return -1;
        if (!$a_starts && $b_starts) return 1;
        
        // Otherwise by count
        return $b['count'] - $a['count'];
    });
    
    // Limit results
    $suggestions = array_slice($suggestions, 0, 10);
    
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_gi_enhanced_autocomplete', 'gi_ajax_enhanced_autocomplete');
add_action('wp_ajax_nopriv_gi_enhanced_autocomplete', 'gi_ajax_enhanced_autocomplete');

/**
 * Display zero results message with suggestions
 */
function gi_display_zero_results_suggestions($search_query = '') {
    $suggestions = gi_handle_zero_search_results(array('s' => $search_query));
    ?>
    <div class="zero-results-container">
        <div class="zero-results-message">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <h3 class="text-xl font-semibold mb-2">検索結果が見つかりませんでした</h3>
            <p class="text-gray-600 mb-6">「<?php echo esc_html($search_query); ?>」に一致する補助金が見つかりません。</p>
        </div>
        
        <?php if (!empty($suggestions['similar_categories'])): ?>
        <div class="suggestion-section mb-6">
            <h4 class="font-semibold mb-3">関連するカテゴリ</h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($suggestions['similar_categories'] as $category): ?>
                <a href="<?php echo esc_url($category['link']); ?>" 
                   class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition">
                    <?php echo esc_html($category['name']); ?>
                    <span class="text-sm opacity-75">(<?php echo $category['count']; ?>件)</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($suggestions['relaxed_conditions'])): ?>
        <div class="suggestion-section mb-6">
            <h4 class="font-semibold mb-3">検索条件の見直し</h4>
            <ul class="space-y-2">
                <?php foreach ($suggestions['relaxed_conditions'] as $condition): ?>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo esc_html($condition['message']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($suggestions['popular_grants'])): ?>
        <div class="suggestion-section">
            <h4 class="font-semibold mb-3">人気の補助金</h4>
            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($suggestions['popular_grants'] as $grant): ?>
                <div class="border rounded-lg p-4 hover:shadow-lg transition">
                    <h5 class="font-medium mb-2">
                        <a href="<?php echo esc_url($grant['link']); ?>" class="text-blue-600 hover:text-blue-800">
                            <?php echo esc_html($grant['title']); ?>
                        </a>
                    </h5>
                    <p class="text-sm text-gray-600 mb-2"><?php echo esc_html(wp_trim_words($grant['excerpt'], 20)); ?></p>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>最大<?php echo esc_html($grant['max_amount']); ?></span>
                        <span><?php echo esc_html($grant['views']); ?>回閲覧</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
        .zero-results-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .zero-results-message {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .suggestion-section {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
    </style>
    <?php
}

/**
 * Add search history widget for logged-in users
 */
function gi_search_history_widget() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, 'gi_search_history', true);
    
    if (empty($history)) {
        return;
    }
    ?>
    <div class="search-history-widget">
        <h4 class="widget-title">検索履歴</h4>
        <ul class="history-list">
            <?php foreach (array_slice($history, 0, 5) as $entry): ?>
            <li class="history-item">
                <a href="#" class="history-link" data-filters='<?php echo esc_attr(json_encode($entry['filters'])); ?>'>
                    <?php 
                    $display_parts = array();
                    if (!empty($entry['filters']['s'])) {
                        $display_parts[] = $entry['filters']['s'];
                    }
                    if (!empty($entry['filters']['category'])) {
                        $display_parts[] = $entry['filters']['category'];
                    }
                    echo esc_html(implode(' / ', $display_parts) ?: 'すべて');
                    ?>
                </a>
                <span class="history-date"><?php echo esc_html(human_time_diff(strtotime($entry['timestamp']), current_time('timestamp')) . '前'); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <script>
    document.querySelectorAll('.history-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const filters = JSON.parse(this.dataset.filters);
            
            // Apply filters to search form
            Object.keys(filters).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = filters[key];
                }
            });
            
            // Trigger search
            document.querySelector('.grant-search-form')?.dispatchEvent(new Event('submit'));
        });
    });
    </script>
    
    <style>
        .search-history-widget {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .history-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .history-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-link {
            color: #4b5563;
            text-decoration: none;
            flex: 1;
        }
        
        .history-link:hover {
            color: #6366f1;
        }
        
        .history-date {
            font-size: 0.75rem;
            color: #9ca3af;
        }
    </style>
    <?php
}
add_action('gi_search_sidebar', 'gi_search_history_widget');