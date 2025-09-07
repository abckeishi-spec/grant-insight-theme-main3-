/**
 * Grant Insight Enhanced Search - 検索機能強化版
 * フォールバック機能付きAJAX検索システム
 * 
 * @version 1.0
 * @author Grant Insight Team
 */

(function($) {
    'use strict';

    // 設定オブジェクト
    const GIEnhanced = {
        config: {
            ajaxUrl: '',
            nonce: '',
            searchNonce: '',
            fallback: true,
            debug: false
        },
        
        // 初期化
        init: function() {
            this.setupConfig();
            this.bindEvents();
            this.setupErrorHandling();
            
            if (this.config.debug) {
                console.log('GI Enhanced Search initialized');
            }
        },
        
        // 設定の初期化
        setupConfig: function() {
            if (typeof gi_ajax !== 'undefined') {
                this.config.ajaxUrl = gi_ajax.ajax_url || '';
                this.config.nonce = gi_ajax.nonce || '';
                this.config.searchNonce = gi_ajax.search_nonce || '';
            }
            
            // フォールバック設定
            if (!this.config.ajaxUrl) {
                this.config.ajaxUrl = '/wp-admin/admin-ajax.php';
            }
        },
        
        // イベントバインディング
        bindEvents: function() {
            const self = this;
            
            // 検索フォームの処理
            $(document).on('submit', '.search-form, #searchform', function(e) {
                const $form = $(this);
                const query = $form.find('input[type="search"], input[name="s"]').val();
                
                if (!query || query.trim() === '') {
                    return true; // 空の場合は通常の検索を許可
                }
                
                // AJAX検索が利用可能かチェック
                if (self.isAjaxAvailable()) {
                    e.preventDefault();
                    self.performAjaxSearch(query);
                } else {
                    // フォールバック: 通常のWordPress検索
                    if (self.config.debug) {
                        console.log('Falling back to standard WordPress search');
                    }
                    return true;
                }
            });
            
            // 高度検索フォームの処理
            $(document).on('submit', '#advanced-search-form', function(e) {
                e.preventDefault();
                self.performAdvancedSearch();
            });
            
            // フィルターの変更
            $(document).on('change', '.filter-select, .filter-checkbox', function() {
                if (self.isAjaxAvailable()) {
                    self.performAdvancedSearch();
                }
            });
            
            // ページネーション
            $(document).on('click', '.pagination a', function(e) {
                if (self.isAjaxAvailable()) {
                    e.preventDefault();
                    const page = self.getPageFromUrl($(this).attr('href'));
                    self.loadPage(page);
                }
            });
        },
        
        // AJAX利用可能性チェック
        isAjaxAvailable: function() {
            return !!(this.config.ajaxUrl && (this.config.nonce || this.config.searchNonce));
        },
        
        // AJAX検索実行
        performAjaxSearch: function(query) {
            const self = this;
            
            this.showLoading();
            
            const data = {
                action: 'gi_load_grants',
                search: query,
                nonce: this.config.nonce || this.config.searchNonce,
                page: 1,
                per_page: 12
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 10000, // 10秒タイムアウト
                success: function(response) {
                    if (response.success) {
                        self.displayResults(response.data);
                        self.updateUrl(query);
                    } else {
                        self.handleAjaxError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError({
                        message: 'ネットワークエラーが発生しました',
                        error: error,
                        status: status
                    });
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // 高度検索実行
        performAdvancedSearch: function() {
            const self = this;
            const $form = $('#advanced-search-form');
            
            if ($form.length === 0) {
                return;
            }
            
            this.showLoading();
            
            const formData = this.getFormData($form);
            formData.action = 'gi_advanced_search';
            formData.nonce = this.config.nonce || this.config.searchNonce;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 15000, // 15秒タイムアウト
                success: function(response) {
                    if (response.success) {
                        self.displayResults(response.data);
                        self.updateAdvancedUrl(formData);
                    } else {
                        self.handleAjaxError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError({
                        message: '検索処理でエラーが発生しました',
                        error: error,
                        status: status
                    });
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // フォームデータ取得
        getFormData: function($form) {
            const data = {};
            
            // テキスト入力
            data.search = $form.find('input[name="search"]').val() || '';
            data.amount_min = parseInt($form.find('input[name="amount_min"]').val()) || 0;
            data.amount_max = parseInt($form.find('input[name="amount_max"]').val()) || 0;
            data.deadline_from = $form.find('input[name="deadline_from"]').val() || '';
            data.deadline_to = $form.find('input[name="deadline_to"]').val() || '';
            
            // カテゴリー選択
            const categories = [];
            $form.find('input[name="categories[]"]:checked').each(function() {
                categories.push($(this).val());
            });
            data.categories = JSON.stringify(categories);
            
            // 都道府県選択
            const prefectures = [];
            $form.find('input[name="prefectures[]"]:checked').each(function() {
                prefectures.push($(this).val());
            });
            data.prefectures = JSON.stringify(prefectures);
            
            // ページング
            data.page = parseInt($form.find('input[name="page"]').val()) || 1;
            data.per_page = parseInt($form.find('select[name="per_page"]').val()) || 12;
            
            return data;
        },
        
        // 結果表示
        displayResults: function(data) {
            const $container = $('#search-results, .grants-container');
            
            if ($container.length === 0) {
                console.error('Results container not found');
                return;
            }
            
            // 結果のHTML生成
            let html = '';
            
            if (data.grants && data.grants.length > 0) {
                html += '<div class="grants-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
                
                data.grants.forEach(function(grant) {
                    html += this.generateGrantCard(grant);
                }.bind(this));
                
                html += '</div>';
                
                // ページネーション
                if (data.pages > 1) {
                    html += this.generatePagination(data.current_page, data.pages);
                }
                
                // 結果統計
                html = this.generateResultsHeader(data) + html;
                
            } else {
                html = this.generateNoResults();
            }
            
            $container.html(html);
            
            // スクロール
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 500);
        },
        
        // 助成金カード生成
        generateGrantCard: function(grant) {
            const thumbnail = grant.thumbnail || '/wp-content/themes/grant-insight/images/default-grant.jpg';
            const excerpt = grant.excerpt || '詳細情報をご確認ください。';
            const categories = grant.categories || [];
            const prefectures = grant.prefectures || [];
            
            let html = `
                <div class="grant-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="grant-thumbnail">
                        <img src="${this.escapeHtml(thumbnail)}" alt="${this.escapeHtml(grant.title)}" 
                             class="w-full h-48 object-cover rounded-t-lg">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-3">
                            <a href="${this.escapeHtml(grant.permalink)}" class="text-gray-900 hover:text-emerald-600 transition-colors">
                                ${this.escapeHtml(grant.title)}
                            </a>
                        </h3>
                        <p class="text-gray-600 mb-4">${this.escapeHtml(excerpt)}</p>
                        
                        <div class="grant-meta space-y-2">
            `;
            
            // 助成金額
            if (grant.meta && grant.meta.amount_max) {
                html += `
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-yen-sign mr-2"></i>
                        最大 ${this.formatAmount(grant.meta.amount_max)}
                    </div>
                `;
            }
            
            // 締切日
            if (grant.meta && grant.meta.deadline) {
                html += `
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-calendar mr-2"></i>
                        締切: ${this.formatDate(grant.meta.deadline)}
                    </div>
                `;
            }
            
            html += `
                        </div>
                        
                        <div class="mt-4 flex flex-wrap gap-2">
            `;
            
            // カテゴリータグ
            categories.slice(0, 2).forEach(function(category) {
                html += `<span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-xs rounded">${this.escapeHtml(category.name)}</span>`;
            }.bind(this));
            
            // 都道府県タグ
            prefectures.slice(0, 1).forEach(function(prefecture) {
                html += `<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${this.escapeHtml(prefecture.name)}</span>`;
            }.bind(this));
            
            html += `
                        </div>
                        
                        <div class="mt-4">
                            <a href="${this.escapeHtml(grant.permalink)}" 
                               class="inline-block bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700 transition-colors">
                                詳細を見る
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            return html;
        },
        
        // 結果ヘッダー生成
        generateResultsHeader: function(data) {
            return `
                <div class="results-header mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold">検索結果</h2>
                            <p class="text-gray-600">${data.total}件の助成金が見つかりました</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            ${data.current_page} / ${data.pages} ページ
                        </div>
                    </div>
                </div>
            `;
        },
        
        // 結果なし表示
        generateNoResults: function() {
            return `
                <div class="no-results text-center py-12">
                    <div class="mb-4">
                        <i class="fas fa-search text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">検索結果が見つかりませんでした</h3>
                    <p class="text-gray-500 mb-6">検索条件を変更して再度お試しください。</p>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p>• キーワードを変更してみてください</p>
                        <p>• フィルター条件を緩和してみてください</p>
                        <p>• 全ての助成金を<a href="/grants/" class="text-emerald-600 hover:underline">こちら</a>から確認できます</p>
                    </div>
                </div>
            `;
        },
        
        // ページネーション生成
        generatePagination: function(currentPage, totalPages) {
            if (totalPages <= 1) return '';
            
            let html = '<div class="pagination flex justify-center mt-8"><div class="flex space-x-2">';
            
            // 前のページ
            if (currentPage > 1) {
                html += `<a href="#" data-page="${currentPage - 1}" class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50">前へ</a>`;
            }
            
            // ページ番号
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === currentPage ? 'bg-emerald-600 text-white' : 'bg-white border-gray-300 hover:bg-gray-50';
                html += `<a href="#" data-page="${i}" class="px-3 py-2 border rounded ${activeClass}">${i}</a>`;
            }
            
            // 次のページ
            if (currentPage < totalPages) {
                html += `<a href="#" data-page="${currentPage + 1}" class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50">次へ</a>`;
            }
            
            html += '</div></div>';
            return html;
        },
        
        // ローディング表示
        showLoading: function() {
            const $container = $('#search-results, .grants-container');
            $container.html(`
                <div class="loading text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600"></div>
                    <p class="mt-4 text-gray-600">検索中...</p>
                </div>
            `);
        },
        
        // ローディング非表示
        hideLoading: function() {
            // showLoadingで表示されるため、特別な処理は不要
        },
        
        // エラーハンドリング
        handleAjaxError: function(errorData) {
            console.error('AJAX Error:', errorData);
            
            const message = errorData.message || 'エラーが発生しました。ページを再読み込みしてお試しください。';
            
            const $container = $('#search-results, .grants-container');
            $container.html(`
                <div class="error-message bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-red-800 mb-2">エラーが発生しました</h3>
                    <p class="text-red-600 mb-4">${this.escapeHtml(message)}</p>
                    <div class="space-x-4">
                        <button onclick="location.reload()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            ページを再読み込み
                        </button>
                        <a href="/grants/" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            助成金一覧へ
                        </a>
                    </div>
                </div>
            `);
            
            // フォールバック: 通常の検索ページにリダイレクト
            if (this.config.fallback) {
                setTimeout(function() {
                    const searchQuery = $('input[name="search"], input[name="s"]').val();
                    if (searchQuery) {
                        window.location.href = '/search/?s=' + encodeURIComponent(searchQuery);
                    }
                }, 3000);
            }
        },
        
        // エラーハンドリング設定
        setupErrorHandling: function() {
            const self = this;
            
            // グローバルエラーハンドラー
            window.addEventListener('error', function(e) {
                if (self.config.debug) {
                    console.error('Global error:', e.error);
                }
            });
            
            // Promise rejection ハンドラー
            window.addEventListener('unhandledrejection', function(e) {
                if (self.config.debug) {
                    console.error('Unhandled promise rejection:', e.reason);
                }
            });
        },
        
        // ユーティリティ関数
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        formatAmount: function(amount) {
            if (!amount) return '';
            return parseInt(amount).toLocaleString() + '円';
        },
        
        formatDate: function(date) {
            if (!date) return '';
            const d = new Date(date);
            return d.getFullYear() + '年' + (d.getMonth() + 1) + '月' + d.getDate() + '日';
        },
        
        getPageFromUrl: function(url) {
            const match = url.match(/[?&]page=(\d+)/);
            return match ? parseInt(match[1]) : 1;
        },
        
        updateUrl: function(query) {
            if (history.pushState) {
                const newUrl = window.location.pathname + '?s=' + encodeURIComponent(query);
                history.pushState({search: query}, '', newUrl);
            }
        },
        
        updateAdvancedUrl: function(data) {
            if (history.pushState) {
                const params = new URLSearchParams();
                Object.keys(data).forEach(key => {
                    if (data[key] && key !== 'action' && key !== 'nonce') {
                        params.append(key, data[key]);
                    }
                });
                const newUrl = window.location.pathname + '?' + params.toString();
                history.pushState({advancedSearch: data}, '', newUrl);
            }
        },
        
        loadPage: function(page) {
            const $form = $('#advanced-search-form');
            if ($form.length > 0) {
                $form.find('input[name="page"]').val(page);
                this.performAdvancedSearch();
            } else {
                // 簡単検索の場合
                const query = $('input[name="search"], input[name="s"]').val();
                const data = {
                    action: 'gi_load_grants',
                    search: query,
                    page: page,
                    nonce: this.config.nonce || this.config.searchNonce
                };
                
                this.showLoading();
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            this.displayResults(response.data);
                        }
                    }.bind(this),
                    error: function() {
                        this.handleAjaxError({message: 'ページ読み込みエラー'});
                    }.bind(this),
                    complete: function() {
                        this.hideLoading();
                    }.bind(this)
                });
            }
        }
    };
    
    // 初期化
    $(document).ready(function() {
        GIEnhanced.init();
    });
    
    // グローバルに公開
    window.GIEnhanced = GIEnhanced;
    
})(jQuery);

