<?php
header('Content-Type: application/json; charset=utf-8');
$file = __DIR__ . '/gold-transactions.json';
if(!file_exists($file)){
    file_put_contents($file, '[]', LOCK_EX);
}

$method = $_SERVER['REQUEST_METHOD'];

function load_all($file){
    $json = file_get_contents($file);
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function save_all($file, $arr){
    $fp = fopen($file, 'c+');
    if(!$fp){
        return false;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($arr, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

if($method === 'GET'){
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $rows = load_all($file);
    if($id){
        foreach($rows as $r){
            if(isset($r['id']) && $r['id'] === $id){
                echo json_encode(['ok'=>true,'record'=>$r]);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Not found']);
        exit;
    } else {
        echo json_encode(['ok'=>true,'count'=>count($rows)]);
        exit;
    }
}

if($method === 'POST'){
    $input = json_decode(file_get_contents('php://input'), true);
    if(!$input){
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);
        exit;
    }

    $rows = load_all($file);
    $id = $input['id'] ?? null;
    $nowIso = date('c');

    // sanitize
    $record = [
        'id' => $id ? preg_replace('/[^A-Za-z0-9\-]/','',$id) : null,
        'customer' => [
            'name' => trim($input['customer']['name'] ?? ''),
            'phone'=> trim($input['customer']['phone'] ?? ''),
            'email'=> trim($input['customer']['email'] ?? '')
        ],
        'metal' => in_array($input['metal'] ?? '', ['gold','silver','platinum']) ? $input['metal'] : 'gold',
        'purity'=> [
            'type' => $input['purity']['type'] ?? '',
            'value'=> floatval($input['purity']['value'] ?? 0)
        ],
        'weights'=> [
            'g' => floatval($input['weights']['g'] ?? 0),
            'dwt'=> floatval($input['weights']['dwt'] ?? 0)
        ],
        'factor'=> floatval($input['factor'] ?? 0),
        'fees'=> [
            'percent' => floatval($input['fees']['percent'] ?? 0),
            'flat' => floatval($input['fees']['flat'] ?? 0)
        ],
        'computed'=> [
            'spot' => floatval($input['computed']['spot'] ?? 0),
            'spotPerGram' => floatval($input['computed']['spotPerGram'] ?? 0),
            'baseValue' => floatval($input['computed']['baseValue'] ?? 0),
            'payout' => floatval($input['computed']['payout'] ?? 0)
        ]
    ];

    if(!$record['id']){
        $rand = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),0,4);
        $record['id'] = 'GT-'.date('Ymd-His').'-'.$rand;
        $record['createdAt'] = $nowIso;
        $rows[] = $record;
    } else {
        $found = false;
        foreach($rows as &$r){
            if($r['id'] === $record['id']){
                $record['createdAt'] = $r['createdAt'] ?? $nowIso;
                $record['updatedAt'] = $nowIso;
                $r = $record;
                $found = true;
                break;
            }
        }
        if(!$found){
            http_response_code(404);
            echo json_encode(['ok'=>false,'error'=>'ID not found']);
            exit;
        }
    }

    save_all($file, $rows);
    echo json_encode(['ok'=>true,'id'=>$record['id'],'record'=>$record]);
    exit;
}

http_response_code(405);
print json_encode(['ok'=>false,'error'=>'Method not allowed']);
?>
