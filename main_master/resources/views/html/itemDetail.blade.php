<tr>
    <td></td>
</tr>
@if (count($item_details))
@foreach ($item_details as $key => $details)
<tr class="breakrow-inside breakrow-after" style="height:20px;">
    <td rowspan="1" valign="top" style="text-align: center;" class="pcs-item-row">
        {{ $key + 1 }}
    </td>
    <td rowspan="1" valign="top" class="pcs-item-row" id="tmp_item_name">
        <div>
            <div>
                <span style="word-wrap: break-word;"
                    id="tmp_item_name">{{ model($details->item, 'item_name') }}</span><br>
                <span style="white-space: pre-wrap;word-wrap: break-word;" class="pcs-item-desc"
                    id="tmp_item_description"></span><br>
            </div>
        </div>
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_qty">
        {{ model($details->itemUom, 'name') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_rate">
        {{ model($details, 'item_qty') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_price') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_discount_amount') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_vat') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_net') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_excise') }}
    </td>
    <td rowspan="1" valign="top" style="text-align: left;" class="pcs-item-row" id="tmp_item_amount">
        {{ model($details, 'item_grand_total') }}
    </td>
</tr>
@endforeach
@endif