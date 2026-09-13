<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }

$input=json_decode(file_get_contents('php://input') ?: '{}', true);
$query=trim((string)($input['query'] ?? ''));
$num=max(1,min(50,(int)($input['numResults'] ?? 20)));
if ($query==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'query required']); exit; }

$apiKey=getenv('EXA_API_KEY') ?: ($_SERVER['EXA_API_KEY'] ?? '');
if (!$apiKey && is_file(__DIR__.'/../.secrets/exa.php')) {
  $secret=require __DIR__.'/../.secrets/exa.php';
  if (is_array($secret)) $apiKey=$secret['EXA_API_KEY'] ?? '';
}
if (!$apiKey) { http_response_code(503); echo json_encode(['ok'=>false,'error'=>'SEARCH_PROVIDER_NOT_CONFIGURED','message'=>'Configure EXA_API_KEY outside the public web root.']); exit; }

$payload=json_encode(['query'=>$query,'type'=>'auto','numResults'=>$num,'contents'=>['highlights'=>['maxCharacters'=>2500],'summary'=>['query'=>$query,'maxCharacters'=>1200]]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$ch=curl_init('https://api.exa.ai/search');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey],CURLOPT_POSTFIELDS=>$payload]);
$response=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
if ($response===false) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'provider_request_failed','detail'=>$err]); exit; }
$data=json_decode($response,true);
if ($code<200 || $code>=300) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'provider_error','provider_status'=>$code]); exit; }

$results=[];
foreach (($data['results'] ?? []) as $x) {
  $h=$x['highlights'] ?? [];
  $results[]=['company'=>$x['title'] ?? '','website'=>$x['url'] ?? '','source'=>$x['url'] ?? '','evidence'=>is_array($h)?implode(' ',$h):'','description'=>$x['summary'] ?? '','status'=>'new','record_type'=>'discovered','source_date'=>date('c')];
}
echo json_encode(['ok'=>true,'query'=>$query,'count'=>count($results),'results'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
