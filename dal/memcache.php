<?php
class Webpie_Dal_Memcache extends Webpie_Dal_Cacheabstract
{
	public $setting = NULL;
	private $cacheObj = NULL;
	private $curCacheObj = NULL;
	public function __construct(){}

	public function cacheSetting($setting)
	{
		$this->setting = $setting;
		$cacheObjName = md5(implode('', $this->setting));
		$this->cacheObj[$cacheObjName] = NULL;
		return $cacheObjName;
	}

	public function cacheConnect($name)
	{
		if(!is_object($this->cacheObj[$name]))
		{
			$this->cacheObj[$name] = new Memcached;
			$this->cacheObj[$name]->addServers($this->setting['servers']);

			if(isset($this->setting['options']))
			{
				foreach($this->setting['options'] as $opt)
				{
					if(!$this->cacheObj[$name]->setOption($opt[0], $opt[1]))
						throw new Webpie_Dal_Exception('Dal Cache Error:setOption fail');
				}
			}
		}

		return $this->cacheObj[$name];
	}

	public function setCurCacheObj($obj)
	{
		if(in_array($obj, $this->cacheObj))
			$this->curCacheObj = $obj;
		else
			throw new Webpie_Dal_Exception('Dal Cache Error:You not connect the cache');

		return $this;
		
	}

	public function get($key, $options = NULL)
	{
		$opts = array();
		$opts[] = &$key;

		if(!empty($options['callback']))
			$opts[] = &$options['callback'];

		if(isset($options['cas']))
			$opts[] = &$options['cas'];

		return call_user_func_array(array($this->curCacheObj, 'get'), $opts);
	}

	public function mGet($key)
	{
		return $this->curCacheObj->getMulti($key);
	}

	public function set($key, $val, $exp = NULL)
	{
		return $this->curCacheObj->set($key, $val, intval($exp));
	}

	public function append($key, $val)
	{
		return $this->curCacheObj->append($key, $val);
	}

	public function casToSet($key, $val, $exp = 0, $cas = NULL)
	{
		if(!$cas)
			throw new Webpie_Dal_Exception('Dal Cache Error: var $cas is NULL');

		return $this->curCacheObj->cas($cas, $key, $val, $exp);
	}

	public function decr($key, $offset = 1)
	{
		if($this->curCacheObj->get($key) === false)
			return $this->curCacheObj->set($key, $offset);

		return $this->curCacheObj->decrement($key, $offset);
	}

	public function incr($key, $offset = 1)
	{
		if($this->curCacheObj->get($key) === false)
			return $this->curCacheObj->set($key, $offset);

		return $this->curCacheObj->increment($key, $offset);
	}

	public function del($key)
	{
		if(is_array($key))
		{
			$curCacheObj = &$this->curCacheObj;
			return array_walk($key, function($k) use (&$curCacheObj){$curCacheObj->delete($k);});
		}
		else
			return $this->curCacheObj->delete($key);
	}
}
