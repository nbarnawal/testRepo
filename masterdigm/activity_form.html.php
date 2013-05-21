<div id="activity_form2" style="display:none;">

	<div id="activity_div" style="position:absolute;top:12px;left:12px;width:748px;background:#EEF;">

		<div id="activity_msg_div"></div>

		<form name="activity_form2" id="dform" action="" method="post">	

		<table cellspacing="0" cellpadding="6px" class="adminform" style="background:#EEF;width:100%" >

			<tr  bgcolor="#CCCCFF">

			 	<td><strong>Add Activity</strong></td>

			 	<td style="text-align:right" width="30%">

			 						 			

			 	</td>

			 </tr>

			<tr>

				<td style="vertical-align:top;width:50%">

					<table class="adminform" style="background:#DFDFEF">

						<tr>

							<td>Activity:</td>

						</tr>

						<tr>

							<td>

								<input type="text" id="subject2" name="subject" class="subject" size="40" value="" />

							</td>

						</tr>

						<tr>

							<td>Details:</td>

						</tr>

						<tr>

							<td><textarea class="inputbox" cols="48" rows="5" id="activity" name="activity" ></textarea></td>

						</tr>

						<tr>

							<td>

								When: <input type="text" id="start_date" class="start_date" name="start_date" value="" READONLY style="border:0px;background:#DDD"/>							

							</td>

						</tr>

						<tr>

							<td>

														

							</td>

						</tr>				

						<tr>

							<td>

								Start Time: <?php  echo activitiesv2::timeList( 'start_time' , 360 ,'start_time2') ?> 								

								End Time: <?php  echo activitiesv2::timeList( 'end_time' , 360 ,'end_time2') ?>		

						  </td>

						</tr>

						<tr>

							<td>Where: <input type="text" id="activity_where" name="activity_where" size="30" value="" /></td>

						</tr>

						

					</table>	

					<br />

					

			<table class="adminform" style="background:#DFDFEF">

					

			<tr>

				<td>

					Notify me through popup and email<br />

					<input type="text" name="notify_in[]" size="3"  value="15" />							

					<?php echo activitiesv2::selectNotificationTime( ) ?> before start time						

				</td>

			</tr>						

						<tr>

							<td>

								<input type="checkbox" name="add_gcal" value="1" /> Add to Google Calendar															

							</td>

						</tr>

						</table>

						<br />

						<table class="adminform" style="background:#DFDFEF">								

						<tr>

							<td>														
								
									<input class="button" type="button" id="save_activity" name="" value="Save" />																											

									<input class="button" type="button" id="cancel_activity" name="" value="Cancel" />

											

							</td>

						</tr>

						

						<tr>

							<td></td>

						</tr>

					</table>			

				</td>

				<td style="vertical-align:top">

					<table class="adminform" style="background:#DFDFEF">		

						<tr>

							<td>Associate with Lead:</td>

						</tr>

						<tr>

							<td>

								<div id="lead_name" contenteditable="true" style="width:180px;color:blue;min-height:18px;background:#FFFFFF;border:1px solid #CCC "></div>

								<input type="hidden" id="leadid" name="leadid" value="" />

							</td>

						</tr>

														

						<tr>

							<td> Assign To:

								<?php echo $permissions ->childrenSelectList(

									array( 'first_item' => 'Self' )

								) ?>

							</td>

						</tr>

						<tr>

							<td>

								

							</td>					

						</tr>						

						<tr>

							<td>Priority: 

							<select id="priority" name="priority" >

								<option value="low">Low</option>

								<option value="medium">Medium</option>

								<option value="high">High</option>

							</select>

							</td>

						</tr>														

						</table>	

						<br />

								<table class="adminform" style="background:#DFDFEF">

								<tr>

									<td>Access:										

											<input type="radio" name="is_public" id="is_public1" value="1"  />

											<label for="is_public1">Public</label>

											<input type="radio" name="is_public" id="is_public0" value="0" checked="checked"  />

											<label for="is_public0">Private</label>

										<div style="border:1px #AAA solid;background:#EEF;padding:4px ">

										<i>Public access allows activities to be viewed on Masterdigm websites and feeds</i> 

										</div>

									</td>

													

								</tr>

								</table>

								<br />

								<table class="adminform" style="background:#DFDFEF">

								<tr>

									<td>

										Repeat: <?php echo activitiesv2::selectRepeats(); ?>																				

									</td>

								</tr>

								

								<tr>																	

									<td>

										<div id="daily_div" class="repeat_divs" style="display:none">

										<table>

											<tr>									

												<td>Starts:</td>

												<td>

													<input type="text" name="" class="start_date" READONLY style="width:120px;border:0px;background:#DDD" value="" />																	

												</td>				

											</tr>

											<tr>

												<td>Ends:</td>												

												<td>

													<input type="text" name="repeat_until" id="repeat_until" class="repeat_ends_values" style="width:120px" value="" />

												</td>												

											</tr>											

										</table>

										</div>

										<div id="weekly_div" class="repeat_divs" style="display:none">

										<table>

											<tr>									

												<td>Starts:</td>

												<td>

													<input type="text" name="" class="start_date" READONLY style="width:120px;border:0px;background:#DDD" value="" />																	

												</td>				

											</tr>

											<tr>

												<td>Ends:</td>												

												<td>

													<input type="text" name="repeat_until_week" id="repeat_until_week" class="repeat_ends_values" style="width:120px" value="" />

												</td>

												

											</tr>

											<tr>

												<td colspan="2">Repeat every:</td>

											</tr>

											<tr>													

												<td colspan="2">																			

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" value="1" id = "repeat_weekdays[1]"/>M

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" id = "repeat_weekdays[2]" value="2" />Tu

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" id = "repeat_weekdays[3]" value="3" />W

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" value="4" id = "repeat_weekdays[4]"/>Th

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" id = "repeat_weekdays[5]" value="5" />F

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" id = "repeat_weekdays[6]" value="6" />Sat

													<input type="checkbox" name="repeat_weekdays[]" class="repeat_weekday" value="7" id = "repeat_weekdays[7]" />Sun													

												</td>

												

											</tr>

											

										</table>

										</div>	

										<div id="monthly_div" class="repeat_divs" style="display:none">

										<table>

											<tr>									

												<td>Starts:</td>

												<td>

													<input type="text" name="" class="start_date" READONLY style="width:120px;border:0px;background:#DDD" value="" />																	

												</td>				

											</tr>

											<tr>

												<td>Ends:</td>												

												<td>

													<input type="text" name="repeat_until_month" id="repeat_until_month" class="repeat_ends_values" style="width:120px" value="" />

												</td>

												

											</tr>

											<tr>

												<td colspan="2">Repeat by:</td>

											</tr>

											<tr>													

												<td colspan="2">																			

													<input type="radio" name="repeat_by_month" id="repeat_by_month_day" class="repeat_by_month" checked value="month_day" /> Day of the month<br />

													<input type="radio" name="repeat_by_month" id="repeat_by_week_day" class="repeat_by_month" value="repeat_by_week_day" /> Day of the week																										

												</td>

												

											</tr>

											<tr>													

												<td colspan="2">																			

													Summary : <span id="monthly_summary" style="font-weight:bold"></span>
													<input type = "hidden" id = "summary_value" value = "" name = "summary_value"/>

												</td>												

											</tr>

										</table>

										</div>	

										<div id="yearly_div" class="repeat_divs" style="display:none">

										<table>

											<tr>									

												<td>Starts:</td>

												<td>

													<input type="text" name="start_date_yearly" class="start_date" READONLY style="width:120px;border:0px;background:#DDD" value="" />																	

												</td>				

											</tr>

											<tr>

												<td>Ends:</td>												

												<td>

													<input type="text" name="repeat_until_year" id="repeat_until_year" class="repeat_ends_values" style="width:120px" value="" />

												</td>

												

											</tr>											

											<tr>													

												<td colspan="2">																			

													Summary : <span id="yearly_summary" style="font-weight:bold"></span>													
													<input type = "hidden" id = "day_value" value = "" name = "day_value"/>
													<input type = "hidden" id = "month_value" value = "" name = "month_value"/>
												</td>												

											</tr>

										</table>

										</div>																															

									</td>				

								</tr>

								<tr>

									<td>

										

									</td>									

								</tr>

								</table>	

								</td>

					</tr>

		</table>	

																																													    		    		    									

		<input type="hidden" name="activity_id" class="activity_id" value="" id = "activity_id"/>
		<input type="hidden" name="activity_start_time" class="activity_start_time" value="" id = "activity_start_time"/>
		<input type="hidden" name="activity_end_time" class="activity_end_time" value="" id = "activity_end_time"/>
		

		</form>									

	</div>	 	



	

</div>