<?php
return [
 'title'=>'Critical external alerts','notice'=>'Disabled by default. Destinations belong to the global administrator saving this form. Applies to future events only; daily summary recipients are unchanged.',
 'email'=>'Email','sms'=>'SMS (Twilio)','whatsapp'=>'WhatsApp (Twilio)','enabled'=>'Enable this channel','destinations'=>'Recipients (list)',
 'destination_help'=>'Emails separated by newline, comma or semicolon. International phone numbers, e.g. +2376XXXXXXXX. Up to twenty recipients per channel.',
 'locale'=>'Message language','kinds'=>'Alerts to send','consent'=>'I authorize these recipients to receive these alerts and confirm their consent for SMS/WhatsApp.',
 'consent_required'=>'Confirm destinations and consent before activation.','invalid'=>'Check alert types, recipients and enabled channels.',
 'ready'=>'Configuration present, receipt unverified','unconfigured'=>'Transport unconfigured','disabled'=>'Disabled',
 'history'=>'External deliveries','channel'=>'Channel','state'=>'State','attempts'=>'Attempts','reference'=>'Reference',
 'authenticated_link'=>'View after signing in','minimal'=>'Financial details and personal data remain in the app under your permissions.',
 'template_help'=>'WhatsApp requires an approved FR/EN template. Variables: 1 alert, 2 shop, 3 reference, 4 authenticated link. Credentials and secrets are configured outside this page, never in the database.',
 'accepted_notice'=>'Accepted means accepted by SMTP or provider, not confirmed receipt. Ambiguous: check the provider; no automatic resend.',
 'states'=>['pending'=>'Pending','retry'=>'Scheduled retry','unconfigured'=>'Unconfigured','sending'=>'Sending','accepted'=>'Accepted by transport','delivered'=>'Provider-reported delivery','ambiguous'=>'Uncertain confirmation','failed'=>'Failed','cancelled'=>'Cancelled'],
 'external_preferences'=>'My external channels','preferences_notice'=>'Applies only to destinations authorized in global settings. This page adds no destination or permission.',
];
