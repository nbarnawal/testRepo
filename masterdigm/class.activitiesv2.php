<?php

class activitiesv2 extends dbextend{
	
    public function __construct( $db ){       
       $this->mosDBTable( '#__mdigm_activities', 'id', $db );              
    }

    public function check(){
     	return true; 
    }
    
	public function getNewSeriesId(){
		global $database;
		
		return $sid = mosMakePassword( 16 );
		
		$database->setQuery(" SELECT id FROM #__mdigm_activities as a  "
		."\n WHERE series_id = '$sid' "		
		);
		
		if( ! $database->loadResult() ){
			$sid = $this->getNewSeriesId();
		}
		
		return $sid;
		
	}
	
	public static function saveToGCal( $activity ){
		//echo "<pre>";print_r($activity);die;
	
		global $database , $permissions;
			loadClass( 'gcal' );
						
			$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
			$service = $gcal->service;
				
			
			$activitySDate = $activity->start_date;
			
			$date_start		=	date('Y-m-d', mktime( 0, 0, 0, date('m',strtotime($activitySDate)),01, date('Y',strtotime($activitySDate)) ) );
			$date_to		=	date('Y-m-t', mktime( 0, 0, 0, date('m',strtotime($activitySDate)),01, date('Y',strtotime($activitySDate)) ) );
				
			try{
				
				$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
				
				
			}catch(Exception $e){
				$return['result'] = 'gcal failed';
				$return['error'] = $e->getMessage();					
				echo json_encode( $return );
				exit();			
			}
			
		/*
		echo "<ul>";
		foreach($events as $event)
		{
			echo "<li>". $event->title->text." -----".$event->when[0]->startTime."</li>";
		}
		echo "</ul>";	
		*/
			
		foreach($events as $event)
			{			
				if(!empty($event->id->text))
			{
				$eid = $gcal->parseIdText($event->id->text);
				if($eid == $activity->gcalid)
				{
					
					$event->title = $service->newTitle( $activity->subject );
					$event->content = $service->newContent( $activity->activity );
					$event->where = array($service->newWhere( $activity->where ));
							 
					$startDate 	= $activity->start_date;
					$startTime 	= $activity->start_time;
					$endDate 	= $activity->end_date;
					$endTime 	= $activity->end_time;
					loadClass('date');
		
					if(dateClass::is_dst($startDate)){
					$permissions->timezone	+=	1; 
					}
		  
					$tzOffset 	= 	isset( $tzArray[$permissions->timezone] ) ? $tzArray[$permissions->timezone] : '-08' ;
				
			
				if($activity->repeat_until == "" || $activity->repeat_until == '0000-00-00')
				{
					$when = $service->newWhen();
					$when->startTime 	= "{$startDate}T{$startTime}.000{$tzOffset}:00";
					$when->endTime 	= "{$endDate}T{$endTime}.000{$tzOffset}:00";
				
					$event->when 	= array($when);
				}
				else
				{
					
					$repeats = strtoupper($activity->repeats);
					$repeat_until = $activity->repeat_until;
					$str_arr = explode("-",$startDate);
					$str_time = explode(":",$startTime);
					$str_date_time = date('Ymd',mktime(0,0,0,$str_arr[1],$str_arr[2],$str_arr[0])).'T'.date('His',mktime($str_time[0],$str_time[1],$str_time[2],0,0,0));
					$end_arr = explode("-",$repeat_until);
					$end_time_arr = explode(":",$endTime);
					$end_date_time = date('Ymd',mktime(0,0,0,$end_arr[1],$end_arr[2],$end_arr[0])).'T'.date('His',mktime($end_time_arr[0],$end_time_arr[1],$end_time_arr[2],0,0,0)).'Z';
					switch($repeats)
					{
							case 'DAILY':
							$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
										"RRULE:FREQ=".$repeats.";UNTIL=".$end_date_time."\r\n";
										
								break;
							case 'WEEKLY':
							
							$required_day = array();	
							$days_alpha = array('1'=>'MO','2'=>'TU','3'=>'WE','4'=>'TH','5'=>'FR','6'=>'SA','7'=>'SU');
							$r_days = (string)$activity->repeat_weekdays;
							$repeated_days = strlen($r_days);
							for($i=0;$i<$repeated_days;$i++)
							{
								$required_day[] = $days_alpha[$r_days[$i]];
								
							}
							
							$days = implode(",",$required_day);
							$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
										"RRULE:FREQ=".$repeats.";WKST=SU;BYDAY=".$days.";UNTIL=".$end_date_time."\r\n";
							break;
				
						case 'MONTHLY':
							if($activity->repeats_every == 'month_day')
							{
								$string_length = strlen($activity->monthly_repeat_weekday);
								if($string_length>3)
								{
									$day = substr($activity->monthly_repeat_weekday,0,2);
								}
								else
								{
									$day = substr($activity->monthly_repeat_weekday,0,1);
								}
								$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
									"RRULE:FREQ=".$repeats.";BYMONTHDAY=".$day.";UNTIL=".$end_date_time."\r\n";
							}
							else
							{
									$bymonth_day = explode(" ",$activity->monthly_repeat_weekday);
									
									if($bymonth_day[0]!='last')
									{
										if($bymonth_day[0]=='First')
										{
											$num= 1;
										}
										if($bymonth_day[0]=='Second')
										{
											$num= 2;
										}
										if($bymonth_day[0]=='Third')
										{
											$num= 3;
										}
										if($bymonth_day[0]=='Fourth')
										{
											$num= 4;
										}
								if($bymonth_day[1]=='Monday')
									{
										$re_day = 'MO';
									}
									if($bymonth_day[1]=='Tuesday')
									{
										$re_day = 'TU';
									}
									if($bymonth_day[1]=='Wednesday')
									{
										$re_day = 'WE';
									}
									if($bymonth_day[1]=='Thursday')
									{
										$re_day = 'TH';
									}
									if($bymonth_day[1]=='Friday')
									{
										$re_day = 'FR';
									}
									if($bymonth_day[1]=='Saturday')
									{
										$re_day = 'SA';
									}
									if($bymonth_day[1]=='Sunday')
									{
										$re_day = 'SU';
									}
									
								$repeat_day = $num.$re_day;
								
								}
								else
								{
									if($bymonth_day[1]=='Monday')
									{
										$re_day = '-2MO';
									}
									if($bymonth_day[1]=='Tuesday')
									{
										$re_day = '-3TU';
									}
									if($bymonth_day[1]=='Wednesday')
									{
										$re_day = '=-4WE';
									}
									if($bymonth_day[1]=='Thursday')
									{
										$re_day = '-5TH';
									}
									if($bymonth_day[1]=='Friday')
									{
										$re_day = '-6FR';
									}
									if($bymonth_day[1]=='Saturday')
									{
										$re_day = '-7SA';
									}
									if($bymonth_day[1]=='Sunday')
									{
										$re_day = '-1SU';
									}
									$repeat_day = $num.$re_day;
								}
								$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
										"RRULE:FREQ=".$repeats.";BYDAY=".$repeat_day.";UNTIL=".$end_date_time."\r\n";
							}
						break;
						case 'YEARLY':
						$com_month = $activity->yearly_month_value;
						$com_day = $activity->yearly_day_value;
						$month = array('1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December');
						foreach($month as $key=>$val)
						{
							if($val == $com_month)
							{
								$month_value = $key;
							}
						}
						$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
									"RRULE:FREQ=".$repeats.";BYYEARDAY=".$com_day.";BYMONTH=".$month_value.";UNTIL=".$end_date_time."\r\n";
							
						break;
					}

					$event->recurrence = $service->newRecurrence($recurrence);	
				}
				try{
					$event->save($event);
					 /*if( $event->save($event) ){
						echo $tzOffset;
					}else{
						echo 'FFFF';
					}
				}*/
				}
				
				catch(Exception $e ){
					echo $e->getMessage();
				}
			}
		}	
	}
			
			return $event;
	}
	
	public static function getActivityByEventId( $event_id ){
		global $database;
		if( substr( $event_id , 0, 6 ) == 'series' ){
			$aid_array = explode( '_' , $event_id ); 
			$act = activitiesv2::getActivityBySeriesId( $aid_array[1] );
			$activity = new activitiesv2( $database );
			$activity->load( $act->id );
			
		}else{
			$activity = new activitiesv2( $database );
			$activity->load( $event_id );	
		}	
		
		return $activity;
	}
	
	public static function getActivityBySeriesId( $series_id ){
		global $database;
		//echo "SELECT * FROM #__mdigm_activities as a WHERE series_id = '$series_id'";exit;
		$database->setQuery("SELECT * FROM #__mdigm_activities as a WHERE series_id = '$series_id' ");
		$database->loadObject( $activity );
		
		return $activity;		
	}
	
	public static function getRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options = array() ){
		global $database;
		
		if( is_array( $users ) ){
     		$users = implode(',' , $users );
     	}
     	$where[] = " ( a.userid IN ( $users ) OR a.assigned_to IN ( $users ) ) ";
     	$whereText = ' AND '.implode( ' AND ' , $where );
     	/* echo $sql = "SELECT a.*, TO_DAYS( start_date ) as start_day_int, TO_DAYS( repeat_until ) as end_day_int  , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
			."\n MINUTE(end_time) as end_min"
			."\n FROM #__mdigm_activities as a"
			."\n WHERE  ( ( TO_DAYS( start_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )  OR ( TO_DAYS( end_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )   )"
			."\n AND repeats != 'none' AND a.status != 'cancelled' "
			."\n $whereText ";*/
			
     	$database->setQuery( " SELECT a.*, TO_DAYS( start_date ) as start_day_int, TO_DAYS( repeat_until ) as end_day_int  , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
			."\n MINUTE(end_time) as end_min"
			."\n FROM #__mdigm_activities as a"
			."\n WHERE  ( ( TO_DAYS( start_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )  OR ( TO_DAYS( end_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )   )"
			."\n $whereText "			
		);
		$recurring_activities	=	$database->loadObjectList();
		
		
				 
		return $recurring_activities;		
	}
	public static function getfinalRecurringActivitiesByDateRange($users , $start_date , $end_date , $options = array())
	{
		global $database;
		
		if( is_array( $users ) ){
     		$users = implode(',' , $users );
     	}
     	$where[] = " ( a.userid IN ( $users ) OR a.assigned_to IN ( $users ) ) ";
     	$whereText = ' AND '.implode( ' AND ' , $where );
     			
     	$database->setQuery( " SELECT a.*, TO_DAYS( start_date ) as start_day_int, TO_DAYS( repeat_until ) as end_day_int  , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
			."\n MINUTE(end_time) as end_min"
			."\n FROM #__mdigm_activities as a"
			."\n WHERE  ( ( TO_DAYS( start_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )  OR ( TO_DAYS( end_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )   )"
			."\n AND repeats = 'none'"
			."\n $whereText "			
		);
		$nonerecurring_activities	=	$database->loadObjectList();
		return $nonerecurring_activities;
	}
	
	public static function getNonRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options = array() ){
		global $database;
		
     	if( is_array( $users ) ){
     		$users = implode(',' , $users );
     	}
     	
     	$where[] = " a.userid IN ( $users ) OR a.assigned_to IN ( $users ) ";
     	$whereText = ' AND '.implode( ' AND ' , $where );
     	/*echo $sql = " SELECT a.* , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
		."\n MINUTE(end_time) as end_min"
		."\n FROM #__mdigm_activities as a"
		."\n WHERE  TO_DAYS( start_date ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) "
		."\n AND repeats = 'none' AND a.status != 'cancelled' "
		."\n $whereText ";*/
		    
     	$database->setQuery( " SELECT a.* , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
		."\n MINUTE(end_time) as end_min"
		."\n FROM #__mdigm_activities as a"
		."\n WHERE  TO_DAYS( start_date ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) "
		."\n AND repeats = 'none' AND a.status != 'cancelled' "
		."\n $whereText "			
		);
		$activities		=	$database->loadObjectList();
		
		return $activities;
	}
	public static function gettempRecurringActivitiesByDateRange($users , $start_date , $end_date , $options = array())
	{
		global $database;
		
		if( is_array( $users ) ){
     		$users = implode(',' , $users );
     	}
     	$where[] = " ( a.userid IN ( $users ) OR a.assigned_to IN ( $users ) ) ";
     	$whereText = ' AND '.implode( ' AND ' , $where );
     			
     	$database->setQuery( " SELECT a.*, TO_DAYS( start_date ) as start_day_int, TO_DAYS( repeat_until ) as end_day_int  , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
			."\n MINUTE(end_time) as end_min"
			."\n FROM #__mdigm_activities as a"
			."\n WHERE  ( ( TO_DAYS( start_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )  OR ( TO_DAYS( end_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )   )"
			."\n AND repeats != 'none' AND a.status != 'cancelled'"
			."\n $whereText "			
		);
		$nonerecurring_activities	=	$database->loadObjectList();
		return $nonerecurring_activities;
	}
	public static function getCancelRecurringActivitiesByDateRange($users , $start_date , $end_date , $options = array())
	{
		global $database;
		
		if( is_array( $users ) ){
     		$users = implode(',' , $users );
     	}
     	$where[] = " ( a.userid IN ( $users ) OR a.assigned_to IN ( $users ) ) ";
     	$whereText = ' AND '.implode( ' AND ' , $where );
 
     	$database->setQuery( " SELECT a.*, TO_DAYS( start_date ) as start_day_int, TO_DAYS( repeat_until ) as end_day_int  , a.status as status, a.assigned_to as assigned_to, HOUR(start_time) as hr,HOUR(end_time) as end_hr, "
			."\n MINUTE(end_time) as end_min"
			."\n FROM #__mdigm_activities as a"
			."\n WHERE  ( ( TO_DAYS( start_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )  OR ( TO_DAYS( end_date  ) BETWEEN TO_DAYS( '$start_date' ) AND TO_DAYS( '$end_date' ) )   )"
			."\n AND a.status = 'cancelled'"
			."\n $whereText "			
		);
		$cancelrecurring_activities	=	$database->loadObjectList();
		return $cancelrecurring_activities;
	}
	public static function getNextRecurringActivity( $activity ){
		global $database;
		
		loadClass( array ('date' , 'time' ) );
		$start_date 	= $activity->start_date;
		$end_date 		= $activity->repeat_until;
		$now 			=  dateClass::getMYSQLToDaysByDate( date('Y-m-d' ) );
		$start_today 	=  dateClass::getMYSQLToDaysByDate($start_date);
		$end_today 		=  dateClass::getMYSQLToDaysByDate($end_date);
				
		switch( $activity->repeats ){
			case 'daily':
																												
				if( $now < $start_today ){					
					$recur_date = dateClass::getDateByMYSQLToDays( $start_today ).' '.$activity->start_time;
					break;
				}		
				if( $start_today <= $now && $end_today >= $now ){
										
					if( ptime::convertHrMinToInt( $activity->start_time ) <= ptime::convertHrMinToInt( date('H:i:s' ) )) {						
						$recur_at = $start_today	+	1;						
					}else{
						
						$recur_at = $start_today;
					}
					
					$recur_date = dateClass::getDateByMYSQLToDays( $recur_at ).' '.$activity->start_time;
										
					break;
				}
				// past activity 
				if( $now >  $end_today ){
					return false;	
				}
									
			break;
			case 'week':
				
			break;
			case 'monthly':
			break;	
		}
		
		return $recur_date;
		
	}
	
	public static function getXMLActivitiesByDateRange( $users , $start_date , $end_date , $options = array() ){
		global $database;
		global $permissions;
		loadClass( array('activity_exceptions') );
		
		$activities = activitiesv2::getNonRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options );
		$all_recurring_activities = activitiesv2::getRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options );
		$final_recurring_activities = activitiesv2::getfinalRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options );	
		$temp_recurring_activities = activitiesv2::gettempRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options );
		$cancel_recurring_activities = activitiesv2::getCancelRecurringActivitiesByDateRange( $users , $start_date , $end_date , $options );
		
		/*
		echo "<pre>";
		print_r($activities);
		echo "</pre>";
		*/
		
		
		
		$xml = "<data>";
		loadClass( 'gcal' );
		loadClass( 'date' );
		try{
		$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
		}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
	$date_start = '';
	$date_to = '';
	try{
		
	
		//$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
		$events  		= 	$gcal->getEventsByDateRange( $start_date, $end_date );

		
	}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
			$i=0;
			foreach( $events as $event ){
				//echo $event->title->text;
				//echo "\n";
				
				
			$gcalid	 =	$gcal->parseIdText($event->id->text); 
				
				//if($event->title->text != "activityNB-1")
					//continue;
					
				//echo "---------".$gcalid." ".$event->title->text;
				//echo "\n";
				foreach($activities as $a)
				{
					
				if(!empty($a->gcalid))
				{
						//echo "=====".$a->gcalid;
						//echo "\n";

					if($gcalid == $a->gcalid)
					{


						$text = limit_words( $a->subject , 8 ,'...' );
						$text = str_replace('"','&quot;' , $text );
						$text = stripslashes( $text );			
						$xml .="<event id='$a->id' start_date='$a->start_date $a->start_time' end_date='$a->end_date $a->end_time' text=\"$text\" series_id='$a->series_id'  details='' />\n";		
					}
				}
				$i++;
			}
			
		}
		//exit;
			
		$database->setQuery( "SELECT TO_DAYS( '$start_date' ) " );
		$start_day_n 	=  $database->loadResult();		
		$database->setQuery( "SELECT TO_DAYS( '$end_date' ) " );
		$end_day_n 	=  $database->loadResult();	
		
		$s_array = array();
		
		foreach( $all_recurring_activities as $a ){
			$s_array[] = "'$a->series_id'";
		}
		
		$exp_array = activity_exceptions::getAllExceptionsBySeriesIds( $s_array , $start_day_n , $end_day_n  );
		
		foreach( $events as $event ){
		$gcalid	 =	$gcal->parseIdText($event->id->text); 
		
		foreach( $temp_recurring_activities as $a ){
			if($gcalid == $a->gcalid)
			{
				$flag = true;
					if( $a->repeats != 'none'){
							if($a->repeat_until=='0000-00-00')
							{
								$end_day_n = dateClass::getMYSQLToDaysByDate( $a->end_date );
							}
							else
							{
								
								$end_day_n = dateClass::getMYSQLToDaysByDate( $a->repeat_until );
								
							}
						
						
					}	
					$start_day 	= $start_day_n; 
					$end_day = $end_day_n;
												
									
									$start_date = $a->start_day_int;
									$i=0;
					switch($a->repeats)
					{
						case 'daily':
						$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
									for($i_todays; $i_todays<=$end_day; $i_todays++)
									{
										if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays, $exp_array[ $a->series_id ] ) ){
										continue;	
										}
										$flagCancelled = false;
										
											foreach($cancel_recurring_activities as $cancel_a)
											{
													$cancel_gcal_id = explode("_",$cancel_a->gcalid);
													$cancel_date 		= dateClass::getMYSQLToDaysByDate($cancel_a->start_date);
													if($cancel_gcal_id[0]==$a->gcalid)
													{
														$flag = false;
														if($i_todays == $cancel_date)
														{
															$flagCancelled = true;
															break;
														}
													}
												
											}
													
											
										if($flagCancelled == false && $flag == false)
										{
											$xml_recurring_activities[$a->id][$i]['start_day'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
											$xml_recurring_activities[$a->id][$i]['start_date'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['start_time'] = $a->start_time;
											$xml_recurring_activities[$a->id][$i]['end_time'] = $a->end_time;
											$text 	 = 	limit_words( $a->subject , 8 , '...' );
											$text 	= 	str_replace('"','&quot;' , $text );						
											$text 	= 	stripslashes( $text ).' ';
											$xml_recurring_activities[$a->id][$i]['text'] = $text;
											$i++;
																	
										}
										
									}
							break;
						case 'weekly':
							$weekdays_array = str_split( $a->repeat_weekdays );
							$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
							for($i_todays; $i_todays<=$end_day; $i_todays++)
									{
										if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays, $exp_array[ $a->series_id ] ) ){
										continue;	
										}
										$flagCancelled = false;
										
											foreach($cancel_recurring_activities as $cancel_a)
											{
													$cancel_gcal_id = explode("_",$cancel_a->gcalid);
													if($cancel_gcal_id[0]==$a->gcalid)
													{
														$cancel_date = dateClass::getMYSQLToDaysByDate($cancel_a->start_date);
														$flag = false;
														if($i_todays == $cancel_date)
														{
															$flagCancelled = true;
															break;
														}
													}
												
											}
										
										
											
										if($flagCancelled == false && $flag == false)
										{
											$r_sdate = 	dateClass::getDateByMYSQLToDays( $i_todays );
											$weekday = date( 'N' , strtotime( $r_sdate ) );
											if( in_array( $weekday ,  $weekdays_array ) ){
											$xml_recurring_activities[$a->id][$i]['start_day'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
											$xml_recurring_activities[$a->id][$i]['start_date'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['start_time'] = $a->start_time;
											$xml_recurring_activities[$a->id][$i]['end_time'] = $a->end_time;
											$text 	 = 	limit_words( $a->subject , 8 , '...' );
											$text 	= 	str_replace('"','&quot;' , $text );						
											$text 	= 	stripslashes( $text ).' ';
											$xml_recurring_activities[$a->id][$i]['text'] = $text;
											$i++;
											}						
										}
										
									}
							break;
						case 'monthly':
						$event_new = array();	
						$repeat_by_month = $a->repeats_every;					
						$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
						$ostart_day = date( 'j' , strtotime( $a->start_date ) );
							
						if( $repeat_by_month == 'month_day' ){
							
							for($i_todays; $i_todays<=$end_day; $i_todays++)
									{
										if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays, $exp_array[ $a->series_id ] ) ){
										continue;	
										}
										$flagCancelled = false;
										
											foreach($cancel_recurring_activities as $cancel_a)
											{
													$cancel_gcal_id = explode("_",$cancel_a->gcalid);
													$cancel_date = dateClass::getMYSQLToDaysByDate($cancel_a->start_date);
													if($cancel_gcal_id[0]==$a->gcalid)
													{
														$flag = false;
														if($i_todays == $cancel_date)
														{
															$flagCancelled = true;
															break;
														}
													}
												
											}
									if($flagCancelled == false && $flag == false)
										{
											$month_day = date( 'j' , strtotime( dateClass::getDateByMYSQLToDays( $i_todays ) ) );
											if( $repeat_by_month == 'month_day' && $month_day == $ostart_day ){
											$r_sdate = 	dateClass::getDateByMYSQLToDays( $i_todays );
											
											$xml_recurring_activities[$a->id][$i]['start_day'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
											$xml_recurring_activities[$a->id][$i]['start_date'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['start_time'] = $a->start_time;
											$xml_recurring_activities[$a->id][$i]['end_time'] = $a->end_time;
											$text 	 = 	limit_words( $a->subject , 8 , '...' );
											$text 	= 	str_replace('"','&quot;' , $text );						
											$text 	= 	stripslashes( $text ).' ';
											$xml_recurring_activities[$a->id][$i]['text'] = $text;
											$i++;
											}						
										}
									}
										
						}
										
						if( $repeat_by_month == 'repeat_by_week_day' ){
						$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date); 						
						$i_week_day 	= dateClass::getDayOfTheWeekByMYSQLToDays( $i_todays );						 
						$i_month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays( $i_todays ) / 7 );
							for($i_todays; $i_todays<=$end_day; $i_todays++)
									{
										if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays, $exp_array[ $a->series_id ] ) ){
										continue;	
										}
										$flagCancelled = false;
										
											foreach($cancel_recurring_activities as $cancel_a)
											{
													$cancel_gcal_id = explode("_",$cancel_a->gcalid);
													if($cancel_gcal_id[0]==$a->gcalid)
													{
														$flag = false;
														$cancel_date = dateClass::getMYSQLToDaysByDate($cancel_a->start_date);
														//echo "$i_todays == $cancel_a->start_day_int";
														if($i_todays == $cancel_date)
														{
															$flagCancelled = true;
															break;
															
														}
													}
												
											}
											
									if($flagCancelled == false && $flag == false)
										{
											$month_day = date( 'j' , strtotime( dateClass::getDateByMYSQLToDays( $i_todays ) ) );
											$week_day	= dateClass::getDayOfTheWeekByMYSQLToDays( $i_todays );							
											$month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays( $i_todays ) / 7 );
											 
											if( $repeat_by_month == 'repeat_by_week_day'  && $i_week_day == $week_day && $i_month_week == $month_week ){
											$r_sdate = 	dateClass::getDateByMYSQLToDays( $i_todays );
											$xml_recurring_activities[$a->id][$i]['start_day'] = $start_date;
											$xml_recurring_activities[$a->id][$i]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
											$xml_recurring_activities[$a->id][$i]['start_date'] = $i_todays;
											$xml_recurring_activities[$a->id][$i]['start_time'] = $a->start_time;
											$xml_recurring_activities[$a->id][$i]['end_time'] = $a->end_time;
											$text 	 = 	limit_words( $a->subject , 8 , '...' );
											$text 	= 	str_replace('"','&quot;' , $text );						
											$text 	= 	stripslashes( $text ).' ';
											$xml_recurring_activities[$a->id][$i]['text'] = $text;
											
											}					
										}
										$i++;
									}
								}
					break;
					}
					
						
																
						
			if($flag)
			{
				$k=0;
				switch($a->repeats)
				{
				case 'daily':
				$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
					for($i_todays; $i_todays<=$end_day; $i_todays++)
					{
						if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays , $exp_array[ $a->series_id ] ) ){
										continue;	
									}
						else
						{
							$flagEdited = false;
										
											foreach($activities as $act)
											{
													$edited_gcal_id = explode("_",$act->gcalid);
													if($edited_gcal_id[0]==$a->gcalid)
													{
														$flag = false;
														$edited_date = dateClass::getMYSQLToDaysByDate($act->start_date);
														if($i_todays == $edited_date)
														{
															$flagEdited = true;
															break;
															
														}
													}
												
											}
											
						if($flagEdited == false)
						{
							$xml_recurring_activities[$a->id][$k]['start_day'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
							//$start_date = dateClass::getDateByMYSQLToDays($a->start_day_int);
							$xml_recurring_activities[$a->id][$k]['start_date'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['start_time'] = $a->start_time;
							$xml_recurring_activities[$a->id][$k]['end_time'] = $a->end_time;
							$text 	 = 	limit_words( $a->subject , 8 , '...' );
							$text 	= 	str_replace('"','&quot;' , $text );						
							$text 	= 	stripslashes( $text ).' ';
							$xml_recurring_activities[$a->id][$k]['text'] = $text;
							$k++;
						}
						}
					}
				break;
				case 'weekly':

				$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
				$weekdays_array = str_split( $a->repeat_weekdays );
				for($i_todays; $i_todays<=$end_day; $i_todays++)
					{
						if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays , $exp_array[ $a->series_id ] ) ){
										continue;	
									}
						else
						{
						$r_sdate = 	dateClass::getDateByMYSQLToDays( $i_todays );
						$weekday = date( 'N' , strtotime( $r_sdate ) );
						if( in_array( $weekday ,  $weekdays_array ) ){
							$xml_recurring_activities[$a->id][$k]['start_day'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
							//$start_date = dateClass::getDateByMYSQLToDays($a->start_day_int);
							$xml_recurring_activities[$a->id][$k]['start_date'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['start_time'] = $a->start_time;
							$xml_recurring_activities[$a->id][$k]['end_time'] = $a->end_time;
							$text 	 = 	limit_words( $a->subject , 8 , '...' );
							$text 	= 	str_replace('"','&quot;' , $text );						
							$text 	= 	stripslashes( $text ).' ';
							$xml_recurring_activities[$a->id][$k]['text'] = $text;
							$k++;
						}
					}	
					}
				break;
				case 'monthly':
				$event_new = array();	
				$repeat_by_month = $a->repeats_every;					
				
				if( $repeat_by_month == 'month_day' ){
				$ostart_month = date( 'n' , strtotime(dateClass::getDateByMYSQLToDays($start_date)));
				
				$end_month = date( 'n' ,strtotime(dateClass::getDateByMYSQLToDays($end_day)));
				$ostart_day = date( 'j' , strtotime($a->start_date));	
				$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date);
					for($i_todays; $i_todays<=$end_day; $i_todays++)
						{
							
							if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays , $exp_array[ $a->series_id ] ) ){
											continue;	
										}
							else
							{
							
							$month_day = date( 'j' , strtotime( dateClass::getDateByMYSQLToDays( $i_todays ) ) );
							
								if( $repeat_by_month == 'month_day' && $month_day == $ostart_day ){
									
									$xml_recurring_activities[$a->id][$k]['start_day'] = $i_todays;
									$xml_recurring_activities[$a->id][$k]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
									$xml_recurring_activities[$a->id][$k]['start_date'] = $i_todays;
									$xml_recurring_activities[$a->id][$k]['start_time'] = $a->start_time;
									$xml_recurring_activities[$a->id][$k]['end_time'] = $a->end_time;
									$text 	 = 	limit_words( $a->subject , 8 , '...' );
									$text 	= 	str_replace('"','&quot;' , $text );						
									$text 	= 	stripslashes( $text ).' ';
									$xml_recurring_activities[$a->id][$k]['text'] = $text;
									$k++;
							}	
						}
						
						}	
				}
				if( $repeat_by_month == 'repeat_by_week_day' ){
				$i_todays 		= dateClass::getMYSQLToDaysByDate($a->start_date); 						
				$i_week_day 	= dateClass::getDayOfTheWeekByMYSQLToDays($i_todays);						 
				$i_month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays($i_todays) / 7 );
				 
				
				for($i_todays; $i_todays<=$end_day; $i_todays++)
				{
					if( isset( $exp_array[ $a->series_id ] ) && in_array(  $i_todays , $exp_array[ $a->series_id ] ) ){
									continue;	
								}
					else
					{
					 $month_day = date( 'j' , strtotime( dateClass::getDateByMYSQLToDays( $i_todays ) ) );
					$week_day	= dateClass::getDayOfTheWeekByMYSQLToDays( $i_todays );							
					$month_week 	= ceil( dateClass::getDayOfTheMonthByMYSQLToDays( $i_todays ) / 7 );
					if( $repeat_by_month == 'repeat_by_week_day'  && $i_week_day == $week_day && $i_month_week == $month_week ){
							$xml_recurring_activities[$a->id][$k]['start_day'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['series_id'] = 'series_'.$a->series_id.'_'.$i_todays;
							$start_date = dateClass::getDateByMYSQLToDays($a->start_day_int);
							$xml_recurring_activities[$a->id][$k]['start_date'] = $i_todays;
							$xml_recurring_activities[$a->id][$k]['start_time'] = $a->start_time;
							$xml_recurring_activities[$a->id][$k]['end_time'] = $a->end_time;
							$text 	 = 	limit_words( $a->subject , 8 , '...' );
							$text 	= 	str_replace('"','&quot;' , $text );						
							$text 	= 	stripslashes( $text ).' ';
							$xml_recurring_activities[$a->id][$k]['text'] = $text;
							
						}
						$k++;
				} 
				}
						
				}      	
				break;
			}
				
			}
		}
	}
}	
		if(is_array($xml_recurring_activities))
		{
		foreach($xml_recurring_activities as $key =>$rec_val)
			{
				foreach($rec_val as $k=>$val)
				{
				$e_id = $val['series_id'];
				$start_date = dateClass::getDateByMYSQLToDays($val['start_date']);
				$r_sdate = $start_date.' '.$val['start_time'];
				$end_date = $start_date.' '.$val['end_time'];
				$text = $val['text'];
				$xml 	.=	"<event id='".$e_id."' start_date='$r_sdate' end_date='$end_date' text=\"$text\" details='' />\n";
				}								
				
												
			}
		}
	
	
	
		$xml .= "</data>";
		return $xml;
	}
		
	
	
	public static function getActivitiesByDateRange( $users , $start_date , $end_date , $options = array() ){
     			
		//$activities = activitiesv2::getActivitiesByDateRange($users, $start_date, $end_date, $options);
							
		return $activities;
    }
    
    
	public static function timeList( $name ='start_time' , $value = 6 , $id = NULL ){
		
		$ampm	=	'am';
		for( $i = 360; $i < ( 23 * 60 ) ; $i = $i+15 ){
			
			$hr				=	$i;
			$hr				=	$hr<10?'0'.$hr:$hr;		
			$hrText			=	$i>12?$i-12:$i;			
	
			if( $i > 11 ){
				$ampm		=	'pm';
			}
			
			$timeText 	= activitiesv2::convertMinuteToHr( $i );				 			
			$timeArr[]	= mosHTML::makeOption( $i , $timeText );
						
		}	
		
		$id = $id ? $id : $name; 
		return mosHTML::selectList( $timeArr, $name , 'id="'.$id.'" class="'.$name.'" ','value','text', $value );
			
	}
	
	public static function selectRepeatEvery(){
		
	}
	
	public static function selectRepeats(){
			
			$repeatList[]	=	mosHTML::makeOption('none','Does not repeat');
			$repeatList[]	=	mosHTML::makeOption('daily','Daily');
			$repeatList[]	=	mosHTML::makeOption('weekly','Weekly');
			$repeatList[]	=	mosHTML::makeOption('monthly','Monthly');
			$repeatList[]	=	mosHTML::makeOption('yearly','Yearly');
			
			return mosHTML::selectList( $repeatList , 'repeats' , 'id="repeats"', 'value','text', '' );
			
	}
	
	public static function selectNotificationTime(){
						
			$nList[]	=	mosHTML::makeOption('none','Do not notify');
			$nList[]	=	mosHTML::makeOption('minutes','Minutes');
			$nList[]	=	mosHTML::makeOption('hours','Hours');
			$nList[]	=	mosHTML::makeOption('Days','Days');			
			
			return mosHTML::selectList( $nList , 'notify_period[]' , 'id="notify_period"', 'value','text', 'minutes' );
			
	}
		
	public static function deleteGCal( $gcalid ){

		global $database, $my , $permissions;
		
			loadClass( 'gcal' );
			
			if( $permissions->google_username && $permissions->google_password ){
				
						try
						{
							$gcal	=	new gcal($permissions->google_username,$permissions->google_password);
								
						}catch( Exception $e ){
							throw new Exception( $e->getMessage() );	
						}
						$date_start = '';
			$date_to = '';
			try{
				$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
				
				
			}catch(Exception $e){
				$return['result'] = 'gcal failed';
				$return['error'] = $e->getMessage();					
				echo json_encode( $return );
				exit();			
			}
			
			foreach($events as $event)
			{
				if(!empty($event->id->text))
				{
					$eid = $gcal->parseIdText($event->id->text);
					if($eid == $gcalid)
					{
						
							$event->delete();
					}
				}
						
						
													
			}
			
	}
}
public static function editGCalActivity( $activity ){
		global $database, $my , $permissions;
		
		loadClass( 'gcal' );
		
				try{
					$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
					
					
					
				}catch(Exception $e){
					$return['gcal_result'] = 'gcal failed';
					$return['gcal_error'] = $e->getMessage();					
					echo json_encode( $return );
					exit();			
				}
					
				
				if( ! $gcal->client ){
					$return['gcal_result'] = 'gcal failed';
					$return['gcal_error'] = ' Your Google account is invalid. Please check Google username and password on My Account Settings';					
					echo json_encode( $return );
					exit();							
				}
				
				$event = $gcal->getEvent( $activity->gcalid );
				$gcal->createActivity( $activity , '' , $event );
				
				$return['gcal_details'] = $event;
		
	}
		
	public static function convertMinuteToHr( $min ){
		
		$hr 	= 	floor( $min / 60 );
		$minute =  	$min % 60;
		$ampm	=  	$hr > 11 ? 'pm' : 'am';
		
		$hr = $hr > 12 ? $hr - 12: $hr;
		$hr = str_pad( $hr, 2, '0' , STR_PAD_LEFT); 
		$minute = str_pad( $minute, 2, '0' , STR_PAD_LEFT);
		
		return $hr.':'.$minute.' '.$ampm;
		
	}
/*************Added By Developer******************/
  public static function deleteRecurringGcal($gcalid,$remove_date_time,$str_date_time,$end_date_time,$repeats,$activity)
{
		
		//echo "<pre>";print_r($activity);echo "</pre>";exit;
		//echo $end_date_time; exit;
		global $database , $permissions , $my ;

		loadClass( 'gcal' );
		loadClass( 'date' );
		try{
		$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
		
		
	}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
	$date_start = '';
	$date_to = '';
	try{
		$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
	}catch(Exception $e){
		$return['result'] = 'gcal failed';
		$return['error'] = $e->getMessage();					
		echo json_encode( $return );
		exit();			
	}
	foreach($events as $event)
	{
		if(!empty($event->id->text))
		{
		$eid = $gcal->parseIdText($event->id->text);
		if($eid == $gcalid)
		{
			switch($repeats)
			{
				case 'DAILY':
				$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
							"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
							"RRULE:FREQ=".$repeats.";UNTIL=".$end_date_time."\r\n";
							
					break;
				
				case 'WEEKLY':
				$required_day = array();	
				$days_alpha = array('1'=>'MO','2'=>'TU','3'=>'WE','4'=>'TH','5'=>'FR','6'=>'SA','7'=>'SU');
				$r_days = (string)$activity->repeat_weekdays;
				$repeated_days = strlen($r_days);
				for($i=0;$i<$repeated_days;$i++)
				{
					$required_day[] = $days_alpha[$r_days[$i]];
					
				}
				
				$days = implode(",",$required_day);
				$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
							"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
							"RRULE:FREQ=".$repeats.";WKST=SU;BYDAY=".$days.";UNTIL=".$end_date_time."\r\n";
				break;
	
			case 'MONTHLY':
				if($activity->repeats_every == 'month_day')
				{
					$string_length = strlen($activity->monthly_repeat_weekday);
					if($string_length>3)
					{
						$day = substr($activity->monthly_repeat_weekday,0,2);
					}
					else
					{
						$day = substr($activity->monthly_repeat_weekday,0,1);
					}
					
					$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
									"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
									"RRULE:FREQ=".$repeats.";BYMONTHDAY=".$day.";UNTIL=".$end_date_time."\r\n";
					
				}
				else
				{
						$bymonth_day = explode(" ",$activity->monthly_repeat_weekday);
						
						if($bymonth_day[0]!='last')
						{
							if($bymonth_day[0]=='First')
							{
								$num= 1;
							}
							if($bymonth_day[0]=='Second')
							{
								$num= 2;
							}
							if($bymonth_day[0]=='Third')
							{
								$num= 3;
							}
							if($bymonth_day[0]=='Fourth')
							{
								$num= 4;
							}
					if($bymonth_day[1]=='Monday')
						{
							$re_day = 'MO';
						}
						if($bymonth_day[1]=='Tuesday')
						{
							$re_day = 'TU';
						}
						if($bymonth_day[1]=='Wednesday')
						{
							$re_day = 'WE';
						}
						if($bymonth_day[1]=='Thursday')
						{
							$re_day = 'TH';
						}
						if($bymonth_day[1]=='Friday')
						{
							$re_day = 'FR';
						}
						if($bymonth_day[1]=='Saturday')
						{
							$re_day = 'SA';
						}
						if($bymonth_day[1]=='Sunday')
						{
							$re_day = 'SU';
						}
						
					$repeat_day = $num.$re_day;
					
					}
					else
					{
						if($bymonth_day[1]=='Monday')
						{
							$re_day = '-2MO';
						}
						if($bymonth_day[1]=='Tuesday')
						{
							$re_day = '-3TU';
						}
						if($bymonth_day[1]=='Wednesday')
						{
							$re_day = '=-4WE';
						}
						if($bymonth_day[1]=='Thursday')
						{
							$re_day = '-5TH';
						}
						if($bymonth_day[1]=='Friday')
						{
							$re_day = '-6FR';
						}
						if($bymonth_day[1]=='Saturday')
						{
							$re_day = '-7SA';
						}
						if($bymonth_day[1]=='Sunday')
						{
							$re_day = '-1SU';
						}
						$repeat_day = $num.$re_day;
					}
					$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
									"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
							"RRULE:FREQ=".$repeats.";BYDAY=".$repeat_day.";UNTIL=".$end_date_time."\r\n";
				}
				
			break;
			case 'YEARLY':
			$com_month = $activity->yearly_month_value;
			$com_day = $activity->yearly_day_value;
			$month = array('1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December');
			foreach($month as $key=>$val)
			{
				if($val == $com_month)
				{
					$month_value = $key;
				}
			}
			$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" .
							"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
						"RRULE:FREQ=".$repeats.";BYYEARDAY=".$com_day.";BYMONTH=".$month_value.";UNTIL=".$end_date_time."\r\n";
				
			break;
			
		}
			/*$recurrence = "DTSTART;TZID=America/Whitehorse:".$start_date."\r\n" . 
							"EXDATE;TZID=America/Whitehorse:".$remove_date_time."\r\n".
							"RRULE:FREQ=".$repeats.";UNTIL=".$end_date."\r\n";*/
			
			$event->recurrence = $gcal->service->newRecurrence($recurrence);
			$event->save();
		}
		
	}
	}
	
}

public static function saveRecurringToGCal($new_activity){
//echo "<pre>";print_r($new_activity);echo "</pre>";exit;
			global $database , $permissions;
			loadClass( 'gcal' );
						
			$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
			$service = $gcal->service;
				
			$date_start = '';
			$date_to = '';
			try{
				$events  		= 	$gcal->getEventsByDateRange( $date_start, $date_to );
				
				
			}catch(Exception $e){
				$return['result'] = 'gcal failed';
				$return['error'] = $e->getMessage();					
				echo json_encode( $return );
				exit();			
			}
		foreach($events as $event)
			{			
				if(!empty($event->id->text))
				{
					$eid = $gcal->parseIdText($event->id->text);
					if($eid == $new_activity->gcalid)
					{
						$activity_delete = activitiesV2::deleteGCal($new_activity->gcalid);
						/************* Creation of new activity***/
						$new_event = $gcal->createactivity($new_activity,$notify_when = array() , $event =false);
						
					}
				}
			}
			return $new_event;
		}
			//echo "<pre>";print_r($event);echo "</pre>";
			//
	
public static function saveactivityToGCal($new_activity){
			global $database , $permissions;
			loadClass( 'gcal' );
						
			$gcal	=	new gcal( $permissions->google_username, $permissions->google_password );
			$service = $gcal->service;
					
					$new_event = $gcal->createactivity($new_activity,$notify_when = array() , $event =false);
					return $new_event;
			}
				
		
 }

?>