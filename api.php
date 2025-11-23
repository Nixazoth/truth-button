<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['action'] ?? null;
// simple data folder
$dataDir = __DIR__ . '/../data';
if(!is_dir($dataDir)) mkdir($dataDir,0755,true);

function readJson($f){ if(!file_exists($f)) return []; $c = @file_get_contents($f); return json_decode($c,true) ?: []; }
function writeJson($f,$d){ file_put_contents($f,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }

$groupsF = $dataDir . '/groups.json';\$questionsF = $dataDir . '/questions.json';\$answersF = $dataDir . '/answers.json';\$placesF = $dataDir . '/places.json';

// ensure places mock
if(!file_exists($placesF)){
  $places = [
    ['id'=>'p1','name'=>'Café Cozy','coords'=>'48.85,2.35','tags'=>['cozy','cafe']],
    ['id'=>'p2','name'=>'La Cantine Rapide','coords'=>'48.86,2.34','tags'=>['cheap','fastfood']],
    ['id'=>'p3','name'=>'Le Spot Chill','coords'=>'48.84,2.36','tags'=>['hangout','music']]
  ]; writeJson($placesF,$places);
}

$groups = readJson($groupsF);
$questions = readJson($questionsF);
$answers = readJson($answersF);
$places = readJson($placesF);

// helpers
function slugCode($n){ return substr(strtoupper(bin2hex(random_bytes(2))),0,4); }

if($action=='create_group'){
  $name = trim($body['name'] ?? '');
  if(!$name) exit(json_encode(['success'=>false,'error'=>'name required']));
  $id = 'g'.time().rand(10,99);
  $code = slugCode($name);
  $group = ['id'=>$id,'name'=>$name,'code'=>$code,'owner'=>'anon','createdAt'=>time()];
  $groups[] = $group;
  writeJson($groupsF,$groups);
  exit(json_encode(['success'=>true,'group'=>$group]));
}

if($action=='join_group'){
  $code = strtoupper(trim($body['code'] ?? ''));
  foreach($groups as $g) if($g['code']==$code) exit(json_encode(['success'=>true,'group'=>$g]));
  exit(json_encode(['success'=>false,'error'=>'not found']));
}

if($action=='create_question'){
  $groupId = $body['groupId'] ?? null; $q = trim($body['question'] ?? '');
  if(!$groupId || !$q) exit(json_encode(['success'=>false,'error'=>'missing']));
  $id='q'.time().rand(10,99);
  $question=['id'=>$id,'groupId'=>$groupId,'question'=>$q,'createdAt'=>time()];
  $questions[] = $question; writeJson($questionsF,$questions);
  exit(json_encode(['success'=>true,'question'=>$question]));
}

if($action=='get_question'){
  $groupId = $body['groupId'] ?? null;
  if(!$groupId) exit(json_encode(['success'=>false]));
  // return last question for group
  $last = null; foreach($questions as $q) if($q['groupId']==$groupId) $last=$q;
  if(!$last) exit(json_encode(['success'=>false]));
  exit(json_encode(['success'=>true,'question'=>$last]));
}

if($action=='answer_question'){
  $groupId = $body['groupId'] ?? null; $emoji = $body['emoji'] ?? null; $word = $body['word'] ?? null;
  if(!$groupId) exit(json_encode(['success'=>false,'error'=>'no group']));
  // find last q
  $last = null; foreach($questions as $q) if($q['groupId']==$groupId) $last=$q;
  if(!$last) exit(json_encode(['success'=>false,'error'=>'no question']);
  $a = ['id'=>'a'.time().rand(10,99),'questionId'=>$last['id'],'groupId'=>$groupId,'emoji'=>$emoji,'word'=>$word,'createdAt'=>time()];
  $answers[] = $a; writeJson($answersF,$answers);
  exit(json_encode(['success'=>true]));
}

if($action=='get_pulse'){
  $groupId = $body['groupId'] ?? null; if(!$groupId) exit(json_encode(['success'=>false]));
  // aggregate last 200 answers for group's last question
  $last = null; foreach($questions as $q) if($q['groupId']==$groupId) $last=$q;
  $agg = ['mood'=>'—','count'=>0]; $moods = [];
  if($last){ foreach($answers as $a) if($a['questionId']==$last['id']){ $moods[$a['emoji'] ?: $a['word']] = ($moods[$a['emoji'] ?: $a['word']] ?? 0) + 1; $agg['count']++; }}
  if($agg['count']>0){ arsort($moods); $topKey = array_key_first($moods); $agg['mood']=$topKey; }
  // simple place match: if mood contains 'cozy' or emoji thumbs -> cozy
  $selectedPlaces = [];
  foreach($places as $p){ $selectedPlaces[]=$p; }
  exit(json_encode(['success'=>true,'pulse'=>$agg,'places'=>$selectedPlaces]));
}

// fallback
exit(json_encode(['success'=>false,'error'=>'unknown action']));

?>

---- end of files ----
