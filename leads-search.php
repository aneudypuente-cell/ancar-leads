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
function http_get(string $url): string { $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,CURLOPT_ENCODING=>'',CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36']); $body=curl_exec($ch); curl_close($ch); return is_string($body)?$body:''; }
function clean_text(string $html): string { $html=preg_replace('/<script\b[^>]*>.*?<\/script>|<style\b[^>]*>.*?<\/style>/is',' ',$html) ?? $html; return trim(preg_replace('/\s+/',' ',strip_tags($html)) ?? strip_tags($html)); }
function parse_search(string $html,int $limit): array {
  $out=[]; if($html==='') return $out;
  libxml_use_internal_errors(true); $dom=new DOMDocument();
  if(@$dom->loadHTML($html)){ $xp=new DOMXPath($dom); $nodes=$xp->query('//li[contains(concat(" ",normalize-space(@class)," ")," b_algo ")]//h2/a | //h2/a | //div[contains(@class,"result")]//h2/a'); if($nodes) foreach($nodes as $a){$url=html_entity_decode((string)$a->getAttribute('href'),ENT_QUOTES);$title=trim(preg_replace('/\s+/',' ',$a->textContent));if(preg_match('/^https?:\/\//i',$url)&&$title&&!preg_match('/bing\.com|microsoft\.com/i',$url))$out[]=['title'=>$title,'url'=>$url];if(count($out)>=$limit)break;} }
  if(count($out)<$limit && preg_match_all('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>(.*?)<\/a>/is',$html,$m,PREG_SET_ORDER)){ foreach($m as $row){$url=html_entity_decode($row[1],ENT_QUOTES);$title=trim(strip_tags($row[2]));if(!$title||preg_match('/bing\.com|microsoft\.com/i',$url))continue;$out[]=['title'=>$title,'url'=>$url];if(count($out)>=$limit)break;} }
  $seen=[]; return array_values(array_filter($out,function($x)use(&$seen){$k=strtolower($x['url']);if(isset($seen[$k]))return false;$seen[$k]=1;return true;}));
}
function extract_contacts(string $text): array { $email='';$phone='';$whatsapp='';if(preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',$text,$m))$email=$m[0];if(preg_match('/(?:\+?\d[\d\s().-]{7,}\d)/',$text,$m))$phone=trim($m[0]);if(preg_match('/(?:whatsapp|wa\.me)[^\d+]{0,40}(\+?\d[\d\s().-]{7,}\d)/i',$text,$m))$whatsapp=trim($m[1]);return[$email,$phone,$whatsapp]; }
function result_row(string $title,string $url,string $text,string $source): array { [$email,$phone,$whatsapp]=extract_contacts($text); return ['company'=>$title,'website'=>$url,'source'=>$url,'evidence'=>substr($text,0,1800),'description'=>substr($text,0,700),'email'=>$email,'phone'=>$phone,'whatsapp'=>$whatsapp,'status'=>'new','record_type'=>'discovered','source_type'=>$source,'source_date'=>date('c')]; }
$results=[];$provider='public-web';$apiKey=getenv('EXA_API_KEY') ?: ($_SERVER['EXA_API_KEY'] ?? '');
if($apiKey){$payload=json_encode(['query'=>$query,'type'=>'auto','numResults'=>$num,'contents'=>['highlights'=>['maxCharacters'=>2500],'summary'=>['query'=>$query,'maxCharacters'=>1200]]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$ch=curl_init('https://api.exa.ai/search');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey],CURLOPT_POSTFIELDS=>$payload]);$response=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$data=json_decode(is_string($response)?$response:'{}',true);if($code>=200&&$code<300){foreach(($data['results']??[]) as $x){$h=$x['highlights']??[];$e=is_array($h)?implode(' ',$h):'';$results[]=result_row((string)($x['title']??''),(string)($x['url']??''),$e.' '.(string)($x['summary']??''),'exa');}$provider='exa';}}
if(!$results){$items=parse_search(http_get('https://www.bing.com/search?q='.rawurlencode($query)),$num);foreach($items as $item){$page=clean_text(http_get($item['url']));$results[]=result_row($item['title'],$item['url'],$page,'bing');}}
$seen=[];$results=array_values(array_filter($results,function($x)use(&$seen){$k=strtolower($x['website']??$x['company']??'');if(!$k||isset($seen[$k]))return false;$seen[$k]=1;return true;}));
echo json_encode(['ok'=>true,'provider'=>$provider,'query'=>$query,'count'=>count($results),'results'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
