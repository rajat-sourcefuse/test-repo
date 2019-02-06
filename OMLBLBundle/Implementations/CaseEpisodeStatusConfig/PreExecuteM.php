<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CaseEpisodeStatusConfig;

use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
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

    const SELF_OBJ = 'caseEpisodeStatusConfig';

    /**
     * function will validate data before execute create
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $isDefault = false;
        
        // only if isDefaultStatus is true
        if (!empty($data->getData('properties')['isDefaultStatus'])) {
            foreach ($data->getData('properties')['programName'] as $key => $value) {
                $searchKey = [];
                $searchKey[0]['type'] = self::SELF_OBJ;
                $searchKey[0]['conditions'][] = ['programName' => $value];
                $searchKey[0]['conditions'][] = ['organizationId' => $data->getData('parent')];
                $searchKey[0]['conditions'][] = ['isDefaultStatus' => 1];
                $searchKey[0]['outKey'] = 'response';
                $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                if (!empty($resp['data']['response'])) {
                    $isDefault = true;
                }
            }
        }

        // only one default status per program.
        if ($isDefault) {
            throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_DEFAULT,400);
        }
    }

    /**
     * @description function will validate data before execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
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

    /**
     * @description function will validate data before execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
