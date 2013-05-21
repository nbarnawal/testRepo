<?php

if( ! $my->id ){
	$return['result'] = 'error';
	$return['error'] = 'not login';
	
	echo json_encode( $return );
	exit();
}
/**
 * @TODO test
 */
checkiflogged();
loadClass( array( 'activitiesv2' , 'activity_exceptions' , 
	'activity_notification2', 'gcal',
	'time' , 'cache' , 'date', 
	'when' , 'when_iterator' 
	) );

function saveactivity(){
	global $database, $my, $broker , $database,$mosConfig_absolute_path , $mosConfig_live_site, $permissions ;
	
	
	require_once($mosConfig_absolute_path.'/components/com_masterdigm/classes/subclasses/class.gcal.php');
			
	
	$activity = new activitiesv2( $database );	
	
	$activity_id = mosGetParam( $_REQUEST , 'activity_id' , '' );
	
	/*$activity->weekday = $_POST['summary_value'];*/
	
	// check if activity is recurring
	$series_id = '';
	
	if( substr( $activity_id , 0, 6 ) == 'series' ){
		$aid_array = explode( '_' , $activity_id );		
		$series_id = $aid_array[1];
		$series_activity = activitiesv2::getActivityBySeriesId( $series_id );
		
		$activity->load(  $series_activity->id );  
	}else{
		$activity->id 	= $activity_id;
		$activity->load( $activity_id );	
		
	}
		//echo "<pre>";print_r($activity);echo "</pre>";exit;
	$activity->bind( $_POST );
	

	$act_subject  = mosGetParam( $_REQUEST , 'subject' , '' );
	$activity->subject 		= htmlentities($act_subject);
	$activity->start_date 	= mosGetParam( $_REQUEST , 'start_date' , '' );
	
	//start date and end date will be the same for simple activities
	 
	$activity->end_date = $activity->start_date;
	
	$activity->start_time = ptime::convertIntToHrMin( mosGetParam( $_REQUEST , 'start_time' , 0 ) );	
	$activity->end_time = ptime::convertIntToHrMin( mosGetParam( $_REQUEST , 'end_time' , 0 ) );
	
	if( strlen( trim( $activity->subject ) ) < 3  ){
		$return['result'] = 'error';
		$return['error'] = 'Invalid activity details';		
		echo json_encode( $return );
		exit();
	}
	
	if( ! $activity->start_date || ! $activity->start_time ){
		$return['result'] = 'error';
		$return['error'] = 'Invalid start date';		
		echo json_encode( $return );
		exit(); 
	}
		
	$activity->userid = $my->id;
	$activity->createdby = $my->id;
	
	
	if( $activity->repeats != 'none' &&  $activity->repeats ){
		
		if( ! $activity_id ){
			$activity->series_id = $activity->getNewSeriesId();
		}	
			
		switch( $activity->repeats ){			
			case 'weekly':						
				$weekdays  = mosGetParam( $_REQUEST , 'repeat_weekdays' , array() );		
				
				if( ! count( $weekdays ) ){
					$return['result'] = 'error';
					$return['error'] = 'Please select a weekday';
					echo json_encode( $return );
					exit();
				}
				
				$weekdays_array = array();
					
				foreach( $weekdays as $w ){
					$weekdays_array[] = $w;	
				}
		
				$activity->repeat_weekdays = implode( '' , $weekdays_array );
				$activity->repeat_until = mosGetParam( $_REQUEST , 'repeat_until_week' , '' );
				
							 
			break;
			case 'monthly':
				
				$activity->repeats_every	= mosGetParam( $_REQUEST , 'repeat_by_month' , 'month day' );
				$activity->repeat_until = mosGetParam( $_REQUEST , 'repeat_until_month' , '' );	
				$activity->monthly_repeat_weekday = mosGetParam($_REQUEST, 'summary_value' , '');
			break;	
			case 'yearly':				
				$activity->repeat_until = mosGetParam( $_REQUEST , 'repeat_until_year' , '' );		
				$activity->yearly_month_value = mosGetParam( $_REQUEST , 'month_value' , '' );
				$activity->yearly_day_value = mosGetParam( $_REQUEST , 'day_value' , '' );				
			break;
		}
		
		if( ! $activity->repeat_until  ){
			$return['result'] = 'error';
			$return['error'] = 'Please indicate repeat end date';
			echo json_encode( $return );
			exit();
		}
			
	}
			
	// new activities 
	if( $activity->id ){
		//echo "<pre>";print_r($activity);die;
		// modify one of the existing series event		
			$subject = $activity->subject;
				
			 $series_coverage = strtolower( mosGetParam( $_REQUEST , 'series_coverage' , '' ) );
			if( ! $series_coverage && $activity->repeats != 'none'){
					$return['result'] = 'error';
					$return['error'] = 'Invalid series coverage';
					echo json_encode( $return );
					exit();
			}
			
			if( $series_coverage == 'only this' ){
				//echo 'samiskha';exit;
				$activity->id = '0';
				$activity->repeats	= 'none';
				$activity->repeat_until 	= '';
				$activity->repeats_every	= '';		
				$activity->repeat_weekdays 	= '';				
				$activity->series_id 		= '';
				$activity->subject = htmlentities($subject);
				
				// create new activity exception
				
				$exception = new activity_exceptions($database);
				$exception->series_id = $series_activity->series_id;
				$exception->recurring_date = '';
			}										
			
			switch( $activity->repeats ){
				case 'weekly':
				break;	
			}
			
				
	}else{	
		
		$activity->date_added = date('Y-m-d H:i:s');	
					
	}
	
	
	if( $activity->store() ){
	//echo "<pre>";print_r($activity);echo "</pre>";die;
		$return['result'] 		= 'success';


    	
    	$notify_in = mosGetParam( $_REQUEST , 'notify_in' , array() );
    	$notify_period = mosGetParam( $_REQUEST , 'notify_period' , array() );
    	
    	foreach( $notify_in as $k => $v ){
    		switch( $notify_period[$k] ){    			
    			case 'hours':
    				$minutes = $notify_in[$k] * 60;
    			break;	
    			case 'days':
    				$minutes = $notify_in[$k] * 1440;
    			break;
    			default:
    			case 'minutes':
    				$minutes = $notify_in[$k]; 
    			break;
    		}
    		
    		$notification =  activity_notification2::addNotificationByActivityId( $minutes , $activity->id , $k , $v , $notify_period[$k] );
    		activity_notification2::setNotification( $activity );    		 
    	}
		
	}
		
		else{
		$return['result'] = 'error';
		$return['error'] = $activity->getError();
	}
	

		loadClass('gcal');
		if($permissions->google_username && $permissions->google_password){
			
			try{
				$gcal	=	new gcal($permissions->google_username,$permissions->google_password);
			}catch(Exception $e){
				/*$gcal_failed  = true;*/
				$error_msg	=	' Failed to create google calendar object! ';
				logError( $error_msg.' u='.$permissions->google_username, $permissions->google_password );	
			}
			
			if(!$gcal->client){				
				$msg_ext	=	' Your Google account is invalid. Please check Google username and password on My Account Settings';								
			}
			else {

					/*
					$database->setQuery('DELETE FROM jos_mdigm_activities WHERE subject = "reccr-25"');        	
					$database->query(); 
		 			*/
			
					if( $activity->gcalid ){
					
					
							$gcalId = $activity->gcalid;
								
							if( strpos($gcalId,"_") && $series_coverage == 'all') {	 // recurring event  
								$calenderEventId = substr($gcalId,0,strpos($gcalId,"_"));
								
								$database->setQuery('SELECT gcalid FROM jos_mdigm_activities WHERE gcalid LIKE "'.$calenderEventId.'%" AND 
								start_date >= "'.$activity->start_date.'"');        	
							
								$vars	=	$database->loadResultArray();
								foreach( $vars as $var ){
									
									$gcal->delete($var); 
									
									$database->setQuery('DELETE FROM jos_mdigm_activities WHERE gcalid = "'.$var.'"');        	
									$database->query(); 
								
								}	
								$newEvent =	 $gcal->createActivity( $activity ,$notify_when=false,$event =false);		
								
							}
							else
							{
								
								//echo 'DELETE FROM jos_mdigm_activities WHERE gcalid = "'.$activity->gcalid.'"';
								
								$database->setQuery('DELETE FROM jos_mdigm_activities WHERE gcalid = "'.$activity->gcalid.'"');        	
								$database->query(); 

								$gcal->delete($activity->gcalid); 
				
								$newEvent =	 $gcal->createActivity( $activity ,$notify_when=false,$event =false);		
								//activitiesv2::saveToGCal($activity);
				 
							}
		
							//echo "</pre>";
					
							
				}else {
						
					$newEvent =	 $gcal->createActivity( $activity ,$notify_when=false,$event =false);		
									
					if($newEvent){
						
						$activity->gcalid	=	$newEvent;
						if( ! $activity->store() ){
							logError('GCal id failed to save on saveactivity()!');	
						}
													
					}
					
						
				}

			}				

		}
		else{			
			$msg_ext	=	'Your Google account is invalid. Please check Google username and password on My Account Settings';
		}
		
	echo json_encode( $return );
	exit();	
}


function deleteactivity($event_id = NULL){
		global $database, $my, $broker , $database,$mosConfig_absolute_path , $mosConfig_live_site, $permissions ;
		
		
		
		if(is_null($event_id))
		{
			$event_id = mosGetParam( $_REQUEST , 'aid' , '' );
		}
		$delete_all = mosGetParam( $_REQUEST , 'delete_all' , 0 );
		
		$activity = new activitiesv2( $database );
		$activity->load( $event_id );
			
		loadClass( 'gcal' );	
		$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
				
		if( $delete_all ==1)
		{
		
			$gcalId = $activity->gcalid;
								
			if( strpos($gcalId,"_")) {	 // recurring event  
				
				$calenderEventId = substr($gcalId,0,strpos($gcalId,"_"));
				
				$database->setQuery('SELECT gcalid FROM jos_mdigm_activities WHERE gcalid LIKE "'.$calenderEventId.'%"');        	
			
				$vars	=	$database->loadResultArray();
				foreach( $vars as $var ){
					$database->setQuery('DELETE FROM jos_mdigm_activities WHERE gcalid = "'.$var.'"');        	
					$database->query(); 
					$gcal->delete($var); 
				}	
			
			}
		
		}
		else 
		{
				$database->setQuery('DELETE FROM jos_mdigm_activities WHERE gcalid = "'.$activity->gcalid.'"');        	
				$database->query(); 
				$gcal->delete($activity->gcalid); 
				
		}	
			
		$return[ 'result' ] 	= 'success';
		echo json_encode( $return );
		exit();
}

	
	//
	
function getactivity(){
	global $database, $my, $broker , $database,$mosConfig_absolute_path , $mosConfig_live_site, $permissions ;
	
	$aid = mosGetParam( $_REQUEST , 'aid' , '' );	 
	$start_date = mosGetParam( $_REQUEST , 'start_date' , '' );
	
	$sd_array = explode( 'GMT' , $start_date );
	$activity = activitiesv2::getActivityByEventId( $aid );
			
	$return = array();
	
	if( $activity->userid != $my->id &&  $activity->assigned_to != $my->id ){
		
		$return['result'] = 'error';
		$return['error']  = 'Activity access denied';
		$return['uid']  = $activity->userid;
		$return['aid']  = $aid;		
		
		echo json_encode($return);
		exit();
	}
	
	$options['dir']				=	'/schema';
	$options['instance']		=	'schema';
				
	$cache  	= 	cache::instance( $options );
			 
	$load_file	=   'mdigm_activities';
	$vars		=	$cache->load( $load_file );

	$return['result'] 	= 'success';
	
	foreach( $vars as $var ){
		
		if( $var == 'start_time' || $var == 'end_time' ){
			
			$st 	= new ptime( NULL , $activity->$var );
			$mod 	= $st->daymin % 15;
			$daymin = $st->daymin - $mod; 
			$return[$var] 	= $daymin;
				
		}else{							
			$return[ $var ] = stripslashes( $activity->$var );	
		}	
					 			   		  
	} 	
	
	//$s_time = $activity->start_date.' '.$activity->start_time;
	$s_time =	$sd_array[0];
	$return[ 'formatted_date' ] = date( 'D M d ' , strtotime( $s_time ) ).', '.ptime::convertToAMPM( $activity->start_time ).'-'.ptime::convertToAMPM( $activity->end_time );
	$return[ 'selected_date'] = $activity->start_date;
	if( substr( $aid , 0, 6 ) == 'series' ){
		$aid_array = explode( '_' , $aid );	
		
		$return['selected_date'] = $aid_array[2];
	}	
	
	$notification 	= 	activity_notification2::getNotificationByActivityId( $activity->id );
	
	$return['notify_before_minutes'] 	= 	$notification->notify_before_minutes;
	$return['notification_unit'] 		=  	$notification->notification_unit;
	$return['notification_period'] 		=  	$notification->notification_period;

	//------------------------------------------
	
	$gcalId = $activity->gcalid;
	//$activity->gcalid	 9c8ob0sn1uhorcnn8vnrv6uggs_20130220T184500Z
	if( strpos($gcalId,"_")) {	 // recurring event  

		loadClass( 'gcal' );	
		$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );

		$calenderEventId = substr($gcalId,0,strpos($gcalId,"_"));
		$eventFeed = $gcal->getEvent($calenderEventId);
			
		$dateTimeString = $eventFeed->recurrence->text;
		$feqAndUntilInfo = getFrequencyAndEndDate($dateTimeString);
		
		$return['repeats'] 		=  	strtolower($feqAndUntilInfo['frequency']);
		$return['repeat_until'] 		=  	$feqAndUntilInfo['until'];

	}
	//----------------------------------	

	
	/*
	echo '{"result":"success","id":"18987","subject":"recur15-25","activity":"","typeid":"0","status":"undone","date_scheduled":"0000-00-00 00:00:00","start_date":"2013-02-13","end_date":"2013-02-13","start_time":450,"end_time":510,"activity_where":"","date_added":"2013-02-15 06:57:57","priority":"low","userid":"68","createdby":"68","leadid":"0","contactid":"0","attachment":"","attachment_origname":"","email_alert":"0","assigned_to":"0","gcalid":"tuam39bn3kd6lklr8pnbr2q9ns_20130213T153000Z","repeats":"daily","repeat_until":"2013-02-17","repeats_every":"","repeat_weekdays":"","series_id":"tuam39bn3kd6lklr8pnbr2q9ns","notify":"","notify_lead":"0","notify_lead_subject":"","notify_lead_message":"","is_public":"0","gcal_last_update":"2013-02-15T06:57:57.000Z","monthly_repeat_weekday":"","yearly_month_value":"","yearly_day_value":"","formatted_date":"Wed Feb 13 , 07:30am-08:30am","selected_date":"2013-02-13","notify_before_minutes":null,"notification_unit":null,"notification_period":null}';	
	*/	
	echo json_encode($return);
	exit();
}

function changeactivity(){
	global $database , $permissions , $my ;	
	
	$st 	= mosGetParam( $_REQUEST , 'st' , '' ); 
	$et 	= mosGetParam( $_REQUEST , 'et' , '' );
	$aid 	= mosGetParam( $_REQUEST , 'aid' , '' );
			
	$s_array = explode( 'GMT' , $st );
	$e_array = explode( 'GMT' , $et );
	
	$stimestamp = strtotime( $s_array[0] );
	$etimestamp = strtotime( $e_array[0] );
	
	$database->setQuery('SHOW columns FROM jos_mdigm_activities');        	
    $vars	=	$database->loadResultArray();    
    	
	if( substr( $aid , 0 , 6 ) == 'series' ){
		$s = explode( '_' ,  $aid );
		
		$series_id 		= $s[1];
		$activity_day	= $s[2];
		
		$parent_activity = activitiesv2::getActivityBySeriesId( $series_id );							    	    	
    	$new_activity = new activitiesv2( $database );
		
    	
    	foreach( $vars as $var ){
    		$new_activity->$var = $parent_activity->$var;
    	}
		
		$old_activity = clone($new_activity);
	   	$new_activity->id = '0';
    	$new_activity->series_id = '';
    	$new_activity->repeats = 'none';
    	$new_activity->repeat_until = '';
    	$new_activity->repeats_every = '';
    	$new_activity->repeat_weekdays = '';
    	
    	$new_activity->start_date = date( 'Y-m-d' , $stimestamp );
		$new_activity->end_date = date( 'Y-m-d' , $etimestamp );
		$new_activity->start_time = date( 'H:i:00' , $stimestamp );
		$new_activity->end_time = date( 'H:i:00' , $etimestamp );
		$new_activity->date_added = date( 'Y-m-d ' );
		$new_activity->gcalid = $old_activity->gcalid;
		
		$return = array();									
		
		
		if($new_activity->store())
		{
			$newactivity_data = activitiesv2::saveRecurringToGCal($new_activity);
			$new_activity->gcalid= $newactivity_data;
			$new_activity->store();
			$return['series_id'] = $series_id;
			$return['result'] = 'success';
			
			foreach( $vars as $var ){
				$return[$var] = $new_activity->$var;				
			}
			
			/*$activity_exception = new activity_exceptions( $database );
			$activity_exception->series_id 		= $series_id;
			$activity_exception->recurring_date	= $activity_day;			
			$activity_exception->new_activity_id =	$new_activity->id; 
			$activity_exception->store();*/
			
			/*********************************** Addig Recurring Act to Gcal On edition ********/
			if($old_activity->repeat_until != "" || $old_activity->repeat_until != '0000-00-00')
			{
				activity_notification2::deleteByActivityId( $old_activity->id);
				$request_date = strtotime($new_activity->start_date);
				$activity_date = strtotime($old_activity->start_date);
				$acivity_repeat_date = strtotime($old_activity->repeat_until);
				$activity_end_date = strtotime($old_activity->end_date);
				$end_time = $old_activity->end_time;
				$repeats = $old_activity->repeats;
				$repeat_until = $old_activity->repeat_until;
				$subject = $old_activity->subject;
			
				if($request_date==$activity_date && $request_date<$acivity_repeat_date)
				{
					$str_arr = explode("-",$new_activity->start_date);
					$str_time = explode(":",$old_activity->start_time);
					$str_date_time = date('Y-m-d H:i:s',mktime($str_time[0],$str_time[1],$str_time[2],$str_arr [1],$str_arr[2],$str_arr[0]));
					
					$request_activity_start = date('Y-m-d H:i:s',strtotime($str_date_time. ' + 1 day'));
					 /*** One event with Event $request_activity_start_date ***/ 
					$recurring_activity = new activitiesv2( $database );
					$recurrence_start_date = explode(" ",$request_activity_start);
					$recurring_activity->subject = $subject;
					$recurring_activity->start_date = $recurrence_start_date['0'];
					$recurring_activity->start_time =  $recurrence_start_date['1'];
					$recurring_activity->end_date = $recurrence_start_date['0'];
					$recurring_activity->end_time =  $end_time;
					$recurring_activity->repeats = $repeats;
					$recurring_activity->repeat_until = $repeat_until;
					$recurring_activity->status = 'undone';
					$recurring_activity->userid = $my->id;
					$recurring_activity->createdby = $my->id;
					if( $old_activity->repeats != 'none' &&  $old_activity->repeats !='0000:00:00' ){
						$recurring_activity->series_id = $recurring_activity->getNewSeriesId();
						
					}
					$next_recurr_act = $recurring_activity->store();
					if($next_recurr_act)
					{
						$recurrent_event = activitiesV2::saveactivityToGCal( $recurring_activity);
						if($recurrent_event)
						{
							$recurring_activity->gcalid = $recurrent_event;
							$recurring_activity->store();
						}
					}
				}
				if($request_date>$activity_date && $request_date<$acivity_repeat_date)
				{
					$str_arr = explode("-",$new_activity->start_date);
					$str_time = explode(":",$old_activity->start_time);
					$str_date_time = date('Y-m-d H:i:s',mktime($str_time[0],$str_time[1],$str_time[2],$str_arr [1],$str_arr[2],$str_arr[0]));
					
					 $request_activity_start_date = date('Y-m-d H:i:s',strtotime($str_date_time. ' + 1 day'));
					 /*** One event with Event $request_activity_start_date ***/ 
					$one_recurr_activity = new activitiesv2( $database );
					$new_recur_start_date = explode(" ",$request_activity_start_date);
					$one_recurr_activity->subject = $subject;
					$one_recurr_activity->start_date = $new_recur_start_date['0'];
					$one_recurr_activity->start_time =  $new_recur_start_date['1'];
					$one_recurr_activity->end_date = $new_recur_start_date['0'];
					$one_recurr_activity->end_time =  $end_time;
					$one_recurr_activity->repeats = $repeats;
					$one_recurr_activity->repeat_until = $repeat_until;
					$one_recurr_activity->status = 'undone';
					$one_recurr_activity->userid = $my->id;
					$one_recurr_activity->createdby = $my->id;
					if( $old_activity->repeats != 'none' &&  $old_activity->repeats !='0000:00:00' ){
						$one_recurr_activity->series_id = $one_recurr_activity->getNewSeriesId();
						
					}
					$store_new_act = $one_recurr_activity->store();
					if($store_new_act)
					{
						$new_recurr_event = activitiesV2::saveactivityToGCal( $one_recurr_activity);
						//echo "<pre>";print_r($new_recurr_event);exit;
						if($new_recurr_event)
						{
							$one_recurr_activity->gcalid = $new_recurr_event;
							$one_recurr_activity->store();
						}
					}
					/**** Difference calculation**/
					 $datediff = $request_date - $activity_date;
					 $days = floor($datediff/(60*60*24));
					 if($days>0)
					{
						$strt_arr = explode("-",$new_activity->start_date);
						$strt_time = explode(":",$old_activity->start_time);
						$actstr_date_time = date('Y-m-d H:i:s',mktime($strt_time[0],$strt_time[1],$strt_time[2],$strt_arr[1],$strt_arr[2],$strt_arr[0]));
						$activity_str_date = date('Y-m-d H:i:s',strtotime($actstr_date_time. ' -'.$days.' day'));
						$other_recurr_activity = new activitiesv2( $database );
						$other_recur_start_date = explode(" ",$activity_str_date);
						$other_recurr_activity->subject = $subject;
						$other_recurr_activity->start_date = $other_recur_start_date['0'];
						$other_recurr_activity->start_time =  $other_recur_start_date['1'];
						$other_recurr_activity->end_date = $other_recur_start_date['0'];
						$other_recurr_activity->end_time =  $end_time;
						$other_recurr_activity->status = 'undone';
						$other_recurr_activity->repeats = $repeats;
						$other_recurr_activity->userid = $my->id;
						$other_recurr_activity->createdby = $my->id;
						 if( $old_activity->repeats != 'none' &&  $old_activity->repeats != '0000:00:00' ){
						
							$other_recurr_activity->series_id = $other_recurr_activity->getNewSeriesId();
						
						}
						 if($days>1)
						 {
							$other_recurr_activity->repeats = $repeats;
							$cal_day = floor($days - 1);
							$strt_arr = explode("-",$new_activity->start_date);
							$strt_time = explode(":",$old_activity->start_time);
							$actstr_date_time = date('Y-m-d H:i:s',mktime($strt_time[0],$strt_time[1],$strt_time[2],$strt_arr[1],$strt_arr[2],$strt_arr[0]));
							$activity_repeat_date = date('Y-m-d',strtotime($actstr_date_time. '-'.$cal_day.' day'));
							$one_recurr_activity->repeats = $repeats;
						 }
						 else
						 {
							$activity_repeat_date = '';
							$other_recurr_activity->repeats = 'none';
						 }
						 $other_recurr_activity->repeat_until = $activity_repeat_date;
						 $store_new_act = $other_recurr_activity->store();
						 if($store_new_act)
						 {
							$new_repeat_event = activitiesV2::saveactivityToGCal($other_recurr_activity);
							if($new_recurr_event)
							{
								$other_recurr_activity->gcalid = $new_repeat_event;
								$other_recurr_activity->store();
							}
						}
					}
					
								
				}
				if($request_date==$acivity_repeat_date)
				{
					$datediff = $request_date - $activity_date;
					$days = floor($datediff/(60*60*24));
					$act_strt_arr = explode("-",$new_activity->start_date);
					$act_strt_time = explode(":",$old_activity->start_time);
					$activitystr_date_time = date('Y-m-d H:i:s',mktime($act_strt_time[0],$act_strt_time[1],$act_strt_time[2],$act_strt_arr[1],$act_strt_arr[2],$act_strt_arr[0]));
					$activity_start_date = date('Y-m-d H:i:s',strtotime($activitystr_date_time. ' -'.$days.' day'));
					$recurrence_new_activity = new activitiesv2( $database );
					$recurring_start_date = explode(" ",$activity_start_date);
					$recurrence_new_activity->subject = $subject;
					$recurrence_new_activity->start_date = $recurring_start_date['0'];
					$recurrence_new_activity->start_time =  $recurring_start_date['1'];
					$recurrence_new_activity->end_date = $recurring_start_date['0'];
					$recurrence_new_activity->end_time =  $end_time;
					$recurrence_new_activity->status = 'undone';
					$recurrence_new_activity->repeats = $repeats;
					$recurrence_new_activity->userid = $my->id;
					$recurrence_new_activity->createdby = $my->id;
					 if( $old_activity->repeats != 'none' &&  $old_activity->repeats != '0000:00:00' ){
					
						$recurrence_new_activity->series_id = $recurrence_new_activity->getNewSeriesId();
					
					}
					$activity_repeat_date = date('Y-m-d',strtotime($activitystr_date_time. '-1 day'));
					$recurrence_new_activity->repeat_until = $activity_repeat_date;
					if($recurrence_new_activity->store())
					{
						$new_repeat_event = activitiesV2::saveactivityToGCal($recurrence_new_activity);
							if($new_recurr_event)
							{
								$recurrence_new_activity->gcalid = $new_repeat_event;
								$recurrence_new_activity->store();
							}
					}
					 
				}
							
		/****************************************/
			
		}
	}
	else{
			$return['result'] 	= 'error';
			$return['error'] 	= $other_recurr_activity->getError();
		}	
		
		echo json_encode( $return );
		exit();
    	    	    	
	}else{
		
		$activity = new activitiesv2( $database );
		$activity->load( $aid );
	
		
		$activity->start_date = date( 'Y-m-d' , $stimestamp );
		$activity->end_date = date( 'Y-m-d' , $etimestamp );
		$activity->start_time = date( 'H:i:00' , $stimestamp );
		$activity->end_time = date( 'H:i:00' , $etimestamp );
		
		$act = $activity->store();
		
		
		//echo "stored";
		if( $activity->store() ){
		
		foreach( $vars as $var ){
				$return[$var] = $activity->$var;				
			}
	
			$return['result'] = 'success'; 
			if( $activity->gcalid ){		
			activitiesv2::saveToGCal($activity);
				
			}
		}else{
			$return['result'] = 'error';
			$return['error'] = $activity->getError();
		}	
		
		
	echo json_encode( $return );
	exit();	
		
	}	
	
	
}

function hasReminders(){
	global $my;
	loadLib('cache');
    			
    $cache = new mycache( 'reminders' , 'reminders' );    			
    
    if( $c = $cache->getCache() ){
    	
	    $c = unserialize($c);
	    
	    $now 	= time();
	    $upto 	= $now + 600;
	    
	    $a_array = array();
	    foreach( $c as $k => $v ){
	    	if( $v >= $now && $v <= $upto ){
	    		$a_array[] = $k;
	    	}
	    }
	    
	    if( count( $a_array ) ){
	    	$activities = $database->setQuery( " SELECT * FROM #__mdigm_activities "
	    	."\n WHERE id IN ( ".implode( ',' , $a_array )." )"  );

		    foreach( $activities as $a ){
		    	$return['result'] = array(
		    		'id'=>$a->di,
		    		'subject'=>$a->subject,
		    		'start_date'=>$start_date,
		    		'start_time'=>$start_time
		    	);
		    }
	    }
	    
	    
    }    
    
  echo json_encode( $return );  
    
  exit();  
}



function syncgcal(){
	global $database , $permissions , $my ;
	
	loadClass( 'gcal' );	
	$return = array();
	if( ! $my->id ){
		$return['result'] = 'error';			
		$return['error'] = 'not login';
		echo json_encode( $return );
		exit();
	}
	
	if( ! $permissions->google_username || ! $permissions->google_password){
		$return['result'] = 'no gcal account';					
		echo json_encode( $return );
		exit();
	}

	// check if calendar has been sync recently		
	//if( !isset( $_GET['force_sync']) ){
	if( false){	
		$params 	= 	unserialize( $permissions->params );
		$last_gcal_sync	=	isset( $params['last_gcal_update'] )? $params['last_gcal_update'] : 0 ;
		$now = time() - 600 ;
		$return['last_sync'] =	$last_gcal_sync; 
		$return['now'] =	$now;
		if( $last_gcal_sync > $now ){
			$return['result'] = 'resync upto date';
			$return['error'] = '';
								
			echo json_encode( $return );
			exit();
		}
		
	}	
		
	if( isset( $params['last_gcal_sync'] ) ){
		$last_gcal_sync = $params['last_gcal_sync'];
	}
	
	try{
		$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
		
		
		
	}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
		
	
	if( ! $gcal->client){
		$return['result'] = 'gcal failed';
		$return['error'] = ' Your Google account is invalid. Please check Google username and password on My Account Settings';					
		echo json_encode( $return );
		exit();							
	}
	
	
	$mode = mosGetParam( $_REQUEST , 'mode' , '' );
	$month = mosGetParam( $_REQUEST , 'month' , '' );
	
	if($mode != "") {
		$monsArray = array("Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4, "May" => 5, "Jun" => 6, "Jul" => 7, "Aug" =>  8, "Sep" => 9, "Oct" => 10, "Nov" => 11, 
		"Dec" => 12,"January" => 1, "February" => 2, "March" => 3, "April" => 4, "May" => 5, "June" => 6, "July" => 7, "August" =>  8, "September" => 9, "October" => 10, 
		"November" => 11, "December" => 12);
		
		$monthIndex = $monsArray[$month];
		
	}


	if($month == "") {
	
		//$date_start		=	date('Y-m-d H:i:s');
		//$date_to		=	date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')+60 , date('Y') ) );
		
		$date_start = date( 'Y-m-d' , mktime( 0,0,0 , date('m')-1 , '01', date('Y') ) );
		$date_to = date('Y-m-t',strtotime('next month'));
	}
	else
	{
		if($mode == "next") { //sync and load data for next 1 month from selected month
			$date_start = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex+1 , '01', date('Y') ) );
			$date_to = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex+2 , 01, date('Y') ) );    //last date of month	
		}
		elseif($mode == "prev"){ //sync and load data for pre to 1 months 1 month from selected month
			$date_start = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex-2 , '01', date('Y') ) );
			$date_to = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex-1 , '01', date('Y') ) );    //last date of month	
		}
		else { //sync and load data for selected/current month
			$date_start = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex , '01', date('Y') ) );
			$date_to = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex , '01', date('Y') ) );    //last date of month	
		}
		
	}
	
	//echo $date_start." ".$date_to;
	//echo "</br>";

	try{
		$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
		
	}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
	
	$success	=	0;
	$updated	=	0;
	$events_array  = array();
	
	foreach( $events as $event ){
	
	$gcalid	 =	$gcal->parseIdText($event->id->text);
	//echo $event->title->text." GCAL_ID:".$gcalid;
	//echo "\n";
		// check if activity has been added already
	$database->setQuery( "Select id FROM #__mdigm_activities as a"
		."\n WHERE gcalid='$gcalid' "
		);
																		
		$cnt		=	$database->loadResult();
		$is_series	=	false;
		
		if( count($event->when) > 1 ){
			$series_id	=	mosMakeRandomText( 8 );
			$is_series	=	true;	
		}
							
		loadClass( 'date' );
	//------------- update exsisting events-----------	
	if( $cnt ){	              				
		foreach( $event->when as $w ){
				//---------------- convert the time for current timezone ------
				$dateObj = new DateTime($w->startTime);
				$dateObj->setTimezone(new DateTimeZone('America/Whitehorse'));
				$startTimeVar = $dateObj->format('Y-m-d\TH:i:s');
				//----------------
				$date			=	new dateClass($startTimeVar , 1);
				$start_date		=	$date->date;
				$start_time		=	$date->time;

				
				$database->setQuery( "Select id FROM #__mdigm_activities as a"
				."\n WHERE gcalid='$gcalid' AND start_date = '$start_date' "
				);							
				//recheck activity for recurring events having same gcalid
				$activity_id =	$database->loadResult(); 																														
				
				$cnt =	$activity_id ? $activity_id : $cnt; 
				
				$activity = new activity($database);
				
				$activity->load( $cnt );
			if( $event->eventStatus->value == 'http://schemas.google.com/g/2005#event.canceled' ){		
								
					/*$events_array[] = array( 
					'id' => $activity->id ,
					'start_date' => $start_date.' '.$start_time,					
					'end_date' => $start_date.' '.$end_time,
					'subject' => $activity->subject,
					'status' => 'cancelled'			
					);*/
					$activity->subject = htmlspecialchars_decode($event->title->text);
					$activity->status = 'cancelled';
					$activity->store();
					
					continue;
				}
				else
				{
					//---------------- convert the time for current timezone ------
					$dateObj = new DateTime($w->endTime);
					$dateObj->setTimezone(new DateTimeZone('America/Whitehorse'));
					$endTimeVar = $dateObj->format('Y-m-d\TH:i:s');
					//--------------------------------------------------------------

					$edate			=	new dateClass($endTimeVar , 1);
					$end_date		=	$edate->date;
					$end_time		=	$edate->time;
					$start_time = date('H:i:s',strtotime($start_time));
					$end_time = date('H:i:s',strtotime($end_time));
					//$activity->subject = str_replace("&", "and", $event->title->text);//htmlentities($event->title->text, ENT_COMPAT);
					//echo $activity->subject;
					$activity->subject = htmlentities($event->title->text, ENT_COMPAT);
					$activity->start_date	=	$start_date;
					$activity->start_time	=	$start_time;	
					$activity->end_date		=	$end_date;
					$activity->end_time		=	$end_time;
					$activity->activity_where	=	$event->where[0]->text;
					$activity->activity			=	$event->content->text;
					$activity->gcal_last_update = 	$event->updated->text;
					//echo "<pre>";print_r($activity);echo "</pre>";
					$act = $activity->store();	
								
				}
				// activity already found... update what needs to be updated		
				$updated = $updated + 1;
				
				break;
			}	
			
			/************************************************************/
			
			if( isset($event->recurrence->text) && $event->recurrence->text ){
				
				$rec_array	= explode("\n" , $event->recurrence->text );
				$getStartDateTimeText  = $rec_array[0];
				$getEndDateTimeText  = $rec_array[1];
			
				$startDateTimeArr = getDateAndTime($getStartDateTimeText);
				$startDate = $startDateTimeArr['date'];
				$startTime = $startDateTimeArr['time'];
				
				
				$database->setQuery( "Select id FROM #__mdigm_activities as a"
				."\n WHERE gcalid='$gcalid' AND start_date = '$startDate' "
				);							
				//recheck activity for recurring events having same gcalid
				$activity_id =	$database->loadResult(); 																														
				
				$cnt =	$activity_id ? $activity_id : $cnt; 
				
				$activity = new activity($database);
				$activity->load( $cnt );
				
				$activity->subject = htmlentities($event->title->text, ENT_COMPAT);
				
				
				$activity->start_date = $startDate;
				$activity->start_time = $startTime;
			
			
				$endDateTimeArr = getDateAndTime($getEndDateTimeText);
				$activity->end_date = $endDateTimeArr['date'];
				$activity->end_time = $endDateTimeArr['time'];
				
				$activity->activity_where	=	$event->where[0]->text;
				$activity->activity			=	$event->content->text;
				$activity->gcal_last_update = 	$event->updated->text;
				
				$activity->store();
			}
			
			/************************************************************/
			
			$events_array[] = array( 
					'id' => $activity->id ,
					'start_date' => $activity->start_date.' '.$activity->start_time,					
					'end_date' => $activity->start_date.' '.$activity->end_time,
					'subject' => $activity->subject,	
					'status' => $activity->status,		
				);			
				
		}
		else{	//------------- add new events-----------	
	
			
				$first_event = true;
				$recurring = count( $event->when ) > 1 ? true : false;
				
				$activity	= 	new activity( $database );						
				$gcal_imp = explode( '_' , $gcalid );
			
				if( count($gcal_imp) > 1 ){
					$activity->series_id = $gcal_imp[0];	
				}
				
				$activity->subject			=	htmlspecialchars_decode($event->title->text);
				$activity->activity			=	$event->content->text;
				$activity->activity_where	=	$event->where[0]->text;												
				$activity->date_added		=	$created_date_time;
				$activity->gcal_last_update	=	$event->updated->text;
				
				if( $event->eventStatus->value == 'http://schemas.google.com/g/2005#event.canceled' ){
					foreach( $event->when as $w ){
						$sdate = $w->startTime;
						$date			=	new dateClass($w->startTime , 1);
						$start_date		=	$date->date; 
						$start_time		=	$date->time;
						$edate			=	new dateClass($w->endTime , 1);
						$end_date		=	$edate->date;
						$end_time		=	$edate->time;	
						$activity->start_date = $start_date;
						$activity->end_date = $end_date;
						
					}
					$activity->status		=	'cancelled';
				}
				else{
					$activity->status		=	'undone';	
				}
				
				$activity->gcalid		=	$gcalid;		
				$activity->userid		=	$my->id;
				$activity->createdby	=	$my->id;
				
				if($is_series){
					$activity->series_id	=	$series_id;
				}	
			
				if( isset($event->recurrence->text) && $event->recurrence->text ){
					$rec_array	= explode("\n" , $event->recurrence->text );
					
					$getStartDateTimeText  = $rec_array[0];
					$getEndDateTimeText  = $rec_array[1];
					
					$startDateTimeArr = getDateAndTime($getStartDateTimeText);
					$activity->start_date = $startDateTimeArr['date'];
					$activity->start_time = $startDateTimeArr['time'];

					$endDateTimeArr = getDateAndTime($getEndDateTimeText);
					$activity->end_date = $endDateTimeArr['date'];
					$activity->end_time = $endDateTimeArr['time'];
					$series_obj = new activitiesv2($database);
					$activity->series_id = $series_obj->getNewSeriesId();
					
					foreach( $rec_array as $rec ){
						if( substr( $rec , 0 , 5 ) == 'RRULE' ){
							$rec = str_replace( 'RRULE:' , '' , $rec );
							break;							
						}					
					}	

					$rec_imp = explode( ';' , $rec );
					foreach( $rec_imp as $r ){
						list( $key , $val )	= explode( '=', $r );
						
						switch( $key ){
							case 'FREQ':
								$activity->repeats = strtolower( $val );
								break;
							case 'UNTIL':
								$activity->repeat_until = date( 'Y-m-d' , strtotime( $val ) );
							break;
							case 'BYDAY':
								$d_array = array( 
									'MO' => '1' , 'TU'=> '2' 
									,'WE'=> '3', 'TH'=> '4', 'FR'=> '5' 
									,'SA'=> '6' , 'SU'=> '7'  
								);
								$days =	explode( ',' , $val );
								foreach( $days as $d ){
									$activity->repeat_weekdays .= $d_array[$d];
								}
							if($activity->repeats == 'monthly')
							{
								$activity->repeats_every = 'repeat_by_week_day';
							}
							break;	
							case 'BYMONTHDAY':
							if($activity->repeats == 'monthly')
							{
								$activity->repeats_every = 'month_day';
							}
							
						}												
					}									
				}

				$act = $activity->store();
				if( $act ){
					
					foreach( $event->when as $w ){
											
						$sdate = $w->startTime;
						$date			=	new dateClass($w->startTime , 1);
						$start_date		=	$date->date; 
						$start_time		=	$date->time;
						
						if( $first_event ){
							$first_date = $start_date; 												
							$first_event = false;
						}	
						$date_create		=	new dateClass($event->published->text , 1);
						$created_date_time	=	$date_create->date.' '.$date_create->time;
						$edate			=	new dateClass($w->endTime , 1);
						$end_date		=	$edate->date;
						$end_time		=	$edate->time;				
						
						if( $is_series ){
							$activity_id = 'series_'.$series_id.'_'.dateClass::getMYSQLToDaysByDate($start_date);
							$activity->repeat_until		=	$end_date;
							
						}else{
							$activity_id = $activity->id;
							$activity->repeat_until		=	'';
													
						}
						$activity->start_date		=	$first_date;
						$activity->start_time		=	$start_time;	
						$activity->end_date			=	$first_date;
						$activity->end_time			=	$end_time;
						
						/*$events_array[] = array( 
							'id' => $activity_id ,
							'start_date' => $start_date.' '.$start_time,					
							'end_date' => $start_date.' '.$end_time,
							'subject' => $activity->subject,
							'status' => $activity->status,			
						);*/
						
						$last_day = $end_date;
					}
					$activity->store();
				}
				$events_array[] = array( 
							'id' => $activity_id ,
							'start_date' => $start_date.' '.$start_time,					
							'end_date' => $start_date.' '.$end_time,
							'subject' => $activity->subject,
							'status' => $activity->status,			
						);
			}
		}   	

	/*
	echo "<pre>";
	print_r($activity);
	echo "</pre>";
	*/			
	
	$params['last_gcal_update'] = time();
	$permissions->params  = serialize( $params );
	$permissions->store(); 
		 
    $return['result'] = 'success';  
    $return['events'] = $events_array;    
   
    echo json_encode( $return );
    exit();		
	
	
}

function getDateAndTime($dateTimeString)
{
		$dateTimeTextArray	= explode(":" , $dateTimeString );	
		$dateTimeText = $dateTimeTextArray[1];

		$dateTimeTextArray	= explode("T" , $dateTimeText );	
	
		$date = $dateTimeTextArray[0];
		$time = $dateTimeTextArray[1];
		
		list($year,$month,$day) = sscanf($date, '%4d%2d%2d');
		$date = sprintf('%4d-%02d-%02d',$year,$month,$day);
		
		list($hour,$minute,$second) = sscanf($time, '%2d%2d%2d');
		$time = sprintf('%02d:%02d:%02d',$hour,$minute,$second);

		return array("date"=>$date, "time"=>$time);
}


function object_to_array($data) 
{
if ((! is_array($data)) and (! is_object($data))) return 'xxx'; //$data;

$result = array();

$data = (array) $data;
foreach ($data as $key => $value) {
    if (is_object($value)) $value = (array) $value;
    if (is_array($value)) 
    $result[$key] = object_to_array($value);
    else
        $result[$key] = $value;
}

return $result;
}




function getFrequencyAndEndDate($dateTimeString)
{
	
	$frequency = extract_unit($dateTimeString,":FREQ=",";UNTIL=");
	$untilData = extract_unit($dateTimeString,";UNTIL=","z");
	
	
	$dateTimeTextArray	= explode("T" , $untilData );	
	$date = $dateTimeTextArray[0];
	list($year,$month,$day) = sscanf($date, '%4d%2d%2d');
	$date = sprintf('%4d-%02d-%02d',$year,$month,$day);
	
	return array("frequency"=>$frequency, "until"=>$date);
		
}


function extract_unit($string, $start, $end)
{
	$pos = stripos($string, $start);
	
	$str = substr($string, $pos);
	
	$str_two = substr($str, strlen($start));
	
	$second_pos = stripos($str_two, $end);
	
	$str_three = substr($str_two, 0, $second_pos);
	
	$unit = trim($str_three); // remove whitespaces
	
	return $unit;
}
