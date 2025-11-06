/**
 * Rakubun AI Content Generator Admin JavaScript
 */

(function($) {
    'use strict';

    let stripe = null;
    let cardElement = null;
    let currentPaymentType = null;
    let currentPaymentAmount = 0;

    // Initialize Stripe when document is ready
    $(document).ready(function() {
        // Initialize Stripe if on purchase page and connected
        if ($('#rakubun-payment-form').length) {
            // Check if plugin is connected to external dashboard
            if (rakubunAI.is_connected) {
                // For dashboard-managed payments, we still need to initialize Stripe
                // The public key will be used for card element creation
                if (rakubunAI.stripe_public_key) {
                    initializeStripe();
                } else {
                    // Fallback: Try to fetch Stripe config from dashboard or show warning
                    console.warn('Stripe public key not configured. Payments may not work correctly.');
                    console.log('Plugin is connected to dashboard. Payment intent will be created server-side.');
                    // Still try to initialize in case it's needed
                    initializeStripe();
                }
            } else {
                console.error('Plugin is not connected to dashboard. Please register in settings.');
            }
        }

        // Initialize dashboard features
        if ($('.rakubun-ai-dashboard').length) {
            initializeDashboard();
        }

        // Generate Article Form
        $('#rakubun-generate-article-form').on('submit', function(e) {
            e.preventDefault();
            generateArticle();
        });

        // Handle article length description updates
        $('#article_length').on('change', function() {
            updateLengthDescription($(this).val());
        });

        // Handle article tone description updates
        $('#article_tone').on('change', function() {
            updateToneDescription($(this).val());
        });

        // Initialize descriptions
        updateLengthDescription($('#article_length').val());
        updateToneDescription($('#article_tone').val());

        // Generate Image Form
        $('#rakubun-generate-image-form').on('submit', function(e) {
            e.preventDefault();
            generateImage();
        });
    });

/**
 * Initialize Stripe
 */
function initializeStripe() {
    if (typeof Stripe === 'undefined') {
        console.error('Stripe.js library is not loaded');
        return;
    }

    // If we don't have a Stripe public key, we cannot initialize
    if (!rakubunAI.stripe_public_key) {
        console.error('Stripe public key is not configured. Payment system cannot be initialized.');
        return;
    }

    try {
        stripe = Stripe(rakubunAI.stripe_public_key);
        const elements = stripe.elements();
        cardElement = elements.create('card');
        console.log('Stripe initialized successfully');
    } catch (error) {
        console.error('Error initializing Stripe:', error);
        stripe = null;
        cardElement = null;
    }
}

/**
 * Update length description based on selected value
 */
function updateLengthDescription(value) {
    const descriptions = {
        short: {
            title: '短い',
            text: '動画、インフォグラフィック、製品説明など、簡潔なコンテンツに最適です。'
        },
        medium: {
            title: '標準',
            text: 'バランスの取れた深さと読みやすさで、読者を引きつけ、適切な情報を提供します。'
        },
        long: {
            title: '長い',
            text: 'SEOランキングを上げるための包括的な記事。より多くのリードを生成します。'
        }
    };

    const desc = descriptions[value] || descriptions['medium'];
    $('#article_length_desc').html(
        '<p><strong style="color: #0073aa;">' + desc.title + '</strong></p>' +
        '<p>' + desc.text + '</p>'
    );
}

/**
 * Update tone description based on selected value
 */
function updateToneDescription(value) {
    const descriptions = {
        neutral: {
            title: 'ニュートラル',
            text: '客観的でバランスの取れた、事実に基づいたトーン。'
        },
        formal: {
            title: 'フォーマル',
            text: 'プロフェッショナルで正式な言語。企業やアカデミックなコンテンツに最適です。'
        },
        trustworthy: {
            title: '信頼性重視',
            text: '権威的で信頼できる、専門的な知識を示すトーン。'
        },
        friendly: {
            title: 'フレンドリー',
            text: '親しみやすく会話的なトーン。読者を親しく引きつけます。'
        },
        witty: {
            title: 'ユーモア',
            text: '会話的で楽しく、ユーモアを交えたトーン。'
        }
    };

    const desc = descriptions[value] || descriptions['neutral'];
    $('#article_tone_desc').html(
        '<p><strong style="color: #0073aa;">' + desc.title + '</strong></p>' +
        '<p>' + desc.text + '</p>'
    );
}

    /**
     * Generate Article
     */
    function generateArticle() {
        const title = $('#article_title').val();
        const prompt = $('#article_prompt').val();
        const language = $('#article_language').val();
        const contentLength = $('#article_length').val();
        const tone = $('#article_tone').val();
        const focusKeywords = $('#article_keywords').val();
        const createPost = $('#create_post').is(':checked');
        const generateTags = $('#generate_tags').is(':checked');
        const categories = $('#article_categories').val() || [];

        if (!prompt) {
            alert('記事のプロンプトを入力してください。');
            return;
        }

        // Show loading
        $('#rakubun-article-result').hide();
        $('#rakubun-article-error').hide();
        $('#rakubun-article-loading').show();
        $('#rakubun-generate-article-form button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_generate_article',
                nonce: rakubunAI.nonce,
                title: title,
                prompt: prompt,
                language: language,
                content_length: contentLength,
                tone: tone,
                focus_keywords: focusKeywords,
                create_post: createPost,
                generate_tags: generateTags,
                categories: categories
            },
            success: function(response) {
                $('#rakubun-article-loading').hide();
                $('#rakubun-generate-article-form button[type="submit"]').prop('disabled', false);

                if (response.success) {
                    // Display title if available
                    if (response.data.title) {
                        $('#rakubun-article-title').html('<h3 style="margin: 0; color: #667eea; font-size: 1.5rem;">' + escapeHtml(response.data.title) + '</h3>');
                    }
                    
                    $('#rakubun-article-content').html(formatArticleContent(response.data.content));
                    $('#rakubun-article-result').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);
                    
                    // Scroll to result
                    $('html, body').animate({
                        scrollTop: $('#rakubun-article-result').offset().top - 100
                    }, 800);
                } else {
                    showError('#rakubun-article-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-article-loading').hide();
                $('#rakubun-generate-article-form button[type="submit"]').prop('disabled', false);
                showError('#rakubun-article-error', 'エラーが発生しました。もう一度お試しください。');
            }
        });
    }

    /**
     * Generate Image
     */
    function generateImage() {
        const prompt = $('#image_prompt').val();
        const size = $('#image_size').val();
        const saveToMedia = $('#save_to_media').is(':checked');

        if (!prompt) {
            alert('画像のプロンプトを入力してください。');
            return;
        }

        // Show loading
        $('#rakubun-image-result').hide();
        $('#rakubun-image-error').hide();
        $('#rakubun-image-loading').show();
        $('#rakubun-generate-image-form button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_generate_image',
                nonce: rakubunAI.nonce,
                prompt: prompt,
                size: size,
                save_to_media: saveToMedia
            },
            success: function(response) {
                $('#rakubun-image-loading').hide();
                $('#rakubun-generate-image-form button[type="submit"]').prop('disabled', false);

                if (response.success) {
                    $('#rakubun-image-preview').html('<img src="' + response.data.url + '" alt="Generated Image">');
                    $('#rakubun-image-download').attr('href', response.data.url);
                    $('#rakubun-image-result').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Show success message if saved to media
                    if (response.data.attachment_id) {
                        alert('画像が生成され、メディアライブラリに保存されました！');
                    }
                } else {
                    showError('#rakubun-image-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-image-loading').hide();
                $('#rakubun-generate-image-form button[type="submit"]').prop('disabled', false);
                showError('#rakubun-image-error', 'エラーが発生しました。もう一度お試しください。');
            }
        });
    }

    /**
     * Initiate Payment - Redirect to Stripe Checkout
     */
    window.rakubunInitiatePayment = function(packageId, amount) {
        if (!rakubunAI.is_connected) {
            alert('プラグインがダッシュボードに接続されていません。');
            return;
        }

        currentPaymentType = packageId;
        currentPaymentAmount = amount;

        // Show checkout container and hide all pricing sections
        $('.rakubun-pricing').hide();
        $('.rakubun-rewrite-packages').hide();
        $('.rakubun-pricing-navigation').hide();
        $('#rakubun-checkout-container').show();
        
        // Scroll to checkout
        $('html, body').animate({
            scrollTop: $('#rakubun-checkout-container').offset().top - 100
        }, 300);
        
        // Setup checkout button
        $('#rakubun-checkout-button').off('click').on('click', function() {
            initiateStripeCheckout(packageId, amount);
        });
    };

    /**
     * Cancel Checkout
     */
    window.rakubunCancelCheckout = function() {
        $('#rakubun-checkout-container').hide();
        $('.rakubun-pricing').show();
        $('.rakubun-rewrite-packages').show();
        $('.rakubun-pricing-navigation').show();
        
        // Scroll back to top
        $('html, body').animate({
            scrollTop: 0
        }, 300);
    };

    /**
     * Initiate Stripe Checkout Session
     */
    function initiateStripeCheckout(packageId, amount) {
        $('#rakubun-checkout-button').prop('disabled', true);
        $('#rakubun-payment-loading').show();

        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_create_checkout_session',
                nonce: rakubunAI.nonce,
                package_id: packageId,
                amount: amount
            },
            success: function(response) {
                if (response.success && response.data.checkout_url) {
                    // Redirect to Stripe Checkout
                    window.location.href = response.data.checkout_url;
                } else {
                    $('#rakubun-payment-loading').hide();
                    $('#rakubun-checkout-button').prop('disabled', false);
                    showError('#rakubun-payment-error', response.data?.message || 'チェックアウトセッションの作成に失敗しました。');
                    $('#rakubun-payment-error').show();
                }
            },
            error: function(xhr, status, error) {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-checkout-button').prop('disabled', false);
                showError('#rakubun-payment-error', 'エラーが発生しました。もう一度お試しください。');
                $('#rakubun-payment-error').show();
                console.error('Checkout error:', error);
            }
        });
    }

    /**
     * Confirm Payment with Stripe
     */
    function confirmPayment(clientSecret, paymentMethodId, paymentIntentId) {
        stripe.confirmCardPayment(clientSecret, {
            payment_method: paymentMethodId
        }).then(function(result) {
            if (result.error) {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-payment-submit').prop('disabled', false);
                showError('#rakubun-payment-error', result.error.message);
            } else {
                // Payment succeeded, process on server
                processPaymentSuccess(paymentIntentId);
            }
        });
    }

    /**
     * Process successful payment
     */
    function processPaymentSuccess(paymentIntentId) {
        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_process_payment',
                nonce: rakubunAI.nonce,
                credit_type: currentPaymentType,
                payment_intent_id: paymentIntentId
            },
            success: function(response) {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-payment-submit').prop('disabled', false);

                if (response.success) {
                    $('#rakubun-payment-form').hide();
                    $('#rakubun-payment-success').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Reset after 3 seconds
                    setTimeout(function() {
                        $('#rakubun-payment-success').hide();
                        $('.rakubun-pricing').show();
                    }, 3000);
                } else {
                    showError('#rakubun-payment-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-payment-submit').prop('disabled', false);
                showError('#rakubun-payment-error', '決済処理に失敗しました。もう一度お試しください。');
            }
        });
    }

    /**
     * Escape HTML special characters
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Update Credits Display
     */
    function updateCreditsDisplay(credits) {
        $('.credits-count').text(credits.article_credits);
        $('.credits-count-articles').text(credits.article_credits);
        $('.credits-count-images').text(credits.image_credits);
    }

    /**
     * Show Error Message
     */
    function showError(selector, message) {
        $(selector).find('#rakubun-error-message').text(message);
        $(selector).show();
        
        // Scroll to error
        $('html, body').animate({
            scrollTop: $(selector).offset().top - 100
        }, 800);
    }

    /**
     * Format Article Content
     */
    function formatArticleContent(content) {
        // Content is already formatted as HTML from backend markdown conversion
        // Just ensure it's properly displayed
        return content;
    }

    /**
     * Copy Content to Clipboard
     */
    window.rakubunCopyContent = function(elementId) {
        const element = document.getElementById(elementId);
        const text = element.innerText;
        
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                alert('コンテンツがクリップボードにコピーされました！');
            }).catch(function(err) {
                console.error('Failed to copy text: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // Fallback for older browsers or HTTP
            fallbackCopyTextToClipboard(text);
        }
    };

    /**
     * Fallback copy method for older browsers
     */
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = '0';
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                alert('コンテンツがクリップボードにコピーされました！');
            } else {
                alert('コピーに失敗しました。手動でコピーしてください。');
            }
        } catch (err) {
            console.error('Fallback: Failed to copy', err);
            alert('コピーに失敗しました。手動でコピーしてください。');
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * Dashboard Gallery and Analytics Functions
     */

    function initializeDashboard() {
        // Image gallery interactions - use event delegation for dynamically loaded content
        $(document).on('click', '.btn-regenerate', function() {
            const prompt = $(this).data('prompt');
            openRegenerationModal(prompt);
        });

        $(document).on('click', '.btn-view-full', function() {
            const imageUrl = $(this).data('url');
            openImageViewer(imageUrl);
        });

        // Modal interactions
        $(document).on('click', '.modal-close, .viewer-close', function() {
            closeModals();
        });

        // Close modals on outside click
        $(document).on('click', '.regeneration-modal, .image-viewer-modal', function(e) {
            if (e.target === this) {
                closeModals();
            }
        });

        // Regeneration form submission
        $(document).on('submit', '#regenerationForm', function(e) {
            e.preventDefault();
            regenerateImage();
        });

        // Keyboard shortcuts for modals
        $(document).on('keydown', function(e) {
            // Escape key to close modals
            if (e.key === 'Escape') {
                closeModals();
            }
            
            // Space or Enter to toggle zoom in image viewer
            if ($('#imageViewerModal').is(':visible')) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    $('#viewerImage').toggleClass('zoomed');
                }
                
                // Arrow keys to navigate between images (future feature)
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    // Could implement next/previous image navigation here
                    console.log('Arrow key navigation - feature for future implementation');
                }
            }
        });

        // Analytics refresh (optional periodic update)
        setInterval(refreshAnalytics, 300000); // Refresh every 5 minutes
        
        // Initialize gallery animations if on dashboard
        if ($('.gallery-grid').length) {
            addGalleryAnimations();
        }
    }

    /**
     * Open regeneration modal
     */
    function openRegenerationModal(prompt) {
        $('#regenerate-prompt').val(prompt);
        $('#regenerationModal').fadeIn(300);
    }

    /**
     * Open Image Viewer
     */
    function openImageViewer(imageUrl) {
        $('#viewerImage').attr('src', imageUrl);
        $('#imageViewerModal').fadeIn(300);
        
        // Reset zoom state
        $('#viewerImage').removeClass('zoomed');
        
        // Add zoom functionality
        $('#viewerImage').off('click').on('click', function() {
            $(this).toggleClass('zoomed');
        });
        
        // Prevent body scroll when modal is open
        $('body').css('overflow', 'hidden');
        
        // Auto-hide instructions after 3 seconds
        setTimeout(function() {
            $('.viewer-instructions').fadeOut(500);
        }, 3000);
        
        // Show instructions again on mouse move
        $(document).on('mousemove.viewer', function() {
            $('.viewer-instructions').fadeIn(300);
            clearTimeout(window.instructionTimer);
            window.instructionTimer = setTimeout(function() {
                $('.viewer-instructions').fadeOut(500);
            }, 2000);
        });
    }

    /**
     * Close all modals
     */
    function closeModals() {
        $('.regeneration-modal, .image-viewer-modal').fadeOut(300);
        
        // Reset zoom and restore body scroll
        $('#viewerImage').removeClass('zoomed');
        $('body').css('overflow', 'auto');
        
        // Clean up event listeners
        $(document).off('mousemove.viewer');
        clearTimeout(window.instructionTimer);
        
        // Reset instructions visibility
        $('.viewer-instructions').show();
    }

    /**
     * Regenerate image with new parameters
     */
    function regenerateImage() {
        const prompt = $('#regenerate-prompt').val();
        const size = $('#regenerate-size').val();
        const $form = $('#regenerationForm');
        const $submitBtn = $form.find('button[type="submit"]');
        const $btnText = $submitBtn.find('.btn-text');
        const $btnLoading = $submitBtn.find('.btn-loading');

        if (!prompt.trim()) {
            alert('プロンプトを入力してください。');
            return;
        }

        // Show loading state
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();

        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_regenerate_image',
                nonce: rakubunAI.nonce,
                prompt: prompt,
                size: size
            },
            success: function(response) {
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();

                if (response.success) {
                    // Show success message
                    alert(response.data.message);
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);
                    
                    // Close modal
                    closeModals();
                    
                    // Refresh gallery
                    refreshGallery();
                    
                    // Refresh analytics
                    refreshAnalytics();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
                alert('エラーが発生しました。再試行してください。');
            }
        });
    }

    /**
     * Refresh gallery with new images
     */
    function refreshGallery() {
        // In a full implementation, you might want to reload the gallery section
        // For now, we'll just reload the page to show the new image
        setTimeout(function() {
            location.reload();
        }, 1000);
    }

    /**
     * Refresh analytics data
     */
    function refreshAnalytics() {
        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_get_analytics',
                nonce: rakubunAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateAnalyticsDisplay(response.data.analytics);
                }
            },
            error: function() {
                console.log('Failed to refresh analytics');
            }
        });
    }

    /**
     * Update analytics display with new data
     */
    function updateAnalyticsDisplay(analytics) {
        // Update analytics cards
        $('.analytics-card').each(function() {
            const $card = $(this);
            const $icon = $card.find('.card-icon');
            
            if ($icon.text() === '📈') {
                // Total articles
                $card.find('h3').text(analytics.total_articles);
                $card.find('.recent-activity').text('過去7日間: ' + analytics.recent_articles + '件');
            } else if ($icon.text() === '🎨') {
                // Total images
                $card.find('h3').text(analytics.total_images);
                $card.find('.recent-activity').text('過去7日間: ' + analytics.recent_images + '件');
            } else if ($icon.text() === '💰') {
                // Total spent
                $card.find('h3').text('¥' + Number(analytics.total_spent || 0).toLocaleString());
            } else if ($icon.text() === '⚡') {
                // Weekly activity
                $card.find('h3').text(analytics.recent_articles + analytics.recent_images);
            }
        });
    }

    /**
     * Enhanced gallery item animations
     */
    function addGalleryAnimations() {
        $('.gallery-item').each(function(index) {
            $(this).css('animation-delay', (index * 100) + 'ms');
            $(this).addClass('fade-in');
        });
    }

    /**
     * Chart interactions
     */
    $(document).on('mouseenter', '.bar', function() {
        const title = $(this).attr('title');
        // You could implement a tooltip here
        console.log(title);
    });

})(jQuery);

/**
 * Additional CSS animations for enhanced UX
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in {
        animation: fadeIn 0.6s ease forwards;
        opacity: 0;
    }
    
    .chart-bar:hover .bar {
        filter: brightness(1.2);
    }
    
    .analytics-card:hover .card-icon {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }
`;
document.head.appendChild(style);
