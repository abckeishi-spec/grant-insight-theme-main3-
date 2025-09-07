/**
 * Help System JavaScript
 * ヘルプモーダルとツールチップの機能を提供
 */
(function($) {
    'use strict';

    const HelpSystem = {
        // FAQ データ
        faqData: window.gi_help ? window.gi_help.faq_items : [],
        
        // 初期化
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        // イベントバインディング
        bindEvents: function() {
            // ヘルプボタンクリック
            $('#gi-help-trigger').on('click', this.toggleHelpModal.bind(this));
            
            // モーダル閉じるボタン
            $('.modal-close, .modal-overlay').on('click', this.closeHelpModal.bind(this));
            
            // カテゴリフィルター
            $('.help-category').on('click', this.filterFAQ.bind(this));
            
            // 検索機能
            $('.help-search-input').on('input', this.searchFAQ.bind(this));
            
            // ESCキーで閉じる
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    this.closeHelpModal();
                }
            }.bind(this));
        },
        
        // ツールチップ初期化
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $this = $(this);
                const content = $this.data('tooltip');
                
                $this.on('mouseenter', function() {
                    const tooltip = $('<div class="tooltip-popup">' + content + '</div>');
                    $('body').append(tooltip);
                    
                    const offset = $this.offset();
                    tooltip.css({
                        top: offset.top - tooltip.outerHeight() - 10,
                        left: offset.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2)
                    }).fadeIn(200);
                });
                
                $this.on('mouseleave', function() {
                    $('.tooltip-popup').fadeOut(200, function() {
                        $(this).remove();
                    });
                });
            });
        },
        
        // ヘルプモーダル表示/非表示
        toggleHelpModal: function(e) {
            e.preventDefault();
            const $modal = $('#gi-help-modal');
            
            if ($modal.is(':visible')) {
                this.closeHelpModal();
            } else {
                this.openHelpModal();
            }
        },
        
        // ヘルプモーダルを開く
        openHelpModal: function() {
            const $modal = $('#gi-help-modal');
            $modal.fadeIn(300);
            this.loadFAQContent('all');
            
            // フォーカストラップ
            $modal.find('.help-search-input').focus();
        },
        
        // ヘルプモーダルを閉じる
        closeHelpModal: function() {
            $('#gi-help-modal').fadeOut(300);
        },
        
        // FAQコンテンツ読み込み
        loadFAQContent: function(category) {
            const $content = $('.help-content');
            let filteredItems = this.faqData;
            
            if (category !== 'all') {
                filteredItems = this.faqData.filter(item => item.category === category);
            }
            
            if (filteredItems.length === 0) {
                $content.html('<p class="no-results">該当する質問が見つかりませんでした。</p>');
                return;
            }
            
            let html = '<div class="faq-list">';
            filteredItems.forEach(function(item) {
                html += `
                    <div class="faq-item" data-category="${item.category}">
                        <div class="faq-question">
                            <span class="faq-icon">Q</span>
                            <span>${item.question}</span>
                        </div>
                        <div class="faq-answer">
                            <span class="faq-icon">A</span>
                            <span>${item.answer}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $content.html(html);
            
            // アコーディオン機能
            $('.faq-question').on('click', function() {
                $(this).next('.faq-answer').slideToggle(300);
                $(this).parent().toggleClass('open');
            });
        },
        
        // FAQフィルター
        filterFAQ: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const category = $button.data('category');
            
            // アクティブ状態更新
            $('.help-category').removeClass('active');
            $button.addClass('active');
            
            // コンテンツ更新
            this.loadFAQContent(category);
        },
        
        // FAQ検索
        searchFAQ: function(e) {
            const query = $(e.target).val().toLowerCase();
            
            if (query.length < 2) {
                this.loadFAQContent('all');
                return;
            }
            
            const filteredItems = this.faqData.filter(item => {
                return item.question.toLowerCase().includes(query) ||
                       item.answer.toLowerCase().includes(query);
            });
            
            const $content = $('.help-content');
            
            if (filteredItems.length === 0) {
                $content.html('<p class="no-results">検索結果が見つかりませんでした。</p>');
                return;
            }
            
            let html = '<div class="faq-list">';
            filteredItems.forEach(function(item) {
                // 検索語をハイライト
                const highlightedQuestion = item.question.replace(
                    new RegExp(query, 'gi'),
                    '<mark>$&</mark>'
                );
                const highlightedAnswer = item.answer.replace(
                    new RegExp(query, 'gi'),
                    '<mark>$&</mark>'
                );
                
                html += `
                    <div class="faq-item" data-category="${item.category}">
                        <div class="faq-question">
                            <span class="faq-icon">Q</span>
                            <span>${highlightedQuestion}</span>
                        </div>
                        <div class="faq-answer">
                            <span class="faq-icon">A</span>
                            <span>${highlightedAnswer}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $content.html(html);
            
            // アコーディオン機能
            $('.faq-question').on('click', function() {
                $(this).next('.faq-answer').slideToggle(300);
                $(this).parent().toggleClass('open');
            });
        },
        
        // コンテキストヘルプ表示
        showContextHelp: function(context) {
            const helpText = this.getContextHelp(context);
            
            if (helpText) {
                const $popup = $('<div class="context-help-popup">' + helpText + '</div>');
                $('body').append($popup);
                
                $popup.fadeIn(300);
                
                setTimeout(function() {
                    $popup.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        // コンテキストヘルプ取得
        getContextHelp: function(context) {
            const contextHelps = {
                'grant-search': '補助金を検索するには、キーワードやカテゴリを入力してください。',
                'ai-diagnosis': 'AI診断では、いくつかの質問に答えることで最適な補助金を提案します。',
                'filter': 'フィルターを使用して、条件に合う補助金を絞り込むことができます。',
                'application': '申請ボタンをクリックすると、公式サイトへ移動します。'
            };
            
            return contextHelps[context] || null;
        }
    };
    
    // 初期化
    $(document).ready(function() {
        HelpSystem.init();
        
        // グローバルに公開
        window.GIHelpSystem = HelpSystem;
    });
    
})(jQuery);

// スタイル追加
const style = document.createElement('style');
style.textContent = `
    .tooltip-popup {
        position: absolute;
        background: #333;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 10000;
        display: none;
        max-width: 250px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .tooltip-popup::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #333;
    }
    
    .faq-list {
        margin-top: 20px;
    }
    
    .faq-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .faq-item.open {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .faq-question {
        padding: 15px;
        background: #f9fafb;
        cursor: pointer;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        transition: background 0.3s ease;
    }
    
    .faq-question:hover {
        background: #f3f4f6;
    }
    
    .faq-answer {
        padding: 15px;
        display: none;
        background: white;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    
    .faq-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background: #6366f1;
        color: white;
        border-radius: 50%;
        font-weight: bold;
        font-size: 12px;
        flex-shrink: 0;
    }
    
    .no-results {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    mark {
        background: #fef3c7;
        color: inherit;
        padding: 2px 4px;
        border-radius: 2px;
    }
    
    .context-help-popup {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #4f46e5;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        max-width: 300px;
        z-index: 9999;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
`;
document.head.appendChild(style);