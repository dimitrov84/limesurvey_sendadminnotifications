<?php

/**
 * sendAdminNotifications LimeSurvey Plugin
 * 
 * @author Dimitar Dimitrov <ddimitrov@partners.org>
 * @license Apache License 2.0
 * @version 3.0
 * 
 * LimeSurvey plugin to send / re-send Basic and Detailed Admin Notifications for surveys
 * The plugin, once installed, is triggered on the following screens:
 * - Browse Tokens (tokens browse)
 * - Browse Responses
 * - View Response Details
 * 
 * The "envelope" look has been replaced by an arrow that looks like a mail forward
 */

class sendAdminNotifications extends PluginBase {

    protected $storage = 'DbStorage';
    static protected $name = 'sendAdminNotifications';
    static protected $description = 'sendAdminNotifications plugin - A plugin to re-trigger the Admin notifications';
    private $iSurveyId = -1;

    private $bIsAdmin=false;
    private $sStatus="error";
    private $sMessage="There was an error processing the notification!";
    private $iNext=0;
    private $iUpdatedValueCount=0;
    private $iNulledValueCount=0;
    private $aUpdatedArray=array();

    public function init()
    {
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('newDirectRequest');
        $this->subscribe('beforeControllerAction','beforeToolsMenuRender_process');    
    }

    /**
     * Handle the script injection
     */
    public function beforeToolsMenuRender_process ( ){
        $event = $this->event;
        
        /**
             array (
                'controller' => 'admin',
                'action' => 'tokens',
                'subaction' => 'browse',
            ),
         */

        $oRequest=$this->pluginManager->getAPI()->getRequest();
        $sController=Yii::app()->getController()->getId();
    
        $qParams = Yii::app()->request->getQueryParams();
        $this->iSurveyId = is_array($qParams) && isset($qParams['surveyId']) ? htmlspecialchars($qParams['surveyId'], ENT_QUOTES, 'UTF-8') : -1; //Yii::app()->request->getParam('surveyId');
        if ( $event->get('action')=='tokens' && $event->get('subaction')=='index' ) {
            // The SurveyID here is not a parameter, but rather part of the URL
            $url_params = explode("/",Yii::app()->request->getRequestUri());
            if ( array_search("surveyid",$url_params) ) {
                $iI = array_search("surveyid",$url_params);
                if ( count($url_params) > $iI ) {
                    $this->iSurveyId = htmlspecialchars($url_params[$iI + 1], ENT_QUOTES, 'UTF-8'); // grab the survey ID from the URL
                }
            }

            if ( $this->iSurveyId != -1 ) {
                // REAL WORK in the tokens browse screen
                $aSubmitNotificationVar=array(
                    'jsonurl'=>$this->api->createUrl('plugins/direct', array('plugin' => get_class(),'surveyid'=>$this->iSurveyId,'function' => 'sendSubmitNotification'))
                    //'jsonurl'=>$this->api->createUrl('plugins/direct', array('plugin' => 'sendAdminNotifications', 'function' => 'sendSubmitNotification'))
                );
                // inject some global JS variables to make life easier  
                Yii::app()->getClientScript()->registerScript('aSubmitNotificationVar','sendNotificationVar='.json_encode($aSubmitNotificationVar),CClientScript::POS_BEGIN);
                Yii::app()->getClientScript()->registerScript('aSurveyIDVar','surveyIDVar='.json_encode($this->iSurveyId),CClientScript::POS_BEGIN);
                Yii::app()->getClientScript()->registerScript('aScreenAction','screenAction='.json_encode("tokens_browse"),CClientScript::POS_BEGIN);
                //Yii::app()->getClientScript()->registerScript('aPJaxReload', $('#token-grid').on('pjax:end',addSendSubmitNotification_list()), ,CClientScript::POS_BEGIN);
                // Inject the JS
                App()->clientScript->registerScriptFile(App()->assetManager->publish(dirname(__FILE__) . '/js/sendnotifications.js'));
            }
        }
        if ( $event->get('controller')=='responses' && $event->get('action')=='browse' ) {
            // REAL WORK in the Responses browse screen
            $aSubmitNotificationVar=array(
                'jsonurl'=>$this->api->createUrl('plugins/direct', array('plugin' => get_class(),'surveyid'=>$this->iSurveyId,'function' => 'sendSubmitNotification'))
            );
            // inject some global JS variables to make life easier
            Yii::app()->getClientScript()->registerScript('aSubmitNotificationVar','sendNotificationVar='.json_encode($aSubmitNotificationVar),CClientScript::POS_BEGIN);
            Yii::app()->getClientScript()->registerScript('aSurveyIDVar','surveyIDVar='.json_encode($this->iSurveyId),CClientScript::POS_BEGIN);
            Yii::app()->getClientScript()->registerScript('aScreenAction','screenAction='.json_encode("responses_browse"),CClientScript::POS_BEGIN);
            //Yii::app()->getClientScript()->registerScript('aPJaxReload', $('#responses-grid').on('pjax:end',addSendSubmitNotification_list()), ,CClientScript::POS_BEGIN);
            // Inject the JS
            App()->clientScript->registerScriptFile(App()->assetManager->publish(dirname(__FILE__) . '/js/sendnotifications.js'));
        }
        if ( $event->get('controller')=='responses' && $event->get('action')=='view' ) {
            $response_id = is_array($qParams) && isset($qParams['id']) ? htmlspecialchars($qParams['id'], ENT_QUOTES, 'UTF-8') : -1; //Yii::app()->request->getParam('id'); // get the resposne ID from 
            
            // REAL WORK in the Responses browse screen
            $aSubmitNotificationVar=array(
                'jsonurl'=>$this->api->createUrl('plugins/direct', array('plugin' => get_class(),'surveyid'=>$this->iSurveyId,'function' => 'sendSubmitNotification'))
            );
            // inject some global JS variables to make life easier
            Yii::app()->getClientScript()->registerScript('aSubmitNotificationVar','sendNotificationVar='.json_encode($aSubmitNotificationVar),CClientScript::POS_BEGIN);
            Yii::app()->getClientScript()->registerScript('aSurveyIDVar','surveyIDVar='.json_encode($this->iSurveyId),CClientScript::POS_BEGIN);
            Yii::app()->getClientScript()->registerScript('aScreenAction','screenAction='.json_encode("responses_view"),CClientScript::POS_BEGIN);
            Yii::app()->getClientScript()->registerScript('aCurrentResponseID','current_response_id='.json_encode($response_id),CClientScript::POS_BEGIN);
            // Inject the JS
            App()->clientScript->registerScriptFile(App()->assetManager->publish(dirname(__FILE__) . '/js/sendnotifications.js'));
        }

    }

    /**
     * Handle direct request
     * NOTE: Some of this code was developed in LimeSurvey 2.x so it is not 100% relevant for LimeSurvey 3.x
     */
    public function newDirectRequest() {
        $oEvent = $this->event;
        $sFunction=$oEvent->get('function');

        $qParams = Yii::app()->request->getQueryParams();

        $this->iSurveyId=$iSurveyId=is_array($qParams) && isset($qParams['surveyid']) ? htmlspecialchars($qParams['surveyid'], ENT_QUOTES, 'UTF-8') : -1; //$this->api->getRequest()->getParam('surveyid');
        $oSurvey=Survey::model()->findByPK($iSurveyId);
        if(!$oSurvey)
            throw new CHttpException(404, gt("The survey does not seem to exist."));
        if(!Permission::model()->hasSurveyPermission($iSurveyId, 'tokens', 'update'))
            throw new CHttpException(401, gt("You do not have sufficient rights to access this page."));
        if(!tableExists('{{tokens_' . $iSurveyId . '}}'))
            throw new CHttpException(404, gt("Token table don't exist."));
        //if($oSurvey->active!="Y")
            //throw new CHttpException(404, gt("The survey seem’s inactive."));
        
        
            // Optionnal parameters : token
            $sToken=(string)Yii::app()->request->getQuery('token', "");
            // Optionnal parameters : srid
            $iResponseId=(int)Yii::app()->request->getQuery('srid', 0);
            // Optionnal parameters : tid
            $iTokenID=(int)Yii::app()->request->getQuery('tid', 0);

            // Screen Type can be T (token browse), R (Responses), 
            // The TID in the Token browse correspond to the Token ID
            // The TID int he Responses screen correspond to the Response ID
            $screenType = (string)Yii::app()->request->getQuery('type', "");

            $sNullNoRelevance = $this->get('sNullNoRelevance');
            $bNullNoRelevance =($sNullNoRelevance=="allways" || ($sNullNoRelevance=="deletenonvalues" && Yii::app()->getConfig('deletenonvalues')));
            $aSurveyInfo=getSurveyInfo($iSurveyId);
            $bError=false;
            $sMessage="";
            
            if($sToken && !(tableExists('{{tokens_'.$aSurveyInfo['sid'].'}}') || $aSurveyInfo['anonymized']!="N"))
            {
                throw new CHttpException(400, 'Token table is not set or survey is anonymous.');
            }
            Yii::app()->setConfig('surveyID',$iSurveyId);
            $bIsTokenSurvey=tableExists("tokens_{$iSurveyId}");
    /**            if(Permission::model()->hasSurveyPermission($iSurveyId,'responses','update'))
            {
                $this->bIsAdmin=true;// Admin view response and can update one by one. Else : only update if token or srid
            }
    */
            $iNextSrid=0;

            // Find the oResponse according to parameters
            $oResponse=NULL;
            if($screenType == 'T' || $screenType == 't') {
                //$oResponse=SurveyDynamic::model($iSurveyId)->find("tid = :tid",array(':tid'=>$iTokenID));
                $oToken=Token::model($iSurveyId)->find("tid=:tid",array(":tid"=>$iTokenID));
                if(!$oToken)
                    $this->sMessage="Invalid Token ID";
                
                $oResponse=SurveyDynamic::model($iSurveyId)->find("token = :token",array(':token'=>$oToken->token));
                if(!$oResponse) {
                    $this->sMessage="Invalid Response ID or No Response ID Found";
                    $this->sStatus="error";
                    $this->displayJson();
                    return; // No more can be done here
                }
            }
            if($screenType == 'R' || $screenType == 'r')
            {
                // iTokenID here is equal to the Response ID - lazy re-use of a variable name .. sorry
                $oResponse=SurveyDynamic::model($iSurveyId)->find("id = :id",array(':id'=>$iTokenID));
                if(!$oResponse) {
                    $this->sMessage="Invalid Response ID";
                    $this->sStatus="error";
                    $this->displayJson();
                    return; // No more can be done here
                }
            }
            elseif($sToken)
            {
                $oResponse=SurveyDynamic::model($iSurveyId)->find("token = :token",array(':token'=>$sToken));
                if(!$oResponse)
                    $this->sMessage="Invalid Token";
            }
            elseif($bDoNext && $this->bIsAdmin)
            {
                $oResponse=SurveyDynamic::model($iSurveyId)->find( 
                    array(
                        'condition'=>'submitdate IS NOT NULL',
                        'order'=>'id'
                    )
                );
                if(!$oResponse)
                    $this->sMessage="No submited response";
            }

            if($oResponse)
            {
                $iResponseId=$oResponse->id;
                $aOldAnswers=$oResponse->attributes; // Not needed but keep it
                
                if(isset($oResponse->token) && $oResponse->token)
                    $sToken=$oResponse->token;
                // Fill $_SESSION['survey_'.$iSurveyId]
                // buildsurveysession($iSurveyId);


                // ------------------------- this code is from the frontend_helper -> function buildsurveysession
                // We can't use the buildsurveysession function directly due to template -> controller inclusion issue
                $thissurvey = getSurveyInfo($iSurveyId, $sLangCode);
                $survey = Survey::model()->findByPk($iSurveyId);
                resetAllSessionVariables($iSurveyId);
                
                UpdateGroupList($iSurveyId, $_SESSION['survey_' . $iSurveyId]['s_lang']);

                $totalquestions               = $survey->countTotalQuestions;
                $iTotalGroupsWithoutQuestions = QuestionGroup::model()->getTotalGroupsWithoutQuestions($iSurveyId);

                $_SESSION['survey_' . $iSurveyId]['totalquestions'] = $survey->countInputQuestions;

                // 2. SESSION VARIABLE: totalsteps
                setTotalSteps($iSurveyId, $thissurvey, $totalquestions);

                // Break out and crash if there are no questions!
                if (($totalquestions == 0 || $iTotalGroupsWithoutQuestions > 0) && !$preview) {
                    breakOutAndCrash($sTemplateViewPath, $totalquestions, $iTotalGroupsWithoutQuestions, $thissurvey);
                }

                //3. SESSION VARIABLE - insertarray
                //An array containing information about used to insert the data into the db at the submit stage
                //4. SESSION VARIABLE - fieldarray
                //See rem at end..

                if ($tokensexist == 1 && $clienttoken) {
                    $_SESSION['survey_' . $iSurveyId]['token'] = $clienttoken;
                }

                if ($thissurvey['anonymized'] == "N") {
                    $_SESSION['survey_' . $iSurveyId]['insertarray'][] = "token";
                }

                $fieldmap = $_SESSION['survey_' . $iSurveyId]['fieldmap'] = createFieldMap($survey, 'full', true, false, $_SESSION['survey_' . $iSurveyId]['s_lang']);

                // first call to initFieldArray
                initFieldArray($iSurveyId, $fieldmap);

                // Prefill questions/answers from command line params
                prefillFromCommandLine($iSurveyId);

                if (isset($_SESSION['survey_' . $iSurveyId]['fieldarray'])) {
                    $_SESSION['survey_' . $iSurveyId]['fieldarray'] = array_values($_SESSION['survey_' . $iSurveyId]['fieldarray']);
                }
            
                //Check if a passthru label and value have been included in the query url
                checkPassthruLabel($iSurveyId, $preview, $fieldmap);

                // -------------------------------------------------------------

                $_SESSION['survey_'.$iSurveyId]['srid']=$iResponseId;
                if(isset($oResponse->startlanguage ) && $oResponse->startlanguage )
                {
                    $_SESSION['survey_'.$iSurveyId]['s_lang']=$oResponse->startlanguage;
                    //SetSurveyLanguage($iSurveyId, $oResponse->startlanguage);//$clang=>Yii->app->lang;
                }
                else
                {
                    //SetSurveyLanguage($iSurveyId);
                }

                $rooturl=Yii::app()->baseUrl . '/';
                $step=0;
                if(isset($oResponse->lastpage) && $oResponse->lastpage)
                {
                    $_SESSION['survey_'.$iSurveyId]['prevstep']=-1;
                    $_SESSION['survey_'.$iSurveyId]['maxstep'] = $oResponse->lastpage;
                    $_SESSION['survey_'.$iSurveyId]['step'] = $oResponse->lastpage;
                }
                $_SESSION['survey_'.$iSurveyId]['LEMtokenResume']=true;
                $radix=getRadixPointData($aSurveyInfo['surveyls_numberformat']);
                $radix = $radix['separator'];
                $aEmSurveyOptions = array(
                    'active' => ($aSurveyInfo['active'] == 'Y'),
                    'allowsave' => ($aSurveyInfo['allowsave'] == 'Y'),
                    'anonymized' => ($aSurveyInfo['anonymized'] != 'N'),
                    'assessments' => ($aSurveyInfo['assessments'] == 'Y'),
                    'datestamp' => ($aSurveyInfo['datestamp'] == 'Y'),
                    'deletenonvalues'=>Yii::app()->getConfig('deletenonvalues'),
                    'hyperlinkSyntaxHighlighting' => false,
                    'ipaddr' => ($aSurveyInfo['ipaddr'] == 'Y'),
                    'radix'=>$radix,
                    'refurl' => (($aSurveyInfo['refurl'] == "Y" && isset($oResponse->refurl)) ? $oResponse->refurl : NULL),
                    'savetimings' => ($aSurveyInfo['savetimings'] == "Y"),
                    'surveyls_dateformat' => (isset($aSurveyInfo['surveyls_dateformat']) ? $aSurveyInfo['surveyls_dateformat'] : 1),
                    'startlanguage'=>(isset($clang->langcode) ? $clang->langcode : $aSurveyInfo['language']),
                    'target' => Yii::app()->getConfig('uploaddir').DIRECTORY_SEPARATOR.'surveys'.DIRECTORY_SEPARATOR.$aSurveyInfo['sid'].DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR,
                    'tempdir' => Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR,
                    'timeadjust' => 0,
                    'token' => (isset($sToken) ? $sToken : NULL),
                );
                LimeExpressionManager::SetDirtyFlag();
                //buildsurveysession($iSurveyId);
                foreach($aOldAnswers as $column=>$value){
                    if (in_array($column, $_SESSION['survey_'.$iSurveyId]['insertarray']) && isset($_SESSION['survey_'.$iSurveyId]['fieldmap'][$column]))
                    {
                        $_SESSION['survey_'.$iSurveyId][$column]=$value;
                    }
                }
                // Use ExpressionManager to fill Session .... 
                LimeExpressionManager::StartSurvey($iSurveyId,'survey',$aEmSurveyOptions);// This is needed for EM ; use survey to do whole Expression
                LimeExpressionManager::JumpTo(2,false,false,true) ;// To set all relevanceStatus 
                $aInsertArray=$_SESSION['survey_'.$iSurveyId]['insertarray'];
                $aFieldMap=$_SESSION['survey_'.$iSurveyId]['fieldmap'];
                $updatedValues=array('old'=>array(),'new'=>array());
                foreach($aOldAnswers as $column=>$value)
                {
                    if (in_array($column,$aInsertArray) && isset($aFieldMap[$column]))
                    {
                        Yii::import('application.helpers.viewHelper');
                        $sColumnName=viewHelper::getFieldCode($aFieldMap[$column]);
                        $bRelevance=true;
                        if($bNullNoRelevance && isset($aFieldMap[$column]['relevance']) && trim($aFieldMap[$column]['relevance']!=""))
                        {
                            $bRelevance= (bool)LimeExpressionManager::ProcessString("{".$aFieldMap[$column]['relevance']."}");
                            if(!$bRelevance)
                            {
                                if(!is_null($oResponse->$column))
                                {
                                    $updatedValues['old'][$sColumnName]=$oResponse->$column;
                                    $updatedValues['new'][$sColumnName]=null;
                                    $this->iNulledValueCount++;
                                }
                                $oResponse->$column= null;
                            }
                            
                        }
                        if ($aFieldMap[$column]['type'] == '*' && $bRelevance)
                        {
                            //($string, $questionNum=NULL, $replacementFields=array(), $debug=false, $numRecursionLevels=1, $whichPrettyPrintIteration=1, $noReplacements=false, $timeit=true, $staticReplacement=false)
                            $oldVal=$oResponse->$column;
                            $newVal=$oResponse->$column=LimeExpressionManager::ProcessString($aFieldMap[$column]['question'], null, array(), false, 1, 0, false, false, true);
                            if($oldVal!=$newVal && ($oldVal && $newVal))
                            {
                                $updatedValues['old'][$sColumnName]=$oldVal;
                                $updatedValues['new'][$sColumnName]=$newVal;
                                $this->iUpdatedValueCount++;
                            }
                        }
                    }
                }
                //$oResponse->save(); // We don't really want to save this, though
                // Send the submit notifications
                global $thissurvey;
                $thissurvey = getSurveyInfo($iSurveyId);
                $_SESSION['survey_'.$iSurveyId]['srid'] = $iResponseId;
                $lang = Survey::model()->findByPk($iSurveyId)->language;
                $_SESSION['survey_'.$iSurveyId]['s_lang'] = $lang;

                sendSubmitNotifications($iSurveyId);
                // Construct a message
                $this->sStatus  =   "success";
                $this->sMessage =   sprintf('Submit Notification was successfully sent for Response ID %s',$iResponseId);

                // Then see if we should display an error message - this is backwards, but it's OK
                $aEmailNotificationTo=array();
                if ( (!isset($thissurvey['emailnotificationto']) || empty($thissurvey['emailnotificationto'])) && 
                    (!isset($thissurvey['emailresponseto']) || empty($thissurvey['emailresponseto']))) {
                        $this->sStatus  =   "error";
                                $this->sMessage =   sprintf('Could not send e-mail because No Admin e-mail is specified');
                }
                else {
                    if(validateEmailAddress($thissurvey['emailresponseto'])) {
                        $aEmailNotificationTo[]=$thissurvey['emailresponseto'];
                    }
                    if(validateEmailAddress($thissurvey['emailnotificationto'])) {
                                        $aEmailNotificationTo[]=$thissurvey['emailnotificationto'];
                                }
                    if ( count($aEmailNotificationTo)<=0) {
                        $this->sStatus  =   "error";
                                    $this->sMessage =   sprintf('Could not send e-mail because No Admin e-mail is specified');
                    }
                }
            }
            $this->displayJson();

    }
        
    private function displayJson()
    {
        Yii::import('application.helpers.viewHelper');
        viewHelper::disableHtmlLogging();
        header('Content-type: application/json');
        echo json_encode(
            array(
                "status"=>$this->sStatus,
                "message"=>$this->sMessage,
                "next"=>$this->iNext
            )
        );
        die();
    }
}
