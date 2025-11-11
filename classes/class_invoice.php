<?php
class Invoices
{
    public $commonConfig;
    public $auth_user;
    public $dealer_id;
    public $stock_id;
    public $dealer;
    public $branch;
    public $branch_name;
    public $branch_address;
    public $branch_mobile;
    public $branch_email;
    public $branch_city;
    public $branch_state;
    public $branch_gstin;
    public $branch_pan;
    public $branch_state_name;
    public $branch_city_name;
    public $buylead_id;
    public $executive_id;
    public $login_user_id;
    public $branch_id;
    public $connection;   
    public $id;
    public $docs;
    public $history;

    // Customer Details
    public $customer_name;
    public $customer_mobile;
    public $customer_email;
    public $customer_pan;
    public $customer_gstin;
    public $customer_pin_code;
    public $customer_state;
    public $customer_state_name;
    public $customer_city;
    public $customer_city_name;
    public $customer_address;
    public $customer_area;
    public $customer_billing_address;
    public $billing_pin_code;
    public $billing_city;
    public $billing_city_name;
    public $billing_state;
    public $billing_state_name;

    // Vehicle Details
    public $make;
    public $model;
    public $variant;
    public $variant_name;
    public $model_name;
    public $make_name;
    public $model_code;
    public $mileage;
    public $registration_no;
    public $chassis_no;
    public $color;
    public $order_date;
    public $order_id;
    public $hsn_code;

    public $invoice_number;
    public $invoice_date;
    public $invoice_cancellation_date;
    public $invoice_type;
    public $irn_number;
    public $taxable_amt;
    public $cess_rate;
    public $discount;
    public $tcs_rate;
    public $total_amount;

    public $sgst_rate;
    public $cgst_rate;
    public $igst_rate;
    public $igst_rate_value;
    public $cgst_rate_value;
    public $sgst_rate_value;
    public $cess_rate_value;
    public $tcs_rate_value;
    public $customer_type;
    public $customer_type_view;
    public $dealer_margin;

    public $pan_card_path;
    public $gst_certificate_path;
    public $address_proof;
    public $identity_proof;

    public $opted_for_finance;
    public $opted_for_finance_view;
    public $financier;
    public $downpayment;
    public $advance_amount_paid;
    public $finance_amount;
    public $remarks;
    public $finance_doc;
    public $rate_of_intrest;
    public $emi;
    public $tenure;
    public $assured_buyback;
    public $assured_buyback_view;
    public $finance_type;
    public $roundoff_amt ;

    public $status;
    public $status_name;
    public $sub_status;
    public $created;
    public $created_by;
    public $updated;
    public $updated_name;
    public $updated_by;
    

    // Status map as class property
    private $status_map = [
        "all" => ["status_id" => "all", "label" => "All", "is_active" => "y"],
        1     => ["status_id" => "1", "label" => "Draft"],
        2     => ["status_id" => "2", "label" => "Issued"],
        3     => ["status_id" => "3", "label" => "Cancelled", "sub" => [
            "pending"            => ["status_id" => "1","label" => "Pending"],
            "approved"           => ["status_id" => "2","label" => "Approved"],
            "rejected"           => ["status_id" => "3","label" => "Rejected"],
            "request-correction" => ["status_id" => "4", "label" => "Request Correction"]
        ]]
    ];

    public function __construct($id = 0)
    {
        global $connection;
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        $this->id = (int)$id;

        if ($id > 0) {
            $invoice = $this->getInvoice($id);
            if (!empty($invoice)) {
                foreach ($invoice as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
    }

    public function getStatus($id){
        $where = "WHERE 1=1";
        if($this->auth_user == 'dealer'){
            $where = " WHERE id = ". $id ;
        }
       $query = "SELECT status, sub_status FROM invoices $where LIMIT 1";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-getStatus', true);
            return false;
        }
        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            return false;
        }
        return [
            'status' => (int)$row['status'],
            'sub_status' => $row['sub_status']
        ]; 
    }

    /**
     * Add new invoice
     */
    public function addInvoice($data)
    {
        $cols = [];
        $vals = [];

        foreach ($data as $key => $val) {
            $cols[] = "`$key`";
            $vals[] = "'" . mysqli_real_escape_string($this->connection, $val) . "'";
        }

        $sql = "INSERT INTO invoices (" . implode(",", $cols) . ") 
                VALUES (" . implode(",", $vals) . ")";
        $res = mysqli_query($this->connection, $sql);

        if ($res) {
            $new_id = mysqli_insert_id($this->connection);
            $this->update_invoice_number($new_id);
            return $new_id;
        } else {
            logSqlError(mysqli_error($this->connection), $sql, 'invoices-add', true);
            return false;
        }
    }

    /**
     * Update invoice by ID
     */
    public function updateCustomerData($id, $data){
        $query = "update invoices set 
                    customer_type = '". mysqli_real_escape_string($this->connection, $data['customer_type']) ."',
                    customer_name = '". mysqli_real_escape_string($this->connection, $data['customer_name']) ."',
                    customer_mobile = '". mysqli_real_escape_string($this->connection, $data['customer_mobile']) ."',
                    customer_email = '". mysqli_real_escape_string($this->connection, $data['customer_email']) ."',
                    customer_pan = '". mysqli_real_escape_string($this->connection, $data['customer_pan']) ."',
                    customer_gstin = '". mysqli_real_escape_string($this->connection, $data['customer_gstin']) ."',
                    customer_address = '". mysqli_real_escape_string($this->connection, $data['customer_address']) ."',
                    customer_area = '". mysqli_real_escape_string($this->connection, $data['customer_area']) ."',
                    customer_pin_code = '". mysqli_real_escape_string($this->connection, $data['customer_pin_code']) ."',
                    billing_pin_code = '". mysqli_real_escape_string($this->connection, $data['billing_pin_code']) ."',
                    customer_state = '". mysqli_real_escape_string($this->connection, $data['customer_state']) ."',
                    customer_city = '". mysqli_real_escape_string($this->connection, $data['customer_city']) ."',
                    customer_billing_address = '". mysqli_real_escape_string($this->connection, $data['customer_billing_address']) ."',
                    billing_state = '". mysqli_real_escape_string($this->connection, $data['billing_state']) ."',
                    billing_city = '". mysqli_real_escape_string($this->connection, $data['billing_city']) ."',
                    updated = NOW(),
                    updated_by = ". intval($this->login_user_id) ."
                WHERE id = ". intval($id) ;
        $res = mysqli_query($this->connection, $query);
        if ($res) {
            logTableInsertion('invoices', $id);
            return true;
        } else {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-update-customer', true);
            return false;
        }
    }
    public function updateInvoiceData($id, $data){
        $query = "update invoices set 
                    invoice_date = '". mysqli_real_escape_string($this->connection, $data['invoice_date']) ."',
                    invoice_type = '". mysqli_real_escape_string($this->connection, $data['invoice_type']) ."',
                    irn_number = '". mysqli_real_escape_string($this->connection, $data['irn_number']) ."',
                    taxable_amt = '". mysqli_real_escape_string($this->connection, $data['taxable_amt']) ."',
                    discount = '". mysqli_real_escape_string($this->connection, $data['discount']) ."',
                    sgst_rate = '". mysqli_real_escape_string($this->connection, $data['sgst_rate']) ."',
                    cgst_rate = '". mysqli_real_escape_string($this->connection, $data['cgst_rate']) ."',
                    igst_rate = '". mysqli_real_escape_string($this->connection, $data['igst_rate']) ."',
                    tcs_rate = '". mysqli_real_escape_string($this->connection, $data['tcs_rate']) ."',
                    cess_rate = '". mysqli_real_escape_string($this->connection, $data['cess_rate']) ."',
                    total_amount = '". mysqli_real_escape_string($this->connection, $data['total_amount']) ."',
                    dealer_margin = '". mysqli_real_escape_string($this->connection, $data['dealer_margin']) ."',
                    discount = '". mysqli_real_escape_string($this->connection, $data['discount']) ."',
                    updated = NOW(),
                    updated_by = ". intval($this->login_user_id) ."
                WHERE id = ". intval($id) ;
        $res = mysqli_query($this->connection, $query);
        if ($res) {
            logTableInsertion('invoices', $id);
            return true;
        } else {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-update-invoice', true);
            return false;
        }
    }
    public function updatePaymentData($id, $data) {
        $query = "update invoices set 
                    opted_for_finance = '". mysqli_real_escape_string($this->connection, $data['opted_for_finance']) ."',
                    financier = '". mysqli_real_escape_string($this->connection, $data['financier']) ."',
                    downpayment = '". mysqli_real_escape_string($this->connection, $data['downpayment']) ."',
                    advance_amount_paid = '". mysqli_real_escape_string($this->connection, $data['advance_amount_paid']) ."',
                    finance_amount = '". mysqli_real_escape_string($this->connection, $data['finance_amount']) ."',
                    rate_of_intrest = '". mysqli_real_escape_string($this->connection, $data['rate_of_intrest']) ."',
                    emi = '". mysqli_real_escape_string($this->connection, $data['emi']) ."',
                    tenure = '". mysqli_real_escape_string($this->connection, $data['tenure']) ."',
                    finance_type = '". mysqli_real_escape_string($this->connection, $data['finance_type']) ."',
                    assured_buyback = '". mysqli_real_escape_string($this->connection, $data['assured_buyback']) ."',
                    remarks = '". mysqli_real_escape_string($this->connection, $data['remarks']) ."',
                    updated = NOW(),
                    updated_by = ". intval($this->login_user_id) ."
                WHERE id = ". intval($id) ;
        $res = mysqli_query($this->connection, $query);
        if ($res) {
            logTableInsertion('invoices', $id);
            return true;
        } else {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-update-payment', true);
            return false;
        }
    }
    public function updateDocumentData($id, $data){
        $query = "update invoices set 
                    pan_card_path = '". mysqli_real_escape_string($this->connection, $data['pan_card_path']) ."',
                    gst_certificate_path = '". mysqli_real_escape_string($this->connection, $data['gst_certificate_path']) ."',
                    address_proof = '". mysqli_real_escape_string($this->connection, $data['address_proof']) ."',
                    identity_proof = '". mysqli_real_escape_string($this->connection, $data['identity_proof']) ."',
                    updated = NOW(),
                    updated_by = ". intval($this->login_user_id) ."
                WHERE id = ". intval($id) ;
        $res = mysqli_query($this->connection, $query);
        if ($res) {
            logTableInsertion('invoices', $id);
            return true;
        } else {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-update-document', true);
            return false;
        }
    }
    public function updateInvoice($id, $data)
    {
        $updates = [];

        foreach ($data as $key => $val) {
            $updates[] = "`$key`='" . mysqli_real_escape_string($this->connection, $val) . "'";
        }

        // Always update the `updated` column
        $updates[] = "`updated` = NOW(), `updated_by` = " . intval($this->login_user_id);

        $where = "WHERE 1=1";
        if($auth_user == 'dealer'){
            $where = "WHERE `dealer` = " . intval($this->dealer_id);
        }

        $sql = "UPDATE invoices 
                SET " . implode(", ", $updates) . " 
                $where AND id = " . intval($id) ;

        $res = mysqli_query($this->connection, $sql);
        if ($res) {
            $this->update_invoice_number($id);
            logTableInsertion('invoices', $id);
            return true;
        }else {
            logSqlError(mysqli_error($this->connection), $sql, 'invoices-update', true);
            return false;
        }
    }

    public function getInvoiceHistory($id){
        $where = "WHERE 1=1";
        if($this->auth_user == 'dealer'){
            $where = " WHERE i.invoice_id = ". intval($id) ;
        }
        if (intval($id) <= 0) { return []; }
        $query = "SELECT 
                    i.*, 
                    u.name AS updated_name,
                    b.name AS branch_name, b.address AS branch_address, b.contact_mobile AS branch_mobile, 
                    b.contact_email AS branch_email, b.city AS branch_city, b.state AS branch_state,
                    msa_billing.cw_state AS billing_state_name, msa_billing.cw_city AS billing_city_name,
                    msa_customer.cw_state AS customer_state_name, msa_customer.cw_city AS customer_city_name,
                    msa_branch.cw_state AS branch_state_name, msa_branch.cw_city AS branch_city_name,
                    v.variant AS variant_name, v.model AS model_name, v.make AS make_name
                FROM invoices_log i
                LEFT JOIN users u ON i.updated_by = u.id
                LEFT JOIN dealer_branches b ON i.branch = b.id
                LEFT JOIN master_states_areaslist msa_billing ON msa_billing.cw_zip = i.billing_pin_code
                LEFT JOIN master_states_areaslist msa_customer ON msa_customer.cw_zip = i.customer_pin_code
                LEFT JOIN master_states_areaslist msa_branch ON msa_branch.cw_zip = b.pin_code
                LEFT JOIN master_variants_new v ON (v.make_id = i.make AND v.model_id = i.model AND v.id = i.variant)
                $where ORDER BY i.id DESC";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'invoices-getHistory', true);
            return false;
        }
        $history = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['customer_type'] = isset($this->commonConfig['customer_type'][$row['customer_type']]) ? $this->commonConfig['customer_type'][$row['customer_type']] : $row['customer_type']; 
            $row['opted_for_finance_view'] = isset($this->commonConfig['boolean'][$row['opted_for_finance']]) ? $this->commonConfig['boolean'][$row['opted_for_finance']] : $row['opted_for_finance'];
            $row['assured_buyback_view'] = isset($this->commonConfig['boolean'][$row['assured_buyback']]) ? $this->commonConfig['boolean'][$row['assured_buyback']] : $row['assured_buyback'];
            $row['status_name'] = isset($this->status_map[$row['status']]['label']) ? $this->status_map[$row['status']]['label'] : $row['status'];
            // documents
            // $row['docs'] = (array) $this->processDocuments($row);
            $history[] = $row;
        }
        return $history;                                                       
    }

    /**
     * Get single invoice
     */

    public function getInvoice($id)
    {
        $where = " WHERE i.id = " . intval($id);
        if ($this->auth_user == 'dealer') {
            $where .= " AND i.dealer = " . intval($this->dealer_id);
        }

        $sql = "
            SELECT 
                i.*, 
                b.name AS branch_name, b.address AS branch_address, b.contact_mobile AS branch_mobile, 
                b.contact_email AS branch_email, b.city AS branch_city, b.state AS branch_state,
                b.gstin AS branch_gstin, b.pan AS branch_pan, b.general_terms, b.payment_terms,
                v.variant AS variant_name, v.model AS model_name, v.make AS make_name,
                msa_billing.cw_state AS billing_state_name, msa_billing.cw_city AS billing_city_name,
                msa_customer.cw_state AS customer_state_name,  msa_customer.cw_city AS customer_city_name,
                msa_branch.cw_state AS branch_state_name,  msa_branch.cw_city AS branch_city_name
            FROM invoices i
            LEFT JOIN dealer_branches b ON i.branch = b.id
            LEFT JOIN master_states_areaslist msa_billing ON msa_billing.cw_zip = i.billing_pin_code
            LEFT JOIN master_states_areaslist msa_customer ON msa_customer.cw_zip = i.customer_pin_code
            LEFT JOIN master_states_areaslist msa_branch ON msa_branch.cw_zip = b.pin_code
            LEFT JOIN master_variants_new v ON (v.make_id = i.make AND v.model_id = i.model AND v.id = i.variant)
            $where";

        $res = mysqli_query($this->connection, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            if (isset($row['id'])) {
                $row['id'] = data_encrypt($row['id']);
            }

            $taxable_amt = isset($row['taxable_amt'])
                ? floatval(str_replace(',', '', (string)$row['taxable_amt']))
                : 0.0;

            $tax_fields = ['igst_rate', 'sgst_rate', 'cgst_rate', 'tcs_rate', 'cess_rate'];
            foreach ($tax_fields as $field) {
                $rate = isset($row[$field]) ? floatval($row[$field]) : 0.0;
                $row["{$field}_value"] = (string) round(($taxable_amt * $rate) / 100.0, 2);
            }

            $row['customer_type_view'] = $this->commonConfig['customer_type'][$row['customer_type']] ?? '';
            $row['opted_for_finance_view'] = $this->commonConfig['boolean'][$row['opted_for_finance']] ?? '';
            $row['assured_buyback_view'] = $this->commonConfig['boolean'][$row['assured_buyback']] ?? '';
            $row['status_name'] = $this->status_map[$row['status']]['label'] ?? $row['status'];

            $docs = (array) $this->processDocuments($row);
            $history = $this->getInvoiceHistory($id);

            $moduleConfig = new moduleConfig();
            $menu = $moduleConfig->getConfig('invoice', 'menu', $row);

            $final = [
                'id'                    => $row['id'] ?? '',
                'detail'                => $row,
                'documents'             => $docs,
                'history'               => $history,
                'menu'                  => $menu,
            ];

            return $final;
        }

        // empty response if no data found
        return [
            'id'                    => '',
            'detail'                => [],
            'documents'             => [],
            'history'               => [],
            'menu'                  => [],
        ];
    }


    public function getInvoices($filters = [], $status = null, $sub_status = null, $current_page = 1, $per_page = 10)
    {
        // Build filter condition (ONLY filters, no status/sub_status)
        $filterCondition = " WHERE 1=1 ";
        foreach ($filters as $key => $val) {
            $val = mysqli_real_escape_string($this->connection, $val);
            if ($val !== '') {
                $filterCondition .= " AND `" . mysqli_real_escape_string($this->connection, $key) . "` LIKE '%$val%'";
            }
        }

        $statusCondition = "";
        if ($status != null && $status != '' && $status != 'all') {
            $statusCondition .= " AND i.status = " . intval($status);
        }

        if ($sub_status != null && $sub_status != '') {
            $statusCondition .= " AND i.sub_status = " . intval($sub_status);
        }

        $dealerCondition = "";
        if($this->auth_user == 'dealer'){
            $dealerCondition = " AND i.dealer = ". intval($this->dealer_id) ;
        }

        $count_sql = "SELECT COUNT(*) as total FROM invoices i" . $filterCondition . $statusCondition . $dealerCondition ;
        $count_res = mysqli_query($this->connection, $count_sql);
        $total = ($count_res && $row = mysqli_fetch_assoc($count_res)) ? (int)$row['total'] : 0;

        $pages = ($per_page > 0) ? ceil($total / $per_page) : 1;
        $start = ($current_page - 1) * $per_page;

        $sql = "SELECT i.* , v.variant as variant_name, v.model as model_name, v.make as make_name 
                FROM invoices i
                LEFT JOIN master_variants_new v ON (v.make_id = i.make AND v.model_id = i.model AND v.id = i.variant)
                " . $filterCondition . $statusCondition . $dealerCondition ." 
                ORDER BY i.id DESC 
                LIMIT $start, $per_page";

        $res = mysqli_query($this->connection, $sql);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $sql, 'invoices-get', true);
        }

        $list = [];
        while ($res && $row = mysqli_fetch_assoc($res)) {
            if (isset($row['id'])) {
                $row['id'] = data_encrypt($row['id']);
            }
            $row['status_name'] = isset($this->status_map[$row['status']]['label'])
                ? $this->status_map[$row['status']]['label']
                : $row['status'];
            $row['docs'] = (array) $this->processDocuments($row);
            $list[] = $row;
        }

        // Pass only filterCondition to getStatuses (so counts are based on filters only, not the currently selected status/sub_status)
        $menu = $this->getStatuses($filterCondition);

        return [
            "pagination" => [
                "total" => $total,
                "pages" => $pages,
                "per_page" => $per_page,
                "current_page" => (int)$current_page,
                "start_count" => $total > 0 ? ($start + 1) : 0,
                "end_count" => min($start + $per_page, $total)
            ],
            "list" => $list,
            "menu" => $menu
        ];
    }

    private function update_invoice_number($invoice_id)
    {
        $branch_code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->branch_name), 0, 3));
        $buylead_id  = $this->buylead_id ?: '0';
        $stock_id    = $this->stock_id ?: '0';
        $invoice_date = !empty($this->invoice_date) ? strtotime($this->invoice_date) : time();

        $date_str = date('YmdHis', $invoice_date);
        $invoice_number = "INV-{$branch_code}-{$buylead_id}-{$stock_id}-{$date_str}";

        $update_sql = "
            UPDATE invoices 
            SET invoice_number = '" . mysqli_real_escape_string($this->connection, $invoice_number) . "' 
            WHERE id = " . intval($invoice_id);

        $update_res = mysqli_query($this->connection, $update_sql);
        if (!$update_res) {
            logSqlError(mysqli_error($this->connection), $update_sql, 'invoices-update-invno-save', true);
            return false;
        }

        return $invoice_number;
    }

    private function processDocuments(array $lead): object {
        $documents = [];
        $baseUrl = rtrim($this->commonConfig['document_base_url'] ?? '', '/') . '/';
        
        $docFields = [
            'pan_card_path' => 'PAN Card',
            'gst_certificate_path' => 'GST Certificate',
            'address_proof'  => 'Address Proof',
            'identity_proof' => 'Identity Proof'
        ];

        foreach ($docFields as $key => $displayName) {
            if (!empty($lead[$key]) && trim($lead[$key]) !== '') {
                $documents[$key] = (object)[
                    'name' => $displayName,
                    'url' => $baseUrl . trim($lead[$key]),
                    'key' => $key
                ];
            }
        }

        return (object)$documents;
    }

    /**
     * Get status + sub-status counts
     */
    public function getStatuses($condition = " WHERE 1=1 ")
    {
        $menu = [];
        $dealerCondition = "";
        if($this->auth_user == 'dealer'){
            $dealerCondition = "  AND dealer = ". intval($this->dealer_id) ;
        }
        // All
        $sql_all = "SELECT COUNT(*) as cnt FROM invoices $condition $dealerCondition";
        $cnt_all = ($res_all = mysqli_query($this->connection, $sql_all)) && ($row_all = mysqli_fetch_assoc($res_all)) 
            ? $row_all['cnt'] : 0;
        $menu['all'] = array_merge($this->status_map['all'], ["count" => (int)$cnt_all]);

        // Other statuses
        foreach ($this->status_map as $key => $meta) {
            if ($key === "all") continue;

            $status_id = intval($key);

            $sql = "SELECT COUNT(*) as cnt FROM invoices $condition $dealerCondition AND status = $status_id";
            $cnt = ($res = mysqli_query($this->connection, $sql)) && ($row = mysqli_fetch_assoc($res)) 
                ? $row['cnt'] : 0;

            if ($status_id === 3 && !empty($meta['sub'])) { // Cancelled
                $sub = [];
                foreach ($meta['sub'] as $sub_key => $sub_meta) {
                    $sub_status_id = intval($sub_meta['status_id'] ?? 0);

                    $q = "SELECT COUNT(*) as cnt 
                        FROM invoices 
                        $condition $dealerCondition AND status = 3
                        AND sub_status = $sub_status_id";

                    $res_sub = mysqli_query($this->connection, $q);
                    if (!$res_sub) {
                        logSqlError(mysqli_error($this->connection), $q, 'invoice-get-sub');
                        $sub_cnt = 0;
                    } else {
                        $row_sub = mysqli_fetch_assoc($res_sub);
                        $sub_cnt = $row_sub['cnt'] ?? 0;
                    }

                    $sub[$sub_key] = array_merge($sub_meta, ["count" => (int)$sub_cnt]);
                }

                $menu['cancelled'] = array_merge($meta, ["count" => (int)$cnt, "sub" => $sub]);
            } else {
                $menu[strtolower($meta['label'])] = array_merge($meta, ["count" => (int)$cnt]);
            }
        }

        return $menu;
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice($id)
    {
        $sql = "DELETE FROM invoices WHERE id = " . intval($id);
        return mysqli_query($this->connection, $sql);
    }

    //  export functionality not in use
     public function exportInvoice($filters = []){
        $main_headers = [
            ['name' => 'Branch Details', 'colspan' => 7 ],
            ['name' => 'Customer Details',  'colspan' => 11 ],
            ['name' => 'Billing Address', 'colspan' => 4 ],
            ['name' => 'Vehicle Details',    'colspan' => 8 ],
            ['name' => 'Invoice Details',  'colspan' => 20],
        ];

        $headers = [
            ['name' => 'ID', 'type' => 'number', 'value' => 'id'],
            ['name' => 'Branch Name', 'type' => 'string', 'value' => 'branch_name'],
            ['name' => 'Branch Address', 'type' => 'string', 'value' => 'branch_address'],
            ['name' => 'Branch Mobile', 'type' => 'string', 'value' => 'branch_mobile'],
            ['name' => 'Branch Email', 'type' => 'string', 'value' => 'branch_email'],
            ['name' => 'Branch City', 'type' => 'string', 'value' => 'branch_city'],
            ['name' => 'Branch State', 'type' => 'string', 'value' => 'branch_state_name'],

            ['name' => 'Customer Type', 'type' => 'string', 'value' => 'customer_type', 'config' => 'customer_type'],
            ['name' => 'Customer Name', 'type' => 'string', 'value' => 'customer_name'],
            ['name' => 'Customer Mobile', 'type' => 'string', 'value' => 'customer_mobile'],
            ['name' => 'Customer Email', 'type' => 'string', 'value' => 'customer_email'],
            ['name' => 'Customer PAN', 'type' => 'string',  'value' =>  'customer_pan'],
            ['name' => 'Customer GSTIN',  'type' =>  'string',  'value' =>  'customer_gstin'],
            ['name' => 'Customer Address',  'type' =>  'string',  'value' =>  'customer_address'],
            ['name' => 'Customer Area', 'type' => 'string', 'value' => 'customer_area'],
            ['name' => 'Customer Pin Code', 'type' => 'string', 'value' => 'customer_pin_code'],
            ['name' => 'Customer City', 'type' => 'string', 'value' => 'customer_city_name'],
            ['name' => 'Customer State', 'type' => 'string', 'value' => 'customer_state_name'],

            ['name' => 'Billing Address',  'type' =>  'string',  'value' =>  'customer_billing_address'],
            ['name' => 'Billing Pin Code',  'type' =>  'string',  'value' =>  'billing_pin_code'],
            ['name' => 'Billing City',  'type' =>  'string',  'value' =>  'billing_city_name'],
            ['name' => 'Billing State',  'type' =>  'string',  'value' =>  'billing_state_name'],

            ['name' => 'Make', 'type' => 'string', 'value' => 'make_name'],
            ['name' => 'Model', 'type' => 'string', 'value' => 'model_name'],
            ['name' => 'Variant', 'type' => 'string', 'value' => 'variant_name'],
            ['name' => 'Mileage',  'type' =>  'string',  'value' =>  'mileage'],
            ['name' => 'Registration No.',  'type' =>  'string',  'value' =>  'registration_no'],
            ['name' => 'Order ID',  'type' =>  'string',  'value' =>  'order_id'],
            ['name' => 'Order Date',  'type' =>  'date',  'value' =>  'order_date'],
            ['name' => 'HSN Code',  'type' =>  'string',  'value' =>  'hsn_code'],

            ['name' => 'Invoice Number',  'type' =>  'string',  'value' =>  'invoice_number'],
            ['name' => 'Invoice Date',  'type' =>  'date',  'value' =>  'invoice_date'],
            ['name' => 'Invoice Cancellation Date',  'type' =>  'date',  'value' =>  'invoice_cancellation_date'],
            ['name' => 'Invoice Type',  'type' =>  'string',  'value' =>  'invoice_type'],
            ['name' => 'Base Price',  'type' =>  'number',  'value' =>  'taxable_amt'],
            ['name' => 'Discount',  'type' =>  'number',  'value' =>  'discount'],
            ['name' => 'Cess Rate (%)',  'type' =>  'number',  'value' =>  'cess_rate'],
            ['name' => 'Cess Value',  'type' =>  'number',  'value' =>  'cess_rate_value'],
            ['name' => 'TCS Rate (%)',  'type' =>  'number',  'value' =>  'tcs_rate'],
            ['name' => 'TCS Value',  'type' =>  'number',  'value' =>  'tcs_rate_value'],
            ['name' => 'SGST Rate (%)',  'type' =>  'number',  'value' =>  'sgst_rate'],
            ['name' => 'SGST Value',  'type' =>  'number',  'value' =>  'sgst_rate_value'],
            ['name' => 'CGST Rate (%)',  'type' =>  'number',  'value' =>  'cgst_rate'],
            ['name' => 'CGST Value',  'type' =>  'number',  'value' =>  'cgst_rate_value'],
            ['name' => 'IGST Rate (%)',  'type' =>  'number',  'value' =>  'igst_rate'],
            ['name' => 'IGST Value',  'type' =>  'number',  'value' =>  'igst_rate_value'],
            ['name' => 'Total Amount',  'type' =>  'number',  'value' =>  'total_amount'],
            ['name' => 'Dealer Margin',  'type' =>  'number',  'value' =>  'dealer_margin'],
            ['name' => 'Status',  'type' =>  'string',  'value' =>  'status_name'],
            ['name' => 'Sub Status',  'type' =>  'string',  'value' =>  'sub_status_name'],
            // ['name' => 'Created On',  'type' =>  'date',  'value' =>  'created'],
            // ['name' => 'Created By',  'type' =>  'string',  'value' =>  'created_by'],
            // ['name' => 'Updated On',  'type' =>  'date',  'value' =>  'updated'],
            // ['name' => 'Updated By',  'type' =>  'string',  'value' =>  'updated_by'],
        ];

        if($this->auth_user == 'dealer'){
            $where = " WHERE i.dealer = ". intval($this->dealer_id) ;
        }
        else if($this->auth_user == 'admin'){
            $where = " WHERE 1=1 " ;
        }

        foreach ($filters as $key => $val) {
            $val = mysqli_real_escape_string($this->connection, $val);
            if ($val !== '') {
                $where .= " AND i.`" . mysqli_real_escape_string($this->connection, $key) . "` LIKE '%$val%'";
            }
        }

        $query = "SELECT 
                i.*, 
                b.name AS branch_name, 
                b.address AS branch_address, 
                b.contact_mobile AS branch_mobile, 
                b.contact_email AS branch_email, 
                b.city AS branch_city, b.state AS branch_state,
                v.variant AS variant_name, v.model AS model_name, v.make AS make_name,
                msa_billing.cw_state AS billing_state_name, msa_billing.cw_city AS billing_city_name,
                msa_customer.cw_state AS customer_state_name,  msa_customer.cw_city AS customer_city_name,
                msa_branch.cw_state AS branch_state_name,  msa_branch.cw_city AS branch_city_name
            FROM invoices i
            LEFT JOIN dealer_branches b ON i.branch = b.id
            LEFT JOIN master_states_areaslist msa_billing ON msa_billing.cw_zip = i.billing_pin_code
            LEFT JOIN master_states_areaslist msa_customer ON msa_customer.cw_zip = i.customer_pin_code
            LEFT JOIN master_states_areaslist msa_branch ON msa_branch.cw_zip = b.pin_code
            LEFT JOIN master_variants_new v ON (v.make_id = i.make AND v.model_id = i.model AND v.id = i.variant)
            $where
            ORDER BY i.id DESC";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, 'sellleads-export', true);
        }

        $data = [];
        $config = [];
        $config_file = $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        if (file_exists($config_file)) {
            $config = require $config_file;
        }

        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $status_label = '';
                $status_val = $row['status'] ?? null;
                $status_int = is_numeric($status_val) ? intval($status_val) : null;

                if ($status_int !== null && isset($this->status_map[$status_int]['label'])) {
                    $status_label = $this->status_map[$status_int]['label'];
                }

                if ($status_int === 3 && !empty($this->status_map[3]['sub'])) {
                    $sub_val = $row['sub_status'] ?? '';
                    $sub_label = '';
                    foreach ($this->status_map[3]['sub'] as $sub_key => $sub_meta) {
                        if ((isset($sub_meta['status_id']) && (string)$sub_meta['status_id'] === (string)$sub_val)
                            || (string)$sub_key === (string)$sub_val) {
                            $sub_label = $sub_meta['label'];
                            break;
                        }
                    }
                }

                $row['status_name'] = $status_label;
                $row['sub_status_name'] = $sub_label ?? '';

                $taxable_amt = isset($row['taxable_amt']) ? floatval(str_replace(',', '', (string)$row['taxable_amt'])) : 0.0;
                $tax_fields = ['igst_rate', 'sgst_rate', 'cgst_rate', 'tcs_rate', 'cess_rate'];
                foreach ($tax_fields as $field) {
                    $rate = isset($row[$field]) ? floatval($row[$field]) : 0.0;
                    $row["{$field}_value"] = (string) round(($taxable_amt * $rate) / 100.0, 2);
                }

                $rowData = [];
                foreach ($headers as $header) {
                    $col = $header['value'];
                    $val = $row[$col] ?? '';
                    if (!empty($header['config']) && isset($config[$header['config']]) && $val !== '') {
                        $rowData[] = $config[$header['config']][$val] ?? $val;
                    } else {
                        $rowData[] = $val;
                    }
                }
                $data[] = $rowData;
            }
        }

        $filename = "invoice_".date('Ymd_His').".xlsx";
        $url = exportExcelFile($headers, $data, $filename, $main_headers);
        return ['file_url' => $url];
    }

   
}
?>