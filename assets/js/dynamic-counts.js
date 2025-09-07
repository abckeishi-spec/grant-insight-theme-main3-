/**
 * 動的件数更新機能 - フロントエンドJavaScript
 */
(function($) {
    'use strict';

    const DynamicCounts = {
        // 初期化
        init: function() {
            this.updateAllCounts();
            this.bindEvents();
            
            // 定期更新（5分ごと）
            setInterval(this.updateAllCounts.bind(this), 300000);
        },

        // イベントバインディング
        bindEvents: function() {
            // カテゴリー変更時に件数更新
            $(document).on('change', '.grant-category-filter', this.updateCategoryCounts.bind(this));
            
            // 都道府県変更時に件数更新
            $(document).on('change', '.grant-prefecture-filter', this.updatePrefectureCounts.bind(this));
        },

        // 全件数更新
        updateAllCounts: function() {
            // カテゴリー件数更新
            this.updateCategoryCounts();
            
            // 都道府県件数更新
            this.updatePrefectureCounts();
            
            // 総件数更新
            this.updateTotalCount();
        },

        // カテゴリー件数更新
        updateCategoryCounts: function() {
            const $elements = $('[data-category-count]');
            if ($elements.length === 0) return;
            
            const slugs = [];
            $elements.each(function() {
                const slug = $(this).data('category-count');
                if (slug && slugs.indexOf(slug) === -1) {
                    slugs.push(slug);
                }
            });
            
            if (slugs.length === 0) return;
            
            $.ajax({
                url: window.gi_counts.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_grant_counts',
                    nonce: window.gi_counts.nonce,
                    type: 'category',
                    slugs: slugs
                },
                success: function(response) {
                    if (response.success) {
                        this.displayCounts($elements, response.data);
                    }
                }.bind(this)
            });
        },

        // 都道府県件数更新
        updatePrefectureCounts: function() {
            const $elements = $('[data-prefecture-count]');
            if ($elements.length === 0) return;
            
            const slugs = [];
            $elements.each(function() {
                const slug = $(this).data('prefecture-count');
                if (slug && slugs.indexOf(slug) === -1) {
                    slugs.push(slug);
                }
            });
            
            if (slugs.length === 0) return;
            
            $.ajax({
                url: window.gi_counts.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_grant_counts',
                    nonce: window.gi_counts.nonce,
                    type: 'prefecture',
                    slugs: slugs
                },
                success: function(response) {
                    if (response.success) {
                        this.displayCounts($elements, response.data, 'prefecture');
                    }
                }.bind(this)
            });
        },

        // 総件数更新
        updateTotalCount: function() {
            const $element = $('[data-total-count]');
            if ($element.length === 0) return;
            
            $.ajax({
                url: window.gi_counts.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_grant_counts',
                    nonce: window.gi_counts.nonce,
                    type: 'total'
                },
                success: function(response) {
                    if (response.success && response.data.total !== undefined) {
                        this.animateCount($element, response.data.total);
                    }
                }.bind(this)
            });
        },

        // 件数表示
        displayCounts: function($elements, data, type = 'category') {
            const dataAttr = type === 'prefecture' ? 'prefecture-count' : 'category-count';
            
            $elements.each(function() {
                const $el = $(this);
                const slug = $el.data(dataAttr);
                
                if (data[slug] !== undefined) {
                    const count = parseInt(data[slug]);
                    const format = $el.data('format') || '%d件';
                    
                    // アニメーション効果付きで更新
                    if ($el.hasClass('animate-count')) {
                        DynamicCounts.animateCount($el, count, format);
                    } else {
                        $el.text(format.replace('%d', count.toLocaleString()));
                    }
                    
                    // 0件の場合はクラス追加
                    if (count === 0) {
                        $el.addClass('count-zero');
                    } else {
                        $el.removeClass('count-zero');
                    }
                }
            });
        },

        // カウントアニメーション
        animateCount: function($element, targetCount, format = '%d件') {
            const currentText = $element.text();
            const currentCount = parseInt(currentText.replace(/[^0-9]/g, '')) || 0;
            
            if (currentCount === targetCount) return;
            
            const duration = 1000; // 1秒
            const steps = 30;
            const increment = (targetCount - currentCount) / steps;
            let step = 0;
            
            const timer = setInterval(function() {
                step++;
                const newCount = Math.round(currentCount + (increment * step));
                
                if (step >= steps) {
                    clearInterval(timer);
                    $element.text(format.replace('%d', targetCount.toLocaleString()));
                } else {
                    $element.text(format.replace('%d', newCount.toLocaleString()));
                }
            }, duration / steps);
        },

        // 特定カテゴリーの件数取得
        getCategoryCount: function(slug, callback) {
            $.ajax({
                url: window.gi_counts.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_grant_counts',
                    nonce: window.gi_counts.nonce,
                    type: 'category',
                    slugs: [slug]
                },
                success: function(response) {
                    if (response.success && response.data[slug] !== undefined) {
                        callback(response.data[slug]);
                    } else {
                        callback(0);
                    }
                }
            });
        },

        // 特定都道府県の件数取得
        getPrefectureCount: function(slug, callback) {
            $.ajax({
                url: window.gi_counts.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_grant_counts',
                    nonce: window.gi_counts.nonce,
                    type: 'prefecture',
                    slugs: [slug]
                },
                success: function(response) {
                    if (response.success && response.data[slug] !== undefined) {
                        callback(response.data[slug]);
                    } else {
                        callback(0);
                    }
                }
            });
        }
    };

    // 初期化
    $(document).ready(function() {
        // gi_countsオブジェクトが存在する場合のみ初期化
        if (window.gi_counts) {
            DynamicCounts.init();
        }
        
        // グローバルアクセス用
        window.GIDynamicCounts = DynamicCounts;
    });

})(jQuery);