<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientDocument;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \SynapEssentials\WorkFlowBundle\ResponseLibrary\communicationCenter;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\OMLBundle\Services\ServiceQue;
/**
 * BL class for postExecute of WorkList.
 *
 * @author sourav Bhargava <sourav.bhargava@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
 private $obj;
    private $serviceContainer;

    public function __construct()
    {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function postExecuteGet($data, ServiceQueI $serviceQue) {
      
        return true;
    }

    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

}