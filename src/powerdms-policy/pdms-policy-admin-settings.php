<?php

require_once('pdms-oauth.php');

$pdms_policy_settings_api_defaults = array(
    'access_token'     => '',
    'refresh_token'    => '',
    'expires_on'       => '',
    'site_key'         => '',
    'first_name'       => '',
    'last_name'        => ''
);

function pdms_policy_settings_scripts() {
    // Register the script like this for a plugin:
    $inline = 'var pdms_apiBaseUrl = "' . pdms_getApiBaseUrl() . '";';
    wp_register_script( 'pdms_policy_settings_script_footer', plugins_url( '/js/pdms-policy-admin-settings-footer.js', __FILE__), array(), null, true );
    wp_add_inline_script('pdms_policy_settings_script_footer', $inline, 'before');

    // For either a plugin or a theme, you can then enqueue the script:
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'pdms_policy_settings_script_header' );
    wp_enqueue_script( 'pdms_policy_settings_script_footer' );

}
add_action( 'admin_enqueue_scripts', 'pdms_policy_settings_scripts' );

function pdms_policy_settings_options() {
    global $pdms_policy_settings_api_defaults;
    return wp_parse_args(get_option('pdms_policy_settings_api'), $pdms_policy_settings_api_defaults);
}

function pdms_policy_settings_init() {
    // register settings for "pdms_policy_settings" page
    register_setting( 'pdms_policy_settings', 'pdms_policy_settings_api' );

    // register a new section in the "pdms_policy_settings" page
    add_settings_section(
        'pdms_policy_settings_section_api',
        __( 'PowerDMS API', 'pdms_policy_settings_page' ),
        'pdms_policy_settings_section_api_callback',
        'pdms_policy_settings'
    );

    $requestUri = parse_url(home_url(add_query_arg(array(), $wp->request)));
    if (endsWith($requestUri['host'], 'powerdms.net')) {
        add_settings_field(
            'pdms_policy_settings_section_api_field_apiBaseUrl',
            __( 'API Base URL:', 'pdms_policy_settings_page' ),
            'pdms_policy_settings_section_api_field_apiBaseUrl_callback',
            'pdms_policy_settings',
            'pdms_policy_settings_section_api',
            [
                'label_for' => 'pdms_policy_settings_section_api_field_apiBaseUrl',
                'class' => 'pdms_admin_settings_row'
            ]
        );
    }

    add_settings_field(
        'pdms_policy_settings_section_api_field_login',
        __( 'Please login to powerdms.com:', 'pdms_policy_settings_page' ),
        'pdms_policy_settings_section_api_field_login_callback',
        'pdms_policy_settings',
        'pdms_policy_settings_section_api',
        [
            'label_for' => 'pdms_policy_settings_section_api_field_login',
            'class' => 'pdms_admin_settings_row'
        ]
    );
}
add_action( 'admin_init', 'pdms_policy_settings_init' );

function pdms_policy_settings_section_api_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>" style="font-style: italic;"><?php esc_html_e( 'Configure your PowerDMS Policy Integration', 'pdms_policy_settings_page' ); ?></p>
    <?php
}

function pdms_policy_settings_section_api_field_apiBaseUrl_callback( $args) {
    ?>

    <input type="text"
           id="<?php echo esc_attr($args['label_for']) ?>"
           name="<?php echo esc_attr($args['label_for']) ?>"
           value="<?php echo pdms_getApiBaseUrl(); ?>" />

    <?php

    submit_button('Save Settings');
}

function pdms_policy_settings_section_api_field_login_callback( $args ) {
    $options = pdms_policy_settings_options();
    $hasRefreshToken = $options[ 'refresh_token' ] !== '';
    ?>

    <input type="button" id="<?php echo esc_attr($args['label_for']); ?>" value="Login" />

    <?php
    if (!$hasRefreshToken){

    } else {

    }
}

function pdms_policy_settings_section_api_field_accessToken_callback( $args ) {
    $options = pdms_policy_settings_options();
    ?>

    <span id="<?php echo esc_attr($args['label_for']); ?>"
          style="width: 500px;overflow: scroll;background: #fff;display: block;border: 1px solid #ccc;color:#888;cursor: default;">
        <?php echo $options[ 'access_token' ] === '' ? 'please supply credentials' : esc_attr($options[ 'access_token' ]) ?>
    </span>

    <?php
}

function pdms_policy_settings_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = pdms_policy_settings_options();
    if (isset($_GET['code']))
    {
        if (isset($_GET['state']))
        {

        }

        $accessToken = pdms_oauth_getAccessToken('pdms_policy_settings_api', $_GET['code']);
        if (!isset($accessToken)) {
            add_settings_error( 'pdms_policy_settings_messages', 'pdms_error_message', __( 'Settings Saved', 'pdms_policy_settings_page' ), 'updated' );
        }

        wp_register_script( 'pdms_oauth_callback', plugins_url( '/js/pdms-oauth-callback.js', __FILE__), array(), null, false );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'pdms_oauth_callback' );
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        if (isset($_POST['pdms_policy_settings_section_api_field_apiBaseUrl'])) {
            pdms_updateApiBaseUrl($_POST['pdms_policy_settings_section_api_field_apiBaseUrl']);
        }
    }

    // show error/update messages
    settings_errors( 'pdms_policy_settings_messages' );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="" method="post">
            <input type="hidden" name="pdms_policy_settings_api_update" value="true" />
            <?php
            $options = pdms_policy_settings_options();
            settings_fields( 'pdms_policy_settings' );
            do_settings_sections( 'pdms_policy_settings' );
            if (isset($options['refresh_token']) && $options['refresh_token'] !== '') {
                ?>
                <hr/>
                Currently logged in as:<br/>
                <span>Site Key:</span> <?php echo $options['site_key'] ?><br/>
                <span>First Name:</span> <?php echo $options['first_name'] ?><br/>
                <span>Last Name:</span> <?php echo $options['last_name'] ?><br/>
                <?php
            }

                ?>
        </form>
    </div>
    <?php
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

?>