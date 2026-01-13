<?php
return [
    'impulsego_to' => array_values(array_filter(array_map('trim',
        explode(',', env('IMPULSEGO_MAIL_TO', ''))
    ))),
    'kpinvest_to'  => array_values(array_filter(array_map('trim',
        explode(',', env('KPINVEST_MAIL_TO', ''))
    ))),
];
