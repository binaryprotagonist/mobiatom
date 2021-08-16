<?php
//echo "<pre>";print_r($invoice);exit;
?>

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
            padding: 10px 15px;
            max-width: 700px;
            margin: 0 auto;
            font-family: sans-serif;
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
            font-size: 16pt;
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
            vertical-align: middle;
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
            color:;
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

        @top-center


        {
            content: element(header)
        ;
        }
        margin-top:

        0.700000
        in

        ;
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
            text-align: inherit; /* Specifically setting th as inherit for supporting RTL */
        }

        /* Overrides/Patches End */

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

        .trclass_evenrow {
            background-color: #f6f5f5;
        }

        .trclass_oddrow {
            background-color: #ffffff;
        }

        table {
            -fs-table-paginate: paginate;
        }

        .title-section {
            float: right;
            margin-top: 20px;
        }

        .rtl .title-section {
            float: left;
        }

        .pcs-itemtable-header {
            padding: 4px 4px;
        }

        .summary-section {
            float: right;
        }

        .rtl .summary-section {
            float: left;
        }

        .box-padding {
            padding: 8px 4px;
        }

        .trclass_evenrow {
            background-color: #f6f5f5;
        }
    </style>
</head>
<body>
<div id="ember1152" class="ember-view">


    <div class="pcs-template ">
        <div class="pcs-template-header pcs-header-content" id="header">


            <div class="pcs-template-fill-emptydiv"></div>

        </div>


        <div class="pcs-template-body">
            <table style="line-height:18px;" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tbody>
                <tr>
                    <td>
                    </td>

                    <td width="50%" class="pcs-orgname text-align-right">
                        <b>{{ ($userDetails)?$userDetails->organisation['org_name']:null }}</b><br>
                        <span style="white-space: pre-wrap;"
                              id="tmp_org_address">Company ID : {{ ($userDetails)?$userDetails->organisation['org_company_id']:null }}
                            {{ ($userDetails)?$userDetails->organisation['org_street1']." ".$userDetails->organisation['org_street1']:null }}
                            {{ ($userDetails)?$userDetails->organisation['org_city']." ".$userDetails->organisation['org_state']." ".$userDetails->organisation['org_postal']:null }}
                            {{ ($userDetails)?$userDetails->organisation['countryInfo']['name']:null }}
                            GSTIN {{ ($userDetails)?$userDetails->organisation['gstin_number']:null }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table cellpadding="0" cellspacing="0" border="0" class="title-section">
                            <tbody>
                            <tr>
                                <td class="pcs-entity-title" style="padding-top:6px;line-height:30px;"><b>Statement of
                                        Accounts</b></td>
                            </tr>
                            <tr>
                                <td style="font-size:12px; border-top: 1px solid #000;border-bottom: 1px solid #000;"
                                    height="24" class="text-align-right">{{ $accountSummary->statement_date  }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 0px 0px 5px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="70%">
                            <tbody>
                            <tr>
                                <td class="pcs-label"><b>To</b></td>
                            </tr>
                            <tr>
                                <td>
							<span style="white-space: pre-wrap;" id="tmp_billing_address"><strong><span class="pcs-customer-name" id="zb-pdf-customer-detail"><a href="#/contacts/297877000000007196">
                                {{ ($userDetails)?$userDetails->firstname." ".$userDetails->lastname:null }}</a></span></strong>
{{ ($userDetails)?$userDetails->customerInfo['customer_code'].",".$userDetails->customerInfo['customer_address_1']:null }}
{{ ($userDetails)?$userDetails->customerInfo['customer_address_2']:null }}
{{ ($userDetails)?$userDetails->customerInfo['customer_city']:null }}
{{ ($userDetails)?$userDetails->customerInfo['customer_zipcode'].",".$userDetails->customerInfo['customer_state']:null }}</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                    <td style="padding:20px 0px 30px 0px;" valign="bottom">
                        <table cellpadding="5" cellspacing="0" width="79%" border="0" class="summary-section">
                            <tbody>
                            <tr>
                                <td class="pcs-label" style="padding:4px 6px 4px 6px; border-bottom:1px solid #dcdcdc;"
                                    bgcolor="#e8e8e8" colspan="5"><b>Account Summary</b></td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="padding-top:6px;" width="50%">Opening Balance</td>
                                <td style="padding:6px 0px 0px 6px;" class="text-align-right" id="opening_balance">
                                    ₹ {{ number_format((float) $accountSummary->openingBalance, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="padding-top:4px;">Invoiced Amount</td>
                                <td style="padding:6px 0px 0px 6px;" class="text-align-right" id="invoiced_amount">
                                    ₹ {{ number_format((float) $accountSummary->invoiceAmount, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label">Amount Received</td>
                                <td style="padding:4px 0px 2px 6px;" class="text-align-right" id="amount_received">
                                    ₹ {{ number_format((float) $accountSummary->paymentReceived, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="padding-top:6px;border-top:1px solid #000;">Balance Due
                                </td>
                                <td style="padding:6px 0px 0px 6px;border-top:1px solid #000;" class="text-align-right"
                                    id="balance_due">
                                    ₹ {{ number_format((float) $accountSummary->balanceDue, 2, '.', '') }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
            <table style="width:100%;margin-top:20px;table-layout:fixed;" class="pcs-itemtable" border="0"
                   cellspacing="0" cellpadding="0">
                <thead>
                <tr style="height:17px;">


                    <td style="padding: 5px 7px 2px 7px;width: 15%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Date</b>
                    </td>
                    <td style="padding: 5px 7px 2px 7px;width: 14%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Transactions </b>
                    </td>
                    <td style="padding: 5px 5px 2px 5px;width: 25%;text-align: center;" valign="bottom" rowspan="2"
                        id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Details</b>
                    </td>
                    <td style="padding: 5px 7px 2px 7px; width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Debit </b>
                    </td>
                    <td style="padding: 5px 7px 2px 7px; width: 13%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Credit</b>
                    </td>

        <td style="padding: 5px 7px 2px 7px; width: 20%;" valign="bottom" rowspan="2" id=""
            class="pcs-itemtable-header pcs-itemtable-breakword">
            <b>Balance</b>
        </td>

                    <td style="padding: 5px 7px 2px 7px; width: 20%;" valign="bottom" rowspan="2" id=""
                        class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Running Balance</b>
                    </td>

                </tr>
                </thead>
                <tbody class="itemBody">
                    <tr>
                        <td></td>
                    </tr>
                @if(count($balanceStatement))
                    @php
                        $balanceAmount = number_format((float) 0, 2, '.', '');
                        $runing_balanceAmount = number_format((float) 0, 2, '.', '');
                        $debit_Amount = number_format((float) 0, 2, '.', '');
                        $credit_Amount = number_format((float) 0, 2, '.', '');
                    @endphp
                    @foreach ($balanceStatement as $balance)
                        <tr class="breakrow-inside breakrow-after ">
                            <td class="box-padding">{{ $balance['c_date'] }}</td>


                            <td class="box-padding">
                                {{ $balance['transaction'] }}
                            </td>

                            <td class="box-padding">
                                {{ $balance['detail'] }}
                            </td>

                            <td class="box-padding">
                                {{ $balance['amount'] }}
                            </td>
                            <td class="box-padding">
                                {{ $balance['payment'] }}

                            </td>

                            <td class="box-padding">

                            @if($balance['status']==0)
                                    @php
                                        $balanceAmount = $balance['amount'];
                                        $debit_Amount = $balanceAmount;
                                    @endphp
                                    {{ number_format((float) $balanceAmount, 2, '.', '') }} 
                                @elseif($balance['status']==1)
                                    @php
                                        $balanceAmount =  $balance['amount'];
                                        $debit_Amount =   $debit_Amount + $balanceAmount;
                                    @endphp
                                    {{ number_format((float) $balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==2)
                                    @php
                                        $balanceAmount = $balance['payment'];

                                        $credit_Amount = $credit_Amount + $balanceAmount;
                                    @endphp
                                    - {{ number_format((float) $balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==3)
                                    @php
                                        $balanceAmount =  $balance['payment'];
                                        $credit_Amount = $credit_Amount + $balanceAmount;
                                    @endphp
                                    - {{ number_format((float) $balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==4)
                                    @php
                                        $balanceAmount =  $balance['payment'];
                                        $credit_Amount = $credit_Amount + $balanceAmount;
                                    @endphp
                                - {{ number_format((float) $balanceAmount, 2, '.', '') }}
                                @endif
                            </td>
                            
                            <!-- Running Balance calculation -->
                            <td class="box-padding">
                                @if($balance['status']==0)
                                    @php
                                        $runing_balanceAmount = $balance['amount'];
                                    @endphp
                                    {{ number_format((float) $runing_balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==1)
                                    @php
                                        $runing_balanceAmount = $runing_balanceAmount + $balance['amount'];
                                    @endphp
                                    {{ number_format((float) $runing_balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==2)
                                    @php
                                        $runing_balanceAmount = $runing_balanceAmount - $balance['payment'];
                                    @endphp
                                    {{ number_format((float) $runing_balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==3)
                                    @php
                                        $runing_balanceAmount = $runing_balanceAmount - $balance['payment'];
                                    @endphp
                                    {{ number_format((float) $runing_balanceAmount, 2, '.', '') }}
                                @elseif($balance['status']==4)
                                    @php
                                        $runing_balanceAmount = $runing_balanceAmount - $balance['payment'];
                                    @endphp
                                    {{ number_format((float) $runing_balanceAmount, 2, '.', '') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
            <table width="100%"  style="border-top: 1px solid #dcdcdc; /*background-color:#FF0000; border: 2px solid black; */">
                <tbody>
                <tr>
                    <td></td>
                    <td width="60%">
                        <table width="100%">
                            <tbody>


                            <tr style="height:40px;" class="pcs-balance">
                                <td valign="middle" align="right" style="padding: 2px 2px 2px 0;"><b> ₹{{ number_format((float) $debit_Amount, 2, '.', '') }}   </b>
                                </td>

                                <td valign="middle" align="right" style="padding: 2px 2px 2px 0;"><b> ₹{{ number_format((float) $credit_Amount, 2, '.', '') }} </b>
                                </td>
                                <td id="tmp_total" valign="middle" align="right"
                                    style="width:120px; padding: 2px 2px 2px 0;">
                                    <b>₹{{ number_format((float) $runing_balanceAmount, 2, '.', '') }}</b>
                                </td>

                                <td id="tmp_total" valign="middle" align="right"
                                    style="width:120px; padding: 10px 10px 10px 5px;">
                                    <b>₹{{ number_format((float) $runing_balanceAmount, 2, '.', '') }}</b>                                   
                                </td>
                            </tr>


                            <tr style="height:40px;" class="pcs-balance">
                            
                            <td valign="middle" align="right" style=" padding: 5px 10px 5px 0;"><b>  </b></td>
                            <td valign="middle" align="right" style=" padding: 5px 10px 5px 0;"><b>  </b></td>

                                <td valign="middle" align="right" style=" padding: 5px 10px 5px 0;"><b>Balance Due </b>
                                </td>
                                <td id="tmp_total" valign="middle" align="right"
                                    style="width:120px;padding: 10px 10px 10px 5px;">
                                    <b>₹{{ number_format((float) $runing_balanceAmount, 2, '.', '') }}</b></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>


        </div>
        <div class="pcs-template-footer">
            <div>

            </div>
        </div>


    </div>
</div>
</body>
</html>