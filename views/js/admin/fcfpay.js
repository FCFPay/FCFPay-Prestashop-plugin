/**
 *  Copyright (C) FCF Inc. - All Rights Reserved
 *
 *
 *  @author    FCF Inc.
 *  @copyright 2020-2022 FCF Inc.
 *  @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

$(document).ready(function () {
    var type = $("#FCFPAY_PAYMENT_MODE").val();
    console.log(type);
    if (type == 'all') {
  /*
      $("#custom_payment_configurations").closest('.form-group').hide();
      $("#fcfpay_configurations").closest('.form-group').show();*/
        $("#custom_payment_configurations").hide();
        $("#fcfpay_configurations").show();
    } else {
      /*$("#custom_payment_configurations").closest('.form-group').show();
      $("#fcfpay_configurations").closest('.form-group').hide();*/
        $("#custom_payment_configurations").show();
        $("#fcfpay_configurations").hide();
    }
    $("#FCFPAY_PAYMENT_MODE").on('change',function () {
        var type = $("#FCFPAY_PAYMENT_MODE").val();
        if (type == 'all') {
            $("#custom_payment_configurations").hide();
            $("#fcfpay_configurations").show();
        } else {
            $("#custom_payment_configurations").show();
            $("#fcfpay_configurations").hide();
        }
    });

});

function addNewPaymentConfiguration()
{
    var next_row = $("#custom_payment_grid tbody").find("tr").length + 1;
    var nt = $("#custom_payment_grid tbody tr:last").attr('id');
    if (!isNaN(parseInt(nt, 10)) && nt == next_row) {
        next_row = next_row+1;
    }
    var new_row = "";
    new_row += "<tr id='"+next_row+"'>";
    new_row += "<td><input type='text' class='input-text' name='FCFPAY_CUSTOM_METHODS["+next_row+"][name]'></td>";
    new_row += "<td><input type='text' class='input-text' name='FCFPAY_CUSTOM_METHODS["+next_row+"][code]'></td>";
    new_row += "<td><input type='text' class='input-text' name='FCFPAY_CUSTOM_METHODS["+next_row+"][currency]'></td>";
    new_row += "<td><button class='btn btn-danger' onclick='deleteRow(this)'><span><i class='icon-eraser'></i></span></button></td>";
    $("#custom_payment_grid tbody:last-child").append(
        new_row
    );

}
function deleteRow(obj)
{
    $(obj).closest("tr").remove();
}