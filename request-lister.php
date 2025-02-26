<?php
/*
 * Plugin Name: RequestLister
 * Description: A simple plugin to capture multiple fields including name and email, save to a text file, and display the list of names.
 * Version: 1.2.5
 * Author: Jorge Pereira 
 * Author URI:   http://jorgep.com/plugins
 * License:      GPLv2 or later
 */

function sanitize_input($input) {
    $input = sanitize_text_field($input); // Uses WordPress's built-in function
    return str_replace(',', ' ', $input); // Remove commas replace with Blanks
}

function save_data_to_file($data_file, $data) {
    $data_folder = dirname($data_file);
    if (!file_exists($data_folder)) {
        mkdir($data_folder, 0755, true);
    }
    file_put_contents($data_file, $data);
}

function update_output_message($fields, $old_values, $new_values, $updated) {
    $output = '';
    if ($updated) {
        $output .= '<p>Your request has been updated. Thank you!</p>';
        foreach ($fields as $field) {
            $field = trim($field);
            $output .= '<p>Old ' . ucfirst($field) . ': ' . esc_html($old_values[$field]) . '</p>';
            $output .= '<p>New ' . ucfirst($field) . ': ' . esc_html($new_values[$field]) . '</p>';
        }
    } else {
        $output .= '<p>Your request has been added. Thank you!</p>';
    }
    return $output;
}

function display_35rl_form($atts) {
    $atts = shortcode_atts(array(
        'fields' => 'name',
        'data_file' => 'sampledata.txt',
    ), $atts);

    $fields = explode(',', $atts['fields']);
    $data_file = plugin_dir_path(__FILE__) . 'data/' . basename($atts['data_file']);
    $output = '';
    $updated = false;
    $old_values = array();
    $new_values = array();
    $date_time = date('Y-m-d H:i:s'); // Get current date and time
    $is_admin = current_user_can('manage_options');

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], '35rl_form_nonce')) {
            $output .= '<p>Security check failed. Please try again.</p>';
            return $output;
        }

        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            $output .= '<p>Invalid email format. Please try again.</p>';
            return $output;
        }

        $lines = file_exists($data_file) ? file($data_file, FILE_IGNORE_NEW_LINES) : array();
        $new_lines = array();
        $data = '';

        foreach ($fields as $field) {
            $field = trim($field);
            if (isset($_POST[$field])) {
                $new_values[$field] = sanitize_input($_POST[$field]);
                $data .= $new_values[$field] . ', ';
            }
        }
        $data .= $email . ', ' . $date_time . "\n";

        foreach ($lines as $index => $line) {
            $line_data = explode(', ', $line);
            if (!empty($line) && $line_data[count($line_data) - 3] != $email) {
                $new_lines[] = $line;
            } elseif ($line_data[count($line_data) - 3] == $email) {
                foreach ($fields as $field) {
                    $field = trim($field);
                    $old_values[$field] = $line_data[array_search($field, $fields)];
                }
                $old_values['email'] = $email;
                $new_lines[] = implode(', ', $new_values) . ', ' . $email . ', ' . $date_time;
                $updated = true;
            }
        }

        if (!$updated) {
            $new_lines[] = $data;
        }

        save_data_to_file($data_file, implode("\n", $new_lines));
        $output .= update_output_message($fields, $old_values, $new_values, $updated);
    } else {
        $output .= '<form method="post">';
        foreach ($fields as $field) {
            $field = trim($field);
            $output .= '<label for="' . $field . '">' . ucfirst($field) . ':</label>';
            $output .= '<input type="text" id="' . $field . '" name="' . $field . '" required><br>';
        }
        
        $output .= '<label for="email">Your email:</label>';
        $output .= '<input type="email" id="email" name="email" required><br>';
        
        // Add nonce field for security
        $output .= wp_nonce_field('35rl_form_nonce', '_wpnonce', true, false);
        
        $output .= '<input type="submit" value="Submit">';
        $output .= '</form>';
    }

    if (file_exists($data_file)) {
        $entries = array();
        $lines = file($data_file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (!empty($line)) {
                $entries[] = explode(', ', $line);
            }
        }
        usort($entries, function($a, $b) {
            return strcmp($a[0], $b[0]);
        });
        if (!empty($entries)) {
            $output .= '<h3>Submitted Entries:</h3>';
            $output .= '<style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                th, td {
                    border: 1px solid;
                    text-align: left;
                    padding: 8px;
                }
            </style>';
            $output .= '<table>';
            $output .= '<tr>';
            $output .= '<th>Date and Time</th>';
            foreach ($fields as $field) {
                $output .= '<th>' . ucfirst(trim($field)) . '</th>';
            }
            if ($is_admin) {
                $output .= '<th>Email</th>';
            }
            $output .= '</tr>';
            foreach ($entries as $entry) {
                $output .= '<tr>';
                $output .= '<td>' . esc_html(end($entry)) . '</td>'; // Display date and time
                foreach ($entry as $key => $value) {
                    if ($key !== array_key_last($entry) && $key !== count($entry) - 2 && $key < count($fields)) {
                        $output .= '<td>' . esc_html($value) . '</td>';
                    } elseif ($key === count($entry) - 2 && $is_admin) {
                        // This is the email field - only display for admins
                        $output .= '<td>' . esc_html($value) . '</td>';
                    }
                }
                $output .= '</tr>';
            }
            $output .= '</table>';
            if ($is_admin) {
                $output .= '<p>Data file: ' . esc_html($data_file) . '</p>';
            }
        }
    }

    return $output;
}

add_shortcode('35rl_form', 'display_35rl_form');
?>
