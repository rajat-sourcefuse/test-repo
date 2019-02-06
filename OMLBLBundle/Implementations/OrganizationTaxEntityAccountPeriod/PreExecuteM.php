<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationTaxEntityAccountPeriod;

/**
 * BL class for preExecute of organizationTaxEntityAccountPeriod.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

class PreExecuteM implements PreExecuteI {

    const ACCOUNTING_PERIOD_STATUS_OPEN = 'metaAccountingPeriodStatus:open';

    /**
     * This function will execute just before the creation of organization tax entity account period
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        if (isset($data->getData('properties')['status']) &&
                $data->getData('properties')['status'] == self::ACCOUNTING_PERIOD_STATUS_OPEN
        ) {
            // function wil check if accounting period with status open exists
            $this->chkTaxEntityAccountPeriod($data, $serviceQue);
        }
        if (isset($data->getData('properties')['startDate']) && isset($data->getData('properties')['endDate'])) {
//          function wil check does accounting period overlaps with others
            $taxEntityId = $data->getData('parent');
            $startDate = $data->getData('properties')['startDate'];
            $endDate = $data->getData('properties')['endDate'];
            $this->chkTaxEntityPeriodOverlapping($taxEntityId, $startDate, $endDate, $serviceQue);
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
     * This function will execute just before the updation of tax entity account period
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        if (isset($data->getData('properties')['status']) &&
                $data->getData('properties')['status'] == self::ACCOUNTING_PERIOD_STATUS_OPEN
        ) {
            // check if there is any account period with open status
            $accountPeriodId = $data->getData('conditions')['object'][0];
            $accountPeriodData = $this->fetchObjectData($accountPeriodId, $serviceQue);
            $taxEntityId = $accountPeriodData['organizationTaxEntityId'];
            $taxEntityData = $this->fetchTaxEntityAccountPeriod($taxEntityId, $serviceQue, $accountPeriodId);
            if (!empty($taxEntityData)) {
                throw new SynapExceptions(SynapExceptionConstants::ACCOUNTING_PERIOD_OPEN_STATUS_EXISTS);
            }
        }
        if (isset($data->getData('conditions')['object'][0])) {
            if (isset($data->getData('properties')['startDate']) || isset($data->getData('properties')['endDate'])) {
//          function wil check does accounting period overlaps with others
                $accountPeriodId = $data->getData('conditions')['object'][0];
                $accountPeriodData = $this->fetchObjectData($accountPeriodId, $serviceQue);
                if (isset($data->getData('properties')['startDate'])) {
                    $startDate = $data->getData('properties')['startDate'];
                } else {
                    $startDate = $accountPeriodData['startDate'];
                }

                if (isset($data->getData('properties')['endDate'])) {
                    $endDate = $data->getData('properties')['endDate'];
                } else {
                    $endDate = $accountPeriodData['endDate'];
                }
                $taxEntityId = $accountPeriodData['organizationTaxEntityId'];
                $taxEntityData = $this->chkTaxEntityPeriodOverlapping($taxEntityId, $startDate, $endDate, $serviceQue, $accountPeriodId);
            }
        }
        return true;
    }

    /**
     * This function will check if any other account period having status as open
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function chkTaxEntityAccountPeriod($data, ServiceQueI $serviceQue) {
        $taxEntityId = $data->getData('parent');
        $taxEntityData = $this->fetchTaxEntityAccountPeriod($taxEntityId, $serviceQue);
        if (!empty($taxEntityData)) {
            throw new SynapExceptions(SynapExceptionConstants::ACCOUNTING_PERIOD_OPEN_STATUS_EXISTS, 400);
        }
        return;
    }

    /**
     * This function will get tax entity account period based on tax entity id
     * 
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @param $accountingPeriodId
     * @return $taxEntityData
     */
    private function fetchTaxEntityAccountPeriod($taxEntityId, ServiceQueI $serviceQue, $accountingPeriodId = '') {

        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityAccountPeriod';
        $searchKey[0]['conditions'][] = array(
            'organizationTaxEntityId' => $taxEntityId
        );
        $searchKey[0]['conditions'][] = array(
            'status' => self::ACCOUNTING_PERIOD_STATUS_OPEN
        );
        if (!empty($accountingPeriodId)) {
            $searchKey[0]['conditions'][] = array(
                'id' => array('NE' => $accountingPeriodId)
            );
        }
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $taxEntityData = $resp['data']['response'];
        return $taxEntityData;
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
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>, Date: 11-08-2017, JIRA Id: AP-753
     * 
     * This function will check does any other account period overlapping with oeach other
     * 
     * @param type $taxEntityId
     * @param type $startDate
     * @param type $endDate
     * @param ServiceQueI $serviceQue
     * @param type $accountingPeriodId
     * @return return throw exception while period overlapping with others.
     */
    private function chkTaxEntityPeriodOverlapping($taxEntityId, $startDate, $endDate, ServiceQueI $serviceQue, $accountingPeriodId = '') {
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityAccountPeriod';
        $searchKey[0]['conditions'][] = array(
            'organizationTaxEntityId' => $taxEntityId
        );
        $searchKey[0]['conditions'][] = array(
            array(
                array('startDate' => array('GE' => $startDate)),
                array('startDate' => array('LE' => $endDate))
            ),
            'OR',
            array(
                array('endDate' => array('GE' => $startDate)),
                array('endDate' => array('LE' => $endDate))
            ),
            'OR',
            array(
                array('startDate' => array('LE' => $startDate)),
                array('endDate' => array('GE' => $endDate))
            )
        );

        if (!empty($accountingPeriodId)) {
            $searchKey[0]['conditions'][] = array(
                'id' => array('NE' => $accountingPeriodId)
            );
        }
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $taxEntityData = $resp['data']['response'];

        if (!empty($taxEntityData)) {
            throw new SynapExceptions(SynapExceptionConstants::ACCOUNTING_PERIOD_OVERLAPPING_EACH_OTHER, 400);
        }
        return;
    }

}
