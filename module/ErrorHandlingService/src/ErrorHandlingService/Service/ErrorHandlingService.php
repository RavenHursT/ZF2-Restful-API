<?php
/**
 * File: ErrorHandlingService.php
 * User: matthewmarcus
 * Date: 6/12/13
 * Time: 5:13 PM
 */

namespace ErrorHandlingService\Service;


use Zend\Log\Logger;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

class ErrorHandlingService
{
	protected $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	public function logEventError(MvcEvent $e)
	{
		$this->logger->err('A ZF2 MVC error event occured => ' . $e->getError());
		return $this;
	}

	/**
	 * This is how the MVC view strategy knows to just render JSON and not go looking for templates.
	 * @param MvcEvent $e
	 * @return JsonModel
	 */
	public function setJsonResult(MvcEvent $e){
		$ret = array(
			'success' => FALSE,
			'statusCode' => $e->getResponse()->getStatusCode(),
			'errorType' => $e->getError(),
		);
		$ex = $e->getResult()->exception;
		if($ex instanceof \Exception){
			$this->logException($ex);
			$ret['exceptionTrace'] = array();
			while ($ex instanceof \Exception){
				$ret['exceptionTrace'][] = array(
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
					'file' => $ex->getFile(),
					'line' => $ex->getLine(),
				);
				$ex = $ex->getPrevious();
			}
		}
		$jsonModel = new JsonModel($ret);
		$e->setResult($jsonModel);
		return $jsonModel;
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