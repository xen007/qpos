<?php
return [
    'connection'=>'notification_database',
    'queue'=>'qpos-notifications',
    // Critical sales are an opt-in threshold, not a synonym for every checkout.
    'critical_sale_threshold'=>null,
    'low_stock_threshold'=>'10',
    'expiry_thresholds'=>[90,30,7,0],
    'max_attempts'=>5,
];
