<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<main class="blue-pay-home nav-visual-page solutions-page">
	<section class="nav-visual-hero reverse">
		<div class="nav-visual-copy">
			<p class="blue-kicker">SOLUTIONS</p>
			<h1>适配不同业务的收款场景</h1>
			<p>网站、移动应用、自动化订单、虚拟商品与商户分发，都可以通过同一套网关能力完成支付接入。</p>
			<div class="nav-inline-actions">
				<a class="blue-primary" href="doc.html">查看方案接入</a>
				<a class="blue-secondary" href="/user/">进入商户后台</a>
			</div>
		</div>
		<div class="nav-visual-art"><img src="/assets/img/generated/nav-solutions-image2.png" alt="解决方案视觉图" /></div>
	</section>

	<section class="scenario-strip">
		<div><b>网站收款</b><span>下单后跳转到收银台完成支付</span></div>
		<div><b>应用接入</b><span>移动端或 H5 统一承接支付流程</span></div>
		<div><b>订单自动化</b><span>回调通知触发业务系统发货或开通</span></div>
		<div><b>商户分发</b><span>不同商户按配置使用自己的支付参数</span></div>
	</section>

	<section class="thin-process-section split-text">
		<div>
			<p class="blue-kicker">ROUTING LOGIC</p>
			<h2>管理端配置账号，支付网关只消费开放接口。</h2>
		</div>
		<div class="solution-copy-list">
			<p>收款账号、通道状态、可用策略留在管理端配置。</p>
			<p>支付网关通过接口获取可用能力，不直接绑定部署地址。</p>
			<p>业务系统只关心订单是否到账，以及是否收到可信通知。</p>
		</div>
	</section>
</main>
<script>
	(function($) {
		$('body').addClass('landing modern-landing blue-pay-page nav-visual-body');
	})(jQuery);
</script>
<?php require INDEX_ROOT.'foot.php';?>
