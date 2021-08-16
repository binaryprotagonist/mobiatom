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
            margin: 12px 0 0 0;
        }
    </style>
</head>

<body>
    <div id="ember549" class="ember-view">
        <div class="pcs-template">
            <div class="pcs-template-header pcs-header-content" id="header">
                <div class="pcs-template-fill-emptydiv"></div>
            </div>
            <div class="pcs-template-body">
                <table style="width:100%;table-layout: fixed;">
                    <tbody>
                        <tr>
                            <td style="vertical-align: top; width:50%;">
                                <div>
                                </div>
                                <span class="pcs-orgname"><b>Mobiato Consulting</b></span><br>
                                <span class="pcs-label">
                                    <span style="white-space: pre-wrap;word-wrap: break-word;" id="tmp_org_address">Alk
                                        Barsha <br>Dubai<br>dubai Dubai<br>181529<br>United Arab Emirates
                                    </span>
                                </span>
                            </td>
                            <td style="vertical-align: top; text-align:right;width:50%;">
                                <span class="pcs-entity-title">ESTIMATION </span><br>
                                <span id="tmp_entity_number" style="font-size: 10pt;" class="pcs-label"><b>Estimation#
                                        {{$estimation->estimate_code}}</b></span><br>
                                <span class="mabaldue">SALES PERSON </span>
                                <span id="tmp_entity_number" style="font-size: 10pt;"
                                    class="pcs-label"><b>{{ model($estimation->salesperson, 'name') }}</b></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table style="width:100%;margin-top:30px;table-layout:fixed;">
                    <tbody>
                        <tr>
                            <td style="width:60%;vertical-align:bottom;word-wrap: break-word;">
                                <div style="clear:both;width:50%;margin-top: 20px;">
                                    <label style="font-size: 10pt;" class="pcs-label"
                                        id="tmp_shipping_address_label">Bill To</label>
                                    <br>
                                    <span style="white-space: pre-wrap;line-height: 15px;" id="tmp_billing_address">
                                        <strong>
                                            <span class="pcs-customer-name" id="zb-pdf-customer-detail">
                                                @if (is_object($estimation->customer))
                                                <a href="#">{{ $estimation->customer->getName() }}</a>
                                                @endif
                                            </span>
                                        </strong>
                                        <span style="white-space: pre-wrap;word-wrap: break-word;"
                                            id="tmp_org_address"><br> <b>{{  $estimation->organisation->org_street1 }}</b>
                                            <br>{{  $estimation->organisation->org_street2 }}<br>{{ $estimation->organisation->org_city }}
                                            <br>{{ $estimation->organisation->org_postal }}
                                            {{ $estimation->organisation->org_state }}<br>{{ $estimation->organisation->country }}
                                        </span>
                                    </span>
                                </div>
                            </td>
                            <td align="right" style="vertical-align:bottom;width: 40%;">
                                <table style="float:right;table-layout: fixed;word-wrap: break-word;width: 100%;"
                                    border="0" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            <td style="text-align:right;padding:5px 10px 5px 0px;font-size:10pt;">
                                                <span class="pcs-label">Estimation Date :</span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span
                                                    id="tmp_entity_date">{{ date('d-m-Y', strtotime($estimation->estimate_date)) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right;padding:5px 10px 5px 0px;font-size: 10pt;">
                                                <span class="pcs-label">Subject :</span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span
                                                    id="tmp_ref_number">{{ model($estimation, 'subject') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right;padding:5px 10px 5px 0px;font-size: 10pt;">
                                                <span class="pcs-label">Due Date : </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span>{{ \Carbon\Carbon::parse($estimation->expairy_date)->format('d M Y') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right;padding:5px 10px 5px 0px;font-size: 10pt;">
                                                <span class="pcs-label">P.O.# : </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span>{{ model($estimation->organisation, 'org_postal') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:right;padding:5px 10px 5px 0px;font-size: 10pt;">
                                                <span class="pcs-label">Project Name : </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span>Design project</span>
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
                            <td style="padding: 5px 5px 2px 5px;width: 5%;text-align: center;" valign="bottom"
                                rowspan="2" id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>#</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;width: 12%;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>ITEM NAME</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;width: 12%;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>UOM </b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px; width: 12%;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>QUANTITY</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Price</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Discount</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Vat</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Net</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Excise</b>
                            </td>
                            <td style="padding: 5px 7px 2px 7px;" valign="bottom" rowspan="2" id=""
                                class="pcs-itemtable-header pcs-itemtable-breakword">
                                <b>Total</b>
                            </td>
                        </tr>
                        <tr></tr>
                    </thead>
                    <tbody class="itemBody">
                        @include('html.itemDetail', ['item_details' => $estimation->estimationdetail])
                    </tbody>
                </table>
                <div style="width: 100%;margin-top: 1px;">
                    <div style="width: 45%;padding: 3px 10px 3px 3px;font-size: 9pt;float: left;">
                        <div style="white-space: pre-wrap;"></div>
                    </div>
                    <div style="width: 50%;float:right;">
                        <table class="pcs-totals" cellspacing="0" border="0" width="100%">
                            <tbody>
                                <tr>
                                    <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Gross Total </td>
                                    <td id="tmp_subtotal" valign="middle" align="right"
                                        style="width:120px;padding: 4px 10px 4px 5px;">
                                        {{ number_format($estimation->gross_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Vat </td>
                                    <td id="tmp_subtotal" valign="middle" align="right"
                                        style="width:120px;padding: 4px 10px 4px 5px;">
                                        {{ number_format($estimation->vat, 2) }}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Excise </td>
                                    <td id="tmp_subtotal" valign="middle" align="right"
                                        style="width:120px;padding: 4px 10px 4px 5px;">
                                        {{ number_format($estimation->exise, 2) }}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Net Total </td>
                                    <td id="tmp_subtotal" valign="middle" align="right"
                                        style="width:120px;padding: 4px 10px 4px 5px;">
                                        {{ number_format($estimation->net_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td valign="middle" align="right" style="padding: 2px 10px 2px 0;">Discount </td>
                                    <td id="tmp_subtotal" valign="middle" align="right"
                                        style="width:120px;padding: 4px 10px 4px 5px;">
                                        {{ number_format($estimation->discount, 2) }}</td>
                                </tr>
                                <tr style="height:40px;" class="pcs-balance">
                                    <td valign="middle" align="right" style="padding: 5px 10px 5px 0;"><b>Total</b></td>
                                    <td id="tmp_total" valign="middle" align="right"
                                        style="width:120px;;padding: 10px 10px 10px 5px;">
                                        <b>{{ number_format($estimation->total, 2) }}</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                <div style="width: 100%;margin-top: 10px;">
                    <table cellspacing="0" border="0" width="100%">
                        <tbody>
                            <tr>
                                <td class="total-in-words-label text-align-right">Total In Words:</td>
                                <td class="total-in-words-value text-align-right">
                                    <i><b>{{ convertToCurrency($estimation->total) }}</b></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="clear: both;"></div>
                </div>
                <div style="margin-top:30px;">
                    <label style="display: table-cell;font-size: 10pt;padding-right: 5px;" class="pcs-label">Authorized
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