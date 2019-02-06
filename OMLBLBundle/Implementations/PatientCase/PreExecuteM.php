<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientCase;

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

    const STATUS_TYPE_ACTIVE = 'metaCaseEpisodeStatusType:active';
    const STATUS_TYPE_INACTIVE = 'metaCaseEpisodeStatusType:inactive';
    const STATUS_TYPE_PENDING = 'metaCaseEpisodeStatusType:pending';

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
        // fetching program's case. if yes then throw exception.
        $searchKey = [];
        $searchKey[0]['type'] = 'patientCase';
        $searchKey[0]['conditions'][] = ['programName' => $data->getData('properties')['programName']];
        $searchKey[0]['conditions'][] = ['patientId' => $data->getData('parent')];
      //  $searchKey[0]['conditions'][] = ['caseStatusType' => self::STATUS_TYPE_ACTIVE];
        $searchKey[0]['outKey'] = 'response';
        $caseResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        
        // A patient can have multiple cases open at one time but only one
        // active case per program.
        if (!empty($caseResp['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::CASE_ALREADY_OPENED,400);
        }

        // set status type
        $statusType = $this->getStatusType($data->getData('properties')['status'], $serviceQue);
        $data->setData(array('properties' => array('caseStatusType' => $statusType)));

    }

    /**
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * This function will get the status type
     * 
     * @param type $statusConfId
     * @param type $serviceQue
     * @return type
     */
    private function getStatusType($statusConfId, $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['objectId'] = $statusConfId;
        $searchKey[0]['outKey'] = 'response';
        $confResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (!empty($confResp['data']['response']['type'])) {
            return $confResp['data']['response']['type'];
        }
    }

    /**
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * This function will get the default status if any for the program
     * 
     * @param type $progId
     * @param type $serviceQue
     * @return type
     */
    private function getDefualtStatus($progId, $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['type'] = 'caseEpisodeStatusConfig';
        $searchKey[0]['conditions'][] = ['programName' => $progId];
        $searchKey[0]['conditions'][] = ['isDefaultStatus' => 1];
        $searchKey[0]['outKey'] = 'response';
        $confResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (!empty($confResp['data']['response'][0]['id'])) {
            return $confResp['data']['response'][0]['id'];
        } else {
            throw new SynapExceptions(SynapExceptionConstants::CONFIGURE_FIRST,400);
        }
    }

    /**
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * This function will get all org status if any for the program
     * 
     * @param type $progId
     * @param type $orgId
     * @param type $serviceQue
     * @return type array
     */
    private function getOrgConfigStatus($progId, $orgId, $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['type'] = 'caseEpisodeStatusConfig';
        $searchKey[0]['conditions'][] = ['programName' => $progId];
        $searchKey[0]['conditions'][] = ['organizationId' => $orgId];
        $searchKey[0]['outKey'] = 'response';
        $confResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $outarr = [];
        if (!empty($confResp['data']['response'])) {
            foreach ($confResp['data']['response'] as $value) {
                $outarr[$value['type']] = $value['id'];
            }
        } else {
            throw new SynapExceptions(SynapExceptionConstants::CONFIGURE_FIRST,400);
        }
        return $outarr;
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
        if (!empty($data->getData('properties')['status'])) {
            // set status type
            $statusType = $this->getStatusType($data->getData('properties')['status'], $serviceQue);
            $data->setData(array('properties' => array('caseStatusType' => $statusType)));
        }
    }

}
