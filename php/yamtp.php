<?php
namespace yamtp {
	trait dyn_func_call {
		public function __call($method, $arguments = array()) {
			$method_call = $method . "_instance";
			if (method_exists($this, $method_call)) {
				if ($arguments) {
					return call_user_func_array(array($this, $method_call), $arguments);
				} else {
					return call_user_func(array($this, $method_call));
				}
			} else {
				throw new \Exception("Unknown method call");
			}
		}
		
		public static function __callStatic($method, $arguments = array()) {
			$class = get_called_class();
			$method_call = $method . "_static";
			if (method_exists($class, $method_call)) {
				if ($arguments) {
					if (is_object($arguments[0]))
						$arguments[0] = &$arguments[0];
					return forward_static_call_array(array($class, $method_call), $arguments);
				} else {
					return forward_static_call(array($class, $method_call));
				}
			} else {
				throw new \Exception("Unknown method call");
			}
		}
	}
	
	trait dyn_set_get {
		public function __get($name) {
			$method = "dget_" . $name;
			if (method_exists($this, $method)) {
				return $this->$method();
			} elseif (property_exists($this, $name)) {
				return $this->$name;
			} else {
				$this->get($name);
			}
		}
		
		protected function get($name) {
			if ($name == "obj") {
				echo print_r(debug_backtrace(), true);
			}
			if (isset($this->vars[$name]) || $this->vars[$name] === null) {
				return $this->vars[$name];
			} else {
				echo "name: {$name}\n";
				throw new \Exception("No variable to get");
			}
		}
		
		public function __set($name, $value) {
			$method = "dset_" . $name;
			if (method_exists($this, $method)) {
				$this->$method($value);
			} elseif (property_exists($this, $name)) {
				$this->$name = $value;
			} else {
				$this->set($name, $value);
			}
		}
		
		protected function set($name, $value) {
			if (isset($this->vars[$name]) || $this->vars[$name] === null) {
				$this->vars[$name] = $value;
			} else {
				throw new \Exception("No variable to set");
			}
		}
	}
	
	class yamto {
		use dyn_func_call;
		use dyn_set_get;
		
		protected $vars = array(
			"host"		=> null,
			"port"		=> 80,
			"headers"	=> array(
				"mime"		=> "text/plain",
				"enc" 		=> null,
				"auth"		=> false,
				"type"		=> yamtp::GET
			),
			"message"	=> array(),
			"callback"	=> null
		);
		
		/* Start dyn_get */
		public function dget_host() {
			return $this->get('host');
		}
		
		public function dget_port() {
			return $this->get('port');
		}
		
		public function dget_headers() {
			return $this->get('headers');
		}
		
		public function dget_message() {
			return $this->get('message');
		}
		
		public function dget_callback() {
			return $this->get('callback');
		}
		/* End dyn_get */
		
		/* Start dyn_set */
		public function dset_message($value) {
			$this->set("message", $value);
		}
		
		public function dset_callback($value) {
			$this->set("callback", $value);
		}
		/* End dyn_set */
		
		/* Start instance */
		public function __construct($host, $port = 80) {
			$this->vars['host'] = $host;
			$this->vars['port'] = $port;
		}
		
		protected function set_header_instance($key, $value) {
			if (isset($this->vars['header'][$key])) {
				throw new \Exception("Header already set");
			} else {
				$this->vars['headers'][$key] = $value;
			}
			return $this;
		}
		/* End instance */
		
		protected function set_headers_array_instance($headers) {
			foreach($headers as $key => $value) {
				$this->set_header_instance($key, $value);
			}
			return $this;
		}
		
		/* Start static */
		protected static function set_header_static(&$rs, $key, $value) {
			$rs->set_header_instance($key, $value);
		}
		
		protected static function set_headers_array_static(&$rs, $headers) {
			$rs->set_headers_array_instance($headers);
		}
		/* End static */
	}
	
	class yamtp {
		use dyn_func_call;
		
		const GET		= "get";
		const POST		= "post";
		const PUT		= "put";
		const UPDATE	= "update";
		const DELETE	= "delete";
		
		const PLAIN		= null;
		const URL		= "url";
		const BASE64	= "base64";
		const UU		= "uu";
		
		private $obj = null;
		
		/* Start instance */
		public function __construct($host, $port = 80) {
			$this->obj = new yamto($host, $port);
		}
		
		protected function send_instance($message = null, $transport_agent = transport::CURL, $options = null) {
			$trasporter = new transport();
			$transporter->host = $message->host;
			$transporter->port = $message->port;
			$transporter->data = $this->generate_content($message, $options);
			$transporter->method = $message->headers['type'];
			$transporter->transport_agent = $transport_agent;
			
			$transporter->send();
			
			return $this;
		}
		/* End instance */
		
		/* Start static */
		protected static function open_static($host, $port = 80) {
			return new self($host, $port);
		}
		
		protected static function send_static(&$rs, $message, $headers = array(), $transport_agent = transport::CURL, $options = null) {
			$obj = new yamto();
			$obj->message = $message;
			if ($headers)
				$obj->set_headers_array($headers);
			$rs->send_instance($obj, $transport_agent, $options);
		}
		/* End static */
		
		/* Start private */
		private function generate_content(&$message = null, $options = null) {
			$out = "";
			
			if ($message instanceof \yamtp\yamto) {
				$this->obj = $message;
			} elseif ($message) {
				$this->obj->message;
			}
			
			$message = $this->obj->message;
			
			if ($message) {
				if ($this->obj->headers['enc']) {
					if (is_array($message)) {
						$this->encode_messages($message);
					} else {
						$this->encode_message($message);
					}
				}
				
				$tmpArr = array(
					"headers" => $this->obj->headers,
					"message" => $message,
					"callback" => $this->obj->callback
				);
				$out = json_encode($tmpArr, $options);
			} else {
				throw new \Exception("No message to send");
			}
			
			return $out;
		}
		
		private function encode_message(&$message) {
			switch($this->obj->headers['enc']) {
				case self::URL:
					$message = urlencode($message);
					break;
				case self::BASE64:
					$message = base64_encode($message);
					break;
				case self::UU:
					$message = convert_uuencode($message);
					break;
			}
		}
		
		private function encode_messages(&$messages) {
			foreach($messages as $key => &$value) {
				$this->encode_message($value);
			}
		}
		/* End private */
	}
	
	class transport {
		use dyn_func_call;
		use dyn_set_get;
		
		const CURL		= 1;
		const XHTTP		= 2; // Not used in PHP
		const STREAM	= 3;
		const RAW		= 4;
		
		private $vars = array(
			"host"				=> null,
			"port"				=> 80,
			"data"				=> null,
			"method"			=> yamtp::POST,
			"transport_agent"	=> self::CURL,
			"response"			=> null
		);
		
		/* Start dyn_get */
		public function dget_host() {
			return $this->get("host");
		}
		
		public function dget_port() {
			return $this->get("port");
		}
		
		public function dget_data() {
			return $this->get("data");
		}
		
		public function dget_method() {
			return $this->get("method");
		}
		
		public function dget_transport_agent() {
			return $this->get("transport_agent");
		}
		
		public function dget_response() {
			return $this->get("response");
		}
		/* End dyn_get */
		
		/* Start dyn_set */
		public function dset_data($value) {
			$this->set("data", $value);
		}
		
		public function dset_method($value) {
			$this->set("method", $value);
		}
		
		public function dset_transport_agent($value) {
			$this->set("transport_agent", $value);
		}
		/* End dyn_set */
		
		/* Start instance */
		public function __construct($host, $port = 80) {
			$this->vars['host'] = $host;
			$this->vars['port'] = $port;
		}
		
		protected function send_instance() {
			switch($this->vars['transport_agent']) {
				case self::CURL:
					return $this->send_curl();
					break;
				case self::XHTTP:
					return $this->send_xhttp();
					break;
				case self::STREAM:
					return $this->send_stream();
					break;
				case self::RAW:
					return $this->send_raw();
					break;
			}
		}
		/* End instance */
		
		/* Start static */
		protected static function open_static($host, $port = 80) {
			return new self($host, $port);
		}
		
		protected static function send_static(&$rs, $data, $method = yamtp::POST, $transport_agent = self::RAW) {
			$rs->data = $data;
			$rs->method = $method;
			$rs->transport_agent = $transport_agent;
			return $rs->send();
		}
		/* End static */
		
		/* Start private */
		/**
		 * Possibly not used
		 */
		private function send_curl() {
			$url = $this->vars['host'] . ":" . $this->vars['port'];
			$ch = curl_init();
			
			curl_setopt($ch, \CURLOPT_PORT, $this->vars['port']);
			curl_setopt($ch, \CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, \CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
			
			switch($this->vars['method']) {
				case yamtp::GET:
					if (strpos("?", $url) === false) {
						$url .= "?"
					} else {
						$url .= "&";
					}
					$url .= urlencode($this->vars['data']);
					curl_setopt($ch, \CURLOPT_HTTPGET, true);
					break;
				case yamtp::POST:
					curl_setopt($ch, \CURLOPT_POST, true);
					curl_setopt($ch, \CURLOPT_POSTFIELDS, $this->vars['data']);
					break;
				case yamtp::PUT:
					curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, "PUT");
					curl_setopt($ch, \CURLOPT_POSTFIELDS, $this->vars['data']);
					break;
				case yamtp::UPDATE:
					curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, "UPDATE");
					curl_setopt($ch, \CURLOPT_POSTFIELDS, $this->vars['data']);
					break;
				case yamtp::DELETE:
					curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, "DELETE");
					curl_setopt($ch, \CURLOPT_POSTFIELDS, $this->vars['data']);
					break;
			}
			
			curl_setopt($ch, \CURLOPT_URL, $url);
			
			$response = curl_exec($ch);
			
			if ($response === false) {
				$this->vars['response'] = curl_error();
			} else {
				$this->vars['response'] = $response;
			}
		}
		
		/* Not used in PHP */
		private function send_xhttp() {}
		
		/**
		 * Possibly not used
		 */
		private function send_stream() {
			$url = $this->vars['host'] . ":" . $this->vars['port'];
			
			if ($this->vars['method'] == yamtp::GET) {
				if (strpos("?", $url) === false) {
					$url .= "?"
				} else {
					$url .= "&";
				}
				$url .= urlencode($this->vars['data']);
				
				$opts = array(
					"method" => $this->vars['method'],
					"header" => "",
					"content" => ""
				);
			} else {
				$opts = array(
					"method" => $this->vars['method'],
					"header" => "",
					"content" => $this->vars['data']
				);
			}
			
			$context = stream_context_create($opts);
			
			if ($fp = fopen($this->vars['host'] . ":" . $this->vars['port'], 'rb', false, $context)) {
				fclose($fp);
				$this->vars['result'] = file_get_contents($url, false, $context);
			} else {
				throw new \Exception("Error with URL");
			}
		}
		
		private function send_raw() {
			$url = $this->vars['host'];
			$page = "";
			
			$tmpArr = explode("/", $url);
			$tmp = array_pop($tmpArr);
			if (strpos(".", $tmp) > 0)
				$page = $tmp;
			
			if ($fp = fsockopen($url, $this->vars['port'])) {
				fwrite($fp, "YAMTP/1.0.00\r\n");
				fwrite($fp, "host: {$this->vars['host']}\r\n");
				fwrite($fp, "page: {$page}\r\n");
				fwrite($fp, "content-length: " . strlen($content) . "\r\n");
				fwrite($fp, "referer: {$_SERVER['SERVER_ADDR']} ({$_SERVER['SERVER_NAME']})\r\n");
				fwrite($fp, "originator: {$_SERVER['REMOTE_ADDR']} ({$_SERVER['REMOTE_HOST']})");
				fwrite($fp, "\r\n");
				
				if ($content)
					fwrite($fp, $this->vars['data']);
				
				while (!feof($fp)) {
					$this->vars['result'] .= fgets($fp, 1024);
				}
				
				fclose($fp);
			} else {
				throw new \Exception("Error with URL");
			}
		}
		/* End private */
	}
}
?>
