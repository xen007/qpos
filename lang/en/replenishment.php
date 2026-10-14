<?php
return [
'title'=>'Replenishment','policies'=>'Stock thresholds and alerts','policy_help'=>'Quantities use the base unit. Empty = inherited. Creating a product creates no stock. Shared settings require a global administrator.',
'scope'=>'Scope','product_shop'=>'Product in this shop','global'=>'All shops','defaults'=>'Defaults',
'minimum'=>'Minimum threshold','optimal'=>'Target stock','maximum'=>'Maximum stock','alerts_enabled'=>'Enable alerts','maximum_enabled'=>'Enable maximum','hide_out_of_stock'=>'Hide out of stock at POS',
'effective'=>'Effective value','inherit'=>'Inherit','saved'=>'Setting saved and audited.','invalid_policy'=>'Thresholds must satisfy minimum ≤ target ≤ enabled maximum, with at most six decimal places.','policy_volume'=>'Too many combinations for interactive validation; contact the system manager.',
'out'=>'Out of stock','low'=>'Low stock','normal'=>'Normal','over'=>'Overstock','critical'=>'Stockout risk before delivery','orders'=>'Purchase orders','suppliers'=>'Supplier catalogue',
'active_supplier'=>'Select an active external supplier.',
'multiple_help'=>'Minimum must be compatible with the order multiple.',
'price_date_required'=>'Enter the date of this estimated price.',
'supplier_reference'=>'Supplier reference',
'delay_days'=>'Lead time in days',
'minimum_order'=>'Minimum order (packagings)',
'order_multiple'=>'Order multiple (packagings)',
'estimated_cost'=>'Estimated cost per packaging (XAF)',
'price_on'=>'Price date',
'preferred'=>'Preferred supplier',
'supplier_help'=>'Prices are dated estimates. Multiple suppliers are allowed; at most one preferred supplier per product. Brand never determines the supplier.',
'quick_product'=>'Create product here',
'fractional'=>'Allow fractional quantities',
'zero_stock'=>'Catalogue only: zero stock. Actual cost is entered on receipt.',
'creation_failed'=>'Unable to create product.'
];
