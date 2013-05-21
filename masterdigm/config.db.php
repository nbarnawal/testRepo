<?php 

$option = isset( $_REQUEST['option'] ) ?	$_REQUEST['option'] : 'com_masterdigm';
$option = strval( strtolower( $option ) );

if( $option == 'login' ){
	$username = isset( $_REQUEST['username'] ) ? $_REQUEST['username'] : '';
	//unset( $_SESSION['network'] );
        //$username_segments = explode( '@' , )  
	if( preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $username ) ){		
		
		$email_section = explode( '@' , $username );			
		$network =  isset( $email_section[1] ) ? $email_section[1] : false;
		
		if( $network ){
			$_SESSION['network'] = $network;
		}else{
			unset( $_SESSION['network'] );
		}	
	}	
		 
}
if( isset( $_SESSION['network'] )){

	switch( $_SESSION['network'] ){
		case 'yabbot.com':			
			$mosConfig_host = '67.222.8.195';
			$mosConfig_user = 'myads_user2';
			$mosConfig_password = 'hjk342';
			$mosConfig_db 	= 'myads_masterdigm';
		break;	
		case 'default':
		default:
				
		break;		
	}	
}


//echo $adb;exit(); 
