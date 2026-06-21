<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<main class="blue-pay-home nav-visual-page product-center-page">
	<section class="nav-visual-hero">
		<div class="nav-visual-copy">
			<p class="blue-kicker">PRODUCT CENTER</p>
			<h1>把收款能力拆成清晰模块</h1>
			<p>支付网关、统一收银台、订单管理、异步通知和商户后台保持同一套流程，业务侧只需要围绕订单状态完成接入。</p>
			<div class="nav-inline-actions">
				<a class="blue-primary" href="/user/reg.php">注册接入</a>
				<a class="blue-secondary" href="doc.html">查看文档</a>
			</div>
		</div>
		<div class="nav-visual-art"><img src="/assets/img/generated/nav-product-center-image2.png" alt="产品中心视觉图" /></div>
	</section>

	<section class="plain-feature-band">
		<div class="plain-feature-line strong"><span>01</span><h2>支付网关</h2><p>统一承接商户下单请求，负责参数校验、签名校验和支付入口分发。</p></div>
		<div class="plain-feature-line"><span>02</span><h2>统一收银台</h2><p>用户进入收银台后选择支付方式，前台流程更清晰。</p></div>
		<div class="plain-feature-line"><span>03</span><h2>订单中心</h2><p>订单提交、支付中、已支付、异常状态集中展示，便于商户核对。</p></div>
		<div class="plain-feature-line"><span>04</span><h2>通知回调</h2><p>支付完成后主动推送给业务系统，也可通过查询接口兜底确认。</p></div>
	</section>

	<section class="thin-process-section">
		<div>
			<p class="blue-kicker">CAPABILITY MAP</p>
			<h2>不用把能力堆成卡片，流程本身就应该清楚。</h2>
		</div>
		<ol class="thin-process-list">
			<li><b>提交订单</b><span>商户按标准参数发起支付请求</span></li>
			<li><b>生成入口</b><span>平台返回支付链接或收银台页面</span></li>
			<li><b>完成支付</b><span>用户按业务选择对应支付方式</span></li>
			<li><b>同步状态</b><span>回调通知与主动查询共同确认结果</span></li>
		</ol>
	</section>
</main>
<script>
	(function($) {
		$('body').addClass('landing modern-landing blue-pay-page nav-visual-body');
	})(jQuery);
</script>
<?php require INDEX_ROOT.'foot.php';?>
