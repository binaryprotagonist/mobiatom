<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <title>Title </title>
   <style>
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
         font-size: 12pt;
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
         border-right: 1px solid #9e9e9e;
         border-bottom: 1px solid #9e9e9e;
      }

      .pcs-itemtable tr td:first-child,
      .pcs-itemtable tr th:first-child {
         border-left: 0px;
      }

      .pcs-itemtable-header {
         font-weight: normal;
         border-right: 1px solid #9e9e9e;
         border-bottom: 1px solid #9e9e9e;
      }

      .pcs-itemtable-header {
         font-size: 8pt;
         color: #000000;
         background-color: #f2f3f4;
      }

      .pcs-item-row {
         border-right: 1px solid #9e9e9e;
         border-bottom: 1px solid #9e9e9e;
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
         padding: 1px 5px;
         word-wrap: break-word;
      }

      .text-align-right {
         text-align: right;
      }
   </style>
</head>

<body>
   <div class="pcs-template">
      <div class="pcs-template-header pcs-header-content">
         <div class="pcs-template-fill-emptydiv"></div>
      </div>
      <div class="pcs-template-body">
         <div class="pcs-template-bodysection">
            <table style="width: 100%;">
               <tbody>
                  <tr>
                     <td style="width:50%;padding: 2px 10px;vertical-align: middle;">
                        <div>
                           <span style="font-weight: bold;" class="pcs-orgname">Mobiato Consulting<br></span>
                           <span style="white-space: pre-wrap;">Alk Barsha
                              Dubai
                              dubai Dubai
                              181529
                              United Arab Emirates</span>
                        </div>
                     </td>
                     <td style="width:40%;padding: 5px;vertical-align: bottom;" align="right">
                        <div class="pcs-entity-title">CREDIT NOTE</div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <div style="width: 100%;">
               <table cellspacing="0" cellpadding="0" border="0"
                  style="width: 100%;table-layout: fixed;word-wrap: break-word;border-top: 1px solid #9e9e9e;"
                  class="invoice-detailstable">
                  <thead>
                     <tr>
                        <th style="width: 50%"></th>
                        <th style="width: 50%"></th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td style="padding-bottom: 10px;">
                           <span class="pcs-label">Debit Note No</span>
                           <span style="font-weight: 600;">: {{ $debit_note->debit_note_number }}</span>
                           <span class="pcs-label">Debit Date</span>
                           <span style="font-weight: 600;">:
                              {{ date('d/m/Y', strtotime($debit_note->debit_note_date)) }}</span>
                           <span class="pcs-label">Invoice#</span>
                           <span style="font-weight: 600;">: {{ model($debit_note->invoice, 'invoice_number') }}</span>
                           <span class="pcs-label">Invoice Date </span>
                           <span style="font-weight: 600;">:
                              {{ date('d/m/Y', strtotime(model($debit_note->invoice, 'invoice_date'))) }}</span>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <div style="clear:both;"></div>
            <table style="" class="pcs-addresstable" border="0" cellspacing="0" cellpadding="0">
               <thead>
                  <tr>
                     <th style="border-top: 1px solid #9e9e9e;    text-align: left;"><label
                           style="margin-bottom: 0px;display: block;font-size:10pt;    text-align: left;"
                           class="pcs-label"><b>Bill To</b></label></th>
                  </tr>
               </thead>
               <tbody>
                  <tr>
                     <td style="padding-bottom: 10px;" valign="top">
                        <span style="white-space: pre-wrap;line-height: 15px;"><strong><span
                                 class="pcs-customer-name"><a href="#">{{ model($debit_note->customer, 'firstname') }}
                                    {{ model($debit_note->customer, 'lastname') }}</a></span></strong><br>
                           <b>{{  $debit_note->organisation->org_street1 }}</b>
                           <br>{{  $debit_note->organisation->org_street2 }}<br>{{ $debit_note->organisation->org_city }}
                           <br>{{ $debit_note->organisation->org_postal }}
                           {{ $debit_note->organisation->org_state }}<br>{{ $debit_note->organisation->country }}</span>
                     </td>
                  </tr>
               </tbody>
            </table>
            <div style="clear:both;"></div>
            <table style="width: 100%;table-layout:fixed;clear: both;" class="pcs-itemtable" cellspacing="0"
               cellpadding="0" border="0">
               <thead>
                  <tr style="height:17px;">
                     <td style="padding: 5px 5px 2px 5px;width: 5%;text-align: center;" valign="bottom" rowspan="2"
                        id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>#</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 11%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>ITEM NAME</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 11%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>UOM </b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 11%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>QUANTITY</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Price</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Discount</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Vat</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Net</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Excise</b>
                     </td>
                     <td style="padding: 5px 7px 2px 7px;width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Total</b>
                     </td>
                  </tr>
                  <tr></tr>
               </thead>
               <tbody class="itemBody">
                  @include('html.itemDetail', ['item_details' => $debit_note->debitNoteDetails])
               </tbody>
            </table>
            <div style="width: 100%;">
               <div style="width: 50%;padding: 4px 4px 3px 7px;float: left;">
                  <div style="margin:10px 0 5px">
                     <div style="padding-right: 10px;">Customer Note</div>
                     <span><b><i>Customer notes will be display here</i></b></span>
                  </div>
                  <div style="margin-top:80px;">
                     <label style="display: table-cell;font-size: 10pt;padding-right: 5px;" class="pcs-label">Authorized
                        Signature</label>
                     <div style="display: table-cell;">
                        <div style="display: inline-block;width: 200px;border-bottom: 1px solid #000;"></div>
                        <div></div>
                     </div>
                  </div>
               </div>
               <div style="width: 43.6%;float:right;" class="pcs-totals">
                  <table style="border-left: 1px solid #9e9e9e;" class="pcs-totaltable" cellspacing="0" border="0"
                     width="100%">
                     <tbody>
                        <tr>
                           <td valign="middle">Gross Total</td>
                           <td valign="middle" style="width:110px;">
                              {{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_gross')->toArray()), 2) }}
                           </td>
                        </tr>
                        <tr>
                           <td valign="middle">Vat</td>
                           <td valign="middle" style="width:110px;">
                              {{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_vat')->toArray()), 2) }}
                           </td>
                        </tr>
                        <tr style="height:10px;">
                           <td valign="middle">Excise</td>
                           <td valign="middle" style="width:110px;">
                              {{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_net')->toArray()), 2) }}
                           </td>
                        </tr>
                        <tr style="height:10px;">
                           <td valign="middle">Net Total</td>
                           <td valign="middle" style="width:110px;">
                              {{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_excise')->toArray()), 2) }}
                           </td>
                        </tr>
                        <tr style="height:10px;">
                           <td valign="middle">Discount</td>
                           <td valign="middle" style="width:110px;">
                              {{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_discount_amount')->toArray()), 2) }}
                           </td>
                        </tr>
                        <tr style="height:10px; font-size:16px;" class="pcs-balance">
                           <td valign="middle"><b>Total</b></td>
                           <td valign="middle" style="width:110px;;">
                              <strong>{{ number_format(array_sum($debit_note->debitNoteDetails->pluck('item_grand_total')->toArray()), 2) }}</strong>
                           </td>
                        </tr>
                     </tbody>
                  </table>
                  <div style="width: 100%;margin-top: 10px;">
                     <table cellspacing="0" border="0" width="100%">
                        <tbody>
                           <tr>
                              <td class="total-in-words-label text-align-right">Total In Words:</td>
                              <td class="total-in-words-value text-align-right">
                                 <i><b>{{ convertToCurrency(array_sum($debit_note->debitNoteDetails->pluck('item_grand_total')->toArray())) }}</b></i>
                              </td>
                           </tr>
                        </tbody>
                     </table>
                     <div style="clear: both;"></div>
                  </div>
               </div>
               <div style="clear: both;"></div>
               <div style="clear: both;"></div>
            </div>
         </div>
      </div>
      <div class="pcs-template-footer">
         <div>
         </div>
      </div>
   </div>
</body>

</html>