<?php
/*
 * Plugin Name: RequestLister
 * Description: A simple plugin to capture multiple fields including name and email, save to a text file, and display the list of names.
 * Version: 1.5.0
 * Author: Jorge Pereira
 * Author URI: http://jorgep.com/plugins
 * License:   GPLv2 or later
 */

define('CUSTOM_DEBUG', TRUE); // Set to true to enable custom debugging, false to disable
// wp_debug_mode(true); // Define if not already defined
  wp_debug_mode(false); // Override to false

@ini_set('display_errors', 0); // Recommended to keep errors from showing on live site, let logging handle it


function sanitize_input1($input) {
    $input = sanitize_text_field($input);
    return str_replace(',', ' ', $input);
}

function extract_field_name($field) {
    if (preg_match('/^([^{]+)/', trim($field), $matches)) {
        return trim($matches[1]);
    }
    return trim($field);
}

function extract_field_options($field) {
    if (preg_match('/{([^}]+)}/', $field, $matches)) {
        $options_str = $matches[1];
        return array_map('trim', explode(';', $options_str));
    }
    return array();
}

function display_35rl_form($atts) {
    $atts = shortcode_atts(array(
        'fields' => 'name',
        'data_file' => 'sampledata.txt',
    ), $atts);

    $fields_raw = explode(',', $atts['fields']);
    $fields = array();

    foreach ($fields_raw as $field) {
        $field = trim($field);
        $fields[] = $field;
    }

    $data_file = plugin_dir_path(__FILE__) . 'data/' . basename($atts['data_file']);
    $output = '';
    $is_admin = current_user_can('manage_options');   

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
        // Data saving is now handled by save_data.php
    } else {

        // Live display of form data
		    if ($is_admin) {
                $output .= '<p><small>Data file: ' . esc_html($data_file) . '</small></p>';
            }
        If (CUSTOM_DEBUG) $output .= '<div id="formDataDisplay"></div>';

        $output .= '<form id="dataEntry" method="post" action="' . plugin_dir_url(__FILE__) . 'save_data.php">';
        $output .= '<input type="hidden" name="fields" value="' . esc_attr(json_encode($fields)) . '">';
        $output .= '<input type="hidden" name="redirect_url" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
		 $output .= '<input type="hidden" name="data_file" value="' . esc_attr($atts['data_file']) . '">'; 



        foreach ($fields as $field) {
            $field_name = extract_field_name($field);
            $field_options = extract_field_options($field);

            $output .= '<label for="' . esc_attr($field_name) . '">' . ucfirst($field_name) . ':</label>';

            if (!empty($field_options)) {
                $output .= '<select id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" required>';
                $output .= '<option value="" disabled selected>Select ' . ucfirst($field_name) . '</option>';
                foreach ($field_options as $option) {
                    $output .= '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                }
                $output .= '</select><br>';
            } else {
                $output .= '<input type="text" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" required><br>';
            }
        }

        $output .= '<label for="email">Your email:</label>';
        $output .= '<input type="email" id="email" name="email" required><br>';

        $output .= wp_nonce_field('35rl_form_nonce', '_wpnonce', true, false);

        $output .= '<input type="submit" value="Submit">';
        $output .= '</form>';
		  if (WP_DEBUG) {

				// JavaScript for live display // Only show if 
				//   TODO:   Would be nice to show the NAME TAG labels
				$output .= '<script>
    (function() { // Wrap in an IIFE to create a local scope
        const form = document.getElementById("dataEntry");
        const formDataDisplay = document.getElementById("formDataDisplay");

        if (form && formDataDisplay) { // Check if elements exist before adding listeners.
            function updateFormDataDisplay() {
                const formData = {};
                const formElements = form.elements;

                for (let i = 0; i < formElements.length; i++) {
                    const element = formElements[i];
                    if (element.name) {
                        formData[element.name] = element.value;
                    }
                }

                formDataDisplay.innerHTML = "<pre>" + JSON.stringify(formData, null, 2) + "</pre>";
            }

            form.addEventListener("input", updateFormDataDisplay);
            form.addEventListener("change", updateFormDataDisplay);
            updateFormDataDisplay();
        }
    })();
</script>';
		  }
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
                $field_name = extract_field_name($field);
                $output .= '<th>' . ucfirst($field_name) . '</th>';
            }
            if ($is_admin) {
                $output .= '<th>Email</th>';
            }
            $output .= '</tr>';
            foreach ($entries as $entry) {
                $timestamp_pos = array_key_last($entry);
                $email_pos = $timestamp_pos - 1;

                $output .= '<tr>';
                $output .= '<td>' . esc_html($entry[$timestamp_pos]) . '</td>';

                $field_index = 0;
                foreach ($fields as $field) {
                    $field_name = extract_field_name($field);
                    if ($field_index < count($entry) - 2) {
                        $output .= '<td>' . esc_html(isset($entry[$field_index]) ? $entry[$field_index] : '') . '</td>';
                    }
                    $field_index++;
                }

                if ($is_admin && $email_pos >= 0) {
                    $output .= '<td>' . esc_html($entry[$email_pos]) . '</td>';
                }

                $output .= '</tr>';
            }
            $output .= '</table>';
        
        }
    }

    return $output;
}

add_shortcode('35rl_form', 'display_35rl_form');
?>