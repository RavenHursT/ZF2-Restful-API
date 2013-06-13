<?php
/**
 * File: ErrorHandlingService.php
 * User: matthewmarcus
 * Date: 6/12/13
 * Time: 5:13 PM
 */

namespace Wandisco\Service;


class ErrorHandlingService
{
	protected $logger;

	function __construct($logger)
	{
		$this->logger = $logger;
	}

	function logError(\Exception $e)
	{
		$trace = $e->getTraceAsString();
		$i = 1;
		do {
			$messages[] = $i++ . ": " . $e->getMessage();
		} while ($e = $e->getPrevious());

		$log = "Exception:\n" . implode("\n", $messages);
		$log .= "\nTrace:\n" . $trace;

		echo $log;
		$this->logger->err($log);
	}
}