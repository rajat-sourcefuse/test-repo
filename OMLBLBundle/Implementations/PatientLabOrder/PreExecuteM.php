<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientLabOrder;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI {

    /**
     * Function will validate data before execute create
     * 
     * @author Sourav Bhargava <sourav.bhargava@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        //AP-327 (A) begins
        $properties = $data->getData('properties');

        if (isset($properties['locationId'])) {
            $searchKey = [];
            $searchKey[0]['objectId'] = $properties['locationId'];
            $searchKey[0]['outKey'] = 'response';
            $locationInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
            if (!$locationInfo['data']['response']['isLocation']) {
                throw new SynapExceptions("Please send valid location Id.",400);
            }
        }//AP-327 (A) Ends
        return true;
    }

    /**
     * Function will validate data before execute delete
     * 
     * @author Sourav Bhargava <sourav.bhargava@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {


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
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        // if cptCode is set then update same cptCode in test
        if (!empty($data->getData('properties')['cptCode'])) {
            $parentId = $data->getData('conditions')['object'][0];
            $cptCode = $data->getData('properties')['cptCode'];
            $this->updateLabTestCpt($parentId, $cptCode, 'cptCode', $serviceQue);
        } else if (!empty($data->getData('properties')['testCodeLoinc'])) { // if testCodeLoinc is set then update same testCodeLoinc in test
            $parentId = $data->getData('conditions')['object'][0];
            $testCodeLoinc = $data->getData('properties')['testCodeLoinc'];
            $this->updateLabTestCpt($parentId, $testCodeLoinc, 'testCodeLoinc', $serviceQue);
        }
        $properties = $data->getData('properties');
        //AP-327 (b) begins
        if (isset($properties['locationId'])) {
            $searchKey = [];
            $searchKey[0]['objectId'] = $properties['locationId'];
            $searchKey[0]['outKey'] = 'response';
            $locationInfo = $serviceQue->executeQue("ws_oml_read", $searchKey);
            if (!$locationInfo['data']['response']['isLocation']) {
                throw new SynapExceptions("Please send valid location Id.",400);
            }
        }//AP327 (b) ends

        return true;
    }

    /**
     * Function will update same cptCode in test if cptCode is set
     * @param type $parentId
     * @param type $cptCode
     * @param type $serviceQue
     */
    private function updateLabTestCpt($parentId, $cptCode, $labTestProperty, $serviceQue) {
        $searchKey = [];
        $searchKey[0]['objectId'] = $parentId;
        $searchKey[0]['type'] = 'patientLabTest';
        $searchKey[0]['conditions'][] = array($labTestProperty => array('ISNOTNULL' => NULL));
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $testIdDataInDB = $aResp['data']['response'];
        if (!empty($testIdDataInDB)) {
            foreach ($testIdDataInDB as $value) {
                $deleteObj = [$value['id']];
                $labTestResp = $serviceQue->executeQue("ws_oml_delete", $deleteObj);
            }
        }
        foreach ($cptCode as $lCodeId) {
            $labTestInputData = array(
                'parent' => $parentId,
                'objectType' => 'patientLabTest',
                'properties' => array(
                    $labTestProperty => $lCodeId
                )
            );
            $labTestResp = $serviceQue->executeQue('ws_oml_create', $labTestInputData);
        }
    }

}
