<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientTxPlanLevel;

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
class PostExecuteM implements PostExecuteI
{


    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {

        return true;
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
        $this->updateCombineTitle($data, $serviceQue);
        
        $this->updateEndDate($data, $serviceQue);

        return true;
    }

    /**
     * This method checks if titles comes for update than fetch all children 
     * of that level and send that children to update to update combineTitle 
     * Property 
     * @param type $data
     * @param type $serviceQue
     */
    private function updateCombineTitle($data, $serviceQue)
    {

        if (isset($data->getData('properties')['title']) && !empty($data->getData('properties')['title'])) {

            $patientTxPlanLevelId = $data->getData('conditions')['object'][0];

            // OML READ REQUEST TO GET CHILDS PATIENT TREATMENT LEVEL
            $searchKey = [];
            $searchKey[0]['conditions'][] = array('parentId' => $patientTxPlanLevelId);
            $searchKey[0]['type'] = 'patientTxPlanLevel';
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevelChild = $serviceQue->executeQue("ws_oml_read", $searchKey);

            $patientTxPlanLevelChildResponse = $patientTxPlanLevelChild['data']['response'];

            if (!empty($patientTxPlanLevelChildResponse)) {
                // Update Each Child of patinet treatment plan level and udpate there status to inactive
                foreach ($patientTxPlanLevelChildResponse as $txPlanLevelChild) {

                    $updateObjs = array();
                    $updateObjs['conditions']['object'][0] = $txPlanLevelChild['id'];
                    $updateObjs['properties']['title'] = $txPlanLevelChild['title'];

                    $patientTxPlanLevelChild = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                }
            }
        }
    }

    
    /**
     * This method check if end date is coming for update than update all childs 
     * of that level
     * @param type $data
     * @param type $serviceQue
     * @return type
     */
    private function updateEndDate($data, $serviceQue)
    {

        if (isset($data->getData('properties')['endDate']) && !empty($data->getData('properties')['endDate'])) {

            $patientTxPlanLevelId = $data->getData('conditions')['object'][0];
            // OML READ REQUEST TO GET CHILDS PATIENT TREATMENT LEVEL
            $searchKey = [];
            $searchKey[0]['conditions'][] = array('parentId' => $patientTxPlanLevelId);
            $searchKey[0]['type'] = 'patientTxPlanLevel';
            $searchKey[0]['sendNullKey'] = true;
            $searchKey[0]['outKey'] = 'response';
            $patientTxPlanLevelChild = $serviceQue->executeQue("ws_oml_read", $searchKey);

            $patientTxPlanLevelChildResponse = $patientTxPlanLevelChild['data']['response'];

            if (!empty($patientTxPlanLevelChildResponse)) {
                // Update Each Child of patinet treatment plan level and update there 
                // end date
                foreach ($patientTxPlanLevelChildResponse as $txPlanLevelChild) {

                    if (!empty($txPlanLevelChild['endDate'])) {

                        $dateObj = new \DateTime('NOW');
                        $curentDateApi = $dateObj->format(DateTimeUtility::DATE_FORMAT);
                        $passedEndDate = DateTimeUtility::getDateApiFormat($data->getData('properties')['endDate']);

                        $endDateObj = DateTimeUtility::convertDateObject($passedEndDate, DateTimeUtility::DATE_FORMAT);
                        $childEndDateObj = DateTimeUtility::convertDateObject($txPlanLevelChild['endDate'], DateTimeUtility::DATE_FORMAT);

                        if ($childEndDateObj < $endDateObj) {
                            $newEndDate = $txPlanLevelChild['endDate'];
                        } else {
                            $newEndDate = $passedEndDate;
                        }
                    } else {
                        $newEndDate = $data->getData('properties')['endDate'];
                    }
                    
                    $updateObjs = array();
                    $updateObjs['conditions']['object'][0] = $txPlanLevelChild['id'];
                    $updateObjs['properties']['endDate'] = $newEndDate;

                    $patientTxPlanLevelChild = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                }
            }
        }
        
        return;
    }

}
