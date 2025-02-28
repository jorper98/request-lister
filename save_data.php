<?php
/* save_data.php
 * part of Plugin Name: RequestLister
 * Author: Jorge Pereira
 * Author URI: http://jorgep.com/plugins
 * 
 */

$i = 0;
$max_attempts = 10;

while (!file_exists($wp_load_path = dirname(__FILE__) . str_repeat('/..', $i) . '/wp-load.php')) {
    $i++;
    if ($i > $max_attempts) {
        die('wp-load.php not found after ' . $max_attempts . ' attempts.');
    }
}

require_once($wp_load_path);

wp_debug_mode(false);

function sanitize_input($input) {
    $input = sanitize_text_field($input);
    return str_replace(',', ' ', $input);
}

function save_data_to_file($data_file, $data) {
    $data_folder = dirname($data_file);
    if (!file_exists($data_folder)) {
        if (!mkdir($data_folder, 0755, true)) {
            return false;
        }
    }
    if (!is_writable($data_folder)) {
        return false;
    }
    $result = file_put_contents($data_file, $data);
    return ($result !== false);
}

// Debug console logging
if (WP_DEBUG && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $post_data_json = json_encode($_POST, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
   // echo '<script>console.log("Incoming POST Data:", ' . $post_data_json . ');</script>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], '35rl_form_nonce')) {
        echo '<p>Security check failed. Please try again.</p>';
        return;
    }

    $email = sanitize_email($_POST['email']);
    if (!is_email($email)) {
        echo '<p>Invalid email format. Please try again.</p>';
        return;
    }

    $data_file_name = isset($_POST['data_file']) ? sanitize_text_field($_POST['data_file']) : 'sampledata.txt';
    $data_file = dirname(__FILE__) . '/data/' . basename($data_file_name);
    $fields = isset($_POST['fields']) ? json_decode(stripslashes($_POST['fields']), true) : array();
    $new_values = array();
    $date_time = date('Y-m-d H:i:s');
    $lines = file_exists($data_file) ? file($data_file, FILE_IGNORE_NEW_LINES) : array();
    $new_lines = array();

    foreach ($fields as $field_name) {
        if (preg_match('/\{.*?\}/', $field_name, $matches)) {
            $field_name = preg_replace('/\{.*?\}/', '', $field_name);
        }

        if (isset($_POST[$field_name])) {
            $new_values[$field_name] = sanitize_input($_POST[$field_name]);
        } else {
            $new_values[$field_name] = '';
        }
    }

    $data_parts = array();
    foreach ($fields as $field_name) {
        if (preg_match('/\{.*?\}/', $field_name, $matches)) {
            $field_name = preg_replace('/\{.*?\}/', '', $field_name);
        }

        $data_parts[] = isset($new_values[$field_name]) ? $new_values[$field_name] : '';
    }
    $data_parts[] = $email;
    $data_parts[] = $date_time;
    $new_data_line = implode(', ', $data_parts);

    $updated = false;
    foreach ($lines as $line) {
        if (empty($line)) {
            continue;
        }

        $line_data = explode(', ', $line);
        $email_pos = count($line_data) - 2;

        if ($email_pos >= 0 && $line_data[$email_pos] == $email) {
            $new_lines[] = $new_data_line;
            $updated = true;
        } else {
            $new_lines[] = $line;
        }
    }

    if (!$updated) {
        $new_lines[] = $new_data_line;
    }

    save_data_to_file($data_file, implode("\n", $new_lines));

    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
    error_log("Redirect URL: " . $redirect_url);

    header('Location: ' . $redirect_url);
    exit;
} else {
    echo "Invalid request.";
}
?>
