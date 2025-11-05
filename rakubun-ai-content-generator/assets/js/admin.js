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
            alert('Please enter a prompt for your article.');
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
                        alert('Article generated and saved as draft post!');
                    }
                } else {
                    showError('#rakubun-article-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-article-loading').hide();
                $('#rakubun-generate-article-form button[type="submit"]').prop('disabled', false);
                showError('#rakubun-article-error', 'An error occurred. Please try again.');
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
            alert('Please enter a prompt for your image.');
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
                        alert('Image generated and saved to media library!');
                    }
                } else {
                    showError('#rakubun-image-error', response.data.message);
                }
            },
            error: function() {
                $('#rakubun-image-loading').hide();
                $('#rakubun-generate-image-form button[type="submit"]').prop('disabled', false);
                showError('#rakubun-image-error', 'An error occurred. Please try again.');
            }
        });
    }

    /**
     * Initiate Payment
     */
    window.rakubunInitiatePayment = function(type, amount) {
        if (!stripe) {
            alert('Payment system is not properly configured. Please contact the administrator.');
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
                showError('#rakubun-payment-error', 'Failed to create payment intent. Please try again.');
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
                showError('#rakubun-payment-error', 'Payment processing failed. Please try again.');
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
                alert('Content copied to clipboard!');
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
                alert('Content copied to clipboard!');
            } else {
                alert('Failed to copy content. Please copy manually.');
            }
        } catch (err) {
            console.error('Fallback: Failed to copy', err);
            alert('Failed to copy content. Please copy manually.');
        }
        
        document.body.removeChild(textArea);
    }

})(jQuery);
