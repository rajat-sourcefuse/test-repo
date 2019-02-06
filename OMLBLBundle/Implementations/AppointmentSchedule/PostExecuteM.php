<?php

namespace SynapEssentials\OMLBLBundle\Implementations\AppointmentSchedule;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI {

    private $serviceQue;

    const STATUS = 'appointmentStatus';
    const STATUS_ARRIVED = 'metaAppointmentStatus:arrived';
    const STATUS_SCHEDULED = 'metaAppointmentStatus:scheduled';
    const ENCOUNTER_STATUS_INITIATED = 'metaEncounterStatus:initiated';
    const ENCOUNTERTYPE = 'encounterType';
    const EPISODE_STATUS_TYPE = 'metaCaseEpisodeStatusType:arrived';
    const PRIMARY_COMPANY_RANK = 'metaInsuranceCompanyRank:one';
    const SECONDARY_COMPANY_RANK = 'metaInsuranceCompanyRank:two';
    const TERTIARY_COMPANY_RANK = 'metaInsuranceCompanyRank:three';

    /**
     * @description function will create recurring appointments.
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        $encounterType = !empty($data->getData('properties')['encounterType']) ? $data->getData('properties')['encounterType'] : '';
        $appointmentScheduleId = $data->getData('id');

        /* On creating: group encounter attendance and notes */
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('properties')['encounterType'];
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        //AP-304 closing group encounter code calls 
//        if((count($resp) > 0) && ($resp['data']['response']['groupSchedule']==true)){   
//            foreach ($data->getData('properties')['patientId'] as $patientId) {
//                $this->createGroupEncounterAttendance($appointmentScheduleId, $patientId, $encounterType, $data->getData('properties')[self::STATUS], $serviceQue);
//            }
//        }
        

        if ($data->getData('properties')[self::STATUS] == self::STATUS_ARRIVED) {
            // If status of appointment is arrived, then values fetched and converted to the required format to create a patientEncounter.

            $patientIdArr = $providerArr = [];
            $primaryProvider = $scheduledProvider = $referringProvider = '';
            if (!empty($data->getData('properties')['patientId'])) {
                $patientIdArr = $data->getData('properties')['patientId'];
            }

            if (!empty($data->getData('properties')['provider'])) {
                $providerArr = $data->getData('properties')['provider'];
            }

            if (!empty($data->getData('properties')['scheduledProvider'])) {
                $scheduledProvider = $data->getData('properties')['scheduledProvider'];
            }
            if (!empty($data->getData('properties')['primaryProvider'])) {
                $primaryProvider = $data->getData('properties')['primaryProvider'];
            }

            if (!empty($data->getData('properties')['referringProvider'])) {
                $referringProvider = $data->getData('properties')['referringProvider'];
            }

            $appointmentScheduleProg = $data->getData('properties')['programs'];
            $reasonForVisit = null;
            if (isset($data->getData('properties')['reasonForVisit'])) {
                $reasonForVisit = $data->getData('properties')['reasonForVisit'];
            }
            $appointmentStartDate = $data->getData('properties')['startDate'];
            $encounterDuration = $data->getData('properties')['duration'];
            $encounterStartDate = $appointmentStartDate;
            $encounterStartDate = DateTimeUtility::convertFormat($appointmentStartDate, DateTimeUtility::DB_DATE_FORMAT, DateTimeUtility::DATE_FORMAT);

            $appointmentEndDate = $data->getData('properties')['endDate'];
            $encounterEndDate = $appointmentEndDate;
            $encounterEndDate = DateTimeUtility::convertFormat($encounterEndDate, DateTimeUtility::DB_DATE_FORMAT, DateTimeUtility::DATE_FORMAT);

            $appointmentStartTime = $data->getData('properties')['startTime'];
            $encounterStartTime = DateTimeUtility::convertTimeFormat($appointmentStartTime, DateTimeUtility::TIME_FORMAT);

            $appointmentEndTime = $data->getData('properties')['endTime'];
            $encounterEndTime = DateTimeUtility::convertTimeFormat($appointmentEndTime, DateTimeUtility::TIME_FORMAT);
            
            $appointmentLocation = $data->getData('properties')['locationId'];

            foreach ($data->getData('properties')['patientId'] as $patientId) {
                // creating encounter    

                $this->createEncounter($patientId, $providerArr, $scheduledProvider, $primaryProvider, $appointmentScheduleId, $appointmentScheduleProg, $reasonForVisit, $encounterStartDate, $encounterStartTime, $encounterEndDate, $encounterEndTime, $encounterDuration, $serviceQue, $encounterType, $appointmentLocation, $referringProvider);
            }
        }
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * @description function will perform some actions after execute update
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {

        // Search for appointment schedule status
        $appointmentScheduleId = $data->getData('properties')['id'];
        $searchKey = [];
        $searchKey[0]['type'] = 'appointmentSchedule';
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $searchKey[0]['conditions'][] = array('id' => $appointmentScheduleId);
        $searchKey[0]['outKey'] = 'response';

        $appointmentScheduleResponse = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $appointmentScheduleData = $appointmentScheduleResponse['data']['response'];

        /* On update: Trigger preEncounterWorkflow if encounterType is not blank and status of appointment is scheduled. */
        if (isset($data->getData('properties')[self::STATUS]) && !empty($data->getData('properties')[self::STATUS])) {
            if ($data->getData('properties')[self::STATUS] == self::STATUS_SCHEDULED) {
                $encounterType = '';
                if (!empty($data->getData('properties')['encounterType'])) {
                    $encounterType = $data->getData('properties')['encounterType'];
                } else {
                    if (!empty($appointmentScheduleData['encounterType'])) {
                        $encounterType = $appointmentScheduleData['encounterType'];
                    }
                }
            }
        }

        // If appointment schedule status is arrived than call update encounter
        if (isset($appointmentScheduleData[0][self::STATUS]) && !empty($appointmentScheduleData[0][self::STATUS]) && $appointmentScheduleData[0][self::STATUS] == self::STATUS_ARRIVED) {
            $encounterType = !empty($appointmentScheduleData[0][self::ENCOUNTERTYPE]) ? $appointmentScheduleData[0][self::ENCOUNTERTYPE] : '';
            $this->updateEncounter($data, $serviceQue, $encounterType);
        }


        return true;
    }

    /**
     * 
     * @description Update all encounter  related to appoitment schedule
     * @param type $data
     * @param type $serviceQue
     * @return boolean
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     */
    private function updateEncounter($data, $serviceQue, $encounterType) {
        $appointmentScheduleId = $data->getData('properties')['id'];

        // Search Patient Encounter Based On $appointmentScheduleId
        $searchKey = [];
        $searchKey[0]['type'] = 'patientEncounter';
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $searchKey[0]['conditions'][] = array('appointmentScheduleId' => $appointmentScheduleId);
        $searchKey[0]['outKey'] = 'response';

        $patientEncounterResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientEncounterData = $patientEncounterResp['data']['response'];

        // Check If $patientEncounterData contains any data
        if (!empty($patientEncounterData) && count($patientEncounterData) > 0) {

//            $updatePropertyArr = array(
//                'careTeam' => 'provider',
//                'program' => 'programs',
//                'reasonForVisit' => 'reasonForVisit',
//                'encounterStartDate' => 'startDate',
//                'encounterEndDate' => 'endDate',
//                'encounterStartTime' => 'startTime',
//                'encounterEndTime' => 'endTime',
//                'encounterType' => 'encounterType',
//                'duration' => 'duration',
//                'referredBy' => 'referredBy'
//            );


            $updatePropertyArr = array(
                'careTeam' => 'provider',
                'program' => 'programs',
                'reasonForVisit' => 'reasonForVisit',
                'encounterStartDate' => 'startDate',
                'encounterStartTime' => 'startTime',
                'encounterEndDate' => 'endDate',
                'encounterEndTime' => 'endTime',
                'encounterType' => 'encounterType',
                'duration' => 'duration',
                'referredBy' => 'referredBy',
                'referringProvider' => 'referringProvider',
                'attendingProvider' => 'primaryProvider'
            );

            foreach ($updatePropertyArr as $updatedKey => $updatedValue) {
                if (isset($data->getData('properties')[$updatedValue]) && !empty($data->getData('properties')[$updatedValue])) {
                    $setUpdatedValue = $data->getData('properties')[$updatedValue];

                    if ($updatedValue === 'startDate' || $updatedValue === 'endDate') {
                        $setUpdatedValue = DateTimeUtility::convertFormat($setUpdatedValue, DateTimeUtility::DB_DATE_FORMAT, DateTimeUtility::DATE_FORMAT);
                    }

                    if ($updatedValue === 'startTime' || $updatedValue === 'endTime') {
                        $setUpdatedValue = DateTimeUtility::convertTimeFormat($setUpdatedValue, DateTimeUtility::TIME_FORMAT);
                    }

                    if ($updatedValue === 'duration') {
                        $setUpdatedValue = (int) $setUpdatedValue;
                    }

                    $updateObjs['properties'][$updatedKey] = $setUpdatedValue;
                }
            }

            // Check If updated record does not contains encounterTyp and previous appointmentchedule contains encounterType
            if (!array_key_exists("encounterType", $updateObjs['properties']) && !empty($encounterType)) {
                $updateObjs['properties'][self::ENCOUNTERTYPE] = $encounterType;
            }

            foreach ($patientEncounterData as $patientEncounterDatas) {
                $newUpdateObjs = array();
                $updateObjs['conditions']['object'][0] = $patientEncounterDatas['id'];
                $newUpdateObjs = $updateObjs;
                $updateResp = $serviceQue->executeQue("ws_oml_update", $newUpdateObjs);
            }
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
    private function createEncounter($patientId, $providerArr, $scheduledProvider, $primaryProvider, $appointmentScheduleId, $appointmentScheduleProg, $reasonForVisit, $encounterStartDate, $encounterStartTime, $encounterEndDate, $encounterEndTime, $encounterDuration, $serviceQue, $encounterType, $location, $referringProvider) {

        if (!empty($patientId)) {

            $objectData['properties']['appointmentScheduleId'] = $appointmentScheduleId;
            $objectData['properties']['encounterStartDate'] = $encounterStartDate;
            $objectData['properties']['encounterStartTime'] = $encounterStartTime;
            $objectData['properties']['duration'] = (int) $encounterDuration;
            //  $objectData['properties']['encounterEndDate'] = $encounterEndDate;
            //  $objectData['properties']['encounterEndTime'] = $encounterEndTime;            
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
            }

            if (!empty($scheduledProvider)) {
                $objectData['properties']['scheduledProvider'] = $scheduledProvider;
            }
            if (!empty($primaryProvider)) {
                $objectData['properties']['attendingProvider'] = $primaryProvider;
                $dateTimeUtility = new DateTimeUtility();
                $currDate = date($dateTimeUtility::DATE_FORMAT);
                /**
                 * Author Kuldeep Singh, Date: 08-08-2017, JIRA Id: AP-553, AP-339
                 * Appointment checking-in : Relevant patient insurance payer has tax entity/transmission tunnel assignments
                 * 
                 */
                $patientInsurancecData = $this->checkPatientInsurance($serviceQue, $patientId, $currDate);
                if (isset($patientInsurancecData) && count($patientInsurancecData) > 0) {
                    $arrInsurancePayers = $this->patientInsurancePayers($patientInsurancecData);
                    $orgTaxEntityPayerConfigData = $this->payerWiseOrgTaxPayerConfig($serviceQue, $appointmentLocation, $arrInsurancePayers);
                    if (isset($arrInsurancePayers['primaryPayer'])) {
                        if (count($orgTaxEntityPayerConfigData) > 0 && count($orgTaxEntityPayerConfigData['primaryPayer']) > 0) {

                            $orgPayerId = $orgTaxEntityPayerConfigData['primaryPayer'][0]['organizationPayerId'];
                            $taxEntityId = $orgTaxEntityPayerConfigData['primaryPayer'][0]['taxEntityId'];
                            $orgEmpPayerExistingData = $this->checkOrgEmpPayerTaxEntity($serviceQue, $orgPayerId, $taxEntityId, $primaryProvider, $currDate);
                            if (isset($orgEmpPayerExistingData) && count($orgEmpPayerExistingData) > 0) {
                                $objectData['properties']['attendingProvider'] = $primaryProvider;
                            } else {
                                throw new SynapExceptions(SynapExceptionConstants::ATTENDING_PROVIDER_NOT_REGISTERED_WITH_PATIENT_PRIMARY_PAYER,400);
                            }
                        }
                    }
                }
            }

            if (!empty($referringProvider)) {
                $objectData['properties']['referringProvider'] = $referringProvider;
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
        return true;
    }

    /**
     * trigger encounter workflow
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $encounterId
     * @param type $patientId
     * @return boolean
     */
    private function triggerEncounterWorkflow($encounterId, $patientId, $encounterType, $serviceQue) {
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
     * Mark group encounter patient attendance and notes
     * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
     * @param type $data
     * @param type $serviceQue
     * @return boolean
     */
    private function createGroupEncounterAttendance($appointmentScheduleId, $patientId, $encounterType, $encounterStatus, $serviceQue) {
        if ((!empty($patientId)) && (!empty($encounterType))) {
            $attendanceData = array();
            $attendanceData['properties']['appointmentPatientId'] = $patientId;
            $attendanceData['properties']['appointmentStatus'] = $encounterStatus;
            $attendanceData['properties']['encounterType'] = $encounterType;
            $attendanceData['parent'] = $appointmentScheduleId;
            $attendanceData['objectType'] = 'appointmentScheduleAttendance';
            $serviceQue->executeQue('ws_oml_create', $attendanceData);
        }
        return true;
    }

    /**
     * @description this function fetch and return the patientInsurance details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $patientId, $insuranceCompanyRank, $coverageDate
     * @return patientInsurance detail
     */
    private function checkPatientInsurance($serviceQue, $patientId, $coverageDate) {
        $patientInsurancecSearchKey = [];
        $patientInsurancecSearchKey[0]['type'] = 'patientInsurance';
        $patientInsurancecSearchKey[0]['conditions'][] = array('patientId' => $patientId);
//        $patientInsurancecSearchKey[0]['conditions'][] = array('insuranceCompanyRank' => self::PRIMARY_COMPANY_RANK);
        $patientInsurancecSearchKey[0]['conditions'][] = array('coverageStartDate' => array('LE' => $coverageDate));
        $patientInsurancecSearchKey[0]['conditions'][] = array(
            array('coverageEndDate' => array('GE' => $coverageDate)),
            "OR",
            array('coverageEndDate' => array('isnull' => null))
        );
        $patientInsurancecSearchKey[0]['sendNullKey'] = 1;
        $patientInsurancecSearchKey[0]['outKey'] = 'response';
        $patientInsurancecData = $serviceQue->executeQue('ws_oml_read', $patientInsurancecSearchKey);
        $patientInsuranceResponse = $patientInsurancecData['data']['response'];
        return $patientInsuranceResponse;
    }

    /**
     * @description this function fetch and return the organizationTaxEntityPayerConfig details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $orgPayerId, $locationId
     * @return organizationTaxEntityPayerConfig detail
     */
    private function checkOrgTaxEntityPayerConfig($serviceQue, $orgPayerId, $locationId) {
        $orgTaxEntityPayerConfigSearchKey = [];
        $orgTaxEntityPayerConfigSearchKey[0]['type'] = 'organizationTaxEntityPayerConfig';
        $orgTaxEntityPayerConfigSearchKey[0]['conditions'][] = array('organizationPayerId' => $orgPayerId);
        $orgTaxEntityPayerConfigSearchKey[0]['conditions'][] = array('locationId' => $locationId);
        $orgTaxEntityPayerConfigSearchKey[0]['sendNullKey'] = 1;
        $orgTaxEntityPayerConfigSearchKey[0]['outKey'] = 'response';
        $orgTaxEntityPayerConfigData = $serviceQue->executeQue('ws_oml_read', $orgTaxEntityPayerConfigSearchKey);
        $orgTaxEntityPayerConfigResponse = $orgTaxEntityPayerConfigData['data']['response'];
        return $orgTaxEntityPayerConfigResponse;
    }

    /**
     * @description this function fetch and return the organizationEmployeePayerTaxEntity details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $orgPayerId, $taxEntityId, $employeeId, $currentDate
     * @return organizationEmployeePayerTaxEntity detail
     */
    private function checkOrgEmpPayerTaxEntity($serviceQue, $orgPayerId, $taxEntityId, $employeeId, $currentDate) {
        $orgEmpPayerSearchKey = [];
        $orgEmpPayerSearchKey[0]['type'] = 'organizationEmployeePayerTaxEntity';
        $orgEmpPayerSearchKey[0]['conditions'][] = array('payerId' => $orgPayerId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('taxEntityId' => $taxEntityId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('employeeId' => $employeeId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('startDate' => array('LE' => $currentDate));
        $orgEmpPayerSearchKey[0]['conditions'][] = array(
            array('endDate' => array('GE' => $currentDate)),
            "OR",
            array('endDate' => array('isnull' => null))
        );
        $orgEmpPayerSearchKey[0]['sendNullKey'] = 1;
        $orgEmpPayerSearchKey[0]['outKey'] = 'response';
        $orgEmpPayerExistingData = $serviceQue->executeQue('ws_oml_read', $orgEmpPayerSearchKey);
        $orgEmpPayerExistingResponse = $orgEmpPayerExistingData['data']['response'];
        return $orgEmpPayerExistingResponse;
    }

    /**
     * @description this function array of Patient Insurance Payers
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $arrPatientInsurances
     * @return Insurnace payer array
     */
    private function patientInsurancePayers($arrPatientInsurances) {
        $arrOrgPayers = array();
        if (count($arrPatientInsurances) > 0) {
            foreach ($arrPatientInsurances as $arrPatientInsurance) {
                switch ($arrPatientInsurance['insuranceCompanyRank']) {
                    case self::PRIMARY_COMPANY_RANK:
                        $arrOrgPayers['primaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                    case self::SECONDARY_COMPANY_RANK:
                        $arrOrgPayers['secondaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                    case self::TERTIARY_COMPANY_RANK:
                        $arrOrgPayers['tertiaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                }
            }
        }
        return $arrOrgPayers;
    }

    /**
     * @description this function fetch and return the organizationTaxEntityPayerConfig details according to Patient Insurance Payer
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $locationId, $arrPatientInsurancec
     * @return organizationTaxEntityPayerConfig detail
     */
    private function payerWiseOrgTaxPayerConfig($serviceQue, $locationId, $arrOrgPayers) {
        $arrOrgTaxPayerConfig = array();
        if (count($arrOrgPayers) > 0) {
            foreach ($arrOrgPayers as $payer => $orgPayerId) {
                $orgTaxEntityPayerConfigData = $this->checkOrgTaxEntityPayerConfig($serviceQue, $orgPayerId, $locationId);
                $arrOrgTaxPayerConfig[$payer] = $orgTaxEntityPayerConfigData;
                if (count($orgTaxEntityPayerConfigData) <= 0) {
                    throw new SynapExceptions(SynapExceptionConstants::PATIENT_INSURANCE_ASSIGNMENT_TAX_ENTITY_TRANS_TUNNEL_NOT_EXIST,400, array('property' => $payer));
                    break;
                }
            }
        }
        return $arrOrgTaxPayerConfig;
    }

}
