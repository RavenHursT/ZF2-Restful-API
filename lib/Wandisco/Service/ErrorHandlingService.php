<?php
/**
 * File: ErrorHandlingService.php
 * User: matthewmarcus
 * Date: 6/12/13
 * Time: 5:13 PM
 */

namespace Wandisco\Service;


use Zend\Mvc\MvcEvent;

class ErrorHandlingService
{
	protected $logger;

	public function __construct($logger)
	{
		$this->logger = $logger;
	}

	public function logEventError(MvcEvent $e)
	{
		$this->logger->err('A ZF2 MVC error event occured => ' . $e->getError());
	}

	public function logGenericError($errorString){

	}

	public function logException(\Exception $e){
		$trace = $e->getTraceAsString();
		$i = 1;
		do {
			$messages[] = $i++ . ": " . $e->getMessage();
		} while ($e = $e->getPrevious());

		$log = "Exception:\n" . implode("\n", $messages);
		$log .= "\nTrace:\n" . $trace;

//		echo $log; exit;
		$this->logger->err($log);
	}
}