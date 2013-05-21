<?php

class gcal{
	
	var $eventId	=	null;
	var $service 	= 	null;
	var $client	 	=	null;
	var $username	=	null;
	var $password	=	null;
	var $feeds		=	null;
	private $current_event	=	null;
	
	function __construct($username, $password){
		global $mosConfig_absolute_path;
			set_include_path($mosConfig_absolute_path.'/libraries/Gdata/library/');
  
			require_once($mosConfig_absolute_path.'/libraries/Gdata/library/Zend/Loader.php');
			$dir	=	$mosConfig_absolute_path.'/libraries/Gdata/library/';

			Zend_Loader::loadClass('Zend_Gdata');
			Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			Zend_Loader::loadClass('Zend_Gdata_Calendar');
			Zend_Loader::loadClass('Zend_Http_Client');
			
			$service 			= 	Zend_Gdata_Calendar::AUTH_SERVICE_NAME;		
			$this->username		=	$username;
			$this->password		=	$password;
			$this->client 		= 	Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, $service);
			$this->service 		= 	new Zend_Gdata_Calendar($this->client);
	}
	
	public function setFeeds(){
		try{
		    $this->feeds	= $this->service->getCalendarListFeed();
		}catch (Zend_Gdata_App_Exception $e){
		    	echo "Error: " . $e->getResponse();
		}				
	}
	
	public function getFeeds(){
		return $this->feeds;
	}

	/**
	 * creates/edit an activity
	 *
	 * @param object $activity
	 * @param array $notify_when
	 * @param object $event
	 * @return boolean
	 */
	public function createActivity( $activity , $notify_when = array() , $event =false){
			
	 global $mosConfig_absolute_path;
	global $permissions;
			/*$str = set_include_path($mosConfig_absolute_path.'/libraries/Gdata/library/');*/
			require_once($mosConfig_absolute_path.'/libraries/Gdata/library/Zend/Loader.php');
			require_once($mosConfig_absolute_path.'/libraries/Gdata/library/Zend/Gdata/Query.php');
			$dir	=	$mosConfig_absolute_path.'/libraries/Gdata/library/';

			Zend_Loader::loadClass('Zend_Gdata');
			Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			Zend_Loader::loadClass('Zend_Gdata_Calendar');
			Zend_Loader::loadClass('Zend_Http_Client');	
			$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
			$user =$permissions->google_username;
			$pass =$permissions->google_password;
			$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
			$service = new Zend_Gdata_Calendar($client);
			$event 	= $service->newEventEntry();
			
			/************Event INFO ***********/
			$title 			= 	$activity->subject; 
			  $desc			=	$activity->activity; 
			  $where 		= 	$activity->activity_where; 
			  $startDate 	=  $activity->start_date;
			  $startTime 	= 	$activity->start_time; 
			  $endDate 		= 	$activity->end_date; 
			  $endTime 		= 	$activity->end_time;
			  
			  
			  loadClass('date');
		if(dateClass::is_dst($startDate)){
	  		$permissions->timezone	+=	1; 
			}
			 $tzOffset 	= 	isset( $tzArray[$permissions->timezone] ) ? $tzArray[$permissions->timezone] : '-08' ;
	  $event->title = $service->newTitle(trim($title));
	  $event->where  = array($service->newWhere($where));
	  $event->content = $service->newContent($desc);
	  $event->content->type = 'text';
	   $when = $service->newWhen();
	   if(empty($activity->repeat_until))
	   {
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
			$end_date_time = date('Ymd',mktime(0,0,0,$end_arr[1],$end_arr[2]+1,$end_arr[0])).'T'.date('His',mktime($end_time_arr[0],$end_time_arr[1],$end_time_arr[2],0,0,0)).'Z';
			
			$event_end_arr = explode("-",$endDate);
			$end_time_arr = explode(":",$endTime);
			$event_end_date_time = date('Ymd',mktime(0,0,0,$event_end_arr[1],$event_end_arr[2],$event_end_arr[0])).'T'.date('His',mktime($end_time_arr[0],$end_time_arr[1],$end_time_arr[2],0,0,0)).'Z';		
	  switch($repeats)
		{
				case 'DAILY':
				$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" .
							  "DTEND;TZID=America/Whitehorse:".$event_end_date_time."\r\n" . 
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
				 			"DTEND;TZID=America/Whitehorse:".$event_end_date_time."\r\n" . 
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
						"DTEND;TZID=America/Whitehorse:".$event_end_date_time."\r\n" .
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
							"DTEND;TZID=America/Whitehorse:".$event_end_date_time."\r\n" .
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
						"DTEND;TZID=America/Whitehorse:".$event_end_date_time."\r\n" .
						"RRULE:FREQ=".$repeats.";BYYEARDAY=".$com_day.";BYMONTH=".$month_value.";UNTIL=".$end_date_time."\r\n";
				
			break;
		}

		/*$recurrence = "DTSTART;TZID=America/Whitehorse:".$str_date_time."\r\n" . 
				"RRULE:FREQ=".$repeats.";UNTIL=".$end_date_time."\r\n";*/

		$event->recurrence = $service->newRecurrence($recurrence);
		
	}
		
				
		$createdEvent	= $service->insertEvent( $event );	
		
		/* global $permissions;
	  
	  $tzArray	=	array(	  	
	  	'-12'=>'-12',
	  	'-11'=>'-11',	
	  	'-10'=>'-10',	
	  	'-9'=>'-08',
	  	'-8'=>'-08',
	  	'-7'=>'-07',
	  	'-6'=>'-06',
	  	'-5'=>'-05',
	  	'-4'=>'-04',
	  	'-3'=>'-03',
	  	'-2'=>'-02',
	  	'-1'=>'-01',
	  	'0'=>'00'	  		  	
	  );
	  
	  $title 			= 	$activity->subject; 
	  $desc			=	$activity->activity; 
	  $where 		= 	$activity->activity_where; 
	  $startDate 	=  $activity->start_date;
	  $startTime 	= 	$activity->start_time; 
	  $endDate 		= 	$activity->end_date; 
	  $endTime 		= 	$activity->end_time;

	loadClass('date');
	
	 if(dateClass::is_dst($startDate)){
	  		$permissions->timezone	+=	1; 
	  }
	  
	  $tzOffset 	= 	isset( $tzArray[$permissions->timezone] ) ? $tzArray[$permissions->timezone] : '-08' ;
	    
	  $gc 				= $this->service;
	  if( ! $event ){	  	
	  	$event 	= $gc->newEventEntry();
		//echo "<pre>";print_r($event);echo "</pre>";exit;
	  }
	  
	  $event->title 	= $gc->newTitle(trim($title));
	  $event->where  = array($gc->newWhere($where));
	  
	 
	  $event->content = $gc->newContent($desc);
	  $event->content->type = 'text';
	  
		/**		
			$startDate = "2012-10-21";
			$startTime = "14:00:00";
			$endDate = "2012-10-21";
			$endTime = "16:00:00";
			
			$tzOffset = "-08";
			*/
	 /* $when = $gc->newWhen();
			/*$when->startTime = "{$startDate}T{$startTime}.000-08:00";
			$when->endTime = "{$endDate}T{$endTime}.000-08:00";*/
		/*$when = $gc->newWhen();
		$when->startTime 	= "{$startDate}T{$startTime}.000{$tzOffset}:00";
		$when->endTime 	= "{$endDate}T{$endTime}.000{$tzOffset}:00";
		  	  	  	 
		$event->when 	= array($when);
	 if($activity->repeat_until)
	 {
	  		  	  
	  	$recurrence = "DTSTART;TZID=America/Whitehorse:20130114T180000\r\n" . 
						"DTEND;TZID=America/Whitehorse:20130114T190000\r\n" .
						"RRULE:FREQ=WEEKLY;BYDAY=MO\r\n" .
						"BEGIN:VTIMEZONE\r\n" .
						"TZID:America/Whitehorse\r\n" .
						"X-LIC-LOCATION:America/Whitehorse\r\n" .
						"BEGIN:STANDARD\r\n" .
						"TZOFFSETFROM:-0800\r\n" .
						"TZOFFSETTO:-0700\r\n" .
						"TZNAME:IST\r\n" .
						"DTSTART:19700101T000000\r\n" .
						"END:STANDARD\r\n" .
						"END:VTIMEZONE\r\n";

		$event->recurrence = $gc->newRecurrence($recurrence);
	  }
	  	  
	  /**
	   * Temporary code on trying to obtain eventid
	   * until I find a better solution	   
	   */
	  	  	  
	   /* if( $activity->gcalid ){
	    	
		    try{
				$createdEvent	= $event->save();	
				
			} catch (Zend_Gdata_App_Exception $e) {	
				logError('GCal failed to save ! '.$e->getMessage());								
			}												
			
		}else{	
			
	  		$createdEvent	= $gc->insertEvent( $event );	
				
		}*/	
		/**
		
	  */
	  /*
	  $href			=	$createdEvent->link[0]->href;	  
	  $lArr			=	explode('eid=',$href);
	  $lArr_		=	explode('&',$lArr[1]);
	  $eventid		=	$lArr_[0];
	  */		  
	  
	  $eventid	=	$this->parseIdText($createdEvent->id->text);
	  
	  $this->eventid	=	$eventid;
	  
	  
	  if(count($notify_when)){
	  	 
	  	  if($this->current_event	=	$createdEvent){	  	  	
	  	  	  //$this->setReminder($notify_when);				  					
	  	  }else{
	  	  	 logError('Event Notification failed!!!');	  
	  	  }
	  }	  
			
	  return $eventid;
}

	function getEventsByDateRange($startDate = 0, $endDate = 0){
	
	global $mosConfig_absolute_path;
	global $permissions;
	
					$str = set_include_path($mosConfig_absolute_path.'/libraries/Gdata/library/');
					//echo "jjj:".get_include_path();exit;
		  
					require_once($mosConfig_absolute_path.'/libraries/Gdata/library/Zend/Loader.php');
					require_once($mosConfig_absolute_path.'/libraries/Gdata/library/Zend/Gdata/Query.php');
					$dir	=	$mosConfig_absolute_path.'/libraries/Gdata/library/';
					Zend_Loader::loadClass('Zend_Gdata');
					Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
					Zend_Loader::loadClass('Zend_Gdata_Calendar');
					Zend_Loader::loadClass('Zend_Http_Client');	
		$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
		$user = $permissions->google_username;
		$pass = $permissions->google_password;
		$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
		$service = new Zend_Gdata_Calendar($client);
		
		$event_feed = new Zend_Gdata_Query();
		$query = $service->newEventQuery();
		$query->setUser('default');
		//$query->setStartMin("2006-01-01");
			
		//$startDate = "2013-12-10";
		//$endDate = "2014-01-30";
		
		$query->setStartMin($startDate);
		$query->setStartMax($endDate);
		
		//echo $startDate." ".$endDate;
		
		// Set to $query->setVisibility('private-magicCookieValue') if using
		// MagicCookie auth
		$query->setVisibility('private');
		$query->setProjection('full');
		$query->setOrderby('starttime');
		/*$query->setFutureevents('true');*/
		$query->setMaxResults(200);


			/*$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
			$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
			$service = new Zend_Gdata_Calendar($client);*/
			 
			$query->setsingleevents(TRUE);
			
			
			try {
			$eventFeed = $service->getCalendarEventFeed($query);
			//$eventFeed = $service->getCalendarEventFeed();

			
		} catch (Zend_Gdata_App_Exception $e) {
			echo "Error: " . $e->getMessage();
		}
		/*
		echo "<pre>";
		print_r($eventFeed);
		echo "</pre>";
		*/
		
		/*
		echo "<ul>";
		foreach($eventFeed as $event)
		{
			echo "<li>". $event->title->text." -----".$event->when[0]->startTime." -----".$event->when[0]->endTime."</li>";
		}
		echo "</ul>";
		*/
		
		
		
		
		
		
		
// Iterate through the list of events, outputting them as an HTML list
		return $eventFeed;
	}  

	function getEvent($eventId = null){
	
	  $eventId		=	$eventId ? $eventId : $this->eventId;
	  //$gdataCal 	= 	new Zend_Gdata_Calendar( $this->client );
	  $query 	= 	$this->service->newEventQuery();
	  $query->setUser('default');
	  $query->setVisibility('private');
	  $query->setProjection('full');
	  $query->setEvent($eventId);
		
	  try{	    
	  	return $this->service->getCalendarEventEntry($query);	    	    
	  		    
	  } catch (Zend_Gdata_App_Exception $e){
	    //var_dump($e);
	    return null;
	  }
	  
	}
	
	function parseIdText(&$text){
		
		 $sectArr		=	explode('/',$text);
	  	 $cnt			=	count($sectArr)-1;
	  	 $eventid		=	$sectArr[$cnt];
	  	 return	$eventid; 	
	}
	
	function setReminder($minutesArr	=	array()){
	  $eventId	=	$this->eventId;	
	  $gc 		= 	$this->service;
	  $method 	= 	"email";
	  $event	=	$this->current_event;
	  
	  if(!count($minutesArr)){
	  		logError('Reminder does not have any time set!');
	  		return false;
	  }
	  
	  if($event){
	  	
	    $times 			= 	$event->when;
	    $x				=	0;
	    $reminderArr	=	array();    
	    
	    if( is_array( $times ) ){
		    foreach( $times as $when ){
		    	
		    	foreach($minutesArr as $minutes){    	  			    			    				
		    			$reminder 		= 	$gc->newReminder();
		        		$reminder->setMinutes($minutes);
		        		$reminder->setMethod($method);        		        		        			    	
		    	}
		        
		    	$when->reminders	= array($reminder);	                
		    }
	    }  
	            
	    $eventNew = $event->save();    
	        
	    return $eventNew;
	    
	  } else {
	    return false;
	  }
	}
	
	public function delete( $gcalid ){
		
		if( $this->service ){
			
			$query = $this->service->newEventQuery();
			$query->setUser('default');
			$query->setVisibility('private');
			$query->setProjection('full');
			$query->setEvent( $gcalid );
			
		try{
	    	$event = $this->service->getCalendarEventEntry($query);
			$event->delete();
			
		  } catch (Zend_Gdata_App_Exception $e) {		   
		    
		  	return null;
		  	
		  }
			
		}	
		
	}
	
		
		
	
}	


	