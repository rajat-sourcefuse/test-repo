<?php

namespace SynapEssentials\OMLBLBundle\Implementations\WorkListApprover;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use \SynapEssentials\OMLBundle\Services\ServiceQue;
use \SynapEssentials\WorkFlowBundle\utilities\workListApproverUtility;
use \SynapEssentials\WorkFlowBundle\utilities\workListUtility;
use \SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;

/**
 * Description of PreExecuteM
 *
 * @author Sourav Bhargava<sourav.bhargava@sourcefuse.com>
 */
class PreExecuteM implements PreExecuteI {

    private $obj;
    private $serviceContainer;

    public function __construct() {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    public function preExecuteCreate($data, ServiceQueI $serviceQue) {

        return true;
    }

    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
return true;
//        $dat = $data->getAll();
//        $WLAUO = new workListApproverUtility($this->serviceContainer);
//        $WLUO = new workListUtility($this->serviceContainer);
//        $searchKey = [];
//        $searchKey[0]['objectId'] = $dat['conditions']['object'][0];
//        $searchKey[0]['outKey'] = 'response';
//
//        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
//
//        $workListId = $resp['data']['response']['workListId'];
//        $searchKey = [];
//        $searchKey[0]['objectId'] = $workListId;
//        $searchKey[0]['type'] = 'workListApprover';
//        $searchKey[0]['outKey'] = 'response';
//
//        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
//        $approvalId = array();
//        foreach ($resp['data']['response'] as $r) {
//            if ($r['id'] != $dat['conditions']['object'][0]) {
//                $approvalId[] = $r['id'];
//            }
//        }
//
//        $data->setData(array("object" => $approvalId), 'conditions');
      
       // return true;
    }

    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

}
