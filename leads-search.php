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
$num=max(1,min(20,(int)($input['numResults'] ?? 10)));
if ($query==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'query required']); exit; }

function http_get(string $url): string {
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36']);
  $body=curl_exec($ch); curl_close($ch);
  return is_string($body)?$body:'';
}
function clean_text(string $html): string {
  $html=preg_replace('/<script[^>]*>.*?</script>|<style[^>]*>.*?</style>/is',' ',$html);
  return trim(preg_replace('/s+/',' ',strip_tags($html)));
}
function parse_public_search(string $html, int $limit): array {
  $out=[];
  if ($html==='') return $out;
  if (preg_match_all('/<a[^>]+class="result__a"[^>]+href="([^"]+)"[^>]*>(.*?)</a>/is',$html,$m,PREG_SET_ORDER)) {
    foreach($m as $row){$url=html_entity_decode($row[1],ENT_QUOTES);$title=clean_text($row[2]);if(stripos($url,'uddg=')!==false){$p=parse_url($url,PHP_URL_QUERY);parse_str((string)$p,$q);$url=$q['uddg']??$url;}if(!preg_match('#^https?://#i',$url))continue;$out[]=['title'=>$title,'url'=>$url];if(count($out)>=$limit)break;}
  }
  return $out;
}
function extract_contacts(string $text): array {
  $email='';$phone='';$whatsapp='';
  if(preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+.[A-Z]{2,}/i',$text,$m))$email=$m[0];
  if(preg_match('/(?:+?d[ds().-]{7,}d)/',$text,$m))$phone=trim($m[0]);
  if(preg_match('/(?:whatsapp(?:s*(?:business|me))?[^+d]{0,30})(+?d[ds().-]{7,}d)/i',$text,$m))$whatsapp=trim($m[1]);
  return [$email,$phone,$whatsapp];
}

$apiKey=getenv('EXA_API_KEY') ?: ($_SERVER['EXA_API_KEY'] ?? '');
$results=[];
if($apiKey){
  $payload=json_encode(['query'=>$query,'type'=>'auto','numResults'=>$num,'contents'=>['highlights'=>['maxCharacters'=>2500],'summary'=>['query'=>$query,'maxCharacters'=>1200]]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $ch=curl_init('https://api.exa.ai/search');
  curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey],CURLOPT_POSTFIELDS=>$payload]);
  $response=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  $data=json_decode(is_string($response)?$response:'{}',true);
  if($code>=200&&$code<300){
    foreach(($data['results']??[]) as $x){
      $h=$x['highlights']??[];$e=is_array($h)?implode(' ',$h):'';
      [$email,$phone,$whatsapp]=extract_contacts($e.' '.($x['summary']??''));
      $results[]=['company'=>$x['title']??'','website'=>$x['url']??'','source'=>$x['url']??'','evidence'=>$e,'description'=>$x['summary']??'','email'=>$email,'phone'=>$phone,'whatsapp'=>$whatsapp,'status'=>'new','record_type'=>'discovered','source_date'=>date('c')];
    }
  }
}
if(!$results){
  $searchUrl='https://www.bing.com/search?q='.rawurlencode($query);
  $items=parse_public_search(http_get($searchUrl),$num);
  foreach($items as $item){
    $page=clean_text(http_get($item['url']));
    $evidence=substr($page,0,5000);
    [$email,$phone,$whatsapp]=extract_contacts($page);
    $results[]=['company'=>$item['title'],'website'=>$item['url'],'source'=>$item['url'],'evidence'=>$evidence,'description'=>substr($page,0,1000),'email'=>$email,'phone'=>$phone,'whatsapp'=>$whatsapp,'status'=>'new','record_type'=>'discovered','source_date'=>date('c')];
  }
}
echo json_encode(['ok'=>true,'provider'=>$apiKey?'exa':'public-web','query'=>$query,'count'=>count($results),'results'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
