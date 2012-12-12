<?php
class Utils
{
	/**
	 * PBKDF2 加密函数
	 * 参考标准
	 * @link https://www.ietf.org/rfc/rfc2898.txt
	 *
	 * php官方函数将在php5.5发布
	 * @see http://php.net/manual/en/function.hash-pbkdf2.php
	 * example: pbkdf2("sha256", 'password', 'salt', 1, 20);
	 * result:120fb6cffcf8b32c43e7 (与php5.5内置的pbkdf2函数输出一至)
	 *
	 * @param string $algo 			The hash algorithm to use. Recommended: SHA256
	 * @param string $password 		The password to use for the derivation.
	 * @param string $salt 			The salt to use for the derivation.
	 * @param int	 $iterations	The number of internal iterations to perform for the derivation.
	 * @param int	 $length		The length of the derived key to output. If 0, the length of the supplied algorithm is used.
	 * @param boolean $raw_output	When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
	 * @return string
	 */
	public static function pbkdf2($algo, $password, $salt, $iterations, $length= 0, $raw_output = false)
	{
		$algo = strtolower($algo);
		if(!in_array($algo, hash_algos(), true))
			throw new Exception('PBKDF2 ERROR: Invalid hash algorithm.');
		if($iterations <= 0)
			throw new Exception('PBKDF2 ERROR: Invalid parameters.');
		// hash方式默认长度，对一个空值进行hash计算，得出
		// length in octets of pseudorandom function output, a positive integer
		$hlen = strlen(hash($algo, null, true));
		if ($length <=0) $length = $hlen;
		if ($length > (pow(2,32)-1)*$hlen)
			throw new Exception('PBKDF2 ERROR: derived key too long.');
	
		// 如果生成的加密值长度低于取值长度，就将加密再执行N遍以补足数据长度
		// N=取值长度除以加密方式对应的值长度加一法取整
		$block_count = ceil($length / $hlen);
		$output = "";
		for($i = 1; $i <= $block_count; $i++) {
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);
			// first iteration
			$last = $xorsum = hash_hmac($algo, $last, $password, true);
			// perform the other $count - 1 iterations
			for ($j = 1; $j < $iterations; $j++) {
				$xorsum ^= ($last = hash_hmac($algo, $last, $password, true));
			}
			$output .= $xorsum;
		}
		if($raw_output)
			return substr($output, 0, $length);
		else
			return substr(bin2hex($output), 0, $length);
	}
}