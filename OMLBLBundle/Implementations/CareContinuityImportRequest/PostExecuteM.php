<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CareContinuityImportRequest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\CCDABundle\Utilities\CCDACONSTANTS;
use \Externals\CCDABundle\Utilities\CommonFunctions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use Externals\CCDABundle\Handlers\CareContinuityImportHandler;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

class PostExecuteM implements PostExecuteI {

    const propsHeightInchName = 'heightInch';
    const objPatientAdmission = 'patientAdmission';
    const objPatientAllergy = 'patientAllergy';
    const objPatientDiagnosis = 'patientDiagnosis';
    const objPatientVital = 'patientVital';
    const objPatientMedication = 'patientMedication';
    const metaPrimaryAddress = 'metaAddressType:primaryHome';
    const metaPrimaryTelephone = 'metaTelephoneType:homePrimary';

    private $elasticSearchUrl;

    public function __construct() {
        $this->elasticSearchUrl = CommonFunctions::getConfigValue('elasticSearchUrl');
    }

    /**
     * @description this function will start process after exicute Create
     * sending it via EMR direct mail
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        $properties = $data->getData('properties');
        if (isset($properties['senderDirectAddress'])) {
            /* if it came through EMR Direct, we needn't prcess it further. */
            return true;
        }
        $parsedData = array();
        /* It came through normal care continuity import by user or data migration. */
        if (isset($properties['isReconciled']) && $properties['isReconciled'] == true) {
            $cdnPath = $data->getData('properties')['cdnPathOriginal'];
            $allowPatientDuplicate = FALSE;
            if (isset($properties['allowPatientDuplicate']) && $properties['allowPatientDuplicate'] == 1) {
                $allowPatientDuplicate = TRUE;
            }
            $allowPatientMerge = FALSE;
            if (isset($properties['allowPatientMerge']) && $properties['allowPatientMerge'] == 1) {
                $allowPatientMerge = TRUE;
            }
            $careRequest = new CareContinuityImportHandler('careContinuityImportRequest');
            $ccdaParsedData = $careRequest->fetchPatientDataWithStyledMessage($serviceQue, $cdnPath, $allowPatientDuplicate, $allowPatientMerge);

            /* code to import the patient */
            if (isset($ccdaParsedData['parsedData'])) {
                $parsedData = $ccdaParsedData['parsedData'];
                if (isset($parsedData['PatientRole'])) {
                    $patientRole = $parsedData['PatientRole'];
                    if (isset($ccdaParsedData['patientID'])) {
                        $patientID = $ccdaParsedData['patientID'];
                        $this->createUpdatePatient($serviceQue, $patientRole, CCDACONSTANTS::DB_UPDATE, $allowPatientDuplicate, $allowPatientMerge, $patientID);
                    } else {
                        $patientID = $this->createUpdatePatient($serviceQue, $patientRole, CCDACONSTANTS::DB_CREATE, $allowPatientDuplicate, $allowPatientMerge);
                    }
                }
                if (isset($patientID)) {
                    if (isset($parsedData['patientAllergy'])) {
                        $this->createPatientAllergy($serviceQue, $parsedData['patientAllergy'], $patientID);
                    }
                    if (isset($parsedData['patientDiagnosis'])) {
                        $this->createPatientDiagnosis($serviceQue, $parsedData['patientDiagnosis'], $patientID);
                    }
                    if (isset($parsedData['patientVital'])) {
                        $this->createPatientVital($serviceQue, $parsedData['patientVital'], $patientID);
                    }
                    if (isset($parsedData['patientMedication'])) {
                        $this->createPatientMedication($serviceQue, $parsedData['patientMedication'], $patientID);
                    }
                    $cdnPathModified = isset($ccdaParsedData['cdnPathModified']) ? $ccdaParsedData['cdnPathModified'] : '';
                    $careContinuityDocumentType = isset($ccdaParsedData['careContinuityDocumentType']) ? $ccdaParsedData['careContinuityDocumentType'] : '';
                    /* update careContinuityImportRequest properties */
                    $updateObjs['conditions']['object'][0] = $data->getData('id');
                    $updateObjs['properties']['patientId'] = $patientID;
                    $updateObjs['properties']['cdnPathModified'] = $cdnPathModified;
                    $updateObjs['properties']['careContinuityDocumentType'] = $careContinuityDocumentType;
                    $updateResp = $serviceQue->executeQue(CCDACONSTANTS::DB_UPDATE, $updateObjs);
                } else {
                    throw new SynapException(SynapExceptionConstants::PROPERTY_DATA_NOT_VALID, 400, 'patientId');
                }
            }
        }
        return true;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
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
        return true;
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

    /**
     * @description function will remove all the old record in temp tables 
     * if the import request is assigned earlier to any patient.
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param $type of request
     * @param $condition to match record
     */
    private function _removeOldRecord(ServiceQueI $serviceQue, $type, $condition) {
        $request = array();
        $request[] = array("outKey" => "info",
            "type" => $type,
            "conditions" => array($condition)
        );
        $record = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $request);
        $ids = array();
        foreach ($record["data"]["info"] as $arecord) {
            $ids[] = $arecord["id"];
        }
        if (count($ids) > 0) {
            $serviceQue->executeQue(CCDACONSTANTS::DB_DELETE, $ids);
        }
    }

    public function getPatientData($cdnPath) {
        $careRequest = new CareContinuityImportHandler("");
        return $careRequest->fetchPatientData($cdnPath);
    }

    /**
     * @description function will fetch patient data from ccda import request 
     * table and insert record into respective temp table for assigning it
     * to patient after execute update
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        $result = $data->getAll();
        if (isset($result["properties"]["patientId"]) && (isset($result["properties"]["isReconciled"]) && $result["properties"]["isReconciled"] != true)) {
            $requestId = reset($result["conditions"]["object"]);
            $condition[] = array("outKey" => "ccdaImport", "objectId" => $requestId);
            $record = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $condition);
            if ($record["status"]["success"]) {
                $aRecord = $record["data"]["ccdaImport"];
                if ($aRecord["careContinuityDocumentType"] != "metaCareContinuityDocumentType:CCDA") {
                    return true;
                }

                $messageId = $aRecord["emrDirectMessageId"];
                $patientData = $this->getPatientData($aRecord["cdnPathModified"]);
                $parentId = $aRecord["patientId"];
                if (isset($patientData["patientAllergy"])) {
                    $patientAllergy = $patientData["patientAllergy"];
                    if (array_values($patientAllergy) !== $patientAllergy) {
                        if (!is_array(reset($patientAllergy))) {
                            $temp = $patientAllergy;
                            $patientAllergy = [];
                            $patientAllergy[] = $temp;
                        }
                    }
                    $request = array();
                    $request["objectType"] = "ccdaImportAllergy";
                    $request["parent"] = $parentId;
                    $this->_removeOldRecord($serviceQue, $request["objectType"], array("emrDirectMessageId" => $messageId));
                    foreach ($patientAllergy as $allergy) {
                        $this->validateFields($allergy);
                        $allergy["emrDirectMessageId"] = $messageId;
                        $allergy["careContinuityImportRequestId"] = $requestId;
                        $request["properties"] = $allergy;
                        $result = $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $request);
                    }
                }


                if (isset($patientData["patientDiagnosis"])) {
                    $patientDiagnosis = $patientData["patientDiagnosis"];
                    if ($patientDiagnosis && count($patientDiagnosis) > 0) {
                        if (array_values($patientDiagnosis) !== $patientDiagnosis) {
                            $temp = $patientDiagnosis;
                            $patientDiagnosis = [];
                            $patientDiagnosis[] = $temp;
                        }
                        $snomedCodes = [];
                        foreach ($patientDiagnosis as $diagnosis) {
                            $snomedCodes[] = $diagnosis["snomedCode"];
                        }

                        $request = array();
                        $request["objectType"] = "ccdaImportDiagnosis";
                        $request["parent"] = $parentId;
                        $this->_removeOldRecord($serviceQue, $request["objectType"], array("emrDirectMessageId" => $messageId));
                        $condition = [];
                        $condition[] = array("outKey" => "info", "type" => "metaSnomedCode",
                            "conditions" => array(array("code" => array('IN' => $snomedCodes))));
                        $record = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $condition);

                        $metaSnomedCode = [];
                        $snomedCodes = $record["data"]["info"];
                        foreach ($snomedCodes as $snomed) {
                            $metaSnomedCode[$snomed['code']] = $snomed['id'];
                        }

                        foreach ($patientDiagnosis as $diagnosis) {
                            $condition = [];
                            $status = strtolower($diagnosis["status"]);
                            if ($status == "completed") {
                                $status = "resolved";
                            }
                            $diagnosis["status"] = "metaPatientDiagnosisStatus:" . $status;
                            $this->validateFields($diagnosis);
                            $diagnosis["careContinuityImportRequestId"] = $requestId;
                            $diagnosis["emrDirectMessageId"] = $messageId;
                            $diagnosis["metaSnomed"] = "metaSnomed:" . $diagnosis['snomedCode'];
                            $diagnosis["snomedCode"] = $metaSnomedCode[$diagnosis['snomedCode']];
                            $request["properties"] = $diagnosis;
                            $result = $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $request);
                        }
                    }
                }
                if (isset($patientData["patientMedication"])) {
                    $patientMedication = $patientData["patientMedication"];
                    if ($patientMedication && count($patientMedication) > 0) {
                        if (array_values($patientMedication) !== $patientMedication) {
                            $temp = $patientMedication;
                            $patientMedication = [];
                            $patientMedication[] = $temp;
                        }
                        $request = array();
                        $request["objectType"] = "ccdaImportMedication";
                        $request["parent"] = $parentId;
                        $this->_removeOldRecord($serviceQue, $request["objectType"], array("emrDirectMessageId" => $messageId));
                        foreach ($patientMedication as $medication) {
                            $medicationDrugBrandName = strtok(trim($medication["medicationDrugBrandName"]), " ");
                            $medication["medicationDrugBrandName"] = $medicationDrugBrandName;
                            $this->validateFields($medication);
                            $medication["careContinuityImportRequestId"] = $requestId;
                            $medication["emrDirectMessageId"] = $messageId;
                            $request["properties"] = $medication;
                            $result = $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $request);
                        }
                    }
                }
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    private function validateFields(&$arr) {
        foreach ($arr as &$a) {
            if (is_array($a)) {
                $a = "";
            }
        }
    }

    /**
     * Function will fetch the object data based on propertyName and propertyValue
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $objectType, $propertyName, $propertyValue, &$arrProperty, $arrPropIndex
     * @return boolean 
     */
    private function fetchMetaObjectData(ServiceQueI $serviceQue, $objectType, $propertyName, $propertyValue, &$arrProperty, $arrPropIndex) {
        $searchKey = [];
        $searchKey[0]['type'] = $objectType;
        $searchKey[0]['conditions'][] = array($propertyName => $propertyValue);
        $searchKey[0]['outKey'] = 'response';
        $objectResp = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $searchKey);
        $objectData = $objectResp['data']['response'];
        if (!empty($objectData)) {
            $arrProperty[$arrPropIndex] = $objectData[0]['id'];
        }
        return true;
    }

    /**
     * Function will fetch the object data based on conditions
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $propertyName, $conditions
     * @return $objectData
     */
    private function fetchObjectData(ServiceQueI $serviceQue, $objectType, $conditions) {
        $searchKey = [];
        $searchKey[0]['type'] = $objectType;
        $searchKey[0]['conditions'] = $conditions;
        $searchKey[0]['outKey'] = 'response';
        $objectResp = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }

    /**
     * Function will createUpdatePatient patient with patientAddress.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $patientRole, $omlAction, $allowPatientDuplicate, $allowPatientMerge, $patientID
     * @return $patientID
     */
    private function createUpdatePatient(ServiceQueI $serviceQue, $patientRole, $omlAction, $allowPatientDuplicate, $allowPatientMerge, $patientID = NULL) {
        $arrObject = $arrProp = array();
        $arrProp['identity'] = $patientRole['identity'];
        $this->createDbDateFormat($patientRole, 'dob');
        $arrProp['dob'] = $patientRole['dob'];
        $arrProp['firstName'] = $patientRole['firstName'];
        $arrProp['lastName'] = $patientRole['lastName'];
        if (isset($patientRole['genderCode']) && !empty($patientRole['genderCode'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaGender', 'code', $patientRole['genderCode'], $arrProp, 'gender');
        }
        if (isset($patientRole['race']) && !empty($patientRole['race'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaRace', 'code', $patientRole['race'], $arrProp, 'race');
            if (isset($arrProp['race'])) {
                $arrProp['race'] = [$arrProp['race']];
            }
        }
        if (isset($patientRole['ethnicityCode']) && !empty($patientRole['ethnicityCode'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaEthnicity', 'code', $patientRole['ethnicityCode'], $arrProp, 'ethnicity');
        }
        if (isset($patientRole['maritalStatusCode']) && !empty($patientRole['maritalStatusCode'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaMaritalStatus', 'code', $patientRole['maritalStatusCode'], $arrProp, 'maritalStatus');
        }
        if (isset($patientRole['preferredLanguageCode']) && !empty($patientRole['preferredLanguageCode'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaLanguage', 'code', $patientRole['preferredLanguageCode'], $arrProp, 'preferredLanguage');
        }
        if (isset($patientRole['patientAddress']) && !empty($patientRole['patientAddress'])) {
            $this->fetchPatientAddress($serviceQue, $patientRole['patientAddress'], $arrProp);
            if ($omlAction == CCDACONSTANTS::DB_UPDATE) {
                $addressProps = $arrProp['patientAddress'][0];
                $propsAddressTypeName = 'addressType';
                $arrConditions = array(
                    array(
                        'patientId' => $patientID
                    ),
                    array(
                        $propsAddressTypeName => $addressProps[$propsAddressTypeName]
                    )
                );
                $arrPatientAddress = $this->fetchObjectData($serviceQue, 'patientAddress', $arrConditions);
                if (!empty($arrPatientAddress)) {
                    $arrProp['patientAddress'][0]['id'] = $arrPatientAddress[0]['id'];
                }
            }
        }
        if (isset($patientRole['patientTelephone']) && !empty($patientRole['patientTelephone'])) {
            $this->fetchPatientTelephone($serviceQue, $patientRole['patientTelephone'], $arrProp);
        }
        if ($omlAction == CCDACONSTANTS::DB_UPDATE) {
            $arrObject['conditions']['object'][0] = $patientID;
        } else { /* create case */
            $arrObject['objectType'] = 'patient';
            if ($allowPatientDuplicate && !$allowPatientMerge) {
                $arrProp['isDuplicateAllow'] = $allowPatientDuplicate;
            }
        }
        $arrProp['createNewSynapUser'] = FALSE;
        $arrObject['properties'] = $arrProp;
        $resPatient = $serviceQue->executeQue($omlAction, $arrObject);
        $patientID = $resPatient['data']['id'];
        return $patientID;
    }

    /**
     * Function fetch patientAddress from input array $patientAddress and assign to reference array $arrProp for patient.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $patientAddress
     * @param reference $arrProp
     * @return boolean
     */
    private function fetchPatientAddress($serviceQue, $patientAddress, &$arrProp) {
        $arrAddressProp = array();

        $arrAddressProp['zip'] = $patientAddress['zip'];
        if (isset($patientAddress['state']) && !empty($patientAddress['state'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaState', 'value', $patientAddress['state'], $arrAddressProp, 'state');
        }
        if (isset($patientAddress['country']) && !empty($patientAddress['country'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaCountry', 'value', $patientAddress['country'], $arrAddressProp, 'country');
        }
        $propsAddressName = 'addressType';
        if (isset($patientAddress[$propsAddressName]) && !empty($patientAddress[$propsAddressName])) {
            $this->fetchMetaObjectData($serviceQue, 'metaAddressType', 'code', $patientAddress[$propsAddressName], $arrAddressProp, $propsAddressName);
            if ($arrAddressProp[$propsAddressName] == self::metaPrimaryAddress) {
                $arrAddressProp['isPrimary'] = TRUE;
            }
        }
        $arrAddressProp['city'] = $patientAddress['city'];
        $arrAddressProp['address1'] = $patientAddress['address1'];
        $arrProp['patientAddress'][] = $arrAddressProp;
        return TRUE;
    }

    /**
     * Function fetch patientTelephone from input array $patientTelephone and assign to reference array $arrProp for patient.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $patientAddress
     * @param reference $arrProp
     * @return boolean
     */
    private function fetchPatientTelephone($serviceQue, $patientTelephone, &$arrProp) {
        $arrTelephoneProp = array();
        if (isset($patientTelephone['telephoneType']) && !empty($patientTelephone['telephoneType'])) {
            $this->fetchMetaObjectData($serviceQue, 'metaTelephoneType', 'code', $patientTelephone['telephoneType'], $arrTelephoneProp, 'telephoneType');
        } else {
            $arrTelephoneProp['telephoneType'] = self::metaPrimaryTelephone;
        }
        $phoneNumber = preg_replace('/[^0-9]/', '', $patientTelephone['number']);
        $arrTelephoneProp['number'] = substr($phoneNumber, -10); /* extract last 10 digits only, exclude STD code case. */
        $arrProp['patientTelephone'][] = $arrTelephoneProp;
        return TRUE;
    }

    /**
     * Function update/create patientAllergy according to available data.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $patientAllergy
     * @param type $patientID
     * @return boolean
     */
    private function createPatientAllergy(ServiceQueI $serviceQue, $patientAllergies, $patientID) {
        $arrPatientAllergies = array();
        if (!empty($patientAllergies)) {
            if (isset($patientAllergies[0])) {
                $arrPatientAllergies = $patientAllergies;
            } else {
                $arrPatientAllergies[0] = $patientAllergies;
            }
            foreach ($arrPatientAllergies as $patientAllergy) {
                $arrObject = $arrProp = array();
                $propsOnSetDateName = 'onSetDate';
                if (isset($patientAllergy[$propsOnSetDateName]) && !empty($patientAllergy[$propsOnSetDateName])) {
                    $this->createDbDateFormat($patientAllergy, $propsOnSetDateName);
                    $arrProp['name'] = $patientAllergy['name'];
                    $arrProp[$propsOnSetDateName] = $patientAllergy[$propsOnSetDateName];
                    if (isset($patientAllergy['drugRxNormId']) && !empty($patientAllergy['drugRxNormId'])) {
                        $arrProp['drugRxNormId'] = $patientAllergy['drugRxNormId'];
                    }
                    $propsReactionName = 'reaction';
                    if (isset($patientAllergy[$propsReactionName]) && !empty($patientAllergy[$propsReactionName])) {
                        $arrProp[$propsReactionName] = $patientAllergy[$propsReactionName];
                    }
                    if (isset($patientAllergy['status']) && !empty($patientAllergy['status'])) {
                        $arrProp['isActive'] = (strtoupper($patientAllergy['status']) == 'ACTIVE' ? 1 : 0);
                    }
                    $arrConditions = array(
                        array(
                            'patientId' => $patientID
                        ),
                        array(
                            'name' => $patientAllergy['name']
                        )
                    );
                    $arrPatientAllergy = $this->fetchObjectData($serviceQue, self::objPatientAllergy, $arrConditions);
                    if (!empty($arrPatientAllergy)) {
                        $updateObjs = [];
                        $updateObjs['conditions']['object'][0] = $arrPatientAllergy[0]['id'];
                        $updateObjs['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_UPDATE, $updateObjs);
                    } else {
                        $arrObject['objectType'] = self::objPatientAllergy;
                        $arrObject['parent'] = $patientID;
                        $arrObject['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $arrObject);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Function update/create patientDiagnosis according to available data.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $patientDiagnosis
     * @param type $patientID
     * @return boolean
     */
    private function createPatientDiagnosis(ServiceQueI $serviceQue, $patientDiagnosis, $patientID) {
        if (!empty($patientDiagnosis)) {
            $arrPatientDiagnosis = array();
            if (isset($patientDiagnosis[0])) {
                $arrPatientDiagnosis = $patientDiagnosis;
            } else {
                $arrPatientDiagnosis[0] = $patientDiagnosis;
            }
            foreach ($arrPatientDiagnosis as $diagnosis) {
                $arrObject = $arrProp = array();
                if (isset($diagnosis['snomedCode']) && !empty($diagnosis['snomedCode'])) {
                    $this->createDbDateFormat($diagnosis, 'onsetDate');
                    $diagnosticSnomedCode = $diagnosis['snomedCode'];
                    $arrProp['snomedCode'] = $diagnosticSnomedCode;
                    $arrResponse = CommonFunctions::sendElasticCurlRequests($this->elasticSearchUrl, 'snomed_icd10_codes', 'snomed_icd10_code', 'snomedid', $diagnosticSnomedCode);

                    if ($arrResponse['status'] && !empty($arrResponse['data'])) {
                        $arrProp['icd10Code'] = $arrResponse['data']['icd10id'];
                        $arrProp['icd10Desc'] = $arrResponse['data']['icd10desc'];
                        $arrProp['snomedDesc'] = $arrResponse['data']['snomeddesc'];
                    }
                    $arrProp['onsetDate'] = $diagnosis['onsetDate'];
                    if (isset($diagnosis['status']) && !empty($diagnosis['status'])) {
                        $this->fetchMetaObjectData($serviceQue, 'metaPatientDiagnosisStatus', 'value', $diagnosis['status'], $arrProp, 'status');
                    }
                    $arrConditions = array(
                        array(
                            'patientId' => $patientID
                        ),
                        array(
                            'snomedCode' => $diagnosticSnomedCode
                        )
                    );
                    $arrPatientDiagnosis = $this->fetchObjectData($serviceQue, self::objPatientDiagnosis, $arrConditions);
                    if (!empty($arrPatientDiagnosis)) {
                        $updateObjs = [];
                        $updateObjs['conditions']['object'][0] = $arrPatientDiagnosis[0]['id'];
                        $updateObjs['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_UPDATE, $updateObjs);
                    } else {
                        $arrObject['objectType'] = self::objPatientDiagnosis;
                        $arrObject['parent'] = $patientID;
                        $arrObject['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $arrObject);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Function update/create patientVital according to available data.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $patientVital
     * @param type $patientID
     * @return boolean
     */
    private function createPatientVital(ServiceQueI $serviceQue, $patientVital, $patientID) {
        return;
        if (!empty($patientVital)) {
            $arrPatientVitals = array();
            if (isset($patientVital[0])) {
                $arrPatientVitals = $patientVital;
            } else {
                $arrPatientVitals[0] = $patientVital;
            }
            $arrTimeGroupedData = $this->mappingVitalProperties($arrPatientVitals);
            if (!empty($arrTimeGroupedData)) {
                $arrMetaProperties = array('painLevel' => 'metaPainLevel', 'temperatureUnit' => 'metaTemperatureUnit', 'bpUnit' => 'metaBloodPressureUnit');
                foreach ($arrTimeGroupedData as $timeIndex => $arrVitals) {
                    $arrObject = $arrProp = array();
                    $objDate = new \DateTime($timeIndex);
                    $date = $objDate->format(DateTimeUtility::DATE_FORMAT);
                    $time = $objDate->format(DateTimeUtility::TIME_FORMAT);

//                    $arrProp['encounterId'] = $encounterId;
//                    $arrProp['smokingStatus'] = $smokingStatus;
                    foreach ($arrVitals as $keyIndex => $vitalVal) {
                        if (in_array($keyIndex, array_keys($arrMetaProperties))) {
                            $this->fetchMetaObjectData($serviceQue, $arrMetaProperties[$keyIndex], 'value', $vitalVal['value'], $arrProp, $keyIndex);
                        } else {
                            $arrProp[$keyIndex] = $vitalVal['value'];
                        }
                    }
                    $arrProp['observationDate'] = $date;
                    $arrProp['observationTime'] = $time;

                    $arrConditions = array(
                        array('patientId' => $patientID),
//                        array('encounterId' => $encounterId),
                        array('observationDate' => $date),
                        array('observationTime' => $time)
                    );
//                     search for existing data for update properties
                    $arrPatientVital = $this->fetchObjectData($serviceQue, 'patientVital', $arrConditions);
                    if (!empty($arrPatientVital)) {
                        $updateObjs = [];
                        $updateObjs['conditions']['object'][0] = $arrPatientVital[0]['id'];
                        $updateObjs['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_UPDATE, $updateObjs);
                    } else {
                        $arrObject['objectType'] = 'patientVital';
                        $arrObject['parent'] = $patientID;
                        $arrObject['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $arrObject);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Function mapping to patientVital properties
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $patientVital
     * @return array properties
     */
    private function mappingVitalProperties($patientVital) {
        $arrVitalProp = array('patientId' => 'patientId', 'smokingStatus' => 'smokingStatus', 'encounterId' => 'encounterId', 'observationDate' => 'observationDate', 'observationTime' => 'observationTime', 'heightFeet' => 'Height_FT', 'heightInch' => 'Height_IN', 'weightPound' => 'Weight Measured', 'temperature' => 'temperature', 'pulse' => 'pulse', 'bpSystolic' => 'bpSystolic', 'bpDiastolic' => 'bpDiastolic', 'respiratoryRate' => 'Respiratory Rate', 'pulseOximetry' => 'pulseOximetry', 'headCircumference' => 'headCircumference', 'painLevel' => 'painLevel', 'notes' => 'notes', 'bmiInternal' => 'BMI (Body Mass Index)', 'bmi' => 'bmi', 'bpMedlineUrl' => 'bpMedlineUrl', 'bmiMedlineUrl' => 'bmiMedlineUrl', 'temperatureUnit' => 'temperatureUnit', 'bpUnit' => 'bpUnit', 'isPregnant' => 'isPregnants');
        /*      BSA (Body Surface Area)---xml value skipped, as we are using bmi */
        $arrTimeGroupedData = array();
        foreach ($patientVital as $vital) {
            if ($vital['displayName'] == 'Height') {
                $vitalIndex = 'heightFeet';
                if ($vital['unit'] == 'IN') {
                    $vitalIndex = 'heightInch';
                }
            } else {
                $vitalIndex = array_search($vital['displayName'], $arrVitalProp);
            }

            if (!empty($vitalIndex)) {
                $arrTimeGroupedData[$vital['time']][$vitalIndex] = array('code' => $vital['code'], 'displayName' => $vital['displayName'], 'value' => $vital['value'], 'unit' => $vital['unit']);
            }
        }
        return $arrTimeGroupedData;
    }

    /**
     * Function update/create patientMedication according to available data.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param ServiceQueI $serviceQue
     * @param type $patientMedication
     * @param type $patientID
     * @return boolean
     */
    private function createPatientMedication(ServiceQueI $serviceQue, $patientMedication, $patientID) {
        if (!empty($patientMedication)) {
            $arrPatientMedication = array();
            if (isset($patientMedication[0])) {
                $arrPatientMedication = $patientMedication;
            } else {
                $arrPatientMedication[0] = $patientMedication;
            }
            foreach ($arrPatientMedication as $medication) {
                $arrObject = $arrProp = array();
                $propsRxNormName = 'drugRxNormId';
                if (isset($medication[$propsRxNormName]) && !empty($medication[$propsRxNormName])) {
                    $drugRxNormId = $medication[$propsRxNormName];
                    $this->createDbDateFormat($medication, 'startDate');
                    $arrProp['startDate'] = $medication['startDate'];
                    $propsStopDate = 'stopDate';
                    if (isset($medication[$propsStopDate]) && !empty($medication[$propsStopDate])) {
                        $this->createDbDateFormat($medication, $propsStopDate);
                        if ($arrProp['startDate'] <= $medication[$propsStopDate]) {
                            $arrProp['stopDate'] = $medication[$propsStopDate];
                        }
                    }
                    $arrProp['drugRxNormId'] = $drugRxNormId;
                    $arrProp['rxNormStr'] = $medication['medicationDesc'];
                    $arrProp['medicationDrugBrandName'] = $medication['medicationDrugBrandName'];
                    $arrProp['medicationDrugForm'] = $medication['medicationDrugForm'];
                    $arrProp['dose'] = $medication['dose'];
                    $arrProp['doseUnit'] = $medication['doseUnit'];
                    $arrResponse = CommonFunctions::sendElasticCurlRequests($this->elasticSearchUrl, 'rxnorms', 'rxnorm', 'rxcui', $drugRxNormId);
                    if ($arrResponse['status'] && !empty($arrResponse['data'])) {
                        $arrProp['rxNormStr'] = $arrResponse['data']['str'];
                    }
                    $arrConditions = array(
                        array(
                            'patientId' => $patientID
                        ),
                        array(
                            'drugRxNormId' => $drugRxNormId
                        )
                    );
                    if (isset($arrProp['startDate']) && isset($arrProp['stopDate'])) {
                        $arrConditions[] = array(
                            'startDate' => $arrProp['startDate']
                        );
                        $arrConditions[] = array(
                            'stopDate' => $arrProp['stopDate']
                        );
                    }
                    $arrProp['route'] = $medication['medicationDrugRoute'];
                    $arrPatientMedication = $this->fetchObjectData($serviceQue, self::objPatientMedication, $arrConditions);
                    if (!empty($arrPatientMedication)) {
                        $updateObjs = [];
                        $updateObjs['conditions']['object'][0] = $arrPatientMedication[0]['id'];
                        $updateObjs['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_UPDATE, $updateObjs);
                    } else {
                        $arrObject['objectType'] = self::objPatientMedication;
                        $arrObject['parent'] = $patientID;
                        $arrObject['properties'] = $arrProp;
                        $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $arrObject);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Function convert date into DB date format.
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $arrProps
     * @param type $propName
     * @return DB date format data
     */
    private function createDbDateFormat(&$arrProps, $propName) {
        if (isset($arrProps[$propName]) && !empty($arrProps[$propName])) {
            $dateTimeUtility = new DateTimeUtility();
            $arrProps[$propName] = $dateTimeUtility->convertFormat($arrProps[$propName], $dateTimeUtility::CCDA_XML_DATE_FORMAT, $dateTimeUtility::DATE_FORMAT);
        }
        return $arrProps;
    }

}
