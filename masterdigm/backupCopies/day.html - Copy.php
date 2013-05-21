<div style="float:right">
	<!-- 
	<input type="button" name="" value="Sync with Google Calendar" class="button" />
	 -->
</div>


<input type="text" id="clickCount" value="0" />

<div id="scheduler_div" class="dhx_cal_container" style='width:80%; height:100%;'>
		<div class="dhx_cal_navline">
			<div class="dhx_cal_prev_button">&nbsp;</div>
			<div class="dhx_cal_next_button">&nbsp;</div>
			<div class="dhx_cal_today_button"></div>
			<div class="dhx_cal_date"></div>
			<div class="dhx_cal_tab" name="day_tab" style="right:204px;"></div>
			<div class="dhx_cal_tab" name="week_tab" style="right:140px;"></div>
			<div class="dhx_cal_tab" name="month_tab" style="right:76px;"></div>
        
        </div>
		<div class="dhx_cal_header">
		</div>
		<div class="dhx_cal_data">
		</div>
</div>

<div id="edit_recurring_div" style="display:none;width:480px">
	<h3>Edit recurring event</h3>
	Would you like to change only this event or all events in the series?
	<br /><br /><input style="width:144px" class="button" type="button" name="edit_recurring" id="this_edit" value="Only This" /> Only this event 
	<br /><br /><input type="button" style="width:144px" class="button" name="edit_recurring" id="all_edit" value="All" /> All events in this series will be changed
</div>
<?php require_once($mosConfig_absolute_path.'/components/com_masterdigm/pro/calendarv2/views/activity_quickview.html.php');  ?>
<?php require_once($mosConfig_absolute_path.'/components/com_masterdigm/pro/calendarv2/views/activity_form_quick.html.php');  ?>
<?php require_once($mosConfig_absolute_path.'/components/com_masterdigm/pro/calendarv2/views/activity_form.html.php');  ?>

<div id="message_screen" style="display:none">
	<div style="padding:24px;">
	<span id="message_txt" style="font-weight:bold"></span> <img src="<?php echo urlFormat('/images/ajax-loader-fb.gif'); ?>" />
	</div>
</div>
<form>
	<input type="hidden" name="series_id" id="series_id" class="series_id" value="" />
	<input type="hidden" name="activity_id" id="activity_id" class="activity_id" value="" />
	<input type="hidden" name="event_id" id="event_id" class="event_id" value="" />
	<input type="hidden" name="selected_date" id="selected_date" class="selected_date" value="" />
</form>
<script>
	var modal;
	var change_event_flag=true;
    
	$(document).ready(
		function(){						
			$(".dhx_cal_next_button").click(function(){
				var clickVar = $("#clickCount").val();
				$("#clickCount").val(clickVar );
			});
			
			$(".dhx_cal_prev_button").click(function(){
				var clickVar = $("#clickCount").val();
				$("#clickCount").val(clickVar );
			});
			
	});
	
	$(document).ready(
		function(){						
			scheduler.config.multi_day = true;
			scheduler.config.first_hour = 6;
			scheduler.config.last_hour = 22;
			scheduler.config.xml_date="%Y-%m-%d %H:%i";
			scheduler.config.api_date="%Y-%m-%d %H:%i";
			scheduler.config.time_step  = 15;
			scheduler.config.hour_date="%h:%i %A";			
			scheduler.init('scheduler_div',new Date(),"week");
			scheduler.load("<?php echo urlFormat('/index.php/calendarv2/getactivities') ?>");

			// Event when user clicks an area without an activity			
			dhtmlxEvent(scheduler._els["dhx_cal_data"][0],"click",function(e){
				  e = e||event;
					
				  var pos = scheduler._mouse_coords(e);
				  var timestamp = scheduler._min_date.valueOf()+(pos.y*scheduler.config.time_step+(scheduler._table_view?0:pos.x)*24*60)*60000;
				  if (!scheduler._locate_event(e?e.target:event.srcElement)){
					  d = new Date(timestamp)
					
					  start_min = ( d.getHours() * 60 ) + d.getMinutes();
					  mod = start_min % 15;
					  start_min = start_min - mod;
					  end_min = start_min + 15;
					  start_date = d.getFullYear()+'-'+zeroFill(d.getMonth()+1 ,2 )+'-'+zeroFill( d.getDate() , 2 );

					  $('.subject').val( '' )
					  $('.start_time').val( start_min )
					  $('.end_time').val( end_min )
					  $('.start_date').val( start_date )
					  $('.activity_id').val( '0' );
					  					   
					  modal = $("#activity_div1").modal(
						  {	  
							minHeight:220,
							minWidth: 450
						  }	
					   );

					   						 
				  } 
				  
			})
	
			scheduler.attachEvent("onClick", function( event_id, native_event_object){												
				openEventDetails( event_id ); 	
				//any custom logic here
			});	
			
			if(change_event_flag == true)
			{
				scheduler.attachEvent( "onEventChanged" , function( event_id,event_object ){
					$.ajax({					
						type:"POST",
						url:"<?php echo urlFormat('/index.php/proajax/calendar/changeactivity') ?>",
						data: 'aid='+event_id+'&st='+event_object.start_date+'&et='+event_object.end_date,						
						success: function( html ){	
							var r = eval( '('+html+')' );
							scheduler.deleteEvent(event_id)
							sdate = r.start_date+' '+r.start_time
							edate = r.end_date+' '+r.end_time
							
							scheduler.addEvent( {
								   id:r.id,
								   start_date: sdate,
								   end_date: edate,
								   text:r.subject,						   							   
							});
							console.log( r.result );

							location.reload();
																								
						}												
					});
					//any custom logic here
				});		
			}
				
			/*** Repeats ***/
			
			$('#repeats').change(
				function(){
					repeatSelect( this.value );
				}	
			);

			$('.repeat_by_month').click(
				function(){
					monthRepeatSelect(this.value);	
				}	
			)
							
			var o_default = '';
			var ru_default = '<?php echo date('Y-m-d'); ?>';
			
			$('.repeat_ends').click(
				function(){

					$('.repeat_ends_values').attr('disabled' , true )					
															  
					switch( this.value ){
						case '1':
							$('#occurences').val( o_default )														
							$('#occurences').attr('disabled' , false )
							ru_default = $('#repeat_until').val()
							$('#repeat_until').val('')											
						break;	
						case '2':
							$('#repeat_until').val(ru_default)
							$('#repeat_until').attr('disabled' , false )
							o_default =	$('#occurences').val(); 
							$('#occurences').val( '' )		
						break;								
					}	
					
				}	

				
			);		


			
			setTimeout( syncgooglecal , 300 );			
			
		}					
	)
	
	function syncgooglecal(){
		$.ajax({					
			type:"GET",
			url:"<?php echo urlFormat('/index.php/proajax/calendar/syncgcal') ?>",
			data: '',						
			success: function( html ){	
				var r = eval( '('+html+')' );
				
				if( r.result == 'success' ){
					for( ev in r.events ){
						e = r.events[ev]
						scheduler.deleteEvent( e.id );
						if( e.status == 'add'){
							scheduler.addEvent({
								id:e.id,
								start_date: e.start_date,
						        end_date: e.end_date,
								text:e.subject,						   							   
							});
						}	 						
					}
				}	
								
				console.log( r.result );	
				scheduler.load("<?php echo urlFormat('/index.php/calendarv2/getactivities') ?>");
			}												
		});		
	}	
			
	$('#show_more').click(
		function(){
			 var subj =  $('#subject1').val();
			 start_time = $('#start_time1').val(); 
			 end_time = $('#end_time1').val();
			 modal.close();
			 modal = $("#activity_form2").modal(
			  {	  
				minHeight:520,
				minWidth: 760
			  }	
			);
			if( !$('#subject2').val() ){								
				$('#subject2').val( subj );
			}											
			$('.start_time').val( start_time );		
			$('.end_time').val( end_time );
		}	
	);

	$('#cancel_activity').click(
		function(){			
			modal.close();
		}	
	);		

	$('.start_time').change(function() {		
		if( parseInt( this.value ) >= parseInt( $('#end_time1').val() ) ){
			new_val = parseInt(this.value) + 15
			$('#end_time1').val( new_val )
			$('#end_time2').val( new_val )
		}	        
    });       
		
	$('#save_quick_activity').click(
		function(){
			$.ajax({					
				type:"POST",
				url:"<?php echo urlFormat('/index.php/proajax/calendar/saveactivity') ?>",
				data: $("#activity_form1").serialize(),
					
				success: function( html ){					
					modal.close();
					
					r = eval('('+html+')' )
					
					if( r.result =='error'){
						alert(	r.error );
					}	
					if( r.result =='success'){
						sdate = r.start_date+' '+r.start_time
						edate = r.end_date+' '+r.end_time
						if( $('#activity_id1').val() ){
							scheduler.deleteEvent( r.activity_id ); 
						}

						scheduler.addEvent( {
							   id:r.activity_id,
							   start_date: sdate,
					           end_date: edate,
							   text:r.subject,						   							   
						});
						
							
					}	
					
					console.log( r.result );																					
				}
											
			});
		}	
	);
	
	
	var data;
	
	$('#save_activity').click(
		function(){
			data = $("#dform").serialize();
			if( $('#series_id').val() != ""){				
				modal.close();
				modal = $("#modify_event_div").modal(
				  {	  
					minHeight:220,
					minWidth: 450
				  }	
				);
			}
			else{	
				saveActivity();
			}							
		}	
	);	

	function saveActivity(){		
		$.ajax({					
			type:"POST",
			url:"<?php echo urlFormat('/index.php/proajax/calendar/saveactivity') ?>",
			data: data,
				
			success: function( html ){					
									
				r = eval('('+html+')' )					
				if( r.result =='error'){
					alert(	r.error );
				}	
				if( r.result =='success'){

					modal.close();
					change_event_flag == false;
												
					if( $('#activity_id1').val() ){
					//alert('act id: '+$('#activity_id1').val());					
						scheduler.deleteEvent( r.activity_id ); 
					}
					
					if( r.repeats == 'none' ){
						scheduler.addEvent({
							   id:r.activity_id,
							   start_date: r.start_date+' '+r.start_time,
					           end_date: r.end_date+' '+r.end_time,
							   text:r.subject,						   							   
						});	
						//alert('act id 2: '+$('#activity_id').val());						
					}else{
						switch( r.repeats ){
							case 'daily':
							case 'weekly':
							case 'monthly':	
								for( ev in r.recurring_events ){
									e = r.recurring_events[ev]
									scheduler.deleteEvent( e.id );
									 
									scheduler.addEvent( {
										   id:e.id,
										   start_date: e.start_date,
								           end_date: e.end_date,
										   text:e.subject,						   							   
									});
								}		
							break;										
						}
					}																
				}	
				
				
				restPageData(html);
				//console.log( r.result );																					
			}
										
		});
	}		
	
	
	function restPageData(html)
	{
		
		$("#scheduler_div").load(location.href+ "#scheduler_div",function resFun(){
			
			var clickCount = $("#clickCount").val();
			
			if(clickCount > 0)
			{
				$("#clickCount").val(0);
				for(i=1; i<= clickCount; i++)
				{
					$("#clickCount").val(i);
					$(".dhx_cal_next_button").click();	
				}
			}
			else if(clickCount < 0)
			{
				$("#clickCount").val(0);
				for(i=1; i<= clickCount; i--)
				{
					$("#clickCount").val(i);
					$(".dhx_cal_prev_button").click();	
				}
			}
						
		} )
		
	}

	

	$('#this_edit,#all_edit').click(
		function(){

			$('#edit_what').val(this.value);						
			
			modal.close();
			populateEvent( $('#activity_id').val() )
		 	modal = $("#activity_div1").modal(
				  {	  
					minHeight:220,
					minWidth: 450
				  }	
			);	

				
		
		}	
	);

	function openEventDetails( event_id ){
	
		$('#activity_id').val( event_id );
		populateEvent(event_id)
		
		modal = $("#event_details_div").modal(
				  {	  
					height:320,
					width: 480
				  }	
			   );						   
		return;
		
		var e_str = new String( event_id );
		if( e_str.substr( 0 , 6 ) == 'series' ){
			
		}
	}
		
	function populateEvent( event_id ){
		
		$('#activity_id').val( event_id )
		$('#event_id').val( event_id )
											
		$.ajax({					
			type:	"POST",
			url:	"<?php echo urlFormat('/index.php/proajax/calendar/getactivity') ?>",
			data: 	'aid='+event_id+'&start_date='+scheduler.getEventStartDate( event_id ),
				
			success: function( html ){
				r = eval( '('+html+')' );

				if( r.result == 'not login' ){
					location.href='<?php echo urlFormat('login') ?>';
					return;
				}
					
				if( r.result == 'success' ){
					$('.subject').val( r.subject );
					$('#event_details').html( '<b>'+r.subject+'</b>' );
					$('#event_date_details').html( r.formatted_date );
					$('.start_time').val( r.start_time );								
					$('.end_time').val( r.end_time );
					$('.start_date').val( r.start_date );
					$('#activity_start_time').val(r.start_time);
					$('#activity_end_time').val(r.end_time);
					$('#activity').val( r.activity );
					$('#activity_id').val( r.id );
					$('.activity_id').val( r.id );
					$('.series_id').val( r.series_id );
					$('#selected_date').val( r.selected_date );
					$('#activity_where').val( r.activity_where );
					$('#priority').val( r.priority );
					if(r.id != "")
					{
						$('#save_activity').attr('id','save_edit_activity'); 
					}
					if( r.is_public == '1' ){
						$('#is_public1').attr( 'checked' , true );
					}else{
						$('#is_public0').attr( 'checked' , true );
					}

					if( r.repeats != 'none' ){
						$('#repeats').val( r.repeats );
						repeatSelect( r.repeats );
						$('.repeat_ends_values').val( r.repeat_until );
						switch( r.repeats ){
							case 'weekly':
							var repeat = split_weekday(r.repeat_weekdays);
								for(var i=0;i<repeat.length;i++)
									{
										var week_val = repeat[i];
										document.getElementById('repeat_weekdays['+week_val+']').checked = true;
									}
							break;
							case 'monthly':
								monthRepeatSelect( r.repeats_every ); 
								if( r.repeats_every == 'week day' ){
									$('#repeat_by_week_day').attr( 'checked' , true  );
								}	
							break;
						}	
					}																												
				}	
					
				console.log( html )		
			}												
		});
	}	

	$('#lead_name').keypress(function( event ){
		$.ajax({					
			type:"POST",
			url:"<?php echo urlFormat('/index.php/proajax/calendar/searchlead') ?>",
			data: 'q='+this.html,
				
			success: function( html ){
				alert(html)	
			}
		})	
	});	

	function repeatSelect( value ){
		
		var t = new dhtmlXCalendarObject(['repeat_until','repeat_until_week' ,'repeat_until_month','repeat_until_year' ]);
		t.hideTime();
		t.setDate( $('#start_date').val() )										
		t.setSensitiveRange( $('#start_date').val() , "<?php echo date('Y-m-d' , mktime( 0,0,0, date('m') , date('d') , date('Y')+1 ) ); ?>" );
		$('.repeat_divs').css('display', 'none');
		
		switch( value){
			case 'daily':
				$('#daily_div').css('display', 'block');
			break;	
			case 'weekly':
				$('#weekly_div').css('display', 'block');						
				if( ! $('.repeat_weekday[value=<?php echo date('N')?>]').is(':checked') ){								
					$('.repeat_weekday[value=<?php echo date('N')?>]').attr('checked' , true );
				}else{
					
				}									
			break;
			case 'monthly':							
				$('#monthly_div').css('display', 'block');
				monthRepeatSelect( 'month day' )																														
			break;
			case 'yearly':	
				var jDate = new Date( $('#start_date').val() );
				month = new Array( 'January' , 'February' , 'March' , 'April' , 'May' ,'June' , 'July' , 'August' , 'September' 
							, 'October' , 'November' ,'December'
						 );						
				$('#yearly_div').css('display', 'block');
				$('#yearly_summary').html( 'Annually on '+ month[ jDate.getMonth()]+' '+jDate.getDate()  );	
				$('#day_value').val(jDate.getDate());
				$('#month_value').val( month[ jDate.getMonth()]);	
				t.setSensitiveRange( $('#start_date').val() , "<?php echo date('Y-m-d' , mktime( 0,0,0, date('m') , date('d') , date('Y')+10 ) ); ?>" );													
			break;
		}
	}
		
	function monthRepeatSelect( value ){
		n = new Date($('#start_date').val());
		if( value=='repeat_by_week_day' ){						
			$('#monthly_summary').html( ' Every '+ weekAndDay($('#start_date').val())+' of the month ' );
			$('#summary_value').val(weekAndDay($('#start_date').val()));
		}else{
			d = n.getDate() 
			switch( d ){
				case 1:
				 	nth = 'first';
				break; 	
				case 2:
				 	nth = 'second';
				break;
				case 3:
				 	nth = 'third';
				break;
				default:
					nth = d+'th';
				break;
			}						
			$('#monthly_summary').html( 'Every '+nth+' day of the month' );	
			$('#summary_value').val(nth);
		}	
	}
		
	function zeroFill( number, width ){
	  width -= number.toString().length;
	  if ( width > 0 )
	  {
	    return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number;
	  }
	  return number + ""; // always return a string
	}

	function weekAndDay( sdate ){
		    var date = new Date(sdate),
	        days = ['Sunday','Monday','Tuesday','Wednesday',
	                'Thursday','Friday','Saturday'],
	        prefixes = ['First', 'Second', 'Third', 'Fourth', 'Last'];

	    return prefixes[0 | date.getDate() / 7] + ' ' + days[date.getDay()];
	}
function split_weekday(str)
{

var Arr=new Array();
var i,t;

for(i=0;str.toString().length>0 ;i++)
{
t=str%10;
str=str/10;
if(parseFloat(str)!=0)
str=parseInt(str);
else
break;
Arr[i]=t;

}
return Arr;
}
function changeactivity()
{
				$.ajax({					
						type:"POST",
						url:"<?php echo urlFormat('/index.php/proajax/calendar/changeactivity') ?>",
						data: 'aid='+event_id+'&st='+event_object.start_date+'&et='+event_object.end_date,						
						success: function( html ){	
							var r = eval( '('+html+')' );
							scheduler.deleteEvent(event_id)
							sdate = r.start_date+' '+r.start_time
							edate = r.end_date+' '+r.end_time
							
							scheduler.addEvent( {
								   id:r.id,
								   start_date: sdate,
								   end_date: edate,
								   text:r.subject,						   							   
							});
							console.log( r.result );
																								
						}												
					});
					//any custom logic here
				
}
	
</script>
