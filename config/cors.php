<?php
return [

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| This configuration determines what cross-origin operations may execute
| in web browsers. You are free to adjust these settings as needed.
|
| For more information: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
|
*/

'paths' => ['api/*', 'sanctum/csrf-cookie', 'http://localhost:5173', 'http://192.168.1.82:5173'],  // Make sure this is an array

'allowed_methods' => ['*'],

'allowed_origins' => ['*'],  // You can replace this with specific domains if needed

'allowed_origins_patterns' => [],

'allowed_headers' => ['*'],

'exposed_headers' => [],

'max_age' => 0,

'supports_credentials' => true,

];
?>