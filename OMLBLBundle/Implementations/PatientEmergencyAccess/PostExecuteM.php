<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientEmergencyAccess;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

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
        $today = new \DateTime();
        $sessionUtil = \SynapEssentials\Utilities\SessionUtility::getInstance();
        
        $objectData['parent']  = $data->getData('parent');
        $objectData['properties']['informationType']  = 'All';
        $objectData['properties']['informationReleaseTo']  = 'session';
        $objectData['properties']['informationReleaseToSession']  =
                $sessionUtil->getSessionId();
        $objectData['properties']['releaseDateFrom']  = $today->format(DateTimeUtility::DATE_FORMAT);
        $objectData['properties']['releaseDateTo']  =
                $today->modify('+1 day')->format(DateTimeUtility::DATE_FORMAT);
        $objectData['properties']['releasePurpose']  = 'HealthCare';
        $objectData['objectType'] = 'patientReleaseOfInformation';
        $resp = $serviceQue->executeQue('ws_oml_create', $objectData);
                
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

}
