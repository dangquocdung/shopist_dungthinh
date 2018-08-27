<?php
$api_key = '';
$api_secret = '';
$get_nexmo_data = get_nexmo_data();

if(count($get_nexmo_data) > 0 && !empty($get_nexmo_data['nexmo_key']) && !empty($get_nexmo_data['nexmo_secret'])){
  $api_key = $get_nexmo_data['nexmo_key'];
  $api_secret = $get_nexmo_data['nexmo_secret'];
}

return [

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | If you're using API credentials, change these settings. Get your
    | credentials from https://dashboard.nexmo.com | 'Settings'.
    |
    */

    'api_key'    => $api_key,
    'api_secret' => $api_secret,

    /*
    |--------------------------------------------------------------------------
    | Signature Secret
    |--------------------------------------------------------------------------
    |
    | If you're using a signature secret, use this section. This can be used
    | without an `api_secret` for some APIs, as well as with an `api_secret`
    | for all APIs.
    |
    */

    'signature_secret' => function_exists('env') ? env('NEXMO_SIGNATURE_SECRET', '') : '',

    /*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    |
    | Private keys are used to generate JWTs for authentication. Generation is
    | handled by the library. JWTs are required for newer APIs, such as voice
    | and media
    |
    */

    'private_key' => function_exists('env') ? env('NEXMO_PRIVATE_KEY', '') : '',
    'application_id' => function_exists('env') ? env('NEXMO_APPLICATION_ID', '') : '',

];
