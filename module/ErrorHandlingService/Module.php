<?php

namespace ErrorHandlingService;

use EventLogger\Service\EventLogger;
use ErrorHandlingService\Service\ErrorHandlingService;
use Zend\EventManager\Event;
use Zend\Feed\Reader\Reader;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ResponseSender\SendResponseEvent;

class Module implements AutoloaderProviderInterface{
	protected
		$_log = NULL;

	public function onBootstrap(Event $e){
		$eventManager = $e->getApplication()->getEventManager();

		$this->setLog($e->getApplication()
			->getServiceManager()
			->get('EventLogger\Service\EventLogger')
		);

		$this->logRequest($e->getRequest());

		$currentModule = $this; // Needed for event listeners.

		$eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function(MvcEvent $e){
			if ($e->isError()) {

				$jsonModel = $e->getApplication()
					->getServiceManager()
					->get('ErrorHandlingService\Service\ErrorHandling')
					->logEventError($e)
					->setJsonResult($e);

				return $jsonModel;

			} else {
				//don't do anything, just let Zend handle it.
				return;
			}
		});

		//Listener to log response
		$eventManager->attach(MvcEvent::EVENT_FINISH, function($e) use ($currentModule){
			$currentModule->logResponse($e->getResponse());
		});
	}

	public function logRequest(Request $request){
		$this->getLog()->info('Incoming request => ' . $request->__toString());
		return $this;
	}

	public function logResponse(Response $response){
		$this->getLog()->info("Outgoing response => " . $response->__toString());
		return $this;
	}

	public function setLog(Logger $log){
		$this->_log = $log;
		return $this;
	}

	public function getLog(){
		return $this->_log;
	}

	public function getServiceConfig(){
		return array(
			'factories' => array(
				'ErrorHandlingService\Service\ErrorHandling' =>  function($sm) {
					$logger = $sm->get('EventLogger\Service\EventLogger');
					$service = new ErrorHandlingService($logger);
					return $service;
				}
			),
		);
	}

	/**
	 * Get Config
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}

	/**
	 * Set Autoloader Configuration
	 *
	 * @return array
	 */
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php',
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}
}