<?php
/**
 * Debug script for testing OpenAI Image API
 * Remove this file after debugging
 */

// Load WordPress
require_once('../../../wp-config.php');

// Get API key from options
$api_key = get_option('rakubun_ai_openai_api_key', '');

if (empty($api_key)) {
    die('Error: OpenAI API key not configured in WordPress settings.');
}

// Test the API directly
$endpoint = 'https://api.openai.com/v1/images/generations';

$data = array(
    'model' => 'dall-e-3',
    'prompt' => 'A beautiful sunset over mountains with a lake in the foreground',
    'n' => 1,
    'size' => '1024x1024',
    'quality' => 'standard',
    'response_format' => 'url'
);

$args = array(
    'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
        'User-Agent' => 'Rakubun-AI-WordPress-Plugin/1.0'
    ),
    'body' => json_encode($data),
    'timeout' => 120,
    'method' => 'POST'
);

echo "<h1>Debug: OpenAI Image API Test</h1>\n";
echo "<h2>Request Data:</h2>\n";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>\n";

$response = wp_remote_post($endpoint, $args);

if (is_wp_error($response)) {
    echo "<h2>WordPress Error:</h2>\n";
    echo "<p>" . $response->get_error_message() . "</p>\n";
    exit;
}

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "<h2>Response Status Code:</h2>\n";
echo "<p>" . $status_code . "</p>\n";

echo "<h2>Response Body:</h2>\n";
echo "<pre>" . htmlspecialchars($body) . "</pre>\n";

if ($status_code === 200) {
    $response_data = json_decode($body, true);
    if (isset($response_data['data'][0]['url'])) {
        echo "<h2>Generated Image:</h2>\n";
        echo '<img src="' . htmlspecialchars($response_data['data'][0]['url']) . '" style="max-width: 512px;" alt="Generated Image">';
    }
} else {
    $error_data = json_decode($body, true);
    if (isset($error_data['error']['message'])) {
        echo "<h2>API Error Message:</h2>\n";
        echo "<p>" . htmlspecialchars($error_data['error']['message']) . "</p>\n";
    }
}

echo "\n<p><strong>Note:</strong> Delete this file after debugging!</p>\n";
?>