<?php
if(!defined('IN_CRONLITE'))exit();
?>
			<footer class="modern-footer">
				<div class="footer-callout">
					<p class="eyebrow">READY</p>
					<h2>一套清爽稳定的支付网关，用统一接口承接商户收款。</h2>
					<div class="footer-actions">
						<a href="/user/reg.php" class="pill-link dark">注册商户</a>
						<a href="doc.html" class="quiet-link">查看开发文档</a>
					</div>
				</div>
				<div class="footer-grid">
					<div>
						<a class="brand-mark footer-brand" href="/">
							<span class="gateway-logo-icon footer-logo-icon"><img class="gateway-logo-img" src="/assets/img/generated/gateway-logo-mark-image2.png" alt="" /></span>
							<span class="brand-text"><strong>支付网关</strong><em>Unified Payment Gateway</em></span>
						</a>
						<p class="footer-note"><?php echo h($conf['sitename'])?> &copy; <?php echo date("Y")?>. <?php echo $conf['footer']?></p>
					</div>
					<div class="footer-links">
						<h3>产品</h3>
						<a href="produceIntroduce.html">功能介绍</a>
						<a href="doc.html">开发文档</a>
						<a href="/user/test.php">在线测试</a>
					</div>
					<div class="footer-links">
						<h3>账户</h3>
						<a href="/user/">商户登录</a>
						<a href="/user/reg.php">商户注册</a>
						<a href="agreement.html">用户协议</a>
					</div>
					<div class="footer-links">
						<h3>联系</h3>
						<a href="https://wpa.qq.com/msgrd?v=3&uin=<?php echo h($conf['kfqq'])?>&Site=pay&Menu=yes" target="_blank">QQ：<?php echo h($conf['kfqq'])?></a>
						<a href="mailto:<?php echo h($conf['email'])?>"><?php echo h($conf['email'])?></a>
					</div>
				</div>
			</footer>
		</div>
		<script src="<?php echo STATIC_ROOT?>js/reveal-motion.js?v=20260618"></script>
	</body>
</html>
