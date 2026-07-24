<?php

return [
    'import_max_mb' => (int) env('EXPERTIS_IMPORT_MAX_MB', 50),
    'chunk_size' => max(100, (int) env('EXPERTIS_IMPORT_CHUNK_SIZE', 1000)),
    'guardar_archivo_original' => filter_var(
        env('EXPERTIS_GUARDAR_ARCHIVO_ORIGINAL', true),
        FILTER_VALIDATE_BOOL,
    ),
    'preview_ttl_minutes' => 120,
];
