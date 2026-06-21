<?php
$is_defend=true;
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='到账看板';
include './head.php';

function user_nl_tbl($name){
	return '`'.DBQZ.'_'.$name.'`';
}
function user_nl_table_exists($name){
	global $DB;
	$table = DBQZ.'_'.$name;
	return $DB->getColumn("SHOW TABLES LIKE :table", [':table'=>$table]) ? true : false;
}
function user_nl_value($sql, $params=[]){
	global $DB;
	$v = $DB->getColumn($sql, $params);
	return $v ? $v : 0;
}
function user_nl_weekday($ts){
	$map = ['周日','周一','周二','周三','周四','周五','周六'];
	return $map[intval(date('w', $ts))];
}
function user_nl_apply_percent(&$items){
	$max = 0;
	foreach($items as $row){ if($row['amount'] > $max) $max = $row['amount']; }
	foreach($items as &$row){
		$row['avg'] = intval($row['count']) > 0 ? round(floatval($row['amount']) / intval($row['count']), 2) : 0;
		$row['percent'] = $max > 0 && $row['amount'] > 0 ? max(8, intval(round($row['amount'] / $max * 100))) : 0;
	}
}
function user_nl_summary_by_day($uid, $days=14){
	global $DB;
	$items = [];
	$index = [];
	$start = strtotime(date('Y-m-d 00:00:00', strtotime('-'.($days-1).' day')));
	for($i=0;$i<$days;$i++){
		$ts = strtotime('+'.$i.' day', $start);
		$key = date('Y-m-d', $ts);
		$index[$key] = $i;
		$items[] = ['key'=>$key, 'label'=>date('m/d', $ts), 'sub'=>user_nl_weekday($ts), 'count'=>0, 'amount'=>0.0, 'avg'=>0.0, 'percent'=>0];
	}
	$rows = $DB->getAll("SELECT DATE_FORMAT(paid_at,'%Y-%m-%d') k, COUNT(*) c, COALESCE(SUM(amount),0) a FROM ".user_nl_tbl('nl_collect_session')." WHERE uid=:uid AND status='paid' AND paid_at>=:start AND paid_at<DATE_ADD(CURDATE(), INTERVAL 1 DAY) GROUP BY DATE_FORMAT(paid_at,'%Y-%m-%d')", [':uid'=>$uid, ':start'=>date('Y-m-d H:i:s', $start)]);
	if($rows){
		foreach($rows as $row){
			if(isset($index[$row['k']])){
				$pos = $index[$row['k']];
				$items[$pos]['count'] = intval($row['c']);
				$items[$pos]['amount'] = round(floatval($row['a']), 2);
			}
		}
	}
	user_nl_apply_percent($items);
	return $items;
}
function user_nl_summary_by_month($uid, $months=12){
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
	$rows = $DB->getAll("SELECT DATE_FORMAT(paid_at,'%Y-%m') k, COUNT(*) c, COALESCE(SUM(amount),0) a FROM ".user_nl_tbl('nl_collect_session')." WHERE uid=:uid AND status='paid' AND paid_at>=:start GROUP BY DATE_FORMAT(paid_at,'%Y-%m')", [':uid'=>$uid, ':start'=>date('Y-m-d H:i:s', $start)]);
	if($rows){
		foreach($rows as $row){
			if(isset($index[$row['k']])){
				$pos = $index[$row['k']];
				$items[$pos]['count'] = intval($row['c']);
				$items[$pos]['amount'] = round(floatval($row['a']), 2);
			}
		}
	}
	user_nl_apply_percent($items);
	return $items;
}
function user_nl_api_post($base_url, $path, $payload=[], $secret=''){
	if(empty($base_url) || empty($secret) || !function_exists('curl_init')) return ['ok'=>false];
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
	$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	curl_close($ch);
	if(!$response) return ['ok'=>false];
	$data = json_decode($response, true);
	if(!is_array($data) || $http_code >= 400 || intval($data['code'] ?? -1) !== 0) return ['ok'=>false];
	return ['ok'=>true, 'data'=>$data];
}
function user_nl_stat($stats, $key, $default=0){
	return isset($stats[$key]) ? $stats[$key] : $default;
}

$view = isset($_GET['view']) && $_GET['view'] === 'sessions' ? 'sessions' : 'overview';
$local_ready = user_nl_table_exists('nl_collect_session');
$server_url = !empty($conf['notifyledger_server_url']) ? $conf['notifyledger_server_url'] : 'http://127.0.0.1:8098';
$internal_secret = !empty($conf['notifyledger_internal_secret']) ? $conf['notifyledger_internal_secret'] : '';
$api_result = user_nl_api_post($server_url, '/internal/stats', ['uid'=>intval($uid), 'days'=>14, 'months'=>12, 'limit'=>30], $internal_secret);
$api_ready = !empty($api_result['ok']);
$ready = $api_ready || $local_ready;

$waiting = $waiting_amount = $paid = 0;
$today_amount = $today_orders = $yesterday_amount = $yesterday_orders = 0;
$week_amount = $week_orders = $last30_amount = $last30_orders = 0;
$month_amount = $month_orders = $avg_amount = $total_amount = $total_orders = 0;
$last_paid_at = '-';
$sessions = [];
$daily_summary = [];
$monthly_summary = [];

if($api_ready){
	$api_data = $api_result['data'];
	$api_stats = isset($api_data['stats']) && is_array($api_data['stats']) ? $api_data['stats'] : [];
	$waiting = user_nl_stat($api_stats, 'waiting');
	$waiting_amount = user_nl_stat($api_stats, 'waiting_amount');
	$paid = user_nl_stat($api_stats, 'paid');
	$today_amount = user_nl_stat($api_stats, 'amount');
	$today_orders = user_nl_stat($api_stats, 'today_orders');
	$yesterday_amount = user_nl_stat($api_stats, 'yesterday_amount');
	$yesterday_orders = user_nl_stat($api_stats, 'yesterday_orders');
	$week_amount = user_nl_stat($api_stats, 'week_amount');
	$week_orders = user_nl_stat($api_stats, 'week_orders');
	$last30_amount = user_nl_stat($api_stats, 'last30_amount');
	$last30_orders = user_nl_stat($api_stats, 'last30_orders');
	$month_amount = user_nl_stat($api_stats, 'month_amount');
	$month_orders = user_nl_stat($api_stats, 'month_orders');
	$avg_amount = user_nl_stat($api_stats, 'avg_amount');
	$total_amount = user_nl_stat($api_stats, 'total_amount');
	$total_orders = user_nl_stat($api_stats, 'total_orders');
	$last_paid_at = user_nl_stat($api_stats, 'last_paid_at', '-');
	$sessions = isset($api_data['recent_sessions']) && is_array($api_data['recent_sessions']) ? $api_data['recent_sessions'] : [];
	$daily_summary = isset($api_data['daily_summary']) && is_array($api_data['daily_summary']) ? $api_data['daily_summary'] : [];
	$monthly_summary = isset($api_data['monthly_summary']) && is_array($api_data['monthly_summary']) ? $api_data['monthly_summary'] : [];
}elseif($local_ready){
	$session_table = user_nl_tbl('nl_collect_session');
	$waiting = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='waiting'", [':uid'=>$uid]);
	$waiting_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='waiting'", [':uid'=>$uid]);
	$paid = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid'", [':uid'=>$uid]);
	$today_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE(paid_at)=CURDATE()", [':uid'=>$uid]);
	$today_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE(paid_at)=CURDATE()", [':uid'=>$uid]);
	$yesterday_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE(paid_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [':uid'=>$uid]);
	$yesterday_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE(paid_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)", [':uid'=>$uid]);
	$week_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)", [':uid'=>$uid]);
	$week_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)", [':uid'=>$uid]);
	$last30_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)", [':uid'=>$uid]);
	$last30_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)", [':uid'=>$uid]);
	$month_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", [':uid'=>$uid]);
	$month_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", [':uid'=>$uid]);
	$avg_amount = user_nl_value("SELECT COALESCE(ROUND(AVG(amount),2),0) FROM ".$session_table." WHERE uid=:uid AND status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", [':uid'=>$uid]);
	$total_amount = user_nl_value("SELECT COALESCE(SUM(amount),0) FROM ".$session_table." WHERE uid=:uid AND status='paid'", [':uid'=>$uid]);
	$total_orders = user_nl_value("SELECT COUNT(*) FROM ".$session_table." WHERE uid=:uid AND status='paid'", [':uid'=>$uid]);
	$last_paid_at = user_nl_value("SELECT COALESCE(DATE_FORMAT(MAX(paid_at),'%m-%d %H:%i'),'-') FROM ".$session_table." WHERE uid=:uid AND status='paid'", [':uid'=>$uid]);
	$sessions = $DB->getAll("SELECT epay_trade_no,epay_out_trade_no,channel,amount,status,expire_at,paid_at,created_at FROM ".$session_table." WHERE uid=:uid ORDER BY id DESC LIMIT 30", [':uid'=>$uid]);
	$daily_summary = user_nl_summary_by_day($uid, 14);
	$monthly_summary = user_nl_summary_by_month($uid, 12);
}
?>
<div id="content" class="app-content" role="main">
  <div class="app-content-body">
    <div class="notion-page nl-user-page">
      <div class="nl-topbar">
        <div><div class="notion-kicker">BUFAN LEDGER · MERCHANT</div><h1>到账看板</h1><div class="notion-subtitle">展示通过不凡收款管理端创建、匹配并回写的订单。</div></div>
        <div class="nl-actions"><a class="btn btn-default <?php echo $view==='overview'?'active':''?>" href="notifyledger.php?view=overview">统计总览</a><a class="btn btn-default <?php echo $view==='sessions'?'active':''?>" href="notifyledger.php?view=sessions">收款会话</a></div>
      </div>
      <?php if(!$ready){?>
      <div class="panel panel-default"><div class="panel-body">收款管理端接口未连通，且当前 EPay 数据库没有可兼容读取的收款会话表。请联系平台管理员检查 Go 服务地址和内部密钥。</div></div>
      <?php }elseif($view === 'overview'){?>
      <section class="nl-section">
        <div class="nl-section-head"><div><div class="notion-kicker">Overview</div><h2>我的收款总结</h2></div><p>按天看今日/昨日/近 7 日，按月看本月/近 12 月，方便对账。</p></div>
        <div class="nl-summary-grid user-grid user-grid-extended">
          <div class="nl-summary-card primary"><small>今日到账</small><b>¥<?php echo number_format((float)$today_amount,2)?></b><span><?php echo intval($today_orders)?> 笔收款</span></div>
          <div class="nl-summary-card"><small>昨日到账</small><b>¥<?php echo number_format((float)$yesterday_amount,2)?></b><span><?php echo intval($yesterday_orders)?> 笔收款</span></div>
          <div class="nl-summary-card"><small>近 7 日到账</small><b>¥<?php echo number_format((float)$week_amount,2)?></b><span><?php echo intval($week_orders)?> 笔累计</span></div>
          <div class="nl-summary-card"><small>本月到账</small><b>¥<?php echo number_format((float)$month_amount,2)?></b><span><?php echo intval($month_orders)?> 笔 · 均 ¥<?php echo number_format((float)$avg_amount,2)?></span></div>
          <div class="nl-summary-card"><small>近 30 日到账</small><b>¥<?php echo number_format((float)$last30_amount,2)?></b><span><?php echo intval($last30_orders)?> 笔滚动统计</span></div>
          <div class="nl-summary-card"><small>累计到账</small><b>¥<?php echo number_format((float)$total_amount,2)?></b><span><?php echo intval($total_orders)?> 笔 · 最近 <?php echo h($last_paid_at)?></span></div>
          <div class="nl-summary-card"><small>待到账</small><b>¥<?php echo number_format((float)$waiting_amount,2)?></b><span><?php echo intval($waiting)?> 笔等待匹配</span></div>
          <div class="nl-summary-card"><small>已到账订单</small><b><?php echo intval($paid)?></b><span>已同步到订单系统</span></div>
        </div>
        <div class="nl-chart-grid">
          <div class="panel panel-default nl-chart-panel"><div class="nl-chart-head"><div><div class="notion-kicker">Daily</div><h3>近 14 天收款柱状表</h3></div><span>我的订单 · 按天</span></div><div class="nl-bars nl-bars-day"><?php foreach($daily_summary as $row){?><div class="nl-bar-item" title="<?php echo h($row['label'].' '.$row['sub'].'：¥'.number_format($row['amount'],2).' / '.$row['count'].'笔 / 客单¥'.number_format($row['avg'],2))?>"><em>¥<?php echo number_format($row['amount'],2)?></em><div class="nl-bar-track"><div class="nl-bar" style="height:<?php echo intval($row['percent'])?>%"></div></div><span><?php echo h($row['label'])?></span><small><?php echo intval($row['count'])?>笔</small></div><?php }?></div></div>
          <div class="panel panel-default nl-chart-panel"><div class="nl-chart-head"><div><div class="notion-kicker">Monthly</div><h3>近 12 个月收款柱状表</h3></div><span>我的订单 · 按月</span></div><div class="nl-bars nl-bars-month"><?php foreach($monthly_summary as $row){?><div class="nl-bar-item" title="<?php echo h($row['sub'].'年'.$row['label'].'：¥'.number_format($row['amount'],2).' / '.$row['count'].'笔 / 客单¥'.number_format($row['avg'],2))?>"><em>¥<?php echo number_format($row['amount'],2)?></em><div class="nl-bar-track"><div class="nl-bar month" style="height:<?php echo intval($row['percent'])?>%"></div></div><span><?php echo h($row['label'])?></span><small><?php echo intval($row['count'])?>笔</small></div><?php }?></div></div>
        </div>
        <div class="nl-detail-grid user-detail">
          <div class="panel panel-default nl-chart-panel nl-mini-table"><div class="nl-chart-head"><div><div class="notion-kicker">Daily Detail</div><h3>每日明细</h3></div></div><table class="table nl-detail-table"><thead><tr><th>日期</th><th>笔数</th><th>金额</th><th>客单</th></tr></thead><tbody><?php foreach($daily_summary as $row){?><tr><td><?php echo h($row['label'])?><br><small><?php echo h($row['sub'])?></small></td><td><?php echo intval($row['count'])?></td><td>¥<?php echo number_format($row['amount'],2)?></td><td>¥<?php echo number_format($row['avg'],2)?></td></tr><?php }?></tbody></table></div>
          <div class="panel panel-default nl-chart-panel nl-mini-table"><div class="nl-chart-head"><div><div class="notion-kicker">Monthly Detail</div><h3>每月明细</h3></div></div><table class="table nl-detail-table"><thead><tr><th>月份</th><th>笔数</th><th>金额</th><th>客单</th></tr></thead><tbody><?php foreach($monthly_summary as $row){?><tr><td><?php echo h($row['sub'])?><br><small><?php echo h($row['label'])?></small></td><td><?php echo intval($row['count'])?></td><td>¥<?php echo number_format($row['amount'],2)?></td><td>¥<?php echo number_format($row['avg'],2)?></td></tr><?php }?></tbody></table></div>
        </div>
      </section>
      <?php }else{?>
      <section class="nl-section"><div class="nl-section-head"><div><div class="notion-kicker">Sessions</div><h2>最近收款会话</h2></div><p>只显示你自己的不凡收款会话。</p></div><div class="panel panel-default"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>系统订单</th><th>商户订单</th><th>渠道</th><th>金额</th><th>状态</th><th>创建 / 完成</th></tr></thead><tbody><?php foreach($sessions as $row){?><tr><td><?php echo h($row['epay_trade_no'])?></td><td><?php echo h($row['epay_out_trade_no'])?></td><td><span class="label label-default"><?php echo h($row['channel'])?></span></td><td>¥<?php echo h($row['amount'])?></td><td><?php echo h($row['status'])?></td><td><?php echo h($row['created_at'])?><br><span class="text-muted"><?php echo h($row['paid_at'])?></span></td></tr><?php }?></tbody></table></div></div></section>
      <?php }?>
    </div>
  </div>
</div>
<?php include './foot.php';?>
