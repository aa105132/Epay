<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<main class="blue-pay-home">
	<section class="blue-hero">
		<div class="blue-hero-copy">
			<div class="blue-badge"><i class="fa fa-shield"></i> 企业级聚合支付网关</div>
			<h1>聚合支付<br><span>让收款更简单</span></h1>
			<p>面向网站、应用与自动化业务的统一支付接入层。商户只需对接一套接口，即可完成订单提交、支付跳转、状态查询与异步通知。</p>
			<div class="blue-points" aria-label="核心能力">
				<span><i class="fa fa-check-circle"></i>统一接口</span>
				<span><i class="fa fa-random"></i>多渠道聚合</span>
				<span><i class="fa fa-bell-o"></i>异步通知</span>
				<span><i class="fa fa-lock"></i>签名校验</span>
			</div>
			<div class="blue-actions">
				<a class="blue-primary" href="/user/reg.php"><i class="fa fa-bolt"></i>立即接入</a>
				<a class="blue-secondary" href="doc.html"><i class="fa fa-file-text-o"></i>查看文档</a>
			</div>
		</div>

		<div class="blue-hero-art" aria-label="支付网关主视觉">
			<img src="/assets/img/generated/payment-hero-image2.png" alt="支付网关主视觉" />
		</div>
	</section>

	<section class="blue-capability-row" aria-label="平台能力">
		<article><i class="fa fa-plug"></i><strong>统一接口</strong><span>一套参数完成支付接入</span></article>
		<article><i class="fa fa-sitemap"></i><strong>渠道路由</strong><span>按业务策略选择收款通道</span></article>
		<article><i class="fa fa-refresh"></i><strong>订单同步</strong><span>查询与通知保持状态一致</span></article>
		<article><i class="fa fa-code"></i><strong>开发友好</strong><span>文档、示例与 LLM 文本齐全</span></article>
	</section>

	<section class="blue-notice" aria-label="平台公告">
		<div><i class="fa fa-bell"></i><strong>平台公告</strong><span>开发文档与 LLM 接入文本已整理，可直接复制给开发助手完成对接。</span></div>
		<a href="doc.html">查看文档 <i class="fa fa-angle-right"></i></a>
	</section>

	<section class="blue-section blue-split" id="solutions">
		<div class="blue-feature-copy">
			<div class="blue-kicker">PAYMENT WORKFLOW</div>
			<h2>把复杂支付流程收敛到一个网关</h2>
			<p>从创建订单、生成支付链接，到回调验签、订单查询、商户对账，统一放在同一套支付流程里处理。</p>
			<div class="workflow-list">
				<div><i class="fa fa-paper-plane-o"></i><strong>订单提交</strong><span>商户系统提交标准参数</span></div>
				<div><i class="fa fa-credit-card"></i><strong>支付收银</strong><span>用户进入收银台完成付款</span></div>
				<div><i class="fa fa-bell-o"></i><strong>回调通知</strong><span>支付结果主动推送到业务系统</span></div>
				<div><i class="fa fa-search"></i><strong>订单查询</strong><span>按订单号查询最终支付状态</span></div>
			</div>
		</div>
		<div class="blue-feature-art"><img src="/assets/img/generated/gateway-feature-panel-image2.png" alt="支付流程能力示意" /></div>
	</section>

	<section class="blue-section">
		<div class="blue-section-head">
			<h2>产品优势</h2>
			<p>面向真实业务接入设计，降低开发、运维和对账成本。</p>
		</div>
		<div class="blue-adv-grid">
			<article><i class="fa fa-th-large green"></i><h3>多渠道聚合</h3><p>聚合主流支付能力，商户无需为每个渠道分别维护接入逻辑。</p></article>
			<article><i class="fa fa-random blue"></i><h3>灵活分发</h3><p>可按业务规则选择收款方式，适配不同商品、商户与场景。</p></article>
			<article><i class="fa fa-shield purple"></i><h3>安全验签</h3><p>通过签名校验、参数校验与回调校验，减少异常请求风险。</p></article>
			<article><i class="fa fa-code orange"></i><h3>快速接入</h3><p>提供标准 API、接入文档、示例参数与 LLM 接入说明。</p></article>
			<article><i class="fa fa-bar-chart cyan"></i><h3>清晰对账</h3><p>商户后台集中查看订单状态，方便业务侧完成核对。</p></article>
		</div>
	</section>

	<section class="blue-section pay-methods">
		<div class="blue-section-head inline">
			<div><h2>支持的支付方式</h2><p>覆盖常见线上收款方式，按业务需要接入。</p></div>
			<a href="produceIntroduce.html">查看更多 <i class="fa fa-angle-right"></i></a>
		</div>
		<div class="method-grid">
			<div class="method-card wechat"><i class="fa fa-wechat"></i><strong>微信支付</strong></div>
			<div class="method-card alipay"><span>支</span><strong>支付宝</strong></div>
			<div class="method-card union"><span>银</span><strong>银联支付</strong></div>
			<div class="method-card cloud"><i class="fa fa-cloud"></i><strong>云闪付</strong></div>
			<div class="method-card bank"><i class="fa fa-bank"></i><strong>网银支付</strong></div>
			<div class="method-card more"><i class="fa fa-th-large"></i><strong>更多方式</strong></div>
		</div>
	</section>

	<section class="blue-bottom-strip">
		<div><i class="fa fa-book"></i><strong>开发文档</strong><span>接口参数、签名规则、回调说明</span></div>
		<div><i class="fa fa-download"></i><strong>LLM 文本</strong><span>可下载后交给开发助手接入</span></div>
		<div><i class="fa fa-user"></i><strong>商户后台</strong><span>订单查询、资料配置、接口密钥</span></div>
		<div><i class="fa fa-check-circle"></i><strong>状态闭环</strong><span>提交、支付、通知、查询完整闭环</span></div>
	</section>
</main>
<script>
	(function($) {
		$('body').addClass('landing modern-landing blue-pay-page');
	})(jQuery);
</script>
<?php require INDEX_ROOT.'foot.php';?>
