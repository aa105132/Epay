<?php
$is_defend=true;
include("../includes/common.php");

if(isset($_GET['invite'])){
    $invite_code = trim($_GET['invite']);
    $uid = get_invite_uid($invite_code);
    if($uid && is_numeric($uid)){
        $_SESSION['invite_uid'] = intval($uid);
    }
}

if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

if(!$conf['reg_input_settle'] && (empty($userrow['account']) || empty($userrow['username']))){
    exit("<script language='javascript'>window.location.href='./completeinfo.php';</script>");
}

if($userrow['status']==0){
    $status = '<font color="red">已封禁</font>';
}elseif($userrow['pay']==0 && $userrow['settle']==0){
    $status = '<font color="red">关闭支付、结算</font>';
}elseif($userrow['pay']==0){
    $status = '<font color="red">关闭支付</font>';
}elseif($userrow['settle']==0){
    $status = '<font color="red">关闭结算</font>';
}elseif($conf['cert_force']==1 && $userrow['cert']==0){
    $status = '<a href="certificate.php"><font color="red">未实名认证</font></a>';
}elseif($userrow['pay']==2){
    $status = '<font color="orange">待审核</font>';
}else{
    $status = '<font color="green">正常</font>';
}
$title='用户中心';
include './head.php';
?>
<style>
/* 仪表盘专属布局（使用 Material token） */
.md-stat-tile{display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:18px 20px !important;}
.md-stat-tile .md-stat-value{font-size:1.85rem;line-height:1.1;font-weight:600;margin:0;}
.md-stat-tile .md-stat-label{font-size:0.82rem;}
.md-merchant-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:16px 20px;}
.md-merchant-body{padding:8px 20px 20px;text-align:center;}
.md-status-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:var(--md-shape-full);font-size:0.8rem;font-weight:600;background:var(--md-success-container);color:var(--md-on-success-container);margin-top:6px;}
.md-stat-row{display:flex;border-top:1px solid var(--md-outline-variant);}
.md-stat-row > .col{flex:1;padding:14px 8px;text-align:center;}
.md-stat-row > .col + .col{border-left:1px solid var(--md-outline-variant);}
.md-stat-row .h3{font-size:1.25rem;font-weight:600;margin:0 0 2px;}
.md-chart-card .tab-content{padding:8px 12px 16px;}
.md-chart-card .nav-tabs{margin:0 -20px;padding:0 20px;}
.md-empty-chart{display:grid;place-items:center;height:260px;color:var(--md-on-surface-variant);text-align:center;}
.md-empty-chart p{margin:4px 0;}
</style>
<?php
$rs=$DB->query("SELECT * FROM pre_settle WHERE uid={$uid} AND status=1 ORDER BY id DESC LIMIT 9");
$max_settle=0;
$chart='';
$i=0;
while($row = $rs->fetch())
{
    if($row['money']>$max_settle)$max_settle=$row['money'];
    $chart.='['.$i++.','.$row['money'].'],';
}
$chart=substr($chart,0,-1);

$list = $DB->getAll("SELECT * FROM pre_anounce WHERE status=1 ORDER BY sort ASC");
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">
        <div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
                        </button>
                        <h4 class="modal-title">欢迎回来</h4>
                    </div>
                    <div class="modal-body">
<?php echo h($conf['modal'])?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
                    </div>
                </div>
            </div>
        </div>

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">用户中心</h1>
  <small class="text-muted">欢迎使用<?php echo $conf['sitename']?></small>
</div>
<div class="wrapper-md control">
<!-- stats -->
<?php
if($conf['cert_force']==1 && $userrow['cert']==0){
    echo '<div class="alert alert-danger"><span class="btn-sm btn-danger">重要</span>&nbsp;请完成实名认证，否则您的商户无法正常收款！ <a href="./certificate.php" class="btn btn-default btn-xs">立即实名认证</a></div>';
}
if($conf['verifytype']==1 && empty($userrow['phone'])){
    echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有绑定密保手机，请&nbsp;<a href="editinfo.php" class="btn btn-default btn-xs">尽快绑定</a></div>';
}elseif($conf['verifytype']==0 && empty($userrow['email'])){
    echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有绑定密保邮箱，请&nbsp;<a href="editinfo.php" class="btn btn-default btn-xs">尽快绑定</a></div>';
}
if(empty($userrow['pwd'])){
    echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有设置登录密码，请&nbsp;<a href="userinfo.php?mod=account" class="btn btn-default btn-xs">点此设置</a>，设置登录密码之后你就可以使用手机号/邮箱+密码登录</div>';
}
?>

          <div class="row row-sm text-center">
            <div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item md-stat-tile">
                <div class="round"><i class="fa fa-money fa-fw"></i></div>
                <div class="md-stat-value text-primary-dk"><span class="text-muted text-md">¥</span><?php echo $userrow['money']?></div>
                <div class="md-stat-label text-muted">商户当前余额</div>
              </div>
            </div>
            <div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item md-stat-tile">
                <div class="round"><i class="fa fa-check-square-o fa-fw"></i></div>
                <div class="md-stat-value text-dark-dk"><span class="text-muted text-md">¥</span><span id="settle_money"></span></div>
                <div class="md-stat-label text-muted">已结算余额</div>
              </div>
            </div>
            <div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item md-stat-tile">
                <div class="round"><i class="fa fa-area-chart fa-fw"></i></div>
                <div class="md-stat-value text-success-dk"><span id="orders"></span><span class="text-muted text-md">个</span></div>
                <div class="md-stat-label text-muted">订单总数</div>
              </div>
            </div>
            <div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item md-stat-tile">
                <div class="round"><i class="fa fa-cart-plus fa-fw"></i></div>
                <div class="md-stat-value text-info-dk"><span id="orders_today"></span><span class="text-muted text-md">个</span></div>
                <div class="md-stat-label text-muted">今日订单</div>
              </div>
            </div>
        </div>
        <div class="row">
        <div class="col-md-6">

        <div class="panel b-a">
            <div class="panel-heading bg-info dk no-border md-merchant-head">
              <a class="btn btn-sm btn-rounded btn-info" href="./userinfo.php?mod=api"><i class="fa fa-lock fa-fw"></i>&nbsp;API信息</a>
              <a class="btn btn-sm btn-rounded btn-info" href="./editinfo.php"><i class="fa fa-cog fa-fw"></i>&nbsp;修改资料</a>
            </div>
            <div class="md-merchant-body clearfix">
              <div class="thumb-lg avatar m-t-n-xxl">
                <img src="<?php echo ($userrow['qq'])?'//q2.qlogo.cn/headimg_dl?bs=qq&dst_uin='.$userrow['qq'].'&src_uin='.$userrow['qq'].'&fid='.$userrow['qq'].'&spec=100&url_enc=0&referer=bu_interface&term_type=PC':'assets/img/user.png'?>" alt="..." class="b b-3x b-white">
              </div>
              <div class="h2 font-thin m-t-sm">欢迎您，<?php echo $userrow['username']?></div>
              <div class="md-status-chip"><i class="fa fa-circle" style="font-size:0.5rem;"></i>商户状态：<?php echo $status;?></div>
            </div>
            <div class="md-stat-row">
              <a class="col text-muted">
                <div class="h3"><span id="order_today_all"></span></div>
                <i class="fa fa-plus fa-fw"></i><span>今日收入</span>
              </a>
              <a class="col text-muted">
                <div class="h3"><span id="order_lastday_all"></span></div>
                <i class="fa fa-plus-circle fa-fw"></i><span>昨日收入</span>
              </a>
            </div>
            <?php if($conf['user_transfer']==1){?>
            <div class="md-stat-row">
              <a class="col text-muted">
                <div class="h3"><span id="transfer_today_all"></span></div>
                <i class="fa fa-send fa-fw"></i><span>今日支出</span>
              </a>
              <a class="col text-muted">
                <div class="h3"><span id="transfer_lastday_all"></span></div>
                <i class="fa fa-send-o fa-fw"></i><span>昨日支出</span>
              </a>
            </div>
            <?php }?>
        </div>

        <div class="panel panel-default text-center">
        <div class="panel-heading font-bold">
            收入统计与通道费率
        </div>
        <div class="table-responsive">
        <table class="table table-striped">
        <thead><tr id="paytypes"></tr></thead>
        <tbody><tr id="order_today"></tr><tr id="order_lastday"></tr><tr id="success_rate"></tr><tr id="payrates"></tr></tbody>
        </table>
        </div>
        </div>

        </div>
        <div class="col-md-6">

        <div class="panel panel-default">
        <div class="panel-heading font-bold text-center">
            公告通知
        </div>
        <div class="list-group">
<?php foreach($list as $row){?>
            <div class="list-group-item"><em class="fa fa-fw fa-volume-up"></em><font color="<?php echo h($row['color'])?$row['color']:null?>"><?php echo $row['content']?></font><span class="text-xs text-muted">&nbsp;-<?php echo $row['addtime']?></span></div>
<?php }?>
        </div>
        </div>

        <div class="panel wrapper md-chart-card">
            <label class="i-switch bg-warning pull-right" ng-init="showSpline=true">
              <input type="checkbox" ng-model="showSpline">
              <i></i>
            </label>

            <!-- Tab导航 -->
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#settle-tab" aria-controls="settle-tab" role="tab" data-toggle="tab">结算统计</a>
              </li>
              <li role="presentation">
                <a href="#order-tab" aria-controls="order-tab" role="tab" data-toggle="tab">订单金额统计</a>
              </li>
            </ul>

            <!-- Tab内容 -->
            <div class="tab-content">
              <!-- 结算统计表 -->
              <div role="tabpanel" class="tab-pane active" id="settle-tab">
<?php if($chart!==''){?>
                <div ui-jq="plot" ui-refresh="showSpline" ui-options="
                  [
                    { data: [ <?php echo $chart?> ], label:'结算金额', points: { show: true, radius: 1}, splines: { show: true, tension: 0.4, lineWidth: 1, fill: 0.8 } }
                  ],
                  {
                    colors: ['#23b7e5', '#7266ba'],
                    series: { shadowSize: 3 },
                    xaxis:{ font: { color: '#a1a7ac' } },
                    yaxis:{ font: { color: '#a1a7ac' }, max:<?php echo ($max_settle+10)?> },
                    grid: { hoverable: true, clickable: true, borderWidth: 0, color: '#dce5ec' },
                    tooltip: true,
                    tooltipOpts: { content: '结算金额¥%y',  defaultTheme: false, shifts: { x: 10, y: -25 } }
                  }
                " style="height:260px" >
                </div>
<?php }else{?>
                <div class="md-empty-chart">
                  <i class="fa fa-line-chart" style="font-size:2rem;color:var(--md-outline-variant);margin-bottom:8px;"></i>
                  <p>暂无结算记录</p>
                  <p class="text-muted">结算完成后将在此展示近 7 笔金额趋势</p>
                </div>
<?php }?>
              </div>

              <!-- 订单统计表 -->
              <div role="tabpanel" class="tab-pane" id="order-tab">
                <div id="order-chart-container" style="height:260px">
                  <div class="md-empty-chart">
                    <p>点击切换到此标签页将加载订单统计数据</p>
                    <p class="text-muted">显示近7天订单金额趋势</p>
                  </div>
                </div>
              </div>
            </div>
        </div>
        </div>
      </div>
      <!-- / stats -->
</div>
    </div>
  </div>

<script src="/assets/js/chart.js"></script>
<?php include 'foot.php';?>
<script>
$(document).ready(function(){
	$.ajax({
		type : "GET",
		url : "ajax2.php?act=getcount",
		dataType : 'json',
		async: true,
		success : function(data) {
			$('#orders').html(data.orders);
			$('#orders_today').html(data.orders_today);
			$('#settle_money').html(data.settle_money);
			$('#order_today_all').html(data.order_today_all);
			$('#order_lastday_all').html(data.order_lastday_all);
			$('#transfer_today_all').html(data.transfer_today_all);
			$('#transfer_lastday_all').html(data.transfer_lastday_all);
			$.each(data.channels, function (i, item) {
				$('#paytypes').append('<th style="text-align:center;"><img src="/assets/icon/'+item.name+'.ico" width="18px">&nbsp;'+item.showname+'</th>');
			});
			$.each(data.channels, function (i, item) {
				$('#order_today').append('<td>今日：'+item.order_today+' 元</td>');
				$('#order_lastday').append('<td>昨日：'+item.order_lastday+' 元</td>');
				$('#success_rate').append('<td>成功率：'+item.success_rate+' %</td>');
				$('#payrates').append('<td>费率：'+item.rate+' %</td>');
			});
		}
	});
	<?php if(!empty($conf['modal'])){?>
	$('#myModal').modal('show');
	<?php }?>

	// 监听tab切换事件
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("href"); // 激活的tab
		if (target === '#order-tab') {
			// 当切换到订单统计表时
			loadOrderChart();
		}
	});

	function loadOrderChart() {
		// 如果图表已经初始化，则销毁它，避免重复初始化
		if (window.orderChartInstance) {
			window.orderChartInstance.destroy();
		}

		// 显示加载中
		$('#order-chart-container').html('<div class="text-center" style="padding:100px 0;">加载中...</div>');

		$.ajax({
			type : "GET",
			url : "ajax2.php?act=orderCount",
			dataType : 'json',
			async: true,
			success : function(data) {
				initOrderChart(data);
			}
		});
	}

	function initOrderChart(data) {
		// 准备canvas容器
		$('#order-chart-container').html('<canvas id="orderChart"></canvas>');

		var ctx = document.getElementById('orderChart').getContext('2d');
		window.orderChartInstance = new Chart(ctx, {
			type: 'line',
			data: {
				labels: data.labels,
				datasets: data.datasets
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							callback: function(value) {
								return '¥' + value;
							}
						}
					}
				},
				plugins: {
					legend: {
						display: true,
						position: 'top'
					},
					tooltip: {
						mode: 'index',
						intersect: false
					}
				}
			}
		});
	}
});
</script>
