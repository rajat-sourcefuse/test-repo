<?php

namespace SynapEssentials\OMLBLBundle\Implementations\WorkList;

/**
 * BL class for preExecute of PatientWarning.
 *
 * @author Sourav Bhargava <sourav.bhargava@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \SynapEssentials\Utilities\SessionUtility;
use \SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;
use \SynapEssentials\WorkFlowBundle\utilities\workListApproverUtility;
use \SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use \SynapEssentials\OMLBundle\Services\ServiceQue;

class PreExecuteM implements PreExecuteI
{

    private $obj;
    private $serviceContainer;

    public function __construct()
    {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return vodi
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {

        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * This function validates data before execute update
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $dat = $data->getAll();
        //if trying to mark skip/complete 
        if (isset($dat['properties']['status']) && (($dat['properties']['status'] == 'metaWorklistStatus:SKIPPED') || ($dat['properties']['status'] == 'metaWorklistStatus:COMPLETE'))) {
            //find the worklist apporovals
            $count = count($dat['conditions']['object']);
            $wlAO = new workListApproverUtility($this->serviceContainer);
            foreach ($dat['conditions']['object'] as $wl) {
                
                if (!$wlAO->checkApprovalProcessComplete($wl)) {
                    //if worklist is not approved
                    if (count($dat['conditions']['object']) == 1) {
                       
                        //if only one object has been passed then update the query
                        $updateArr = array("status" => 'metaWorklistStatus:PREAPPROVAL');
                        $data->setData($updateArr, 'properties');
                        $wlAO->markNextPending($dat['conditions']['object']);
                    } else {
                        //else throw error
                        throw new SynapExceptions("You cannot mark a worklist as complete/skip with approval pending.",400);
                    }
                }
            }
        }
        // validate BL
        $sessionUtil = SessionUtility::getInstance();
        $organizationEmployeeId = $sessionUtil->getOrganizationEmployeeId();
        $patientId = $sessionUtil->getPatientId();
        $addData = array();

        if (isset($organizationEmployeeId)) {
            $addData['properties']['authorEmployee'] = $organizationEmployeeId;
        } elseif (isset($patientId)) {
            $addData['properties']['authorPatient'] = $patientId;
        }

        $data->setData($addData);


        $return = $this->validate($data, $serviceQue);
 
        return $return;
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
     * This function validates input data for BL.
     * 
     * @param array $properties
     * @return void
     */
    private function validate($data, $serviceQue)
    {

        $properties = $data->getData('properties');
        $conditions = $data->getData('conditions');


        $worklistID = (string) $conditions['object'][0];
        $worklist = array();
        $worklist[0]['objectId'] = $worklistID;
        $worklist[0]['outKey'] = "worklist";
        $WL = $serviceQue->executeQue("ws_oml_read", $worklist);
        $WL = $WL['data'];

        $wla = false;
        $sessionUtil = SessionUtility::getInstance();
        $organizationEmployeeId = $sessionUtil->getOrganizationEmployeeId();
        $patientId = $sessionUtil->getPatientId();

        if (isset($organizationEmployeeId)) {

            if (isset($WL['worklist']['assigneeEmployee']) && (in_array($organizationEmployeeId, $WL['worklist']['assigneeEmployee']))) {

                $wla = true;
            }
        } else if (isset($patientId)) {

            if (isset($WL['worklist']['assigneePatient']) && (in_array($patientId,$WL['worklist']['assigneePatient']))) {

                $wla = true;
            }
        } elseif (empty($WL['worklist']['assigneePatient']) && empty($WL['worklist']['assigneeEmployee'])) {
            $wla = true;
        }

        return $wla;
    }

}
