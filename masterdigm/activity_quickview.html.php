<div id="event_details_div" style="display:none;width:480px;">	

	<span id="event_details"></span><br />

	<span id="event_date_details" style="font-size:12px"></span>

	<br /><br />

	<div style="float:right"><a href="#" id="edit_event">Edit</a></div>

	<a href="#" id="delete_event">Delete</a>

</div>



<div id="delete_event_div" style="display:none;width:480px">	

	<h3>Delete recurring event</h3>

	Would you like to delete only this event or all events in the series?

	<br /><br /><input style="width:144px" class="button" type="button" name="delete_this" id="delete_this" value="Only This" /> Only this event 

	<br /><br /><input type="button" style="width:144px" class="button" name="delete_all" id="delete_all" value="All" /> All events in this series will be deleted

	<input type="hidden" name="delete_todays" id="delete_todays" value="" />

</div>



<div id="modify_event_div" style="display:none;width:480px">	

	<h3>Modify recurring event</h3>

	Would you like to modify only this event or all events in the series?

	<br /><br /><input style="width:144px" class="button" type="button" name="modify_this" id="modify_this" value="Only This" /> Only this event 

	<br /><br /><input type="button" style="width:144px" class="button" name="modify_all" id="modify_all" value="All" /> All events in this series will be modified	

</div>



<script>

	$('#edit_event').click(

		function(){

			

			modal.close();

			modal = $("#activity_form2").modal(

			  {	  

				minHeight:520,

				minWidth: 760

			  }	

			);

		}

	);



	$('#modify_all,#modify_this').click(

		function(){			

			var sc = this.value.toLowerCase();

			sc = sc.replace(" " ,"+"); 

			data = data+'&series_coverage='+sc;

			saveActivity();							

		}	

	);		



	$('#delete_all').click(

		function(){



			$("#message_txt").html( 'Activity deletion in progress' )			

			$("#delete_event_div").html( $("#message_screen").html() )

							

			$.ajax({					

				type:"POST",

				url:"<?php echo urlFormat('/index.php/proajax/calendar/deleteactivity') ?>",

				data: 'aid='+$('#activity_id').val()+'&delete_all=1',						

				success: function( html ){	

					var r = eval( '('+html+')' );

					console.log( r.result );

					modal.close();					

					if( r.result == 'success'){
						/*
						sday = parseInt( r.start_day );

						eday = parseInt( r.end_day );

						for( i = sday ; i <= eday ; i++){

							e_id = 'series_'+r.series_id+'_'+i

							scheduler.deleteEvent( e_id );

						}							
						*/
						scheduleForNextPrev('currentMonth');

					}																							

				}												

			});

		}		

	);		



	$('#delete_this').click(

			function(){	



				$("#message_txt").html( 'Activity deletion in progress' )			

				$("#delete_event_div").html( $("#message_screen").html() )

						

				$.ajax({					

					type:"POST",

					url:"<?php echo urlFormat('/index.php/proajax/calendar/deleteactivity') ?>",

					data: 'aid='+$('#activity_id').val()+'&selected_date='+$('#selected_date').val(),						

					success: function( html ){	

						var r = eval( '('+html+')' );

						console.log( r.result );

						modal.close();					

						if( r.result == 'success'){							

							//scheduler.deleteEvent( r.event_id );							
							scheduleForNextPrev('currentMonth');
						}																							

					}												

				});

			}		

		);



	$('#delete_event').click(

		function(){						

			

			if( $('#series_id').val() ){

				modal.close();				

				modal = $("#delete_event_div").modal(

				  {	  

					minHeight:220,

					minWidth: 450

				  }	

				);

			}else{

				

				$("#message_txt").html( 'Activity deletion in progress' )			

				$("#event_details_div").html( $("#message_screen").html() )

				

				$.ajax({					

					type:"POST",

					url:"<?php echo urlFormat('/index.php/proajax/calendar/deleteactivity') ?>",

					data: 'aid='+$('#activity_id').val()+'selected_date='+$('#selected_date').val(),						

					success: function( html ){	

						var r = eval( '('+html+')' );

						console.log( r.result );

						modal.close();	

						//scheduler.deleteEvent( r.activity_id );																	
						scheduleForNextPrev('currentMonth');
					}												

				});

				

			}		

			

		}

	);			

</script>