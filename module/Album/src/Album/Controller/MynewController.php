<?php
/**
 * File: AlbumController.php
 * User: matthewmarcus
 * Date: 5/10/13
 * Time: 1:45 PM
 */

namespace Album\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;


class MynewController extends AbstractRestfulController{
	public function getList()
	{
		die('MynewController::getList()');
	}

	public function get($id)
	{
		die('MynewController::getId($id)');
	}

	public function create($data)
	{
		die('MynewController::create($data)');
	}

	public function update($id, $data)
	{
		die('MynewController::update($id, $data)');
	}

	public function delete($id)
	{
		die('MynewController::delete($id)');
	}
}