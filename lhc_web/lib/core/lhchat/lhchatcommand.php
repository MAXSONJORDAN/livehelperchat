<?php

/**
 * Responsible for executing !<command> based queries
 * 
 * */
class erLhcoreClassChatCommand
{

    private static $supportedCommands = array(
        '!name' => 'self::setName',
        '!email' => 'self::setEmail',
        '!phone' => 'self::setPhone',
        '!goto' => 'self::redirectTo',
        '!translate' => 'self::startTranslation',
        '!screenshot' => 'self::takeScreenshot',
        '!contactform' => 'self::contactForm',
        '!block' => 'self::blockUser',
        '!close' => 'self::closeChat',
        '!closed' => 'self::closeChatDialog',
        '!delete' => 'self::deleteChat',
        '!pending' => 'self::pendingChat',
        '!active' => 'self::activeChat',
        '!remark' => 'self::addRemark',
        '!info' => 'self::info',
        '!help' => 'self::help',
    	'!note' => 'self::notice',
    	'!hold' => 'self::hold',
    	'!gotobot' => 'self::goToBot',
    	'!transferforce' => 'self::transferforce',
    	'!files' => 'self::enableFiles',
    	'!stopfiles' => 'self::disableFiles',
    	'!modal' => 'self::showModal',
    );

    private static function extractCommand($message)
    {
        $params = explode(' ', $message);
        
        $commandData['command'] = array_shift($params);
        $commandData['argument'] = trim(implode(' ', $params));
        
        return $commandData;
    }

    public static function showModal($params) {

        if (!isset($params['argument']) || empty($params['argument'])) {
            return array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Please provide modal URL!')
            );
        }

        $paramsURL = explode(' ',$params['argument']);
        $URL = array_shift($paramsURL);

        if (is_numeric($URL)) {
            $URL = erLhcoreClassSystem::getHost() . erLhcoreClassDesign::baseurldirect('form/formwidget') . '/' . $URL;
        }

        // Store as message to visitor
        $msg = new erLhcoreClassModelmsg();
        $msg->msg = !empty($paramsURL) ? implode(' ',$paramsURL) : erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand','We will show a form in a moment!');
        $msg->meta_msg = '{"content":{"execute_js":{"text":"","ext_execute":"modal_ext","ext_args":"{\"delay\":3,\"url\":\"' . $URL .'\"}"}}}';
        $msg->chat_id = $params['chat']->id;
        $msg->user_id = $params['user']->id;
        $msg->time = time();
        $msg->name_support = $params['user']->name_support;
        $msg->saveThis();

        // Update last user msg time so auto responder work's correctly
        $params['chat']->last_op_msg_time = $params['chat']->last_user_msg_time = time();
        $params['chat']->last_msg_id = $msg->id;

        // All ok, we can make changes
        $params['chat']->updateThis(array('update' => array('last_msg_id', 'last_op_msg_time', 'status_sub', 'last_user_msg_time')));

        return array(
            'status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW,
            'processed' => true,
            'raw_message' => '!modal',
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Modal activated!') . ' ' . $URL
        );
    }

    /**
     * Processes command
     */
    public static function processCommand($params)
    {
        $commandData = self::extractCommand($params['msg']);
        
        if (key_exists($commandData['command'], self::$supportedCommands)) {
            $params['argument'] = $commandData['argument'];
            return call_user_func_array(self::$supportedCommands[$commandData['command']], array(
                $params
            ));
        } else { // Perhaps some extension has implemented this command?

            $command = erLhcoreClassModelGenericBotCommand::findOne(array('customfilter' => array('(dep_id = 0 OR dep_id = ' . (int)$params['chat']->dep_id . ')'),'filter' => array('command' => ltrim($commandData['command'],'!'))));

            if ($command instanceof erLhcoreClassModelGenericBotCommand) {

                if ($command->sub_command != '') {
                    $commandData['argument'] = trim($command->sub_command . ' '.$commandData['argument']);
                }

                $trigger = $command->trigger;

                $ignore = false;
                $update_status = false;

                $responseData = [];

                if ($trigger instanceof erLhcoreClassModelGenericBotTrigger) {

                    $ignore = strpos($commandData['argument'],'--silent') !== false;
                    $update_status = strpos($commandData['argument'],'--update_status') !== false;

                    if ($ignore == true) {
                        $commandData['argument'] = trim(str_replace('--silent','',$commandData['argument']));
                    }

                    if ($update_status == true) {
                        $commandData['argument'] = trim(str_replace('--update_status','',$commandData['argument']));
                    }

                    $argumentsTrigger = array(
                        'msg' => $commandData['argument'], 
                        'caller_user_id' => $params['user']->id,
                        'caller_user_class' => get_class($params['user']));

                    foreach (explode('--arg',$commandData['argument']) as $indexArgument => $argumentValue) {
                        $argumentsTrigger['replace_array']['{arg_'.($indexArgument + 1).'}'] = trim($argumentValue); // For direct replacement
                        $argumentsTrigger['arg_'.($indexArgument + 1)] = trim($argumentValue);                       // For {args.arg_3} to work
                    }

                    $responseData['last_message'] = erLhcoreClassGenericBotWorkflow::processTrigger($params['chat'], $trigger, false, array('args' => $argumentsTrigger));

                    $response = '"' . $trigger->name . '"' . ' ' . erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'was executed');

                } else {
                    $response = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Assigned trigger could not be found');
                }

                $responseData = array_merge(array(
                    'ignore' => $ignore,
                    'processed' => true,
                    'process_status' => '',
                    'raw_message' => $commandData['command'] . ' || ' . $response
                ),$responseData);

                if ($update_status == true) {
                    $responseData['custom_args']['update_status'] = true;
                }

                if ($command->info_msg != '') {
                    $responseData['info'] = $command->info_msg;
                }

                return $responseData;

            } else {
                $commandResponse = erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.customcommand', array('command' => $commandData['command'], 'argument' => $commandData['argument'], 'params' => $params));

                if (isset($commandResponse['processed']) && $commandResponse['processed'] == true) {
                    return $commandResponse;
                }
            }
        }
        
        return array(
            'processed' => true,
            'ignore' => true,
            'process_status' => '',
            'info' => 'Unknown command! [' . $commandData['command'] .']',
        );
    }

    /**
     * Updates chat nick.
     *
     * @param array $params            
     *
     * @return boolean
     */
    public static function setName($params)
    {
        
        // Update object attribute
        $params['chat']->nick = $params['argument'];
        
        // Update only
        $db = ezcDbInstance::get();
        $stmt = $db->prepare('UPDATE lh_chat SET nick = :nick WHERE id = :id');
        $stmt->bindValue(':id', $params['chat']->id, PDO::PARAM_INT);
        $stmt->bindValue(':nick', $params['chat']->nick, PDO::PARAM_STR);
        $stmt->execute();
        
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Nick changed!')
        );
    }
    
    /**
     * Just adds message from operator
     *
     * @param array $params
     *
     * @return boolean
     */
    public static function notice($params)
    {
    	return array(
    			'processed' => true,
    			'process_status' => '',
    			'raw_message' => $params['argument']
    	);
    }

    public static function disableFiles($params)
    {
        $chatVariables = $params['chat']->chat_variables_array;

        if (isset($chatVariables['lhc_fu'])) {
            unset($chatVariables['lhc_fu']);
            $params['chat']->chat_variables = json_encode($chatVariables);
            $params['chat']->chat_variables_array = $chatVariables;
        }

        if (!isset($params['argument']) || $params['argument'] != 'no') {
            $msg = new erLhcoreClassModelmsg();
            $msg->msg = (isset($params['argument']) && $params['argument'] != '') ? $params['argument'] : erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Files upload was disabled!');
            $msg->chat_id = $params['chat']->id;
            $msg->user_id = $params['user']->id;
            $msg->time = time();
            $msg->name_support = $params['user']->name_support;

            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $params['chat']));

            $msg->saveThis();
        }

        // Schedule UI Refresh
        $params['chat']->operation .= "lhc_ui_refresh:0\n";

        // Store permanently
        $params['chat']->updateThis(array('update' => array('chat_variables', 'operation')));

        return array(
            'processed' => true,
            'process_status' => '',
            'raw_message' => '!stopfiles'
        );
    }

    public static function enableFiles($params)
    {
        $chatVariables = $params['chat']->chat_variables_array;
        $chatVariables['lhc_fu'] = 1;

        $params['chat']->chat_variables = json_encode($chatVariables);
        $params['chat']->chat_variables_array = $chatVariables;

        if (!isset($params['argument']) || $params['argument'] != 'no') {
            $msg = new erLhcoreClassModelmsg();
            $msg->msg = (isset($params['argument']) && $params['argument'] != '') ? $params['argument'] : erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand','I have enabled files upload for you. [fupload]Upload a file[/fupload].');
            $msg->chat_id = $params['chat']->id;
            $msg->user_id = $params['user']->id;
            $msg->time = time();
            $msg->name_support = $params['user']->name_support;

            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved',array('msg' => & $msg, 'chat' => & $params['chat']));

            $msg->saveThis();
        }

        // Schedule UI Refresh
        $params['chat']->operation .= "lhc_ui_refresh:1\n";

        // Store permanently
        $params['chat']->updateThis(array('update' => array('chat_variables','operation')));

        return array(
            'processed' => true,
            'process_status' => '',
            'raw_message' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand','Files upload enabled.')
        );
    }

    /**
     * Just adds message from operator
     *
     * @param array $params
     *
     * @return boolean
     */
    public static function hold($params)
    {
        $params['chat']->status_sub = erLhcoreClassModelChat::STATUS_SUB_ON_HOLD;

        if ($params['argument'] != '') {
            $defaultHoldMessage = $params['argument'];
        } else if ($params['chat']->auto_responder !== false && $params['chat']->auto_responder->auto_responder !== false && $params['chat']->auto_responder->auto_responder->wait_timeout_hold != '') {
            $defaultHoldMessage = $params['chat']->auto_responder->auto_responder->wait_timeout_hold;
        } else {
            $defaultHoldMessage = '';
        }

        if ($defaultHoldMessage != '') {
            // Store as message to visitor
            $msg = new erLhcoreClassModelmsg();
            $msg->msg = $defaultHoldMessage;
            $msg->chat_id = $params['chat']->id;
            $msg->user_id = $params['user']->id;
            $msg->time = time();
            $msg->name_support = $params['user']->name_support;

            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved',array('msg' => & $msg, 'chat' => & $params['chat']));

            $msg->saveThis();
        }

        // Reset auto responder on hold command
        if ($params['chat']->auto_responder !== false) {
            $params['chat']->auto_responder->active_send_status = 0;
            $params['chat']->auto_responder->saveThis();
        }

        // Update last user msg time so auto responder work's correctly
        $params['chat']->last_op_msg_time = $params['chat']->last_user_msg_time = time();

        // All ok, we can make changes
        $params['chat']->updateThis(array('update' => array('last_op_msg_time','status_sub','last_user_msg_time')));


        return array(
            'custom_args' => array(
              'hold_added' => true
            ),
            'processed' => true,
            'raw_message' => '!hold',
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat status changed on-hold!')
        );
    }

    /**
     * @desc Transfers chat to bot
     *
     * @param $params
     * @return array
     * @throws ezcPersistentDefinitionNotFoundException
     * @throws ezcPersistentObjectNotPersistentException
     * @throws ezcPersistentQueryException
     */
    public static function goToBot($params) {

        $params['chat']->status = erLhcoreClassModelChat::STATUS_BOT_CHAT;
        $params['chat']->last_op_msg_time = $params['chat']->last_user_msg_time = time();
        $params['chat']->updateThis(array('update' => array('status','last_op_msg_time','last_user_msg_time')));

        return array(
            'processed' => true,
            'raw_message' => '!gotobot',
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was transferred to bot!')
        );
    }

    /**
     * Updates chat email.
     *
     * @param array $params            
     *
     * @return boolean
     */
    public static function setEmail($params)
    {
        
        // Update object attribute
        $params['chat']->email = $params['argument'];
        
        if (! isset($params['no_ui_update'])) {
            // Schedule interface update
            $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";
        }
        
        // Update only
        $db = ezcDbInstance::get();
        $stmt = $db->prepare('UPDATE lh_chat SET email = :email, operation_admin = :operation_admin WHERE id = :id');
        $stmt->bindValue(':id', $params['chat']->id, PDO::PARAM_INT);
        $stmt->bindValue(':email', $params['chat']->email, PDO::PARAM_STR);
        $stmt->bindValue(':operation_admin', $params['chat']->operation_admin, PDO::PARAM_STR);
        $stmt->execute();
        
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'E-mail changed!')
        );
    }

    /**
     * Option to transfer user to another user via command line directly
     * */
    public static function transferforce($params)
    {
        $user = erLhcoreClassModelUser::findOne(array('filter' => array('username' => $params['argument'])));
        
        // Try find user by e-mail
        if (!($user instanceof erLhcoreClassModelUser)) {
            $user = erLhcoreClassModelUser::findOne(array('filter' => array('email' => $params['argument'])));
        }
        
        if ($user instanceof erLhcoreClassModelUser) {
            
            $permissionsArray = erLhcoreClassRole::accessArrayByUserID($params['user']->id);
            
            if ($params['chat']->user_id == $params['user']->id || erLhcoreClassRole::canUseByModuleAndFunction($permissionsArray, 'lhchat', 'allowtransferdirectly')) {
                                                
                $params['chat']->user_id = $user->id;
                $params['chat']->status_sub = erLhcoreClassModelChat::STATUS_SUB_OWNER_CHANGED;
                $params['chat']->user_typing_txt = htmlspecialchars_decode(erTranslationClassLhTranslation::getInstance()->getTranslation('chat/accepttrasnfer','Chat has been transfered to'),ENT_QUOTES) . ' - ' . (string)$user;
                $params['chat']->user_typing  = time();
                     
                // Change department if user cannot read current department, so chat appears in right menu
                $filter = erLhcoreClassUserDep::parseUserDepartmetnsForFilter($user->id, $user->cache_version);
                if ($filter !== true && !in_array($params['chat']->dep_id, $filter)) {
                    $dep_id = erLhcoreClassUserDep::getDefaultUserDepartment($user->id);                    
                    if ($dep_id > 0) {
                        $params['chat']->dep_id = $dep_id;                       
                    }
                }
                
                $params['chat']->status_sub_sub = erLhcoreClassModelChat::STATUS_SUB_SUB_TRANSFERED;
    
                // Update UI
                if (! isset($params['no_ui_update'])) {
                    $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";
                }
    
                // All ok, we can make changes
                erLhcoreClassChat::getSession()->update($params['chat']);
                
                // Chat was transfered callback
                erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.chat_transfered_force', array('chat' => & $params['chat']));
                
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/accepttrasnfer','Chat has been transfered to') . ' - ' . (string)$user
                );
            
            } else {
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'You do not have permission to transfer chat directly!')
                );
            }            
        } else {
            return array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'User could not be found!')
            );
        }
    }
    
    /**
     * Updates chat phone
     *
     * @param array $params            
     *
     * @return boolean
     */
    public static function setPhone($params)
    {
        
        // Update object attribute
        $params['chat']->phone = $params['argument'];
        
        if (! isset($params['no_ui_update'])) {
            // Schedule interface update
            $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";
        }

        $params['chat']->updateThis(array('update' => array('phone','operation_admin')));

        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Phone changed!')
        );
    }

    /**
     * Redirects user to specified URL
     *
     * @param array $params            
     *
     * @return boolean
     */
    public static function redirectTo($params)
    {
        
        // Update object attribute
        $params['chat']->operation .= 'lhc_chat_redirect:' . str_replace(':', '__SPLIT__', $params['argument']) . "\n";
        $params['chat']->updateThis(array('update' => array('operation')));

        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'User was redirected!')
        );
    }

    public static function startTranslation($params)
    {
        // Schedule interface update
        $params['chat']->operation_admin .= "lhc.methodCall('lhc.translation','startTranslation',{'btn':$('#start-trans-btn-{$params['chat']->id}'),'chat_id':'{$params['chat']->id}'});";
        
        $params['chat']->updateThis(array('update' => array('operation_admin')));
        
        return array(
            'processed' => true,
            'process_status' => ''
        );
    }

    public static function takeScreenshot($params)
    {
        // Update object attribute
        $params['chat']->operation .= "lhc_screenshot\n";
        
        // Update only
        $params['chat']->updateThis(array('update' => array('operation')));

        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Screenshot was scheduled!')
        );
    }

    public static function contactForm($params)
    {
        if (isset($params['no_ui_update'])) {
            erLhcoreClassChatHelper::redirectToContactForm($params);
        } else {
            
            // Schedule interface update
            $params['chat']->operation_admin .= "lhinst.redirectContact('{$params['chat']->id}');";
            
            $params['chat']->updateThis(array('update' => array('operation_admin')));
        }
        
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'User was redirected to contact form!')
        );
    }

    public static function blockUser($params)
    {
        if (erLhcoreClassUser::instance()->hasAccessTo('lhchat','allowblockusers')) {
            erLhcoreClassModelChatBlockedUser::blockChat(array('user' => $params['user'], 'chat' => $params['chat']));
            return array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'User was blocked!')
            );
        } else {
            return array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'You do not have permission to block user!')
            );
        }

    }
    
    public static function info($params)
    {
        $infoArray = array();
        $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Department').' - '.(string)$params['chat']->department;
        
        if ($params['chat']->referrer != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Started chat from').' - '.(string)$params['chat']->referrer;
        }
        
        if ($params['chat']->session_referrer != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Refered from').' - '.(string)$params['chat']->session_referrer;
        }
        
        if ($params['chat']->online_user !== false && $params['chat']->online_user->current_page != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Current page').' - '.(string)$params['chat']->online_user->current_page;
        }
        
        if ($params['chat']->email != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'E-mail').' - '.(string)$params['chat']->email;
        }
        
        if ($params['chat']->phone != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Phone').' - '.(string)$params['chat']->phone;
        }
        
        if ($params['chat']->country_name != '') {
            $infoArray[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Country').' - '.(string)$params['chat']->country_name;
        }
        
        return array(
            'processed' => true,
            'process_status' => '',
            'ignore' => true,
            'info' => implode("\n", array_filter($infoArray))
        );
    }
    
    public static function help()
    {                    
        return array(
            'processed' => true,
            'process_status' => '',
            'ignore' => true,
            'info' => implode("\n", array_keys(self::$supportedCommands))
        );
    }

    public static function closeChat($params)
    {
        if (isset($params['no_ui_update'])) {
            
            $permissionsArray = erLhcoreClassRole::accessArrayByUserID($params['user']->id);
            
            if ($params['chat']->user_id == $params['user']->id || erLhcoreClassRole::canUseByModuleAndFunction($permissionsArray, 'lhchat', 'allowcloseremote')) {
                erLhcoreClassChatHelper::closeChat($params);
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was closed!')
                );
            } else {
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'You do not have permission to close a chat!')
                );
            }
        } else {
            // Schedule interface update
            $params['chat']->operation_admin .= "lhinst.closeActiveChatDialog('{$params['chat']->id}',$('#tabs'),true);";

            $params['chat']->updateThis(array('update' => array('operation_admin')));

            $responseData = array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was closed!')
            );

            $ignore = strpos($params['argument'],'--silent') !== false;

            if ($ignore == true) {
                $responseData['ignore'] = true;
            }

            return $responseData;
        }
    }
    
    /**
     * 
     * @param array $params
     * 
     * @return multitype:boolean string
     */
    public static function closeChatDialog($params)
    {
        // Schedule interface update
        $params['chat']->operation_admin .= "lhinst.removeDialogTab('{$params['chat']->id}',$('#tabs'),true);";
                
        $params['chat']->updateThis(array('update' => array('operation_admin')));

        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was closed!')
        );        
    }
    
    /**
     * Deletes a chat
     */
    public static function deleteChat($params)
    {
        if (isset($params['no_ui_update'])) {
            
            $permissionsArray = erLhcoreClassRole::accessArrayByUserID($params['user']->id);
            
            if (erLhcoreClassRole::canUseByModuleAndFunction($permissionsArray, 'lhchat', 'deleteglobalchat') || (erLhcoreClassRole::canUseByModuleAndFunction($permissionsArray, 'lhchat', 'deletechat') && $params['chat']->user_id == $params['user']->id)) {
                erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.delete', array(
                    'chat' => & $params['chat'],
                    'user' => $params['user']
                ));
                $params['chat']->removeThis();
                
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was deleted!')
                );
            } else {
                return array(
                    'processed' => true,
                    'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'You do not have permission to delete a chat!')
                );
            }
        } else {
            // Schedule interface update
            $params['chat']->operation_admin .= "lhinst.deleteChat('{$params['chat']->id}',$('#tabs'),true);";
            
            $params['chat']->updateThis(array('update' => array('operation_admin')));
            
            return array(
                'processed' => true,
                'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat was deleted!')
            );
        }
    }

    /**
     * Changes stat status to pending
     */
    public static function pendingChat($params)
    {
        erLhcoreClassChatHelper::changeStatus(array(
            'user' => $params['user'],
            'chat' => & $params['chat'],
            'status' => erLhcoreClassModelChat::STATUS_PENDING_CHAT,
            'allow_close_remote' => erLhcoreClassRole::canUseByModuleAndFunction(erLhcoreClassRole::accessArrayByUserID($params['user']->id), 'lhchat', 'allowcloseremote')
        ));
        
        if (! isset($params['no_ui_update'])) {
            $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";
            $params['chat']->updateThis(array('update' => array('operation_admin')));
        }
        
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat status was changed to pending!')
        );
    }
    
    public static function activeChat($params)
    {
        erLhcoreClassChatHelper::changeStatus(array(
            'user' => $params['user'],
            'chat' => & $params['chat'],
            'status' => erLhcoreClassModelChat::STATUS_ACTIVE_CHAT,
            'allow_close_remote' => erLhcoreClassRole::canUseByModuleAndFunction(erLhcoreClassRole::accessArrayByUserID($params['user']->id), 'lhchat', 'allowcloseremote')
        ));
        
        if (! isset($params['no_ui_update'])) {
            $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";

            $params['chat']->updateThis(array('update' => array('operation_admin')));
        }
        
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Chat status was changed to active!')
        );
    }
    
    /**
     * Add remarks to chat
     * */
    public static function addRemark($params)
    {     
        $params['chat']->remarks = $params['argument'];
        
        if (! isset($params['no_ui_update'])) {
            $params['chat']->operation_admin .= "lhinst.updateVoteStatus(" . $params['chat']->id . ");";
        }

        $params['chat']->updateThis(array('update' => array('operation_admin','remarks')));
              
        return array(
            'processed' => true,
            'process_status' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chatcommand', 'Remarks were saved!')
        );
    }
}

?>