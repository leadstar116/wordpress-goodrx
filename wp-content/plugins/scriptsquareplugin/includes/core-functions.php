<?php // ScriptSquare - Core Functionality

error_reporting(E_ALL);
ini_set('display_errors', 1);

// disable direct file access
if (!defined('ABSPATH')) {

    exit;
}



// Get Drug By Name
function scriptsquareplugin_get_drug_by_name($content)
{
    global $post;
    // only edit specific post types
    $types = array('drug');

    if ($post && in_array($post->post_type, $types, true)) {
        $options = get_option('scriptsquareplugin_options', scriptsquareplugin_options_default());

        if (isset($options['api_url']) && !empty($options['api_url'])) {

            $url = esc_url($options['api_url']);

            $args = [
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => $options['api_key'],
                )
            ];

            $request = wp_remote_get($url . "/drugs?drugname=$post->post_name&includestrength=false", $args);

            if (is_wp_error($request)) {
                return false; // Bail early
            }

            $body = wp_remote_retrieve_body($request);

            $data = json_decode($body);

            if(isset($data->message)) {
                $content = '
                    <h3 class="scriptsquareplugin_warning">
                        Something went wrong! Please check API configuration.
                        Error details: <span>'.$data->message.'</span>
                    </h3>
                ';
            } else {
                $content = '
                    <table class="table use-dataTable songs-rating-table playlist">
                    <thead>
                        <tr>
                            <th scope="col" class="no-sort">GPI10</th>
                            <th scope="col">Drug Name</th>
                            <th scope="col">Item Status</th>
                            <th scope="col">Dosage Form</th>
                        </tr>
                    </thead>
                    <tbody>
                ';
                foreach($data as $product) {
                    // print_r($product);
                    $content .= '<tr>'.
                        '<td>'.$product->GPI10.'</td>'.
                        '<td>'.$product->Product_Name.'</td>'.
                        '<td>'.$product->Item_Status_Flag.'</td>'.
                        '<td>'.$product->Dosage_Form.'</td>'.
                        '</tr>';
                }
                $content .= '
                    </tbody>
                    </table>
                ';
            }
        }
    }

    return $content;
}

// add the filter when main loop starts
add_action('loop_start', function (WP_Query $query) {
    if ($query->is_main_query()) {
        add_filter('the_content', 'scriptsquareplugin_get_drug_by_name', -10);
    }
});

// remove the filter when main loop ends
add_action('loop_end', function (WP_Query $query) {
    if (has_filter('the_content', 'scriptsquareplugin_get_drug_by_name')) {
        remove_filter('the_content', 'scriptsquareplugin_get_drug_by_name');
    }
});


// custom plugin styles
function scriptsquareplugin_custom_styles() {
    wp_enqueue_style( 'scriptsquareplugin', plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/scriptsquareplugin_style.css', array(), null, 'screen' );
}
add_action( 'wp_enqueue_scripts', 'scriptsquareplugin_custom_styles' );
