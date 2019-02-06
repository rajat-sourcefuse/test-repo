<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientContactTelephone;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

    /**
     * Function will validate data before execute create
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
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

    /**
     * Function will validate data before execute delete
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        // fetching telephone data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData();
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objIdDataInDB = $aResp['data']['response'];
        
        if (!empty($objIdDataInDB) && $objIdDataInDB['isPrimary']) {
            throw new SynapExceptions(SynapExceptionConstants::CAN_NOT_DELETE_PRIMARY,
                    400,array('object' => 'patientContactTelephone'));
        }
    }

    /**
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
