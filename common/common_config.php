<?php

global $config;
$base_url = $config['base_url'] ?? 'https://jlr-udms.cartrade.com';
$document_base_url = $config['document_base_url'] ;

return [
    'base_url' => $base_url ?? 'https://jlr-udms.cartrade.com',
    'document_base_url' => $document_base_url,
    'title' => [
        'Mr.'   => 'Mr.',
        'Mrs.'  => 'Mrs.',
        'Ms.'   => 'Ms.',
        'M/s'   => 'M/s',
        'Miss' => 'Miss',
        'Dr.'   => 'Dr.',
        'Prof.' => 'Prof.',
        'Hon.'  => 'Hon.', 
        'Sir'  => 'Sir',
        'Madam'=> 'Madam',
    ],

    'years'=> array_combine(range(date('Y'), 1980), range(date('Y'), 1980)),
    'months' => array_combine(range(1, 12), array_map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)), range(1, 12))),

    'owners'=>[
        '1' => 'First',
        '2' => 'Second',
        '3' => 'Third',
        '4' => 'Fourth',
        '5' => 'More than 4',
    ],

    // Transmission types
    'transmission' => [
        '1' => 'Automatic',
        '2' => 'Manual',
    ],

    // Fuel types
    'fuel' => [
        '1' => 'Petrol',
        '2' => 'Diesel',
        '3' => 'Electric',
        '4' => 'Hybrid',
        '5' => 'CNG',
    ],

    // Registration types
    'reg_type' => [
        '1' => 'Unregistered',
        '2' => 'Individual Registered',
        '3' => 'Company Registered',
        '4' => 'Commercial Registered',
    ],

    // Car types
    'car_type' => [
        '1' => 'OUV',
        '2' => 'Demo Car',
        '3' => 'Customer Car'
    ],

    // Boolean
    'boolean' => [
        '1' => 'Yes',
        '0' => 'No'
    ],

    'active_type' => [
        'y' => 'Yes',
        'n' => 'No'
    ],

    // Insurance Types
    'insurance_type' => [
        '1' => 'Expired',
        '2' => 'Third Party',
        '3' => 'Corporate',
        '4' => 'Comprehensive'        
    ],

    'colors' => [
        '1'  => 'Beige',
        '2'  => 'Black',
        '3'  => 'Blue',
        '4'  => 'Bronze',
        '5'  => 'Brown',
        '6'  => 'Gold',
        '7'  => 'Green',
        '8'  => 'Grey',
        '9'  => 'Maroon',
        '10' => 'Orange',
        '11' => 'Purple',
        '12' => 'Red',
        '13' => 'Silver',
        '14' => 'White',
        '15' => 'Yellow',
        '16' => 'Custom Colour'
    ],

    'hypothecation' => [
        'y' => 'Yes',
        'n' => 'No'
    ],
    // Document viewing configuration
   
    'franchise_type' => [ 
        '1' => 'Sales Retailer', 
        '2' => 'Authorised Repairer'
    ],

    'pm_status' => [ 
        '1' => 'Fresh', 
        '2' => 'Follow up',
        '3' => 'Deal Done', 
        '4' => 'Purchased', 
        '5' => 'Lost'
    ],

    'pm_sidebar_statuses' => [ 
        '1' => 'Fresh', 
        '2' => 'Follow up',
        '3' => 'Evaluation',  // Virtual bucket (not a real status, shows leads with status=2 AND sub_status=7)
        '4' => 'Deal Done', 
        '5' => 'Purchased', 
        '6' => 'Lost'
    ],

    'sm_status' => [ 
        '1' => 'Fresh', 
        '2' => 'Follow up',
        // '3' => 'Appointment Scheduled', 
        // '4' => 'Test Drive', 
        // '5' => 'Booked', 
        // '6' => 'Sold', 
        // '7' => 'Lost'
        '3' => 'Booked', 
        '4' => 'Sold', 
        '5' => 'Lost'
    ],
    'exchange_status' =>[
         '1' => "pending",
         '2' => "trade-in"
    ],

    'sm_sidebar_statuses' => [ 
        '1' => 'Fresh', 
        '2' => 'Follow up',
        // '3' => 'Appointment Scheduled', 
        // '4' => 'Test Drive', 
        // '5' => 'Booked', 
        // '6' => 'Sold', 
        // '7' => 'Lost'
        'testdrive' => 'Test Drive', // Virtual bucket (not a real status, shows leads with status=2 AND sub_status=8)
        '3' => 'Booked', 
        '4' => 'Sold', 
        '5' => 'Lost'
    ],
    'exchange_sidebar_statuses'=>[
        '1' => "Pending",
        '2' => "Trade-In"
    ],

    'pm_evaluation_place' => [ 
        '1' => 'Showroom', 
        '2' => 'Field',
    ],

    'pm_classify' => [ 
        'Hot' => 'Hot', 
        'Warm' => 'Warm', 
        'Cold' => 'Cold'
    ],

    'sm_classify' => [
        'Hot' => 'Hot',
        'Warm' => 'Warm',
        'Cold' => 'Cold'
    ],

    'pm_sub_status' => [
        '1' => [],  // Fresh - no sub-statuses
        '2' => [    // Follow up
                '1' => 'Appointment Fixed',
                '2' => 'Busy',
                '3' => 'Call Later',
                '4' => 'Number Not Available',
                '5' => 'Price negotiation with Customer',
                '6' => 'Ringing',
                '7' => 'Evaluation Scheduled',
        ],
        '3' => [    // Deal Done
                '1' => 'Token Paid',
                '2' => 'Token Pending',
        ],
        '4' => [],  // Purchased - no sub-statuses
        '5' => [    // Lost
                '1' => 'Bought Car But Kept Used Car As Additional',
                '2' => 'Bought Car But Used Car Sold Outside',
                '3' => 'Bought Competition Car',
                '4' => 'Customer Dropped Plan',
                '5' => 'Customer Price Too High',
                '6' => 'Customer Sold Elsewhere',
                '7' => 'Dealer Reject',
                '8' => 'Not Contactable',
                '9' => 'Not Interested',
                '10' => 'Outstation Lead',
                '11' => 'Too Much Time Elapsed',
                '12' => 'Wrong Number',
        ],
    ],


    
    'sm_sub_status' => [
        '1' => [],
        '2' => [
                '1' => 'Appointment Fixed',
                '2' => 'Busy',
                '3' => 'Call Later',
                '4' => 'Customer Has Called',
                '5' => 'Desired Car Not In Stock',
                '6' => 'Number Not Available',
                '7' => 'Price negotiation with Customer',
                '8' => 'Ringing',
                '9' => 'Test Drive Scheduled'
        ],
        '3' => [
                '1' => 'Token Paid',
                '2' => 'Token Pending',
        ],
        '4' => [],
        '5' => [
                '1' => 'Already Bought Car',
                '2' => 'Customer Dropped Plan',
                '3' => 'Not Contactable',
                '4' => 'Outstation Lead',
                '5' => 'Researching',
                '6' => 'Wrong Number',
                '7' => 'Dealer Reject',
                '8' => 'Invoice Cancelled'
        ],
    ],

    'inventory_lead_statuses' => [    
                 '1' => 'Refurbishment Details Pending',
                 '2' => 'Certification In Progress',
                 '3' => 'Need Certification Approval',
                 '4' => 'Ready For Sale',
                 '5' => 'Booked',
                 '6' => 'Sold',
    ],

    'evaluation_places' => [
        '1' => 'showroom',
        '2' => 'field',
    ],

    'evaluation_types' => [
        '1' => 'Full MPI',
        '2' => 'Lite MPI',
    ],
    

    'is_exchange' => [
        'n' => 'No',
        'y' => 'Yes',
    ],

    // 'lead_classifications' => [
    //     '1' => 'Hot',
    //     '2' => 'Warm',
    //     '3' => 'Cold',
    // ],
    

    // Reason for Selling options
    'reason_for_selling' => [
        '1' => 'Buying a JLR new car',
        '2' => 'Buying a JLR used car',
        '3' => 'Buying a non-JLR new car',
        '4' => 'Buying a non-JLR used car',
        '5' => 'Only selling'
    ],

    // Reason for Selling Subsection options (shown when 'Only selling' is selected)
    'rs_subsection_options' => [
        '6' => 'Moving to another city/country',
        '7' => 'Unhappy with product',
        '8' => 'Unhappy with service',
        '9' => 'Other (Please specify)'
    ],

    'customer_type' => [
        '1' => 'Individual',
        '2' => 'Company',
        '3' => 'broker'
    ],

    'source_other' => [
       '1'=> 'Auction',
       '2'=> 'Ex Demo',
       '3'=> 'Ex Lease',
       '4'=> 'JLR OUV',
       '5'=> 'JLR Financial Services',
       '6'=> 'JLR Wholesale',
       '7'=> 'Part Exchange / Trade In',
       '8'=> 'Other',
    ],

    'contact_method' => [
       '1'=> 'Mobile',
       '2'=> 'Email',
       '3'=> 'WhatsApp'
    ],


    'buying_horizon' => [
        '1' => 'less than 7 days',
        '2' => '7 to 15 days',
        '3' => '15 to 30 days',
        '4' => 'More than 30 days'
    ],

    'buyer_type' => [
        '1' => 'Broker', 
        '2' => 'Customer',
        '3' => 'Company'
    ],

    'budget_range' => [
        '1' => 'Under 25L',
        '2' => ' 25L– 50L',
        '3' => ' 50L– 75L',
        '4' => ' 75L– 1Cr',
        '5' => ' 1Cr+',
    ],
    'vehicle_budget_range' => [
        '0 - 2500000' => 'Under 25 Lacs',
        '2500000 - 5000000' => '25 Lacs - 50 Lacs',
        '5000000 - 7500000' => '50 Lacs - 75 Lacs',
        '7500000 - 10000000' => '75 Lacs - 1 Cr',
        '10000000 - 0' => 'Over 1Cr',
    ],

    // Customer Preferences - Mileage Range
    'mileage_range' => [
        '1' => '0-10k',
        '2' => '10k-25k',
        '3' => '25k-50k',
        '4' => '50k-100k',
        '5' => '100k+',
    ],

    // Customer Preferences - Car Age
    'car_age' => [
        '1' => '< 1 year',
        '2' => '1-2 years',
        '3' => '2-5 years',
        '4' => '5 years+',
    ],

    'finance' => [
        'y' => 'Yes',
        'n' => 'No'
    ],

    'customer_visited' => [
        'y' => 'Yes',
        'n' => 'No'
    ],

    'test_drive_done' => [
        'n' => 'No',
        'y' => 'Yes',
    ],

    'testdrive_place' => [
        '1' => 'Showroom',
        '2' => 'Home Visit',
        '3' => 'Other',
        '4' => 'Event',
    ],

    'testdrive_status' => [
        '1' => 'Scheduled',
        '2' => 'Completed',
        '3' => 'Cancelled',
        '4' => 'No Show'
    ],

    // common statuses
    'common_statuses' => [
        'inventory' => [
            'refurbishment' => '1',
            'certification' => '2',
            'need_certification_approval' => '3',
            'ready_for_sale' => '4',
            'booked' => '5',
            'sold' => '6'
        ],
        'pm' => [
            'status' => [
                'fresh' => '1',
                'followup' => '2',
                'deal_done' => '3',
                'purchased' => '4',
                'lost' => '5'
            ],
            'sub_status' => [
                
            ]
        ],

        'sm' => [
            'status' => [
                'fresh' => '1',
                'followup' => '2',
                'booked' => '3',
                'sold' => '4',
                'lost' => '5'
            ],
            'sub_status' =>[
                'appointment_fixed' => '1',
                'busy' => '2',
                'call_later' => '3',
                'customer_has_called' => '4',
                'desired_car_not_in_stock' => '5',
                'number_not_available' => '6',
                'price_negotiation_with_customer' => '7',
                'ringing' => '8',
                'test_drive_scheduled' => '9',
                
                'token_paid' => '1',
                'token_pending' => '2',

                'already_bought_car' => '1',
                'customer_dropped_plan' => '2',
                'not_contactable' => '3',
                'outstation_lead' => '4',
                'researching' => '5',
                'wrong_number' => '6',
                'dealer_reject' => '7',
                'invoice_cancelled' => '8',
            ]
        ],
    ],

    'body_types' => [
        '1' => 'SUV',
        '2' => 'Sedan',
        '3' => 'Hatchback',
        '4' => 'Coupe',
        '5' => 'Convertible',
        '6' => 'Pickup Truck',
        '7' => 'Van/Minivan',
        '8' => 'Wagon',
        '9' => 'Crossover',
    ],  

    'certification_type' => [
        '1' => 'JLR Premium Selection',
        '2' => 'Non-Certified'
    ],

    'certification_status' => [
        '1' => 'Approved',
        '2' => 'Rejected',
    ],

    'invoice_type' => [
        '1' => 'First Time Sale',
        '2' => 'Margin Scheme',
        '3' => 'Park & Sell Commission Based'
    ]


];
