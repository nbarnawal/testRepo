<?php
/**
 * used by SyncGCal on calendar
 *
 */

class dateClass{
	
	public $day 		= 	null;
	public $month 		= 	null;
	public $year 		= 	null;		
	public $hr 			= 	null;
	public $min 		= 	null;
	public $sec 		= 	null;
	public $date		=	null;
	public $time		=	null;
	public $is_date		=	true;
	private $timestamp	=	null;
	private $timezone	=	'-8'; // Pacific standard time
	
	/****** @param date $date mysql date format yyyy-mm-dd hh:ii:ss *******/
	
	public function dateClass($date =  '0000-00-00 00:00:00' , $format = '0'){
		 
		if(!$date || $date =='0000-00-00' || $date == '0000-00-00 00:00:00'){
			
			$this->is_date	=	false;
			
		} else {	
						
			switch( $format ){
				
				/**** default format yyyy-mm-dd hh:ii:ss ****/
				
				case '0':				
					$dArr			=	explode( ' ', $date );
					$dayArr			=	explode( '-', $dArr[0] );					
					$tArr			=	explode( ':', $dArr[1] );
					
					$this->day		=	$dayArr[2];
					$this->month	=	$dayArr[1];		
					$this->year		=	$dayArr[0];
					$this->hr		=	$tArr[0] ? $tArr[0]:0;
					$this->min		=	$tArr[1] ? $tArr[1]:0;
					$this->sec		=	$tArr[2] ? $tArr[2]:0;
					
				break;
				/**
				 * GCalendar Format of yyyy-mm-ddThh:ii:sss.000Timezone
				 */	
				case '1':
					$dArr			=	explode('T',$date);
					$dayArr			=	explode('-',$dArr[0]);
					$tArr			=	explode('.',$dArr[1]);
					$tArr_			=	explode(':',$tArr[0]);
					
					$this->day		=	$dayArr[2];
					$this->month	=	$dayArr[1];		
					$this->year		=	$dayArr[0];
					$this->date		=	$dArr[0];
											
					$this->time		=	$tArr[0];
					$this->hr		=	$tArr_[0]? $tArr_[0]:0;	
					$this->min		=	$tArr_[1]? $tArr_[1]:0;
					$this->sec		=	$tArr_[2]? $tArr_[2]:0;
										
				break;	
			};			
			$this->timestamp  = mktime($this->hr,$this->min,$this->sec,intval($this->month), intval($this->day), intval($this->year));
		}	
	}
		
	/**
	 * Enter description here...
	 *
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ){
		if((int) $timestamp ){
			$this->timestamp	=	intval($timestamp);
		}
		return $this;	
	}
	
	public function getTimestamp(){
		return $this->timestamp;	
	}
	public function getDateTime(){
		return date('Y-m-d H:i:s',$this->timestamp);	
	}
	public function year(){
		return date('Y',$this->timestamp);
	}
	public function month(){
		return date('m',$this->timestamp);
	}
	public function day(){
		return date('d',$this->timestamp);
	}
	public function hour(){
		return date('H',$this->timestamp);
	}
	public function minute(){
		return date('m',$this->timestamp);
	}
	public function seconds(){
		return date('s',$this->timestamp);
	}
	/**
	 * 
	 * @param int $diff
	 * @param boolean $has_time
	 * @return date
	 */
	public function getDateByDayDiff( $diff , $has_time = false){
		if(!$diff){
			if($has_time){
				return date('Y-m-d H:i:s');
			}else{
				return date('Y-m-d');	
			}
		}
		
		$timestamp		=	mktime($this->hr,$this->min,$this->sec,$this->month,$this->day+$diff,$this->year);	
		if($has_time){
			return date('Y-m-d H:i:s',  $timestamp );
		}else{
			return	date('Y-m-d', $timestamp );
		}		 
	}
	
	public function addDays($days){
		$date	=	date('Y-m-d',mktime(0,0,0,$this->month,$this->day+$days,$this->year));
		return $date;	
	}
	
	/**
	 * Time adjustments for timezone support
	 *
	 * @param datetime $date
	 * @param int $timezone
	 * @param array()
	 * @return datetime
	 */
	//public static function adjustedTimezone( $date , $timezone , $to_server_time = false , $return_timestamp = false , $format = 0){
	public static function adjustedTimezone( $date , $timezone , 
		$options 	= array( 'return_timestamp' => false,
			'format'=> 0,
			'to_server_time' => false
		)
	){
		global $mosConfig_server_timezone;
		
		if(!$date){
			$date   =	date('Y-m-d H:i:s');			
		}
			
		if( $timezone == $mosConfig_server_timezone ){			
				return $date;
		}
		
		$time_diff			=	intval( $timezone )	 -	intval( $mosConfig_server_timezone );
		$dateClass 			= 	new dateClass( $date );		

		$f	=	'Y-m-d H:i:s';
		
		switch( $options['format'] ){
			case 0:
				$f	=	'Y-m-d H:i:s';	
			break;
			case 1:
				$f	=	'Y-m-d';
			break;		
			case 2:
				$f	=	'H:i:s';
			break;
			default:
				$f	=	'Y-m-d H:i:s';
			break;	
		}
		
		if( $options['to_server_time'] ){
			
			$ret	=	mktime( $dateClass->hr - $time_diff, $dateClass->min, $dateClass->sec, $dateClass->month, $dateClass->day, $dateClass->year);			
			if( !$options['return_timestamp'] ){
				$ret 		= 	date($f, $ret );		
			}			
						
		}else{
			$ret 		= 	mktime( $dateClass->hr + $time_diff, $dateClass->min, $dateClass->sec, $dateClass->month, $dateClass->day, $dateClass->year);
			
			if( !$options['return_timestamp'] ){
				$ret 		= 	date($f, $ret );
			}		
		}
		
		return	$ret;  
	}

	public static function toServerTime( $date , $timezone ){
		
	}
	
	public static function timezone_convert( $val ){
		switch( $val ){
				case -11:
					$t	=	'Hawaii';
				break;
				case -9:
					$t	=	'Alaska';
				break;
				case -8:
					$t	=	'Pacific';
				break;
				case -7:
					$t	=	'Mountain';
				break;	
				case -6:
					$t	=	'Central';
				break;
				case -5:
					$t	=	'Eastern';
				break;
				default;
					$t	=	$val;
				break;			
			}
			return $t;
	}
	
	public static function timezone_select( $default = '-8'){
		
		for($i = 0 ; $i<13 ; $i++ ){
			if($i){
				$tzArr[]	=	mosHTML::makeOption("+$i","+$i");
			}else{
				$tzArr[]	=	mosHTML::makeOption("$i","$i");	
			}
		}
		
		for($i = 1 ; $i<13 ; $i++ ){	
			$t			=	dateClass::timezone_convert( -$i );
			$tzArr[]	=	mosHTML::makeOption("-$i","$t");			
		}
		
		return  mosHTML::selectList($tzArr,'timezone','','value','text',$default );
	}
	
	public function timestamp(){		
		return $this->timestamp;		
	}
	/**
	 * Used for checking DST
	 *
	 */
	public static function getSecondSunday($year, $month){
		
		$firstday		=	date('N',mktime(0,0,0,$month,1,$year));
		return 15 - $firstday;
	}
	/**
	 * used for checking DST
	 *
	 */
	public static function getFirstSunday($year, $month){		
		$firstday		=	date('N',mktime(0,0,0,$month,1,$year));
		return 8 - $firstday;
	}
	/**
	 * checks if date is DST or not 
	 *
	 * @return boolean
	 */
	public static function is_dst($date){
		global $broker;				
		$dateObj		=	new dateClass($date);
		$months_no_dst 	= 	array(12,1,2);
		$months_dst 	= 	array(4,5,6,7,8,9,10);
		
		if($broker->state =='AZ' || $broker->state =='HI'){
			return false;
		}
		/** if months are between Dec and Feb there is no dst **/
		if(in_array($dateObj->month,$months_no_dst)){
			return false;	
		}
		
		/** if months are between March and Nov return true **/		
		if(in_array($dateObj->month,$months_dst)){
			return true;	
		}
		
		if($dateObj->month==3){
			if($dateObj->day>14){
				return true;
			}else{
				/** get second sunday **/
				$second_sunday 	= dateClass::getSecondSunday($dateObj->year,$dateObj->month);
				if($dateObj->day >= $second_sunday ){
					return true;
				}else{
					return false;
				}
			}			
		}
		
		if($dateObj->month==11){
			if($dateObj->day>7){
				return false;
			}else{
				/** get first sunday **/
				$first_sunday 	  = dateClass::getFirstSunday($dateObj->year,$dateObj->month);
				if($dateObj->day < $first_sunday ){
					return true;
				}else{
					return false;
				}
			}
		}									
	}
		
	public function getWeekDay($date){
		$dateObj	=	new dateClass($date);		
		$ret	=	date('N',mktime(0,0,0,$dateObj->month,$dateObj->day,$dateObj->year));
		return $ret;
	}
	
	public function to_days($date){
		global $database;
		
		$database->setQuery("SELECT TO_DAYS('$date')");
		return $database->loadResult();		
	}
	/**
	 * returns the number of days on a given month...
	 *
	 *  @return int
	 */
	function monthDayCount($date){
		$dateObj	=	new dateClass($date);		
		$ret		=	date('t',mktime(0,0,0,$dateObj->month,$dateObj->day,$dateObj->year));
		return $ret;		
	}
	/**	  
	 *
	 * @param unknown_type $var
	 * @param unknown_type $value
	 */
	 
	function set($var , $value){
		$this->$var = $value;	
	}
	
	function get($var){
		return $this->$var;
	}
	
	/**
	 * Compares the month and day of the current month and day
	 * used in editaction marketing tab
	 * @return boolean false when param month and day is less than $this->month and $this->day 
	 */
	function monthDayCompare( $now	=	false ){
		if( !$now ){
			$now	=		date('Y-m-d');
		}
		
		$nowObj		=	new dateClass( $now );
		
		if(	$this->month < $nowObj->month ){
			return	false;
		}
		
		if( $this->day < $nowObj->day && $this->month == $nowObj->month ){
			return false;
		}
		
		return true;
				
	}
	/**
	 * Get difference of days given two dates	 
	 *
	 * @param date $start_date
	 * @param date_type $end_date
	 * @return int
	 */
	
	function dayDifference($start_date , $end_date){
		global $database;
						
		if(!preg_match('/^\d{4}-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1])$/',$start_date)){		
			return false;
		}
		
		if(!preg_match('/^\d{4}-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1])$/',$end_date)){			
			return false;
		}
		
		$database->setQuery("Select TO_DAYS('$end_date')");				
		$dEnd		=	$database->loadResult();
		
		$database->setQuery("Select TO_DAYS('$start_date')");				
		$dStart		=	$database->loadResult();

		if($dStart > $dEnd){
			return false;
		}
		
		return $dEnd - $dStart;
	}
	/**
	 * static version of to_days($date)	
	 * Enter description here ...
	 * @param unknown_type $date
	 */
	public static function getDayInt( $date ){
		global $database;
		
		$database->setQuery("SELECT TO_DAYS('$date')");
		return $database->loadResult();		
	}
	/**
	 * Get the number of months difference between two dates. 
	 * Used by Newkey MLM codes
	 *
	 * @param date $date1  // must be the older date
	 * @param date $date2
	 */
	public static function getMonthsCount( $date1 , $date2 ){
		
		$date1	=	new dateClass( $date1 );
		$date2	=	new dateClass( $date2 );
		
		$months		=	0;
		$months		=	( $date2->year - $date1->year ) * 12;
		$months		=	$months	+ ( $date2->month - $date1->month );
		
		$m			=	( $date2->day >= $date1->day ) ? 1 : 0;
		/**
		 * Months count on is considered April 1-30, May 1-31 etc... and not April 15-May15th etc...
		 */ 
		//$months		=	$months	+ $m;
		$months		=	$months	+ 1;
		
		return $months;
		
	}
	
	/**
	 * Get the number of months difference between two dates. 
	 * Used by Newkey MLM codes
	 *
	 * @param date $date1  // must be the older date
	 * @param date $date2
	 */
	public static function getYearsCount( $date1 , $date2 ){
		
		$date1	=	new dateClass( $date1 );
		$date2	=	new dateClass( $date2 );
				
		$years		=	$date2->year  - $date1->year;
		$y			=	0;
		
		if( $date2->month > $date1->month ){
			$y 	= 	1;			
		}elseif( $date2->month == $date1->month ){
			if($date2->day >= $date1->day ){			
				$y 	= 	1;
			}	
		}
		$years		=	$years + $y;		
				
		return $years;		
	}
	
	
	function selectList($tag , $selected){
		
		$dateSelected = new dateClass($selected);
		
		if($selected){
			//$this->loadMysqlDate($selected);
		}
		
		$html .= mosHTML::monthSelectList($tag.'_month','id="'.$tag.'_month"' ,$dateSelected->month);
	 	$daylist[]	=	mosHTML::makeOption( '00', 'Day' );
	 	
	 	for($i=1;$i<32;$i++){
	 		$d = $d < 10?'0'.$i:$i;
	 		$daylist[]	=	mosHTML::makeOption( $d, $d );		 				 	
	 	}
	 	$html .= mosHTML::selectList($daylist,$tag.'_day','id="'.$tag.'_day"','value','text',$dateSelected->day);
		//$html .= mosHTML::integerSelectList(1,31,1,$tag.'_day','',$dateSelected->day, false, $initial_option);
		$initial_option	=	mosHTML::makeOption( '0000', 'Year' );
		$html .= mosHTML::integerSelectList(date('Y')-5,date('Y', mktime(0,0,0,1,1,date('Y')+1)),1,$tag.'_year',' id="'.$tag.'_year" ',$dateSelected->year,'',$initial_option);
		
		return $html;
	}
	
	function nth( $n ){
		 if( !intval($n) ){
		 	return 0;
		 }
		 
		 $mod	=	$n % 10 ;		 
		 
		 if( $mod == 1 ){
		 	return $n.'st';
		 }elseif( $mod == 2 ){
		 	return $n.'nd';
		 }elseif( $mod == 3 ){
		 	return $n.'rd';
		 }else{
		 	return $n.'th';
		 }
	}
	
	public static function getDateByMYSQLToDays( $to_days ){
		$day = $to_days - 719528;
		$daytime = $day * 86400; 
		return date( 'Y-m-d' , $daytime );		
	}
	
	public static function getDayOfTheMonthByMYSQLToDays( $to_days ){
		$day = $to_days - 719528;
		$daytime = $day * 86400; 
		return date( 'd' , $daytime );
	}
	
	public static function getDayOfTheWeekByMYSQLToDays( $to_days ){
		$date = dateClass::getDateByMYSQLToDays($to_days);
		 
		return date( 'N' , strtotime( $date ) );
	}
	
	public static function getMYSQLToDaysByDate( $date ){
		
		$timestamp = strtotime( $date );
		$days_from_epoch = ceil( $timestamp / 86400 );
		//719528 is the days from epoch to 00-00-0000
		$to_days =  719528 + $days_from_epoch; 
		return $to_days;
		
	}
	
	public static function displayTime( $datetime , $is_timestamp = false ){
		
		$timestamp = $is_timestamp ? $datetime : strtotime( $datetime );
		$now	   = time();
		
		 $diff =		 $now - $timestamp;

		 if( $diff < 60 ){
		 	return 'a minute ago';
		 }
		 
		if( $diff < 180 ){
		 	return '3 minutes ago';
		 }
		 
		if( $diff < 600 ){
		 	return '10 minutes ago';
		}
		
		if( $diff < 3600 ){			
			$v  = floor( $diff / 60 ); 			
			return $v.' minutes ago';
		}
						
		if( $diff >= 3600 &&  $diff <= 7200 ){
			return 'an hour ago';
		}
		
		if( $diff >= 7200 &&  $diff <= 14400 ){
			return '3 hrs ago';
		}
		
		if( $diff >= 7200 &&  $diff <= 86400 ){
			$v  = floor( $diff / 3600 ); 
			return $v.' hrs ago ';
		}
		
		if( $diff > 86400 ){
			$v  = floor( $diff / 86400 );
			$daytext = $v > 1 ? 'days':'day'; 
			return $v.' '.$daytext.' ago ';
		}
		
		return $diff;
	} 
}

?>