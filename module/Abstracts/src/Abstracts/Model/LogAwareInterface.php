<?php
/**
 * File: LogAwareInterface.php
 * User: matthewmarcus
 * Date: 7/29/13
 * Time: 11:13 AM
 */

namespace Abstracts\Model;


use Zend\Log\Logger;

interface LogAwareInterface {
	public function setLog(Logger $log);
}