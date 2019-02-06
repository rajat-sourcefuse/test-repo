<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientPrintView;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Piyush Arora <piyush.arora@sourcefuse.com>
 */
use SynapEssentials\JasperBundle\Implementations\ReportGeneration;
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI {

    /**
     * Function will validate data before execute create
     * 
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param type $data contains request data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $properties = $data->getData('properties');

        /**
         * check if mandatory synapShotReport property is set in the request
         */
        if (empty($properties['synapShotReport'])) {
            throw new SynapExceptions(SynapExceptionConstants::SYNAPSHOT_MANDATORY,400);
        }

        // fetch the patient id from request
        $patientId = $data->getData('parent');

        // fetch objectType from the request
        $objectType = $data->getData('objectType');

        /**
         * variables for storing synapShot report, encounterIds and otherReports
         */
        $synapShotReports = '';
        $encResp = '';
        $otherReports = '';

        /**
         * Iterate through the synapshotReport(s) and store in variable as a comma seperated string
         */
        foreach ($properties['synapShotReport'] as $report) {
            $synapShotReports .= explode(':', $report)[1] . ',';
        }

        // remove the extra comma at the end, if any
        $synapShotReports = "[" . substr($synapShotReports, 0, strrpos($synapShotReports, ',')) . "]";

        // check if optional parameter others is set in the request
        if (isset($properties['otherReport']) && is_array($properties['otherReport'])) {
            foreach ($properties['otherReport'] as $other) {
                $otherReports .= explode(':', $other)[1] . ',';
            }

            // removing comma from the end
            $otherReports = "[" . substr($otherReports, 0, strrpos($otherReports, ',')) . "]";
        }

        // Instantiate the ReportGeneration class
        $reportSuc = new ReportGeneration();

        $dataReport = $reportSuc->getReport(array('synapShot' => $synapShotReports), array('id' => $patientId, 'encounterId' => $encResp, 'others' => $otherReports), $objectType, 'reportFile', true);
        if (is_array($dataReport)) {
            $data->setData(array('report' => $dataReport));
            $data->setData(array('properties' => array('reportFile' => $dataReport['id'])));
        } else {
            $data->setData(array('properties' => array('reportFile' => $dataReport)));
        }
    }

    /**
     * Function will validate data before execute delete
     * 
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param  $data
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
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        return true;
    }

}
