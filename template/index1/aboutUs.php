<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<main class="blue-pay-home nav-visual-page about-page">
	<section class="nav-visual-hero">
		<div class="nav-visual-copy">
			<p class="blue-kicker">ABOUT US</p>
			<h1>长期维护一套可信的支付基础设施</h1>
			<p><?php echo h($conf['orgname'])?> 面向商户与开发者提供聚合支付接入能力，重点放在接口清晰、状态可靠、对账可追踪。</p>
			<div class="nav-inline-actions">
				<a class="blue-primary" href="mailto:<?php echo h($conf['email'])?>">联系合作</a>
				<a class="blue-secondary" href="doc.html">查看接口</a>
			</div>
		</div>
		<div class="nav-visual-art"><img src="/assets/img/generated/nav-about-image2.png" alt="关于我们视觉图" /></div>
	</section>

	<section class="about-statement">
		<div>
			<p class="blue-kicker">SERVICE PRINCIPLES</p>
			<h2>少一点包装，多一点确定性。</h2>
		</div>
		<div class="about-principles">
			<p><b>接口稳定</b><span>支付参数、签名规则、回调格式保持清晰。</span></p>
			<p><b>状态可查</b><span>支付结果支持通知与查询双路径确认。</span></p>
			<p><b>接入友好</b><span>开发文档、示例和 LLM 文本让接入更直接。</span></p>
		</div>
	</section>

	<section class="contact-minimal">
		<h2>联系方式</h2>
		<a href="https://wpa.qq.com/msgrd?v=3&uin=<?php echo h($conf['kfqq'])?>&Site=pay&Menu=yes" target="_blank">QQ：<?php echo h($conf['kfqq'])?></a>
		<a href="mailto:<?php echo h($conf['email'])?>">邮箱：<?php echo h($conf['email'])?></a>
	</section>
</main>
<script>
	(function($) {
		$('body').addClass('landing modern-landing blue-pay-page nav-visual-body');
	})(jQuery);
</script>
<?php require INDEX_ROOT.'foot.php';?>
