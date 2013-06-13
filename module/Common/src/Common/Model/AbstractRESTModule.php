<?php

namespace Common\Model;

use Zend\Config\Config;
use Zend\Config\Reader\Ini;
use Zend\EventManager\Event;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Common\Services\ErrorHandling as ErrorHandlingService;

abstract class AbstractRESTModule {

	protected
		$_controllerFilePaths = NULL,
		$_routes = NULL,
		$_event = NULL;

	public function onBootstrap(MvcEvent $e){
//		$eventManager = $e->getApplication()->getEventManager();
//		$moduleRouteListener = new ModuleRouteListener();
//		$moduleRouteListener->attach($eventManager);
//		/**
//		 * Log any Uncaught Exceptions, including all Exceptions in the stack
//		 */
//		$sharedManager = $e->getApplication()->getEventManager()->getSharedManager();
//		$sm = $e->getApplication()->getServiceManager();
//		$sharedManager->attach('Zend\Mvc\Application', 'dispatch.error',
//			function($e) use ($sm) {
//				die('did we even get here?');
//				if ($e->getParam('exception')){
//					$ex = $e->getParam('exception');
//					do {
//						$sm->get('Log')->crit(
//							sprintf(
//								"%s:%d %s (%d) [%s]\n",
//								$ex->getFile(),
//								$ex->getLine(),
//								$ex->getMessage(),
//								$ex->getCode(),
//								get_class($ex)
//							)
//						);
//					}
//					while($ex = $ex->getPrevious());
//				}
//			}
//		);


//		print_r($sharedManager->getListeners('Zend\Mvc\Application', 'dispatch.error'));

		$application = $e->getTarget();
//		print_r(get_class($application->getEventManager()));exit;
		$eventManager = $application->getEventManager();
		$services = $application->getServiceManager();
		$listener = $eventManager->attach('dispatch.error', function ($event) use ($services) {
			$exception = $event->getResult()->exception;
			if (!$exception) {
				return;
			}
			$service = $services->get('Application\Service\ErrorHandling');
			$service->logException($exception);
		});
		print_r($listener);
	}

	public abstract function getModuleNamespace();

	public abstract function getModuleRootPath();

	public function init(){
//		$newSL = new ServiceListener('hi');
		$this->_controllerFilePaths = $this->getControllerFilePaths();
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
					'template_path_stack' => array(
						'album' => APP_ROOT . 	'/module/Album/config/../view'
					)
				)
			)
		);

		if(file_exists($this->getModuleRootPath() . '/config/module.config.ini')){
			$reader = new Ini();
			$additionalModuleConfig = new Config($reader->fromFile($this->getModuleRootPath() . '/config/module.config.ini'));
			$config = $config->merge($additionalModuleConfig);
		}

		return $config->toArray();
	}

	public function getServiceConfig(){
		return array(
			'factories' => array(
				'Application\Service\ErrorHandling' =>  function($sm) {
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
					$log = new Logger();
					$writer = new Stream($config['log_file_dir'] . '/' . $config['application_domain']. '-' . date('Ymd') . '.log');
					$log->addWriter($writer);

					return $log;
				},
			),
		);
	}
}