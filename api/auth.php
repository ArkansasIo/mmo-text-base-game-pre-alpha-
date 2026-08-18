<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../base/AdminAuth.class.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function auth_json(array $body, int $status=200): never { http_response_code($status); echo json_encode($body, JSON_UNESCAPED_SLASHES); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') auth_json(['ok'=>false,'error'=>'POST is required'],405);
$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=$_POST;
$mode=(string)($input['mode']??'player');$action=(string)($input['action']??'login');$s=new Game();
if($action==='logout'){if($mode==='admin'){$a=new AdminAuth($s->db_link);$a->logout();}else{User::logOut();}auth_json(['ok'=>true,'mode'=>$mode,'action'=>'logout']);}
if($mode==='admin'){$a=new AdminAuth($s->db_link);$username=trim((string)($input['username']??''));$password=(string)($input['password']??'');if($username===''||$password==='')auth_json(['ok'=>false,'error'=>'Credentials were not accepted'],401);if(!$a->login($username,$password))auth_json(['ok'=>false,'error'=>'Credentials were not accepted'],401);auth_json(['ok'=>true,'mode'=>'admin','user'=>['id'=>(int)$a->admin['admin_id'],'username'=>$a->admin['username'],'role'=>$a->admin['role']]]);}
$username=trim((string)($input['username']??$input['email']??''));$password=(string)($input['password']??'');if($username===''||$password==='')auth_json(['ok'=>false,'error'=>'Credentials were not accepted'],401);$u=new User($username,$password);if(!$u->loggedIn)auth_json(['ok'=>false,'error'=>'Credentials were not accepted'],401);session_regenerate_id(true);auth_json(['ok'=>true,'mode'=>'player','user'=>['id'=>(int)$u->userid,'username'=>$u->userName,'access'=>(int)$u->access,'race'=>(int)$u->raceID]]);
