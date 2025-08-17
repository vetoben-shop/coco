<?php
header('Content-Type: application/json; charset=utf-8');

$cacheFile = __DIR__ . '/gold-price-cache.json';
$ttl = 60; // cache seconds
$source = 'https://data-asg.goldprice.org/dbXRates/USD';

$now = time();
$mode = 'live';
$age = 0;
$data = null;

if(file_exists($cacheFile)){
    $cached = json_decode(file_get_contents($cacheFile), true);
    if($cached){
        $age = $now - ($cached['timestamp'] ?? 0);
        if($age < $ttl){
            $mode = 'file';
            $data = $cached;
        }
    }
}

if(!$data){
    $ch = curl_init($source);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_USERAGENT => 'curl',
    ]);
    $body = curl_exec($ch);
    $err = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if(!$err && $status === 200){
        $j = json_decode($body, true);
        if(isset($j['items'][0])){
            $item = $j['items'][0];
            $data = [
                'timestamp' => $now,
                'gold' => ['symbol'=>'XAUUSD', 'spot'=>floatval($item['xauPrice'] ?? 0)],
                'silver'=> ['symbol'=>'XAGUSD', 'spot'=>floatval($item['xagPrice'] ?? 0)],
                'platinum'=> ['symbol'=>'XPTUSD', 'spot'=>floatval($item['xptPrice'] ?? 0)],
            ];
            file_put_contents($cacheFile, json_encode($data), LOCK_EX);
            $mode = 'live';
            $age = 0;
        }
    }
}

if(!$data){
    if(isset($cached) && $cached){
        $data = $cached;
        $mode = 'file';
        $age = $now - ($cached['timestamp'] ?? $now);
    }
}

if(!$data){
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Unable to fetch prices']);
    exit;
}

$response = [
    'ok' => true,
    'timestamp' => $data['timestamp'],
    'source' => $source,
    'cache' => ['age'=>$age,'mode'=>$mode],
    'unit' => 'USD/oz',
    'gold' => $data['gold'],
    'silver' => $data['silver'],
    'platinum' => $data['platinum'],
];

echo json_encode($response);
?>
