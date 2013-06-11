<?php

namespace Application\Model;

abstract class AbstractRESTModule {

	protected
		$_controllerFilePaths = NULL,
		$_routes = NULL;

	public function __construct(){
	}

	public abstract function getModuleNamespace();

	public abstract function getModuleRootPath();

	public function init(){
		$this->_controllerFilePaths = $this->getControllerFilePaths();
	}

	public function getAutoLoaderConfig() {
		return array(
			//TODO:: Make this find out if module has autoload_classmap.php
//			'Zend\Loader\ClassMapAutoloader' => array(
//				$this->getModuleRootPath() . '/autoload_classmap.php',
//			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					$this->getModuleNamespace() => $this->getModuleRootPath() . '/src/' . $this->getModuleNamespace()
				)
			)
		);
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
		return array(
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
		);
	}
}