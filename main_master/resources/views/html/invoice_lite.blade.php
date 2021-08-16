<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <title>Title </title>
   <style>
      @font-face {
         font-family: 'WebFont-Ubuntu';
         src: local(Ubuntu), url(https://fonts.gstatic.com/s/ubuntu/v10/4iCs6KVjbNBYlgoKcg72nU6AF7xm.woff2);
      }

      .pcs-template {
         font-family: Ubuntu, 'WebFont-Ubuntu';
         font-size: 9pt;
         color: #333333;
         background: #ffffff;
      }

      .pcs-header-content {
         font-size: 9pt;
         color: #333333;
         background-color: #ffffff;
      }

      .pcs-template-body {
         padding: 0 0.400000in 0 0.550000in;
      }

      .pcs-template-footer {
         height: 0.700000in;
         font-size: 6pt;
         color: #aaaaaa;
         padding: 0 0.400000in 0 0.550000in;
         background-color: #ffffff;
      }

      .pcs-footer-content {
         word-wrap: break-word;
         color: #aaaaaa;
         border-top: 1px solid #adadad;
      }

      .pcs-label {
         color: #333333;
      }

      .pcs-entity-title {
         font-size: 28pt;
         color: #000000;
      }

      .pcs-orgname {
         font-size: 10pt;
         color: #333333;
      }

      .pcs-customer-name {
         font-size: 9pt;
         color: #333333;
      }

      .pcs-itemtable-header {
         font-size: 9pt;
         color: #ffffff;
         background-color: #3c3d3a;
      }

      .pcs-itemtable-breakword {
         word-wrap: break-word;
      }

      .pcs-taxtable-header {
         font-size: 9pt;
         color: #ffffff;
         background-color: #3c3d3a;
      }

      .breakrow-inside {
         page-break-inside: avoid;
      }

      .breakrow-after {
         page-break-after: auto;
      }

      .pcs-item-row {
         font-size: 9pt;
         border-bottom: 1px solid #adadad;
         background-color: #ffffff;
         color: #000000;
      }

      .pcs-item-sku {
         margin-top: 2px;
         font-size: 10px;
         color: #444444;
      }

      .pcs-item-desc {
         color: #727272;
         font-size: 9pt;
      }

      .pcs-balance {
         background-color: #f5f4f3;
         font-size: 9pt;
         color: #000000;
      }

      .pcs-totals {
         font-size: 9pt;
         color: #000000;
         background-color: #ffffff;
      }

      .pcs-notes {
         font-size: 8pt;
      }

      .pcs-terms {
         font-size: 8pt;
      }

      .pcs-header-first {
         background-color: #ffffff;
         font-size: 9pt;
         color: #333333;
         height: auto;
      }

      .pcs-status {
         color: ;
         font-size: 15pt;
         border: 3px solid;
         padding: 3px 8px;
      }

      .billto-section {
         padding-top: 0mm;
         padding-left: 0mm;
      }

      .shipto-section {
         padding-top: 0mm;
         padding-left: 0mm;
      }

      @page :first {
         @top-center {
            content: element(header);
         }

         margin-top: 0.700000in;
      }

      .pcs-template-header {
         padding: 0 0.400000in 0 0.550000in;
         height: 0.700000in;
      }

      .pcs-template-fill-emptydiv {
         display: table-cell;
         content: " ";
         width: 100%;
      }

      /* Additional styles for RTL compat */
      /* Helper Classes */
      .inline {
         display: inline-block;
      }

      .v-top {
         vertical-align: top;
      }

      .text-align-right {
         text-align: right;
      }

      .rtl .text-align-right {
         text-align: left;
      }

      .text-align-left {
         text-align: left;
      }

      .rtl .text-align-left {
         text-align: right;
      }

      /* Helper Classes End */
      .item-details-inline {
         display: inline-block;
         margin: 0 10px;
         vertical-align: top;
         max-width: 70%;
      }

      .total-in-words-container {
         width: 100%;
         margin-top: 10px;
      }

      .total-in-words-label {
         vertical-align: top;
         padding: 0 10px;
      }

      .total-in-words-value {
         width: 170px;
      }

      .total-section-label {
         padding: 5px 10px 5px 0;
         vertical-align: middle;
      }

      .total-section-value {
         width: 120px;
         vertical-align: middle;
         padding: 10px 10px 10px 5px;
      }

      .rtl .total-section-value {
         padding: 10px 5px 10px 10px;
      }

      .tax-summary-description {
         color: #727272;
         font-size: 8pt;
      }

      .bharatqr-bg {
         background-color: #f4f3f8;
      }

      /* Overrides/Patches for RTL compat */
      .rtl th {
         text-align: inherit;
         /* Specifically setting th as inherit for supporting RTL */
      }

      /* Overrides/Patches End */
      /* Signature styles */
      .sign-border {
         width: 200px;
         border-bottom: 1px solid #000;
      }

      .sign-label {
         display: table-cell;
         font-size: 10pt;
         padding-right: 5px;
      }

      /* Signature styles End */
      /* Subject field styles */
      .subject-block {
         margin-top: 20px;
      }

      .subject-block-value {
         word-wrap: break-word;
         white-space: pre-wrap;
         line-height: 14pt;
         margin-top: 5px;
      }

      /* Subject field styles End*/
      .lineitem-column {
         padding: 10px 10px 5px 10px;
         word-wrap: break-word;
      }

      body {
         margin: 0;
         font-family: sans-serif;
         font-size: 1rem;
         font-weight: 400;
         line-height: 1.42857;
         color: #212529;
         background-color: #fff;
      }

      .pcs-template-body {
         padding: 10px 15px;
         max-width: 700px;
         margin: 0 auto;
         font-family: sans-serif;
      }

      .pcs-header-content {
         font-size: 8pt;
         color: #000000;
         background-color: #ffffff;
      }

      .pcs-template-bodysection {
         border: 1px solid #9e9e9e;
      }

      .pcs-orgname {
         font-size: 10pt;
         color: #000000;
      }

      .pcs-entity-title {
         font-size: 22pt;
         color: #000000;
      }

      .invoice-detailstable>tbody>tr>td>span {
         width: 45%;
         padding: 1px 5px;
         display: inline-block;
         vertical-align: top;
         font-family: sans-serif;
      }

      .invoice-detailstable>tbody>tr>td {
         width: 50%;
         vertical-align: top;
         border-top: 1px solid #9e9e9e;
         font-family: sans-serif;
      }

      .pcs-template {
         font-family: Ubuntu, 'WebFont-Ubuntu';
         font-size: 8pt;
         color: #000000;
         background: #ffffff;
      }

      .pcs-label {
         color: #333333;
      }

      .pcs-addresstable>thead>tr>th {
         padding: 1px 5px;
         background-color: #f2f3f4;
         font-weight: normal;
         border-bottom: 1px solid #9e9e9e;
         font-family: sans-serif;
      }

      .pcs-addresstable {
         width: 100%;
         table-layout: fixed;
      }

      .pcs-itemtable-header {
         font-weight: normal;
      }

      .pcs-itemtable tr td:first-child,
      .pcs-itemtable tr th:first-child {
         border-left: 0px;
      }

      .pcs-itemtable-header {
         font-weight: normal;
      }

      .pcs-itemtable-header {
         font-size: 8pt;
         color: #000000;
         background-color: #f2f3f4;
      }

      .pcs-item-row {
         border-bottom: 1px solid #9e9e9e;
         vertical-align: middle;
      }

      .pcs-itemtable {
         border-top: 1px solid #9e9e9e;
      }

      .pcs-addresstable>tbody>tr>td {
         line-height: 15px;
         padding: 5px 5px 0px 5px;
         vertical-align: top;
         word-wrap: break-word;
      }

      .pcs-totaltable tbody>tr>td {
         padding: 4px 7px 0px;
         text-align: right;
      }

      .pcs-itemtable tbody>tr>td {
         padding: 5px 5px;
         word-wrap: break-word;
      }

      .pcs-itemtable-header {
         font-size: 9pt;
         color: #ffffff;
         background-color: #3c3d3a;
      }

      .mabaldue {
         font-size: 10pt;
         color: #000000;
         font-weight: bold;
         display: block;
      }

      .lighttable {}

      .lighttable thead {}

      .lighttable thead tr th {
         background: #eff0f1;
         color: #444;
         padding: 8px;
         text-align: left;
      }

      .lighttable tbody tr td {
         padding: 8px;
         text-align: left;
         border: 1px solid #eff0f1;
      }
   </style>
</head>

<body>
   <div class="ember-view">
      <div class="pcs-template">
         <div class="pcs-template-header pcs-header-content">
            <div class="pcs-template-fill-emptydiv"></div>
         </div>
         <div class="pcs-template-body">
            <table style="width:100%;table-layout: fixed;">
               <tbody>
                  <tr>
                     <td style="vertical-align: top; width:50%;">
                        <div>
                        </div>
                     <span class="pcs-orgname"> <img src="{{ url('company-image', 'logo.png') }}" width="220px"> </span><br>
                     </td>
                     <td style="    vertical-align: bottom; width:50%; text-align:right;" align="right">
                        <div>
                        </div>
                        <span class="pcs-orgname"><b>Mobiato </b></span><br>
                        <span class="pcs-label">
                           <span style="white-space: pre-wrap;word-wrap: break-word;">Alk Barsha <br>Dubai<br>dubai
                              Dubai 181529<br>United Arab Emirates
                           </span>
                        </span>
                     </td>
                  </tr>
               </tbody>
            </table>
            <table style="width:100%;table-layout: fixed;">
               <tbody>
                  <tr>
                     <td style="vertical-align: top;  border-bottom:1px solid #eee;width: 40%;">
                     </td>
                     <td style="vertical-align: top; width:20%;    text-align: center;position: relative;top: 13px;">
                        <span style="font-size: 13pt; text-transform: uppercase;">
                           Invoice
                        </span>
                     </td>
                     <td style="vertical-align: top; border-bottom:1px solid #eee;width: 40%;">
                     </td>
                  </tr>
               </tbody>
            </table>
            <table style="width:100%;margin-top:30px;table-layout:fixed;">
               <tbody>
                  <tr>
                     <td style="width:60%;vertical-align:top;word-wrap: break-word;">
                        <div style="clear:both;width:50%;margin-top: 6px;">
                           <label style="font-size: 10pt;" class="pcs-label">Bill To</label>
                           <br>
                           <span style="white-space: pre-wrap;line-height: 15px;"><strong><span
                                    class="pcs-customer-name"><a href="#">test test</a></span></strong>
                              <span style="white-space: pre-wrap;word-wrap: break-word;"><br>
                                 <b>{{  $invoice->organisation->org_street1 }}</b>
                                 <br>{{  $invoice->organisation->org_street2 }}<br>{{ $invoice->organisation->org_city }}
                                 <br>{{ $invoice->organisation->org_postal }}
                                 {{ $invoice->organisation->org_state }}<br>{{ $invoice->organisation->country }}
                              </span>
                           </span>
                        </div>
                     </td>
                     <td align="right" style="vertical-align:top;width: 40%;">
                        <table style="float:right;table-layout: fixed;word-wrap: break-word;width: 100%;" border="0"
                           cellspacing="0" cellpadding="0">
                           <tbody>
                              <tr>
                                 <td style="text-align:right;font-size:9pt;">
                                    <span class="pcs-label">Invoice# </span>
                                 </td>
                              </tr>
                              <tr>
                                 <td style="text-align:right;">
                                    <span style="font-size: 12pt;font-weight: bold;">{{ $invoice->invoice_number }}</span>
                                    </td>
                                 </tr>
                              </tbody>
                           </table>
                        </td>
                     </tr>
                  </tbody>
               </table>
               <table class="lighttable" style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
                  <thead>
                     <tr>
                        <th>
                           Invoice Date :
                        </th>
                        <th>
                           Terms :
                        </th>
                        <th>
                           Due Date :
                        </th>
                        <th>
                           P.O.# :
                        </th>
                        <th>
                           Project Name :
                        </th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                                 </td>
                                 <td>
                                    {{ model($invoice->paymentTerm, 'name') }}
                                 </td>
                                 <td>
                                    {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                                 </td>
                                 <td>
                                    {{ model($invoice->organisation, 'org_postal') }}
                                 </td>
                                 <td>
                                    Design project
                                 </td>
                              </tr>
                           </tbody>
                        </table>
                        <table class="lighttable"
                           style="width:100%;margin-top:20px;margin-bottom:20px;table-layout:fixed;"
                           class="pcs-itemtable" border="0" cellspacing="0" cellpadding="0">
                           <thead>
                              <tr style="height:17px;">
                                 <th style="padding: 8px 8px 8px 8px;width: 5%;text-align: center;" valign="bottom"
                                    rowspan="2" id="" class=" pcs-itemtable-breakword">
                                    #
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;width: 12%;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    ITEM NAME
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;width: 12%;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    UOM
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px; width: 12%;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    QUANTITY
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Price
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Discount
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Vat
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Net
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Excise
                                 </th>
                                 <th style="padding: 8px 8px 8px 8px;" valign="bottom" rowspan="2" id=""
                                    class=" pcs-itemtable-breakword">
                                    Total
                                 </th>
                              </tr>
                              <tr>
                              </tr>
                           </thead>
                           <tbody class="itemBody">
                              @include('html.itemDetail', ['item_details' => $invoice->invoices])
                           </tbody>
                        </table>
                        <div style="width: 100%;margin-top: 1px;">
                           <div style="width: 45%;padding: 3px 10px 3px 3px;font-size: 9pt;float: left;">
                              <div style="margin:10px 0 5px">
                                 <div style="padding-right: 10px;">Customer Note</div>
                                 <span><b><i>Customer notes will be display here</i></b></span>
                              </div>
                           </div>
                           <div style="width: 50%;float:right;">
                              <table class="pcs-totals" cellspacing="0" border="0" width="100%">
                                 <tbody>
                                    <tr>
                                       <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Gross Total
                                       </td>
                                       <td valign="middle" align="right" style="width:120px;padding: 4px 10px 4px 5px;">
                                          {{ number_format(array_sum($invoice->invoices->pluck('item_gross')->toArray()), 2) }}
                                       </td>
                                    </tr>
                                    <tr>
                                       <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Vat </td>
                                       <td valign="middle" align="right" style="width:120px;padding: 4px 10px 4px 5px;">
                                          {{ number_format(array_sum($invoice->invoices->pluck('item_vat')->toArray()), 2) }}
                                          </td>
                                    </tr>
                                    <tr>
                                       <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Excise </td>
                                       <td valign="middle" align="right" style="width:120px;padding: 4px 10px 4px 5px;">
                                          {{ number_format(array_sum($invoice->invoices->pluck('item_net')->toArray()), 2) }}
                                       </td>
                                    </tr>
                                    <tr>
                                       <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Net Total
                                       </td>
                                       <td valign="middle" align="right" style="width:120px;padding: 4px 10px 4px 5px;">
                                          {{ number_format(array_sum($invoice->invoices->pluck('item_excise')->toArray()), 2) }}
                                          </td>
                                    </tr>
                                    <tr>
                                       <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Discount </td>
                                       <td valign="middle" align="right" style="width:120px;padding: 4px 10px 4px 5px;">
                                          {{ number_format(array_sum($invoice->invoices->pluck('item_discount_amount')->toArray()), 2) }}</td>
                                    </tr>
                                    <tr style="height:40px;" class="pcs-balance">
                                       <td valign="middle" align="right" style="padding: 5px 10px 5px 0;"><b>Total</b>
                                       </td>
                                       <td valign="middle" align="right"
                                          style="width:120px;;padding: 10px 10px 10px 5px;">
                                          <b>{{ number_format(array_sum($invoice->invoices->pluck('item_grand_total')->toArray()), 2) }}</b>
                                       </td>
                                    </tr>
                                 </tbody>
                              </table>
                           </div>
                           <div style="clear: both;"></div>
                           <div style="white-space: pre-wrap;"></div>
                        </div>
                        <div style="width: 100%;margin-top: 10px;">
                           <table cellspacing="0" border="0" width="100%">
                              <tbody>
                                 <tr>
                                    <td class="total-in-words-label text-align-right">Total In Words:</td>
                                    <td class="total-in-words-value text-align-right"><i><b>{{ convertToCurrency(array_sum($invoice->invoices->pluck('item_grand_total')->toArray())) }}</b></i></td>
                                 </tr>
                              </tbody>
                           </table>
                           <div style="clear: both;"></div>
                        </div>
                        <div style="margin-top:0px;">
                           <label style="display: table-cell;font-size: 10pt;padding-right: 5px;"
                              class="pcs-label">Authorized
                              Signature</label>
                           <div style="display: table-cell;">
                              <div style="display: inline-block;width: 200px;border-bottom: 1px solid #000;"></div>
                              <div></div>
                           </div>
                        </div>
         </div>
         <div class="pcs-template-footer">
            <div>
            </div>
         </div>
      </div>
   </div>
   </div>
</body>

</html>