<?php
/**
 * Novita AI Qwen image generation (async)
 */
class Rakubun_AI_Novita_Image {

    /**
     * Novita API key
     */
    private $api_key;

    /**
     * Constructor
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Generate image via Novita Qwen async API
     * Polls task result every $poll_interval seconds until success or timeout
     */
    public function generate_image($prompt, $size = '1024x1024', $poll_interval = 15, $max_wait_seconds = 180) {
        $prompt = trim((string) $prompt);
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'Novita API key is missing.'
            );
        }

        if (empty($prompt)) {
            return array(
                'success' => false,
                'error' => 'Prompt cannot be empty.'
            );
        }

        // Validate supported sizes (align with existing UI)
        $allowed_sizes = array('1024*1024', '1024*1792', '1792*1024');

        if (!in_array($size, $allowed_sizes, true)) {
            $size = '1024*1024';
        }

        $create_endpoint = 'https://api.novita.ai/v3/async/qwen-image-txt2img';

        $args_post = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent' => 'Rakubun-AI-WordPress-Plugin/1.0'
            ),
            'body' => json_encode(array(
                'prompt' => $prompt,
                'size' => $size
            )),
            'timeout' => 60,
            'method' => 'POST'
        );

        $create_response = wp_remote_post($create_endpoint, $args_post);

        if (is_wp_error($create_response)) {
            error_log('Novita API (create) error: ' . $create_response->get_error_message());
            return array(
                'success' => false,
                'error' => $create_response->get_error_message()
            );
        }

        $create_status = wp_remote_retrieve_response_code($create_response);
        $create_body = wp_remote_retrieve_body($create_response);

        if ($create_status < 200 || $create_status >= 300) {
            error_log('Novita API (create) non-2xx: ' . $create_status . ' body=' . $create_body);
            $err = json_decode($create_body, true);
            return array(
                'success' => false,
                'error' => isset($err['error']) ? (is_array($err['error']) ? (isset($err['error']['message']) ? $err['error']['message'] : 'API error') : $err['error']) : 'Failed to create Novita task.'
            );
        }

        $create_json = json_decode($create_body, true);
        // Task ID may be under task.task_id or task_id depending on API version
        $task_id = null;
        if (isset($create_json['task']['task_id'])) {
            $task_id = $create_json['task']['task_id'];
        } elseif (isset($create_json['task_id'])) {
            $task_id = $create_json['task_id'];
        }

        if (empty($task_id)) {
            error_log('Novita API: task_id not found in create response: ' . $create_body);
            return array(
                'success' => false,
                'error' => 'Novita task_id not found.'
            );
        }

        // Poll for result
        $deadline = time() + (int) $max_wait_seconds;
        $result_endpoint_base = 'https://api.novita.ai/v3/async/task-result?task_id=' . rawurlencode($task_id);

        do {
            $args_get = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'User-Agent' => 'Rakubun-AI-WordPress-Plugin/1.0'
                ),
                'timeout' => 60,
                'method' => 'GET'
            );

            $result_response = wp_remote_get($result_endpoint_base, $args_get);

            if (is_wp_error($result_response)) {
                error_log('Novita API (result) error: ' . $result_response->get_error_message());
                return array(
                    'success' => false,
                    'error' => $result_response->get_error_message()
                );
            }

            $result_status = wp_remote_retrieve_response_code($result_response);
            $result_body = wp_remote_retrieve_body($result_response);

            if ($result_status >= 200 && $result_status < 300) {
                $result_json = json_decode($result_body, true);

                $status = isset($result_json['task']['status']) ? $result_json['task']['status'] : null;

                if ($status === 'TASK_STATUS_SUCCEED') {
                    if (!empty($result_json['images']) && isset($result_json['images'][0]['image_url'])) {
                        return array(
                            'success' => true,
                            'url' => $result_json['images'][0]['image_url'],
                            'revised_prompt' => $prompt
                        );
                    }
                    return array(
                        'success' => false,
                        'error' => 'Novita returned success but without images.'
                    );
                }

                if ($status === 'TASK_STATUS_FAILED' || $status === 'TASK_STATUS_CANCELED') {
                    $reason = isset($result_json['task']['reason']) ? $result_json['task']['reason'] : 'Task failed.';
                    return array(
                        'success' => false,
                        'error' => $reason
                    );
                }

                // else still pending/processing; continue polling if time remains
            } else {
                // non-2xx result response; log and break if no time remains
                error_log('Novita API (result) non-2xx: ' . $result_status . ' body=' . $result_body);
            }

            if (time() + $poll_interval > $deadline) {
                break;
            }

            // Respect PHP max execution time; keep short sleeping intervals
            sleep((int) $poll_interval);
        } while (time() < $deadline);

        return array(
            'success' => false,
            'error' => 'Timed out waiting for Novita task to complete.'
        );
    }
}
