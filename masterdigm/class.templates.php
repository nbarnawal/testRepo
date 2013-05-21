<?php

class template extends dbextend{
	
    function  template(&$db){
       $this->mosDBTable( '#__mdigm_templates', 'templateid', $db );
       
    }

    function check(){
		
		if(trim($this->template)=='' && !$this->templateid){
			$this->_error	=	'Template name is required' ;
			return false;	
		}
		else{
     		return true; 
		}
    } 	        
} 

class Protemplate{

	public static function render( $options = array() ){
		global $mainframe,$mosConfig_absolute_path;
				
		$mainframe->_template	=	getTemplate('protemplate');
		$menu  			= 	$options['active_tab'];
		$submenu		= 	isset( $options['submenu'] ) ? $options['submenu'] :'';
		
		$tabs			=	new Top_Menu( $menu , $submenu);
		$tabs->render(); 					
		
	}
}
?>