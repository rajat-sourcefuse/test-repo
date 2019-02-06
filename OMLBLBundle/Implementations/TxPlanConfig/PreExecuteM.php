<?php

namespace SynapEssentials\OMLBLBundle\Implementations\TxPlanConfig;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

    const LEVEL_ORDER = '1';
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

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


        // Find existing config with the same program
        $searchKey = [];
        $searchKey[0]['type'] = 'txPlanConfig';
        $searchKey[0]['conditions'][] = array(array('programId' => array('IN' => $data->getData('properties')['programId'])));
        $searchKey[0]['outKey'] = 'response';
        $existingConfig = $serviceQue->executeQue("ws_oml_read", $searchKey);
        if (!empty($existingConfig['data']['response'])) {
            //throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_ALREADY_EXIST);
        }

        $this->checkPrimaryActive($data, $type = self::CREATE, $serviceQue);

        // max 4 levels for a plan can add
        $programCount = count($data->getData('properties')['programId']);
        if (!empty($data->getData('properties')['txPlanLevelConfig'])) {
            $txPlanLevels = $data->getData('properties')['txPlanLevelConfig'];
            $count = count($txPlanLevels);
            if ($count > 4) {
                throw new SynapExceptions(SynapExceptionConstants::TX_MORE_CONFIG_LEVEL,400);
            }
            $levelOrderArr = [];
            for ($i = 0; $i < $count; $i++) {
                // level order can not be same
                if (!empty($txPlanLevels[$i]['levelOrder'])) {
                    if (!in_array($txPlanLevels[$i]['levelOrder'], $levelOrderArr)) {
                        $levelOrderArr[] = $txPlanLevels[$i]['levelOrder'];
                    } else {
                        throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_ORDER,400);
                    }
                }

                //if count of program Id's in config == 1 then showProgram checkbox will not
                //come with any level. we will make it default false
                if ($programCount == 1) {
                    $updateArr = array("txPlanLevelConfig" => array("showProgram" => false));
                    $data->setData($updateArr, 'properties', $count - ($i + 1));
                } else {
                    //if programCount >1 then showProgram checkbox will
                    //come with only level 1. we will make others default false
                    if ($txPlanLevels[$i]['levelOrder'] == self::LEVEL_ORDER) {
                        if (!isset($txPlanLevels[$i]['showProgram']) || !$txPlanLevels[$i]['showProgram']) {
                            $updateArr = array("txPlanLevelConfig" => array("showProgram" => false));
                            $data->setData($updateArr, 'properties', $i);
                            //throw new SynapExceptions(SynapExceptionConstants::TX_CONFIG_LEVEL_SHOW_PROG);
                        }
                    } else {
                        $updateArr = array("txPlanLevelConfig" => array("showProgram" => false));
                        $data->setData($updateArr, 'properties', $i);
                    }
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
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $this->checkPrimaryActive($data, $type = self::UPDATE, $serviceQue);
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
     * This function check if teatment plan within provided date already exists
     * @param type $data
     * @param type $type
     * @param type $serviceQue
     * @return type
     * @throws SynapExceptions
     * @author sourabh grover <sourabh.grover@sourcefuse.com>
     */
    private function checkPrimaryActive($data, $type, $serviceQue)
    {
        // Find existing config with the same program and plan type

        if ($type == self::UPDATE) {


            $condition = $data->getData('conditions');
            $txPlanConfigId = $condition['object'][0];

            $searchKey = [];
            $searchKey[0]['objectId'] = $txPlanConfigId;
            $searchKey[0]['sendNullKey'] = 1;
            $searchKey[0]['outKey'] = 'response';
            $txPlanConfigData = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $txPlanConfigResp = $txPlanConfigData['data']['response'];

            $properties = $data->getData('properties');

            $programId = (!empty($properties['programId'])) ? $properties['programId'] : $txPlanConfigResp['programId'];
            $planType = (!empty($properties['planType'])) ? $properties['planType'] : $txPlanConfigResp['planType'];
            $startDate = (!empty($properties['startDate'])) ? $properties['startDate'] : $txPlanConfigResp['startDate'];
            $endDate = (!empty($properties['endDate'])) ? $properties['endDate'] : $txPlanConfigResp['endDate'];
        } else {

            $startDate = $data->getData('properties')['startDate'];
            $endDate = (!empty($data->getData('properties')['endDate'])) ? $data->getData('properties')['endDate'] : '';
            $programId = $data->getData('properties')['programId'];
            $planType = $data->getData('properties')['planType'];
        }

        $searchKey = [];
        $searchKey[0]['type'] = 'txPlanConfig';
        $searchKey[0]['conditions'][] = array(array('programId' => array('IN' => $programId)));
        $searchKey[0]['conditions'][] = ['planType' => $planType];
        if ($type == self::UPDATE) {
            $searchKey[0]['conditions'][] = array('id' => array('NE' => $txPlanConfigId));
        }

        if (!empty($endDate)) {

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
                , 'OR',
                array(
                    array('startDate' => array('LE' => $startDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        } else {

            $searchKey[0]['conditions'][] = array(
                array(
                    array('startDate' => array('LE' => $startDate)),
                    array('endDate' => array('GE' => $startDate))
                )
                ,
                'OR',
                array(
                    array('startDate' => array('GE' => $startDate))
                )
                ,
                'OR',
                array(
                    array('startDate' => array('LE' => $startDate)),
                    array('endDate' => array('ISNULL' => true))
                )
            );
        }

        $searchKey[0]['outKey'] = 'response';
        $existingConfig = $serviceQue->executeQue("ws_oml_read", $searchKey);


        if (!empty($existingConfig['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::TREATMENT_PLAN_CONFIG_ALREADY_EXISTS,400);
        }

        return;
    }
}
