<?php

require_once('pdms-oauth.php');

function pdms_policy_documents($atts = [], $content = null, $tag = '') {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // override default attributes with user attributes
    $search_attr = shortcode_atts([
        'folders' => null,
        'pagesize' => 50,
    ], $atts, $tag);


    // TODO: Support offset
    $url = pdms_getApiBaseUrl() . 'v1/search';
    $accessToken = pdms_oauth_getAccessToken('pdms_policy_settings_api');
    $searchParams = array(
        'objectTypes' => array('Document'),
        'limit' => $search_attr['pagesize'],
        'parentIds' => $search_attr['folders'] === null ? null : explode(',', $search_attr['folders'])
    );

    $body = json_encode($searchParams);
    $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json'),
            'body' => $body,
            'cookies' => array()
        ));

    if ( is_wp_error( $response ) ) {
        echo 'An error occurred contacting powerdms.com';
        return $content;
    }

    $output = '';
    $data = json_decode($response['body'])->data;
    foreach ($data->results as $result) {
        $document = $result->result;
        $output .= '<p><h2 class="pdms_policy_documentName">' . $document->name . '</h2>';
        $output .= '<span class="pdms_policy_documentDescription">' . $document->description . '</span>';
        // enclosing tags
        if (!is_null($content)) {
            // secure output by executing the_content filter hook on $content
            $output .= apply_filters('the_content', $content);

            // run shortcode parser recursively
            $output .= do_shortcode($content);
        }

        $output .= '</p>';
    }

    return $output;
}

add_shortcode('pdms-policy-documents', 'pdms_policy_documents');

?>