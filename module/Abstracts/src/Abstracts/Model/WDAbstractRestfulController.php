<?php
/**
 * File: WDAbstractRestfulController.php
 * User: matthewmarcus
 * Date: 7/29/13
 * Time: 11:20 AM
 */

namespace Abstracts\Model;


use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractRestfulController;

abstract class WDAbstractRestfulController extends AbstractRestfulController implements LogAwareInterface{
	protected
		$_log = NULL;

	public function setLog(Logger $log){
		$this->_log = $log;
		return $this;
	}

}