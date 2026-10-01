<?php

declare(strict_types=1);

return [

    'route_prefix' => 'files',

    'middleware' => ['api'],

    'upload_middleware' => [],

    'disk' => 's3',

    'presign_disk' => 's3_public',

    'max_upload_bytes' => 104_857_600,

    'allowed_types' => [],

];
