<?php
declare(strict_types=1);

// Buffer the response so redirects remain safe even if a legacy include emits output first.
if (ob_get_level() === 0) { ob_start(); }
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(["lifetime"=>0,"path"=>"/","secure"=>!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!=="off","httponly"=>true,"samesite"=>"Lax"]);session_start();}
function e(?string $v): string{return htmlspecialchars($v??"",ENT_QUOTES,"UTF-8");}
function url(string $path=""): string{
    if (preg_match("#^https?://#i", $path)) return $path;
    $path = preg_replace("#^(?:\.\./|\./)+#", "", $path) ?? $path;
    return rtrim(BASE_URL,"/")."/".ltrim($path,"/");
}
function redirect(string $path): never{
    header("Location: ".url($path));
    exit;
}
function asset_url(string $path): string{
    $asset = url($path);
    $relative = ltrim(preg_replace("#^(?:\.\./|\./)+#", "", $path) ?? $path, "/");
    $file = dirname(__DIR__) . "/" . $relative;
    if (is_file($file)) {
        $version = (string) @filemtime($file);
        if ($version !== "") {
            $asset .= (str_contains($asset, "?") ? "&" : "?") . "v=" . rawurlencode($version);
        }
    }
    return $asset;
}
function flash(string $key,?string $value=null):?string{if($value!==null){$_SESSION["flash"][$key]=$value;return $value;} $v=$_SESSION["flash"][$key]??null;unset($_SESSION["flash"][$key]);return $v;}
function csrf_token(): string{if(empty($_SESSION["csrf_token"]))$_SESSION["csrf_token"]=bin2hex(random_bytes(32));return $_SESSION["csrf_token"]; }
function verify_csrf(?string $token):void{if(!$token||!hash_equals($_SESSION["csrf_token"]??"",$token)){http_response_code(419);exit("Invalid CSRF token.");}}
function current_user():?array{return $_SESSION["user"]??null;}
function login_user(array $u):void{session_regenerate_id(true);$_SESSION["user"]= ["user_id"=>(int)$u["user_id"],"role"=>$u["role"],"name"=>$u["name"],"email"=>$u["email"]];$_SESSION["last_activity"]=time();}
function logout_user():void{
    $_SESSION=[];
    if(ini_get("session.use_cookies")){
        $p=session_get_cookie_params();
        setcookie(session_name(),"",time()-42000,$p["path"],$p["domain"]??"",$p["secure"],$p["httponly"]);
    }
    session_destroy();
}
function require_login():void{
    if(!current_user())redirect("login.php");
    if(!empty($_SESSION["last_activity"])&&time()-(int)$_SESSION["last_activity"]>SESSION_TIMEOUT){logout_user();redirect("login.php?timeout=1");}
    $_SESSION["last_activity"]=time();
    $s=db()->prepare("SELECT user_id,role,name,email,status FROM users WHERE user_id=? LIMIT 1");
    $s->execute([current_user()["user_id"]]);
    $u=$s->fetch();
    if(!$u||$u["status"]!=="active"){logout_user();redirect("login.php?disabled=1");}
    $_SESSION["user"]["name"]=$u["name"];
    $_SESSION["user"]["email"]=$u["email"];
    $_SESSION["user"]["role"]=$u["role"];
    $p=db()->prepare("SELECT currency_code FROM user_preferences WHERE user_id=? LIMIT 1");
    $p->execute([(int)$u["user_id"]]);
    $currency=(string)($p->fetchColumn()?:DEFAULT_CURRENCY);
    $_SESSION["user"]["currency_code"]=isset(SUPPORTED_CURRENCIES[$currency])?$currency:DEFAULT_CURRENCY;
}

function require_student():void{require_login();if(current_user()["role"]!=="student")redirect("admin/dashboard.php");}
function require_admin():void{require_login();if(current_user()["role"]!=="admin")redirect("student/dashboard.php");}
function currency_symbol(?string $code=null):string{
    $code=$code?:($_SESSION["user"]["currency_code"]??DEFAULT_CURRENCY);
    return SUPPORTED_CURRENCIES[$code]["symbol"]??SUPPORTED_CURRENCIES[DEFAULT_CURRENCY]["symbol"];
}
function currency_options():array{return SUPPORTED_CURRENCIES;}
function current_currency_code():string{
    $code=(string)($_SESSION["user"]["currency_code"]??DEFAULT_CURRENCY);
    return isset(SUPPORTED_CURRENCIES[$code])?$code:DEFAULT_CURRENCY;
}
function money(float|int|string $n,?string $code=null):string{
    $code=$code?:current_currency_code();
    $symbol=currency_symbol($code);
    return $symbol.number_format((float)$n,2);
}

function valid_date(string $d):bool{$x=DateTime::createFromFormat("Y-m-d",$d);return $x&&$x->format("Y-m-d")===$d;}
function month_start(?string $d=null):string{return date("Y-m-01",strtotime($d?:date("Y-m-d")));}
function add_activity(int $uid,string $action,string $entity,?int $id=null,?array $meta=null):void{$s=db()->prepare("INSERT INTO activity_logs(user_id,action_type,entity_type,entity_id,metadata) VALUES(?,?,?,?,?)");$s->execute([$uid,$action,$entity,$id,$meta?json_encode($meta,JSON_UNESCAPED_UNICODE):null]);}
function user_categories(int $uid,string $type):array{$s=db()->prepare("SELECT category_id,name,type,is_default FROM categories WHERE type=? AND is_active=1 AND (is_default=1 OR user_id=?) ORDER BY is_default DESC,name");$s->execute([$type,$uid]);return $s->fetchAll();}
function find_category(int $cid,int $uid,string $type):?array{$s=db()->prepare("SELECT * FROM categories WHERE category_id=? AND type=? AND is_active=1 AND (is_default=1 OR user_id=?) LIMIT 1");$s->execute([$cid,$type,$uid]);return $s->fetch()?:null;}
function ensure_budget_alerts(int $uid):void{ $s=db()->prepare("SELECT b.budget_id,b.limit_amount,b.alert_threshold_percent,c.name,COALESCE(SUM(CASE WHEN t.status='active' THEN t.amount ELSE 0 END),0) spent FROM budgets b JOIN categories c ON c.category_id=b.category_id LEFT JOIN transactions t ON t.user_id=b.user_id AND t.category_id=b.category_id AND t.type='expense' AND t.transaction_date>=b.month_start AND t.transaction_date<DATE_ADD(b.month_start,INTERVAL 1 MONTH) WHERE b.user_id=? AND b.month_start=? AND b.is_active=1 GROUP BY b.budget_id,b.limit_amount,b.alert_threshold_percent,c.name");$s->execute([$uid,month_start()]);foreach($s->fetchAll() as $b){if((float)$b['limit_amount']<=0)continue;$pct=((float)$b['spent']/(float)$b['limit_amount'])*100;if($pct<(float)$b['alert_threshold_percent'])continue;$type=$pct>=100?'budget_exceeded':'budget_near_limit';$c=db()->prepare("SELECT notification_id FROM notifications WHERE user_id=? AND notification_type=? AND related_budget_id=? AND created_at>=DATE_FORMAT(CURRENT_TIMESTAMP,'%Y-%m-01') LIMIT 1");$c->execute([$uid,$type,$b['budget_id']]);if(!$c->fetch()){db()->prepare("INSERT INTO notifications(user_id,notification_type,title,message,related_budget_id) VALUES(?,?,?,?,?)")->execute([$uid,$type,$pct>=100?'Budget exceeded':'Budget approaching limit',sprintf("%s is at %.0f%% of its budget (%s spent of %s).",$b['name'],$pct,money($b['spent']),money($b['limit_amount'])),$b['budget_id']]);}}}
