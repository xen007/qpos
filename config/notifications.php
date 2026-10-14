<?php
return [
    'connection'=>'notification_database',
    'queue'=>'qpos-notifications',
    // Critical sales are an opt-in threshold, not a synonym for every checkout.
    'critical_sale_threshold'=>null,
    'low_stock_threshold'=>'10',
    'expiry_thresholds'=>[90,30,7,0],
    'max_attempts'=>5,
    'twilio'=>[
        'account_sid'=>env('TWILIO_ACCOUNT_SID'),
        'auth_token'=>env('TWILIO_AUTH_TOKEN'),
        'sms_from'=>env('TWILIO_SMS_FROM'),
        'whatsapp_from'=>env('TWILIO_WHATSAPP_FROM'),
        'templates'=>['fr'=>env('TWILIO_WHATSAPP_TEMPLATE_FR'),'en'=>env('TWILIO_WHATSAPP_TEMPLATE_EN')],
    ],
];
