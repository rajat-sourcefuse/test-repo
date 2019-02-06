<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientCase;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;

class PostExecuteM implements PostExecuteI
{

    const STATUS_TYPE_ACTIVE = 'metaCaseEpisodeStatusType:active';
    const STATUS_TYPE_INACTIVE = 'metaCaseEpisodeStatusType:inactive';
    const STATUS_TYPE_PENDING = 'metaCaseEpisodeStatusType:pending';
    const PATIENT_STATUS_ACTIVE = 'metaPatientStatus:active';
    const PATIENT_STATUS_INACTIVE = 'metaPatientStatus:inactive';
    const PATIENT_STATUS_PENDING = 'metaPatientStatus:pending';

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // only then if episode request is not coming with this request.
        // after creating the case, creating episode
        if (empty($data->getData('properties')['patientCaseEpisode'])) {
            $this->createEpisode($data->getData('id'), $data->getData('properties')['status'], $serviceQue);
        }
    }

    /**
     * This will trigger the workflow
     * @param type $encounterId
     * @param type $patientId
     * @return type
     */
    private function triggerEncounterWorkflow($encounterId, $patientId)
    {
        // this is the sample code of workflow need to be modified
        $data = array('patientEncounterId' => $encounterId, 'patientId' => $patientId);
        $configurator = \SynapEssentials\TransactionManagerBundle\EventListener\Configurator::getInstance();
        $container = $configurator->getServiceContainer();
        $exeManagerObject = ExecutionManager::getInstance($container);
        //$resp will contain multiple execution ID
        $resp = $exeManagerObject->startRelatedWorkFlows('patientArrived', $data);
        return $resp;
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
        // get parent id (patient id)
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('conditions')['object'][0];
        $searchKey[0]['outKey'] = 'response';
        // case record after update
        $caseResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        // Patient Status, patient's status is controlled by the case status.
        // if there are no active cases, then the patient is not active
        if (!empty($data->getData('properties')['status'])) {
            // update patient status
            $updateObjs = [];
            $updateObjs['conditions']['object'][0] = $caseResp['data']['response']['patientId'];
            if ($caseResp['data']['response']['statusType'] == self::STATUS_TYPE_ACTIVE) {
                $updateObjs['properties']['status'] = self::PATIENT_STATUS_ACTIVE;
            } elseif ($caseResp['data']['response']['statusType'] == self::STATUS_TYPE_INACTIVE) {
                $updateObjs['properties']['status'] = self::PATIENT_STATUS_INACTIVE;
            } else {
                $updateObjs['properties']['status'] = self::PATIENT_STATUS_PENDING;
            }
            $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
        }
    }

    /**
     * Function will create episode.
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $parentId
     * @param type $serviceQue
     */
    private function createEpisode($parentId, $status, $serviceQue)
    {
        $objectData = [];
        $objectData['parent'] = $parentId;
        $today = new \DateTime('NOW');
        $objectData['properties']['admissionDate'] = $today->format(DateTimeUtility::DATE_FORMAT);
        $objectData['properties']['status'] = $status;
        $objectData['objectType'] = 'patientCaseEpisode';
        $episodeResponse = $serviceQue->executeQue('ws_oml_create', $objectData);
    }
}
