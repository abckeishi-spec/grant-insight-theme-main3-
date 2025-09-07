/**
 * Grant Insight UI Enhancement JavaScript
 * UI強化機能のJavaScript
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

(function($) {
    'use strict';
    
    // グローバル変数
    let GI_UI = {
        config: gi_ui_config || {},
        breakpoints: {},
        currentBreakpoint: '',
        isTouch: false,
        isKeyboardUser: false,
        observers: {},
        cache: new Map()
    };
    
    /**
     * 初期化
     */
    function init() {
        setupBreakpoints();
        detectDeviceCapabilities();
        setupEventListeners();
        initializeComponents();
        setupAccessibility();
        setupPerformanceOptimizations();
        
        // DOM準備完了後の処理
        $(document).ready(function() {
            initializeLazyLoading();
            initializeAnimations();
            initializeFormEnhancements();
            setupResponsiveImages();
        });
    }
    
    /**
     * ブレークポイントの設定
     */
    function setupBreakpoints() {
        GI_UI.breakpoints = GI_UI.config.breakpoints || {
            'mobile': '320px',
            'mobile-large': '480px',
            'tablet': '768px',
            'tablet-large': '1024px',
            'desktop': '1200px',
            'desktop-large': '1440px'
        };
        
        updateCurrentBreakpoint();
        
        // リサイズ時の処理
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                updateCurrentBreakpoint();
                handleResponsiveChanges();
            }, 250);
        });
    }
    
    /**
     * 現在のブレークポイントを更新
     */
    function updateCurrentBreakpoint() {
        const width = window.innerWidth;
        let newBreakpoint = 'mobile';
        
        if (width >= 1440) newBreakpoint = 'desktop-large';
        else if (width >= 1200) newBreakpoint = 'desktop';
        else if (width >= 1024) newBreakpoint = 'tablet-large';
        else if (width >= 768) newBreakpoint = 'tablet';
        else if (width >= 480) newBreakpoint = 'mobile-large';
        
        if (newBreakpoint !== GI_UI.currentBreakpoint) {
            const oldBreakpoint = GI_UI.currentBreakpoint;
            GI_UI.currentBreakpoint = newBreakpoint;
            
            $('body').removeClass('gi-bp-' + oldBreakpoint).addClass('gi-bp-' + newBreakpoint);
            
            // ブレークポイント変更イベントを発火
            $(document).trigger('gi:breakpoint-change', {
                old: oldBreakpoint,
                new: newBreakpoint,
                width: width
            });
        }
    }
    
    /**
     * デバイス機能の検出
     */
    function detectDeviceCapabilities() {
        // タッチデバイスの検出
        GI_UI.isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        if (GI_UI.isTouch) {
            $('body').addClass('touch-device');
        }
        
        // レティナディスプレイの検出
        if (window.devicePixelRatio > 1) {
            $('body').addClass('retina-display');
        }
        
        // 接続速度の検出
        if ('connection' in navigator) {
            const connection = navigator.connection;
            if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
                $('body').addClass('slow-connection');
            }
        }
        
        // プリファレンスの検出
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            $('body').addClass('reduced-motion');
        }
        
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $('body').addClass('dark-mode-preferred');
        }
        
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            $('body').addClass('high-contrast-preferred');
        }
    }
    
    /**
     * イベントリスナーの設定
     */
    function setupEventListeners() {
        // キーボードナビゲーションの検出
        $(document).on('keydown', function(e) {
            if (e.key === 'Tab') {
                GI_UI.isKeyboardUser = true;
                $('body').addClass('keyboard-navigation');
            }
        });
        
        $(document).on('mousedown', function() {
            GI_UI.isKeyboardUser = false;
            $('body').removeClass('keyboard-navigation');
        });
        
        // スクロールイベント
        let scrollTimer;
        $(window).on('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(handleScroll, 10);
        });
        
        // フォーカス管理
        $(document).on('focusin', '.gi-modal, .gi-dropdown', function(e) {
            trapFocus(e.currentTarget);
        });
        
        // エラーハンドリング
        window.addEventListener('error', function(e) {
            console.error('GI UI Error:', e.error);
            // エラー報告（開発環境のみ）
            if (GI_UI.config.debug) {
                reportError(e.error);
            }
        });
    }
    
    /**
     * コンポーネントの初期化
     */
    function initializeComponents() {
        initializeNavigation();
        initializeModals();
        initializeDropdowns();
        initializeAccordions();
        initializeTabs();
        initializeCarousels();
        initializeTooltips();
    }
    
    /**
     * ナビゲーションの初期化
     */
    function initializeNavigation() {
        // モバイルメニューの切り替え
        $('.gi-nav-toggle').on('click', function() {
            const $menu = $('.gi-nav-menu');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            $menu.toggleClass('active');
            
            // アニメーション
            if (!isExpanded) {
                $menu.slideDown(300);
            } else {
                $menu.slideUp(300);
            }
        });
        
        // サブメニューの処理
        $('.menu-item-has-children > a').on('click', function(e) {
            if (GI_UI.currentBreakpoint === 'mobile' || GI_UI.currentBreakpoint === 'mobile-large') {
                e.preventDefault();
                const $submenu = $(this).siblings('.sub-menu');
                const isExpanded = $(this).attr('aria-expanded') === 'true';
                
                $(this).attr('aria-expanded', !isExpanded);
                $submenu.slideToggle(300);
            }
        });
        
        // スティッキーナビゲーション
        const $header = $('.gi-header');
        if ($header.length) {
            const headerOffset = $header.offset().top;
            
            $(window).on('scroll', function() {
                if ($(window).scrollTop() > headerOffset + 100) {
                    $header.addClass('sticky');
                } else {
                    $header.removeClass('sticky');
                }
            });
        }
    }
    
    /**
     * モーダルの初期化
     */
    function initializeModals() {
        // モーダルを開く
        $('[data-modal-trigger]').on('click', function(e) {
            e.preventDefault();
            const modalId = $(this).data('modal-trigger');
            const $modal = $('#' + modalId);
            
            if ($modal.length) {
                openModal($modal);
            }
        });
        
        // モーダルを閉じる
        $('[data-modal-close]').on('click', function() {
            const $modal = $(this).closest('.gi-modal');
            closeModal($modal);
        });
        
        // オーバーレイクリックで閉じる
        $('.gi-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                closeModal($(this).closest('.gi-modal'));
            }
        });
        
        // ESCキーで閉じる
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                const $openModal = $('.gi-modal.active');
                if ($openModal.length) {
                    closeModal($openModal);
                }
            }
        });
    }
    
    /**
     * モーダルを開く
     */
    function openModal($modal) {
        $modal.addClass('active').attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        
        // フォーカス管理
        const $firstFocusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
        if ($firstFocusable.length) {
            $firstFocusable.focus();
        }
        
        // アニメーション
        $modal.find('.gi-modal-content').css('transform', 'scale(0.8)').animate({
            opacity: 1
        }, 300, function() {
            $(this).css('transform', 'scale(1)');
        });
    }
    
    /**
     * モーダルを閉じる
     */
    function closeModal($modal) {
        $modal.removeClass('active').attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        
        // アニメーション
        $modal.find('.gi-modal-content').animate({
            opacity: 0
        }, 300, function() {
            $(this).css('transform', 'scale(0.8)');
        });
    }
    
    /**
     * ドロップダウンの初期化
     */
    function initializeDropdowns() {
        $('.gi-dropdown-trigger').on('click', function(e) {
            e.preventDefault();
            const $dropdown = $(this).next('.gi-dropdown-menu');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            
            // 他のドロップダウンを閉じる
            $('.gi-dropdown-menu.active').removeClass('active');
            $('.gi-dropdown-trigger[aria-expanded="true"]').attr('aria-expanded', 'false');
            
            if (!isExpanded) {
                $(this).attr('aria-expanded', 'true');
                $dropdown.addClass('active');
            }
        });
        
        // 外側クリックで閉じる
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.gi-dropdown').length) {
                $('.gi-dropdown-menu.active').removeClass('active');
                $('.gi-dropdown-trigger[aria-expanded="true"]').attr('aria-expanded', 'false');
            }
        });
    }
    
    /**
     * アコーディオンの初期化
     */
    function initializeAccordions() {
        $('.gi-accordion-trigger').on('click', function() {
            const $content = $(this).next('.gi-accordion-content');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            
            if (isExpanded) {
                $content.slideUp(300);
            } else {
                $content.slideDown(300);
            }
        });
    }
    
    /**
     * タブの初期化
     */
    function initializeTabs() {
        $('.gi-tab-trigger').on('click', function(e) {
            e.preventDefault();
            const $tabList = $(this).closest('.gi-tab-list');
            const $tabPanels = $tabList.siblings('.gi-tab-panels');
            const targetId = $(this).attr('href').substring(1);
            
            // アクティブ状態の更新
            $tabList.find('.gi-tab-trigger').attr('aria-selected', 'false').removeClass('active');
            $(this).attr('aria-selected', 'true').addClass('active');
            
            // パネルの表示切り替え
            $tabPanels.find('.gi-tab-panel').attr('aria-hidden', 'true').removeClass('active');
            $('#' + targetId).attr('aria-hidden', 'false').addClass('active');
        });
        
        // キーボードナビゲーション
        $('.gi-tab-list').on('keydown', '.gi-tab-trigger', function(e) {
            const $triggers = $(this).closest('.gi-tab-list').find('.gi-tab-trigger');
            const currentIndex = $triggers.index(this);
            let nextIndex;
            
            switch (e.key) {
                case 'ArrowRight':
                    nextIndex = (currentIndex + 1) % $triggers.length;
                    $triggers.eq(nextIndex).focus().click();
                    e.preventDefault();
                    break;
                case 'ArrowLeft':
                    nextIndex = (currentIndex - 1 + $triggers.length) % $triggers.length;
                    $triggers.eq(nextIndex).focus().click();
                    e.preventDefault();
                    break;
                case 'Home':
                    $triggers.first().focus().click();
                    e.preventDefault();
                    break;
                case 'End':
                    $triggers.last().focus().click();
                    e.preventDefault();
                    break;
            }
        });
    }
    
    /**
     * カルーセルの初期化
     */
    function initializeCarousels() {
        $('.gi-carousel').each(function() {
            const $carousel = $(this);
            const $slides = $carousel.find('.gi-carousel-slide');
            const $prevBtn = $carousel.find('.gi-carousel-prev');
            const $nextBtn = $carousel.find('.gi-carousel-next');
            const $indicators = $carousel.find('.gi-carousel-indicator');
            
            let currentSlide = 0;
            const totalSlides = $slides.length;
            
            function goToSlide(index) {
                $slides.removeClass('active').eq(index).addClass('active');
                $indicators.removeClass('active').eq(index).addClass('active');
                currentSlide = index;
                
                // アクセシビリティ
                $carousel.find('[aria-label*="スライド"]').attr('aria-label', `スライド ${index + 1} / ${totalSlides}`);
            }
            
            $prevBtn.on('click', function() {
                const prevSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                goToSlide(prevSlide);
            });
            
            $nextBtn.on('click', function() {
                const nextSlide = (currentSlide + 1) % totalSlides;
                goToSlide(nextSlide);
            });
            
            $indicators.on('click', function() {
                const index = $(this).index();
                goToSlide(index);
            });
            
            // 自動再生
            if ($carousel.data('autoplay')) {
                const interval = $carousel.data('interval') || 5000;
                let autoplayTimer = setInterval(function() {
                    const nextSlide = (currentSlide + 1) % totalSlides;
                    goToSlide(nextSlide);
                }, interval);
                
                // ホバー時は停止
                $carousel.on('mouseenter', function() {
                    clearInterval(autoplayTimer);
                }).on('mouseleave', function() {
                    autoplayTimer = setInterval(function() {
                        const nextSlide = (currentSlide + 1) % totalSlides;
                        goToSlide(nextSlide);
                    }, interval);
                });
            }
        });
    }
    
    /**
     * ツールチップの初期化
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            const $trigger = $(this);
            const content = $trigger.data('tooltip');
            const position = $trigger.data('tooltip-position') || 'top';
            
            const $tooltip = $('<div class="gi-tooltip" role="tooltip">' + content + '</div>');
            $tooltip.addClass('gi-tooltip-' + position);
            $('body').append($tooltip);
            
            $trigger.on('mouseenter focus', function() {
                const offset = $trigger.offset();
                const triggerWidth = $trigger.outerWidth();
                const triggerHeight = $trigger.outerHeight();
                const tooltipWidth = $tooltip.outerWidth();
                const tooltipHeight = $tooltip.outerHeight();
                
                let left, top;
                
                switch (position) {
                    case 'top':
                        left = offset.left + (triggerWidth / 2) - (tooltipWidth / 2);
                        top = offset.top - tooltipHeight - 8;
                        break;
                    case 'bottom':
                        left = offset.left + (triggerWidth / 2) - (tooltipWidth / 2);
                        top = offset.top + triggerHeight + 8;
                        break;
                    case 'left':
                        left = offset.left - tooltipWidth - 8;
                        top = offset.top + (triggerHeight / 2) - (tooltipHeight / 2);
                        break;
                    case 'right':
                        left = offset.left + triggerWidth + 8;
                        top = offset.top + (triggerHeight / 2) - (tooltipHeight / 2);
                        break;
                }
                
                $tooltip.css({ left: left, top: top }).addClass('active');
            });
            
            $trigger.on('mouseleave blur', function() {
                $tooltip.removeClass('active');
            });
        });
    }
    
    /**
     * 遅延読み込みの初期化
     */
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }
                        
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
            
            GI_UI.observers.image = imageObserver;
        }
    }
    
    /**
     * アニメーションの初期化
     */
    function initializeAnimations() {
        if ('IntersectionObserver' in window && !$('body').hasClass('reduced-motion')) {
            const animationObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        animationObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            document.querySelectorAll('.gi-animate').forEach(function(element) {
                animationObserver.observe(element);
            });
            
            GI_UI.observers.animation = animationObserver;
        }
    }
    
    /**
     * フォーム強化の初期化
     */
    function initializeFormEnhancements() {
        // リアルタイムバリデーション
        $('form').on('input', 'input, textarea, select', function() {
            validateField(this);
        });
        
        // 送信時の処理
        $('form').on('submit', function(e) {
            const $form = $(this);
            const isValid = validateForm($form[0]);
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // ローディング状態
            const $submitBtn = $form.find('[type="submit"]');
            $submitBtn.addClass('gi-loading').prop('disabled', true);
            
            // タイムアウト処理
            setTimeout(function() {
                $submitBtn.removeClass('gi-loading').prop('disabled', false);
            }, 30000);
        });
        
        // ファイルアップロード強化
        $('input[type="file"]').on('change', function() {
            const files = this.files;
            const $preview = $(this).siblings('.gi-file-preview');
            
            if ($preview.length && files.length > 0) {
                $preview.empty();
                
                Array.from(files).forEach(function(file) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const $img = $('<img>').attr('src', e.target.result).addClass('gi-preview-image');
                            $preview.append($img);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    }
    
    /**
     * フィールドのバリデーション
     */
    function validateField(field) {
        const $field = $(field);
        const value = $field.val().trim();
        const type = $field.attr('type');
        const required = $field.prop('required');
        
        let isValid = true;
        let errorMessage = '';
        
        // 必須チェック
        if (required && !value) {
            isValid = false;
            errorMessage = 'この項目は必須です。';
        }
        
        // 型別バリデーション
        if (value && type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = '有効なメールアドレスを入力してください。';
            }
        }
        
        if (value && type === 'url') {
            try {
                new URL(value);
            } catch {
                isValid = false;
                errorMessage = '有効なURLを入力してください。';
            }
        }
        
        if (value && type === 'tel') {
            const telRegex = /^[\d\-\+\(\)\s]+$/;
            if (!telRegex.test(value)) {
                isValid = false;
                errorMessage = '有効な電話番号を入力してください。';
            }
        }
        
        // パスワード強度チェック
        if (type === 'password' && value) {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'パスワードは8文字以上で入力してください。';
            }
        }
        
        // 結果の表示
        $field.removeClass('gi-error gi-success');
        const $errorMsg = $field.siblings('.gi-error-message');
        
        if (!isValid) {
            $field.addClass('gi-error');
            if ($errorMsg.length) {
                $errorMsg.text(errorMessage);
            } else {
                $('<div class="gi-error-message" role="alert">' + errorMessage + '</div>').insertAfter($field);
            }
        } else if (value) {
            $field.addClass('gi-success');
            $errorMsg.remove();
        } else {
            $errorMsg.remove();
        }
        
        return isValid;
    }
    
    /**
     * フォーム全体のバリデーション
     */
    function validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('input, textarea, select');
        
        fields.forEach(function(field) {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * レスポンシブ画像の設定
     */
    function setupResponsiveImages() {
        $('img[data-sizes]').each(function() {
            const $img = $(this);
            const sizes = $img.data('sizes');
            
            if (sizes) {
                $img.attr('sizes', sizes);
            }
        });
    }
    
    /**
     * アクセシビリティの設定
     */
    function setupAccessibility() {
        // スキップリンクの処理
        $('.skip-link').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            const $target = $(target);
            
            if ($target.length) {
                $target.attr('tabindex', '-1').focus();
                
                // スムーススクロール
                $('html, body').animate({
                    scrollTop: $target.offset().top
                }, 300);
            }
        });
        
        // ARIA属性の動的更新
        updateAriaAttributes();
    }
    
    /**
     * ARIA属性の更新
     */
    function updateAriaAttributes() {
        // 展開可能な要素
        $('[aria-expanded]').each(function() {
            const $trigger = $(this);
            const targetId = $trigger.attr('aria-controls');
            
            if (targetId) {
                const $target = $('#' + targetId);
                const isExpanded = $trigger.attr('aria-expanded') === 'true';
                
                $target.attr('aria-hidden', !isExpanded);
            }
        });
        
        // ライブリージョンの更新
        $('.gi-live-region').attr('aria-live', 'polite');
    }
    
    /**
     * パフォーマンス最適化の設定
     */
    function setupPerformanceOptimizations() {
        // 画像の最適化
        if ('loading' in HTMLImageElement.prototype) {
            $('img').attr('loading', 'lazy');
        }
        
        // プリロードの設定
        const criticalResources = [
            '/css/critical.css',
            '/js/critical.js'
        ];
        
        criticalResources.forEach(function(resource) {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = resource.endsWith('.css') ? 'style' : 'script';
            document.head.appendChild(link);
        });
    }
    
    /**
     * レスポンシブ変更の処理
     */
    function handleResponsiveChanges() {
        // ナビゲーションの調整
        if (GI_UI.currentBreakpoint === 'mobile' || GI_UI.currentBreakpoint === 'mobile-large') {
            $('.gi-nav-menu').removeClass('active');
            $('.gi-nav-toggle').attr('aria-expanded', 'false');
        }
        
        // モーダルの調整
        $('.gi-modal.active').each(function() {
            const $modal = $(this);
            adjustModalPosition($modal);
        });
        
        // カルーセルの調整
        $('.gi-carousel').each(function() {
            adjustCarouselLayout($(this));
        });
    }
    
    /**
     * スクロール処理
     */
    function handleScroll() {
        const scrollTop = $(window).scrollTop();
        
        // パララックス効果
        $('.gi-parallax').each(function() {
            const $element = $(this);
            const speed = $element.data('parallax-speed') || 0.5;
            const yPos = -(scrollTop * speed);
            
            $element.css('transform', 'translateY(' + yPos + 'px)');
        });
        
        // スクロールインジケーター
        const $indicator = $('.gi-scroll-indicator');
        if ($indicator.length) {
            const documentHeight = $(document).height();
            const windowHeight = $(window).height();
            const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
            
            $indicator.css('width', scrollPercent + '%');
        }
    }
    
    /**
     * フォーカストラップ
     */
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }
    
    /**
     * エラー報告
     */
    function reportError(error) {
        if (GI_UI.config.ajax_url) {
            $.post(GI_UI.config.ajax_url, {
                action: 'gi_report_error',
                error: error.toString(),
                stack: error.stack,
                url: window.location.href,
                userAgent: navigator.userAgent,
                nonce: GI_UI.config.nonce
            });
        }
    }
    
    /**
     * 公開API
     */
    window.GI_UI = {
        init: init,
        getCurrentBreakpoint: function() { return GI_UI.currentBreakpoint; },
        isTouch: function() { return GI_UI.isTouch; },
        isKeyboardUser: function() { return GI_UI.isKeyboardUser; },
        openModal: openModal,
        closeModal: closeModal,
        validateField: validateField,
        validateForm: validateForm
    };
    
    // 初期化実行
    init();
    
})(jQuery);

