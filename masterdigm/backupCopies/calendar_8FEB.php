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
	$return['activity_id'] = $activity_id;
	if( substr( $activity_id , 0, 6 ) == 'series' ){
		$aid_array = explode( '_' , $activity_id );		
		$return['selected_date'] = $aid_array[2];
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
			if( ! $series_coverage ){
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
		$return['activity_id'] 	= $activity->id;

		$database->setQuery('Show columns FROM jos_mdigm_activities ');        	
    	$vars	=	$database->loadResultArray();
    	foreach( $vars as $var ){
    		$return[$var] = $activity->$var;
    	}	

    	if( $activity->repeats != 'none'){
    		
    		$database->setQuery( "SELECT TO_DAYS( '$activity->start_date' ) " );
			$start_day 	=  $database->loadResult();
			
			$database->setQuery( "SELECT TO_DAYS( '$activity->repeat_until' ) " );
			$end_day 	=  $database->loadResult();	
			$return['e_date'] = $end_day;
			$return['s_date'] = $start_day;
			
			switch( $activity->repeats ){
				case 'daily':			
						$event_new = array();	
			    		for( $start_day ; $start_day <= $end_day ; $start_day++ ){												
															
							$r_sdate = 	dateClass::getDateByMYSQLToDays( $start_day );
							$text 	 = 	limit_words( $activity->subject , 8 , '...' );
							$text 	= 	str_replace('"','&quot;' , $text );						
							$text 	= 	stripslashes( $text );
							$e_id = 'series_'.$activity->series_id.'_'.$start_day;
							$event_new[] = array(
								'id'=> $e_id,
								'subject'=> $text,
								'start_date'=> $r_sdate.' '.$activity->start_time,
								'end_date'=> $r_sdate.' '.$activity->end_time,  
							); 										
																				
						}
						
						$return['recurring_events'] = $event_new;
				break;	
				case 'weekly':
						$event_new = array();
						for( $start_day ; $start_day <= $end_day ; $start_day++ ){															    			
							$r_sdate = 	dateClass::getDateByMYSQLToDays( $start_day );
							$weekday = date( 'N' , strtotime( $r_sdate ) );
							
							if( in_array( $weekday ,  $weekdays_array ) ){
																							
								$text 	 = 	limit_words( $activity->subject , 8 , '...' );
								
								$text 	= 	str_replace('"','&quot;' , $text );						
								$text 	= 	stripslashes( $text );
								
								$e_id = 'series_'.$activity->series_id.'_'.$start_day;
															
								$event_new[] = array(
									'id'=> $e_id,
									'subject'=> $text,
									'start_date'=> $r_sdate.' '.$activity->start_time,
									'end_date'=> $r_sdate.' '.$activity->end_time,  
								); 										
							}													
						}					
						$return['recurring_events'] = $event_new;
				break;
				case 'monthly':
					
					$event_new = array();	
					$repeat_by_month = mosGetParam( $_REQUEST , 'repeat_by_month' , '' );
					$r_sdate = 	dateClass::getDayOfTheMonthByMYSQLToDays($start_day);
					
					if( $repeat_by_month == 'month_day' ){
						
						$i_week_day	= dateClass::getDayOfTheWeekByMYSQLToDays( $start_day );
																					
					   	for( $start_day ; $start_day <= $end_day ; $start_day++ ){
					   																	    			
							$month_day 	= dateClass::getDayOfTheMonthByMYSQLToDays( $start_day );
							$week_day	= dateClass::getDayOfTheWeekByMYSQLToDays( $start_day );
							
							if( $repeat_by_month == 'month_day' && $i_week_day == $week_day ){
								
								$date = dateClass::getDateByMYSQLToDays( $start_day );
								$text 	 = 	limit_words( $activity->subject , 8 , '...' );								
								$text 	= 	str_replace( '"' , '&quot;' , $text );						
								$text 	= 	stripslashes( $text );
									
								$e_id = 'series_'.$activity->series_id.'_'.$start_day;
																
								$event_new[] = array(
									'id'=> $e_id,
									'subject'=> $text,
									'start_date'=> $date.' '.$activity->start_time,
									'end_date'=> $date.' '.$activity->end_time,  
								);
							}
							
							if( $repeat_by_month == 'month_day' && $month_day == $r_sdate ){
								
								$text 	 = 	limit_words( $activity->subject , 8 , '...' );								
								$text 	= 	str_replace( '"' , '&quot;' , $text );						
								$text 	= 	stripslashes( $text );
									
								$date = dateClass::getDateByMYSQLToDays( $start_day );
								$e_id = 'series_'.$activity->series_id.'_'.$start_day;
																
								$event_new[] = array(
									'id'=> $e_id,
									'subject'=> $text,
									'start_date'=> $date.' '.$activity->start_time,
									'end_date'=> $date.' '.$activity->end_time,  
								);
							}																																												 																													
						}
					}
					
					if( $repeat_by_month == 'repeat_by_week_day' ){
						
						$i_todays 		= dateClass::getMYSQLToDaysByDate( $activity->start_date ); 						
						$i_week_day 	= dateClass::getDayOfTheWeekByMYSQLToDays( $i_todays );						 
						$i_month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays( $start_days ) / 7 );
						
						for( $start_day ; $start_day <= $end_day ; $start_day++ ){
					   																	    			
							$month_day = date( 'j' , strtotime( dateClass::getDateByMYSQLToDays( $start_day ) ) );
							$week_day	= dateClass::getDayOfTheWeekByMYSQLToDays( $start_day );							
							$month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays( $start_day ) / 7 );
							
							if( $repeat_by_month == 'repeat_by_week_day'  && $i_week_day == $week_day && $i_month_week == $month_week ){
								
								$d_date  = dateClass::getDateByMYSQLToDays($start_day);
								
								$text 	= limit_words( $activity->subject , 8 , '...' );								
								$text 	= str_replace( '"' , '&quot;' , $text );						
								$text 	= stripslashes( $text );
								//$text 	= $a->start_date.' '.$i_month_week.' '.$month_week ;
									
								$e_id = 'series_'.$activity->series_id.'_'.$start_day;
								$event_new[] = array(
									'id'=> $e_id,
									'subject'=> $text,
									'start_date'=> $d_date.' '.$activity->start_time,
									'end_date'=> $d_date.' '.$activity->end_time,  
								);								
							}																																												 																													
						}
						
					}
											
					$return['recurring_events'] = $event_new;
					
				break;		
			}	
			
    	}
    	
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
			else{	
					if( $activity->gcalid ){
					$query = $gcal->service->newEventQuery();
					$query->setUser('default');
					$query->setVisibility('private');
					$query->setProjection('full');
					$query->setEvent($activity->gcalid);
					
				   $date_start = '';
					$date_to = '';
					try{
							
							$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
							activitiesv2::saveToGCal($activity);
						}
					    
					catch (Zend_Gdata_App_Exception $e){
					    
					    $error_msg	=	'GCal id failed to save on saveactivity()! /n'.$e->getMessage();
					    logError( $error_msg );
					    
					}
									
				}else{
						
						if(!empty($_REQUEST['summary_value']))
						{
							$day_event['weekday'] = $_REQUEST['summary_value'];
						}
						if(!empty($_POST['month_value']))
						{
							$day_event['month_value'] = $_POST['month_value'];
							$day_event['day_value'] = $_POST['day_value'];
						}
						
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
	error_reporting(0);
	if(is_null($event_id))
	{
		$event_id = mosGetParam( $_REQUEST , 'aid' , '' );
	}
	else
	{
		$event_id = $event_id;
	}
	
	$selected_date = mosGetParam( $_REQUEST , 'selected_date' , 0 );
	$r_sdate = 	dateClass::getDateByMYSQLToDays( $selected_date );
	
	$delete_all = mosGetParam( $_REQUEST , 'delete_all' , 0 );
	
	$activity = activitiesv2::getActivityByEventId( $event_id );
	//echo "<pre>";print_r($activity);echo "</pre>";exit;
	$return = array();
	
	$activity_id = $activity->id;
	$gcalid = $activity->gcalid;
	$series_id = $activity->series_id;
			
	if( $delete_all ==1){
			
		if( $activity->delete() ){
			
			$return[ 'result' ] = 'success';
			$return[ 'series_id' ] 	= $activity->series_id;
			$return[ 'start_day' ] 	= dateClass::getMYSQLToDaysByDate( $activity->start_date );
			$return[ 'end_day' ] 	= dateClass::getMYSQLToDaysByDate( $activity->repeat_until );
			
			activity_notification2::deleteByActivityId( $activity_id );
			
			
								
			if( $gcalid ){
				activitiesv2::deleteGCal( $gcalid );	
			}
			
	}
	}
	if( $activity->series_id ){
			// delete event from repeating events
			$activity_exception = new activity_exceptions( $database );
			$activity_exception->series_id 			= $activity->series_id;
			$activity_exception->recurring_date		= $selected_date;			
			$activity_exception->new_activity_id 	= '0'; 
			$activity_exception->store();
			
			$return[ 'result' ] 	= 'success';
			$return[ 'event_id' ] 	= 'series_'.$activity->series_id.'_'.$selected_date;
					

			$activity_start = explode("-",$activity->start_date);
			$activity_end  = explode("-",$activity->repeat_until);
			$activity_start_time = explode(":",$activity->start_time);
			$activity_end_time = explode(":",$activity->end_time);
			if(is_numeric($selected_date))
			{
				$remove_selected_date = 	dateClass::getDateByMYSQLToDays( $selected_date );
			}
			else
			{
				$remove_selected_date = $selected_date;
			}
			$remove_date = explode("-",$remove_selected_date);
			
			$str_date_time = date('Ymd',mktime(0,0,0,$activity_start[1],$activity_start[2],$activity_start[0])).'T'.date('His',mktime($activity_start_time[0],$activity_start_time[1],$activity_start_time[2],0,0,0));
			$end_date_time = date('Ymd',mktime(0,0,0,$activity_end[1],$activity_end[2],$activity_end[0])).'T'.date('His',mktime($activity_end_time[0],$activity_end_time[1],$activity_end_time[2],0,0,0)).'Z';
			$remove_date_time = date('Ymd',mktime(0,0,0,$remove_date[1],$remove_date[2],$remove_date[0])).'T'.date('His',mktime($activity_start_time[0],$activity_start_time[1],$activity_start_time[2],0,0,0));
			$repeats = strtoupper($activity->repeats);
			
			
			$startDate = dateClass::getDateByMYSQLToDays( $startDate );
			$startDate 	= str_replace( '-' , '' , $startDate );
			
			$gcal_suffix = $startDate.'T'.$startTime.'00Z';
			 			
			/*if( $gcalid ){
				$gcalid = $gcalid.'_'.$gcal_suffix;
				$return[ 'gaclid' ] = $gcalid;				 
				activitiesv2::deleteGCal( $gcalid );
			}*/
					
			if($gcalid)
			{
				$repeats = strtoupper($activity->repeats);
				$repeat_weekdays = $activity->repeat_weekdays;
				$monthly_repeat_weekday= $activity->monthly_repeat_weekday;
				$repeats_every = $activity->repeats_every;
				
				
				activitiesv2::deleteRecurringGcal($gcalid,$remove_date_time,$str_date_time,$end_date_time,$repeats,$activity);
			}
			
		}
		
		
		
		else{
				
			 /*delete non repeating events*/
			if( $activity->delete() ){	
				activity_notification2::deleteByActivityId( $activity_id );
				
				if( $gcalid ){ 
						
						activitiesv2::deleteGCal( $gcalid );
				}	
				
				$return[ 'result' ] = 'success';
				$return[ 'activity_id' ] 	= $activity->id;
				$return[ 'start_date' ] 	= $selected_date;				
			}else{
			
				$return[ 'result' ] = 'error';
				$return[ 'error' ] = $activity->getError();
			}
		}	
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
	
	$date_start		=	date('Y-m-d H:i:s');
	$date_to		=	date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')+60 , date('Y') ) );
	
	
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
				$date			=	new dateClass($w->startTime , 1);
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
					$edate			=	new dateClass($w->endTime , 1);
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
