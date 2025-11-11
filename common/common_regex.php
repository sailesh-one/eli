<?php
// common_regex.php

$patterns = [
    // Basic text
    "alpha" => "^[a-zA-Z ]+$",                       // Letters + spaces
    "alphanumeric" => "^[a-zA-Z0-9 ]+$",             // Letters, digits, spaces
    "alphanumericspecial" => "^[a-zA-Z0-9.,@\-\/ ]+$",
    "title" => "^[a-zA-Z0-9&\\-\\/\\. ]+$",

    // Contact
    "mobile" => "^[6-9]\\d{9}$",                     // 10-digit Indian mobile
    "email" => "^(?=.{5,100}$)([A-Za-z0-9_\\-\\.]+)@([A-Za-z0-9_\\-\\.]+)\\.([A-Za-z]{2,20})$",
    "address" => "^[a-zA-Z0-9\\s,\\.\\-#()\,]+$",     // Alphanumeric + punctuation

    // Identifiers
    "id" => "^\\d+$",                                // Generic numeric ID
    "numeric" => "^[0-9]+$",                         // Generic number
    "double" => "^[0-9]+(?:\\.[0-9]{1,2})?$",         // Decimal number with up to 2 decimal places
    "active" => "^(y|n)$",                           // Status Y/N
    "chassis" => "^[a-zA-Z0-9]+$",             // Letters, digits, spaces

    "date" => "^\\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\\d|3[01])$", // YYYY-MM-DD
    "date_time" => "^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\s(0\d|1\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$", // YYYY-MM-DD HH:MM[:SS]

    // Prices, counts
    "flag_type" => "^[1-4]$",                        // Flag types 1-4 (Boolean, Increment, Text, DateTime)

    // Optional text fields
    "remarks" => "^[a-zA-Z0-9\\s.,!?()\\-]{3,500}$",// Free text with punctuation
    "price" => "^[0-9]+$",

    // OTP / Password
    "otp" => "^\\d{6}$",                             // 6-digit OTP
    "password" => "^.{8,}$",                         // 8+ characters
    "url"       => "^[a-zA-Z-_$]+$",
    "flag" => '^[1-4]$',
    "year"    => "^[0-9]{4}$",
    "month" => "^(0?[1-9]|1[0-2])$",
    "field" => "^[a-z_]+$",

    "reg_num" => "^[a-zA-Z0-9\\s-]{2,30}$",      
    'bharat_reg_number' => "^[0-9]{2}BH[0-9]{4}[A-Z]{1,2}$",
    'delhi_reg_number'  => "^DL[1-9](?:[0-9]|)(?:[A-Z]{1,3}|)[0-9]{4}$",
    'old_reg_number'    => "^[A-Z]{3}[0-9]{4}$",
    'number_dot'          => "^[0-9.]+",
    'pan_number'      => "^[A-Z]{3}[PCHABGJLFT][A-Z][0-9]{4}[A-Z]$",
];


    function validate_field_regex($field, $value) {
        global $patterns;

        // Case 1: If $field exists in $patterns, treat it as a name
        if (isset($patterns[$field])) {
            $regex = "/" . $patterns[$field] . "/";
        }
        // Case 2: Otherwise assume $field itself is a regex
        else {
            $regex ="/" .  $field . "/";
        }

        return preg_match($regex, $value);
    }


    function validate_fields_regex($data, $fields) {
        global $patterns;
        $errors = [];

        foreach ($fields as $field) {
            $value = $data[$field] ?? '';

            // Check empty
            if (trim($value) === '') {
                $errors[$field] = ucwords(str_replace('_', ' ', $field)) . " is required.";
                continue;
            }

            // Case 1: If $field exists in patterns, use it
            if (isset($patterns[$field])) {
                $regex = "/" . $patterns[$field] . "$/";
            }
            // Case 2: Otherwise treat $field itself as a regex
            else {
                $regex = "/" . $field . "/";
            }

            // Validate with regex
            if (!preg_match($regex, $value)) {
                $errors[$field] = "Invalid " . str_replace('_', ' ', $field) . " format.";
            }
        }
        return $errors;
    }


    function get_field_regex($field) {
        global $patterns;
        return isset($patterns[$field]) ? $patterns[$field] : null;
    }

    function file_name_filter($field, $filename) {
        global $patterns;
        $search   = array($patterns[$field], '/ +/');
        $filename = preg_replace($search, '', $filename);
        return $filename?strtolower($filename):"";
    }
    function validate_number_dots($field,$hsn_code){
        global $patterns;
        $val=0;
        if (isset($patterns[$field])) {
           $regex = "/" . $patterns[$field] . "$/";
        }
        if (!preg_match($regex, $hsn_code)) {
           $val=1;
        }
        return $val;
    }


