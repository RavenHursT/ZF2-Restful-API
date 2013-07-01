<?php

namespace Abstracts\Model;

use Zend\Stdlib\Exception\BadMethodCallException;

abstract class AbstractBaseModel {
	public function __call($called, $args){
		if(strpos($called, 'get') === 0 || strpos($called, 'set') === 0){
			$context = substr($called, 0, 3);
			$propName = lcfirst(str_replace($context, NULL, $called));
			if(property_exists($this, $propName) || property_exists($this, '_' . $propName)){
					$propName = (property_exists($this, $propName)) ? $propName : '_' . $propName;
			} else {
				throw new \Exception('Undefined method property ' . get_class($this) . '::' . $propName);
			}
			if($context == 'get'){
				return $this->$propName;
			} elseif($context == 'set') {
				if(!isset($args[0])){
					throw new \Exception(get_class($this) . '::' . $called . ' requires arguments, none given.');
				}
				$this->$propName = $args[0];
				return $this;
			}
		}
		if($called != 'onBootstrap'){
			throw new BadMethodCallException('Call to non-existent method ' . get_class($this) . '::' . $called);
		}
	}
}