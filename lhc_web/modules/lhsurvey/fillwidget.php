<?php

// For IE to support headers if chat is installed on different domain
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

$tpl = erLhcoreClassTemplate::getInstance( 'lhsurvey/fillwidget.tpl.php');

$embedMode = false;
if ((string)$Params['user_parameters_unordered']['mode'] == 'embed') {
	$embedMode = true;
}

if (isset($Params['user_parameters_unordered']['theme']) && ($themeId = erLhcoreClassChat::extractTheme($Params['user_parameters_unordered']['theme'])) !== false) {
	try {
		$theme = erLhAbstractModelWidgetTheme::fetch($themeId);
		$Result['theme'] = $theme;
		$tpl->set('theme',$theme);
	} catch (Exception $e) {

	}
}

try {
  
    if (is_numeric((string)$Params['user_parameters_unordered']['chatid']) && $Params['user_parameters_unordered']['chatid'] > 0) {
        
        if ((string)$Params['user_parameters_unordered']['hash'] != '') {
            $hash = $Params['user_parameters_unordered']['hash'];
        }
        
        if (is_numeric($Params['user_parameters_unordered']['chatid'])) {
            $chat = erLhcoreClassModelChat::fetch($Params['user_parameters_unordered']['chatid']);
        }
        
    } else if ((string)$Params['user_parameters_unordered']['hash'] != '') {
        list($chatID,$hash) = explode('_',$Params['user_parameters_unordered']['hash']);    
        $chat = erLhcoreClassModelChat::fetch($chatID);
    }
    
    erLhcoreClassChat::setTimeZoneByChat($chat);

    $chatVariables = $chat->chat_variables_array;

    if (erLhcoreClassModelChatBlockedUser::isBlocked(array('online_user_id' => $chat->online_user_id, 'country_code' => $chat->country_code, 'ip' => $chat->ip, 'dep_id' => $chat->dep_id, 'nick' => $chat->nick, 'email' => $chat->email)) || (isset($chatVariables['lhc_ds']) && (int)$chatVariables['lhc_ds'] == 0)) {
        throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('chat/startchat','At this moment you can contact us via email only. Sorry for the inconveniences.'));
    }

    if ($chat->hash == $hash)
    {
        $survey = erLhAbstractModelSurvey::fetch($Params['user_parameters_unordered']['survey']);
        $survey->translate();

        if ($survey instanceof erLhAbstractModelSurvey) {
            $surveyItem = erLhAbstractModelSurveyItem::getInstance($chat, $survey);

            $collectedSurvey = false;
            
            if (isset($_POST['Vote'])) {
                $errors = erLhcoreClassSurveyValidator::validateSurvey($surveyItem, $survey);
                if (empty($errors)) {
                    $surveyItem->status =  erLhAbstractModelSurveyItem::STATUS_PERSISTENT;
                    $surveyItem->saveOrUpdate();

                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('survey.filled', array('chat' => & $chat, 'survey' => $survey, 'survey_item' => & $surveyItem));

                    $tpl->set('just_stored', true);
                    
                    $collectedSurvey = true;
                                       
                } else {
                    $tpl->set('errors', $errors);
                }
            }

            if ($collectedSurvey === false) {
                $chat->user_status = erLhcoreClassModelChat::USER_STATUS_CLOSED_CHAT;
                $chat->updateThis(['update' => ['user_status']]);
            }

            if (($collectedSurvey === true || $surveyItem->is_filled == true) && $chat->status_sub == erLhcoreClassModelChat::STATUS_SUB_SURVEY_SHOW) {
                $chat->status_sub = erLhcoreClassModelChat::STATUS_SUB_SURVEY_COLLECTED;
                // They are equal now in priority for the auto responder
                $chat->last_op_msg_time = $chat->last_user_msg_time = time();
                $chat->saveThis();
            }
            
            $tpl->set('chat', $chat);
            $tpl->set('survey', $survey);
            $tpl->set('survey_item', $surveyItem);

            $Result['chat'] = $chat;
            
        } else {
            $tpl->setFile( 'lhchat/errors/surveynotexists.tpl.php');
        }
    } else {
        $tpl->setFile( 'lhchat/errors/chatnotexists.tpl.php');
    }

} catch(Exception $e) {
   $tpl->setFile('lhchat/errors/chatnotexists.tpl.php');
}

$Result['content'] = $tpl->fetch();
$Result['pagelayout'] = 'widget';
$Result['pagelayout_css_append'] = 'widget-chat';
$Result['dynamic_height'] = true;
$Result['dynamic_height_message'] = 'lhc_sizing_chat';
$Result['path'] = array(array('title' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chat','Chat started')));
$Result['is_sync_required'] = false;

if ($embedMode == true) {
	$Result['dynamic_height_message'] = 'lhc_sizing_chat_page';
	$Result['pagelayout_css_append'] = 'embed-widget';
}

?>