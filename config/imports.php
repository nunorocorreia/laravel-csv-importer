<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Storage Disk
    |--------------------------------------------------------------------------
    |
    | This disk is used to read CSV files that have been uploaded to a cloud
    | provider (for example, S3 or MinIO). Override the value via the
    | IMPORT_STORAGE_DISK environment variable when needed.
    |
    */
    'storage_disk' => env(
        'IMPORT_STORAGE_DISK',
        env('FILESYSTEM_CLOUD', env('FILESYSTEM_DISK', 's3'))
    ),
];


