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
class FcfPayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $db = Db::getInstance();
//Receive the RAW post data.
        $content = trim(file_get_contents("php://input"));
        $t_content = Tools::getAllValues();
        $log_data = array(
            'p_input' => $content,
            't_input' => $t_content,
        );
        $fcfpay = Module::getInstanceByName('fcfpay');
        //$fcfpay->log($log_data);
		//$fcfpay->log("Received callback");

//Attempt to decode the incoming RAW post data from JSON.
        $decoded = json_decode($content, true);
        if(is_array($decoded)) {
            if(isset($decoded['success']) && $decoded['success']) {
                $data = $decoded['data'];
                $order_reference = $data['order_id'];
                $fcfpay->log("Received callback for order:");
                $fcfpay->log($order_reference);
                
                $request = array(
                    'order_id' => $order_reference
                  );
                $response = $fcfpay->sendApiRequest(json_encode($request),'check-order');
    
                $fcfpay = Module::getInstanceByName('fcfpay');
                //$fcfpay->log($response);
				$id_order = (int)$db->getValue("SELECT `id_order` FROM `"._DB_PREFIX_."orders` WHERE `reference`='".pSQL($order_reference)."'");
            if($id_order) {
                $order = new Order($id_order);
                if(isset($response['success']) && $response['success']) {
                    $data = $response['data'];
//                    $data['deposited'] = 'true';
                    $total = $order->total_paid;
                    $fiat = (float)$data['total_fiat_amount'];
                    if($fiat >= $total) {
                        $status = 'paid';
                    } else {
                        $status = 'unpaid';
                        $result['error'][] = "Order id $id_order is not paid yet";
                    }
                    $order_reference = $data['order_id'];
                    $updatedAt = date('Y-m-d H:i:s');
                    $exist = (int)$db->getValue("SELECT `id` FROM `"._DB_PREFIX_."fcfpay_orders` WHERE `order_reference`='".pSQL($order_reference)."'");
                        if($exist) {
                            $db->update(
                                "fcfpay_orders",
                                array(
                                    'unique_id' => pSQL($data['unique_id']),
                                    'transaction_id' => pSQL($data['txid']),
                                    'status' => pSQL($status),
                                    'data' => pSQL(json_encode($data)),
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
                                    'data' => pSQL(json_encode($data)),
                                    'updated_at' => pSQL($updatedAt),
                                )
                            );
                        }
                        foreach ($data['txs'] as $tx){
                            $tx_id = $tx['txid'];
                            $id_order_payment = (int)$db->getValue("SELECT `id_order_payment` FROM `"._DB_PREFIX_."order_payment` WHERE transaction_id='".pSQL($tx_id)."'");

                                if(!$id_order_payment) {
                                    $op = new OrderPayment();
                                    $op->order_reference = $order_reference;
                                    $op->id_currency = $order->id_currency;
                                    $op->amount = (float)$tx['fiat_amount'];
                                    $op->payment_method = "FCF Pay";
                                    $op->transaction_id = $tx['txid'];
                                    $op->add();
                                } else {
                                    $db->update(
                                        'order_payment',
                                        [
                                            'transaction_id' => pSQL($tx['txid']),
                                            'amount' =>(float)$tx['fiat_amount']
                                        ],
                                        'id_order_payment="'.(int)$id_order_payment.'"'
                                    );
                                }
                            }


                        if(trim($status == 'paid')) {
                            $id_order_state = Configuration::get('FCFPAY_SUCCESS_STATUS');
                            $fcfpay->changeStatus($id_order,$id_order_state);
                            
                            $result['success'][] = "Order $id_order is updated with paid status";
                        }
                    

                } else {
                    $id_order_state = Configuration::get('FCFPAY_FAILED_STATUS');
                    $fcfpay->changeStatus($id_order,$id_order_state);
                    $result['error'][] = "Order $id_order is  updated with failed status";
                }
            } else {
                $d = array('error' => "$order_reference does not exist in prestashop");
                $fcfpay->log($d);
            }
            }
        }

        die('true');
    }
}
