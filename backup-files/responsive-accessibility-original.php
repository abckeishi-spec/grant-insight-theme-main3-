<?php
/**
 * Responsive and Accessibility Enhancement
 * 
 * Task 14: レスポンシブ・アクセシビリティ強化
 * Implements responsive design improvements and accessibility features
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 14.1 レスポンシブ改善
 * Responsive design improvements
 */
function gi_responsive_styles() {
    ?>
    <style>
        /* Base responsive utilities */
        .container-responsive {
            width: 100%;
            padding-right: 1rem;
            padding-left: 1rem;
            margin-right: auto;
            margin-left: auto;
        }
        
        /* Breakpoint: 768px (Tablet) */
        @media (min-width: 768px) {
            .container-responsive {
                max-width: 768px;
                padding-right: 1.5rem;
                padding-left: 1.5rem;
            }
            
            /* Grid adjustments for tablets */
            .grant-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Breakpoint: 1024px (Desktop) */
        @media (min-width: 1024px) {
            .container-responsive {
                max-width: 1024px;
                padding-right: 2rem;
                padding-left: 2rem;
            }
            
            /* Grid adjustments for desktop */
            .grant-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            /* Sidebar layout */
            .content-with-sidebar {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 2rem;
            }
        }
        
        /* Breakpoint: 1280px (Large Desktop) */
        @media (min-width: 1280px) {
            .container-responsive {
                max-width: 1280px;
            }
            
            /* Grid adjustments for large screens */
            .grant-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .content-with-sidebar {
                grid-template-columns: 1fr 350px;
            }
        }
        
        /* Mobile-first responsive tables */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        
        .table-responsive table {
            min-width: 100%;
            border-collapse: collapse;
        }
        
        @media (max-width: 767px) {
            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive th,
            .table-responsive td,
            .table-responsive tr {
                display: block;
            }
            
            .table-responsive thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .table-responsive tr {
                border: 1px solid #e5e7eb;
                margin-bottom: 1rem;
                border-radius: 0.5rem;
                padding: 1rem;
            }
            
            .table-responsive td {
                border: none;
                position: relative;
                padding-left: 50%;
                min-height: 2rem;
                display: flex;
                align-items: center;
            }
            
            .table-responsive td:before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                width: 45%;
                text-align: left;
                font-weight: 600;
                color: #6b7280;
            }
        }
        
        /* Responsive navigation */
        .nav-responsive {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .nav-responsive {
                flex-direction: row;
                align-items: center;
                gap: 1rem;
            }
        }
        
        /* Responsive cards */
        .card-responsive {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        @media (min-width: 768px) {
            .card-responsive {
                padding: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .card-responsive {
                padding: 2rem;
            }
            
            .card-responsive:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Responsive typography */
        .heading-responsive {
            font-size: 1.5rem;
            line-height: 1.3;
        }
        
        @media (min-width: 768px) {
            .heading-responsive {
                font-size: 1.875rem;
            }
        }
        
        @media (min-width: 1024px) {
            .heading-responsive {
                font-size: 2.25rem;
            }
        }
        
        /* Responsive spacing utilities */
        .spacing-responsive {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (min-width: 768px) {
            .spacing-responsive {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .spacing-responsive {
                padding: 2rem;
                margin-bottom: 2rem;
            }
        }
        
        /* Responsive images */
        .img-responsive {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        /* Responsive modals */
        .modal-responsive {
            width: calc(100% - 2rem);
            max-width: 500px;
            margin: 1rem;
        }
        
        @media (min-width: 768px) {
            .modal-responsive {
                width: 90%;
                max-width: 600px;
            }
        }
        
        @media (min-width: 1024px) {
            .modal-responsive {
                width: 80%;
                max-width: 800px;
            }
        }
        
        /* Hide/show utilities */
        .mobile-only {
            display: block;
        }
        
        .tablet-up {
            display: none;
        }
        
        .desktop-up {
            display: none;
        }
        
        @media (min-width: 768px) {
            .mobile-only {
                display: none;
            }
            
            .tablet-up {
                display: block;
            }
        }
        
        @media (min-width: 1024px) {
            .desktop-up {
                display: block;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'gi_responsive_styles', 20);

/**
 * 14.2 アクセシビリティ対応
 * Accessibility improvements
 */
class GI_Accessibility {
    
    /**
     * Initialize accessibility features
     */
    public static function init() {
        add_action('wp_head', array(__CLASS__, 'add_accessibility_styles'));
        add_action('wp_footer', array(__CLASS__, 'add_accessibility_scripts'));
        add_filter('body_class', array(__CLASS__, 'add_body_classes'));
        add_action('wp_footer', array(__CLASS__, 'add_skip_links'));
    }
    
    /**
     * Add accessibility-focused styles
     */
    public static function add_accessibility_styles() {
        ?>
        <style>
            /* Focus visible styles */
            *:focus {
                outline: 2px solid transparent;
                outline-offset: 2px;
            }
            
            *:focus-visible {
                outline: 2px solid #6366f1;
                outline-offset: 2px;
                border-radius: 0.25rem;
            }
            
            /* Skip links */
            .skip-links {
                position: absolute;
                top: -40px;
                left: 0;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                z-index: 100000;
            }
            
            .skip-links:focus {
                top: 0;
            }
            
            /* Screen reader only text */
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border-width: 0;
            }
            
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                * {
                    border-color: currentColor !important;
                }
                
                a {
                    text-decoration: underline !important;
                }
                
                button,
                .button {
                    border: 2px solid currentColor !important;
                }
            }
            
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }
            
            /* Color blind friendly colors */
            .color-safe-success {
                color: #059669; /* Green that works for most color blindness */
                background-color: #d1fae5;
            }
            
            .color-safe-warning {
                color: #d97706; /* Orange that works for most color blindness */
                background-color: #fed7aa;
            }
            
            .color-safe-error {
                color: #dc2626; /* Red with good contrast */
                background-color: #fee2e2;
            }
            
            .color-safe-info {
                color: #0891b2; /* Cyan with good contrast */
                background-color: #cffafe;
            }
            
            /* Keyboard navigation indicators */
            .keyboard-navigable {
                position: relative;
            }
            
            .keyboard-navigable:focus::after {
                content: '';
                position: absolute;
                top: -4px;
                right: -4px;
                bottom: -4px;
                left: -4px;
                border: 2px solid #6366f1;
                border-radius: 0.5rem;
                pointer-events: none;
            }
            
            /* Accessible buttons */
            .btn-accessible {
                min-height: 44px;
                min-width: 44px;
                padding: 0.5rem 1rem;
                font-size: 1rem;
                line-height: 1.5;
                border: 2px solid transparent;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .btn-accessible:hover:not(:disabled) {
                transform: scale(1.05);
            }
            
            .btn-accessible:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* Accessible forms */
            .form-accessible label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                color: #374151;
            }
            
            .form-accessible input,
            .form-accessible select,
            .form-accessible textarea {
                width: 100%;
                min-height: 44px;
                padding: 0.5rem 0.75rem;
                font-size: 1rem;
                border: 2px solid #d1d5db;
                border-radius: 0.375rem;
                transition: border-color 0.2s ease;
            }
            
            .form-accessible input:focus,
            .form-accessible select:focus,
            .form-accessible textarea:focus {
                border-color: #6366f1;
                outline: none;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }
            
            .form-accessible .error-message {
                color: #dc2626;
                font-size: 0.875rem;
                margin-top: 0.25rem;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .form-accessible .error-message::before {
                content: '⚠';
                font-size: 1rem;
            }
            
            /* Accessible tables */
            .table-accessible {
                width: 100%;
                border-collapse: collapse;
                caption-side: top;
            }
            
            .table-accessible caption {
                font-weight: 600;
                margin-bottom: 0.5rem;
                text-align: left;
            }
            
            .table-accessible th {
                background-color: #f3f4f6;
                font-weight: 600;
                text-align: left;
                padding: 0.75rem;
            }
            
            .table-accessible td {
                padding: 0.75rem;
                border-top: 1px solid #e5e7eb;
            }
            
            .table-accessible tbody tr:hover {
                background-color: #f9fafb;
            }
            
            /* Accessible tooltips */
            [role="tooltip"] {
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                z-index: 1000;
                pointer-events: none;
            }
            
            /* Loading states */
            .loading-accessible {
                position: relative;
            }
            
            .loading-accessible::after {
                content: '読み込み中...';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                padding: 1rem;
                border-radius: 0.375rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            
            /* Accessible icons */
            .icon-with-text {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .icon-only {
                position: relative;
            }
            
            .icon-only .icon-label {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border-width: 0;
            }
        </style>
        <?php
    }
    
    /**
     * Add accessibility JavaScript
     */
    public static function add_accessibility_scripts() {
        ?>
        <script>
        (function() {
            'use strict';
            
            // Keyboard navigation handler
            const KeyboardNavigation = {
                init: function() {
                    // Add keyboard event listeners
                    document.addEventListener('keydown', this.handleKeyDown.bind(this));
                    
                    // Mark interactive elements
                    this.markInteractiveElements();
                    
                    // Setup focus trap for modals
                    this.setupFocusTrap();
                    
                    // Add ARIA live regions
                    this.setupLiveRegions();
                },
                
                handleKeyDown: function(e) {
                    // Tab navigation enhancement
                    if (e.key === 'Tab') {
                        document.body.classList.add('keyboard-nav');
                    }
                    
                    // Escape key to close modals
                    if (e.key === 'Escape') {
                        this.closeActiveModal();
                    }
                    
                    // Arrow key navigation for menus
                    if (e.target.closest('[role="menu"]')) {
                        this.handleMenuNavigation(e);
                    }
                },
                
                markInteractiveElements: function() {
                    // Add tabindex to interactive elements without it
                    const interactiveElements = document.querySelectorAll(
                        'a, button, input, select, textarea, [onclick], [role="button"]'
                    );
                    
                    interactiveElements.forEach(element => {
                        if (!element.hasAttribute('tabindex') && !element.disabled) {
                            element.setAttribute('tabindex', '0');
                        }
                        
                        // Add keyboard event handlers for clickable elements
                        if (element.hasAttribute('onclick') && !element.matches('button, a')) {
                            element.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    element.click();
                                }
                            });
                        }
                    });
                },
                
                setupFocusTrap: function() {
                    const modals = document.querySelectorAll('[role="dialog"], .modal');
                    
                    modals.forEach(modal => {
                        const focusableElements = modal.querySelectorAll(
                            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
                        );
                        
                        if (focusableElements.length > 0) {
                            const firstElement = focusableElements[0];
                            const lastElement = focusableElements[focusableElements.length - 1];
                            
                            modal.addEventListener('keydown', function(e) {
                                if (e.key === 'Tab') {
                                    if (e.shiftKey && document.activeElement === firstElement) {
                                        e.preventDefault();
                                        lastElement.focus();
                                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                                        e.preventDefault();
                                        firstElement.focus();
                                    }
                                }
                            });
                        }
                    });
                },
                
                setupLiveRegions: function() {
                    // Create live region for announcements
                    if (!document.getElementById('aria-live-region')) {
                        const liveRegion = document.createElement('div');
                        liveRegion.id = 'aria-live-region';
                        liveRegion.setAttribute('aria-live', 'polite');
                        liveRegion.setAttribute('aria-atomic', 'true');
                        liveRegion.className = 'sr-only';
                        document.body.appendChild(liveRegion);
                    }
                },
                
                handleMenuNavigation: function(e) {
                    const menu = e.target.closest('[role="menu"]');
                    const items = menu.querySelectorAll('[role="menuitem"]');
                    const currentIndex = Array.from(items).indexOf(e.target);
                    
                    let nextIndex = currentIndex;
                    
                    switch(e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            nextIndex = (currentIndex + 1) % items.length;
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            nextIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                            break;
                        case 'Home':
                            e.preventDefault();
                            nextIndex = 0;
                            break;
                        case 'End':
                            e.preventDefault();
                            nextIndex = items.length - 1;
                            break;
                    }
                    
                    if (nextIndex !== currentIndex) {
                        items[nextIndex].focus();
                    }
                },
                
                closeActiveModal: function() {
                    const activeModal = document.querySelector('.modal.active, [role="dialog"][aria-hidden="false"]');
                    if (activeModal) {
                        const closeButton = activeModal.querySelector('.modal-close, [aria-label="閉じる"]');
                        if (closeButton) {
                            closeButton.click();
                        }
                    }
                },
                
                announce: function(message) {
                    const liveRegion = document.getElementById('aria-live-region');
                    if (liveRegion) {
                        liveRegion.textContent = message;
                        setTimeout(() => {
                            liveRegion.textContent = '';
                        }, 1000);
                    }
                }
            };
            
            // ARIA attributes handler
            const AriaHandler = {
                init: function() {
                    this.addAriaLabels();
                    this.addAriaDescriptions();
                    this.addRoles();
                    this.setupExpandables();
                },
                
                addAriaLabels: function() {
                    // Add aria-labels to icon-only buttons
                    document.querySelectorAll('button:not([aria-label])').forEach(button => {
                        if (!button.textContent.trim() && button.querySelector('svg, img')) {
                            // Try to determine label from context
                            const title = button.getAttribute('title');
                            const dataLabel = button.getAttribute('data-label');
                            
                            if (title) {
                                button.setAttribute('aria-label', title);
                            } else if (dataLabel) {
                                button.setAttribute('aria-label', dataLabel);
                            }
                        }
                    });
                    
                    // Add labels to form inputs
                    document.querySelectorAll('input, select, textarea').forEach(input => {
                        if (!input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
                            const label = document.querySelector(`label[for="${input.id}"]`);
                            if (label) {
                                input.setAttribute('aria-labelledby', label.id || this.generateId(label));
                            } else {
                                const placeholder = input.getAttribute('placeholder');
                                if (placeholder) {
                                    input.setAttribute('aria-label', placeholder);
                                }
                            }
                        }
                    });
                },
                
                addAriaDescriptions: function() {
                    // Add descriptions to complex elements
                    document.querySelectorAll('[data-description]').forEach(element => {
                        const description = element.getAttribute('data-description');
                        if (description && !element.getAttribute('aria-describedby')) {
                            const descId = this.generateId();
                            const descElement = document.createElement('span');
                            descElement.id = descId;
                            descElement.className = 'sr-only';
                            descElement.textContent = description;
                            element.appendChild(descElement);
                            element.setAttribute('aria-describedby', descId);
                        }
                    });
                },
                
                addRoles: function() {
                    // Add semantic roles
                    document.querySelectorAll('nav:not([role])').forEach(nav => {
                        nav.setAttribute('role', 'navigation');
                    });
                    
                    document.querySelectorAll('header:not([role])').forEach(header => {
                        header.setAttribute('role', 'banner');
                    });
                    
                    document.querySelectorAll('main:not([role])').forEach(main => {
                        main.setAttribute('role', 'main');
                    });
                    
                    document.querySelectorAll('footer:not([role])').forEach(footer => {
                        footer.setAttribute('role', 'contentinfo');
                    });
                    
                    document.querySelectorAll('form:not([role])').forEach(form => {
                        form.setAttribute('role', 'form');
                    });
                },
                
                setupExpandables: function() {
                    // Setup aria-expanded for collapsible elements
                    document.querySelectorAll('[data-toggle]').forEach(toggle => {
                        const targetId = toggle.getAttribute('data-toggle');
                        const target = document.getElementById(targetId);
                        
                        if (target) {
                            toggle.setAttribute('aria-controls', targetId);
                            toggle.setAttribute('aria-expanded', !target.classList.contains('hidden'));
                            
                            toggle.addEventListener('click', function() {
                                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                                toggle.setAttribute('aria-expanded', !isExpanded);
                            });
                        }
                    });
                },
                
                generateId: function(element) {
                    const id = 'aria-' + Math.random().toString(36).substr(2, 9);
                    if (element) {
                        element.id = id;
                    }
                    return id;
                }
            };
            
            // Color contrast checker
            const ContrastChecker = {
                check: function() {
                    // Check if user prefers high contrast
                    const prefersHighContrast = window.matchMedia('(prefers-contrast: high)').matches;
                    
                    if (prefersHighContrast) {
                        document.body.classList.add('high-contrast');
                    }
                    
                    // Listen for changes
                    window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
                        if (e.matches) {
                            document.body.classList.add('high-contrast');
                        } else {
                            document.body.classList.remove('high-contrast');
                        }
                    });
                }
            };
            
            // Initialize all accessibility features
            document.addEventListener('DOMContentLoaded', function() {
                KeyboardNavigation.init();
                AriaHandler.init();
                ContrastChecker.check();
                
                // Remove keyboard navigation class on mouse click
                document.addEventListener('mousedown', function() {
                    document.body.classList.remove('keyboard-nav');
                });
                
                // Announce page changes for screen readers
                if (window.history && window.history.pushState) {
                    const originalPushState = window.history.pushState;
                    window.history.pushState = function() {
                        originalPushState.apply(window.history, arguments);
                        KeyboardNavigation.announce('ページが更新されました');
                    };
                }
            });
            
            // Export for use in other scripts
            window.GIAccessibility = {
                announce: KeyboardNavigation.announce,
                generateId: AriaHandler.generateId
            };
        })();
        </script>
        <?php
    }
    
    /**
     * Add body classes for accessibility
     */
    public static function add_body_classes($classes) {
        // Add class for reduced motion preference
        if (isset($_COOKIE['prefers_reduced_motion'])) {
            $classes[] = 'reduced-motion';
        }
        
        // Add class for high contrast preference
        if (isset($_COOKIE['prefers_high_contrast'])) {
            $classes[] = 'high-contrast';
        }
        
        // Add class for large text preference
        if (isset($_COOKIE['prefers_large_text'])) {
            $classes[] = 'large-text';
        }
        
        return $classes;
    }
    
    /**
     * Add skip links for keyboard navigation
     */
    public static function add_skip_links() {
        ?>
        <div class="skip-links-container">
            <a href="#main" class="skip-links">メインコンテンツへスキップ</a>
            <a href="#search" class="skip-links">検索へスキップ</a>
            <a href="#navigation" class="skip-links">ナビゲーションへスキップ</a>
        </div>
        <?php
    }
}

// Initialize accessibility features
add_action('init', array('GI_Accessibility', 'init'));

/**
 * Accessibility settings panel
 */
function gi_accessibility_settings_panel() {
    ?>
    <div id="accessibility-panel" class="accessibility-panel" style="display: none;">
        <h3>アクセシビリティ設定</h3>
        
        <div class="setting-group">
            <label for="text-size">文字サイズ</label>
            <div class="button-group">
                <button data-size="small">小</button>
                <button data-size="normal" class="active">標準</button>
                <button data-size="large">大</button>
                <button data-size="xlarge">特大</button>
            </div>
        </div>
        
        <div class="setting-group">
            <label for="contrast">コントラスト</label>
            <div class="button-group">
                <button data-contrast="normal" class="active">標準</button>
                <button data-contrast="high">高</button>
                <button data-contrast="dark">ダークモード</button>
            </div>
        </div>
        
        <div class="setting-group">
            <label>
                <input type="checkbox" id="reduce-motion">
                アニメーションを減らす
            </label>
        </div>
        
        <div class="setting-group">
            <label>
                <input type="checkbox" id="highlight-focus">
                フォーカスを強調表示
            </label>
        </div>
        
        <div class="setting-group">
            <label>
                <input type="checkbox" id="show-alt-text">
                画像の代替テキストを表示
            </label>
        </div>
        
        <button class="reset-settings">設定をリセット</button>
    </div>
    
    <button id="accessibility-toggle" class="accessibility-toggle" aria-label="アクセシビリティ設定">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 1v6m0 6v6m4.22-13.22l-3.52 3.52m-1.4 1.4l-3.52 3.52M20.5 12h-6m-6 0h-6m13.22 4.22l-3.52-3.52m-1.4-1.4l-3.52-3.52"></path>
        </svg>
    </button>
    
    <style>
        .accessibility-panel {
            position: fixed;
            right: 20px;
            top: 100px;
            width: 300px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            z-index: 9999;
        }
        
        .accessibility-panel h3 {
            margin: 0 0 1rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .setting-group {
            margin-bottom: 1.5rem;
        }
        
        .setting-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 0.5rem;
        }
        
        .button-group button {
            padding: 0.5rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .button-group button.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        .accessibility-toggle {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.25);
            transition: all 0.3s;
            z-index: 9998;
        }
        
        .accessibility-toggle:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        .reset-settings {
            width: 100%;
            padding: 0.75rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .reset-settings:hover {
            background: #f3f4f6;
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const panel = document.getElementById('accessibility-panel');
        const toggle = document.getElementById('accessibility-toggle');
        
        // Toggle panel
        toggle.addEventListener('click', function() {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        
        // Text size settings
        document.querySelectorAll('[data-size]').forEach(button => {
            button.addEventListener('click', function() {
                const size = this.dataset.size;
                document.body.className = document.body.className.replace(/text-size-\w+/g, '');
                document.body.classList.add('text-size-' + size);
                
                // Update active state
                document.querySelectorAll('[data-size]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Save preference
                localStorage.setItem('text-size', size);
            });
        });
        
        // Contrast settings
        document.querySelectorAll('[data-contrast]').forEach(button => {
            button.addEventListener('click', function() {
                const contrast = this.dataset.contrast;
                document.body.className = document.body.className.replace(/contrast-\w+/g, '');
                if (contrast !== 'normal') {
                    document.body.classList.add('contrast-' + contrast);
                }
                
                // Update active state
                document.querySelectorAll('[data-contrast]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Save preference
                localStorage.setItem('contrast', contrast);
            });
        });
        
        // Other settings
        document.getElementById('reduce-motion').addEventListener('change', function() {
            document.body.classList.toggle('reduced-motion', this.checked);
            localStorage.setItem('reduce-motion', this.checked);
        });
        
        document.getElementById('highlight-focus').addEventListener('change', function() {
            document.body.classList.toggle('highlight-focus', this.checked);
            localStorage.setItem('highlight-focus', this.checked);
        });
        
        document.getElementById('show-alt-text').addEventListener('change', function() {
            document.body.classList.toggle('show-alt-text', this.checked);
            localStorage.setItem('show-alt-text', this.checked);
        });
        
        // Reset settings
        document.querySelector('.reset-settings').addEventListener('click', function() {
            localStorage.removeItem('text-size');
            localStorage.removeItem('contrast');
            localStorage.removeItem('reduce-motion');
            localStorage.removeItem('highlight-focus');
            localStorage.removeItem('show-alt-text');
            location.reload();
        });
        
        // Load saved settings
        const savedTextSize = localStorage.getItem('text-size');
        if (savedTextSize) {
            document.querySelector(`[data-size="${savedTextSize}"]`)?.click();
        }
        
        const savedContrast = localStorage.getItem('contrast');
        if (savedContrast) {
            document.querySelector(`[data-contrast="${savedContrast}"]`)?.click();
        }
        
        if (localStorage.getItem('reduce-motion') === 'true') {
            document.getElementById('reduce-motion').checked = true;
            document.body.classList.add('reduced-motion');
        }
        
        if (localStorage.getItem('highlight-focus') === 'true') {
            document.getElementById('highlight-focus').checked = true;
            document.body.classList.add('highlight-focus');
        }
        
        if (localStorage.getItem('show-alt-text') === 'true') {
            document.getElementById('show-alt-text').checked = true;
            document.body.classList.add('show-alt-text');
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'gi_accessibility_settings_panel');