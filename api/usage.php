<?php
require_once __DIR__.'/../_private/config/config.php'; require_once ROOT_PATH.'/_private/lib/security.php'; require_once ROOT_PATH.'/_private/lib/credits.php';
$u=require_auth();
$displayCurrency=wallet_currency_for_user((int)$u['id']);
$days=max(1,min(90,(int)($_GET['days']??30)));
$baseWhere="g.user_id=? AND g.created_at>=DATE_SUB(NOW(),INTERVAL ? DAY)";

$s=$pdo->prepare("SELECT DATE(created_at) day,COUNT(*) requests,SUM(cost) cost,SUM(input_tokens) input_tokens,SUM(output_tokens) output_tokens FROM api_usage WHERE user_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY day");
$s->execute([$u['id'],$days]); $rows=$s->fetchAll();

$tot=$pdo->prepare("SELECT COUNT(*) requests,COALESCE(SUM(cost),0) cost,COALESCE(SUM(input_tokens),0) input_tokens,COALESCE(SUM(output_tokens),0) output_tokens FROM api_usage WHERE user_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL ? DAY)");
$tot->execute([$u['id'],$days]); $totals=$tot->fetch();

$g=$pdo->prepare("SELECT COUNT(*) operations,COALESCE(SUM(g.credits_spent),0) credits,COALESCE(SUM(CASE WHEN g.status='completed' THEN 1 ELSE 0 END),0) completed FROM generations g WHERE $baseWhere");
$g->execute([$u['id'],$days]); $genTotals=$g->fetch();
$genTotals['balance_currency']=$displayCurrency;
$genTotals['cost']=round(fx_convert((float)($genTotals['credits']??0),'USD',$displayCurrency),2);

$m=$pdo->prepare("SELECT COALESCE(am.slug,g.model_id,'unknown') model,COUNT(*) operations,COALESCE(SUM(g.credits_spent),0) credits FROM generations g LEFT JOIN ai_models am ON am.id=g.model_id WHERE $baseWhere GROUP BY model ORDER BY credits DESC,operations DESC LIMIT 12");
$m->execute([$u['id'],$days]); $models=$m->fetchAll();
foreach($models as &$mr){$mr['cost']=round(fx_convert((float)($mr['credits']??0),'USD',$displayCurrency),2);}unset($mr);

$p=$pdo->prepare("SELECT COALESCE(pr.name,'Без проекта') project,COUNT(*) operations,COALESCE(SUM(g.credits_spent),0) credits FROM generations g LEFT JOIN projects pr ON pr.id=g.project_id WHERE $baseWhere GROUP BY project ORDER BY credits DESC,operations DESC LIMIT 12");
$p->execute([$u['id'],$days]); $projects=$p->fetchAll();
foreach($projects as &$prr){$prr['cost']=round(fx_convert((float)($prr['credits']??0),'USD',$displayCurrency),2);}unset($prr);

$recent=$pdo->prepare("SELECT g.id,g.type,g.status,COALESCE(g.credits_spent,0) credits_spent,COALESCE(am.slug,'') model,COALESCE(pr.name,'') project,g.created_at FROM generations g LEFT JOIN ai_models am ON am.id=g.model_id LEFT JOIN projects pr ON pr.id=g.project_id WHERE $baseWhere ORDER BY g.id DESC LIMIT 15");
$recent->execute([$u['id'],$days]);
$recentRows=$recent->fetchAll();
foreach($recentRows as &$rr){$rr['cost']=round(fx_convert((float)($rr['credits_spent']??0),'USD',$displayCurrency),2);}unset($rr);

json_out([
  'days'=>$days,
  'rows'=>$rows,
  'totals'=>$totals,
  'generation_totals'=>$genTotals,
  'models'=>$models,
  'projects'=>$projects,
  'recent'=>$recentRows,'currency'=>$displayCurrency
]);
