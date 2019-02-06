<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientForm;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for preExecute of PatientForm.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\OMLBundle\DataTypes\Signature;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Utilities\ContextDataUtility;

class PreExecuteM implements PreExecuteI
{

    /**
     * Function will check if key in JSON is of signature type and validate that. If PIN has entered it will pick the original signature and put that
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // Get encounterId from current request (if exist) and set with the passed data
        $encounterObj = ContextDataUtility::getInstance();
        $encounterId = $encounterObj->getEncounterId();
        if (!empty($encounterId)) {
            $data->setData(array('properties' => array('patientEncounterId' => $encounterId)));
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}
