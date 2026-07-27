<?php

return [
    'import_max_mb' => (int) env('EXPERTIS_IMPORT_MAX_MB', 50),
    'chunk_size' => max(100, (int) env('EXPERTIS_IMPORT_CHUNK_SIZE', 1000)),
    'queue' => env('EXPERTIS_QUEUE', 'expertis'),
    'job_timeout' => max(60, (int) env('EXPERTIS_JOB_TIMEOUT', 1800)),
    'stale_minutes' => max(1, (int) env('EXPERTIS_STALE_MINUTES', 15)),
    'heartbeat_rows' => 500,
    'prepared_directory' => 'private/expertis/prepared',
    'guardar_archivo_original' => filter_var(
        env('EXPERTIS_GUARDAR_ARCHIVO_ORIGINAL', true),
        FILTER_VALIDATE_BOOL,
    ),
    'preview_ttl_minutes' => 120,
];
