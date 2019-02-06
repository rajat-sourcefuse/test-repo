<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientCaseEpisode;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\OMLBundle\Utilities\RecursionUtility;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

class PostExecuteM implements PostExecuteI
{

    /**
     * function will validate data before execute create
     * 
     * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {

        if (!empty($data->getData('parent'))) {
            // Fetch Patient Case Id From Input
            $patientCaseId = $data->getData('parent');
            // Fetch Patient Case Episode Id From Input
            $patientCaseEpisodeId = $data->getData('resp')['data']['id'];

            //Get Program Status from patient case
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientCaseEpisodeId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientCaseEpisode = $resp['data']['response'];


            //Get Program Name from patient case
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientCaseId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientCase = $resp['data']['response'];

            if (!empty($patientCase['programNameName']) && !empty($patientCase['patientId'])) {
                // Create WorkFlow Name
                $workFlowName = "Case_" . $patientCase['programNameName'] . "_" . $patientCaseEpisode['statusName'];

                // Get workflow ID
                $searchKey = [];
                $searchKey[0]['conditions'][] = array('name' => $workFlowName);
                $searchKey[0]['type'] = 'synapWorkflow';
                $searchKey[0]['outKey'] = 'response';
                $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                $synapWorkflowResponse = $resp['data']['response'];

                if (!empty($synapWorkflowResponse[0]['id'])) {
                    // Start the workflow
                    $wfParams = array('patientCaseId' => $patientCaseId, 'patientId' => $patientCase['patientId'], 'patientCaseEpisodeId' => $patientCaseEpisodeId);
                    if (isset($patientCase['programName'])) {
                        $wfParams['patientCaseProgramId'] = $patientCase['programName'];
                    }
                    $workFlowData = array('workflowId' => $synapWorkflowResponse[0]['id'], 'workflowIdName' => $workFlowName);
                    $this->startGenericWorkflow($wfParams, $workFlowData);
                }
            }

            $this->updatePatientCaseStatus($data->getData('properties')['patientCaseId'], $data->getData('properties')['episodeStatusType'], $serviceQue);
        }

        return true;
    }

    /**
     * trigger encounter workflow
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $encounterId
     * @param type $patientId
     * @return boolean
     */
    private function triggerWorkflow($workflowData)
    {
        $configurator = \SynapEssentials\TransactionManagerBundle\EventListener\Configurator::getInstance();
        $container = $configurator->getServiceContainer();
        $exeManagerObject = ExecutionManager::getInstance($container);
        //$resp will contain multiple execution ID
        $resp = $exeManagerObject->startRelatedWorkFlows('patientProgram', $workflowData);
        return $resp;
    }

    /**
     * @description function will validate data before execute delete
     * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
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
    public function postExecuteGet($data, ServiceQueI $serviceQue)
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
    public function postExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * @description function will validate data before execute update
     * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        if (!empty($data->getData('parent'))) {
            // Fetch Patient Case Id From Input
            $patientCaseId = $data->getData('parent');
            // Fetch Patient Case Episode Id From Input
            $patientCaseEpisodeId = $data->getData('conditions')['object'][0];

            //Get Program Status from patient case
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientCaseEpisodeId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientCaseEpisode = $resp['data']['response'];

            //Get Program Name from patient case
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientCaseId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientCase = $resp['data']['response'];

            if (!empty($patientCase['programNameName']) && !empty($patientCase['patientId'])) {
                // Create WorkFlow Name
                $workFlowName = "Case_" . $patientCase['programNameName'] . "_" . $patientCaseEpisode['statusName'];
                // Get workflow ID
                $searchKey = [];
                $searchKey[0]['conditions'][] = array('name' => $workFlowName);
                $searchKey[0]['type'] = 'synapWorkflow';
                $searchKey[0]['outKey'] = 'response';
                $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                $synapWorkflowResponse = $resp['data']['response'];

                if (!empty($synapWorkflowResponse[0]['id'])) {
                    // Start the workflow

                    $data = array('patientCaseId' => $patientCaseId, 'patientId' => $patientCase['patientId'], 'patientCaseEpisodeId' => $patientCaseEpisodeId);
                    if (isset($patientCase['programName'])) {
                        $data['patientCaseProgramId'] = $patientCase['programName'];
                    }
                    $workFlowData = array('workflowId' => $synapWorkflowResponse[0]['id'], 'workflowIdName' => $workFlowName);
                    $this->startGenericWorkflow($data, $workFlowData);
                }
            }
        }

        return true;
    }

    /**
     * This function start work flow if case episode status is either active or inactive 
     * and discharge date is filled
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     * @param type $data
     * @param type $workFlowData
     * @return type
     */
    private function startGenericWorkflow($data, $workFlowData)
    {
        $configurator = \SynapEssentials\TransactionManagerBundle\EventListener\Configurator::getInstance();
        $container = $configurator->getServiceContainer();
        $exeManagerObject = ExecutionManager::getInstance($container);
        $exeManagerObject->startWorkFlow($data, $workFlowData);
        return;
    }

    private function updatePatientCaseStatus($caseId, $caseStatusType, $serviceQue)
    {

        $updateObjs = [];
        $updateObjs['conditions']['object'][0] = $caseId;
        $updateObjs['properties']['caseStatusType'] = $caseStatusType;
        $episodeResponse = $serviceQue->executeQue('ws_oml_update', $updateObjs);
        return;
    }
}
