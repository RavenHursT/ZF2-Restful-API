<?php

namespace Wandisco\Model;

use Wandisco\Model\Log\WandiscoLogger;
use Wandisco\Service\ErrorHandlingService;
use Zend\Config\Config;
use Zend\Config\Reader\Ini;
use Zend\EventManager\Event;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\JsonModel;

abstract class AbstractRESTModule extends AbstractBaseModel {

	protected
		$_controllerFilePaths = NULL,
		$_routes = NULL,
		$_event = NULL,
		$_log = NULL,
		$_serviceManager = NULL;

	public function onBootstrap(Event $e){
//		print_r($e->getRequest()->__toString()); exit;
		$this->_event = $e;

		$eventManager = $e->getApplication()->getEventManager();
		$sharedManager = $eventManager->getSharedManager();

		$this->setServiceManager($e->getApplication()->getServiceManager());
		$this->setLog($this->getServiceManager()->get('Log'));
		$this->logRequest($e->getRequest());


		$currentModule = $this; // Needed for event listeners.

		$this->_event
			->getApplication()
			->getEventManager()
			->attach(MvcEvent::EVENT_DISPATCH_ERROR, function(MvcEvent $e){
				if ($e->isError()) {
					$e->getApplication()
						->getServiceManager()
						->get('Wandisco\Service\ErrorHandling')
						->logEventError($e);
					$jsonModel = new JsonModel(array(
						'success' => FALSE,
						'statusCode' => $e->getResponse()->getStatusCode(),
						'errorMessage' => $e->getError()
					));
					$e->setResult($jsonModel);
//					$e->stopPropagation();
					return $jsonModel;
				} else {
					//don't do anything, just let Zend handle it.
					return;
				}
		});

		//Listener to log response
		$sharedManager->attach('Zend\Mvc\Application', 'finish', function($e) use ($currentModule){
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

	public function getModuleNamespace(){
		$classBits = explode('\\', get_class($this));
		array_pop($classBits);
		return implode('\\', $classBits);
	}

	public abstract function getModuleRootPath();

	public function init(){
		$this->_controllerFilePaths = $this->getControllerFilePaths();
//		print_r($this->_controllerFilePaths);exit;
	}

	public function getAutoLoaderConfig() {
		$config = array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					$this->getModuleNamespace() => $this->getModuleRootPath() . '/src/' . $this->getModuleNamespace()
				)
			)
		);

		if(file_exists($this->getModuleRootPath() . '/autoload_classmap.php')){
			$config['Zend\Loader\ClassMapAutoloader'] = array(
				$this->getModuleRootPath() . '/autoload_classmap.php'
			);
		}

		return $config;
	}

	private function _getInvokables(){

		$invokables = array();
		foreach($this->_controllerFilePaths as $controllerFileName){
			$invokables[preg_replace('/Controller$/', '', $controllerFileName)] = $controllerFileName;
		}
		return $invokables;
	}

	/**
	 * TODO: This needs to be cleaned up.  Seperate out controller names from file paths.  Doesn't make much sense as-is --Mmarcus
	 * @return Array of controller names.
	 * @throws \ErrorException
	 */
	public function getControllerFilePaths(){
		if($this->_controllerFilePaths){
			return $this->_controllerFilePaths;
		}
		$moduleSrcPath = APP_ROOT . '/module/' . $this->getModuleNamespace() . '/src/';
		$filePaths = glob($moduleSrcPath . $this->getModuleNamespace() . '/Controller/*');
		if(!$filePaths || !count($filePaths)){
			throw new \ErrorException("No controller files found for module.  Cannot build module routes.");
		}
		return str_replace('/', '\\', str_replace(array($moduleSrcPath, '.php'), '', $filePaths));
	}

	public function getRoutes(){
		if($this->_routes){
			return $this->_routes;
		}
		foreach($this->_controllerFilePaths as $filePath){
			$defaultController = $this->getDefaultController($filePath);
			$this->_routes[$this->_extractControllerNameFromFilePath($filePath)] = $this->getControllerRoute($this->_extractControllerNameFromFilePath($filePath));
		}
//		print_r($this->_routes);exit;
		return $this->_routes;
	}

	protected function getControllerRoute($controllerName, $type = 'Zend\Mvc\Router\Http\Segment'){
		return array(
			'type' => $type,
			'options' => array(
				'route' => $this->_getRouteString($controllerName),
				'constraints' => array(
					'id' => '[0-9]+'
				),
				'defaults' => array(
					'controller' => $this->getDefaultController()
				)
			)
		);
	}

	protected function _getRouteString($controllerName = NULL){
		return (strtolower($controllerName) == 'index') ?
			'/' .  strtolower($this->getModuleNamespace()) . '[/:id][/]' :
			'/' . strtolower($this->getModuleNamespace()) . '/' . $controllerName . '[/:id][/]';
	}

	public function getDefaultController(){
		if(array_search($this->getModuleNamespace() . '\\Controller\\IndexController', $this->getControllerFilePaths())){
			return ucfirst($this->getModuleNamespace()) . '\Controller\Index';
		} else {
			$firstController = $this->_extractControllerNameFromFilePath($this->_controllerFilePaths[0]);
			return ucfirst($this->getModuleNamespace()) . '\Controller\\' . ucfirst($firstController);
		}
	}
	
	private function _extractControllerNameFromFilePath($filePath){
		return strtolower(
			str_replace(
				array(
					ucfirst($this->getModuleNamespace()) . '\Controller\\',
					'Controller'
				),
				'',
				$filePath)
		);
	}

	public function getConfig() {
//		print_r($this->getRoutes());
		$config =  new Config(
			array(
				'controllers' => array(
					'invokables' => $this->_getInvokables()
				),
				'router' => array(
					'routes' => $this->getRoutes()
				),
				'view_manager' => array(
					'display_not_found_reason' => true,
					'display_exceptions'       => true,
					'doctype'                  => 'application/json',
					'strategies' => array(
						'ViewJsonStrategy'
					),
					'template_path_stack' => array(
						strtolower($this->getModuleNamespace()) => $this->getModuleRootPath() . '/view',
					)
				)
			)
		);

		if(file_exists($this->getModuleRootPath() . '/config/module.config.ini')){
			$reader = new Ini();
//			echo print_r($reader->fromFile($this->getModuleRootPath() . '/config/module.config.ini'), TRUE);exit;
			$additionalModuleConfig = new Config($reader->fromFile($this->getModuleRootPath() . '/config/module.config.ini'));
			$config = $config->merge($additionalModuleConfig);
		}
		return $config;
	}

	public function getServiceConfig(){
		return array(
			'factories' => array(
				'Wandisco\Service\ErrorHandling' =>  function($sm) {
					$logger = $sm->get('Log');
					$service = new ErrorHandlingService($logger);
					return $service;
				},
				'Log' => function ($sm) {
					$config = $sm->get('Config');
					if(isset($config['log_file_dir'])){
						$logFileDir = $config['log_file_dir'];
					} else {
						$logFileDir = '/tmp';
					}
					$log = new WandiscoLogger();
					$writer = new Stream($logFileDir . '/' . $config['application_domain']. '-' . date('Ymd') . '.log');
					$writer->setFormatter(new Simple('%timestamp% %priorityName% (%priority%) [f=%requestFingerprint%] [%class%::%function%]: %message% %extra%', 'c'));
					$log->addWriter($writer);
//					Logger::registerErrorHandler($log);
//					Logger::registerExceptionHandler($log);

					return $log;
				},
			),
		);
	}
}