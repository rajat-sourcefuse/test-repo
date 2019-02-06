<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientTxPlan;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

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
        // more than one org wide plan
        $programSpecific = false;
        if (!empty($data->getData('properties'))) {
            $txPlanConfigId = $data->getData('properties')['txPlanConfigId'];
            $searchKey = [];
            $searchKey[0]['objectId'] = $txPlanConfigId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $txConfig = $resp['data']['response'];
            $countProgram = $txConfig['programId'];

            // config should not be expired
            if (!empty($txConfig['endDate'])) {
                $endDateObj = DateTimeUtility::convertDateObject($txConfig['endDate']);
                $today = new \DateTime();
                if ($endDateObj < $today) {
                    throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_EXPIRED,400);
                }
            }

            // $this->checkPrimaryPatientTax($patientTxPlanResp, $effectiveDate, $endDate);
            $this->checkPrimaryPatientTaxCreate($data, $serviceQue);
        }




        // A program cann't be assigned to more than one level
        // If a Program is selected at Level 1 of an Integrated Treatment Plan,
        // then that Program cannot be selected at another Level 1 within that plan.
        if (!empty($data->getData('properties')['patientTxPlanLevel'])) {
            $txPlanLevel = $data->getData('properties')['patientTxPlanLevel'];
            $moreThanOneProg = $isSameProgMoreThanOne = 0;
            $levelProgIdArr = [];

            for ($j = 0; $j < count($txPlanLevel); $j++) {
                if (!empty($txPlanLevel[$j]['program'])) {
                    $moreThanOneProg++;

                    // prog id assigned to the levels
                    if (!in_array($txPlanLevel[$j]['program'], $levelProgIdArr)) {
                        $levelProgIdArr[] = $txPlanLevel[$j]['program'];
                    } else {
                        $isSameProgMoreThanOne++;
                    }
                }
                // Not prog specific then no prog be assigned to levels
                if ($countProgram == 1 && $moreThanOneProg > 0) {
                    throw new SynapExceptions(SynapExceptionConstants::PROGRAM_NOT_ASSIGNED_TX_PLAN_LEVEL,400);
                }
                // if prog specific then same prog can not assign to more than
                // one level
                if ($countProgram > 1 && $isSameProgMoreThanOne) {
                    throw new SynapExceptions(SynapExceptionConstants::SAME_PROGRAM_NOT_TX_PLAN_LEVEL,400);
                }
            }
        }
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
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {

        $this->checkPrimaryPatientTaxUpdate($data, $serviceQue);

        $properties = $data->getData('properties');

        // Check If Properties array contains endDate and one more any other property 
        // Than call checkProgressNote function 
        if (array_key_exists("endDate", $properties)) {

            unset($properties['endDate']);
            unset($properties['customProperty']);
            if (!empty($properties)) {
                $this->checkProgressNote($data, $serviceQue);
            }
        }

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
        $patientTxPlanId = $condition['object'][0];

        // Get the record of txPlanConfig based on passed Program Id
        $searchKey = [];
        $searchKey[0]['type'] = 'txProgressNote';
        $searchKey[0]['conditions'][] = array('objectPath' => array('SUBTREE' => $patientTxPlanId));
        $searchKey[0]['sendNullKey'] = true;
        $searchKey[0]['outKey'] = 'response';

        $txProgressNoteInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $txProgressNoteResponse = $txProgressNoteInfo['data']['response'];

        if (!empty($txProgressNoteResponse)) {
            throw new SynapExceptions(SynapExceptionConstants::PLAN_ALREADY_DOCUMENTED,400);
        }


        return;
    }

    /**
     * This function check if treatment plan is already created with in provided
     * effective date and end date
     * @param type $data
     * @param type $serviceQue
     * @return type
     * @throws SynapExceptions
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     */
    private function checkPrimaryPatientTaxCreate($data, $serviceQue)
    {

        $txPlanConfigId = $data->getData('properties')['txPlanConfigId'];
        $objectType = $data->getData('objectType');
        // Set  Search Conditions 
        $searchKey = [];
        $searchKey[0]['conditions'][] = ['txPlanConfigId' => $txPlanConfigId];
        // If Program Is Coming than search for that program else search for empty program
        if (!empty($data->getData('properties')['program'])) {
            $searchKey[0]['conditions'][] = ['program' => $data->getData('properties')['program']];
        } else {
            $searchKey[0]['conditions'][] = ['program' => array('isnull' => null)];
        }

        if (!empty($data->getData('parent'))) {
            $searchKey[0]['conditions'][] = ['patientId' => $data->getData('parent')];
        }
        // Set status property as true
        $searchKey[0]['conditions'][] = ['status' => true];
        $searchKey[0]['type'] = $objectType;

        $effectiveDate = $data->getData('properties')['effectiveDate'];
        $endDate = (!empty($data->getData('properties')['endDate'])) ? $data->getData('properties')['endDate'] : '';

        if (!empty($endDate)) {

            $searchKey[0]['conditions'][] = array(
                array(
                    array('effectiveDate' => array('GE' => $effectiveDate)),
                    array('effectiveDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('endDate' => array('GE' => $effectiveDate)),
                    array('endDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('GE' => $endDate))
                )
                , 'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        } else {

            $searchKey[0]['conditions'][] = array(
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('GE' => $effectiveDate))
                )
                ,
                'OR',
                array(
                    array('effectiveDate' => array('GE' => $effectiveDate))
                )
                ,
                'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        }


        $searchKey[0]['outKey'] = 'response';


        $treatmentResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $treatmentDbData = $treatmentResp['data']['response'];

        // If patient treatment plan already exists between
        if (!empty($treatmentDbData[0])) {
            throw new SynapExceptions(SynapExceptionConstants::PATIENT_TREATMENT_PLAN_ALREADY_EXISTS);
        }

        return;
    }

    /**
     * This function check if treatment plan is already created with in provided
     * effective date and end date
     * @param type $data
     * @param type $serviceQue
     * @return type
     * @throws SynapExceptions
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     */
    private function checkPrimaryPatientTaxUpdate($data, $serviceQue)
    {
        $condition = $data->getData('conditions');
        $properties = $data->getData('properties');

        $patientTxPlanId = $condition['object'][0];
        $searchKey[0]['objectId'] = $patientTxPlanId;
        $searchKey[0]['sendNullKey'] = 1;
        $searchKey[0]['outKey'] = 'response';
        $patientTxPlanData = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientTxPlanInfo = $patientTxPlanData['data']['response'];

        // Set Effective Date 
        $effectiveDate = (!empty($properties['effectiveDate'])) ? $properties['effectiveDate'] : $patientTxPlanInfo['effectiveDate'];
        $endDate = (!empty($properties['endDate'])) ? $properties['endDate'] : $patientTxPlanInfo['endDate'];

        $program = (!empty($properties['program'])) ? $properties['program'] : $patientTxPlanInfo['program'];
        $objectType = $data->getData('objectType');


        $searchKey = [];
        $searchKey[0]['type'] = $objectType;
        $searchKey[0]['conditions'][] = array('id' => array('NE' => $patientTxPlanId));
        
        $searchKey[0]['conditions'][] = ['patientId' => $patientTxPlanInfo['patientId']];


        // If Program Is Coming than search for that program else search for empty program
        if (!empty($program)) {
            $searchKey[0]['conditions'][] = ['program' => $program];
        } else {
            $searchKey[0]['conditions'][] = ['program' => array('isnull' => null)];
        }

        // Set status property as true
        $searchKey[0]['conditions'][] = ['status' => true];
        
        if (!empty($endDate)) {

            $searchKey[0]['conditions'][] = array(
                array(
                    array('effectiveDate' => array('GE' => $effectiveDate)),
                    array('effectiveDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('endDate' => array('GE' => $effectiveDate)),
                    array('endDate' => array('LE' => $endDate))
                ),
                'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('GE' => $endDate))
                )
                , 'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        } else {

            $searchKey[0]['conditions'][] = array(
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('GE' => $effectiveDate))
                )
                ,
                'OR',
                array(
                    array('effectiveDate' => array('GE' => $effectiveDate))
                )
                ,
                'OR',
                array(
                    array('effectiveDate' => array('LE' => $effectiveDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        }

        $searchKey[0]['outKey'] = 'response';

        $treatmentResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $treatmentDbData = $treatmentResp['data']['response'];

        // If patient treatment plan already exists between
        if (!empty($treatmentDbData[0])) {
            throw new SynapExceptions(SynapExceptionConstants::PATIENT_TREATMENT_PLAN_ALREADY_EXISTS,400);
        }

        return;
    }

}
