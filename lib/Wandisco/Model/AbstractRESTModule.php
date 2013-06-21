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
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;

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


		$host = $this; // Needed for event listeners.

		//TODO: figure out how the heck to get this working --mmarcus
		$eventManager->attach('dispatch.error', function($e) use ($host){
			if ($ex = $e->getParam('exception')) {
				$service = $host->getServiceManager()->get('Wandisco\Service\ErrorHandling');
				$service->logException($ex);
			}
		});

		//Listener to log response
		$sharedManager->attach('Zend\Mvc\Application', 'finish', function($e) use ($host){
			$host->logResponse($e->getResponse());
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

	public function getControllerFilePaths(){
		if($this->_controllerFilePaths){
			return $this->_controllerFilePaths;
		}
		$moduleSrcPath = APP_ROOT . '/module/' . $this->getModuleNamespace() . '/src/';
		return str_replace('/', '\\', str_replace(array($moduleSrcPath, '.php'), '', glob($moduleSrcPath . $this->getModuleNamespace() . '/Controller/*')));
	}

	public function getRoutes(){
		if($this->_routes){
			return $this->_routes;
		}
		foreach($this->_controllerFilePaths as $filePath){
			if( ($controllerName = $this->_extractControllerNameFromFilePath($filePath) ) != strtolower($this->getModuleNamespace())){
				$routeStr = '/' . strtolower($this->getModuleNamespace()) . '/' . $controllerName . '[/:id][/]';
				$defaultController = ucfirst($this->getModuleNamespace()) . '/Controller/' . ucfirst($controllerName);
			} else {
				$routeStr = '/' .  strtolower($this->getModuleNamespace()) . '[/:id][/]';
				$defaultController = ucfirst($this->getModuleNamespace()) . '/Controller/' . ucfirst($this->getModuleNamespace());
			}
			$this->_routes[$controllerName] = array(
				'type' => 'Zend\Mvc\Router\Http\Segment',
				'options' => array(
					'route' => $routeStr,
					'constraints' => array(
						'id' => '[0-9]+'
					),
					'defaults' => array(
						'controller' => $defaultController
					)
				)
			);
		}
//		print_r($this->_routes);exit;
		return $this->_routes;
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
		$config =  new Config(
			array(
				'controllers' => array(
					'invokables' => $this->_getInvokables()
				),
				'router' => array(
					'routes' => $this->getRoutes()
				),
				'view_manager' => array(
					'strategies' => array(
						'ViewJsonStrategy'
					),
//					'template_path_stack' => array(
//						strtolower($this->getModuleNamespace()) => $this->getModuleRootPath() . '/view',
//					)
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