<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$query = trim((string)($input['query'] ?? ''));
$num = max(1, min(20, (int)($input['numResults'] ?? 10)));
if ($query === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'query required']); exit; }

function http_get(string $url): string {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_MAXREDIRS=>3,
    CURLOPT_CONNECTTIMEOUT=>8,
    CURLOPT_TIMEOUT=>15,
    CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; AnCar Leads/2.0; +https://ancarrd.site/)'
  ]);
  $body = curl_exec($ch);
  curl_close($ch);
  return is_string($body) ? $body : '';
}
function clean_text(string $html): string {
  $html = preg_replace('#<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>#is', ' ', $html) ?? $html;
  return trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
}
function add_public_result(array &$out, string $url, string $title, int $limit, string $snippet=''): void {
  $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5);
  if (stripos($url, 'uddg=') !== false) {
    $p = parse_url($url, PHP_URL_QUERY);
    parse_str((string)$p, $q);
    $url = (string)($q['uddg'] ?? $url);
  }
  if (!preg_match('#^https?://#i', $url)) return;
  $host = strtolower((string)parse_url($url, PHP_URL_HOST));
  if ($host === '' || preg_match('#(^|\.)duckduckgo\.com$|(^|\.)bing\.com$|(^|\.)microsoft\.com$#i', $host)) return;
  $key = strtolower(rtrim($url, '/'));
  foreach ($out as $existing) if (strtolower(rtrim((string)$existing['url'], '/')) === $key) return;
  $out[] = ['title'=>clean_text($title) ?: $url, 'url'=>$url, 'search_snippet'=>clean_text($snippet)];
  if (count($out) > $limit) array_pop($out);
}
function parse_public_search(string $html, int $limit): array {
  $out = [];
  if ($html === '') return $out;
  if (preg_match_all('#<a[^>]*class=["\'][^"\']*result__a[^"\']*["\'][^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER)) {
    foreach ($m as $row) {
      $snippet='';
      $start=stripos($html,$row[0]);
      if ($start!==false) $snippet=substr($html,$start,2500);
      add_public_result($out, $row[1], $row[2], $limit, $snippet);
    }
  }
  if (count($out) < $limit && preg_match_all('#<li[^>]*class=["\'][^"\']*b_algo[^"\']*["\'][\s\S]*?</li>#i', $html, $blocks)) {
    foreach ($blocks[0] as $block) {
      if (preg_match('#<h2[^>]*>\s*<a[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $block, $a)) {
        add_public_result($out, $a[1], $a[2], $limit, $block);
      }
      if (count($out) >= $limit) break;
    }
  }
  return array_slice($out, 0, $limit);
}
function extract_contacts(string $text): array {
  $email = ''; $phone = ''; $whatsapp = '';
  if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m)) $email = $m[0];
  if (preg_match('/\+?[0-9][0-9\s().-]{7,}[0-9]/', $text, $m)) $phone = trim($m[0]);
  if (preg_match('/(?:whatsapp(?:\s*(?:business|me))?[^+0-9]{0,30})(\+?[0-9][0-9\s().-]{7,}[0-9])/i', $text, $m)) $whatsapp = trim($m[1]);
  return [$email, $phone, $whatsapp];
}

$apiKey = getenv('EXA_API_KEY') ?: ($_SERVER['EXA_API_KEY'] ?? '');
$results = [];
$provider = 'none';
$attempts = [];
if ($apiKey !== '') {
  $attempts[] = 'exa';
  $payload = json_encode(['query'=>$query,'type'=>'auto','numResults'=>$num,'contents'=>['highlights'=>['maxCharacters'=>2500],'summary'=>['query'=>$query,'maxCharacters'=>1200]]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $ch = curl_init('https://api.exa.ai/search');
  curl_setopt_array($ch, [CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey],CURLOPT_POSTFIELDS=>$payload]);
  $response = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  $data = json_decode(is_string($response) ? $response : '{}', true);
  if ($code >= 200 && $code < 300) {
    foreach (($data['results'] ?? []) as $x) {
      $h = $x['highlights'] ?? []; $e = is_array($h) ? implode(' ', $h) : '';
      [$email,$phone,$whatsapp] = extract_contacts($e.' '.($x['summary'] ?? ''));
      $results[] = ['company'=>$x['title'] ?? '','website'=>$x['url'] ?? '','source'=>$x['url'] ?? '','evidence'=>$e,'description'=>$x['summary'] ?? '','email'=>$email,'phone'=>$phone,'whatsapp'=>$whatsapp,'status'=>'new','record_type'=>'discovered','source_date'=>date('c')];
    }
    if ($results) $provider = 'exa';
  }
}
if (!$results) {
  $attempts[] = 'duckduckgo';
  $items = parse_public_search(http_get('https://html.duckduckgo.com/html/?q='.rawurlencode($query)), $num);
  if (!$items) {
    $attempts[] = 'bing';
    $items = parse_public_search(http_get('https://www.bing.com/search?q='.rawurlencode($query)), $num);
  }
  if ($items) $provider = 'public-web';
  foreach ($items as $item) {
    $page = clean_text(http_get($item['url']));
    $pageEvidence = substr($page, 0, 5000);
    $searchEvidence = (string)($item['search_snippet'] ?? '');
    $evidence = trim($searchEvidence.' '.$pageEvidence);
    [$email,$phone,$whatsapp] = extract_contacts($evidence);
    $results[] = ['company'=>$item['title'],'website'=>$item['url'],'source'=>$item['url'],'evidence'=>$evidence ?: 'Resultado público encontrado en el buscador; contenido de la página no pudo recuperarse desde el servidor.','description'=>substr($page,0,1000) ?: ($searchEvidence ?: $item['title']),'email'=>$email,'phone'=>$phone,'whatsapp'=>$whatsapp,'status'=>'new','record_type'=>'discovered','source_date'=>date('c')];
  }
}
$ok = count($results) > 0;
http_response_code($ok ? 200 : 502);
echo json_encode(['ok'=>$ok,'provider'=>$provider,'attempts'=>$attempts,'query'=>$query,'count'=>count($results),'results'=>$results,'error'=>$ok?'':'No se obtuvieron resultados desde los proveedores configurados.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
