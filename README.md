PBKDF2
=====

/**

* PBKDF2 加密函数
* 参考标准
* @link https://www.ietf.org/rfc/rfc2898.txt
*
* php官方函数将在php5.5发布
* @see http://php.net/manual/en/function.hash-pbkdf2.php
* @example: pbkdf2("sha256", 'password', 'salt', 1, 20);
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
