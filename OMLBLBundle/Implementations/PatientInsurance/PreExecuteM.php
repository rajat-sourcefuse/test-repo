<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientInsurance;

use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\Utilities\SessionUtility;
/**
 * BL class for preExecute of PatientWarning.
 *
 * @author Vinod Vaishnav <vinod.vaishnav@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI {

    const RANK = 'insuranceCompanyRank';
    const TERTIARY_RANK = 'metaInsuranceCompanyRank:three';
    const SECONDARY_RANK = 'metaInsuranceCompanyRank:two';
    const PRIMARY_RANK = 'metaInsuranceCompanyRank:one';
    const MEDICAID_CODE = 'medicaidCode';
    const MEDICAID_NETWORK_PAYER_ID = 'metaNetworkPayer:456';
    const MEDICARE_NETWORK_PAYER_ID = 'metaNetworkPayer:123';
    const INS_TYPE_CODE = 'insuranceTypeCode';
    const CURRENT_OBJ = 'patientInsurance';
    const CLAIM_NEW_STATUS = 'metaClaimStatus:new';
    const CLAIM_DENIED_STATUS = 'metaClaimStatus:denied';
    const CLAIM_REJECTED_STATUS = 'metaClaimStatus:rejected';

    private $otherPayerArr = ['name' => 'otherName',
        'networkPayerId' => 'otherNetworkPayerId',
        'address1' => 'otherAddress1',
        'address2' => 'otherAddress2',
        'state' => 'otherState',
        'city' => 'otherCity',
        'zip' => 'otherZip',
        'contact' => 'otherContact'];

    /**
     * This function validates data before execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $startDate = $data->getData('properties')['coverageStartDate'];
        $endDate = (!empty($data->getData('properties')['coverageEndDate'])) ? $data->getData('properties')['coverageEndDate'] : '';
        $patientId = $data->getData('properties')['patientId'];
        $program = '';
        $insuranceRank = $data->getData('properties')[self::RANK];
        $props = $data->getData('properties');
        $claimId = (isset($props['claimId']) ? $props['claimId'] : '');
        $this->insuranceAddEditRestrict($serviceQue, $insuranceRank, $patientId, $claimId);
        $this->checkPrimaryActive($insuranceRank, $startDate, $endDate, $patientId, $serviceQue, $program, '', $claimId);
        $this->insuranceCreate($data, $serviceQue);
        if (!empty($claimId)) { // versionedInsuranceClaimMaxLevelUpdate
            $this->versionedInsuranceClaimMaxLevelUpdate($serviceQue, $insuranceRank, $claimId);
        }
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        $patientInsuranceId = $data->getData();
        $searchKey[0]['objectId'] = $patientInsuranceId;
        $searchKey[0]['outKey'] = 'response';
        $patientInsuranceInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $insuranceInfo = $patientInsuranceInfo['data']['response'];
        $this->insuranceDeleteRestrict($serviceQue, $insuranceInfo);
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        $condition = $data->getData('conditions');
        $properties = $data->getData('properties');
        $patientInsuranceId = $condition['object'][0];
        $searchKey[0]['objectId'] = $patientInsuranceId;
        $searchKey[0]['outKey'] = 'response';
        $patientInsuranceInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $insuranceInfo = $patientInsuranceInfo['data']['response'];
        $patientId = $insuranceInfo['patientId'];
        $program = '';
        $claimId = isset($properties['claimId']) ? $properties['claimId'] : (isset($insuranceInfo['claimId']) ? $insuranceInfo['claimId'] : '');
        if (!empty($claimId)) {
            $this->versionedInsuranceUpdateRestrict($serviceQue, $claimId);
        }
        if (!empty($properties[self::RANK])) {
            $this->insuranceAddEditRestrict($serviceQue, $properties[self::RANK], $patientId, $claimId, $patientInsuranceId);
        }
        if (!empty($properties['coverageStartDate']) || !empty($properties['coverageEndDate'])) {
            if (!empty($properties['coverageStartDate'])) {
                $startDate = $properties['coverageStartDate'];
            }
            if (!empty($properties['coverageEndDate'])) {
                $endDate = $properties['coverageEndDate'];
            }
            if (empty($startDate)) {
                $startDate = $insuranceInfo['coverageStartDate'];
            }
            if (empty($endDate)) {
                $endDate = (!empty($insuranceInfo['coverageEndDate'])) ? $insuranceInfo['coverageEndDate'] : '';
            }
        } else {
            if (empty($startDate)) {
                $startDate = $insuranceInfo['coverageStartDate'];
            }
            if (empty($endDate)) {
                $endDate = (!empty($insuranceInfo['coverageEndDate'])) ? $insuranceInfo['coverageEndDate'] : '';
            }
        }

        if (!empty($properties[self::RANK])) {
            $rank = $properties[self::RANK];
        } else {
            $rank = $insuranceInfo[self::RANK];
        }
        if (!empty($properties[self::RANK])) {
            $insuranceRank = $properties[self::RANK];
            $this->checkPrimaryActive($insuranceRank, $startDate, $endDate, $insuranceInfo['patientId'], $serviceQue, $program, $patientInsuranceId, $claimId);
        } else {

            if (!empty($properties['coverageStartDate']) || !empty($properties['coverageEndDate']) || !empty($program)) {

                if (!empty($rank)) {
                    $this->checkPrimaryActive($rank, $startDate, $endDate, $insuranceInfo['patientId'], $serviceQue, $program, $patientInsuranceId, $claimId);
                }
            } else {
                if (!empty($rank)) {
                    $this->checkPrimaryActive($rank, $startDate, $endDate, $insuranceInfo['patientId'], $serviceQue, $program, $patientInsuranceId, $claimId);
                }
            }
        }

        if ($patientInsuranceInfo['status']) {
            $searchKey[0]['objectId'] = $patientId;
            $searchKey[0]['outKey'] = 'response';
            $patientInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);

            if ($patientInfo['status']) {

                $dob = $patientInfo['data']['response']['dob'];
                if (array_key_exists('coverageStartDate', $properties) && array_key_exists('coverageEndDate', $properties)) {
                    $this->validateCoverageDates($properties['coverageStartDate'], $properties['coverageEndDate'], $dob);
                } else {
                    if (array_key_exists('coverageStartDate', $properties)) {
                        $startDate = $properties['coverageStartDate'];
                        $endDate = (!empty($insuranceInfo['coverageEndDate'])) ? $insuranceInfo['coverageEndDate'] : '';
                        $this->validateCoverageDates($startDate, $endDate, $dob);
                    }
                    if (array_key_exists('coverageEndDate', $properties)) {
                        $endDate = $properties['coverageEndDate'];
                        $startDate = $insuranceInfo['coverageStartDate'];
                        $this->validateCoverageDates($startDate, $endDate, $dob);
                    }
                }
            } else {
                throw new SynapExceptions(SynapExceptionConstants::PARENT_OBJECT_ID_DOES_NOT_EXIST, 404);
            }
        } else {
            throw new SynapExceptions(SynapExceptionConstants::OBJECT_ID_DOES_NOT_EXIST, 404);
        }

        return;
    }

    /**
     * Function validates the coverage date, medicaid code and insurance
     * type code.
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function insuranceCreate($data, $serviceQue) {
        $properties = $data->getData('properties');
        $searchKey = [];
        $searchKey[0]['objectId'] = $properties['patientId'];
        $searchKey[0]['outKey'] = 'response';
        $patientInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);

//        get DOB from demographics
        if ($patientInfo['status']) {
            $dob = $patientInfo['data']['response']['dob'];
        } else {
            throw new SynapExceptions(SynapExceptionConstants::PARENT_OBJECT_ID_DOES_NOT_EXIST, 404);
        }

        $properties['coverageEndDate'] = (!empty($properties['coverageEndDate'])) ? $properties['coverageEndDate'] : '';
// Validate Insurance Comapny Record
        $this->validateCoverageDates($properties['coverageStartDate'], $properties['coverageEndDate'], $dob);

// fetching insurance company data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $properties['insuranceCompanyName'];
        $searchKey[0]['outKey'] = 'response';
        $insCompResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $insCompDBData = $insCompResp['data']['response'];

// Insurance company rank is secondry
        if ($properties[self::RANK] == self::SECONDARY_RANK &&
                !empty($insCompDBData['networkPayerId'])) {
// if insurance company medicaid then medicaidcode is required
            if ($insCompDBData['networkPayerId'] == self::MEDICAID_NETWORK_PAYER_ID) {
                if (empty($properties[self::MEDICAID_CODE])) {
                    throw new SynapExceptions(SynapExceptionConstants::MEDIACID_CODE_IS_REQUIRED, 400);
                }
            }
// if insurance company medicare then insuranceTypeCode is required
            if ($insCompDBData['networkPayerId'] == self::MEDICARE_NETWORK_PAYER_ID) {
                if (empty($properties[self::INS_TYPE_CODE])) {
                    throw new SynapExceptions(SynapExceptionConstants::INSURANCE_TYPE_CODE_IS_REQUIRED, 400);
                }
            }
        }
    }

    /**
     * This function compare coverage date.
     * coverageStartDate cannot be more than current date 
     * coverageEndDate cannot be less than current date
     * 
     * @param string $startDate date with YYYY-MM-DD
     * @param string $endDate date with YYYY-MM-DD
     * @return void
     */
    private function validateCoverageDates($startDate, $endDate, $dob) {

// dates are required
        if (empty($startDate)) {
            throw new SynapExceptions(SynapExceptionConstants::COVERAGE_START_DATE_REQUIRED, 400);
        }

//TODO- nitesh- use DateTimeUtility::convertDateObject() method for $dob
        $dob = new \DateTime($dob);
//        $dob = $dob->format(DateTimeUtility::DATE_FORMAT);
        $startDate = DateTimeUtility::convertDateObject($startDate);


//TODO-  coverageStartDate and coverageEndDate can not be less than DOB
        if ($startDate < $dob) {
            throw new SynapExceptions(SynapExceptionConstants::COVERAGE_START_DATE_SHOULD_BE_MORE_THAN_DOB, 400);
        }


        if (!empty($endDate)) {
            $endDate = DateTimeUtility::convertDateObject($endDate);

            if ($endDate < $dob) {
                throw new SynapExceptions(SynapExceptionConstants::COVERAGE_END_DATE_SHOULD_BE_MORE_THAN_DOB, 400);
            }

            if ($endDate < $startDate) {
                throw new SynapExceptions(SynapExceptionConstants::COVERAGE_START_DATE_SHOULD_BE_LESS_THAN_END_DATE, 400);
            }
        }
    }

    /**
     * This function versionedInsuranceClaimMaxLevelUpdate($serviceQue, $insuranceRank, $claimId)
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $serviceQue, $insuranceRank, $patientId, $claimId, $patientInsuranceId
     * @throws SynapExceptions
     */
    private function versionedInsuranceClaimMaxLevelUpdate($serviceQue, $insuranceRank, $claimId) {
        $arrTierWiseLevel = array(self::PRIMARY_RANK => 1, self::SECONDARY_RANK => 2, self::TERTIARY_RANK => 3);
        $updateObjs = array();
        $updateObjs['conditions']['object'][0] = $claimId;
        $updateObjs['properties']['maxLevel'] = $arrTierWiseLevel[$insuranceRank];
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
    }

    /**
     * This function versionedInsuranceUpdateRestrict
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $serviceQue, $insuranceRank, $patientId, $claimId, $patientInsuranceId
     * @throws SynapExceptions
     */
    private function versionedInsuranceUpdateRestrict($serviceQue, $claimId) {
        $objConditions = array('id' => $claimId, 'status' => array('IN' => array(self::CLAIM_NEW_STATUS, self::CLAIM_DENIED_STATUS, self::CLAIM_REJECTED_STATUS)));
        $claimData = $this->fetchObjectByCondition('patientClaim', $objConditions, $serviceQue);
        if (!$claimData) { // Insurance can only be edited when claim status is New, Rejected, or Denied.
            throw new SynapExceptions(SynapExceptionConstants::VERSIONED_INSURNACE_UPDATE_RESTRICTED, 400);
        }
    }

    /**
     * This function insuranceAddEditRestrict
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $serviceQue, $insuranceRank, $patientId, $claimId, $patientInsuranceId
     * @throws SynapExceptions
     */
    private function insuranceAddEditRestrict($serviceQue, $insuranceRank, $patientId, $claimId = '', $patientInsuranceId = '') {
        $objConditions = array(self::RANK => $insuranceRank, 'patientId' => $patientId, 'claimId' => (!empty($claimId) ? $claimId : array('ISNULL' => TRUE)));
        if (!empty($patientInsuranceId)) {
            $objConditions['id'] = array('NE' => $patientInsuranceId);
        }
        if (!empty($claimId)) { //  check same tier duplicate exist for versioned insurance
            $currentTierData = $this->fetchObjectByCondition(self::CURRENT_OBJ, $objConditions, $serviceQue);
            if ($currentTierData) {
                throw new SynapExceptions(SynapExceptionConstants::VERSIONED_SAME_INSURANCE_TIER_ALREADY_EXIST, 400);
            }
        }
        if ($insuranceRank != self::PRIMARY_RANK) {
            $arrTier = array(self::SECONDARY_RANK => self::PRIMARY_RANK, self::TERTIARY_RANK => self::SECONDARY_RANK);
            $objConditions[self::RANK] = $arrTier[$insuranceRank];
            $insuranceData = $this->fetchObjectByCondition(self::CURRENT_OBJ, $objConditions, $serviceQue);
            if (!$insuranceData) { // check primary tier exist for seconday and tertiary insurance
                throw new SynapExceptions(SynapExceptionConstants::VERSIONED_INSURNACE_LEVEL_NOT_EXIST, 400, array('tierLevel' => ($insuranceRank == self::TERTIARY_RANK ? 'secondary' : 'primary')));
            }
        }
    }

    /**
     * This function insuranceDeleteRestrict
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $serviceQue, $patientInsuranceId
     * @throws SynapExceptions
     */
    private function insuranceDeleteRestrict($serviceQue, $insuranceInfo) {
        $insuranceRank = $insuranceInfo[self::RANK];
        if ($insuranceRank != self::TERTIARY_RANK) {
            $arrTier = array(self::PRIMARY_RANK => self::SECONDARY_RANK, self::SECONDARY_RANK => self::TERTIARY_RANK);
            $objConditions = array(self::RANK => $arrTier[$insuranceRank], 'patientId' => $insuranceInfo['patientId'], 'claimId' => (!empty($insuranceInfo['claimId']) ? $insuranceInfo['claimId'] : array('ISNULL' => TRUE)));
            $insuranceData = $this->fetchObjectByCondition(self::CURRENT_OBJ, $objConditions, $serviceQue);
            if ($insuranceData) { // check does upper level insurance tier exist,if Yes then restrict delete operation
                throw new SynapExceptions(SynapExceptionConstants::VERSIONED_INSURNACE_UPPER_LEVEL_EXIST, 400, array('tierLevel' => ($insuranceRank == self::PRIMARY_RANK ? 'secondary' : 'tertiary')));
            }
        }
        /* decrement  max level by 1 */
        if (!empty($insuranceInfo['claimId'])) {
            $claimConditions = array('id' => $insuranceInfo['claimId'], 'maxLevel' => array('GT' => 0));
            $claimData = reset($this->fetchObjectByCondition('patientClaim', $claimConditions, $serviceQue));
            if ($claimData) {
                $arrTierWiseLevel = array(self::PRIMARY_RANK => 'primaryPayerProfileId', self::SECONDARY_RANK => 'secondaryPayerProfileId', self::TERTIARY_RANK => 'tertiaryPayerProfileId');
                $updateObjs = array();
                $updateObjs['conditions']['object'][0] = $insuranceInfo['claimId'];
                $updateObjs['properties']['maxLevel'] = --$claimData['maxLevel'];
                $updateObjs['properties'][$arrTierWiseLevel[$insuranceRank]] = NULL;
                $serviceQue->executeQue("ws_oml_update", $updateObjs);
            }
        }
    }

    /**
     * This function Primary Insurance records active during the same date range
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function checkPrimaryActive($insuranceRank, $startDate, $endDate, $patientId, $serviceQue, $program = '', $patientInsuranceId = '', $claimId = '') {
// fetching insurance data from db
        $searchKey = [];
        $searchKey[0]['type'] = self::CURRENT_OBJ;
        if (!empty($endDate)) {

            $searchKey[0]['conditions'] = array(
                array(
                    array('coverageStartDate' => array('GE' => $startDate)),
                    array('coverageStartDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('coverageEndDate' => array('GE' => $startDate)),
                    array('coverageEndDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('coverageStartDate' => array('LE' => $startDate)),
                    array('coverageEndDate' => array('GE' => $endDate))
                ),
                'OR',
                array(
                    array('coverageStartDate' => array('LE' => $startDate)),
                    array('coverageEndDate' => array('ISNULL' => true))
                )
            );
        } else {

            $searchKey[0]['conditions'] = array(
                array(
                    array('coverageStartDate' => array('LE' => $startDate)),
                    array('coverageEndDate' => array('GE' => $startDate))
                )
                ,
                'OR',
                array(
                    array('coverageStartDate' => array('GE' => $startDate))
                )
                ,
                'OR',
                array(
                    array('coverageStartDate' => array('LE' => $startDate)),
                    array('coverageEndDate' => array('ISNULL' => true))
                )
            );
        }
        $searchKey[0]['conditions'][] = [self::RANK => $insuranceRank];
        $searchKey[0]['conditions'][] = ['patientId' => $patientId];

        if (!empty($patientInsuranceId)) {
            $searchKey[0]['conditions'][] = array('id' => array('NE' => $patientInsuranceId));
        }
        if (!empty($claimId)) {
            $searchKey[0]['conditions'][] = array('claimId' => $claimId);
        } else {
            $searchKey[0]['conditions'][] = array('claimId' => array('ISNULL' => TRUE));
        }

        $searchKey[0]['outKey'] = 'response';
        $insResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $insDBData = $insResp['data']['response'];
//        If response comes based on date
        if (!empty($insDBData[0])) {
            throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_ACTIVE_INSURANCE_PER_TIER, 400);
        }
    }

    /**
     * Function will fetch the object data based on conditions
     * 
     * @param type $objectName, $objConditions, $serviceQue, $requiredAdditionalInfo
     * @return $objectData
     */
    private function fetchObjectByCondition($objectName, $objConditions, $serviceQue, $requiredAdditionalInfo = TRUE) {
        $searchKey = [];
        $searchKey[0]['type'] = $objectName;
        $searchKey[0]['conditions'][] = $objConditions;
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['requiredAdditionalInfo'] = $requiredAdditionalInfo;
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }

}
