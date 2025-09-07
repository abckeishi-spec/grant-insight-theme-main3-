/**
 * カスタマイザーライブプレビュー用JavaScript
 */
(function($) {
    'use strict';

    // ヘッダー背景色
    wp.customize('gi_header_bg_color', function(value) {
        value.bind(function(newval) {
            $('.site-header, header.header, #masthead').css('background-color', newval);
        });
    });

    // ヘッダーテキスト色
    wp.customize('gi_header_text_color', function(value) {
        value.bind(function(newval) {
            $('.site-header, header.header, #masthead').css('color', newval);
        });
    });

    // ヘッダーリンク色
    wp.customize('gi_header_link_color', function(value) {
        value.bind(function(newval) {
            $('.site-header a, header.header a, #masthead a').css('color', newval);
        });
    });

    // ヘッダーリンクホバー色（CSSルールを動的に更新）
    wp.customize('gi_header_link_hover_color', function(value) {
        value.bind(function(newval) {
            updateHoverStyle('header-link-hover', '.site-header a:hover, header.header a:hover, #masthead a:hover', 'color', newval);
        });
    });

    // フッター背景色
    wp.customize('gi_footer_bg_color', function(value) {
        value.bind(function(newval) {
            $('.site-footer, footer.footer, #colophon').css('background-color', newval);
        });
    });

    // フッターテキスト色
    wp.customize('gi_footer_text_color', function(value) {
        value.bind(function(newval) {
            $('.site-footer, footer.footer, #colophon').css('color', newval);
        });
    });

    // フッターリンク色
    wp.customize('gi_footer_link_color', function(value) {
        value.bind(function(newval) {
            $('.site-footer a, footer.footer a, #colophon a').css('color', newval);
        });
    });

    // フッターリンクホバー色
    wp.customize('gi_footer_link_hover_color', function(value) {
        value.bind(function(newval) {
            updateHoverStyle('footer-link-hover', '.site-footer a:hover, footer.footer a:hover, #colophon a:hover', 'color', newval);
        });
    });

    // アクセント色
    wp.customize('gi_accent_color', function(value) {
        value.bind(function(newval) {
            $('.btn-primary, .button-primary, .wp-block-button__link, .ai-diagnosis-start').css({
                'background-color': newval,
                'border-color': newval
            });
            $('.text-accent, .highlight').css('color', newval);
        });
    });

    // ロゴ幅
    wp.customize('gi_logo_width', function(value) {
        value.bind(function(newval) {
            $('.gi-custom-logo').css('max-width', newval + 'px');
        });
    });

    // ロゴ位置
    wp.customize('gi_logo_position', function(value) {
        value.bind(function(newval) {
            var alignment = {
                'left': 'flex-start',
                'center': 'center',
                'right': 'flex-end'
            };
            $('.logo-container, .site-branding').css('justify-content', alignment[newval] || 'flex-start');
        });
    });

    // メインロゴ
    wp.customize('gi_main_logo', function(value) {
        value.bind(function(newval) {
            if (newval) {
                var img = '<img src="' + newval + '" alt="' + wp.customize('blogname').get() + '" class="gi-custom-logo header-logo">';
                $('.header-logo').replaceWith(img);
            }
        });
    });

    // フッターロゴ
    wp.customize('gi_footer_logo', function(value) {
        value.bind(function(newval) {
            if (newval) {
                var img = '<img src="' + newval + '" alt="' + wp.customize('blogname').get() + '" class="gi-custom-logo footer-logo">';
                $('.footer-logo').replaceWith(img);
            }
        });
    });

    // モバイルロゴ
    wp.customize('gi_mobile_logo', function(value) {
        value.bind(function(newval) {
            if (newval) {
                var img = '<img src="' + newval + '" alt="' + wp.customize('blogname').get() + '" class="gi-custom-logo mobile-logo">';
                $('.mobile-logo').replaceWith(img);
            }
        });
    });

    // ホバースタイルを動的に更新する関数
    function updateHoverStyle(id, selector, property, value) {
        var styleId = 'gi-hover-style-' + id;
        var $style = $('#' + styleId);
        
        if ($style.length === 0) {
            $style = $('<style id="' + styleId + '"></style>');
            $('head').append($style);
        }
        
        $style.html(selector + ' { ' + property + ': ' + value + ' !important; }');
    }

    // アイコンのリアルタイム更新
    var iconTypes = [
        'diagnosis_main', 'step_icon', 'result_icon',
        'featured', 'new', 'hot', 'urgent', 'success', 'warning',
        'search', 'filter', 'favorite', 'share', 'download', 'info', 'help',
        'it_digital', 'manufacturing', 'startup', 'regional',
        'environment', 'welfare', 'education', 'tourism',
        'menu_hamburger', 'close', 'expand', 'collapse', 'external_link'
    ];

    iconTypes.forEach(function(iconType) {
        wp.customize('gi_icon_' + iconType, function(value) {
            value.bind(function(newval) {
                if (newval) {
                    // 画像アイコンを更新
                    $('.gi-icon-' + iconType).each(function() {
                        if ($(this).is('img')) {
                            $(this).attr('src', newval);
                        } else {
                            // 絵文字を画像に置換
                            var size = $(this).data('size') || 24;
                            var img = '<img src="' + newval + '" alt="' + iconType + '" class="gi-custom-icon gi-icon-' + iconType + ' inline-icon" width="' + size + '" height="' + size + '">';
                            $(this).replaceWith(img);
                        }
                    });
                }
            });
        });
    });

    // カラーダークニング関数
    function darkenColor(color, percent) {
        var num = parseInt(color.replace('#', ''), 16);
        var amt = Math.round(2.55 * percent);
        var R = (num >> 16) + amt;
        var B = (num >> 8 & 0x00FF) + amt;
        var G = (num & 0x0000FF) + amt;
        
        return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (B < 255 ? B < 1 ? 0 : B : 255) * 0x100 +
            (G < 255 ? G < 1 ? 0 : G : 255))
            .toString(16).slice(1);
    }

})(jQuery);