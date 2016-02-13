<?php

/*
 * this script didn't try to solve blocking captcha, just type it manually as I
 * just need 2002 pages ... (yes, it's stupid though)
 */
$cachePath = __DIR__ . '/cache';
if (!file_exists($cachePath)) {
    mkdir($cachePath, 0777, true);
}
$proxy = 'proxy.hinet.net:80';
$genderLength = strlen('性别：');
$typeLength = strlen('执业资格：');
$orgLength = strlen('执业单位：<a href="http://www.zhuakang.com/a?x=detail&id=');
$headerWritten = false;
$fh = fopen(__DIR__ . '/list.csv', 'w');

for ($i = 1; $i <= 2002; $i++) {
    error_log('processing page ' . $i);
    $cachedFile = $cachePath . '/p_' . $i;
    if (!file_exists($cachedFile)) {
        $c = '';
        $sleepCount = 0;
        while (empty($c)) {
            $curl = curl_init('http://yaoshi.zhuakang.com/area/710000/' . $i);
            curl_setopt($curl, CURLOPT_REFERER, 'http://yaoshi.zhuakang.com/area/710000/');
            //curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            $response = curl_exec($curl);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $c = substr($response, $header_size);
            if (!empty($c) && false === strpos($c, '流量异常，输入验证') && false === strpos($c, 'Connection timed out')) {
                file_put_contents($cachedFile, $c);
            } else {
                $c = '';
                ++$sleepCount;
                error_log('empty result, delay 1 second(' . $sleepCount . ')');
                error_log($header);
                sleep(1);
            }
        }
    }
    $c = file_get_contents($cachedFile);
    $pos = strpos($c, '<h3 id="item_name_');
    while (false !== $pos) {
        $data = array();
        $pos += 18;
        $posEnd = strpos($c, '"', $pos);
        $data['id'] = substr($c, $pos, $posEnd - $pos);
        $pos = strpos($c, '/">', $posEnd) + 3;
        $posEnd = strpos($c, '</a>', $pos);
        $data['name'] = substr($c, $pos, $posEnd - $pos);
        $pos = strpos($c, '性别：', $posEnd) + $genderLength;
        $posEnd = strpos($c, '</td>', $pos);
        $data['gender'] = substr($c, $pos, $posEnd - $pos);
        $pos = strpos($c, '执业资格：', $posEnd) + $typeLength;
        $posEnd = strpos($c, '</td>', $pos);
        $data['type'] = substr($c, $pos, $posEnd - $pos);
        $pos = strpos($c, '执业单位：<a href="http://www.zhuakang.com/a?x=detail&id=', $posEnd);
        if (false === $pos) {
            $data['organization'] = '';
        } else {
            $pos += $orgLength;
            $posEnd = strpos($c, '">', $pos);
            $data['organization'] = substr($c, $pos, $posEnd - $pos);
        }

        $pos = strpos($c, '地区：', $posEnd);
        $pos = strpos($c, '">', $pos) + 2;
        $posEnd = strpos($c, '</a>', $pos);
        $data['area'] = substr($c, $pos, $posEnd - $pos);
        if (false === $headerWritten) {
            $headerWritten = true;
            fputcsv($fh, array_keys($data));
        }
        fputcsv($fh, $data);
        $pos = strpos($c, '<h3 id="item_name_', $posEnd);
    }
}