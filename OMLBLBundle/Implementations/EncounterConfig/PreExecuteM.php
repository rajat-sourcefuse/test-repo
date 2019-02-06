<?php

namespace SynapEssentials\OMLBLBundle\Implementations\EncounterConfig;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

class PreExecuteM implements PreExecuteI
{

    /**
     * Function will ensure that a maximum of 4 diagnosis codes passed.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {   
        return true;
    }

    /**
     * Function will validate data before executing update.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before deletion
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before execute get
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
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}
