<?php
/**
 * File: Album.php
 * User: matthewmarcus
 * Date: 5/30/13
 * Time: 5:22 PM
 */

namespace Album\Model;


class Album {
	private $_id;
	private $_artist;
	private $_title;

	public function __get($arg){
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
		$this->_artist = (isset($data['artist'])) ? $data['artist'] : null;
		$this->_title  = (isset($data['title'])) ? $data['title'] : null;
	}
}