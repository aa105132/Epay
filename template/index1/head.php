<?php
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="default" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="keywords" content="<?php echo h($conf['keywords'])?>" />
		<meta name="description" content="<?php echo h($conf['sitename'])?>提供统一支付接口、商户后台、订单查询和异步通知能力。" />
		<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/main.css" />
		<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/yohaku-modern.css?v=20260618-image2" />
		<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" />
		<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
		<script src="<?php echo $cdnpublic?>jquery.dropotron/1.4.3/jquery.dropotron.min.js"></script>
		<!--[if lte IE 8]><script src="<?php echo $cdnpublic?>html5shiv/3.7.3/html5shiv.min.js"></script><![endif]-->
		<!--[if lte IE 8]><script src="<?php echo $cdnpublic?>respond.js/1.4.2/respond.min.js"></script><![endif]-->
		<title><?php echo h($conf['sitename'])?> - 聚合支付网关</title>
	</head>

	<body class="bufan-site">
		<div id="page-wrapper" class="site-shell">
			<header id="header" class="modern-header">
				<a class="brand-mark gateway-logo-link" href="/" aria-label="<?php echo h($conf['sitename'])?> 首页">
					<span class="gateway-logo-icon"><img class="gateway-logo-img" src="/assets/img/generated/gateway-logo-mark-image2.png" alt="" /></span>
					<span class="brand-text gateway-brand-text"><strong>支付网关</strong><em>PAYMENT GATEWAY</em></span>
				</a>
				<nav class="modern-nav" aria-label="主导航">
					<a href="/">首页</a>
					<a href="produceIntroduce.html">产品中心</a>
					<a href="solutions.html">解决方案</a>
					<a href="doc.html">开发文档</a>
					<a href="aboutUs.html">关于我们</a>
				</nav>
				<div class="modern-actions">
					<a href="/user/" class="quiet-link">商户登录</a>
					<a href="/user/reg.php" class="pill-link">注册接入</a>
				</div>
			</header>
