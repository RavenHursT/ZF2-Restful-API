<?php
/**
 * File: LeadsController.php
 * User: matthewmarcus
 * Date: 5/10/13
 * Time: 1:45 PM
 */

namespace Leads\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;


class LeadsController extends AbstractRestfulController{
	public function getList()
	{
		// If you want to see your current config (i.e. routes, invokables, etc.), uncomment the following:
//		print_r($this->getServiceLocator()->get('Config'));exit;
		die('getList()');
	}

	public function get($id)
	{
		die('getId($id)');
	}

	public function create($data)
	{
		die('create($data)');
	}

	public function update($id, $data)
	{
		die('update($id, $data)');
	}

	public function delete($id)
	{
		die('delete($id)');
	}
}