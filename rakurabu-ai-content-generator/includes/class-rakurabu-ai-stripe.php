<?php
/**
 * Stripe payment integration
 */
class Rakurabu_AI_Stripe {

    /**
     * Stripe secret key
     */
    private $secret_key;

    /**
     * Stripe API base URL
     */
    private $api_base = 'https://api.stripe.com/v1';

    /**
     * Constructor
     */
    public function __construct() {
        $this->secret_key = get_option('rakurabu_ai_stripe_secret_key', '');
    }

    /**
     * Create payment intent
     */
    public function create_payment_intent($amount, $currency = 'usd', $metadata = array()) {
        if (empty($this->secret_key)) {
            return array(
                'success' => false,
                'error' => 'Stripe secret key is not configured.'
            );
        }

        $endpoint = $this->api_base . '/payment_intents';
        
        $data = array(
            'amount' => $amount * 100, // Convert to cents
            'currency' => $currency,
            'metadata' => $metadata
        );

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['client_secret'])) {
            return array(
                'success' => true,
                'client_secret' => $body['client_secret'],
                'payment_intent_id' => $body['id']
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to create payment intent.'
        );
    }

    /**
     * Verify payment
     */
    public function verify_payment($payment_intent_id) {
        if (empty($this->secret_key)) {
            return array(
                'success' => false,
                'error' => 'Stripe secret key is not configured.'
            );
        }

        $endpoint = $this->api_base . '/payment_intents/' . $payment_intent_id;
        
        $response = $this->make_request($endpoint, array(), 'GET');

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['status']) && $body['status'] === 'succeeded') {
            return array(
                'success' => true,
                'payment' => $body
            );
        }

        return array(
            'success' => false,
            'error' => 'Payment verification failed.',
            'status' => isset($body['status']) ? $body['status'] : 'unknown'
        );
    }

    /**
     * Create checkout session
     */
    public function create_checkout_session($line_items, $success_url, $cancel_url, $metadata = array()) {
        if (empty($this->secret_key)) {
            return array(
                'success' => false,
                'error' => 'Stripe secret key is not configured.'
            );
        }

        $endpoint = $this->api_base . '/checkout/sessions';
        
        $data = array(
            'mode' => 'payment',
            'line_items' => $line_items,
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => $metadata
        );

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['id'])) {
            return array(
                'success' => true,
                'session_id' => $body['id'],
                'url' => isset($body['url']) ? $body['url'] : ''
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to create checkout session.'
        );
    }

    /**
     * Make API request to Stripe
     */
    private function make_request($endpoint, $data, $method = 'POST') {
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'timeout' => 30
        );

        if ($method === 'POST') {
            $args['body'] = http_build_query($data);
            $response = wp_remote_post($endpoint, $args);
        } else {
            $response = wp_remote_get($endpoint, $args);
        }

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $error_body = json_decode($body, true);
            $error_message = isset($error_body['error']['message']) 
                ? $error_body['error']['message'] 
                : 'API request failed with status code: ' . $status_code;
            
            return array(
                'success' => false,
                'error' => $error_message
            );
        }

        return array(
            'success' => true,
            'body' => $body
        );
    }
}
