<?php

function generateRandomString($length = 25)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

error_reporting(E_ALL);

if (!file_exists(dirname(__FILE__) . "/dummies.jpeg")) {
    $fh = fopen((dirname(__FILE__) . "/dummies.jpeg"), 'w');
    $size = 1024 * 1024 * 9; // 10mb
    $chunk = 1024;
    while ($size > 0) {
        fputs($fh, str_pad('', min($chunk, $size)));
        $size -= $chunk;
    }
    fclose($fh);
}


$curlLimit = 100;
$total     = 10000000;

$iterations = ceil($total/$curlLimit);

echo 'Всего: ' . $iterations . PHP_EOL;
for ($i = 1; $i <= $iterations; $i++) {
    $multiCurl  = curl_multi_init();
    $curls      = [];


    for ($k = 1; $k <= $curlLimit; $k++) {
        $cf = new CURLFile(dirname(__FILE__) . "/dummies.jpeg", 'image/jpeg', generateRandomString(25).'.jpg');

        $curlOptions = [
            CURLOPT_HEADER                  => false,
            CURLOPT_FOLLOWLOCATION          => false,
            CURLOPT_MAXREDIRS               => 1,
            CURLOPT_POSTREDIR               => CURL_REDIR_POST_ALL,
            CURLOPT_SSL_VERIFYPEER          => false,
            CURLOPT_SSL_VERIFYHOST          => false,
            CURLOPT_TIMEOUT                 => 50,
            CURLOPT_CONNECTTIMEOUT          => 30,
            CURLOPT_RETURNTRANSFER          => true,
            CURLOPT_VERBOSE                 => false,
            CURLOPT_HTTP_VERSION            => CURL_HTTP_VERSION_1_1,
            CURLOPT_REFERER                 => 'https://pos.gosuslugi.ru/inbox-service/filestorage',
            CURLOPT_POST                    => true,
            CURLOPT_POSTFIELDS              => ["file" => $cf],
        ];


        $curls[$k] = curl_init('https://pos.gosuslugi.ru/inbox-service/filestorage');
        curl_setopt_array($curls[$k], $curlOptions);
        curl_multi_add_handle($multiCurl, $curls[$k]);
    }


    do {
        $status = curl_multi_exec($multiCurl, $active);
        if ($active) {
            curl_multi_select($multiCurl);
        }
    } while ($active && $status == CURLM_OK);


    $results = [];
    foreach ($curls as $k => $curl) {
        $r = json_decode(curl_multi_getcontent($curl), true);
        echo $k . ': ' . curl_getinfo($curl, CURLINFO_HTTP_CODE) .': '. $r['id'] . PHP_EOL;
    }

    unset($curls);
    unset($multiCurl);
}
