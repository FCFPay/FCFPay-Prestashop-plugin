<?php
/**
 *  Copyright (C) FCF Inc. - All Rights Reserved
 *
 *
 *  @author    FCF Inc.
 *  @copyright 2020-2022 FCF Inc.
 *  @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * @since 1.5.0
 */
class FcfPayValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'fcfpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', [], 'Modules.Checkpayment.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $storeCurrency = $this->context->currency;
        $currency_code = $storeCurrency->iso_code;
        $currency_code_numeric = $storeCurrency->iso_code_num;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $os = Configuration::get('FCFPAY_INITIAL_STATUS');
        $displayName = 'FCF Pay';
        $update_date = date('Y-m-d');
        $mailVars =    array();
        $fcfpay = Module::getInstanceByName('fcfpay');


        try {
            $fcfpay->validateOrder(
                (int) $cart->id,
                (int) $os,
                $total,
                $displayName,
                null,
                $mailVars,
                (int) $storeCurrency->id,
                false,
                $customer->secure_key
            );
            $order = new Order($fcfpay->currentOrder);
            if(!empty($order->reference)) {
                $domain = $this->context->shop->getBaseURL(true);
                $order_reference = $order->reference;
                $id_order = $order->id;
                $callback = $this->context->shop->getBaseURL() . 'modules/fcfpay/callback.php';
                $redirect_url = $this->context->shop->getBaseURL().'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$fcfpay->id.'&id_order='.$fcfpay->currentOrder.'&key='.$customer->secure_key;

                // check if FCFPAY_SEND_ITEM_DETAILS is enabled 
                if(Configuration::get('FCFPAY_SEND_ITEM_DETAILS') == 1) {
                    $products = $cart->getProducts();
                    $items = array();
                    $itemIndex = 1;
                    foreach ($products as $product) {
                        $items[$itemIndex] = array(
                            'Item Name' => $product['name'],
                            'Quantity' => $product['quantity'],
                            'Price' => $product['price_wt'],
                            'Total' => $product['price_wt'] * $product['quantity'],
                        );
                        $itemIndex++;
                    }

                    $data = array(
                        'domain' => $domain,
                        'order_id' => $order_reference,
                        'user_id' => $customer->id,
                        'amount' => $total,
                        'currency_name' => $currency_code,
                        'order_date' => $update_date,
                        'redirect_url' => $redirect_url,
                        'items' => $items,
                    );                 
                } else {
                    $data = array(
                        'domain' => $domain,
                        'order_id' => $order_reference,
                        'user_id' => $customer->id,
                        'amount' => $total,
                        'currency_name' => $currency_code,
                        'currency_code' => $currency_code_numeric,
                        'order_date' => $update_date,
                        'redirect_url' => $redirect_url,
                    );
                }

               $request = json_encode($data, true);
               $response = $fcfpay->sendApiRequest($request);
                if (isset($response['success']) && $response['success']) {
                    $this->updateFcfOrder($id_order,$order_reference);
                    Tools::redirect($response['data']['checkout_page_url']);

                } else {
                    $error = "Failed to create order, Please choose other payment option";
                    if (isset($response['message'])) {
                        $error = "FCF Pay Error : ".$response['message'];
                    }
                    die($error);
                }
            }
        } catch (\Exception $e) {
            if($order->id) {
                $order->delete();
            }
            die("Failed to create order ".$e->getMessage());
        }

    }
    public function updateFcfOrder($id_order, $order_reference = '')
    {
        $status = 'unpaid';
        $updatedAt = date('Y-m-d H:i:s');
        $db = Db::getInstance();
        $exist = (int)$db->getValue("SELECT `id` FROM `"._DB_PREFIX_."fcfpay_orders` WHERE `order_reference`='".pSQL($order_reference)."'");
        if($exist) {
            $db->update(
                "fcfpay_orders",
                array(
                    'id_order' => (int)$id_order,
                    'status' => pSQL($status),
                    'updated_at' => pSQL($updatedAt),
                ),
                'order_reference="'.pSQL($order_reference).'"'
            );
        } else {
            $db->insert(
                'fcfpay_orders',
                array(
                    'id_order' => (int)$id_order,
                    'order_reference' => pSQL($order_reference),
                    'status' => pSQL($status),
                    'updated_at' => pSQL($updatedAt),
                )
            );
        }
    }

}
