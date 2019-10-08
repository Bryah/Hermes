<?php

namespace Bryah\Hermes\Models;


trait UserTrait {

    public function foo(){
        return "bar";
    }

    public function conversations($messageType=null,$start=0,$limit=20)
    {
        if (!$messageType) {
            return $this->belongsToMany(EloquentBase::modelPath('Conversation'),
                                        EloquentBase::tableName('conversation_user'))
                                        ->withTimestamps()->orderBy('updated_at', 'desc');
        } else {
            $conversationUsersModel = EloquentBase::modelPath('Conversation');
            $messagesModel = EloquentBase::modelPath('Message');

            $usersConversationIds = \App\ConversationUser::where('user_id',$this->id)
                                                            ->get()
                                                            ->lists('conversation_id');

            switch ($messageType) {
                case "received":
                    foreach($usersConversationIds as $cId) {
                        $messages = $messagesModel::whereIn('conversation_id',$usersConversationIds)
                                                    ->where('user_id','!=',$this->id)
                                                    ->orderBy('updated_at', 'desc')
                                                    ->get();
                    }
                    break;
                case "sent":
                    foreach($usersConversationIds as $cId) {
                        $messages = $messagesModel::whereIn('conversation_id',$usersConversationIds)
                                                    ->where('user_id',$this->id)
                                                    ->orderBy('updated_at', 'desc')
                                                    ->get();
                    }
                    break;
                case "archived":
                    foreach($usersConversationIds as $cId) {
                        $allMessages = $messagesModel::whereIn('conversation_id',$usersConversationIds)
                                                    ->get();
                        $messageIds = [];
                        foreach($allMessages as $aM) {
                            if ($aM->messageState($this->id) == 3) {
                                $messageIds[] = $aM->id;
                            }
                        }
                        $messages = $messagesModel::whereIn('id',$messageIds)
                                                    ->orderBy('updated_at', 'desc')
                                                    ->get();
                    }
                    break;
            }

            $filteredConversationIds = [];
            foreach($messages as $m) {
                $filteredConversationIds[] = $m->conversation_id;
            }

            return $this->belongsToMany(EloquentBase::modelPath('Conversation'),
                                        EloquentBase::tableName('conversation_user'))
                                        ->whereIn('conversation_id',$filteredConversationIds)
                                        ->withTimestamps()
                                        ->orderBy('updated_at', 'desc')
                                        ->skip($start)
                                        ->take($limit);
        }

    }

	public function messageStates()
    {
        return $this->hasMany(EloquentBase::modelPath('MessageState'))->orderBy('updated_at', 'desc');
    }

    public function unreadMessageStates(){
    	return $this->messageStates()->where('state', '=', 0)->orderBy('updated_at', 'desc')->get();
    }

    public function unreadMessagesCount(){
        return count($this->unreadMessageStates());
    }

    public function hasUnreadMessages(){
        return $this->unreadMessagesCount() > 0;
    }

    public function unreadConversations(){
    	//TODO: Do this using Eloquent
    	$unreadConversations = array();
    	foreach($this->unreadMessages() as $unreadMessage){
    		$unreadConversation = $unreadMessage->conversation;
    		$conversationId = $unreadConversation->id;

    		if(isset($unreadConversations[$conversationId])) continue;

    		$unreadConversations[$conversationId] = $unreadConversation;
    	}
      	return $unreadConversations;
    }

    public function unreadMessages(){
    	return $this->findMessages('unread');
    }

    public function findMessages($state = false){
    	$user_id = $this->id;

    	$unreadMessages = Message::whereHas('messageStates', function($q) use( &$user_id, &$state)
    	{
    		$q->where('user_id', '=', $user_id);

    		if($state)
    		$q->where('state', '=', MessageState::indexOf($state));

    	})->with('conversation')->get();

    	return $unreadMessages;
    }





}
