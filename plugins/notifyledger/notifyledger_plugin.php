<?php

class notifyledger_plugin
{
	static public $info = [
		'name'        => 'notifyledger',
		'showname'    => '不凡收款通知',
		'author'      => '不凡',
		'link'        => 'https://github.com/aa105132/AndroidNotificationDispatcher',
		'types'       => ['alipay','wxpay'],
		'inputs' => [
			'appurl' => [
				'name' => '不凡 Go 服务地址',
				'type' => 'input',
				'note' => '例如：http://127.0.0.1:8098，结尾不要加 /',
			],
			'appkey' => [
				'name' => '内部通信密钥',
				'type' => 'input',
				'note' => '必须与 Go 服务 NL_EPAY_INTERNAL_SECRET 一致',
			],
			'timeout' => [
				'name' => '订单超时时间',
				'type' => 'input',
				'note' => '单位秒，默认 900',
			],
		],
		'select' => [
			'1' => '通知监听收款',
		],
		'note' => '通过不凡 Go 收款管理端创建收款会话，由安卓通知监听端上报到账通知后自动回写订单。',
		'bindwxmp' => false,
		'bindwxa' => false,
	];

	static public function submit(){
		return self::createSession(false);
	}

	static public function mapi(){
		return self::createSession(true);
	}

	static private function createSession($api=false){
		global $channel, $order, $siteurl, $conf;
		$appurl = rtrim($channel['appurl'], '/');
		$appkey = !empty($conf['notifyledger_internal_secret']) ? $conf['notifyledger_internal_secret'] : (!empty($channel['appkey']) ? $channel['appkey'] : SYS_KEY);
		if(empty($appurl)){
			return ['type'=>'error','msg'=>'不凡 Go 服务地址未配置'];
		}
		if(empty($appkey)){
			return ['type'=>'error','msg'=>'不凡内部通信密钥未配置'];
		}
		$timeout = intval($channel['timeout']);
		if($timeout <= 0) $timeout = 900;
		$payload = [
			'trade_no' => $order['trade_no'],
			'out_trade_no' => $order['out_trade_no'],
			'uid' => intval($order['uid']),
			'channel' => $order['typename'],
			'amount' => floatval($order['realmoney']),
			'expire_at' => date('Y-m-d H:i:s', time() + $timeout),
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'notify_url' => $siteurl.'notifyledger_internal.php',
		];
		$result = self::postJson($appurl.'/internal/collect-sessions', $payload, $appkey);
		if(!$result || intval($result['code']) !== 0 || empty($result['pay_url'])){
			$msg = $result && isset($result['msg']) ? $result['msg'] : '不凡 Go 服务无响应';
			return ['type'=>'error','msg'=>'创建不凡收款会话失败：'.$msg];
		}
		if($api){
			return ['type'=>'jump','url'=>$result['pay_url']];
		}
		return ['type'=>'jump','url'=>$result['pay_url']];
	}

	static private function postJson($url, $payload, $secret){
		$body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$timestamp = strval(time());
		$nonce = bin2hex(random_bytes(8));
		$sign = hash_hmac('sha256', $timestamp."\n".$nonce."\n".$body, $secret);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'X-Timestamp: '.$timestamp,
			'X-Nonce: '.$nonce,
			'X-Signature: '.$sign,
		]);
		$response = curl_exec($ch);
		curl_close($ch);
		if(!$response) return null;
		$data = json_decode($response, true);
		return is_array($data) ? $data : null;
	}

	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	static public function return(){
		global $order;
		processReturn($order);
		return ['type'=>'page','page'=>'return'];
	}
}
