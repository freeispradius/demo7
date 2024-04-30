<?php

/**
 *  PHP Mikrotik Billing (https://freeispradius.com/)
 *  by https://t.me/freeispradius
 **/
use PEAR2\Net\RouterOS;
use PEAR2\Net\RouterOS\Query;
use PEAR2\Net\RouterOS\Response;

class Mikrotik


{
    public static function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    public static function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $iport = explode(":", $ip);
        return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
    }

    public static function isUserLogin($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    public static function logMeIn($client, $user, $pass, $ip, $mac)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $user)
                ->setArgument('password', $pass)
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac)
        );
    }

    public static function logMeOut($client, $user)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $user)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    public static function addHotspotPlan($client, $name, $sharedusers, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('shared-users', $sharedusers)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setHotspotPlan($client, $name, $sharedusers, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            Mikrotik::addHotspotPlan($client, $name, $sharedusers, $rate);
        } else {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', $sharedusers)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function setHotspotExpiredPlan($client, $name, $pool)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $name)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '1K/1K')
            );
        } else {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '1K/1K')
            );
        }
    }

    public static function removeHotspotPlan($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    public static function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    public static function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                );
            } else if ($plan['limit_type'] == "Data_Limit") {
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            } else if ($plan['limit_type'] == "Both_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $customer['username'])
                    ->setArgument('profile', $plan['name_plan'])
                    ->setArgument('comment', $customer['fullname'])
                    ->setArgument('email', $customer['email'])
                    ->setArgument('password', $customer['password'])
            );
        }
    }

    public static function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setHotspotUserPackage($client, $user, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
        $client->sendSync($setRequest);
    }

    public static function removeHotspotActiveUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function removePpoeUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function addPpoeUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ppp/secret/add');
        if (!empty($customer['pppoe_password'])) {
            $pass = $customer['pppoe_password'];
        } else {
            $pass = $customer['password'];
        }
        $client->sendSync(
            $addRequest
                ->setArgument('name', $customer['username'])
                ->setArgument('service', 'pppoe')
                ->setArgument('profile', $plan['name_plan'])
                ->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'])
                ->setArgument('password', $pass)
        );
    }

    public static function setPpoeUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setPpoeUserPlan($client, $user, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
        $client->sendSync($setRequest);
    }

    public static function removePpoeActive($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function removePool($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }

    public static function addPool($client, $name, $ip_address)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('ranges', $ip_address)
        );
    }

    public static function setPool($client, $name, $ip_address)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');

        if (empty($poolID)) {
            self::addPool($client, $name, $ip_address);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('ranges', $ip_address)
            );
        }
    }


    public static function addPpoePlan($client, $name, $pool, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('local-address', $pool)
                ->setArgument('remote-address', $pool)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setPpoePlan($client, $name, $pool, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            self::addPpoePlan($client, $name, $pool, $rate);
        } else {
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('local-address', $pool)
                    ->setArgument('remote-address', $pool)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function removePpoePlan($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    public static function sendSMS($client, $to, $message)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $smsRequest = new RouterOS\Request('/tool sms send');
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        $client->sendSync($smsRequest);
    }

    public static function getIpHotspotUser($client, $username){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }





///static plan added by me
//dont use this yet maybe later
//static plan added by me
//dont use this yet maybe later
//static plan added by me
//dont use this yet maybe later
//static plan added by me
//dont use this yet maybe later







public static function setStaticPlan($client, $name, $ipAddress, $rateLimit)
{
    global $_app_stage;
    if ($_app_stage == 'demo') {
        return null;
    }

    // Check if the static IP address is already assigned
    $printRequest = new RouterOS\Request(
        '/ip firewall address-list print .proplist=.id',
        RouterOS\Query::where('address', $ipAddress)
    );
    $addressID = $client->sendSync($printRequest)->getProperty('.id');

    // Add or update the address in the address list
    if (empty($addressID)) {
        // Add new address to the list
        $addRequest = new RouterOS\Request('/ip/firewall/address-list/add');
        $client->sendSync(
            $addRequest
                ->setArgument('list', $name)
                ->setArgument('address', $ipAddress)
        );
    } else {
        // Update existing address in the list
        $setRequest = new RouterOS\Request('/ip/firewall/address-list/set');
        $client->sendSync(
            $setRequest
                ->setArgument('numbers', $addressID)
                ->setArgument('list', $name)
                ->setArgument('address', $ipAddress)
        );
    }

    // Set rate limit for the static IP using simple queue
    $queueRequest = new RouterOS\Request(
        '/queue simple print .proplist=.id',
        RouterOS\Query::where('target', $ipAddress)
    );
    $queueID = $client->sendSync($queueRequest)->getProperty('.id');

    if (empty($queueID)) {
        // Add new queue for rate limiting
        $addQueueRequest = new RouterOS\Request('/queue/simple/add');
        $client->sendSync(
            $addQueueRequest
                ->setArgument('name', 'Queue-' . $name)
                ->setArgument('target', $ipAddress)
                ->setArgument('max-limit', $rateLimit)
        );
    } else {
        // Update existing queue for rate limiting
        $setQueueRequest = new RouterOS\Request('/queue/simple/set');
        $client->sendSync(
            $setQueueRequest
                ->setArgument('numbers', $queueID)
                ->setArgument('max-limit', $rateLimit)
        );
    }
}


public static function removeStaticPlan($client, $ipAddress)
{
    global $_app_stage;
    if ($_app_stage == 'demo') {
        return null;
    }

    // Find the address list entry with the specified IP address
    $printRequest = new RouterOS\Request(
        '/ip/firewall/address-list/print',
        RouterOS\Query::where('address', $ipAddress)
    );
    $addressID = $client->sendSync($printRequest)->getProperty('.id');

    // If the entry exists, remove it
    if ($addressID !== null) {
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        $client->sendSync(
            $removeRequest->setArgument('numbers', $addressID)
        );
    }

    // Optionally, you might also want to remove any associated rate limiting queues
    // for the given IP address, similar to what you've done in the setStaticIPPlan method
}


public static function addStaticPlan($client, $name, $pool, $rateLimit) {
    global $_app_stage;
    if ($_app_stage == 'demo') {
        return null;
    }

    // Add new PPP profile with the base network IP
    $addProfileRequest = new RouterOS\Request('/ppp/profile/add');
    $client->sendSync(
        $addProfileRequest
            ->setArgument('name', $name)
            ->setArgument('local-address', $pool)
            ->setArgument('rate-limit', $rateLimit)
    );








}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
public static function addStaticUser($client, $plan, $customer)
{
    global $_app_stage;
    if ($_app_stage == 'demo') {
        return null;
    }

    // Check if IP address is provided for the customer, otherwise use a placeholder
    $ipAddress = !empty($customer['ip_address']) ? $customer['ip_address'] : '10.10.10.10';

    // Define names for the queue and address list entries
    $queueName = !empty($customer['ip_address']) ? 'Queue-' . $customer['username'] : 'Queue [username] IP not found';
    $addressListName = !empty($customer['ip_address']) ? $customer['username'] : 'IP not found';

    // Add the IP to the RouterOS address list named 'allowed' or with a specific name if IP not found
    $addAddressListRequest = new RouterOS\Request('/ip/firewall/address-list/add');
    $client->sendSync(
        $addAddressListRequest
            ->setArgument('list', 'allowed')
            ->setArgument('address', $ipAddress)
            ->setArgument('comment', $addressListName) // Use comment to differentiate entries
    );

    // Retrieve the bandwidth details from the database
    $bandwidth = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
    $rateUp = $bandwidth['rate_up'] . (strtolower($bandwidth['rate_up_unit']) == 'kbps' ? 'k' : 'M');
    $rateDown = $bandwidth['rate_down'] . (strtolower($bandwidth['rate_down_unit']) == 'kbps' ? 'k' : 'M');
    $rateLimit = $rateUp . '/' . $rateDown;

    // Set up a simple queue for rate limiting, using placeholder data if no IP address is found
    $addQueueRequest = new RouterOS\Request('/queue/simple/add');
    $client->sendSync(
        $addQueueRequest
            ->setArgument('name', $queueName)
            ->setArgument('target', $ipAddress)
            ->setArgument('max-limit', $rateLimit)
    );
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////public static function removeStaticUser($client, $customer) {
    public static function removeStaticUser($client, $username) {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
    
        // Retrieve the customer data from the database
        $customer = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
    
        if (!$customer) {
            // Handle the case where the customer was not found in the database
            return;
        }
    
        // Get the IP address from the customer data
        $ipAddress = $customer->ip_address;
    
        try {
            // Find the address list entry
            $findAddressListRequest = new RouterOS\Request('/ip/firewall/address-list/print');
            $findAddressListRequest->setQuery(Query::where('list', 'allowed')->andWhere('address', $ipAddress));
            $addressListResponses = $client->sendSync($findAddressListRequest);
            foreach ($addressListResponses as $addressListResponse) {
                if ($addressListResponse->getType() === RouterOS\Response::TYPE_DATA) {
                    $addressListId = $addressListResponse->getProperty('.id');
                    // Verify if the address exactly matches the IP address from customer data
                    $address = $addressListResponse->getProperty('address');
                    if ($address === $ipAddress) {
                        // Remove the address list entry only if it matches the IP address
                        $removeAddressListRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
                        $removeAddressListRequest->setArgument('.id', $addressListId);
                        $client->sendSync($removeAddressListRequest);
                    }
                }
            }
    
            $findQueueRequest = new RouterOS\Request('/queue/simple/print');
            $findQueueRequest->setQuery(Query::where('target', $ipAddress .'/32'));
            $queueResponses = $client->sendSync($findQueueRequest);
    
            foreach ($queueResponses as $queueResponse) {
                if ($queueResponse->getType() === RouterOS\Response::TYPE_DATA) {
                    $queueId = $queueResponse->getProperty('.id');
                    // Verify if the queue target exactly matches the IP address
                    $target = $queueResponse->getProperty('target');
                    if ($target === $ipAddress .'/32') {
                        // Remove the queue only if it matches the IP address
                        $removeQueueRequest = new RouterOS\Request('/queue/simple/remove');
                        $removeQueueRequest->setArgument('.id', $queueId);
                        $client->sendSync($removeQueueRequest);
                    }
                }
            }
    
        } catch (Exception $e) {
            // Handle the error
            // Error handling logic here
        }
    }

    public static function rebootRouter($ip_address, $username, $password)
    {
        try {
            $client = Mikrotik::getClient($ip_address, $username, $password);
            $request = new RouterOS\Request('/system/reboot');
            $client->sendSync($request);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function pingRouter($ip_address, $username, $password)
    {
        try {
            $client = Mikrotik::getClient($ip_address, $username, $password);
            $request = new RouterOS\Request('/ping');
            $request->setArgument('address', $ip_address);
            $request->setArgument('count', 1);
            $response = $client->sendSync($request);
            return $response->getType() === RouterOS\Response::TYPE_FINAL;
        } catch (Exception $e) {
            return false;
        }
    }  

}