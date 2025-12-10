<?php

return [

    'paths' => ['api/*', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ["*"],  // or your frontend: ['http://192.168.61.111:9055']

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
