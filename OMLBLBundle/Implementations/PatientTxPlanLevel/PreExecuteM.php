<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientTxPlanLevel;

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

    const CREATEPROCESS = 'CREATEPROCESS';
    const UPDATEPROCESS = 'UPDATEPROCESS';

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

        if (isset($data->getData('properties')['parentId'])) {
            $parentId = $data->getData('properties')['parentId'];
        } else {
            $parentId = '';
        }


        $this->levelDateCheck($data, $serviceQue);

        $this->calculateCombineTitle($data, $serviceQue, $parentId);
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
        return true;
    }

    /**
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <sourabh.grover@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {

        $properties = $data->getData('properties');

        // Check If Properties array contains effectiveDate and one more any other property 
        // Than call checkProgressNote function 
        if (array_key_exists("effectiveDate", $properties)) {
            $this->checkProgressNote($data, $serviceQue);
        }

        $this->levelDateCheckUpdate($data, $serviceQue);

        if (isset($data->getData('properties')['title']) && !empty($data->getData('properties')['title'])) {
            // OML READ REQUEST TO GET CHILDS PATIENT TREATMENT LEVEL
            $searchKey = [];
            $searchKey[0]['objectId'] = $data->getData('conditions')['object'][0];
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevel = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanLevelResponse = $patientTxPlanLevel['data']['response'];

            if (!empty($patientTxPlanLevelResponse['parentId'])) {
                $parentId = $patientTxPlanLevelResponse['parentId'];
            } else {
                $parentId = '';
            }

            $this->calculateCombineTitle($data, $serviceQue, $parentId);
        }

        return true;
    }

    /**
     * Thi method checks the level which is creating has patient 
     * teatmnet plan level as a parent or patient teatmnet plan
     * @param type $data
     * @param type $serviceQue
     */
    private function levelDateCheck($data, $serviceQue)
    {

        // If Level which is creating has Patient Treatment Plan level as a parent
        if (isset($data->getData('properties')['parentId'])) {

            $patientTxPlanLevelId = $data->getData('properties')['parentId'];

            // OML READ REQUEST TO PATIENT TREATEMENT PLAN
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientTxPlanLevelId;
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevelInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanLevelInfoResponse = $patientTxPlanLevelInfo['data']['response'];

            // Calling to function set new end date if parent conatin new end date
            if (isset($patientTxPlanLevelInfoResponse['endDate']) && !empty($patientTxPlanLevelInfoResponse['endDate'])) {

                $this->setNewEndDate($patientTxPlanLevelInfoResponse['endDate'], $data);
            }

            // Set Parent Effective Date 
            isset($patientTxPlanLevelInfoResponse['effectiveDate']) ? $parentEffectiveDate = $patientTxPlanLevelInfoResponse['effectiveDate'] : $parentEffectiveDate = '';

            // Set Parent End Date 
            isset($patientTxPlanLevelInfoResponse['endDate']) ? $parentEndDate = $patientTxPlanLevelInfoResponse['endDate'] : $parentEndDate = '';


            // Set Child Effective Date 
            (isset($data->getData('properties')['effectiveDate'])) ? $childEffectiveDate = $data->getData('properties')['effectiveDate'] : $childEffectiveDate = '';

            // Set Child End Date 
            (isset($data->getData('properties')['endDate'])) ? $childEndDate = $data->getData('properties')['endDate'] : $childEndDate = '';

            $this->compareDate($parentEndDate, $parentEffectiveDate, $childEffectiveDate, $childEndDate);
        } else {


            // If Level which is creating has Patient Treatment Plan  as a parent
            $patientTxPlanId = $data->getData('parent');

            // OML READ REQUEST TO PATIENT TREATEMENT PLAN
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientTxPlanId;
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanInfoResponse = $patientTxPlanInfo['data']['response'];
            // calling to function set new end date 
            if (isset($patientTxPlanInfoResponse['endDate']) && !empty($patientTxPlanInfoResponse['endDate'])) {

                $this->setNewEndDate($patientTxPlanInfoResponse['endDate'], $data);
            }

            // Set Parent Effective Date 
            isset($patientTxPlanInfoResponse['effectiveDate']) ? $parentEffectiveDate = $patientTxPlanInfoResponse['effectiveDate'] : $parentEffectiveDate = '';

            // Set Parent End Date 
            isset($patientTxPlanInfoResponse['endDate']) ? $parentEndDate = $patientTxPlanInfoResponse['endDate'] : $parentEndDate = '';

            // Set Child Effective Date 
            (isset($data->getData('properties')['effectiveDate'])) ? $childEffectiveDate = $data->getData('properties')['effectiveDate'] : $childEffectiveDate = '';

            // Set Child End Date 
            (isset($data->getData('properties')['endDate'])) ? $childEndDate = $data->getData('properties')['endDate'] : $childEndDate = '';

            $this->compareDate($parentEndDate, $parentEffectiveDate, $childEffectiveDate, $childEndDate);
        }
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
     * This function checks if progress notes already exists with relation to patientTxPlanId
     * @param type $data
     * @param type $serviceQue
     * @return type
     * @throws SynapExceptions
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     */
    private function checkProgressNote($data, $serviceQue)
    {
        $condition = $data->getData('conditions');
        $patientTxPlanLevelId = $condition['object'][0];

        // Get the record of txPlanConfig based on passed $patientTxPlanLevelId
        $searchKey = [];
        $searchKey[0]['type'] = 'txProgressNote';
        $searchKey[0]['conditions'][] = array('patientTxPlanLevelId' => $patientTxPlanLevelId);
        $searchKey[0]['sendNullKey'] = true;
        $searchKey[0]['outKey'] = 'response';

        $txProgressNoteInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $txProgressNoteResponse = $txProgressNoteInfo['data']['response'];

        if (!empty($txProgressNoteResponse)) {
            throw new SynapExceptions(SynapExceptionConstants::PLANLEVEL_ALREADY_DOCUMENTED,400);
        }


        return;
    }

    /**
     * This method set new end date of level if parent level end date is less 
     * than current end date than set new end date in current request
     * @param type $parentEndDate
     * @param type $data
     * @return type
     */
    private function setNewEndDate($parentEndDate, $data)
    {
        $dateObj = new \DateTime('NOW');
        $curentDateApi = $dateObj->format(DateTimeUtility::DATE_FORMAT);
        $currentDateObj = DateTimeUtility::convertDateObject($curentDateApi, DateTimeUtility::DATE_FORMAT);

        $parentEndDateObj = DateTimeUtility::convertDateObject($parentEndDate, DateTimeUtility::DATE_FORMAT);

        // If parent end date is less than today date this loop cover 
        // Inactive status scenario
        if ($parentEndDateObj < $currentDateObj) {

            // Check if end date passed in level else set parent end date in current request
            if (isset($data->getData('properties')['endDate']) && !empty($data->getData('properties')['endDate'])) {
                $currentEndDateObj = DateTimeUtility::convertDateObject($data->getData('properties')['endDate'], DateTimeUtility::DATE_FORMAT);

                // Check if current end date passed in level is less than today date 
                // than keep that end date as it is else set parent end date in that
                if ($currentEndDateObj < $currentDateObj) {
                    $newEndDate = $data->getData('properties')['endDate'];
                } else {
                    $newEndDate = $parentEndDate;
                }
            } else {
                $newEndDate = $parentEndDate;
            }

            $data->setData(array('properties' => array('endDate' => $newEndDate)));
        } else {

            // If parent end date is not less than today date than check if end date 
            // is not set for current level than set parent end date in that 
            if (empty($data->getData('properties')['endDate'])) {
                $data->setData(array('properties' => array('endDate' => $parentEndDate)));
            }
        }

        return;
    }

    /**
     * This method set Data for combineTitle Property
     * @param type $data
     * @param type $serviceQue
     */
    private function calculateCombineTitle($data, $serviceQue, $parentId)
    {

        if (!empty($parentId)) {

            // OML READ REQUEST TO FIND PARENT OF PATIENT TREATEMENT PLAN LEVEL
            $patientTxPlanLevelId = $parentId;
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientTxPlanLevelId;
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevelInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanLevelInfoResponse = $patientTxPlanLevelInfo['data']['response'];

            $combinedTitle = $patientTxPlanLevelInfoResponse ['combineTitle'] . '/' . $data->getData('properties')['title'];
        } else {
            $combinedTitle = $data->getData('properties')['title'];
        }

        $data->setData(array('properties' => array('combineTitle' => $combinedTitle)));
    }

    /**
     * This function calculate parent and it's child effective date and end date 
     * and pass those dates to compare data function
     * @param type $data
     * @param type $serviceQue
     */
    private function levelDateCheckUpdate($data, $serviceQue)
    {

        $patientTxPlanLevelId = $data->getData('conditions')['object'][0];

        // OML READ REQUEST TO PATIENT TREATEMENT PLAN
        $searchKey = [];
        $searchKey[0]['objectId'] = $patientTxPlanLevelId;
        $searchKey[0]['sendNullKey'] = true;
        $searchKey[0]['outKey'] = 'response';
        $patientTxPlanLevelInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientTxPlanLevelInfoResponse = $patientTxPlanLevelInfo['data']['response'];



        // Treatment Plan level which is coming to edit has plan level as parent
        if (!empty($patientTxPlanLevelInfoResponse['parentId'])) {

            // OML READ REQUEST TO PATIENT TREATEMENT PLAN
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientTxPlanLevelInfoResponse['parentId'];
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevelData = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanLevelResponse = $patientTxPlanLevelData['data']['response'];


            // Calling to function set new end date 
            if (isset($patientTxPlanLevelResponse['endDate']) && !empty($patientTxPlanLevelResponse['endDate'])) {
                $this->setNewEndDate($patientTxPlanLevelResponse['endDate'], $data);
            }


            // Set Parent Effective Date 
            isset($patientTxPlanLevelResponse['effectiveDate']) ? $parentEffectiveDate = $patientTxPlanLevelResponse['effectiveDate'] : $parentEffectiveDate = '';

            // Set Parent End Date 
            isset($patientTxPlanLevelResponse['endDate']) ? $parentEndDate = $patientTxPlanLevelResponse['endDate'] : $parentEndDate = '';

            // Set Child Effective Date 
            (isset($data->getData('properties')['effectiveDate'])) ? $childEffectiveDate = $data->getData('properties')['effectiveDate'] : $childEffectiveDate = $patientTxPlanLevelInfoResponse['effectiveDate'];

            // Set Child End Date 
            (isset($data->getData('properties')['endDate'])) ? $childEndDate = $data->getData('properties')['endDate'] : $childEndDate = $patientTxPlanLevelInfoResponse['endDate'];

            $this->compareDate($parentEndDate, $parentEffectiveDate, $childEffectiveDate, $childEndDate);
        } else {

            // Treatmet Plan level which is coming to edit has Treatment plan as parent
            $planId = $patientTxPlanLevelInfoResponse['patientTxPlanId'];

            // OML READ REQUEST TO PATIENT TREATEMENT PLAN
            $searchKey = [];
            $searchKey[0]['objectId'] = $planId;
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanData = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientTxPlanResponse = $patientTxPlanData['data']['response'];

            // calling to function set new end date 
            if (isset($patientTxPlanResponse['endDate']) && !empty($patientTxPlanResponse['endDate'])) {
                $this->setNewEndDate($patientTxPlanResponse['endDate'], $data);
            }

            // Set Parent Effective Date 
            isset($patientTxPlanResponse['effectiveDate']) ? $parentEffectiveDate = $patientTxPlanResponse['effectiveDate'] : $parentEffectiveDate = '';

            // Set Parent End Date 
            isset($patientTxPlanResponse['endDate']) ? $parentEndDate = $patientTxPlanResponse['endDate'] : $parentEndDate = '';

            // Set Child Effective Date 
            (isset($data->getData('properties')['effectiveDate'])) ? $childEffectiveDate = $data->getData('properties')['effectiveDate'] : $childEffectiveDate = $patientTxPlanLevelInfoResponse['effectiveDate'];

            // Set Child End Date 
            (isset($data->getData('properties')['endDate'])) ? $childEndDate = $data->getData('properties')['endDate'] : $childEndDate = $patientTxPlanLevelInfoResponse['endDate'];

            $this->compareDate($parentEndDate, $parentEffectiveDate, $childEffectiveDate, $childEndDate);
        }
    }

    /**
     * Function compares date if child effective and end date donot lie between 
     * parent effective date and end date than throw exception
     * @param type $parentEndDate
     * @param type $parentEffectiveDate
     * @param type $childEffectiveDate
     * @param type $childEndDate
     * @return type
     * @throws SynapExceptions
     */
    private function compareDate($parentEndDate, $parentEffectiveDate, $childEffectiveDate, $childEndDate)
    {

        // If parent effective date and end date is not empty 
        // than check child effective date and end date must be between parent effective date and end date 
        if (!empty($parentEndDate) && !empty($parentEffectiveDate)) {

            // Convert All Dates Into Object For Comparison 
           
            $parentEffectiveDateObj = DateTimeUtility::convertDateObject($parentEffectiveDate, DateTimeUtility::DATE_FORMAT);
            $parentEndDateObj = DateTimeUtility::convertDateObject($parentEndDate, DateTimeUtility::DATE_FORMAT);
 
            if (!empty($childEffectiveDate)) {
                 $childEffectiveDateObj = DateTimeUtility::convertDateObject($childEffectiveDate, DateTimeUtility::DATE_FORMAT);
                if (($childEffectiveDateObj < $parentEffectiveDateObj) || ($childEffectiveDateObj > $parentEndDateObj)) {
                    throw new SynapExceptions(SynapExceptionConstants::EFFECTIVEDATE_BETWEEN_PARENTDATE,400);
                }
            }

            if (!empty($childEndDate)) {
                $childEndDateObj = DateTimeUtility::convertDateObject($childEndDate, DateTimeUtility::DATE_FORMAT);
                if (($childEndDateObj < $parentEffectiveDateObj) || ($childEndDateObj > $parentEndDateObj)) {
                    throw new SynapExceptions(SynapExceptionConstants::ENDDATE_BETWEEN_PARENTDATE,400);
                }
            }
        }
        return;
    }

}
