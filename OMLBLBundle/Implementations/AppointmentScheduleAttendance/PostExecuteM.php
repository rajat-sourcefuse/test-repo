<?php

namespace SynapEssentials\OMLBLBundle\Implementations\AppointmentScheduleAttendance;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    private $serviceQue;
    const STATUS = 'appointmentStatus';
    const STATUS_ARRIVED = 'metaAppointmentStatus:arrived';
    const STATUS_SCHEDULED = 'metaAppointmentStatus:scheduled';
    const ENCOUNTER_STATUS_INITIATED = 'metaEncounterStatus:initiated';
    const ENCOUNTERTYPE = 'encounterType';
    const EPISODE_STATUS_TYPE = 'metaCaseEpisodeStatusType:arrived';

    /**
     * @description function will create recurring appointments.
     * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */

    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $appointmentScheduleId = $data->getData('parent');
        $appointmentScheduleAttendanceId = $data->getData('id');
        
        /*On creating: group encounter attendance and notes*/
        $searchKey = [];
        $searchKey[0]['objectId'] = $appointmentScheduleId;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);       

        if ($data->getData('properties')[self::STATUS] == self::STATUS_ARRIVED) {
            // If status of appointment is arrived, then values fetched and converted to the required format to create a patientEncounter.
            $providerArr=((count($resp['data']['response']['provider']) > 0) ? $resp['data']['response']['provider'] : []);
            $appointmentScheduleProg=((!empty($resp['data']['response']['programs'])) ? $resp['data']['response']['programs'] : null);
            $reasonForVisit=((!empty($resp['data']['response']['reasonForVisit'])) ? $resp['data']['response']['reasonForVisit'] : null);
            
            $encounterStartDate=null;
            if (isset($resp['data']['response']['startDate'])) {
                $appointmentStartDate = $resp['data']['response']['startDate'];
                $encounterStartDate = $appointmentStartDate;
            }
          
            $encounterEndDate=null;
            if (isset($resp['data']['response']['endDate'])) {
                $appointmentEndDate = $resp['data']['response']['endDate'];
                $encounterEndDate = $appointmentEndDate;
            }
            
            $encounterStartTime = ((!empty($resp['data']['response']['startTime'])) ? DateTimeUtility::convertTimeFormat($resp['data']['response']['startTime'], DateTimeUtility::TIME_FORMAT) : "");
            $encounterEndTime = ((!empty($resp['data']['response']['endTime'])) ? DateTimeUtility::convertTimeFormat($resp['data']['response']['endTime'], DateTimeUtility::TIME_FORMAT) : "");
            $appointmentLocation = ((!empty($resp['data']['response']['locationId'])) ? $resp['data']['response']['locationId'] : "");
            
            // creating encounter
            $encounterData = $this->createEncounter(
                $data->getData('properties')['appointmentPatientId'],
                $providerArr,
                $appointmentScheduleId,
                $appointmentScheduleProg,
                $reasonForVisit,
                $encounterStartDate,
                $encounterStartTime,
                $encounterEndDate,
                $encounterEndTime,
                $serviceQue,
                $data->getData('properties')['encounterType'],                
                $appointmentLocation
            );
            
            $this->updatePatientEncounterData($encounterData['encounterId'], $encounterData['executionId'], $appointmentScheduleAttendanceId, $serviceQue);
        }
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
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
     * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $data = $data->getData();
        $appointmentScheduleId=null;
        $appointmentPatientId=null;
                
        if (!empty($data['properties'][self::STATUS])) {
            $appointmentScheduleAttendanceId = $data['conditions']['object'][0];

            // get $appointmentScheduleAttendanceId record
            $searchKey = [];
            $searchKey[0]['objectId'] = $appointmentScheduleAttendanceId;
            $searchKey[0]['outKey'] = 'response';
            $response = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $appointmentPatientId= $response['data']['response']['appointmentPatientId'];
            $appointmentScheduleId= $response['data']['response']['appointmentScheduleId'];            
        }
        
        if ($data['properties'][self::STATUS] == self::STATUS_ARRIVED) {
            $searchKey = [];
            $searchKey[0]['objectId'] = $appointmentScheduleId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
           
            $providerArr = ((count($resp['data']['response']['provider']) > 0) ? $resp['data']['response']['provider'] : []);
            $appointmentScheduleProg = ((!empty($resp['data']['response']['programs'])) ? $resp['data']['response']['programs'] : null);
            $reasonForVisit = ((!empty($resp['data']['response']['reasonForVisit'])) ? $resp['data']['response']['reasonForVisit'] : null);
            
            $encounterStartDate = null;
            if (isset($resp['data']['response']['startDate'])) {
                $appointmentStartDate = $resp['data']['response']['startDate'];
                $encounterStartDate = $appointmentStartDate;
            }
          
            $encounterEndDate = null;
            if (isset($resp['data']['response']['endDate'])) {
                $appointmentEndDate = $resp['data']['response']['endDate'];
                $encounterEndDate = $appointmentEndDate;
            }
                     
            $encounterStartTime = ((!empty($resp['data']['response']['startTime'])) ? DateTimeUtility::convertTimeFormat($resp['data']['response']['startTime'], DateTimeUtility::TIME_FORMAT) : "");
            $encounterEndTime = ((!empty($resp['data']['response']['endTime'])) ? DateTimeUtility::convertTimeFormat($resp['data']['response']['endTime'], DateTimeUtility::TIME_FORMAT) : "");            
            $appointmentLocation = ((!empty($resp['data']['response']['locationId'])) ? $resp['data']['response']['locationId'] : "");
           
            // creating encounter
            $encounterData = $this->createEncounter(
                $appointmentPatientId,
                $providerArr,
                $appointmentScheduleId,
                $appointmentScheduleProg,
                $reasonForVisit,
                $encounterStartDate,
                $encounterStartTime,
                $encounterEndDate,
                $encounterEndTime,
                $serviceQue,
                $resp['data']['response']['encounterType'],                
                $appointmentLocation
            );
            
            $this->updatePatientEncounterData($encounterData['encounterId'], $encounterData['executionId'], $appointmentScheduleAttendanceId, $serviceQue);
        }
        
        
        
        return true;
    }

    /**
     * create encounter if appointment status changed to arrived
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param type $serviceQue
     * @return boolean
     */

    private function createEncounter($patientId, $providerArr, $appointmentScheduleId, $appointmentScheduleProg, $reasonForVisit, $encounterStartDate, $encounterStartTime, $encounterEndDate, $encounterEndTime, $serviceQue, $encounterType, $location)
    {
        $encounterId = null;
        $executionId = null;
        if (!empty($patientId)) {
            $objectData['properties']['appointmentScheduleId'] = $appointmentScheduleId;
            $objectData['properties']['encounterStartDate'] = $encounterStartDate;
            $objectData['properties']['encounterStartTime'] = $encounterStartTime;
            $objectData['properties']['encounterEndDate'] = $encounterEndDate;
            $objectData['properties']['encounterEndTime'] = $encounterEndTime;  
            $objectData['properties']['location'] = $location;
            if (!empty($encounterType)) {
                $objectData['properties']['encounterType'] = $encounterType;
            }
            $objectData['properties']['program'] = $appointmentScheduleProg;
            $objectData['properties']['status'] = self::ENCOUNTER_STATUS_INITIATED;

            if (!empty($reasonForVisit)) {
                $objectData['properties']['reasonForVisit'] = $reasonForVisit;
            }

            if (!empty($providerArr)) {
                $objectData['properties']['careTeam'] = $providerArr;
                $objectData['properties']['attendingProvider'] = $providerArr[0];
            }

            $objectData['parent'] = $patientId;
            $objectData['objectType'] = 'patientEncounter';
            $encounterResponse = $serviceQue->executeQue('ws_oml_create', $objectData);
            if (isset($encounterResponse['data']['id'])) {
                $encounterId = $encounterResponse['data']['id'];
                

                $executionId = $this->triggerEncounterWorkflow($encounterId, $patientId, $encounterType, $serviceQue);
                if (!empty($executionId)) {
                    $updateObjs['conditions']['object'][0] = $encounterId;
                    $updateObjs['properties']['executionId'] = $executionId;
                    $updateResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                }
            }
        }
        return array('encounterId' => $encounterId, 'executionId' => $executionId);
    }

    /**
     * trigger encounter workflow
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $encounterId
     * @param type $patientId
     * @return boolean
     */

    private function triggerEncounterWorkflow($encounterId, $patientId, $encounterType, $serviceQue)
    {
        $executionId = '';

        if (!empty($encounterType)) {
            $data = array('patientEncounterId' => $encounterId, 'patientId' => $patientId);
            
            $configurator = \SynapEssentials\TransactionManagerBundle\EventListener\Configurator::getInstance();
            $container = $configurator->getServiceContainer();
            $exeManagerObject = ExecutionManager::getInstance($container);

            // Get workflow ID
            $searchKey = [];
            $searchKey[0]['objectId'] = $encounterType;
            $searchKey[0]['outKey'] = 'response';
            $encounterConfigResponse = $serviceQue->executeQue("ws_oml_read", $searchKey);
            if (!empty($encounterConfigResponse['data']['response']['workflow'])) {
                // Start the workflow
                $executionId = $exeManagerObject->startWorkFlow($data, array('workflowId' => $encounterConfigResponse['data']['response']['workflow'], 'workflowIdName' => $encounterConfigResponse['data']['response']['workflowName']));
            }
        }
        return $executionId;
    }
    
    /**
     * Function update patient_encounter_id of appointmentScheduleAttendance
     * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
     * @param type $patientEncounterId
     * @param type $appointmentScheduleAttendance
     */
    private function updatePatientEncounterData($patientEncounterId, $executionId, $appointmentScheduleAttendance, $serviceQue)
    {
        $appointmentScheduleAttendanceData = array();
        $appointmentScheduleAttendanceData['conditions']['object'][0] = $appointmentScheduleAttendance;
        $appointmentScheduleAttendanceData['properties']['patientEncounterId'] = $patientEncounterId;
        $appointmentScheduleAttendanceData['properties']['executionId'] = $executionId;
        $serviceQue->executeQue("ws_oml_update", $appointmentScheduleAttendanceData);
    }
}
