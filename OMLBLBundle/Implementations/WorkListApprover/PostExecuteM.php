<?php

namespace SynapEssentials\OMLBLBundle\Implementations\WorkListApprover;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use \SynapEssentials\OMLBundle\Services\ServiceQue;
use \SynapEssentials\WorkFlowBundle\utilities\workListApproverUtility;
use \SynapEssentials\WorkFlowBundle\utilities\workListUtility;
use \SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;

/**
 * Description of PostExecuteM
 *
 * @author Sourav Bhargava<sourav.bhargava@sourcefuse.com>
 */
class PostExecuteM implements PostExecuteI {

    private $obj;
    private $serviceContainer;

    public function __construct() {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
//        \Doctrine\Common\Util\Debug::dump($data->getAll());
//        exit;
    }

    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        
    }

    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        
    }

    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        $dat = $data->getAll();
        if (isset($dat['properties']['approvalStatus']) && (($dat['properties']['approvalStatus'] == 'metaWorkListApprovalStatus:APPROVED') || ($dat['properties']['approvalStatus'] == 'metaWorkListApprovalStatus:REJECTED'))) {
            $searchKey = [];
            $searchKey[0]['objectId'] = $dat['conditions']['object'];
            $searchKey[0]['outKey'] = 'response';
            $resp = $this->obj->executeQue("ws_oml_read", $searchKey);
            $WLAUO = new workListApproverUtility($this->serviceContainer);
            $WLUO = new workListUtility($this->serviceContainer);
            if($dat['properties']['approvalStatus'] == 'metaWorkListApprovalStatus:APPROVED'){
                $WLUO->completeWork($resp["data"]["response"]['workListId']);
                 if (isset($resp["data"]["response"]["workListIdEzcExecutionId"])) {
                    $exMO = ExecutionManager::getInstance($this->serviceContainer);
                    $resp = $exMO->resumeWorkflow($resp["data"]["response"]["workListIdEzcExecutionId"]);
                }
            }
        }
        return true;
    }

    public function postExecuteUpdate2($data, ServiceQueI $serviceQue) {
        return true;
        $dat = $data->getAll();
        //  if (count($dat['conditions']['object']) == 1) {
        $passedWL = array();
        $failedWL = array();
        $searchKey = [];
        $searchKey[0]['objectId'] = $dat['conditions']['object'];
        $searchKey[0]['outKey'] = 'response';
        $resp = $this->obj->executeQue("ws_oml_read", $searchKey);
        $WLAUO = new workListApproverUtility($this->serviceContainer);
        $WLUO = new workListUtility($this->serviceContainer);
        if (!empty($resp["data"]["response"])) {
            if ($resp["data"]['count']['response'] < 2) {
                $r = $resp["data"]["response"];
                $resp["data"]["response"] = [];
                $resp["data"]["response"][0] = $r;
            }

            $obj = $dat['conditions']['object'][0];
            if ($obj == $resp["data"]["response"][0]['id'] && !in_array($resp["data"]["response"][0]['workListId'], $passedWL) && !in_array($resp["data"]["response"][0]['workListId'], $failedWL)) {
                //if wlId not in passed List/failed List then enterHere
                $apprResp = $WLAUO->checkApprovalProcessComplete($resp["data"]["response"][0]['workListId']);
                if ($apprResp == true) {

                    $passedWL[] = $resp["data"]["response"][0]['workListId'];
                } else {
                    $failedWL[] = $resp["data"]["response"][0]['workListId'];
                }
                if (isset($dat['properties']['approvalStatus']) && ($dat['properties']['approvalStatus'] == 'metaWorkListApprovalStatus:APPROVED')) {

                    //approvalStatus  has been marked mark the next Level as pending
                    //$WLAUO->markNextPending($resp["data"]["response"][0]['workListId'], $obj);
                    // if ($WLAUO->checkApprovalProcessComplete($resp["data"]["response"][0]['workListId'])) {
//mark workList complete
                    $WLUO->completeWork($resp["data"]["response"][0]['workListId']);


                    //  }
                } elseif (isset($dat['properties']['approvalStatus']) && ($dat['properties']['approvalStatus'] == 'metaWorkListApprovalStatus:REJECTED')) {
                    $WLUO->preApprovalWork($resp["data"]["response"][0]['workListId']);
                }

                if (isset($resp["data"]["response"][0]["workListIdEzcExecutionId"])) {
                    $exMO = ExecutionManager::getInstance($this->serviceContainer);
                    $resp = $exMO->resumeWorkflow($resp["data"]["response"][0]["workListIdEzcExecutionId"]);
                }
            }
        }
    }

    public function postExecuteView($data, ServiceQueI $serviceQue) {
        
    }

//put your code here
}
