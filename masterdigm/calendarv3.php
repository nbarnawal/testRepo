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

function getactivities(){
	global $database , $my;
	
	$start_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')-30, date('Y') ) ); 
	$end_date = date( 'Y-m-d' , mktime( 0,0,0 , date('m') , date('d')+365 , date('Y') ) );
	
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