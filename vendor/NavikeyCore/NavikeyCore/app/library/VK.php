<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

/**
 * Description of vk
 *
 * @author Novaks
 */
class VK
{

    public function __construct()
    {

    }

    public function getUser(string $appsec, $app_cookie)
    {
        $session = [];
        $member = false;
        $valid_keys = ['expire', 'mid', 'secret', 'sid', 'sig'];
        if ($app_cookie) {
            $session_data = explode('&', $app_cookie, 10);
            foreach ($session_data as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                if (empty($key) || empty($value) || !in_array($key, $valid_keys)) {
                    continue;
                }
                $session[$key] = $value;
            }
            foreach ($valid_keys as $key) {
                if (!isset($session[$key])) {
                    return $member;
                }
            }
            ksort($session);
            $sign = '';
            foreach ($session as $key => $value) {
                if ($key != 'sig') {
                    $sign .= ($key . '=' . $value);
                }
            }
            $sign_md5 = md5($sign . $appsec);
            if ($session['sig'] == $sign_md5 && $session['expire'] > time()) {
                $member = array(
                    'id' => intval($session['mid']),
                    'secret' => $session['secret'],
                    'sid' => $session['sid']
                );
            }
        }
        return $member;
    }

}
