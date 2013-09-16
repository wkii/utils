<?php

class PhpAes
{
	/**
	 * This was AES-256 / CBC / ZeroBytePadding encrypted.
	 * return base64_encode string
	 * @author Terry
	 * @param string $plaintext
	 * @param string $key
	 */
	public static function AesEncrypt($plaintext,$key = null)
	{
		if(!extension_loaded('mcrypt'))
			throw new CException(Yii::t('yii','AesEncrypt requires PHP mcrypt extension to be loaded in order to use data encryption feature.'));
		
		$module = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
		$key=self::substr($key===null ? Yii::app()->params['encryptKey'] : $key, 0, mcrypt_enc_get_key_size($module));
		/* Create the IV and determine the keysize length, use MCRYPT_RAND
		 * on Windows instead */
		srand();
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_RAND);
		
		/* Intialize encryption */
		mcrypt_generic_init($module, $key, $iv);
		
		/* Encrypt data */
		$encrypted = $iv.mcrypt_generic($module, $plaintext);
		
		/* Terminate encryption handler */
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);
		return str_replace('=','',base64_encode($encrypted));
	}
	
	/**
	 * This was AES-256 / CBC / ZeroBytePadding decrypted.
	 * @author Terry
	 * @param string $encrypted	base64_encode encrypted string
	 * @param string $key
	 * @throws CException
	 * @return string
	 */
	public static function AesDecrypt($encrypted, $key = null)
	{
		if(!extension_loaded('mcrypt'))
			throw new CException(Yii::t('yii','AesDecrypt requires PHP mcrypt extension to be loaded in order to use data encryption feature.'));
		
		$ciphertext_dec = base64_decode($encrypted);
		$module = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
		$key=self::substr($key===null ? Yii::app()->params['encryptKey'] : $key, 0, mcrypt_enc_get_key_size($module));
		$ivSize=mcrypt_enc_get_iv_size($module);
		$iv=substr($ciphertext_dec,0,$ivSize);
		
		/* Initialize encryption module for decryption */
		mcrypt_generic_init($module, $key, $iv);
		
		/* Decrypt encrypted string */
		$decrypted = mdecrypt_generic($module, substr($ciphertext_dec,$ivSize,self::strlen($ciphertext_dec)));
		
		/* Terminate decryption handle and close module */
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);
		return trim($decrypted);
	}
	
	/**
	 * Returns the length of the given string.
	 * If available uses the multibyte string function mb_strlen.
	 * @param string $string the string being measured for length
	 * @return integer the length of the string
	 */
	private static function strlen($string)
	{
		return extension_loaded('mbstring') ? mb_strlen($string,'8bit') : strlen($string);
	}
	
	/**
	 * Returns the portion of string specified by the start and length parameters.
	 * If available uses the multibyte string function mb_substr
	 * @param string $string the input string. Must be one character or longer.
	 * @param integer $start the starting position
	 * @param integer $length the desired portion length
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 */
	private static function substr($string,$start,$length)
	{
		return extension_loaded('mbstring') ? mb_substr($string,$start,$length,'8bit') : substr($string,$start,$length);
	}
}
