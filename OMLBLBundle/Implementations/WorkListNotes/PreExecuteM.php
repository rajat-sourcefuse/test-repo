<?php

namespace SynapEssentials\OMLBLBundle\Implementations\WorkListNotes;

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
use \SynapEssentials\Utilities\SessionUtility;

/**
 * Description of PostExecuteM
 *
 * @author Sourav Bhargava<sourav.bhargava@sourcefuse.com>
 */
class PreExecuteM implements PreExecuteI
{

    private $obj;
    private $serviceContainer;

    public function __construct()
    {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $sessO = SessionUtility::getInstance();
        $OEID = $sessO->getOrganizationEmployeeId();
        if (isset($OEID) && !empty($OEID)) {
            $updateArr = array("notesBy" => $OEID);
            $data->setData($updateArr, 'properties');
        }
        return true;
    }

    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {    
        return true;
    }

    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}
