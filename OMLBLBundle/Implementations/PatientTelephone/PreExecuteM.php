<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientTelephone;

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
    private static $totalPrimaryCount = 0;
    private static $dbCount = 0;
    private $ParentArr = [];
    private $dbTelephoneArr = [];

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
        
        $patientId = $data->getData()['parent'];
        
        if (isset($data->getData('properties')['isPrimary']) &&
                $data->getData('properties')['isPrimary']) {
            if (!array_key_exists($patientId, $this->ParentArr)) {
                self::$totalPrimaryCount += 1;
            }
        }
        $this->ParentArr[$patientId] = self::$totalPrimaryCount;
        
        // fetching telephone data for a patient from db
        $searchKey = [];
        $searchKey[0]['type'] = 'patientTelephone';
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['conditions'][] = ['isPrimary'=>1];
        $tResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $telephoneDataInDB = $tResp['data']['response'];
        $this->dbTelephoneArr[self::$dbCount] = $telephoneDataInDB;
        self::$dbCount += 1;
        
        if (!empty($this->dbTelephoneArr[0])) {
            if ($this->ParentArr[$patientId] == 1) {
                throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY,
                  400,  array('object' => 'patientTelephone'));
            }
        } else {
            if ($this->ParentArr[$patientId] == 0) {
                $updateArr = array("isPrimary" => true);
                $data->setData($updateArr, 'properties');
            }
        }
        
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
        $teleDataInDB = $aResp['data']['response'];
        
        if (!empty($teleDataInDB) && $teleDataInDB['isPrimary']) {
            throw new SynapExceptions(SynapExceptionConstants::CAN_NOT_DELETE_PRIMARY,
                400,    array('object' => 'patientTelephone'));
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
