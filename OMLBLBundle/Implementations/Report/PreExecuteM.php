<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Report;

/*
 * BL class for preExecute of Report.
 *
 * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
 */

use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\ReportBundle\Implementation\ReportClass\ReportParameter;

class PreExecuteM implements PreExecuteI {

    const REPORT_PENDING_STATUS = 'metaReportStatus:pending';
    const REPORT_COMPLETE_STATUS = 'metaReportStatus:complete';

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $arrProperties = $data->getData('properties');
        /**
         * This function validate reportParameterData property data of report omlObject`s
         *  according to metaReportType object`s parameterName property
         */
        $ReportParameter = new ReportParameter();
        $ReportParameter->validate($arrProperties, $serviceQue);
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
     * This function will mark user inactive in OpenAM when deleted from Synap
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function validates data before execute update
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        $reportId = $data->getData('conditions')['object'][0];
        $reportData = $this->fetchObjectData($reportId, $serviceQue);

        $arrProperties = $data->getData('properties');
        if (!isset($arrProperties['type'])) {
            $arrProperties['type'] = $reportData['type'];
        }
        if (!isset($arrProperties['reportParameterData'])) {
            $arrProperties['reportParameterData'] = $reportData['reportParameterData'];
        }
        /**
         * This function validate reportParameterData property data of report omlObject`s
         *  according to metaReportType object`s parameterName property
         */
        $ReportParameter = new ReportParameter();
        $ReportParameter->validate($arrProperties, $serviceQue);
        return true;
    }

    /**
     * Function will fetch the object data based on objectId
     * 
     * @param type $objectId
     * @param ServiceQueI $serviceQue
     * @return $objectData
     */
    private function fetchObjectData($objectId, ServiceQueI $serviceQue) {
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['sendNullKey'] = 1;
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }

}
