<?php

/**
 *  PHP Mikrotik Billing (https://freeispradius.com/)
 *  by https://t.me/freeispradius
 **/


class User
{
    public static function _info()
    {
        $id = $_SESSION['uid'];
        $d = ORM::for_table('tbl_customers')->find_one($id);

        if(empty($d['username'])){
            r2(U . 'logout', 'd', '');
        }
        return $d;
    }

    public static function _billing()
    {
        $id = $_SESSION['uid'];
        $d = ORM::for_table('tbl_user_recharges')->where('customer_id', $id)->find_many();
        return $d;
    }
}
