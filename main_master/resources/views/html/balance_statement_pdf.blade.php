<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Balance Statement</title>
</head>

<body>
<div id="ember1152" class="ember-view">
    <div class="pcs-template "
         style="font-family: Ubuntu, 'WebFont-Ubuntu';font-size: 9pt;color: #333333;background: #ffffff;">
        <div class="pcs-template-header pcs-header-content" id="header"
             style="font-size: 9pt;color: #333333;background-color: #ffffff;padding: 0 0.400000in 0 0.550000in;height: 0.700000in;">


            <div class="pcs-template-fill-emptydiv" style="display: table-cell;content: &quot; &quot;;width: 100%;">
            </div>

        </div>


        <div class="pcs-template-body"
             style="padding: 10px 15px;max-width: 700px;margin: 0 auto;font-family: sans-serif;">
            <table style="line-height: 18px;text-align:right;" cellpadding="0" cellspacing="0"
                   border="0" width="30%" align="right">
                <tbody>
                <tr>

                    <td  align="right" width="30%" class="pcs-orgname text-align-right"
                        style="font-size: 10pt;color: #333333;text-align: right;">
                        <b>{{ $userDetails->organisation['org_name'] }}</b><br>
                        <span
                              id="tmp_org_address">Company ID : {{ $userDetails->organisation['org_company_id'] }}
                            {{ $userDetails->organisation['org_street1']." ".$userDetails->organisation['org_street1'] }}
                            {{ $userDetails->organisation['org_city']." ".$userDetails->organisation['org_state']." ".$userDetails->organisation['org_postal'] }}
                            {{ $userDetails->organisation['countryInfo']['name'] }}
                            GSTIN {{ $userDetails->organisation['gstin_number'] }}
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
                <table cellpadding="0" cellspacing="0" border="0" style="margin-top: 20px;" align="right">
                    <tbody>
                    <tr>
                        <td class="pcs-entity-title"
                            style="padding-top: 6px;line-height: 30px;font-size: 16pt;color: #000000;text-align:right;">
                            <b>Statement
                                of Accounts</b></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px;border-top: 1px solid #000;border-bottom: 1px solid #000;text-align: right;"
                            height="24" class="text-align-right">{{ $accountSummary->statement_date  }}</td>
                    </tr>
                    </tbody>
                </table>
            <table cellpadding="0" cellspacing="0" border="0" class="title-section"
                   style="margin-top: 20px;">
                <tbody>
                <tr>
                    <td style="padding:20px 0px 0px 5px;" width="30%">
                        <table cellpadding="0" cellspacing="0" border="0" width="80%"
                               style="">
                            <tbody>
                            <tr>
                                <td class="pcs-label" style="color: #333333;"><b>To</b></td>
                            </tr>
                            <tr>
                                <td>
												<span style="white-space: pre-wrap;" id="tmp_billing_address"><strong>
                                    <span
                                            class="pcs-customer-name" id="zb-pdf-customer-detail"
                                            style="font-size: 9pt;color: #333333;"><a
                                                href="#/contacts/297877000000007196">{{ $userDetails->firstname." ".$userDetails->lastname }}</a></span></strong>
                                                    {{ $userDetails->customerInfo['customer_code'].",".$userDetails->customerInfo['customer_address_1'] }}
                                                    {{ $userDetails->customerInfo['customer_address_2'] }}
                                                    {{ $userDetails->customerInfo['customer_city'] }}
                                                    {{ $userDetails->customerInfo['customer_zipcode'].",".$userDetails->customerInfo['customer_state'] }}</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                    <td width="30%"></td>
                    <td style="padding:20px 0px 30px 0px;" valign="bottom" width="40%">
                        <table cellpadding="0" cellspacing="0" width="100%" border="0" class="summary-section">
                            <tbody>
                            <tr>
                                <td colspan="2" class="pcs-label"
                                    style="padding: 4px 6px 4px 6px;border-bottom: 1px solid #dcdcdc;color: #333333;"
                                    bgcolor="#e8e8e8" colspan="5"><b>Account Summary</b></td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="text-align: left;padding-top: 6px;color: #333333;" >
                                    Opening
                                    Balance
                                </td>
                                <td style="padding: 6px 0px 0px 6px;text-align: right;"
                                    class="text-align-right">
                                    ₹ {{ number_format((float) $accountSummary->openingBalance, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="padding-top: 4px;color: #333333;text-align: left;">Invoiced
                                    Amount</td>
                                <td style="padding: 6px 0px 0px 6px;text-align: right;"
                                    class="text-align-right">
                                    ₹ {{ number_format((float) $accountSummary->invoiceAmount, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label" style="color: #333333;text-align: left;">Amount Received</td>
                                <td style="padding: 4px 0px 2px 6px;text-align: right;"
                                    class="text-align-right">
                                    ₹ {{ number_format((float) $accountSummary->paymentReceived, 2, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="pcs-label"
                                    style="padding-top: 6px;border-top: 1px solid #000;color: #333333;">
                                    Balance Due
                                </td>
                                <td style="padding: 6px 0px 0px 6px;border-top: 1px solid #000;text-align: right;"
                                    class="text-align-right">
                                    ₹ {{ number_format((float) $accountSummary->balanceDue, 2, '.', '') }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
            <table style="width: 100%;margin-top: 20px;table-layout: fixed;"
                   class="pcs-itemtable" border="0" cellspacing="0" cellpadding="0">
                <thead>
                <tr style="height:17px;">


                    <td style="text-align:left;padding: 4px;width: 15%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom" id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Date</b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 14%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom"  id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Transactions </b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 25%;text-align: center;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom"  id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Details</b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 13%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom"  id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Debit </b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 13%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom"  id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Credit</b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 20%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom" id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Balance</b>
                    </td>
                    <td style="text-align:left;padding: 4px;width: 20%;font-size: 8pt;color: #ffffff;background-color: #3c3d3a;word-wrap: break-word;"
                        valign="bottom" id="" class="pcs-itemtable-header pcs-itemtable-breakword">
                        <b>Running Balance</b>
                    </td>

                </tr>
                <tr>
                </tr>
                </thead>
                <tbody class="itemBody">
                    <tr><td></td></tr>
                @if(count($balanceStatement))
                    @php
                        $balanceAmount = number_format((float) 0, 2, '.', '');
                        $runing_balanceAmount = number_format((float) 0, 2, '.', '');
                        $debit_Amount = number_format((float) 0, 2, '.', '');
                        $credit_Amount = number_format((float) 0, 2, '.', '');
                    @endphp
                    @foreach ($balanceStatement as $balance)
                <tr class="breakrow-inside breakrow-after "
                    style="page-break-inside: avoid;page-break-after: auto;">
                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">{{ $balance['c_date'] }}</td>


                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">
                        {{ $balance['transaction'] }}
                    </td>

                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">
                        {{ $balance['detail'] }}
                    </td>

                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">
                        {{ $balance['amount'] }}
                    </td>
                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">
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
                    <td class="box-padding" style="padding: 6px 4px;font-size:8pt;">
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

            <table width="100%" style="border-top: 1px solid #dcdcdc;">
                <tbody>
                <tr>
                    <td></td>
                    <td width="60%">
                        <table width="100%" style="">
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


                            <tr style="height: 40px;background-color: #f5f4f3;font-size: 9pt;color: #000000;"
                                class="pcs-balance">
                                <td valign="middle" align="right" style=" padding: 5px 10px 5px 0;"><b>  </b></td>
                                <td valign="middle" align="right" style=" padding: 5px 10px 5px 0;"><b>  </b></td>

                                <td valign="middle" align="right" style="padding: 5px 10px 5px 0;">
                                    <b>Balance Due </b>
                                </td>
                                <td id="tmp_total" valign="middle" align="right"
                                    style="width:120px;;padding: 10px 10px 10px 5px;">
                                    <b>₹{{ number_format((float) $balanceAmount, 2, '.', '') }}</b></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="pcs-template-footer"
             style="height: 0.700000in;font-size: 6pt;color: #aaaaaa;padding: 0 0.400000in 0 0.550000in;background-color: #ffffff;">
            <div>
            </div>
        </div>
    </div>
</div>
</body>

</html>