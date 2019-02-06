<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientEncounterPrintRequest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use \FPDF_FPDI;

/**
 * BL class for preExecute of PatientEncounterPrintRequest.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;

class PreExecuteM extends FPDF_FPDI implements PreExecuteI
{

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $patientEncounterId = $data->getData('parent');
        $searchEncounter = [];
        $searchEncounter[0]['objectId'] = $patientEncounterId;
        $searchEncounter[0]['outKey'] = 'response';
        $response = $serviceQue->executeQue("ws_oml_read", $searchEncounter);

        $executionId = $response['data']['response']['executionId'];

        $searchKey = [];
        $searchKey[0]['type'] = 'workList';
        $searchKey[0]['conditions'][] = array('ezcExecutionId' => $executionId);
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (!empty($resp['data']['response'])) {
            $outputFilePath = getcwd() . '/temp/cqm/concat.pdf';
            foreach ($resp['data']['response'] as $worklist) {
                // $filePath = getcwd() . '/temp/cqm/' . $worklist['id'].'_'.time() . ".pdf";
                // $fileContent = file_get_contents($worklist['pdfDocumentUrl']);
                // file_put_contents($filePath, $fileContent);
                $filePath = getcwd() . '/temp/cqm/workList:tagkpcy_1459515782.pdf';
                $pageCount = $this->setSourceFile($filePath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tplIdx = $this->ImportPage($pageNo);
                    $s = $this->getTemplatesize($tplIdx);
                    $this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
                    $this->useTemplate($tplIdx);
                }
                //@unlink($filePath);
            }
            $this->Output($outputFilePath, 'F');
            $outFileContent = file_get_contents($outputFilePath);
            $uploadRequest = array(
                'objectType' => 'patientEncounterPrintRequest',
                'property' => 'pdfDocument',
                'fileName' => 'report.pdf',
                'fileContent' => base64_encode($outFileContent),
            );
            $response = $serviceQue->executeQue('ws_oml_file_upload', $uploadRequest);
        }
        echo "<pre>";print_r($resp);die;
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
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
    /**
     * Function will perform some actions after execute get
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
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}
