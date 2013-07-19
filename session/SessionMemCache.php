<?php

/**
 * session 集存中放memcache扩展
 * 如果使用ZlibMemCache扩展，则把本类的继承改成ZlibMemCache
 * 可以指定多个memcache
 * @author Terry
 *
 * 使用方法：
 * 配置文件中，在preload中增加sessionCache,如'preload'=>array('log','sessionCache'),
 * 然后在配置文件的components中增加如下内容
 * 'sessionCache'=>array(
 *  	'class'=>'ext.session.SessionMemcache',
 *  		'sessionExpire'=>180, // default is 180 minutes
 *  		'servers'=>array(
 *  		array('host'=>'localhost','port'=>'11211'),
 * 			array('host'=>'localhost','port'=>'11212','persistent'=>'1','weight'=>'1','timeout'=>'1','retryInterval'=>'15'),
 *  		)
 *  	),
 */
class SessionMemCache extends CMemCache
{
	/**
	 * 是否先测试一下cache的连通，用于测试
	 */
	public $tryConnect = false;
	/**
	 * session有效期，单位为分钟
	 * @var int
	 */
	public $sessionExpire = 180;
	
	public function init()
	{
		$servers = $this->getServers();
		$cache = $this->tryConnect ? $this->getMemCache() : null;
		
		if (!count($servers))
			$servers[] = new CMemCacheServerConfiguration(array('host'=>'localhost','port'=>'11211'));
		$memcachePath = array();
		foreach ($servers as $k => $server)
		{
			if ($this->tryConnect)
			{
				if ($this->useMemcached)
				{
					$cache->addServer($server->host,$server->port,$server->weight);
					$cache->setByKey($server->host.':'.$server->port, 'SessionMemcache_Test', 'test',10);
					if ($cache->getByKey($server->host.':'.$server->port, 'SessionMemcache_Test') == false)
						throw new CException(Yii::t('yii','SessionMemCache Server '.$server->host.':'.$server->port.' failed with: Connection refused.'));
				}
				else
				{
					$cache->connect($server->host, $server->port);
				}
			}
			
			if ($this->useMemcached)
				$memcachePath[$k] = $server->host . ':' . $server->port;
			else
				$memcachePath[$k] = 'tcp://' . $server->host . ':' . $server->port;
			
			$memcachePath[$k] .= '?persistent='.$server->persistent . '&weight='.$server->weight . '&timeout='.$server->timeout . '&retry_interval='.$server->retryInterval;
		}
		$memcachePath = implode(',', $memcachePath);
		if ($this->useMemcached)
			ini_set('session.save_handler', 'memcached');
		else
			ini_set('session.save_handler', 'memcache');
		
		ini_set('session.save_path', $memcachePath);
		// session有效期,分钟
		session_cache_expire($this->sessionExpire);
	}
	
}