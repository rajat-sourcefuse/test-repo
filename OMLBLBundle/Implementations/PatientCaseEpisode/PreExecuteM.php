<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientCaseEpisode;

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
        // fetching case's all open episode. if yes then throw exception.
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('parent');
        $searchKey[0]['type'] = 'patientCaseEpisode';
        $searchKey[0]['outKey'] = 'response';
        $epiResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        // A patient can have multiple episode open at one time but only one
        // active episode per program.
        if (!empty($epiResp['data']['response']) &&
                $epiResp['data']['response'][0]['statusType'] == self::STATUS_TYPE_ACTIVE) {
            throw new SynapExceptions(SynapExceptionConstants::EPISODE_ALREADY_OPENED,400);
        }

        // set status type
        $statusType = $this->getStatusType($data->getData('properties')['status'], $serviceQue);
        
        $data->setData(array('properties' => array('episodeStatusType' => $statusType)));

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
        // fetching current episode record
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('conditions')['object'][0];
        $searchKey[0]['outKey'] = 'response';
        $epiResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $statusArr = $this->getOrgConfigStatus(
            $epiResp['data']['response']['patientCaseIdProgramName'],
            $epiResp['data']['response']['organizationId'],
            $serviceQue
        );

        if (!empty($data->getData('properties')['status'])) {
            if (!array_key_exists($data->getData('properties')['status'], $statusArr)) {
                throw new SynapExceptions(SynapExceptionConstants::CONFIGURE_FIRST,400, array('statusType' => ''));
            }
        }
        // if discharge date not empty and and that is equal to today/prior
        // status should be inactive
        // if discharge date not empty and and that is in future then warn
        // user you can't change status
        $today = new \DateTime('NOW');
        $currDate = $today->format(DateTimeUtility::DATE_FORMAT);

        if (!empty($data->getData('properties')['dischargeDate'])) {
            $dischargeDate = DateTimeUtility::convertDateObject($data->getData('properties')['dischargeDate']);

            if ($dischargeDate <= $today) {
                // if discharge date not empty and that is equal to today/prior
                // status should be inactive
                if (!empty($data->getData('properties')['status'])) {
                    if ($statusArr[$data->getData('properties')['status']] != self::STATUS_TYPE_INACTIVE) {
                        // if discharge date not empty and that is equal to today/prior
                        // status should be inactive
                        throw new SynapExceptions(SynapExceptionConstants::STATUS_SHOULD_BE_CLOSED,400);
                    }
                } else {
                    throw new SynapExceptions(SynapExceptionConstants::STATUS_REQUIRED,400);
                }
            } elseif (!empty($data->getData('properties')['status'])) {
                // if discharge date not empty and that is in future then warn
                // user you can't change status

                if ($data->getData('properties')['status'] != $epiResp['data']['response']['status']) {
                    throw new SynapExceptions(SynapExceptionConstants::STATUS_NOT_REQUIRED,400);
                }
            }
        } elseif (!empty($data->getData('properties')['status'])) {
            // if status cofigured and inactive sent then discharge date required
            if ($statusArr[$data->getData('properties')['status']] == self::STATUS_TYPE_INACTIVE) {
                throw new SynapExceptions(SynapExceptionConstants::DISCHARGE_DATE_REQUIRED,400);
            }
        }

        if (!empty($data->getData('properties')['status'])) {
            // set status type
            $statusType = $this->getStatusType($data->getData('properties')['status'], $serviceQue);
            $data->setData(array('properties' => array('episodeStatusType' => $statusType)));
            
            // if statue sent pending and its same as before then return true
            if ($statusType == 'metaCaseEpisodeStatusType:pending' &&
                    $statusType == $epiResp['data']['response']['episodeStatusType']) {
                return true;
            }
            
            //You can not change status active/inactive to pending.
            /*if ($statusType == 'metaCaseEpisodeStatusType:pending' &&
                    $epiResp['data']['response']['episodeStatusType'] != 'metaCaseEpisodeStatusType:pending') {
                throw new SynapExceptions(SynapExceptionConstants::CANNOT_CHANGE_TO_PENDING);
            }
            
            if ($statusType == 'metaCaseEpisodeStatusType:active' &&
                    $epiResp['data']['response']['episodeStatusType'] == 'metaCaseEpisodeStatusType:inactive') {
                throw new SynapExceptions(SynapExceptionConstants::CANNOT_CHANGE_TO_ACTIVE);
            }*/
            // update patientCase as inactive
            $updateObjs = [];
            $updateObjs['conditions']['object'][0] = $epiResp['data']['response']['patientCaseId'];
            $updateObjs['properties']['status'] = $data->getData('properties')['status'];

            if ($statusArr[$data->getData('properties')['status']] == self::STATUS_TYPE_INACTIVE) {
                // TODO: Naveen - Note: This is not possible at the moment.
                // update patientDivisionProgram as false
                // if closed then set closeDate as current date
                $updateCloseDateArr = array("closeDate" => $currDate);
                $data->setData($updateCloseDateArr, 'properties');
                $updateObjs['properties']['endDate'] = $data->getData('properties')['dischargeDate'];
            }

            // update patientCase according to episode
            $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
            
        } // end isset status
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
            throw new SynapExceptions(SynapExceptionConstants::CONFIGURE_FIRST,400, array('statusType' => 'Default'));
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
        $searchKey[0]['conditions'][] = array('programName' => array("IN" => array($progId)));
        $searchKey[0]['outKey'] = 'response';
        $confResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $outarr = [];
        if (!empty($confResp['data']['response'])) {
            foreach ($confResp['data']['response'] as $value) {
                $outarr[$value['id']] = $value['type'];
            }
        } else {
            throw new SynapExceptions(SynapExceptionConstants::CONFIGURE_FIRST,400, array('statusType' => ''));
        }
        return $outarr;
    }

}
