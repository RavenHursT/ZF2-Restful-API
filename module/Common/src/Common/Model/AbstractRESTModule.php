<?php

namespace Common\Model;

use Zend\Config\Config;
use Zend\EventManager\Event;
use Zend\ServiceManager\ServiceManager;

abstract class AbstractRESTModule {

	protected
		$_controllerFilePaths = NULL,
		$_routes = NULL,
		$_event = NULL;

	public function __construct(){
	}

	public abstract function getModuleNamespace();

	public abstract function getModuleRootPath();

	public function init(){
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
			$reader = new \Zend\Config\Reader\Ini();
			$additionalModuleConfig = new Config($reader->fromFile($this->getModuleRootPath() . '/config/module.config.ini'));
			$config = $config->merge($additionalModuleConfig);
		}

		return $config->toArray();
	}

	public function getServiceConfig(){
		return array(
			'factories' => array(
				'Log' => function ($sm) {
					$config = $sm->get('Config');
					if(isset($config['log_file_dir'])){
						$logFileDir = $config['log_file_dir'];
					} else {
						$logFileDir = '/tmp';
					}
					$log = new \Zend\Log\Logger();
					$writer = new \Zend\Log\Writer\Stream($config['log_file_dir'] . '/' . $config['application_domain']. '-' . date('Ymd') . '.log');
					$log->addWriter($writer);

					return $log;
				},
			),
		);
	}
}