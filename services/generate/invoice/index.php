<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_invoice.php';

$encryptedId = $_GET['id'] ?? 0;
$id = data_decrypt($encryptedId);

$invoiceObj = new Invoices();

// Fetch the invoice data
$displayData = $invoiceObj->getInvoice($id);

if (!$displayData) {
    die("Invoice not found for ID: $id");
}

function calculateGST($amount, $rate) {
    return ($amount * $rate) / 100;
}

function amountInWords($num) {
    $num = round($num);
    if ($num == 0) return "Zero Only.";
    $a = [ "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
        "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
    $b = [ "", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
    $convert = function($n) use (&$convert, $a, $b) {
        if ($n < 20) return $a[$n];
        if ($n < 100) return $b[intval($n/10)] . ($n%10? " ".$a[$n%10] : "");
        if ($n < 1000) return $a[intval($n/100)] . " Hundred" . ($n%100? " ".$convert($n%100): "");
        if ($n < 100000) return $convert(intval($n/1000)) . " Thousand" . ($n%1000? " ".$convert($n%1000): "");
        if ($n < 10000000) return $convert(intval($n/100000)) . " Lakh" . ($n%100000? " ".$convert($n%100000): "");
        return $convert(intval($n/10000000)) . " Crore" . ($n%10000000? " ".$convert($n%10000000): "");
    };
    return $convert($num) . " Only.";
}

// Calculations
$sgstAmount = calculateGST($displayData['taxable_amt'], $displayData['sgst_rate']);
$cgstAmount = calculateGST($displayData['taxable_amt'], $displayData['cgst_rate']);
$grandTotal = $displayData['taxable_amt'] + $sgstAmount + $cgstAmount;
$roundOffAmount = round($grandTotal) - $grandTotal;
$roundedGrandTotal = round($grandTotal);
$amountWords = amountInWords($grandTotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice Preview</title>
<style>
/* Keep your existing CSS */
h1 { font-size: 36px; font-family: 'land_rover_webmedium'; font-weight: 700; margin: 15px; color:#000; }
.invoice-box { max-width:754px; padding:20px; border:1px solid #eee; font-size:14px; line-height:24px; font-family: 'avenir_nextregular'; color:#111; }
.invoice-box table { width:100%; line-height:inherit; text-align:left; border-collapse:collapse; }
.invoice-box table tr.heading td { background:#eee; border-bottom:1px solid #ddd; font-weight:bold; font-family:'land_rover_webmedium'; }
.invoice-box table tr.item td { border-bottom:1px solid #eee; }
.invoice-box table tr.total td:nth-child(2) { border-top:2px solid #eee; font-weight:bold; }
.address strong { font-size:17px; font-family:'land_rover_webmedium'; }
.address { text-align:center; font-size:13px; line-height:18px; }
.billing-details { line-height:18px; }
.terms { font-size:12px; line-height:16px; }
</style>
</head>
<body style="background:#f3f3f3; padding:20px;">

<?php for ($i = 0; $i < 3; $i++): ?>
<div style="page-break-after: always;">
<div class="invoice-box" style="background:#fff; box-shadow:0 4px 20px rgba(0,0,0,0.1);">

<div style="text-align:right; margin-bottom:10px;">
<?php 
if($i==0) echo '(Original for Recipient)';
elseif($i==1) echo '(Duplicate for Transport)';
else echo '(Triplicate for Supplier)';
?>
</div>

<table>
<tr class="top">
<td colspan="2">
  <table>
    <tr>
      <td class="title" width="170">
        <img src="/assets/images/udms-logo.png" style="width:100%; max-width:150px;">
      </td>
      <td>
        <h1 style="text-align:center;">TAX INVOICE</h1>
      </td>
    </tr>
  </table>
</td>
</tr>

<tr><td height="10"></td></tr>

<tr class="information">
<td colspan="2">
  <table>
    <tr>
      <td class="address">
        <strong><?= $displayData['branch_name'] ?></strong><br>
        <?= $displayData['branch_address'] ?><br>
        Email: <?= $displayData['branch_email'] ?> | Call: <?= $displayData['branch_mobile'] ?>
      </td>
    </tr>
  </table>
</td>
</tr>

<tr>
<td colspan="2">
  <table cellpadding="0">
    <tr>
      <td><strong>Invoice Number :</strong> <?= $displayData['invoice_number'] ?></td>
      <td><strong>Invoice Date :</strong> <?= $displayData['invoice_date'] ?></td>
    </tr>
  </table>
</td>
</tr>

<tr><td height="10"></td></tr>

<tr>
<td colspan="2">
  <table border="1" cellpadding="5" style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:50%; vertical-align:top;">
        <strong>Billing Details</strong><br>
        Sold To &nbsp;&nbsp;&nbsp;: <?= $billing['name'] ?? $displayData['customer_name'] ?><br>
        C/O <br>
        Address &nbsp;&nbsp;: <?= $billing['address'] ?? $displayData['customer_billing_address'] ?><br>
      </td>
      <td style="width:50%; vertical-align:top;">
        <strong>Delivery Details</strong><br>
        Shipped To &nbsp;: <?= $delivery['name'] ?? $displayData['customer_name'] ?><br>
        Address &nbsp;&nbsp;&nbsp;: <?= $delivery['address'] ?? $displayData['customer_address'] ?><br>
        Financed By &nbsp;: <?= $delivery['financier'] ?? $displayData['financier'] ?><br>
      </td>
    </tr>
  </table>
</td>
</tr>

<tr><td height="10"></td></tr>

<tr>
  <td colspan="2">
    <table cellpadding="5" style="width:100%; border-collapse:collapse; border:1px solid #000;">
      <tr>
        <td style="border:none;">Order No : <?= $displayData['order_id'] ?? 0 ?></td>
        <td style="border:none;">GSTIN : <?= $displayData['customer_gstin'] ?? '' ?></td>
      </tr>
      <tr>
        <td style="border:none;">Order Date : <?= $displayData['order_date'] ?? '' ?></td>
        <td style="border:none;">Aadhar : <?= $displayData['customer_aadhar'] ?? '' ?></td>
      </tr>
      <tr>
        <td style="border:none;">Mobile No : <?= $displayData['customer_mobile'] ?? '' ?></td>
        <td style="border:none;">State Name : <?= $displayData['customer_state_name'] ?? '' ?></td>
      </tr>
      <tr>
        <td style="border:none;">PAN No : <?= $displayData['customer_pan'] ?? '' ?></td>
        <td style="border:none;">State Code : <?= $displayData['customer_state_code'] ?? '' ?></td>
      </tr>
    </table>
  </td>
</tr>


<tr><td height="10"></td></tr>

<tr>
<td colspan="2">
<table border="1" cellpadding="5">
<tr class="heading">
<td>S.No</td>
<td>Description</td>
<td>Qty</td>
<td>Rate</td>
<td>Amount</td>
</tr>
<tr>
<td align="center">1</td>
<td>
Pre-Owned Car Model: <?= $displayData['make_name'] ?> <?= $displayData['model_name'] ?> <?= $displayData['variant_name'] ?><br>
Color: <br>
Registration No: <?= $displayData['registration_no'] ?><br>
Chassis No: <?= $displayData['chassis_no'] ?><br>
Engine No: <br>
HSN Code: <?= $displayData['hsn_code'] ?>
</td>
<td align="center">1</td>
<td align="right"><?= number_format($displayData['taxable_amt'],2) ?></td>
<td align="right"><?= number_format($displayData['taxable_amt'],2) ?></td>
</tr>
<tr>
<td align="center">2</td>
<td>SGST</td>
<td></td>
<td align="right"><?= $displayData['sgst_rate'] ?>%</td>
<td align="right"><?= number_format($sgstAmount,2) ?></td>
</tr>
<tr>
<td align="center">3</td>
<td>CGST</td>
<td></td>
<td align="right"><?= $displayData['cgst_rate'] ?>%</td>
<td align="right"><?= number_format($cgstAmount,2) ?></td>
</tr>
<tr>
<td></td>
<td><strong>Total (Including Taxes)</strong></td>
<td></td><td></td>
<td align="right"><strong><?= number_format($grandTotal,2) ?></strong></td>
</tr>
<tr>
<td></td>
<td>Round Off Amount</td><td></td><td></td>
<td align="right"><?= number_format($roundOffAmount,2) ?></td>
</tr>
<tr>
<td></td>
<td><strong>Grand Total</strong></td><td></td><td></td>
<td align="right"><strong><?= number_format($roundedGrandTotal,2) ?></strong></td>
</tr>
</table>
</td>
</tr>

<tr><td><strong>Amount in Words:</strong> <?= $amountWords ?></td></tr>

<tr><td height="10"></td></tr>

<tr>
<td class="terms" colspan="2">
<strong>Terms & Conditions:</strong><br>
<?= $displayData['general_terms'] ?>
</td>
</tr>

</table>
</div>
</div>
<?php endfor; ?>

</body>

</html>
