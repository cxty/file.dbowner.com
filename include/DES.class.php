<?php
class DES
{
    var $key;
    var $iv; //偏移量
   
    function DES( $key, $iv=0 ) {
    //key长度8例如:1234abcd
        $this->key = $key;
        if( $iv == 0 ) {
            $this->iv = $key; //默认以$key 作为 iv
        } else {
            $this->iv = $iv; //mcrypt_create_iv ( mcrypt_get_block_size (MCRYPT_DES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM );
        }
    }
   /**
		 * 加密
		 * @param string $encrypt
		 */
		public function encrypt($encrypt)
		{
			$size = mcrypt_get_block_size('des', 'ecb');
			$input = $this->pkcs5_pad($encrypt, $size);
			$key = $this->key;
			$td = mcrypt_module_open('des', '', 'ecb', '');
			$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			@mcrypt_generic_init($td, $this->key, $this->iv);
			$data = mcrypt_generic($td, $input);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$data = base64_encode($data);
			return $data;
		}
		/**
		 * 解密
		 * @param string $decrypt
		 */
		public function decrypt($decrypt) {
			  $encrypted = base64_decode($decrypt);   
			  $td = mcrypt_module_open('des','','ecb',''); //使用MCRYPT_DES算法,cbc模式         
			  $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);      
			  $ks = mcrypt_enc_get_key_size($td);        
			  @mcrypt_generic_init($td, $this->key, $this->iv);       //初始处理          
			  $decrypted = mdecrypt_generic($td, $encrypted);       //解密          
			  mcrypt_generic_deinit($td);       //结束         
			  mcrypt_module_close($td);              
			  $y=$this->pkcs5_unpad($decrypted);       
			  return $y;
		}
		public function pkcs5_pad ($text, $blocksize) {
			$pad = $blocksize - (strlen($text) % $blocksize);
			return $text . str_repeat(chr($pad), $pad);
		}
		public function pkcs5_unpad($text)
		{
			$pad = ord($text{strlen($text)-1});
			if ($pad > strlen($text)) return false;
			if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
			return substr($text, 0, -1 * $pad);
		}
}

?>