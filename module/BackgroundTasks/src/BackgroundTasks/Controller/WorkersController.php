<?php
/**
 * File: LeadsController.php
 * User: matthewmarcus
 * Date: 5/10/13
 * Time: 1:45 PM
 */

namespace BackgroundTasks\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\JsonModel;


class WorkersController extends AbstractRestfulController{
	public function getList()
	{
		$this->getServiceLocator()->get('Log')->info('LeadsController::getList()');
		// If you want to see your current config (i.e. routes, invokables, etc.), uncomment the following:
//		print_r($this->getServiceLocator()->get('Config'));exit;
//		die('getList()');
		return new JsonModel(array(
			'data' => array(
				'someString' => 'Foo',
				'someInt' => 10,
				'someBool' => false
			)
		));
	}

	public function get($id)
	{
//		die('getId($id)');
	}

	public function create($data)
	{
		if(!isset($data['type']) || empty($data['type'])){
			throw new \Exception('Could not dispatch new worker. Type could not be found, or not given.');
		} else {
			return new JsonModel($data);
		}
	}

	public function update($id, $data)
	{
//		die('update($id, $data)');
	}

	public function delete($id)
	{
//		die('delete($id)');
	}
}