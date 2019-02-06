<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientMedication;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\DrFirstBundle\Managers\UploadDataManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\OMLBLBundle\Implementations\Utility\Utility;

/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions  
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $utilityInstance = new Utility();
        // If pateint is eligible to be created on dr first
        if ($utilityInstance->isPatientCreatedForDrFirst($data->getData('properties')['patientId'])) {
            //get service container
            $configurator = Configurator::getInstance();
            $properties = $data->getData('properties');
            //get class object
            $drFirstUploadManager = new UploadDataManager($configurator->getServiceContainer());
            $data = $data->getAll();
            $drFirstUploadManager->submitPatientMedication($data);
        }
        return true;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * @description function will perform some actions after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
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
    public function postExecuteGet($data, ServiceQueI $serviceQue)
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
    public function postExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}
