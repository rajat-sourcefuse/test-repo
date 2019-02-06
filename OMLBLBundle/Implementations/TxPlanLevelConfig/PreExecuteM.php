<?php

namespace SynapEssentials\OMLBLBundle\Implementations\TxPlanLevelConfig;

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
        //print_r($data->getData());exit;
        $parentId = $data->getData('parent');
        $searchKey = [];
        $searchKey[0]['objectId'] = $parentId;
        $searchKey[0]['type'] = 'txPlanLevelConfig';
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $txLevelConfig = $resp['data']['response'];
        
        if (!empty($txLevelConfig)) {
            // only 4 levels can be added
            if (count($txLevelConfig) == 4) {
                throw new SynapExceptions(SynapExceptionConstants::TX_MORE_CONFIG_LEVEL,400);
            }
            
            $levelOrderArr = [];
            for ($i=0; $i< count($txLevelConfig); $i++) {
                // creating $levelOrderArr of level order from db
                if (!in_array($txLevelConfig[$i]['levelOrder'], $levelOrderArr)) {
                    $levelOrderArr[] = $txLevelConfig[$i]['levelOrder'];
                } else {
                    throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_ORDER,400);
                }
            }
            // level order can not be same
            if (in_array($data->getData('properties')['levelOrder'], $levelOrderArr)) {
                throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_ORDER,400);
            }
        }

        $searchKey1 = [];
        $searchKey1[0]['objectId'] = $parentId;
        $searchKey1[0]['outKey'] = 'response';
        $resp1 = $serviceQue->executeQue("ws_oml_read", $searchKey1);
        $txConfig = $resp1['data']['response'];
        if (!empty($txConfig)) {
            //if count of program Id's in config == 1 then showProgram checkbox will not
            //come with any level. we will make it default false
            if (count($txConfig['programId'] > 1)) {
                //if count of program Id's in config > 1 then showProgram checkbox will
                //come with only level 1. we will make others default false
                if ($data->getData('properties')['levelOrder'] == 1) {
                    if (!isset($data->getData('properties')['showProgram'])
                            || !$data->getData('properties')['showProgram']) {
                        $updateArr = array("showProgram" => false);
                        $data->setData($updateArr, 'properties');
                        //throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_SHOW_PROG);
                    }
                } else {
                    $updateArr = array("showProgram" => false);
                    $data->setData($updateArr, 'properties');
                }
            }
        }
        
        // mapin from should come if showAssessedNeed true
        if (isset($data->getData('properties')['showAssessedNeed'])) {
            if ($data->getData('properties')['showAssessedNeed'] &&
                    empty($data->getData('properties')['mapInFrom'])) {
                throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_MAPIN,400);
            }
        }
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
        return true;
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
