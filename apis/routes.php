<?php
global $config;
$config['routes'] = [
        'auth' => [
            'protected' => false,
            'roles' => ['guest', 'user', 'admin'],
            'subroutes' => [
                'getuser' => ['protected' => true, 'roles' => ['user', 'admin']],
            ]
        ],
        'purchase-master' => [
            'protected' => true,
            'roles' => ['dealer', 'admin'],
            'subroutes' => [
                'getconfig' => ['protected' => true, 'roles' => ['user', 'dealer', 'admin']],
                'getleads' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'getlead' => ['protected' => true, 'roles' => ['user','dealer', 'admin']],
                'addlead' => ['protected' => true, 'roles' => ['admin']],
                'updatelead' => ['protected' => true, 'roles' => ['admin']],
                'updateleadstatus' => ['protected' => true, 'roles' => ['admin']],
                'uploadimages' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'gethistory' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'exportdata' => ['protected' => true, 'roles' => ['dealer', 'admin']],
            ]
        ],
        'exchange' => [
            'protected' => true,
            'roles' => ['dealer', 'admin'],
            'subroutes' => [
                'getconfig' => ['protected' => true, 'roles' => ['user', 'dealer', 'admin']],
                'getleads' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'getlead' => ['protected' => true, 'roles' => ['user','dealer', 'admin']],
                    'exportdata' => ['protected' => true, 'roles' => ['dealer', 'admin']],

            ]
        ],
        'sales-master' => [
            'protected' => true,
            'roles' => ['dealer', 'admin'],
            'subroutes' => [
                'getleads' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'getlead' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'addlead' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'updateleadstatus' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'gethistory' => ['protected' => true, 'roles' => ['dealer', 'admin']],
            ]
        ],
        'my-stock' => [
            'protected' => true,
            'roles' => ['dealer', 'admin'],
            'subroutes' => [
                'getconfig' => ['protected' => true, 'roles' => ['user', 'dealer', 'admin']],
                // 'getleads' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'getlist' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'getlead' => ['protected' => true, 'roles' => ['dealer', 'admin']],
                'addlead' => ['protected' => true, 'roles' => ['admin']],
                'updatelead' => ['protected' => true, 'roles' => ['admin']],
                'updateleadstatus' => ['protected' => true, 'roles' => ['admin']],
                'exportdata' => ['protected' => true, 'roles' => ['dealer', 'admin']],

            ]
        ],
        'admin' => [
            'protected' => true,
            'roles' => ['admin'],
            'subroutes' => [
                'roles_list' => ['protected' => true, 'roles' => [ 'admin']],
                'add_role' => ['protected' => true, 'roles' => [ 'admin']],
                'edit_role' => ['protected' => true, 'roles' => [ 'admin']],
            ]
        ],
        "master-data" => [
            'protected' => true,
            'roles' => ['admin','dealer'],
            'subroutes' => [
                'getYears' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getMakes' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getmakesbyYear' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getmodelsbyMake' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getvariantsbyModel' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getmmvsData' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getExecutives' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getSources' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getSubSources' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getcities' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getVahanDetails' => ['protected' => true, 'roles' => [ 'admin','dealer']],
                'getColorsByMake' => ['protected' => true, 'roles' => [ 'admin','dealer']],
            ]
        ],
        "executive-management" => [
            'protected' => true,
            'roles' => ['dealer'],
            'subroutes' => [
                'get' => ['protected' => true, 'roles' => ['dealer']],
                'add' => ['protected' => true, 'roles' => ['dealer']],
                'edit' => ['protected' => true, 'roles' => ['dealer']],
            ]
        ],
        "invoice" => [
            'protected' => true,
            'roles' => ['dealer', 'admin'],
            'subroutes' => [
                'gethistory' => ['protected' => true, 'roles' => ['dealer', 'admin']],
            ]
        ],
    ];
