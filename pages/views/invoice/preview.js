export const Preview = {
  name: "Preview",
  props: { store: { type: Object, required: true } },

  data() {
    return {
      isDownloading: false,
      pages: [
        "<i>(Original for Recipient)</i>",
        "<i>(Duplicate for Transport)</i>",
        "<i>(Triplicate for Supplier)</i>",
      ],
    };
  },

  computed: {
    displayData() {
      return this.store?.getDetails || {};
    },

    // SGST amount calculated from taxable_amt and sgst_rate
    sgstAmount() {
      const base = Number((this.displayData.taxable_amt || 0).toString().replace(/[,₹\s]/g, "")) || 0;
      const rate = Number((this.displayData.sgst_rate || 0).toString().replace(/[,%\s]/g, "")) || 0;
      const amt = (base * rate) / 100;
      return amt.toFixed(2);
    },

    // CGST amount calculated from taxable_amt and cgst_rate
    cgstAmount() {
      const base = Number((this.displayData.taxable_amt || 0).toString().replace(/[,₹\s]/g, "")) || 0;
      const rate = Number((this.displayData.cgst_rate || 0).toString().replace(/[,%\s]/g, "")) || 0;
      const amt = (base * rate) / 100;
      return amt.toFixed(2);
    },

    // Total including taxes (base + sgst + cgst)
    grandTotal() {
      const base = Number((this.displayData.taxable_amt || 0).toString().replace(/[,₹\s]/g, "")) || 0;
      const sgst = Number(this.sgstAmount) || 0;
      const cgst = Number(this.cgstAmount) || 0;
      const total = base + sgst + cgst;
      return total.toFixed(2);
    },

    roundOffAmount() {
      const total = Number(this.grandTotal) || 0;
      const rounded = Math.round(total);
      const diff = rounded - total;
      return diff.toFixed(2);
    },

    roundedGrandTotal() {
      return Math.round(Number(this.grandTotal) || 0).toFixed(2);
    },

    amountInWords() {
      const n = Math.round(Number(this.grandTotal) || 0);
      if (n === 0) return "Zero Only.";
      const a = [ "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
        "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen",];
      const b = [ "", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety",];

      const convert = (num) => {
        num = Number(num);
        if (num < 20) return a[num];
        if (num < 100) return (b[Math.floor(num / 10)] + (num % 10 ? " " + a[num % 10] : "")).trim();
        if (num < 1000) return ( a[Math.floor(num / 100)] + " Hundred" + (num % 100 === 0 ? "" : " " + convert(num % 100))).trim();
        if (num < 100000) return ( convert(Math.floor(num / 1000)) + " Thousand" + (num % 1000 === 0 ? "" : " " + convert(num % 1000))).trim();
        if (num < 10000000) return ( convert(Math.floor(num / 100000)) + " Lakh" + (num % 100000 === 0 ? "" : " " + convert(num % 100000))).trim();
        return ( convert(Math.floor(num / 10000000)) + " Crore" + (num % 10000000 === 0 ? "" : " " + convert(num % 10000000))).trim();
      };

      return convert(n).replace(/\s+/g, " ").trim() + " Only.";
    },
  },

  methods: {
    async downloadPDF() {
      if (!this.store || this.isDownloading) return;

      this.isDownloading = true;

      try {
        const result = await this.store.download_preview(this.displayData.id);
      } catch (err) {
        console.error("downloadPDF error:", err);
      } finally {
        this.isDownloading = false;
      }
    },
    getPageHTML(label) {
      return `
      <style>
        h1 {
          font-size: 36px;
          font-family: 'LandRoverWeb-Bold';
          font-weight: 700;
          margin: 0px !important;
          padding-bottom: 0px;
          color: #000;
        }
        .invoice-box {
          max-width: 754px;
          padding: 20px;
          border: 1px solid #eee;
          font-size: 14px;
          line-height: 24px;
          font-family: 'Avenir Next';
          color: #111111;
        }

        .invoice-box table {
          width: 100%;
          line-height: inherit;
          text-align: left;
          border-collapse: collapse;
        }

        .invoice-box table tr.top table td.title {
          font-size: 45px;
          color: #333;
        }

        .invoice-box table tr.information table td {
          padding-bottom: 10px;
        }

        .invoice-box table tr.heading td {
          background: #eee;
          border-bottom: 1px solid #ddd;
          font-weight: bold;
          font-family: 'LandRoverWeb-Bold';
        }

        .invoice-box table tr.details td {
          padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
          border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
          border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
          border-top: 2px solid #eee;
          font-weight: bold;
        }

        .address strong {
          font-size: 17px;
          font-family: 'LandRoverWeb-Bold';
        }

        .address {
          text-align: center;
          font-size: 13px;
          line-height: 18px;
        }

        .billing-details {
          line-height: 18px;
        }

        .terms {
          font-size: 12px;
          line-height: 16px;
        }
        
        .download-btn {
          position: sticky;
          top: 10px;
          align-self: flex-end;
          z-index: 100;
          border-radius: 10%;
          width: 40px;  
          height: 40px; 
          display: flex;
          justify-content: center;
          align-items: center;
          border: 1px solid black;
          background-color: white;
          transition: all 0.3s ease;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .download-btn i {
          font-size: 18px;
          color: black;
          transition: color 0.3s ease;
        }

        .spin {
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

      </style>

      <div style="margin-bottom: 10px; font-size:14px; text-align:right;">${label}</div>

      <div class="invoice-box">
        <table class="header w-100">
          <tr class="top">
            <td colspan="2">
              <table class="header">
                <td class="title" width="170">
                  <img src="/assets/images/udms-logo.png" alt="udms" style="width: 100%; max-width: 150px;" />
                </td>
                <td bgcolor="#E6E5E2">
                  <h1 class="text-center mb-0">TAX INVOICE</h1>
                </td>
              </table>
            </td>
          </tr>

          <tr><td height="5"></td></tr>

          <tr class="information">
            <td colspan="2">
              <table>
                <tr>
                  <td class="address">
                    <strong>${this.displayData.branch_name || "Dealer Name"}</strong><br>
                    ${this.displayData.branch_address || ""}<br>
                    Email: ${this.displayData.branch_email || ""} | Call: ${this.displayData.branch_mobile || ""}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td colspan="2">
              <table cellpadding="0" class="header">
                <tr>
                  <td><strong>Invoice Number :</strong> ${this.displayData.invoice_number || ""}</td>
                  <td align="right"><strong>Invoice Date :</strong> ${this.displayData.invoice_date || ""}</td>
                </tr>
              </table>
            </td>
          </tr>

          <tr class="top">
            <td colspan="2">
              <table style="border:1px solid #333333" cellpadding="10" width="100%">
                <tr>
                  <td class="billing-details" width="50%">
                    <table>
                      <tr><td colspan="3"><strong>Billing Details</strong></td></tr>
                      <tr><td width="90">Sold To</td><td width="10">:</td><td>${this.displayData.customer_name || ""}</td></tr>
                      <tr><td>C/O</td><td>:</td><td>${this.displayData.customer_co || ""}</td></tr>
                      <tr>
                        <td>Address</td>
                        <td>:</td>
                        <td>${this.displayData.customer_billing_address || ""}, ${this.displayData.billing_state_name || ""}, ${this.displayData.billing_city_name || ""}</td>
                      </tr>
                    </table>
                  </td>

                  <td class="billing-details" width="50%">
                    <table>
                      <tr><td colspan="3"><strong>Delivery Details</strong></td></tr>
                      <tr><td width="90">Shipped To</td><td width="10">:</td><td>${this.displayData.customer_name || ""}</td></tr>
                      <tr><td>Address</td><td>:</td><td>${this.displayData.customer_address || ""}</td></tr>
                      <tr><td>Financed By</td><td>:</td><td>${this.displayData.financier || ""}</td></tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td height="10"></td></tr>

          <tr class="top">
            <td colspan="2">
              <table style="border: 1px solid #333333;" cellpadding="10">
                <tr>
                  <td class="billing-details" width="50%">
                    <table>
                      <tr><td width="90">Order No</td><td width="10">:</td><td>${this.displayData.order_id || ""}</td></tr>
                      <tr><td>Order Date</td><td>:</td><td>${this.displayData.order_date || ""}</td></tr>
                      <tr><td>Mobile No</td><td>:</td><td>${this.displayData.customer_mobile || ""}</td></tr>
                      <tr><td>PAN No</td><td>:</td><td>${this.displayData.customer_pan || ""}</td></tr>
                    </table>
                  </td>

                  <td class="billing-details" width="50%">
                    <table>
                      <tr><td width="90">GSTIN</td><td width="10">:</td><td>${this.displayData.customer_gstin || ""}</td></tr>
                      <tr><td>Aadhar</td><td>:</td><td>${this.displayData.customer_aadhar || ""}</td></tr>
                      <tr><td>State Name</td><td>:</td><td>${this.displayData.customer_state_name || ""}</td></tr>
                      <tr><td>State Code</td><td>:</td><td>${this.displayData.customer_state_code || ""}</td></tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td height="10"></td></tr>

          <tr>
            <td style="padding: 0; margin: 0;">
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
                  <td valign="top" align="left">
                    Pre-Owned Car Model: ${this.displayData.make_name || ""} ${this.displayData.model_name || ""} ${this.displayData.variant_name || ""}<br>
                    Color: ${this.displayData.ext_color_name || ""}<br>
                    Registration No: ${this.displayData.registration_no || "-"}<br>
                    Chassis No: ${this.displayData.chassis_no || "-"}<br>
                    Engine No: ${this.displayData.engine_no || ""}<br>
                    GST Assessable Value: ${""}<br>
                    HSN Code: ${this.displayData.hsn_code || ""}
                  </td>
                  <td align="center">1</td>
                  <td align="right">${""}</td>
                  <td align="right">${this.displayData.taxable_amt || ""}</td>
                </tr>
                <tr>
                  <td align="center">2</td>
                  <td>SGST</td>
                  <td></td>
                  <td align="right">${this.displayData.sgst_rate || ""}%</td>
                  <td align="right">${this.sgstAmount}</td>
                </tr>
                <tr>
                  <td align="center">3</td>
                  <td>CGST</td>
                  <td></td>
                  <td align="right">${this.displayData.cgst_rate || ""}%</td>
                  <td align="right">${this.cgstAmount}</td>
                </tr>
                <tr>
                  <td></td>
                  <td><strong>Total (Including Taxes)</strong></td>
                  <td></td>
                  <td></td>
                  <td align="right"><strong>${this.grandTotal}</strong></td>
                </tr>
                <tr>
                  <td></td>
                  <td>Round Off Amount</td>
                  <td></td>
                  <td></td>
                  <td align="right">${this.roundOffAmount}</td>
                </tr>
                <tr>
                  <td></td>
                  <td><strong>Grand Total</strong></td>
                  <td></td>
                  <td></td>
                  <td align="right"><strong>${this.roundedGrandTotal}</strong></td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td><strong>Amount in Words:</strong> ${this.amountInWords}</td></tr>
          <tr><td height="10"></td></tr>

          <tr>
            <td>
              <table>
                <tr>
                  <td>
                    Dealer CIN : ${this.displayData.dealer_cin || ""}<br>
                    Dealer State : ${this.displayData.branch_state_name || ""}<br>
                    State Name : ${this.displayData.branch_state_name || ""}
                  </td>
                  <td valign="top">
                    Dealer PAN : ${this.displayData.branch_pan || ""}<br>
                    Dealer GSTIN : ${this.displayData.branch_gstin || ""}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td height="70"></td></tr>

          <tr>
            <td>
              <table>
                <tr>
                  <td width="70%">(Customer Name & Signatory)</td>
                  <td valign="top">(Authorized Signatory)</td>
                </tr>
              </table>
            </td>
          </tr>

          <tr><td height="10"></td></tr>

          <tr>
            <td class="terms">
              <strong>Terms & Conditions:</strong><br>
              ${this.displayData.terms || 'All finance cases are subject to actual disbursement / realization of loan. Excess/refund to be borne by customer. This amount includes applicable interest.'}
            </td>
          </tr>
        </table>
      </div>`;
    },
  },

 template: `
<div style="display:flex; justify-content:center; align-items:flex-start; min-height:100vh; background:#f3f3f3; padding:20px;">
  <div class="invoice-container" 
       style="background:rgb(243,243,243); width:800px; max-height:90vh; overflow-y:auto; border-radius:10px; position:relative; display:flex; flex-direction:column;">


    <!-- Invoice Pages -->
    <div style="box-shadow:0 4px 20px rgba(0,0,0,0.1); background-color:#fff; flex:1; display:flex; flex-direction:column;">

      <button
          @click="downloadPDF"
          class="download-btn"
          title="Download PDF"
        >
          <i 
            v-if="!isDownloading" 
            class="bi bi-download" 
            style="font-size:18px; color:black;"
          ></i>
          <i 
            v-else 
            class="bi bi-arrow-repeat spin" 
            style="font-size:18px; color:black;"
          ></i>
        </button>

      <div
        v-for="(label,index) in pages"
        :key="index"
        style="min-height:1100px; padding:20px; box-sizing:border-box; page-break-after:always; border-bottom: 15px solid rgb(243, 243, 243);"
        v-html="getPageHTML(label)">
      </div>
    </div>

  </div>
</div>
`,

};
