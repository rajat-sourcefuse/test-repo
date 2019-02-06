<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientEncounter;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

class PostExecuteM implements PostExecuteI
{

    const STATUS_COMPLETE = 'metaEncounterStatus:complete';

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // if status is completed then update isAdministered in medications
        if (isset($data->getData('properties')['status']) &&
                $data->getData('properties')['status'] == self::STATUS_COMPLETE) {
            $this->updateISAdministered($data, $serviceQue);
        }
        return;
    }

    /**
     * Update isAdministered in medications if status completed and If
     * patientMedication stop date is less than or equal to current date,
     * update the patientMedication.isAdministered as true
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     */
    private function updateISAdministered($data, $serviceQue)
    {
        $searchKey[0]['objectId'] = $data->getData('parent');
        $searchKey[0]['patientEncounterId'] = $data->getData('id');
        $searchKey[0]['type'] = 'patientMedication';
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientMedications = $resp['data']['response'];

        $todayObj = new \DateTime('NOW');

        $updateMedicatinArr = [];
        if (!empty($patientMedications)) {
            foreach ($patientMedications as $patientMedication) {
                if (!empty($patientMedication['stopDate'])) {
                    $stopDate = DateTimeUtility::convertDateObject($patientMedication['stopDate']);

                    if ($stopDate <= $todayObj) {
                        $updateMedicatinArr['conditions']['object'][0] = $patientMedication['id'];
                        $updateMedicatinArr['properties']['isAdministered'] = true;
                        $upResp = $serviceQue->executeQue("ws_oml_update", $updateMedicatinArr);
                    }
                }
            }
        }
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
     * @description function will perform some actions after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
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

}
