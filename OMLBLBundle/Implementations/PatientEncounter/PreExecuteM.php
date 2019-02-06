<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientEncounter;

use SynapEssentials\Utilities\SessionUtility;
/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

class PreExecuteM implements PreExecuteI
{

    //constants for payers
    const PRIMARY_COMPANY_RANK = 'metaInsuranceCompanyRank:one';
    const SECONDARY_COMPANY_RANK = 'metaInsuranceCompanyRank:two';
    const TERTIARY_COMPANY_RANK = 'metaInsuranceCompanyRank:three';
    const PRIMARY_LEVEL = 1;
    const SECONDARY_LEVEL = 2;
    const TERTIARY_LEVEL = 3;
    const STATUS_COMPLETE = 'metaEncounterStatus:complete';
    const CASE_EPISODE_STATUS_TYPE_INACTIVE = 'metaCaseEpisodeStatusType:inactive';
    
    var $primaryPayer = '';
    var $secondaryPayer = '';
    var $tertiaryPayer = '';

    /**
     * @description function will validate data before execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        //Manish Kumar commented this code as divisions are not more required in organizationTaxEntity
        //check division tax entity
        //$this->checkDivisionTaxEntity($data, $serviceQue);
        
        if (isset($data->getData('properties')['status']) && $data->getData('properties')['status'] == self::STATUS_COMPLETE) {
            $currentTimeApiFormat = DateTimeUtility::convertTimeFormat($time = 'NOW', DateTimeUtility::TIME_FORMAT);
            $currentDateApiFormat = DateTimeUtility::getDateApiFormat($date = 'NOW', DateTimeUtility::DATE_FORMAT);

            $data->setData(array('properties' => array('encounterEndDate' => $currentDateApiFormat, 'encounterEndTime' => $currentTimeApiFormat)));
        }
        
        //set payer related details
        $this->setPayerDetails($data, $serviceQue);

        //set tax Entity id
        $this->setTaxEntityId($data, $serviceQue);

        $this->setUpdateCaseData($data, $serviceQue);

        //set encounter config and program name in patient encounter
        $this->setConfigAndProgramName($data, $serviceQue);
        $this->updatePatientEncounterDate($data->getData('properties'), $serviceQue);
        return;
    }

    /**
     * set organizationTaxEntityId in patient encounter
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     */
    private function setTaxEntityId($data, ServiceQueI $serviceQue)
    {
        $taxEntityId = $this->getTaxEntityId($data, $serviceQue);
        $data->setData(array('taxEntityId' => $taxEntityId), 'properties');
        return;
    }
    
    /**
     * get organizationTaxEntityId from patient encounter
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     * @return $taxEntityId
     */
    private function getTaxEntityId($data, ServiceQueI $serviceQue)
    {
        $taxEntityId = '';        
        $locationId = isset($data->getData('properties')['location']) ? $data->getData('properties')['location'] : '';                
        if (!empty($locationId)) {
            if(!empty($this->primaryPayer)){
                $payerConfigDetail = $this->getPayerConfig($locationId, $serviceQue);
                $taxEntityId = isset($payerConfigDetail[0]['taxEntityId'])?$payerConfigDetail[0]['taxEntityId']:'';
            }else{
                $locationDetail = $this->fetchObjectData($locationId,$serviceQue);
                $taxEntityId = isset($locationDetail['taxEntityId'])?$locationDetail['taxEntityId']:'';
            }
        }
        return $taxEntityId;
    }

    /**
     * update payer related details in patient encounter
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     */
    private function setPayerDetails($data, ServiceQueI $serviceQue)
    {
        //fetch insurance for a patient
        $patientInsuarnce = $this->getPatientInsurance($data, $serviceQue);

        //set payer related detail in encounter array
        $this->setInsuranceDetail($data, $patientInsuarnce, $serviceQue);
        return;
    }

    /**
     * get payerConfig based on location and primary payer
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $locationId
     */
    private function getPayerConfig($locationId, ServiceQueI $serviceQue)
    {
        //search key to get the objectData
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityPayerConfig';
        $searchKey[0]['conditions'][] = array('locationId' => $locationId);
        $searchKey[0]['conditions'][] = array('organizationPayerId' => $this->primaryPayer);
        $searchKey[0]['outKey'] = 'response';

        //fetch objectData
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        return $objectResp['data']['response'];
    }

    /**
     * Update notesFilledBy if notes filled by someone
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     */
    private function updateNotesFilledBy($data, $serviceQue)
    {
        $sessionUtil = SessionUtility::getInstance();
        $loggedInUser = $sessionUtil->getOrganizationEmployeeId();
        $data->setData(array('notesFilledBy' => $loggedInUser), 'properties');
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

        if (isset($data->getData('properties')['status']) && $data->getData('properties')['status'] == self::STATUS_COMPLETE) {
            $currentTimeApiFormat = DateTimeUtility::convertTimeFormat($time = 'NOW', DateTimeUtility::TIME_FORMAT);
            $currentDateApiFormat = DateTimeUtility::getDateApiFormat($date = 'NOW', DateTimeUtility::DATE_FORMAT);
            $data->setData(array('properties' => array('encounterEndDate' => $currentDateApiFormat, 'encounterEndTime' => $currentTimeApiFormat)));
        }
        
        if (isset($data->getData('properties')['location'])) {
            //set tax Entity id        
            $this->setTaxEntityId($data, $serviceQue);
        }

        $this->setUpdateCaseData($data, $serviceQue);

        //set encounter config and program name in patient encounter
        $this->setConfigAndProgramName($data, $serviceQue);
        return;
    }

    /**
     * Common Function to set and update data for create and update
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function setUpdateCaseData($data, $serviceQue)
    {
        // if notes is not empty then update notesFilledBy
        if (!empty($data->getData('properties')['notes']) &&
                empty($data->getData('properties')['notesFilledBy'])) {
            $this->updateNotesFilledBy($data, $serviceQue);
        }
    }

    /**
     * this function will get list of patient insurance data
     * @param  $patientId, $serviceQue
     * @return $patientInsuranceArr
     */
    private function getPatientInsurance($data, ServiceQueI $serviceQue)
    {
        //get encounter start date and program          
        $patientId = $data->getData('parent');
        $encounterStartDate = $data->getData('properties')['encounterStartDate'];
        //$encounterProgram = isset($data->getData('properties')['program']) ? $data->getData('properties')['program'] : '';

        //create search key
        $searchKey = [];
        $searchKey[0]['type'] = 'patientInsurance';
        $searchKey[0]['conditions'][] = array('patientId' => $patientId);
        $searchKey[0]['conditions'][] = array('coverageStartDate' => array('LE' => $encounterStartDate));
        $searchKey[0]['conditions'][] = array(
            array('coverageEndDate' => array('GE' => $encounterStartDate)),
            "OR",
            array('coverageEndDate' => array('isnull' => null))
        );
        /*
         * Commented this code as program assignment will get removed from patient insurance
        $searchKey[0]['conditions'][] = array(
            array('program' => $encounterProgram),
            "OR",
            array('program' => array('isnull' => null))
        );*/
        //$searchKey[0]['requiredAdditionalInfo'] = "0";
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientInsuranceArr = $resp['data']['response'];
        return $patientInsuranceArr;
    }

    /**
     * this function fetch and set the payer related detail in patientClaimProperties
     * @param  $patientInsuranceArr
     * @return
     */
    private function setInsuranceDetail($data, $patientInsuranceArr)
    {
        $insuranceArr = [];
        //set payer related properties for claim array
        if (!empty($patientInsuranceArr)) {
            foreach ($patientInsuranceArr as $payers) {
                switch ($payers['insuranceCompanyRank'])
                {
                    case self::PRIMARY_COMPANY_RANK:
                        $this->primaryPayer = $payers['insuranceCompanyName'];
                        // this will always includes payerId only i.e. organizationPayer:medicare
                        $insuranceArr['primaryPayer'] = $payers['id'];
                        $insuranceArr['currentPayer'] = $payers['insuranceCompanyName'];
                        $data->setData(array('properties' => array('primaryPayer' => $payers['id'], 'primaryPayerName' => $payers['insuranceCompanyNameName'], 'currentPayer' => $payers['insuranceCompanyName'])));
                        break;
                    case self::SECONDARY_COMPANY_RANK:
//                        $this->secondaryPayer = $payers['id'];
                        $insuranceArr['secondaryPayer'] = $payers['id'];
                        $data->setData(array('properties' => array('secondaryPayer' => $payers['id'], 'secondaryPayerName' => $payers['insuranceCompanyNameName'])));
                        break;
                    case self::TERTIARY_COMPANY_RANK:
//                        $this->tertiaryPayer = $payers['id'];
                        $insuranceArr['tertiaryPayer'] = $payers['id'];
                        $data->setData(array('properties' => array('tertiaryPayer' => $payers['id'], 'tertiaryPayerName' => $payers['insuranceCompanyNameName'])));
                        break;
                }
            }
        }
        $maxLevel = $this->getPayerMaxLevel($insuranceArr);
        $insuranceArr['currentLevel'] = self::PRIMARY_LEVEL;
        $insuranceArr['maxLevel'] = $maxLevel;
        $data->setData(array('properties' => array('currentLevel' => self::PRIMARY_LEVEL, 'maxLevel' => $maxLevel)));
        return;
    }

    /**
     * @description function will get patient payer max level based on its payer
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $insuranceProperties     
     */
    private function getPayerMaxLevel($insuranceProperties)
    {
        $maxLevel = '';
        /**
         * Author Kuldeep Singh, Date: 02-08-2017 11:30AM, JIRA Id: AP-546
         * Check valid insurances on patient appointment to be checkedin, should have relevent sequence i.e 
         *  1. Primary 2. Secondary 3. Tertiary
         * 
         */
        if (isset($insuranceProperties['secondaryPayer']) && !isset($insuranceProperties['primaryPayer'])) {
            throw new SynapExceptions(SynapExceptionConstants::PATIENT_SECONDARY_INSURANCE_WITH_NO_PRIMARY_INSURNACE,400);
        } elseif (isset($insuranceProperties['tertiaryPayer']) && !isset($insuranceProperties['secondaryPayer'])) {
            throw new SynapExceptions(SynapExceptionConstants::PATIENT_TERTIARY_INSURANCE_WITH_NO_SECONDARY_INSURNACE,400);
        }
        if (isset($insuranceProperties['tertiaryPayer'])) {
            $maxLevel = self::TERTIARY_LEVEL;
        } else if (isset($insuranceProperties['secondaryPayer'])) {
            $maxLevel = self::SECONDARY_LEVEL;
        } else if (isset($insuranceProperties['primaryPayer'])) {
            $maxLevel = self::PRIMARY_LEVEL;
        }
        return $maxLevel;
    }
    
    /**
     * Function will fetch data based on objectId
     * 
     * @param type $objectId
     * @param ServiceQueI $serviceQue
     * @return $serviceData
     */
    private function fetchObjectData($objectId, ServiceQueI $serviceQue) {
        //search key to get the objectData
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';

        //fetch objectData
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }

    /**
     * function to set config name and program name in patient encounter
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function setConfigAndProgramName($data, $serviceQue)
    {
        $encounterProperties = $data->getData('properties');        
        //set encounter config name
        if(isset($encounterProperties['encounterType']) && !empty($encounterProperties['encounterType'])){
            //fetch encounter config and set encounter config name
            $encounterConfigData = $this->fetchObjectData($encounterProperties['encounterType'], $serviceQue);
            $configName = $encounterConfigData['encounterName'];
            $data->setData(array('encounterConfigName'=>$configName),'properties');
        }

        //set encounter config name
        if(isset($encounterProperties['program']) && !empty($encounterProperties['program'])){
            //fetch encounter config and set encounter config name
            $programData = $this->fetchObjectData($encounterProperties['program'], $serviceQue);
            $programName = $programData['name'];            
            $data->setData(array('programName'=>$programName),'properties');
        }
        return;
    }
    
    /**
     * This function will update patient`s encounter date based on latest start date
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return  void
     */
    private function updatePatientEncounterDate($properties, ServiceQueI $serviceQue) {
        $patientId = $properties['patientId'];
        $encounterStartDate = $properties['encounterStartDate'];
        $searchKey = [];
        $searchKey[0]['type'] = 'patient';
        $searchKey[0]['conditions'][] = ['id' => $patientId];
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $respData = $resp['data']['response'];
        if (!empty($respData)) {
            foreach ($respData as $patientData) {
                if (!isset($patientData['lastEncounterDate']) || $this->isStartDateGreater($patientData, $encounterStartDate)) {
                    $updateArr = [];
                    $updateArr['lastEncounterDate'] = $encounterStartDate;
                    $this->updateobject($serviceQue, $patientData['id'], $updateArr);
                }
            }
        }
        return;
    }

    /**
     * Function will update object properties
     * 
     * @param type $statementId, $objectId, $arrProps
     * @return
     */
    private function updateobject($serviceQue, $objectId, $arrProps) {
        $updateObjs['conditions']['object'][0] = $objectId;
        $updateObjs['properties'] = $arrProps;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return;
    }

    /**
     * Function check does journal effective date is grater from last account`s txn date
     * 
     * @param type $patientData, $encounterStartDate
     * @return boolean
     */
    private function isStartDateGreater($patientData, $encounterStartDate) {
        $effectiveDateGreater = FALSE;
        if (isset($patientData['lastEncounterDate']) && !empty($patientData['lastEncounterDate']) && !empty($encounterStartDate)) {
            $dateTimeUtility = new DateTimeUtility();
            $dateDifference = $dateTimeUtility->getDateDiffObject($patientData['lastEncounterDate'], $encounterStartDate);
            if ($dateDifference->format("%R%a") > 0) {
                $effectiveDateGreater = TRUE;
            }
        }
        return $effectiveDateGreater;
    }

}
