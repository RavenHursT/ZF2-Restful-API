<?php
/**
 * File: Lead.php
 * User: matthewmarcus
 * Date: 5/30/13
 * Time: 5:22 PM
 */

namespace Leads\Model;


class Lead {
	private $_id;

	public function __get($arg){
		//TODO: this is nonsense, use __call for methods --mmarcus
		if(method_exists($this, 'get' . ucfirst($arg))){
			$methodName = 'get' . ucfirst($arg);
			return $this->$methodName();
		}

		if(property_exists($this, '_' . $arg)){
			$propName = '_' . $arg;
			return $this->$propName;
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
		return null;
	}

	public function exchangeArray($data)
	{
		$this->_id     = (isset($data['id'])) ? $data['id'] : null;
	}
}