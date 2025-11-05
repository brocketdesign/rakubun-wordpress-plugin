/**
 * Rakurabu AI Content Generator Admin JavaScript
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
        if ($('#rakurabu-payment-form').length && rakurabuAI.stripe_public_key) {
            initializeStripe();
        }

        // Generate Article Form
        $('#rakurabu-generate-article-form').on('submit', function(e) {
            e.preventDefault();
            generateArticle();
        });

        // Generate Image Form
        $('#rakurabu-generate-image-form').on('submit', function(e) {
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
            stripe = Stripe(rakurabuAI.stripe_public_key);
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
        $('#rakurabu-article-result').hide();
        $('#rakurabu-article-error').hide();
        $('#rakurabu-article-loading').show();
        $('#rakurabu-generate-article-form button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: rakurabuAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakurabu_generate_article',
                nonce: rakurabuAI.nonce,
                title: title,
                prompt: prompt,
                create_post: createPost
            },
            success: function(response) {
                $('#rakurabu-article-loading').hide();
                $('#rakurabu-generate-article-form button[type="submit"]').prop('disabled', false);

                if (response.success) {
                    $('#rakurabu-article-content').html(formatArticleContent(response.data.content));
                    $('#rakurabu-article-result').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Show success message if post was created
                    if (response.data.post_id) {
                        alert('Article generated and saved as draft post!');
                    }
                } else {
                    showError('#rakurabu-article-error', response.data.message);
                }
            },
            error: function() {
                $('#rakurabu-article-loading').hide();
                $('#rakurabu-generate-article-form button[type="submit"]').prop('disabled', false);
                showError('#rakurabu-article-error', 'An error occurred. Please try again.');
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
        $('#rakurabu-image-result').hide();
        $('#rakurabu-image-error').hide();
        $('#rakurabu-image-loading').show();
        $('#rakurabu-generate-image-form button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: rakurabuAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakurabu_generate_image',
                nonce: rakurabuAI.nonce,
                prompt: prompt,
                size: size,
                save_to_media: saveToMedia
            },
            success: function(response) {
                $('#rakurabu-image-loading').hide();
                $('#rakurabu-generate-image-form button[type="submit"]').prop('disabled', false);

                if (response.success) {
                    $('#rakurabu-image-preview').html('<img src="' + response.data.url + '" alt="Generated Image">');
                    $('#rakurabu-image-download').attr('href', response.data.url);
                    $('#rakurabu-image-result').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Show success message if saved to media
                    if (response.data.attachment_id) {
                        alert('Image generated and saved to media library!');
                    }
                } else {
                    showError('#rakurabu-image-error', response.data.message);
                }
            },
            error: function() {
                $('#rakurabu-image-loading').hide();
                $('#rakurabu-generate-image-form button[type="submit"]').prop('disabled', false);
                showError('#rakurabu-image-error', 'An error occurred. Please try again.');
            }
        });
    }

    /**
     * Initiate Payment
     */
    window.rakurabuInitiatePayment = function(type, amount) {
        if (!stripe) {
            alert('Payment system is not properly configured. Please contact the administrator.');
            return;
        }

        currentPaymentType = type;
        currentPaymentAmount = amount;

        // Show payment form
        $('.rakurabu-pricing').hide();
        $('#rakurabu-payment-form').show();
        
        // Mount card element if not already mounted
        if (cardElement && !cardElement._mounted) {
            cardElement.mount('#rakurabu-stripe-card-element');
            cardElement._mounted = true;
        }

        // Setup payment button
        $('#rakurabu-payment-submit').off('click').on('click', processPayment);
    };

    /**
     * Cancel Payment
     */
    window.rakurabuCancelPayment = function() {
        $('#rakurabu-payment-form').hide();
        $('.rakurabu-pricing').show();
        $('#rakurabu-card-errors').hide();
    };

    /**
     * Process Payment
     */
    function processPayment() {
        $('#rakurabu-payment-loading').show();
        $('#rakurabu-payment-submit').prop('disabled', true);

        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
        }).then(function(result) {
            if (result.error) {
                $('#rakurabu-payment-loading').hide();
                $('#rakurabu-payment-submit').prop('disabled', false);
                showError('#rakurabu-card-errors', result.error.message);
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
        // For simplicity, we'll simulate the payment process
        // In production, you should create a proper payment intent on the server
        
        // Simulate payment verification
        $.ajax({
            url: rakurabuAI.ajaxurl,
            type: 'POST',
            data: {
                action: 'rakurabu_process_payment',
                nonce: rakurabuAI.nonce,
                credit_type: currentPaymentType,
                payment_intent_id: 'pi_' + Math.random().toString(36).substr(2, 9) // Mock payment ID
            },
            success: function(response) {
                $('#rakurabu-payment-loading').hide();
                $('#rakurabu-payment-submit').prop('disabled', false);

                if (response.success) {
                    $('#rakurabu-payment-form').hide();
                    $('#rakurabu-payment-success').show();
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits);

                    // Reset after 3 seconds
                    setTimeout(function() {
                        $('#rakurabu-payment-success').hide();
                        $('.rakurabu-pricing').show();
                    }, 3000);
                } else {
                    showError('#rakurabu-payment-error', response.data.message);
                }
            },
            error: function() {
                $('#rakurabu-payment-loading').hide();
                $('#rakurabu-payment-submit').prop('disabled', false);
                showError('#rakurabu-payment-error', 'Payment processing failed. Please try again.');
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
    window.rakurabuCopyContent = function(elementId) {
        const element = document.getElementById(elementId);
        const text = element.innerText;
        
        navigator.clipboard.writeText(text).then(function() {
            alert('Content copied to clipboard!');
        }).catch(function(err) {
            console.error('Failed to copy text: ', err);
        });
    };

})(jQuery);
