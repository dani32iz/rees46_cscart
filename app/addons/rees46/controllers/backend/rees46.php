<?php
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'export_orders') {
    if ((Registry::get('addons.rees46.shop_id') == '') || (Registry::get('addons.rees46.shop_secret') == '')) {
        fn_set_notification('E', 'Ошибка' ,'Для выгрузки заказов введите код и секретный ключ вашего магазина в настройках модуля.', 'I');
    } elseif (Registry::get('addons.rees46.already_exported') == 'true') {
        fn_set_notification('E', 'Ошибка' ,'Вы уже выгрузили заказы', 'I');
    } else {
        $params = array('timestamp > ' . strtotime('-6 months'), 'items_per_page' => 1000);
        $orders = fn_get_orders($params);

        $processed_orders = array();

        foreach ($orders[0] as $order) {
            $order_info = fn_get_order_info($order['order_id']);

            $items_formatted_info = array();
            foreach ($order_info['products'] as $product) {
                $product_formatted = array(
                    'id' =>  $product['product_id'],
                    'price' => $product['price'],
                    'amount' => $product['amount']
                );

                array_push($items_formatted_info, $product_formatted);
            }

            $order_formatted_info = array(
                'id' => $order_info['order_id'],
                'user_id' => $order_info['user_id'],
                'user_email' => $order_info['email'],
                'date' => $order_info['timestamp'],
                'items' => $items_formatted_info
            );

            array_push($processed_orders, $order_formatted_info);
        }

        $result = array(
            'shop_id' => Registry::get('addons.rees46.shop_id'),
            'shop_secret' => Registry::get('addons.rees46.shop_secret'),
            'orders' => $processed_orders
        );

        $pest = new \PestJSON('http://api.rees46.com');
        $pest->post('/import/orders.json', $result);

        Registry::set('addons.rees46.already_exported', 'true');

        fn_set_notification('N', 'Выгрузка заказов в REES46 успешно завершена.', '', 'I');
    }

    return array(CONTROLLER_STATUS_REDIRECT, "addons.manage");
}
