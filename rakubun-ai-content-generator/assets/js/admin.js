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
        // Initialize Stripe if on purchase page
        if ($('#rakubun-payment-form').length && rakubunAI.stripe_public_key) {
            initializeStripe();
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
            console.error('Stripe.js not loaded');
            return;
        }

        try {
            stripe = Stripe(rakubunAI.stripe_public_key);
            const elements = stripe.elements();
            cardElement = elements.create('card');
        } catch (error) {
            console.error('Error initializing Stripe:', error);
        }
    }

    /**
     * Generate Article
     */
    function generateArticle() {
        const title = $('#article_title').val();
        const prompt = $('#article_prompt').val();
        const createPost = $('#create_post').is(':checked');

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
                create_post: createPost
            },
            success: function(response) {
                $('#rakubun-article-loading').hide();
                $('#rakubun-generate-article-form button[type="submit"]').prop('disabled', false);

                if (response.success) {
                    $('#rakubun-article-content').html(formatArticleContent(response.data.content));
                    $('#rakubun-article-result').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Show success message if post was created
                    if (response.data.post_id) {
                        alert('記事が生成され、下書きとして保存されました！');
                    }
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
     * Initiate Payment
     */
    window.rakubunInitiatePayment = function(type, amount) {
        if (!stripe) {
            alert('決済システムが正しく設定されていません。管理者にお問い合わせください。');
            return;
        }

        currentPaymentType = type;
        currentPaymentAmount = amount;

        // Show payment form
        $('.rakubun-pricing').hide();
        $('#rakubun-payment-form').show();
        
        // Mount card element if not already mounted
        if (cardElement && !cardElement._mounted) {
            cardElement.mount('#rakubun-stripe-card-element');
            cardElement._mounted = true;
        }

        // Setup payment button
        $('#rakubun-payment-submit').off('click').on('click', processPayment);
    };

    /**
     * Cancel Payment
     */
    window.rakubunCancelPayment = function() {
        $('#rakubun-payment-form').hide();
        $('.rakubun-pricing').show();
        $('#rakubun-card-errors').hide();
    };

    /**
     * Process Payment
     */
    function processPayment() {
        $('#rakubun-payment-loading').show();
        $('#rakubun-payment-submit').prop('disabled', true);

        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
        }).then(function(result) {
            if (result.error) {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-payment-submit').prop('disabled', false);
                showError('#rakubun-card-errors', result.error.message);
            } else {
                // Create payment intent via backend
                createPaymentIntent(result.paymentMethod.id);
            }
        });
    }

    /**
     * Create Payment Intent
     */
    function createPaymentIntent(paymentMethodId) {
        // First, create a payment intent on the server
        $.ajax({
            url: rakubunAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakubun_create_payment_intent',
                nonce: rakubunAI.nonce,
                credit_type: currentPaymentType
            },
            success: function(response) {
                if (response.success) {
                    // Confirm the payment with Stripe
                    confirmPayment(response.data.client_secret, paymentMethodId, response.data.payment_intent_id);
                } else {
                    $('#rakubun-payment-loading').hide();
                    $('#rakubun-payment-submit').prop('disabled', false);
                    showError('#rakubun-payment-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-payment-loading').hide();
                $('#rakubun-payment-submit').prop('disabled', false);
                showError('#rakubun-payment-error', '決済の作成に失敗しました。もう一度お試しください。');
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
        $(selector).find('p').text(message);
        $(selector).show();
    }

    /**
     * Format Article Content
     */
    function formatArticleContent(content) {
        // Convert markdown-style formatting to HTML
        content = content.replace(/\n\n/g, '</p><p>');
        content = content.replace(/\n/g, '<br>');
        return '<p>' + content + '</p>';
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
