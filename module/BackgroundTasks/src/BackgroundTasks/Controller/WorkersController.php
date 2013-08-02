<?php
/**
 * File: LeadsController.php
 * User: matthewmarcus
 * Date: 5/10/13
 * Time: 1:45 PM
 */

namespace BackgroundTasks\Controller;

use Abstracts\Model\WDAbstractRestfulController;
use Zend\Http\Header\ContentType;
use Zend\Http\Response;
use Zend\Log\Logger;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\JsonModel;


class WorkersController extends WDAbstractRestfulController{

//	public function onDispatch(MvcEvent $e){
//		$this->setLog($this->getServiceLocator()->get('EventLogger\Service\EventLogger'));
//		print_r($this->getEventManager()->getEvents());exit;
//		return parent::onDispatch($e);
//	}

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
		}
		$workerServiceName = '\BackgroundTasks\Workers\\' . ucfirst($data['type']);

		$this->_log->info('Spinning up new worker of class => ' . $workerServiceName);
		$workerService = $this->getServiceLocator()->get($workerServiceName);
		$workerService->init();

		$this->_log->info(get_class($workerService) . ' type worker spun up w/ ID => ' . $workerService->getWorker()->getId());

		$responseData = new JsonModel(array(
			'success' => TRUE,
			'worker_id' => $workerService->getWorker()->getId()
		));

		$response = new Response();
		$response->setStatusCode(201)
			->getHeaders()->addHeaders(array(
				ContentType::fromString('Content-Type: application/json'),
			));

		$response->setContent($responseData->serialize());
		return $response;
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