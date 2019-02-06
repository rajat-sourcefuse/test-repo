<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEligibilityRequest;

use SynapEssentials\BillingEDIBundle\Implementations\EDI271Parser\Ansi271Processor;
use Externals\ClearingHouseBundle\Implementations\ClearingHouseFactory;
use Externals\ClearingHouseBundle\Implementations\Beans\FtpConfig;
use Externals\ClearingHouseBundle\Implementations\Beans\EdiFileExtensions;
use Externals\ClearingHouseBundle\Implementations\Beans\TunnelConfig;
use SynapEssentials\BillingEDIBundle\Implementations\EDI270Base;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * BL class for postExecute of OrganizationEligibilityRequest.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI {

    const REQUEST_TYPE_REAL_TIME = 'metaEligibilityRequestType:realtime';
    const REQUEST_TYPE_BATCH = 'metaEligibilityRequestType:batch';
    const TUNNEL_TYPE_ZIRMED = 'metaTransmissionTunnelType:zirmed';
    const TUNNEL_TYPE_SFTP = 'metaTransmissionTunnelType:sftp';
    const TUNNEL_TYPE_CLAIM_MD = "metaTransmissionTunnelType:claimmd";
    const REQUEST_STATUS_SENT = 'metaEligibilityRequestStatus:requestSent';
    const REQUEST_STATUS_FAILED = 'metaEligibilityRequestStatus:requestFailed';
    const REQUEST_STATUS_RECEIVED = 'metaEligibilityRequestStatus:responseReceived';

    /**
     * Function will be execute after creation of OrganizationEligibilityRequest
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        //process 270 request
        $this->process270Request($data, $serviceQue);
        return true;
    }

    /**
     * Function will perform some actions after execute delete
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
     * Function will perform some actions after execute update
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will preapre data to generate 270 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function process270Request($data, ServiceQueI $serviceQue) {
        //get eligibilityRequestId, patientId, location Id and insurance id
        $eligibiltyRequestId = $data->getData('id');
        $patientId = $data->getData('properties')['patientId'];
        $locationId = $data->getData('properties')['locationId'];
        $primaryPayerId = $data->getData('properties')['insuranceId'];

        $requestType = isset($data->getData('properties')['requestType']) ? $data->getData('properties')['requestType'] : self::REQUEST_TYPE_REAL_TIME;
        $trackingId = isset($data->getData('properties')['trackingId']) ? $data->getData('properties')['trackingId'] : '';

        //if payer or location information is missing throw error
        if (empty($primaryPayerId) || empty($locationId)) {
            throw new SynapExceptions(SynapExceptionConstants::PAYER_AND_LOCATION_MANDATORY, 400);
        }

        //get primary insuarnce data using primary payer id
        $getPrimaryPayerInfo = $this->fetchObjectData($primaryPayerId, $serviceQue);
        //get location data using primary location id
        $locationInfo = $this->fetchObjectData($locationId, $serviceQue);
        //get patient information using patient id
        $patientInfo = $this->fetchObjectData($patientId, $serviceQue);

        //get payer config using insuranceCompanyName and location
        $payerConfigInfo = $this->getPayerConfigInfo($getPrimaryPayerInfo['insuranceCompanyName'], $locationId, $serviceQue);
        
        //if payer config is missing through error
        if (empty($payerConfigInfo)) {
            throw new SynapExceptions(SynapExceptionConstants::PAYER_CONFIG_MISSING_FOR_TAX_ENTITY, 400);
        }
        //append eqRequest information into payer info
        $payerConfigInfo['eqRequest'] = $locationInfo['eqRequest'];

        //get isa06 and isa08 from payer config details
        $isa06 = $payerConfigInfo[0]['transmissionTunnelIdIsa06']; //client id for 270 class
        $isa08 = $payerConfigInfo[0]['transmissionTunnelIdIsa08']; //receiverId for 270 class
        //create 270base class instance and call generate270 function
        $edi270 = new EDI270Base(FALSE, "", $isa06, "", $getPrimaryPayerInfo, $payerConfigInfo, $trackingId, $isa08);
        $response = $edi270->generate270($patientInfo);

        //upload response data to cdn server
        $cdnResponse = $this->uploadFileToCdn($response, $serviceQue);

        if (isset($cdnResponse['data']['id'])) {
            //if we get response from cdn, update eligibilityRequest object with cdnUrl
            $this->updateObjectForUrl($eligibiltyRequestId, $cdnResponse['data']['id'], $serviceQue);
        }

        //send 270 content to clearing house for getting 271 as a response
        $response270 = $this->process270ForClearingHouse($requestType, $payerConfigInfo, $response);

        if (!empty($response270)) {
            if ($requestType == self::REQUEST_TYPE_BATCH) {
                $this->processBatchResponse($response270, $eligibiltyRequestId, $serviceQue);
            } else if ($requestType == self::REQUEST_TYPE_REAL_TIME) {
                $this->processRealTimeResponse($response270, $locationId, $eligibiltyRequestId, $serviceQue);
            }
        }
        return true;
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
     * Function will fetch payer config based on payerId and locationId
     * 
     * @param $payerId
     * @param $locationId
     * @param ServiceQueI $serviceQue
     * @return $payerTaxEntityData
     */
    private function getPayerConfigInfo($payerId, $locationId, ServiceQueI $serviceQue) {
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityPayerConfig';
        $searchKey[0]['conditions'][] = array('organizationPayerId' => $payerId);
        $searchKey[0]['conditions'][] = array('locationId' => $locationId);
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $payerTaxEntityData = $resp['data']['response'];
        return $payerTaxEntityData;
    }

    /**
     * Function will upload given file to CDN
     * 
     * @param type $fileContent
     * @return $response (response from cdn)
     */
    private function uploadFileToCdn($fileContent, ServiceQueI $serviceQue) {
        $uploadRequest = array(
            'objectType' => 'organizationEligibilityRequest',
            'property' => 'edi270CdnUrl',
            'fileName' => 'edi-270.edi',
            'fileContent' => base64_encode($fileContent),
        );
        $response = $serviceQue->executeQue('ws_oml_file_upload', $uploadRequest);
        return $response;
    }

    /**
     * Function will update cdn url into eligibilityRequest object
     * 
     * @param type $eligibilityRequestId,$cdnId
     * @return
     */
    private function updateObjectForUrl($eligibilityRequestId, $cdnId, $serviceQue) {
        $updateArr = [];
        $updateArr['edi270CdnUrl'] = $cdnId;
        $updateObjs['conditions']['object'][0] = $eligibilityRequestId;
        $updateObjs['conditions']['status'] = self::REQUEST_STATUS_SENT;
        $updateObjs['properties'] = $updateArr;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return;
    }

    /**
     * Function will send 270 file to clearing house for response
     * @param type $requestType (batch/real time)
     * @param type $apiUser
     * @param type $apiSecret
     * @param type $content270 (file 270 content)
     * @return
     */
    private function process270ForClearingHouse($requestType, $payerConfigInfo, $content270) {

        $apiUser = $payerConfigInfo[0]['transmissionTunnelIdClearingHouseAPIUser'];
        $apiSecret = $payerConfigInfo[0]['transmissionTunnelIdClearingHouseAPISecret'];
        if ($requestType == self::REQUEST_TYPE_BATCH) {
            if ($payerConfigInfo[0]['transmissionTunnelIdType'] != self::TUNNEL_TYPE_SFTP) {
                return;
            }
            $edi270Extension = $payerConfigInfo[0]['transmissionTunnelIdEdi270Extension']; //edi270ext
            $edi271Extension = $payerConfigInfo[0]['transmissionTunnelIdEdi271Extension']; //edi271ext
            $edi837PExtension = $payerConfigInfo[0]['transmissionTunnelIdEdi837PExtension']; //edi837pext
            $edi999PExtension = $payerConfigInfo[0]['transmissionTunnelIdEdi999PExtension']; //edi999ext
            $edi835Extension = $payerConfigInfo[0]['transmissionTunnelIdEdi835Extension']; //edi835ext
            $cdrExtension = $payerConfigInfo[0]['transmissionTunnelIdCdrExtension']; //cdrext
            $sftpUrl = $payerConfigInfo[0]['transmissionTunnelIdSftpUrl']; //sftp url
            $sftpUploadFolder = $payerConfigInfo[0]['transmissionTunnelIdSftpUploadFolder']; //sftp upload folder
            $sftpDownloadFolder = $payerConfigInfo[0]['transmissionTunnelIdSftpDownloadFolder']; //sftp download folder
            $sftpArchiveFolder = $payerConfigInfo[0]['transmissionTunnelIdSftpArchiveFolder']; //sftp archive folder

            $ftpConfig = new FtpConfig($sftpUrl, $sftpUploadFolder, $sftpDownloadFolder, $sftpArchiveFolder);
            $ediFileExtension = new EdiFileExtensions($edi270Extension, $edi271Extension, $edi837PExtension, $edi999PExtension, $edi835Extension, $cdrExtension);
            //create tunnel config object for tunnel type sftp
            $config = new TunnelConfig(self::TUNNEL_TYPE_SFTP, $apiUser, $apiSecret, $ftpConfig, $ediFileExtension);
        } else if ($requestType == self::REQUEST_TYPE_REAL_TIME) {
            if ($payerConfigInfo[0]['transmissionTunnelIdType'] != self::TUNNEL_TYPE_CLAIM_MD) {
                throw new SynapExceptions(SynapExceptionConstants::TRANSMISSION_TUNNEL_NOT_VALID, 400);
            }
            //create tunnnel config object for tunnel type claimmd
            $config = new TunnelConfig(self::TUNNEL_TYPE_CLAIM_MD, $apiUser, $apiSecret);
        }
        //create instance of Clerainghouse factory and get object using tunnel config object
        $obj = ClearingHouseFactory::getInstance()->getObject($config);
        $response270 = $obj->sendEligibilityRequest($content270);
        //$this->processBatchResponse($response270); 
        return $response270;
    }

    /**
     * Function will update the eligibilityRequest object for 270 response
     * @param type $response270Obj
     * @param type $eligibilityRequestId
     * @param type $serviceQue
     * @return
     */
    private function processRealTimeResponse($response270Obj, $locationId, $eligibilityRequestId, $serviceQue) {

        $content271 = $response270Obj->getEdi();
        $content271ErrorMessage = $response270Obj->getError();

        $updateArr = [];
        if ($content271 != null) {
            $updateArr = $this->parse271AndCreateXml($content271, $locationId, $serviceQue);
        }

        if ($content271ErrorMessage != null) {

            throw new SynapExceptions($content271ErrorMessage, 400);
//            $updateArr['response'] = $content271ErrorMessage;
//            $updateArr['status'] = self::REQUEST_STATUS_FAILED;
        }

        if (!empty($updateArr)) {
            $updateObjs['conditions']['object'][0] = $eligibilityRequestId;
            $updateObjs['properties'] = $updateArr;
            $serviceQue->executeQue("ws_oml_update", $updateObjs);
        }
        return;
    }

    /**
     * Function will update the eligibilityRequest object for 270 response for batch
     * @param type $response270Obj
     * @param type $eligibilityRequestId
     * @param type $serviceQue
     * @return
     */
    private function processBatchResponse($response270Obj, $eligibilityRequestId, $serviceQue) {

//        $content271ErrorMessage = $response270Obj->getError();
//
//        $updateArr = [];
//
//        if ($content271ErrorMessage != null) {
//            $updateArr['response'] = $content271ErrorMessage;
//            $updateArr['status'] = self::REQUEST_STATUS_FAILED;
//        }

        //if (!empty($updateArr)) {
        $updateObjs['conditions']['object'][0] = $eligibilityRequestId;
        $updateObjs['properties']['status'] = self::REQUEST_STATUS_RECEIVED;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        //}
        return;
    }

    /**
     * Function will parse 271 and upload content on s and update object for same
     * @param type $content271
     * @return $cdn271Url
     */
    private function parse271AndCreateXml($content271, $locationId, $serviceQue) {

        //get eqRequest using location id
        $locationData = $this->fetchObjectData($locationId, $serviceQue);
        $eqRequest = isset($locationData['eqRequest']) ? $locationData['eqRequest'] : '30^UC';

        $ansiObj = new Ansi271Processor($content271, $eqRequest);

        $ansiObj->processMessage();
        $parsed271 = $ansiObj->returnMessage();

        //Temporarily getting content from predefined xml to show at UI
        //$filename = getcwd() . '/temp/xml271.xml';
        //$xml271 = file_get_contents($filename);
        //$xml271 = $ansiObj->returnViewJson();
        $json271Arr = $ansiObj->returnViewJson();

        //upload 271 edi and get URL
        $uploadRequest = array(
            'objectType' => 'organizationEligibilityRequest',
            'property' => 'edi271CdnUrl',
            'fileName' => 'edi-271.edi',
            'fileContent' => base64_encode($parsed271),
        );
        $updateArr = []; //initialize update arr to set response

        $cdnResponse = $serviceQue->executeQue('ws_oml_file_upload', $uploadRequest);
        if (isset($cdnResponse['data']['id'])) {
            $updateArr['edi271CdnUrl'] = $cdnResponse['data']['id'];
            $updateArr['status'] = self::REQUEST_STATUS_RECEIVED;
        }

        //$json271 = $this->convertXmltoJson($xml271);

        //upload 271 json and get URL
//        $uploadJsonRequest = array(
//            'objectType' => 'organizationEligibilityRequest',
//            'property' => 'json271CdnUrl',
//            'fileName' => 'json-271.json',
//            'fileContent' => $json271,
//        );

        //$cdnJsonResponse = $serviceQue->executeQue('ws_oml_file_upload', $uploadJsonRequest);
        if (!empty($json271Arr)) {
            $encodeJson = $json271Arr;
            $updateArr['json271'] = $encodeJson;
        }
        return $updateArr;
    }

    /**
     * Function will convert given xml content to json
     * @param type $content271Xml
     * @return $content271Json
     */
    private function convertXmltoJson($content271Xml) {

        $contentWithoutLineBreak = str_replace(array("\n", "\r", "\t"), '', $content271Xml);

        $contentWithoutSingleQuote = trim(str_replace("'", " ", $contentWithoutLineBreak));

        $contentWithSingleQquote = trim(str_replace('"', "'", $contentWithoutSingleQuote));

        $simpleXml = simplexml_load_string($contentWithSingleQquote);

        $content271Json = json_encode($simpleXml);

        $content271JsonDecode = json_decode($content271Json);

        return $content271JsonDecode;
    }

}
