<?php

namespace SynapEssentials\OMLBLBundle\Implementations\WorkList;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \SynapEssentials\WorkFlowBundle\ResponseLibrary\communicationCenter;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\OMLBundle\Services\ServiceQue;
use SynapEssentials\OMLBundle\Utilities\OMLObjectUtility;
/**
 * BL class for postExecute of WorkList.
 *
 * @author sourav Bhargava <sourav.bhargava@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI {

    private $obj;
    private $serviceContainer;

    public function __construct() {
        $confObj = Configurator::getInstance();
        $this->serviceContainer = $confObj->getServiceContainer();
        $this->obj = ServiceQue::getInstance($confObj);
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {

        $workListId = $data->getData('id');
        $pData = $data->getAll();
        $CCData = array();

        if (isset($pData['properties']['assigneeEmployee'])) {
            if (is_array($pData['properties']['assigneeEmployee'])) {
                foreach ($pData['properties']['assigneeEmployee'] as $ae) {
                    $res = $this->getEmployeeSynapUserId($ae);
                    if ($res) {
                        $CCData['to'][] = $res;
                    }
                }
            } else {

                $res = $this->getEmployeeSynapUserId($pData['properties']['assigneeEmployee']);


                if ($res) {
                    $CCData['to'] = array($res);
                }
            }
        } elseif (isset($pData['properties']['assigneePatient'])) {
            if (is_array($pData['properties']['assigneePatient'])) {
                foreach ($pData['properties']['assigneePatient'] as $ap) {
                    $res = $this->getPatientSynapUserId($ap);
                    if ($res) {
                        $CCData['to'] = array_values($res);
                    }
                }
            } else {
                $res = $this->getPatientSynapUserId($pData['properties']['assigneePatient']);
                if ($res) {
                    if (is_array($res)) {
                        $CCData['to'] = array_values($res);
                    } else {
                        $CCData['to'] = array($res);
                    }
                }
            }
        }

        if (isset($CCData['to']) && (!empty($CCData['to']))) {

            $resp = $this->getUserNameFromUserID($CCData['to']);

            if ($resp) {
                $CCData['to'] = array_values($this->getUserNameFromUserID($CCData['to']));
            } else {
                return true;
            }

            $message = array();
            $message['workListId'] = $workListId;
            if (isset($pData['properties']['workflowId'])) {
                $message['workflowId'] = $pData['properties']['workflowId'];
            }
            if (isset($pData['properties']['workflowExeId'])) {
                $message['workflowExeId'] = $pData['properties']['workflowExeId'];
            }

            if (isset($pData['properties']['task'])) {
                $pData['properties']['task'] = json_decode($pData['properties']['task'], true);

                $pData['properties']['displayTask'] = $pData['properties']['task'];
            }
            if (isset($pData['properties']['viewTask'])) {
                $pData['properties']['viewTask'] = json_decode($pData['properties']['viewTask'], true);
            }
            if (isset($pData['properties']['readonlyTask'])) {
                $pData['properties']['readonlyTask'] = json_decode($pData['properties']['readonlyTask'], true);
            }
            $CCData["tags"] = ["workflow"];
            $CCData["subject"] = "New Task Created.";
            $CCData["message"] = json_encode($message);
            $pData['properties']['id'] = $pData['id'];
            $CCData['extra'] = $pData['properties'];
            $ccO = new communicationCenter();
            $resp = $ccO->sendMessage($CCData);
            //throw new \SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions(json_encode($CCData));
            if (isset($resp['messageId'])) {
                $worklist = array();
                $worklist['objectType'] = "workList";
                $worklist['conditions']['object'][] = $pData['id'];
                $worklist["properties"]['cCId'] = $resp['messageId'];

                $a = $serviceQue->executeQue("ws_oml_update", $worklist);
            }
        }


        // throw new \SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions(json_encode($pData));

        return true;
    }

    private function getUserNameFromUserID($synapUserIdArr) {
        $return = false;
        if (!empty($synapUserIdArr)) {

            $synapUserIdArr = array_values($synapUserIdArr);

            $searchKey = [];
            $searchKey[0]['objectId'] = $synapUserIdArr;
            $searchKey[0]['outKey'] = 'response';
            $resp = $this->obj->executeQue("ws_oml_read", $searchKey);
            if (!empty($resp['data']['response']) && count($synapUserIdArr) > 1) {
                foreach ($resp['data']['response'] as $val) {

                    if (isset($val['username'])) {

                        $return[$val['id']] = $val['username'];
                    }
                }
            } elseif (!empty($resp['data']['response']) && count($synapUserIdArr) == 1) {
                if (isset($resp['data']['response']['username'])) {
                    $return[$synapUserIdArr[0]] = $resp['data']['response']['username'];
                }
            }
        }
        return $return;
    }

    public function getEmployeeSynapUserId($orgEmpId) {
        $return = false;
        $resp = $this->getObjectData($orgEmpId);
        if (isset($resp['data']['response'])) {
            if (isset($resp['data']['response']['synapUserId'])) {
                $return = $resp['data']['response']['synapUserId'];
            }
        }
        return $return;
    }

    public function getPatientSynapUserId($patientId) {
        $return = false;
        $resp = $this->getObjectData($patientId);
        if (isset($resp['data']['response'])) {
            if (isset($resp['data']['response']['synapUserId'])) {
                $return = $resp['data']['response']['synapUserId'];
            }
        }
        return $return;
    }

    private function getObjectData($objectId) {
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';
        return $this->obj->executeQue("ws_oml_read", $searchKey);
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {

        $pData = $data->getAll();
        //print_r($pData['conditions']['object']);
        if (isset($pData['properties']['status'])) {
            foreach ($pData['conditions']['object'] as $workListId) {

                $this->changePatientServiceStatus($workListId, $pData['properties']['status']);
            }
        }
        // if (isset($pData['properties']['status'])) {
        foreach ($pData['conditions']['object'] as $workListId) {
            $Gdata[0]['objectId'] = $workListId;
            $Gdata[0]['outKey'] = "worklist";


            $return = $serviceQue->executeQue("ws_oml_read", $Gdata);

            if (!empty($return['data']['worklist'])) {
               
                if (isset($return['data']['worklist']['assigneeEmployee'])) {

                    if (is_array($return['data']['worklist']['assigneeEmployee'])) {
                        foreach ($return['data']['worklist']['assigneeEmployee'] as $ae) {
                            $res = $this->getEmployeeSynapUserId($ae);
                            if ($res) {
                                $CCData['to'][] = $res;
                            }
                        }
                    } else {
                        $res = $this->getEmployeeSynapUserId($return['data']['worklist']['assigneeEmployee']);
                        if ($res) {
                            $CCData['to'] = array($res);
                        }
                    }
                } elseif (isset($return['data']['worklist']['assigneePatient'])) {
//                
                    if (is_array($return['data']['worklist']['assigneePatient'])) {
                        foreach ($return['data']['worklist']['assigneePatient'] as $ap) {
                            $res = $this->getPatientSynapUserId($ap);
                            if ($res) {
                                $CCData['to'] = array_values($res);
                            }
                        }
                    } else {
                        $res = $this->getPatientSynapUserId($return['data']['worklist']['assigneePatient']);
                        if ($res) {
                            if (is_array($res)) {
                                $CCData['to'] = array_values($res);
                            } else {
                                $CCData['to'] = array($res);
                            }
                        }
                    }
                }
                if (!empty($CCData['to'])) {
                    $resp = $this->getUserNameFromUserID($CCData['to']);
                    if ($resp) {
                        $CCData['to'] = array_values($this->getUserNameFromUserID($CCData['to']));
                    } else {
                        return true;
                    }
                    $message = array();
                    $message['workListId'] = $workListId;
                    if (isset($return['data']['worklist']['workflowId'])) {
                        $message['workflowId'] = $return['data']['worklist']['workflowId'];
                    }
                    if (isset($return['data']['worklist']['workflowExeId'])) {
                        $message['workflowExeId'] = $return['data']['worklist']['workflowExeId'];
                    }

                    $CCData["tags"] = ["workflow"];
                    $CCData["subject"] = "New Task Created.";
                    $CCData["message"] = json_encode($message);
                    $CCData['extra'] = $return['data']['worklist'];
                    if (isset($return['data']['worklist']['cCId'])) {
                        $CCData["_id"] = $return['data']['worklist']['cCId'];
                    }
                    $ccO = new communicationCenter();
                    $resp = $ccO->sendMessage($CCData);
                }
            }
        }
        //}
        foreach ($pData['conditions']['object'] as $workListId) {
            $Gdata = array();
            $Gdata[0]['objectId'] = $workListId;
            $Gdata[0]['outKey'] = "worklist";
            $return = $serviceQue->executeQue("ws_oml_read", $Gdata);
            $CCData = array();
            $CCData['extra'] = $return["data"]["worklist"];
            $CCData["_id"] = array();
            $searchKey = [];
            $searchKey[0]["type"] = "workListReminder";
            $searchKey[0]['objectId'] = $workListId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $this->obj->executeQue("ws_oml_read", $searchKey);
            if (!empty($resp["response"])) {
                foreach ($resp["response"] as $val) {
                    $CCData["_id"][] = $val["cCId"];
                }
            }
            $searchKey = [];
            $searchKey[0]["type"] = "workListApprover";
            $searchKey[0]['objectId'] = $workListId;
            $searchKey[0]['outKey'] = 'response';
            $resp = $this->obj->executeQue("ws_oml_read", $searchKey);
            if (!empty($resp["response"])) {
                foreach ($resp["response"] as $val) {
                    $CCData["_id"][] = $val["cCId"];
                }
            }

            if (!empty($CCData["_id"])) {
                $ccO = new communicationCenter();
                $resp = $ccO->sendMessage($CCData);
            }
        }
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        if (!empty($data->getData('responseArr'))) {
            $wld = $data->getData('responseArr');
            $patientId = array();
            if (isset($wld[0])) {
                foreach ($wld as $v) {
                    if (isset($v['subjectId'])) {
                        $this->getPatientIdz($v['subjectId'], $patientId);
                    }
                }
                $patientData = $this->getPatientData($patientId);
                if (!empty($patientData)) {
                    foreach ($wld as $k => $v) {
                        if (isset($v['subjectId'])) {
                            if ($this->getType($v['subjectId']) == 'patient') {
                                $wld[$k]['subjectDisplayName'] = $patientData[$v['subjectId']];
                            }
                        }
                    }
                }
            } else {
                //single call
                if (isset($wld['subjectId'])) {
                    $this->getPatientIdz($wld['subjectId'], $patientId);
                    $patientData = $this->getPatientData($patientId);
                   
                    if(($this->getType($wld['subjectId']) == 'patient') && (isset($patientData[$wld['subjectId']]))) {
                        $wld['subjectDisplayName'] = $patientData[$wld['subjectId']];
                    }
                }
            }
//             $data->unSetData($data->getData('responseArr'));
//             print_r($data->getData('responseArr'));
//             exit;
             $data->setData( $wld,'responseArr');
            
            //  exit;
            return true;
        }
    }

    private function getPatientData($patientIdArr) {
        $return = array();
        if (!empty($patientIdArr)) {
            $getData = array();
            $getData[0]['outKey'] = "patientData";
            $getData[0]['type'] = 'patient';
            $getData[0]['conditions'] = array(array("id" => array("IN" => $patientIdArr)));
           // $getData[0]['RequiredAdditionalInfo'] = false;
            $returnP = $this->obj->executeQue("ws_oml_read", $getData);
            if (!empty($returnP['data']['patientData'])) {
                foreach ($returnP['data']['patientData'] as $vv) {
                    
                    $return[$vv['id']] = $vv['lastName'].",".$vv['firstName']. " (".$vv['dob'].") ".$vv['genderCode'];
                }
            }
        }

        return $return;
    }

    private function getType($id) {
        return OMLObjectUtility::getObjectType($id);
    }

    private function getPatientIdz($subjectId, &$arr) {
        $type = $this->getType($subjectId);
        if ($type == 'patient') {
            $arr[$subjectId] = $subjectId;
        }
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    private function changePatientServiceStatus($workListId, $workListStatus) {
        $getData = array();
        $getData[0]['outKey'] = "pd";
        $getData[0]['type'] = 'patientService';
        $getData[0]['conditions'] = array(array("workListId" => $workListId));
        $idArr = array();
        $return = $this->obj->executeQue("ws_oml_read", $getData);
        if (empty($return['data']['pd'])) {
            return;
        } else {
            foreach ($return['data']['pd'] as $pd) {
                if ($pd['status'] != 'metaServiceStatus:completed') {
                    $idArr[] = $pd['id'];
                }
            }
        }


        $data = array();
        switch ($workListStatus) {
            case 'metaWorklistStatus:PENDING':
            case 'metaWorklistStatus:REOPEN':
            case 'metaWorklistStatus:PREAPPROVAL':
                $data['properties']['status'] = 'metaServiceStatus:pendingApproval';
                break;
            case 'metaWorklistStatus:SKIPPED':
            case 'metaWorklistStatus:COMPLETE':
                $data['properties']['status'] = 'metaServiceStatus:completed';
                break;
            default :

                return false;
                break;
        }

        if (!empty($idArr)) {
            $data['conditions']['object'] = $idArr;
            $res = $this->obj->executeQue("ws_oml_update", $data);
        }
    }

}
