/**
 * AI診断機能 - フロントエンドJavaScript
 */
(function($) {
    'use strict';

    const AIDiagnosis = {
        // 初期化
        init: function() {
            this.bindEvents();
            this.currentStep = 1;
            this.answers = {};
            this.questions = window.gi_ai_diagnosis ? window.gi_ai_diagnosis.questions : {};
        },

        // イベントバインディング
        bindEvents: function() {
            // 診断開始ボタン
            $(document).on('click', '.ai-diagnosis-start', this.startDiagnosis.bind(this));
            
            // 次へボタン
            $(document).on('click', '.ai-diagnosis-next', this.nextStep.bind(this));
            
            // 戻るボタン
            $(document).on('click', '.ai-diagnosis-prev', this.prevStep.bind(this));
            
            // 回答選択
            $(document).on('change', '.ai-diagnosis-answer', this.saveAnswer.bind(this));
            
            // 診断実行
            $(document).on('click', '.ai-diagnosis-submit', this.submitDiagnosis.bind(this));
            
            // 履歴表示
            $(document).on('click', '.ai-diagnosis-history', this.showHistory.bind(this));
        },

        // 診断開始
        startDiagnosis: function(e) {
            e.preventDefault();
            this.currentStep = 1;
            this.answers = {};
            this.showStep(1);
            
            // モーダル表示
            $('#ai-diagnosis-modal').fadeIn();
        },

        // 次のステップへ
        nextStep: function(e) {
            e.preventDefault();
            
            // 現在のステップの検証
            if (!this.validateStep(this.currentStep)) {
                this.showError('必須項目を選択してください。');
                return;
            }
            
            this.currentStep++;
            this.showStep(this.currentStep);
        },

        // 前のステップへ
        prevStep: function(e) {
            e.preventDefault();
            
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
            }
        },

        // ステップ表示
        showStep: function(step) {
            $('.ai-diagnosis-step').hide();
            $('.ai-diagnosis-step[data-step="' + step + '"]').fadeIn();
            
            // プログレスバー更新
            const totalSteps = Object.keys(this.questions).length;
            const progress = (step / totalSteps) * 100;
            $('.ai-diagnosis-progress-bar').css('width', progress + '%');
            $('.ai-diagnosis-progress-text').text('ステップ ' + step + ' / ' + totalSteps);
        },

        // 回答保存
        saveAnswer: function(e) {
            const $input = $(e.target);
            const questionKey = $input.attr('name');
            const questionType = $input.attr('type');
            
            if (questionType === 'checkbox') {
                // 複数選択
                if (!this.answers[questionKey]) {
                    this.answers[questionKey] = [];
                }
                
                if ($input.is(':checked')) {
                    this.answers[questionKey].push($input.val());
                } else {
                    const index = this.answers[questionKey].indexOf($input.val());
                    if (index > -1) {
                        this.answers[questionKey].splice(index, 1);
                    }
                }
            } else {
                // 単一選択
                this.answers[questionKey] = $input.val();
            }
        },

        // ステップ検証
        validateStep: function(step) {
            const stepElement = $('.ai-diagnosis-step[data-step="' + step + '"]');
            const questionKey = stepElement.data('question');
            const question = this.questions[questionKey];
            
            if (question && question.required) {
                const answer = this.answers[questionKey];
                
                if (!answer || (Array.isArray(answer) && answer.length === 0)) {
                    return false;
                }
            }
            
            return true;
        },

        // 診断送信
        submitDiagnosis: function(e) {
            e.preventDefault();
            
            // 最終ステップの検証
            if (!this.validateStep(this.currentStep)) {
                this.showError('必須項目を選択してください。');
                return;
            }
            
            // ローディング表示
            this.showLoading();
            
            // Ajax送信
            $.ajax({
                url: window.gi_ai_diagnosis.ajax_url,
                type: 'POST',
                data: {
                    action: 'gi_ai_diagnosis',
                    nonce: window.gi_ai_diagnosis.nonce,
                    answers: JSON.stringify(this.answers)
                },
                success: this.handleDiagnosisSuccess.bind(this),
                error: this.handleDiagnosisError.bind(this)
            });
        },

        // 診断成功処理
        handleDiagnosisSuccess: function(response) {
            this.hideLoading();
            
            if (response.success) {
                this.showResults(response.data);
            } else {
                this.showError(response.data.message || 'エラーが発生しました。');
                
                // フォールバック助成金表示
                if (response.data.fallback_grants) {
                    this.showFallbackGrants(response.data.fallback_grants);
                }
            }
        },

        // 診断エラー処理
        handleDiagnosisError: function(xhr, status, error) {
            this.hideLoading();
            this.showError('通信エラーが発生しました。時間をおいて再度お試しください。');
        },

        // 結果表示
        showResults: function(data) {
            let html = '<div class="ai-diagnosis-results">';
            
            // 信頼度スコア
            html += '<div class="confidence-score">';
            html += '<h3>マッチング信頼度</h3>';
            html += '<div class="score-bar">';
            html += '<div class="score-fill" style="width: ' + data.confidence_score + '%">';
            html += '<span>' + Math.round(data.confidence_score) + '%</span>';
            html += '</div></div></div>';
            
            // 推奨事項
            if (data.recommendations && data.recommendations.length > 0) {
                html += '<div class="recommendations">';
                html += '<h3>推奨事項</h3>';
                html += '<ul>';
                data.recommendations.forEach(function(rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul></div>';
            }
            
            // マッチした助成金
            html += '<div class="matched-grants">';
            html += '<h3>あなたにおすすめの助成金</h3>';
            
            if (data.matched_grants && data.matched_grants.length > 0) {
                data.matched_grants.forEach(function(grant) {
                    html += '<div class="grant-result-card">';
                    html += '<h4><a href="' + grant.permalink + '" target="_blank">' + grant.title + '</a></h4>';
                    
                    // マッチング理由
                    if (data.match_reasons[grant.id]) {
                        html += '<div class="match-reasons">';
                        data.match_reasons[grant.id].forEach(function(reason) {
                            html += '<span class="reason-badge">' + reason + '</span>';
                        });
                        html += '</div>';
                    }
                    
                    html += '<p class="grant-excerpt">' + grant.excerpt + '</p>';
                    html += '<div class="grant-meta">';
                    html += '<span class="amount">最大 ' + grant.amount + '万円</span>';
                    html += '<span class="deadline">締切: ' + (grant.deadline || '随時') + '</span>';
                    html += '</div>';
                    html += '</div>';
                });
            } else {
                html += '<p>該当する助成金が見つかりませんでした。条件を変更して再度お試しください。</p>';
            }
            
            html += '</div>';
            
            // アクションボタン
            html += '<div class="result-actions">';
            html += '<button class="btn-restart-diagnosis">もう一度診断する</button>';
            html += '<button class="btn-save-results">結果を保存</button>';
            html += '</div>';
            
            html += '</div>';
            
            // 結果を表示
            $('.ai-diagnosis-content').html(html);
            
            // 診断IDを保存
            if (data.diagnosis_id) {
                this.lastDiagnosisId = data.diagnosis_id;
            }
        },

        // フォールバック助成金表示
        showFallbackGrants: function(grants) {
            let html = '<div class="fallback-grants">';
            html += '<h3>人気の助成金</h3>';
            html += '<p>診断結果の代わりに、人気の助成金をご紹介します。</p>';
            
            grants.forEach(function(grant) {
                html += '<div class="grant-card">';
                html += '<h4><a href="' + grant.permalink + '">' + grant.title + '</a></h4>';
                html += '<p>' + grant.excerpt + '</p>';
                html += '</div>';
            });
            
            html += '</div>';
            
            $('.ai-diagnosis-fallback').html(html).show();
        },

        // 履歴表示
        showHistory: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: window.gi_ai_diagnosis.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_diagnosis_history',
                    nonce: window.gi_ai_diagnosis.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        this.displayHistory(response.data);
                    } else {
                        this.showMessage('診断履歴がありません。');
                    }
                }.bind(this),
                error: function() {
                    this.showError('履歴の取得に失敗しました。');
                }.bind(this)
            });
        },

        // 履歴表示UI
        displayHistory: function(history) {
            let html = '<div class="diagnosis-history">';
            html += '<h3>診断履歴</h3>';
            
            history.forEach(function(item) {
                html += '<div class="history-item">';
                html += '<div class="history-date">' + item.created_at + '</div>';
                html += '<div class="history-score">信頼度: ' + Math.round(item.confidence_score) + '%</div>';
                html += '<button class="btn-view-history" data-id="' + item.id + '">詳細を見る</button>';
                html += '</div>';
            });
            
            html += '</div>';
            
            $('#diagnosis-history-modal').html(html).fadeIn();
        },

        // ローディング表示
        showLoading: function() {
            $('.ai-diagnosis-loading').show();
            $('.ai-diagnosis-content').hide();
        },

        // ローディング非表示
        hideLoading: function() {
            $('.ai-diagnosis-loading').hide();
            $('.ai-diagnosis-content').show();
        },

        // エラー表示
        showError: function(message) {
            $('.ai-diagnosis-error').html('<p>' + message + '</p>').fadeIn();
            setTimeout(function() {
                $('.ai-diagnosis-error').fadeOut();
            }, 5000);
        },

        // メッセージ表示
        showMessage: function(message) {
            $('.ai-diagnosis-message').html('<p>' + message + '</p>').fadeIn();
            setTimeout(function() {
                $('.ai-diagnosis-message').fadeOut();
            }, 3000);
        }
    };

    // 初期化
    $(document).ready(function() {
        AIDiagnosis.init();
    });

})(jQuery);