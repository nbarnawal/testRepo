<?php



defined( '_VALID_MOS' ) or die( 'Restricted access' );



checkiflogged();

loadClass( array( 'activitiesv2' , 'activity_notification2', 'time' , 'date') );

 

function view(){			

	global $database, $mosConfig_absolute_path, $mosConfig_live_site,$mainframe,$my;

	global $broker,$permissions,$userType;



	$start_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')-60, date('Y') ) ); 

	$end_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')+60 , date('Y') ) );



	$activity = new activitiesv2($database);

	$activity->load( 4 );

	echo activitiesv2::getNextRecurringActivity($activity);

	

	//echo activitiesv2::getXMLActivitiesByDateRange( $my->id , $start_date, $end_date , array( 'return_xml' =>true ) );

	//exit();

	$current_date 	=  mosGetParam( $_REQUEST , 'd' , date( 'Y-m-d' ) );	

				

	$mainframe->addScript( urlFormat( '/js/jquery/jquery-1.7.2.min.js' ) );

	$mainframe->addScript( urlFormat( '/js/dhtmlx/scheduler/dhtmlxscheduler.js' ) );

	$mainframe->addScript( urlFormat( '/js/dhtmlx/scheduler/ext/dhtmlxscheduler_timeline.js' ) );

	$mainframe->addScript( urlFormat( '/js/jquery/jquery.simplemodal.1.4.2.min.js' ) );		

	$mainframe->addCss( urlFormat( '/js/dhtmlx/scheduler/dhtmlxscheduler_glossy.css' ) );

	$mainframe->addCss( urlFormat( '/js/dhtmlx/scheduler/ext/dhtmlxscheduler_tooltip.js' ) );	

	

	$mainframe->addScript( urlFormat( '/js/dhtmlx/calendar/dhtmlxcalendar.js' ) );

	$mainframe->addCss( urlFormat( '/js/dhtmlx/calendar/dhtmlxcalendar.css' ) );

	$mainframe->addCss( urlFormat( '/js/dhtmlx/calendar/skins/dhtmlxcalendar_dhx_skyblue.css' ) );

		

	

	$mainframe->_template	=	getTemplate( 'protemplate' );

	$calendarclass	=	'class="on" title="selected"';

	$calselected	=	1;

	

	activity_notification2::remind( $my->id );

	

	$menu_path	=	$mosConfig_absolute_path.'/components/com_masterdigm/views/pro/topmenu.php';	

	require_once( $menu_path );		

	require_once($mosConfig_absolute_path.'/components/com_masterdigm/pro/calendarv2/views/day.html.php');

	

}


/*

function getactivities_1(){

	global $database , $my;

$start_date = mosGetParam( $_REQUEST , 'from' , '' );
$end_date = mosGetParam( $_REQUEST , 'to' , '' );

	
	if( $start_date == "")
	{
			//$start_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')-30, date('Y') ) ); 
			//$end_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')+365 , date('Y') ) );

	}		
	//echo $start_date." ".$end_date;  

	$start_date = "2013-01-01"; 
	$end_date = "2013-02-14";

	
	echo activitiesv2::getXMLActivitiesByDateRange( $my->id , $start_date, $end_date , array( 'return_xml' =>true ) );

	exit();

		

}
*/


function getactivities(){

	global $database , $my;

	//$start_date = mosGetParam( $_REQUEST , 'from' , '' );
	//$end_date = mosGetParam( $_REQUEST , 'to' , '' );

	$mode = mosGetParam( $_REQUEST , 'mode' , '' );
	$month = mosGetParam( $_REQUEST , 'month' , '' );


	$monsArray = array("Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4, "May" => 5, "Jun" => 6, "Jul" => 7, "Aug" =>  8, "Sep" => 9, "Oct" => 10, "Nov" => 11, 
	"Dec" => 12,"January" => 1, "February" => 2, "March" => 3, "April" => 4, "May" => 5, "June" => 6, "July" => 7, "August" =>  8, "September" => 9, "October" => 10, 
	"November" => 11, "December" => 12);
	
	$monthIndex = $monsArray[$month];

	//$end_date = date( 'Y-m-d' , mktime( 0,0,0 , -1 , '01', date('Y') ) );

	if($month == "") {
	
	$start_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m')-1 , '01', date('Y') ) );
	$end_date = date('Y-m-t',strtotime('next month'));

	//$start_date = "2013-02-01"; 
	//$end_date = "2013-02-28";
	}
	else
	{
		if($mode == "next") { //sync and load data for next 1 month from selected month
			$start_date = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex+1 , '01', date('Y') ) );
			$end_date = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex+2 , '01', date('Y') ) );    //last date of month	
		}
		elseif($mode == "prev") { //sync and load data for pre to 1 months 1 month from selected month
			$start_date = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex-2 , '01', date('Y') ) );
			$end_date = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex-1 , '01', date('Y') ) );    //last date of month	
		}
		else //sync and load data for selected/current month
		{
			$start_date = date( 'Y-m-d' , mktime( 0,0,0 , $monthIndex , '01', date('Y') ) );
			$end_date = date( 'Y-m-t' , mktime( 0,0,0 , $monthIndex , '01', date('Y') ) );    //last date of month	
		}
		
	}
	
	//echo $start_date." ".$end_date;

	echo activitiesv2::getXMLActivitiesByDateRange( $my->id , $start_date, $end_date , array( 'return_xml' =>true ) );

	exit();

		

}







function test_setnotification(){

	global $database;

	

	$activity_id = 2;

	$activity = new activitiesv2($database);

	$activity->load( $activity_id  );	

	

    if( $a = activity_notification2::setNotification( $activity  ) ){

    

    }

    

    exit();

    

}



function test_(){

	global $database;

	

	

	echo dateClass::getMYSQLToDaysByDate( date('Y-m-d')).' date = ';

	

	echo dateClass::getDateByMYSQLToDays( dateClass::getMYSQLToDaysByDate( date('Y-m-d') ) ).'<br /> ';

	

	$database->setQuery( "SELECT TO_DAYS( '".date('Y-m-d')."' ) " );

	echo  $database->loadResult().' ';

	

	$database->setQuery( "SELECT TO_DAYS( '1970-01-01' ) " );

	echo  $database->loadResult();

	

	exit();

	

}



function test_xml(){

	global $database , $my;

	

	$start_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')-30, date('Y') ) ); 

	$end_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')+365 , date('Y') ) );

	

	echo activitiesv2::getXMLActivitiesByDateRange( $my->id , $start_date, $end_date , array( 'return_xml' =>true ) );

	exit();	

}