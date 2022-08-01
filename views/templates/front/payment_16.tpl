<!--
 /**
 *  Copyright (C) FCF Inc. - All Rights Reserved
 *
 *
 *  @author    FCF Inc.
 *  @copyright 2020-2022 FCF Inc.
 *  @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
 -->

<style>
    p.payment_module a.fcfpay::after{
        display: block;
        content: "\f054";
        position: absolute;
        right: 15px;
        margin-top: -11px;
        top: 50%;
        font-family: "FontAwesome";
        font-size: 25px;
        height: 22px;
        width: 14px;
        color: #777;
    }
</style>
<input type="hidden" id="fcfpay_ajax_url" value="{$fcfpay_ajax_url}">
<input type="hidden" id="id_shop" value="{$id_shop}">
<input type="hidden" id="id_cart" value="{$id_cart}">
<input type="hidden" id="id_customer" value="{$id_customer}">
{foreach $methods as $method}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a style="cursor:pointer;padding-left: 15px;" class="fcfpay" onclick="redirectToFcfPay('{$method['payment_code']}')" title="{$method['name']}">
                <img src="{$method['logo']}" alt="{$method['name']}" width="86" height="49"/>
                {$method['name']}
            </a>
        </p>
    </div></div>
{/foreach}
<script type="text/javascript">
    ajax_url = "{$ajax_url}";
    id_cart = {$order_id};
    id_shop = {$shop_id};
    id_customer = {$customer_id};
</script>
