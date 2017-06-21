<?php

function pdms_getApiBaseUrl() {
    return get_option('pdms_api_base_url', 'https://api.powerdms.com/');
}

function pdms_updateApiBaseUrl($apiBaseUrl) {
    if (!endsWith($apiBaseUrl, '/')) {
        $apiBaseUrl .= '/';
    }

    return update_option('pdms_api_base_url', $apiBaseUrl);
}

function pdms_oauth_getAccessToken($optionName, $code = null) {
    $options = get_option($optionName);
    if ($code === null && ($options === false || !isset($options['access_token']) || $options['access_token'] === '')) {
        return null; // no authorization code & no access token exists
    }

    // get access_token via authorization code
    if (isset($code)) {
        $schemeAndHost = home_url(add_query_arg(array(), $wp->request));
        $requestUri = parse_url( $schemeAndHost . $_SERVER["REQUEST_URI"]);
        parse_str($requestUri['query'], $queryString);
        $redirectUri = $schemeAndHost . $requestUri['path'] . '?page=' . $queryString['page'];
        $tokenRequest = array(
            'grant_type'        => 'authorization_code',
            'code'              => $code,
            'client_id'         => '',
            'client_secret'     => '',
            'scope'             => 'openid profile offline_access',
            'redirect_uri'      => pdms_getApiBaseUrl() . 'auth/redirect?redirect_uri=' . $redirectUri
        );

        $url = pdms_getApiBaseUrl() . 'auth/connect/token';
        $body = http_build_query($tokenRequest);
        $response = wp_remote_post($url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 1,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
                'body' => $body,
                'cookies' => array()
            )
        );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $tokenResponse = json_decode($response['body']);
        pdms_handleTokenResponse($optionName, $tokenResponse);

        return $tokenResponse->access_token;
    }

    $now = (new DateTime())->getTimestamp();
    if ($options['expires_on'] != '' && $now > $options['expires_on']) {
        // get access_token via refresh_token
        return pdms_oauth_refreshAccessToken($options['refresh_token'], 'pdms_policy_settings_api');
    } else {
        return $options['access_token'];
    }
}

function pdms_oauth_refreshAccessToken($refreshToken, $optionName) {
    $tokenRequest = array(
        'grant_type'        => 'refresh_token',
        'client_id'         => '',
        'client_secret'     => '',
        'scope'             => 'openid profile offline_access',
        'refresh_token'     => $refreshToken
    );
    $url = pdms_getApiBaseUrl() . 'auth/connect/token';
    $body = http_build_query($tokenRequest);
    $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 1,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => $body,
            'cookies' => array()
        )
    );

    if ( is_wp_error( $response ) ) {
        return '';
    } else {
        $tokenResponse = json_decode($response['body']);
        pdms_handleTokenResponse($optionName, $tokenResponse);

        return $tokenResponse->access_token;
    }
}

function pdms_handleTokenResponse($optionName, $tokenResponse) {
    $options = get_option($optionName);
    if (isset($tokenResponse->access_token))
        $options['access_token'] = $tokenResponse->access_token;

    if (isset($tokenResponse->refresh_token))
        $options['refresh_token'] = $tokenResponse->refresh_token;

    if (isset($tokenResponse->expires_in)) {
        $date = new DateTime();
        $date->add(new DateInterval('PT' . ($tokenResponse->expires_in - 60) . 'S')); // add oauth expiration seconds - 60sec buffer
        $options['expires_on'] = $date->getTimestamp();
    }

    if (isset($tokenResponse->id_token)) {
        $claims = decodeJWT($tokenResponse->id_token, 1);
        $options['site_key'] = $claims->tnt_key;
        $options['first_name'] = $claims->given_name;
        $options['last_name'] = $claims->family_name;
    }

    update_option($optionName, $options);
}


function decodeJWT($jwt, $section = 0) {
    $parts = explode(".", $jwt);
    return json_decode(base64url_decode($parts[$section]));
}


/**
 * A wrapper around base64_decode which decodes Base64URL-encoded data,
 * which is not the same alphabet as base64.
 */
function base64url_decode($base64url) {
    return base64_decode(b64url2b64($base64url));
}

/**
 * Per RFC4648, "base64 encoding with URL-safe and filename-safe
 * alphabet".  This just replaces characters 62 and 63.  None of the
 * reference implementations seem to restore the padding if necessary,
 * but we'll do it anyway.
 *
 */
function b64url2b64($base64url) {
    // "Shouldn't" be necessary, but why not
    $padding = strlen($base64url) % 4;
    if ($padding > 0) {
        $base64url .= str_repeat("=", 4 - $padding);
    }
    return strtr($base64url, '-_', '+/');
}

?>