<?php
/**
 * 不凡收款监控看板
**/
include("../includes/common.php");
$title='收款监控';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$saved = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save_config'){
	if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']){
		sysmsg('CSRF 校验失败');
	}
	$server_url_new = trim($_POST['notifyledger_server_url']);
	$secret_new = trim($_POST['notifyledger_internal_secret']);
	if($server_url_new && !preg_match('/^https?:\/\//i', $server_url_new)){
		sysmsg('Go 服务地址必须以 http:// 或 https:// 开头');
	}
	saveSetting('notifyledger_server_url', rtrim($server_url_new, '/'));
	if($secret_new !== '') saveSetting('notifyledger_internal_secret', $secret_new);
	$CACHE->clear();
	$conf['notifyledger_server_url'] = rtrim($server_url_new, '/');
	if($secret_new !== '') $conf['notifyledger_internal_secret'] = $secret_new;
	$saved = true;
}

function nl_tbl($name){
	return '`'.DBQZ.'_'.$name.'`';
}
function nl_table_exists($name){
	global $DB;
	$table = DBQZ.'_'.$name;
	return $DB->getColumn("SHOW TABLES LIKE :table", [':table'=>$table]) ? true : false;
}
function nl_safe_count($sql, $params=[]){
	global $DB;
	$v = $DB->getColumn($sql, $params);
	return $v ? $v : 0;
}
function nl_weekday($ts){
	$map = ['周日','周一','周二','周三','周四','周五','周六'];
	return $map[intval(date('w', $ts))];
}
function nl_apply_percent(&$items){
	$max = 0;
	foreach($items as $row){
		if($row['amount'] > $max) $max = $row['amount'];
	}
	foreach($items as &$row){
		$row['avg'] = intval($row['count']) > 0 ? round(floatval($row['amount']) / intval($row['count']), 2) : 0;
		$row['percent'] = $max > 0 && $row['amount'] > 0 ? max(8, intval(round($row['amount'] / $max * 100))) : 0;
	}
}
function nl_summary_by_day($days=14){
	global $DB;
	$items = [];
	$index = [];
	$start = strtotime(date('Y-m-d 00:00:00', strtotime('-'.($days-1).' day')));
	for($i=0;$i<$days;$i++){
		$ts = strtotime('+'.$i.' day', $start);
		$key = date('Y-m-d', $ts);
		$index[$key] = $i;
		$items[] = ['key'=>$key, 'label'=>date('m/d', $ts), 'sub'=>nl_weekday($ts), 'count'=>0, 'amount'=>0.0, 'avg'=>0.0, 'percent'=>0];
	}
	$rows = $DB->getAll("SELECT DATE_FORMAT(paid_at,'%Y-%m-%d') k, COUNT(*) c, COALESCE(SUM(amount),0) a FROM ".nl_tbl('nl_collect_session')." WHERE status='paid' AND paid_at>=:start AND paid_at<DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY DATE_FORMAT(paid_at,'%Y-%m-%d')", [':start'=>date('Y-m-d H:i:s', $start)]);
	if($rows){
		foreach($rows as $row){
			if(isset($index[$row['k']])){
				$pos = $index[$row['k']];
				$items[$pos]['count'] = intval($row['c']);
				$items[$pos]['amount'] = round(floatval($row['a']), 2);
			}
		}
	}
	nl_apply_percent($items);
	return $items;
}
function nl_summary_by_month($months=12){
	global $DB;
	$items = [];
	$index = [];
	$start = strtotime(date('Y-m-01 00:00:00', strtotime('-'.($months-1).' month')));
	for($i=0;$i<$months;$i++){
		$ts = strtotime('+'.$i.' month', $start);
		$key = date('Y-m', $ts);
		$index[$key] = $i;
		$items[] = ['key'=>$key, 'label'=>date('m月', $ts), 'sub'=>date('Y', $ts), 'count'=>0, 'amount'=>0.0, 'avg'=>0.0, 'percent'=>0];
	}
	$rows = $DB->getAll("SELECT DATE_FORMAT(paid_at,'%Y-%m') k, COUNT(*) c, COALESCE(SUM(amount),0) a FROM ".nl_tbl('nl_collect_session')." WHERE status='paid' AND paid_at>=:start GROUP BY DATE_FORMAT(paid_at,'%Y-%m')", [':start'=>date('Y-m-d H:i:s', $start)]);
	if($rows){
		foreach($rows as $row){
			if(isset($index[$row['k']])){
				$pos = $index[$row['k']];
				$items[$pos]['count'] = intval($row['c']);
				$items[$pos]['amount'] = round(floatval($row['a']), 2);
			}
		}
	}
	nl_apply_percent($items);
	return $items;
}
function nl_admin_link($view){
	return './notifyledger.php?view='.$view;
}
function nl_normalize_pick_strategy($strategy){
	$strategy = strtolower(trim($strategy));
	if(in_array($strategy, ['random','round_robin','least_orders','least_amount'], true)) return $strategy;
	return 'least_amount';
}
function nl_pick_strategy_label($strategy){
	$strategy = nl_normalize_pick_strategy($strategy);
	$labels = [
		'least_amount' => '金额最少优先',
		'least_orders' => '订单数最少优先',
		'round_robin' => '轮询分配',
		'random' => '随机分配',
	];
	return $labels[$strategy];
}
function nl_config_value($key, $default=''){
	global $DB;
	if(!nl_table_exists('nl_config')) return $default;
	$v = $DB->getColumn("SELECT v FROM ".nl_tbl('nl_config')." WHERE k=:k LIMIT 1", [':k'=>$key]);
	return $v ? $v : $default;
}
function nl_api_post($base_url, $path, $payload=[], $secret=''){
	if(empty($base_url)) return ['ok'=>false, 'error'=>'Go 服务地址未配置'];
	if(empty($secret)) return ['ok'=>false, 'error'=>'内部密钥未配置'];
	if(!function_exists('curl_init')) return ['ok'=>false, 'error'=>'PHP curl 扩展不可用'];
	$body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$timestamp = strval(time());
	try{
		$nonce = bin2hex(random_bytes(8));
	}catch(Exception $e){
		$nonce = md5(uniqid('', true));
	}
	$sign = hash_hmac('sha256', $timestamp."\n".$nonce."\n".$body, $secret);
	$ch = curl_init(rtrim($base_url, '/').$path);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_TIMEOUT, 8);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'X-Timestamp: '.$timestamp,
		'X-Nonce: '.$nonce,
		'X-Signature: '.$sign,
	]);
	$response = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	curl_close($ch);
	if($response === false || $response === ''){
		return ['ok'=>false, 'error'=>$error ? $error : 'Go 服务无响应'];
	}
	$data = json_decode($response, true);
	if(!is_array($data)){
		return ['ok'=>false, 'error'=>'Go 服务返回非 JSON'];
	}
	if($http_code >= 400 || intval($data['code'] ?? -1) !== 0){
		return ['ok'=>false, 'error'=>$data['msg'] ?? ('HTTP '.$http_code), 'data'=>$data];
	}
	return ['ok'=>true, 'data'=>$data];
}
function nl_stat($stats, $key, $default=0){
	return isset($stats[$key]) ? $stats[$key] : $default;
}

$view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'overview';
if(!in_array($view, ['overview','sessions','events','config'], true)) $view = 'overview';
$local_ready = nl_table_exists('nl_device') && nl_table_exists('nl_account') && nl_table_exists('nl_notification_event') && nl_table_exists('nl_collect_session');
$server_url = !empty($conf['notifyledger_server_url']) ? $conf['notifyledger_server_url'] : 'http://127.0.0.1:8098';
$internal_secret = !empty($conf['notifyledger_internal_secret']) ? $conf['notifyledger_internal_secret'] : '';
$api_result = nl_api_post($server_url, '/internal/stats', ['days'=>14, 'months'=>12, 'limit'=>20], $internal_secret);
$api_ready = !empty($api_result['ok']);
$api_error = $api_ready ? '' : ($api_result['error'] ?? '接口未连通');
$ready = $api_ready || $local_ready;
$pick_strategy = nl_normalize_pick_strategy($local_ready ? nl_config_value('account_pick_strategy', 'least_amount') : 'least_amount');
$pick_strategy_label = nl_pick_strategy_label($pick_strategy);

$device_count = $account_count = $waiting_count = $waiting_amount = $paid_count = 0;
$today_events = $ambiguous_events = 0;
$today_match_rate = '0%';
$today_amount = $today_orders = $yesterday_amount = $yesterday_orders = 0;
$week_amount = $week_orders = $last30_amount = $last30_orders = 0;
$month_amount = $month_orders = $avg_amount = $year_amount = $year_orders = 0;
$total_amount = $total_orders = 0;
$last_paid_at = '-';
$events = [];
$sessions = [];
$daily_summary = [];
$monthly_summary = [];

if($api_ready){
	$api_data = $api_result['data'];
	$api_stats = isset($api_data['stats']) && is_array($api_data['stats']) ? $api_data['stats'] : [];
	$strategy_data = isset($api_data['pick_strategy']) && is_array($api_data['pick_strategy']) ? $api_data['pick_strategy'] : [];
	$pick_strategy = nl_normalize_pick_strategy($strategy_data['value'] ?? $pick_strategy);
	$pick_strategy_label = $strategy_data['label'] ?? nl_pick_strategy_label($pick_strategy);
	$device_count = nl_stat($api_stats, 'devices');
	$account_count = nl_stat($api_stats, 'accounts');
	$waiting_count = nl_stat($api_stats, 'waiting');
	$waiting_amount = nl_stat($api_stats, 'waiting_amount');
	$paid_count = nl_stat($api_stats, 'paid');
	$today_events = nl_stat($api_stats, 'events');
	$ambiguous_events = nl_stat($api_stats, 'event_ambiguous');
	$today_match_rate = nl_stat($api_stats, 'today_match_rate', '0%');
	$today_amount = nl_stat($api_stats, 'amount');
	$today_orders = nl_stat($api_stats, 'today_orders');
	$yesterday_amount = nl_stat($api_stats, 'yesterday_amount');
	$yesterday_orders = nl_stat($api_stats, 'yesterday_orders');
	$week_amount = nl_stat($api_stats, 'week_amount');
	$week_orders = nl_stat($api_stats, 'week_orders');
	$last30_amount = nl_stat($api_stats, 'last30_amount');
	$last30_orders = nl_stat($api_stats, 'last30_orders');
	$month_amount = nl_stat($api_stats, 'month_amount');
	$month_orders = nl_stat($api_stats, 'month_orders');
	$avg_amount = nl_stat($api_stats, 'avg_amount');
	$year_amount = nl_stat($api_stats, 'year_amount');
	$year_orders = nl_stat($api_stats, 'year_orders');
	$total_amount = nl_stat($api_stats, 'total_amount');
	$total_orders = nl_stat($api_stats, 'total_orders');
	$last_paid_at = nl_stat($api_stats, 'last_paid_at', '-');
	$events = isset($api_data['recent_events']) && is_array($api_data['recent_events']) ? $api_data['recent_events'] : [];
	$sessions = isset($api_data['recent_sessions']) && is_array($api_data['recent_sessions']) ? $api_data['recent_sessions'] : [];
	$daily_summary = isset($api_data['daily_summary']) && is_array($api_data['daily_summary']) ? $api_data['daily_summary'] : [];
	$monthly_summary = isset($api_data['monthly_summary']) && is_array($api_data['monthly_summary']) ? $api_data['monthly_summary'] : [];
}elseif($local_ready){
	$session_table = nl_tbl('nl_collect_session');
	$event_table = nl_tbl('nl_notification_event');
	$device_count = nl_safe_count("SELECT COUNT(*) FROM ".nl_tbl('nl_device'));
	$account_count = nl_safe_count("SELECT COUNT(*) FROM ".nl_tbl('nl_account'));
	$waiting_count = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='waiting'");
	$waiting_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='waiting'");
	$paid_count = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid'");
	$today_events = nl_safe_count("SELECT COUNT(*) FROM ".$event_table." WHERE DATE(received_at)=CURDATE()");
	$ambiguous_events = nl_safe_count("SELECT COUNT(*) FROM ".$event_table." WHERE match_status='ambiguous'");
	$today_match_rate = nl_safe_count("SELECT COALESCE(CONCAT(ROUND(SUM(CASE WHEN match_status='matched' THEN 1 ELSE 0 END)/NULLIF(COUNT(*),0)*100,1),'%'),'0%') FROM ".$event_table." WHERE DATE(received_at)=CURDATE()");
	$today_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND DATE(paid_at)=CURDATE()");
	$today_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND DATE(paid_at)=CURDATE()");
	$yesterday_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND DATE(paid_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
	$yesterday_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND DATE(paid_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
	$week_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
	$week_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
	$last30_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)");
	$last30_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)");
	$month_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
	$month_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
	$avg_amount = nl_safe_count("SELECT COALESCE(ROUND(AVG(amount),2),0) FROM ".$session_table." WHERE status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
	$year_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid' AND YEAR(paid_at)=YEAR(CURDATE())");
	$year_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid' AND YEAR(paid_at)=YEAR(CURDATE())");
	$total_amount = nl_safe_count("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE status='paid'");
	$total_orders = nl_safe_count("SELECT COUNT(*) FROM ".$session_table." WHERE status='paid'");
	$last_paid_at = nl_safe_count("SELECT COALESCE(DATE_FORMAT(MAX(paid_at),'%m-%d %H:%i'),'-') FROM ".$session_table." WHERE status='paid'");
	$events = $DB->getAll("SELECT event_id,device_no,channel,raw_title,raw_text,parsed_amount,match_status,matched_trade_no,received_at FROM ".$event_table." ORDER BY id DESC LIMIT 20");
	$sessions = $DB->getAll("SELECT epay_trade_no,epay_out_trade_no,uid,channel,amount,status,expire_at,paid_at,created_at FROM ".$session_table." ORDER BY id DESC LIMIT 20");
	$daily_summary = nl_summary_by_day(14);
	$monthly_summary = nl_summary_by_month(12);
}
?>
<div class="notion-page nl-page">
	<div class="nl-layout">
		<aside class="nl-side">
			<div class="nl-brand"><div class="nl-logo">不</div><div><b>不凡收款</b><span>EPay 收款监控</span></div></div>
			<a class="<?php echo $view==='overview'?'active':''?>" href="<?php echo nl_admin_link('overview')?>"><span>01</span>统计总览</a>
			<a class="<?php echo $view==='sessions'?'active':''?>" href="<?php echo nl_admin_link('sessions')?>"><span>02</span>收款会话</a>
			<a class="<?php echo $view==='events'?'active':''?>" href="<?php echo nl_admin_link('events')?>"><span>03</span>通知流水</a>
			<a class="<?php echo $view==='config'?'active':''?>" href="<?php echo nl_admin_link('config')?>"><span>04</span>联动配置</a>
			<div class="nl-side-card"><span>Go 服务地址</span><code><?php echo h($server_url)?></code><span>接口：<?php echo $api_ready ? '已连通' : '未连通'?></span></div>
		</aside>
		<main class="nl-main">
			<div class="nl-topbar">
				<div><div class="notion-kicker">BUFAN LEDGER · <?php echo h($view)?></div><h1>收款监控</h1><div class="notion-subtitle">EPay 内部视角：通过 Go 内部接口看统计、查会话、追通知；账号和补单由独立收款管理端处理。</div></div>
				<div class="nl-actions"><a class="btn btn-default" href="./pay_channel.php">通道配置</a></div>
			</div>

			<?php if($saved){?><div class="alert alert-success">不凡收款配置已保存。</div><?php }?>
			<?php if(!$ready){?>
			<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">不凡收款接口未连通</h3></div><div class="panel-body"><p>EPay 没有通过内部接口连上 Go 收款服务，也没有发现可兼容读取的共享数据库表。</p><p class="text-muted">接口错误：<?php echo h($api_error)?></p><pre>cd ../notify-ledger-server
cp .env.example .env
# 修改 NL_DSN / NL_EPAY_BASE_URL / NL_EPAY_INTERNAL_SECRET
./bufan-ledger</pre><p class="text-muted">当前 Go 服务地址：<code><?php echo h($server_url)?></code></p></div></div>
			<?php }elseif($view === 'overview'){?>
			<section class="nl-section">
				<div class="nl-section-head"><div><div class="notion-kicker">Overview</div><h2>收款总结</h2></div><p>按天看今日/昨日/近 7 日，按月看本月/今年/累计，并提供明细表。</p></div>
				<div class="nl-summary-grid nl-summary-grid-extended">
					<div class="nl-summary-card primary"><small>今日到账</small><b>¥<?php echo number_format((float)$today_amount,2)?></b><span><?php echo intval($today_orders)?> 笔收款</span></div>
					<div class="nl-summary-card"><small>昨日到账</small><b>¥<?php echo number_format((float)$yesterday_amount,2)?></b><span><?php echo intval($yesterday_orders)?> 笔收款</span></div>
					<div class="nl-summary-card"><small>近 7 日到账</small><b>¥<?php echo number_format((float)$week_amount,2)?></b><span><?php echo intval($week_orders)?> 笔收款</span></div>
					<div class="nl-summary-card"><small>本月到账</small><b>¥<?php echo number_format((float)$month_amount,2)?></b><span><?php echo intval($month_orders)?> 笔 · 均 ¥<?php echo number_format((float)$avg_amount,2)?></span></div>
					<div class="nl-summary-card"><small>近 30 日到账</small><b>¥<?php echo number_format((float)$last30_amount,2)?></b><span><?php echo intval($last30_orders)?> 笔滚动统计</span></div>
					<div class="nl-summary-card"><small>今年到账</small><b>¥<?php echo number_format((float)$year_amount,2)?></b><span><?php echo intval($year_orders)?> 笔收款</span></div>
					<div class="nl-summary-card"><small>累计到账</small><b>¥<?php echo number_format((float)$total_amount,2)?></b><span><?php echo intval($total_orders)?> 笔 · 最近 <?php echo h($last_paid_at)?></span></div>
					<div class="nl-summary-card"><small>待处理会话</small><b>¥<?php echo number_format((float)$waiting_amount,2)?></b><span><?php echo intval($waiting_count)?> 笔待通知/确认</span></div>
				</div>
				<div class="nl-chart-grid">
					<div class="panel panel-default nl-chart-panel"><div class="nl-chart-head"><div><div class="notion-kicker">Daily</div><h3>近 14 天收款柱状表</h3></div><span>按到账日期汇总金额与笔数</span></div><div class="nl-bars nl-bars-day"><?php foreach($daily_summary as $row){?><div class="nl-bar-item" title="<?php echo h($row['label'].' '.$row['sub'].'：¥'.number_format($row['amount'],2).' / '.$row['count'].'笔 / 客单¥'.number_format($row['avg'],2))?>"><em>¥<?php echo number_format($row['amount'],2)?></em><div class="nl-bar-track"><div class="nl-bar" style="height:<?php echo intval($row['percent'])?>%"></div></div><span><?php echo h($row['label'])?></span><small><?php echo intval($row['count'])?>笔</small></div><?php }?></div></div>
					<div class="panel panel-default nl-chart-panel"><div class="nl-chart-head"><div><div class="notion-kicker">Monthly</div><h3>近 12 个月收款柱状表</h3></div><span>按月份汇总金额与笔数</span></div><div class="nl-bars nl-bars-month"><?php foreach($monthly_summary as $row){?><div class="nl-bar-item" title="<?php echo h($row['sub'].'年'.$row['label'].'：¥'.number_format($row['amount'],2).' / '.$row['count'].'笔 / 客单¥'.number_format($row['avg'],2))?>"><em>¥<?php echo number_format($row['amount'],2)?></em><div class="nl-bar-track"><div class="nl-bar month" style="height:<?php echo intval($row['percent'])?>%"></div></div><span><?php echo h($row['label'])?></span><small><?php echo intval($row['count'])?>笔</small></div><?php }?></div></div>
				</div>
				<div class="nl-detail-grid">
					<div class="panel panel-default nl-chart-panel nl-mini-table"><div class="nl-chart-head"><div><div class="notion-kicker">Daily Detail</div><h3>每日明细</h3></div></div><table class="table nl-detail-table"><thead><tr><th>日期</th><th>笔数</th><th>金额</th><th>客单</th></tr></thead><tbody><?php foreach($daily_summary as $row){?><tr><td><?php echo h($row['label'])?><br><small><?php echo h($row['sub'])?></small></td><td><?php echo intval($row['count'])?></td><td>¥<?php echo number_format($row['amount'],2)?></td><td>¥<?php echo number_format($row['avg'],2)?></td></tr><?php }?></tbody></table></div>
					<div class="panel panel-default nl-chart-panel nl-mini-table"><div class="nl-chart-head"><div><div class="notion-kicker">Monthly Detail</div><h3>每月明细</h3></div></div><table class="table nl-detail-table"><thead><tr><th>月份</th><th>笔数</th><th>金额</th><th>客单</th></tr></thead><tbody><?php foreach($monthly_summary as $row){?><tr><td><?php echo h($row['sub'])?><br><small><?php echo h($row['label'])?></small></td><td><?php echo intval($row['count'])?></td><td>¥<?php echo number_format($row['amount'],2)?></td><td>¥<?php echo number_format($row['avg'],2)?></td></tr><?php }?></tbody></table></div>
					<div class="nl-ops-grid nl-ops-compact"><div><small>分配策略</small><b><?php echo h($pick_strategy_label)?></b><span><?php echo h($pick_strategy)?> · 在 Go 收款端配置</span></div><div><small>设备</small><b><?php echo $device_count?></b><span>监听手机</span></div><div><small>账号</small><b><?php echo $account_count?></b><span>收款账号</span></div><div><small>待匹配</small><b><?php echo $waiting_count?></b><span>需关注</span></div><div><small>模糊通知</small><b><?php echo $ambiguous_events?></b><span>需人工判断</span></div><div><small>今日匹配率</small><b><?php echo h($today_match_rate)?></b><span><?php echo $today_events?> 条通知</span></div><div><small>已回写</small><b><?php echo $paid_count?></b><span>累计成功</span></div></div>
				</div>
			</section>
			<?php }elseif($view === 'sessions'){?>
			<section class="nl-section"><div class="nl-section-head"><div><div class="notion-kicker">Sessions</div><h2>收款会话</h2></div><p>只看 EPay 下单后创建的收款会话。</p></div><div class="panel panel-default"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>订单</th><th>商户</th><th>渠道</th><th>金额</th><th>状态</th><th>创建 / 完成</th></tr></thead><tbody><?php foreach($sessions as $row){?><tr><td><a href="./order.php?my=search&column=trade_no&value=<?php echo h($row['epay_trade_no'])?>"><?php echo h($row['epay_trade_no'])?></a><br><small><?php echo h($row['epay_out_trade_no'])?></small></td><td><?php echo h($row['uid'])?></td><td><span class="label label-default"><?php echo h($row['channel'])?></span></td><td>¥<?php echo h($row['amount'])?></td><td><?php echo h($row['status'])?></td><td><?php echo h($row['created_at'])?><br><small><?php echo h($row['paid_at'])?></small></td></tr><?php }?></tbody></table></div></div></section>
			<?php }elseif($view === 'events'){?>
			<section class="nl-section"><div class="nl-section-head"><div><div class="notion-kicker">Events</div><h2>通知流水</h2></div><p>模糊匹配或漏单请进入独立收款管理端人工补单。</p></div><div class="panel panel-default"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>时间</th><th>设备</th><th>渠道</th><th>金额</th><th>状态</th><th>订单</th><th>内容</th></tr></thead><tbody><?php foreach($events as $row){?><tr><td><?php echo h($row['received_at'])?></td><td><?php echo h($row['device_no'])?></td><td><span class="label label-default"><?php echo h($row['channel'])?></span></td><td>¥<?php echo h($row['parsed_amount'])?></td><td><?php echo h($row['match_status'])?></td><td><?php echo h($row['matched_trade_no'])?></td><td><?php echo h($row['raw_title'])?><br><small><?php echo h($row['raw_text'])?></small></td></tr><?php }?></tbody></table></div></div></section>
			<?php }else{?>
			<section class="nl-section nl-narrow"><div class="nl-section-head"><div><div class="notion-kicker">Config</div><h2>联动配置</h2></div><p>EPay 只维护 Go 服务地址和内部密钥；账号、设备、收款码和分配策略都在独立收款管理端配置。两者可以分开部署。</p></div><div class="panel panel-default"><div class="panel-body"><form method="post" class="nl-form-grid"><input type="hidden" name="act" value="save_config"><input type="hidden" name="csrf_token" value="<?php echo $admin_csrf_token?>"><div class="form-group"><label>Go 服务地址</label><input class="form-control" name="notifyledger_server_url" value="<?php echo h($server_url)?>" placeholder="http://127.0.0.1:8098"></div><div class="form-group"><label>内部密钥</label><input class="form-control" name="notifyledger_internal_secret" value="" placeholder="留空则不修改"></div><button class="btn btn-primary" type="submit">保存联动配置</button></form><p class="text-muted" style="margin-top:12px">该密钥需与 Go 服务 <code>NL_EPAY_INTERNAL_SECRET</code>、notifyledger 通道密钥保持一致。当前接口状态：<code><?php echo $api_ready ? '已连通' : h($api_error)?></code>。当前收款端分配策略：<code><?php echo h($pick_strategy_label)?></code>。EPay 不提供 Go 后台跳转入口，避免两个后台强绑定。</p></div></div></section>
			<?php }?>
		</main>
	</div>
</div>
